<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\Extractor\UnitOfCodePage;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

/**
 * Class UnitsOfCodeGraphNodeExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\Extractor\UnitOfCodePage
 */
class UnitsOfCodeGraphNodeExtractor
{
    /**
     * @param UnitOfCode $node
     * @return array
     */
    public function extract(UnitOfCode $node): array
    {
        return [
            'id' => spl_object_hash($node),
            'label' => $node->name(),
        ];
    }
}
