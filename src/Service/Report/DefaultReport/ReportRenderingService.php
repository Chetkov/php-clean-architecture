<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport;

use Chetkov\PHPCleanArchitecture\Model\Module;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingServiceInterface;

/**
 * Class ReportRenderingService
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport
 */
class ReportRenderingService implements ReportRenderingServiceInterface
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
     * @inheritDoc
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
