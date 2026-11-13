<?php

use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExerciseController;
use App\Http\Controllers\Admin\MembershipPlanController;
use App\Http\Controllers\Admin\ModalityCategoryController;
use App\Http\Controllers\Admin\ModalityController;
use App\Http\Controllers\Admin\NetworkController;
use App\Http\Controllers\Admin\NetworkSettingsController;
use App\Http\Controllers\Admin\PersonController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\PlanParameterController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StudentAccessController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WorkoutBuilderController;
use App\Http\Controllers\Admin\WorkoutPlanController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExerciseImageController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\ModalityImageController;
use App\Http\Controllers\NetworkLogoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentAssessmentController;
use App\Http\Controllers\StudentAvatarController;
use App\Http\Controllers\StudentCheckinController;
use App\Http\Controllers\StudentCheckoutController;
use App\Http\Controllers\StudentClassController;
use App\Http\Controllers\StudentMeasurementController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\StudentTechnicalSheetController;
use App\Http\Controllers\StudentWorkoutController;
use App\Http\Controllers\StudentWorkoutPlanBuilderController;
use App\Http\Controllers\StudentWorkoutPlanController;
use App\Http\Controllers\Unit\AccessController as UnitAccessController;
use App\Http\Controllers\Unit\AssessmentController as UnitAssessmentController;
use App\Http\Controllers\Unit\ClassController as UnitClassController;
use App\Http\Controllers\Unit\DashboardController as UnitDashboardController;
use App\Http\Controllers\Unit\PaymentController as UnitPaymentController;
use App\Http\Controllers\Unit\StudentController as UnitStudentController;
use App\Http\Middleware\StudentPortalAccess;
use App\Http\Middleware\StudentPortalNetwork;
use App\Models\Student;
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

// Network branding (logo served by the application, not by the storage symlink)
Route::get('network/logo/{network}', NetworkLogoController::class)
    ->name('network.logo');

// Modality images are served by the application as well
Route::get('modality-image/{modality}', ModalityImageController::class)
    ->name('modality.image');

// Exercise reference images/gifs are served by the application as well
Route::get('exercise-image/{exercise}', ExerciseImageController::class)
    ->name('exercise.image');

// Student avatars are served by the application as well
Route::get('student-avatar/{student}', StudentAvatarController::class)
    ->name('student.avatar');

// Mercado Pago payment notifications (must be reachable without authentication)
Route::post('webhooks/mercado-pago', MercadoPagoWebhookController::class)
    ->name('webhooks.mercado-pago');

