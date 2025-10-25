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
        $period->delete();
        return redirect()->route('assessment.periods.index')->with('success', 'Periode berhasil dihapus');
    }

    public function submitSetup(Request $request, Periods $period, AhpService $ahp): RedirectResponse
    {
        if ($period->status !== 'draft') {
            return back()->with('error', 'Setup sudah terkunci');
        }

        // Ambil kriteria dan matriks pairwise dari DB
        $criteria = Criteria::where('period_id', $period->id)->orderBy('order_index')->get(['id']);
        if ($criteria->count() < 2) {
            return back()->with('error', 'Minimal 2 kriteria diperlukan');
        }
        $n = $criteria->count();
        $indexOf = [];
        foreach ($criteria as $idx => $c) { $indexOf[$c->id] = $idx; }
        $matrix = array_fill(0, $n, array_fill(0, $n, 1.0));
        $pairs = PairwiseCriteria::where('period_id', $period->id)->get();
        foreach ($pairs as $p) {
            $i = $indexOf[$p->i_criterion_id] ?? null;
            $j = $indexOf[$p->j_criterion_id] ?? null;
            if ($i === null || $j === null || $i === $j) continue;
            $matrix[$i][$j] = (float)$p->value;
            $matrix[$j][$i] = 1.0 / max((float)$p->value, 1e-9);
        }

        $result = $ahp->computeWeights($criteria->pluck('id')->all(), $matrix);
        $CR = (float)($result['CR'] ?? 1.0);
        if ($CR > 0.10) {
            return back()->with('error', 'CR melebihi 0.10. Perbaiki matriks perbandingan.');
        }

        // Validasi jumlah kandidat
        $candidatesCount = Candidates::where('period_id', $period->id)->count();
        if ($candidatesCount < 2) {
            return back()->with('error', 'Minimal 2 kandidat diperlukan.');
        }

        DB::transaction(function () use ($period, $criteria, $result, $CR) {
            // Simpan snapshot bobot
            foreach ($criteria as $c) {
                Weights::updateOrCreate(
                    [
                        'period_id' => $period->id,
                        'node_id' => $c->id,
                        'level' => 'criterion',
                    ],
                    [
                        'weight' => (float)($result['weights'][$c->id] ?? 0.0),
                        'cr_at_level' => $CR,
                        'computed_at' => now(),
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
