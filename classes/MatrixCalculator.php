<?php
class MatrixCalculator {

    // 1. การปรับค่าสเกลาร์ (Linear Scaling / Brightness)
    public static function linearScaling(array $pixels, float $alpha, float $beta): array {
        $result = [];
        foreach ($pixels as $pixel) {
            $r = min(255, max(0, $alpha * $pixel['r'] + $beta));
            $g = min(255, max(0, $alpha * $pixel['g'] + $beta));
            $b = min(255, max(0, $alpha * $pixel['b'] + $beta));
            $result[] = ['r' => $r, 'g' => $g, 'b' => $b];
        }
        return $result;
    }

    // 2. การคูณเมทริกซ์แปลงสี (Color Space Matrix Transformation: Grayscale, Sepia)
    public static function colorMatrixTransform(array $pixels, array $matrix): array {
        $result = [];
        foreach ($pixels as $p) {
            $r = min(255, max(0, $p['r'] * $matrix[0] + $p['g'] * $matrix[1] + $p['b'] * $matrix[2]));
            $g = min(255, max(0, $p['r'] * $matrix[3] + $p['g'] * $matrix[4] + $p['b'] * $matrix[5]));
            $b = min(255, max(0, $p['r'] * $matrix[6] + $p['g'] * $matrix[7] + $p['b'] * $matrix[8]));
            $result[] = ['r' => (int)$r, 'g' => (int)$g, 'b' => (int)$b];
        }
        return $result;
    }

    // 3. การวนคอนโวลูชันเมทริกซ์ 2 มิติ (2D Discrete Spatial Convolution)
    public static function convolution2D(array $imageGrid, int $width, int $height, array $kernel): array {
        $output = $imageGrid;
        for ($y = 1; $y < $height - 1; $y++) {
            for ($x = 1; $x < $width - 1; $x++) {
                $rSum = 0; $gSum = 0; $bSum = 0;
                $kIdx = 0;

                for ($ky = -1; $ky <= 1; $ky++) {
                    for ($kx = -1; $kx <= 1; $kx++) {
                        $p = $imageGrid[$y + $ky][$x + $kx];
                        $kVal = $kernel[$kIdx++];
                        $rSum += $p['r'] * $kVal;
                        $gSum += $p['g'] * $kVal;
                        $bSum += $p['b'] * $kVal;
                    }
                }

                $output[$y][$x] = [
                    'r' => min(255, max(0, (int)$rSum)),
                    'g' => min(255, max(0, (int)$gSum)),
                    'b' => min(255, max(0, (int)$bSum))
                ];
            }
        }
        return $output;
    }
}
?>