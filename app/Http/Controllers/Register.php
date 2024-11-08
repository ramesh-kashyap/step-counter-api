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
                'password' => 'required|confirmed|numeric|digits:4',
                'sponsor' => 'required|exists:users,username',
                // 'code' => 'required',              
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
          
            // $code = $request->code;
            // if (PasswordReset::where('token', $code)->where('email', $request->email)->count() != 1) {
            //     // $notify[] = ['error', 'Invalid token'];
            //     // return Redirect::back()->withNotify($notify);
            //     return response()->json([
            //         'success' => false,
            //         'errors' => 'Invalid token' // Returns all error messages
            //     ], 422);
            // }

            
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
    
    // In RegistrationController.php
public function showRegistrationForm($sponsorCode)
{
    return view('registrationForm', ['sponsorCode' => $sponsorCode]);
}

}