<?php

declare(strict_types=1);

// phpcs:ignoreFile

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing;

class FirstSubject
{
    public function value(): ServiceDependency
    {
        return new ServiceDependency();
    }
}

class SecondSubject
{
    public function value(): ReturnDependency
    {
        return new ReturnDependency();
    }
}
