<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;
use App\Mail\EmailVerification;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;
use App\CartItem;
use App\Cart;


class UserController extends Controller
{

    protected function register(Request $request)
    { $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:customers',
            'password' => 'required',
            'contact' => 'required',
            'country_code' => 'required',
           ]);
       if ($validator->fails()) {
      
        $error_msg = $validator->errors()->first();
         return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
      
   }else{

      $ctime = round(microtime(true)*1000);
        //expire time after 15 min
        $expire_at = $ctime + 900000;

        $digits = 6;
        $otp = rand(pow(10, $digits - 1), pow(10, $digits) - 1);


            DB::table('customers')->insert([
			    
                'role_id' => 3,
                'name' => $request['name'],
                'email' => $request['email'],
                'contact' => $request['contact'],
                'country_code' => $request['country_code'],
                'password' => bcrypt($request['password']),
                'active' => 0,
                'otp' => $otp,
                'otp_expire_at'=>$expire_at
                ]);
            
       // Mail::to($request->email)->send(new EmailVerification($user));
 
             $name = $request["name"];
             $email = $request["email"];
             $html = "Hi $name
                      Please verify your email with this Otp
                      OTP : $otp";
         mail($request->email, 'Verification Email', $html );

       /*   Mail::send('emails.emailverification', ['name' => $name,'otp' => $otp], 
		function ($m) use ($email) {
            $m->from('smtp@hagglerplanet.com', 'Email Verification');
            $m->to($email)->subject('Email Verification');
        }); */


        return response()->json(["status"=>true,"responseMessage"=>"Your otp send on your mail successfully.","otp"=>$otp]);
     }
   }

     protected function forgot_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
     }else{
		 
		if (!DB::table('customers')->where('email',$request->email)->exists() || User::onlyTrashed()->where('email',$request->email)->exists()) {
            return response()->json(['error' => 'This email does not exists', 'status_code' => 204]);
        }
	 $userdata = DB::table('customers')->where(['email' => $request->email])->first();
		// echo "<pre>"; print_r($userdata); echo "<pre>";
			    $ctime = round(microtime(true)*1000);
				//expire time after 15 min
				$expire_at = $ctime + 900000;

				$digits = 6;
				$otp = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
					$name = $userdata->name;
					$html = "Hi $name
							  OTP to reset your password:
							  OTP : $otp";
					mail($userdata->email, 'Verification Email', $html );
			$user = DB::table('customers')->where(['id'=>$userdata->id])->update([   
               'otp' => $otp
           ]);
		  return response()->json(['status'=>true,'responseMessage'=>"otp sent on email successfully.",'otp'=>$otp, 'user_id'=>$userdata->id,'email'=>$userdata->email]);
		 } 

    }
	
	
     protected function reset_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'otp' => 'required'
        ]);
        if ($validator->fails()) {
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
     }else{
		 
		if (!DB::table('customers')->where(['email' => $request->email,'otp'=>$request['otp']])->first()){
            return response()->json(['error' => 'This otp does is not correct', 'status_code' => 200]);
        }
	   $userdata = DB::table('customers')->where(['email' => $request->email,'otp'=>$request['otp']])->first();
	   $user = DB::table('customers')->where(['id'=>$userdata->id])->update([   
               'otp' => NULL,
			   'active'=>'1',
			   'email_verified_at'=>date('Y-m-d h:i:s'),
			   'password' => bcrypt($request['password']),
           ]);
		  return response()->json(['status'=>true,'responseMessage'=>'Password updated successfully']);
		 } 

    }

    public function login(Request $request)
    {
            $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);
		
           // echo "<pre>";print_r();die;
        if ($validator->fails()) {
            //echo "yes";die;
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
       }
        $ln = $request->input('ln');
        if($ln){
            $ln = $request->input('ln');
        }else{
            $ln = 'en';
        }
        
        $credentials = $request->only('email', 'password');
        // if (empty($credentials)) {
        //     return response()->json(['status' => false, 'responseMessage' => "Email and Password fields are required"]);
        // }elseif (empty($ln) || $ln == ''){
        //     return response()->json(['status' => false, 'responseMessage' => "Ln field is required"]);
        // }
        if (!DB::table('customers')->where('email',$request->email)->exists()) {
            return response()->json(['status' => false,'responseMessage' => 'This email does not exists', 'status_code' => 204]);
        }
        if(DB::table('customers')->where(['email' => $request->email, 'active' => 0])->exists()){
            return response()->json(['status' => false, 'responseMessage' => "Your email is not verified", 'status_code' => 204]);
        }
		
		
		
        $userdata = DB::table('customers')->where(['email' => $request->email,'active' => 1])->first();
		if(Hash::check(request('password'), $userdata->password)){
		
        $cart = Cart::where(['customer_id' => $userdata->id, 'payment_status' => 1])->orderby('id', 'desc')->take(1)->first();
		
            if (isset($cart)) {
                $cart_id = $cart->id;
            
        $cust = DB::table('customers')->where('id',$userdata->id)->get()->first();
        
        $customer_id = $cust->id;
        $totalcart = CartItem::where('cart_id', $cart_id)->get();
          if (count($totalcart) > 0) {
              $total_cart = count($totalcart);
          } else {
              $total_cart = 0;
          }
			}else{
				$total_cart = 0;
			}
          
        if ($userdata) {
            $check = $this->check_auth($request->header(),$userdata);
            if($check['status'] == false){
              return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
              exit;
            }
            $token  = $check['token'];
         if(json_decode($userdata->name , true ) && $ln != ''){ 
             $lns =json_decode($userdata->name);
             $name = $lns->$ln;
             if(json_decode($userdata->description)){
                $desc =json_decode($userdata->description);
                $description = $desc->$ln;
             }else{
                $description = $userdata->description;
             }
             
             $user = array(
                 "id" => $userdata->id,
                 "total_cart" => $total_cart,
                 "shop_id" => ($userdata->shop_id == null) ? "" : $userdata->shop_id,
                 "role_id" => ($userdata->role_id == null) ? "" : $userdata->role_id,
                 "name" => ($name == null) ? "" : $name,
                 "email" => ($userdata->email == null) ? "" : $userdata->email,
                 "mobile" => ($userdata->contact == null) ? "" : $userdata->contact,
                 "dob" => ($userdata->dob == null) ? "" : $userdata->dob,
                 "sex" => ($userdata->sex == null) ? "" : $userdata->sex,
                 "description"=>($description == null) ? "" : $description,
                 "active" => $userdata->active,
                 "image" => $userdata->image,
                 "token"=>$token,
                 "ln"=>$ln
                 
             );
         }else{ 
             $user = array(
                 "id" => $userdata->id,
                 "total_cart" => $total_cart,
                 "shop_id" => ($userdata->shop_id == null) ? "" : $userdata->shop_id,
                 "role_id" => ($userdata->role_id == null) ? "" : $userdata->role_id,
                 "name" => ($userdata->name == null) ? "" : $userdata->name,
                 "email" => ($userdata->email == null) ? "" : $userdata->email,
                 "mobile" => ($userdata->contact == null) ? "" : $userdata->contact,
                 "dob" => ($userdata->dob == null) ? "" : $userdata->dob,
                 "sex" => ($userdata->sex == null) ? "" : $userdata->sex,
                 "description"=>($userdata->description == null) ? "" : $userdata->description,
                 "active" => $userdata->active,
                 "image" => $userdata->image,
                 "token"=>$token,
                 "ln"=>"en"
             );
         }


            return response()->json(['status' => true, 'responseMessage' => "Successfully Login", "responseData" => $user]);
        }
		return response()->json(['status' => false, 'responseMessage' => "Please provide valid login credentials."]);
		}
		
        return response()->json(['status' => false, 'responseMessage' => "Password is wrong."]);
    }
	

