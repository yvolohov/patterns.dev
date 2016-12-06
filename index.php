<?php

require_once 'WordsComparator.php';

// 'abcea', 'eabc'
// 'aaabaaa', 'aabaaaa'
WordsComparator::compare('aaaaaaa', 'aaaaaaa'); // пересечение по обеим осям
