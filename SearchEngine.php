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
        $stmt = $this->pdo->prepare('SELECT LOWER(Word) Word FROM Words WHERE LanguageID = :language_id ORDER BY Word');
        $stmt->execute([':language_id' => 16]);

        while ($row = $stmt->fetch()) {
            echo $row['Word'] . PHP_EOL;
        }

        return [];
    }

    public function __destruct()
    {
        $this->pdo = null;
    }
}