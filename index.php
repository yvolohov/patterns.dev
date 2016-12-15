<?php

require_once 'settings.php';
require_once 'SearchEngine.php';

$searchEngine = new SearchEngine($dsn, $user, $password);
$result = $searchEngine->search('teacher');
print_r($result) . PHP_EOL;