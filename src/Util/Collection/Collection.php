<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Util\Collection;

abstract class Collection implements \IteratorAggregate, \Countable
{
    protected array $items;

    final public function __construct(iterable $items)
    {
        $items = $items instanceof \Traversable ? iterator_to_array($items) : $items;
        $this->items = $items;
    }

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

    public function filter(callable $filter): static
    {
        return new static(array_filter($this->items, $filter));
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
