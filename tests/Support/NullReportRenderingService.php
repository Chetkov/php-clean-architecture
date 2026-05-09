<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Support;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingServiceInterface;

final class NullReportRenderingService implements ReportRenderingServiceInterface
{
    public function render(string $reportPath, Component ...$components): void
    {
    }
}
