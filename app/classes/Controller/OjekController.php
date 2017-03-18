<?php
	namespace Controller;



	use \Psr\Http\Message\ServerRequestInterface as Request;

	use \Psr\Http\Message\ResponseInterface as Response;

	use Illuminate\Database\Capsule\Manager as DB;

	

	use Models\OrderOjekHistory;
	use Models\Topup;
	use Models\TopupHistory;
	
	use Tools\Res;
	use Tools\PushNotif;
	
	class OjekController
	{
		public static function save(Request $request, Response $response){
			$post 		= $request->getParsedBody();
			$user_id 	= $request->getAttribute("user_id");

			if( isset($post['order_id']) ){
				$vendor_id = $post['vendor_id'];
			} else $vendor_id = null;

			if( isset($post['from_lat']) && isset($post['to_lat']) && !is_null($user_id)  ){
				$m = new OrderOjekHistory;
				$m->user_id = $user_id;
				$m->from_alamat = $post['from_alamat'];
				$m->from_ket_lain = $post['from_ket_lain'];
				$m->from_lat = $post['from_lat'];
				$m->from_lng = $post['from_lng'];
				$m->to_alamat = $post['to_alamat'];
				$m->to_ket_lain = $post['to_ket_lain'];
				$m->to_lat = $post['to_lat'];
				$m->to_lng = $post['to_lng'];
				$m->status = $post['status'];
				$m->payment_method = $post['payment_method'];
				$m->vendor_id = $vendor_id;
				$m->km = $post['km'];
				$m->price = $post['price'];
				$m->type_vendor = $post['type_vendor'];
				if( $m->save() ){
					
					//jika pembayaran menggunakan saldo
					
					if( $m->payment_method == 'Saldo'  ){
						$mSaldo = Topup::where("user_id",$user_id)->first();
						$mSaldo->nominal = (float) $mSaldo->nominal - (float) $post['price'];
						if( $mSaldo->save() ){
							//catat di history
							$mSaldoHistory = new TopupHistory;
							$mSaldoHistory->nominal =  $post['price'];
							$mSaldoHistory->type = "kurang";
							$mSaldoHistory->status = "Success";
							$mSaldoHistory->user_id = $user_id;
							$mSaldoHistory->save();
						}	
					}
					
					//setelah berhasil save, maka broadcast ke semua vendor menurut type vendor
					$pushTopik = "topik_" . strtolower($m->type_vendor);
					$pushData = [
						"action"		=> "new_order",
						"type_order"	=> "Ojek",
						"intent"		=> "move",
						"type_vendor"	=> $m->type_vendor,
						"id"			=> $m->id		
					];

					$push = PushNotif::pushBroadcast("Pesanan Baru Layanan " . $m->type_vendor,
							"Pesanan Baru telah di terima, klik untuk detail",$pushTopik,$pushData);
					/////
					
					
					if( isset($push["message_id"]) ){
						return Res::cb($response,true,"Berhasil",['order'=>$m]);	
					}else{
						return Res::cb($response,false,json_encode($push),[]);
					}
					
					
				}else{
					return Res::cb($response,false,"Gagal",[]);
				}
			}else{
				return Res::cb($response,false,"Order tidak valid",[]);
			}
		}
		public static function getById(Request $request, Response $response){
			$order_id = $request->getAttribute('order_id');
			$m =  DB::select(" select *, case when vendor_id is null then 0 else vendor_id end as vendor_id from eo_order_ojek_history where id = '".$order_id."' ");
			//$m = OrderOjekHistory::where("id" ,$order_id)->first();
			return Res::cb($response,true,"Berhasil",["history"=>$m[0]]);		}
	}