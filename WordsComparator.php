<?php

require_once 'WordsComparatorTester.php';

class WordsComparator
{
    const ENCODING = 'utf-8';

    const RELATION_TYPE_ABSORBING = 2;
    const RELATION_TYPE_CROSSING = 1;
    const RELATION_TYPE_INDEPENDENCE = 0;

    public static function compare($firstString='', $secondString='', $simpleView=False)
    {
        // Собираем векторы из точек пересечения
        $allVectors = self::getVectors($firstString, $secondString);

        // Пропускаем векторы через процедуру конкуренции
        $orderedVectors = self::orderCompetingVectors($allVectors);

        // Сортируем упорядоченные векторы в порядке их расположения
        $sorter = function($a, $b) {return $a['y_start'] - $b['y_start'];};
        usort($orderedVectors, $sorter);

        // Тестируем
        WordsComparatorTester::test($firstString, $secondString, $orderedVectors);

        // Строим и возвращаем шаблон
        return self::buildTemplate($firstString, $secondString, $orderedVectors, $simpleView);
    }

    private static function buildTemplate($firstString, $secondString, $orderedVectors, $simpleView)
    {
        $result = '';
        $previousVector = $currentVector = Null;
        $maximalValue = count($orderedVectors) + 1;

        for ($counter = 1; $counter <= $maximalValue; $counter++) {
            $previousVector = $currentVector;
            $currentVector = ($counter < $maximalValue) ? $orderedVectors[$counter - 1] : Null;
            $spaceBetweenVectors = self::getSpaceBetweenVectors(
                $firstString,
                $secondString,
                $previousVector,
                $currentVector,
                $simpleView
            );
            $result .= $spaceBetweenVectors;

            if ($counter < $maximalValue) {
                $result .= substr($firstString, $currentVector['y_start'], $currentVector['length']);
            }
        }

        return $result;
    }

    private static function getSpaceBetweenVectors($firstString, $secondString, $previousVector,
                                                   $currentVector, $simpleView)
    {
        return ' ';
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
                    $vector['y_end'] = $firstIndex;
                    $vector['x_end'] = $secondIndex;
                    $vector['length']++;
                    $vectors[$newKey] = $vector;
                }
                else {
                    $vectors[$newKey] = [
                        'y_start' => $firstIndex,
                        'y_end' => $firstIndex,
                        'x_start' => $secondIndex,
                        'x_end' => $secondIndex,
                        'length' => 1
                    ];
                }
            }
        }

        return $vectors;
    }

    private static function orderCompetingVectors($vectors)
    {
        /* Сортируем векторы по убыванию их длины (поле 'length') */
        $sorter = function($a, $b) {return $b['length'] - $a['length'];};
        usort($vectors, $sorter);

        $orderedVectors = [];
        $vectorsCount = count($vectors);

        for ($vectorsIndex = 0; $vectorsIndex < $vectorsCount; $vectorsIndex++) {
            $forcedOut = false;
            $orderedVectorsCount = count($orderedVectors);

            /* Проверка вектора на вытеснение более сильными (длинными) векторами */
            for ($orderedVectorsIndex = 0; $orderedVectorsIndex < $orderedVectorsCount; $orderedVectorsIndex++) {
                $relations = self::getRelationsOfVectors($orderedVectors[$orderedVectorsIndex], $vectors[$vectorsIndex]);

                /* $orderedVector полностью перекрывает $vector, последний вытесняется */
                if ($relations['type'] == self::RELATION_TYPE_ABSORBING) {
                    $forcedOut = true;
                }

                /* $orderedVector и $vector частично пересекаются, поэтому перекрытую
                 * часть $vector нужно обрезать */
                elseif ($relations['type'] == self::RELATION_TYPE_CROSSING) {

                    if ($relations['cut_from_left'] > 0) {
                        $vectors[$vectorsIndex]['y_start'] += $relations['cut_from_left'];
                        $vectors[$vectorsIndex]['x_start'] += $relations['cut_from_left'];
                        $vectors[$vectorsIndex]['length'] -= $relations['cut_from_left'];
                    }
                    elseif ($relations['cut_from_right'] > 0) {
                        $vectors[$vectorsIndex]['y_end'] -= $relations['cut_from_right'];
                        $vectors[$vectorsIndex]['x_end'] -= $relations['cut_from_right'];
                        $vectors[$vectorsIndex]['length'] -= $relations['cut_from_right'];
                    }

                    /* после обрезки $vector нужно заново отсортировать $vectors по длине
                     * и сдвинуть счетчик назад, чтобы заново пройти данный виток цикла */
                    usort($vectors, $sorter);
                    $vectorsIndex--;
                    $forcedOut = true;
                }

                /* если нет ни поглощения ни пересечения, вектор может быть добавлен в упорядоченные */
                else {
                    

                }
            }

            if ($forcedOut) {
                continue;
            }

            $orderedVectors[] = $vectors[$vectorsIndex];
        }

        return $orderedVectors;
    }

    private static function getRelationsOfVectors($orderedVector, $vector)
    {
        $firstRelations = self::getRelationsOfVectorsByAxis(
            $orderedVector['y_start'],
            $orderedVector['y_end'],
            $vector['y_start'],
            $vector['y_end']
        );

        $secondRelations = self::getRelationsOfVectorsByAxis(
            $orderedVector['x_start'],
            $orderedVector['x_end'],
            $vector['x_start'],
            $vector['x_end']
        );

        return ($firstRelations['type'] > $secondRelations['type']) ? $firstRelations : $secondRelations;
    }

    private static function getRelationsOfVectorsByAxis($orderedVectorStart, $orderedVectorEnd,
                                                        $vectorStart, $vectorEnd)
    {
        $relations = [
            'type' => self::RELATION_TYPE_INDEPENDENCE,
            'cut_from_left' => 0,
            'cut_from_right' => 0
        ];

        $pointIsInside = function($firstPoint, $secondPoint, $testPoint) {
            return ($testPoint >= $firstPoint && $testPoint <= $secondPoint);
        };

        $vectorStartIsInside = $pointIsInside($orderedVectorStart, $orderedVectorEnd, $vectorStart);
        $vectorEndIsInside = $pointIsInside($orderedVectorStart, $orderedVectorEnd, $vectorEnd);

        if ($vectorStartIsInside && $vectorEndIsInside) {
            $relations['type'] = self::RELATION_TYPE_ABSORBING;
        }
        elseif ($vectorStartIsInside) {
            $relations['type'] = self::RELATION_TYPE_CROSSING;
            $relations['cut_from_left'] = $orderedVectorEnd - $vectorStart + 1;
        }
        elseif ($vectorEndIsInside) {
            $relations['type'] = self::RELATION_TYPE_CROSSING;
            $relations['cut_from_right'] = $vectorEnd - $orderedVectorStart + 1;
        }

        return $relations;
    }
}