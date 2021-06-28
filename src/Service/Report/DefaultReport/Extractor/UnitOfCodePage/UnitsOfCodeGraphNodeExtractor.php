<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\UnitOfCodePage;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

/**
 * Class UnitsOfCodeGraphNodeExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\UnitOfCodePage
 */
class UnitsOfCodeGraphNodeExtractor
{
    /**
     * @param UnitOfCode $node
     * @return array<string, mixed>
     */
    public function extract(UnitOfCode $node): array
    {
        return [
            'id' => spl_object_hash($node),
            'label' => $node->name(),
            'cluster' => $node->component()->name(),
        ];
    }
}
