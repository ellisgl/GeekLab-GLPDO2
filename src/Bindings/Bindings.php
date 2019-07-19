<?php

namespace GeekLab\GLPDO2\Bindings;

use \PDO;
use \Exception;
use \TypeError;
use \JsonException;

class Bindings
{
    /** @var DateTimeBindingInterface $dateTime */
    protected $dateTime;

    /** @var LogicBindingInterface $logic */
    protected $logic;

    /** @var NumericBindingInterface $numeric */
    protected $numeric;

    /** @var OtherBindingInterface $other */
    protected $other;

    /** @var StringBindingInterface $string */
    protected $string;

    public function __construct(
        DateTimeBindingInterface $dateTime,
        LogicBindingInterface $logic,
        NumericBindingInterface $numeric,
        OtherBindingInterface $other,
        StringBindingInterface $string
    ) {
        $this->dateTime = $dateTime;
        $this->logic = $logic;
        $this->numeric = $numeric;
        $this->other = $other;
        $this->string = $string;
    }

    /**
     * Bind a boolean value as bool or optional null.
     *
     * @param int|bool|null $value
     * @param array $options ['nullable' => (bool)]
     *
     * @return array
     * @throws TypeError
     */
    public function bBool($value, array $options = []): array
    {
        if (isset($options['nullable']) && $options['nullable'] === \true) {
            return $this->logic->bBoolNullable($value);
        }

        return $this->logic->bBool($value);
    }

    /**
     * Bind a boolean value as int or optional null.
     *
     * @param int|bool|null $value
     * @param array $options ['nullable' => (bool)]
     * @return array
     * @throws TypeError
     */
    public function bBoolInt($value, array $options = []): array
    {
        if (isset($options['nullable']) && $options['nullable'] === \true) {
            return $this->logic->bBoolIntNullable($value);
        }

        return $this->logic->bBoolInt($value);
    }

    /**
     * Bind a date value as date or optional null.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string|null $value
     * @param array $options ['nullable' => (bool)]
     *
     * @return array
     * @throws TypeError
     */
    public function bDate(?string $value, array $options = []): array
    {
        if (isset($options['nullable']) && $options['nullable'] === \true) {
            return $this->dateTime->bDateNullable($value);
        }

        return $this->dateTime->bDate($value);
    }

    /**
     * Bind a date value as date time or optional null.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string|null $value
     * @param array $options ['nullable' => (bool)]
     *
     * @return array
     * @throws TypeError
     */
    public function bDateTime(?string $value, array $options = []): array
    {
        if (isset($options['nullable']) && $options['nullable'] === \true) {
            return $this->dateTime->bDateTimeNullable($value);
        }

        return $this->dateTime->bDateTime($value);
    }

    /**
     * Bind a float value as float or optional null.
     *
     * @param string|int|float|null $value
     * @param array $options ['decimals' => (int), 'nullable' => (bool)]
     *
     * @return array
     * @throws TypeError
     */
    public function bFloat($value, array $options = ['decimals' => 2]): array
    {
        if (!isset($options['decimals'])) {
            $options['decimals'] = 2;
        }

        if (isset($options['nullable']) && $options['nullable'] === \true) {
            return $this->numeric->bFloatNullable($value, $options['decimals']);
        }

        return $this->numeric->bFloat($value, $options['decimals']);
    }

    /**
     * Bind an integer value as int or optional null.
     *
     * @param string|int|float|bool|null $value
     * @param array $options ['nullable' => (bool)]
     *
     * @return array
     * @throws TypeError
     */
    public function bInt($value, array $options = []): array
    {
        if (isset($options['nullable']) && $options['nullable'] === \true) {
            return $this->numeric->bIntNullable($value);
        }

        return $this->numeric->bInt($value);
    }

    /**
     * Convert array of integers to comma separated values. Uses %%
     * Great for IN() statements.
     *
     * @param array $data
     * @param int $default
     *
     * @return array
     * @throws TypeError
     */
    public function bIntArray(array $data, int $default = 0): array
    {
        return $this->numeric->bIntArray($data, $default);
    }

    /**
     * Bind JSON to string or optional null.
     *
     * @param string|object|null $value
     * @param array $options ['nullable' => (bool)]
     *
     * @return array
     * @throws JsonException
     * @throws TypeError
     */
    public function bJson($value, array $options = []): array
    {
        if (isset($options['nullable']) && $options['nullable'] === \true) {
            return $this->string->bJsonNullable($value);
        }

        return $this->string->bJson($value);
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
        return $this->string->bLike($value, $ends, $starts);
    }

    /**
     * !!!DANGER!!!
     * Bind a raw value.
     *
     * @param string|int|float|bool $value
     *
     * @return array
     */
    public function bRaw($value): array
    {
        return $this->other->bRaw($value);
    }

    /**
     * Bind a string value as string or optional null.
     *
     * @param string|int|float|bool|null $value
     * @param array $options ['nullable' => (bool)]
     * @return array
     * @throws Exception
     */
    public function bStr($value, array $options = []): array
    {
        return $this->string->bStr($value);
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
        return $this->string->bStrArr($values, $default);
    }

    /**
     * Bind a string to the PDO data type.
     *
     * @param string|int|float|bool|null $value
     * @param int $type
     *
     * @return array
     */
    public function bValueType($value, int $type = \PDO::PARAM_STR): array
    {
        return $this->other->bValueType($value, $type);
    }
}
