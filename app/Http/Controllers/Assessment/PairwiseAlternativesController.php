<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Candidates;
use App\Models\Criteria;
use App\Models\PairwiseAlternatives;
use App\Models\Periods;
use App\Services\AhpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PairwiseAlternativesController extends Controller
{
    public function list(Request $request, Periods $period)
    {
        $criterionId = (string) $request->query('criterion_id', '');
        if (!$criterionId) {
            return response()->json(['pairs' => []]);
        }
        $pairs = PairwiseAlternatives::where('period_id', $period->id)
            ->where('criterion_id', $criterionId)
            ->get(['i_candidate_id as i_id','j_candidate_id as j_id','value']);
        return response()->json(['pairs' => $pairs]);
    }

    public function upsert(Request $request, Periods $period)
    {
        if ($period->status !== 'draft') {
            return response()->json(['message' => 'Step ini sudah terkunci'], 403);
        }
        $data = $request->validate([
            'criterion_id' => ['required','uuid'],
            'pairs' => ['required','array','min:1'],
            'pairs.*.i_id' => ['required','uuid'],
            'pairs.*.j_id' => ['required','uuid','different:pairs.*.i_id'],
            'pairs.*.value' => ['required','numeric','gt:0'],
        ]);

        $validCandidates = Candidates::where('period_id', $period->id)->pluck('id')->all();
        $validSet = array_flip($validCandidates);
        $criterionExists = Criteria::where('period_id', $period->id)->where('id', $data['criterion_id'])->exists();
        if (!$criterionExists) {
            return response()->json(['message' => 'Kriteria tidak valid'], 422);
        }

        DB::transaction(function () use ($data, $period, $validSet) {
            foreach ($data['pairs'] as $row) {
                $i = $row['i_id'];
                $j = $row['j_id'];
                $v = (float)$row['value'];
                if (!isset($validSet[$i]) || !isset($validSet[$j]) || $i === $j) {
                    continue;
                }
                PairwiseAlternatives::updateOrCreate(
                    [
                        'period_id' => $period->id,
                        'criterion_id' => $data['criterion_id'],
                        'i_candidate_id' => $i,
                        'j_candidate_id' => $j,
                    ],
                    [
                        'value' => $v,
                        'updated_by' => Auth::id(),
                        'updated_at' => now(),
                    ]
                );
                if (strcmp($i, $j) > 0) {
                    PairwiseAlternatives::where('period_id', $period->id)
                        ->where('criterion_id', $data['criterion_id'])
                        ->where('i_candidate_id', $j)
                        ->where('j_candidate_id', $i)
                        ->delete();
                }
            }
        });

        return response()->json(['message' => 'Pairwise disimpan']);
    }

    public function calculate(Request $request, Periods $period, AhpService $ahp)
    {
        $criterionId = (string) $request->input('criterion_id', '');
        if (!$criterionId) {
            return response()->json(['weights' => [], 'lambda_max' => null, 'CI' => null, 'CR' => null]);
        }
        // Candidates in this period
        $cands = Candidates::where('period_id', $period->id)
            ->join('students', 'students.id', '=', 'candidates.student_id')
            ->orderBy('students.name')
            ->get(['candidates.id','students.name','students.nis','students.class']);
        $n = $cands->count();
        if ($n < 2) {
            return response()->json(['weights' => [], 'lambda_max' => null, 'CI' => null, 'CR' => null]);
        }
        $matrix = array_fill(0, $n, array_fill(0, $n, 1.0));
        $indexOf = [];
        foreach ($cands as $idx => $c) { $indexOf[$c->id] = $idx; }

        $pairs = $request->input('pairs');
        if (is_array($pairs) && !empty($pairs)) {
            $validIds = array_flip($cands->pluck('id')->all());
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
            $stored = PairwiseAlternatives::where('period_id', $period->id)
                ->where('criterion_id', $criterionId)
                ->get();
            foreach ($stored as $p) {
                $i = $indexOf[$p->i_candidate_id] ?? null;
                $j = $indexOf[$p->j_candidate_id] ?? null;
                if ($i === null || $j === null || $i === $j) continue;
                $matrix[$i][$j] = (float)$p->value;
                $matrix[$j][$i] = 1.0 / max((float)$p->value, 1e-9);
            }
        }

        $candIds = $cands->pluck('id')->all();
        $result = $ahp->computeWeights($candIds, $matrix);
        $weightsList = [];
        foreach ($cands as $idx => $c) {
            $weightsList[] = [
                'candidate_id' => $c->id,
                'name' => $c->name,
                'nis' => $c->nis,
                'class' => $c->class,
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
