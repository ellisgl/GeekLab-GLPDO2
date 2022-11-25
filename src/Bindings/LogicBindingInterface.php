<?php

namespace GeekLab\GLPDO2\Bindings;

use \Exception;

interface LogicBindingInterface
{
    /**
     * Bind a boolean value as bool, with NULL option.
     *
     * @param bool | int | null $value
     * @param bool              $null
     *
     * @return array
     * @throws Exception
     */
    public function bBool(bool | int | null $value = null, bool $null = false): array;

    /**
     * Bind a boolean value as int, with NULL option.
     *
     * @param bool | int | null $value
     * @param bool              $null
     *
     * @return array
     * @throws Exception
     */
    public function bBoolInt(bool | int | null $value = null, bool $null = false): array;
}
