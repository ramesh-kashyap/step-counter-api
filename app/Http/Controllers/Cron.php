<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\UserStep;
use Illuminate\Http\Request;
use App\Models\Investment;
use App\Models\BuyFund;
use App\Models\Income;
use App\Models\User;
use App\Models\Contract;
use App\Models\User_trade;
use App\Models\Reward;
use App\Models\Withdraw;
use Illuminate\Support\Facades\URL;
use App\Models\Trade;
use App\Models\plan;
use Illuminate\Support\Facades\Http;
use DateTime;
use DateInterval;
use DatePeriod;
use Carbon\Carbon;
use Helper;
use Plisio\PlisioSdkLaravel\Payment;
use Log;

class Cron extends Controller
{

  public function __construct()
  {
    date_default_timezone_set("Asia/Kolkata");   //India time (GMT+5:30)
  }
  public function tradeAmt()
  {
    User::where('id', '>=', 0)->update(['tradeAmt' => 0]);
  }


  public function releasefund()
  {

    $allResult = Investment::where('status', 'Pending')->get();
    $todays = Date("Y-m-d");
    if ($allResult) {
      foreach ($allResult as $key => $value) {

        $userID = $value->user_id;
        $joining_amt = $value->amount;
        date_default_timezone_set("Asia/Kolkata");   //India time (GMT+5:30)

        $user_detail = User::where('id', $userID)->first();
        $today = date("Y-m-d H:i:s");
        $previous_date = date('Y-m-d', (strtotime('-1 day', strtotime($today))));
        $newTime = date("Y-m-d H:i:s", strtotime($value->created_at . " +15 minutes"));



        if ($today >= $newTime) {
          Investment::where('id', $value->id)->delete();
        }


      }
    }




  }



  function manage_trade()
  {
    $trade = \DB::table('contract')->where('c_status', 1)->orderBy('created_at', 'DESC')->first();
    $status = false;
    $tcoins_arr = coinrates();
    if (!$trade) {
      $status = true;
    }
    if ($status == true) {
      $data = array(
        'status' => $status,
      );
    } else {

      $btc = "";
      $side = $trade->c_buy;
      $entry_price = round($tcoins_arr[$trade->c_name], 5);
      $position = $trade->qty;

      $action = "incre";
      $profit = $position + rand(1, 21);
      $entry_price = $entry_price + rand(0.1, 0.9);
      if ($profit % 2 != 0) {
        $action = "decre";
        $profit = $position - rand(1, 11);
        $entry_price = $entry_price - rand(0.1, 0.9);
      }

      $data = array(
        'profit' => $profit,
        'action' => $action,
        'btc_price' => $entry_price,
        'status' => $status,
      );
    }

    // Encode the object as JSON
    $jsonData = json_encode($data);
    header('Content-Type: application/json');
    echo $jsonData;

  }


  public function generate_roi()
  {

    $allResult = Contract::where('c_status', '-1')->get();
    $todays = Date("Y-m-d");
    $day = Date("l");

    if ($allResult) {

      foreach ($allResult as $key => $contract) {

        $userID = $contract->user_id;

        $userDetails = Income::where('invest_id', $contract->id)->where('remarks', 'Quantify Income')->first();

        if (!$userDetails) {
          $userDetail = User::where('id', $userID)->where('active_status', 'Active')->first();

          if ($userDetail) {
            echo "ID:" . $userDetail->username . " Roi:" . $contract->profit . "<br>";

            $data['remarks'] = 'Quantify Income';
            $data['comm'] = $contract->profit;
            $data['amt'] = $contract->c_ref;
            $data['invest_id'] = $contract->id;
            $data['level'] = 0;
            $data['ttime'] = $contract->ttime;
            $data['created_at'] = $contract->created_at;
            $data['updated_at'] = $contract->updated_at;
            $data['user_id_fk'] = $userDetail->username;
            $data['user_id'] = $userDetail->id;
            $income = Income::create($data);
          }





        }

      }

    }




  }






