<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\UnitOfCodePage;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

/**
 * Class UnitsOfCodeGraphEdgeExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\UnitOfCodePage
 */
class UnitsOfCodeGraphEdgeExtractor
{
    /**
     * @param UnitOfCode[] $edge [$from, $to]
     * @return array
     */
    public function extract(array $edge): array
    {
        $from = $edge['from'];
        $to = $edge['to'];

        $extractedData = [
            'from' => spl_object_hash($from),
            'to' => spl_object_hash($to),
        ];

        $isRelationInAllowedState = $from->component()->isUnitsOfCodeRelationInAllowedState($to, $from);

        if (!$from->component()->isDependencyAllowed($to->component())) {
            $extractedData['color'] = $isRelationInAllowedState ? 'yellow' : 'red';
        } elseif (!$to->isAccessibleFromOutside() && !$to->belongToComponent($from->component())) {
            $extractedData['color'] = $isRelationInAllowedState ? 'yellow' : 'orange';
        }

        return $extractedData;
    }
}
