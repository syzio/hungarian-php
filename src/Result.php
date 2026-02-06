<?php

declare(strict_types=1);

namespace Oizys\Hungarian;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Stringable;
use Traversable;

/**
 * Immutable result of a Hungarian algorithm solve.
 *
 * @implements IteratorAggregate<int, array{int, int}>
 */
class Result implements Countable, IteratorAggregate, JsonSerializable, Stringable
{
    /**
     * @param  array<int, array{int, int}>  $assignments
     */
    public function __construct(
        private readonly array $assignments,
        private readonly int|float $cost,
    ) {}

    /**
     * Row-to-column assignment pairs, sorted by row index.
     * Each entry is [rowIndex, columnIndex].
     *
     * @return array<int, array{int, int}>
     */
    public function assignments(): array
    {
        return $this->assignments;
    }

    /**
     * The optimal total cost of the assignment.
     */
    public function cost(): int|float
    {
        return $this->cost;
    }

    /**
     * Get as a simple row => column map.
     *
     * @return array<int, int>
     */
    public function map(): array
    {
        $map = [];
        foreach ($this->assignments as $pair) {
            $map[$pair[0]] = $pair[1];
        }

        return $map;
    }

    public function count(): int
    {
        return count($this->assignments);
    }

    /**
     * @return Traversable<int, array{int, int}>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->assignments);
    }

    /**
     * @return array{assignments: array<int, array{int, int}>, cost: int|float}
     */
    public function jsonSerialize(): array
    {
        return [
            'assignments' => $this->assignments,
            'cost' => $this->cost,
        ];
    }

    public function __toString(): string
    {
        $n = count($this->assignments);

        return "{$n} assignments, cost: {$this->cost}";
    }
}
