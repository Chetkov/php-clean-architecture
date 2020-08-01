<?php

namespace Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy;

use Chetkov\PHPCleanArchitecture\Helper\StringHelper;

/**
 * Class ThrowsAnnotationsParsingStrategy
 * @package Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy
 */
class ThrowsAnnotationsParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы исключений найденные в аннотациях throws
     * @inheritDoc
     */
    public function parse(string $content): array
    {
        preg_match_all("/@throws\s+(?P<exceptions>[\w|\\\\\$]*)/ium", $content, $matches);

        $dependencies = [];
        foreach (array_filter($matches['exceptions']) as $exceptionsAsString) {
            foreach (explode('|', StringHelper::removeSpaces($exceptionsAsString)) as $exception) {
                $dependencies[$exception] = true;
            }
        }

        return array_keys($dependencies);
    }
}
