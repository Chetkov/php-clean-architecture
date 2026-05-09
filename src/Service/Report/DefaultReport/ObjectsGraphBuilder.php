<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport;

/**
 * Class ObjectsGraphBuilder
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport
 * @template T of object
 */
class ObjectsGraphBuilder
{
    /** @var array<string, T> */
    private $nodes;

    /** @var array<string, array{from: T, to: T}> */
    private $edges;

    public function reset(): void
    {
        $this->nodes = [];
        $this->edges = [];
    }

    /**
     * @param T $node
     */
    public function addNode(object $node): void
    {
        $nodeUid = $this->makeNodeUid($node);
        if (!isset($this->nodes[$nodeUid])) {
            $this->nodes[$nodeUid] = $node;
        }
    }

    /**
     * @param T $from
     * @param T $to
     */
    public function addEdge(object $from, object $to): void
    {
        $this->addNode($from);
        $this->addNode($to);

        $edgeUid = $this->makeNodeUid($from) . $this->makeNodeUid($to);
        if (!isset($this->edges[$edgeUid])) {
            $this->edges[$edgeUid] = [
                'from' => $from,
                'to' => $to,
            ];
        }
    }

    /**
     * @return array<T>
     */
    public function getNodes(): array
    {
        return array_values($this->nodes);
    }

    /**
     * @return array<array{from: T, to: T}>
     */
    public function getEdges(): array
    {
        return array_values($this->edges);
    }

    /**
     * @return string
     */
    private function makeNodeUid(object $node): string
    {
        return spl_object_hash($node);
    }
}
