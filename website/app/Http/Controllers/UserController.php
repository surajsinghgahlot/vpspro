<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Validator;
use App\Models\UserInstance;
use App\User;
use Hash;
use Auth;

class UserController extends Controller
{

      public function __construct()
    {
         $this->middleware('auth')->except('croneStopAllInstance');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
       if(auth::user()->role_id != 1){
             return redirect()->route('home');
       }
        $users = User::where(function($query) use ($request){
            if(isset($request->search) && !empty($request->search)){
               $query->whereRaw('LOWER(name) like ?' , '%'.strtolower($request->search).'%')
                     ->orWhereRaw('LOWER(email) like ?' , '%'.strtolower($request->search).'%')
                     ->orWhereRaw('LOWER(phone) like ?' , '%'.strtolower($request->search).'%')
                     ->orWhereRaw('LOWER(address) like ?' , '%'.strtolower($request->search).'%');
            }
            if(isset($request->status) && !empty($request->status)){
                 if($request->status == 'active')
                      $query->where('is_active','1');

                  if($request->status == 'deactive')
                      $query->where('is_active','0');
            }
        })
        ->where('role_id','2')
        ->whereNull('deleted_at')
        ->orderBy('id','desc')
        ->paginate('10');
      $data['users'] = $users;
      $amis = DB::table('amis')->whereNull('deleted_at')->get();
      $instanceTypes = DB::table('instance_types')->whereNull('deleted_at')->get();
      return view('user.index',compact('data','amis','instanceTypes'));
    }

    public function create(Request $request){
      if(auth::user()->role_id != 1){
        return redirect()->route('home');
      }
        $amis = DB::table('amis')->whereNull('deleted_at')->get();
        return view('user.create',compact('amis'));
    }

    public function store(Request $request){
      if(auth::user()->role_id != 1){
        return redirect()->route('home');
      }
      $input = $request->all();
      $rules = [
          'name'     => 'required|string|unique:users,name,null,id,deleted_at,NULL',
          'email'    => 'required|string|email|max:255|unique:users,email,null,id,deleted_at,NULL',
          'phone'    => 'string|unique:users,phone,null,id,deleted_at,NULL',
          'password' => 'required'
       ];
       
      $request->validate($rules);

       $User = new User;
       $User->name    = $input['name'];
       $User->email   = $input['email'];
       $User->phone   = $input['phone'] ?? NULL;
       $User->role_id   = '2';
       $User->password = Hash::make($input['password']);

       if($User->save()){
         try{
           $data = array(
             'to'       => $request->email,
             'name'     =>  $request->name,
             'emailAddress'  =>  $request->email,
             'password'      =>  $request->password
           );
           \Mail::send('Mails.registered', $data, function ($message) use($data) {
             $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
             $message->to($data['to'])->subject('Registered');
           });
         }catch(\Exception $e){

         }
         return redirect()->route('index.user')->with('status',true)->with('message','Successfully created user');
       }
         else{
           return redirect()->route('index.user')->with('status',false)->with('message','Failed to create user');
         }
    }

    public function createInstance(Request $request){
         if(auth::user()->role_id != 1){
          return redirect()->route('home');
         }
          $inputs = $request->all();
          $rules = [
             'id'   => 'required',
             'ami'  => 'required',
             'type' => 'required'
          ];
          $validator = Validator::make($inputs, $rules);
          if ($validator->fails()) {
              $errors =  $validator->errors()->all();
              return response(['status' => false , 'message' => $errors[0]] , 200);              
          }
          $user = User::find($request->id);
          $name = $user->email;
          $staus =  $this->createIns($request->id,$request->ami,$request->type,$name);
      $previewDate = $user->instance_created;
      if(strtotime($previewDate) >= strtotime(date('Y-m-d'))){
        $expiryDate =  date('Y-m-d', strtotime('+30 days', strtotime($previewDate)));
        }else{
        $expiryDate =  date('Y-m-d', strtotime('+30 days', strtotime(date('Y-m-d'))));
        }
        if(!empty($user->instance_expiry) && !is_null($user->instance_expiry)){
           if(strtotime($user->instance_expiry) >= strtotime(date('Y-m-d H:i:s'))){
                $user->instance_expiry = $user->instance_expiry;
           }
        }else{
          $user->instance_expiry = $expiryDate;
        }

          if($staus && $user->update()){
            return ['status'=>true,'message'=>'Instance created'];
          }
          else{
            return ['status'=>false,'message'=>'Failed to create instance'];
          }
     }

