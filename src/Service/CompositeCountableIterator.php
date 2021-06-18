<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service;

class CompositeCountableIterator implements \Iterator, \Countable
{
    /** @var \Iterator[] */
    private $iterators;

    /** @var \Iterator|null */
    private $currentIterator;

    /**
     * @param \Iterator ...$iterators
     */
    public function __construct(\Iterator ...$iterators)
    {
        $this->iterators = $iterators;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->getCurrentIterator()->current();
    }

    public function next(): void
    {
        $this->getCurrentIterator()->next();
        if (!$this->getCurrentIterator()->valid()) {
            $this->currentIterator = next($this->iterators) !== false ? current($this->iterators) : null;
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
     * @return \Iterator
     */
    private function getCurrentIterator(): \Iterator
    {
        if (!$this->currentIterator instanceof \Iterator) {
            throw new \RuntimeException('$this->currentIterator must be of type \Iterator');
        }
        return $this->currentIterator;
    }

    /**
     * @param \Iterator $iterator
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
