<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use \PDO;
use \DomainException;
use \Exception;
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
     * @throws Exception
     */
    public function bDate(?string $value, bool $null = false): array
    {
        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in date spot.');
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
     * @throws Exception
     */
    public function bDateTime($value = null, bool $null = false): array
    {
        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in date time spot.');
        }

        $dt = 0;

        if ($value !== null) {
            // Trim $value and see if it matches full date time string format.
            $dt = preg_match(self::DATE_TIME_REGEX, trim($value));
        }

        // Use NULL?
        if ($dt === 0 && $null) {
            return [null, PDO::PARAM_NULL];
        }

        if ($dt === 0 && $value !== null) {
            if (preg_match(self::DATE_REGEX, $value) === 0) {
                // $value is not a valid date string, set to earliest date time available (GMT).
                $value = '1970-01-01 00:00:00';
            } else {
                // $value is a valid date string, add midnight time.
                $value .= ' 00:00:00';
            }
        }

        // DateTimes are really strings.
        return [$value, PDO::PARAM_STR];
    }
}
