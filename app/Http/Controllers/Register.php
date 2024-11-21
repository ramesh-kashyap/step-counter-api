<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\PasswordReset;
use Redirect;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Log;
use Hash;

class Register extends Controller
{

   
   



  public function register(Request $request)
    {
        try{
            $validation =  Validator::make($request->all(), [
                'email' => 'required|unique:users,email',
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
            if (PasswordReset::where('token', $code)->where('email', $request->email)->count() != 1) {
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
             
            // $data['name'] = $post_array['name'];
            // $data['phone'] = $post_array['phone'];
           
            
            $data['username'] = $username;
            $data['password'] =   Hash::make($post_array['password']);
            $data['tpassword'] =   Hash::make($tpassword);
            $data['TPSR'] =  $tpassword;
            $data['PSR'] =  $post_array['password'];
            $data['email'] = $post_array['email'];
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
           
           
        }

          
    }

    public function uploadImage(Request $request)
{
    // Validate the uploaded file
    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg,gif', // max 2MB
    ]);

    if ($request->file('image')) {
        // Store the image in the 'public' disk (public/images folder)
        $path = $request->file('image')->store('images', 'public');

        // Get the relative path for the stored image (without the base URL)
        $relativeUri = 'images/' . basename($path);  // Use the file name as relative path

        // Get the currently authenticated user
        $user = auth()->user();

        // If the user already has an image URI, delete the old image from storage
        if ($user->remember_token) {
            // Delete the previous image from storage
            Storage::disk('public')->delete($user->remember_token);  // Use the relative path to delete the old image
        }

        // Update the user's image URI in the database with the new relative path
        $user->remember_token = $relativeUri;
        $user->save();

        // Return the full URL to the image
        return response()->json([
            'uri' => asset('storage/' . $relativeUri), // Full URL to the image
        ]);
    }

    return response()->json(['error' => 'Image upload failed.'], 500);
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
            $email = $request->email; 
    
            // Delete any existing password reset entries for this number
            PasswordReset::where('email', $email)->delete();
    
            // Create a new password reset entry
            $passwordReset = new PasswordReset();
            $passwordReset->email = $email;
            $passwordReset->token = $code;
            $passwordReset->created_at = \Carbon\Carbon::now();
            $passwordReset->save();
    
            // Send the OTP via SMS
            $this->SendSMS($email, $code);
    
            // Return a JSON response indicating success
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully.',
                'data' => [
                    'email' => $email,
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

public function update_profile(Request $request)
{
    try {
        // Validate input fields
        $validation = Validator::make($request->all(), [
            'code' => 'required', // Only code is required
            'email' => 'nullable|email', // If email is provided, validate it
        ]);

        // Check if validation fails
        if ($validation->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validation->errors()->first() // Returns the first error message
            ], 422);
        }

        // Check if the provided code is valid
        $code = $request->code;
        if (PasswordReset::where('token', $code)->where('email', Auth::user()->email)->count() != 1) {
            return response()->json([
                'success' => false,
                'errors' => 'Invalid token'
            ], 422);
        }

        // Update user data
        $user = Auth::user(); // Get the authenticated user
        $user->usdtBep20 = $request->input('bep', $user->bep); // Update if provided, keep current value if not
        $user->usdtTrc20 = $request->input('trc', $user->trc);
        $user->name = $request->input('name', $user->name);
        if ($request->filled('email')) {
            $user->email = $request->input('email'); // Only update email if provided
        }
        
        $user->save(); // Save changes to the user

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully'
        ], 200);
    } catch (\Exception $e) {
        // Log and return error message
        Log::error('Profile update error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'input' => $request->all() // Optionally include the input data
        ], 400);
    }
}


}