public function skip_login(Request $request)
    {
      

            $check = $this->check_auth_skip($request->header());
            if($check['status'] == false){
              return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
              exit;
            }
            $token  = $check['token'];
            //die("this is skip");
      return response()->json(['status' => true, "token" => $token]);
    }

public function country_code(Request $request){
	$validator = Validator::make($request->all(), [
	'country' => 'required'
   ]);
if ($validator->fails()) {

$error_msg = $validator->errors()->first();
 return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
}
else{


$country_sql="select DISTINCT country_code, country from all_countries";
	$country = DB::select($country_sql);
	
	return response()->json(['status' => true, 'responseMessage' => "Successfully Login", "responseData" => $country]);
}


}

/*public function check_auth($header,$userdata)
{
	

  if(!array_key_exists('device-id',$header)){
	   return ['status' => false, 'responseMessage' => "Device id is required"];
	  exit();
  }
  if(!array_key_exists('api-version',$header)){
	   return ['status' => false, 'responseMessage' => "API version is required"];
	  exit();
  }
  if(!array_key_exists('device-type',$header)){
	   return ['status' => false, 'responseMessage' => "Device type is required"];
	  exit();
  }
	$token = Str::random(16);
	$device_id = $header['device-id'][0];
	$api_version = $header['api-version'][0];
	$device_type = $header['device-type'][0];
	
	
  $auth_data = DB::table('user_auth')->where(['device_id' => $device_id,'api_version' =>$api_version,'device_type' => $device_type ])->first();
  if(!empty($auth_data)){
	  
	   DB::table('user_auth')->where('id', $auth_data->id)->update(['token' => $token]);
	   return ['status' => true, 'token' => $token];
	  
  }else{
	   DB::table('user_auth')->insert([
			'customer_id' => $userdata->id,
			'device_id' => $device_id,
			'api_version' => $api_version,
			'device_type' =>$device_type,
			'token'=>$token
			]);
	  return ['status' => true, 'token' => $token];
  }
		  
}*/

public function check_auth_skip($header)
{
  

  if(!array_key_exists('device-id',$header)){
     return ['status' => false, 'responseMessage' => "Device id is required"];
    exit();
  }
  if(!array_key_exists('api-version',$header)){
     return ['status' => false, 'responseMessage' => "API version is required"];
    exit();
  }
  if(!array_key_exists('device-type',$header)){
     return ['status' => false, 'responseMessage' => "Device type is required"];
    exit();
  }
  $token = Str::random(16);
  $device_id = $header['device-id'][0];
  $api_version = $header['api-version'][0];
  $device_type = $header['device-type'][0];
  
  
  $auth_data = DB::table('user_auth')->where(['device_id' => $device_id,'api_version' =>$api_version,'device_type' => $device_type ])->first();
  if(!empty($auth_data)){
    
     DB::table('user_auth')->where('id', $auth_data->id)->update(['token' => $token]);
     return ['status' => true, 'token' => $token];
    
  }else{
     DB::table('user_auth')->insert([
      'device_id' => $device_id,
      'api_version' => $api_version,
      'device_type' =>$device_type,
      'token'=>$token
      ]);
    return ['status' => true, 'token' => $token];
  }
      
}

