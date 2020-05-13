<?php

namespace Chetkov\PHPCleanArchitecture\Service\DependenciesFinder;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

/**
 * Class ReflectionDependenciesFinder
 * @package Chetkov\PHPCleanArchitecture\Service\DependenciesFinder
 */
class ReflectionDependenciesFinder implements DependenciesFinderInterface
{
    /**
     * @inheritDoc
     */
    public function find(UnitOfCode $unitOfCode): array
    {
        try {
            $class = new \ReflectionClass($unitOfCode->name());

            $dependencies = [];

            $parent = $class->getParentClass();
            if ($parent) {
                $dependencies[] = $parent->getName();
            }

            foreach ($class->getInterfaces() as $interface) {
                $dependencies[] = $interface->getName();
            }

            foreach ($class->getTraits() as $trait) {
                $dependencies[] = $trait->getName();
            }

            $methods = array_filter(array_merge($class->getMethods(), [$class->getConstructor()]));
            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                if ($returnType) {
                    $dependencies[] = $returnType->getName();
                }

                foreach ($method->getParameters() as $parameter) {
                    $type = $parameter->getType();
                    if ($type) {
                        $dependencies[] = $type->getName();
                    }
                }
            }
        } catch (\ReflectionException $e) {
            $dependencies = [];
        }

        return array_filter(array_unique($dependencies), function (string $dependency) {
            return !ExclusionChecker::isExclusion($dependency);
        });
    }
}
