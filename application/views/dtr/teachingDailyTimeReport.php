<?php 
   $style = '
<style>
    #snackbar {
    visibility: hidden;
    min-width: 250px;
    margin-left: -125px;
    background-color: #1032CC;
    text-align: center;
    border-radius: 2px;
    padding: 16px;
    position: fixed;
    z-index: 1;
    left: 40%;
    bottom: 50%;
    font-size: 16px;
    }

    #snackbar.show {
    visibility: visible;
    -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
    animation: fadein 0.5s, fadeout 0.5s 2.5s;
    }

    @-webkit-keyframes fadein {
    from {bottom: 50%; opacity: 0;} 
    to {bottom: 50%; opacity: 1;}
    }

    @keyframes fadein {
    from {bottom: 50%; opacity: 0;}
    to {bottom: 50%; opacity: 1;}
    }

    @-webkit-keyframes fadeout {
    from {bottom: 50%; opacity: 1;} 
    to {bottom: 50%; opacity: 0;}
    }

    @keyframes fadeout {
    from {bottom: 50%; opacity: 1;}
    to {bottom: 50%; opacity: 0;}
    }

    table > tbody > tr > td {
        vertical-align: middle !important;
        font-weight: normal;
    }

    table > tbody > tr > th {
        vertical-align: middle !important;
        font-weight: normal;

    }
    .table {
        width: 100%;
        vertical-align: middle !important;
    }
    .table th, .table td {
        width: unset;
        vertical-align: middle !important;
    }

    .summary-table th, .summary-table td {
        border: 1px solid;
    }
    .summary-title {
        text-align: right;
        padding-right: 25px
    }
    .summary-content {
        text-align: left;
        padding-left: 25px
    }

    #indvtbl tr th,#indvtblnt tr th{
        border: 1px solid gray !important;
    }
