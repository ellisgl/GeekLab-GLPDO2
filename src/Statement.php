<?php

namespace GeekLab\GLPDO2;

// Make EA inspection stop complaining.
use \DomainException;
use \Exception;
use \PDO;
use \PDOStatement;

class Statement
{
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

    /** @const string DATE_REGEX Standard date format YYYY-MM-DD */
    private const DATE_REGEX = '%^(19|20)\d{2}-(0[1-9]|1[012])-(0[1-9]|[12]\d|3[01])$%';

    /** @const string DATE_TIME_REGEX Standard date time format YYYY-MM-DD HH:MM:SS */
    private const DATE_TIME_REGEX = '%^(19|20)\d{2}-(0[1-9]|1[012])-(0[1-9]|[12]\d|3[01]) ' .
                                    '([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$%';

    // Bind types

    /**
     * Bind a boolean value as bool, with NULL option or with integer option.
     *
     * @param string|int|bool|null $value
     * @param bool                 $null
     * @param bool                 $int
     *
     * @return Statement
     * @throws Exception
     */
    public function bBool($value = null, bool $null = false, bool $int = false): self
    {
        // use NULL
        if ($value === null && $null) {
            return $this->bStr(null, true);
        }

        if ($value === null && $null === false) {
            throw new DomainException('Can not bind NULL in boolean spot.');
        }

        $name  = $this->getNextName();
        $value = (bool) $value;
        $value = $int ? (int) $value : $value;
        $type  = $int ? PDO::PARAM_INT : PDO::PARAM_BOOL;

        $this->bind($name, $value, $type);

        return $this;
    }

    /**
     * Bind a date value as date or optional NULL.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string|null $value
     * @param bool        $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bDate($value = null, bool $null = false): self
    {
        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in date spot.');
        }

        $d = null;

        if ($value !== null) {
            $value = trim($value);
            $d     = preg_match(self::DATE_REGEX, $value);
        }

        // Use NULL?
        if (!$d && $null) {
            return $this->bStr(null, true);
        }

        $this->bStr($d ? $value : '1970-01-01');
        return $this;
    }

    /**
     * Bind a date value as date time or optional NULL.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string|null $value
     * @param bool        $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bDateTime($value = null, bool $null = false): self
    {
        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in date time spot.');
        }

        $dt = null;

        if ($value !== null) {
            $value = trim($value);
            $dt    = preg_match(self::DATE_TIME_REGEX, $value);
        }

        // Use NULL?
        if (!$dt && $null) {
            return $this->bStr(null, true);
        }

        if (!$dt) {
            if ($value !== null && !preg_match(self::DATE_REGEX, $value)) {
                $value = '1970-01-01 00:00:00';
            } else {
                $value .= ' 00:00:00';
            }
        }

        $this->bStr($value);
        return $this;
    }

    /**
     * Bind a float.
     *
     * @param string|int|float|null $value
     * @param int                   $decimals
     * @param bool                  $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bFloat($value = null, $decimals = 3, $null = false): self
    {
        // Use NULL?
        if ($value === null && $null) {
            return $this->bRaw('NULL');
        }

        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in float spot.');
        }

        if (!is_numeric($value)) {
            throw new DomainException('Can not bind "' . $value . '" in float spot.');
        }

        $format = sprintf('%%0.%df', $decimals);

        // Apparently using PDO::PARAM_STR makes this fail!
        $this->bRaw(sprintf($format, $value));

        return $this;
    }

    /**
     * Bind a value to a named parameter.
     *
     * @param string                     $name
     * @param string|int|float|bool|null $value
     * @param int                        $type
     *
     * @return Statement
     */
    public function bind(string $name, $value, int $type = PDO::PARAM_STR): self
    {
        $this->named[$name] = array(
            'type'  => $type,
            'value' => $value
        );

        return $this;
    }

    /**
     * Bind a raw value to a named parameter.
     *
     * @param string                $name
     * @param string|int|float|bool $value
     * @return Statement
     */
    public function rawBind(string $name, $value): self
    {
        $this->rawNamed[$name] = $value;

        return $this;
    }

