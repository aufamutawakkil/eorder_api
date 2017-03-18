<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;

	use Models\Provinces;
	use Models\Regencies;
	use Models\Districts;
	use Models\Villages;


	class AddressController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public function getAll(Request $request, Response $response){
			$prov = DB::table('provinces')->where('id','35')->get();
			$regencies =  DB::table('regencies')->where('province_id','35')->get();
			$regencies_id = [];
			foreach ($regencies as $k => $v) {
				$regencies_id[] = $v->id;
			}

			$districts = DB::table('districts')->whereIn('regency_id',$regencies_id)->limit(100)->get();
			$districts_id = [];
			foreach ($districts as $k => $v) {
			 	$districts_id[] = $v->id;
			}

			$villages = DB::table('villages')->whereIn('district_id',$districts_id)->limit(100)->get();

			$data = [
				'provinces' => $prov,
				'regencies' => $regencies,
				'districts' => $districts,
				'villages'  => $villages
			];
			
			return Res::cb($response,true,SUCCESS,'Berhasil',$data);

		}

		

	}