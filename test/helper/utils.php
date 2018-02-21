<?php
function removeDir($directory)
{
    $output = [];

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd = "rmdir \"{$directory}\" /S /Q";
    } else {
        $cmd = "rm -rf \"{$directory}\"";
    }

    $lastLine = exec($cmd, $output);

    return $output;
}
