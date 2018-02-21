<?php
use Evenement\EventEmitterInterface;
use Peridot\Reporter\CodeCoverageReporters;
use Peridot\Reporter\CodeCoverage\CodeCoverageReporter;

return function (EventEmitterInterface $emitter) {
    $coverage = new CodeCoverageReporters($emitter);
    $coverage->register();

    $emitter->on('code-coverage.start', function (CodeCoverageReporter $reporter) {
        $reporter->addDirectoryToWhitelist(__DIR__ . '/src');
    });
};
