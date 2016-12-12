<?php

require_once 'WordsComparator.php';

// 'abcea', 'eabc'
// 'aaabaaa', 'aabaaaa'
// 'abcd', 'abcdcdab'
$result = WordsComparator::compare('yvolohov@gmail.com', 'volokhov.yaroslav@pdffiller.team');
echo $result, PHP_EOL;


// 'yvolohov@gmail.com', 'volokhov.yaroslav@pdffiller.team'

// *volo*hov*a*il*.*m
// [y|]volo[|k]hov[@gm|.yarosl]a[|v@pdff]il[|ler].[co|tea]m
