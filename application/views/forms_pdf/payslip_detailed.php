<?php
require_once(APPPATH."config/constants.php");
$CI =& get_instance();
$CI->load->library('PdfCreator_mpdf');
$CI->load->model('Payroll_model', 'payroll_model');
$CI->load->model('Time', 'time');
require_once  APPPATH . 'libraries/mpdf/vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A3', 'tempDir' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'mpdf', 'margin_top' => '5', 'margin_bottom' => '5', 'margin_right' => '5', 'margin_left' => '5']);
$mpdf->simpleTables=true;
// $mpdf->packTableData=true;
$campusDisplay = $CI->payroll_model->checkCompanyCampus($campusid);   
$campusEmail = $CI->payroll_model->checkCampusEmail($campusid);
$SIZE = false;    

$style = "
    <style type='text/css'>
       .content-left{
            width: 100%;
            height: 30%;
        }
        .space{
            width: 100%;
            height: 1.5%;
        }
        .contenttext{
            margin-left: 2%;
            width: 95%;
            height: 24.5%;
            // border-right: 1px solid black;
            // border-style: dashed;            
            text-align: justify;
            text-justify: inter-word;
           /* font-size: 10px;*/

        }
        .fixed{
            table-layout:fixed; 
        }
        body{
            font-family: verdana;
            font-size: 13px;
        }
        hr{
            margin:0px;
        }
        hr{
            margin:0px;
            padding:0px;
        }
        td{
            margin:0;
            padding:0.5px;
        }
        .fixedcontainer{
            border:0px solid blue;
            height:405px;
            width:100%;
            position:absolute;
            left:35px;
            top:35px;

        }
        .fixedcontainer2{
            border:0px solid blue;
            height:440px;
            width:100%;
            position:absolute;
            left:35px;
            top:530px;

        }
        .spaceBetween{
            border:0px solid green;
            height:90px;
            width:100%;
            position:absolute;
            left:35px;
            top:440px;


        }
        .slipcontainer{
            border:1px solid black;
            display: inline-block;
            margin-top:10px;
            margin-left:25px;
            margin-right:25px;
            margin-bottom:10px;
            width:94%;
        }
        .containerleft{
            padding-top: 3px;
            border-right:1px solid black;
            width:50%;
            float:left;

        }
        .containerright{
            margin-top: 0px;
            width:auto;
            float:right;
        }
        .footer{
            border:1px solid blue;
        }
        .footerleft{
            border-top:1px solid black;
            border-right:1px solid black;
            width:50%;
            float:left;

        }
        .footerright{
            position:absolute;
            margin-top: 0px;
            border-top:1px solid black;
            width:auto;
            float:right;

        }
        .container{
            /*border: 1px solid black;*/
            margin-top: 5px;
            margin-left; 3%;
            width: 72%;
            height: 33%;
            float: left;
        }
        .header{
            text-align: center;
        }
        table{
            width: 100%;
        }
        td { 
            padding: 2px;
        }
        .tableheader{
            /*font-size: 15px;*/
            /*font-size: 8px;*/
            border-bottom-width: 1px;
            border-bottom-style: solid;
            border-bottom-color: #CDC1A7;
            border-top-width: 1px;
            border-top-style: solid;
            border-top-color: #CDC1A7;
            text-align: left;
        }
        .earnings{
            float: left;
            /*border: 1px solid black;*/
            width: 46.6%;
            height: 16%;
            text-align: right;
        }
        .deduction{
            float: left;
            /*border: 1px solid black;*/
            width: 52.6%;
            height: 16%;
            text-align: right;
        }
        .footer{
            margin-left: 1%;
            width: 99%;
            height: 5%;   
            /*border: 1px solid black;*/
        }
        .footer .text{
            margin-top: 2%;
            font-weight: bold;
        }
        .edtbl{
            width: 100%;
        }
        .eddesc{
            text-align: left;
        }
        .edamt{

            text-align: right;
        }
        .floatright{
        	text-align:right;float:right;
        }
        #sys-gen{
            text-align:right;
            display: inline-block;
            margin-top:10px;
            margin-left:25px;
            margin-right:25px;
            margin-bottom:10px;
            width:94%;
        }
    </style>
</head>
<body>";


$mpdf->WriteHTML($style);

$html = "";
$cutoffdate = date('F d',strtotime($sdate)).' -  '.date('F d, Y',strtotime($edate));
$payrollGroup = (count($emplist) > 1) ? "ALL EMPLOYEE" : "INDIVIDUAL" ;

