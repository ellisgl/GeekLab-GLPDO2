<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use \PDO;
use \InvalidArgumentException;
use \TypeError;
use GeekLab\GLPDO2\Bindings\LogicBindingInterface;

class MySQLLogicBindings implements LogicBindingInterface
{
    /**
     * Bind a boolean value as bool or null.
     *
     * @param int|bool|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bBoolNullable($value = null): array
    {
        // use NULL
        if ($value === null ) {
            return [null, PDO::PARAM_NULL];
        }

        return $this->bBool($value);
    }

    /**
     * Bind a boolean value as bool.
     *
     * @param int|bool $value
     *
     * @return array
     * @throws TypeError
     */
    public function bBool($value): array
    {
        if ($value === null) {
            throw new TypeError('Can not bind ' . gettype($value) . ':(' . $value .') in boolean spot.');
        }

        return [$value, PDO::PARAM_BOOL];
    }


    /**
     * Bind a boolean value as int or null.
     *
     * @param int|bool|null $value
     *
     * @return array
     * @throws TypeError
     */
    public function bBoolIntNullable($value = null): array
    {
        // use NULL
        if ($value === null) {
            return [null, PDO::PARAM_NULL];
        }

        return $this->bBoolInt($value);
    }

    /**
     * Bind a boolean value as int.
     *
     * @param int|bool $value
     *
     * @return array
     * @throws TypeError
     */
    public function bBoolInt($value): array
    {
        if ($value === null) {
            throw new TypeError('Can not bind ' . gettype($value) . ':(' . $value .') in boolean / integer spot.');
        }

        return [(int) $value, PDO::PARAM_INT];
    }
}
