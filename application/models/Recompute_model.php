<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Recompute_model extends CI_Model {

	private $user;

	public function __construct() {
		parent::__construct();
        $this->load->model("time", "time");
	}

	public function getServerTime(){
		$query_time = $this->db->query("SELECT CURRENT_TIMESTAMP ")->row()->CURRENT_TIMESTAMP;
		return $query_time;
	}

    public function generate_cutoff_ref_no(){
        $data = array();
        $ref_no = "";
        $last_no = "";
        $str_length = 5;
        $year = date("Y");
        $query = $this->db->query("SELECT id, prefix, current_number from cut_off_ref_no WHERE prefix = '$year' ORDER BY id DESC LIMIT 1");
        if($query->num_rows() > 0){
            $last_no = (int) $query->row(0)->current_number;
            $ref_no = $this->reformString($last_no + 1, "0", $str_length, true) ."-". $query->row(0)->prefix;
            $data = array($query->row(0)->id, $ref_no, $query->row(0)->prefix, $last_no);
        }else{
            $ref_no = "000001-".$year;
            $data = array("", $ref_no, $year);
            
        }
        return $data;
    }
	
    function reformString($stringToFill='',$fillString='0',$lengthOfString=2,$alignRight=true){
        $result = $stringToFill;
        for($c=1;$c<=$lengthOfString-strlen($stringToFill);$c++)
         {
           if($alignRight) $result = $fillString . $result;
           else $result .= $fillString;
         }
        return $result;
    }

    function getPayrollCutoffBaseId($sdate='',$edate=''){
        $payroll_cutoff_id = '';
        $p_q = $this->db->query("SELECT baseid FROM payroll_cutoff_config WHERE startdate='$sdate' AND enddate='$edate'");
        if($p_q->num_rows() > 0) $payroll_cutoff_id = $p_q->row(0)->baseid;
        return $payroll_cutoff_id;
    }
      
    function loadAllEmpbyDept($dept = "", $eid = "", $sched = "",$campus="",$company_campus="", $sdate = "", $edate = "", $sortby = "", $office="", $teachingtype="", $empstatus="", $fund_type="", $employment_status="", $user=''){
		$this->user = $user;
        $date = date('Y-m-d');
        $whereClause = $orderBy = $wC = "";
        if($sortby == "alphabetical") $orderBy = " ORDER BY fullname";
        if($sortby == "department") $orderBy = " ORDER BY d.description";
        if($dept)   $whereClause .= " AND b.deptid='$dept'";
        if($office)   $whereClause .= " AND b.office='$office'";
        if($employment_status)   $whereClause .= " AND b.employmentstat='$employment_status'";
        if($teachingtype){
            if($teachingtype != "trelated") $whereClause .= " AND b.teachingtype='$teachingtype' AND trelated = '0'";
            else $whereClause .= " AND b.teachingtype = 'nonteaching' AND trelated = '1'";
        }
        if($empstatus != "all" && $empstatus != ''){
            if($empstatus=="1"){
                $wC .= " AND (('$date' < dateresigned2 OR dateresigned2 = '0000-00-00' OR dateresigned2 = '1970-01-01' OR dateresigned2 IS NULL) AND isactive ='1')";
            }
            if($empstatus=="0"){
                $wC .= " AND (('$date' >= dateresigned2 AND dateresigned2 IS NOT NULL AND dateresigned2 <> '0000-00-00' AND dateresigned2 <> '1970-01-01' ) OR isactive = '0')";
            }
            if(is_null($empstatus)) $wC .= " AND isactive = '1' AND (dateresigned2 = '0000-00-00' OR dateresigned2 = '1970-01-01' OR dateresigned2 IS NULL)";
        }
        if($eid && $eid != 'all')    $whereClause .= " AND a.employeeid='$eid'";
        if($campus && $campus != "All")    $whereClause .= " AND b.campusid='$campus'";
        if($company_campus && $company_campus != 'all')    $whereClause .= " AND b.company_campus='$company_campus'";
        
        $utwc = '';
		$utdept = $this->getEmployeeDepartment($user);
		$utoffice = $this->getEmployeeOffice($user);
		$userType = $this->getUserType($user);
        if($userType == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }

        $batchaccess = $this->getBatchAccess($user);
        if ($batchaccess && !in_array("all", explode(",", $batchaccess))) {
            $whereClause .= " AND b.employeeid IN (SELECT e.employeeid FROM payroll_batch_emp e WHERE FIND_IN_SET (base_id,'$batchaccess') ) ";
        }
        if($sched) $whereClause .= " AND a.schedule='$sched'";
        if($fund_type) $whereClause .= " AND a.fund_type='$fund_type'";
        $whereClause .= $utwc;


        if($this->isPayrollCutoffNoDTR($sdate, $edate) == 0){
            if($sdate && $edate) $whereClause .= " AND c.cutoffstart = '$sdate' AND c.cutoffend = '$edate' AND c.`status` = 'PROCESSED' ";
            return $this->db->query("SELECT a.*, CONCAT(lname,', ',fname,' ',mname) as fullname,a.$sched as regpay, b.teachingtype, b.employmentstat, b.office, (SELECT personal_economic_relief_allowance FROM payroll_employee_salary WHERE employeeid = a.employeeid) AS pera
                                        FROM payroll_employee_salary_history a 
                                        INNER JOIN employee b ON b.employeeid = a.employeeid
                                        INNER JOIN processed_employee c ON c.`employeeid` = b.`employeeid`
                                        LEFT JOIN code_office d ON d.`code` = b.`office`
                                        WHERE (b.dateresigned2 = '1970-01-01' OR b.dateresigned2 = '0000-00-00' OR b.dateresigned2 IS NULL OR b.dateresigned2 >= '$date' OR b.dateresigned = '1970-01-01' OR b.dateresigned = '0000-00-00' OR b.dateresigned IS NULL OR b.dateresigned >= '$date') AND a.`date_effective` <= '$sdate' AND a.id = (SELECT id FROM payroll_employee_salary_history WHERE date_effective <= '$sdate'  AND employeeid = b.employeeid ORDER BY timestamp DESC LIMIT 1)  $whereClause GROUP BY employeeid $orderBy ")->result();
        }else{
            return $this->db->query("SELECT a.*, CONCAT(lname,', ',fname,' ',mname) as fullname,a.$sched as regpay, b.teachingtype, b.employmentstat, b.office
                                     FROM payroll_employee_salary_history a 
                                     INNER JOIN employee b ON b.employeeid = a.employeeid
                                     LEFT JOIN code_office d ON d.`code` = b.`office`
                                     WHERE (b.dateresigned2 = '1970-01-01' OR b.dateresigned2 = '0000-00-00' OR b.dateresigned2 IS NULL OR b.dateresigned2 >= '$date' OR b.dateresigned = '1970-01-01' OR b.dateresigned = '0000-00-00' OR b.dateresigned IS NULL OR b.dateresigned >= '$date') AND a.`date_effective` <= '$sdate' AND a.id = (SELECT id FROM payroll_employee_salary_history WHERE date_effective <= '$sdate'  AND employeeid = b.employeeid ORDER BY timestamp DESC LIMIT 1)  $whereClause GROUP BY b.employeeid $orderBy ")->result();
        }    
    } 

	public function getEmployeeDepartment($employeeid=''){
    	$q_dept = $this->db->query("SELECT code FROM employee a INNER JOIN code_department b ON a.`deptid` = b.`code` WHERE employeeid = '$employeeid' ");
    	return $q_dept->num_rows() > 0 ? $q_dept->row()->code : '';
    }
    
    public function getEmployeeOffice($employeeid=''){
    	$q_office = $this->db->query("SELECT code FROM employee a INNER JOIN code_office b ON a.`office` = b.`code` WHERE employeeid = '$employeeid' ");
    	return $q_office->num_rows() > 0 ? $q_office->row()->code : '';
    }

    function getUserType($uid=''){
        $query = $this->db->query("SELECT user_type FROM user_info WHERE id='$uid' ");
        return $query->num_rows() > 0 ? $query->row()->user_type : '';
    }

    function getCampusUser($username='') {
        $return = "";
        $query = $this->db->query("SELECT campus FROM user_info where username='$username' ")->result();
        foreach ($query as $key) {
            $return = $key->campus;
        }
        return $return;
    }
	
    function getBatchAccess($username='') {
        $return = "";
        $query = $this->db->query("SELECT batch_access FROM user_info where username='$username' and batch_access != 'null' ");
        if($query->num_rows() > 0){
            foreach ($query->result() as $key) {
                $return = $key->batch_access;
            }
        }
        return $return;
    }
	
	public function isPayrollCutoffNoDTR($sdate, $edate){
        $q_nodtr = $this->db->query("SELECT a.nodtr FROM payroll_cutoff_config a INNER JOIN cutoff b ON a.CutoffID = b.CutoffID WHERE a.startdate = '$sdate' AND a.enddate = '$edate' ORDER BY a.CutoffID DESC ");
        if($q_nodtr->num_rows() > 0) return $q_nodtr->row(0)->nodtr;
        else return false;
	}

    function processPayrollSummary($emplist=array(),$sdate='',$edate='',$schedule='',$quarter='',$recompute=false,$payroll_cutoff_id=''){

		$recomputed_emp_payroll = 0;

		//< initialize needed info ---------------------------------------------------
		$info    = $arr_income_config = $arr_income_adj_config = $arr_incomeoth_config = $arr_deduc_config = $arr_fixeddeduc_config = $arr_loan_config = array();

		///< ------------------------------ income config ------------------------------------------------------------
		$income_config_q = $this->displayIncome(); // PAYROLL
		$arr_income_config = $this->constructArrayListFromStdClass($income_config_q,'id','description');
		$arr_income_adj_config = $arr_income_config;
		$arr_income_adj_config['SALARY'] = array('description'=>'SALARY','hasData'=>0);

		///< ------------------------------ incomeoth config ---------------------------------------------------------------
		$incomeoth_config_q = $this->displayIncomeOth(); // PAYROLL
		$arr_incomeoth_config = $this->constructArrayListFromStdClass($incomeoth_config_q,'id','description');

		///< ------------------------------ fixed deduction config ----------------------------------------------------
		$fixeddeduc_config_q = $this->db->query("SELECT code_deduction,description FROM deductions");
		$arr_fixeddeduc_config = $this->constructArrayListFromStdClass($fixeddeduc_config_q,'code_deduction','description');


		///< ------------------------------ deduction config ----------------------------------------------------------
		$deduction_config_q = $this->displayDeduction(); // PAYROLL
		$arr_deduc_config = $this->constructArrayListFromStdClass($deduction_config_q,'id','description');
		$arr_deduc_config_arithmetic = $this->constructArrayListFromStdClass($deduction_config_q,'id','arithmetic');


		///< ------------------------------ loan config ---------------------------------------------------------------
		$loan_config_q = $this->displayLoan(); // PAYROLL
		$arr_loan_config = $this->constructArrayListFromStdClass($loan_config_q,'id','description');

		if($recompute === true){
			foreach($emplist as $row){
				$eid = $row->employeeid;
				$this->db->query("DELETE FROM payroll_computed_table WHERE cutoffstart='$sdate' AND cutoffend='$edate' AND schedule='$schedule' AND quarter='$quarter' AND employeeid='$eid' AND status='PENDING'");
			}
		}

		foreach ($emplist as $row) {
			$eid = $row->employeeid;
				
			$check_saved_q = $this->getPayrollSummary('SAVED',$sdate,$edate,$schedule,$quarter,$eid,TRUE,'PROCESSED');

			if(!$check_saved_q){

				$info[$eid]['income'] = $info[$eid]['income_adj'] = $info[$eid]['deduction'] = $info[$eid]['fixeddeduc'] = $info[$eid]['loan'] = array();

				$info[$eid]['fullname'] 	=  isset($row->fullname) ? $row->fullname : '';
				$info[$eid]['deptid'] = isset($row->deptid) ? $row->deptid : '';
				$info[$eid]['office'] = isset($row->office) ? $row->office : '';
				// $info[$eid]['pera'] = isset($row->pera) ? $row->pera : '';

				list($info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config) 
						= $this->computeNewPayrollInfo($row,$schedule,$quarter,$sdate,$edate,$payroll_cutoff_id,$info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_deduc_config_arithmetic,$arr_loan_config); 

			} ///< end if SAVED
		} //end loop emplist

        if($schedule == "semimonthly" && $quarter == 2){
        	$arr_income_config['Monetize']['description'] = "Monetize";
		}

		$data['emplist'] = $info;
		$data['income_config'] = $arr_income_config;
		$data['income_adj_config'] = $arr_income_adj_config;
		$data['incomeoth_config'] = $arr_incomeoth_config;
		$data['fixeddeduc_config'] = $arr_fixeddeduc_config;
		$data['deduction_config'] = $arr_deduc_config;
		$data['loan_config'] = $arr_loan_config;

		return $data;

	}

	function constructArrayListFromStdClass($res='',$key='',$value=''){
	    $arr = array();
	    if($res->num_rows() > 0){
	        foreach ($res->result() as $k => $row) {
	            $arr[$row->$key] = array('description'=>$row->$value,'hasData'=>0);
	        }
	    }
	    return $arr;
	}

    function displayIncome($id = ""){
        $whereClause = "";        
        if($id) $whereClause = " WHERE id='$id'";
        $query = $this->db->query("SELECT id,code,description,taxable,incomeType,grossinc,ismainaccount,mainaccount,fund_type,deductedby,isIncluded,isBonus,addedby FROM payroll_income_config $whereClause");
        return $query;
    }
    
    function displayIncomeOth($id = ""){
        $whereClause = "";        
        if($id) $whereClause = " WHERE id='$id'";
        $query = $this->db->query("SELECT id,description,addedby,taxable,grossinc FROM payroll_income_oth_config $whereClause");
        return $query;
    }
    
    function displayDeduction($id = ""){
        $whereClause = "";        
        if($id) $whereClause = " WHERE id='$id'";
        $query = $this->db->query("SELECT id,description,arithmetic,addedby,taxable,grossinc,loanaccount, is_provident FROM  payroll_deduction_config $whereClause");
        return $query;
    }
    
    function displayLoan($id = ""){
        $whereClause = "";        
        if($id) $whereClause = " WHERE id='$id'";
        $query = $this->db->query("SELECT id,description,loan_type,addedby,taxable,grossinc FROM payroll_loan_config $whereClause");
        return $query;
    }

    function getPayrollSummary($status='',$cutoffstart='',$cutoffend='',$schedule='',$quarter='',$employeeid='',$checkCount=false,$status2='',$bank=''){
		$wC = '';
		if($employeeid)					$wC .= " AND employeeid='$employeeid'";
		if($bank)						$wC .= " AND bank='$bank'";
		if($status && $status2) 		$wC .= " AND (status='$status' OR status='$status2')";
		elseif($status && !$status2)	$wC .= " AND status='$status'";
		$utwc = '';
        if($utwc) $wC .= " AND employeeid IN (SELECT employeeid FROM employee WHERE 1 $utwc)";
		if($checkCount){
			$cutoff_exist_q = $this->db->query("SELECT count(id) AS existcount from payroll_computed_table WHERE cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND schedule='$schedule' AND quarter='$quarter' $wC");
			if($cutoff_exist_q->num_rows() > 0) return $cutoff_exist_q->row(0)->existcount;
			else 								return 0;
		}else{
			$payroll_q = $this->db->query("SELECT * FROM payroll_computed_table WHERE cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND schedule='$schedule' AND quarter='$quarter' $wC");
			return $payroll_q;
		}
	}

	function computeNewPayrollInfo($row,$schedule,$quarter,$sdate,$edate,$payroll_cutoff_id,$info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_deduc_config_arithmetic,$arr_loan_config){
		$perdept_amt_arr = array();
		$workdays =	$absentdays = 0;
		$workhours_lec = $workhours_lab = $workhours_admin = "";
		$eid 		= $row->employeeid;
		$tnt 		= $row->teachingtype;
		$daily_hours = $tnt == "teaching" ? 7 : 8;
		$employmentstat = $row->employmentstat;
		$regpay 	=  $row->regpay;
		$monthlySalary 	=  $row->monthly;

		$from_date = new DateTime($sdate);
		$to_date = new DateTime($edate);

		$monthDays = $from_date->diff($to_date)->format("%r%a");
		$monthDays++;
		$daily = $monthlySalary / $monthDays;
		// echo "<pre>"; print_r($row->daily); die;

		$absent_rate = $monthlySalary / 22;
		$hourly = $daily / $daily_hours;
		$minutely = $hourly / 60;
		$daily2 		=  $row->daily;
		// $hourly =  $row->hourly;
		// $minutely =  $row->minutely;
		$lechour 	=  $row->lechour;
		$labhour 	=  $row->labhour;
		$rlehour 	=  $row->rlehour;
		$fixedday 	=  $row->fixedday;
		$is_pera = isset($row->pera) ? $row->pera : 0;
		$is_provident_premium 	=  $row->provident_premium;
		$basic_personal_exception 	=  $row->bpe;
		$dependents = $row->dependents;
		$isFinal = '';
		$project_hol_pay = 0;
		$str_income = $str_income_adj = $str_fixeddeduc = $str_deduc = $str_loan = "";
		$total_deducSub= $totalincome= $totalincome_adj = $totalfix=$total_deducAdd=$totalloan = 0;
		$has_bdayleave = $this->has_birthday_leave($sdate, $edate, $eid);
		$minimum_wage = $this->minimum_wage($sdate);
		$perdept_salary = array();
		$vl_balance = $t_overload = 0;
		$info[$eid]['teaching_pay'] = $info[$eid]['parrtime_pay'] = $info[$eid]['overload_pay'] = 0;
		// $employmentstat = "";
		$is_trelated = $this->isTeachingRelated($eid); // EMPLOYEE
		if($tnt == 'teaching'){
			$perdept_salary = $this->getPerdeptSalaryHistory($eid,$sdate); // PAYROLLCOMPUTATION

			list($tardy_amount,$absent_amount,$workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$perdept_amt_arr,$hold_status,$x,$x,$forFinalPay,$conf_base_id,$excess_min, $vl_balance, $t_overload, $hasZeroRate) = $this->getTardyAbsentSummaryTeaching($eid,$tnt,$schedule,$quarter,$sdate,$edate,$hourly,$lechour,$labhour,$rlehour,$perdept_salary,false,$regpay, $employmentstat, "", $absent_rate, $daily);
			list($info[$eid]['salary'], $info[$eid]['teaching_pay'], $info[$eid]['parrtime_pay'], $info[$eid]['overload_pay']) 	= $this->computeTeachingCutoffSalary($workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$hourly,$lechour,$labhour,$rlehour,$fixedday,$regpay,$perdept_amt_arr,$hold_status,$excess_min,$has_bdayleave,$minimum_wage); // PAYROLLCOMPUTATION
				if($hasZeroRate > 0) $info[$eid]['teaching_pay']= $info[$eid]['salary'] = $info[$eid]['parrtime_pay'] =  0;
			

			list($project_hol_pay, $sub_hol_pay) = $this->getProjectHolidayPay($payroll_cutoff_id,$lechour,$labhour,$rlehour,$eid,$sdate); // PAYROLLCOMPUTATION
			// $info[$eid]["salary"] -= $sub_hol_pay;

			/*remove absent for teaching, condition ni olfu HYP-3937*/
			// $absent_amount = 0;

			$this->saveHolidayPay($project_hol_pay, $eid, $sdate, $edate, $quarter); //INCOME
			$info[$eid]['substitute'] = $this->computeSubstitute($eid,$conf_base_id); //PAYROLLCOMPUATION

		}else{
			if(!$is_trelated){
				list($tardy_amount,$absent_amount,$workdays,$x,$x,$conf_base_id, $isFinal, $vl_balance) = $this->getTardyAbsentSummaryNT($eid,$tnt,$schedule,$quarter,$sdate,$edate,$hourly,false,$daily, $monthlySalary, $sdate, $is_pera, $fixedday, $daily2);
				$info[$eid]['salary'] 	= $this->computeNTCutoffSalary($workdays,$fixedday,$regpay,$daily,$has_bdayleave,$minimum_wage, $daily2); // PAYROLLCOMPUATION

				$info[$eid]['substitute'] = 0;
			}else{
				$perdept_salary = $this->getPerdeptSalaryHistory($eid,$sdate); // PAYROLLCOMPUATION
				list($tardy_amount,$absent_amount,$workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$perdept_amt_arr,$hold_status,$x,$x,$forFinalPay,$conf_base_id,$excess_min, $vl_balance, $t_overload, $hasZeroRate) = $this->getTardyAbsentSummaryTeaching($eid,$tnt,$schedule,$quarter,$sdate,$edate,$hourly,$lechour,$labhour,$rlehour,$perdept_salary, $is_pera,$regpay, $employmentstat, $is_trelated, $absent_rate, $daily);

				list($info[$eid]['salary'], $info[$eid]['teaching_pay'], $info[$eid]['parrtime_pay'], $info[$eid]['overload_pay']) 	= $this->computeTeachingCutoffSalary($workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$hourly,$lechour,$labhour,$rlehour,$fixedday,$regpay,$perdept_amt_arr,$hold_status,0,false,0, $is_trelated); // PAYROLLCOMPUATION

				if($hasZeroRate > 0) $info[$eid]['teaching_pay'] = $info[$eid]['salary'] = $info[$eid]['parrtime_pay'] = $info[$eid]['overload_pay'] = 0;

				list($project_hol_pay, $sub_hol_pay) = $this->getProjectHolidayPay($payroll_cutoff_id,$lechour,$labhour,$rlehour,$eid,$sdate); // PAYROLLCOMPUATION
				// $info[$eid]["salary"] -= $sub_hol_pay;

				$this->saveHolidayPay($project_hol_pay, $eid, $sdate, $edate, $quarter); // INCOME

			}
		}
		
		$info[$eid]["pera"] = 0;
		$allowed_pera = $this->getEmployeePERA($eid);
		if ($allowed_pera) {
			$info[$eid]["pera"] = 2000;
		}


		///< pag wala attendance - wala salary,tardy,absent pero papasok pa rin sa payroll - maiiwan mga income nya (DOUBLE CHECKING)
		if( !$this->hasAttendanceConfirmed($tnt,array('employeeid'=>$eid,'status'=>'PROCESSED','forcutoff'=>'1','payroll_cutoffstart'=>$sdate,'payroll_cutoffend'=>$edate,'quarter'=>$quarter), $is_trelated )){
			$info[$eid]['salary'] = $tardy_amount = $absent_amount = 0;
			$perdept_amt_arr = array();
		}

		if($quarter == 2){
			// list($info[$eid]['overtime'],$ot_det) = $this->computeOvertime2($eid,$tnt,$hourly,$conf_base_id,$employmentstat);
			list($info[$eid]['overtime'],$ot_det) = $this->computeOvertime3($eid,$tnt,$hourly,$conf_base_id,$employmentstat,$monthlySalary,$sdate); // PAYROLLCOMPUATION
		}else{
			$info[$eid]['overtime'] = 0;
			$ot_det = array();
		}
		
		/*check cutoff if no late and undertime*/
		$is_flexi = $this->isFlexiNoHours($eid); // ATTENDANCE
		if($this->validateDTRCutoff($sdate, $edate, $quarter) || $is_flexi > 0) $tardy_amount = $absent_amount = 0;

		$info[$eid]['tardy'] 		= $tardy_amount;
		$info[$eid]['absents'] 		= $absent_amount;
		$info[$eid]['overload'] 	= $info[$eid]['overload_pay'];

		///<  compute and save other income
		// $arr_adj_to_add = $this->comp->computeOtherIncomeAdj($eid,$payroll_cutoff_id);
		$this->computeEmployeeOtherIncome($eid,$sdate,$edate,$tnt,$schedule,$quarter,$perdept_salary,$regpay); // PAYROLLCOMPUTATION
		if(!$fixedday && $tnt=="teaching") $this->computeCOLAIncome($eid,$sdate,$edate,$schedule,$quarter,$workdays,$absentdays); //PAYROLLCOMPUTATION
		// $this->comp->computeLongevity($eid,$sdate,$edate,$tnt,$schedule,$quarter);
		///< income
		list($arr_income_config,$info[$eid]['income'],$totalincome,$str_income, $str_monetize_id) = $this->computeEmployeeIncome($eid,$schedule,$quarter,$sdate,$edate,$arr_income_config,$payroll_cutoff_id, $monthlySalary); //PAYROLLCOMPUTATION
		// $getTotalNotIncludedInGrosspay = $this->getTotalNotIncludedInGrosspay($info[$eid]['income']);
		$getTotalNotIncludedInGrosspay = 0;
		///< income adjustment
		list($arr_income_adj_config,$info[$eid]['income_adj'],$totalincome,$str_income_adj) = $this->computeEmployeeIncomeAdj($eid,$schedule,$quarter,$sdate,$edate,$arr_income_adj_config,$totalincome,$payroll_cutoff_id); //PAYROLLCOMPUTATION
		
		//<!--GROSS PAY-->
		$info[$eid]['grosspay'] = ($info[$eid]['salary'] + $info[$eid]['teaching_pay'] + $info[$eid]['parrtime_pay'] + $totalincome + $info[$eid]['overtime'] + $info[$eid]["pera"]) - $tardy_amount - $absent_amount;
		// $info[$eid]['grosspay'] = ($info[$eid]['salary'] + $totalincome + $info[$eid]['overtime']) - $tardy_amount - $absent_amount;

		// deduct provident premium
		$info[$eid]["provident_premium"] = 0;
		if($is_provident_premium){
			/*get 1% of salary and assign to provident_premium and minus it to basic pay*/
			$info[$eid]["provident_premium"] = (1 / 100) * $info[$eid]['salary'];
			// $info[$eid]["salary"] -= $info[$eid]["provident_premium"];
		}

		list($prevSalary,$prevGrosspay) = $this->getPrevCutoffSalary(date('Y-m',strtotime($sdate)),$quarter,$eid);

		///< fixed deduc
		list($arr_fixeddeduc_config,$info[$eid]['fixeddeduc'],$totalfix,$str_fixeddeduc,$ee_er) = $this->computeEmployeeFixedDeduc($eid,$schedule,$quarter,$sdate,$edate,$arr_fixeddeduc_config,$info[$eid],$prevSalary,$prevGrosspay,$getTotalNotIncludedInGrosspay,$info[$eid]['salary']); //PAYROLLCOMP

		///< loan
		list($arr_loan_config,$info[$eid]['loan'],$totalloan,$str_loan) = $this->computeEmployeeLoan($eid,$schedule,$quarter,$sdate,$edate,$arr_loan_config); //PAYROLLCOMP

		//<!--NET BASIC PAY-->
		// $info[$eid]['netbasicpay'] = ($info[$eid]['salary']  - ($info[$eid]['absents']+ $info[$eid]['tardy']));
		$info[$eid]['netbasicpay'] = ($info[$eid]['salary'] + $info[$eid]['teaching_pay'] + $info[$eid]['parrtime_pay'] + $info[$eid]['pera'] - ($info[$eid]['absents']+ $info[$eid]['tardy']));

		if($isFinal){
			list($_13th_month, $employee_benefits) = $this->compute13thMonthPay_2($eid,date('Y',strtotime($sdate)),$sdate,$edate,$info[$eid]['netbasicpay'],$info[$eid]['income'], true, $regpay); // INCOME
			if($_13th_month > 0) $this->saveEmployeeOtherIncome($eid,$sdate,$edate,'5',$_13th_month,$schedule,$quarter); //INCOME
			if($employee_benefits > 0) $this->saveEmployeeOtherIncome($eid,$sdate,$edate,'37',$employee_benefits,$schedule,$quarter); //INCOME
			///< income (RECOMPUTE TO INCLUDE 13TH MONTH PAY)
			list($arr_income_config,$info[$eid]['income'],$totalincome,$str_income, $str_monetize_id) = $this->computeEmployeeIncome($eid,$schedule,$quarter,$sdate,$edate,$arr_income_config,$payroll_cutoff_id, $monthlySalary); //PAYROLLCOMP

		}

		// echo "<pre>"; print_r($arr_deduc_config); die;

		///< deduction
		list($arr_deduc_config,$info[$eid]['deduction'],$total_deducSub,$total_deducAdd,$str_deduc) = $this->computeEmployeeDeduction($eid,$schedule,$quarter,$sdate,$edate,$arr_deduc_config,$arr_deduc_config_arithmetic); //PAYROLLCOMP
		///< TAX COMPUTATION
		$wh_tax = $this->getExistingWithholdingTax($eid, $edate); //PAYROLLCOMP
		if($wh_tax!=""){
			if ($tnt=='teaching' && $employmentstat=='PRTTIM') {
				$info[$eid]['whtax'] = $info[$eid]['netbasicpay'] * ($wh_tax/100);
			}else if($tnt=='nonteaching' && $employmentstat=='EL'){
				$info[$eid]['whtax'] = $info[$eid]['netbasicpay'] * ($wh_tax/100);
			}else{
				$info[$eid]['whtax'] = $wh_tax;
			}
		} else {
			// $info[$eid]['whtax']  = $this->comp->computeWithholdingTax($schedule,$dependents,$info[$eid]['netbasicpay'],$info[$eid]['income'],$info[$eid]['income_adj'],$info[$eid]['deduction'],$info[$eid]['fixeddeduc'],$info[$eid]['overtime'],$info[$eid]['provident_premium']);
			if($quarter == 1){
				$year = date("Y", strtotime($sdate));
				$info[$eid]['whtax'] = $this->taxComputation($eid, $info[$eid]['fixeddeduc'], $info[$eid]['deduction'], $str_fixeddeduc, $info[$eid]['salary'], $info[$eid]["provident_premium"], $basic_personal_exception, $year, $sdate, $edate); //PAYROLLCOMP
			}else{
				$year = date("Y", strtotime($sdate));
				list($excess_percent, $last_annual) = $this->latestTaxExcess($eid, $year);
				$info[$eid]['salary'] = $info[$eid]['netbasicpay'] = $info[$eid]["grosspay"] = array_sum($info[$eid]["income"]);
				$info[$eid]['whtax'] = array_sum($info[$eid]["income"]) * (intval($excess_percent) / 100);
				$new_annual = $info[$eid]['salary'] + $last_annual;
				if($new_annual > $last_annual){
					$tax_config_q = $this->db->query("SELECT * FROM code_yearly_tax WHERE '{$new_annual}' BETWEEN tib_from AND tib_to AND year = '$year'");
					if($tax_config_q->num_rows() > 0){
						$tax_config = $tax_config_q->row(0);
						$info[$eid]['whtax'] = (( $info[$eid]['salary'] ) * ($tax_config->of_excess_over/100) );
					}
				}
			}
		}
		$info[$eid]['whtax'] = isset($info[$eid]['whtax']) ? $info[$eid]['whtax'] : 0;
		//<!--NET PAY-->
		$info[$eid]['netpay'] = ($info[$eid]['grosspay'] - $totalloan - $totalfix - $total_deducSub - $info[$eid]['whtax'] - $total_deducAdd);

		$info[$eid]['isHold'] = 0;

		if($tnt == "nonteaching"){
			$cto = $this->getUseCTOApplications($eid, $sdate, $edate);
			$ctoDaily = round($this->time->exp_time($cto) / 28800, 0);
			$ctoExcess = $this->time->exp_time($cto) % 28800;
			list($regpay, $daily) = $this->getEmployeeSalaryRate($regpay, $daily, $eid, $sdate);
			
			$ctoExcess = ($ctoExcess / 60) * $minutely;
			$ctopayment = ($daily * $ctoDaily) + $ctoExcess;
			$info[$eid]['absents'] = $info[$eid]['absents'] - $ctopayment;
			$info[$eid]['absents'] = ($info[$eid]['absents'] < 0) ? 0 : $info[$eid]['absents'];
			// $info[$eid]['grosspay'] = $info[$eid]['grosspay'] + $ctopayment;
			// $info[$eid]['netbasicpay'] = $info[$eid]['netbasicpay'] + $ctopayment;
			// $info[$eid]['netpay'] = $info[$eid]['netpay'] + $ctopayment;
		}

		if ($this->isEmployeeTeachingOnly($eid) && $employmentstat == "PRTTIM") $info[$eid]['salary'] = 0;
		if ($this->isEmployeeTeachingOnly($eid) && $employmentstat != "PRTTIM") $info[$eid]['teaching_pay'] = 0;
		$info[$eid]['teaching_pay'] = $info[$eid]['parrtime_pay'];


		// PROVIDENT APPLY TO NET PAY 
		if($is_provident_premium) $info[$eid]['netpay'] -= $info[$eid]["provident_premium"];

		///< save to computed table
		$data_tosave = $data_tosave_oth = array();
		$data_tosave['cutoffstart'] 	= $sdate;
		$data_tosave['cutoffend'] 		= $edate;
		$data_tosave['employeeid'] 		= $eid;
		$data_tosave['schedule'] 		= $schedule;
		$data_tosave['quarter'] 		= $quarter;
		$data_tosave['salary'] 			= $info[$eid]['salary'];
		$data_tosave['pera'] 	= $info[$eid]['pera'];
		$data_tosave['teaching_pay'] 	= $info[$eid]['teaching_pay'];
		$data_tosave['overtime'] 		= $info[$eid]['overtime'];
		$data_tosave['substitute'] 		= isset($info[$eid]['substitute']) ? $info[$eid]['substitute'] : "";
		$data_tosave['income'] 			= $str_income;
		$data_tosave['monetize_id'] 			= $str_monetize_id;
		$data_tosave['income_adj'] 		= $str_income_adj;
		$data_tosave['fixeddeduc'] 		= $str_fixeddeduc;
		$data_tosave['otherdeduc'] 		= $str_deduc;
		$data_tosave['loan'] 			= $str_loan;
		$data_tosave['withholdingtax'] 	= $info[$eid]['whtax'];
		$data_tosave['tardy'] 			= $info[$eid]['tardy'];
		$data_tosave['absents'] 		= $info[$eid]['absents'];
		$data_tosave['netbasicpay'] 	= $info[$eid]['netbasicpay'];
		$data_tosave['gross'] 			= $info[$eid]['grosspay'];
		$data_tosave['net'] 			= $info[$eid]['netpay'];
		$data_tosave['isHold'] 			= $info[$eid]['isHold'];
		$data_tosave['provident_premium'] = $info[$eid]['provident_premium'];
		$data_tosave['vl_balance'] = $vl_balance;
		$data_tosave['t_overload'] = $t_overload;

		$data_tosave_oth['perdept_amt_arr'] = $perdept_amt_arr;
		$data_tosave_oth['ee_er'] 		= $ee_er;
		$data_tosave_oth['ot_det'] 		= $ot_det;

		$info[$eid]['base_id'] = $this->savePayrollCutoffSummaryDraft($data_tosave,$data_tosave_oth);

		// echo "<pre>"; print_r($str_income); 

		return array($info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config);
	}

	
	function has_birthday_leave($sdate, $edate, $eid){
		return $this->db->query("SELECT * FROM leave_request WHERE fromdate BETWEEN '$sdate' AND '$edate' AND employeeid = '$eid'")->num_rows();
	}
	
	function minimum_wage($cutoffstart){
		$year = date("Y", strtotime($cutoffstart));
		$q_wage = $this->db->query("SELECT amount FROM payroll_wage_config WHERE year = '$year'");
		if($q_wage->num_rows() > 0) return $q_wage->row()->amount;
		else{
			$q_wage2 = $this->db->query("SELECT amount FROM payroll_wage_config ORDER BY year DESC LIMIT 1");
			if($q_wage2->num_rows() > 0){
				return isset($q_wage2->row()->amount) ? $q_wage2->row()->amount : 0;
			}else{
				return false;
			}
		}
	}
	
	function isTeachingRelated($user = ""){
		$query = $this->db->query("SELECT teachingtype, trelated FROM employee WHERE employeeid='$user'");
		return $query->row(0)->teachingtype == 'nonteaching' && $query->row(0)->trelated == '1';
	}
	
	function getPerdeptSalaryHistory($employeeid='',$payroll_cutoff_from=''){
		$perdept_salary = array();
		$base_id = '';
		$base_res = $this->db->query("SELECT a.id FROM payroll_employee_salary_history a INNER JOIN payroll_emp_salary_perdept_history b ON a.id = b.base_id WHERE a.employeeid='$employeeid' AND a.date_effective <= '$payroll_cutoff_from' AND a.status = '1' ORDER BY a.date_effective DESC, a.timestamp DESC LIMIT 1"); 
		// from order by date_effective ASC to DESC


		if($base_res->num_rows() > 0) $base_id = $base_res->row(0)->id;
		if($base_id){
			$res = $this->db->query("SELECT * FROM payroll_emp_salary_perdept_history WHERE base_id='$base_id'");
			foreach ($res->result() as $key => $row) {
				if($row->aimsdept == "all"){
					$load_arr = $this->employeeScheduleList($employeeid); // SCHEDULE
					foreach($load_arr as $sched_aimsdept => $sched_r){
						$perdept_salary[$sched_aimsdept][$row->classification] = array('lechour'=>$row->lechour,'labhour'=>$row->labhour,'rlehour'=>$row->rlehour);
					}
				}else{
					$perdept_salary[$row->aimsdept][$row->classification] = array('lechour'=>$row->lechour,'labhour'=>$row->labhour,'rlehour'=>$row->rlehour);
				}
			}
		}
		return $perdept_salary;
	}

	public function employeeScheduleList($employeeid){
		$sched_list = array();

		$q_sched = $this->db->query("SELECT employeeid, aimsdept, campus FROM employee_schedule_history WHERE employeeid = '$employeeid' AND aimsdept IS NOT NULL AND aimsdept != '' GROUP BY aimsdept  ");
		if($q_sched->num_rows() > 0){
			foreach($q_sched->result() as $scheds){
				$sched_list[$scheds->aimsdept]["lechour"] = "";
				$sched_list[$scheds->aimsdept]["labhour"] = "";
				$sched_list[$scheds->aimsdept]["rlehour"] = "";
				$sched_list[$scheds->aimsdept]["campus"] = $scheds->campus;
			}
		}
		return $sched_list;
	}
	
	function getTardyAbsentSummaryTeaching($empid = "",$ttype="",$schedule = "",$quarter = "",$sdate = "",$edate = "",$hourly=0,$lechour=0,$labhour=0,$rlehour=0,$perdept_salary=array(),$force_useHourly=false,$semimonthly=0, $employmentstat='', $is_trelated='', $absent_rate=0, $daily=0){
		$remaining_balance = 0;
		
		$tardy_amount = $absent_amount = $tardy_lec = $tardy_lab = $tardy_admin = $tardy_rle = $absent_lec = $absent_lab = $absent_admin = $absent_rle = 0;
		$workhours_lec = $workhours_lab = $workhours_admin = $workhours_rle = $hold_status = 0;
		$isFinal = 0;
		$tot_min = 0;
		$hasZeroRate = 0;
		$total_tardy_min = $total_absent_min = 0;
		$t_overload = 0;
		$min_lec = $lechour / 60;
		$min_lab = $labhour / 60;
		$min_admin = $hourly / 60;
		$min_rle = $rlehour / 60;

		$teaching_absent_rate = ($absent_rate / 7) / 60;

		$perdept_amt_arr = array();
			    
		$base_id = '';
    	$detail_q = $this->db->query("SELECT id ,latelec, latelab, lateadmin, laterle, deduclec, deduclab, deducadmin, deducrle, workhours_lec, workhours_lab, workhours_admin, workhours_rle , hold_status_change, isFinal, t_overload, absent
    									FROM attendance_confirmed 
    									WHERE employeeid='$empid' AND payroll_cutoffstart='$sdate' AND payroll_cutoffend='$edate' 
    											AND `status`='PROCESSED' AND forcutoff=1
										ORDER BY cutoffstart DESC");

    	if($detail_q->num_rows() > 0){
    		$tlec = $tlab = $tadmin = $trle = $tdlec = $tdlab = $tdadmin = $tdrle = 0;
    		$hold_status = $detail_q->row(0)->hold_status_change;
    		$isFinal = $detail_q->row(0)->isFinal;
    		$t_overload = $detail_q->row(0)->t_overload;
    		if($t_overload != 0){
    			$t_overload = ($this->time->hoursToMinutes($detail_q->row(0)->t_overload) * $min_lec);
    		}
    		
    		if($hold_status != 'ALL'){
    			if($is_trelated){
    				$absent_amount = $detail_q->row(0)->absent * $absent_rate;
    			}
	    		///< workhours will refer to latest  cutoff
	    		$workhours_lec 	= $detail_q->row(0)->workhours_lec;
	    		$workhours_lab 	= $detail_q->row(0)->workhours_lab;
	    		$workhours_admin 	= $detail_q->row(0)->workhours_admin;
	    		$workhours_rle 	= $detail_q->row(0)->workhours_rle;


				
	    		foreach ($detail_q->result() as $key => $row) {
									// echo "<pre>"; print_r($detail_q->result()); die;

	    			///< for cases of more than 1 dtr cutoff per 1 payroll cutoff
	    			///< sum up tardy and absent
	    			$base_id 	= $row->id;
					$perdept_q = $this->db->query("SELECT work_hours, late_hours, deduc_hours, `type`, aimsdept, leave_project, classification FROM workhours_perdept WHERE base_id='$base_id' ORDER BY type ASC");
	    			$perdept_list = $perdept_q->result();
	    			/*get included only in computation -- this is for nursing department*/
	    			if($this->isNursingDepartment($empid) > 0 && !$this->isNursingExcluded($empid)){
	    				$nursing_included = $this->nursingIncludedPerdept($perdept_q->result());
	    				$perdept_list = $nursing_included;
	    			}
	    			foreach ($perdept_list as $key_dept => $row_dept) {
	    				$leave_project = $row_dept->leave_project;
						$aimsdept = $row_dept->aimsdept;
						// foreach ($perdept_salary as $key => $v) $aimsdept = $key;
	    				$type = $row_dept->type;
	    				$type_rate = '';
	    				if($type == "LEC") $type_rate = "lechour";
	    				if($type == "LAB") $type_rate = "labhour";
	    				if($type == "RLE") $type_rate = "rlehour";

	    				if( ($type == 'LEC' && $type == 'LAB' && $type == 'RLE') || $hold_status != 'LECLAB' ){
	    					if($employmentstat != "PRTTIM"){
	    						if($type == "ADMIN"){
	    							if($daily){
	    								$rate_min = ($daily / 8) / 60;
	    							}else{
	    								$rate_min = $min_admin;
	    							}
	    						}else{
	    							if($row_dept->classification == "7" || $row_dept->classification == "8" || $row_dept->classification == "9") $rate_min = isset($perdept_salary[$aimsdept][$row_dept->classification][$type_rate]) ? ($perdept_salary[$aimsdept][$row_dept->classification][$type_rate] / 60): 0;
	    							else $rate_min = $min_admin;
	    						}
	    					}else{
	    						if($type == 'ADMIN'){
									if($daily){
	    								$rate_min = ($daily / 8) / 60;
	    							}else{
	    								$rate_min = $min_admin;
	    							}
								}else{
									$rate_min = isset($perdept_salary[$aimsdept][$row_dept->classification][$type_rate]) ? ($perdept_salary[$aimsdept][$row_dept->classification][$type_rate] / 60): 0;
								}
	    					}



								
			    				if($force_useHourly) $rate_min = $min_admin;
			    				if($rate_min == 0){
			    					$hasZeroRate++;
			    				}
			    				

			    				$late_min = $this->time->hoursToMinutes($row_dept->late_hours);
			    				// list($late_min, $remaining_balance) = $this->removeLateUTByVL($empid, $edate, $late_min);
			    				$deduc_min = $this->time->hoursToMinutes($row_dept->deduc_hours);
		    					


			    				if($leave_project){
			    					$workhours_tmp = $this->time->exp_time($row_dept->work_hours);
			    					$leave_project_tmp = $this->time->exp_time($leave_project);
			    					$workhours_f = $workhours_tmp - $leave_project_tmp;
			    					$row_dept->work_hours = $this->time->sec_to_hm($workhours_f);
								}
								
								$secondsWorkHours = $this->time->exp_time($row_dept->work_hours);
								$secondsLateHours = $this->time->exp_time($row_dept->late_hours);
								$secondsDeducHours = $this->time->exp_time($row_dept->deduc_hours);

								// $totalHours = $this->time->sec_to_hm($secondsWorkHours + $secondsLateHours + $secondsDeducHours);
								$totalHours = $this->time->sec_to_hm($secondsWorkHours);
			    				// $work_amt = ($type == 'ADMIN') ? 0 : ($this->time->hoursToMinutes($row_dept->work_hours) * $rate_min); //< no perdept work amount if type=ADMIN

								$work_amt = ($this->time->hoursToMinutes($totalHours) * $rate_min);

			    				$late_amt = $late_min * $rate_min;
			    				if($employmentstat != "PRTTIM"){
			    					$deduc_amt = $deduc_min * $teaching_absent_rate;
			    				}else{
			    					$deduc_amt = $deduc_min * $rate_min;
			    				}
			    				

			    				/*remove late and absent of nursing because it is already taken ughhh*/
			    				/*if($this->extensions->isNursingDepartment($empid) > 0){
			    					$deduc_amt = $late_amt = 0;
			    				}*/

			    				if(!isset($perdept_amt_arr[$aimsdept][$row_dept->classification][$type]['work_amount'])) $perdept_amt_arr[$aimsdept][$row_dept->classification][$type]['work_amount'] = 0;
			    				if(!isset($perdept_amt_arr[$aimsdept][$row_dept->classification][$type]['late_amount'])) $perdept_amt_arr[$aimsdept][$row_dept->classification][$type]['late_amount'] = 0;
			    				if(!isset($perdept_amt_arr[$aimsdept][$row_dept->classification][$type]['deduc_amount'])) $perdept_amt_arr[$aimsdept][$row_dept->classification][$type]['deduc_amount'] = 0;
			    				$perdept_amt_arr[$aimsdept][$row_dept->classification][$type]['work_amount'] += $work_amt;
			    				$perdept_amt_arr[$aimsdept][$row_dept->classification][$type]['late_amount'] += $late_amt;
			    				$perdept_amt_arr[$aimsdept][$row_dept->classification][$type]['deduc_amount'] += $deduc_amt;

			    				$tardy_amount += $late_amt;
								if(!$is_trelated) $absent_amount += $deduc_amt;

			    				$total_tardy_min += $late_min;
			    				$total_absent_min += $deduc_min;
			    				// echo "<pre>"; print_r($deduc_min."-".$rate_min."-".$aimsdept."-".$type);
			    				$tot_min += (($this->time->hoursToMinutes($row_dept->work_hours) - $deduc_min - $late_min) > 0) ? ($this->time->hoursToMinutes($row_dept->work_hours) - $deduc_min - $late_min) : 0;
	    				}
	    			}

	    	// 		if(in_array($empDepartment, $separated_department)){
    		// 			$daily_rate = $this->getEmployeeDailySalary($empid);
						// $days_absent = $this->getEmployeeDayAbsent($empid, $sdate, $edate);
						// $perday_deduction = $daily_rate * $days_absent; #per day deduction
						// $absent_amount += $perday_deduction;
    		// 		}

	    		}
    		} // end if hold_status

    	}


	    /*for medicine department*/
	    $excess = 0;
    	if($this->isMedicineDepartment($empid) > 0){ // EXTENSIONS
	    	if($tot_min < 960){
	    		$less_workhours = 16 - ($tot_min / 60);
	    		$less_rate = $semimonthly / 16;
	    		$less_amount = $less_workhours * $less_rate;
	    		// var_dump($less_amount); die;
	    		if($less_amount) $absent_amount += $less_amount;
	    	}else{
	    		$excess = $tot_min - 960;
	    	}
		}

		
		// echo"<pre>";print_r(array($tardy_amount,$absent_amount,$workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$perdept_amt_arr,$hold_status,$total_tardy_min,$total_absent_min,$isFinal,$base_id,$excess,$remaining_balance, $t_overload, $hasZeroRate));die;

		
		return array($tardy_amount,$absent_amount,$workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$perdept_amt_arr,$hold_status,$total_tardy_min,$total_absent_min,$isFinal,$base_id,$excess,$remaining_balance, $t_overload, $hasZeroRate);
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

	function nursingIncludedPerdept($perdept){
		/*validate to make rle first and lab 2nd*/
		$validated_perdept = array("RLE" => array(), "LAB" => array(), "LEC" => array());
		
		foreach($perdept as $perdepts){
			$perdepts = (array) $perdepts;
			if($perdepts["type"] == "RLE") $validated_perdept["RLE"][] = $perdepts;
			elseif($perdepts["type"] == "LAB") $validated_perdept["LAB"][] = $perdepts;
			else $validated_perdept["LEC"][] = $perdepts;
		}

		$filtered_perdepts = array();
		foreach($validated_perdept as $sorted_perdepts){
			foreach($sorted_perdepts as $perdeptss){
				$workhours = $this->time->exp_time($perdeptss["work_hours"]);
				$deduchours = $this->time->exp_time($perdeptss["deduc_hours"]);
				$latehours = $this->time->exp_time($perdeptss["late_hours"]);
				$workhours = $workhours - $deduchours - $latehours;
				$perdeptss["work_hours"] = $this->time->sec_to_hm($workhours);
				$filtered_perdepts[] = (object) $perdeptss;
			}
		}

		$new_perdept = array();
		$to_deduc = 2880;
		if($filtered_perdepts){
			foreach($filtered_perdepts as $key => $row){
				$dept_min = $this->time->hoursToMinutes($row->work_hours);
				if($to_deduc >= $dept_min){
					$to_deduc -= $dept_min;
				}else{
					$countable = $dept_min - $to_deduc;
					$to_deduc -= $to_deduc;
					$new_perdept[$key] = $row;
					$new_perdept[$key]->work_hours = $this->time->minutesToHours($countable);
				}
			}
		}

		return (object) $new_perdept;
	}

	function computeTeachingCutoffSalary($workhours_lec='',$workhours_lab='',$workhours_admin='',$workhours_rle='',$hourly=0,$lechour=0,$labhour=0,$rlehour=0,$fixedday=0,$regpay=0,$perdept_amt_arr=array(),$hold_status='',$excess_min=0,$hasleave=false,$minimum_wage=0, $is_trelated=''){
		$salary = 0;
		$perdept_amount = 0;
		$parttime_amount = 0;
		$admin_work = 0;
		$overload_amount = 0;
		if(sizeof($perdept_amt_arr) > 0){
			foreach ($perdept_amt_arr as $aimsdept => $classification_arr) {
				foreach ($classification_arr as $classification => $leclab_arr) {
					foreach ($leclab_arr as $type => $amt) {
						if($is_trelated){
							if ($type != 'ADMIN'){
								if($classification == "7" || $classification == "8" || $classification == "9") $parttime_amount += $amt['work_amount'];
								else if($classification == "1") $overload_amount += $amt['work_amount'];
								else $perdept_amount += $amt['work_amount'];
							}
							else{
								$admin_work += $amt['work_amount'];
							}
						}else{
							if($classification == "7" || $classification == "8" || $classification == "9") $parttime_amount += $amt['work_amount'];
							else if($classification == "1") $overload_amount += $amt['work_amount'];
							else $perdept_amount += $amt['work_amount'];
						}
					}
				}
			}
		}
		

		if($hold_status == 'ALL') 			$regpay = $perdept_amount = $parttime_amount = $overload_amount = 0;
		elseif($hold_status == 'LECLAB') 	$perdept_amount = $parttime_amount = $overload_amount = 0;
		
		if($fixedday){
			$hourly = $hourly;
			$minutely = $hourly / 60;
			$excess_amt = 0;
			$minutely = $minutely;
			if($excess_min > 0) $excess_amt = $minutely * $excess_min;
			$salary += $perdept_amount + $excess_amt;
		}else{
			$salary = $perdept_amount;
			/*remove regpay if not monthly rate*/
			$regpay = 0;
		}
		/*add minimum wage for bday leave*/
		if($hasleave && $salary > 0){
			$salary += $minimum_wage;

		}

		if(!$is_trelated){
			// $regpay += $salary; //  URSHYP-1701
		}

		
		return array($regpay, $salary, $parttime_amount, $overload_amount);
	}

	function getProjectHolidayPay($payroll_cutoff_id,$lechour,$labhour,$rlehour,$eid,$payroll_cutoff_from){
		$rate = "";
		$lec = $lab = $admin = "";
		$lec_amount = $lab_amount = 0;
		$tot_lab_amount = $tot_lec_amount = $tot_rle_amount =  $tot_admin_amount = 0;
		$sub_lab_amount = $sub_lec_amount = $sub_rle_amount = $sub_admin_amount = 0;
		$teachingtype = $this->getEmployeeTeachingType($eid); // EXTENSIONS
		$deptid = $this->getEmployeeOffice($eid); // EXTENSIONS
		list($from_date, $to_date) = $this->getDTRCutoffByPayrollCutoffID($payroll_cutoff_id); // EXTENSIONS
		if($from_date && $to_date){
			$qdate = $this->displayDateRange($from_date, $to_date); //ATTCOMPUTE
			foreach($qdate as $rdate){
				$date = $rdate->dte;
				$holiday = $this->isHolidayNew($eid,$date,$deptid); //ATTCOMPUTE
	            if($holiday){
	                $holidayInfo = $this->holidayInfo($date);
	                if($holidayInfo) $rate = $this->getHolidayTypeRate($holidayInfo["holiday_type"], $teachingtype); // EXTENSIONS
	            
					$q_detailed = $this->db->query("SELECT * FROM employee_attendance_detailed WHERE sched_date = '$date' AND employeeid = '$eid' ");
					if($q_detailed->num_rows() > 0){
						$lec = $q_detailed->row()->lec;
						$lab = $q_detailed->row()->lab;
						$admin = $q_detailed->row()->admin;
						$rle = $q_detailed->row()->rle;
						$lec_hours = $this->constructArrayListFromAttendanceDetailed($lec);
						$lab_hours = $this->constructArrayListFromAttendanceDetailed($lab);
						$admin_hours = $this->constructArrayListFromAttendanceDetailed($admin);
						$rle_hours = $this->constructArrayListFromAttendanceDetailed($rle);
						if($lec_hours){
							foreach($lec_hours as $count_lec => $lec_data){
								$lec_data["aimsdept"] = isset($lec_data["aimsdept"]) ? $lec_data["aimsdept"] : 0;
								$lec_data["deduc_hours"] = isset($lec_data["deduc_hours"]) ? $lec_data["deduc_hours"] : 0;
								list($lechour, $labhour, $rlehour) = $this->getPerdeptSalaryByID($eid, $payroll_cutoff_from, $lec_data["aimsdept"]);
								$lec_tothours = $lec_data["work_hours"] - ($lec_data["deduc_hours"] - $lec_data["late_hours"]);
								$lec_tothours = $lec_tothours / 60;
								$lec_amount = $lec_tothours * ($lechour / 60);
								if($holidayInfo["holiday_type"]==5) $lec_amount /= 2;
								$sub_lec_amount += $lec_tothours * ($lechour / 60);
								$tot_lec_amount += $lec_amount * $rate / 100;
							}
						}
						if($lab_hours){
							foreach($lab_hours as $count_lab => $lab_data){
								list($lechour, $labhour, $rlehour) = $this->getPerdeptSalaryByID($eid, $payroll_cutoff_from, $lab_data["aimsdept"]);
								$lab_tothours = $lab_data["work_hours"] - ($lab_data["deduc_hours"] - $lab_data["late_hours"]);
								$lab_tothours = $lab_tothours / 60;
								$lab_amount = $lab_tothours * ($labhour / 60);
								if($holidayInfo["holiday_type"]==5) $lab_amount /= 2;
								$sub_lab_amount += $lab_tothours * ($labhour / 60);
								$tot_lab_amount += $lab_amount * $rate / 100;
							}
						}
						if($admin_hours){
							foreach($admin_hours as $count_admin => $admin_data){
								list($lechour, $adminhour, $rlehour) = $this->getPerdeptSalaryByID($eid, $payroll_cutoff_from, $admin_data["aimsdept"]);
								$admin_tothours = $admin_data["work_hours"] - ($admin_data["deduc_hours"] - $admin_data["late_hours"]);
								$admin_tothours = $admin_tothours / 60;
								$admin_amount = $admin_tothours * ($adminhour / 60);
								if($holidayInfo["holiday_type"]==5) $admin_amount /= 2;
								$sub_admin_amount += $admin_tothours * ($adminhour / 60);
								$tot_admin_amount += $admin_amount * $rate / 100;
							}
						}
						if($rle_hours){
							foreach($rle_hours as $count_rle => $rle_data){
								list($lechour, $labhour, $rlehour) = $this->getPerdeptSalaryByID($eid, $payroll_cutoff_from, $rle_data["aimsdept"]);
								$rle_tothours = $rle_data["work_hours"] - ($rle_data["deduc_hours"] - $rle_data["late_hours"]);
								$rle_tothours = $rle_tothours / 60;
								$rle_amount = $rle_tothours * ($rlehour / 60);
								if($holidayInfo["holiday_type"]==5) $rle_amount /= 2;
								$sub_rle_amount += $rle_tothours * ($rlehour / 60);
								$tot_rle_amount += $rle_amount * $rate / 100;
							}
						}
					}
	            }
			}
		}

		return array($tot_lab_amount + $tot_lec_amount, $sub_lab_amount + $sub_lec_amount);
	}

	function getPerdeptSalaryByID($employeeid, $payroll_cutoff_from, $aimsdept){
		$perdept_salary = array();
		$salaryid = '';
		$base_res = $this->db->query("SELECT id FROM payroll_employee_salary_history WHERE employeeid='$employeeid' AND date_effective <= '$payroll_cutoff_from' ORDER BY date_effective DESC LIMIT 1");
		if($base_res->num_rows() > 0) $salaryid = $base_res->row(0)->id;

		$q_salary = $this->db->query("SELECT * FROM `payroll_emp_salary_perdept_history` WHERE base_id = '$salaryid' AND employeeid = '$employeeid' AND aimsdept = '$aimsdept' ");
		if($q_salary->num_rows() > 0) return array($q_salary->row()->lechour, $q_salary->row()->labhour, $q_salary->row()->rlehour);
		else return array(0, 0, 0);
	}
	
	function constructArrayListFromAttendanceDetailed($str=''){
	    $arr = array();
	    if($str){
	        $str_base = explode('&', $str);
	        if(count($str_base)){
	        	foreach($str_base as $count => $str_arr){
	        		$str_arr = explode("/", $str_arr);
		            foreach ($str_arr as $i_temp) {
		                $str_arr_temp = explode('=', $i_temp);
		                if(isset($str_arr_temp[0]) && isset($str_arr_temp[1])){
		                	$str_arr_temp[1] = $str_arr_temp[1] != "" ? $str_arr_temp[1] : 0;
		                    $arr[$count][$str_arr_temp[0]] = $str_arr_temp[1];
		                }
	        		}
	            }
	        }
	    }
	    return $arr;
	}
	
	public function getHolidayTypeRate($holiday_type, $teachingtype){
		$q_holiday = $this->db->query("SELECT * FROM code_holiday_type WHERE holiday_type = '$holiday_type'");
		if($q_holiday->num_rows() > 0){
			if($teachingtype == "teaching") return $q_holiday->row()->t_rate;
			else return $q_holiday->row()->nt_rate;
		}
	}

    function holidayInfo($date=""){
        $return=array();
        $sql = $this->db->query("SELECT a.withPay, a.holiday_type, a.description, b.hdescription, b.code, a.holiday_rate
        FROM code_holiday_type a
        LEFT JOIN code_holidays b ON a.`holiday_type` = b.holiday_type
        LEFT JOIN code_holiday_calendar c ON b.`holiday_id` = c.holiday_id
        WHERE '$date' BETWEEN c.date_from AND c.date_to");
        foreach($sql->result() as $row)
        {
            $return["holiday_type"] = $row->holiday_type;
            $return["withPay"] = $row->withPay;
            $return["type"] = $row->description;
            $return["description"] = $row->hdescription;
            $return["code"] = $row->code;
            $return["holiday_rate"] = $row->holiday_rate;
        }
        return $return;
    }

	public function getEmployeeTeachingType($employeeid){
		$q_type = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
		if($q_type->num_rows() > 0) return $q_type->row()->teachingtype;
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
	
    function displayDateRange($dfrom = "",$dto = ""){
        $date_list = [];
        if ($dfrom && $dto) {
            $start = new DateTime($dfrom);
            $end = new DateTime($dto);
            $end->modify('+1 day'); // To include the last day in the range
            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start, $interval, $end);

            foreach ($period as $value) {
                $date_list[] = (object) ['dte' => $value->format('Y-m-d')];
            }
        }   
        return $date_list;
    }

	function isHolidayNew($empid,$date,$deptid,$campus="",$halfday="",$teachingtype=""){
        $where_clause = "";
        if($teachingtype && $teachingtype!="all") $where_clause = " AND teaching_type = '$teachingtype'";
        $sql = $this->db->query("SELECT a.holiday_id,a.date_from,a.date_to FROM code_holiday_calendar a INNER JOIN code_holidays b ON a.holiday_id = b.holiday_id WHERE '$date' BETWEEN a.date_from AND a.date_to AND (a.halfday = '$halfday' OR a.halfday IS NULL) ");
        if($sql->num_rows() > 0){

            
            $paymentType = "";
            $holiday_id = $sql->row(0)->holiday_id;
            $query = $this->db->query("SELECT * from employee where employeeid = '{$empid}'");
            // echo "<pre>";print_r($empid);die;
            $employmentstat = $query->row(0)->employmentstat;
            $statusemp = $this->db->query("SELECT * from code_status where description = '{$employmentstat}'");
            if ($statusemp->num_rows() > 0) {
                $employmentstat = $statusemp->row(0)->code;
            }
            $campusid = $query->row(0)->campusid;
            $teachingtype = $query->row(0)->teachingtype;
            $holiday = $this->db->query("SELECT * FROM code_holidays WHERE holiday_id = '$holiday_id'")->result();

            $Ptype = $this->db->query("SELECT fixedday FROM payroll_employee_salary WHERE employeeid = '{$empid}'");

            if ($Ptype->num_rows() > 0) {
                $paymentType = $Ptype->row(0)->fixedday;
            }

            if(isset($holiday[0]->campus)){

                if ($holiday[0]->campus == "All" OR $holiday[0]->campus == "" OR $holiday[0]->campus == $campusid) {

                    if ($holiday[0]->teaching_type == "all" OR $holiday[0]->teaching_type == $teachingtype) {

                        if ($holiday[0]->payment_type == "all" OR $holiday[0]->payment_type == $paymentType) {

                            $que = $this->db->query("SELECT status_included from holiday_inclusions where holi_cal_id = '{$holiday_id}' AND dept_included = '{$deptid}' AND status_included IS NOT NULL");
            // echo "<pre>"; print_r($this->db->last_query()); die;
                            if($que->num_rows() > 0)
                            {
                                $return = false;
                                foreach(explode(", ",$que->row(0)->status_included) as $k => $v)
                                {
                                    $include = explode("~",$v);
                                    if(isset($include[1]) && $include[1] == $employmentstat)
                                    {
                                        $return = $include[1];
                                        break;
                                    }
                                }
                                return $return;
                            }
                        }
                    }
                }
            }

            else { return false; }
        }
        else{   return false;}
    }

	function computeSubstitute($empid,$id=''){
		$substitutepay = 0;
		if($id){
			$q_substitute = $this->utils->getSingleTblData("attendance_confirmed_substitute_hours",array('*'),array('base_id'=>$id));
			
			foreach ($q_substitute->result() as $row) {
				list($lec, $lab) = $this->aimsdeptSalaryRate($empid, $row->type);
				$lec /= 60;
				$substitute_hours = $row->hours;
				$substitute_minute = $this->time->hoursToMinutes($substitute_hours);
				if($row["holiday"]){
					if($row["holiday"] == "2"){
						$substitute_minute /= 2;
					}elseif($row["holiday"] == "9"){
						$substitute_minute = 0;
					}
				}
				$substitutepay += ($substitute_minute * $lec);

			}
		}
		return $substitutepay;
	}

	public function aimsdeptSalaryRate($employeeid, $aimdept){
		$lec = $lab = 0;
		$q_rate = $this->db->query("SELECT * FROM `payroll_emp_salary_perdept_history` WHERE employeeid = '$employeeid' AND aimsdept = '$aimdept' LIMIT 1");
		if($q_rate->num_rows() > 0){ 
			$lec = $q_rate->row()->lechour;
			$lab = $q_rate->row()->labhour;
			$rle = $q_rate->row()->rlehour;
		}

		return array($lec, $lab);
	}

	function getSingleTblData($tbl='',$fields=array(),$filter=array(),$order_by='',$limit=0){
        $this->db->select($fields);
        if($order_by) $this->db->order_by($order_by);
        if($limit) 		$this->db->limit($limit);
        if(sizeof($filter) > 0) $data_q = $this->db->get_where($tbl,$filter); 
        else 					$data_q = $this->db->get($tbl); 
        return $data_q;
        
    }

	
    public function saveHolidayPay($project_hol_pay, $eid, $sdate, $edate, $cutoff_period){
		$this->db->query("DELETE FROM employee_income WHERE employeeid = '$eid' AND code_income = '7' ");
    	$emp_income = $this->db->query("SELECT * FROM employee_income WHERE employeeid = '$eid' AND datefrom = '$sdate' AND dateto = '$edate' AND code_income = '7' ");
    	if($emp_income->num_rows() == 0) $this->db->query("INSERT INTO employee_income (employeeid, code_income, datefrom, dateto, amount, nocutoff, schedule, cutoff_period, visibility) VALUES ('$eid', '7', '$sdate', '$edate', '$project_hol_pay', '1', 'semimonthly', '$cutoff_period', 'SHOW')");
    	else $this->db->query("UPDATE employee_income SET amount = '$project_hol_pay' WHERE employeeid = '$eid' AND datefrom = '$sdate' AND dateto = '$edate' AND code_income = '7' ");
    }

	function getTardyAbsentSummaryNT($empid = "",$ttype="",$schedule = "",$quarter = "",$sdate = "",$edate = "",$hourly=0,$useDTRCutoff=false,$daily=0, $monthlySalary=0, $date="", $is_pera="", $fixedday=0, $daily2=0){
		$minutely = $tardy_amount = $absent_amount = $tardy = $ut = $absent = $isFinal = $workdays = 0;
		$base_id = '';
		
		$wC = '';
		if($useDTRCutoff){
			$wC .= " AND cutoffstart='$sdate' AND cutoffend='$edate'";
		}else{
			$wC .= " AND payroll_cutoffstart='$sdate' AND payroll_cutoffend='$edate'";
		}

		// echo $sdate . ' ' . $edate;
	  
    	$detail_q = $this->db->query("SELECT id, cutoffstart, lateut, ut, absent, day_absent, workdays, isFinal FROM attendance_confirmed_nt WHERE employeeid='$empid' $wC");

		// print_r($this->db->last_query());
		

    	$day_absent = 0;
    	if($detail_q->num_rows() > 0){
			$daysInMonth = date('t', strtotime($detail_q->row(0)->cutoffstart));
			$minutely = $monthlySalary / $daysInMonth / 8 / 60;


    		$base_id 	= $detail_q->row(0)->id;

    		$tlec 		= $detail_q->row(0)->lateut;
    		$utlec 		= $detail_q->row(0)->ut;
    		$tabsent 	= $detail_q->row(0)->absent;

    		$workdays 	= $detail_q->row(0)->workdays;
    		$isFinal 	= $detail_q->row(0)->isFinal;

	        $tardy = $this->time->exp_time($tlec);
	        $ut = $this->time->exp_time($utlec);
	        $absent = $this->time->exp_time($tabsent);
	        $day_absent 	= $detail_q->row(0)->day_absent;
    	}

    	if(!$fixedday){
			$minutely = $daily2 / 8 / 60;
		}



	    $tardy      	= $this->time->hoursToMinutes($this->time->sec_to_hm($tardy)) + $this->time->hoursToMinutes($this->time->sec_to_hm($ut));

		// print_r($tardy);die('here');
	    /*remove tardy and undertime using VL credits*/
		list($tardy, $remaining_balance) = $this->removeLateUTByVL($empid, $edate, $tardy);

	    $absent      	= $this->time->hoursToMinutes($this->time->sec_to_hm($absent));
	    $absent_hour = $absent / 60;

		$tardy_amount     = number_format($tardy * $minutely,2,'.', '');
		$daily_pera = $is_pera ? (2000 / 22) : 0;
		// echo "<pre>"; print_r($daily);
		// $day_absent = count(explode('/', $day_absent));

	    $absent_amount     = number_format($day_absent * ($daily2 + $daily_pera),2,'.', '');

	    if($fixedday){
			$absent_amount     = number_format($day_absent * ($daily2 + $daily_pera),2,'.', '');
		}

		return array($tardy_amount,$absent_amount,$workdays,$tardy,$absent,$base_id, $isFinal, $remaining_balance);
	}

	function removeLateUTByVL($employeeid, $payroll_date, $late_ut){
		$remaining_balance = 0;
		$q_credit = $this->db->query("SELECT * FROM employee_leave_credit WHERE employeeid = '$employeeid' AND '$payroll_date' BETWEEN dfrom AND dto AND leavetype = 'VL' ");
		if($q_credit->num_rows() > 0){
			foreach($q_credit->result() as $credit){
				$remaining_balance = $credit->balance;
				if($this->includedInLateUTRemoval($employeeid)){
					if($credit->balance > 0){
						if($credit->balanceType == "dy") $remaining_balance = ($credit->balance * 8) - ($late_ut / 60);
						else $remaining_balance = ($credit->balance) - ($late_ut / 60);

						// save trail of vl balance used for late/ut
						$user = $this->user;
						$used_balance = round($late_ut / 60, 3);
						// DELETE EXISTING
						$this->db->query("DELETE FROM vl_deduc_late_trail WHERE employeeid = '$employeeid' AND payroll_date = '$payroll_date' AND status = 'PENDING' ");
						$this->db->query("INSERT INTO vl_deduc_late_trail (employeeid, late_ut, balance_used, addedby, payroll_date) VALUES ('$employeeid', '$late_ut', '$used_balance', '$user', '$payroll_date') ");

						if($remaining_balance < 0){
							$late_ut =  ($late_ut) - ($credit->balance * 8 * 60);
							$remaining_balance = 0;
						}else{
							$late_ut = 0;
						}

						if($credit->balanceType == "dy") $remaining_balance = round($remaining_balance / 8, 3);
						else $remaining_balance = round($remaining_balance, 3);
					}
				}
			}
		}

		return array($late_ut, $remaining_balance);
	}

	public function includedInLateUTRemoval($employeeid){
		return $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' AND ( employmentstat IN ('PRMNT', 'CNTRCT', 'CSL') OR sep_type = 'VSL')")->num_rows();
	}
	
	function computeNTCutoffSalary($workdays=0,$fixedday=0,$regpay=0,$daily=0,$hasleave=false,$minimum_wage=0, $daily2=0){
		$salary = 0;
		if($fixedday){
			$salary = $regpay;
		}else{
			$salary = $workdays * $daily2;
		}

		if($hasleave && $salary > 0){
			// $salary -= $daily;
			// $salary += $minimum_wage;
		}

		// echo "<pre>"; print_r($regpay); die;
		
		return $salary;
	}

	public function getEmployeePERA($empid){
		$query = $this->db->query("SELECT personal_economic_relief_allowance FROM payroll_employee_salary WHERE employeeid = '$empid' LIMIT 1");
		if($query->num_rows() > 0){
			return $query->row()->personal_economic_relief_allowance;
		}else{
			return false;
		}
	}
	
	function hasAttendanceConfirmed($teachingtype='',$filter=array(), $is_trelated=''){
		$hasData = false;
		$tbl = '';
		if($teachingtype == 'teaching' || $is_trelated) $tbl = 'attendance_confirmed';
		elseif($teachingtype == 'nonteaching') $tbl = 'attendance_confirmed_nt';
		if($tbl){
			$this->db->select('id');
			$res = $this->db->get_where($tbl,$filter);
			if($res->num_rows() > 0) $hasData = true;
		}
		return $hasData;
	}

	function computeOvertime3($empid='',$tnt='teaching',$hourly=0,$base_id='',$employmentstat='',$monthlySalary,$date){
		$overtimepay = 0;
		$ot_det = array();
		// $daysInMonth = date('t', strtotime($date));
		$daysInMonth = 22;
		$hourly = $monthlySalary / $daysInMonth / 8;

		/*$hourly = number_format($hourly, 2, '.', '');
		$minutely_orig = $hourly / 60;

		$minutely_orig = number_format($minutely_orig, 2, '.', '');*/

		$setup = $this->getOvertimeSetup($employmentstat);
		// echo '<pre>';
		// print_r($setup);
		// echo '</pre>';

		$tbl = 'attendance_confirmed_ot_hours';
		if($tnt=='nonteaching') $tbl = 'attendance_confirmed_nt_ot_hours';

		if($base_id){
			$ot_q = $this->getSingleTblData($tbl,array('*'),array('base_id'=>$base_id));
			
			foreach ($ot_q->result() as $key => $row) {
				// print_r($row);die;
				$att_baseid = $row->id;

				$ot_hours = $row->ot_hours;
				$ot_type = $row->ot_type;
				$holiday_type = $row->holiday_type;
				$is_excess = $row->is_excess;

				$ot_min = $this->time->hoursToMinutes($ot_hours);
				$ot_hour = $ot_min / 60;
				
				$percent = 100; ///< default

				if(isset($setup[$employmentstat][$ot_type][$holiday_type][$is_excess])){ ///< get percent if has existing setup
					$percent = $setup[$employmentstat][$ot_type][$holiday_type][$is_excess];
				}

				$percent = $percent / 100;

				$hourly_rate = $hourly * $percent;
				$initial_pay = $hourly_rate * $ot_hour;

				$ot_det[$att_baseid] = $initial_pay; ///< insert later for overtime amount details

				$overtimepay += $initial_pay;

			}
		}

		return array($overtimepay,$ot_det);
	}

	function getOvertimeSetup($employmentstat=''){
		$filter = $setup = array();

		if($employmentstat) $filter['code_status'] = $employmentstat;
		$ot_q = $this->getSingleTblData('code_overtime',array('*'),$filter);

		foreach ($ot_q->result() as $key => $row) {
			$setup[$row->code_status][$row->ot_types] = array(
																'NONE' 		=> array('0'=>$row->percent,'1'=>$row->excess_percent),
																'REGULAR' 	=> array('0'=>$row->regular_percent,'1'=>$row->regular_percent_excess),
																'SPECIAL' 	=> array('0'=>$row->other_percent,'1'=>$row->other_percent_excess)
															);
		}
		return $setup;
	}

    public function isFlexiNoHours($empid){
        return $this->db->query("SELECT * FROM code_schedule a INNER JOIN employee b ON a.`schedid` = b.empshift WHERE flexible = 'YES' AND hours = 0 AND employeeid = '$empid'")->num_rows();
    }
	
	function validateDTRCutoff($sdate, $edate, $quarter){
		$q_cutoff = $this->db->query("SELECT a.nodtr FROM payroll_cutoff_config a INNER JOIN cutoff b ON a.CutoffID = b.CutoffID WHERE a.startdate = '$sdate' AND a.enddate = '$edate' AND a.quarter = '$quarter' ");
		if($q_cutoff->num_rows() > 0) return ($q_cutoff->row()->nodtr) ? true : false;
		else return false;
		
	}

	function computeEmployeeOtherIncome($employeeid='',$sdate='',$edate='',$tnt='teaching',$schedule='',$quarter='',$perdept_salary=array(),$regpay=0){
		$total_holiday_and_leave = $this->getTotalLeaveAndHoliday($employeeid, $sdate, $edate, $tnt); // EXTENSIONS
		$income_config_q = $this->displayIncome();
		$arr_income_config = $this->constructArrayListFromStdClass($income_config_q,'id','deductedby');
		$workingdays = '';
		$computeOtherIncome = 1;
		foreach ($arr_income_config as $codeIncome => $det) {
				$deductedby = $det['description'];

				$oth_q = $this->getEmployeeOtherIncomeConfig($employeeid,$sdate,$edate,$codeIncome);

				if($oth_q->num_rows() > 0){
					$row = $oth_q->row(0);

				    ///< compute for deduction and total pay
				    $total_deduc = $total_pay = $deduc_hours = 0;
				    $oth_monthly = $row->monthly;
				    $oth_daily = $row->daily;
				    $oth_hourly = $row->hourly;


					if($deductedby != '' || $deductedby != NULL){

						$deduc_min = 0;

						if($tnt == 'teaching'){
							list($tardy_amount,$absent_amount,$x,$x,$x,$x,$tardy_min,$absent_min) = $this->getTardyAbsentSummaryTeaching($employeeid,$tnt,'','',$sdate,$edate,$oth_hourly,$oth_hourly,$oth_hourly,$oth_hourly, $perdept_salary, $regpay);
							$workingdays = 261;
						}else{
							list($tardy_amount,$absent_amount,$x,$tardy_min,$absent_min) = $this->getTardyAbsentSummaryNT($employeeid,$tnt,'','',$sdate,$edate,$oth_hourly,false,$oth_daily);
							$workingdays = 313;
						}

						///< deduct base on setup
						if($deductedby == 'BOTH'){
							$deduc_min = $tardy_min + $absent_min;
							$total_deduc = $tardy_amount + $absent_amount;

						}elseif($deductedby == 'TARDY'){
							$deduc_min = $tardy_min;
							$total_deduc = $tardy_amount;

						}elseif($deductedby == 'ABSENT'){
							$deduc_min = $absent_min;
							// $total_deduc = $absent_amount;
							if($deduc_min > 0) $total_deduc = $oth_monthly * 2 * 12 / $workingdays;
						}

						$deduc_hours = $this->time->minutesToHours($deduc_min);
					}

				    if($deductedby == 'ABSENT'){
				    	$no_days = $deduc_hours / 8;
				    	$total_pay = $oth_monthly - ($no_days * $total_deduc);
				    }
				    else $total_pay = $oth_monthly - $total_deduc;

				    if($total_pay < 0) $total_pay = 0;

				    if($codeIncome == 29) $total_pay -= $total_holiday_and_leave * $oth_daily;

				    ///< insert to employee_income
			    	$this->saveEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,$total_pay,$schedule,$quarter);

			    	//< get corresponding dtr cutoff for given payroll cutoff
			    	list($dtr_start,$dtr_end) = $this->getDtrPayrollCutoffPair('','',$sdate,$edate);

			    	///< save other income computation results for viewing
			    	$this->saveEmployeeOtherIncomeComputed($codeIncome,$employeeid,$dtr_start,$dtr_end,$sdate,$edate,$total_pay,$total_deduc,$deduc_hours);
				    
				} ///< end if

		} //<end loop income config
	}

	function getDtrPayrollCutoffPair($dtr_start='',$dtr_end='',$payroll_start='',$payroll_end='',$dtr_id='',$p_id=''){
        $wC = $payroll_quarter = $payroll_sched = '';

        if($dtr_start && $dtr_end) $wC .= " WHERE a.CutoffFrom='$dtr_start' AND a.CutoffTo='$dtr_end'";
        elseif($payroll_start && $payroll_end) $wC .= " WHERE b.startdate='$payroll_start' AND b.enddate='$payroll_end'";
        elseif($dtr_id) $wC .= " WHERE a.ID='$dtr_id'";
        elseif($p_id) $wC   .= " WHERE b.id='$p_id'";

        $p_cutoff = $this->db->query("SELECT a.CutoffFrom, a.CutoffTo, b.startdate, b.enddate, b.quarter, b.schedule
                                        FROM cutoff a
                                        LEFT JOIN payroll_cutoff_config b ON b.`baseid`=a.`ID` 
                                        $wC");

        if($p_cutoff->num_rows() > 0){
          $dtr_start = $p_cutoff->row(0)->CutoffFrom;
          $dtr_end = $p_cutoff->row(0)->CutoffTo;
          $payroll_start = $p_cutoff->row(0)->startdate;
          $payroll_end = $p_cutoff->row(0)->enddate;
          $payroll_quarter = $p_cutoff->row(0)->quarter;
          $payroll_sched = $p_cutoff->row(0)->schedule;
        }

        return array($dtr_start,$dtr_end,$payroll_start,$payroll_end,$payroll_quarter,$payroll_sched);
    }

	function saveEmployeeOtherIncomeComputed($code_income='',$employeeid='',$dtr_start='',$dtr_end='',$payroll_start='',$payroll_end='',$total_pay='',$total_deduc='',$deduc_hours=''){
		$res = $this->db->query("SELECT id FROM other_income_computed 
									WHERE employeeid='$employeeid' 
									AND code_income='$code_income'
									AND dtr_cutoffstart='$dtr_start' AND dtr_cutoffend='$dtr_end'
									AND payroll_cutoffstart='$payroll_start' AND payroll_cutoffend='$payroll_end'");

		if($res->num_rows() > 0) 	$this->updateEmployeeOtherIncomeComputed($res->row(0)->id,$total_pay,$total_deduc,$deduc_hours);
		else 						$this->insertEmployeeOtherIncomeComputed($code_income,$employeeid,$dtr_start,$dtr_end,$payroll_start,$payroll_end,$total_pay,$total_deduc,$deduc_hours);
	}
	
	function updateEmployeeOtherIncomeComputed($id='',$total_pay='',$total_deduc='',$deduc_hours=''){
		$res = $this->db->query("UPDATE other_income_computed SET amount_total='$total_pay', amount_deduc='$total_deduc', hours_deduc='$deduc_hours' WHERE id='$id'");
		return $res;
	}

	function insertEmployeeOtherIncomeComputed($code_income='',$employeeid='',$dtr_start='',$dtr_end='',$payroll_start='',$payroll_end='',$total_pay='',$total_deduc='',$deduc_hours=''){
		$user = $this->user;
		$res = $this->db->query("INSERT INTO other_income_computed (employeeid,code_income,dtr_cutoffstart,dtr_cutoffend,payroll_cutoffstart,payroll_cutoffend,amount_total,amount_deduc,hours_deduc,addedby) 
									VALUES ('$employeeid','$code_income','$dtr_start','$dtr_end','$payroll_start','$payroll_end','$total_pay','$total_deduc','$deduc_hours','$user')");
		return $res;
	}

	function getEmployeeOtherIncomeConfig($employeeid='',$sdate='',$edate='',$codeIncome=''){
		$wC = "";
		if($codeIncome) $wC .= " AND a.other_income = '{$codeIncome}'";
		if($employeeid) $wC .= " AND a.employeeid = '{$employeeid}'";
		$res = $this->db->query("SELECT a.*
									FROM other_income a
                            		WHERE ( (dateEffective <= '$edate') OR (dateEnd >= '$edate') )
                            		{$wC}
                            		order by a.employeeid");
		return $res;
	}

	public function getTotalLeaveAndHoliday($employeeid, $sdate, $edate, $tnt=""){
		if($tnt == "teaching") $query_att = $this->db->query("SELECT SUM(eleave + vleave + sleave + oleave + tholiday) AS total FROM attendance_confirmed WHERE employeeid = '$employeeid' AND payroll_cutoffstart = '$sdate' AND payroll_cutoffend = '$edate' ");
		else $query_att = $this->db->query("SELECT SUM(eleave + vleave + sleave + oleave + isholiday) AS total FROM attendance_confirmed_nt WHERE employeeid = '$employeeid' AND payroll_cutoffstart = '$sdate' AND payroll_cutoffend = '$edate' ");

		if($query_att->num_rows() > 0) return $query_att->row()->total;
		else return false;
	}

	function computeCOLAIncome($employeeid='',$sdate='',$edate='',$schedule='',$quarter='',$workdays=0,$absentdays=0){
		$codeIncome = '11';
		$cola_amount = 0;
		$present_days = $this->cutoffPresentDays($employeeid, $sdate, $edate); //ATTENDANCE
		$multiplier = $this->getCOLAEffectiveAmount($sdate); // INCOME
		$cola_amount = $multiplier * $present_days;

		if($cola_amount > 0){
			$this->saveEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,$cola_amount,$schedule,$quarter);
		}
	}

	public function getCOLAEffectiveAmount($sdate=''){
		$multiplier = 0;
		$res = $this->db->query("SELECT multiplier FROM payroll_income_cola_config WHERE date_effective <= '$sdate' ORDER BY date_effective DESC LIMIT 1");
		if($res->num_rows(0) > 0) $multiplier = $res->row(0)->multiplier;
		return $multiplier;
	}

    public function cutoffPresentDays($employeeid, $startdate, $enddate){
        $q_att = $this->db->query("SELECT day_present FROM attendance_confirmed WHERE employeeid = '$employeeid' AND payroll_cutoffstart = '$startdate' AND payroll_cutoffend = '$enddate'");
        if($q_att->num_rows() > 0){
            $present_days = $q_att->row()->day_present;
            $present_arr = explode(",", $present_days);
            return count($present_arr);
        }else{
            return false;
        }
    }

	function computeEmployeeIncome($empid='',$schedule='',$quarter='',$sdate='',$edate='',$arr_income_config='',$payroll_cutoff_id='', $salary=0){
		$arr_info = array();
		$str_income = $str_monetize_id = '';
		$totalincome = 0;

		$res = $this->incometitle($empid,'amount',$schedule,$quarter,'',$sdate,$edate); //PAYROLLOPTIONS

		foreach ($res->result() as $key => $row) {
			$amount = $row->title;

			$arr_info[$row->code_income] = $amount;
			$totalincome += $amount;
			$arr_income_config[$row->code_income]['hasData'] = 1;
			if($str_income) $str_income .= '/';
			$str_income .= $row->code_income . '=' . $amount;
		}
		if($schedule == "semimonthly" && $quarter == 2){
			$monetize = $daysCount = 0;
			$monetize_rate = $this->MonetizationDetails(1); // PAYROLL
			$monetization_query = $this->db->query("SELECT * FROM monetize_app WHERE applied_by = '$empid' AND date_applied <= '$edate' AND app_status = 'APPROVED' AND credited = '0'");
			if($monetization_query->num_rows() > 0 && $monetize_rate[0]->description){
				foreach ($monetization_query->result() as $key => $value) {
					$monetize_id = $value->id;
					$checkComputedTable = $this->db->query("SELECT * FROM payroll_computed_table WHERE FIND_IN_SET('$monetize_id', monetize_id)");
					if($checkComputedTable->num_rows() == 0){
						$daysCount += $value->nodays;
						$str_monetize_id .= ($str_monetize_id) ? ",".$value->id : $value->id;
					}
						
				}
				$monetize = $daysCount * $salary * $monetize_rate[0]->description;
			}

			$arr_info['Monetize'] = $monetize;
			$totalincome += $monetize;
			$arr_income_config['Monetize']['hasData'] = 1;
			if($str_income) $str_income .= '/';
			$str_income .= 'Monetize' . '=' . $monetize;
		}

		return array($arr_income_config,$arr_info,$totalincome,$str_income, $str_monetize_id);
	}

    function MonetizationDetails($id = ""){
        $wc = ($id ? "WHERE id = '$id'" : '');
        $query = $this->db->query("SELECT id,title,description,created_at FROM  monetization_details $wc")->result();
        return $query;
    }

	function incometitle($eid = "",$title = "",$schedule = "",$quarter = "", $colname = "",$sdate = "",$edate = ""){
		$whereClause = "";
		if($eid){$whereClause   = " AND employeeid='$eid'";}        
		if($schedule){
			$whereClause .= " AND schedule='$schedule'";
			if($schedule == "semimonthly"){
				if($quarter)  $whereClause .= " AND FIND_IN_SET(cutoff_period,'$quarter,3')";
			}else             $whereClause .= " AND cutoff_period='$quarter'";  
		}
		if($colname)    $whereClause .= " AND code_income='$colname'";
		if($sdate && $edate)    $whereClause  .= " AND ((datefrom BETWEEN '$sdate' AND '$edate') OR (datefrom <= '$sdate')) AND datefrom <> '0000-00-00'  ";
		$query = $this->db->query("SELECT IFNULL($title,0) as title, code_income FROM employee_income INNER JOIN payroll_income_config b ON (b.id = code_income)  WHERE code_income <> '' $whereClause AND nocutoff > 0 GROUP BY code_income");
		return $query;
	}

	function computeEmployeeIncomeAdj($empid='',$schedule='',$quarter='',$sdate='',$edate='',$arr_income_adj_config='',$totalincome=0,$payroll_cutoff_id=0){
		$arr_info = array();
		$str_income_adj = '';

		$res = $this->getEmpIncomeAdj($empid,'amount',$schedule,$quarter,'',$sdate,$edate);

		foreach ($res->result() as $key => $row) {
			$amount = $row->title;
			if($row->deduct==1) $amount = $amount * -1;

			$arr_info[$row->code_income] = $amount;
			$totalincome += $amount;
			$arr_income_adj_config[$row->code_income]['hasData'] = 1;
			if($str_income_adj) $str_income_adj .= '/';
			$str_income_adj .= $row->code_income . '=' . $amount;
		}

		$res = $this->getEmpIncomeAdjSalary($empid,'amount',$schedule,$quarter,'SALARY',$sdate,$edate);

		foreach ($res->result() as $key => $row) {
			$amount = $row->title;
			if($row->deduct==1) $amount = $amount * -1;

			$arr_info[$row->code_income] = $amount;
			$totalincome += $amount;
			$arr_income_adj_config[$row->code_income]['hasData'] = 1;
			if($str_income_adj) $str_income_adj .= '/';
			$str_income_adj .= $row->code_income . '=' . $amount;
		}
		
		$leave_adj_code = '31';
		$leave_adj_amt = $this->getLeaveAdjAmount($payroll_cutoff_id,$empid);

		if($leave_adj_amt){
			$arr_info[$leave_adj_code] = $leave_adj_amt;
			$totalincome += $leave_adj_amt;
			$arr_income_adj_config[$leave_adj_code]['hasData'] = 1;
			if($str_income_adj) $str_income_adj .= '/';
			$str_income_adj .= $leave_adj_code . '=' . $leave_adj_amt;
		}

		$ob_adj_code = '1';
		$ob_adj_amt = $this->getLeaveAdjAmount($payroll_cutoff_id,$empid,'OB');
		$ob_adj_amt += $this->getLeaveAdjAmount($payroll_cutoff_id,$empid,'CORRECTION');

		if($ob_adj_amt){
			$arr_info[$ob_adj_code] = $ob_adj_amt;
			$totalincome += $ob_adj_amt;
			$arr_income_adj_config[$ob_adj_code]['hasData'] = 1;
			if($str_income_adj) $str_income_adj .= '/';
			$str_income_adj .= $ob_adj_code . '=' . $ob_adj_amt;
		}

		return array($arr_income_adj_config,$arr_info,$totalincome,$str_income_adj);
	}

	function getLeaveAdjAmount($payroll_cutoff_id='',$empid='',$type='LEAVE'){
		$total_amt = 0;

		$tbl = 'leave_adjustment';
		if($type ==  'OB') $tbl = 'ob_adjustment';
		if($type ==  'CORRECTION') $tbl = 'correction_adjustment';

		$adj_q = $this->db->query("SELECT SUM(amount) as total_amt FROM $tbl WHERE employeeid='$empid' AND payroll_cutoff_id='$payroll_cutoff_id';");
		
		if($adj_q->num_rows() > 0){
			$total_amt = $adj_q->row(0)->total_amt;
		}
		return $total_amt;
	}

	function getEmpIncomeAdj($eid = "",$title = "",$schedule = "",$quarter = "", $colname = "",$sdate = "",$edate = ""){
        $whereClause = "";
        if($eid){$whereClause   = " AND employeeid='$eid'";}        
        if($schedule){
            $whereClause .= " AND schedule='$schedule'";
            if($schedule == "semimonthly"){
                if($quarter)  $whereClause .= " AND FIND_IN_SET(cutoff_period,'$quarter,3')";
            }else             $whereClause .= " AND cutoff_period='$quarter'";  
        }
        if($colname)    $whereClause .= " AND code_income='$colname'";
        if($sdate && $edate)    $whereClause  .= " AND ((datefrom BETWEEN '$sdate' AND '$edate') OR (datefrom <= '$sdate')) AND datefrom <> '0000-00-00'  ";
        $query = $this->db->query("SELECT IFNULL($title,0) as title, code_income, a.deduct, a.taxable FROM employee_income_adj a INNER JOIN payroll_income_config b ON (b.id = a.code_income)  WHERE a.code_income <> '' $whereClause AND nocutoff > 0 GROUP BY code_income");
        return $query;
   }

   function getEmpIncomeAdjSalary($eid = "",$title = "",$schedule = "",$quarter = "", $colname = "",$sdate = "",$edate = ""){
        $whereClause = "";
        if($eid){$whereClause   = " AND employeeid='$eid'";}        
        if($schedule){
            $whereClause .= " AND schedule='$schedule'";
            if($schedule == "semimonthly"){
                if($quarter)  $whereClause .= " AND FIND_IN_SET(cutoff_period,'$quarter,3')";
            }else             $whereClause .= " AND cutoff_period='$quarter'";  
        }
        if($colname)    $whereClause .= " AND code_income='$colname'";
        if($sdate && $edate)    $whereClause  .= " AND ((datefrom BETWEEN '$sdate' AND '$edate') OR (datefrom <= '$sdate')) AND datefrom <> '0000-00-00'  ";
        $query = $this->db->query("SELECT IFNULL($title,0) as title, code_income, deduct, taxable FROM employee_income_adj WHERE code_income <> '' $whereClause AND nocutoff > 0 GROUP BY code_income");
        return $query;
   }

	function getPrevCutoffSalary($cutoff_month='',$quarter=1,$employeeid=''){
		$prevSalary = $prevGrosspay = 0;
		if($cutoff_month){
			if($quarter > 1){
				$res = $this->db->query("SELECT salary,gross FROM payroll_computed_table WHERE employeeid='$employeeid' AND DATE_FORMAT(cutoffstart,'%Y-%m')='$cutoff_month' AND quarter=1 LIMIT 1");
				if($res->num_rows() > 0){
					$prevSalary = $res->row(0)->salary;
					$prevGrosspay = $res->row(0)->gross;
				}
			}
		}else{

		}

		return array($prevSalary,$prevGrosspay);
	}

	function computeEmployeeFixedDeduc($empid='',$schedule='',$quarter='',$sdate='',$edate='',$arr_fixeddeduc_config='',$arr_info_emp='',$prevSalary=0,$prevGrosspay=0, $getTotalNotIncludedInGrosspay = 0, $basic_salary=0){
		$tnt = $this->getEmployeeTeachingType($empid);
		$arr_info = $ee_er = array();
		$str_fixeddeduc = '';
		$totalfix = 0;
		$total_gross = $arr_info_emp['grosspay'] + $prevGrosspay;
		$employee_salary = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid = '$empid' ");
		if($employee_salary->num_rows() > 0){
			$employee_salary = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid = '$empid' ORDER BY date_effective DESC LIMIT 1")->row()->monthly;
		}else{
			$employee_salary = $this->db->query("SELECT * FROM payroll_employee_salary WHERE employeeid = '$empid' ORDER BY date_effective DESC LIMIT 1")->row()->monthly;
		}
		if($quarter == 1){
			$res = $this->getEmpFixedDeduc($empid,'amount','HIDDEN',$schedule,$quarter,'',$sdate,$edate); // PAYROLLOPTIONS
			// echo "<pre>"; print_r($res->result()); die;
	 		foreach ($res->result() as $key => $row) {
				$cutoff_period = $row->cutoff_period;
				$er = $ec = $provident_er = 0;
				$amount_fx = $row->title;
				$code_deduction = $row->code_deduction;
				
				if($row->code_deduction == 'PHILHEALTH'){
					list($amount_fx,$er) = $this->computePHILHEALTHContri($amount_fx,($basic_salary),$cutoff_period,$sdate,$prevGrosspay,$quarter,$empid);
					if(!$this->checkIdnumber($empid, $code_deduction)) $amount_fx = $er = 0; // PAYROLLOPTIONS

				}
				else if ($row->code_deduction == 'SSS') {
					/*if teaching is Non-Teaching, use salary, if teaching use gross*/
					if($tnt == "nonteaching"){
						// $arr_info_emp["grosspay"] = $basic_salary;
						// $prevGrosspay = $prevSalary;
					}
					list($amount_fx,$ec,$er,$provident_er) = $this->computeSSSContri($amount_fx,$arr_info_emp['grosspay'],$prevGrosspay,$empid,$sdate,$edate,$quarter,$cutoff_period,$getTotalNotIncludedInGrosspay);
					if(!$this->checkIdnumber($empid, $code_deduction)) $amount_fx = $er = $ec = 0; // PAYROLLOPTIONS
				}
				else if ($row->code_deduction == 'PAGIBIG') {
					list($amount_fx2,$er, $per_ee, $per_er) = $this->computePagibigContri($amount_fx,$empid,($basic_salary), $cutoff_period, $quarter);
					if($amount_fx2 != 0) $amount_fx = $amount_fx2;
					if($amount_fx == "") $amount_fx = $per_ee;
					if($er == 0) $er = $per_er;
					if(!$this->checkIdnumber($empid, $code_deduction)) $amount_fx = $er = 0; // PAYROLLOPTIONS
				}
				else if ($row->code_deduction == 'PERAA') {
					if($row->amount == "") $amount_fx = ($arr_info_emp['salary'] * 2) * 0.0325;
					$er = $amount_fx;
				}
				else if ($row->code_deduction == 'GSIS') {
					list($amount_fx,$er) = $this->getGSISPayment(($basic_salary), $amount_fx,$empid, $cutoff_period, $quarter);
				}

				$ee_er[$row->code_deduction]['EE'] = $amount_fx;
				$ee_er[$row->code_deduction]['ER'] = $er;
				$ee_er[$row->code_deduction]['EC'] = $ec;
				$ee_er[$row->code_deduction]['provident_er'] = $provident_er;


				$arr_info[$row->code_deduction] = $amount_fx;
				$totalfix += $amount_fx;

				$arr_fixeddeduc_config[$row->code_deduction]['hasData'] = 1;
				if($str_fixeddeduc) $str_fixeddeduc .= '/';
				$str_fixeddeduc .= $row->code_deduction . '=' . $amount_fx;
			}
		}
			
		// echo"<pre>";print_r($arr_info);die;

		return array($arr_fixeddeduc_config,$arr_info,$totalfix,$str_fixeddeduc,$ee_er);
	}

	function getGSISPayment($basic_salary, $ee_gsis = 0, $empid, $schedule, $quarter){
		$er_gsis = 0;
		
		if($ee_gsis == NULL){
			$q_gsis = $this->db->query("SELECT * FROM gsis_table WHERE '$basic_salary' BETWEEN min_salary AND max_salary");
			if($q_gsis->num_rows() > 0){
				if($q_gsis->row()->ee_based == "percentage") $ee_gsis = ($q_gsis->row()->ee_share / 100) * $basic_salary;
				else $ee_gsis = $q_gsis->row()->ee_share;

				if($q_gsis->row()->er_based == "percentage") $er_gsis = ($q_gsis->row()->er_share / 100) * $basic_salary;
				else $er_gsis = $q_gsis->row()->er_share;
			}
		}
		
		return array($ee_gsis, $er_gsis);
	}

	function computePagibigContri($encoded_ee=NULL,$employeeid='',$gross=0, $cutoff_period=0, $quarter=0){
		$ee = $er = $per_er = $per_ee = $per_ee_total = $per_er_total =  0;
		// echo "<pre>"; print_r($gross); die;
		if($encoded_ee == NULL){
			$query = $this->db->query("SELECT emp_ee,emp_er, per_er, per_ee FROM hdmf_deduction WHERE '$gross' BETWEEN compensationfrom AND compensationto ORDER BY year DESC LIMIT 1");
			if ($query->num_rows() > 0) {
				$ee = $query->row()->emp_ee;
				$er = $query->row()->emp_er;
				$per_er = $query->row()->per_er;
				$per_ee = $query->row()->per_ee;

				$per_ee_total = $gross * ($per_ee / 100)/* / 2*/;
				$per_er_total = $gross * ($per_er / 100)/* / 2*/;
			} 
		}else{
			$ee = $encoded_ee;
			$query = $this->db->query("SELECT emp_ee,emp_er, per_er, per_ee FROM hdmf_deduction WHERE emp_ee <= $encoded_ee ORDER BY emp_ee DESC LIMIT 1");
			if ($query->num_rows() > 0) {
				$er = $query->row()->emp_er;
				$per_er = $query->row()->per_er;
				$per_ee = $query->row()->per_ee;
				$per_ee_total = $gross * ($per_ee / 100);
				$per_er_total = $gross * ($per_er / 100);
			} 
		}

		if($cutoff_period == 3){
			$ee /= 2;
			$er /= 2;
		}

		return array($ee,$er, $per_ee_total, $per_er_total);
	}

	function computeSSSContri($encoded_ee=NULL,$gross=0,$prevGrosspay=0,$empid='',$sdate='',$edate='',$quarter='',$cutoff_period='',$getTotalNotIncludedInGrosspay=0){
		$ee = $ec = $er = $provident_er = 0;
		$total_gross = 0;
		$year = date("Y", strtotime($sdate));
		if($cutoff_period == 3){
			if($quarter == 1){
				$gross -= $getTotalNotIncludedInGrosspay;
				list($ee,$ec,$er,$provident_er) = $this->getSSSContriFromSetup($encoded_ee,$gross, date('Y',strtotime($sdate)));
			}elseif($quarter == 2){
				$gross -= $getTotalNotIncludedInGrosspay;
				list($prev_ee,$prev_ec,$prev_er) = $this->getPrevSSSContri(date('Y-m',strtotime($sdate)),$quarter,$empid);
				list($ee,$ec,$er,$provident_er) = $this->getSSSContriFromSetup($encoded_ee,$gross + $prevGrosspay, date('Y',strtotime($sdate)));

				$ee = $ee - $prev_ee;
				$ec = $ec - $prev_ec;
				$er = $er - $prev_er;
			} 

		}else{
			$gross -= $getTotalNotIncludedInGrosspay;
			if($cutoff_period == 1) 	$total_gross = $gross;
			elseif($cutoff_period == 2) $total_gross = $gross + $prevGrosspay;

			list($ee,$ec,$er,$provident_er) = $this->getSSSContriFromSetup($encoded_ee,$total_gross, date('Y',strtotime($sdate)));
		}

		return array($ee,$ec,$er,$provident_er);
	}

	function getPrevSSSContri($cutoff_month='',$quarter=1,$employeeid=''){
		$prev_ee = $prev_ec = $prev_er = 0;
		if($cutoff_month){
			if($quarter > 1){
				$res = $this->db->query("SELECT b.code_deduction,b.EE,b.EC,b.ER FROM payroll_computed_table a
											INNER JOIN payroll_computed_ee_er b ON b.base_id = a.id
											WHERE a.employeeid='$employeeid' AND DATE_FORMAT(a.cutoffstart,'%Y-%m')='$cutoff_month' AND a.quarter=1 AND b.code_deduction = 'SSS'
											LIMIT 1");

				if($res->num_rows() > 0){
					$prev_ee = $res->row(0)->EE;
					$prev_ec = $res->row(0)->EC;
					$prev_er = $res->row(0)->ER;
				}
			}
		}else{

		}

		return array($prev_ee,$prev_ec,$prev_er);
	}

	function getSSSContriFromSetup($encoded_ee=NULL,$gross=0,$year=""){
		$ee = $ec = $er = $provident_er = 0;
		
		if($encoded_ee == NULL){
			$query = $this->db->query("SELECT emp_ee,emp_con,emp_er,total_ee,provident_er FROM sss_deduction WHERE '$gross' BETWEEN compensationfrom AND compensationto AND year = '$year'");
			if ($query->num_rows() > 0) {
				$ee = $query->row()->total_ee;
				$ec = $query->row()->emp_con;
				$er = $query->row()->emp_er;
				$provident_er = $query->row()->provident_er;
			}else{
				$query_latest = $this->db->query("SELECT emp_ee,emp_con,emp_er, total_ee, provident_er FROM sss_deduction WHERE '$gross' BETWEEN compensationfrom AND compensationto AND compensationto ORDER BY YEAR DESC LIMIT 1");
				if ($query_latest->num_rows() > 0) {
					$ee = $query_latest->row()->total_ee;
					$ec = $query_latest->row()->emp_con;
					$er = $query_latest->row()->emp_er;
					$provident_er = $query_latest->row()->provident_er;
				}
			}  
		}else{
			$ee = $encoded_ee;
			$query = $this->db->query("SELECT emp_ee,emp_con,emp_er,provident_er FROM sss_deduction WHERE emp_ee <= $encoded_ee ORDER BY emp_ee DESC LIMIT 1");
			if ($query->num_rows() > 0) {
				$ec = $query->row()->emp_con;
				$er = $query->row()->emp_er;
				$provident_er = $query->row()->provident_er;
			} 
		}
		return array($ee,$ec,$er,$provident_er);
	}

	function checkIdnumber($empid='', $code=''){
        $idnum = "emp_".$code;
        $query = $this->db->query("SELECT $idnum from employee where employeeid = '$empid' and $idnum != '' ")->num_rows();
        return $query;
    }

	function computePHILHEALTHContri($encoded_ee=NULL,$gross=0,$cutoff_period="",$sdate="",$prevGrosspay="",$quarter="",$empid=""){
		$ee = $er = $true_ee = 0;
		
		if($encoded_ee == NULL){
			if($quarter == 1){
				$ee = $this->philhealthContribution($gross, $sdate);
				$ee = $ee / 2;
				// if($cutoff_period == 3) $ee = $ee / 2; ///< for employee and employer
				// else $ee = $ee / 4; ///< for employee and employer
			}elseif($quarter == 2){
				list($prev_ee,$prev_ec,$prev_er) = $this->getPrevPhilhealthContri(date('Y-m',strtotime($sdate)),$quarter,$empid);
				$ee = $this->philhealthContribution($gross + $prevGrosspay, $sdate);
				$ee = $ee / 2; ///< for employee and employer
				$ee -= $prev_ee;
				$er -= $prev_er;

			} 
						
			$true_ee = (intval($ee*100))/100;
			$excess = $ee - $true_ee;
			
			$er = $ee + $excess;
			$er = round($er,2);
			
		}else{
			$true_ee = $er = $encoded_ee;
		}
		return array($true_ee,$er); 
	}

	function getPrevPhilhealthContri($cutoff_month='',$quarter=1,$employeeid=''){
		$prev_ee = $prev_ec = $prev_er = 0;
		if($cutoff_month){
			if($quarter > 1){
				$res = $this->db->query("SELECT b.code_deduction,b.EE,b.EC,b.ER FROM payroll_computed_table a
											INNER JOIN payroll_computed_ee_er b ON b.base_id = a.id
											WHERE a.employeeid='$employeeid' AND DATE_FORMAT(a.cutoffstart,'%Y-%m')='$cutoff_month' AND a.quarter=1 AND b.code_deduction = 'PHILHEALTH'
											LIMIT 1");

				if($res->num_rows() > 0){
					$prev_ee = $res->row(0)->EE;
					$prev_ec = $res->row(0)->EC;
					$prev_er = $res->row(0)->ER;
				}
			}
		}else{

		}

		return array($prev_ee,$prev_ec,$prev_er);
	}

	function philhealthContribution($monthlySalary=0,$payroll_start=""){
		$year = date("Y", strtotime($payroll_start));
		$isrange = $this->db->query("SELECT * FROM `philhealth_empshare` WHERE $monthlySalary BETWEEN min_salary AND max_salary AND min_salary != '' AND max_salary != '' AND year = '$year'");
		if($isrange->num_rows() == 0) $isrange = $this->db->query("SELECT * FROM `philhealth_empshare` WHERE $monthlySalary BETWEEN min_salary AND max_salary AND min_salary != '' AND max_salary != '' ORDER BY year DESC LIMIT 1");
		// print_r($this->db->last_query());die;
		// echo $monthlySalary;die;
		if($isrange->num_rows() > 0){
			if($isrange->row()->percentage){
				$isrange->row()->percentage = str_replace(".", "", $isrange->row()->percentage);
				$isrange->row()->percentage = "0.0".$isrange->row()->percentage;
				$ee = $monthlySalary * $isrange->row()->percentage;
				return $ee;
			}else{
				$ee = $isrange->row()->def_amount;
				return $ee;
			}
		}
		$isminimum = $this->db->query("SELECT * FROM `philhealth_empshare` WHERE min_salary > $monthlySalary AND min_salary != '' AND def_amount != '' AND year = '$year'");
		if($isminimum->num_rows() == 0) $isminimum = $this->db->query("SELECT * FROM `philhealth_empshare` WHERE min_salary > $monthlySalary AND min_salary != '' AND def_amount != '' ORDER BY year DESC LIMIT 1");

		if($isminimum->num_rows() > 0){
			$ee = $isminimum->row()->def_amount;
			return $ee;
		}
		$ismaximum = $this->db->query("SELECT * FROM `philhealth_empshare` WHERE max_salary > $monthlySalary AND max_salary != '' AND def_amount != '' AND year = '$year'");
		if($ismaximum->num_rows() == 0) $ismaximum = $this->db->query("SELECT * FROM `philhealth_empshare` WHERE max_salary > $monthlySalary AND max_salary != '' AND def_amount != '' ORDER BY year DESC LIMIT 1");

		if($ismaximum->num_rows() > 0){
			$ee = $ismaximum->row()->def_amount;
			return $ee;
		}
	}
	
	function getEmpFixedDeduc($eid = "",$title = "",$visible = "",$schedule = "",$quarter = "",$colname = "", $cutoffstart = "", $cutoffend = ""){
		$whereClause = " AND visibility='$visible'";
		if($eid)     $whereClause = " AND employeeid='$eid' AND visibility='$visible'";
		
		// urs condition
		if($quarter)  $whereClause .= " AND FIND_IN_SET(cutoff_period,'$quarter,3')";
		else $whereClause .= " AND cutoff_period='$quarter'"; 
		
		if($colname)    $whereClause .= " AND a.code_deduction='$colname'";
		$query = $this->db->query("SELECT a.$title as title, a.code_deduction, a.cutoff_period,a.amount FROM employee_deduction a INNER JOIN deductions b ON(b.code_deduction = a.code_deduction) 
				WHERE a.code_deduction <> '' $whereClause GROUP BY a.code_deduction");

		return $query;
   	}
	function computeEmployeeLoan($empid='',$schedule='',$quarter='',$sdate='',$edate='',$arr_loan_config=''){
		$arr_info = array();
		$str_loan = '';
		$totalloan = 0;
		if($quarter == 1){
			$res = $this->loantitle($empid,'amount',$schedule,$quarter,'',$sdate,$edate); // PAYROLLOPTIONS

			foreach ($res->result() as $key => $row) {
				$skip_loan = $this->checkIfSkipInLoanPayment($empid, $row->code_loan);
				// if(!$skip_loan){
				if($skip_loan) $row->title = 0;
				$arr_info[$row->code_loan] = $row->title;
				$totalloan += $row->title;
				$arr_loan_config[$row->code_loan]['hasData'] = 1;
				if($str_loan) $str_loan .= '/';
				$str_loan .= $row->code_loan . '=' . $row->title;
				// }
			}
		}
			

		return array($arr_loan_config,$arr_info,$totalloan,$str_loan);
	}
	
    function checkIfSkipInLoanPayment($employeeid, $code_loan){
    	$q_loan = $this->db->query("SELECT * FROM skip_loan_history WHERE employeeid = '$employeeid' AND code_loan = '$code_loan' AND status = 'YES' ");
    	if($q_loan->num_rows() > 0) return true;
    	else return false;
    }


	function loantitle($eid = "",$title = "",$schedule = "",$quarter = "",$colname = "",$sdate = "",$edate = ""){
        $whereClause = "";
        if($eid)     $whereClause = " AND employeeid='$eid'";
        if($schedule){
            $whereClause .= " AND schedule='$schedule'";
            // if($schedule == "semimonthly"){
            //     if($quarter)  $whereClause .= " AND FIND_IN_SET(cutoff_period,'$quarter,3')";
            // }else             $whereClause .= " AND cutoff_period='$quarter'";  
            $whereClause .= " AND FIND_IN_SET(cutoff_period,'$quarter,3')";
        }
        if($colname)    $whereClause .= " AND code_loan='$colname'";
        if($sdate && $edate)    $whereClause  .= " AND ((datefrom BETWEEN '$sdate' AND '$edate') OR (datefrom <= '$sdate')) AND datefrom <> '0000-00-00'  ";
        $query = $this->db->query("SELECT IFNULL($title,0) as title, code_loan FROM employee_loan INNER JOIN payroll_loan_config b ON(b.id = code_loan) WHERE code_loan <> '' $whereClause AND nocutoff > 0 GROUP BY code_loan");
        return $query;
   	}

	function compute13thMonthPay_2($employeeid='',$year='',$current_cutoffstart='',$current_cutoffend='',$current_netbasicpay=array(),$current_income_arr=array(), $forPayroll = "", $last_pay = ""){

		$remaining_cutoff = $this->getRemainingCutoffForPayroll($employeeid, $current_cutoffstart, $current_cutoffend); // EXTENSIONS

		$deminimiss_list = array();
		$salary_list = $filter = array();

		$total_deduction = 0;
		$latest_processed_month = $amount = $employee_benefits = 0;
		// $isComplete = true;
		$teachingtype = $this->getempdatacol('teachingtype',$employeeid);
		$deptid = $this->getempdatacol('deptid',$employeeid);
		/*get deminimiss income*/
		$included_income = $this->getIncomeIncluded(); // INCOME
		foreach($included_income as $row) $deminimiss_list[$row->id] = $row->id;
		/*end*/
		$latest_processed_month = intval(date('m',strtotime($current_cutoffstart)));

		if($latest_processed_month){

			$config_arr = $this->getIncomeConfigIncludedIn13thMonth();

			$filter['employeeid'] = $employeeid;
			$filter['status'] = "PROCESSED";
			$filter["DATE_FORMAT(cutoffstart,'%Y')"] = $year;

			$yearly_q = $this->utils->getSingleTblData('payroll_computed_table',array('id','cutoffstart','cutoffend','salary','netbasicpay','income','tardy','absents'),$filter);

			foreach ($yearly_q->result() as $key => $row) {
				$month = date('m',strtotime($row->cutoffstart));
				$month = intval($month);

				if(!isset($salary_list[$month]['salary'])) $salary_list[$month]['salary'] = 0;
				$salary_list[$month]['salary'] += $row->salary;

				///< income list
				$income_list = $this->constructArrayListFromComputedTable($row->income);

				foreach ($income_list as $i_code => $i_amount) {
					if(in_array($i_code, $config_arr)){
						$salary_list[$month]['salary'] += $i_amount;
					}
				}

				/*for employee benefits*/
				foreach ($income_list as $i_code => $i_amount) {
					if(in_array($i_code, $deminimiss_list)){
						$employee_benefits += $i_amount;
					}
				}
				/*end*/

				///< deduc tardy and absents
				$total_deduction += ($row->tardy + $row->absents);
			}

			///< add current cutoff netbasic and income
			if(!isset($salary_list[$latest_processed_month]['salary'])) $salary_list[$latest_processed_month]['salary'] = 0;
			
			if($forPayroll){
				$salary_list[$latest_processed_month]['salary'] += $current_netbasicpay;
				foreach ($current_income_arr as $i_code => $i_amount) {
					if(in_array($i_code, $deminimiss_list)){
						$employee_benefits += $i_amount;
					}
				}
			}

			foreach ($current_income_arr as $i_code => $i_amount) {
				if(in_array($i_code, $config_arr)){
					$salary_list[$month]['salary'] += $i_amount;
				}
			}

			$total_monthly_salary = 0;
			foreach ($salary_list as $month => $det) {
				$total_monthly_salary += $det['salary'];
			}

			if($remaining_cutoff >0){
				$project_salary = $last_pay * $remaining_cutoff;
				$total_monthly_salary += $project_salary;
			}

			$total_monthly_salary -= $total_deduction;

			$amount = $total_monthly_salary / 12;
		}

		/*project employee_benefits*/
		$project_employee_benefits = $this->getEmployeeOtherIncome($employeeid, $deminimiss_list);
		$project_employee_benefits *= $remaining_cutoff;
		$employee_benefits += $project_employee_benefits;
		/*end*/
		$employee_benefits /= 12;
		return array($amount,$employee_benefits);

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

	function getIncomeConfigIncludedIn13thMonth(){
		$config_arr = array();
		$includedlist = '61,63,66';
		$res = $this->db->query("SELECT * FROM payroll_income_config WHERE FIND_IN_SET(mainaccount,'$includedlist')");
		foreach ($res->result() as $key => $row) {
			array_push($config_arr, $row->id);
		}
		return $config_arr;
	}

	function getIncomeIncluded(){
        $this->db->from("payroll_income_config");
        $this->db->where('isIncluded', '1');
        $query = $this->db->get();
        return $query->result();
    }

	function getempdatacol($col="",$eid=""){
		$return = '';
		$user =  $this->user;
		if($eid) $user = $eid;
		$sql = "SELECT $col FROM employee WHERE employeeid='$user';";
		foreach($this->db->query($sql)->result() as $row){
		  $return = $row->$col;
		}
		return $return;
	}

	public function getRemainingCutoffForPayroll($employeeid, $dfrom, $dto){
		$query = $this->db->query("SELECT * FROM processed_employee WHERE cutoffstart = '$dfrom' AND cutoffend = '$dto' AND employeeid = '$employeeid' LIMIT 1 ");
		return $query->row()->remaining_cutoff;
	}	

	function saveEmployeeOtherIncome($employeeid='',$sdate='',$edate='',$codeIncome='',$total_pay=0,$schedule='',$quarter=''){
		$code = '';
		$projected_income_code = 0;
		if($codeIncome == 5){
			$code = 18;
			$projected_income_code = 39;
		}
		else if($codeIncome == 37){
			$code = 27; 
			$projected_income_code = 40;
		}

		$exisiting_income = 0;
		$total = 0;
		$projected_income = $this->db->query("SELECT code_income, amount FROM projected_income WHERE code_income='$projected_income_code' AND employeeid='$employeeid'");

		if($projected_income->num_rows() > 0) $res = $this->db->query("SELECT code_income, amount FROM employee_income WHERE code_income='$projected_income_code' AND employeeid='$employeeid'");
		else $res = $this->db->query("SELECT code_income, amount FROM employee_income WHERE code_income='$codeIncome' AND employeeid='$employeeid'");

		if($res->num_rows() > 0) $exisiting_income = $res->row()->amount;
		$total = round($total_pay, 2) - round($exisiting_income, 2);
		
		if($res->num_rows() > 0){
			if($codeIncome == 5 || $codeIncome == 37){
				if($total){
					$this->insertProjectedEmployeeOtherIncome($employeeid,$sdate,$edate,$projected_income_code,$total_pay,$schedule,$quarter);
					
					if($total > 0) $this->updateEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,$total,$schedule,$quarter);
					else $this->updateEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,0,$schedule,$quarter);

					$emp_deduction = $this->db->query("SELECT code_deduction, amount FROM employee_deduction WHERE code_deduction='$code' AND employeeid='$employeeid'");
					if($emp_deduction->num_rows() > 0){
						if($emp_deduction->row()->amount > 0){
						 $this->db->query("DELETE FROM employee_deduction WHERE employeeid = '$employeeid' AND code_deduction = '$code' ");
					   	 $this->insertEmployeeDeduction($employeeid, $sdate, $edate, $code, abs($total), $schedule, $quarter);
						}
					}else{
						$this->insertEmployeeDeduction($employeeid, $sdate, $edate, $code, abs($total), $schedule, $quarter);
					}
				}
			}else{
				if($total_pay > $exisiting_income){
					$this->updateEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,$total_pay,$schedule,$quarter);
				}else if($total_pay == $exisiting_income){

				}
				else{
					$this->updateEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,$total_pay,$schedule,$quarter);
					$this->insertEmployeeDeduction($employeeid, $sdate, $edate, $code, $total, $schedule, $quarter);
				}
			}
		}else{		
			$this->insertEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,$total_pay,$schedule,$quarter);
		}
	}

	function insertEmployeeOtherIncome($employeeid='',$sdate='',$edate='',$codeIncome='',$total_pay=0,$schedule='',$quarter=''){
		$res = $this->db->query("INSERT INTO employee_income (employeeid,code_income,datefrom,amount,nocutoff,schedule,cutoff_period) VALUES ('$employeeid','$codeIncome','$sdate','$total_pay',1,'$schedule','$quarter')");
		return $res;
	}

	function insertEmployeeDeduction($employeeid, $sdate, $edate, $code_deduction, $ap_13month, $schedule, $quarter){
    	$query = $this->db->query("SELECT * FROM employee_deduction WHERE employeeid = '$employeeid' AND datefrom = '$sdate' AND dateto = '$edate' AND code_deduction = '$code_deduction' ");
    	if($query->num_rows == 0){
	    	$data = array(
	    		"employeeid" => $employeeid,
	    		"datefrom" => $sdate,
	    		"dateto" => $edate,
	    		"code_deduction" => $code_deduction,
	    		"amount" => $ap_13month,
	    		"schedule" => $schedule,
	    		"nocutoff" => "1",
	    		"cutoff_period" => $quarter
	    	);

	    	$this->db->insert("employee_deduction", $data);
	    }else{
	    	$this->db->query("DELETE FROM employee_deduction WHERE employeeid = '$employeeid' AND datefrom = '$sdate' AND dateto = '$edate' AND code_deduction = '$code_deduction' ");
	    	$data = array(
	    		"employeeid" => $employeeid,
	    		"datefrom" => $sdate,
	    		"dateto" => $edate,
	    		"code_deduction" => $code_deduction,
	    		"amount" => $ap_13month,
	    		"schedule" => $schedule,
	    		"nocutoff" => "1",
	    		"cutoff_period" => $quarter
	    	);

	    	$this->db->insert("employee_deduction", $data);
	    }
    }

	function updateEmployeeOtherIncome($id='',$total_pay='',$total_deduc='',$deduc_hours=''){
		$res = $this->db->query("UPDATE other_income_computed SET amount_total='$total_pay', amount_deduc='$total_deduc', hours_deduc='$deduc_hours' WHERE id='$id'");
		return $res;
	}

	function insertProjectedEmployeeOtherIncome($employeeid='',$sdate='',$edate='',$codeIncome='',$total_pay=0,$schedule='',$quarter=''){
		$res = $this->db->query("INSERT INTO projected_income (employeeid,code_income,datefrom,amount,nocutoff,schedule,cutoff_period) VALUES ('$employeeid','$codeIncome','$sdate','$total_pay',1,'$schedule','$quarter')");
		return $res;
	}

	function computeEmployeeDeduction($empid='',$schedule='',$quarter='',$sdate='',$edate='',$arr_deduc_config='',$arr_deduc_config_arithmetic=''){
		$arr_info = array();
		$str_deduc = '';
		$total_deducSub = $total_deducAdd = 0;
		if($quarter == 1){
			$res = $this->deducttitle($empid,'amount','SHOW',$schedule,$quarter,''); // PAYROLLOPTIONS
			// echo "<pre>"; print_r($this->db->last_query()); die;
			foreach ($res->result() as $key => $row) {
				$arr_info[$row->code_deduction] = $row->title;
				if ($arr_deduc_config_arithmetic[$row->code_deduction]['description'] == "sub") {
					 $total_deducSub += $row->title;
				}else{
					 $total_deducAdd += $row->title;	
				}
				// $total_deduc += $row->title;
				$arr_deduc_config[$row->code_deduction]['hasData'] = 1;
				if($str_deduc) $str_deduc .= '/';
				$str_deduc .= $row->code_deduction . '=' . $row->title;
			}
		}
			

		return array($arr_deduc_config,$arr_info,$total_deducSub,$total_deducAdd,$str_deduc);
	}

	function deducttitle($eid = "",$title = "",$visible = "",$schedule = "",$quarter = "",$colname = ""){
        $whereClause = " AND visibility='$visible'";
        if($eid)     $whereClause = " AND employeeid='$eid' AND visibility='$visible'";
        if($schedule){
            $whereClause .= " AND schedule='$schedule'";
            if($schedule == "semimonthly"){
                if($quarter)  $whereClause .= " AND FIND_IN_SET(cutoff_period,'$quarter,3')";
            }else             $whereClause .= " AND cutoff_period='$quarter'";  
        }
        if($visible == "SHOW")  $whereClause .= " AND nocutoff > 0";
        if($colname)    $whereClause .= " AND code_deduction='$colname'";
        $query = $this->db->query("SELECT IFNULL($title,0) as title, code_deduction FROM employee_deduction  INNER JOIN payroll_deduction_config b ON(b.id = code_deduction) WHERE code_deduction <> '' $whereClause GROUP BY code_deduction");
        return $query;
   }

	function getExistingWithholdingTax($employeeid, $date){
		$query_whtax = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE date_effective <= '$date' AND employeeid = '$employeeid' ORDER BY date_effective DESC LIMIT 1 ");
		if($query_whtax->num_rows() > 0) return $query_whtax->row()->whtax;
		else return false;
	}

	public function taxComputation($employeeid, $fixed_deduc, $other_deduc, $str_fixeddeduc, $salary, $provident, $basic_personal_exception, $year="2023", $cutoffstart, $cutoffend){
		$bonus_threshold = 0;
		$income = $this->db->query("SELECT * FROM employee_income a INNER JOIN payroll_income_config b ON a.code_income = b.id WHERE employeeid = '$employeeid' AND isBonus = '1'");

		// GET BONUS THRESHOLD
		$str_bonus_threshold = "";
		if($income->num_rows() > 0){
			foreach($income->result() as $inc){
				$bonus_threshold += $inc->amount;

				if($str_bonus_threshold) $str_bonus_threshold .= '/';
				$str_bonus_threshold .= $inc->code_income . '=' . $inc->amount;
			}
		}

		$grosspay = $salary - array_sum($fixed_deduc) - array_sum($other_deduc) - $provident;
		$grosspay *= 12; //MULTIPLY GROSS PAY TO 12 TO GET YEARLY GROSS PAY

		$yearly_gross = $bonus_threshold + $grosspay;

		$annual_tax = $yearly_gross - 90000; 	// LESS: 90,000
		if(is_numeric($basic_personal_exception)) $annual_tax -= $basic_personal_exception;	// LESS: BASIC PERSONAL EXEPTION EXEPTION

		$excess_percent = "";
		$whtax = 0;
		$tax_config_q = $this->db->query("SELECT * FROM code_yearly_tax WHERE '$annual_tax' BETWEEN tib_from AND tib_to AND year = '$year'");
		if($tax_config_q->num_rows() > 0){
			$tax_config = $tax_config_q->row(0);
			// print_r($tax_config);die;
			// $excess_percent = $tax_config->of_excess_over;
			// $whtax = (( $annual_tax - $tax_config->tib_from ) * ($excess_percent/100) ) + $tax_config->additional_rate;
			$percent = $tax_config->of_excess_over/100;
			$excess = $tax_config->additional_rate;
			$whtax = ($annual_tax - $excess) * $percent;
		}

		// divide to 12
		$whtax /= 12;

		// delete existing trail
		$this->db->query("DELETE FROM tax_breakdown WHERE employeeid = '$employeeid' AND cutoffstart = '$cutoffstart' AND cutoffend = '$cutoffend' ");

		$tax_breakdown = array(
			"employeeid" => $employeeid,
			"cutoffstart" => $cutoffstart,
			"cutoffend" => $cutoffend,
			"bonus_threshold" => $str_bonus_threshold,
			"grosspay" => $grosspay,
			"fixed_deduc" => $str_fixeddeduc,
			"provident" => $provident,
			"less" => 90000,
			"bpe" => $basic_personal_exception,
			"annual_tax" => $annual_tax,
			"excess_percent" => $excess_percent,
			"processed_by" => $this->user
		);
		$this->db->insert("tax_breakdown", $tax_breakdown);

		return $whtax;

	}

	function latestTaxExcess($eid, $year){
		$q_tax = $this->db->query("SELECT * FROM tax_breakdown WHERE employeeid = '$eid' AND YEAR(cutoffstart) = '$year' ORDER BY cutoffstart DESC LIMIT 1");
		if($q_tax->num_rows() > 0){
			return array($q_tax->row()->excess_percent, $q_tax->row()->annual_tax);
		}else{
			return 0;
		}
	}

	function getUseCTOApplications($eid, $sdate, $edate){
		$cto = 0;
		$query = $this->db->query("SELECT * FROM employee_cto_usage WHERE employeeid = '$eid' AND (date_applied BETWEEN '$sdate' AND '$edate') AND app_status = 'APPROVED' AND credited = '0'");
		if($query->num_rows() > 0){
			foreach ($query->result() as $key => $value) {
				$cto += $this->time->exp_time($value->total);
			}

			$cto = $this->time->sec_to_hm($cto);
		}
		return $cto;
	}

	function getEmployeeSalaryRate($regpay, $daily, $employeeid, $sdate){
		$p_history = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid = '$employeeid' AND date_effective <= '$sdate' ORDER BY date_effective DESC LIMIT 1");
		if($p_history->num_rows() > 0) return array($p_history->row()->semimonthly, $p_history->row()->daily);
		else return array($regpay, $daily);
	}
	
	function savePayrollCutoffSummaryDraft($data=array(),$data_oth=array()){
		$data['addedby']   = $this->user;
		$base_id = $this->insertSingleTblData('payroll_computed_table',$data);
		if($base_id){

			if(sizeof($data_oth['ee_er']) > 0){
				foreach ($data_oth['ee_er'] as $code => $amt) {
					$amt['EE'] = round($amt['EE'],2);
					$amt['EC'] = round($amt['EC'],2);
					$amt['ER'] = round($amt['ER'],2);
					$this->insertSingleTblData('payroll_computed_ee_er',array('base_id'=>$base_id,'code_deduction'=>$code,'EE'=>$amt['EE'],'EC'=>$amt['EC'],'ER'=>$amt['ER'],'provident_er'=>$amt['provident_er']));
				}
			}
			

			if(sizeof($data_oth['perdept_amt_arr']) > 0){ ///< perdept amount details saving
				foreach ($data_oth['perdept_amt_arr'] as $aimsdept => $classification_arr) {
					foreach ($classification_arr as $classification => $leclab_arr) {
						foreach ($leclab_arr as $type => $amt) {
							$this->insertSingleTblData('payroll_computed_perdept_detail',array('base_id'=>$base_id,'type'=>$type,'aimsdept'=>$aimsdept,'work_amount'=>$amt['work_amount'],'late_amount'=>$amt['late_amount'],'deduc_amount'=>$amt['deduc_amount'],'classification'=>$classification));
						}
					}
				}
			}

			if(sizeof($data_oth['ot_det']) > 0){
				foreach ($data_oth['ot_det'] as $att_baseid => $amt) {
					$amt = round($amt,2);
					$this->insertSingleTblData('payroll_computed_overtime',array('base_id'=>$base_id,'att_baseid'=>$att_baseid,'amount'=>$amt));
				}
			}

		} //< end main if

		return $base_id;
	}

	function insertSingleTblData($tbl='',$insert_data=array()){
		$res = false;
        if(sizeof($insert_data) > 0) 	$res = $this->db->insert($tbl,$insert_data); 
        if($res) $res = $this->db->insert_id();
        return $res;
    }

	function isEmployeeTeachingOnly($employeeid) {
		$schedule = $this->db->query("SELECT employeeid FROM employee_schedule WHERE employeeid='{$employeeid}' AND leclab<>'LEC'");
		return $schedule->num_rows() == 0;
	}

    function constructArrayListFromComputedTable($str=''){
	    $arr = array();
	    if($str){
	        $str_arr = explode('/', $str);
	        if(count($str_arr)){
	            foreach ($str_arr as $i_temp) {
	                $str_arr_temp = explode('=', $i_temp);
	                if(isset($str_arr_temp[0]) && isset($str_arr_temp[1])){
	                    $arr[$str_arr_temp[0]] = $str_arr_temp[1];
	                }
	            }
	        }
	    }
	    return $arr;
	}
    
    function showdepartment($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("code,description");
        $this->db->order_by("description","asc");
        $q = $this->db->get("code_department"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->code] = $row->description;
        }
        return $returns;
    }

    function payrollSubTotal($emplist=array()){
		$total = array();
		$old_deptid = "";
		if($emplist){
			foreach($emplist as $row){
				// echo "<pre>"; print_r($row); die;
				$deptid = $row["deptid"];
				if($row["loan"]){
					foreach($row["loan"] as $key => $val){
						if(isset($total[$deptid]["loan"][$key])) $total[$deptid]["loan"][$key] += $val;
						else $total[$deptid]["loan"][$key] = $val;
					}
				}

				if($row["fixeddeduc"]){
					foreach($row["fixeddeduc"] as $key => $val){
						if($val == "") $val = 0;
						if(isset($total[$deptid]["fixeddeduc"][$key])) $total[$deptid]["fixeddeduc"][$key] += $val;
						else $total[$deptid]["fixeddeduc"][$key] = $val;
					}
				}

				if($row["deduction"]){
					foreach($row["deduction"] as $key => $val){
						if(isset($total[$deptid]["deduction"][$key])) $total[$deptid]["deduction"][$key] += $val;
						else $total[$deptid]["deduction"][$key] = $val;
					}
				}

				if($row["income_adj"]){
					foreach($row["income_adj"] as $key => $val){
						if(isset($total[$deptid]["income_adj"][$key])) $total[$deptid]["income_adj"][$key] += $val;
						else $total[$deptid]["income_adj"][$key] = $val;
					}
				}

				if($row["income"]){
					foreach($row["income"] as $key => $val){
						if(isset($total[$deptid]["income"][$key])) $total[$deptid]["income"][$key] += $val;
						else $total[$deptid]["income"][$key] = $val;
					}
				}

				if(isset($total[$deptid]["salary"])) $total[$deptid]["salary"] += $row["salary"];
				else $total[$deptid]["salary"] = $row["salary"];

				if(isset($total[$deptid]["teaching_pay"])) $total[$deptid]["teaching_pay"] += $row["teaching_pay"];
				else $total[$deptid]["teaching_pay"] = $row["teaching_pay"];

				if(isset($total[$deptid]["tardy"])) $total[$deptid]["tardy"] += $row["tardy"];
				else $total[$deptid]["tardy"] = $row["tardy"];

				if(isset($total[$deptid]["absents"])) $total[$deptid]["absents"] += $row["absents"];
				else $total[$deptid]["absents"] = $row["absents"];

				if(isset($total[$deptid]["overtime"])) $total[$deptid]["overtime"] += $row["overtime"];
				else $total[$deptid]["overtime"] = $row["overtime"];

				if(isset($total[$deptid]["provident_premium"])) $total[$deptid]["provident_premium"] += $row["provident_premium"];
				else $total[$deptid]["provident_premium"] = $row["provident_premium"];

				if(isset($total[$deptid]["whtax"])) $total[$deptid]["whtax"] += $row["whtax"];
				else $total[$deptid]["whtax"] = $row["whtax"];

				if(isset($total[$deptid]["netbasicpay"])) $total[$deptid]["netbasicpay"] += $row["netbasicpay"];
				else $total[$deptid]["netbasicpay"] = $row["netbasicpay"];

				if(isset($total[$deptid]["grosspay"])) $total[$deptid]["grosspay"] += $row["grosspay"];
				else $total[$deptid]["grosspay"] = $row["grosspay"];

				if(isset($total[$deptid]["netpay"])) $total[$deptid]["netpay"] += $row["netpay"];
				else $total[$deptid]["netpay"] = $row["netpay"];
				if(isset($row["substitute"])){
					if(isset($total[$deptid]["substitute"])) $total[$deptid]["substitute"] += $row["substitute"];
					else $total[$deptid]["substitute"] = $row["substitute"];
				}
			}
		}

		return $total;
	}
    
    function payrollGrandTotal($emplist=array()){
		$total = array();
		if($emplist){
			foreach($emplist as $row){
				// echo "<pre>"; print_r($row); die;
				if($row["loan"]){
					foreach($row["loan"] as $key => $val){
						if(isset($total["loan"][$key])) $total["loan"][$key] += $val;
						else $total["loan"][$key] = $val;
					}
				}

				if($row["fixeddeduc"]){
					foreach($row["fixeddeduc"] as $key => $val){
						if($val == "") $val = 0;
						if(isset($total["fixeddeduc"][$key])) $total["fixeddeduc"][$key] += $val;
						else $total["fixeddeduc"][$key] = $val;
					}
				}

				if($row["deduction"]){
					foreach($row["deduction"] as $key => $val){
						if(isset($total["deduction"][$key])) $total["deduction"][$key] += $val;
						else $total["deduction"][$key] = $val;
					}
				}

				if($row["income_adj"]){
					foreach($row["income_adj"] as $key => $val){
						if(isset($total["income_adj"][$key])) $total["income_adj"][$key] += $val;
						else $total["income_adj"][$key] = $val;
					}
				}

				if($row["income"]){
					foreach($row["income"] as $key => $val){
						if(isset($total["income"][$key])) $total["income"][$key] += $val;
						else $total["income"][$key] = $val;
					}
				}

				if(isset($total["salary"])) $total["salary"] += $row["salary"];
				else $total["salary"] = $row["salary"];

				if(isset($row["pera"])){
					if(isset($total["pera"])) $total["pera"] += $row["pera"];
					else $total["pera"] = $row["pera"];
				}

				if(isset($total["teaching_pay"])) $total["teaching_pay"] += $row["teaching_pay"];
				else $total["teaching_pay"] = $row["teaching_pay"];

				if(isset($total["tardy"])) $total["tardy"] += $row["tardy"];
				else $total["tardy"] = $row["tardy"];

				if(isset($total["absents"])) $total["absents"] += $row["absents"];
				else $total["absents"] = $row["absents"];

				if(isset($total["overtime"])) $total["overtime"] += $row["overtime"];
				else $total["overtime"] = $row["overtime"];

				if(isset($total["provident_premium"])) $total["provident_premium"] += $row["provident_premium"];
				else $total["provident_premium"] = $row["provident_premium"];

				if(isset($total["whtax"])) $total["whtax"] += $row["whtax"];
				else $total["whtax"] = $row["whtax"];

				if(isset($total["netbasicpay"])) $total["netbasicpay"] += $row["netbasicpay"];
				else $total["netbasicpay"] = $row["netbasicpay"];

				if(isset($total["grosspay"])) $total["grosspay"] += $row["grosspay"];
				else $total["grosspay"] = $row["grosspay"];

				if(isset($total["netpay"])) $total["netpay"] += $row["netpay"];
				else $total["netpay"] = $row["netpay"];

				if(isset($row["substitute"])){
					if(isset($total["substitute"])) $total["substitute"] += $row["substitute"];
					else $total["substitute"] = $row["substitute"];
				}
			}
		}

		return $total;
	}
}