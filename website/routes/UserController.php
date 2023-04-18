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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
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
        $amis = DB::table('amis')->whereNull('deleted_at')->get();
        return view('user.create',compact('amis'));
    }

    public function store(Request $request){
      $input = $request->all();
      $rules = [
          'name'     => 'required|string|unique:users,name,null,id,deleted_at,NULL',
          'email'    => 'required|string|email|max:255|unique:users,email,null,id,deleted_at,NULL',
          'phone'    => 'required|string|unique:users,phone,null,id,deleted_at,NULL',
          'password' => 'required'
       ];
       
      $request->validate($rules);

       $User = new User;
       $User->name    = $input['name'];
       $User->email   = $input['email'];
       $User->phone   = $input['phone'];
       $User->password = Hash::make($input['password']);

       if($User->save())
           return redirect()->route('index.user')->with('status',true)->with('message','Successfully created user');
         else
           return redirect()->route('index.user')->with('status',false)->with('message','Failed to create user');
    }

    public function createInstance(Request $request){
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
          if($staus){
            return ['status'=>true,'message'=>'Instance created'];
          }
          else{
            return ['status'=>false,'message'=>'Failed to create instance'];
          }
     }

    public function createIns($id,$ami,$type,$name){
          try{
              $url    = "https://pcb70syeq7.execute-api.ap-south-1.amazonaws.com/default/create-instance";
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
              ));
              $response = curl_exec($curl);
              $err = curl_error($curl);
              curl_close($curl);
              DB::table('instance_user')->insert([
                'user_id' => $id,
                'ami'     => $ami,
                'type'    => $type,
                'name'    => $name,
                'status'  => '1'
               ]);
               return true;
          }catch(\Exception $e){
                return false;
          }
      }

      public function instances(Request $request){
          $instances = UserInstance::where(function($query) use ($request){
            if($request->search)
                $query->whereRaw('LOWER(name) like ?' , '%'.strtolower($request->search).'%');
            if($request->status == '0' || $request->status)
                $query->where('status',$request->status);
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
            $url    = "https://7g1monbiyj.execute-api.ap-south-1.amazonaws.com/default/get-ip";
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
              $url    = "https://t91ya37tal.execute-api.ap-south-1.amazonaws.com/default/stop-ec2";
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
              ));
              $response = curl_exec($curl);
              $err = curl_error($curl);
              curl_close($curl);
              DB::table('instance_user')->where('name',$name)->update(['status'=>'0']);
              sleep(45);
              return ['status'=> true ,'message'=>'Stopped'];
          }catch(\Exception $e){
                return ['status'=> false ,'message'=>'Failed to stop'];
          }
      }

      public function startInstance(Request $request){
        $name = $request->name;
        try{
         $url    = "https://zxt7zrf5i3.execute-api.ap-south-1.amazonaws.com/default/start-ec2";
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
         ));
         $response = curl_exec($curl);
         $err = curl_error($curl);
         curl_close($curl);
         DB::table('instance_user')->where('name',$name)->update(['status'=>'1']);
         sleep(45);
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
            $url    = "https://milgkxlot2.execute-api.ap-south-1.amazonaws.com/default/delete-instance";
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
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            DB::table('instance_user')->whete('user_id',$user->id)->delete();
         }catch(\Exception $e){
         }
            
            if($request->is_notify == '1'){
                $data = array(
                  'to'     => $email,
                  'due'    =>  $request->reason
                );
                \Mail::send('Mails.delete_account', $data, function ($message) use($data) {
                  $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
                  $message->to($data['to'])->subject('Account Deleted');
                });
            }

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

}
