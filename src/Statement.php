<?php

namespace GeekLab\GLPDO2;

use PDO;
use PDOStatement;
use Exception;
use InvalidArgumentException;
use GeekLab\GLPDO2\Bindings\Bindings;

class Statement
{
    private Bindings $bindings;   // Parameter bindings.
    private int $bindPos = 0;     // Position for SQL binds.
    /**
     * @var array{} | array{mixed} $named
     */
    private array $named = [];    // Named binding values.
    /**
     * @var array{} | array{mixed} $SQL
     */
    private array $SQL = [];      // SQL Statement, as sent to PDO.
    /**
     * @var array{} | array{mixed} $shownSQL
     */
    private array $shownSQL = []; // SQL Statement for getComputed(). Differs only where "??" stands for "?".
    private int $sqlPos = 0;      // Position holder for statement processing.
    /**
     * @var array{} | array<int|string, mixed> $rawNamed
     */
    private array $rawNamed = []; // Raw named placeholders.
    private int $rawPos = 0;      // Position for raw binds (bRaw, bFloat, bIntArray, bStrArr).
    private int $rawSqlPos = 0;   // Position holder for '%%' placeholders found by sql().
    private int $arrPos = 0;      // Position holder for array element binds (bStrArr).

    private Dialect $dialect;     // Decides how sql() finds quoted text and comments.

    // Where sql() is in the statement text, so '?' and '%%' inside literals and comments are left alone.
    // Kept across sql() calls, since a quoted string can span several of them.
    private const LEX_CODE = 0;
    private const LEX_QUOTED = 1;        // Inside '...', "..." or `...`; $lexQuote holds which.
    private const LEX_LINE_COMMENT = 2;  // After "--" or "#", until the end of the line.
    private const LEX_BLOCK_COMMENT = 3; // Inside /* ... */; $lexDepth counts nesting (PostgreSQL).
    private const LEX_DOLLAR = 4;        // Inside a PostgreSQL $tag$...$tag$ string; $lexQuote holds the tag.
    private int $lexState = self::LEX_CODE;
    private string $lexQuote = '';
    private bool $lexBackslash = false;  // Whether a backslash escapes the next character in this quote.
    private int $lexDepth = 0;
    // /**
    //  * @var array{} | array{mixed} $rawSql
    //  */
    // private array $rawSql = [];   // SQL Statement.

    public function __construct(Bindings $bindings)
    {
        $this->bindings = $bindings;
        $this->dialect = $bindings->getDialect();
    }

    /**
     * Replace the raw placeholder with raw values.
     *
     * @param string $sql
     *
     * @return string
     */
    private function rawPlaceholderFill(string $sql): string
    {
        foreach ($this->rawNamed as $name => $rVal) {
            // A callback, so '$1' or '\' in the value is inserted as is rather than read as a back reference.
            $sql = (string)preg_replace_callback(
                '/' . preg_quote((string)$name, '/') . '\b/',
                static fn (): string => (string)$rVal,
                $sql
            );
        }

        return $sql;
    }

    /**
     * Bind a value to a named parameter.
     *
     * @param string                             $name
     * @param float | bool | int | string | null $value
     * @param int                                $type
     *
     * @return Statement
     */
    public function bind(string $name, float | bool | int | string | null $value, int $type = PDO::PARAM_STR): self
    {
        $this->named[$name] = array(
            'type' => $type,
            'value' => $value
        );
        return $this;
    }

    /**
     * Bind a raw value to a named parameter.
     *
     * @param string                      $name
     * @param float | bool | int | string $value
     *
     * @return Statement
     */
    public function rawBind(string $name, float | bool | int | string $value): self
    {
        $this->rawNamed[$name] = $value;
        return $this;
    }

    // Bind types

