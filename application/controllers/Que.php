<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Que extends CI_Controller {
	
	function __construct(){
		parent::__construct();
		date_default_timezone_set('Asia/Manila');
		$this->load->model("facial");
	}

	// DITO NAKALIST YUNG MGA API CALL
	public function index(){
		echo "Started";
		$token = $this->workerToken();
		$data = $this->facial->queueList($token);
		if(isset($data->que_list) && $data->que_list){
			foreach($data->que_list as $que){
				$token = $this->workerToken();
				$que_id = $que->id;
				$parameters = $que->parameters;
				$endpoint = $que->endpoint;
				$device_key = $que->device_key;
				$device_from = $que->device_from;
				$person_id = $que->person_id;
				$date_from = $que->date_from;
				$date_to = $que->date_to;
				
				$this->facial->updateQueStatus($que_id, $token);
				sleep(3);
				if($endpoint == "api/face/add"){
					$this->processFaceAdd($device_key, $person_id, $parameters, $endpoint, $que_id, $token);
				}else if($endpoint == "api/person/add"){
					$this->processPersonAdd($device_key, $person_id, $parameters, $endpoint, $que_id, $device_from, $token);
				}else if($endpoint == "api/record/list/find"){
					$this->processFacialLogs($parameters, $endpoint, $device_key, $date_from, $date_to, $que_id, $token);
				}else if($endpoint == "api/face/find"){
					$this->processFaceFind($parameters, $endpoint, $device_key, $person_id, $que_id, $token);
				}else{
					// CHANGE STATUS OF QUE TO PREVENT INFINITE LOOP
					// $this->facial->updateQueue($que_id);
				}
			}
		}
		
		$hours_allowed = array(3, 6, 9, 12, 15, 18, 20);
		$date_time = date('Y-m-d H:i:s');
		$currentHour = date('G', strtotime($date_time));
		$currentMinute = date('i', strtotime($date_time));
		if (in_array($currentHour, $hours_allowed)) {
			if($currentMinute == 0){
				$this->dailyLogs();
			}
		}

	}
	
	// NANDITO YUNG MGA METHOD NA KINOCALL BASED SA ENDPOINT NUNG API
	public function processFaceAdd($device_key, $person_id, $parameters, $endpoint, $que_id, $token){
		$count = $this->facial->existingInFacialImage($device_key, $person_id, $token);
		if($count == 0){
			$command_response = $this->facial->facialCommand($parameters, $endpoint);
			if($command_response){
				$this->facial->deleteQueue($que_id, $token);
			}else{
				$this->facial->updateQueRetryCount($que_id, $token);
			}
		}else{
			$this->facial->deleteQueue($que_id, $token);
		}

	}
	
	public function processPersonAdd($device_key, $person_id, $parameters, $endpoint, $que_id, $device_from, $token){
		$count = $this->facial->existingInFacialPerson($device_key, $person_id, $token);
		if($count == 0){
			$command_response = $this->facial->facialCommand($parameters, $endpoint);
			if($command_response){
				$this->facial->deleteQueue($que_id, $token);
				
				$person_d = $this->facial->personDetails($person_id, $token);
				$name = isset($person_d->name) ? $person_d->name : "";
				$card = isset($person_d->card) ? $person_d->card : "";
				$empid = isset($person_d->empid) ? $person_d->empid : "";
				
				$face_id = bin2hex($empid."face1");
                $this->facial->savePerson($face_id, $name, $card, $empid, $device_key, $person_id, $token);

				$payloadPerson = 'deviceKey='.$device_from.'&secret=12345678&personId='.$person_id;
                $urlPerson = 'api/face/find';

                $responseImage = $this->facial->facialCommand($payloadPerson, $urlPerson);
                if($responseImage){
                    for ($j = 0; $j < count($responseImage); $j++) {
                        $imgB64 = $responseImage[$j]->imgBase64;
                        $faceId = $responseImage[$j]->faceId;

                        sleep(1);
                        $payloadFace = 'deviceKey='.$device_key.'&secret=12345678&personId='.$person_id.'&faceId='.$faceId.'&imgBase64='.$imgB64;
                        $urlFace = 'api/face/add';
                        $transferResponse = $this->facial->facialCommand($payloadFace, $urlFace);
                        if($transferResponse){
							 // UPDATE FACIAL PERSON IMAGE STATUS
							 $this->facial->updateFacialImageStatus($person_id, $device_key, $token);
                        }else if(empty($transferResponse)){
							$this->facial->saveApiToQueList($urlFace, $payloadFace, "PENDING", 1, $device_key, $device_from, $person_id, $token);
                        }
                    } 
                }else if(empty($transferResponse)){
					$this->facial->saveApiToQueList($urlPerson, $payloadPerson, "PENDING", 1, $device_key, $device_from, $person_id, $token);
                }

			}else{
				$this->facial->updateQueRetryCount($que_id, $token);
			}
		} else {
			$this->facial->deleteQueue($que_id, $token);
		}
		
	}

	public function processFacialLogs($payloadRecord, $urlRecord, $device_key, $date_from, $date_to, $que_id, $token){
		$date_range = $this->facial->getDatesFromRange($date_from, $date_to);
		$attendance = $this->facial->facialCommand($payloadRecord, $urlRecord);
		
		if($attendance && is_array($attendance)){
			// CHANGE STATUS OF QUE
			$this->facial->deleteQueue($que_id, $token);
			$this->facial->processFacialLogs($que_id, $attendance, $token, $date_range);

		}else{
			$this->facial->updateQueRetryCount($que_id, $token);
		}

	}

	public function processFaceFind($parameters, $endpoint, $device_key, $person_id, $que_id, $token){
		$responseImage = $this->facial->facialCommand($parameters, $endpoint);
		if($responseImage){
			for ($j = 0; $j < count($responseImage); $j++) {
				$imgB64 = $responseImage[$j]->imgBase64;
				$faceId = $responseImage[$j]->faceId;

				$payloadFace = 'deviceKey='.$device_key.'&secret=12345678&personId='.$person_id.'&faceId='.$faceId.'&imgBase64='.$imgB64;
				$urlFace = 'api/face/add';
				$transferResponse = $this->facial->facialCommand($payloadFace, $urlFace);
				if($transferResponse){
					 // UPDATE FACIAL PERSON IMAGE STATUS
					 $this->facial->updateFacialImageStatus($person_id, $device_key, $token);
					// CHANGE STATUS OF QUE
					$this->facial->deleteQueue($que_id, $token);
				}else if(empty($transferResponse)){
					$this->facial->updateQueRetryCount($que_id, $token);
				}
			} 
		}else if(empty($transferResponse)){
			$this->facial->updateQueRetryCount($que_id, $token);
		}
	}
	
	public function dailyLogs(){
		sleep(40);
		$token = $this->workerToken();
		$this->facial->processDailyLogs($token);
	}

	public function workerToken(){
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => getenv("CONFIG_BASE_URL"). '/index.php/Worker_api_/worker_token',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS =>'{
				"client_secret": "UrSt0K3nW0rK3r",
				"username" : "hyperion",
				"password": "@ursWorker2024"
			}',
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			),
		));

		$response = curl_exec($curl);

		$token_d = json_decode($response);
		return isset($token_d->access_token) ? $token_d->access_token : "";
	}

}
