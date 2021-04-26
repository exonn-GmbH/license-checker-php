<?php

namespace LicenseChecker\Composer;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DependencyTreeRetriever
{
    private static string $output = '';

    public function getDependencyTree(bool $noDev): string
    {
        if (!empty(self::$output)) {
            return self::$output;
        }

		$noDevArguments = $noDev ? ['--no-dev'] : [];

        $process = new Process(array_merge(['composer', 'show', '-t', '-f', 'json'], $noDevArguments));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        self::$output = $process->getOutput();

        return self::$output;
    }
}
