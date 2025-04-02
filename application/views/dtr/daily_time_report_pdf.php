<?php 

/**
 * @author Angelica
 * @copyright 2017
 *
 */

set_time_limit(0);
ini_set('memory_limit', -1);
ini_set('max_execution_time', '500');
ini_set("pcre.backtrack_limit", "10000000");

$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => array(215.9, 355.6), 'tempDir' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'mpdf', 'margin_top' => '5', 'margin_bottom' => '5', 'margin_right' => '5', 'margin_left' => '5']);
$mpdf->useSubstitutions = false; 
$mpdf->simpleTables = true;
$mpdf->SetDisplayMode('fullpage');
// $mpdf->SetCompression(true);

$content  = "
<style>
    @page{            
        margin-top: 4.5cm;
        odd-header-name: html_Header;
        odd-footer-name: html_Footer;
    }
    th{
    	color: white;
    }  
    table{
        border-collapse: collapse;
        font-size: 9px;
        border-spacing: 3px;
	}
	#indvtbl td, #indvtblnt td {
		text-align: center;
	}
</style>";


foreach ($report_list as $key => $value) {
	$header = Globals::headerPdf($value['campus'], "Daily Time Report", $value['header_desc'], true);
	$content .= $header;
	$content .= $value['report'];
	if(isset($report_list[$key+1])) $content .= '<pagebreak>';
}

// Collect all content and write to the PDF
$mpdf->WriteHTML($content);
$content = ob_get_clean();

// Save the generated PDF
$mpdf->Output($path, "F");

?>
