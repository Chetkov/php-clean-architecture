<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report;

use Chetkov\PHPCleanArchitecture\Model\ComponentInterface;

/**
 * Interface ReportRenderingServiceInterface
 * @package Chetkov\PHPCleanArchitecture\Service\Report
 */
interface ReportRenderingServiceInterface
{
    /**
     * @param string $reportPath
     * @param ComponentInterface ...$components
     */
    public function render(string $reportPath, ComponentInterface ...$components): void;
}
