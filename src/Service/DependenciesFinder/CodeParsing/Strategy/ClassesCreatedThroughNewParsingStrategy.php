<?php

namespace Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy;

/**
 * Class ClassesCreatedThroughNewParsingStrategy
 * @package Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy
 */
class ClassesCreatedThroughNewParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы, экземпляры которых создаются через new
     * @inheritDoc
     */
    public function parse(string $content): array
    {
        preg_match_all('/new\s*([^(]*)/ium', $content, $matches);
        [, $result] = $matches;
        return array_unique($result);
    }
}
