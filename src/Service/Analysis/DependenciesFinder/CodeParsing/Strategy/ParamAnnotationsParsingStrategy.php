<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

use Chetkov\PHPCleanArchitecture\Service\Helper\StringHelper;

/**
 * Class ParamAnnotationsParsingStrategy
 * @package Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy
 */
class ParamAnnotationsParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы найденные в аннотациях param
     * @inheritDoc
     */
    public function parse(string $content): array
    {
        $pattern = '/@param[\s]+(?P<types>[^$]*)/ium';
        preg_match_all($pattern, $content, $matches);

        $dependencies = [];
        foreach (array_filter($matches['types']) as $typesAsString) {
            foreach (explode('|', str_replace('[]', '', StringHelper::removeSpaces($typesAsString))) as $type) {
                $dependencies[$type] = true;
            }
        }

        return array_keys($dependencies);
    }
}
