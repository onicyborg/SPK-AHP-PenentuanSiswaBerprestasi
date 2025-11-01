<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeriodRequest;
use App\Http\Requests\UpdatePeriodRequest;
use App\Models\Periods;
use App\Models\Criteria;
use App\Models\PairwiseCriteria;
use App\Models\Candidates;
use App\Models\Weights;
use App\Models\Scores;
use App\Models\PairwiseAlternatives;
use App\Models\Results;
use App\Models\ResultsBreakdown;
use App\Models\NormalizationStats;
use App\Models\AuditLogs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AhpService;

class PeriodController extends Controller
{
    public function index()
    {
        $periods = Periods::orderByDesc('created_at')->get();
        return view('assessment.periods.index', compact('periods'));
    }

    public function show(Periods $period, Request $request)
    {
        $step = (int) $request->query('step', 1);
        $step = max(1, min(5, $step));
        $criteria = collect();
        $candidatesCount = 0;
        $hasPairwise = false;
        $selectedCluster = null; // null means ROOT cluster
        $parentOptions = collect();
        if ($step === 1) {
            $selectedCluster = $request->query('cluster');
            if ($selectedCluster === 'root' || $selectedCluster === '') { $selectedCluster = null; }

            // Options for parent selection (only root-level shown as parents)
            $parentOptions = Criteria::where('period_id', $period->id)
                ->whereNull('parent_id')
                ->orderBy('order_index')->get(['id','name']);

            // Criteria for current cluster
            $query = Criteria::where('period_id', $period->id)->orderBy('order_index');
            if ($selectedCluster === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $selectedCluster);
            }
            $criteria = $query->get();
            $candidatesCount = Candidates::where('period_id', $period->id)->count();
            // hasPairwise scoped to cluster
            $ids = $criteria->pluck('id')->all();
            $hasPairwise = !empty($ids)
                ? PairwiseCriteria::where('period_id', $period->id)
                    ->whereIn('i_criterion_id', $ids)
                    ->whereIn('j_criterion_id', $ids)
                    ->exists()
                : false;
        }
        return view('assessment.periods.show', compact('period', 'step', 'criteria', 'candidatesCount', 'hasPairwise', 'selectedCluster', 'parentOptions'));
    }

    public function store(StorePeriodRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = 'draft';
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        Periods::create($data);
        return back()->with('success', 'Periode berhasil dibuat');
    }

    public function update(UpdatePeriodRequest $request, Periods $period): RedirectResponse
    {
        $data = $request->validated();
        // Keep status if not provided; default remains managed elsewhere
        if (empty($data['status'])) {
            unset($data['status']);
        }
        $data['updated_by'] = Auth::id();
        $period->update($data);
        return back()->with('success', 'Periode berhasil diperbarui');
    }

    public function destroy(Periods $period): RedirectResponse
    {
        DB::transaction(function () use ($period) {
            // Hapus data turunan yang mereferensikan period ini (tanpa menyentuh master data: students, users)
            ResultsBreakdown::where('period_id', $period->id)->delete();
            Results::where('period_id', $period->id)->delete();
            NormalizationStats::where('period_id', $period->id)->delete();
            Scores::where('period_id', $period->id)->delete();
            PairwiseAlternatives::where('period_id', $period->id)->delete();
            PairwiseCriteria::where('period_id', $period->id)->delete();
            Weights::where('period_id', $period->id)->delete();
            Candidates::where('period_id', $period->id)->delete();
            Criteria::where('period_id', $period->id)->delete();
            AuditLogs::where('period_id', $period->id)->delete();

            // Terakhir hapus record period
            // Nonaktifkan event agar observer tidak menulis audit log baru yang melanggar FK
            Periods::withoutEvents(function () use ($period) {
                $period->delete();
            });
        });
        return redirect()->route('assessment.periods.index')->with('success', 'Periode dan seluruh data terkait berhasil dihapus');
    }

    public function submitSetup(Request $request, Periods $period, AhpService $ahp): RedirectResponse
    {
        if ($period->status !== 'draft') {
            return back()->with('error', 'Setup sudah terkunci');
        }

        // Validasi konsistensi per cluster (root + setiap parent dengan anak)
        $rootIds = Criteria::where('period_id', $period->id)
            ->whereNull('parent_id')->orderBy('order_index')->pluck('id');
        if ($rootIds->count() < 2) {
            return back()->with('error', 'Minimal 2 kriteria pada root diperlukan');
        }

        // Kumpulkan daftar parent yang memiliki anak
        $parentIds = Criteria::where('period_id', $period->id)
            ->whereIn('id', function($q) use ($period) {
                $q->select('parent_id')->from('criteria')
                  ->where('period_id', $period->id)
                  ->whereNotNull('parent_id');
            })->orderBy('order_index')->pluck('id');

        $clusters = [];
        $clusters[] = ['key' => 'root', 'ids' => $rootIds->all(), 'parent_id' => null];
        foreach ($parentIds as $pid) {
            $childIds = Criteria::where('period_id', $period->id)
                ->where('parent_id', $pid)->orderBy('order_index')->pluck('id')->all();
            if (count($childIds) >= 2) {
                $clusters[] = ['key' => 'parent:'.$pid, 'ids' => $childIds, 'parent_id' => $pid];
            }
        }

        $clusterResults = [];
        foreach ($clusters as $cluster) {
            $ids = $cluster['ids'];
            $n = count($ids);
            if ($n < 2) { continue; }

            $indexOf = [];
            foreach ($ids as $idx => $id) { $indexOf[$id] = $idx; }
            $matrix = array_fill(0, $n, array_fill(0, $n, 1.0));

            $pairs = PairwiseCriteria::where('period_id', $period->id)
                ->whereIn('i_criterion_id', $ids)
                ->whereIn('j_criterion_id', $ids)
                ->get(['i_criterion_id','j_criterion_id','value']);
            foreach ($pairs as $p) {
                $i = $indexOf[$p->i_criterion_id] ?? null;
                $j = $indexOf[$p->j_criterion_id] ?? null;
                if ($i === null || $j === null || $i === $j) continue;
                $v = max((float)$p->value, 1e-9);
                $matrix[$i][$j] = $v;
                $matrix[$j][$i] = 1.0 / $v;
            }

            $res = $ahp->computeWeights($ids, $matrix);
            $CR = (float)($res['CR'] ?? 1.0);
            if ($CR > 0.10) {
                $label = $cluster['key'] === 'root' ? 'Root' : 'Sub-kriteria';
                return back()->with('error', "CR melebihi 0.10 pada cluster: {$label}.");
            }
            $clusterResults[$cluster['key']] = $res;
        }

        // Validasi jumlah kandidat
        $candidatesCount = Candidates::where('period_id', $period->id)->count();
        if ($candidatesCount < 2) {
            return back()->with('error', 'Minimal 2 kandidat diperlukan.');
        }

        DB::transaction(function () use ($period, $rootIds, $clusterResults) {
            // Propagasi bobot global ke LEAF criteria
            $now = now();
            $rootRes = $clusterResults['root'] ?? ['weights' => [], 'CR' => null];
            $rootWeights = (array)($rootRes['weights'] ?? []);

            // Ambil mapping parent_id untuk semua criteria
            $allCriteria = Criteria::where('period_id', $period->id)->get(['id','parent_id']);
            $parentOf = $allCriteria->pluck('parent_id','id');

            // Tentukan LEAF: id yang tidak menjadi parent bagi kriteria lain
            $hasChildrenIds = Criteria::where('period_id', $period->id)
                ->whereNotNull('parent_id')
                ->pluck('parent_id')
                ->unique()
                ->filter()
                ->values();
            $leafIds = $allCriteria->pluck('id')->diff($hasChildrenIds)->values();

            foreach ($leafIds as $leafId) {
                $parentId = $parentOf[$leafId] ?? null;
                if ($parentId) {
                    // Leaf di bawah parent: bobot global = bobot root(parent) * bobot lokal leaf pada cluster parent
                    $parentGlobal = (float)($rootWeights[$parentId] ?? 0.0);
                    $childLocal = (float)($clusterResults['parent:'.$parentId]['weights'][$leafId] ?? 0.0);
                    $global = $parentGlobal * $childLocal;
                    $crAtLevel = (float)($clusterResults['parent:'.$parentId]['CR'] ?? 0.0);
                } else {
                    // Leaf di root (tidak punya anak dan parent null): bobot global = bobot root langsung
                    $global = (float)($rootWeights[$leafId] ?? 0.0);
                    $crAtLevel = (float)($rootRes['CR'] ?? 0.0);
                }

                Weights::updateOrCreate(
                    [
                        'period_id' => $period->id,
                        'node_id' => $leafId,
                        'level' => 'criterion',
                    ],
                    [
                        'weight' => $global,
                        'cr_at_level' => $crAtLevel,
                        'computed_at' => $now,
                    ]
                );
            }

            // Lock period ke status input
            $period->status = 'input';
            $period->last_calculated_at = now();
            $period->is_results_stale = false;
            $period->updated_by = Auth::id();
            $period->save();
        });

        return redirect()->route('assessment.periods.show', ['period' => $period->id, 'step' => 2])
            ->with('success', 'Setup terkunci. Lanjut ke langkah 2.');
    }
}
