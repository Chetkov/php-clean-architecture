<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis;

/**
 * @template T
 * @implements \Iterator<\Iterator<mixed, T>>
 */
class CompositeCountableIterator implements \Iterator, \Countable
{
    /** @var array<\Iterator<mixed, T>> */
    private $iterators;

    /** @var \Iterator<mixed, T>|null */
    private $currentIterator;

    /**
     * @param \Iterator<mixed, T> ...$iterators
     */
    public function __construct(\Iterator ...$iterators)
    {
        $this->iterators = $iterators;
    }

    /**
     * @return T
     */
    public function current()
    {
        return $this->getCurrentIterator()->current();
    }

    public function next(): void
    {
        $this->getCurrentIterator()->next();
        if (!$this->getCurrentIterator()->valid()) {
            if (next($this->iterators) !== false) {
                $this->currentIterator = current($this->iterators) ?: null;
            }
        }
    }

    /**
     * @return bool|float|int|string|null
     */
    public function key()
    {
        return $this->getCurrentIterator()->key();
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        if (!$this->currentIterator instanceof \Iterator || !$this->currentIterator->valid()) {
            if (next($this->iterators) !== false) {
                $this->currentIterator = current($this->iterators) ?: null;
            }
        }

        return $this->currentIterator instanceof \Iterator && $this->currentIterator->valid();
    }

    public function rewind(): void
    {
        $this->currentIterator = null;
        if (!empty($this->iterators)) {
            foreach ($this->iterators as $iterator) {
                $iterator->rewind();
            }
            $this->currentIterator = reset($this->iterators);
        }
    }

    /**
     * @return \Iterator<mixed, T>
     */
    private function getCurrentIterator(): \Iterator
    {
        if (!$this->currentIterator instanceof \Iterator) {
            throw new \RuntimeException('$this->currentIterator must be of type \Iterator');
        }
        return $this->currentIterator;
    }

    /**
     * @param \Iterator<mixed, T> $iterator
     * @return $this
     */
    public function addIterator(\Iterator $iterator): self
    {
        $this->iterators[] = $iterator;
        return $this;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        $count = 0;
        foreach ($this as $_) {
            $count++;
        }
        return $count;
    }
}
