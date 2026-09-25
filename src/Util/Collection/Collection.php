<?php

declare(strict_types=1);

namespace App\Util\Collection;

/**
 * @template T
 * @implements \IteratorAggregate<int, T>
 */
abstract class Collection implements \IteratorAggregate, \Countable
{
    /** @var array<int, T> */
    protected array $items;

    /**
     * @param iterable<int, T> $items
     */
    final public function __construct(iterable $items)
    {
        $items = $items instanceof \Traversable ? iterator_to_array($items) : $items;
        $this->items = $items;
    }

    /**
     * @return array<int, T>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return count($this->items) < 1;
    }

    /**
     * @param callable(T): bool $filter
     */
    public function filter(callable $filter): static
    {
        return new static(array_filter($this->items, $filter));
    }

    /**
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
