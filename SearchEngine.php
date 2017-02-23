<?php

require_once 'WordsComparator.php';

class SearchEngine
{
    private $pdo = null;
    private $buffer = [];

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

            $wordBundle = [];
            $wordBundle['word'] = $currentWord;
            $wordBundle['pattern'] = $pattern;
            $wordBundle['symbols'] = $countOfSymbols;
            $wordBundle['gaps'] = $countOfGaps;
            $this->pushWord($wordBundle);
        }

        return $this->buffer;
    }

    private function pushWord($wordBundle)
    {
        $bufferLength = count($this->buffer);

        if ($bufferLength < 10) {
            $this->buffer[] = $wordBundle;
        }
        else {
            $bundle = $this->buffer[9];
            $result = $this->compareBundles($wordBundle, $bundle);

            if ($result) {
                $this->buffer[9] = $wordBundle;
            }
            else {
                return;
            }
        }

        /* -1 because index, -1 because we need to get previous and next elements */
        $maxIndex = count($this->buffer) - 2;

        for ($index = $maxIndex; $index >= 0; $index--) {
            $currentBundle = $this->buffer[$index];
            $previousBundle = $this->buffer[$index + 1];
            $result = $this->compareBundles($previousBundle, $currentBundle);

            if ($result) {
                $this->buffer[$index + 1] = $currentBundle;
                $this->buffer[$index] = $previousBundle;
            }
            else {
                return;
            }
        }
    }

    private function compareBundles($wordBundle, $bundle)
    {
        return $this->compareBundlesTwo($wordBundle, $bundle);
    }

    private function compareBundlesOne($wordBundle, $bundle)
    {
        return (($wordBundle['symbols'] > $bundle['symbols'])
            || ($wordBundle['symbols'] == $bundle['symbols'] && $wordBundle['gaps'] < $bundle['gaps']));
    }

    private function compareBundlesTwo($wordBundle, $bundle)
    {
        return (($wordBundle['symbols'] - $wordBundle['gaps']) > ($bundle['symbols'] - $bundle['gaps']));
    }

    public function __destruct()
    {
        $this->pdo = null;
    }
}