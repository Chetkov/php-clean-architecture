<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor;

use Chetkov\PHPCleanArchitecture\Model\Component;

/**
 * Class ComponentsGraphNodeExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor
 */
class ComponentsGraphNodeExtractor
{
    /**
     * @param Component $node
     * @return array
     */
    public function extract(Component $node): array
    {
        $fanIn = array_map(function (Component $inputDependency) {
            return $inputDependency->name();
        }, $node->getDependentComponents());

        $fanOut = array_map(function (Component $outputDependency) {
            return $outputDependency->name();
        }, $node->getDependencyComponents());

        $title = 'Abstractness: ' . $node->calculateAbstractnessRate() . ', ';
        $title .= 'Instability: ' . $node->calculateInstabilityRate() . ', ';
        $title .= 'Fan-in: ' . implode(', ', $fanIn) . ', ';
        $title .= 'Fan-out: ' . implode(', ', $fanOut) . '';

        return [
            'id' => spl_object_hash($node),
            'label' => $node->name(),
            'title' => $title,
        ];
    }
}
