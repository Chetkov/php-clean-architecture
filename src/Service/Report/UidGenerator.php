<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report;

/**
 * Trait UidGenerator
 * @package Chetkov\PHPCleanArchitecture\Service\Report
 */
trait UidGenerator
{
    /**
     * @param string $name
     * @return string
     */
    private function generateUid(string $name): string
    {
        return strtolower(preg_replace('/[ \/\\\]/', '-', $name));
    }
}
