<?php

require_once 'WordsComparator.php';

// 'abcea', 'eabc'
// 'aaabaaa', 'aabaaaa'
// 'abcd', 'abcdcdab'
WordsComparator::compare('abcea', 'eabc'); // пересечение по обеим осям
