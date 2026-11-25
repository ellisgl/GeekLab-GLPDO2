<?php

namespace GeekLab\GLPDO2;

use GeekLab\GLPDO2\Bindings\MySQL\MySQLNumericBindings;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the integer bindings. No database needed.
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

    #[DataProvider('intProvider')]
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

    #[DataProvider('outOfRangeProvider')]
    public function testBIntRejectsOutOfRange(float | string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MySQLNumericBindings())->bInt($value);
    }

    /**
     * @return array<string, array{float | bool | int | string, string}>
     */
    public static function uintProvider(): array
    {
        return [
            'zero'                     => [0, '0'],
            'positive int'             => [42, '42'],
            'true'                     => [true, '1'],
            'false'                    => [false, '0'],
            'numeric string'           => ['123', '123'],
            'leading zeros'            => ['007', '7'],
            'all zeros'                => ['000', '0'],
            'plus sign and spaces'     => [' +5 ', '5'],
            'float truncates'          => [1.9, '1'],
            'float string truncates'   => ['2.5', '2'],
            'exponent string'          => ['1e3', '1000'],
            'negative zero string'     => ['-0', '0'],
            'PHP_INT_MAX'              => [PHP_INT_MAX, '9223372036854775807'],
            'above PHP_INT_MAX string' => ['9223372036854775808', '9223372036854775808'],
            'UINT64 max string'        => ['18446744073709551615', '18446744073709551615'],
        ];
    }

    #[DataProvider('uintProvider')]
    public function testBUIntBindsUnsignedIntegers(float | bool | int | string $value, string $expected): void
    {
        $this->assertSame([$expected], (new MySQLNumericBindings())->bUInt($value));
    }

    public function testBUIntBindsNullWhenAllowed(): void
    {
        $this->assertSame(['NULL'], (new MySQLNumericBindings())->bUInt(null, true));
    }

    /**
     * @return array<string, array{float | string | null}>
     */
    public static function badUIntProvider(): array
    {
        return [
            'null not allowed'         => [null],
            'negative int'             => [-1],
            'negative string'          => ['-5'],
            'negative float'           => [-0.5],
            'not numeric'              => ['abc'],
            'SQL'                      => ['1; DROP TABLE users'],
            'hex string'               => ['0x10'],
            'above UINT64 max'         => ['18446744073709551616'],
            'too many digits'          => ['123456789012345678901'],
            'float at 2^63'            => [9223372036854775808.0],
            'huge exponent string'     => ['1e20'],
            'INF'                      => [INF],
            'NAN'                      => [NAN],
        ];
    }

    #[DataProvider('badUIntProvider')]
    public function testBUIntRejectsInvalidValues(float | string | null $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MySQLNumericBindings())->bUInt($value);
    }

    public function testBUIntArrayValidatesAndRemovesDuplicates(): void
    {
        $this->assertSame(
            ['1, 2, 18446744073709551615'],
            (new MySQLNumericBindings())->bUIntArray([1, '2', '01', 2.7, '18446744073709551615'])
        );
    }

    /**
     * @return array<string, array{array<mixed>}>
     */
    public static function badUIntArrayProvider(): array
    {
        return [
            'empty'        => [[]],
            'negative'     => [[1, -2]],
            'not numeric'  => [[1, 'x']],
            'null'         => [[1, null]],
            'nested array' => [[1, [2]]],
            'too large'    => [['18446744073709551616']],
        ];
    }

    /**
     * @param array<mixed> $values
     */
    #[DataProvider('badUIntArrayProvider')]
    public function testBUIntArrayRejectsInvalidValues(array $values): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MySQLNumericBindings())->bUIntArray($values);
    }

    public function testStatementPutsUnsignedValuesInTheSql(): void
    {
        $statement = (new Statement(Bindings\MySQL\MySQLBindingFactory::build()))
            ->sql('SELECT * FROM t WHERE id = %% AND n IN (%%) AND m = %%')
            ->bUInt('18446744073709551615')->bUIntArray([3, 4])->bUInt(null, true);

        $this->assertSame(
            'SELECT * FROM t WHERE id = 18446744073709551615 AND n IN (3, 4) AND m = NULL',
            $statement->getComputed()
        );
    }

    public function testCustomNumericBindingsFallBackToMySQLForUnsigned(): void
    {
        // A NumericBindingInterface written before the unsigned methods existed.
        $numeric = new class implements Bindings\NumericBindingInterface {
            public function bFloat(float | int | string | null $value = null, int $decimals = 3, bool $null = false): array
            {
                return ['0'];
            }

            public function bInt(float | bool | int | string | null $value = null, bool $null = false): array
            {
                return [0, PDO::PARAM_INT];
            }

            public function bIntArray(array $data): array
            {
                return ['0'];
            }
        };

        $bindings = new Bindings\Bindings(
            new Bindings\MySQL\MySQLDateTimeBindings(),
            new Bindings\MySQL\MySQLLogicBindings(),
            $numeric,
            new Bindings\MySQL\MySQLRawBindings(),
            new Bindings\MySQL\MySQLStringBindings()
        );

        $this->assertSame(['5'], $bindings->bUInt(5));
        $this->assertSame(['5, 6'], $bindings->bUIntArray([5, 6]));
    }
}
