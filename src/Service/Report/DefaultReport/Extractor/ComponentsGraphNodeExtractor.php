<?php

declare(strict_types=1);

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
     * @return array<string, mixed>
     */
    public function extract(Component $node): array
    {
        $fanIn = array_map(static function (Component $inputDependency) {
            return $inputDependency->name();
        }, $node->getDependentComponents());

        $fanOut = array_map(static function (Component $outputDependency) {
            return $outputDependency->name();
        }, $node->getDependencyComponents());

        $title = 'Abstractness: ' . $node->calculateAbstractnessRate() . ', ';
        $title .= 'Instability: ' . $node->calculateInstabilityRate() . ', ';
        $title .= 'Fan-in: ' . implode(', ', $fanIn) . ', ';
        $title .= 'Fan-out: ' . implode(', ', $fanOut) . '';

        return [
            'id' => spl_object_hash($node),
            'label' => $node->name(),
            'cluster' => $node->group(),
            'title' => $title,
        ];
    }
}
