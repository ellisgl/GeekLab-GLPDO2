<?php

namespace GeekLab\GLPDO2\Bindings;

use \Exception;

interface LogicBindingInterface
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
}
