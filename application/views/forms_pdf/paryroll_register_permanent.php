<?php
 /**
  * @author Franco
  * @copyright 2024
  */
  
    $counter = 0;
    $header = Globals::headerPdf($campusid,"");
    $dates = explode(' ', $payrollcutoff);
    // list($from, $to) = $this->extras->getCutOffDTR($dates[0], $dates[1], $quarter);

    $totalSalary = $totalAdjustedSalary = $totalDifferential = $totalPera = $totalTeachingPay = $totalTardy = $totalAbsent = $totalProvidentPremium = $totalWithholdingTax = $totalNet = 0;
    $totalDeductions = array();

    $deductions = explode('/',$emplist[0]->fixeddeduc);
    $deductionCount = count($deductions);

    $otherDeductions = array();
    foreach ($emplist as $emp) {
        $newOtherDeductions = explode('/', $emp->otherdeduc);
        foreach ($newOtherDeductions as $newOtherDeduction) {
            $newOtherDeduction = explode('=', $newOtherDeduction);
            if ($newOtherDeduction[0]) {
                $otherDeductions[$newOtherDeduction[0]] = $this->extensions->getDeductionDesc($newOtherDeduction[0]);
            }
        }
    }
    $otherDeductionCount = count($otherDeductions);

    $loans = array(); 
    foreach ($emplist as $emp) {
        $newLoans = explode('/', $emp->loan);
        foreach ($newLoans as $newLoan) {
            $newLoan = explode('=', $newLoan);
            if ($newLoan[0]) {
                $loans[$newLoan[0]] = $this->extensions->getLoanDesc($newLoan[0]);
            }
        }
    }
    $loanCount = count($loans);
    // echo"<pre>";print_r($emplist);die;
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
            <p class="center"><b>PAYROLL FOR EMPLOYEES by DEPARTMENT</b></p>
            <p>We Acknowledge receipt of sums shown opposite our names in CASH as full compensation for our services for the period: <?=date('F j - ', strtotime($dates[0]))?><?=date(' j, Y', strtotime($dates[1]))?>.</p>
            <table class="table">
                <thead>
                    <tr>
                        <th rowspan="2" colspan=1>No</th>
                        <th rowspan="2" colspan=1>Employees</th>
                        <th rowspan="2">Position</th>
                        <th rowspan="2">Monthly Salary</th>
                        <th rowspan="2">Adjusted Salary</th>
                        <th rowspan="2">Differential</th>
                        <th colspan="<?=0+4?>">Earnings</th>
                        <th colspan="<?=$deductionCount+$otherDeductionCount+$loanCount+2?>">Less: DEDUCTIONS</th>
                        <th rowspan="2">Net Pay</th>
                        <th rowspan="2">Signature</th>
                    </tr>
                    <tr>
                        <th>PERA</th>
                        <th>Teaching Pay</th>
                        <th>Tardy / UT</th>
                        <th>Absent</th>
                        <th>Provident Premium</th>
                        <th>Withholding Tax</th>
                        <?php foreach ($deductions as $deduction) { 
                            $deduction = explode('=', $deduction); ?>
                            <th><?=str_replace('+', ' ', $deduction[0])?></th>
                        <?php } ?>
                        <?php foreach ($otherDeductions as $otherDeduction) { ?>
                            <th><?=str_replace('+', ' ', $otherDeduction)?></th>
                        <?php } ?>
                        <?php foreach ($loans as $loan) { ?>
                            <th><?=str_replace('+', ' ', $loan)?></th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emplist as $employee) { $counter++; ?>
                        <tr>
                            <td><?=$counter?></td>
                            <td><?=$this->extensions->getEmployeeName($employee->employeeid)?></td>
                            <td><?=$this->extensions->getEmployeePositionDesc($employee->employeeid)?></td>
                            <td><?=number_format($employee->salary,2) ?></td>
                            <td>-</td>
                            <td>-</td>

                            <!-- EARNINGS -->
                            <td><?=number_format($employee->pera,2) ?></td>
                            <td><?=number_format($employee->teaching_pay,2) ?></td>
                            <td><?=number_format($employee->tardy,2) ?></td>
                            <td><?=number_format($employee->absents,2) ?></td>
                            
                            <!-- DEDUCTIONS -->
                            <td><?=number_format($employee->provident_premium,2) ?></td>
                            <td><?=number_format($employee->withholdingtax,2) ?></td>
                            <?php $deductions = explode('/',$employee->fixeddeduc); ?>
                            <?php foreach ($deductions as $deduction) { 
                                $deduction = explode('=', $deduction); ?>
                                <td><?=number_format($deduction[1],2)?></td>
                                <?php $totalDeductions[$deduction[0]] = isset($totalDeductions[$deduction[0]]) ? $totalDeductions[$deduction[0]] : 0; ?>
                                <?php $totalDeductions[$deduction[0]] += $deduction[1]; ?>
                            <?php } ?>

                            <?php foreach ($otherDeductions as $id => $otherDeduction) { ?>
                                <?php $employeeOtherDeductions = explode('/',$employee->otherdeduc); ?>
                                <?php foreach ($employeeOtherDeductions as $employeeOtherDeduction) { ?>
                                    <?php $employeeOtherDeduction = explode('=', $employeeOtherDeduction); ?>
                                    <?php if (isset($employeeOtherDeduction[1]) && $employeeOtherDeduction[0] == $id) { ?>
                                        <td><?=number_format($employeeOtherDeduction[1],2)?></td>
                                        <?php $totalOtherDeductions[$employeeOtherDeduction[0]] = isset($totalOtherDeductions[$deduction[0]]) ? $totalOtherDeductions[$employeeOtherDeduction[0]] : 0; ?>
                                        <?php $totalOtherDeductions[$employeeOtherDeduction[0]] += $employeeOtherDeduction[1]; ?>
                                    <?php } else { ?>
                                        <td>0.00</td>
                                    <?php } ?>
                                <?php } ?>
                            <?php } ?>

                            <!-- LOANS -->
                            <?php foreach ($loans as $id => $loan) { ?>
                                <?php $employeeLoans = explode('/',$employee->loan); ?>
                                <?php foreach ($employeeLoans as $employeeLoan) { ?>
                                    <?php $employeeLoan = explode('=', $employeeLoan); ?>
                                    <?php if (isset($employeeLoan[1]) && $employeeLoan[0] == $id) { ?>
                                        <td><?=number_format($employeeLoan[1],2)?></td>
                                        <?php $totalLoans[$employeeLoan[0]] = isset($totalLoans[$employeeLoan[0]]) ? $totalLoans[$employeeLoan[0]] : 0; ?>
                                        <?php $totalLoans[$employeeLoan[0]] += $employeeLoan[1]; ?>
                                    <?php } else { ?>
                                        <td>0.00</td>
                                    <?php } ?>
                                <?php } ?>
                            <?php } ?>

                            <td><?=number_format($employee->net,2) ?></td>
                            <td></td>
                        </tr>
                        <?php $totalSalary += $employee->salary; $totalAdjustedSalary += 0; $totalDifferential += 0; $totalPera += $employee->pera; $totalTeachingPay += $employee->teaching_pay; $totalTardy += $employee->tardy; $totalAbsent += $employee->absents; $totalProvidentPremium += $employee->provident_premium; $totalWithholdingTax += $employee->withholdingtax; $totalNet += $employee->net; ?>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">GRAND TOTAL</th>
                        <th><?=number_format($totalSalary,2)?></th>
                        <th><?=number_format($totalAdjustedSalary,2)?></th>
                        <th><?=number_format($totalDifferential,2)?></th>
                        <th><?=number_format($totalPera,2)?></th>
                        <th><?=number_format($totalTeachingPay,2)?></th>
                        <th><?=number_format($totalTardy,2)?></th>
                        <th><?=number_format($totalAbsent,2)?></th>
                        <th><?=number_format($totalProvidentPremium,2)?></th>
                        <th><?=number_format($totalWithholdingTax,2)?></th>
                        <?php foreach ($totalDeductions as $key => $value) { ?>
                            <th><?=number_format($value,2)?></th>
                        <?php } ?>
                        <?php foreach ($totalOtherDeductions as $key => $value) { ?>
                            <th><?=number_format($value,2)?></th>
                        <?php } ?>
                        <?php foreach ($totalLoans as $key => $value) { ?>
                            <th><?=number_format($value,2)?></th>
                        <?php } ?>
                        <th><?=number_format($totalNet,2)?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table> <br>
        </div>
        <div class="display-footer"> <br><br><br>
            <table width="100%">
                <tr>
                    <td width="15%" style="padding: 10px">APPROVED FOR PAYMENT:</td>
                    <td width="30%" style="padding: 10px; text-align: center;"><?=$aname?><hr style="color: black; margin: 0"><?=$aposition?></td>
                    <td width="30%" style="padding: 10px">I CERTIFY MY OFFICIAL OATH THAT EACH EMPLOYEE WHOSE NAME APPEARS ON THE ABOVE ROLL HAS RECEIVED THE AMOUNT / SALARY WARRANT INDICATED OPPOSITE HIS/HER NAME</td>
                    <td width="30%" style="padding: 10px">FUNDS AVAILABLE:</td>
                </tr>
                <tr><td colspan="4">&nbsp;</td></tr>
                <tr>
                    <td style="padding: 10px">CERTIFIED CORRECT:</td>
                    <td style="padding: 10px; text-align: center;"><?=$cname?><hr style="color: black; margin: 0"><?=$cposition?></td>
                    <td style="padding: 10px; text-align: center;"><?=$cname2?><hr style="color: black; margin: 0"><?=$cposition2?></td>
                    <td style="padding: 10px; text-align: center;"><?=$cname3?><hr style="color: black; margin: 0"><?=$cposition3?></td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>