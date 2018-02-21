<?php
namespace NGUC;

/**
 * Class NGUCException
 *
 * @author Jose De Gouveia
 * @package NGUC
 */
class NGUCException extends \Exception
{
    const EMPTY_CHUNK_NAME = 10001;
    const INVALID_CHUNK = 10002;
    const NOT_TEMP_DIR = 10003;
    const NOT_UPLD_DIR = 10004;
    const NOT_UPLD_FILE = 10005;
    const CANT_READ_UPLD_CHUNK = 10006;
    const CANT_APPEND_CHUNK = 10007;
    const CANT_CLEAN_FILE = 10008;
    const CANT_MOVE_FILE = 10009;
}
