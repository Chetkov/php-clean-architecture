<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests;

use Chetkov\PHPCleanArchitecture\PHPCleanArchitectureFacade;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\Ast\AstDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\EventManagerInterface;
use Chetkov\PHPCleanArchitecture\Service\Report\SpaReport\ReportRenderingService;
use Chetkov\PHPCleanArchitecture\Tests\Support\NullEventManager;
use Chetkov\PHPCleanArchitecture\Tests\Support\NullReportRenderingService;
use PHPUnit\Framework\TestCase;

final class PHPCleanArchitectureFacadeTest extends TestCase
{
    public function testCheckReportsForbiddenComponentDependency(): void
    {
        $facade = new PHPCleanArchitectureFacade($this->createConfig([
            'component-a' => [
                'allowed_dependencies' => ['component-a'],
            ],
        ]));

        $errors = $facade->check();

        self::assertCount(1, $errors);
        self::assertStringContainsString(
            '"component-a" can not depend on "component-b"!',
            $errors[0]
        );
        self::assertStringContainsString(
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA\AClass -> ' .
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentB\BClass',
            $errors[0]
        );
    }

    public function testCheckReportsDependencyOnPrivateElement(): void
    {
        $privatePath = __DIR__ . '/Fixtures/Project/ComponentB/Internal';
        $facade = new PHPCleanArchitectureFacade($this->createConfig([
            'component-a' => [
                'allowed_dependencies' => ['component-a', 'component-b'],
            ],
            'component-b' => [
                'private_elements' => [$privatePath],
            ],
        ]));

        $errors = $facade->check([__DIR__ . '/Fixtures/Project/ComponentA/UsesPrivateClass.php']);

        self::assertCount(1, $errors);
        self::assertStringContainsString(
            '"component-a" can not depend on NON PUBLIC ' .
            '"Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentB\Internal\PrivateClass"!',
            $errors[0]
        );
        self::assertStringContainsString(
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA\UsesPrivateClass',
            $errors[0]
        );
    }

    public function testReportDoesNotCreatePrivateViolationForInternalDependency(): void
    {
        $reportPath = sys_get_temp_dir() . '/phpca-internal-private-report-' . bin2hex(random_bytes(8));
        $config = $this->createConfig([
            'component-a' => [
                'allowed_dependencies' => ['component-a', 'component-b'],
                'private_elements' => [__DIR__ . '/Fixtures/Project/ComponentA/Internal'],
            ],
        ]);
        $config['reports_dir'] = $reportPath;
        $config['factories']['report_rendering_service'] = static function (
            EventManagerInterface $eventManager
        ): ReportRenderingService {
            return new ReportRenderingService($eventManager);
        };

        $facade = new PHPCleanArchitectureFacade($config);

        self::assertSame([], $facade->check());

        $facade->generateReport($reportPath);

        $reportData = json_decode((string) file_get_contents($reportPath . '/report.json'), true);
        self::assertIsArray($reportData);

        $internalDependency = $this->findDependency(
            $reportData,
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA\UsesOwnPrivateClass',
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA\Internal\InternalPrivateClass'
        );
        self::assertTrue($internalDependency['isInternal']);
        self::assertFalse($internalDependency['isTargetPublic']);
        self::assertSame(0, $reportData['summary']['violations']);
        self::assertSame(0, $reportData['summary']['activeViolations']);
        $this->assertNoViolation($reportData, $internalDependency['id'], 'private-unit');

        $componentA = $this->findComponent($reportData, 'component-a');
        self::assertFalse($componentA['health']['hasPrivateApiDependencies']);

        $this->removeDirectory($reportPath);
    }

    public function testAllowedCurrentStateSuppressesExistingDependencyErrors(): void
    {
        $storage = tempnam(sys_get_temp_dir(), 'phpca-allowed-state-');
        self::assertIsString($storage);

        $facade = new PHPCleanArchitectureFacade($this->createConfig([
            'component-a' => [
                'allowed_dependencies' => ['component-a'],
            ],
        ]));

        $facade->allowCurrentState($storage);

        $facade = new PHPCleanArchitectureFacade($this->createConfig(
            [
                'component-a' => [
                    'allowed_dependencies' => ['component-a'],
                ],
            ],
            $storage
        ));

        self::assertSame([], $facade->check());

        unlink($storage);
    }

    public function testEmptyNamespaceRootIsAnalyzed(): void
    {
        $config = $this->createEmptyNamespaceScriptConfig();
        $reportData = (new PHPCleanArchitectureFacade($config))->buildReportData();

        self::assertSame(1, $reportData['summary']['components']);
        self::assertSame(1, $reportData['summary']['units']);
        self::assertSame('phpca-tool', $reportData['units'][0]['name']);
    }

