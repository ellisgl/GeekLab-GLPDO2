<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use \PDO;
use \InvalidArgumentException;
use \TypeError;
use GeekLab\GLPDO2\Bindings\NumericBindingInterface;

class MySQLNumericBindings implements NumericBindingInterface
{
    /**
     * Bind a float or null
     *
     * @param string|int|float|null $value
     * @param int $decimals
     *
     * @return array
     * @throws TypeError
     */
    public function bFloatNullable($value = null, $decimals = 3): array
    {
        // Use NULL?
        if ($value === null) {
            return ['NULL'];
        }

        return $this->bFloat($value, $decimals);
    }

    /**
     * Bind a float.
     *
     * @param string|int|float $value
     * @param int $decimals
     *
     * @return array
     * @throws TypeError
     */
    public function bFloat($value, $decimals = 3): array
    {
        if (!is_numeric($value)) {
            throw new TypeError('Can not bind "' . $value . '" in float spot.');
        }

        $format = sprintf('%%0.%df', $decimals);

        // Apparently using PDO::PARAM_STR makes this fail!
        return [sprintf($format, $value)];
    }

    /**
     * Bind an integer or null.
     *
     * @param string|int|float|bool|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bIntNullable($value = null): array
    {
        // Use NULL?
        if ($value === null) {
            return [null, PDO::PARAM_NULL];
        }

        return $this->bInt($value);
    }

    /**
     * Bind an integer.
     *
     * @param string|int|float|bool $value
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function bInt($value): array
    {
        if (!is_numeric($value)) {
            throw new TypeError('Can not bind "' . $value . '" in integer spot.');
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
     * @throws TypeError
     */
    public function bIntArray(array $data, int $default = 0): array
    {
        if (empty($data)) {
            throw new TypeError('Can not bind an empty array.');
        }

        // Make unique integer array
        $numbers = [];

        foreach ($data as $value) {
            $numbers[(int) $value] = true;
        }

        $numbers = array_keys($numbers);

        // turn into a string
        $result = implode(', ', $numbers);

        return [$result ?: $default];
    }
}
