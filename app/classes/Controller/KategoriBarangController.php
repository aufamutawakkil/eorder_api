<?php
	
	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;
	
	use Models\KategoriBarang;

	use Tools\Res;



	class KategoriBarangController
	{
		
		protected $ci;
	
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}
		
		public static function getAll(Request $req,Response $res){
			$id = $req->getAttribute('id');
		    try{
		    	$kategoriBarang = DB::select(" select b.*,f.path_thumbnail,f.path_origin from eo_kategori_barang b left join eo_foto f on f.from_id = b.id 
		    		and f.from_table = 'eo_kategori_barang' 
		    		where  b.visible = 'yes'  and b.toko_id = $id ");
				return Res::cb($res,true,"Berhasil",['kategoriBarang' => $kategoriBarang]); 
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]); 
			}
		}
		
	}