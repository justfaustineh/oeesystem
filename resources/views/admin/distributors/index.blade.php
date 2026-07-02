@extends('layouts.admin')

@section('title', 'Distributors')
@section('page-title', 'Distributor Management')
@section('page-desc', 'Manage EAC distributor network across all 7 countries')

@section('content')
<div class="content-card">
    <div class="content-card-header">
        <h5>All Distributors</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reps.index') }}" class="btn btn-light"><i class="ti ti-users-group me-1"></i> Regional Reps</a>
            @can('distributors.create')
            <a href="{{ route('admin.distributors.download-template') }}" class="btn btn-light" title="Download import template">
                <i class="ti ti-download me-1"></i> Template
            </a>
            <button class="btn btn-light" id="importDistributorsBtn" title="Bulk import from Excel">
                <i class="ti ti-upload me-1"></i> Import
            </button>
            <button class="btn btn-eac-primary" id="addDistributorBtn">
                <i class="ti ti-plus me-1"></i> Add Distributor
            </button>
            @endcan
        </div>
    </div>

    <table class="table table-hover w-100" id="distributorsTable">
        <thead>
            <tr>
                <th>Company</th>
                <th>Country</th>
                <th>Contact</th>
                <th>Branches</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
    </table>
</div>

{{-- IMPORT MODAL --}}
<div class="modal fade modal-eac" id="importModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-upload me-2"></i>Import Distributors</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info border-0" style="font-size:13px">
                        <i class="ti ti-info-circle me-1"></i> 
                        <strong>Instructions:</strong> Upload an Excel file (.xlsx, .xls) or CSV with distributor data. 
                        <a href="{{ route('admin.distributors.download-template') }}" class="alert-link">Download template</a> to see the required format.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select File <span class="req">*</span></label>
                        <div class="form-control-dropzone" id="importDropzone">
                            <input type="file" id="importFile" name="file" class="form-control d-none" accept=".xlsx,.xls,.csv">
                            <div class="text-center py-5">
                                <i class="ti ti-cloud-upload" style="font-size:48px; color:#0D6E63"></i>
                                <p class="mt-3 mb-1"><strong>Drop file here or click to select</strong></p>
                                <p class="text-muted small">Excel (.xlsx, .xls) or CSV • Max 5MB</p>
                            </div>
                            <div id="fileInfo" class="mt-3" style="display:none">
                                <div class="d-flex align-items-center gap-2 p-2 bg-light rounded">
                                    <i class="ti ti-file-spreadsheet"></i>
                                    <div class="flex-grow-1">
                                        <div id="fileName" class="small"><strong></strong></div>
                                        <div id="fileSize" class="text-muted small"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-icon btn-light" id="clearFile">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-check form-switch small">
                        <input class="form-check-input" type="checkbox" id="skipErrors" name="skip_errors" value="1">
                        <label class="form-check-label" for="skipErrors">Skip rows with errors and continue import</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-eac-primary" id="importSubmitBtn">
                        <span class="btn-text"><i class="ti ti-upload me-1"></i> Import</span>
                        <span class="spinner-border spinner-border-sm d-none" id="importSpinner"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- IMPORT RESULTS MODAL --}}
