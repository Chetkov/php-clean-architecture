<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Infrastructure\Console;

class Console
{
    /**
     * @return int
     */
    public static function getTerminalWidth(): int
    {
        return (int) shell_exec("tput cols");
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
