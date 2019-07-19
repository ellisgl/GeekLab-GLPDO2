<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use \PDO;
use \InvalidArgumentException;
use GeekLab\GLPDO2\Constants;
use GeekLab\GLPDO2\Bindings\DateTimeBindingInterface;
use TypeError;

class MySQLDateTimeBindings implements DateTimeBindingInterface, Constants
{
    /**
     * Bind a date value as date or null.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string|null $value
     *
     * @return array
     */
    public function bDateNullable(?string $value = null): array
    {
        if ($value === null) {
            return [null, PDO::PARAM_NULL];
        }

        return $this->bDate($value);
    }

    /**
     * Bind a date value as date.
     * YYYY-MM-DD is the proper date format.
     *
     * @todo Use PHP's date stuff for validation?
     *
     * @param string|null $value
     *
     * @return array
     */
    public function bDate(?string $value): array
    {
        if ($value === null) {
            throw new TypeError('Can not bind NULL in date spot.');
        }

        $value = trim($value);
        return [preg_match(self::DATE_REGEX, $value) ? $value : '1970-01-01', PDO::PARAM_STR];
    }

    /**
     * Bind a date value as date time or null.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string|null $value
     *
     * @return array
     */
    public function bDateTimeNullable(?string $value = null): array
    {
        if ($value === null) {
            return [null, PDO::PARAM_NULL];
        }

        return $this->bDateTime($value);
    }

    /**
     * Bind a date value as date time.
     * YYYY-MM-DD HH:MM:SS is the proper date time format.
     *
     * @todo Use PHP's date stuff for validation?
     *
     * @param string|null $value
     *
     * @return array
     */
    public function bDateTime(?string $value): array
    {
        if ($value === null) {
            throw new TypeError('Can not bind NULL in date time spot.');
        }

        $value = trim($value);
        $isDateTime = preg_match(self::DATE_TIME_REGEX, trim($value));


        if ($isDateTime === 0) {
            // $value is not a valid date string, set to earliest date time available (GMT).
            // Or $value is a valid date string, add midnight time.
            $value = preg_match(self::DATE_REGEX, $value) === 0 ? '1970-01-01 00:00:00' : $value . ' 00:00:00';
        }

        // DateTimes are really strings.
        return [$value, PDO::PARAM_STR];
    }
}
