<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Periods;
use App\Models\Criteria;
use App\Models\Candidates;
use App\Models\Scores;
use App\Models\Weights;
use App\Models\NormalizationStats;
use App\Models\Results;
use App\Models\ResultsBreakdown;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\AhpService;

class ScoreController extends Controller
{
    public function index(Request $request, Periods $period)
    {
        $this->guardViewableStates($period);
        $criterionId = (string) $request->query('criterion_id', '');
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);

        if ($criterionId === '') {
            return response()->json(['data'=>[], 'current_page'=>1, 'per_page'=>$perPage, 'total'=>0, 'last_page'=>0]);
        }

        $query = Candidates::where('candidates.period_id', $period->id)
            ->join('students', 'students.id', '=', 'candidates.student_id')
            ->leftJoin('scores', function($j) use ($period, $criterionId) {
                $j->on('scores.candidate_id','=','candidates.id')
                  ->where('scores.period_id', $period->id)
                  ->where('scores.criterion_id', $criterionId);
            })
            ->select([
                'candidates.id as candidate_id',
                'students.nis', 'students.name', 'students.class',
                DB::raw('scores.raw_value as raw_value')
            ]);

        if ($search !== '') {
            $query->where(function($w) use ($search){
                $w->where('students.name','like',"%{$search}%")
                  ->orWhere('students.nis','like',"%{$search}%");
            });
        }

