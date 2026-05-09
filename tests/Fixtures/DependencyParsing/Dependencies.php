<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing;

class ServiceDependency
{
}

class FactoryDependency
{
}

class StaticDependency
{
    public static function touch(): void
    {
    }
}

class InstanceofDependency
{
}

class DocBlockDependency
{
}

class ParamDependency
{
}

class ReturnDependency
{
}

class ThrowsDependency extends \Exception
{
}

class VarDependency
{
}
