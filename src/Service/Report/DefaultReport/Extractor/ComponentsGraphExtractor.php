<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\ObjectsGraphBuilder;

/**
 * Class ComponentsGraphExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor
 */
class ComponentsGraphExtractor
{
    /** @var ComponentsGraphNodeExtractor */
    private $nodeExtractor;

    /** @var ComponentsGraphEdgeExtractor */
    private $edgeExtractor;

    public function __construct()
    {
        $this->nodeExtractor = new ComponentsGraphNodeExtractor();
        $this->edgeExtractor = new ComponentsGraphEdgeExtractor();
    }

    /**
     * @param ObjectsGraphBuilder $graphBuilder
     * @return array<string, mixed>
     */
    public function extract(ObjectsGraphBuilder $graphBuilder): array
    {
        return [
            'nodes' => json_encode(array_map(function (Component $node) {
                return $this->nodeExtractor->extract($node);
            }, $graphBuilder->getNodes())),
            'edges' => json_encode(array_map(function (array $edge) {
                return $this->edgeExtractor->extract($edge);
            }, $graphBuilder->getEdges())),
            'clusters' => '["' . implode('","',array_unique(array_map(static function (Component $component) {
                    return $component->group();
                }, $graphBuilder->getNodes()))) . '"]',
        ];
    }
}
