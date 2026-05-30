<?php

declare(strict_types=1);

use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\Ast\AstDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\EventManagerInterface;
use Chetkov\PHPCleanArchitecture\Service\Report\SpaReport\ReportRenderingService;
use Chetkov\PHPCleanArchitecture\Tests\Support\NullEventManager;

$projectRoot = __DIR__;

return [
    'reports_dir' => (string) getenv('PHPCA_REPORTS_DIR') ?: sys_get_temp_dir() . '/phpca-legacy-rate-report',
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
        'Feature' => [
            'roots' => [
                [
                    'path' => $projectRoot . '/Modern',
                    'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Modern',
                ],
                [
                    'path' => $projectRoot . '/Legacy',
                    'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Legacy',
                    'legacy' => true,
                ],
            ],
            'sub' => [
                'inherit' => ['factories', 'vendor_based_components', 'restrictions', 'exclusions'],
                'components' => [
                    'Modern Layer' => [
                        'roots' => [
                            [
                                'path' => $projectRoot . '/Modern',
                                'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Modern',
                            ],
                        ],
                    ],
                    'Legacy Layer' => [
                        'roots' => [
                            [
                                'path' => $projectRoot . '/Legacy',
                                'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Legacy',
                                'legacy' => true,
                            ],
                        ],
                    ],
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
