<?php

namespace GeekLab\GLPDO2\Bindings\SQLite;

use GeekLab\GLPDO2\Bindings\MySQL\MySQLNumericBindings;

/**
 * SQLite integers are signed 64-bit. SQLite reads a larger number as a float, so 2^64 - 1 and 2^64 - 2
 * would be stored as the same value. bUInt() and bUIntArray() throw above 2^63 - 1 instead.
 * Store larger values as text, with bStr().
 */
class SQLiteNumericBindings extends MySQLNumericBindings
{
    /** @const string UINT_MAX SQLite's largest integer, 2^63 - 1. */
    protected const UINT_MAX = '9223372036854775807';
}
