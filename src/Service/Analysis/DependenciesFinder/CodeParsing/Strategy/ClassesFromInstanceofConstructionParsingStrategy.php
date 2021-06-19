<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

/**
 * Class ClassesFromInstanceofConstructionParsingStrategy
 * @package Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy
 */
class ClassesFromInstanceofConstructionParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы используемые в конструкциях instanceof
     * @inheritDoc
     */
    public function parse(string $content): array
    {
        preg_match_all('/(?P<variable>\$\w+) +instanceof +(?P<class>[\w\\\]+)/ium', $content, $matches);
        return array_unique($matches['class']);
    }
}
