<?php
namespace NGUC;

/**
 * Class NgFileChunk
 *
 * @author Jose De Gouveia
 * @package NGUC
 */
class NgFileChunk
{

    /**
     * an id to identify the file being processes
     *
     * @var string
     */
    public $fileId = "";

    /**
     * File name
     *
     * @var string
     */
    public $name = "";

    /**
     * The size of the chunk
     * that the file is being
     * split
     *
     * @var integer
     */
    public $size = 0;

    /**
     * The size of the current chunk
     * being upload
     *
     * @var integer
     */
    public $currentSize = 0;

    /**
     * The current number of the chunk
     *
     * @var integer
     */
    public $number = 0;

    /**
     * Total size of the file when completed
     *
     * @var integer
     */
    public $totalSize = 0;

    /**
     *
     * @param string $fileId
     * @param string $name
     * @param integer $size
     * @param integer $currentSize
     * @param integer $number
     * @param integer $totalSize
     */
    public function __construct(
        $fileId = "",
        $name = "",
        $size = 0,
        $currentSize = 0,
        $number = 0,
        $totalSize = 0
    ) {
        $this->fileId = filter_var($fileId, FILTER_SANITIZE_STRING);
        $this->name = filter_var($name, FILTER_SANITIZE_STRING);
        $this->size = !empty($size) ? $size : 0;
        $this->currentSize = !empty($currentSize) ? $currentSize : 0;
        $this->number = !empty($number) ? $number : 0;
        $this->totalSize = !empty($totalSize) ? $totalSize : 0;
    }

    /**
     * Populate the chunk the rest of the data
     * from the request
     *
     * @param string $fileId
     * @param string $name
     * @return void
     */
    public function populate($fileId, $name)
    {
        $this->fileId = filter_var($fileId, FILTER_SANITIZE_STRING);
        $this->name = filter_var($name, FILTER_SANITIZE_STRING);
        $this->size = filter_input(INPUT_POST, "_chunkSize", FILTER_SANITIZE_STRING);
        $this->currentSize = filter_input(INPUT_POST, "_currentChunkSize", FILTER_SANITIZE_STRING);
        $this->number = filter_input(INPUT_POST, "_chunkNumber", FILTER_SANITIZE_STRING);
        $this->totalSize = filter_input(INPUT_POST, "_totalSize", FILTER_SANITIZE_STRING);
    }
}
