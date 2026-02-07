<?php

declare(strict_types=1);

namespace Oizys\Hungarian;

/**
 * Kuhn-Munkres (Hungarian) algorithm for the assignment problem.
 *
 * Solves the problem of optimally assigning workers to jobs given
 * a cost matrix, in O(n^3) time. Supports square and rectangular matrices,
 * and INF values to mark forbidden assignments.
 */
class Hungarian
{
    public const string MODE_MINIMIZE = 'minimize';

    public const string MODE_MAXIMIZE = 'maximize';

    /**
     * @throws InvalidMatrixException If mode is invalid.
     */
    public function __construct(private readonly string $mode = self::MODE_MINIMIZE)
    {
        if ($mode !== self::MODE_MINIMIZE && $mode !== self::MODE_MAXIMIZE) {
            throw new InvalidMatrixException("Invalid mode \"{$mode}\". Use Hungarian::MODE_MINIMIZE or Hungarian::MODE_MAXIMIZE.");
        }
    }

    /**
     * Solve the assignment problem for the given cost matrix.
     *
     * All working memory is local to this method — no state is retained
     * between calls, making this safe for long-running processes.
     *
     * @param  array<int, array<int, int|float>>  $matrix  Cost matrix (square or rectangular).
     *
     * @throws InvalidMatrixException If the matrix is invalid.
     */
    public function solve(array $matrix): Result
    {
        $this->validate($matrix);

        $rows = count($matrix);

        if ($rows === 0) {
            return new Result([], 0);
        }

        $cols = count($matrix[0]);

        // Keep a reference to the original for cost calculation and INF detection.
        $original = $matrix;

        // Replace INF with a large finite sentinel to avoid NaN in arithmetic.
        $matrix = $this->sanitize($matrix, $rows, $cols);

        // For maximization, negate the matrix so we can always minimize.
        if ($this->mode === self::MODE_MAXIMIZE) {
            $matrix = $this->negate($matrix);
        }

        // Pad to square if rectangular.
        $n = max($rows, $cols);
        if ($rows !== $cols) {
            $matrix = $this->pad($matrix, $rows, $cols, $n);
        }

        $raw = $this->munkres($matrix, $n);

        // Filter out dummy rows/columns and forbidden (INF) assignments.
        $assignments = [];
        $cost = 0;
        foreach ($raw as $pair) {
            $r = $pair[0];
            $c = $pair[1];

            if ($r >= $rows || $c >= $cols) {
                continue;
            }

            if (is_infinite($original[$r][$c])) {
                continue;
            }

            $assignments[] = $pair;
            $cost += $original[$r][$c];
        }

        return new Result($assignments, $cost);
    }

    /**
     * Core Kuhn-Munkres algorithm — O(n^3).
     *
     * Uses the potential (dual variable) formulation with augmenting paths.
     * All arrays are local — nothing is stored on $this.
     *
     * @param  array<int, array<int, int|float>>  $matrix
     * @return array<int, array{int, int}>
     */
    private function munkres(array $matrix, int $n): array
    {
        $u = array_fill(0, $n + 1, 0);
        $v = array_fill(0, $n + 1, 0);
        $p = array_fill(0, $n + 1, 0);
        $way = array_fill(0, $n + 1, 0);

        for ($i = 1; $i <= $n; $i++) {
            $minv = array_fill(0, $n + 1, INF);
            $used = array_fill(0, $n + 1, false);

            $p[0] = $i;
            $j0 = 0;

            do {
                $used[$j0] = true;
                $i0 = $p[$j0];
                $delta = INF;
                $j1 = 0;

                for ($j = 1; $j <= $n; $j++) {
                    if ($used[$j]) {
                        continue;
                    }

                    $cur = $matrix[$i0 - 1][$j - 1] - $u[$i0] - $v[$j];

                    if ($cur < $minv[$j]) {
                        $minv[$j] = $cur;
                        $way[$j] = $j0;
                    }

                    if ($minv[$j] < $delta) {
                        $delta = $minv[$j];
                        $j1 = $j;
                    }
                }

                for ($j = 0; $j <= $n; $j++) {
                    if ($used[$j]) {
                        $u[$p[$j]] += $delta;
                        $v[$j] -= $delta;
                    } else {
                        $minv[$j] -= $delta;
                    }
                }

                $j0 = $j1;
            } while ($p[$j0] !== 0);

            do {
                $j1 = $way[$j0];
                $p[$j0] = $p[$j1];
                $j0 = $j1;
            } while ($j0 !== 0);
        }

        $result = [];
        for ($j = 1; $j <= $n; $j++) {
            $result[] = [$p[$j] - 1, $j - 1];
        }

        usort($result, static fn ($a, $b) => $a[0] - $b[0]);

        return $result;
    }

