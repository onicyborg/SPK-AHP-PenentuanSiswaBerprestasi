@php
    use App\Models\Criteria;
    $isEditable = in_array($period->status, ['input','calculate']);
    // Hanya tampilkan LEAF criteria (yang tidak punya anak)
    $criteriaList = Criteria::where('period_id', $period->id)
        ->whereNotIn('id', function($q) use ($period){
            $q->select('parent_id')->from('criteria')
              ->where('period_id', $period->id)
              ->whereNotNull('parent_id');
        })
        ->orderBy('order_index')->get(['id','name','type']);
@endphp

<div class="d-flex align-items-center mb-4">
    <h4 class="mb-0">Langkah 3: Penilaian (0–100) & Calculate</h4>
    <span class="ms-3 badge badge-light-{{ $isEditable ? ($period->status==='calculate'?'info':'warning') : 'secondary' }}">Status: {{ $period->status }}</span>
    <span class="ms-3 text-muted">Nilai 0–100</span>
</div>

@if ($period->status === 'calculate' && $period->is_results_stale)
    <div class="alert alert-warning">Hasil belum disinkronkan — tekan Calculate untuk memperbarui.</div>
@endif

<div class="card mb-5">
    <div class="card-body d-flex gap-3 align-items-center flex-wrap">
        <div>
            <label class="form-label mb-1">Pilih Kriteria</label>
            <select id="criterionSelect" class="form-select" style="min-width:280px;" {{ $criteriaList->isEmpty() ? 'disabled' : '' }}>
                @foreach ($criteriaList as $c)
                    <option value="{{ $c->id }}" data-type="{{ $c->type }}">{{ $c->name }}</option>
                @endforeach
            </select>
            <div class="form-text" id="typeHint"></div>
        </div>
        <div class="ms-auto d-flex gap-2">
            @if ($isEditable)
            <button id="btnSaveScores" class="btn btn-light-primary btn-sm">Simpan Nilai</button>
            @endif
            <button id="btnCalculate" class="btn btn-primary btn-sm">Mulai Perhitungan AHP</button>
        </div>
    </div>
    <div class="card-footer py-2 small text-muted">
        - Benefit: dinormalisasi x / max(x).  - Cost: dinormalisasi min(x) / x (tidak boleh 0).
    </div>

</div>

<div class="row g-5">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2 mb-3">
                    <div class="ms-auto input-group" style="max-width:320px;">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchBox" class="form-control" placeholder="Cari nama/NIS">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th style="width:60px">No</th>
                                <th style="width:140px">NIS</th>
                                <th>Nama</th>
                                <th style="width:180px">Input Nilai (0–100)</th>
                            </tr>
                        </thead>
                        <tbody id="gridBody"></tbody>
                    </table>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <div class="text-muted small" id="paginationInfo">&nbsp;</div>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-light" id="prevPage">Prev</button>
                        <button class="btn btn-sm btn-light" id="nextPage">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex align-items-center"><h5 class="mb-0">Ringkasan Normalisasi</h5></div>
            <div class="card-body" id="summaryBox">
                <div class="text-muted">Tekan Calculate untuk melihat ringkasan.</div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="periodId" value="{{ $period->id }}">

