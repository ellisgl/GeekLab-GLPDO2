<?php

namespace GeekLab\GLPDO2;

use GeekLab\GLPDO2\Bindings\MySQL\MySQLNumericBindings;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the integer binding. No database needed.
 */
class MySQLNumericBindingsTest extends TestCase
{
    /**
     * @return array<string, array{float | int | string, int}>
     */
    public static function intProvider(): array
    {
        return [
            'positive int'           => [42, 42],
            'zero'                   => [0, 0],
            'negative int'           => [-5, -5],
            'negative numeric string' => ['-5', -5],
            'numeric string'         => ['123', 123],
            'float truncates'        => [1.9, 1],
            'negative float truncates toward zero' => [-1.9, -1],
            'float string truncates' => ['2.5', 2],
            'exponent string'        => ['1e3', 1000],
            'PHP_INT_MAX'            => [PHP_INT_MAX, PHP_INT_MAX],
            'PHP_INT_MIN'            => [PHP_INT_MIN, PHP_INT_MIN],
            'PHP_INT_MIN as string'  => ['-9223372036854775808', PHP_INT_MIN],
        ];
    }

    /**
     * @dataProvider intProvider
     */
    public function testBIntBindsIntegers(float | int | string $value, int $expected): void
    {
        $this->assertSame([$expected, PDO::PARAM_INT], (new MySQLNumericBindings())->bInt($value));
    }

    /**
     * @return array<string, array{float | string}>
     */
    public static function outOfRangeProvider(): array
    {
        return [
            'string above PHP_INT_MAX' => ['9223372036854775808'],
            'string below PHP_INT_MIN' => ['-9223372036854775809'],
            'huge float'               => [1e20],
            'huge negative float'      => [-1e20],
            'INF'                      => [INF],
            'NAN'                      => [NAN],
        ];
    }

    /**
     * @dataProvider outOfRangeProvider
     */
    public function testBIntRejectsOutOfRange(float | string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MySQLNumericBindings())->bInt($value);
    }
}
