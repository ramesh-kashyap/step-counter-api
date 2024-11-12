<?php

namespace App\Http\Controllers\UserPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BuyFund;
use App\Models\Income;
use App\Models\Withdraw;
use App\Models\Investment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Hexters\CoinPayment\CoinPayment;
use App\Models\CoinpaymentTransaction;
use Illuminate\Support\Facades\Http;

use Log;
use Redirect;
class AddFund extends Controller
{

    public function index(Request $request)
  {

  $user=Auth::user();
   $userid=$user->id;
  

    $investments = Investment::select('amount as comm','created_at','status')->where('user_id',$userid)->where('status','Active')->take(2)->get()->map(function ($item) {
        $item->remarks = 'Deposits'; // Add your custom remark value here
        return $item;
    })->toArray();
    // $incomes =    Income::select('comm','created_at','remarks')->where('user_id',$user->id)->orderBy('id','DESC')->limit(7)->get()->map(function ($item) {
    //     $item->status = 'Received'; // Add your custom remark value here
    //     return $item;
    // })->toArray();
    $withdraws = Withdraw::select('amount as comm','created_at','status')->where('user_id',$user->id)->where('status','!=','Failed')->take(3)->get()->map(function ($item) {
        $item->remarks = 'Withdrawals'; // Add your custom remark value here
        return $item;
    })->toArray();

    // Combine records into a single array
    $combinedRecords = array_merge($investments,$withdraws );
    // echo "<pre>";
    // print_r($combinedRecords);
    
    //  dd($combinedRecords);
    //  $notes = Income::where('user_id',$user->id)->orderBy('id', 'DESC')->limit(10)->get();
  
//   $totalPackage = Investment::where('user_id',$user->id)->where('status','Active')->sum("amount");
//   $todaysRoi = Income::where('user_id',$user->id)->where('remarks','Trading Bonus')->where('ttime',Date("Y-m-d"))->sum("comm");
//   $total = $totalPackage;
//   $portion = $todaysRoi;
//   if($totalPackage>0)
//     {
//           $percentage = ($portion / $total) * 100; // 20 
//     }
//     else
//     {
//     $percentage=0;    
//     }
      


//   $this->data['percentage'] = $percentage;
//   $this->data['todaysRoi'] = $todaysRoi;
  $this->data['level_income'] = $combinedRecords;
//   $this->data['page'] = 'user.fund.wallet';
//   return $this->dashboard_layout();
  return response()->json([
    'success' => true,
    'data'=> $this->data,
    'message' => 'Show Sucessfully' // Returns all error messages
], 200);

  }

    public function confirmDeposit(Request $request) 
{
try{
 $validation =  Validator::make($request->all(), [
    'Sum' => 'required|numeric|min:10',
    'PSys' => 'required',
 ]);


//  dd($request->all());
if($validation->fails()) {
    Log::info($validation->getMessageBag()->first());

    // return redirect()->route('user.invest')->withErrors($validation->getMessageBag()->first())->withInput();
    return response()->json([
        'success' => false,
        'errors' => $validation->errors()->first() // Returns all error messages
    ], 422);
}




$user=$request->user();
// dd($user);

$min_amount = $request->minimum_deposit;
$max_amount = $request->maximum_deposit;
$plan = $request->plan;
$paymentMode = $request->PSys;
Log::info($paymentMode);
$amount = $request->Sum;



//  $invest_check=BuyFund::where('user_id',$user->id)->where('status','Pending')->first();

// if ($invest_check) 
// {
//   return  redirect()->route('user.DepositHistory')->withErrors(array('your deposit already pending'));
// }


$amountTotal= $request->Sum;


if($paymentMode=="USDTBEP20")
{
  $paymentMode= "USDT_BSC"; 
}
else
{
  $paymentMode= "USDT_TRX";    
}


   $invoice = substr(str_shuffle("0123456789"), 0, 7);
   $apiURL = 'https://plisio.net/api/v1/invoices/new';
    $postInput = [
    'source_currency' => 'USD',
    'source_amount' => $amountTotal,
    'order_number' => $invoice,
    'currency' => $paymentMode,
    // 'email' => $user->email,
    'order_name' =>$user->username,
   
    'callback_url' => 'https://syntheticventure.com/dynamicupicallback?json=true',
    'api_key' => '6Wmf87DHpYmEKz6zDDH8UrzMXACo7nweTe5C8MVkUwYh6Y4S6-yY8wo8hfKjR-K0',
    ];

    $headers = [
        'Content-Type' => 'application/json'
    ];

    $response = Http::withHeaders($headers)->get($apiURL, $postInput);

    $statusCode = $response->status();
    $resultAarray = json_decode($response->getBody(), true);
       date_default_timezone_set("Asia/Kolkata");   //India time (GMT+5:30)
//   if($paymentMode=="USDT_BSC")
//   {
//       dd($resultAarray);
//   }

if($resultAarray['status']=="success")
{

   $data = [
        'orderId' => $invoice,
        'txn_no' =>$resultAarray['data']['txn_id'],
        'user_id' => $user->id,
     
        'user_id_fk' => $user->username,
        'amount' => $amountTotal,
        'type' =>$paymentMode,
        'status' => 'Pending',
        'bdate' => Date("Y-m-d"),
        'created_at' => date("Y-m-d H:i:s"),
    ];
    $payment =  BuyFund::insert($data);
            
        

$this->data['walletAddress'] =$resultAarray['data']['wallet_hash'];
$this->data['paymentMode'] =$paymentMode;
$this->data['transaction_id'] =$resultAarray['data']['txn_id'];
$this->data['qr_code'] =$resultAarray['data']['qr_code'];
$this->data['orderId'] =$invoice;
$this->data['amount'] =$amount;
$this->data['invoice_total_sum'] =$resultAarray['data']['invoice_total_sum'];
// $this->data['page'] = 'user.fund.confirmDeposit';

return response()->json([
    'success' => true,
    'data' => $data,
    'message' => 'Submit Sucessfully' // Returns all error messages
], 200);
// return $this->dashboard_layout();  

}
else
{
// return Redirect::back()->withErrors(array('try again'));
return response()->json([
    'success' => false,
    'errors' => 'try again' // Returns all error messages
], 422);
}

}
catch(\Exception $e){
Log::info('error here');
Log::info($e->getMessage());
print_r($e->getMessage());
die("hi");
return response()->json([
    'success' => false,

    'error' => $e->getMessage(),
    'input' => $request->all() // Optionally include the input data
], 400);
  }

}
}
