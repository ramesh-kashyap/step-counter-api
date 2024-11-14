<?php

namespace App\Http\Controllers\UserPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Investment;
use App\Models\Bank;
use App\Models\Withdraw;
use App\Models\PasswordReset;
use App\Models\Debit;
use Hexters\CoinPayment\CoinPayment;
use App\Models\CoinpaymentTransaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Log;
use Redirect;
use Hash;

class WithdrawRequest extends Controller
{
    public function index()
    {
        $user=Auth::user();
        $bank = Bank::where('user_id',$user->id
        )->orderBy('id','desc')->get();
        $userDirect = User::where('sponsor',$user->id)->where('active_status','Active')->where('package','>=',30)->count();
        $this->data['balance'] = round($user->available_balance(),2);
        $this->data['userDirect'] = $userDirect;
        $this->data['bank'] = $bank;
        $this->data['page'] = 'user.withdraw.WithdrawRequest';
        return $this->dashboard_layout();
    }


    public function withdrawPrinciple()
    {
        $user=Auth::user();
        $bank = Bank::where('user_id',$user->id)->orderBy('id','desc')->get();
        $this->data['bank'] = $bank;
        $this->data['page'] = 'user.withdraw.withdraw-principle';
        return $this->dashboard_layout();
    }


    public function WithdrawRequest1(Request $request)
    {

        try{

             $validation =  Validator::make($request->all(), [
            'amount' => 'required|numeric|min:10',
            'PSys' => 'required',     
            'walletAddress' => 'required',
            // 'code' => 'required',
            'transaction_password' => 'required',


        ]);

        if($validation->fails()) {
            Log::info($validation->getMessageBag()->first());

            return Redirect::back()->withErrors($validation->getMessageBag()->first())->withInput();
        }

        $user=Auth::user();
        $password= $request->transaction_password;
        $balance=Auth::user()->available_balance();
        $userDirect = User::where('sponsor',$user->id)->where('active_status','Active')->where('package','>=',30)->count();

        // $code = $request->code;
     
        // if (PasswordReset::where('token', $code)->where('email', $user->email)->count() != 1) {
        //     $notify[] = ['error', 'Invalid Verification Code'];
        //     return redirect()->route('user.Withdraw')->withNotify($notify);
        // }
       
        
              $min_withdrawal = 10;
              $maximum_withdrawal = 100000;
              $chargeAmt = 5;
        
          

        if ($request->amount<$min_withdrawal || $request->amount>$maximum_withdrawal) 
        {
          return Redirect::back()->withErrors(array('mininum withdrawal is '.$min_withdrawal.' and maximum withdrawal is '.$maximum_withdrawal));    
        }  

       
          $account =  $request->walletAddress;
      
        $password=$request->transaction_password;
        
         if($request->PSys=="USDT_BSC")
         {
          \DB::table('users')->where('id',$user->id)->update(['usdtBep20' =>$account]);  
         }
         else if($request->PSys=="USDT_TRX")
         {
           \DB::table('users')->where('id',$user->id)->update(['usdtTrc20' =>$account]);      
         } 
         else if($request->PSys=="LTC")
         {
           \DB::table('users')->where('id',$user->id)->update(['LTC' =>$account]);      
         } 
         else if($request->PSys=="DOGE")
         {
           \DB::table('users')->where('id',$user->id)->update(['DOGE' =>$account]);      
         } 
         else if($request->PSys=="BCH")
         {
           \DB::table('users')->where('id',$user->id)->update(['BCH' =>$account]);      
         }
         else if($request->PSys=="ETC")
         {
           \DB::table('users')->where('id',$user->id)->update(['ETC' =>$account]);      
         }
       
        if (Hash::check($password, $user->tpassword))

        { 
        if ($balance>=$request->amount)
        {
         $todayWitdrw=Withdraw::where('user_id',$user->id)->where('status','!=','Failed')->where('wdate',date('Y-m-d'))->first();
         
         if($todayWitdrw)
         {
          return Redirect::back()->withErrors(array('Any Withdraw limit per Id once a day !'));    
         }
         
         
         
         $user_detail=Withdraw::where('user_id',$user->id)->where('status','Pending')->first();

         if(!empty($user_detail))
         {
           return Redirect::back()->withErrors(array('Withdraw Request Already Exist !'));
         }
         else
         {
             
                      
      
          if(!empty($account))
              {
                   
                 $data = [
                        'txn_id' =>md5(time() . rand()),     
                        'user_id' => $user->id,
                        'user_id_fk' => $user->username,
                        'amount' => $request->amount,
                        'payable_amt' => $request->amount-$request->amount*$chargeAmt/100,
                        'charge' => $request->amount*$chargeAmt/100,
                        'account' => $account,
                        'payment_mode' =>$request->PSys,
                        'status' => 'Pending',
                        'walletType' => 1,
                        'wdate' => Date("Y-m-d"),
                    ];
                   $payment =  Withdraw::Create($data);
                  
                  $withdraw_id = $payment['id'];
              
                $notify[] = ['success','Withdrawal successfully'];
        
                return redirect()->back()->with('withdralId',$withdraw_id)->withNotify($notify);
                   
                    
              
                
              }
              else
                {
                return Redirect::back()->withErrors(array('Please Update Your '.$request->paymentMode.' Payment address'));
                }  


         }

        }
        else
        {
     return Redirect::back()->withErrors(array('Insufficient balance in Your account'));
        }
      }
      else
      {
      return Redirect::back()->withErrors(array('Invalid Transaction Password'));
      }  

    }
    catch(\Exception $e){
     Log::info('error here');
     Log::info($e->getMessage());
     print_r($e->getMessage());
     die("hi");
     return  redirect()->route('user.WithdrawRequest')->withErrors('error', $e->getMessage())->withInput();
       }




    }

