<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Helper\Console;

class Console
{
    public static function progress(float $percentage, string $text): void
    {
        $percentagePart = str_pad(number_format($percentage) . '%', 4, ' ', STR_PAD_LEFT);

        $terminalWidth = (int) `tput cols`;
        $barWidth = (int) ($terminalWidth / 4) - 3;
        $textWidth = $terminalWidth - $barWidth - strlen($percentagePart) - 4;

        $textLength = strlen($text);
        if ($textLength > $textWidth) {
            $partLength = (int) (($textWidth - 3) / 2);
            $firstPart = substr($text, 0, $partLength);
            $lastPart = substr($text, -$partLength, $partLength);
            $text = $firstPart . '...' . $lastPart;
        }

        $numBars = (int) round($percentage / 100 * $barWidth);
        $numEmptyBars = $barWidth - $numBars;

        $barsPart = sprintf(
            "[%s%s] %s",
            str_repeat('|', $numBars),
            str_repeat(' ', $numEmptyBars),
            $percentagePart
        );

        echo str_pad("$barsPart $text", $terminalWidth) . "\r";
    }

    /**
     * @param string $message
     */
    public static function write(string $message = ''): void
    {
        echo $message;
    }

    /**
     * @param string $message
     */
    public static function writeln(string $message = ''): void
    {
        self::write(PHP_EOL . $message);
    }
}