public function verifyOtp(Request $request)
{
$ln = $request->input('ln');
// if (empty($ln) || $ln == ''){
	// return response()->json(['status' => false, 'responseMessage' => "Ln field is required"]);
// }

if($ln){
	$ln = $request->input('ln');
}else{
	$ln = 'en';
}


$validator = \Validator::make($request->all(), [
	'otp' => 'required|min:6',
	'email' => 'required|email'
]);
if ($validator->fails()) {
$error_msg = $validator->errors()->first();

	return response()->json(['status' => false, 'responseMessage' => $error_msg]);
}
$email = $request->input('email');
$ctime = round(microtime(true)*1000);
if ($email != '' || $email != null)
{
	if (!DB::table('customers')->where(['email' => $request->email, 'otp' => $request->otp])->exists()) {
		return response()->json(['status' => false, 'responseMessage' => "This otp is invalid"]);
	}

	$udata = DB::table('customers')->where(['email' => $request->email, 'otp' => $request->otp])->first();
	//echo $udata ;
	if ($ctime > $udata->otp_expire_at) {
		return response()->json(['status' => false, 'responseMessage' => "This otp is expired"]);
	}
	DB::table('customers')->where('email', $request->email)->update(['email_verified_at'=>Carbon::now(),'active' => 1, 'otp' => 0,'otp_expire_at'=>0]);
	$userdata = DB::table('customers')->where(['email' => $request->email, 'active' => 1])->first();
}

if(json_decode($userdata->name , true ) && $ln != ''){
	$lns =json_decode($userdata->name);
	$desc =json_decode($userdata->description);
	$name = $lns->$ln;
	$description = $desc->$ln;
	$user = array(
		"id" => $userdata->id,
		"shop_id" => ($userdata->shop_id == null) ? "" : $userdata->shop_id,
		"role_id" => ($userdata->role_id == null) ? "" : $userdata->role_id,
		"name" => ($name == null) ? "" : $name,
		"email" => ($userdata->email == null) ? "" : $userdata->email,
		"mobile" => ($userdata->contact == null) ? "" : $userdata->contact,
		"dob" => ($userdata->dob == null) ? "" : $userdata->dob,
		"sex" => ($userdata->sex == null) ? "" : $userdata->sex,
		"description"=>($description == null) ? "" : $description,
		"active" => $userdata->active,
		"ln"=>$ln
	);
}else{ 
	$user = array(
		"id" => $userdata->id,
		"shop_id" => ($userdata->shop_id == null) ? "" : $userdata->shop_id,
		"role_id" => ($userdata->role_id == null) ? "" : $userdata->role_id,
		"name" => ($userdata->name == null) ? "" : $userdata->name,
		"email" => ($userdata->email == null) ? "" : $userdata->email,
		"mobile" => ($userdata->contact == null) ? "" : $userdata->contact,
		"dob" => ($userdata->dob == null) ? "" : $userdata->dob,
		"sex" => ($userdata->sex == null) ? "" : $userdata->sex,
		"description"=>($userdata->description == null) ? "" : $userdata->description,
		"active" => $userdata->active,
		"ln"=>"en"
	);
}
return response()->json(['status' => true, 'responseMessage' => "Successfully Verified account", "responseData" => $user]);
}

public function querylog(Request $request){
\DB::enableQueryLog();
$list = \DB::table("users")->get();
$query = \DB::getQueryLog(DB::table('customers')->where('email', $request->email)->update(['email_verified_at'=>Carbon::now(),'active' => 1, 'otp' => 0,'otp_expire_at'=>0]));
/* echo "<pre>";
print_r(end($query));
echo "</pre>";*/
}

public function updateProfile(Request $request){ 
 //echo "<pre>"; print_r($request->all());die;

$validator = Validator::make($request->all(), [
	'customer_id'=> 'required',
	'first_name' => 'required',
	'last_name' => 'required',
	'mobile' => 'required',
	'gender' => 'required',
	"dob" => 'required',
	"location"=>'required',
  "country_code"=>'required',
	
]);


$check = $this->validate_token($request->header());
if($check['status'] == false){
  return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
  exit;
}
$token  = $check['token'];

/// Code for image upload 

$image_path = ($request->hasfile('image')) ? 'https://v1.hagglerplanet.com/apis/public/storage/'.$request->file('image')->store('') : '';
//echo "<pre>"; print_r($image_path);
if ($validator->fails()) {
	return response()->json(['status' => false, 'responseMessage' => "All fields are required"]);
}
// $validator = \Validator::make($request->all(), [
  //  'email'=>'unique:customers,email,'.$request->customer_id,
//]);
// if ($validator->fails()) {
//    return response()->json(['status' => false, 'responseMessage' => "This email already taken by another user"]);
// }
$user = DB::table('customers')->where(['id'=>$request->customer_id,'active'=>1])->get()->first();
//echo "<pre>"; print_r($user);
if(!$user){
   return response()->json(['status' => false, 'responseMessage' => "User does not exists."]);
}

if(!empty($request->password) || $request->password != ''){

 $update['name'] = $request->first_name.' '. $request->last_name; 
 $update['contact'] = $request->mobile; 
 $update['sex'] = $request->gender; 
 $update['dob'] = $request->dob; 
 $update['description'] = $request->location; 
 $update['country_code'] = $request->country_code; 

 if(!empty($image_path)){
	$update['image'] = $image_path; 
 }

 $user = DB::table('customers')->where(['id'=>$request->customer_id,'active'=>1])->update($update);
   
   if($user === 1){
		return response()->json(['status' => true, 'responseMessage' => "Successfully Updated"]);
   }
}else{
  $update['name'] = $request->first_name.' '. $request->last_name; 
  $update['contact'] = $request->mobile; 
  $update['sex'] = $request->gender; 
  $update['dob'] =$request->dob; 
  $update['description'] = $request->location; 
  $update['country_code'] = $request->country_code; 
	if(!empty($image_path)){
		$update['image'] = $image_path; 
	 }
  $user = DB::table('customers')->where(['id'=>$request->customer_id,'active'=>1])->update($update);
  
   if($user === 1){
		return response()->json(['status' => true, 'responseMessage' => "Successfully Updated"]);
   }
}
return response()->json(['status' => true, 'responseMessage' => "some error found"]);
}

