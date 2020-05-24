<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\Extractor\UnitOfCodePage;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

/**
 * Class UnitsOfCodeGraphEdgeExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\Extractor\UnitOfCodePage
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

        if (!$from->module()->isDependencyAllowed($to->module())) {
            $extractedData['color'] = 'red';
        } elseif (!$to->isAccessibleFromOutside() && !$to->belongToModule($from->module())) {
            $extractedData['color'] = 'orange';
        }

        return $extractedData;
    }
}
