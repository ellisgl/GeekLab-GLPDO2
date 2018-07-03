<?php

namespace GeekLab\GLPDO2;

class Statement
{
    /**
     * Position for SQL binds
     *
     * @var int
     */
    private $bindPos = 0;

    /**
     * Position for Filtering
     *
     * @var int
     */
    private $filterPos = 0;

    /**
     * Named binding values
     *
     * @var array
     */
    private $named = array();

    /**
     * SQL Statement
     *
     * @var array
     */
    private $SQL = array();

    /**
     * Position holder for statement processing
     *
     * @var int
     */
    private $sqlPos = 0;

    /**
     * Raw Named
     *
     * @var array
     */
    private $rawNamed = array();

    /**
     * Position holder for raw statement processing
     * @var int
     */
    private $rawPos = 0;

    /**
     * SQL Statement
     *
     * @var array
     */
    private $rawSql = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        // Init
    }

    // Bind types

    /**
     * Bind a boolean value as bool, with NULL option or with integer option
     *
     * @param $value
     * @param bool $null
     * @param bool $int
     *
     * @return $this
     */
    public function bBool($value, $null = FALSE, $int = FALSE)
    {
        // use NULL
        if (!$value && $null)
        {
            return $this->bStr(NULL, TRUE);
        }


        $name  = $this->getNextName();
        $value = (boolean)$value;

        $this->bind($name, (($int) ? (int)$value : $value), (($int) ? \PDO::PARAM_INT : \PDO::PARAM_BOOL));

        return $this;
    }

    /**
     * Bind a date value as date or optional NULL
     * YYYY-MM-DD is the proper date format
     *
     * @param $value
     * @param bool $null
     *
     * @return $this
     */
    public function bDate($value, $null = FALSE)
    {

        $d = preg_match('%^(19|20)[0-9]{2}[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$%', $value);

        // Use NULL?
        if ((!$value || !$d) && $null)
        {
            return $this->bStr(NULL, TRUE);
        }

        $this->bStr(($d) ? $value : '1970-01-01');
        return $this;
    }

    /**
     * Bind filtering stuff?
     *
     * @param $value
     *
     * @return $this
     */
    public function bFilter($value)
    {
        $this->bind('filter', $value, \PDO::PARAM_STR);
        return $this;
    }

    /**
     * Bind a float
     *
     * @param $value
     * @param int $decimals
     * @param bool $null
     *
     * @return $this
     */
    public function bFloat($value, $decimals = 3, $null = FALSE)
    {
        // Use NULL?
        if (!$value && $null)
        {
            return $this->bStr(NULL, TRUE);
        }

        $format = sprintf('%%0.%df', $decimals);
        $this->bStr(sprintf($format, $value));

        return $this;
    }

    /**
     * Bind a value to a named parameter
     *
     * @param $name
     * @param $value
     * @param $type
     *
     * @return $this
     */
    public function bind($name, $value, $type = \PDO::PARAM_STR)
    {
        $this->named[$name] = array(
            'type'  => $type,
            'value' => $value
        );

        return $this;
    }

    /**
     * Bind a raw value to a named parameter
     *
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function rawBind($name, $value)
    {
        $this->rawNamed[$name] = $value;

        return $this;
    }

    /**
     * Bind an integer with optional NULL
     *
     * @param      $value
     * @param bool $null
     *
     * @return $this
     */
    public function bInt($value, $null = FALSE)
    {
        // Use NULL?
        if (!$value && $null)
        {
            return $this->bStr(NULL, TRUE);
        }

        $name  = $this->getNextName();
        $value = sprintf('%u', $value);

        $this->bind($name, (int)$value, \PDO::PARAM_INT);

        return $this;
    }

    /**
     * Convert array of integers to comma separated values. Uses %%
     * Great for IN() statements.
     *
     * @param array $data
     * @param int $default
     *
     * @return int|string
     */
    public function bIntArray(array $data, $default = 0)
    {

        // Make unique integer array
        $numbers = array();

        foreach ($data as $value)
        {
            $numbers[(int)$value] = TRUE;
        }

        $numbers = array_keys($numbers);

        // turn into a string
        $result = join(', ', $numbers);

        return $this->bRaw($result ? $result : $default);
    }
    /**
     * Create and bind string for LIKE() statements.
     *
     * @param $value
     * @param bool $ends Ends with?
     * @param bool $starts Starts with?
     *
     * @return $this
     */
    public function bLike($value, $ends = FALSE, $starts = FALSE)
    {
        //$value = mysql_real_escape_string($value);
        $name = $this->getNextName();

        // Starts with
        $value = ($starts && !$ends) ? $value . '%' : $value;

        // Ends with
        $value = (!$starts && $ends) ? '%' . $value : $value;

        // Is somewhere...
        $value = (!$starts && !$ends) ? '%' . $value . '%' : $value;

        $this->bind($name, $value);

        return $this;
    }

    /**
     * Bind a raw value
     *
     * @param $value
     *
     * @return $this
     */
    public function bRaw($value)
    {
        $name = $this->getNextName('raw');

        $this->rawBind($name, $value);
        return $this;
    }

    /**
     * Bind a string value
     *
     * @param $value
     * @param bool $null
     * @param $type
     *
     * @return $this
     */
    public function bStr($value, $null = FALSE, $type = \PDO::PARAM_STR)
    {
        $name = $this->getNextName();

        if (!$value && $null)
        {
            $type = \PDO::PARAM_NULL;
        }

        $this->bind($name, $value, $type);

        return $this;
    }

    /**
     * Convert an array into a string and bind it
     * Great for IN() statements.
     *
     * @param array $values
     * @param string $default
     *
     * @return $this
     */
    public function bStrArr(array $values, $default = NULL)
    {
        //  No array elements?
        $aStr = (!is_array($values)) ? $default : '\'' . join("', '", $values) . '\'';

        $this->bRaw($aStr);
        return $this;
    }


    // The rest of the helpers

    /**
     * Convert an array to a comma delimited string, with keys.
     * Should I remove this?
     *
     * @param array $data
     *
     * @return string
     */
    public static function arrToString(array $data)
    {
        if (!empty($data))
        {
            $query_string = array();

            foreach ($data as $k => $v)
            {
                $query_string[] = $k . '="' . $v . '"';
            }

            return implode(',', $query_string);
        }

        return "";
    }

    /**
     * Name the positions for binding in PDO.
     *
     * @param $type
     *
     * @return string
     */
    private function getNextName($type = FALSE)
    {
        switch ($type)
        {
            case 'filter' :
                // filter
                $ret = sprintf(':filter%d', $this->filterPos++);
                return $ret;
                break;

            case 'sql' :
                // sql statement syntax
                $ret = sprintf(':pos%d', $this->sqlPos++);

                return $ret;
                break;

            case 'rawSql':
                //$ret = sprintf(':raw%d', $this->_rawSql++);
                $ret = sprintf(':raw%d', $this->rawPos);

                return $ret;
                break;

            case 'raw' :
                // raw statement syntax
                $ret = sprintf(':raw%d', $this->rawPos++);

                return $ret;
                break;

            case 'bind' :
            default :
                // bind/filling values
                $ret = sprintf(':pos%d', $this->bindPos++);
                return $ret;
        }
    }

    /**
     * Prepare and Execute the SQL statement
     *
     * @param \PDO $PDO
     *
     * @return \PDOStatement
     */
    public function execute(\PDO $PDO)
    {
        // prepare the SQL
        $sql = join(' ', $this->SQL);

        // Replace raw placements with raw values
        foreach ($this->rawNamed as $name => $rVal)
        {
            $sql = preg_replace('/'.$name .'\b/', $rVal, $sql);
        }

        $stmt = $PDO->prepare($sql);

        // bind named parameters
        foreach ($this->named as $name => $sVal)
        {
            switch ($sVal['type'])
            {
                case \PDO::PARAM_BOOL :
                    $stmt->bindValue($name, (boolean)$sVal['value'], $sVal['type']);
                    break;

                case \PDO::PARAM_NULL :
                    $stmt->bindValue($name, NULL);
                    break;

                case \PDO::PARAM_INT :
                    $stmt->bindValue($name, (int)$sVal['value'], $sVal['type']);
                    break;

                case \PDO::PARAM_STR :
                default :
                    $stmt->bindValue($name, (string)$sVal['value'], $sVal['type']);
                    break;
            }
        }

        $stmt->execute();

        return $stmt;
    }

    /**
     * Used to assign a "positional" parameter which just ends up getting
     * translated to a named parameter magically.
     *
     * @param $matches
     *
     * @return int|string
     */
    private function placeholderFill($matches)
    {
        $key = $matches[0];

        // can't file this param
        if (!isset($this->named[$key]) && !isset($this->rawNamed[$key]))
        {
            return $key;
        }

        if (isset($this->named[$key]))
        {
            // here is the param
            $sVal = $this->named[$key];

            switch ($sVal['type'])
            {
                case \PDO::PARAM_BOOL :
                    return $sVal['value'] ? 'TRUE' : 'FALSE';

                case \PDO::PARAM_NULL :
                    return 'NULL';

                case \PDO::PARAM_INT :
                    return (int)$sVal['value'];

                case \PDO::PARAM_STR :
                default :
                    return "'" . $sVal['value'] . "'";
            }
        }
        else
        {
            if (isset($this->rawNamed[$key]))
            {
                return $this->rawNamed[$key];
            }
        }

        return false;
    }

    /**
     * Get name of the placeholder
     *
     * @return string
     */
    private function placeholderGetName()
    {
        return $this->getNextName('sql');
    }

    /**
     * Get name of the raw placeholder
     *
     * @return string
     */
    private function rawPlaceHolderGetName()
    {
        return $this->getNextName('rawSql');
    }

    /**
     * Builds up the SQL parameterized statement
     *
     * @param string $text
     *
     * @return $this
     */
    public function sql($text)
    {
        // replace positioned placeholders with named placeholders (first value)
        $text = preg_replace_callback('/\?/m', array($this, 'placeholderGetName'), $text);
        $text = preg_replace_callback('/%%/m', array($this, 'rawPlaceholderGetName'), $text);

        // just add the text as-is
        if (func_num_args() === 1)
        {
            $this->SQL[] = $text;
        }
        else
        {
            // treat as sprintf statement
            $args        = func_get_args();
            $args[0]     = $text;
            $this->SQL[] = call_user_func_array('sprintf', $args);
        }

        return $this;
    }

    /**
     * Reset / Clear out properties.
     *
     * @return $this
     */
    public function reset()
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
    public function getComputed()
    {
        // merge sql together
        $sql = join("\n", $this->SQL);

        // replace positioned placeholders with named placeholders (first value)
        $sql = preg_replace_callback('/:[a-z0-9_]+/m', array($this, 'placeholderFill'), $sql);

        return $sql;
    }

    /**
     * Return the SQL as a string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getComputed();
    }

    public function __debugInfo()
    {
        return [
            'Named Positions' => $this->named,
            'Unbound SQL'     => $this->SQL,
            'Bound SQL'       => $this->getComputed()
        ];
    }
}