    /**
     * Bind a boolean value as bool, with NULL option.
     *
     * @param bool | int | null $value
     * @param bool              $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bBool(bool | int | null $value = null, bool $null = false): self
    {
        $binding = $this->bindings->bBool($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

        return $this;
    }

    /**
     * Bind a boolean value as int, with NULL option.
     *
     * @param bool | int | null $value
     * @param bool              $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bBoolInt(bool | int | null $value = null, bool $null = false): self
    {
        $binding = $this->bindings->bBoolInt($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

        return $this;
    }

    /**
     * Bind a date value as date or optional NULL.
     * YYYY-MM-DD is the proper date format.
     *
     * @param string | null $value
     * @param bool          $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bDate(?string $value = null, bool $null = false): self
    {
        $binding = $this->bindings->bDate($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

        return $this;
    }

    /**
     * Bind a date value as date time or optional NULL.
     * YYYY-MM-DD HH:MM:SS is the proper date format.
     *
     * @param string | null $value
     * @param bool          $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bDateTime(?string $value = null, bool $null = false): self
    {
        $binding = $this->bindings->bDateTime($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

        return $this;
    }

    /**
     * Bind a float.
     *
     * @param float | int | string | null $value
     * @param int                         $decimals
     * @param bool                        $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bFloat(float | int | string | null $value = null, int $decimals = 3, bool $null = false): self
    {
        $binding = $this->bindings->bFloat($value, $decimals, $null);
        $this->rawBind($this->getNextName('raw'), $binding[0]);

        return $this;
    }


    /**
     * Bind an integer with optional NULL.
     *
     * @param float | bool | int | string | null $value
     * @param bool                               $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bInt(float | bool | int | string | null $value = null, bool $null = false): self
    {
        $binding = $this->bindings->bInt($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

        return $this;
    }

    /**
     * Convert array of integers to comma separated values. Uses %%
     * Great for IN() statements.
     *
     * @param array{} | array{mixed} $data
     *
     * @return Statement
     * @throws Exception
     */
    public function bIntArray(array $data): self
    {
        $binding = $this->bindings->bIntArray($data);
        $this->rawBind($this->getNextName('raw'), $binding[0]);

        return $this;
    }

    /**
     * Quote an identifier (database, table, column), or a list of them. Uses %%
     * Quoting follows the bindings: backticks for MySQL, double quotes for PostgreSQL.
     * 'db.users.id' quotes each part, 'users.*' leaves the '*' alone,
     * and an array becomes a comma separated list, e.g. for INSERT column lists.
     * Quoting makes any name safe to put in the SQL, but still don't let users pick
     * arbitrary tables or columns: check names against a list you allow.
     *
     * @param string | array<mixed> $name
     *
     * @return Statement
     * @throws InvalidArgumentException
     */
    public function bIdent(string | array $name): self
    {
        $binding = $this->bindings->bIdent($name);
        $this->rawBind($this->getNextName('raw'), $binding[0]);

        return $this;
    }

    /**
     * Bind an unsigned integer (0 to 18446744073709551615), with NULL option. Uses %%
     * Values above PHP_INT_MAX can be given as integer strings. Floats are truncated toward zero.
     * Negative values, non-numeric strings and values above the maximum throw InvalidArgumentException.
     *
     * @param float | bool | int | string | null $value
     * @param bool                               $null
     *
     * @return Statement
     * @throws InvalidArgumentException
     */
    public function bUInt(float | bool | int | string | null $value = null, bool $null = false): self
    {
        $binding = $this->bindings->bUInt($value, $null);
        $this->rawBind($this->getNextName('raw'), $binding[0]);

        return $this;
    }

    /**
     * Convert an array of unsigned integers to comma separated values. Uses %%
     * Great for IN() statements. Every value is validated as bUInt() does, and duplicates are removed.
     *
     * @param array{} | array{mixed} $data
     *
     * @return Statement
     * @throws InvalidArgumentException
     */
    public function bUIntArray(array $data): self
    {
        $binding = $this->bindings->bUIntArray($data);
        $this->rawBind($this->getNextName('raw'), $binding[0]);

        return $this;
    }

    /**
     * Bind an object or JSON string to a string
     *
     * @param mixed $value
     * @param bool  $null
     *
     * @return Statement
     * @throws Exception
     */
    public function bJSON(mixed $value, bool $null = false): self
    {
        $binding = $this->bindings->bJSON($value, $null);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

        return $this;
    }

    /**
     * Create and bind string for LIKE() statements.
     *
     * @param string $value
     * @param bool   $ends   Ends with?
     * @param bool   $starts Starts with?
     *
     * @return Statement
     */
    public function bLike(string $value, bool $ends = false, bool $starts = false): self
    {
        $binding = $this->bindings->bLike($value, $ends, $starts);
        $this->bind($this->getNextName(), $binding[0]);

        return $this;
    }