    public function createIns($id,$ami,$type,$name){
          try{
              $url    = env('CREATE_INSTANCE');
              $dataArray = [
                  "ami"  => $ami,
                  "type" => $type,
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
              $user = User::find($id);
              DB::table('instance_user')->insert([
                'user_id' => $id,
                'ami'     => $ami,
                'type'    => $type,
                'name'    => $name,
                'expiry_date' => date('Y-m-d', strtotime('+30 days', strtotime($user->instance_created))),
                'status'  => '1'
               ]);
               return true;
          }catch(\Exception $e){
                return false;
          }
      }

      public function instances(Request $request){
        

        if(auth::user()->role_id == '2'){
           $mTime = date('H:i:s',strtotime(env('START_TIME')));
           $aTime = date('H:i:s',strtotime(env('END_TIME')));
           $bonusDate = auth::user()->bonus_date;
           $bonus = false;
           if($bonusDate && strtotime(date('Y-m-d',strtotime($bonusDate))) == strtotime(date('Y-m-d'))){
                $startTime   =  date('Y-m-d H:i:s',strtotime($bonusDate));
                $endTime     =  date('Y-m-d H:i:s',strtotime('+' . env('BONUS_TIME') . ' minutes', strtotime($startTime)));
                $currentTime =  date('Y-m-d H:i:s');
                if((strtotime($startTime) < strtotime($currentTime)) && (strtotime($endTime) > strtotime($currentTime))){
                   $bonus = true;
               }
           }


             if(time() > strtotime($aTime) || time() < strtotime($mTime))
                 if(!$bonus)
                    return redirect()->route('home');
              }


          $instances = UserInstance::where(function($query) use ($request){

            if($request->search)
                $query->whereRaw('LOWER(name) like ?' , '%'.strtolower($request->search).'%');
            if($request->status == '0' || $request->status)
                $query->where('status',$request->status);

            if($request->instance_id){
                $query->where('id',$request->instance_id);
            }

          })->whereNull('deleted_at');
        if(auth::user()->role_id == '2'){
          $instances = $instances->where('user_id',auth::id());
        }
         $instances = $instances->orderBy('id','desc')->paginate('10');
         $data['instances'] = $instances;
         $amis = DB::table('amis')->whereNull('deleted_at')->get();
         $instanceTypes = DB::table('instance_types')->whereNull('deleted_at')->get();
         return view('instance.index',compact('data','amis','instanceTypes'));
      }

      public function getIp(Request $request){
           $name = $request->name;
          try{
            $url    = env('GET_IP');
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
            $ip = $response;
            return ['stauts'=>true,'message'=>$ip];
        }catch(\Exception $e){
              return ['stauts'=>false,'message'=>'Ip Not found'];
        }
      }

      public function stopInstance(Request $request){
             $name = $request->name;
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
              sleep(15);
              return ['status'=> true ,'message'=>'Stopped'];
          }catch(\Exception $e){
                return ['status'=> false ,'message'=>'Failed to stop'];
          }
      }

