<?php

use Chetkov\PHPCleanArchitecture\PHPCleanArchitectureFacade;

require_once dirname(__DIR__, 3) . '/autoload.php';

$configPath = $argv[1] ?? dirname(__DIR__, 4) . '/phpca-config.php';
$config = require $configPath;

$reportsPath = $config['reports_dir'];
if (!is_dir($reportsPath) && !mkdir($reportsPath) && !is_dir($reportsPath)) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created', $reportsPath));
}

$analyzer = new PHPCleanArchitectureFacade($config);
$analyzer->generateReport($reportsPath);

echo PHP_EOL . 'Report: ' . $reportsPath . '/index.html' . PHP_EOL;
