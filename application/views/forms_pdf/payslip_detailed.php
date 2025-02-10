<?php
error_reporting(E_ALL & ~E_NOTICE);
set_time_limit(0);
ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);
ini_set("pcre.backtrack_limit", "500000000");


$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A3', 'tempDir' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'mpdf', 'margin_top' => '5', 'margin_bottom' => '5', 'margin_right' => '5', 'margin_left' => '5']);
$mpdf->simpleTables=true;
$mpdf->packTableData=true;

$style = "
    <style type='text/css'>
       .content-left{
            width: 100%;
            height: 30%;
        }
        .space{
            width: 100%;
            height: 1.5%;
        }
        .contenttext{
            margin-left: 2%;
            width: 95%;
            height: 24.5%;
            // border-right: 1px solid black;
            // border-style: dashed;            
            text-align: justify;
            text-justify: inter-word;
           /* font-size: 10px;*/

        }
        .fixed{
            table-layout:fixed; 
        }
        body{
            font-family: verdana;
            font-size: 10px;
        }
        hr{
            margin:0px;
        }
        hr{
            margin:0px;
            padding:0px;
        }
        td{
            margin:0;
            padding:0.5px;
        }
        .fixedcontainer{
            border:0px solid blue;
            height:405px;
            width:100%;
            position:absolute;
            left:35px;
            top:35px;

        }
        .fixedcontainer2{
            border:0px solid blue;
            height:440px;
            width:100%;
            position:absolute;
            left:35px;
            top:530px;

        }
        .spaceBetween{
            border:0px solid green;
            height:90px;
            width:100%;
            position:absolute;
            left:35px;
            top:440px;


        }
        .slipcontainer{
            border:1px solid black;
            display: inline-block;
            margin-top:10px;
            margin-left:25px;
            margin-right:25px;
            margin-bottom:10px;
            width:94%;
        }
        .containerleft{
            padding-top: 3px;
            border-right:1px solid black;
            width:50%;
            float:left;

        }
        .containerright{
            margin-top: 0px;
            width:auto;
            float:right;
        }
        .footer{
            border:1px solid blue;
        }
        .footerleft{
            border-top:1px solid black;
            border-right:1px solid black;
            width:50%;
            float:left;

        }
        .footerright{
            position:absolute;
            margin-top: 0px;
            border-top:1px solid black;
            width:auto;
            float:right;

        }
        .container{
            /*border: 1px solid black;*/
            margin-top: 5px;
            margin-left; 3%;
            width: 72%;
            height: 33%;
            float: left;
        }
        .header{
            text-align: center;
        }
        table{
            width: 100%;
        }
        td { 
            padding: 2px;
        }
        .tableheader{
            /*font-size: 15px;*/
            /*font-size: 8px;*/
            border-bottom-width: 1px;
            border-bottom-style: solid;
            border-bottom-color: #CDC1A7;
            border-top-width: 1px;
            border-top-style: solid;
            border-top-color: #CDC1A7;
            text-align: left;
        }
        .earnings{
            float: left;
            /*border: 1px solid black;*/
            width: 46.6%;
            height: 16%;
            text-align: right;
        }
        .deduction{
            float: left;
            /*border: 1px solid black;*/
            width: 52.6%;
            height: 16%;
            text-align: right;
        }
        .footer{
            margin-left: 1%;
            width: 99%;
            height: 5%;   
            /*border: 1px solid black;*/
        }
        .footer .text{
            margin-top: 2%;
            font-weight: bold;
        }
        .edtbl{
            width: 100%;
        }
        .eddesc{
            text-align: left;
        }
        .edamt{

            text-align: right;
        }
        .floatright{
        	text-align:right;float:right;
        }
    </style>
</head>
<body>";


$mpdf->WriteHTML($style);

// $mpdf->SetWatermarkImage($imgurl."images/school_logo.jpg", 
//     0.2, 
//     array(80,80), 
//     array(55,20));


// $mpdf->showWatermarkImage = true;


