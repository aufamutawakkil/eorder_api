<?php

    namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Models\TopupHistory;
	use Models\Topup;

	use Tools\Res;
	use Tools\Encrypt;

    class SaldoController {
        protected $ci;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function topup(Request $req, Response $res){
			$post = $req->getParsedBody();

			if( isset($post['user_id']) && isset($post['bank']) && isset($post['atas_nama']) && isset($post['nominal']) ){

				$lastSaldo = $post['last_saldo'];

				//save to history
				$mTopupHistory = new TopupHistory;
				$mTopupHistory->user_id = $post['user_id'];
				$mTopupHistory->type = "tambah";
				$mTopupHistory->atas_nama = $post['atas_nama'];
				$mTopupHistory->nominal = $post['nominal'];
				$mTopupHistory->bank = $post['bank'];
				
				if($mTopupHistory->save()){
					//update topup
					//cek jika sudah ada user_id
					$u = Topup::where("user_id",$post["user_id"]);
					if( count($u) < 0 ){ //jika tidak ada
						$u = new Topup;
						$u->user_id =  $post['user_id'];
						$u->nominal = 0;
						$u->save();
					}
					return Res::cb($res,true,"Berhasil",['saldo'=>[] ]);
				}else{
					return Res::cb($res,false,"Gagal",['saldo'=>[]]);
				}
			

			}else{
				return Res::cb($res,false,"Request Anda tidak valid",[]);
			}

		}

	}