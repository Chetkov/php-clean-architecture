<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\UnitOfCodePage;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\ObjectsGraphBuilder;

/**
 * Class UnitsOfCodeGraphExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\UnitOfCodePage
 */
class UnitsOfCodeGraphExtractor
{
    /** @var UnitsOfCodeGraphNodeExtractor */
    private $nodeExtractor;

    /** @var UnitsOfCodeGraphEdgeExtractor */
    private $edgeExtractor;

    public function __construct()
    {
        $this->nodeExtractor = new UnitsOfCodeGraphNodeExtractor();
        $this->edgeExtractor = new UnitsOfCodeGraphEdgeExtractor();
    }

    /**
     * @param ObjectsGraphBuilder $graphBuilder
     * @return array
     */
    public function extract(ObjectsGraphBuilder $graphBuilder): array
    {
        return [
            'nodes' => json_encode(array_map(function (UnitOfCode $node) {
                return $this->nodeExtractor->extract($node);
            }, $graphBuilder->getNodes())),
            'edges' => json_encode(array_map(function (array $edge) {
                return $this->edgeExtractor->extract($edge);
            }, $graphBuilder->getEdges())),
        ];
    }
}
