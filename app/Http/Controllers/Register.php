<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\PasswordReset;
use Redirect;
use Carbon\Carbon;
use Log;
use Hash;
class Register extends Controller
{

   
   



  public function register(Request $request)
    {
        try{
            $validation =  Validator::make($request->all(), [
                'phone' => 'required|unique:users,phone',
                'password' => 'required|numeric',
                'sponsor' => 'required|exists:users,username',
                'code' => 'required',              
            ]);

            
            if($validation->fails()) {

                Log::info($validation->getMessageBag()->first());
     
                // return Redirect::back()->withErrors($validation->getMessageBag()->first())->withInput();
                return response()->json([
                    'success' => false,
                    'errors' => $validation->errors()->first() // Returns all error messages
                ], 422);
            }
            //check if email exist
          
            $code = $request->code;
            if (PasswordReset::where('token', $code)->where('email', $request->phone)->count() != 1) {
                // $notify[] = ['error', 'Invalid token'];
                // return Redirect::back()->withNotify($notify);
                return response()->json([
                    'success' => false,
                    'errors' => 'Invalid token' // Returns all error messages
                ], 422);
            }
        //     $number=$request->phone;
        //    $this->SendSMS($number,$otp);
            
            $user = User::where('username',$request->sponsor)->first();
            if(!$user)
            {
                // return Redirect::back()->withErrors(array('Introducer ID Not Active'));
                return response()->json([
                    'success' => false,
                    'errors' => 'Introducer ID Not Active' // Returns all error messages
                ], 422);
            }
            $totalID = User::count();
            $totalID++;
         $username =substr(rand(),-2).substr(time(),-3).substr(mt_rand(),-2);
            
           $tpassword =substr(time(),-2).substr(rand(),-2).substr(mt_rand(),-1);
            $post_array  = $request->all();
                //  
            // $data['name'] = $post_array['name'];
            $data['phone'] = $post_array['phone'];
          
            $data['username'] = $username;
            $data['password'] =   Hash::make($post_array['password']);
            $data['tpassword'] =   Hash::make($tpassword);
            $data['TPSR'] =  $tpassword;
            $data['PSR'] =  $post_array['password'];

            $data['sponsor'] = $user->id;
            $data['package'] = 0;
            $data['jdate'] = date('Y-m-d');
            $data['created_at'] = Carbon::now();
            $data['remember_token'] = substr(rand(),-7).substr(time(),-5).substr(mt_rand(),-4);
            $sponsor_user =  User::orderBy('id','desc')->limit(1)->first();
             $data['level'] = $user->level+1;

         
            $data['ParentId'] =  $sponsor_user->id;
            $user_data =  User::create($data);
            $registered_user_id = $user_data['id'];
            $user = User::find($registered_user_id);
            Auth::loginUsingId($registered_user_id);
          
        //    sendEmail($user->email, 'Welcome to '.siteName(), [
        //         'name' => $user->name,
        //         'username' => $user->username,
        //         'password' => $user->PSR,
        //         'tpassword' => $user->TPSR,
        //         'viewpage' => 'register_sucess',
        //          'link'=>route('login'),
        //     ]);
            
        return response()->json([
            'success' => true,
            'message' => 'Register Sucessfully' // Returns all error messages
        ], 200);
           
            //  return redirect()->route('register_sucess')->with('messages', $user);

        }
        catch(\Exception $e){
            Log::info('error here');
            Log::info($e->getMessage());
            print_r($e->getMessage());
            die('hi');

      
            // return back()->withErrors('error', $e->getMessage())->withInput();
            return response()->json([
                'success' => false,
        
                'error' => $e->getMessage(),
                'input' => $request->all() // Optionally include the input data
            ], 400);
           
        }

          
    }
    

    function verificationCode($length)
    {
        if ($length == 0) return 0;
        $min = pow(10, $length - 1);
        $max = 0;
        while ($length > 0 && $length--) {
            $max = ($max * 10) + 9;
        }
        return random_int($min, $max);
    }
        

 function SendSMS($number,$otp)
    {

    //   $message = "Dear ".$name." You have Registered Successfully. Your User ID is ".$userid." Password is ".$password." and Transaction password is ".$tpassword." Thank you for join us MANEUVER";
    $message = "Dear Customer, $otp is your OTP for reset password. This is valid for 5 minutes. MHLDAY";
    $message = urlencode($message);     

    $url ="http://nimbusit.net/api/pushsms?user=210512&authkey=925Mgitw2g3Q&sender=MHLDAY&mobile=".$number."&text=".$message."&entityid=1701172726198989039&templateid=1707172983314741064&rpt=1";


    //  Initiate curl
    $ch = curl_init();
    // Disable SSL verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // Will return the response, if false it print the response
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Set the url
    curl_setopt($ch, CURLOPT_URL,$url);
    // Execute
    $result=curl_exec($ch);
    // Closing
    curl_close($ch);
    return true;
    }
    
    public function sendCodephone(Request $request)
    {
        try {
            $code = $this->verificationCode(4);
            $number = $request->number; 
    
            // Delete any existing password reset entries for this number
            PasswordReset::where('email', $number)->delete();
    
            // Create a new password reset entry
            $passwordReset = new PasswordReset();
            $passwordReset->email = $number;
            $passwordReset->token = $code;
            $passwordReset->created_at = \Carbon\Carbon::now();
            $passwordReset->save();
    
            // Send the OTP via SMS
            $this->SendSMS($number, $code);
    
            // Return a JSON response indicating success
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully.',
                'data' => [
                    'number' => $number,
                    'otp' => $code // Include OTP for testing purposes (remove in production)
                ]
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors and return a JSON error response
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   
   
    // In RegistrationController.php
public function showRegistrationForm($sponsorCode)
{
    return view('registrationForm', ['sponsorCode' => $sponsorCode]);
}

}