public function updateProfilepic(Request $request){
$validator = Validator::make($request->all(), [
	'user_id'=> 'required',
	"image"=>'required',
]);

if ($validator->fails()) {
	return response()->json(['status' => false, 'responseMessage' => "All fields are required"]);
}


if(!DB::table('customers')->where(['id'=>$request->user_id,'active'=>1])){
   return response()->json(['status' => false, 'responseMessage' => "User does not exists."]);
}
$file = $request->file('image');
$destinationPath = 'uploads';
$fileUpload =  $file->move($destinationPath,$file->getClientOriginalName());
if($fileUpload){
   $user = DB::table('customers')->where(['id'=>$request->user_id,'active'=>1])->update([
	   'image' => $request->mobile
   ]);
   if($user === 1){
		return response()->json(['status' => true, 'responseMessage' => "Successfully Updated"]);
   }
}else{
		   return response()->json(['false' => true, 'responseMessage' => "some error found"]);
}

}

public function viewProfile(Request $request){
    $ln= $request['ln'];
    $user_id= $request['customer_id'];
	
	$check = $this->validate_token($request->header());
		if($check['status'] == false){
		  return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
		  exit;
		}
		$token  = $check['token'];
	
    if(empty($ln) || $ln ==''){
            return response()->json(['status' => false, 'responseMessage' => "Ln field is required"]);
        }
    if(empty($user_id) || $user_id ==''){
                    return response()->json(['status' => false, 'responseMessage' => "Customer Id field is required"]);
        }
    //$user = User::where('id',$request->user_id)->first();
    $user = DB::table('customers')->where('id',$user_id)->get()->first();

    $explode = explode(' ', $user->name);
 //   unset($user->name);
    $user->firstname = $explode[0];
    if(count($explode) == 2){
    $user->lastname = $explode[1];
    }else{
        $user->lastname = '';
    }

	if($user){
		return response()->json(['status' => true, 'responseMessage' => "User Details", "responseData" => $user]);
	}else{
		return response()->json(['status' => False, 'responseMessage' => "No User Found"]);
	}
   }

public function delete_user(Request $request){
       $email=$request['email'];
       if(empty($email) || $email ==''){
                    return response()->json(['status' => false, 'responseMessage' => "User Email field is required"]);
        }
        $user_email = DB::table('customers')->where('email', $email)->delete();
        if($user_email){
        return response()->json(['status' => true, 'responseMessage' => "User has been deleted"]);
            }else{
                return response()->json(['status' => False, 'responseMessage' => "No User Found"]);
            }
    }
	
public function cardSave(Request $request){			
	$validator = Validator::make($request->all(), [
		'customer_id' => 'required',
		'name' => 'required',
		'card' => 'required',
		'exp_month' => 'required',
		'exp_year' => 'required',
		'cvv' => 'required'
	]);
	if ($validator->fails()) {
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
   }else{
	   DB::table('cards')->insert([
                'customer_id' => $request['customer_id'],
				'name' => $request['name'],
                'card' => $request['card'],
                'exp_month' => $request['exp_month'],
                'exp_year' => $request['exp_year'],
                'cvv' => $request['cvv']
                ]);
		}
   return response()->json(['status' => True, 'responseMessage' => "Card Added Successfully"]);	
}
	
public function customerCardDetails(Request $request){
	$data=[];
		$validator = Validator::make($request->all(), [
            'customer_id' => 'required'
		
        ]);
		
		$check = $this->validate_token($request->header());
		if($check['status'] == false){
		  return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
		  exit;
		}
		$token  = $check['token'];
		
        if ($validator->fails()) {
            //echo "yes";die;
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
   }else{

 $userdata = DB::table('cards')->where(['customer_id' => $request->customer_id])->get();


       foreach ($userdata as $p) {
		$data[]=$p;	
    }
	////echo "<pre>";print_r($data);die;
	$response = array(
            'card_detail' => $data,
        );
        return response()->json(['status' => true, 'responseMessage' => "Successfully", "responseData" => $response]);	
}

		
	}

public function cardDelete(Request $request){
		$validator = Validator::make($request->all(), [
            'id' => 'required',
            'customer_id' => 'required'
        ]);
		
		$check = $this->validate_token($request->header());
		if($check['status'] == false){
		  return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
		  exit;
		}
		$token  = $check['token'];
		
		if ($validator->fails()) {
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
		}
		else{
		DB::table('cards')->where('id', $request->id)->delete([
            'id' => $request['id'],
            ]);
			
		return response()->json(['status' => True, 'responseMessage' => "Card Delete Successfully"]);	
		}
		return response()->json(['status' => False, 'responseMessage' => "Somwthing Went Wrong"]);


    }
	
	public function remove_address(Request $request){
		$validator = Validator::make($request->all(), [
            'id' => 'required',
            'customer_id' => 'required'
        ]);
		
		$check = $this->validate_token($request->header());
		if($check['status'] == false){
		  return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
		  exit;
		}
		$token  = $check['token'];
		
		if ($validator->fails()) {
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
		}
		else{
		DB::table('addresses')->where('id', $request->id)->delete([
            'id' => $request['id'],
            ]);
			
		return response()->json(['status' => True, 'responseMessage' => "address remove Successfully"]);	
		}
		return response()->json(['status' => False, 'responseMessage' => "Somwthing Went Wrong"]);


    }
	
public function changePassword(Request $request){
		$validator = Validator::make($request->all(), [
            'id' => 'required',
            'old_password' => 'required',
            'new_password' => 'required',
            'confirm_password' => 'required |same:new_password'
        ]);
		if ($validator->fails()) {
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
		}
		else{
		$userdata = DB::table('customers')->where(['id' => $request->id,'active' => 1])->first();
		//echo "<pre>"; print_r($userdata); die;
		}
		if(Hash::check(request('old_password'), $userdata->password)){
			
		 DB::table('customers')->where('id', $request->id)->update([
                'password' => bcrypt($request['new_password']),
                ]);
				
		return response()->json(['status' => True, 'responseMessage' => "Password Updated Successfully"]);
				
		}
		else{
			return response()->json(['status' => False, 'responseMessage' => "Old Password is wrong"]);	
		}
	}
	
