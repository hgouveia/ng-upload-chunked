<?php
namespace NGUC\Test;

describe("\\NGUC\\NgFileChunk", function () {

    describe("__construct", function () {
        it("should clean the input", function () {
            $chunk = new \NGUC\NgFileChunk(
                'myId?q=\'><?=echo \\"<script>alert(\'hello\')</script>', //fileId
                'file.zip?echo="<script>"' // name
            );

            expect($chunk->fileId)->to->be->equal("myId?q=&#39;>");
            expect($chunk->name)->to->be->equal("file.zip?echo=&#34;&#34;");
        });
    });

    describe("->populate", function () {
        beforeEach(function () {
            $this->chunk = new \NGUC\NgFileChunk();
        });

        it("should clean the input", function () {
            $this->chunk->populate(
                'myId?q=\'><?=echo \\"<script>alert(\'hello\')</script>', //fileId
                'file.zip?echo="<script>"' // name
            );

            expect($this->chunk->fileId)->to->be->equal("myId?q=&#39;>");
            expect($this->chunk->name)->to->be->equal("file.zip?echo=&#34;&#34;");
        });
    });
});
