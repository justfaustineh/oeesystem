<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDistributorRequest;
use App\Http\Requests\Admin\ImportDistributorsRequest;
use App\Imports\DistributorImport;
use App\Exports\DistributorTemplateExport;
use App\Models\Country;
use App\Models\Distributor;
use App\Models\DistributorBranch;
use App\Models\DistributorContact;
use App\Models\ProductCategory;
use App\Models\Region;
use App\Services\SecurityNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class DistributorController extends Controller
{
    public function index()
    {
        $countries = Country::active()->orderBy('name')->get();
        $categories = ProductCategory::active()->orderBy('name')->get();
        $regions = Region::orderBy('name')->get();

        return view('admin.distributors.index', compact('countries', 'categories', 'regions'));
    }

    public function data(Request $request)
    {
        $query = Distributor::with(['country', 'region'])->withCount(['branches', 'contacts'])->select('distributors.*');

        return DataTables::of($query)
            ->addColumn('name_col', function ($d) {
                $logo = $d->logo_path
                    ? '<img src="' . asset('storage/' . $d->logo_path) . '" width="32" height="32" class="rounded-circle me-2" style="object-fit:cover">'
                    : '<span class="avatar-placeholder me-2"><i class="ti ti-building-store"></i></span>';
                $featured = $d->is_featured ? '<span class="badge bg-warning-subtle text-warning ms-1"><i class="ti ti-star"></i></span>' : '';

                return $logo . '<strong>' . e($d->company_name) . '</strong>' . $featured
                    . '<div class="text-muted small">' . ucfirst($d->type) . '</div>';
            })
            ->addColumn('country_col', fn ($d) => ($d->country->flag_emoji ?? '') . ' ' . ($d->country->name ?? '—'))
            ->addColumn('contact_col', function ($d) {
                return '<div class="small">' . ($d->phone ? '<i class="ti ti-phone me-1"></i>' . e($d->phone) . '<br>' : '')
                    . ($d->email ? '<i class="ti ti-mail me-1"></i>' . e($d->email) : '') . '</div>';
            })
            ->addColumn('branches_col', fn ($d) => '<span class="badge bg-info-subtle text-info">' . $d->branches_count . ' branches</span>')
            ->addColumn('status_col', function ($d) {
                $map = ['active' => 'success', 'inactive' => 'secondary', 'suspended' => 'danger'];
                $color = $map[$d->status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '-subtle text-' . $color . '">' . ucfirst($d->status) . '</span>';
            })
            ->addColumn('actions', function ($d) {
                $view = '<button class="btn btn-sm btn-icon btn-light view-distributor" data-id="' . $d->id . '" title="View"><i class="ti ti-eye"></i></button>';
                $edit = '<button class="btn btn-sm btn-icon btn-light edit-distributor" data-id="' . $d->id . '" title="Edit"><i class="ti ti-pencil"></i></button>';
                $delete = '<button class="btn btn-sm btn-icon btn-light text-danger delete-distributor" data-id="' . $d->id . '" title="Delete"><i class="ti ti-trash"></i></button>';

                return '<div class="d-flex gap-1">' . $view . $edit . $delete . '</div>';
            })
            ->rawColumns(['name_col', 'country_col', 'contact_col', 'branches_col', 'status_col', 'actions'])
            ->make(true);
    }

    public function store(StoreDistributorRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->except(['categories', 'logo', '_token']);
            $data['is_featured'] = $request->boolean('is_featured');

            if ($request->hasFile('logo')) {
                $data['logo_path'] = $request->file('logo')->store('distributors/logos', 'public');
            }

            $distributor = Distributor::create($data);
            $distributor->productCategories()->sync($request->input('categories', []));

            SecurityNotifier::logEvent(auth()->user(), 'Distributor Created', ['Distributor' => $distributor->company_name]);

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Distributor created successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function show(Distributor $distributor)
    {
        $distributor->load(['country', 'region', 'branches', 'contacts', 'productCategories']);

        return response()->json(['status' => 'success', 'distributor' => $distributor]);
    }

    public function edit(Distributor $distributor)
    {
        $distributor->load(['branches', 'contacts', 'productCategories']);

        return response()->json([
            'status' => 'success',
            'distributor' => $distributor,
            'category_ids' => $distributor->productCategories->pluck('id'),
        ]);
    }

    public function update(StoreDistributorRequest $request, Distributor $distributor)
    {
        DB::beginTransaction();

        try {
            $data = $request->except(['categories', 'logo', '_token', '_method']);
            $data['is_featured'] = $request->boolean('is_featured');

            if ($request->hasFile('logo')) {
                if ($distributor->logo_path) Storage::disk('public')->delete($distributor->logo_path);
                $data['logo_path'] = $request->file('logo')->store('distributors/logos', 'public');
            }

            $distributor->update($data);
            $distributor->productCategories()->sync($request->input('categories', []));

            SecurityNotifier::logEvent(auth()->user(), 'Distributor Updated', ['Distributor' => $distributor->company_name]);

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Distributor updated successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Distributor $distributor)
    {
        if ($distributor->logo_path) Storage::disk('public')->delete($distributor->logo_path);
        $distributor->delete();

        SecurityNotifier::logEvent(auth()->user(), 'Distributor Deleted', ['Distributor' => $distributor->company_name]);

        return response()->json(['status' => 'success', 'message' => 'Distributor deleted successfully.']);
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        return Excel::download(new DistributorTemplateExport(), 'distributor_import_template.xlsx');
    }

    /**
     * Show import form
     */
    public function importForm()
    {
        return view('admin.distributors.import');
    }

    /**
     * Handle bulk import
     */
    public function import(ImportDistributorsRequest $request)
    {
        try {
            $file = $request->file('file');
            
            // Create import instance
            $import = new DistributorImport();
            
            // Execute import
            Excel::import($import, $file);
            
            // Get errors if any
            $errors = $import->getErrors();
            $successCount = Distributor::count(); // This is approximate
            
            if (!empty($errors)) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Import completed with some errors.',
                    'errors' => $errors,
                    'error_count' => count($errors),
                ], 200);
            }
            
            SecurityNotifier::logEvent(auth()->user(), 'Bulk Distributors Imported', [
                'File' => $file->getClientOriginalName(),
                'Rows' => 'Multiple'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Distributors imported successfully!',
                'error_count' => 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // --- Branches ---
    public function branches(Distributor $distributor)
    {
        return response()->json(['status' => 'success', 'branches' => $distributor->branches()->with('region')->get()]);
    }

    public function storeBranch(Request $request, Distributor $distributor)
    {
        $request->validate(['name' => 'required|string|max:255', 'phone' => 'nullable|string|max:20', 'city' => 'nullable|string|max:100']);

        $branch = $distributor->branches()->create($request->only(['name', 'phone', 'email', 'address', 'city', 'region_id']));

        return response()->json(['status' => 'success', 'message' => 'Branch added.', 'branch' => $branch]);
    }

    public function destroyBranch(DistributorBranch $branch)
    {
        $branch->delete();
        return response()->json(['status' => 'success', 'message' => 'Branch removed.']);
    }

    // --- Contacts ---
    public function contacts(Distributor $distributor)
    {
        return response()->json(['status' => 'success', 'contacts' => $distributor->contacts()->get()]);
    }

    public function storeContact(Request $request, Distributor $distributor)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $contact = $distributor->contacts()->create($request->only(['name', 'title', 'phone', 'email', 'is_primary']));

        return response()->json(['status' => 'success', 'message' => 'Contact added.', 'contact' => $contact]);
    }

    public function destroyContact(DistributorContact $contact)
    {
        $contact->delete();
        return response()->json(['status' => 'success', 'message' => 'Contact removed.']);
    }
}