<div class="modal fade modal-eac" id="importResultsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:600px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-check me-2"></i>Import Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="resultsContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ADD/EDIT MODAL --}}
<div class="modal fade modal-eac" id="distributorModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:760px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="distributorModalTitle"><i class="ti ti-building-store me-2"></i>Add Distributor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="distributorForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="distributor_id" id="distributor_id">
                <input type="hidden" name="_method" id="distributor_method" value="POST">

                <div class="modal-body" style="max-height:65vh">
                    <div class="eac-tabs">
                        <div class="eac-tab active"><span class="tab-num">1</span> Basic Info</div>
                        <div class="eac-tab"><span class="tab-num">2</span> Branches</div>
                        <div class="eac-tab"><span class="tab-num">3</span> Contacts</div>
                    </div>

                    {{-- TAB 1 --}}
                    <div class="tab-pane-eac active" data-tab="0">
                        <div class="row">
                            <div class="col-md-7 mb-3">
                                <label class="form-label">Company Name <span class="req">*</span></label>
                                <input type="text" name="company_name" class="form-control" required>
                            </div>
                            <div class="col-md-5 mb-3">
                                <label class="form-label">Trade Name</label>
                                <input type="text" name="trade_name" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Country <span class="req">*</span></label>
                                <select name="country_id" class="form-select select2-dist" required>
                                    <option value="">— Select —</option>
                                    @foreach($countries as $c)
                                        <option value="{{ $c->id }}">{{ $c->flag_emoji }} {{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select select2-dist">
                                    <option value="distributor">Distributor</option>
                                    <option value="dealer">Dealer</option>
                                    <option value="stockist">Stockist</option>
                                    <option value="agent">Agent</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select select2-dist">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control" placeholder="https://">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product Categories Carried</label>
                            <select name="categories[]" class="form-select select2-dist" multiple>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contract Start</label>
                                <input type="text" name="contract_start" class="form-control flatpickr-date">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contract End</label>
                                <input type="text" name="contract_end" class="form-control flatpickr-date">
                            </div>
                        </div>
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Logo</label>
                                <input type="file" name="logo" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="dist_is_featured">
                                    <label class="form-check-label" for="dist_is_featured" style="font-size:13px">Featured Distributor</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    {{-- TAB 2: BRANCHES --}}
                    <div class="tab-pane-eac" data-tab="1">
                        <div class="alert alert-light border" style="font-size:12.5px">
                            <i class="ti ti-info-circle me-1"></i> Save the distributor first (Tab 1), then add branches here on edit.
                        </div>
                        <div id="branchesList" class="list-group mb-3"></div>
                        <div class="card border-0 bg-light p-3 rounded-3" id="addBranchForm" style="display:none">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <input type="text" id="branchName" class="form-control form-control-sm" placeholder="Branch Name *">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <input type="text" id="branchPhone" class="form-control form-control-sm" placeholder="Phone">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <input type="text" id="branchCity" class="form-control form-control-sm" placeholder="City">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <input type="text" id="branchAddress" class="form-control form-control-sm" placeholder="Address">
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-eac-primary mt-1" id="saveBranchBtn"><i class="ti ti-plus me-1"></i> Add Branch</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-light" id="toggleBranchForm">
                            <i class="ti ti-plus me-1"></i> Add Branch
                        </button>
                    </div>

                    {{-- TAB 3: CONTACTS --}}
                    <div class="tab-pane-eac" data-tab="2">
                        <div id="contactsList" class="list-group mb-3"></div>
                        <div class="card border-0 bg-light p-3 rounded-3" id="addContactForm" style="display:none">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <input type="text" id="contactName" class="form-control form-control-sm" placeholder="Contact Name *">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <input type="text" id="contactTitle" class="form-control form-control-sm" placeholder="Title">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <input type="text" id="contactPhone" class="form-control form-control-sm" placeholder="Phone">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <input type="email" id="contactEmail" class="form-control form-control-sm" placeholder="Email">
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-eac-primary mt-1" id="saveContactBtn"><i class="ti ti-plus me-1"></i> Add Contact</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-light" id="toggleContactForm">
                            <i class="ti ti-plus me-1"></i> Add Contact
                        </button>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-light" id="distBackBtn" style="display:none">
                        <i class="ti ti-arrow-left me-1"></i> Back
                    </button>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-eac-primary" id="distNextBtn">
                            Next <i class="ti ti-arrow-right ms-1"></i>
                        </button>
                        <button type="submit" class="btn btn-eac-primary" id="distSaveBtn" style="display:none">
                            <span class="btn-text"><i class="ti ti-device-floppy me-1"></i> Save</span>
                            <span class="spinner-border spinner-border-sm d-none" id="distSaveSpinner"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- VIEW MODAL --}}
<div class="modal fade modal-eac" id="viewDistributorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:680px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-eye me-2"></i>Distributor Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewDistributorBody">
                <div class="text-center text-muted py-4">Loading...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const DIST_TABS = 3;
let currentDistributorId = null;

