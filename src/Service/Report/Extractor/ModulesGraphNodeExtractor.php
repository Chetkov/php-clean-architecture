<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\Extractor;

use Chetkov\PHPCleanArchitecture\Model\Module;

/**
 * Class ModulesGraphNodeExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\Extractor
 */
class ModulesGraphNodeExtractor
{
    /**
     * @param Module $node
     * @return array
     */
    public function extract(Module $node): array
    {
        $fanIn = array_map(function (Module $inputDependency) {
            return $inputDependency->name();
        }, $node->getDependentModules());

        $fanOut = array_map(function (Module $outputDependency) {
            return $outputDependency->name();
        }, $node->getDependencyModules());

        $title = 'Abstractness: ' . $node->calculateAbstractnessRate() . '<br>';
        $title .= 'Instability: ' . $node->calculateInstabilityRate() . '<br>';
        $title .= 'Fan-in: ' . implode(', ', $fanIn) . '<br>';
        $title .= 'Fan-out: ' . implode(', ', $fanOut) . '<br>';

        return [
            'id' => spl_object_hash($node),
            'label' => $node->name(),
            'title' => $title,
        ];
    }
}
