<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Config;

final class AllowedStateStorageContext
{
    /** @var string|null */
    private $rootStorage;

    /** @var array<string> */
    private $rootIdParts;

    /**
     * @param array<string> $rootIdParts
     */
    public function __construct(?string $rootStorage, array $rootIdParts)
    {
        $this->rootStorage = $rootStorage;
        $this->rootIdParts = $rootIdParts;
    }

    public function rootStorage(): ?string
    {
        return $this->rootStorage;
    }

    /**
     * @return array<string>
     */
    public function rootIdParts(): array
    {
        return $this->rootIdParts;
    }
}
