<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\EventManagerInterface;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\ComponentReportRenderingFinishedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\ComponentReportRenderingStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\ReportRenderingFinishedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\ReportRenderingStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\UnitOfCodeReportRenderedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingServiceInterface;
use Chetkov\PHPCleanArchitecture\Service\Report\TemplateRendererInterface;

/**
 * Class ReportRenderingService
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport
 */
class ReportRenderingService implements ReportRenderingServiceInterface
{
    /** @var EventManagerInterface */
    private $eventManager;

    /** @var IndexPageRenderingService */
    private $indexPageRenderingService;

    /** @var ComponentPageRenderingService */
    private $componentPageRenderingService;

    /** @var UnitOfCodePageRenderingService */
    private $unitOfCodePageRenderingService;

    /**
     * @param EventManagerInterface $eventManager
     * @param TemplateRendererInterface $templateRenderer
     */
    public function __construct(EventManagerInterface $eventManager, TemplateRendererInterface $templateRenderer)
    {
        $this->eventManager = $eventManager;
        $this->indexPageRenderingService = new IndexPageRenderingService($templateRenderer);
        $this->componentPageRenderingService = new ComponentPageRenderingService($templateRenderer);
        $this->unitOfCodePageRenderingService = new UnitOfCodePageRenderingService($templateRenderer);
    }

    /**
     * @inheritDoc
     */
    public function render(string $reportPath, Component ...$components): void
    {
        $this->eventManager->notify(new ReportRenderingStartedEvent());

        $totalComponents = count($components);
        foreach ($components as $componentPosition => $component) {
            if (!$component->isEnabledForAnalysis()) {
                continue;
            }

            $this->eventManager->notify(new ComponentReportRenderingStartedEvent($componentPosition, $totalComponents, $component));
            $unitOfCodePosition = 0;
            $totalUnitsOfCode = count($component->unitsOfCode());
            foreach ($component->unitsOfCode() as $unitOfCode) {
                $this->unitOfCodePageRenderingService->render($reportPath, $unitOfCode, ...$components);
                $this->eventManager->notify(new UnitOfCodeReportRenderedEvent($unitOfCodePosition++, $totalUnitsOfCode, $unitOfCode));
            }

            $this->componentPageRenderingService->render($reportPath, $component, ...$components);
            $this->eventManager->notify(new ComponentReportRenderingFinishedEvent($componentPosition, $totalComponents, $component));
        }

        $this->indexPageRenderingService->render($reportPath, ...$components);
        $this->eventManager->notify(new ReportRenderingFinishedEvent());
    }

    /**
     * @return string
     */
    public static function templatesPath(): string
    {
        return __DIR__ . '/Template';
    }
}
