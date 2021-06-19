<?php

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

use Chetkov\PHPCleanArchitecture\Helper\StringHelper;

/**
 * Class VarAnnotationsParsingStrategy
 * @package Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy
 */
class VarAnnotationsParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы найденные в аннотациях var
     * @inheritDoc
     */
    public function parse(string $content): array
    {
        $filter = static function (string $element) {
            return !empty($element) && mb_stripos($element, '$') === false;
        };

        $groupPattern = '\s*([\w|\[\]\\\\\$]*)';
        preg_match_all("/@var{$groupPattern}{$groupPattern}/ium", $content, $matches);
        [, $group1, $group2] = $matches;

        $dependencies = [];
        foreach (array_merge(array_filter($group1, $filter), array_filter($group2, $filter)) as $one) {
            foreach (explode('|', str_replace('[]', '', StringHelper::removeSpaces($one))) as $type) {
                $dependencies[$type] = true;
            }
        }

        return array_keys($dependencies);
    }
}