    public function testGenerateReportCreatesSpaReportWithJsonData(): void
    {
        $reportPath = sys_get_temp_dir() . '/phpca-spa-report-' . bin2hex(random_bytes(8));
        $config = $this->createConfig([
            'component-a' => [
                'allowed_dependencies' => ['component-a'],
            ],
            'component-b' => [
                'private_elements' => [__DIR__ . '/Fixtures/Project/ComponentB/Internal'],
            ],
        ]);
        $config['reports_dir'] = $reportPath;
        $config['factories']['report_rendering_service'] = static function (
            EventManagerInterface $eventManager
        ): ReportRenderingService {
            return new ReportRenderingService($eventManager);
        };

        $facade = new PHPCleanArchitectureFacade($config);
        $facade->generateReport($reportPath);

        self::assertFileExists($reportPath . '/index.html');
        self::assertFileExists($reportPath . '/report.json');
        $indexHtml = (string) file_get_contents($reportPath . '/index.html');
        self::assertSame(
            1,
            preg_match('/<script id="phpca-report-data" type="application\/json">(.+)<\/script>/', $indexHtml, $matches)
        );
        $embeddedJson = $matches[1] ?? null;
        self::assertIsString($embeddedJson);

        $reportData = json_decode((string) file_get_contents($reportPath . '/report.json'), true);
        self::assertIsArray($reportData);
        $embeddedReportData = json_decode($embeddedJson, true);
        self::assertIsArray($embeddedReportData);
        self::assertSame($reportData['summary'], $embeddedReportData['summary']);
        self::assertSame(4, $reportData['schemaVersion']);
        self::assertSame(2, $reportData['summary']['components']);
        self::assertGreaterThanOrEqual(3, $reportData['summary']['units']);
        self::assertGreaterThanOrEqual(1, $reportData['summary']['dependencies']);
        self::assertGreaterThanOrEqual(1, $reportData['summary']['activeViolations']);
        self::assertNotEmpty($reportData['components']);
        self::assertNotEmpty($reportData['units']);
        self::assertNotEmpty($reportData['dependencies']);
        self::assertNotEmpty($reportData['violations']);
        self::assertArrayHasKey('externalComponents', $reportData);
        self::assertArrayHasKey('externalUnits', $reportData);
        self::assertArrayHasKey('componentEdges', $reportData);
        self::assertNotEmpty($reportData['componentEdges']);
        self::assertArrayNotHasKey('componentName', $reportData['units'][0]);
        self::assertTrue(array_is_list($reportData['dependencies'][0]));
        self::assertCount(5, $reportData['dependencies'][0]);
        self::assertArrayHasKey('sourceUnitCount', $reportData['componentEdges'][0]);
        self::assertArrayHasKey('targetUnitCount', $reportData['componentEdges'][0]);

        $this->removeDirectory($reportPath);
    }

    public function testReportMarksAllowedStateOnlyForSuppressedViolations(): void
    {
        $storage = tempnam(sys_get_temp_dir(), 'phpca-allowed-state-');
        self::assertIsString($storage);
        $reportPath = sys_get_temp_dir() . '/phpca-spa-report-' . bin2hex(random_bytes(8));

        $restrictions = [
            'component-a' => [
                'allowed_dependencies' => ['component-a', 'component-b'],
            ],
            'component-b' => [
                'private_elements' => [__DIR__ . '/Fixtures/Project/ComponentB/Internal'],
            ],
        ];

        (new PHPCleanArchitectureFacade($this->createConfig($restrictions)))->allowCurrentState($storage);

        $config = $this->createConfig($restrictions, $storage);
        $config['reports_dir'] = $reportPath;
        $config['factories']['report_rendering_service'] = static function (
            EventManagerInterface $eventManager
        ): ReportRenderingService {
            return new ReportRenderingService($eventManager);
        };

        (new PHPCleanArchitectureFacade($config))->generateReport($reportPath);

        $reportData = json_decode((string) file_get_contents($reportPath . '/report.json'), true);
        self::assertIsArray($reportData);

        $publicAllowedDependency = $this->findDependencyByTarget(
            $reportData,
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentB\BClass'
        );
        self::assertTrue($publicAllowedDependency['isComponentAllowed']);
        self::assertTrue($publicAllowedDependency['isTargetPublic']);
        self::assertFalse($publicAllowedDependency['isAllowedState']);

        $privateAllowedStateDependency = $this->findDependencyByTarget(
            $reportData,
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentB\Internal\PrivateClass'
        );
        self::assertTrue($privateAllowedStateDependency['isComponentAllowed']);
        self::assertFalse($privateAllowedStateDependency['isTargetPublic']);
        self::assertTrue($privateAllowedStateDependency['isAllowedState']);

        $privateAllowedStateViolations = array_values(array_filter(
            $reportData['violations'],
            static function (array $violation) use ($privateAllowedStateDependency): bool {
                return $violation['dependencyId'] === $privateAllowedStateDependency['id']
                    && $violation['type'] === 'private-unit';
            }
        ));
        self::assertCount(1, $privateAllowedStateViolations);
        self::assertSame('allowed-state', $privateAllowedStateViolations[0]['status']);

        unlink($storage);
        $this->removeDirectory($reportPath);
    }

