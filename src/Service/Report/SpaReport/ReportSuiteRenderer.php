<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\SpaReport;

use Chetkov\PHPCleanArchitecture\Service\Config\EffectiveConfigNode;

final class ReportSuiteRenderer
{
    /**
     * @return array<string, mixed>
     */
    public function render(EffectiveConfigNode $rootNode): array
    {
        $suiteData = $this->buildSuiteData($rootNode);
        $this->writeJson($rootNode->reportPath() . '/suite.json', $this->stripInlineReports($suiteData));
        $this->embedJsonData($rootNode->reportPath() . '/index.html', 'phpca-report-suite', $suiteData, 'Suite');

        return $suiteData;
    }

    /**
     * @param array<string, mixed> $historyData
     */
    public function writeHistory(EffectiveConfigNode $rootNode, array $historyData): void
    {
        $this->writeJson($rootNode->reportPath() . '/history.json', $historyData);
        $this->embedJsonData($rootNode->reportPath() . '/index.html', 'phpca-report-history', $historyData, 'History');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSuiteData(EffectiveConfigNode $node): array
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
    private function buildSuiteNode(EffectiveConfigNode $node, string $rootReportPath, bool $includeReport): array
    {
        $suiteNode = [
            'id' => $node->id(),
            'title' => $node->title(),
            'reportPath' => $this->relativeReportPath($rootReportPath, $node->reportPath()),
            'children' => array_map(function (EffectiveConfigNode $child) use ($rootReportPath): array {
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

        return $this->stringKeyedArray($data);
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
            $data['tree'] = $this->stripInlineReports($this->stringKeyedArray($data['tree']));
        }

        if (isset($data['children']) && is_array($data['children'])) {
            $children = [];
            foreach ($data['children'] as $child) {
                if (is_array($child)) {
                    $children[] = $this->stripInlineReports($this->stringKeyedArray($child));
                }
            }
            $data['children'] = $children;
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
    private function embedJsonData(string $indexPath, string $scriptId, array $data, string $dataName): void
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
            throw new \RuntimeException($dataName . ' data can not be encoded to JSON.');
        }

        $position = strpos($html, '</head>');
        if ($position === false) {
            throw new \RuntimeException('SPA report index.html can not be prepared for inline suite data.');
        }

        $script = '    <script id="' . $scriptId . '" type="application/json">' . $json . '</script>' . PHP_EOL;
        $html = substr($html, 0, $position) . $script . substr($html, $position);

        if (file_put_contents($indexPath, $html) === false) {
            throw new \RuntimeException(sprintf('File "%s" was not written', $indexPath));
        }
    }

    /**
     * @param array<mixed, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function stringKeyedArray(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
