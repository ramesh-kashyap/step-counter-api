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
               
            $check_date=Carbon::today()->format('d-m-Y');
                               
            $match_date =UserStep::where('user_id',1)->get('today');
          dd(  $match_date);
                 if($match_date){
                    return response()->json([
                        'success' => false,
                        
                        'message' => 'today Already Completed' // Returns all error messages
                    ], 200);
                 }else{
                    $data = [
                        'user_id' => 1, // Replace with the actual user ID if needed
                        'step' => $request->step,
                        'today' => Carbon::today()->format('Y-m-d')  // Store date in 'Y-m-d' format
                    ];
            
                    // Insert data using Query Builder
                    DB::table('user_steps')->insert($data);
                   return response()->json([
                'success' => true,
                
                'message' => 'data save Successfully.' // Returns all error messages
            ], 200);}
        }
       


}