    public function testReportMatrixFixtureCoversEveryDependencyStatus(): void
    {
        $allowedStateStorage = tempnam(sys_get_temp_dir(), 'phpca-report-matrix-allowed-state-');
        self::assertIsString($allowedStateStorage);
        $reportPath = sys_get_temp_dir() . '/phpca-report-matrix-' . bin2hex(random_bytes(8));

        $allowedState = [
            'application' => [
                'domain' => [
                    'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application\ApplicationService' => [
                        'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Domain\DomainEntity' => true,
                    ],
                    'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application\ApplicationUsesSecret' => [
                        'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Domain\Internal\DomainSecret' => true,
                    ],
                ],
            ],
            'domain' => [
                'application' => [
                    'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Domain\DomainUsesApplication' => [
                        'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application\ApplicationService' => true,
                    ],
                ],
            ],
        ];
        file_put_contents(
            $allowedStateStorage,
            '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($allowedState, true) . ';' . PHP_EOL
        );

        $config = $this->createReportMatrixConfig($allowedStateStorage);
        $config['reports_dir'] = $reportPath;

        (new PHPCleanArchitectureFacade($config))->generateReport($reportPath);

        $reportData = json_decode((string) file_get_contents($reportPath . '/report.json'), true);
        self::assertIsArray($reportData);

        $allowedDependency = $this->findDependency(
            $reportData,
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application\ApplicationService',
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Domain\DomainEntity'
        );
        self::assertTrue($allowedDependency['isComponentAllowed']);
        self::assertTrue($allowedDependency['isTargetPublic']);
        self::assertFalse($allowedDependency['isAllowedState']);

        $internalDependency = $this->findDependency(
            $reportData,
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application\ApplicationUsesOwnHelper',
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application\ApplicationHelper'
        );
        self::assertTrue($internalDependency['isInternal']);

        $blockedDependency = $this->findDependency(
            $reportData,
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application\ApplicationUsesInfrastructure',
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Infrastructure\InfrastructureAdapter'
        );
        self::assertFalse($blockedDependency['isComponentAllowed']);
        self::assertFalse($blockedDependency['isAllowedState']);
        $this->assertViolation($reportData, $blockedDependency['id'], 'forbidden-component', 'active');

        $blockedAllowedStateDependency = $this->findDependency(
            $reportData,
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Domain\DomainUsesApplication',
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application\ApplicationService'
        );
        self::assertFalse($blockedAllowedStateDependency['isComponentAllowed']);
        self::assertTrue($blockedAllowedStateDependency['isAllowedState']);
        $this->assertViolation($reportData, $blockedAllowedStateDependency['id'], 'forbidden-component', 'allowed-state');

        $privateAllowedStateDependency = $this->findDependency(
            $reportData,
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application\ApplicationUsesSecret',
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Domain\Internal\DomainSecret'
        );
        self::assertTrue($privateAllowedStateDependency['isComponentAllowed']);
        self::assertFalse($privateAllowedStateDependency['isTargetPublic']);
        self::assertTrue($privateAllowedStateDependency['isAllowedState']);
        $this->assertViolation($reportData, $privateAllowedStateDependency['id'], 'private-unit', 'allowed-state');

        unlink($allowedStateStorage);
        $this->removeDirectory($reportPath);
    }

