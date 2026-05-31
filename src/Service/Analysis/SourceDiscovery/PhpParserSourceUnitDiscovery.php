<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\SourceDiscovery;

use Chetkov\PHPCleanArchitecture\Model\Helper\PathHelper;
use Chetkov\PHPCleanArchitecture\Model\Path;
use Chetkov\PHPCleanArchitecture\Model\SourceUnit;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Class PhpParserSourceUnitDiscovery
 * @package Chetkov\PHPCleanArchitecture\Service\Analysis\SourceDiscovery
 */
class PhpParserSourceUnitDiscovery implements SourceUnitDiscoveryInterface
{
    /** @var Parser */
    private $parser;

    public function __construct(?Parser $parser = null)
    {
        $this->parser = $parser ?? (new ParserFactory())->createForNewestSupportedVersion();
    }

    /**
     * @inheritDoc
     */
    public function discover(\SplFileInfo $file, Path $rootPath): array
    {
        $fullPath = $file->getRealPath();
        if (!$fullPath) {
            return [];
        }

        $fallbackName = $this->createFallbackName($fullPath, $rootPath);
        $content = file_get_contents($fullPath);
        if ($content === false) {
            return $this->fallbackSourceUnit($fallbackName, $fullPath);
        }

        try {
            $ast = $this->parser->parse($content) ?? [];
        } catch (\PhpParser\Error $error) {
            return $this->fallbackSourceUnit($fallbackName, $fullPath);
        }

        $sourceUnits = [];
        $this->collectSourceUnits($ast, '', $fullPath, $sourceUnits);

        if (empty($sourceUnits)) {
            return $this->fallbackSourceUnit($fallbackName, $fullPath);
        }

        return $sourceUnits;
    }

    /**
     * @return array<SourceUnit>
     */
    private function fallbackSourceUnit(string $fallbackName, string $fullPath): array
    {
        if ($fallbackName === '') {
            return [];
        }

        return [new SourceUnit($fallbackName, $fullPath, SourceUnit::KIND_SCRIPT)];
    }

    private function createFallbackName(string $fullPath, Path $rootPath): string
    {
        return PathHelper::removeDoubleBackslashes($rootPath->namespace() .
            PathHelper::pathToNamespace($rootPath->getRelativePath($fullPath)));
    }

    /**
     * @param array<Node> $nodes
     * @param string $namespace
     * @param string $path
     * @param array<SourceUnit> $sourceUnits
     */
    private function collectSourceUnits(array $nodes, string $namespace, string $path, array &$sourceUnits): void
    {
        foreach ($nodes as $node) {
            if ($node instanceof Stmt\Namespace_) {
                $nestedNamespace = $this->nameToString($node->name);
                $this->collectSourceUnits($node->stmts, $nestedNamespace, $path, $sourceUnits);
                continue;
            }

            if ($node instanceof Stmt\ClassLike) {
                $sourceUnit = $this->createSourceUnit($node, $namespace, $path);
                if ($sourceUnit) {
                    $sourceUnits[] = $sourceUnit;
                }
                continue;
            }

            foreach ($node->getSubNodeNames() as $subNodeName) {
                $subNode = $node->$subNodeName;
                if ($subNode instanceof Node) {
                    $this->collectSourceUnits([$subNode], $namespace, $path, $sourceUnits);
                } elseif (is_array($subNode)) {
                    $this->collectSourceUnits(array_filter($subNode, static function ($item): bool {
                        return $item instanceof Node;
                    }), $namespace, $path, $sourceUnits);
                }
            }
        }
    }

    private function createSourceUnit(Stmt\ClassLike $node, string $namespace, string $path): ?SourceUnit
    {
        if ($node instanceof Stmt\Class_ && $node->isAnonymous()) {
            return null;
        }

        $nodeName = $node->name;
        if (!$nodeName instanceof Node\Identifier) {
            return null;
        }

        $name = $namespace ? $namespace . '\\' . $nodeName->toString() : $nodeName->toString();
        if ($node instanceof Stmt\Interface_) {
            return new SourceUnit($name, $path, SourceUnit::KIND_INTERFACE, true);
        }
        if ($node instanceof Stmt\Trait_) {
            return new SourceUnit($name, $path, SourceUnit::KIND_TRAIT);
        }
        if ($node instanceof Stmt\Enum_) {
            return new SourceUnit($name, $path, SourceUnit::KIND_ENUM);
        }
        if (!$node instanceof Stmt\Class_) {
            return null;
        }

        return new SourceUnit($name, $path, SourceUnit::KIND_CLASS, $node->isAbstract());
    }

    private function nameToString(?\PhpParser\Node\Name $name): string
    {
        return $name instanceof \PhpParser\Node\Name ? $name->toString() : '';
    }
}
