[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![phpstan enabled](https://img.shields.io/badge/phpstan-enabled-green.svg)](https://github.com/phpstan/phpstan)

Master:
[![Build Status](https://scrutinizer-ci.com/g/ellisgl/GeekLab-GLPDO2/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ellisgl/GeekLab-GLPDO2/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ellisgl/GeekLab-GLPDO2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ellisgl/GeekLab-GLPDO2/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/ellisgl/GeekLab-GLPDO2/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ellisgl/GeekLab-GLPDO2/?branch=master)


Develop:
[![Build Status](https://scrutinizer-ci.com/g/ellisgl/GeekLab-GLPDO2/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/ellisgl/GeekLab-GLPDO2/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ellisgl/GeekLab-GLPDO2/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/ellisgl/GeekLab-GLPDO2/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/ellisgl/GeekLab-GLPDO2/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/ellisgl/GeekLab-GLPDO2/?branch=master)

GeekLab\GLPDO2
============

Easy to use PDO Wrapper for PHP 7.2+

### Latest
2019-06-?? (4.0.0):
* Splitting out methods for nullable. PHPMD was complaining.

### Features
* Bind value by the correct type. E.g. Don't bind as a string where an integer bind should be.
* Bindings are injected, so you can create your own!
* Help prevent injections.
* PSR1/2/4 Compliant.

### Installation
composer require geeklab/glpdo2

### Todo
* More tests, since we can test at many levels and implementations.
* Use PHP Date/Time stuff for validation?
* Better schema for testing.
* Better docs.
* More of a real wold example.

### Basic Usage (Quick-N-Dirty)

```
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('DS', DIRECTORY_SEPARATOR);
require_once '..' . DS . '..' . DS . 'vendor' . DS . 'autoload.php';

$dbConn    = new PDO('mysql:host=localhost;dbname=playground', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db        = new \GeekLab\GLPDO2\GLPDO2($dbConn);
$statement = new \GeekLab\GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());
$start     = 0;
$limit     = 5;

$statement->sql('SELECT *')
          ->sql('FROM (')
          ->sql('          SELECT *')
          ->sql('          FROM   `mock_data`')
          ->sql('          LIMIT  ?, ?')->bInt($start)->bInt($limit)
          ->sql('     ) SUBQ')
          ->sql('ORDER BY `id` DESC;');

// Show computedSQL statement
print_r($statement->getComputed());

$res = $db->selectRows($statement);

print_r($res);
```

### Data Binding Methods
Statement->bBoolNullable($value)<br/>
Binds a value as bool(true, false) or as null.

Statement->bBool($value)<br/>
Binds a value as bool(true, false).

Statement->bBoolIntNullable($value)<br/>
Binds a value as int(0, 1) or null.

Statement->bBoolInt($value)<br/>
Binds a value as int(0, 1).

Statement->bDateNullable($value)<br/>
Binds a value as a date (string - validated for YYYY-MM-DD) or null.

Statement->bDate($value)<br/>
Binds a value as a date (string - validated for YYYY-MM-DD).

Statement->bDateTime($value, optional boolean $null)<br/>
Binds a value as a date time (string - validated for YYYY-MM-DD HH:MM:SS), with optional NULL return.

Statement->bFloat($value, optional integer $decimals, optional boolean $null)<br/>
Binds a value aa a float, with decimal place (default of 3) and optional NULL return. Use '%%' instead of '?'.

Statement->bInt($value, optional boolean $null)<br/>
Bind a value as an integer, whith optional NULL return.

Statement->bIntArray(array $data, integer $default)<br/>
Converts an array of integers to a comma separated values. Will output $default (which is 0) if $data is not an array. Used with IN() statements. Use '%%' instead of '?'.

Statement->bJSON($data, optional boolean $null)<br/>
Binds a JSON object or string as a string, with optional NULL value. Throws JsonException.

Statement->bLike($value, boolean $ends, boolean $starts)<br/>
Binds a value as a string for LIKE queries. $ends = "ends with", $starts = "starts with"

Statement->bStr($value, optional boolean $null, optional \PDO::PARAM_* $type)<br/>
Binds a value as a string, with optional NULL value return and optional PDO binding type (default \PDO::PARAM_STR).

Statement->bStrArr(array $values, optional string $default)<br/>
Binds a string converted array for use with IN statements. $default is used when value isn't an array, which the default is NULL. Use '%%' instead of '?'. 

Statement->bind($name, $value, \PDO::PARAM_* $type)<br/>
Binds a value to a named parameter with option PDO binding type (default \PDO::PARAM_STR)

Statement->bRaw($value)<br/>
Binds a raw value to '%%' in the sql statement. This is unquoted and unescaped. Good for tables names and functions. Can be dangerous if not handled correctly.

### Query Methods
GLPDO->queryDelete(Statement $SQL)<br/>
Runs a delete query and returns numbers of affected rows.

GLPDO->queryInsert(Statement $SQL)<br/>
Runs an insert query and returns the primary ID.

GLPDO->queryUpdate(Statement $SQL)<br/>
Runs an update query and returns number of affect rows

GLPDO->selectRows(Statement $SQL)<br/>
Run a normal query, returns multiple rows as an array of associative arrays, or false.

GLPDO->selectRow(Statement $SQL)<br/>
Runs a normal query, returns a single row as an array, or false.

GLPDO->selectValueCaseSensitive(Statement $SQL, $column, $default)<br/>
Runs a normal query, returns a single column ($column - case sensitive) and can return a default (mixed $default = null) value is no value is in the column.

GLPDO->selectValue(Statement $SQL, $column, $default)<br/>
Runs a normal query, returns a single column ($column) and can return a default (mixed $default = null) value is no value is in the column.

GLPDO->selectRow(Statement $SQL)<br/>
Runs a normal query, returns a single row as an array.

GLPDO->beginTransaction()<br/>
Begins an SQL Transaction.

GLPDO->commit()<br/>
Commits an SQL Transaction.

GLPDO->inTransaction()<br/>
Is there a transaction in progress, returns bool.

### Misc Methods
Statement->sql(string $text)<br/>
Used to build up the SQL parameterized statement.

Statement->reset()<br/>
Used to reset Statement private variables. Usefully for creating multiple queries without having to create a new Statement object.

Statement->execute(\PDO $pdo)<br/>
Prepares and executes the statement

Statement->getComputed()<br/>
Returns what the compiled SQL query string might look like for debugging purposes.

### Alternative Packages
* https://github.com/nkt/flame
