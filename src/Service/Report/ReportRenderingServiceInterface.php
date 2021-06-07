<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report;


use Chetkov\PHPCleanArchitecture\Model\Component;

/**
 * Interface ReportRenderingServiceInterface
 * @package Chetkov\PHPCleanArchitecture\Service\Report
 */
interface ReportRenderingServiceInterface
{
    /**
     * @param string $reportPath
     * @param Component ...$components
     */
    public function render(string $reportPath, Component ...$components): void;
}