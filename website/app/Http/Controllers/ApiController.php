<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use DB;
use App\Models\Product;
use Auth;
use Hash;

class ApiController extends Controller
{

  public function croneStopAllInstance(){
    try{
        $url    = env('STOP_ALL_INSTANCE');
        $curl   = curl_init();
        $getUrl = $url;
        curl_setopt_array($curl, array(
          CURLOPT_URL => $getUrl,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
                   "x-api-key: " . env('API_TOKEN'),
                )
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        DB::table('instance_user')->update(['status'=>'0']);
        return 'True';
      }catch(\Exception $e){
        return "false";
      } 
  }

  public function stopInstance(){
    if( env('START_TIME') > date('H:i:s') || date('H:i:s') > env('END_TIME') ){
         $users = User::select('id','email','bonus_date','is_bonus_time')->whereDate('bonus_date',date('Y-m-d'))->where('is_bonus_time','1')->get();
         if($users->toarray()){
            foreach($users as $key => $user){
                $startTime = date('H:i:s',strtotime($user->bonus_date));
                $endTime   = date('H:i:s',strtotime('+' . env('BONUS_TIME') . ' minutes', strtotime($startTime)));
               if(strtotime(date('H:i:s')) > strtotime($endTime)){
                     $name = $user->email;
             try{
                      $url    = env('STOP_INSTANCE');
                      $dataArray = [
                          "name" => $name
                      ];
                      $curl   = curl_init();
                      $data   = http_build_query($dataArray);
                      $getUrl = $url."?".$data;

                      curl_setopt_array($curl, array(
                        CURLOPT_URL => $getUrl,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_HTTPHEADER => array(
                          "x-api-key: " . env('API_TOKEN'),
                       )
                      ));
                      $response = curl_exec($curl);
                      $err = curl_error($curl);
                      curl_close($curl);
                      DB::table('instance_user')->where('name',$name)->update(['status'=>'0']);
                      DB::table('users')->where('id',$user->id)->update([ 'bonus_date' => NULL , 'is_bonus_time'=>'0']);
                      return ['status'=> true ,'message'=>'Stopped'];
                  }catch(\Exception $e){
                        return ['status'=> false ,'message'=>'Failed to stop'];
                  }
               }
            }
         }
    }
  }


}
