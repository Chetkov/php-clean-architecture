<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report;


use Chetkov\PHPCleanArchitecture\Model\Module;

/**
 * Interface ReportRenderingServiceInterface
 * @package Chetkov\PHPCleanArchitecture\Service\Report
 */
interface ReportRenderingServiceInterface
{
    /**
     * @param string $reportPath
     * @param Module ...$modules
     */
    public function render(string $reportPath, Module ...$modules): void;
}