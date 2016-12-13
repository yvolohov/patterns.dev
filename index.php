<?php

require_once 'SearchEngine.php';

$host = '127.0.0.1';
$dbname = 'feldata';
$user = 'root';
$password = '';
$charset = 'utf8';
$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

$searchEngine = new SearchEngine($dsn, $user, $password);
$result = $searchEngine->search('cure');
print_r($result) . PHP_EOL;