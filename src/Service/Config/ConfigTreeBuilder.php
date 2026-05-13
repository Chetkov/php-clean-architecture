<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Config;

final class ConfigTreeBuilder
{
    private const INHERITABLE_KEYS = [
        'exclusions',
        'factories',
        'restrictions',
        'vendor_based_components',
    ];

    /** @var string */
    private $rootReportsPath;

    /**
     * @param array<string, mixed> $rootConfig
     */
    public function build(array $rootConfig): ConfigTreeNode
    {
        $this->rootReportsPath = $this->normalizePath($rootConfig['reports_dir'] ?? getcwd() . '/phpca-reports');
        $rootConfig = $this->normalizeConfig($rootConfig);
        $rootConfig['reports_dir'] = $this->rootReportsPath;

        return $this->buildNode(
            'root',
            'Root',
            $this->rootReportsPath,
            [],
            $rootConfig,
            $this->extractInheritedContext($rootConfig)
        );
    }

    /**
     * @param array<string> $idParts
     * @param array<string, mixed> $config
     * @param array<string, mixed> $inheritedContext
     */
    private function buildNode(
        string $id,
        string $title,
        string $reportPath,
        array $idParts,
        array $config,
        array $inheritedContext
    ): ConfigTreeNode {
        $components = $this->normalizeComponents($config['components'] ?? []);
        $children = [];
        $usedChildSlugs = [];
        foreach ($components as $component) {
            if (!isset($component['sub']) || !is_array($component['sub'])) {
                continue;
            }

            $componentName = (string) ($component['name'] ?? 'component');
            $slug = $this->uniqueSlug($this->slugify($componentName), $usedChildSlugs);
            $childIdParts = array_merge($idParts, [$slug]);
            $childConfig = $this->createEffectiveSubConfig($component['sub'], $inheritedContext);
            $childReportPath = $this->rootReportsPath . '/' . implode('/', $childIdParts);
            $childConfig['reports_dir'] = $childReportPath;

            $children[] = $this->buildNode(
                implode('/', $childIdParts),
                $componentName,
                $childReportPath,
                $childIdParts,
                $childConfig,
                $this->extractInheritedContext($childConfig)
            );
        }

        $config['components'] = $this->stripSubConfigs($components);

        return new ConfigTreeNode($id, $title, $reportPath, $config, $children);
    }

    /**
     * @param array<string, mixed> $subConfig
     * @param array<string, mixed> $inheritedContext
     *
     * @return array<string, mixed>
     */
    private function createEffectiveSubConfig(array $subConfig, array $inheritedContext): array
    {
        $inherit = $subConfig['inherit'] ?? [];
        if (!is_array($inherit)) {
            $inherit = [];
        }

        $effective = [];
        foreach ($inherit as $key) {
            if (!is_string($key) || !in_array($key, self::INHERITABLE_KEYS, true) || !array_key_exists($key, $inheritedContext)) {
                continue;
            }

            $effective[$key] = $inheritedContext[$key];
        }

        unset($subConfig['inherit']);

        $effective = $this->mergeConfig($effective, $subConfig);
        return $this->normalizeConfig($effective);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function normalizeConfig(array $config): array
    {
        $config['components'] = $this->normalizeComponents($config['components'] ?? []);
        $config['vendor_based_components'] = $config['vendor_based_components'] ?? [
            'enabled' => false,
            'vendor_path' => '',
            'excluded' => [],
        ];
        $config['restrictions'] = $config['restrictions'] ?? [];
        $config['exclusions'] = $config['exclusions'] ?? [
            'allowed_state' => [
                'enabled' => false,
                'storage' => '',
            ],
        ];

        return $config;
    }

    /**
     * @param mixed $components
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeComponents($components): array
    {
        if (!is_array($components)) {
            return [];
        }

        $normalized = [];
        foreach ($components as $key => $componentConfig) {
            if (!is_array($componentConfig)) {
                continue;
            }

            if (!isset($componentConfig['name']) && is_string($key)) {
                $componentConfig['name'] = $key;
            }

            $normalized[] = $componentConfig;
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $components
     *
     * @return array<int, array<string, mixed>>
     */
    private function stripSubConfigs(array $components): array
    {
        return array_map(static function (array $component): array {
            unset($component['sub']);
            return $component;
        }, $components);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function extractInheritedContext(array $config): array
    {
        $context = [];
        foreach (self::INHERITABLE_KEYS as $key) {
            if (array_key_exists($key, $config)) {
                $context[$key] = $config[$key];
            }
        }

        return $context;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function mergeConfig(array $base, array $override): array
    {
        if ($this->isList($base) || $this->isList($override)) {
            return $override;
        }

        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeConfig($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * @param array<mixed> $items
     */
    private function isList(array $items): bool
    {
        $expectedKey = 0;
        foreach (array_keys($items) as $key) {
            if ($key !== $expectedKey) {
                return false;
            }

            $expectedKey++;
        }

        return true;
    }

    private function normalizePath(string $path): string
    {
        return rtrim($path, '/');
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
