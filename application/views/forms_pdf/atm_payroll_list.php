<?php 

/**
 * @author Angelica
 * @copyright 2017
 *
 */

$CI =& get_instance();
$CI->load->library('PdfCreator_mpdf');
// echo '<pre>';
// print_r($list);die;

// function mPDF($mode='',$format='A4',$default_font_size=0,$default_font='',$mgl=15,$mgr=15,$mgt=16,$mgb=16,$mgh=9,$mgf=9, $orientation='P') {

// $mpdf = new mPDF('utf-8','LETTER','10','','3','3','6','10','9','9');
require_once  APPPATH . 'libraries/mpdf/vendor/autoload.php';
$campus_desc = (isset($campusid) ? ($campusid == "All" || $campusid == '' ? "All Campus" : $this->extensions->getCampusDescription($campusid)) : '');
$company_desc = (isset($company_campus) ? $this->extensions->getCompanyDescriptionReports($company_campus) : '');
$company_campus = $company_campus ?? '';
if(isset($bank_name) && $bank_name == "METROBANK") $bank_name = "Metropolitan Bank and Trust Company";

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => [400, 400], // Width: 900 points, Height: 600 points
    'tempDir' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mpdf',
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 25,
    'margin_header' => 5,
    'margin_footer' => 0
]);
date_default_timezone_set('Asia/Manila');
$mpdf->setFooter(date("Y-m-d h:i A")."       Page {PAGENO} of {nb}");
$styles = "
			<style>
				@page{            
					/*margin-top: 4.35cm;*/
                    margin-top: 3.5cm;
                    /*odd-header-name: html_Header;*/
                    odd-footer-name: html_Footer;

				}
				@page :first {
					margin-top: 3.5cm;
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
					height: 100%;
                    
				}
				td{
					font-family: cursive;
				}
				.content{
                    height: 100%;
                    margin: 0 15px;
					
                }
				.footer-design{
                    background: url('".base_url()."/images/footer_bg.png') no-repeat;
                    background-size: cover;
                }
				
			</style>
";

// LOLA 11-22-2022
$COMPANY_CAMPUS = ($company_campus == "" ? "St. Therese - MTC Colleges INC." : $company_campus);
$SCHOOL_NAME = "St. Therese - MTC Colleges";
$REPORT_TITLE = $mtitle;
$DATERANGE = "CUTOFF : ".$this->extensions->getDTRCutoffConfig($cutoff_start, $cutoff_end);
// .date('F d', strtotime($cutoff_start))." - ".date('F d, Y', strtotime($cutoff_end));
$SIZE = true;
$SIZEFOOTER = false;

$header = "<body>".Globals::headerPdf("",strtoupper($REPORT_TITLE),$DATERANGE,$SIZE);
$control_no = $this->reports->getAcctngCtrlNo();
$content = '
			<br><br><br><br><br>
			<div class="content">
			<table id="maincontent" class="table" cellspacing="0" cellpadding="10" border="1" style="font-family: type new roman">
				<thead>
					<tr style="background-color: #4CAE25;color:white;">
						<td class="align_center"><b style="font-size: 12px;color:white;">Branch Code of Company</b></td>
						<td class="align_center"><b style="font-size: 12px;color:white;">Company 10 Digit Account Number</b></td>
						<td class="align_center"><b style="font-size: 12px;color:white;">Branch Code of Employee #</b></td>
						<td class="align_center"><b style="font-size: 12px;color:white;">Employee 10 Digit Account Number</b></td>
						<td class="align_center"><b style="font-size: 12px;color:white;">Amount</b></td>
						<td class="align_center"><b style="font-size: 12px;color:white;">Company Code</b></td>
						<td class="align_center"><b style="font-size: 12px;color:white;">Account Name</b></td>
					</tr>
				</thead>
				
				<tbody>';
						if($list){
							foreach ($list as $employeeid => $det) { 
							
							$content .= ' 
									
									<tr>
										<td style="text-align:right;">'.$det["bank_id"].'</td>
										<td style="text-align:right;">'.$det["account_number"].'</td>
										<td style="text-align:right;"></td>
										<td style="text-align:right;">'.$det["account_num"].'</td>
										<td>'.formatAmount($det["net_salary"]).'</td>
										<td>'.$det["comp_code"].'</td>
										<td>'.strtoupper(utf8_decode($det["fullname"])).'</td>
									</tr>
							
						'; 		
							}
						}
							
$content .= ' 					
				</tbody>
			
			</table>
';


$main = "
			".$styles."
			<div class='container'>
				".$header."
				".$content."
			</div>
</div>".Globals::footerWithCnPdf($control_no);

$main .="</body>";

// echo $main; die;

function formatAmount($amount=''){
    if($amount){
        $amount = number_format( $amount, 2 );
    }else{
        $amount = '0.00';
    }
    return $amount;
}
$main .= "
<table style='width: 100%;text-align: center;'>
    <tr>
        <td>
			<b style='font-size: 17px;padding-bottom: 55px;font-size: 16px;border-top: 2px solid #000;margin-top: 50px'>
				<span style='color: white;'>space</span>
				$first_person_name
				<span style='color: white;'>space</span>
			</b>
			<div>
				$first_person_position
			</div>
		</td>
        <td>
			<b style='font-size: 17px;padding-bottom: 55px;font-size: 16px;border-top: 2px solid #000;margin-top: 50px'>
				<span style='color: white;'>space</span>
				$second_person_name
				<span style='color: white;'>space</span>
			</b>
			<div>
				$second_person_position
			</div>
    </tr>
	 <tr>
	 	<td style='padding-top: 55px'></td>
	 	<td style='padding-top: 55px'></td>
    </tr>
</table>
";
// echo $main;
$mpdf->WriteHTML($main);
$mpdf->Output($path, "F");

?>