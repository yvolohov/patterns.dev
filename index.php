<?php

require_once 'WordsComparator.php';

$result = WordsComparator::compare('volokhov.yaroslav@pdffiller.team', 'yvolohov@gmail.com');
echo $result, PHP_EOL;


// 'yvolohov@gmail.com', 'volokhov.yaroslav@pdffiller.team'

// *volo*hov*a*il*.*m
// [y|]volo[|k]hov[@gm|.yarosl]a[|v@pdff]il[|ler].[co|tea]m