public function contact(Request $request){
			$validator = Validator::make($request->all(), [
            'customer_id' => 'required',
            'text' => 'required'
        ]);
		
		/* $check = $this->validate_token($request->header());
		if($check['status'] == false){
		  return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
		  exit;
		}
		$token  = $check['token']; */
		
		if ($validator->fails()) {
		$error_msg = $validator->errors()->first();
		return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
		}
		else{
			     DB::table('contact')->insert([
                'customer_id' => $request['customer_id'],
                'text' => $request['text']
                ]);
				
		    $customer_id = $request['customer_id'];
            $text = $request['text'];
            $html = "Hi Admin Your Customer $customer_id Has Some query Kindly resolve it
               Query : $text";

        Mail::send('emails.contactbyemail', ['customer_id' => $customer_id,'text' => $text], function ($m) use ($text) {
            $m->from('smtp@hagglerplanet.com', 'Customer Query');

            $m->to('omtest572@gmail.com')->subject('Customer Query');
        });
				
		return response()->json(['status' => True, 'responseMessage' => "Query Send Successfully"]);
		}
	}
	
	
public function eWallet(Request $request){
	$data=[];
	$validator = Validator::make($request->all(), [
    'customer_id' => 'required'
    ]);
	
	$check = $this->validate_token($request->header());
		if($check['status'] == false){
		  return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
		  exit;
		}
		$token  = $check['token'];
	
    if ($validator->fails()) {
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
    }else{
   $userdata = DB::table('e_wallet')->where(['customer_id' => $request->customer_id])->get();
	foreach ($userdata as $p) {
	$data[]=$p;	
    }
	$response = array(
        'e_wallet' => $data,
    );
    return response()->json(['status' => true, 'responseMessage' => "Successfully", "responseData" => $response]);	
	}	
	}

  public function update_ewallet(Request $request){ 
// echo "<pre>"; print_r($request->all());

$validator = Validator::make($request->all(), [
  'customer_id'=> 'required',
  'fname' => 'required',
  'lname' => 'required',
  'dob' => 'required',
  'email' => 'required',
  'mobile_number' => 'required',
  'country_code' => 'required',
  'address' => 'required',
  'address_2' => 'required',
  "state" => 'required',
  "city"=>'required',
  "zip_code"=>'required',
  "doc_state"=>'required',
  "id_card"=>'required',
  "id_card2"=>'required',
  "id_card3"=>'required',
  
]);


$check = $this->validate_token($request->header());
if($check['status'] == false){
  return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
  exit;
}
$token  = $check['token'];

if ($validator->fails()) {
  return response()->json(['status' => false, 'responseMessage' => "All fields are required"]);
}

$user_ids = $request['customer_id'];
$user = DB::table('e_wallet')->where(['customer_id'=>$user_ids])->first();
//echo "<pre>";print_r($user);die;
if(!$user){
   return response()->json(['status' => false, 'responseMessage' => "User does not exists."]);
}

    $image_path1 = ($request->hasfile('id_card')) ? 'https://v1.hagglerplanet.com/apis/public/storage/'.$request->file('id_card')->store('') : '';
   
    $image_path2 = ($request->hasfile('id_card2')) ? 'https://v1.hagglerplanet.com/apis/public/storage/'.$request->file('id_card2')->store('') : '';
    $image_path3 = ($request->hasfile('id_card3')) ? 'https://v1.hagglerplanet.com/apis/public/storage/'.$request->file('id_card3')->store('') : '';

           $update['fname'] = $request['fname']; 
           $update['lname'] = $request['lname']; 
           $update['dob'] = $request['dob']; 
           $update['email'] = $request['email']; 
           $update['mobile_number'] = $request['mobile_number']; 
           $update['country_code'] = $request['country_code']; 
           $update['address'] = $request['address']; 
           $update['address_2'] = $request['address_2']; 
           $update['state'] = $request['state']; 
           $update['city'] = $request['city'];
           $update['zip_code'] = $request['zip_code'];
           $update['doc_state'] = $request['doc_state'];  
           //$update['passport_number'] = $request['passport_number']; 
           $update['id_card'] = $image_path1; 
           $update['id_card2'] = $image_path2;
           $update['id_card3'] = $image_path3; 
           $update['updated_at'] = date("Y-m-d h:i:s"); 
           $update['status'] = 'Pending'; 


           $user = DB::table('e_wallet')->where(['wallet_id'=>$user->wallet_id])->update($update);
             
             if($user === 1){
              return response()->json(['status' => true, 'responseMessage' => "Successfully Updated"]);
             }

          return response()->json(['status' => true, 'responseMessage' => "some error found"]);
          }

	
