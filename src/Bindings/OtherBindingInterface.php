<?php

namespace GeekLab\GLPDO2\Bindings;

use \PDO;

interface OtherBindingInterface
{
    /**
     * Bind a string to the PDO data type.
     *
     * @param string|int|float|bool|null $value
     * @param int $type
     *
     * @return array
     */
    public function bValueType($value, int $type = PDO::PARAM_STR): array;

    /**
     * !!!DANGER!!!
     * Bind a raw value.
     *
     * @param string|int|float|bool $value
     *
     * @return array
     */
    public function bRaw($value): array;
}
