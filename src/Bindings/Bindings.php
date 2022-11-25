<?php

namespace GeekLab\GLPDO2\Bindings;

use PDO;
use Exception;
use DomainException;
use JsonException;

class Bindings
{
    protected DateTimeBindingInterface $dateTime;
    protected LogicBindingInterface $logic;
    protected NumericBindingInterface $numeric;
    protected RawBindingInterface $raw;
    protected StringBindingInterface $string;

    public function __construct(
        DateTimeBindingInterface $dateTime,
        LogicBindingInterface $logic,
        NumericBindingInterface $numeric,
        RawBindingInterface $raw,
        StringBindingInterface $string
    ) {
        $this->dateTime = $dateTime;
        $this->logic = $logic;
        $this->numeric = $numeric;
        $this->raw = $raw;
        $this->string = $string;
    }

    /**
     * Bind a boolean value as bool, with NULL option.
     *
     * @param bool | int | null $value
     * @param bool              $null
     *
     * @return array
     * @throws Exception
     */
    public function bBool(bool | int | null $value = null, bool $null = false): array
    {
        return $this->logic->bBool($value, $null);
    }

    /**
     * Bind a boolean value as int, with NULL option.
     *
     * @param bool | int | null $value
     * @param bool              $null
     *
     * @return array
     * @throws Exception
     */
    public function bBoolInt(bool | int | null $value = null, bool $null = false): array
    {
        return $this->logic->bBoolInt($value, $null);
    }

    /**
     * Bind a date value as date or optional NULL.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string | null $value
     * @param bool          $null
     *
     * @return array
     * @throws Exception
     */
    public function bDate(?string $value, bool $null = false): array
    {
        return $this->dateTime->bDate($value, $null);
    }

    /**
     * Bind a date value as date time or optional NULL.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string | null $value
     * @param bool          $null
     *
     * @return array
     * @throws Exception
     */
    public function bDateTime(?string $value = null, bool $null = false): array
    {
        return $this->dateTime->bDateTime($value, $null);
    }

    /**
     * Bind a float.
     *
     * @param float | int | string | null $value
     * @param int                         $decimals
     * @param bool                        $null
     *
     * @return array
     * @throws Exception
     */
    public function bFloat(float | int | string | null $value = null, int $decimals = 3, bool $null = false): array
    {
        return $this->numeric->bFloat($value, $decimals, $null);
    }

    /**
     * Bind an integer with optional NULL.
     *
     * @param float | bool | int | string | null $value
     * @param bool                               $null
     *
     * @return array
     * @throws Exception
     */
    public function bInt(float | bool | int | string | null $value = null, bool $null = false): array
    {
        return $this->numeric->bInt($value, $null);
    }

    /**
     * Convert array of integers to comma separated values. Uses %%
     * Great for IN() statements.
     *
     * @param array $data
     * @param int   $default
     *
     * @return array
     * @throws Exception
     */
    public function bIntArray(array $data, int $default = 0): array
    {
        return $this->numeric->bIntArray($data, $default);
    }

    /**
     * Bind a object or JSON string to a string
     *
     * @param object | string | null $value
     * @param bool                   $null
     *
     * @return array
     * @throws JsonException
     */
    public function bJSON(object | string | null $value, bool $null = false): array
    {
        return $this->string->bJSON($value, $null);
    }

    /**
     * Create and bind string for LIKE() statements.
     *
     * @param string $value
     * @param bool   $ends   Ends with?
     * @param bool   $starts Starts with?
     *
     * @return array
     */
    public function bLike(string $value, bool $ends = false, bool $starts = false): array
    {
        return $this->string->bLike($value, $ends, $starts);
    }

    /**
     * !!!DANGER!!!
     * Bind a raw value.
     *
     * @param float | bool | int | string $value
     *
     * @return array
     */
    public function bRaw(float | bool | int | string $value): array
    {
        return $this->raw->bRaw($value);
    }

    /**
     * Bind a string value.
     *
     * @param float | bool | int | string | null $value
     * @param bool                               $null
     * @param int                                $type
     *
     * @return array
     * @throws Exception
     */
    public function bStr(
        float | bool | int | string | null $value,
        bool $null = false,
        int $type = PDO::PARAM_STR
    ): array {
        return $this->string->bStr($value, $null, $type);
    }

    /**
     * Convert an array into a string and bind it.
     * Great for IN() statements.
     *
     * @param array                       $values
     * @param float | bool | int | string $default
     *
     * @return array
     */
    public function bStrArr(array $values, float | bool | int | string $default = ''): array
    {
        return $this->string->bStrArr($values, $default);
    }
}
