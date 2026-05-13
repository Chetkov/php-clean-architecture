<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\SpaReport;

use Chetkov\PHPCleanArchitecture\Service\Config\ConfigTreeNode;

final class ReportSuiteRenderer
{
    public function render(ConfigTreeNode $rootNode): void
    {
        $suiteData = $this->buildSuiteData($rootNode);
        $this->writeJson($rootNode->reportPath() . '/suite.json', $this->stripInlineReports($suiteData));
        $this->embedSuiteData($rootNode->reportPath() . '/index.html', $suiteData);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSuiteData(ConfigTreeNode $node): array
    {
        return [
            'schemaVersion' => 1,
            'rootId' => 'root',
            'tree' => $this->buildSuiteNode($node, $node->reportPath(), false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSuiteNode(ConfigTreeNode $node, string $rootReportPath, bool $includeReport): array
    {
        $suiteNode = [
            'id' => $node->id(),
            'title' => $node->title(),
            'reportPath' => $this->relativeReportPath($rootReportPath, $node->reportPath()),
            'children' => array_map(function (ConfigTreeNode $child) use ($rootReportPath): array {
                return $this->buildSuiteNode($child, $rootReportPath, true);
            }, $node->children()),
        ];

        if ($includeReport) {
            $suiteNode['report'] = $this->readReportData($node->reportPath());
        }

        return $suiteNode;
    }

    /**
     * @return array<string, mixed>
     */
    private function readReportData(string $reportPath): array
    {
        $path = $reportPath . '/report.json';
        $json = file_get_contents($path);
        if (!is_string($json)) {
            throw new \RuntimeException(sprintf('Report data "%s" can not be read', $path));
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException(sprintf('Report data "%s" can not be decoded', $path));
        }

        return $data;
    }

    private function relativeReportPath(string $rootReportPath, string $reportPath): string
    {
        $rootReportPath = rtrim($rootReportPath, '/');
        $reportPath = rtrim($reportPath, '/');

        if ($reportPath === $rootReportPath) {
            return '.';
        }

        $prefix = $rootReportPath . '/';
        if (strpos($reportPath, $prefix) === 0) {
            return substr($reportPath, strlen($prefix));
        }

        return basename($reportPath);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function stripInlineReports(array $data): array
    {
        if (isset($data['report'])) {
            unset($data['report']);
        }

        if (isset($data['tree']) && is_array($data['tree'])) {
            $data['tree'] = $this->stripInlineReports($data['tree']);
        }

        if (isset($data['children']) && is_array($data['children'])) {
            $data['children'] = array_map(function (array $child): array {
                return $this->stripInlineReports($child);
            }, $data['children']);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeJson(string $path, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            throw new \RuntimeException('Suite data can not be encoded to JSON.');
        }

        if (file_put_contents($path, $json . PHP_EOL) === false) {
            throw new \RuntimeException(sprintf('File "%s" was not written', $path));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function embedSuiteData(string $indexPath, array $data): void
    {
        $html = file_get_contents($indexPath);
        if (!is_string($html)) {
            throw new \RuntimeException(sprintf('File "%s" can not be read', $indexPath));
        }

        $json = json_encode(
            $data,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );
        if (!is_string($json)) {
            throw new \RuntimeException('Suite data can not be encoded to JSON.');
        }

        $position = strpos($html, '</head>');
        if ($position === false) {
            throw new \RuntimeException('SPA report index.html can not be prepared for inline suite data.');
        }

        $script = '    <script id="phpca-report-suite" type="application/json">' . $json . '</script>' . PHP_EOL;
        $html = substr($html, 0, $position) . $script . substr($html, $position);

        if (file_put_contents($indexPath, $html) === false) {
            throw new \RuntimeException(sprintf('File "%s" was not written', $indexPath));
        }
    }
}
