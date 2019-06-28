<?php

namespace GeekLab\GLPDO2;

use GeekLab\GLPDO2;
use PHPUnit\Framework\TestCase;
use \PDO;
use \Exception;
use \JsonException;
use \TypeError;
use \stdClass;

class GLPDO2MySQLTest extends TestCase
{
    private $db;
    private const SAMPLE_DATA = [
        [
            'id' => '1',
            'name' => 'Davis',
            'location' => 'Germany',
            'dp' => '10.1',
            'someDate' => '2000-01-01',
            'someDateTime' => '2000-01-01 00:01:02'
        ],
        [
            'id' => '2',
            'name' => 'Hyacinth',
            'location' => 'Germany',
            'dp' => '1.1',
            'someDate' => '2000-01-02',
            'someDateTime' => '2000-01-02 00:01:02'
        ],
        [
            'id' => '3',
            'name' => 'Quynn',
            'location' => 'USA',
            'dp' => '5.2',
            'someDate' => '2000-01-03',
            'someDateTime' => '2000-01-03 00:01:02'
        ],
        [
            'id' => '4',
            'name' => 'Julian',
            'location' => 'USA',
            'dp' => '2.0',
            'someDate' => '2000-01-04',
            'someDateTime' => '2000-01-04 00:01:02'
        ],
        [
            'id' => '5',
            'name' => 'Kieran',
            'location' => 'Canada',
            'dp' => '100.5',
            'someDate' => '2000-01-05',
            'someDateTime' => '2000-01-05 00:01:02'
        ],
        [
            'id' => '6',
            'name' => 'Ryder',
            'location' => 'El Salvador',
            'dp' => '60.0',
            'someDate' => '2000-01-06',
            'someDateTime' => '2000-01-06 00:01:02'
        ],
        [
            'id' => '7',
            'name' => 'Reese',
            'location' => 'Estonia',
            'dp' => '15.2',
            'someDate' => '2000-01-07',
            'someDateTime' => '2000-01-07 00:01:02'
        ],
        [
            'id' => '8',
            'name' => 'Sarah',
            'location' => 'Christmas Island',
            'dp' => '-10.5',
            'someDate' => '2000-01-08',
            'someDateTime' => '2000-01-08 00:01:02'
        ],
        [
            'id' => '9',
            'name' => 'Nadine',
            'location' => 'Gabon',
            'dp' => '-56.9',
            'someDate' => '2000-01-09',
            'someDateTime' => '2000-01-09 00:01:02'
        ],
        [
            'id' => '10',
            'name' => 'Drew',
            'location' => 'Burundi',
            'dp' => '-56.5',
            'someDate' => '2000-01-10',
            'someDateTime' => '2000-01-10 00:01:02'
        ],
    ];

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $dbConn = new PDO('mysql:host=' . getenv('MYSQLHOST') . ';dbname=' . getenv('MYSQLDB'), getenv('MYSQLUSER'),
            getenv('MYSQLPASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db = new GLPDO2\GLPDO2($dbConn);
    }

    protected function setUp(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('DROP TABLE IF EXISTS `test`');

        $this->db->queryDelete($Statement);

        $Statement->reset()
            ->sql('CREATE TABLE `test` (')
            ->sql('    `id`   INTEGER PRIMARY KEY AUTO_INCREMENT,')
            ->sql('    `name` VARCHAR(255) DEFAULT NULL,')
            ->sql('    `location` VARCHAR(255) DEFAULT NULL,')
            ->sql('    `dp` FLOAT(10,1) DEFAULT "0.0" NOT NULL,')
            ->sql('    `someDate` DATE NOT NULL,')
            ->sql('    `someDateTime` DATETIME NOT NULL')
            ->sql(');');
        $this->db->queryInsert($Statement);

        $Statement->reset()
            ->sql('INSERT INTO `test` (`id`, `name`, `location`, `dp`, `someDate`, `someDateTime`)')
            ->sql('VALUES')
            ->sql('    (1,"Davis", "Germany", "10.1", "2000-01-01", "2000-01-01 00:01:02"),')
            ->sql('    (2,"Hyacinth", "Germany", "1.1", "2000-01-02", "2000-01-02 00:01:02"),')
            ->sql('    (3,"Quynn", "USA", "5.2", "2000-01-03", "2000-01-03 00:01:02"),')
            ->sql('    (4,"Julian", "USA", "2.0", "2000-01-04", "2000-01-04 00:01:02"),')
            ->sql('    (5,"Kieran", "Canada", "100.5", "2000-01-05", "2000-01-05 00:01:02"),')
            ->sql('    (6,"Ryder", "El Salvador", "60.0", "2000-01-06", "2000-01-06 00:01:02"),')
            ->sql('    (7,"Reese", "Estonia", "15.2", "2000-01-07", "2000-01-07 00:01:02"),')
            ->sql('    (8,"Sarah", "Christmas Island", "-10.5", "2000-01-08", "2000-01-08 00:01:02"),')
            ->sql('    (9,"Nadine", "Gabon", "-56.9", "2000-01-09", "2000-01-09 00:01:02"),')
            ->sql('    (10,"Drew", "Burundi", "-56.5", "2000-01-10", "2000-01-10 00:01:02");');
        $this->db->queryInsert($Statement);
    }

    public function testPDOConnection(): void
    {
        $this->assertIsObject($this->db, '$this->db is not an object!');
    }

    // Basic Select
    public function testBasicSelect(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT * FROM `test`;');

        // Make sure our statement is what it is.
        $this->assertEquals('SELECT * FROM `test`;', $Statement->getComputed());

        // Only to help create the array (self::SAMPLE_DATA)
        //print_r($this->db->selectRows($Statement));

        // Testing results
        $this->assertEquals(self::SAMPLE_DATA, $this->db->selectRows($Statement));
    }

    // For code coverage...
    public function testDoesItConstruct(): void
    {
        $dbConn = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db = new GLPDO2\GLPDO2($dbConn);

        $this->assertSame(\GeekLab\GLPDO2\GLPDO2::class, get_class($db));
    }

    // Select with bindings
    // Bool
    public function testBoolIntNull(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis2')
            ->sql('    ?,')->bBoolIntNullable()// Yeah, totally need to make the table better for all the tests...
            ->sql('    %%,')->bFloat('1.8', 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');

        $expected = "INSERT INTO `test` (`name`, `location`, `dp`, `someDate`, `someDateTime`)\n" .
            "VALUES (\n" .
            "    'Ellis2',\n" .
            "    NULL,\n" .
            "    1.8,\n" .
            "    '2000-01-12',\n" .
            "    '2000-01-12 00:01:02'\n" .
            ');';

        $this->assertEquals($expected, $Statement->getComputed());
    }

    // Bool
    public function testBoolFalseInt(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  (0 = ?);')->bBoolInt(false);

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
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  (0 = ?);')->bBoolInt(true);

        $expected = "SELECT *\n" .
            "FROM   `test`\n" .
            'WHERE  (0 = 1);';

        $this->assertEquals($expected, $Statement->getComputed());
        $this->assertEmpty($this->db->selectRows($Statement));
    }

    public function testBoolNull(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis2')
            ->sql('    ?,')->bBoolNullable()// Yeah, totally need to make the table better for all the tests...
            ->sql('    %%,')->bFloat('1.8', 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');

        $expected = "INSERT INTO `test` (`name`, `location`, `dp`, `someDate`, `someDateTime`)\n" .
            "VALUES (\n" .
            "    'Ellis2',\n" .
            "    NULL,\n" .
            "    1.8,\n" .
            "    '2000-01-12',\n" .
            "    '2000-01-12 00:01:02'\n" .
            ');';

        $this->assertEquals($expected, $Statement->getComputed());
    }

    public function testBoolTrue(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis2')
            ->sql('    ?,')->bBool(true)// Yeah, totally need to make the table better for all the tests...
            ->sql('    %%,')->bFloat('1.8', 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');

        $expected = "INSERT INTO `test` (`name`, `location`, `dp`, `someDate`, `someDateTime`)\n" .
            "VALUES (\n" .
            "    'Ellis2',\n" .
            "    TRUE,\n" .
            "    1.8,\n" .
            "    '2000-01-12',\n" .
            "    '2000-01-12 00:01:02'\n" .
            ');';

        $this->assertEquals($expected, $Statement->getComputed());

        $this->db->queryInsert($Statement);

    }

    public function testBoolFalse(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis2')
            ->sql('    ?,')->bBool(false)// Yeah, totally need to make the table better for all the tests...
            ->sql('    %%,')->bFloat('1.8', 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');

        $expected = "INSERT INTO `test` (`name`, `location`, `dp`, `someDate`, `someDateTime`)\n" .
            "VALUES (\n" .
            "    'Ellis2',\n" .
            "    FALSE,\n" .
            "    1.8,\n" .
            "    '2000-01-12',\n" .
            "    '2000-01-12 00:01:02'\n" .
            ');';

        $this->assertEquals($expected, $Statement->getComputed());

        $this->db->queryInsert($Statement);
    }

    // Date
    public function testDate(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

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
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

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
    public function testFloatNullable(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `dp` = %%;')->bFloatNullable('1.101', 1); // Making it one decimal point

        $expected = "SELECT *\n" .
            "FROM   `test`\n" .
            'WHERE  `dp` = 1.1;';

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            [
                'id' => '2',
                'name' => 'Hyacinth',
                'location' => 'Germany',
                'dp' => '1.1',
                'someDate' => '2000-01-02',
                'someDateTime' => '2000-01-02 00:01:02'
            ]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));

        $Statement->reset()
            ->sql('INSERT INTO `test` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis2')
            ->sql('    ?,')->bStr('USA')
            ->sql('    %%,')->bFloatNullable(null, 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');

        $expected = "INSERT INTO `test` (`name`, `location`, `dp`, `someDate`, `someDateTime`)\n" .
            "VALUES (\n" .
            "    'Ellis2',\n" .
            "    'USA',\n" .
            "    NULL,\n" .
            "    '2000-01-12',\n" .
            "    '2000-01-12 00:01:02'\n" .
            ');';

        $this->assertEquals($expected, $Statement->getComputed());
    }

    public function testFloat(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `dp` = %%;')->bFloat('1.101', 1); // Making it one decimal point

        $expected = "SELECT *\n" .
            "FROM   `test`\n" .
            'WHERE  `dp` = 1.1;';

        $this->assertEquals($expected, $Statement->getComputed());
    }

    // Int
    public function testIntNullable(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `id` = ?;')->bIntNullable('1');

        $expected = "SELECT *\n" .
            "FROM   `test`\n" .
            'WHERE  `id` = 1;';

        $this->assertEquals($expected, $Statement->getComputed());

        $Statement->reset()
            ->sql('INSERT INTO `%%` (`id`, `name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bIntNullable(null)
            ->sql('    ?,')->bStr('Ellis')
            ->sql('    ?,')->bStr('USA')
            ->sql('    %%,')->bFloat(8.10, 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');

        $expected = "INSERT INTO `test` (`id`, `name`, `location`, `dp`, `someDate`, `someDateTime`)\n" .
            "VALUES (\n" .
            "    NULL,\n" .
            "    'Ellis',\n" .
            "    'USA',\n" .
            "    8.1,\n" .
            "    '2000-01-12',\n" .
            "    '2000-01-12 00:01:02'\n" .
            ');';

        $this->assertEquals($expected, $Statement->getComputed());
    }

    public function testInt(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `id` = ?;')->bInt('1');

        $expected = "SELECT *\n" .
            "FROM   `test`\n" .
            'WHERE  `id` = 1;';

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            [
                'id' => '1',
                'name' => 'Davis',
                'location' => 'Germany',
                'dp' => '10.1',
                'someDate' => '2000-01-01',
                'someDateTime' => '2000-01-01 00:01:02'
            ]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // Int array
    public function testIntArray(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM `test`')
            ->sql('WHERE `id` IN (%%);')->bIntArray(array(1, 2, 3));

        $expected = "SELECT *\n" .
            "FROM `test`\n" .
            'WHERE `id` IN (1, 2, 3);';

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            [
                'id' => '1',
                'name' => 'Davis',
                'location' => 'Germany',
                'dp' => '10.1',
                'someDate' => '2000-01-01',
                'someDateTime' => '2000-01-01 00:01:02'
            ],
            [
                'id' => '2',
                'name' => 'Hyacinth',
                'location' => 'Germany',
                'dp' => '1.1',
                'someDate' => '2000-01-02',
                'someDateTime' => '2000-01-02 00:01:02'
            ],
            [
                'id' => '3',
                'name' => 'Quynn',
                'location' => 'USA',
                'dp' => '5.2',
                'someDate' => '2000-01-03',
                'someDateTime' => '2000-01-03 00:01:02'
            ]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // JSON
    public function testJSON(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis')
            ->sql('    ?,')->bJSON('{"a":123}')
            ->sql('    %%,')->bFloat(8.10, 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');

        $expected = "INSERT INTO `test` (`name`, `location`, `dp`, `someDate`, `someDateTime`)\n" .
            "VALUES (\n" .
            "    'Ellis',\n" .
            "    '{\"a\":123}',\n" .
            "    8.1,\n" .
            "    '2000-01-12',\n" .
            "    '2000-01-12 00:01:02'\n" .
            ');';

        $this->assertEquals($expected, $Statement->getComputed());

        $object = new stdClass();
        $object->a = 123;

        $Statement->reset()
            ->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis')
            ->sql('    ?,')->bJSON($object)
            ->sql('    %%,')->bFloat(8.10, 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');

        $this->assertEquals($expected, $Statement->getComputed());
    }

    public function testJSONNull(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis')
            ->sql('    ?,')->bJSON(null, true)
            ->sql('    %%,')->bFloat(8.10, 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');

        $expected = "INSERT INTO `test` (`name`, `location`, `dp`, `someDate`, `someDateTime`)\n" .
            "VALUES (\n" .
            "    'Ellis',\n" .
            "    NULL,\n" .
            "    8.1,\n" .
            "    '2000-01-12',\n" .
            "    '2000-01-12 00:01:02'\n" .
            ');';

        $this->assertEquals($expected, $Statement->getComputed());
    }

    // Like
    public function testLikeBeginsWith(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` LIKE ?;')->bLike('dr', false, true);

        $expected = "SELECT *\n" .
            "FROM   `test`\n" .
            "WHERE  `name` LIKE 'dr%';";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            [
                'id' => '10',
                'name' => 'Drew',
                'location' => 'Burundi',
                'dp' => '-56.5',
                'someDate' => '2000-01-10',
                'someDateTime' => '2000-01-10 00:01:02'
            ]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    public function testLikeEndsWith(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` LIKE ?;')->bLike('nn', true);

        $expected = "SELECT *\n" .
            "FROM   `test`\n" .
            "WHERE  `name` LIKE '%nn';";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            [
                'id' => '3',
                'name' => 'Quynn',
                'location' => 'USA',
                'dp' => '5.2',
                'someDate' => '2000-01-03',
                'someDateTime' => '2000-01-03 00:01:02'
            ]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    public function testLikeSomewhere(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` LIKE ?;')->bLike('li');

        $expected = "SELECT *\n" .
            "FROM   `test`\n" .
            "WHERE  `name` LIKE '%li%';";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            [
                'id' => '4',
                'name' => 'Julian',
                'location' => 'USA',
                'dp' => '2.0',
                'someDate' => '2000-01-04',
                'someDateTime' => '2000-01-04 00:01:02'
            ]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    public function testLikeNowhere(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

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
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

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
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` = ?;')->bStr('Sarah');

        $expected = "SELECT *\n" .
            "FROM   `test`\n" .
            "WHERE  `name` = 'Sarah';";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            [
                'id' => '8',
                'name' => 'Sarah',
                'location' => 'Christmas Island',
                'dp' => '-10.5',
                'someDate' => '2000-01-08',
                'someDateTime' => '2000-01-08 00:01:02'
            ]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // String Array
    public function testStringArray(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `location` IN (%%);')->bStrArr(['Germany', 'USA']);

        $expected = "SELECT *\n" .
            "FROM   `test`\n" .
            "WHERE  `location` IN ('Germany', 'USA');";

        $this->assertEquals($expected, $Statement->getComputed());

        $expected = [
            [
                'id' => '1',
                'name' => 'Davis',
                'location' => 'Germany',
                'dp' => '10.1',
                'someDate' => '2000-01-01',
                'someDateTime' => '2000-01-01 00:01:02'
            ],
            [
                'id' => '2',
                'name' => 'Hyacinth',
                'location' => 'Germany',
                'dp' => '1.1',
                'someDate' => '2000-01-02',
                'someDateTime' => '2000-01-02 00:01:02'
            ],
            [
                'id' => '3',
                'name' => 'Quynn',
                'location' => 'USA',
                'dp' => '5.2',
                'someDate' => '2000-01-03',
                'someDateTime' => '2000-01-03 00:01:02'
            ],
            [
                'id' => '4',
                'name' => 'Julian',
                'location' => 'USA',
                'dp' => '2.0',
                'someDate' => '2000-01-04',
                'someDateTime' => '2000-01-04 00:01:02'
            ]
        ];

        $this->assertEquals($expected, $this->db->selectRows($Statement));
    }

    // Insert
    public function testInsert(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis')
            ->sql('    ?,')->bStr('USA')
            ->sql('    %%,')->bFloat(8.5, 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');

        $expected = "INSERT INTO `test` (`name`, `location`, `dp`, `someDate`, `someDateTime`)\n" .
            "VALUES (\n" .
            "    'Ellis',\n" .
            "    'USA',\n" .
            "    8.5,\n" .
            "    '2000-01-12',\n" .
            "    '2000-01-12 00:01:02'\n" .
            ');';

        $this->assertEquals($expected, $Statement->getComputed());

        $this->assertEquals(11, $this->db->queryInsert($Statement), 'Insert statement did not return id of 11');
    }

    // Update
    public function testUpdate(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

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
    public function testSelectValueCaseInsensitive(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` = \'Drew\';');
        $this->assertEquals('Drew', $this->db->selectValue($Statement, 'NAMe'));
    }

    public function testSelectValueCaseSensitive(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` = \'Drew\';');
        $this->assertNotEquals('Drew', $this->db->selectValue($Statement, 'NAMe', true));
        $this->assertEquals('Drew', $this->db->selectValue($Statement, 'name', true));
    }

    public function testSelectBadPlaceHolders(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` = :pos999')
            ->sql('AND `location` = :raw999;');

        $expected = "SELECT *\n" .
            "FROM   `test`\n" .
            "WHERE  `name` = :pos999\n" .
            'AND `location` = :raw999;';

        $this->assertSame($expected, $Statement->getComputed());
    }

    // Delete
    public function testDelete(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());
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

    public function testToString(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('UPDATE `test`')
            ->sql('SET    `location` = ?')->bStr('Mexico')
            ->sql('WHERE  `name`     = ?;')->bStr('Drew');

        $expected = "UPDATE `test`\n" .
            "SET    `location` = 'Mexico'\n" .
            "WHERE  `name`     = 'Drew';";

        $this->assertEquals($expected, (string) $Statement);
    }

    public function testDebugInfo(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('UPDATE `test`')
            ->sql('SET    `location` = ?')->bStr('Mexico')
            ->sql('WHERE  `name`     = ?;')->bStr('Drew');

        $expected = [
            'Named Positions' => [
                ':pos0' => [
                    'type' => 2,
                    'value' => 'Mexico'
                ],
                ':pos1' => [
                    'type' => 2,
                    'value' => 'Drew'
                ]
            ],
            'Unbound SQL' => [
                'UPDATE `test`',
                'SET    `location` = :pos0',
                'WHERE  `name`     = :pos1;'
            ],
            'Bound SQL' => "UPDATE `test`\n" .
                "SET    `location` = 'Mexico'\n" .
                "WHERE  `name`     = 'Drew';"
        ];

        $this->assertSame($expected, $Statement->__debugInfo());
    }

    // Injection Test
    public function testInjection(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` = ?;')->bStr("1' OR 1; --'");

        $this->assertEmpty($this->db->selectRows($Statement));
    }

    // Good transaction
    public function testGoodTransaction(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis2')
            ->sql('    ?,')->bStr('USA')
            ->sql('    %%,')->bFloat('1.8', 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');

        $this->db->beginTransaction();

        $result = $this->db->queryInsert($Statement);

        $this->assertTrue($this->db->inTransaction());
        $this->db->commit();
        $this->assertEquals(11, $result, 'Insert statement did not return id of 11');
    }

    public function testRollback(): void
    {
        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis2')
            ->sql('    ?,')->bStr('USA')
            ->sql('    %%,')->bFloat('1.8', 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');

        $this->db->beginTransaction();
        $this->db->queryInsert($Statement);
        $this->assertTrue($this->db->inTransaction());
        $this->db->rollback();

        $Statement->reset()
            ->sql('SELECT `id`')
            ->sql('FROM `test`')
            ->sql('WHERE `name` = ?')->bStr('Ellis2')
            ->sql('LIMIT 1;');

        $result = $this->db->selectRow($Statement);

        $this->assertEmpty($result);
    }

    // Exception Tests
    public function testIntNullException(): void
    {
        $this->expectException(TypeError::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `id` = ?;')->bInt(null);
        $this->db->selectRows($Statement);
    }

    public function testStringNullException(): void
    {
        $this->expectException(Exception::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` = ?;')->bStr(null);
        $this->db->selectRows($Statement);

    }

    public function testBoolNullException(): void
    {
        $this->expectException(TypeError::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` = ?;')->bBool(null);
        $this->db->selectRows($Statement);
    }

    public function testBoolIntNullException(): void
    {
        $this->expectException(TypeError::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` = ?;')->bBoolInt(null);
        $this->db->selectRows($Statement);
    }

    public function testFloatNullException(): void
    {
        $this->expectException(TypeError::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` = %%;')->bFloat();
        $this->db->selectRows($Statement);
    }

    public function testDateNullException(): void
    {
        $this->expectException(\TypeError::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `someDate` = ?;')->bDate(null);
        $this->db->selectRows($Statement);
    }

    public function testDateNullPDOException(): void
    {
        $this->expectException(Exception::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis2')
            ->sql('    ?,')->bStr('USA')
            ->sql('    %%,')->bFloat('1.8', 1)
            ->sql('    ?,')->bDateNullable()
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');
        $this->db->queryInsert($Statement);
    }

    public function testDateTimeNullException(): void
    {
        $this->expectException(\TypeError::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `someDate` = ?;')->bDateTime(null);
        $this->db->selectRows($Statement);
    }

    public function testDateTimeNullPDOException(): void
    {
        $this->expectException(Exception::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis2')
            ->sql('    ?,')->bStr('USA')
            ->sql('    %%,')->bFloat('1.8', 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTimeNullable()
            ->sql(');');
        $this->db->queryInsert($Statement);
    }

    public function testIntArrayEmptyArrayException(): void
    {
        $this->expectException(Exception::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` IN (%%);')->bIntArray(array());
        $this->db->selectRows($Statement);
    }

    public function testFloatInvalidTypeException(): void
    {
        $this->expectException(TypeError::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` = %%;')->bFloat('xyz');
        $this->db->selectRows($Statement);
    }

    public function testIntInvalidTypeException(): void
    {
        $this->expectException(TypeError::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('SELECT *')
            ->sql('FROM   `test`')
            ->sql('WHERE  `name` = ?;')->bInt('xyz');
        $this->db->selectRows($Statement);
    }

    public function testJSONNullException(): void
    {
        $this->expectException(Exception::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis')
            ->sql('    ?,')->bJSON(null)
            ->sql('    %%,')->bFloat(8.10, 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');
    }

    public function testBadJSONException(): void
    {
        $this->expectException(JsonException::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis')
            ->sql('    ?,')->bJSON('SDI')
            ->sql('    %%,')->bFloat(8.10, 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');
    }

    public function testBadJSONException2(): void
    {
        $this->expectException(JsonException::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`, `someDateTime`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis')
            ->sql('    ?,')->bJSON(123)
            ->sql('    %%,')->bFloat(8.10, 1)
            ->sql('    ?,')->bDate('2000-01-12')
            ->sql('    ?')->bDateTime('2000-01-12 00:01:02')
            ->sql(');');
    }

    public function testBadTransaction(): void
    {
        $this->expectException(Exception::class);

        $Statement = new GLPDO2\Statement(GLPDO2\Bindings\MySQL\MySQLBindingFactory::build());

        $Statement->sql('INSERT INTO `%%` (`name`, `location`, `dp`, `someDate`)')->bRaw('test')
            ->sql('VALUES (')
            ->sql('    ?,')->bStr('Ellis2')
            ->sql('    ?,')->bStr('USA')
            ->sql('    ?,')->bStr(null, true)
            ->sql('    ?')->bDate('2000-01-12')
            ->sql(');');
        $this->db->beginTransaction();
        $this->db->queryInsert($Statement);
    }
}
