<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor;

use Chetkov\PHPCleanArchitecture\Model\ComponentInterface;

/**
 * Class ComponentsGraphNodeExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor
 */
class ComponentsGraphNodeExtractor
{
    /**
     * @param ComponentInterface $node
     * @return array<string, mixed>
     */
    public function extract(ComponentInterface $node): array
    {
        $fanIn = array_map(static function (ComponentInterface $inputDependency) {
            return $inputDependency->name();
        }, $node->getDependentComponents());

        $fanOut = array_map(static function (ComponentInterface $outputDependency) {
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
