<?php

namespace GeekLab\GLPDO2\Bindings;

use \TypeError;

interface DateTimeBindingInterface
{
    /**
     * Bind a date value as date or null.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bDateNullable(?string $value): array;

    /**
     * Bind a date value as date.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bDate(string $value): array;

    /**
     * Bind a date time value as date time or null.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bDateTimeNullable(?string $value = null): array;

    /**
     * Bind a date time value as date time.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bDateTime(string $value): array;
}
