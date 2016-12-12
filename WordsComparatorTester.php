<?php

class WordsComparatorTester
{
    const ENCODING = 'utf-8';

    public static function test($firstString, $secondString, $orderedVectors)
    {
        $fullMatrix = self::getFullMatrix($firstString, $secondString);
        $orderedMatrix = self::getOrderedMatrix($fullMatrix, $orderedVectors);

        self::showVectorsMatrix($fullMatrix);
        self::showVectorsMatrix($orderedMatrix);
    }

    private static function showVectorsMatrix($matrix)
    {
        $firstLength = count($matrix);

        for ($firstIndex = 0; $firstIndex < $firstLength; $firstIndex++) {
            $secondLength = count($matrix[$firstIndex]);
            $row = '';

            for ($secondIndex = 0; $secondIndex < $secondLength; $secondIndex++) {
                $cell = $matrix[$firstIndex][$secondIndex];
                $row .= (!empty($cell)) ? ' ' . $cell . ' ' : '[ ]';
            }

            echo $row . PHP_EOL;
        }
        echo '----------------------' . PHP_EOL;
    }

    private static function getFullMatrix($firstString, $secondString)
    {
        $matrix = [];
        $firstLength = mb_strlen($firstString, self::ENCODING);
        $secondLength = mb_strlen($secondString, self::ENCODING);

        for ($firstIndex = 0; $firstIndex < $firstLength; $firstIndex++) {
            $firstStringSymbol = $firstString{$firstIndex};

            for ($secondIndex = 0; $secondIndex < $secondLength; $secondIndex++) {
                $secondStringSymbol = $secondString{$secondIndex};
                $matrix[$firstIndex][$secondIndex] = ($firstStringSymbol === $secondStringSymbol)
                    ? $firstStringSymbol : '';
            }
        }
        return $matrix;
    }

    private static function getOrderedMatrix($fullMatrix, $orderedVectors)
    {
        $orderedMatrix = [];
        $firstLength = count($fullMatrix);

        for ($firstIndex = 0; $firstIndex < $firstLength; $firstIndex++) {
            $secondLength = count($fullMatrix[$firstIndex]);

            for ($secondIndex = 0; $secondIndex < $secondLength; $secondIndex++) {
                $orderedMatrix [$firstIndex][$secondIndex] = '';
            }
        }

        foreach ($orderedVectors as $orderedVector) {

            for ($firstIndex = $orderedVector['y_start'], $secondIndex = $orderedVector['x_start'];
                 $firstIndex <= $orderedVector['y_end'] || $secondIndex <= $orderedVector['x_end'];
                 $firstIndex++, $secondIndex++) {
                $orderedMatrix[$firstIndex][$secondIndex] = $fullMatrix[$firstIndex][$secondIndex];
            }
        }
        return $orderedMatrix;
    }
}