<?php

namespace GeekLab\GLPDO2\Bindings;

use \Exception;

interface LogicBindingInterface
{
    /**
     * Bind a boolean value as bool or null.
     *
     * @param int|bool|null $value
     *
     * @return array
     * @throws Exception
     */
    public function bBoolNullable($value = null): array;

    /**
     * Bind a boolean value as bool.
     *
     * @param int|bool $value
     *
     * @return array
     * @throws Exception
     */
    public function bBool($value): array;


    /**
     * Bind a boolean value as int or null.
     *
     * @param int|bool $value
     *
     * @return array
     * @throws Exception
     */
    public function bBoolIntNullable($value = null): array;

    /**
     * Bind a boolean value as int.
     *
     * @param int|bool $value
     *
     * @return array
     * @throws Exception
     */
    public function bBoolInt($value): array;
}
