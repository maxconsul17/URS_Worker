<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class RecomputeManager
{   
    private $CI;
    private $worker_model;
    private $recompute_model;

    function __construct() {
        $this->CI = & get_instance();
        $this->CI->load->model("Worker_model", "worker_model");
        $this->CI->load->model("Recompute_model", "recompute_model");

        $this->worker_model = $this->CI->worker_model;
        $this->recompute_model = $this->CI->recompute_model;
    }
    
    public function getRecomputeJob() {
        return $this->worker_model->getRecomputeJob();
    }

    public function processRecompute($recomputeJob, $worker_id){
        $this->recompute_process($recomputeJob, $worker_id);
    }

    public function recompute_process($recompute, $worker_id){

        $this->worker_model->updateRecomputeStatus($recompute->id, "ongoing");
		$user = $recompute->user;

        // Convert the string back to an associative array
        $data = [];
        $pairs = explode(', ', $recompute->formdata); 

        foreach ($pairs as $pair) {
            list($key, $value) = explode(' => ', $pair); 
            $data[trim($key)] = trim($value);
        }
        
		$deptid     	= isset($data['deptid']) ? $data['deptid'] : '';
		$employeeid 	= isset($data['employeeid']) ? $data['employeeid'] : '';
		$schedule   	= isset($data['schedule']) ? $data['schedule'] : '';
		$payrollcutoff 	= isset($data['payrollcutoff']) ? $data['payrollcutoff'] : '';
		$quarter    	= isset($data['quarter']) ? $data['quarter'] : '';
		$campus 		= isset($data['campusid']) ? $data['campusid'] : '';
		$bank	 		= isset($data['bank']) ? $data['bank'] : '';
		$fundtype	 	= isset($data['fundtype']) ? $data['fundtype'] : '';
		$sortby     	= isset($data['sorting']) ? $data['sorting'] : '';
		
		$refno = $this->recompute_model->generate_cutoff_ref_no();
		
		$dates = explode(' ',$payrollcutoff);
		if(isset($dates[0]) && isset($dates[1])){
			$sdate = $dates[0];
			$edate = $dates[1];
			$payroll_cutoff_id = $this->recompute_model->getPayrollCutoffBaseId($sdate,$edate);
		}else{
			echo 'Invalid Cutoff';
			return;
		}

		$emplist = $this->recompute_model->loadAllEmpbyDept($deptid,$employeeid,$schedule,$campus,"",$sdate,$edate,$user);

		if(sizeof($emplist) > 0){
			$data = $this->recompute_model->processPayrollSummary($emplist,$sdate,$edate,$schedule,$quarter,true,$payroll_cutoff_id);
			$departments = $this->recompute_model->showdepartment();
			$data['dept'] 	= $departments[$deptid];
			$data['deptid'] = $deptid;
			$data['employeeid'] = $employeeid;
			$data['schedule'] = $schedule;
			$data['cutoff'] = $payrollcutoff;
			$data['quarter'] = $quarter;
			$data['campus'] = $campus;
			$data['status'] = 'SAVED';
			$data['issaved'] = true;
			$data['sortby'] = $sortby;
			$data['refno'] = $refno[1];
		}else{
			echo 'No employees to recompute.';
			// return;
		}

		$data["subtotal"] = $this->recompute_model->payrollSubTotal($data["emplist"]);
		$data["total"] = $this->recompute_model->payrollGrandTotal($data["emplist"]);
        
        $this->worker_model->updateRecomputeStatus($recompute->id, "done");
    }	
}