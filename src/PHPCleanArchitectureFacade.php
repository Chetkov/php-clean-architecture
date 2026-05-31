<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture;

use Chetkov\PHPCleanArchitecture\Model\AnalysisContext;
use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\AnalysisFinishedEvent;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\AnalysisStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\ComponentAnalysisFinishedEvent;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\ComponentAnalysisStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\DependenciesFinderInterface;
use Chetkov\PHPCleanArchitecture\Service\Analysis\SourceFileFinder;
use Chetkov\PHPCleanArchitecture\Service\Config\ConfigNormalizer;
use Chetkov\PHPCleanArchitecture\Service\EventManagerInterface;
use Chetkov\PHPCleanArchitecture\Model\Path;
use Chetkov\PHPCleanArchitecture\Model\Restrictions;
use Chetkov\PHPCleanArchitecture\Service\Analysis\ComponentAnalyzer;
use Chetkov\PHPCleanArchitecture\Service\Report\Event\ReportBuildingFinishedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\Event\ReportBuildingStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingServiceInterface;
use Chetkov\PHPCleanArchitecture\Service\Report\SpaReport\ReportDataBuilder;
use Chetkov\PHPCleanArchitecture\Service\VendorBasedComponentsCreationService;

/**
 * Class PHPCleanArchitectureFacade
 * @package Chetkov\PHPCleanArchitecture
 */
class PHPCleanArchitectureFacade
{
    /** @var ComponentAnalyzer */
    private $componentAnalyzer;

    /** @var SourceFileFinder */
    private $sourceFileFinder;

    /** @var AnalysisContext */
    private $analysisContext;

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
        $config = (new ConfigNormalizer())->normalizeConfig($config);
        $this->analysisContext = new AnalysisContext();
        $vendorBasedComponentsConfig = $this->arrayValue($config, 'vendor_based_components');
        $vendorPath = $this->stringValue($vendorBasedComponentsConfig, 'vendor_path');
        if ($this->boolValue($vendorBasedComponentsConfig, 'enabled', false) && $vendorPath !== null) {
            $vendorBasedComponentsCreator = new VendorBasedComponentsCreationService(
                $this->stringListValue($vendorBasedComponentsConfig, 'excluded'),
                $this->analysisContext
            );
            $vendorBasedComponentsCreator->create($vendorPath);
        }

        $allowedState = [];
        $allowedStateConfig = $this->arrayValue($this->arrayValue($config, 'exclusions'), 'allowed_state');
        $allowedStateStorage = $this->stringValue($allowedStateConfig, 'storage');
        if ($this->boolValue($allowedStateConfig, 'enabled', false) && $allowedStateStorage !== null && file_exists($allowedStateStorage)) {
            $loadedAllowedState = require $allowedStateStorage;
            if (is_array($loadedAllowedState)) {
                $allowedState = $loadedAllowedState;
            }
        }

        $this->analyzedComponents = [];
        $commonRestrictionsConfig = $this->arrayValue($config, 'restrictions');
        $this->checkAcyclicDependenciesPrinciple = $this->boolValue($commonRestrictionsConfig, 'check_acyclic_dependencies_principle', true);
        $this->checkStableDependenciesPrinciple = $this->boolValue($commonRestrictionsConfig, 'check_stable_dependencies_principle', true);
        foreach ($this->componentConfigs($config) as $componentConfig) {
            $rootPaths = [];
            foreach ($this->arrayListValue($componentConfig, 'roots') as $rootPathConfig) {
                $rootPath = $this->stringValue($rootPathConfig, 'path');
                $rootNamespace = $this->stringValue($rootPathConfig, 'namespace');
                if ($rootPath === null || $rootNamespace === null) {
                    continue;
                }

                $rootPaths[] = new Path(
                    $rootPath,
                    $rootNamespace,
                    $this->boolValue($rootPathConfig, 'legacy', false)
                );
            }

            $excludedPaths = [];
            foreach ($this->stringListValue($componentConfig, 'excluded') as $excludedPath) {
                $excludedPaths[] = new Path($excludedPath, '');
            }

            $restrictions = new Restrictions();
            $componentRestrictionsConfig = $this->arrayValue($componentConfig, 'restrictions');

            foreach ($this->stringListValue($componentRestrictionsConfig, 'public_elements') as $publicElement) {
                $restrictions->addPublicPath(Path::fromString($publicElement));
            }
            foreach ($this->stringListValue($componentRestrictionsConfig, 'private_elements') as $privateElement) {
                $restrictions->addPrivatePath(Path::fromString($privateElement));
            }

            foreach ($this->stringListValue($componentRestrictionsConfig, 'allowed_dependencies') as $allowedDependency) {
                $restrictions->addAllowedDependencyComponent(Component::create($this->analysisContext, $allowedDependency));
            }
            foreach ($this->stringListValue($componentRestrictionsConfig, 'forbidden_dependencies') as $forbiddenDependency) {
                $restrictions->addForbiddenDependencyComponent(Component::create($this->analysisContext, $forbiddenDependency));
            }

            $componentName = $this->stringValue($componentConfig, 'name') ?? 'component';
            $componentAllowedState = $this->allowedStateConfig($allowedState[$componentName] ?? null);
            if ($componentAllowedState !== null) {
                $restrictions->setAllowedState($componentAllowedState);
            }

            $maxAllowableDistance = $this->floatValue($componentRestrictionsConfig, 'max_allowable_distance');
            if ($maxAllowableDistance === null) {
                $maxAllowableDistance = $this->floatValue($commonRestrictionsConfig, 'max_allowable_distance');
            }
            $restrictions->setMaxAllowableDistance($maxAllowableDistance);

            $component = Component::create(
                $this->analysisContext,
                $componentName,
                $rootPaths,
                $excludedPaths,
                $restrictions
            );

            $isEnabledForAnalysis = $this->boolValue($componentConfig, 'is_analyze_enabled', true);
            if ($isEnabledForAnalysis) {
                $this->analyzedComponents[] = $component;
            } else {
                $component->excludeFromAnalyze();
            }
        }

