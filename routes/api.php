<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\MakeOrderController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\RawMaterialCategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::group(['middleware' => 'makeOrderToken', 'prefix' => 'make-order'], function(){
  Route::get('unit', [MakeOrderController::class, 'getUnit']);
  Route::post('transaction', [TransactionController::class, 'store']);
  Route::get('product', [ProductController::class, 'index']);
  Route::post('login', [UserController::class, 'login']);
});

Route::group(['middleware' => 'accessToken'], function(){
  Route::post('/login/token', [UserController::class, 'loginByToken']);
  
  Route::resources([
    '/company' => CompanyController::class,
    '/branch' => BranchController::class,
    '/unit' => UnitController::class,
    '/category' => CategoryController::class,
    '/product' => ProductController::class,
    '/transaction' => TransactionController::class,
    '/material' => RawMaterialController::class,
    '/material-category' => RawMaterialCategoryController::class,
  ]);
  
  Route::group(['prefix' => 'material'], function(){
    Route::post('/{id}', [RawMaterialController::class, 'update']);
    Route::post('/{id}/update-stock', [RawMaterialController::class, 'updateStock']);
    Route::get('/{id}/stock-history', [RawMaterialController::class, 'getStockHistory']);
  });

  Route::group(['prefix' => 'product'], function(){
    Route::post('/{id}', [ProductController::class, 'update']);
    Route::post('/{id}/update-stock', [ProductController::class, 'updateStock']);
    Route::get('/{id}/stock-history', [ProductController::class, 'getStockHistory']);
  });

  Route::group(['prefix' => 'unit'], function(){
    Route::put('/{id}/update-tax', [UnitController::class, 'updateTax']);
    Route::get('/{id}/download-qrcode', [UnitController::class, 'download']);
    Route::get('/{id}/generate-qrcode', [UnitController::class, 'generateQRCode']);
  });

  Route::group(['prefix' => 'transaction'], function(){
    Route::put('/{id}/status', [TransactionController::class, 'updateStatus']);
  });

});
