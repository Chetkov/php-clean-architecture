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

    /** @var string */
    private $rootReportsPath;

    public function __construct(
        ?ConfigNormalizer $normalizer = null,
        ?ConfigInheritanceResolver $inheritanceResolver = null,
        ?ConfigTreePathResolver $pathResolver = null
    ) {
        $this->normalizer = $normalizer ?? new ConfigNormalizer();
        $this->inheritanceResolver = $inheritanceResolver ?? new ConfigInheritanceResolver($this->normalizer);
        $this->pathResolver = $pathResolver ?? new ConfigTreePathResolver();
    }

    /**
     * @param array<string, mixed> $rootConfig
     */
    public function build(array $rootConfig): EffectiveConfigNode
    {
        $this->rootReportsPath = $this->pathResolver->normalizeRootReportPath(
            $rootConfig['reports_dir'] ?? getcwd() . '/phpca-reports'
        );
        $rootConfig = $this->normalizer->normalizeConfig($rootConfig);
        $rootConfig['reports_dir'] = $this->rootReportsPath;

        return $this->buildNode(
            'root',
            'Root',
            $this->rootReportsPath,
            [],
            $rootConfig,
            $this->inheritanceResolver->extractInheritedContext($rootConfig)
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
    ): EffectiveConfigNode {
        $components = $this->normalizer->normalizeComponents($config['components'] ?? []);
        $children = [];
        $usedChildSlugs = [];
        foreach ($components as $component) {
            if (!isset($component['sub']) || !is_array($component['sub'])) {
                continue;
            }

            $componentName = (string) ($component['name'] ?? 'component');
            $childIdParts = $this->pathResolver->childIdParts($idParts, $componentName, $usedChildSlugs);
            $childConfig = $this->inheritanceResolver->createEffectiveSubConfig($component['sub'], $inheritedContext);
            $childReportPath = $this->pathResolver->childReportPath($this->rootReportsPath, $childIdParts);
            $childConfig['reports_dir'] = $childReportPath;

            $children[] = $this->buildNode(
                implode('/', $childIdParts),
                $componentName,
                $childReportPath,
                $childIdParts,
                $childConfig,
                $this->inheritanceResolver->extractInheritedContext($childConfig)
            );
        }

        $config['components'] = $this->normalizer->stripSubConfigs($components);

        return new EffectiveConfigNode($id, $title, $reportPath, $config, $children);
    }
}
