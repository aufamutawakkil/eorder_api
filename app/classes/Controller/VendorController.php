<?php



namespace Controller;



use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;



use Models\Vendor;
use Models\VVendor;
use Models\OrderOjekHistory;
use Models\OrderServiceHistory;
use Models\OrderHistoryCancelBooking;
use Models\User;
use Models\VendorIncomeHistory;
use Models\VendorPosition;

use Tools\Res;
use Tools\Encrypt;
use Tools\PushNotif;

class VendorController {
	protected $ci;
	public function __construct(ContainerInterface $ci) {
		$this->ci = $ci;
	}

	public static function accept(Request $request, Response $response){
		$post = $request->getParsedBody();
		
		if( isset($post['vendor_id']) && isset($post['order_id']) && isset($post['type_order']) ){
			
			if( $post['type_order'] == "Ojek" ){
				DB::beginTransaction();
				$m = OrderOjekHistory::where('id',$post['order_id'])->where('status','Pending')->first();
				if( count($m) > 0 ){
					$m->vendor_id = $post['vendor_id'];
					$m->status = 'Progress';
					if($m->save()){
						$user =  User::where('id',$m->user_id)->first();
						$m['no_telp'] = $user->no_telp;
						$data = ["action"=>"accept_driver","intent"=>"move","vendor_id"=>$post['vendor_id'],"order_id"=>$post['order_id'] ];
						$push=PushNotif::pushTo("Pesanan diterima","Driver Menerima pesanan Anda",
								$user->firebase_token,$data,"background");
						if($push['success'] == 1 || $push['success'] == "1"){
							DB::commit();
							return Res::cb($response,true,"Berhasil",["order" => [ "no_telp"=>$m->no_telp ]  ]);
						}else{
							DB::rollback();
							return Res::cb($response,true,"Gagal Token " . $user->firebase_token,["order" => []]);
						}
					}
				}else{
					return Res::cb($response,false,"Order Sudah di ambil driver lain",[ 'order'=> [] ]);
				}
			}else if($post['type_order'] == "Makanan"){
				DB::beginTransaction();
				$m = OrderServiceHistory::where('id',$post['order_id'])->where('status','Pending')->first();
				if( count($m) > 0 ){
					$m->vendor_id = $post['vendor_id'];
					$m->status = 'Progress';
					if($m->save()){
						$user =  User::where('id',$m['user_id'])->first();
						$m['no_telp'] = $user['no_telp'];
						$push=PushNotif::pushTo("Pesanan diterima","Driver Menerima pesanan Anda",
						$user->firebase_token,["action"=>"accept_driver","intent"=>"move","vendor_id"=>$post['vendor_id']]);
						if($push['success'] == 1 || $push['success'] == "1"){
							DB::commit();
							return Res::cb($response,true,"Berhasil",["order" => ['no_telp'=> $user['no_telp']]  ]);
						}else{
							DB::rollback();
							return Res::cb($response,true,"Gagal Token " . $user->firebase_token,["order" => []]);
						}
					}
				}else{
					return Res::cb($response,false,"Order Sudah di ambil driver lain");
				}
			}
		}

		return Res::cb($response,false,"Request Tidak valid",['order'=>[] ]);		
	}

	public static function login( Request $request, Response $response ){
		$post = $request->getParsedBody();
		$totalOrder = 0;
		$totalPendapatan = 0;
		if ( isset($post['no_telp']) ){
			$phone = $post['no_telp'];
			$vendor = VVendor::where('no_telp',$phone)->first();
			if ($vendor->count()) {
				//update vendor firebase token
				$v = Vendor::where("no_telp",$phone)->first();
				$v->firebase_token = $post["firebase_token"];
				if($v->save()){
					//get total order
					$mT = OrderOjekHistory::where("vendor_id",$v->id)->where("status","Complete")->get();
					$totalOrder += count($mt);
					if( $vendor->type_vendor == "Ojek" ){
						$mT = OrderServiceHistory::where("vendor_id",$v->id)->where("status","Complete")->get();
						$totalOrder += count($mT);	
					}
					$vendor["total_order"] = $totalOrder;
					
					//get total pendaptan
					$mP = DB::select("select sum(income) as income from eo_vendor_income_history where vendor_id = ".$v->id);
					$vendor["total_pendapatan"] =$mP[0]->income;
					return Res::cb($response,true,"Berhasil",['vendor'=>$vendor]);	
				}
			} else {
				return Res::cb($response,false,"Nomor Telp belum terdaftar",[ 'vendor'=>[] ]);
			}

		} else {
			return Res::cb($response,false,"Nomor Telp tidak valid",[]);

		}

	}

