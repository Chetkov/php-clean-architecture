<?php

declare(strict_types=1);

$config = require __DIR__ . '/report-config.php';

$config['components'][0]['sub'] = [
    'inherit' => ['factories', 'vendor_based_components', 'exclusions', 'restrictions'],
    'reports_dir' => sys_get_temp_dir() . '/phpca-ignored-child-report',
    'components' => [
        'Слой A!' => [
            'roots' => [
                [
                    'path' => __DIR__ . '/ComponentA',
                    'namespace' => 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA',
                ],
            ],
            'restrictions' => [
                'allowed_dependencies' => ['Слой A!', 'Layer B'],
            ],
        ],
        'Layer B' => [
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
