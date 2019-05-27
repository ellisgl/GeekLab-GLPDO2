<?php

namespace GeekLab\GLPDO2;

use GeekLab\GLPDO2;
use PHPUnit\Framework\TestCase;
use \PDO;
use \Exception;
use \DomainException;

class GLPDO2Test extends TestCase
{
    private $db;
    private const SAMPLE_DATA = [
        ['id' => 1, 'name' => 'Davis', 'location' => 'Germany', 'dp' => '10.1', 'someDate' => null],
        ['id' => 2, 'name' => 'Hyacinth', 'location' => 'Germany', 'dp' => '1.1', 'someDate' => null],
        ['id' => 3, 'name' => 'Quynn', 'location' => 'USA', 'dp' => '5.2', 'someDate' => null],
        ['id' => 4, 'name' => 'Julian', 'location' => 'USA', 'dp' => '2', 'someDate' => null],
        ['id' => 5, 'name' => 'Kieran', 'location' => 'Canada', 'dp' => '100.5', 'someDate' => null],
        ['id' => 6, 'name' => 'Ryder', 'location' => 'El Salvador', 'dp' => '60', 'someDate' => null],
        ['id' => 7, 'name' => 'Reese', 'location' => 'Estonia', 'dp' => '15.2', 'someDate' => null],
        ['id' => 8, 'name' => 'Sarah', 'location' => 'Christmas Island', 'dp' => '-10.5', 'someDate' => null],
        ['id' => 9, 'name' => 'Nadine', 'location' => 'Gabon', 'dp' => '-56.9', 'someDate' => null],
        ['id' => 10, 'name' => 'Drew', 'location' => 'Burundi', 'dp' => '-56.5', 'someDate' => null],
    ];

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $dbConn   = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db = new GLPDO2\GLPDO2($dbConn);
    }

    protected function setUp(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('DROP TABLE IF EXISTS `test`');

        $this->db->queryDelete($Statement);

        $Statement->reset()
                  ->sql('CREATE TABLE `test` (')
                  ->sql('    `id`   INTEGER PRIMARY KEY AUTOINCREMENT,')
                  ->sql('    `name` TEXT DEFAULT NULL,')
                  ->sql('    `location` TEXT DEFAULT NULL,')
                  ->sql('    `dp` NUMERIC DEFAULT "0.0" NOT NULL,')
                  ->sql('    `someDate` TEXT DEFAULT NULL')
                  ->sql(');');
        $this->db->queryInsert($Statement);

        $Statement->reset()
                  ->sql('INSERT INTO `test` (`id`,`name`,`location`,`dp`)')
                  ->sql('VALUES')
                  ->sql('    (1,"Davis", "Germany", "10.1"), (2,"Hyacinth", "Germany", "1.1"), (3,"Quynn", "USA", "5.2"), (4,"Julian", "USA", "2.0"), (5,"Kieran", "Canada", "100.5"), (6,"Ryder", "El Salvador", "60.0"), (7,"Reese", "Estonia", "15.2"), (8,"Sarah", "Christmas Island", "-10.5"), (9,"Nadine", "Gabon", "-56.9"), (10,"Drew", "Burundi", "-56.5");');
        $this->db->queryInsert($Statement);
    }

    public function testPDOConnection(): void
    {
        $this->assertTrue(is_object($this->db), '$this->db is not an object!');
    }

    // Basic Select
    public function testBasicSelect(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT * FROM `test`;');

        // Make sure our statement is what it is.
        $this->assertEquals('SELECT * FROM `test`;', $Statement->getComputed());

        // Only to help create the array (self::SAMPLE_DATA)
        //print_r($this->db->selectRows($Statement));

        // Testing results
        $this->assertEquals(self::SAMPLE_DATA, $this->db->selectRows($Statement));
    }

    // Select with bindings
    // Bool
    public function testBoolFalseInt(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  (0 = ?);')->bBool(false, false, true);

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    'WHERE  (0 = 0);';

        // Statement filled in corrected?
        $this->assertEquals($expected, $Statement->getComputed());

        // Testing results
        $this->assertEquals(self::SAMPLE_DATA, $this->db->selectRows($Statement));
    }

    // Bool
    public function testBoolTrueInt(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  (0 = ?);')->bBool('x', false, true);

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    'WHERE  (0 = 1);';

        $this->assertEquals($expected, $Statement->getComputed());
        $this->assertEmpty($this->db->selectRows($Statement));
    }

    // These two don't translate to SQLite...
    // BoolFalseBool
    // BoolTrueBool

    // Date
    public function testDate(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `someDate` = ?;')->bDate('2000-01-02');

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `someDate` = '2000-01-02';";

        $this->assertEquals($expected, $Statement->getComputed());

        $Statement->reset()
                  ->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `someDate` = ?;')->bDate('');

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `someDate` = '1970-01-01';";

        $this->assertEquals($expected, $Statement->getComputed());
    }

    // Date Time
    public function testDateTime(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `someDate` = ?;')->bDateTime('2000-01-02 00:11:22');

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `someDate` = '2000-01-02 00:11:22';";

        $this->assertEquals($expected, $Statement->getComputed());

        $Statement->reset()
                  ->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `someDate` = ?;')->bDateTime('2000-01-02');

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `someDate` = '2000-01-02 00:00:00';";

        $this->assertEquals($expected, $Statement->getComputed());

        $Statement->reset()
                  ->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `someDate` = ?;')->bDateTime('');

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `someDate` = '1970-01-01 00:00:00';";

        $this->assertEquals($expected, $Statement->getComputed());
    }

    // Float
    public function testFloat(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `dp` = ?;')->bFloat('1.101', 1); // Making it one decimal point

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `dp` = '1.1';";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            ['id' => '2', 'name' => 'Hyacinth', 'location' => 'Germany', 'dp' => '1.1', 'someDate' => null]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // Int
    public function testInt(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `id` = ?;')->bInt('1');

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    'WHERE  `id` = 1;';

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            ['id' => '1', 'name' => 'Davis', 'location' => 'Germany', 'dp' => '10.1', 'someDate' => null]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // Int array
    public function testIntArray(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM `test`')
                  ->sql('WHERE `id` IN (%%);')->bIntArray(array(1, 2, 3));

        $expected = "SELECT *\n" .
                    "FROM `test`\n" .
                    'WHERE `id` IN (1, 2, 3);';

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            ['id' => '1', 'name' => 'Davis', 'location' => 'Germany', 'dp' => '10.1', 'someDate' => null],
            ['id' => '2', 'name' => 'Hyacinth', 'location' => 'Germany', 'dp' => '1.1', 'someDate' => null],
            ['id' => '3', 'name' => 'Quynn', 'location' => 'USA', 'dp' => '5.2', 'someDate' => null]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // Like
    public function testLikeBeginsWith(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` LIKE ?;')->bLike('dr', false, true);

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `name` LIKE 'dr%';";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            ['id' => '10', 'name' => 'Drew', 'location' => 'Burundi', 'dp' => '-56.5', 'someDate' => null]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    public function testLikeEndsWith(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` LIKE ?;')->bLike('nn', true);

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `name` LIKE '%nn';";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            ['id' => '3', 'name' => 'Quynn', 'location' => 'USA', 'dp' => '5.2', 'someDate' => null]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    public function testLikeSomewhere(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` LIKE ?;')->bLike('li');

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `name` LIKE '%li%';";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            ['id' => '4', 'name' => 'Julian', 'location' => 'USA', 'dp' => '2', 'someDate' => null]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    public function testLikeNowhere(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` LIKE ?;')->bLike('li', true, true);

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `name` LIKE 'li';";

        $this->assertEquals($expected, $Statement->getComputed());
        $this->assertEquals([], $this->db->selectRows($Statement));
    }

    // Raw
    public function testRaw(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT * FROM `%%` ')->bRaw('test')
                  ->sql('WHERE ')
                  ->sql('(0  = %%) & ')->bRaw(0)
                  ->sql('(1  = %%) & ')->bRaw(1)
                  ->sql('(2  = %%) & ')->bRaw(2)
                  ->sql('(3  = %%) & ')->bRaw(3)
                  ->sql('(4  = %%) & ')->bRaw(4)
                  ->sql('(5  = %%) & ')->bRaw(5)
                  ->sql('(6  = %%) & ')->bRaw(6)
                  ->sql('(7  = %%) & ')->bRaw(7)
                  ->sql('(8  = %%) & ')->bRaw(8)
                  ->sql('(9  = %%) & ')->bRaw(9)
                  ->sql('(10 = %%) & ')->bRaw(10)
                  ->sql('(11 = %%);')->bRaw(11);

        $expected = "SELECT * FROM `test` \n" .
                    "WHERE \n" .
                    "(0  = 0) & \n" .
                    "(1  = 1) & \n" .
                    "(2  = 2) & \n" .
                    "(3  = 3) & \n" .
                    "(4  = 4) & \n" .
                    "(5  = 5) & \n" .
                    "(6  = 6) & \n" .
                    "(7  = 7) & \n" .
                    "(8  = 8) & \n" .
                    "(9  = 9) & \n" .
                    "(10 = 10) & \n" .
                    '(11 = 11);';

        $this->assertEquals($expected, $Statement->getComputed());
        $this->assertEquals(self::SAMPLE_DATA, $this->db->selectRows($Statement));
    }

    // String
    public function testString(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = ?;')->bStr('Sarah');

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `name` = 'Sarah';";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            ['id' => '8', 'name' => 'Sarah', 'location' => 'Christmas Island', 'dp' => '-10.5', 'someDate' => null]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // String Array
    public function testStringArray(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `location` IN (%%);')->bStrArr(['Germany', 'USA']);

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `location` IN ('Germany', 'USA');";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            ['id' => 1, 'name' => 'Davis', 'location' => 'Germany', 'dp' => '10.1', 'someDate' => null],
            ['id' => 2, 'name' => 'Hyacinth', 'location' => 'Germany', 'dp' => '1.1', 'someDate' => null],
            ['id' => 3, 'name' => 'Quynn', 'location' => 'USA', 'dp' => '5.2', 'someDate' => null],
            ['id' => 4, 'name' => 'Julian', 'location' => 'USA', 'dp' => '2', 'someDate' => null]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // Insert
    public function testInsert(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`)')->bRaw('test')
                  ->sql('VALUES (')
                  ->sql('    ?,')->bStr('Ellis')
                  ->sql('    ?,')->bStr('USA')
                  ->sql('    ?')->bFloat(8.5, 1)
                  ->sql(');');

        $expected = "INSERT INTO `test` (`name`, `location`, `dp`)\n" .
                    "VALUES (\n" .
                    "    'Ellis',\n" .
                    "    'USA',\n" .
                    "    '8.5'\n" .
                    ');';

        $this->assertEquals($expected, $Statement->getComputed());

        $this->assertEquals(11, $this->db->queryInsert($Statement), 'Insert statement did not return id of 11');
    }

    // Update
    public function testUpdate(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('UPDATE `test`')
                  ->sql('SET    `location` = ?')->bStr('Mexico')
                  ->sql('WHERE  `name`     = ?;')->bStr('Drew');

        $expected = "UPDATE `test`\n" .
                    "SET    `location` = 'Mexico'\n" .
                    "WHERE  `name`     = 'Drew';";

        $this->assertEquals($expected, $Statement->getComputed());
        $this->assertEquals(1, $this->db->queryUpdate($Statement), '0 or more than 1 row updated.');

        $Statement->reset()
                  ->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = \'Drew\';');
        $this->assertEquals('Mexico', $this->db->selectRow($Statement)['location'], 'Record did not update correctly.');
    }

    // selectValue tests
    public function testSelectValueCaseInsenstive(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = \'Drew\';');
        $this->assertEquals('Drew', $this->db->selectValue($Statement, 'NAMe'));
    }

    public function testSelectValueCaseSensitve(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = \'Drew\';');
        $this->assertNotEquals('Drew', $this->db->selectValue($Statement, 'NAMe', true));
        $this->assertEquals('Drew', $this->db->selectValue($Statement, 'name', true));
    }

    // Delete
    public function testDelete(): void
    {
        $Statement = new GLPDO2\Statement();
        $Statement->sql('DELETE FROM `test`')
                  ->sql('WHERE       `name` = ?;')->bStr('Drew');

        $expected = "DELETE FROM `test`\n" .
                    "WHERE       `name` = 'Drew';";

        $this->assertEquals($expected, $Statement->getComputed());
        $this->assertEquals(1, $this->db->queryDelete($Statement), '0 or more than 1 row deleted.');

        $Statement->reset()
                  ->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = ?;')->bStr('Drew');
        $this->assertEmpty($this->db->selectRow($Statement), 'Record did not delete.');

        $Statement->reset()
                  ->sql('SELECT *')
                  ->sql('FROM   `test`;');

        $expected = self::SAMPLE_DATA;

        array_pop($expected);

        $this->assertEquals($expected, $this->db->selectRows($Statement), 'Table data does not match!');
    }

    // Injection Test
    public function testInjection(): void
    {
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = ?;')->bStr("1' OR 1; --'");

        $this->assertEmpty($this->db->selectRows($Statement));
    }

    // Good transaction
    public function testGoodTransaction(): void
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`)')->bRaw('test')
                  ->sql('VALUES (')
                  ->sql('    ?,')->bStr('Ellis2')
                  ->sql('    ?,')->bStr('USA')
                  ->sql('    ?')->bFloat('1.8', 1)
                  ->sql(');');

        $expected = "INSERT INTO `test` (`name`, `location`, `dp`)\n" .
                    "VALUES (\n" .
                    "    'Ellis2',\n" .
                    "    'USA',\n" .
                    "    '1.8'\n" .
                    ');';

        $this->assertEquals($expected, $Statement->getComputed());

        try {
            $this->db->beginTransaction();

            $result = $this->db->queryInsert($Statement);

            $this->db->commit();
            $this->assertEquals(11, $result, 'Insert statement did not return id of 11');
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }

            throw $e;
        }
    }

    // Exception Tests
    public function testIntNullException(): void
    {
        $this->expectException(Exception::class);
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `id` = ?;')->bInt();
        $this->db->selectRows($Statement);
    }

    public function testStringNullException(): void
    {
        $this->expectException(Exception::class);
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = ?;')->bStr(null);
        $this->db->selectRows($Statement);

    }

    public function testBoolNullException(): void
    {
        $this->expectException(Exception::class);
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = ?;')->bBool(null);
        $this->db->selectRows($Statement);
    }

    public function testFloatNullException(): void
    {
        $this->expectException(Exception::class);
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = ?;')->bFloat(null);
        $this->db->selectRows($Statement);
    }

    public function testDateNullException(): void
    {
        $this->expectException(Exception::class);
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `someDate` = ?;')->bDate(null, false);
        $this->db->selectRows($Statement);
    }

    public function testDateTimeNullException(): void
    {
        $this->expectException(Exception::class);
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `someDate` = ?;')->bDateTime(null, false);
        $this->db->selectRows($Statement);

    }

    public function testIntArrayEmptyArrayException(): void
    {
        $this->expectException(Exception::class);
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` IN (%%);')->bIntArray(array());
        $this->db->selectRows($Statement);
    }

    public function testFloatInvalidTypeException(): void
    {
        $this->expectException(Exception::class);
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = ?;')->bFloat('xyz');
        $this->db->selectRows($Statement);
    }

    public function testIntInvalidTypeException(): void
    {
        $this->expectException(Exception::class);
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = ?;')->bInt('xyz');
        $this->db->selectRows($Statement);
    }

    public function testBadTransaction(): void
    {
        $this->expectException(Exception::class);
        $Statement = new GLPDO2\Statement();
        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`)')->bRaw('test')
                  ->sql('VALUES (')
                  ->sql('    ?,')->bStr('Ellis2')
                  ->sql('    ?,')->bStr('USA')
                  ->sql('    ?')->bStr(null, true)
                  ->sql(');');
        $this->db->beginTransaction();
        $this->db->queryInsert($Statement);
    }
}