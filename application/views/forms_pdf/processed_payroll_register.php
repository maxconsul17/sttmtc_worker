<?php
/**
* @author RYSCHRISTIAN
* @copyright 2024
*/

set_time_limit(0);
ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);
ini_set('display_errors', 0);
ini_set("pcre.backtrack_limit", "500000000");

$pdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A3-L',
    'tempDir' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'mpdf',
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 30,
    'margin_header' => 5,
    'margin_footer' => 0
]);

$stylesheet = "
    <style>
        body {
            font-family: bahnschrift;
        }
        .content {
            height: 100%;
            margin: 0 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 16px;
        }
        table th, table td {
            border: 1px solid black;
            padding: 12px;
            text-align: center;
        }
        thead th {
            background-color: #4CAE25;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .align_center {
            text-align: center;
        }
        .align_left {
            text-align: left;
        }
        .align_right {
            text-align: right;
        }
        .bold {
            font-weight: bold;
        }
        .footer-design {
            background: url('images/footer_bg_lg.png') no-repeat;
            background-size: cover;
        }
    </style>
";

$pdf->WriteHTML($stylesheet);
// echo "<pre>";print_r($emplist);die;
$REPORT_TITLE = "Payroll Register Report"; 
$DATERANGE = "DATE: " .$this->extensions->getDTRCutoffConfig($sdate, $edate);

$header_content = "
<div style='text-align: center;'>
    <table width='100%' style='margin-bottom: 20px; border: none;'>
        <tr>
           <tr></tr>
            <td style='width: 20%; border: none;'>
                <img src='images/school_logo.png' width='100' height='100'>
            </td>
            <td style='width: 60%; text-align: center; border: none;'>
                <h1>ST. Therese - MTC Colleges</h1>
                <h4>{$REPORT_TITLE}</h4>
                <h5>{$DATERANGE}</h5>
            </td>
            <td style='border: none;'></td>
        </tr>
        <tr><td style='border: none;'></td></tr>
    </table>
</div>
";

$content = $header_content . "
<div class='content no-search'>
    <table>
        <thead>
            <tr>
                <th colspan='2' class='align_center'>Information</th>
                <th colspan='9' class='align_center'>Earnings</th>
                <th colspan='6' class='align_center'>Deductions</th>
                <th rowspan='2'>Net Pay</th>
                <th rowspan='2'>Finalized By</th>
                <th rowspan='2'>Finalized Date</th>
                <th rowspan='2'>HOLD</th>
            </tr>
            <tr>
                <th>Employee ID</th>
                <th style='width: 20vw;'>Full Name</th>
                <th>Regular Pay</th>
                <th>Tardy</th>
                <th>Absent</th>
                <th>Net Basic Pay</th>
                <th>Overtime</th>
                <th>Holiday</th>
                <th>Suspension</th>
                <th>Longevity</th>
                <th>Gross Salary</th>
                <th>WHT</th>
                <th>PAGIBIG</th>
                <th>PHILHEALTH</th>
                <th>SSS</th>
                <th>Cash Advance</th>
                <th>Facility Loan</th>
            </tr>
        </thead>
        <tbody>";

foreach ($emplist as $empid => $detail) {
    $content .= "
    <tr>
        <td>{$empid}</td>
        <td>{$detail['fullname']}</td>
        <td>" . number_format($detail['salary'], 2) . "</td>
        <td>" . number_format($detail['tardy'], 2) . "</td>
        <td>" . number_format($detail['absents'], 2) . "</td>
        <td>" . number_format($detail['netbasicpay'], 2) . "</td>
        <td>" . number_format($detail['overtime'], 2) . "</td>
        <td>" . number_format($detail['holiday_pay'], 2) . "</td>
        <td>" . number_format($detail['suspension_pay'], 2) . "</td>
        <td>" . number_format($detail['income'][1], 2) . "</td>
        <td>" . number_format($detail['grosspay'], 2) . "</td>
        <td>" . number_format($detail['whtax'], 2) . "</td>
        <td>" . number_format($detail['fixeddeduc']['PAGIBIG'], 2) . "</td>
        <td>" . number_format($detail['fixeddeduc']['PHILHEALTH'], 2) . "</td>
        <td>" . number_format($detail['fixeddeduc']['SSS'], 2) . "</td>
        <td>" . number_format($detail['deduction'][1], 2) . "</td>
        <td>" . number_format($detail['loan'][1], 2) . "</td>
        <td>" . number_format($detail['netpay'], 2) . "</td>
        <td>{$detail['finalized_by']}</td>
        <td>" . date('F d, Y', strtotime($detail['finalized_date'])) . "</td>
        <td>" . ($detail['isHold'] ? 'Yes' : 'No') . "</td>
    </tr>";
}

$total = $total; 

$content .= "
    <tr class='bold'>
        <td colspan='2'>Grand Total</td>
        <td>" . number_format($total['salary'], 2) . "</td>
        <td>" . number_format($total['tardy'], 2) . "</td>
        <td>" . number_format($total['absents'], 2) . "</td>
        <td>" . number_format($total['netbasicpay'], 2) . "</td>
        <td>" . number_format($total['overtime'], 2) . "</td>
        <td>" . number_format($holiday_total, 2) . "</td>
        <td>" . number_format($suspension_total, 2) . "</td>
        <td>" . number_format($total['income'][1], 2) . "</td>
        <td>" . number_format($total['grosspay'], 2) . "</td>
        <td>" . number_format($total['whtax'], 2) . "</td>
        <td>" . number_format($total['fixeddeduc']['PAGIBIG'], 2) . "</td>
        <td>" . number_format($total['fixeddeduc']['PHILHEALTH'], 2) . "</td>
        <td>" . number_format($total['fixeddeduc']['SSS'], 2) . "</td>
        <td>" . number_format($total['deduction'][1], 2) . "</td>
        <td>" . number_format($total['loan'][1], 2) . "</td>
        <td>" . number_format($total['netpay'], 2) . "</td>
        <td colspan='3'></td>
    </tr>
</tbody>
</table>
</div>
";
$pdf->WriteHTML($content);

$pdf->Output($path, "F");
?>