        $page = $query->orderBy('students.name')->paginate($perPage);
        return response()->json([
            'data' => $page->items(),
            'current_page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'last_page' => $page->lastPage(),
        ]);
    }

    public function criteriaList(Request $request, Periods $period)
    {
        $this->guardViewableStates($period);
        $all = Criteria::where('period_id', $period->id)
            ->orderBy('order_index')
            ->get(['id','name','type','parent_id','order_index']);
        $parentIds = Criteria::where('period_id', $period->id)
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->unique()
            ->filter()
            ->values();
        $data = $all->map(function($c) use ($parentIds){
            return [
                'id' => (string)$c->id,
                'name' => $c->name,
                'type' => $c->type,
                'parent_id' => $c->parent_id,
                'is_parent' => $parentIds->contains($c->id),
                'is_leaf' => !$parentIds->contains($c->id),
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function childrenStats(Request $request, Periods $period, string $parentId)
    {
        $this->guardViewableStates($period);
        // Ambil LEAF child langsung di bawah parentId
        $parentIds = Criteria::where('period_id', $period->id)
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->unique()
            ->filter()
            ->values();
        $leafChildren = Criteria::where('period_id', $period->id)
            ->where('parent_id', $parentId)
            ->whereNotIn('id', $parentIds)
            ->orderBy('order_index')
            ->get(['id','name','type']);
        if ($leafChildren->isEmpty()) {
            return response()->json(['data' => []]);
        }
        $stats = NormalizationStats::where('period_id', $period->id)
            ->whereIn('criterion_id', $leafChildren->pluck('id'))
            ->get()->keyBy('criterion_id');
        $rows = $leafChildren->map(function($c) use ($stats){
            $s = $stats->get($c->id);
            return [
                'id' => (string)$c->id,
                'name' => $c->name,
                'type' => $c->type,
                'method' => $s?->method,
                'min' => $s?->min_value,
                'max' => $s?->max_value,
                'mean' => $s?->mean_value,
                'std' => $s?->std_dev_value,
                'count' => $s?->count_samples,
                'computed_at' => optional($s?->computed_at)->toDateTimeString(),
            ];
        });
        return response()->json(['data' => $rows]);
    }

    public function weightsRoots(Request $request, Periods $period)
    {
        $this->guardViewableStates($period);
        $all = Criteria::where('period_id', $period->id)
            ->orderBy('order_index')
            ->get(['id','name','parent_id','order_index']);
        $parentIds = Criteria::where('period_id', $period->id)
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->unique()
            ->filter()
            ->values();
        $leafIds = $all->pluck('id')->diff($parentIds)->values();
        $weights = Weights::where('period_id', $period->id)
            ->where('level','criterion')
            ->whereIn('node_id', $leafIds)
            ->pluck('weight','node_id')
            ->all(); // plain array keyed by node_id (string)
        $children = [];
        foreach ($all as $c) { $pid = $c->parent_id; $children[(string)$pid][] = (string)$c->id; }
        $roots = $all->whereNull('parent_id')->values();
        $out = [];
        foreach ($roots as $r) {
            $sum = 0.0; $stack = $children[(string)$r->id] ?? [];
            // Jika root adalah leaf (tidak punya anak), tambahkan bobot root sendiri
            if (empty($stack)) {
                $sum += (float) ($weights[(string)$r->id] ?? 0.0);
            }
            while (!empty($stack)) {
                $id = array_pop($stack);
                if (isset($children[(string)$id])) { foreach ($children[(string)$id] as $k) { $stack[] = $k; } }
                else { $sum += (float) ($weights[(string)$id] ?? 0.0); }
            }
            $out[] = ['id'=>(string)$r->id, 'name'=>$r->name, 'total_weight'=>$sum];
        }
        return response()->json(['data' => $out]);
    }

    public function finalize(Request $request, Periods $period)
    {
        // Only allow finalize when not already finalized
        if ($period->status === 'finalized') {
            return response()->json(['message' => 'Periode sudah difinalisasi'], 400);
        }
        // Optionally ensure results are calculated
        // if ($period->status !== 'calculated') { return response()->json(['message' => 'Hitung AHP terlebih dahulu'], 400); }

        DB::transaction(function () use ($period) {
            $period->status = 'finalized';
            $period->finalized_at = now();
            $period->is_results_stale = false;
            $period->save();
        });
        return response()->json(['message' => 'Periode berhasil difinalisasi']);
    }

    public function results(Request $request, Periods $period)
    {
        $this->guardViewableStates($period);
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);

        $q = Results::where('results.period_id', $period->id)
            ->join('candidates','candidates.id','=','results.candidate_id')
            ->join('students','students.id','=','candidates.student_id')
            ->select([
                'results.candidate_id', 'results.rank', 'results.final_score',
                'students.nis', 'students.name', 'students.class'
            ]);
        if ($search !== '') {
            $q->where(function($w) use ($search){
                $w->where('students.name','like',"%{$search}%")
                  ->orWhere('students.nis','like',"%{$search}%");
            });
        }
        $page = $q->orderBy('results.rank')->paginate($perPage);
        return response()->json([
            'data' => $page->items(),
            'current_page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'last_page' => $page->lastPage(),
            'is_results_stale' => (bool)$period->is_results_stale,
            'last_calculated_at' => optional($period->last_calculated_at)->toDateTimeString(),
        ]);
    }

    public function breakdown(Request $request, Periods $period, string $candidateId)
    {
        $this->guardViewableStates($period);
        $rows = ResultsBreakdown::where('results_breakdown.period_id', $period->id)
            ->where('results_breakdown.candidate_id', $candidateId)
            ->leftJoin('criteria', function($j) use ($period){
                $j->on('criteria.id','=','results_breakdown.criterion_id')
                  ->where('criteria.period_id', $period->id);
            })
            ->orderBy('criteria.order_index')
            ->get([
                'results_breakdown.criterion_id',
                'results_breakdown.raw_value',
                'results_breakdown.normalized_value',
                'results_breakdown.weight',
                'results_breakdown.contribution',
                DB::raw("COALESCE(criteria.name, 'Unknown') as criterion_name"),
                DB::raw("COALESCE(criteria.type, 'benefit') as criterion_type"),
            ]);
        return response()->json(['data' => $rows]);
    }

    public function weightsList(Request $request, Periods $period)
    {
        $this->guardViewableStates($period);
        // Hanya kriteria leaf (level=criterion bobot global)
        $parentIds = Criteria::where('period_id', $period->id)
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->unique()
            ->filter()
            ->values();

        $rows = Weights::where('weights.period_id', $period->id)
            ->where('weights.level','criterion')
            ->leftJoin('criteria', function($j) use ($period){
                $j->on('criteria.id','=','weights.node_id')
                  ->where('criteria.period_id', $period->id);
            })
            ->when($parentIds->isNotEmpty(), fn($q) => $q->whereNotIn('weights.node_id', $parentIds))
            ->orderBy('criteria.order_index')
            ->get([
                'weights.node_id as id',
                DB::raw("COALESCE(criteria.name, 'Unknown') as name"),
                DB::raw("COALESCE(criteria.type, 'benefit') as type"),
                'weights.weight'
            ]);
        return response()->json(['data' => $rows]);
    }

    public function batchStore(Request $request, Periods $period)
    {
        $this->guardEditableStates($period, allowCalculateState: true);

        $data = $request->validate([
            'criterion_id' => ['required','uuid'],
            'items' => ['required','array','min:1'],
            'items.*.candidate_id' => ['required','uuid'],
            'items.*.raw_value' => ['nullable','numeric','between:0,100'],
        ]);

        $criterion = Criteria::where('period_id', $period->id)->where('id', $data['criterion_id'])->firstOrFail();
        // Cost type must be > 0 if provided
        if ($criterion->type === 'cost') {
            foreach ($data['items'] as $it) {
                if (array_key_exists('raw_value', $it) && $it['raw_value'] !== null && (float)$it['raw_value'] <= 0.0) {
                    return response()->json(['message' => 'Kriteria cost tidak boleh 0'], 422);
                }
            }
        }

        $validCandidates = Candidates::where('period_id', $period->id)->pluck('id')->all();
        $validSet = array_flip($validCandidates);

        DB::transaction(function() use ($period, $data, $validSet) {
            foreach ($data['items'] as $row) {
                $cand = $row['candidate_id'];
                if (!isset($validSet[$cand])) continue;
                // Lewati baris kosong agar tidak menulis NULL ke kolom non-null
                $raw = array_key_exists('raw_value', $row) ? $row['raw_value'] : null;
                if ($raw === null) continue;

                $attrs = [
                    'period_id' => $period->id,
                    'criterion_id' => $data['criterion_id'],
                    'candidate_id' => $cand,
                ];
                $values = [
                    'raw_value' => (float)$raw,
                    'updated_by' => Auth::id(),
                ];
                // Preserve created_by
                $existing = Scores::where($attrs)->first();
                if ($existing) {
                    $existing->fill($values);
                    $existing->save();
                } else {
                    Scores::create($attrs + $values + [
                        'created_by' => Auth::id(),
                        'normalized_value' => 0.0, // inisialisasi agar tidak melanggar NOT NULL
                    ]);
                }
            }

            // Setelah simpan, hitung ulang normalisasi & statistik untuk kriteria ini saja
            $now = now();
            $crit = Criteria::where('period_id', $period->id)->where('id', $data['criterion_id'])->first();
            if ($crit) {
                $rows = Scores::where('period_id', $period->id)
                    ->where('criterion_id', $crit->id)
                    ->get(['id','raw_value']);

                $vals = $rows->pluck('raw_value')->filter(fn($v)=>$v !== null)->map(fn($v)=>(float)$v)->values();
                $count = $vals->count();
                if ($count === 0) {
                    Scores::where('period_id',$period->id)->where('criterion_id',$crit->id)->update(['normalized_value' => 0.0]);
                } else {
                    $min = $vals->min();
                    $max = $vals->max();
                    $sum = $vals->sum();
                    $mean = $count ? ($sum / $count) : 0.0;
                    $std = 0.0;
                    if ($count > 1) {
                        $variance = $vals->map(fn($v)=>pow($v - $mean,2))->sum() / ($count - 1);
                        $std = sqrt($variance);
                    }

                    $method = $crit->type === 'cost' ? 'min_over_x' : 'x_over_max';
                    foreach ($rows as $r) {
                        $raw = $r->raw_value === null ? null : (float)$r->raw_value;
                        $norm = 0.0;
                        if ($raw !== null) {
                            if ($method === 'x_over_max') {
                                $norm = $max > 0 ? ($raw / $max) : 0.0;
                            } else {
                                $norm = $raw > 0 ? ($min / $raw) : 0.0;
                            }
                        } else {
                            $norm = 0.0;
                        }
                        Scores::where('id',$r->id)->update(['normalized_value'=>$norm]);
                    }

                    NormalizationStats::updateOrCreate(
                        [
                            'period_id' => $period->id,
                            'criterion_id' => $crit->id,
                        ],
                        [
                            'method' => $method,
                            'params' => ['min'=>$min, 'max'=>$max],
                            'min_value' => $min,
                            'max_value' => $max,
                            'sum_value' => $sum,
                            'mean_value' => $mean,
                            'std_dev_value' => $std,
                            'count_samples' => $count,
                            'computed_at' => $now,
                            'computed_by' => Auth::id(),
                        ]
                    );
                }
            }

            // Tanda hasil stale (hasil akhir perlu dihitung ulang)
            $period->is_results_stale = true;
            $period->updated_by = Auth::id();
            $period->save();
        });

        return response()->json(['message' => 'Nilai berhasil disimpan']);
    }

    public function calculate(Request $request, Periods $period, AhpService $ahp)
    {
        $this->guardEditableStates($period, allowCalculateState: true);

        $criterionId = $request->input('criterion_id'); // null => all

        // Ambil semua kriteria aktif di period
        $criteria = Criteria::where('period_id', $period->id)
            ->when($criterionId, fn($q) => $q->where('id', $criterionId))
            ->orderBy('order_index')->get(['id','type']);
        if ($criteria->isEmpty()) {
            return response()->json(['message' => 'Tidak ada kriteria untuk dihitung'], 422);
        }

        // Weights: gunakan bobot yang tersimpan (level 'criterion')
        $weights = Weights::where('period_id', $period->id)
            ->where('level','criterion')
            ->whereIn('node_id', $criteria->pluck('id'))
            ->pluck('weight','node_id');

        DB::transaction(function () use ($period, $criteria, $weights) {
            $now = now();
            // Agregasi ke Results & ResultsBreakdown menggunakan normalized_value yang sudah ada
            $candIds = Candidates::where('period_id', $period->id)->pluck('id');
            // Bersihkan lama
            ResultsBreakdown::where('period_id', $period->id)->delete();

            $finalScores = [];
            foreach ($candIds as $cid) { $finalScores[(string)$cid] = 0.0; }

            foreach ($criteria as $c) {
                $w = (float) ($weights[$c->id] ?? 0.0);
                $rows = Scores::where('period_id', $period->id)->where('criterion_id', $c->id)->get(['candidate_id','raw_value','normalized_value']);
                foreach ($rows as $r) {
                    $nv = $r->normalized_value === null ? 0.0 : (float)$r->normalized_value;
                    $contrib = $nv * $w;
                    $finalScores[(string)$r->candidate_id] = ($finalScores[(string)$r->candidate_id] ?? 0.0) + $contrib;
                    ResultsBreakdown::create([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'period_id' => $period->id,
                        'candidate_id' => $r->candidate_id,
                        'criterion_id' => $c->id,
                        'raw_value' => $r->raw_value,
                        'normalized_value' => $nv,
                        'weight' => $w,
                        'contribution' => $contrib,
                        'computed_at' => $now,
                        'computed_by' => Auth::id(),
                    ]);
                }
            }

            // Simpan Results dengan ranking
            $sorted = collect($finalScores)->sortDesc();
            $rank = 1;
            Results::where('period_id', $period->id)->delete();
            foreach ($sorted as $cid => $score) {
                Results::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'period_id' => $period->id,
                    'candidate_id' => $cid,
                    'final_score' => $score,
                    'rank' => $rank++,
                    'computed_at' => $now,
                ]);
            }

            // Tandai periode (tanpa mengubah status karena constraint)
            $period->is_results_stale = false;
            $period->last_calculated_at = $now;
            $period->updated_by = Auth::id();
            $period->save();
        });

        return response()->json(['message' => 'Perhitungan diperbarui']);
    }

    public function stats(Request $request, Periods $period)
    {
        $this->guardViewableStates($period);
        $criterionId = (string) $request->query('criterion_id', '');
        if ($criterionId === '') {
            return response()->json(['message' => 'criterion_id wajib diisi'], 422);
        }
        $stat = NormalizationStats::where('period_id', $period->id)
            ->where('criterion_id', $criterionId)
            ->first();
        if (!$stat) {
            return response()->json(['data' => null]);
        }
        return response()->json([
            'data' => [
                'method' => $stat->method,
                'params' => $stat->params,
                'min' => $stat->min_value,
                'max' => $stat->max_value,
                'sum' => $stat->sum_value,
                'mean' => $stat->mean_value,
                'std' => $stat->std_dev_value,
                'count' => $stat->count_samples,
                'computed_at' => optional($stat->computed_at)->toDateTimeString(),
            ]
        ]);
    }

    public function normalizationDetails(Request $request, Periods $period)
    {
        $this->guardViewableStates($period);
        $criterionId = (string) $request->query('criterion_id', '');
        if ($criterionId === '') {
            return response()->json(['message' => 'criterion_id wajib diisi'], 422);
        }
        $crit = Criteria::where('period_id', $period->id)->where('id', $criterionId)->first();
        if (!$crit) {
            return response()->json(['message' => 'Kriteria tidak ditemukan'], 404);
        }
        $stat = NormalizationStats::where('period_id', $period->id)
            ->where('criterion_id', $criterionId)
            ->first();
        $method = $stat?->method ?? ($crit->type === 'cost' ? 'min_over_x' : 'x_over_max');
        $params = $stat?->params ?? [];
        $formula = $method === 'x_over_max' ? 'nv = x / max(x)' : 'nv = min(x) / x';

        $rows = Scores::where('scores.period_id', $period->id)
            ->where('scores.criterion_id', $criterionId)
            ->join('candidates','candidates.id','=','scores.candidate_id')
            ->join('students','students.id','=','candidates.student_id')
            ->orderBy('students.name')
            ->get([
                'students.nis','students.name','students.class',
                'scores.raw_value','scores.normalized_value'
            ]);

        return response()->json([
            'data' => [
                'criterion' => [ 'id' => (string)$crit->id, 'name' => $crit->name, 'type' => $crit->type ],
                'method' => $method,
                'formula' => $formula,
                'params' => $params,
                'stat' => [
                    'min' => $stat?->min_value,
                    'max' => $stat?->max_value,
                    'mean' => $stat?->mean_value,
                    'std' => $stat?->std_dev_value,
                    'count' => $stat?->count_samples,
                    'computed_at' => optional($stat?->computed_at)->toDateTimeString(),
                ],
                'rows' => $rows,
            ]
        ]);
    }

    public function completeness(Request $request, Periods $period)
    {
        $this->guardViewableStates($period);
        // Hanya cek LEAF criteria (tidak punya anak), sesuai input Step 3
        $allCriteria = Criteria::where('period_id', $period->id)->get(['id','parent_id']);
        $parentIds = Criteria::where('period_id', $period->id)
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->unique()
            ->filter()
            ->values();
        $criteria = $allCriteria->pluck('id')->diff($parentIds)->values();
        $candidates = Candidates::where('period_id', $period->id)->pluck('id');
        $critCount = $criteria->count();
        $candCount = $candidates->count();
        if ($critCount === 0 || $candCount === 0) {
            return response()->json(['complete' => false, 'missing' => $critCount*$candCount, 'per_criterion' => []]);
        }

        // Hitung skor yang sudah terisi (raw_value not null)
        $filled = Scores::where('period_id', $period->id)
            ->whereIn('criterion_id', $criteria)
            ->whereIn('candidate_id', $candidates)
            ->whereNotNull('raw_value')
            ->count();
        $expected = $critCount * $candCount;

        // Per-criterion missing
        $per = [];
        foreach ($criteria as $cid) {
            $filledPer = Scores::where('period_id', $period->id)
                ->where('criterion_id', $cid)
                ->whereIn('candidate_id', $candidates)
                ->whereNotNull('raw_value')
                ->count();
            $per[] = [
                'criterion_id' => (string)$cid,
                'missing' => max($candCount - $filledPer, 0),
            ];
        }

        return response()->json([
            'complete' => $filled >= $expected,
            'missing' => max($expected - $filled, 0),
            'per_criterion' => $per,
        ]);
    }

    private function guardEditableStates(Periods $period, bool $allowCalculateState = false): void
    {
        $ok = $period->status === 'input' || ($allowCalculateState && $period->status === 'calculate');
        if (!$ok) {
            abort(403, 'Tidak dapat mengedit pada status saat ini');
        }
    }

    private function guardViewableStates(Periods $period): void
    {
        // Boleh dilihat pada status input, calculate, dan finalized
        if (!in_array($period->status, ['input','calculate','finalized'], true)) {
            abort(403, 'Tidak dapat melihat pada status saat ini');
        }
    }
}
