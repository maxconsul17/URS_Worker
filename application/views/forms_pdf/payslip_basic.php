<?php
ini_set('memory_limit', -1);
ini_set('display_errors', '0');
set_time_limit(0);
require_once(APPPATH."constants.php");
$CI =& get_instance();
$CI->load->library('PdfCreator_mpdf');
require_once  APPPATH . 'libraries/mpdf/vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A7', 'tempDir' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'mpdf', 'margin_top' => '5', 'margin_bottom' => '5', 'margin_right' => '5', 'margin_left' => '5']);
$SIZE = false;    

$style = "
    <style type='text/css'>
       
        body{
            font-family: verdana;
            font-size: 10px;
        }
       
        .slipcontainer{
            display: inline-block;
            margin-top:10px;
            margin-left:10px;
            margin-right:10px;
            margin-bottom:10px;
        }

        .side-title{
            font-size: 6px;
        }

        table{
            font-size:5px;
            width: 100%;
            font-family:calibri;
            border-collapse: collapse;
        }
        .amount{
            text-align: right;
        }

        html,body{
            height: 100%
        }
        
        td{
            font-family: 'Times New Roman', Times, serif;
        }

        #maincontent td{
            text-align:center;
        }
        
        #remarks{
            text-align:right;
            font-size:5px;
            font-family: 'Times New Roman', Times, serif;
            margin-right:15px;
        }

    </style>
</head>
<body>";
$html = "";

$mpdf->WriteHTML($style);
foreach($emplist as $employeeid => $emp){
    // HEADER
    $html .= "
        <table width='100%' >
            <tr>
                <td width='24%' rowspan='1' style='text-align: right;'><img src='".base_url()."/images/school_logo.png' style='width: 35px;height:25px;' /></td>
                <td colspan='2' style='font-size: 10px;'>Republic of the Philippines</td>
            </tr>
            <tr>
                <td colspan='3' style='text-align: center;font-size: 10px;'>University of Rizal System</td>
            </tr>
            <tr>
                <td colspan='3' style='text-align: center;font-size: 10px;'>Morong Campus</td>
            </tr>
            <tr>
                <td colspan='3' style='text-align: center;font-size: 10px;'></td>
            </tr>
            <tr>
                <td colspan='3' style='text-align: center;font-size: 10px;'></td>
            </tr>
            <tr>
                <td colspan='3' valign='middle' style='padding: 0;text-align: center; margin-left:100px;font-weight:bold;'>P A Y S L I P &nbsp; - &nbsp; O T H E R &nbsp; C L A I M S</td>
            </tr>
        </table>   
    ";

    $html .='
    <div class="slipcontainer">
        <div class="contenttext">
            <br>
            <table>
                <tr>
                    <td class="side-title"><b>EMPLOYEE NAME: </b> '.$emp["fullname"].' </td>
                    <td></td>
                </tr>
                <tr>
                    <td class="side-title"><b>DESIGNATION:</b>  '.$this->extensions->getEmployeePositionDesc($employeeid).' </td>
                    <td></td>
                </tr>
                <tr>
                    <td class="side-title"><b>DEPARTMENT:</b> '.$this->extensions->getEmployeeDepartment($employeeid).'</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="side-title"><b>DATE OF CREDIT</b>: '.(date("F d, Y", strtotime($emp["date_processed"]))).' </td>
                    <td></td>
                </tr>
                
            </table>
            <br>
        
        ';
            
    // echo $html; die;
    $html .='
        </div>
            <table id="maincontent" class="table" cellspacing="0" cellpadding="10" border="1" style="font-family: type new roman">
                <thead>
                    <tr>
                        <th>PARTICULARS</th>
                        <th>GROSS</th>
                        <th>W/ HOLDING TAX</th>
                        <th>NET PAY</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td></td>
                        <td>'.number_format($emp["grosspay"], 2).'</td>
                        <td>'.number_format($emp["wtax"], 2).'</td>
                        <td>'.number_format($emp["netpay"], 2).'</td>
                    </tr>
                </tbody>
            </table>
    </div>
        ';

    if($counter == 2){
        $html .= "<pagebreak></pagebreak>";    
        $counter = 0;    
    }
    
    $counter++;

}

$html .='
    <div id="remarks">
        <p>Effectivity Date:'.(date("F d, Y")).'</p>
    </div>
';

$mpdf->WriteHTML($html);
$mpdf->WriteHTML("</body");

$mpdf->Output($path, 'F');
