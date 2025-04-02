<?php
 /**
  * @author Franco
  * @copyright 2024
  */

    $header = Globals::headerPdf($campusid,"GENERAL PAYROLL");
    $footer = '<htmlpagefooter name="Footer"> 
    <table width="100%">
            <tr >
                <td width="50%" style="text-align: right; border-top: 1px solid"><strong>'. date("F d, Y") .' -</strong> Page  <strong>{PAGENO}</strong> of <strong>{nbpg}</strong></td>
            </tr>
    </table> </htmlpagefooter>';
    $totalNetBasicPay = $totalNetAmount = $totalWitHoldingTax = $totalNet = $count = 0;
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
        .display-body table {
            border-collapse: collapse;
            font-size: 11px;
        }
        .display-body table th {
            border: 1px solid gray;
            background-color: #005;
            color: #FFF;
            padding: 5px;
        }
        .display-body table td {
            text-align: center;
            border: 1px solid gray;
            padding: 5px;
        }

	</style>
<body>
    <div class="display">
        <div class="display-header">
            <?=$header?>
        </div>
        <div class="display-body"> <br> <br> <br> <br>
            <p>We Acknowledge receipt of cash shown opposite our nanme as full compensation for our services rendered for the period covered. PART TIME INSTRUCTORS</p>
            <table class="table" width='100%'>
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
                <?php if (!empty($emplist)): ?>
                    <?php foreach ($emplist as $value): $count++; ?>
                        <tr>
                            <td><?=$count?></td>
                            <td><?=$value->lname.', '.$value->fname.' '.$value->mname?></td>
                            <td><?=$value->emp_tin?></td>
                            <td></td>
                            <td></td>
                            <td><?=number_format($value->netbasicpay, 2)?></td>
                            <td>-</td>
                            <td><?=number_format($value->teaching_pay, 2)?></td>
                            <td><?=number_format($value->withholdingtax, 2)?></td>
                            <td><?=number_format($value->net, 2)?></td>
                            <td></td>
                        </tr>
                        <?php $totalNetBasicPay += $value->netbasicpay; $totalNetAmount += $value->teaching_pay; $totalWitHoldingTax += $value->withholdingtax; $totalNet += $value->net; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11">No employee data found</td>
                    </tr>
                <?php endif; ?>

                </tbody>
                <tfoot>
                    <tr>
                        <td colspan=5>*** TOTAL ***</td>
                        <td><?=number_format($totalNetBasicPay, 2)?></td>
                        <td>-</td>
                        <td><?=number_format($totalNetAmount, 2)?></td>
                        <td><?=number_format($totalWitHoldingTax, 2)?></td>
                        <td><?=number_format($totalNet, 2)?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="display-footer">
            <?=$footer?>
        </div>
    </div>
</body>
</html>