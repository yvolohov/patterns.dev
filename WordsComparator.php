<?php

class WordsComparator
{
    const ENCODING = 'utf-8';

    public static function compare($firstString='', $secondString='', $simpleView=False)
    {
        // Здесь будем хранить результат сравнения
        $result = '';

        // Собираем векторы из точек пересечения
        $allVectors = self::getVectors($firstString, $secondString);

        // Пропускаем векторы через процедуру конкуренции
        $orderedVectors = self::orderCompetingVectors($allVectors);

        // Показываем поле сравнения
        self::showVectors($firstString, $secondString);
    }

    private static function getVectors($firstString, $secondString)
    {
        $firstLength = mb_strlen($firstString, self::ENCODING);
        $secondLength = mb_strlen($secondString, self::ENCODING);
        $vectors = [];

        // Собираем векторы из точек
        for ($firstIndex = 0; $firstIndex < $firstLength; $firstIndex++) {
            $firstStringSymbol = $firstString{$firstIndex};

            for ($secondIndex = 0; $secondIndex < $secondLength; $secondIndex++) {
                $secondStringSymbol = $secondString{$secondIndex};

                if ($firstStringSymbol !== $secondStringSymbol) {
                    continue;
                }

                $previousKey = '_' . (string) ($firstIndex - 1) . '_' . (string) ($secondIndex - 1);
                $newKey = '_' . (string) $firstIndex . '_' . (string) $secondIndex;

                if (array_key_exists($previousKey, $vectors)) {
                    $vector = $vectors[$previousKey];
                    unset($vectors[$previousKey]);
                    $vector['end_first'] = $firstIndex;
                    $vector['end_second'] = $secondIndex;
                    $vector['length']++;
                    $vectors[$newKey] = $vector;
                }
                else {
                    $vectors[$newKey] = [
                        'start_first' => $firstIndex,
                        'start_second' => $secondIndex,
                        'end_first' => $firstIndex,
                        'end_second' => $secondIndex,
                        'length' => 1
                    ];
                }
            }
        }

        return $vectors;
    }

    private static function orderCompetingVectors($vectors)
    {
        // Сортируем векторы по убыванию их длины (поле 'length')
        $sorter = function($a, $b) {return $b['length'] - $a['length'];};
        usort($vectors, $sorter);

        $orderedVectors = [];
        $vectorsCount = count($vectors);

        for ($vectorsIndex = 0; $vectorsIndex < $vectorsCount; $vectorsIndex++) {
            $currentVector = $vectors[$vectorsIndex];
            $forcedOut = false;
            $orderedVectorsCount = count($orderedVectors);

            // Проверка вектора на вытеснение более сильными векторами
            for ($orderedVectorsIndex = 0; $orderedVectorsIndex < $orderedVectorsCount; $orderedVectorsIndex++) {
                $orderedVector = $orderedVectors[$orderedVectorsIndex];

            }

            if ($forcedOut) {
                continue;
            }

            // Добавление вектора в упорядоченные
            $orderedVectors[] = $currentVector;
        }

        return $orderedVectors;
    }

    // test function (delete after the end of developing)
    private static function showVectors($firstString, $secondString)
    {
        echo '------------------' . PHP_EOL;
        $firstLength = mb_strlen($firstString, self::ENCODING);
        $secondLength = mb_strlen($secondString, self::ENCODING);

        for ($firstIndex = 0; $firstIndex < $firstLength; $firstIndex++) {
            $firstStringSymbol = $firstString{$firstIndex};
            $row = '';

            for ($secondIndex = 0; $secondIndex < $secondLength; $secondIndex++) {
                $secondStringSymbol = $secondString{$secondIndex};
                $row .= ($firstStringSymbol === $secondStringSymbol) ? '[' . $firstStringSymbol . ']' : '[.]';
            }
            echo $row . PHP_EOL;
        }
    }
}