public function addMoney(Request $request){
		$validator = Validator::make($request->all(), [
        'customer_id' => 'required',
		    'card_num' => 'required',
        'card_cvv' => 'required',
        'card_expiry_month' => 'required',
        'card_expiry_year' => 'required',
        'amount' => 'required'
       ]);
	   
	  
	   $check = $this->validate_token($request->header());
		if($check['status'] == false){
		  return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
		  exit;
		}
		$token  = $check['token'];
	   
    if ($validator->fails()) {
        //echo "yes";die;
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
   }else{
	    
		$digits = 6;
        $transaction = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
		DB::table('wallet_transaction')->insert([
        'customer_id' => $request['customer_id'],
		'card_num' => $request['card_num'],
        'card_cvv' => $request['card_cvv'],
        'card_expiry_month' => $request['card_expiry_month'],
        'card_expiry_year' => $request['card_expiry_year'],
        'amount' => $request['amount'],
        'transaction_id' => bcrypt($transaction),
        'direction' => "credit"
        ]);
		$id = DB::getPdo()->lastInsertId();
	
	$moneyadd = DB::table('wallet_transaction')->where(['id' => $id,'customer_id' => $request->customer_id])->first();
	$amountadd = $request->amount;
//echo "<pre>"; print_r($moneyadd); die;
	
	$moneyadd1 = DB::table('e_wallet')->where(['customer_id' => $request->customer_id])->first();
	$amountadd1 = (double)$moneyadd1->wallet_balance;
	//echo "<pre>"; print_r($amountadd1); die;
  $avilable_balance =$amountadd + $amountadd1;
	DB::table('e_wallet')->where('customer_id', $request->customer_id)->update([
        'wallet_balance' => $avilable_balance,
        ]);
				
   }

   $notification = DB::select("SELECT * FROM `user_auth` WHERE `customer_id` = $request->customer_id");
      //echo "<pre>";print_r($notification);die;

              $title = "Money Added";
              $text = "You have added money successfully";
              $description = "You have added money successfully";
              $customer_id = $request->customer_id;
              $order_id = '';
              $type="Add Money";

              DB::table('app_notification')->insert([
                'customer_id' => $request['customer_id'],
                'title' =>  $title,
                'description' => $description,
                'related_to' => "order"
                
            ]);

            $noti_id = DB::getPdo()->lastInsertId();
             //echo "<pre>";print_r($noti_id);die;
      foreach ($notification as $key => $value) {
        //echo "<pre>";print_r($value);
                   $to = $value->device_id;

         $message = $this->send_notification($to,$title,$text,$description,$customer_id,$order_id,$type);
                 //echo "<pre>";print_r($message);
        
      }
            
   

   return response()->json(['status' => True, 'transaction_id' => $moneyadd->transaction_id ,"avilable_balance" => $avilable_balance,'responseMessage' => "Money Added Successfully"]);	
	
}

public function addWalletUser(Request $request){
		$validator = Validator::make($request->all(), [
        'customer_id' => 'required',
		'fname' => 'required',
        'lname' => 'required',
        'dob' => 'required',
        'email' => 'required',
        'mobile_number' => 'required',
        'country_code' => 'required',
        'address' => 'required',
        'address_2' => 'required',
        'state' => 'required',
		'city' => 'required',
		'zip_code' => 'required',
		'doc_state' => 'required',
		//'passport_number' => 'required',
		'id_card' => 'required',
		'id_card2' => 'required',
		'id_card3' => 'required'
       ]);
	   
	   $check = $this->validate_token($request->header());
		if($check['status'] == false){
		  return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
		  exit;
		}
		$token  = $check['token'];

    $user = DB::table('e_wallet')->where(['customer_id'=>$request['customer_id']])->first();
    if(!empty($user)){
       return response()->json(['status'=>False,'responseMessage'=>"Customer Ewallet already Exsists."]);
    }

    $image_path1 = ($request->hasfile('id_card')) ? 'https://v1.hagglerplanet.com/apis/public/storage/'.$request->file('id_card')->store('') : '';
   
    $image_path2 = ($request->hasfile('id_card2')) ? 'https://v1.hagglerplanet.com/apis/public/storage/'.$request->file('id_card2')->store('') : '';
    $image_path3 = ($request->hasfile('id_card3')) ? 'https://v1.hagglerplanet.com/apis/public/storage/'.$request->file('id_card3')->store('') : '';

	   //echo "<pre>";print_r($image_path1);
	   //$image_path1 = ($request->hasfile('id_card')) ? $request->file('id_card')->store('image') : '';
	   //$image_path2 = ($request->hasfile('id_card2')) ? $request->file('id_card2')->store('image') : '';
	  // $image_path3 = ($request->hasfile('id_card3')) ? $request->file('id_card3')->store('image') : '';
    if ($validator->fails()) {
        //echo "yes";die;
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
   }else{

	DB::table('e_wallet')->insert([
        'customer_id' => $request['customer_id'],
		'fname' => $request['fname'],
        'lname' => $request['lname'],
        'dob' => $request['dob'],
        'email' => $request['email'],
        'mobile_number' => $request['mobile_number'],
        'country_code' => $request['country_code'],
        'address' => $request['address'],
        'address_2' => $request['address_2'],
        'state' => $request['state'],
        'city' => $request['city'],
        'zip_code' => $request['zip_code'],
        'doc_state' => $request['doc_state'],
        //'passport_number' => $request['passport_number'],
        'id_card' => $image_path1,
        'id_card2' => $image_path2,
        'id_card3' => $image_path3,
        'created_at' => date("Y-m-d h:i:s"),
		    'status' => 'Pending'
        ]);
				
   }
   return response()->json(['status' => True, 'responseMessage' => "Wallet User Added Successfully"]);	
	
}

public function walletForm(Request $request){
	$data=[];
	$validator = Validator::make($request->all(), [
    'customer_id' => 'required'
    ]);
    if ($validator->fails()) {
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
    }else{		
   $userdata = DB::table('wallet_form')->where(['customer_id' => $request->customer_id])->get();
	foreach ($userdata as $p) {
	$data[]=$p;	
    }
	$response = array(
        'wallet_form' => $data,
    );
    return response()->json(['status' => true, 'responseMessage' => "Successfully", "responseData" => $response]);	
	}	
	}

