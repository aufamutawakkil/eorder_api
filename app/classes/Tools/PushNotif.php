<?php
namespace Tools;

class PushNotif{
      
      public static function init(){
          define('API_ACCESS_KEY', 'AAAARcCBa5k:APA91bH2GFK5xh2nETazFF_gOAeDJaf9xbiEzJKgxU5CgL8ViWruj9LVGY0YGJsaB4le3sXKtF_11Ft59bhi3DMEXeuw8Tamzf3XE6JGVQn8wKbtuj4Sk6Uxv6aLJuCdiIkCFIXjFGd9');
      }
      
      public static function pushTo($title,$body,$token,$data=["action"=>"null"],$state='foreground'){
            // API access key from Google API's Console
           //$registrationIds = array('15310207485');
            // prep the bundle
            
            self::init();
            $msg = array(
                  'body'      => $body,
                  'title'     => $title,
                  'vibrate'   => 1,
                  'sound'     => 1,
                  'icon'      => 'ic_launcher_n'
            );
            
            if($state == "foreground"){
                  $fields = array
                  (
                        //'registration_ids'    => $registrationIds,
                        'notification' => $msg,
                        'to'           => $token,
                        'data'         => $data
                  );     
            }else{
                  $data["title"] = $title;
                  $data["body"] = $body;
                   $fields = array
                  (
                        //'registration_ids'    => $registrationIds,
                        'to'           => $token,
                        'data'         => $data
                  );    
            }
             
            $headers = array
            (
                  'Authorization: key=' . API_ACCESS_KEY,
                  'Content-Type: application/json'
            );
            
            return self::send($headers,$fields);
            
        }
      
      public static function pushBroadcast($title,$body,$topic,$data=["action"=>"null"]){
             // API access key from Google API's Console
           //$registrationIds = array('15310207485');
            // prep the bundle
            self::init();
            $msg = array
            (
                  'body'      => $body,
                  'title'     => $title,
                  'vibrate'   => 1,
                  'sound'     => 1,
                  'icon'      => "ic_launcher_n"
            );
            $fields = array
            (
                  //'registration_ids'    => $registrationIds,
                  'notification' => $msg,
                  'to'           => "/topics/" . $topic,
                  'data'         => ["messages"=>"ini pesan"]
            );
             
            $headers = array
            (
                  'Authorization: key=' . API_ACCESS_KEY,
                  'Content-Type: application/json'
            );
             
           return self::send($headers,$fields);
      }
      
      public static function send($headers,$fields){
            $ch = curl_init();
            curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
            curl_setopt( $ch,CURLOPT_POST, true );
            curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
            curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
            $result = curl_exec($ch );
            curl_close( $ch );
            
            return json_decode($result,true);
      }
  }

?>