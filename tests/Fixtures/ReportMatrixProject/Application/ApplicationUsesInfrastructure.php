<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application;

use Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Infrastructure\InfrastructureAdapter;

final class ApplicationUsesInfrastructure
{
    public function adapter(): InfrastructureAdapter
    {
        return new InfrastructureAdapter();
    }
}