  public function leadership_rank()
  {

    date_default_timezone_set("Asia/Kolkata");
    //   User::where('id',20)->update(['name' =>'Rameshk']);
    $allResult = User::where('active_status', 'Active')->orderBy('id', 'ASC')->cursor();

    if ($allResult) {
      $counter = 1;
      foreach ($allResult as $key => $value) {

        $userID = $value->id;
        $userName = $value->username;
        $adate_date = $value->adate;

        $ids = $this->my_level_team($userID);


        $totalrecharge = Investment::whereIn('user_id', (!empty($ids) ? $ids : array()))->where('status', 'Active')->sum("amount");


        $require_power_bunsess = array('0', '5000', '10000', '20000');
        $require_bonus = array('0', '50', '100', '400');


        for ($p = 1; $p < 4; $p++) {
          $my_gen_busniess = $require_power_bunsess[$p];

          $bonus = $require_bonus[$p];

          $check_level = Reward::where('remarks', 'Reward Income')->where('user_id', $userID)->where('level', $p)->count("id");

          if ($check_level <= 0) {
            $goalstatus = ($totalrecharge >= $my_gen_busniess ? 'Achieved' : 'Pending');
            if ($goalstatus == 'Achieved') {

              echo "<br>";
              echo "ID : " . $userName . "<br>";
              echo "Level : " . $p;


              $data['remarks'] = 'Reward Income';
              $data['amount'] = $bonus;
              $data['total_business'] = $my_gen_busniess;
              $data['level'] = $p;
              $data['tdate'] = date("Y-m-d");
              $data['user_id_fk'] = $userName;
              $data['user_id'] = $userID;
              $data['status'] = 'Approved';
              $income = Reward::firstOrCreate(['remarks' => 'Reward Bonus', 'level' => $p, 'user_id' => $userID], $data);



            }

          }


        }


        $counter++;
      }
    }




  }


