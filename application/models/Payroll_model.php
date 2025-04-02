<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll_model extends CI_Model {

	private $user;

	public function __construct() {
		parent::__construct();
        $this->load->model("time", "time");
	}

	public function getServerTime(){
		$query_time = $this->db->query("SELECT CURRENT_TIMESTAMP ")->row()->CURRENT_TIMESTAMP;
		return $query_time;
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

    function loadAllEmpbyDeptForPayslip($dept = "", $eid = "", $sched = "",$sort = "", $payroll_cutoffstart ,$includeResigned=true,$adminside ='', $campus='', $company= '', $bank = '', $tnt = '', $user=''){
        $this->user = $user;

        $data = array();
        $whereClause = "";
        $old_empid = "";
        $orderby = "";
        if($dept)   $whereClause .= " AND b.deptid='$dept'";
        if($eid)    $whereClause .= " AND b.employeeid IN ($eid)";
        if($campus && $campus!="All")   $whereClause .= " AND b.campusid = '$campus'";
        if($company && $company != 'all')   $whereClause .= ' AND b.company_campus = "'.$company.'"';
        if($bank)   $whereClause .= " AND c.bank = '$bank'";
        else        $orderby .= " ORDER BY b.deptid, fullname, timestamp DESC";

        if($tnt){
            if($tnt != "trelated") $whereClause .= " AND b.teachingtype='$tnt'";
            else $whereClause .= " AND b.teachingtype = 'teaching' AND trelated = '1'";
        }
  
        if(!$includeResigned) $whereClause .= " AND (b.dateresigned = '1970-01-01' OR b.dateresigned IS NULL OR b.dateresigned = '0000-00-00')"; 
        if($payroll_cutoffstart)          $whereClause .= " AND (dateresigned > '$payroll_cutoffstart' OR b.dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00')
                                                AND a.date_effective <= '$payroll_cutoffstart'";
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
        $whereClause .= $utwc;
  
        $sql = $this->db->query("SELECT COUNT(a.id) AS count
                                   FROM payroll_employee_salary_history a
                                   INNER JOIN payroll_computed_table c ON a.employeeid = c.employeeid 
                                   INNER JOIN employee b ON b.employeeid = a.employeeid
                                   WHERE a.schedule='$sched' $whereClause");
        $count = $sql->row()->count;
        $start = 0; $limit = 10000;

        while ($start < $count) {
            $query = $this->db->query("SELECT a.id, a.`employeeid`, a.`workdays`, a.`fixedday`, a.`workhours`, a.`workhoursexemp`, a.`monthly`, a.`semimonthly`, a.`biweekly`, a.`weekly`, a.`daily`, a.`hourly`, a.`minutely`, a.`schedule`, a.`dependents`, a.`whtax`, a.`absent`, a.`absentbalance`, a.`addedby`, a.`lechour`, a.`labhour`, a.`honorarium`, a.`date_effective`, a.`timestamp`, CONCAT(lname,', ',fname,' ',mname) AS fullname, a.`monthly` AS regpay, b.`teachingtype` , b.`deptid`, b.`office`, b.`emp_accno`, c.`bank`, c.`date_processed`, b.`employmentstat`
                                   FROM payroll_employee_salary_history a
                                   INNER JOIN payroll_computed_table c ON a.employeeid = c.employeeid 
                                   INNER JOIN employee b ON b.employeeid = a.employeeid
                                   WHERE a.schedule='$sched' $whereClause $orderby LIMIT $start, $limit");

            if($query->num_rows() > 0){
                foreach ($query->result() as $key => $value) {
                if($old_empid != $value->employeeid){
                    $data[] = array(
                    "id" => $value->id,
                    "employeeid" => $value->employeeid,
                    "workdays" => $value->workdays,
                    "fixedday" => $value->fixedday,
                    "workhours" => $value->workhours,
                    "workhoursexemp" => $value->workhoursexemp,
                    "monthly" => $value->monthly,
                    "semimonthly" => $value->semimonthly,
                    "biweekly" => $value->biweekly,
                    "weekly" => $value->weekly,
                    "daily" => $value->daily,
                    "hourly" => $value->hourly,
                    "minutely" => $value->minutely,
                    "schedule" => $value->schedule,
                    "dependents" => $value->dependents,
                    "whtax" => $value->whtax,
                    "absent" => $value->absent,
                    "absentbalance" => $value->absentbalance,
                    "addedby" => $value->addedby,
                    "lechour" => $value->lechour,
                    "labhour" => $value->labhour,
                    "honorarium" => $value->honorarium,
                    "date_effective" => $value->date_effective, 
                    "timestamp" => $value->timestamp,
                    "fullname" => $value->fullname,
                    "regpay" => $value->regpay,
                    "teachingtype" =>$value->teachingtype, 
                    "deptid" => $value->deptid,
                    "office" => $value->office,
                    "emp_accno" => $value->emp_accno, 
                    "bank" => $value->bank, 
                    "date_processed" => $value->date_processed,
                    "employmentstat" => $value->employmentstat 
                    );
                }
                $old_empid = $value->employeeid;
                }
            }

            $start += $limit;
        }
  
        return $data;
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

    function getPayslipSummary($emplist=array(), $sdate='',$edate='',$schedule='',$quarter='',$bank='',$status='PROCESSED'){
		//< initialize needed info ---------------------------------------------------
		$arr_info    = $arr_income_config = $arr_incomeoth_config = $arr_deduc_config = $arr_fixeddeduc_config = $arr_loan_config = array();

		///< ------------------------------ income config ------------------------------------------------------------
		$income_config_q = $this->displayIncome();
		$arr_income_config = $this->constructArrayListFromStdClass($income_config_q,'id','taxable');
		$arr_income_config_desc = $this->constructArrayListFromStdClass($income_config_q,'id','description');


		///< ------------------------------ incomeoth config ---------------------------------------------------------------
		$incomeoth_config_q = $this->displayIncomeOth();
		$arr_incomeoth_config = $this->constructArrayListFromStdClass($incomeoth_config_q,'id','description');

		///< ------------------------------ fixed deduction config ----------------------------------------------------
		$fixeddeduc_config_q = $this->db->query("SELECT code_deduction,description FROM deductions");
		$arr_fixeddeduc_config = $this->constructArrayListFromStdClass($fixeddeduc_config_q,'code_deduction','description');


		///< ------------------------------ deduction config ----------------------------------------------------------
		$deduction_config_q = $this->displayDeduction();
		$arr_deduc_config = $this->constructArrayListFromStdClass($deduction_config_q,'id','arithmetic');
		$arr_deduc_config_desc = $this->constructArrayListFromStdClass($deduction_config_q,'id','description');

		///< ------------------------------ loan config ---------------------------------------------------------------
		$loan_config_q = $this->displayLoan();
		$arr_loan_config = $this->constructArrayListFromStdClass($loan_config_q,'id','description');


		foreach ($emplist as $row) {
			//< $emplist as row database table ["payroll_employee_salary"]
			$empid = $row["employeeid"];
			$deptcode = $row["deptid"];
			$teachingtype = $row["teachingtype"];
			

			///< check for computation
			$res = $this->getPayrollSummary($status,$sdate,$edate,$schedule,$quarter,$empid,FALSE,$bank);


			if($res->num_rows() > 0){

				$regpay =  $row["regpay"];
				$dependents = $row["dependents"];

				$arr_info[$empid]['income'] = $arr_info[$empid]['deduction'] = $arr_info[$empid]['fixeddeduc'] = $arr_info[$empid]['loan'] = array();

				$arr_info[$empid]['fullname']   	= $row["fullname"];
				$arr_info[$empid]['deptid']     	= $row["deptid"];
				$arr_info[$empid]['office']     	= $row["office"];
				$arr_info[$empid]['bank'] 		    = $row["bank"];
				$arr_info[$empid]['date_processed'] = $row["date_processed"];


				// $arr_info[$empid]['salary'] 	= $regpay;

				

				//< rates
				$hourly = $arr_info[$empid]['hourly'] = $row["hourly"];
				$daily = $arr_info[$empid]['daily'] = $row["daily"];

				$res 							= $res->row(0);

				$arr_info[$empid]['salary'] = $res->salary;

				$arr_info[$empid]['overtime'] 	= $res->overtime;
				$tardy = $arr_info[$empid]['tardy'] 		= $res->tardy;
				$arr_info[$empid]['absents'] 	= $res->absents;
				$arr_info[$empid]['whtax'] 		= $res->withholdingtax;
				$arr_info[$empid]['editedby'] 	= $res->editedby;
				$arr_info[$empid]['netbasicpay'] = $res->netbasicpay;
				$arr_info[$empid]['grosspay'] 	= $res->gross;
				$arr_info[$empid]['netpay'] 	= $res->net;
				$arr_info[$empid]['provident_premium'] 	= $res->provident_premium;
				$arr_info[$empid]['teaching_pay'] 	= $res->teaching_pay;
				$arr_info[$empid]['pera'] 	= $res->pera;

				//teachers load
				$lechour = $arr_info[$empid]['lechour'] = isset($row->lechour) ? $row->lechour : 0;
				$labhour = $arr_info[$empid]['labhour'] = isset($row->labhour) ? $row->labhour : 0;
				list($arr_info[$empid]["total_leclab_pay"],
					 $arr_info[$empid]["workhours_lec"],$arr_info[$empid]["total_lec_pay"],
					 $arr_info[$empid]["workhours_lab"],$arr_info[$empid]["total_lab_pay"],
					 $arr_info[$empid]["absenthourly"],$arr_info[$empid]["latehourly"],$arr_info[$empid]["overtimeHour"]) = $this->displayAbsentLateUndertime($empid,$quarter,$sdate,$edate,$teachingtype,$hourly,$lechour,$labhour,$tardy);


				//< income
				$overtime_detailed = $this->payslipOvertimeDetailed($empid, $sdate, $edate); // ATTCOMPUTE
				$arr_info[$empid]["overtime_detailed"] = $overtime_detailed;
				$income_arr 				= $this->constructArrayListFromComputedTable($res->income); //PAYROLLPROCESS
				$arr_info[$empid]['income'] = $income_arr;
				$totalIncome = 0;
				$totalIncomeNonTaxable = 0;
				$totalIncomeTaxable = 0;

				foreach ($income_arr as $k => $v) {
					if($arr_income_config[$k]['description'] == "withtax"){
							$totalIncomeTaxable += $v;
					}else{
						  $totalIncomeNonTaxable += $v;
					}
					$totalIncome += $v;
				}

				$arr_info[$empid]["perdept_amt"] = $this->getPerdeptDetail($empid, $sdate, $edate, $teachingtype);

				$arr_info[$empid]["totalIncome"] = $totalIncome;
				$arr_info[$empid]["totalIncomeTaxable"] = $totalIncomeTaxable;
				$arr_info[$empid]["totalIncomeNonTaxable"] = $totalIncomeNonTaxable;

				///< fixed deduc
		        $fixeddeduc_arr = $this->constructArrayListFromComputedTable($res->fixeddeduc); //PAYROLLPROCESS
		        $arr_info[$empid]['fixeddeduc'] = $fixeddeduc_arr;
		        foreach ($fixeddeduc_arr as $k => $v) {$arr_fixeddeduc_config[$k]['hasData'] = 1;}

		        ///< deduc
		        $deduc_arr = $this->constructArrayListFromComputedTable($res->otherdeduc); //PAYROLLPROCESS
		        $arr_info[$empid]['deduction'] = $deduc_arr;

		        $totalOtherDeducSub = $totalOtherDeducAdd = 0;
		        foreach ($deduc_arr as $k => $v) {
		        	if($arr_deduc_config[$k]['description'] == "sub"){
		        		$totalOtherDeducSub += $v;
		        	}else{
		        		$totalOtherDeducAdd += $v;
		        	}

		    	}
		    	
		    	$total_loan = 0;
		        ///< loan
		        $loan_arr = $this->constructArrayListFromComputedTable($res->loan); //PAYROLLPROCESS
		        $arr_info[$empid]['loan'] = $loan_arr;
		        foreach ($loan_arr as $k => $v) {
		        	$total_loan += $v;
		        }

		        //totals
		        $arr_info[$empid]["totalOtherDeduc"] = $totalOtherDeducSub - $totalOtherDeducAdd + $total_loan;
		        $arr_info[$empid]["semitotalPay"] = $arr_info[$empid]['salary'] + $arr_info[$empid]['overtime'] + $arr_info[$empid]["totalIncome"];

			}

		} //end loop emplist

		
		$data['emplist'] = $arr_info;
		$data['income_config'] = $arr_income_config;
		$data['income_config_desc'] = $arr_income_config_desc;
		$data['incomeoth_config'] = $arr_incomeoth_config;
		$data['fixeddeduc_config'] = $arr_fixeddeduc_config;
		$data['deduction_config'] = $arr_deduc_config;
		$data['deduction_config_desc'] = $arr_deduc_config_desc;
		$data['loan_config'] = $arr_loan_config;
		$data['sdate'] = $sdate;
		$data['edate'] = $edate;

		return $data;
	}

    public function getPerdeptDetail($empid, $sdate, $edate, $teachingtype){
		$_att = "";
		if($teachingtype == "teaching"){
			$q_att = $this->db->query(" SELECT b.*
	        FROM attendance_confirmed a 
	        INNER JOIN workhours_perdept b ON a.id = b.base_id
	        WHERE a.employeeid = '$empid' AND a.payroll_cutoffstart = '$sdate' AND a.payroll_cutoffend = '$edate' AND a.status = 'PROCESSED' ");
		}else{
			$q_att = $this->db->query(" SELECT b.*
	        FROM attendance_confirmed_nt a 
	        INNER JOIN workhours_perdept_nt b ON a.id = b.base_id
	        WHERE a.employeeid = '$empid' AND a.payroll_cutoffstart = '$sdate' AND a.payroll_cutoffend = '$edate' AND a.status = 'PROCESSED' ");
		}

		/*get included only in computation -- this is for nursing department*/
		if($this->isNursingDepartment($empid) > 0 && !$this->isNursingExcluded($empid)){ //EXTENSIONS
			$nursing_included = $this->nursingIncludedPerdept($q_att->result()); 
			$perdept_salary = $nursing_included;
			$perdept_salary = $q_att->result();
		}else{
			$perdept_salary = $q_att->result();
		}

		$perdept_list = array();
		if($perdept_salary){
			foreach($perdept_salary as $att_row){
				$perdept_list[$att_row->aimsdept][$att_row->type]["work_hours"] = $att_row->work_hours;
				$perdept_list[$att_row->aimsdept][$att_row->type]["late_hours"] = $att_row->late_hours;
				$perdept_list[$att_row->aimsdept][$att_row->type]["deduc_hours"] = $att_row->deduc_hours;
			}
		}
		$perdept_payroll = $this->getWorkHoursPerdept($empid, $sdate, $edate);
		if($perdept_payroll){
			foreach($perdept_payroll as $payroll_row){
				if(isset($perdept_list[$payroll_row->aimsdept][$payroll_row->type])){
					$perdept_list[$payroll_row->aimsdept][$payroll_row->type]["work_amount"] = $payroll_row->work_amount;
					$perdept_list[$payroll_row->aimsdept][$payroll_row->type]["late_amount"] = $payroll_row->late_amount;
					$perdept_list[$payroll_row->aimsdept][$payroll_row->type]["deduc_amount"] = $payroll_row->deduc_amount;
				}
			}
		}
	
		return $perdept_list;
	}

    public function getWorkHoursPerdept($employeeid, $dfrom, $dto){
        $wc = '';
        if($employeeid) $wc = " AND employeeid IN ('$employeeid')";
        $q_workhours = $this->db->query("SELECT b.*, c.`DESCRIPTION` FROM payroll_computed_table a LEFT JOIN payroll_computed_perdept_detail b ON a.id = b.`base_id` LEFT JOIN tblCourseCategory c ON b.`aimsdept` = c.`CODE` WHERE cutoffstart = '$dfrom' AND cutoffend = '$dto' $wc ORDER BY type ");
        if($q_workhours->num_rows() > 0) return $q_workhours->result();
        else return false;
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

	public function isNursingDepartment($eid){
		return $this->db->query("SELECT * FROM employee WHERE (deptid = '14' OR office = '122') AND employeeid = '$eid'")->num_rows();
	}

	public function isNursingExcluded($eid){
		return $this->db->query("SELECT * FROM employee WHERE (deptid = '14' OR office = '122') AND nursing_excluded = '1' AND employeeid = '$eid'")->num_rows();
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

    public function payslipOvertimeDetailed($empid, $d_cutoffrom, $d_cutoffto){
        $ot_amount = 0;
        $ot_data = array();

        $rate_per_hour = ($this->getEmployeeSalaryRate1($empid, "hourly")); //INCOME
        $rate_per_minute = $rate_per_hour / 60;
        $rate_per_minute = number_format($rate_per_minute, 2, '.', '');
        $employeement_status = $this->getEmploymentStatus($empid); //EXTRAS
        $setup = $this->getOvertimeSetup($employeement_status); //PAYROLLCOMPUTATION
        $q_att = $this->db->query("SELECT * FROM `attendance_confirmed_nt` WHERE employeeid = '$empid' AND payroll_cutoffstart = '$d_cutoffrom' AND payroll_cutoffend = '$d_cutoffto' ");
        if($q_att->num_rows() > 0){
            $att_id = $q_att->row()->id;
            $q_ot = $this->db->query("SELECT * FROM attendance_confirmed_nt_ot_hours where base_id = '$att_id' ");
            if($q_ot->num_rows() > 0){
                foreach($q_ot->result_array() as $row){
                    $sel_setup = ($row["holiday_type"]) ? 0 : 1;
                    $is_excess = ($row["is_excess"]) ? 1 : 0;
                    $ot_min = $this->time->hoursToMinutes($row["ot_hours"]);
                    $ot_hour = $ot_min / 60;
                    
                    $percent = 100;
                    if(isset($setup[$employeement_status][$row["ot_type"]][$row["holiday_type"]])) $percent = $setup[$employeement_status][$row["ot_type"]][$row["holiday_type"]][$is_excess];
                    $percent = $percent / 100;
                    
                    $hourly_rate = $rate_per_hour * $percent;
                    $ot_amount = $hourly_rate * $ot_hour;
                    // $ot_amount = floatval($ot_amount);
                    if(!isset($ot_data[$row["ot_type"]][$row["holiday_type"]])) $ot_data[$row["ot_type"]][$row["holiday_type"]]["ot_hours"] = $this->time->exp_time($row["ot_hours"]);
                    else $ot_data[$row["ot_type"]][$row["holiday_type"]]["ot_hours"] += $this->time->exp_time($row["ot_hours"]);

                    if(!isset($ot_data[$row["ot_type"]][$row["holiday_type"]])) $ot_data[$row["ot_type"]][$row["holiday_type"]]["ot_amount"] = $ot_amount;
                    else $ot_data[$row["ot_type"]][$row["holiday_type"]]["ot_amount"] += $ot_amount;

                }
            }
        }
        // Globals::pd($ot_data); die;
        return $ot_data;
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

	function getSingleTblData($tbl='',$fields=array(),$filter=array(),$order_by='',$limit=0){
        $this->db->select($fields);
        if($order_by) $this->db->order_by($order_by);
        if($limit) 		$this->db->limit($limit);
        if(sizeof($filter) > 0) $data_q = $this->db->get_where($tbl,$filter); 
        else 					$data_q = $this->db->get($tbl); 
        return $data_q;
    }

    function getEmploymentStatus($empid){
        $query = $this->db->query("SELECT employmentstat FROM employee WHERE employeeid = '$empid' ");
        if($query->num_rows() > 0) return $query->row()->employmentstat;
        else return "REG";
    }

    function getEmployeeSalaryRate1($employeeid, $column){
    	$query = $this->db->query("SELECT * FROM payroll_employee_salary WHERE employeeid = '$employeeid' ORDER BY TIMESTAMP DESC LIMIT 1 ");
    	if($query->num_rows() > 0) return $query->row()->$column;
    	else return false;    	
    }

    function displayAbsentLateUndertime($eid,$quarter,$dfrom,$dto,$teachingtype,$hourly,$lechour,$labhour,$tardy){
        $return = "";
        //check the employee if teaching or non-teaching
    
        if($teachingtype == "teaching"){
        $query = $this->db->query(" SELECT a.overload,a.workhours_lec,a.workhours_lab,a.timestamp,a.status,a.payroll_cutoffstart,a.payroll_cutoffend,a.deduclec,a.deduclab,a.deducadmin,a.latelec,a.latelab,a.lateadmin
            FROM attendance_confirmed a 
            WHERE a.employeeid = '$eid' AND a.payroll_cutoffstart = '$dfrom' AND a.payroll_cutoffend = '$dto' AND a.quarter = '$quarter' AND a.status = 'SUBMITTED' ")->result_array();
    
         $absences = $lates = $overtimes = $lecinMinutes = $labinMinutes = 0;
    
        foreach ($query as $row) {
    
            $absences += $this->time->hoursToMinutes($row['deduclec']) + $this->time->hoursToMinutes($row['deduclab']) + $this->time->hoursToMinutes($row['deducadmin']);
            $lates += $this->time->hoursToMinutes($row['latelec']) + $this->time->hoursToMinutes($row['latelab']) + $this->time->hoursToMinutes($row['lateadmin']);
            ($row['workhours_lec'] != null || $row['workhours_lec'] != "") ? $lecinMinutes += $this->time->hoursToMinutes($row['workhours_lec']) : $lecinMinutes += 0;
            ($row['workhours_lab'] != null || $row['workhours_lec'] != "") ? $labinMinutes += $this->time->hoursToMinutes($row['workhours_lab']) :  $labinMinutes += 0;
            ($row['overload'] != null || $row['overload'] != "") ? $overtimes += $this->time->hoursToMinutes($row['overload']) : $overtimes += 0;
        }
        $lecinHour = $lecinMinutes / 60;
        $labinHour = $labinMinutes / 60;
    
    
    
        $absent = $this->time->minutesToHours($absences)." (h:m)";
        $late = $this->time->minutesToHours($lates)." (h:m)";
        $overtime = $this->time->minutesToHours($overtimes)." (h:m)";
        $total_workhours_lec = $this->time->minutesToHours($lecinMinutes);
        $total_workhours_lec_pay = $lechour * $lecinHour;
        $total_workhours_lab = $this->time->minutesToHours($labinMinutes);
        $total_workhours_lab_pay = $labhour * $labinHour;
        $total_leclab_pay = $total_workhours_lec_pay + $total_workhours_lab_pay;
    
        }else{
    
        $query = $this->db->query("SELECT a.status,a.payroll_cutoffstart,a.payroll_cutoffend,a.absent,a.lateut,a.otreg,a.otsat,a.otsun,a.othol
            FROM attendance_confirmed_nt a 
            WHERE a.employeeid = '$eid' AND a.payroll_cutoffstart = '$dfrom' AND a.payroll_cutoffend = '$dto' AND a.quarter = '$quarter' AND a.status = 'SUBMITTED' ")->result_array();	
        $absences = $lates = $overtimes = 0;
        foreach ($query as $row) {
    
            $absences += $this->time->hoursToMinutes($row['absent']);
            $lates += $this->time->hoursToMinutes($row['lateut']);
            $overtimes += $this->time->hoursToMinutes($row['otreg']) + $this->time->hoursToMinutes($row['otsat']) + $this->time->hoursToMinutes($row['otsun']) + $this->time->hoursToMinutes($row['othol']);
        }
    
         $absent = $absences / 60 / 8;
         $late = $this->time->minutesToHours($lates)." (h:m)";
         ($absent > 1) ? $absent = $absent." (Days)" : $absent= $absent." (Day)";
         $overtime = $this->time->minutesToHours($overtimes)." (h:m)";   
         // $dailyRate = ($daily)." (Rate/day)";
         // $hourlyRate = ($hourly)." (Rate/hour)";
         $total_workhours_lec = 0;
         $total_workhours_lec_pay = 0;
         $total_workhours_lab = 0;
         $total_workhours_lab_pay = 0;
         $total_leclab_pay = 0;
        }
    
        return array($total_leclab_pay,$total_workhours_lec,$total_workhours_lec_pay,$total_workhours_lab,$total_workhours_lab_pay,$absent,$late,$overtime);
    }

    function getPayrollSummary($status='',$cutoffstart='',$cutoffend='',$schedule='',$quarter='',$employeeid='',$checkCount=false,$status2='',$bank='',$refno="", $fund_type=""){
		// urs condition
		// $schedule = "monthly";
		$wC = '';
		if($employeeid)					$wC .= " AND employeeid='$employeeid'";
		// if($bank)						$wC .= " AND bank='$bank'";
		if($fund_type)						$wC .= " AND fund_type='$fund_type'";
		if($status && $status2) 		$wC .= " AND (status='$status' OR status='$status2')";
		elseif($status && !$status2)	$wC .= " AND status='$status'";
		$utwc = '';
        $user = $this->user;

        $utdept = $this->getEmployeeDepartment($user);
		$utoffice = $this->getEmployeeOffice($user);
		$userType = $this->getUserType($user);
        if($userType == "ADMIN"){
			if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (deptid, '$utdept')";
			if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (office, '$utoffice')";
			if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (deptid, '$utdept') OR FIND_IN_SET (office, '$utoffice'))";
			if(!$utdept && !$utoffice) $utwc =  " AND employeeid = 'nosresult'";
			$usercampus = $this->getCampusUser();
			$utwc .= " AND FIND_IN_SET (campusid,'$usercampus') ";
        }
        if($utwc) $wC .= " AND employeeid IN (SELECT employeeid FROM employee WHERE 1 $utwc)";
		if($refno) $wC .= " AND ref_no='$refno'";
		if($checkCount){
			$cutoff_exist_q = $this->db->query("SELECT count(id) AS existcount from payroll_computed_table WHERE cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND schedule='$schedule' AND quarter='$quarter' $wC");
			if($cutoff_exist_q->num_rows() > 0) return $cutoff_exist_q->row(0)->existcount;
			else 								return 0;
		}else{
			$payroll_q = $this->db->query("SELECT * FROM payroll_computed_table WHERE cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND schedule='$schedule' AND quarter='$quarter' $wC");
			return $payroll_q;
		}
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
    
    public function getBankName($bankCode){
        $query = $this->db->query("SELECT * FROM code_bank_account WHERE code = '$bankCode'");
        if($query->num_rows() > 0) return $query->row()->bank_name;
        else return "";
   }

    public function checkCompanyCampus($selected_campus='') {
        $returnCompanyCampus = "";
        switch ($selected_campus) {
            case 'ANG':
                $returnCompanyCampus = "URS "."ANGONO";
                break;
            case 'ANT':
                $returnCompanyCampus = "URS "."ANTIPOLO";
                break;
            case 'BIN':
                $returnCompanyCampus = "URS "."BINANGONAN";
                break;
            case 'CAR':
                $returnCompanyCampus = "URS "."CARDONA";
                break;
            case 'CNT':
                $returnCompanyCampus = "URS "."CAINTA";
                break;
            case 'MOR':
                $returnCompanyCampus = "URS "."MORONG";
                break;
            case 'PIL':
                $returnCompanyCampus = "URS "."PILILLA";
                break;
            case 'ROD':
                $returnCompanyCampus = "URS "."RODRIGUEZ";
                break;
            case 'TAY':
                $returnCompanyCampus = "URS "."TAYTAY";
                break;
            case 'TNY':
                $returnCompanyCampus = "URS "."TANAY";
                break;
            case 'AGO':
                $returnCompanyCampus = "URS "."ANGONO";
                break;
            default:
            $returnCompanyCampus = "URS (ALL CAMPUS)";
                break;
        }

        return $returnCompanyCampus;
    }

    public static function checkCampusEmail($selected_campus=""){
        $returnCompanyCampus = "";
        switch ($selected_campus) {
            case 'ANG':
                $returnCompanyCampus = "hrmo.angono@urs.edu.ph";
                break;
            case 'ANT':
                $returnCompanyCampus = "hrmo.antipolo@urs.edu.ph";
                break;
            case 'BIN':
                $returnCompanyCampus = "hrmo.binangonan@urs.edu.ph";
                break;
            case 'CAR':
                $returnCompanyCampus = "hrmo.cardona@urs.edu.ph";
                break;
            case 'CNT':
                $returnCompanyCampus = "hrmo.cainta@urs.edu.ph";
                break;
            case 'MOR':
                $returnCompanyCampus = "hrmo.morong@urs.edu.ph";
                break;
            case 'PIL':
                $returnCompanyCampus = "hrmopililla@urs.edu.ph";
                break;
            case 'ROD':
                $returnCompanyCampus = "hrmo.rodriguez@urs.edu.ph";
                break;
            case 'TAY':
                $returnCompanyCampus = "hrmo.taytay@urs.edu.ph";
                break;
            case 'TNY':
                $returnCompanyCampus = "hrmo.tanay@urs.edu.ph";
                break;
            case 'AGO':
                $returnCompanyCampus = "hrmo.angono@urs.edu.ph";
                break;
            default:
            $returnCompanyCampus = "";
                break;
        }

        return $returnCompanyCampus;
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
					$load_arr = $this->employeeScheduleList($employeeid);
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
    
	public function getEmployeeTeachingType($employeeid){
		$q_type = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
		if($q_type->num_rows() > 0) return $q_type->row()->teachingtype;
		else return false;
	}

	function isTeachingRelated($user = ""){
		$query = $this->db->query("SELECT teachingtype, trelated FROM employee WHERE employeeid='$user'");
		return $query->row(0)->teachingtype == 'nonteaching' && $query->row(0)->trelated == '1';
	}

    public function employeeAbsentTardy($employeeid, $sdate, $edate){
        $lateut = $ut = $absent = "0.00";
        $q_att = $this->db->query("SELECT * FROM attendance_confirmed_nt WHERE employeeid = '$employeeid' AND payroll_cutoffstart = '$sdate' AND payroll_cutoffend= '$edate'");
        if($q_att->num_rows() > 0){
            if($q_att->row()->absent) $absent = $q_att->row()->absent;
            if($q_att->row()->lateut) $lateut = $q_att->row()->lateut;
            if($q_att->row()->ut) $ut = $q_att->row()->ut;
        }

        $lateut = ($this->time->exp_time($lateut) + $this->time->exp_time($ut));
        $lateut = $this->time->sec_to_hm($lateut);

        return array($lateut, $absent);
    }

	public function getDTRCutoffConfigPayslip($dfrom, $dto){
		$query_date = $this->db->query("SELECT * FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.`id` = b.`baseid` WHERE startdate = '$dfrom' AND enddate = '$dto' ");
		if($query_date->num_rows() > 0){
			return array($query_date->row()->CutoffFrom, $query_date->row()->CutoffTo);
		}
		else{ 
			return "";
		}
	}

    public static function getBEDDepartments(){
        return array('ELEM','HS','SHS','BED','ACAD');
    }

	public function getEmployeeDeparment($employeeid){
    	$q_dept = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
    	if($q_dept->num_rows() > 0) return $q_dept->row()->deptid;
    	else return false;
    }

    function computeEmployeeAttendanceSummaryTeaching($from_date='',$to_date='',$empid='',$toCheckPrevAtt=false,$isBED=false){
        $not_included_ol = array("ABSENT", "EL", "VL", "SL", "CORRECTION");
        $date_list = array();
        $edata          = 'NEW';
        $deptid = $this->getindividualdept($empid);
        $tdaily_absent = '';
        $tlec = $tlab = $tadmin = $trle = $tabsent = $tabsentperday = $tel = $tvl = $tsl = $tol = $tdlec = $tdlab = $tdadmin = $tdrle = $tOverload = 0; 
        // LOLA add Total time absent late ut pattern
        $timeAbsent = 0;
        $tempabsent = $lateutlec = $lateutlab = $lateutrle = $twork_lec = $twork_lab = $twork_admin = $twork_rle = 0;
        $workhours_arr = array();
        $workhours_perday = array();
        $aimsdept = '';
        $hasLog = $isSuspension = $isCreditedHoliday = false;
        $firstDate = true;
        $last_day = '';
        $absent_day = '';
        $date_list_absent = $tot_sub = 0;
        $tholiday = $tsuspension = 0;
        $qdate = $this->time->displayDateRange($from_date, $to_date);
        $fromtime = $totime = "";
        ///< based from source -> individual attendance_report
        $daily_absent = $daily_present = array();
        $used_time = array();

        $firstDayOfWeek = $this->getFirstDayOfWeek($empid);
        $lastDayOfWeek = $this->getLastDayOfWeek($empid);
        list($ath, $overload_limit) = $this->getEmployeeATH($empid);
        $ath = 60 * $ath;

        $patternAbsent = array();
        $weeklyOverload = $weeklyATH = $weeklyTotalOverload = 0;
        foreach ($qdate as $rdate) {
            if($firstDayOfWeek == date("l",strtotime($rdate->dte))){
                $weeklyOverload = $weeklyATH = $weeklyTotalOverload = 0;
            }
            $is_half_holiday = true;
            $has_after_suspension = false;
            $has_last_log = false;
            // Holiday
            $isSuspension = $hasSched = false;
            $holiday = $this->isHolidayNew($empid,$rdate->dte,$deptid ); 


            $dispLogDate = date("d-M (l)",strtotime($rdate->dte));
            $sched = $this->displaySched($empid,$rdate->dte);
            $countrow = $sched->num_rows();
                
            $isValidSchedule = true;

            if($countrow > 0){
                if($sched->row(0)->starttime == '00:00:00' && $sched->row(0)->endtime == '00:00:00') $isValidSchedule = false;
            }

            if($holiday){
                $holidayInfo = $this->holidayInfo($rdate->dte);
                if($holidayInfo['holiday_type']==5){
                    $isSuspension = true;
                    $tsuspension++;
                }else{
                    $tholiday++;
                }
            }else{
                if($countrow > 0){
                    $is_holiday_halfday = $this->isHolidayNew($empid, $rdate->dte,$deptid, "", "on");
                    list($fromtime, $totime) = $this->getHolidayHalfdayTime($rdate->dte);
                    if($is_holiday_halfday && ($fromtime && $totime) ){
                        $holidayInfo = $this->holidayInfo($rdate->dte);
                        $is_half_holiday = true;
                        if($holidayInfo["holiday_type"] == 5) $tsuspension++;
                        else $tholiday++;
                    }
                }
            }

            if(!$toCheckPrevAtt){
                ///< for validation of absent for 1st day in range. this will check for previous day attendance
                if($firstDate && $holiday){
                    // $hasLog = $this->attendance->checkPreviousSchedAttendanceTeaching($rdate->dte,$empid);
                    $firstDate = false;
                }
            }

            // substitute
            list($substitute["list"][$rdate->dte], $substitute_tot) = $this->substituteTotalHours($rdate->dte, $empid, $holiday, isset($holidayInfo['holiday_type'])? $holidayInfo['holiday_type'] : "");
            $date_list[$rdate->dte]["substitute"] = $substitute_tot;

            $bed_isfirsthalf_absent = $bed_issechalf_absent = $bed_iswholeday_absent = true;
            $bed_setup = $this->getBEDAttendanceSetup();
            $perday_info = array();
            
            if($countrow > 0 && $isValidSchedule){
                $hasSched = true;

                ///< for validation of holiday (will only be credited if not absent during last schedule)
                $hasLogprev = $hasLog;
                $hasLog = false;

                if($hasLogprev || $isSuspension)    $isCreditedHoliday = true;
                else                                $isCreditedHoliday = false;

                $tempsched = "";
                $seq = 0;
                $isFirstSched = true;
                $bed_rowcount_half = 0;
                $tot_absent = 0;
                $schedule_result = $sched->result();
                $between_overload = 0;
                foreach($schedule_result as $rschedkey => $rsched){
                    $overload = 0;
                    $persched_info = array();

                    if($tempsched == $dispLogDate)  $dispLogDate = "";
                    $seq += 1;
                    $stime = $rsched->starttime;
                    $etime = $rsched->endtime; 
                    $type  = $rsched->leclab;
                    $tardy_start = $rsched->tardy_start;
                    $absent_start = $rsched->absent_start;
                    $earlydismissal = $rsched->early_dismissal;
                    $aimsdept = $rsched->aimsdept;

                    // logtime
                    list($login,$logout,$q,$haslog_forremarks,$used_time) = $this->displayLogTime($empid,$rdate->dte,$stime,$etime,$edata,$seq,$absent_start,$earlydismissal,$used_time);
                    if($seq == $countrow){
                        $weeklyOverloadOT = $this->displayLogTimeOutsideOT($empid,$rdate->dte);
                        if($weeklyOverloadOT){
                            $overload += $weeklyOverloadOT;
                            $weeklyOverload += $weeklyOverloadOT;
                        }
                    }
                    if($haslog_forremarks) $hasLog = true;
                    
                    # LOLA - CAL TIME WITH ABSENT WITHIN THE DAY SCHEDS
                    $tot_absent = $this->getEmployeeScheduleComputeLateUndertimeAbsence($empid,$rdate->dte,$rdate->dte,"absent",$type);
                    $patternAbsent[$type][$rdate->dte] = $tot_absent;

                    // Leave
                    list($el,$vl,$sl,$ol,$oltype,$ob)     = $this->displayLeave($empid,$rdate->dte,'',$stime,$etime,$seq);
                    if($ol == "DIRECT"){
                        $is_wfh = $this->isWfhOB($empid,$rdate->dte);
                        if($is_wfh->num_rows() == 1){
                            $ob_id = $is_wfh->row()->aid;
                            $hastime = $this->hasWFHTimeRecord($ob_id,$rdate->dte);
                            if($hastime->num_rows() == 0) $ol = $oltype = $ob = 0;
                        }
                    }

                    // Absent
                    $absent = $this->displayAbsent($stime,$etime,$login,$logout,$empid,$rdate->dte,$earlydismissal, $absent_start);

                    if ($vl >= 1 || $el >= 1 || $sl >= 1 || ($holiday && $isCreditedHoliday))   $absent = "";

                    if ($vl > 0 || $el > 0 || $sl > 0 || ($ol == "DIRECT" && ($login && $logout)) /* || $ob > 0*/){
                        $absent = "";
                    }
                    
                    if($login && $logout && $vl == 0 && $el == 0 && $sl == 0 && (!$holiday && !$isCreditedHoliday)) $daily_present[$rdate->dte] = $rdate->dte;

                    // Late / Undertime
                    // list($lateutlec,$lateutlab,$lateutadmin,$tschedlec,$tschedlab,$tschedadmin,$lateutrle,$tschedrle) = $this->attcompute->displayLateUT($stime,$etime,$tardy_start,$login,$logout,$type,$absent);
                    
                    # LOLA - REVISE LATE / UNDERTIME CAL
                    list($lateutlec,$lateutlab,$lateutadmin,$tschedlec,$tschedlab,$tschedadmin,$lateutrle,$tschedrle) = $this->displayLateUTNew($stime,$etime,$tardy_start,$login,$logout,$type,$absent);

                    if($el || $vl || $sl || ($holiday && $isCreditedHoliday)){
                         $lateutlec = $lateutlab = $lateutadmin = $tschedlec = $tschedlab = $tschedadmin = $lateutrle = $tschedrle = "";
                    }

                    if($isBED){
                        $isAbsent = $this->time->exp_time($absent) > 0 ? 1 : 0;
                        list($rowcount_half,$isfirsthalf_absent,$issechalf_absent,$iswholeday_absent) = 
                        $this->getBEDPerdayAbsent($bed_setup,array('sched_start'=>$stime,'sched_end'=>$etime,'isAbsent'=>$isAbsent));
                        
                        $bed_rowcount_half += $rowcount_half;

                        $bed_isfirsthalf_absent  =  $bed_isfirsthalf_absent ? (!$isfirsthalf_absent ? false : true) : false ;
                        $bed_issechalf_absent    =  $bed_issechalf_absent ? (!$issechalf_absent ? false : true) : false ;
                        $bed_iswholeday_absent    =  $bed_iswholeday_absent ? (!$iswholeday_absent ? false : true) : false ;
                    }

                    if($absent && !$type) $absent = '';


                    if(strtotime($login) > strtotime($rdate->dte." ".$stime)) $start = strtotime($login);
                    else $start = strtotime($rdate->dte." ".$stime);
                    if(strtotime($logout) > strtotime($rdate->dte." ".$etime)) $end = strtotime($rdate->dte." ".$etime);
                    else $end = strtotime($logout);

                    $mins = ($end - $start) / 60;
                    if($rsched->leclab == "LEC" || $rsched->leclab == "LAB"){
                        $weeklyATH += $mins;
                    }

                    $excessTime = 0;
                    if($login && $logout && $stime && $etime && ($rsched->leclab == "LEC" || $rsched->leclab == "LAB")){
                        if(isset($schedule_result[$rschedkey + 1])){
                            if(date('H:i',strtotime($etime)) < date('H:i',strtotime($schedule_result[$rschedkey + 1]->starttime)) && $mins > 0){
                                $schedTime = strtotime($schedule_result[$rschedkey + 1]->starttime);
                                $logTime = strtotime($etime);
                                $excessTime += ($schedTime - $logTime) / 60;
                                $between_overload++;
                            }
                        }else if(isset($schedule_result[$rschedkey - 1])){
                            if(date('H:i',strtotime($stime)) > date('H:i',strtotime($schedule_result[$rschedkey - 1]->endtime)) && $mins > 0){
                                $schedTime = strtotime($stime);
                                $logTime = strtotime(substr($login, 11));
                                $excessTime += ($schedTime - $logTime) / 60;
                            }
                        }else{
                            if($between_overload == 0){
                                if(date('H:i',strtotime($stime)) > date('H:i',strtotime($login)) && $mins > 0){
                                    $schedTime = strtotime($stime);
                                    $logTime = strtotime(substr($login, 11));
                                    $excessTime += ($schedTime - $logTime) / 60;
                                }

                                if(date('H:i',strtotime($logout)) > date('H:i',strtotime($etime)) && $mins > 0){
                                    $schedTime = strtotime($etime);
                                    $logTime = strtotime(substr($logout, 11));
                                    $excessTime += ($logTime - $schedTime) / 60;
                                }
                            }else{
                                if(date('H:i',strtotime($logout)) > date('H:i',strtotime($etime)) && $mins > 0){
                                    $schedTime = strtotime($etime);
                                    $logTime = strtotime(substr($logout, 11));
                                    $excessTime += ($logTime - $schedTime) / 60;
                                }
                            }
                        }

                        if($excessTime != 0){
                            $overload += $excessTime;
                            $weeklyOverload += $excessTime;
                            
                        }
                    }


                    if($lastDayOfWeek == date("l",strtotime($rdate->dte)) && $countrow == $seq){
                        if($weeklyATH >= $ath){
                            if($weeklyOverload > $overload_limit)  $weeklyTotalOverload = $overload_limit;
                            else $weeklyTotalOverload = $weeklyOverload;
                            $weeklyTotalOverload = $weeklyTotalOverload + ($weeklyATH - $ath);
                        }else{
                            if($weeklyOverload > 0 && $weeklyATH > 0){
                                $def_ath = $ath - $weeklyATH;
                                $weeklyOverload = $weeklyOverload - $def_ath;

                                if($weeklyOverload > $overload_limit)  $weeklyTotalOverload = $overload_limit;
                                else $weeklyTotalOverload = $weeklyOverload;
                            }else{
                                $weeklyTotalOverload = 0;
                            }
                        }
                    }
                    if($weeklyTotalOverload > 0){
                        $tOverload += $weeklyTotalOverload;
                    }

                    $tempsched = $dispLogDate;
                    /*
                     * ----------------Total---------------------------------------------
                     */ 

                    // Absent
                    if($absent){
                        if(!$isBED) $tabsent += $this->time->exp_time($absent) > 0 ? 1 : 0;
                        // if($rdate->dte != $absent_day) $tdaily_absent .= substr($rdate->dte, 5)." 1/";
                        // $absent_day = $rdate->dte;

                    }
                    
                    // Leave
                    if($dispLogDate){
                        $tel      += $el;
                        $tvl      += $vl;
                        $tsl      += $sl;
                        $tol      += (!in_array($ol, $not_included_ol) && $ol >= 1) ? (($date_tmp != $rdate->dte) ? 1 : -0.5) : 0;
                    }
                    
                    if(!$isBED){
                        // Late / UT
                        if($tlec){
                            $secs  = strtotime($lateutlec)-strtotime("00:00:00");
                            if($secs>0) $tlec = date("H:i",strtotime($tlec)+$secs);
                        }else
                            $tlec    = $lateutlec;

                        $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutlec) - strtotime("00:00:00")) : $lateutlec;
                            
                        if($tlab){
                            $secs  = strtotime($lateutlab)-strtotime("00:00:00");
                            if($secs>0) $tlab = date("H:i",strtotime($tlab)+$secs);
                        }else
                            $tlab    = $lateutlab;

                        $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutlab) - strtotime("00:00:00")) : $lateutlab;

                        if($tadmin){
                            $secs  = strtotime($lateutadmin)-strtotime("00:00:00");
                            if($secs>0) $tadmin = date("H:i",strtotime($tadmin)+$secs);
                        }else
                            $tadmin    = $lateutadmin;

                        $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutadmin) - strtotime("00:00:00")) : $lateutadmin;

                        if($trle){
                            $secs  = strtotime($lateutrle)-strtotime("00:00:00");
                            if($secs>0) $trle = date("H:i",strtotime($trle)+$secs);
                        }else
                            $trle    = $lateutrle;

                        $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutrle) - strtotime("00:00:00")) : $lateutrle;

                        // Deductions
                        if($tschedlec)      $tdlec += $this->time->exp_time($tschedlec);
                        $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->time->exp_time($tschedlec)) : $this->time->exp_time($tschedlec);

                        if($tschedlab)      $tdlab += $this->time->exp_time($tschedlab);
                        $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->time->exp_time($tschedlab)) : $this->time->exp_time($tschedlab);

                        if($tschedadmin)    $tdadmin += $this->time->exp_time($tschedadmin);
                        $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->time->exp_time($tschedadmin)) : $this->time->exp_time($tschedadmin);

                        if($tschedrle)    $tdrle += $this->time->exp_time($tschedrle);
                        $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->time->exp_time($tschedrle)) : $this->time->exp_time($tschedrle);

                    }else{
                        $persched_info['sched_type'] = $type;
                        $persched_info['lateut_lec'] = $lateutlec;
                        $persched_info['lateut_lab'] = $lateutlab;
                        $persched_info['lateut_admin'] = $lateutadmin;
                        $persched_info['lateut_rle'] = $lateutrle;
                        $persched_info['deduc_lec'] = $tschedlec;
                        $persched_info['deduc_lab'] = $tschedlab;
                        $persched_info['deduc_admin'] = $tschedadmin;
                        $persched_info['deduc_rle'] = $tschedrle;
                        array_push($perday_info, $persched_info);
                    }
                    
                    if(!$tschedadmin && !$absent) $hasLog = true;

                    if($login && $logout && $isFirstSched) $has_last_log = true;
                    $isAffectedAfter = $this->affectedBySuspensionAfter(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));

                    // if(!$holiday && !$isCreditedHoliday){
                        list($work_lec,$work_lab,$work_admin,$workhours_arr,$work_rle) = $this->getWorkhoursPerdeptArr($stime,$etime,$type,$aimsdept,$workhours_arr,$lateutlec,$tschedlec,$lateutlab,$tschedlab,$lateutadmin,$tschedadmin,$empid,$rdate->dte,$deptid,$sl,$vl,$login,$logout, $workhours_perday,$rdate->dte,$lateutrle,$tschedrle,$has_last_log,$has_after_suspension,$isFirstSched, $start, $end, true);
                        // echo "<pre>"; print_r($work_lec); die;
                        $twork_lec += $work_lec;
                        $twork_lab += $work_lab;
                        $twork_admin += $work_admin;
                        $twork_rle += $work_rle;
                    // }
                    $workhours_perday = $this->getWorkhoursPerdayArr($stime,$etime,$type,$aimsdept,$rdate->dte,$workhours_perday,$lateutlec,$tschedlec,$lateutlab,$tschedlab,$lateutadmin,$tschedadmin,$holiday,$empid,$rdate->dte,$deptid,$sl,$vl,$login,$logout,$lateutrle,$tschedrle,$has_last_log,$has_after_suspension, $isFirstSched);
                    
                    if($isAffectedAfter) $has_after_suspension = true;
                    $isFirstSched = false;
                    
                }   // end foreach sched
               
                if($isBED){
                    if($bed_rowcount_half == $countrow) {
                        $bed_issechalf_absent = $bed_iswholeday_absent = false;
                    }elseif($bed_rowcount_half == 0){
                        $bed_isfirsthalf_absent = $bed_iswholeday_absent = false;
                    }

                    if((!$login || !$logout || $login == "0000-00-00 00:00:00" || $logout == "0000-00-00 00:00:00") && ($bed_issechalf_absent || $bed_isfirsthalf_absent)){
                        $bed_issechalf_absent = true;
                    }

                    $bed_absent = 0;
                    if($bed_iswholeday_absent){
                        $bed_absent = 1;
                        $tdadmin += 28800; ///< 8hrs for 1day absent -- BED is fixed to admin TYPE
                        $day_absent = substr($rdate->dte, 5);
                        $tdaily_absent .= $day_absent." 1/";
                        $date_list_absent += 28800;
                    }else{
                        if($bed_issechalf_absent || $bed_isfirsthalf_absent){
                            $bed_absent = 0.5;
                            $tdadmin += 14400; ///< 4hrs for half day absent -- BED is fixed to admin TYPE
                            $day_absent = substr($rdate->dte, 5);
                            $tdaily_absent .= $day_absent." 0.5/";
                            $date_list_absent += 14400;
                        }

                        ///< construct lateut
                        ///< if half/wholeday present , add deduc to late per specific sched

                        $lateut_perday = $this->constructLateUTBedSummary($perday_info,$bed_isfirsthalf_absent,$bed_issechalf_absent,$bed_rowcount_half);
                        $date_list_tlec = ($lateut_perday['tlec']) ? $this->time->sec_to_hm($lateut_perday['tlec']) : 0;
                        $date_list_tlab = ($lateut_perday['tlab']) ? $this->time->sec_to_hm($lateut_perday['tlab']) : 0;
                        $date_list_tadmin = ($lateut_perday['tadmin']) ? $this->time->sec_to_hm($lateut_perday['tadmin']) : 0;
                        $date_list_trle = ($lateut_perday['trle']) ? $this->time->sec_to_hm($lateut_perday['trle']) : 0;

                        if($tlec){
                            if($lateut_perday['tlec']) $tlec = $this->time->sec_to_hm($this->time->exp_time($tlec) + $lateut_perday['tlec']);
                        }else $tlec = $lateut_perday['tlec'] ? $this->time->sec_to_hm($lateut_perday['tlec']) : '';
                        if($date_list_tlec) $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($tlec) - strtotime("00:00:00")) : $date_list_tlec;

                        if($tlab){
                            if($lateut_perday['tlab']) $tlab = $this->time->sec_to_hm($this->time->exp_time($tlab) + $lateut_perday['tlab']);
                        }else $tlab = $lateut_perday['tlab'] ? $this->time->sec_to_hm($lateut_perday['tlab']) : '';
                        if($date_list_tlab) $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($tlab) - strtotime("00:00:00")) : $date_list_tlab;

                        if($tadmin){
                            if($lateut_perday['tadmin']) $tadmin = $this->time->sec_to_hm($this->time->exp_time($tadmin) + $lateut_perday['tadmin']);
                        }else $tadmin = $lateut_perday['tadmin'] ? $this->time->sec_to_hm($lateut_perday['tadmin']) : '';
                        if($date_list_tadmin) $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($tadmin) - strtotime("00:00:00")) : $date_list_tadmin;

                        if($trle){
                            if($lateut_perday['trle']) $trle = $this->time->sec_to_hm($this->time->exp_time($trle) + $lateut_perday['trle']);
                        }else $trle = $lateut_perday['trle'] ? $this->time->sec_to_hm($lateut_perday['trle']) : '';
                        if($date_list_trle) $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($trle) - strtotime("00:00:00")) : $date_list_trle;

                    }

                    $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $date_list_absent) : $date_list_absent;
                    $tabsent     += $bed_absent;
                }
                if($this->hasLogtime($empid, $rdate->dte) == 0) $tdaily_absent .= substr($rdate->dte, 5)." 1/";

            } // end if valid sched
            $tot_sub += $this->time->exp_time($substitute_tot);
            $date_list_absent = $substitute_tot = 0;
            $hasLog = "";
        } // end loop dates

        # GET TOTAL ABSENT TIME
        $timeAbsent = (is_array($patternAbsent) ? $this->totalTimeabsentLecLabAdminRleCompute($patternAbsent) : "");

        $tot_sub = $this->time->sec_to_hm($tot_sub);
        $twork_lec = $twork_lec ? $this->time->sec_to_hm($twork_lec) : "";
        $twork_lab = $twork_lab ? $this->time->sec_to_hm($twork_lab) : "";
        $twork_admin = $twork_admin ? $this->time->sec_to_hm($twork_admin) : "";
        $twork_rle = $twork_rle ? $this->time->sec_to_hm($twork_rle) : "";

        $tdlec = ($tdlec ? $this->time->sec_to_hm($tdlec) : "");
        $tdlab = ($tdlab ? $this->time->sec_to_hm($tdlab) : "");
        $tdadmin = ($tdadmin ? $this->time->sec_to_hm($tdadmin) : "");
        $tdrle = ($tdrle ? $this->time->sec_to_hm($tdrle) : "");
        $date_list["workhours_perday"] = $workhours_perday;
        $substitute["tot_sub"] = $tot_sub;
        $tOverload = ($tOverload ? $this->time->minutesToHours($tOverload) : "");
        return array($tlec,$tlab,$tadmin,$tabsent,$tdaily_absent,$tel,$tvl,$tsl,$tol,$tdlec,$tdlab,$tdadmin,$holiday,$hasSched,$hasLog,$twork_lec,$twork_lab,$twork_admin,$workhours_arr,$date_list,$daily_present,$substitute,$tholiday,$tsuspension,$trle,$tdrle,$twork_admin,$twork_rle,$timeAbsent, $tOverload);
    } 

    public function computeEmployeeAttendanceSummaryNonTeaching($from_date='',$to_date='',$empid='',$toCheckPrevAtt=false){
        $edata = 'NEW';
        $date_list = array();
        $deptid = $this->getindividualdept($empid);
        $not_included_ol = array("ABSENT", "EL", "VL", "SL", "CORRECTION");
        $date_tmp = "";

        $fixedday = $this->isFixedDay($empid);

        $x = $totr = $totrest = $tothol = $tlec = $tutlec = $absent = $tabsent = $tabsentperday = $tel = $tvl = $tsl = $tol = $tdlec = $tdlab = $tholiday = $pending = $tempOverload = $overload = $tOverload = $lastDayOfWeek = $service_credit = $cs_app = $tsc = 0; 

        $tlec = $tlab = $tadmin = $tabsent = $tabsentperday = $tel = $tvl = $tsl = $tol = $tdlec = $tdlab = $tdadmin = $tcto = 0; 
        $tempabsent = $lateutlec= $lateutlab = $twork_lec = $twork_lab = $twork_admin = 0;
        $workhours_arr = array();
        $workhours_perday = array();
        $workhours_perdept = array();
        $cto_id_list = array();

        $workdays = 0;
        $seq_new = 0;
        $tlec = 0 ;
        $tempabsent = "";
        $hasLog = $isSuspension = false;

        $ot_list = array();
        $ot_save_list = array();
        
        $used_time = array();
        $qdate = $this->time->displayDateRange($from_date, $to_date);

        $isCreditedHoliday = false;
        $firstDate = true;

        $day_absent = 0;
        ///< based from source -> individual attendance_report
        foreach ($qdate as $rdate) {
            $has_last_log = true;
            $holiday_type = '';

            // Holiday
            $isSuspension = false;
            $holiday = $this->isHolidayNew($empid,$rdate->dte,$deptid ); 

            $holidayInfo = $this->holidayInfo($rdate->dte);
            if($holiday)
            {
                $holiday_type = $holidayInfo["type"];
                if($holidayInfo["code"]=="SUS") $isSuspension = true;
                //if($holidayInfo["withPay"]=='NO') $holiday = '';
                // if($holidayInfo["holiday_rate"] <= 0) $holiday = ''; 
            }

            $is_holiday_valid = $this->getTotalHoliday($rdate->dte, $rdate->dte, $empid);
            if(!$is_holiday_valid){
                $holidayInfo = array();
                $holiday = "";
            }

            $dispLogDate = date("d-M (l)",strtotime($rdate->dte));
            $sched = $this->displaySched($empid,$rdate->dte);
            $countrow = $sched->num_rows();
                
            $isValidSchedule = true;

            if($countrow > 0){
                if($sched->row(0)->starttime == '00:00:00' && $sched->row(0)->endtime == '00:00:00') $isValidSchedule = false;
            }


            $hasSched = false;

             if(!$toCheckPrevAtt){
                ///< for validation of absent for 1st day in range. this will check for previous day attendance
                if($firstDate && $holiday){
                    // $hasLog = $this->attendance->checkPreviousSchedAttendanceNonTeaching($rdate->dte,$empid);
                    $firstDate = false;
                }
            }

            if($countrow > 0 && $isValidSchedule){
                $hasSched = $firstsched = true;

                ///< for validation of holiday (will only be credited if not absent during last schedule)
                $hasLogprev = $hasLog;
                $hasLog = false;

                
                if($hasLogprev || $isSuspension)    $isCreditedHoliday = true;
                else                                $isCreditedHoliday = false;

                $tempsched = "";
                $seq=0;

                $isFirstSched = true;
                $ot_list = array();
                $q_sched = $sched;
                foreach($sched->result() as $rsched){

                    //NOT FLEXIBLE -----------------------------------------------------------------------------------------------------------------------------------
                    if($rsched->flexible != "YES")
                    {

                        if($tempsched == $dispLogDate)  $dispLogDate = "";
                        $seq += 1;
                        $stime  = $rsched->starttime;
                        $etime  = $rsched->endtime; 
                        $tstart = $rsched->tardy_start; 
                        $absent_start = $rsched->absent_start;
                        $earlyd = $rsched->early_dismissal;
                        $type = $rsched->type;
                        $aimsdept = $rsched->aimsdept;
                        
                        // logtime
                        list($login,$logout,$q)           = $this->displayLogTime($empid,$rdate->dte,$stime,$etime,$edata,$seq,$absent_start,$earlyd,$used_time);
                        
                         // Overtime
                        list($otreg,$otrest,$othol) = $this->displayOt($empid,$rdate->dte,true);

                        if($isFirstSched){
                            $ot_list_tmp = $this->getOvertime($empid,$rdate->dte,true,$holiday_type);
                            $ot_list = $this->constructOTlist($ot_list,$ot_list_tmp);

                            $ot_save_list = $this->insertOTListToArray($ot_save_list, $ot_list);
                        }

                        // Leave
                        list($el,$vl,$sl,$ol,$oltype,$ob)  = $this->displayLeave($empid,$rdate->dte,'',$stime,$etime,$seq);
                        list($cto, $ctohalf, $cto_id) = $this->displayCTOUsageAttendance($empid,$rdate->dte, $stime, $etime);
                        if($ol == "DIRECT"){
                            $is_wfh = $this->isWfhOB($empid,$rdate->dte);
                            if($is_wfh->num_rows() == 1){
                                $ob_id = $is_wfh->row()->aid;
                                $hastime = $this->hasWFHTimeRecord($ob_id,$rdate->dte);
                                if($hastime->num_rows() == 0) $ol = $oltype = $ob = "";
                            }
                        }

                        //Service Credit 
                        $service_credit = $this->displayServiceCredit($empid,$stime,$etime,$rdate->dte);

                        // Change Schedule
                        $cs_app = $this->displayChangeSchedApp($empid,$rdate->dte);
                        
                        
                        // Leave Pending
                        $pending = $this->displayPendingApp($empid,$rdate->dte);

                         // Absent
                        $absent = $this->displayAbsent($stime,$etime,$login,$logout,$empid,$rdate->dte,$earlyd);
                        if($oltype == "ABSENT") $absent = $absent;
                        else if($holiday && $isCreditedHoliday) $absent = "";

                        if ($vl > 0 || $el > 0 || $sl > 0 || ($ol && $ol != "CORRECTION") || $service_credit > 0 || $cto){
                            $absent = "";
                        }
                        
                        // Late / Undertime
                        $lateutlec = $this->displayLateUTNT($stime,$etime,$login,$logout,$absent,'',$tstart);
                        $utlec  = $this->computeUndertimeNT($stime,$etime,$login,$logout,$absent,'',$tstart);
                        if($el || $vl || $sl || $service_credit || ($holiday && $isCreditedHoliday) || $cto) $lateutlec = $utlec = "";
                        
                                            
                        if($isFirstSched){
                            if(!$login || $absent) $login = $this->getLogin($empid, $edata, $rdate->dte);
                            if(!$logout || $absent) $logout = $this->getLogout($empid, $edata, $rdate->dte);

                            if($login && $logout){
                                $to_time = strtotime($login);
                                $from_time = strtotime($logout);
                                $tot_min = round(abs($to_time - $from_time) / 60,2);
                                if($tot_min > 5){
                                    $lateutlec = $this->displayLateUTNT($stime, $etime, $login, $logout, "", "", $tstart);
                                    $utlec = $this->computeUndertimeNT($stime,$etime,$login,$logout,"","","");
                                }else{
                                    $absent = "4:00";
                                    $lateutlec = $utlec = "";
                                }

                                // if($absent) $lateutlec = $absent;
                                if($utlec || $lateutlec) $log_remarks = $absent = "";
                                $hasLog = TRUE;
                            }else{
                                 foreach($sched->result() as $rsched){
                                    if(isset($sched_new[1]->starttime)) $stime  = $rsched->starttime;
                                    if(isset($sched_new[1]->endtime)) $etime  = $rsched->endtime; 
                                    if(isset($sched_new[1]->tardy_start)) $tstart = $rsched->tardy_start; 
                                    if(isset($sched_new[1]->absent_start)) $absent_start = $rsched->absent_start;
                                    if(isset($sched_new[1]->early_dismissal)) $earlyd = $rsched->early_dismissal;
                                    $seq_new += 1;
                                    list($login_new,$logout_new,$q_new,$haslog_forremarks_new)           = $this->displayLogTime($empid,$rdate->dte,$stime,$etime,$edata,$seq_new,$absent_start,$earlyd);
                                    if($login_new || $logout_new){
                                        // $lateutlec = $absent;
                                        // $lateutlab = $absent;
                                    }
                                 }
                                 // $absent = "";
                            }
                        }else{
                            
                            if(!$login || $absent) $login = $this->getLogin($empid, $edata, $rdate->dte);
                            if(!$logout || $absent) $logout = $this->getLogout($empid, $edata, $rdate->dte);

                            if($el == FALSE && $vl == FALSE && $sl == FALSE  && $service_credit == FALSE && !$cto){
                                if($login){
                                    // $utlec = $absent;
                                    // $utlab = $absent;
                                    // $absent = "";
                                }
                                if($login && $logout){
                                    $to_time = strtotime($login);
                                    $from_time = strtotime($logout);
                                    $tot_min = round(abs($to_time - $from_time) / 60,2);
                                    if($tot_min > 5){
                                        $lateutlec = $this->displayLateUTNT($stime, $etime, $login, $logout, "", "", $tstart);
                                        $utlec = $this->computeUndertimeNT($stime,$etime,$login,$logout,"","","");
                                    }else{
                                        $absent = "4:00";
                                        $lateutlec = $utlec = "";
                                    }

                                    // if($absent) $utlec = $absent;
                                    if($utlec || $lateutlec) $log_remarks = $absent = "";
                                }
                            }
                        }

                        if($isFirstSched){
                            if($lateutlec){
                                $is_holiday_halfday = $this->isHolidayNew($empid, $rdate->dte,$deptid, "on");
                                if($is_holiday_halfday){
                                    list($fromtime, $totime) = $this->getHolidayHalfdayTime($rdate->dte, "first");
                                } 
                                if($is_holiday_halfday && ($fromtime && $totime) ){
                                    $is_half_holiday = true;
                                    $half_holiday = $this->holidayHalfdayComputation(date("H:i", strtotime($login)), date("H:i", strtotime($logout)), date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), $isFirstSched);
                                    if($half_holiday > 0){
                                        $lateutlec = $this->time->sec_to_hm(abs($half_holiday));
                                    }else{
                                        $lateutlec = "";
                                    }
                                }
                            }
                        }else{
                            $is_holiday_halfday = $this->isHolidayNew($empid, $rdate->dte,$deptid, "on");
                            if($is_holiday_halfday){
                                list($fromtime, $totime) = $this->getHolidayHalfdayTime($rdate->dte, "second");
                            } 
                            if($is_holiday_halfday && ($fromtime && $totime) ){
                                $is_half_holiday = true;
                                if($utlec){
                                    $half_holiday = $this->holidayHalfdayComputation($login, $logout, date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), $isFirstSched);
                                    if($half_holiday > 0){
                                        $utlec = $this->time->sec_to_hm(abs($half_holiday)); 
                                    }else{
                                        $utlec = "";
                                    }
                                    
                                }
                            }
                        }
                        if($el || $vl || $sl  || $service_credit || ($holiday && $isCreditedHoliday) || $cto) $lateutlec = $utlec = "";

                        $is_trelated = $this->isTeachingRelated($empid);
                        // Late / UT
                        if($is_trelated){
                            list($lateutlec,$lateutlab,$lateutadmin,$tschedlec,$tschedlab,$tschedadmin) = $this->displayLateUT($stime,$etime,$tstart,$login,$logout,$type,$absent);
                            if($el || $vl || $sl  || ($holiday && $isCreditedHoliday) || $cto){
                                 $lateutlec = $lateutlab = $lateutadmin = $tschedlec = $tschedlab = $tschedadmin = "";
                            }
                            if($tlec){
                                $secs  = strtotime($lateutlec)-strtotime("00:00:00");
                                if($secs>0) $tlec = date("H:i",strtotime($tlec)+$secs);
                            }else
                                $tlec    = $lateutlec;

                            $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutlec) - strtotime("00:00:00")) : $lateutlec;
                                
                            if($tlab){
                                $secs  = strtotime($lateutlab)-strtotime("00:00:00");
                                if($secs>0) $tlab = date("H:i",strtotime($tlab)+$secs);
                            }else
                                $tlab    = $lateutlab;

                            $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutlab) - strtotime("00:00:00")) : $lateutlab;

                            if($tadmin){
                                $secs  = strtotime($lateutadmin)-strtotime("00:00:00");
                                if($secs>0) $tadmin = date("H:i",strtotime($tadmin)+$secs);
                            }else
                                $tadmin    = $lateutadmin;

                            $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutadmin) - strtotime("00:00:00")) : $lateutadmin;

                            // Deductions
                            if($tschedlec)      $tdlec += $this->time->exp_time($tschedlec);
                            $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->time->exp_time($tschedlec)) : $this->time->exp_time($tschedlec);

                            if($tschedlab)      $tdlab += $this->time->exp_time($tschedlab);
                            $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->time->exp_time($tschedlab)) : $this->time->exp_time($tschedlab);

                            if($tschedadmin)    $tdadmin += $this->time->exp_time($tschedadmin);
                            $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->time->exp_time($tschedadmin)) : $this->time->exp_time($tschedadmin);

                            if(!$holiday && !$isCreditedHoliday){
                            list($work_lec,$work_lab,$work_admin,$workhours_arr) = $this->getWorkhoursPerdeptArr($stime,$etime,$type,$aimsdept,$workhours_arr,$lateutlec,$tschedlec,$lateutlab,$tschedlab,$lateutadmin,$tschedadmin,$empid,$rdate->dte,$deptid,$sl,$login,$logout,$workhours_perday,$rdate->dte,"","",$has_last_log, false, $isFirstSched, '','',false);
                                $twork_lec += $work_lec;
                                $twork_lab += $work_lab;
                                $twork_admin += $work_admin;
                            }
                            $workhours_perday = $this->getWorkhoursPerdayArr($stime,$etime,$type,$aimsdept,$rdate->dte,$workhours_perday,$lateutlec,$tschedlec,$lateutlab,$tschedlab,$lateutadmin,$tschedadmin,$holiday,$empid,$rdate->dte,$deptid,$sl,$vl,$login,$logout,"","",$has_last_log, false, $isFirstSched);
                        }
                        $absent = $this->time->exp_time($absent);
                        if($absent >= 14400 && $countrow==2) $absent = 14400;
                        elseif($absent >= 14400 && $countrow==1) $absent = 28800;
                        $absent   = ($absent ? $this->time->sec_to_hm($absent) : "");
                    }else{ ///< FLEXIBLE ---------------------------------------------------------------------------------------------------------------------------------
                        $stime  = $rsched->starttime;
                        $etime  = $rsched->endtime; 
                        $type  = $rsched->leclab;
                        $tstart = $rsched->tardy_start; 
                        $earlyd = $rsched->early_dismissal;
                        
                        // logtime
                        $log = $this->displayLogTimeFlexi($empid,$rdate->dte,$edata);

                        // Overtime
                        list($otreg, $otrest, $othol) = $this->displayOt($empid,$rdate->dte,true);



                        if($isFirstSched){
                            $ot_list_tmp = $this->getOvertime($empid,$rdate->dte,true,$holiday_type);
                            $ot_list = $this->constructOTlist($ot_list,$ot_list_tmp);
                            $ot_save_list = $this->insertOTListToArray($ot_save_list, $ot_list);
                        }

                        // Leave
                        list($el,$vl,$sl,$ol,$oltype,$ob)             = $this->displayLeave($empid,$rdate->dte,$seq);
                        list($cto, $ctohalf, $cto_id) = $this->displayCTOUsageAttendance($empid,$rdate->dte, $stime, $etime);
                        if($ol == "DIRECT"){
                            $is_wfh = $this->isWfhOB($empid,$rdate->dte);
                            if($is_wfh->num_rows() == 1){
                                $ob_id = $is_wfh->row()->aid;
                                $hastime = $this->hasWFHTimeRecord($ob_id,$rdate->dte);
                                if($hastime->num_rows() == 0) $ol = $oltype = $ob = "";
                            }
                        }

                        //Service Credit 
                        $service_credit = $this->displayServiceCredit($empid,$stime,$etime,$rdate->dte);

                        $count_leave = $vl > 0 ? $vl : ( $el > 0 ? $el : ( $sl > 0 ? $sl : ( $ob > 0 ? $ob : ( $service_credit > 0 ? $service_credit : ( $cto > 0 ? $cto : 0 ) ) ) ) ) ;
                        // Absent
                        $absent = $this->displayAbsentFlexi($log,$rsched->hours,$rsched->mode,$empid,$rdate->dte,'',$rsched->breaktime, $count_leave);

                        if($oltype == "ABSENT") $absent = $absent;
                        else if($holiday && $isCreditedHoliday) $absent = "";

                        if ($vl > 0 || $el > 0 || $sl > 0 || $ob > 0 || $ol || $service_credit > 0 || $cto > 0){
                            $absent = "";
                        }


                        // Late / Undertime
                        $lateutlec = '';
                        $utlec = $this->displayLateUTNTFlexi($log,$rsched->hours,$rsched->mode,$absent,$rsched->breaktime, $count_leave);

                        if($el >= 1 || $vl >= 1 || $sl >= 1 || $ob >= 1 || $service_credit >= 1 || ($holiday && $isCreditedHoliday) || $cto) $utlec = "";
                        if(date("Y-m-d",strtotime($utlec)) < $rdate->dte)
                        {
                            $utlec = $lateutlab = "";
                        }
                        
                        $login = $logout = $q = "";

                    }///< end if FLEXIBLE/NOT


                    $tempsched = $dispLogDate;
                    
                    /*
                     * ----------------Total---------------------------------------------
                     */ 
                    $hasOL = $ol ? ($ol != 'CORRECTION' ? true : false) : false; 
                    $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"])) ? $date_list[$rdate->dte]["absent"] : "";
                    // Absent
                    if($absent){
                        // $tabsentperday += $this->attcompute->exp_time($absent);
                        if(!$fixedday && $hasOL)   {}
                        else{
                            $tabsent += $this->time->exp_time($absent);
                            $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? $date_list[$rdate->dte]["absent"] + $this->time->exp_time($absent) : $this->time->exp_time($absent);
                        }
                    }else{
                        $hasLog = true;
                    }

                    $hasLog = $hasLog ? $hasLog : ($hasOL ? true : false); 
                    
                    // Late / UT
                    $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"])) ? $date_list[$rdate->dte]["late"] : "";
                    if($lateutlec){
                        $tlec += $this->time->exp_time($lateutlec);
                        $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? $date_list[$rdate->dte]["late"] + $this->time->exp_time($lateutlec) : $this->time->exp_time($lateutlec);
                    }

                    $date_list[$rdate->dte]["undertime"] = (isset($date_list[$rdate->dte]["undertime"])) ? $date_list[$rdate->dte]["undertime"] : "";
                    if($utlec){
                        $tutlec += $this->time->exp_time($utlec);
                        $date_list[$rdate->dte]["undertime"] = (isset($date_list[$rdate->dte]["undertime"]) && $date_list[$rdate->dte]["undertime"]) ? $date_list[$rdate->dte]["undertime"] + $this->time->exp_time($utlec) : $this->time->exp_time($utlec);
                    }
                    
                    // Leave
                    if($dispLogDate)
                    {
                        $tel      += $el;
                        $tvl      += ($vl + $el);
                        $tsl      += $sl;
                        $tol      += (!in_array($ol, $not_included_ol) && $ol >= 1) ? (($date_tmp != $rdate->dte) ? 1 : -0.5) : 0;
                        $date_tmp  = $rdate->dte;
                        //$tol      += ($ol ? 1 : "") + ($q ? ($q == 1 ? "" : 1) : "") ;
                        // $tsc      += $service_credit;
                        #echo "<pre>". $rdate->dte ." - ". $ol . " - ". $q;
                    }

                    if($fixedday){
                        if($hasSched) $workdays+=0.5;
                    }else{
                        if($hasSched && ($absent=='' || $hasOL || $holiday)) $workdays+=0.5;
                    }
                    
                    $firstsched = false;
                    $isFirstSched = false;
                    if($absent) $day_absent += 0.5;
                    $has_last_log = (!$login && !$logout) ? false : true;
                }   // end foreach
                
                /* Overtime */
                $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"])) ? $date_list[$rdate->dte]["overtime"] : "";

                if($cto){
                    if(!in_array($cto_id, $cto_id_list)){
                        $tcto += $this->time->exp_time($cto);
                        $cto_id_list[] = $cto_id;
                    }
                    
                }


                if($otreg){

                    $totr += $this->time->exp_time($otreg);
                    $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"]) && $date_list[$rdate->dte]["overtime"]) ? $date_list[$rdate->dte]["overtime"] + $this->time->exp_time($otreg) : $this->time->exp_time($otreg);
                    // $ot_save_list[count($ot_save_list) - 1]["ot_hours"] = $this->attcompute->sec_to_hm($date_list[$rdate->dte]["overtime"]);
/*
                    $ot_list_tmp = $this->attcompute->getOvertime($empid,$rdate->dte,false,$holiday_type);
                    $ot_list = $this->attcompute->constructOTlist($ot_list,$ot_list_tmp);*/

                    list($ot_amount, $ot_mode) = $this->getOvertimeAmountDetailed($empid, $ot_list, $date_list[$rdate->dte]["overtime"]);
                    $date_list[$rdate->dte]["ot_type"] = $ot_mode;
                    $date_list[$rdate->dte]["ot_amount"] = $ot_amount;
                }

                if($otrest){
                    $totrest += $this->time->exp_time($otrest);
                    $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"]) && $date_list[$rdate->dte]["overtime"]) ? $date_list[$rdate->dte]["overtime"] + $this->time->exp_time($otrest) : $this->time->exp_time($otrest);
                    // $ot_save_list[count($ot_save_list) - 1]["ot_hours"] = $this->attcompute->sec_to_hm($date_list[$rdate->dte]["overtime"]);

/*                    $ot_list_tmp = $this->attcompute->getOvertime($empid,$rdate->dte,false,$holiday_type);
                    $ot_list = $this->attcompute->constructOTlist($ot_list,$ot_list_tmp);*/

                    list($ot_amount, $ot_mode) = $this->getOvertimeAmountDetailed($empid, $ot_list, $date_list[$rdate->dte]["overtime"]);
                    $date_list[$rdate->dte]["ot_type"] = $ot_mode;
                    $date_list[$rdate->dte]["ot_amount"] = $ot_amount;
                }

                if($othol){
                    $tothol += $this->time->exp_time($othol);
                    $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"]) && $date_list[$rdate->dte]["overtime"]) ? $date_list[$rdate->dte]["overtime"] + $this->time->exp_time($othol) : $this->time->exp_time($othol);
                    // $ot_save_list[count($ot_save_list) - 1]["ot_hours"] = $this->attcompute->sec_to_hm($date_list[$rdate->dte]["overtime"]);

/*                    $ot_list_tmp = $this->attcompute->getOvertime($empid,$rdate->dte,false,$holiday_type);
                    $ot_list = $this->attcompute->constructOTlist($ot_list,$ot_list_tmp);*/

                    list($ot_amount, $ot_mode) = $this->getOvertimeAmountDetailed($empid, $ot_list, $date_list[$rdate->dte]["overtime"]);
                    $date_list[$rdate->dte]["ot_type"] = $ot_mode;
                    $date_list[$rdate->dte]["ot_amount"] = $ot_amount;
                }

               
            }else{  ////< to compute for overtime if employee have no schedule for this day ----------------------------------------------------------------------
                $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"])) ? $date_list[$rdate->dte]["absent"] : "";
                $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"])) ? $date_list[$rdate->dte]["late"] : "";
                $date_list[$rdate->dte]["undertime"] = (isset($date_list[$rdate->dte]["undertime"])) ? $date_list[$rdate->dte]["undertime"] : "";
                $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"])) ? $date_list[$rdate->dte]["overtime"] : "";
                list($otreg,$otrest,$othol) = $this->displayOt($empid,$rdate->dte,false);
                /* Overtime */
                // total regular
                if($otreg){
                    $totr += $this->time->exp_time($otreg);
                    $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"]) && $date_list[$rdate->dte]["overtime"]) ? $date_list[$rdate->dte]["overtime"] + $this->time->exp_time($otreg) : $this->time->exp_time($otreg);
                    $ot_save_list[count($ot_save_list) - 1]["ot_hours"] = $this->time->sec_to_hm($date_list[$rdate->dte]["overtime"]);

/*                    $ot_list_tmp = $this->attcompute->getOvertime($empid,$rdate->dte,false,$holiday_type);
                    $ot_list = $this->attcompute->constructOTlist($ot_list,$ot_list_tmp);*/

                    list($ot_amount, $ot_mode) = $this->getOvertimeAmountDetailed($empid, $ot_list, $date_list[$rdate->dte]["overtime"]);
                    $date_list[$rdate->dte]["ot_type"] = $ot_mode;
                    $date_list[$rdate->dte]["ot_amount"] = $ot_amount;
                }
                // total saturday
                if($otrest){
                    $totrest += $this->time->exp_time($otrest);
                    $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"]) && $date_list[$rdate->dte]["overtime"]) ? $date_list[$rdate->dte]["overtime"] + $this->time->exp_time($otrest) : $this->time->exp_time($otrest);
                    // $ot_save_list[count($ot_save_list) - 1]["ot_hours"] = $this->attcompute->sec_to_hm($date_list[$rdate->dte]["overtime"]);

                    $ot_list_tmp = $this->getOvertime($empid,$rdate->dte,false,$holiday_type);
                    $ot_list = $this->constructOTlist($ot_list,$ot_list_tmp);
                    $ot_save_list = $this->insertOTListToArray($ot_save_list, $ot_list);

                    list($ot_amount, $ot_mode) = $this->getOvertimeAmountDetailed($empid, $ot_list, $date_list[$rdate->dte]["overtime"]);
                    $date_list[$rdate->dte]["ot_type"] = $ot_mode;
                    $date_list[$rdate->dte]["ot_amount"] = $ot_amount;
                }

                // total holiday
                if($othol){
                    $tothol += $this->time->exp_time($othol);
                    $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"]) && $date_list[$rdate->dte]["overtime"]) ? $date_list[$rdate->dte]["overtime"] + $this->time->exp_time($othol) : $this->time->exp_time($othol);
                    // $ot_save_list[count($ot_save_list) - 1]["ot_hours"] = $this->attcompute->sec_to_hm($date_list[$rdate->dte]["overtime"]);

                    $ot_list_tmp = $this->getOvertime($empid,$rdate->dte,false,$holiday_type);
                    $ot_list = $this->constructOTlist($ot_list,$ot_list_tmp);
                    $ot_save_list = $this->insertOTListToArray($ot_save_list, $ot_list);

                    list($ot_amount, $ot_mode) = $this->getOvertimeAmountDetailed($empid, $ot_list, $date_list[$rdate->dte]["overtime"]);
                    $date_list[$rdate->dte]["ot_type"] = $ot_mode;
                    $date_list[$rdate->dte]["ot_amount"] = $ot_amount;
                }
                
                $ot_list_tmp = $this->getOvertime($empid,$rdate->dte,false,$holiday_type);
                $ot_list = $this->constructOTlist($ot_list,$ot_list_tmp);

            } // end if  
            if($holiday && $isCreditedHoliday) $tholiday++;

            $firstDate = true;
            $ot_list = array();
        }

        $tabsent = ($tabsent ? $this->time->sec_to_hm($tabsent) : "");

        $tlec   = ($tlec ? $this->time->sec_to_hm($tlec) : "");       
        $tutlec   = ($tutlec ? $this->time->sec_to_hm($tutlec) : "");       
        $totr   = ($totr ? $this->time->sec_to_hm($totr) : "");
        $totrest = ($totrest ? $this->time->sec_to_hm($totrest) : ""); 
        $tothol = ($tothol ? $this->time->sec_to_hm($tothol) : "");
         $tcto = ($tcto ? $this->time->sec_to_hm($tcto) : "");
        // $tOverload = ($tOverload ? $this->attcompute->sec_to_hm($tOverload) : "");
        $date_list["workhours_perday"] = $workhours_perday;
        return array($tabsent,$tlec,$tlab,$tadmin,$tdlec,$tdlab,$tdadmin,$tutlec,$totr,$totrest,$tothol,$tel,$tvl,$tsl,$tsl,$tol,$tholiday,$holiday,$hasSched,$hasLog,$workdays, $ot_save_list, $date_list, $tsc, $workhours_arr,$twork_lec,$twork_lab,$twork_admin, $day_absent, $tcto); ///< $hasSched is applicable only for checking of attendance good for 1 day

    }

    function getOvertimeAmountDetailed($empid, $ot_details, $emp_ot=''){
        $ot_amount = 0;
        $ot_type = "";

        $rate_per_hour = $this->getEmployeeSalaryRate1($empid, "daily") / 8;
        $rate_per_minute = $rate_per_hour / 60;
        $employeement_status = $this->getEmploymentStatus($empid);
        $setup = $this->getOvertimeSetup($employeement_status);

        $percent = 100;
        foreach ($ot_details as $ot_type => $holiday_type_list) {
            foreach ($holiday_type_list as $holiday_type => $ot_info) {
                $ot_min = ($emp_ot) ? $emp_ot : $ot_info[0];
                $ot_min = $this->time->sec_to_hm($ot_min);
                $ot_min = $this->time->hoursToMinutes($ot_min);
                $sel_setup = (isset($ot_info[1])) ? 1 : 0;

                if(isset($setup[$employeement_status][$ot_type][$holiday_type][$sel_setup])) $percent = $setup[$employeement_status][$ot_type][$holiday_type][$sel_setup];
                $percent = $percent / 100;
                
                $minutely = $rate_per_minute * $percent;
                $ot_amount = $minutely * $ot_min;

                switch ($ot_type) {
                    case 'WITH_SCHED': case 'WITH_SCHED_WEEKEND':
                        $ot_type = "Regular Day";
                        break;
                    
                    case 'NO_SCHED':
                        $ot_type = "Rest Day";
                        break;
                }
            }
        }

        return array($ot_amount, $ot_type);
    }

    function displayLateUTNTFlexi($log="",$hours="",$mode="",$absent="",$breaktime=0,$count_leave=0){
        $lec = $lab = $tschedlec = $tschedlab = "";
        $lateut = "";
        $time = sprintf('%02d:%02d', (int) $hours, fmod($hours, 1) * 60);
        $h = date("H:i:s",strtotime($time));
        $hSTR  = $this->time->exp_time($h);
        $breaktime = $breaktime * 60 * 60;

        if($mode == "day")
        {
            if(count($log) > 0 && !$absent)
            {
                $login = $logout = $totalHour= 0;

                if($count_leave == 0.50){
                    $totalHour = ($hSTR-$breaktime)/2;
                }
                
                for($i = 0;$i < count($log);$i++)
                {
                    if(isset($log[$i][0]) && isset($log[$i][1])){
                        if($log[$i][0] != '0000-00-00 00:00:00' && $log[$i][1] != '0000-00-00 00:00:00' && $log[$i][0] != '' && $log[$i][1] != ''){
                            if($log[$i][0]) $login = $this->time->exp_time(date("H:i:s",strtotime($log[$i][0])));
                            if($log[$i][1]) $logout = $this->time->exp_time(date("H:i:s",strtotime($log[$i][1])));
                        }
                    }

                    $totalHour += $logout - $login;
                }
                
                $diff = $hSTR - $totalHour;
                
                // $lateut = date('H:i', $diff);
                if($diff > 0){
                    $lateut = $this->time->sec_to_hm($diff);
                }
                

            }elseif(count($log) == 0 && !$absent){
                $totalHour = 0;
                if($count_leave == 0.50){
                    $totalHour = (($hSTR-$breaktime)/2);
                }elseif($count_leave >= 1){
                    $totalHour = $hSTR;
                }

                $diff = $hSTR - $totalHour;     
    
                if($diff >  (($hSTR-$breaktime)/2) && $diff <= ((($hSTR-$breaktime)/2)+$breaktime)){
                    $diff = ($hSTR-$breaktime)/2;
                }elseif($diff > ((($hSTR-$breaktime)/2)+$breaktime) || $totalHour > ((($hSTR-$breaktime)/2)+$breaktime) ){
                    $diff = $diff - $breaktime;
                }

                if( $diff > 0 ){
                    $lateut = $this->time->sec_to_hm($diff);
                }
            }
        }
        if($lateut == "00:00") $lateut = "";
        return $lateut;
    }

    function displayAbsentFlexi($log="",$hours="",$mode="",$empid="",$dset="",$type='',$breaktime=0,$count_leave=0){
        $absent = "";
        $time = sprintf('%02d:%02d', (int) $hours, fmod($hours, 1) * 60);
        $h = date("H:i",strtotime($time));

        $hSTR = $this->time->exp_time($time);
        $breaktime = $breaktime * 60 * 60;

        if($mode == "day")
        {
            $totalHour= 0;

            if($count_leave == 0.50){
                $totalHour = ($hSTR-$breaktime)/2;
            }else{
                $totalHour = ($hSTR-$breaktime);
            }
            
            if(count($log) <= 0) $absent = $totalHour;
            else{

                if(isset($log[0][0])){
                    if($log[0][0] == null || $log[0][0] == '0000-00-00 00:00:00') $absent = $totalHour;
                }
                if(isset($log[0][1])){
                    if($log[0][1] == null || $log[0][1] == '0000-00-00 00:00:00') $absent = $totalHour;
                }

            }

            if( $absent > 0 ){
                $absent = $this->time->sec_to_hm($absent);
            }
        
            if($empid){
                $query = $this->db->query("SELECT * FROM attendance_absent_checker WHERE employeeid='$empid' AND scheddate = '$dset'");
                if($query->num_rows() > 0)  $absent = $h;
            }

        }
        return $absent;
    }

    function displayLogTimeFlexi($eid="",$date="",$tbl=""){
        $return = array();
        if($tbl == "NEW")   $tbl = "timesheet";
        else                $tbl = "timesheet_bak";
        // $query = $this->db->query("SELECT timein,timeout,otype FROM $tbl WHERE userid='$eid' AND DATE(timein)='$date' ORDER BY timein ASC");
        $query = $this->db->query("SELECT MIN(timein) AS timein,MAX(timeout) AS timeout,otype FROM $tbl WHERE userid='$eid' AND DATE(timein)='$date' ORDER BY timein ASC");

        if($query->num_rows() > 0){
            foreach($query->result() as $row)
            {
                $timein = $row->timein;
                $timeout = $row->timeout;
                if($timein!=null || $timeout!=null){
                    if($timein=='0000-00-00 00:00:00') $timein = "";
                    if($timeout=='0000-00-00 00:00:00') $timeout = "";
                    array_push($return,array($timein,$timeout,$row->otype));
                }
            }
        }else{
            $query = $this->db->query("SELECT logtime FROM timesheet_trail WHERE userid='$eid' AND DATE(logtime)='$date' AND log_type = 'IN' ORDER BY logtime DESC");
            if($query->num_rows() > 0){
                foreach($query->result() as $row)
                {
                    $logtime = $row->logtime;
                    if($logtime=='0000-00-00 00:00:00') $logtime = "";
                    array_push($return,array($logtime,"",""));
                }
            }   
            
        }
        
        return $return;
    }

    function displayLateUT($stime="",$etime="",$tardy_start='',$login="",$logout="",$type="",$absent=""){
        if(!$tardy_start) $tardy_start = $stime;
        $lec = $lab = $tschedlec = $tschedlab = $admin = $tschedadmin = $rle = $tschedrle = 0;
        $schedstart   = strtotime($stime);
        $schedend   = strtotime($etime);
        $schedtardy   = strtotime($tardy_start);
        
        if($login && $logout && !$absent){
            if($login)  $login = date("H:i:s",strtotime($login));
            if($logout) $logout = date("H:i:s",strtotime($logout));
            
            // Late
            $logtime    = strtotime($login);
            $logouttime    = strtotime($logout);
            
            $late = '';
            if($logtime >= $schedtardy) $late        = round(($logtime - $schedstart) / 60,2);

            if($late > 0){
                if($type == 'LEC')       $lec =  $late;
                elseif($type == 'LAB')   $lab = $late;
                elseif($type == 'RLE')   $rle = $late;
                else                     $admin = $late;
            }
            
            // Undertime
            $ut=0;
            if($logouttime < $schedend) $ut = round(($schedend - $logouttime) / 60,2);
            if($ut > 0){
                if($type == 'LEC')       $lec +=  $ut;
                elseif($type == 'LAB')   $lab += $ut;
                elseif($type == 'RLE') $rle += $ut;
                else                    $admin += $ut;
            }
            
        }

        if($type == 'LEC' && $lec)       $lec =  date('H:i', mktime(0,$lec));
        elseif($type == 'LAB' && $lab)   $lab =  date('H:i', mktime(0,$lab));
        elseif($type == 'RLE' && $rle)                   $rle =  date('H:i', mktime(0,$rle));
        else $admin =  date('H:i', mktime(0,$admin));
        
        if($absent){
            // total sched
            $tsched   = round(abs($schedstart - $schedend) / 60,2);
            $tsched   = date('H:i', mktime(0,$tsched));
            if($type == 'LEC')       $tschedlec =  $tsched;
            elseif($type == 'LAB')   $tschedlab = $tsched;
            elseif($type == 'RLE')           $tschedrle = $tsched;
            else                     $tschedadmin = $tsched;
        }
         
        return array($lec,$lab,$admin,$tschedlec,$tschedlab,$tschedadmin,$rle,$tschedrle);
    }

    function holidayHalfdayComputation($login, $logout, $fromtime, $totime , $firstsched=""){
        /*if(!$firstsched){*/
            if(($this->time->exp_time($fromtime) <= $this->time->exp_time($logout) ) || ($this->time->exp_time($logout) <= $this->time->exp_time($totime)) ){
                if($logout) return $this->time->exp_time($fromtime) - $this->time->exp_time(date("H:i", strtotime($logout)));
                else return false;
            }
        /*}else{
            if(($this->exp_time($fromtime) <= $this->exp_time($login) ) || ($this->exp_time($login) <= $this->exp_time($totime)) ){
                if($login) return $this->exp_time(date("H:i", strtotime($login))) - $this->exp_time($totime);
                else return false;
            }
        }*/
    }

    function getLogout($empid, $edata, $date){
        $logout = "";

        $tbl = "timesheet_bak";
        if($edata == "NEW") $tbl = "timesheet";

        $q_findLogTime = $this->db->query("SELECT * FROM $tbl WHERE userid='$empid' AND (DATE_FORMAT(timein, '%Y-%m-%d') BETWEEN '$date' AND '$date' OR DATE_FORMAT(timeout, '%Y-%m-%d') BETWEEN '$date' AND '$date') ORDER BY timein DESC;")->result();

        foreach ($q_findLogTime as $res) $logout = $res->timeout; 

        return $logout;
    }

    function getLogin($empid, $edata, $date){
        $login = "";

        $tbl = "timesheet_bak";
        if($edata == "NEW") $tbl = "timesheet";

        $q_findLogTime = $this->db->query("SELECT * FROM $tbl WHERE userid='$empid' AND (DATE_FORMAT(timein, '%Y-%m-%d') BETWEEN '$date' AND '$date' OR DATE_FORMAT(timeout, '%Y-%m-%d') BETWEEN '$date' AND '$date') ORDER BY timein DESC;")->result();

        foreach ($q_findLogTime as $res){
            if($res->timein != "0000-00-00 00:00:00" && $res->timein) $login = $res->timein;
        } 

        if(!$login){
            $q_findLogTime = $this->db->query("SELECT * FROM timesheet_trail WHERE userid='$empid' AND DATE_FORMAT(logtime, '%Y-%m-%d') = '$date' AND log_type='IN' ORDER BY logtime DESC;")->result();
            foreach ($q_findLogTime as $res){
                if($res->logtime != "0000-00-00 00:00:00" && $res->logtime) $login = $res->logtime;
            }
        }

        return $login;
    }

    function computeUndertimeNT($stime="",$etime="",$login="",$logout="",$absent="",$ttype="",$tardy=""){
        $lateut = "";
        if($login && $logout && !$absent){
            // if($login < $stime) $login = $stime;
            
            if($login)  $login = date("H:i",strtotime($login));
            if($logout) $logout = date("H:i",strtotime($logout));
            
            // Undertime
            $schedend    = strtotime($etime);
            $logtime     = strtotime($logout);
            $ut          = round(abs($logtime - $schedend) / 60,2);
            if(abs($logout) > 0){
                if( $logout < $etime )   $lateut = date('H:i', mktime(0,$ut));
            }
        }
        if($lateut == "00:00") $lateut = "";
        return $lateut;
    }

    function displayLateUTNT($stime="",$etime="",$login="",$logout="",$absent="",$ttype="",$tardy=""){
        $lateut = "";
        if($login && $logout && !$absent){
            
            if($login)  $login = date("H:i",strtotime($login));
            if($logout) $logout = date("H:i",strtotime($logout));
            
            // Late
            $schedstart  = strtotime($stime);
            $logtime     = strtotime($login);
            $schedtardy   = strtotime($tardy) - 60; //< get actual tardy start
            if($logtime > $schedtardy){
                $lateut        = round(($logtime - strtotime($stime)) / 60,2);
                $lateut = date('H:i', mktime(0,$lateut));
            }
            
        }
        if($lateut == "00:00") $lateut = "";
        return $lateut;
    }

    function displayPendingApp($eid="",$date="",$absent="", $ol=""){
        $return="";
        $query1 = $this->db->query("SELECT a.id,b.type FROM leave_app_emplist a INNER JOIN leave_app_base b ON a.base_id = b.id WHERE '$date' BETWEEN b.datefrom AND b.dateto AND a.employeeid='$eid' AND b.status = 'PENDING'");
        if($query1->num_rows() > 0){  
            $desc_q = $this->db->query("SELECT description FROM code_request_form WHERE code_request='{$query1->row(0)->type}'");
            if($desc_q->num_rows() > 0) $return.=($return?", ".$desc_q->row(0)->description." APPLICATION":$desc_q->row(0)->description." APPLICATION");
            else $return.=($return?", LEAVE APPLICATION":"LEAVE APPLICATION");
        }
        $query1 = $this->db->query("SELECT a.id,b.type FROM ob_app_emplist a INNER JOIN ob_app b ON a.base_id = b.id WHERE '$date' BETWEEN b.datefrom AND b.dateto AND a.employeeid='$eid' AND b.status = 'PENDING' AND obtypes != '2'");
        if($query1->num_rows() > 0){  
            $obtype = $query1->row(0)->type;
            $obtypedesc = ($obtype == 'CORRECTION' ? "CORRECTION FOR TIME IN/OUT APPLICATION":($obtype == 'ABSENT' ? "ABSENT APPLICATION":"OFFICIAL BUSINESS APPLICATION"));
            $return.=($return?", ".$obtypedesc:$obtypedesc);
        }
        $query2 = $this->db->query("SELECT id FROM seminar_app WHERE '$date' BETWEEN datesetfrom AND datesetto AND applied_by='$eid' AND status = 'PENDING'");
        if($query2->num_rows() > 0){  
            $return.=($return?", SEMINAR APPLICATION":"SEMINAR APPLICATION");
        }
        $query3 = $this->db->query("SELECT a.id FROM ot_app_emplist a INNER JOIN ot_app b ON a.base_id = b.id WHERE '$date' BETWEEN b.dfrom AND b.dto AND a.employeeid='$eid' AND b.status = 'PENDING' AND b.ot_type = 'OT'");
        if($query3->num_rows() > 0){  
            # CHECK IF THE REQUEST CONTAINS A DISAPPROVED
            $queryOTChecker = $this->db->query("SELECT a.id FROM ot_app_emplist a INNER JOIN ot_app b ON a.base_id = b.id WHERE '$date' BETWEEN b.dfrom AND b.dto AND a.employeeid='$eid' AND a.status = 'DISAPPROVED' AND b.ot_type = 'OT'");
            if(!($queryOTChecker->num_rows() > 0)) $return.=($return?", OVERTIME APPLICATION":"OVERTIME APPLICATION");
        }

        $query33 = $this->db->query("SELECT a.id FROM ot_app_emplist a INNER JOIN ot_app b ON a.base_id = b.id WHERE '$date' BETWEEN b.dfrom AND b.dto AND a.employeeid='$eid' AND b.status = 'PENDING' AND b.ot_type = 'CTO'");
        if($query33->num_rows() > 0){  
            # CHECK IF THE REQUEST CONTAINS A DISAPPROVED
            $queryCTOChecker = $this->db->query("SELECT a.id FROM ot_app_emplist a INNER JOIN ot_app b ON a.base_id = b.id WHERE '$date' BETWEEN b.dfrom AND b.dto AND a.employeeid='$eid' AND a.status = 'DISAPPROVED' AND b.ot_type = 'CTO'");
            if(!($queryCTOChecker->num_rows() > 0)) $return.=($return?", COC APPLICATION":"COC APPLICATION");
        }
        
        $query4 = $this->db->query("SELECT a.id FROM change_sched_app_emplist a INNER JOIN change_sched_app b ON a.base_id = b.id WHERE '$date' BETWEEN b.dfrom AND b.dto AND a.employeeid='$eid' AND b.status = 'PENDING'"); 
        if($query4->num_rows() > 0){  
            $return.=($return?", CHANGE SCHEDULE APPLICATION":"CHANGE SCHEDULE APPLICATION");
        }

        $query4 = $this->db->query("SELECT a.id FROM sc_app_emplist_new a INNER JOIN sc_app b ON a.base_id = b.id WHERE `date`='$date' AND a.employeeid='$eid' AND b.app_status = 'PENDING' GROUP BY a.base_id"); 
        if($query4->num_rows() > 0){  
            $return.=($return?", APPLICATION CONVERSION SERVICE CREDIT":"APPLICATION CONVERSION SERVICE CREDIT");
        }

        $query5 = $this->db->query("SELECT b.id FROM employee_cto_usage_emplist a INNER JOIN employee_cto_usage b ON a.base_id = b.id WHERE `date_applied`='$date' AND a.employeeid='$eid' AND b.app_status = 'PENDING' GROUP BY a.base_id"); 
        if($query5->num_rows() > 0){  
            $return.=($return?", CTO APPLICATION":"CTO APPLICATION");
        }

        $query6 = $this->db->query("SELECT b.id FROM employee_proportional_vl a INNER JOIN proportional_vl_dates b ON a.id = b.base_id WHERE b.`date`='$date' AND a.employeeid='$eid' AND a.status = 'PENDING' GROUP BY b.base_id"); 
        if($query6->num_rows() > 0){  
            $return.=($return?", PROPORTIONAL VACATION LEAVE APPLICATION":"PROPORTIONAL VACATION LEAVE APPLICATION");
        }
        
        
        return $return;
    }

    function displayChangeSchedApp($employeeid='',$date=''){
        $return = '';
        $query4 = $this->db->query("SELECT a.id FROM change_sched_app_emplist a INNER JOIN change_sched_app b ON a.base_id = b.id WHERE '$date' BETWEEN b.dfrom AND b.dto AND a.employeeid='$employeeid' AND (b.status = 'APPROVED' OR b.status = 'BYPASSED')"); 
        if($query4->num_rows() > 0){  
            $return = 'EMPLOYEE CHANGE SCHEDULE';
        }
        return $return;
    }

    function displayServiceCredit($eid="",$stime='',$etime='',$date=""){

        $service_credit = '';
        $time_aff = $stime.'|'.$etime;
        
        $query = $this->db->query("SELECT a.*,b.* FROM sc_app_use a LEFT JOIN sc_app_use_emplist b ON a.id = b.base_id WHERE b.employeeid='$eid' AND a.date = '$date' AND b.status = 'APPROVED'");
        
        if($query->num_rows() > 0){
            foreach($query->result() as $row)
            {
                $arr_sched_aff = array();
                $service_credit = $row->needed_service_credit;

                if($service_credit == 0.5 && $row->sched_affected){
                    $arr_sched_aff = explode(',', $row->sched_affected);
                }

                if($service_credit == 0.5 && sizeof($arr_sched_aff) > 0){
                    if(!in_array($time_aff, $arr_sched_aff)){
                        $service_credit = '';
                    }
                }

            }
        }
        
        return $service_credit;
    }

    function displayCTOUsageAttendance($eid, $date, $stime, $etime){
        $total = $isHalfDay = $cto_id = $sched_affected = "";
        $official_time = $stime.'|'.$etime;
        $query = $this->db->query("SELECT * FROM employee_cto_usage WHERE employeeid = '$eid' AND date_applied = '$date' AND app_status = 'APPROVED'");
        if($query->num_rows() > 0){
            $total = $query->row()->total;
            $isHalfDay = $query->row()->ishalfday;
            $cto_id = $query->row()->id;
            $sched_affected = $query->row()->sched_affected;
            if($isHalfDay == 1){
                if($official_time != $sched_affected){
                    $total = $isHalfDay = $cto_id = $sched_affected = "";
                }
            }
        }

        return array($total, $isHalfDay, $cto_id, $sched_affected);
    }

    function insertOTListToArray($ot_save_list, $ot_list){
        if(count($ot_list)){
            foreach ($ot_list as $ot_type => $ot_data) {
                foreach ($ot_data as $holiday_type => $holiday_data) {
                    foreach ($holiday_data as $is_excess => $ot_time) {
                        $ot_save_list[] = array(
                            'ot_hours'=> $this->time->sec_to_hm($ot_time),
                            'ot_type' => $ot_type,
                            'holiday_type' => $holiday_type,
                            'is_excess' => $is_excess
                        );
                    }
                }
            }
        }
        
        return $ot_save_list;
    }
    
    function constructOTlist($ot_list,$ot_list_tmp){
        foreach ($ot_list_tmp as $ot_type => $det) {
            foreach ($det as $ot_hol_type => $ex_det) {
                foreach ($ex_det as $isExcess => $ot_hours) {
                    if(!isset($ot_list[$ot_type][$ot_hol_type][$isExcess])) $ot_list[$ot_type][$ot_hol_type][$isExcess] = 0;
                    $ot_list[$ot_type][$ot_hol_type][$isExcess] += $ot_hours;
                }
            }
        }
        return $ot_list;
    }

    function getOvertime($employeeid='',$date='',$hasSched=true,$code_holtype=''){
        // need time ng ot
        // check if weekend

        //TODO : NIGHT_DIFF

        $ot_list = array();
        $excess_limit = 8*60*60;

        $dayofweek = date('N',strtotime($date));
        $isWeekend = in_array($dayofweek, array('6','7')) ? true : false;

        $ot_type = '';
        if($hasSched) $ot_type = 'WITH_SCHED';
        if($hasSched && $isWeekend) $ot_type = 'WITH_SCHED_WEEKEND';
        if(!$hasSched) $ot_type = 'NO_SCHED';

        $holiday_type = 'NONE';
        if($code_holtype){
           /* if($code_holtype == 1)  $holiday_type = 'REGULAR';
            elseif($code_holtype != 1) $holiday_type = 'SPECIAL';*/
            $holiday_type = $code_holtype;
        }

        if (strpos($holiday_type, 'SPECIAL ') !== false) {
            $holiday_type = "SPECIAL";
        }


        $ot_q = $this->db->query("
                                    SELECT tstart,tend,total
                                    FROM overtime_request
                                    WHERE employeeid='$employeeid' AND ('$date' BETWEEN dfrom AND dto) AND `status` = 'APPROVED' 
                                ");

        foreach ($ot_q->result() as $key => $row) {
            $isExcess = false;
            $excess = 0;
            $ottime = $this->time->exp_time($row->total);

            if($ottime > $excess_limit){
                $excess = $ottime - $excess_limit;
                $ottime = $excess_limit;
            }

            if($excess > 0) $isExcess = true;

            /*for multiple apply of ot*/
            if(isset($ot_list[$ot_type][$holiday_type])){
                $ot_list[$ot_type][$holiday_type][0] += $ottime;
                if($isExcess) $ot_list[$ot_type][$holiday_type][1] += $excess;
            }else{
                $ot_list[$ot_type][$holiday_type][0] = $ottime;
                if($isExcess) $ot_list[$ot_type][$holiday_type][1] = $excess;
            }
        }
        // echo '<pre>'.$date;
        // print_r($ot_list);
        // echo '</pre>';

        return $ot_list;
    }

    function displayOt($eid="",$date="",$hasSched=true){
        $otreg = $otrest = $othol = 0;
        // $query = $this->db->query("SELECT a.*,b.* FROM ot_app a LEFT JOIN ot_app_emplist b ON a.id = b.base_id WHERE b.employeeid='$eid' AND '$date' BETWEEN a.dfrom AND a.dto AND b.status = 'APPROVED'");

        $query = $this->db->query("
                                    SELECT a.tstart,a.tend,a.total
                                    FROM overtime_request a INNER JOIN ot_app b ON a.aid = b.id
                                    WHERE a.employeeid='$eid' AND ('$date' BETWEEN a.dfrom AND a.dto) AND a.status = 'APPROVED' AND b.ot_type = 'OT'
                                ");
       if($query->num_rows() > 0){
            foreach($query->result() as $value){
                if      ($hasSched)  $otreg += $this->time->exp_time($value->total);
                else                 $otrest += $this->time->exp_time($value->total);
                
                if($this->isHoliday($date)){

                    $otreg = $otrest = 0;
                    $othol += $this->time->exp_time($value->total);
                }
            }
        }
        
        $otreg = ($otreg) ? $this->time->sec_to_hm($otreg) : 0;
        $otrest = ($otrest) ? $this->time->sec_to_hm($otrest) : 0;
        $othol = ($othol) ? $this->time->sec_to_hm($othol) : 0;
        return array($otreg,$otrest,$othol);
    }

    public function getTotalHoliday($date_from, $date_to, $employeeid){
        $count_holiday = 0;

        $status = "";
        $q_emp_data = $this->db->query("SELECT CONCAT(office, '~', employmentstat) AS status_included FROM employee WHERE employeeid='$employeeid';")->result();
        foreach ($q_emp_data as $row) $status = $row->status_included;

        $q_count_holiday = $this->db->query("SELECT COUNT(*) AS count_holiday
                                             FROM  code_holiday_calendar a
                                             INNER JOIN holiday_inclusions b ON b.holi_cal_id = a.holiday_id
                                             WHERE a.date_from BETWEEN '$date_from' AND '$date_to' AND a.date_to BETWEEN '$date_from' AND '$date_to' AND status_included LIKE '%$status%'")->result();
        foreach ($q_count_holiday as $row) $count_holiday = $row->count_holiday;
        
        return $count_holiday;
    }

    function isFixedDay($empid=''){
        $fixedday = TRUE;
        $fixedday_q = $this->db->query("SELECT fixedday FROM payroll_employee_salary WHERE employeeid='$empid'");
        if($fixedday_q->num_rows() > 0) $fixedday = $fixedday_q->row(0)->fixedday;
        return $fixedday;
    }

    function totalTimeabsentLecLabAdminRleCompute($absent){
        $returnVal = array();
        foreach ($absent as $key => $days) {
            $totalTime = 0;
            foreach ($days as $value) {
                $totalTime += $value;
            }
            $returnVal[$key] = $totalTime;
        }
        return json_encode($returnVal);
    }

    function hasLogtime($empid, $date){
        return $this->db->query("SELECT * FROM timesheet WHERE DATE(timein) = '$date' AND userid = '$empid'")->num_rows();
    }

    function constructLateUTBedSummary($perday_info=array(),$bed_isfirsthalf_absent=false,$bed_issechalf_absent=false,$bed_rowcount_half=0){
        $lateut_perday = array('tlec'=>0,'tlab'=>0,'tadmin'=>0,'trle'=>0);

        foreach ($perday_info as $key => $persched_info) {
            $lec = $lab = $admin = 0;

            $lec = $this->time->exp_time($persched_info['deduc_lec']);
            $lab = $this->time->exp_time($persched_info['deduc_lab']);
            $admin = $this->time->exp_time($persched_info['deduc_admin']);
            $rle = $this->time->exp_time($persched_info['deduc_rle']);

            $late_lec = $lec + $this->time->exp_time($persched_info['lateut_lec']);
            $late_lab = $lab + $this->time->exp_time($persched_info['lateut_lab']);
            $late_admin = $admin + $this->time->exp_time($persched_info['lateut_admin']);
            $late_rle = $rle + $this->time->exp_time($persched_info['lateut_rle']);

            if($key < $bed_rowcount_half){
                if(!$bed_isfirsthalf_absent){
                    if($persched_info['sched_type'] == 'LEC'){ 
                        $lateut_perday['tlec'] +=  $late_lec;
                    }elseif($persched_info['sched_type'] == 'LAB'){ 
                        $lateut_perday['tlab'] += $late_lab;
                    }elseif($persched_info['sched_type'] == 'ADMIN'){ 
                        $lateut_perday['tadmin'] += $late_admin;
                    }else{                                        
                        $lateut_perday['trle'] += $late_rle;
                    }
                }
            }else{
                if(!$bed_issechalf_absent){
                    if($persched_info['sched_type'] == 'LEC'){ 
                        $lateut_perday['tlec'] +=  $late_lec;
                    }elseif($persched_info['sched_type'] == 'LAB'){ 
                        $lateut_perday['tlab'] += $late_lab;
                    }elseif($persched_info['sched_type'] == 'ADMIN'){ 
                        $lateut_perday['tadmin'] += $late_admin;
                    }else{                                        
                        $lateut_perday['trle'] += $late_rle;
                    }
                }
            }
        }
        return $lateut_perday;
    }

    function getWorkhoursPerdayArr($stime='',$etime='',$type='',$aimsdept='',$cur_date='',$workhours_perday=array(),$lateutlec='',$tschedlec='',$lateutlab='',$tschedlab='',$lateutadmin='',$tschedadmin,$holiday='',$empid='',$date='',$deptid='',$sl='',$vl='',$login='',$logout='',$lateutrle='',$tschedrle='',$has_last_log=false,$has_after_suspension=false, $isFirstSched = false){
        $suspension_less = 0;
        $twork_lec = $twork_lab = $twork_admin = $twork_rle = 0;
        $tsched   = round(abs(strtotime($stime) - strtotime($etime)) / 60,2);
        $tsched   = date('H:i', mktime(0,$tsched));
        $tsched   = $this->time->exp_time($tsched);
        $is_suspension = true;
        $is_holiday_halfday = $this->isHolidayNew($empid, $date,$deptid, "", "on");
        list($fromtime, $totime) = $this->getHolidayHalfdayTime($date);
        $holidayInfo = $this->holidayInfo($date);
        if($is_holiday_halfday && ($fromtime && $totime) ){
            $is_half_holiday = true;
            if($holidayInfo["holiday_type"] == 5){
                $isAffected = $this->affectedBySuspension(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
                
                if($isAffected){
                    $tschedlec = $tschedlab = $tschedadmin = $tschedrle = "";
                    $is_half_holiday = true;
                    if($holidayInfo["holiday_type"] == 5){
                        if($has_after_suspension){
                            if($login && $logout){
                                $rate = 100;
                            }else{
                                if($has_last_log) $rate = 100;
                                else $rate = 50;
                            }
                        }else{
                            $isAffectedBefore = $this->affectedBySuspensionBefore(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
                            if($isAffectedBefore){
                                $rate = 50;
                                if($has_last_log && !$isFirstSched) $rate = 100;
                            }

                            $isAffectedAfter = $this->affectedBySuspensionAfter(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
                            if($isAffectedAfter){
                                $rate = 50;
                                if($has_last_log) $rate = 100;
                            }
                        }
                    }

                    $display_hol_remarks = true;
                }else{
                    $is_half_holiday = false;
                    if($holidayInfo["holiday_type"] == 5) $rate = 100;
                    else $rate = $this->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");

                    if(!$login && !$logout) $rate = 0;
                    else $suspension_less = $tsched;
                }

                $tsched = $tsched * $rate / 100;
            }else{
                $is_suspension = false;
                $rate = $this->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
                $tsched = $tsched * $rate / 100;
            }
        }else{
            // $tschedlec = $tschedlab = $tschedadmin = $tschedrle = "";
            $rate = 100;
            $is_half_holiday = true;
            if(isset($holidayInfo["holiday_type"]) && $holidayInfo["holiday_type"] == 5){
                $is_half_holiday = true;
                $rate = 50;
            }else{
                if(isset($holidayInfo["holiday_type"])) $rate = $this->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
                $is_suspension = false;
                $is_half_holiday = false;
            }

            $tsched = $tsched * $rate / 100;
        }


        /*special condition of */
       /* $leave_project = 0;
        if($sl){
            if(isset($workhours_perday[$cur_date])) $leave_project = $this->SLComputation($workhours_perday[$cur_date], $tsched);
        }else if($vl){
            if($this->extensions->isNursingDepartment($empid) > 0) $leave_project = $this->SLComputation($workhours_perday[$cur_date], $tsched);
            else $leave_project = 0;
        }*/

        ///< perdepartment work hours
        if($type == 'LEC'){
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LEC']['work_hours'])) $workhours_perday[$cur_date][$aimsdept]['LEC']['work_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LEC']['late_hours'])) $workhours_perday[$cur_date][$aimsdept]['LEC']['late_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LEC']['deduc_hours'])) $workhours_perday[$cur_date][$aimsdept]['LEC']['deduc_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LEC']['is_suspension'])) $workhours_perday[$cur_date][$aimsdept]['LEC']['is_suspension'] = $is_suspension;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LEC']['suspension_less'])) $workhours_perday[$cur_date][$aimsdept]['LEC']['suspension_less'] = $suspension_less;
            // if(!isset($workhours_perday[$cur_date][$aimsdept]['LEC']['leave_project'])) $workhours_perday[$cur_date][$aimsdept]['LEC']['leave_project'] = 0;
            $workhours_perday[$cur_date][$aimsdept]['LEC']['work_hours'] += $tsched;
            $workhours_perday[$cur_date][$aimsdept]['LEC']['late_hours'] += ($holiday) ? 0 : $this->time->exp_time($lateutlec);
            $workhours_perday[$cur_date][$aimsdept]['LEC']['deduc_hours'] += ($holiday) ? 0 : $this->time->exp_time($tschedlec);
            $workhours_perday[$cur_date][$aimsdept]['LEC']['aimsdept'] = $aimsdept;
            // $workhours_perday[$cur_date][$aimsdept]['LEC']['leave_project'] += $leave_project;
        }elseif($type == 'LAB'){
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LAB']['work_hours'])) $workhours_perday[$cur_date][$aimsdept]['LAB']['work_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LAB']['late_hours'])) $workhours_perday[$cur_date][$aimsdept]['LAB']['late_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LAB']['deduc_hours'])) $workhours_perday[$cur_date][$aimsdept]['LAB']['deduc_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LAB']['is_suspension'])) $workhours_perday[$cur_date][$aimsdept]['LAB']['is_suspension'] = $is_suspension;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LAB']['suspension_less'])) $workhours_perday[$cur_date][$aimsdept]['LAB']['suspension_less'] = $suspension_less;
            // if(!isset($workhours_perday[$cur_date][$aimsdept]['LAB']['leave_project'])) $workhours_perday[$cur_date][$aimsdept]['LAB']['leave_project'] = 0;
            $workhours_perday[$cur_date][$aimsdept]['LAB']['work_hours'] += $tsched;
            $workhours_perday[$cur_date][$aimsdept]['LAB']['late_hours'] += ($holiday) ? 0 : $this->time->exp_time($lateutlab);
            $workhours_perday[$cur_date][$aimsdept]['LAB']['deduc_hours'] += ($holiday) ? 0 : $this->time->exp_time($tschedlab);
            $workhours_perday[$cur_date][$aimsdept]['LAB']['aimsdept'] = $aimsdept;
            // $workhours_perday[$cur_date][$aimsdept]['LAB']['leave_project'] += $leave_project;
        }elseif($type == 'RLE'){
            if(!isset($workhours_perday[$cur_date][$aimsdept]['RLE']['work_hours'])) $workhours_perday[$cur_date][$aimsdept]['RLE']['work_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['RLE']['late_hours'])) $workhours_perday[$cur_date][$aimsdept]['RLE']['late_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['RLE']['deduc_hours'])) $workhours_perday[$cur_date][$aimsdept]['RLE']['deduc_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['RLE']['is_suspension'])) $workhours_perday[$cur_date][$aimsdept]['RLE']['is_suspension'] = $is_suspension;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['RLE']['suspension_less'])) $workhours_perday[$cur_date][$aimsdept]['RLE']['suspension_less'] = $suspension_less;
            // if(!isset($workhours_perday[$cur_date][$aimsdept]['RLE']['leave_project'])) $workhours_perday[$cur_date][$aimsdept]['RLE']['leave_project'] = 0;
            $workhours_perday[$cur_date][$aimsdept]['RLE']['work_hours'] += $tsched;
            $workhours_perday[$cur_date][$aimsdept]['RLE']['late_hours'] += ($holiday) ? 0 : $this->time->exp_time($lateutrle);
            $workhours_perday[$cur_date][$aimsdept]['RLE']['deduc_hours'] += ($holiday) ? 0 : $this->time->exp_time($tschedrle);
            $workhours_perday[$cur_date][$aimsdept]['RLE']['aimsdept'] = $aimsdept;
            // $workhours_perday[$cur_date][$aimsdept]['ADMIN']['leave_project'] += $leave_project;
        }else{
            if(!isset($workhours_perday[$cur_date][$aimsdept]['ADMIN']['work_hours'])) $workhours_perday[$cur_date][$aimsdept]['ADMIN']['work_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['ADMIN']['late_hours'])) $workhours_perday[$cur_date][$aimsdept]['ADMIN']['late_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['ADMIN']['deduc_hours'])) $workhours_perday[$cur_date][$aimsdept]['ADMIN']['deduc_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['ADMIN']['is_suspension'])) $workhours_perday[$cur_date][$aimsdept]['ADMIN']['is_suspension'] = $is_suspension;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['ADMIN']['suspension_less'])) $workhours_perday[$cur_date][$aimsdept]['ADMIN']['suspension_less'] = $suspension_less;
            // if(!isset($workhours_perday[$cur_date][$aimsdept]['ADMIN']['leave_project'])) $workhours_perday[$cur_date][$aimsdept]['ADMIN']['leave_project'] = 0;
            $workhours_perday[$cur_date][$aimsdept]['ADMIN']['work_hours'] += $tsched;
            $workhours_perday[$cur_date][$aimsdept]['ADMIN']['late_hours'] += ($holiday) ? 0 : $this->time->exp_time($lateutadmin);
            $workhours_perday[$cur_date][$aimsdept]['ADMIN']['deduc_hours'] += ($holiday) ? 0 : $this->time->exp_time($tschedadmin);
            $workhours_perday[$cur_date][$aimsdept]['ADMIN']['aimsdept'] = $aimsdept;
            // $workhours_perday[$cur_date][$aimsdept]['ADMIN']['leave_project'] += $leave_project;
        }

        return $workhours_perday;
    }

	public function getHolidayTypeRate($holiday_type, $teachingtype){
		$q_holiday = $this->db->query("SELECT * FROM code_holiday_type WHERE holiday_type = '$holiday_type'");
		if($q_holiday->num_rows() > 0){
			if($teachingtype == "teaching") return $q_holiday->row()->t_rate;
			else return $q_holiday->row()->nt_rate;
		}
	}

    public function affectedBySuspension($hol_start="", $hol_end="", $sched_start="", $sched_end=""){
        $hol_start = strtotime($hol_start);
        $hol_end = strtotime($hol_end);
        $sched_start = strtotime($sched_start);
        $sched_end = strtotime($sched_end);
        if(($hol_start <= $sched_start && $hol_end >= $sched_start) || ($hol_start <= $sched_end && $hol_end >= $sched_end)) return true;
        else return false;
    }

    public function affectedBySuspensionBefore($hol_start="", $hol_end="", $sched_start="", $sched_end=""){
        $hol_start = strtotime($hol_start);
        $hol_end = strtotime($hol_end);
        $sched_start = strtotime($sched_start);
        $sched_end = strtotime($sched_end);
        if($hol_start <= $sched_start && $hol_end >= $sched_start) return true;
        else return false;
    }

    public function affectedBySuspensionAfter($hol_start="", $hol_end="", $sched_start="", $sched_end=""){
        $hol_start = strtotime($hol_start);
        $hol_end = strtotime($hol_end);
        $sched_start = strtotime($sched_start);
        $sched_end = strtotime($sched_end);
        if($sched_start < $hol_start && $hol_end > $sched_end) return true;
        else return false;
    }

    function getWorkhoursPerdeptArr($stime='',$etime='',$type='',$aimsdept='',$workhours_arr=array(),$lateutlec='',$tschedlec='',$lateutlab='',$tschedlab='',$lateutadmin='',$tschedadmin, $empid='',$date='',$deptid='',$sl='',$vl='',$login='',$logout='',$workhours_perday=array(),$cur_date='',$lateutrle='',$tschedrle='',$has_last_log=false,$has_after_suspension=false, $isFirstSched = false, $start='', $end='', $isStartEnd=false){
        $twork_lec = $twork_lab = $twork_admin = $twork_rle = 0;

        if($isStartEnd){
            $mins = ($end - $start) / 60;
            if(!$end || !$start || $mins < 0) $mins = 0;
            // echo "<pre>"; print_r($mins); die;
            $tsched = $mins * 60;
        }else{
            $tsched   = round(abs(strtotime($stime) - strtotime($etime)) / 60,2);
            $tsched   = date('H:i', mktime(0,$tsched));
            $tsched   = $this->time->exp_time($tsched);
        }
        

            

        if($type == 'LEC')       $twork_lec =  $tsched;
        elseif($type == 'LAB')   $twork_lab = $tsched;
        elseif($type == "ADMIN") $twork_admin = $tsched;
        else                     $twork_rle = $tsched;

        $is_holiday_halfday = $this->isHolidayNew($empid, $date,$deptid, "", "on");
        list($fromtime, $totime) = $this->getHolidayHalfdayTime($date);
        $holidayInfo = $this->holidayInfo($date);
        if($is_holiday_halfday && ($fromtime && $totime) ){
            $is_half_holiday = true;
            if($holidayInfo["holiday_type"] == 5){
                $isAffected = $this->affectedBySuspension(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
                
                if($isAffected){
                    $tschedlec = $tschedlab = $tschedadmin = $tschedrle = "";
                    $is_half_holiday = true;
                    if($holidayInfo["holiday_type"] == 5){
                        if($has_after_suspension){
                            if($login && $logout){
                                $rate = 100;
                            }else{
                                if($has_last_log) $rate = 100;
                                else $rate = 50;
                            }
                        }else{
                            $isAffectedBefore = $this->affectedBySuspensionBefore(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
                            if($isAffectedBefore){
                                $rate = 50;
                                if($has_last_log && !$isFirstSched) $rate = 100;
                            }

                            $isAffectedAfter = $this->affectedBySuspensionAfter(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
                            if($isAffectedAfter){
                                $rate = 50;
                                if($has_last_log) $rate = 100;
                            }
                        }
                    }else{
                        $rate = $this->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
                    }

                    $display_hol_remarks = true;
                }else{
                    $is_half_holiday = false;
                    if($holidayInfo["holiday_type"] == 5) $rate = 100;
                    else $rate = $this->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");

                    if(!$login && !$logout) $rate = 0;
                }

                $tsched = $tsched * $rate / 100;
            }else{
                $is_suspension = false;
                $rate = $this->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
                $tsched = $tsched * $rate / 100;
            }
        }else{
            // $tschedlec = $tschedlab = $tschedadmin = $tschedrle = "";
            $rate = 100;
            $is_half_holiday = true;
            if(isset($holidayInfo["holiday_type"]) && $holidayInfo["holiday_type"] == 5){
                $is_half_holiday = true;
                $rate = 50;
            }else{
                if(isset($holidayInfo["holiday_type"])) $rate = $this->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
                $is_half_holiday = false;
            }

            $tsched = $tsched * $rate / 100;
        }

        /*special condition of */
        $leave_project = 0;
        if($sl){
            $leave_project = $this->SLComputation(isset($workhours_perday[$cur_date]) ? $workhours_perday[$cur_date] : array(), $tsched);
        }else if($vl){
            if($this->isNursingDepartment($empid) > 0 && !$this->isNursingExcluded($empid)) $leave_project = $this->SLComputation(isset($workhours_perday[$cur_date]) ? $workhours_perday[$cur_date] : array(), $tsched);
            else $leave_project = $tsched;
        }

        ///< perdepartment work hours
        if($type == 'LEC'){
            if(!isset($workhours_arr[$aimsdept]['LEC']['work_hours'])) $workhours_arr[$aimsdept]['LEC']['work_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['LEC']['late_hours'])) $workhours_arr[$aimsdept]['LEC']['late_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['LEC']['deduc_hours'])) $workhours_arr[$aimsdept]['LEC']['deduc_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['LEC']['leave_project'])) $workhours_arr[$aimsdept]['LEC']['leave_project'] = 0;
            $workhours_arr[$aimsdept]['LEC']['work_hours'] += $tsched;
            $workhours_arr[$aimsdept]['LEC']['late_hours'] += $this->time->exp_time($lateutlec);
            $workhours_arr[$aimsdept]['LEC']['deduc_hours'] += $this->time->exp_time($tschedlec);
            $workhours_arr[$aimsdept]['LEC']['leave_project'] += $leave_project;
        }elseif($type == 'LAB'){
            if(!isset($workhours_arr[$aimsdept]['LAB']['work_hours'])) $workhours_arr[$aimsdept]['LAB']['work_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['LAB']['late_hours'])) $workhours_arr[$aimsdept]['LAB']['late_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['LAB']['deduc_hours'])) $workhours_arr[$aimsdept]['LAB']['deduc_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['LAB']['leave_project'])) $workhours_arr[$aimsdept]['LAB']['leave_project'] = 0;
            $workhours_arr[$aimsdept]['LAB']['work_hours'] += $tsched;
            $workhours_arr[$aimsdept]['LAB']['late_hours'] += $this->time->exp_time($lateutlab);
            $workhours_arr[$aimsdept]['LAB']['deduc_hours'] += $this->time->exp_time($tschedlab);
            $workhours_arr[$aimsdept]['LAB']['leave_project'] += $leave_project;
        }elseif($type == 'RLE'){
            if(!isset($workhours_arr[$aimsdept]['RLE']['work_hours'])) $workhours_arr[$aimsdept]['RLE']['work_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['RLE']['late_hours'])) $workhours_arr[$aimsdept]['RLE']['late_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['RLE']['deduc_hours'])) $workhours_arr[$aimsdept]['RLE']['deduc_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['RLE']['leave_project'])) $workhours_arr[$aimsdept]['RLE']['leave_project'] = 0;
            $workhours_arr[$aimsdept]['RLE']['work_hours'] += $tsched;
            $workhours_arr[$aimsdept]['RLE']['late_hours'] += $this->time->exp_time($lateutrle);
            $workhours_arr[$aimsdept]['RLE']['deduc_hours'] += $this->time->exp_time($tschedrle);
            $workhours_arr[$aimsdept]['RLE']['leave_project'] += $leave_project;
        }
        else{
            if(!isset($workhours_arr[$aimsdept]['ADMIN']['work_hours'])) $workhours_arr[$aimsdept]['ADMIN']['work_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['ADMIN']['late_hours'])) $workhours_arr[$aimsdept]['ADMIN']['late_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['ADMIN']['deduc_hours'])) $workhours_arr[$aimsdept]['ADMIN']['deduc_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['ADMIN']['leave_project'])) $workhours_arr[$aimsdept]['ADMIN']['leave_project'] = 0;
            $workhours_arr[$aimsdept]['ADMIN']['work_hours'] += $tsched;
            $workhours_arr[$aimsdept]['ADMIN']['late_hours'] += $this->time->exp_time($lateutadmin);
            $workhours_arr[$aimsdept]['ADMIN']['deduc_hours'] += $this->time->exp_time($tschedadmin);
            $workhours_arr[$aimsdept]['ADMIN']['leave_project'] += $leave_project;
        }

        return array($twork_lec,$twork_lab,$twork_admin,$workhours_arr,$twork_rle);
    }

    public function SLComputation($workhours_arr, $tsched){
        /*for sl computation*/
       $t_lec = $t_lab = 0;
       foreach($workhours_arr as $aimsdept_c => $row){
           foreach($row as $type_c => $workhours_c){
               if($type_c=="LEC") $t_lec += $workhours_c["work_hours"];
               elseif($type_c=="LAB") $t_lab += $workhours_c["work_hours"];
           }
       }

       if($t_lec < 17400){
           if(($t_lec + $tsched) > 17400){
               $tsched = ($t_lec + $tsched) - 17400;
               // $tsched -= $excess;
           }
       }else{
           $tsched = 0;
       }

       if($t_lec < 17400){
           if(($t_lab + $tsched) > 17400){
               $tsched = ($t_lab + $tsched) - 17400;
               // $tsched -= $excess;
           }
       }else{
           $tsched = 0;
       }

       return $tsched;
    }

    function getBEDPerdayAbsent($setup=array(),$persched_info=array()){
        $rowcount_half = 0;
        $isfirsthalf_absent = $issechalf_absent = $iswholeday_absent = true;

        if( $this->time->exp_time($persched_info['sched_start']) >= $this->time->exp_time($setup['firsthalf_start']) && $this->time->exp_time($persched_info['sched_end']) <= $this->time->exp_time($setup['halfday_cutoff']) ){
            $rowcount_half++;

            if($persched_info['isAbsent'] == 0) $isfirsthalf_absent = $iswholeday_absent = false;

        }elseif( $this->time->exp_time($persched_info['sched_start']) > $this->time->exp_time($setup['halfday_cutoff']) && $this->time->exp_time($persched_info['sched_end']) <= $this->time->exp_time($setup['sechalf_end']) ){
            if($persched_info['isAbsent'] == 0) $issechalf_absent = $iswholeday_absent = false;

        }elseif( $this->time->exp_time($persched_info['sched_start']) >= $this->time->exp_time($setup['firsthalf_start']) && $this->time->exp_time($persched_info['sched_end']) <= $this->time->exp_time($setup['sechalf_end']) ){
            if($persched_info['isAbsent'] == 0) $iswholeday_absent = $isfirsthalf_absent = $issechalf_absent = false;
        }
        return array($rowcount_half,$isfirsthalf_absent,$issechalf_absent,$iswholeday_absent);
    }

    function displayLateUTNew($stime="",$etime="",$tardy_start='',$login="",$logout="",$type="",$absent=""){
        if(!$tardy_start) $tardy_start = $stime;
        $lec = $lab = $tschedlec = $tschedlab = $admin = $tschedadmin = $rle = $tschedrle = 0;
        $schedstart   = strtotime($stime);
        $schedend   = strtotime($etime);
        $schedtardy   = strtotime($tardy_start);
        $suddenAbsent = false;
        if($login && $logout && !$absent){
            if($login)  $login = date("H:i:s",strtotime($login));
            if($logout) $logout = date("H:i:s",strtotime($logout));
            
            # START CALCULATION
            if(strtotime($login) > strtotime(date('Y-m-d')." ".$stime)) $start = strtotime($login);
            else $start = strtotime(date('Y-m-d')." ".$stime);
            if(strtotime($logout) > strtotime(date('Y-m-d')." ".$etime)) $end = strtotime(date('Y-m-d')." ".$etime);
            else $end = strtotime($logout);
            $workhoursmins = ($end - $start) / 60;
            $late = ($start -  strtotime(date('Y-m-d')." ".$stime)) / 60;
            $undertime = ($end -  strtotime(date('Y-m-d')." ".$etime)) / 60;
            # END CAL
            
            if(!$end || !$start || $workhoursmins < 0) $workhoursmins = 0;
            if(strtotime(date('Y-m-d')." ".$etime) > strtotime($end) && $workhoursmins == 0){
                $suddenAbsent = true;
            }
            if($suddenAbsent){
                // $undertime = ($start -  strtotime(date('Y-m-d')." ".$etime)) / 60;
                // $totalABSENT += abs($undertime);
            }else{ 
                $late += abs($undertime);
            }
            
            if($type == 'LEC')       $lec =  $late;
            elseif($type == 'LAB')   $lab = $late;
            elseif($type == 'RLE')   $rle = $late;
            else                     $admin = $late;
        }

        if($type == 'LEC' && $lec)          $lec =  date('H:i', mktime(0,$lec));
        elseif($type == 'LAB' && $lab)      $lab =  date('H:i', mktime(0,$lab));
        elseif($type == 'RLE' && $rle)      $rle =  date('H:i', mktime(0,$rle));
        else $admin =                       date('H:i', mktime(0,$admin));
        
        if($absent){
            // total sched
            $tsched   = round(abs($schedstart - $schedend) / 60,2);
            $tsched   = date('H:i', mktime(0,$tsched));
            if($type == 'LEC')       $tschedlec =  $tsched;
            elseif($type == 'LAB')   $tschedlab = $tsched;
            elseif($type == 'RLE')   $tschedrle = $tsched;
            else                     $tschedadmin = $tsched;
        }
         
        return array($lec,$lab,$admin,$tschedlec,$tschedlab,$tschedadmin,$rle,$tschedrle);
    }

    function displayAbsent($stime="",$etime="",$login="",$logout="",$empid="",$dset="",$earlyd="",$absent_start="", $night_shift = 0, $date='', $updatedLogout=0){
        if($absent_start == "") $absent_start = $etime; /* if no absent start , end time magiging absent start */
        $absent = "";
        $isteaching = $this->getempteachingtype($empid);
        if($night_shift == 0 && strtotime(date("H:i:s",strtotime($login))) < strtotime(date("H:i:s",strtotime($logout))) ){
            if($login)  $login = date("H:i:s",strtotime($login));
            if($logout) $logout = date("H:i:s",strtotime($logout));
            $schedstart   = strtotime($stime);
            $schedend   = strtotime($etime);
            $logtime    = strtotime($login);
            $logouttime    = strtotime($logout);
            
            $schedHour = round((abs($logouttime - $logtime) /60)/60,2);
            $interval   = round(abs($schedend - $schedstart) / 60,2);
            // $interval   = round(abs($schedend - $etime) / 60,2);
            
            $totalHoursOfWork = round(abs($schedend - $schedstart) / 60,2);
            
            // echo $interval;
            
            if($schedHour <= 2)
            {
                if( $stime && ($interval <= 30 || !$login) && $stime <> '00:00:00'  ) $absent = date('H:i', mktime(0,$totalHoursOfWork));
            }
            else if($schedHour > 2)
            {
                if( $stime && !$login && $stime <> '00:00:00'  ) $absent = date('H:i', mktime(0,$totalHoursOfWork));
                // if( $stime && ($interval <= 60 || !$login) && $stime <> '00:00:00'  ) $absent = date('H:i', mktime(0,$totalHoursOfWork));
            }
            
            if($empid){
                $query = $this->db->query("SELECT * FROM attendance_absent_checker WHERE employeeid='$empid' AND scheddate = '$dset' AND schedstart = '$stime' AND schedend = '$etime'");
                if($query->num_rows() > 0)  $absent++;
            }

            if($logout <= $stime && !$absent) $absent = date('H:i', mktime(0,$totalHoursOfWork));  // log-out <= start of schedule will be marked as absent.


            if(!$absent && $earlyd)if($logout < $earlyd) $absent = date('H:i', mktime(0,$totalHoursOfWork));  // log-out <= early dismissal will be marked as absent. 

            if(!$absent && $absent_start){
                if($login >= $absent_start && $stime <> $login && $login == '00:00:00'){
                    $absent = date('H:i', mktime(0,$totalHoursOfWork));  // log-in >= absent start will be marked as absent.
                }
            } 

            // if(!$isteaching)    $absent = ($absent/2) ? ($absent/2) : "";
            if(!$absent && !$login && !$logout){
                 $absent = date('H:i', mktime(0,$totalHoursOfWork));
            }

            return $absent;
        }else{
            list($years, $months, $days, $hours, $minutes, $seconds) = $this->time->getDateTimeDiff($login, date('Y-m-d H:i:s', strtotime($logout . ' +1 day')));
            list($sched_years, $sched_months, $sched_days, $sched_hours, $sched_minutes, $sched_seconds) = $this->time->getDateTimeDiff($date." ".$stime, date('Y-m-d H:i:s', strtotime($date." ".$etime . ' +1 day')));
            $schedstart   = strtotime($date." ".$stime);
            $earlyd = $date." ".$earlyd;
            $schedend   = strtotime(date('Y-m-d H:i:s', strtotime($date." ".$etime . ' +1 day')));
            $logtime    = strtotime($login);
            if($updatedLogout == 0) $logouttime  = strtotime(date('Y-m-d H:i:s', strtotime($logout . ' +1 day')));
            else $logouttime  = strtotime(date('Y-m-d H:i:s', strtotime($logout)));

            $schedHour = round((abs($logouttime - $logtime) /60)/60,2);
            $interval   = round(abs($schedend - $schedstart) / 60,2);
            
            $totalHoursOfWork = round(abs($schedend - $schedstart) / 60,2);

            
            if($schedHour <= 2)
            {
                if( $stime && ($interval <= 30 || !$login) && $stime <> '00:00:00'  ) $absent = date('H:i', mktime(0,$totalHoursOfWork));
            }
            else if($schedHour > 2)
            {
                if( $stime && !$login && $stime <> '00:00:00'  ) $absent = date('H:i', mktime(0,$totalHoursOfWork));

                // if( $stime && ($interval <= 60 || !$login) && $stime <> '00:00:00'  ) $absent = date('H:i', mktime(0,$totalHoursOfWork));
            }
            
            if($empid){
                $query = $this->db->query("SELECT * FROM attendance_absent_checker WHERE employeeid='$empid' AND scheddate = '$dset' AND schedstart = '$stime' AND schedend = '$etime'");
                if($query->num_rows() > 0)  $absent++;
            }

            if(!$absent && $earlyd){
                if(date('Y-m-d H:i:s', strtotime($logout . ' +1 day')) < $earlyd){
                    $absent = date('H:i', mktime(0,$totalHoursOfWork));  // log-out <= early dismissal will be marked as absent. 
                }
            }

            if(!$absent && $absent_start){
                if(strtotime(date("H:i:s", strtotime($login))) >= strtotime($absent_start)){
                    // $absent = date('H:i', mktime(0,$totalHoursOfWork));  // log-in >= absent start will be marked as absent.
                }
            } 
            // if(!$isteaching)    $absent = ($absent/2) ? ($absent/2) : "";
            if(!$absent && (!$login || !$logout)){
                 $absent = date('H:i', mktime(0,$totalHoursOfWork));
            }

            return $absent;
        }
            
    }

    function getempteachingtype($user = ""){
        $return = false;
        $query = $this->db->query("SELECT teachingtype FROM employee WHERE employeeid='$user'");
        if($query->num_rows() > 0)  $return = ($query->row(0)->teachingtype == "teaching" ? true : false);
        return $return;    
    }
    
    function hasWFHTimeRecord($id, $date){
        return $this->db->query("SELECT * FROM ob_timerecord WHERE base_id = '$id' AND t_date = '$date' AND status = 'APPROVED'");
    }

    function isWfhOB($eid, $date){
        return $this->db->query("SELECT * FROM ob_request WHERE '$date' BETWEEN fromdate AND todate AND employeeid='$eid' AND (ob_type = 'ob' OR ob_type = '') AND obtypes = '2'");
    }
    
    function displayLeave($eid="",$date="",$absent="",$stime='',$etime='',$sched_count=''){
        $sl = $el = $vl = $ol = $ob = $abs_count = $tfrom = $tto = $daterange = $split=$l_nopay=0;
        $l_nopay_remarks = "";
        $oltype = ""; $ob_app_id = "";
         $time_aff = $stime.'|'.$etime;
         $query = $this->db->query("SELECT * FROM leave_request WHERE '$date' BETWEEN fromdate AND todate AND employeeid='$eid' AND status = 'APPROVED' ");
         // echo $this->db->last_query().'<br>';
         if($query->num_rows() > 0){  
             $res = $query->row(0);
             $arr_sched_aff = array();
             $no_days = $res->no_days;
             $base_id = $res->aid;
 
             $time_aff = $this->displayLeaveSched($eid, $date, $sched_count);
 
             if($no_days == 0.50 && $res->sched_affected){
                 $arr_sched_aff = explode(',', $res->sched_affected);
             }
 
 
             if($res->leavetype == "VL" && $query->row(0)->paid == "YES")
             {     
                 if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                     if(in_array($time_aff, $arr_sched_aff)){
                         $vl = $no_days; 
                         $ol = $res->leavetype; 
                         $oltype = $this->othLeaveDesc($ol);
                     }
                 }else{
                     $vl = $no_days >= 1 ? 1.00 : $no_days;  
                     $ol = $res->leavetype; 
                     $oltype = $this->othLeaveDesc($ol);
                 }  
             }
             else if(strpos($res->leavetype, 'PL-') !== false && $query->row(0)->paid == "YES")
             {     
                 if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                     if(in_array($time_aff, $arr_sched_aff)){
                         $vl = $no_days; 
                         $ol = $res->leavetype; 
                         $oltype = $this->othLeaveDesc($ol);
                     }
                 }else{
                     $vl = $no_days >= 1 ? 1.00 : $no_days;  
                     $ol = $res->leavetype; 
                     $oltype = $this->othLeaveDesc($ol);
                 }  
             }
             else if($res->leavetype == "EL" && $res->paid == "YES"){  
                 if($no_days == 0.50){
                     if(in_array($time_aff, $arr_sched_aff)){
                         $vl = $no_days; 
                         $ol = $res->leavetype; 
                         $oltype = $this->othLeaveDesc($ol);
                     }
                 }else{
                     $vl = 1.00; 
                     $ol = $res->leavetype; 
                     $oltype = $this->othLeaveDesc($ol);
                 }  
             }
             else if($res->paid == "YES" && ($res->leavetype != "VL" && $res->leavetype != "SL")){  
                 if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                     if(in_array($time_aff, $arr_sched_aff)){
                         
                         $ol = $res->leavetype; 
                         $oltype = $this->othLeaveDesc($ol);
                         $ol = $no_days; 
                     }
                 }else{
                       
                     $ol = $res->leavetype; 
                     $oltype = $this->othLeaveDesc($ol);
                     $ol = $no_days >= 1 ? 1.00 : $no_days;
                 }  
             }
             else if($res->leavetype == "SL" && $res->paid == "YES"){  
                 if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                     if(in_array($time_aff, $arr_sched_aff)){
                         $sl = $no_days; 
                         $ol = $res->leavetype; 
                         $oltype = $this->othLeaveDesc($ol);
                     }
                 }else{
                     $sl = $no_days >= 1 ? 1.00 : $no_days;  
                     $ol = $res->leavetype; 
                     $oltype = $this->othLeaveDesc($ol);
                 }  
             }
             else if($res->leavetype == "ABSENT"){  
                 if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                     if(in_array($time_aff, $arr_sched_aff)){
                         $abs_count = $no_days; 
                         $ol = $res->leavetype; 
                         $oltype = $this->othLeaveDesc($ol);
                     }
                 }else{
                     $abs_count = $no_days >= 1 ? 1.00 : $no_days;  
                     $ol = $res->leavetype; 
                     $oltype = $this->othLeaveDesc($ol);
                 }  
             }
             else if($res->leavetype == "other"/* && $res->paid == "YES"*/){ 
                 // $othertype = $res->othertype;
                 // if($othertype=='NO PUNCH IN/OUT')   $oltype = 'CORRECTED TIME IN/OUT';
                 // elseif($othertype=='ABSENT')        $oltype = 'ABSENT W/ FILE';
                 // else                                $oltype = "OFFICIAL BUSINESS";
                 $ol = $res->other; 
             }else if($res->leavetype && $res->paid == "NO"){ 
                 $l_nopay = $no_days;
                 $ol = $res->leavetype; 
                 $l_nopay_remarks = $this->othLeaveDesc($ol).' APPLICATION (NO PAY)';
             }
             else{
                 $ol = $res->leavetype;  
                 $oltype = $this->othLeaveDesc($ol);
             }
         }
 
         $query1 = $this->db->query("SELECT * FROM ob_request WHERE '$date' BETWEEN fromdate AND todate AND employeeid='$eid' AND (ob_type = 'ob' OR ob_type = '') AND obtypes != '2' AND status = 'APPROVED' ");
         $obtypes = "";
         if($query1->num_rows() > 0){  
             $res = $query1->row(0);
             $ob_app_id = $res->aid;
             $arr_sched_aff = array();
             $no_days = $res->no_days;
             $isHalfDay = $res->isHalfDay;
             if(!$res->sched_affected) $res->sched_affected = $res->timefrom."|".$res->timeto;
             // echo "<pre>"; print_r($res->sched_affected);
             $arr_sched_aff = explode(',', $res->sched_affected);
             $obtypes = $res->obtypes;
 
             if($isHalfDay  && sizeof($arr_sched_aff) > 0){
                 if(in_array($time_aff, $arr_sched_aff)){
                     $othertype = $res->type;
                     if($othertype=='DA' && $res->paid == "YES"){
                         if($isHalfDay) $ob = 0.50;
                         else $ob = $no_days;
                     }
                     if($othertype=='CORRECTION')        $oltype .= 'CORRECTED TIME IN/OUT';
                     elseif($othertype=='ABSENT')        $oltype .= 'ABSENT W/ FILE';
                     else                                $oltype .= "OFFICIAL BUSINESS";
                     $ol = $othertype;
                 }
             }else{
                 $othertype = $res->type;
                 if($othertype=='DA' && $res->paid == "YES"){
                     if($isHalfDay) $ob = 0.50;
                     else $ob = 1.00; 
                 }
                 if($othertype=='CORRECTION')        $oltype .= 'CORRECTED TIME IN/OUT';
                 elseif($othertype=='ABSENT')        $oltype .= 'ABSENT W/ FILE';
                 else                                $oltype .= "OFFICIAL BUSINESS";
                 $ol = $othertype;
             }
 
         }
 
         $query2 = $this->db->query("SELECT * FROM ob_request WHERE '$date' BETWEEN fromdate AND todate AND employeeid='$eid' AND ob_type != 'ob' AND type != 'CORRECTION'  AND status = 'APPROVED' ");
         
         if($query2->num_rows() > 0){  
 
             $res = $query2->row(0);
             $ob_app_id = $res->aid;
             $ob = "";
             $othertype = $res->ob_type;
             if($othertype=='late')        $oltype = 'Excuse Slip (late)';
             elseif($othertype=='absent')  $oltype = 'Excuse Slip (absent)';
             // $ol = $othertype;
             
         }
 
         $query3 = $this->db->query("SELECT * FROM ob_request WHERE '$date' BETWEEN fromdate AND todate AND employeeid='$eid' AND (ob_type = 'ob' OR ob_type = '') AND obtypes = '2'  AND status = 'APPROVED'");
 
         if($query3->num_rows() > 0){  
 
             $res = $query3->row(0);
             $ob_app_id = $res->aid;
             if($res->type == "DA"){
                 $obtypes = $res->obtypes;
                 $is_wfh = $this->isWfhOB($eid,$date);
                 if($is_wfh->num_rows() == 1){
                     $ob_id = $is_wfh->row()->aid;
                     $hastime = $this->hasWFHTimeRecord($ob_id,$date);
                     if($hastime->num_rows() == 0){
                         // $ol = $oltype = $ob = "";
                     }
                     else{
                         $fitSched = false;
                         foreach ($hastime->result() as $htkey => $htval) {
                             $ht_timein =  date("H:i:s", strtotime($htval->timein));
                             $ht_timeout =  date("H:i:s", strtotime($htval->timeout));
                             if($ht_timein <= $stime || $ht_timeout >= $etime){
                                 $fitSched = true;
                             }
                         }
                         if($fitSched){
                             $othertype = $res->type;
                             $ob = 1.00;
                             if($oltype) $oltype .= "<br><br>OFFICIAL BUSINESS";
                             else $oltype .= "OFFICIAL BUSINESS";
                             if(!$ol) $ol = $othertype;
                         }
                     }
                 }
                  
             }
 
         }
 
         return array($el,$vl,$sl,$ol,$oltype,$ob,$abs_count,$l_nopay,$obtypes, $ob_app_id, $l_nopay_remarks);
    }

    function othLeaveDesc($type=""){
        $return = "";
        if($type)   $wC = " WHERE code_request='$type'";
        $query = $this->db->query("SELECT * FROM code_request_form $wC");
        foreach($query->result() as $row){
            $return = $row->description;
        }
        return $return;
    }

    function displayLeaveSched($employeeid='', $date='',$sched_count=''){
        $sched = array();
        $schedule = $this->db->query("SELECT * FROM employee_schedule_history WHERE DATE(dateactive) <= '$date' AND idx = DATE_FORMAT('$date','%w') AND employeeid = '$employeeid' ");
        if($schedule->num_rows() > 0){
            $seq_count = 1;
            foreach($schedule->result() as $res){
                $sched[$seq_count] = $res->starttime."|".$res->endtime;
                $seq_count++;
            }
        }
  
        return isset($sched[$sched_count]) ? $sched[$sched_count] : "|";
    }

    function getEmployeeScheduleComputeLateUndertimeAbsence($employeeid='',$start='',$end='',$returnSetter='',$returntype=""){
		$getDays = $this->time->displayDateRange($start, $end);
		if(is_array($getDays)){
			foreach ($getDays as $key => $value) {
				// $daysched[$value->dte] = $this->leave->getEmployeeSchedule($employeeid, $value->dte)->result();
                $daysched[$value->dte] = $this->getEmployeeScheduleTypeLecLab($employeeid, $value->dte,$returntype)->result();
			}
		}else{
			// $daysched = $this->leave->getEmployeeSchedule($employeeid, $start)->result();
            $daysched = $this->getEmployeeScheduleTypeLecLab($employeeid, $start,$returntype)->result();
		}
	
		$sched_arr = array();
		$totalTimeAbsent = 0;
		if(count($daysched) > 0){
            $totalUT = $totalLATE = $totalABSENT = $totalWORKHOURS = 0;
			foreach ($daysched as $dayKey => $sched) {
                if(count($sched)>0){
                    $totalCountSched = count($sched)-1;
                    $mainStartTime = $sched[0]->starttime;
                    $mainEndTime = $sched[$totalCountSched]->endtime;
                }
                $q = $haslog_forremarks = '';
                $used_time = array();
                $seq = 0;
				foreach ($sched as $key => $row) {
                    $seq += 1;
                    $suddenAbsent = false;
                    $stime = $row->starttime;
				    $etime = $row->endtime;
				    $tardy_start = $row->tardy_start;
				    $absent_start = $row->absent_start;
				    $earlydismissal = $row->early_dismissal;
                     
                    list($login,$logout,$q,$haslog_forremarks,$used_time) = $this->displayLogTime($employeeid,$dayKey,$stime,$etime,"NEW",$seq,$absent_start,$earlydismissal,$used_time);
                    // echo "<pre>"; var_dump($employeeid." - ".$dayKey." - ".$stime." - ".$etime." - 'NEW' - ".$seq." - ".$absent_start." - ".$earlydismissal." - ",$this->db->last_query());
                    
                    // IF THE EMPLOYEE HAS SCHEDULE
                    if($row->starttime && $row->endtime){
                        $sched_arr[$dayKey][$key]['sched'] = $row->starttime." - ".$row->endtime;
                        $sched_arr[$dayKey][$key]['logs'] = $login." - ".$logout;
                        $sched_arr[$dayKey][$key]['isAbsent'] = ($login && $logout ? false : true);
                        $dayworkhoursmins = (strtotime($dayKey." ".$mainEndTime) - strtotime($dayKey." ".$mainStartTime)) / 60;
                        $sched_arr[$dayKey][$key]['dayworkhours'] = $this->time->minutesToHours($dayworkhoursmins);
                        # CALCULATION
                        if(strtotime($login) > strtotime($dayKey." ".$stime)) $start = strtotime($login);
                        else $start = strtotime($dayKey." ".$stime);
                        if(strtotime($logout) > strtotime($dayKey." ".$etime)) $end = strtotime($dayKey." ".$etime);
                        else $end = strtotime($logout);
                        $workhoursmins = ($end - $start) / 60;
                        $late = ($start -  strtotime($dayKey." ".$row->starttime)) / 60;
                        $undertime = ($end -  strtotime($dayKey." ".$row->endtime)) / 60;
                        # END CAL
                        
                        if(!$end || !$start || $workhoursmins < 0) $workhoursmins = 0;
                        if(strtotime($dayKey." ".$row->endtime) > strtotime($end) && $workhoursmins == 0){
                            $suddenAbsent = true;
                        }
                        $sched_arr[$dayKey][$key]['startend'] = date('Y-m-d H:i:s',$start)." - ".date('Y-m-d H:i:s',$end);
                        $sched_arr[$dayKey][$key]['workhours'] = $this->time->minutesToHours($workhoursmins);
                        $sched_arr[$dayKey][$key]['late'] = $this->time->minutesToHours(abs($late));
                        if($suddenAbsent){
                            $undertime = ($start -  strtotime($dayKey." ".$row->endtime)) / 60;
                            $sched_arr[$dayKey][$key]['undertime'] = '0:00';
                            $sched_arr[$dayKey][$key]['suddenAbsent'] = $this->time->minutesToHours(abs($undertime));
                            $totalABSENT += abs($undertime);
                        }else{ 
                            $totalUT += abs($undertime);
                            $sched_arr[$dayKey][$key]['undertime'] = $this->time->minutesToHours(abs($undertime));
                            $sched_arr[$dayKey][$key]['suddenAbsent'] = '0:00';
                        }
                        $totalLATE += abs($late);
                        $totalWORKHOURS += $workhoursmins; 
                        # if you want to see all schedule including their lates, ut and absents
                        // var_dump($this->time->minutesToHours(abs($totalUT))."  ".$this->time->minutesToHours(abs($totalLATE))."  ".$this->time->minutesToHours(abs($totalABSENT))."  ".$this->time->minutesToHours(abs($totalWORKHOURS)));
                    }
				}
			}
		}
		// echo "<pre>";var_dump($sched_arr);
        if($returnSetter == 'late') return $totalLATE;
        if($returnSetter == 'ut') return $totalUT;
        if($returnSetter == 'absent') return $totalABSENT;
        if($returnSetter == 'workhours') return $totalWORKHOURS;

        # if you want to see all schedule including their lates, ut and absents
        # return json_encode($sched_arr);
	}

    function getEmployeeScheduleTypeLecLab($employeeid='',$date='',$type=''){
		$wc = '';
		if($type) $wc .= " AND leclab='$type'"; 
		$query = $this->db->query("SELECT dateactive FROM employee_schedule_history WHERE employeeid = '$employeeid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) ORDER BY dateactive DESC,starttime DESC LIMIT 1;");
		if($query->num_rows() > 0){
            $da = $query->row(0)->dateactive;
            $query = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$employeeid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) AND DATE_FORMAT(dateactive,'%Y-%m-%d %H') = DATE_FORMAT('$da','%Y-%m-%d %H') $wc GROUP BY starttime,endtime ORDER BY starttime;"); 
        }
        return $query;
	}

    function displayLogTimeOutsideOT($eid="",$date=""){
        $haslog = true;
        $timein = $timeout = $otype = "";
        $overload = 0;
        $time_logs = array();

        $tbl = "timesheet";

        $query = $this->db->query("
                SELECT timein,timeout,otype FROM $tbl 
                WHERE userid='$eid' 
                AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                ORDER BY timein ASC");
        if($query->num_rows() > 0){
            foreach ($query->result() as $key => $value) {
                $time_logs[$key]['timein'] = date("H:i:s", strtotime($value->timein));
                $time_logs[$key]['timeout'] = date("H:i:s", strtotime($value->timeout));
            }
        }
        

        $overload_logs = $time_logs;
        $sched = $this->displaySched($eid,$date);
        foreach($sched->result() as $rsched){
            $stime = $rsched->starttime;
            $etime = $rsched->endtime; 
            foreach ($time_logs as $key => $value) {
                $timein = $value['timein'];
                $timeout = $value['timeout'];
                if($timein <= $etime && $timeout >= $stime){
                    unset($overload_logs[$key]);
                }
            }
            
        }
       
        foreach ($overload_logs as $key => $value) {
            $start = strtotime($date." ".$value['timein']);
            $end = strtotime($date." ".$value['timeout']);
            $overload += ($end - $start) / 60;
        }
        return $overload;
    }

    function displayLogTime($eid="",$date="",$tstart="",$tend="",$tbl="NEW",$seq=1,$absent_start='',$earlyd='',$used_time=array(), $campus='', $night_shift=0){
        // echo "<pre>"; print_r($date);die;
        $haslog = true;
        $timein = $timeout = $otype = $is_ob = "";

        if($tbl == "NEW")   $tbl = "timesheet";
        else                $tbl = "timesheet_bak";

        /*$wCAbsentEarlyD = '';
        if($absent_start) $wCAbsentEarlyD .= " AND ( TIME(timeout) > '$absent_start' )";
        if($earlyd)       $wCAbsentEarlyD .= " AND ( TIME(timein) < '$earlyd'  )";*/
        // echo "<pre>"; print_r(array($tstart, $tend));
        $add_wc = "";
        if($used_time){
            if(!isset($used_time[0])) $used_time[0] = "0000-00-00 00:00:00";
            if(!isset($used_time[1])) $used_time[1] = "0000-00-00 00:00:00";
            $add_wc = " AND timein != '{$used_time[0]}' AND timeout != '{$used_time[1]}' ";
        }
        //QUERY CHANGED TO DESC FROM ASC :KEN
        if($night_shift == 1){
            $query = $this->db->query("
                SELECT timein,timeout,otype, addedby, ob_id FROM $tbl 
                WHERE userid='$eid' 
                AND ( DATE(timein)='$date' ) 
                AND timein != timeout
                AND (UNIX_TIMESTAMP(timeout) - UNIX_TIMESTAMP(timein) ) > '60' 
                ORDER BY timein ASC LIMIT 1");
        }else{
            $query = $this->db->query("
            SELECT timein,timeout,otype, addedby, ob_id FROM $tbl 
            WHERE userid='$eid' 
            AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
            AND ( TIME(timein)<='$absent_start' )
            -- AND ( TIME(timein)>='$tstart' )
            AND ( TIME(timeout) >= '$earlyd' ) 
            AND timein != timeout
            $add_wc
            AND (UNIX_TIMESTAMP(timeout) - UNIX_TIMESTAMP(timein) ) > '60' 
            ORDER BY timein ASC LIMIT 1");
        }
        //QUERY CHANGED TO ASC FROM DESC :MAX 2022
        
            // echo "<pre>"; print_r($this->db->last_query()); die;
        
        if($query->num_rows() > 0){
            $otype   = $query->row($seq)->otype;
            $addedby   = $query->row($seq)->addedby;
            $seq = $seq - 1;
            $timein  = $query->row($seq)->timein;
            $timeout = $query->row($seq)->timeout;
            $is_ob = $query->row($seq)->ob_id;
            
            if(in_array($timein, $used_time) && in_array($timeout, $used_time)){
                $query = $this->db->query("
                SELECT timein,timeout,otype, addedby, ob_id FROM $tbl 
                WHERE userid='$eid' 
                AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                AND ( TIME(timein)<='$tend' )
                -- AND ( TIME(timein)>='$tstart' )
                AND ( TIME(timeout) > '$tstart' ) 
                AND timein != timeout
        
                ORDER BY timein DESC LIMIT 1");
                if($query->num_rows() > 0){
                    $timein  = $query->row($seq)->timein;
                    $timeout = $query->row($seq)->timeout;
                    $otype   = $query->row($seq)->otype;
                    $addedby   = $query->row($seq)->addedby;
                    $is_ob = $query->row($seq)->ob_id;
                }
            } 

            if($otype == "Facial" && $campus){
                if($addedby != "FacialResync"){
                    $facial_campus_id = $this->db->query("SELECT campusid FROM facial_heartbeat WHERE deviceKey = '$addedby'");
                    if($facial_campus_id->num_rows() > 0){
                        if($facial_campus_id->row()->campusid != $campus){
                            $timein = $timeout = $otype = "";
                        }

                        if($timein == "" && $timeout == ""){
                            $query = $this->db->query("
                            SELECT timein,timeout,otype, addedby FROM $tbl 
                            WHERE userid='$eid' 
                            AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                            AND ( TIME(timein)<='$tend' )
                            -- AND ( TIME(timein)>='$tstart' )
                            AND ( TIME(timeout) > '$tstart' ) 
                            AND timein != timeout
                    
                            ORDER BY timein DESC");
                            if($query->num_rows() > 0){
                                foreach ($query->result() as $key => $value) {
                                    $time_in  = date('Y-m-d', strtotime($value->timein));
                                    $time_out =   date('Y-m-d', strtotime($value->timeout));
                                    $added_by   = $value->addedby;
                                    $facial_logs = $this->db->query("SELECT * FROM facial_Log a INNER JOIN facial_heartbeat b ON a.deviceKey = b.deviceKEy WHERE a.deviceKey = '$added_by' AND ( DATE(`date`)='$time_in' AND DATE(`date`)='$time_out' ) AND b.campusid = '$campus' AND employeeid = '$eid' ");
                                    if($facial_logs->num_rows()){
                                        $timein = $value->timein;
                                        $timeout = $value->timeout;
                                        $otype = "Facial";
                                    }

                                }
                            }
                        }
                    }

                    if($night_shift != 1){
                        $query = "
                            SELECT 
                                MIN(a.`time`) AS timein, 
                                MAX(a.`time`) AS timeout, 
                                a.deviceKey, 
                                a.`date`
                            FROM facial_Log a 
                            INNER JOIN facial_heartbeat b ON a.deviceKey = b.deviceKey
                            WHERE a.deviceKey = ? 
                            AND a.employeeid = ? 
                            AND DATE(FROM_UNIXTIME(FLOOR(a.`time`/1000))) = ?
                        ";

                        $result = $this->db->query($query, [$addedby, $eid, $date])->row_array();

                        if (!empty($result['timein'])) {
                            $timein = date("Y-m-d H:i:s", substr($result['timein'], 0, 10));
                            $timeout = date("Y-m-d H:i:s", substr($result['timeout'], 0, 10));
                            $otype = "Facial";
                        }
                    }
                    
                }
            }else if($otype == "Facial" && $addedby != "FacialResync" && $night_shift != 1){
                $query = "
                        SELECT 
                            MIN(a.`time`) AS timein, 
                            MAX(a.`time`) AS timeout, 
                            a.deviceKey, 
                            a.`date`
                        FROM facial_Log a 
                        WHERE a.deviceKey = ? 
                          AND a.employeeid = ? 
                          AND DATE(FROM_UNIXTIME(FLOOR(a.`time`/1000))) = ?
                    ";

                    $result = $this->db->query($query, [$addedby, $eid, $date])->row_array();

                    if (!empty($result['timein'])) {
                        $timein = date("Y-m-d H:i:s", substr($result['timein'], 0, 10));
                        $timeout = date("Y-m-d H:i:s", substr($result['timeout'], 0, 10));
                        $otype = "Facial";
                    }
            }
            
        }else{

            $wCAbsentEarlyD = '';
            if($absent_start) $wCAbsentEarlyD .= " AND ( TIME(timeout) > '$absent_start' OR DATE_FORMAT(timeout,'%H:%i:%s') = '00:00:00' )";
            if($earlyd)       $wCAbsentEarlyD .= " AND ( TIME(timein) < '$earlyd' OR DATE_FORMAT(timein,'%H:%i:%s') = '00:00:00' )";

            $query = $this->db->query("
                    SELECT timein,timeout,otype, addedby FROM $tbl 
                    WHERE userid='$eid' 
                    AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                    AND ( TIME(timein)<='$tend' OR  DATE_FORMAT(timein,'%H:%i:%s') = '00:00:00' )
                    AND ( TIME(timeout) > '$tstart' OR DATE_FORMAT(timeout,'%H:%i:%s') = '00:00:00' ) 
                    AND timein != timeout
                    $wCAbsentEarlyD 
                    ORDER BY timein ASC LIMIT 1");
               
    
                    // echo "<pre>"; print_r($this->db->last_query());
                    // echo "<pre>"; print_r($query->num_rows());

            if($query->num_rows() > 0){
                $seq = $seq - 1;

                $timein  = $query->row($seq)->timein;
                $timeout = $query->row($seq)->timeout;
                $otype   = $query->row($seq)->otype;  
                $addedby   = $query->row($seq)->addedby;  
                if($otype == "Facial" && $campus){
                    if($addedby != "FacialResync"){
                        $facial_campus_id = $this->db->query("SELECT campusid FROM facial_heartbeat WHERE deviceKey = '$addedby'");
                        if($facial_campus_id->num_rows() > 0){
                            if($facial_campus_id->row()->campusid != $campus){
                                $timein = $timeout = $otype = "";
                            }

                            if($timein == "" && $timeout == ""){
                                $query = $this->db->query("
                                SELECT timein,timeout,otype, addedby FROM $tbl 
                                WHERE userid='$eid' 
                                AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                                AND ( TIME(timein)<='$tend' )
                                -- AND ( TIME(timein)>='$tstart' )
                                AND ( TIME(timeout) > '$tstart' ) 
                                AND timein != timeout
                        
                                ORDER BY timein DESC");
                                if($query->num_rows() > 0){
                                    foreach ($query->result() as $key => $value) {
                                        $time_in  = date('Y-m-d', strtotime($value->timein));
                                        $time_out =   date('Y-m-d', strtotime($value->timeout));
                                        $added_by   = $value->addedby;
                                        $facial_logs = $this->db->query("SELECT * FROM facial_Log a INNER JOIN facial_heartbeat b ON a.deviceKey = b.deviceKEy WHERE a.deviceKey = '$added_by' AND ( DATE(`date`)='$time_in' AND DATE(`date`)='$time_out' ) AND b.campusid = '$campus' AND employeeid = '$eid' ");
                                        if($facial_logs->num_rows()){
                                            $timein = $value->timein;
                                            $timeout = $value->timeout;
                                            $otype = "Facial";
                                        }

                                    }
                                }
                            }
                            if($night_shift != 1){
                                $query = "
                                    SELECT 
                                        MIN(a.`time`) AS timein, 
                                        MAX(a.`time`) AS timeout, 
                                        a.deviceKey, 
                                        a.`date`
                                    FROM facial_Log a 
                                    INNER JOIN facial_heartbeat b ON a.deviceKey = b.deviceKey
                                    WHERE a.deviceKey = ? 
                                    AND a.employeeid = ? 
                                    AND DATE(FROM_UNIXTIME(FLOOR(a.`time`/1000))) = ?
                                ";

                                $result = $this->db->query($query, [$addedby, $eid, $date])->row_array();

                                if (!empty($result['timein'])) {
                                    $timein = date("Y-m-d H:i:s", substr($result['timein'], 0, 10));
                                    $timeout = date("Y-m-d H:i:s", substr($result['timeout'], 0, 10));
                                    $otype = "Facial";
                                }
                            }
                            
                        }
                    }
                } else if($otype == "Facial" && $addedby != "FacialResync" && $night_shift != 1){
                    $query = "
                            SELECT 
                                MIN(a.`time`) AS timein, 
                                MAX(a.`time`) AS timeout, 
                                a.deviceKey, 
                                a.`date`
                            FROM facial_Log a 
                            WHERE a.deviceKey = ? 
                              AND a.employeeid = ? 
                              AND DATE(FROM_UNIXTIME(FLOOR(a.`time`/1000))) = ?
                        ";

                        $result = $this->db->query($query, [$addedby, $eid, $date])->row_array();

                        if (!empty($result['timein'])) {
                            $timein = date("Y-m-d H:i:s", substr($result['timein'], 0, 10));
                            $timeout = date("Y-m-d H:i:s", substr($result['timeout'], 0, 10));
                            $otype = "Facial";
                        }
                }
            }else{
                
                $query = $this->db->query("
                SELECT timein,timeout,otype, addedby, ob_id FROM $tbl 
                WHERE userid='$eid' 
                AND ( DATE(timein)='$date' ) 
                AND timein != timeout
                AND (UNIX_TIMESTAMP(timeout) - UNIX_TIMESTAMP(timein) ) > '60' 
                ORDER BY timein ASC LIMIT 1");

                $query = $this->db->query("SELECT logtime FROM timesheet_trail WHERE userid='$eid' AND DATE(logtime)='$date' AND log_type = 'IN' ORDER BY logtime DESC LIMIT $seq");
             
                if($query->num_rows() > 0){
                    $seq = $seq - 1;
                    $timein  = $query->row($seq)->logtime;
                    $timeout = $otype = "";
                    // $return = array($timein,"","",$haslog);
                }else{
                    $haslog = false;
                    $checklog_q = $this->db->query("SELECT timein,timeout, addedby, otype, ob_id FROM $tbl WHERE userid='$eid' AND DATE(timein)='$date' ORDER BY timein DESC LIMIT $seq"); // lol timeid to timein DESC
                  
                    if($checklog_q->num_rows() > 0) $haslog = true;
                    if($haslog){
                        $seq = $seq - 1;                           // lola put this
                        $timein = $timeout = "";
                        $timein  = $checklog_q->row($seq)->timein; // lola get value timein
                        $timeout  = $checklog_q->row($seq)->timeout; // URS-564
                        $otype   = $checklog_q->row($seq)->otype;  
                        $addedby   = $checklog_q->row($seq)->addedby; 
                        $is_ob   = $checklog_q->row($seq)->ob_id;  
                        if($otype == "Facial" && $campus){
                            if($addedby != "FacialResync"){
                                $facial_campus_id = $this->db->query("SELECT campusid FROM facial_heartbeat WHERE deviceKey = '$addedby'");
                                if($facial_campus_id->num_rows() > 0){
                                    if($facial_campus_id->row()->campusid != $campus){
                                        $timein = $timeout = $otype = "";
                                    }

                                    if($timein == "" && $timeout == ""){
                                        $query = $this->db->query("
                                        SELECT timein,timeout,otype, addedby FROM $tbl 
                                        WHERE userid='$eid' 
                                        AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                                        AND ( TIME(timein)<='$tend' )
                                        -- AND ( TIME(timein)>='$tstart' )
                                        AND ( TIME(timeout) > '$tstart' ) 
                                        AND timein != timeout
                                
                                        ORDER BY timein DESC");
                                        if($query->num_rows() > 0){
                                            foreach ($query->result() as $key => $value) {
                                                $time_in  = date('Y-m-d', strtotime($value->timein));
                                                $time_out =   date('Y-m-d', strtotime($value->timeout));
                                                $added_by   = $value->addedby;
                                                $facial_logs = $this->db->query("SELECT * FROM facial_Log a INNER JOIN facial_heartbeat b ON a.deviceKey = b.deviceKEy WHERE a.deviceKey = '$added_by' AND ( DATE(`date`)='$time_in' AND DATE(`date`)='$time_out' ) AND b.campusid = '$campus' AND employeeid = '$eid' ");
                                                if($facial_logs->num_rows()){
                                                    $timein = $value->timein;
                                                    $timeout = $value->timeout;
                                                    $otype = "Facial";
                                                }

                                            }
                                        }
                                    }
                                    if($night_shift != 1){
                                        $query = "
                                            SELECT 
                                                MIN(a.`time`) AS timein, 
                                                MAX(a.`time`) AS timeout, 
                                                a.deviceKey, 
                                                a.`date`
                                            FROM facial_Log a 
                                            INNER JOIN facial_heartbeat b ON a.deviceKey = b.deviceKey
                                            WHERE a.deviceKey = ? 
                                            AND a.employeeid = ? 
                                            AND DATE(FROM_UNIXTIME(FLOOR(a.`time`/1000))) = ?
                                        ";

                                        $result = $this->db->query($query, [$addedby, $eid, $date])->row_array();

                                        if (!empty($result['timein'])) {
                                            $timein = date("Y-m-d H:i:s", substr($result['timein'], 0, 10));
                                            $timeout = date("Y-m-d H:i:s", substr($result['timeout'], 0, 10));
                                            $otype = "Facial";
                                        }
                                    }
                                }
                            }
                            $otype = true;
                        }else if($otype == "Facial" && $addedby != "FacialResync"  && $night_shift != 1){
                            $query = "
                                    SELECT 
                                        MIN(a.`time`) AS timein, 
                                        MAX(a.`time`) AS timeout, 
                                        a.deviceKey, 
                                        a.`date`
                                    FROM facial_Log a 
                                    WHERE a.deviceKey = ? 
                                      AND a.employeeid = ? 
                                      AND DATE(FROM_UNIXTIME(FLOOR(a.`time`/1000))) = ?
                                ";

                                $result = $this->db->query($query, [$addedby, $eid, $date])->row_array();

                                if (!empty($result['timein'])) {
                                    $timein = date("Y-m-d H:i:s", substr($result['timein'], 0, 10));
                                    $timeout = date("Y-m-d H:i:s", substr($result['timeout'], 0, 10));
                                    $otype = "Facial";
                                }
                        }
                    }else{

                        $query = "
                        SELECT 
                            MIN(a.`time`) AS timein, 
                            MAX(a.`time`) AS timeout, 
                            a.deviceKey, 
                            a.`date`
                        FROM facial_Log a 
                        WHERE 
                           a.employeeid = ? 
                          AND DATE(FROM_UNIXTIME(FLOOR(a.`time`/1000))) = ?
                    ";

                    $result = $this->db->query($query, [$eid, $date])->row_array();

                    if (!empty($result['timein'])) {
                        $timein = date("Y-m-d H:i:s", substr($result['timein'], 0, 10));
                        $timeout = date("Y-m-d H:i:s", substr($result['timeout'], 0, 10));
                        $otype = "Facial";
                    }else if($night_shift != 1){
                         // DISPLAY THE TIME-IN OF EMPLOYEE IMMEDIATELY.
                        $query = $this->db->select('stamp_in, stamp_out, DATE(FROM_UNIXTIME(FLOOR(`time_in`/1000))) AS datecreated')
                                ->from('login_attempts_terminal')
                                ->where('user_id', $eid)
                                ->where("DATE(FROM_UNIXTIME(FLOOR(`time_in`/1000)))", $date)
                                ->get();

                        $rows = $query->result_array();
                        $haslog = false;
                        if($query->num_rows() > 0) $haslog = true;
                        if($haslog){
                            $timein = $rows[0]['stamp_in'];
                        }
                    }

                    }
                }
            }   
        }
        if($timein=='0000-00-00 00:00:00') $timein = "";
        if($timeout=='0000-00-00 00:00:00') $timeout = "";
        $used_time = array($timein, $timeout);

   
        return array($timein,$timeout,$otype,$haslog,$used_time, $is_ob);
    }

    function getBEDAttendanceSetup(){
        $setup = array();
        $setup['firsthalf_start']    = '05:00';
        $setup['halfday_cutoff']     = '12:00';
        $setup['sechalf_end']        = '21:00';
        return $setup;
    }

	public function substituteTotalHours($date, $empid, $holiday="", $holiday_type=""){
		$subtotal = 0;
		$subs_arr = array();
		$q_sub = $this->db->query("SELECT * FROM `substitute_request` WHERE employeeid = '$empid' AND '$date' BETWEEN dfrom AND dto");
		if($q_sub->num_rows() > 0){
			foreach($q_sub->result_array() as $row){
				$id = $row["id"];
				$total = $row["total"];
				$subs_arr[$id]["holiday"] = $holiday;
				$subs_arr[$id]["holiday_type"] = $holiday_type;
				$subs_arr[$id]["hours"] = $row["total"];
				$subs_arr[$id]["type"] = $row["type"];
				$subs_arr[$id]["aimsdept"] = $row["aimsdept"];

				$subtotal += $this->time->exp_time($row["total"]);
			}
		}

		return array($subs_arr, $this->time->sec_to_hm($subtotal));
	}

    function displaySched($eid="",$date = ""){
        $return = "";
        $wc = "";
        $latestda = date('Y-m-d', strtotime($this->getLatestDateActive($eid, $date)));
        if($date >= $latestda) $wc .= " AND DATE(dateactive) = DATE('$latestda')";
        $query = $this->db->query("SELECT dateactive FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE('$date') $wc ORDER BY dateactive DESC,starttime DESC LIMIT 1;");
        if($query->num_rows() > 0){
            $da = $query->row(0)->dateactive;
            $query = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE('$date') AND dateactive = '$da' GROUP BY starttime,endtime ORDER BY starttime;"); 
        }
        return $query; 
    }

	public function getLatestDateActive($employeeid, $date){
		$query = $this->db->query("SELECT dateactive FROM employee_schedule_history WHERE employeeid = '$employeeid' AND DATE(dateactive) <= DATE('$date') ORDER BY dateactive DESC LIMIT 1");
    	if($query->num_rows() > 0) return $query->row()->dateactive;
    	else return false;
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

    function isHoliday($date=""){
        $sql = $this->db->query("SELECT date_from,date_to FROM code_holiday_calendar WHERE '$date' BETWEEN date_from AND date_to");
        if($sql->num_rows() > 0)  return true;
        else                      return false;
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

	public function getHolidayHalfdayTime($date, $isFirstSched = ""){
		$where_clause = "";
		if($isFirstSched) $where_clause = " AND sched_count = '$isFirstSched'" ;
		$q_holiday = $this->db->query("SELECT * FROM code_holiday_calendar WHERE '$date' BETWEEN date_from AND date_to $where_clause ");
		if($q_holiday->num_rows() > 0) return array($q_holiday->row()->fromtime,$q_holiday->row()->totime);
		else return false;
	}

    function getEmployeeATH($employeeid){
        $designation_list = array();
        $overload_limit = $ath = 0;
        $designation = $this->getEemployeeCurrentData($employeeid, "designation");
        if($designation) $designation_list[] = $designation;
        $sub_designation = $this->getEemployeeCurrentData($employeeid, "sub_designation");
        if($sub_designation){
            foreach (explode(',', $sub_designation) as $key => $value) {
                $designation_list[] = $value;
            }
        }
        foreach ($designation_list as $key => $value) {
            $query = $this->db->query("SELECT * FROM code_designation WHERE code = '$value'");
            if($query->num_rows() > 0){
                if($query->row()->ath > $ath){
                    $ath = $query->row()->ath;
                    $overload_limit = $query->row()->overload_limit;
                }
            }
        }
        if($ath == 0 || $ath == ""){
            $query = $this->db->query("SELECT * FROM code_designation WHERE code = 'ND'");
            if($query->num_rows() > 0){
                if($query->row()->ath > $ath){
                    $ath = $query->row()->ath;
                    $overload_limit = $query->row()->overload_limit;
                }
            }else{
                $ath = 18;
                $overload_limit = 27;
            }
        }
        return array($ath, $overload_limit);
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

    function getLastDayOfWeek($eid=""){
        $return = "";
        $query = $this->db->query("SELECT DISTINCT(dayofweek) FROM employee_schedule_history WHERE employeeid = '$eid' ORDER BY idx DESC LIMIT 1")->result();
       if($query) {
            switch($query[0]->dayofweek) {
                case "M": $return = "Monday"; break;
                case "T": $return = "Thusday"; break;
                case "W": $return = "Wednesday"; break;
                case "TH": $return = "Thursday"; break;
                case "F": $return = "Friday"; break;
                case "S": $return = "Saturday"; break;
                case "SUN": $return = "Sunday"; break;
            }
        }
        
        return $return; 
    }
    
    function getFirstDayOfWeek($eid=""){
        $return = "";
        $query = $this->db->query("SELECT DISTINCT(dayofweek) FROM employee_schedule_history WHERE employeeid = '$eid' ORDER BY idx ASC LIMIT 1")->result();
       
        if($query) {
            switch($query[0]->dayofweek) {
                case "M": $return = "Monday"; break;
                case "T": $return = "Thusday"; break;
                case "W": $return = "Wednesday"; break;
                case "TH": $return = "Thursday"; break;
                case "F": $return = "Friday"; break;
                case "S": $return = "Saturday"; break;
                case "SUN": $return = "Sunday"; break;
            }
        }
        
        return $return; 
    }

    function getindividualdept($eid = ""){
        $query = $this->db->query("SELECT office FROM employee WHERE employeeid='$eid' ");
        if($query->num_rows() > 0) return $query->row(0)->office;
        else return false;
    }

    public function totalWorkhoursPerday($employeeid, $date, $teachingtype = "nonteaching"){
        $t_min = $sched_min = 0;
        $tap_count = 0;
        $logs_data = array();
        $q_timesheet = $this->db->query("SELECT * FROM timesheet WHERE userid = '$employeeid' AND DATE(timein) = '$date' AND otype='CORRECTION' GROUP BY timein, timeout ORDER BY timestamp");
        if($q_timesheet->num_rows() > 0){
            foreach($q_timesheet->result() as $row){
                $continue = true;
                foreach ($logs_data as $key => $value) {
                    if(isset($value["timein"]) && isset($value["timeout"])){
                        if($value["timein"] <= $row->timein || $value["timeout"] >= $row->timein){
                            $continue = false;
                        }
                    }
                }
                if($continue){
                    $timein = $row->timein;
                    $timeout = $row->timeout;
                    $t_min += round(abs(strtotime($timeout) - strtotime($timein)) / 60,2);
                    $tap_count++;
                    $logs_data[] = array("timein" => $timein, "timeout" => $timeout);
                }
                
            }
        }
        $q_timesheet = $this->db->query("SELECT * FROM timesheet WHERE userid = '$employeeid' AND DATE(timein) = '$date' GROUP BY timein, timeout ORDER BY timestamp");
        if($q_timesheet->num_rows() > 0){
            foreach($q_timesheet->result() as $row){
                $continue = true;
                foreach ($logs_data as $key => $value) {
                    if(isset($value["timein"]) && isset($value["timeout"])){
                        if($value["timein"] <= $row->timein || $value["timeout"] >= $row->timein){
                            $continue = false;
                        }
                    }
                }
                if($continue){
                    $timein = $row->timein;
                    $timeout = $row->timeout;
                    $t_min += round(abs(strtotime($timeout) - strtotime($timein)) / 60,2);
                    $tap_count++;
                    $logs_data[] = array("timein" => $timein, "timeout" => $timeout);
                }
                
            }
        }

        if($teachingtype == "nonteaching"){
            $from_time = $t_vacant = $seq = 0;
            $sched = $this->displaySched($employeeid,$date);
            $used_time = array();
            $breaktime = array();
            $sched_count = $sched->num_rows();
            if($sched->num_rows() > 0){
                foreach($sched->result() as $sched_row){
                    $night_shift = $sched_row->night_shift;
                    if($from_time){
                        $seq += 1;
                        $starttime = $sched_row->starttime;
                        $endtime = $sched_row->endtime; 
                        $type  = $sched_row->leclab;
                        $tardy_start = $sched_row->tardy_start;
                        $absent_start = $sched_row->absent_start;
                        $earlydismissal = $sched_row->early_dismissal;
                        $aimsdept = $sched_row->aimsdept;

                        // logtime
                        list($login,$logout,$q,$haslog_forremarks,$used_time) = $this->displayLogTime($employeeid,$date,$starttime,$endtime,"NEW",$seq,$absent_start,$earlydismissal,$used_time);
                        $stime = strtotime($from_time);
                        $etime = strtotime($sched_row->starttime);
                        if($haslog_forremarks) $t_vacant += round(abs($etime - $stime) / 60,2);
                    }

                    $from_time = $sched_row->endtime;
                    if($night_shift == 1){
                        $sched_min += round(abs(strtotime(date("Y-m-d H:i:s",strtotime($date.' '.$sched_row->endtime . ' +1 day'))) - strtotime($date.' '.$sched_row->starttime)) / 60,2);
                    }else{
                        $sched_min += round(abs(strtotime($sched_row->endtime) - strtotime($sched_row->starttime)) / 60,2);
                    }
                }
            }



            if($tap_count>1) $t_vacant = $t_vacant;
            else $t_vacant = 0;


            // echo $t_vacant;

            if($t_vacant > 0 && $sched_count > 0){
                $t_min = $t_min - $t_vacant;
            }
        }
        // echo "<pre>"; print_r($logs_data); 

        // if($t_min > 60 && $teachingtype == "nonteaching" && $tap_count == 1 && $t_min > 480) $t_min -= 60;
        if($t_min > $sched_min) $t_min = $sched_min;
        return $this->time->minutesToHours($t_min);
    }

    function getOfficeDesc($deptid=""){
		$return ="";
		$query = $this->db->query("SELECT * FROM code_office WHERE code = '{$deptid}'")->result();
		foreach($query as $row)
		{
			$return = $row->description;
		}
		return $return;
	}

    function getDeptDesc($deptid=""){
        $return ="";
        $query = $this->db->query("SELECT * FROM code_department WHERE code = '{$deptid}'")->result();
        foreach($query as $row)
        {
            $return = $row->description;
        }
        return $return;
    }

	public function getCampusDescription($campusid, $allcampus=false, $specific=false){
		$return = $allcampus === true ? "All Campus" : $specific ? " " : "No Campus";
		$query = $this->db->query("SELECT * FROM code_campus WHERE code = ".$this->db->escape($campusid)." ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return $return;
	}

    public function getCourseDescriptionByCode($code){
    	$q_coursedesc = $this->db->query("SELECT * FROM tblCourseCategory WHERE CODE = '$code' ");
    	if($q_coursedesc->num_rows() > 0) return $q_coursedesc->row()->DESCRIPTION;
    	else return "";
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
}