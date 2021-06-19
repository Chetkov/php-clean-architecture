<?php

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

/**
 * Interface CodeParsingStrategyInterface
 * @package Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy
 */
interface CodeParsingStrategyInterface
{
    /**
     * @param string $content
     * @return string[]
     */
    public function parse(string $content): array;
}
