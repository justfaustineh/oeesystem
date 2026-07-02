<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\ZindukaController;
use App\Http\Controllers\Admin\CounterfeitReportController;
use App\Http\Controllers\Admin\DistributorController;
use App\Http\Controllers\Admin\RegionalRepresentativeController;
use App\Http\Controllers\Admin\ElectricianController;
use App\Http\Controllers\Admin\ServiceRequestController;
use App\Http\Controllers\Admin\EnquiryController;

/*
|--------------------------------------------------------------------------
| SYSTEM / MISC (public)
|--------------------------------------------------------------------------
*/
Route::get('/system/disclaimer', [SystemController::class, 'disclaimer'])->name('system.disclaimer');

Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATION (guest only)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');

    // ── Self-registration (Customer, Engineer, Distributor, Electrician only) ──
    Route::post('/register', [RegisterController::class, 'register'])->name('register.attempt');

    Route::get('/otp/{user}', [OtpController::class, 'show'])->name('otp.show');
    Route::post('/otp/{user}/verify', [OtpController::class, 'verify'])->name('otp.verify');
    Route::post('/otp/{user}/resend', [OtpController::class, 'resend'])->name('otp.resend');

    // ── Pending admin approval landing page (post email-verification) ──
    Route::get('/pending-approval/{user}', [OtpController::class, 'pendingApproval'])->name('pending-approval');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| PUBLIC ZINDUKA VERIFICATION (no auth — unlimited, end-user facing)
|--------------------------------------------------------------------------
| Reached via: typed URL, SMS shortcode reply, or QR/barcode scan on stickers.
*/
Route::prefix('verify')->name('zinduka.public.')->controller(ZindukaController::class)->group(function () {
    Route::get('/{code?}', 'showVerifyPage')->name('verify');
    Route::post('/', 'verify')->name('verify.submit');
});

