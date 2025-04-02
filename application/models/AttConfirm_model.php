<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AttConfirm_model extends CI_Model {

	private $user;

	public function __construct() {
		parent::__construct();
        $this->load->model("time", "time");
	}

	public function getServerTime(){
		$query_time = $this->db->query("SELECT CURRENT_TIMESTAMP ")->row()->CURRENT_TIMESTAMP;
		return $query_time;
	}

    public function getEmployeeList($where = "", $orderBy = ""){
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

    public function checkIfHasLog($employeeid, $date) {
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

    public function getempteachingtype($user = ""){
        $return = false;
        $query = $this->db->query("SELECT teachingtype FROM employee WHERE employeeid='$user'");
        if($query->num_rows() > 0)  $return = ($query->row(0)->teachingtype == "teaching" ? true : false);
        return $return;    
      }
    
    public function isTeachingRelated($user = ""){
        $query = $this->db->query("SELECT teachingtype, trelated FROM employee WHERE employeeid='$user'");
        return $query->row(0)->teachingtype == 'nonteaching' && $query->row(0)->trelated == '1';
    }

    function getAttendanceTeaching($employeeid, $datesetfrom, $datesetto){
        $attendance = array();
        $date_range = $this->displayDateRange($datesetfrom, $datesetto);
        foreach ($date_range as $date) {
            $attendance[$date->dte] = $this->db->query("SELECT * FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND `date` = '$date->dte' ORDER BY `id`")->result();
        }
        return $attendance;
    }

    function exp_time($time) { //explode time and convert into seconds
        $time = explode(':', $time);
        $h = $m = 0;
        if(isset($time[0]) && is_numeric($time[0])) { $h = $time[0];} else{ $h = 0;}
        if(isset($time[1]) && is_numeric($time[1])) { $m = $time[1]; }else {$m = 0;}
        $time = $h * 3600 + $m * 60;
        return $time;
    }

    function getAttendanceNonteaching($employeeid, $datesetfrom, $datesetto){
        $attendance = array();
        $date_range = $this->displayDateRange($datesetfrom, $datesetto);
        foreach ($date_range as $date) {
            $attendance[$date->dte] = $this->db->query("SELECT * FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date->dte' ORDER BY `id`")->result();
        }
        return $attendance;
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
            $ottime = $this->exp_time($row->total);

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

    function getFirstDayOfWeek($eid=""){
        $return = "";
        $query = $this->db->query("SELECT DISTINCT(dayofweek) FROM employee_schedule_history WHERE employeeid = '$eid' ORDER BY idx ASC LIMIT 1")->result();
       
       if($query)
       {
       switch($query[0]->dayofweek)
       {
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

    function getLastDayOfWeek($eid=""){
        $return = "";
        $query = $this->db->query("SELECT DISTINCT(dayofweek) FROM employee_schedule_history WHERE employeeid = '$eid' ORDER BY idx DESC LIMIT 1")->result();
       if($query)
       {
       switch($query[0]->dayofweek)
       {
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

    function getFacultyLoadsClassfication() {
		return $this->db->query("SELECT id, description FROM faculty_load_classification")->result();
	}

    function getEmployeeATH($employeeid){
        $designation_list = array();
        $overload_limit = $ath = 0;
        $designation = $this->extensions->getEemployeeCurrentData($employeeid, "designation");
        if($designation) $designation_list[] = $designation;
        $sub_designation = $this->extensions->getEemployeeCurrentData($employeeid, "sub_designation");
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

    public function getTapCampus($userid, $date){
        $query = $this->db->query("SELECT username FROM timesheet WHERE userid = '$userid' AND DATE(timein) = '$date'");
        if($query->num_rows() > 0) return $query->row()->username;
        else return false;
    }

    public function isFacial($userid, $date){ //get device name if the otype is facial
        $result = false;
        $query = $this->db->query("SELECT * FROM timesheet WHERE otype='FACIAL' AND userid = '$userid' AND DATE(timein) = '$date' LIMIT 1 ");
        if($query->num_rows() > 0){
            $subquery = $this->db->query("SELECT deviceKey FROM facial_Log WHERE employeeid='$userid' AND DATE(date)='$date' LIMIT 1");
            if($subquery->num_rows() > 0){
                $row = $subquery->row_array();
                $devicekey = $row['deviceKey'];
                $getDeviceName = $this->db->query("SELECT deviceName FROM facial_heartbeat WHERE deviceKey='$devicekey'");
                if($getDeviceName->num_rows() > 0){
                    $deviceName = $getDeviceName->row_array();
                    $result = $deviceName['deviceName'];
                }
            }
        }
        return $result;        
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

    public function getHolidayTypeRate($holiday_type, $teachingtype){
		$q_holiday = $this->db->query("SELECT * FROM code_holiday_type WHERE holiday_type = '$holiday_type'");
		if($q_holiday->num_rows() > 0){
			if($teachingtype == "teaching") return $q_holiday->row()->t_rate;
			else return $q_holiday->row()->nt_rate;
		}
	}

    function displaySched($eid="",$date = ""){
        $return = "";
        $wc = "";
        $latestda = date('Y-m-d', strtotime($this->extensions->getLatestDateActive($eid, $date)));
        if($date >= $latestda) $wc .= " AND DATE(dateactive) = DATE('$latestda')";
        // $query = $this->db->query("SELECT dateactive FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) $wc ORDER BY dateactive DESC,starttime DESC LIMIT 1;");
        $query = $this->db->query("SELECT dateactive FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE('$date') $wc ORDER BY dateactive DESC,starttime DESC LIMIT 1;");
        if($query->num_rows() > 0){
            $da = $query->row(0)->dateactive;
            // $query = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) AND DATE_FORMAT(dateactive,'%Y-%m-%d %H') = DATE_FORMAT('$da','%Y-%m-%d %H') GROUP BY starttime,endtime ORDER BY starttime;"); 
            // $query = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE('$date') AND DATE_FORMAT(dateactive,'%Y-%m-%d %H') = DATE_FORMAT('$da','%Y-%m-%d %H') GROUP BY starttime,endtime ORDER BY starttime;"); // Commented for change sched
            $query = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE('$date') AND dateactive = '$da' GROUP BY starttime,endtime ORDER BY starttime;"); 
            // echo "<pre>"; print_r($this->db->last_query()); 
        }
        return $query; 
    }

    function displayLogTime($eid="",$date="",$tstart="",$tend="",$tbl="NEW",$seq=1,$absent_start='',$earlyd='',$used_time=array(),$campus='',$night_shift=0,$previous_campus=''){
        $haslog = false; $timein = $timeout = $otype = $is_ob = ""; 
        $tbl = $tbl == 'NEW' ? 'timesheet' : 'timesheet_bak';
        $sequence = $seq-1;
        if ($night_shift == 1) {
            $query = $this->db->query("SELECT t.timein, t.timeout, t.otype, t.addedby, t.ob_id FROM $tbl t WHERE userid='$eid' AND DATE(timein)='$date' AND timein != timeout AND (UNIX_TIMESTAMP(timeout) - UNIX_TIMESTAMP(timein) ) > '60' ORDER BY timein;");
        } else {
            $query = $this->db->query("SELECT t.timein, t.timeout, t.otype, t.addedby, t.ob_id, f.campusid FROM $tbl t LEFT JOIN facial_heartbeat f ON t.addedby = f.deviceKey WHERE t.userid = '$eid' AND DATE(t.timein) = '$date' ORDER BY t.timein LIMIT $sequence, 999999;");
        }
        
        if ((($query->num_rows() > 0 && $query->row(0)->timein) && $campus) || $campus || $night_shift) { 
            // FOR NIGHT SHIFT / TEACHING / IF ISSET CAMPUS, THEN CHECK IF THERE IS FACIAL LOGS IN THE CAMPUS
            $first = 1; $lastCampus = '';
            $otype = isset($query->row(0)->otype) ? $query->row(0)->otype : 0;
            $is_ob = isset($query->row(0)->ob_id) ? $query->row(0)->ob_id : 0;
            foreach ($query->result() as $logs) {
                if (count($used_time) > 0 && $previous_campus == $campus && $logs->addedby && $otype == $logs->otype) { // IF SAME CAMPUS FROM PREVIUS LOGS, RETURN LAST TIMEIN AND TIMEOUT
                    $timein = $used_time[0];
                    $timeout = $used_time[1];
                    break;
                } else {
                    if (($logs->campusid == $campus && $logs->campusid == $lastCampus) || $first) { // $first IS TRUE AT FIRST LOOP TO GET FIRST TIMEIN AND TIMEOUT
                        if ($otype != 'Facial' || $campus == $logs->campusid) { // IF FACIAL, ALWAYS CHECK FOR CAMPUS
                            if ($first) { $first = 0;
                                $timein = $logs->timein;
                            }
                            $timeout = $logs->timeout;
                        }
                    } else {
                        break; // BREAK IF NEXT LOG IS OTHER CAMPUS 
                    }
    
                    $lastCampus = $logs->campusid;
                }
            }

            if ($this->isTimeRangeWithin($timein, $timeout, $tstart, $tend, $night_shift)) {
                $used_time = array($timein, $timeout);
                $haslog = true;
            }
        } else { 
            // FOR REGULAR SHIFT / NON TEACHING
            $query = $this->db->query("SELECT timein,timeout,otype,addedby,ob_id FROM $tbl WHERE userid='$eid' AND (DATE(timein)='$date' OR DATE(timeout)='$date') AND TIME(timein)<='$tend' AND TIME(timeout) > '$tstart' AND timein != timeout ORDER BY timein");

            if ($query->num_rows() > 0 && $query->row(0)->timein) {
                $timein = $query->row(0)->timein;
                $timeout = $query->row(0)->timeout;
                $otype = isset($query->row(0)->otype) ? $query->row(0)->otype : 0;
                $is_ob = isset($query->row(0)->ob_id) ? $query->row(0)->ob_id : 0;

                if ($this->isTimeRangeWithin($timein, $timeout, $tstart, $tend, $night_shift)) {
                    $used_time = array($timein, $timeout);
                    $haslog = true;
                }
            } else {
                // SOME MISSING LOGS FROM TIME SHEET
                $query = $this->db->query("SELECT MIN(a.`time`) AS timein, MAX(a.`time`) AS timeout, a.deviceKey, a.`date` FROM facial_Log a INNER JOIN facial_heartbeat b ON a.deviceKey = b.deviceKey WHERE a.employeeid = '$eid' AND DATE(FROM_UNIXTIME(FLOOR(a.`time`/1000))) = '$date'");
                if ($query->num_rows() > 0 && $query->row(0)->timein) {
                    $timein = date("Y-m-d H:i:s", substr($query->row(0)->timein, 0, 10));
                    $timeout = date("Y-m-d H:i:s", substr($query->row(0)->timeout, 0, 10));
                    $otype = 'Facial';
                    $used_time = array($timein, $timeout);
                    $haslog = true;
                } else {
                    // DISPLAY THE TIME-IN OF EMPLOYEE IMMEDIATELY.
                    $query = $this->db->query("SELECT stamp_in, stamp_out FROM login_attempts_terminal WHERE user_id='$eid' AND DATE(FROM_UNIXTIME(FLOOR(`time_in`/1000)))='$date'");
                    if ($query->num_rows() > 0) {
                        $timein = $query->row(0)->stamp_in;
                        $timeout = $query->row(0)->stamp_out;
                        $otype = 'Facial';
                        $used_time = array($timein, $timeout);
                        $haslog = true;
                    }
                }

            }
        }

        if ($haslog == false) {
            $timein = $timeout = $otype = '';
            $used_time = array();
        }

        return array($timein,$timeout,$otype,$haslog,$used_time,$is_ob);
    }

    function isTimeRangeWithin($timein='', $timeout, $timestart, $timeend, $isNightShift) {
        $timein = date("H:i:s", strtotime($timein));
        $timeout = date("H:i:s", strtotime($timeout));
        $timestart = date("H:i:s", strtotime($timestart));
        $timeend = date("H:i:s", strtotime($timeend));

        if ($isNightShift == 1) {
            return (
                ($timein <= $timestart || $timeout >= $timeend) || // Handles crossing midnight
                ($timein <= $timestart && $timeout >= $timeend)    // Fully covers range
            );
        }
        
        return ($timein <= $timestart && $timeout >= $timeend) || ($timein >= $timestart && $timein <= $timeend) || ($timeout >= $timestart && $timeout <= $timeend);
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
        $sched = $this->attcompute->displaySched($eid,$date);
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
                if      ($hasSched)  $otreg += $this->attcompute->exp_time($value->total);
                else                 $otrest += $this->attcompute->exp_time($value->total);
                
                if($this->isHoliday($date)){

                    $otreg = $otrest = 0;
                    $othol += $this->attcompute->exp_time($value->total);
                }
            }
        }
        
        $otreg = ($otreg) ? $this->attcompute->sec_to_hm($otreg) : 0;
        $otrest = ($otrest) ? $this->attcompute->sec_to_hm($otrest) : 0;
        $othol = ($othol) ? $this->attcompute->sec_to_hm($othol) : 0;
        return array($otreg,$otrest,$othol);
    }

    function displayCOC($eid="",$date="",$hasSched=true){
        $query = $this->db->query("
                                    SELECT b.id
                                    FROM overtime_request a INNER JOIN ot_app b ON a.aid = b.id
                                    WHERE a.employeeid='$eid' AND ('$date' BETWEEN a.dfrom AND a.dto) AND a.status = 'APPROVED' AND b.ot_type = 'CTO'
                                ");
        return $query->num_rows();

    }

    function displaySCAttendance($eid, $date, $stime, $etime){
        $sc = 0;
        $isHalfDay = $sched_affected = "";
        $official_time = $stime.'|'.$etime;
        $query = $this->db->query("SELECT * FROM sc_app WHERE applied_by = '$eid' AND date = '$date' AND app_status = 'APPROVED'");
        if($query->num_rows() > 0){
            $sc = 1;
        }
        // if($sc_use){
        //     echo $isHalfDay.'~~'.$sc_use.'~~'.$date;
        // }
        
        return $sc;
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
                         $oltype = $this->employeemod->othLeaveDesc($ol);
                     }
                 }else{
                     $vl = $no_days >= 1 ? 1.00 : $no_days;  
                     $ol = $res->leavetype; 
                     $oltype = $this->employeemod->othLeaveDesc($ol);
                 }  
             }
             else if(strpos($res->leavetype, 'PL-') !== false && $query->row(0)->paid == "YES")
             {     
                 if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                     if(in_array($time_aff, $arr_sched_aff)){
                         $vl = $no_days; 
                         $ol = $res->leavetype; 
                         $oltype = $this->employeemod->othLeaveDesc($ol);
                     }
                 }else{
                     $vl = $no_days >= 1 ? 1.00 : $no_days;  
                     $ol = $res->leavetype; 
                     $oltype = $this->employeemod->othLeaveDesc($ol);
                 }  
             }
             else if($res->leavetype == "EL" && $res->paid == "YES"){  
                 if($no_days == 0.50){
                     if(in_array($time_aff, $arr_sched_aff)){
                         $vl = $no_days; 
                         $ol = $res->leavetype; 
                         $oltype = $this->employeemod->othLeaveDesc($ol);
                     }
                 }else{
                     $vl = 1.00; 
                     $ol = $res->leavetype; 
                     $oltype = $this->employeemod->othLeaveDesc($ol);
                 }  
             }
             else if($res->paid == "YES" && ($res->leavetype != "VL" && $res->leavetype != "SL")){  
                 if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                     if(in_array($time_aff, $arr_sched_aff)){
                         
                         $ol = $res->leavetype; 
                         $oltype = $this->employeemod->othLeaveDesc($ol);
                         $ol = $no_days; 
                     }
                 }else{
                       
                     $ol = $res->leavetype; 
                     $oltype = $this->employeemod->othLeaveDesc($ol);
                     $ol = $no_days >= 1 ? 1.00 : $no_days;
                 }  
             }
             else if($res->leavetype == "SL" && $res->paid == "YES"){  
                 if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                     if(in_array($time_aff, $arr_sched_aff)){
                         $sl = $no_days; 
                         $ol = $res->leavetype; 
                         $oltype = $this->employeemod->othLeaveDesc($ol);
                     }
                 }else{
                     $sl = $no_days >= 1 ? 1.00 : $no_days;  
                     $ol = $res->leavetype; 
                     $oltype = $this->employeemod->othLeaveDesc($ol);
                 }  
             }
             else if($res->leavetype == "ABSENT"){  
                 if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                     if(in_array($time_aff, $arr_sched_aff)){
                         $abs_count = $no_days; 
                         $ol = $res->leavetype; 
                         $oltype = $this->employeemod->othLeaveDesc($ol);
                     }
                 }else{
                     $abs_count = $no_days >= 1 ? 1.00 : $no_days;  
                     $ol = $res->leavetype; 
                     $oltype = $this->employeemod->othLeaveDesc($ol);
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
                 $l_nopay_remarks = $this->employeemod->othLeaveDesc($ol).' APPLICATION (NO PAY)';
             }
             else{
                 $ol = $res->leavetype;  
                 $oltype = $this->employeemod->othLeaveDesc($ol);
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
                 $is_wfh = $this->attcompute->isWfhOB($eid,$date);
                 if($is_wfh->num_rows() == 1){
                     $ob_id = $is_wfh->row()->aid;
                     $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
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
 
    function hasWFHTimeRecord($id, $date){
        return $this->db->query("SELECT * FROM ob_timerecord WHERE base_id = '$id' AND t_date = '$date' AND status = 'APPROVED'");
    }

    function displayPVL($employeeid, $date){
         return $this->db->query("SELECT * FROM employee_proportional_vl a INNER JOIN proportional_vl_dates b ON a.id = b.base_id WHERE a.status = 'APPROVED' AND a.employeeid = '$employeeid' AND b.date = '$date'")->num_rows();
     }

    public function employeeAttendanceTeaching($employeeid, $date){
        $this->load->model("ob_application");
        $deptid = $this->getindividualdept($employeeid);
        $classification_arr = $this->getFacultyLoadsClassfication();
        $classification_list = array();
        foreach ($classification_arr as $key => $value) {
            $classification_list[$value->id] =  strtolower($value->description);
        }
        $edata = "NEW";
        $x = 0;
        $not_included_ol = array("ABSENT", "EL", "VL", "SL", "CORRECTION");
        $cto_id_list = $sc_app_id_list = array();
        $firstDayOfWeek = $this->getFirstDayOfWeek($employeeid);
        $lastDayOfWeek = $this->getLastDayOfWeek($employeeid);
        $lab_holhours = $lec_holhours = $admin_holhours = $rle_holhours = $holiday_type = "";
        $subtotaltpdlec = $totaltpdlec = $subtotaltpdlab = $totaltpdlab = $subtotaltpdadmin = $totaltpdadmin = $subtotaltpdrle = $totaltpdrle = 0;
        $weeklyOverload = $weeklyATH = $weeklyTotalOverload = 0;
        $rendered_lec = $rendered_lab = $rendered_admin = $rendered_rle = $t_rendered_lec = $t_rendered_lab = $t_rendered_admin = $t_rendered_rle = $vacation = $emergency = $sick = $other = 0;
        $lateutlec = $lateutlab = $lateutadmin = $tschedlec = $tschedlab = $tschedadmin = $lateutrle = $tschedrle = $absent =  $tpdlab = $tpdlec = $tpdrle = 0;
        $ot_remarks = $sc_app_remarks = $wfh_app_remarks = $seminar_app_remarks = $tempabsent = "";
        $hasLog = $isSuspension = false;
        list($ath, $overload_limit) = $this->getEmployeeATH($employeeid);
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
        $campus_tap = $this->getTapCampus($employeeid, $date);
        $deviceTap = $this->isFacial($employeeid, $date);
        $rate = 0;
        // Holiday
        $holiday = $this->isHolidayNew($employeeid,$date,$deptid); 
        if($holiday){
            $holidayInfo = $this->holidayInfo($date);
            if(isset($holidayInfo['holiday_type'])){
                $holiday_type = $holidayInfo['holiday_type'];
                if($holidayInfo['holiday_type']==5) $isSuspension = true;
                if($holidayInfo['holiday_type']==9) $isRegularHoliday = true;
                $rate = $this->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
            }
        }

        $dispLogDate = date("d-M (l)",strtotime($date));

        $sched = $this->displaySched($employeeid,$date);

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
                list($login,$logout,$q,$haslog_forremarks,$used_time, $is_ob) = $this->displayLogTime($employeeid,$date,$stime,$etime,"NEW",$seq,$absent_start,$earlydismissal,$used_time, $campus, $night_shift, isset($schedule_result[$rschedkey-1]->campus) ? $schedule_result[$rschedkey-1]->campus : '');
                if($seq == $countrow){
                    $weeklyOverloadOT = $this->displayLogTimeOutsideOT($employeeid,$date);
                    if($weeklyOverloadOT){
                        $overload += $weeklyOverloadOT;
                        $weeklyOverload += $weeklyOverloadOT;
                    }
                }

                if($login=='0000-00-00 00:00:00') $login = '';
                if($logout=='0000-00-00 00:00:00') $logout = '';

                list($otreg,$otrest,$othol) = $this->displayOt($employeeid,$date,true);
                if($otreg || $otrest || $othol){
                    $ot_remarks = "OVERTIME APPLICATION";
                }

                $coc = $this->displayCOC($employeeid,$date,true);
                if($coc > 0){
                    if($ot_remarks != "APPROVED COC APPLICATION"){
                        $ot_remarks.=($ot_remarks?", APPROVED COC APPLICATION":"APPROVED COC APPLICATION");
                    }
                }

                $sc_application = $this->displaySCAttendance($employeeid,$date, $stime, $etime);
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
                else if($holiday && $isCreditedHoliday) $absent = "";
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
                if($el || $vl || $pvl || $sl || ($holiday && $isCreditedHoliday) || $cto || $sc_app  || $isOverload){
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

                if($el || $vl || $pvl || $sl || $is_half_holiday || ($holiday && $isCreditedHoliday) || $isOverload) { 
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
                        else if($holiday && $isCreditedHoliday) $absent_ = "";
                        if ($vl_ >= 1  || $el_ >= 1 || $sl_ >= 1 || $ob_ >= 1 ){
                            $absent_ = "";
                        }
                        if ($vl_ > 0 || $el_ > 0 || $sl_ > 0 || $ob_ > 0){
                            $absent_ = "";
                        }
                        
                        // Late / Undertime
                        list($lateutlec_,$lateutlab_,$lateutadmin_,$tschedlec_,$tschedlab_,$tschedadmin_,$lateutrle_,$tschedrle_, $lateut_rem_) = $this->attcompute->displayLateUTNS($stime_,$etime_,$tardy_start_,$login_,$logout_,$type_,$absent_);
                        if($el_ || $vl_  || $sl_ || ($holiday && $isCreditedHoliday)){
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
        $sched = $this->displaySched($employeeid,$date);

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
                    if($oltype == "ABSENT") $absent = $absent;
                    else if($holiday && $isCreditedHoliday) $absent = "";

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
                    if($el || $vl || $sl || $service_credit || ($holiday && $isCreditedHoliday)) $lateutlec = $utlec = "";

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

                    if($el || $vl || $sl  || $service_credit || ($holiday && $isCreditedHoliday) || $cto || $sc_app || $ishalfday) $lateutlec = $utlec = $absent = "";
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
                        if(in_array("undertime", $ob_data)) $log_remarks = "EXCUSED UNDERTIME";
                        else{
                            $log_remarks = "<span style='color:red'>UNEXCUSED UNDERTIME</span>";
                            $ob_type = false;
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

    public function getDtrPayrollCutoffPair($dtr_start='',$dtr_end='',$payroll_start='',$payroll_end='',$dtr_id='',$p_id=''){
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

    public function checkIfCutoffNoDTR($cutoffstart, $cutoffto){
        $cutoffid = $this->db->query("SELECT ID FROM cutoff WHERE CutoffFrom = '$cutoffstart' AND CutoffTo = '$cutoffto' ")->row()->ID;
        $q_nodtr = $this->db->query("SELECT nodtr FROM payroll_cutoff_config WHERE baseid = '$cutoffid' ");
        if($q_nodtr->num_rows() > 0) return $q_nodtr->row()->nodtr;
        else return false;
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

    function getOvertimeAmountDetailed($empid, $ot_details, $emp_ot=''){
        #echo "<pre>"; print_r($ot_details);
        $this->load->model('utils');
        $this->load->model('payrollcomputation');
        $this->load->model('time');
        $this->load->model('income');
        $ot_amount = 0;
        $ot_type = "";

        $rate_per_hour = ($this->income->getEmployeeSalaryRate1($empid, "daily") / 8);
        $rate_per_minute = $rate_per_hour / 60;
        $employeement_status = $this->extras->getEmploymentStatus($empid);
        $setup = $this->payrollcomputation->getOvertimeSetup($employeement_status);

        $percent = 100;
        foreach ($ot_details as $ot_type => $holiday_type_list) {
            foreach ($holiday_type_list as $holiday_type => $ot_info) {
                $ot_min = ($emp_ot) ? $emp_ot : $ot_info[0];
                $ot_min = $this->sec_to_hm($ot_min);
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

    function sec_to_hm($time) { //convert seconds to hh:mm
        $time = (int) $time;
        if(is_numeric($time)){
            $hour = floor($time / 3600);
            $minute = strval(floor(($time % 3600) / 60));
            if ($minute == 0) {
                $minute = "00";
            } else {
                $minute = $minute;
            }

            if ($hour == 0) {
                $hour = "00";
            } else {
                $hour = $hour;
            }
            $time = $hour . ":" . str_pad($minute,2,'0',STR_PAD_LEFT);
            return $time;
        }
    }

    function getPayrollCutoff($cutoffstart, $cutoffto){
        $cutoffid = $this->db->query("SELECT ID FROM cutoff WHERE CutoffFrom = '$cutoffstart' AND CutoffTo = '$cutoffto' ")->row()->ID;
        $query = $this->db->query("SELECT * FROM payroll_cutoff_config WHERE baseid = '$cutoffid' ")->result_array();
        return $query;
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
            $sched = $this->attcompute->displaySched($employeeid,$date);
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
                        list($login,$logout,$q,$haslog_forremarks,$used_time) = $this->attcompute->displayLogTime($employeeid,$date,$starttime,$endtime,"NEW",$seq,$absent_start,$earlydismissal,$used_time);
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

    function getEmployeeSalaryRate1($employeeid, $column){
    	$query = $this->db->query("SELECT * FROM payroll_employee_salary WHERE employeeid = '$employeeid' ORDER BY TIMESTAMP DESC LIMIT 1 ");
    	if($query->num_rows() > 0) return $query->row()->$column;
    	else return false;    	
    }
}