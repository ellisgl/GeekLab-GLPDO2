<?php

namespace GeekLab\GLPDO2\Bindings;

use \TypeError;

interface LogicBindingInterface
{
    /**
     * Bind a boolean value as bool or null.
     *
     * @param int|bool|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bBoolNullable($value = null): array;

    /**
     * Bind a boolean value as bool.
     *
     * @param int|bool|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bBool(?$value): array;


    /**
     * Bind a boolean value as int or null.
     *
     * @param int|bool|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bBoolIntNullable($value = null): array;

    /**
     * Bind a boolean value as int.
     *
     * @param int|bool|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bBoolInt(?$value): array;
}
