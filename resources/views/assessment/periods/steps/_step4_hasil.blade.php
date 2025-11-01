@php
    $isEditable = in_array($period->status, ['input', 'calculate']);
@endphp

<div class="d-flex align-items-center mb-4">
    <h4 class="mb-0">Langkah 4: Hasil Perhitungan AHP</h4>
    <span class="ms-3 badge badge-light-{{ $period->is_results_stale ? 'warning' : 'success' }}">
        {{ $period->is_results_stale ? 'Perlu dihitung ulang' : 'Up-to-date' }}
    </span>
    <span class="ms-3 text-muted small">Terakhir dihitung:
        {{ optional($period->last_calculated_at)->format('Y-m-d H:i') ?? '-' }}</span>
    <input type="hidden" id="periodId" value="{{ $period->id }}">
    @csrf
    <button id="btnStartAhp" class="btn btn-sm btn-danger ms-auto" data-finalized="{{ $period->status === 'finalized' ? 1 : 0 }}" {{ $period->status === 'finalized' ? 'disabled' : '' }}>Finalisasi Perhitungan</button>
</div>

<style>
  .badge-rank-1{ background: linear-gradient(90deg,#D4AF37,#FFD700); color:#1f1f1f; }
  .badge-rank-2{ background: linear-gradient(90deg,#C0C0C0,#E0E0E0); color:#1f1f1f; }
  .badge-rank-3{ background: linear-gradient(90deg,#CD7F32,#DFA86A); color:#ffffff; }
  .badge-rank-other{ background:#6c757d; color:#ffffff; }
</style>

<div class="row g-5">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Ranking Siswa</h5>
                <div class="input-group" style="max-width:320px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchBox" class="form-control" placeholder="Cari nama/NIS">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th style="width:80px">Rank</th>
                                <th style="width:140px">NIS</th>
                                <th>Nama</th>
                                <th style="width:120px">Kelas</th>
                                <th style="width:160px">Skor Akhir</th>
                                <th style="width:120px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="resultsBody"></tbody>
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
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Bobot Kriteria</h5>
            </div>
            <div class="card-body" id="weightsBox">
                <div class="text-muted">Memuat bobot...</div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-nowrap gap-2">
                <h5 class="mb-0 text-truncate" style="min-width:0">Ringkasan Normalisasi</h5>
                <select id="criterionSelect" class="form-select form-select-sm ms-2" style="max-width: 240px; flex:0 0 auto;"></select>
            </div>
            <div class="card-body" id="summaryBox">
                <div class="text-muted">Pilih kriteria untuk melihat statistik.</div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Proses Normalisasi</h5>
                <div class="text-muted small">Pilih kriteria leaf untuk melihat detail</div>
            </div>
            <div class="card-body">
                <div id="normDetailBox"><div class="text-muted">Belum ada kriteria dipilih.</div></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Breakdown -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Kontribusi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detailHeader" class="mb-3 small text-muted"></div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Kriteria</th>
                                <th class="text-center" style="width:120px">Tipe</th>
                                <th class="text-end" style="width:140px">Nilai Raw</th>
                                <th class="text-end" style="width:160px">Normalisasi</th>
                                <th class="text-end" style="width:140px">Bobot</th>
                                <th class="text-end" style="width:160px">Kontribusi</th>
                            </tr>
                        </thead>
                        <tbody id="breakdownBody"></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end">Total</th>
                                <th class="text-end" id="totalContribution">0.0000</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            const periodId = document.getElementById('periodId').value;
            const csrf = document.querySelector('input[name=_token]')?.value || '{{ csrf_token() }}';
            const els = {
                search: document.getElementById('searchBox'),
                resultsBody: document.getElementById('resultsBody'),
                prev: document.getElementById('prevPage'),
                next: document.getElementById('nextPage'),
                info: document.getElementById('paginationInfo'),
                weightsBox: document.getElementById('weightsBox'),
                critSelect: document.getElementById('criterionSelect'),
                summary: document.getElementById('summaryBox'),
                btnStartAhp: document.getElementById('btnStartAhp'),
            };

            const routes = {
                results: (search, page) =>
                    `{{ url('assessment/periods') }}/${periodId}/results?search=${encodeURIComponent(search||'')}&page=${page||1}`,
                breakdown: (candidateId) =>
                    `{{ url('assessment/periods') }}/${periodId}/results/${candidateId}/breakdown`,
                weights: `{{ url('assessment/periods') }}/${periodId}/weights/roots`,
                criteria: `{{ url('assessment/periods') }}/${periodId}/criteria`,
                stats: (criterionId) =>
                    `{{ url('assessment/periods') }}/${periodId}/scores/stats?criterion_id=${criterionId}`,
                childrenStats: (parentId) =>
                    `{{ url('assessment/periods') }}/${periodId}/criteria/${parentId}/children/stats`,
                calculate: `{{ url('assessment/periods') }}/${periodId}/scores/calculate`,
                finalize: `{{ url('assessment/periods') }}/${periodId}/finalize`,
                normDetails: (criterionId) => `{{ url('assessment/periods') }}/${periodId}/scores/details?criterion_id=${criterionId}`,
                completeness: `{{ url('assessment/periods') }}/${periodId}/scores/completeness`,
            };

            let state = {
                page: 1,
                last: 1,
                search: ''
            };
            let criteriaCache = [];

            function renderResults(items, current, last, total) {
                els.resultsBody.innerHTML = '';
                if (!items.length) {
                    els.resultsBody.innerHTML =
                        '<tr><td colspan="6" class="text-center text-muted">Belum ada hasil. Jalankan perhitungan AHP.</td></tr>';
                }
                items.forEach(r => {
                    const tr = document.createElement('tr');
                    let rankClass = 'badge-rank-other';
                    if (r.rank === 1) rankClass = 'badge-rank-1';
                    else if (r.rank === 2) rankClass = 'badge-rank-2';
                    else if (r.rank === 3) rankClass = 'badge-rank-3';
                    const rankBadge = `<span class="badge ${rankClass}">${r.rank}</span>`;
                    tr.innerHTML = `
        <td>${rankBadge}</td>
        <td>${r.nis||''}</td>
        <td>${r.name||''}</td>
        <td>${r.class||''}</td>
        <td class="text-end">${Number(r.final_score??0).toFixed(6)}</td>
        <td><button class="btn btn-sm btn-outline-primary" data-candidate="${r.candidate_id}">Detail</button></td>`;
                    els.resultsBody.appendChild(tr);
                });
                els.info.textContent = `Halaman ${current} dari ${last} • Total ${total}`;
                els.prev.disabled = current <= 1;
                els.next.disabled = current >= last;
            }

            function fetchResults() {
                fetch(routes.results(state.search, state.page))
                    .then(r => r.json())
                    .then(d => {
                        renderResults(d.data || [], d.current_page || 1, d.last_page || 1, d.total || 0);
                    });
            }

            async function loadCriteria() {
                try {
                    const r = await fetch(routes.criteria);
                    const d = await r.json();
                    criteriaCache = d.data || [];
                    els.critSelect.innerHTML = criteriaCache
                        .map(c =>
                            `<option value="${c.id}" data-parent="${c.is_parent?1:0}">${c.is_parent?'[Parent] ':''}${c.name}</option>`
                            )
                        .join('');
                } catch {
                    els.summary.innerHTML = '<div class="text-danger">Gagal memuat daftar kriteria.</div>';
                }
            }

            async function fetchWeights() {
                els.weightsBox.innerHTML = '<div class="text-muted">Memuat bobot...</div>';
                try {
                    const r = await fetch(routes.weights);
                    const d = await r.json();
                    const roots = d.data || []; // [{id,name,total_weight}]
                    if (!roots.length) {
                        els.weightsBox.innerHTML = '<div class="text-muted">Belum ada bobot kriteria.</div>';
                        return;
                    }
                    const maxW = Math.max(...roots.map(x => Number(x.total_weight || 0)));
                    const scale = maxW > 0 ? (100 / maxW) : 0;
                    els.weightsBox.innerHTML = roots.map(x => {
                        const w = Number(x.total_weight || 0);
                        const bar = Math.round(w * scale);
                        return `<div class="mb-2">
          <div class="d-flex justify-content-between small"><span>${x.name}</span><span>${w.toFixed(4)}</span></div>
          <div class="progress" style="height:6px;"><div class="progress-bar" role="progressbar" style="width:${bar}%"></div></div>
        </div>`
                    }).join('');
                } catch {
                    els.weightsBox.innerHTML = '<div class="text-danger">Gagal memuat bobot.</div>';
                }
            }

            async function loadSummary() {
                const cid = els.critSelect.value;
                if (!cid) {
                    els.summary.innerHTML = '<div class="text-muted">Pilih kriteria.</div>';
                    return;
                }
                const isParent = els.critSelect.selectedOptions[0]?.dataset?.parent === '1';
                els.summary.innerHTML = '<div class="text-muted">Memuat ringkasan...</div>';
                try {
                    if (isParent) {
                        const r = await fetch(routes.childrenStats(cid), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const d = await r.json();
                        const rows = d?.data || [];
                        if (!rows.length) {
                            els.summary.innerHTML =
                                '<div class="text-muted">Parent ini tidak memiliki child leaf atau belum ada normalisasi.</div>';
                            return;
                        }
                        els.summary.innerHTML = `
          <div class="mb-2">Parent: <span class="badge badge-light">${els.critSelect.selectedOptions[0].textContent}</span></div>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Child</th>
                  <th>Method</th>
                  <th class="text-end">Min</th>
                  <th class="text-end">Max</th>
                  <th class="text-end">Mean</th>
                  <th class="text-end">Std</th>
                  <th class="text-end">Count</th>
                  <th>Computed</th>
                </tr>
              </thead>
              <tbody>
                ${rows.map(s=>`
                      <tr>
                        <td>${s.name}</td>
                        <td><span class="badge badge-light">${s.method||'-'}</span></td>
                        <td class="text-end">${s.min===null?'-':Number(s.min).toFixed(2)}</td>
                        <td class="text-end">${s.max===null?'-':Number(s.max).toFixed(2)}</td>
                        <td class="text-end">${s.mean===null?'-':Number(s.mean).toFixed(2)}</td>
                        <td class="text-end">${s.std===null?'-':Number(s.std).toFixed(2)}</td>
                        <td class="text-end">${s.count??0}</td>
                        <td>${s.computed_at||'-'}</td>
                      </tr>`).join('')}
              </tbody>
            </table>
          </div>`;
                        // kosongkan detail proses untuk parent
                        document.getElementById('normDetailBox').innerHTML = '<div class="text-muted">Pilih kriteria leaf untuk melihat proses normalisasi.</div>';
                    } else {
                        const r = await fetch(routes.stats(cid), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const d = await r.json();
                        const s = d?.data;
                        if (!s) {
                            els.summary.innerHTML = '<div class="text-muted">Belum ada hasil normalisasi.</div>';
                            return;
                        }
                        els.summary.innerHTML = `
          <div class="mb-2">Kriteria: <span class="badge badge-light">${els.critSelect.selectedOptions[0].textContent}</span></div>
          <div class="row row-cols-2 g-2">
            <div><span class="text-muted">Method</span><div class="fw-semibold">${s.method}</div></div>
            <div><span class="text-muted">Computed</span><div class="fw-semibold">${s.computed_at || '-'}</div></div>
            <div><span class="text-muted">Min</span><div class="fw-semibold">${Number(s.min??0).toFixed(2)}</div></div>
            <div><span class="text-muted">Max</span><div class="fw-semibold">${Number(s.max??0).toFixed(2)}</div></div>
            <div><span class="text-muted">Mean</span><div class="fw-semibold">${Number(s.mean??0).toFixed(2)}</div></div>
            <div><span class="text-muted">Std</span><div class="fw-semibold">${Number(s.std??0).toFixed(2)}</div></div>
            <div><span class="text-muted">Count</span><div class="fw-semibold">${Number(s.count??0)}</div></div>
          </div>`;
                        // muat detail proses normalisasi lengkap
                        await loadNormalizationDetails(cid);
                    }
                } catch {
                    els.summary.innerHTML = '<div class="text-danger">Gagal memuat ringkasan.</div>';
                }
            }

            async function loadNormalizationDetails(criterionId){
                const box = document.getElementById('normDetailBox');
                box.innerHTML = '<div class="text-muted">Memuat detail normalisasi...</div>';
                try{
                    const r = await fetch(routes.normDetails(criterionId), { headers: { 'X-Requested-With':'XMLHttpRequest' }});
                    const payload = await r.json();
                    if (!r.ok){ box.innerHTML = `<div class=\"text-danger\">${payload.message||'Gagal memuat detail'}</div>`; return; }
                    const d = payload.data;
                    const method = d.method;
                    const formula = d.formula;
                    const params = JSON.stringify(d.params||{});
                    const stat = d.stat||{};
                    const rows = d.rows||[];
                    const headerHtml = `
                      <div class="mb-3">
                        <div><strong>Kriteria:</strong> ${d.criterion.name} (${d.criterion.type})</div>
                        <div><strong>Metode:</strong> <code>${method}</code></div>
                        <div><strong>Rumus:</strong> <code>${formula}</code></div>
                        <div class="small text-muted">Parameter: ${params}</div>
                      </div>
                      <div class="row g-3 mb-3">
                        <div class="col-auto"><span class="badge bg-light text-dark">min: ${stat.min??'-'}</span></div>
                        <div class="col-auto"><span class="badge bg-light text-dark">max: ${stat.max??'-'}</span></div>
                        <div class="col-auto"><span class="badge bg-light text-dark">mean: ${stat.mean??'-'}</span></div>
                        <div class="col-auto"><span class="badge bg-light text-dark">std: ${stat.std??'-'}</span></div>
                        <div class="col-auto"><span class="badge bg-light text-dark">n: ${stat.count??0}</span></div>
                      </div>`;
                    const tableHtml = `
                      <div class="table-responsive">
                        <table class="table table-sm align-middle">
                          <thead>
                            <tr>
                              <th style=\"width:140px\">NIS</th>
                              <th>Nama</th>
                              <th style=\"width:120px\">Kelas</th>
                              <th style=\"width:160px\">Nilai Asli (x)</th>
                              <th style=\"width:180px\">Nilai Ternormalisasi (nv)</th>
                            </tr>
                          </thead>
                          <tbody>
                            ${rows.map(row=>{
                              const raw = (row.raw_value??null)===null? '-' : Number(row.raw_value).toFixed(4);
                              const nv = (row.normalized_value??null)===null? '-' : Number(row.normalized_value).toFixed(6);
                              return `<tr><td>${row.nis}</td><td>${row.name}</td><td>${row.class}</td><td>${raw}</td><td>${nv}</td></tr>`;
                            }).join('')}
                          </tbody>
                        </table>
                      </div>`;
                    box.innerHTML = headerHtml + tableHtml;
                } catch {
                    box.innerHTML = '<div class="text-danger">Gagal memuat detail normalisasi.</div>';
                }
            }

            async function openDetail(candidateId, header) {
                const r = await fetch(routes.breakdown(candidateId));
                const d = await r.json();
                const rows = d.data || [];
                document.getElementById('detailHeader').textContent = header;
                const tbody = document.getElementById('breakdownBody');
                tbody.innerHTML = '';
                let total = 0.0;
                rows.forEach(x => {
                    const contrib = Number(x.contribution || 0);
                    total += contrib;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
        <td>${x.criterion_name}</td>
        <td class="text-center"><span class="badge ${x.criterion_type==='cost'?'bg-danger-subtle text-danger':'bg-success-subtle text-success'}">${x.criterion_type}</span></td>
        <td class="text-end">${x.raw_value===null?'-':Number(x.raw_value).toFixed(2)}</td>
        <td class="text-end">${Number(x.normalized_value??0).toFixed(6)}</td>
        <td class="text-end">${Number(x.weight??0).toFixed(4)}</td>
        <td class="text-end">${contrib.toFixed(6)}</td>`;
                    tbody.appendChild(tr);
                });
                document.getElementById('totalContribution').textContent = total.toFixed(6);
                new bootstrap.Modal(document.getElementById('detailModal')).show();
            }

            // Events
            els.prev.addEventListener('click', () => {
                if (state.page > 1) {
                    state.page--;
                    fetchResults();
                }
            });
            els.next.addEventListener('click', () => {
                state.page++;
                fetchResults();
            });
            els.search.addEventListener('input', function() {
                state.search = this.value || '';
                state.page = 1;
                fetchResults();
            });
            els.critSelect.addEventListener('change', loadSummary);
            els.resultsBody.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-candidate]');
                if (!btn) return;
                const tr = btn.closest('tr');
                const header =
                    `${tr.children[2].textContent} • NIS ${tr.children[1].textContent} • Rank ${tr.children[0].textContent}`;
                openDetail(btn.getAttribute('data-candidate'), header);
            });
            els.btnStartAhp?.addEventListener('click', async () => {
                const proceed = window.Swal ? await window.Swal.fire({
                    title: 'Finalisasi Periode?',
                    text: 'Setelah finalisasi, seluruh langkah akan menjadi readonly dan tidak bisa diubah.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Finalisasi',
                    cancelButtonText: 'Batal'
                }).then(res => res.isConfirmed) : window.confirm('Finalisasi periode? Semua data akan dikunci.');
                if (!proceed) return;
                const btn = els.btnStartAhp;
                btn.disabled = true;
                const old = btn.textContent;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memfinalisasi...';
                try {
                    const r = await fetch(routes.finalize, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({})
                    });
                    const d = await r.json().catch(() => ({}));
                    if (r.ok) {
                        showToast('Periode difinalisasi');
                        location.reload();
                    } else {
                        showToast(d.message || 'Gagal finalisasi', 'danger');
                    }
                } catch {
                    showToast('Gagal finalisasi (network error)', 'danger');
                } finally {
                    btn.disabled = false;
                    btn.textContent = old;
                }
            });
            // refresh handled implicitly after actions; no manual refresh button

            function showToast(message, variant = 'success') {
                const id = 'step4Toast';
                let c = document.getElementById(id);
                if (!c) {
                    c = document.createElement('div');
                    c.id = id;
                    c.className = 'position-fixed top-0 end-0 p-3';
                    c.style.zIndex = 1080;
                    document.body.appendChild(c);
                }
                const t = document.createElement('div');
                t.className = `toast align-items-center text-bg-${variant}`;
                t.role = 'alert';
                t.ariaLive = 'assertive';
                t.ariaAtomic = 'true';
                t.innerHTML =
                    `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
                c.appendChild(t);
                new bootstrap.Toast(t, {
                    delay: 2500
                }).show();
            }

            async function updateFinalizeButton(){
                const btn = els.btnStartAhp;
                if (!btn) return;
                const isFinalized = btn.dataset.finalized === '1';
                if (isFinalized){ btn.disabled = true; btn.title = 'Periode sudah difinalisasi'; return; }
                try{
                    const r = await fetch(routes.completeness, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
                    const d = await r.json();
                    const complete = !!d.complete;
                    btn.disabled = !complete;
                    btn.title = complete ? '' : `Belum lengkap: ${d.missing ?? ''} nilai belum diisi`;
                } catch {
                    // Jika gagal cek, jangan blokir; biarkan admin yang memutuskan
                    btn.disabled = false;
                    btn.title = '';
                }
            }

            // init
            fetchResults();
            loadCriteria().then(() => {
                loadSummary();
                fetchWeights();
                updateFinalizeButton();
            });
        })();
    </script>
@endpush
