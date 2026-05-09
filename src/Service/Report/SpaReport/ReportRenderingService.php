<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\SpaReport;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\EventManagerInterface;
use Chetkov\PHPCleanArchitecture\Service\Report\Event\ComponentReportRenderingFinishedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\Event\ComponentReportRenderingStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\Event\ReportRenderingFinishedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\Event\ReportRenderingStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\Event\UnitOfCodeReportRenderedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingServiceInterface;

final class ReportRenderingService implements ReportRenderingServiceInterface
{
    /** @var EventManagerInterface */
    private $eventManager;

    /** @var ReportDataBuilder */
    private $reportDataBuilder;

    public function __construct(EventManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
        $this->reportDataBuilder = new ReportDataBuilder();
    }

    public function render(string $reportPath, Component ...$components): void
    {
        $this->eventManager->notify(new ReportRenderingStartedEvent());
        $this->ensureDirectoryExists($reportPath);

        $enabledComponents = array_filter($components, static function (Component $component): bool {
            return $component->isEnabledForAnalysis();
        });
        $totalComponents = count($enabledComponents);

        $componentPosition = 0;
        foreach ($enabledComponents as $component) {
            $this->eventManager->notify(new ComponentReportRenderingStartedEvent($componentPosition, $totalComponents, $component));
            $unitOfCodePosition = 0;
            $totalUnitsOfCode = count($component->unitsOfCode());
            foreach ($component->unitsOfCode() as $unitOfCode) {
                $this->eventManager->notify(new UnitOfCodeReportRenderedEvent($unitOfCodePosition++, $totalUnitsOfCode, $unitOfCode));
            }

            $this->eventManager->notify(new ComponentReportRenderingFinishedEvent($componentPosition, $totalComponents, $component));
            $componentPosition++;
        }

        $this->copyAssets($reportPath);
        $this->writeJson($reportPath . '/report.json', $this->reportDataBuilder->build(...$components));
        $this->eventManager->notify(new ReportRenderingFinishedEvent());
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
    }

    private function copyAssets(string $reportPath): void
    {
        $assetsPath = __DIR__ . '/Assets';
        if (!is_dir($assetsPath)) {
            throw new \RuntimeException('SPA report assets were not found. Run npm run report:build before packaging.');
        }

        $this->copyDirectory($assetsPath, $reportPath);
    }

    private function copyDirectory(string $sourcePath, string $destinationPath): void
    {
        $this->ensureDirectoryExists($destinationPath);
        $items = scandir($sourcePath);
        if ($items === false) {
            throw new \RuntimeException(sprintf('Directory "%s" can not be read', $sourcePath));
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $source = $sourcePath . '/' . $item;
            $destination = $destinationPath . '/' . $item;
            if (is_dir($source)) {
                $this->copyDirectory($source, $destination);
                continue;
            }

            if (!copy($source, $destination)) {
                throw new \RuntimeException(sprintf('File "%s" was not copied to "%s"', $source, $destination));
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeJson(string $path, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            throw new \RuntimeException('Report data can not be encoded to JSON.');
        }

        if (file_put_contents($path, $json . PHP_EOL) === false) {
            throw new \RuntimeException(sprintf('File "%s" was not written', $path));
        }
    }
}
