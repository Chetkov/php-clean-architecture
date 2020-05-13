<?php

use Chetkov\PHPCleanArchitecture\PHPCleanArchitectureFacade;

require_once dirname(__DIR__, 3) . '/autoload.php';

$config = require dirname(__DIR__, 4) . '/php-clean-architecture-config.php';

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
