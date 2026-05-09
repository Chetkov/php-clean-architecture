<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\ReportMatrixProject\Application;

final class ApplicationUsesOwnHelper
{
    public function helper(): ApplicationHelper
    {
        return new ApplicationHelper();
    }
}
