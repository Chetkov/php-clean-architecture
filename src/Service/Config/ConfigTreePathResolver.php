<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Config;

final class ConfigTreePathResolver
{
    public function normalizeRootReportPath(string $path): string
    {
        return rtrim($path, '/');
    }

    /**
     * @param array<string> $parentIdParts
     * @param array<string, true> $usedSiblingSlugs
     *
     * @return array<string>
     */
    public function childIdParts(array $parentIdParts, string $componentName, array &$usedSiblingSlugs): array
    {
        return array_merge($parentIdParts, [
            $this->uniqueSlug($this->slugify($componentName), $usedSiblingSlugs),
        ]);
    }

    /**
     * @param array<string> $childIdParts
     */
    public function childReportPath(string $rootReportsPath, array $childIdParts): string
    {
        return $rootReportsPath . '/' . implode('/', $childIdParts);
    }

    /**
     * @param array<string> $childIdParts
     */
    public function childAllowedStateStoragePath(string $rootStoragePath, array $childIdParts): string
    {
        $directory = dirname($rootStoragePath);
        $baseName = basename($rootStoragePath);
        if (substr($baseName, -4) === '.php') {
            $baseName = substr($baseName, 0, -4);
        }

        return $directory . '/' . $baseName . '/' . implode('/', $childIdParts) . '.php';
    }

    /**
     * @param array<string, true> $usedSlugs
     */
    private function uniqueSlug(string $slug, array &$usedSlugs): string
    {
        $candidate = $slug;
        $counter = 2;
        while (isset($usedSlugs[$candidate])) {
            $candidate = $slug . '-' . $counter;
            $counter++;
        }

        $usedSlugs[$candidate] = true;
        return $candidate;
    }

    private function slugify(string $value): string
    {
        $originalValue = $value;
        $value = strtr($value, $this->transliterationMap());
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($transliterated) && $transliterated !== '') {
            $value = $transliterated;
        }

        $value = strtolower($value);
        $value = (string) preg_replace('/[^a-z0-9._-]+/', '-', $value);
        $value = trim($value, '-._');

        if ($value === '') {
            return 'component-' . substr(sha1($originalValue), 0, 12);
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    private function transliterationMap(): array
    {
        return [
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'E',
            'Ж' => 'Zh',
            'З' => 'Z',
            'И' => 'I',
            'Й' => 'Y',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'H',
            'Ц' => 'Ts',
            'Ч' => 'Ch',
            'Ш' => 'Sh',
            'Щ' => 'Sch',
            'Ъ' => '',
            'Ы' => 'Y',
            'Ь' => '',
            'Э' => 'E',
            'Ю' => 'Yu',
            'Я' => 'Ya',
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'e',
            'ж' => 'zh',
            'з' => 'z',
            'и' => 'i',
            'й' => 'y',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'ts',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'sch',
            'ъ' => '',
            'ы' => 'y',
            'ь' => '',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',
        ];
    }
}
