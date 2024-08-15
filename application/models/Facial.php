<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Facial extends CI_Model {

    public function getDatesFromRange($start, $end, $format = 'Y-m-d') {
        $array = array();
        $interval = new DateInterval('P1D');

        $realEnd = new DateTime($end);
        $realEnd->add($interval);

        $period = new DatePeriod(new DateTime($start), $interval, $realEnd);

        foreach($period as $date) { 
            $array[] = $date->format($format); 
        }

        return $array;
    }

    public function facialCommand($payload,$url)
    { 
        $faceServer = $this->db->faceServer;
        $urlServer = $faceServer.$url;
        $curl = curl_init();
        if($url == "api/face/add") $payload = $this->addUrlEncode($payload);
        curl_setopt_array($curl, array(
          CURLOPT_URL => $urlServer,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 4000,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => $payload,
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded'
          ),
        ));

        $response = curl_exec($curl);
        
        if($url != "api/record/list/find") $payload = "";

        // COMMENT NA MUNA TO SINCE DI ACCESSIBLE SA WORKER YUNG DATABASE
        // $trail = array(
        //     "endpoint" => $url,
        //     "parameter" => $payload,
        //     "response" => $response
        // );
        // $this->db->insert("api_result", $trail);
        
        $response = json_decode("[".$response."]");
        curl_close($curl);
        // echo "<pre>";print_r($response);die;
         if(is_array($response) || is_object($response)){
            if($url == "api/face/find"){
                $response = isset($response[0]->data)? $response[0]->data : array();
            }else if($url == "api/person/list/find"){
                // 
                $response = isset($response[0]->data)? $response[0]->data->records : 'false';
                // echo "<pre>";print_r($response);die;
            }else if($url == "api/record/list/find"){
                // 
                $response = isset($response[0]->data)? $response[0]->data->records : 'false';
                // echo "<pre>";print_r($response);die;
            }else{
                $response = isset($response[0]->success) ? $response[0]->success : array();
            }
        }else{
            return array();
        }

        return $response;
    }

    public function addUrlEncode($query_string) {
        // Parse the query string into an associative array
        parse_str($query_string, $params);
    
        // URL encode the value of imgBase64
        if (isset($params['imgBase64'])) {
            $params['imgBase64'] = $this->customRawUrlEncode($params['imgBase64']);
        }

        $encodedData = array();

        // URL encode each key-value pair
        foreach ($params as $key => $value) {
            $encodedData[] = $key . '=' . $value;
        }

        // Combine the encoded key-value pairs into a query string
        $queryString = implode('&', $encodedData);

        // Return the query string
        return $queryString;
    }

    public function customRawUrlEncode($string) {
        return str_replace('%20', '%2B', rawurlencode($string));
    }

    public function existingInFacialImage($device_key, $person_id, $token){
        $url = "http://192.168.2.32/urshris/index.php/Worker_api_/exist_facial_image";

        // CONSTRUCT PARAM
        $param_tmp = array(
            "device_key" => $device_key,
            "person_id" => $person_id
        );
        $param = json_encode($param_tmp);

        $response = $this->callURSApi($url, $token, $param);
        $face_image = json_decode($response);
        return isset($face_image->existing) ? $face_image->existing : 0;
    }

    public function existingInFacialPerson($device_key, $person_id, $token){
        $url = "http://192.168.2.32/urshris/index.php/Worker_api_/exist_facial_person";

        // CONSTRUCT PARAM
        $param_tmp = array(
            "device_key" => $device_key,
            "person_id" => $person_id
        );
        $param = json_encode($param_tmp);

        $response = $this->callURSApi($url, $token, $param);
        $facial_person = json_decode($response);
        return isset($facial_person->existing) ? $facial_person->existing : 0;
    }

    public function queueList($token){
        $url = "http://192.168.2.32/urshris/index.php/Worker_api_/queue_list";
        $response = $this->callURSApi($url, $token);
        $que = json_decode($response);
        return $que;
    }

    public function deleteQueue($que_id, $token){
        $url = "http://192.168.2.32/urshris/index.php/Worker_api_/delete_que";

        // CONSTRUCT PARAM
        $param_tmp = array(
            "que_id" => $que_id
        );
        $param = json_encode($param_tmp);

        $this->callURSApi($url, $token, $param);
    }

    public function updateQueRetryCount($que_id, $token){
        $url = "http://192.168.2.32/urshris/index.php/Worker_api_/update_que_retry";

        // CONSTRUCT PARAM
        $param_tmp = array(
            "que_id" => $que_id
        );
        $param = json_encode($param_tmp);

        $this->callURSApi($url, $token, $param);
    }
    
    public function updateQueStatus($que_id, $token){
        $url = "http://192.168.2.32/urshris/index.php/Worker_api_/update_que_status";

        // CONSTRUCT PARAM
        $param_tmp = array(
            "que_id" => $que_id
        );
        $param = json_encode($param_tmp);

        $this->callURSApi($url, $token, $param);
    }


    public function personDetails($person_id, $token){
        $url = "http://192.168.2.32/urshris/index.php/Worker_api_/person_details";

        // CONSTRUCT PARAM
        $param_tmp = array(
            "person_id" => $person_id
        );
        $param = json_encode($param_tmp);

        $response = $this->callURSApi($url, $token, $param);
        $person_details = json_decode($response);
        return $person_details;
    }

    public function savePerson($faceid, $name, $card, $empid, $serial_number, $personId, $token){
        $url = "http://192.168.2.32/urshris/index.php/Worker_api_/save_person";

        // CONSTRUCT PARAM
        $param_tmp = array(
            "faceid" => $faceid,
            "name" => $name,
            "card" => $card,
            "empid" => $empid,
            "serial_number" => $serial_number,
            "person_id" => $personId
        );
        $param = json_encode($param_tmp);

        $response = $this->callURSApi($url, $token, $param);
        $person_details = json_decode($response);
        return $person_details;
    }

    public function updateFacialImageStatus($person_id, $device_key, $token){
        $url = "http://192.168.2.32/urshris/index.php/Worker_api_/update_face_status";

        // CONSTRUCT PARAM
        $param_tmp = array(
            "person_id" => $person_id,
            "device_key" => $device_key
        );
        $param = json_encode($param_tmp);

        $this->callURSApi($url, $token, $param);
    }

    public function saveApiToQueList($endpoint, $parameters, $status, $priority, $device_key, $device_from, $person_id, $token){
        $url = "http://192.168.2.32/urshris/index.php/Worker_api_/save_api_que";

        // CONSTRUCT PARAM
        $param_tmp = array(
            "endpoint" => $endpoint,
            "parameters" => $parameters,
            "status" => $status,
            "priority" => $priority,
            "device_key" => $device_key,
            "device_from" => $device_from,
            "person_id" => $person_id
        );
        $param = json_encode($param_tmp);

        $this->callURSApi($url, $token, $param);
    }

    public function processFacialLogs($que_id, $attendance, $token){
        $url = "http://192.168.2.32/urshris/index.php/Worker_api_/process_facial_logs";

        // CONSTRUCT PARAM
        $param_tmp = array(
            "attendance" => $attendance,
            "que_id" => $que_id
        );
        $param = json_encode($param_tmp);

        $this->callURSApi($url, $token, $param);
    }

    public function processDailyLogs($token){
        $url = "http://192.168.2.32/urshris/index.php/Worker_api_/process_daily_logs";
        $this->callURSApi($url, $token);
    }

    public function callURSApi($url, $token, $param=""){

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $param,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$token
            ),
        ));

        $response = curl_exec($curl);
        return $response;

    }
    
}