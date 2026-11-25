<?php

namespace GeekLab\GLPDO2\Bindings\PostgreSQL;

use GeekLab\GLPDO2\Bindings\Bindings;
use GeekLab\GLPDO2\Bindings\MySQL\MySQLDateTimeBindings;
use GeekLab\GLPDO2\Bindings\MySQL\MySQLLogicBindings;
use GeekLab\GLPDO2\Bindings\MySQL\MySQLNumericBindings;
use GeekLab\GLPDO2\Bindings\MySQL\MySQLRawBindings;
use GeekLab\GLPDO2\Bindings\MySQL\MySQLStringBindings;
use GeekLab\GLPDO2\Dialect;

class PostgreSQLBindingFactory
{
    /**
     * The value bindings are plain PDO types that PostgreSQL accepts as well, so the MySQL classes are reused.
     * What differs is identifier quoting and how Statement::sql() reads the SQL text.
     */
    public static function build(): Bindings
    {
        return new Bindings(
            new MySQLDateTimeBindings(),
            new MySQLLogicBindings(),
            new MySQLNumericBindings(),
            new MySQLRawBindings(),
            new MySQLStringBindings(),
            new PostgreSQLIdentifierBindings(),
            Dialect::PostgreSQL
        );
    }
}