$html = "";
/*$eid        = $_GET['eid']; 
$dept       = $_GET['dept'];
$dfrom      = $_GET['dfrom'];
$dto        = $_GET['dto'];
$schedule   = $_GET['schedule'];
$quarter    = $_GET['quarter']; 
$sort       = $_GET['sort'];
$bank       = $_GET['bank'];*/
$cutoffdate = date('F d',strtotime($sdate)).' -  '.date('F d, Y',strtotime($edate));
$payrollGroup = (count($emplist) > 1) ? "ALL EMPLOYEE" : "INDIVIDUAL" ;

function getEmpDesc($eid){
    $return = "";
    $query = mysql_query("SELECT b.description FROM employee a INNER JOIN code_office b ON a.deptid = b.code WHERE a.employeeid='$eid'");
    $data = mysql_fetch_array($query);
    $return = $data['description'];
    return $return; 
}

$slipcount = count($emplist);
$counter = 0;
foreach ($emplist as $empid => $empinfo) {
    $perdept_salary = $this->payrollcomputation->getPerdeptSalaryHistory($empid,$sdate);

    $t_holiday = 0;
    $days_absent = $totallate = $totalundertime = 0;
    $tnt = $this->extensions->getEmployeeTeachingType($empid);
    if($tnt=="nonteaching" && !$this->employee->isTeachingRelated($empid)){
        list($tardy, $absent) = $this->attendance->employeeAbsentTardy($empid, $sdate, $edate);
        if($tardy) $empinfo["teaching_tardy"] = $tardy;
        if($absent) $empinfo["workhours_absent"] = $absent;
    }

    $workhours_deduc = $workhours_late = 0;
    if($empinfo["perdept_amt"]){
        foreach($empinfo["perdept_amt"] as $aimsdept => $perdept){
            foreach($perdept as $type => $row){
                $workhours_deduc += $this->attcompute->exp_time($row["deduc_hours"]);
                $workhours_late += $this->attcompute->exp_time($row["late_hours"]);
            }
        }
    }

    if($tnt=="teaching"){
        $empinfo["workhours_absent"] = $this->attcompute->sec_to_hm($workhours_deduc);
        $empinfo["teaching_tardy"] = $this->attcompute->sec_to_hm($workhours_late);
    }

	$counter ++;
    $otTotal = 0;

    $tag = "";
    if($tnt=="nonteaching") $tag = "Office";
    else $tag = "Department";

    foreach($empinfo["overtime_detailed"] as $ot_type => $ot_row){
        foreach($ot_row as $ot_hol => $ot_det){
            $ot_hours = $this->attcompute->sec_to_hm($ot_det["ot_hours"]);
            $otTotal = $otTotal+$ot_det["ot_hours"];
        }
    }
    list($cutoffstart, $cutoffend) = $this->extensions->getDTRCutoffConfigPayslip($dfrom, $dto);
    $absent_count = $this->db->query("SELECT absents FROM employee_attendance_detailed WHERE sched_date BETWEEN '$cutoffstart' AND '$cutoffend' AND employeeid = '$empid' AND absents != '' ")->num_rows();

    $late_tmp = 0;
    $late_list = $this->db->query("SELECT late FROM employee_attendance_detailed WHERE sched_date BETWEEN '$cutoffstart' AND '$cutoffend' AND employeeid = '$empid' AND late != '' ");
    if($late_list->num_rows() > 0){
        foreach($late_list->result() as $det){
            $late_tmp += $this->attcompute->exp_time($det->late);
        }
    }

    $totallate = $this->attcompute->sec_to_hm($late_tmp);

$deptDesc = $this->extras->getDeptDesc($empinfo['deptid']);
$officeDesc = $this->extras->getOfficeDesc($empinfo['office']);
$dept_off = $deptDesc."/".$officeDesc;


if(!$deptDesc || !$officeDesc) $dept_off = str_replace("/", "", $dept_off);
$fullnameLength = strlen($empinfo["fullname"]);
    ($fullnameLength > 26) ? $fullname = "<span style='font-size:8px;'>".$empinfo["fullname"]."</span>" : $fullname = $empinfo["fullname"] ; 
$html .= '
<div class="fixedcontainer"></div>
<div class="fixedcontainer2"></div>
<div class="spaceBetween"></div>';

$html .= '
    <table  border="1"style="display: inline-block;margin-top:10px;margin-left:25px;margin-right:25px;margin-bottom:10px;width:98.6%;font-size:120%;">
        <tr>
            <td style="width:33%;">
                <span>'.date("m/d/Y",time()).'</span>
            </td>
            <td style="width:33%;">
                <center>Employee Payslip</center>
            </td>
            <td></td>
        </tr>
    </table>';
// NOTE REMOVE '.$company_campus.' in employee payslip


// HEADER

$html .= '
<table class="fixed" style="display: inline-block;margin-top:10px;margin-left:25px;margin-right:25px;margin-bottom:10px;width:98.6%;">
    <tr>
        <th><img src="images/school_logo.jpg" style="width: 11%;text-align: center;" /></th>
        <th style="text-align:center;font-family:Book Antiqua;">
           <h2>St. Therese - MTC Colleges INC.</h1>
        </th> 
        <th style="text-align: right;width:55%;"><h1><p>P A Y &nbsp; S L I P</p></h1></th>
    <tr>
        <th></th>
        <th></th>
        <th style="text-align: right; color: gray;"><h3>STRICTLY CONFIDENTIAL</h3></th>
    </tr>
</table>  
';


      $html .='
<div class="slipcontainer">
	<div class="containerleft">
        <div class="contenttext">
        <table style="border-collapse:collapse;width:100%";>
            <!-- <tr>
                <td>Basic Pay :</td>
                <td style="text-align:right">'.number_format($empinfo["teaching_pay"],2).'</td>
            </tr> -->
           
            <tr>
                <td>Basic Pay :</td>
                <td style="text-align:right">'.number_format($empinfo["salary"],2).'</td>
            </tr>
            <tr>
                <td>Holiday Pay :</td>
                <td style="text-align:right">'.number_format($empinfo["holiday_pay"],2).'</td>
            </tr>
            <tr>
                <td>Suspension Pay :</td>
                <td style="text-align:right">'.number_format($empinfo["suspension_pay"],2).'</td>
            </tr>
            <!--<tr>
                <td>Overtime(s): &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.$this->attcompute->sec_to_hm($otTotal).'  </td>
                <!--<td>Overtime(s): &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 0:00 X 0.00 (Rate/hour)</td>-->
                <!--<td style="text-align:right">'.number_format($empinfo["overtime"],2).'</td>-->
            <!--</tr>-->
            <!--<tr>
                <td colspan="2" style="text-align:center">---------------------------------------- Overtime Breakdown ----------------------------------------</td>
            </tr>-->
            ';
            foreach($empinfo["overtime_detailed"] as $ot_type => $ot_row){
                foreach($ot_row as $ot_hol => $ot_det){
                    $ot_hours = $this->attcompute->sec_to_hm($ot_det["ot_hours"]);
                    if($ot_hol != "NONE") $ot_hol = $ot_hol." ". "Holiday";
                    else $ot_hol = "";
                    $html.= '
                    <!--<tr>
                            <td style="padding-left: 50px;">'.ucwords(strtolower(str_replace("_", " ", $ot_type))).': ' .$ot_hol. '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.$ot_hours.'</td>
                            <td style="text-align:right; padding-right: 50px;">'.number_format($ot_det["ot_amount"], 2).'</td>
                        </tr>-->
                    ';
                }
            }
            $html.='
            <!-- <tr>
                <td style="padding-left: 50px;">Night Differential: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 0:00 X 0.00 (Rate/hour)</td>
                <td style="text-align:right;  padding-right: 50px;">'."0.00s".'</td>
            </tr> -->
            <tr>
                <td>Other Taxable Income :</td>
                <td style="text-align:right">'.number_format($empinfo["totalIncomeTaxable"],2).'</td>
            </tr>
            <tr>
                <td>Other Non-Taxable Income :</td>
                <td style="text-align:right">'.number_format($empinfo["totalIncomeNonTaxable"],2).'</td>
            </tr>
            
            <tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td>LESS : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Total :</td>
                <td style="text-align:right">'.number_format($empinfo["semitotalPay"],2).'</td>
            </tr>
            <tr><td>&nbsp;</td></tr>
             <tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td>Absenteeism:</td>
                <td style="text-align:right">'.($absent_count > 1? $absent_count.' Days' : $absent_count.' Day').'</td>
            </tr>
             <tr>
                <td>Tardiness(hr:mm): </td>
                <td style="text-align:right">'.$totallate.'</td>
            </tr>
             <tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td>Absent:  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.$empinfo["workhours_absent"].' (hr:min) &nbsp;&nbsp;</td>
                <td style="text-align:right">'.number_format($empinfo["absents"],2).'</td>
            </tr>
            <!-- <tr>
                <td>Late / UT: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.$empinfo["teaching_tardy"].' (hr:min) &nbsp;&nbsp;</td>
                <td style="text-align:right">'.number_format($empinfo["tardy"],2).'</td>
            </tr> -->
            <tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td class="floatright">  GROSS PAY:  </td>
                <td style="text-align:right">'.number_format($empinfo["grosspay"],2).'</td>
            </tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>';

    $contributions = array("PAGIBIG","PHILHEALTH","SSS");
                for($i=0;$i < count($contributions);$i++){
                    if(array_key_exists($contributions[$i],$empinfo["fixeddeduc"])){
                        if($contributions[$i] == "PAGIBIG"){
                            $html .= "<tr>
                            <td> <p>Less Contributions: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ".$contributions[$i].":</p></td>
                            <td class='edamt'>".number_format(floatval($empinfo["fixeddeduc"][$contributions[$i]]),2)."</td>
                        </tr>                        
                       ";
                        }else{
                            $html .= "<tr>
                            <td class='eddesc'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$contributions[$i].":</td>
                            <td class='edamt'>".number_format(floatval($empinfo["fixeddeduc"][$contributions[$i]]),2)."</td>
                        </tr>    ";
                        }

                    }else{
                        if($contributions[$i] == "PAGIBIG"){
                            $html .= "<tr>
                                    <td> <p>Less Contributions: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ".$contributions[$i].":</p></td>
                                    <td class='edamt'>0.00</td>
                                </tr>  
                                ";
                            }else{
                                $html .= "<tr>
                                    <td class='eddesc'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$contributions[$i].":</td>
                                    <td class='edamt'>0.00</td>
                                    </tr>   
                                ";
                            }
                    }

                }          
$html .=    '
            <tr>
                 <td><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Union Dues: 
                 </p>
                 </td>
                 <td style="text-align:right">0.00</td>
            </tr>

            <tr>
                 <td><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Health Insurance:
                 </p>
                 </td>
                 <td style="text-align:right">0.00</td>
            </tr>

            <tr>
                 <td><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Tax Withheld:
                 </p>
                 </td>
                 <td style="text-align:right">'.number_format($empinfo["whtax"],2).'</td>
            </tr>
            <tr>
                 <td><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Other Deduction:
                 </p>
                </td>
                <td class = "edamt">'.number_format($empinfo["totalOtherDeduc"] ,2).'</td>
            </tr>

        </table>
        </div>
    </div>
    <div class="containerright">
    	<div class="contenttext">
        	<table style="width:100%"; >
        	 <tr height="100px;">
                <td ><b>Other Non-Txbl Income</b></td>
            </tr>';
foreach ($empinfo["income"] as $incomeKey => $incomeVal) { 
			if($income_config[$incomeKey]['description'] == "notax"){
		            $html .= '<tr>
					            <td class="eddesc">'.$income_config_desc[$incomeKey]["description"].'</td>
					            <td class="edamt">'.number_format($incomeVal,2).'</td>
		            		</tr>';
		            	}  
            }               
$html .= '<tr>
            <br><br><br>
                <br><br>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td ><b>Other Taxable Income</b></td>
            </tr>';
foreach ($empinfo["income"] as $incomeKey => $incomeVal) 
			{ 
				if($income_config[$incomeKey]['description'] == "withtax")
				{
	            $html .= '<tr>
				            <td class="eddesc">'.$income_config_desc[$incomeKey]["description"].'</td>
				            <td class="edamt">'.number_format($incomeVal,2).'</td>
	            		</tr>';
			    }  
            }   

$html .= '<tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td ><b>Other Deduction</b></td>
            </tr>';
foreach ($empinfo["deduction"] as $deducKey => $deducVal) 
			{ 
			if($deduction_config[$deducKey]['description'] == "sub")
				{
	            $html .= '<tr>
				            <td class="eddesc">'.$deduction_config_desc[$deducKey]["description"].'</td>
				            <td class="edamt">'.number_format($deducVal,2).'</td>
	            		</tr>';
	            }else{
	            	$html .= '<tr>
					            <td class="eddesc">'.$deduction_config_desc[$deducKey]["description"].'</td>
					            <td class="edamt">('.number_format($deducVal,2).')</td>
		            		</tr>';
	            }
            }
foreach ($empinfo["loan"] as $loanKey => $loanVal) 
            { 
            
                $html .= '<tr>
                            <td class="eddesc">'.$loan_config[$loanKey]["description"].'</td>
                            <td class="edamt">'.number_format($loanVal,2).'</td>
                        </tr>';
            
            }              

$html .='<tr>
                <td colspan="2"><hr width="100%" height="1px"></td>
            </tr>
            <tr>
                <td class = "eddesc"> Total: </td>
                <td class = "edamt">'.number_format($empinfo["totalOtherDeduc"] ,2).'</td>

            </tr>

        	</table>
        	<br>';
    
    // OLD CONDITION
    /*if($tnt=="teaching" || $this->employee->isTeachingRelated($empid)){*/
    if(false){
        $html .='<tr>
                	<table style="width:100%"; >
                    <tr>
                        <td colspan="4"><b>Other Teacher'."'".'s Load</b></td>
                    </tr>
                    <tr>
                        <td width="15%"><b>Dept</b></td>
                        <td><b>Hr</b></td>
                        <td><b>Rate/HR</b></td>
                        <td><b>LEC</b></td>
                        <td><b>LAB</b></td>
                    </tr>
                    ';
                    if(sizeof($empinfo['perdept_amt']) > 0){
                        foreach ($empinfo['perdept_amt'] as $aimsdept => $d_list) {
                            $lec_work_hours = $lec_work_amount = $lab_work_hours = $lab_work_amount = 0;
                            foreach($d_list as $type => $leclab){
                                if($type == "LEC"){
                                    $lec_work_hours = $d_list["LEC"]["work_hours"];
                                    $lec_work_amount = $d_list["LEC"]["work_amount"];
                                }else{
                                    $lab_work_hours = $d_list["LAB"]["work_hours"];
                                    $lab_work_amount = $d_list["LAB"]["work_amount"];
                                }
                            }

                            $html .= '

                                <tr>
                                    <td>'.$this->extensions->getCourseDescriptionByCode($aimsdept).'</td>
                                    <td>'.(number_format($lec_work_hours+$lab_work_hours, 2)).'</td>
                                    <td>LEC - '.$perdept_salary[$aimsdept]["lechour"].'| LAB - '.$perdept_salary[$aimsdept]["labhour"].'</td>
                                    <td>'.number_format($lec_work_amount,2).'</td>
                                    <td>'.number_format($lab_work_amount,2).'</td>
                                </tr>


                            ';

                            $empinfo["total_leclab_pay"] += ($lec_work_amount + $lab_work_amount);
                        }

                    }else{
                        $html .= '
                            <tr>
                                <td>LEC PAY</td>
                                <td></td>
                                <td>0</td>
                                <td>0.00</td>
                                <td class="edamt">0.00</td>
                            </tr>

                            <tr>
                                <td>LAB PAY</td>
                                <td></td>
                                <td>0</td>
                                <td>0.00</td>
                                <td class="edamt">0.00</td>
                            </tr>
                        ';
                    }




        $html .='   <tr>
                        <td colspan="4"><hr width="100%" height="1px"></td>
                        <td><hr width="100%" height="1px"></td>
                    </tr>
                    <tr>
                        <td colspan="4">Total:   </td>
                        <td class="edamt">'.number_format($empinfo["total_leclab_pay"],2).'</td>
                    </tr>
                    </table>';
    }

$html .='
        </div>
    </div>

    <div class="footerleft">
        <table>
            <tr>
                <td colspan="2" style="text-align:right;"><b>NET PAY:&nbsp;&nbsp;&nbsp; </b>'.number_format($empinfo["netpay"],2).'</td>
            </tr>
        </table>
    </div>
    <div class="footerright">
        <table>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td><b>Signature: </b></td>
                <td><b>REMARKS: </b></td>
            </tr>
        </table>
    </div>
</div>
<table style=" border:1px solid black;display: inline-block;margin-top:10px;margin-left:25px;margin-right:25px;margin-bottom:10px;width:98.6%;">
    <tr>
        <td style="padding:0px;border:1px solid black;">
            <table>
                <tr>
                <td style="width:200px;padding:0px;"><b>ID NO</b></td>
                <td style="width:200px;padding:0px;"><b>Name</b></td>
                <td style="width:200px;padding:0px;"><b>'.$tag.'</b></td>
                <td style="width:200px;padding:0px;"><b>Bank Name</b></td>
                <td style="width:200px;padding:0px;"><b>Pay Date</b></td>
                <td style="width:200px;padding:0px;"><b>Cut-Off Date</b></td>
                
                </tr>
                <tr>
                <td style="padding:0px;">'.$empid.'</td>
                <td style="padding:0px;">'.$fullname.'</td>
                <td style="padding:0px;">'.$dept_off.'</td>
                <td style="padding:0px;">'.$this->extensions->getBankName($empinfo['bank']).'</td>
                <td style="padding:0px;">'.$cutoffdate.'</td>
                <td style="padding:0px;">'. $this->extensions->getDTRCutoffConfig($sdate, $edate) .'</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
';

// # YOU CAN LOOP OTHER DATAS HERE
# FOOTER
$control_no = $this->reports->getAcctngCtrlNo();
$html.='
    <table style="border-bottom:1px dashed black;margin:0px 0px 20px 0px;"> 
        <tr>
            <td align="center" colspan=2 style="padding:0px 0px 0px 0px;"><b>Note : </b>This is a system-generated pay slip and does not require a signature. For any discrepancies, kindly reach out to the Accounting Department.</td>
        </tr>
         <tr>
            <td align="right" colspan=2 style="padding:0px 0px 0px 0px;"> <i>'.$control_no.'</i></td>
        </tr>
    </table>
';


	if($counter == 2){
        $html .= "<pagebreak></pagebreak>";    
        $counter = 0;    
    }

    
} /// end main loop
// die;
// echo $html."</body";
// echo $html;die;
$mpdf->WriteHTML($html);
$mpdf->WriteHTML("</body>");


$mpdf->Output($path, "F");

/*ibabalik din po ito agad pakiremind si max thank you*/

/*<tr>
    <td>Paid Holiday: &nbsp;&nbsp;&nbsp; '.$t_holiday.'.00 (Days) X '.number_format(($t_holiday * $empinfo["daily"]),2).' (Rate/Day) </td>
    <td style="text-align:right">'."0.00".'</td>
</tr>*/