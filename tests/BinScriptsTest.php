<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests;

use PHPUnit\Framework\TestCase;

final class BinScriptsTest extends TestCase
{
    public function testPhpcaCheckReturnsSuccessWhenThereAreNoErrors(): void
    {
        $result = $this->runBin('phpca-check', __DIR__ . '/Fixtures/Project/clean-config.php');

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString('No errors!', $result['output']);
    }

    public function testPhpcaCheckReturnsFailureAndPrintsErrors(): void
    {
        $result = $this->runBin('phpca-check', __DIR__ . '/Fixtures/Project/forbidden-dependency-config.php');

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString(
            '"component-a" can not depend on "component-b"!',
            $result['output']
        );
    }

    public function testPhpcaCheckRunsNestedSubConfigs(): void
    {
        $result = $this->runBin('phpca-check', __DIR__ . '/Fixtures/Project/nested-forbidden-config.php');

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('[component-a]', $result['output']);
        self::assertStringContainsString(
            '"component-a-layer" can not depend on "component-b-layer"!',
            $result['output']
        );
    }

    public function testPhpcaAllowCurrentStateSavesNestedSuiteStateUsedByCheck(): void
    {
        $workspace = sys_get_temp_dir() . '/phpca-allowed-state-suite-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($workspace, 0777, true));

        $configPath = $workspace . '/phpca-config.php';
        $rootStatePath = $workspace . '/phpca-allowed-state.php';
        self::assertNotFalse(file_put_contents(
            $configPath,
            '<?php' . PHP_EOL .
            '$config = require ' . var_export(__DIR__ . '/Fixtures/Project/nested-forbidden-config.php', true) . ';' . PHP_EOL .
            '$config["exclusions"]["allowed_state"] = [' . PHP_EOL .
            '    "enabled" => true,' . PHP_EOL .
            '    "storage" => ' . var_export($rootStatePath, true) . ',' . PHP_EOL .
            '];' . PHP_EOL .
            'return $config;' . PHP_EOL
        ));

        $saveResult = $this->runBin('phpca-allow-current-state', $configPath);

        self::assertSame(0, $saveResult['exitCode']);
        self::assertStringContainsString('root: ' . $rootStatePath, $saveResult['output']);
        self::assertStringContainsString('component-a: ' . $workspace . '/phpca-allowed-state/component-a.php', $saveResult['output']);
        self::assertFileExists($rootStatePath);
        self::assertFileExists($workspace . '/phpca-allowed-state/component-a.php');

        $checkResult = $this->runBin('phpca-check', $configPath);

        self::assertSame(0, $checkResult['exitCode']);
        self::assertStringContainsString('No errors!', $checkResult['output']);

        $this->removeDirectory($workspace);
    }

    public function testPhpcaCheckIsolatesNestedConfigsFromRootAnalysisState(): void
    {
        $result = $this->runBin('phpca-check', __DIR__ . '/Fixtures/Project/nested-isolation-config.php');

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString('No errors!', $result['output']);
    }

    public function testPhpcaCheckResolvesAutoloadAndConfigFromConsumerVendorPackage(): void
    {
        $consumerRoot = $this->createConsumerProjectFixture();
        $binPath = $consumerRoot . '/vendor/v.chetkov/php-clean-architecture/bin/phpca-check';
        $result = $this->runCommand(
            implode(' ', [
                escapeshellarg(PHP_BINARY),
                escapeshellarg($binPath),
            ]),
            $consumerRoot
        );

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString('No errors!', $result['output']);
    }

    public function testPhpcaDebugUnmatchedFilesReturnsSuccessWhenEveryFileIsCovered(): void
    {
        $result = $this->runBin('phpca-debug-unmatched-files', __DIR__ . '/Fixtures/Project/clean-config.php', [
            __DIR__ . '/Fixtures/Project/ComponentA',
            __DIR__ . '/Fixtures/Project/ComponentB',
        ]);

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString('No unmatched files!', $result['output']);
    }

