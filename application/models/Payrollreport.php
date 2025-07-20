<?php 
/**
 * @author Angelica Arangco
 * @copyright 2017
 *
 * This model is an extension to models\payroll.php
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Payrollreport extends CI_Model {

	//by NACES
	function getPayslipSummary($emplist=array(), $sdate='',$edate='',$schedule='',$quarter='',$bank='',$status='PROCESSED',$isSub = false){

		$this->load->model("payrollprocess");


		//< initialize needed info ---------------------------------------------------
		$arr_info    = $arr_income_config = $arr_incomeoth_config = $arr_deduc_config = $arr_fixeddeduc_config = $arr_loan_config = array();

		///< ------------------------------ income config ------------------------------------------------------------
		$income_config_q = $this->payroll->displayIncome();
		$arr_income_config = $this->payrollprocess->constructArrayListFromStdClass($income_config_q,'id','taxable');
		$arr_income_config_desc = $this->payrollprocess->constructArrayListFromStdClass($income_config_q,'id','description');


		///< ------------------------------ incomeoth config ---------------------------------------------------------------
		$incomeoth_config_q = $this->payroll->displayIncomeOth();
		$arr_incomeoth_config = $this->payrollprocess->constructArrayListFromStdClass($incomeoth_config_q,'id','description');

		///< ------------------------------ fixed deduction config ----------------------------------------------------
		$fixeddeduc_config_q = $this->db->query("SELECT code_deduction,description FROM deductions");
		$arr_fixeddeduc_config = $this->payrollprocess->constructArrayListFromStdClass($fixeddeduc_config_q,'code_deduction','description');


		///< ------------------------------ deduction config ----------------------------------------------------------
		$deduction_config_q = $this->payroll->displayDeduction();
		$arr_deduc_config = $this->payrollprocess->constructArrayListFromStdClass($deduction_config_q,'id','arithmetic');
		$arr_deduc_config_desc = $this->payrollprocess->constructArrayListFromStdClass($deduction_config_q,'id','description');

		///< ------------------------------ loan config ---------------------------------------------------------------
		$loan_config_q = $this->payroll->displayLoan();
		$arr_loan_config = $this->payrollprocess->constructArrayListFromStdClass($loan_config_q,'id','description');

		foreach ($emplist as $row) {
			//< $emplist as row database table ["payroll_employee_salary"]
			$empid = $row["employeeid"];
			$deptcode = $row["deptid"];
			$teachingtype = $row["teachingtype"];
			

			///< check for computation
			$res = $this->payrollprocess->getPayrollSummary($status,$sdate,$edate,$schedule,$quarter,$empid,FALSE,$bank);

			if($isSub)
			{
				$res = $this->payrollprocess->getPayrollSummarySub($status,$sdate,$edate,$schedule,$quarter,$empid,FALSE,$bank);
			}




			if($res->num_rows() > 0){

				$regpay =  $row["regpay"];
				$dependents = $row["dependents"];

				$arr_info[$empid]['income'] = $arr_info[$empid]['deduction'] = $arr_info[$empid]['fixeddeduc'] = $arr_info[$empid]['loan'] = array();

				$arr_info[$empid]['fullname'] 	= $row["fullname"];
				$arr_info[$empid]['deptid'] 	= $row["deptid"];
				$arr_info[$empid]['office'] 	= $row["office"];
				$arr_info[$empid]['bank'] 		= $row["bank"];


				// $arr_info[$empid]['salary'] 	= $regpay;

				

				//< rates
				$hourly = $arr_info[$empid]['hourly'] = $row["hourly"];
				$daily = $arr_info[$empid]['daily'] = $row["daily"];

				$res 							= $res->row(0);

				$arr_info[$empid]['salary'] = $res->salary;
				$arr_info[$empid]['teaching_pay'] = $res->teaching_pay;

				$arr_info[$empid]['overtime'] 	= $res->overtime;
				$tardy = $arr_info[$empid]['tardy'] 		= $res->tardy;
				$arr_info[$empid]['absents'] 	= $res->absents;
				$arr_info[$empid]['whtax'] 		= $res->withholdingtax;
				$arr_info[$empid]['editedby'] 	= $res->editedby;
				$arr_info[$empid]['netbasicpay'] = $res->netbasicpay;
				$arr_info[$empid]['grosspay'] 	= $res->gross;
				$arr_info[$empid]['netpay'] 	= $res->net;

				//teachers load
				$lechour = $arr_info[$empid]['lechour'] = $row->lechour ?? null;
				$labhour = $arr_info[$empid]['labhour'] = $row->labhour ?? null;
				list($arr_info[$empid]["total_leclab_pay"],
					 $arr_info[$empid]["workhours_lec"],$arr_info[$empid]["total_lec_pay"],
					 $arr_info[$empid]["workhours_lab"],$arr_info[$empid]["total_lab_pay"],
					 $arr_info[$empid]["absenthourly"],$arr_info[$empid]["latehourly"],$arr_info[$empid]["overtimeHour"]) = $this->displayAbsentLateUndertime($empid,$quarter,$sdate,$edate,$teachingtype,$hourly,$lechour,$labhour,$tardy);


				//< income
				$overtime_detailed = $this->attcompute->payslipOvertimeDetailed($empid, $sdate, $edate);
				$arr_info[$empid]["overtime_detailed"] = $overtime_detailed;
				$income_arr 				= $this->payrollprocess->constructArrayListFromComputedTable($res->income);
				$arr_info[$empid]['income'] = $income_arr;
				$totalIncome = 0;
				$totalIncomeNonTaxable = 0;
				$totalIncomeTaxable = 0;

				foreach ($income_arr as $k => $v) {
					if($arr_income_config[$k]['description'] == "withtax"){
							$totalIncomeTaxable += $v;
					}else{
						  $totalIncomeNonTaxable += $v;
					}
					$totalIncome += $v;
				}

				$arr_info[$empid]["perdept_amt"] = $this->getPerdeptDetail($empid, $sdate, $edate, $teachingtype);

				$arr_info[$empid]["totalIncome"] = $totalIncome;
				$arr_info[$empid]["totalIncomeTaxable"] = $totalIncomeTaxable;
				$arr_info[$empid]["totalIncomeNonTaxable"] = $totalIncomeNonTaxable;

				///< fixed deduc
		        $fixeddeduc_arr = $this->payrollprocess->constructArrayListFromComputedTable($res->fixeddeduc);
		        $arr_info[$empid]['fixeddeduc'] = $fixeddeduc_arr;
		        foreach ($fixeddeduc_arr as $k => $v) {$arr_fixeddeduc_config[$k]['hasData'] = 1;}

		        ///< deduc
		        $deduc_arr = $this->payrollprocess->constructArrayListFromComputedTable($res->otherdeduc);
		        $arr_info[$empid]['deduction'] = $deduc_arr;

		        $totalOtherDeducSub = $totalOtherDeducAdd = 0;
		        foreach ($deduc_arr as $k => $v) {
		        	if($arr_deduc_config[$k]['description'] == "sub"){
		        		$totalOtherDeducSub += $v;
		        	}else{
		        		$totalOtherDeducAdd += $v;
		        	}

		    	}
		    	
		    	$total_loan = 0;
		        ///< loan
		        $loan_arr = $this->payrollprocess->constructArrayListFromComputedTable($res->loan);
		        $arr_info[$empid]['loan'] = $loan_arr;
		        foreach ($loan_arr as $k => $v) {
		        	$total_loan += $v;
		        }

		        //totals
		        $arr_info[$empid]["totalOtherDeduc"] = $totalOtherDeducSub - $totalOtherDeducAdd + $total_loan;
		        $arr_info[$empid]["semitotalPay"] = $arr_info[$empid]['salary'] + $arr_info[$empid]['overtime'] + $arr_info[$empid]["totalIncome"];

			}

			// $data[] = array(
			// 	'emplist' => $arr_info, 
			// 	'income_config' => $arr_income_config,
			// 	'income_config_desc' => $arr_income_config_desc,
			// 	'incomeoth_config' => $arr_incomeoth_config,
			// 	'fixeddeduc_config' => $arr_fixeddeduc_config,
			// 	'deduction_config' => $arr_deduc_config,
			// 	'deduction_config_desc' => $arr_deduc_config_desc,
			// 	'loan_config' => $arr_loan_config,
			// 	'sdate' => $sdate,
			// 	'edate' => $edate
			// );

		} //end loop emplist
		
		$data['emplist'] = $arr_info;
		$data['income_config'] = $arr_income_config;
		$data['income_config_desc'] = $arr_income_config_desc;
		$data['incomeoth_config'] = $arr_incomeoth_config;
		$data['fixeddeduc_config'] = $arr_fixeddeduc_config;
		$data['deduction_config'] = $arr_deduc_config;
		$data['deduction_config_desc'] = $arr_deduc_config_desc;
		$data['loan_config'] = $arr_loan_config;
		$data['sdate'] = $sdate;
		$data['edate'] = $edate;

		return $data;
	}

	function displayAbsentLateUndertime($eid,$quarter,$dfrom,$dto,$teachingtype,$hourly,$lechour,$labhour,$tardy){
    $return = "";
    //check the employee if teaching or non-teaching
    $this->load->model("time");

    if($teachingtype == "teaching"){
    $query = $this->db->query(" SELECT a.overload,a.workhours_lec,a.workhours_lab,a.timestamp,a.status,a.payroll_cutoffstart,a.payroll_cutoffend,a.deduclec,a.deduclab,a.deducadmin,a.latelec,a.latelab,a.lateadmin
        FROM attendance_confirmed a 
        WHERE a.employeeid = '$eid' AND a.payroll_cutoffstart = '$dfrom' AND a.payroll_cutoffend = '$dto' AND a.quarter = '$quarter' AND a.status = 'SUBMITTED' ")->result_array();

 	$absences = $lates = $overtimes = $lecinMinutes = $labinMinutes = 0;

    foreach ($query as $row) {

    	$absences += $this->time->hoursToMinutes($row['deduclec']) + $this->time->hoursToMinutes($row['deduclab']) + $this->time->hoursToMinutes($row['deducadmin']);
        $lates += $this->time->hoursToMinutes($row['latelec']) + $this->time->hoursToMinutes($row['latelab']) + $this->time->hoursToMinutes($row['lateadmin']);
        ($row['workhours_lec'] != null || $row['workhours_lec'] != "") ? $lecinMinutes += $this->time->hoursToMinutes($row['workhours_lec']) : $lecinMinutes += 0;
        ($row['workhours_lab'] != null || $row['workhours_lec'] != "") ? $labinMinutes += $this->time->hoursToMinutes($row['workhours_lab']) :  $labinMinutes += 0;
        ($row['overload'] != null || $row['overload'] != "") ? $overtimes += $this->time->hoursToMinutes($row['overload']) : $overtimes += 0;
    }
    $lecinHour = $lecinMinutes / 60;
    $labinHour = $labinMinutes / 60;



    $absent = $this->time->minutesToHours($absences)." (h:m)";
    $late = $this->time->minutesToHours($lates)." (h:m)";
    $overtime = $this->time->minutesToHours($overtimes)." (h:m)";
    $total_workhours_lec = $this->time->minutesToHours($lecinMinutes);
    $total_workhours_lec_pay = $lechour * $lecinHour;
    $total_workhours_lab = $this->time->minutesToHours($labinMinutes);
    $total_workhours_lab_pay = $labhour * $labinHour;
    $total_leclab_pay = $total_workhours_lec_pay + $total_workhours_lab_pay;

    }else{

    $query = $this->db->query("SELECT a.status,a.payroll_cutoffstart,a.payroll_cutoffend,a.absent,a.lateut,a.otreg,a.otsat,a.otsun,a.othol
        FROM attendance_confirmed_nt a 
        WHERE a.employeeid = '$eid' AND a.payroll_cutoffstart = '$dfrom' AND a.payroll_cutoffend = '$dto' AND a.quarter = '$quarter' AND a.status = 'SUBMITTED' ")->result_array();	
    $absences = $lates = $overtimes = 0;
    foreach ($query as $row) {

    	$absences += $this->time->hoursToMinutes($row['absent']);
        $lates += $this->time->hoursToMinutes($row['lateut']);
        $overtimes += $this->time->hoursToMinutes($row['otreg']) + $this->time->hoursToMinutes($row['otsat']) + $this->time->hoursToMinutes($row['otsun']) + $this->time->hoursToMinutes($row['othol']);
    }

     $absent = $absences / 60 / 8;
     $late = $this->time->minutesToHours($lates)." (h:m)";
     ($absent > 1) ? $absent = $absent." (Days)" : $absent= $absent." (Day)";
     $overtime = $this->time->minutesToHours($overtimes)." (h:m)";   
     // $dailyRate = ($daily)." (Rate/day)";
     // $hourlyRate = ($hourly)." (Rate/hour)";
     $total_workhours_lec = 0;
     $total_workhours_lec_pay = 0;
     $total_workhours_lab = 0;
     $total_workhours_lab_pay = 0;
     $total_leclab_pay = 0;
    }

    return array($total_leclab_pay,$total_workhours_lec,$total_workhours_lec_pay,$total_workhours_lab,$total_workhours_lab_pay,$absent,$late,$overtime);     


	}

	///< @Angelica net pay history per cutoff per department from computed table
	function getNetPayHistory($month='',$year='',$status='', $campus='',$company_campus='', $sortby='', $employeeid=''){
		$data['list'] = $data['cutofflist'] = array();
		$wC = "WHERE `status`='$status'";
		$oD = "ORDER BY cutoffstart";
		if($campus && $campus != 'All' && $campus != 'null') $wC .= " AND b.campusid =  '$campus'";
		if($employeeid) $wC .= " AND b.employeeid IN ($employeeid)";
		if($company_campus) $wC .= " AND b.company_campus =  '$company_campus'";
		if($sortby == "department"){
			$oD .= ',b.office, b.lname';
		}else{
			$oD .= ',b.lname';
		}

		$utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus && $usercampus != "null") $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }
        $wC .= $utwc;
		$res = $this->db->query("SELECT a.employeeid, cutoffstart, cutoffend, net FROM payroll_computed_table a LEFT JOIN employee b ON a.employeeid = b.employeeid $wC AND DATE_FORMAT(cutoffstart, '%m') <= '$month' AND DATE_FORMAT(cutoffstart, '%Y') = '$year' 
			$oD");
		foreach ($res->result() as $key => $row) {
			$deptid = $this->employee->getempdatacol('deptid',$row->employeeid);

			$cutoff = $row->cutoffstart . '|' . $row->cutoffend;

			if(!in_array($cutoff, $data['cutofflist'])) array_push($data['cutofflist'], $cutoff);

			if(!isset($data['list'][$deptid][$cutoff])) $data['list'][$deptid][$cutoff] = 0;
			$data['list'][$deptid][$cutoff] += $row->net;
		}
		return $data;
	}

	function getNetPayHistory_new($month='',$year='',$status=''){
		$data['list'] = $data['cutofflist'] = array();

		$res = $this->db->query("SELECT employeeid, cutoffstart, cutoffend, net FROM payroll_computed_table WHERE `status`='$status' AND DATE_FORMAT(cutoffstart, '%m') <= '$month' AND DATE_FORMAT(cutoffstart, '%Y') = '$year' 
			ORDER BY cutoffstart");
		foreach ($res->result() as $key => $row) {
			$deptid = $this->employee->getempdatacol('deptid',$row->employeeid);

			$cutoff = $row->cutoffstart . '|' . $row->cutoffend;

			if(!in_array($cutoff, $data['cutofflist'])) array_push($data['cutofflist'], $cutoff);

			if(!isset($data['list'][$deptid][$cutoff])) $data['list'][$deptid][$cutoff] = 0;
			$data['list'][$deptid][$cutoff] += $row->net;
		}
		return $data;
	}

	function getNetPayHistoryNew($month='',$year='',$status='', $campus='',$company_campus='', $sortby='', $employeeid='', $tnt=''){
		$wC = " AND `status`='$status'";
		$oD = "ORDER BY cutoffstart";
		if($campus && $campus != 'All' && $campus != 'null') $wC .= " AND b.campusid =  '$campus'";
		if($employeeid) $wC .= " AND b.employeeid IN ($employeeid)";
		if($company_campus) $wC .= ' AND b.company_campus =  "'.$company_campus.'"';
		if($tnt && $tnt != 'undefined'){
			if($tnt != "trelated") $wC .= " AND b.teachingtype = '$tnt' AND b.trelated = '0' ";
      		else $wC .= " AND b.teachingtype='teaching' AND b.trelated = '1'";
		}
		if($sortby == "department"){
			$oD .= ',b.deptid, b.lname';
		}else{
			$oD .= ',b.lname';
		}

		$utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus && $usercampus != "null") $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }

		if($month != '00'){
			$wC .= " AND DATE_FORMAT(cutoffstart, '%m') = '$month'";
		}

        $wC .= $utwc;

		return $this->db->query("SELECT a.employeeid, a.cutoffstart, a.cutoffend, a.net, a.status,  CONCAT(b.`lname`, ', ', b.`fname`, ' ', b.`mname`) AS fullname, b.deptid FROM payroll_computed_table a INNER JOIN employee b on a.employeeid = b.employeeid WHERE `status`='$status' AND DATE_FORMAT(cutoffstart, '%Y') = '$year' $wC $oD")->result();

		// https://jira.pinnacle.edu.ph/browse/TMSHYP-3590 specific month

		// return $this->db->query("SELECT a.employeeid, a.cutoffstart, a.cutoffend, a.net, a.status,  CONCAT(b.`lname`, ', ', b.`fname`, ' ', b.`mname`) AS fullname, b.deptid FROM payroll_computed_table a INNER JOIN employee b on a.employeeid = b.employeeid WHERE `status`='$status' AND DATE_FORMAT(cutoffstart, '%m') <= '$month' AND DATE_FORMAT(cutoffstart, '%Y') = '$year' $wC $oD")->result();

	}

	function getSalaryHistoryReport($month='', $year='', $campus='',$company_campus='', $department='', $type='', $sortby='', $employeeid='',$office=''){
		$from = $to = "";
		if($month && $year){
			$from = $year."-".$month."-01";
			$to = $year."-".$month."-31";
	    }else{
	    	$from = $year."-01-01" ;
	    	$to = $year."-12-31" ;
	    }

	    if($employeeid && $employeeid !== 'null'){
			if($employeeid[0]){
	            foreach ($employeeid as $key => $value){
	                $value = str_replace("'", "",$value);
	                if($key == 0) $filter['employeeid'] = "'".$value."'";
	                else $filter['employeeid'] .= ",'".$value."'";
	            }
	        }
        }

        $employeeid = (isset($filter['employeeid']) ? $filter['employeeid'] : '');
		$whereClause = $orderby = "";
		if($campus && $campus != 'All') $whereClause .= " AND b.campusid = '$campus'";
		if($company_campus && $company_campus != 'all') $whereClause .= " AND b.company_campus =  ".$this->db->escape($company_campus)."";
		if($department) $whereClause .= " AND b.deptid = '$department'";
		if($office) $whereClause .= " AND b.office = '$office'";
		if($employeeid) $whereClause .= " AND b.employeeid IN ($employeeid)";
		if($type && $type != 'undefined'){
	        if($type != "trelated") $whereClause .= " AND b.teachingtype = '$type' AND b.trelated = '0' ";
	        else $whereClause .= " AND b.teachingtype='teaching' AND b.trelated = '1'";
	    }
		if($sortby == "department"){
			$orderby = 'b.office, b.lname, a.date_effective';
		}else{
			$orderby = 'b.lname ASC, a.timestamp DESC';
		}

		$utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }
        $whereClause .= $utwc;
		// $query = $this->db->query("SELECT *, a.emp_bank as payroll_bank FROM payroll_employee_salary_history a INNER JOIN employee b ON a.employeeid = b.employeeid where (a.date_effective between '$from' AND '$to') $whereClause ORDER BY $orderby ")->result_array();
		$query = $this->db->query("SELECT DISTINCT a.fixedday, a.employeeid, b.lname, b.fname, b.mname, a.id, a.monthly, a.semimonthly, a.daily, a.hourly, a.minutely, a.account_number, a.date_effective, a.timestamp, b.deptid, b.office, a.emp_bank AS payroll_bank, dependents FROM payroll_employee_salary_history a INNER JOIN employee b ON a.employeeid = b.employeeid where (a.date_effective between '$from' AND '$to') $whereClause ORDER BY $orderby ")->result_array();
		return $query;
	}

	///< @Angelica - get payroll emp fixed contribution history
	function getEmployeeFixedContriHistory($code_deduction='',$cutoffstart='',$cutoffend='',$bank=''){
		$data = array();

		$wC = $er_wC = '';
		if($bank) 				$wC .= " AND a.bank='$bank'";
		$utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }
        $wC .= $utwc;
		$res = $this->db->query("SELECT a.id, a.employeeid, b.deptid, b.emp_tin, b.emp_pagibig, b.bdate, REPLACE(b.LName, 'Ã‘', 'Ñ') AS lname,REPLACE(b.FName, 'Ã‘', 'Ñ') AS fname,REPLACE(b.MName, 'Ã‘', 'Ñ') AS mname 
									FROM payroll_computed_table a
									LEFT JOIN employee b ON b.employeeid=a.employeeid
									WHERE a.cutoffstart='$cutoffstart' AND a.cutoffend='$cutoffend' $wC");

		if($code_deduction) 	$er_wC .= " AND code_deduction='$code_deduction'";

		foreach ($res->result() as $key => $row) {
			$base_id = $row->id;

			$data[$row->deptid][$row->employeeid] = array(
														'emp_tin' => $row->emp_tin,
														'emp_pagibig' => $row->emp_pagibig,
														'lname' => $row->lname,
														'fname' => $row->fname,
														'mname' => $row->mname,
														'bdate' => $row->bdate
														);
			
			$er_q = $this->db->query("SELECT * FROM payroll_computed_ee_er WHERE base_id = '$base_id' $er_wC");
			foreach ($er_q->result() as $er_key => $er_row) {
				$data[$row->deptid][$row->employeeid][$er_row->code_deduction] = array('EE'=>$er_row->EE,'EC'=>$er_row->EC,'ER'=>$er_row->ER);
			}
		}
		return $data;
	}

	function getEmployeePhilhealthContri($month='',$year=''){
		$data = array();

		$res = $this->db->query("SELECT emp_philhealth,e.`employeeid`,p.`salary`,p.`cutoffstart`,p.`cutoffend`,p.`fixeddeduc`,e.bdate FROM employee e
								LEFT JOIN payroll_computed_table p ON e.`employeeid` = p.`employeeid` WHERE p.`bank`<>'' 
								AND p.`status`='PROCESSED' AND DATE_FORMAT(cutoffstart,'%m-%Y') = '".$month.'-'.$year."' AND DATE_FORMAT(cutoffend,'%m-%Y')  = '".$month.'-'.$year."'");

		foreach($res->result() as $key => $row){
			$philhealthid = "";
			$philhealthid = $row->emp_philhealth;
			$salary = $row->salary;
			$bdate = $row->bdate;
			$fixeddeduc = array();
			$fixeddeduc = $this->payrollprocess->constructArrayListFromComputedTable($row->fixeddeduc);

			$contributionStatus = "NE";
			if(count($fixeddeduc)>0){
				foreach($fixeddeduc as $deduccode=>$amount){
					if($deduccode=="PHILHEALTH" && $amount>0){
						$contributionStatus = "A";
					}
				}
			}
			if(!isset($data[$philhealthid]['PhilHealthId'])) $data[$philhealthid]['PhilHealthId'] = "";
			if(!isset($data[$philhealthid]['Salary'])) $data[$philhealthid]['Salary'] = 0;
			if(!isset($data[$philhealthid]['Status'])) $data[$philhealthid]['Status'] = "";
			if(!isset($data[$philhealthid]['EffectivityDate'])) $data[$philhealthid]['EffectivityDate'] = "";
			if(!isset($data[$philhealthid]['BDate'])) $data[$philhealthid]['BDate'] = "";

			

			$data[$philhealthid]['PhilHealthId'] = $philhealthid;
			$data[$philhealthid]['Salary']+=$salary;

			if($data[$philhealthid]['Status']!='A'){
				$data[$philhealthid]['Status'] = $contributionStatus;
			}

			if($data[$philhealthid]['Status']=='NE'){
				$data[$philhealthid]['EffectivityDate'] = date('m/d/Y',strtotime('01/'.$month.'/'.$year));
			}

			if($bdate){
				$data[$philhealthid]['BDate'] = date('m/d/Y',strtotime($bdate));
			}
		}
		return $data;
	}

	# =================================================================== PAYROLL CARD ===================================================================

	# for mcu-hyperion 21249
	# by justin (with e)

	function changeResultQueryIntoArray($q_result){
		$array = array();

		foreach ($q_result as $res) {
			foreach ($res as $fields => $value) {
				$array[$res->id][$fields] = $value;
			}
		}

		return $array;
	}

	function setIncomeIntoArray(){
		$this->load->model('payroll');
		$arr_income = array();
		
		$q_income_config = $this->payroll->displayIncome();
		$arr_income = $this->changeResultQueryIntoArray($q_income_config->result());
		
		return $arr_income;
	}

	function setDeductionIntoArray(){
		$this->load->model('payroll');
		$arr_deduction = array();
		
		$q_deduction_config = $this->payroll->displayDeduction();
		$arr_deduction = $this->changeResultQueryIntoArray($q_deduction_config->result());

		return $arr_deduction;
	}

	function setLoanIntoArray(){
		$this->load->model('payroll');
		$arr_loan = array();

		$q_loan_config = $this->payroll->displayLoan();
		$arr_loan = $this->changeResultQueryIntoArray($q_loan_config->result());

		return $arr_loan;
	}

	function getIncomeSetupToArray($income_config){
		$income_setup = array();

		$income_setup["basicAdmin"] = $this->searchThisAreIncludedInComputation($income_config, array("mainaccount" => 61));
		$income_setup["absUt"]		= $this->searchThisAreIncludedInComputation($income_config, array("mainaccount" => 62));
		$income_setup["tLoad"]		= $this->searchThisAreIncludedInComputation($income_config, array("mainaccount" => 66));
		$income_setup["sssBen"]		= $this->searchThisAreIncludedInComputation($income_config, array("mainaccount" => 63));
		$income_setup["longevity"]	= $this->searchThisAreIncludedInComputation($income_config, array("mainaccount" => 6));
		$income_setup["otNonTax"]	= $this->searchThisAreIncludedInComputation($income_config, array("mainaccount" => 64));
		$income_setup["othTaxInc"]	= $this->searchThisAreIncludedInComputation($income_config, array("mainaccount" => "", "incomeType" => ""));
		$income_setup["cola"]		= $this->searchThisAreIncludedInComputation($income_config, array("mainaccount" => 71));
		$income_setup["13month"]	= $this->searchThisAreIncludedInComputation($income_config, array("mainaccount" => 56));
		$income_setup["deminimis"]	= $this->searchThisAreIncludedInComputation($income_config, array("incomeType" => 1));
		$income_setup["notForAlpha"]= $this->searchThisAreIncludedInComputation($income_config, array("mainaccount" => 70));

		return $income_setup;
	}

	function searchThisAreIncludedInComputation($search_in, $search_by){
		$arr_result = array();

		foreach ($search_in as $id => $info) {
			$isThisAdded = true;

			foreach ($search_by as $s_fields => $s_value) {
				if($info[$s_fields] != $s_value){
					$isThisAdded = false;
				}
			}

			if($isThisAdded) array_push($arr_result, $id);
		}

		return $arr_result;
	}

	function setDeductionSetupToArray($deduction_config){
		$deduction_setup = array();

		$deduction_setup["sss"] 		= $this->searchThisAreIncludedInComputation($deduction_config, array("mainaccount" => 133));
		$deduction_setup["hdmf"] 		= $this->searchThisAreIncludedInComputation($deduction_config, array("mainaccount" => 134));
		$deduction_setup["phl"] 		= $this->searchThisAreIncludedInComputation($deduction_config, array("mainaccount" => 135));
		$deduction_setup["maxicare"]	= $this->searchThisAreIncludedInComputation($deduction_config, array("mainaccount" => 3));
		$deduction_setup["loan"]		= $this->searchThisAreIncludedInComputation($deduction_config, array("mainaccount" => "L2"));
		$deduction_setup["philam"]		= $this->searchThisAreIncludedInComputation($deduction_config, array("mainaccount" => 2));
		$deduction_setup["ploan"]		= $this->searchThisAreIncludedInComputation($deduction_config, array("mainaccount" => "L15"));
		$deduction_setup["sloan"]		= $this->searchThisAreIncludedInComputation($deduction_config, array("mainaccount" => "L13"));
		$deduction_setup["otherDeduc"]	= $this->searchThisAreIncludedInComputation($deduction_config, array("mainaccount" => ""));

		return $deduction_setup;
	}

	function setLoanSetupToArray($loan_config){
		$loan_setup = array();

		$loan_setup["loan"] = $this->searchThisAreIncludedInComputation($loan_config, array("mainaccount" => 2));
		$loan_setup["ploan"] = $this->searchThisAreIncludedInComputation($loan_config, array("mainaccount" => 15));
		$loan_setup["sloan"] = $this->searchThisAreIncludedInComputation($loan_config, array("mainaccount" => 13));
		$loan_setup["otherDeduc"] = $this->searchThisAreIncludedInComputation($loan_config, array("mainaccount" => ""));

		return $loan_setup;
	}

	function changeToArrayList($array){
		$arr_list = array();
		
		foreach (explode("/", $array) as $exp_array) {
			if($exp_array){
				list($id, $value) = explode("=", $exp_array);
				$arr_list[$id] = $value;
			}
		}

		return $arr_list;
	}

	function getSumAmountOfIncludedSetup($arr_list, $included_setup){
		$amount = 0;

		foreach ($arr_list as $id => $value) {
			if(in_array($id, $included_setup)){
				$amount += $value;
			}
		}

		return $amount;
	}

	function getPayrollCardContent($sel_date, $q_payroll_computed, $income_setup, $deduction_setup, $loan_setup){
		$tbl_content = array();
		$tbl_content["description"] = str_replace("_", " ", $sel_date);

		foreach ($q_payroll_computed as $res) {
			$income_list 	 = $this->changeToArrayList($res->income);
			$fixeddeduc_list = $this->changeToArrayList($res->fixeddeduc);
			$otherdeduc_list = $this->changeToArrayList($res->otherdeduc);
			$loan_list 		 = $this->changeToArrayList($res->loan);

			$basicAdmin 	 = $res->salary + $this->getSumAmountOfIncludedSetup($income_list, $income_setup["basicAdmin"]);
			$absUT 			 = ($res->absents + $res->tardy);
			$tLoad 			 = $this->getSumAmountOfIncludedSetup($income_list, $income_setup["tLoad"]);
			$sssBen 		 = $this->getSumAmountOfIncludedSetup($income_list, $income_setup["sssBen"]);
			$netBasicPay 	 = ($basicAdmin + $tLoad + $sssBen) - $absUt;
			$longevity 		 = $this->getSumAmountOfIncludedSetup($income_list, $income_setup["longevity"]);
			$otNonTax 		 =  $res->overtime + $this->getSumAmountOfIncludedSetup($income_list, $income_setup["otNonTax"]);
			$othTaxInc 		 = $this->getSumAmountOfIncludedSetup($income_list, $income_setup["otNonTax"]);
			$cola 			 = $this->getSumAmountOfIncludedSetup($income_list, $income_setup["cola"]);
			$othNonTaxInc 		 = $this->getSumAmountOfIncludedSetup($income_list, $income_setup["othTaxInc"]);
			$amountOf13Month = $this->getSumAmountOfIncludedSetup($income_list, $income_setup["13month"]);
			$deminimis 		 = $this->getSumAmountOfIncludedSetup($income_list, $income_setup["deminimis"]);
			$notForAlpha 	 = $this->getSumAmountOfIncludedSetup($income_list, $income_setup["notForAlpha"]);
			$grossPay 		 = $netBasicPay + $longevity + $otNonTax + $othNonTaxInc + $cola + $amountOf13Month + $deminimis + $notForAlpha;
			$withHoldingTax  = $res->withholdingtax;
			$sss 			 = $fixeddeduc_list["SSS"] + $this->getSumAmountOfIncludedSetup($otherdeduc_list, $deduction_setup["sss"]);
			$hdmf 			 = $fixeddeduc_list["PAGIBIG"] + $this->getSumAmountOfIncludedSetup($otherdeduc_list, $deduction_setup["hdmf"]);
			$phl 			 = $fixeddeduc_list["PHILHEALTH"] + $this->getSumAmountOfIncludedSetup($otherdeduc_list, $deduction_setup["phl"]);
			$maxicare 		 = $this->getSumAmountOfIncludedSetup($otherdeduc_list, $deduction_setup["maxicare"]);
			$loan 			 = $this->getSumAmountOfIncludedSetup($loan_list, $loan_setup["loan"]) + $this->getSumAmountOfIncludedSetup($otherdeduc_list, $deduction_setup["loan"]);
			$philam 		 = $this->getSumAmountOfIncludedSetup($otherdeduc_list, $deduction_setup["philam"]);
			$ploan 			 = $this->getSumAmountOfIncludedSetup($loan_list, $loan_setup["ploan"]) + $this->getSumAmountOfIncludedSetup($otherdeduc_list, $deduction_setup["ploan"]);
			$sloan 			 = $this->getSumAmountOfIncludedSetup($loan_list, $loan_setup["sloan"]) + $this->getSumAmountOfIncludedSetup($otherdeduc_list, $deduction_setup["sloan"]);
			$otherDeduc 	 = $this->getSumAmountOfIncludedSetup($loan_list, $loan_setup["otherDeduc"]) + $this->getSumAmountOfIncludedSetup($otherdeduc_list, $deduction_setup["otherDeduc"]);
			$netPay 		 = $grossPay - ($withholdingtax + $sss + $hdmf + $phl + $maxicare + $loan + $philam + $ploan + $sloan + $otherDeduc);


			$tbl_content["basic_admin"] 	= ($basicAdmin) ? $basicAdmin : 0;
			$tbl_content["abs_ut"] 			= ($absUT) ? $absUT : 0;
			$tbl_content["tload_elep"] 		= ($tLoad) ? $tLoad : 0;
			$tbl_content["sss_ben"] 		= ($sssBen) ? $sssBen : 0;
			$tbl_content["net_basic_pay"]	= ($netBasicPay) ? $netBasicPay : 0;
			$tbl_content["lp"]				= ($longevity) ? $longevity : 0;
			$tbl_content["ot_hon_hp"]		= ($otNonTax) ? $otNonTax : 0;
			$tbl_content["oth_tax_inc"]		= ($othTaxInc) ? $othTaxInc : 0;
			$tbl_content["cola_ntax"]		= ($cola) ? $cola : 0;
			$tbl_content["oth_nontax"]		= ($othNonTaxInc) ? $othNonTaxInc : 0;
			$tbl_content["13_month"]		= ($amountOf13Month) ? $amountOf13Month : 0;
			$tbl_content["de_menimis"]		= ($deminimis) ? $deminimis : 0;
			$tbl_content["not_for_alpha"]	= ($notForAlpha) ? $notForAlpha : 0;
			$tbl_content["gross_pay"]		= ($grossPay) ? $grossPay : 0;
			$tbl_content["w_tax"]			= ($withHoldingTax) ? $withHoldingTax : 0;
			$tbl_content["sss"]				= ($sss) ? $sss : 0;
			$tbl_content["hdmf"]			= ($hdmf) ? $hdmf : 0;
			$tbl_content["phl"]				= ($phl) ? $phl : 0;
			$tbl_content["maxicare"]		= ($maxicare) ? $maxicare : 0;
			$tbl_content["ar_emp"]			= ($loan) ? $loan : 0;
			$tbl_content["philam"]			= ($philam) ? $philam : 0;
			$tbl_content["ploan"]			= ($ploan) ? $ploan : 0;
			$tbl_content["sloan"]			= ($sloan) ? $sloan : 0;
			$tbl_content["other_deduc"]		= ($otherDeduc) ? $otherDeduc : 0;
			$tbl_content["net_pay"]			= ($netPay) ? $netPay : 0;

		}

		return $tbl_content;	            
	}

	function getPayrollCardData($empid, $pyear){
		$data = array();
		$this->load->model('reports');
		$this->load->model('utils');
		$this->load->model('payroll');

		# > for table header.. incase of changing table header..
		$tbl_header = array(
				"date_year" => $pyear,
				"basic_admin" => 'BASIC ADMIN', 
				"abs_ut" => 'ABS/UT', 
				"tload_elep" => 'T.LOAD/ELEP', 
				"sss_ben" => 'SSS BEN', 
				"net_basic_pay" => 'NET BASIC PAY', 
				"lp" => 'LP', 
				"ot_hon_hp" => 'OT/HON/HP', 
				"oth_tax_inc" => 'OTH TAX INC', 
				"cola_ntax" => 'COLA/N.TAX', 
				"oth_nontax" => 'OTH NON-TAX', 
				"13_month" => '13TH MONTH', 
				"de_menimis" => 'De Minimis', 
				"not_for_alpha" => 'NOT FOR ALPHA/For Liq\'n', 
				"gross_pay" => 'GROSS PAY', 
				"w_tax" => 'W_TAX', 
				"sss" => 'SSS', 
				"hdmf" => 'HDMF', 
				"phl" => 'PHL', 
				"maxicare" => 'MAXICARE', 
				"ar_emp" => 'AR-EMP', 
				"philam" => 'PHILAM', 
				"ploan" => 'PLOAN', 
				"sloan" => 'SLOAN', 
				"other_deduc" => 'OTHER DED', 
				"net_pay" => 'NET PAY');
		$data["tbl_header"] = $tbl_header;

		# para sa total..
		$arr_total = array();
		$tbl_all_content = array();
		$tbl_total_content = array();

		# >total para sa header..
		$total_tax_excemption = 250000;
		$total_taxable = $total_thirteen_month = $total_annual = 0;
		
		$income_config = $this->setIncomeIntoArray();
		$income_setup  = $this->getIncomeSetupToArray($income_config);

		$deduction_config = $this->setDeductionIntoArray();
		$deduction_setup  = $this->setDeductionSetupToArray($deduction_config);

		$loan_config = $this->setLoanIntoArray();
		$loan_setup  = $this->setLoanSetupToArray($loan_config);
		
		# > run muna yung total..
		foreach ($tbl_header as $key => $value) $tbl_total_content[$key] = 0;

		# > diplayed table content
		for ($i=1; $i <= 12 ; $i++) { 
			$showDay = $this->utils->showCutOffDaysPerMonth($pyear, $i);

			foreach ($showDay as $days) {
				$tbl_content = array();
				$sel_date = date("M_d", strtotime($pyear."-".$i."-".$days));
				$tbl_all_content[$sel_date] = array();
				$tbl_all_content[$sel_date]["description"] = str_replace("_", " ", $sel_date);
				
				$s_date = $e_date = '';
				$e_date = $pyear."-".$i."-".$days;

				if($days == 15) $s_date = $pyear."-".$i."-1";
				else 			$s_date = $pyear."-".$i."-16";

				$q_payroll_computed = $this->reports->getEmpDataPerDay($s_date, $e_date, $empid);
				
				if(count($q_payroll_computed) > 0){
					$tbl_all_content[$sel_date] = $this->getPayrollCardContent($sel_date, $q_payroll_computed, $income_setup, $deduction_setup, $loan_setup);
				}else{
					# > kapag wala data.. default content nya dapat is zero..
					$isFirst = true;
					foreach ($tbl_header as $key => $description) {
						if(!$isFirst) $tbl_all_content[$sel_date][$key] = 0;

						$isFirst = false;
					}
				}

			}

		}
		$tbl_total_content = $this->getSumOfTableContent($tbl_all_content);
		$data["tbl_all_content"] = $tbl_all_content;
		$data["tbl_total_content"] = $tbl_total_content;

		# > Taxable Income = GROSSPAY LESS (TAX EXEMPTION, TOTAL SSS BEN, COLA, OTH NON TAC INCOME, 13TH MONTH PAY, DE MINIMIS, NOT FOR ALPHA, SSS, HDMF, PHL CONTRIBUTION)
		$arr_include_compute_tax = array("sss_ben","cola_ntax","oth_nontax","13_month","de_menimis","not_for_alpha","sss","hdmf","phl");
		$total_taxable = $tbl_total_content["gross_pay"] - ($total_tax_excemption + $this->getSumForTaxableIncome($tbl_total_content, $arr_include_compute_tax));

		$total_thirteen_month = $tbl_total_content["13_month"];

		$this->load->model("payrollcomputation");
		$total_annual = $this->payrollcomputation->computeYearlyPersonalIncomeTaxRate($total_taxable, "yearly", "S");

		# > Final Tax  = ANNUAL TAX DUE LESS TOTAL W_TAX 
		$final_tax = $total_annual - $tbl_total_content["w_tax"];

		$data["tax_excemption"]  = $total_tax_excemption;
		$data["taxable_income"]  = $total_taxable;
		$data["13_month_pay"] 	 = $total_thirteen_month;
		$data["annual_tax"]		 = $total_annual;
		$data["final_tax"]    	 = $final_tax;
		
		return $data;
	}



	function getSumOfTableContent($tbl_all_content){
		$tbl_total_content = array();
		$tbl_total_content["total"] = "Total :";

		foreach ($tbl_all_content as $date => $info) {
			$isFirst = true;

			foreach ($info as $key => $value) {
				if(!$isFirst){
					$tbl_total_content[$key] += $value;
				}
				$isFirst = false;
			}
		}
		
		return $tbl_total_content;
	}

	function getSumForTaxableIncome($tbl_total_content, $include_col){
		$amount = 0;

		foreach ($tbl_total_content as $key => $value) {
			if(in_array($key, $include_col)){
				$amount += $value;
			}
		}

		return $amount;
	}
	# end for mcu-hyperion 21249
	# =================================================================== END PAYROLL CARD ===================================================================

	public function getPayrollRegisterList($filter){
		$utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND a.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
        }
        $filter .= $utwc;
		$q_emplist = $this->db->query("SELECT * FROM employee a INNER JOIN payroll_computed_table b ON a.`employeeid` = b.`employeeid` LEFT JOIN campus_company c ON c.company_description = a.company_campus $filter GROUP BY a.employeeid ORDER BY b.cutoffstart, a.campusid ");
		if($q_emplist->num_rows() > 0) return $q_emplist->result();
		else return false;
	}

	public function getPerdeptDetail($empid, $sdate, $edate, $teachingtype){
		$_att = "";
		if($teachingtype == "teaching"){
			$q_att = $this->db->query(" SELECT b.*
	        FROM attendance_confirmed a 
	        INNER JOIN workhours_perdept b ON a.id = b.base_id
	        WHERE a.employeeid = '$empid' AND a.payroll_cutoffstart = '$sdate' AND a.payroll_cutoffend = '$edate' AND a.status = 'PROCESSED' ");
		}else{
			$q_att = $this->db->query(" SELECT b.*
	        FROM attendance_confirmed_nt a 
	        INNER JOIN workhours_perdept_nt b ON a.id = b.base_id
	        WHERE a.employeeid = '$empid' AND a.payroll_cutoffstart = '$sdate' AND a.payroll_cutoffend = '$edate' AND a.status = 'PROCESSED' ");
		}

		/*get included only in computation -- this is for nursing department*/
		$perdept_salary;
		$this->load->model('payrollcomputation','comp');
		if($this->extensions->isNursingDepartment($empid) > 0 && !$this->extensions->isNursingExcluded($empid)){
			$nursing_included = $this->comp->nursingIncludedPerdept($q_att->result()); 
			$perdept_salary = $nursing_included;
			$perdept_salary = $q_att->result();
		}else{
			$perdept_salary = $q_att->result();
		}

		$perdept_list = array();
		if($perdept_salary){
			foreach($perdept_salary as $att_row){
				$perdept_list[$att_row->aimsdept][$att_row->type]["work_hours"] = $att_row->work_hours;
				$perdept_list[$att_row->aimsdept][$att_row->type]["late_hours"] = $att_row->late_hours;
				$perdept_list[$att_row->aimsdept][$att_row->type]["deduc_hours"] = $att_row->deduc_hours;
			}
		}
		$perdept_payroll = $this->payroll->getWorkHoursPerdept($empid, $sdate, $edate);
		if($perdept_payroll){
			foreach($perdept_payroll as $payroll_row){
				if(isset($perdept_list[$payroll_row->aimsdept][$payroll_row->type])){
					$perdept_list[$payroll_row->aimsdept][$payroll_row->type]["work_amount"] = $payroll_row->work_amount;
					$perdept_list[$payroll_row->aimsdept][$payroll_row->type]["late_amount"] = $payroll_row->late_amount;
					$perdept_list[$payroll_row->aimsdept][$payroll_row->type]["deduc_amount"] = $payroll_row->deduc_amount;
				}
			}
		}
	
		return $perdept_list;
	}

}


?>