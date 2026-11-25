<?php

namespace GeekLab\GLPDO2\Bindings\PostgreSQL;

use GeekLab\GLPDO2\Bindings\QuotedIdentifierBindings;

/**
 * Quotes identifiers with double quotes: "db"."users"."id"
 * Quoted names are case sensitive in PostgreSQL, so "Users" and "users" are different tables.
 */
class PostgreSQLIdentifierBindings extends QuotedIdentifierBindings
{
    public function __construct()
    {
        parent::__construct('"');
    }
}
