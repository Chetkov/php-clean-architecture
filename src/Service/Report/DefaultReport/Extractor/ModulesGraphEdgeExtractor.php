<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor;

use Chetkov\PHPCleanArchitecture\Model\Module;

/**
 * Class ModulesGraphEdgeExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor
 */
class ModulesGraphEdgeExtractor
{
    /**
     * @param Module[] $edge [$from, $to]
     * @return array
     */
    public function extract(array $edge): array
    {
        $from = $edge['from'];
        $to = $edge['to'];

        $extractedData = [
            'from' => spl_object_hash($from),
            'to' => spl_object_hash($to),
            'label' => (string)count($from->getDependencyUnitsOfCode($to)),
        ];

        if (!$from->isDependencyAllowed($to)) {
            $extractedData['color'] = 'red';
        } else {
            foreach ($from->getDependencyUnitsOfCode($to) as $dependency) {
                if (!$dependency->isAccessibleFromOutside()) {
                    $extractedData['color'] = 'orange';
                    break;
                }
            }
        }

        return $extractedData;
    }
}
