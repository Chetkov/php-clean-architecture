<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA;

use Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentB\Internal\PrivateClass;

class UsesPrivateClass
{
    public function createDependency(): PrivateClass
    {
        return new PrivateClass();
    }
}
