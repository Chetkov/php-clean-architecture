<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor;

use Chetkov\PHPCleanArchitecture\Model\Component;

/**
 * Class ComponentsGraphEdgeExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor
 */
class ComponentsGraphEdgeExtractor
{
    /**
     * @param Component[] $edge [$from, $to]
     * @return array<string, mixed>
     */
    public function extract(array $edge): array
    {
        $from = $edge['from'];
        $to = $edge['to'];
        $label = count($from->getDependentUnitsOfCode($to)) . '->' . count($from->getDependencyUnitsOfCode($to));

        $extractedData = [
            'from' => spl_object_hash($from),
            'to' => spl_object_hash($to),
            'label' => $label,
        ];

        if (!$from->isDependencyAllowed($to)) {
            $extractedData['color'] = $from->isDependencyInAllowedState($to) ? 'yellow' : 'red';
        } else {
            foreach ($from->getDependencyUnitsOfCode($to) as $dependency) {
                if (!$dependency->isAccessibleFromOutside()) {
                    foreach ($dependency->inputDependencies($from) as $dependent) {
                        if (!$dependent->isDependencyInAllowedState($dependency)) {
                            $extractedData['color'] = 'orange';
                            break 2;
                        }
                        $extractedData['color'] = 'yellow';
                    }
                }
            }
        }

        return $extractedData;
    }
}
