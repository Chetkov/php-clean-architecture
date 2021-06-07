<?php

namespace Chetkov\PHPCleanArchitecture;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Model\Path;
use Chetkov\PHPCleanArchitecture\Model\Restrictions;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\ComponentAnalyzer;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingServiceInterface;
use Chetkov\PHPCleanArchitecture\Service\VendorBasedComponentsCreationService;

/**
 * Class PHPCleanArchitectureFacade
 * @package Chetkov\PHPCleanArchitecture
 */
class PHPCleanArchitectureFacade
{
    /** @var ComponentAnalyzer */
    private $componentAnalyzer;

    /** @var callable */
    private $reportRenderingServiceFactory;

    /** @var bool */
    private $checkAcyclicDependenciesPrinciple;

    /** @var bool */
    private $checkStableDependenciesPrinciple;

    /** @var Component[] */
    private $analyzedComponents;

    /** @var bool */
    private $isAnalyzePerformed = false;

    /**
     * PHPCleanArchitectureFacade constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $vendorBasedComponentsConfig = $config['vendor_based_components'];
        if (!empty($vendorBasedComponentsConfig['enabled']) && !empty($vendorBasedComponentsConfig['vendor_path'])) {
            $excludedVendorPaths = $vendorBasedComponentsConfig['excluded'] ?? [];
            $vendorBasedComponentsCreator = new VendorBasedComponentsCreationService($excludedVendorPaths);
            $vendorBasedComponentsCreator->create($vendorBasedComponentsConfig['vendor_path']);
        }

        $this->analyzedComponents = [];
        $commonRestrictionsConfig = $config['restrictions'] ?? [];
        $this->checkAcyclicDependenciesPrinciple = $commonRestrictionsConfig['check_acyclic_dependencies_principle'] ?? true;
        $this->checkStableDependenciesPrinciple = $commonRestrictionsConfig['check_stable_dependencies_principle'] ?? true;
        foreach ($config['components'] as $componentConfig) {
            $rootPaths = [];
            foreach ($componentConfig['roots'] ?? [] as $rootPathConfig) {
                $rootPaths[] = new Path($rootPathConfig['path'], $rootPathConfig['namespace']);
            }

            $excludedPaths = [];
            foreach ($componentConfig['excluded'] ?? [] as $excludedPath) {
                $excludedPaths[] = new Path($excludedPath, '');
            }

            $restrictions = new Restrictions();
            $componentRestrictionsConfig = $componentConfig['restrictions'] ?? [];

            foreach ($componentRestrictionsConfig['public_elements'] ?? [] as $publicElement) {
                $restrictions->addPublicUnitOfCode(UnitOfCode::create($publicElement));
            }
            foreach ($componentRestrictionsConfig['private_elements'] ?? [] as $privateElement) {
                $restrictions->addPrivateUnitOfCode(UnitOfCode::create($privateElement));
            }

            foreach ($componentRestrictionsConfig['allowed_dependencies'] ?? [] as $allowedDependency) {
                $restrictions->addAllowedDependencyComponent(Component::create($allowedDependency));
            }
            foreach ($componentRestrictionsConfig['forbidden_dependencies'] ?? [] as $forbiddenDependency) {
                $restrictions->addForbiddenDependencyComponent(Component::create($forbiddenDependency));
            }

            $maxAllowableDistance = $componentRestrictionsConfig['max_allowable_distance'] ?? null;
            if ($maxAllowableDistance === null) {
                $maxAllowableDistance = $commonRestrictionsConfig['max_allowable_distance'] ?? null;
            }
            $restrictions->setMaxAllowableDistance($maxAllowableDistance);

            $component = Component::create(
                $componentConfig['name'],
                $rootPaths,
                $excludedPaths,
                $restrictions
            );

            $isEnabledForAnalysis = $componentConfig['is_analyze_enabled'] ?? true;
            if ($isEnabledForAnalysis) {
                $this->analyzedComponents[] = $component;
            } else {
                $component->excludeFromAnalyze();
            }
        }

        $loggerFactory = $config['factories']['logger'];
        $dependenciesFinderFactory = $config['factories']['dependencies_finder'];
        $this->componentAnalyzer = new ComponentAnalyzer($dependenciesFinderFactory(), $loggerFactory());
        $this->reportRenderingServiceFactory = $config['factories']['report_rendering_service'];
    }

    /**
     * @param string $path
     */
    public function generateReport(string $path): void
    {
        $this->analyze();

        $this->createReportRenderingService()->render($path, ...$this->analyzedComponents);
    }

    /**
     * @return string[]
     */
    public function check(): array
    {
        $this->analyze();

        $errors = [];
        foreach ($this->analyzedComponents as $component) {
            if ($this->checkAcyclicDependenciesPrinciple) {
                foreach ($component->getCyclicDependencies() as $cyclicDependenciesPath) {
                    $errors[] = 'Cyclic dependencies: ' . implode('-', array_map(function (Component $component) {
                            return $component->name();
                        }, $cyclicDependenciesPath)) . ' violates the ADP (acyclic dependencies principle)';
                }
            }

            if ($this->checkStableDependenciesPrinciple) {
                foreach ($component->getDependentComponents() as $dependentComponent) {
                    $dependentComponentInstabilityRate = $dependentComponent->calculateInstabilityRate();
                    $componentInstabilityRate = $component->calculateInstabilityRate();
                    if ($dependentComponentInstabilityRate < $componentInstabilityRate) {
                        $errors[] = "Dependency {$dependentComponent->name()}(instability: $dependentComponentInstabilityRate) -> {$component->name()}(instability: $componentInstabilityRate) violates the SDP (stable dependencies principle)";
                    }
                }
            }

            foreach ($component->getIllegalDependencyComponents() as $illegalDependencyComponent) {
                $errorMessage = "\"{$component->name()}\" can not depend on \"{$illegalDependencyComponent->name()}\"! Dependent elements:" . PHP_EOL;
                foreach ($component->getDependentUnitsOfCode($illegalDependencyComponent) as $dependentUnitOfCode) {
                    $errorMessage .= $dependentUnitOfCode->name() . PHP_EOL;
                }
                $errors[] = $errorMessage;
            }

            foreach ($component->getIllegalDependencyUnitsOfCode(true) as $illegalDependency) {
                $errorMessage = "\"{$component->name()}\" can not depend on NON PUBLIC \"{$illegalDependency->name()}\"! Dependent elements:" . PHP_EOL;
                foreach ($illegalDependency->inputDependencies($component) as $dependentUnitOfCode) {
                    $errorMessage .= $dependentUnitOfCode->name() . PHP_EOL;
                }
                $errors[] = $errorMessage;
            }

            if ($distanceRateOverage = $component->calculateDistanceRateOverage()) {
                $errors[] = "\"{$component->name()}\" exceeded the maximum allowable distance by $distanceRateOverage. Current value {$component->calculateDistanceRate()}";
            }
        }

        return $errors;
    }

    private function analyze(): void
    {
        if (!$this->isAnalyzePerformed) {
            foreach ($this->analyzedComponents as $component) {
                $this->componentAnalyzer->analyze($component);
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
