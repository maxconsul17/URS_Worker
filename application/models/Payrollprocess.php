
<?php 
/**
 * @author Angelica Arangco
 * @copyright 2017
 *
 * This model is an extension to models\payroll.php
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Payrollprocess extends CI_Model {
	
	///< construct an associative array list from computed table string, arr['key'] = $value;
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


	///< construct an associative array list from stdclass object, $arr['key'] = $value;
	function constructArrayListFromStdClass($res='',$key='',$value=''){
	    $arr = array();
	    if($res->num_rows() > 0){
	        foreach ($res->result() as $k => $row) {
	            $arr[$row->$key] = array('description'=>$row->$value,'hasData'=>0);
	        }
	    }
	    return $arr;
	}

	function processPayrollSummary($emplist=array(),$sdate='',$edate='',$schedule='',$quarter='',$recompute=false,$payroll_cutoff_id=''){

		$recomputed_emp_payroll = 0;
		$workdays = 0;
		$workhours_lec = $workhours_lab = $workhours_admin = "";

		//< initialize needed info ---------------------------------------------------
		$info    = $arr_income_config = $arr_income_adj_config = $arr_incomeoth_config = $arr_deduc_config = $arr_fixeddeduc_config = $arr_loan_config = array();

		///< ------------------------------ income config ------------------------------------------------------------
		$income_config_q = $this->payroll->displayIncome();
		$arr_income_config = $this->constructArrayListFromStdClass($income_config_q,'id','description');
		$arr_income_adj_config = $arr_income_config;
		$arr_income_adj_config['SALARY'] = array('description'=>'SALARY','hasData'=>0);

		///< ------------------------------ incomeoth config ---------------------------------------------------------------
		$incomeoth_config_q = $this->payroll->displayIncomeOth();
		$arr_incomeoth_config = $this->constructArrayListFromStdClass($incomeoth_config_q,'id','description');

		///< ------------------------------ fixed deduction config ----------------------------------------------------
		$fixeddeduc_config_q = $this->db->query("SELECT code_deduction,description FROM deductions");
		$arr_fixeddeduc_config = $this->constructArrayListFromStdClass($fixeddeduc_config_q,'code_deduction','description');


		///< ------------------------------ deduction config ----------------------------------------------------------
		$deduction_config_q = $this->payroll->displayDeduction();
		$arr_deduc_config = $this->constructArrayListFromStdClass($deduction_config_q,'id','description');
		$arr_deduc_config_arithmetic = $this->constructArrayListFromStdClass($deduction_config_q,'id','arithmetic');


		///< ------------------------------ loan config ---------------------------------------------------------------
		$loan_config_q = $this->payroll->displayLoan();
		$arr_loan_config = $this->constructArrayListFromStdClass($loan_config_q,'id','description');

		if($recompute === true){
			foreach($emplist as $row){
				$eid = $row->employeeid;
				$this->db->query("DELETE FROM payroll_computed_table WHERE cutoffstart='$sdate' AND cutoffend='$edate' AND schedule='$schedule' AND quarter='$quarter' AND employeeid='$eid' AND status='PENDING'");
			}
		}

		foreach ($emplist as $row) {
			// echo "<pre>"; print_r($row); die;
			$perdept_amt_arr = array();
			$eid = $row->employeeid;
				
			$check_saved_q = $this->getPayrollSummary('SAVED',$sdate,$edate,$schedule,$quarter,$eid,TRUE,'PROCESSED');

			if(!$check_saved_q){

				$info[$eid]['income'] = $info[$eid]['income_adj'] = $info[$eid]['deduction'] = $info[$eid]['fixeddeduc'] = $info[$eid]['loan'] = array();

				$info[$eid]['fullname'] 	=  isset($row->fullname) ? $row->fullname : '';
				$info[$eid]['deptid'] = isset($row->deptid) ? $row->deptid : '';
				$info[$eid]['office'] = isset($row->office) ? $row->office : '';
				// $info[$eid]['pera'] = isset($row->pera) ? $row->pera : '';

				///< check for pending computation, if true - display directly, else compute payroll first
				// $res = $this->getPayrollSummary('PENDING',$sdate,$edate,$schedule,$quarter,$eid);
				// if($res->num_rows() > 0){
				// 	list($info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config) 
				// 		= $this->constructPayrollComputedInfo($res,$info,$eid,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config);
				// }else{ ///< compute
				// 	list($info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config) 
				// 		= $this->computeNewPayrollInfo($row,$schedule,$quarter,$sdate,$edate,$payroll_cutoff_id,$info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_deduc_config_arithmetic,$arr_loan_config);
				// }

				list($info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config) 
						= $this->computeNewPayrollInfo($row,$schedule,$quarter,$sdate,$edate,$payroll_cutoff_id,$info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_deduc_config_arithmetic,$arr_loan_config); 

			} ///< end if SAVED

			

			$recomputed_emp_payroll += 1;
            $emplist_total_payroll = sizeof($emplist);

            $this->session->set_userdata('emplist_total_payroll', $emplist_total_payroll);
            $this->session->set_userdata('recomputed_emp_payroll', $recomputed_emp_payroll);

		} //end loop emplist

		$this->session->unset_userdata('emplist_total_payroll');
        $this->session->unset_userdata('recomputed_emp_payroll');
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

	function constructPayrollComputedInfo($res,$info=array(),$eid='',$arr_income_config=array(),$arr_income_adj_config=array(),$arr_fixeddeduc_config=array(),$arr_deduc_config=array(),$arr_loan_config=array()){
		$res = $res->row(0);

		$info[$eid]['base_id'] 		= $res->id;

		$info[$eid]['tardy'] 		= $res->tardy;
		$info[$eid]['absents'] 		= $res->absents;
		$info[$eid]['whtax'] 		= $res->withholdingtax;
		$info[$eid]['salary'] 		= $res->salary;
		$info[$eid]['teaching_pay'] 		= $res->teaching_pay;
		$info[$eid]['overtime'] 	= $res->overtime;
		$info[$eid]['substitute'] 	= $res->substitute;
		// echo "<pre>"; print_r($info); die;
		//<!--NET BASIC PAY-->
		$info[$eid]['netbasicpay'] 	= $res->netbasicpay;;
		$info[$eid]['grosspay']    	= $res->gross;
		$info[$eid]['netpay']    	= $res->net;

		$info[$eid]['isHold']    	= $res->isHold;
		$info[$eid]['provident_premium']    	= $res->provident_premium;

		$income_adj_arr 				= $this->constructArrayListFromComputedTable($res->income_adj);
		$info[$eid]['income_adj'] = $income_adj_arr;
		foreach ($income_adj_arr as $k => $v) {$arr_income_adj_config[$k]['hasData'] = 1;}
		
		//< income
		$income_arr 				= $this->constructArrayListFromComputedTable($res->income);
		$info[$eid]['income'] = $income_arr;
		foreach ($income_arr as $k => $v) {$arr_income_config[$k]['hasData'] = 1;}

		///< fixed deduc
        $fixeddeduc_arr = $this->constructArrayListFromComputedTable($res->fixeddeduc);
        $info[$eid]['fixeddeduc'] = $fixeddeduc_arr;
        foreach ($fixeddeduc_arr as $k => $v) {$arr_fixeddeduc_config[$k]['hasData'] = 1;}

        ///< deduc
        $deduc_arr = $this->constructArrayListFromComputedTable($res->otherdeduc);
        $info[$eid]['deduction'] = $deduc_arr;
        foreach ($deduc_arr as $k => $v) {$arr_deduc_config[$k]['hasData'] = 1;}

        ///< loan
        $loan_arr = $this->constructArrayListFromComputedTable($res->loan);
        $info[$eid]['loan'] = $loan_arr;
        foreach ($loan_arr as $k => $v) {$arr_loan_config[$k]['hasData'] = 1;}

        return array($info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config);
	}

	function isEmployeeTeachingOnly($employeeid) {
		$schedule = $this->db->query("SELECT employeeid FROM employee_schedule WHERE employeeid='{$employeeid}' AND leclab<>'LEC'");
		return $schedule->num_rows() == 0;
	}

	function computeNewPayrollInfo($row,$schedule,$quarter,$sdate,$edate,$payroll_cutoff_id,$info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_deduc_config_arithmetic,$arr_loan_config){
		$this->load->model('payrollcomputation','comp');
		$this->load->model('income');
		$perdept_amt_arr = array();
		$workdays =	$absentdays = 0;
		$workhours_lec = $workhours_lab = $workhours_admin = "";
		$eid 		= $row->employeeid;
		$tnt 		= $row->teachingtype;
		$daily_hours = $tnt == "teaching" ? 7 : 8;
		$employmentstat = $row->employmentstat;
		$regpay 	=  $row->regpay;
		// list($regpay, $daily) = $this->getEmployeeSalaryRate($regpay, $daily, $eid, $sdate);
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
		$is_trelated = $this->employee->isTeachingRelated($eid);
		if($tnt == 'teaching'){
			$perdept_salary = $this->comp->getPerdeptSalaryHistory($eid,$sdate);

			list($tardy_amount,$absent_amount,$workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$perdept_amt_arr,$hold_status,$x,$x,$forFinalPay,$conf_base_id,$excess_min, $vl_balance, $t_overload, $hasZeroRate) = $this->comp->getTardyAbsentSummaryTeaching($eid,$tnt,$schedule,$quarter,$sdate,$edate,$hourly,$lechour,$labhour,$rlehour,$perdept_salary,false,$regpay, $employmentstat, "", $absent_rate, $daily);
			list($info[$eid]['salary'], $info[$eid]['teaching_pay'], $info[$eid]['parrtime_pay'], $info[$eid]['overload_pay']) 	= $this->comp->computeTeachingCutoffSalary($workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$hourly,$lechour,$labhour,$rlehour,$fixedday,$regpay,$perdept_amt_arr,$hold_status,$excess_min,$has_bdayleave,$minimum_wage);
				if($hasZeroRate > 0) $info[$eid]['teaching_pay']= $info[$eid]['salary'] = $info[$eid]['parrtime_pay'] =  0;
			

			list($project_hol_pay, $sub_hol_pay) = $this->comp->getProjectHolidayPay($payroll_cutoff_id,$lechour,$labhour,$rlehour,$eid,$sdate);
			// $info[$eid]["salary"] -= $sub_hol_pay;

			/*remove absent for teaching, condition ni olfu HYP-3937*/
			// $absent_amount = 0;

			$this->income->saveHolidayPay($project_hol_pay, $eid, $sdate, $edate, $quarter);			$info[$eid]['substitute'] = $this->comp->computeSubstitute($eid,$conf_base_id);

		}else{
			if(!$is_trelated){
				list($tardy_amount,$absent_amount,$workdays,$x,$x,$conf_base_id, $isFinal, $vl_balance) = $this->comp->getTardyAbsentSummaryNT($eid,$tnt,$schedule,$quarter,$sdate,$edate,$hourly,false,$daily, $monthlySalary, $sdate, $is_pera, $fixedday, $daily2);
				$info[$eid]['salary'] 	= $this->comp->computeNTCutoffSalary($workdays,$fixedday,$regpay,$daily,$has_bdayleave,$minimum_wage, $daily2);

				$info[$eid]['substitute'] = 0;
			}else{
				$perdept_salary = $this->comp->getPerdeptSalaryHistory($eid,$sdate);
				list($tardy_amount,$absent_amount,$workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$perdept_amt_arr,$hold_status,$x,$x,$forFinalPay,$conf_base_id,$excess_min, $vl_balance, $t_overload, $hasZeroRate) = $this->comp->getTardyAbsentSummaryTeaching($eid,$tnt,$schedule,$quarter,$sdate,$edate,$hourly,$lechour,$labhour,$rlehour,$perdept_salary, $is_pera,$regpay, $employmentstat, $is_trelated, $absent_rate, $daily);

				list($info[$eid]['salary'], $info[$eid]['teaching_pay'], $info[$eid]['parrtime_pay'], $info[$eid]['overload_pay']) 	= $this->comp->computeTeachingCutoffSalary($workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$hourly,$lechour,$labhour,$rlehour,$fixedday,$regpay,$perdept_amt_arr,$hold_status,0,false,0, $is_trelated);

				if($hasZeroRate > 0) $info[$eid]['teaching_pay'] = $info[$eid]['salary'] = $info[$eid]['parrtime_pay'] = $info[$eid]['overload_pay'] = 0;

				list($project_hol_pay, $sub_hol_pay) = $this->comp->getProjectHolidayPay($payroll_cutoff_id,$lechour,$labhour,$rlehour,$eid,$sdate);
				// $info[$eid]["salary"] -= $sub_hol_pay;

				$this->income->saveHolidayPay($project_hol_pay, $eid, $sdate, $edate, $quarter);

			}
		}
		
		$info[$eid]["pera"] = 0;
		$allowed_pera = $this->extensions->getEmployeePERA($eid);
		if ($allowed_pera) {
			$info[$eid]["pera"] = 2000;
		}


		///< pag wala attendance - wala salary,tardy,absent pero papasok pa rin sa payroll - maiiwan mga income nya (DOUBLE CHECKING)
		if( !$this->hasAttendanceConfirmed($tnt,array('employeeid'=>$eid,'status'=>'PROCESSED','forcutoff'=>'1','payroll_cutoffstart'=>$sdate,'payroll_cutoffend'=>$edate,'quarter'=>$quarter), $is_trelated )){
			$info[$eid]['salary'] = $tardy_amount = $absent_amount = 0;
			$perdept_amt_arr = array();
		}

		if($quarter == 2){
			// list($info[$eid]['overtime'],$ot_det) = $this->comp->computeOvertime2($eid,$tnt,$hourly,$conf_base_id,$employmentstat);
			list($info[$eid]['overtime'],$ot_det) = $this->comp->computeOvertime3($eid,$tnt,$hourly,$conf_base_id,$employmentstat,$monthlySalary,$sdate);
		}else{
			$info[$eid]['overtime'] = 0;
			$ot_det = array();
		}
		// $info[$eid]['overtime'] = $this->comp->computeOvertime($eid,$tnt,$schedule,$quarter,$sdate,$edate,$hourly);  ///< TO DO : INCLUDE OVERTIME IN COMPUTATIONS (income, tax, gross pay , etc)
		
		/*check cutoff if no late and undertime*/
		$is_flexi = $this->attendance->isFlexiNoHours($eid);
		if($this->validateDTRCutoff($sdate, $edate, $quarter) || $is_flexi > 0) $tardy_amount = $absent_amount = 0;

		$info[$eid]['tardy'] 		= $tardy_amount;
		$info[$eid]['absents'] 		= $absent_amount;
		$info[$eid]['overload'] 	= $info[$eid]['overload_pay'];

		///<  compute and save other income
		// $arr_adj_to_add = $this->comp->computeOtherIncomeAdj($eid,$payroll_cutoff_id);
		$this->comp->computeEmployeeOtherIncome($eid,$sdate,$edate,$tnt,$schedule,$quarter,$perdept_salary,$regpay);
		if(!$fixedday && $tnt=="teaching") $this->comp->computeCOLAIncome($eid,$sdate,$edate,$schedule,$quarter,$workdays,$absentdays);
		// $this->comp->computeLongevity($eid,$sdate,$edate,$tnt,$schedule,$quarter);
		///< income
		list($arr_income_config,$info[$eid]['income'],$totalincome,$str_income, $str_monetize_id) = $this->comp->computeEmployeeIncome($eid,$schedule,$quarter,$sdate,$edate,$arr_income_config,$payroll_cutoff_id, $monthlySalary);
		// $getTotalNotIncludedInGrosspay = $this->getTotalNotIncludedInGrosspay($info[$eid]['income']);
		$getTotalNotIncludedInGrosspay = 0;
		///< income adjustment
		list($arr_income_adj_config,$info[$eid]['income_adj'],$totalincome,$str_income_adj) = $this->comp->computeEmployeeIncomeAdj($eid,$schedule,$quarter,$sdate,$edate,$arr_income_adj_config,$totalincome,$payroll_cutoff_id);
		
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
		list($arr_fixeddeduc_config,$info[$eid]['fixeddeduc'],$totalfix,$str_fixeddeduc,$ee_er) = $this->comp->computeEmployeeFixedDeduc($eid,$schedule,$quarter,$sdate,$edate,$arr_fixeddeduc_config,$info[$eid],$prevSalary,$prevGrosspay,$getTotalNotIncludedInGrosspay,$info[$eid]['salary']);

		///< loan
		list($arr_loan_config,$info[$eid]['loan'],$totalloan,$str_loan) = $this->comp->computeEmployeeLoan($eid,$schedule,$quarter,$sdate,$edate,$arr_loan_config);

		//<!--NET BASIC PAY-->
		// $info[$eid]['netbasicpay'] = ($info[$eid]['salary']  - ($info[$eid]['absents']+ $info[$eid]['tardy']));
		$info[$eid]['netbasicpay'] = ($info[$eid]['salary'] + $info[$eid]['teaching_pay'] + $info[$eid]['parrtime_pay'] + $info[$eid]['pera'] - ($info[$eid]['absents']+ $info[$eid]['tardy']));

		if($isFinal){
			list($_13th_month, $employee_benefits) = $this->income->compute13thMonthPay_2($eid,date('Y',strtotime($sdate)),$sdate,$edate,$info[$eid]['netbasicpay'],$info[$eid]['income'], true, $regpay);
			if($_13th_month > 0) $this->income->saveEmployeeOtherIncome($eid,$sdate,$edate,'5',$_13th_month,$schedule,$quarter);
			if($employee_benefits > 0) $this->income->saveEmployeeOtherIncome($eid,$sdate,$edate,'37',$employee_benefits,$schedule,$quarter);
			///< income (RECOMPUTE TO INCLUDE 13TH MONTH PAY)
			list($arr_income_config,$info[$eid]['income'],$totalincome,$str_income, $str_monetize_id) = $this->comp->computeEmployeeIncome($eid,$schedule,$quarter,$sdate,$edate,$arr_income_config,$payroll_cutoff_id, $monthlySalary);

		}

		// echo "<pre>"; print_r($arr_deduc_config); die;

		///< deduction
		list($arr_deduc_config,$info[$eid]['deduction'],$total_deducSub,$total_deducAdd,$str_deduc) = $this->comp->computeEmployeeDeduction($eid,$schedule,$quarter,$sdate,$edate,$arr_deduc_config,$arr_deduc_config_arithmetic);
		///< TAX COMPUTATION
		$wh_tax = $this->comp->getExistingWithholdingTax($eid, $edate);
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
				$info[$eid]['whtax'] = $this->comp->taxComputation($eid, $info[$eid]['fixeddeduc'], $info[$eid]['deduction'], $str_fixeddeduc, $info[$eid]['salary'], $info[$eid]["provident_premium"], $basic_personal_exception, $year, $sdate, $edate);
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
			$ctoDaily = round($this->attcompute->exp_time($cto) / 28800, 0);
			$ctoExcess = $this->attcompute->exp_time($cto) % 28800;
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

	function getUseCTOApplications($eid, $sdate, $edate){
		$cto = 0;
		$query = $this->db->query("SELECT * FROM employee_cto_usage WHERE employeeid = '$eid' AND (date_applied BETWEEN '$sdate' AND '$edate') AND app_status = 'APPROVED' AND credited = '0'");
		if($query->num_rows() > 0){
			foreach ($query->result() as $key => $value) {
				$cto += $this->attcompute->exp_time($value->total);
			}

			$cto = $this->attcompute->sec_to_hm($cto);
		}
		return $cto;
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


	///< PENDING STATUS
	function savePayrollCutoffSummaryDraft($data=array(),$data_oth=array()){
		$this->load->model('utils');
		$data['addedby']   = $this->session->userdata('username');
		$base_id = $this->utils->insertSingleTblData('payroll_computed_table',$data);
		if($base_id){

			if(sizeof($data_oth['ee_er']) > 0){
				foreach ($data_oth['ee_er'] as $code => $amt) {
					$amt['EE'] = round($amt['EE'],2);
					$amt['EC'] = round($amt['EC'],2);
					$amt['ER'] = round($amt['ER'],2);
					$this->utils->insertSingleTblData('payroll_computed_ee_er',array('base_id'=>$base_id,'code_deduction'=>$code,'EE'=>$amt['EE'],'EC'=>$amt['EC'],'ER'=>$amt['ER'],'provident_er'=>$amt['provident_er']));
				}
			}
			

			if(sizeof($data_oth['perdept_amt_arr']) > 0){ ///< perdept amount details saving
				foreach ($data_oth['perdept_amt_arr'] as $aimsdept => $classification_arr) {
					foreach ($classification_arr as $classification => $leclab_arr) {
						foreach ($leclab_arr as $type => $amt) {
							$this->utils->insertSingleTblData('payroll_computed_perdept_detail',array('base_id'=>$base_id,'type'=>$type,'aimsdept'=>$aimsdept,'work_amount'=>$amt['work_amount'],'late_amount'=>$amt['late_amount'],'deduc_amount'=>$amt['deduc_amount'],'classification'=>$classification));
						}
					}
				}
			}

			if(sizeof($data_oth['ot_det']) > 0){
				foreach ($data_oth['ot_det'] as $att_baseid => $amt) {
					$amt = round($amt,2);
					$this->utils->insertSingleTblData('payroll_computed_overtime',array('base_id'=>$base_id,'att_baseid'=>$att_baseid,'amount'=>$amt));
				}
			}

		} //< end main if

		return $base_id;
	}

	///< SAVED STATUS
	function savePayrollCutoffSummary($empid = "",$cutoffstart="", $cutoffend="", $schedule = "",$quarter = "",$status="SAVED",$bank='', $fund_type=''){
		$success = false;
		$update_res = $this->db->query("UPDATE payroll_computed_table SET  fund_type='$fund_type',status='$status'
										WHERE employeeid='$empid' AND schedule='$schedule' AND cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND quarter='$quarter'");
		if($update_res) $success = true;
		return $success;
	}

	function saveEmpLoanPayment($pct_id, $employeeid, $cutoffstart, $cutoffend, $schedule, $quarter, $loans_list){
		$this->load->model('loan');
		$arr_loan = array();
		if($loans_list){
			foreach (explode("/", $loans_list) as $loans) {
				list($id, $amount) = explode("=", $loans);

				$arr_loan[$id] = $amount;
			}
		}

		if(count($arr_loan) > 0){
			foreach ($arr_loan as $code_loan => $loan_amount) {
				$q_emp_loan = $this->loan->getEmployeeLoanPayment($employeeid, $code_loan, $cutoffstart, $cutoffend, $schedule, $quarter);
				
				foreach ($q_emp_loan as $row) {
					$base_id = $row->id;

					$this->loan->processEmployeePayment($base_id, $loan_amount, $pct_id, $employeeid, $code_loan);
				}
				
			}
		}
	}

	///< PROCESSED STATUS
	function finalizePayrollCutoffSummary($empid = "",$cutoffstart="", $cutoffend="", $schedule = "",$quarter = "", $refno=""){



		$user = $this->session->userdata('username');
		$update_res = $this->db->query("UPDATE payroll_computed_table SET status='PROCESSED', editedby = '$user', date_processed = CURRENT_TIMESTAMP, ref_no = '$refno' 
										WHERE employeeid='$empid' AND schedule='$schedule' AND cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND quarter='$quarter'");

		// update status of vl deduc late trail
		$this->db->query("UPDATE vl_deduc_late_trail SET status = 'PROCESSED' WHERE employeeid = '$empid' AND payroll_date = '$cutoffend' AND status = 'PENDING'");

		$query = $this->db->query("SELECT * FROM employee_cto_usage WHERE employeeid = '$empid' AND (date_applied BETWEEN '$cutoffstart' AND '$cutoffend') AND app_status = 'APPROVED' AND credited = '0'");
		if($query->num_rows() > 0){
			foreach ($query->result() as $key => $value) {
				$cto_id = $value->id;
				$this->db->query("UPDATE employee_cto_usage SET credited ='1', app_status = 'AVAILED' WHERE id = '$cto_id'");
			}
		}

		$monetization_query = $this->db->query("SELECT * FROM monetize_app WHERE applied_by = '$empid' AND date_applied <= '$cutoffend' AND app_status = 'APPROVED' AND credited = '0'");
		if($monetization_query->num_rows() > 0){
			foreach ($monetization_query->result() as $key => $value) {
				$monetize_id = $value->id;
				$this->db->query("UPDATE monetize_app SET credited ='1', app_status = 'AVAILED' WHERE id = '$monetize_id'");
			}
		}


		$vl_balance = $this->db->query("SELECT vl_balance FROM payroll_computed_table WHERE employeeid='$empid' AND schedule='$schedule' AND cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND quarter='$quarter'")->row()->vl_balance;
		if($vl_balance >= 0){
			$this->load->model("leave_application");
			list($haveCredits,$curr_balance,$curr_credit,$curr_availed) = $this->leave_application->checkLeaveBalance($empid,"VL",$cutoffstart,$cutoffend);
			$vl_availed = $curr_credit - $vl_balance;
			$this->db->query("UPDATE employee_leave_credit SET balance = '$vl_balance', avail = '$vl_availed' WHERE employeeid = '$empid' AND '$cutoffend' BETWEEN dfrom AND dto AND leavetype = 'VL'");
		}
		$success = false;

		if($update_res){
			$sel_res = $this->db->query("SELECT * FROM payroll_computed_table WHERE employeeid='$empid' AND schedule='$schedule' AND cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND quarter='$quarter'");
			if($sel_res->num_rows() > 0){

				$pct_id 		= $sel_res->row()->id;
				$loans 			= $sel_res->row()->loan;
				$income 		= $sel_res->row()->income;
				$income_adj 		= $sel_res->row()->income_adj;
				$deductfixed 	= $sel_res->row()->fixeddeduc;
				$deductothers 	= $sel_res->row()->otherdeduc;
				$fixeddeduc_arr = array_sum($this->constructArrayListFromComputedTable($sel_res->row()->fixeddeduc));
				$grosssalary 	=((int) $sel_res->row()->salary + (int) $sel_res->row()->income) - ((int) $sel_res->row()->otherdeduc+(int) $sel_res->row()->loan + $fixeddeduc_arr);
				$netsalary 		=((int) $sel_res->row()->salary + (int) $sel_res->row()->income) - (((int) $sel_res->row()->absents + (int) $sel_res->row()->tardy));

				$this->saveEmpLoanPayment($pct_id, $empid, $cutoffstart, $cutoffend, $schedule, $quarter, $loans);
				

				$query = $this->db->query("INSERT INTO payroll_computed_table_history 
				                                    (employeeid,cutoffstart,cutoffend,schedule,quarter,salary,income,overtime,withholdingtax,fixeddeduc,otherdeduc,loan,tardy,absents,addedby) 
				                            (SELECT employeeid,cutoffstart,cutoffend,schedule,quarter,salary,income,overtime,withholdingtax,fixeddeduc,otherdeduc,loan,tardy,absents,'$user'
				                            FROM payroll_computed_table WHERE employeeid='$empid' AND schedule='$schedule' AND cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND quarter='$quarter')
				                            ");


				$uptloan      =   explode("/",$loans);
				$uptincome    =   explode("/",$income);
				$uptincome_adj    =   explode("/",$income_adj);
				$uptcontri    =   explode("/",$deductfixed);
				$uptothded    =   explode("/",$deductothers);

				$this->finalizeLoan($empid,$schedule,$quarter,$cutoffstart,$cutoffend,$loans,$uptloan,$user);
				$this->finalizeIncome($empid,$schedule,$quarter,$cutoffstart,$cutoffend,$income,$uptincome,$user);
				$this->finalizeIncomeAdj($empid,$schedule,$quarter,$cutoffstart,$cutoffend,$income_adj,$uptincome_adj,$user);
				$this->finalizeFixedDeduction($empid,$schedule,$quarter,$cutoffstart,$cutoffend,$deductfixed,$uptcontri,$user);
				$this->finalizeOtherDeduction($empid,$schedule,$quarter,$cutoffstart,$cutoffend,$deductothers,$uptothded,$user);

				if($query) $success = true;

			}
		}

		return $success;
	}

	function finalizeLoan($eid='',$schedule = "",$quarter = "",$sdate = "",$edate = "",$loans='',$uptloan=array(),$user=''){
        if(count($uptloan) > 0 && !empty($loans)){
            for($x = 0; $x<count($uptloan); $x++){
                $code = explode("=",$uptloan[$x]);
                $qloan = $this->db->query("SELECT nocutoff,amount,famount FROM employee_loan WHERE employeeid='$eid' AND code_loan='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
                if($qloan->num_rows() > 0){
                    $amount = $qloan->row(0)->amount; 
                    $famount = $qloan->row(0)->famount; 
                    $nocutoff = $qloan->row(0)->nocutoff;
                    $this->load->model("loan");
                	$skip_loan = $this->loan->checkIfSkipInLoanPayment($eid, $code[0]);
                	$mode = "CUTOFF";
                	if($skip_loan){
                		$mode = "HOLD";
                	}else{
                    	$nocutoff = $qloan->row(0)->nocutoff-1; 
                	}

                    if($nocutoff >= 0){
                        $qloan = $this->db->query("UPDATE employee_loan SET nocutoff='$nocutoff' WHERE employeeid='$eid' AND code_loan='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
                        $ploan = $this->db->query("INSERT INTO payroll_process_loan 
                                                            (employeeid,code_loan,cutoffstart,cutoffend,amount,schedule,cutoff_period,user) 
                                                    VALUES  ('$eid','".$code[0]."','$sdate','$edate','".$code[1]."','$schedule','$quarter','$user')
                                                    ");
                    
						$hloan = $this->db->query("SELECT * FROM employee_loan_history WHERE employeeid = '".$eid."' AND code_loan = '".$code[0]."' AND schedule='$schedule' ORDER BY cutoffstart DESC LIMIT 1");
						if($hloan->num_rows() > 0){
							if($nocutoff != 0){ 
								$balance = $hloan->row(0)->remainingBalance - $amount;
								$this->db->query("INSERT INTO employee_loan_history (employeeid,code_loan,cutoffstart,cutoffend,startBalance,amount,remainingBalance,schedule,cutoff_period,mode,user)
								VALUES('".$eid."','".$code[0]."','$sdate','$edate',".$hloan->row(0)->remainingBalance.",".$amount.",".$balance.",'".$schedule."','".$quarter."','CUTOFF','".$user."')");
							}
							else {
								$balance = $hloan->row(0)->remainingBalance - $famount;
								$this->db->query("INSERT INTO employee_loan_history (employeeid,code_loan,cutoffstart,cutoffend,startBalance,amount,remainingBalance,schedule,cutoff_period,mode,user)
								VALUES('".$eid."','".$code[0]."','$sdate','$edate',".$hloan->row(0)->remainingBalance.",".$famount.",".$balance.",'".$schedule."','".$quarter."','CUTOFF','".$user."')");
							}
						}
					}						
                }
            }
        }
	}

	function finalizeIncome($eid='',$schedule = "",$quarter = "",$sdate = "",$edate = "",$income='',$uptincome=array(),$user=''){
		if(count($uptincome) > 0 && !empty($income)){
		    for($x = 0; $x<count($uptincome); $x++){
		        $code = explode("=",$uptincome[$x]);
		        $qincome = $this->db->query("SELECT nocutoff FROM employee_income WHERE employeeid='$eid' AND code_income='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
		        if($qincome->num_rows() > 0){
		            $nocutoff = $qincome->row(0)->nocutoff-1; 
		            if($nocutoff >= 0){
		                $qincome = $this->db->query("UPDATE employee_income SET nocutoff='$nocutoff' WHERE employeeid='$eid' AND code_income='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
		                $pincome = $this->db->query("INSERT INTO payroll_process_income 
		                                                    (employeeid,code_income,cutoffstart,cutoffend,amount,schedule,cutoff_period,remainingCutoff,user) 
		                                            VALUES  ('$eid','".$code[0]."','$sdate','$edate','".$code[1]."','$schedule','$quarter','$nocutoff','$user')
		                                            ");
		            } 
		        }
				$remainingCutoff = $this->db->query("SELECT * FROM employee_income WHERE employeeid = '$eid' AND code_income = '{$code[0]}' ");
				if($remainingCutoff->num_rows() > 0){
					$remainingCutoff = $remainingCutoff->row()->nocutoff;
					// if($remainingCutoff == 0) $this->db->query("DELETE FROM employee_income WHERE employeeid = '$eid' AND code_income = '{$code[0]}' ");
				}
		    }
		}

		
	}

	function finalizeIncomeAdj($eid='',$schedule = "",$quarter = "",$sdate = "",$edate = "",$income='',$uptincome=array(),$user=''){
		if(count($uptincome) > 0 && !empty($income)){
		    for($x = 0; $x<count($uptincome); $x++){
		        $code = explode("=",$uptincome[$x]);
		        $qincome = $this->db->query("SELECT nocutoff FROM employee_income_adj WHERE employeeid='$eid' AND code_income='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
		        if($qincome->num_rows() > 0){
		            $nocutoff = $qincome->row(0)->nocutoff-1; 
		            if($nocutoff >= 0){
		                $qincome = $this->db->query("UPDATE employee_income_adj SET nocutoff='$nocutoff' WHERE employeeid='$eid' AND code_income='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
		                $pincome = $this->db->query("INSERT INTO payroll_process_income_adj 
		                                                    (employeeid,code_income,cutoffstart,cutoffend,amount,schedule,cutoff_period,user) 
		                                            VALUES  ('$eid','".$code[0]."','$sdate','$edate','".$code[1]."','$schedule','$quarter','$user')
		                                            ");
		            } 
		        }
		    	$remainingCutoff = $this->db->query("SELECT * FROM employee_income_adj WHERE employeeid = '$eid' AND code_income = '{$code[0]}' ")->row()->nocutoff;
				if($remainingCutoff == 0) $this->db->query("DELETE FROM employee_income_adj WHERE employeeid = '$eid' AND code_income = '{$code[0]}' ");
		    }
		}
	}

	function finalizeFixedDeduction($eid='',$schedule = "",$quarter = "",$sdate = "",$edate = "",$deductfixed='',$uptcontri=array(),$user=''){
		if(count($uptcontri) > 0 && !empty($deductfixed)){
		    for($x = 0; $x<count($uptcontri); $x++){
		        $code = explode("=",$uptcontri[$x]);
		        list($tcontri,$er,$ec)   =  $this->payroll->payroll_collection_contribution($code[1]);
		            $pcontri = $this->db->query("INSERT INTO payroll_process_contribution 
		                                                    (employeeid,code_deduct,cutoffstart,cutoffend,amount,schedule,cutoff_period,user) 
		                                            VALUES  ('$eid','".strtoupper($code[0])."','$sdate','$edate','".$code[1]."','$schedule','$quarter','$user')
		                                        "); 
		                       $this->db->query("INSERT INTO payroll_process_contribution_collection 
		                                                    (employeeid,code_deduct,cutoffstart,cutoffend,amount,schedule,cutoff_period,user,ec,amounter,amounttotal) 
		                                            VALUES  ('$eid','".strtoupper($code[0])."','$sdate','$edate','".$code[1]."','$schedule','$quarter','$user','$ec','$er','$tcontri')
		                                        "); 
		                                        
		    }
		}
	}

	function finalizeOtherDeduction($eid='',$schedule = "",$quarter = "",$sdate = "",$edate = "",$deductothers='',$uptothded=array(),$user=''){
		if(count($uptothded) > 0 && !empty($deductothers)){
            for($x = 0; $x<count($uptothded); $x++){
                $code = explode("=",$uptothded[$x]);
                $qincome = $this->db->query("SELECT nocutoff FROM employee_deduction WHERE employeeid='$eid' AND code_deduction='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
                if($qincome->num_rows() > 0){
                $nocutoff = $qincome->row(0)->nocutoff-1; 
                    if($nocutoff >= 0){
                        $qincome = $this->db->query("UPDATE employee_deduction SET nocutoff='$nocutoff' WHERE employeeid='$eid' AND code_deduction='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");                                        
                        $pcontri = $this->db->query("INSERT INTO payroll_process_otherdeduct 
                                                                (employeeid,code_deduct,cutoffstart,cutoffend,amount,schedule,cutoff_period,nocutoff,user) 
                                                        VALUES  ('$eid','".strtoupper($code[0])."','$sdate','$edate','".$code[1]."','$schedule','$quarter','$nocutoff','$user')
                                                    "); 
                    }             
                }                                                                                           
            	$remainingCutoff = $this->db->query("SELECT * FROM employee_deduction WHERE employeeid = '$eid' AND code_deduction = '{$code[0]}' ")->row()->nocutoff;
				if($remainingCutoff == 0) $this->db->query("DELETE FROM employee_deduction WHERE employeeid = '$eid' AND code_deduction = '{$code[0]}' ");
            }
        }
	}



	function getProcessedPayrollSummary($emplist=array(), $sdate='',$edate='',$schedule='',$quarter='',$status='PROCESSED',$bank='',$refno="", $fund_type=""){
		//< initialize needed info ---------------------------------------------------
		$arr_info    = $arr_income_config = $arr_incomeoth_config = $arr_deduc_config = $arr_fixeddeduc_config = $arr_loan_config = array();

		///< ------------------------------ income config ------------------------------------------------------------
		$income_config_q = $this->payroll->displayIncome();
		$arr_income_config = $this->constructArrayListFromStdClass($income_config_q,'id','description');

		$arr_income_adj_config = $arr_income_config;
		$arr_income_adj_config['SALARY'] = array('description'=>'SALARY','hasData'=>0);

		///< ------------------------------ incomeoth config ---------------------------------------------------------------
		$incomeoth_config_q = $this->payroll->displayIncomeOth();
		$arr_incomeoth_config = $this->constructArrayListFromStdClass($incomeoth_config_q,'id','description');

		///< ------------------------------ fixed deduction config ----------------------------------------------------
		$fixeddeduc_config_q = $this->db->query("SELECT code_deduction,description FROM deductions");
		$arr_fixeddeduc_config = $this->constructArrayListFromStdClass($fixeddeduc_config_q,'code_deduction','description');


		///< ------------------------------ deduction config ----------------------------------------------------------
		$deduction_config_q = $this->payroll->displayDeduction();
		$arr_deduc_config = $this->constructArrayListFromStdClass($deduction_config_q,'id','description');


		///< ------------------------------ loan config ---------------------------------------------------------------
		$loan_config_q = $this->payroll->displayLoan();
		$arr_loan_config = $this->constructArrayListFromStdClass($loan_config_q,'id','description');


		foreach ($emplist as $row) {
			$empid = $row->employeeid;
			
			///< check for computation
			$res = $this->getPayrollSummary($status,$sdate,$edate,$schedule,$quarter,$empid,false,'',$bank,$refno, $fund_type);
			if($res->num_rows() > 0){
			
			// 	die;
				$regpay =  $row->regpay;
				$dependents = $row->dependents;

				$arr_info[$empid]['income'] = $arr_info[$empid]['income_adj'] = $arr_info[$empid]['deduction'] = $arr_info[$empid]['fixeddeduc'] = $arr_info[$empid]['loan'] = array();

				$arr_info[$empid]['fullname'] 	= isset($row->fullname) ? $row->fullname : '';
				$arr_info[$empid]['deptid'] 	= isset($row->deptid) ? $row->deptid : '';
				$arr_info[$empid]['office'] 	= isset($row->office) ? $row->office : '';
				$res 							= $res->row(0); 

				$arr_info[$empid]['base_id'] 	= $res->id; 

				$arr_info[$empid]['salary'] 	= $res->salary;
				$arr_info[$empid]['overtime'] 	= $res->overtime;
				$arr_info[$empid]['tardy'] 		= $res->tardy;
				$arr_info[$empid]['absents'] 	= $res->absents;
				$arr_info[$empid]['whtax'] 		= $res->withholdingtax;
				$arr_info[$empid]['editedby'] 	= $res->editedby;
				$arr_info[$empid]['netbasicpay'] = $res->netbasicpay;
				$arr_info[$empid]['grosspay'] 	= $res->gross;
				$arr_info[$empid]['provident_premium'] 	= $res->provident_premium;
				$arr_info[$empid]['netpay'] 	= $res->net;
				$arr_info[$empid]['timestamp'] 	= date("F d, Y h:i A", strtotime($res->timestamp));
				$arr_info[$empid]['editedby'] 	= $res->editedby;
				$arr_info[$empid]['isHold'] 	= $res->isHold;
				$arr_info[$empid]['teaching_pay'] 	= $res->teaching_pay;
				$arr_info[$empid]['pera'] 	= $res->pera;
				$arr_info[$empid]['ref_no'] 	= $res->ref_no;

				//< income
				$income_arr 				= $this->constructArrayListFromComputedTable($res->income);
				$arr_info[$empid]['income'] = $income_arr;
				foreach ($income_arr as $k => $v) {
					$arr_income_config[$k]['hasData'] = 1;
					if($k == "Monetize") $arr_income_config[$k]['description'] = $k;
				}

				$income_adj_arr 				= $this->constructArrayListFromComputedTable($res->income_adj);
				$arr_info[$empid]['income_adj'] = $income_adj_arr;
				foreach ($income_adj_arr as $k => $v) {$arr_income_adj_config[$k]['hasData'] = 1;}

				///< fixed deduc
		        $fixeddeduc_arr = $this->constructArrayListFromComputedTable($res->fixeddeduc);
		        $arr_info[$empid]['fixeddeduc'] = $fixeddeduc_arr;
		        foreach ($fixeddeduc_arr as $k => $v) {$arr_fixeddeduc_config[$k]['hasData'] = 1;}

		        ///< deduc
		        $deduc_arr = $this->constructArrayListFromComputedTable($res->otherdeduc);
		        $arr_info[$empid]['deduction'] = $deduc_arr;
		        foreach ($deduc_arr as $k => $v) {$arr_deduc_config[$k]['hasData'] = 1;}

		        ///< loan
		        $loan_arr = $this->constructArrayListFromComputedTable($res->loan);
		        $arr_info[$empid]['loan'] = $loan_arr;
		        foreach ($loan_arr as $k => $v) {$arr_loan_config[$k]['hasData'] = 1;}


			}

		} //end loop emplist
		
		$data['emplist'] = $arr_info;
		$data['income_config'] = $arr_income_config;
		$data['income_adj_config'] = $arr_income_adj_config;
		$data['incomeoth_config'] = $arr_incomeoth_config;
		$data['fixeddeduc_config'] = $arr_fixeddeduc_config;
		$data['deduction_config'] = $arr_deduc_config;
		$data['loan_config'] = $arr_loan_config;
		$data['sdate'] = $sdate;
		$data['edate'] = $edate;

		return $data;
	}


	function getAtmPayrolllist($emp_bank='', $cutoffstart, $status = 'PROCESSED', $sortby = '', $campus = '', $company = '', $deptid = '', $office = '', $teachingtype = '', $employeeid=''){
		$where_clause = $order_by = $account_no = '';
		if($employeeid && $employeeid[0] && is_array($employeeid)){
			$emplist = "'" . implode( "','", $employeeid ) . "'";
			$where_clause .= " AND a.`employeeid` IN ($emplist) ";
		}else{
			if($employeeid){
				if(!in_array("", $employeeid)){
					if($employeeid) $where_clause .= " AND a.`employeeid` = '$employeeid' ";
				}
			}
		}
		if($emp_bank) $where_clause .= " AND c.`bank`='$emp_bank' ";
		if($teachingtype) $where_clause .= " AND a.`teachingtype`='$teachingtype' ";
		if($deptid) $where_clause .= " AND a.`deptid`='$deptid' ";
		if($office) $where_clause .= " AND a.`office`='$office' ";
		if($emp_bank) $where_clause .= " AND c.`bank`='$emp_bank' ";
		if($campus && $campus != 'all' && $campus != 'All') $where_clause .= " AND a.`campusid`='$campus' ";
		if($company && $company != 'all') $where_clause .= ' AND a.`company_campus`="'.$company.'" ';
		
		if($sortby == 'alphabetical') $order_by = " ORDER BY a.lname";
		if($sortby == 'department') $order_by = " ORDER BY b.description";
		$res = $this->db->query("SELECT a.employeeid, lname, mname, fname, c.`bank`, c.`net`, a.emp_accno, b.description,a.company_campus
							FROM employee a
							INNER JOIN code_office b ON b.`code`=a.`office`
							INNER JOIN payroll_computed_table c ON c.`employeeid`=a.`employeeid`
							WHERE c.`status` = '$status' AND cutoffstart='$cutoffstart' $where_clause $order_by");
		$data = array();
		if($res->num_rows() > 0){
			foreach ($res->result() as $key => $row) {
				$emp_bank = $this->extensions->getEmpBank($row->employeeid);
				$emp_bank = explode("/", $emp_bank);
				if($emp_bank){
					foreach($emp_bank as $bank){
						$fbank = explode("=", $bank);
						if($row->bank == $fbank[0]) $account_no = isset($fbank[1]) ? $fbank[1] : '';
					}
				}

				$fullname = $row->lname . ' ' . $row->fname . ' ' . substr($row->mname, 0,1) . '.';
				$data['list'][$row->employeeid] = array('fullname'=>utf8_encode($fullname),'account_num'=>$account_no,'net_salary'=>$row->net,'description'=>$row->description,'company_campus'=>$row->company_campus, "fname" => $row->fname, "mname" => $row->mname, "lname" => $row->lname);
			}
		}

		// $b_q = $this->payroll->displayBankList($emp_bank);

		$data['branch'] = 'METROBANK';
		$data['bank_name'] = 'MBOS';

		/*if($b_q->num_rows() > 0){
			$data['branch'] = $b_q->row(0)->branch;
			$data['bank_name'] = $b_q->row(0)->bank_name;
		}*/


		return $data;

	}


	///< Reglamentory Payment
	function getReglamentoryPaymentComputed($id='',$base_id='',$code_deduction=''){
		$wC = '';
		if($id)				$wC .= " AND id='$id'";
		if($base_id)		$wC .= " AND base_id='$base_id'";
		if($code_deduction)	$wC .= " AND code_deduction='$code_deduction'";
		$res = $this->db->query("SELECT * FROM payroll_computed_ee_er WHERE EE <> 0 $wC LIMIT 1");
		return $res;
	}

	function checkDeductionIfWithtax($key){
		$deduc_query = $this->db->query("SELECT taxable FROM payroll_deduction_config WHERE id = '$key'")->row()->taxable;
		return $deduc_query;
	}

	function checkIfPayrollSaved($payroll_start, $payroll_end, $employeeid){
		$query = $this->db->query("SELECT * FROM payroll_computed_table WHERE employeeid = '$employeeid' AND cutoffstart = '$payroll_start' AND cutoffend = '$payroll_end' ");
		if($query->num_rows() > 0){
			return $query->row()->status;
		}else{
			return FALSE;
		}
	}

	function getTotalNotIncludedInGrosspay($arr_income){
		$income = $this->extensions->getNotIncludedInGrosspayIncome();
		$total = 0;
		foreach($arr_income as $inc_key => $value){
			if(array_key_exists($inc_key, $income)) $total += $value;
		}

		return $total;
	}

	function validateDTRCutoff($sdate, $edate, $quarter){
		$q_cutoff = $this->db->query("SELECT a.nodtr FROM payroll_cutoff_config a INNER JOIN cutoff b ON a.CutoffID = b.CutoffID WHERE a.startdate = '$sdate' AND a.enddate = '$edate' AND a.quarter = '$quarter' ");
		// echo "<pre>"; print_r($this->db->last_query()); die;
		if($q_cutoff->num_rows() > 0) return ($q_cutoff->row()->nodtr) ? true : false;
		else return false;
		
	}
	
	function getAbsentPerdept($empid,$cutoffstart='',$cutoffend=''){
		$query_perdeptAbsent = $this->db->query("SELECT * FROM attendance_confirmed a 
											INNER JOIN workhours_perdept b ON b.`base_id` = a.`id` 
											WHERE a.payroll_cutoffstart='$cutoffstart' 
											AND a.payroll_cutoffend='$cutoffend' 
											AND a.employeeid='$empid' ")->result_array();
		return $query_perdeptAbsent;
	}

	function getAbsentNonteaching($empid,$cutoffstart='',$cutoffend=''){
		$query_perdeptAbsent = $this->db->query("SELECT * FROM attendance_confirmed_nt 
											WHERE payroll_cutoffstart='$cutoffstart' 
											AND payroll_cutoffend='$cutoffend' 
											AND employeeid='$empid' ")->result_array();
		return $query_perdeptAbsent;
	}	

	function updateComputedEE_ORNum($id='',$base_id='',$code_deduction='',$or_number='',$datepaid='',$cutoff='',$schedule=''){
		$wC = "";
		$wC_arr = array();
		if($id) 			array_push($wC_arr, "id='$id'");
		if($base_id) 		array_push($wC_arr, "base_id='$base_id'");
		if($code_deduction)	array_push($wC_arr, "code_deduction='$code_deduction'");
		if(sizeof($wC_arr) > 0){
			$wC = " WHERE " . implode(' AND ', $wC_arr);
		}

		$update = "";
		if(!$datepaid)	$update .= " ,datepaid=NULL";
		else 			$update .= " ,datepaid='$datepaid'";
		
		if(!$cutoff)	$update .= " ,payroll_cutoff=NULL, schedule=NULL";
		else			$update .= " ,payroll_cutoff='$cutoff', schedule = '$schedule'";
		$username = $this->session->userdata("username");
		$date = date('Y-m-d h:i:s');
		
		$res = $this->db->query("UPDATE payroll_computed_ee_er SET or_number='$or_number', modified_by = '$username', modified_date = '$date' $update $wC");
		return $res;
	}

	function getEmployeeSalaryRate($regpay, $daily, $employeeid, $sdate){
		$p_history = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid = '$employeeid' AND date_effective <= '$sdate' ORDER BY date_effective DESC LIMIT 1");
		if($p_history->num_rows() > 0) return array($p_history->row()->semimonthly, $p_history->row()->daily);
		else return array($regpay, $daily);
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

	function latestTaxExcess($eid, $year){
		$q_tax = $this->db->query("SELECT * FROM tax_breakdown WHERE employeeid = '$eid' AND YEAR(cutoffstart) = '$year' ORDER BY cutoffstart DESC LIMIT 1");
		if($q_tax->num_rows() > 0){
			return array($q_tax->row()->excess_percent, $q_tax->row()->annual_tax);
		}else{
			return 0;
		}
	}

	function employeeBPE($eid){
		$q_bpe = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid = '$eid' ORDER BY timestamp LIMIT 1");
		if($q_bpe->num_rows() > 0){
			return (int) $q_bpe->row()->bpe;
		}else{
			return 0;
		}
	}

	function employeeBonusThreshold($eid){
		$q_bonus = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid = '$eid' ORDER BY timestamp LIMIT 1");
		if($q_bonus->num_rows() > 0){
			return (int) $q_bonus->row()->bonus_treshold;
		}else{
			return 0;
		}
	}

	function employeeYearlyTax($empid, $year){
		$q_tax = $this->db->query("SELECT SUM(withholdingtax) AS tax_year FROM payroll_computed_table WHERE employeeid = '$empid' AND YEAR(cutoffstart) = '$year'");
		if($q_tax->num_rows() > 0){
			return $q_tax->row()->tax_year;
		}else{
			return 0;
		}
	}

	function getPera($employeeid) {
		return $this->db->query("SELECT personal_economic_relief_allowance FROM payroll_employee_salary WHERE employeeid = '$employeeid'")->result();
	}

} //endoffile