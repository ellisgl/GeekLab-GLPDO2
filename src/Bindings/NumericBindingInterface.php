<?php

namespace GeekLab\GLPDO2\Bindings;

use Exception;

interface NumericBindingInterface
{
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
    public function bFloat(float | int | string | null $value = null, int $decimals = 3, bool $null = false): array;

    /**
     * Bind an integer with optional NULL.
     *
     * @param float | bool | int | string | null $value
     * @param bool                               $null
     *
     * @return array
     * @throws Exception
     */
    public function bInt(float | bool | int | string | null $value = null, bool $null = false): array;

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
