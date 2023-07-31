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
        return (string) preg_replace("/\/{2,}/u", '/', $subject);
    }

    /**
     * @param string $subject
     * @return string
     */
    public static function removeDoubleBackslashes(string $subject): string
    {
        return (string) preg_replace("/\\\{2,}/u", '\\', $subject);
    }

    /**
     * @param string $filePath
     * @return string
     */
    public static function pathToNamespace(string $filePath): string
    {
        return str_replace(['/', '.php'], ['\\', ''], self::removeDoubleSlashes($filePath));
    }

    /**
     * @param string $fullName
     * @return string|null
     */
    public static function detectPath(string $fullName): ?string
    {
        try {
            assert(class_exists($fullName, false)
                || trait_exists($fullName, false)
                || interface_exists($fullName, false));
            $reflection = new \ReflectionClass($fullName);
            $path = $reflection->getFileName() ?: null;
        } catch (\ReflectionException $e) {
            $path = null;
        }
        return $path;
    }
}
