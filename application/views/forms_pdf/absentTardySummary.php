<?php
 /**
  * @author Franco
  * @copyright 2024
  */

    $header = Globals::headerPdf($campusid,"SUMMARY OF ABSENCES AND TARDINESS: $date");
    $footer = '<htmlpagefooter name="Footer"> 
    <table width="100%">
            <tr >
                <td width="50%" style="text-align: right; border-top: 1px solid"><strong>'. date("F d, Y") .' -</strong> Page  <strong>{PAGENO}</strong> of <strong>{nbpg}</strong></td>
            </tr>
    </table> </htmlpagefooter>';
    $count = 0;
    // echo"<pre>";print_r($datas);die;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
    <style>
	    @page{            
	        margin-top: 5.5cm;
            margin-bottom: 1.5cm;
	        odd-header-name: html_Header;
	        odd-footer-name: html_Footer;
	    }  
	    body{
	    	font-family: Arial;
            font-size: 12px;
	    }
        .display-body .table {
            border-collapse: collapse;
            font-size: 11px;
            width: 100%;
        }
        .display-body .table th {
            background-color: #005;
            color: #FFF;
            border: 1px solid gray;
            padding: 5px;
        }
        .display-body .table td {
            text-align: center;
            border: 1px solid gray;
            padding: 5px;
        }
        .center {
            text-align: center;
        }
	</style>
<body>
    <div class="display">
        <div class="display-header">
            <?=$header?>
        </div>
        <div class="display-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th colspan="2"></th>
                        <th colspan="4">ABSENCES</th>
                        <th colspan="6">TARDINESS</th>
                    </tr>
                    <tr>
                        <th colspan="2" rowspan="2" width="25%">NAME</th>
                        <th rowspan="2" width="8%">NO. OF DAYS OF ABSENCES</th>
                        <th rowspan="2" width="9%">CAUSE</th>
                        <th rowspan="2" width="8%">INCLUSIVE DATES</th>
                        <th rowspan="2" width="8%">WITH OR W/OUT PAY</th>
                        <th rowspan="2" width="8%">NO. OF TIMES OF TARDY</th>
                        <th rowspan="2" width="8%">INCLUSIVE DATES</th>
                        <th rowspan="2" width="8%">WITH OR W/OUT PAY</th>
                        <th colspan="3" width="18%">NUMBER OF</th>
                    </tr>
                    <tr>
                        <th>DAYS</th>
                        <th>HRS</th>
                        <th>MINS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datas as $value) { 
                            $absentDate = $absentPay = $lateDate = $latePay = $date = ""; 
                            $totalAbsent = $totalLate = $lateHours = $lateMinutes = 0;
                            $value = $value[0]; $count++; 
                            $dates = explode(',', $value->date);
                            $absents =  explode(',', $value->absent);
                            $lates =  explode(',', $value->late);
                            $leaves = explode(',', $value->leave);
                            for ($i=0; $i<count($dates); $i++) { 
                                if ($dates[$i] && strpos($date, $dates[$i]) === false) {
                                    $date .= $date ? ', ' : '';
                                    $date .= $dates[$i];

                                    if ($absents[$i]) {
                                        $absentDate .= $absentDate ? ', ' : '';
                                        $absentDate .= $dates[$i];

                                        $absentPay .= $absentPay ? ', ' : '';
                                        $absentPay .= $leaves[$i] ? 'W/ Pay' : 'W/O Pay';

                                        $totalAbsent++;
                                    }

                                    if ($lates[$i]) {
                                        $lateDate .= $lateDate ? ', ' : '';
                                        $lateDate .= $dates[$i];

                                        $latePay .= $latePay ? ', ' : '';
                                        $latePay .= $leaves[$i] ? '' : '';
                                        
                                        $totalLate++;
                                    }
                                }
                                if ($lates[$i]) {
                                    $late = explode(':', $lates[$i]);
                                    $lateHours += $late[0];
                                    $lateMinutes += $late[1];
                                }
                            } ?>
                            <tr>
                                <td><?=$count?></td>
                                <td><?=$value->fullname?></td>
                                <td><?=$totalAbsent?></td>
                                <td></td>
                                <td><?=$absentDate?></td>
                                <td><?=$absentPay?></td>
                                <td><?=$totalLate?></td>
                                <td><?=$lateDate?></td>
                                <td><?=$latePay?></td>
                                <td>0</td>
                                <td><?=$lateHours?></td>
                                <td><?=$lateMinutes?></td>
                            </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="display-footer">
            <?=$footer?>
        </div>
    </div>
</body>
</html>