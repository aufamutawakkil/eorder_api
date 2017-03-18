<?php
namespace Controller;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

use Models\User;
use Models\Aktifasi;
use Models\Foto;
use Models\RateHistory;
use Models\VVendor;
use Models\InputUser;
use Models\VendorType;
use Models\Credit;
use Models\CreditSetting;

use Tools\Res;
use Tools\Encrypt;
use Tools\PushNotif;

class UserController {
	protected $ci;

	public function __construct(ContainerInterface $ci) {
		$this->ci = $ci;
	}
	
	public static function login( Request $request, Response $response ){

		$post = $request->getParsedBody();
		if ( isset($post['no_telp']) ){

			$phone = $post['no_telp'];
			$user = User::where('no_telp',$phone)->first();
			
			if( count($user) <= 0 ){
				return Res::cb($response,false,"Nomor Telp belum terdaftar",[]);
			}
			
			
			
			/*cek apakah user sudah melakukan aktifasi*/
			$a = Aktifasi::where("kode_aktifasi",$user->kode_aktifasi)->first();
			if($a->is_active == "no"){
				return Res::cb($response,false,"Nomor Belum di aktifasi, silahkan generate ulang token",[]);
			}
			
			/*cek user ini di suspend apa tidak*/
			if( $user->is_suspend == "yes" ){
				return Res::cb($response,false,"Maaf, Akun Anda tidak bisa digunakan",[]);
			}

			if ($user->count()) {
				$i = InputUser::where('no_telp',$phone)->first();
				$i->firebase_token = $post["firebase_token"];
				if( $i->save() ){
					return Res::cb($response,true,"Berhasil",['user'=>$user]);	
				}else{
					return Res::cb($response,false,"Kesalahan pada server, coba beberapa saat lagi",[]);
				}
				
			} else {
				return Res::cb($response,false,"Nomor Telp belum terdaftar",[]);
			}
		} else {
			return Res::cb($response,false,"Nomor Telp tidak valid",[]);
		}

	    return $newResponse;

	}

	public static function getbyId( Request $request, Response $response, $args ){



		if ( isset($args['id']) ){

			

			$id = $args['id'];

			$user = User::find($id); //user profil

			$pasien = User::find($id)->pasien;  //pasien



			if ($user->count()) {

				if( $pasien->count() ){

					$cb = $response->withJson( array('status' => true, 'data' => [  'profile' => $user , 'pasien' => $pasien ]) );

				}else 

					$cb = $response->withJson( array('status' => true, 'data' => ['profile' => $user]) );



			} else {

				$cb = $response->withJson( array( 'status' => false ,'message' => 'User belum terdaftar' ) );

			}

		} else {

			$cb = $response->withJson( array( 'status' => false ) );

		}



	    return $cb;

	}

	public static function refreshToken( Request $req, Response $res){
		$post = $req->getParsedBody();
		if( isset($post['token']) ){
			$m = User::where('firebase_token',$post['token'])->first();
			if( count( $m ) > 0 ){
				$m->firebase_token = $post['token'];
				if( $m->save() ){
					return Res::cb($res,true,"Token berhasil diupdate",["token"=>$post['token']]);
				}else{
					return Res::cb($res,false,"Token terdaftar tapi gagal di simpan",[]);
				}
			}else{
				return Res::cb($res,false,"Token Belum terdaftar",[]);
			}
		}else{
			return Res::cb($res,false,"Request tidak valid",[]);
		}

	}
	
	public static function giveRating( Request $req, Response $res){
		$post = $req->getParsedBody();
		if( isset( $post["vendor_id"] ) && isset( $post["user_id"] ) && isset( $post["rate"] ) ){
			$mRateHistory = new RateHistory;
			$mRateHistory->vendor_id = $post["vendor_id"];
			$mRateHistory->user_id = $post["user_id"];
			$mRateHistory->rate = $post["rate"];
			$mRateHistory->msg = $post["msg"];
			if( $mRateHistory->save() ){
				//send notification to driver
				$mVendor = VVendor::where("id",$post["vendor_id"])->first();
				$data = [
					"action" => "give_rating",
					"intent" => "move",
					'rate'	 => $mVendor->rate,
					"msg"	 => $post["msg"]
				];
			
				$push = PushNotif::pushTo("Anda telah dirating","Klik untuk detail"
						,$mVendor->firebase_token,$data,"background");
						
				return Res::cb($res,true,"Berhasil",['rate'=>[]]);
				
			}
		}else{
			return Res::cb($res,false,"Request tidak valid",[]);
		}
	}

