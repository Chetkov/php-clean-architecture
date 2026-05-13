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
        self::assertStringContainsString(
            '<script id="phpca-report-suite" type="application/json">',
            $indexHtml
        );

        $suite = json_decode((string) file_get_contents($reportRoot . '/suite.json'), true);
        self::assertIsArray($suite);
        self::assertSame('root', $suite['rootId']);
        self::assertSame('component-a', $suite['tree']['children'][0]['id']);
        self::assertSame('component-a', $suite['tree']['children'][0]['title']);
        self::assertArrayNotHasKey('report', $suite['tree']);

        $childReport = json_decode((string) file_get_contents($reportRoot . '/component-a/report.json'), true);
        self::assertIsArray($childReport);
        self::assertSame(['Слой A!', 'Layer B'], array_column($childReport['components'], 'name'));

        $this->removeDirectory($reportRoot);
    }

    /**
     * @return array{exitCode: int, output: string}
     */
    private function runBin(string $name, string $configPath): array
    {
        $command = implode(' ', [
            escapeshellarg(PHP_BINARY),
            escapeshellarg(dirname(__DIR__) . '/bin/' . $name),
            escapeshellarg($configPath),
        ]);

        return $this->runCommand($command, dirname(__DIR__));
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
