<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model;

trait CachingTrait
{
    /** @var array<string, mixed> */
    private $cache = [];

    /**
     * @param string $key
     * @return mixed|null
     */
    private function get(string $key)
    {
        return $this->cache[$key] ?? null;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    private function set(string $key, $value): void
    {
        $this->cache[$key] = $value;
    }

    /**
     * @param string $key
     * @param callable $callable
     * @return mixed
     */
    private function execWithCache(string $key, callable $callable)
    {
        $result = $this->get($key);
        if ($result === null) {
            $result = $callable();
            $this->set($key, $result);
        }
        return $result;
    }
}