  public function update_vip()
  {
    try {
      $allResult = User::orderBy('id', 'desc')->get();

      if ($allResult->isEmpty()) {
        Log::info('No users found for VIP update.');
        return response()->json(['message' => 'No users found for VIP update.'], 404);
      }

      foreach ($allResult as $user) {
        $my_level_team = my_level_team_count($user->id);
        $gen_team1 = (array_key_exists(1, $my_level_team) ? $my_level_team[1] : array());
        $gen_team2 = (array_key_exists(2, $my_level_team) ? $my_level_team[2] : array());
        $gen_team3 = (array_key_exists(3, $my_level_team) ? $my_level_team[3] : array());

        $gen_team1 = User::where(function ($query) use ($gen_team1) {
          if (!empty($gen_team1)) {
            foreach ($gen_team1 as $key => $value) {
              //   $f = explode(",", $value);
              //   print_r($f)."<br>";
              $query->orWhere('id', $value);
            }
          } else {
            $query->where('id', null);
          }
        })->orderBy('id', 'DESC')->get();

        $gen_team2 = User::where(function ($query) use ($gen_team2) {
          if (!empty($gen_team2)) {
            foreach ($gen_team2 as $key => $value) {
              //   $f = explode(",", $value);
              //   print_r($f)."<br>";
              $query->orWhere('id', $value);
            }
          } else {
            $query->where('id', null);
          }
        })->orderBy('id', 'DESC')->get();
        $gen_team3 = User::where(function ($query) use ($gen_team3) {
          if (!empty($gen_team3)) {
            foreach ($gen_team3 as $key => $value) {
              //   $f = explode(",", $value);
              //   print_r($f)."<br>";
              $query->orWhere('id', $value);
            }
          } else {
            $query->where('id', null);
          }
        })->orderBy('id', 'DESC')->get();



        // Calculate totals
        $gen_team1total = $gen_team1->count();
        $team1_vip2 = $gen_team1->where('real_vip', 2)->count();
        $team1_vip3 = $gen_team1->where('real_vip', 3)->count();
        $team1_vip4 = $gen_team1->where('real_vip', 4)->count();
        $team1_vip5 = $gen_team1->where('real_vip', 5)->count();
        $team1_vip6 = $gen_team1->where('real_vip', 6)->count();
        $team1_vip7 = $gen_team1->where('real_vip', 7)->count();


        $gen_team2total = $gen_team2->count();
        $team2_vip2 = $gen_team2->where('real_vip', 2)->count();
        $team2_vip3 = $gen_team2->where('real_vip', 3)->count();
        $team2_vip4 = $gen_team2->where('real_vip', 4)->count();
        $team2_vip5 = $gen_team2->where('real_vip', 5)->count();
        $team2_vip6 = $gen_team2->where('real_vip', 6)->count();
        $team2_vip7 = $gen_team2->where('real_vip', 7)->count();

        $gen_team3total = $gen_team3->count();
        $team3_vip2 = $gen_team3->where('real_vip', 2)->count();
        $team3_vip3 = $gen_team3->where('real_vip', 3)->count();
        $team3_vip4 = $gen_team3->where('real_vip', 4)->count();
        $team3_vip5 = $gen_team3->where('real_vip', 5)->count();
        $team3_vip6 = $gen_team3->where('real_vip', 6)->count();
        $team3_vip7 = $gen_team3->where('real_vip', 7)->count();

        // Combine totals for team 2 and team 3
        $combine23_vip2 = $team2_vip2 + $team3_vip2;
        $combine23_vip3 = $team2_vip3 + $team3_vip3;
        $combine23_vip4 = $team2_vip4 + $team3_vip4;
        $combine23_vip5 = $team2_vip5 + $team3_vip5;
        $combine23_vip6 = $team2_vip6 + $team3_vip6;
        $combine23_vip7 = $team2_vip7 + $team3_vip7;


        $vip = $user->vip;

        // Determine VIP level based on conditions
        if (($team1_vip6 + $team1_vip7) >= 2 && ($combine23_vip5 + $combine23_vip6 + $combine23_vip7) >= 15) {
          $vip = 7;
        } elseif (($team1_vip5 + $team1_vip6 + $team1_vip7) >= 3 && ($combine23_vip4 + $combine23_vip5 + $combine23_vip6 + $combine23_vip7) >= 12) {
          $vip = 6;
        } elseif (($team1_vip4 + $team1_vip5 + $team1_vip6 + $team1_vip7) >= 3 && ($combine23_vip3 + $combine23_vip4 + $combine23_vip5 + $combine23_vip6 + $combine23_vip7) >= 5) {
          $vip = 5;
        } elseif (($team1_vip3 + $team1_vip4 + $team1_vip5 + $team1_vip6 + $team1_vip7) >= 2 && ($combine23_vip2 + $combine23_vip3 + $combine23_vip4 + $combine23_vip5 + $combine23_vip6 + $combine23_vip7) >= 5) {
          $vip = 4;
        } elseif (($team1_vip3 + $team1_vip3 + $team1_vip4 + $team1_vip5 + $team1_vip6 + $team1_vip7) >= 3) {
          $vip = 3;
        } else {
          $vip = $user->vip;
        }


        // Check if VIP status has changed
        if ($user->vip !== $vip) {
          $user->vip = $vip;
          $user->save();
          Log::info("VIP status updated for user ID: {$user->id} to VIP level {$vip}");
        } else {
          Log::info("No change in VIP status for user ID: {$user->id}");
        }
      }

      return response()->json(['message' => 'VIP levels updated successfully.'], 200);
    } catch (\Exception $e) {
      Log::error('Error updating VIP levels: ' . $e->getMessage());
      return response()->json(['error' => 'An error occurred while updating VIP levels. Please try again later.'], 500);
    }
  }


  public function my_level_team($userid, $level = 3)
  {
    $arrin = array($userid);
    $ret = array();

    $i = 1;
    while (!empty($arrin)) {
      $alldown = User::select('id')->whereIn('sponsor', $arrin)->get()->toArray();
      if (!empty($alldown)) {
        $arrin = array_column($alldown, 'id');
        $ret[$i] = $arrin;
        $i++;

        if ($i > $level) {
          break;
        }


      } else {
        $arrin = array();
      }
    }

    $final = array();
    if (!empty($ret)) {
      array_walk_recursive($ret, function ($item, $key) use (&$final) {
        $final[] = $item;
      });
    }


    return $final;

  }


