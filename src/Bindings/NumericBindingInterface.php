<?php

namespace GeekLab\GLPDO2\Bindings;

use \Exception;

interface NumericBindingInterface
{
    /**
     * Bind a float or null
     *
     * @param string|int|float|null $value
     * @param int $decimals
     *
     * @return array
     * @throws Exception
     */
    public function bFloatNullable($value = null, $decimals = 3): array;

    /**
     * Bind a float as float/decimal.
     *
     * @param string|int|float $value
     * @param int $decimals
     *
     * @return array
     * @throws Exception
     */
    public function bFloat($value, $decimals = 3): array;

    /**
     * Bind an integer or null.
     *
     * @param string|int|float|bool|null $value

     *
     * @return array
     * @throws Exception
     */
    public function bIntNullable($value = null): array;

    /**
     * Bind an integer.
     *
     * @param string|int|float|bool $value
     *
     * @return array
     * @throws Exception
     */
    public function bInt($value): array;

    /**
     * Convert array of integers to comma separated values. Uses %%.
     * Great for IN() statements.
     *
     * @param array $data
     * @param int $default
     *
     * @return array
     * @throws Exception
     */
    public function bIntArray(array $data, int $default = 0): array;
}
