<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Login;
use App\Models\User;
use Illuminate\Http\Request;
use DB;
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
            echo "login Successfully";
        }
        else{
            echo "Something Wrong Happened";
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
                echo "Inserted Succussfully";
            }
            else{
                echo "Something Wrong";
            }
        }
    }
}
