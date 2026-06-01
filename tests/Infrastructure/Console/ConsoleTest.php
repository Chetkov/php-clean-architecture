<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Infrastructure\Console;

use Chetkov\PHPCleanArchitecture\Infrastructure\Console\Console;
use PHPUnit\Framework\TestCase;

final class ConsoleTest extends TestCase
{
    /** @var string|false */
    private $previousColumns;

    /** @var string|false */
    private $previousPath;

    protected function setUp(): void
    {
        $this->previousColumns = getenv('COLUMNS');
        $this->previousPath = getenv('PATH');
    }

    protected function tearDown(): void
    {
        $this->restoreEnv('COLUMNS', $this->previousColumns);
        $this->restoreEnv('PATH', $this->previousPath);
    }

    public function testUsesColumnsEnvironmentVariableWhenAvailable(): void
    {
        putenv('COLUMNS=96');

        self::assertSame(96, Console::getTerminalWidth());
    }

    public function testFallsBackWithoutTput(): void
    {
        putenv('COLUMNS');
        putenv('PATH=/path-without-tput');

        self::assertSame(120, Console::getTerminalWidth());
    }

    /**
     * @param string|false $value
     */
    private function restoreEnv(string $name, $value): void
    {
        if ($value === false) {
            putenv($name);
            return;
        }

        putenv($name . '=' . $value);
    }
}
