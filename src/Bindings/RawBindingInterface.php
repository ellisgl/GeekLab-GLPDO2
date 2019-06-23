<?php

namespace GeekLab\GLPDO2\Bindings;

interface RawBindingInterface
{
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
