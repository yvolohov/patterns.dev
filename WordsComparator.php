<?php

class WordsComparator
{
    const ENCODING = 'utf-8';

    const RELATION_TYPE_ABSORBING = 2;
    const RELATION_TYPE_CROSSING = 1;
    const RELATION_TYPE_INDEPENDENCE = 0;

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
                    $vector['first_end'] = $firstIndex;
                    $vector['second_end'] = $secondIndex;
                    $vector['length']++;
                    $vectors[$newKey] = $vector;
                }
                else {
                    $vectors[$newKey] = [
                        'first_start' => $firstIndex,
                        'first_end' => $firstIndex,
                        'second_start' => $secondIndex,
                        'second_end' => $secondIndex,
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

    private static function getRelationsOfVectors($orderedVector, $vector)
    {
        $firstRelations = self::getRelationsOfVectorsByAxis(
            $orderedVector['first_start'],
            $orderedVector['first_end'],
            $vector['first_start'],
            $vector['first_end']
        );

        $secondRelations = self::getRelationsOfVectorsByAxis(
            $orderedVector['second_start'],
            $orderedVector['second_end'],
            $vector['second_start'],
            $vector['second_end']
        );

        $firstType = $firstRelations['type'];
        $secondType = $secondRelations['type'];
        $cutFromLeft = ($firstType >= $secondType) ? $firstRelations['cut_from_left'] : $secondRelations['cut_from_left'];
        $cutFromRight = ($firstType >= $secondType) ? $firstRelations['cut_from_right'] : $secondRelations['cut_from_right'];

        return [
            'type' => max($firstType, $secondType),
            'cut_from_left' => $cutFromLeft,
            'cut_from_right' => $cutFromRight
        ];
    }

    private static function getRelationsOfVectorsByAxis($orderedVectorStart, $orderedVectorEnd, $vectorStart, $vectorEnd)
    {
        $relations = [
            'type' => self::RELATION_TYPE_INDEPENDENCE,
            'cut_from_left' => 0,
            'cut_from_right' => 0
        ];

        if ($orderedVectorStart <= $vectorStart && $orderedVectorEnd >= $vectorEnd) {
            $relations['type'] = self::RELATION_TYPE_ABSORBING;
        }
        elseif ($orderedVectorStart > $vectorStart && $orderedVectorEnd >= $vectorEnd) {
            $relations['type'] = self::RELATION_TYPE_CROSSING;
            $relations['cut_from_right'] = $vectorEnd - $orderedVectorStart + 1;
        }
        elseif ($orderedVectorStart <= $vectorStart && $orderedVectorEnd < $vectorEnd) {
            $relations['type'] = self::RELATION_TYPE_CROSSING;
            $relations['cut_from_left'] = $orderedVectorEnd - $vectorStart + 1;
        }

        return $relations;
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