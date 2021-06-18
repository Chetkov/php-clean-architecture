<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Event\Listener;

use Chetkov\PHPCleanArchitecture\Helper\Console\Console;
use Chetkov\PHPCleanArchitecture\Model\Event\Event\AnalysisFinishedEvent;
use Chetkov\PHPCleanArchitecture\Model\Event\Event\AnalysisStartedEvent;
use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Model\Event\EventListenerInterface;

class AnalysisEventListener implements EventListenerInterface
{
    /** @var float|null */
    private $startedAt;

    public function handle(EventInterface $event): void
    {
        switch (true) {
            case $event instanceof AnalysisStartedEvent:
                $this->handleStart($event);
                break;
            case $event instanceof AnalysisFinishedEvent:
                $this->handleFinish($event);
                break;
            default:
        }
    }

    /**
     * @param AnalysisStartedEvent $event
     */
    private function handleStart(AnalysisStartedEvent $event): void
    {
        if (!$this->startedAt) {
            $this->startedAt = $event->getMicroTime();
        }

        Console::write('Analysis started.');
        Console::writeln();
    }

    /**
     * @param AnalysisFinishedEvent $event
     */
    private function handleFinish(AnalysisFinishedEvent $event): void
    {
        $startedAt = $this->startedAt ?? microtime(true);
        $this->startedAt = null;

        $executionTime = round($event->getMicroTime() - $startedAt, 3);
        Console::write(sprintf('Analysis finished. Execution time: %s sec.', $executionTime), true);
        Console::writeln();
    }
}
