<?php

declare(strict_types=1);

$config = require __DIR__ . '/clean-config.php';
$config['components'][0]['restrictions']['allowed_dependencies'] = ['component-a'];

return $config;
