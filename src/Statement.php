<?php

namespace GeekLab\GLPDO2;

// Make EA inspection stop complaining.
use \PDO;
use \PDOStatement;
use \Exception;
use \JsonException;
use \TypeError;
use GeekLab\GLPDO2\Bindings\Bindings;

class Statement
{
    /** @var Bindings $bindings */
    private $bindings;

    /** @var int $bindPos Position for SQL binds. */
    private $bindPos = 0;

    /** @var array $named Named binding values. */
    private $named = [];

    /** @var array $SQL SQL Statement. */
    private $SQL = [];

    /** @var int Position holder for statement processing. */
    private $sqlPos = 0;

    /** @var array Raw named placeholders. */
    private $rawNamed = [];

    /** @var int $rawPos Position holder for raw statement processing. */
    private $rawPos = 0;

    /** @var array $rawSql SQL Statement. */
    private $rawSql = [];

    public function __construct(Bindings $bindings)
    {
        $this->bindings = $bindings;
    }

    /**
     * Due to an outstanding bug (https://bugs.php.net/bug.php?id=70409)
     * where filter_var + FILTER_NULL_ON_FAILURE doesn't return null on null,
     * I have to do this and I feel bad about it.
     *
     * @param bool|int|string|null $value
     *
     * @return bool|null
     */
    private function filterValidateBool($value): ?bool
    {
        return  $value === null
            ? null
            : filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }


    /**
     * Replace the raw placeholder with raw values.
     *
     * @param string $sql
     *
     * @return string
     */
    private function rawPlaceholderFill(string $sql): string
    {
        foreach ($this->rawNamed as $name => $rVal) {
            $sql = (string) preg_replace('/' . $name . '\b/', $rVal, $sql);
        }

        return $sql;
    }

    /**
     * Bind a value to a named parameter.
     *
     * @param string $name
     * @param string|int|float|bool|null $value
     * @param int $type
     *
     * @return Statement
     */
    public function bind(string $name, $value, int $type = PDO::PARAM_STR): self
    {
        $this->named[$name] = array(
            'type' => $type,
            'value' => $value
        );

        return $this;
    }

    /**
     * Bind a raw value to a named parameter.
     *
     * @param string $name
     * @param string|int|float|bool $value
     * @return Statement
     */
    public function rawBind(string $name, $value): self
    {
        $this->rawNamed[$name] = $value;

        return $this;
    }

    // Bind types

