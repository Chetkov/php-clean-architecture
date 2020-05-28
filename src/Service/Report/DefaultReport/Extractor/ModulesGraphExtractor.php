<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor;

use Chetkov\PHPCleanArchitecture\Model\Module;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\ObjectsGraphBuilder;

/**
 * Class ModulesGraphExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor
 */
class ModulesGraphExtractor
{
    /** @var ModulesGraphNodeExtractor */
    private $nodeExtractor;

    /** @var ModulesGraphEdgeExtractor */
    private $edgeExtractor;

    /**
     * ModulesGraphExtractor constructor.
     */
    public function __construct()
    {
        $this->nodeExtractor = new ModulesGraphNodeExtractor();
        $this->edgeExtractor = new ModulesGraphEdgeExtractor();
    }

    /**
     * @param ObjectsGraphBuilder $graphBuilder
     * @return array
     */
    public function extract(ObjectsGraphBuilder $graphBuilder): array
    {
        return [
            'nodes' => json_encode(array_map(function (Module $node) {
                return $this->nodeExtractor->extract($node);
            }, $graphBuilder->getNodes())),
            'edges' => json_encode(array_map(function (array $edge) {
                return $this->edgeExtractor->extract($edge);
            }, $graphBuilder->getEdges())),
        ];
    }
}
