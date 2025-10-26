@php
    use Illuminate\Support\Carbon;
    $logs = \App\Models\AuditLogs::with('actedBy')
        ->where('period_id', $period->id)
        ->orderByDesc('acted_at')
        ->limit(100)
        ->get();

    function logIcon(string $entity, string $action): string {
        if ($entity === 'period' && $action === 'submit_setup') return 'bi-lock-fill text-primary';
        if ($entity === 'criteria') return $action === 'delete' ? 'bi-folder-x text-danger' : 'bi-folder2-open text-primary';
        if ($entity === 'pairwise_criteria') return 'bi-diagram-3 text-info';
        if ($entity === 'candidate') return $action === 'detach' ? 'bi-person-dash text-danger' : 'bi-person-plus text-success';
        if ($entity === 'student') return $action === 'create' ? 'bi-person-check text-success' : 'bi-person text-secondary';
        return 'bi-info-circle text-secondary';
    }

    function logLabel(string $entity, string $action): string {
        $map = [
            'period.submit_setup' => 'Setup dikunci',
            'criteria.create' => 'Tambah Kriteria',
            'criteria.update' => 'Ubah Kriteria',
            'criteria.delete' => 'Hapus Kriteria',
            'pairwise_criteria.upsert' => 'Simpan Matrix Perbandingan',
            'pairwise_criteria.calculate' => 'Hitung Bobot & CR',
            'candidate.attach' => 'Tambah Kandidat',
            'candidate.detach' => 'Hapus Kandidat',
            'student.create' => 'Tambah Siswa',
        ];
        return $map["{$entity}.{$action}"] ?? ucfirst($entity).' '.str_replace('_',' ', $action);
    }

    // Formatter nilai: tanggal ditampilkan ramah pengguna, boolean jadi Ya/Tidak, lainnya apa adanya
    $fmt = function($v) {
        if (is_null($v)) return '—';
        if (is_bool($v)) return $v ? 'Ya' : 'Tidak';
        if (is_scalar($v)) {
            $s = (string) $v;
            try {
                $dt = Carbon::parse($s);
                // Jika parse sukses dan string tampak seperti tanggal/waktu
                if ($dt instanceof Carbon) {
                    return $dt->format('H:i:s') === '00:00:00'
                        ? $dt->translatedFormat('d M Y')
                        : $dt->translatedFormat('d M Y H:i');
                }
            } catch (\Throwable $e) { /* not a date */ }
            return $s;
        }
        return json_encode($v);
    };
@endphp

<div class="d-flex align-items-center mb-4">
    <h4 class="mb-0">Log Aktivitas</h4>
    <span class="ms-3 badge badge-light-secondary">Periode: {{ $period->name }}</span>
</div>

@if ($logs->isEmpty())
    <div class="alert alert-info d-flex align-items-center">
        <i class="bi bi-journal-text fs-2 me-3"></i>
        <div>Belum ada aktivitas untuk periode ini.</div>
    </div>
@else
    @php $byDate = $logs->groupBy(fn($l)=>Carbon::parse($l->acted_at)->toDateString()); @endphp
    <div class="row">
        <div class="col-lg-12">
            <div class="border rounded p-4 bg-body">
                @foreach ($byDate as $date => $items)
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-3">
                            <div class="fw-bold">{{ Carbon::parse($date)->translatedFormat('l, d M Y') }}</div>
                            <div class="ms-3 text-muted small">{{ $items->count() }} aktivitas</div>
                        </div>
                        <div class="position-relative ps-4" style="border-left: 2px dashed rgba(0,0,0,.2);">
                            @foreach ($items as $log)
                                @php
                                    $time = Carbon::parse($log->acted_at);
                                    $who = $log->actedBy->name ?? 'Sistem';
                                    $title = logLabel($log->entity, $log->action);
                                    $icon = logIcon($log->entity, $log->action);
                                    // Normalisasi bentuk perubahan agar seragam menjadi pairs[field] = [before, after]
                                    $raw = $log->changes_json ?: [];
                                    $pairs = [];
                                    if (is_array($raw)) {
                                        // Bentuk: { changes: { field: [before, after] } }
                                        if (array_key_exists('changes', $raw) && is_array($raw['changes'])) {
                                            $pairs = $raw['changes'];
                                        } else {
                                            // Bentuk: { field: {before:.., after:..} } atau { field: [before, after] }
                                            $pairs = $raw;
                                        }
                                    }
                                    $id = 'log_'.str_replace(['-',' '],'', $log->id ?? spl_object_id($log));
                                @endphp
                                <div class="d-flex align-items-start mb-4">
                                    <div class="me-3 translate-middle-y" style="margin-left: -10px;">
                                        <span class="badge rounded-circle bg-body border" style="width:18px;height:18px;"></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center">
                                            <i class="{{ $icon }} me-2"></i>
                                            <div class="fw-semibold">{{ $title }}</div>
                                            <span class="ms-2 badge badge-light">{{ $log->entity }}</span>
                                            <span class="ms-auto text-muted small" title="{{ $time->format('d M Y H:i') }}">{{ $time->diffForHumans() }}</span>
                                        </div>
                                        <div class="text-muted small mt-1">Oleh: {{ $who }}</div>
                                        @if (!empty($pairs))
                                            <button class="btn btn-link p-0 mt-1 small" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $id }}">Lihat detail</button>
                                            <div id="{{ $id }}" class="collapse mt-2">
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th class="text-muted small">Field</th>
                                                                <th class="text-muted small">Sebelum</th>
                                                                <th class="text-muted small">Sesudah</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($pairs as $field => $change)
                                                                @php
                                                                    // Dukungan bentuk array [before, after]
                                                                    if (is_array($change) && array_key_exists(0, $change)) {
                                                                        $before = $change[0] ?? null;
                                                                        $after = $change[1] ?? null;
                                                                    } else {
                                                                        // Dukungan key umum
                                                                        $before = $change['before'] ?? $change['old'] ?? null;
                                                                        $after  = $change['after']  ?? $change['new'] ?? null;
                                                                    }
                                                                @endphp
                                                                <tr>
                                                                    <td class="small">{{ $field }}</td>
                                                                    <td class="small text-muted">{{ $fmt($before) }}</td>
                                                                    <td class="small">{{ $fmt($after) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="text-muted small">Menampilkan {{ $logs->count() }} aktivitas terbaru.</div>
            </div>
        </div>
    </div>
@endif
