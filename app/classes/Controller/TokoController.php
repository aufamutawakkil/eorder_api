<?php
	
	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;
	
	use Tools\Res;



	class TokoController
	{
		
		protected $ci;
	
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}
		
		public static function getAll(Request $req,Response $res){
		    try{
		    	$toko = DB::select("select t.*,f.path_thumbnail,path_origin from eo_toko t left join eo_foto f on f.from_id = t.id and f.from_table = 'eo_toko' ");
				return Res::cb($res,true,"Berhasil",['toko' => $toko]); 
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]); 
			}
		}
		
	}