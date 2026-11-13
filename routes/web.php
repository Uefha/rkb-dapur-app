<?php

use App\Http\Controllers\Admin\AdminSubmissionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FormBuilderController;
use App\Http\Controllers\Admin\FormCategoryController;
use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\FormCatalogController;
use App\Http\Controllers\User\FormFillController;
use App\Http\Controllers\User\FormSubmissionController;
use App\Http\Controllers\User\MySubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'start'])->name('app.start');

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    // URL dashboard umum hanya menjadi pengarah ke dashboard sesuai peran.
    Route::get('dashboard', function () {
        return redirect()->route(auth()->user()->homeRoute());
    })->name('dashboard.redirect');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::patch('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::resource('users', UserManagementController::class)->except(['show', 'destroy']);
        Route::get('users/{user}/forms', [UserManagementController::class, 'displayedForms'])->name('users.forms');
        Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        Route::patch('users/{user}/status', [UserManagementController::class, 'status'])->name('users.status');

        Route::resource('categories', FormCategoryController::class)->except(['show']);

        Route::resource('forms', FormController::class)->except(['show']);
        Route::post('forms/{form}/publish', [FormController::class, 'publish'])->name('forms.publish');
        Route::post('forms/{form}/unpublish', [FormController::class, 'unpublish'])->name('forms.unpublish');
        Route::post('forms/{form}/archive', [FormController::class, 'archive'])->name('forms.archive');
        Route::post('forms/{form}/new-version', [FormController::class, 'newVersion'])->name('forms.new-version');
        Route::post('forms/{form}/duplicate', [FormController::class, 'duplicate'])->name('forms.duplicate');
        Route::get('forms/{form}/access', [FormController::class, 'access'])->name('forms.access');
        Route::put('forms/{form}/access', [FormController::class, 'updateAccess'])->name('forms.access.update');

        Route::prefix('forms/{form}/builder')->name('forms.builder.')->group(function () {
            Route::get('/', [FormBuilderController::class, 'index'])->name('index');

            Route::post('sections', [FormBuilderController::class, 'storeSection'])->name('sections.store');
            Route::put('sections/{section}', [FormBuilderController::class, 'updateSection'])->name('sections.update');
            Route::delete('sections/{section}', [FormBuilderController::class, 'destroySection'])->name('sections.destroy');
            Route::post('sections/{section}/move/{direction}', [FormBuilderController::class, 'moveSection'])
                ->whereIn('direction', ['up', 'down'])->name('sections.move');

            Route::post('sections/{section}/fields', [FormBuilderController::class, 'storeField'])->name('fields.store');
            Route::put('fields/{field}', [FormBuilderController::class, 'updateField'])->name('fields.update');
            Route::delete('fields/{field}', [FormBuilderController::class, 'destroyField'])->name('fields.destroy');
            Route::post('fields/{field}/move/{direction}', [FormBuilderController::class, 'moveField'])
                ->whereIn('direction', ['up', 'down'])->name('fields.move');
        });

        // Daftar submission, filter, dan ekspor laporan.
        Route::get('submissions', [AdminSubmissionController::class, 'index'])->name('submissions.index');
        Route::get('submissions/export/{format}', [AdminSubmissionController::class, 'export'])->name('submissions.export');
        Route::get('submissions/{submission}', [AdminSubmissionController::class, 'show'])->name('submissions.show');
        Route::post('submissions/{submission}/review', [AdminSubmissionController::class, 'review'])->name('submissions.review');
        Route::post('submissions/{submission}/approve', [AdminSubmissionController::class, 'approve'])->name('submissions.approve');
        Route::post('submissions/{submission}/reject', [AdminSubmissionController::class, 'reject'])->name('submissions.reject');
        Route::post('submissions/{submission}/request-revision', [AdminSubmissionController::class, 'requestRevision'])->name('submissions.request-revision');
        Route::delete('submissions/{submission}', [AdminSubmissionController::class, 'destroy'])->name('submissions.destroy');
    });

    Route::middleware('role:user')->name('user.')->group(function () {
        Route::get('user/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');

        Route::get('forms', [FormCatalogController::class, 'index'])->name('forms.index');
        Route::get('forms/{form}', [FormCatalogController::class, 'show'])->name('forms.show');
        Route::get('forms/{form}/fill', [FormFillController::class, 'show'])->name('forms.fill');
        Route::post('forms/{form}/submit', [FormSubmissionController::class, 'store'])->name('forms.submit');

        Route::get('my-submissions', [MySubmissionController::class, 'index'])->name('my-submissions.index');
        Route::get('my-submissions/{submission}', [MySubmissionController::class, 'show'])->name('my-submissions.show');
        Route::delete('my-submissions/{submission}', [MySubmissionController::class, 'destroy'])->name('my-submissions.destroy');
    });
});

require __DIR__.'/auth.php';