    /**
     * @param array<string, array<string, array<string>>> $restrictions
     * @param string|null $allowedStateStorage
     * @return array<string, mixed>
     */
    private function createConfig(array $restrictions, ?string $allowedStateStorage = null): array
    {
        return [
            'reports_dir' => sys_get_temp_dir() . '/phpca-test-report',
            'vendor_based_components' => [
                'enabled' => false,
                'vendor_path' => '',
                'excluded' => [],
            ],
            'restrictions' => [
                'check_acyclic_dependencies_principle' => false,
                'check_stable_dependencies_principle' => false,
            ],
            'components' => [
                [
                    'name' => 'component-a',
                    'roots' => [
                        [
                            'path' => __DIR__ . '/Fixtures/Project/ComponentA',
                            'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA',
                        ],
                    ],
                    'restrictions' => $restrictions['component-a'] ?? [],
                ],
                [
                    'name' => 'component-b',
                    'roots' => [
                        [
                            'path' => __DIR__ . '/Fixtures/Project/ComponentB',
                            'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentB',
                        ],
                    ],
                    'restrictions' => $restrictions['component-b'] ?? [],
                ],
            ],
            'exclusions' => [
                'allowed_state' => [
                    'enabled' => $allowedStateStorage !== null,
                    'storage' => $allowedStateStorage ?? '',
                ],
            ],
            'factories' => [
                'dependencies_finder' => static function (): AstDependenciesFinder {
                    return new AstDependenciesFinder();
                },
                'report_rendering_service' => static function (): NullReportRenderingService {
                    return new NullReportRenderingService();
                },
                'event_manager' => static function (): NullEventManager {
                    return new NullEventManager();
                },
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createEmptyNamespaceScriptConfig(): array
    {
        return [
            'reports_dir' => sys_get_temp_dir() . '/phpca-test-report',
            'vendor_based_components' => [
                'enabled' => false,
                'vendor_path' => '',
                'excluded' => [],
            ],
            'restrictions' => [
                'check_acyclic_dependencies_principle' => false,
                'check_stable_dependencies_principle' => false,
            ],
            'components' => [
                [
                    'name' => 'scripts',
                    'roots' => [
                        [
                            'path' => __DIR__ . '/Fixtures/SourceDiscovery/Scripts',
                            'namespace' => '',
                        ],
                    ],
                    'restrictions' => [
                        'allowed_dependencies' => ['scripts'],
                    ],
                ],
            ],
            'exclusions' => [
                'allowed_state' => [
                    'enabled' => false,
                    'storage' => '',
                ],
            ],
            'factories' => [
                'dependencies_finder' => static function (): AstDependenciesFinder {
                    return new AstDependenciesFinder();
                },
                'report_rendering_service' => static function (): NullReportRenderingService {
                    return new NullReportRenderingService();
                },
                'event_manager' => static function (): NullEventManager {
                    return new NullEventManager();
                },
            ],
        ];
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = glob($path . '/*');
        self::assertIsArray($files);
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }

    /**
     * @param array<string, mixed> $reportData
     * @return array<string, mixed>
     */
    private function findDependencyByTarget(array $reportData, string $targetUnitName): array
    {
        foreach ($reportData['dependencies'] as $dependency) {
            $normalizedDependency = $this->normalizeDependency($dependency);
            if ($this->unitName($reportData, $normalizedDependency['toUnitId']) === $targetUnitName) {
                return $normalizedDependency;
            }
        }

        self::fail('Dependency to ' . $targetUnitName . ' was not found in report data.');
    }

    /**
     * @param array<string, mixed> $reportData
     * @return array<string, mixed>
     */
    private function findDependency(array $reportData, string $fromUnitName, string $toUnitName): array
    {
        foreach ($reportData['dependencies'] as $dependency) {
            $normalizedDependency = $this->normalizeDependency($dependency);
            if (
                $this->unitName($reportData, $normalizedDependency['fromUnitId']) === $fromUnitName
                && $this->unitName($reportData, $normalizedDependency['toUnitId']) === $toUnitName
            ) {
                return $normalizedDependency;
            }
        }

        self::fail(sprintf('Dependency %s -> %s was not found in report data.', $fromUnitName, $toUnitName));
    }

    /**
     * @param array<int|string, mixed> $dependency
     * @return array{id: string, fromUnitId: string, toUnitId: string, fromComponentId: string, toComponentId: string, isInternal: bool, isComponentAllowed: bool, isTargetPublic: bool, isAllowedState: bool}
     */
    private function normalizeDependency(array $dependency): array
    {
        if (array_is_list($dependency)) {
            $flags = $dependency[4];
            return [
                'id' => $dependency[0] . '->' . $dependency[1],
                'fromUnitId' => $dependency[0],
                'toUnitId' => $dependency[1],
                'fromComponentId' => $dependency[2],
                'toComponentId' => $dependency[3],
                'isInternal' => (bool) ($flags & 1),
                'isComponentAllowed' => (bool) ($flags & 2),
                'isTargetPublic' => (bool) ($flags & 4),
                'isAllowedState' => (bool) ($flags & 8),
            ];
        }

        return [
            'id' => $dependency['id'],
            'fromUnitId' => $dependency['fromUnitId'],
            'toUnitId' => $dependency['toUnitId'],
            'fromComponentId' => $dependency['fromComponentId'],
            'toComponentId' => $dependency['toComponentId'],
            'isInternal' => $dependency['isInternal'],
            'isComponentAllowed' => $dependency['isComponentAllowed'],
            'isTargetPublic' => $dependency['isTargetPublic'],
            'isAllowedState' => $dependency['isAllowedState'],
        ];
    }

    /**
     * @param array<string, mixed> $reportData
     */
    private function unitName(array $reportData, string $unitId): string
    {
        foreach (array_merge($reportData['units'], $reportData['externalUnits'] ?? []) as $unit) {
            if ($unit['id'] === $unitId) {
                return $unit['name'];
            }
        }

        self::fail('Unit ' . $unitId . ' was not found in report data.');
    }

    /**
     * @param array<string, mixed> $reportData
     */
    private function assertViolation(array $reportData, string $dependencyId, string $type, string $status): void
    {
        foreach ($reportData['violations'] as $violation) {
            if ($violation['dependencyId'] === $dependencyId && $violation['type'] === $type) {
                self::assertSame($status, $violation['status']);
                return;
            }
        }

        self::fail(sprintf('Violation %s for dependency %s was not found.', $type, $dependencyId));
    }

    /**
     * @param array<string, mixed> $reportData
     */
    private function assertNoViolation(array $reportData, string $dependencyId, string $type): void
    {
        foreach ($reportData['violations'] as $violation) {
            self::assertFalse(
                $violation['dependencyId'] === $dependencyId && $violation['type'] === $type,
                sprintf('Unexpected violation %s for dependency %s was found.', $type, $dependencyId)
            );
        }
    }

    /**
     * @param array<string, mixed> $reportData
     * @return array<string, mixed>
     */
    private function findComponent(array $reportData, string $componentName): array
    {
        foreach ($reportData['components'] as $component) {
            if ($component['name'] === $componentName) {
                return $component;
            }
        }

        self::fail('Component ' . $componentName . ' was not found in report data.');
    }

    /**
     * @return array<string, mixed>
     */
    private function createReportMatrixConfig(string $allowedStateStorage): array
    {
        $fixturePath = __DIR__ . '/Fixtures/ReportMatrixProject';

        return [
            'reports_dir' => sys_get_temp_dir() . '/phpca-report-matrix',
            'vendor_based_components' => [
                'enabled' => false,
                'vendor_path' => '',
                'excluded' => [],
            ],
            'restrictions' => [
                'check_acyclic_dependencies_principle' => false,
                'check_stable_dependencies_principle' => false,
            ],
            'components' => [
                [
                    'name' => 'domain',
                    'roots' => [
                        [
                            'path' => $fixturePath . '/Domain',
                            'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Domain',
                        ],
                    ],
                    'restrictions' => [
                        'allowed_dependencies' => ['domain'],
                        'private_elements' => [$fixturePath . '/Domain/Internal'],
                    ],
                ],
                [
                    'name' => 'application',
                    'roots' => [
                        [
                            'path' => $fixturePath . '/Application',
                            'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application',
                        ],
                    ],
                    'restrictions' => [
                        'allowed_dependencies' => ['application', 'domain'],
                    ],
                ],
                [
                    'name' => 'infrastructure',
                    'roots' => [
                        [
                            'path' => $fixturePath . '/Infrastructure',
                            'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Infrastructure',
                        ],
                    ],
                    'restrictions' => [
                        'allowed_dependencies' => ['infrastructure', 'domain'],
                    ],
                ],
            ],
            'exclusions' => [
                'allowed_state' => [
                    'enabled' => true,
                    'storage' => $allowedStateStorage,
                ],
            ],
            'factories' => [
                'dependencies_finder' => static function (): AstDependenciesFinder {
                    return new AstDependenciesFinder();
                },
                'report_rendering_service' => static function (
                    EventManagerInterface $eventManager
                ): ReportRenderingService {
                    return new ReportRenderingService($eventManager);
                },
                'event_manager' => static function (): NullEventManager {
                    return new NullEventManager();
                },
            ],
        ];
    }
}
