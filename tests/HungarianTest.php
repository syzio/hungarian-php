<?php

declare(strict_types=1);

use Oizys\Hungarian\Hungarian;
use Oizys\Hungarian\InvalidMatrixException;
use Oizys\Hungarian\Result;

// ---------------------------------------------------------------
// Core algorithm
// ---------------------------------------------------------------

test('classic 4x4 example', function (): void {
    $result = (new Hungarian)->solve([
        [82, 83, 69, 92],
        [77, 37, 49, 92],
        [11, 69, 5, 86],
        [8, 9, 98, 23],
    ]);

    expect($result)->toBeInstanceOf(Result::class);
    expect($result->cost())->toBe(140);

    $map = $result->map();
    expect($map)->toHaveCount(4);
    expect(array_keys($map))->toBe([0, 1, 2, 3]);

    $cols = array_values($map);
    sort($cols);
    expect($cols)->toBe([0, 1, 2, 3]);
});

test('1x1 matrix', function (): void {
    $result = (new Hungarian)->solve([[42]]);

    expect($result->cost())->toBe(42);
    expect($result->assignments())->toBe([[0, 0]]);
});

test('2x2 matrix', function (): void {
    $result = (new Hungarian)->solve([[1, 2], [3, 4]]);

    expect($result->cost())->toBe(5);
});

test('3x3 matrix', function (): void {
    $result = (new Hungarian)->solve([[10, 5, 13], [3, 7, 2], [8, 12, 6]]);

    expect($result->cost())->toBe(14);
});

test('5x5 matrix', function (): void {
    $result = (new Hungarian)->solve([
        [7, 53, 183, 439, 863],
        [497, 383, 563, 79, 973],
        [287, 63, 343, 169, 583],
        [627, 343, 773, 959, 943],
        [767, 473, 103, 699, 303],
    ]);

    expect($result->cost())->toBe(1075);
    expect($result->map())->toHaveCount(5);

    $cols = array_values($result->map());
    sort($cols);
    expect($cols)->toBe([0, 1, 2, 3, 4]);
});

test('all zeros', function (): void {
    $result = (new Hungarian)->solve([[0, 0, 0], [0, 0, 0], [0, 0, 0]]);

    expect($result->cost())->toBe(0);
});

test('identity-like matrix', function (): void {
    $result = (new Hungarian)->solve([[0, 100, 100], [100, 0, 100], [100, 100, 0]]);

    expect($result->cost())->toBe(0);
    expect($result->map())->toBe([0 => 0, 1 => 1, 2 => 2]);
});

test('float values', function (): void {
    $result = (new Hungarian)->solve([[1.5, 2.7], [3.1, 0.8]]);

    expect($result->cost())->toEqualWithDelta(2.3, 1e-10);
});

test('negative values', function (): void {
    $result = (new Hungarian)->solve([[-1, -2], [-3, -4]]);

    expect($result->cost())->toBe(-5);
});

// ---------------------------------------------------------------
// Maximization
// ---------------------------------------------------------------

test('maximize 3x3', function (): void {
    $result = (new Hungarian(Hungarian::MODE_MAXIMIZE))->solve([
        [10, 5, 13],
        [3, 7, 2],
        [8, 12, 6],
    ]);

    expect($result->cost())->toBe(28);
});

test('maximize 2x2', function (): void {
    $result = (new Hungarian(Hungarian::MODE_MAXIMIZE))->solve([[1, 2], [3, 4]]);

    expect($result->cost())->toBe(5);
});

// ---------------------------------------------------------------
// Empty matrix
// ---------------------------------------------------------------

test('empty matrix', function (): void {
    $result = (new Hungarian)->solve([]);

    expect($result->cost())->toBe(0);
    expect($result->assignments())->toBe([]);
    expect($result->map())->toBe([]);
});

// ---------------------------------------------------------------
// GC safety
// ---------------------------------------------------------------

test('multiple solves on same instance', function (): void {
    $h = new Hungarian;

    $r1 = $h->solve([[1, 2], [3, 4]]);
    $r2 = $h->solve([[10, 5, 13], [3, 7, 2], [8, 12, 6]]);
    $r3 = $h->solve([[99]]);

    expect($r1->cost())->toBe(5);
    expect($r2->cost())->toBe(14);
    expect($r3->cost())->toBe(99);
});

// ---------------------------------------------------------------
// Result object
// ---------------------------------------------------------------

