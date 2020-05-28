<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport;

use Chetkov\PHPCleanArchitecture\Helper\StringHelper;
use Chetkov\PHPCleanArchitecture\Model\Module;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\ModulePage\DependencyModuleExtractor;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\ModulesGraphExtractor;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class ModulePageRenderingService
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport
 */
class ModulePageRenderingService
{
    use UidGenerator;

    /** @var Environment */
    private $twig;

    /** @var ObjectsGraphBuilder */
    private $modulesGraphBuilder;

    /** @var DependencyModuleExtractor */
    private $dependencyModuleExtractor;

    /** @var ModulesGraphExtractor */
    private $modulesGraphExtractor;

    /**
     * ModulePageRenderingService constructor.
     */
    public function __construct()
    {
        $templatesLoader = new FilesystemLoader(__DIR__ . '/Template/');
        $this->twig = new Environment($templatesLoader);
        $this->modulesGraphBuilder = new ObjectsGraphBuilder();
        $this->dependencyModuleExtractor = new DependencyModuleExtractor();
        $this->modulesGraphExtractor = new ModulesGraphExtractor();
    }

    /**
     * @param string $reportsPath
     * @param Module $module
     * @param Module ...$processedModules
     */
    public function render(string $reportsPath, Module $module, Module ...$processedModules): void
    {
        $this->modulesGraphBuilder->reset();

        $extractedDependentModulesData = [];
        foreach ($module->getDependentModules() as $dependentModule) {
            $this->modulesGraphBuilder->addEdge($dependentModule, $module);
            $extractedDependentModulesData[] = $this->dependencyModuleExtractor->extract($dependentModule, $module, $processedModules);
        }

        $extractedDependencyModulesData = [];
        foreach ($module->getDependencyModules() as $dependencyModule) {
            if ($dependencyModule->isGlobal() || $dependencyModule->isPrimitives()) {
                continue;
            }
            $this->modulesGraphBuilder->addEdge($module, $dependencyModule);
            $extractedDependencyModulesData[] = $this->dependencyModuleExtractor->extract($dependencyModule, $module, $processedModules, true);
        }

        $moduleName = $this->generateUid($module->name());
        file_put_contents($reportsPath . '/' . $moduleName . '.html', $this->twig->render('module-info.twig', [
            'name' => $module->name(),
            'primitiveness_rate' => $module->calculatePrimitivenessRate(),
            'abstractness_rate' => $module->calculateAbstractnessRate(),
            'instability_rate' => $module->calculateInstabilityRate(),
            'distance_rate' => $module->calculateDistanceRate(),
            'dependent_modules' => $extractedDependentModulesData,
            'dependency_modules' => $extractedDependencyModulesData,
            'dependent_modules_json' => StringHelper::escapeBackslashes(json_encode($extractedDependentModulesData)),
            'dependency_modules_json' => StringHelper::escapeBackslashes(json_encode($extractedDependencyModulesData)),
            'modules_graph' => $this->modulesGraphExtractor->extract($this->modulesGraphBuilder),
        ]));
    }
}