      public function removeInstance(Request $request){
          $email    = $request->id;
          try{
            $url    = env('REMOVE_INSTANCE');
            $dataArray = [
                "name" => $email
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
            \DB::table('instance_user')->where('name',$email)->delete();
         }catch(\Exception $e){
         }
            return redirect()->route('index.user')->with('status',true)->with('message',__('Successfully deleted account'));
         return redirect()->route('index.user')->with('status',false)->with('message',__('Failed to delete account'));
      }


      public function startInstance(Request $request){
        $name = $request->name;
        try{
         $url    = env('START_INSTANCE');
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
         DB::table('instance_user')->where('name',$name)->update(['status'=>'1']);
         sleep(15);
         return ['status'=>true,'message'=>'Started'];
     }catch(\Exception $e){
           return ['status'=>false,'message'=>'Failed to start'];
     }
 }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);
        $data['user'] = $user;
        return view('user.show',compact('data'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
         $user = User::find($request->id);
         $email    = $user->email;
         if($user->delete()){

          try{
            $url    = env('DELETE_INSTANCE');
            $dataArray = [
                "name" => $email
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
            DB::table('instance_user')->whete('user_id',$user->id)->delete();
         }catch(\Exception $e){
         }
            
            // if($request->is_notify == '1'){
            //     $data = array(
            //       'to'     => $email,
            //       'due'    =>  $request->reason
            //     );
            //     \Mail::send('Mails.delete_account', $data, function ($message) use($data) {
            //       $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
            //       $message->to($data['to'])->subject('Account Deleted');
            //     });
            // }

            return redirect()->route('index.user')->with('status',true)->with('message',__('Successfully deleted account'));
         }
         return redirect()->route('index.user')->with('status',false)->with('message',__('Failed to delete account'));
    }

    public function activeAccount(Request $request){
        $user = User::find($request->id);
        $user->is_active        = '1';
        $email    = $user->email;
         if($user->update()){
            
            if($request->is_notify == '1'){
                $data = array(
                  'to'     => $email
                );

                \Mail::send('Mails.active_account', $data, function ($message) use($data) {
                  $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
                  $message->to($data['to'])->subject('Active Account');
                });
            }

            return redirect()->route('index.user')->with('status',true)->with('message',__('Successfully actived account'));
         }
         return redirect()->route('index.user')->with('status',false)->with('message',__('Failed to active account'));
    }

    public function deactiveAccount(Request $request){
      if(auth::user()->role_id != 1){
        return redirect()->route('home');
      }
      $user = User::find($request->id);
      $email    = $user->email;
      $user->is_active        = '0';
      $user->deactive_reason  = $request->reason;
       if($user->update()){
          
          if($request->is_notify == '1'){
              $data = array(
                'to'     => $email,
                'due'    =>  $request->reason
              );

              \Mail::send('Mails.deactive_account', $data, function ($message) use($data) {
                $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
                $message->to($data['to'])->subject('Deactive Account');
              });
          }

          return redirect()->route('index.user')->with('status',true)->with('message',__('Successfully deactive account'));
       }
       return redirect()->route('index.user')->with('status',false)->with('message',__('Failed to deactive account'));
  }

  public function resetPassword(Request $request,$id){
       $input    = $request->all();
       $rules = [
                'password'      => 'required|min:6',
                ];
        $request->validate($rules);
        $User  = User::find($id);
        $User->password = Hash::make($input['password']);
        if($User->update()){
        return redirect()->back()->with('status',true)->with('message','Successfully reset password');
    }
        return redirect()->back()->with('status',false)->with('message','Failed to reset passsword');
  }

  public function newinstance(Request $request){
      $UserInstance = UserInstance::find($request->id);
      $user = User::find($UserInstance->user_id);
      $previewDate = $user->instance_expiry;
      if(strtotime($previewDate) >= strtotime(date('Y-m-d'))){
         $expiryDate =  date('Y-m-d', strtotime('+30 days', strtotime($previewDate)));
      }else{
         $expiryDate =  date('Y-m-d', strtotime('+30 days', strtotime(date('Y-m-d'))));
      }
      $user->instance_expiry = $expiryDate;
      if($user->update())
        return redirect()->back()->with('status',true)->with('message','Successfully renew instance');
      else
        return redirect()->back()->with('status',false)->with('message','Failed to renew instance');
               
  }

  public function stopAllInstance(){
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
        sleep(15);
        return redirect()->back()->with('status',true)->with('message','Stopped all instances');
      }catch(\Exception $e){
        return redirect()->back()->with('status',false)->with('message','Failed to stop all instances');
      }
  }

  public function  startAllInstance(){
    try{
     $url    = env('START_ALL_INSTANCE');
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
     DB::table('instance_user')->update(['status'=>'1']);
     sleep(15);
     return redirect()->back()->with('status',true)->with('message','Started all instances');
    }catch(\Exception $e){
        return redirect()->back()->with('status',false)->with('message','Failed to start all instances');
    }
}

  public function instanceRDP(Request $request){
           $email = $request->email;
           try{
              $url    = env('GET_IP');
              $dataArray = [
                  "name" => $email
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
              $ip = $response;
              $ip = str_replace('"', "", $ip);
              $file = "$email.rdp";
              $txt = fopen($file, "w") or die("Unable to open file!");
              fwrite($txt, "auto connect:i:1\n");
              fwrite($txt, "full address:s:$ip\n");
              fwrite($txt, "username:s:Administrator\n");
              fwrite($txt, "password:s:VPS@786\n");
              fclose($txt);

              header('Content-Description: File Transfer');
              header('Content-Disposition: attachment; filename='.basename($file));
              header('Expires: 0');
              header('Cache-Control: must-revalidate');
              header('Pragma: public');
              header('Content-Length: ' . filesize($file));
              header("Content-Type: text/plain");
              readfile($file);
           }catch(\Exception $e){

           }

  }

  public function addBonus(Request $request){
      $userId = $request->user_id;
      $user = User::find($userId);
      $user->bonus_date     = date('Y-m-d H:i:s');
      $user->is_bonus_time  = '1';
      if($user->update())
            return back()->with('status',true)->with('message','Added '.env('BONUS_TIME').' mins bonus time');
      else
            return back()->with('status',false)->with('message','Failed to add bonus time');
  }

}
