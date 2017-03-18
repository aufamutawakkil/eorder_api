<?php
	
	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;
	
	use Models\OrderServiceHistory;
	use Models\OrderServiceBarangHistory;

	use Tools\Res;



	class HistoryController
	{
		
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

		public static function getAllByUser(Request $req,Response $res){
			$id = $req->getAttribute('user_id');
		    try{
		    	$barang = DB::select("select b.id,b.created_at,'Makanan' as type,b.status from eo_order_service_history b
		    						where  b.user_id = $id");

		    	$ojek = DB::select("select b.id,b.created_at,'Ojek' as type,b.status from eo_order_ojek_history b
		    						where  b.user_id = $id");

		    	$history = array_merge($barang,$ojek);

				return Res::cb($res,true,"Berhasil",['history' => $history]); 
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]); 
			}
		}

		public static function getAllByStatus(Request $req,Response $res){
			$status = $req->getAttribute('status_id'); // 1 -> Pending dan Progress , 2 -> Complete dan Cancel 
			$user_id = $req->getAttribute('user_id');
		    $orderId = [];
		    $order = [];
		    if($status == 1){
		    	$status = "'Pending','Progress'";
		    }else{
		    	$status = "'Complete','Cancel'";
		    }


		    try{
		    	$barang = DB::select("select b.id,b.created_at,'Makanan' as type,b.status from eo_order_service_history b
		    						where  b.user_id = $user_id and b.status in($status) ");

		    	$ojek = DB::select("select b.id,b.created_at,type_vendor as type,b.status from eo_order_ojek_history b
		    						where  b.user_id = $user_id and b.status in($status)");

		    	$history = array_merge($barang,$ojek);
		    	$newHistory = [];
		    	foreach ($history as $v) {
		    		$newTime = explode(' ',$v->created_at)[1];
		    		$newHistory[] = [
		    							'tgl' => explode(' ',$v->created_at)[0],
		    							'time'=>  explode(':',$newTime)[0] . ":" . explode(':',$newTime)[1],
		    							'type'=> $v->type,
		    							'status'=> $v->status,
		    							'id' => $v->id 
		    						] ;
		    	}

				return Res::cb($res,true,"Berhasil",['history' => $newHistory]); 
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]); 
			}
		}

		public static function getById(Request $req,Response $res){
			$id = $req->getAttribute('id');
			$user_id = $req->getAttribute('user_id');
		    $orderId = [];
		    $order = [];
		    try{
		    	
		    	$orderQ = DB::select("
		    		select t.*,b.*,t.id as order_id,b.id as order_barang_id 
		    		from eo_order_service_history t
		    		inner join eo_order_service_barang_history b on b.order_service_history_id = t.id  
		    		where t.user_id = $user_id and t.id = $id
		    		
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
	}