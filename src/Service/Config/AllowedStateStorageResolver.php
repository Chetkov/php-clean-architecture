<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Config;

final class AllowedStateStorageResolver
{
    /** @var ConfigTreePathResolver */
    private $pathResolver;

    public function __construct(ConfigTreePathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function configuredStorage(array $config): ?string
    {
        $storage = $config['exclusions']['allowed_state']['storage'] ?? null;
        return is_string($storage) && $storage !== '' ? $storage : null;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $rawSubConfig
     * @param array<string> $childIdParts
     *
     * @return AllowedStateStorageContext
     */
    public function configureChildStorage(
        array &$config,
        array $rawSubConfig,
        array $childIdParts,
        AllowedStateStorageContext $inheritedContext
    ): AllowedStateStorageContext {
        $explicitStorage = $this->explicitStorage($rawSubConfig);
        if ($explicitStorage !== null) {
            return new AllowedStateStorageContext($explicitStorage === '' ? null : $explicitStorage, $childIdParts);
        }

        $inheritedRootStorage = $inheritedContext->rootStorage();
        if ($inheritedRootStorage === null || $this->configuredStorage($config) === null) {
            return new AllowedStateStorageContext($this->configuredStorage($config), $inheritedContext->rootIdParts());
        }

        $relativeIdParts = array_slice($childIdParts, count($inheritedContext->rootIdParts()));
        $config['exclusions']['allowed_state']['storage'] = $this->pathResolver->childAllowedStateStoragePath(
            $inheritedRootStorage,
            $relativeIdParts
        );

        return $inheritedContext;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function explicitStorage(array $config): ?string
    {
        if (!array_key_exists('exclusions', $config) || !is_array($config['exclusions'])) {
            return null;
        }

        $allowedState = $config['exclusions']['allowed_state'] ?? null;
        if (!is_array($allowedState) || !array_key_exists('storage', $allowedState)) {
            return null;
        }

        $storage = $allowedState['storage'];
        return is_string($storage) ? $storage : null;
    }
}
