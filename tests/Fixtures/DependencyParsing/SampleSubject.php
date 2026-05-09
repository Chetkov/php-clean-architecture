<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing;

use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ServiceDependency as AliasDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\{
    DocBlockDependency,
    FactoryDependency,
    InstanceofDependency,
    ParamDependency,
    ReturnDependency,
    StaticDependency,
    ThrowsDependency,
    VarDependency
};

/**
 * @property DocBlockDependency $docBlockDependency
 * @method FactoryDependency make(ParamDependency $param): ReturnDependency
 */
class SampleSubject
{
    /**
     * @var VarDependency
     */
    private $value;

    /**
     * @param ParamDependency $param
     * @return ReturnDependency
     * @throws ThrowsDependency
     */
    public function handle(ParamDependency $param): ReturnDependency
    {
        $created = new AliasDependency();
        StaticDependency::touch();

        if ($created instanceof InstanceofDependency) {
            return new ReturnDependency();
        }

        return new ReturnDependency();
    }
}
