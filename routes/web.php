<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DormController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/manifest.webmanifest', ManifestController::class)->name('manifest');

Route::middleware('throttle:health')->group(function (): void {
    Route::get('/health/live', [HealthController::class, 'live'])->name('health.live');
    Route::get('/health/ready', [HealthController::class, 'ready'])->name('health.ready');
    Route::get('/health/heartbeat', [HealthController::class, 'heartbeat'])->name('health.heartbeat');
    Route::get('/health/metrics', [HealthController::class, 'metrics'])->name('health.metrics');
});

Route::view('/api/docs', 'api-docs')->name('api.docs');
Route::get('/api/openapi.json', OpenApiController::class)->name('api.openapi');

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifikasi/{notification}/baca', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/siswa/import', [StudentController::class, 'importForm'])->name('students.import');
    Route::post('/siswa/import/pratinjau', [StudentController::class, 'importPreview'])->name('students.import.preview');
    Route::post('/siswa/import/konfirmasi', [StudentController::class, 'importConfirm'])->name('students.import.confirm');
    Route::get('/siswa/import/templat', [StudentController::class, 'template'])->name('students.template');
    Route::get('/siswa/ekspor', [StudentController::class, 'export'])->name('students.export');
    Route::resource('siswa', StudentController::class)
        ->parameters(['siswa' => 'student'])
        ->except(['destroy'])
        ->names([
            'index' => 'students.index',
            'create' => 'students.create',
            'store' => 'students.store',
            'show' => 'students.show',
            'edit' => 'students.edit',
            'update' => 'students.update',
        ]);

    Route::get('/presensi', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/presensi', [AttendanceController::class, 'store'])->name('attendance.store');

    Route::get('/insiden', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/insiden/buat', [IncidentController::class, 'create'])->name('incidents.create');
    Route::post('/insiden', [IncidentController::class, 'store'])->name('incidents.store');
    Route::get('/insiden/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::patch('/insiden/{incident}', [IncidentController::class, 'update'])->name('incidents.update');
    Route::patch('/insiden/{incident}/tugas/{task}', [IncidentController::class, 'toggleTask'])->name('incidents.tasks.toggle');
    Route::patch('/insiden/{incident}/tindak-lanjut/{followup}', [IncidentController::class, 'completeFollowup'])->name('incidents.followups.complete');

    Route::get('/asrama', [DormController::class, 'index'])->name('dorm.index');
    Route::post('/asrama/logbook', [DormController::class, 'storeLog'])->name('dorm.logs.store');

    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/ekspor', [ReportController::class, 'export'])->name('reports.export');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('can:manage-users')->group(function () {
            Route::resource('users', UserController::class)
                ->parameters(['users' => 'user'])
                ->except(['show', 'destroy'])
                ->names([
                    'index' => 'users.index',
                    'create' => 'users.create',
                    'store' => 'users.store',
                    'edit' => 'users.edit',
                    'update' => 'users.update',
                ]);
        });

        Route::middleware('can:manage-users')->group(function () {
            Route::get('master', [MasterDataController::class, 'index'])->name('master.index');
            Route::post('master/academic-years', [MasterDataController::class, 'storeAcademicYear'])->name('master.academic-years.store');
            Route::patch('master/academic-years/{academicYear}', [MasterDataController::class, 'updateAcademicYear'])->name('master.academic-years.update');
            Route::patch('master/academic-years/{academicYear}/activate', [MasterDataController::class, 'activateAcademicYear'])->name('master.academic-years.activate');
            Route::post('master/levels', [MasterDataController::class, 'storeLevel'])->name('master.levels.store');
            Route::patch('master/levels/{level}', [MasterDataController::class, 'updateLevel'])->name('master.levels.update');
            Route::post('master/classrooms', [MasterDataController::class, 'storeClassroom'])->name('master.classrooms.store');
            Route::patch('master/classrooms/{classroom}', [MasterDataController::class, 'updateClassroom'])->name('master.classrooms.update');
            Route::post('master/dorms', [MasterDataController::class, 'storeDorm'])->name('master.dorms.store');
            Route::patch('master/dorms/{dorm}', [MasterDataController::class, 'updateDorm'])->name('master.dorms.update');
            Route::post('master/locations', [MasterDataController::class, 'storeLocation'])->name('master.locations.store');
            Route::patch('master/locations/{location}', [MasterDataController::class, 'updateLocation'])->name('master.locations.update');

            Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
            Route::patch('settings', [SettingController::class, 'update'])->name('settings.update');
        });

        Route::get('audit', AuditController::class)->middleware('can:view-audit')->name('audit.index');
    });
});
