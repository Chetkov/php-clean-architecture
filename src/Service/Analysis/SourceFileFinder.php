<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis;

use Chetkov\PHPCleanArchitecture\Model\Path;

final class SourceFileFinder
{
    /**
     * @param array<Path> $paths
     * @param string $fileExtension
     * @param array<string> $shebangTemplates example: ['/usr/bin/env php', '/usr/bin/php']
     *
     * @return CompositeCountableIterator<\SplFileInfo>
     */
    public function find(
        array $paths,
        string $fileExtension = '.php',
        array $shebangTemplates = ['/usr/bin/env php', '/usr/bin/php']
    ): CompositeCountableIterator {
        /** @var CompositeCountableIterator<\SplFileInfo> $filesIterator */
        $filesIterator = new CompositeCountableIterator();
        foreach ($paths as $path) {
            if (is_file($path->path())) {
                $filesIterator->addIterator(new \ArrayIterator([new \SplFileInfo($path->path())]));
                continue;
            }
            if (!is_dir($path->path())) {
                continue;
            }

            /** @var \RecursiveIteratorIterator<\RecursiveDirectoryIterator> $recursiveDirectoryIterator */
            $recursiveDirectoryIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path->path()));
            $phpFiles = [];
            $phpExtIterator = new \RegexIterator($recursiveDirectoryIterator, "/\\$fileExtension$/i");
            /** @var \SplFileInfo $phpFile */
            foreach ($phpExtIterator as $phpFile) {
                $phpFiles[] = $phpFile;
            }
            if ($phpFiles !== []) {
                /** @var \ArrayIterator<int, \SplFileInfo> $phpFilesIterator */
                $phpFilesIterator = new \ArrayIterator($phpFiles);
                $filesIterator->addIterator($phpFilesIterator);
            }

            $phpFilesWithoutPhpExtensions = [];
            $notPhpExtIterator = new \RegexIterator($recursiveDirectoryIterator, "/^((?!\\$fileExtension).)*$/i");
            /** @var \SplFileInfo $notPhpFile */
            foreach ($notPhpExtIterator as $notPhpFile) {
                if (!$notPhpFile->isFile()) {
                    continue;
                }

                $notPhpFilePath = $notPhpFile->getRealPath();
                if (!$notPhpFilePath) {
                    continue;
                }

                $content = file_get_contents($notPhpFilePath);
                if (!$content) {
                    continue;
                }

                foreach ($shebangTemplates as $shebang) {
                    if (false !== stripos($content, "#!$shebang")) {
                        $phpFilesWithoutPhpExtensions[] = $notPhpFile;
                        break;
                    }
                }
            }

            if (!empty($phpFilesWithoutPhpExtensions)) {
                /** @var \ArrayIterator<int, \SplFileInfo> $phpFilesWithoutPhpExtensionsIterator */
                $phpFilesWithoutPhpExtensionsIterator = new \ArrayIterator($phpFilesWithoutPhpExtensions);
                $filesIterator->addIterator($phpFilesWithoutPhpExtensionsIterator);
            }
        }

        return $filesIterator;
    }
}
