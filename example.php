<?php
error_reporting(E_ALL);

use Albandes\db;
use Albandes\audit;
use Albandes\services;

require("vendor/autoload.php");

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required('DEBUG')->isBoolean();

date_default_timezone_set($_ENV['TIME_ZONE']);
$debug = filter_var($_ENV['DEBUG'], FILTER_VALIDATE_BOOLEAN) ;


// Connect database
try{
    $dsn = "mysql:dbname={$_ENV['DB_NAME']};port={$_ENV['DB_PORT']};host={$_ENV['DB_HOSTNAME']}";
    $db = new DB($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);   
}catch(\PDOException $e){
    die("<br>Error connecting to database: " . $e->getMessage() . " File: " . __FILE__ . " Line: " . __LINE__ );
}

$db = new db($_ENV['DB_HOSTNAME'],$_ENV['DB_NAME'],$_ENV['DB_PORT'],$_ENV['DB_USERNAME'],$_ENV['DB_PASSWORD'],$_ENV['DB_CHARSET']);
//$pdo = $db->getConnection();
//$audit = new audit($pdo);

$audit = new audit($db);
$audit->set_debug($_ENV['DEBUG']);

//$action = "INSERT";
//$action = "UPDATE";
$action = "DELETE";

$table = "people" ;

$trigger = $audit->createTrigger($table,$action);
$ret = $audit->dropTrigger($table,$action);
$ret = $audit->insertTrigger($trigger);

die($ret);




