<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Config;

final class EffectiveConfigNode
{
    /** @var string */
    private $id;

    /** @var string */
    private $title;

    /** @var string */
    private $reportPath;

    /** @var array<string, mixed> */
    private $config;

    /** @var array<EffectiveConfigNode> */
    private $children;

    /**
     * @param array<string, mixed> $config
     * @param array<EffectiveConfigNode> $children
     */
    public function __construct(string $id, string $title, string $reportPath, array $config, array $children)
    {
        $this->id = $id;
        $this->title = $title;
        $this->reportPath = $reportPath;
        $this->config = $config;
        $this->children = $children;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function reportPath(): string
    {
        return $this->reportPath;
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->config;
    }

    /**
     * @return array<EffectiveConfigNode>
     */
    public function children(): array
    {
        return $this->children;
    }

    /**
     * @return array<EffectiveConfigNode>
     */
    public function flatten(): array
    {
        $nodes = [$this];
        foreach ($this->children as $child) {
            array_push($nodes, ...$child->flatten());
        }

        return $nodes;
    }
}