	public static function getById( Request $request, Response $response ){
		$id = $request->getAttribute("vendor_id");
		$vendor = VVendor::where('id',$id)->first();

		if ($vendor->count()) {
			return Res::cb($response,true,"Berhasil",['vendor'=>$vendor]);
		} else {
			return Res::cb($response,false,"Vendor belum terdaftar",[ 'vendor'=>[] ]);
		}
		
	}

	public static function newestOrder( Request $req, Response $res){
			$typeVendor = $req->getAttribute("type_vendor");
			$dateNow = date('d-m-Y'); 
			try{
				if($typeVendor == 'Ojek')
		    	$barang = DB::select("select b.user_id,b.id,date_format(b.created_at,'%d-%m-%Y') as tgl,
		    		date_format(b.created_at,'%H:%i') as time,'Makanan' as type,b.status from eo_order_service_history b
		    		where  b.status = 'Pending'  
		    		and date_format(b.created_at,'%d-%m-%Y') = date_format( str_to_date('".$dateNow."','%d-%m-%Y'),'%d-%m-%Y') 
		    		order by b.id desc ");
		    	else $barang = [];


		    	$ojek = DB::select("select b.user_id,b.id,date_format(b.created_at,'%d-%m-%Y') as tgl,
		    		date_format(b.created_at,'%H:%i') as time,type_vendor as type,b.status from eo_order_ojek_history b
		    						where b.status = 'Pending' 
		    						and date_format(b.created_at,'%d-%m-%Y') = date_format( str_to_date('".$dateNow."','%d-%m-%Y'),'%d-%m-%Y') 
		    						and type_vendor = '".$typeVendor."' order by b.id desc ");
		    						
		    	$order = array_merge($barang,$ojek);
		    	if( count($order) > 0 ){
		    		$sortOrder = [];
					$dates = []; 
		    		foreach($order as $o){
			    		$sortOrder[] = $o;
			    		$dates[] = date('d-m-Y H:i:s',strtotime( $o->tgl . " " . $o->time ));
			    	}
			    	array_multisort($dates, SORT_DESC, $sortOrder);
					return Res::cb($res,true,"Berhasil",['order' => $sortOrder]);
		    	}else{
		    		return Res::cb($res,true,"Berhasil,tetapi data tidak ditemukan",[ 'order'=> [] ]); 
		    	}
		     

			}catch(Exception $e){

				return Res::cb($res,false,"Gagal, data tidak ditemukan",[ 'order'=> [] ]); 

			}

	}

	public static function cancelOrder(Request $req, Response $res){
		$userId = $req->getAttribute("user_id");
		$post = $req->getParsedBody();
		$vendorId = null;
		if( isset( $post['order_id'] ) && isset( $post['type_order'] ) ){
			if( $post['type_order'] == "Ojek" ){
				$m = OrderOjekHistory::where('id',$post['order_id'])->first();
			}else{
				$m = OrderServiceHistory::where('id',$post['order_id'])->first();
			}
			$vendorId = $m->vendor_id;
			//$m->vendor_id = null;
			$m->status = 'Cancel';

			if( $m->save() ){
				//note in history cancel
				$mC = new OrderHistoryCancelBooking;
				$mC->order_id 	= $post['order_id'];
				$mC->user_id 	= $userId;
				$mC->vendor_id 	= $post["vendor_id"];
				$mC->comment = $post['comment'];
				$mC->type_order = $post['type_order'];
				
				//send notif to vendor
				$data = [
					"order_id" => $post["order_id"],
					"action" => "order_reject",
					"intent" => "move",
					"type_order" => $post['type_order']
				];
				
				$firebseToken = Vendor::where("id",$post["vendor_id"])->first()["firebase_token"];
				$push = PushNotif::pushTo("Maaf, Pesanan telah dibatalkan","Klik untuk detail"
						,$firebseToken,$data,"background");
				if( $push["success"]=="1" || $push["success"]==1 ){
					if($mC->save()){
						return Res::cb($res,true,"Order Telah dibatalkan",['order'=>[]]);
					}else{
						return Res::cb($res,false,"Kesalahan masukan, mohon ulangi",[]);
					}	
				}else{
					return Res::cb($res,false,"Kesalahan server, ID 208",[]);
				}
			}else{
				return Res::cb($res,false,"Gagal",[]);
			}
		}else{
			return Res::cb($res,false,"Request Tidak Valid",[]);
		}
	}

	public static function history( Request $req, Response $res){
		$status_id = $req->getAttribute("status_id");
		$id = $req->getAttribute("vendor_id");

		if($status_id == 1){
	    	$status = "'Progress'";
	    }else{
	    	$status = "'Complete','Cancel'";
	    }

		try{
			//get type vendor
			$typeVendor = VVendor::where("id",$id)->first()["type_vendor"];
			if($typeVendor == "Ojek")
	    		$barang = DB::select("select b.user_id,b.id,date_format(b.created_at,'%d-%m-%Y') as tgl,date_format(b.created_at,'%H:%i') as time,'Makanan' as type,b.status from eo_order_service_history b
	    		where b.vendor_id = $id and b.status in($status)");
	    	else $barang = [];

	    	$ojek = DB::select("select b.user_id,b.id,date_format(b.created_at,'%d-%m-%Y') as tgl,date_format(b.created_at,'%H:%i') as time,type_vendor as type,b.status from eo_order_ojek_history b
	    						where b.vendor_id = $id and b.status in($status) and type_vendor='".$typeVendor."' ");

	    	$order = array_merge($barang,$ojek);

			return Res::cb($res,true,"Berhasil",['history' => $order]); 

		}catch(Exception $e){

			return Res::cb($res,false,"Gagal, data tidak ditemukan",[ 'history'=> [] ]); 

		}

	}
	
	public static function finishOrder( Request $req, Response $res){
		$post = $req->getParsedBody();
		if( isset($post["order_id"]) && isset($post["type_order"]) ){
			if( $post["type_order"] == "Ojek" ){
				$m = OrderOjekHistory::where("id",$post['order_id'])->first();
			}else{
				$m = OrderServiceHistory::where("id",$post['order_id'])->first();
			}
			
			$m->status = "Complete";
			//sebelum disimpan , pastikan kalau sudah mengirim notifikasi ke user, 
			//notifikasi kalau booking sdah selssai, dan bisa di rating
			$u = User::where("id",$m->user_id)->first();
			$data = [
				"action" => "order_finish",
				"intent" => "move",
				"vendor_id" => $m->vendor_id,
				"type_order" => $post["type_order"],
				"order_id" => $post["order_id"]
			];
			
			$push = PushNotif::pushTo("Order Telah Selesai","Terimakasih, Order Anda telah diselsaikan",
					$u->firebase_token,$data,"background");
			
			if( isset($push["success"]) && ($push["success"] == "1" || $push["success"]  == 1) ){
				if( $m->save() ){
					//update total order 
					$mVendor = Vendor::where("id",$m->vendor_id)->first();
					$mVendor->total_order = (int) $mVendor->total_order + (int) 1;
					if($mVendor->save()){
						//baca settingan typeVendor
						$vvendor = VVendor::where("id",$m->vendor_id)->first();
						if($vvendor->sistem_gaji == "BAGI HASIL"){
							$percent  = $vvendor->percent; 
							$potongan = ($m->price * $vvendor->percent) / 100;
							
							//update income vendor
							$mVendorIncomeHistory = new VendorIncomeHistory;
							$mVendorIncomeHistory->vendor_id = $m->vendor_id;
							$mVendorIncomeHistory->income  = $m->price - $potongan;
							$mVendorIncomeHistory->potongan = $potongan;
							$mVendorIncomeHistory->save();
						}		
					}
					
					
					return Res::cb($res,true,"Order telah diselesaikan",['order'=>[]]); 
				}else{
					return Res::cb($res,false,"Terjadi kesalahan pada server, save tidak bekerja",["order"=>[]] ); 
				}	
			}else{
				return Res::cb($res,false,"Terjadi kesalahan pada server",["order"=>[]]); 
			}
			
		}else{
			return Res::cb($res,false,"Request tidak valid",[]); 
		}
	}
	
	public static function updateLastPosition(Request $req, Response $res){
		$post = $req->getParsedBody();
		$m = new VendorPosition;
		$m->latitude = $post["latitude"];
		$m->longitude = $post["longitude"];
		$m->vendor_id = $post["vendor_id"];
		if( $m->save() ){
			//update position vendor
			$v = Vendor::where("id",$post["vendor_id"])->first();
			$v->last_latitude = $post["latitude"];
			$v->last_longitude = $post["longitude"];
			if($v->save()){
				return Res::cb($res,true,"Updated",['pos'=>[]]); 
			}
		}
	} 
	
	public static function getVendorPosition(Request $req, Response $res){
		$typeVendor = $req->getAttribute("type_vendor");
		$m = DB::select("select last_longitude as longitude ,last_latitude as latitude 
						from v_vendor v 
						where name = '".$typeVendor."' ");
		return Res::cb($res,true,"Berhasil",['position'=>$m]); 
	}
}