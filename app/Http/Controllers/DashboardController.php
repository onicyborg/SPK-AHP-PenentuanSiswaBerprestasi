<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Periods;
use App\Models\Criteria;
use App\Models\Candidates;
use App\Models\Scores;
use App\Models\Weights;
use App\Models\Results;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = Periods::orderBy('created_at', 'desc')->first();

        if (!$period) {
            return view('welcome', [
                'period' => null,
                'summary' => [
                    'criteria_leaf' => 0,
                    'candidates' => 0,
                    'expected' => 0,
                    'filled' => 0,
                    'progress_pct' => 0.0,
                    'avg_cr' => null,
                    'is_results_stale' => false,
                    'last_calculated_at' => null,
                ],
                'topResults' => collect(),
                'weightsRows' => collect(),
            ]);
        }

        $criteriaAll = Criteria::where('period_id', $period->id)->get(['id','parent_id','name','order_index']);
        $parentIds = Criteria::where('period_id', $period->id)
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->unique()
            ->filter()
            ->values();
        $leafIds = $criteriaAll->pluck('id')->diff($parentIds)->values();

        $candidates = Candidates::where('period_id', $period->id)->pluck('id');
        $critCount = $leafIds->count();
        $candCount = $candidates->count();
        $expected = $critCount * $candCount;
        $filled = 0;
        if ($expected > 0) {
            $filled = Scores::where('period_id', $period->id)
                ->when($leafIds->isNotEmpty(), fn($q) => $q->whereIn('criterion_id', $leafIds))
                ->when($candCount > 0, fn($q) => $q->whereIn('candidate_id', $candidates))
                ->whereNotNull('raw_value')
                ->count();
        }
        $progressPct = $expected > 0 ? round(($filled / $expected) * 100, 2) : 0.0;

        $avgCr = Weights::where('period_id', $period->id)
            ->whereNotNull('cr_at_level')
            ->avg('cr_at_level');

        $weightsRows = Weights::where('weights.period_id', $period->id)
            ->where('weights.level', 'criterion')
            ->leftJoin('criteria', function($j) use ($period){
                $j->on('criteria.id','=','weights.node_id')
                  ->where('criteria.period_id', $period->id);
            })
            ->when($leafIds->isNotEmpty(), fn($q) => $q->whereIn('weights.node_id', $leafIds))
            ->orderBy('criteria.order_index')
            ->limit(10)
            ->get([
                'weights.node_id as id',
                DB::raw("COALESCE(criteria.name, 'Unknown') as name"),
                'weights.weight'
            ]);

        $topResults = Results::where('results.period_id', $period->id)
            ->join('candidates','candidates.id','=','results.candidate_id')
            ->join('students','students.id','=','candidates.student_id')
            ->orderBy('results.rank')
            ->limit(5)
            ->get([
                'results.rank', 'results.final_score',
                'students.name as student_name', 'students.class as student_class'
            ]);

        $summary = [
            'criteria_leaf' => $critCount,
            'candidates' => $candCount,
            'expected' => $expected,
            'filled' => $filled,
            'progress_pct' => $progressPct,
            'avg_cr' => $avgCr !== null ? (float)$avgCr : null,
            'is_results_stale' => (bool) $period->is_results_stale,
            'last_calculated_at' => optional($period->last_calculated_at)->toDateTimeString(),
        ];

        return view('welcome', compact('period','summary','topResults','weightsRows'));
    }
}