  public function calculateRoi()
  {

    // date_default_timezone_set("Asia/Kolkata");   //India time (GMT+5:30)


    $investments = Investment::where('roiCandition', 0)->where('status', 'Active')->get();

    foreach ($investments as $investment) {
      $plan = plan::find($investment->plan);

      if (!$plan) {
        continue; // Skip if plan not found
      }

      $days = $plan->days;
      $profit = $plan->profit;

      $roiCount = Income::where('invest_id', $investment->id)
        ->where('remarks', 'Trading Bonus')
        ->count();

      $today = Date("Y-m-d");
      // Check if an income record for the current day already exists
      $todayRoiCount = Income::where('invest_id', $investment->id)
        ->where('remarks', 'Trading Bonus')
        ->where('ttime', $today)
        ->count();

      //   dd()

      if ($roiCount < $days && $todayRoiCount <= 0 && $today > $investment->sdate) {
        $bonus = ($investment->amount * $profit) / 100;

        echo "ID:" . $investment->user_id_fk . " Roi:" . $bonus . "<br>";


        Income::create([
          'user_id' => $investment->user_id,
          'user_id_fk' => $investment->user_id_fk,
          'amt' => $investment->amount,
          'comm' => $bonus,
          'level' => 0,
          'ttime' => Date("Y-m-d"),
          'invest_id' => $investment->id,
          'remarks' => 'Trading Bonus'
        ]);

        $newRoiCount = $roiCount + 1;

        if ($newRoiCount >= $days) {
          $investment->roiCandition = 1;
          $investment->save();
        }

        $this->add_level_income($investment->user_id, $bonus, $investment->plan);
      }
    }
  }

  
  public function countDirectTeamMembers($user_id)
  {
    $user = User::find($user_id);

    if (!$user) {
      return 0; // Return 0 if user not found
    }

    // Count the users where the sponsor is the given user
    $directTeamCount = User::where('sponsor', $user_id)->count();

    return $directTeamCount;
  }

  

  public function calculateRoi_income()
  {

    // date_default_timezone_set("Asia/Kolkata");   //India time (GMT+5:30)


    $investments = Investment::where('roiCandition', 0)->where('status', 'Active')->get();

    foreach ($investments as $investment) {

      $amount = $investment->amount;
      $totalGetAmount = $investment->amount * 2;
      $roiAmount = ($investment->amount * 2) / 30;
      $workingIncome = $investment->amount * 3;
      
      $roiCount = Income::where('invest_id', $investment->id)
        ->where('remarks', 'ROI Bonus')
        ->sum('comm');

      $workIncomesum = Income::where('user_id', $investment->user_id)
        ->where('remarks', 'Team Commission')
        ->sum('comm');
      
      $today = Date("Y-m-d");
      // Check if an income record for the current day already exists
      $todayRoiCount = Income::where('invest_id', $investment->id)
        ->where('remarks', 'ROI Bonus')
        ->where('ttime', $today)
        ->count();

      //   dd()

      if ($todayRoiCount <= 0 && $today > $investment->sdate) {

        if ($roiCount <= $totalGetAmount) {
          echo "ID:" . $investment->user_id_fk . " Roi:" . $roiAmount . "<br>";
          $this->add_level_income($investment->user_id, $roiAmount );

          Income::create([
            'user_id' => $investment->user_id,
            'user_id_fk' => $investment->user_id_fk,
            'amt' => $investment->amount,
            'comm' => $roiAmount,
            'level' => 0,
            'ttime' => Date("Y-m-d"),
            'invest_id' => $investment->id,
            'remarks' => 'ROI Bonus'
          ]);
         
        } else {

          $investment->roiCandition = 1;
          $investment->save();
        }


       
      }
    }
  }

