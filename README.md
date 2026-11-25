# GLPDO2

[![CI](https://github.com/ellisgl/GeekLab-GLPDO2/actions/workflows/ci.yml/badge.svg)](https://github.com/ellisgl/GeekLab-GLPDO2/actions/workflows/ci.yml)
[![Packagist Version](https://img.shields.io/packagist/v/geeklab/glpdo2)](https://packagist.org/packages/geeklab/glpdo2)
[![PHP Version](https://img.shields.io/packagist/dependency-v/geeklab/glpdo2/php)](https://packagist.org/packages/geeklab/glpdo2)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](https://phpstan.org/)
[![License: BSD-3-Clause](https://img.shields.io/badge/License-BSD--3--Clause-blue.svg)](LICENSE.BSD)

**A small PDO wrapper that keeps every value next to its placeholder, and binds it with the right type.**

You still write plain SQL. GLPDO2 removes the bookkeeping around it: matching placeholders to values, picking `PDO::PARAM_*` types, validating input, building `IN (...)` lists, and seeing the final query when something goes wrong.

```php
$users = $db->selectRows(
    $statement
        ->sql('SELECT `id`, `name`')
        ->sql('FROM   `users`')
        ->sql('WHERE  `active` = ?')->bBool(true)
        ->sql('AND    `role` IN (%%)')->bStrArr(['admin', 'editor'])
        ->sql('AND    `created` >= ?')->bDate('2026-01-01')
        ->sql('ORDER BY `name`')
        ->sql('LIMIT  ?')->bInt(10)
);
```

## Contents

- [Why GLPDO2](#why-glpdo2)
- [Installation](#installation)
- [Quick start](#quick-start)
- [How statements work](#how-statements-work)
- [Binding methods](#binding-methods)
- [Query methods](#query-methods)
- [Security](#security)
- [Custom bindings](#custom-bindings)
- [Limitations](#limitations)
- [Development](#development)

## Why GLPDO2

The same query with plain PDO:

```php
$sql = 'SELECT `id`, `name` FROM `users`
        WHERE `active` = :active AND `role` IN (:role0, :role1)
        AND `created` >= :created ORDER BY `name` LIMIT :limit';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':active', true, PDO::PARAM_BOOL);
$stmt->bindValue(':role0', 'admin');
$stmt->bindValue(':role1', 'editor');
$stmt->bindValue(':created', '2026-01-01');
$stmt->bindValue(':limit', 10, PDO::PARAM_INT); // forget PARAM_INT and LIMIT breaks
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

With GLPDO2:

- **Values sit next to their placeholders.** Adding or removing a condition is a one-line change, with no list of names to keep in sync.
- **Each binding knows its type.** `bInt()` binds an integer and rejects `'abc'`. `bBool()` binds a boolean. `bDate()` checks for `YYYY-MM-DD`.
- **`IN (...)` lists are one call.** `bStrArr()` binds each value as its own parameter, and `bIntArray()` builds a list of integers.
- **You can see the final SQL.** `getComputed()` returns the query with the values filled in, for logging and debugging.
- **It's still PDO.** Bring your own connection, keep your own SQL, and drop down to PDO whenever you want.

## Installation

```bash
composer require geeklab/glpdo2
```

Requires PHP 8.1 or newer with the `pdo_mysql` extension. The bindings are written for MySQL and MariaDB.

## Quick start

The examples use this table:

```sql
CREATE TABLE `users` (
    `id`       INT AUTO_INCREMENT PRIMARY KEY,
    `name`     VARCHAR(100) NOT NULL,
    `role`     VARCHAR(20)  NOT NULL,
    `active`   BOOLEAN      NOT NULL DEFAULT TRUE,
    `settings` JSON         NULL,
    `created`  DATE         NOT NULL
);
```

### Connect

```php
use GeekLab\GLPDO2\GLPDO2;
use GeekLab\GLPDO2\Statement;
use GeekLab\GLPDO2\Bindings\MySQL\MySQLBindingFactory;

$pdo = new PDO('mysql:host=localhost;dbname=app;charset=utf8mb4', 'user', 'secret', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$db = new GLPDO2($pdo);
$bindings = MySQLBindingFactory::build();
```

Create a `Statement` for each query, or reuse one with `reset()`.

### Insert

```php
$id = $db->queryInsert(
    (new Statement($bindings))
        ->sql('INSERT INTO `users` (`name`, `role`, `settings`, `created`)')
        ->sql('VALUES (?, ?, ?, ?)')
            ->bStr('Ada')
            ->bStr('admin')
            ->bJSON('{"theme": "dark"}')
            ->bDate('2026-03-14')
);
// $id is the new row's ID, as a string: "1"
```

### Select

```php
// Many rows
$admins = $db->selectRows(
    (new Statement($bindings))
        ->sql('SELECT `id`, `name` FROM `users`')
        ->sql('WHERE `role` = ? AND `active` = ?')->bStr('admin')->bBool(true)
);
// [['id' => 1, 'name' => 'Ada'], ...]

// One row, or false if there's none
$user = $db->selectRow(
    (new Statement($bindings))
        ->sql('SELECT * FROM `users` WHERE `id` = ?')->bInt($id)
);

// One column of the first row, or a default
$name = $db->selectValue(
    (new Statement($bindings))->sql('SELECT `name` FROM `users` WHERE `id` = ?')->bInt($id),
    'name',
    default: 'unknown'
);
```

### Update and delete

```php
$changed = $db->queryUpdate(
    (new Statement($bindings))
        ->sql('UPDATE `users` SET `active` = ?')->bBool(false)
        ->sql('WHERE `created` < ?')->bDate('2026-01-01')
);
// $changed is the number of rows affected
```

`queryDelete()` works the same way.

### Transactions

```php
$db->beginTransaction();

try {
    $db->queryUpdate((new Statement($bindings))
        ->sql('UPDATE `users` SET `role` = ? WHERE `id` = ?')->bStr('editor')->bInt($id));
    $db->queryInsert((new Statement($bindings))
        ->sql('INSERT INTO `audit` (`user_id`, `action`) VALUES (?, ?)')->bInt($id)->bStr('demoted'));

    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}
```

## How statements work

Build a query one line at a time with `sql()`, and put each value's binding right after the line with its placeholder. There are two kinds of placeholder:

| Placeholder | Filled by | How the value gets to MySQL |
|---|---|---|
| `?` | `bStr`, `bInt`, `bBool`, `bBoolInt`, `bDate`, `bDateTime`, `bJSON`, `bLike` | Bound as a PDO parameter. The value is never part of the SQL text. |
| `%%` | `bStrArr`, `bIntArray`, `bFloat`, `bRaw` | Replaced in the SQL text: by a list of bound parameters (`bStrArr`), by validated numbers (`bIntArray`, `bFloat`), or as is (`bRaw`). |

Placeholders are filled in order: the first `?` gets the first `?`-type binding, and the first `%%` gets the first `%%`-type binding. Inside quoted strings and comments, `?` and `%%` are plain text, so `WHERE note = 'Why?'` works. Inside backticks, `%%` is still a placeholder, so `` `%%` `` with `bRaw()` can fill in a table name.

### See the final SQL

`getComputed()` returns the statement with the values filled in, one `sql()` call per line:

```php
$statement = (new Statement($bindings))
    ->sql('SELECT * FROM `users`')
    ->sql('WHERE `role` IN (%%)')->bStrArr(['admin', 'editor'])
    ->sql('AND `id` > ?')->bInt(5);

echo $statement->getComputed();
// SELECT * FROM `users`
// WHERE `role` IN ('admin', 'editor')
// AND `id` > 5
```

This is for reading only. It shows the values without escaping them, so don't run it. `echo $statement` and `var_dump($statement)` show the same thing.

## Binding methods

Every method returns the `Statement`, so calls chain. Methods with a `$null` parameter throw `InvalidArgumentException` when given `null`, unless `$null` is `true`; then they bind SQL `NULL`.

| Method | Placeholder | Binds | Notes |
|---|---|---|---|
| `bStr($value, $null = false, $type = PDO::PARAM_STR)` | `?` | string | Pass a different `PDO::PARAM_*` type if needed. |
| `bInt($value, $null = false)` | `?` | integer | Accepts ints, floats and numeric strings. Floats are truncated toward zero. Non-numeric or out-of-range values throw `InvalidArgumentException`. |
| `bFloat($value, $decimals = 3, $null = false)` | `%%` | number with `$decimals` decimal places | Must be numeric. |
| `bBool($value, $null = false)` | `?` | boolean | |
| `bBoolInt($value, $null = false)` | `?` | `0` or `1` | For `TINYINT` flag columns. |
| `bDate($value, $null = false)` | `?` | `YYYY-MM-DD` | See [Limitations](#limitations) for invalid dates. |
| `bDateTime($value, $null = false)` | `?` | `YYYY-MM-DD HH:MM:SS` | A date alone gets `00:00:00` added. |
| `bJSON($value, $null = false)` | `?` | JSON string | Takes an object or a JSON string. Invalid JSON, and arrays, throw `JsonException`. |
| `bLike($value, $ends = false, $starts = false)` | `?` | `%value%` | `$starts = true` gives `value%` (starts with). `$ends = true` gives `%value` (ends with). |
| `bStrArr(array $values, $default = '')` | `%%` | `:arr0, :arr1, …` | For `IN (%%)`. Each value is its own bound parameter. If `$values` is empty, `$default` is inserted **as is**. |
| `bIntArray(array $values)` | `%%` | `1, 2, 3` | For `IN (%%)`. Values are cast to integers and duplicates removed. An empty array throws `InvalidArgumentException`. |
| `bRaw($value)` | `%%` | the value, **as is** | For table names, column names and SQL functions you control. **Never pass user input.** |
| `bind($name, $value, $type = PDO::PARAM_STR)` | `:name` | anything | Binds your own named placeholder. |

## Query methods

All of these are on `GLPDO2` and take a `Statement`. Errors come through as `PDOException`, as long as the connection uses `PDO::ERRMODE_EXCEPTION`.

| Method | Returns |
|---|---|
| `selectRows($statement)` | All rows, as an array of associative arrays. |
| `selectRow($statement)` | The first row as an associative array, or `false`. |
| `selectValue($statement, $column, $caseSensitive = false, $default = null)` | One column of the first row, or `$default`. The column name match ignores case unless `$caseSensitive` is `true`. |
| `queryInsert($statement)` | The new row's ID (from `lastInsertId()`) as a string, or `false` if nothing was inserted. |
| `queryUpdate($statement)` | The number of rows affected. |
| `queryDelete($statement)` | The number of rows affected. |
| `beginTransaction()`, `commit()`, `rollback()`, `inTransaction()` | Same as PDO's methods. |

On `Statement` itself:

| Method | Does |
|---|---|
| `sql($text)` | Adds a line to the statement. |
| `reset()` | Clears the statement so it can be reused. |
| `execute($pdo)` | Prepares and runs the statement, and returns the `PDOStatement`. |
| `getComputed()` | Returns the SQL with values filled in, for debugging. |

## Security

Most bindings send values to MySQL as PDO parameters, so they can't change the query. The `%%` bindings put text into the SQL itself, so it matters what they accept:

| Binding | Safe for user input? | Why |
|---|---|---|
| `?` bindings, `bStrArr()` values | ✅ Yes | Sent as bound parameters. |
| `bInt()`, `bFloat()`, `bIntArray()` | ✅ Yes | Validated or cast to numbers before they reach the SQL. |
| `bStrArr()`'s `$default` | ❌ No | Inserted as is when the array is empty. |
| `bRaw()` | ❌ No | Inserted as is, by design. |

Versions before 4.0.11 pasted `bStrArr()` values into the SQL without escaping them. Upgrade if you use it.

## Custom bindings

The bindings are grouped into five interfaces in `GeekLab\GLPDO2\Bindings`: date/time, logic, numeric, raw and string. `MySQLBindingFactory::build()` creates the MySQL set. To change how a type is bound, implement the matching interface (or extend the MySQL class) and pass your own set in:

```php
use GeekLab\GLPDO2\Bindings\Bindings;
use GeekLab\GLPDO2\Bindings\MySQL;

$bindings = new Bindings(
    new MyDateTimeBindings(), // implements DateTimeBindingInterface
    new MySQL\MySQLLogicBindings(),
    new MySQL\MySQLNumericBindings(),
    new MySQL\MySQLRawBindings(),
    new MySQL\MySQLStringBindings()
);

$statement = new Statement($bindings);
```

## Limitations

- **MySQL and MariaDB only.** Other databases may work for simple queries, but aren't tested.
- **Invalid dates don't throw.** `bDate()` and `bDateTime()` bind `1970-01-01` (`1970-01-01 00:00:00`) for a value that isn't in the expected format, and only accept years 1900–2099. Validate dates first if that matters.
- **`bLike()` doesn't escape wildcards.** A `%` or `_` in the value acts as a wildcard.
- **`getComputed()` is approximate.** It shows string values in quotes without escaping them.

## Development

Run the checks with Composer:

```bash
composer phpstan  # PHPStan, level 8
composer phpcs    # PSR-12 code style
composer test     # PHPUnit
```

Most tests need a MySQL database, given by environment variables. One way, with Docker:

```bash
docker run -d --rm --name glpdo2-mysql -p 3306:3306 \
    -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=glpdo2 mysql:8.4

MYSQLHOST=127.0.0.1 MYSQLDB=glpdo2 MYSQLUSER=root MYSQLPASS=root composer test
```

[CI](.github/workflows/ci.yml) runs the tests on PHP 8.1–8.5 against MySQL 8.4.

## Changelog and license

See [CHANGELOG.md](CHANGELOG.md) for the release history. GLPDO2 is released under the [BSD 3-Clause License](LICENSE.BSD).
