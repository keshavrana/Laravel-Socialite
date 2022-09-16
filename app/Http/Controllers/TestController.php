<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Login;
use App\Models\User;
use Illuminate\Http\Request;
use App\Imports\CustomerImport;
use Excel;
use DB;
use Socialite;
use Illuminate\Support\Facades\Validator;

class TestController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function form(){
        return view('form');
    } 
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'=> 'required',
            'email'=> 'required|email|unique:users,email',
            'password'=> 'required'
        ]);
        if ($validator->fails()) {
            $error_msg = $validator->errors()->first();
            return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
        }else{		
            return User::create($request->all());
        }
    }

    public function userLogin(Request $request){
        $data = Login::select('name')->where('name',$request->username)->where('password',$request->password)->first();
        if(!empty($data)){
            $request->session()->put('user', $request->username);
            return redirect('/dashboard');
        }
        else{
            return redirect()->back()->with('error', 'Please Check Your Credential');
        }

    }

    public function register(Request $request){
        if(empty($request->all())){
            return view('register');
        }
        else{
            $all_request = [
                'name' => $request->name,
                'password' => $request->password
            ];
            $result = Login::insert($all_request);
            if($result){
                $request->session()->flash('msg','Registration Done Please Login Here');
                return redirect('/');
            }
            else{
                return redirect()->back()->with('msg', 'Something Wrong Here');   

            }
        }
    }

    public function dashboard(){
        return view('dashboard');
    }

    public function import(Request $request)
    {
        
        $upload=$request->file('fileupl');
         //dd($upload);
        $filePath=$upload->getRealPath();
        //dd($filePath);
        $data = Excel::import(new CustomerImport,$filePath);
        //dd($data);
        echo "Record Has Been Imported Successfully"; 
        return redirect('adduser');
    }

    public function tables(){
        return view('tables');
    }

    public function addUser(){
        return view('adduser');
    }

    public function github(){
        return Socialite::driver('github')->redirect();
    }

    public function githubRedirect(){
        $user_info = Socialite::driver('github')->user();
        $user = Login::firstOrCreate([
            'email' => $user_info->email
        ], [
            'name' => $user_info->name,
            'password' => $user_info->name,
        ]);

        session()->put('user', $user_info->name);
        return redirect('/dashboard');

    }
}
