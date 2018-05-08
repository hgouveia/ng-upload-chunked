<?php
namespace NGUC;

/**
 *
 * Class NgUploadChunked
 *
 * @author Jose De Gouveia
 * @package NGUC
 */
class NgUploadChunked
{

    /**
     *
     * @var array
     */
    protected $config = [];

    /**
     *
     * @var array
     */
    protected $defaultConfig = [
        "ext" => ".part",
        "fileInputName" => "file",
        "directoryPermission" => 0755,
        "readChunkSize" => 1048576, // 1MB
        "uploadDirectory" => "",
        "tempDirectory" => "",
    ];

    /**
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     *
     * @param array $config
     * @throws NGUCException
     */
    public function setConfig(array $config = [])
    {
        $this->config = \array_merge($this->defaultConfig, $config);
    }

    /**
     *
     * @return array $config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Gets the current size fo the file
     * that is being uploaded in chunks
     *
     * @param string $fileId
     * @return int
     */
    public function getUploadedSize($fileId)
    {
        $size = 0;
        $path = $this->getFilePath($fileId);

        if (\file_exists($path)) {
            $size = \filesize($path);
        }

        return $size;
    }

    /**
     * Abort the current upload
     * by deleting the current file been uploaded
     *
     * @param string $fileId
     * @throws NGUCException
     * @return boolean
     */
    public function abort($fileId)
    {
        $filePath = $this->getFilePath($fileId);
        $this->clean($filePath);
        return true;
    }
    /**
     * Handle the upload by chunk
     *
     * @param NgFileChunk $chunk
     * @throws NGUCException
     * @return void
     */
    public function upload(NgFileChunk $chunk)
    {
        $this->prepareDirectories();
        $this->validateChunk($chunk);

        $filePath = $this->getFilePath($chunk->fileId);
        $destPath = $this->config['uploadDirectory'] . DIRECTORY_SEPARATOR . $chunk->name;

        // 1. Read Uploaded chunk
        $data = $this->readUploadedChunk();

        // 2. Append chunk
        $this->appendChunk($filePath, $data);

        // 3. Check if file has been uploaded
        $this->moveWhenFinished($filePath, $destPath, $chunk->totalSize);
    }

    /**
     * Move to the destination folder when finished
     *
     * @param string $filePath
     * @param string $destPath
     * @param int $totalSize
     * @throws NGUCException
     * @return void
     */
    private function moveWhenFinished($filePath, $destPath, $totalSize)
    {
        $uploadedSize = @\filesize($filePath);
        $moveEx = new NGUCException(
            "Couldn't Move the file from '{$filePath}' to '{$destPath}'",
            NGUCException::CANT_MOVE_FILE
        );

        if (!$uploadedSize) {
            throw $moveEx;
        }

        if ($uploadedSize === \intVal($totalSize)) {
            if (!@\rename($filePath, $destPath)) {
                throw $moveEx;
            }
        }
    }

    /**
     * Validate if the chunk gotten from the request
     * has the required fields or are valid
     *
     * @param NgFileChunk $chunk
     * @throws NGUCException
     * @return boolean
     */
    protected function validateChunk(NgFileChunk $chunk)
    {
        $validateFields = ["fileId", "name", "size", "totalSize"];

        // if empty, probably the client exited the page while uploading
        // or didnt attach any file
        $key = $this->config['fileInputName'];
        if (empty($_FILES[$key]['tmp_name'])) {
            $filePath = $this->getFilePath($chunk->fileId);
            $this->clean($filePath);
            throw new NGUCException(
                "No Upload File",
                NGUCException::NOT_UPLD_FILE
            );
        }

        $invalidFields = [];
        foreach ($validateFields as $field) {
            if (empty($chunk->{$field})) {
                $invalidFields[] = $field;
            }
        }

        if (!empty($invalidFields)) {
            $invalidFields = implode(",", $invalidFields);
            throw new NGUCException(
                "'{$invalidFields}' were empty or not defined",
                NGUCException::INVALID_CHUNK
            );
        }
        return true;
    }

    /**
     * Read the data from the file chunk uploaded
     *
     * @throws NGUCException
     * @return string data
     */
    protected function readUploadedChunk()
    {
        $data = "";
        $key = $this->config['fileInputName'];
        $uploadedChunkPath = $_FILES[$key]['tmp_name'];
        $uploadHandler = @\fopen($uploadedChunkPath, "rb");

        if (!$uploadHandler) {
            throw new NGUCException(
                "Couldn't read chunk: '{$uploadedChunkPath}'",
                NGUCException::CANT_READ_UPLD_CHUNK
            );
        }

        while (!\feof($uploadHandler)) {
            $data .= \fread($uploadHandler, $this->config['readChunkSize']);
        }
        \fclose($uploadHandler);

        return $data;
    }

    /**
     * Append chunk to the file
     *
     * @param string $path
     * @param string $data
     * @throws NGUCException
     * @return void
     */
    protected function appendChunk($path, $data)
    {
        $handler = @\fopen($path, "ab");

        if (!$handler) {
            throw new NGUCException(
                "Couldn't append the data to path: {$path}",
                NGUCException::CANT_APPEND_CHUNK
            );
        }

        \fwrite($handler, $data);
        \fclose($handler);
    }

    /**
     * Get the path of the file
     * where the chunks are being appended
     *
     * @param string $fileId
     * @return void
     */
    protected function getFilePath($fileId)
    {
        $ext = $this->config['ext'];
        $path = $this->config['tempDirectory'] . DIRECTORY_SEPARATOR . $fileId . $ext;
        return $path;
    }

    /**
     * Create the temporal and upload directory
     *
     * @throws NGUCException
     * @return void
     */
    private function prepareDirectories()
    {
        $defaultTempFolder = $this->getDefaultTempFolder();
        $chmod = $this->config['directoryPermission'];

        // if not defined use default one
        if (empty($this->config['tempDirectory'])) {
            $this->config['tempDirectory'] = $defaultTempFolder;
        }

        if (!@\file_exists($this->config['tempDirectory'])) {
            if (!@\mkdir($this->config['tempDirectory'], $chmod, true)) {
                throw new NGUCException(
                    "Temporal directory '{$this->config['tempDirectory']} with permission '{$chmod}', " .
                    "couldn't be created",
                    NGUCException::NOT_TEMP_DIR
                );
            }
        }

        // Upload Directory
        if (!@\file_exists($this->config['uploadDirectory'])) {
            if (!@\mkdir($this->config['uploadDirectory'], $chmod, true)) {
                throw new NGUCException(
                    "Upload directory '{$this->config['tempDirectory']} with permission '{$chmod}', " .
                    "couldn't be created",
                    NGUCException::NOT_UPLD_DIR
                );
            }
        }
    }

    /**
     * Set the default temporal folder
     * of php or the system
     *
     * @return string
     */
    private function getDefaultTempFolder()
    {
        $sysTempDir = \sys_get_temp_dir();
        $phpTempDir = \ini_get("upload_tmp_dir");
        $tempDir = !empty($phpTempDir) ? $phpTempDir : $sysTempDir;
        return $tempDir . DIRECTORY_SEPARATOR . "nguc";
    }

    /**
     * Remove the file that was being processed
     *
     * @param string $filePath
     * @throws NGUCException
     * @return void
     */
    private function clean($filePath)
    {
        if (\file_exists($filePath)) {
            if (!@\unlink($filePath)) {
                throw new NGUCException(
                    "Couldn't remove the file: '{$filePath}', probably due permissions",
                    NGUCException::CANT_CLEAN_FILE
                );
            }
        }
    }
}
