<?php

namespace GeekLab\GLPDO2;

// Make EA inspection stop complaining.
use \DomainException;
use \Exception;
use \PDO;
use \PDOStatement;

class Statement
{
    /**
     * Position for SQL binds.
     * @var int
     */
    private $bindPos = 0;

    /**
     * Position for Filtering.
     * @var int
     */
    private $filterPos = 0;

    /**
     * Named binding values.
     * @var array
     */
    private $named = array();

    /**
     * SQL Statement.
     * @var array
     */
    private $SQL = array();

    /**
     * Position holder for statement processing.
     * @var int
     */
    private $sqlPos = 0;

    /**
     * Raw Named
     * @var array
     */
    private $rawNamed = array();

    /**
     * Position holder for raw statement processing.
     * @var int
     */
    private $rawPos = 0;

    /**
     * SQL Statement.
     * @var array
     */
    private $rawSql = array();

    /** @const string DATE_REGEX Standard date format YYYY-MM-DD */
    private const DATE_REGEX      = '%^(19|20)\d{2}-(0[1-9]|1[012])-(0[1-9]|[12]\d|3[01])$%';

    /** @const string DATE_TIME_REGEX Standard date time format YYYY-MM-DD HH:MM:SS */
    private const DATE_TIME_REGEX = '%^(19|20)\d{2}-(0[1-9]|1[012])-(0[1-9]|[12]\d|3[01]) ([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$%';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Init
    }

    // Bind types

    /**
     * Bind a boolean value as bool, with NULL option or with integer option.
     *
     * @param       $value
     * @param bool  $null
     * @param bool  $int
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
        $value = (boolean)$value;

        $this->bind($name, ($int ? (int)$value : $value), ($int ? PDO::PARAM_INT : PDO::PARAM_BOOL));

        return $this;
    }

    /**
     * Bind a date value as date or optional NULL.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string $value
     * @param bool   $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bDate($value = null, bool $null = false): self
    {
        $value = trim($value);
        $d     = preg_match(self::DATE_REGEX, $value);

        // Use NULL?
        if (($value === null || !$d) && $null) {
            return $this->bStr(null, true);
        }

        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in date spot.');
        }

        $this->bStr($d ? $value : '1970-01-01');
        return $this;
    }

    /**
     * Bind a date value as date time or optional NULL.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string $value
     * @param bool   $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bDateTime($value = null, bool $null = false): self
    {
        $value = trim($value);
        $dt    = preg_match(self::DATE_TIME_REGEX, $value);

        // Use NULL?
        if (($value === null || !$dt) && $null) {
            return $this->bStr(null, true);
        }

        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in date time spot.');
        }

        if(!$dt) {
            if (!preg_match(self::DATE_REGEX, $value)) {
                $value = '1970-01-01 00:00:00';
            } else {
                $value .= ' 00:00:00';
            }
        }

        $this->bStr($value);
        return $this;
    }

    /**
     * Bind filtering stuff?
     *
     * @param string $value
     *
     * @return Statement
     */
    public function bFilter(string $value): self
    {
        $this->bind('filter', $value);
        return $this;
    }

    /**
     * Bind a float.
     *
     * @param      $value
     * @param int  $decimals
     * @param bool $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bFloat($value = null, $decimals = 3, $null = false): self
    {
        // Use NULL?
        if ($value === null && $null) {
            return $this->bStr(null, true);

        }

        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in float spot.');
        }

        if (!is_numeric($value)) {
            throw new DomainException('Can not bind "' . $value . '" in float spot.');
        }

        $format = sprintf('%%0.%df', $decimals);
        $this->bStr(sprintf($format, $value));

        return $this;
    }

    /**
     * Bind a value to a named parameter.
     *
     * @param string  $name
     * @param         $value
     * @param         $type
     *
     * @return Statement
     */
    public function bind(string $name, $value, $type = PDO::PARAM_STR): self
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
     * @param  $name
     * @param  $value
     * @return Statement
     */
    public function rawBind($name, $value): self
    {
        $this->rawNamed[$name] = $value;

        return $this;
    }

    /**
     * Bind an integer with optional NULL.
     *
     * @param       $value
     * @param bool  $null
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

        $this->bind($name, (int)$value, PDO::PARAM_INT);
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
    public function bIntArray(array $data, $default = 0)
    {
        if (empty($data)) {
            throw new DomainException('Can not bind an empty array.');
        }

        // Make unique integer array
        $numbers = array();

        foreach ($data as $value) {
            $numbers[(int)$value] = true;
        }

        $numbers = array_keys($numbers);

        // turn into a string
        $result = implode(', ', $numbers);

        return $this->bRaw($result ?: $default);
    }

    /**
     * Create and bind string for LIKE() statements.
     *
     * @param string $value
     * @param bool   $ends   Ends with?
     * @param bool   $starts Starts with?
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
     * @param string $value
     *
     * @return Statement
     */
    public function bRaw(string $value): self
    {
        $name = $this->getNextName('raw');

        $this->rawBind($name, $value);
        return $this;
    }

    /**
     * Bind a string value.
     *
     * @param       $value
     * @param bool  $null
     * @param       $type
     *
     * @return Statement
     * @throws Exception
     */
    public function bStr($value, bool $null = false, $type = PDO::PARAM_STR): self
    {
        $name = $this->getNextName();

        if ($value === null && $null) {
            $type = PDO::PARAM_NULL;
        } elseif ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in string spot.');
        }

        $this->bind($name, $value, $type);
        return $this;
    }

    /**
     * Convert an array into a string and bind it.
     * Great for IN() statements.
     *
     * @param array   $values
     * @param         $default
     * @return Statement
     */
    public function bStrArr(array $values, $default = null): self
    {
        //  No array elements?
        $aStr = (!is_array($values)) ? $default : '\'' . implode("', '", $values) . '\'';

        $this->bRaw($aStr);
        return $this;
    }


    // The rest of the helpers

    /**
     * Convert an array to a comma delimited string, with keys.
     * Should I remove this?
     *
     * @param array $data
     * @return string
     * @throws Exception
     */
    public static function arrToString(array $data): string
    {
        if (empty($data)) {
            throw new DomainException('Can not bind empty array.');
        }

        $query_string = array();

        foreach ($data as $k => $v) {
            $query_string[] = $k . '="' . $v . '"';
        }

        return implode(',', $query_string);

    }

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
            case 'filter':
                // filter
                $ret = sprintf(':filter%d', $this->filterPos++);

                return $ret;
                break;

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
     */
    public function execute(PDO $PDO): PDOStatement
    {
        // prepare the SQL
        $sql = implode(' ', $this->SQL);

        // Replace raw placements with raw values
        foreach ($this->rawNamed as $name => $rVal) {
            $sql = preg_replace('/' . $name . '\b/', $rVal, $sql);
        }

        $stmt = $PDO->prepare($sql);

        // bind named parameters
        foreach ($this->named as $name => $sVal) {
            switch ($sVal['type']) {
                case PDO::PARAM_BOOL :
                    $stmt->bindValue($name, (boolean)$sVal['value'], $sVal['type']);
                    break;

                case PDO::PARAM_NULL :
                    $stmt->bindValue($name, null);
                    break;

                case PDO::PARAM_INT :
                    $stmt->bindValue($name, (int)$sVal['value'], $sVal['type']);
                    break;

                case PDO::PARAM_STR :
                default :
                    $stmt->bindValue($name, (string)$sVal['value'], $sVal['type']);
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
        if (empty($matches)) {
            throw new DomainException('Can not fill placeholders with empty array.');
        }

        $key = $matches[0];

        // Can't fill this param.
        if (!isset($this->named[$key]) && !isset($this->rawNamed[$key])) {
            return $key;
        }

        if (isset($this->named[$key])) {
            // here is the param
            $sVal = $this->named[$key];

            switch ($sVal['type']) {
                case PDO::PARAM_BOOL :
                    return $sVal['value'] ? 'TRUE' : 'FALSE';

                case PDO::PARAM_NULL :
                    return 'NULL';

                case PDO::PARAM_INT :
                    return (int)$sVal['value'];

                case PDO::PARAM_STR :
                default :
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
     * @return Statement
     */
    public function sql($text): self
    {
        // Replace positioned placeholders with named placeholders (first value).
        $text = preg_replace_callback('/\?/m', array($this, 'placeholderGetName'), $text);
        $text = preg_replace_callback('/%%/m', array($this, 'rawPlaceholderGetName'), $text);

        // Just add the text as-is.
        if (func_num_args() === 1) {
            $this->SQL[] = $text;
        } else {
            // Treat as an sprintf statement.
            $args        = func_get_args();
            $args[0]     = $text;
            // Use argument unpacking, instead of call_user_func_array().
            $this->SQL[] = sprintf(...$args);
        }

        return $this;
    }

    /**
     * Reset / Clear out properties.
     *
     * @return Statement
     */
    public function reset(): self
    {
        $this->bindPos   = 0;
        $this->filterPos = 0;
        $this->named     = array();
        $this->SQL       = array();
        $this->sqlPos    = 0;
        $this->rawNamed  = array();
        $this->rawPos    = 0;
        $this->rawSql    = array();

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
        $sql = preg_replace_callback('/:[a-z0-9_]+/m', array($this, 'placeholderFill'), $sql);

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