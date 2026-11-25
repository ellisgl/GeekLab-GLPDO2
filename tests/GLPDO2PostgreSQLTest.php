<?php

namespace GeekLab\GLPDO2;

use GeekLab\GLPDO2\Bindings\PostgreSQL\PostgreSQLBindingFactory;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Runs PostgreSQL-specific SQL through GLPDO2 against a real server.
 * Skipped unless PGHOST is set (with PGDB, PGUSER, PGPASS and optionally PGPORT).
 */
class GLPDO2PostgreSQLTest extends TestCase
{
    private GLPDO2 $db;

    protected function setUp(): void
    {
        if (!getenv('PGHOST')) {
            $this->markTestSkipped('Set PGHOST, PGDB, PGUSER and PGPASS to run the PostgreSQL tests.');
        }

        $pdo = new PDO(
            'pgsql:host=' . getenv('PGHOST') . ';port=' . (getenv('PGPORT') ?: '5432') . ';dbname=' . getenv('PGDB'),
            (string)getenv('PGUSER'),
            (string)getenv('PGPASS'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $this->db = new GLPDO2($pdo);

        $this->db->queryUpdate($this->statement()->sql('DROP TABLE IF EXISTS %%')->bIdent('Test Items'));
        $this->db->queryUpdate(
            $this->statement()
                ->sql('CREATE TABLE %% (')->bIdent('Test Items')
                ->sql('    "id"   SERIAL PRIMARY KEY,')
                ->sql('    "Name" TEXT NOT NULL, -- quoted, so case sensitive?')
                ->sql('    "data" JSONB NOT NULL')
                ->sql(')')
        );

        foreach ([['Ann', '{"tags": ["a", "b"], "n": 1}'], ['Bob', '{"tags": ["c"], "x": 2}']] as [$name, $json]) {
            $this->db->queryInsert(
                $this->statement()
                    ->sql('INSERT INTO %% (%%)')->bIdent('Test Items')->bIdent(['Name', 'data'])
                    ->sql('VALUES (?, ?::jsonb)')->bStr($name)->bStr($json)
            );
        }
    }

    private function statement(): Statement
    {
        return new Statement(PostgreSQLBindingFactory::build());
    }

    public function testInsertReturnsTheNewId(): void
    {
        $id = $this->db->queryInsert(
            $this->statement()
                ->sql('INSERT INTO "Test Items" ("Name", "data") VALUES (?, ?::jsonb)')->bStr('Cy')->bStr('{}')
        );

        $this->assertSame('3', $id);
    }

    public function testQuotedIdentifiersAndPlaceholders(): void
    {
        $rows = $this->db->selectRows(
            $this->statement()
                ->sql('SELECT %% FROM %%')->bIdent(['Test Items.id', 'Name'])->bIdent('public.Test Items')
                ->sql('WHERE "Name" IN (%%) ORDER BY "id"')->bStrArr(['Ann', 'Bob'])
        );

        $this->assertSame([['id' => 1, 'Name' => 'Ann'], ['id' => 2, 'Name' => 'Bob']], $rows);
    }

    public function testJsonbQuestionMarkOperators(): void
    {
        $value = $this->db->selectValue(
            $this->statement()
                ->sql('SELECT "Name" FROM "Test Items"')
                ->sql('WHERE "data" ?? ? AND "data"->\'tags\' ??| ?::text[]')->bStr('x')->bStr('{c,z}'),
            'Name',
            true
        );

        $this->assertSame('Bob', $value);
    }

    public function testHashOperatorIsNotAComment(): void
    {
        $value = $this->db->selectValue(
            $this->statement()->sql('SELECT "data" #>> ? AS v FROM "Test Items" WHERE "id" = ?')
                ->bStr('{tags,1}')->bInt(1),
            'v'
        );

        $this->assertSame('b', $value);
    }

    public function testLiteralsAndCommentsKeepTheirQuestionMarks(): void
    {
        // PDO's own scanner doesn't nest comments, so the outer comment has no "?" after the inner one closes.
        $row = $this->db->selectRow(
            $this->statement()
                ->sql("SELECT 'Why?' AS a, E'it\\'s?' AS b, \"odd?\" AS c, d")
                ->sql('FROM (SELECT 1 AS "odd?") t, /* outer? /* nested? */ */ (SELECT ?::int AS d) u --trailing?')
                ->bInt(7)
        );

        $this->assertSame(['a' => 'Why?', 'b' => "it's?", 'c' => 1, 'd' => 7], $row);
    }

    public function testDollarQuotedStringKeepsItsQuestionMarks(): void
    {
        $row = $this->db->selectRow(
            $this->statement()->sql('SELECT $$ok? %%$$ AS a, $t$it\'s? $$ $t$ AS b, ?::int AS c')->bInt(7)
        );

        $this->assertSame(['a' => 'ok? %%', 'b' => "it's? \$\$ ", 'c' => 7], $row);
    }

    public function testDollarQuotedFunctionBody(): void
    {
        $this->db->queryUpdate(
            $this->statement()
                ->sql('CREATE OR REPLACE FUNCTION pg_temp.glpdo2_add(a int, b int) RETURNS int AS $fn$')
                ->sql('    SELECT a + b; -- what? %%')
                ->sql('$fn$ LANGUAGE sql')
        );

        $value = $this->db->selectValue(
            $this->statement()->sql('SELECT pg_temp.glpdo2_add(?, ?) AS v')->bInt(2)->bInt(3),
            'v'
        );

        $this->assertSame('5', $value);
    }

    public function testUnsignedValuesAboveBigint(): void
    {
        // PostgreSQL has no unsigned types; numeric(20) holds the BIGINT UNSIGNED range.
        $this->db->queryUpdate(
            $this->statement()->sql('CREATE TEMPORARY TABLE "big" ("id" numeric(20) PRIMARY KEY)')
        );
        $this->db->queryInsert(
            $this->statement()->sql('INSERT INTO "big" VALUES (%%), (%%)')
                ->bUInt('18446744073709551615')->bUInt(0)
        );

        $value = $this->db->selectValue(
            $this->statement()->sql('SELECT "id" FROM "big" WHERE "id" IN (%%)')
                ->bUIntArray(['18446744073709551615', '18446744073709551614']),
            'id'
        );

        $this->assertSame('18446744073709551615', $value);
    }
}
