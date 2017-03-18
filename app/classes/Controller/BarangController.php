<?php

	

	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;

	use Models\OrderServiceHistory;
	use Models\OrderServiceBarangHistory;
	use Models\Topup;
	use Models\TopupHistory;

	use Tools\Res;
	use Tools\PushNotif;


	class BarangController{
		
		protected $ci;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getAll(Request $req,Response $res){
			$id = $req->getAttribute('id');
		    try{
		    	$barang = DB::select("select b.*,f.path_thumbnail,f.path_origin from eo_barang b 

		    						left join eo_foto f on 

		    						f.from_id = b.id 

		    						and f.from_table = 'eo_barang' 
		    						where  b.visible = 'yes'  and b.kategori_barang_id = $id ");

				return Res::cb($res,true,"Berhasil",['barang' => $barang]); 
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]); 
			}
		}

		public static function historyByStatus(Request $req,Response $res){

			$status = $req->getAttribute('status'); // 1 -> Pending dan Progress , 2 -> Complete dan Cancel 

			$user_id = $req->getAttribute('user_id');

		    $orderId = [];

		    $order = [];

		    if($status == 1){

		    	$status = "'Pending','Progress'";

		    }else{

		    	$status = "'Complete','Cancel'";

		    }





		    try{

		    	$orderQ = DB::select("

		    		select t.*,b.*,t.id as order_id,b.id as order_barang_id 

		    		from eo_order_service_history t

		    		inner join eo_order_service_barang_history b on b.order_service_history_id = t.id  

		    		where t.user_id = $user_id and t.status in($status)

		    		

		    	");



		    	foreach ($orderQ as $o) {

		    		if(!in_array($o->order_id,$orderId)){

		    			$orderId[] =  $o->order_id;

		    			$order = $o;

		    			$orderorder_barang = (object) ['order_barang' => [] ];

		    			$order->order_barang[] = [

		    				"barang_id" => $o->barang_id,

		    				"qty" => $o->qty,

		    				"services_id" => $o->services_id,

		    				"total" => $o->total,

		    				"toko_id" => $o->toko_id,

		    			];

		    		}else{

						$order->order_barang[] = [

		    				"barang_id" => $o->barang_id,

		    				"qty" => $o->qty,

		    				"services_id" => $o->services_id,

		    				"total" => $o->total,

		    				"toko_id" => $o->toko_id,

		    			];

		    		}

		    	}

				return Res::cb($res,true,"Berhasil",['history' => $order]); 

			}catch(Exception $e){

				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]); 

			}

		}

		public static function historyById(Request $req,Response $res){
			$id = $req->getAttribute('id');
			$user_id = $req->getAttribute('user_id');
		    $orderId = [];
		    $order = [];

		    try{
		    	$orderQ = DB::select("
		    		select b.*, case when v.id is null then 0 else v.id end as vendor_id 
		    		from v_order_service_barang b
		    		left join eo_vendor v on v.id = b.vendor_id
		    		where b.user_id = $user_id and b.order_id = $id 
		    	");
				
			/*	die("
		    		select b.*, 
		    		from v_order_service_barang b
		    		inner join 
		    		where b.user_id = $user_id and b.order_id = $id 
		    	");*/
				
		    	foreach ($orderQ as $o) {
		    		if(!in_array($o->order_id,$orderId)){
		    			$orderId[] =  $o->order_id;
		    			$order = $o;
		    			//$order = (object) ['order_barang' => [] ];
		    			$order->order_barang[] = [
		    				"barang" => $o->nama_barang,
		    				"qty" => $o->qty,
		    				"total" => $o->total,
		    				"toko" => $o->nama_toko,
		    			];
		    		}else{
						$order->order_barang[] = [
		    				"barang" => $o->nama_barang,
		    				"qty" => $o->qty,
		    				"total" => $o->total,
		    				"toko" => $o->nama_toko,
		    			];
		    		}
		    	}

				return Res::cb($res,true,"Berhasil",['history' => $order]); 
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]); 
			}
		}

		public static function order(Request $req,Response $res){
			$post = $req->getParsedBody();

			$userId = $post['user_id'];
			$vendorId = $post['vendor_id'];
			$orderLat = $post['order_lat'];
			$orderLng = $post['order_lng'];
			$orderKetLain = $post['order_ket_lain'];
			$orderMethod = $post['order_method'];
			$orderStatus = $post['status'];
			$totalPrice = $post['price'];
			$priceAntar = $post['price_antar'];
			$km = $post['km'];

			//barang yang dipesan
			/*
				[
					{
						"barang_id" : "1",
						"qty"		: "2",
						"services_id" : "1",
						"total"		: "50000",
						"toko_id" : "1"
					},{
						"barang_id" : "2",
						"qty"		: "1",
						"services_id" : "1",
						"total"		: "18000",
						"toko_id" : "1"
					}
				]
			*/

			$jsonBarang  = $post['order_barang'];
			$barang = json_decode($jsonBarang,true);

		    try{
		    	$m = new OrderServiceHistory;
		    	$m->user_id = $userId;
		    	$m->vendor_id = $vendorId;
		    	$m->order_lat = $orderLat;
		    	$m->order_lng = $orderLng;
		    	$m->order_ket_lain = $orderKetLain;
		    	$m->order_method = $orderMethod;
		    	$m->status = $orderStatus;
		    	$m->price = $totalPrice;
		    	$m->price_antar = $priceAntar;
		    	$m->km = $km;
		    	
		    	if($m->save()){
		    		
		    		//jika pembayaran menggunakan saldo
					
					if( $m->order_method == 'Saldo'){
						$mSaldo = Topup::where("user_id",$userId)->first();
						$mSaldo->nominal = (float) $mSaldo->nominal - (float) $totalPrice;
						if( $mSaldo->save() ){
							//catat di history
							$mSaldoHistory = new TopupHistory;
							$mSaldoHistory->nominal =  $post['price'];
							$mSaldoHistory->type = "kurang";
							$mSaldoHistory->status = "Success";
							$mSaldoHistory->user_id = $userId;
							$mSaldoHistory->save();
						}	
					}
					
		    		foreach ($barang as $b) {
		    			$mB = new OrderServiceBarangHistory;
		    			$mB->order_service_history_id = $m->id;
		    			$mB->barang_id = $b['barang_id'];
		    			$mB->services_id = $b['services_id'];
		    			$mB->toko_id = $b['toko_id'];
		    			$mB->qty = $b['qty'];
		    			$mB->total = $b['total'];
		    			$mB->save();
		    		}
		    	}
		    	
		    	//broadcast to all vendor
		    	$data = [
		    		"action" => "new_order_barang",
		    		"intent" => "move",
		    		"order_id" => "order_id"
		    	];
		    	$push = PushNotif::pushBroadcast("Pesanan Baru","Pesanan makanan baru","topik_ojek",$data);
				if( isset($push["message_id"])){
					return Res::cb($res,true,"Berhasil",['barang' => $barang]); 
				}else{
					return Res::cb($res,false,"Gagal broadcast pesanan",[]); 
				}
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]); 
			}
		}
	}