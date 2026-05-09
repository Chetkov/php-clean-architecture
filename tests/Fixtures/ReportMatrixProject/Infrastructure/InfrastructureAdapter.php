<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Infrastructure;

use Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Domain\DomainEntity;

final class InfrastructureAdapter
{
    public function domainEntity(): DomainEntity
    {
        return new DomainEntity();
    }
}
