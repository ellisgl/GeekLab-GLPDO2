<?php

namespace GeekLab\GLPDO2\Bindings;

use \Exception;

interface DateTimeBindingInterface
{
    /**
     * Bind a date value as date or optional NULL.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string|null $value
     * @param bool $null
     *
     * @return array
     * @throws Exception
     */
    public function bDate(?string $value, bool $null = false): array;

    /**
     * Bind a date value as date time or optional NULL.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string|null $value
     * @param bool $null
     *
     * @return array
     * @throws Exception
     */
    public function bDateTime($value = null, bool $null = false): array;
}
