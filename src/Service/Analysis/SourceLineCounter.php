<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis;

final class SourceLineCounter
{
    public function count(\SplFileInfo $file): int
    {
        $path = $file->getRealPath();
        if (!$path || !is_file($path) || !is_readable($path)) {
            return 0;
        }

        $lineCount = 0;
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return 0;
        }

        try {
            while (fgets($handle) !== false) {
                $lineCount++;
            }
        } finally {
            fclose($handle);
        }

        return $lineCount;
    }
}