    public function testPhpcaDebugUnmatchedFilesPrintsFilesOutsideConfiguredComponents(): void
    {
        $result = $this->runBin('phpca-debug-unmatched-files', __DIR__ . '/Fixtures/Project/unmatched-files-config.php', [
            __DIR__ . '/Fixtures/Project/Unmatched',
        ]);

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('Unmatched files found:', $result['output']);
        self::assertStringContainsString('[root] 1 file(s)', $result['output']);
        self::assertStringContainsString(__DIR__ . '/Fixtures/Project/Unmatched/LooseClass.php', $result['output']);
        self::assertStringNotContainsString(__DIR__ . '/Fixtures/Project/Unmatched/ExcludedLooseClass.php', $result['output']);
    }

    public function testPhpcaDebugUnmatchedFilesRunsNestedSubConfigs(): void
    {
        $result = $this->runBin('phpca-debug-unmatched-files', __DIR__ . '/Fixtures/Project/nested-unmatched-config.php');

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('[component-a] 1 file(s)', $result['output']);
        self::assertStringContainsString(__DIR__ . '/Fixtures/Project/NestedUnmatched/ComponentA/UnmatchedLayer.php', $result['output']);
    }

    public function testPhpcaBuildReportsCreatesSpaReport(): void
    {
        $reportRoot = sys_get_temp_dir() . '/phpca-bin-report-root-' . bin2hex(random_bytes(8));
        $reportPath = $reportRoot . '/layers/AgentWorkspace';
        self::assertTrue(mkdir($reportRoot, 0777, true));

        $result = $this->runCommand(
            implode(' ', [
                'PHPCA_REPORTS_DIR=' . escapeshellarg($reportPath),
                escapeshellarg(PHP_BINARY),
                escapeshellarg(dirname(__DIR__) . '/bin/phpca-build-reports'),
                escapeshellarg(__DIR__ . '/Fixtures/Project/report-config.php'),
            ]),
            dirname(__DIR__)
        );

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString('Report: ' . $reportPath . '/index.html', $result['output']);
        self::assertFileExists($reportPath . '/index.html');
        self::assertFileExists($reportPath . '/report.json');
        self::assertStringContainsString(
            '<script id="phpca-report-data" type="application/json">',
            (string) file_get_contents($reportPath . '/index.html')
        );
        $this->assertReportHtmlIsSelfContained($reportPath . '/index.html');

        $this->removeDirectory($reportRoot);
    }

    public function testPhpcaBuildReportsCreatesNestedReportSuite(): void
    {
        $reportRoot = sys_get_temp_dir() . '/phpca-bin-report-root-' . bin2hex(random_bytes(8));
        $standaloneChildReport = sys_get_temp_dir() . '/phpca-ignored-child-report';

        $result = $this->runCommand(
            implode(' ', [
                'PHPCA_REPORTS_DIR=' . escapeshellarg($reportRoot),
                escapeshellarg(PHP_BINARY),
                escapeshellarg(dirname(__DIR__) . '/bin/phpca-build-reports'),
                escapeshellarg(__DIR__ . '/Fixtures/Project/nested-report-config.php'),
            ]),
            dirname(__DIR__)
        );

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString('Report: ' . $reportRoot . '/index.html', $result['output']);
        self::assertFileExists($reportRoot . '/index.html');
        self::assertFileExists($reportRoot . '/report.json');
        self::assertFileExists($reportRoot . '/component-a/index.html');
        self::assertFileExists($reportRoot . '/component-a/report.json');
        self::assertFileExists($reportRoot . '/suite.json');
        self::assertDirectoryDoesNotExist($standaloneChildReport);

        $indexHtml = (string) file_get_contents($reportRoot . '/index.html');
        $this->assertReportHtmlIsSelfContained($reportRoot . '/index.html');
        $this->assertReportHtmlIsSelfContained($reportRoot . '/component-a/index.html');
        self::assertStringContainsString(
            '<script id="phpca-report-suite" type="application/json">',
            $indexHtml
        );
        $hasInlineSuite = preg_match(
            '#<script id="phpca-report-suite" type="application/json">(.+)</script>#',
            $indexHtml,
            $suiteScriptMatches
        );
        self::assertSame(1, $hasInlineSuite);
        $inlineSuite = json_decode($suiteScriptMatches[1], true);
        self::assertIsArray($inlineSuite);
        self::assertSame('.', $inlineSuite['tree']['reportPath']);
        self::assertArrayNotHasKey('report', $inlineSuite['tree']);
        self::assertSame('component-a', $inlineSuite['tree']['children'][0]['reportPath']);
        self::assertArrayHasKey('report', $inlineSuite['tree']['children'][0]);
        self::assertStringNotContainsString($reportRoot, $suiteScriptMatches[1]);

        $suite = json_decode((string) file_get_contents($reportRoot . '/suite.json'), true);
        self::assertIsArray($suite);
        self::assertSame('root', $suite['rootId']);
        self::assertSame('component-a', $suite['tree']['children'][0]['id']);
        self::assertSame('component-a', $suite['tree']['children'][0]['title']);
        self::assertSame('.', $suite['tree']['reportPath']);
        self::assertSame('component-a', $suite['tree']['children'][0]['reportPath']);
        self::assertArrayNotHasKey('report', $suite['tree']);
        self::assertArrayNotHasKey('report', $suite['tree']['children'][0]);

        $childReport = json_decode((string) file_get_contents($reportRoot . '/component-a/report.json'), true);
        self::assertIsArray($childReport);
        self::assertSame(['Слой A!', 'Layer B'], array_column($childReport['components'], 'name'));

        $this->removeDirectory($reportRoot);
    }