</style>';

    $fullname = $this->worker_model->getEmployeeName($employeeid);
    $campus = $this->worker_model->getEemployeeCurrentData($employeeid, 'campusid');
    $employmentstat = $this->worker_model->getemployeestatus($this->worker_model->getEemployeeCurrentData($employeeid, 'employmentstat'));
    $department = $this->worker_model->getEmployeeDepartment($employeeid);

    $content = "<div >
    <table class=table table-bordered datatable id=indvtbl width=100%>
        <thead>
            <tr style='background-color: #162538;'>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:white;' >FULLNAME:</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:white;' colspan='4'>".$fullname."</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:white;' colspan='2'>DEPARTMENT:</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:white;' colspan='5'>".$department."</th>
                <!-- <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:white;' colspan='2' rowspan=2>&nbsp;</th> -->
            </tr>
            <tr style='background-color: #162538;'>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:white;'>STARTING DATE</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:white;' colspan='2'>OFFICIAL TIME</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:white;' colspan='2'>DATE FROM: ".$dfrom."</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:white;' colspan='2'>DATE TO: ".$dto."</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:white;' colspan='2'>EMPLOYEE STATUS</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:white;' colspan='3'>".$employmentstat."</th>
                
            </tr>

            <tr style='background-color: #a8a6a6;'>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>DATE</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black; width: 35px;'>IN</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>OUT</th>
                <!-- <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>DAY</th> -->
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>TIME IN</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>TIME OUT</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>TOTAL RENDERED</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>LATES</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>ABSENT</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>UNDERTIME</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>OVERTIME</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>DAY TYPE</th>
                <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>REMARKS/OTHERS</th>
                <!-- <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>LEAVE TYPE</th> -->
                <!-- <th style='padding: 5px;text-align: center;font-size: 12px;font-weight: bold;color:black;'>LEAVE HOURS</th> -->
            </tr>
        </thead>
        <tbody>";
                $off_lec_total = $off_lab_total = $off_admin_total = $off_overload_total = $twr_lec_total = $twr_lab_total = $twr_admin_total = $twr_overload_total = $teaching_overload_total = $ot_regular_total = $ot_restday_total = $ot_holiday_total = $lateut_lec_total = $lateut_lab_total = $lateut_admin_total = $lateut_overload_total = $absent_lec_total = $absent_lab_total = $absent_admin_total = $service_credit_total = $cto_total = $holiday_lec_total = $holiday_lab_total = $holiday_admin_total = $holiday_overload_total = $holiday_total = $twr_total = $undertime_total = $late_total = $absent_data_total = $overtime_total = $leave_hours_total =  0;
                $excess = array();
                // echo "<pre>"; print_r($attendance); die;
                foreach ($attendance as $date_arr => $att_date) {
                    $counter = 0;
                    $rowspan = 0;
                    $official_in = $official_out = $actlog_time_in = $actlog_time_out = "--:--";
                    $rendered = $late = $undertime = $absent = $overtime = $leave_hours =  0;
                    $day_type = $leave_type = "";
                    $date = date("d-M (l)",strtotime($date_arr));
                    $day = date("l", strtotime($date_arr));
                    foreach ($att_date as $key => $value) {
                        if($official_in == "--:--" || $counter == 1){
                            $official_in = ($value->off_time_in != "--" ? date('h:i A',strtotime($value->off_time_in)) : "--:--");
                        }
                        $official_out = ($value->off_time_out != "--" ? date('h:i A',strtotime($value->off_time_out)) : "--:--");

                        if($actlog_time_in == "--:--" || $counter == 1){
                            $actlog_time_in = ($value->actlog_time_in != "--" ? date('h:i A',strtotime($value->actlog_time_in)) : "--:--");
                        }
                        $actlog_time_out = ($value->actlog_time_out != "--" ? date('h:i A',strtotime($value->actlog_time_out)) : "--:--");

                        // if($counter == 0){
                            $getScheduleEmp = $this->extras->getEmpScheduleHistorySlim($employeeid, $value->date);
                            $day_type = $this->extras->getDayTypeEmployee($value->date, $employeeid, $getScheduleEmp);

                            $overtime = $this->attcompute->exp_time($value->ot_regular) + $this->attcompute->exp_time($value->ot_restday) + $this->attcompute->exp_time($value->ot_holiday);
                        // }

                        $rendered = $this->attcompute->exp_time($value->twr_lec) + $this->attcompute->exp_time($value->twr_lab) + $this->attcompute->exp_time($value->twr_admin) + $this->attcompute->exp_time($value->twr_overload);

                        if($value->lateut_remarks == "undertime"){
                            $undertime = $this->attcompute->exp_time($value->lateut_lec) + $this->attcompute->exp_time($value->lateut_lab) + $this->attcompute->exp_time($value->lateut_admin) + $this->attcompute->exp_time($value->lateut_overload);
                        }else if($value->lateut_remarks == "late"){
                            $late = $this->attcompute->exp_time($value->lateut_lec) + $this->attcompute->exp_time($value->lateut_lab) + $this->attcompute->exp_time($value->lateut_admin) + $this->attcompute->exp_time($value->lateut_overload);
                        }

                        $absent = $this->attcompute->exp_time($value->absent_lec) + $this->attcompute->exp_time($value->absent_lab) + $this->attcompute->exp_time($value->absent_admin);

                        

                        if($value->absent_lec || $value->absent_lab || $value->absent_admin){
                            $absent_data = $this->extras->getAbsentReason($value->date, $employeeid);
                            if(isset($absent_data['reason']) && $absent_data['reason'] != "none"){
                                $leave_type = $absent_data['reason'];
                                $leave_hours += $this->attcompute->exp_time($value->absent_lec) + $this->attcompute->exp_time($value->absent_lab) + $this->attcompute->exp_time($value->absent_admin);
                            }
                            
                        }

                        
                      

                        if($value->service_credit != "" && $value->service_credit != "--") $service_credit_total += $value->service_credit;
                        if($value->cto != "" && $value->cto != "--") $cto_credit_total +=  $this->attcompute->exp_time($value->cto);
                        
                        $counter++;
                        $rowspan = count($att_date);
                        
                        $content .="
                        <tr>";
                            if ($counter == 1) { 
                            $content .= "<td rowspan='".$rowspan."' style='padding: 2px;text-align: center;font-size: 10px; white-space: nowrap'>".$date."</td>";
                            }
                        $content .="
                            <td style='padding: 2px;text-align: center;font-size: 10px;'>".$official_in."</td>
                            <td style='padding: 2px;text-align: center;font-size: 10px;'>".$official_out."</td>
                            <!-- <td style='padding: 2px;text-align: center;font-size: 10px;'>".$day."</td> -->
                            <td style='padding: 2px;text-align: center;font-size: 10px;'>".$actlog_time_in."</td>
                            <td style='padding: 2px;text-align: center;font-size: 10px;'>".$actlog_time_out."</td>
                            <td style='padding: 2px;text-align: center;font-size: 10px;'>".($rendered ? $this->attcompute->sec_to_hm($rendered) : "0")."</td>
                            <td style='padding: 2px;text-align: center;font-size: 10px;'>".($late ? $this->attcompute->sec_to_hm($late) : "-")."</td>
                            <td style='padding: 2px;text-align: center;font-size: 10px;'>".($absent ? $this->attcompute->sec_to_hm($absent) : "-")."</td>
                            <td style='padding: 2px;text-align: center;font-size: 10px;'>".($undertime ? $this->attcompute->sec_to_hm($undertime) : "-")."</td>
                            <td style='padding: 2px;text-align: center;font-size: 10px;'>".($overtime ? $this->attcompute->sec_to_hm($overtime) : "-")."</td>
                            <td style='padding: 2px;text-align: center;font-size: 10px;'>".$day_type."</td>
                            <td style='padding: 2px;text-align: center;font-size: 10px;'>".$value->remarks."</td>
                            <!-- <td style='padding: 2px;text-align: center;font-size: 10px;'>".$leave_type."</td> -->
                            <!-- <td style='padding: 2px;text-align: center;font-size: 10px;'>".($leave_hours ? $this->attcompute->sec_to_hm($leave_hours) : "-")."</td> -->
                        </tr>";
                    }
                    $excess[$day_type] = isset($excess[$day_type]) ? $excess[$day_type] + 1 : 1;
                    
                    $twr_total += $rendered;
                    $absent_data_total += $absent;
                    $overtime_total += $overtime;
                    $leave_hours_total += $leave_hours;
                    $undertime_total += $undertime;
                    $late_total += $late;


                }
                
                $total_work = $twr_total;

