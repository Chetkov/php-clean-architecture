<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Helper\Console;

class ProgressBar
{
    /** 3 is length of max percentage value (100) */
    private const MAX_PERCENTAGE_LENGTH = 3;

    private const FILLED_BARS_LABEL = '{filled_bars}';
    private const EMPTY_BARS_LABEL = '{empty_bars}';
    private const PERCENTAGE_LABEL = '{percentage}';
    private const TEXT_LABEL = '{text}';
    private const FORMAT = '[' . self::FILLED_BARS_LABEL . self::EMPTY_BARS_LABEL . self::PERCENTAGE_LABEL . '%] ' . self::TEXT_LABEL;

    /** @var int */
    private $percentageWidth;

    /** @var int */
    private $percentageTextWidth;

    /** @var int */
    private $width;

    /** @var int */
    private $textWidth;

    /** @var int */
    private $barWidth;

    /** @var self[] */
    private static $instances = [];

    /**
     * @param int $percentageTextWidth Какой процент от ширины прогресс-бара должен занять текст?
     * @param int $percentageWidth Какой процент доступного места должен занять прогресс-бар?
     */
    public function __construct(
        int $percentageTextWidth = 75,
        int $percentageWidth = 100
    ) {
        $this->percentageTextWidth = $percentageTextWidth;
        $this->percentageWidth = $percentageWidth;

        $this->calculateWidths((int) shell_exec("tput cols"));
    }

    /**
     * @param int $percentageTextWidth Какой процент от ширины прогресс-бара должен занять текст?
     * @param int $percentageWidth Какой процент доступного места должен занять прогресс-бар?
     * @return self
     */
    public static function getInstance(
        int $percentageTextWidth = 75,
        int $percentageWidth = 100
    ): self {
        $paramsHash = implode('|', [$percentageTextWidth, $percentageWidth]);
        if (!isset(self::$instances[$paramsHash])) {
            self::$instances[$paramsHash] = new self($percentageTextWidth, $percentageWidth);
        }
        return self::$instances[$paramsHash];
    }

    /**
     * @param int $fullWidth
     */
    private function calculateWidths(int $fullWidth): void
    {
        $absoluteWidth = (int) ($fullWidth / 100 * $this->percentageWidth);
        $availableWidth = $absoluteWidth - strlen(str_replace([
                self::FILLED_BARS_LABEL,
                self::EMPTY_BARS_LABEL,
                self::PERCENTAGE_LABEL,
                self::TEXT_LABEL
            ], [''], self::FORMAT)) - self::MAX_PERCENTAGE_LENGTH;

        $this->width = $absoluteWidth;
        $this->textWidth = (int) ($absoluteWidth / 100 * $this->percentageTextWidth);
        $this->barWidth = $availableWidth - $this->textWidth;
    }

    /**
     * @param int $percentage
     * @param string $text
     */
    public function render(int $percentage, string $text): void
    {
        echo $this->getOutput($percentage, $text) . "\r";
    }

    /**
     * @param int $percentage
     * @param string $text
     * @return string
     */
    public function getOutput(int $percentage, string $text = ''): string
    {
        $text = $this->shortenText($text);

        $numFilledBars = (int) ($percentage / 100 * $this->barWidth);
        if ($numFilledBars < 0) {
            $numFilledBars = 0;
        }

        $numEmptyBars = $this->barWidth - $numFilledBars;
        if ($numEmptyBars < 0) {
            $numEmptyBars = 0;
        }

        return str_pad(str_replace([self::FILLED_BARS_LABEL, self::EMPTY_BARS_LABEL, self::PERCENTAGE_LABEL, self::TEXT_LABEL], [
            str_repeat('|', $numFilledBars),
            str_repeat(' ', $numEmptyBars),
            str_pad((string) $percentage, 3, ' ', STR_PAD_LEFT),
            $text
        ],self::FORMAT), $this->width);
    }

    /**
     * @param string $text
     * @return string
     */
    private function shortenText(string $text): string
    {
        $label = '...';
        $labelLength = 3;
        if ($this->textWidth < $labelLength) {
            return '';
        }

        $textLength = strlen($text);
        if ($textLength > $this->textWidth) {
            $partLength = (int) (($this->textWidth - $labelLength) / 2);
            $firstPart = substr($text, 0, $partLength);
            $lastPart = substr($text, -$partLength, $partLength);
            $text = $firstPart . $label . $lastPart;
        }

        return $text;
    }
}
