@extends('layouts.master')

@section('title', 'Manage Users')

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
        <div class="card-header flex-wrap d-flex align-items-center gap-2">
            <h3 class="card-title mb-0 me-4">Daftar Pengguna</h3>
            <!-- Controls: search + add -->
            <div class="d-flex align-items-center gap-2 flex-nowrap my-3 ms-auto">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="usersSearch" class="form-control form-control-sm" placeholder="Cari kata kunci...">
                </div>
                <button id="btnAddUser" class="btn btn-primary btn-sm text-nowrap">
                    <i class="ki-duotone ki-plus fs-2"></i> Tambah Pengguna
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-semibold text-muted">
                            <th>Foto</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $u)
                            <tr data-id="{{ $u->id }}">
                                <td class="photo">
                                    @if ($u->photo_url)
                                        <img src="{{ $u->photo_url }}" alt="Foto" class="rounded" style="width:40px;height:40px;object-fit:cover;">
                                    @else
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($u->name) }}&background=random&color=fff&size=80&bold=true" alt="Avatar" class="rounded" style="width:40px;height:40px;object-fit:cover;">
                                    @endif
                                </td>
                                <td class="name">{{ $u->name }}</td>
                                <td class="email">{{ $u->email }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light-primary btnEdit" data-id="{{ $u->id }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light-danger btnDelete" data-id="{{ $u->id }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="userForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalTitle">Tambah Pengguna</h5>
                        <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ki-duotone ki-cross fs-2x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="modalLoader" class="d-none w-100 py-5 d-flex align-items-center justify-content-center">
                            <div class="spinner-border" role="status" aria-hidden="true"></div>
                            <span class="ms-3">Memuat data...</span>
                        </div>

                        <input type="hidden" id="user_id" name="id">

                        <div class="row">
                            <div class="col-md-4 mb-5">
                                <label class="form-label d-block">Foto</label>
                                <div class="image-input image-input-circle" data-kt-image-input="true"
                                    style="background-image: url('{{ asset('assets/media/svg/avatars/blank.svg') }}')">
                                    <div id="photo_wrapper" class="image-input-wrapper w-125px h-125px"></div>
                                    <label class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="change" data-bs-toggle="tooltip" data-bs-dismiss="click"
                                        title="Ubah foto">
                                        <i class="ki-duotone ki-pencil fs-6"><span class="path1"></span><span class="path2"></span></i>
                                        <input type="file" name="photo" id="photo" accept=".png, .jpg, .jpeg, .webp" />
                                        <input type="hidden" name="photo_remove" />
                                    </label>
                                    <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="remove" data-bs-toggle="tooltip" data-bs-dismiss="click" title="Hapus foto">
                                        <i class="ki-outline ki-cross fs-3"></i>
                                    </span>
                                </div>
                                <div class="invalid-feedback d-block" data-field="photo"></div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-5">
                                    <label class="form-label">Nama</label>
                                    <input type="text" name="name" id="name" class="form-control" required>
                                    <div class="invalid-feedback" data-field="name"></div>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" id="email" class="form-control" required>
                                    <div class="invalid-feedback" data-field="email"></div>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label">Password <span class="text-muted">(opsional saat edit)</span></label>
                                    <input type="password" name="password" id="password" class="form-control">
                                    <div class="invalid-feedback" data-field="password"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveUser">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <script>
        const CSRF_TOKEN = '{{ csrf_token() }}';
        const routes = {
            index: '{{ route('assessor.index') }}',
            store: '{{ route('assessor.store') }}',
            show: (id) => '{{ route('assessor.show', ['id' => 'ID_PLACEHOLDER']) }}'.replace('ID_PLACEHOLDER', id),
            update: (id) => '{{ route('assessor.update', ['id' => 'ID_PLACEHOLDER']) }}'.replace('ID_PLACEHOLDER', id),
            destroy: (id) => '{{ route('assessor.destroy', ['id' => 'ID_PLACEHOLDER']) }}'.replace('ID_PLACEHOLDER', id),
        };

        const modalEl = document.getElementById('userModal');
        const userModal = new bootstrap.Modal(modalEl);

        const form = document.getElementById('userForm');
        const userId = document.getElementById('user_id');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const photoInput = document.getElementById('photo');
        const photoWrapper = document.getElementById('photo_wrapper');
        const photoRemove = document.querySelector('input[name="photo_remove"]');

        const table = $('#usersTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            ordering: true,
            searching: true,
            columnDefs: [
                { targets: -1, className: 'text-end' }
            ]
        });

        document.getElementById('usersSearch').addEventListener('input', function() {
            table.search(this.value).draw();
        });

        function clearValidation() {
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        }

        document.getElementById('btnAddUser').addEventListener('click', () => {
            clearValidation();
            form.reset();
            userId.value = '';
            photoWrapper.style.backgroundImage = "url('{{ asset('assets/media/avatars/blank.png') }}')";
            if (photoRemove) photoRemove.value = '';
            document.getElementById('userModalTitle').textContent = 'Tambah Pengguna';
            userModal.show();
        });

        $('#usersTable').on('click', '.btnEdit', function() {
            clearValidation();
            const id = $(this).data('id');
            form.reset();
            userId.value = id;
            document.getElementById('userModalTitle').textContent = 'Edit Pengguna';
            userModal.show();

            const loader = document.getElementById('modalLoader');
            loader.classList.remove('d-none');
            [...form.elements].forEach(el => el.disabled = true);

            $.ajax({
                url: routes.show(id),
                method: 'GET',
                success: function(resp) {
                    const d = resp.data || {};
                    nameInput.value = d.name || '';
                    emailInput.value = d.email || '';
                    const uiAvatar = (name) => `https://ui-avatars.com/api/?name=${encodeURIComponent(name || 'User')}&background=random&color=fff&size=160&bold=true`;
                    const imgUrl = d.photo_url || uiAvatar(d.name);
                    photoWrapper.style.backgroundImage = `url('${imgUrl}')`;
                    if (photoRemove) photoRemove.value = '';
                },
                error: function() {
                    toastr?.error?.('Gagal memuat data pengguna. Coba lagi.');
                    userModal.hide();
                },
                complete: function() {
                    loader.classList.add('d-none');
                    [...form.elements].forEach(el => el.disabled = false);
                }
            });
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            clearValidation();

            const id = userId.value;
            const formData = new FormData();
            formData.append('name', nameInput.value);
            formData.append('email', emailInput.value);
            if (passwordInput.value) formData.append('password', passwordInput.value);
            if (photoInput.files && photoInput.files[0]) {
                formData.append('photo', photoInput.files[0]);
                if (photoRemove) photoRemove.value = '';
            }
            if (photoRemove) {
                formData.append('photo_remove', photoRemove.value);
            }
            if (id) formData.append('_method', 'PUT');

            $.ajax({
                url: id ? routes.update(id) : routes.store,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
                data: formData,
                processData: false,
                contentType: false,
                success: function(resp) {
                    const d = resp.data;
                    const uiAvatar = (name) => `https://ui-avatars.com/api/?name=${encodeURIComponent(name || 'User')}&background=random&color=fff&size=80&bold=true`;
                    const photoCell = d.photo_url ? `<img src="${d.photo_url}" class="rounded" style="width:40px;height:40px;object-fit:cover;">` : `<img src="${uiAvatar(d.name)}" class="rounded" style="width:40px;height:40px;object-fit:cover;">`;
                    if (!id) {
                        const rowNode = table.row.add([
                            photoCell,
                            d.name,
                            d.email,
                            `<button class="btn btn-sm btn-light-primary btnEdit" data-id="${d.id}"><i class="bi bi-pencil-square"></i></button>
                             <button class="btn btn-sm btn-light-danger btnDelete" data-id="${d.id}"><i class="bi bi-trash"></i></button>`
                        ]).draw(false).node();
                        $(rowNode).attr && $(rowNode).attr('data-id', d.id);
                        $(rowNode).children().eq(0).addClass('photo');
                        $(rowNode).children().eq(1).addClass('name');
                        $(rowNode).children().eq(2).addClass('email');
                    } else {
                        const $row = $(`#usersTable tr[data-id='${id}']`);
                        $row.find('.photo').html(photoCell);
                        $row.find('.name').text(d.name);
                        $row.find('.email').text(d.email);
                    }
                    userModal.hide();
                    toastr?.success?.(resp.message || 'Berhasil disimpan');
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errs = xhr.responseJSON.errors;
                        Object.keys(errs).forEach(field => {
                            const input = document.getElementById(field);
                            if (input) input.classList.add('is-invalid');
                            const fb = form.querySelector(`.invalid-feedback[data-field="${field}"]`);
                            if (fb) fb.textContent = errs[field][0];
                        });
                        return;
                    }
                    toastr?.error?.('Terjadi kesalahan. Coba lagi.');
                }
            });
        });

        $('#usersTable').on('click', '.btnDelete', function() {
            const id = $(this).data('id');
            const name = $(this).closest('tr').find('.name').text().trim();

            function performDelete() {
                $.ajax({
                    url: routes.destroy(id),
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
                    success: function(resp) {
                        const row = $(`#usersTable tr[data-id='${id}']`);
                        table.row(row).remove().draw(false);
                        if (window.Swal && Swal.fire) {
                            Swal.fire({ icon: 'success', title: 'Terhapus', text: resp.message || 'Berhasil dihapus', timer: 1500, showConfirmButton: false });
                        }
                        toastr?.success?.(resp.message || 'Berhasil dihapus');
                    },
                    error: function(xhr) {
                        const msg = xhr?.responseJSON?.message || 'Gagal menghapus. Coba lagi.';
                        if (window.Swal && Swal.fire) {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
                        }
                        toastr?.error?.(msg);
                    }
                });
            }

            if (window.Swal && Swal.fire) {
                Swal.fire({
                    title: 'Hapus pengguna?',
                    html: `Apakah Anda yakin ingin menghapus <b>${name || 'data ini'}</b>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({ title: 'Menghapus...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                        performDelete();
                    }
                });
            } else {
                if (confirm('Yakin ingin menghapus pengguna ini?')) {
                    performDelete();
                }
            }
        });
    </script>
@endpush
