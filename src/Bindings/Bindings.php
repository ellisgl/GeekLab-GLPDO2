<?php

namespace GeekLab\GLPDO2\Bindings;

use \PDO;
use \Exception;
use \DomainException;
use \JsonException;

class Bindings
{
    /** @var DateTimeBindingInterface $dateTime */
    protected $dateTime;

    /** @var LogicBindingInterface $logic */
    protected $logic;

    /** @var NumericBindingInterface $numeric */
    protected $numeric;

    /** @var RawBindingInterface $raw */
    protected $raw;

    /** @var StringBindingInterface $string */
    protected $string;

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
     * Bind a boolean value as bool or null.
     *
     * @param int|bool|null $value
     *
     * @return array
     * @throws Exception
     */
    public function bBoolNullable($value = null): array
    {
        return $this->logic->bBoolNullable($value);
    }

    /**
     * Bind a boolean value as bool.
     *
     * @param int|bool $value

     *
     * @return array
     * @throws Exception
     */
    public function bBool($value): array
    {
        return $this->logic->bBool($value);
    }

    /**
     * Bind a boolean value as int or null.
     *
     * @param int|bool|null $value
     *
     * @return array
     * @throws Exception
     */
    public function bBoolIntNullable($value = null): array
    {
        return $this->logic->bBoolIntNullable($value);
    }

    /**
     * Bind a boolean value as int.
     *
     * @param int|bool $value
     *
     * @return array
     * @throws Exception
     */
    public function bBoolInt($value): array
    {
        return $this->logic->bBoolInt($value);
    }

    /**
     * Bind a date value as date or null.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string|null $value
     *
     * @return array
     */
    public function bDateNullable(?string $value = null): array
    {
        return $this->dateTime->bDateNullable($value);
    }

    /**
     * Bind a date value as date.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string $value
     *
     * @return array
     */
    public function bDate(string $value): array
    {
        return $this->dateTime->bDate($value);
    }

    /**
     * Bind a date time value as date time or null.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string|null $value
     *
     * @return array
     */
    public function bDateTimeNullable(?string $value = null): array
    {
        return $this->dateTime->bDateTimeNullable($value);
    }

    /**
     * Bind a date value as date time.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string $value

     *
     * @return array
     */
    public function bDateTime(string $value): array
    {
        return $this->dateTime->bDateTime($value);
    }

    /**
     * Bind a float or null.
     *
     * @param string|int|float|null $value
     * @param int $decimals
     *
     * @return array
     * @throws Exception
     */
    public function bFloatNullable($value = null, $decimals = 3): array
    {
        return $this->numeric->bFloatNullable($value, $decimals);
    }

    /**
     * Bind a float.
     *
     * @param string|int|float $value
     * @param int $decimals
     *
     * @return array
     * @throws Exception
     */
    public function bFloat($value, $decimals = 3): array
    {
        return $this->numeric->bFloat($value, $decimals);
    }

    /**
     * Bind an integer or null.
     *
     * @param string|int|float|bool|null $value
     * @param bool $null
     *
     * @return array
     * @throws Exception
     */
    public function bIntNullable($value = null): array
    {
        return $this->numeric->bIntNullable($value);
    }

    /**
     * Bind an integer.
     *
     * @param string|int|float|bool $value
     * @param bool $null
     *
     * @return array
     * @throws Exception
     */
    public function bInt($value): array
    {
        return $this->numeric->bInt($value);
    }

    /**
     * Convert array of integers to comma separated values. Uses %%
     * Great for IN() statements.
     *
     * @param array $data
     * @param int $default
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
     * @param string|object|null $value
     * @param bool $null
     *
     * @return array
     * @throws JsonException
     */
    public function bJSON($value, bool $null = false): array
    {
        return $this->string->bJSON($value, $null);
    }

    /**
     * Create and bind string for LIKE() statements.
     *
     * @param string $value
     * @param bool $ends Ends with?
     * @param bool $starts Starts with?
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
     * @param string|int|float|bool $value
     *
     * @return array
     */
    public function bRaw($value): array
    {
        return $this->raw->bRaw($value);
    }

    /**
     * Bind a string value.
     *
     * @param string|int|float|bool|null $value
     * @param bool $null
     * @param int $type
     *
     * @return array
     * @throws Exception
     */
    public function bStr($value, bool $null = false, int $type = PDO::PARAM_STR): array
    {
        return $this->string->bStr($value, $null, $type);
    }

    /**
     * Convert an array into a string and bind it.
     * Great for IN() statements.
     *
     * @param array $values
     * @param string|int|float|bool $default
     *
     * @return array
     */
    public function bStrArr(array $values, $default = ''): array
    {
        return $this->string->bStrArr($values, $default);
    }
}
