<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Report;

use Chetkov\PHPCleanArchitecture\Infrastructure\Console\Console;
use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Service\EventListenerInterface;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\ReportRenderingFinishedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\ReportRenderingStartedEvent;

class ReportRenderingEventListener implements EventListenerInterface
{
    /** @var float|null */
    private $startedAt;

    public function handle(EventInterface $event): void
    {
        switch (true) {
            case $event instanceof ReportRenderingStartedEvent:
                $this->handleStart($event);
                break;
            case $event instanceof ReportRenderingFinishedEvent:
                $this->handleFinish($event);
                break;
            default:
        }
    }

    /**
     * @param ReportRenderingStartedEvent $event
     */
    private function handleStart(ReportRenderingStartedEvent $event): void
    {
        if (!$this->startedAt) {
            $this->startedAt = $event->getMicroTime();
        }

        Console::write('Report rendering started.');
        Console::writeln();
    }

    /**
     * @param ReportRenderingFinishedEvent $event
     */
    private function handleFinish(ReportRenderingFinishedEvent $event): void
    {
        $startedAt = $this->startedAt ?? microtime(true);
        $this->startedAt = null;

        $executionTime = round($event->getMicroTime() - $startedAt, 3);
        Console::write(sprintf('Report rendering finished. Execution time: %s sec.', $executionTime), true);
        Console::writeln();
    }
}