/*
|--------------------------------------------------------------------------
| ADMIN AREA (auth + session timeout + country scope)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'session.timeout', 'country.scope'])->prefix('admin')->name('admin.')->group(function () {

    // ── Dashboard ────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Users ────────────────────────────────────────────────────────
    Route::prefix('users')->name('users.')->controller(UserController::class)->group(function () {
        Route::get('/', 'index')->middleware('check.permission')->name('index');
        Route::get('/data', 'data')->middleware('check.permission')->name('data');
        Route::post('/', 'store')->middleware('check.permission')->name('store');
        Route::get('/export', 'export')->middleware('check.permission')->name('export');
        Route::delete('/bulk-destroy', 'bulkDestroy')->middleware('check.permission')->name('bulk-destroy');
        Route::get('/{user}', 'show')->middleware('check.permission')->name('show');
        Route::get('/{user}/edit', 'edit')->middleware('check.permission')->name('edit');
        Route::put('/{user}', 'update')->middleware(['check.permission', 'protect.owner'])->name('update');
        Route::delete('/{user}', 'destroy')->middleware(['check.permission', 'protect.owner'])->name('destroy');
        Route::patch('/{user}/avatar', 'avatar')->middleware('check.permission')->name('avatar');
        Route::delete('/{user}/avatar', 'removeAvatar')->middleware('check.permission')->name('avatar.remove');
        Route::patch('/{user}/toggle-status', 'toggleStatus')->middleware('check.permission')->name('toggle-status');
        Route::patch('/{user}/reset-password', 'resetPassword')->middleware('check.permission')->name('reset-password');
    });

    // ── Roles ────────────────────────────────────────────────────────
    Route::prefix('roles')->name('roles.')->controller(RoleController::class)->group(function () {
        Route::get('/', 'index')->middleware('check.permission')->name('index');
        Route::get('/data', 'data')->middleware('check.permission')->name('data');
        Route::post('/', 'store')->middleware('check.permission')->name('store');
        Route::get('/{role}/edit', 'edit')->middleware('check.permission')->name('edit');
        Route::put('/{role}', 'update')->middleware('check.permission')->name('update');
        Route::delete('/{role}', 'destroy')->middleware('check.permission')->name('destroy');
    });

    // ── Countries ────────────────────────────────────────────────────
    Route::prefix('countries')->name('countries.')->controller(CountryController::class)->group(function () {
        Route::get('/', 'index')->middleware('check.permission')->name('index');
        Route::get('/data', 'data')->middleware('check.permission')->name('data');
        Route::post('/', 'store')->middleware('check.permission')->name('store');
        Route::get('/{country}/edit', 'edit')->middleware('check.permission')->name('edit');
        Route::put('/{country}', 'update')->middleware('check.permission')->name('update');
        Route::delete('/{country}', 'destroy')->middleware('check.permission')->name('destroy');
    });

    // ── Product Categories & Subcategories ──────────────────────────
    Route::prefix('product-categories')->name('product-categories.')->controller(ProductCategoryController::class)->group(function () {
        Route::get('/', 'index')->middleware('check.permission')->name('index');
        Route::get('/data', 'data')->middleware('check.permission')->name('data');
        Route::post('/', 'store')->middleware('check.permission')->name('store');
        Route::get('/{product_category}/edit', 'edit')->middleware('check.permission')->name('edit');
        Route::put('/{product_category}', 'update')->middleware('check.permission')->name('update');
        Route::delete('/{product_category}', 'destroy')->middleware('check.permission')->name('destroy');
        Route::get('/{product_category}/subcategories', 'subcategories')->middleware('check.permission')->name('subcategories');
        Route::post('/{product_category}/subcategories', 'storeSubcategory')->middleware('check.permission')->name('subcategories.store');
    });

    Route::delete('subcategories/{subcategory}', [ProductCategoryController::class, 'destroySubcategory'])
        ->middleware('check.permission')->name('product-categories.subcategories.destroy');

    // ── Products ────────────────────────────────────────────────���────
    Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
        Route::get('/', 'index')->middleware('check.permission')->name('index');
        Route::get('/data', 'data')->middleware('check.permission')->name('data');
        Route::post('/', 'store')->middleware('check.permission')->name('store');

        // Bulk import (must be registered before the '/{product}' wildcard routes)
        Route::get('/import/template', 'downloadImportTemplate')->middleware('check.permission')->name('import.template');
        Route::post('/import', 'import')->middleware('check.permission')->name('import');

        Route::get('category/{category}/subcategories', 'subcategoriesByCategory')->name('subcategories.by-category');
        Route::get('/{product}', 'show')->middleware('check.permission')->name('show');
        Route::get('/{product}/edit', 'edit')->middleware('check.permission')->name('edit');
        Route::post('/{product}', 'update')->middleware('check.permission')->name('update');
        Route::delete('/{product}', 'destroy')->middleware('check.permission')->name('destroy');
        Route::delete('/{product}/image', 'deleteImage')->middleware('check.permission')->name('image.destroy');
    });

    // ── Documents ────────────────────────────────────────────────────
    Route::prefix('documents')->name('documents.')->controller(DocumentController::class)->group(function () {
        Route::get('/', 'index')->middleware('check.permission')->name('index');
        Route::get('/data', 'data')->middleware('check.permission')->name('data');
        Route::post('/', 'store')->middleware('check.permission')->name('store');
        Route::get('/{document}/edit', 'edit')->middleware('check.permission')->name('edit');
        Route::post('/{document}', 'update')->middleware('check.permission')->name('update');
        Route::delete('/{document}', 'destroy')->middleware('check.permission')->name('destroy');
        Route::get('/{document}/download', 'download')->middleware('check.permission')->name('download');
    });

    // ── Zinduka Anti-Counterfeit ─────────────────────────────────────
    Route::prefix('zinduka')->name('zinduka.')->controller(ZindukaController::class)->group(function () {
        // Dashboard + datatables
        Route::get('/', 'index')->middleware('check.permission')->name('index');
        Route::get('/logs/data', 'logsData')->middleware('check.permission')->name('logs.data');

        // Manual verification (staff-facing, inside admin panel, permission-gated)
        Route::post('/verify', 'verify')->middleware('check.permission')->name('verify');

        // Batches: generation, export, deletion
        Route::prefix('batches')->name('batches.')->group(function () {
            Route::get('/data', 'batchData')->middleware('check.permission')->name('data');
            Route::post('/', 'storeBatch')->middleware('check.permission')->name('store');
            Route::get('/{batch}/export', 'exportBatch')->middleware('check.permission')->name('export');
            Route::delete('/{batch}', 'destroyBatch')->middleware('check.permission')->name('destroy');

            // Async asset generation (QR, barcode, stickers)
            Route::get('/{batch}/assets-status', 'assetsStatus')->middleware('check.permission')->name('assets-status');
            Route::get('/{batch}/stickers', 'downloadBulkStickers')->middleware('check.permission')->name('stickers');

            // Rebuild the bulk sticker PDF from the current template without
            // regenerating codes/QR/barcode assets — used after tweaking the
            // sticker layout so staff can re-print without a fresh batch.
            Route::post('/{batch}/regenerate-stickers', 'regenerateStickers')->middleware('check.permission')->name('regenerate-stickers');
        });

        // Individual code assets
        Route::prefix('codes')->name('codes.')->group(function () {
            Route::get('/{code}/sticker', 'downloadSingleSticker')->middleware('check.permission')->name('sticker');
        });
    });

    // ── Counterfeit Reports ──────────────────────────────────────────
    Route::prefix('counterfeit-reports')->name('counterfeit-reports.')->controller(CounterfeitReportController::class)->group(function () {
        Route::get('/data', 'data')->middleware('check.permission')->name('data');
        Route::get('/{report}', 'show')->middleware('check.permission')->name('show');
        Route::patch('/{report}/status', 'updateStatus')->middleware('check.permission')->name('status');
    });

    // ── Distributors ─────────────────────────────────────────────────
    Route::prefix('distributors')->name('distributors.')->controller(DistributorController::class)->group(function () {
        Route::get('/', 'index')->middleware('check.permission')->name('index');
        Route::get('/data', 'data')->middleware('check.permission')->name('data');
        Route::post('/', 'store')->middleware('check.permission')->name('store');

        // Bulk import (must be before /{distributor} wildcard routes)
        Route::get('/import/template', 'downloadTemplate')->middleware('check.permission')->name('download-template');
        Route::post('/import', 'import')->middleware('check.permission')->name('import');

        Route::get('/{distributor}', 'show')->middleware('check.permission')->name('show');
        Route::get('/{distributor}/edit', 'edit')->middleware('check.permission')->name('edit');
        Route::put('/{distributor}', 'update')->middleware('check.permission')->name('update');
        Route::delete('/{distributor}', 'destroy')->middleware('check.permission')->name('destroy');
        
        // Branches
        Route::get('/{distributor}/branches', 'branches')->middleware('check.permission')->name('branches');
        Route::post('/{distributor}/branches', 'storeBranch')->middleware('check.permission')->name('branches.store');
        Route::delete('/branches/{branch}', 'destroyBranch')->middleware('check.permission')->name('branches.destroy');
        
        // Contacts
        Route::get('/{distributor}/contacts', 'contacts')->middleware('check.permission')->name('contacts');
        Route::post('/{distributor}/contacts', 'storeContact')->middleware('check.permission')->name('contacts.store');
        Route::delete('/contacts/{contact}', 'destroyContact')->middleware('check.permission')->name('contacts.destroy');
    });

    // ── Regional Representatives ─────────────────────────────────────
    Route::prefix('representatives')->name('reps.')->controller(RegionalRepresentativeController::class)->group(function () {
        Route::get('/', 'index')->middleware('check.permission')->name('index');
        Route::get('/data', 'data')->middleware('check.permission')->name('data');
        Route::post('/', 'store')->middleware('check.permission')->name('store');
        Route::get('/{regionalRepresentative}/edit', 'edit')->middleware('check.permission')->name('edit');
        Route::put('/{regionalRepresentative}', 'update')->middleware('check.permission')->name('update');
        Route::delete('/{regionalRepresentative}', 'destroy')->middleware('check.permission')->name('destroy');
    });

    // ── Electricians ─────────────────────────────────────────────────
    Route::prefix('electricians')->name('electricians.')->controller(ElectricianController::class)->group(function () {
        Route::get('/', 'index')->middleware('check.permission')->name('index');
        Route::get('/data', 'data')->middleware('check.permission')->name('data');
        Route::post('/', 'store')->middleware('check.permission')->name('store');
        Route::get('/{electrician}/edit', 'edit')->middleware('check.permission')->name('edit');
        Route::put('/{electrician}', 'update')->middleware('check.permission')->name('update');
        Route::patch('/{electrician}/vet', 'vet')->middleware('check.permission')->name('vet');
        Route::delete('/{electrician}', 'destroy')->middleware('check.permission')->name('destroy');
    });

    // ── Service Requests ─────────────────────────────────────────────
    Route::prefix('service-requests')->name('service-requests.')->controller(ServiceRequestController::class)->group(function () {
        Route::get('/', 'index')->middleware('check.permission')->name('index');
        Route::get('/data', 'data')->middleware('check.permission')->name('data');
        Route::get('/{serviceRequest}', 'show')->middleware('check.permission')->name('show');
        Route::post('/{serviceRequest}/assign', 'assign')->middleware('check.permission')->name('assign');
        Route::delete('/{serviceRequest}', 'destroy')->middleware('check.permission')->name('destroy');
    });

    // ── Enquiries ────────────────────────────────────────────────────
    Route::prefix('enquiries')->name('enquiries.')->controller(EnquiryController::class)->group(function () {
        Route::get('/', 'index')->middleware('check.permission')->name('index');
        Route::get('/data', 'data')->middleware('check.permission')->name('data');
        Route::post('/', 'store')->middleware('check.permission')->name('store');
        Route::get('/{enquiry}', 'show')->middleware('check.permission')->name('show');
        Route::post('/{enquiry}/reply', 'reply')->middleware('check.permission')->name('reply');
        Route::patch('/{enquiry}/status', 'updateStatus')->middleware('check.permission')->name('status');
        Route::delete('/{enquiry}', 'destroy')->middleware('check.permission')->name('destroy');
    });
    
});
