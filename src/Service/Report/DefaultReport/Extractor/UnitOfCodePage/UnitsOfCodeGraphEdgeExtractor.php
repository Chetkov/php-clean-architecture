<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\UnitOfCodePage;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

/**
 * Class UnitsOfCodeGraphEdgeExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\UnitOfCodePage
 */
class UnitsOfCodeGraphEdgeExtractor
{
    /**
     * @param array<UnitOfCode> $edge [$from, $to]
     * @return array<string, mixed>
     */
    public function extract(array $edge): array
    {
        $from = $edge['from'];
        $to = $edge['to'];

        $extractedData = [
            'from' => spl_object_hash($from),
            'to' => spl_object_hash($to),
        ];

        $isDependencyInAllowedState = $from->isDependencyInAllowedState($to);

        if (!$from->component()->isDependencyAllowed($to->component())) {
            $extractedData['color'] = $isDependencyInAllowedState ? 'green' : 'red';
        } elseif (!$to->belongToComponent($from->component())) {
            if (!$to->isAccessibleFromOutside()) {
                $extractedData['color'] = $isDependencyInAllowedState ? 'green' : 'orange';
            } elseif (!$from->isIntegrationAllowed($to->component())) {
                $extractedData['color'] = $isDependencyInAllowedState ? 'green' : 'gray';
            }
        }

        return $extractedData;
    }
}
