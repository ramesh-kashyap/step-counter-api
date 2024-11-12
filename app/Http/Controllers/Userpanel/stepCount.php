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

                //    dd(  $check_date===$match_date_formatted);
                 if($check_date===$match_date_formatted){
                 
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