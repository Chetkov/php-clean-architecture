<?php

namespace Chetkov\PHPCleanArchitecture;

use Chetkov\ConsoleLogger\ConsoleLoggerFactory;
use Chetkov\ConsoleLogger\LoggerConfig;
use Chetkov\ConsoleLogger\StyledLogger\LoggerStyle;
use Chetkov\ConsoleLogger\StyledLogger\StyledLoggerDecorator;
use Chetkov\PHPCleanArchitecture\Model\Module;
use Chetkov\PHPCleanArchitecture\Model\Path;
use Chetkov\PHPCleanArchitecture\Model\Restrictions;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\AggregationDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsingDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\ReflectionDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\ModuleAnalyzer;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingService;
use Chetkov\PHPCleanArchitecture\Service\VendorBasedModulesCreationService;

/**
 * Class PHPCleanArchitectureFacade
 * @package Chetkov\PHPCleanArchitecture
 */
class PHPCleanArchitectureFacade
{
    /** @var ModuleAnalyzer */
    private $moduleAnalyzer;

    /** @var Module[] */
    private $modules;

    /** @var bool */
    private $isAnalyzePerformed = false;

    /** @var bool */
    private $detectCyclicDependencies;

    /**
     * PHPCleanArchitectureFacade constructor.
     * @param array $config
     * @param ModuleAnalyzer|null $moduleAnalyzer
     */
    public function __construct(array $config, ?ModuleAnalyzer $moduleAnalyzer = null)
    {
        $vendorBasedModulesConfig = $config['vendor_based_modules'];
        if (!empty($vendorBasedModulesConfig['enabled']) && !empty($vendorBasedModulesConfig['vendor_path'])) {
            $excludedVendorPaths = $vendorBasedModulesConfig['excluded'] ?? [];
            $vendorBasedModulesCreator = new VendorBasedModulesCreationService($excludedVendorPaths);
            $vendorBasedModulesCreator->create($vendorBasedModulesConfig['vendor_path']);
        }

        $this->modules = [];
        $commonRestrictionsConfig = $config['restrictions'] ?? [];
        $this->detectCyclicDependencies = $commonRestrictionsConfig['detect_cyclic_dependencies'] ?? true;
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

        if (!$moduleAnalyzer) {
            $loggerConfig = new LoggerConfig();
            $loggerConfig
                ->setIsShowDateTime(true)
                ->setIsShowLevel(false)
                ->setIsShowData(false)
                ->setDateTimeFormat('H:i:s')
                ->setFieldDelimiter(' :: ');
            $logger = new StyledLoggerDecorator(
                ConsoleLoggerFactory::create($loggerConfig),
                new LoggerStyle()
            );

            $moduleAnalyzer = new ModuleAnalyzer(
                new AggregationDependenciesFinder(...[
                    new ReflectionDependenciesFinder(),
                    new CodeParsingDependenciesFinder(),
                ]),
                $logger
            );
        }
        $this->moduleAnalyzer = $moduleAnalyzer;
    }

    /**
     * @param string $path
     */
    public function generateReport(string $path): void
    {
        $this->analyze();

        $reportRenderingService = new ReportRenderingService();
        $reportRenderingService->render($path, ...$this->modules);
    }

    /**
     * @return string[]
     */
    public function check(): array
    {
        $this->analyze();

        $errors = [];
        foreach ($this->modules as $module) {
            if ($this->detectCyclicDependencies) {
                foreach ($module->getCyclicDependencies() as $cyclicDependenciesPath) {
                    $errors[] = 'Cyclic dependencies: ' . implode('-', array_map(function (Module $module) {
                        return $module->name();
                    }, $cyclicDependenciesPath));
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
}