@push('scripts')
<script>
(function(){
    const isEditable = {{ $isEditable ? 'true' : 'false' }};
    const periodId = document.getElementById('periodId').value;
    const routes = {
        list: (criterionId, search, page) => `{{ url('assessment/periods') }}/${periodId}/scores?criterion_id=${criterionId}&search=${encodeURIComponent(search||'')}&page=${page||1}`,
        batch: `{{ url('assessment/periods') }}/${periodId}/scores/batch`,
        calculate: `{{ url('assessment/periods') }}/${periodId}/scores/calculate`,
        stats: (criterionId) => `{{ url('assessment/periods') }}/${periodId}/scores/stats?criterion_id=${criterionId}`,
        completeness: `{{ url('assessment/periods') }}/${periodId}/scores/completeness`,
    };

    const els = {
        criterion: document.getElementById('criterionSelect'),
        typeHint: document.getElementById('typeHint'),
        gridBody: document.getElementById('gridBody'),
        search: document.getElementById('searchBox'),
        prev: document.getElementById('prevPage'),
        next: document.getElementById('nextPage'),
        info: document.getElementById('paginationInfo'),
        btnSave: document.getElementById('btnSaveScores'),
        btnCalc: document.getElementById('btnCalculate'),
        summary: document.getElementById('summaryBox'),
    };

    let state = { page: 1, last: 1, search: '' };
    let completenessOk = false;

    function updateTypeHint(){
        const opt = els.criterion.options[els.criterion.selectedIndex];
        const type = opt?.dataset?.type || 'benefit';
        els.typeHint.textContent = type === 'cost' ? 'Tipe: cost — nilai 0 tidak diperbolehkan' : 'Tipe: benefit';
    }

    function fetchGrid(){
        const criterionId = els.criterion.value; if (!criterionId) return;
        fetch(routes.list(criterionId, state.search, state.page)).then(r=>r.json()).then(data=>{
            renderRows(data.data||[], data.current_page||1, data.last_page||1, data.total||0, data.per_page||0);
        });
    }

    function renderRows(rows, current, last, total, perPage){
        state.page = current; state.last = last;
        els.gridBody.innerHTML = '';
        const startNo = (current-1)*(perPage||0) + 1;
        rows.forEach((r,idx)=>{
            const tr = document.createElement('tr');
            const disabled = isEditable ? '' : 'disabled';
            tr.innerHTML = `
                <td>${startNo + idx}</td>
                <td>${r.nis||''}</td>
                <td>${r.name||''}</td>
                <td>
                    <input type="number" class="form-control form-control-sm score-input" min="0" max="100" step="0.01" ${disabled}
                        data-candidate="${r.candidate_id}" value="${r.raw_value ?? ''}" data-orig="${r.raw_value ?? ''}" placeholder="0..100">
                    <div class="form-text text-warning d-none unsaved-hint">Belum disimpan</div>
                </td>`;
            els.gridBody.appendChild(tr);
        });
        els.info.textContent = `Halaman ${current} dari ${last} • Total ${total}`;
        els.prev.disabled = current<=1; els.next.disabled = current>=last;
        updateCalcState();
    }

    // Tanda input berubah vs nilai tersimpan
    els.gridBody.addEventListener('input', function(e){
        const inp = e.target.closest('.score-input'); if (!inp) return;
        const orig = inp.getAttribute('data-orig');
        const cur = inp.value;
        const changed = (orig ?? '') !== (cur ?? '');
        const hint = inp.parentElement.querySelector('.unsaved-hint');
        if (changed) { inp.classList.add('border','border-warning'); hint?.classList.remove('d-none'); }
        else { inp.classList.remove('border','border-warning'); hint?.classList.add('d-none'); }
        updateCalcState();
    });

    els.prev.addEventListener('click', ()=>{ if (state.page>1){ state.page--; fetchGrid(); }});
    els.next.addEventListener('click', ()=>{ if (state.page<state.last){ state.page++; fetchGrid(); }});
    els.search.addEventListener('input', function(){ state.search = this.value||''; state.page = 1; fetchGrid(); });
    els.criterion.addEventListener('change', ()=>{ state.page=1; updateTypeHint(); fetchGrid(); loadSummary(); checkCompleteness(); });

    els.btnSave?.addEventListener('click', function(){
        const criterionId = els.criterion.value; if (!criterionId) return;
        const items = Array.from(document.querySelectorAll('.score-input')).map(inp=>({
            candidate_id: inp.getAttribute('data-candidate'),
            raw_value: inp.value === '' ? null : Number(inp.value)
        }));
        // cost: hint—tidak boleh 0 ditangani di server juga
        fetch(routes.batch, {
            method:'POST',
            headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With':'XMLHttpRequest' },
            body: JSON.stringify({ criterion_id: criterionId, items })
        }).then(async r=>{ const d = await r.json().catch(()=>({}));
            if (r.ok) {
                // Set nilai origin = current untuk input di halaman aktif
                document.querySelectorAll('.score-input').forEach(inp=>{
                    inp.setAttribute('data-orig', inp.value);
                    inp.classList.remove('border','border-warning');
                    inp.parentElement.querySelector('.unsaved-hint')?.classList.add('d-none');
                });
                checkCompleteness();
                updateCalcState();
                // Refresh ringkasan normalisasi untuk kriteria aktif
                loadSummary();
            }
            showToast(d.message || (r.ok?'Tersimpan':'Gagal menyimpan'), r.ok?'success':'danger');
        });
    });

    els.btnCalc.addEventListener('click', async function(){
        // Menjalankan seluruh proses AHP (agregasi final) untuk SEMUA kriteria
        els.btnCalc.disabled = true; els.btnCalc.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses AHP...';
        fetch(routes.calculate, {
            method:'POST',
            headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With':'XMLHttpRequest' },
            body: JSON.stringify({})
        }).then(async r=>{
            let d={}; try{ d = await r.json(); }catch(e){}
            if (r.ok) { showToast('Perhitungan AHP selesai', 'success'); fetchGrid(); loadSummary(); }
            else { showToast(d.message || `Gagal menghitung (${r.status})`, 'danger'); }
        }).catch(()=>showToast('Gagal menghitung (network error)','danger'))
        .finally(()=>{ els.btnCalc.disabled = false; els.btnCalc.textContent = 'Mulai Perhitungan AHP'; });
    });

    async function loadSummary(){
        const criterionId = els.criterion.value; if (!criterionId) { els.summary.innerHTML = '<div class="text-muted">Pilih kriteria.</div>'; return; }
        els.summary.innerHTML = '<div class="text-muted">Memuat ringkasan...</div>';
        try {
            const r = await fetch(routes.stats(criterionId), { headers: { 'X-Requested-With':'XMLHttpRequest' }});
            const d = await r.json();
            const s = d?.data;
            if (!s) { els.summary.innerHTML = '<div class="text-muted">Belum ada hasil normalisasi untuk kriteria ini.</div>'; return; }
            els.summary.innerHTML = `
                <div class="mb-2">Method: <span class="badge badge-light">${s.method}</span></div>
                <div class="row row-cols-2 g-2">
                    <div><span class="text-muted">Min</span><div class="fw-semibold">${Number(s.min??0).toFixed(2)}</div></div>
                    <div><span class="text-muted">Max</span><div class="fw-semibold">${Number(s.max??0).toFixed(2)}</div></div>
                    <div><span class="text-muted">Mean</span><div class="fw-semibold">${Number(s.mean??0).toFixed(2)}</div></div>
                    <div><span class="text-muted">Std</span><div class="fw-semibold">${Number(s.std??0).toFixed(2)}</div></div>
                    <div><span class="text-muted">Count</span><div class="fw-semibold">${Number(s.count??0)}</div></div>
                    <div><span class="text-muted">Computed</span><div class="fw-semibold">${s.computed_at || '-'}</div></div>
                </div>`;
        } catch { els.summary.innerHTML = '<div class="text-danger">Gagal memuat ringkasan.</div>'; }
    }

    function showToast(message, variant='success'){
        const id='step3Toast'; let c=document.getElementById(id); if(!c){ c=document.createElement('div'); c.id=id; c.className='position-fixed top-0 end-0 p-3'; c.style.zIndex=1080; document.body.appendChild(c);}
        const t=document.createElement('div'); t.className=`toast align-items-center text-bg-${variant}`; t.role='alert'; t.ariaLive='assertive'; t.ariaAtomic='true'; t.innerHTML=`<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`; c.appendChild(t); new bootstrap.Toast(t,{delay:2500}).show();
    }

    // init
    updateTypeHint();
    fetchGrid();
    loadSummary();
    checkCompleteness();

    function hasUnsaved(){
        return Array.from(document.querySelectorAll('.score-input')).some(inp => (inp.getAttribute('data-orig') ?? '') !== (inp.value ?? ''));
    }
    function updateCalcState(){
        const disabled = hasUnsaved() || !completenessOk;
        els.btnCalc.disabled = disabled;
        els.btnCalc.title = disabled ? 'Lengkapi semua nilai dan simpan semua perubahan terlebih dahulu' : '';
    }
    async function checkCompleteness(){
        try {
            const r = await fetch(routes.completeness, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            const d = await r.json();
            completenessOk = !!d.complete;
        } catch { completenessOk = false; }
        updateCalcState();
    }
})();
</script>
@endpush