        $factories = $this->arrayValue($config, 'factories');
        $eventManagerFactory = $this->callableValue($factories, 'event_manager');
        $dependenciesFinderFactory = $this->callableValue($factories, 'dependencies_finder');
        $reportRenderingServiceFactory = $this->callableValue($factories, 'report_rendering_service');

        $eventManager = $eventManagerFactory();
        if (!$eventManager instanceof EventManagerInterface) {
            throw new \RuntimeException('Factory "event_manager" must return EventManagerInterface.');
        }
        $this->eventManager = $eventManager;
        $this->sourceFileFinder = new SourceFileFinder();
        $dependenciesFinder = $dependenciesFinderFactory();
        if (!$dependenciesFinder instanceof DependenciesFinderInterface) {
            throw new \RuntimeException('Factory "dependencies_finder" must return DependenciesFinderInterface.');
        }
        $this->componentAnalyzer = new ComponentAnalyzer(
            $dependenciesFinder,
            $this->eventManager,
            $this->analysisContext
        );
        $this->reportRenderingServiceFactory = $reportRenderingServiceFactory;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<int, array<string, mixed>>
     */
    private function componentConfigs(array $config): array
    {
        return $this->arrayListValue($config, 'components');
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function arrayValue(array $data, string $key): array
    {
        $value = $data[$key] ?? [];

        return is_array($value) ? $this->stringKeyedArray($value) : [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, array<string, mixed>>
     */
    private function arrayListValue(array $data, string $key): array
    {
        $value = $data[$key] ?? [];
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $result[] = $this->stringKeyedArray($item);
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string>
     */
    private function stringListValue(array $data, string $key): array
    {
        $value = $data[$key] ?? [];
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static function ($item): bool {
            return is_string($item);
        }));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function stringValue(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function boolValue(array $data, string $key, bool $default): bool
    {
        $value = $data[$key] ?? null;

        return is_bool($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function floatValue(array $data, string $key): ?float
    {
        $value = $data[$key] ?? null;
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function callableValue(array $data, string $key): callable
    {
        $value = $data[$key] ?? null;
        if (!is_callable($value)) {
            throw new \RuntimeException(sprintf('Factory "%s" must be callable.', $key));
        }

        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return array<string, array<string, array<string, bool>>>|null
     */
    private function allowedStateConfig($value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $allowedState = [];
        foreach ($value as $dependencyComponentName => $dependentUnits) {
            if (!is_string($dependencyComponentName) || !is_array($dependentUnits)) {
                return null;
            }

            foreach ($dependentUnits as $sourceUnit => $dependencyUnits) {
                if (!is_string($sourceUnit) || !is_array($dependencyUnits)) {
                    return null;
                }

                foreach ($dependencyUnits as $targetUnit => $isAllowed) {
                    if (!is_string($targetUnit) || !is_bool($isAllowed)) {
                        return null;
                    }

                    $allowedState[$dependencyComponentName][$sourceUnit][$targetUnit] = $isAllowed;
                }
            }
        }

        return $allowedState;
    }

    /**
     * @param array<mixed, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function stringKeyedArray(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param array<string> $scanPaths
     *
     * @return array<string>
     */
    public function findUnmatchedFiles(array $scanPaths): array
    {
        $paths = array_map(static function (string $path) {
            return new Path($path);
        }, $scanPaths);

        $unmatchedFiles = [];
        foreach ($this->sourceFileFinder->find($paths) as $file) {
            $fullPath = $file->getRealPath();
            if (!$fullPath || $this->isExcludedByKnownComponent($fullPath) || $this->isMatchedByKnownComponent($fullPath)) {
                continue;
            }

            $unmatchedFiles[] = $fullPath;
        }

        sort($unmatchedFiles);

        return array_values(array_unique($unmatchedFiles));
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
        $storageDirectory = dirname($storageFile);
        if (!is_dir($storageDirectory) && !mkdir($storageDirectory, 0777, true) && !is_dir($storageDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $storageDirectory));
        }

        file_put_contents($storageFile, $asCode);
    }

    /**
     * @param string $reportPath
     * @param array<string> $allowedPaths
     */
    public function generateReport(string $reportPath, array $allowedPaths = []): void
    {
        $this->eventManager->notify(new ReportBuildingStartedEvent());
        $this->analyze()->filterByPaths($allowedPaths);

        $this->createReportRenderingService()->render($reportPath, ...$this->analyzedComponents);
        $this->eventManager->notify(new ReportBuildingFinishedEvent());
    }

    /**
     * @param array<string> $allowedPaths
     *
     * @return array<string, mixed>
     */
    public function buildReportData(array $allowedPaths = []): array
    {
        $this->analyze()->filterByPaths($allowedPaths);

        return (new ReportDataBuilder())->build(...$this->analyzedComponents);
    }

    /**
     * @param array<string> $allowedPaths
     *
     * @return array<string>
     */
    public function check(array $allowedPaths = []): array
    {
        $this->analyze()->filterByPaths($allowedPaths);

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
                        $errors[] = "Dependency {$dependentComponent->name()} (instability: $dependentComponentInstabilityRate) -> {$component->name()} (instability: $componentInstabilityRate) violates the SDP (stable dependencies principle)";
                    }
                }
            }

            foreach ($component->getIllegalDependencyComponents() as $illegalDependencyComponent) {
                $errorMessage = "\"{$component->name()}\" can not depend on \"{$illegalDependencyComponent->name()}\"! Dependent elements:" . PHP_EOL;
                foreach ($component->getDependentUnitsOfCode($illegalDependencyComponent) as $dependentUnitOfCode) {
                    foreach ($dependentUnitOfCode->outputDependencies($illegalDependencyComponent) as $dependencyUnitOfCode) {
                        if (!$dependentUnitOfCode->isDependencyInAllowedState($dependencyUnitOfCode)) {
                            $errorMessage .= $dependentUnitOfCode->name() . ' -> ' . $dependencyUnitOfCode->name() . PHP_EOL;
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

    /**
     * @return $this
     */
    private function analyze(): self
    {
        if (!$this->isAnalyzePerformed) {
            $this->eventManager->notify(new AnalysisStartedEvent());
            $totalComponents = count($this->analyzedComponents);
            foreach (array_values($this->analyzedComponents) as $index => $component) {
                $this->eventManager->notify(new ComponentAnalysisStartedEvent($index, $totalComponents, $component));
                $this->componentAnalyzer->analyze($component);
                $this->eventManager->notify(new ComponentAnalysisFinishedEvent($index, $totalComponents, $component));
            }
            $this->isAnalyzePerformed = true;
            $this->eventManager->notify(new AnalysisFinishedEvent());
        }

        return $this;
    }

    /**
     * @param array<string> $allowedPaths
     *
     * @return void
     */
    private function filterByPaths(array $allowedPaths): void
    {
        if ($allowedPaths === []) {
            return;
        }

        $allowedPaths = array_map(static function (string $path) {
            return new Path($path);
        }, $allowedPaths);

        foreach ($this->analyzedComponents as $component) {
            $component->filterByPaths($allowedPaths);
        }

        foreach ($this->analyzedComponents as $index => $component) {
            if (empty($component->getDependencyComponents()) && empty($component->getDependentComponents())) {
                unset($this->analyzedComponents[$index]);
            }
        }
    }

    /**
     * @return ReportRenderingServiceInterface
     */
    private function createReportRenderingService(): ReportRenderingServiceInterface
    {
        $reportRenderingServiceFactory = $this->reportRenderingServiceFactory;
        $reportRenderingService = $reportRenderingServiceFactory($this->eventManager);
        if (!$reportRenderingService instanceof ReportRenderingServiceInterface) {
            throw new \RuntimeException('Factory "report_rendering_service" must return ReportRenderingServiceInterface.');
        }

        return $reportRenderingService;
    }

    private function isMatchedByKnownComponent(string $fullPath): bool
    {
        foreach ($this->analysisContext->components() as $component) {
            foreach ($component->rootPaths() as $rootPath) {
                if ($rootPath->isPartOfPath($fullPath)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isExcludedByKnownComponent(string $fullPath): bool
    {
        foreach ($this->analysisContext->components() as $component) {
            if ($component->isExcluded($fullPath)) {
                return true;
            }
        }

        return false;
    }
}
