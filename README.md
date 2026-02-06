# Hungarian

Kuhn-Munkres (Hungarian) algorithm for the assignment problem in PHP. Solves optimal assignment in O(n^3) time.

## Installation

```bash
composer require oizys/hungarian
```

## Usage

```php
use Oizys\Hungarian\Hungarian;

$solver = new Hungarian;

$result = $solver->solve([
    [82, 83, 69, 92],
    [77, 37, 49, 92],
    [11, 69,  5, 86],
    [ 8,  9, 98, 23],
]);

$result->cost();        // 140
$result->assignments(); // [[0, 2], [1, 1], [2, 0], [3, 3]]
$result->map();         // [0 => 2, 1 => 1, 2 => 0, 3 => 3]

count($result);         // 4
json_encode($result);   // {"assignments":[[0,2],[1,1],[2,0],[3,3]],"cost":140}
(string) $result;       // "4 assignments, cost: 140"

foreach ($result as $pair) {
    // [rowIndex, columnIndex]
}
```

`Result` implements `Countable`, `IteratorAggregate`, `JsonSerializable`, and `Stringable`.

### Maximization

```php
$solver = new Hungarian(Hungarian::MODE_MAXIMIZE);
```

### Rectangular matrices

Matrices don't need to be square. Extra rows or columns are left unassigned automatically.

### Forbidden assignments

Use `INF` to mark cells that must not be assigned:

```php
$result = $solver->solve([
    [10,  2, INF, 15],
    [15, INF, INF,  2],
    [ 1, INF, INF,  4],
    [ 2, INF, INF, 10],
]);
```

## Requirements

PHP 8.3+

## License

MIT
