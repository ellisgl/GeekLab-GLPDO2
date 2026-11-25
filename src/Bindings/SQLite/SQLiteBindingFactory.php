<?php

namespace GeekLab\GLPDO2\Bindings\SQLite;

use GeekLab\GLPDO2\Bindings\Bindings;
use GeekLab\GLPDO2\Bindings\MySQL\MySQLDateTimeBindings;
use GeekLab\GLPDO2\Bindings\MySQL\MySQLLogicBindings;
use GeekLab\GLPDO2\Bindings\MySQL\MySQLRawBindings;
use GeekLab\GLPDO2\Bindings\MySQL\MySQLStringBindings;
use GeekLab\GLPDO2\Dialect;

class SQLiteBindingFactory
{
    /**
     * The value bindings are plain PDO types that SQLite accepts as well, so the MySQL classes are reused,
     * except that bUInt() stops at SQLite's integer maximum.
     * What differs is identifier quoting and how Statement::sql() reads the SQL text.
     */
    public static function build(): Bindings
    {
        return new Bindings(
            new MySQLDateTimeBindings(),
            new MySQLLogicBindings(),
            new SQLiteNumericBindings(),
            new MySQLRawBindings(),
            new MySQLStringBindings(),
            new SQLiteIdentifierBindings(),
            Dialect::SQLite
        );
    }
}
