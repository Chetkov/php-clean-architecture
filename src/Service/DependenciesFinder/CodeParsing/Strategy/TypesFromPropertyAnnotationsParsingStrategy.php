<?php

namespace Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy;

use Chetkov\PHPCleanArchitecture\Helper\StringHelper;

/**
 * Class TypesFromPropertyAnnotationsParsingStrategy
 * @package Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy
 */
class TypesFromPropertyAnnotationsParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы найденные в аннотациях param
     * @inheritDoc
     */
    public function parse(string $content): array
    {
        $pattern = '/(@property|@property-read|@property-write)[\s]+(?P<types>[^$]*)/ium';
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
