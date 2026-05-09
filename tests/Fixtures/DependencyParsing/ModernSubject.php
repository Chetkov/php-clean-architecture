<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing;

use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\{
    AttributeDependency as DependencyAttribute,
    CatchDependency,
    ClassConstantDependency,
    DocBlockDependency,
    FactoryDependency,
    InstanceofDependency,
    InterfaceDependency,
    IntersectionDependency,
    NestedDependency,
    ParamDependency,
    ParentDependency,
    PromotedDependency,
    ReturnDependency,
    ServiceDependency,
    StaticDependency,
    ThrowsDependency,
    TraitDependency,
    UnionDependency,
    VarDependency
};

/**
 * @property DocBlockDependency $docBlockDependency
 * @method FactoryDependency make(ParamDependency&IntersectionDependency $param): ReturnDependency
 */
#[DependencyAttribute]
class ModernSubject extends ParentDependency implements InterfaceDependency
{
    use TraitDependency;

    private ClassConstantDependency $classConstantDependency;

    /**
     * @var VarDependency
     */
    private $value;

    public function __construct(private PromotedDependency $promotedDependency)
    {
        $this->value = new VarDependency();
        $this->classConstantDependency = new ClassConstantDependency();
    }

    /**
     * @param ParamDependency $param
     * @return ReturnDependency
     * @throws ThrowsDependency
     */
    public function handle(
        ParamDependency $param,
        UnionDependency|ServiceDependency $unionDependency,
        IntersectionDependency&InterfaceDependency $intersectionDependency,
    ): ReturnDependency {
        $this->promoted();
        $this->value();
        $this->classConstantDependency();
        $this->dependencyClass();

        StaticDependency::touch();

        $created = $this->createNestedDependency();

        if ($created instanceof InstanceofDependency) {
            return new ReturnDependency();
        }

        try {
            $this->throwCatchDependency();
        } catch (CatchDependency $exception) {
            $this->classConstantDependency = new ClassConstantDependency();
        }

        return new ReturnDependency();
    }

    public function promoted(): PromotedDependency
    {
        return $this->promotedDependency;
    }

    public function value(): VarDependency
    {
        return $this->value;
    }

    public function dependencyClass(): string
    {
        return ClassConstantDependency::class;
    }

    public function classConstantDependency(): ClassConstantDependency
    {
        return $this->classConstantDependency;
    }

    private function createNestedDependency(): object
    {
        return new class () extends NestedDependency implements InterfaceDependency {
            use TraitDependency;
        };
    }

    /**
     * @throws CatchDependency
     */
    private function throwCatchDependency(): void
    {
        throw new CatchDependency();
    }
}