test('result map', function (): void {
    $result = (new Hungarian)->solve([[0, 100, 100], [100, 0, 100], [100, 100, 0]]);

    expect($result->map())
        ->toBeArray()
        ->toBe([0 => 0, 1 => 1, 2 => 2]);
});

test('result countable', function (): void {
    $result = (new Hungarian)->solve([[0, 100, 100], [100, 0, 100], [100, 100, 0]]);

    expect($result)->toHaveCount(3);
    expect(count($result))->toBe(3);
});

test('result iterable', function (): void {
    $result = (new Hungarian)->solve([[0, 100], [100, 0]]);

    $pairs = [];
    foreach ($result as $pair) {
        $pairs[] = $pair;
    }

    expect($pairs)->toBe($result->assignments());
});

test('result stringable', function (): void {
    $result = (new Hungarian)->solve([[0, 100, 100], [100, 0, 100], [100, 100, 0]]);

    expect((string) $result)->toBe('3 assignments, cost: 0');
});

test('result json serializable', function (): void {
    $result = (new Hungarian)->solve([[1, 2], [3, 4]]);

    $decoded = json_decode(json_encode($result), true);

    expect($decoded)->toHaveKeys(['assignments', 'cost']);
    expect($decoded['cost'])->toBe($result->cost());
    expect($decoded['assignments'])->toBe($result->assignments());
});

test('empty result interfaces', function (): void {
    $result = (new Hungarian)->solve([]);

    expect($result)->toHaveCount(0);
    expect((string) $result)->toBe('0 assignments, cost: 0');
    expect(json_encode($result))->toBe('{"assignments":[],"cost":0}');
});

test('assignments sorted by row', function (): void {
    $result = (new Hungarian)->solve([
        [82, 83, 69, 92],
        [77, 37, 49, 92],
        [11, 69, 5, 86],
        [8, 9, 98, 23],
    ]);

    $rows = array_column($result->assignments(), 0);
    $sorted = $rows;
    sort($sorted);

    expect($rows)->toBe($sorted);
});

// ---------------------------------------------------------------
// Rectangular matrices
// ---------------------------------------------------------------

test('tall 2x1 matrix', function (): void {
    $result = (new Hungarian)->solve([[0], [11]]);

    expect($result->cost())->toBe(0);
    expect($result->map())->toBe([0 => 0]);
});

test('wide 1x2 matrix', function (): void {
    $result = (new Hungarian)->solve([[0, 1]]);

    expect($result->cost())->toBe(0);
    expect($result->map())->toBe([0 => 0]);
});

test('tall 3x2 matrix', function (): void {
    $result = (new Hungarian)->solve([[5, 10], [1, 8], [7, 3]]);

    expect($result->cost())->toBe(4);
    expect($result)->toHaveCount(2);
});

test('wide 2x3 matrix', function (): void {
    $result = (new Hungarian)->solve([[5, 1, 7], [10, 8, 3]]);

    expect($result->cost())->toBe(4);
    expect($result)->toHaveCount(2);
});

// ---------------------------------------------------------------
// INF (forbidden cells)
// ---------------------------------------------------------------

test('INF forbidden cells', function (): void {
    $result = (new Hungarian)->solve([
        [10, 2, INF, 15],
        [15, INF, INF, 2],
        [1, INF, INF, 4],
        [2, INF, INF, 10],
    ]);

    expect($result->cost())->toBe(5);
    expect($result)->toHaveCount(3);
    expect(is_infinite($result->cost()))->toBeFalse();
});

test('all INF 1x1', function (): void {
    $result = (new Hungarian)->solve([[INF]]);

    expect($result->cost())->toBe(0);
    expect($result)->toHaveCount(0);
});

// ---------------------------------------------------------------
// Reference test cases
// ---------------------------------------------------------------

test('reference case 1', function (): void {
    $result = (new Hungarian)->solve([
        [1, 2, 3, 0, 1],
        [0, 2, 3, 12, 1],
        [3, 0, 1, 13, 1],
        [3, 1, 1, 12, 0],
        [3, 1, 1, 12, 0],
    ]);

    expect($result->cost())->toBe(1);
});

test('reference case 2', function (): void {
    $result = (new Hungarian)->solve([
        [0, 2, 0, 0, 1],
        [0, 3, 12, 1, 1],
        [3, 1, 1, 13, 1],
        [3, 1, 1, 12, 0],
        [3, 1, 1, 12, 0],
    ]);

    expect($result->cost())->toBe(2);
});