    /**
     * Bind a boolean value as bool or null.
     * Knock, knock. Who's there? Tri-state.
     *
     * @param int|bool|null $value
     *
     * @return Statement
     * @throws TypeError
     */
    public function bBoolNullable($value = null): self
    {
        $binding = $this->bindings->bBoolNullable($this->filterValidateBool($value));
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Bind a boolean value as bool.
     *
     * @param int|bool $value
     *
     * @return Statement
     * @throws TypeError
     */
    public function bBool($value): self
    {
        $binding = $this->bindings->bBool($this->filterValidateBool($value));
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Bind a boolean value as int or null.
     * Tri-state who? Tri-state Boolean...
     *
     * @param int|bool|null $value
     *
     * @return Statement
     * @throws TypeError
     */
    public function bBoolIntNullable($value = null): self
    {
        $binding = $this->bindings->bBoolIntNullable($this->filterValidateBool($value));
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Bind a boolean value as int.
     *
     * @param int|bool $value
     *
     * @return Statement
     * @throws TypeError
     */
    public function bBoolInt($value): self
    {
        $binding = $this->bindings->bBoolInt($this->filterValidateBool($value));
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Bind a date value as date or null.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string|null $value
     *
     * @return Statement
     * @throws TypeError
     */
    public function bDateNullable(?string $value = null): self
    {
        $binding = $this->bindings->bDateNullable($value);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Bind a date value as date.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string $value
     *
     * @return Statement
     * @throws TypeError
     */
    public function bDate(string $value): self
    {
        $binding = $this->bindings->bDate($value);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Bind a date time value as date time or null.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string|null $value
     *
     * @return Statement
     * @throws TypeError
     */
    public function bDateTimeNullable(?string $value = null): self
    {
        $binding = $this->bindings->bDateTimeNullable($value);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Bind a date time value as date time.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string $value
     *
     * @return Statement
     * @throws TypeError
     */
    public function bDateTime(string $value): self
    {
        $binding = $this->bindings->bDateTime($value);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }


    /**
     * Bind a float.
     *
     * @param string|int|float|null $value
     * @param int $decimals
     *
     * @return Statement
     * @throws TypeError
     */
    public function bFloatNullable($value = null, $decimals = 3): self
    {
        $binding = $this->bindings->bFloatNullable($value, $decimals);
        $this->rawBind($this->getNextName('raw'), $binding[0]);
        return $this;
    }

    /**
     * Bind a float.
     *
     * @param string|int|float $value
     * @param int $decimals
     *
     * @return Statement
     * @throws TypeError
     */
    public function bFloat($value, $decimals = 3): self
    {
        $binding = $this->bindings->bFloat($value, $decimals);
        $this->rawBind($this->getNextName('raw'), $binding[0]);
        return $this;
    }

    /**
     * Bind an integer or null.
     *
     * @param string|int|float|bool|null $value
     *
     * @return Statement
     * @throws TypeError
     */
    public function bIntNullable($value = null): self
    {
        $binding = $this->bindings->bIntNullable($value);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Bind an integer.
     *
     * @param string|int|float|bool $value
     *
     * @return Statement
     * @throws TypeError
     */
    public function bInt($value): self
    {
        $binding = $this->bindings->bInt($value);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Convert array of integers to comma separated values. Uses %%
     * Great for IN() statements.
     *
     * @param array $data
     * @param int $default
     *
     * @return Statement
     * @throws TypeError
     */
    public function bIntArray(array $data, int $default = 0): self
    {
        $binding = $this->bindings->bIntArray($data, $default);
        $this->rawBind($this->getNextName('raw'), $binding[0]);
        return $this;
    }

    /**
     * Bind JSON to string or null.
     *
     * @param string|object|null $value
     *
     * @return Statement
     * @throws JsonException
     * @throws TypeError
     */
    public function bJsonNullable($value): self
    {
        $binding = $this->bindings->bJsonNullable($value);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Bind JSON to string.
     *
     * @param string|object|null $value
     *
     * @return Statement
     * @throws JsonException
     * @throws TypeError
     */
    public function bJson($value): self
    {
        $binding = $this->bindings->bJson($value);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Create and bind string for LIKE() statements.
     *
     * @param string $value
     * @param bool $ends Ends with?
     * @param bool $starts Starts with?
     *
     * @return Statement
     */
    public function bLike(string $value, bool $ends = false, bool $starts = false): self
    {
        $binding = $this->bindings->bLike($value, $ends, $starts);
        $this->bind($this->getNextName(), $binding[0]);
        return $this;
    }

    /**
     * !!!DANGER!!!
     * Bind a raw value.
     *
     * @param string|int|float|bool $value
     *
     * @return Statement
     */
    public function bRaw($value): self
    {
        $binding = $this->bindings->bRaw($value);
        $this->rawBind($this->getNextName('raw'), $binding[0]);
        return $this;
    }

    /**
     * Bind a string or null.
     *
     * @param string|int|float|bool|null $value
     * @param int $type
     *
     * @return Statement
     * @throws Exception
     */
    public function bStrNullable($value, int $type = PDO::PARAM_STR): self
    {
        $binding = $this->bindings->bStrNullable($value, $type);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Bind a string value.
     *
     * @param string|int|float|bool|null $value
     * @param int $type
     *
     * @return Statement
     * @throws Exception
     */
    public function bStr($value, int $type = PDO::PARAM_STR): self
    {
        $binding = $this->bindings->bStr($value, $type);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Convert an array into a string and bind it.
     * Great for IN() statements.
     *
     * @param array $values
     * @param string|int|float|bool $default
     *
     * @return Statement
     */
    public function bStrArr(array $values, $default = ''): self
    {
        $binding = $this->bindings->bStrArr($values, $default);
        $this->rawBind($this->getNextName('raw'), $binding[0]);
        return $this;
    }

    // The rest of the helpers

    /**
     * Name the positions for binding in PDO.
     *
     * @param string $type
     *
     * @return string
     */
    private function getNextName(string $type = 'bind'): string
    {
        switch ($type) {
            case 'sql':
                // sql statement syntax
                $ret = sprintf(':pos%d', $this->sqlPos++);

                return $ret;

            case 'rawSql':
                //$ret = sprintf(':raw%d', $this->_rawSql++);
                $ret = sprintf(':raw%d', $this->rawPos);

                return $ret;

            case 'raw':
                // raw statement syntax
                $ret = sprintf(':raw%d', $this->rawPos++);

                return $ret;

            case 'bind':
            default:
                // bind/filling values
                $ret = sprintf(':pos%d', $this->bindPos++);

                return $ret;
        }
    }

    /**
     * Prepare and Execute the SQL statement.
     *
     * @param PDO $pdo
     *
     * @return PDOStatement
     * @throws Exception
     */
    public function execute(PDO $pdo): PDOStatement
    {
        // Prepare the SQL, force to string in case of null.
        // Then replace raw placements with raw values.
        $sql = $this->rawPlaceholderFill((string) implode(' ', $this->SQL));

        /** @var PDOStatement $stmt */
        $stmt = $pdo->prepare($sql);

        // Bind named parameters.
        foreach ($this->named as $name => $sVal) {
            switch ($sVal['type']) {
                case PDO::PARAM_BOOL:
                    $stmt->bindValue($name, (bool) $sVal['value'], $sVal['type']);
                    break;

                case PDO::PARAM_NULL:
                    $stmt->bindValue($name, null);
                    break;

                case PDO::PARAM_INT:
                    $stmt->bindValue($name, (int) $sVal['value'], $sVal['type']);
                    break;

                case PDO::PARAM_STR:
                default:
                    $stmt->bindValue($name, (string) $sVal['value'], $sVal['type']);
                    break;
            }
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * Use for building out what a might look like when it's pass to the DB.
     * Used by Statement::getComputed()
     *
     * @param array $matches
     *
     * @return mixed
     * @throws Exception
     */
    private function placeholderFill(array $matches)
    {
        $key = $matches[0];

        // Can't fill this param.
        if (!isset($this->named[$key]) && !isset($this->rawNamed[$key])) {
            return $key;
        }

        if (isset($this->named[$key])) {
            // here is the param
            $sVal = $this->named[$key];

            switch ($sVal['type']) {
                case PDO::PARAM_BOOL:
                    return $sVal['value'] ? 'TRUE' : 'FALSE';

                case PDO::PARAM_NULL:
                    return 'NULL';

                case PDO::PARAM_INT:
                    return (int) $sVal['value'];

                case PDO::PARAM_STR:
                default:
                    return "'" . $sVal['value'] . "'";
            }
        }

        // Since it's not named, it must be raw.
        return $this->rawNamed[$key];
    }

    /**
     * Get name of the placeholder.
     *
     * @return string
     */
    private function placeholderGetName(): string
    {
        return $this->getNextName('sql');
    }

    /**
     * Get name of the raw placeholder.
     *
     * @return string
     */
    private function rawPlaceHolderGetName(): string
    {
        return $this->getNextName('rawSql');
    }

    /**
     * Builds up the SQL parameterized statement.
     *
     * @param string $text
     *
     * @return Statement
     */
    public function sql(string $text): self
    {
        // Replace positioned placeholders with named placeholders (first value).
        // Force to string, in the case of null.
        $text = (string) preg_replace_callback('/\?/m', function () {
            return $this->placeholderGetName();
        }, $text);

        $text = (string) preg_replace_callback('/%%/m', function () {
            return $this->rawPlaceholderGetName();
        }, $text);

        $this->SQL[] = $text;

        return $this;
    }

    /**
     * Reset / Clear out properties.
     *
     * @return Statement
     */
    public function reset(): self
    {
        $this->bindPos = 0;
        $this->named = [];
        $this->SQL = [];
        $this->sqlPos = 0;
        $this->rawNamed = array();
        $this->rawPos = 0;
        $this->rawSql = array();

        return $this;
    }

    /**
     * Create what the SQL query string might look like.
     * Great for debugging. YMMV though.
     *
     * @return string
     */
    public function getComputed(): string
    {
        // Merge SQL together
        $sql = implode("\n", $this->SQL);

        // Replace positioned placeholders with named placeholders (first value).
        // Force to string, in the case of null.
        $sql = (string) preg_replace_callback('/:[a-z0-9_]+/m', function ($matches) {
            return $this->placeholderFill($matches);
        }, $sql);

        return $sql;
    }

    /**
     * Return the SQL as a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getComputed();
    }

    /**
     * Magic Method for debugging.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            'Named Positions' => $this->named,
            'Unbound SQL' => $this->SQL,
            'Bound SQL' => $this->getComputed()
        ];
    }
}