	public static function save( Request $request, Response $response ){
		$post = $request->getParsedBody();
		if ( isset($post['no_telp']) && isset($post['name'])  ){
			$phone = $post['no_telp'];
            $name = $post['name'];
            $jenis_kelamin = $post['jenis_kelamin'];
            $foto = $post['foto'];
            $alamat = $post['alamat'];
            $firebaseToken = $post['firebase_token'];

            //untuk update data
            if( isset($post['user_id']) ){
            	$mUser = InputUser::where("id",$post["user_id"])->first();
            	$mUser->nama = $name;
            	$mUser->jenis_kelamin = $jenis_kelamin;
            	$mUser->alamat = $alamat;
            	$mUser->firebase_token = $firebaseToken;
            	if($mUser->save()){
					$mF = Foto::where("from_id",$post['user_id'])->where("from_table","eo_user")->first();
					if( count($mF) <= 0 ){
						$mF = new Foto;
					}
            		
			        $mF->from_id = $mUser->id;
			        $mF->from_table = "eo_user";
			        $mF->path_origin = "ada";
			        $mF->path_thumbnail = "ada";
			        if($mF->save()){
			        	return Res::cb($response,true,"Berhasil",["user"=>$mUser]);
			        }
            	}
            }

			$user_exists = InputUser::where('no_telp', $phone);

			if ($user_exists->count()){
				$newResponse = $response->withJson( array( 'status' => false, 'message' => 'Nomor  '.$phone.' sudah digunakan' ) );
			} else {
				$activation_code =  Encrypt::generateActivationCode($phone);

				$newUser = new InputUser;
				$newUser->nama = $name;
		        $newUser->no_telp = $phone;
		        $newUser->kode_aktifasi = $activation_code;
		        $newUser->jenis_kelamin = $jenis_kelamin;
		        $newUser->alamat = $alamat;
		        $newUser->firebase_token = $firebaseToken;

				if ($newUser->save()) {
					//input foto
			        $mF = new Foto;
			        $mF->from_id = $newUser->id;
			        $mF->from_table = "eo_user";
			        $mF->path_origin = "ada";
			        $mF->path_thumbnail = "ada";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  
			        if($mF->save()){
			        	
			        	$creditSetting = CreditSetting::where("id","1")->first();
			        	if( count($creditSetting) > 0  ){
			        		/*generate code credit*/
				        	$creditCode = strtoupper(Encrypt::generateCreditCode($newResponse->no_telp));
				        	$credit = new Credit;
				        	$credit->user_id = $newUser->id;
				        	$credit->code_credit = $creditCode;
				        	$credit->nominal = 0;
				        	$credit->max_share = 0;
				        	$credit->save();
			        	}
			        	
			        	
			        	/*input activation*/
						$activation = new Aktifasi;
						$activation->kode_aktifasi = $activation_code;
						$activation->user_id = $newUser->id;
						if( $activation->save() ){
							$activation = Aktifasi::find($activation->id);
							$activation->expire_date = date('Y-m-d H:i:s', strtotime( date($activation->created_at) . "+3 minutes")) ;
							if($activation->save()){
								//send activation code to number
								$msg = urlencode("E-Order Kode Aktivasi Anda ".$activation_code." ");
								$url="https://reguler.zenziva.net/apps/smsapi.php?userkey=631mrc&passkey=okedehsiplah&nohp=".$newUser->no_telp."&pesan=".$msg;

						         $c = curl_init();
								curl_setopt($c, CURLOPT_URL,$url);
								curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
								curl_setopt($c, CURLOPT_HEADER, 0);
								curl_setopt($c,CURLOPT_SSL_VERIFYPEER, false);
								curl_exec ($c);
								$activation['code_invite'] = $creditCode;
								return Res::cb($response,true,"Berhasil",['aktifasi'=> $activation]);
							}
						}
			        }
				} else {
					$newResponse = $response->withJson( array( 'status' => false ) );
				}
			}
		} else {
			$newResponse = $response->withJson( array( 'status' => false ) );
		}
	    return $newResponse;

	}
	
	public static function rateVendor(Request $req, Response $res){
		$post = $req->getParsedBody();
		if( isset($post["user_id"]) && isset($post["rate"]) && isset($post["vendor_id"]) ){
			$m = new RateHistory;
			$m->vendor_id = $post["vendor_id"];
			$m->user_id = $post["user_id"];
			$m->rate = $post["rate"];
			if( $m->save() ){
				return Res::cb($res,true,"Terimakasih telah merating ",[]);
			}else{
				return Res::cb($res,false,"Terdapat kesalahan pada server",[]);
			}
		}else{
			return Res::cb($res,false,"Request tidak valid",[]);
		}
	} 
	
	public static function changeNumber(Request $req, Response $res){
		$post = $req->getParsedBody();
		if($post['nomor_lama'] && $post["nomor_baru"]){
			$chekNumber = User::where("no_telp",$post["nomor_lama"])->first();
			if( count($chekNumber) > 0  ){
				$activation_code =  Encrypt::generateActivationCode($post["nomor_baru"]);
				$expire_date = date('Y-m-d H:i:s', strtotime( date('Y-m-d H:i:s') . "+3 minutes")) ;
		
				$m = new Aktifasi;
				$m->kode_aktifasi = $activation_code;
				$m->is_active = 'yes';
				$m->user_id = $chekNumber->id;
				$m->expire_date = 	$expire_date;
				$m->is_expire = "no";
				if($m->save()){
					//kirim kode aktivasi
					$msg = urlencode("E-Order Kode Aktivasi Anda ".$m->kode_aktifasi." ");
					$url="https://reguler.zenziva.net/apps/smsapi.php?userkey=631mrc&passkey=okedehsiplah&nohp=".$post["nomor_baru"]."&pesan=".$msg;
	
			        $c = curl_init();
					curl_setopt($c, CURLOPT_URL,$url);
					curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
					curl_setopt($c, CURLOPT_HEADER, 0);
					curl_setopt($c,CURLOPT_SSL_VERIFYPEER, false);
					curl_exec ($c);	
					
					return Res::cb($res,true,"Silahkan melakukan aktifasi nomor",["null"=>"null"]);
				}
			}else{
				return Res::cb($res,false,"Nomor Anda tidak terdaftar",[]);
			}
		}
	}
	
	public static function syncSetting(Request $req, Response $res){
		$m = VendorType::get();
		$sS = DB::select("select * from eo_service_setting");
		$inviteSetting = CreditSetting::get();
		if( $m->count() > 0 ){
			return Res::cb($res,true,"Berhasil",["tarif" => $m,"service_setting"=>$sS[0],"invite_frend_setting"=>$inviteSetting[0] ]);
		}else{
			return Res::cb($res,false,"Data Tidak ditemukan",[]);	
		}
		
	}
	

}