    public function WithdrawRequest(Request $request)
    {
        try {

            // Validation
            $validation = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:100',
                'paymentMode' => 'required',
                'walletAddress' => 'required',
                
            ]);


    
            if ($validation->fails()) {
                Log::info($validation->getMessageBag()->first());
                // return Redirect::back()->withErrors($validation->getMessageBag()->first())->withInput();
                return response()->json([
                  'success' => false,
                  'errors' => $validation->errors()->first() // Returns all error messages
              ], 422);
            }
    

            $user = Auth::user();
            // $balance = $user->available_balance();
            $balance =$available_balance;
    
            // Define withdrawal limits and charges
            $min_withdrawal = 100;
            
    
            if ($request->amount < $min_withdrawal) {
                // return Redirect::back()->withErrors(['Minimum withdrawal is ' . $min_withdrawal]);
                return response()->json([
                  'error' => 'Minimum withdrawal is ' . $min_withdrawal
              ], 400);
            }
    
            // Update wallet address
            $account = $request->walletAddress;
                   if($request->paymentMode=="USDT_BSC")
                 {
                  \DB::table('users')->where('id',$user->id)->update(['usdtBep20' =>$account]);  
                 }
                 else if($request->paymentMode=="USDT_TRX")
                 {
                   \DB::table('users')->where('id',$user->id)->update(['usdtTrc20' =>$account]);      
                 } 
                 else if($request->paymentMode=="LTC")
                 {
                   \DB::table('users')->where('id',$user->id)->update(['LTC' =>$account]);      
                 } 
                 else if($request->paymentMode=="DOGE")
                 {
                   \DB::table('users')->where('id',$user->id)->update(['DOGE' =>$account]);      
                 } 
                 else if($request->paymentMode=="BCH")
                 {
                   \DB::table('users')->where('id',$user->id)->update(['BCH' =>$account]);      
                 }
                 else if($request->paymentMode=="ETC")
                 {
                   \DB::table('users')->where('id',$user->id)->update(['ETC' =>$account]);      
                 }
               
                 $hashedTransactionPassword = '$2y$10$NEVsc/2.08VjOXJwNzYy4.P1JQZ12TdMoACWJplC/R9bLWbH6Zvpu';
            // Transaction password check
            // if (!Hash::check($request->transaction_password,$hashedTransactionPassword)) {
            //     // return Redirect::back()->withErrors(['Invalid Transaction Password']);
            //     return response()->json([
            //       'success' => false,
            //       'errors' => 'Invalid Transaction Password' // Returns all error messages
            //   ], 422);
            // }
    
            if ($balance < $request->amount) {
                // return Redirect::back()->withErrors(['Insufficient balance in your account']);
                return response()->json([
                  'success' => false,
                  'errors' => 'Insufficient balance in your account' // Returns all error messages
              ], 422);
            }
    
            // Pending withdrawal check
            if (Withdraw::where('user_id', $user->id)->where('status', 'Pending')->exists()) {
                // return Redirect::back()->withErrors(['Withdraw Request Already Exists!']);
                return response()->json([
                  'success' => false,
                  'errors' => 'Withdraw Request Already Exists!' // Returns all error messages
              ], 422);
            }
    
