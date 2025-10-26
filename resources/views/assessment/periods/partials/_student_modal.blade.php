<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="studentForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalTitle">Tambah Siswa</h5>
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

                    <input type="hidden" id="student_id" name="id">

                    <div class="row">
                        <div class="col-md-4 mb-5">
                            <label class="form-label d-block">Foto</label>
                            <div class="image-input image-input-circle image-input-empty image-input-placeholder" data-kt-image-input="true"
                                style="background-image: url('{{ asset('assets/media/svg/avatars/blank.svg') }}')">
                                <div id="photo_wrapper" class="image-input-wrapper w-125px h-125px" style="background-image: url('{{ asset('assets/media/svg/avatars/blank.svg') }}')"></div>
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
                                <label class="form-label">NIS</label>
                                <input type="text" name="nis" id="nis" class="form-control" required>
                                <div class="invalid-feedback" data-field="nis"></div>
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Nama</label>
                                <input type="text" name="name" id="name" class="form-control" required>
                                <div class="invalid-feedback" data-field="name"></div>
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Kelas</label>
                                <input type="text" name="class" id="class" class="form-control">
                                <div class="invalid-feedback" data-field="class"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveStudent">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
