<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Config;

final class ConfigInheritanceResolver
{
    private const INHERITABLE_KEYS = [
        'exclusions',
        'factories',
        'restrictions',
        'vendor_based_components',
    ];

    /** @var ConfigNormalizer */
    private $normalizer;

    public function __construct(ConfigNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * @param array<string, mixed> $subConfig
     * @param array<string, mixed> $inheritedContext
     *
     * @return array<string, mixed>
     */
    public function createEffectiveSubConfig(array $subConfig, array $inheritedContext): array
    {
        $effective = $this->selectInheritedValues($subConfig['inherit'] ?? [], $inheritedContext);
        unset($subConfig['inherit']);

        return $this->normalizer->normalizeConfig($this->mergeConfig($effective, $subConfig));
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public function extractInheritedContext(array $config): array
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
     * @param mixed $inherit
     * @param array<string, mixed> $inheritedContext
     *
     * @return array<string, mixed>
     */
    private function selectInheritedValues($inherit, array $inheritedContext): array
    {
        if (!is_array($inherit)) {
            return [];
        }

        $effective = [];
        foreach ($inherit as $key) {
            if (!is_string($key) || !in_array($key, self::INHERITABLE_KEYS, true) || !array_key_exists($key, $inheritedContext)) {
                continue;
            }

            $effective[$key] = $inheritedContext[$key];
        }

        return $effective;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function mergeConfig(array $base, array $override): array
    {
        if ($override === []) {
            return $this->isList($base) ? [] : $base;
        }

        if ($base === []) {
            return $override;
        }

        if ($this->isList($base) || $this->isList($override)) {
            return $override;
        }

        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                if ($this->isList($base[$key]) || $this->isList($value)) {
                    $base[$key] = $value;
                    continue;
                }

                $base[$key] = $this->mergeConfig($this->stringKeyedArray($base[$key]), $this->stringKeyedArray($value));
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
        if ($items === []) {
            return false;
        }

        $expectedKey = 0;
        foreach (array_keys($items) as $key) {
            if ($key !== $expectedKey) {
                return false;
            }

            $expectedKey++;
        }

        return true;
    }

    /**
     * @param array<mixed, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function stringKeyedArray(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
