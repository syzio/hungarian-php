<?php

declare(strict_types=1);

namespace Oizys\Hungarian;

/**
 * Immutable result of a Hungarian algorithm solve.
 */
class Result
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
}
