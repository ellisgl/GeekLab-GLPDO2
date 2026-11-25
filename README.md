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
- [PostgreSQL](#postgresql)
- [SQLite](#sqlite)
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

Requires PHP 8.4 or newer, with the `pdo_mysql` extension for MySQL and MariaDB, `pdo_pgsql` for PostgreSQL, or `pdo_sqlite` for SQLite 3.

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

| Placeholder | Filled by | How the value gets to the database |
|---|---|---|
| `?` | `bStr`, `bInt`, `bBool`, `bBoolInt`, `bDate`, `bDateTime`, `bJSON`, `bLike` | Bound as a PDO parameter. The value is never part of the SQL text. |
| `%%` | `bStrArr`, `bIntArray`, `bUInt`, `bUIntArray`, `bFloat`, `bIdent`, `bRaw` | Replaced in the SQL text: by a list of bound parameters (`bStrArr`), by validated numbers (`bIntArray`, `bUInt`, `bUIntArray`, `bFloat`), by quoted identifiers (`bIdent`), or as is (`bRaw`). |

Placeholders are filled in order: the first `?` gets the first `?`-type binding, and the first `%%` gets the first `%%`-type binding. Inside quoted strings and comments, `?` and `%%` are plain text, so `WHERE note = 'Why?'` works. Inside quoted identifiers (backticks for MySQL, double quotes for PostgreSQL, any of `"..."`, `` `...` `` and `[...]` for SQLite), `%%` is still a placeholder, so `` `%%` `` with `bRaw()` can fill in a table name. What counts as a string or comment depends on the database; see [PostgreSQL](#postgresql) and [SQLite](#sqlite).

### Quoting table and column names

The examples here quote names with MySQL backticks. To leave quoting to the library, put `%%` where the name goes and bind it with `bIdent()`. The bindings decide how to quote it: backticks for MySQL, double quotes for PostgreSQL and SQLite.

```php
$statement
    ->sql('SELECT %% FROM %% WHERE %% = ?')
    ->bIdent(['id', 'users.name'])->bIdent('app.users')->bIdent('id')->bInt(5);

// MySQL:      SELECT `id`, `users`.`name` FROM `app`.`users` WHERE `id` = 5
// PostgreSQL: SELECT "id", "users"."name" FROM "app"."users" WHERE "id" = 5
```

- Dots split a name into parts, and each part is quoted on its own. A name that contains a dot can't be quoted with `bIdent()`.
- A `*` as the last part (`users.*`) is left unquoted.
- Aliases aren't parsed: `'users u'` becomes one quoted name. Write the alias in the SQL instead: `->sql('FROM %% u')`.
- An array becomes a comma-separated list, for `SELECT` and `INSERT` column lists.
- A quote character inside a name is doubled, so the name can't break out of its quotes. Even so, don't let users pick any table or column they like. Check names against a list you allow.

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
| `bUInt($value, $null = false)` | `%%` | unsigned integer | `0` to `18446744073709551615`, for `UNSIGNED` columns. Pass values above `PHP_INT_MAX` as integer strings. Floats are truncated toward zero. Negative, non-numeric and out-of-range values throw `InvalidArgumentException`. See [Unsigned integers](#unsigned-integers). |
| `bFloat($value, $decimals = 3, $null = false)` | `%%` | number with `$decimals` decimal places | Must be numeric. |
| `bBool($value, $null = false)` | `?` | boolean | |
| `bBoolInt($value, $null = false)` | `?` | `0` or `1` | For `TINYINT` flag columns. |
| `bDate($value, $null = false)` | `?` | `YYYY-MM-DD` | See [Limitations](#limitations) for invalid dates. |
| `bDateTime($value, $null = false)` | `?` | `YYYY-MM-DD HH:MM:SS` | A date alone gets `00:00:00` added. |
| `bJSON($value, $null = false)` | `?` | JSON string | Takes an object or a JSON string. Invalid JSON, and arrays, throw `JsonException`. |
| `bLike($value, $ends = false, $starts = false)` | `?` | `%value%` | `$starts = true` gives `value%` (starts with). `$ends = true` gives `%value` (ends with). |
| `bStrArr(array $values, $default = '')` | `%%` | `:arr0, :arr1, …` | For `IN (%%)`. Each value is its own bound parameter. If `$values` is empty, `$default` is inserted **as is**. |
| `bIntArray(array $values)` | `%%` | `1, 2, 3` | For `IN (%%)`. Values are cast to integers and duplicates removed. An empty array throws `InvalidArgumentException`. |
| `bUIntArray(array $values)` | `%%` | `1, 2, 3` | For `IN (%%)` on `UNSIGNED` columns. Each value is validated as `bUInt()` does, so a bad value throws rather than being cast. Duplicates are removed. An empty array throws `InvalidArgumentException`. |
| `bIdent($name)` | `%%` | quoted identifier(s) | Table, column or database name, quoted for your database. `'db.users.id'` quotes each part; an array gives a comma-separated list. See [Quoting table and column names](#quoting-table-and-column-names). |
| `bRaw($value)` | `%%` | the value, **as is** | For table names, column names and SQL functions you control. **Never pass user input.** |
| `bind($name, $value, $type = PDO::PARAM_STR)` | `:name` | anything | Binds your own named placeholder. |

### Unsigned integers

`bInt()` binds a PHP integer, which stops at `9223372036854775807` (2⁶³−1), and it accepts negative numbers. For `UNSIGNED` columns, use `bUInt()` and `bUIntArray()` instead. They reject negative values and go up to `BIGINT UNSIGNED`'s maximum, `18446744073709551615` (2⁶⁴−1):

```php
$statement
    ->sql('SELECT * FROM `events` WHERE `id` = %%')->bUInt('18446744073709551615')
    ->sql('OR `parent_id` IN (%%)')->bUIntArray([1, 2, '9223372036854775808']);
```

They use `%%` and put the validated digits into the SQL text, rather than binding a parameter. A value above `PHP_INT_MAX` can only be bound as a string, and in an expression or an `IN (...)` list MySQL compares a string with a number as a float, so `18446744073709551615` and `18446744073709551614` would match. Digits in the SQL text are compared exactly. Only digits get through the validation, so this is safe for user input.

PostgreSQL has no unsigned types. There, `bUInt()` checks that a value isn't negative, and values above 2⁶³−1 need a `numeric` column.

SQLite integers are signed 64-bit, and SQLite reads any larger number as a float, so 2⁶⁴−1 and 2⁶⁴−2 would be stored as the same value. With the SQLite bindings, `bUInt()` and `bUIntArray()` therefore stop at `9223372036854775807` and throw above it. Store larger values as text with `bStr()`.

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
| `bInt()`, `bUInt()`, `bFloat()`, `bIntArray()`, `bUIntArray()` | ✅ Yes | Validated or cast to numbers before they reach the SQL. |
| `bIdent()` | ⚠️ Can't inject SQL, but still check | Quoted and escaped, so it can't change the query's structure. It can still name any table or column, so check user-chosen names against a list you allow. |
| `bStrArr()`'s `$default` | ❌ No | Inserted as is when the array is empty. |
| `bRaw()` | ❌ No | Inserted as is, by design. |

Versions before 4.0.11 pasted `bStrArr()` values into the SQL without escaping them. Upgrade if you use it.

## PostgreSQL

Build the bindings with `PostgreSQLBindingFactory`. Everything else works as it does for MySQL:

```php
use GeekLab\GLPDO2\Bindings\PostgreSQL\PostgreSQLBindingFactory;

$pdo = new PDO('pgsql:host=localhost;dbname=app', 'user', 'pass', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db = new GLPDO2($pdo);
$statement = new Statement(PostgreSQLBindingFactory::build());

$tagged = $db->selectRows(
    $statement
        ->sql('SELECT %% FROM %%')->bIdent(['id', 'name'])->bIdent('public.users')
        ->sql('WHERE "settings" ?? ?')->bStr('beta')
        ->sql('AND "created" >= ?::date')->bDate('2026-01-01')
);
```

With it, `sql()` reads the text by PostgreSQL's rules rather than MySQL's:

| | MySQL | PostgreSQL |
|---|---|---|
| Strings | `'...'` and `"..."`, with backslash escapes | `'...'` (backslash is a plain character), `E'...'` (backslash escapes), `$$...$$` and `$tag$...$tag$` |
| Quoted identifiers | `` `...` `` | `"..."` |
| Comments | `-- ` (needs whitespace after it), `#`, `/* */` | `--`, `/* */` (can nest) |
| Literal `?` | — | `??`, for the jsonb `?`, `?\|` and `?&` operators |

`#` isn't a comment in PostgreSQL, so operators like `#>` and `#>>` work. For the jsonb operators, write `??` where you mean a literal `?`: `"data" ?? ?` checks whether the bound key exists. PDO turns `??` back into `?`, and `getComputed()` shows it as `?`.

PDO scans the SQL again before sending it, and its scanner doesn't nest comments: it ends a comment at the first `*/`. Don't put `?`, `:name` or quotes in the outer comment after a nested one closes.

The value bindings are the same as for MySQL. PostgreSQL is stricter about types, so add a cast where it can't infer one, such as `?::date`, `?::jsonb` or `?::int` in a select list. `bBoolInt()` binds `0` or `1`, which a PostgreSQL `boolean` column won't accept; use `bBool()` there.

## SQLite

Build the bindings with `SQLiteBindingFactory`. Everything else works as it does for MySQL:

```php
use GeekLab\GLPDO2\Bindings\SQLite\SQLiteBindingFactory;

$pdo = new PDO('sqlite:/path/to/app.db', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db = new GLPDO2($pdo);
$statement = new Statement(SQLiteBindingFactory::build());

$rows = $db->selectRows(
    $statement
        ->sql('SELECT %% FROM %%')->bIdent(['id', 'name'])->bIdent('users')
        ->sql('WHERE "settings" ->> ? = ?')->bStr('$.theme')->bStr('dark')
);
```

With it, `sql()` reads the text by SQLite's rules:

| | SQLite |
|---|---|
| Strings | `'...'`. A backslash is a plain character; `''` is a quote. |
| Quoted identifiers | `"..."`, `` `...` `` and `[...]` |
| Comments | `--` (no whitespace needed after it) and `/* */` (doesn't nest) |

`#` and `$` have no special meaning, and `??` is two placeholders, since SQLite has no `?` operator.

SQLite handles PDO placeholders itself, so the PDO limit described under [PostgreSQL](#postgresql) doesn't apply. SQLite has no boolean type: `bBool()` stores `1` or `0`. Dates from `bDate()` and `bDateTime()` are stored as text, which SQLite's date functions read. For unsigned integers above 2⁶³−1, see [Unsigned integers](#unsigned-integers).

## Custom bindings

The bindings are grouped into six interfaces in `GeekLab\GLPDO2\Bindings`: date/time, logic, numeric, raw, string and identifier. `MySQLBindingFactory::build()` creates the MySQL set. To change how a type is bound, implement the matching interface (or extend the MySQL class) and pass your own set in. `bUInt()` and `bUIntArray()` are in `UnsignedNumericBindingInterface`, which extends the numeric interface. If your numeric bindings only implement `NumericBindingInterface`, those two use the MySQL versions:

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

Two more arguments are optional:

- `$identifier`: how `bIdent()` quotes names. It defaults to the dialect's quoting. For other quote characters, use `QuotedIdentifierBindings`, such as `new QuotedIdentifierBindings('[', ']')` for SQL Server.
- `$dialect`: a `GeekLab\GLPDO2\Dialect`, which decides how `sql()` finds strings and comments. It defaults to `Dialect::MySQL`.

```php
use GeekLab\GLPDO2\Dialect;

$bindings = new Bindings(
    new MyDateTimeBindings(),
    new MySQL\MySQLLogicBindings(),
    new MySQL\MySQLNumericBindings(),
    new MySQL\MySQLRawBindings(),
    new MySQL\MySQLStringBindings(),
    dialect: Dialect::PostgreSQL
);
```

## Limitations

- **MySQL, MariaDB, PostgreSQL and SQLite 3.** Other databases may work for simple queries, but aren't tested. See [PostgreSQL](#postgresql) for a limit that comes from PDO.
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

[CI](.github/workflows/ci.yml) runs the tests on PHP 8.4–8.5 against MySQL 8.4, PostgreSQL 17 and SQLite.

## Changelog and license

See [CHANGELOG.md](CHANGELOG.md) for the release history. GLPDO2 is released under the [BSD 3-Clause License](LICENSE.BSD).
