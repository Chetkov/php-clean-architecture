<?php

namespace Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy;

/**
 * Class ClassesCalledStaticallyParsingStrategy
 * @package Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy
 */
class ClassesCalledStaticallyParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает классы, к которым есть обращения через ::
     * @inheritDoc
     */
    public function parse(string $content): array
    {
        preg_match_all('/([\w\\\]*)\s*:{2}/um', $content, $matches);
        [, $result] = $matches;
        return array_unique($result);
    }
}
