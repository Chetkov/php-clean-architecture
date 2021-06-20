<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingServiceInterface;
use Chetkov\PHPCleanArchitecture\Service\Report\TemplateRendererInterface;

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

    /**
     * @param TemplateRendererInterface $templateRenderer
     */
    public function __construct(TemplateRendererInterface $templateRenderer)
    {
        $this->indexPageRenderingService = new IndexPageRenderingService($templateRenderer);
        $this->componentPageRenderingService = new ComponentPageRenderingService($templateRenderer);
        $this->unitOfCodePageRenderingService = new UnitOfCodePageRenderingService($templateRenderer);
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

    /**
     * @return string
     */
    public static function templatesPath(): string
    {
        return __DIR__ . '/Template';
    }
}
