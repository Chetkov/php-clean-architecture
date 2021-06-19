<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Helper;

/**
 * Class PathHelper
 * @package Chetkov\PHPCleanArchitecture\Service\Helper
 */
class PathHelper
{
    /**
     * @param string $subject
     * @return string
     */
    public static function removeDoubleSlashes(string $subject): string
    {
        return preg_replace("/\/{2,}/u", '/', $subject);
    }

    /**
     * @param string $subject
     * @return string
     */
    public static function removeDoubleBackslashes(string $subject): string
    {
        return preg_replace("/\\\{2,}/u", '\\', $subject);
    }

    /**
     * @param string $filePath
     * @return string
     */
    public static function pathToNamespace(string $filePath): string
    {
        return str_replace(['/', '.php'], ['\\', ''], self::removeDoubleSlashes($filePath));
    }
}
