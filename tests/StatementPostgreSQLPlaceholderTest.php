<?php

namespace GeekLab\GLPDO2;

use GeekLab\GLPDO2\Bindings\PostgreSQL\PostgreSQLBindingFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * With the PostgreSQL dialect, sql() finds strings and comments by PostgreSQL's rules.
 * These tests only build statements, so no database is needed.
 */
class StatementPostgreSQLPlaceholderTest extends TestCase
{
    private function statement(): Statement
    {
        return new Statement(PostgreSQLBindingFactory::build());
    }

    /**
     * The PDO-bound SQL, as sent to prepare().
     */
    private function unbound(Statement $statement): string
    {
        return implode("\n", $statement->__debugInfo()['Unbound SQL']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function untouchedProvider(): array
    {
        return [
            'single-quoted string'      => ["SELECT 'Why?'"],
            'quoted identifier'         => ['SELECT "odd?name" FROM t'],
            'doubled quote escape'      => ["SELECT 'It''s ok?'"],
            'escape string'             => ["SELECT E'a\\'b?'"],
            'lowercase escape string'   => ["SELECT e'a\\'b?'"],
            'dollar string'             => ['SELECT $$Why? %%$$'],
            'tagged dollar string'      => ["SELECT \$fn\$ it's ? \$\$ %% \$fn\$"],
            'dash dash without space'   => ['SELECT 1 --why? %%'],
            'block comment'             => ['SELECT 1 /* why? %% */'],
            'nested block comment'      => ['SELECT 1 /* a /* nested? */ still? %% */'],
            'percent pair in string'    => ["SELECT * FROM t WHERE a LIKE '%%'"],
            'comment marker in string'  => ["SELECT '-- ?', '/* ?', '\$\$ ?'"],
        ];
    }

    #[DataProvider('untouchedProvider')]
    public function testPlaceholderCharactersInLiteralsAndCommentsAreLeftAlone(string $sql): void
    {
        $this->assertSame($sql, $this->statement()->sql($sql)->getComputed());
    }

    public function testBackslashDoesNotEscapeInStandardStrings(): void
    {
        // 'a\' is a complete string in PostgreSQL, so the "?" after it is a placeholder.
        $statement = $this->statement()->sql("SELECT 'a\\', ?")->bInt(1);

        $this->assertSame("SELECT 'a\\', 1", $statement->getComputed());
    }

    public function testNamesEndingInEAreNotEscapeStrings(): void
    {
        // "some'..." isn't E'...', so the backslash doesn't escape the closing quote.
        $statement = $this->statement()->sql("SELECT some'a\\', ?")->bInt(1);

        $this->assertSame("SELECT some'a\\', 1", $statement->getComputed());
    }

    public function testHashIsNotAComment(): void
    {
        $statement = $this->statement()->sql('SELECT data #> ? FROM t')->bStr('{a,b}');

        $this->assertSame("SELECT data #> '{a,b}' FROM t", $statement->getComputed());
    }

    public function testDoubleQuestionMarkIsALiteralOperator(): void
    {
        $statement = $this->statement()
            ->sql('SELECT * FROM t WHERE data ?? ? AND data ??| ? AND id = ?')
            ->bStr('a')->bStr('{b,c}')->bInt(3);

        // PDO turns "??" into "?" itself; getComputed() shows what the database gets.
        $this->assertSame(
            "SELECT * FROM t WHERE data ? 'a' AND data ?| '{b,c}' AND id = 3",
            $statement->getComputed()
        );
        $this->assertSame(
            'SELECT * FROM t WHERE data ?? :pos0 AND data ??| :pos1 AND id = :pos2',
            $this->unbound($statement)
        );
    }

    public function testDollarInNameDoesNotStartAString(): void
    {
        $statement = $this->statement()->sql('SELECT foo$bar$, ?')->bInt(1);

        $this->assertSame('SELECT foo$bar$, 1', $statement->getComputed());
    }

    public function testCastAfterPlaceholder(): void
    {
        $statement = $this->statement()->sql('SELECT ?::int')->bStr('5');

        $this->assertSame("SELECT '5'::int", $statement->getComputed());
    }

    public function testRawPlaceholderInDoubleQuotesNamesATable(): void
    {
        $statement = $this->statement()->sql('SELECT "col?" FROM "%%"')->bRaw('test');

        $this->assertSame('SELECT "col?" FROM "test"', $statement->getComputed());
    }

    public function testBIdentUsesDoubleQuotes(): void
    {
        $statement = $this->statement()->sql('SELECT %% FROM %%')->bIdent(['id', 'name'])->bIdent('public.t');

        $this->assertSame('SELECT "id", "name" FROM "public"."t"', $statement->getComputed());
    }

    public function testDollarStringSpanningSqlCallsIsTracked(): void
    {
        $statement = $this->statement()
            ->sql('CREATE FUNCTION f() RETURNS int AS $body$')
            ->sql('    SELECT 1; -- why?')
            ->sql('$body$ LANGUAGE sql; SELECT ?')->bInt(2);

        $this->assertSame(
            "CREATE FUNCTION f() RETURNS int AS \$body\$\n    SELECT 1; -- why?\n\$body\$ LANGUAGE sql; SELECT 2",
            $statement->getComputed()
        );
    }

    public function testResetClearsAnOpenNestedComment(): void
    {
        $statement = $this->statement()->sql('SELECT /* /* unterminated */');
        $statement->reset()->sql('SELECT ?')->bInt(5);

        $this->assertSame('SELECT 5', $statement->getComputed());
    }
}