    /**
     * Bind an array of strings for IN() statements. Uses %%
     * Each value is bound as its own parameter, so values are never pasted into the SQL.
     * If $values is empty, $default is inserted raw (unquoted and unescaped) instead.
     *
     * @param array{} | array{mixed}      $values
     * @param float | bool | int | string $default
     *
     * @return Statement
     * @throws InvalidArgumentException
     */
    public function bStrArr(array $values, float | bool | int | string $default = ''): self
    {
        if (empty($values)) {
            $this->rawBind($this->getNextName('raw'), $default);

            return $this;
        }

        $names = [];

        foreach ($values as $value) {
            if ($value !== null && !is_scalar($value)) {
                throw new InvalidArgumentException('Can not bind a non-scalar value in a string array.');
            }

            // NULL becomes an empty string, as it did when the values were imploded.
            $binding = $this->bindings->bStr($value ?? '');
            $name = $this->getNextName('arr');
            $this->bind($name, $binding[0], $binding[1]);
            $names[] = $name;
        }

        // The %% placeholder becomes a list of named placeholders, e.g. ":arr0, :arr1".
        $this->rawBind($this->getNextName('raw'), implode(', ', $names));

        return $this;
    }

    /**
     * !!!DANGER!!!
     * Bind a raw value.
     *
     * @param float | bool | int | string $value
     *
     * @return Statement
     */
    public function bRaw(float | bool | int | string $value): self
    {
        $binding = $this->bindings->bRaw($value);
        $this->rawBind($this->getNextName('raw'), $binding[0]);

        return $this;
    }

    /**
     * Bind a string value.
     *
     * @param float | bool | int | string | null $value
     * @param bool                               $null
     * @param int                                $type
     *
     * @return Statement
     * @throws Exception
     */
    public function bStr(
        float | bool | int | string | null $value,
        bool $null = false,
        int $type = PDO::PARAM_STR
    ): self {
        $binding = $this->bindings->bStr($value, $null, $type);
        $this->bind($this->getNextName(), $binding[0], $binding[1]);

        return $this;
    }

    // The rest of the helpers

    /**
     * Name the positions for binding in PDO.
     *
     * @param string $type
     *
     * @return string
     */
    private function getNextName(string $type = 'bind'): string
    {
        return match ($type) {
            'sql' => sprintf(':pos%d', $this->sqlPos++),
            // Each '%%' gets its own number, matched in order to the raw binds, as '?' is to binds.
            'rawSql' => sprintf(':raw%d', $this->rawSqlPos++),
            'raw' => sprintf(':raw%d', $this->rawPos++),
            'arr' => sprintf(':arr%d', $this->arrPos++),
            default => sprintf(':pos%d', $this->bindPos++),
        };
    }

    /**
     * Prepare and Execute the SQL statement.
     *
     * @param PDO $PDO
     *
     * @return PDOStatement
     * @throws Exception
     */
    public function execute(PDO $PDO): PDOStatement
    {
        // Join the sql() calls one per line, as getComputed() does, so a "-- comment" in one call
        // doesn't swallow the calls after it. Then replace raw placements with raw values.
        $sql = $this->rawPlaceholderFill(implode("\n", $this->SQL));

        /** @var PDOStatement $stmt */
        $stmt = $PDO->prepare($sql);

        // Bind named parameters.
        foreach ($this->named as $name => $sVal) {
            switch ($sVal['type']) {
                case PDO::PARAM_BOOL:
                    $stmt->bindValue($name, (bool)$sVal['value'], $sVal['type']);
                    break;

                case PDO::PARAM_NULL:
                    $stmt->bindValue($name, null);
                    break;

                case PDO::PARAM_INT:
                    $stmt->bindValue($name, (int)$sVal['value'], $sVal['type']);
                    break;

                case PDO::PARAM_STR:
                default:
                    $stmt->bindValue($name, (string)$sVal['value'], $sVal['type']);
                    break;
            }
        }

        $stmt->execute();

        return $stmt;
    }

    /**
     * Use for building out what a might look like when it's pass to the DB.
     * Used by Statement::getComputed()
     *
     * @param array{} | array{int|string} $matches
     *
     * @return mixed
     * @throws Exception
     */
    private function placeholderFill(array $matches): mixed
    {
        if (empty($matches)) {
            return null;
        }

        $key = $matches[0];

        // Can't fill this param.
        if (!isset($this->named[$key]) && !isset($this->rawNamed[$key])) {
            return $key;
        }

        if (isset($this->named[$key])) {
            // here is the param
            $sVal = $this->named[$key];

            return match ($sVal['type']) {
                PDO::PARAM_BOOL => $sVal['value'] ? 'TRUE' : 'FALSE',
                PDO::PARAM_NULL => 'NULL',
                PDO::PARAM_INT => (int)$sVal['value'],
                default => "'" . $sVal['value'] . "'",
            };
        }

        // Since it's not named, it must be raw.
        return $this->rawNamed[$key];
    }

