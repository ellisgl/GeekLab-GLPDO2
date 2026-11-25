<?php

namespace GeekLab\GLPDO2;

use GeekLab\GLPDO2\Bindings\SQLite\SQLiteBindingFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * With the SQLite dialect, sql() finds strings and comments by SQLite's rules.
 * These tests only build statements, so no database is needed.
 */
class StatementSQLitePlaceholderTest extends TestCase
{
    private function statement(): Statement
    {
        return new Statement(SQLiteBindingFactory::build());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function untouchedProvider(): array
    {
        return [
            'single-quoted string'     => ["SELECT 'Why?'"],
            'doubled quote escape'     => ["SELECT 'It''s ok?'"],
            'double-quoted identifier' => ['SELECT "odd?name" FROM t'],
            'backtick identifier'      => ['SELECT `odd?name` FROM t'],
            'bracket identifier'       => ['SELECT [odd?name] FROM t'],
            'quote in brackets'        => ["SELECT [it's?] FROM t"],
            'dash dash without space'  => ['SELECT 1 --why? %%'],
            'block comment'            => ['SELECT 1 /* why? %% */'],
            'percent pair in string'   => ["SELECT * FROM t WHERE a LIKE '%%'"],
            'comment marker in string' => ["SELECT '-- ?', '/* ?', '[?'"],
        ];
    }

    #[DataProvider('untouchedProvider')]
    public function testPlaceholderCharactersInLiteralsAndCommentsAreLeftAlone(string $sql): void
    {
        $this->assertSame($sql, $this->statement()->sql($sql)->getComputed());
    }

    public function testBackslashDoesNotEscapeInStrings(): void
    {
        // 'a\' is a complete string in SQLite, so the "?" after it is a placeholder.
        $statement = $this->statement()->sql("SELECT 'a\\', ?")->bInt(1);

        $this->assertSame("SELECT 'a\\', 1", $statement->getComputed());
    }

    public function testEscapeStringPrefixMeansNothing(): void
    {
        // E'...' is PostgreSQL only; in SQLite E is a name and the backslash is a plain character.
        $statement = $this->statement()->sql("SELECT E'a\\', ?")->bInt(1);

        $this->assertSame("SELECT E'a\\', 1", $statement->getComputed());
    }

    public function testHashIsNotAComment(): void
    {
        $statement = $this->statement()->sql('SELECT ? # ?')->bInt(1)->bInt(2);

        $this->assertSame('SELECT 1 # 2', $statement->getComputed());
    }

    public function testBlockCommentsDoNotNest(): void
    {
        // The first "*/" ends the comment, so the "?" after it is a placeholder.
        $statement = $this->statement()->sql('SELECT /* a /* b */ ?')->bInt(3);

        $this->assertSame('SELECT /* a /* b */ 3', $statement->getComputed());
    }

    public function testDollarSignsAreNotQuotes(): void
    {
        $statement = $this->statement()->sql('SELECT $$, ?')->bInt(4);

        $this->assertSame('SELECT $$, 4', $statement->getComputed());
    }

    public function testDoubleQuestionMarkIsTwoPlaceholders(): void
    {
        // SQLite has no "?" operator, so "??" isn't an escape here.
        $statement = $this->statement()->sql('SELECT ??')->bInt(1)->bInt(2);

        $this->assertSame('SELECT 12', $statement->getComputed());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function rawIdentifierProvider(): array
    {
        return [
            'double quotes' => ['SELECT "col?" FROM "%%"', 'SELECT "col?" FROM "test"'],
            'backticks'     => ['SELECT `col?` FROM `%%`', 'SELECT `col?` FROM `test`'],
            'brackets'      => ['SELECT [col?] FROM [%%]', 'SELECT [col?] FROM [test]'],
        ];
    }

    #[DataProvider('rawIdentifierProvider')]
    public function testRawPlaceholderInIdentifierQuotesNamesATable(string $sql, string $expected): void
    {
        $this->assertSame($expected, $this->statement()->sql($sql)->bRaw('test')->getComputed());
    }

    public function testBIdentUsesDoubleQuotes(): void
    {
        $statement = $this->statement()->sql('SELECT %% FROM %%')->bIdent(['id', 'name'])->bIdent('main.t');

        $this->assertSame('SELECT "id", "name" FROM "main"."t"', $statement->getComputed());
    }

    public function testBracketIdentifierSpanningSqlCallsIsTracked(): void
    {
        $statement = $this->statement()
            ->sql('SELECT [odd')
            ->sql('name?]')
            ->sql(', ?')->bInt(5);

        $this->assertSame("SELECT [odd\nname?]\n, 5", $statement->getComputed());
    }

    public function testBUIntStopsAtSQLitesIntegerMaximum(): void
    {
        $this->assertSame(
            'SELECT 9223372036854775807',
            $this->statement()->sql('SELECT %%')->bUInt('9223372036854775807')->getComputed()
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->statement()->bUIntArray([1, '9223372036854775808']);
    }
}