$content .=" 
        </tbody>
    </table>
</div>
<br>
<div >
    <table border=1 width='80%' style='font-size: 9px; margin-left:15% ;page-break-inside: avoid;' >
        <thead>
            <tr style='background-color: #162538;'>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:white;' colspan='5'>COMPUTATION SUMMARY</th>
            </tr>
            <tr>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>Total Work Rendered</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>".($total_work ? $this->attcompute->sec_to_hm($total_work) : '')."</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;' colspan='2'>Total Work Rendered</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>Excess</th>
            </tr>
            <tr>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>Absences</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>".($absent_data_total ? $this->attcompute->sec_to_hm($absent_data_total) : '')."</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>Regular Schedule</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>(Day Type 1)</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>".(isset($excess[1]) ? $excess[1] : '')."</th>
            </tr>
            <tr>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>Lates</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>".($late_total ? $this->attcompute->sec_to_hm($late_total) : '')."</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>Day Off</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>(Day Type 2)</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>".(isset($excess[2]) ? $excess[2] : '')."</th>
            </tr>
            <tr>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>Undertime</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>".($undertime_total ? $this->attcompute->sec_to_hm($undertime_total) : '')."</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>Legal Holiday</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>(Day Type 3)</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>".(isset($excess[3]) ? $excess[3] : '')."</th>
            </tr>
            <tr>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>Overtime</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>".($overtime_total ? $this->attcompute->sec_to_hm($overtime_total) : '')."</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>Special Holiday</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>(Day Type 4)</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>".(isset($excess[4]) ? $excess[4] : '')."</th>
            </tr>
            <tr>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>Total Paid Leaves</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>".($leave_hours_total ? $this->attcompute->sec_to_hm($leave_hours_total) : '')."</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>Legal Holiday & Day-off</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>(Day Type 5)</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>".(isset($excess[5]) ? $excess[5] : '')."</th>
            </tr>
            <tr>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'></th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'></th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>Special Holiday & Day-off</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>(Day Type 6)</th>
                <th style='padding: 3px;text-align: center;font-size: 10px;font-weight: bold;color:black;'>".(isset($excess[6]) ? $excess[6] : '')."</th>
            </tr>
        </thead>
    </table>
    <br>
    <table style='width:100%;'>
        <tr>
            <td style='text-align: center' width=33%>".(1 ? 'Conforme:' : '')."</td>
            <td style='text-align: center' width=33%>".(1 ? 'Verified By:' : '')."</td>
            <td style='text-align: center' width=33%>".(1 ? 'Approved By:' : '')."</td>
        </tr>
        <tr>
            <td style='text-align: center' width=33%>".(1 ? '___________________________________' : '')."</td>
            <td style='text-align: center' width=33%>".(1 ? '___________________________________' : '')."</td>
            <td style='text-align: center' width=33%>".(1 ? '___________________________________' : '')."</td>
        </tr>
        <tr>
            <td style='text-align: center' width=33%>".(1 ? $this->extensions->getEmployeeName($employeeid) : '')."</td>
            <td style='text-align: center' width=33%>".(1 ? $verified_name : '')."</td>
            <td style='text-align: center' width=33%>".(1 ? $campus_director_name : '')."</td>
        </tr>
        <tr>
            <td style='text-align: center;'></td>
            <td style='text-align: center' width=33%>".(1 ? $verified_position : '')."</td>
            <td style='text-align: center' width=33%>".(1 ? $campus_director_position : '')."</td>
        </tr>
    </table>
</div>";

     $content.='
        </tr>
    </table>';
    
    echo $style.$content;
?>