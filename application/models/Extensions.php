<?php 
/**
 * @author Max Consul
 * @copyright 2018
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Extensions extends CI_Model {

	/**
	* Query for other db data
	*
	* @return query result
	*/

	public function getLastTimesheetId(){
		$query = $this->db->query("SELECT * FROM timesheet ORDER BY timestamp DESC LIMIT 1");
		if($query->num_rows() > 0 ) return $query->row()->timeid;
		else return FALSE;
	}

	public function getLeaveRequestCode(){
		$query = $this->db->query("SELECT * FROM code_request_form")->result_array();
		$description = array();
		$data = array();
		foreach($query as $row){
			$description = explode(" ", $row['description']);
			$data[$row['code_request']] = $description[0];
		}
		return $data;
	}

	public function getCampusId(){
		$query = $this->db->query("SELECT code FROM code_campus");
		$code_campus = array();
		if($query->num_rows() > 0){
			foreach($query->result_array() as $value){
				$code_campus[$value['code']] = $value['code'];
			}
			return $code_campus;
		}
		else return false;
	}

	public function getCampusLists(){
		$data = array();
		$query = $this->db->query("SELECT * FROM code_campus");
		if($query->num_rows() > 0){
			foreach($query->result_array() as $value){
				$data[$value['code']] = $value['description'];
			}

			return $data;
		}
	}

	public function getBuildingLists(){
		$data = array();
		$query = $this->db->query("SELECT building FROM employee_schedule_history  WHERE building != '' GROUP BY building");
		if($query->num_rows() > 0){
			foreach($query->result_array() as $value){
				$data[$value['building']] = $value['building'];
			}
			return $data;
		}
	}

	// public function getTerminalLists($campus = "", $where){
	// 	$data = array();
	// 	$query = $this->db->query("SELECT terminal_name, id FROM terminal");
	// 	if($query->num_rows() > 0){
	// 		foreach($query->result_array() as $value){
	// 			$data[$value['id']] = $value['terminal_name'];
	// 		}
	// 		return $data;
	// 	}
	// }

	public function getFloorLists(){
		$data = array();
		$query = $this->db->query("SELECT floor FROM employee_schedule_history WHERE floor != '' GROUP BY floor ");
		if($query->num_rows() > 0){
			foreach($query->result_array() as $value){
				$data[$value['floor']] = $value['floor'];
			}

			return $data;
		}
	}

	public function isConsecutiveAbsent($sdate, $edate, $empid){
		$count = 0;
		$old_date = '';
		$date_diff = '';
		$query = $this->db->query("SELECT sched_date FROM `employee_attendance_detailed` WHERE sched_date BETWEEN '$sdate' AND '$edate' AND employeeid = '$empid' AND  absents != '' AND absents != 0 ")->result_array();
		if(count($query) >= 10) return true;
		else return false;
	}

	public function getEmployeeDeptHead($empid){
		$query = $this->db->query("SELECT head FROM employee a INNER JOIN code_office b ON b.`code` = a.`deptid` WHERE employeeid = '$empid' ");
		if($query->num_rows() > 0){
			if($query->row()->head != $empid){
				return $this->getEmployeeName($query->row()->head);
			}else{
				$getDivisionHead = $this->db->query("SELECT divisionhead FROM employee a INNER JOIN code_office b ON b.`code` = a.`deptid` WHERE employeeid = '$empid' ");
				if($getDivisionHead->num_rows() > 0){
					return $this->getEmployeeName($getDivisionHead->row()->divisionhead);
				}
			}
		}

	}

	public function getEmployeeName($empid){
		$query = $this->db->query("SELECT CONCAT(lname, ', ', fname , ' ', mname) AS fullname FROM employee WHERE employeeid = '$empid' ");
		if($query->num_rows() > 0) return $query->row()->fullname;
		else return false;
	}

	public function getLeaveDescription($code){
		$query = $this->db->query("SELECT b.description FROM code_request_form a INNER JOIN online_application_code b ON a.base_id = b.id WHERE code_request = '$code' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return false;
	}

	public function getLeaveDescriptionNew($code){
		$query = $this->db->query("SELECT b.description FROM code_request_form a INNER JOIN online_application_code b ON a.base_id = b.id WHERE code_request = '$code' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return "";
	}

	public function employee_name($empid, $column){
		$query = $this->db->query("SELECT $column FROM employee WHERE employeeid = '$empid' ");
		if($query->num_rows() > 0) return $query->row()->$column;
		else return false;
	}

	public function getEmployeePositionId($empid){
		$query = $this->db->query("SELECT positionid FROM employee WHERE employeeid = '$empid' ");
		if($query->num_rows() > 0) return $query->row()->positionid;
		else return;
	}

	public function getEmplistByOfficeHead($office, $teachingtype){
		$query = $this->db->query("SELECT CONCAT(lname, ' ,', fname, ' .', mname) AS fullname, employeeid FROM employee WHERE office = '$office' AND teachingtype = '$teachingtype' ");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getEmplistByCampusPrincipal($campusid, $teachingtype){
		$query = $this->db->query("SELECT CONCAT(lname, ' ,', fname, ' .', mname) AS fullname, employeeid FROM employee WHERE campusid = '$campusid' AND teachingtype = '$teachingtype' ");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function deleteZeroCutoff($empid, $no_cutoff, $code_income, $be_tag){
		$be_tag = strtolower($be_tag);
		if($no_cutoff == "0"){
			$query = $this->db->query("DELETE FROM employee_$be_tag WHERE employeeid = '$empid' AND code_$be_tag = '$code_income' ");
			if($query) return true;
			else return false;
		}
	}

	public function getZeroCutoff($empid, $no_cutoff, $code_income, $be_tag){
		$be_tag = strtolower($be_tag);
		if($no_cutoff == "0"){
			$query = $this->db->query("SELECT * FROM employee_$be_tag WHERE employeeid = '$empid' AND code_$be_tag = '$code_income' ");
			if($query) return true;
			else return false;
		}
	}

	public function checkIfOfficeHead($empid){
		$query = $this->db->query("SELECT * FROM code_office WHERE head = '$empid' OR divisionhead = '$empid' ");
		if($query->num_rows() > 0) return true;
		else return false;
	}

	public function getRemainingCutoff($dfrom, $dto){
		$query = $this->db->query("SELECT * FROM cutoff WHERE CutoffFrom > '$dfrom' AND CutoffTo >  '$dto' ");
		return $query->num_rows();
	}

	public function getRemainingCutoffForPayroll($employeeid, $dfrom, $dto){
		$query = $this->db->query("SELECT * FROM processed_employee WHERE cutoffstart = '$dfrom' AND cutoffend = '$dto' AND employeeid = '$employeeid' LIMIT 1 ");
		return $query->row()->remaining_cutoff;
	}	

	public function getEmpBank($employeeid){
		$query = $this->db->query("SELECT emp_bank FROM employee WHERE employeeid = '$employeeid' ");
		if($query->num_rows() > 0) return $query->row()->emp_bank;
		else return false;
	}

	public function getEmpBankAccountNo($employeeid){
		$query = $this->db->query("SELECT emp_accno FROM employee WHERE employeeid = '$employeeid' ");
		if($query->num_rows() > 0) return $query->row()->emp_accno;
		else return false;
	}

    public function getBankList(){
     	return $this->db->query("SELECT * FROM code_bank_account")->result_array();
    }

    public function getBankCount(){
     	return $this->db->query("SELECT * FROM code_bank_account")->num_rows();
    }

    public function getBankName($bankCode){
     	$query = $this->db->query("SELECT * FROM code_bank_account WHERE code = '$bankCode'");
     	if($query->num_rows() > 0) return $query->row()->bank_name;
     	else return "";
    }

	public function checkIfPayedPhilhealth($eid, $cutoffstart){
		$philhealth = '';
		$date=date_create($cutoffstart);
        date_sub($date,date_interval_create_from_date_string("5 days"));
        $date = date_format($date,"Y-m-d");
        $checkLastCutoff = $this->db->query("SELECT fixeddeduc FROM payroll_computed_table WHERE employeeid = '$eid' AND '$date' BETWEEN cutoffstart AND cutoffend AND status = 'PROCESSED' ");
        if($checkLastCutoff->num_rows() > 0){
            $emp_fixeddeduc = explode("/", $checkLastCutoff->row()->fixeddeduc);
            foreach($emp_fixeddeduc as $key => $value){
                $emp_deduc = explode("=", $value);
                if(in_array("PHILHEALTH", $emp_deduc)){
                    $philhealth = true;
                }
            }
        }
        if($philhealth) return '';
        else return "PHILHEALTH";
	}

	function GetYearDiffBasedOnToday($date){
		if($date != "0000-00-00"){
			$today = new DateTime("NOW");
			$dateformat = new DateTime($date);
			$diff = $dateformat->diff($today);
			return $diff->y;
		}else{
			return "0";
		}
	}

	public function getBloodDescription($code){
		$query = $this->db->query("SELECT * FROM code_blood WHERE bloodid = '$code' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return $code;
	}
	
	public function getOfficeDescription($code){
		$query = $this->db->query("SELECT * FROM code_office WHERE code = '$code' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return "No Department/College";
	}

	public function getOfficeDesc($code){
		$query = $this->db->query("SELECT * FROM code_office WHERE code = '$code' ");
		if($code){
			if($query->num_rows() > 0)return $query->row()->description;
			else return "[DELETED OFFICE]";
		}
		else{
			return "[NO OFFICE]";
		}
	}

	public function getPositionDescription($code){
		$query = $this->db->query("SELECT * FROM code_position WHERE positionid = '$code' ");
		if($query->num_rows() > 0) return GLOBALS::_e($query->row()->description);
		else return "No Position";
	}

	public function getDeparmentDescriptionReport($code=''){
		if(!$code || $code == 'null'){
			return "No Department";
		}else{
			$query = $this->db->query("SELECT * FROM code_department WHERE code = '$code' ");
			if($query->num_rows() > 0) return GLOBALS::_e($query->row()->description);
			else return $code;
		}
	}

	public function getOfficeDescriptionReport($code=''){
		if(!$code || $code == 'null'){
			return "No Office";
		}else{
			$query = $this->db->query("SELECT * FROM code_office WHERE code = '$code' ");
			if($query->num_rows() > 0) return GLOBALS::_e($query->row()->description);
			else return $code;
		}
	}

	public function getCutoffdate($date){
		$startdate = $enddate = '';
		$query = $this->db->query("SELECT * FROM payroll_cutoff_config WHERE '$date' BETWEEN DATE_FORMAT(startdate, '%Y-%m') AND DATE_FORMAT(enddate, '%Y-%m') ");
		if($query->num_rows() > 0){
			$data = $query->result_array();
			return array($data[0]['startdate'], $data[1]['enddate']);
		}
		else return false;
	}

	public function getHRHead(){
		$query = $this->db->query("SELECT CONCAT(lname, ' ,', fname , ' ,', mname) AS fullname FROM employee WHERE positionid = '99' ");
		if($query->num_rows() > 0) return $query->row()->fullname;
		else return false;
	}

	public function getEmployeeOtherIncome($employeeid, $deminimiss_list){
		$where_clause = '';
		foreach($deminimiss_list as $key => $value){
			if(!$where_clause) $where_clause .= " AND (other_income = '$key' ";
			if($where_clause) $where_clause .= " OR other_income = '$key' ";
		}
		if($where_clause) $where_clause .= " ) ";
		$query = $this->db->query("SELECT SUM(monthly) as total FROM other_income WHERE employeeid = '$employeeid' $where_clause ");
		return $query->row()->total;

	}

	public function getDisciplinaryActionSetup(){
		$query = $this->db->query("SELECT * FROM code_disciplinary_action_sanction ");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getDisciplinarySanctions($code){
		$query = $this->db->query("SELECT * FROM code_disciplinary_action_offense_type WHERE code = '$code' ");
		if($query->num_rows() >0) return $query->row()->sanctions;
		else return " = 0";
	}

	public function getIncomeSetup(){
		$query = $this->db->query("SELECT * FROM payroll_income_config");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getIncomeDesc($code){
		$query = $this->db->query("SELECT * FROM payroll_income_config WHERE id = '$code' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return false;
	}

	public function getDeductionSetup(){
		$query = $this->db->query("SELECT * FROM payroll_deduction_config");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getDeductionDesc($code){
		$query = $this->db->query("SELECT * FROM payroll_deduction_config WHERE id = '$code' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return false;
	}

	public function getLoanSetup(){
		$query = $this->db->query("SELECT * FROM payroll_loan_config");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getLoanDesc($code){
		$query = $this->db->query("SELECT * FROM payroll_loan_config WHERE id = '$code' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return false;
	}

	public function getFixedDeductionSetup(){
		$array = array(
			"SSS" => "SSS",
			"PHILHEALTH" => "PHILHEALTH",
			"PAGIBIG" => "PAGIBIG FUND",
			"PERAA" => "PERAA",
		);
		return $array;
	}

	public function getFixedDeductionDesc($code){
		$query = $this->db->query("SELECT * FROM deductions WHERE code_deduction = '$code' ");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getSpecialVoucherData($type){
		$where_clause = " WHERE type = '$type' ";
		if($type == "income") $data['income'] = $this->getIncomeSetup();
		else if($type == "deduction") $data['deduction'] = $this->getDeductionSetup();
		else if($type == "loan") $data['loan'] = $this->getLoanSetup();
		else if($type == "regdeduction") $data['regdeduction'] = $this->getFixedDeductionSetup();
		else if($type != "witholdingtax"){ 
			$data['income'] = $this->getIncomeSetup();
			$data['deduction'] = $this->getDeductionSetup();
			$data['loan'] = $this->getLoanSetup();
			$data['regdeduction'] = $this->getFixedDeductionSetup();
			$where_clause = "";
		}
		$query = $this->db->query("SELECT * FROM special_voucher $where_clause");
		if($query->num_rows() > 0){
			$data['records'] = $query->result_array();
			return $data;
		}
		else return false;
	}

	public function insertSpecialVoucher($data){
		$query = $this->db->insert("special_voucher", $data);
		if($query) return true;
		else return false;
	}

	public function editSpecialVoucherData($employeeid = "", $category = "", $account = ""){
		if($category != "witholdingtax"){
			$query = $this->db->query("SELECT * FROM special_voucher a INNER JOIN employee b ON b.employeeid = a.employeeid WHERE a.employeeid = '$employeeid' AND a.type = '$category' AND a.account = '$account' ");
		}else{
			$query = $this->db->query("SELECT * FROM special_voucher a INNER JOIN employee b ON b.employeeid = a.employeeid WHERE a.employeeid = '$employeeid' AND a.type = '$category' ");
		}
		if($query->num_rows() > 0) return $query->result_array();
		return false;
	}

	public function updateVoucherData($data){
		$this->db->where('employeeid', $data['employeeid']);
		$this->db->where('type', $data['type']);
		$this->db->where('account', $data['account']);
		$this->db->set($data);
		$query = $this->db->update('special_voucher');
		if($query) return true;
		else return false;
	}

	public function deleteVoucherData($data){
		if($data['type'] != "witholdingtax"){
			$this->db->where('employeeid', $data['employeeid']);
			$this->db->where('type', $data['type']);
			$this->db->where('account', $data['account']);
			$query = $this->db->delete('special_voucher');
		}else{
			$this->db->where('employeeid', $data['employeeid']);
			$this->db->where('type', $data['type']);
			$query = $this->db->delete('special_voucher');
		}
		if($query) return true;
		else return false;
	}

	public function getActiveEmployees(){
		$query = $this->db->query("SELECT * FROM employee WHERE (dateresigned = '1970-01-01' OR dateresigned='0000-00-00' OR dateresigned IS NULL) AND isactive = 1 ");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getUsageLoginData(){
    	$q_dept = $this->db->query("SELECT COUNT(DISTINCT username) AS LOG, DATE_FORMAT(DATE(`timestamp`), '%M') AS DATE FROM login_attempts_hris WHERE STATUS = 'success' GROUP BY MONTH(DATE(`timestamp`)) LIMIT 12")->result_array();
		return $q_dept;
    }

	public function getTimeInAccuracy($empid, $timein){
        $return = array("","");
        $islate = false;
        $last_id = "";
        $sched = $this->attcompute->displaySched($empid,date("Y-m-d"));
        foreach($sched->result() as $rsched){
        	if($empid != $last_id){
	            $stime = $rsched->tardy_start;
	            if(strtotime($stime) < strtotime($timein)) $islate = true;
	            else $islate = false;
	        }

	        $last_id = $empid;
        }
        return $islate;
    }

	public function getTaxableIncome(){
		$query = $this->db->query("SELECT * FROM payroll_income_config WHERE taxable = 'withtax' ");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getAllCutoffPerYearNew($year){
		$query =$this->db->query("SELECT * FROM payroll_cutoff_config WHERE DATE_FORMAT(startdate, '%Y') = '$year' AND  DATE_FORMAT(enddate, '%Y') = '$year' GROUP BY startdate, enddate ORDER BY startdate ASC");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getAllCutoffPerYear($year){
		$query =$this->db->query("SELECT * FROM payroll_cutoff_config WHERE DATE_FORMAT(startdate, '%Y') = '$year' AND  DATE_FORMAT(enddate, '%Y') = '$year' ORDER BY startdate ASC");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getPayrollComputedData($startdate, $enddate, $campus='', $sortby='', $company=''){
		$where = "WHERE STATUS ='PROCESSED' AND cutoffstart = '$startdate' AND cutoffend = '$enddate'";
		$sortby = "";
		if($campus && $campus != 'all') $where .= " AND b.campusid =  '$campus'";
		if($company && $company != 'all') $where .= " AND b.company_campus =  '$company'";
		if($sortby == "department"){
			$orderby = 'b.office, b.lname';
		}else{
			$orderby = 'b.lname';
		}

		$utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
        }
        $where .= $utwc;

		$query =$this->db->query("SELECT CONCAT(lname, ', ', fname, ', ', mname) AS fullname ,a.* FROM payroll_computed_table a INNER JOIN employee b ON b.`employeeid` = a.`employeeid` $where ORDER BY $orderby");
		if($query->num_rows() > 0) return $query->result_array();
		else return array();
	}

	public function checkIfSystemIsRecomputing($tnt){
		$query = $this->db->query("SELECT * FROM recomputing_percentage WHERE teachingtype = '$tnt' ");
		if($query->num_rows() > 0){
			$emp_count = $query->row()->emp_count;
			$emp_total = $query->row()->emp_total;
			$success = $query->row()->success;
			$failed = $query->row()->failed;
			if(!$emp_count && !$emp_total && !$success && !$failed) return true;
			else return false;
		}
	}

	public function getSpecialVoucherDataForAlphalist(){
		$utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (deptid, '$utdept') OR FIND_IN_SET (office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND employeeid = 'nosresult'";
		$usercampus = $this->extras->getCampusUser();
		$utwc .= " AND FIND_IN_SET (campusid,'$usercampus') ";
        }
        if($utwc) $utwc = " AND employeeid IN (SELECT employeeid FROM employee WHERE 1 $utwc)";
		$query_special_voucher = $this->db->query("SELECT * FROM special_voucher WHERE 1 $utwc");
		if($query_special_voucher->num_rows() > 0) return $query_special_voucher->result_array();
		else return array();
	}

	public function getEmployeeSalary($startdate = '', $enddate = '', $employeeid = ''){
		$query_empsalary = $this->db->query("SELECT * FROM payroll_computed_table WHERE employeeid = '$employeeid' AND cutoffstart = '$startdate' AND cutoffend = '$enddate' ");
		if($query_empsalary->num_rows() > 0) return $query_empsalary->row()->salary;
		else return false;
	}

	public function getEmployeeLatestSalary($empid){
		$query_salary = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid = '$empid' ORDER BY date_effective DESC LIMIT 1 ");
		if($query_salary->num_rows() > 0) return array($query_salary->row()->monthly,$query_salary->row()->daily,$query_salary->row()->hourly,$query_salary->row()->date_effective);
		else{
			$query_salary = $this->db->query("SELECT * FROM payroll_employee_salary WHERE employeeid = '$empid' ORDER BY date_effective DESC LIMIT 1 ");
			if($query_salary->num_rows() > 0) return array($query_salary->row()->monthly,$query_salary->row()->daily,$query_salary->row()->hourly,$query_salary->row()->date_effective);
		}
	}

	public function getDepartmentDescription($deptid){
		$query_dept = $this->db->query("SELECT * FROM code_department WHERE code = '$deptid' ");
		if($query_dept->num_rows() > 0) return GLOBALS::_e($query_dept->row()->description);
		else return "No Department";
	}

	public function getServerTime(){
		$query_time = $this->db->query("SELECT CURRENT_TIMESTAMP ")->row()->CURRENT_TIMESTAMP;
		return $query_time;
	}

	public function getNotIncludedInGrosspayIncome(){
		$data = array();
		$query_income = $this->db->query("SELECT * FROM payroll_income_config WHERE grosspayNotIncluded = '0' ");
		if($query_income->num_rows() > 0){
			foreach ($query_income->result_array() as $key => $value) {
				$data[$value['id']] = $value['id'];
			}
		}
		return $data;
	}

	public function getCampusDescription($campusid, $allcampus=false, $specific=false){
		$return = $allcampus === true ? "All Campus" : $specific ? " " : "No Campus";
		$query = $this->db->query("SELECT * FROM code_campus WHERE code = ".$this->db->escape($campusid)." ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return $return;
	}

	public function getTerminalName($terminalid){
		$query = $this->db->query("SELECT * FROM terminal WHERE username = '$terminalid' ");
		if($query->num_rows() > 0){
			$campus = $query->row()->campus;
			$q_campus = $this->db->query("SELECT * FROM code_campus WHERE code = '$campus'");
			if($q_campus->num_rows() > 0) return $q_campus->row()->description;
			else return false;
		}
		else{
			return false;
		}
	}

	public function getCompanyDescription($id){
		$query = $this->db->query("SELECT * FROM campus_company WHERE campus_code = ".$this->db->escape($id)." ");
		if($query->num_rows() > 0) return $query->row()->company_description;
		else return "All Company";
	}

	public function getCompanyDescriptionReports($id){
		$query = $this->db->query("SELECT * FROM campus_company WHERE campus_code = ".$this->db->escape($id)." ");
		if($query->num_rows() > 0) return $query->row()->company_description;
		else return " ";
	}

	public function getMultipleCompany($id){
		$query = $this->db->query("SELECT company_description FROM campus_company WHERE campus_code = ".$this->db->escape($id)."")->result_array();
		if (count($query) > 0) {
			foreach ($query as $key => $val) {
				$data[$key] = $val;
			}
			return $data;
		}
		else{
			return 'No Company';
		}
	}

	public function getCompanyDescriptionAll($id){
		// $query = $this->db->query("SELECT * FROM campus_company WHERE campus_code = ".$this->db->escape($id)." ");
		// if($query->num_rows() > 0) return $query->row()->company_description;
		// else return "All Company";
		if($id){
			return $id;
		}
		else{
			return 'All Company';
		}
	}

	public function getAttendanceAdjustmentRecords($fv, $datesetfrom, $datesetto){
		$data = array();
		$where_clause = '';
		$cutoff_id = $this->getDTRCutoffId($datesetfrom, $datesetto);
		if($fv) $where_clause .= " AND b.employeeid = '$fv' ";
		$utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
        }
        $where_clause .= $utwc;
		$query_ob = $this->db->query("SELECT a.*, CONCAT(lname, ', ', fname, ', ', mname) AS fullname FROM ob_adjustment a INNER JOIN employee b ON a.`employeeid` = b.`employeeid` WHERE payroll_cutoff_id = '$cutoff_id' $where_clause ");
		if($query_ob->num_rows() > 0) $data['ob_adjustment'] = $query_ob->result_array();

		$query_leave = $this->db->query("SELECT a.*, CONCAT(lname, ', ', fname, ', ', mname) AS fullname FROM leave_adjustment a INNER JOIN employee b ON a.`employeeid` = b.`employeeid` WHERE payroll_cutoff_id = '$cutoff_id' $where_clause ");
		if($query_leave->num_rows() > 0) $data['leave_adjustment'] = $query_leave->result_array();

		$query_correction = $this->db->query("SELECT a.*, CONCAT(lname, ', ', fname, ', ', mname) AS fullname FROM correction_adjustment a INNER JOIN employee b ON a.`employeeid` = b.`employeeid` WHERE payroll_cutoff_id = '$cutoff_id' $where_clause ");
		if($query_correction->num_rows() > 0) $data['correction_adjustment'] = $query_correction->result_array();

		return $data;
	}

	public function getPayrollCutoffConfig($dfrom, $dto){
		$cutoff = explode("-", $dtr_cutoff);
		$query_date = $this->db->query("SELECT * FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.`id` = b.`baseid` WHERE CutoffFrom = '$dfrom' AND CutoffTo = '$dto' ");
		if($query_date->num_rows() > 0) return date("F d, Y", strtotime($query_date->row()->startdate))." - ".date("F d, Y", strtotime($query_date->row()->enddate));
		else return date('Y-m-d');
	}

	public function getDTRCutoffConfig($dfrom, $dto){
		$query_date = $this->db->query("SELECT * FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.`id` = b.`baseid` WHERE startdate = '$dfrom' AND enddate = '$dto' AND CutoffFrom != '0000-00-00' ");
		if($query_date->num_rows() > 0){
			$cutoffdate = (date('F Y',strtotime($query_date->row()->CutoffFrom)) == date('F Y',strtotime($query_date->row()->CutoffTo))) ? date('F d',strtotime($query_date->row()->CutoffFrom)).' -  '.date('d, Y',strtotime($query_date->row()->CutoffTo)) : date('F d',strtotime($query_date->row()->CutoffFrom)).' -  '.date('F d, Y',strtotime($query_date->row()->CutoffTo));
			return $cutoffdate;
		}
		else{ 
			return "";
		}
	}

	public function getDTRCutoffConfigPayslip($dfrom, $dto){
		$cutoff = explode("-", $dtr_cutoff);
		$query_date = $this->db->query("SELECT * FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.`id` = b.`baseid` WHERE startdate = '$dfrom' AND enddate = '$dto' ");
		if($query_date->num_rows() > 0){
			$cutoffdate = (date('F Y',strtotime($query_date->row()->CutoffFrom)) == date('F Y',strtotime($query_date->row()->CutoffTo))) ? date('F d',strtotime($query_date->row()->CutoffFrom)).' -  '.date('d, Y',strtotime($query_date->row()->CutoffTo)) : date('F d',strtotime($query_date->row()->CutoffFrom)).' -  '.date('F d, Y',strtotime($query_date->row()->CutoffTo));
			return array($query_date->row()->CutoffFrom, $query_date->row()->CutoffTo);
		}
		else{ 
			return "";
		}
	}

	public function getDeminimissIncomeKeys(){
		$data = array();
		$query_income = $this->db->query("SELECT * FROM payroll_income_config WHERE incomeType = 'deminimiss' ");
		if($query_income->num_rows() > 0){
			foreach ($query_income->result_array() as $key => $value) {
				$data[$value['id']] = $value['id'];
			}
		}
		return $data;
	}

	public function getNonDeminimissIncomeKeys(){
		$data = array();
		$query_income = $this->db->query("SELECT * FROM payroll_income_config WHERE incomeType != 'deminimiss' ");
		if($query_income->num_rows() > 0){
			foreach ($query_income->result_array() as $key => $value) {
				$data[$value['id']] = $value['id'];
			}
		}
		return $data;
	}

	public function getAllIncomeKeysAndDescription(){
		$data = array();
		$query_income = $this->db->query("SELECT * FROM payroll_income_config");
		if($query_income->num_rows() > 0){
			foreach ($query_income->result_array() as $key => $value) {
				$data[$value['id']] = $value['description'];
			}
		}
		return $data;
	}

	public function getDeductioConfignKeys(){
		$data = array();
		$query_deduction = $this->db->query("SELECT * FROM payroll_deduction_config");
		if($query_deduction->num_rows() > 0){
			foreach ($query_deduction->result_array() as $key => $value) {
				$data[$value['id']] = $value['id'];
			}
		}
		return $data;
	}
	public function monthSelection(){
		return array(
			"01" => "January",
			"02" => "February",
			"03" => "March",
			"04" => "April",
			"05" => "May",
			"06" => "June",
			"07" => "July",
			"08" => "August",
			"09" => "September",
			"10" => "October",
			"11" => "November",
			"12" => "December"
		);
	}

	public function getTotalLeaveAndHoliday($employeeid, $sdate, $edate, $tnt=""){
		$query_att;
		if($tnt == "teaching") $query_att = $this->db->query("SELECT SUM(eleave + vleave + sleave + oleave + tholiday) AS total FROM attendance_confirmed WHERE employeeid = '$employeeid' AND payroll_cutoffstart = '$sdate' AND payroll_cutoffend = '$edate' ");
		else $query_att = $this->db->query("SELECT SUM(eleave + vleave + sleave + oleave + isholiday) AS total FROM attendance_confirmed_nt WHERE employeeid = '$employeeid' AND payroll_cutoffstart = '$sdate' AND payroll_cutoffend = '$edate' ");

		if($query_att->num_rows() > 0) return $query_att->row()->total;
		else return false;
	}

	public function getDTRCutoffId($datefrom,$dateto){
		$q_dtrcutoff = $this->db->query("SELECT * FROM cutoff WHERE CutoffFrom = '$datefrom' AND CutoffTo = '$dateto' ");
		if($q_dtrcutoff->num_rows() > 0){
			$dtr_id	= $q_dtrcutoff->row()->ID;
			$q_payrollcutoff = $this->db->query("SELECT * FROM payroll_cutoff_config WHERE baseid = '$dtr_id' ");
			if($q_payrollcutoff->num_rows() > 0) return $q_payrollcutoff->row()->id;
		}
		else return false;
	}

	public function getEmployeeTeachingType($employeeid){
		$q_type = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
		if($q_type->num_rows() > 0) return Globals::_e($q_type->row()->teachingtype);
		else return false;
	}

	public function getEmployeeList(){
		$q_employeelist = $this->db->query("SELECT CONCAT(lname, ' ,', fname, ' .', mname) AS fullname, employeeid, lname, fname, mname, deptid, office, bdate, gender, campusid, mobile, cp_name, cp_mobile, teachingtype, emp_sss, emp_tin, emp_philhealth, emp_peraa, emp_pagibig, addr, mobile, landline, email, emptype, cp_address, cp_relation, positionid FROM employee WHERE employeeid = '2018-05-002' ");
		if($q_employeelist->num_rows() > 0) return $q_employeelist->result_array();
		else return false;
	}

	public function getStudentList(){
		$q_employeelist = $this->db->query("SELECT * FROM student LIMIT 100 ");
		if($q_employeelist->num_rows() > 0) return $q_employeelist->result_array();
		else return false;
	}

	public function updateEmployeeCardnumber($employeeid, $rfid){
		if($this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' AND employeecode != '$rfid' ")->num_rows()){
			$this->db->query("UPDATE employee SET employeecode = '$rfid' WHERE employeeid = '$employeeid' ");
			return true;
		}else{
			return false;
		}
	}

	public function verifyAccessToken($token){
		return $this->db->query("SELECT * FROM token_allowed WHERE access_token = '$token' ")->num_rows();
	}

	public function checkIfDeptIsBED($code){
		$q_bed = $this->db->query("SELECT * FROM code_office WHERE code = '$code' ");
		if($q_bed->num_rows() > 0) return $q_bed->row()->isBED;
		else return false;
	}

	public function checkIfCutoffNoDTR($cutoffstart, $cutoffto){
        $cutoffid = $this->db->query("SELECT ID FROM cutoff WHERE CutoffFrom = '$cutoffstart' AND CutoffTo = '$cutoffto' ")->row()->ID;
        $q_nodtr = $this->db->query("SELECT nodtr FROM payroll_cutoff_config WHERE baseid = '$cutoffid' ");
        if($q_nodtr->num_rows() > 0) return $q_nodtr->row()->nodtr;
        else return false;
	}

	public function checkIfCollegeTeaching($employeeid){
		$collegeDepartment = $this->loadCollegeDepartment();
		$collegeDepartment = "'".implode("','", $collegeDepartment). "'";
		$collegeDepartment = str_replace('\'', '', $collegeDepartment);
		$q_employee = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' AND deptid IN ($collegeDepartment)");
		if($q_employee->num_rows() > 0) return true;
		else return false;
	}

	public function loadCollegeDepartment(){
		$data = array();
		$q_dept = $this->db->query("SELECT * FROM code_department #WHERE iscollege = '1' ");
		if($q_dept->num_rows() > 0){
			foreach($q_dept->result_array() as $row){
				$data[$row['code']] = $row['code'];
			}
		}

		return $data;
	}

    public function empTeachingType($eid)
    {
        $return = "";
        $query = $this->db->query("SELECT teachingtype FROM employee WHERE employeeid='$eid'");
        if ($query->num_rows() > 0) {
            $return = $query->row()->teachingtype;
        }
        return $return;
    }	

    public function checkIfSecondApprover($idkey, $table){
    	$tbl = "";
    	if($table == "leave") $tbl = "leave_app_emplist";
    	elseif($table == "overtime") $tbl = "ot_app_emplist";
    	elseif($table == "ob") $tbl = "ob_app_emplist";
    	elseif($table == "changesched") $tbl = "change_sched_app_emplist";
    	elseif($table == "servicecredit") $tbl = "sc_app_emplist";
    	elseif($table == "useservicecredit") $tbl = "sc_app_use_emplist";
    	elseif($table == "seminar") $tbl = "seminar_app_emplist";
    	elseif($table == "substitute") $tbl = "substitute_app_emplist";
		$issecond = false;
		$q_leave = $this->db->query("SELECT * FROM $tbl WHERE id = '$idkey' ");
		if($q_leave->num_rows() > 0){
			foreach($q_leave->result_array() as $row){
				foreach($row as $value){
					if($value == "APPROVED") $issecond = true;
				}	
			}
		}

		return $issecond;
	}

	public function getCurrentColValue($idkey, $table,$col="id"){
		$tbl = "";
    	if($table == "leave") $tbl = "leave_app";
    	elseif($table == "overtime") $tbl = "ot_app";
    	elseif($table == "ob") $tbl = "ob_app_emplist";
    	elseif($table == "changesched") $tbl = "change_sched_app";
    	elseif($table == "servicecredit") $tbl = "sc_app";
    	elseif($table == "useservicecredit") $tbl = "sc_app_use";
    	elseif($table == "seminar") $tbl = "seminar_app";
    	elseif($table == "substitute") $tbl = "substitute_app";
		$status = "";
		$q = $this->db->query("SELECT $col FROM $tbl WHERE id = '$idkey' ");
		if($q->num_rows() > 0){
			foreach($q->result_array() as $row){
				$status = $row[$col];
			}
		}
		return $status;
	}

	public function getBEDDepartments(){
        $data = array();
        $records = $this->db->query("SELECT * FROM code_office WHERE isBED != '1' ")->result_array();
        foreach($records as $row) $data[] = $row["code"];

        return $data;
    }

    public function getEmployeeEmail($employeeid){
		$q_email = "";
		if(!$this->extras->findIfAdmin($employeeid)) $q_email = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' OR email = '$employeeid' ");
		else $q_email = $this->db->query("SELECT * FROM user_info WHERE username = '$employeeid' ");
		if($q_email->num_rows() > 0) return $q_email->row()->email;
		if($q_email->num_rows() > 0) return $q_email->row()->email;
		else return false;
	}

	public function generateRandomPassword($length = 10){
	    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}

	public function generateRandomPasswordNumber($length = 6){
	    $characters = '0123456789';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}

	public function forgotPassStatusKey($userid, $key, $action){
		if ($action == "insert") {
			return $this->db->query("INSERT INTO forgot_password_history (`userid`,`key`) VALUES ('$userid', '$key') ");
		}else{
			return $this->db->query("UPDATE forgot_password_history SET `status` = 'READ' WHERE `key` = '$key'");
		}
	}

    public function getEmployeeGender($employeeid){
    	$gender_arr = array(""=>"Not set yet","2"=>"MALE", "1"=>"FEMALE");
    	$gender = $this->db->query("SELECT gender FROM employee WHERE employeeid = '$employeeid' ")->row()->gender;
    	return isset($gender_arr[$gender]) ? $gender_arr[$gender] : "";
    }

    public function checkIfDeptHead($userid){
    	return $this->db->query("SELECT * FROM code_department WHERE head = '$userid' OR divisionhead = '$userid' ")->num_rows();
    }

    public function checkIfCampusPrincipal($userid){
    	// return $this->db->query("SELECT * FROM code WHERE campus_principal = '$userid' ")->num_rows();
    }

    public function getAllDepartmentUnder($userid){
    	$data = array();
    	$q_dept = $this->db->query("SELECT * FROM code_department WHERE head = '$userid' OR divisionhead = '$userid' ");
    	if($q_dept->num_rows() > 0){
    		foreach($q_dept->result_array() as $row){
    			$data[] = $row["code"];
    		}
    	}
    	return $data;
    }

    public function getAllOfficeUnder($userid){
    	$data = array();
    	$q_office = $this->db->query("SELECT * FROM code_office WHERE head = '$userid' OR divisionhead = '$userid' ");
    	if($q_office->num_rows() > 0){
    		foreach($q_office->result_array() as $row){
    			$data[] = $row["code"];
    		}
    	}
   		$q_office2 = $this->db->query("SELECT * FROM campus_office WHERE (/*hrhead='$userid' OR */dhead='$userid' OR divisionhead='$userid'/* OR phead='$userid'*/)");
   		if($q_office2->num_rows() > 0){
    		foreach($q_office2->result_array() as $row){
    			if(!in_array($row["base_code"], $data)) $data[] = $row["base_code"];
    		}
    	}
    	return $data;
    }

    public function getAllCampusUnder($userid){
    	$data = array();
    	$q_campus = $this->db->query("SELECT * FROM code WHERE campus_principal = '$userid' ");
    	if($q_campus->num_rows() > 0){
    		foreach($q_campus->result_array() as $row){
    			$data[] = $row["code"];
    		}
    	}
    	return $data;
    }

    public function getEmplistForDepartmentAttendance($where_clause, $teachingtype){
    	return $this->db->query("SELECT CONCAT(lname, ' ,', fname , ' ,', mname) AS fullname, employeeid, office, deptid FROM employee WHERE teachingtype = '$teachingtype' $where_clause ")->result_array();
    }

    public function getEmployeeFname($employeeid){
    	return $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ")->row()->fname;
    }

    public function getEmployeeMname($employeeid){
    	return $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ")->row()->mname;
    }

    public function getEmployeeLname($employeeid){
    	return $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ")->row()->lname;
    }

    public function getEmployeeDepartment($employeeid){
    	$q_dept = $this->db->query("SELECT description FROM employee a INNER JOIN code_department b ON a.`deptid` = b.`code` WHERE employeeid = '$employeeid' ");
    	if($q_dept->num_rows() > 0) return $q_dept->row()->description;
    	else return "Not assigned";
    }

    public function getEmployeeOfficeDesc($employeeid){
    	$q_office = $this->db->query("SELECT description FROM employee a INNER JOIN code_office b ON a.`office` = b.`code` WHERE employeeid = '$employeeid' ");
    	if($q_office->num_rows() > 0) return $q_office->row()->description;
    	else return "Not assigned";
    }

    public function getEmployeePositionDesc($empid){
		$query = $this->db->query("SELECT description FROM employee a INNER JOIN code_position b ON a.`positionid` = b.`positionid` WHERE employeeid = '$empid' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return;
	}

    public function getBirthdayCelebrantsToday(){
    	$datenow = date("m-d", strtotime($this->db->query("SELECT CURRENT_TIMESTAMP ")->row()->CURRENT_TIMESTAMP));
      	$q_bday = $this->db->query("SELECT * FROM employee WHERE DATE_FORMAT(bdate, '%m-%d') = '$datenow' LIMIT 5 ");
      	if($q_bday->num_rows() > 0) return $q_bday->result_array();
      	else return false;
    }

	public function sendEmailToNextApprover($approver_id){
		$email = $this->extensions->getEmployeeEmail($approver_id);

		$fullname = $this->extensions->getEmployeeName($approver_id);

		if($email && $fullname){
			$data["approver_name"] = $fullname;

			$this->load->model("email");
			$this->email->sendEmailForOnlineApplication($email, $data);
		}
		return true;
	}

    public function getAppSequenceForEmail($type=""){
    	$res = $this->db->query("SELECT dhseq,  chseq,  hhseq,  cpseq,  dpseq,  fdseq,  boseq,  pseq,  upseq FROM code_request_form WHERE code_request='$type'")->result_array();
    	return $res;
    }

    public function getCurrentCutoff($date_now){
    	$q_cutoff = $this->db->query("SELECT * FROM cutoff WHERE '$date_now' BETWEEN ConfirmFrom AND ConfirmTo ");
    	if($q_cutoff->num_rows() > 0){
    		if($q_cutoff->row()->ConfirmFrom && $q_cutoff->row()->ConfirmTo) return array($q_cutoff->row()->CutoffFrom, $q_cutoff->row()->CutoffTo);
    		else return false;
    	}else{
    		return false;
    	}
    }

    public function getSubjectDescription($id){
    	$q_sebdesc = $this->db->query("SELECT * FROM code_subj_competent_to_teach WHERE id = '$id' ");
    	if($q_sebdesc->num_rows() > 0) return Globals::_e($q_sebdesc->row()->description);
    	return "--";
    }

    public function getAimsDesc($code){
    	$q_sebdesc = $this->db->query("SELECT * FROM aims_department WHERE education_level = '$code' GROUP BY education_level ORDER BY description ASC ");
    	if($q_sebdesc->num_rows() > 0) return Globals::_e($q_sebdesc->row()->description);
    	return "--";
    }

    public function getCourseDescription($id){
    	$q_coursedesc = $this->db->query("SELECT * FROM tblCourseCategory WHERE CODE = '$id' ");
    	if($q_coursedesc->num_rows() > 0) return $q_coursedesc->row()->DESCRIPTION;
    	else return "--";
    }

     public function getCourseDescriptionByCode($code){
    	$q_coursedesc = $this->db->query("SELECT * FROM tblCourseCategory WHERE CODE = '$code' ");
    	if($q_coursedesc->num_rows() > 0) return $q_coursedesc->row()->DESCRIPTION;
    	else return "";
    }

    public function getApplicantStatusDesc($id){
    	$q_statusdesc = $this->db->query("SELECT * FROM code_applicant_status WHERE id = '$id' ");
    	if($q_statusdesc->num_rows() > 0){
    		if($q_statusdesc->row()->isrequirements == 1){
    			return 'Initial Requirements';
    		}else if($q_statusdesc->row()->isprerequirements == 1){
    			return 'Pre Requirements';
    		}else{
    			return $q_statusdesc->row()->description;
    		}
    	}
    	else return false;
    }

	public function getHolidayHalfdayTime($date, $isFirstSched = ""){
		$where_clause = "";
		if($isFirstSched) $where_clause = " AND sched_count = '$isFirstSched'" ;
		$q_holiday = $this->db->query("SELECT * FROM code_holiday_calendar WHERE '$date' BETWEEN date_from AND date_to $where_clause ");
		if($q_holiday->num_rows() > 0) return array($q_holiday->row()->fromtime,$q_holiday->row()->totime);
		else return false;
	}

	public function getDTRCutoffByPayrollCutoffID($pcutoff_id){
		$q_cutoff = $this->db->query("SELECT * FROM cutoff WHERE id = '$pcutoff_id' ");
		if($q_cutoff->num_rows() > 0){
			return array($q_cutoff->row()->CutoffFrom, $q_cutoff->row()->CutoffTo);
		}else{
			return array("", "");
		}
	}

	public function getApplicantCampus($applicantId){
		$campusid = $this->db->query("SELECT campusid FROM applicant WHERE applicantId = '$applicantId' ")->row()->campusid;
		return $this->extensions->getCampusDescription($campusid);
	}

	public function getApplicantPosition($applicantId){
    	$positionid = $positiondesc = "";
    	$q_position = $this->db->query("SELECT * FROM applicant WHERE applicantId = '$applicantId' ");
    	if($q_position->num_rows() > 0) $positionid = $q_position->row()->positionApplied;
    
    	$q_posdesc = $this->db->query("SELECT * FROM code_position WHERE positionid = '$positionid' ");
    	if($q_posdesc->num_rows() > 0) $positiondesc = $q_posdesc->row()->description;

    	return $positiondesc;
    }  

	public function getEmployeeDeptid($employeeid){
		$q_dept = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
		if($q_dept->num_rows() > 0) return $q_dept->row()->deptid;
		else return false;
	}

	public function getEmployeeOffice($employeeid){
		$q_dept = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
		if($q_dept->num_rows() > 0) return $q_dept->row()->office;
		else return false;
	}

	public function getHolidayTypeRate($holiday_type, $teachingtype){
		$q_holiday = $this->db->query("SELECT * FROM code_holiday_type WHERE holiday_type = '$holiday_type'");
		if($q_holiday->num_rows() > 0){
			if($teachingtype == "teaching") return $q_holiday->row()->t_rate;
			else return $q_holiday->row()->nt_rate;
		}
	}

	public function getSuspensionTypeRate($holiday_type, $teachingtype){
		$q_holiday = $this->db->query("SELECT * FROM code_holiday_type WHERE holiday_type = '$holiday_type'");
		if($q_holiday->num_rows() > 0){
			if($teachingtype == "teaching") return $q_holiday->row()->nat_rate;
			else return $q_holiday->row()->nant_rate;
		}
	}

	public function employeeBirthdate($employeeid){
		$q_bday = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
		if($q_bday->num_rows() > 0) return $q_bday->row()->bdate;
		else return false;
	}

	public function employeeDateEmployed($employeeid){
		$q_employed = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
		if($q_employed->num_rows() > 0) return $q_employed->row()->dateemployed;
		else return false;
	}

	public function generateEmployeeEmail($fname, $mname, $lname){

		/*replace enye with n for gsuite validation*/
		$fname = str_replace("Ñ", "N", $fname);
		$lname = str_replace("Ñ", "N", $lname);
		$mname = str_replace("Ñ", "N", $mname);
		$fname = str_replace("ñ", "n", $fname);
		$lname = str_replace("ñ", "n", $lname);
		$mname = str_replace("ñ", "n", $mname);

		$fname = str_replace(" ", "", $fname);
		$lname = str_replace(" ", "", $lname);
		$mname = str_replace(" ", "", $mname);
		$email = isset($fname[0]) ? strtolower($fname[0]) : '';
		$email .= isset($mname[0]) ? strtolower($mname[0]) : '';
		$email .= isset($lname) ? strtolower($lname)."@urs.edu.ph" : '';
		/*if($_SERVER["HTTP_HOST"] != "192.168.2.32"){
			$client = Api_helper::getClientToken();
	        $service = new Google_Service_Directory($client);
	            
	        // Build the User Object
	        $user = new Google_Service_Directory_User();
	      	try {
	        	$email = isset($lname) ? strtolower($lname) : '';
				$email .= isset($fname) ? strtolower($fname) : '';
				$email .= isset($mname[0]) ? strtolower($mname[0])."@fatima.edu.ph" : '';
			} catch (\Google_Service_Exception $e) {
				
			}
		}*/
		return $email;
	}

	public function getDocumentSetup(){
		$q_document = $this->db->query("SELECT * FROM code_documents");
		return $q_document->result_array();
	}

	public function getDocumentDescription($code){
		$q_doc = $this->db->query("SELECT * FROM code_documents WHERE code = '$code' ");
		if($q_doc->num_rows() > 0) return $q_doc->row()->description;
		else return false;
	}

	public function getDPA($userid){
		$q_dpa = $this->db->query("SELECT * FROM employee where employeeid = '$userid'");
		if($q_dpa->num_rows() > 0) return $q_dpa->row()->dpa;
		else return false;
	}

	public function acceptDPA($userid){
		return $this->db->query("UPDATE employee set dpa = '1' where employeeid = '$userid'");
	}

	public function loadTeachingEmployee(){
		return $this->db->query("SELECT CONCAT(lname, ' ,', fname, ' .', mname) AS fullname, employeeid FROM employee WHERE teachingtype = 'teaching'")->result_array();
	}

	public function loadTeachingEmployeeSelect2($where = '', $lc = ''){
		$utwc = '';
		$utdept = $this->session->userdata("department");
		$utoffice = $this->session->userdata("office");
		if($this->session->userdata("usertype") == "ADMIN"){
			if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
			if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
			if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
		}
		return $this->db->query("SELECT CONCAT(a.employeeid, ' - ', a.lname, ', ', a.fname, ' ', a.mname) AS fullname, a.employeeid FROM employee a $where $utwc order by lname, fname, mname $lc")->result_array();
	}

	public function loadTeachingEmployeeSelect2AccountType($where = '', $lc = '', $accounttype = '', $usertype= ''){
		$wc = $utwc = '';
		$utdept = $this->session->userdata("department");
		$utoffice = $this->session->userdata("office");
		if($accounttype && $accounttype != 'undefined') $wc .= " AND b.type = '$accounttype' ";
		if($usertype && $usertype != 'undefined') $wc .= " AND c.code = '$usertype' ";
		if($this->session->userdata("usertype") == "ADMIN"){
			if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
			if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
			if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
		}
		$wc .= $utwc;
		return $this->db->query("SELECT CONCAT(a.employeeid, ' - ', a.lname, ', ', a.fname, ' ', a.mname) AS fullname, a.employeeid FROM employee a INNER JOIN user_info b ON a.employeeid = b.username LEFT JOIN user_type c ON b.user_type = c.code $where $wc order by lname, fname, mname $lc")->result_array();
	}

	public function getCompany($empid){
		return $this->db->query("SELECT * FROM employee WHERE employeeid = '$empid'")->row()->company_campus;
	}

	public function checkIfDepartmentPrincipal($employeeid){
		$positionid = 0;
		// $query = $this->db->query("SELECT * FROM code_department2 WHERE dept_principal = '$employeeid' ");
		
		// return ($query->num_rows() > 0) ? true : false;
	}

	public function getPayrollTypeDesc($code){
        $q_type = $this->db->query("SELECT * FROM rank_code_type");
        if($q_type->num_rows() > 0) return $q_type->row()->description;
        else return false;
    }

    public function checkUserForgotPass($name){
		$q_email = $this->db->query("SELECT b.id, a.email,b.email AS emailAdmin, a.personal_email, b.username, b.type FROM user_info b LEFT JOIN employee a ON b.username = a.employeeid WHERE b.email = '$name' OR a.email = '$name' OR a.personal_email = '$name' OR b.username = '$name' LIMIT 1");
		$data = array();
		if($q_email->num_rows() > 0){
			$data["userid"] = $q_email->row()->id;
			if ($q_email->row()->type == "ADMIN" || $q_email->row()->type == "SUPER ADMIN") {

				$data["email"] = $q_email->row()->emailAdmin;
				return $data;
			}else{
				$data["email"] = $q_email->row()->email;
				return $data;
			}	
		} 
		else{
			return false;
		} 
	}

	public function checkUserForgotKey($key){
		$q_email = $this->db->query("SELECT * FROM forgot_password_history WHERE `key` = '$key'");
		if($q_email->row()->status == "SENT"){
			return $q_email->row()->userid;
		} 
		else{
			return false;
		} 
	}

	public function getMonthDescription($code){
		$array = array(
			"January"  => "01",
			"February" => "02",
			"March"	   => "03",
			"April"    => "04",
			"May"      => "05",
			"June"     => "06",
			"July"     => "07",
			"August"   => "08",
			"September"=> "09",
			"October"  => "10",
			"November" => "11",
			"December" => "12"
		);
		$key = array_search($code, $array);
		return $key;
	}

	public function getRelationDesc($relationid){
		$q = $this->db->query("SELECT * FROM code_relationship WHERE relationshipid='$relationid'");
		if($q->num_rows() > 0) return Globals::_e($q->row()->description);
		else return " ";
	}

	public function getEmployeeDeparment($employeeid){
    	$q_dept = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
    	if($q_dept->num_rows() > 0) return $q_dept->row()->deptid;
    	else return false;
    }

    public function getPayrollBank($employeeid="",$dfrom="",$dto=""){
    	$query = $this->db->query("SELECT * FROM payroll_computed_table a LEFT JOIN code_bank_account b ON a.bank = b.code WHERE employeeid = '$employeeid' AND cutoffstart = '$dfrom' AND cutoffend = '$dto'");
    	if($query->num_rows() > 0) return $query->row()->bank_name;
    	else return false;
    }

    public function greetingsMessage(){
    	return $this->db->query("SELECT * FROM announcement WHERE type = 'birthday' AND employeeid = ''");
    }

    public function agreementMessage($campusid, $company_campus, $officeid, $deptid){
    	$username = $this->session->userdata('username');
    	$today = date('Y-m-d');
    	return $this->db->query("SELECT a.* FROM announcement a INNER JOIN announcement_dept b ON a.id = b.base_id WHERE a.announcement = 'agreement' AND (a.campus = '$campusid' OR a.campus = 'All') AND (a.company_campus = ".$this->db->escape($company_campus)." OR a.company_campus = 'all') AND (a.officeid = '$officeid' OR a.officeid = 'alloffice') AND (b.deptid = '$deptid' OR b.deptid = 'alldept') AND a.posted_until >= '$today' AND a.popup = 'YES' AND a.id NOT IN (SELECT announcement_id FROM agreement_logs WHERE username = '$username') ORDER BY a.posted_until ASC");
    }

    public function getEmployee201Files($table, $base_id){
		$filename = $content = $mime = $dbname = '';
		$dbname = $this->db->database_files; 
        // if($_SERVER["HTTP_HOST"] == "192.168.2.97") $dbname = "HrisFiles";
        // else if($_SERVER["HTTP_HOST"] == "hris.fatima.edu.ph" && strpos($_SERVER["REQUEST_URI"], 'training') !== false) $dbname = "TRNGHrisFiles";
        // else if($_SERVER["HTTP_HOST"] == "hris.fatima.edu.ph" && strpos($_SERVER["REQUEST_URI"], 'hris') !== false) $dbname = "HrisFiles"; 
		$query = $this->db->query("SELECT * FROM $dbname.employee201_files WHERE table_name = '$table' AND base_id = '$base_id' ORDER BY `id` DESC");
		if($query->num_rows() > 0){
			$filename = $query->row()->filename;
			$content = $query->row()->content;
			$mime = $query->row()->mime;
		}
		else{
			$dbname = 'no data gather';
		}
		return array($filename, $content, $mime);
	}

	public function getTableFiles($table, $base_id){
		$filename = $content = $mime = $dbname = '';
        $dbname = $this->db->database_files;  
		$query = $this->db->query("SELECT * FROM $dbname.table_files WHERE table_name = '$table' AND base_id = '$base_id' ORDER BY ID DESC LIMIT 1");
		if($query->num_rows() > 0){
			$filename = $query->row()->filename;
			$content = $query->row()->content;
			$mime = $query->row()->mime;
		}
		return array($filename, $content, $mime);
	}

	public function gethrisFiles($table, $base_id){
		$filename = $content = $mime = $dbname = '';
		$dbname = $this->db->database_files;
        
		$query = $this->db->query("SELECT * FROM $dbname.$table WHERE base_id = '$base_id'");
		if($query->num_rows() > 0){
			$filename = $query->row()->filename;
			$content = $query->row()->content;
			$mime = $query->row()->mime;
		}
		return array($filename, $content, $mime);
	}

	public function deleteTableFiles($table, $base_id){
		$dbname = $this->db->database_files;
          
		return $this->db->query("DELETE FROM $dbname.table_files WHERE table_name = '$table' AND base_id = '$base_id'");
		
	}


	public function campusOfficeHead($code, $campus, $head=""){
		$q_campus = $this->db->query("SELECT $head FROM campus_office WHERE base_code = '$code' AND campus = '$campus'");
		if($q_campus->num_rows() > 0) return $q_campus->row()->$head;
		else return false;
	}

	public function getOfficeByManagementID($id){
		$q_office = $this->db->query("SELECT * FROM code_office WHERE managementid = '$id'");
		if($q_office->num_rows() > 0) return $q_office->row()->code;
		else return false;
	}

	public function isNursingDepartment($eid){
		return $this->db->query("SELECT * FROM employee WHERE (deptid = '14' OR office = '122') AND employeeid = '$eid'")->num_rows();
	}

	public function isNursingExcluded($eid){
		return $this->db->query("SELECT * FROM employee WHERE (deptid = '14' OR office = '122') AND nursing_excluded = '1' AND employeeid = '$eid'")->num_rows();
	}

	public function isMedicineDepartment($eid){
		return $this->db->query("SELECT * FROM employee WHERE (deptid = '13' OR office = '99') AND employeeid = '$eid'")->num_rows();
	}

	public function getScheduleDescription($schedid){
		$query = $this->db->query("SELECT description FROM code_schedule WHERE schedid = '$schedid' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return false;
	}

	public function isHoliday($employeeid, $date, $deptid){
		$holiday = $this->attcompute->isHolidayNew($employeeid,$date,$deptid ); 
		if($holiday){
			$holidayInfo = $this->attcompute->holidayInfo($date);
			return $holidayInfo["description"];
		}else{
			return false;
		}
	}

	public function getAdminName($userid){
		$query = $this->db->query("SELECT CONCAT(lastname, ', ', firstname , ' ', middlename) AS fullname FROM user_info WHERE username = '$userid' ");
		if($query->num_rows() > 0) return $query->row()->fullname;
		else return false;
	}

	public function getEemployeeCurrentData($employeeid, $column, $dateformat=''){
    	$query = $this->db->query("SELECT $column FROM employee WHERE employeeid = '$employeeid' ");
    	if($query->num_rows() > 0){
    		if($dateformat){
    			if($query->row()->$column != '' && $query->row()->$column != '0000-00-00' && $query->row()->$column != '1970-01-01') return date($dateformat, strtotime($query->row()->$column));
    			else return false;
    		}else{
    			return $query->row()->$column;
    		}
    	}
    	else return false;
    }

    public function getApproverList($codes, $campusid){
    	if(is_array($codes)) $codes = implode(',', $codes);
    	$query = $this->db->query("SELECT divisionhead,hrhead,phead, base_code, dhead FROM campus_office WHERE 1 AND campus = '$campusid' ");
    	if($query->num_rows() > 0){
    		return $query->result_array();
    	}else{
    		return false;
    	}
    }

    public function getOBtypeDesc($code){
    	$query = $this->db->query("SELECT * FROM ob_type_list WHERE status = '1' AND id = '$code'");
    	if($query->num_rows() > 0) return $query->row()->type;
    	else return false;
    }

    public function isOBWFH($id){
    	return $this->db->query("SELECT * FROM ob_type_list WHERE id = '$id' AND iswfh = '1' ")->num_rows();
    }

    public function isHolidaySuspension($holiday_id){
    	return $this->db->query("SELECT * FROM code_holiday_type WHERE holiday_type = '$holiday_id' AND is_suspension = '1'")->num_rows();
    }

    public function hasGsuiteAccount($employeeid, $email){
    	return $this->db->query("SELECT * FROM gsuite_accounts WHERE employeeid = '$employeeid' AND email = '$email' ")->num_rows();
    }

    public function is_employee_exists($eid){
    	return $this->db->query("SELECT * FROM employee WHERE employeeid = '$eid'")->num_rows();
    }

    public function getCampusDescriptionByAimsdept($aimsdept="", $employeeid=""){
		$q_sched = $this->db->query("SELECT campus FROM employee_schedule_history WHERE aimsdept = '$aimsdept' AND employeeid = '$employeeid'");
		if($q_sched->num_rows() > 0){
			return $this->getCampusDescription($q_sched->row()->campus);
		}else{
			return false;
		}
	}

	public function getLatestDateActive($employeeid, $date){
		// $query = $this->db->query("SELECT dateactive FROM employee_schedule_history WHERE employeeid = '$employeeid' AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) ORDER BY dateactive DESC LIMIT 1");
		$query = $this->db->query("SELECT dateactive FROM employee_schedule_history WHERE employeeid = '$employeeid' AND DATE(dateactive) <= DATE('$date') ORDER BY dateactive DESC LIMIT 1");
    	if($query->num_rows() > 0) return $query->row()->dateactive;
    	else return false;
    }

    public function is_teaching_related($employeeid){
    	return $this->db->query("SELECT * FROM employee WHERE teachingtype = 'nonteaching' AND trelated = '1' AND employeeid = '$employeeid'")->num_rows();
    }

    public function is_employee_resigned($date, $employeeid){
    	return $this->db->query("SELECT * FROM employee WHERE dateresigned2 <= '$date' AND dateresigned2 != '0000-00-00' AND employeeid = '$employeeid'")->num_rows();
    }

    public function getAccountNameNew($userid){
		$query = $this->db->query("SELECT CONCAT(lname, ', ', fname , ' ', mname) AS fullname FROM employee WHERE employeeid = '$userid' ");
		if($query->num_rows() > 0) return $query->row()->fullname;
		else return false;
	}

    public function getAccountName($userid){
		$query = $this->db->query("SELECT CONCAT(lastname, ', ', firstname , ' ', middlename) AS fullname FROM user_info WHERE username = '$userid' ");
		if($query->num_rows() > 0) return $query->row()->fullname;
		else return false;
	}

	public function getClearanceID($separation){
		$query = $this->db->query("SELECT clearance_id FROM separation_data WHERE id = '$separation'");
		if($query->num_rows() > 0) return $query->row()->clearance_id;
		else return false;
	}

	public function getClearanceForms($clearance_id){
		$ef = $af = 0;
		$query = $this->db->query("SELECT * FROM clearance_type WHERE id = '$clearance_id'");
		if($query->num_rows() > 0){
			$ef = $query->row()->exit;
			$af = $query->row()->accountability;
		}
		return array($ef, $af);
	}

	public function getDepartmentDescriptionByManagement($deptid){
		$query_dept = $this->db->query("SELECT a.description FROM code_department a INNER JOIN code_office b ON a.code = b.managementid WHERE b.managementid = '$deptid' ");
		if($query_dept->num_rows() > 0) return GLOBALS::_e($query_dept->row()->description);
		else return "No Department";
	}

	public function getAbsencesRemarks($employeeid = "",$date_absent = ""){
		if($employeeid != ''){
			$query_status = $this->db->query(
				"SELECT emp.employeeid,lb.type,le.status,cd.description 
				 FROM employee as emp 
				 	LEFT JOIN leave_app_base as lb ON lb.applied_by=emp.employeeid 
					LEFT JOIN leave_app_emplist as le ON le.base_id=emp.employeeid 
					LEFT JOIN code_request_form cd ON cd.code_request = lb.type
				 WHERE emp.employeeid = '$employeeid'
				");
			return $query_status->row(0);
		}else{
			return false;
		}
	}

	public function getUserType(){
		$query_status = $this->db->query("SELECT * FROM user_type ORDER BY code ASC");
		$data = array();
		if($query_status){
			foreach ($query_status->result_array() as $key => $value) {
				$data[$value['code']."|".$value['description']] = $value;
			}
			return $data;
		}else{
			return false;
		}
	}

	# CUTOFF LIST
	public function getCutoff($date_now){
    	$q_cutoff = $this->db->query("SELECT * FROM cutoff WHERE '$date_now' BETWEEN `CutoffFrom` AND `CutoffTo` ");
    	if($q_cutoff->num_rows() > 0){
    		if($q_cutoff->row()->ConfirmFrom && $q_cutoff->row()->ConfirmTo) return array($q_cutoff->row()->CutoffFrom, $q_cutoff->row()->CutoffTo);
    		else return false;
    	}else{
    		return false;
    	}
    }
	public function getCutoffswithPayroll(){

		$query = $this->db->query("SELECT co.`CutoffFrom` AS cutoff_from,co.`CutoffTo` AS cutoff_to,pcc.`startdate` AS payroll_start,pcc.`enddate` AS payroll_end,pcc.`confrmdate` AS confirm_start,pcc.`confrmend` AS confirm_end, YEAR(co.`ConfirmTo`) AS YEAR
			FROM cutoff AS co 
			LEFT JOIN payroll_cutoff_config AS pcc ON co.`ID`=pcc.`baseid` ORDER BY co.`CutoffTo` DESC;"
		);
		if($query->num_rows() > 0) return $query;
		else return false;
		
	}

	public function includedInLateUTRemoval($employeeid){
		return $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' AND ( employmentstat IN ('PRMNT', 'CNTRCT', 'CSL') OR sep_type = 'VSL')")->num_rows();
	}

	public function applicationListDropdown(){
		$option = "<option value=''>Select an option</option>";
		$q_app = $this->db->query("SELECT a.`description`, b.`code_request`, b.`approver_count`, b.`is_leave`  FROM online_application_code a INNER JOIN code_request_form b ON a.`id` = b.`base_id` WHERE ismain = '1'");
		if($q_app->num_rows() > 0){
			foreach($q_app->result() as $row){
				$option .= "<option approver-count='".$row->approver_count."' is-leave='$row->is_leave' value='".$row->code_request."'>".$row->description."</option>";
			}
		}
		// $option .= "<option value='ServiceCredit'>SERVICE CREDIT</option>";
		// $option .= "<option value='Monetization'>MONETIZATION</option>";
		$option .= "<option value='PVL'>PROPORTIONAL VACATION LEAVE</option>";
		$option .= "<option value='CTO'>CTO</option>";
		return $option;
	}

	public function getDTRCutoffByPayrollCutoff($datefrom,$dateto){
		$q_dtrcutoff = $this->db->query("SELECT a.`CutoffFrom`, a.`CutoffTo` FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.`ID` = b.`baseid` WHERE startdate = '$datefrom' AND enddate = '$dateto' ");
		if($q_dtrcutoff->num_rows() > 0){
			return array($q_dtrcutoff->row()->CutoffFrom, $q_dtrcutoff->row()->CutoffTo);		
		}
		else{
			return false;
		}
	}

	public function applicationApprover($employeeid){
		$q1 = $this->db->query("SELECT * FROM ob_app_emplist WHERE approver_id = '$employeeid'")->num_rows();
		$q2 = $this->db->query("SELECT * FROM leave_app_emplist WHERE approver_id = '$employeeid'")->num_rows();
		$q3 = $this->db->query("SELECT * FROM sc_app_emplist_new WHERE approver_id = '$employeeid'")->num_rows();
		$q4 = $this->db->query("SELECT * FROM sc_app_use_emplist_new WHERE approver_id = '$employeeid'")->num_rows();
		$q5 = $this->db->query("SELECT * FROM ot_app_emplist WHERE approver_id = '$employeeid'")->num_rows();
		$q6 = $this->db->query("SELECT * FROM monetize_app_emplist WHERE approver_id = '$employeeid'")->num_rows();
		$q7 = $this->db->query("SELECT * FROM employee_cto_usage_emplist WHERE approver_id = '$employeeid'")->num_rows();

		return ($q1 + $q2 + $q3 + $q4 + $q5 + $q6 + $q7);
	}

	function getDateIncluded($from_date, $to_date){
		$days_arr   = array();
        $d_from     = date_create($from_date);
        $d_to       = date_create($to_date);
        $diff_date  = date_diff($d_from, $d_to);
        $count_days = $diff_date->format("%a");

        for ($i=0; $i <= $count_days ; $i++) { 
            $days = date_create($from_date);
            date_add($days,date_interval_create_from_date_string("$i days"));
            $days_arr[$i] = date_format($days, "Y-m-d"); 
        }

        return $days_arr;
	}

    function getDateDifference($from_date, $to_date, $format = '%R%a days'){
        $datetime1 = date_create($from_date);
        $datetime2 = date_create($to_date);
        $interval = date_diff($datetime1, $datetime2);
        
        return $interval->format($format);
    }

    

    function convertTimeToNumber($time){
        $returnNum = 0;

        list($hours, $minutes) = explode(":", $time);
        $returnNum += $hours;
        $returnNum += ($minutes / 60);
        
        return $returnNum;
    }

	public function payroll_batch_description($batch_id)
	{
		$q_batch = $this->db->query("SELECT description FROM payroll_batch WHERE id = '$batch_id'");
		if ($q_batch->num_rows() > 0) return $q_batch->row()->description;
		else return false;
	}

	function getAvailableCTO($employeeid){
		$totalCredit = $totalUsed = $totalAvailed = 0;
		$todate = date('Y-m-d');
		$service_credit = $this->db->query("SELECT * FROM employee_compensatory_credit WHERE employeeid = '$employeeid' AND ('$todate' BETWEEN date_from AND date_to)");
        if($service_credit->num_rows() > 0){
            foreach ($service_credit->result() as $key => $value) {
            	$totalCredit += $this->hhmmtoWholeNumber($value->total);
                $totalUsed +=  $this->hhmmtoWholeNumber($value->used);
                $totalAvailed += $this->hhmmtoWholeNumber($value->balance);
            }
            $totalCredit = $this->attcompute->sec_to_hm($totalCredit * 60);
		    $totalUsed = $this->attcompute->sec_to_hm($totalUsed * 60);
		    $totalAvailed = $this->attcompute->sec_to_hm($totalAvailed * 60);
        }

        return array($totalCredit, $totalUsed, $totalAvailed);
	}

	function getLastCTO($employeeid){
		$todate = date('Y-m-d');
		$service_credit = $this->db->query("SELECT * FROM employee_compensatory_credit WHERE employeeid = '$employeeid' AND ('$todate' BETWEEN date_from AND date_to)  ORDER BY timestamp DESC");

		if ($service_credit->num_rows() > 0) {
			$row = $service_credit->row(); // Get the first row
			$timestamp = $row->timestamp; // Assuming 'timestamp' is the name of your timestamp column
			
			// You can use $timestamp here or return it as needed
			return $timestamp;
		} else {
			return null; // Or handle if no rows are found
		}
	}

	function hhmmtoWholeNumber($hhmm){
		$totalCreditHours = 0;
		if($hhmm != 0){
			$hhmm = explode(":", $hhmm);
			if(isset($hhmm[0]) && isset($hhmm[1])){
				$totalCredit_hr = $hhmm[0];
				$totalCredit_mm = $hhmm[1];
				$totalCreditHours = ($totalCredit_hr * 60) + $totalCredit_mm;
			}
				
		}

		// echo "<pre>"; print_r(gmdate("H:i", (($totalCreditHours * 60) * 60))); die;
		return $totalCreditHours;
	}

	function menuTitle($id){
		$q = $this->db->query("SELECT title FROM menus WHERE menu_id = '$id'");
		if($q->num_rows() > 0) return $q->row()->title;
		else return "No title.";
	}

	public function getDisapproveRemarks($table, $base_id){
        $query = $this->db->query("SELECT * FROM $table WHERE base_id = '$base_id' AND status = 'DISAPPROVED'");
        if($query->num_rows() > 0) return $query->row()->remarks;
        else return "";
	}

	public function isPayrollCutoffNoDTR($sdate, $edate){
        $q_nodtr = $this->db->query("SELECT a.nodtr FROM payroll_cutoff_config a INNER JOIN cutoff b ON a.CutoffID = b.CutoffID WHERE a.startdate = '$sdate' AND a.enddate = '$edate' ORDER BY a.CutoffID DESC ");
        if($q_nodtr->num_rows() > 0) return $q_nodtr->row(0)->nodtr;
        else return false;
	}

	public function checkLastApprover($table, $base_id){
		$last_approver = false;
		$username = $this->session->userdata('username');
		$totalSequence = $this->db->query("SELECT * FROM $table WHERE base_id = '$base_id'")->num_rows();
		if($totalSequence > 0){
			$currentSuquence = $this->db->query("SELECT sequence FROM $table WHERE base_id = '$base_id' AND approver_id = '$username' AND ongoing_approver = 'me'");
			if($currentSuquence->num_rows() > 0){
			 if($currentSuquence->row()->sequence == $totalSequence) $last_approver = true;
			}
		}

		return $last_approver;
	}

	public function getDisapprovedRemarks($table, $base_id){
		$query = $this->db->query("SELECT * FROM $table WHERE base_id = '$base_id' AND status = 'DISAPPROVED'");
		if($query->num_rows() > 0) return $query->row()->remarks;
		else return "";
	}

	public function getLatestClearance($employeeid){
		$query = $this->db->query("SELECT a.other_type, b.description as type FROM employee_deficiency a INNER JOIN code_deficiency b ON a.def_id = b.id WHERE  a.employeeid = '$employeeid' ORDER BY date_completed DESC LIMIT 1");
		if($query->num_rows() > 0) return array($query->row()->type, $query->row()->other_type);
		else return array("", "");
	}

	public function getSignatureConfig(){
		return $this->db->query("SELECT * FROM signature_config WHERE description <> '' ORDER BY id ASC");
	}

	public function savePrintDefault($base_id, $appointing_name = '', $appointing_position='', $csc_mc_no='', $published_at='', $published_datefrom='', $published_dateto='', $posted_at='', $posted_datefrom='', $posted_dateto='', $startdate='', $holddate=''){
		$this->db->query("INSERT INTO print_default(base_id, appointing_name, appointing_position,csc_mc_no, published_at,published_datefrom, published_dateto, posted_at,posted_datefrom, posted_dateto, startdate,holddate) VALUES ('$base_id', '$appointing_name', '$appointing_position', '$csc_mc_no', '$published_at', '$published_datefrom','$published_dateto', '$posted_at', '$posted_datefrom', '$posted_dateto', '$startdate', '$holddate')");

	}

	public function saveReportsitem($data){
		foreach ($data as $key => $value) {
			$check = $this->db->query("SELECT * FROM reports_item WHERE reportcode = '$key' AND level = '$value'")->num_rows();
			if($check == 0){
				$this->db->query("INSERT INTO reports_item(reportcode, level) VALUES ('$key', '$value')");
			}else{
				$this->db->query("DELETE FROM reports_item WHERE reportcode = '$key' AND level = '$value'");
				$this->db->query("INSERT INTO reports_item(reportcode, level) VALUES ('$key', '$value')");
			}
		}
	}

	public function saveSignatureConfig($data, $reporttype=''){

		$signature_config = explode('|', $data);
		foreach ($signature_config as $key => $value) {
			list($name_position, $description) = explode('^',$value);
			$check = $this->db->query("SELECT * FROM signature_config WHERE description = '$description' AND reporttype = '$reporttype' AND name_position = '$name_position'")->num_rows();
			if($check == 0){
				$this->db->query("INSERT INTO signature_config(description, name_position, reporttype) VALUES ('$description', '$name_position', '$reporttype')");
			}else{
				$this->db->query("DELETE FROM signature_config WHERE description = '$description' AND reporttype = '$reporttype' AND name_position = '$name_position'");
				$this->db->query("INSERT INTO signature_config(description, name_position, reporttype) VALUES ('$description', '$name_position', '$reporttype')");
			}
		}
	}
	
	public function getIncomeID($code){
		$query = $this->db->query("SELECT * FROM payroll_income_config WHERE code = '$code'");
		if($query->num_rows() > 0){
			return $query->row()->id;
		}else{
			return false;
		}
	}

	public function pvl_perMonth($month_year, $base_id){
		return $this->db->query("SELECT * FROM proportional_vl_dates WHERE base_id = '$base_id' AND month_year = '$month_year'")->num_rows();
	}

	public function saveDailyAttendance($employeeid, $dateYesterday, $credit){
		$remarks = "";
		$this->db->query("DELETE FROM daily_attendance_tracker WHERE employeeid = '$employeeid' AND `date` = '$dateYesterday'");
		if($credit == 0) $remarks = "ABSENT";
		else if($credit == 0.021 || $credit == 0.020) $remarks = "HALFDAY";
		else if($credit == 0.041 || $credit == 0.042) $remarks = "PRESENT";
		$this->db->query("INSERT INTO daily_attendance_tracker(`employeeid`, `date`, `remark`) VALUES ('$employeeid', '$dateYesterday', '$remarks')");
	}

	public function checkDailyAttendance($employeeid, $dateYesterday){
		$query = $this->db->query("SELECT * FROM daily_attendance_tracker WHERE employeeid = '$employeeid' AND `date` = '$dateYesterday'");
		if($query->num_rows() > 0) return $query->row()->remark;
		else return "";
	}

	public function getCertifiedCorrect($limit=""){
		$orderby = '';
		if($limit == "yes"){
			$orderby = "ORDER BY id DESC LIMIT 1";
		}
		return $this->db->query("SELECT * FROM certified_correct $orderby");
	}

	public function deleteCertifiedCorrect($id){
		return $this->db->query("DELETE FROM certified_correct WHERE id = '$id'");
	}

	public function saveSignature($description, $file, $filename, $name_position=''){
		$fQuery = $this->db->query("SELECT * FROM signature_list WHERE description = '$description' AND file_name = '$filename' AND file = '$file'  AND name_position = '$name_position'");
		if($fQuery->num_rows() > 0){
			return $fQuery->row(0)->id;
		}else{
			$this->db->query("INSERT INTO signature_list(description, file_name, file, name_position) VALUES ('$description', '$filename', '$file', '$name_position')");
			return $this->db->insert_id();
		}
	}

	public function saveCertifiedCorrect($name='', $position='', $ccid="", $sig_id=""){
		$check_name = $this->db->query("SELECT * FROM signature_config WHERE description = '$name' AND name_position = 'ccname'")->num_rows();
		if($check_name == 0){
			$this->db->query("INSERT INTO signature_config(description, name_position, signature_id) VALUES ('$name', 'ccname', '$sig_id')");
		}else{
			$this->db->query("DELETE FROM signature_config WHERE description = '$name' AND name_position = 'ccname'");
			$this->db->query("INSERT INTO signature_config(description, name_position, signature_id) VALUES ('$name', 'ccname', '$sig_id')");
		}

		$check_position = $this->db->query("SELECT * FROM signature_config WHERE description = '$position' AND name_position = 'ccposition'")->num_rows();
		if($check_position == 0){
			$this->db->query("INSERT INTO signature_config(description, name_position) VALUES ('$position', 'ccposition')");
		}else{
			$this->db->query("DELETE FROM signature_config WHERE description = '$position' AND name_position = 'ccposition'");
			$this->db->query("INSERT INTO signature_config(description, name_position) VALUES ('$position', 'ccposition')");
		}
		
	}

	 public function getLatestEmploymentStatusData($id, $column){
    	$query = $this->db->query("SELECT $column FROM employee_employment_status_history WHERE id = '$id' ");
    	if($query->num_rows() > 0) return $query->row()->$column;
    	else return false;
    }

    public function monetizationCount(){
    	return $this->db->query("SELECT * FROM monetize_app a INNER JOIN monetize_app_emplist b ON a.id = b.base_id WHERE a.app_status = 'PENDING'  GROUP BY b.base_id");
	}
	
	public function getLeaveHistory($employeeid) {
		return $this->db->query("SELECT * FROM monthly_leave_credit_history WHERE employee_id = '{$employeeid}'")->result();
	}

	function getCreditedLeaveByMonth($employeeid, $date) {
		$where = " WHERE employeeid = '{$employeeid}' AND YEAR(timestamp) = YEAR('{$date}') AND MONTH(timestamp) = MONTH('{$date}')";
		$query = $this->db->query("SELECT credited FROM leaveCreditingLogs $where");

		return ($query->num_rows() > 0) ? $query->row()->credited : 0;

	}

	function getOBListDesc($code=''){
        $wc = '';
        if($code) $wc = " AND id='$code'";
        // if($this->session->userdata("usertype") != "ADMIN") $wc .= " AND type!='SEMINAR'";
        $query = $this->db->query("SELECT * FROM ob_type_list WHERE status = '1' $wc");
        if($query->num_rows() > 0){
            return $query->row()->type;
        }else{
            return $code;
        }
	}
	
	function convertCreditToMinutes($total) {
        $return = 0;
        $remaining = $total;
        $equivalent = array( // MINUTES
            "0.002" => 1, "0.004" => 2, "0.006" => 3, "0.008" => 4, "0.010" => 5, "0.012" => 6, "0.015" => 7, "0.017" => 8," 0.019" => 9, "0.021" => 10, "0.023" => 11, "0.025" => 12, "0.027" => 13, "0.029" => 14, "0.031" => 15, "0.033" => 16, "0.035" => 17, "0.037" => 18, "0.040" => 19, "0.042" => 20, "0.044" => 21, "0.046" => 22, "0.048" => 23," 0.050" => 24, "0.052" => 25, "0.054" => 26, "0.056" => 27, "0.058" => 28, "0.060" => 29, "0.062" => 30, "0.065" => 31, "0.067" => 32, "0.069" => 33, "0.071" => 34, "0.073" => 35, "0.075" => 36, "0.077" => 37, "0.079" => 38," 0.081" => 39, "0.083" => 40, "0.085" => 41, "0.087" => 42, "0.090" => 43, "0.092" => 44, "0.094" => 45, "0.096" => 46, "0.098" => 47, "0.100" => 48, "0.102" => 49, "0.104" => 50, "0.106" => 51, "0.108" => 52, "0.110" => 53," 0.112" => 54, "0.115" => 55, "0.117" => 56, "0.119" => 57, "0.121" => 58, "0.123" => 59, "0.125" => 60
        );
        while ($remaining > 0.125) {
            $return += 60;
            $remaining -= .125;
        }
        $return += $equivalent["$remaining"];

        return $return;
    }

	function convertMinutesToCredit($minutes=0) {
		if ($minutes == 0) return 0;
		$return = 0;
		$remaining = $minutes;
		$equivalent = array( // CREDIT
			1 => 0.002, 2 => 0.004, 3 => 0.006, 4 => 0.008, 5 => 0.010, 6 => 0.012, 7 => 0.015, 8 => 0.017, 9 => 0.019, 10 => 0.021, 
			11 => 0.023, 12 => 0.025, 13 => 0.027, 14 => 0.029, 15 => 0.031, 16 => 0.033, 17 => 0.035, 18 => 0.037, 19 => 0.040, 
			20 => 0.042, 21 => 0.044, 22 => 0.046, 23 => 0.048, 24 => 0.050, 25 => 0.052, 26 => 0.054, 27 => 0.056, 28 => 0.058, 
			29 => 0.060, 30 => 0.062, 31 => 0.065, 32 => 0.067, 33 => 0.069, 34 => 0.071, 35 => 0.073, 36 => 0.075, 37 => 0.077, 
			38 => 0.079, 39 => 0.081, 40 => 0.083, 41 => 0.085, 42 => 0.087, 43 => 0.090, 44 => 0.092, 45 => 0.094, 46 => 0.096, 
			47 => 0.098, 48 => 0.100, 49 => 0.102, 50 => 0.104, 51 => 0.106, 52 => 0.108, 53 => 0.110, 54 => 0.112, 55 => 0.115, 
			56 => 0.117, 57 => 0.119, 58 => 0.121, 59 => 0.123, 60 => 0.125
		);

		while ($remaining > 60) {
			$return += .125;
			$remaining -= 60;
		}
		$return += $equivalent["$remaining"];

		return $return;
	}

	function getTypeOfOB($employeeid, $date) {
		$result = $this->db->query("SELECT `type` FROM ob_type_list WHERE id = (SELECT obtypes FROM ob_app WHERE applied_by = '$employeeid' AND datefrom = '$date' AND `status` = 'APPROVED' LIMIT 1)");
		return $result->num_rows() > 0 ? $result->row()->type : '';
	}

	function getEmployeeEmploymentStatus($employeeid) {
		$result = $this->db->query("SELECT employmentstat FROM employee WHERE employeeid = '{$employeeid}'");
		return $result->num_rows($result) > 0 ? $result->row()->employmentstat : '';
	}

	function getFacultyLoadsClassfication() {
		return $this->db->query("SELECT id, description FROM faculty_load_classification")->result();
	}

	public function loadFacialPerson($where = '', $lc = ''){
		return $this->db->query("SELECT personId, name FROM facial_person $where GROUP BY FaceId1 ORDER BY name $lc")->result_array();
	}

	public function getSchoolOf() {
		return $this->db->query("SELECT education_level, description FROM aims_department GROUP BY education_level ORDER BY description")->result();
	}

	public function showFundTypeOptions($code) {
		$return = "<option value=''> - Select Fund Type - </option>";
		$result = $this->db->query("SELECT code, fund_description FROM code_fund_type")->result();
		foreach ($result as $value) {
			$isSelected = $code == $value->code ? 'selected' : '';
			$return .= "<option value='$value->code' $isSelected> $value->fund_description </option>";
		}
		return $return;
	}

	public function getFundTypeDescription($code) {
		$result = $this->db->query("SELECT fund_description FROM code_fund_type WHERE code = '$code'");
		return $result->num_rows() > 0 ? $result->row()->fund_description : '';
	}

	public function getIncomeDescription($id) {
		$result = $this->db->query("SELECT description FROM payroll_income_config WHERE id = '$id'");
		return $result->num_rows() > 0 ? $result->row()->description : '';
	}

	public function getIncomeFundType($id) {
		$result = $this->db->query("SELECT fund_type FROM payroll_income_config WHERE id = '$id'");
		return $result->num_rows() > 0 ? $result->row()->fund_type : '';
	}

	public function getPayrollDescription($id) {
		$result = $this->db->query("SELECT description FROM payroll_config WHERE id = '$id'");
		return $result->num_rows() > 0 ? $result->row()->description : '';
	}

	public function getPayrollFundType($id) {
		$result = $this->db->query("SELECT fund_type FROM payroll_config WHERE id = '$id'");
		return $result->num_rows() > 0 ? $result->row()->fund_type : '';
	}

	public function getAllEmployees() {
		return $this->db->query("SELECT employeeid, CONCAT(fname, ' ', mname, ' ', lname) AS fullname FROM employee")->result();
	}

	public function getEmployeeFundTypePerIncome($id, $employeeid) {
		$result = $this->db->query("SELECT fund_type_code FROM employee_income_individual_fund_type_history WHERE income_id = '$id' AND employeeid = '$employeeid' ORDER BY id DESC LIMIT 1");
		return $result->num_rows() > 0 ? $result->row()->fund_type_code : '';
	}

	public function getEmployeeSalaryFundType($employeeid) {
		$result = $this->db->query("SELECT fund_type FROM payroll_employee_salary_history WHERE employeeid = '$employeeid' ORDER BY id DESC LIMIT 1");
		return $result->num_rows() > 0 ? $result->row()->fund_type : '';
	}

	public function getRemarksList() {
		return $this->db->query("SELECT request_code, description FROM code_request_type")->result();
	}

	public function saveNewRemarks($data) {
		$description = $this->db->escape($data['description']);
		if ($data['id'] == '') {
			return $this->db->query("INSERT INTO code_request_type
										SET description={$description}");
		} else {
			return $this->db->query("UPDATE code_request_type
										SET description = {$description}
										WHERE request_code = {$data['id']}");
		}
	}

	public function deleteRemarks($data) {
		return $this->db->query("DELETE FROM code_request_type WHERE request_code ='{$data['id']}'");
	}

	public function getInUsedRemarks() {
		$datas = $this->db->query("SELECT DISTINCT remarks FROM employee_schedule_adjustment")->result();
		
		$return = array();
		foreach ($datas as $data) {
			array_push($return, $data->remarks);
		}
		return $return;
	}

	function getAllTable($table=''){
		return $this->db->query("SELECT * FROM $table")->result();
	}

	function checkSchedIfHasNightShift($employeeid, $date){
		$wc = "";
		$latestda = date('Y-m-d', strtotime($this->extensions->getLatestDateActive($employeeid, $date)));
    	if($date >= $latestda) $wc .= " AND DATE(dateactive) = DATE('$latestda')";
    	$res = $this->db->query(" SELECT * FROM employee_schedule_history WHERE employeeid='$employeeid' AND DATE(dateactive) <= DATE('$date') AND night_shift = '1' $wc ")->num_rows();
		return $res;
	}

	public function getEmployeeFundType($empid, $date){
		$query = $this->db->query("SELECT fund_type FROM payroll_employee_salary_history WHERE employeeid = '$empid' AND date_effective <= '$date' ORDER BY timestamp DESC LIMIT 1");
		if($query->num_rows() > 0){
			return $query->row()->fund_type;
		}else{
			return "FUND011";
		}
	}

	public function getEmployeePERA($empid){
		$query = $this->db->query("SELECT personal_economic_relief_allowance FROM payroll_employee_salary WHERE employeeid = '$empid' LIMIT 1");
		if($query->num_rows() > 0){
			return $query->row()->personal_economic_relief_allowance;
		}else{
			return false;
		}
	}

	public function getCurrentYear(){
		return $this->db->query("SELECT YEAR(CURRENT_DATE) AS current_year")->row()->current_year;
	}
	
	public function cutoffByCurrentDate(){
		$from_date = $to_date = "";
		$datenow = $this->getServerTime();
		$query = $this->db->query("SELECT * FROM cutoff WHERE '$datenow' BETWEEN CutoffFrom AND CutoffTo GROUP BY CutoffFrom, CutoffTo");
		if($query->num_rows() > 0){
			$from_date = $query->row()->CutoffFrom;
			$to_date = $query->row()->CutoffTo;
		}

		return array($from_date, $to_date);
	}

	function showTeachingTypeOptions($type='', $isAll=false, $trelated=false) {
		$return = $isAll ? "<option value=''>All Teaching Type</option>" : "<option value=''>Select Teaching Type</option>";
		$data = array(
			'teaching' => 'Teaching', 'nonteaching' => 'Non-Teaching', 'trelated' => 'Teaching with Admin Loads',
		);
		$type = $trelated ? 'trelated' : $type;
		foreach ($data as $key => $value) {
			$isSelected = $key == $type ? "selected" : "";
			$return .= "<option value='$key' $isSelected>$value</option>";
		}
		return $return;
	}
	
} //endoffile