public function allCity(Request $request){
		$validator = Validator::make($request->all(), [
        'name' => 'required'
       ]);
    if ($validator->fails()) {
        //echo "yes";die;
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
   }else{
	$state1 = DB::table('all_countries')->where(['state'=>$request->name])->get();
	//$state = DB::table('states')->where(['id'=>$request->id])->get();
	echo "<pre>";print_r($state1);die;
	
}	

}


 
/*public function login_by_socialmedia(Request $request)
    {
            $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
    
      if ($validator->fails()) {
            //echo "yes";die;
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
       }
        $ln = $request->input('ln');
        if($ln){
            $ln = $request->input('ln');
        }else{
            $ln = 'en';
        }
        
        $credentials = $request->only('email');
        // if (empty($credentials)) {
        //     return response()->json(['status' => false, 'responseMessage' => "Email and Password fields are required"]);
        // }elseif (empty($ln) || $ln == ''){
        //     return response()->json(['status' => false, 'responseMessage' => "Ln field is required"]);
        // }
  if (!DB::table('customers')->where('email',$request->email)->exists()) {

          $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:customers',
            'token' => 'required'
            //'country_code' => 'required',
           ]);
       if ($validator->fails()) {
      
        $error_msg = $validator->errors()->first();
         return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
      
   }else{

            DB::table('customers')->insert([
          
                'role_id' => 3,
                'name' => $request['name'],
                'email' => $request['email'],
                //'contact' => $request['contact'],
                //'country_code' => $request['country_code'],
                //password' => bcrypt($request['password']),
                'active' => 1,
                //'otp' => $otp,
                //'otp_expire_at'=>$expire_at
                ]);

            
            

      
     }
  
  }
        if(DB::table('customers')->where(['email' => $request->email, 'active' => 0])->exists()){
            return response()->json(['status' => false, 'responseMessage' => "Your email is not verified", 'status_code' => 204]);
        }
    
        $userdata = DB::table('customers')->where(['email' => $request->email,'active' => 1])->first();
    if($userdata->email){
    
        $cart = Cart::where(['customer_id' => $userdata->id, 'payment_status' => 1])->orderby('id', 'desc')->take(1)->first();
    
            if (isset($cart)) {
                $cart_id = $cart->id;
            
        $cust = DB::table('customers')->where('id',$userdata->id)->get()->first();
        
        $customer_id = $cust->id;
        $totalcart = CartItem::where('cart_id', $cart_id)->get();
          if (count($totalcart) > 0) {
              $total_cart = count($totalcart);
          } else {
              $total_cart = 0;
          }
      }else{
        $total_cart = 0;
      }
          
        if ($userdata) {
            $check = $this->check_auth($request->header(),$userdata);
            if($check['status'] == false){
              return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
              exit;
            }
            $token  = $check['token'];
         if(json_decode($userdata->name , true ) && $ln != ''){ 
             $lns =json_decode($userdata->name);
             $name = $lns->$ln;
             if(json_decode($userdata->description)){
                $desc =json_decode($userdata->description);
                $description = $desc->$ln;
             }else{
                $description = $userdata->description;
             }
             
             $user = array(
                 "id" => $userdata->id,
                 "total_cart" => $total_cart,
                 "shop_id" => ($userdata->shop_id == null) ? "" : $userdata->shop_id,
                 "role_id" => ($userdata->role_id == null) ? "" : $userdata->role_id,
                 "name" => ($name == null) ? "" : $name,
                 "email" => ($userdata->email == null) ? "" : $userdata->email,
                 "mobile" => ($userdata->contact == null) ? "" : $userdata->contact,
                 "dob" => ($userdata->dob == null) ? "" : $userdata->dob,
                 "sex" => ($userdata->sex == null) ? "" : $userdata->sex,
                 "description"=>($description == null) ? "" : $description,
                 "active" => $userdata->active,
                 "image" => $userdata->image,
                 "token"=>$token,
                 "ln"=>$ln
                 
             );
         }else{ 
             $user = array(
                 "id" => $userdata->id,
                 "total_cart" => $total_cart,
                 "shop_id" => ($userdata->shop_id == null) ? "" : $userdata->shop_id,
                 "role_id" => ($userdata->role_id == null) ? "" : $userdata->role_id,
                 "name" => ($userdata->name == null) ? "" : $userdata->name,
                 "email" => ($userdata->email == null) ? "" : $userdata->email,
                 "mobile" => ($userdata->contact == null) ? "" : $userdata->contact,
                 "dob" => ($userdata->dob == null) ? "" : $userdata->dob,
                 "sex" => ($userdata->sex == null) ? "" : $userdata->sex,
                 "description"=>($userdata->description == null) ? "" : $userdata->description,
                 "active" => $userdata->active,
                 "image" => $userdata->image,
                 "token"=>$token,
                 "ln"=>"en"
             );
         }


            return response()->json(['status' => true, 'responseMessage' => "Successfully Login", "responseData" => $user]);
        }
    return response()->json(['status' => false, 'responseMessage' => "Please provide valid login credentials."]);
    }
    
        return response()->json(['status' => false, 'responseMessage' => "Password is wrong."]);
    }*/



