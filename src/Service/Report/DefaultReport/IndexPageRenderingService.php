<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\IndexPage\ComponentExtractor;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\ComponentsGraphExtractor;
use Chetkov\PHPCleanArchitecture\Service\Report\TemplateRendererInterface;

/**
 * Class IndexPageRenderingService
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport
 */
class IndexPageRenderingService
{
    /** @var TemplateRendererInterface */
    private $templateRenderer;

    /** @var ObjectsGraphBuilder */
    private $componentsGraphBuilder;

    /** @var ComponentExtractor */
    private $componentExtractor;

    /** @var ComponentsGraphExtractor */
    private $componentsGraphExtractor;

    /**
     * @param TemplateRendererInterface $templateRenderer
     */
    public function __construct(TemplateRendererInterface $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
        $this->componentsGraphBuilder = new ObjectsGraphBuilder();
        $this->componentExtractor = new ComponentExtractor();
        $this->componentsGraphExtractor = new ComponentsGraphExtractor();
    }

    /**
     * @param string $reportsPath
     * @param Component ...$components
     */
    public function render(string $reportsPath, Component ...$components): void
    {
        $extractedComponentsData = [];
        $this->componentsGraphBuilder->reset();

        foreach ($components as $component) {
            $extractedComponentsData[] = $this->componentExtractor->extract($component);
            foreach ($component->getDependentComponents() as $dependentComponent) {
                $this->componentsGraphBuilder->addEdge($dependentComponent, $component);
            }
            foreach ($component->getDependencyComponents() as $dependencyComponent) {
                if ($dependencyComponent->isPrimitives() || $dependencyComponent->isGlobal()) {
                    continue;
                }
                $this->componentsGraphBuilder->addEdge($component, $dependencyComponent);
            }
        }

        $reportContent = $this->templateRenderer->render('index.twig', [
            'components_graph' => $this->componentsGraphExtractor->extract($this->componentsGraphBuilder),
            'components' => $extractedComponentsData,
        ]);

        file_put_contents($reportsPath . '/' . 'index.html', $reportContent);
    }
}
