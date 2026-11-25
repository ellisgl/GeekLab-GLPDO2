<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use PDO;
use InvalidArgumentException;
use GeekLab\GLPDO2\Bindings\NumericBindingInterface;

class MySQLNumericBindings implements NumericBindingInterface
{
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
}
