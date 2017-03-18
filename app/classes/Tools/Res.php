<?php
	namespace Tools;

	class Res
	{
		
		public static function cb($res,$status,$message,$data){
			return $res->withJson(
				[
					'status'  	=> $status,
					'message' 	=> $message,
					'data'		=> $data
				]
			);
		}
	}