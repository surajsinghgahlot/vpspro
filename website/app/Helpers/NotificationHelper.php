<?php
namespace App\Helpers;
use DB;

class NotificationHelper {

    static $SERVER_API_KEY = 'AAAAUgcOHfQ:APA91bE4pxEdgUMVpF4vruh0pLBJHaeStJvbuGqMZLAmXICoMpUYNuGrDIBnIzT5VgeviEORp_JqPjYvs4ONvQLb8mDXelNd_Jn6oU982hVWR1LN9VoQdc6cDeK3OqPrCWQGcOUFR47r';

    public static function android($deviceTokens,$data){

       $registration_ids =  $deviceTokens;
       $data             = $data;
       
       $message = array(
         "message" => $data
       );
  
       $url = 'https://fcm.googleapis.com/fcm/send';
    
            $fields = array(
                'registration_ids' => $registration_ids,
                'data' => $message
                );
                
            $headers = array(
            'Authorization: key=' . self::$SERVER_API_KEY,
            'Content-Type: application/json'
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            
                if ($result === FALSE){
                die('Curl failed: ' . curl_error($ch));
                }
                curl_close($ch);

    }

    public static function broadcast($notifyData){

         if(empty($notifyData) || is_null($notifyData))
              return false;

         $storeData = array();

         if($notifyData['receiver_id'] == 'ALL'){
              $receiverData = \DB::table('users')->select('id as receiver_id','role_id','device_type','device_token','is_notify')
                                   ->where('is_active','1')
                                   ->where('is_ban','0')
                                   ->where('is_hold','0')
                                   ->get();
         }else{
            $receiverData = \DB::table('users')->select('id as receiver_id', 'role_id' ,'device_type','device_token','is_notify')
                                    ->where('is_active','1')
                                    ->where('is_ban','0')
                                    ->where('is_hold','0')
                                    ->whereIn('id',$notifyData['receiver_id'])
                                    ->get();
         }

         if($receiverData->toArray()){
            foreach($receiverData as $key => $value){
               array_push($storeData,[
                    'sender_id'   => $notifyData['sender_id'],
                    'receiver_id' => $value->receiver_id,
                    'title'       => $notifyData['title'],
                    'body'        => $notifyData['body'],
                    'meta_data'   => serialize(['id'=>$notifyData['id'],'type'=>$notifyData['type']])
               ]);
               if($value->device_token){
                   self::android([$value->device_token],[
                        'title' => $notifyData['title'],
                        'body'  => $notifyData['body'],
                        'type'  => $notifyData['type'],
                        'key'   => $notifyData['type'],
                        'icon'  => $notifyData['icon'] ?? NULL,
                        'user_type' => $value->role_id,
                        'user_id'   => $value->receiver_id,
                   ]);
               }
            }
            if($storeData)
                \DB::table('notifications')->insert($storeData);
            
       //     $deviceTokens = array_column($receiverData->toarray(),'device_token');

            //  if($deviceTokens){
            //      self::android($deviceTokens,[
            //          'title' => $notifyData['title'],
            //          'body'  => $notifyData['body'],
            //          'type'  => $notifyData['type'],
            //          'key'   => $notifyData['type'],
            //          'icon'  => $notifyData['icon'] ?? NULL,
            //          'id'    => $notifyData['id']
            //      ]);
            //  }

        }   

    }

}

?>