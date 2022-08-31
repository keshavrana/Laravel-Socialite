<?php
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/form',[TestController::class,'form']);
Route::get('/register',[TestController::class,'register']);
Route::get('/dashboard',[TestController::class,'dashboard']);
Route::get('/userlogin',[TestController::class,'userLogin']);
Route::get('/', function () {
    return view('login');
});
