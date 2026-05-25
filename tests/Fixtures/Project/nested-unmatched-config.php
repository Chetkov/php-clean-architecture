<?php

declare(strict_types=1);

$config = require __DIR__ . '/clean-config.php';
$config['components'] = [
    'component-a' => [
        'roots' => [
            [
                'path' => __DIR__ . '/NestedUnmatched/ComponentA',
                'namespace' => 'NestedUnmatched\\ComponentA',
            ],
        ],
        'sub' => [
            'inherit' => ['factories', 'exclusions', 'restrictions', 'vendor_based_components'],
            'components' => [
                'layer-a' => [
                    'roots' => [
                        [
                            'path' => __DIR__ . '/NestedUnmatched/ComponentA/LayerA',
                            'namespace' => 'NestedUnmatched\\ComponentA\\LayerA',
                        ],
                    ],
                ],
            ],
        ],
    ],
];

return $config;
