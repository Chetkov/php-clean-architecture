<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report;

use Chetkov\PHPCleanArchitecture\Model\Module;

/**
 * Class ReportRenderingService
 * @package Chetkov\PHPCleanArchitecture\Service\Report
 */
class ReportRenderingService
{
    /** @var IndexPageRenderingService */
    private $indexPageRenderingService;

    /** @var ModulePageRenderingService */
    private $modulePageRenderingService;

    /** @var UnitOfCodePageRenderingService */
    private $unitOfCodePageRenderingService;

    public function __construct()
    {
        $this->indexPageRenderingService = new IndexPageRenderingService();
        $this->modulePageRenderingService = new ModulePageRenderingService();
        $this->unitOfCodePageRenderingService = new UnitOfCodePageRenderingService();
    }

    /**
     * @param string $reportPath
     * @param Module ...$modules
     */
    public function render(string $reportPath, Module ...$modules): void
    {
        $this->indexPageRenderingService->render($reportPath, ...$modules);
        foreach ($modules as $module) {
            $this->modulePageRenderingService->render($reportPath, $module, ...$modules);
            foreach ($module->unitsOfCode() as $unitOfCode) {
                $this->unitOfCodePageRenderingService->render($reportPath, $unitOfCode, ...$modules);
            }
        }
    }
}
