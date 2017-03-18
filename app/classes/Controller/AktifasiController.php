<?php
    namespace Controller;
    
	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	
	use Models\Aktifasi;
	use Models\User;
	use Models\Credit;
	
	use Tools\Res;
	use Tools\Encrypt;
	
    class AktifasiController {
       
        protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}
		
		public static function activate(Request $request, Response $response){
		    $post = $request->getParsedBody();
		    if( isset($post['kode_aktifasi']) ){
		    	
		    	//cek apakah kode aktivasi terdaftar atau tidak
		    	$activation = Aktifasi::where('kode_aktifasi',$post['kode_aktifasi'])->first();
		    	if( count( $activation ) <= 0 ){
		    		return Res::cb($response,false,'Kode Tidak Valid',['aktifasi' => [] ]);
		    	}
		    	
		        $activation = Aktifasi::where('kode_aktifasi',$post['kode_aktifasi'])->where('is_expire','no')->first();
		      
		        if(count($activation) > 0){
		            $activation = Aktifasi::find($activation->id);
    		        $activation->is_active = 'yes';
    		        if( $activation->save() ){
    		        	if( isset($post["nomor_baru"]) ){
    		        		$u =  DB::statement("update eo_user set no_telp='".$post["nomor_baru"]."' where no_telp = ".$post['nomor_lama']);
    		        		if($u){
    		        			$credit = Credit::where("user_id",$activation->user_id)->first();
    		        			 return Res::cb($response,true,'Berhasil',['aktifasi' => []  ]);
    		        		}else{
    		        			return Res::cb($response,false,'Kesalahan Server',['aktifasi' => $credit ]);
    		        		}
    		        	}else{
    		        		 return Res::cb($response,true,'Berhasil',['aktifasi' => [] ]);
    		        	}
    		        	
    		        }else{
    		            return Res::cb($response,false,'Gagal Aktivasi',[]);
    		        }
		        }else{
		            return Res::cb($response,false,'Gagal Aktivasi, Kode Aktivasi Expired',[]);
		        }
		    }
		    
		}
		
		public static function reactivation(Request $request, Response $response){
		     $post = $request->getParsedBody();
		     
		     if(isset( $post['user_id'] )  && isset($post['phone'])  ){
		         $activation_code = Encrypt::generateActivationCode($post['phone']);
		         $activation = new Aktifasi;
		         $activation->user_id = $post['user_id'];
		         $activation->code_activation = $activation_code;
		         $activation->expire_date = date('Y-m-d H:i:s', strtotime( date('Y-m-d H:i:s') . "+3 minutes"));
		         
		         /*make all aktivation code expired*/
		         DB::statement("update eo_aktifasi set is_expire='yes' where user_id = ".$post['user_id']);
		         if( $activation->save() ){
		             
		             /*update user*/
		             $user = User::find($post['user_id']);
		             $user->activation_code = $activation_code;
		            if( $user->update() )
		                return Res::cb($response,true,SUCCESS,'Berhasil Generate Kode Aktivasi',['aktifasi' => $activation]);
		             else
		                 return Res::cb($response,true,FAILED,'Gagal Update User');
		         }else{
		             return Res::cb($response,true,FAILED,'Gagal Generate Kode Aktivasi');
		         }
		         
		     }
		     
		}
		
    }