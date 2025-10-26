<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Candidates;
use App\Models\Students;
use App\Models\Periods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CandidateController extends Controller
{
    public function available(Request $request, Periods $period)
    {
        $this->authorizeDraft($period);
        $q = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);

        $candidateIds = Candidates::where('period_id', $period->id)->pluck('student_id');
        $query = Students::query()->whereNotIn('id', $candidateIds);
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('nis', 'like', "%{$q}%");
            });
        }
        $page = $query->orderBy('name')->paginate($perPage);
        return response()->json([
            'data' => $page->items(),
            'current_page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'last_page' => $page->lastPage(),
        ]);
    }

    public function selected(Request $request, Periods $period)
    {
        $q = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);

        $query = Candidates::query()
            ->where('period_id', $period->id)
            ->join('students', 'students.id', '=', 'candidates.student_id')
            ->select('students.id', 'students.nis', 'students.name', 'students.class');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('students.name', 'like', "%{$q}%")
                  ->orWhere('students.nis', 'like', "%{$q}%");
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

    public function attach(Request $request, Periods $period)
    {
        $this->authorizeDraft($period);
        $data = $request->validate([
            'student_ids' => ['required','array','min:1'],
            'student_ids.*' => ['uuid'],
        ]);
        $now = now();
        $rows = [];
        foreach (array_unique($data['student_ids']) as $sid) {
            $rows[] = [
                'period_id' => $period->id,
                'student_id' => $sid,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        // idempotent insert
        DB::table('candidates')->upsert($rows, ['period_id','student_id'], ['updated_by','updated_at']);
        return response()->json(['message' => 'Kandidat ditambahkan']);
    }

    public function detach(Request $request, Periods $period)
    {
        $this->authorizeDraft($period);
        $data = $request->validate([
            'student_ids' => ['required','array','min:1'],
            'student_ids.*' => ['uuid'],
        ]);
        Candidates::where('period_id', $period->id)
            ->whereIn('student_id', $data['student_ids'])
            ->delete();
        return response()->json(['message' => 'Kandidat dihapus']);
    }

    private function authorizeDraft(Periods $period): void
    {
        if ($period->status !== 'draft') {
            abort(403, 'Step ini sudah terkunci');
        }
    }
}
