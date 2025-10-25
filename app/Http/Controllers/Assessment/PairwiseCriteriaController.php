<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Criteria;
use App\Models\PairwiseCriteria;
use App\Models\Periods;
use App\Services\AhpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PairwiseCriteriaController extends Controller
{
    public function list(Periods $period)
    {
        $pairs = PairwiseCriteria::where('period_id', $period->id)
            ->get(['i_criterion_id as i_id','j_criterion_id as j_id','value']);
        return response()->json(['pairs' => $pairs]);
    }

    public function upsert(Request $request, Periods $period)
    {
        if ($period->status !== 'draft') {
            return response()->json(['message' => 'Step ini sudah terkunci'], 403);
        }

        $data = $request->validate([
            'pairs' => ['required','array','min:1'],
            'pairs.*.i_id' => ['required','uuid'],
            'pairs.*.j_id' => ['required','uuid','different:pairs.*.i_id'],
            'pairs.*.value' => ['required','numeric','gt:0'],
        ]);

        $criterionIds = Criteria::where('period_id', $period->id)->pluck('id')->all();
        $validSet = array_flip($criterionIds);

        DB::transaction(function () use ($data, $period, $validSet) {
            foreach ($data['pairs'] as $row) {
                $i = $row['i_id'];
                $j = $row['j_id'];
                $v = (float)$row['value'];
                if (!isset($validSet[$i]) || !isset($validSet[$j]) || $i === $j) {
                    continue;
                }
                PairwiseCriteria::updateOrCreate(
                    [
                        'period_id' => $period->id,
                        'i_criterion_id' => $i,
                        'j_criterion_id' => $j,
                    ],
                    [
                        'value' => $v,
                        'updated_by' => Auth::id(),
                        'updated_at' => now(),
                    ]
                );
                // Optional: bersihkan entry kebalikannya jika ada (server menjaga hanya i<j)
                if (strcmp($i, $j) > 0) {
                    PairwiseCriteria::where('period_id', $period->id)
                        ->where('i_criterion_id', $j)
                        ->where('j_criterion_id', $i)
                        ->delete();
                }
            }
        });

        return response()->json(['message' => 'Pairwise disimpan']);
    }

    public function calculate(Request $request, Periods $period, AhpService $ahp)
    {
        // Determine cluster scope
        $idsFilter = $request->input('ids');
        $criteriaQuery = Criteria::where('period_id', $period->id)->orderBy('order_index');
        if (is_array($idsFilter) && !empty($idsFilter)) {
            $criteriaQuery->whereIn('id', $idsFilter);
        }
        $criteria = $criteriaQuery->get(['id','name']);
        $n = $criteria->count();
        if ($n < 2) {
            return response()->json([
                'weights' => [], 'lambda_max' => null, 'CI' => null, 'CR' => null
            ]);
        }

        // Build matrix either from payload pairs or DB fallback
        $matrix = array_fill(0, $n, array_fill(0, $n, 1.0));
        $indexOf = [];
        foreach ($criteria as $idx => $c) { $indexOf[$c->id] = $idx; }

        $pairs = $request->input('pairs');
        if (is_array($pairs) && !empty($pairs)) {
            // Validate minimal: ids belong to period and i != j
            $validIds = array_flip($criteria->pluck('id')->all());
            foreach ($pairs as $row) {
                $iId = $row['i_id'] ?? null;
                $jId = $row['j_id'] ?? null;
                $val = isset($row['value']) ? (float)$row['value'] : null;
                if (!$iId || !$jId || $iId === $jId || !isset($validIds[$iId]) || !isset($validIds[$jId]) || !($val > 0)) {
                    continue;
                }
                $i = $indexOf[$iId];
                $j = $indexOf[$jId];
                $matrix[$i][$j] = $val;
                $matrix[$j][$i] = 1.0 / max($val, 1e-9);
            }
        } else {
            // Fallback to DB stored pairs
            $stored = PairwiseCriteria::where('period_id', $period->id)
                ->whereIn('i_criterion_id', $criteria->pluck('id'))
                ->whereIn('j_criterion_id', $criteria->pluck('id'))
                ->get();
            foreach ($stored as $p) {
                $i = $indexOf[$p->i_criterion_id] ?? null;
                $j = $indexOf[$p->j_criterion_id] ?? null;
                if ($i === null || $j === null || $i === $j) continue;
                $matrix[$i][$j] = (float)$p->value;
                $matrix[$j][$i] = 1.0 / max((float)$p->value, 1e-9);
            }
        }

        $result = $ahp->computeWeights($criteria->pluck('id')->all(), $matrix);
        // Format weights list with names
        $weightsList = [];
        foreach ($criteria as $idx => $c) {
            $weightsList[] = [
                'criterion_id' => $c->id,
                'name' => $c->name,
                'weight' => $result['weights'][$c->id] ?? 0.0,
            ];
        }

        return response()->json([
            'weights' => $weightsList,
            'lambda_max' => $result['lambda_max'],
            'CI' => $result['CI'],
            'CR' => $result['CR'],
        ]);
    }
}
