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
     * @var boolean
     */
    protected $finished = false;

    /**
     *
     * @var [NgFileChunk]
     */
    protected $currentChunk = null;

    /**
     *
     * @var array
     */
    protected $defaultConfig = [
        "ext" => ".part",
        "fileInputName" => "file",
        "directoryPermission" => 0755,
        "readChunkSize" => 1048576, // 1MB
        "useTempDirectory" => true,
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
     *
     * @return boolean $finished
     */
    public function isFinished()
    {
        return $this->finished;
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
        $this->prepareDirectories();
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
        $this->prepareDirectories();
        $this->finished = false;
        $filePath = $this->getFilePath($fileId);
        $this->clean($filePath);

        return true;
    }
    /**
     * Handle the uploaded by chunk
     *
     * @param NgFileChunk $chunk
     * @throws NGUCException
     * @return void
     */
    public function process(NgFileChunk $chunk)
    {
        $this->prepareDirectories();
        $this->validateChunk($chunk);
        $this->finished = false;
        $this->currentChunk = $chunk;

        $filePath = $this->getFilePath($chunk->fileId);
        $destPath = $this->getUploadPath();

        // Read Uploaded chunk and Append to temporal file
        $this->readAndAppendChunk($filePath);

        // Check if file upload has been completed
        $this->moveWhenFinished($filePath, $destPath, $chunk->totalSize);
    }

    /**
     * Gets the path where the file will be stored
     * after is fully uploaded
     *
     * @return void
     */
    public function getUploadPath()
    {
        $destFolder = $this->config['uploadDirectory'] . DIRECTORY_SEPARATOR;
        return ($this->currentChunk)
        ? $destFolder . $this->currentChunk->name
        : $destFolder;
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
            $this->finished = true;
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
     * And write it directly to the destination
     *
     * @param string $path
     * @param string $data
     * @throws NGUCException
     * @return void
     */
    protected function readAndAppendChunk($path)
    {
        $key = $this->config['fileInputName'];
        $uploadedChunkPath = $_FILES[$key]['tmp_name'];
        $uploadHandler = @\fopen($uploadedChunkPath, "rb");
        $writeHandler = @\fopen($path, "ab");

        if (!$uploadHandler) {
            throw new NGUCException(
                "Couldn't read chunk: '{$uploadedChunkPath}'",
                NGUCException::CANT_READ_UPLD_CHUNK
            );
        }

        if (!$writeHandler) {
            throw new NGUCException(
                "Couldn't append the data to path: {$path}",
                NGUCException::CANT_APPEND_CHUNK
            );
        }

        while (!\feof($uploadHandler)) {
            $data = \fread($uploadHandler, $this->config['readChunkSize']);
            \fwrite($writeHandler, $data);
        }

        \fclose($uploadHandler);
        \fclose($writeHandler);
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
        $chmod = $this->config['directoryPermission'];

        // By default it will use a temporal folder
        // to store the chunk while is been uploaded
        if ($this->config['useTempDirectory']) {
            $defaultTempFolder = $this->getDefaultTempFolder();

            // if not defined use default one
            if (empty($this->config['tempDirectory'])) {
                $this->config['tempDirectory'] = $defaultTempFolder;
            } elseif (!@\file_exists($this->config['tempDirectory'])) {
                if (!@\mkdir($this->config['tempDirectory'], $chmod, true)) {
                    throw new NGUCException(
                        "Temporal directory '{$this->config['tempDirectory']} with permission '{$chmod}', " .
                        "couldn't be created",
                        NGUCException::NOT_TEMP_DIR
                    );
                }
            }
        }

        // Upload Directory
        if (empty($this->config['uploadDirectory'])) {
            $this->config['uploadDirectory'] = !empty($_SERVER['DOCUMENT_ROOT'])
            ? $_SERVER['DOCUMENT_ROOT']
            : getcwd();
        } elseif (!@\file_exists($this->config['uploadDirectory'])) {
            if (!@\mkdir($this->config['uploadDirectory'], $chmod, true)) {
                throw new NGUCException(
                    "Upload directory '{$this->config['tempDirectory']} with permission '{$chmod}', " .
                    "couldn't be created",
                    NGUCException::NOT_UPLD_DIR
                );
            }
        }

        // if use of temporal folder is disabled
        // we set as the upload directory, so it will
        // been uploaded in the destination folder directly
        if (!$this->config['useTempDirectory']) {
            $this->config['tempDirectory'] = $this->config['uploadDirectory'];
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
        return $tempDir;
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
