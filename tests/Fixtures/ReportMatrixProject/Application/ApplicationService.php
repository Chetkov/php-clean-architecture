<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application;

use Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Domain\DomainEntity;

final class ApplicationService
{
    public function domainEntity(): DomainEntity
    {
        return new DomainEntity();
    }
}
