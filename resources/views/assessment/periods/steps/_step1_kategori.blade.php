@php
    $isDraft = ($period->status === 'draft');
@endphp

<div class="d-flex align-items-center mb-4">
    <h4 class="mb-0">Langkah 1: Kategori (Kriteria) & Pairwise</h4>
    <span class="ms-3 badge badge-light-{{ $isDraft ? 'warning' : 'secondary' }}">Status: {{ $period->status }}</span>
    @unless($isDraft)
        <span class="ms-3 text-muted">Read-only</span>
    @endunless
    <span class="ms-3">Cluster: <span class="badge badge-light-info">{{ $selectedCluster ? ($parentOptions->firstWhere('id',$selectedCluster)->name ?? 'Sub') : 'Root' }}</span></span>
    <form class="ms-auto d-flex align-items-center gap-2" method="GET" action="{{ route('assessment.periods.show', $period) }}">
        <input type="hidden" name="step" value="1">
        <select name="cluster" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="root" {{ !$selectedCluster ? 'selected' : '' }}>Root (tanpa parent)</option>
            @foreach ($parentOptions as $p)
                <option value="{{ $p->id }}" {{ $selectedCluster === $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        <span class="text-muted">Kandidat: {{ $candidatesCount ?? 0 }}</span>
    </form>
</div>

<div class="row g-5">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h5 class="card-title mb-0">Daftar Kriteria</h5>
                @if ($isDraft)
                <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#modalCreateCriterion">
                    <i class="bi bi-plus"></i> Tambah
                </button>
                @endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr class="text-muted fw-semibold">
                                <th>Nama</th>
                                <th>Tipe</th>
                                <th>Urutan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($criteria as $c)
                                <tr>
                                    <td>{{ $c->name }}</td>
                                    <td><span class="badge badge-light-{{ $c->type==='benefit'?'success':'danger' }}">{{ $c->type }}</span></td>
                                    <td>{{ $c->order_index }}</td>
                                    <td class="text-end">
                                        @if ($isDraft)
                                        <button class="btn btn-light-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditCriterion"
                                            data-id="{{ $c->id }}" data-name="{{ $c->name }}" data-type="{{ $c->type }}" data-order="{{ $c->order_index }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-light-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalDeleteCriterion"
                                            data-id="{{ $c->id }}" data-name="{{ $c->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Belum ada kriteria</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h5 class="card-title mb-0">Pairwise Kriteria</h5>
                <div class="ms-auto small text-muted">Skala 1..9, reciprocal otomatis</div>
            </div>
            <div class="card-body">
                @if ($criteria->count() < 2)
                    <div class="alert alert-info">Minimal 2 kriteria diperlukan untuk mengisi matriks perbandingan.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="pairwiseTable">
                        <thead>
                            <tr>
                                <th class="bg-light"></th>
                                @foreach ($criteria as $c)
                                    <th class="bg-light text-center">{{ $c->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($criteria as $i => $ci)
                                <tr>
                                    <th class="bg-light">{{ $ci->name }}</th>
                                    @foreach ($criteria as $j => $cj)
                                        @if ($i === $j)
                                            <td class="text-center text-muted">1</td>
                                        @elseif ($i < $j)
                                            <td>
                                                <input type="number" min="0.111" step="0.001" max="9" class="form-control form-control-sm pair-input"
                                                    data-i="{{ $ci->id }}" data-j="{{ $cj->id }}" {{ $isDraft ? '' : 'readonly' }} placeholder="1..9">
                                            </td>
                                        @else
                                            <td class="text-center"><span class="reciprocal" data-i="{{ $ci->id }}" data-j="{{ $cj->id }}">-</span></td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex gap-2 mt-3">
                    @if ($isDraft)
                    <button id="btnSaveMatrix" class="btn btn-light-primary btn-sm">Simpan Matrix</button>
                    @endif
                    <button id="btnCalculate" class="btn btn-primary btn-sm">Hitung Bobot & CR</button>
                </div>

                <div class="mt-4" id="calcResult" style="display:none;">
                    <div class="alert" id="crAlert"></div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Kriteria</th><th>Bobot</th></tr></thead>
                            <tbody id="weightsBody"></tbody>
                        </table>
                    </div>
                    <div class="text-muted small" id="statsLine"></div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end mt-5">
    @if ($isDraft)
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSubmitSetup">Submit Setup (Lock)</button>
    @else
    <div class="alert alert-info py-2 px-3 mb-0">Langkah ini terkunci. Untuk mengubah, kembalikan status ke draft.</div>
    @endif
    <input type="hidden" id="periodId" value="{{ $period->id }}">
</div>

@if ($isDraft)
<!-- Modal: Create Criterion -->
<div class="modal fade" id="modalCreateCriterion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('assessment.periods.criteria.store', $period) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kriteria</h5>
                    <button type="button" class="btn btn-sm btn-icon" data-bs-dismiss="modal"><i class="ki-duotone ki-cross fs-2x"></i></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="parent_id" value="{{ $selectedCluster ?? '' }}">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipe</label>
                            <select name="type" class="form-select" required>
                                <option value="benefit">benefit</option>
                                <option value="cost">cost</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Urutan</label>
                            <input type="number" name="order_index" class="form-control" min="0" value="{{ $criteria->count() }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    </div>

<!-- Modal: Edit Criterion -->
<div class="modal fade" id="modalEditCriterion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEditCriterion">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kriteria</h5>
                    <button type="button" class="btn btn-sm btn-icon" data-bs-dismiss="modal"><i class="ki-duotone ki-cross fs-2x"></i></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipe</label>
                            <select name="type" class="form-select" required>
                                <option value="benefit">benefit</option>
                                <option value="cost">cost</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Urutan</label>
                            <input type="number" name="order_index" class="form-control" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    </div>

<!-- Modal: Delete Criterion -->
<div class="modal fade" id="modalDeleteCriterion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formDeleteCriterion">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Hapus Kriteria</h5>
                    <button type="button" class="btn btn-sm btn-icon" data-bs-dismiss="modal"><i class="ki-duotone ki-cross fs-2x"></i></button>
                </div>
                <div class="modal-body">
                    <p>Yakin hapus <b id="delCritName"></b>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </div>
            </form>
        </div>
    </div>
    </div>

<!-- Modal: Submit Setup -->
<div class="modal fade" id="modalSubmitSetup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('assessment.periods.submit_setup', $period) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Kunci Setup</h5>
                    <button type="button" class="btn btn-sm btn-icon" data-bs-dismiss="modal"><i class="ki-duotone ki-cross fs-2x"></i></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan mengunci Kriteria & Siswa. Pastikan CR ≤ 0.10 dan kandidat ≥ 2.</p>
                    <div id="currentSummary" class="small text-muted"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Lanjutkan</button>
                </div>
            </form>
        </div>
    </div>
    </div>
@endif

@push('scripts')
<script>
(function(){
    const isDraft = {{ $isDraft ? 'true' : 'false' }};
    const periodId = document.getElementById('periodId')?.value;
    const pairInputs = document.querySelectorAll('#pairwiseTable .pair-input');
    const clusterIds = @json(($criteria ?? collect())->pluck('id'));

    // Simple Bootstrap toast helper
    function showToast(message, variant = 'success'){
        const containerId = 'ahpToastContainer';
        let container = document.getElementById(containerId);
        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = 1080;
            document.body.appendChild(container);
        }
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${variant} border-0`;
        toastEl.setAttribute('role','alert');
        toastEl.setAttribute('aria-live','assertive');
        toastEl.setAttribute('aria-atomic','true');
        toastEl.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
        container.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    // Mirror reciprocal UI only (client-side)
    pairInputs.forEach(function(inp){
        inp.addEventListener('input', function(){
            const i = inp.getAttribute('data-i');
            const j = inp.getAttribute('data-j');
            const val = parseFloat(inp.value || '0');
            const rec = document.querySelector(`#pairwiseTable .reciprocal[data-i='${j}'][data-j='${i}']`);
            if (rec) {
                rec.textContent = val > 0 ? (1/val).toFixed(3) : '-';
            }
        });
    });

    // Save matrix (upsert)
    document.getElementById('btnSaveMatrix')?.addEventListener('click', function(){
        const pairs = [];
        pairInputs.forEach(function(inp){
            const v = parseFloat(inp.value || '0');
            if (v > 0) {
                pairs.push({ i_id: inp.getAttribute('data-i'), j_id: inp.getAttribute('data-j'), value: v });
            }
        });
        fetch(`{{ url('assessment/periods') }}/${periodId}/pairwise/criteria`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ pairs })
        }).then(async r => {
            const data = await r.json().catch(()=>({}));
            if (r.ok) {
                showToast(data.message || 'Matrix pairwise berhasil disimpan', 'success');
            } else {
                showToast(data.message || 'Gagal menyimpan matrix', 'danger');
            }
        }).catch(()=>{ showToast('Gagal menyimpan matrix', 'danger'); });
    });

    // Calculate using current inputs (no need to save)
    document.getElementById('btnCalculate')?.addEventListener('click', function(){
        const pairs = [];
        pairInputs.forEach(function(inp){
            const v = parseFloat(inp.value || '0');
            if (v > 0) {
                pairs.push({ i_id: inp.getAttribute('data-i'), j_id: inp.getAttribute('data-j'), value: v });
            }
        });
        fetch(`{{ url('assessment/periods') }}/${periodId}/pairwise/criteria/calculate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ pairs, ids: clusterIds })
        }).then(async r => {
            const data = await r.json().catch(()=>({}));
            const box = document.getElementById('calcResult');
            const body = document.getElementById('weightsBody');
            const crAlert = document.getElementById('crAlert');
            const stats = document.getElementById('statsLine');
            body.innerHTML = '';
            (data.weights||[]).forEach(w => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${w.name}</td><td>${(w.weight||0).toFixed(6)}</td>`;
                body.appendChild(tr);
            });
            const ok = (data.CR ?? 1) <= 0.10;
            crAlert.className = 'alert ' + (ok ? 'alert-success' : 'alert-danger');
            crAlert.textContent = 'CR: ' + (data.CR?.toFixed ? data.CR.toFixed(4) : data.CR) + ' (batas 0.10)';
            stats.textContent = `λ_max: ${Number(data.lambda_max||0).toFixed(4)} | CI: ${Number(data.CI||0).toFixed(4)}`;
            box.style.display = '';
            // Prefill modal summary
            const sum = document.getElementById('currentSummary');
            if (sum) sum.textContent = `Ringkas: ${data.weights?.length||0} kriteria, CR ${Number(data.CR||0).toFixed(4)}, kandidat ${Number({{ $candidatesCount ?? 0 }})}`;
            if ((pairs.length||0) === 0) {
                // optional lightweight hint
                crAlert.textContent += ' • Catatan: belum ada input pada matrix.';
            }
            if (r.ok) {
                const note = (pairs.length||0) > 0 ? ' (menggunakan input saat ini)' : ' (menggunakan data tersimpan)';
                showToast('Perhitungan bobot & CR berhasil' + note, ok ? 'success' : 'warning');
            } else {
                showToast(data.message || 'Gagal menghitung bobot & CR', 'danger');
            }
        }).catch(()=>{ showToast('Gagal menghitung bobot & CR', 'danger'); });
    });

    // Auto preview on load if DB already has pairwise (no need to save)
    if ({{ isset($hasPairwise) && $hasPairwise ? 'true' : 'false' }}) {
        // Prefill inputs
        fetch(`{{ url('assessment/periods') }}/${periodId}/pairwise/criteria`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
            .then(r => r.json()).then(resp => {
                const pairs = resp.pairs || [];
                pairs.forEach(row => {
                    const selector = `#pairwiseTable .pair-input[data-i='${row.i_id}'][data-j='${row.j_id}']`;
                    const inp = document.querySelector(selector);
                    if (inp) {
                        inp.value = Number(row.value || 0).toFixed(3);
                        // mirror reciprocal
                        const rec = document.querySelector(`#pairwiseTable .reciprocal[data-i='${row.j_id}'][data-j='${row.i_id}']`);
                        if (rec) rec.textContent = (1/Number(row.value || 1)).toFixed(3);
                    }
                });
            }).catch(()=>{});

        fetch(`{{ url('assessment/periods') }}/${periodId}/pairwise/criteria/calculate`, {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ ids: clusterIds })
        }).then(async r => {
            const data = await r.json().catch(()=>({}));
            const box = document.getElementById('calcResult');
            const body = document.getElementById('weightsBody');
            const crAlert = document.getElementById('crAlert');
            const stats = document.getElementById('statsLine');
            body.innerHTML = '';
            (data.weights||[]).forEach(w => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${w.name}</td><td>${(w.weight||0).toFixed(6)}</td>`;
                body.appendChild(tr);
            });
            const ok = (data.CR ?? 1) <= 0.10;
            crAlert.className = 'alert ' + (ok ? 'alert-success' : 'alert-danger');
            crAlert.textContent = 'CR: ' + (data.CR?.toFixed ? data.CR.toFixed(4) : data.CR) + ' (batas 0.10)';
            stats.textContent = `λ_max: ${Number(data.lambda_max||0).toFixed(4)} | CI: ${Number(data.CI||0).toFixed(4)}`;
            box.style.display = '';
            showToast('Menampilkan bobot & CR dari data tersimpan', 'info');
        }).catch(()=>{});
    }

    // Edit/Delete modals wiring
    const modalEdit = document.getElementById('modalEditCriterion');
    modalEdit?.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget; if (!btn) return;
        const id = btn.getAttribute('data-id');
        const form = document.getElementById('formEditCriterion');
        form.action = `{{ url('assessment/periods') }}/${periodId}/criteria/${id}`;
        modalEdit.querySelector('[name="name"]').value = btn.getAttribute('data-name') || '';
        modalEdit.querySelector('[name="type"]').value = btn.getAttribute('data-type') || 'benefit';
        modalEdit.querySelector('[name="order_index"]').value = btn.getAttribute('data-order') || '0';
    });

    const modalDelete = document.getElementById('modalDeleteCriterion');
    modalDelete?.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget; if (!btn) return;
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name') || '';
        document.getElementById('delCritName').textContent = name;
        const form = document.getElementById('formDeleteCriterion');
        form.action = `{{ url('assessment/periods') }}/${periodId}/criteria/${id}`;
    });
})();
</script>
@endpush
