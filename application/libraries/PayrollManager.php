<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PayrollManager
{   
    private $CI;
    private $worker_model;
    private $payroll_model;
    private $payrollreport;
    private $reports;
    private $extensions;
    private $utils;
    private $payrollprocess;

    function __construct() {
        $this->CI = & get_instance();
        $this->CI->load->model("Worker_model", "worker_model");
        $this->CI->load->model("Payroll_model", "payroll_model");
        $this->CI->load->model("Payrollreport", "payrollreport");
        $this->CI->load->model("Reports", "reports");
        $this->CI->load->model("Extensions", "extensions");
        $this->CI->load->model("Utils", "utils");
        $this->CI->load->model("Payrollprocess", "payrollprocess");

        $this->worker_model = $this->CI->worker_model;
        $this->payroll_model = $this->CI->payroll_model;
        $this->payrollreport = $this->CI->payrollreport;
        $this->reports = $this->CI->reports;
        $this->extensions = $this->CI->extensions;
        $this->utils = $this->CI->utils;
        $this->payrollprocess = $this->CI->payrollprocess;
    }
    
    public function getPayrollJob() {
        return $this->worker_model->getPayrollJob();
    }

    public function processPayroll($details, $worker_id){
        if ($details->code == 'payslip') $this->payroll_process($details, $worker_id);
        elseif ($details->code == 'payrollreg') $this->payrollreg_process($details, $worker_id);
        elseif ($details->code == 'atmpayroll') $this->atmpayroll_process($details, $worker_id);
    }

    public function payroll_process($details, $worker_id){

        $this->worker_model->updatePayrollStatus($details->id, "ongoing");
		$user = $details->user;

        $data = json_decode($details->formdata,true);

		if(isset($data["payrollcutoff"])){
			list($data["dfrom"], $data["dto"]) = explode(" ", $data["payrollcutoff"]);
		}
        $data['tnt'] = isset($data['tnt']) ? $data['tnt'] : '';
		$data["dept"] = isset($data["deptid"]) ? $data["deptid"] : $data['dept'];
		$data["campus"] = isset($data["campusid"]) ? $data["campusid"] : $data['campus'];
		$data['employeeid'] = isset($data['employeeid']) ? $data['employeeid'] : $data["eid"];
		if(!$data["schedule"]) $data["schedule"] = "semimonthly";
		$data["sort"] = 0;
		$data['payroll_config'] = $this->payroll_model->getAllIncomeKeysAndDescription();
		if($data['employeeid'] && $data['employeeid'] !== 'null'){
            $employeeid = $data['employeeid'];
            unset($data['employeeid']);
            foreach ($employeeid as $key => $value){
                $value = str_replace("'", "",$value);
                if($value){
                	if($key == 0) $data['employeeid'] = "'".$value."'";
                	else $data['employeeid'] .= ",'".$value."'";
                }
            }
        }

		if ($data['eid']) {
			if(!is_array($data["eid"])) $data["eid"] = array(0=>$data["eid"]);
			$data["eid"] = "'" . implode( "','", $data["eid"]) . "'";
		}
        
		$emplist = $this->payroll_model->loadAllEmpbyDeptForPayslip($data["dept"],$data["eid"],$data["schedule"],$data["sort"],$data["dfrom"],true,'', $data["campus"], '', $data['bank'], $data['tnt'], $user); // PAYROLL

		$emp_data = $this->payroll_model->getPayslipSummary($emplist, $data["dfrom"],$data["dto"],$data["schedule"],$data["quarter"],$data["bank"]); // PAYROLLREPORT
		$emp_data["emp_bank"] = $this->payroll_model->getBankName($data["bank"], $data['tnt']); // EXTENSIONS
		$emp_data["dfrom"] = $data["dfrom"];
		$emp_data["dto"] = $data["dto"];
		$emp_data["campusid"] = $data["campusid"];

        $emp_data["path"] = "files/payroll/{$details->id}.pdf";
        if($data["quarter"] == 1) $this->CI->load->view('forms_pdf/payslip_detailed', $emp_data); 
		else $this->CI->load->view('forms_pdf/payslip_basic', $emp_data);
        
        $this->worker_model->updatePayrollStatus($details->id, "done");
    }	

    public function payrollreg_process($details, $worker_id){

        $this->worker_model->updatePayrollStatus($details->id, "ongoing");
		$user = $details->user;

        $data = json_decode($details->formdata,true);
        $session_data = json_decode($details->session_data,true);

        if ($data['quarter'] == '1'){
            $filter = $this->getPayrollRegisterFilter($data);
            $data['emplist'] = $this->payrollreport->getPayrollRegisterList($filter, $session_data);
    
            switch ($data['estatid']) {
                case 'PRTTIM': $view = 'payroll_register_parttime'; break;
                case 'EL': $view = 'payroll_register_emergencylabor'; break;
                case 'PRMNT': $view = 'paryroll_register_permanent'; break;
                default: $view = 'payroll_registrar'; break;
            }
            $data["path"] = "files/payroll/{$details->id}.pdf";
    
            if($data['reportformat'] == "PDF" && $view != 'payroll_registrar.old.php') {
                $pdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => [377.698, 279.4], 'tempDir' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'mpdf', 'margin_top' => '5', 'margin_bottom' => '5', 'margin_right' => '5', 'margin_left' => '5']);
                $info = $this->CI->load->view('forms_pdf/'.$view, $data, true);
                $pdf->WriteHTML($info);
                $pdf->Output($data["path"], 'F');
            } else {
                $this->CI->load->view('forms_pdf/'.$view, $data, true);
            }
    
            $this->worker_model->updatePayrollStatus($details->id, "done");
        }else{
            $post_data = $data;
            
            $data = array();
            $data["path"] = "files/payroll/{$details->id}.pdf";

            $date_from = $date_to = "";
            if($post_data["rep_type"] == "cutoff"){    
                list($date_from, $date_to) = explode(" ", $post_data['payrollcutoff']);
                $data["sched_display"] = date("F", strtotime($date_to)) ." ". date("d", strtotime($date_from)) ."-". date("d", strtotime($date_to)) ." ". date("Y", strtotime($date_to));
            }else{
                $date_from = $post_data["yearfrom"]. "-01-01";
                $date_to = $post_data["yearto"]. "-12-31";
                $data["sched_display"] = "January " . $post_data["yearfrom"] . " - ". "December ". $post_data["yearto"];
            }
    
            $deminimiss_inc = array();
            $deductionToDisplay = array();
            $filter = $this->getPayrollRegisterFilter($post_data); 
            $selected_income = $this->getPayrollRegisterIncomeDisplay($post_data);
            $selected_deduction = $this->getPayrollRegisterDeductionDisplay($post_data);
            // $selected_adjustment = $this->getPayrollAdjustmentIncomeDisplay($post_data);
    
            /*for filter history*/
            $save_adjustment_history = $this->reports->save_payrollregister_filter($selected_income, "income");
            $save_adjustment_history = $this->reports->save_payrollregister_filter($selected_deduction, "deduction");
            // $save_history = $this->reports->save_payrollregister_filter($selected_adjustment, $post_data['demchoices']);
            /*end*/
            $inc_income = $inc_adjustment = $inc_loan = $inc_fixed_deduc = $inc_deduction = $inc_loan = $summary = $deminimiss_column = $notdeminimiss_column = $deduction_column = $income_column = array();
            $emp_list = $grand_total["income"] = $grand_total["deduction"] = array();
            $emplist = $this->payrollreport->getPayrollRegisterList($filter, $session_data);
            $income_arr = $inc_income = $adjustmentToDisplay = array();
            if ($emplist) {
                foreach($emplist as $row){
                    $sort_key = "name";
                    if($post_data["sortby"] == "department") $sort_key = $row->office; 
                    if($post_data["sortby"] == "alphabetical") $sort_key = $row->lname.",".$row->fname;
                    if($post_data["sortby"] == "campus") $sort_key = $row->campusid;
    
                    $income_arr = $this->setListTagToArray($row->income);
                    $inc_income = $this->setIncludeIncomeLoanToTotal($income_arr, $inc_income);
                    // $adjustment_arr = $this->setListTagToArray($row->income_adj);
                    // $inc_adjustment = $this->setIncludeIncomeLoanToTotal($adjustment_arr, $inc_adjustment);
    
                    $displayIncome = array_intersect_key($inc_income, $selected_income);
                    $income_todisplay = $this->getIncomeToDisplay($displayIncome);
    
                    $notDisplayIncome = array_diff_key($income_arr, $selected_income);
                    $income_notdisplay = $this->getIncomeToDisplay($notDisplayIncome);
    
                    // $displayAdjustment = array_intersect_key($inc_adjustment, $selected_adjustment);
                    // $notDisplayAdjustment = array_diff_key($inc_adjustment, $selected_adjustment);
    
                    $income_column = $this->getPayrollRegisterIncomeColumn($deminimiss_column, $income_todisplay);
    
                    $income_list = array(
                        "salary"            => $row->salary,
                        "tardy"             => $row->tardy,
                        "absent"            => $row->absents,
                        "basic_pay"         => $row->netbasicpay,
                        "income_list"       => $income_arr,
                        // "adjustment_list"   => $adjustment_arr,
                        "overtime"          => $row->overtime,
                        "gross"             => $row->gross,
                        // "totalOtherAdjustmentToDisplay" => array_sum($notDisplayAdjustment),
                        "totalIncomeToDisplay" => array_sum($income_notdisplay)
                    );
    
                    $fixed_deduc_arr = $this->setListTagToArray($row->fixeddeduc);
                    $inc_fixed_deduc = $this->setIncludeIncomeLoanToTotal($fixed_deduc_arr, $inc_fixed_deduc);
                    $deduc_arr = $this->setListTagToArray($row->otherdeduc);
                    $inc_deduction = $this->setIncludeIncomeLoanToTotal($deduc_arr, $inc_deduction);
    
                    $displayDeduction = array_intersect_key($inc_deduction, $selected_deduction);
                    $deduction_todisplay = $this->getDeductionToDisplay($displayDeduction);
                    $deduction_column = $this->getPayrollRegisterDeductionColumn($deduction_column, $deduction_todisplay);
    
                    $loan_arr = $this->setListTagToArray($row->loan);
                    $inc_loan = $this->setIncludeIncomeLoanToTotal($loan_arr, $inc_loan);
    
                     /*get the deduction that will be displayed*/
                    $emp_deduction = array_intersect_key($deduc_arr,$selected_deduction);
    
                    /*get sum of total deduction and selected deduction*/
                    $totalDeduction = array_sum($deduc_arr);
                    $totalSelectedDeduction = array_sum($emp_deduction);
    
                    foreach($emp_deduction as $key => $value){
                        if(!in_array($value, $deductionToDisplay)){
                            $deductionToDisplay[$key] = $value;
                        }
                    }
    
                    $totalOtherDeductionToDisplay = $totalDeduction - $totalSelectedDeduction;
    
                    $deduction_list = array(
                        "provident_premium"      => $row->provident_premium,
                        "with_holding_tax"      => $row->withholdingtax,
                        "fixed_deduc_list"      => $fixed_deduc_arr,
                        "deduc_list"            => $deduc_arr,
                        "loan_list"             => $loan_arr,
                        "total_deduction"       => $this->getTotalDeduction($row->withholdingtax, $fixed_deduc_arr, $deduc_arr, $loan_arr),
                        "net"                   => $row->net,
                        "totalOtherDeductionToDisplay" => $totalOtherDeductionToDisplay
                    );
    
                    $grand_total["income"] = $this->setGrandTotalInArray($income_list, $grand_total["income"]);
                    $grand_total["deduction"] = $this->setGrandTotalInArray($deduction_list, $grand_total["deduction"]);
    
                    $cutoff_display = date("F d", strtotime($row->cutoffstart)). " - ". date("F d, Y", strtotime($row->cutoffend));
    
                    $emp_list[$cutoff_display][$sort_key][$row->employeeid] = array(
                        "employeeid"        => $row->employeeid,
                        "name"              => $row->lname.", ".$row->fname.", ". $row->mname,
                        "income"            => $income_list,
                        "campus"            => $row->campusid,
                        "company"           => $row->company_campus,
                        "deptid"            => $row->deptid,
                        "office"            => $row->office,
                        "teachingtype"            => $row->teachingtype,
                        "deduction"         => $deduction_list
                    );
    
                    if(!isset($row->office)){
                        $summary[$row->office] = array(
                            "income" => array(),
                            "deduction" => array()
                        );
                    } 
                    if(!isset($summary[$row->office])){
                        $summary[$row->office] = array();
                        $summary[$row->office]['count'] = 0;
                    }
    
                    if(!isset($summary[$row->office]['count'])) $summary[$row->office]['count'] = 0;
                    
                    $summary[$row->office] = $this->setGrandTotalInArray($income_list, $summary[$row->office]);
                    $summary[$row->office] = $this->setGrandTotalInArray($deduction_list, $summary[$row->office]);
                    $summary[$row->office]['count']++;
    
                    if(isset($summary[$row->office]["income"])) $summary[$row->office]["income"] = $this->setGrandTotalInArray($income_list, $summary[$row->office]["income"]);
                    if(isset($summary[$row->office]["deduction"])) $summary[$row->office]["deduction"] = $this->setGrandTotalInArray($deduction_list, $summary[$row->office]["deduction"]);
                }
            }
                
            $data["config"] = $this->getArrayConfig();
    /*        ksort($inc_adjustment);
            $data["inc_adjustment"] = $selected_adjustment;*/
            ksort($inc_income);
            $data["inc_income"] = $income_column;
            ksort($inc_fixed_deduc);
            $data["inc_fixed_deduc"] = $inc_fixed_deduc;
            ksort($inc_deduction);
            $data["inc_deduction"] = $deduction_column;
            ksort($inc_loan);
            $data["inc_loan"] = $inc_loan;
            $data["grand_total"] = $grand_total;
            $data["summary"] = $summary;
            // echo "<pre>"; print_r($summary); die;
            $data["emp_list"] = $emp_list;
            $data["sort_type"] = $post_data["sortby"];
            $data['display'] = $post_data['sd_filter'];
            $data['company_campus'] = $post_data['company_campus'];
            $data["campus_name"] = $this->extensions->getCampusDescription($post_data["campusid"]);
            $data["campus"] = $post_data["campusid"];
            $data['emp_type']  = ($post_data["tnt"]) ? ucfirst($post_data["tnt"]) ." Employees " : "";
        
            $data['campusid'] = $post_data['campusid'];
            $data["mtitle"] = "PAYROLL SHEET FOR SALARY SCHEDULE";                                                     // ****
            $data["departmentid"] = $this->utils->getDepartmentDesc($post_data['department']);                // **** LOLA 11-22-2022
            // echo "<pre>"; print_r($post_data); die;
            if($post_data['reportformat'] == "PDF"){
                $pdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => [377.698, 279.4], 'tempDir' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'mpdf', 'margin_top' => '5', 'margin_bottom' => '5', 'margin_right' => '5', 'margin_left' => '5']);
                $info = $this->CI->load->view('forms_pdf/payroll_registrar.bak.php', $data, true);
                $pdf->WriteHTML($info);
                $pdf->Output($data["path"], 'F');
            }
            else{
                $data["path"] = "files/payroll/{$details->id}.xls";
                $this->CI->load->view("reports_excel/payroll_registrar", $data); // ERROR FOR EACH PAYROLL
            } 

            $this->worker_model->updatePayrollStatus($details->id, "done");
        }
        
    }	

    public function atmpayroll_process($details, $worker_id){
        $data = array();
        $this->worker_model->updatePayrollStatus($details->id, "ongoing");

        $formdata = json_decode($details->formdata,true);

		$deptid     		=  isset($formdata['deptid']) ? $formdata['deptid'] : '';
		$employeeid 		=  isset($formdata['employeeid']) ? $formdata['employeeid'] : '';
		$schedule   		=  isset($formdata['schedule']) ? $formdata['schedule'] : '';
		$cutoff     		=  isset($formdata['payrollcutoff']) ? $formdata['payrollcutoff'] : '';
		$quarter    		=  isset($formdata['quarter']) ? $formdata['quarter'] : '';
		$campus    			=  isset($formdata['campus']) ? $formdata['campus'] : '';
		$company_campus    	=  isset($formdata['company_campus']) ? $formdata['company_campus'] : '';
		$sortby 			=  isset($formdata['sortby']) ? $formdata['sortby'] : '';
		$office 			=  isset($formdata['office']) ? $formdata['office'] : '';
		$teachingtype 		=  isset($formdata['tnt']) ? $formdata['tnt'] : '';

		$reportname 		=  isset($formdata['reportname']) ? $formdata['reportname'] : '';
		$reportformat 		=  isset($formdata['reportformat']) ? $formdata['reportformat'] : '';

		$dateprocessed 		=  isset($formdata['dateprocessed']) ? $formdata['dateprocessed'] : '';

		$dates = explode(' ',$cutoff);
		if(isset($dates[0]) && isset($dates[1])){
			$sdate = $dates[0];
			$edate = $dates[1];
		}else{
			echo 'Invalid Cutoff';
			return;
		}

		if($reportname == 'payrollsummary'){
			$deminimiss = ($this->input->get('deminimiss')) ? $this->input->get('deminimiss') : $this->input->post('deminimiss');
			$other =  ($this->input->get('other')) ? $this->input->get('other') : $this->input->post('other');

			$emplist = $this->payroll->loadAllEmpbyDept($deptid,$employeeid,$schedule,$campus,$company_campus,$sdate,$edate,$sortby,$office,$teachingtype);
			if(sizeof($emplist) > 0){
				$data = $this->payrollprocess->getProcessedPayrollSummary($emplist,$sdate,$edate,$schedule,$quarter);
				$data['deminimiss_config'] 	= $this->payrollconfig->getIncomeConfig('deminimiss','',array('description'));
				$data['others_config'] 		= $this->payrollconfig->getIncomeConfig('other','',array('description'));
				$data['deminimiss'] 		= $deminimiss;
				$data['other'] 				= $other;

				if($reportformat == 'xls'){

				}else{
					$this->load->view('payroll/reports_pdf/processed_payroll_summary',$data);
				}

			}else{
				echo 'No employees to display.';
				return;
			}

		}elseif($reportname=='atmpayrolllist' || $reportname=='bankpayrolllist'){
			$emp_bank = isset($formdata['emp_bank']) ? $formdata['emp_bank'] : '';
			$status =  isset($formdata['emp_status']) ? $formdata['emp_status'] : '';

			if(!$status) $status = isset($formdata['payroll_status']) ? $formdata['payroll_status'] : '';
			$data = $this->payrollprocess->getAtmPayrolllist($emp_bank, $sdate, $status, $sortby,$campus, $company_campus,$deptid,$office,$teachingtype,$employeeid);
			$data['sdate'] = $sdate;
			$data['edate'] = $edate;
			$data['sortby'] = $sortby;
			$data["emp_bank"] = $emp_bank;
			$data["dateprocessed"] = $dateprocessed;
			$data['campus_desc'] = (isset($campus) ? ($campus == "All" || $campus == '' ? "All Campus" : $this->extensions->getCampusDescription($campus)) : '');
			$data['company_desc'] = (isset($company_campus) ? $this->extensions->getCompanyDescriptionReports($company_campus) : '');
			$data['emp_type']  = ($teachingtype) ? ucfirst($teachingtype) ." Employees " : "";
			

			if($reportformat == 'XLS'){
				if($reportname == "atmpayrolllist") $this->load->view('payroll/reports_excel/atm_payroll_list',$data);
				else $this->load->view('payroll/reports_excel/bank_payroll_list',$data);
			}else{
				$data['campusid'] = $campus;
				$data["mtitle"] = "ATM PAYROLL LIST";                                                     				   // ****
				$data["departmentid"] = $this->utils->getDepartmentDesc(isset($formdata['department']) ? $formdata['department'] : '');                // **** LOLA 11-22-2022
				$data['cutoff_start'] = $sdate;
				$data['cutoff_end'] = $edate;
				$data['company_campus'] = isset($formdata['company_campus']) ? $formdata['company_campus'] : '';
                $data["path"] = "files/payroll/{$details->id}.pdf";
				// echo "<pre>";print_r($data);die;
				$this->CI->load->view('payroll/reports_pdf/atm_payroll_list',$data);
			}
		}

        $this->worker_model->updatePayrollStatus($details->id, "done");
    }

    function getPayrollRegisterFilter($filter){
        // echo "<pre>";print_r($filter);
        $sortid = "campusid, company_campus, deptid, lname";
        $filtercampus = $filter['campusid'];
        $filtercompany = isset($filter['company_campus']) ? $filter['company_campus'] : '';
        $employeeid = "";
        if(isset($filter['employeeid']) && $filter['employeeid'] && $filter['employeeid'] !== 'null'){
            $employeeid = $filter['employeeid'];
            unset($filter['employeeid']);
            if(is_array($employeeid)){
                foreach ($employeeid as $key => $value){
                    $value = str_replace("'", "",$value);
                    if($key == 0) $filter['employeeid'] = "'".$value."'";
                    else $filter['employeeid'] .= ",'".$value."'";
                }
            }
        }

        

    // if($filter["sortby"] == "alphabetical") $sortid = "CONCAT(lname, ', ', fname, ', ', mname)";
        // if($filter["sortby"] == "campus") $sortid = "campusid, lname";
        // if($filter["sortby"] == "department") $sortid = "deptid, lname";
        // if($filter["sortby"] == "office") $sortid = "office, lname";


       
        $where_clause = " WHERE a.employeeid != ''";
        if (isset($filter['estatid']) && $filter['estatid']) $where_clause .= " AND a.employmentstat = '{$filter['estatid']}' ";
        if (isset($filter['batchid']) && $filter['batchid']) $where_clause .= " AND a.employeeid IN (SELECT employeeid FROM payroll_batch_emp WHERE base_id = '{$filter['batchid']}') ";
      

        if (isset($filter['teaching_type']) && $filter['teaching_type'] != "") {
            if ($filter['teaching_type'] == 'trelated') {
                $where_clause .= " AND a.teachingtype = 'nonteaching' AND a.trelated = '1'";
            } else {
                $where_clause .= " AND a.teachingtype = '{$filter['teaching_type']}'";
            }
        }
        if($filter['department']) $where_clause .= " AND a.deptid = '{$filter['department']}' ";
        if($filter['office']) $where_clause .= " AND a.office = '{$filter['office']}' ";
        if($filter['campusid'] && $filter['campusid'] != 'All') $where_clause .= " AND a.campusid = '$filtercampus' ";
        if(isset($filter['company_campus']) && $filter['company_campus'] != 'all' && $filter['company_campus'] != '') $where_clause .= ' AND a.company_campus = "'.$filtercompany.'"';
        if($filter['payroll_status']) $where_clause .= " AND b.status = '{$filter['payroll_status']}' ";
        if(isset($filter['bank'])) $where_clause .= " AND b.bank = '{$filter['bank']}' ";
        if($filter["rep_type"] == "cutoff"){
            list($cutoff_from, $cutoff_to) = explode(" ", $filter['payrollcutoff']);
            if($filter['schedule']) $where_clause .= " AND b.schedule = '{$filter['schedule']}' ";
            if($filter['quarter']) $where_clause .= " AND b.quarter = '{$filter['quarter']}' ";
            if($cutoff_from && $cutoff_to) $where_clause .= " AND b.cutoffstart = '$cutoff_from' AND b.cutoffend = '$cutoff_to' ";
        }else{
            $where_clause .= " AND YEAR(b.cutoffstart) = '{$filter['yearfrom']}' AND YEAR(b.cutoffend) = '{$filter['yearto']}' ";
        }
        if(isset($filter['employeeid']) && $filter['employeeid']) $where_clause .= "AND a.employeeid IN ({$filter['employeeid']})";
        if($employeeid && !is_array($employeeid)) $where_clause .= " AND a.employeeid = '$employeeid'";
        if (isset($filter['ref_no']) && $filter['ref_no']) $where_clause .= " AND b.ref_no = '{$filter['ref_no']}' ";

        if (isset($filter['sort_by']) && $filter['sort_by'] == 'office') {
            $where_clause .= " ORDER BY a.office";
        } elseif (isset($filter['sort_by']) && $filter['sort_by'] == 'department') {
            $where_clause .= " ORDER BY a.deptid";
        } 

        return $where_clause;
    }

    function getPayrollRegisterIncomeDisplay($post_data){
        $deminimiss_inc = array();
        if(array_key_exists("income", $post_data)){
            if(in_array("selectalldeminimiss", $post_data['income'])){
                $deminimiss_inc = $this->reports->getDeminimissIncome();    /*get all deminimiss income*/
                /* remove selectalldeminimiss in array*/
                $key = array_search("selectalldeminimiss", $post_data['income']);
                unset($post_data['income'][$key]); 
                /*end*/

                $deminimiss_inc = array_merge($deminimiss_inc, $post_data['income']);
            }else{
                $deminimiss_inc = $post_data['income'];
            }
        }

        $deminimiss_inc = array_flip($deminimiss_inc);
        return $deminimiss_inc;
    }
    function getPayrollRegisterDeductionDisplay($post_data){
        $deduction_inc = array();
        if(array_key_exists("deduction", $post_data)){
            if(in_array("selectalldeduction", $post_data['deduction'])){
                $deduction_inc = $this->reports->getDeductionConfigArr();    /*get all deduction*/
                /* remove selectalldeduction in array*/
                $key = array_search("selectalldeduction", $post_data['deduction']);
                unset($post_data['deduction'][$key]); 
                /*end*/

                $deduction_inc = array_merge($deduction_inc, $post_data['deduction']);
            }else{
                $deduction_inc = $post_data['deduction'];
            }
        }

        $deduction_inc = array_flip($deduction_inc);
        return $deduction_inc;
    }
    function setListTagToArray($list_tag){
        $data = array();

        if($list_tag){
            foreach (explode("/", $list_tag) as $tag_info) {
                list($key, $amount) = explode("=", $tag_info);

                $data[$key] = round($amount,2);      
            }
        }

        return $data;
    }
    function setIncludeIncomeLoanToTotal($list_tag, $data){

        foreach ($list_tag as $key => $amount) {
            if(!array_key_exists($key, $data)) $data[$key] = 0;

            $data[$key] += $amount;
        }

        return $data;
    }
    function getIncomeToDisplay($income){
        $income_todisplay = array();
        $deminimiss_inc = $this->reports->getDeminimissIncome();
        foreach($income as $key => $value){
            $income_todisplay[$key] = $value;
        }

        return $income_todisplay;
    }
    function getPayrollRegisterIncomeColumn($arr_col, $display_arr){
        foreach($display_arr as $key => $value){
            if(!array_key_exists($key, $arr_col)) $arr_col[$key] = $key;
        }

        return $arr_col;
    }
    function getDeductionToDisplay($deduction){
        $deduction_todisplay = array();
        $deminimiss_inc = $this->reports->getDeductionConfigArr();
        foreach($deduction as $key => $value){
            $deduction_todisplay[$key] = $value;
        }

        return $deduction_todisplay;
    }
    function getPayrollRegisterDeductionColumn($arr_col, $display_arr){
        foreach($display_arr as $key => $value){
            if(!array_key_exists($key, $arr_col)) $arr_col[$key] = $key;
        }

        return $arr_col;
    }
    function getTotalDeduction($with_holding_tax, $fixed_deduc, $other_deduc, $loan){
        $total = 0;

        $total += $with_holding_tax;
        $total += $this->getTotalAmountInArray($fixed_deduc);
        $total += $this->getTotalAmountInArray($other_deduc);
        $total += $this->getTotalAmountInArray($loan);

        return $total;
    }
    function getTotalAmountInArray($array){
        $amount = 0;

        foreach ($array as $key => $value) $amount += $value;

        return $amount;
    }
    function setGrandTotalInArray($list, $grand_total){

        foreach ($list as $key => $l_value) {
            
            if(is_array($l_value)){
                if(!array_key_exists($key, $grand_total)) $grand_total[$key] = array();

                foreach ($l_value as $sub_key => $sub_val) {
                    if(!array_key_exists($sub_key, $grand_total[$key])) $grand_total[$key][$sub_key] = 0;

                    $grand_total[$key][$sub_key] += $sub_val;
                }
            }else{
                if(!array_key_exists($key, $grand_total)) $grand_total[$key] = 0;

                $grand_total[$key] += $l_value;
            }
        }

        return $grand_total;
    }
    function getArrayConfig(){
        $this->CI->load->model("payrollconfig");
        $this->CI->load->model("extras");
        $data = array();

        $q_income = $this->CI->payrollconfig->getAllIncomeConfig();
        foreach ($q_income as $row) $data["income"][$row->id] = $row->description;

        $q_deduction = $this->CI->payrollconfig->getDeductionConfig();
        foreach ($q_deduction as $row) $data["deduction"][$row->id] = $row->description;

        $q_loan = $this->CI->payrollconfig->getLoanConfig();
        foreach ($q_loan as $row) $data["loan"][$row->id] = $row->description;

        $data["name"]["name"] = "ALL EMPLOYEE";

        $data["department"] = $this->CI->extras->showdepartment("NO DEPARTMENT");

        $data["campus"] = $this->CI->extras->showcampus("NO CAMPUS");

        return $data;
    }
}