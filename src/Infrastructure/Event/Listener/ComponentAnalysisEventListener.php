<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener;

use Chetkov\PHPCleanArchitecture\Infrastructure\Console\Console;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\ComponentAnalysisEvent;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\ComponentAnalysisStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\ComponentAnalysisFinishedEvent;
use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Service\EventListenerInterface;

class ComponentAnalysisEventListener implements EventListenerInterface
{
    /** @var array<float> */
    private $startedAt = [];

    /**
     * @param EventInterface $event
     */
    public function handle(EventInterface $event): void
    {
        if (!$event instanceof ComponentAnalysisEvent) {
            return;
        }

        switch (true) {
            case $event instanceof ComponentAnalysisStartedEvent:
                $this->handleStart($event);
                break;
            case $event instanceof ComponentAnalysisFinishedEvent:
                $this->handleFinish($event);
                break;
            default:
        }
    }

    /**
     * @param ComponentAnalysisStartedEvent $event
     */
    private function handleStart(ComponentAnalysisStartedEvent $event): void
    {
        $componentName = $event->getComponent()->name();
        if (!isset($this->startedAt[$componentName])) {
            $this->startedAt[$componentName] = $event->getMicroTime();
        }
    }

    /**
     * @param ComponentAnalysisFinishedEvent $event
     */
    private function handleFinish(ComponentAnalysisFinishedEvent $event): void
    {
        $componentName = $event->getComponent()->name();
        $startedAt = $this->startedAt[$componentName] ?? microtime(true);
        unset($this->startedAt[$componentName]);

        $executionTime = round($event->getMicroTime() - $startedAt, 3);
        Console::write(sprintf('%s: %s sec.', $componentName, $executionTime), true);
        Console::writeln();
    }
}
