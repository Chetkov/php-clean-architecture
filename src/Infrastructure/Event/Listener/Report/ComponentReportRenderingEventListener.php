<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Report;

use Chetkov\PHPCleanArchitecture\Infrastructure\Console\Console;
use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Service\EventListenerInterface;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\ComponentReportRenderingEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\ComponentReportRenderingFinishedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\ComponentReportRenderingStartedEvent;

class ComponentReportRenderingEventListener implements EventListenerInterface
{
    /** @var array<float> */
    private $startedAt = [];

    /**
     * @param EventInterface $event
     */
    public function handle(EventInterface $event): void
    {
        if (!$event instanceof ComponentReportRenderingEvent) {
            return;
        }

        switch (true) {
            case $event instanceof ComponentReportRenderingStartedEvent:
                $this->handleStart($event);
                break;
            case $event instanceof ComponentReportRenderingFinishedEvent:
                $this->handleFinish($event);
                break;
            default:
        }
    }

    /**
     * @param ComponentReportRenderingStartedEvent $event
     */
    private function handleStart(ComponentReportRenderingStartedEvent $event): void
    {
        $componentName = $event->getComponent()->name();
        if (!isset($this->startedAt[$componentName])) {
            $this->startedAt[$componentName] = $event->getMicroTime();
        }
    }

    /**
     * @param ComponentReportRenderingFinishedEvent $event
     */
    private function handleFinish(ComponentReportRenderingFinishedEvent $event): void
    {
        $componentName = $event->getComponent()->name();
        $startedAt = $this->startedAt[$componentName] ?? microtime(true);
        unset($this->startedAt[$componentName]);

        $executionTime = round($event->getMicroTime() - $startedAt, 3);
        Console::write(sprintf('%s: %s sec.', $componentName, $executionTime), true);
        Console::writeln();
    }
}
