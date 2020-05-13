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
        return [
            'id' => spl_object_hash($node),
            'label' => $node->name(),
        ];
    }
}
