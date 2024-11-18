<?php

namespace App\Http\Controllers\UserPanel;

use App\Http\Controllers\Controller;
use App\Models\UserStep;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Redirect;
use Helper;
use DB;

use Log;
class stepCount extends Controller
{
 
    public function step_count(Request $request)
    {
        $user=Auth::user();
         $validation =  Validator::make($request->all(), [
                'step' => 'required',

            ]);
            if($validation->fails()) {

                Log::info($validation->getMessageBag()->first());
     
                // return Redirect::back()->withErrors($validation->getMessageBag()->first())->withInput();
                return response()->json([
                    'success' => false,
                    'errors' => $validation->errors()->first() // Returns all error messages
                ], 422);
            }
               
            $check_date=Carbon::today()->format('Y-m-d');
                               
            $match_date = UserStep::where('user_id', $user->id)
            ->latest('today') // Order by 'today' column in descending order
            ->value('today');   
            $match_date_formatted = Carbon::parse($match_date)->format('Y-m-d'); // Converts to "12-11-2024"

            $today_steps=DB::table('user_steps')->where('user_id',$user->id)->where('today',$check_date)->first();


                //    dd(  $check_date===$match_date_formatted);
                 if($today_steps){
                 
                    $data = [
                     
                        'step' => $request->step,
                        
                    ];
                    $update_step=DB::table('user_steps')->where('user_id',$user->id)->where('today',$check_date)->update($data);
                 
                    return response()->json([
                        'success' => true,
                        
                        'message' => 'Step Updated' // Returns all error messages
                    ], 200);
               
                 }else{
                    $data = [
                        'user_id' => $user->id, // Replace with the actual user ID if needed
                        'step' => $request->step,
                        'today' => Carbon::today()->format('Y-m-d')  // Store date in 'Y-m-d' format
                    ];
            
                   
                    DB::table('user_steps')->insert($data);
                   return response()->json([
                'success' => true,
                
                'message' => 'data save Successfully.' // Returns all error messages
            ], 200);}
 }
       
 public function checkVip($totalSteps){
    // $user=auth::user();  
    // $todaySteps = UserStep::where('user_id', 1)
    // ->whereDate('today', Carbon::today())
    // ->pluck('step');
    // $today = Carbon::today();
   
    // $totalSteps = $todaySteps->sum(); // Calculate the total steps


    $investments = Investment::where('user_id', 1)->where('roiCandition', 0)->where('status', 'Active')->get();

    foreach ($investments as $investment) {

      $amount = $investment->amount;
      
      $today = Date("Y-m-d");
      $todayStepCount = Income::where('invest_id', $investment->id)
      ->where('remarks', 'Step Bonus')
      ->where('ttime', $today)
      ->count();


      if ( $amount<=100) {
       
        $stepBonus=$amount*0.001;
    } else {
      $stepBonus=$amount*0.002;
    }

    
      

      if ($todayStepCount <= 0 && $totalSteps>=500 ) {

        echo "ID:" . $investment->user_id_fk . " Step Bous:" . $stepBonus . "<br>";
        Income::create([
          'user_id' => $investment->user_id,
          'user_id_fk' => $investment->user_id_fk,
          'amt' => $investment->amount,
          'comm' => $stepBonus,
          'level' => 0,
          'ttime' => Date("Y-m-d"),
          'invest_id' => $investment->id,
          'remarks' => 'Step Bonus'
        ]);
      }
    }
  }

        public function step_history(){
            $user=Auth::user();
               
           $stepHistory=UserStep::where('user_id',$user->id)->select('step','today') ->orderBy('today', 'desc')->get();
        $totalStep=UserStep::where('user_id',$user->id)->sum('step');
                  
                 
                    return response()->json([
                        'success' => true,
                        'stepHistory' => $stepHistory,
                        'totalStep' => $totalStep,
                        
                        'message' => 'Data Fetch Succesfully' // Returns all error messages
                    ], 200);
               
               
        }

}