<?php

declare(strict_types=1);

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
                    'path' => __DIR__ . '/ComponentA',
                    'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA',
                ],
            ],
            'restrictions' => [
                'allowed_dependencies' => ['component-a', 'component-b'],
            ],
        ],
        [
            'name' => 'component-b',
            'roots' => [
                [
                    'path' => __DIR__ . '/ComponentB',
                    'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentB',
                ],
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
