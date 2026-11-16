<?php

use App\Http\Controllers\Api\V1\AssignmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\MaintenanceRequestController;
use App\Http\Controllers\Api\V1\RequestReportController;
use App\Http\Controllers\Api\V1\RequestStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/requests', [MaintenanceRequestController::class, 'index']);
        Route::post('/requests', [MaintenanceRequestController::class, 'store']);
        Route::get('/requests/{maintenanceRequest}', [MaintenanceRequestController::class, 'show']);
        Route::post('/requests/{maintenanceRequest}/assign', [AssignmentController::class, 'store']);
        Route::patch('/requests/{maintenanceRequest}/status', [RequestStatusController::class, 'update']);
        Route::post('/requests/{maintenanceRequest}/report', [RequestReportController::class, 'store']);

        Route::get('/customers', [CustomerController::class, 'index']);
        Route::post('/customers', [CustomerController::class, 'store']);
    });
});