$(document).ready(function () {
    flatpickr('.flatpickr-date', { dateFormat: 'Y-m-d' });

    $('.select2-dist').select2({ theme: 'bootstrap-5', dropdownParent: $('#distributorModal'), width: '100%' });

    let table = $('#distributorsTable').DataTable({
        processing: true, serverSide: true,
        ajax: "{{ route('admin.distributors.data') }}",
        columns: [
            { data: 'name_col', name: 'company_name' },
            { data: 'country_col', name: 'country.name', orderable: false },
            { data: 'contact_col', name: 'phone', orderable: false },
            { data: 'branches_col', name: 'branches_count', orderable: false },
            { data: 'status_col', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ],
        order: [[0, 'asc']],
        language: { search: '', searchPlaceholder: 'Search distributors...' }
    });

    function resetDistForm() {
        $('#distributorForm')[0].reset();
        $('#distributor_id').val('');
        $('#distributor_method').val('POST');
        currentDistributorId = null;
        $('.select2-dist').val(null).trigger('change');
        $('#distributorModalTitle').html('<i class="ti ti-building-store me-2"></i>Add Distributor');
        $('#branchesList, #contactsList').empty();
        $('#addBranchForm, #addContactForm').hide();
        eacTabInit('distributorModal');
        $('#distBackBtn').hide();
        $('#distNextBtn').show();
        $('#distSaveBtn').hide();
    }

    // --- Import Functionality ---
    $('#importDistributorsBtn').on('click', function () {
        new bootstrap.Modal('#importModal').show();
    });

    // Dropzone handling
    const dropzone = $('#importDropzone');
    const fileInput = $('#importFile');
    const fileInfo = $('#fileInfo');

    dropzone.on('click', function () {
        fileInput.click();
    });

    dropzone.on('dragover', function (e) {
        e.preventDefault();
        $(this).addClass('bg-light');
    });

    dropzone.on('dragleave', function () {
        $(this).removeClass('bg-light');
    });

    dropzone.on('drop', function (e) {
        e.preventDefault();
        $(this).removeClass('bg-light');
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            fileInput[0].files = files;
            displayFileInfo(files[0]);
        }
    });

    fileInput.on('change', function () {
        if (this.files.length > 0) {
            displayFileInfo(this.files[0]);
        }
    });

    function displayFileInfo(file) {
        const size = (file.size / 1024).toFixed(2);
        $('#fileName').html('<strong>' + file.name + '</strong>');
        $('#fileSize').text(size + ' KB');
        fileInfo.show();
    }

    $('#clearFile').on('click', function () {
        fileInput.val('');
        fileInfo.hide();
    });

    $('#importForm').on('submit', function (e) {
        e.preventDefault();
        
        if (!fileInput.val()) {
            notifyWarning('Please select a file to import.');
            return;
        }

        const btn = $('#importSubmitBtn');
        btn.prop('disabled', true);
        $('#importSpinner').removeClass('d-none');

        const formData = new FormData(this);

        $.ajax({
            url: "{{ route('admin.distributors.import') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
                showImportResults(res);
                table.ajax.reload(null, false);
                $('#importForm')[0].reset();
                fileInput.val('');
                fileInfo.hide();
            },
            error: function (xhr) {
                notifyError(xhr.responseJSON?.message || 'Import failed.');
            },
            complete: function () {
                btn.prop('disabled', false);
                $('#importSpinner').addClass('d-none');
            }
        });
    });

    function showImportResults(res) {
        let html = '';
        
        if (res.status === 'success') {
            html = `
                <div class="alert alert-success border-0">
                    <i class="ti ti-check me-2"></i>
                    <strong>Success!</strong> All distributors imported successfully.
                </div>
            `;
        } else if (res.status === 'warning') {
            html = `
                <div class="alert alert-warning border-0 mb-3">
                    <i class="ti ti-alert-circle me-2"></i>
                    <strong>Partial Success:</strong> ${res.error_count} row(s) had errors and were skipped.
                </div>
                <div style="max-height:400px; overflow-y:auto">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Row</th>
                                <th>Column</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            if (res.errors && res.errors.length > 0) {
                res.errors.forEach(error => {
                    html += `
                        <tr>
                            <td><small>${error.row}</small></td>
                            <td><small>${error.column}</small></td>
                            <td><small class="text-danger">${error.message}</small></td>
                        </tr>
                    `;
                });
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
        }

        $('#resultsContent').html(html);
        new bootstrap.Modal('#importResultsModal').show();
        
        if (res.status === 'success') {
            notifySuccess(res.message);
        } else if (res.status === 'warning') {
            notifyWarning(res.message);
        }
    }

    // --- Add Distributor ---
    $('#addDistributorBtn').on('click', function () {
        resetDistForm();
        new bootstrap.Modal('#distributorModal').show();
    });

    $('#distNextBtn').on('click', function () {
        const modal = $('#distributorModal');
        let current = modal.data('current-tab') || 0;

        if (current === 0 && !$('[name="company_name"]').val()) {
            notifyWarning('Company name is required.');
            return;
        }

        if (current < DIST_TABS - 1) {
            current++;
            eacTabGoTo('distributorModal', current);
            $('#distBackBtn').show();

            if (current === DIST_TABS - 1) {
                $('#distNextBtn').hide();
                $('#distSaveBtn').show();
            }

            if (currentDistributorId) {
                if (current === 1) loadBranches();
                if (current === 2) loadContacts();
            }
        }
    });

    $('#distBackBtn').on('click', function () {
        const modal = $('#distributorModal');
        let current = modal.data('current-tab') || 0;

        if (current > 0) {
            current--;
            eacTabGoTo('distributorModal', current);
            $('#distNextBtn').show();
            $('#distSaveBtn').hide();
            if (current === 0) $('#distBackBtn').hide();
        }
    });

    $('#distributorForm').on('submit', function (e) {
        e.preventDefault();
        const btn = $('#distSaveBtn');
        btn.prop('disabled', true);
        $('#distSaveSpinner').removeClass('d-none');

        const id = $('#distributor_id').val();
        const url = id ? "{{ url('admin/distributors') }}/" + id : "{{ route('admin.distributors.store') }}";

        $.ajax({
            url: url, method: 'POST', data: new FormData(this), processData: false, contentType: false,
            success: function (res) {
                notifySuccess(res.message);
                bootstrap.Modal.getInstance(document.getElementById('distributorModal')).hide();
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                notifyError(xhr.status === 422 ? Object.values(xhr.responseJSON.errors)[0][0] : (xhr.responseJSON?.message || 'An error occurred.'));
            },
            complete: function () {
                btn.prop('disabled', false);
                $('#distSaveSpinner').addClass('d-none');
            }
        });
    });

    $('#distributorsTable').on('click', '.edit-distributor', function () {
        const id = $(this).data('id');

        $.get("{{ url('admin/distributors') }}/" + id + "/edit", function (res) {
            const d = res.distributor;
            resetDistForm();
            currentDistributorId = d.id;
            $('#distributor_id').val(d.id);
            $('#distributor_method').val('PUT');
            $('#distributorModalTitle').html('<i class="ti ti-pencil me-2"></i>Edit Distributor');

            ['company_name','trade_name','email','phone','website','city','address','notes'].forEach(f => $('[name="' + f + '"]').val(d[f]));
            $('[name="country_id"]').val(d.country_id).trigger('change');
            $('[name="type"]').val(d.type).trigger('change');
            $('[name="status"]').val(d.status).trigger('change');
            $('[name="categories[]"]').val(res.category_ids).trigger('change');
            $('[name="contract_start"]').val(d.contract_start);
            $('[name="contract_end"]').val(d.contract_end);
            $('#dist_is_featured').prop('checked', d.is_featured == 1);

            new bootstrap.Modal('#distributorModal').show();
        });
    });

    $('#distributorsTable').on('click', '.delete-distributor', function () {
        const id = $(this).data('id');
        Swal.fire({ title: 'Delete this distributor?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#E85C0D', cancelButtonColor: '#94A3B8', confirmButtonText: 'Yes, delete' })
            .then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('admin/distributors') }}/" + id, method: 'DELETE',
                        success: res => { notifySuccess(res.message); table.ajax.reload(null, false); },
                        error: xhr => notifyError(xhr.responseJSON?.message || 'Could not delete.')
                    });
                }
            });
    });

    $('#distributorsTable').on('click', '.view-distributor', function () {
        const id = $(this).data('id');
        new bootstrap.Modal('#viewDistributorModal').show();

        $.get("{{ url('admin/distributors') }}/" + id, function (res) {
            const d = res.distributor;
            const cats = d.product_categories?.map(c => `<span class="badge bg-primary-subtle text-primary me-1">${c.name}</span>`).join('') || '—';
            const branches = d.branches?.map(b => `<div class="small py-1 border-bottom"><strong>${b.name}</strong> — ${b.city || '—'} ${b.phone ? '· ' + b.phone : ''}</div>`).join('') || '—';
            const contacts = d.contacts?.map(c => `<div class="small py-1 border-bottom"><strong>${c.name}</strong> ${c.title ? '(' + c.title + ')' : ''} — ${c.phone || ''} ${c.email || ''}</div>`).join('') || '—';

            $('#viewDistributorBody').html(`
                <div class="row g-2 mb-3" style="font-size:13px">
                    <div class="col-6"><strong>Company:</strong> ${d.company_name}</div>
                    <div class="col-6"><strong>Type:</strong> ${d.type}</div>
                    <div class="col-6"><strong>Country:</strong> ${d.country?.name || '—'}</div>
                    <div class="col-6"><strong>Status:</strong> ${d.status}</div>
                    <div class="col-6"><strong>Phone:</strong> ${d.phone || '—'}</div>
                    <div class="col-6"><strong>Email:</strong> ${d.email || '—'}</div>
                    <div class="col-12"><strong>Categories:</strong> ${cats}</div>
                    <div class="col-12 mt-2"><strong>Branches:</strong>${branches}</div>
                    <div class="col-12 mt-2"><strong>Contacts:</strong>${contacts}</div>
                </div>
            `);
        });
    });

    // --- Branches ---
    function loadBranches() {
        if (!currentDistributorId) return;
        $.get("{{ url('admin/distributors') }}/" + currentDistributorId + "/branches", function (res) {
            let html = res.branches.map(b =>
                `<div class="list-group-item d-flex justify-content-between align-items-center">
                    <div><strong>${b.name}</strong><div class="text-muted small">${b.city || ''} ${b.phone || ''}</div></div>
                    <button class="btn btn-sm btn-icon btn-light text-danger delete-branch" data-id="${b.id}"><i class="ti ti-trash"></i></button>
                 </div>`
            ).join('') || '<div class="text-muted text-center py-3 small">No branches yet.</div>';
            $('#branchesList').html(html);
        });
    }

    $('#toggleBranchForm').on('click', function () { $('#addBranchForm').toggle(); });

    $('#saveBranchBtn').on('click', function () {
        if (!currentDistributorId) { notifyWarning('Save the distributor first.'); return; }
        const name = $('#branchName').val().trim();
        if (!name) { notifyWarning('Branch name is required.'); return; }

        $.post("{{ url('admin/distributors') }}/" + currentDistributorId + "/branches", {
            _token: "{{ csrf_token() }}", name: name, phone: $('#branchPhone').val(),
            city: $('#branchCity').val(), address: $('#branchAddress').val()
        }, function (res) {
            notifySuccess(res.message);
            $('#branchName, #branchPhone, #branchCity, #branchAddress').val('');
            $('#addBranchForm').hide();
            loadBranches();
        }).fail(xhr => notifyError(xhr.responseJSON?.message || 'Failed.'));
    });

    $('#branchesList').on('click', '.delete-branch', function () {
        const id = $(this).data('id');
        $.ajax({ url: "{{ url('admin/distributors/branches') }}/" + id, method: 'DELETE',
            success: res => { notifySuccess(res.message); loadBranches(); },
            error: xhr => notifyError(xhr.responseJSON?.message || 'Failed.')
        });
    });

    // --- Contacts ---
    function loadContacts() {
        if (!currentDistributorId) return;
        $.get("{{ url('admin/distributors') }}/" + currentDistributorId + "/contacts", function (res) {
            let html = res.contacts.map(c =>
                `<div class="list-group-item d-flex justify-content-between align-items-center">
                    <div><strong>${c.name}</strong> ${c.title ? '<span class="text-muted small">(' + c.title + ')</span>' : ''}
                    <div class="text-muted small">${c.phone || ''} ${c.email ? '· ' + c.email : ''}</div></div>
                    <button class="btn btn-sm btn-icon btn-light text-danger delete-contact" data-id="${c.id}"><i class="ti ti-trash"></i></button>
                 </div>`
            ).join('') || '<div class="text-muted text-center py-3 small">No contacts yet.</div>';
            $('#contactsList').html(html);
        });
    }

    $('#toggleContactForm').on('click', function () { $('#addContactForm').toggle(); });

    $('#saveContactBtn').on('click', function () {
        if (!currentDistributorId) { notifyWarning('Save the distributor first.'); return; }
        const name = $('#contactName').val().trim();
        if (!name) { notifyWarning('Contact name is required.'); return; }

        $.post("{{ url('admin/distributors') }}/" + currentDistributorId + "/contacts", {
            _token: "{{ csrf_token() }}", name: name, title: $('#contactTitle').val(),
            phone: $('#contactPhone').val(), email: $('#contactEmail').val()
        }, function (res) {
            notifySuccess(res.message);
            $('#contactName, #contactTitle, #contactPhone, #contactEmail').val('');
            $('#addContactForm').hide();
            loadContacts();
        }).fail(xhr => notifyError(xhr.responseJSON?.message || 'Failed.'));
    });

    $('#contactsList').on('click', '.delete-contact', function () {
        const id = $(this).data('id');
        $.ajax({ url: "{{ url('admin/distributors/contacts') }}/" + id, method: 'DELETE',
            success: res => { notifySuccess(res.message); loadContacts(); },
            error: xhr => notifyError(xhr.responseJSON?.message || 'Failed.')
        });
    });
});
</script>
@endpush
