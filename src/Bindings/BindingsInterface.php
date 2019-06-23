<?php

namespace GeekLab\GLPDO2\Bindings;

use DomainException;
use Exception;
use PDO;

interface BindingsInterface
{
    /**
     * Bind a boolean value as bool, with NULL option.
     *
     * @param int|bool|null $value
     * @param bool $null
     *
     * @return array
     * @throws Exception
     */
    public function bBool($value = null, bool $null = false): array;

    /**
     * Bind a boolean value as int, with NULL option.
     *
     * @param int|bool|null $value
     * @param bool $null
     *
     * @return array
     * @throws Exception
     */
    public function bBoolInt($value = null, bool $null = false): array;

    /**
     * Bind a date value as date or optional NULL.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string|null $value
     * @param bool $null
     *
     * @return array
     * @throws Exception
     */
    public function bDate(?string $value, bool $null = false): array;

    /**
     * Bind a date value as date time or optional NULL.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string|null $value
     * @param bool $null
     *
     * @return array
     * @throws Exception
     */
    public function bDateTime($value = null, bool $null = false): array;

    /**
     * Bind a float.
     *
     * @param string|int|float|null $value
     * @param int $decimals
     * @param bool $null
     *
     * @return array
     * @throws Exception
     */
    public function bFloat($value = null, $decimals = 3, $null = false): array;

    /**
     * Bind an integer with optional NULL.
     *
     * @param string|int|float|bool|null $value
     * @param bool $null
     *
     * @return array
     * @throws Exception
     */
    public function bInt($value = null, bool $null = false): array;

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
    public function bIntArray(array $data, int $default = 0): array;

    /**
     * Bind a object or JSON string to a string
     *
     * @param string|object|null $value
     * @param bool $null
     *
     * @return array
     * @throws \JsonException
     */
    public function bJSON($value, bool $null = false): array;

    /**
     * Create and bind string for LIKE() statements.
     *
     * @param string $value
     * @param bool $ends Ends with?
     * @param bool $starts Starts with?
     *
     * @return array
     */
    public function bLike(string $value, bool $ends = false, bool $starts = false): array;

    /**
     * !!!DANGER!!!
     * Bind a raw value.
     *
     * @param string|int|float|bool $value
     *
     * @return array
     */
    public function bRaw($value): array;

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
    public function bStr($value, bool $null = false, int $type = PDO::PARAM_STR): array;

    /**
     * Convert an array into a string and bind it.
     * Great for IN() statements.
     *
     * @param array $values
     * @param string|int|float|bool $default
     *
     * @return array
     */
    public function bStrArr(array $values, $default = ''): array;
}
