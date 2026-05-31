<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Config;

final class ConfigNormalizer
{
    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public function normalizeConfig(array $config): array
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
    public function normalizeComponents($components): array
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

            /** @var array<string, mixed> $componentConfig */
            $normalized[] = $componentConfig;
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $components
     *
     * @return array<int, array<string, mixed>>
     */
    public function stripSubConfigs(array $components): array
    {
        return array_map(static function (array $component): array {
            unset($component['sub']);
            return $component;
        }, $components);
    }
}
