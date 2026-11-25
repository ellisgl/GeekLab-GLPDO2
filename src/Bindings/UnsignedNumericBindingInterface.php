<?php

namespace GeekLab\GLPDO2\Bindings;

use InvalidArgumentException;

/**
 * Unsigned integer bindings, kept apart from NumericBindingInterface so existing implementations of it don't break.
 * Bindings falls back to MySQLNumericBindings for these when the numeric bindings don't implement this.
 */
interface UnsignedNumericBindingInterface extends NumericBindingInterface
{
    /**
     * Bind an unsigned integer (0 to 18446744073709551615), with NULL option. Uses %%
     *
     * @param float | bool | int | string | null $value
     * @param bool                               $null
     *
     * @return array{string}
     * @throws InvalidArgumentException
     */
    public function bUInt(float | bool | int | string | null $value = null, bool $null = false): array;

    /**
     * Convert an array of unsigned integers to comma separated values. Uses %%
     * Great for IN() statements.
     *
     * @param array{} | array{mixed} $data
     *
     * @return array{string}
     * @throws InvalidArgumentException
     */
    public function bUIntArray(array $data): array;
}
