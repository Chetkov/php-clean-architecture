<?php

namespace Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy;

use Chetkov\PHPCleanArchitecture\Helper\StringHelper;

/**
 * Class TypesFromThrowAnnotationsParsingStrategy
 * @package Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy
 */
class TypesFromThrowAnnotationsParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы найденные в аннотациях throw
     * @inheritDoc
     */
    public function parse(string $content): array
    {
        $filter = static function (string $element) {
            return !empty($element) && mb_stripos($element, '$') === false;
        };

        $groupPattern = '\s*([\w|\[\]\\\\\$]*)';
        preg_match_all("/@throw{$groupPattern}{$groupPattern}/ium", $content, $matches);
        [, $group1, $group2] = $matches;

        $dependencies = [];
        foreach (array_merge(array_filter($group1, $filter), array_filter($group2, $filter)) as $one) {
            foreach (explode('|', StringHelper::removeSpaces($one)) as $type) {
                $dependencies[$type] = true;
            }
        }

        return array_keys($dependencies);
    }
}
