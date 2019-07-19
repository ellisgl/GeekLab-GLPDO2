<?php

namespace GeekLab\GLPDO2\Bindings;

use \PDO;
use \JsonException;
use \TypeError;

interface StringBindingInterface
{
    /**
     * Bind a JSON to string or null.
     *
     * @param string|object|null $value
     *
     * @return array
     * @throws JsonException
     * @throws TypeError
     */
    public function bJsonNullable($value): array;

    /**
     * Bind a JSON to string.
     *
     * @param string|object|null $value
     *
     * @return array
     * @throws JsonException
     * @throws TypeError
     */
    public function bJson($value): array;

    /**
     * Create and bind string for LIKE() statements.
     *
     * @param string $value
     * @param bool $ends Ends with?
     * @param bool $starts Starts with?
     *
     * @return array
     */
    public function bLike(string $value, bool $ends = false, bool $starts = false): array;

    /**
     * Bind a string value or null.
     *
     * @param string|int|float|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bStrNullable($value): array;

    /**
     * Bind a string value.
     *
     * @param string|int|float|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bStr($value): array;

    /**
     * Convert an array into a string and bind it.
     * Great for IN() statements.
     *
     * @param array $values
     * @param string|int|float|bool $default
     *
     * @return array
     */
    public function bStrArr(array $values, $default = ''): array;
}
