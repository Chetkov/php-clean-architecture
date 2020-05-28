<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report;


use Chetkov\PHPCleanArchitecture\Model\Module;

/**
 * Class ReportRenderingService
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport
 */
interface ReportRenderingServiceInterface
{
    /**
     * @param string $reportPath
     * @param Module ...$modules
     */
    public function render(string $reportPath, Module ...$modules): void;
}