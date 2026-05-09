<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests;

use Chetkov\PHPCleanArchitecture\PHPCleanArchitectureFacade;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\CodeParsingDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesCalledStaticallyParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesCreatedThroughNewParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesFromInstanceofConstructionParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\MethodAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ParamAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\PropertyAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ReturnAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ThrowsAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\VarAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CompositeDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\ReflectionDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Tests\Support\NullEventManager;
use Chetkov\PHPCleanArchitecture\Tests\Support\NullReportRenderingService;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class PHPCleanArchitectureFacadeTest extends TestCase
{
    #[RunInSeparateProcess]
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

    #[RunInSeparateProcess]
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

    #[RunInSeparateProcess]
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
                'dependencies_finder' => static function (): CompositeDependenciesFinder {
                    return new CompositeDependenciesFinder(
                        new ReflectionDependenciesFinder(),
                        new CodeParsingDependenciesFinder(
                            new ClassesCreatedThroughNewParsingStrategy(),
                            new ClassesCalledStaticallyParsingStrategy(),
                            new ClassesFromInstanceofConstructionParsingStrategy(),
                            new PropertyAnnotationsParsingStrategy(),
                            new MethodAnnotationsParsingStrategy(),
                            new ParamAnnotationsParsingStrategy(),
                            new ReturnAnnotationsParsingStrategy(),
                            new ThrowsAnnotationsParsingStrategy(),
                            new VarAnnotationsParsingStrategy()
                        )
                    );
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
}
