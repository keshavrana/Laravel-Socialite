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
Route::get('/adduser',[TestController::class,'addUser']);
Route::get('tables',[TestController::class,'tables']);
Route::post('import',[TestController::class,'import']);

// Github Login Here
Route::get('sign-in/github',[TestController::class,'github']);
Route::get('sign-in/github/redirect',[TestController::class,'githubRedirect']);
//End Here

// Google Login Here
Route::get('sign-in/google',[TestController::class,'google']);
Route::get('sign-in/google/redirect',[TestController::class,'googleRedirect']);
//End Here


Route::get('logout',function(){
    Session::flush();
    return redirect('/')->with('msg','Logout Successfully');
});
Route::get('/', function () {
    return view('login');
});
