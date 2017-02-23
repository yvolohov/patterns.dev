<?php

$argvCount = count($argv);

if ($argvCount < 2) {
    exit;
}

$variator = '#' . $argv[1] . '{';

for ($index = 2; $index < $argvCount; $index++) {
    $variator .= $argv[$index];
    $variator .= (($argvCount - $index) > 1) ? '|' : '';
}

$variator .= '}';

echo $variator . PHP_EOL;