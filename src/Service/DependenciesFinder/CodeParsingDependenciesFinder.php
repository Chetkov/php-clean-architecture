<?php

namespace Chetkov\PHPCleanArchitecture\Service\DependenciesFinder;

use Chetkov\PHPCleanArchitecture\Helper\PathHelper;
use Chetkov\PHPCleanArchitecture\Helper\StringHelper;
use Chetkov\PHPCleanArchitecture\Model\Type\TypePrimitive;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

/**
 * Class CodeParsingDependenciesFinder
 * @package Chetkov\PHPCleanArchitecture\Service\DependenciesFinder
 */
class CodeParsingDependenciesFinder implements DependenciesFinderInterface
{
    /**
     * @inheritDoc
     */
    public function find(UnitOfCode $unitOfCode): array
    {
        $dependencies = [];
        if (!$unitOfCode->path()) {
            return $dependencies;
        }

        $content = file_get_contents($unitOfCode->path());

        [$existingClasses, $importedNamespaceParts] = $this->parseUses($content);
        $dependencies[] = $existingClasses;

        [$existingClasses, $importedClassNames] = $this->parseCode($content);
        $dependencies[] = $existingClasses;

        $dependencies = array_merge(...$dependencies);
        foreach ($importedClassNames as $importedClassName) {
            $tmp = explode('\\', $importedClassName);
            $startOfImportedClassName = trim(array_shift($tmp));

            foreach ($importedNamespaceParts as $importedNamespacePart) {
                $tmp = explode('\\', $importedNamespacePart);
                $endOfImportedNamespacePart = trim(end($tmp));

                if ($startOfImportedClassName !== $endOfImportedNamespacePart) {
                    continue;
                }

                $dependencies[] = PathHelper::removeDoubleBackslashes($importedNamespacePart . '\\' . $importedClassName);
            }
        }

        return array_unique($dependencies);
    }

    /**
     * @param string $content
     * @return array
     */
    private function parseUses(string $content): array
    {
        $existingClasses = [];
        $importedNamespaceParts = [];

        preg_match_all('/^use ([^;]*);$/ium', $content, $matches);
        [, $results] = $matches;

        foreach ($results as $row) {
            $row = preg_replace('/( {2,}|[\n]+)/ium', ' ', $row);

            if (preg_match('/(.*){(.*)}/u', $row, $matches)) {
                $rows = [];
                [, $commonNamespacePart, $details] = $matches;
                $specificNamespaceParts = explode(',', $details);
                foreach ($specificNamespaceParts as $specificNamespacePart) {
                    $rows[] = $commonNamespacePart . $specificNamespacePart;
                }
            } else {
                $rows = [$row];
            }

            foreach ($rows as $one) {
                [$className] = explode(' as ', StringHelper::removeDoubleSpaces($one));
                $namespace = StringHelper::removeSpaces($className);

                if ($this->isElementExists($namespace)) {
                    $existingClasses[$namespace] = true;
                } else {
                    $importedNamespaceParts[$namespace] = true;
                }
            }
        }
        return [array_keys($existingClasses), array_keys($importedNamespaceParts)];
    }

    /**
     * @param string $content
     * @return string[]
     */
    private function parseCode(string $content): array
    {
        $dependencies = [];
        $dependencies[] = $this->getClassesCreatedThroughNew($content);
        $dependencies[] = $this->getClassesCalledStatically($content);
        $dependencies[] = $this->getTypesFromVarAnnotation($content);

        $fullNames = [];
        $importedClassNames = [];
        foreach (array_unique(array_merge(...$dependencies)) as $dependency) {
            if (ExclusionChecker::isExclusion($dependency)) {
                continue;
            }

            if (TypePrimitive::isThisType($dependency)
                || $this->isElementExists($dependency)
            ) {
                $fullNames[] = $dependency;
            } else {
                $importedClassNames[] = $dependency;
            }
        }

        return [$fullNames, $importedClassNames];
    }

    /**
     * Возвращает классы, экземпляры которых создаются через new
     * @param string $content
     * @return string[]
     */
    private function getClassesCreatedThroughNew(string $content): array
    {
        preg_match_all('/new\s*([^(]*)/ium', $content, $matches);
        [, $result] = $matches;
        return array_unique($result);
    }

    /**
     * Возвращает классы, к которым есть обращения через ::
     * @param string $content
     * @return string[]
     */
    private function getClassesCalledStatically(string $content): array
    {
        preg_match_all('/([\w\\\]*)\s*:{2}/um', $content, $matches);
        [, $result] = $matches;
        return array_unique($result);
    }

    /**
     * Возвращает классы найденные в аннотациях
     * @param string $content
     * @return string[]
     */
    private function getTypesFromVarAnnotation(string $content): array
    {
        $filter = function (string $element) {
            return !empty($element) && mb_stripos($element, '$') === false;
        };

        $groupPattern = '\s*([\w|\[\]\\\\\$]*)';
        preg_match_all("/@var{$groupPattern}{$groupPattern}/ium", $content, $matches);
        [, $group1, $group2] = $matches;

        $dependencies = [];
        foreach (array_merge(array_filter($group1, $filter), array_filter($group2, $filter)) as $one) {
            foreach (explode('|', str_replace('[]', '', StringHelper::removeSpaces($one))) as $type) {
                $dependencies[$type] = true;
            }
        }

        return array_keys($dependencies);
    }

    /**
     * @param string $name
     * @return bool
     */
    private function isElementExists(string $name): bool
    {
        return class_exists($name) || interface_exists($name) || trait_exists($name);
    }
}
