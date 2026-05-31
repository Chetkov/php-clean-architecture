<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Config;

final class ConfigTreeBuilder
{
    /** @var ConfigNormalizer */
    private $normalizer;

    /** @var ConfigInheritanceResolver */
    private $inheritanceResolver;

    /** @var ConfigTreePathResolver */
    private $pathResolver;

    /** @var AllowedStateStorageResolver */
    private $allowedStateStorageResolver;

    /** @var string */
    private $rootReportsPath;

    public function __construct(
        ?ConfigNormalizer $normalizer = null,
        ?ConfigInheritanceResolver $inheritanceResolver = null,
        ?ConfigTreePathResolver $pathResolver = null,
        ?AllowedStateStorageResolver $allowedStateStorageResolver = null
    ) {
        $this->normalizer = $normalizer ?? new ConfigNormalizer();
        $this->inheritanceResolver = $inheritanceResolver ?? new ConfigInheritanceResolver($this->normalizer);
        $this->pathResolver = $pathResolver ?? new ConfigTreePathResolver();
        $this->allowedStateStorageResolver = $allowedStateStorageResolver
            ?? new AllowedStateStorageResolver($this->pathResolver);
    }

    /**
     * @param array<string, mixed> $rootConfig
     */
    public function build(array $rootConfig): EffectiveConfigNode
    {
        $configuredReportsPath = $rootConfig['reports_dir'] ?? null;
        $this->rootReportsPath = $this->pathResolver->normalizeRootReportPath(
            is_string($configuredReportsPath) && $configuredReportsPath !== '' ? $configuredReportsPath : getcwd() . '/phpca-reports'
        );
        $rootConfig = $this->normalizer->normalizeConfig($rootConfig);
        $rootConfig['reports_dir'] = $this->rootReportsPath;
        $allowedStateContext = new AllowedStateStorageContext(
            $this->allowedStateStorageResolver->configuredStorage($rootConfig),
            []
        );

        return $this->buildNode(
            'root',
            'Root',
            $this->rootReportsPath,
            [],
            $rootConfig,
            $this->inheritanceResolver->extractInheritedContext($rootConfig),
            $allowedStateContext
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
        array $inheritedContext,
        AllowedStateStorageContext $allowedStateContext
    ): EffectiveConfigNode {
        $components = $this->normalizer->normalizeComponents($config['components'] ?? []);
        $children = [];
        $usedChildSlugs = [];
        foreach ($components as $component) {
            $subConfig = $component['sub'] ?? null;
            if (!is_array($subConfig)) {
                continue;
            }

            $componentName = isset($component['name']) && is_string($component['name']) && $component['name'] !== ''
                ? $component['name']
                : 'component';
            $childIdParts = $this->pathResolver->childIdParts($idParts, $componentName, $usedChildSlugs);
            $childConfig = $this->inheritanceResolver->createEffectiveSubConfig($this->stringKeyedArray($subConfig), $inheritedContext);
            if (!isset($childConfig['debug_scan_paths'])) {
                $childConfig['debug_scan_paths'] = $this->componentRootPaths($component);
            }
            $childReportPath = $this->pathResolver->childReportPath($this->rootReportsPath, $childIdParts);
            $childConfig['reports_dir'] = $childReportPath;
            $childAllowedStateContext = $this->allowedStateStorageResolver->configureChildStorage(
                $childConfig,
                $this->stringKeyedArray($subConfig),
                $childIdParts,
                $allowedStateContext
            );

            $children[] = $this->buildNode(
                implode('/', $childIdParts),
                $componentName,
                $childReportPath,
                $childIdParts,
                $childConfig,
                $this->inheritanceResolver->extractInheritedContext($childConfig),
                $childAllowedStateContext
            );
        }

        $config['components'] = $this->normalizer->stripSubConfigs($components);

        return new EffectiveConfigNode($id, $title, $reportPath, $config, $children);
    }

    /**
     * @param array<string, mixed> $component
     *
     * @return array<string>
     */
    private function componentRootPaths(array $component): array
    {
        $paths = [];
        $roots = $component['roots'] ?? [];
        if (!is_array($roots)) {
            return [];
        }

        foreach ($roots as $rootConfig) {
            if (!is_array($rootConfig) || empty($rootConfig['path']) || !is_string($rootConfig['path'])) {
                continue;
            }

            $paths[] = $rootConfig['path'];
        }

        return $paths;
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
