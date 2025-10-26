@extends('layouts.master')

@section('title', 'Detail Periode')

@push('styles')
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .image-input-placeholder {
            background-image: url('{{ asset('assets/media/svg/avatars/blank.svg') }}');
        }

        [data-bs-theme="dark"] .image-input-placeholder {
            background-image: url('{{ asset('assets/media/svg/avatars/blank-dark.svg') }}');
        }

        .image-input-wrapper {
            background-size: cover;
            background-position: center;
        }

        .image-input.image-input-circle .image-input-wrapper {
            border-radius: 50%;
        }
    </style>
@endpush

@section('content')
    <div class="card m-5">
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success d-flex align-items-center p-4 mb-5">
                    <i class="ki-duotone ki-check-circle fs-2hx me-3"></i>
                    <div>{{ session('success') }}</div>
                </div>
                <script>
                    (function() {
                        const alerts = document.querySelectorAll('.card-body .alert');
                        alerts.forEach(function(el) {
                            el.style.transition = 'opacity .3s ease';
                            setTimeout(function() {
                                el.style.opacity = '0';
                                setTimeout(function() {
                                    el.remove();
                                }, 300);
                            }, 3000);
                        });
                    })();
                </script>
            @endif
            @if (session('error'))
                <script>
                    (function() {
                        const msg = @json(session('error'));
                        if (window.Swal && typeof Swal.fire === 'function') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Terjadi Kesalahan',
                                text: msg
                            });
                        } else {
                            // Fallback cepat jika SweetAlert tidak tersedia
                            const host = document.querySelector('.card-body');
                            if (!host) return;
                            const alert = document.createElement('div');
                            alert.className = 'alert alert-danger d-flex align-items-center p-4 mb-5';
                            alert.innerHTML = '<i class="ki-duotone ki-information fs-2hx me-3"></i><div>' + msg + '</div>';
                            host.prepend(alert);
                            setTimeout(() => {
                                alert.remove();
                            }, 4000);
                        }
                    })();
                </script>
            @endif

            <div class="d-flex align-items-center mb-5">
                <div>
                    <h2 class="mb-1">{{ $period->name }}</h2>
                    <div class="text-muted">
                        <span
                            class="badge badge-light-{{ $period->status === 'finalized' ? 'success' : ($period->status === 'calculated' ? 'primary' : ($period->status === 'input' ? 'warning' : 'secondary')) }}">{{ $period->status ?? 'draft' }}</span>
                        <span
                            class="ms-3">{{ $period->start_date ? \Illuminate\Support\Carbon::parse($period->start_date)->format('d M Y') : '-' }}
                            —
                            {{ $period->end_date ? \Illuminate\Support\Carbon::parse($period->end_date)->format('d M Y') : '-' }}</span>
                    </div>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <a href="{{ route('assessment.periods.index') }}" class="btn btn-light btn-sm">Kembali</a>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit"
                        data-id="{{ $period->id }}" data-name="{{ $period->name }}"
                        data-start_date="{{ $period->start_date ? \Illuminate\Support\Carbon::parse($period->start_date)->format('Y-m-d') : '' }}"
                        data-end_date="{{ $period->end_date ? \Illuminate\Support\Carbon::parse($period->end_date)->format('Y-m-d') : '' }}">Edit
                        Periode</button>
                </div>
            </div>

            @php $step = (int) $step; @endphp
            <ul class="nav nav-pills mb-5">
                <li class="nav-item"><a class="nav-link {{ $step === 1 ? 'active' : '' }}"
                        href="{{ route('assessment.periods.show', ['period' => $period->id, 'step' => 1]) }}">1.
                        Kategori</a>
                </li>
                <li class="nav-item"><a class="nav-link {{ $step === 2 ? 'active' : '' }}"
                        href="{{ route('assessment.periods.show', ['period' => $period->id, 'step' => 2]) }}">2. Siswa</a>
                </li>
                <li class="nav-item"><a class="nav-link {{ $step === 3 ? 'active' : '' }}"
                        href="{{ route('assessment.periods.show', ['period' => $period->id, 'step' => 3]) }}">3.
                        Penilaian</a>
                </li>
                <li class="nav-item"><a class="nav-link {{ $step === 4 ? 'active' : '' }}"
                        href="{{ route('assessment.periods.show', ['period' => $period->id, 'step' => 4]) }}">4. Hasil</a>
                </li>
                <li class="nav-item"><a class="nav-link {{ $step === 5 ? 'active' : '' }}"
                        href="{{ route('assessment.periods.show', ['period' => $period->id, 'step' => 5]) }}">Log Aktivitas</a>
                </li>
            </ul>

            <div class="card bg-light-primary">
                <div class="card-body">
                    @if ($step === 1)
                        @include('assessment.periods.steps._step1_kategori')
                    @elseif ($step === 2)
                        @include('assessment.periods.steps._step2_siswa')
                    @elseif ($step === 3)
                        @include('assessment.periods.steps._step3_penilaian')
                    @elseif ($step === 4)
                        @include('assessment.periods.steps._step4_hasil')
                    @else
                        @include('assessment.periods.steps._step5_aktivitas')
                    @endif
                    <div class="d-flex justify-content-between mt-6">
                        <a class="btn btn-light btn-sm {{ $step <= 1 ? 'disabled' : '' }}"
                            href="{{ $step <= 1 ? '#' : route('assessment.periods.show', ['period' => $period->id, 'step' => $step - 1]) }}">Previous</a>
                        <div class="flex-grow-1 px-4 d-flex justify-content-center align-items-center gap-2">
                            <a href="{{ route('assessment.periods.show', ['period' => $period->id, 'step' => 1]) }}"
                                class="d-inline-block rounded-circle {{ $step === 1 ? 'bg-primary' : 'bg-secondary' }}"
                                style="width:12px;height:12px;border:1px solid rgba(0,0,0,.2);"></a>
                            <a href="{{ route('assessment.periods.show', ['period' => $period->id, 'step' => 2]) }}"
                                class="d-inline-block rounded-circle {{ $step === 2 ? 'bg-primary' : 'bg-secondary' }}"
                                style="width:12px;height:12px;border:1px solid rgba(0,0,0,.2);"></a>
                            <a href="{{ route('assessment.periods.show', ['period' => $period->id, 'step' => 3]) }}"
                                class="d-inline-block rounded-circle {{ $step === 3 ? 'bg-primary' : 'bg-secondary' }}"
                                style="width:12px;height:12px;border:1px solid rgba(0,0,0,.2);"></a>
                            <a href="{{ route('assessment.periods.show', ['period' => $period->id, 'step' => 4]) }}"
                                class="d-inline-block rounded-circle {{ $step === 4 ? 'bg-primary' : 'bg-secondary' }}"
                                style="width:12px;height:12px;border:1px solid rgba(0,0,0,.2);"></a>
                            <a href="{{ route('assessment.periods.show', ['period' => $period->id, 'step' => 5]) }}"
                                class="d-inline-block rounded-circle {{ $step === 5 ? 'bg-primary' : 'bg-secondary' }}"
                                style="width:12px;height:12px;border:1px solid rgba(0,0,0,.2);"></a>
                        </div>
                        <a class="btn btn-primary btn-sm {{ $step >= 5 ? 'disabled' : '' }}"
                            href="{{ $step >= 5 ? '#' : route('assessment.periods.show', ['period' => $period->id, 'step' => $step + 1]) }}">Next</a>
                    </div>
                </div>
            </div>
        </div>

        @include('assessment.periods.partials._modal_edit')
    @endsection

    @push('scripts')
        <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
        <script>
            const modalEdit = document.getElementById('modalEdit');
            modalEdit?.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (!button) return;
                const form = modalEdit.querySelector('form');
                form.action = '{{ route('assessment.periods.update', ['period' => '__ID__']) }}'.replace('__ID__',
                    button.getAttribute('data-id'));
                modalEdit.querySelector('[name="name"]').value = button.getAttribute('data-name') || '';
                modalEdit.querySelector('[name="start_date"]').value = button.getAttribute('data-start_date') || '';
                modalEdit.querySelector('[name="end_date"]').value = button.getAttribute('data-end_date') || '';
                modalEdit.querySelector('[name="status"]').value = button.getAttribute('data-status') || 'draft';
            });

            const modalDelete = document.getElementById('modalDelete');
            modalDelete?.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const form = modalDelete.querySelector('form');
                form.action = '{{ route('assessment.periods.destroy', ['period' => '__ID__']) }}'.replace('__ID__',
                    button.getAttribute('data-id'));
                modalDelete.querySelector('#deletePeriodName').textContent = button.getAttribute('data-name') || '';
            });
        </script>
    @endpush
