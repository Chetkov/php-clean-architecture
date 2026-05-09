<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\SourceDiscovery;

use Chetkov\PHPCleanArchitecture\Model\Path;
use Chetkov\PHPCleanArchitecture\Model\SourceUnit;

/**
 * Interface SourceUnitDiscoveryInterface
 * @package Chetkov\PHPCleanArchitecture\Service\Analysis\SourceDiscovery
 */
interface SourceUnitDiscoveryInterface
{
    /**
     * @param \SplFileInfo $file
     * @param Path $rootPath
     * @return array<SourceUnit>
     */
    public function discover(\SplFileInfo $file, Path $rootPath): array;
}
