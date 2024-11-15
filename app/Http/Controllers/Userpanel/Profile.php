<?php

namespace App\Http\Controllers\UserPanel;

use App\Http\Controllers\Controller;
use App\Mail\Sendmail;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Bank;
use App\Models\UserLogin;
use App\Models\Investment;
use App\Models\Income;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\UserStep;

use Auth;
use Log;
use Redirect;
use Hash;
use Validator;
use Carbon\Carbon;

class Profile extends Controller
{

    public function index()
    {
    $user=Auth::user();
    $profile_data = User::where('id',$user->id)->orderBy('id','desc')->first();
    $this->data['login_logs'] =UserLogin::where('user_id',$user->id)->orderBy('id','DESC')->limit(10)->get();
    $this->data['pendingDeposit'] =Investment::where('user_id',$user->id)->where('status','Pending')->orderBy('id','DESC')->sum('amount');
    $ids=$this->my_level_team($user->id);
    $notes = User::where(function($query) use($ids)
    {
      if(!empty($ids)){
        foreach ($ids as $key => $value) {
        //   $f = explode(",", $value);
        //   print_r($f)."<br>";
          $query->orWhere('id', $value);
        }
      }else{$query->where('id',null);}
    })->orderBy('id', 'DESC')->get();

    $this->data['totalTeam'] =$notes->count();

    $this->data['profile_data'] =$profile_data;
    
    $this->data['page'] = 'user.profile.profile-setting';
    return $this->dashboard_layout();

    }
    public function user_info()
    {
        try {
    $user=Auth::user();
    
    $totalSum = $user->totalInvestmentSum(); //amount:55
    $totalWithdrawalSumToday=$user->totalWithdrawalSumToday();//amount:12
    $totalWithdrawalSum=$user->totalWithdrawalSum();             //amount:12
    $totalInvestmentSumToday=$user->totalInvestmentSumToday();  //amount:25
    $today = Carbon::today()->format('Y-m-d');
    $totalTeamincome=Income::Where('user_id',$user->id)->where('remarks','Team Income')->sum('comm');
    $todayTeamincome=Income::Where('user_id',$user->id)->where('remarks','Team Income')->whereDate('ttime',$today)->sum('comm');
    $totalsteps=UserStep::Where('user_id',$user->id)->sum('step');
    $todaysteps=UserStep::Where('user_id',$user->id)->whereDate('today',$today)->sum('step');
    
    $available_balance = ($user->totalInvestmentSum() + $user->users_incomes()) - ($user->totalWithdrawalSum());
    $wallets =[
       
        'trcAddress' => $user->usdtTrc20,  // Replace 'trc_column' with your actual column name for TRC data
        'bepAddress' => $user->usdtBep20,
         // Replace 'bep_column' with your actual column name for BEP data
    ];
    $data=[
        'totalTeamIncome' => $totalTeamincome,
        'todayTeamIncome' => $todayTeamincome,
        'totalSteps' => $totalsteps,
        'todaySteps' => $todaysteps,
        'totalInvestSum' =>  $totalSum,
        'investmentSumToday' => $totalInvestmentSumToday,
        'totalWithdrawalSum' =>  $totalWithdrawalSum,
        'totalWithdrawalSumToday' => $totalWithdrawalSumToday,
        'userName' => $user->username,
        'walletAddress'=>$wallets,
        'phone' => $user->phone,
        'email' => $user->email,
        'name' => $user->name,
        'availableBalance'=>$available_balance,

    ];
    return response()->json([
        'success' => true,
        'data'=>$data,
        'message' => 'Data Submited' // Returns all error messages
    ], 200);
    
} catch (\Exception $e) {
    Log::error('Error fetching user info', [
        'error_message' => $e->getMessage(),
        'user_id' => auth()->id(),
        'stack_trace' => $e->getTraceAsString(),
    ]);

    return response()->json([
        'success' => false,
        'error' => $e->getMessage(),
    ], 400);
}


    }


