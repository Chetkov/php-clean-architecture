<?php

declare(strict_types=1);

use Chetkov\PHPCleanArchitecture\Service\EventManagerInterface;
use Chetkov\PHPCleanArchitecture\Service\Report\SpaReport\ReportRenderingService;

$config = require __DIR__ . '/clean-config.php';
$config['reports_dir'] = (string) getenv('PHPCA_REPORTS_DIR') ?: $config['reports_dir'];
$config['factories']['report_rendering_service'] = static function (
    EventManagerInterface $eventManager
): ReportRenderingService {
    return new ReportRenderingService($eventManager);
};

return $config;
