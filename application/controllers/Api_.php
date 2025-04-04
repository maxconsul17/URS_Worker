<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	require(APPPATH.'/libraries/REST_Controller.php');
	class Api_ extends REST_Controller {

		function __construct(){
	    	parent::__construct();
		}

        public function hyperion_dtr_report_post(){
			$posted_data = file_get_contents("php://input");
			$posted_data = json_decode($posted_data);
			// $posted_data = $this->input->post();
			
			if($posted_data->client_secret == "URSWORKERDHR1R1HKA/DfmqHadXjDSjNhoiNBnGVTIQ" && $posted_data->username == "hyperion" && $posted_data->password == "@ursHyperion2025"){
				$data = $posted_data->data;
				$this->db->insert("report_list", $data->report_details);
				$rep_id = $this->db->insert_id();
				foreach($data->employee_list as $employeeid => $employee_data):
					foreach($employee_data as $key => $employee):
						$this->db->query("DELETE FROM employee WHERE employeeid = '{$employeeid}'");
						$this->db->insert("employee", $employee);
					endforeach;
				endforeach;
				$this->db->query("DELETE FROM employee_attendance_teaching");
				$this->db->query("DELETE FROM employee_attendance_nonteaching");
				foreach($data->attendance_teaching as $employeeid => $logsArr):
					foreach($logsArr as $key => $logs):
						$this->db->insert("employee_attendance_teaching", $logs);
					endforeach;
				endforeach;

				foreach($data->attendance_nonteaching as $employeeid => $logsArr):
					foreach($logsArr as $key => $logs):
						$this->db->insert("employee_attendance_nonteaching", $logs);
					endforeach;
				endforeach;

				foreach($data->report_data as $key => $report_data):
					$report_data->base_id = $rep_id;
					$this->db->insert("report_breakdown", $report_data);
				endforeach;
				$this->response(array("rep_id" => $rep_id), 200);
			}else{
				$this->response("Invalid Credentials", 200);
			}
		}
    }
?>