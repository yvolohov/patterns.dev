<?php

class WordsComparatorTester
{
    const ENCODING = 'utf-8';

    public static function test($xString, $yString, $orderedVectors)
    {
        $fullMatrix = self::getFullMatrix($yString, $xString);
        $orderedMatrix = self::getOrderedMatrix($fullMatrix, $orderedVectors);

        self::showVectorsMatrix($fullMatrix);
        self::showVectorsMatrix($orderedMatrix);
    }

    private static function showVectorsMatrix($matrix)
    {
        $yLength = count($matrix);

        for ($yIndex = 0; $yIndex < $yLength; $yIndex++) {
            $xLength = count($matrix[$yIndex]);
            $row = '';

            for ($xIndex = 0; $xIndex < $xLength; $xIndex++) {
                $cell = $matrix[$yIndex][$xIndex];
                $row .= (!empty($cell)) ? ' ' . $cell . ' ' : '[ ]';
            }

            echo $row . PHP_EOL;
        }
        echo '----------------------' . PHP_EOL;
    }

    private static function getFullMatrix($yString, $xString)
    {
        $matrix = [];
        $yLength = mb_strlen($yString, self::ENCODING);
        $xLength = mb_strlen($xString, self::ENCODING);

        for ($yIndex = 0; $yIndex < $yLength; $yIndex++) {
            $yStringSymbol = $yString{$yIndex};

            for ($xIndex = 0; $xIndex < $xLength; $xIndex++) {
                $xStringSymbol = $xString{$xIndex};
                $matrix[$yIndex][$xIndex] = ($yStringSymbol === $xStringSymbol)
                    ? $yStringSymbol : '';
            }
        }
        return $matrix;
    }

    private static function getOrderedMatrix($fullMatrix, $orderedVectors)
    {
        $orderedMatrix = [];
        $yLength = count($fullMatrix);

        for ($yIndex = 0; $yIndex < $yLength; $yIndex++) {
            $xLength = count($fullMatrix[$yIndex]);

            for ($xIndex = 0; $xIndex < $xLength; $xIndex++) {
                $orderedMatrix [$yIndex][$xIndex] = '';
            }
        }

        foreach ($orderedVectors as $orderedVector) {

            for ($yIndex = $orderedVector['y_start'], $xIndex = $orderedVector['x_start'];
                 $yIndex <= $orderedVector['y_end'] || $xIndex <= $orderedVector['x_end'];
                 $yIndex++, $xIndex++) {
                $orderedMatrix[$yIndex][$xIndex] = $fullMatrix[$yIndex][$xIndex];
            }
        }
        return $orderedMatrix;
    }
}