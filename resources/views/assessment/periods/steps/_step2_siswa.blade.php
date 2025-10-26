@php
    $isDraft = ($period->status === 'draft');
@endphp

<div class="d-flex align-items-center mb-4">
    <h4 class="mb-0">Langkah 2: Siswa (Kandidat)</h4>
    <span class="ms-3 badge badge-light-{{ $isDraft ? 'warning' : 'secondary' }}">Status: {{ $period->status }}</span>
    @unless($isDraft)
        <span class="ms-3 text-muted">Read-only</span>
    @endunless
</div>

<div class="row g-5">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <h5 class="mb-0">Master Siswa</h5>
                <div class="ms-auto input-group w-50">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchAvailable" class="form-control" placeholder="Cari nama/NIS" {{ $isDraft ? '' : 'disabled' }}>
                </div>
                @if ($isDraft)
                <button class="btn btn-primary btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#studentModal"><center><i class="bi bi-plus-square"></i></center></button>
                @endif
            </div>
            <div class="card-body px-3 pb-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:36px" class="text-center"><input type="checkbox" id="chkAvailAll" {{ $isDraft ? '' : 'disabled' }}></th>
                                <th>NIS</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                            </tr>
                        </thead>
                        <tbody id="availBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small">Terpilih: <span id="availSelected">0</span></div>
                <div id="availPagination"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-2 d-flex flex-column align-items-center justify-content-center">
        <button id="btnAttach" class="btn btn-primary" {{ $isDraft ? 'disabled' : 'disabled' }}><i class="bi bi-arrow-right fs-2"></i></button>
        <button id="btnDetach" class="btn btn-light" {{ $isDraft ? 'disabled' : 'disabled' }}><i class="bi bi-arrow-left fs-2"></i></button>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <h5 class="mb-0">Kandidat Terpilih</h5>
                <div class="ms-auto input-group w-50">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchSelected" class="form-control" placeholder="Cari nama/NIS">
                </div>
            </div>
            <div class="card-body px-3 pb-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:36px" class="text-center"><input type="checkbox" id="chkSelAll"></th>
                                <th>NIS</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                            </tr>
                        </thead>
                        <tbody id="selBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small">Terpilih: <span id="selSelected">0</span></div>
                <div id="selPagination"></div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end mt-5">
    @if ($isDraft)
    <form method="POST" action="{{ route('assessment.periods.submit_setup', $period) }}">
        @csrf
        <button class="btn btn-primary">Submit Setup (Lock)</button>
    </form>
    @else
    <div class="alert alert-info py-2 px-3 mb-0">Langkah ini terkunci karena data sudah di submit.</div>
    @endif
    <input type="hidden" id="periodId" value="{{ $period->id }}">
</div>

@include('assessment.periods.partials._student_modal')

