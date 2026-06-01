<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Infrastructure\Console;

class Console
{
    private const DEFAULT_TERMINAL_WIDTH = 120;

    /**
     * @return int
     */
    public static function getTerminalWidth(): int
    {
        $columns = getenv('COLUMNS');
        if (is_string($columns) && ctype_digit($columns) && (int) $columns > 0) {
            return (int) $columns;
        }

        $width = @shell_exec('command -v tput >/dev/null 2>&1 && tput cols 2>/dev/null');
        if (is_string($width) && ctype_digit(trim($width)) && (int) trim($width) > 0) {
            return (int) trim($width);
        }

        return self::DEFAULT_TERMINAL_WIDTH;
    }

    /**
     * @param string $message
     * @param bool $rewrite
     */
    public static function write(string $message = '', bool $rewrite = false): void
    {
        echo $rewrite ? str_pad($message, self::getTerminalWidth()) : $message;
    }

    /**
     * @param string $message
     */
    public static function writeln(string $message = ''): void
    {
        self::write(PHP_EOL . $message);
    }
}
