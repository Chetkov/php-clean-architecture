<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA;

use Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentB\BClass;

class AClass
{
    public function createDependency(): BClass
    {
        return new BClass();
    }
}
