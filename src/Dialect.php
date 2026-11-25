<?php

namespace GeekLab\GLPDO2;

use GeekLab\GLPDO2\Bindings\IdentifierBindingInterface;
use GeekLab\GLPDO2\Bindings\MySQL\MySQLIdentifierBindings;
use GeekLab\GLPDO2\Bindings\PostgreSQL\PostgreSQLIdentifierBindings;
use GeekLab\GLPDO2\Bindings\SQLite\SQLiteIdentifierBindings;

/**
 * The SQL dialect a statement is written in.
 * It decides how Statement::sql() finds quoted text and comments, and how bIdent() quotes names by default.
 */
enum Dialect
{
    /**
     * MySQL and MariaDB: '...' and "..." strings with backslash escapes, `...` identifiers,
     * "-- " (with whitespace after it), "#" and non-nesting block comments.
     */
    case MySQL;

    /**
     * PostgreSQL: '...' strings (backslash escapes only in E'...'), "..." identifiers, $tag$...$tag$ strings,
     * "--" and nesting block comments. "??" is a literal "?", for the jsonb ?, ?| and ?& operators.
     */
    case PostgreSQL;

    /**
     * SQLite 3: '...' strings without backslash escapes, "...", `...` and [...] identifiers,
     * "--" and non-nesting block comments.
     */
    case SQLite;

    /**
     * The identifier quoting this dialect uses.
     *
     * @return IdentifierBindingInterface
     */
    public function identifierBindings(): IdentifierBindingInterface
    {
        return match ($this) {
            self::MySQL => new MySQLIdentifierBindings(),
            self::PostgreSQL => new PostgreSQLIdentifierBindings(),
            self::SQLite => new SQLiteIdentifierBindings(),
        };
    }
}
