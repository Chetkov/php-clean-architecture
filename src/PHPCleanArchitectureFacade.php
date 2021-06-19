<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\AnalysisFinishedEvent;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\AnalysisStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\ComponentAnalysisFinishedEvent;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\ComponentAnalysisStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\EventManagerInterface;
use Chetkov\PHPCleanArchitecture\Model\Path;
use Chetkov\PHPCleanArchitecture\Model\Restrictions;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\Analysis\ComponentAnalyzer;
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

    /** @var EventManagerInterface */
    private $eventManager;

    /** @var callable */
    private $reportRenderingServiceFactory;

    /** @var bool */
    private $checkAcyclicDependenciesPrinciple;

    /** @var bool */
    private $checkStableDependenciesPrinciple;

    /** @var array<Component> */
    private $analyzedComponents;

    /** @var bool */
    private $isAnalyzePerformed = false;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $vendorBasedComponentsConfig = $config['vendor_based_components'];
        if (!empty($vendorBasedComponentsConfig['enabled']) && !empty($vendorBasedComponentsConfig['vendor_path'])) {
            $excludedVendorPaths = $vendorBasedComponentsConfig['excluded'] ?? [];
            $vendorBasedComponentsCreator = new VendorBasedComponentsCreationService($excludedVendorPaths);
            $vendorBasedComponentsCreator->create($vendorBasedComponentsConfig['vendor_path']);
        }

        $allowedState = [];
        $commonExclusionsConfig = $config['exclusions'] ?? [];
        if (!empty($commonExclusionsConfig['allowed_state']['enabled'])
            && !empty($commonExclusionsConfig['allowed_state']['storage'])
            && file_exists($commonExclusionsConfig['allowed_state']['storage'])
        ) {
            $allowedState = require $commonExclusionsConfig['allowed_state']['storage'];
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

            if (isset($allowedState[$componentConfig['name']])) {
                $restrictions->setAllowedState($allowedState[$componentConfig['name']]);
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

        $eventManagerFactory = $config['factories']['event_manager'];
        $dependenciesFinderFactory = $config['factories']['dependencies_finder'];
        $this->eventManager = $eventManagerFactory();
        $this->componentAnalyzer = new ComponentAnalyzer($dependenciesFinderFactory(), $this->eventManager);
        $this->reportRenderingServiceFactory = $config['factories']['report_rendering_service'];
    }

    /**
     * @param string $storageFile
     */
    public function allowCurrentState(string $storageFile): void
    {
        $this->analyze();

        $currentState = [];
        foreach ($this->analyzedComponents as $component) {
            foreach ($component->getDependencyComponents() as $dependencyComponent) {
                foreach ($component->getDependentUnitsOfCode($dependencyComponent) as $dependentUnitOfCode) {
                    foreach ($dependentUnitOfCode->outputDependencies($dependencyComponent) as $dependencyUnitOfCode) {
                        $currentState
                        [$component->name()]
                        [$dependencyComponent->name()]
                        [$dependentUnitOfCode->name()]
                        [$dependencyUnitOfCode->name()] = true;
                    }
                }
            }
        }

        $asCode = '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($currentState, true) . ';' . PHP_EOL;
        file_put_contents($storageFile, $asCode);
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
     * @return array<string>
     */
    public function check(): array
    {
        $this->analyze();

        $errors = [];
        foreach ($this->analyzedComponents as $component) {
            if ($this->checkAcyclicDependenciesPrinciple) {
                foreach ($component->getCyclicDependencies() as $cyclicDependenciesPath) {
                    $errors[] = 'Cyclic dependencies: ' . implode('-', array_map(static function (Component $component) {
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
                    foreach ($dependentUnitOfCode->outputDependencies($illegalDependencyComponent) as $dependencyUnitOfCode) {
                        if (!$dependentUnitOfCode->isDependencyInAllowedState($dependencyUnitOfCode)) {
                            $errorMessage .= $dependentUnitOfCode->name() . PHP_EOL;
                        }
                    }
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
            $this->eventManager->notify(new AnalysisStartedEvent());
            $totalComponents = count($this->analyzedComponents);
            foreach ($this->analyzedComponents as $index => $component) {
                $this->eventManager->notify(new ComponentAnalysisStartedEvent($index, $totalComponents, $component));
                $this->componentAnalyzer->analyze($component);
                $this->eventManager->notify(new ComponentAnalysisFinishedEvent($index, $totalComponents, $component));
            }
            $this->isAnalyzePerformed = true;
            $this->eventManager->notify(new AnalysisFinishedEvent());
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