@push('scripts')
<script>
(function(){
    const periodId = document.getElementById('periodId').value;
    const isDraft = {{ $isDraft ? 'true' : 'false' }};
    const routes = {
        available: (q, page=1) => `{{ url('assessment/periods') }}/${periodId}/candidates/available?search=${encodeURIComponent(q||'')}&page=${page}`,
        selected: (q, page=1) => `{{ url('assessment/periods') }}/${periodId}/candidates/selected?search=${encodeURIComponent(q||'')}&page=${page}`,
        attach: `{{ url('assessment/periods') }}/${periodId}/candidates/attach`,
        detach: `{{ url('assessment/periods') }}/${periodId}/candidates/detach`,
        studentStore: `{{ route('siswa.store') }}`,
    };

    const avail = { q:'', page:1 }; const sel = { q:'', page:1 };
    const el = {
        availBody: document.getElementById('availBody'),
        selBody: document.getElementById('selBody'),
        chkAvailAll: document.getElementById('chkAvailAll'),
        chkSelAll: document.getElementById('chkSelAll'),
        btnAttach: document.getElementById('btnAttach'),
        btnDetach: document.getElementById('btnDetach'),
        availSelCount: document.getElementById('availSelected'),
        selSelCount: document.getElementById('selSelected'),
        searchAvailable: document.getElementById('searchAvailable'),
        searchSelected: document.getElementById('searchSelected'),
        availPagination: document.getElementById('availPagination'),
        selPagination: document.getElementById('selPagination'),
    };

    // Enable buttons only in draft
    if (isDraft) { el.btnAttach.removeAttribute('disabled'); el.btnDetach.removeAttribute('disabled'); }

    function renderRows(container, rows, side){
        container.innerHTML = '';
        rows.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center"><input type="checkbox" class="rowchk" data-id="${r.id}"></td>
                <td>${r.nis||''}</td>
                <td>${r.name||''}</td>
                <td>${r.class||''}</td>`;
            container.appendChild(tr);
        });
        updateCounts();
    }

    function renderPagination(container, meta, onClick){
        container.innerHTML = '';
        const { current_page, last_page } = meta;
        const wrap = document.createElement('div');
        wrap.className = 'd-flex align-items-center';
        const prev = document.createElement('button'); prev.className='btn btn-sm btn-light'; prev.textContent='Prev'; prev.disabled = current_page<=1; prev.onclick = ()=> onClick(current_page-1);
        const label = document.createElement('span'); label.className='mx-2 small text-muted'; label.textContent = `Page ${current_page}/${last_page}`;
        const next = document.createElement('button'); next.className='btn btn-sm btn-light'; next.textContent='Next'; next.disabled = current_page>=last_page; next.onclick = ()=> onClick(current_page+1);
        wrap.append(prev,label,next); container.appendChild(wrap);
    }

    function fetchAvailable(){
        fetch(routes.available(avail.q, avail.page)).then(r=>r.json()).then(json=>{
            renderRows(el.availBody, json.data, 'avail');
            renderPagination(el.availPagination, json, (p)=>{ avail.page=p; fetchAvailable(); });
        });
    }
    function fetchSelected(){
        fetch(routes.selected(sel.q, sel.page)).then(r=>r.json()).then(json=>{
            renderRows(el.selBody, json.data, 'sel');
            renderPagination(el.selPagination, json, (p)=>{ sel.page=p; fetchSelected(); });
        });
    }

    function getCheckedIds(container){
        return Array.from(container.querySelectorAll('input.rowchk:checked')).map(i=>i.getAttribute('data-id'));
    }
    function updateCounts(){
        el.availSelCount.textContent = el.availBody.querySelectorAll('input.rowchk:checked').length;
        el.selSelCount.textContent = el.selBody.querySelectorAll('input.rowchk:checked').length;
    }

    el.chkAvailAll?.addEventListener('change', function(){
        el.availBody.querySelectorAll('input.rowchk').forEach(i=>{ i.checked = el.chkAvailAll.checked; }); updateCounts();
    });
    el.chkSelAll?.addEventListener('change', function(){
        el.selBody.querySelectorAll('input.rowchk').forEach(i=>{ i.checked = el.chkSelAll.checked; }); updateCounts();
    });
    el.availBody.addEventListener('change', updateCounts);
    el.selBody.addEventListener('change', updateCounts);

    function debounce(fn, ms){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; }
    el.searchAvailable?.addEventListener('input', debounce(function(e){ avail.q = e.target.value; avail.page=1; fetchAvailable(); }, 300));
    el.searchSelected?.addEventListener('input', debounce(function(e){ sel.q = e.target.value; sel.page=1; fetchSelected(); }, 300));

    el.btnAttach?.addEventListener('click', function(){
        const ids = getCheckedIds(el.availBody); if (ids.length===0) return;
        fetch(routes.attach, { method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ student_ids: ids }) })
            .then(r=>r.json()).then(()=>{ fetchAvailable(); fetchSelected(); showToast('Kandidat ditambahkan','success'); });
    });
    el.btnDetach?.addEventListener('click', function(){
        const ids = getCheckedIds(el.selBody); if (ids.length===0) return;
        fetch(routes.detach, { method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ student_ids: ids }) })
            .then(r=>r.json()).then(()=>{ fetchAvailable(); fetchSelected(); showToast('Kandidat dihapus','success'); });
    });

    function showToast(message, variant='success'){
        const id='step2Toast'; let c=document.getElementById(id); if(!c){ c=document.createElement('div'); c.id=id; c.className='position-fixed top-0 end-0 p-3'; c.style.zIndex=1080; document.body.appendChild(c);}
        const t=document.createElement('div'); t.className=`toast align-items-center text-bg-${variant}`; t.role='alert'; t.ariaLive='assertive'; t.ariaAtomic='true'; t.innerHTML=`<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`; c.appendChild(t); new bootstrap.Toast(t,{delay:2500}).show();
    }

    // Modal tambah siswa (reuse dari manage-siswa)
    const studentModal = document.getElementById('studentModal');
    const studentForm = document.getElementById('studentForm');
    studentForm?.addEventListener('submit', function(e){
        e.preventDefault(); if(!isDraft) return;
        const fd = new FormData(studentForm);
        fetch(routes.studentStore, { method:'POST', headers:{ 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: fd })
            .then(async r=>{ const js = await r.json().catch(()=>null); if(!r.ok){ throw js; } return js; })
            .then(()=>{ bootstrap.Modal.getInstance(studentModal)?.hide(); studentForm.reset(); fetchAvailable(); showToast('Siswa ditambahkan','success'); })
            .catch(err=>{ showToast(err?.message||'Gagal menambahkan siswa','danger'); });
    });

    fetchAvailable(); fetchSelected();
})();
</script>
@endpush
