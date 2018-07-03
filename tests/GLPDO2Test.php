<?php

namespace GeekLab\GLPDO2;

use PDO;
use GeekLab\GLPDO2;
use PHPUnit\Framework\TestCase;

class GLPDO2Test extends TestCase
{
    private $db;
    private $x = [
        ['id' => 1, 'name' => 'Davis', 'location' => 'Germany', 'dp' => '10.1'],
        ['id' => 2, 'name' => 'Hyacinth', 'location' => 'Germany', 'dp' => '1.1'],
        ['id' => 3, 'name' => 'Quynn', 'location' => 'USA', 'dp' => '5.2'],
        ['id' => 4, 'name' => 'Julian', 'location' => 'USA', 'dp' => '2'],
        ['id' => 5, 'name' => 'Kieran', 'location' => 'Canada', 'dp' => '100.5'],
        ['id' => 6, 'name' => 'Ryder', 'location' => 'El Salvador', 'dp' => '60'],
        ['id' => 7, 'name' => 'Reese', 'location' => 'Estonia', 'dp' => '15.2'],
        ['id' => 8, 'name' => 'Sarah', 'location' => 'Christmas Island', 'dp' => '-10.5'],
        ['id' => 9, 'name' => 'Nadine', 'location' => 'Gabon', 'dp' => '-56.9'],
        ['id' => 10, 'name' => 'Drew', 'location' => 'Burundi', 'dp' => '-56.5'],
    ];

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $dbConn   = new PDO('sqlite::memory:', NULL, NULL, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db = new GLPDO2\GLPDO2($dbConn);
    }

    protected function setUp()
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('DROP TABLE IF EXISTS `test`');

        $this->db->queryDelete($Statement);

        $Statement->reset()
                  ->sql('CREATE TABLE `test` (')
                  ->sql('    `id`   INTEGER PRIMARY KEY AUTOINCREMENT,')
                  ->sql('    `name` TEXT DEFAULT NULL,')
                  ->sql('    `location` TEXT DEFAULT NULL,')
                  ->sql('    `dp` NUMERIC DEFAULT NULL')
                  ->sql(');');
        $this->db->queryInsert($Statement);

