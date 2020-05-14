<?php

use Chetkov\PHPCleanArchitecture\PHPCleanArchitectureFacade;

require_once dirname(__DIR__, 3) . '/autoload.php';

$configPath = $argv[1] ?? dirname(__DIR__, 4) . '/phpca-config.php';
$config = require $configPath;

$analyzer = new PHPCleanArchitectureFacade($config);
$errors = $analyzer->check();
if (empty($errors)) {
    exit('No errors!' . PHP_EOL);
}

echo PHP_EOL;
foreach ($errors as $index => $error) {
    $number = $index + 1;
    echo "$number. $error" . PHP_EOL;
}
exit(1);
