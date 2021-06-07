<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingServiceInterface;

/**
 * Class ReportRenderingService
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport
 */
class ReportRenderingService implements ReportRenderingServiceInterface
{
    /** @var IndexPageRenderingService */
    private $indexPageRenderingService;

    /** @var ComponentPageRenderingService */
    private $componentPageRenderingService;

    /** @var UnitOfCodePageRenderingService */
    private $unitOfCodePageRenderingService;

    public function __construct()
    {
        $this->indexPageRenderingService = new IndexPageRenderingService();
        $this->componentPageRenderingService = new ComponentPageRenderingService();
        $this->unitOfCodePageRenderingService = new UnitOfCodePageRenderingService();
    }

    /**
     * @inheritDoc
     */
    public function render(string $reportPath, Component ...$components): void
    {
        $this->indexPageRenderingService->render($reportPath, ...$components);
        foreach ($components as $component) {
            if (!$component->isEnabledForAnalysis()) {
                continue;
            }

            $this->componentPageRenderingService->render($reportPath, $component, ...$components);
            foreach ($component->unitsOfCode() as $unitOfCode) {
                $this->unitOfCodePageRenderingService->render($reportPath, $unitOfCode, ...$components);
            }
        }
    }
}
