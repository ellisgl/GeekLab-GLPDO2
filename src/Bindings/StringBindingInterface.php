<?php

namespace GeekLab\GLPDO2\Bindings;

use PDO;
use Exception;
use JsonException;

interface StringBindingInterface
{
    /**
     * Bind an object or JSON string to a string.
     *
     * @param object | string | null $value
     * @param bool                   $null
     *
     * @return array{string, int}
     * @throws JsonException
     */
    public function bJSON(object | string | null $value, bool $null = false): array;

    /**
     * Create and bind string for LIKE() statements.
     *
     * @param string $value
     * @param bool   $ends   Ends with?
     * @param bool   $starts Starts with?
     *
     * @return array{string}
     */
    public function bLike(string $value, bool $ends = false, bool $starts = false): array;

    /**
     * Bind a string value.
     *
     * @param float | bool | int | string | null $value
     * @param bool                               $null
     * @param int                                $type
     *
     * @return array{string, int}
     * @throws Exception
     */
    public function bStr(
        float | bool | int | string | null $value,
        bool $null = false,
        int $type = PDO::PARAM_STR
    ): array;

    /**
     * Convert an array into a string and bind it.
     * Great for IN() statements.
     *
     * @param array{} | array{mixed}      $values
     * @param float | bool | int | string $default
     *
     * @return array{float | bool | int | string}
     */
    public function bStrArr(array $values, float | bool | int | string $default = ''): array;
}
