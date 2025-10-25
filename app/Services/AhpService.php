<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AhpService
{
    /**
     * Hitung bobot (eigenvector aproksimasi) dan CR dari matriks pairwise.
     * @param array $criteriaIds ordered list of criterion ids
     * @param array $matrix square matrix [n][n] dengan diagonal 1, reciprocal property
     * @return array [weights => [criterion_id=>weight], lambda_max, CI, CR]
     */
    public function computeWeights(array $criteriaIds, array $matrix): array
    {
        $n = count($criteriaIds);
        if ($n === 0) {
            return [
                'weights' => [],
                'lambda_max' => null,
                'CI' => null,
                'CR' => null,
            ];
        }

        // Geometric mean per row
        $geom = [];
        for ($i = 0; $i < $n; $i++) {
            $prod = 1.0;
            for ($j = 0; $j < $n; $j++) {
                $v = (float)($matrix[$i][$j] ?? 1.0);
                $prod *= $v > 0 ? $v : 1.0;
            }
            $geom[$i] = pow($prod, 1.0 / $n);
        }
        $sumGeom = array_sum($geom) ?: 1.0;
        $weightsVec = array_map(fn($g) => $g / $sumGeom, $geom);

        // lambda_max: mean of (A*w)/w
        $Aw = array_fill(0, $n, 0.0);
        for ($i = 0; $i < $n; $i++) {
            $rowSum = 0.0;
            for ($j = 0; $j < $n; $j++) {
                $rowSum += (float)$matrix[$i][$j] * $weightsVec[$j];
            }
            $Aw[$i] = $rowSum;
        }
        $ratios = [];
        for ($i = 0; $i < $n; $i++) {
            $ratios[$i] = $weightsVec[$i] > 0 ? ($Aw[$i] / $weightsVec[$i]) : 0.0;
        }
        $lambdaMax = array_sum($ratios) / $n;
        $CI = $n > 1 ? (($lambdaMax - $n) / ($n - 1)) : 0.0;
        $RI = $this->randomIndex($n);
        $CR = $RI > 0 ? ($CI / $RI) : 0.0;

        $weights = [];
        foreach ($criteriaIds as $idx => $cid) {
            $weights[$cid] = (float)$weightsVec[$idx];
        }

        return [
            'weights' => $weights,
            'lambda_max' => (float)$lambdaMax,
            'CI' => (float)$CI,
            'CR' => (float)$CR,
        ];
    }

    private function randomIndex(int $n): float
    {
        // Saat n > 15, RI ~ 1.59 (mendekati batas), referensi tabel RI Saaty
        $ri = [
            0 => 0.00,
            1 => 0.00,
            2 => 0.00,
            3 => 0.58,
            4 => 0.90,
            5 => 1.12,
            6 => 1.24,
            7 => 1.32,
            8 => 1.41,
            9 => 1.45,
            10 => 1.49,
            11 => 1.51,
            12 => 1.48,
            13 => 1.56,
            14 => 1.57,
            15 => 1.59,
        ];
        return $ri[$n] ?? 1.59;
    }
}
