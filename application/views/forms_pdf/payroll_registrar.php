<?php
 /**
  * @author Franco
  * @copyright 2024
  */

    $header = Globals::headerPdf($campusid,"GENERAL PAYROLL");
    $footer = '<htmlpagefooter name="Footer"> 
    <table>
                <tr>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;" width="20%">&emsp;</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;" width="25%">CERTIFIED: Services duly rendered as stated.</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;" width="10%">&emsp;</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;" width="25%">APPROVED FOR PAYMENT:</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;" width="20%">&emsp;</td>
                </tr>
                <tr>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;" colspan="5">&emsp;</td>
                </tr>
                <tr>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                    <td style="text-align:center; font-family: times new roman; font-size: 12px; border-bottom: 1px solid black">'.$cname.'</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                    <td style="text-align:center; font-family: times new roman; font-size: 12px; border-bottom: 1px solid black">'.$aname.'</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                </tr>
                <tr>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                    <td style="text-align:center; font-family: times new roman; font-size: 12px;">'.$cposition.'</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                    <td style="text-align:center; font-family: times new roman; font-size: 12px;">'.$aposition.'</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                </tr>
                <tr>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;" colspan="5">&emsp;</td>
                </tr>
                <tr>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">CERTIFIED: Supporting documents complete and proper fund available.</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">CERTIFIED: Each employee whose name appears above has been paid the amount indicated opposit on his/her name.</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                </tr>
                <tr>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;" colspan="5">&emsp;</td>
                </tr>
                <tr>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                    <td style="text-align:center; font-family: times new roman; font-size: 12px; border-bottom: 1px solid black"> '.$cname2.'</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                    <td style="text-align:center; font-family: times new roman; font-size: 12px; border-bottom: 1px solid black"> '.$aname2.'</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                </tr>
                <tr>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                    <td style="text-align:center; font-family: times new roman; font-size: 12px;"> '.$cposition2.'</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                    <td style="text-align:center; font-family: times new roman; font-size: 12px;">'.$aposition2.'</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                </tr>
            </table>
            <br>
    <table width="100%">
            <tr >
                <td width="50%" style="text-align: right; border-top: 1px solid"><strong>'. date("F d, Y") .' -</strong> Page  <strong>{PAGENO}</strong> of <strong>{nbpg}</strong></td>
            </tr>
    </table> </htmlpagefooter>';
    $totalAmount = $totalNetAmount = $totalDeduction = $totalNetAmountAfterTax = $count = 0;
    $dates = explode(' ', $payrollcutoff);
    list($from, $to) = $this->extras->getCutOffDTR($dates[0], $dates[1], $quarter);
?>

