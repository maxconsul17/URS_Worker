<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AttendanceConfirmManager
{   
    private $CI;
    private $worker_model;
    private $payroll_model;
    private $db;

    function __construct() {
        $this->CI = & get_instance();
        $this->CI->load->model("Worker_model", "worker_model");
        $this->CI->load->model("AttConfirm_model", "attconfirm_model");
        $this->CI->load->model("time", "time");
        $this->db = $this->CI->db;

        $this->worker_model = $this->CI->worker_model;
        $this->attconfirm_model = $this->CI->attconfirm_model;
    }
    
    public function getAttConfirmJob() {
        return $this->worker_model->getAttConfirmJob();
    }

    public function attConfirm($details, $worker_id){
        $this->attendance_confirm($details, $worker_id);
    }

    public function attendance_confirm($details, $worker_id){
        $this->worker_model->updateAttConfirmStatus($details->id, "ongoing");

        $cutoff = $details->cutoff;
        $teaching_type = $details->teaching_type;
        $where = " AND a.employeeid <> ''";
		if (isset($campus) && $campus != "All" && $campus != "") $where .= " AND a.campusid = '$campus'";
        if (isset($deptid) && $deptid != "All" && $deptid != "") $where .= " AND a.deptid = '$deptid'";
        if (isset($office) && $office != "All" && $office != "") $where .= " AND a.office = '$office'";
        if (isset($tnt) && strtolower($tnt) != "all" && $tnt != ""){
        	if($tnt != "trelated") $where .= " AND a.teachingtype = '$tnt'  AND a.trelated = '0'";
	      	else $where .= " AND (a.teachingtype='nonteaching' AND a.trelated = '1')";
        }
        if (isset($status) && $status != "All" && $status != "") $where .= " AND a.isactive = '$status'";
        if ($details->employeeid && $details->employeeid != "All"&& $details->employeeid != "all" && $details->employeeid != "") $where .= " AND FIND_IN_SET (a.employeeid, '$details->employeeid')";
		$return['employee_list'] =  $this->attconfirm_model->getEmployeeList($where);

        foreach ($return['employee_list'] as $empKey => $EmpDetails) {
            
            $dateRange = array();
            list($date_from, $date_to) = explode(',', $details->cutoff);
            $date_range = $this->attconfirm_model->displayDateRange($date_from, $date_to);
            foreach ($date_range as $date) {
                $hasLog = $this->attconfirm_model->checkIfHasLog($EmpDetails->employeeid, $date->dte);
                if(!$hasLog){
                    $dateRange[] = $date->dte;
                }
            }
            if(count($dateRange) > 0){
                foreach ($dateRange as $dateKey => $dateValue) {
                    $recompute = isset($recompute) ? $recompute : 0;
                    $employeeid = isset($EmpDetails->employeeid) ? $EmpDetails->employeeid : '';
                    $date = isset($dateValue) ? $dateValue : date('Y-m-d');
                    if (isset($recompute) && $recompute!='' && $recompute != '0') {
                        // $this->empAttDaily($employeeid, $date);
                    } else {
                        $hasLog = $this->attconfirm_model->checkIfHasLog($employeeid, $date);
                        if(!$hasLog){
                            // $this->empAttDaily($employeeid, $date);
                        }
                    }
                    if(!isset($dateRange[$dateKey+1])){ // if empAttDaily is done looping
                        $emp_list = $employeeid;
                        list($dfrom, $dto) = explode(",", $cutoff);
                        list($dtr_start,$dtr_end,$payroll_start,$payroll_end,$payroll_quarter) = $this->attconfirm_model->getDtrPayrollCutoffPair($dfrom,$dto);
                        $isnodtr = $this->attconfirm_model->checkIfCutoffNoDTR($dfrom,$dto);
                        $workhours_arr = array();
                        foreach (explode(",", $emp_list) as $employeeid) {
                            $teaching_related = $this->attconfirm_model->isTeachingRelated($employeeid);
                            if($teaching_type == "teaching" || $teaching_related){
                                $attendance = $this->attconfirm_model->getAttendanceTeaching($employeeid, $dfrom, $dto);
                                $off_lec_total = $off_lab_total = $off_admin_total = $off_overload_total = $twr_lec_total = $twr_lab_total = $twr_admin_total = $twr_overload_total = $teaching_overload_total = $ot_regular_total = $ot_restday_total = $ot_holiday_total = $lateut_lec_total = $lateut_lab_total = $lateut_admin_total = $lateut_overload_total = $absent_lec_total = $absent_lab_total = $absent_admin_total = $service_credit_total = $cto_total = $holiday_lec_total = $holiday_lab_total = $holiday_admin_total = $holiday_overload_total = $holiday_total = $date_list_absent = $total_absent = $vacation_total = $emergency_total = $other_total = $sick_total =  0;
                                $daily_present = $daily_absent = "";
                                foreach ($attendance as $att_date) {
                                    $counter = 0;
                                    $rowspan = 0;
                                    $is_absent = 0;
                                    $date = $daily_lec = $daily_lab = $daily_admin = $daily_overload = $daily_overtime_mode =  "";
                                    $daily_lec_absent = $daily_lab_absent = $daily_admin_absent = $daily_overload_absent = $daily_lec_late = $daily_lab_late = $daily_admin_late = $daily_overload_late = $daily_lec_undertime = $daily_lab_undertime = $daily_admin_undertime = $daily_overload_undertime = $daily_overtime = $daily_undertime = $daily_late = $daily_absents = $daily_overtime_amount =  0;
                                    $ot_list = array();
                                    foreach ($att_date as $key => $value) {
                                        $rowspan = $value->rowspan;
                                        $leave_project = 0;
                                        if($value->classification == "overload"){
                                            if($value->off_lec) $teaching_overload_total += $this->attconfirm_model->exp_time($value->off_lec);
                                            else if($value->off_lab) $teaching_overload_total += $this->attconfirm_model->exp_time($value->off_lab);
                                            else if($value->off_admin) $teaching_overload_total += $this->attconfirm_model->exp_time($value->off_admin);
                                            else if($value->off_overload) $teaching_overload_total += $this->attconfirm_model->exp_time($value->off_overload);
                                        }else{
                                            $off_lec_total += $this->attconfirm_model->exp_time($value->off_lec);
                                            $off_lab_total += $this->attconfirm_model->exp_time($value->off_lab);
                                            $off_admin_total += $this->attconfirm_model->exp_time($value->off_admin);
                                            $off_overload_total += $this->attconfirm_model->exp_time($value->off_overload);

                                            $lateut_lec_total += $this->attconfirm_model->exp_time($value->lateut_lec);
                                            $lateut_lab_total += $this->attconfirm_model->exp_time($value->lateut_lab);
                                            $lateut_admin_total += $this->attconfirm_model->exp_time($value->lateut_admin);
                                            $lateut_overload_total += $this->attconfirm_model->exp_time($value->lateut_overload);

                                            $absent_lec_total += $this->attconfirm_model->exp_time($value->absent_lec);
                                            $absent_lab_total += $this->attconfirm_model->exp_time($value->absent_lab);
                                            $absent_admin_total += $this->attconfirm_model->exp_time($value->absent_admin);
                                        }
                                            

                                        $twr_lec_total += $this->attconfirm_model->exp_time($value->twr_lec);
                                        $twr_lab_total += $this->attconfirm_model->exp_time($value->twr_lab);
                                        $twr_admin_total += $this->attconfirm_model->exp_time($value->twr_admin);
                                        $twr_overload_total += $this->attconfirm_model->exp_time($value->twr_overload);

                                        // if($value->teaching_overload != "" && $value->teaching_overload != "--") $teaching_overload_total += $this->attconfirm_model->exp_time($value->teaching_overload);

                                        if($counter == 0){
                                            if($value->vacation != ""){
                                                $vacation_total += $value->vacation;
                                                $leave_project = $value->vacation;
                                            }

                                            if($value->emergency != ""){
                                                $emergency_total += $value->emergency;
                                                $leave_project = $value->emergency;
                                            }
                                            if($value->sick != ""){
                                                $sick_total += $value->sick;
                                                $leave_project = $value->sick;
                                            }
                                            if($value->other != ""){
                                                $other_total += $value->other;
                                                $leave_project = $value->other;
                                            }
                                        }

                                        $ot_regular_total += $this->attconfirm_model->exp_time($value->ot_regular);
                                        $ot_restday_total += $this->attconfirm_model->exp_time($value->ot_restday);
                                        $ot_holiday_total += $this->attconfirm_model->exp_time($value->ot_holiday);

                                        $daily_overtime = $this->attconfirm_model->exp_time($value->ot_holiday) + $this->attconfirm_model->exp_time($value->ot_restday) + $this->attconfirm_model->exp_time($value->ot_regular);

                                        $ot_list_tmp = $this->attconfirm_model->getOvertime($employeeid,$value->date,true,$value->holiday_type);
                                        $ot_list = $this->attconfirm_model->constructOTlist($ot_list,$ot_list_tmp);

                                        if($value->ot_regular){
                                            list($overtime_amount, $daily_overtime_mode) = $this->attconfirm_model->getOvertimeAmountDetailed($employeeid, $ot_list, $this->attconfirm_model->exp_time($value->ot_regular));
                                            $daily_overtime_amount += $overtime_amount;
                                        }else if($value->ot_restday){
                                            list($overtime_amount, $daily_overtime_mode) = $this->attconfirm_model->getOvertimeAmountDetailed($employeeid, $ot_list, $this->attconfirm_model->exp_time($value->ot_restday));
                                            $daily_overtime_amount += $overtime_amount;
                                        }else if($value->ot_holiday){
                                            list($overtime_amount, $daily_overtime_mode) = $this->attconfirm_model->getOvertimeAmountDetailed($employeeid, $ot_list, $this->attconfirm_model->exp_time($value->ot_holiday));
                                            $daily_overtime_amount += $overtime_amount;
                                        }

                                        

                                        if($value->service_credit != "" && $value->service_credit != "--") $service_credit_total += $value->service_credit;
                                        if($value->cto != "" && $value->cto != "--") $cto_total +=  $this->attconfirm_model->exp_time($value->cto);

                                        $holiday_lec_total += $this->attconfirm_model->exp_time($value->holiday_lec);
                                        $holiday_lab_total += $this->attconfirm_model->exp_time($value->holiday_lab);
                                        $holiday_admin_total += $this->attconfirm_model->exp_time($value->holiday_admin);
                                        $holiday_overload_total += $this->attconfirm_model->exp_time($value->holiday_overload);

                                        if($value->holiday && $counter == 0) $holiday_total++;

                                        if($value->absent_lec && $this->time->hoursToMinutes($value->absent_lec) > 0) $is_absent++;
                                        else if($value->absent_lab && $this->time->hoursToMinutes($value->absent_lab) > 0) $is_absent++;
                                        else if($value->absent_admin && $this->time->hoursToMinutes($value->absent_admin) > 0) $is_absent++;
                                        $date = $value->date;

                                        if($value->off_time_in != "--" && $value->off_time_out != "--" && $value->rowspan != 0){
                                            if($value->off_lec){
                                                if($value->lateut_lec && !$isnodtr){
                                                    if($value->lateut_remarks == "late") $daily_late += $this->attconfirm_model->exp_time($value->lateut_lec);
                                                    else if($value->lateut_remarks == "undertime") $daily_undertime += $this->attconfirm_model->exp_time($value->lateut_lec);
                                                }
                                                if(!$isnodtr) $daily_absents += $this->attconfirm_model->exp_time($value->absent_lec);
                                                $daily_lec .= "work_hours"."=".$this->attconfirm_model->exp_time($value->off_lec)."/late_hours=".(!$isnodtr && $value->lateut_lec ? $value->lateut_lec : 0)."/deduc_hours=".(!$isnodtr && $value->absent_lec ? $value->absent_lec : 0)."/aimsdept=".$value->aims_dept."/suspension=/classification_id=".$value->classification_id;

                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['work_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['work_hours'] = 0;
                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['late_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['late_hours'] = 0;
                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['deduc_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['deduc_hours'] = 0;
                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['leave_project'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['leave_project'] = 0;

                                                $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['work_hours'] += ($value->classification_id == 1 ? $this->attconfirm_model->exp_time($value->twr_lec) : $this->attconfirm_model->exp_time($value->off_lec));
                                                $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['late_hours'] += $this->attconfirm_model->exp_time($value->lateut_lec);
                                                $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['deduc_hours'] += $this->attconfirm_model->exp_time($value->absent_lec);
                                                $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['leave_project'] += $leave_project;
                                            }else if($value->off_lab){
                                                if($value->lateut_lab && !$isnodtr){
                                                    if($value->lateut_remarks == "late") $daily_late += $this->attconfirm_model->exp_time($value->lateut_lab);
                                                    else if($value->lateut_remarks == "undertime") $daily_undertime += $this->attconfirm_model->exp_time($value->lateut_lab);
                                                }
                                                if(!$isnodtr) $daily_absents += $this->attconfirm_model->exp_time($value->absent_lab);
                                                $daily_lab .= "work_hours"."=".$this->attconfirm_model->exp_time($value->off_lab)."/late_hours=".(!$isnodtr && $value->lateut_lab ? $value->lateut_lab : '')."/deduc_hours=".(!$isnodtr && $value->absent_lab  ? $value->absent_lab : 0)."/aimsdept=".$value->aims_dept."/suspension=/classification_id=".$value->classification_id;

                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['work_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['work_hours'] = 0;
                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['late_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['late_hours'] = 0;
                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['deduc_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['deduc_hours'] = 0;
                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['leave_project'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['leave_project'] = 0;

                                                $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['work_hours'] += ($value->classification_id == 1 ? $this->attconfirm_model->exp_time($value->twr_lab) : $this->attconfirm_model->exp_time($value->off_lab));
                                                $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['late_hours'] += $this->attconfirm_model->exp_time($value->lateut_lab);
                                                $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['deduc_hours'] += $this->attconfirm_model->exp_time($value->absent_lab);
                                                $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['leave_project'] += $leave_project;
                                            }else if($value->off_admin){
                                                if($value->lateut_admin && !$isnodtr){
                                                    if($value->lateut_remarks == "late") $daily_late += $this->attconfirm_model->exp_time($value->lateut_admin);
                                                    else if($value->lateut_remarks == "undertime") $daily_undertime += $this->attconfirm_model->exp_time($value->lateut_admin);
                                                }
                                                if(!$isnodtr) $daily_absents += $this->attconfirm_model->exp_time($value->absent_admin);
                                                $daily_admin .= "work_hours"."=".$this->attconfirm_model->exp_time($value->off_admin)."/late_hours=".(!$isnodtr && $value->lateut_admin ? $value->lateut_admin : 0)."/deduc_hours=".(!$isnodtr && $value->absent_admin ? $value->absent_admin : 0)."/aimsdept=".$value->aims_dept."/suspension=/classification_id=".$value->classification_id;

                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['work_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['work_hours'] = 0;
                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['late_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['late_hours'] = 0;
                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['deduc_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['deduc_hours'] = 0;
                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['leave_project'])) $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['leave_project'] = 0;

                                                $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['work_hours'] += ($value->classification_id == 1 ? $this->attconfirm_model->exp_time($value->twr_admin) : $this->attconfirm_model->exp_time($value->off_admin));
                                                $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['late_hours'] += $this->attconfirm_model->exp_time($value->lateut_admin);
                                                $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['deduc_hours'] += $this->attconfirm_model->exp_time($value->absent_admin);
                                                $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['leave_project'] += $leave_project;
                                            }else if($value->off_overload){
                                                if($value->lateut_overload && !$isnodtr){
                                                    if($value->lateut_remarks == "late") $daily_late += $this->attconfirm_model->exp_time($value->lateut_overload);
                                                    else if($value->lateut_remarks == "undertime") $daily_undertime += $this->attconfirm_model->exp_time($value->lateut_overload);
                                                }
                                                if(!$isnodtr) $daily_overload .= "work_hours"."=".$this->attconfirm_model->exp_time($value->off_overload)."/late_hours=".(!$isnodtr && $value->lateut_overload  ? $value->lateut_overload : 0)."/deduc_hours=/aimsdept=".$value->aims_dept."/suspension=/classification_id=".$value->classification_id;

                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['work_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['work_hours'] = 0;
                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['late_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['late_hours'] = 0;
                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['deduc_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['deduc_hours'] = 0;
                                                if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['leave_project'])) $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['leave_project'] = 0;

                                                $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['work_hours'] +=  ($value->classification_id == 1 ? $this->attconfirm_model->exp_time($value->twr_overload) : $this->attconfirm_model->exp_time($value->off_overload));
                                                $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['late_hours'] += $this->attconfirm_model->exp_time($value->lateut_overload);
                                                $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['leave_project'] += $leave_project;
                                            }
                                        }
                                        $counter++;
                                    }
                                    

                                    if($is_absent > 0 && $is_absent == count($att_date) && ($rowspan != 0 && $rowspan != NULL && $rowspan != "")){
                                        $total_absent++;
                                        $day_absent = substr($date, 5);
                                        $daily_absent .= $day_absent." 1/";
                                    }else{
                                        if($daily_present == "") $daily_present = $date;
                                        else $daily_present .= ",".$date;
                                    }
                                    $this->db->query("DELETE FROM employee_attendance_detailed WHERE employeeid='$employeeid' AND sched_date='$date'");

                                    $save_data = array(
                                        "employeeid" => $employeeid,
                                        "sched_date" => $date,
                                        "overtime"   =>  ($daily_overtime ? $this->attconfirm_model->sec_to_hm($daily_overtime) : ''),
                                        "late"       =>  ($daily_late ? $this->attconfirm_model->sec_to_hm($daily_late) : ''),
                                        "undertime"  =>  ($daily_undertime ? $this->attconfirm_model->sec_to_hm($daily_undertime) : ''),
                                        "absents"    => ($daily_absents ? $this->attconfirm_model->sec_to_hm($daily_absents) : ''),
                                        "ot_amount"    => $daily_overtime_amount,
                                        "ot_type"    => $daily_overtime_mode,
                                        "lec"    => $daily_lec,
                                        "lab"    => $daily_lab,
                                        "admin"    => $daily_admin,
                                        "rle"    => $daily_overload
                                    );
                                    
                                    $this->db->insert("employee_attendance_detailed", $save_data);
                                } // foreach ($attendance as $att_date)
                                if($isnodtr){
                                    $lateut_lec_total = $lateut_lab_total = $lateut_admin_total = $lateut_overload_total = $absent_lec_total = $absent_lab_total = $absent_admin_total = $daily_absent = "";
                                }
                                $tabsent = '{';
                                if($absent_lec_total) $tabsent .= '"LEC":'.$this->time->hoursToMinutes($this->attconfirm_model->sec_to_hm($absent_lec_total));
                                if($absent_lab_total) $tabsent .= ($tabsent != '{' ? ',' : '').'"LAB":'.$this->time->hoursToMinutes($this->attconfirm_model->sec_to_hm($absent_lab_total));
                                if($absent_admin_total) $tabsent .= ($tabsent != '{' ? ',' : '').'"ADMIN":'.$this->time->hoursToMinutes($this->attconfirm_model->sec_to_hm($absent_admin_total));
                                $tabsent .= '}';
                                $query = $this->db->query("SELECT * FROM attendance_confirmed WHERE cutoffstart='$dfrom' AND cutoffend='$dto' AND employeeid='$employeeid'");
                                if($query->num_rows() == 0){
                                    $base_id = '';
                                    $lateut_lec_total = $lateut_lec_total ? $this->attconfirm_model->sec_to_hm($lateut_lec_total) : 0;
                                    $lateut_lab_total = $lateut_lab_total ? $this->attconfirm_model->sec_to_hm($lateut_lab_total) : 0;
                                    $lateut_admin_total = $lateut_admin_total ? $this->attconfirm_model->sec_to_hm($lateut_admin_total) : 0;
                                    $lateut_overload_total = $lateut_overload_total ? $this->attconfirm_model->sec_to_hm($lateut_overload_total) : 0;

                                    $absent_lec_total = $absent_lec_total ? $this->attconfirm_model->sec_to_hm($absent_lec_total) : 0;
                                    $absent_lec_total = $absent_lec_total ? $this->attconfirm_model->sec_to_hm($absent_lec_total) : 0;
                                    $absent_admin_total = $absent_admin_total ? $this->attconfirm_model->sec_to_hm($absent_admin_total) : 0;

                                    $off_lec_total = $off_lec_total ? $this->attconfirm_model->sec_to_hm($off_lec_total) : 0;
                                    $off_lab_total = $off_lab_total ? $this->attconfirm_model->sec_to_hm($off_lab_total) : 0;
                                    $off_admin_total = $off_admin_total ? $this->attconfirm_model->sec_to_hm($off_admin_total) : 0;
                                    $off_overload_total = $off_overload_total ? $this->attconfirm_model->sec_to_hm($off_overload_total) : 0;
                                    $teaching_overload_total = $teaching_overload_total ? $this->attconfirm_model->sec_to_hm($teaching_overload_total) : 0;
                                    $res = $this->db->query("INSERT INTO attendance_confirmed SET 
                                        employeeid = '$employeeid',
                                        cutoffstart = '$dfrom',
                                        cutoffend = '$dto',
                                        overload = '',
                                        substitute = '',
                                        workhours_lec = '$off_lec_total',
                                        workhours_lab = '$off_lab_total',
                                        workhours_admin = '$off_admin_total',
                                        workhours_rle = '$off_overload_total',
                                        latelec = '$lateut_lec_total',
                                        latelab = '$lateut_lab_total',
                                        lateadmin = '$lateut_admin_total',
                                        laterle = '$lateut_overload_total',
                                        absent = '$total_absent',
                                        tabsent = '$tabsent',
                                        day_absent = '$daily_absent',
                                        day_present = '$daily_present',
                                        vleave = '$vacation_total',
                                        eleave = '$emergency_total',
                                        sleave = '$sick_total',
                                        oleave = '$other_total',
                                        deduclec = '$absent_lec_total',
                                        deduclab = '$absent_lab_total',
                                        deducadmin = '$absent_admin_total',
                                        date_processed = '". date("Y-m-d H:i:s") ."',
                                        payroll_cutoffstart = '$payroll_start',
                                        payroll_cutoffend = '$payroll_end',
                                        quarter = '$payroll_quarter',
                                        hold_status = '',
                                        hold_status_change = '',
                                        f_dtrend = '$dto',
                                        f_payrollend = '$payroll_end',
                                        tholiday = '$holiday_total',
                                        tsuspension = '',
                                        t_overload = '$teaching_overload_total'");
                                    if($res) $base_id = $this->db->insert_id();

                                    foreach ($workhours_arr as $aimsdept => $classification_arr) {
                                        foreach ($classification_arr as $classification => $leclab_arr) {
                                            foreach ($leclab_arr as $type => $sec) {
                                                $work_hours = $this->attconfirm_model->sec_to_hm($sec['work_hours']);
                                                $late_hours = $this->attconfirm_model->sec_to_hm($sec['late_hours']);
                                                $deduc_hours = $this->attconfirm_model->sec_to_hm($sec['deduc_hours']);
                                                $leave_project = $this->attconfirm_model->sec_to_hm($sec['leave_project']);
                                                $this->db->query("INSERT INTO workhours_perdept (base_id, work_hours, work_days , late_hours, deduc_hours, type, aimsdept,leave_project, classification) VALUES ('$base_id','$work_hours',0,'$late_hours','$deduc_hours','$type','$aimsdept','$leave_project','$classification')");

                                            }
                                        }
                                    }
                                }
                            }else{
                                if(!isset($workhours_arr['']['ADMIN']['work_hours'])) $workhours_arr['']['ADMIN']['work_hours'] = 0;
                                if(!isset($workhours_arr['']['ADMIN']['late_hours'])) $workhours_arr['']['ADMIN']['late_hours'] = 0;
                                if(!isset($workhours_arr['']['ADMIN']['deduc_hours'])) $workhours_arr['']['ADMIN']['deduc_hours'] = 0;
                                if(!isset($workhours_arr['']['ADMIN']['leave_project'])) $workhours_arr['']['ADMIN']['leave_project'] = 0;
                                $startdate = $enddate = $quarter = $isnodtr = "";
                                $payrollcutoff = $this->attconfirm_model->getPayrollCutoff($dfrom, $dto);
                                foreach($payrollcutoff as $cutoff_info){
                                    $startdate = $cutoff_info['startdate'];
                                    $enddate = $cutoff_info['enddate'];
                                    $quarter = $cutoff_info['quarter'];
                                    $isnodtr = $cutoff_info['nodtr'];
                                }
                                $attendance = $this->attconfirm_model->getAttendanceNonteaching($employeeid, $dfrom, $dto);
                                $not_included_ol = array("ABSENT", "EL", "VL", "SL", "CORRECTION");
                                $off_time_in = $off_time_out = $off_time_total = $actlog_time_in = $actlog_time_out = $terminal = $twr_total = $ot_regular_total = $ot_restday_total = $ot_holiday_total = $late_total = $undertime_total = $vl_deduc_late_total = $vl_deduc_undertime_total = $absent_data_total = $service_credit_total = $cto_credit_total = $vacation_total = $sick_total = $other_total = $emergency_total = $holiday_total = $total_holiday = $total_absent = $workdays = 0;
                                $daily_overtime_amount = 0;
                                $daily_absent = "";
                                foreach ($attendance as $att_date) {
                                    $counter = $daily_overtime = $daily_undertime = $daily_late = $daily_absents = $daily_overtime_amount =  0;
                                    $rowspan = 0;
                                    $is_absent = 0;
                                    $date = $daily_overtime_mode = "";
                                    $ot_list = array();
                                    foreach ($att_date as $key => $value) {
                                        $date = $value->date;
                                        $leave_project = 0;
                                        $rowspan = $value->rowspan;
                                        if($counter == 0){
                                            $twr_total += $this->attconfirm_model->exp_time($value->twr);
                                            $ot_regular_total += $this->attconfirm_model->exp_time($value->ot_regular);
                                            $ot_restday_total += $this->attconfirm_model->exp_time($value->ot_restday);
                                            $ot_holiday_total += $this->attconfirm_model->exp_time($value->ot_holiday);
                                            if($value->other != "" && $value->other != "--" && (!in_array($value->other, $not_included_ol) && $value->other && $value->other!="DIRECT")){
                                                if($other_total == 0.5){
                                                    $other_total += 0.5;
                                                    $leave_project = 0.5;
                                                }
                                                else{
                                                    $other_total += 1;
                                                    $leave_project = 1;
                                                }
                                            }

                                            $daily_overtime += $this->attconfirm_model->exp_time($value->ot_holiday) + $this->attconfirm_model->exp_time($value->ot_restday) + $this->attconfirm_model->exp_time($value->ot_regular);
                                            
                                            if($value->holiday){
                                                $holiday_total++;
                                                $total_holiday += $this->attconfirm_model->exp_time($value->twr);
                                            }
                                            $off_time_total += $this->attconfirm_model->exp_time($value->off_time_total);
                                            if($value->vl != "" && $value->vl != "--"){
                                                $vacation_total += $value->vl;
                                                $leave_project = $value->vl;
                                            }
                                            if($value->sl != "" && $value->sl != "--"){
                                                $sick_total += $value->sl;
                                                $leave_project = $value->sl;
                                            }

                                            if($value->el != "" && $value->el != "--"){
                                                $emergency_total += $value->el;
                                                $leave_project = $value->el;
                                            }
                                        }

                                        
                                        $ot_list_tmp = $this->attconfirm_model->getOvertime($employeeid,$value->date,true,$value->holiday_type);
                                        $ot_list = $this->attconfirm_model->constructOTlist($ot_list,$ot_list_tmp);
                                        
                                        $daily_undertime += $this->attconfirm_model->exp_time($value->undertime);
                                        $daily_late += $this->attconfirm_model->exp_time($value->late);
                                        $daily_absents += $this->attconfirm_model->exp_time($value->absent);
                                        if($value->ot_regular){
                                            list($overtime_amount, $daily_overtime_mode) = $this->attconfirm_model->getOvertimeAmountDetailed($employeeid, $ot_list, $this->attconfirm_model->exp_time($value->ot_regular));
                                            $daily_overtime_amount += $overtime_amount;
                                        }else if($value->ot_restday){
                                            list($overtime_amount, $daily_overtime_mode) = $this->attconfirm_model->getOvertimeAmountDetailed($employeeid, $ot_list, $this->attconfirm_model->exp_time($value->ot_restday));
                                            $daily_overtime_amount += $overtime_amount;
                                        }else if($value->ot_holiday){
                                            list($overtime_amount, $daily_overtime_mode) = $this->attconfirm_model->getOvertimeAmountDetailed($employeeid, $ot_list, $this->attconfirm_model->exp_time($value->ot_holiday));
                                            $daily_overtime_amount += $overtime_amount;
                                        }
                                        if($value->absent) $is_absent++;

                                        $workhours_arr['']['ADMIN']['work_hours'] += $this->attconfirm_model->exp_time($value->off_time_total);
                                        $workhours_arr['']['ADMIN']['late_hours'] += $this->attconfirm_model->exp_time($value->late) + $this->attconfirm_model->exp_time($value->undertime);
                                        $workhours_arr['']['ADMIN']['deduc_hours'] += $this->attconfirm_model->exp_time($value->absent);
                                        $workhours_arr['']['ADMIN']['leave_project'] += $leave_project;

                                        $late_total += $this->attconfirm_model->exp_time($value->late);
                                        $undertime_total += $this->attconfirm_model->exp_time($value->undertime);
                                        $vl_deduc_late_total += $this->attconfirm_model->exp_time($value->vl_deduc_late);
                                        $vl_deduc_undertime_total += $this->attconfirm_model->exp_time($value->vl_deduc_undertime);
                                        $absent_data_total += $this->attconfirm_model->exp_time($value->absent);

                                        if($value->service_credit != "" && $value->service_credit != "--") $service_credit_total += $value->service_credit;
                                        if($value->cto != "" && $value->cto != "--") $cto_credit_total +=  $this->attconfirm_model->exp_time($value->cto);
                                        $counter++;
                                    }

                                    if($is_absent > 0 && $is_absent == count($att_date) && ($rowspan != 0 && $rowspan != NULL && $rowspan != "")){
                                        $total_absent++;
                                        $day_absent = substr($date, 5);
                                        // echo "<pre>"; print_r($date); 
                                        $daily_absent .= $day_absent." 1/";
                                    }

                                    if($rowspan != 0 && $rowspan != NULL && $rowspan != ""){
                                        $workdays++;
                                    }

                                    $this->db->query("DELETE FROM employee_attendance_detailed WHERE employeeid='$employeeid' AND sched_date='$date'");

                                    $save_data = array(
                                        "employeeid" => $employeeid,
                                        "sched_date" => $date,
                                        "overtime"   =>  ($daily_overtime ? $this->attconfirm_model->sec_to_hm($daily_overtime) : ''),
                                        "late"       =>  ($daily_late ? $this->attconfirm_model->sec_to_hm($daily_late) : ''),
                                        "undertime"  =>  ($daily_undertime ? $this->attconfirm_model->sec_to_hm($daily_undertime) : ''),
                                        "absents"    => ($daily_absents ? $this->attconfirm_model->sec_to_hm($daily_absents) : ''),
                                        "ot_amount"    => $daily_overtime_amount,
                                        "ot_type"    => $daily_overtime_mode
                                    );
                                    
                                    $this->db->insert("employee_attendance_detailed", $save_data);
                                }

                                if($isnodtr){
                                    $late_total = $undertime_total = $absent_data_total = "";
                                }
                                $query = $this->db->query("SELECT * FROM attendance_confirmed_nt WHERE cutoffstart='$dfrom' AND cutoffend='$dto' AND employeeid='$employeeid'");
                                if($query->num_rows() == 0){
                                    $base_id = "";
                                    $ot_regular_total = $ot_regular_total ? $this->attconfirm_model->sec_to_hm($ot_regular_total) : '';
                                    $ot_restday_total = $ot_restday_total ? $this->attconfirm_model->sec_to_hm($ot_restday_total) : '';
                                    $ot_holiday_total = $ot_holiday_total ? $this->attconfirm_model->sec_to_hm($ot_holiday_total) : '';

                                    $late_total = $late_total ? $this->attconfirm_model->sec_to_hm($late_total) : '';
                                    $undertime_total = $undertime_total ? $this->attconfirm_model->sec_to_hm($undertime_total) : '';
                                    $absent_data_total = $absent_data_total ? $this->attconfirm_model->sec_to_hm($absent_data_total) : '';
                                    $res = $this->db->query("INSERT INTO attendance_confirmed_nt SET 
                                        employeeid = '$employeeid',
                                        cutoffstart = '$dfrom',
                                        cutoffend = '$dto',
                                        workdays = '$workdays',
                                        otreg = '$ot_regular_total',
                                        otrest = '$ot_restday_total',
                                        othol = '$ot_holiday_total',
                                        lateut = '$late_total',
                                        ut = '$undertime_total',
                                        absent = '$absent_data_total',
                                        day_absent = '$total_absent',
                                        eleave = '$emergency_total',
                                        vleave = '$vacation_total',
                                        sleave = '$sick_total',
                                        oleave = '$other_total',
                                        status = 'SUBMITTED',
                                        isholiday = '$holiday_total',
                                        forcutoff = '1',
                                        payroll_cutoffstart = '$startdate',
                                        payroll_cutoffend = '$enddate',
                                        quarter = '$quarter',
                                        date_processed = '". date("Y-m-d h:i:s") ."',
                                        usertype = '$usertype',
                                        scleave = '$service_credit_total',
                                        cto = '$cto_credit_total'");
                                    if($res){
                                        $base_id = $this->db->insert_id();
                                        foreach ($workhours_arr as $aimsdept => $leclab_arr) {
                                            foreach ($leclab_arr as $type => $sec) {
                                                $work_hours = $this->attconfirm_model->sec_to_hm($sec['work_hours']);
                                                $late_hours = $this->attconfirm_model->sec_to_hm($sec['late_hours']);
                                                $deduc_hours = $this->attconfirm_model->sec_to_hm($sec['deduc_hours']);
                                                $this->db->query("INSERT INTO workhours_perdept_nt (base_id, work_hours, work_days, late_hours, deduc_hours, type, aimsdept) VALUES ('$base_id', '$work_hours', 0,'$late_hours','$deduc_hours','$type','$aimsdept')");
                                            }
                                        }

                                        foreach ($ot_list as $ot_data_tmp){
                                            $ot_data = $ot_data_tmp;
                                            $ot_data["base_id"] = $base_id;

                                            $this->db->insert('attendance_confirmed_nt_ot_hours', $ot_data);
                                        }
                                    }
                                }
                            }
                        } // foreach (explode(",", $emp_list) as $employeeid)
                        // return true;
                    }
                }
            }
        }
        $this->worker_model->updateAttConfirmStatus($details->id, "done");
    }	

    public function empAttDaily($employeeid, $date){
		$isteaching = $this->attconfirm_model->getempteachingtype($employeeid);
		$teaching_related = $this->attconfirm_model->isTeachingRelated($employeeid);
		if($isteaching){
			$this->attconfirm_model->employeeAttendanceTeaching($employeeid, $date);
		}else{
			if($teaching_related){
				$this->attconfirm_model->employeeAttendanceTeaching($employeeid, $date);
			}else{
				$this->attconfirm_model->employeeAttendanceNonteaching($employeeid, $date);
			}
		}
	}
}