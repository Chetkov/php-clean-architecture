<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application;

use Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Domain\Internal\DomainSecret;

final class ApplicationUsesSecret
{
    public function secret(): DomainSecret
    {
        return new DomainSecret();
    }
}