test('reference 10x10 case 3', function (): void {
    $result = (new Hungarian)->solve([
        [-3, -3, -3, -3, -2, -2, -2, -2, -99, -99],
        [-3, -3, -3, -3, -5, -5, -5, -5, -2, -99],
        [-2, -2, -2, -2, -5, -5, -5, -5, -3, -99],
        [-2, -2, -2, -2, -5, -5, -5, -5, -99, -3],
        [-3, -3, -3, -3, -2, -2, -2, -2, -99, -5],
        [-4, -4, -4, -4, -3, -3, -3, -3, -1, -99],
        [-4, -4, -4, -4, -3, -3, -3, -3, -99, -1],
        [-4, -4, -4, -4, -1, -1, -1, -1, -99, -99],
        [-1, -1, -1, -1, -3, -3, -3, -3, -6, -99],
        [-3, -3, -3, -3, -1, -1, -1, -1, -99, -6],
    ]);

    expect($result->cost())->toBe(-231);
});

test('reference 10x10 case 4', function (): void {
    $result = (new Hungarian)->solve([
        [-2, -2, -2, -2, -5, -5, -5, -5, -3, -99],
        [-2, -2, -2, -2, -5, -5, -5, -5, -99, -3],
        [-2, -2, -2, -2, -3, -3, -3, -3, -99, -99],
        [-3, -3, -3, -3, -5, -5, -5, -5, -8, -2],
        [-2, -2, -2, -2, -3, -3, -3, -3, -99, -8],
        [-3, -3, -3, -3, -1, -1, -1, -1, -99, -4],
        [-1, -1, -1, -1, -3, -3, -3, -3, -99, -99],
        [-3, -3, -3, -3, -1, -1, -1, -1, -6, -99],
        [-3, -3, -3, -3, -1, -1, -1, -1, -99, -6],
        [-1, -1, -1, -1, -3, -3, -3, -3, -7, -99],
    ]);

    expect($result->cost())->toBe(-227);
});

test('reference 10x10 case 5', function (): void {
    $result = (new Hungarian)->solve([
        [-5, -5, -5, -5, -3, -3, -3, -3, -6, -2],
        [-2, -2, -2, -2, -3, -3, -3, -3, -99, -6],
        [-3, -3, -3, -3, -2, -2, -2, -2, -99, -99],
        [-2, -2, -2, -2, -3, -3, -3, -3, -11, -5],
        [-3, -3, -3, -3, -2, -2, -2, -2, -99, -11],
        [-3, -3, -3, -3, -4, -4, -4, -4, -1, -7],
        [-4, -4, -4, -4, -1, -1, -1, -1, -3, -99],
        [-3, -3, -3, -3, -4, -4, -4, -4, -9, -1],
        [-1, -1, -1, -1, -4, -4, -4, -4, -99, -9],
        [-4, -4, -4, -4, -1, -1, -1, -1, -10, -3],
    ]);

    expect($result->cost())->toBe(-229);
});

// ---------------------------------------------------------------
// Validation
// ---------------------------------------------------------------

test('empty row throws', function (): void {
    (new Hungarian)->solve([[]]);
})->throws(InvalidMatrixException::class, 'must not be empty');

test('non-array row throws', function (): void {
    (new Hungarian)->solve([[1, 2], 'not an array']);
})->throws(InvalidMatrixException::class, 'must be an array');

test('non-numeric value throws', function (): void {
    (new Hungarian)->solve([[1, 'two'], [3, 4]]);
})->throws(InvalidMatrixException::class, 'int or float');

test('associative row keys throw', function (): void {
    (new Hungarian)->solve(['a' => [1, 2], 'b' => [3, 4]]);
})->throws(InvalidMatrixException::class, 'sequential');

test('associative column keys throw', function (): void {
    (new Hungarian)->solve([['x' => 1, 'y' => 2], ['x' => 3, 'y' => 4]]);
})->throws(InvalidMatrixException::class, 'sequential');

test('jagged matrix throws', function (): void {
    (new Hungarian)->solve([[1, 2, 3], [4, 5, 6], [7, 8]]);
})->throws(InvalidMatrixException::class, 'same number of columns');

test('invalid mode throws', function (): void {
    new Hungarian('invalid');
})->throws(\InvalidArgumentException::class, 'Invalid mode');
