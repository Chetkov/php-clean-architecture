<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\IndexPage\ComponentExtractor;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\ComponentsGraphExtractor;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class IndexPageRenderingService
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport
 */
class IndexPageRenderingService
{
    /** @var Environment */
    private $twig;

    /** @var ObjectsGraphBuilder */
    private $componentsGraphBuilder;

    /** @var ComponentExtractor */
    private $componentExtractor;

    /** @var ComponentsGraphExtractor */
    private $componentsGraphExtractor;

    public function __construct()
    {
        $templatesLoader = new FilesystemLoader(__DIR__ . '/Template/');
        $this->twig = new Environment($templatesLoader);
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

        file_put_contents($reportsPath . '/' . 'index.html', $this->twig->render('index.twig', [
            'components_graph' => $this->componentsGraphExtractor->extract($this->componentsGraphBuilder),
            'components' => $extractedComponentsData,
        ]));
    }
}