function getEmpDesc($eid){
    $return = "";
    $query = mysql_query("SELECT b.description FROM employee a INNER JOIN code_office b ON a.deptid = b.code WHERE a.employeeid='$eid'");
    $data = mysql_fetch_array($query);
    $return = $data['description'];
    return $return; 
}

$slipcount = count($emplist);
$counter = 0;
foreach ($emplist as $empid => $empinfo) {
    $perdept_salary = $CI->payroll_model->getPerdeptSalaryHistory($empid,$sdate);

    $t_holiday = 0;
    $days_absent = $totallate = $totalundertime = 0;
    $tnt = $CI->payroll_model->getEmployeeTeachingType($empid);
    if($tnt=="nonteaching" && !$CI->payroll_model->isTeachingRelated($empid)){
        list($tardy, $absent) = $CI->payroll_model->employeeAbsentTardy($empid, $sdate, $edate);
        if($tardy) $empinfo["teaching_tardy"] = $tardy;
        if($absent) $empinfo["workhours_absent"] = $absent;
    }

    $workhours_deduc = $workhours_late = 0;
    if($empinfo["perdept_amt"]){
        foreach($empinfo["perdept_amt"] as $aimsdept => $perdept){
            foreach($perdept as $type => $row){
                $workhours_deduc += $CI->time->exp_time($row["deduc_hours"]);
                $workhours_late += $CI->time->exp_time($row["late_hours"]);
            }
        }
    }

    if($tnt=="teaching"){
        $empinfo["workhours_absent"] = $CI->time->sec_to_hm($workhours_deduc);
        $empinfo["teaching_tardy"] = $CI->time->sec_to_hm($workhours_late);
    }

	$counter ++;
    $otTotal = 0;

    $tag = "";
    if($tnt=="nonteaching") $tag = "Office";
    else $tag = "Department";

    foreach($empinfo["overtime_detailed"] as $ot_type => $ot_row){
        foreach($ot_row as $ot_hol => $ot_det){
            $ot_hours = $CI->time->sec_to_hm($ot_det["ot_hours"]);
            $otTotal = $otTotal+$ot_det["ot_hours"];
        }
    }

    list($cutoffstart, $cutoffend) = $CI->payroll_model->getDTRCutoffConfigPayslip($dfrom, $dto);
    if($CI->payroll_model->getEmployeeTeachingType($empid) == "teaching"){
          $isBED = false;
          $bed_depts = $CI->payroll_model->getBEDDepartments();
          $deptid = $CI->payroll_model->getEmployeeDeparment($empid);
          if(in_array($deptid, $bed_depts)) $isBED = true;
          $data = $CI->payroll_model->computeEmployeeAttendanceSummaryTeaching($cutoffstart,$cutoffend,$empid,"", $isBED);
          // echo "<pre>"; print_r($data); die;
            
          if($data[4]){
            $absent_list = explode("/", $data[4]);
            foreach($absent_list as $abs_d){
                list($day, $c_abs) = explode(" ", $abs_d);
                $days_absent+=$c_abs;
            }
          }

          if(isset($data[19]) && $data[19]){
              foreach($data[19] as $date => $date_list){
                  $tworkhours = 0;
                 

                  if(isset($data[19]['workhours_perday'][$date])){
                    foreach ($data[19]['workhours_perday'][$date] as $leclab => $leclabVal) {
                      $tworkhours += $leclabVal['work_hours'];
                      $totallate += $leclabVal['late_hours'];
                    }
                  }
                  // if(isset($date_list["absent"]) && $date_list["absent"] >= $tworkhours) $days_absent += 1;
              }
              $totallate = $CI->time->sec_to_hm($totallate);
          }
      }else{
          $data = $CI->payroll_model->computeEmployeeAttendanceSummaryNonTeaching($cutoffstart,$cutoffend,$empid);
          $t_holiday = isset($data[16]) ? $data[16] : 0;
          if(isset($data[22]) && $data[22]){
              foreach($data[22] as $date => $date_list){
                  $tworkhours = $CI->payroll_model->totalWorkhoursPerday($empid, $date);
                  $tworkhours = $CI->time->exp_time($tworkhours);
                  if(isset($date_list['absent']) && $date_list["absent"] > $tworkhours) $days_absent += 1;
                  if(isset($date_list["late"])) $totallate += (int) $date_list["late"];
                  if(isset($date_list["undertime"])) $totalundertime += (int) $date_list["undertime"];
              }
          }
          $totallate = $CI->time->sec_to_hm($totallate);
          $totalundertime = $CI->time->sec_to_hm($totalundertime);
      }
$deptDesc = $CI->payroll_model->getDeptDesc($empinfo['deptid']);
$officeDesc = $CI->payroll_model->getOfficeDesc($empinfo['office']);
$dept_off = $deptDesc."/".$officeDesc;


if(!$deptDesc || !$officeDesc) $dept_off = str_replace("/", "", $dept_off);
$fullnameLength = strlen($empinfo["fullname"]);
    ($fullnameLength > 26) ? $fullname = "<span style='font-size:8px;'>".$empinfo["fullname"]."</span>" : $fullname = $empinfo["fullname"] ; 
$html .= '
<div class="fixedcontainer"></div>
<div class="fixedcontainer2"></div>
<div class="spaceBetween"></div>';

$html .= '
    <table  border="1"style="display: inline-block;margin-top:10px;margin-left:25px;margin-right:25px;margin-bottom:10px;width:98.6%;font-size:120%;">
        <tr>
            <td style="width:33%;">
                <span>'.date("m/d/Y",time()).'</span>
            </td>
            <td style="width:33%;">
                <center>Employee Payslip '.$CI->payroll_model->getCampusDescription($campusid).'</center>
            </td>
            <td></td>
        </tr>
    </table>';

// HEADER
    $html .= "
    <table width='100%'>
		<tr>
			<td rowspan='5' width='".($SIZE ? "25%":"35%")."' style='text-align: right;'><img src='images/school_logo.png' style='width: 45px;text-align: center;' /></td>
			<td colspan='1' style='text-align: center;font-size: 10px;'>Republic of the Philippines</td>
			<td rowspan='5' style='width='".($SIZE ? "25%":"35%")."''><img src='images/ursiso.jpg' style='width: 120px;text-align: center;' /></td>
		</tr>
		<tr>
			<td id='title-pdf' valign='middle' style='padding: 0;text-align: center;color:black;' width='".($SIZE ? "50%":"30%")."'><span style='font-size: 18px; font-weight: normal;'>UNIVERSITY OF RIZAL SYSTEM</span></td>
		</tr>
		<tr>
			<td  valign='middle' style='padding: 0;text-align: center;color:black;'><span style='font-size: 10px; font-family: Arial, Helvetica, sans-serif;' width='".($SIZE ? "50%":"30%")."'>Province of Rizal</span></td>
		</tr>
		<tr>
			<td  valign='middle' style='padding: 0;text-align: center;color:black;'><span style='font-size: 10px; font-family: Arial, Helvetica, sans-serif;' width='".($SIZE ? "50%":"30%")."'>www.urs.edu.ph</span></td>
		</tr>
		<tr>
			<td valign='middle' style='padding: 2px;font-size: 10px;text-align: center; margin-left:100px;'></td>
		</tr>
		<tr>
			<td colspan='3' valign='middle' style='padding: 0;font-size: 10px;text-align: center; margin-left:100px;'>Email Address : ursmain@urs.edu.ph / urs.opmorong@gmail.com</td>
		</tr>
		<tr>
			<td colspan='3' valign='middle' style='padding: 0;font-size: 10px;text-align: center; margin-left:100px;'>Main Campus : URS Tanay Tel. (02) 8401-4900; 8401-4910; 8401-4911; 8539-9957 to 58</td>
		</tr>

		</table>
		<table width='100%' >
		<tr>
			<td valign='middle' style='padding: 0;text-align: center; margin-left:100px;border:1px solid black;'></td>
		</tr>
		</table>
		<table width='100%' >
		<tr>
			<td valign='middle' style='padding: 0;text-align: center; margin-left:100px;font-weight:bold;color:#0070C0;'><i>Office of the Campus HRMO - ".$campusDisplay."</i></td>
		</tr>
		<tr>
			<td valign='middle' style='padding: 0;text-align: center; margin-left:100px;font-weight:bold;color:#0070C0;'>Tel. No. (02) 8542-1095 loc. 203 Email Address : ".$campusEmail."</td>
		</tr>
		<tr>
			<td valign='middle' style='padding: 0;text-align: center; margin-left:100px;font-weight:bold;'>P A Y S L I P</td>
		</tr>
        <tr>
			<td valign='middle' style='padding: 0;text-align: center; margin-left:100px;font-weight:bold;'>STRICLY CONFIDENTIAL</td>
		</tr>
	</table>   
    ";

      $html .='
<div id="sys-gen">
    <i>System Generated</i>
</div>
<div class="slipcontainer">
	<div class="containerleft">
        <div class="contenttext">
        <table style="border-collapse:collapse;width:100%";>
            <tr>
                <td>Basic Pay :</td>
                <td style="text-align:right">'.number_format($empinfo["salary"],2).'</td>
            </tr>
            <tr>
                <td>Teaching Pay :</td>
                <td style="text-align:right">'.number_format($empinfo["teaching_pay"],2).'</td>
            </tr>
            <tr>
                <td>PERA</td>
                <td style="text-align:right">'.number_format($empinfo["pera"],2).'</td>
            </tr>
             <tr>
                <td>Overtime(s): &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.$CI->time->sec_to_hm($otTotal).'  </td>
                <!--<td>Overtime(s): &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 0:00 X 0.00 (Rate/hour)</td>-->
                <td style="text-align:right">'.number_format($empinfo["overtime"],2).'</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:center">---------------------------------------- Overtime Breakdown ----------------------------------------</td>
            </tr>
            ';
            foreach($empinfo["overtime_detailed"] as $ot_type => $ot_row){
                foreach($ot_row as $ot_hol => $ot_det){
                    $ot_hours = $CI->time->sec_to_hm($ot_det["ot_hours"]);
                    if($ot_hol != "NONE") $ot_hol = $ot_hol." ". "Holiday";
                    else $ot_hol = "";
                    $html.= '
                        <tr>
                            <td style="padding-left: 50px;">'.ucwords(strtolower(str_replace("_", " ", $ot_type))).': ' .$ot_hol. '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.$ot_hours.'</td>
                            <td style="text-align:right; padding-right: 50px;">'.number_format($ot_det["ot_amount"], 2).'</td>
                        </tr>
                    ';
                }
            }
            $html.='
            <!-- <tr>
                <td style="padding-left: 50px;">Night Differential: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 0:00 X 0.00 (Rate/hour)</td>
                <td style="text-align:right;  padding-right: 50px;">'."0.00".'</td>
            </tr> -->
            <tr>
                <td>Other Taxable Income :</td>
                <td style="text-align:right">'.number_format($empinfo["totalIncomeTaxable"],2).'</td>
            </tr>
            <tr>
                <td>Other Non-Taxable Income :</td>
                <td style="text-align:right">'.number_format($empinfo["totalIncomeNonTaxable"],2).'</td>
            </tr>
            
            <tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td>LESS : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Total :</td>
                <td style="text-align:right">'.number_format($empinfo["semitotalPay"],2).'</td>
            </tr>
            <tr><td>&nbsp;</td></tr>
             <tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td>Absenteeism:</td>
                <td style="text-align:right">'.($days_absent > 1? $days_absent.' Days' : $days_absent.' Day').'</td>
            </tr>
             <tr>
                <td>Tardiness(hr:mm): </td>
                <td style="text-align:right">'.$totallate.'</td>
            </tr>
             <tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td>Absent:  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.$empinfo["workhours_absent"].' (hr:min) &nbsp;&nbsp;</td>
                <td style="text-align:right">'.number_format($empinfo["absents"],2).'</td>
            </tr>
            <tr>
                <td>Late / UT: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.$empinfo["teaching_tardy"].' (hr:min) &nbsp;&nbsp;</td>
                <td style="text-align:right">'.number_format($empinfo["tardy"],2).'</td>
            </tr>
            <tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td class="floatright">  GROSS PAY:  </td>
                <td style="text-align:right">'.number_format($empinfo["grosspay"],2).'</td>
            </tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>';

    $contributions = array("PAGIBIG","PHILHEALTH","SSS", "GSIS");
                for($i=0;$i < count($contributions);$i++){
                    if(array_key_exists($contributions[$i],$empinfo["fixeddeduc"])){
                        if($contributions[$i] == "PAGIBIG"){
                            $html .= "<tr>
                            <td> <p>Less Contributions: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ".$contributions[$i].":</p></td>
                            <td class='edamt'>".number_format(floatval($empinfo["fixeddeduc"][$contributions[$i]]),2)."</td>
                        </tr>                        
                       ";
                        }else{
                            $html .= "<tr>
                            <td class='eddesc'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$contributions[$i].":</td>
                            <td class='edamt'>".number_format(floatval($empinfo["fixeddeduc"][$contributions[$i]]),2)."</td>
                        </tr>    ";
                        }

                    }else{
                        if($contributions[$i] == "PAGIBIG"){
                            $html .= "<tr>
                                    <td> <p>Less Contributions: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ".$contributions[$i].":</p></td>
                                    <td class='edamt'>0.00</td>
                                </tr>  
                                ";
                            }else{
                                $html .= "<tr>
                                    <td class='eddesc'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$contributions[$i].":</td>
                                    <td class='edamt'>0.00</td>
                                    </tr>   
                                ";
                            }
                    }

                }     

$html .=    '
            <tr>
                 <td><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Provident Premium:
                 </p>
                </td>
                <td class = "edamt">'.number_format($empinfo["provident_premium"] ,2).'</td>
            </tr>
            <tr>
                 <td><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Union Dues: 
                 </p>
                 </td>
                 <td style="text-align:right">0.00</td>
            </tr>

            <tr>
                 <td><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Health Insurance:
                 </p>
                 </td>
                 <td style="text-align:right">0.00</td>
            </tr>

            <tr>
                 <td><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Tax Withheld:
                 </p>
                 </td>
                 <td style="text-align:right">'.number_format($empinfo["whtax"],2).'</td>
            </tr>
            <tr>
                 <td><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Other Deduction:
                 </p>
                </td>
                <td class = "edamt">'.number_format($empinfo["totalOtherDeduc"] ,2).'</td>
            </tr>

        </table>
        </div>
    </div>
    <div class="containerright">
    	<div class="contenttext">
        	<table style="width:100%"; >
        	 <tr height="100px;">
                <td ><b>Other Non-Txbl Income</b></td>
            </tr>';
foreach ($empinfo["income"] as $incomeKey => $incomeVal) { 
			if($income_config[$incomeKey]['description'] == "notax"){
		            $html .= '<tr>
					            <td class="eddesc">'.$income_config_desc[$incomeKey]["description"].'</td>
					            <td class="edamt">'.number_format($incomeVal,2).'</td>
		            		</tr>';
		            	}  
            }               
$html .= '<tr>
            <br><br><br>
                <br><br>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td ><b>Other Taxable Income</b></td>
            </tr>';
foreach ($empinfo["income"] as $incomeKey => $incomeVal) 
			{ 
				if($income_config[$incomeKey]['description'] == "withtax")
				{
	            $html .= '<tr>
				            <td class="eddesc">'.$income_config_desc[$incomeKey]["description"].'</td>
				            <td class="edamt">'.number_format($incomeVal,2).'</td>
	            		</tr>';
			    }  
            }   

$html .= '<tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td ><b>Other Deduction</b></td>
            </tr>';
foreach ($empinfo["deduction"] as $deducKey => $deducVal) 
			{ 
			if($deduction_config[$deducKey]['description'] == "sub")
				{
	            $html .= '<tr>
				            <td class="eddesc">'.$deduction_config_desc[$deducKey]["description"].'</td>
				            <td class="edamt">'.number_format($deducVal,2).'</td>
	            		</tr>';
	            }else{
	            	$html .= '<tr>
					            <td class="eddesc">'.$deduction_config_desc[$deducKey]["description"].'</td>
					            <td class="edamt">('.number_format($deducVal,2).')</td>
		            		</tr>';
	            }
            }
foreach ($empinfo["loan"] as $loanKey => $loanVal) 
            { 
            
                $html .= '<tr>
                            <td class="eddesc">'.$loan_config[$loanKey]["description"].'</td>
                            <td class="edamt">'.number_format($loanVal,2).'</td>
                        </tr>';
            
            }              

$html .='<tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td class = "eddesc"> Total: </td>
                <td class = "edamt">'.number_format($empinfo["totalOtherDeduc"] ,2).'</td>

            </tr>

        	</table>
        	<br>';
    if($tnt=="teaching" || $CI->payroll_model->isTeachingRelated($empid)){
        $html .='<tr>
                	<table style="width:100%"; >
                    <tr>
                        <td colspan="4"><b>Other Teacher'."'".'s Load</b></td>
                    </tr>
                    <tr>
                        <td><b>Classification</b></td>
                        <td><b>Hr</b></td>
                        <td><b>Rate/HR</b></td>
                        <td><b>LEC</b></td>
                    </tr>
                    ';
                    if(sizeof($empinfo['perdept_amt']) > 0){
                        foreach ($empinfo['perdept_amt'] as $aimsdept => $d_list) {
                            $lec_work_hours = $lec_work_amount = $lab_work_hours = $lab_work_amount = 0;
                            foreach($d_list as $type => $leclab){
                                if($type == "LEC"){
                                    $lec_work_hours = $d_list["LEC"]["work_hours"];
                                    $lec_work_amount = $d_list["LEC"]["work_amount"];
                                }else{
                                    $lab_work_hours = $d_list["LAB"]["work_hours"];
                                    $lab_work_amount = $d_list["LAB"]["work_amount"];
                                }
                            }

                            $html .= '

                                <tr>
                                    <td>'.$CI->payroll_model->getCourseDescriptionByCode($aimsdept).'</td>
                                    <td>'.(number_format($lec_work_hours+$lab_work_hours, 2)).'</td>
                                    <td>LEC - '.$perdept_salary[$aimsdept]["lechour"].'</td>
                                    <td>'.number_format($lec_work_amount,2).'</td>
                                </tr>


                            ';

                            $empinfo["total_leclab_pay"] += ($lec_work_amount);
                        }

                    }else{
                        $html .= '
                            <tr>
                                <td>LEC PAY</td>
                                <td></td>
                                <td>0</td>
                                <td class="edamt">0.00</td>
                            </tr>

                            <tr>
                                <td>LAB PAY</td>
                                <td></td>
                                <td>0</td>
                                <td class="edamt">0.00</td>
                            </tr>
                        ';
                    }




        $html .='   <tr>
                        <td colspan="3"><hr width="100%" height="1px"></td>
                        <td><hr width="100%" height="1px"></td>
                    </tr>
                    <tr>
                        <td colspan="3">Total:   </td>
                        <td class="edamt">'.number_format($empinfo["total_leclab_pay"],2).'</td>
                    </tr>
                    </table>';
    }

$html .='
        </div>
    </div>

    <div class="footerleft">
        <table>
            <tr>
                <td colspan="2" style="text-align:right;"><b>NET PAY:&nbsp;&nbsp;&nbsp; </b>'.number_format($empinfo["netpay"],2).'</td>
            </tr>
        </table>
    </div>
    <div class="footerright">
        <table>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td><b>Certificate by: </b></td>
                <td><b>Position: </b></td>
            </tr>
        </table>
    </div>
</div>
<table style=" border:1px solid black;display: inline-block;margin-top:10px;margin-left:25px;margin-right:25px;margin-bottom:10px;width:98.6%;">
    <tr>
        <td style="padding:0px;border:1px solid black;">
            <table>
                <tr>
                <td style="width:200px;padding:0px;"><b>ID NO</b></td>
                <td style="width:200px;padding:0px;"><b>Name</b></td>
                <td style="width:200px;padding:0px;"><b>'.$tag.'</b></td>
                <td style="width:200px;padding:0px;"><b>Bank Name</b></td>
                <td style="width:200px;padding:0px;"><b>Pay Date</b></td>
                <td style="width:200px;padding:0px;"><b>Cut-Off Date</b></td>
                
                </tr>
                <tr>
                <td style="padding:0px;">'.$empid.'</td>
                <td style="padding:0px;">'.$fullname.'</td>
                <td style="padding:0px;">'.$dept_off.'</td>
                <td style="padding:0px;">'.$CI->payroll_model->getBankName($empinfo['bank']).'</td>
                <td style="padding:0px;">'.$cutoffdate.'</td>
                <td style="padding:0px;">'. $CI->payroll_model->getDTRCutoffConfig($sdate, $edate) .'</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
	';
	if($counter == 2){
        $html .= "<pagebreak></pagebreak>";    
        $counter = 0;    
    }
} /// end main loop
// die;
// echo $html."</body>";
$mpdf->WriteHTML($html);
$mpdf->WriteHTML("</body>");

$mpdf->Output($path, 'F');

/*ibabalik din po ito agad pakiremind si max thank you*/

/*<tr>
    <td>Paid Holiday: &nbsp;&nbsp;&nbsp; '.$t_holiday.'.00 (Days) X '.number_format(($t_holiday * $empinfo["daily"]),2).' (Rate/Day) </td>
    <td style="text-align:right">'."0.00".'</td>
</tr>*/