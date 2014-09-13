#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 11/09/14
 * Time: 14:00
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

if (($argc !== 2) || ($argv[1] !== '--really')) {
    echo("$argv[0]:\nRun with --really to completely wipe and reset the database\n");
    die(1);
}

$configFile = dirname(__DIR__) . '/config/config.php';

if (!file_exists($configFile)) {
    echo("$argv[0]:\nCan't find ../config/config.php\n");
    die(2);
}

$schemaFile = dirname(__DIR__) . '/schemas/tables.php';

if (!file_exists($schemaFile)) {
    echo("$argv[0]:\nCan't find ../schemas/tables.php\n");
    die(2);
}

$appConfig = require($configFile);

$dbDefaults = [
    'driver' => 'pdo_mysql',
    'dbname' => null,
    'host' => 'localhost',
    'user' => 'root',
    'password' => null,
    'port' => null,
];

$dbConfig = $appConfig['db.options'] + $dbDefaults;
//print_r($dbConfig);

$tablesToBuild = require($schemaFile);
ksort($tablesToBuild);

$dbConnection = DriverManager::getConnection($dbConfig);
$schemaManager = $dbConnection->getSchemaManager();

$tablesPresent = $schemaManager->listTableNames();
sort($tablesPresent);

foreach ($tablesPresent as $tableName) {
    $schemaManager->dropTable($tableName);
    print("Removed $tableName\n");
}

foreach ($tablesToBuild as $tableName => $table) {
    if (!$schemaManager->tablesExist([$tableName])) {
        $schemaManager->createTable($table);
        print("Created $tableName\n");
    }
}

print("\nDatabase reset\n");