    public function income_report()
    {
    $user=Auth::user();
   

    $incomeReport=Income::where('user_id',$user->id)->select('created_at','amt','comm','remarks')->orderBy('ttime', 'desc')->get();
    
    return response()->json([
        'success' => true,
        'incomeReport' => $incomeReport,
        
        
        'message' => 'Data Fetch Succesfully' // Returns all error messages
    ], 200);
    }


    function SendSMS($name,$number,$userid,$password,$tpassword)
    {

    //   $message = "Dear ".$name." You have Registered Successfully. Your User ID is ".$userid." Password is ".$password." and Transaction password is ".$tpassword." Thank you for join us MANEUVER";
    $message = "Dear Customer, {#var#} is your OTP for reset password. This is valid for 5 minutes. MHLDAY";
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
    
    public function my_level_team($userid,$level=3){
        $arrin=array($userid);
        $ret=array();
  
        $i=1;
        while(!empty($arrin)){
            $alldown=User::select('id')->whereIn('sponsor',$arrin)->get()->toArray();
            if(!empty($alldown)){
                $arrin = array_column($alldown,'id');
                $ret[$i]=$arrin;
                $i++;
  
                if ($i>$level) {
                 break;
                }
  
  
            }else{
                $arrin = array();
            }
        }
  
        $final = array();
        if(!empty($ret)){
            array_walk_recursive($ret, function($item, $key) use (&$final){
                $final[] = $item;
            });
        }
  
  
        return $final;
  
    }

    

    public function wallets()
    {
    $user=Auth::user();
    $profile_data = User::where('id',$user->id)->orderBy('id','desc')->first();
    $this->data['login_logs'] =UserLogin::where('user_id',$user->id)->orderBy('id','DESC')->limit(10)->get();
    $this->data['profile_data'] =$profile_data;
    $this->data['page'] = 'user.profile.wallets';
    return $this->dashboard_layout();

    }
    

    public function change_password()
    {
    $this->data['page'] = 'user.profile.ChangePass';
    return $this->dashboard_layout();

    }


    public function ChangeMail()
    {
    $this->data['page'] = 'user.profile.ChangeMail';
    return $this->dashboard_layout();

    }


    public function bindMail()
    {
    $this->data['page'] = 'user.profile.bindMail';
    return $this->dashboard_layout();

    }
    

    public function change_trx_password()
    {
    $this->data['page'] = 'user.profile.ChangeSecurityPass';
    return $this->dashboard_layout();

    }
    
   public function codeVerify()
    {
    $this->data['page'] = 'user.profile.codeVerify';
  
     return $this->dashboard_layout();

    }
    
  public function codeVerifyPassword()
    {
    $this->data['page'] = 'user.profile.codeVerifyPassword';
  
     return $this->dashboard_layout();

    }
    

 
    public function showinfo()
    {
    $this->data['page'] = 'user.profile.info';
  
     return $this->dashboard_layout();

    }

    public function terms()
    {
    $this->data['page'] = 'user.profile.terms';
  
     return $this->dashboard_layout();

    }


public function BankDetail()
    {
    $user=Auth::user();
    $bank = Bank::where('user_id',$user->id)->first();
    $this->data['bank_value'] = $bank;
    $this->data['page'] = 'user.profile.BankDetail';
    return $this->dashboard_layout();

    }
    
    public function share()
    {
    $user=Auth::user();
  
    $sponsorCode = 'YOUR_SPONSOR_CODE';  // Set your sponsor code
    $url = url("/register?ref=$user->username");  // Generate the URL    
    $this->data['qrImage'] = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=$url&format=png";
    $this->data['page'] = 'user.profile.reffrel';
    return $this->dashboard_layout();

    }

    public function profile_update(Request $request)
    {
        try{
            $validation =  Validator::make($request->all(), [
                // 'email' => 'required|string',
                'name' => 'required|string',
                'country' => 'required|string',
                // 'city' => 'required',
                // 'zipCode' => 'required',
                // 'usdtAddress' => 'required',
                // 'lastname' => 'required',
                'phone' => 'required|numeric'

            ]);
            if($validation->fails()) {
                Log::info($validation->getMessageBag()->first());

                return Redirect::back()->withErrors($validation->getMessageBag()->first())->withInput();
            }
            $user=Auth::user();
            $id=Auth::user()->id;

            //check if email exist
          $post_array  = $request->all();

          $update_data['name']=$post_array['name'];
          $update_data['phone']=$post_array['phone'];
          $update_data['telegram']=$post_array['telegram'];
          $update_data['country']=$post_array['country'];
        //   $update_data['zipCode']=$post_array['zipCode'];
        //   $update_data['city']=$post_array['city'];
        //   $update_data['lastname']=$post_array['lastname'];
        //   if(empty($user->usdtTrc20) )
        //   {
        //     $update_data['usdtTrc20']=$post_array['usdtTrc20'];    
           
        //   }
          if(empty($user->usdtBep20) )
          {  
            $update_data['usdtBep20']=$post_array['usdtBep20'];    
          }
        
          
          $user =  User::where('id',$id)->update($update_data);


        $notify[] = ['success', 'Profile Updated successfully'];
        return redirect()->back()->withNotify($notify);

          }
           catch(\Exception $e){
            Log::info('error here');
            Log::info($e->getMessage());
            print_r($e->getMessage());
          dd($e->getMessage());
            return back()->withErrors('error', $e->getMessage())->withInput();
        }
    }


    public function wallet_update(Request $request)
    {
        try{
     
            $user=Auth::user();
            $id=Auth::user()->id;

            //check if email exist
        
           $code = verificationCode(6);

            PasswordReset::where('email', $user->email)->delete();

            $password = new PasswordReset();
            $password->email = $user->email;
            $password->token = $code;
            $password->created_at = \Carbon\Carbon::now();
            $password->save();

               sendEmail($user->email, 'Your One-Time Password', [
                'name' => $user->name,
                'code' => $code,
                'purpose' => 'Receiver Payment wallet',
                'viewpage' => 'one_time_password',

             ]);
             $userID = $user->id;
            session()->put('usdtTrc20',$request->usdtTrc20);
            session()->put('usdtBep20',$request->usdtBep20);
            $notify[] = ['success', 'Wallet Change email sent successfully'];
            return redirect()->route('user.codeVerify')->withNotify($notify);
            

          }
           catch(\Exception $e){
            Log::info('error here');
            Log::info($e->getMessage());
            print_r($e->getMessage());
       
            return back()->withErrors('error', $e->getMessage())->withInput();
        }
    }


    
    public function bindemail_action(Request $request)
    {
        try{
     
            $user=Auth::user();
            $id=Auth::user()->id;


            $request->validate(['code' => 'required','emailId'=>'required']);
            $code = $request->code;
     
        if (PasswordReset::where('token', $code)->where('email', $request->emailId)->count() != 1) {
            $notify[] = ['error', 'Invalid token'];
            return redirect()->route('user.bindMail')->withNotify($notify);
        }
        
     
         $emailId = $request->emailId;
         
         $emailExist= User::where('email',$emailId)->where('active_status','!=','Block')->first();
            
        if ($emailExist)
         {
            return Redirect::back()->withErrors('email Id already used');
        } 
           $user->email=$emailId;

           $user->save();
           $notify[] = ['success', 'Your Bind Email Successfully.'];
           
        return redirect()->route('user.bindMail')->withNotify($notify);
            

          }
           catch(\Exception $e){
            Log::info('error here');
            Log::info($e->getMessage());
            print_r($e->getMessage());
       
            return back()->withErrors('error', $e->getMessage())->withInput();
        }
    }


      
    public function changeEmailAction(Request $request)
    {
        try{
     
            $user=Auth::user();
            $id=Auth::user()->id;

            $validation =  Validator::make($request->all(), [
                'first_code' => 'required',
                'second_code' => 'required|',
                'newEmail' => 'required|email|unique:users,email,' . $request->newEmail

            ]);
            if($validation->fails()) {
                Log::info($validation->getMessageBag()->first());

                return Redirect::back()->withErrors($validation->getMessageBag()->first())->withInput();
            }

            $code = $request->first_code;
        
            if (PasswordReset::where('token', $code)->where('email', $user->email)->count() != 1) {
                $notify[] = ['error', 'Invalid token'];
                return redirect()->route('user.ChangeMail')->withNotify($notify);
            }
            
            $code = $request->second_code;
            if (PasswordReset::where('token', $code)->where('email', $request->newEmail)->count() != 1) {
                $notify[] = ['error', 'Invalid New Email token'];
                return redirect()->route('user.ChangeMail')->withNotify($notify);
            }
            date_default_timezone_set('Asia/Kolkata');
            $today = date("Y-m-d H:i:s");
           $emailId = $request->newEmail;
           $user->email=$emailId;
           $user->detail_changed_date=$today;

           $user->save();
           $notify[] = ['success', 'Your Change Email Successfully.'];
           
        return redirect()->route('user.ChangeMail')->withNotify($notify);
            

          }
           catch(\Exception $e){
            Log::info('error here');
            Log::info($e->getMessage());
            print_r($e->getMessage());
       
            return back()->withErrors('error', $e->getMessage())->withInput();
        }
    }


      public function wallet_change(Request $request)
    {
        try{
     
            $user=Auth::user();
            $id=Auth::user()->id;


            $request->validate(['code' => 'required']);
            $code = $request->code;
     
        if (PasswordReset::where('token', $code)->where('email', $user->email)->count() != 1) {
            $notify[] = ['error', 'Invalid token'];
            return redirect()->route('user.codeVerify')->withNotify($notify);
        }
        
     
         $usdtTrc20 = session()->get('usdtTrc20');
         $usdtBep20 = session()->get('usdtBep20');
            
           $user->usdtTrc20=$usdtTrc20;
           $user->usdtBep20=$usdtBep20;
           $user->save();
           $notify[] = ['success', 'Your Wallet change Successfully.'];
           
        return redirect()->route('user.wallets')->withNotify($notify);
            

          }
           catch(\Exception $e){
            Log::info('error here');
            Log::info($e->getMessage());
            print_r($e->getMessage());
       
            return back()->withErrors('error', $e->getMessage())->withInput();
        }
    }

    public function old_password(){
        $this->data['page'] = 'user.profile.old_password';
    return $this->dashboard_layout();
    }

    // send code for validation 
    public function sendCode(Request $request)
    {

        $user=Auth::user();
        $code = verificationCode(6);
      
        $emailId = $request->emailId;
        if ($emailId!="") 
        {
          $emailId = $emailId;
        }
        else
        {
            $emailId = $user->email;
        }
       
        PasswordReset::where('email', $emailId)->delete();

        $password = new PasswordReset();
        $password->email = $emailId;
        $password->token = $code;
        $password->created_at = \Carbon\Carbon::now();
        $password->save();

           sendEmail($emailId, 'Your One-Time Password', [
            'name' => $user->name,
            'code' => $code,
            'purpose' => 'Change Password',
            'viewpage' => 'one_time_password',

         ]);

       return true;
    }
   

    public function change_number(Request $request)
    {

        try {
            $data = $request->all();
            $rules = [
                'first_code' => 'required|numeric|digits:6', // Ensures the first code is required, numeric, and exactly 6 digits
                'second_code' => 'required|numeric|digits:6', // Ensures the second code is required, numeric, and exactly 6 digits
                'newphone' => 'required|unique:users,phone,' , // Ensures phone is required, unique, and valid
            ];
            $msg = [
                'first_code.required' => 'The first code is required.',
                'first_code.numeric' => 'The first code must be a numeric value.',
                'first_code.digits' => 'The first code must be exactly 6 digits.',
                
                'second_code.required' => 'The second code is required.',
                'second_code.numeric' => 'The second code must be a numeric value.',
                'second_code.digits' => 'The second code must be exactly 6 digits.',
                
                'newphone.required' => 'Phone number is required.',
                'newphone.unique' => 'This phone number is already registered.',
                'newphone.regex' => 'Please enter a valid phone number.',
            ];

            $validator = Validator::make($data, $rules, $msg);
            if ($validator->fails()){
                // return Redirect::back()->withErrors($validator->getMessageBag()->first());
                return response()->json([
                    'error' => $validator->getMessageBag()->first()
                ], 400);
            }
            $user = Auth::user();
           
            //   $code = $request->code;
            // if (PasswordReset::where('token', $code)->where('email', $user->email)->count() != 1) {
            //     $notify[] = ['error', 'Invalid token'];
            //     return redirect()->route('user.ChangePass')->withNotify($notify);
            // }
            
              date_default_timezone_set('Asia/Kolkata');
            $today = date("Y-m-d H:i:s");         
        //    $code = verificationCode(6);
           $update_number=User::where('id',  $user->id)->update(array(
           'phone'=>$request->newphone,
            'detail_changed_date' =>$today,
            'updated_at' => new \DateTime
           ));
          if($update_number){  return response()->json([
            'success' => true,
            
            'message' => 'Phone Number  Changed successfully' // Returns all error messages
        ], 200);}
        else{
            return response()->json([
                'success' => false,
                
                'message' => 'Phone Number Change Failed' // Returns all error messages
            ], 200);
        }
           
          

        } catch (\Exception $e) {
           
            return response()->json([
                'success' => false,
        
                'error' => $e->getMessage(),
                'input' => $request->all() 
            ], 400);
        }

    }

    // public function change_password_post(Request $request)
    // {

    //     try {
    //         $data = $request->all();
    //         $rules = [
    //             'password' => 'required|confirmed|digits:4'
    //         ];
    //         $msg = [
    //             'password.required' => 'Password is required',
    //             'password.confirmed' => 'Password must match',
    //             'password.digits' => 'Password must be exactly 4 digits'
    //         ];

    //         $validator = Validator::make($data, $rules, $msg);
    //         if ($validator->fails()){
    //             // return Redirect::back()->withErrors($validator->getMessageBag()->first());
    //             return response()->json([
    //                 'error' => $validator->getMessageBag()->first()
    //             ], 400);
    //         }
    //         $user = Auth::user();
           
    //         //   $code = $request->code;
    //         // if (PasswordReset::where('token', $code)->where('email', $user->email)->count() != 1) {
    //         //     $notify[] = ['error', 'Invalid token'];
    //         //     return redirect()->route('user.ChangePass')->withNotify($notify);
    //         // }
            
    //           date_default_timezone_set('Asia/Kolkata');
    //         $today = date("Y-m-d H:i:s");         
    //     //    $code = verificationCode(6);
    //        User::where('id',  $user->id)->update(array(
    //         'password' => \Hash::make($data['password']),
    //         'PSR' => $data['password'],
    //         'detail_changed_date' =>$today,
    //         'updated_at' => new \DateTime
    //        ));

    //         // $notify[] = ['success', 'Password Changed successfully'];
    //         // return redirect()->route('user.ChangePass')->withNotify($notify);
    //         return response()->json([
    //             'success' => true,
                
    //             'message' => 'Password Changed successfully' // Returns all error messages
    //         ], 200);

    //     } catch (\Exception $e) {
    //         // return Redirect::back()->witherrors($e->getMessage())->withInput();
    //         return response()->json([
    //             'success' => false,
        
    //             'error' => $e->getMessage(),
    //             'input' => $request->all() // Optionally include the input data
    //         ], 400);
    //     }

    // }



    public function infochange(Request $request)
    {
        
        $user = Auth::user(); // Get the authenticated user
        $user->nickname = $request->nickname; // Update nickname
        $user->gender = $request->gender; // Update gender

        $user->save(); // Save the user

        $notify[] = ['success', 'Profile Changed successfully'];
        return redirect()->route('user.showinfo')->withNotify($notify);
    
    }

    public function change_password_submit(Request $request)
    {

        try {
           
            $request->validate(['code' => 'required']);
            $code = $request->code;
             $user = Auth::user();
            if (PasswordReset::where('token', $code)->where('email', $user->email)->count() != 1) {
                $notify[] = ['error', 'Invalid token'];
                return redirect()->route('user.codeVerifyPassword')->withNotify($notify);
            }
            
                    
                
        
              $password = session()->get('NewPassword');
             User::where('id', $user->id)->update(array(
                'password' => \Hash::make($password),
                'PSR' =>$password,
                'updated_at' => new \DateTime
            ));

            $notify[] = ['success', 'password updated successfully'];
            return redirect()->route('user.ChangePass')->withNotify($notify);

        } catch (\Exception $e) {
            return Redirect::back()->witherrors($e->getMessage())->withInput();
        }

    }


    public function change_trxpassword_post(Request $request)
    {

        try {
            $data = $request->all();
            $rules = array('password' => 'required|confirmed');
            $msg = [
                'password.required'         => 'Password is required' ,
                'password.confirmed'        => 'Password must match'    ,
            ];

            $validator = Validator::make($data, $rules, $msg);
            if ($validator->fails())
                return Redirect::back()->withErrors($validator->getMessageBag()->first());

            $user = Auth::user();

            
            $code = $request->code;
            if (PasswordReset::where('token', $code)->where('email', $user->email)->count() != 1) {
                $notify[] = ['error', 'Invalid token'];
                return redirect()->route('user.change-trx-password')->withNotify($notify);
            }
            
                date_default_timezone_set('Asia/Kolkata');
                $today = date("Y-m-d H:i:s");  
                User::where('id', $user->id)->update(array(
                'tpassword' => \Hash::make($data['password']),
                 'TPSR' => $data['password'],
                 'detail_changed_date' =>$today,
                'updated_at' => new \DateTime
            ));

           // return Redirect::Back()->with('messages', 'Transaction password updated successfully');

            $notify[] = ['success', 'Transaction password updated successfully'];
            return redirect()->back()->withNotify($notify);

        }
         catch (\Exception $e) {
            return Redirect::back()->witherrors($e->getMessage())->withInput();
        }

    }



    public function bank_profile_update(Request $request)

    {
        try{
            $validation =  Validator::make($request->all(), [
                'account_holder' => 'required',
                'bank_name' => 'required',
                'branch_name' => 'required',
                'ifsc_code' => 'required',
                'account_number' => 'required',

            ]);
            if($validation->fails()) {
                Log::info($validation->getMessageBag()->first());
                return Redirect::back()->withErrors($validation->getMessageBag()->first())->withInput();
            }

            $id=Auth::user()->id;

             $check_exist=Bank::where('user_id', $id)->first();

             $bank_array=array(

                 'user_id'=>$id,
                 'bank_name'=>$request->bank_name,
                 'account_holder'=>$request->account_holder,
                 'branch_name'=>$request->branch_name,
                //  'pancard_no'=>$request->pancard_no,
                 'account_no'=>$request->account_number,
                 'ifsc_code'=>$request->ifsc_code,
             );

             if (!$check_exist)
             {

              $bank=Bank::create($bank_array);
            }
            else
            {
                $bank= Bank::where('user_id', $id)->update($bank_array);
            }




        $notify[] = ['success', 'Bank Updated successfully'];
        return redirect()->back()->withNotify($notify);

          }
           catch(\Exception $e){
            Log::info('error here');
            Log::info($e->getMessage());
            print_r($e->getMessage());
            die("hi");
            return back()->withErrors('error', $e->getMessage())->withInput();

        }
    }
   




}
