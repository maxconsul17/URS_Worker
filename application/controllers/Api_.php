<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	require(APPPATH.'/libraries/REST_Controller.php');
	class Api_ extends REST_Controller {

		function __construct(){
	    	parent::__construct();
		}

        public function hyperion_token_post(){
			$posted_data = file_get_contents("php://input");
			$posted_data = json_decode($posted_data);
			// $posted_data = $this->input->post();
			
			if($posted_data->client_secret == "DHR1R1HKA/DfmqHadXjDSjNhoiNBnGVTIQ" && $posted_data->username == "hyperion" && $posted_data->password == "@ursHyperion2025"){
				$access_token = str_split('abcdefghijklmnopqrstuvwxyz'
		                 .'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
					.'0123456789!@#$%^&*(){}-_+[]`');  /*any characters*/

				shuffle($access_token);  /*probably optional since array_is randomized; this may be redundant*/
				$granted_token = '';
				foreach (array_rand($access_token, 80) as $k) $granted_token .= $access_token[$k];
				$expires = strtotime($this->extensions->getServerTime().' + 5 minute');
				$token_data = array(
					"access_token" => $granted_token,
					"expires_in" => $expires,
					"userid" => $this->session->userdata('username'),
					"token_type"=>"Bearer"
				);
				$this->api->saveHyperionToken($token_data);
				
				$response = $token_data;
				$this->response($response, 200);
			}else{
				$this->response("Invalid Credentials", 200);
			}
		}
    }
?>