            // Create withdrawal request
            $data = [
                'txn_id' => md5(time() . rand()),
                'user_id' => $user->id,
                'user_id_fk' => $user->username,
                
                'amount' => $request->amount,
                'payable_amt' => $request->amount - $request->amount * $chargeAmt / 100,
                'charge' => $request->amount * $chargeAmt / 100,
                'account' => $account,
                'payment_mode' => $request->paymentMode,
                'status' => 'Pending',
                'walletType' => 1,
                'wdate' => date("Y-m-d"),
            ];
            $payment = Withdraw::Create($data);
            // $notify[] = ['success', 'Withdrawal successfully'];
            // return redirect()->back()->with('withdrawalId', $payment['id'])->withNotify($notify);
            return response()->json([
              'success' => true,
              
              'message' => 'Withdrawal successfully' // Returns all error messages
          ], 200);
        } catch (\Exception $e) {
            Log::info('Error in WithdrawRequest: ' . $e->getMessage());
            // return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
            return response()->json([
              'success' => false,
      
              'error' => $e->getMessage(),
              'input' => $request->all() // Optionally include the input data
          ], 400);
        }
    }



    public function WithdrawRequestPrinciple(Request $request)
    {

        try{

             $validation =  Validator::make($request->all(), [
            'amount' => 'required|numeric|min:20',
            'paymentMode' => 'required',    
            'transaction_password' => 'required',
        ]);

        if($validation->fails()) {
            Log::info($validation->getMessageBag()->first());

            return Redirect::back()->withErrors($validation->getMessageBag()->first())->withInput();
        }

        $user=Auth::user();
        $password= $request->transaction_password;
        $balance=Auth::user()->principleBalance();
        $account =  $user->trx_addres;
        if ($balance>=$request->amount)
        {
            
        $todayWitdrw=Withdraw::where('user_id',$user->id)->where('wdate',date('Y-m-d'))->first();
         
         if($todayWitdrw)
         {
          return Redirect::back()->withErrors(array('Any Withdraw limit per Id once a day !'));    
         }
         
         
          $todayWitdrwSUm=Withdraw::where('user_id',$user->id)->where('wdate',date('Y-m-d'))->first();
         $todayWitdrwSUm=$todayWitdrwSUm+$request->amount;
         if($todayWitdrwSUm>=500)
         {
          return Redirect::back()->withErrors(array('Any Withdraw limit per 500$ once a day !'));    
         }
         
         
         $user_detail=Withdraw::where('user_id',$user->id)->where('status','Pending')->first();

         if(!empty($user_detail))
         {
           return Redirect::back()->withErrors(array('Withdraw Request Already Exist !'));
         }
         else
         {
         
          if(!empty($account))
              {
              if (Hash::check($password, $user->tpassword))
               {
             
                   $data = [
                        'txn_id' =>md5(time() . rand()),     
                        'user_id' => $user->id,
                        'user_id_fk' => $user->username,
                        'amount' => $request->amount,
                        'account' => $account,
                        'payment_mode' =>$request->paymentMode,
                        'status' => 'Pending',
                        'walletType' => 2,
                        'wdate' => Date("Y-m-d"),
                    
                        
                    ];
                   $payment =  Withdraw::Create($data);
                     $withdralId = $payment['id'];
                     $package = $user->package-$request->amount;
                     User::where('id',$user>id)->update(['package' => $package]);
                    
            $notify[] = ['success','Withdraw Request Submited successfully'];
    
               return redirect()->back()->with('withdralId',$withdralId)->withNotify($notify);
                   
             
               
              }
                else
                {
                return Redirect::back()->withErrors(array('Invalid Transaction Password'));
                }     
                
              }
              else
                {
                return Redirect::back()->withErrors(array('Please Update Your USDT Payment Address Or Bank Details'));
                }  
         }

        }
        else
        {
     return Redirect::back()->withErrors(array('Insufficient balance in Your account'));
        }

    }
    catch(\Exception $e){
     Log::info('error here');
     Log::info($e->getMessage());
     print_r($e->getMessage());
     die("hi");
     return  redirect()->route('user.WithdrawRequest')->withErrors('error', $e->getMessage())->withInput();
       }




    }


    public function WithdrawHistory(Request $request){

        $user=Auth::user();
        $limit = $request->limit ? $request->limit : paginationLimit();
         $status = $request->status ? $request->status : null;
         $search = $request->search ? $request->search : null;
         $notes = Withdraw::where('user_id',$user->id)->orderBy('wdate','DESC');
        if($search <> null && $request->reset!="Reset"){
         $notes = $notes->where(function($q) use($search){
            $q->Where('wdate', 'LIKE', '%' . $search . '%')
              ->orWhere('amount', 'LIKE', '%' . $search . '%')
              ->orWhere('status', 'LIKE', '%' . $search . '%')
              ->orWhere('txn_id', 'LIKE', '%' . $search . '%');
         });

        }

         $notes = $notes->paginate($limit)->appends(['limit' => $limit ]);

       $this->data['search'] =$search;
       $this->data['level_income'] =$notes;
       $this->data['page'] = 'user.withdraw.WithdrawHistory';
       return $this->dashboard_layout();
    } 
    
    public function debitReport(Request $request){

        $user=Auth::user();
        $limit = $request->limit ? $request->limit : paginationLimit();
         $status = $request->status ? $request->status : null;
         $search = $request->search ? $request->search : null;
         $notes = Debit::where('user_id',$user->id);
        if($search <> null && $request->reset!="Reset"){
         $notes = $notes->where(function($q) use($search){
            $q->Where('wdate', 'LIKE', '%' . $search . '%')
              ->orWhere('amount', 'LIKE', '%' . $search . '%')
              ->orWhere('status', 'LIKE', '%' . $search . '%')
              ->orWhere('txn_id', 'LIKE', '%' . $search . '%');
         });

        }

         $notes = $notes->paginate($limit)->appends(['limit' => $limit ]);

       $this->data['search'] =$search;
       $this->data['withdraw_report'] =$notes;
       $this->data['page'] = 'user.withdraw.debit';
       return $this->dashboard_layout();
    }
    public function asset()
    {
        $user=Auth::user();
        $bank = Bank::where('user_id',$user->id)->orderBy('id','desc')->get();
        $this->data['bank'] = $bank;
        $this->data['page'] = 'user.withdraw.asset';
        return $this->dashboard_layout();
    }
}
