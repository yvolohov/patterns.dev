<?php

require_once 'WordsComparator.php';

class SearchEngine
{
    private $pdo = null;

    public function __construct($dsn, $username='', $password='')
    {
        try {
            $this->pdo = new \PDO($dsn, $username, $password);
        }
        catch (\Exception $e) {
            exit($e->getMessage() . PHP_EOL);
        }
    }

    public function search($word)
    {
        $result = ['symbols' => 0, 'gaps' => PHP_INT_MAX, 'word' => '', 'pattern' => ''];
        $stmt = $this->pdo->prepare('SELECT LOWER(Word) Word FROM Words WHERE LanguageID = :language_id ORDER BY Word');
        $stmt->execute([':language_id' => 16]);

        while ($row = $stmt->fetch()) {
            $currentWord = $row['Word'];

            if ($currentWord == $word) {
                continue;
            }

            $pattern = WordsComparator::compare($word, $currentWord, true);
            $symbols = str_replace('*', '', $pattern);
            $totalCount = mb_strlen($pattern, WordsComparator::ENCODING);
            $countOfSymbols = mb_strlen($symbols, WordsComparator::ENCODING);
            $countOfGaps = $totalCount - $countOfSymbols;

            if (($countOfSymbols > $result['symbols'])
                || ($countOfSymbols == $result['symbols']
                    && $countOfGaps < $result['gaps'])) {
                $result['word'] = $currentWord;
                $result['pattern'] = $pattern;
                $result['symbols'] = $countOfSymbols;
                $result['gaps'] = $countOfGaps;
            }
        }

        return $result;
    }

    public function __destruct()
    {
        $this->pdo = null;
    }
}