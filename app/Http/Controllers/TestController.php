<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\User;
use Illuminate\Http\Request;
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
}
