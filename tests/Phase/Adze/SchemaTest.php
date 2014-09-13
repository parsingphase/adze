<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 11/09/14
 * Time: 13:42
 */

namespace Phase\Adze;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class SchemaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $dbFile;

    /**
     * @var Connection
     */
    protected $dbConnection;

    protected $schemaFile;

    protected function setUp()
    {
        $dbFileSource = dirname(dirname(__DIR__)) . '/resources/empty.sqlite';
        $dbFile = $dbFileSource . '.tmp';

        $copied = copy($dbFileSource, $dbFile);

        if (!$copied) {
            throw new \Exception("Failed to create working copy of empty.sqlite");
        }

        $this->dbFile = $dbFile;

        $params = [
            'driver' => 'pdo_sqlite',
            'path' => $dbFile
        ];

        $this->dbConnection = DriverManager::getConnection($params); //, $config);

        $schemaFile = dirname(dirname(dirname(__DIR__))) . '/schemas/tables.php';

        if (!file_exists($schemaFile)) {
            throw new \Exception('Schema file missing');
        }

        $this->schemaFile = $schemaFile;
        parent::setUp();
    }

    /**
     * Ensure that all tables in the schema file can be created
     */
    public function testSchemaFile()
    {
        $tables = require($this->schemaFile);
        $this->assertTrue(is_array($tables));
        $this->assertTrue(count($tables) > 0);

        $schemaManager = $this->dbConnection->getSchemaManager();

        $madeTables = [];

        foreach ($tables as $tableName => $table) {
            $schemaManager->createTable($table);
            $madeTables[] = $tableName;
        }

        $tablesPresent = $schemaManager->listTableNames();

        sort($tablesPresent);
        sort($madeTables);

        $this->assertEquals($tablesPresent, $madeTables);

    }
}
