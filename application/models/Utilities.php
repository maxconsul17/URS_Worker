<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utilities extends CI_Model {
    public function personName($id=''){
        $res = $this->db->query("SELECT name FROM facial_person WHERE personId = '$id'");
        if (isset($res->row(0)->name)) return $res->row(0)->name;
        else return "Not Found";
    }

    function displayDateRange($dfrom = "",$dto = ""){
        $date_list = array();
        $period = new DatePeriod(
            new DateTime($dfrom),
            new DateInterval('P1D'),
            new DateTime($dto." +1 day")
        );
        foreach ($period as $key => $value) {
            $date_list[$key] = array();
            $date_list[$key] = (object) $date_list[$key];
            $date_list[$key]->dte = $value->format('Y-m-d')    ;   
        }
        
        return $date_list;
    }
    
}