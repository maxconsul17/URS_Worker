<?php 
    /**
    * @author Max Consul
    * @copyright 2019
    */

    $this->lib_includer->load("excel/Writer");
    // require_once(APPPATH."constants.php");
    $xls = New Spreadsheet_Excel_Writer($path);

    /** Fonts Format */
    $normal =& $xls->addFormat(array('Size' => 10));
    $normal->setLocked();
    $normalcenter =& $xls->addFormat(array('Size' => 10));
    $normalcenter->setAlign("center");
    $normalcenter->setLocked();

    $normalLeftBorder =& $xls->addFormat(array('Size' => 8));
    $normalLeftBorder->setAlign("left");
    $normalLeftBorder->setBorder(1);
    $normalLeftBorder->setLocked();

    $normalCenterBorder =& $xls->addFormat(array('Size' => 8));
    $normalCenterBorder->setAlign("center");
    $normalCenterBorder->setBorder(1);
    $normalCenterBorder->setLocked();
    
    $normalRightBorder =& $xls->addFormat(array('Size' => 8));
    $normalRightBorder->setNumFormat("#,##0.00");
    $normalRightBorder->setBorder(1);
    $normalRightBorder->setAlign("right");
    $normalRightBorder->setLocked();

    $normalcenter2 =& $xls->addFormat(array('Size' => 10));
    $normalcenter2->setAlign("center");
    $normalcenter2->setNumFormat("#");
    $normalcenter2->setLocked();

    $normalunderlined =& $xls->addFormat(array('Size' => 10));
    $normalunderlined->setBottom(1);
    $normalunderlined->setLocked();  
    
    $big =& $xls->addFormat(array('Size' => 12));
    $big->setLocked();
    
    $bigbold =& $xls->addFormat(array('Size' => 11));
    $bigbold->setBold();
    $bigbold->setLocked();
    
    $bigboldcenter =& $xls->addFormat(array('Size' => 12));
    $bigboldcenter->setBold();
    $bigboldcenter->setAlign("center");
    $bigboldcenter->setLocked();
    
    $bold =& $xls->addFormat(array('Size' => 8));
    $bold->setBold();
    $bold->setLocked();
    
    $boldcenter =& $xls->addFormat(array('Size' => 8));
    $boldcenter->setAlign("center");
    $boldcenter->setBold();
    $boldcenter->setLocked();
    
    $boldCenterBorder =& $xls->addFormat(array('Size' => 8));
    $boldCenterBorder->setAlign("center");
    $boldCenterBorder->setBold();
    $boldCenterBorder->setFgColor('blue');
    $boldCenterBorder->setColor('white');
    $boldCenterBorder->setBorder(1);
    $boldCenterBorder->setTextWrap();
    $boldCenterBorder->setLocked();
    
    $boldLeftBorder =& $xls->addFormat(array('Size' => 8));
    $boldLeftBorder->setAlign("left");
    $boldLeftBorder->setBold();
    $boldLeftBorder->setFgColor('blue');
    $boldLeftBorder->setColor('white');
    $boldLeftBorder->setBorder(1);
    $boldLeftBorder->setTextWrap();
    $boldLeftBorder->setLocked();
    
    $boldRightBorder =& $xls->addFormat(array('Size' => 8));
    $boldRightBorder->setAlign("right");
    $boldRightBorder->setFgColor('blue');
    $boldRightBorder->setColor('white');
    $boldRightBorder->setNumFormat("#,##0.00_);\(#,##0.00\)");
    $boldRightBorder->setBold();
    $boldRightBorder->setBorder(1);
    $boldRightBorder->setTextWrap();
    $boldRightBorder->setLocked();
    
    $amount =& $xls->addFormat(array('Size' => 8));
    $amount->setAlign("right");
    $amount->setNumFormat("#,##0.00");
    $amount->setLocked();
    
    $amountbold =& $xls->addFormat(array('Size' => 8));
    $amountbold->setNumFormat("#,##0.00_);\(#,##0.00\)");
    $amountbold->setAlign("right");
    $amountbold->setBold();
    $amountbold->setLocked();
    
    $number =& $xls->addFormat(array('Size' => 8));
    $number->setNumFormat("#,##0");
    $number->setLocked();
    
    $numberbold =& $xls->addFormat(array('Size' => 8));
    $numberbold->setNumFormat("#,##0");
    $numberbold->setBold();
    $numberbold->setLocked();

    $sheet = &$xls->addWorksheet("Payroll Registrar");

    function writeText(&$sheet,$text,&$font,$row1, $col1, $row2 = '', $col2 = ''){
    
        if($row2 != '' || $col2 != ''){
            $sheet->setMerge($row1,$col1,$row1 + $row2, $col1 + $col2);
        }

        $sheet->write($row1,$col1,$text,$font);

        return ($row1 + 1);
    }

    function writeBorder(&$sheet, $count_col, $start_col, $row, &$font){
        for($col = $start_col; $col < $start_col + ($count_col); $col++) { 
            $sheet->writeBlank($row, $col, $font);
        }
    }

    function writeTable(&$sheet, $arr_content, $start_col, $start_row){
        $row = $start_row;

        foreach ($arr_content as $content) {
            $col = $start_col;

            foreach ($content as $key => $info) {
                list($caption, $size, $fonts) = $info;

                $sheet->setColumn($col, $col, $size);

                if($col == 1) $sheet->writeString($row, $col, $caption, $fonts);
                else          $sheet->write($row, $col, $caption, $fonts);

                $col += 1;
            }

            $row += 1;
        }

        return $row;
    }

    $campus_name = ($campus_name == "No Campus") ? "ALL" : $campus_name;
    $row = $col = 0;
    $company_desc = (isset($company_campus) ? $this->extensions->getCompanyDescriptionReports($company_campus) : '');

    $count_income_col = 6 + count($inc_income) + count($inc_income) /*+ count($inc_adjustment)*/ + 7;
    $count_deduction_col = 3 + count((isset($grand_total["deduction"]["fixed_deduc_list"])) ? $grand_total["deduction"]["fixed_deduc_list"] : array()) + count((isset($grand_total["deduction"]["deduc_list"])) ? $grand_total["deduction"]["deduc_list"]: array()) + count((isset($grand_total["deduction"]["loan_list"])) ? $grand_total["deduction"]["loan_list"] : array());

    $end_col = ((($count_income_col > $count_deduction_col) ? $count_income_col : $count_deduction_col) + 3) - 1;

    // $row = writeText($sheet, "", $bigboldcenter, $row, $col, 0, $end_col);
    // $row = writeText($sheet, $SCHOOL_NAME, $bigboldcenter, $row, $col, 0, $end_col);
    // $row = writeText($sheet, $SCHOOL_CAPTION, $normalcenter, $row, $col, 0, $end_col);
    // $row = writeText($sheet, "", $bigboldcenter, $row, $col, 0, $end_col);
    // $row = writeText($sheet, "PAYROLL SHEET FOR SALARY SCHEDULE : ". $sched_display, $bigboldcenter, $row, $col, 0, $end_col);
    // $row = writeText($sheet, $campus_name. " CAMPUS", $bigboldcenter, $row, $col, 0, $end_col);
    // $row = writeText($sheet, strtoupper($company_desc), $bigboldcenter, $row, $col, 0, $end_col);
    // $row = writeText($sheet, "", $bigboldcenter, $row, $col, 0, $end_col);

    $numfield = $end_col;
    $sheet->setMerge(0, 0, 0, $numfield);
    $sheet->setMerge(1, 0, 1, $numfield);
    $sheet->setMerge(2, 0, 2, $numfield);
    $sheet->setMerge(3, 0, 3, $numfield);
    $sheet->setMerge(4, 0, 4, $numfield);
    $sheet->setMerge(5, 0, 5, $numfield);
    $sheet->setMerge(6, 0, 6, $numfield);
    $sheet->setMerge(7, 0, 7, $numfield);
    $c = $r =  0;
    $bitmap = Globals::excel_header_bmp($campus);
    $sheet->insertBitmap( $r , $c  +  4 , $bitmap , 0 , 8 , .25 ,.60 );
    $sheet->write(7,0,"PAYROLL SHEET FOR SALARY SCHEDULE : ".$sched_display,$bigboldcenter);
    
    
    $row = 9;

    
    // income
    $income_header = array();
    $income_header[] = array("#", 10, $boldCenterBorder);
    if($display == "detailed"){
        $income_header[] = array("EMPLOYEE ID", 30, $boldCenterBorder);
        $income_header[] = array("EMPLOYEE NAME", 50, $boldCenterBorder);
    }else{
        $income_header[] = array("DEPARTMENT", 50, $boldCenterBorder);
    }
    $income_header[] = array("SALARY", 20, $boldCenterBorder);
    $income_header[] = array("TARDY", 20, $boldCenterBorder);
    $income_header[] = array("ABSENT", 20, $boldCenterBorder);
    $income_header[] = array("BASIC PAY", 20, $boldCenterBorder);

    foreach ($inc_income as $key => $value) {
        $income_header[] = array($config["income"][$key], 20, $boldCenterBorder);
    }
