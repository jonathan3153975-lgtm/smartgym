<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\NetworkController;
use App\Http\Controllers\Admin\NetworkSettingsController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\PlanParameterController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

// Email Verification Routes
Route::middleware('auth')->group(function () {
    Route::get('verify-email', [EmailVerificationPromptController::class, '__invoke'])->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Protected Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Password Confirmation
    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Admin Routes (for platform developer)
    Route::prefix('admin')->name('admin.')->middleware('can:admin.manage')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::middleware('can:networks.manage')->group(function () {
            Route::resource('networks', NetworkController::class)
                ->only(['index', 'store', 'update'])
                ->names('networks');
            Route::patch('networks/{network}/status', [NetworkController::class, 'toggleStatus'])
                ->name('networks.toggle-status');
        });

        Route::get('networks/{network}', [NetworkController::class, 'show'])
            ->middleware('can:network.settings')
            ->name('networks.show');

        Route::middleware('can:network.settings')->group(function () {
            Route::get('network/settings', [NetworkSettingsController::class, 'edit'])
                ->name('network.settings.edit');
            Route::put('network/settings', [NetworkSettingsController::class, 'update'])
                ->name('network.settings.update');
        });

        Route::middleware('can:regions.manage')->group(function () {
            Route::resource('regions', RegionController::class)
                ->only(['index', 'show', 'store', 'update', 'destroy'])
                ->names('regions');
            Route::patch('regions/{region}/status', [RegionController::class, 'toggleStatus'])
                ->name('regions.toggle-status');
        });

        Route::middleware('can:units.manage')->group(function () {
            Route::resource('units', UnitController::class)
                ->only(['index', 'show', 'store', 'update', 'destroy'])
                ->names('units');
            Route::patch('units/{unit}/status', [UnitController::class, 'toggleStatus'])
                ->name('units.toggle-status');
        });

        Route::middleware('can:users.manage')->group(function () {
            Route::get('users/{user}/edit-data', [UserController::class, 'editData'])
                ->name('users.edit-data');
            Route::resource('users', UserController::class)
                ->only(['index', 'show', 'store', 'update', 'destroy'])
                ->names('users');
            Route::patch('users/{user}/status', [UserController::class, 'toggleStatus'])
                ->name('users.toggle-status');
        });

        Route::middleware('can:roles.manage')->group(function () {
            Route::resource('roles', RoleController::class)
                ->only(['index', 'show', 'store', 'update', 'destroy'])
                ->names('roles');
        });

        // SaaS plans (developer access only)
        Route::middleware('can:plans.manage')->group(function () {
            Route::resource('plans', PlanController::class)
                ->only(['index', 'store', 'show', 'update', 'destroy'])
                ->names('plans');

            Route::post('plans/{plan}/parameters', [PlanParameterController::class, 'store'])
                ->name('plans.parameters.store');
            Route::put('plans/{plan}/parameters/{parameter}', [PlanParameterController::class, 'update'])
                ->name('plans.parameters.update');
            Route::delete('plans/{plan}/parameters/{parameter}', [PlanParameterController::class, 'destroy'])
                ->name('plans.parameters.destroy');
        });
    });

    // Students Routes (will be implemented in Phase 2)
    Route::prefix('students')->name('students.')->group(function () {
        Route::get('/', fn () => view('pages.students.index'))->name('index');
        Route::get('/create', fn () => view('pages.students.create'))->name('create');
        Route::get('/inactive', fn () => view('pages.students.inactive'))->name('inactive');
        Route::get('/{student}', fn () => view('pages.students.show'))->name('show');
        Route::get('/{student}/edit', fn () => view('pages.students.edit'))->name('edit');
    });

    // Admin Routes (for platform developer) - will be implemented in Phase 1
    // Route::prefix('admin')->name('admin.')->middleware('can:admin.access')->group(function () {
    //     Route::get('/', fn() => view('pages.admin.dashboard'))->name('dashboard');
    //     Route::resource('networks', App\Http\Controllers\Admin\NetworkController::class)->names('networks');
    //     Route::resource('regions', App\Http\Controllers\Admin\RegionController::class)->names('regions');
    //     Route::resource('units', App\Http\Controllers\Admin\UnitController::class)->names('units');
    //     Route::resource('users', App\Http\Controllers\Admin\UserController::class)->names('users');
    //     Route::resource('roles', App\Http\Controllers\Admin\RoleController::class)->names('roles');
    // });
});
