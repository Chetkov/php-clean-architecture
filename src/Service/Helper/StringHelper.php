<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Helper;

/**
 * Class StringHelper
 * @package Chetkov\PHPCleanArchitecture\Service\Helper
 */
class StringHelper
{
    /**
     * @param string $subject
     * @return string
     */
    public static function removeSpaces(string $subject): string
    {
        return preg_replace('/[ ]*/u', '', $subject);
    }

    /**
     * @param string $subject
     * @return string
     */
    public static function removeDoubleSpaces(string $subject): string
    {
        return preg_replace('/[ ]+/u', ' ', $subject);
    }

    /**
     * @param string $subject
     * @return string
     */
    public static function escapeBackslashes(string $subject): string
    {
        return str_replace('\\', '\\\\', $subject);
    }
}