  public function add_level_income($user_id,$amt)
  {

 
  
    // Level percentages
    $levelPercentages = [
      1 => 15,  // 1st level: 15%
      2 => 10,  // 2nd level: 10%
      3 => 5,   // 3rd level: 5%
      4 => 2,   // 4th level: 2%
      5 => 1,   // 5th level: 1%
    ];

    // Levels 6 to 10: 0.5%
    for ($i = 6; $i <= 10; $i++) {
      $levelPercentages[$i] = 0.5;
    }

    // Levels 11 to 20: 0.25%
    for ($i = 11; $i <= 20; $i++) {
      $levelPercentages[$i] = 0.25;
    }

    $user = User::find($user_id);

    if (!$user) {
      return false; // Skip if user not found
    }

    $fullname = $user->name;
    $rname = $user->username;
    $amount = $amt / 100;
    $currentUserId = $user_id; // Start from the initial user
    $cnt = 1; // Level counter

    while ($currentUserId && $currentUserId != "1" && $cnt <= 20) {
      $sponsorData = User::where('id', $currentUserId)->first();
      $sponsor = $sponsorData->sponsor;

      if (!$sponsorData) {
        break; // Break if no sponsor is found
      }

     
      

      if (!empty($sponsor)) {
        $Sposnor_status = User::where('id', $sponsor)->orderBy('id', 'desc')->first();
        $Sposnor_cnt = User::where('sponsor', $sponsor)->where('active_status', 'Active')->count("id");
        $sp_status = $Sposnor_status->active_status;
        $rank = $Sposnor_status->rank;
        $lastPackage = \DB::table('investments')->where('user_id', $Sposnor_status->id)->where('status', 'Active')->orderBy('id', 'DESC')->limit(1)->first();
        $plan = ($lastPackage) ? $lastPackage->plan : 0;

      } else {
        $Sposnor_status = array();
        $sp_status = "Pending";
        $Sposnor_cnt = 0;
        $rank = 0;
      }

         
      $teamCount = $this->countDirectTeamMembers($sponsor) ?? 0; // Assume a field direct_team_count exists
      $sp_status = $Sposnor_status->active_status ?? "Pending";

      $pp = 0;
       
     
      if ($sp_status == "Active") {
        if ($cnt == 1) {
          $pp = ($amount * $levelPercentages[1]);
        } elseif ($cnt == 2 && $Sposnor_cnt >= 3) {
          $pp = ($amount * $levelPercentages[2]);
        } elseif ($cnt == 3 && $Sposnor_cnt >= 2) {
          $pp = ($amount * $levelPercentages[3]);
        } elseif ($cnt >= 4 && $cnt <= 10 && $Sposnor_cnt >= 2) {
          $pp = ($amount * $levelPercentages[$cnt]);
        } elseif ($cnt >= 11 && $cnt <= 20 && $Sposnor_cnt >= 5) {
          $pp = ($amount * $levelPercentages[$cnt]);
        }
       

      }

      $spid = $Sposnor_status->id;
      $totalDepositSponsor=Investment::where('user_id',$spid)->where('status','Active')->sum('amount');
                                             
                     $total_profit_b = Income::where('user_id', $spid)->sum('comm');
                     $total_profit=($total_profit_b)?$total_profit_b:0;
        
                     $total_get= $totalDepositSponsor*300/100;
                   
                     $n_m_t = $total_get - $total_profit_b;
                       if($pp >= $n_m_t)
                      {
                          $pp = $n_m_t;

                      } 
                     
      // Save income if commission is calculated
      if ($spid > 0 && $cnt <= 20) {
        if ($pp > 0) {
          echo "data send";
          Income::create([
            'user_id' => $Sposnor_status->id,
            'user_id_fk' => $Sposnor_status->username,
            'amt' => $amt, // Based on divided investment
            'comm' => $pp,
            'remarks' => 'Team Commission',
            'level' => $cnt,
            'rname' => $rname,
            'fullname' => $fullname,
            'ttime' => date("Y-m-d"),
          ]);
        }
      }


      // Move to the next level (sponsor's sponsor)
      $currentUserId = $sponsorData->sponsor;
      $cnt++;
    }

    return true;
  }

