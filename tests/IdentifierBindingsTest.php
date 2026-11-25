<?php

namespace GeekLab\GLPDO2;

use GeekLab\GLPDO2\Bindings\Bindings;
use GeekLab\GLPDO2\Bindings\MySQL;
use GeekLab\GLPDO2\Bindings\MySQL\MySQLBindingFactory;
use GeekLab\GLPDO2\Bindings\MySQL\MySQLIdentifierBindings;
use GeekLab\GLPDO2\Bindings\PostgreSQL\PostgreSQLIdentifierBindings;
use GeekLab\GLPDO2\Bindings\QuotedIdentifierBindings;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * bIdent() quotes identifiers for the database the bindings are for. No database is needed.
 */
class IdentifierBindingsTest extends TestCase
{
    private function postgresBindings(): Bindings
    {
        return new Bindings(
            new MySQL\MySQLDateTimeBindings(),
            new MySQL\MySQLLogicBindings(),
            new MySQL\MySQLNumericBindings(),
            new MySQL\MySQLRawBindings(),
            new MySQL\MySQLStringBindings(),
            new PostgreSQLIdentifierBindings()
        );
    }

    /**
     * @return array<string, array{string | array<mixed>, string, string}>
     */
    public static function quotingProvider(): array
    {
        return [
            'plain name'           => ['users', '`users`', '"users"'],
            'qualified name'       => ['db.users.id', '`db`.`users`.`id`', '"db"."users"."id"'],
            'star after table'     => ['users.*', '`users`.*', '"users".*'],
            'bare star'            => ['*', '*', '*'],
            'list'                 => [['id', 'u.name'], '`id`, `u`.`name`', '"id", "u"."name"'],
            'backtick in name'     => ['we`ird', '`we``ird`', '"we`ird"'],
            'double quote in name' => ['we"ird', '`we"ird`', '"we""ird"'],
            'spaces and case'      => ['Order Items', '`Order Items`', '"Order Items"'],
            'dollar and slash'     => ['a$1\\b', '`a$1\\b`', '"a$1\\b"'],
        ];
    }

    /**
     * @param string | array<mixed> $name
     */
    #[DataProvider('quotingProvider')]
    public function testQuotingPerDatabase(string | array $name, string $mysql, string $postgres): void
    {
        $this->assertSame([$mysql], (new MySQLIdentifierBindings())->bIdent($name));
        $this->assertSame([$postgres], (new PostgreSQLIdentifierBindings())->bIdent($name));
    }

    public function testSqlServerStyleBrackets(): void
    {
        $this->assertSame(['[dbo].[we]]ird]'], (new QuotedIdentifierBindings('[', ']'))->bIdent('dbo.we]ird'));
    }

    public function testStatementUsesMySQLByDefault(): void
    {
        $statement = (new Statement(MySQLBindingFactory::build()))
            ->sql('SELECT %% FROM %%')->bIdent(['id', 'name'])->bIdent('users')
            ->sql('WHERE %% = ?')->bIdent('id')->bInt(5);

        $this->assertSame("SELECT `id`, `name` FROM `users`\nWHERE `id` = 5", $statement->getComputed());
    }

    public function testStatementUsesPostgreSQLQuoting(): void
    {
        $statement = (new Statement($this->postgresBindings()))
            ->sql('INSERT INTO %% (%%)')->bIdent('public.users')->bIdent(['name', 'role'])
            ->sql('VALUES (?, ?)')->bStr('Ann')->bStr('admin');

        $this->assertSame(
            "INSERT INTO \"public\".\"users\" (\"name\", \"role\")\nVALUES ('Ann', 'admin')",
            $statement->getComputed()
        );
    }

    public function testBindingsWithoutIdentifierArgumentDefaultToMySQL(): void
    {
        $bindings = new Bindings(
            new MySQL\MySQLDateTimeBindings(),
            new MySQL\MySQLLogicBindings(),
            new MySQL\MySQLNumericBindings(),
            new MySQL\MySQLRawBindings(),
            new MySQL\MySQLStringBindings()
        );

        $this->assertSame(['`users`'], $bindings->bIdent('users'));
    }

    public function testRawValueWithBackReferenceCharactersIsInsertedAsIs(): void
    {
        $statement = (new Statement(MySQLBindingFactory::build()))->sql('SELECT %%')->bIdent('a$1\\0b');

        $this->assertSame('SELECT `a$1\\0b`', $statement->getComputed());
    }

    /**
     * @return array<string, array{string | array<mixed>}>
     */
    public static function invalidProvider(): array
    {
        return [
            'empty name'    => [''],
            'empty part'    => ['db..users'],
            'trailing dot'  => ['users.'],
            'NUL byte'      => ["us\0ers"],
            'empty list'    => [[]],
            'non-string'    => [[1]],
        ];
    }

    /**
     * @param string | array<mixed> $name
     */
    #[DataProvider('invalidProvider')]
    public function testInvalidIdentifiersThrow(string | array $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new MySQLIdentifierBindings())->bIdent($name);
    }
}
