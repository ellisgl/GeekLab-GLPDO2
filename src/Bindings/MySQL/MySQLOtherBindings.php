<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use GeekLab\GLPDO2\Bindings\OtherBindingInterface;

class MySQLOtherBindings implements OtherBindingInterface
{
    /**
     * Bind a string to the PDO data type.
     *
     * @param string|int|float|bool|null $value
     * @param int $type
     *
     * @return array
     */
    public function bValueType($value, int $type = \PDO::PARAM_STR): array
    {
        return [$value, $type];
    }

    /**
     * !!!DANGER!!!
     * Bind a raw value.
     *
     * @param string|int|float|bool $value
     *
     * @return array
     */
    public function bRaw($value): array
    {
        return [$value];
    }
}