        $Statement->reset()
                  ->sql('INSERT INTO `test` (`id`,`name`,`location`,`dp`)')
                  ->sql('VALUES')
                  ->sql('    (1,"Davis", "Germany", "10.1"), (2,"Hyacinth", "Germany", "1.1"), (3,"Quynn", "USA", "5.2"), (4,"Julian", "USA", "2.0"), (5,"Kieran", "Canada", "100.5"), (6,"Ryder", "El Salvador", "60.0"), (7,"Reese", "Estonia", "15.2"), (8,"Sarah", "Christmas Island", "-10.5"), (9,"Nadine", "Gabon", "-56.9"), (10,"Drew", "Burundi", "-56.5");');
        $this->db->queryInsert($Statement);
    }

    public function testPDOConnection()
    {
        $this->assertTrue(is_object($this->db), '$this->db is not an object!');
    }

    // Basic Select
    public function testBasicSelect()
    {
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT * FROM `test`;');

        // Make sure our statement is what it is.
        $this->assertEquals('SELECT * FROM `test`;', $Statement->getComputed());

        // Only to help create the array ($this->x)
        //print_r($this->db->selectRows($Statement));

        // Testing results
        $this->assertEquals($this->x, $this->db->selectRows($Statement));
    }

    // Select with bindings
    // Bool
    public function testBoolFalseInt()
    {
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  (0 = ?);')->bBool(NULL, FALSE, TRUE);

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  (0 = 0);";

        // Statement filled in corrected?
        $this->assertEquals($expected, $Statement->getComputed());

        // Testing results
        $this->assertEquals($this->x, $this->db->selectRows($Statement));
    }

    // Bool
    public function testBoolTrueInt()
    {
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  (0 = ?);')->bBool('x', FALSE, TRUE);

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  (0 = 1);";

        $this->assertEquals($expected, $Statement->getComputed());
        $this->assertEmpty($this->db->selectRows($Statement));
    }

    // These two don't translate to SQLite...
    // BoolFalseBool
    // BoolTrueBool

    // Todo: Date (Probably need to improve date stuff in Statement.php)

    // Float
    public function testFloat()
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
            ['id' => '2', 'name' => 'Hyacinth', 'location' => 'Germany', 'dp' => '1.1']
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // Int
    public function testInt()
    {
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `id` = ?;')->bInt('1');

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `id` = 1;";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            ['id' => '1', 'name' => 'Davis', 'location' => 'Germany', 'dp' => '10.1']
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // Todo: Int array
    public function testIntArray()
    {
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM `test`')
                  ->sql('WHERE `id` IN (%%);')->bIntArray(array(1, 2, 3));

        $expected = "SELECT *\n" .
                    "FROM `test`\n" .
                    "WHERE `id` IN (1, 2, 3);";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            ['id' => '1', 'name' => 'Davis', 'location' => 'Germany', 'dp' => '10.1'],
            ['id' => '2', 'name' => 'Hyacinth', 'location' => 'Germany', 'dp' => '1.1'],
            ['id' => '3', 'name' => 'Quynn', 'location' => 'USA', 'dp' => '5.2']
        ];
        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // Like
    public function testLikeBeginsWith()
    {
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` LIKE ?;')->bLike('dr', FALSE, TRUE);

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `name` LIKE 'dr%';";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            ['id' => '10', 'name' => 'Drew', 'location' => 'Burundi', 'dp' => '-56.5']
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    public function testLikeEndsWith()
    {
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` LIKE ?;')->bLike('nn', TRUE, FALSE);

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `name` LIKE '%nn';";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            ['id' => '3', 'name' => 'Quynn', 'location' => 'USA', 'dp' => '5.2']
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    public function testLikeSomewhere()
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
            ['id' => '4', 'name' => 'Julian', 'location' => 'USA', 'dp' => '2']
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    public function testLikeNowhere()
    {
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` LIKE ?;')->bLike('li', TRUE, TRUE);

        $expected = "SELECT *\n" .
                    "FROM   `test`\n" .
                    "WHERE  `name` LIKE 'li';";

        $this->assertEquals($expected, $Statement->getComputed());
        $this->assertEquals([], $this->db->selectRows($Statement));
    }

    // Raw
    public function testRaw()
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
                    "(11 = 11);";

        $this->assertEquals($expected, $Statement->getComputed());
        $this->assertEquals($this->x, $this->db->selectRows($Statement));
    }

    // String
    public function testString()
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
            ['id' => '8', 'name' => 'Sarah', 'location' => 'Christmas Island', 'dp' => '-10.5']
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // String Array
    public function testStringArray()
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
            ['id' => 1, 'name' => 'Davis', 'location' => 'Germany', 'dp' => '10.1'],
            ['id' => 2, 'name' => 'Hyacinth', 'location' => 'Germany', 'dp' => '1.1'],
            ['id' => 3, 'name' => 'Quynn', 'location' => 'USA', 'dp' => '5.2'],
            ['id' => 4, 'name' => 'Julian', 'location' => 'USA', 'dp' => '2']
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // Insert
    public function testInsert()
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
                    ");";

        $this->assertEquals($expected, $Statement->getComputed());

        $this->assertEquals(11, $this->db->queryInsert($Statement), 'Insert statement did not return id of 11');
    }

    // Update
    public function testUpdate()
    {
        $Statement = new GLPDO2\Statement();
        $Statement->sql('UPDATE `test`')
                  ->sql('SET    `location` = ?')->bStr('Mexico')
                  ->sql('WHERE  `name`     = ?;')->bStr('Drew');

        $expected = "UPDATE `test`\n" .
                    "SET    `location` = 'Mexico'\n" .
                    "WHERE  `name`     = 'Drew';";

        $this->assertEquals($expected, $Statement->getComputed());
        $this->assertEquals(1, $this->db->queryUpdate($Statement), "0 or more than 1 row updated.");

        $Statement->reset()
                  ->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = \'Drew\';');

        $this->assertEquals('Mexico', $this->db->selectRow($Statement)['location'], 'Record did not update correctly.');
    }

    // selectValue tests
    public function testSelectValueCaseInsenstive()
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = \'Drew\';');

        $this->assertEquals('Drew', $this->db->selectValue($Statement, 'NAMe'));
    }

    public function testSelectValueCaseSensitve()
    {
        $Statement = new GLPDO2\Statement();

        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = \'Drew\';');

        $this->assertNotEquals('Drew', $this->db->selectValue($Statement, 'NAMe', TRUE));
        $this->assertEquals('Drew', $this->db->selectValue($Statement, 'name', TRUE));
    }

    // Delete
    public function testDelete()
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

        $expected = $this->x;

        array_pop($expected);

        $this->assertEquals($expected, $this->db->selectRows($Statement), 'Table data does not match!');
    }

    // Injection Test
    public function testInjection()
    {
        $Statement = new GLPDO2\Statement();
        $Statement->sql('SELECT *')
                  ->sql('FROM   `test`')
                  ->sql('WHERE  `name` = ?;')->bStr("1' OR 1; --'");

        $this->assertEmpty($this->db->selectRows($Statement));
    }

    // Todo: Transaction tests
}