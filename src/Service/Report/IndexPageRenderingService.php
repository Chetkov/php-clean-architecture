<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report;

use Chetkov\PHPCleanArchitecture\Model\Module;
use Chetkov\PHPCleanArchitecture\Service\Report\Extractor\IndexPage\ModuleExtractor;
use Chetkov\PHPCleanArchitecture\Service\Report\Extractor\ModulesGraphExtractor;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class IndexPageRenderingService
 * @package Chetkov\PHPCleanArchitecture\Service\Report
 */
class IndexPageRenderingService
{
    /** @var Environment */
    private $twig;

    /** @var ObjectsGraphBuilder */
    private $modulesGraphBuilder;

    /** @var ModuleExtractor */
    private $moduleExtractor;

    /** @var ModulesGraphExtractor */
    private $modulesGraphExtractor;

    public function __construct()
    {
        $templatesLoader = new FilesystemLoader(__DIR__ . '/Template/');
        $this->twig = new Environment($templatesLoader);
        $this->modulesGraphBuilder = new ObjectsGraphBuilder();
        $this->moduleExtractor = new ModuleExtractor();
        $this->modulesGraphExtractor = new ModulesGraphExtractor();
    }

    /**
     * @param string $reportsPath
     * @param Module ...$modules
     */
    public function render(string $reportsPath, Module ...$modules): void
    {
        $extractedModulesData = [];
        $this->modulesGraphBuilder->reset();

        foreach ($modules as $module) {
            $extractedModulesData[] = $this->moduleExtractor->extract($module);
            foreach ($module->getDependentModules() as $dependentModule) {
                $this->modulesGraphBuilder->addEdge($dependentModule, $module);
            }
            foreach ($module->getDependencyModules() as $dependencyModule) {
                if ($dependencyModule->isPrimitives() || $dependencyModule->isGlobal()) {
                    continue;
                }
                $this->modulesGraphBuilder->addEdge($module, $dependencyModule);
            }
        }

        file_put_contents($reportsPath . '/' . 'index.html', $this->twig->render('index.twig', [
            'modules_graph' => $this->modulesGraphExtractor->extract($this->modulesGraphBuilder),
            'modules' => $extractedModulesData,
        ]));
    }
}
