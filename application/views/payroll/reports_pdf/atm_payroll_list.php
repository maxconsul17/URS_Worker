<?php 

/**
 * @author Angelica
 * @copyright 2017
 *
 */

$CI =& get_instance();
$CI->load->library('PdfCreator_mpdf');
// echo '<pre>';
// print_r($campusid);die;

// function mPDF($mode='',$format='A4',$default_font_size=0,$default_font='',$mgl=15,$mgr=15,$mgt=16,$mgb=16,$mgh=9,$mgf=9, $orientation='P') {

// $mpdf = new mPDF('utf-8','LETTER','10','','3','3','6','10','9','9');
require_once  APPPATH . 'libraries/mpdf/vendor/autoload.php';
$campus_desc = (isset($campusid) ? (($campusid == "All" || $campusid == '') ? "All Campus" : $this->extensions->getCampusDescription($campusid)) : '');

$company_desc = (isset($company_campus) ? $this->extensions->getCompanyDescriptionReports($company_campus) : '');
$company_campus = $company_campus ? $company_campus : '';
if($bank_name == "METROBANK") $bank_name = "Metropolitan Bank and Trust Company";

$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'mpdf', 'margin_top' => '5', 'margin_bottom' => '5', 'margin_right' => '5', 'margin_left' => '5']);
date_default_timezone_set('Asia/Manila');
$mpdf->setFooter(date("Y-m-d h:i A")."       Page {PAGENO} of {nb}");
$styles = "
			<style>
				@page{            
					/*margin-top: 4.35cm;*/
					/*odd-header-name: html_Header;
					odd-footer-name: html_Footer;*/
				}
				@page :first {
					margin-top: 4.49cm;
					header: html_Header;
				}
				table{
					width: 100%;
					font-family:calibri;
					border-collapse: collapse;
				}
				.header, #maincontent th{
					color: blue;
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
				
			</style>
";

// LOLA 11-22-2022
$COMPANY_CAMPUS = ($company_campus == "" ? "University of Rizal System INC." : $company_campus);
$SCHOOL_NAME = "University of Rizal System";
$header = "<body>".Globals::headerPdf($campusid);
$content = '';
$content .= '
			<br>
			<table id="maincontent" class="table" cellspacing="0" cellpadding="10" border="1" style="font-family: type new roman">
				<thead>
					<tr style="background-color: #1032CC;">
						<td class="align_center"></td>
						<td class="align_center"><b style="font-size: 12px;color:white;">ACCOUNT #</b></td>
						<td class="align_center"><b style="font-size: 12px;color:white;">EMPLOYEE NAME</b></td>
						<td class="align_center"><b style="font-size: 12px;color:white;">NET SALARY</b></td>
					</tr>
				</thead>
				
				<tbody>';
						$count = 1;
						$sum = 0;
						$last_deptid = "";
						if($list){
							foreach ($list as $employeeid => $det) { 
								/*if($last_deptid != $det["description"]){
									$content .= ' 
										<tr>
											<td colspan="4"><b>'.$det["description"].'</b></td>
										</tr>';
								}*/
	$content .= ' 
									
									<tr>
										<td>'.$count.'</td>
										<td>'.$det["account_num"].'</td>
										<td>'.strtoupper(utf8_decode($det["fullname"])).'</td>
										<td>'.formatAmount($det["net_salary"]).'</td>
									</tr>
							
						'; 		
									$count++;
									$sum += $det["net_salary"];
									$last_deptid = $det["description"];
							}
						}
							
$content .= ' 					
				</tbody>
				<tfoot>
				    <tr>
				    	<td></td>
				    	<td></td>
				    	<td><b>Page Total: </b></td>
				        <td><b>'.formatAmount($sum).'</b></td>
				    </tr>
				</tfoot>
			</table>
';


$main = "
			".$styles."
			<div class='container'>
			".$header."
			".$content."

				<div tag='remarks' style='bottom: 100;left: 0;position: absolute;right: 0;'>
					<div style='display:flex'>
						<div style='float:left;width:50%;'>
							<h6>Total number of records: ".count($list)."</h6>
						</div>
						<div style='float:left;width:50%;'>
							<h6>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pay Period: <br>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".date("F d, Y", strtotime($sdate)) . ' - ' . date("F d, Y", strtotime($edate)) ."</h6>
						</div>
					</div>

					<h6>Total amount:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ".$sum."</h6>
					<br>
					<h6 style='text-indent:50px;'>I/We confirm that entries contained herein are true and correct. I/We further agree not to hold the Bank and its officer and staff liable from any claims, demands or losses resulting from errors, omission, and/or alterations in this project.</h6>

					<div style='display:flex'>
						<div style='float:left;width:50%;'>
							<div style='text-align:center;'><b>____________________________</b></div>
							<div style='text-align:center;'><h6 style='line-height:0.2px'>Prepared By:</h6></div>
						</div>
						<div style='float:left;width:50%;'>
							<div style='text-align:center;'><b>____________________________</b></div>
							<div style='text-align:center;'><h6 style='line-height:0.2px'>Approved By:</h6></div>
						</div>
					</div>
				</div>
			</div>
</body>
";

// echo $main; die;

function formatAmount($amount=''){
    if($amount){
        $amount = number_format( $amount, 2 );
    }else{
        $amount = '0.00';
    }
    return $amount;
}

// echo $main;
$mpdf->WriteHTML($main);

$mpdf->Output($path, 'F');

?>

