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
                    <td style="text-align:center; font-family: times new roman; font-size: 12px; border-bottom: 1px solid black"> '.$cname3.'</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                </tr>
                <tr>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                    <td style="text-align:center; font-family: times new roman; font-size: 12px;"> '.$cposition2.'</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                    <td style="text-align:center; font-family: times new roman; font-size: 12px;">'.$cposition3.'</td>
                    <td style="text-align:left; font-family: times new roman; font-size: 12px;">&emsp;</td>
                </tr>
            </table> </htmlpagefooter>';
    $totalAmount = $totalDeduction = $totalNetAmount = $totalTaxDeduction = $totalNetAmountAfterTax = $count = 0;
    $dates = explode(' ', $payrollcutoff);
    list($from, $to) = $this->extras->getCutOffDTR($dates[0], $dates[1], $quarter);
    // die($this->db->last_query());
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
            <p class="center"><?=date('F Y', strtotime($from))?></p>
            <p>We Acknowledge receipt of cash shown opposite our name as full compensation for our services rendered for the period covered. PART TIME INSTRUCTORS</p>
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
                            $data = $this->extras->getTotalPay($value->employeeid, $dates[0], $dates[1]);
                            $amount = $data[0]->teaching_pay;
                            $deduction = $data[0]->tardy + $data[0]->absents;
                            $netAmount = $data[0]->netbasicpay;
                            $taxDeduction = $data[0]->withholdingtax;
                            $netAmountAfterTax = $data[0]->net; ?>
                            <tr>
                                <td><?=$count?></td>
                                <td><?=$value->lname.', '.$value->fname.' '.$value->mname?></td>
                                <td><?=$value->emp_tin?></td>
                                <td><?=$ratePerHour?></td>
                                <td><?=$renderedHour?></td>
                                <td><?=number_format($amount, 2)?></td>
                                <td><?=number_format($deduction, 2)?></td>
                                <td><?=number_format($netAmount, 2)?></td>
                                <td><?=number_format($taxDeduction, 2)?></td>
                                <td><?=number_format($netAmountAfterTax, 2)?></td>
                                <td></td>
                            </tr>
                            <?php $totalAmount += $amount; $totalDeduction += $deduction; $totalNetAmount += $netAmount; $totalTaxDeduction += $taxDeduction; $totalNetAmountAfterTax += $netAmountAfterTax; } ?>
                        <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan=5>*** TOTAL ***</td>
                        <td><?=number_format($totalAmount, 2)?></td>
                        <td><?=number_format($totalDeduction, 2)?></td>
                        <td><?=number_format($totalNetAmount, 2)?></td>
                        <td><?=number_format($totalTaxDeduction, 2)?></td>
                        <td><?=number_format($totalNetAmountAfterTax, 2)?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table><br><br><br>
            
        </div>
        <div class="display-footer">
            <?=$footer?>
        </div>
    </div>
</body>
</html>