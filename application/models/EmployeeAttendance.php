<?php
    defined('BASEPATH') OR exit('No direct script access allowed');

    class EmployeeAttendance extends CI_Model {
    
        public function updateDTR($employeeid, $date_from, $date_to){
            $this->load->model("facial");
            $date_range = $this->facial->getDatesFromRange($date_from, $date_to);
            foreach ($date_range as $date) {
                $query = $this->db->query("SELECT * FROM employee_attendance_update WHERE employeeid = '$employeeid' AND `date` = '$date'")->num_rows();
                if($query > 0){
                    $this->db->query("UPDATE employee_attendance_update SET hasUpdate = '1' WHERE employeeid = '$employeeid' AND `date` = '$date'");
                }else{
                    $this->db->query("INSERT INTO employee_attendance_update SET hasUpdate = '1', employeeid = '$employeeid', `date` = '$date'");
                }
            }
        }
    }