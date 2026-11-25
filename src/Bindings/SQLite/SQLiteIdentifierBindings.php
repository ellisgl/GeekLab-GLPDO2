<?php

namespace GeekLab\GLPDO2\Bindings\SQLite;

use GeekLab\GLPDO2\Bindings\QuotedIdentifierBindings;

/**
 * Quotes identifiers with double quotes, the SQL standard form SQLite prefers: "main"."users"."id"
 */
class SQLiteIdentifierBindings extends QuotedIdentifierBindings
{
    public function __construct()
    {
        parent::__construct('"');
    }
}