    public function generate_roi_income()
  {
    date_default_timezone_set("Asia/Kolkata");   // India time (GMT+5:30)
    $allResult = Investment::where('status', 'Active')->where('roiCandition', 0)->get();
    $todays = Date("Y-m-d");
    $day = Date("l");
    dd($allResult);
    if ($allResult) {
      if ($day != "Sunday" && $day != "Saturday") {
        foreach ($allResult as $key => $value) {
          $userID = $value->user_id;
          $joining_amt = $value->amount;

          $userDetails = User::where('id', $userID)->where('active_status', 'Active')->first();
          $today = date("Y-m-d");
          $previous_date = date('Y-m-d', (strtotime('-1 day', strtotime($today))));

          if ($userDetails) {
            // Calculate ROI percentage based on investment amount
            $roi_percentage = 0;
            if ($joining_amt == 10000) {
              $roi_percentage = 10;
            } elseif ($joining_amt == 25000) {
              $roi_percentage = 12;
            } elseif ($joining_amt == 500000) {
              $roi_percentage = 15;
            }

            // Calculate ROI amount
            $roi_amount = $joining_amt * $roi_percentage / 100;

            // Check if the user has not reached the maximum income limit
            $total_profit_b = Income::where('user_id', $userID)
              ->where('invest_id', $value->id)
              ->where('remarks', 'Roi Bonus')
              ->sum("comm");
            $total_profit = ($total_profit_b) ? $total_profit_b : 0;
            $total_get = $joining_amt * 300 / 100;
            $max_income = $total_get - $total_profit;

            if ($roi_amount >= $max_income) {
              $roi_amount = $max_income;
            }

            // Create income record
            echo "ID:" . $userDetails->username . " Package:" . $joining_amt . " Roi:" . $roi_amount . "<br>";
            $data['remarks'] = 'Roi Bonus';
            $data['comm'] = $roi_amount;
            $data['amt'] = $joining_amt;
            $data['level'] = 0;
            $data['invest_id'] = $value->id;
            $data['ttime'] = date("Y-m-d");
            $data['user_id_fk'] = $userDetails->username;
            $data['user_id'] = $userDetails->id;
            $income = Income::firstOrCreate(['remarks' => 'Roi Bonus', 'ttime' => date("Y-m-d"), 'user_id' => $userID, 'invest_id' => $value->id], $data);
          }
        }
      }
    }
  }

  public function reward_bonus()
  {

    $allResult = User::where('active_status', 'Active')->get();
    // print_r($allResult);die;
    if ($allResult) {
      foreach ($allResult as $key => $value) {

        $user_id = $value->id;
        $username = $value->username;
        $Power_leg = $value->power_leg;
        $Vicker_leg = $value->vicker_leg;

        // $tolteam=$this->my_level_team_count($user_id);


        $investment = Investment::where('user_id', $user_id)->where('status', 'Active')->sum("amount");
        User::where('id', $user_id)
          ->update([
            'package' => $investment
          ]);

        //   $total_team=(!empty($tolteam)?count($tolteam):0);

        $directUser = User::where('sponsor', $user_id)->where('package', '>=', 100)->where('active_status', 'Active')->count();

        $require_power_bunsess = array('0', '1', '6', '16', '36');
        $require_bonus = array('0', '5', '30', '120', '220');


        for ($p = 1; $p < 5; $p++) {
          $my_gen_busniess = $require_power_bunsess[$p];

          $bonus = $require_bonus[$p];


          // $power_leg=$my_gen_busniess*50/100;
          // $vicker_leg=$my_gen_busniess*50/100;

          // $Require_power_leg=$my_gen_busniess*60/100;
          // $Require_vicker_leg=$my_gen_busniess*40/100;

          $check_level = Income::where('remarks', 'Task Income')->where('user_id', $user_id)->where('level', $p)->count("id");

          if ($check_level <= 0) {
            $goalstatus = ($directUser >= $my_gen_busniess ? 'Achieved' : 'Pending');
            if ($goalstatus == 'Achieved') {

              echo "<br>";
              echo "ID : " . $username . "<br>";
              echo "Level : " . $p;
              User::where('id', $user_id)
                ->update([
                  'rank' => $p
                ]);

              $data['remarks'] = 'Task Income';
              $data['comm'] = $bonus;
              $data['amt'] = $bonus;
              $data['level'] = $p;
              $data['ttime'] = date("Y-m-d");
              $data['user_id_fk'] = $username;
              $data['user_id'] = $user_id;
              $income = Income::firstOrCreate(['remarks' => 'Task Income', 'level' => $p, 'user_id' => $user_id], $data);


            }

          }


        }




      }
    }


    $allResult = Contract::where('c_status', 1)->get();
    if ($allResult) {

      foreach ($allResult as $key => $contract) {



        $user = User::where('id', $contract->user_id)->first();
        $contract->c_status = -1;
        $contract->save();

        $ref = $contract->c_ref;
        $user_id = $user->id;

        $data['remarks'] = 'Quantify Income';
        $data['comm'] = $contract->profit;
        $data['amt'] = $ref;
        $data['invest_id'] = $contract->id;
        $data['level'] = 0;
        $data['ttime'] = date("Y-m-d");
        $data['user_id_fk'] = $user->username;
        $data['user_id'] = $user->id;
        $income = Income::create($data);

        add_level_income($user_id, $contract->profit);


      }

    }




  }





