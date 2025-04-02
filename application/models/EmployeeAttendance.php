<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class EmployeeAttendance extends CI_Model {

    public function employeeAttendanceTeaching($employeeid, $date){
        $this->load->model("ob_application");
        $deptid = $this->employee->getindividualdept($employeeid);
        $classification_arr = $this->extensions->getFacultyLoadsClassfication();
        $classification_list = array();
        foreach ($classification_arr as $key => $value) {
            $classification_list[$value->id] =  strtolower($value->description);
        }
        $edata = "NEW";
        $x = 0;
        $not_included_ol = array("ABSENT", "EL", "VL", "SL", "CORRECTION");
        $cto_id_list = $sc_app_id_list = array();
        $firstDayOfWeek = $this->attcompute->getFirstDayOfWeek($employeeid);
        $lastDayOfWeek = $this->attcompute->getLastDayOfWeek($employeeid);
        $lab_holhours = $lec_holhours = $admin_holhours = $rle_holhours = $holiday_type = "";
        $subtotaltpdlec = $totaltpdlec = $subtotaltpdlab = $totaltpdlab = $subtotaltpdadmin = $totaltpdadmin = $subtotaltpdrle = $totaltpdrle = 0;
        $weeklyOverload = $weeklyATH = $weeklyTotalOverload = 0;
        $rendered_lec = $rendered_lab = $rendered_admin = $rendered_rle = $t_rendered_lec = $t_rendered_lab = $t_rendered_admin = $t_rendered_rle = $vacation = $emergency = $sick = $other = 0;
        $lateutlec = $lateutlab = $lateutadmin = $tschedlec = $tschedlab = $tschedadmin = $lateutrle = $tschedrle = $absent =  $tpdlab = $tpdlec = $tpdrle = 0;
        $ot_remarks = $sc_app_remarks = $wfh_app_remarks = $seminar_app_remarks = $tempabsent = "";
        $hasLog = $isSuspension = false;
        list($ath, $overload_limit) = $this->attcompute->getEmployeeATH($employeeid);
        $ath = 60 * $ath;
        $overload_limit = 60*$overload_limit;
        if(date("l",strtotime($date) != $firstDayOfWeek))
        {
            // $tempOverload = $this->attcompute->getPastDayOverload($employeeid,$date, $firstDayOfWeek,"NEW");
            $tempOverload = 0;
        }

        $used_time = array();
        $isCreditedHoliday = false;
        $firstDate = true;

        if($firstDayOfWeek == date("l",strtotime($date))){
            $weeklyOverload = $weeklyATH = $weeklyTotalOverload = 0;
        }

        $is_holiday_halfday = false;
        $isAffectedAfter = false;
        $is_half_holiday = true;
        $has_after_suspension = false;
        $has_last_log = false;
        $display_hol_remarks = false;
        $isSuspension = false;
        $isRegularHoliday = false;
        $ishalfday = false;

        $holidayInfo = array();

        /*get campus where employee tap*/
        $campus_tap = $this->attendance->getTapCampus($employeeid, $date);
        $deviceTap = $this->attendance->isFacial($employeeid, $date);
        $rate = 0;
        // Holiday
        $holiday = $this->attcompute->isHolidayNew($employeeid,$date,$deptid); 
        if($holiday){
            $holidayInfo = $this->attcompute->holidayInfo($date);
            if(isset($holidayInfo['holiday_type'])){
                $holiday_type = $holidayInfo['holiday_type'];
                if($holidayInfo['holiday_type']==3) $isSuspension = true;
                if($holidayInfo['holiday_type']==5) $isSuspension = true;
                if($holidayInfo['holiday_type']==9) $isRegularHoliday = true;
                $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
            }
        }

        $dispLogDate = date("d-M (l)",strtotime($date));

        $sched = $this->attcompute->displaySched($employeeid,$date);

        $countrow = $sched->num_rows();

        $isValidSchedule = true;

        if($countrow > 0){
            if($sched->row(0)->starttime == '00:00:00' && $sched->row(0)->endtime == '00:00:00') $isValidSchedule = false;
        }

        if($x%2 == 0)   $color = " style='background-color: white;'";
        else            $color = " style='background-color: white;'";
        $x++;

        if($firstDate && $holiday){
            $firstDate = false;
        }

        if($countrow > 0 && $isValidSchedule){
            $haswholedayleave = false;
            $hasleavecount = 0;

            ///< for validation of holiday (will only be credited if not absent during last schedule)
            $hasLogprev = $hasLog;
            $hasLog = false;

            if($hasLogprev || $isSuspension)    $isCreditedHoliday = true;
            else                                $isCreditedHoliday = false;
            $schedule_result = $sched->result();

            $tempsched = "";
            $seq = 0;
            $isFirstSched = true;
            $remark_list = array();
            $between_overload = 0;
            $presentLastLog = false;
            // echo "<pre>"; print_r($schedule_result); die;

            foreach($schedule_result as $rschedkey => $rsched){
                $off_time_in = $off_time_out = $off_lec = $off_lab = $off_admin = $off_overload = $actlog_time_in = $actlog_time_out = $terminal = $twr_lec = $twr_lab = $twr_admin = $twr_overload = $aims_dept = $campus_name = $subject = $teaching_overload = $ot_regular = $ot_restday = $ot_holiday = $lateut_lec = $lateut_lab = $lateut_admin = $lateut_overload = $absent_lec = $absent_lab = $absent_admin = $service_credit = $cto_credit = $remarks = $holiday_lec = $holiday_lab = $holiday_admin = $holiday_overload = $holiday_name = $lateut_remarks = $night_shift = "";
                $overload = 0;
                $updatedLogout = 0;
                if($tempsched == $dispLogDate)  $dispLogDate = "";
                $off_time_in = $stime = $rsched->starttime;
                $off_time_out = $etime = $rsched->endtime; 
                $type  = $rsched->leclab;
                $aims_dept = $aimsdept  = $rsched->aimsdept;
                $night_shift  = $rsched->night_shift;
                $campus  = $rsched->campus;
                if($campus == "Select an Option") $campus = "";
                $time1 = new DateTime($stime);
                $time2 = new DateTime($etime);
                $official_in_date = $date.' '.$stime;
                $official_out_date = $date.' '.$etime;
                if($night_shift == 1){
                    $official_out_date = date('Y-m-d', strtotime($date . ' +1 day')).' '.$etime;
                }

                
                $classification = isset($classification_list[$rsched->classification]) ? $classification_list[$rsched->classification] : '';
                $isOverload = $classification == 'overload';
                if($type == "LEC"){
                    $to_time = strtotime($stime);
                    $from_time = strtotime($etime);
                    if($night_shift == 1){
                        $to_time = strtotime($official_in_date);
                        $from_time = strtotime($official_out_date);
                    }
                    $totaltpdlec += round(abs($to_time - $from_time) / 60,2);
                        $subtotaltpdlec = $this->time->minutesToHours(round(abs($to_time - $from_time) / 60,2));
                        $sched_minutes = round(abs($to_time - $from_time) / 60,2);
                    list($tardy, $absent, $early) = $this->attendance->getSubjTimeConfig($sched_minutes, date('Y', strtotime($date)));
                    $tardy_start = date("H:i:s", strtotime('+'.$tardy.' minutes', strtotime($stime)));
                    $absent_start = date("H:i:s", strtotime('+'.$absent.' minutes', strtotime($stime)));
                    $earlydismissal = date("H:i:s", strtotime('+'.$early.' minutes', strtotime($stime)));
                    
                    $subtotaltpdlab = "";
                    $subtotaltpdadmin = "";
                    $subtotaltpdrle = "";
                }else if($type == "LAB"){
                    $to_time = strtotime($stime);
                    $from_time = strtotime($etime);
                    if($night_shift == 1){
                        $to_time = strtotime($official_in_date);
                        $from_time = strtotime($official_out_date);
                    }
                    $totaltpdlab += round(abs($to_time - $from_time) / 60,2);
                    $subtotaltpdlab = $this->time->minutesToHours(round(abs($to_time - $from_time) / 60,2));
                    $sched_minutes = round(abs($to_time - $from_time) / 60,2);
                    list($tardy, $absent, $early) = $this->attendance->getSubjTimeConfig($sched_minutes, date('Y', strtotime($date)));
                    $tardy_start = date("H:i:s", strtotime('+'.$tardy.' minutes', strtotime($stime)));
                    $absent_start = date("H:i:s", strtotime('+'.$absent.' minutes', strtotime($stime)));
                    $earlydismissal = date("H:i:s", strtotime('+'.$early.' minutes', strtotime($stime)));
                    
                    $subtotaltpdlec = "";
                    $subtotaltpdadmin = "";
                    $subtotaltpdrle = "";
                }else if($type == "ADMIN"){
                    $to_time = strtotime($stime);
                    $from_time = strtotime($etime);
                    if($night_shift == 1){
                        $to_time = strtotime($official_in_date);
                        $from_time = strtotime($official_out_date);
                    }
                    $totaltpdadmin += round(abs($to_time - $from_time) / 60,2);
                    $subtotaltpdadmin = $this->time->minutesToHours(round(abs($to_time - $from_time) / 60,2));
                    $tardy_start = $rsched->tardy_start;
                    $absent_start = $rsched->absent_start;
                    $earlydismissal = $rsched->early_dismissal;
                    $subtotaltpdlab = "";
                    $subtotaltpdlec = "";
                    $subtotaltpdrle = "";
                }else{
                    $to_time = strtotime($stime);
                    $from_time = strtotime($etime);
                    if($night_shift == 1){
                        $to_time = strtotime($official_in_date);
                        $from_time = strtotime($official_out_date);
                    }
                    $totaltpdrle += round(abs($to_time - $from_time) / 60,2);
                    $subtotaltpdrle = $this->time->minutesToHours(round(abs($to_time - $from_time) / 60,2));
                    $tardy_start = $rsched->tardy_start;
                    $absent_start = $rsched->absent_start;
                    $earlydismissal = $rsched->early_dismissal;
                    $subtotaltpdlab = "";
                    $subtotaltpdlec = "";
                    $subtotaltpdadmin = "";
                }

                $seq += 1;
                // logtime
                // $used_time = array();
                list($login,$logout,$q,$haslog_forremarks,$used_time, $is_ob) = $this->attcompute->displayLogTime($employeeid,$date,$stime,$etime,"NEW",$seq,$absent_start,$earlydismissal,$used_time, $campus, $night_shift, isset($schedule_result[$rschedkey-1]->campus) ? $schedule_result[$rschedkey-1]->campus : '');
                if($seq == $countrow){
                    $weeklyOverloadOT = $this->attcompute->displayLogTimeOutsideOT($employeeid,$date);
                    if($weeklyOverloadOT){
                        $overload += $weeklyOverloadOT;
                        $weeklyOverload += $weeklyOverloadOT;
                    }
                }

                if($login=='0000-00-00 00:00:00') $login = '';
                if($logout=='0000-00-00 00:00:00') $logout = '';

                list($otreg,$otrest,$othol) = $this->attcompute->displayOt($employeeid,$date,true);
                if($otreg || $otrest || $othol){
                    $ot_remarks = "OVERTIME APPLICATION";
                }

                $coc = $this->attcompute->displayCOC($employeeid,$date,true);
                if($coc > 0){
                    if($ot_remarks != "APPROVED COC APPLICATION"){
                        $ot_remarks.=($ot_remarks?", APPROVED COC APPLICATION":"APPROVED COC APPLICATION");
                    }
                }

                $sc_application = $this->attcompute->displaySCAttendance($employeeid,$date, $stime, $etime);
                if($sc_application > 0){
                    if($sc_app_remarks != "Approved Conversion Service Credit"){
                        $sc_app_remarks.=($sc_app_remarks?", Approved Conversion Service Credit":"Approved Conversion Service Credit");
                    }
                }

                // Leave
                list($el,$vl,$sl,$ol,$oltype,$ob,$abs_count,$l_nopay,$obtypes, $ob_id, $l_nopay_remarks)     = $this->attcompute->displayLeave($employeeid,$date,'',$stime,$etime,$seq);
                list($cto, $ctohalf, $cto_id, $cto_sched) = $this->attcompute->displayCTOUsageAttendance($employeeid,$date, $stime, $etime);
                list($sc_app, $sc_app_half, $sc_app_id) = $this->attcompute->displaySCUsageAttendance($employeeid,$date, $stime, $etime);

                $pvl = $this->attcompute->displayPVL($employeeid,$date);
                if($ol == "0.50"){
                    $login = $stime;
                    $logout = $etime;
                    $ishalfday = true;
                }


                if($ol == "DIRECT"){
                    $is_wfh = $this->attcompute->isWfhOB($employeeid,$date);
                    
                    if($is_wfh->num_rows() == 1 && $obtypes==2 ){
                        $ob_id = $is_wfh->row()->aid;
                        $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
                        if($hastime->num_rows() == 0) $ol = $oltype = $ob = "";
                        if($wfh_app_remarks != "Approved Work From Home Application"){
                            $wfh_app_remarks.=($wfh_app_remarks?", Approved Work From Home Application":"Approved Work From Home Application");
                        }
                    }
                }else if($ol == "DA" && $obtypes==3 && $ob_id && $is_ob == $ob_id){
                    $ob_details = $this->ob_application->getLeaveDetails($ob_id);
                    if($ob_details['timefrom'] && $ob_details['timeto']){
                        $login = $ob_details['timefrom'];
                        $logout = $ob_details['timeto'];
                        if($night_shift == 1 && strtotime($login) > strtotime($logout)){
                            $login = date('Y-m-d', strtotime($date)).' '.$login;
                            $logout = date('Y-m-d', strtotime($date . ' +1 day')).' '.$logout;
                            $updatedLogout = 1;
                        }
                        // echo $logout;
                    }
                } else if($ol == "DA" && $obtypes==1 && $ob_id){
                    $ob_details = $this->ob_application->getLeaveDetails($ob_id);
                    if($ob_details['sched_affected']){
                        list($login, $logout) = explode('|', $ob_details['sched_affected']);
                        if($night_shift == 1 && strtotime($login) > strtotime($logout)){
                            $login = date('Y-m-d', strtotime($date)).' '.$login;
                            $logout = date('Y-m-d', strtotime($date . ' +1 day')).' '.$logout;
                            $updatedLogout = 1;
                        }
                    }
                } else if($ol == "CORRECTION" && $ob_id){
                    $ob_details = $this->ob_application->getLeaveDetails($ob_id);
                    if(isset($ob_details['app_id'])){
                        $timerecord = $this->employeemod->findApplyTimeRecord($ob_details['app_id']);

                        if(isset($timerecord[0]->request_time)){
                            $login_logout = explode('-', $timerecord[0]->request_time);
                            $login = isset($login_logout[0]) ? $login_logout[0] : '';
                            $logout = isset($login_logout[1]) ? $login_logout[1] : '';
                            if($night_shift == 1 && strtotime($login) > strtotime($logout)){
                                $login = date('Y-m-d', strtotime($date)).' '.$login;
                                $logout = date('Y-m-d', strtotime($date . ' +1 day')).' '.$logout;
                                $updatedLogout = 1;
                            }
                        }
                    }
                } 

                if($cto && $cto_id){
                    if($ctohalf){
                        list($login, $logout) = explode("|", $cto_sched);
                        if($night_shift == 1 && strtotime($login) > strtotime($logout)){
                            $login = date('Y-m-d', strtotime($date)).' '.$login;
                            $logout = date('Y-m-d', strtotime($date . ' +1 day')).' '.$logout;
                            $updatedLogout = 1;
                        }
                    }else{
                        $login = $stime;
                        $logout = $etime;
                        if($night_shift == 1 && $login > $logout){
                            $login = date('Y-m-d', strtotime($date)).' '.$login;
                            $logout = date('Y-m-d', strtotime($date . ' +1 day')).' '.$logout;
                            $updatedLogout = 1;
                        }
                    }
                }

                $cs_app = $this->attcompute->displayChangeSchedApp($employeeid,$date);
                // echo "<pre>"; print_r($cs_app);
                $pending = $this->attcompute->displayPendingApp($employeeid,$date, "", $ol);
                $pending .= $this->attcompute->displayPendingOBWfh($employeeid,$date);

                // Absent
                $initial_absent = $absent = $this->attcompute->displayAbsent($stime,$etime,$login,$logout,$employeeid,$date,$earlydismissal, $absent_start, $night_shift, $date, $updatedLogout);
                // echo "<pre>"; print_r(array($absent)); die;

                if($oltype == "ABSENT")                 $absent = $absent;
                else if($holiday && $isCreditedHoliday && !$isSuspension) $absent = "";
                if ($vl >= 1 || $pvl >= 1 || $el >= 1 || $sl >= 1 || $ob >= 1 || ($cto && $ctohalf == 0) || ($sc_app && $sc_app_half == 0) || (strpos($oltype, "LEAVE") !== false || $isOverload)){
                    $absent = "";
                    $haswholedayleave = true;
                }
                if ($vl > 0 || $pvl > 0 || $el > 0 || $sl > 0 || $ob > 0 || $cto || $sc_app  || $isOverload){
                    $absent = "";
                    $hasleavecount++;
                }
                
                list($lateutlec,$lateutlab,$lateutadmin,$tschedlec,$tschedlab,$tschedadmin,$lateutrle,$tschedrle, $lateut_rem) = $this->attcompute->displayLateUTNS($stime,$etime,$tardy_start,$login,$logout,$type,$absent, $night_shift, $date, $updatedLogout);
                // echo "<pre>"; print_r(array($lateutlec,$lateutlab,$lateutadmin,$tschedlec,$tschedlab,$tschedadmin,$lateutrle,$tschedrle, $lateut_rem));
                if($el || $vl || $pvl || $sl || ($holiday && $isCreditedHoliday && !$isSuspension) || $cto || $sc_app  || $isOverload){
                    $lateutlec = $lateutlab = $lateutadmin = $tschedlec = $tschedlab = $tschedadmin = $tschedrle = "";
                }
                $negateTW = 0;
                if($absent && $presentLastLog){
                    if($type == "LEC"){
                        $lateutlec = $absent;
                        $absent = $tschedlec = '';
                    }elseif($type == "LAB"){
                        $lateutlab = $absent;
                        $absent = $tschedlab = '';
                    }elseif($type == "ADMIN"){
                        $lateutadmin = $absent;
                        $absent = $tschedadmin = '';
                    }else{
                        $lateutrle = $absent;
                        $absent = $tschedrle = '';
                    }
                    $negateTW = 1;
                    $lateut_rem['undertime'] = 'undertime';
                }
                
                if($absent && !$type) $absent = '';

                if($absent && !$type) $absent = '';

                //Total Hours of Work
                $schedstart   = strtotime($stime);
                $schedend   = strtotime($etime);

                if($holiday){
                    if($this->attcompute->isHolidayWithpay($date) == "YES"){
                        if($tempabsent){
                            $absent = $absent;
                        }
                    }else{
                        if(!$login && !$logout){
                            $absent = $absent;
                        }
                    }
                }else{
                    $tempabsent = true;
                }

                // Overload
                if(!$absent && !$lateutlec){
                    $tempOverload += $this->attcompute->displayOverloadTime($stime,$etime,$lateutlab);
                }else{
                    $tempOverload += 0;
                }//late-under-undertime remarks
                $ob_data = $this->attcompute->displayLateUTAbs($employeeid, $date);

                $log_remarks = '';

                if($absent){
                    if(!$login && !$logout) $log_remarks = 'NO TIME IN AND OUT';
                    elseif(!$login) $log_remarks = 'NO TIME IN';
                    elseif(!$logout) $log_remarks = 'NO TIME OUT';
                }

                if(!$login){
                    $login = $this->timesheet->getNooutData($employeeid, $date);
                }
                
                if($login && $logout && $isFirstSched) $has_last_log = true;

                $is_holiday_halfday = $this->attcompute->isHolidayNew($employeeid, $date,$deptid, "", "on");
                list($fromtime, $totime) = $this->extensions->getHolidayHalfdayTime($date);
                if($is_holiday_halfday && ($fromtime && $totime) ){
                    $holidayInfo = $this->attcompute->holidayInfo($date);
                    $is_half_holiday = true;
                    if(isset($holidayInfo["holiday_type"])){
                        $isAffected = $this->attcompute->affectedBySuspension(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
                        
                        if($isAffected){
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
                                    $isAffectedBefore = $this->attcompute->affectedBySuspensionBefore(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
                                    if($isAffectedBefore){
                                        $rate = 50;
                                        if($has_last_log && !$isFirstSched) $rate = 100;
                                    }

                                    $isAffectedAfter = $this->attcompute->affectedBySuspensionAfter(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
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
                            else $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
                            
                            if(!$login && !$logout) $rate = 0;
                        }

                        if($is_half_holiday){
                            $hol_lec = $this->time->hoursToMinutes($subtotaltpdlec);
                            $hol_lab = $this->time->hoursToMinutes($subtotaltpdlab);
                            $hol_admin = $this->time->hoursToMinutes($subtotaltpdadmin);
                            $hol_rle = $this->time->hoursToMinutes($subtotaltpdrle);

                            $lec_holhours = $hol_lec * $rate / 100;
                            $lab_holhours = $hol_lab * $rate / 100;
                            $admin_holhours = $hol_admin * $rate / 100;
                            $rle_holhours = $hol_rle * $rate / 100;

                            // $totlec_holhours += $lec_holhours;
                            // $totlab_holhours += $lab_holhours;
                            // $totadmin_holhours += $admin_holhours;
                            // $totrle_holhours += $rle_holhours;

                            $lec_holhours = $this->time->minutesToHours($lec_holhours);
                            $lab_holhours = $this->time->minutesToHours($lab_holhours);
                            $admin_holhours = $this->time->minutesToHours($admin_holhours);
                            $rle_holhours = $this->time->minutesToHours($rle_holhours);
                        }
                    }else{
                        $half_holiday = $this->attcompute->holidayHalfdayComputation(date("H:i", strtotime($login)), date("H:i", strtotime($logout)), date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)));
                        if($half_holiday > 0){
                            $lateutlec = $lateutlab = $lateutadmin = $lateutrle = $this->attcompute->sec_to_hm(abs($half_holiday));
                        }else{
                            $lateutlec = $lateutlab = $lateutadmin = $lateutrle = "";
                        }
                    }
                }else{
                    $holidayInfo = $this->attcompute->holidayInfo($date);
                    $is_half_holiday = true;
                    if(isset($holidayInfo["holiday_type"])){

                        $is_half_holiday = true;
                        if($holidayInfo["holiday_type"] == 5) $rate = 50;
                        else $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");

                        if($is_half_holiday){
                            $hol_lec = $this->time->hoursToMinutes($subtotaltpdlec);
                            $hol_lab = $this->time->hoursToMinutes($subtotaltpdlab);
                            $hol_admin = $this->time->hoursToMinutes($subtotaltpdadmin);
                            $hol_rle = $this->time->hoursToMinutes($subtotaltpdrle);

                            $lec_holhours = $hol_lec * $rate / 100;
                            $lab_holhours = $hol_lab * $rate / 100;
                            $admin_holhours = $hol_admin * $rate / 100;
                            $rle_holhours = $hol_rle * $rate / 100;

                            // $totlec_holhours += $lec_holhours;
                            // $totlab_holhours += $lab_holhours;
                            // $totadmin_holhours += $admin_holhours;
                            // $totrle_holhours += $rle_holhours;

                            $lec_holhours = $this->time->minutesToHours($lec_holhours);
                            $lab_holhours = $this->time->minutesToHours($lab_holhours);
                            $admin_holhours = $this->time->minutesToHours($admin_holhours);
                            $rle_holhours = $this->time->minutesToHours($rle_holhours);
                        }
                    }else{
                        $is_half_holiday = false;
                    }
                }

                if($el || $vl || $pvl || $sl || $is_half_holiday || ($holiday && $isCreditedHoliday && !$isSuspension) || $isOverload) { 
                    $totaltpdlec = (int)$totaltpdlec - (int)$tpdlec;
                    $totaltpdlab = (int)$totaltpdlab - (int)$tpdlab;
                    $totaltpdrle = (int)$totaltpdrle - (int)$tpdrle;
                    $lateutlec = $lateutlab = $lateutadmin = $tschedlec = $tschedlab = $tschedadmin = $lateutrle = $tschedrle = $absent =  $tpdlab = $tpdlec = $tpdrle = "";
                }
                
                if($lateutlec == "00:00") $lateutlec = "";
                if($lateutlab == "00:00") $lateutlab = "";
                if($lateutadmin == "00:00") $lateutadmin = "";
                if($lateutrle == "00:00") $lateutrle = "";
                if($subtotaltpdlab == "00:00") $subtotaltpdlab = "";
                if($subtotaltpdlec == "00:00") $subtotaltpdlec = "";
                if($subtotaltpdadmin == "00:00") $subtotaltpdadmin = "";
                if($subtotaltpdrle == "00:00") $subtotaltpdrle = "";
                if($night_shift == 1 || $stime > $etime){
                    if(strtotime($login) > strtotime($date." ".$stime)) $start = strtotime($login);
                    else $start = strtotime($date." ".$stime);
                    if(strtotime($logout  .(strtotime("12:00:00") > strtotime(date("H:i:s",strtotime($logout))) ? '+1 day' : '')) > strtotime(date('Y-m-d H:i:s', strtotime($date." ".$etime . ' +1 day')))) $end = strtotime(date('Y-m-d H:i:s', strtotime($date." ".$etime . ' +1 day')));
                    else $end = strtotime($logout .(strtotime("12:00:00") > strtotime(date("H:i:s",strtotime($logout))) ? '+1 day' : ''));

                    if ($oltype == 'OFFICIAL BUSINESS') {
                        if(strtotime($login) > strtotime($date." ".$stime)) $start = strtotime($login);
                        else $start = strtotime($date." ".$stime);
                        if(strtotime($logout) > strtotime(date('Y-m-d H:i:s', strtotime($date." ".$etime . ' +1 day')))) $end = strtotime(date('Y-m-d H:i:s', strtotime($date." ".$etime . ' +1 day')));
                        else $end = strtotime($logout);
                    }else if($ol == "CORRECTION" && $ob_id){
                        if(strtotime($date." ".$login) > strtotime($date." ".$stime)) $start = strtotime($date." ".$login);
                        else $start = strtotime($date." ".$stime);
                        if(strtotime($logout) > strtotime(date('Y-m-d H:i:s', strtotime($date." ".$etime . ' +1 day')))) $end = strtotime(date('Y-m-d H:i:s', strtotime($date." ".$etime . ' +1 day')));
                        else $end = strtotime($logout);
                    }
                }else if(date('H:i:s', strtotime($logout)) < $stime){
                    if(strtotime($login) > strtotime($date." ".$stime)) $start = strtotime($login);
                    else $start = strtotime($date." ".$stime);
                    $end = strtotime($date." ".$etime);
                }else{
                    if(strtotime($login) > strtotime($date." ".$stime)) $start = strtotime($login);
                    else $start = strtotime($date." ".$stime);
                    if(strtotime($logout) > strtotime($date." ".$etime)) $end = strtotime($date." ".$etime);
                    else $end = strtotime($logout);

                    if ($oltype === 'OFFICIAL BUSINESS') {
                        if(strtotime($date." ".$login) > strtotime($date." ".$stime)) $start = strtotime($date." ".$login);
                        else $start = strtotime($date." ".$stime);
                        if(strtotime($date." ".$logout) > strtotime($date." ".$etime)) $end = strtotime($date." ".$etime);
                        else $end = strtotime($date." ".$logout);
                    }else if($ol === "CORRECTION" && $ob_id){
                        if(strtotime($date." ".$login) > strtotime($date." ".$stime)) $start = strtotime($date." ".$login);
                        else $start = strtotime($date." ".$stime);
                        if(strtotime($date." ".$logout) > strtotime($date." ".$etime)) $end = strtotime($date." ".$etime);
                        else $end = strtotime($date." ".$logout);
                    }

                }
                $mins = ($end - $start) / 60;
                $mins = ceil($mins);



                if(!$end || !$start || $mins < 0 || !$logout || !$login || $absent || $negateTW == 1) $mins = 0;

                if($rsched->leclab == "LEC"){
                    if($isRegularHoliday){
                        $rendered_lec = "0:00";
                    }else{

                        $rendered_lec = $this->time->minutesToHours($mins);
                        $t_rendered_lec += $mins;
                        $weeklyATH += $mins;
                    }
                }
                elseif($rsched->leclab == "LAB"){
                    if($isRegularHoliday){
                        $rendered_lab = "0:00";
                    }else{
                        $rendered_lab = $this->time->minutesToHours($mins);
                        $t_rendered_lab += $mins;
                        $weeklyATH += $mins;
                    }
                }
                elseif($rsched->leclab == "ADMIN"){
                    if($isRegularHoliday){
                        $rendered_admin = "0:00";
                    }else{
                        $rendered_admin = $this->time->minutesToHours($mins);
                        $t_rendered_admin += $mins;
                    }
                }else{
                    if($isRegularHoliday){
                        $rendered_rle = "0:00";
                    }else{
                        $rendered_rle = $this->time->minutesToHours($mins);
                        $t_rendered_rle += $mins;
                    }
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

                if($login && $logout && ($login != $logout) && !$absent){
                    $presentLastLog = true;
                }else{
                    $presentLastLog = false;
                }

                $off_lec = $subtotaltpdlec ? $subtotaltpdlec : "";
                $off_lab = $subtotaltpdlab ? $subtotaltpdlab : "";
                $off_admin = $subtotaltpdadmin ? $subtotaltpdadmin : "";
                // echo "<pre>"; print_r($off_admin); 
                $off_overload = $subtotaltpdrle ? $subtotaltpdrle : "";

                $actlog_time_in = ($login? date("h:i A",strtotime($login)) : "--");
                $actlog_time_out = ($logout? date("h:i A",strtotime($logout)) : "--");

                $terminal = $deviceTap ? $deviceTap : $this->extensions->getTerminalName($campus_tap);

                $twr_lec = $rendered_lec ? $rendered_lec : "";
                $twr_lab = $rendered_lab  ? $rendered_lab : "";
                $twr_admin = $rendered_admin ? $rendered_admin : "";
                $twr_overload = $rendered_rle ? $rendered_rle : "";

                // $aims_dept = $aimsdept ? $this->extensions->getAimsDesc($aimsdept) : "";
                $aims_dept = $aimsdept;

                $campus_name = $this->extensions->getCampusDescription($rsched->campus);

                $subject = $rsched->subject;
                $teaching_overload = ($overload ? $this->time->minutesToHours($overload) : "");
                $ot_regular = ($otreg ? $this->attcompute->sec_to_hm($this->attcompute->exp_time($otreg)) : "");
                $ot_restday = ($otrest ? $this->attcompute->sec_to_hm($this->attcompute->exp_time($otrest)) : "");
                $ot_holiday = ($othol ? $this->attcompute->sec_to_hm($this->attcompute->exp_time($othol)) : "");

                $lateut_lec = $lateutlec ? $lateutlec : "";
                $lateut_lab = $lateutlab ? $lateutlab : "";
                $lateut_admin = $lateutadmin ? $lateutadmin : "";
                $lateut_overload = $lateutrle ? $lateutrle : "";

                $absent_lec = ($tschedlec != "0:00") ? $tschedlec : "";
                $absent_lab = ($tschedlab != "0:00") ? $tschedlab : "";
                $absent_admin = ($tschedadmin != "0:00") ? $tschedadmin : "";

                if($sc_app_half == 1 || !$sc_app){
                    $service_credit = ($sc_app ? $sc_app : "");
                }

                if($ctohalf == 1 || !$cto){
                    $cto_credit = ($cto ? $cto : "");
                }

                $emergency = ($el) ? $vl : "";
                $vacation = ($vl) ? $vl : "";
                $sick = ($sl) ? $sl : "";
                $other = ($ol && !in_array($ol, $not_included_ol)) ? 1 : ""; 

                $rwcount = 1;
                if(!$dispLogDate) $rwcount = 1;
                if($haswholedayleave || $pending || $holiday) $rwcount = $countrow;

                if($dispLogDate || (!$haswholedayleave && !$pending && !$holiday)){
                    if($sc_app_half == 0 && $sc_app){
                        $service_credit = ($sc_app ? $sc_app : "");
                    }

                    if($ctohalf == 0 && $cto){
                        $cto_credit = ($cto ? $cto : "");
                    }

                    if ($ol  === "late"){
                        if ($lateutadmin) {
                            $remarks .= "<h5 style='color:green;'>Excused Late</h5>";
                        }
                    }elseif(($lateutlab != "" || $lateutlec != "" || $lateutrle != "" || $lateutadmin != "" && !$ol)) {
                        foreach ($lateut_rem as $lrkey => $lrvalue) {
                            if($lrkey == "undertime" && !$ishalfday) $remarks .= "<h5 style='color:red;'>Unexcused Undertime</h5>";
                            else if($lrkey == "late" && !$ishalfday) $remarks .= "<h5 style='color:red;'>Unexcused Late</h5>";

                            $lateut_remarks = $lrkey;
                        }
                        
                    }

                    if ($oltype == 'OFFICIAL BUSINESS') {
                        $obType = $this->extensions->getTypeOfOB($employeeid, $date);
                        $oltype = $obType == 'SEMINAR' ? $obType : $oltype;
                    }
                    
                    $remarks .= ($log_remarks?$log_remarks."<br>":"");
                    $remarks .=  ($ot_remarks?$ot_remarks."<br>":"");
                    $remarks .=  ($sc_app_remarks?$sc_app_remarks."<br>":"");
                    $remarks .=  ($wfh_app_remarks?$wfh_app_remarks."<br>":"");
                    $remarks .=  $dispLogDate ? ($cs_app?$cs_app.'<br>':'') : '';
                    $remarks .=  ($pending)?"PENDING ".$pending.'<br>'
                            :($ol ? ($oltype ? ($oltype == "ABSENT" ? "ABSENT W/ FILE<br>" 
                            : ($oltype != "Excuse Slip (late)" ? $oltype.'<br>':"")) : ($l_nopay_remarks ? $l_nopay_remarks : $this->employeemod->othLeaveDesc($ol)).'<br>') 
                            : ($q ? ($q == "1" ? "" : "".'<br>') : ""));
                    $remarks .= (($holiday || $is_half_holiday) && isset($holidayInfo['description']))?$holidayInfo['description']:"";
                    $remarks .= $cto?'APPROVED CTO APPLICATION<br>':'';
                    $remarks .= $sc_app?'USE SERVICE CREDIT<br>':'';
                    $remarks .= ($pvl > 0)?'APPROVED PROPORTIONAL VACATION LEAVE<br>':'';

                    if(isset($schedule_result[$rschedkey + 1]) && $rwcount == $countrow && !$sc_app && !$ol){
                        $stime_ = $schedule_result[$rschedkey + 1]->starttime;
                        $etime_ = $schedule_result[$rschedkey + 1]->endtime; 
                        $type_  = $schedule_result[$rschedkey + 1]->leclab;
                        $aimsdept_  = $rsched->aimsdept;
                        $time1_ = new DateTime($stime_);
                        $time2_ = new DateTime($etime_);
                        $seq_ = $seq++;
                        $tardy_start_ = $schedule_result[$rschedkey + 1]->tardy_start;
                        $absent_start_ = $schedule_result[$rschedkey + 1]->absent_start;
                        $earlydismissal_ = $schedule_result[$rschedkey + 1]->early_dismissal;
                        $campus_ = $schedule_result[$rschedkey + 1]->campus;

                        $used_time_ = array();
                        list($login_,$logout_,$q_,$haslog_forremarks_,$used_time_) = $this->attcompute->displayLogTime($employeeid,$date,$stime_,$etime_,"NEW",$seq_,$absent_start_,$earlydismissal_,$used_time_, $campus_);

                        if($login_=='0000-00-00 00:00:00') $login_ = '';
                        if($logout_=='0000-00-00 00:00:00') $logout_ = '';
                        
                        list($el_,$vl_,$sl_,$ol_,$oltype_,$ob_,$abs_count_,$l_nopay_,$obtypes_)     = $this->attcompute->displayLeave($employeeid,$date,'',$stime_,$etime_,$seq_);
                        
                        if($ol_ == "DIRECT"){
                            $is_wfh_ = $this->attcompute->isWfhOB($employeeid,$date);
                            if($is_wfh_->num_rows() == 1 && $obtypes==2){
                                $ob_id_ = $is_wfh_->row()->aid;
                                $hastime_ = $this->attcompute->hasWFHTimeRecord($ob_id_,$date);
                                if($hastime_->num_rows() == 0) $ol_ = $oltype_ = $ob_ = "";
                            }
                        }

                        $cs_app_ = $this->attcompute->displayChangeSchedApp($employeeid,$date);
                        

                        $absent_ = $this->attcompute->displayAbsent($stime_,$etime_,$login_,$logout_,$employeeid,$date,$earlydismissal_, $absent_start_);

                        if($oltype_ == "ABSENT")                $absent_ = $absent_;
                        else if($holiday && $isCreditedHoliday && !$isSuspension) $absent_ = "";
                        if ($vl_ >= 1  || $el_ >= 1 || $sl_ >= 1 || $ob_ >= 1 ){
                            $absent_ = "";
                        }
                        if ($vl_ > 0 || $el_ > 0 || $sl_ > 0 || $ob_ > 0){
                            $absent_ = "";
                        }
                        
                        // Late / Undertime
                        list($lateutlec_,$lateutlab_,$lateutadmin_,$tschedlec_,$tschedlab_,$tschedadmin_,$lateutrle_,$tschedrle_, $lateut_rem_) = $this->attcompute->displayLateUTNS($stime_,$etime_,$tardy_start_,$login_,$logout_,$type_,$absent_);
                        if($el_ || $vl_  || $sl_ || ($holiday && $isCreditedHoliday && !$isSuspension)){
                            $lateutlec_ = $lateutlab_ = $lateutadmin_ = $tschedlec_ = $tschedlab_ = $tschedadmin_ = $tschedrle_ = "";
                        }
                        if($absent_ == ""){
                            if ($ol_  === "late"){
                                if ($lateutadmin_) {
                                    $remarks .= "<h5 style='color:green;'>Excused Late</h5>";
                                }
                            }elseif($lateutlab_ != "" || $lateutlec_ != "" || $lateutrle_ != "" || $lateutadmin_ != "") {
                                foreach ($lateut_rem_ as $lrkey => $lrvalue) {
                                    if($lrkey == "undertime" && !$ishalfday) $remarks .= "<h5 style='color:red;'>Unexcused Undertime</h5>";
                                    else if($lrkey == "late" && !$ishalfday) $remarks .= "<h5 style='color:red;'>Unexcused Late</h5>";

                                    $lateut_remarks = $lrkey;
                                }
                            }
                        }
                    }
                }

                $holiday_lec = ($lec_holhours != "0:00") ? $lec_holhours : "";
                $holiday_lab = ($lab_holhours != "0:00") ? $lab_holhours : "";
                $holiday_admin = ($admin_holhours != "0:00") ? $admin_holhours : "";
                $holiday_overload = ($rle_holhours != "0:00") ? $rle_holhours : "";
                $holiday_name = ($holiday && isset($holidayInfo['type'])) ? $holidayInfo['type'] : "";

                if($cto){
                    if(in_array($cto_id, $cto_id_list)){
                        $cto_credit = "";
                    }else{
                        $cto_id_list[] = $cto_id;
                    }
                }

                if($sc_app){
                    if(in_array($sc_app_id, $sc_app_id_list)){
                        $service_credit = "";
                    }else{
                        $sc_app_id_list[] = $sc_app_id;
                    }
                }

                $classification_id = $rsched->classification;

                $this->db->query("INSERT INTO employee_attendance_teaching
                    SET employeeid = '$employeeid',
                        `date` = '$date',
                        off_time_in = '$off_time_in',
                        off_time_out = '$off_time_out',
                        off_lec = '$off_lec',
                        off_lab = '$off_lab',
                        off_admin = '$off_admin',
                        off_overload = '$off_overload',
                        actlog_time_in = '$actlog_time_in',
                        actlog_time_out = '$actlog_time_out',
                        terminal = '$terminal',
                        twr_lec = '$twr_lec',
                        twr_lab = '$twr_lab',
                        twr_admin = '$twr_admin',
                        twr_overload = '$twr_overload',
                        aims_dept = '$aims_dept',
                        campus = '$campus_name',
                        subject = '$subject',
                        teaching_overload = '$teaching_overload',
                        ot_regular = '$ot_regular',
                        ot_restday = '$ot_restday',
                        ot_holiday = '$ot_holiday',
                        lateut_lec = '$lateut_lec',
                        lateut_lab = '$lateut_lab',
                        lateut_admin = '$lateut_admin',
                        lateut_overload = '$lateut_overload',
                        absent_lec = '$absent_lec',
                        absent_lab = '$absent_lab',
                        absent_admin = '$absent_admin',
                        service_credit = '$service_credit',
                        cto = '$cto_credit',
                        remarks = ".$this->db->escape($remarks).",
                        holiday_lec = '$holiday_lec',
                        holiday_lab = '$holiday_lab',
                        holiday_admin = '$holiday_admin',
                        holiday_overload = '$holiday_overload',
                        lateut_remarks = '$lateut_remarks',
                        holiday = '$holiday',
                        emergency = '$emergency',
                        vacation = '$vacation',
                        sick = '$sick',
                        other = '$other',
                        seq = '$seq',
                        rowspan = '$rwcount',
                        holiday_type = '$holiday_type',
                        rate = '$rate',
                        classification = '$classification',
                        classification_id = '$classification_id',
                        color = ".$this->db->escape($color)."
                        ");
                $isFirstSched = false;  
                $lec_holhours = $lab_holhours = $admin_holhours = $rle_holhours = "";
                $rendered_lec = $rendered_lab = $rendered_admin = $rendered_rle = "";
                if(!$tschedadmin && !$absent) $hasLog = true;
            } // $schedule_result loop
        }else{ // countrow && validsched
            $totalQ = 0;
            $stime = "";
            $etime = ""; 
                
            $log = $this->attcompute->displayLogTimeFlexi($employeeid,$date,$edata);

            // Leave
            list($el,$vl,$sl,$ol,$oltype,$ob,$abs_count,$l_nopay,$obtypes)     = $this->attcompute->displayLeave($employeeid,$date);
            if($ol == "DIRECT"){
                $is_wfh = $this->attcompute->isWfhOB($employeeid,$date);
                if($is_wfh->num_rows() == 1 && $obtypes==2){
                    $ob_id = $is_wfh->row()->aid;
                    $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
                    if($hastime->num_rows() == 0) $ol = $oltype = "";
                }
            }

            // Leave Pending
            $pending = $this->attcompute->displayPendingApp($employeeid,$date);
            $pending .= $this->attcompute->displayPendingOBWfh($employeeid,$date);
            // Overtime
            list($otreg,$otrest,$othol) = $this->attcompute->displayOt($employeeid,$date,true);

            $coc = $this->attcompute->displayCOC($employeeid,$date,true);
            if($coc > 0){
                if($ot_remarks != "APPROVED COC APPLICATION"){
                    $ot_remarks.=($ot_remarks?", APPROVED COC APPLICATION":"APPROVED COC APPLICATION");
                }
            }

            $sc_application = $this->attcompute->displaySCAttendance($employeeid,$date, $stime, $etime);
            if($sc_application > 0){
                if($sc_app_remarks != "Approved Conversion Service Credit"){
                    $sc_app_remarks.=($sc_app_remarks?", Approved Conversion Service Credit":"Approved Conversion Service Credit");
                }
            }

            /* Overtime */
            if($otreg){
                $totr += $this->attcompute->exp_time($otreg);
            }

            if($otrest){
                $totrest += $this->attcompute->exp_time($otrest);
            }

            if($othol){
                $tothol += $this->attcompute->exp_time($othol);
            }
                
            $service_credit    = $this->attcompute->displayServiceCredit($employeeid,$date);
            $service_credit = $service_credit?$service_credit:null;
            
            if($holiday){
                $holidayInfo = $this->attcompute->holidayInfo($date);
            }

            if(count($log) > 0){
                list($el,$vl,$sl,$ol,$oltype,$ob,$abs_count,$l_nopay,$obtypes)     = $this->attcompute->displayLeave($employeeid,$date);
                if($ol == "DIRECT"){
                    $is_wfh = $this->attcompute->isWfhOB($employeeid,$date);
                    if($is_wfh->num_rows() == 1 && $obtypes==2){
                        $ob_id = $is_wfh->row()->aid;
                        $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
                        if($hastime->num_rows() == 0) $ol = $oltype = "";
                    }
                }

                $login = $logout = $q = "";
                $stime = $etime = "--";
                for($i = 0;$i < count($log);$i++){
                    $login = $log[$i][0];
                    $logout = $log[$i][1];
                    $q = $log[$i][2];
                    if($q) $totalQ++;

                    $start = strtotime($login);
                    $end = strtotime($logout);
                    $mins = ($end - $start) / 60;

                    $off_time_in = $off_time_out = "--";
                    $off_lec = $off_lab = $off_admin = $off_overload = $twr_lec = $twr_lab = $twr_admin = $twr_overload = $campus_name = $aims_dept = $subject = $lateut_lec = $lateut_lab = $lateut_admin = $lateut_overload = $absent_lec = $absent_lab = $absent_admin = $cto_credit =  "";
                    $actlog_time_in = ($login? date("h:i A",strtotime($login)) : "--");
                    $actlog_time_out = ($logout? date("h:i A",strtotime($logout)) : "--");
                    $terminal = $deviceTap ? $deviceTap : $this->extensions->getTerminalName($campus_tap);
                    $teaching_overload = (isset($overload)?$this->time->minutesToHours($overload):"");
                    $ot_regular = ($otreg)?$this->attcompute->sec_to_hm($this->attcompute->exp_time($otreg)):"";
                    $ot_restday = ($otrest)?$this->attcompute->sec_to_hm($this->attcompute->exp_time($otrest)):"";
                    $ot_holiday = ($othol)?$this->attcompute->sec_to_hm($this->attcompute->exp_time($othol)):"";
                    $remarks = "";

                    $remarks .= (isset($cs_app)?$cs_app.'<br>':'') ;
                    $remarks .= ($pending)?"PENDING ".$pending.'<br>':($ol ? ($oltype ? ($oltype == "ABSENT" ? "ABSENT W/ FILE<br>" : $oltype.'<br>') : $this->employeemod->othLeaveDesc($ol).'<br>') : ($q ? ($q == "1" ? "" : $q.'<br>') : ""));
                    $remarks .= $ot_remarks."<br>";
                    $remarks .= $sc_app_remarks."<br>";
                    $remarks .= ($holiday && isset($holidayInfo['description']))?$holidayInfo['description'].'':"";                    
                    $holiday_lec = ($lec_holhours != "0:00") ? $lec_holhours : " ";
                    $holiday_lab = ($lab_holhours != "0:00") ? $lab_holhours : " ";
                    $holiday_admin = ($admin_holhours != "0:00") ? $admin_holhours : " ";
                    $holiday_overload = ($rle_holhours != "0:00") ? $rle_holhours : " ";
                    $holiday = ($holiday && isset($holidayInfo['type']))?$holidayInfo['type']:"";

                    $this->db->query("INSERT INTO employee_attendance_teaching
                    SET employeeid = '$employeeid',
                        `date` = '$date',
                        off_time_in = '$off_time_in',
                        off_time_out = '$off_time_out',
                        off_lec = '$off_lec',
                        off_lab = '$off_lab',
                        off_admin = '$off_admin',
                        off_overload = '$off_overload',
                        actlog_time_in = '$actlog_time_in',
                        actlog_time_out = '$actlog_time_out',
                        terminal = '$terminal',
                        twr_lec = '$twr_lec',
                        twr_lab = '$twr_lab',
                        twr_admin = '$twr_admin',
                        twr_overload = '$twr_overload',
                        aims_dept = '$aims_dept',
                        campus = '$campus_name',
                        subject = '$subject',
                        teaching_overload = '$teaching_overload',
                        ot_regular = '$ot_regular',
                        ot_restday = '$ot_restday',
                        ot_holiday = '$ot_holiday',
                        lateut_lec = '$lateut_lec',
                        lateut_lab = '$lateut_lab',
                        lateut_admin = '$lateut_admin',
                        lateut_overload = '$lateut_overload',
                        absent_lec = '$absent_lec',
                        absent_lab = '$absent_lab',
                        absent_admin = '$absent_admin',
                        service_credit = '$service_credit',
                        cto = '$cto_credit',
                        remarks = ".$this->db->escape($remarks).",
                        holiday_lec = '$holiday_lec',
                        holiday_lab = '$holiday_lab',
                        holiday_admin = '$holiday_admin',
                        holiday_overload = '$holiday_overload',
                        holiday = '$holiday'");

                    $stime = $etime = "";

                } // for($i = 0;$i < count($log);$i++){
            }else{ // if(count($log) > 0)
                if($holiday){
                    $holidayInfo = $this->attcompute->holidayInfo($date);
                }
                
                // Leave
                list($el,$vl,$sl,$ol,$oltype) = $this->attcompute->displayLeave($employeeid,$date);
                if($ol == "DIRECT"){
                    $is_wfh = $this->attcompute->isWfhOB($employeeid,$date);
                    if($is_wfh->num_rows() == 1){
                        $ob_id = $is_wfh->row()->aid;
                        $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
                        if($hastime->num_rows() == 0) $ol = $oltype = "";
                    }
                }else{
                    $el = $vl = $sl = $ol = $oltype = 0;
                }

                $sc_application = $this->attcompute->displaySCAttendance($employeeid,$date, $stime, $etime);
                if($sc_application > 0){
                    if($sc_app_remarks != "Approved Conversion Service Credit"){
                        $sc_app_remarks.=($sc_app_remarks?", Approved Conversion Service Credit":"Approved Conversion Service Credit");
                    }
                }

                $off_time_in = $off_time_out = $off_lec = $off_lab = $off_admin = $off_overload = $actlog_time_in = $actlog_time_out = $terminal = $twr_lec = $twr_lab = $twr_admin = $twr_overload = $aims_dept = $campus_name = $subject = $teaching_overload = $ot_regular = $ot_restday = $ot_holiday = $lateut_lec = $lateut_lab = $lateut_admin = $lateut_overload = $absent_lec = $absent_lab = $absent_admin = $service_credit = $cto_credit = $remarks = $holiday_lec = $holiday_lab = $holiday_admin = $holiday_overload = $holiday_name = "--";
                $terminal = $deviceTap ? $deviceTap : $this->extensions->getTerminalName($campus_tap);
                $service_credit = ($sc_application ? $sc_application : '--');
                $remarks = ($ol ? ($oltype ? ($oltype == "ABSENT" ? "ABSENT W/ FILE" : $oltype) : $this->employeemod->othLeaveDesc($ol)) : "");
                $remarks .= ($holiday && isset($holidayInfo['description']))?$holidayInfo['description']:"";
                $remarks .= $sc_app_remarks."<br>";
                $holiday = ($holiday && isset($holidayInfo['type']))?$holidayInfo['type']:"";

                $this->db->query("INSERT INTO employee_attendance_teaching
                    SET employeeid = '$employeeid',
                        `date` = '$date',
                        off_time_in = '$off_time_in',
                        off_time_out = '$off_time_out',
                        off_lec = '$off_lec',
                        off_lab = '$off_lab',
                        off_admin = '$off_admin',
                        off_overload = '$off_overload',
                        actlog_time_in = '$actlog_time_in',
                        actlog_time_out = '$actlog_time_out',
                        terminal = '$terminal',
                        twr_lec = '$twr_lec',
                        twr_lab = '$twr_lab',
                        twr_admin = '$twr_admin',
                        twr_overload = '$twr_overload',
                        aims_dept = '$aims_dept',
                        campus = '$campus_name',
                        subject = '$subject',
                        teaching_overload = '$teaching_overload',
                        ot_regular = '$ot_regular',
                        ot_restday = '$ot_restday',
                        ot_holiday = '$ot_holiday',
                        lateut_lec = '$lateut_lec',
                        lateut_lab = '$lateut_lab',
                        lateut_admin = '$lateut_admin',
                        lateut_overload = '$lateut_overload',
                        absent_lec = '$absent_lec',
                        absent_lab = '$absent_lab',
                        absent_admin = '$absent_admin',
                        service_credit = '$service_credit',
                        cto = '$cto_credit',
                        remarks = ".$this->db->escape($remarks).",
                        holiday_lec = '$holiday_lec',
                        holiday_lab = '$holiday_lab',
                        holiday_admin = '$holiday_admin',
                        holiday_overload = '$holiday_overload',
                        holiday = '$holiday'");
            } // if(count($log) > 0)
        } // else - countrow && validsched 
    }

    public function employeeAttendanceNonteaching($employeeid, $date){
        $this->load->model("ob_application");
        $this->load->model("payrollcomputation");
        $deptid = $this->employee->getindividualdept($employeeid);
        $fixedday = $this->attcompute->isFixedDay($employeeid);
        $classification_arr = $this->extensions->getFacultyLoadsClassfication();
        $classification_list = array();
        $edata = "NEW";
        $teachingtype = "nonteaching";
        foreach ($classification_arr as $key => $value) {
            $classification_list[$value->id] =  strtolower($value->description);
        }
        $total_perday_absent = $totr = $totrest = $tothol = $tOverload = $totlec_holhours = $totlab_holhours = $totadmin_holhours = $totrle_holhours = 0;
        $x = $totr = $totrest = $tothol = $tlec = $tutlec= $absent = $tabsent = $tabsentperday = $tel = $tvl = $tsl = $tol = $tdlec = $tdlab = $tholiday = $pending = $tempOverload = $overload = $tOverload = $lastDayOfWeek = $cs_app = $date_tmp = $tcto = $tsc_app = 0; 
        $tlec = $workdays = $tworkdays = 0 ;
        $tempabsent = 0;
        $t_service_credit = $service_credit = 0;
        $seq_new = 0;
        $cto_id_list = $sc_app_id_list = array();
        $perday_absent = $total_perday_absent = 0;
        $login_new = $logout_new = $q_new = $haslog_forremarks_new = "";
        $not_included_ol = array("ABSENT", "EL", "VL", "SL", "CORRECTION");
        $ishalfday = "";
        $hasLog = $isSuspension = false;
        $used_time = array();
        $isCreditedHoliday = false;
        $firstDate = true;
        $ob_data = array();
        $ot_remarks = $sc_app_remarks = $wfh_app_remarks = "";
        $sum_tworkhours = 0;

        $ot_remarks = "";
        $holidayInfo = array();
        // Holiday
        $isSuspension = false;
        $isRegularHoliday = false;

        $campus_tap = $this->attendance->getTapCampus($employeeid, $date);
        $deviceTap = $this->attendance->isFacial($employeeid, $date);
        $holiday = $this->attcompute->isHolidayNew($employeeid, $date,$deptid, "", "", $teachingtype); 
        $holiday_type = "";
        $rate = 0;
        if($holiday){
            $holidayInfo = $this->attcompute->holidayInfo($date);
            if(isset($holidayInfo['holiday_type'])){
                $holiday_type = $holidayInfo['holiday_type'];
                if($holidayInfo['holiday_type']==3) $isSuspension = true;
                if($holidayInfo['holiday_type']==5) $isSuspension = true;
                if($holidayInfo['holiday_type']==9) $isRegularHoliday = true;
                $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "nonteaching");
            }
        }
        $is_holiday_valid = $this->attendance->getTotalHoliday($date, $date, $employeeid);

        if(!$is_holiday_valid){
            $holidayInfo = array();
            $holiday = "";
        }

        $dispLogDate = date("d-M (l)",strtotime($date));
        $sched = $this->attcompute->displaySched($employeeid,$date);

        $countrow = $sched->num_rows();

        $isValidSchedule = true;

        if($countrow > 0){
            if($sched->row(0)->starttime == '00:00:00' && $sched->row(0)->endtime == '00:00:00') $isValidSchedule = false;
        }
        
        if($x%2 == 0)   $color = " style='background-color: white;'";
        else            $color = " style='background-color: #fafafa;'";
        $x++;
        
        if($firstDate && $holiday){
            $hasLog = $this->attendance->checkPreviousSchedAttendanceNonTeaching($date,$employeeid);
            $firstDate = false;
        }

        list($tworkhours, $excessive) = $this->attendance->totalWorkhoursPerdayNew($employeeid, $date);
        if($isRegularHoliday) $tworkhours = "0:00";
        if($countrow > 0 && $isValidSchedule){
            $haswholedayleave = false;
            $hasleavecount = 0;

            $hasLogprev = $hasLog;
            $hasLog = false;
            
            if($hasLogprev || $isSuspension)    $isCreditedHoliday = "true";
            else                                $isCreditedHoliday = "false";
            $tempsched = "";
            $seq = 0;
            $service_credit = null;
            $service_credit_used = 0;

            $isFirstSched = true;
            $q_sched = $sched;
            $perday_absent = $this->attendance->getTotalAbsentPerday($sched->result(), $employeeid, $date);
            $total_perday_absent += $perday_absent;
            $presentLastLog = false;

            foreach($sched->result() as $rsched){
                $workdays = 0;
                $ob_type = true;
                if(1){
                    if($tempsched == $dispLogDate){  $dispLogDate = "";}
                    $stime  = $rsched->starttime;
                    $etime  = $rsched->endtime; 
                    $tstart = $rsched->tardy_start; 
                    $absent_start = $rsched->absent_start;
                    $earlyd = $rsched->early_dismissal;
                    $night_shift = $rsched->night_shift;
                    if($night_shift == 1){
                        $this->reprocessFacialLogsNightShift($date, $date, $employeeid, $stime);
                        list($tworkhours, $excessive) = $this->attendance->totalWorkhoursPerdayNew($employeeid, $date);
                    }
                    // echo "<pre>"; print_r($night_shift); die;
                    $campus  = $rsched->campus;
                    if($campus == "Select an Option") $campus = "";
                    
                    $seq += 1;
                    // logtime
                    list($login,$logout,$q,$haslog_forremarks, $used_time_no_used, $ob_id)           = $this->attcompute->displayLogTime($employeeid,$date,$stime,$etime,$edata,$seq,$absent_start,$earlyd,$used_time,$campus, $night_shift);
                    if(($q === "LEAVE" || $q === "SICK" || $q === "LEAVE" || $q === "EMERGENCY")){
                        $login = $logout = "";
                    }

                    list($otreg,$otrest,$othol) = $this->attcompute->displayOt($employeeid,$date,true);
                    if($otreg || $otrest || $othol){
                        $ot_remarks = "OVERTIME APPLICATION";
                    }
                    
                    $coc = $this->attcompute->displayCOC($employeeid,$date,true);
                    if($coc > 0){
                        if($ot_remarks != "APPROVED COC APPLICATION"){
                            $ot_remarks.=($ot_remarks?", APPROVED COC APPLICATION":"APPROVED COC APPLICATION");
                        }
                    }
                    $sc_application = $this->attcompute->displaySCAttendance($employeeid,$date, $stime, $etime);
                    if($sc_application > 0){
                        if($sc_app_remarks != "Approved Conversion Service Credit"){
                            $sc_app_remarks.=($sc_app_remarks?", Approved Conversion Service Credit":"Approved Conversion Service Credit");
                        }
                    }

                    list($el,$vl,$sl,$ol,$oltype,$ob,$abs_count,$l_nopay,$obtypes, $ob_id, $l_nopay_remarks)  = $this->attcompute->displayLeave($employeeid,$date,'',$stime,$etime,$seq);
                    if ($vl == 0.5 || $el == 0.5 || $sl == 0.5){
                        $login = $logout = "";
                    }
                    list($cto, $ctohalf, $cto_id) = $this->attcompute->displayCTOUsageAttendance($employeeid,$date, $stime, $etime);
                    list($sc_app, $sc_app_half, $sc_app_id) = $this->attcompute->displaySCUsageAttendance($employeeid,$date, $stime, $etime);

                    $log_remarks_wfh = "";
                    if($ol == "DIRECT"){
                         //$is_wfh = $this->attcompute->isWfhOB($employeeid,$date);
                        $is_wfh = $this->attcompute->isWfhOBAMOnly($employeeid,$date);


                        $wfhContinue = false;
                        $datetime = DateTime::createFromFormat('H:i:s', $stime);
                        // var_dump($datetime->format('A'));die;
                        if ($datetime && $datetime->format('A') === 'AM') {
                            $wfhContinue = true;
                        }

                       $wfh_app_remarks = "";
                        // var_dump($stime);die;
                        if($is_wfh->num_rows() == 1 && $obtypes==2){

                            if($wfhContinue)
                            {
                                $ob_id = $is_wfh->row()->aid;
                                $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
                                if($hastime->num_rows() == 0) $ol = $oltype = $ob = 0;
                                if($wfh_app_remarks != "Approved Work From Home Application"){
                                    $wfh_app_remarks.=($wfh_app_remarks?", Approved Work From Home Application":"Approved Work From Home Application");
                                }
                            }else{
                                $log_remarks_wfh = "<span style='color:red'>UNEXCUSED UNDERTIME</span>";
                            }
                        }


                    }else if($ol == "DA" && $obtypes==3 && $ob_id && $is_ob == $ob_id){
                        $ob_details = $this->ob_application->getLeaveDetails($ob_id);
                        if($ob_details['timefrom'] && $ob_details['timeto']){
                            $login = $ob_details['timefrom'];
                            $logout = $ob_details['timeto'];
                        }
                    } else if($ol == "DA" && $obtypes==1 && $ob_id){
                        $ob_details = $this->ob_application->getLeaveDetails($ob_id);
                        if($ob_details['sched_affected']){
                            list($login, $logout) = explode('|', $ob_details['sched_affected']);
                        }
                    } 

                    $ob_data = $this->attcompute->displayLateUTAbs($employeeid, $date);
                    //Service Credit 
                    $service_credit = $this->attcompute->displayServiceCredit($employeeid,$stime,$etime,$date);
                    // Change Schedule
                    $cs_app = $this->attcompute->displayChangeSchedApp($employeeid,$date);
                    // echo "<pre>"; print_r($this->db->last_query()); die;
                    // $cs_app = false;
                    // Leave Pending
                    $pending = $this->attcompute->displayPendingApp($employeeid,$date, "", $ol);
                    $pending .= $this->attcompute->displayPendingOBWfh($employeeid,$date);

                     // Absent
                    $absent = $this->attcompute->displayAbsent($stime,$etime,$login,$logout,$employeeid,$date,$earlyd, '', $night_shift, $date);
                    // echo "absent: ".$absent."<br>";

                    if($oltype == "ABSENT") $absent = $absent; 
                    else if($holiday && $isCreditedHoliday && !$isSuspension) $absent = "";
                    if ($vl >= 1 || $el >= 1 || $sl >= 1 || $ob >= 1 || $ol >= 1 || $service_credit >= 1 || ($cto && $ctohalf == 0) || ($sc_app && $sc_app_half == 0)){
                        $absent = "";
                        $haswholedayleave = true;
                    }
                    if ($vl > 0 || $el > 0 || $sl > 0 || $ob > 0 || $ol > 0 || $service_credit > 0 || $cto || $sc_app){
                        $absent = "";
                        $hasleavecount++;
                    }
                    if ($vl == 0.5 || $el == 0.5 || $sl == 0.5 || $ob == 0.5 || $ol == 0.5 || $service_credit == 0.5 || ($cto && $ctohalf == 1) || ($sc_app && $sc_app_half == 1)) {
                        $login = date("H:i:s", strtotime($stime));
                        $logout = date("H:i:s", strtotime($etime));
                        $ishalfday = true;
                    }
                    if($abs_count >= 1) $haswholedayleave = true;

                    $lateutlec = $this->attcompute->displayLateUTNTNS($stime,$etime,$login,$logout,$absent,$teachingtype,$tstart,$night_shift, $date, $employeeid);
                    $utlec  = $this->attcompute->computeUndertimeNTNS($stime,$etime,$login,$logout,$absent,$teachingtype,$tstart,$night_shift, $date, $employeeid);

                    if($el || $vl || $sl || $service_credit || ($holiday && $isCreditedHoliday && !$isSuspension)) $lateutlec = $utlec = "";

                    if($absent && $presentLastLog){
                        $utlec = $absent;
                        $absent = '';
                    }
                    if($holiday)
                    {
                        if($this->attcompute->isHolidayWithpay($date) == "YES")
                        {
                            if($tempabsent)
                            {
                                $absent = $absent;
                            }
                        }
                        else
                        {
                            if(!$login && !$logout)
                            {
                                $absent = $absent;
                            }
                        }
                    }
                    else
                    {
                        $tempabsent = $absent;
                    }

                    $log_remarks = $log_remarks_wfh?$log_remarks_wfh:'';

                    if($absent){
                        if(!$login && !$logout && !$haslog_forremarks) $log_remarks = 'NO TIME IN AND OUT';
                        elseif(!$login) $log_remarks = 'NO TIME IN';
                        elseif(!$logout) $log_remarks = 'NO TIME OUT';
                    } 

                    $hasOL = $ol ? ($ol != 'CORRECTION' ? true : false) : false; 
                    if(!$fixedday){
                        if($absent=='' || $hasOL) $workdays=1;
                    }

                    if($isFirstSched){
                        $is_holiday_halfday = $this->attcompute->isHolidayNew($employeeid, $date,$deptid, "", "on");
                        if($is_holiday_halfday){
                            list($fromtime, $totime) = $this->extensions->getHolidayHalfdayTime($date, "first");
                        } 
                        if($is_holiday_halfday && ($fromtime && $totime) ){
                            $holidayInfo = $this->attcompute->holidayInfo($date);
                            $is_half_holiday = true;
                            $half_holiday = $this->attcompute->holidayHalfdayComputation(date("H:i", strtotime($login)), date("H:i", strtotime($logout)), date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), $isFirstSched);
                            if($half_holiday > 0){
                                $lateutlec = $this->attcompute->sec_to_hm(abs($half_holiday)); 
                                $absent = $this->attcompute->sec_to_hm(abs($absent)); 
                            }else{
                                $lateutlec = "";
                                $absent = "";
                                $log_remarks = "";
                            }
                        }
                    }else{
                        $is_holiday_halfday = $this->attcompute->isHolidayNew($employeeid, $date,$deptid, "", "on");
                        if($is_holiday_halfday){
                            list($fromtime, $totime) = $this->extensions->getHolidayHalfdayTime($date, "second");
                        } 
                        if($is_holiday_halfday && ($fromtime && $totime) ){
                            $holidayInfo = $this->attcompute->holidayInfo($date);
                            $is_half_holiday = true;
                            if($utlec){
                                $half_holiday = $this->attcompute->holidayHalfdayComputation($login, $logout, date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), $isFirstSched);
                                if($half_holiday > 0){
                                    $utlec = $this->attcompute->sec_to_hm(abs($half_holiday)); 

                                }else{
                                    $utlec = "";
                                }
                                
                            }
                        }
                    }

                    if($el || $vl || $sl  || $service_credit || ($holiday && $isCreditedHoliday && !$isSuspension) || $cto || $sc_app || $ishalfday) $lateutlec = $utlec = $absent = "";
                    $absent = $this->attcompute->exp_time($absent);
                    // if($absent >= 14400 && $countrow==2) $absent = 14400;
                    // elseif($absent >= 14400 && $countrow==1) $absent = 28800;
                    $absent   = ($absent ? $this->attcompute->sec_to_hm($absent) : "");
                    
                    if($lateutlec && !$ishalfday){
                        if(in_array("late", $ob_data)) $log_remarks = "EXCUSED LATE";
                        else{
                            $log_remarks = "<span style='color:red'>UNEXCUSED LATE</span>";
                            $ob_type = false;
                        }
                    }else if($utlec && !$ishalfday){
                        if (!$isSuspension) {
                            if(in_array("undertime", $ob_data)) $log_remarks = "EXCUSED UNDERTIME";
                            else{
                                $log_remarks = "<span style='color:red'>UNEXCUSED UNDERTIME</span>";
                                $ob_type = false;
                            }
                        }
                    }else if($absent){
                        if(in_array("absent", $ob_data)) $log_remarks = "EXCUSED ABSENT";
                        else{
                            // if(strtotime($date) < strtotime($date_tmp)){
                                // $log_remarks = "UNEXCUSED ABSENT";
                                $ob_type = false;
                                if(!$login && !$logout && !$haslog_forremarks) $log_remarks = 'NO TIME IN AND OUT';
                                elseif(!$login) $log_remarks = 'NO TIME IN';
                                elseif(!$logout) $log_remarks = 'NO TIME OUT';
                                $tworkhours = "0:00";
                            // }
                        }
                    }

                    
                    if(!$login){
                        $login = $this->timesheet->getNooutData($employeeid, $date);
                    }

                    if($login && $logout && ($login != $logout)){
                        $presentLastLog = true;
                    }else{
                        $presentLastLog = false;
                    }

                    list($vl_lateut) = $this->payrollcomputation->removeLateUTByVL($employeeid, $date, $this->time->hoursToMinutes($lateutlec));
                    list($vl_utlec) = $this->payrollcomputation->removeLateUTByVL($employeeid, $date, $this->time->hoursToMinutes($utlec));
                    $vl_lateut = ($lateutlec) ? $this->time->minutesToHours($vl_lateut) : "";
                    $vl_utlec = ($utlec) ? $this->time->minutesToHours($vl_utlec) : "";
                    
                    // if other leave is OB
                    if($ol == "DA"){
                        $ol = $ob;
                    }

                    $off_time_in = ($stime != "00:00:00" ? date('h:i A',strtotime($stime)) : "--");
                    $off_time_out = ($stime != "00:00:00" ? date('h:i A',strtotime($etime)) : "--");
                    $off_time_total = $this->attcompute->sec_to_hm($perday_absent);

                    $actlog_time_in = ($login ? date("h:i A",strtotime($login)) : "--");
                    $actlog_time_out = ($logout  ? date("h:i A",strtotime($logout)) : "--");

                    $terminal = $deviceTap ? $deviceTap : $this->extensions->getTerminalName($campus_tap);

                    $twr = $tworkhours;

                    $ot_regular = ($otreg)?$this->attcompute->sec_to_hm($this->attcompute->exp_time($otreg)):"";
                    $ot_restday = ($otrest)?$this->attcompute->sec_to_hm($this->attcompute->exp_time($otrest)):"";
                    $ot_holiday = ($othol)?$this->attcompute->sec_to_hm($this->attcompute->exp_time($othol)):"";

                    $late = $lateutlec;
                    $undertime = $utlec;
                    // echo "undertime: ".$undertime."<br>"; 
                    // echo "twr: ".$twr."<br>";
                    if($late && $excessive == 1){
                        $twr = $this->attcompute->exp_time($twr) - $this->attcompute->exp_time($late);
                        $twr = $this->attcompute->sec_to_hm($twr);
                    }
                    if($undertime  && $excessive == 1){
                        $twr = $this->attcompute->exp_time($twr) - $this->attcompute->exp_time($undertime);
                        // echo "<pre>"; print_r($twr); 
                        $twr = $this->attcompute->sec_to_hm($twr);
                        // echo "<pre>"; print_r($twr); 
                    }else if($undertime  && $night_shift == 1){
                        $twr = $this->attcompute->exp_time($twr) - $this->attcompute->exp_time($undertime);
                        // echo "<pre>"; print_r($twr); 
                        $twr = $this->attcompute->sec_to_hm($twr);
                    }

                    $vl_deduc_late = $vl_lateut;
                    $vl_deduc_undertime = $vl_utlec;
                    $absent_data = (!$fixedday && !$hasOL) ? $absent : ($absent?$absent:'');

                    $service_credit = ($sc_app ? $sc_app : ($sc_application ? $sc_application : ''));

                    $cto_credit = ($cto ? $cto : "");

                    if ($oltype == 'OFFICIAL BUSINESS') {
                        $obType = $this->extensions->getTypeOfOB($employeeid, $date);
                        $oltype = $obType == 'SEMINAR' ? $obType : $oltype;
                    }
                    $emergency = ($el) ? $el : "";
                    $vacation = ($vl) ? $vl : "";
                    $sick = ($sl) ? $sl : "";
                    $other = ($ol) ? $ol : "";
                    $rwcount = 1;
                    if(!$dispLogDate) $rwcount = 1;
                    if($haswholedayleave || $pending || $holiday) $rwcount = $countrow;

                    $remarks = ($log_remarks?$log_remarks."<br>":'');
                    $remarks .= ($ot_remarks) ? $ot_remarks."<br>" : '' ;
                    $remarks .= ($sc_app_remarks) ? $sc_app_remarks."<br>" : '';
                    $remarks .= ($wfh_app_remarks) ? $wfh_app_remarks."<br>" : '';
                    $remarks .= $cs_app ? ($cs_app?$cs_app.'<br>':'') : '';
                    $remarks .= ($pending)?"PENDING ".$pending.'<br>':'';
                    $remarks .= ($ol ? ($oltype ? ($oltype == "ABSENT" ? "ABSENT W/ FILE<br>" : $oltype.'<br>') : ($l_nopay_remarks ? $l_nopay_remarks : $this->employeemod->othLeaveDesc($ol))) .'<br>' : '') ;
                    $remarks .= $service_credit?'SERVICE CREDIT<br>':'';
                    $remarks .= $cto?'APPROVED USE CTO APPLICATION<br>':'';
                    $remarks .= $sc_app?'USE SERVICE CREDIT<br>':'';
                    $remarks .= $dispLogDate ? (isset($holidayInfo['description']) ? $holidayInfo['description'] : '') : '';

                    $holiday_data = (isset($holidayInfo['type']) ? $holidayInfo['type'] : '');

                    $this->db->query("INSERT INTO employee_attendance_nonteaching
                    SET employeeid = '$employeeid',
                        `date` = '$date',
                        off_time_in = '$off_time_in',
                        off_time_out = '$off_time_out',
                        off_time_total = '$off_time_total',
                        actlog_time_in = '$actlog_time_in',
                        actlog_time_out = '$actlog_time_out',
                        terminal = '$terminal',
                        twr = '$twr',
                        ot_regular = '$ot_regular',
                        ot_restday = '$ot_restday',
                        ot_holiday = '$ot_holiday',
                        late = '$late',
                        undertime = '$undertime',
                        vl_deduc_late = '$vl_deduc_late',
                        vl_deduc_undertime = '$vl_deduc_undertime',
                        absent = '$absent_data',
                        service_credit = '$service_credit',
                        cto = '$cto_credit',
                        remarks = ".$this->db->escape($remarks).",
                        vl = '$vacation',
                        sl = '$sick',
                        other = '$other',
                        el = '$emergency',
                        holiday = '$holiday_data',
                        seq = '$seq',
                        rowspan = '$countrow',
                        color = ".$this->db->escape($color).",
                        rowcount = '$rwcount',
                        holiday_type = '$holiday_type',
                        rate = '$rate'
                        ");

                    $checkTWR = $this->db->query("SELECT twr, remarks, late, undertime, id, absent FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date'");
                    $deductions = array();
                    if($checkTWR->num_rows() > 0){
                        foreach ($checkTWR->result() as $twrK => $twrV) {
                            $iniTWR = $twrV->twr;
                            $iniRemarks = $twrV->remarks;
                            $inilate = $twrV->late;
                            $iniundertime = $twrV->undertime;
                            $iniabsent = $twrV->absent;

                            if($iniRemarks != $remarks && $remarks != ""){
                                 // $this->db->query("UPDATE employee_attendance_nonteaching SET remarks = '$remarks' WHERE employeeid = '$employeeid' AND `date` = '$date'");
                            }
                            $deductions[$twrV->id.'_late'] = $this->attcompute->exp_time($inilate);
                            $deductions[$twrV->id.'_ut'] = $this->attcompute->exp_time($iniundertime);
                            $deductions[$twrV->id.'_absent'] = $this->attcompute->exp_time($iniabsent);
                        }
                    }
                    $total_work_hours = $total_deduction = 0;
                    foreach ($deductions as $DeducKey => $DeducValue) {
                        $total_deduction += $DeducValue;
                    }
                    if($total_deduction > 0){
                        $total_work_hours = $this->attcompute->sec_to_hm($perday_absent - $total_deduction);
                        // COMMENT PO MUNA, COORDINATE KO KAY RIEL BAKIT MAG TWR PAG MAY ABSENT
                        $this->db->query("UPDATE employee_attendance_nonteaching SET twr = '$total_work_hours' WHERE employeeid = '$employeeid' AND `date` = '$date'");
                    }


                } // if($rsched->flexible != "YES")
            } // foreach($sched->result() as $rsched)
        }else{ // if($countrow > 0 && $isValidSchedule)
            $totalQ = 0;
            $stime = "";
            $etime = ""; 
            
            $log = $this->attcompute->displayLogTimeFlexi($employeeid,$date,$edata);
            list($otreg,$otrest,$othol) = $this->attcompute->displayOt($employeeid,$date,false);
            if($otreg || $otrest || $othol){
                $ot_remarks = "OVERTIME APPLICATION";
            }

            list($el,$vl,$sl,$ol,$oltype,$ob,$abs_count,$l_nopay,$obtypes, $ob_id) = $this->attcompute->displayLeave($employeeid,$date);
            if($ol == "DIRECT"){
                $is_wfh = $this->attcompute->isWfhOB($employeeid,$date);
                if($is_wfh->num_rows() == 1 && $obtypes==2){
                    $ob_id = $is_wfh->row()->aid;
                    $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
                    if($hastime->num_rows() == 0) $ol = $oltype = "";
                }
            }else{
                $el = $vl = $sl = 0;
            }

            $tworkhours = $this->attendance->totalWorkhoursPerday($employeeid, $date);

            //Service Credit 
            $service_credit = $this->attcompute->displayServiceCredit($employeeid,$stime,$etime,$date);

            $sc_application = $this->attcompute->displaySCAttendance($employeeid,$date, $stime, $etime);
            if($sc_application > 0){
                if($sc_app_remarks != "Approved Conversion Service Credit"){
                    $sc_app_remarks.=($sc_app_remarks?", Approved Conversion Service Credit":"Approved Conversion Service Credit");
                }
            }

            // Leave Pending
            $pending = $this->attcompute->displayPendingApp($employeeid,$date);

            $pending .= $this->attcompute->displayPendingOBWfh($employeeid,$date);
            // Overtime
            list($otreg,$otrest,$othol) = $this->attcompute->displayOt($employeeid,$date,false);

            if($otreg){
                $totr += $this->attcompute->exp_time($otreg);
                $ot_remarks = "OVERTIME APPLICATION";
            }

            if($otrest){
                $totrest += $this->attcompute->exp_time($otrest);
                $ot_remarks = "OVERTIME APPLICATION";
            }

            if($othol){
                $tothol += $this->attcompute->exp_time($othol);
                $ot_remarks = "OVERTIME APPLICATION";
            }

            if($ol == "DA"){
                $ol = $ob;
            }

            if(count($log) > 0){
                $login = $logout = $q = "";
                $stime = $etime = "--";

                for($i = 0;$i < count($log);$i++){
                    $login = $log[$i][0];
                    $logout = $log[$i][1];
                    $q = $log[$i][2];
                    if($q) $totalQ++;

                    $off_time_in = $off_time_out = $off_time_total = $actlog_time_in = $actlog_time_out = $terminal = $twr = $ot_regular = $ot_restday = $ot_holiday = $late = $undertime = $vl_deduc_late = $vl_deduc_undertime = $absent_data = $service_credit = $cto_credit = $remarks = $vacation = $sick = $other = $holiday_data = "--";
                    $off_time_total = $this->attcompute->sec_to_hm($perday_absent);
                    $actlog_time_in = $login?date("h:i A",strtotime($login)):"--";
                    $actlog_time_out = $logout?date("h:i A",strtotime($logout)):"--";
                    $terminal = $deviceTap ? $deviceTap : $this->extensions->getTerminalName($campus_tap);
                    $twr = $tworkhours;
                    $ot_regular = $otreg?$otreg:"--";
                    $ot_restday = $otrest?$otrest:"--";
                    $ot_holiday = $othol?$othol: "--";
                    $service_credit = ($sc_application ? $sc_application : '--');
                    $remarks = "";
                    $remarks .= $ot_remarks;
                    $remarks .= $sc_app_remarks;
                    $remarks .= ($cs_app?$cs_app.'<br>':"");
                    $remarks .= ($pending)?"PENDING ".$pending.'<br>':($ol ? ($oltype ? ($oltype == "ABSENT" ? "ABSENT W/ FILE<br>" : $oltype."<br>") : $this->employeemod->othLeaveDesc($ol)."<br>") 
                        : '');
                    $remarks .= $service_credit && $service_credit != '--'?'SERVICE CREDIT<br>':'';
                    $remarks .= (isset($holidayInfo["description"]) ? $holidayInfo["description"] : "");
                    if(strpos($pending, "SERVICE CREDIT") === false && !$service_credit){
                        $remarks .= '<a class="btn btn-primary" id="applysc" href="#" data-toggle="modal" data-target="#myModal1" style="display: none;"> dateInitial="$remarks .= $date?>" >Apply as Service Credit
                                    <span class="notifdiv bell" style="position: relative;top:5px;"><i class="glyphicon glyphicon-bell large" style="color: #FF1744;font-size: 20px;"></i></span></a>';
                    }
                    $holiday_data = (isset($holidayInfo['type']) ? $holidayInfo['type'] : '');

                    $this->db->query("INSERT INTO employee_attendance_nonteaching
                    SET employeeid = '$employeeid',
                        `date` = '$date',
                        off_time_in = '$off_time_in',
                        off_time_out = '$off_time_out',
                        off_time_total = '$off_time_total',
                        actlog_time_in = '$actlog_time_in',
                        actlog_time_out = '$actlog_time_out',
                        terminal = '$terminal',
                        twr = '$twr',
                        ot_regular = '$ot_regular',
                        ot_restday = '$ot_restday',
                        ot_holiday = '$ot_holiday',
                        late = '$late',
                        undertime = '$undertime',
                        vl_deduc_late = '$vl_deduc_late',
                        vl_deduc_undertime = '$vl_deduc_undertime',
                        absent = '$absent_data',
                        service_credit = '$service_credit',
                        cto = '$cto_credit',
                        remarks = ".$this->db->escape($remarks).",
                        vl = '$vacation',
                        sl = '$sick',
                        other = '$other',
                        holiday = '$holiday_data'
                        ");
                } // for($i = 0;$i < count($log);$i++){
            }else{ // if(count($log) > 0)
                $sc_application = $this->attcompute->displaySCAttendance($employeeid,$date, $stime, $etime);
                if($sc_application > 0){
                    if($sc_app_remarks != "Approved Conversion Service Credit"){
                        $sc_app_remarks.=($sc_app_remarks?", Approved Conversion Service Credit":"Approved Conversion Service Credit");
                    }
                }

                $log = $this->attcompute->displayLogTimeFlexi($employeeid,$date,$edata);
                $off_time_in = $off_time_out = $off_time_total = $actlog_time_in = $actlog_time_out = $terminal = $twr = $ot_regular = $ot_restday = $ot_holiday = $late = $undertime = $vl_deduc_late = $vl_deduc_undertime = $absent_data = $service_credit = $cto_credit = $remarks = $vacation = $sick = $other = $holiday_data = "--";

                $off_time_total = $this->attcompute->sec_to_hm($perday_absent);
                $terminal = $deviceTap ? $deviceTap : $this->extensions->getTerminalName($campus_tap);
                $twr = $tworkhours;
                $ot_regular = $otreg?$otreg:"--";
                $ot_restday = $otrest?$otrest:"--";
                $ot_holiday = $othol?$othol: "--";
                $service_credit = ($sc_application ? $sc_application : '--');
                $remarks = "";
                $remarks .= $ot_remarks;
                $remarks .= $sc_app_remarks ;
                $remarks .= ($pending) ? "PENDING ".$pending."<br>" : "";
                $remarks .= ($ol ? ($oltype ? ($oltype == "ABSENT" ? "ABSENT W/ FILE" : $oltype) : $this->employeemod->othLeaveDesc($ol)) : "");
                $remarks .= $service_credit && $service_credit != '--'?'SERVICE CREDIT<br>':'';
                $remarks .= (isset($holidayInfo["description"]) ? $holidayInfo["description"] : "");
                $holiday_data = (isset($holidayInfo['type']) ? $holidayInfo['type'] : '');

                $this->db->query("INSERT INTO employee_attendance_nonteaching
                SET employeeid = '$employeeid',
                    `date` = '$date',
                    off_time_in = '$off_time_in',
                    off_time_out = '$off_time_out',
                    off_time_total = '$off_time_total',
                    actlog_time_in = '$actlog_time_in',
                    actlog_time_out = '$actlog_time_out',
                    terminal = '$terminal',
                    twr = '$twr',
                    ot_regular = '$ot_regular',
                    ot_restday = '$ot_restday',
                    ot_holiday = '$ot_holiday',
                    late = '$late',
                    undertime = '$undertime',
                    vl_deduc_late = '$vl_deduc_late',
                    vl_deduc_undertime = '$vl_deduc_undertime',
                    absent = '$absent_data',
                    service_credit = '$service_credit',
                    cto = '$cto_credit',
                    remarks = ".$this->db->escape($remarks).",
                    vl = '$vacation',
                    sl = '$sick',
                    other = '$other',
                    holiday = '$holiday_data'
                    ");
            } 
            $ot_remarks = $sc_app_remarks = $wfh_app_remarks = "";
        } 
    }

    function getEmployeeList($where = "", $orderBy = ""){
        return $this->db->query("SELECT 
        a.employeeid, 
        a.fname, 
        a.lname, 
        SUBSTRING(a.`mname`, 1, 1) as mname, 
        b.`description` as department, 
        TRIM(c.`description`) 
        as position_desc, 
        d.description as employement_desc,
        a.campusid
        FROM employee a 
        LEFT JOIN `code_department` b on a.`deptid` = b.`code` 
        LEFT JOIN `code_position` c on a.`positionid` = c.`positionid` 
        LEFT JOIN `code_status` d on a.`employmentstat` = d.`code`
        WHERE 1 = 1 $where $orderBy")->result();
    }

    function updateDTR($employeeid, $date_from, $date_to){
        $date_range = $this->attcompute->displayDateRange($date_from, $date_to);
        foreach ($date_range as $date) {
            $query = $this->db->query("SELECT * FROM employee_attendance_update WHERE employeeid = '$employeeid' AND `date` = '$date->dte'")->num_rows();
            if($query > 0){
                $this->db->query("UPDATE employee_attendance_update SET hasUpdate = '1' WHERE employeeid = '$employeeid' AND `date` = '$date->dte'");
            }else{
                $this->db->query("INSERT INTO employee_attendance_update SET hasUpdate = '1', employeeid = '$employeeid', `date` = '$date->dte'");
            }
        }
    }

    function checkIfHasLogDelete($employeeid, $date){
        $this->db->query("DELETE FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date'");
        $this->db->query("DELETE FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND `date` = '$date'");
        $today = date('Y-m-d');
        $query_nonteaching = $this->db->query("SELECT * FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date'")->num_rows();
        $query_teaching = $this->db->query("SELECT * FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND `date` = '$date'")->num_rows();
        $query = $this->db->query("SELECT * FROM employee_attendance_update WHERE employeeid = '$employeeid' AND `date` = '$date' AND hasUpdate = '1'")->num_rows();
        if($query > 0 || $date >= $today){
            if($query_nonteaching > 0){
                $this->db->query("DELETE FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date'");
                $this->db->query("UPDATE employee_attendance_update SET hasUpdate = '0' WHERE employeeid = '$employeeid' AND `date` = '$date'");
                return false;
            }

            if($query_teaching > 0){
                $this->db->query("DELETE FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND `date` = '$date'");
                $this->db->query("UPDATE employee_attendance_update SET hasUpdate = '0' WHERE employeeid = '$employeeid' AND `date` = '$date'");
                return false;
            }
        }else{
            if($query_nonteaching > 0){
                return true;
            }else if($query_teaching > 0){
                return true;
            }else{
                return false;
            }
        }
    }

    function checkIfHasLogOld($employeeid, $date){
        $this->db->start_cache();
        $today = date('Y-m-d');
        $query_nonteaching = $this->db->query("SELECT employeeid FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date'")->num_rows();
        $query_teaching = $this->db->query("SELECT employeeid FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND `date` = '$date'")->num_rows();
        $query = $this->db->query("SELECT employeeid FROM employee_attendance_update WHERE employeeid = '$employeeid' AND `date` = '$date' AND hasUpdate = '1'")->num_rows();
        if($query > 0 || $date >= $today){
            if($query_nonteaching > 0){
                $this->db->query("DELETE FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date'");
                $this->db->query("UPDATE employee_attendance_update SET hasUpdate = '0' WHERE employeeid = '$employeeid' AND `date` = '$date'");
                $this->db->stop_cache();
                return false;
            }

            if($query_teaching > 0){
                $this->db->query("DELETE FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND `date` = '$date'");
                $this->db->query("UPDATE employee_attendance_update SET hasUpdate = '0' WHERE employeeid = '$employeeid' AND `date` = '$date'");
                $this->db->stop_cache();
                return false;
            }
        }else{
            $this->db->stop_cache();
            if($query_nonteaching > 0){
                return true;
            }else if($query_teaching > 0){
                return true;
            }else{
                return false;
            }
        }
    }



    function checkIfHasLog($employeeid, $date) {
        $today = date('Y-m-d');

        // Queries for attendance and updates
        $query_nonteaching = $this->db->query("SELECT * FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND date = '$date'");
        $query_teaching = $this->db->query("SELECT * FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND date = '$date'");
        $hasUpdate = $this->db->query("SELECT employeeid FROM employee_attendance_update WHERE employeeid = '$employeeid' AND date = '$date' AND hasUpdate = '1'")->num_rows() > 0;

        // Check for update or future date, handle attendance deletion
        if ($hasUpdate || $date >= $today) {
            if ($query_nonteaching->num_rows() > 0) {
                $this->deleteAttendanceAndResetUpdate($employeeid, $date, 'nonteaching');
                return false;
            }
            if ($query_teaching->num_rows() > 0) {
                $this->deleteAttendanceAndResetUpdate($employeeid, $date, 'teaching');
                return false;
            }
        } else {
            // Handle cases with or without actual log time
            if ($query_nonteaching->num_rows() > 0) {
                return $this->processAttendanceRow($query_nonteaching->row(), $employeeid, $date, 'nonteaching');
            }
            if ($query_teaching->num_rows() > 0) {
                return $this->processAttendanceRow($query_teaching->row(), $employeeid, $date, 'teaching');
            }
        }

        return false;
    }

    // Helper function to delete attendance and reset update
    private function deleteAttendanceAndResetUpdate($employeeid, $date, $type) {
        $table = $type === 'nonteaching' ? 'employee_attendance_nonteaching' : 'employee_attendance_teaching';
        $this->db->query("DELETE FROM $table WHERE employeeid = '$employeeid' AND date = '$date'");
        $this->db->query("UPDATE employee_attendance_update SET hasUpdate = '0' WHERE employeeid = '$employeeid' AND date = '$date'");
    }

    // Helper function to process attendance row
    private function processAttendanceRow($attendanceRow, $employeeid, $date, $type) {
        if ($attendanceRow->actlog_time_in == "--") {
            $query_timesheet = $this->db->query("SELECT * FROM timesheet WHERE userid = '$employeeid' AND DATE(timein) = '$date'");
            if ($query_timesheet->num_rows() > 0) {
                $this->deleteAttendanceAndResetUpdate($employeeid, $date, $type);
                return false;
            }
            return true;
        }
        return true;
    }


    function getAttendanceTeaching($employeeid, $datesetfrom, $datesetto){
        $attendance = array();
        $date_range = $this->attcompute->displayDateRange($datesetfrom, $datesetto);
        foreach ($date_range as $date) {
            $attendance[$date->dte] = $this->db->query("SELECT * FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND `date` = '$date->dte' ORDER BY `id`")->result();
        }
        return $attendance;
    }

    function getAttendanceNonteaching($employeeid, $datesetfrom, $datesetto){
        $attendance = array();
        $date_range = $this->attcompute->displayDateRange($datesetfrom, $datesetto);
        foreach ($date_range as $date) {
            $attendance[$date->dte] = $this->db->query("SELECT * FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date->dte' ORDER BY `id`")->result();
        }
        return $attendance;
    }

    function getTeachingAttendanceSummary($attendance){
        $off_lec_total = $off_lab_total = $off_admin_total = $off_overload_total = $twr_lec_total = $twr_lab_total = $twr_admin_total = $twr_overload_total = $teaching_overload_total = $ot_regular_total = $ot_restday_total = $ot_holiday_total = $lateut_lec_total = $lateut_lab_total = $lateut_admin_total = $lateut_overload_total = $absent_lec_total = $absent_lab_total = $absent_admin_total = $service_credit_total = $cto_total = $holiday_lec_total = $holiday_lab_total = $holiday_admin_total = $holiday_overload_total = $holiday_total = $date_list_absent = $total_absent = $vacation_total = $emergency_total = $other_total = $sick_total =  0;
        foreach ($attendance as $att_date) {
            $counter = 0;
            $rowspan = 0;
            $is_absent = 0;
            $date = "";
            foreach ($att_date as $key => $value) {
                $off_lec_total += $this->attcompute->exp_time($value->off_lec);
                $off_lab_total += $this->attcompute->exp_time($value->off_lab);
                $off_admin_total += $this->attcompute->exp_time($value->off_admin);
                $off_overload_total += $this->attcompute->exp_time($value->off_overload);

                $twr_lec_total += $this->attcompute->exp_time($value->twr_lec);
                $twr_lab_total += $this->attcompute->exp_time($value->twr_lab);
                $twr_admin_total += $this->attcompute->exp_time($value->twr_admin);
                $twr_overload_total += $this->attcompute->exp_time($value->twr_overload);

                if($value->teaching_overload != "" && $value->teaching_overload != "--") $teaching_overload_total += $this->attcompute->exp_time($value->teaching_overload);

                if($counter == 0){
                    $ot_regular_total += $this->attcompute->exp_time($value->ot_regular);
                    $ot_restday_total += $this->attcompute->exp_time($value->ot_restday);
                    $ot_holiday_total += $this->attcompute->exp_time($value->ot_holiday);
                    $vacation_total += $value->vacation;
                    $emergency_total += $value->emergency;
                    $sick_total += $value->sick;
                    $other_total += $value->other;

                }

                $lateut_lec_total += $this->attcompute->exp_time($value->lateut_lec);
                $lateut_lab_total += $this->attcompute->exp_time($value->lateut_lab);
                $lateut_admin_total += $this->attcompute->exp_time($value->lateut_admin);
                $lateut_overload_total += $this->attcompute->exp_time($value->lateut_overload);

                $absent_lec_total += $this->attcompute->exp_time($value->absent_lec);
                $absent_lab_total += $this->attcompute->exp_time($value->absent_lab);
                $absent_admin_total += $this->attcompute->exp_time($value->absent_admin);

                if($value->service_credit != "" && $value->service_credit != "--") $service_credit_total += $value->service_credit;
                if($value->cto != "" && $value->cto != "--") $cto_total +=  $this->attcompute->exp_time($value->cto);

                $holiday_lec_total += $this->attcompute->exp_time($value->holiday_lec);
                $holiday_lab_total += $this->attcompute->exp_time($value->holiday_lab);
                $holiday_admin_total += $this->attcompute->exp_time($value->holiday_admin);
                $holiday_overload_total += $this->attcompute->exp_time($value->holiday_overload);

                if($value->holiday && $counter == 0) $holiday_total++;

                if($value->absent_lec && $value->absent_lab && $value->absent_admin) $is_absent++;
                $date = $value->date;
            }
            if($is_absent > 0 && $is_absent == count($att_date)) $total_absent++;
        }

        return array(
            $this->attcompute->sec_to_hm($off_lec_total), 
            $this->attcompute->sec_to_hm($off_lab_total), 
            $this->attcompute->sec_to_hm($off_admin_total), 
            $this->attcompute->sec_to_hm($off_overload_total), 
            $this->attcompute->sec_to_hm($twr_lec_total), 
            $this->attcompute->sec_to_hm($twr_lab_total), 
            $this->attcompute->sec_to_hm($twr_admin_total), 
            $this->attcompute->sec_to_hm($twr_overload_total), 
            ($teaching_overload_total != 0 ? $this->attcompute->sec_to_hm($teaching_overload_total) : ""), 
            ($ot_regular_total ? $this->attcompute->sec_to_hm($ot_regular_total) : ""), 
            ($ot_restday_total ? $this->attcompute->sec_to_hm($ot_restday_total) : ""), 
            ($ot_holiday_total ? $this->attcompute->sec_to_hm($ot_holiday_total) : ""), 
            ($lateut_lec_total ? $this->attcompute->sec_to_hm($lateut_lec_total) : ""), 
            ($lateut_lab_total ? $this->attcompute->sec_to_hm($lateut_lab_total) : ""), 
            ($lateut_admin_total ? $this->attcompute->sec_to_hm($lateut_admin_total) : ""), 
            ($lateut_overload_total ? $this->attcompute->sec_to_hm($lateut_overload_total) : ""), 
            ($absent_lec_total ? $this->attcompute->sec_to_hm($absent_lec_total) : ""), 
            ($absent_lab_total ? $this->attcompute->sec_to_hm($absent_lab_total) : ""), 
            ($absent_admin_total ? $this->attcompute->sec_to_hm($absent_admin_total) : ""), 
            $service_credit_total, ($cto_total ? $this->attcompute->sec_to_hm($cto_total) : ""), 
            ($holiday_lec_total != 0 ? $this->time->minutesToHours($holiday_lec_total) : ""), 
            ($holiday_lab_total != 0 ? $this->time->minutesToHours($holiday_lab_total) : ""), 
            ($holiday_admin_total != 0 ? $this->time->minutesToHours($holiday_admin_total) : ""), 
            ($holiday_overload_total != 0 ? $this->time->minutesToHours($holiday_overload_total) : ""), 
            $holiday_total, 
            $total_absent,
            $emergency_total,
            $sick_total,
            $vacation_total,
            $other_total);
    }

    function checkAttendanceDate($date){
        return $this->db->query("SELECT * FROM employee_attendance_date WHERE `date` = '$date'");
    }

    function saveAttendanceDate($employeelist, $date){
        $this->db->query("INSERT INTO employee_attendance_date SET employee_list = '$employeelist', `date` = '$date'");
    }

    function updateAttendanceDate($employeelist, $date){
        $this->db->query("UPDATE employee_attendance_date SET employee_list = '$employeelist' WHERE `date` = '$date'");
    }

    // function getAbsentTardySummary($dateFrom, $dateTo, $employeeId) {
    //     return $this->db->query("SELECT eant.`employeeid`, CONCAT(e.`fname`, ' ', e.`lname`) AS fullname, `date`, CONCAT(`late`,`undertime`) AS late, `absent`, CONCAT(vl,sl,el,other) AS `leave` FROM `employee_attendance_nonteaching` AS eant
    //                                 INNER JOIN employee AS e ON e.employeeid=eant.employeeid
    //                             WHERE `date` BETWEEN '$dateFrom' AND '$dateTo' 
    //                                 AND (late NOT IN ('', '--')  OR undertime NOT IN ('', '--')
    //                                 OR absent NOT IN ('', '--'))
    //                                 AND eant.employeeid = '$employeeId'
    //                             UNION
    //                             SELECT eat.`employeeid`, CONCAT(e.`fname`, ' ', e.`lname`) AS fullname, `date`, CONCAT(`lateut_lec`,`lateut_lab`,`lateut_admin`,`lateut_overload`) AS late, CONCAT(`absent_lec`,`absent_lab`,`absent_admin`) AS absent, CONCAT(vacation,emergency,sick,other) AS `leave` FROM `employee_attendance_teaching` AS eat
    //                                 INNER JOIN employee AS e ON e.employeeid=eat.employeeid
    //                             WHERE `date` BETWEEN '$dateFrom' AND '$dateTo' 
    //                                 AND (lateut_lec NOT IN ('', '--')  OR lateut_lab NOT IN ('', '--')  OR lateut_admin NOT IN ('', '--') OR lateut_overload NOT IN ('', '--') 
    //                                 OR absent_lec NOT IN ('', '--') OR absent_lab NOT IN ('', '--') OR absent_admin NOT IN ('', '--')) 
    //                                 AND eat.employeeid = '$employeeId'
    //                             ORDER BY `employeeid`,`date`")->result();
    // }

    function getAbsentTardySummary($dateFrom, $dateTo, $employeeId, $teachingType) {
        if ($teachingType == 'teaching') {
            return $this->db->query("SELECT eat.`employeeid`,
                                        CONCAT(e.`fname`, ' ', e.`lname`) AS fullname,
                                        GROUP_CONCAT(DATE_FORMAT(`date`, '%d' ) ORDER BY DATE) AS `date`,
                                        GROUP_CONCAT(CONCAT(`lateut_lec`,`lateut_lab`,`lateut_admin`,`lateut_overload`) ORDER BY DATE) AS late,
                                        GROUP_CONCAT(CONCAT(`absent_lec`,`absent_lab`,`absent_admin`) ORDER BY DATE) AS absent,
                                        GROUP_CONCAT(CONCAT(vacation, emergency, sick, other) ORDER BY DATE) AS `leave`
                                    FROM `employee_attendance_teaching` AS eat
                                        INNER JOIN employee AS e ON e.employeeid=eat.employeeid
                                    WHERE `date` BETWEEN '$dateFrom' AND '$dateTo' 
                                        AND (lateut_lec NOT IN ('', '--')  OR lateut_lab NOT IN ('', '--')  OR lateut_admin NOT IN ('', '--') OR lateut_overload NOT IN ('', '--') 
                                        OR absent_lec NOT IN ('', '--') OR absent_lab NOT IN ('', '--') OR absent_admin NOT IN ('', '--')) 
                                        AND eat.employeeid = '$employeeId'
                                    ORDER BY `date`")->result();
        } else {
            return $this->db->query("SELECT eant.`employeeid`,
                                        CONCAT(e.`fname`, ' ', e.`lname`) AS fullname,
                                        GROUP_CONCAT(DATE_FORMAT(`date`, '%d' ) ORDER BY DATE) AS `date`,
                                        GROUP_CONCAT(CONCAT(`late`, `undertime`) ORDER BY DATE) AS late,
                                        GROUP_CONCAT(`absent` ORDER BY DATE) AS `absent`,
                                        GROUP_CONCAT(CONCAT(vl, sl, el, other) ORDER BY DATE) AS `leave`
                                    FROM `employee_attendance_nonteaching` AS eant
                                        INNER JOIN employee AS e ON e.employeeid=eant.employeeid
                                    WHERE `date` BETWEEN '$dateFrom' AND '$dateTo' 
                                        AND (late NOT IN ('', '--')  OR undertime NOT IN ('', '--')
                                        OR absent NOT IN ('', '--'))
                                        AND eant.employeeid = '$employeeId'
                                    ORDER BY `date`")->result();
        }
    }

    function getEmployeeIDTeachingTypeList($employeeid='all', $campusid='all', $employmentStatus='') {
        $where = "WHERE 1";
        $where .= $campusid != 'All' && $campusid ? " AND campusid = '$campusid'" : '';
        $where .= $employeeid != 'all' && $employeeid ? " AND employeeid = '$employeeid'" : '';
        $where .= $employmentStatus ? " AND employmentstat = '$employmentStatus'" : '';
        return $this->db->query("SELECT DISTINCT employeeid, teaching FROM employee $where")->result();
    }

    function checkConfirmedAttendance($employeeid, $date_from, $date_to){
        $query = $this->db->query("SELECT * FROM attendance_confirmed WHERE employeeid = '$employeeid' AND cutoffstart = '$date_from' AND cutoffend = '$date_to'")->num_rows();
        $query_nt = $this->db->query("SELECT * FROM attendance_confirmed_nt WHERE employeeid = '$employeeid' AND cutoffstart = '$date_from' AND cutoffend = '$date_to'")->num_rows();
        return $query+$query_nt;
    }

    function reprocessFacialLogsNightShift($dateFrom, $dateTo, $empid = "", $starttime=""){
        $period = $this->getDatesFromRange($dateFrom, $dateTo);    
        $wh = "";
        if($empid) $wh = " AND employeeid = '$empid'";
        $process = 0;
        $emplist = $this->db->query("SELECT employeeid FROM employee WHERE isactive = 1 $wh")->result_array();
        foreach ($emplist as $rw => $val) {
            $empId = $val['employeeid'];
            foreach ($period as $key => $value) {
                $tommorow = date('Y-m-d', strtotime($value. ' +1 day'));
                if($this->extensions->checkSchedIfHasNightShift($empId, $value) > 0){
                    
                    // Checkif has logs
                    if($dateFrom && $empid){
                        $nextSched = $this->attcompute->displaySched($empId,$tommorow);
                    }

                    $record = $this->db->query("SELECT `id` FROM facial_Log WHERE employeeid = '$empId' AND DATE(FROM_UNIXTIME(FLOOR(`time`/1000))) = '$value'")->result_array();
                    if (count($record) > 0) {
                        $In = $Out = "";
                        // Create Time And Time Out
                        // OUT
                        $timeinRecord = $this->db->query("SELECT `time`, deviceKey, `date` FROM facial_Log WHERE employeeid = '$empId' AND DATE(FROM_UNIXTIME(FLOOR(`time`/1000))) = '$value' ORDER BY `time` DESC LIMIT 1")->result_array();
                        if(count($timeinRecord) > 0){
                            $In = date("Y-m-d H:i:s", substr($timeinRecord[0]['time'], 0, 10));
                        }
                        // IN
                        // Check if $nextSched exists and has data
                        if (isset($nextSched) && $nextSched->num_rows() > 0) {
                            $sched_start = $nextSched->row()->starttime;

                            
                            // Get the most recent time-out record before the schedule start time
                            $timeOutRecord = $this->db->query("
                                SELECT `time`, deviceKey 
                                FROM facial_Log 
                                WHERE employeeid = '$empId' 
                                AND DATE(FROM_UNIXTIME(FLOOR(`time`/1000))) = '$tommorow' 
                                AND TIME(FROM_UNIXTIME(FLOOR(`time`/1000))) < '$sched_start' 
                                ORDER BY `time` DESC LIMIT 1
                            ")->result_array();

                            if (count($timeOutRecord) > 0) {
                                // Get the most recent time-in record on the next day
                                $timeinRecordTom = $this->db->query("
                                    SELECT `time`, deviceKey, `date` 
                                    FROM facial_Log 
                                    WHERE employeeid = '$empId' 
                                    AND DATE(FROM_UNIXTIME(FLOOR(`time`/1000))) = '$tommorow' 
                                    ORDER BY `time` DESC LIMIT 1
                                ")->result_array();
                                
                                if (count($timeinRecordTom) > 0) {
                                    $inTom = date("Y-m-d H:i:s", substr($timeinRecordTom[0]['time'], 0, 10));
                                    $OutTom = date("Y-m-d H:i:s", substr($timeOutRecord[0]['time'], 0, 10));

                                    // Check if the time-out is later than time-in, then fetch the earliest time-in record
                                    if (strtotime($OutTom) >= strtotime($inTom)) {
                                        $sched_start = date('H:i:s', strtotime($nextSched->row()->starttime. '- 2hours'));
                                        $timeOutRecord = $this->db->query("
                                            SELECT `time`, deviceKey 
                                            FROM facial_Log 
                                            WHERE employeeid = '$empId' 
                                            AND DATE(FROM_UNIXTIME(FLOOR(`time`/1000))) = '$tommorow' 
                                            AND TIME(FROM_UNIXTIME(FLOOR(`time`/1000))) < '$sched_start' 
                                            ORDER BY `time` ASC LIMIT 1
                                        ")->result_array();
                                         if(count($timeOutRecord) > 0){
                                            $Out = date("Y-m-d H:i:s", substr($timeOutRecord[0]['time'], 0, 10));
                                        }
                                    }else{
                                        $timeOutRecord = $this->db->query("
                                            SELECT `time`, deviceKey 
                                            FROM facial_Log 
                                            WHERE employeeid = '$empId' 
                                            AND DATE(FROM_UNIXTIME(FLOOR(`time`/1000))) = '$tommorow' 
                                            ORDER BY `time` ASC LIMIT 1
                                        ")->result_array();

                                        if(count($timeOutRecord) > 0){
                                            $Out = date("Y-m-d H:i:s", substr($timeOutRecord[0]['time'], 0, 10));
                                        }
                                    }
                                }
                            }
                        } else {
                            // If no schedule found, fetch the earliest time-out record
                            $timeOutRecord = $this->db->query("
                                SELECT `time`, deviceKey 
                                FROM facial_Log 
                                WHERE employeeid = '$empId' 
                                AND DATE(FROM_UNIXTIME(FLOOR(`time`/1000))) = '$tommorow' 
                                ORDER BY `time` ASC LIMIT 1
                            ")->result_array();

                            if(count($timeOutRecord) > 0){
                                $Out = date("Y-m-d H:i:s", substr($timeOutRecord[0]['time'], 0, 10));
                            }
                        }

                        
                        // Check if timesheet is existing 
                        
                        $table_equivalent = array(
                            "timesheet" => "timesheet_night_shift_history",
                        );
                
                        foreach ($table_equivalent as $from_table => $to_table) {
                            // Fetch columns from both tables in a single query per table
                            $from_columns = $this->db->query("SHOW COLUMNS FROM $from_table")->result();
                            $to_columns = $this->db->query("SHOW COLUMNS FROM $to_table")->result();
                        
                            // Create associative arrays for column names excluding auto_increment
                            $from_column_list = [];
                            $to_column_list = [];
                        
                            foreach ($from_columns as $column_data) {
                                if ($column_data->Extra != "auto_increment") {
                                    $from_column_list[$column_data->Field] = $column_data->Field;
                                }
                            }
                        
                            foreach ($to_columns as $column_data) {
                                if ($column_data->Extra != "auto_increment") {
                                    $to_column_list[$column_data->Field] = $column_data->Field;
                                }
                            }
                        
                            // Find common columns between the from and to tables
                            $common_columns = array_intersect_key($from_column_list, $to_column_list);
                        
                            if (count($common_columns) > 0) {
                                // Build the order_column list
                                $order_column = implode(',', array_keys($common_columns));
                        
                                // Prepare the SQL query
                                $query = "INSERT INTO $to_table($order_column) 
                                          SELECT $order_column 
                                          FROM $from_table 
                                          WHERE userid = '$empId'";
                        
                                // Execute the query (Uncomment below if executing query)
                                $this->db->query($query);
                            }
                        }
                        
                        $timesheetRecord = $this->db->query("DELETE FROM timesheet WHERE userid = '$empId' AND DATE_FORMAT(timein,'%Y-%m-%d') = '$value'");
                        // Create TimeSheet data
                        $timesheetData = array();
                        $timesheetData['userid'] = $empId;
                        $timesheetData['timein'] = $In;
                        $timesheetData['timeout'] = $Out;
                        $timesheetData['otype'] = "Facial";
                        $timesheetData['addedby'] = $timeinRecord[0]['deviceKey'];
                        $this->db->insert("timesheet", $timesheetData);
                        $process++;

                    }
                }
            }
        }
        return $process;
    }

    function getDatesFromRange($start, $end, $format = 'Y-m-d') {
        $array = array();
        $interval = new DateInterval('P1D');

        $realEnd = new DateTime($end);
        $realEnd->add($interval);

        $period = new DatePeriod(new DateTime($start), $interval, $realEnd);

        foreach($period as $date) { 
            $array[$date->format($format)] = $date->format($format); 
        }

        return $array;
    }

    function insertInConfirmWorker($cutoff,$employeeid,$username,$teaching_type){
        $result = '';
        // echo "<pre>";print_r(array($cutoff,$employeeid));die;
        $title = "Attendance Confirmation of cutoff ".$this->getCutOffRange($cutoff);
        $query = $this->db->query("INSERT INTO confirm_att_list SET
                            code='Confirm attendance',
                            title='{$title}',
                            employeeid='{$employeeid}',
                            cutoff='{$cutoff}',
                            teaching_type='{$teaching_type}',
                            status='Pending',
                            user='{$username}'
                        ");
        if($query){
            $result = 'success';
        }
        return $result;
    }

    function getCutOffRange($dateRange) {
        $dates = explode(',', $dateRange); // Split the string into an array
    
        if (count($dates) !== 2) {
            return "Invalid date range format";
        }
    
        $startDate = DateTime::createFromFormat('Y-m-d', trim($dates[0]));
        $endDate = DateTime::createFromFormat('Y-m-d', trim($dates[1]));
    
        if (!$startDate || !$endDate) {
            return "Invalid date format";
        }
    
        $formattedStart = $startDate->format('F d, Y'); // e.g., December 01, 2024
        $formattedEnd = $endDate->format('F d, Y'); // e.g., December 31, 2024
    
        return "$formattedStart - $formattedEnd";
    }
    
    function checkIfAttconfirmPending($cutoff){
        $result = 'false';
        $query = $this->db->query("SELECT * FROM confirm_att_list WHERE cutoff='{$cutoff}' AND status='Pending'");
        if($query->num_rows() > 0){
            $result = 'true';
        }
        return  $result;
    }

    //worker codes

    public function getEmployeeListCounts($where){
        return $this->db->query("SELECT 
            CONCAT(a.lname, ', ', a.fname , ' ', a.mname) AS fullname,
            a.employeeid, 
            a.fname, 
            a.lname, 
            SUBSTRING(a.`mname`, 1, 1) as mname, 
            b.`description` as department, 
            TRIM(c.`description`) 
            as position_desc, 
            d.description as employement_desc,
            a.campusid
            FROM employee a 
            LEFT JOIN `code_department` b on a.`deptid` = b.`code` 
            LEFT JOIN `code_position` c on a.`positionid` = c.`positionid` 
            LEFT JOIN `code_status` d on a.`employmentstat` = d.`code`
            WHERE 1 = 1 $where
            ORDER BY fullname ASC")->num_rows();
    }
    
}
/* End of file employee.php */
/* Location: ./application/models/employee.php */
