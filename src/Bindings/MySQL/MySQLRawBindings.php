<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use GeekLab\GLPDO2\Bindings\RawBindingInterface;

class MySQLRawBindings implements RawBindingInterface
{
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
