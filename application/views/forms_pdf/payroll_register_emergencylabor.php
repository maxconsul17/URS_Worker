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
            </table> </htmlpagefooter>';
    $totalAmount = $totalOvertime = $totalLate = $totalTax = $totalOtherPayables = $totalHealth = $totalAmountReceived = $count = 0;
    $dates = explode(' ', $payrollcutoff);
    list($from, $to) = $this->extras->getCutOffDTR($dates[0], $dates[1], $quarter);
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
        .signatories, .recapitulation {
            font-size: 12px;
        }
        .center {
            margin: 0;
            text-align: center;
        }
        .right {
            text-align: right;
        }
	</style>
<body>
    <div class="display">
        <div class="display-header">
            <?=$header?>
        </div>
        <div class="display-body"> <br> <br> <br> <br>
            <p class="center">EMERGENCY LABORERS</p>
            <p class="center"><?=date('F Y', strtotime($dates[0]))?></p>
            <p>We Acknowledge receipt of cash shown opposite our name as full compensation for our services rendered for the period stated.</p>
            <table class="table">
                <thead>
                    <tr>
                        <th colspan=2>NAMES</th>
                        <th>NO. OF DAYS / HRS</th>
                        <th>x</th>
                        <th>RATE PER DAY</th>
                        <th>TOTAL AMOUNT</th>
                        <th>OVERTIME</th>
                        <th>LATE</th>
                        <th>WITHHOLDING TAX</th>
                        <th>OTHER PAYABLES</th>
                        <th>PHILIPPINE HEALTH ISURANCE CORP.</th>
                        <th>TOTAL AMOUNT RECEIVED</th>
                        <th>SIGNATURE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (is_array($emplist)) {
                        foreach ($emplist as $value) { $count++; 
                            $rates = $this->extras->getRates($value->employeeid);
                            $noDays = $this->extras->getNumberOfDays($value->employeeid, $from, $to);
                            $ratePerDay = str_replace(',','',$rates['daily']);
                            $amount = $noDays * $ratePerDay;
                            $overtime = $value->overtime;
                            $late = $value->tardy;
                            $tax = $value->withholdingtax;
                            $deductions = explode('/',$value->fixeddeduc);
                            $health = 0;
                            foreach ($deductions as $deduction) {
                                $deduct = explode('=',$deduction);
                                if ($deduct[0] == 'PHILHEALTH') $health = $deduct[1];
                            }
                            $otherPayables = 0;
                            
                            $amountReceived = $amount + $overtime - $late - $tax - $otherPayables - $health;
                        ?>
                            <tr>
                                <td><?=$count?></td>
                                <td><?=$value->lname.', '.$value->fname.' '.$value->mname?></td>
                                <td><?=$noDays?></td>
                                <td>x</td>
                                <td><?=number_format($ratePerDay, 2)?></td>
                                <td><?=number_format($amount, 2)?></td>
                                <td><?=number_format($overtime, 2)?></td>
                                <td><?=number_format($late, 2)?></td>
                                <td><?=number_format($tax, 2)?></td>
                                <td><?=number_format($otherPayables, 2)?></td>
                                <td><?=number_format($health, 2)?></td>
                                <td><?=number_format($amountReceived, 2)?></td>
                                <td></td>
                            </tr>
                            <?php $totalAmount += $amount; $totalOvertime += $overtime; $totalLate += $late; $totalTax += $tax; $totalOtherPayables += $otherPayables; $totalHealth += $health; $totalAmountReceived += $amountReceived; } ?>
                        <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan=5>*** TOTAL ***</td>
                        <td><?=number_format($totalAmount, 2)?></td>
                        <td><?=number_format($totalOvertime, 2)?></td>
                        <td><?=number_format($totalLate, 2)?></td>
                        <td><?=number_format($totalTax, 2)?></td>
                        <td><?=number_format($totalOtherPayables, 2)?></td>
                        <td><?=number_format($totalHealth, 2)?></td>
                        <td><?=number_format($totalAmountReceived, 2)?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table> <br>
            <table class="recapitulation" width=15% style="margin-left: 25%;">
                <tr>
                    
                    <th colspan=2>Recapitulation</th>
                </tr>
                <tr>
                    <td width=50%></td>
                    <td width=50% class="right">-</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="right">-</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="right"><?=number_format($totalHealth, 2)?></td>
                </tr>
                <tr>
                    <td></td>
                    <td class="right" style="border-bottom: 1px solid"><?=number_format($totalAmountReceived, 2)?></td>
                </tr>
                <tr>
                    <td></td>
                    <td class="right"><b><?=number_format($totalAmountReceived + $totalHealth, 2)?></b></td>
                </tr>
            </table> <br>
            
        </div>
        <div class="display-footer">
            <?=$footer?>
        </div>
    </div>
</body>
</html>