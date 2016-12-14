<?php

require_once 'settings.php';
require_once 'SearchEngine.php';

$searchEngine = new SearchEngine($dsn, $user, $password);
$result = $searchEngine->search('president');
print_r($result) . PHP_EOL;