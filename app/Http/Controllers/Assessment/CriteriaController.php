<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Criteria;
use App\Models\PairwiseCriteria;
use App\Models\Periods;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CriteriaController extends Controller
{
    public function store(Request $request, Periods $period): RedirectResponse
    {
        if ($period->status !== 'draft') {
            abort(403, 'Step ini sudah terkunci');
        }
        $data = $request->validate([
            'name' => ['required','string','max:150'],
            'type' => ['required','in:benefit,cost'],
            'parent_id' => ['nullable','uuid'],
            'order_index' => ['nullable','integer','min:0'],
        ]);

        $data['period_id'] = $period->id;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $created = Criteria::create($data);
        // Tambah kriteria mengubah dimensi matriks cluster terkait → kosongkan pairwise pada cluster itu saja
        // Tentukan cluster: root (parent_id null) atau anak dari parent tertentu
        $clusterQuery = Criteria::where('period_id', $period->id);
        if (!empty($data['parent_id'])) {
            $clusterQuery->where('parent_id', $data['parent_id']);
        } else {
            $clusterQuery->whereNull('parent_id');
        }
        $clusterIds = $clusterQuery->pluck('id')->all();
        if (!empty($clusterIds)) {
            \App\Models\PairwiseCriteria::where('period_id', $period->id)
                ->whereIn('i_criterion_id', $clusterIds)
                ->whereIn('j_criterion_id', $clusterIds)
                ->delete();
        }

        return back()->with('success', 'Kriteria berhasil dibuat');
    }

    public function update(Request $request, Periods $period, Criteria $criterion): RedirectResponse
    {
        if ($period->status !== 'draft') {
            abort(403, 'Step ini sudah terkunci');
        }
        if ($criterion->period_id !== $period->id) {
            abort(404);
        }
        $data = $request->validate([
            'name' => ['required','string','max:150'],
            'type' => ['required','in:benefit,cost'],
            'parent_id' => ['nullable','uuid'],
            'order_index' => ['nullable','integer','min:0'],
        ]);
        $data['updated_by'] = Auth::id();
        $criterion->update($data);

        return back()->with('success', 'Kriteria berhasil diperbarui');
    }

    public function destroy(Periods $period, Criteria $criterion): RedirectResponse
    {
        if ($period->status !== 'draft') {
            abort(403, 'Step ini sudah terkunci');
        }
        if ($criterion->period_id !== $period->id) {
            abort(404);
        }
        // Opsional: hapus pasangan pairwise yang terkait
        \App\Models\PairwiseCriteria::where('period_id', $period->id)
            ->where(function($q) use ($criterion) {
                $q->where('i_criterion_id', $criterion->id)
                  ->orWhere('j_criterion_id', $criterion->id);
            })->delete();

        $criterion->delete();
        return back()->with('success', 'Kriteria berhasil dihapus');
    }
}
