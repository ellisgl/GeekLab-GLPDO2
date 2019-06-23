<?php

namespace GeekLab\GLPDO2\Bindings;

use \Exception;

interface NumericBindingInterface
{
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
}
