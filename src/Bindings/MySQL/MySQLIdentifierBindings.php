<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use GeekLab\GLPDO2\Bindings\QuotedIdentifierBindings;

/**
 * Quotes identifiers with backticks: `db`.`users`.`id`
 * If the server runs with ANSI_QUOTES, use QuotedIdentifierBindings for double quotes instead.
 */
class MySQLIdentifierBindings extends QuotedIdentifierBindings
{
    public function __construct()
    {
        parent::__construct('`');
    }
}
