<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

/**
 * Class ClassesCreatedThroughNewParsingStrategy
 * @package Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy
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