    public function testPhpcaBuildReportsShowsLegacyRateForRootAndNestedReports(): void
    {
        $reportRoot = sys_get_temp_dir() . '/phpca-legacy-rate-report-' . bin2hex(random_bytes(8));

        $result = $this->runCommand(
            implode(' ', [
                'PHPCA_REPORTS_DIR=' . escapeshellarg($reportRoot),
                escapeshellarg(PHP_BINARY),
                escapeshellarg(dirname(__DIR__) . '/bin/phpca-build-reports'),
                escapeshellarg(__DIR__ . '/Fixtures/LegacyRateProject/config.php'),
            ]),
            dirname(__DIR__)
        );

        self::assertSame(0, $result['exitCode']);
        self::assertFileExists($reportRoot . '/report.json');
        self::assertFileExists($reportRoot . '/feature/report.json');

        $rootReport = json_decode((string) file_get_contents($reportRoot . '/report.json'), true);
        self::assertIsArray($rootReport);
        $modernLines = $this->fixtureLineCount('/Fixtures/LegacyRateProject/Modern/ModernService.php');
        $legacyLines = $this->fixtureLineCount('/Fixtures/LegacyRateProject/Legacy/LegacyService.php')
            + $this->fixtureLineCount('/Fixtures/LegacyRateProject/Legacy/LegacyHelper.php');
        $totalLines = $modernLines + $legacyLines;

        self::assertSame($totalLines, $rootReport['summary']['legacy']['linesOfCode']);
        self::assertSame($legacyLines, $rootReport['summary']['legacy']['legacyLinesOfCode']);
        self::assertSame($modernLines, $rootReport['summary']['legacy']['modernLinesOfCode']);
        self::assertSame(3, $rootReport['summary']['legacy']['units']);
        self::assertSame(2, $rootReport['summary']['legacy']['legacyUnits']);
        self::assertSame(1, $rootReport['summary']['legacy']['modernUnits']);
        self::assertSame($legacyLines / $totalLines, $rootReport['summary']['legacy']['legacyRate']);
        self::assertSame($modernLines / $totalLines, $rootReport['summary']['legacy']['modernRate']);

        $legacyUnit = $this->findReportUnit($rootReport, 'LegacyService');
        $modernUnit = $this->findReportUnit($rootReport, 'ModernService');
        self::assertTrue($legacyUnit['isLegacy']);
        self::assertFalse($modernUnit['isLegacy']);
        self::assertSame(
            $this->fixtureLineCount('/Fixtures/LegacyRateProject/Legacy/LegacyService.php'),
            $legacyUnit['linesOfCode']
        );

        $featureComponent = $this->findReportComponent($rootReport, 'Feature');
        self::assertSame($legacyLines, $featureComponent['legacy']['legacyLinesOfCode']);
        self::assertSame($modernLines, $featureComponent['legacy']['modernLinesOfCode']);
        self::assertSame(2, $featureComponent['legacy']['legacyUnits']);
        self::assertSame(1, $featureComponent['legacy']['modernUnits']);

        $nestedReport = json_decode((string) file_get_contents($reportRoot . '/feature/report.json'), true);
        self::assertIsArray($nestedReport);
        self::assertSame($totalLines, $nestedReport['summary']['legacy']['linesOfCode']);
        self::assertSame($legacyLines, $nestedReport['summary']['legacy']['legacyLinesOfCode']);
        self::assertSame($modernLines, $nestedReport['summary']['legacy']['modernLinesOfCode']);
        self::assertSame(3, $nestedReport['summary']['legacy']['units']);
        self::assertSame(2, $nestedReport['summary']['legacy']['legacyUnits']);
        self::assertSame(1, $nestedReport['summary']['legacy']['modernUnits']);
        self::assertSame(
            0,
            $this->findReportComponent($nestedReport, 'Modern Layer')['legacy']['legacyLinesOfCode']
        );
        self::assertSame(
            $legacyLines,
            $this->findReportComponent($nestedReport, 'Legacy Layer')['legacy']['legacyLinesOfCode']
        );

        $indexHtml = (string) file_get_contents($reportRoot . '/index.html');
        self::assertStringContainsString('legacyRate', $indexHtml);
        self::assertStringContainsString('modernRate', $indexHtml);

        $this->removeDirectory($reportRoot);
    }

