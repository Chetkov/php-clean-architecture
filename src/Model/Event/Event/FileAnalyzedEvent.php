<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Event\Event;

use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;

class FileAnalyzedEvent implements EventInterface
{
    use ProgressiveTrait;

    private const STATUS_OK = 'OK';
    private const STATUS_SKIPPED = 'SKIPPED';

    /** @var string */
    private $status = self::STATUS_OK;

    /** @var string */
    private $fullPath;

    /**
     * @param int $position
     * @param int $totalPositions
     * @param string $fullPath
     */
    public function __construct(
        int $position,
        int $totalPositions,
        string $fullPath
    ) {
        $this->position = $position;
        $this->totalPositions = $totalPositions;
        $this->fullPath = $fullPath;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function toSkipped(): void
    {
        $this->status = self::STATUS_SKIPPED;
    }

    /**
     * @return string
     */
    public function getFullPath(): string
    {
        return $this->fullPath;
    }
}
