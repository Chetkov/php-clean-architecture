<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA;

use Chetkov\PHPCleanArchitecture\Tests\Fixtures\Project\ComponentA\Internal\InternalPrivateClass;

class UsesOwnPrivateClass
{
    public function createDependency(): InternalPrivateClass
    {
        return new InternalPrivateClass();
    }
}