    /**
     * Replace INF values with a large finite sentinel so arithmetic stays valid.
     * Sentinel = (sum of absolute finite values + 1) * max dimension.
     *
     * @param  array<int, array<int, int|float>>  $matrix
     * @return array<int, array<int, int|float>>
     */
    private function sanitize(array $matrix, int $rows, int $cols): array
    {
        $infinite = false;
        $sum = 0;

        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                if (is_infinite($matrix[$i][$j])) {
                    $infinite = true;
                } else {
                    $sum += abs($matrix[$i][$j]);
                }
            }
        }

        if (! $infinite) {
            return $matrix;
        }

        $sentinel = ($sum + 1) * max($rows, $cols);

        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                if (is_infinite($matrix[$i][$j])) {
                    $matrix[$i][$j] = $sentinel;
                }
            }
        }

        return $matrix;
    }

    /**
     * Pad a rectangular matrix to square with zero-cost dummy cells.
     *
     * @param  array<int, array<int, int|float>>  $matrix
     * @return array<int, array<int, int|float>>
     */
    private function pad(array $matrix, int $rows, int $cols, int $n): array
    {
        if ($cols < $n) {
            for ($i = 0; $i < $rows; $i++) {
                for ($j = $cols; $j < $n; $j++) {
                    $matrix[$i][$j] = 0;
                }
            }
        }

        if ($rows < $n) {
            $filler = array_fill(0, $n, 0);
            for ($i = $rows; $i < $n; $i++) {
                $matrix[$i] = $filler;
            }
        }

        return $matrix;
    }

    /**
     * Validate matrix structure: sequential keys, consistent row lengths, numeric values.
     *
     * @param  array<mixed>  $matrix
     *
     * @throws InvalidMatrixException
     */
    private function validate(array $matrix): void
    {
        $rows = count($matrix);

        if ($rows === 0) {
            return;
        }

        if (! array_is_list($matrix)) {
            throw new InvalidMatrixException('Matrix must be a sequential integer-indexed array.');
        }

        $cols = null;
        foreach ($matrix as $ri => $row) {
            if (! is_array($row)) {
                $type = gettype($row);
                throw new InvalidMatrixException("Row {$ri} must be an array, got {$type}.");
            }

            $length = count($row);

            if ($length === 0) {
                throw new InvalidMatrixException("Row {$ri} must not be empty.");
            }

            if (! array_is_list($row)) {
                throw new InvalidMatrixException("Row {$ri} must be a sequential integer-indexed array.");
            }

            if ($cols === null) {
                $cols = $length;
            } elseif ($length !== $cols) {
                throw new InvalidMatrixException("All rows must have the same number of columns. Row 0 has {$cols} columns, row {$ri} has {$length}.");
            }

            foreach ($row as $ci => $value) {
                if (! is_int($value) && ! is_float($value)) {
                    $type = gettype($value);
                    throw new InvalidMatrixException("Value at [{$ri}][{$ci}] must be int or float, got {$type}.");
                }
            }
        }
    }

    /**
     * @param  array<int, array<int, int|float>>  $matrix
     * @return array<int, array<int, int|float>>
     */
    private function negate(array $matrix): array
    {
        $negated = [];
        foreach ($matrix as $i => $row) {
            $negated[$i] = [];
            foreach ($row as $j => $value) {
                $negated[$i][$j] = -$value;
            }
        }

        return $negated;
    }
}
