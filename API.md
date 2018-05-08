## Table of contents

- [\NGUC\NgFileChunk](#class-ngucngfilechunk)
- [\NGUC\NGUCException](#class-ngucngucexception)
- [\NGUC\NgUploadChunked](#class-ngucnguploadchunked)

<hr />

### Class: \NGUC\NgFileChunk

> Class NgFileChunk

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>string</em> <strong>$fileId=`''`</strong>, <em>string</em> <strong>$name=`''`</strong>, <em>integer</em> <strong>$size</strong>, <em>integer</em> <strong>$currentSize</strong>, <em>integer</em> <strong>$number</strong>, <em>integer</em> <strong>$totalSize</strong>)</strong> : <em>void</em> |
| public | <strong>populate(</strong><em>string</em> <strong>$fileId</strong>, <em>string</em> <strong>$name</strong>)</strong> : <em>void</em><br /><em>Populate the chunk the rest of the data from the request</em> |

<hr />

### Class: \NGUC\NGUCException

> Class NGUCException

| Visibility | Function |
|:-----------|:---------|

*This class extends \Exception*

*This class implements \Throwable*

<hr />

### Class: \NGUC\NgUploadChunked

> Class NgUploadChunked

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>array</em> <strong>$config=array()</strong>)</strong> : <em>void</em> |
| public | <strong>abort(</strong><em>string</em> <strong>$fileId</strong>)</strong> : <em>boolean</em><br /><em>Abort the current upload by deleting the current file been uploaded</em> |
| public | <strong>getConfig()</strong> : <em>array $config</em> |
| public | <strong>getUploadedSize(</strong><em>string</em> <strong>$fileId</strong>)</strong> : <em>int</em><br /><em>Gets the current size fo the file that is being uploaded in chunks</em> |
| public | <strong>setConfig(</strong><em>array</em> <strong>$config=array()</strong>)</strong> : <em>void</em> |
| public | <strong>upload(</strong><em>[\NGUC\NgFileChunk](#class-ngucngfilechunk)</em> <strong>$chunk</strong>)</strong> : <em>void</em><br /><em>Handle the upload by chunk</em> |
| protected | <strong>appendChunk(</strong><em>string</em> <strong>$path</strong>, <em>string</em> <strong>$data</strong>)</strong> : <em>void</em><br /><em>Append chunk to the file</em> |
| protected | <strong>getFilePath(</strong><em>string</em> <strong>$fileId</strong>)</strong> : <em>void</em><br /><em>Get the path of the file where the chunks are being appended</em> |
| protected | <strong>readUploadedChunk()</strong> : <em>string data</em><br /><em>Read the data from the file chunk uploaded</em> |
| protected | <strong>validateChunk(</strong><em>[\NGUC\NgFileChunk](#class-ngucngfilechunk)</em> <strong>$chunk</strong>)</strong> : <em>boolean</em><br /><em>Validate if the chunk gotten from the request has the required fields or are valid</em> |

