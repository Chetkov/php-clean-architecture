<?php

namespace Chetkov\PHPCleanArchitecture;

use Chetkov\PHPCleanArchitecture\Model\Module;
use Chetkov\PHPCleanArchitecture\Model\Path;
use Chetkov\PHPCleanArchitecture\Model\Restrictions;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\ModuleAnalyzer;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingServiceInterface;
use Chetkov\PHPCleanArchitecture\Service\VendorBasedModulesCreationService;

/**
 * Class PHPCleanArchitectureFacade
 * @package Chetkov\PHPCleanArchitecture
 */
class PHPCleanArchitectureFacade
{
    /** @var ModuleAnalyzer */
    private $moduleAnalyzer;

    /** @var callable */
    private $reportRenderingServiceFactory;

    /** @var bool */
    private $checkAcyclicDependenciesPrinciple;

    /** @var bool */
    private $checkStableDependenciesPrinciple;

    /** @var Module[] */
    private $modules;

    /** @var bool */
    private $isAnalyzePerformed = false;

    /**
     * PHPCleanArchitectureFacade constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $vendorBasedModulesConfig = $config['vendor_based_modules'];
        if (!empty($vendorBasedModulesConfig['enabled']) && !empty($vendorBasedModulesConfig['vendor_path'])) {
            $excludedVendorPaths = $vendorBasedModulesConfig['excluded'] ?? [];
            $vendorBasedModulesCreator = new VendorBasedModulesCreationService($excludedVendorPaths);
            $vendorBasedModulesCreator->create($vendorBasedModulesConfig['vendor_path']);
        }

        $this->modules = [];
        $commonRestrictionsConfig = $config['restrictions'] ?? [];
        $this->checkAcyclicDependenciesPrinciple = $commonRestrictionsConfig['check_acyclic_dependencies_principle'] ?? true;
        $this->checkStableDependenciesPrinciple = $commonRestrictionsConfig['check_stable_dependencies_principle'] ?? true;
        foreach ($config['modules'] as $moduleConfig) {
            $rootPaths = [];
            foreach ($moduleConfig['roots'] ?? [] as $rootPathConfig) {
                $rootPaths[] = new Path($rootPathConfig['path'], $rootPathConfig['namespace']);
            }

            $excludedPaths = [];
            foreach ($moduleConfig['excluded'] ?? [] as $excludedPath) {
                $excludedPaths[] = new Path($excludedPath, '');
            }

            $restrictions = new Restrictions();
            $moduleRestrictionsConfig = $moduleConfig['restrictions'] ?? [];

            foreach ($moduleRestrictionsConfig['public_elements'] ?? [] as $publicElement) {
                $restrictions->addPublicUnitOfCode(UnitOfCode::create($publicElement));
            }
            foreach ($moduleRestrictionsConfig['private_elements'] ?? [] as $privateElement) {
                $restrictions->addPrivateUnitOfCode(UnitOfCode::create($privateElement));
            }

            foreach ($moduleRestrictionsConfig['allowed_dependencies'] ?? [] as $allowedDependency) {
                $restrictions->addAllowedDependencyModule(Module::create($allowedDependency));
            }
            foreach ($moduleRestrictionsConfig['forbidden_dependencies'] ?? [] as $forbiddenDependency) {
                $restrictions->addForbiddenDependencyModule(Module::create($forbiddenDependency));
            }

            $maxAllowableDistance = $moduleRestrictionsConfig['max_allowable_distance'] ?? null;
            if ($maxAllowableDistance === null) {
                $maxAllowableDistance = $commonRestrictionsConfig['max_allowable_distance'] ?? null;
            }
            $restrictions->setMaxAllowableDistance($maxAllowableDistance);

            $this->modules[] = Module::create(
                $moduleConfig['name'],
                $rootPaths,
                $excludedPaths,
                $restrictions
            );
        }

        $loggerFactory = $config['factories']['logger'];
        $dependenciesFinderFactory = $config['factories']['dependencies_finder'];
        $this->moduleAnalyzer = new ModuleAnalyzer($dependenciesFinderFactory(), $loggerFactory());
        $this->reportRenderingServiceFactory = $config['factories']['report_rendering_service'];
    }

    /**
     * @param string $path
     */
    public function generateReport(string $path): void
    {
        $this->analyze();

        $this->createReportRenderingService()->render($path, ...$this->modules);
    }

    /**
     * @return string[]
     */
    public function check(): array
    {
        $this->analyze();

        $errors = [];
        foreach ($this->modules as $module) {
            if ($this->checkAcyclicDependenciesPrinciple) {
                foreach ($module->getCyclicDependencies() as $cyclicDependenciesPath) {
                    $errors[] = 'Cyclic dependencies: ' . implode('-', array_map(function (Module $module) {
                            return $module->name();
                        }, $cyclicDependenciesPath)) . ' violates the ADP (acyclic dependencies principle)';
                }
            }

            if ($this->checkStableDependenciesPrinciple) {
                foreach ($module->getDependentModules() as $dependentModule) {
                    $dependentModuleInstabilityRate = $dependentModule->calculateInstabilityRate();
                    $moduleInstabilityRate = $module->calculateInstabilityRate();
                    if ($dependentModuleInstabilityRate < $moduleInstabilityRate) {
                        $errors[] = "Dependency {$dependentModule->name()}(instability: $dependentModuleInstabilityRate) -> {$module->name()}(instability: $moduleInstabilityRate) violates the SDP (stable dependencies principle)";
                    }
                }
            }

            foreach ($module->getIllegalDependencyModules() as $illegalDependencyModule) {
                $errorMessage = "\"{$module->name()}\" can not depend on \"{$illegalDependencyModule->name()}\"! Dependent elements:" . PHP_EOL;
                foreach ($module->getDependentUnitsOfCode($illegalDependencyModule) as $dependentUnitOfCode) {
                    $errorMessage .= $dependentUnitOfCode->name() . PHP_EOL;
                }
                $errors[] = $errorMessage;
            }

            foreach ($module->getIllegalDependencyUnitsOfCode(true) as $illegalDependency) {
                $errorMessage = "\"{$module->name()}\" can not depend on NON PUBLIC \"{$illegalDependency->name()}\"! Dependent elements:" . PHP_EOL;
                foreach ($illegalDependency->inputDependencies($module) as $dependentUnitOfCode) {
                    $errorMessage .= $dependentUnitOfCode->name() . PHP_EOL;
                }
                $errors[] = $errorMessage;
            }

            if ($distanceRateOverage = $module->calculateDistanceRateOverage()) {
                $errors[] = "\"{$module->name()}\" exceeded the maximum allowable distance by $distanceRateOverage. Current value {$module->calculateDistanceRate()}";
            }
        }

        return $errors;
    }

    private function analyze(): void
    {
        if (!$this->isAnalyzePerformed) {
            foreach ($this->modules as $module) {
                $this->moduleAnalyzer->analyze($module);
            }
            $this->isAnalyzePerformed = true;
        }
    }

    /**
     * @return ReportRenderingServiceInterface
     */
    private function createReportRenderingService(): ReportRenderingServiceInterface
    {
        $reportRenderingServiceFactory = $this->reportRenderingServiceFactory;
        return $reportRenderingServiceFactory();
    }
}
