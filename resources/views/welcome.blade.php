@extends('layouts.master')

@section('page_title', 'Dashboard')


@section('content')
    <div class="m-6 space-y-6">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title m-0">Dashboard</h3>
                <div>
                    @if($period)
                        <span class="badge bg-primary">{{ $period->name }}</span>
                        <span class="ms-2 text-muted">{{ optional($period->start_date)->format('d M Y') }} - {{ optional($period->end_date)->format('d M Y') }}</span>
                        <span class="ms-2 badge bg-secondary text-uppercase">{{ $period->status }}</span>
                    @else
                        <span class="text-muted">Belum ada periode</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if(!$period)
                    <p class="mb-0 text-muted">Buat periode penilaian terlebih dahulu.</p>
                @else
                    <div class="row g-3">
                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="card p-3 h-100">
                                <div class="text-muted">Siswa Kandidat</div>
                                <div class="h3 m-0">{{ $summary['candidates'] }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="card p-3 h-100">
                                <div class="text-muted">Kriteria (Leaf)</div>
                                <div class="h3 m-0">{{ $summary['criteria_leaf'] }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="card p-3 h-100">
                                <div class="text-muted">Progress Input</div>
                                <div class="h3 m-0">{{ number_format($summary['progress_pct'], 2) }}%</div>
                                <div class="text-muted">{{ $summary['filled'] }} / {{ $summary['expected'] }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="card p-3 h-100">
                                <div class="text-muted">Avg CR</div>
                                <div class="h3 m-0">{{ $summary['avg_cr'] !== null ? number_format($summary['avg_cr'], 3) : '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="card p-3 h-100">
                                <div class="text-muted">Missing</div>
                                <div class="h3 m-0">{{ max(($summary['expected'] - $summary['filled']), 0) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="card p-3 h-100">
                                <div class="text-muted">Hasil</div>
                                <div class="h3 m-0">{{ $summary['is_results_stale'] ? 'Perlu Hitung' : 'Up-to-date' }}</div>
                                <div class="text-muted">{{ $summary['last_calculated_at'] ? ('Terakhir: '.$summary['last_calculated_at']) : '-' }}</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title m-0">Top 5 Siswa</h3>
                    </div>
                    <div class="card-body table-responsive">
                        @if(isset($topResults) && $topResults->count())
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Peringkat</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Skor</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($topResults as $row)
                                    <tr>
                                        <td>{{ $row->rank }}</td>
                                        <td>{{ $row->student_name }}</td>
                                        <td>{{ $row->student_class }}</td>
                                        <td>{{ number_format((float)$row->final_score, 4) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-muted">Belum ada hasil.</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title m-0">Bobot Kriteria (Top 10)</h3>
                    </div>
                    <div class="card-body table-responsive">
                        @if(isset($weightsRows) && $weightsRows->count())
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Kriteria</th>
                                    <th class="text-end">Bobot</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($weightsRows as $w)
                                    <tr>
                                        <td>{{ $w->name }}</td>
                                        <td class="text-end">{{ number_format((float)$w->weight, 4) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-muted">Bobot belum tersedia.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
