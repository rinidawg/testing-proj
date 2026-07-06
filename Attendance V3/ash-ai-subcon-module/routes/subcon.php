<?php

use App\Http\Controllers\Subcon\SubconAttachmentController;
use App\Http\Controllers\Subcon\SubconDeliveryController;
use App\Http\Controllers\Subcon\SubconPayoutController;
use App\Http\Controllers\Subcon\SubconPoController;
use App\Http\Controllers\Subcon\SubconTripController;
use App\Http\Controllers\Subcon\SubcontractorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subcon Module Routes  (base: /api/v2)
|--------------------------------------------------------------------------
| REQUIRE before the portal/{role} wildcard (BUG-010 guard). Distinct
| "subcon" first segment. Auth: sanctum + Spatie `permission` alias, same
| pattern as attendance / finance modules.
*/

Route::prefix('api/v2')->middleware('api')->group(function () {
    Route::middleware(['auth:sanctum', 'permission:access.subcon'])->group(function () {

        Route::get('subcon/subcontractors', [SubcontractorController::class, 'index']);
        Route::get('subcon/pos', [SubconPoController::class, 'index']);
        Route::get('subcon/pos/{id}', [SubconPoController::class, 'show']);
        Route::get('subcon/deliveries', [SubconDeliveryController::class, 'index']);
        Route::get('subcon/trips', [SubconTripController::class, 'index']);
        Route::get('subcon/attachments', [SubconAttachmentController::class, 'index']);

        Route::get('subcon/payouts/week', [SubconPayoutController::class, 'week']);
        Route::get('subcon/payouts/export', [SubconPayoutController::class, 'export']);

        Route::middleware('permission:action.subcon.manage')->group(function () {
            Route::post('subcon/subcontractors', [SubcontractorController::class, 'store']);
            Route::post('subcon/pos', [SubconPoController::class, 'store']);

            Route::post('subcon/deliveries', [SubconDeliveryController::class, 'store']);
            Route::post('subcon/deliveries/{id}/repair', [SubconDeliveryController::class, 'repair']);
            Route::post('subcon/deliveries/{id}/scrap', [SubconDeliveryController::class, 'scrap']);
            Route::delete('subcon/deliveries/{id}', [SubconDeliveryController::class, 'destroy']);

            Route::post('subcon/trips', [SubconTripController::class, 'store']);
            Route::delete('subcon/trips/{id}', [SubconTripController::class, 'destroy']);

            Route::post('subcon/payouts/pay', [SubconPayoutController::class, 'pay']);

            Route::post('subcon/attachments', [SubconAttachmentController::class, 'store']);
            Route::delete('subcon/attachments/{id}', [SubconAttachmentController::class, 'destroy']);
        });
    });
});
