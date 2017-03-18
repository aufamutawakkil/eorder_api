<?php
namespace Controller;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

use Models\Credit;
use Models\CreditSetting;
use Models\CreditHistory;
use Models\InputUser;
use Models\User;
use Models\Topup;

use Tools\Res;

class CreditController{
    
    public static function inputCode(Request $request, Response $response){
        $post = $request->getParsedBody();
        $userId = $post["user_id"];
        
        if( isset($post['code_credit']) ){
            
            //cek apakah user sudah melakukan input code 1 kali
            $userCheck = User::where("id",$post["user_id"])->first();
            if( $userCheck->is_input_code_invite == "yes" ){
                return Res::cb($response,false,"Maaf Anda diperbolehkan input kode hanya 1 kali",["credit"=>[]]);
            }
            
            //cek apakah fitur kode referral aktif
            $creditSetting = CreditSetting::where("id","1")->first();
            $onSharePayment = $creditSetting->on_share;
            $onInputCodePayment = $creditSetting->on_input_code_credit;
            if ($creditSetting->is_active == "no" ){
                 return Res::cb($response,false,"Maaf fitur ini sedang kami matikan, terimakasih",["credit"=>[]]);
            }
            
            //pemilik kode referral
            $credit = Credit::where("code_credit",strtoupper($post['code_credit']))->first();
            if( count( $credit ) > 0 ){
                //cek apakah user input kode sudah melakukan input kode ini apa belum
                //karena 1 kode referal hanya boleh digunakan 1x untuk user yang menggunakan kode referal
                $creditHistory = CreditHistory::where("user_share_id",$credit->user_id)->where("user_using_id",$post["user_id"])->first();
                if( count($creditHistory) > 0 ){
                    return Res::cb($response,false,"Kode sudah dipakai",["credit"=>[]]);
                }
                
                //cek jika yang dimasukkan adalah kode kepemilikan sendiri
                if( $credit->user_id == $post["user_id"] ){
                    return Res::cb($response,false,"Kode tidak valid",["credit"=>[]]);
                }else{
                    //cek apakah ada batasan share
                    $maxShareSetting    = $creditSetting->max_share;
                    $maxShareUser       = $credit->max_share;
                    if( ($maxShareUser + 1) > $maxShareSetting ){
                        return Res::cb($response,false,"Kode referral sudah melebihi total share",["credit"=>[]]);
                    }else{
                        //update user credit, pemilik referral dapat komisi
                        $credit->nominal = (int)$credit->nominal +  (int)$onSharePayment;
                        $credit->max_share = (int)$credit->max_share + 1; 
                        if( $credit->save() ){
                            //update user credit, yang input kode dapat komisi
                            $userInputCode = Credit::where("user_id",$post["user_id"])->first();
                            $userInputCode->nominal = (int) $userInputCode->nominal + (int)$onInputCodePayment;
                            if($userInputCode->save()){
                                //input to history untuk user yang input kode
                                $creditHistory = new CreditHistory;
                                $creditHistory->credit_id = $credit->id;    
                                $creditHistory->user_share_id = $credit->user_id;    //pemilik kode referral
                                $creditHistory->user_using_id = $post["user_id"];    //user yang input
                                $creditHistory->user_from_id = $post["user_id"];    
                                $creditHistory->nominal = $onInputCodePayment;
                                if( $creditHistory->save() ){
                                    //input to history untuk pemilik kode referral
                                    $creditHistory = new CreditHistory;
                                    $creditHistory->credit_id = $credit->id;    
                                    $creditHistory->user_share_id = $credit->user_id;    //pemilik kode referral
                                    $creditHistory->user_using_id = $post["user_id"];    //user yang input
                                    $creditHistory->user_from_id = $credit->user_id;    
                                    $creditHistory->nominal = $onSharePayment;    
                                    if( $creditHistory->save() ){
                                        //jadikan user yang input sudah pernah input kode undangan
                                        //karena hanya berlaku untuk 1 kali 
                                        $user = InputUser::where("id",$post['user_id'])->first();
                                        $user->is_input_code_invite = "yes";
                                        if($user->save()){
                                            //ubah saldo 
                                            $topup = Topup::where("user_id",$post['user_id'])->first();
                                            $topup->nominal = (float) $topup->nominal + (float) $credit->nominal;
                                            if($topup->save()){
                                                return Res::cb($response,true,"Berhasil",["credit"=>$credit]);      
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    
                }
            }
            
        }else{
            return Res::cb($response,false,"Request tidak valid",["credit"=>[]]);
        }
    }
   
    
} 

?>