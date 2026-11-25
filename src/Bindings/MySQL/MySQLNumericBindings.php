<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use PDO;
use InvalidArgumentException;
use GeekLab\GLPDO2\Bindings\UnsignedNumericBindingInterface;

class MySQLNumericBindings implements UnsignedNumericBindingInterface
{
    /** @const string UINT_MAX The largest value bUInt() accepts: BIGINT UNSIGNED's maximum, 2^64 - 1. */
    protected const UINT_MAX = '18446744073709551615';

    /**
     * Bind a float.
     *
     * @param float | int | string | null $value
     * @param int                         $decimals
     * @param bool                        $null
     *
     * @return array{string}
     * @throws InvalidArgumentException
     */
    public function bFloat(float | int | string | null $value = null, int $decimals = 3, bool $null = false): array
    {
        // Use NULL?
        if ($value === null) {
            if ($null) {
                return ['NULL'];
            }

            throw new InvalidArgumentException('Can not bind NULL in float spot.');
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Can not bind "' . $value . '" in float spot.');
        }

        $format = sprintf('%%0.%df', $decimals);

        // Apparently using PDO::PARAM_STR makes this fail!
        return [sprintf($format, $value)];
    }

    /**
     * Bind an integer with optional NULL.
     *
     * @param float | bool | int | string | null $value
     * @param bool                               $null
     *
     * @return array{?int, int}
     * @throws InvalidArgumentException
     */
    public function bInt(float | bool | int | string | null $value = null, bool $null = false): array
    {
        // Use NULL?
        if ($value === null) {
            if ($null) {
                return [null, PDO::PARAM_NULL];
            }

            throw new InvalidArgumentException('Can not bind NULL in integer spot.');
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Can not bind "' . $value . '" in integer spot.');
        }

        // Numeric strings become int or float; floats are truncated toward zero, like (int).
        $number = +$value;

        if (is_float($number)) {
            // An integer-only string that still became a float overflowed the integer range.
            $overflowed = is_string($value) && preg_match('/^\s*[+-]?\d+\s*$/', $value) === 1;

            // (int) on a float outside the integer range gives a meaningless value, so refuse it.
            // (float)PHP_INT_MAX rounds up to 2^63, which is already out of range, hence >=.
            if ($overflowed || !is_finite($number) || $number >= PHP_INT_MAX || $number < PHP_INT_MIN) {
                throw new InvalidArgumentException(
                    'Can not bind ' . var_export($value, true) . ' in integer spot: out of range.'
                );
            }

            $number = (int)$number;
        }

        return [$number, PDO::PARAM_INT];
    }

    /**
     * Convert array of integers to comma separated values. Uses %%
     * Great for IN() statements.
     *
     * @param array{} | array{mixed} $data
     *
     * @return array{string}
     * @throws InvalidArgumentException
     */
    public function bIntArray(array $data): array
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Can not bind an empty array.');
        }

        // Make unique integer array
        $numbers = [];

        foreach ($data as $value) {
            $numbers[(int)$value] = true;
        }

        $numbers = array_keys($numbers);

        // Turn into a comma delimited string.
        return [implode(', ', $numbers)];
    }

    /**
     * Bind an unsigned integer (0 to 18446744073709551615), with NULL option. Uses %%
     * The value goes into the SQL as digits, not as a bound parameter: MySQL compares a string parameter
     * with a number as a float, which can't tell large values apart. The digits are validated, so this is
     * safe for user input.
     *
     * @param float | bool | int | string | null $value
     * @param bool                               $null
     *
     * @return array{string}
     * @throws InvalidArgumentException
     */
    public function bUInt(float | bool | int | string | null $value = null, bool $null = false): array
    {
        // Use NULL?
        if ($value === null) {
            if ($null) {
                return ['NULL'];
            }

            throw new InvalidArgumentException('Can not bind NULL in unsigned integer spot.');
        }

        return [$this->toUInt($value)];
    }

    /**
     * Convert an array of unsigned integers to comma separated values. Uses %%
     * Great for IN() statements. Every value is validated as bUInt() does, and duplicates are removed.
     *
     * @param array{} | array{mixed} $data
     *
     * @return array{string}
     * @throws InvalidArgumentException
     */
    public function bUIntArray(array $data): array
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Can not bind an empty array.');
        }

        $numbers = [];

        foreach ($data as $value) {
            if (!is_scalar($value)) {
                throw new InvalidArgumentException('Can not bind a non-scalar value in an unsigned integer array.');
            }

            $numbers[$this->toUInt($value)] = true;
        }

        return [implode(', ', array_keys($numbers))];
    }

    /**
     * Validate an unsigned integer and return it as a string of digits.
     * Floats are truncated toward zero, as bInt() does. Values above PHP_INT_MAX must be integer strings,
     * since a float can't hold them exactly.
     *
     * @param float | bool | int | string $value
     *
     * @return string
     * @throws InvalidArgumentException
     */
    private function toUInt(float | bool | int | string $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        // Integer strings are checked digit by digit, so values above PHP_INT_MAX stay exact.
        if (is_string($value) && preg_match('/^\s*\+?0*(\d+)\s*$/', $value, $match) === 1) {
            $digits = $match[1];

            $max = static::UINT_MAX;

            if (strlen($digits) > strlen($max) || (strlen($digits) === strlen($max) && strcmp($digits, $max) > 0)) {
                throw new InvalidArgumentException(
                    'Can not bind ' . var_export($value, true) . ' in unsigned integer spot: out of range.'
                );
            }

            return $digits;
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException(
                'Can not bind ' . var_export($value, true) . ' in unsigned integer spot.'
            );
        }

        $number = +$value;

        if ($number < 0) {
            throw new InvalidArgumentException(
                'Can not bind ' . var_export($value, true) . ' in unsigned integer spot: negative.'
            );
        }

        if (is_float($number)) {
            // (float)PHP_INT_MAX rounds up to 2^63, hence >=. Larger values need an integer string.
            if (!is_finite($number) || $number >= PHP_INT_MAX) {
                throw new InvalidArgumentException(
                    'Can not bind ' . var_export($value, true) . ' in unsigned integer spot: out of range '
                    . 'for a float. Pass values above ' . PHP_INT_MAX . ' as an integer string.'
                );
            }

            $number = (int)$number;
        }

        return (string)$number;
    }
}