<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
    <style>
	    @page{            
	        margin-top: 3.33cm;
	        odd-header-name: html_Header;
	        odd-footer-name: html_Footer;
	    }  
	    body{
	    	font-family: Arial;
            font-size: 14px;
	    }
        .display-body .table {
            border-collapse: collapse;
            font-size: 11px;
            width: 100%;
        }
        .display-body .table th {
            border: 1px solid gray;
            background-color: #005;
            color: #FFF;
            padding: 5px;
        }
        .display-body .table td {
            text-align: center;
            border: 1px solid gray;
            padding: 5px;
        }
        .foot {
            font-size: 12px;
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
        <div class="display-body"> <br> <br> <br>
            <p class="center"><?=date('F Y', strtotime($dates[0]))?></p> <br>
            <table class="table">
                <thead>
                    <tr>
                        <th>NO.</th>
                        <th>NAME</th>
                        <th>TIN</th>
                        <th>SALARY/HR.</th>
                        <th>EARNED # FOR THE PERIOD HRS. WRK/LAB</th>
                        <th>TOTAL AMOUNT</th>
                        <th>DEDUCTION</th>
                        <th>NET AMOUNT</th>
                        <th>DEDUCTION WT 10%</th>
                        <th>NET AMOUNT AFTER TAX</th>
                        <th>SIGNATURE OF PAYEE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (is_array($emplist)) {
                        foreach ($emplist as $value) { $count++;
                                $ratePerHours = $this->extras->getRatePerHour($value->employeeid);
                                $ratePerHour = $ratePerHours['lec'] ? $ratePerHours['lec'] : $ratePerHours['lab'];
                                $renderedHours = $this->extras->getTotalRenderedHours($value->employeeid, $from, $to);
                                $renderedHour = $renderedHours['lec'] ? $renderedHours['lec'] : $renderedHours['lab'];
                                $renderedLate = $renderedHours['latelec'] ? $renderedHours['latelec'] : $renderedHours['latelab'];
                            if($estatid == "JOSPR"){
                                // URSHYP-2444
                                $amount = $value->salary;
                                $lateAmount = $value->tardy + $value->absent;
                                $netAmount = $value->gross;
                                $deduction = $value->withholdingtax;
                                $netAmountAfterTax = $value->net;
                            }else{
                                
                                $amount = ($ratePerHour/60) * $this->time->hoursToMinutes($renderedHour); 
                                $lateAmount = ($ratePerHour/60) * $this->time->hoursToMinutes($renderedLate); 
                                $netAmount = ($ratePerHour/60) * ($this->time->hoursToMinutes($renderedHour) + $this->time->hoursToMinutes($renderedLate));
                                $deduction = $amount * ($ratePerHours['tax']/100);
                                $netAmountAfterTax = $amount - $deduction;
                            }
                            
                            
                            ?>
                            <tr>
                                <td><?=$count?></td>
                                <td><?=$value->lname.', '.$value->fname.' '.$value->mname?></td>
                                <td><?=$value->emp_tin?></td>
                                <td><?=$ratePerHour?></td>
                                <td><?=$renderedHour?></td>
                                <td><?=number_format($amount, 2)?></td>
                                <td><?=number_format($lateAmount, 2)?></td>
                                <td><?=number_format($netAmount, 2)?></td>
                                <td><?=number_format($deduction, 2)?></td>
                                <td><?=number_format($netAmountAfterTax, 2)?></td>
                                <td></td>
                            </tr>
                            <?php $totalAmount += $amount; $totalNetAmount += $netAmount; $totalDeduction += $deduction; $totalNetAmountAfterTax += $netAmountAfterTax; } ?>
                        <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan=5>*** TOTAL ***</td>
                        <td><?=number_format($totalAmount, 2)?></td>
                        <td>-</td>
                        <td><?=number_format($totalNetAmount, 2)?></td>
                        <td><?=number_format($totalDeduction, 2)?></td>
                        <td><?=number_format($totalNetAmountAfterTax, 2)?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table><br><br>
            
        </div>
        
        <div class="display-footer">
            <?=$footer?>
        </div>
    </div>
</body>
</html> -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Payroll</title>
    <style>
        @page {
            margin-top: 3.33cm;
            odd-header-name: html_Header;
            odd-footer-name: html_Footer;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .display-body .table {
            border-collapse: collapse;
            font-size: 12px;
            width: 100%;
            table-layout: fixed;
        }
        .display-body .table th {
            border: 1px solid #333;
            background-color: #001F3F; /* Dark blue background for the header */
            color: #FFFFFF;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }
        .display-body .table td {
            text-align: center;
            border: 1px solid #888;
            padding: 8px;
        }
        .display-body .table tfoot td {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .center {
            text-align: center;
        }
        .foot {
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="display">
        <div class="display-header">
            <?=$header?>
        </div>
        <div class="display-body">
            <br><br><br>
            <p class="center"><?=date('F Y', strtotime($dates[0]))?></p>
            <br>
            <table class="table">
                <thead>
                    <tr>
                        <th rowspan="2">No.</th>
                        <th rowspan="2">Employee</th>
                        <th rowspan="2">Position</th>
                        <th colspan="2">Monthly</th>
                        <th rowspan="2">Earned Salary</th>
                        <th colspan="7">Less: Deductions</th>
                        <th rowspan="2">Total Deduct</th>
                        <th rowspan="2">Netpay</th>
                        <th colspan="2">Pay Period</th>
                        <th rowspan="2">Signature</th>
                    </tr>
                    <tr>
                        <th>Salary</th>
                        <th>PERA</th>
                        <th>WTAX</th>
                        <th>GSIS</th>
                        <th>PhilHealth</th>
                        <th>Pag-IBIG</th>
                        <th>Lates</th>
                        <th>Undertime</th>
                        <th>Absent</th>
                        <th>01-15</th>
                        <th>16-31</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (is_array($emplist)) {
                        foreach ($emplist as $value) {
                            $count++;
                            $ratePerHours = $this->extras->getRatePerHour($value->employeeid);
                            $monthlySalary = number_format($ratePerHours['monthly'], 2);
                            $pera = number_format($ratePerHours['pera'], 2);
                            $earnedSalary = number_format($ratePerHours['earned'], 2);
                            $wTax = number_format($ratePerHours['wtax'], 2);
                            $gsis = number_format($ratePerHours['gsis'], 2);
                            $philHealth = number_format($ratePerHours['philhealth'], 2);
                            $pagIbig = number_format($ratePerHours['pagibig'], 2);
                            $lates = number_format($ratePerHours['lates'], 2);
                            $undertime = number_format($ratePerHours['undertime'], 2);
                            $absent = number_format($ratePerHours['absent'], 2);
                            $totalDeduct = $ratePerHours['totalDeduct'];
                            $netPay = $ratePerHours['netPay'];
                            $pay01to15 = $ratePerHours['pay01to15'];
                            $pay16to31 = $ratePerHours['pay16to31'];
                            ?>
                            <tr>
                                <td><?=$count?></td>
                                <td><?=$value->lname.', '.$value->fname.' '.$value->mname?></td>
                                <td><?=$value->position?></td>
                                <td><?=$monthlySalary?></td>
                                <td><?=$pera?></td>
                                <td><?=$earnedSalary?></td>
                                <td><?=$wTax?></td>
                                <td><?=$gsis?></td>
                                <td><?=$philHealth?></td>
                                <td><?=$pagIbig?></td>
                                <td><?=$lates?></td>
                                <td><?=$undertime?></td>
                                <td><?=$absent?></td>
                                <td><?=number_format($totalDeduct, 2)?></td>
                                <td><?=number_format($netPay, 2)?></td>
                                <td><?=number_format($pay01to15, 2)?></td>
                                <td><?=number_format($pay16to31, 2)?></td>
                                <td></td>
                            </tr>
                            <?php
                        }
                    } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="13" style="text-align: right;">*** TOTAL ***</td>
                        <td></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
            <br><br>
        </div>
        <div class="display-footer">
            <?=$footer?>
        </div>
    </div>
</body>
</html>
