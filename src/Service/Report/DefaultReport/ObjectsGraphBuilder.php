<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport;

/**
 * Class ObjectsGraphBuilder
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport
 */
class ObjectsGraphBuilder
{
    /** @var array<mixed> */
    private $nodes;

    /** @var array<array> */
    private $edges;

    public function reset(): void
    {
        $this->nodes = [];
        $this->edges = [];
    }

    /**
     * @param mixed $node
     */
    public function addNode($node): void
    {
        $this->validate($node);
        $nodeUid = $this->makeNodeUid($node);
        if (!isset($this->nodes[$nodeUid])) {
            $this->nodes[$nodeUid] = $node;
        }
    }

    /**
     * @param mixed $from
     * @param mixed $to
     */
    public function addEdge($from, $to): void
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
     * @return array<mixed>
     */
    public function getNodes(): array
    {
        return array_values($this->nodes);
    }

    /**
     * @return array<array>
     */
    public function getEdges(): array
    {
        return array_values($this->edges);
    }

    /**
     * @param mixed $node
     * @return string
     */
    private function makeNodeUid($node): string
    {
        return spl_object_hash($node);
    }

    /**
     * @param mixed $node
     */
    private function validate($node): void
    {
        if (!is_object($node)) {
            throw new \InvalidArgumentException('$node must be of type object!');
        }
    }
}
