<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport;

use Chetkov\PHPCleanArchitecture\Service\Helper\StringHelper;
use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\ComponentPage\DependencyComponentExtractor;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\ComponentsGraphExtractor;
use Chetkov\PHPCleanArchitecture\Service\Report\TemplateRendererInterface;

/**
 * Class ComponentPageRenderingService
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport
 */
class ComponentPageRenderingService
{
    use UidGenerator;

    /** @var TemplateRendererInterface */
    private $templateRenderer;

    /** @var ObjectsGraphBuilder */
    private $componentsGraphBuilder;

    /** @var DependencyComponentExtractor */
    private $dependencyComponentExtractor;

    /** @var ComponentsGraphExtractor */
    private $componentsGraphExtractor;

    /**
     * @param TemplateRendererInterface $templateRenderer
     */
    public function __construct(TemplateRendererInterface $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
        $this->componentsGraphBuilder = new ObjectsGraphBuilder();
        $this->dependencyComponentExtractor = new DependencyComponentExtractor();
        $this->componentsGraphExtractor = new ComponentsGraphExtractor();
    }

    /**
     * @param string $reportsPath
     * @param Component $component
     * @param Component ...$processedComponents
     */
    public function render(string $reportsPath, Component $component, Component ...$processedComponents): void
    {
        $this->componentsGraphBuilder->reset();

        $extractedDependentComponentsData = [];
        foreach ($component->getDependentComponents() as $dependentComponent) {
            $this->componentsGraphBuilder->addEdge($dependentComponent, $component);
            $extractedDependentComponentsData[] = $this->dependencyComponentExtractor->extract($dependentComponent, $component, $processedComponents);
        }

        $extractedDependencyComponentsData = [];
        foreach ($component->getDependencyComponents() as $dependencyComponent) {
            if ($dependencyComponent->isGlobal() || $dependencyComponent->isPrimitives()) {
                continue;
            }
            $this->componentsGraphBuilder->addEdge($component, $dependencyComponent);
            $extractedDependencyComponentsData[] = $this->dependencyComponentExtractor->extract($dependencyComponent, $component, $processedComponents, true);
        }

        $reportContent = $this->templateRenderer->render('component-info.twig', [
            'name' => $component->name(),
            'primitiveness_rate' => $component->calculatePrimitivenessRate(),
            'abstractness_rate' => $component->calculateAbstractnessRate(),
            'instability_rate' => $component->calculateInstabilityRate(),
            'distance_rate' => $component->calculateDistanceRate(),
            'dependent_components' => $extractedDependentComponentsData,
            'dependency_components' => $extractedDependencyComponentsData,
            'dependent_components_json' => StringHelper::escapeBackslashes(json_encode($extractedDependentComponentsData)),
            'dependency_components_json' => StringHelper::escapeBackslashes(json_encode($extractedDependencyComponentsData)),
            'components_graph' => $this->componentsGraphExtractor->extract($this->componentsGraphBuilder),
        ]);

        file_put_contents($reportsPath . '/' . $this->generateUid($component->name()) . '.html', $reportContent);
    }
}
