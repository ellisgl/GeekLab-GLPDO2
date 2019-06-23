<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use \Exception;
use \JsonException;
use \DomainException;

use GeekLab\GLPDO2\Bindings\StringBindingInterface;
use PDO;

class MySQLStringBindings implements StringBindingInterface
{

    /**
     * @param object|string|null $value
     * @param bool $null
     *
     * @return array
     * @throws Exception
     */
    public function bJSON($value, bool $null = false): array
    {
        // Use NULL?
        if ($value === null && $null) {
            return $this->bStr(null, true);
        }

        if ($value === null && !$null) {
            throw new JsonException('Can not bind NULL in JSON spot.');
        }

        if (is_object($value)) {
            $value = json_encode($value);
        } elseif (is_string($value)) {
            $JSON = json_decode($value, false, 255);

            if (json_last_error()) {
                throw new JsonException('Can not bind invalid JSON in JSON spot. (' . json_last_error_msg() . ')');
            }

            $value = json_encode($JSON);
        } else {
            throw new JsonException('Can not bind invalid JSON in JSON spot. (' . $value . ')');
        }

        return $this->bStr($value);
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
     * Bind a string value.
     *
     * @param string|int|float|bool|null $value
     * @param bool $null
     * @param int $type
     *
     * @return array
     * @throws Exception
     */
    public function bStr($value, bool $null = false, int $type = PDO::PARAM_STR): array
    {
        if ($value === null && $null) {
            $type = PDO::PARAM_NULL;
        } elseif ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in string spot.');
        }

        return [(string) $value, $type];
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
