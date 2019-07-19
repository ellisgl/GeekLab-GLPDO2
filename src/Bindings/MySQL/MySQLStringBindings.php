<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use \JsonException;
use \PDO;
use \stdClass;
use \TypeError;
use GeekLab\GLPDO2\Bindings\StringBindingInterface;

class MySQLStringBindings implements StringBindingInterface
{
    /**
     * Bind JSON to string or null.
     *
     * @param object|string|null $value
     *
     * @return array
     * @throws JsonException
     * @throws TypeError
     */
    public function bJsonNullable($value): array
    {
        // Use NULL?
        if ($value === null) {
            return $this->bStrNullable(null);
        }

        return $this->bJSON($value);
    }

    /**
     * Bind JSON to string.
     *
     * @param object|string|null $value
     *
     * @return array
     * @throws JsonException
     * @throws TypeError
     */
    public function bJson($value): array
    {
        if ($value === null) {
            throw new TypeError('Can not bind NULL in JSON spot.');
        }

        if (is_object($value)) {
            $json = json_encode($value);

            if (json_last_error()) {
                throw new JsonException(json_last_error_msg());
            }

            return [$json, PDO::PARAM_STR];
        }

        if (is_string($value)) {
            /** @var stdClass $JSON */
            $json = json_decode($value, false, 255);

            if (json_last_error()) {
                throw new JsonException(json_last_error_msg());
            }

            $json = json_encode($json);

            return [$json, PDO::PARAM_STR];
        }

        throw new TypeError('Can not bind ' . gettype($value) . ': ( ' . $value . ') in JSON spot.');
    }

    /**
     * Create and bind string for LIKE() statements.
     *
     * @param string $value
     * @param bool $ends Ends with?
     * @param bool $starts Starts with?
     *
     * @return array
     */
    public function bLike(string $value, bool $ends = false, bool $starts = false): array
    {
        $arr = ['%', $value, '%'];

        if ($starts) {
            // Starts with.
            array_shift($arr);
        }

        if ($ends) {
            // Ends with.
            array_pop($arr);
        }

        return [implode('', $arr)];
    }

    /**
     * Bind a string value or null
     *
     * @param string|int|float|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bStrNullable($value): array
    {
        if ($value === null) {
            return [null, PDO::PARAM_NULL];
        }

        return $this->bStr($value);
    }


    /**
     * Bind a string.
     *
     * @param string|int|float|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bStr($value): array
    {
        if ($value === null) {
            throw new TypeError('Can not bind NULL in string spot.');
        }

        return [(string) $value, PDO::PARAM_STR];
    }

    /**
     * Convert an array into a string and bind it.
     * Great for IN() statements.
     *
     * @param array $values
     * @param string|int|float|bool $default
     *
     * @return array
     */
    public function bStrArr(array $values, $default = ''): array
    {
        return [empty($values) ? $default : '\'' . implode("', '", $values) . '\''];
    }
}
