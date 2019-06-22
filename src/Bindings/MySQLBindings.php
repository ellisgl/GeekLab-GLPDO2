<?php

namespace GeekLab\GLPDO2\Bindings;

use DomainException;
use Exception;
use GeekLab\GLPDO2\Constants;
use GeekLab\GLPDO2\Statement;
use PDO;

class MySQLBindings implements BindingsInterface, Constants
{
    /**
     * Bind a boolean value as bool, with NULL option or with integer option.
     *
     * @param string $name
     * @param string|int|bool|null $value
     * @param bool $null
     * @param bool $int
     *
     * @return array
     * @throws Exception
     */
    public function bBool($value = null, bool $null = false, bool $int = false): array
    {
        // use NULL
        if ($value === null && $null) {
            return $this->bStr(null, true);
        }

        if ($value === null && $null === false) {
            throw new DomainException('Can not bind NULL in boolean spot.');
        }

        $value = (bool)$value;
        $value = $int ? (int)$value : $value;
        $type = $int ? PDO::PARAM_INT : PDO::PARAM_BOOL;

        return [$value, $type];

    }

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
    public function bDate($value = null, bool $null = false): array
    {
        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in date spot.');
        }

        $d = null;

        if ($value !== null) {
            $value = trim($value);
            $d = preg_match(self::DATE_REGEX, $value);
        }

        // Use NULL?
        if ($d === null && $null) {
            return $this->bStr(null, true);
        }

        return $this->bStr($d ? $value : '1970-01-01');
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
            return $this->bStr(null, true);
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
        return $this->bStr($value);
    }

    /**
     * Bind a float.
     *
     * @param string|int|float|null $value
     * @param int $decimals
     * @param bool $null
     *
     * @return array
     * @throws Exception
     */
    public function bFloat($value = null, $decimals = 3, $null = false): array
    {
        // Use NULL?
        if ($value === null && $null) {
            return $this->bRaw('NULL');
        }

        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in float spot.');
        }

        if (!is_numeric($value)) {
            throw new DomainException('Can not bind "' . $value . '" in float spot.');
        }

        $format = sprintf('%%0.%df', $decimals);

        // Apparently using PDO::PARAM_STR makes this fail!
        return $this->bRaw(sprintf($format, $value));
    }

    /**
     * Bind an integer with optional NULL.
     *
     * @param string|int|float|bool|null $value
     * @param bool $null
     *
     * @return array
     * @throws Exception
     */
    public function bInt($value = null, bool $null = false): array
    {
        // Use NULL?
        if ($value === null && $null) {
            return $this->bStr(null, true);
        }

        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in integer spot.');
        }

        if (!is_numeric($value)) {
            throw new DomainException('Can not bind "' . $value . '" in integer spot.');
        }

        $value = sprintf('%u', $value);

        return [(int)$value, PDO::PARAM_INT];
    }

    /**
     * Convert array of integers to comma separated values. Uses %%
     * Great for IN() statements.
     *
     * @param array $data
     * @param int $default
     *
     * @return array
     * @throws Exception
     */
    public function bIntArray(array $data, int $default = 0): array
    {
        if (empty($data)) {
            throw new DomainException('Can not bind an empty array.');
        }

        // Make unique integer array
        $numbers = array();

        foreach ($data as $value) {
            $numbers[(int)$value] = true;
        }

        $numbers = array_keys($numbers);

        // turn into a string
        $result = implode(', ', $numbers);

        return $this->bRaw($result ?: $default);
    }

    /**
     * Bind a object or JSON string to a string
     *
     * @param string|object|null $value
     * @param bool $null
     *
     * @return array
     * @throws \JsonException
     */
    public function bJSON($value, bool $null = false): array
    {
        // Use NULL?
        if ($value === null && $null) {
            return $this->bStr(null, true);
        }

        if ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in JSON spot.');
        }

        if (is_object($value)) {
            $value = json_encode($value);
        } elseif (is_string($value)) {
            $JSON = json_decode($value, false, 255);

            if (json_last_error()) {
                throw new \JsonException('Can not bind invalid JSON in JSON spot. (' . json_last_error_msg() . ')');
            }

            $value = json_encode($JSON);
        } else {
            throw new \JsonException('Can not bind invalid JSON in JSON spot. (' . $value . ')');
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

        if ($starts && !$ends) {
            // Starts with.
            $value .= '%';
        } elseif (!$starts && $ends) {
            // Ends with.
            $value = '%' . $value;
        } elseif (!$starts && !$ends) {
            // Is somewhere...
            $value = '%' . $value . '%';
        }

        return [$value];
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
        return [$value];
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
        //$name = $this->getNextName();

        if ($value === null && $null) {
            $type = PDO::PARAM_NULL;
        } elseif ($value === null && !$null) {
            throw new DomainException('Can not bind NULL in string spot.');
        }

        return [(string)$value, $type];
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
        //  No array elements?
        $aStr = empty($values) ? $default : '\'' . implode("', '", $values) . '\'';

        return $this->bRaw($aStr);
    }
}