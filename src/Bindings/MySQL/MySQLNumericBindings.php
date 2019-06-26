<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use \PDO;
use \InvalidArgumentException;
use GeekLab\GLPDO2\Bindings\NumericBindingInterface;

class MySQLNumericBindings implements NumericBindingInterface
{
    /**
     * Bind a float.
     *
     * @param string|int|float|null $value
     * @param int $decimals
     * @param bool $null
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function bFloat($value = null, $decimals = 3, $null = false): array
    {
        // Use NULL?
        if ($value === null && $null) {
            return ['NULL'];
        }

        if ($value === null && !$null) {
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
     * @param string|int|float|bool|null $value
     * @param bool $null
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function bInt($value = null, bool $null = false): array
    {
        // Use NULL?
        if ($value === null && $null) {
            return [null, PDO::PARAM_NULL];
        }

        if ($value === null && !$null) {
            throw new InvalidArgumentException('Can not bind NULL in integer spot.');
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Can not bind "' . $value . '" in integer spot.');
        }

        return [(int) sprintf('%u', $value), PDO::PARAM_INT];
    }

    /**
     * Convert array of integers to comma separated values. Uses %%
     * Great for IN() statements.
     *
     * @param array $data
     * @param int $default
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function bIntArray(array $data, int $default = 0): array
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Can not bind an empty array.');
        }

        // Make unique integer array
        $numbers = array();

        foreach ($data as $value) {
            $numbers[(int) $value] = true;
        }

        $numbers = array_keys($numbers);

        // turn into a string
        $result = implode(', ', $numbers);

        return [$result ?: $default];
    }
}
