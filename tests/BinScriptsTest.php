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
}