    private function assertReportHtmlIsSelfContained(string $indexPath): void
    {
        $html = (string) file_get_contents($indexPath);

        self::assertStringContainsString('<style data-phpca-report-asset="./assets/', $html);
        self::assertStringContainsString('<script data-phpca-report-asset="./assets/', $html);
        self::assertStringNotContainsString('type="module"', $html);
        self::assertStringNotContainsString('src="./assets/', $html);
        self::assertStringNotContainsString('href="./assets/', $html);
        self::assertGreaterThan(
            strpos($html, '<div id="root"></div>'),
            strpos($html, '<script data-phpca-report-asset="./assets/')
        );
    }

    /**
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function findReportUnit(array $report, string $shortName): array
    {
        foreach ($report['units'] as $unit) {
            if ($unit['shortName'] === $shortName) {
                return $unit;
            }
        }

        self::fail('Unit ' . $shortName . ' was not found.');
    }

    /**
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function findReportComponent(array $report, string $name): array
    {
        foreach ($report['components'] as $component) {
            if ($component['name'] === $name) {
                return $component;
            }
        }

        self::fail('Component ' . $name . ' was not found.');
    }

    private function fixtureLineCount(string $path): int
    {
        $lines = file(__DIR__ . $path);

        return is_array($lines) ? count($lines) : 0;
    }

    /**
     * @return array{exitCode: int, output: string}
     */
    /**
     * @param array<string> $arguments
     *
     * @return array{exitCode: int, output: string}
     */
    private function runBin(string $name, string $configPath, array $arguments = []): array
    {
        $commandParts = [
            escapeshellarg(PHP_BINARY),
            escapeshellarg(dirname(__DIR__) . '/bin/' . $name),
            escapeshellarg($configPath),
        ];
        foreach ($arguments as $argument) {
            $commandParts[] = escapeshellarg($argument);
        }

        return $this->runCommand(implode(' ', $commandParts), dirname(__DIR__));
    }

    /**
     * @return array{exitCode: int, output: string}
     */
    private function runCommand(string $command, string $cwd): array
    {
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $cwd);
        self::assertIsResource($process);

        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'output' => $output,
        ];
    }

    private function createConsumerProjectFixture(): string
    {
        $consumerRoot = sys_get_temp_dir() . '/phpca-consumer-' . bin2hex(random_bytes(8));
        $packageBinRoot = $consumerRoot . '/vendor/v.chetkov/php-clean-architecture/bin';

        self::assertTrue(mkdir($packageBinRoot, 0777, true));
        self::assertTrue(copy(dirname(__DIR__) . '/bin/phpca-check', $packageBinRoot . '/phpca-check'));

        $autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
        $configPath = __DIR__ . '/Fixtures/Project/clean-config.php';

        self::assertNotFalse(file_put_contents(
            $consumerRoot . '/vendor/autoload.php',
            '<?php require ' . var_export($autoloadPath, true) . ';' . PHP_EOL
        ));
        self::assertNotFalse(file_put_contents(
            $consumerRoot . '/phpca-config.php',
            '<?php return require ' . var_export($configPath, true) . ';' . PHP_EOL
        ));

        return $consumerRoot;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = glob($path . '/*');
        self::assertIsArray($files);
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }
}
