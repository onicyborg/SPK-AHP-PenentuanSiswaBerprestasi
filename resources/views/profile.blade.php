@extends('layouts.master')

@push('styles')
    <style>
        .muted-note {
            font-size: .85rem;
            color: #888;
        }

        .image-input-wrapper {
            background-size: cover;
            background-position: center;
        }

        .image-input.image-input-circle .image-input-wrapper {
            border-radius: 8px;
        }

        .w-125px {
            width: 125px;
        }

        .h-125px {
            height: 125px;
        }

        .cursor-pointer {
            cursor: pointer;
        }
    </style>
@endpush

@section('content')
    <div class="row g-6 m-6">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column align-items-center text-center w-100">
                    @php
                        $photo = $user->photo_url ?? asset('assets/media/avatars/blank.png');
                        $displayName = $user->name ?? $user->email;
                    @endphp

                    <!--begin::Image input (same pattern as manage-mentors) -->
                    <div class="mb-4">
                        <div class="image-input" data-kt-image-input="true"
                            style="background-image: url('{{ asset('assets/media/svg/avatars/blank.svg') }}')">
                            <div id="profile_photo_wrapper" class="image-input-wrapper w-125px h-125px"
                                style="background-image: url('{{ $photo }}')"></div>
                            <label
                                class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                data-kt-image-input-action="change" data-bs-toggle="tooltip" data-bs-dismiss="click"
                                title="Change avatar">
                                <i class="ki-duotone ki-pencil fs-6"><span class="path1"></span><span
                                        class="path2"></span></i>
                                <input type="file" name="photo" id="profile_photo" accept=".png, .jpg, .jpeg, .webp" />
                                <input type="hidden" name="photo_remove" />
                            </label>
                            <span
                                class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                data-kt-image-input-action="cancel" data-bs-toggle="tooltip" data-bs-dismiss="click"
                                title="Cancel avatar">
                                <i class="ki-outline ki-cross fs-3"></i>
                            </span>
                            <span
                                class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                data-kt-image-input-action="remove" data-bs-toggle="tooltip" data-bs-dismiss="click"
                                title="Remove avatar">
                                <i class="ki-outline ki-cross fs-3"></i>
                            </span>
                        </div>
                        <div class="invalid-feedback d-block" data-field="photo"></div>
                    </div>
                    <!--end::Image input -->

                    <div class="fw-bold fs-4">{{ $displayName }}</div>
                    <div class="text-muted mb-6">Profil Akun</div>

                    <div class="w-100 text-start">
                        <label class="form-label">Password Akun</label>
                        <button class="btn btn-dark w-100" id="btnOpenPasswordModal" type="button">Ubah Password</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title m-0">Profil Pengguna</h3>
                </div>
                <div class="card-body">
                    <form id="profileForm">
                        @csrf
                        @method('PUT')
                        <div class="mb-6">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="{{ $user->name }}" placeholder="Nama lengkap">
                            <div class="invalid-feedback" data-field="name"></div>
                        </div>
                        <div class="mb-6">
                            <label class="form-label">Alamat Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="{{ $user->email }}" placeholder="Alamat email">
                            <div class="invalid-feedback" data-field="email"></div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <button type="submit" class="btn btn-primary" id="btnSaveProfile">Simpan</button>
                            <span class="muted-note">* Abaikan jika tidak ingin merubah data</span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Password Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="passwordForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Ubah Password</h5>
                        <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ki-duotone ki-cross fs-2x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-5">
                            <label class="form-label">Password Lama</label>
                            <input type="password" class="form-control" id="old_password" name="old_password" required>
                            <div class="invalid-feedback" data-field="old_password"></div>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required
                                minlength="8">
                            <div class="invalid-feedback" data-field="new_password"></div>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="new_password_confirmation"
                                name="new_password_confirmation" required minlength="8">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSavePassword">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const CSRF_TOKEN = '{{ csrf_token() }}';
        const routes = {
            updateProfile: '{{ route('profile.update') }}',
            updatePhoto: '{{ route('profile.photo') }}',
            changePassword: '{{ route('profile.change_password') }}',
        };

        // Helpers
        function clearValidation(scope) {
            (scope || document).querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            (scope || document).querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        }

        // Profile form submit (PUT)
        const profileForm = document.getElementById('profileForm');
        profileForm?.addEventListener('submit', function(e) {
            e.preventDefault();
            clearValidation(profileForm);
            const formData = new FormData(profileForm);
            formData.append('_method', 'PUT');
            fetch(routes.updateProfile, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: formData
            }).then(async (resp) => {
                const data = await resp.json();
                if (!resp.ok) {
                    throw {
                        status: resp.status,
                        data
                    };
                }
                window.toastr?.success?.(data.message || 'Berhasil disimpan');
            }).catch(err => {
                const errs = err?.data?.errors || {};
                Object.keys(errs).forEach(field => {
                    const input = profileForm.querySelector(`[name="${field}"]`);
                    input && input.classList.add('is-invalid');
                    const fb = profileForm.querySelector(
                        `.invalid-feedback[data-field="${field}"]`);
                    fb && (fb.textContent = errs[field][0]);
                });
                if (!Object.keys(errs).length) window.toastr?.error?.('Terjadi kesalahan.');
            });
        });

        // Auto upload photo on change
        const photoInput = document.getElementById('profile_photo');
        const photoWrapper = document.getElementById('profile_photo_wrapper');
        photoInput?.addEventListener('change', function() {
            clearValidation();
            const f = photoInput.files && photoInput.files[0];
            if (!f) return;
            const fd = new FormData();
            fd.append('photo', f);
            fetch(routes.updatePhoto, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: fd
            }).then(async (resp) => {
                const data = await resp.json();
                if (!resp.ok) {
                    throw {
                        status: resp.status,
                        data
                    };
                }
                const url = data?.data?.photo_url;
                if (url) photoWrapper.style.backgroundImage = `url('${url}')`;
                window.toastr?.success?.(data.message || 'Foto diperbarui');
            }).catch(err => {
                const errs = err?.data?.errors || {};
                const fb = document.querySelector('.invalid-feedback[data-field="photo"]');
                fb && (fb.textContent = (errs.photo && errs.photo[0]) || 'Gagal upload foto');
                window.toastr?.error?.('Gagal upload foto');
            });
        });

        // Password modal
        const passwordModalEl = document.getElementById('passwordModal');
        const passwordModal = new bootstrap.Modal(passwordModalEl);
        document.getElementById('btnOpenPasswordModal')?.addEventListener('click', () => {
            clearValidation(passwordModalEl);
            passwordModal.show();
        });

        // Change password submit
        const passwordForm = document.getElementById('passwordForm');
        passwordForm?.addEventListener('submit', function(e) {
            e.preventDefault();
            clearValidation(passwordModalEl);
            const fd = new FormData(passwordForm);
            fetch(routes.changePassword, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: fd
            }).then(async (resp) => {
                const data = await resp.json();
                if (!resp.ok) {
                    throw {
                        status: resp.status,
                        data
                    };
                }
                window.toastr?.success?.(data.message || 'Password berhasil diubah');
                passwordForm.reset();
                passwordModal.hide();
            }).catch(err => {
                const errs = err?.data?.errors || {};
                Object.keys(errs).forEach(field => {
                    const input = passwordForm.querySelector(`[name="${field}"]`);
                    input && input.classList.add('is-invalid');
                    const fb = passwordForm.querySelector(
                        `.invalid-feedback[data-field="${field}"]`);
                    fb && (fb.textContent = errs[field][0]);
                });
                if (!Object.keys(errs).length) window.toastr?.error?.('Gagal mengubah password');
            });
        });
    </script>
@endpush
