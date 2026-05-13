<?php

declare(strict_types=1);

$config = require __DIR__ . '/clean-config.php';

$config['components'] = [
    [
        'name' => 'Shared',
        'roots' => [
            [
                'path' => __DIR__ . '/ComponentB',
                'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentB',
            ],
        ],
    ],
    [
        'name' => 'FeatureA',
        'roots' => [
            [
                'path' => __DIR__ . '/ComponentA',
                'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA',
            ],
        ],
        'restrictions' => [
            'allowed_dependencies' => ['FeatureA', 'Shared'],
        ],
        'sub' => [
            'inherit' => ['factories', 'vendor_based_components', 'exclusions', 'restrictions'],
            'components' => [
                [
                    'name' => 'Domain',
                    'roots' => [
                        [
                            'path' => __DIR__ . '/ComponentA',
                            'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA',
                        ],
                    ],
                    'restrictions' => [
                        'allowed_dependencies' => ['Domain', 'Shared.Domain'],
                    ],
                ],
                [
                    'name' => 'Shared.Domain',
                    'roots' => [
                        [
                            'path' => __DIR__ . '/ComponentB',
                            'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentB',
                        ],
                    ],
                    'is_analyze_enabled' => false,
                ],
            ],
        ],
    ],
];

return $config;
