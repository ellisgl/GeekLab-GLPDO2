<?php

namespace GeekLab\GLPDO2;

use GeekLab\GLPDO2\Bindings\SQLite\SQLiteBindingFactory;
use PDO;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

/**
 * Runs SQLite-specific SQL through GLPDO2 against an in-memory database, so no server is needed.
 */
#[RequiresPhpExtension('pdo_sqlite')]
class GLPDO2SQLiteTest extends TestCase
{
    private GLPDO2 $db;

    protected function setUp(): void
    {
        $this->db = new GLPDO2(new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]));

        $this->db->queryUpdate(
            $this->statement()
                ->sql('CREATE TABLE %% (')->bIdent('Test Items')
                ->sql('    [id]     INTEGER PRIMARY KEY,')
                ->sql('    `Name`   TEXT NOT NULL, -- any of the three identifier quotes?')
                ->sql('    "active" INTEGER NOT NULL,')
                ->sql('    "data"   TEXT NOT NULL,')
                ->sql('    "day"    TEXT NOT NULL')
                ->sql(')')
        );

        foreach ([['Ann', true, '{"n": 1}'], ['Bob', false, '{"n": 2}']] as [$name, $active, $json]) {
            $this->db->queryInsert(
                $this->statement()
                    ->sql('INSERT INTO %% (%%)')->bIdent('Test Items')->bIdent(['Name', 'active', 'data', 'day'])
                    ->sql('VALUES (?, ?, ?, ?)')->bStr($name)->bBool($active)->bStr($json)->bDate('2026-01-02')
            );
        }
    }

    private function statement(): Statement
    {
        return new Statement(SQLiteBindingFactory::build());
    }

    public function testInsertReturnsTheNewId(): void
    {
        $id = $this->db->queryInsert(
            $this->statement()
                ->sql('INSERT INTO "Test Items" ("Name", "active", "data", "day") VALUES (?, ?, ?, ?)')
                ->bStr('Cy')->bBool(true)->bStr('{}')->bDate('2026-01-03')
        );

        $this->assertSame('3', $id);
    }

    public function testQuotedIdentifiersAndPlaceholders(): void
    {
        $rows = $this->db->selectRows(
            $this->statement()
                ->sql('SELECT %% FROM %%')->bIdent(['Test Items.id', 'Name'])->bIdent('main.Test Items')
                ->sql('WHERE [Name] IN (%%) AND `active` = ? ORDER BY "id"')->bStrArr(['Ann', 'Bob'])->bBool(true)
        );

        $this->assertSame([['id' => 1, 'Name' => 'Ann']], $rows);
    }

    public function testLiteralsAndCommentsKeepTheirQuestionMarks(): void
    {
        $row = $this->db->selectRow(
            $this->statement()
                ->sql("SELECT 'Why?' AS a, 'a\\' AS b, 1 AS [c?], ? AS d --trailing?")->bInt(7)
                ->sql('/* why? */')
        );

        $this->assertSame(['a' => 'Why?', 'b' => 'a\\', 'c?' => 1, 'd' => 7], $row);
    }

    public function testJsonArrowOperators(): void
    {
        $value = $this->db->selectValue(
            $this->statement()->sql('SELECT "data" ->> ? AS n FROM "Test Items" WHERE "id" = ?')
                ->bStr('$.n')->bInt(2),
            'n'
        );

        $this->assertSame('2', $value);
    }

    public function testUnsignedValuesWithinTheIntegerRange(): void
    {
        $count = $this->db->selectValue(
            $this->statement()
                ->sql('SELECT COUNT(*) AS c FROM "Test Items" WHERE "id" IN (%%) OR "id" = %%')
                ->bUIntArray([1, '2'])->bUInt(PHP_INT_MAX),
            'c'
        );

        $this->assertSame('2', $count);
    }
}
