<?php

namespace GeekLab\GLPDO2;

// Make EA inspection stop complaining.
use \Exception;
use GeekLab\GLPDO2\Bindings\BindingsInterface;
use \PDO;
use \PDOStatement;

class Statement
{
    /** @var BindingsInterface $bindings */
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

    public function __construct(BindingsInterface $bindings)
    {
        $this->bindings = $bindings;
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
     * Bind a boolean value as bool, with NULL option or with integer option.
     *
     * @param string|int|bool|null $value
     * @param bool $null
     * @param bool $int
     *
     * @return Statement
     * @throws Exception
     */
    public function bBool($value = null, bool $null = false, bool $int = false): self
    {
        $binding = $this->bindings->bBool($value, $null, $int);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Bind a date value as date or optional NULL.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string|null $value
     * @param bool $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bDate($value = null, bool $null = false): self
    {
        $binding = $this->bindings->bDate($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Bind a date value as date time or optional NULL.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string|null $value
     * @param bool $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bDateTime($value = null, bool $null = false): self
    {
        $binding = $this->bindings->bDateTime($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);
        return $this;
    }

    /**
     * Bind a float.
     *
     * @param string|int|float|null $value
     * @param int $decimals
     * @param bool $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bFloat($value = null, $decimals = 3, $null = false): self
    {
        $binding = $this->bindings->bFloat($value, $decimals, $null);
        $this->rawBind($this->getNextName('raw'), $binding[0]);
        return $this;
    }


    /**
     * Bind an integer with optional NULL.
     *
     * @param string|int|float|bool|null $value
     * @param bool $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bInt($value = null, bool $null = false): self
    {
        $binding = $this->bindings->bInt($value, $null);
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
     * @throws Exception
     */
    public function bIntArray(array $data, int $default = 0): self
    {
        $binding = $this->bindings->bIntArray($data, $default);
        $this->rawBind($this->getNextName('raw'), $binding[0]);
        return $this;
    }

    /**
     * Bind a object or JSON string to a string
     *
     * @param string|object|null $value
     * @param bool $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bJSON($value, bool $null = false): self
    {
        $binding = $this->bindings->bJSON($value, $null);
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
     * Bind a string value.
     *
     * @param string|int|float|bool|null $value
     * @param bool $null
     * @param int $type
     *
     * @return Statement
     * @throws Exception
     */
    public function bStr($value, bool $null = false, int $type = PDO::PARAM_STR): self
    {
        $binding = $this->bindings->bStr($value, $null, $type);
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
     * @param PDO $PDO
     *
     * @return PDOStatement
     * @throws Exception
     */
    public function execute(PDO $PDO): PDOStatement
    {
        // Prepare the SQL, force to string in case of null.
        $sql = (string) implode(' ', $this->SQL);

        // Replace raw placements with raw values.
        foreach ($this->rawNamed as $name => $rVal) {
            $sql = (string) preg_replace('/' . $name . '\b/', $rVal, $sql);
        }

        /** @var PDOStatement $stmt */
        $stmt = $PDO->prepare($sql);

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
        $sql = (string) preg_replace_callback('/:[a-z0-9_]+/m', array($this, 'placeholderFill'), $sql);

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