  public function dltPayout()
  {
    \DB::statement("SET SQL_MODE=''");
    $data = \DB::select("SELECT * , COUNT(id) AS DuplicateRanks FROM incomes WHERE `remarks`='Leadership Ranks and Rewards' GROUP BY user_id HAVING COUNT(id)>1;");

    if ($data) {
      $counter = 1;
      foreach ($data as $key => $value) {

        //  dd($value->id);
        Income::where('id', $value->id)->delete();
      }


    }



  }



  public function dailyIncentive()
  {


    $allResult = User::where('active_status', 'Active')->get();
    $todays = Date("Y-m-d");


    if ($allResult) {
      foreach ($allResult as $key => $value) {
        $userID = $value->id;
        $userName = $value->username;
        $userRank = $value->rank;
        $endDate = Date("Y-m-d");
        $rewardDetail = Reward::where('user_id', $userID)->orderBy('id', 'DESC')->limit(1)->first();

        if ($rewardDetail) {

          $Checkincome = Income::where('user_id', $userID)->where('remarks', 'Leadership Ranks and Rewards')->where('level', $rewardDetail->level)->first();
          $days = 0;
          if (!$Checkincome) {
            $days = 15;
          } else {
            $days = (strtotime($endDate) - strtotime($Checkincome->ttime)) / (60 * 60 * 24);
          }

          echo "<br>";
          echo "ID : " . $userName . "<br>";
          echo "Days : " . $days;

          if ($days >= 15) {
            $data['remarks'] = 'Leadership Ranks and Rewards';
            $data['comm'] = $rewardDetail->amount;
            $data['level'] = $rewardDetail->level;
            $data['amt'] = $rewardDetail->amount;
            $data['invest_id'] = $rewardDetail->id;
            $data['ttime'] = date("Y-m-d");
            $data['user_id_fk'] = $userName;
            $data['user_id'] = $userID;
            $income = Income::firstOrCreate(['remarks' => 'Leadership Ranks and Rewards', 'ttime' => date("Y-m-d"), 'user_id' => $userID], $data);
          }



        }





      }
    }
  }




