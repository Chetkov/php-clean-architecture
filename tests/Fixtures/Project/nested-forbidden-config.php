<?php

declare(strict_types=1);

$config = require __DIR__ . '/report-config.php';

$config['components'][0]['sub'] = [
    'inherit' => ['factories', 'vendor_based_components', 'exclusions', 'restrictions'],
    'components' => [
        [
            'name' => 'component-a-layer',
            'roots' => [
                [
                    'path' => __DIR__ . '/ComponentA',
                    'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA',
                ],
            ],
            'restrictions' => [
                'allowed_dependencies' => ['component-a-layer'],
            ],
        ],
        [
            'name' => 'component-b-layer',
            'roots' => [
                [
                    'path' => __DIR__ . '/ComponentB',
                    'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentB',
                ],
            ],
        ],
    ],
];

return $config;