public function check_auth($header,$userdata)
{
  
//echo "<pre>";print_r($header);die("testing");
  if(!array_key_exists('device-id',$header)){
     return ['status' => false, 'responseMessage' => "Device id is required"];
    exit();
  }
  if(!array_key_exists('api-version',$header)){
     return ['status' => false, 'responseMessage' => "API version is required"];
    exit();
  }
  if(!array_key_exists('device-type',$header)){
     return ['status' => false, 'responseMessage' => "Device type is required"];
    exit();
  }
  $token = Str::random(16);
  $device_id = $header['device-id'][0];
  $api_version = $header['api-version'][0];
  $device_type = $header['device-type'][0];
  
  
  $auth_data = DB::table('user_auth')->where(['device_id' => $device_id,'api_version' =>$api_version,'device_type' => $device_type ])->first();
  if(!empty($auth_data)){
    
     DB::table('user_auth')->where('id', $auth_data->id)->update(['token' => $token]);
     return ['status' => true, 'token' => $token];
    
  }else{
  //echo "<pre>";print_r($userdata);die("testing");
 if($userdata->media_token){
    $media_token = $userdata->media_token;
 }else{
    $media_token = '';
 }

 if($userdata->social_account){
    $social_account = $userdata->social_account;
 }else{
    $social_account = '';
 }



     DB::table('user_auth')->insert([
      'customer_id' => $userdata->id,
      'device_id' => $device_id,
      'api_version' => $api_version,
      'device_type' =>$device_type,
      'token'=>$token,
      //''=> $media_type 
      'media_token' => $media_token, 
      'social_account'=>  $social_account
      ]);
    return ['status' => true, 'token' => $token];
  }
      
}


    public function login_by_socialmedia(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
    
      if ($validator->fails()) {
            //echo "yes";die;
       $error_msg = $validator->errors()->first();
       return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
       }
        $ln = $request->input('ln');
        if($ln){
            $ln = $request->input('ln');
        }else{
            $ln = 'en';
        }
        
        $credentials = $request->only('email');
        // if (empty($credentials)) {
        //     return response()->json(['status' => false, 'responseMessage' => "Email and Password fields are required"]);
        // }elseif (empty($ln) || $ln == ''){
        //     return response()->json(['status' => false, 'responseMessage' => "Ln field is required"]);
        // }
  if (!DB::table('customers')->where('email',$request->email)->exists()) {

          $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:customers',
            'token' => 'required',
            "media" => 'required'
            //'country_code' => 'required',
           ]);
       if ($validator->fails()) {
      
        $error_msg = $validator->errors()->first();
         return response()->json(['status'=>False,'responseMessage'=>$error_msg]);
      
   }else{

            DB::table('customers')->insert([
          
                'role_id' => 3,
                'name' => $request['name'],
                'email' => $request['email'],
                //'contact' => $request['contact'],
                //'country_code' => $request['country_code'],
                //password' => bcrypt($request['password']),
                'active' => 1,
                //'otp' => $otp,
                //'otp_expire_at'=>$expire_at
                ]);

            
            

      
     }

  }
             
  
        if(DB::table('customers')->where(['email' => $request->email, 'active' => 0])->exists()){
            return response()->json(['status' => false, 'responseMessage' => "Your email is not verified", 'status_code' => 204]);
        }
    
        $userdata = DB::table('customers')->where(['email' => $request->email,'active' => 1])->first();
    if($userdata->email){
    
        $cart = Cart::where(['customer_id' => $userdata->id, 'payment_status' => 1])->orderby('id', 'desc')->take(1)->first();
    
            if (isset($cart)) {
                $cart_id = $cart->id;
            
        $cust = DB::table('customers')->where('id',$userdata->id)->get()->first();
        
        $customer_id = $cust->id;
        $totalcart = CartItem::where('cart_id', $cart_id)->get();
          if (count($totalcart) > 0) {
              $total_cart = count($totalcart);
          } else {
              $total_cart = 0;
          }
      }else{
        $total_cart = 0;
      }
            
        if ($userdata) {
            //echo "<pre>"; print_r($request->header(),$userdata); die("testing");
            /*if($check['status'] == false){
              return response()->json(['status'=>False,'responseMessage'=>$check['responseMessage']]);
              exit;
            }
*/
             //$token  = $check['token'];

             // $userdata['media_token'] = $request['media'];
             // $userdata['social_account'] = $token;
              //echo "<pre>";print_r($request['media']);die("gfyy");
             $userdata->media_token = $request['token'];
             $userdata->social_account = $request['media']; 
             
           // echo "<pre>";print_r($userdata);die("gfyy");
            $check = $this->check_auth($request->header(),$userdata);
            
            $token = $check['token'];
            //echo "<pre>";print_r($token);die("hghdg");
                  /*
               DB::table('user_auth')->insert([
                'customer_id' => $userdata->id,
                'social_account' => $request['media'],
                'media_token' => $token

                ]);
                */
               // echo "<pre>";print_r($check);die("gfyy");

         if(json_decode($userdata->name , true ) && $ln != ''){ 
             $lns =json_decode($userdata->name);
             $name = $lns->$ln;
             if(json_decode($userdata->description)){
                $desc =json_decode($userdata->description);
                $description = $desc->$ln; 
             }else{
                $description = $userdata->description;
             }
             //$this->
             $user = array(
                 "id" => $userdata->id,
                 "total_cart" => $total_cart,
                 "shop_id" => ($userdata->shop_id == null) ? "" : $userdata->shop_id,
                 "role_id" => ($userdata->role_id == null) ? "" : $userdata->role_id,
                 "name" => ($name == null) ? "" : $name,
                 "email" => ($userdata->email == null) ? "" : $userdata->email,
                 "mobile" => ($userdata->contact == null) ? "" : $userdata->contact,
                 "dob" => ($userdata->dob == null) ? "" : $userdata->dob,
                 "sex" => ($userdata->sex == null) ? "" : $userdata->sex,
                 "description"=>($description == null) ? "" : $description,
                 "active" => $userdata->active,
                 "image" => $userdata->image,
                 "token"=>$token,
                 "ln"=>$ln,
                // "media_token"=> $request['token'],
                // "social_account"=> $request['media']
                 
             );
         }else{ 
             $user = array(
                 "id" => $userdata->id,
                 "total_cart" => $total_cart,
                 "shop_id" => ($userdata->shop_id == null) ? "" : $userdata->shop_id,
                 "role_id" => ($userdata->role_id == null) ? "" : $userdata->role_id,
                 "name" => ($userdata->name == null) ? "" : $userdata->name,
                 "email" => ($userdata->email == null) ? "" : $userdata->email,
                 "mobile" => ($userdata->contact == null) ? "" : $userdata->contact,
                 "dob" => ($userdata->dob == null) ? "" : $userdata->dob,
                 "sex" => ($userdata->sex == null) ? "" : $userdata->sex,
                 "description"=>($userdata->description == null) ? "" : $userdata->description,
                 "active" => $userdata->active,
                 "image" => $userdata->image,
                 "token"=>$token,
                 "ln"=>"en",
                // "media_token"=> $request['token'],
                // "social_account"=> $request['media']
             );
         }


            return response()->json(['status' => true, 'responseMessage' => "Successfully Login", "responseData" => $user]);
        }
    return response()->json(['status' => false, 'responseMessage' => "Please provide valid login credentials."]);
    }
    
      //  return response()->json(['status' => false, 'responseMessage' => "Password is wrong."]);
    }

 
}
