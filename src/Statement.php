<?php

namespace GeekLab\GLPDO2;

use PDO;
use PDOStatement;
use Exception;
use GeekLab\GLPDO2\Bindings\Bindings;

class Statement
{
    private Bindings $bindings;   // Parameter bindings.
    private int $bindPos = 0;     // Position for SQL binds.
    /**
     * @var array{} | array{mixed} $named
     */
    private array $named = [];    // Named binding values.
    /**
     * @var array{} | array{mixed} $SQL
     */
    private array $SQL = [];      // SQL Statement.
    private int $sqlPos = 0;      // Position holder for statement processing.
    /**
     * @var array{} | array<int|string, mixed> $rawNamed
     */
    private array $rawNamed = []; // Raw named placeholders.
    private int $rawPos = 0;      // Position holder for raw statement processing.
    // /**
    //  * @var array{} | array{mixed} $rawSql
    //  */
    // private array $rawSql = [];   // SQL Statement.

    public function __construct(Bindings $bindings)
    {
        $this->bindings = $bindings;
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
            $sql = (string)preg_replace('/' . $name . '\b/', $rVal, $sql);
        }

        return $sql;
    }

    /**
     * Bind a value to a named parameter.
     *
     * @param string                             $name
     * @param float | bool | int | string | null $value
     * @param int                                $type
     *
     * @return Statement
     */
    public function bind(string $name, float | bool | int | string | null $value, int $type = PDO::PARAM_STR): self
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
     * @param string                      $name
     * @param float | bool | int | string $value
     *
     * @return Statement
     */
    public function rawBind(string $name, float | bool | int | string $value): self
    {
        $this->rawNamed[$name] = $value;
        return $this;
    }

    // Bind types

    /**
     * Bind a boolean value as bool, with NULL option.
     *
     * @param bool | int | null $value
     * @param bool              $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bBool(bool | int | null $value = null, bool $null = false): self
    {
        $binding = $this->bindings->bBool($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

        return $this;
    }

    /**
     * Bind a boolean value as int, with NULL option.
     *
     * @param bool | int | null $value
     * @param bool              $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bBoolInt(bool | int | null $value = null, bool $null = false): self
    {
        $binding = $this->bindings->bBoolInt($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

        return $this;
    }

    /**
     * Bind a date value as date or optional NULL.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string | null $value
     * @param bool          $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bDate(?string $value = null, bool $null = false): self
    {
        $binding = $this->bindings->bDate($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

        return $this;
    }

    /**
     * Bind a date value as date time or optional NULL.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string | null $value
     * @param bool          $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bDateTime(?string $value = null, bool $null = false): self
    {
        $binding = $this->bindings->bDateTime($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

        return $this;
    }

    /**
     * Bind a float.
     *
     * @param float | int | string | null $value
     * @param int                         $decimals
     * @param bool                        $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bFloat(float | int | string | null $value = null, int $decimals = 3, bool $null = false): self
    {
        $binding = $this->bindings->bFloat($value, $decimals, $null);
        $this->rawBind($this->getNextName('raw'), $binding[0]);

        return $this;
    }


    /**
     * Bind an integer with optional NULL.
     *
     * @param float | bool | int | string | null $value
     * @param bool                               $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bInt(float | bool | int | string | null $value = null, bool $null = false): self
    {
        $binding = $this->bindings->bInt($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

        return $this;
    }

    /**
     * Convert array of integers to comma separated values. Uses %%
     * Great for IN() statements.
     *
     * @param array{} | array{mixed} $data
     *
     * @return Statement
     * @throws Exception
     */
    public function bIntArray(array $data): self
    {
        $binding = $this->bindings->bIntArray($data);
        $this->rawBind($this->getNextName('raw'), $binding[0]);

        return $this;
    }

    /**
     * Bind an object or JSON string to a string
     *
     * @param object | string | null $value
     * @param bool                   $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bJSON(object | string | null $value, bool $null = false): self
    {
        $binding = $this->bindings->bJSON($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

        return $this;
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
        $binding = $this->bindings->bLike($value, $ends, $starts);
        $this->bind($this->getNextName(), $binding[0]);

        return $this;
    }

    /**
     * Convert an array into a string and bind it.
     * Great for IN() statements.
     *
     * @param array{} | array{mixed}      $values
     * @param float | bool | int | string $default
     *
     * @return Statement
     */
    public function bStrArr(array $values, float | bool | int | string $default = ''): self
    {
        $binding = $this->bindings->bStrArr($values, $default);
        $this->rawBind($this->getNextName('raw'), $binding[0]);

        return $this;
    }

    /**
     * !!!DANGER!!!
     * Bind a raw value.
     *
     * @param float | bool | int | string $value
     *
     * @return Statement
     */
    public function bRaw(float | bool | int | string $value): self
    {
        $binding = $this->bindings->bRaw($value);
        $this->rawBind($this->getNextName('raw'), $binding[0]);

        return $this;
    }

    /**
     * Bind a string value.
     *
     * @param float | bool | int | string | null $value
     * @param bool                               $null
     * @param int                                $type
     *
     * @return Statement
     * @throws Exception
     */
    public function bStr(
        float | bool | int | string | null $value,
        bool $null = false,
        int $type = PDO::PARAM_STR
    ): self {
        $binding = $this->bindings->bStr($value, $null, $type);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

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
        return match ($type) {
            'sql' => sprintf(':pos%d', $this->sqlPos++),
            'rawSql' => sprintf(':raw%d', $this->rawPos),
            'raw' => sprintf(':raw%d', $this->rawPos++),
            default => sprintf(':pos%d', $this->bindPos++),
        };
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
        // Then replace raw placements with raw values.
        $sql = $this->rawPlaceholderFill(implode(' ', $this->SQL));

        /** @var PDOStatement $stmt */
        $stmt = $PDO->prepare($sql);

        // Bind named parameters.
        foreach ($this->named as $name => $sVal) {
            switch ($sVal['type']) {
                case PDO::PARAM_BOOL:
                    $stmt->bindValue($name, (bool)$sVal['value'], $sVal['type']);
                    break;

                case PDO::PARAM_NULL:
                    $stmt->bindValue($name, null);
                    break;

                case PDO::PARAM_INT:
                    $stmt->bindValue($name, (int)$sVal['value'], $sVal['type']);
                    break;

                case PDO::PARAM_STR:
                default:
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
     * @param array{} | array{int|string} $matches
     *
     * @return mixed
     * @throws Exception
     */
    private function placeholderFill(array $matches): mixed
    {
        if (empty($matches)) {
            return null;
        }

        $key = $matches[0];

        // Can't fill this param.
        if (!isset($this->named[$key]) && !isset($this->rawNamed[$key])) {
            return $key;
        }

        if (isset($this->named[$key])) {
            // here is the param
            $sVal = $this->named[$key];

            return match ($sVal['type']) {
                PDO::PARAM_BOOL => $sVal['value'] ? 'TRUE' : 'FALSE',
                PDO::PARAM_NULL => 'NULL',
                PDO::PARAM_INT => (int)$sVal['value'],
                default => "'" . $sVal['value'] . "'",
            };
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
        $text = (string)preg_replace_callback(
            '/\?/m',
            function () {
                return $this->placeholderGetName();
            },
            $text
        );

        $text = (string)preg_replace_callback(
            '/%%/m',
            function () {
                return $this->getNextName('rawSql');
            },
            $text
        );

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
        $this->rawNamed = [];
        $this->rawPos = 0;
        // $this->rawSql = [];

        return $this;
    }

    /**
     * Create what the SQL query string might look like.
     * Great for debugging. YMMV though.
     *
     * @return string
     * @throws Exception
     */
    public function getComputed(): string
    {
        // Merge SQL together
        $sql = implode("\n", $this->SQL);

        // Replace positioned placeholders with named placeholders (first value).
        // Force to string, in the case of null.
        return (string)preg_replace_callback(
            '/:[a-z0-9_]+/m',
            function ($matches) {
                return $this->placeholderFill($matches);
            },
            $sql
        );
    }

    /**
     * Return the SQL as a string.
     *
     * @return string
     * @throws Exception
     */
    public function __toString(): string
    {
        return $this->getComputed();
    }

    /**
     * Magic Method for debugging.
     *
     * @return array{ 'Named Positions': array{} | array{mixed}, 'Unbound SQL': array{} | array{mixed}, 'Bound SQL': string }
     * @throws Exception
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
