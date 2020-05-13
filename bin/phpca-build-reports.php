<?php

use Chetkov\PHPCleanArchitecture\PHPCleanArchitectureFacade;

require_once dirname(__DIR__, 3) . '/autoload.php';

$config = require dirname(__DIR__, 4) . '/php-clean-architecture-config.php';
$reportsPath = $config['reports_dir'];
if (!is_dir($reportsPath) && !mkdir($reportsPath) && !is_dir($reportsPath)) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created', $reportsPath));
}

$analyzer = new PHPCleanArchitectureFacade($config);
$analyzer->generateReport($reportsPath);

echo PHP_EOL . 'Report: ' . $reportsPath . '/index.html' . PHP_EOL;
