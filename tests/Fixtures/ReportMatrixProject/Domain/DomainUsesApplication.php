<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Domain;

use Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application\ApplicationService;

final class DomainUsesApplication
{
    public function applicationService(): ApplicationService
    {
        return new ApplicationService();
    }
}
