<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use PDO;
use InvalidArgumentException;
use GeekLab\GLPDO2\Bindings\LogicBindingInterface;

class MySQLLogicBindings implements LogicBindingInterface
{
    /**
     * Bind a boolean value as bool, with NULL option.
     *
     * @param bool | int | null $value
     * @param bool              $null
     *
     * @return array{?bool, int}
     * @throws InvalidArgumentException
     */
    public function bBool(bool | int | null $value = null, bool $null = false): array
    {
        // use NULL
        if ($value === null && $null) {
            return [null, PDO::PARAM_NULL];
        }

        if ($value === null && $null === false) {
            throw new InvalidArgumentException('Can not bind NULL in boolean spot.');
        }

        return [(bool)$value, PDO::PARAM_BOOL];
    }

    /**
     * Bind a boolean value as int, with NULL option.
     *
     * @param bool | int | null $value
     * @param bool              $null
     *
     * @return array{?int, int}
     * @throws InvalidArgumentException
     */
    public function bBoolInt(bool | int | null $value = null, bool $null = false): array
    {
        // use NULL
        if ($value === null && $null) {
            return [null, PDO::PARAM_NULL];
        }

        if ($value === null && $null === false) {
            throw new InvalidArgumentException('Can not bind NULL in boolean spot.');
        }

        return [(int)$value, PDO::PARAM_INT];
    }
}
