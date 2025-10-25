@extends('layouts.master')

@section('title', 'Assessment - Periode')

@section('content')
    <div class="card m-5">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Daftar Periode</h3>
            <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#modalCreate">
                <i class="ki-duotone ki-plus fs-2"></i> Tambah Periode
            </button>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success d-flex align-items-center p-4 mb-5">
                    <i class="ki-duotone ki-check-circle fs-2hx me-3"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger p-4 mb-5">
                    <div class="fw-bold mb-2">Gagal menyimpan data:</div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted fw-semibold">
                            <th>Nama</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($periods as $p)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $p->name }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-light-{{ $p->status === 'finalized' ? 'success' : ($p->status === 'calculated' ? 'primary' : ($p->status === 'input' ? 'warning' : 'secondary')) }}">{{ $p->status ?? 'draft' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $p->start_date ?
                                        \Illuminate\Support\Carbon::parse($p->start_date)->format('d M Y') : '-' }}
                                        — {{ $p->end_date ? \Illuminate\Support\Carbon::parse($p->end_date)->format('d M Y') : '-' }}</span>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-light-info btn-sm" href="{{ route('assessment.periods.show', $p) }}" title="Detail">
                                        <center><i class="bi bi-eye"></i></center>
                                    </a>
                                    <button class="btn btn-light-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit"
                                        data-id="{{ $p->id }}"
                                        data-name="{{ $p->name }}"
                                        data-start_date="{{ $p->start_date }}"
                                        data-end_date="{{ $p->end_date }}">
                                        <center><i class="bi bi-pencil-square"></i></center>
                                    </button>
                                    <button class="btn btn-light-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalDelete"
                                        data-id="{{ $p->id }}" data-name="{{ $p->name }}">
                                        <center><i class="bi bi-trash"></i></center>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Belum ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('assessment.periods.partials._modal_create')
    @include('assessment.periods.partials._modal_edit')
    @include('assessment.periods.partials._modal_delete')
@endsection

@push('scripts')
<script>
    // Auto-hide alerts after 3 seconds
    (function(){
        const alerts = document.querySelectorAll('.card-body .alert');
        alerts.forEach(function(el){
            // ensure fade transition
            el.style.transition = 'opacity .3s ease';
            setTimeout(function(){
                el.style.opacity = '0';
                setTimeout(function(){ el.remove(); }, 300);
            }, 3000);
        });
    })();

    const modalEdit = document.getElementById('modalEdit');
    modalEdit?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;
        const form = modalEdit.querySelector('form');
        form.action = '{{ route('assessment.periods.update', ['period' => '__ID__']) }}'.replace('__ID__', button.getAttribute('data-id'));
        const startDate = (button.getAttribute('data-start_date') || '').slice(0, 10);
        const endDate = (button.getAttribute('data-end_date') || '').slice(0, 10);
        const nameInput = modalEdit.querySelector('[name="name"]');
        const startInput = modalEdit.querySelector('[name="start_date"]');
        const endInput = modalEdit.querySelector('[name="end_date"]');
        if (nameInput) nameInput.value = button.getAttribute('data-name') || '';
        if (startInput) startInput.value = startDate;
        if (endInput) endInput.value = endDate;
    });

    const modalDelete = document.getElementById('modalDelete');
    modalDelete?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const form = modalDelete.querySelector('form');
        form.action = '{{ route('assessment.periods.destroy', ['period' => '__ID__']) }}'.replace('__ID__', button.getAttribute('data-id'));
        modalDelete.querySelector('#deletePeriodName').textContent = button.getAttribute('data-name') || '';
    });
</script>
@endpush
