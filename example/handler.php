<?php
// This could be replaced with composer autoload
include "../src/NgFileChunk.php";
include "../src/NGUCException.php";
include "../src/NgUploadChunked.php";

// by default tempDirectory will use the php tmp dir
// or the sys temp dir if not specified
$TEMP_DIR = __DIR__ . "/../temp/upload_tmp";
$UPLOAD_DIR = __DIR__ . "/../temp/upload_test";

if (empty($_REQUEST['_uniqueId'])) {
    echo "Action Invalid";
    return;
}

$nguc = new \NGUC\NgUploadChunked([
    "uploadDirectory" => $UPLOAD_DIR,
    "tempDirectory" => $TEMP_DIR,
]);

switch ($_GET['q']) {
    case "upload":
        try {
            $chunk = new \NGUC\NgFileChunk();
            $chunk->populate($_POST['_uniqueId'], $_FILES['file']['name']);
            $nguc->upload($chunk);
        } catch (NGUCException $e) {
            echo "ERROR: " . $e->getCode() . " - " . $e->getMessage();
        }
        break;
    case "status":
        echo $nguc->getUploadedSize($_GET['_uniqueId']);
        break;
    default:
        echo "Action Invalid";
        break;
}
