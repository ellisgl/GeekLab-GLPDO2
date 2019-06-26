<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use \PDO;
use \InvalidArgumentException;
use GeekLab\GLPDO2\Constants;
use GeekLab\GLPDO2\Bindings\DateTimeBindingInterface;

class MySQLDateTimeBindings implements DateTimeBindingInterface, Constants
{
    /**
     * Bind a date value as date or optional NULL.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string|null $value
     * @param bool $null
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function bDate(?string $value, bool $null = false): array
    {
        if ($value === null && !$null) {
            throw new InvalidArgumentException('Can not bind NULL in date spot.');
        }

        if (empty($value) && $null) {
            return [null, PDO::PARAM_NULL];
        }

        if (!empty($value)) {
            $value = trim($value);
            return [preg_match(self::DATE_REGEX, $value) ? $value : '1970-01-01', PDO::PARAM_STR];
        }

        return ['1970-01-01', PDO::PARAM_STR];
    }

    /**
     * Bind a date value as date time or optional NULL.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string|null $value
     * @param bool $null
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function bDateTime($value = null, bool $null = false): array
    {
        if ($value === null && !$null) {
            throw new InvalidArgumentException('Can not bind NULL in date time spot.');
        }

        $isDateTime = 0;

        if ($value !== null) {
            // Trim $value and see if it matches full date time string format.
            $isDateTime = preg_match(self::DATE_TIME_REGEX, trim($value));
        }

        // Use NULL?
        if ($isDateTime === 0 && $null) {
            return [null, PDO::PARAM_NULL];
        }

        if ($isDateTime === 0 && $value !== null) {
            // $value is not a valid date string, set to earliest date time available (GMT).
            // Or $value is a valid date string, add midnight time.
            $value = preg_match(self::DATE_REGEX, $value) === 0 ? '1970-01-01 00:00:00' : $value . ' 00:00:00';
        }

        // DateTimes are really strings.
        return [$value, PDO::PARAM_STR];
    }
}
