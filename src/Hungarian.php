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
            throw new InvalidMatrixException(
                "Invalid mode \"{$mode}\". Use Hungarian::MODE_MINIMIZE or Hungarian::MODE_MAXIMIZE.",
            );
        }
    }
}
