<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\Ast;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\DependenciesFinderInterface;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Class AstDependenciesFinder
 * @package Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\Ast
 */
class AstDependenciesFinder implements DependenciesFinderInterface
{
    /** @var Parser */
    private $parser;

    /** @var DependencyNameNormalizer */
    private $dependencyNameNormalizer;

    public function __construct(?Parser $parser = null, ?DependencyNameNormalizer $dependencyNameNormalizer = null)
    {
        $this->parser = $parser ?? (new ParserFactory())->createForNewestSupportedVersion();
        $this->dependencyNameNormalizer = $dependencyNameNormalizer ?? new DependencyNameNormalizer();
    }

    /**
     * @inheritDoc
     */
    public function find(UnitOfCode $unitOfCode): array
    {
        $path = $unitOfCode->path();
        if ($path === null) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        try {
            $nodes = $this->parser->parse($content) ?? [];
        } catch (\PhpParser\Error $error) {
            return [];
        }

        $targetContext = $this->findTargetContext($nodes, $unitOfCode->name());
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver(null, ['preserveOriginalNames' => true]));
        $nodes = $traverser->traverse($nodes);

        $dependencies = [];
        if ($targetContext['node'] instanceof Stmt\ClassLike) {
            $this->collectFromNode($targetContext['node'], $dependencies);
            $this->collectDocBlockDependencies($targetContext['node'], $targetContext, $dependencies);
        } else {
            foreach ($nodes as $node) {
                $this->collectFromNode($node, $dependencies);
                $this->collectDocBlockDependencies($node, $targetContext, $dependencies);
            }
        }

        unset($dependencies[trim($unitOfCode->name(), '\\')]);

