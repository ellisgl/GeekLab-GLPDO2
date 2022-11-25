<?php

namespace GeekLab\GLPDO2\Bindings;

interface RawBindingInterface
{
    /**
     * !!!DANGER!!!
     * Bind a raw value.
     *
     * @param float | bool | int | string $value
     *
     * @return array{float | bool | int | string}
     */
    public function bRaw(float | bool | int | string $value): array;
}
