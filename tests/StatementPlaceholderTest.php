<?php

namespace GeekLab\GLPDO2;

use GeekLab\GLPDO2\Bindings\MySQL\MySQLBindingFactory;
use PHPUnit\Framework\TestCase;

/**
 * '?' and '%%' are placeholders only outside quoted strings, backtick identifiers and comments.
 * These tests only build statements, so no database is needed.
 */
class StatementPlaceholderTest extends TestCase
{
    private function statement(): Statement
    {
        return new Statement(MySQLBindingFactory::build());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function untouchedProvider(): array
    {
        return [
            'single-quoted string'     => ["SELECT 'Why?'"],
            'double-quoted string'     => ['SELECT "Why?"'],
            'backtick identifier'      => ['SELECT `odd?name` FROM t'],
            'doubled quote escape'     => ["SELECT 'It''s ok?'"],
            'backslash escaped quote'  => ["SELECT 'a\\'b?'"],
            'escaped backslash at end' => ["SELECT 'a\\\\', 'b?'"],
            'percent pair in string'   => ["SELECT * FROM t WHERE a LIKE '%%'"],
            'dash dash comment'        => ['SELECT 1 -- why? %%'],
            'hash comment'             => ['SELECT 1 # why? %%'],
            'block comment'            => ['SELECT 1 /* why? %% */'],
            'quote inside comment'     => ["SELECT 1 /* it's? */"],
            'comment marker in string' => ["SELECT '-- ?', '# ?', '/* ?'"],
        ];
    }

    /**
     * @dataProvider untouchedProvider
     */
    public function testPlaceholderCharactersInLiteralsAndCommentsAreLeftAlone(string $sql): void
    {
        $this->assertSame($sql, $this->statement()->sql($sql)->getComputed());
    }

    public function testPlaceholdersAroundLiteralsStillWork(): void
    {
        $statement = $this->statement()
            ->sql("SELECT ?, 'Why?', ?")->bInt(1)->bStr('x');

        $this->assertSame("SELECT 1, 'Why?', 'x'", $statement->getComputed());
    }

    public function testRawPlaceholderInBackticksNamesATable(): void
    {
        $statement = $this->statement()->sql('SELECT `col?` FROM `%%`')->bRaw('test');

        $this->assertSame('SELECT `col?` FROM `test`', $statement->getComputed());
    }

    public function testPlaceholderAfterBlockCommentWorks(): void
    {
        $statement = $this->statement()->sql('SELECT /* ? */ ?')->bInt(7);

        $this->assertSame('SELECT /* ? */ 7', $statement->getComputed());
    }

    public function testDashDashWithoutSpaceIsNotAComment(): void
    {
        // MySQL reads "1--?" as 1 minus minus the value, not as a comment.
        $statement = $this->statement()->sql('SELECT 1--?')->bInt(2);

        $this->assertSame('SELECT 1--2', $statement->getComputed());
    }

    public function testStringSpanningSqlCallsIsTracked(): void
    {
        $statement = $this->statement()
            ->sql("SELECT 'start")
            ->sql("still a string?'")
            ->sql(', ?')->bInt(3);

        $this->assertSame("SELECT 'start\nstill a string?'\n, 3", $statement->getComputed());
    }

    public function testLineCommentEndsWithItsSqlCall(): void
    {
        $statement = $this->statement()
            ->sql('SELECT 1 -- note?')
            ->sql('+ ?')->bInt(4);

        $this->assertSame("SELECT 1 -- note?\n+ 4", $statement->getComputed());
    }

    public function testResetClearsAnOpenString(): void
    {
        $statement = $this->statement()->sql("SELECT 'unterminated");
        $statement->reset()->sql('SELECT ?')->bInt(5);

        $this->assertSame('SELECT 5', $statement->getComputed());
    }
}
