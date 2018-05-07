# Ng Upload Chunked

[![Packagist Version](https://img.shields.io/packagist/v/hgouveia/ng-upload-chunked.svg?style=flat-square "Packagist Version")](https://packagist.org/packages/hgouveia/ng-upload-chunked)
[![Build Status](https://img.shields.io/travis/hgouveia/ng-upload-chunked/master.svg?style=flat-square "Build Status")](https://travis-ci.org/hgouveia/ng-upload-chunked)
[![HHVM Build Status](https://img.shields.io/badge/hhvm-tested-brightgreen.svg?style=flat-square "HHVM Build Status")](https://travis-ci.org/hgouveia/ng-upload-chunked)
[![Windows Build Status](https://img.shields.io/appveyor/ci/hgouveia/ng-upload-chunked/master.svg?label=windows&style=flat-square "Windows Build Status")](https://ci.appveyor.com/project/hgouveia/ng-upload-chunked)


Php implementation of the file chunked upload for the angular directive [ng-file-upload](https://github.com/danialfarid/ng-file-upload)

**Note:** it could work for any html5 uploader with chunked upload if `NgFileChunk` is constructed properly 

## Install

Clone or download this repo, see the example

With Composer
```
$ composer require hgouveia/ng-upload-chunked
```

## Example of Usage

[API doc](API.md)

Check complete usage in the [example](example/) folder

```php
<?php
// In your POST handler
/*
 $defaultConfig = [
        "ext" => ".part",
        "fileInputName" => "file",
        "directoryPermission" => 0755,
        "readChunkSize" => 1048576, // 1MB
        "uploadDirectory" => "",
        "tempDirectory" => "",
    ];
*/
$nguc = new \NGUC\NgUploadChunked(); //optional $config param

try {
    // Contains the information of the current chunk
    $chunk = new \NGUC\NgFileChunk(
        $_POST['_uniqueId'],
        $_FILES['file']['name'],
        $_POST['_chunkSize'],
        $_POST['_currentChunkSize'],
        $_POST['_chunkNumber'],
        $_POST['_totalSize'],
    );
    
    // this could be used instead, if ng-file-upload is beign used
    //$chunk = new \NGUC\NgFileChunk();
    //$chunk->populate($_POST['_uniqueId'], $_FILES['file']['name']);

    $nguc->upload($chunk);
} catch (\NGUC\NGUCException $e) {
    echo "ERROR: " . $e->getCode() . " - " . $e->getMessage();
}
```

## Test

```
$ ./vendor/bin/peridot test
```

or if npm is available

```
$ npm test
```

## License

Read [License](LICENSE) for more licensing information.

## Contributing

Read [here](CONTRIBUTING.md) for more information.