    /**
     * Get name of the placeholder.
     *
     * @return string
     */
    private function placeholderGetName(): string
    {
        return $this->getNextName('sql');
    }

    /**
     * Builds up the SQL parameterized statement.
     * '?' becomes a named placeholder and '%%' a raw placeholder, except inside
     * string literals and comments, which are found by the bindings' dialect:
     *
     * - MySQL: '...' and "..." strings, "-- " (whitespace after it), "#" and /* * / comments.
     * - PostgreSQL: '...', E'...' and $tag$...$tag$ strings, "--" and nesting /* * / comments.
     *   "??" is a literal "?", for the jsonb ?, ?| and ?& operators.
     *
     * Inside identifier quotes (`...` for MySQL, "..." for PostgreSQL) only '%%' is a placeholder,
     * so `%%` with bRaw() can name a table.
     *
     * @param string $text
     *
     * @return Statement
     */
    public function sql(string $text): self
    {
        $out = '';   // For PDO.
        $shown = ''; // For getComputed().
        $len = strlen($text);
        $pg = $this->dialect === Dialect::PostgreSQL;
        $identQuote = $pg ? '"' : '`';

        for ($i = 0; $i < $len; $i++) {
            $char = $text[$i];
            $next = $i + 1 < $len ? $text[$i + 1] : '';
            $emit = $char;     // What this step adds to $out.
            $emitShown = null; // What it adds to $shown, when that differs.

            switch ($this->lexState) {
                case self::LEX_QUOTED:
                    if ($this->lexQuote === $identQuote && $char === '%' && $next === '%') {
                        // `%%` with bRaw() fills in an identifier, such as a table name.
                        $emit = $this->getNextName('rawSql');
                        $i++;
                    } elseif ($char === '\\' && $this->lexBackslash) {
                        // Backslash escape: copy it and the escaped character as is.
                        $emit = $char . $next;
                        $i++;
                    } elseif ($char === $this->lexQuote) {
                        // A doubled quote ('') closes and immediately reopens, which works out the same.
                        $this->lexState = self::LEX_CODE;
                    }
                    break;

                case self::LEX_DOLLAR:
                    if ($char === '$' && substr($text, $i, strlen($this->lexQuote)) === $this->lexQuote) {
                        $emit = $this->lexQuote;
                        $i += strlen($this->lexQuote) - 1;
                        $this->lexState = self::LEX_CODE;
                    }
                    break;

                case self::LEX_LINE_COMMENT:
                    if ($char === "\n") {
                        $this->lexState = self::LEX_CODE;
                    }
                    break;

                case self::LEX_BLOCK_COMMENT:
                    if ($char === '*' && $next === '/') {
                        $emit = '*/';
                        $i++;

                        if (--$this->lexDepth === 0) {
                            $this->lexState = self::LEX_CODE;
                        }
                    } elseif ($pg && $char === '/' && $next === '*') {
                        // PostgreSQL block comments nest.
                        $emit = '/*';
                        $i++;
                        $this->lexDepth++;
                    }
                    break;

                default:
                    $tag = $pg && $char === '$' ? $this->dollarTag($text, $i) : null;

                    if ($char === '?' && $pg && $next === '?') {
                        // "??" is PDO's escape for a literal "?" (PHP 7.4+), e.g. the jsonb "?" operator.
                        $emit = '??';
                        $emitShown = '?';
                        $i++;
                    } elseif ($char === '?') {
                        $emit = $this->placeholderGetName();
                    } elseif ($char === '%' && $next === '%') {
                        $emit = $this->getNextName('rawSql');
                        $i++;
                    } elseif ($char === "'") {
                        // MySQL always reads backslash escapes in strings. PostgreSQL only in E'...'.
                        $this->startQuote($char, !$pg || $this->isEscapeStringPrefix($text, $i));
                    } elseif ($char === '"') {
                        // A string in MySQL, an identifier in PostgreSQL.
                        $this->startQuote($char, !$pg);
                    } elseif ($char === '`' && !$pg) {
                        $this->startQuote($char, false);
                    } elseif ($tag !== null) {
                        $emit = $tag;
                        $i += strlen($tag) - 1;
                        $this->lexState = self::LEX_DOLLAR;
                        $this->lexQuote = $tag;
                    } elseif (
                        ($char === '#' && !$pg)
                        || ($char === '-' && $next === '-' && ($pg || $this->isCommentGap($text, $i + 2)))
                    ) {
                        $this->lexState = self::LEX_LINE_COMMENT;
                    } elseif ($char === '/' && $next === '*') {
                        $emit = '/*';
                        $i++;
                        $this->lexState = self::LEX_BLOCK_COMMENT;
                        $this->lexDepth = 1;
                    }
            }

            $out .= $emit;
            $shown .= $emitShown ?? $emit;
        }

        // Each sql() call is its own line in getComputed(), so a line comment ends with it.
        if ($this->lexState === self::LEX_LINE_COMMENT) {
            $this->lexState = self::LEX_CODE;
        }

        $this->SQL[] = $out;
        $this->shownSQL[] = $shown;

        return $this;
    }

