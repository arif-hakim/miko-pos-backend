<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\CategoryController;

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
Route::group(['middleware' => 'accessToken'], function(){
  Route::post('/login/token', [UserController::class, 'loginByToken']);
  
  Route::resources([
    '/company' => CompanyController::class,
    '/branch' => BranchController::class,
    '/unit' => UnitController::class,
    '/category' => CategoryController::class,
  ]);
  
  Route::group(['prefix' => 'company'], function(){
  });

  Route::group(['prefix' => 'branch'], function(){
  });

  Route::group(['prefix' => 'unit'], function(){
  });
});
