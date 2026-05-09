<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing;

/**
 * @property \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\DocBlockDependency $docBlockDependency
 * @method \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\FactoryDependency make(\Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ParamDependency $param): \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ReturnDependency
 */
class FullyQualifiedSubject
{
    /**
     * @var \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\VarDependency
     */
    private $value;

    public function __construct()
    {
        $this->value = new \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\VarDependency();
    }

    public function value(): \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\VarDependency
    {
        return $this->value;
    }

    /**
     * @param \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ParamDependency $param
     * @return \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ReturnDependency
     * @throws \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ThrowsDependency
     */
    public function handle($param)
    {
        \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\StaticDependency::touch();

        $created = $this->createServiceDependency(
            new \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ServiceDependency()
        );
        if ($created instanceof \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\InstanceofDependency) {
            return new \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ReturnDependency();
        }

        return new \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ReturnDependency();
    }

    private function createServiceDependency(
        \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ServiceDependency $fallback
    ): \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ServiceDependency {
        if ($fallback instanceof \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\InstanceofDependency) {
            return $fallback;
        }

        return new \Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\InstanceofDependency();
    }
}