    /**
     * Enter a quoted string or identifier.
     *
     * @param string $quote
     * @param bool   $backslash Whether a backslash escapes the next character inside it.
     *
     * @return void
     */
    private function startQuote(string $quote, bool $backslash): void
    {
        $this->lexState = self::LEX_QUOTED;
        $this->lexQuote = $quote;
        $this->lexBackslash = $backslash;
    }

    /**
     * MySQL only treats "--" as a comment when whitespace (or the end of the text) follows it.
     *
     * @param string $text
     * @param int    $pos position just after "--"
     *
     * @return bool
     */
    private function isCommentGap(string $text, int $pos): bool
    {
        return $pos >= strlen($text) || in_array($text[$pos], [' ', "\t", "\r", "\n"], true);
    }

    /**
     * Whether the quote at $pos opens a PostgreSQL E'...' string, where backslash escapes work.
     *
     * @param string $text
     * @param int    $pos position of the opening quote
     *
     * @return bool
     */
    private function isEscapeStringPrefix(string $text, int $pos): bool
    {
        return $pos > 0
            && ($text[$pos - 1] === 'E' || $text[$pos - 1] === 'e')
            && ($pos === 1 || !$this->isIdentifierChar($text[$pos - 2]));
    }

    /**
     * The PostgreSQL dollar quote tag ($$ or $tag$) starting at $pos, or null if there isn't one.
     * A "$" inside a name (foo$bar) or a parameter ($1) doesn't start one.
     *
     * @param string $text
     * @param int    $pos position of the "$"
     *
     * @return string | null
     */
    private function dollarTag(string $text, int $pos): ?string
    {
        if ($pos > 0 && $this->isIdentifierChar($text[$pos - 1])) {
            return null;
        }

        if (preg_match('/\G\$(?:[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)?\$/', $text, $match, 0, $pos) !== 1) {
            return null;
        }

        return $match[0];
    }

    /**
     * Whether a character can be part of an unquoted name.
     *
     * @param string $char
     *
     * @return bool
     */
    private function isIdentifierChar(string $char): bool
    {
        return $char === '_' || $char === '$' || ctype_alnum($char) || ord($char) >= 0x80;
    }

    /**
     * Reset / Clear out properties.
     *
     * @return Statement
     */
    public function reset(): self
    {
        $this->bindPos = 0;
        $this->named = [];
        $this->SQL = [];
        $this->shownSQL = [];
        $this->sqlPos = 0;
        $this->rawNamed = [];
        $this->rawPos = 0;
        $this->rawSqlPos = 0;
        $this->arrPos = 0;
        $this->lexState = self::LEX_CODE;
        $this->lexQuote = '';
        $this->lexBackslash = false;
        $this->lexDepth = 0;
        // $this->rawSql = [];

        return $this;
    }

    /**
     * Create what the SQL query string might look like.
     * Great for debugging. YMMV though.
     *
     * @return string
     * @throws Exception
     */
    public function getComputed(): string
    {
        // Merge SQL together, then fill raw placeholders first, as execute() does.
        // Raw values can contain named placeholders (bStrArr), which are filled below.
        $sql = $this->rawPlaceholderFill(implode("\n", $this->shownSQL));

        // Replace positioned placeholders with named placeholders (first value).
        // Force to string, in the case of null.
        return (string)preg_replace_callback(
            '/:[a-z0-9_]+/m',
            function ($matches) {
                return $this->placeholderFill($matches);
            },
            $sql
        );
    }

    /**
     * Return the SQL as a string.
     *
     * @return string
     * @throws Exception
     */
    public function __toString(): string
    {
        return $this->getComputed();
    }

    /**
     * Magic Method for debugging.
     *
     * @return array{'Named Positions': array{} | array{mixed}, 'Unbound SQL': array{} | array{mixed}, 'Bound SQL': string}
     * @throws Exception
     */
    public function __debugInfo(): array
    {
        return [
            'Named Positions' => $this->named,
            'Unbound SQL' => $this->SQL,
            'Bound SQL' => $this->getComputed()
        ];
    }
}