        return array_keys($dependencies);
    }

    /**
     * @param array<Node> $nodes
     * @return array{node: Stmt\ClassLike|null, namespace: string, imports: array<string, string>}
     */
    private function findTargetContext(array $nodes, string $unitOfCodeName): array
    {
        $context = [
            'node' => null,
            'namespace' => '',
            'imports' => [],
        ];

        foreach ($nodes as $node) {
            if ($node instanceof Stmt\Namespace_) {
                $nestedContext = $this->findTargetContextInNamespace($node, $unitOfCodeName);
                if ($nestedContext['node'] !== null) {
                    return $nestedContext;
                }
                continue;
            }

            if ($node instanceof Stmt\Use_ || $node instanceof Stmt\GroupUse) {
                $context['imports'] = array_merge($context['imports'], $this->parseUseImports($node));
                continue;
            }

            if ($node instanceof Stmt\ClassLike && $this->isTargetClassLike($node, '', $unitOfCodeName)) {
                $context['node'] = $node;
                return $context;
            }
        }

        return $context;
    }

    /**
     * @return array{node: Stmt\ClassLike|null, namespace: string, imports: array<string, string>}
     */
    private function findTargetContextInNamespace(Stmt\Namespace_ $namespace, string $unitOfCodeName): array
    {
        $namespaceName = $namespace->name ? $namespace->name->toString() : '';
        $imports = [];

        foreach ($namespace->stmts as $node) {
            if ($node instanceof Stmt\Use_ || $node instanceof Stmt\GroupUse) {
                $imports = array_merge($imports, $this->parseUseImports($node));
                continue;
            }

            if ($node instanceof Stmt\ClassLike && $this->isTargetClassLike($node, $namespaceName, $unitOfCodeName)) {
                return [
                    'node' => $node,
                    'namespace' => $namespaceName,
                    'imports' => $imports,
                ];
            }
        }

        return [
            'node' => null,
            'namespace' => $namespaceName,
            'imports' => $imports,
        ];
    }

    private function isTargetClassLike(Stmt\ClassLike $node, string $namespace, string $unitOfCodeName): bool
    {
        if (!$node->name instanceof Node\Identifier) {
            return false;
        }

        $name = $namespace ? $namespace . '\\' . $node->name->toString() : $node->name->toString();

        return trim($name, '\\') === trim($unitOfCodeName, '\\');
    }

    /**
     * @return array<string, string>
     */
    private function parseUseImports(Node $node): array
    {
        if ($node instanceof Stmt\Use_) {
            return $this->parseUseItems($node->uses, null, $node->type);
        }

        if ($node instanceof Stmt\GroupUse) {
            return $this->parseUseItems($node->uses, $node->prefix, $node->type);
        }

        return [];
    }

    /**
     * @param array<Node\UseItem> $useItems
     * @return array<string, string>
     */
    private function parseUseItems(array $useItems, ?Name $prefix, int $commonType): array
    {
        $imports = [];
        foreach ($useItems as $useItem) {
            $type = $useItem->type ?: $commonType;
            if ($type !== Stmt\Use_::TYPE_NORMAL) {
                continue;
            }

            $name = $useItem->name->toString();
            if ($prefix !== null) {
                $prefixedName = Name::concat($prefix, $useItem->name);
                if ($prefixedName === null) {
                    continue;
                }
                $name = $prefixedName->toString();
            }
            $imports[$useItem->getAlias()->toString()] = $name;
        }

        return $imports;
    }

    /**
     * @param array<string, bool> $dependencies
     */
    private function collectFromNode(Node $node, array &$dependencies): void
    {
        if ($node instanceof Stmt\Class_) {
            $this->addName($node->extends, $dependencies);
            foreach ($node->implements as $interface) {
                $this->addName($interface, $dependencies);
            }
        } elseif ($node instanceof Stmt\Interface_) {
            foreach ($node->extends as $interface) {
                $this->addName($interface, $dependencies);
            }
        } elseif ($node instanceof Stmt\Enum_) {
            foreach ($node->implements as $interface) {
                $this->addName($interface, $dependencies);
            }
        } elseif ($node instanceof Stmt\TraitUse) {
            foreach ($node->traits as $trait) {
                $this->addName($trait, $dependencies);
            }
            foreach ($node->adaptations as $adaptation) {
                if ($adaptation->trait instanceof Name) {
                    $this->addName($adaptation->trait, $dependencies);
                }
                if ($adaptation instanceof Stmt\TraitUseAdaptation\Precedence) {
                    foreach ($adaptation->insteadof as $insteadof) {
                        $this->addName($insteadof, $dependencies);
                    }
                }
            }
        } elseif ($node instanceof Stmt\ClassMethod || $node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction) {
            foreach ($node->params as $param) {
                $this->addType($param->type, $dependencies);
            }
            $this->addType($node->returnType, $dependencies);
        } elseif ($node instanceof Node\Param) {
            $this->addType($node->type, $dependencies);
        } elseif ($node instanceof Stmt\Property || $node instanceof Stmt\ClassConst) {
            $this->addType($node->type, $dependencies);
        } elseif ($node instanceof Stmt\Catch_) {
            foreach ($node->types as $type) {
                $this->addName($type, $dependencies);
            }
        } elseif (
            $node instanceof Expr\New_
            || $node instanceof Expr\StaticCall
            || $node instanceof Expr\StaticPropertyFetch
            || $node instanceof Expr\ClassConstFetch
            || $node instanceof Expr\Instanceof_
        ) {
            if ($node->class instanceof Name) {
                $this->addName($node->class, $dependencies);
            }
        }

        if (property_exists($node, 'attrGroups')) {
            /** @var array<Node\AttributeGroup> $attrGroups */
            $attrGroups = $node->attrGroups;
            foreach ($attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attribute) {
                    $this->addName($attribute->name, $dependencies);
                }
            }
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName;
            if ($subNode instanceof Node) {
                $this->collectFromNode($subNode, $dependencies);
            } elseif (is_array($subNode)) {
                foreach ($subNode as $item) {
                    if ($item instanceof Node) {
                        $this->collectFromNode($item, $dependencies);
                    }
                }
            }
        }
    }

    /**
     * @param Node\ComplexType|Node\Identifier|Name|null $type
     * @param array<string, bool> $dependencies
     */
    private function addType(?Node $type, array &$dependencies): void
    {
        if ($type instanceof Name) {
            $this->addName($type, $dependencies);
            return;
        }

        if ($type instanceof Node\Identifier) {
            $this->addDependency($type->toString(), $dependencies);
            return;
        }

        if ($type instanceof Node\NullableType) {
            $this->addType($type->type, $dependencies);
            return;
        }

        if ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
            foreach ($type->types as $nestedType) {
                $this->addType($nestedType, $dependencies);
            }
        }
    }

    /**
     * @param array<string, bool> $dependencies
     */
    private function addName(?Name $name, array &$dependencies): void
    {
        if ($name === null) {
            return;
        }

        $namespacedName = $name->getAttribute('namespacedName');
        $dependency = $namespacedName instanceof Name ? $namespacedName->toString() : $name->toString();
        $this->addDependency($dependency, $dependencies);
    }

    /**
     * @param array{namespace: string, imports: array<string, string>} $context
     * @param array<string, bool> $dependencies
     */
    private function collectDocBlockDependencies(Node $node, array $context, array &$dependencies): void
    {
        $docComment = $node->getDocComment();
        if ($docComment !== null) {
            foreach ($this->extractDocBlockTypes($docComment->getText()) as $type) {
                $this->addDependency($this->resolveDocBlockType($type, $context), $dependencies);
            }
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName;
            if ($subNode instanceof Node) {
                $this->collectDocBlockDependencies($subNode, $context, $dependencies);
            } elseif (is_array($subNode)) {
                foreach ($subNode as $item) {
                    if ($item instanceof Node) {
                        $this->collectDocBlockDependencies($item, $context, $dependencies);
                    }
                }
            }
        }
    }

    /**
     * @return array<string>
     */
    private function extractDocBlockTypes(string $docBlock): array
    {
        $types = [];
        preg_match_all('/@(param|return|throws|var|property(?:-read|-write)?)\s+([^\s]+)/u', $docBlock, $matches);
        foreach ($matches[2] as $type) {
            $types[] = $type;
        }

        preg_match_all('/@method\s+([^\s]+)\s+[a-zA-Z_][a-zA-Z0-9_]*\(([^)]*)\)(?::\s*([^\s]+))?/u', $docBlock, $matches);
        foreach ($matches[1] as $type) {
            $types[] = $type;
        }
        foreach ($matches[2] as $parameters) {
            preg_match_all('/([\\\\a-zA-Z_][\\\\a-zA-Z0-9_|&<>,\[\]?]*)\s+\$/u', $parameters, $parameterMatches);
            foreach ($parameterMatches[1] as $type) {
                $types[] = $type;
            }
        }
        foreach ($matches[3] as $type) {
            if ($type !== '') {
                $types[] = $type;
            }
        }

        $expandedTypes = [];
        foreach ($types as $type) {
            preg_match_all('/\\\\?[A-Z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*/u', $type, $typeMatches);
            foreach ($typeMatches[0] as $expandedType) {
                $expandedTypes[] = $expandedType;
            }
        }

        return array_unique($expandedTypes);
    }

    /**
     * @param array{namespace: string, imports: array<string, string>} $context
     */
    private function resolveDocBlockType(string $type, array $context): string
    {
        $type = trim($type, '\\');
        $parts = explode('\\', $type);
        $alias = array_shift($parts);
        if (isset($context['imports'][$alias])) {
            return trim($context['imports'][$alias] . (empty($parts) ? '' : '\\' . implode('\\', $parts)), '\\');
        }

        if (count($parts) > 0 || $context['namespace'] === '') {
            return $type;
        }

        return trim($context['namespace'] . '\\' . $type, '\\');
    }

    /**
     * @param array<string, bool> $dependencies
     */
    private function addDependency(string $dependency, array &$dependencies): void
    {
        $dependency = $this->dependencyNameNormalizer->normalize($dependency);
        if ($dependency === null) {
            return;
        }

        $dependencies[$dependency] = true;
    }
}