// Student portal (per network)
Route::prefix('r/{network:slug}')
    ->name('student.portal.')
    ->middleware(StudentPortalNetwork::class)
    ->group(function () {
        Route::get('/', [StudentPortalController::class, 'index'])->name('index');
        Route::get('login', [StudentPortalController::class, 'showLogin'])->name('login');
        Route::post('login', [StudentPortalController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('login.store');
        Route::get('register', [StudentPortalController::class, 'showRegister'])->name('register');
        Route::post('register', [StudentPortalController::class, 'register'])
            ->middleware('throttle:10,1')
            ->name('register.store');

        Route::middleware(StudentPortalAccess::class)->group(function () {
            Route::get('home', [StudentPortalController::class, 'home'])->name('home');
            Route::get('plans', [StudentPortalController::class, 'plans'])->name('plans');
            Route::post('plans/{membershipPlan}/checkout', [StudentCheckoutController::class, 'store'])
                ->name('plans.checkout');
            Route::get('checkout/{studentSubscription}', [StudentCheckoutController::class, 'status'])
                ->name('checkout.status');
            Route::get('assessment', [StudentAssessmentController::class, 'show'])->name('assessment.show');
            Route::get('assessment/new', [StudentAssessmentController::class, 'create'])->name('assessment.create');
            Route::get('assessment/history', [StudentAssessmentController::class, 'history'])->name('assessment.history');
            Route::get('assessment/{assessment}', [StudentAssessmentController::class, 'showOne'])->name('assessment.show-one');
            Route::post('assessment', [StudentAssessmentController::class, 'store'])->name('assessment.store');
            Route::get('workouts', [StudentWorkoutController::class, 'index'])->name('workouts.index');
            Route::get('workouts/history', [StudentWorkoutController::class, 'history'])->name('workouts.history');
            Route::get('workouts/evolution', [StudentWorkoutController::class, 'evolution'])->name('workouts.evolution');
            Route::get('workouts/history/{workoutLog}', [StudentWorkoutController::class, 'historyShow'])->name('workouts.history.show');
            Route::get('workouts/sessions/{workoutSession}', [StudentWorkoutController::class, 'showSession'])->name('workouts.session');
            Route::post('workouts/sessions/{workoutSession}/start', [StudentWorkoutController::class, 'start'])->name('workouts.start');
            Route::get('workouts/logs/{workoutLog}', [StudentWorkoutController::class, 'log'])->name('workouts.log');
            Route::post('workouts/logs/{workoutLog}/complete', [StudentWorkoutController::class, 'complete'])->name('workouts.complete');
            Route::post('workouts/plans', [StudentWorkoutPlanController::class, 'store'])->name('workouts.plans.store');
            Route::post('workouts/plans/{workoutPlan}/copy', [StudentWorkoutPlanController::class, 'copy'])->name('workouts.plans.copy');
            Route::patch('workouts/plans/{workoutPlan}/activate', [StudentWorkoutPlanController::class, 'activate'])->name('workouts.plans.activate');
            Route::patch('workouts/plans/{workoutPlan}/visibility', [StudentWorkoutPlanController::class, 'visibility'])->name('workouts.plans.visibility');
            Route::get('workouts/plans/{workoutPlan}/builder', [StudentWorkoutPlanController::class, 'builder'])->name('workouts.plans.builder');
            Route::post('workouts/plans/{workoutPlan}/sessions', [StudentWorkoutPlanBuilderController::class, 'storeSession'])->name('workouts.plans.sessions.store');
            Route::put('workouts/plans/sessions/{workoutSession}', [StudentWorkoutPlanBuilderController::class, 'updateSession'])->name('workouts.plans.sessions.update');
            Route::delete('workouts/plans/sessions/{workoutSession}', [StudentWorkoutPlanBuilderController::class, 'destroySession'])->name('workouts.plans.sessions.destroy');
            Route::post('workouts/plans/sessions/{workoutSession}/exercises', [StudentWorkoutPlanBuilderController::class, 'storeExercise'])->name('workouts.plans.exercises.store');
            Route::post('workouts/plans/sessions/{workoutSession}/exercises/reorder', [StudentWorkoutPlanBuilderController::class, 'reorderExercises'])->name('workouts.plans.exercises.reorder');
            Route::put('workouts/plans/exercises/{workoutExercise}', [StudentWorkoutPlanBuilderController::class, 'updateExercise'])->name('workouts.plans.exercises.update');
            Route::delete('workouts/plans/exercises/{workoutExercise}', [StudentWorkoutPlanBuilderController::class, 'destroyExercise'])->name('workouts.plans.exercises.destroy');
            Route::get('measurements', [StudentMeasurementController::class, 'index'])->name('measurements.index');
            Route::post('measurements', [StudentMeasurementController::class, 'store'])->name('measurements.store');
            Route::delete('measurements/{bodyMeasurement}', [StudentMeasurementController::class, 'destroy'])->name('measurements.destroy');
            Route::get('classes', [StudentClassController::class, 'index'])->name('classes.index');
            Route::post('classes/{schoolClass}/checkout', [StudentClassController::class, 'checkout'])->name('classes.checkout');
            Route::post('classes/{schoolClass}/reserve', [StudentClassController::class, 'reserve'])->name('classes.reserve');
            Route::get('classes/bookings/{classBooking}', [StudentClassController::class, 'bookingStatus'])->name('classes.booking.status');
            Route::post('checkin/trial', [StudentCheckinController::class, 'trial'])->name('checkin.trial');
            Route::get('dashboard', [StudentPortalController::class, 'dashboard'])->name('dashboard');
            Route::get('password', [StudentPortalController::class, 'showPasswordForm'])->name('password.edit');
            Route::post('password', [StudentPortalController::class, 'updatePassword'])->name('password.update');
            Route::post('logout', [StudentPortalController::class, 'logout'])->name('logout');
        });
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

        Route::prefix('people')->name('people.')->group(function () {
            Route::get('/', [PersonController::class, 'index'])
                ->middleware('can:people.view')
                ->name('index');
            Route::get('{person}/edit-data', [PersonController::class, 'editData'])
                ->middleware('can:people.edit')
                ->name('edit-data');
            Route::get('{person}', [PersonController::class, 'show'])
                ->middleware('can:people.view')
                ->name('show');
            Route::post('/', [PersonController::class, 'store'])
                ->middleware('can:people.create')
                ->name('store');
            Route::put('{person}', [PersonController::class, 'update'])
                ->middleware('can:people.edit')
                ->name('update');
            Route::patch('{person}/status', [PersonController::class, 'toggleStatus'])
                ->middleware('can:people.edit')
                ->name('toggle-status');
            Route::delete('{person}', [PersonController::class, 'destroy'])
                ->middleware('can:people.delete')
                ->name('destroy');
        });

        Route::prefix('modalities')->name('modalities.')->group(function () {
            Route::get('/', [ModalityController::class, 'index'])
                ->middleware('can:modalities.view')
                ->name('index');
            Route::post('/', [ModalityController::class, 'store'])
                ->middleware('can:modalities.create')
                ->name('store');
            Route::get('{modality}/edit-data', [ModalityController::class, 'editData'])
                ->middleware('can:modalities.edit')
                ->name('edit-data');
            Route::get('{modality}/units-data', [ModalityController::class, 'unitsData'])
                ->middleware('can:modalities.edit')
                ->name('units-data');
            Route::put('{modality}/units', [ModalityController::class, 'syncUnits'])
                ->middleware('can:modalities.edit')
                ->name('units.sync');
            Route::put('{modality}', [ModalityController::class, 'update'])
                ->middleware('can:modalities.edit')
                ->name('update');
            Route::delete('{modality}', [ModalityController::class, 'destroy'])
                ->middleware('can:modalities.delete')
                ->name('destroy');
        });

        Route::prefix('modality-categories')->name('modality-categories.')->group(function () {
            Route::get('/', [ModalityCategoryController::class, 'index'])
                ->middleware('can:modalities.manage')
                ->name('index');
            Route::post('/', [ModalityCategoryController::class, 'store'])
                ->middleware('can:modalities.manage')
                ->name('store');
            Route::put('{modalityCategory}', [ModalityCategoryController::class, 'update'])
                ->middleware('can:modalities.manage')
                ->name('update');
            Route::delete('{modalityCategory}', [ModalityCategoryController::class, 'destroy'])
                ->middleware('can:modalities.manage')
                ->name('destroy');
        });

        Route::prefix('membership-plans')->name('membership-plans.')->group(function () {
            Route::get('/', [MembershipPlanController::class, 'index'])
                ->middleware('can:membership_plans.view')
                ->name('index');
            Route::post('/', [MembershipPlanController::class, 'store'])
                ->middleware('can:membership_plans.create')
                ->name('store');
            Route::get('{membershipPlan}/edit-data', [MembershipPlanController::class, 'editData'])
                ->middleware('can:membership_plans.edit')
                ->name('edit-data');
            Route::put('{membershipPlan}', [MembershipPlanController::class, 'update'])
                ->middleware('can:membership_plans.edit')
                ->name('update');
            Route::delete('{membershipPlan}', [MembershipPlanController::class, 'destroy'])
                ->middleware('can:membership_plans.delete')
                ->name('destroy');
        });

        Route::prefix('classes')->name('classes.')->group(function () {
            Route::get('/', [ClassController::class, 'index'])
                ->middleware('can:classes.manage')
                ->name('index');
            Route::post('/', [ClassController::class, 'store'])
                ->middleware('can:classes.manage')
                ->name('store');
            Route::get('{class}/edit-data', [ClassController::class, 'editData'])
                ->middleware('can:classes.manage')
                ->name('edit-data');
            Route::put('{class}', [ClassController::class, 'update'])
                ->middleware('can:classes.manage')
                ->name('update');
            Route::delete('{class}', [ClassController::class, 'destroy'])
                ->middleware('can:classes.manage')
                ->name('destroy');
        });

        Route::prefix('exercises')->name('exercises.')->middleware('can:exercises.manage')->group(function () {
            Route::get('/', [ExerciseController::class, 'index'])->name('index');
            Route::post('/', [ExerciseController::class, 'store'])->name('store');
            Route::get('{exercise}/edit-data', [ExerciseController::class, 'editData'])->name('edit-data');
            Route::put('{exercise}', [ExerciseController::class, 'update'])->name('update');
            Route::patch('{exercise}/status', [ExerciseController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('{exercise}', [ExerciseController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('workout-plans')->name('workout-plans.')->middleware('can:workouts.manage')->group(function () {
            Route::get('/', [WorkoutPlanController::class, 'index'])->name('index');
            Route::post('/', [WorkoutPlanController::class, 'store'])->name('store');
            Route::get('{workoutPlan}', [WorkoutPlanController::class, 'builder'])->name('builder');
            Route::get('{workoutPlan}/edit-data', [WorkoutPlanController::class, 'editData'])->name('edit-data');
            Route::put('{workoutPlan}', [WorkoutPlanController::class, 'update'])->name('update');
            Route::patch('{workoutPlan}/status', [WorkoutPlanController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('{workoutPlan}', [WorkoutPlanController::class, 'destroy'])->name('destroy');
            Route::post('{workoutPlan}/sessions', [WorkoutBuilderController::class, 'storeSession'])->name('sessions.store');
        });

        Route::middleware('can:workouts.manage')->group(function () {
            Route::put('workout-sessions/{workoutSession}', [WorkoutBuilderController::class, 'updateSession'])
                ->name('workout-sessions.update');
            Route::delete('workout-sessions/{workoutSession}', [WorkoutBuilderController::class, 'destroySession'])
                ->name('workout-sessions.destroy');
            Route::post('workout-sessions/{workoutSession}/exercises', [WorkoutBuilderController::class, 'storeExercise'])
                ->name('workout-sessions.exercises.store');
            Route::post('workout-sessions/{workoutSession}/exercises/reorder', [WorkoutBuilderController::class, 'reorderExercises'])
                ->name('workout-sessions.exercises.reorder');
            Route::put('workout-exercises/{workoutExercise}', [WorkoutBuilderController::class, 'updateExercise'])
                ->name('workout-exercises.update');
            Route::delete('workout-exercises/{workoutExercise}', [WorkoutBuilderController::class, 'destroyExercise'])
                ->name('workout-exercises.destroy');
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

    // Students Routes
    Route::prefix('students')->name('students.')->group(function () {
        Route::get('/', [StudentController::class, 'index'])
            ->middleware('can:students.view')
            ->name('index');
        Route::get('/create', fn () => redirect()->route('students.index'))
            ->middleware('can:students.create')
            ->name('create');
        Route::get('/inactive', fn () => redirect()->route('students.index', ['status' => 'inactive']))
            ->middleware('can:students.view')
            ->name('inactive');
        Route::get('/{student}/edit-data', [StudentController::class, 'editData'])
            ->middleware('can:students.edit')
            ->name('edit-data');
        Route::get('/{student}/assessment', [StudentController::class, 'assessment'])
            ->middleware('can:students.view')
            ->name('assessment');
        Route::get('/{student}/measurements', [StudentController::class, 'measurements'])
            ->middleware('can:students.view')
            ->name('measurements');
        Route::get('/{student}/technical-sheet', [StudentTechnicalSheetController::class, 'show'])
            ->middleware('can:students.view')
            ->name('technical-sheet');
        Route::post('/{student}/technical-sheet/plan', [StudentTechnicalSheetController::class, 'storePlan'])
            ->middleware('can:workouts.manage')
            ->name('technical-sheet.plan');
        Route::post('/{student}/technical-sheet/apply', [StudentTechnicalSheetController::class, 'applyPlan'])
            ->middleware('can:workouts.manage')
            ->name('technical-sheet.apply');
        Route::put('/{student}/access', [StudentAccessController::class, 'update'])
            ->middleware('can:students.edit')
            ->name('access.update');
        Route::get('/{student}/edit', fn (Student $student) => redirect()->route('students.show', $student))
            ->middleware('can:students.edit')
            ->name('edit');
        Route::get('/{student}', [StudentController::class, 'show'])
            ->middleware('can:students.view')
            ->name('show');
        Route::post('/', [StudentController::class, 'store'])
            ->middleware('can:students.create')
            ->name('store');
        Route::put('/{student}', [StudentController::class, 'update'])
            ->middleware('can:students.edit')
            ->name('update');
        Route::patch('/{student}/status', [StudentController::class, 'toggleStatus'])
            ->middleware('can:students.edit')
            ->name('toggle-status');
        Route::delete('/{student}', [StudentController::class, 'destroy'])
            ->middleware('can:students.delete')
            ->name('destroy');
    });

    // Unit administration panel
    Route::prefix('unit')->name('unit.')->middleware('can:unit.panel')->group(function () {
        Route::get('/', [UnitDashboardController::class, 'index'])->name('dashboard');
        Route::get('students', [UnitStudentController::class, 'index'])->name('students.index');
        Route::get('assessments', [UnitAssessmentController::class, 'index'])->name('assessments.index');
        Route::post('assessments/{assessment}/review', [UnitAssessmentController::class, 'review'])
            ->name('assessments.review');
        Route::get('payments', [UnitPaymentController::class, 'index'])->name('payments.index');
        Route::post('subscriptions/{subscription}/confirm', [UnitPaymentController::class, 'confirm'])
            ->middleware('can:students.edit')
            ->name('subscriptions.confirm');
        Route::get('access', [UnitAccessController::class, 'index'])->name('access.index');
        Route::get('classes', [UnitClassController::class, 'index'])->name('classes.index');
        Route::post('class-bookings', [UnitClassController::class, 'storeBooking'])
            ->middleware('can:classes.manage')
            ->name('class-bookings.store');
        Route::post('class-bookings/{booking}/cancel', [UnitClassController::class, 'cancelBooking'])
            ->middleware('can:classes.manage')
            ->name('class-bookings.cancel');
        Route::post('class-bookings/{booking}/confirm', [UnitClassController::class, 'confirmBooking'])
            ->middleware('can:classes.manage')
            ->name('class-bookings.confirm');
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