  public function dynamicupicallback()
  {



    //   echo "Hello";
//   print_r($response);die();
    $response = file_get_contents('php://input');
    date_default_timezone_set('Asia/Kolkata');
    $day = date('l');
    $todays = date("Y-m-d");
    $result = json_decode($response, true);

    \DB::table('activities')->insert(['data' => $response]);
    if (!empty($result)) {

      if ($result['status'] == "completed") {

        $orderId = $result['order_number'];
        $username = $result['order_name'];
        $amount = $result['source_amount'];
        $updateTrue = BuyFund::where('orderId', $orderId)->where('status', 'Pending')->update(['status' => 'Active']);

        if ($updateTrue) {

          $user_detail = User::where('username', $username)->first();
          if ($user_detail->active_status == "Pending") {
            // $user_update=array('active_status'=>'Active','adate'=>Date("Y-m-d H:i:s"),'package'=>$amount);
            // User::where('id',$user_detail->id)->update($user_update);
            \DB::table('general_settings')->where('id', 1)->update(['people_online' => generalDetail()->people_online + 1]);
            \DB::table('general_settings')->where('id', 1)->update(['our_investors' => generalDetail()->our_investors + 1]);
          } else {
            $total = $user_detail->package + $amount;
            //   $user_update=array('package'=>$total,'active_status'=>'Active');
            // User::where('id',$user_detail->id)->update($user_update); 
          }


          \DB::table('general_settings')->where('id', 1)->update(['total_fund_invested' => generalDetail()->total_fund_invested + $amount]);
          $plan = 'BEGINNER';



        }


      } else {
        if ($result['status'] == "mismatch" && $result['amount'] >= $result['invoice_total_sum']) {


          $orderId = $result['order_number'];
          $username = $result['order_name'];
          $amount = $result['source_amount'];
          // $updateTrue = BuyFund::where('orderId',$orderId)->where('status','Pending')->update(['status' => 'Active']);

          //  if($updateTrue)  
          //  {

          //    $user_detail=User::where('username',$username)->first();
          //     if ($user_detail->active_status=="Pending")
          //     {   
          //     $user_update=array('active_status'=>'Active','adate'=>Date("Y-m-d H:i:s"),'package'=>$amount);
          //     User::where('id',$user_detail->id)->update($user_update);
          //   \DB::table('general_settings')->where('id',1)->update(['people_online'=>generalDetail()->people_online+1]);
          //   \DB::table('general_settings')->where('id',1)->update(['our_investors'=>generalDetail()->our_investors+1]);
          //    }
          //    else
          //    {
          //      $total=$user_detail->package+$value->amount;
          //       $user_update=array('package'=>$total,'active_status'=>'Active');
          //     User::where('id',$user_detail->id)->update($user_update); 
          //    }


          //    \DB::table('general_settings')->where('id',1)->update(['total_fund_invested'=>generalDetail()->total_fund_invested+$amount]);
          //         $plan ='BEGINNER';



          //  }//



        }
      }

    }





  }


  public function my_binary($userid)
  {
    $arrin = array($userid);
    $ret = array();
    // print_r($arrin);die();
    while (!empty($arrin)) {
      $alldown = User::select('id')->whereIn('Parentid', $arrin)->get()->toArray();
      if (!empty($alldown)) {
        $arrin = array_column($alldown, 'id');
        $ret[] = $arrin;


      } else {
        $arrin = array();
      }
    }
    // continue;    
    $final = array();
    if (!empty($ret)) {
      array_walk_recursive($ret, function ($item, $key) use (&$final) {
        $final[] = $item;
      });
    }

    return $final;

  }

  public function team_by_position($userid, $position)
  {
    $ret = array();
    $get_position_user = User::where('Parentid', $userid)->where('position', $position)->first();
    if ($get_position_user) {

      $ret = $this->my_binary($get_position_user->id);
      $ret[] = $get_position_user->id;
    }

    return $ret;
  }







  public function my_level_team_count($userid, $level = 10)
  {
    $arrin = array($userid);
    $ret = array();

    $i = 1;
    while (!empty($arrin)) {
      $alldown = User::select('id')->whereIn('sponsor', $arrin)->get()->toArray();
      if (!empty($alldown)) {
        $arrin = array_column($alldown, 'id');
        $ret[$i] = $arrin;
        $i++;


      } else {
        $arrin = array();
      }
    }

    $final = array();
    if (!empty($ret)) {
      array_walk_recursive($ret, function ($item, $key) use (&$final) {
        $final[] = $item;
      });
    }


    return $final;

  }

}
