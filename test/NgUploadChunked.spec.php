<?php
namespace NGUC\Test;

require_once __DIR__ . "/helper/utils.php";

describe("\\NGUC\\NgUploadChunked", function () {
    beforeEach(function () {
        $this->data = "Hello im a append chunk";
        $this->TEST_DIR = __DIR__ . "/_temp";
        $this->UPLOAD_DIR = $this->TEST_DIR . "/permanent";
        $this->TEMP_DIR = $this->TEST_DIR . "/temporal";
        $this->chunkPath = __DIR__ . "/_fixture/file.chunk";
        $this->invalidPath = __DIR__ . "/XWZ:?\0/invalidpath";
        $this->fileName = "file.txt";
        $this->fileId = "unqid_661f85372";
        $this->fileIdInProgress = "unqid_7fb72d6646";
        $this->filePath = $this->TEMP_DIR . "/" . $this->fileId . ".part";
        $this->fileSize = \filesize($this->chunkPath);
        $this->nguc = new \NGUC\NgUploadChunked([
            "uploadDirectory" => $this->UPLOAD_DIR,
            "tempDirectory" => $this->TEMP_DIR,
        ]);
        $this->chunk = new \NGUC\NgFileChunk(
            $this->fileId,
            $this->fileName, 20,
            $this->fileSize, 0,
            $this->fileSize
        );
    });

    describe("->upload", function () {
        beforeEach(function () {
            $_FILES['file']['name'] = $this->fileName;
            $_FILES['file']['tmp_name'] = $this->chunkPath;
        });

        it("should return false", function () {
            $res = $this->nguc->upload($this->chunk);
            expect($res)->to->be->false;
        });
    });

    describe("->getUploadedSize", function () {

        it("should get size 0", function () {
            $res = $this->nguc->getUploadedSize("unqid_unknown");
            expect($res)->to->be->equal(0);
        });

        it("should get current size", function () {
            $_FILES['file']['name'] = $this->fileName;
            $_FILES['file']['tmp_name'] = $this->chunkPath;
            // we fake the totalSize so the file is not moved
            $this->chunk = new \NGUC\NgFileChunk(
                $this->fileIdInProgress,
                $this->fileName, 20,
                $this->fileSize, 0,
                $this->fileSize * 2
            );
            // we make a unfinished upload
            $this->nguc->upload($this->chunk);

            $res = $this->nguc->getUploadedSize($this->fileIdInProgress);
            expect($res)->to->be->equal(17);
        });
    });

    describe("->abort", function () {

        it("should return true after remove the file", function () {
            $_FILES['file']['name'] = $this->fileName;
            $_FILES['file']['tmp_name'] = $this->chunkPath;

            $this->chunk = new \NGUC\NgFileChunk(
                $this->fileIdInProgress,
                $this->fileName, 20,
                $this->fileSize, 0,
                $this->fileSize * 2
            );

            $this->nguc->upload($this->chunk);

            $res = $this->nguc->abort($this->fileIdInProgress);
            expect($res)->to->be->true;
        });
    });

    describe("_moveWhenFinished", function () {
        beforeEach(function () {
            // Get private method
            $this->method = new \ReflectionMethod("\NGUC\NgUploadChunked", "moveWhenFinished");
            $this->method->setAccessible(true);
        });

        it("should move the file", function () {
            $exception = null;
            $movedChunkPath = $this->UPLOAD_DIR . "/move.txt";
            $chunkPath = $this->TEMP_DIR . "/move.chunk";
            file_put_contents($chunkPath, $this->data);
            $fileSize = filesize($chunkPath);
            try {
                $this->method->invokeArgs(
                    $this->nguc,
                    [$chunkPath, $movedChunkPath, $fileSize]
                );
            } catch (\NGUC\NGUCException $e) {
                $exception = $e;
            }
            expect($exception)->to->be->null;
            expect(file_exists($movedChunkPath))->to->be->true;
        });

        it("should throw error when is unable to get the filesize", function () {
            try {
                $exception = $this->method->invokeArgs(
                    $this->nguc,
                    [$this->invalidPath, $this->UPLOAD_DIR, $this->fileSize]
                );
            } catch (\NGUC\NGUCException $e) {
                $exception = $e;
            }
            expect($exception)->to->be->not->null;
            expect($exception->getCode())->to->be->equal(\NGUC\NGUCException::CANT_MOVE_FILE);
        });

        it("should throw error when is unable to move the final file", function () {
            try {
                $exception = $this->method->invokeArgs(
                    $this->nguc,
                    [$this->chunkPath, $this->invalidPath, $this->fileSize]
                );
            } catch (\NGUC\NGUCException $e) {
                $exception = $e;
            }
            expect($exception)->to->be->not->null;
            expect($exception->getCode())->to->be->equal(\NGUC\NGUCException::CANT_MOVE_FILE);
        });
    });

    describe("_validateChunk", function () {
        beforeEach(function () {
            // Get private method
            $this->method = new \ReflectionMethod("\NGUC\NgUploadChunked", "validateChunk");
            $this->method->setAccessible(true);
        });

        it("should validate true", function () {
            try {
                $res = $this->method->invokeArgs($this->nguc, [$this->chunk]);
            } catch (\NGUC\NGUCException $e) {
                $res = $e;
            }
            expect($res)->to->be->true;
        });

        it("should throw error if the chunk is invalid", function () {
            $currentFileId = $this->chunk->fileId;
            try {
                $this->chunk->fileId = null;
                $res = $this->method->invokeArgs($this->nguc, [$this->chunk]);
            } catch (\NGUC\NGUCException $e) {
                $res = $e;
            }
            expect($res)->to->be->not->null;
            expect($res->getCode())->to->be->equal(\NGUC\NGUCException::INVALID_CHUNK);

            $this->chunk->fileId = $currentFileId;
        });

        it("should throw error when there is not file being uploaded", function () {
            $currentFileId = $this->chunk->fileId;
            try {
                unset($_FILES['file']['tmp_name']);
                $this->chunk->fileId = $this->fileIdInProgress;
                $res = $this->method->invokeArgs($this->nguc, [$this->chunk]);
            } catch (\NGUC\NGUCException $e) {
                $res = $e;
            }
            expect($res)->to->be->not->null;
            expect($res->getCode())->to->be->equal(\NGUC\NGUCException::NOT_UPLD_FILE);
            $this->chunk->fileId = $currentFileId;
        });
    });

    describe("_readUploadedChunk", function () {
        beforeEach(function () {
            // Get private method
            $this->method = new \ReflectionMethod("\NGUC\NgUploadChunked", "readUploadedChunk");
            $this->method->setAccessible(true);
        });

        it("should read the chunk", function () {
            $_FILES['file']['tmp_name'] = $this->chunkPath;
            try {
                $data = $this->method->invokeArgs($this->nguc, []);
            } catch (\NGUC\NGUCException $e) {
                $data = $e;
            }
            expect($data)->to->be->equal("Hello im a chunk ");
        });

        it("should throw error when is unable to read the chunk", function () {
            $_FILES['file']['tmp_name'] = __DIR__ . "/invalid.chunk";
            try {
                $exception = $this->method->invokeArgs($this->nguc, []);
            } catch (\NGUC\NGUCException $e) {
                $exception = $e;
            }
            expect($exception)->to->be->not->null;
            expect($exception->getCode())->to->be->equal(\NGUC\NGUCException::CANT_READ_UPLD_CHUNK);
        });
    });

    describe("_appendChunk", function () {
        beforeEach(function () {
            // Get private method
            $this->method = new \ReflectionMethod("\NGUC\NgUploadChunked", "appendChunk");
            $this->method->setAccessible(true);
        });

        it("should append chunk", function () {
            $path = $this->TEMP_DIR . "/append.chunk";
            file_put_contents($path, $this->data);

            try {
                $this->method->invokeArgs($this->nguc, [$path, $this->data]);
            } catch (\NGUC\NGUCException $e) {
                $res = $e;
            }

            $res = file_get_contents($path);
            expect($res)->to->be->equal($this->data . $this->data);
        });

        it("should throw error when is unable to append the chunk", function () {
            try {
                $invalidPath = $this->invalidPath . "/append.chunk";
                $exception = $this->method->invokeArgs($this->nguc, [$invalidPath, $this->data]);
            } catch (\NGUC\NGUCException $e) {
                $exception = $e;
            }
            expect($exception)->to->be->not->null;
            expect($exception->getCode())->to->be->equal(\NGUC\NGUCException::CANT_APPEND_CHUNK);
        });
    });

    describe("_prepareDirectories", function () {
        it("should get default temporal directory", function () {
            $nguc = new \NGUC\NgUploadChunked([
                "uploadDirectory" => $this->UPLOAD_DIR,
            ]);

            $this->method = new \ReflectionMethod("\NGUC\NgUploadChunked", "prepareDirectories");
            $this->method->setAccessible(true);
            $this->method->invokeArgs($nguc, []);
            $config = $nguc->getConfig();

            expect($config['tempDirectory'])->to->be->not->empty;
        });

        it("should throw error when is unable to create temporal directory", function () {
            $nguc = new \NGUC\NgUploadChunked([
                "uploadDirectory" => $this->UPLOAD_DIR,
                "tempDirectory" => $this->invalidPath,
            ]);

            $this->method = new \ReflectionMethod("\NGUC\NgUploadChunked", "prepareDirectories");
            $this->method->setAccessible(true);

            try {
                $exception = $this->method->invokeArgs($nguc, []);
            } catch (\NGUC\NGUCException $e) {
                $exception = $e;
            }

            expect($exception)->to->be->not->null;
            expect($exception->getCode())->to->be->equal(\NGUC\NGUCException::NOT_TEMP_DIR);
        });

        it("should throw error when is unable to create upload directory", function () {
            $nguc = new \NGUC\NgUploadChunked([
                "uploadDirectory" => $this->invalidPath,
            ]);

            $this->method = new \ReflectionMethod("\NGUC\NgUploadChunked", "prepareDirectories");
            $this->method->setAccessible(true);

            try {
                $exception = $this->method->invokeArgs($nguc, []);
            } catch (\NGUC\NGUCException $e) {
                $exception = $e;
            }

            expect($exception)->to->be->not->null;
            expect($exception->getCode())->to->be->equal(\NGUC\NGUCException::NOT_UPLD_DIR);
        });
    });

    // used only to clean the file generated
    describe("dummy", function () {
        it("clean test files", function () {
            removeDir($this->TEST_DIR);
        });
    });
});
