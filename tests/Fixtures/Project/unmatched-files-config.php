<?php

declare(strict_types=1);

$config = require __DIR__ . '/clean-config.php';
$config['components']['component-a']['excluded'][] = __DIR__ . '/Unmatched/ExcludedLooseClass.php';

return $config;
