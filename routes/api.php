<?php

use App\Http\Controllers\Api\ApiMetaController;
use App\Http\Controllers\Api\MasterDataApiController;
use App\Http\Middleware\ApiKeyAuthentication;
use Illuminate\Support\Facades\Route;

Route::get('v1/meta', [ApiMetaController::class, 'show'])->name('api.meta');

Route::middleware(['throttle:api', ApiKeyAuthentication::class])
    ->prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::get('academic-years', [MasterDataApiController::class, 'academicYears'])->name('academic-years');
        Route::get('levels', [MasterDataApiController::class, 'levels'])->name('levels');
        Route::get('classrooms', [MasterDataApiController::class, 'classrooms'])->name('classrooms');
        Route::get('dorms', [MasterDataApiController::class, 'dorms'])->name('dorms');
        Route::get('incident-categories', [MasterDataApiController::class, 'categories'])->name('incident-categories');
        Route::get('incident-locations', [MasterDataApiController::class, 'locations'])->name('incident-locations');
    });
