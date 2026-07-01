<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DocumentationController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\ShopEmployeeController;
use App\Http\Controllers\Api\ShopInvestorController;
use App\Http\Controllers\Api\ShopInvestorReportController;
use App\Http\Controllers\Api\ShopRegistrationController;
use App\Http\Controllers\Api\ShopSubscriptionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('documentation', [DocumentationController::class, 'ui']);
Route::get('documentation.json', [DocumentationController::class, 'spec']);
Route::get('plans', [PlanController::class, 'index']);

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('signup', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('shops/register', [ShopRegistrationController::class, 'store'])->middleware('throttle:5,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,5');
Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,10');

Route::middleware('api.token')->group(function (): void {
    Route::get('me', [AuthController::class, 'me']);
    Route::match(['put', 'patch'], 'me', [AuthController::class, 'updateMe']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('change-password', [AuthController::class, 'changePassword'])->middleware('throttle:5,1');
    Route::post('new-password', [AuthController::class, 'changePassword'])->middleware('throttle:5,1');

    Route::get('shops/{shop}/subscription', [ShopSubscriptionController::class, 'show']);
    Route::match(['put', 'patch'], 'shops/{shop}', [ShopController::class, 'update']);
    Route::post('shops/{shop}/subscription/payments', [ShopSubscriptionController::class, 'storePayment'])->middleware('throttle:5,1');

    Route::get('shops/{shop}/investors', [ShopInvestorController::class, 'index']);
    Route::post('shops/{shop}/investors', [ShopInvestorController::class, 'store'])->middleware('throttle:10,1');
    Route::match(['put', 'patch'], 'shops/{shop}/investors/{investor}', [ShopInvestorController::class, 'update'])->middleware('throttle:15,1');
    Route::delete('shops/{shop}/investors/{investor}', [ShopInvestorController::class, 'destroy'])->middleware('throttle:10,1');
    Route::get('shops/{shop}/investor-reports', [ShopInvestorReportController::class, 'index']);
    Route::post('shops/{shop}/investor-reports/generate', [ShopInvestorReportController::class, 'generate'])->middleware('throttle:5,1');

    Route::get('shops/{shop}/employees', [ShopEmployeeController::class, 'index']);
    Route::post('shops/{shop}/employees', [ShopEmployeeController::class, 'store'])->middleware('throttle:10,1');
    Route::patch('shops/{shop}/employees/{employee}', [ShopEmployeeController::class, 'update'])->middleware('throttle:15,1');
    Route::delete('shops/{shop}/employees/{employee}', [ShopEmployeeController::class, 'destroy'])->middleware('throttle:10,1');

    Route::get('shops/{shop}/expense-types', [ExpenseController::class, 'types']);
    Route::post('shops/{shop}/expense-types', [ExpenseController::class, 'storeType'])->middleware('throttle:10,1');
    Route::get('shops/{shop}/expenses', [ExpenseController::class, 'index']);
    Route::post('shops/{shop}/expenses', [ExpenseController::class, 'store'])->middleware('throttle:15,1');

    Route::get('shops/{shop}/sales', [SaleController::class, 'index']);
    Route::post('shops/{shop}/sales', [SaleController::class, 'store'])->middleware('throttle:15,1');

    Route::get('shops/{shop}/products/report', [ProductController::class, 'report']);
    Route::get('shops/{shop}/products/export', [ProductController::class, 'export']);
    Route::get('shops/{shop}/products/example-csv', [ProductController::class, 'exampleCsv']);
    Route::post('shops/{shop}/products/import', [ProductController::class, 'import'])->middleware('throttle:5,1');
    Route::get('shops/{shop}/categories', [CategoryController::class, 'index']);
    Route::post('shops/{shop}/categories', [CategoryController::class, 'store']);
    Route::match(['put', 'patch'], 'shops/{shop}/categories/{category}', [CategoryController::class, 'update']);
    Route::get('shops/{shop}/products', [ProductController::class, 'index']);
    Route::post('shops/{shop}/products', [ProductController::class, 'store']);
    Route::match(['put', 'patch'], 'shops/{shop}/products/{product}', [ProductController::class, 'update']);
    Route::get('shops/{shop}/products/{product}', [ProductController::class, 'show']);
    Route::post('shops/{shop}/products/{product}/stock', [ProductController::class, 'updateStock']);
    Route::apiResource('users', UserController::class);
});