    /**
     * Bind an integer with optional NULL.
     *
     * @param string|int|float|bool|null $value
     * @param bool                       $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bInt($value = null, bool $null = false): self
    {
        // Use NULL?
        if ($value === null && $null) {
            return $this->bStr(null, true);
        }

        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in integer spot.');
        }

        if (!is_numeric($value)) {
            throw new DomainException('Can not bind "' . $value . '" in integer spot.');
        }

        $name  = $this->getNextName();
        $value = sprintf('%u', $value);

        $this->bind($name, (int) $value, PDO::PARAM_INT);
        return $this;
    }

    /**
     * Convert array of integers to comma separated values. Uses %%
     * Great for IN() statements.
     *
     * @param array $data
     * @param int   $default
     *
     * @return int|string
     * @throws Exception
     */
    public function bIntArray(array $data, int $default = 0)
    {
        if (empty($data)) {
            throw new DomainException('Can not bind an empty array.');
        }

        // Make unique integer array
        $numbers = array();

        foreach ($data as $value) {
            $numbers[(int) $value] = true;
        }

        $numbers = array_keys($numbers);

        // turn into a string
        $result = implode(', ', $numbers);

        return $this->bRaw($result ?: $default);
    }

    /**
     * Bind a object or JSON string to a string
     *
     * @param string|object|null $value
     * @param bool               $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bJSON($value, bool $null = false): self
    {
        // Use NULL?
        if ($value === null && $null) {
            return $this->bStr(null, true);
        }

        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in JSON spot.');
        }

        if (is_object($value)) {
            $value = json_encode($value);
        } elseif (is_string($value)) {
            $JSON = json_decode($value, false, 255);

            if (json_last_error()) {
                throw new DomainException('Can not bind invalid JSON in JSON spot. (' . json_last_error_msg() . ')');
            }

            if (!is_object($JSON) && !is_array($JSON)) {
                throw new DomainException('Can not bind invalid JSON in JSON spot. (UNKNOWN)');
            }

            $value = json_encode($JSON);
        } else {
            throw new DomainException('Can not bind invalid JSON in JSON spot. (' . json_last_error_msg() . ')');
        }

        return $this->bStr($value);
    }

    /**
     * Create and bind string for LIKE() statements.
     *
     * @param string $value
     * @param bool   $ends   Ends with?
     * @param bool   $starts Starts with?
     *
     * @return Statement
     */
    public function bLike(string $value, bool $ends = false, bool $starts = false): self
    {
        //$value = mysql_real_escape_string($value);
        $name = $this->getNextName();

        // Starts with.
        $value = ($starts && !$ends) ? $value . '%' : $value;

        // Ends with.
        $value = (!$starts && $ends) ? '%' . $value : $value;

        // Is somewhere...
        $value = (!$starts && !$ends) ? '%' . $value . '%' : $value;

        $this->bind($name, $value);
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
        $name = $this->getNextName('raw');

        $this->rawBind($name, $value);
        return $this;
    }

    /**
     * Bind a string value.
     *
     * @param string|int|float|bool|null $value
     * @param bool                       $null
     * @param int                        $type
     *
     * @return Statement
     * @throws Exception
     */
    public function bStr($value, bool $null = false, int $type = PDO::PARAM_STR): self
    {
        $name = $this->getNextName();

        if ($value === null && $null) {
            $type = PDO::PARAM_NULL;
        } elseif ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in string spot.');
        }

        $this->bind($name, (string) $value, $type);
        return $this;
    }

    /**
     * Convert an array into a string and bind it.
     * Great for IN() statements.
     *
     * @param array                 $values
     * @param string|int|float|bool $default
     *
     * @return Statement
     */
    public function bStrArr(array $values, $default = ''): self
    {
        //  No array elements?
        $aStr = empty($values) ? $default : '\'' . implode("', '", $values) . '\'';

        $this->bRaw($aStr);
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
                break;

            case 'rawSql':
                //$ret = sprintf(':raw%d', $this->_rawSql++);
                $ret = sprintf(':raw%d', $this->rawPos);

                return $ret;
                break;

            case 'raw':
                // raw statement syntax
                $ret = sprintf(':raw%d', $this->rawPos++);

                return $ret;
                break;

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
        $text        = (string) preg_replace_callback('/\?/m', array($this, 'placeholderGetName'), $text);
        $text        = (string) preg_replace_callback('/%%/m', array($this, 'rawPlaceholderGetName'), $text);
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
        $this->bindPos  = 0;
        $this->named    = array();
        $this->SQL      = array();
        $this->sqlPos   = 0;
        $this->rawNamed = array();
        $this->rawPos   = 0;
        $this->rawSql   = array();

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
            'Unbound SQL'     => $this->SQL,
            'Bound SQL'       => $this->getComputed()
        ];
    }
}
