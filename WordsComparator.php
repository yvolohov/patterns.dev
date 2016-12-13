<?php

require_once 'WordsComparatorTester.php';

class WordsComparator
{
    const ENCODING = 'utf-8';

    const RELATION_TYPE_ABSORBING = 2;
    const RELATION_TYPE_CROSSING = 1;
    const RELATION_TYPE_INDEPENDENCE = 0;

    public static function compare($xString='', $yString='', $simpleView=False)
    {
        // Собираем векторы из точек пересечения
        $allVectors = self::getVectors($yString, $xString);

        // Пропускаем векторы через процедуру конкуренции
        $orderedVectors = self::orderCompetingVectors($allVectors);

        // Сортируем упорядоченные векторы в порядке их расположения
        $sorter = function($a, $b) {return $a['y_start'] - $b['y_start'];};
        usort($orderedVectors, $sorter);

        // Тестируем
        WordsComparatorTester::test($xString, $yString, $orderedVectors);

        // Строим и возвращаем шаблон
        return self::buildTemplate($yString, $xString, $orderedVectors, $simpleView);
    }

    private static function getVectors($yString, $xString)
    {
        $yLength = mb_strlen($yString, self::ENCODING);
        $xLength = mb_strlen($xString, self::ENCODING);
        $vectors = [];

        // Собираем векторы из точек
        for ($yIndex = 0; $yIndex < $yLength; $yIndex++) {
            $yStringSymbol = $yString{$yIndex};

            for ($xIndex = 0; $xIndex < $xLength; $xIndex++) {
                $xStringSymbol = $xString{$xIndex};

                if ($yStringSymbol !== $xStringSymbol) {
                    continue;
                }

                $previousKey = '_' . (string) ($yIndex - 1) . '_' . (string) ($xIndex - 1);
                $newKey = '_' . (string) $yIndex . '_' . (string) $xIndex;

                if (array_key_exists($previousKey, $vectors)) {
                    $vector = $vectors[$previousKey];
                    unset($vectors[$previousKey]);
                    $vector['y_end'] = $yIndex;
                    $vector['x_end'] = $xIndex;
                    $vector['length']++;
                    $vectors[$newKey] = $vector;
                }
                else {
                    $vectors[$newKey] = [
                        'y_start' => $yIndex,
                        'y_end' => $yIndex,
                        'x_start' => $xIndex,
                        'x_end' => $xIndex,
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
                    $orderedVector = $orderedVectors[$orderedVectorsIndex];
                    $vector = $vectors[$vectorsIndex];

                    /* но при этом он должен и по оси X и по оси Y находится
                     * с одной стороны от упорядоченного вектора (т.е. впереди либо сзади) */
                    if ((($vector['x_start'] > $orderedVector['x_start'])
                        && ($vector['y_start'] < $orderedVector['y_start']))
                        || (($vector['x_start'] < $orderedVector['x_start'])
                        && ($vector['y_start'] > $orderedVector['y_start'])))
                    {
                        /* в противном случае вектор вытесняется как неправильный */
                        $forcedOut = true;
                    }

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
        $yRelations = self::getRelationsOfVectorsByAxis(
            $orderedVector['y_start'],
            $orderedVector['y_end'],
            $vector['y_start'],
            $vector['y_end']
        );

        $xRelations = self::getRelationsOfVectorsByAxis(
            $orderedVector['x_start'],
            $orderedVector['x_end'],
            $vector['x_start'],
            $vector['x_end']
        );

        return ($yRelations['type'] > $xRelations['type']) ? $yRelations : $xRelations;
    }

    private static function getRelationsOfVectorsByAxis($orderedVectorStart, $orderedVectorEnd,
                                                        $vectorStart, $vectorEnd)
    {
        $relations = [
            'type' => self::RELATION_TYPE_INDEPENDENCE,
            'cut_from_left' => 0,
            'cut_from_right' => 0
        ];

        $pointIsInside = function($yPoint, $xPoint, $testPoint) {
            return ($testPoint >= $yPoint && $testPoint <= $xPoint);
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

    private static function buildTemplate($yString, $xString, $orderedVectors, $simpleView)
    {
        $result = '';
        $previousVector = $currentVector = Null;
        $maximalValue = count($orderedVectors) + 1;

        for ($counter = 1; $counter <= $maximalValue; $counter++) {
            $previousVector = $currentVector;
            $currentVector = ($counter < $maximalValue) ? $orderedVectors[$counter - 1] : Null;
            $spaceBetweenVectors = self::getSpaceBetweenVectors(
                $yString,
                $xString,
                $previousVector,
                $currentVector,
                $simpleView
            );
            $result .= $spaceBetweenVectors;

            if ($counter < $maximalValue) {
                $result .= substr($yString, $currentVector['y_start'], $currentVector['length']);
            }
        }

        return $result;
    }

    private static function getSpaceBetweenVectors($yString, $xString, $previousVector,
                                                   $currentVector, $simpleView)
    {
        if ($previousVector !== Null && $currentVector !== Null) {
            $spaceXStart = $previousVector['x_end'] + 1;
            $spaceXEnd = $currentVector['x_start'] - 1;
            $spaceYStart = $previousVector['y_end'] + 1;
            $spaceYEnd = $currentVector['y_start'] - 1;
        }
        elseif ($currentVector !== Null) {
            $spaceXStart = 0;
            $spaceXEnd = $currentVector['x_start'] - 1;
            $spaceYStart = 0;
            $spaceYEnd = $currentVector['y_start'] - 1;
        }
        elseif ($previousVector !== Null) {

        }

        return '*';
    }
}