/*    $income_header[] = array("OTHER DEMINIMISS", 20, $boldCenterBorder);

    foreach ($inc_income as $key => $value) {
        $income_header[] = array($config["income"][$key], 20, $boldCenterBorder);
    }*/
    $income_header[] = array("OTHER INCOME", 20, $boldCenterBorder);

/*    foreach ($inc_adjustment as $key => $value) {
        $income_header[] = array($config["income"][$key]." ADJ", 20, $boldCenterBorder);
    }
    $income_header[] = array("OTHER ADJUSTMENT", 20, $boldCenterBorder);*/

    $income_header[] = array("OVERTIME", 20, $boldCenterBorder);
    $income_header[] = array("GROSS PAY", 20, $boldCenterBorder);
    $income_header[] = array("PROVIDENT PREMIUM", 20, $boldCenterBorder);
    $income_header[] = array("WITH HOLDING TAX", 20, $boldCenterBorder);
    
    foreach ($inc_fixed_deduc as $key => $value) {
        $income_header[] = array($key, 20, $boldCenterBorder);
    }
    
    foreach ($inc_deduction as $key => $value) {
        $income_header[] = array($config["deduction"][$key], 20, $boldCenterBorder);
    }
    
    foreach ($inc_loan as $key => $value) {
        $income_header[] = array($config["loan"][$key], 20, $boldCenterBorder);
    }

    $income_header[] = array("OTHER DEDUCTION", 20, $boldCenterBorder);
    // $income_header[] = array("WITH HOLDING TAX", 20, $boldCenterBorder);
    $income_header[] = array("TOTAL DEDUCTION", 20, $boldCenterBorder);
    $income_header[] = array("NET PAY", 20, $boldCenterBorder);

    writeBorder($sheet, count($income_header + $income_header), 0, $row, $boldCenterBorder);
    $row = writeText($sheet, "  INCOME AND DEDUCTION", $boldLeftBorder, $row, $col, 0, (count($income_header) - 1));
    $row = writeTable($sheet, array($income_header), 0, $row);
    $campDesc = $compDesc = $deptDesc = "sometext";
    if($display == "detailed"){
        $count = 1;
        foreach($emp_list as $sort_key => $employees){
            foreach($employees as $employeeid => $employee){
                writeBorder($sheet, count($income_header), 0, $row, $boldCenterBorder);
                // if($sort_type!="name") $row = writeText($sheet, " ". $config[$sort_type][$sort_key] , $boldLeftBorder, $row, $col, 0, (count($income_header) - 1));
                foreach($employee as $employeeid => $emp_info){
                    $currentCampus = $this->extensions->getCampusDescription($emp_info['campus']); 
                    $currentDepartment = $this->extensions->getDepartmentDescription($emp_info['deptid']); 
                    if($campDesc != $currentCampus){
                        $row = writeText($sheet, $currentCampus, $boldLeftBorder, $row, $col, 0, (count($income_header) - 1));
                    }
                    if($compDesc != $emp_info['company']){
                         // $row = writeText($sheet, $emp_info['company'], $boldLeftBorder, $row, $col, 0, (count($income_header) - 1));
                    }
                    if($sort_type == "department"){
                        if($deptDesc != $currentDepartment){
                            $row = writeText($sheet, $currentDepartment, $boldLeftBorder, $row, $col, 0, (count($income_header) - 1));
                        }
                    }
                    $content_table = array();
                    $content_table[] = array(($count), 10, $normalCenterBorder);
                    $content_table[] = array($employeeid, 30, $normalCenterBorder);
                    $content_table[] = array($emp_info["name"], 50, $normalCenterBorder);
                    $content_table[] = array($emp_info["income"]["salary"], 20, $normalRightBorder);
                    $content_table[] = array($emp_info["income"]["tardy"], 20, $normalRightBorder);
                    $content_table[] = array($emp_info["income"]["absent"], 20, $normalRightBorder);
                    $content_table[] = array($emp_info["income"]["basic_pay"], 20, $normalRightBorder);

    /*                foreach ($inc_income as $key => $value) {
                        $content_table[] = array((isset($emp_info["income"]["income_list"][$key])) ? $emp_info["income"]["income_list"][$key] : 0, 20, $normalRightBorder);
                    }
                    $content_table[] = array((isset($emp_info["income"]["totalOtherDeminimissToDisplay"])) ? $emp_info["income"]["totalOtherDeminimissToDisplay"] : 0, 20, $normalRightBorder);*/

                    foreach ($inc_income as $key => $value) {
                        $content_table[] = array((isset($emp_info["income"]["income_list"][$key])) ? $emp_info["income"]["income_list"][$key] : 0, 20, $normalRightBorder);
                    }
                    $content_table[] = array((isset($emp_info["income"]["totalIncomeToDisplay"])) ? $emp_info["income"]["totalIncomeToDisplay"] : 0, 20, $normalRightBorder);

    /*                foreach ($inc_adjustment as $key => $value) {
                        $content_table[] = array((isset($emp_info["income"]["adjustment_list"][$key])) ? $emp_info["income"]["adjustment_list"][$key] : 0, 20, $normalRightBorder);
                    }
                    $content_table[] = array((isset($emp_info["income"]["totalOtherAdjustmentToDisplay"])) ? $emp_info["income"]["totalOtherAdjustmentToDisplay"] : 0, 20, $normalRightBorder);*/

                    $content_table[] = array($emp_info["income"]["overtime"], 20, $normalRightBorder);
                    $content_table[] = array($emp_info["income"]["gross"], 20, $normalRightBorder);
                    $content_table[] = array($emp_info["deduction"]["provident_premium"], 20, $normalRightBorder);
                    $content_table[] = array($emp_info["deduction"]["with_holding_tax"], 20, $normalRightBorder);

                    foreach ($inc_fixed_deduc as $key => $value) {
                        $content_table[] = array((isset($emp_info["deduction"]["fixed_deduc_list"][$key])) ? $emp_info["deduction"]["fixed_deduc_list"][$key] : 0, 20, $normalRightBorder);
                    }

                    foreach ($inc_deduction as $key => $value) {
                        $content_table[] = array((isset($emp_info["deduction"]["deduc_list"][$key])) ? $emp_info["deduction"]["deduc_list"][$key] : 0, 20, $normalRightBorder);
                    }

                    foreach ($inc_loan as $key => $value) {
                        $content_table[] = array((isset($emp_info["deduction"]["loan_list"][$key])) ? $emp_info["deduction"]["loan_list"][$key] : 0, 20, $normalRightBorder);
                    }
                    $content_table[] = array($emp_info["deduction"]["totalOtherDeductionToDisplay"], 20, $normalRightBorder);
                    // $content_table[] = array($emp_info["deduction"]["provident_premium"], 20, $normalRightBorder);
                    // $content_table[] = array($emp_info["deduction"]["with_holding_tax"], 20, $normalRightBorder);
                    $content_table[] = array($emp_info["deduction"]["total_deduction"], 20, $normalRightBorder);
                    $content_table[] = array($emp_info["deduction"]["net"], 20, $normalRightBorder);

                    $row = writeTable($sheet, array($content_table), 0, $row);
                    $count++;
                    $campDesc = $currentCampus;
                    $compDesc = $emp_info['company'];
                    $deptDesc = $currentDepartment;
                }
            }
        }

        $content_table = array();
        $content_table[] = array("", 10, $boldRightBorder);
        $content_table[] = array("", 30, $boldRightBorder);
        $content_table[] = array("Grand Total : ", 50, $boldRightBorder);
        $content_table[] = array((isset($grand_total["income"]["salary"])) ? $grand_total["income"]["salary"] : '0', 20, $boldRightBorder);
        $content_table[] = array((isset($grand_total["income"]["tardy"])) ? $grand_total["income"]["tardy"] : '0', 20, $boldRightBorder);
        $content_table[] = array((isset($grand_total["income"]["absent"])) ? $grand_total["income"]["absent"]: '0', 20, $boldRightBorder);
        $content_table[] = array((isset($grand_total["income"]["basic_pay"])) ? $grand_total["income"]["basic_pay"] : '0', 20, $boldRightBorder);

/*        foreach ($inc_income as $key => $value) {
            $content_table[] = array((isset($grand_total["income"]["income_list"][$key])) ? $grand_total["income"]["income_list"][$key] : 0, 20, $boldRightBorder);                    
        }
            $content_table[] = array((isset($grand_total["income"]["totalOtherDeminimissToDisplay"])) ? $grand_total["income"]["totalOtherDeminimissToDisplay"] : 0, 20, $boldRightBorder);*/

        foreach ($inc_income as $key => $value) {
            $content_table[] = array((isset($grand_total["income"]["income_list"][$key])) ? $grand_total["income"]["income_list"][$key] : 0, 20, $boldRightBorder);                    
        }
            $content_table[] = array((isset($grand_total["income"]["totalIncomeToDisplay"])) ? $grand_total["income"]["totalIncomeToDisplay"] : 0, 20, $boldRightBorder);

/*        foreach ($inc_adjustment as $key => $value) {
            $content_table[] = array((isset($grand_total["income"]["adjustment_list"][$key])) ? $grand_total["income"]["adjustment_list"][$key] : 0, 20, $boldRightBorder);                    
        }
            $content_table[] = array((isset($grand_total["income"]["totalOtherAdjustmentToDisplay"])) ? $grand_total["income"]["totalOtherAdjustmentToDisplay"] : 0, 20, $boldRightBorder);   */

        $content_table[] = array((isset($grand_total["income"]["overtime"])) ? $grand_total["income"]["overtime"] : '0', 20, $boldRightBorder);
        $content_table[] = array((isset($grand_total["income"]["gross"])) ? $grand_total["income"]["gross"] : '0', 20, $boldRightBorder);

        $content_table[] = array((isset($grand_total["deduction"]["provident_premium"])) ? $grand_total["deduction"]["provident_premium"]: '0', 20, $boldRightBorder);
        $content_table[] = array((isset($grand_total["deduction"]["with_holding_tax"])) ? $grand_total["deduction"]["with_holding_tax"]: '0', 20, $boldRightBorder);

        foreach ($inc_fixed_deduc as $key => $value) {
            $content_table[] = array((isset($grand_total["deduction"]["fixed_deduc_list"][$key])) ? $grand_total["deduction"]["fixed_deduc_list"][$key] : 0, 20, $boldRightBorder);
        }

        foreach ($inc_deduction as $key => $value) {
            $content_table[] = array((isset($grand_total["deduction"]["deduc_list"][$key])) ? $grand_total["deduction"]["deduc_list"][$key] : 0, 20, $boldRightBorder);
        }

        foreach ($inc_loan as $key => $value) {
            $content_table[] = array((isset($grand_total["deduction"]["loan_list"][$key])) ? $grand_total["deduction"]["loan_list"][$key] : 0, 20, $boldRightBorder);
        }

        $content_table[] = array((isset($grand_total["deduction"]["totalOtherDeductionToDisplay"])) ? $grand_total["deduction"]["totalOtherDeductionToDisplay"] : '0', 20, $boldRightBorder);
        // $content_table[] = array((isset($grand_total["deduction"]["with_holding_tax"])) ? $grand_total["deduction"]["with_holding_tax"] : '0', 20, $boldRightBorder);
        $content_table[] = array((isset($grand_total["deduction"]["total_deduction"])) ? $grand_total["deduction"]["total_deduction"] : '0', 20, $boldRightBorder);
        $content_table[] = array((isset($grand_total["deduction"]["net"])) ? $grand_total["deduction"]["net"] : '0', 20, $boldRightBorder);

        $row = writeTable($sheet, array($content_table), 0, $row);
    }else{
        $count = 1;
        foreach($summary as $sort_key => $summary_data){
            writeBorder($sheet, count($income_header), 0, $row, $boldCenterBorder);
            // $row = writeText($sheet, " ". $config[$sort_type][$sort_key] , $boldLeftBorder, $row, $col, 0, (count($income_header) - 1));
            $currentDepartment = $this->extensions->getDepartmentDescription($sort_key);
            $content_table = array();
            $content_table[] = array(($count), 10, $normalCenterBorder);
            $content_table[] = array($currentDepartment, 50, $normalCenterBorder);
            $content_table[] = array($summary_data["salary"], 20, $normalRightBorder);
            $content_table[] = array($summary_data["tardy"], 20, $normalRightBorder);
            $content_table[] = array($summary_data["absent"], 20, $normalRightBorder);
            $content_table[] = array($summary_data["basic_pay"], 20, $normalRightBorder);

            foreach ($inc_income as $key => $value) {
                $content_table[] = array((isset($summary_data["income_list"][$key])) ? $summary_data["income_list"][$key] : 0, 20, $normalRightBorder);
            }
            // $content_table[] = array((isset($summary_data["totalOtherDeminimissToDisplay"])) ? $summary_data["totalOtherDeminimissToDisplay"] : 0, 20, $normalRightBorder);

            // foreach ($inc_income as $key => $value) {
            //     $content_table[] = array((isset($summary_data["income_list"][$key])) ? $summary_data["income_list"][$key] : 0, 20, $normalRightBorder);
            // }
            $content_table[] = array((isset($summary_data["totalOtherIncomeToDisplay"])) ? $summary_data["totalOtherIncomeToDisplay"] : 0, 20, $normalRightBorder);

/*            foreach ($inc_adjustment as $key => $value) {
                $content_table[] = array((isset($summary_data["adjustment_list"][$key])) ? $summary_data["adjustment_list"][$key] : 0, 20, $normalRightBorder);
            }
            $content_table[] = array((isset($summary_data["totalOtherAdjustmentToDisplay"])) ? $summary_data["totalOtherAdjustmentToDisplay"] : 0, 20, $normalRightBorder);*/

            $content_table[] = array($summary_data["overtime"], 20, $normalRightBorder);
            $content_table[] = array($summary_data["gross"], 20, $normalRightBorder);

            $content_table[] = array($summary_data["provident_premium"], 20, $normalRightBorder);
            $content_table[] = array($summary_data["with_holding_tax"], 20, $normalRightBorder);

            foreach ($inc_fixed_deduc as $key => $value) {
                $content_table[] = array((isset($summary_data["fixed_deduc_list"][$key])) ? $summary_data["fixed_deduc_list"][$key] : 0, 20, $normalRightBorder);
            }

            foreach ($inc_deduction as $key => $value) {
                $content_table[] = array((isset($summary_data["deduc_list"][$key])) ? $summary_data["deduc_list"][$key] : 0, 20, $normalRightBorder);
            }

            foreach ($inc_loan as $key => $value) {
                $content_table[] = array((isset($summary_data["loan_list"][$key])) ? $summary_data["loan_list"][$key] : 0, 20, $normalRightBorder);
            }
            $content_table[] = array($summary_data["totalOtherDeductionToDisplay"], 20, $normalRightBorder);
            $content_table[] = array($summary_data["with_holding_tax"], 20, $normalRightBorder);
            $content_table[] = array($summary_data["total_deduction"], 20, $normalRightBorder);
            $content_table[] = array($summary_data["net"], 20, $normalRightBorder);

            $row = writeTable($sheet, array($content_table), 0, $row);
            $count++;
        }
    }

    // $xls->send("Payroll Register Report.xls");
    $xls->close();
?>

