<?php 

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Payrollprocess extends CI_Model {
	
	///< construct an associative array list from computed table string, arr['key'] = $value;
	function constructArrayListFromComputedTable($str=''){
	    $arr = array();
	    if($str){
	        $str_arr = explode('/', $str);
	        if(count($str_arr)){
	            foreach ($str_arr as $i_temp) {
	                $str_arr_temp = explode('=', $i_temp);
	                if(isset($str_arr_temp[0]) && isset($str_arr_temp[1])){
	                    $arr[$str_arr_temp[0]] = $str_arr_temp[1];
	                }
	            }
	        }
	    }
	    return $arr;
	}


	///< construct an associative array list from stdclass object, $arr['key'] = $value;
	function constructArrayListFromStdClass($res='',$key='',$value=''){
	    $arr = array();
	    if($res->num_rows() > 0){
	        foreach ($res->result() as $k => $row) {
	            $arr[$row->$key] = array('description'=>$row->$value,'hasData'=>0);
	        }
	    }
	    return $arr;
	}

	function processPayrollSummary($emplist=array(),$emplist2=array(),$sdate='',$edate='',$schedule='',$quarter='',$recompute=false,$payroll_cutoff_id=''){

		$recomputed_emp_payroll = 0;
		$workdays = 0;
		$workhours_lec = $workhours_lab = $workhours_admin = "";

		//< initialize needed info ---------------------------------------------------
		$info    = $arr_income_config = $arr_income_adj_config = $arr_incomeoth_config = $arr_deduc_config = $arr_fixeddeduc_config = $arr_loan_config = array();

		///< ------------------------------ income config ------------------------------------------------------------
		$income_config_q = $this->payroll->displayIncome();
		$arr_income_config = $this->constructArrayListFromStdClass($income_config_q,'id','description');

		$arr_income_adj_config = $arr_income_config;
		$arr_income_adj_config['SALARY'] = array('description'=>'SALARY','hasData'=>0);

		///< ------------------------------ incomeoth config ---------------------------------------------------------------
		$incomeoth_config_q = $this->payroll->displayIncomeOth();
		$arr_incomeoth_config = $this->constructArrayListFromStdClass($incomeoth_config_q,'id','description');

		///< ------------------------------ fixed deduction config ----------------------------------------------------
		$fixeddeduc_config_q = $this->db->query("SELECT code_deduction,description FROM deductions");
		$arr_fixeddeduc_config = $this->constructArrayListFromStdClass($fixeddeduc_config_q,'code_deduction','description');


		///< ------------------------------ deduction config ----------------------------------------------------------
		$deduction_config_q = $this->payroll->displayDeduction();
		$arr_deduc_config = $this->constructArrayListFromStdClass($deduction_config_q,'id','description');
		$arr_deduc_config_arithmetic = $this->constructArrayListFromStdClass($deduction_config_q,'id','arithmetic');


		///< ------------------------------ loan config ---------------------------------------------------------------
		$loan_config_q = $this->payroll->displayLoan();
		$arr_loan_config = $this->constructArrayListFromStdClass($loan_config_q,'id','description');

		if($recompute === true){
			foreach($emplist as $row){
				$eid = $row->employeeid;
				$this->db->query("DELETE FROM payroll_computed_table WHERE cutoffstart='$sdate' AND cutoffend='$edate' AND schedule='$schedule' AND quarter='$quarter' AND employeeid='$eid' AND status='PENDING'");
			}
		}
		// die;

		foreach ($emplist as $row) {
			$perdept_amt_arr = array();
			$eid = $row->employeeid;
				
			$check_saved_q = $this->getPayrollSummary('SAVED',$sdate,$edate,$schedule,$quarter,$eid,TRUE,'PROCESSED');

			$clearance_date = "";
			$is_resigned = $this->db->query("SELECT * FROM employee_employment_status_history WHERE employeeid = '$eid' AND clearance_to != '0000-00-00' ORDER BY timestamp DESC LIMIT 1");
			if($is_resigned->num_rows() > 0){
				$clearance_date = $is_resigned->row()->clearance_to;
			}

			if(!$check_saved_q && ($edate <= $clearance_date || !$clearance_date)){

				$info[$eid]['income'] = $info[$eid]['income_adj'] = $info[$eid]['deduction'] = $info[$eid]['fixeddeduc'] = $info[$eid]['loan'] = array();

				$info[$eid]['fullname'] 	=  isset($row->fullname) ? $row->fullname : '';
				$info[$eid]['deptid'] = isset($row->deptid) ? $row->deptid : '';
				$info[$eid]['office'] = isset($row->office) ? $row->office : '';

				///< check for pending computation, if true - display directly, else compute payroll first
				// $res = $this->getPayrollSummary('PENDING',$sdate,$edate,$schedule,$quarter,$eid);
				// // echo "<pre>"; print_r($res->num_rows()); die;
				// if($res->num_rows() > 0){

				// 	list($info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config) 
				// 		= $this->constructPayrollComputedInfo($res,$info,$eid,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config);

				// }else{ ///< compute

				// 	list($info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config) 
				// 		= $this->computeNewPayrollInfo($row,$schedule,$quarter,$sdate,$edate,$payroll_cutoff_id,$info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_deduc_config_arithmetic,$arr_loan_config);
					
				// }

				list($info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config) 
						= $this->computeNewPayrollInfo($row,$schedule,$quarter,$sdate,$edate,$payroll_cutoff_id,$info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_deduc_config_arithmetic,$arr_loan_config);

			} ///< end if SAVED

			$recomputed_emp_payroll += 1;
            $emplist_total_payroll = sizeof($emplist);

            // $this->session->set_userdata('emplist_total_payroll', $emplist_total_payroll);
            // $this->session->set_userdata('recomputed_emp_payroll', $recomputed_emp_payroll);

		} //end loop emplist

		// $this->session->unset_userdata('emplist_total_payroll');
        // $this->session->unset_userdata('recomputed_emp_payroll');

		$data['emplist'] = $info;
		$data['income_config'] = $arr_income_config;
		$data['income_adj_config'] = $arr_income_adj_config;
		$data['incomeoth_config'] = $arr_incomeoth_config;
		$data['fixeddeduc_config'] = $arr_fixeddeduc_config;
		$data['deduction_config'] = $arr_deduc_config;
		$data['loan_config'] = $arr_loan_config;

		return $data;

	}

	function constructPayrollComputedInfo($res,$info=array(),$eid='',$arr_income_config=array(),$arr_income_adj_config=array(),$arr_fixeddeduc_config=array(),$arr_deduc_config=array(),$arr_loan_config=array()){
		$res = $res->row(0);

		$info[$eid]['base_id'] 		= $res->id;

		$info[$eid]['tardy'] 		= $res->tardy;
		$info[$eid]['absents'] 		= $res->absents;
		$info[$eid]['whtax'] 		= $res->withholdingtax;
		$info[$eid]['salary'] 		= $res->salary;
		$info[$eid]['teaching_pay'] 		= $res->teaching_pay;
		$info[$eid]['overtime'] 	= $res->overtime;
		$info[$eid]['substitute'] 	= $res->substitute;

		//<!--NET BASIC PAY-->
		$info[$eid]['netbasicpay'] 	= $res->netbasicpay;;
		$info[$eid]['grosspay']    	= $res->gross;
		$info[$eid]['netpay']    	= $res->net;

		$info[$eid]['isHold']    	= $res->isHold;

		$income_adj_arr 				= $this->constructArrayListFromComputedTable($res->income_adj);
		$info[$eid]['income_adj'] = $income_adj_arr;
		foreach ($income_adj_arr as $k => $v) {$arr_income_adj_config[$k]['hasData'] = 1;}
		
		//< income
		$income_arr 				= $this->constructArrayListFromComputedTable($res->income);
		$info[$eid]['income'] = $income_arr;
		foreach ($income_arr as $k => $v) {$arr_income_config[$k]['hasData'] = 1;}

		///< fixed deduc
        $fixeddeduc_arr = $this->constructArrayListFromComputedTable($res->fixeddeduc);
        $info[$eid]['fixeddeduc'] = $fixeddeduc_arr;
        foreach ($fixeddeduc_arr as $k => $v) {$arr_fixeddeduc_config[$k]['hasData'] = 1;}

        ///< deduc
        $deduc_arr = $this->constructArrayListFromComputedTable($res->otherdeduc);
        $info[$eid]['deduction'] = $deduc_arr;
        foreach ($deduc_arr as $k => $v) {$arr_deduc_config[$k]['hasData'] = 1;}

        ///< loan
        $loan_arr = $this->constructArrayListFromComputedTable($res->loan);
        $info[$eid]['loan'] = $loan_arr;
        foreach ($loan_arr as $k => $v) {$arr_loan_config[$k]['hasData'] = 1;}

        return array($info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config);
	}

	/**
	 * Fetch and calculate teaching-related payroll data.
	 * 
	 * @author Leandrei M. Santos
	 * @param string $payroll_cutoff_id Payroll cutoff ID.
	 * @param string $tnt               Teaching or non-teaching indicator.
	 * @param string $campus            Campus identifier.
	 * @param string $is_trelated       Indicator if teaching-related.
	 * @param string $office            Office identifier.
	 * @param string $deptid            Department ID.
	 * @param string $status            Employment status.
	 * @param string $company_campus    Company campus identifier.
	 * @param string $employmentstat    Employment status type.
	 * @param string $eid               Employee ID.
	 * 
	 * @return array Calculated payroll data.
	 */
	function getManagePayrollDataTeaching(
		$payroll_cutoff_id = "",$tnt = "",$campus,$is_trelated = "",
		$office = "",$deptid = "",$status = "",$company_campus = "",
		$employmentstat = "",$eid = ""
	) {
		$this->load->model('hr_reports');
		$this->load->model('utils');

		$dtrAimsDept = $this->utils->getAIMSDepartmentCode();
		$cutoffDetails = $this->hr_reports->getCutoffByPayrollCutoff($payroll_cutoff_id);
		list($dtrStart, $dtrEnd, $payrollStart, $payrollEnd) = $this->payrolloptions->getDtrPayrollCutoffPair(
			$cutoffDetails['cutoffFrom'],
			$cutoffDetails['cutoffTo']
		);

		$attendanceList = $this->hr_reports->getAttConfirmed_summary(
			$tnt,$cutoffDetails['cutoffFrom'],$cutoffDetails['cutoffTo'],
			$payrollStart,$eid,$campus,$is_trelated,$office,$deptid,
			$status,$company_campus,$employmentstat
		);

		$data = [
			'lechour_wr' => 0,
			'tardy_ut_deduction' => 0,
			'absent_deduction' => 0,
			'overload_pay' => 0,
			'overload_absent' => 0,
			'overload_less_absent' => 0,
		];

		foreach ($attendanceList as $deptId => $deptDetails) {
			foreach ($deptDetails as $employeeId => $employeeDetails) {
				$otherLeave = $employeeDetails["eleave"] + $employeeDetails["oleave"];
				$perDeptArray = $employeeDetails['perdept_arr'] ?? [];
				$perDeptCount = max(1, count($perDeptArray));

				foreach ($perDeptArray as $aimsDept => $perDeptDetails) {
					$aimsDeptCode = $dtrAimsDept[$aimsDept] ?? ' ';

					$data['lechour_wr'] += $this->calculateWorkHours($eid, $aimsDeptCode, $perDeptDetails, 'LEC', 'lec');
					$data['lechour_wr'] += $this->calculateWorkHours($eid, $aimsDeptCode, $perDeptDetails, 'LAB', 'lab');
					$data['overload_pay'] += $this->calculateWorkHours($eid, $aimsDeptCode, $perDeptDetails, 'RLE', 'rle');

					$data['tardy_ut_deduction'] += $this->calculateLateOrAbsence($eid, $aimsDeptCode, $perDeptDetails, 'LEC', 'lec');
					$data['tardy_ut_deduction'] += $this->calculateLateOrAbsence($eid, $aimsDeptCode, $perDeptDetails, 'LAB', 'lab');

					$data['absent_deduction'] += $this->calculateLateOrAbsence($eid, $aimsDeptCode, $perDeptDetails, 'LEC', 'lec', 'deduc_hours');
					$data['absent_deduction'] += $this->calculateLateOrAbsence($eid, $aimsDeptCode, $perDeptDetails, 'LAB', 'lab', 'deduc_hours');
					$data['overload_absent'] += $this->calculateLateOrAbsence($eid, $aimsDeptCode, $perDeptDetails, 'RLE', 'rle', 'deduc_hours');
				}
			}
		}

		$data['overload_less_absent'] = $data['overload_pay'] - $data['overload_absent'];
		return $data;
	}

	/**
	 * LNDRSNTS
	 * Calculate yung work hours at gawing monetary value.
	 */
	private function calculateWorkHours($eid, $aimsDeptCode, $details, $type, $rateType)
	{
		if (!isset($details[$type]['work_hours'])) {
			return 0;
		}

		$rateHour = $this->utils->getRateHour($eid, $aimsDeptCode, $rateType);
		$rate = $rateHour / 60;

		list($hours, $minutes) = explode(':', $details[$type]['work_hours']);
		$totalMinutes = ($hours * 60) + $minutes;

		return $totalMinutes * $rate;
	}

	/**
	 * LNDRSNTS
	 * Calculate late or absence deductions based on time type.
	 */
	private function calculateLateOrAbsence($eid, $aimsDeptCode, $details, $type, $rateType, $timeKey = 'late_hours')
	{
		if (!isset($details[$type][$timeKey])) {
			return 0;
		}

		$rateHour = $this->utils->getRateHour($eid, $aimsDeptCode, $rateType);
		$rate = $rateHour / 60;

		list($hours, $minutes) = explode(':', $details[$type][$timeKey]);
		$totalMinutes = ($hours * 60) + $minutes;

		return $totalMinutes * $rate;
	}


	function computeNewPayrollInfo($row,$schedule,$quarter,$sdate,$edate,$payroll_cutoff_id,$info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_deduc_config_arithmetic,$arr_loan_config){
		$this->load->model('payrollcomputation','comp');
		$this->load->model('income');
		$perdept_amt_arr = array();
		$workdays =	$absentdays = 0;
		$workhours_lec = $workhours_lab = $workhours_admin = "";

		$eid 		= $row->employeeid;
		$tnt 		= $row->teachingtype;
		$employmentstat = $row->employmentstat;
		$regpay 	=  $row->regpay;
		$daily 		=  $row->daily;
		list($regpay, $daily) = $this->getEmployeeSalaryRate($regpay, $daily, $eid, $sdate);
		$hourly =  $row->hourly;
		// $hourly 	= ($row->daily / 8);
		$lechour 	=  $row->lechour;
		$labhour 	=  $row->labhour;
		$rlehour 	=  $row->rlehour;
		$fixedday 	=  $row->fixedday;
		$dependents = $row->dependents;
		$status = $row->status;
		$office = $row->office;
		$deptid = '';
		$campus = '';
		$is_trelated = '';
		$company_campus = '';
		$isFinal = '';
		$project_hol_pay = 0;
		$str_income = $str_income_adj = $str_fixeddeduc = $str_deduc = $str_loan = "";
		$total_deducSub= $totalincome= $totalincome_adj = $totalfix=$total_deducAdd=$totalloan = 0;
		$has_bdayleave = $this->has_birthday_leave($sdate, $edate, $eid);
		$minimum_wage = $this->minimum_wage($sdate);
		$perdept_salary = array();
		$info[$eid]['teaching_pay'] = 0;
		$info[$eid]['overload'] = 0;
		if($tnt == 'teaching'){

			$perdept_salary = $this->comp->getPerdeptSalaryHistory($eid,$sdate);

			list($tardy_amount,$absent_amount,$workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$perdept_amt_arr,$hold_status,$x,$x,$forFinalPay,$conf_base_id,$excess_min) = $this->comp->getTardyAbsentSummaryTeaching($eid,$tnt,$schedule,$quarter,$sdate,$edate,$hourly,$lechour,$labhour,$rlehour,$perdept_salary,false,$regpay);
			list($info[$eid]['salary'], $info[$eid]['teaching_pay']) 	= $this->comp->computeTeachingCutoffSalary($workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$hourly,$lechour,$labhour,$rlehour,$fixedday,$regpay,$perdept_amt_arr,$hold_status,$excess_min,$has_bdayleave,$minimum_wage);
			
			list($project_hol_pay, $sub_hol_pay, $suspension_pay) = $this->comp->getProjectHolidayPay($payroll_cutoff_id,$lechour,$labhour,$rlehour,$eid,$sdate);
			
			$this->income->saveHolidayPay($project_hol_pay, $eid, $sdate, $edate, $quarter);
			$this->income->saveSuspensionPay($suspension_pay, $eid, $sdate, $edate, $quarter);
			$info[$eid]['substitute'] = $this->comp->computeSubstitute($eid,$conf_base_id);

			// GAWING 0 YUNG AMOUNT NG TARDY AT ABSENT PARA DI NA BUMAWAS PA KAPAG TEACHING DAHIL NABAWAS NA YUN SA TAAS, APPLICABLE MUNA ITO SA TEACHING LANG.
			$is_trelated = $this->employee->isTeachingRelated($eid);
			if(!$is_trelated){
				$tardy_amount = 0;
				$absent_amount = 0;
			}

			/**
			 * @author Leandrei Santos
			 * STMTCC Process
			 * -Overwrite data dahil pina revise yung computation sa teaching
			 * -tanggalin lang to kung ibabalik sa dati.
			 */
			$managePayroll = $this->getManagePayrollDataTeaching($payroll_cutoff_id,$tnt,$campus,$is_trelated,$office,
																$deptid,$status,$company_campus,$employmentstat,$eid);
			$info[$eid]['teaching_pay'] =  $managePayroll['lechour_wr'];
			$tardy_amount = $managePayroll['tardy_ut_deduction'];
			$absent_amount = $managePayroll['absent_deduction'];
			$info[$eid]['overload'] = $managePayroll['overload_less_absent'];


		}else{
			$is_trelated = $this->employee->isTeachingRelated($eid);
			if(!$is_trelated){
				list($tardy_amount,$absent_amount,$workdays,$x,$x,$conf_base_id, $isFinal) = $this->comp->getTardyAbsentSummaryNT($eid,$tnt,$schedule,$quarter,$sdate,$edate,$hourly,false,$daily);
				$info[$eid]['salary'] 	= $this->comp->computeNTCutoffSalary($workdays,$fixedday,$regpay,$daily,$has_bdayleave,$minimum_wage);
				$info[$eid]['substitute'] = 0;
			}else{
				$perdept_salary = $this->comp->getPerdeptSalaryHistory($eid,$sdate);

				list($tardy_amount,$absent_amount,$workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$perdept_amt_arr,$hold_status,$x,$x,$forFinalPay,$conf_base_id,$excess_min) = $this->comp->getTardyAbsentSummaryTeaching($eid,$tnt,$schedule,$quarter,$sdate,$edate,$hourly,$lechour,$labhour,$rlehour,$perdept_salary);
				list($info[$eid]['salary'], $info[$eid]['teaching_pay']) 	=  $this->comp->computeTeachingCutoffSalary($workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$hourly,$lechour,$labhour,$rlehour,$fixedday,$regpay,$perdept_amt_arr,$hold_status);

				list($project_hol_pay, $sub_hol_pay, $suspension_pay) = $this->comp->getProjectHolidayPay($payroll_cutoff_id,$lechour,$labhour,$rlehour,$eid,$sdate);

				$this->income->saveHolidayPay($project_hol_pay, $eid, $sdate, $edate, $quarter);
				$this->income->saveSuspensionPay($suspension_pay, $eid, $sdate, $edate, $quarter);
			}
		}

		///< pag wala attendance - wala salary,tardy,absent pero papasok pa rin sa payroll - maiiwan mga income nya (DOUBLE CHECKING)
		if( !$this->hasAttendanceConfirmed($tnt,array('employeeid'=>$eid,'status'=>'PROCESSED','forcutoff'=>'1','payroll_cutoffstart'=>$sdate,'payroll_cutoffend'=>$edate,'quarter'=>$quarter)) ){
			$info[$eid]['salary'] = $tardy_amount = $absent_amount = 0;
			$perdept_amt_arr = array();
		}

		// $info[$eid]['overtime'] = $this->comp->computeOvertime($eid,$tnt,$schedule,$quarter,$sdate,$edate,$hourly);  ///< TO DO : INCLUDE OVERTIME IN COMPUTATIONS (income, tax, gross pay , etc)
		list($info[$eid]['overtime'],$ot_det) = $this->comp->computeOvertime2($eid,$tnt,$hourly,$conf_base_id,$employmentstat);
		/*check cutoff if no late and undertime*/

		$is_flexi = $this->attendance->isFlexiNoHours($eid);
		if($this->validateDTRCutoff($sdate, $edate) || $is_flexi > 0) $tardy_amount = $absent_amount = 0;

		$info[$eid]['tardy'] 		= $tardy_amount;
		$info[$eid]['absents'] 		= $absent_amount;

		///<  compute and save other income
		// $arr_adj_to_add = $this->comp->computeOtherIncomeAdj($eid,$payroll_cutoff_id);
		$this->comp->computeEmployeeOtherIncome($eid,$sdate,$edate,$tnt,$schedule,$quarter,$perdept_salary,$regpay);
		if(!$fixedday && $tnt=="teaching") $this->comp->computeCOLAIncome($eid,$sdate,$edate,$schedule,$quarter,$workdays,$absentdays);
		// $this->comp->computeLongevity($eid,$sdate,$edate,$tnt,$schedule,$quarter);
		
		///< income
		list($arr_income_config,$info[$eid]['income'],$totalincome,$str_income) = $this->comp->computeEmployeeIncome($eid,$schedule,$quarter,$sdate,$edate,$arr_income_config,$payroll_cutoff_id);
		$getTotalNotIncludedInGrosspay = $this->getTotalNotIncludedInGrosspay($info[$eid]['income']);
		///< income adjustment
		list($arr_income_adj_config,$info[$eid]['income_adj'],$totalincome,$str_income_adj) = $this->comp->computeEmployeeIncomeAdj($eid,$schedule,$quarter,$sdate,$edate,$arr_income_adj_config,$totalincome,$payroll_cutoff_id);

		//<!--GROSS PAY-->
		$info[$eid]['grosspay'] = ($info[$eid]['salary'] + $info[$eid]['teaching_pay'] + $totalincome + $info[$eid]['overtime'] ) - $absent_amount - $tardy_amount;

		list($prevSalary,$prevGrosspay) = $this->getPrevCutoffSalary(date('Y-m',strtotime($sdate)),$quarter,$eid);
		$prev_teaching_pay = $this->getPrevCutoffTeachingPay(date('Y-m',strtotime($sdate)),$quarter,$eid);
		$info[$eid]['netbasicpay'] = $info[$eid]['salary'] + $info[$eid]['teaching_pay'] - $absent_amount - $tardy_amount;

		///< fixed deduc
		list($arr_fixeddeduc_config,$info[$eid]['fixeddeduc'],$totalfix,$str_fixeddeduc,$ee_er) = $this->comp->computeEmployeeFixedDeduc($eid,$schedule,$quarter,$sdate,$edate,$arr_fixeddeduc_config,$info[$eid],$prevSalary,$prevGrosspay,$getTotalNotIncludedInGrosspay,$info[$eid]['salary'],$info[$eid]['teaching_pay'], $prev_teaching_pay, $regpay);


		///< loan
		list($arr_loan_config,$info[$eid]['loan'],$totalloan,$str_loan) = $this->comp->computeEmployeeLoan($eid,$schedule,$quarter,$sdate,$edate,$arr_loan_config);

		if($isFinal){
			list($_13th_month, $employee_benefits) = $this->income->compute13thMonthPay_2($eid,date('Y',strtotime($sdate)),$sdate,$edate,$info[$eid]['netbasicpay'],$info[$eid]['income'], true, $regpay);
			if($_13th_month > 0) $this->income->saveEmployeeOtherIncome($eid,$sdate,$edate,'5',$_13th_month,$schedule,$quarter);
			if($employee_benefits > 0) $this->income->saveEmployeeOtherIncome($eid,$sdate,$edate,'37',$employee_benefits,$schedule,$quarter);

			///< income (RECOMPUTE TO INCLUDE 13TH MONTH PAY)
			list($arr_income_config,$info[$eid]['income'],$totalincome,$str_income) = $this->comp->computeEmployeeIncome($eid,$schedule,$quarter,$sdate,$edate,$arr_income_config,$payroll_cutoff_id);
		}

		///< deduction
		list($arr_deduc_config,$info[$eid]['deduction'],$total_deducSub,$total_deducAdd,$str_deduc) = $this->comp->computeEmployeeDeduction($eid,$schedule,$quarter,$sdate,$edate,$arr_deduc_config,$arr_deduc_config_arithmetic);

		///< TAX COMPUTATION
		$wh_tax = $this->comp->getExistingWithholdingTax($eid, $edate);
		if($wh_tax!="") $info[$eid]['whtax'] = $wh_tax;
		else $info[$eid]['whtax']  = $this->comp->computeWithholdingTax($schedule,$dependents, $regpay, $info[$eid]['income'],$info[$eid]['income_adj'],$info[$eid]['deduction'],$info[$eid]['fixeddeduc'],$info[$eid]['overtime'], $info[$eid]['tardy'], $info[$eid]['absents']);


		//<!--NET PAY-->
		$info[$eid]['netpay'] = ($info[$eid]['grosspay'] - $totalloan - $totalfix - $total_deducSub - $info[$eid]['whtax']);

		$info[$eid]['isHold'] = 0;

		///< save to computed table
		$data_tosave = $data_tosave_oth = array();
		$data_tosave['cutoffstart'] 	= $sdate;
		$data_tosave['cutoffend'] 		= $edate;
		$data_tosave['employeeid'] 		= $eid;
		$data_tosave['schedule'] 		= $schedule;
		$data_tosave['quarter'] 		= $quarter;
		$data_tosave['salary'] 			= $info[$eid]['salary'];
		$data_tosave['teaching_pay'] 			= $info[$eid]['teaching_pay'];
		$data_tosave['overtime'] 		= $info[$eid]['overtime'];
		$data_tosave['substitute'] 		= isset($info[$eid]['substitute']) ? $info[$eid]['substitute'] : "";
		$data_tosave['income'] 			= $str_income;
		$data_tosave['income_adj'] 		= $str_income_adj;
		$data_tosave['fixeddeduc'] 		= $str_fixeddeduc;
		$data_tosave['otherdeduc'] 		= $str_deduc;
		$data_tosave['loan'] 			= $str_loan;
		$data_tosave['withholdingtax'] 	= $info[$eid]['whtax'];
		$data_tosave['tardy'] 			= $info[$eid]['tardy'];
		$data_tosave['absents'] 		= $info[$eid]['absents'];
		$data_tosave['netbasicpay'] 	= $info[$eid]['netbasicpay'];
		$data_tosave['gross'] 			= $info[$eid]['grosspay'];
		$data_tosave['net'] 			= $info[$eid]['netpay'];
		$data_tosave['isHold'] 			= $info[$eid]['isHold'];

		$data_tosave_oth['perdept_amt_arr'] = $perdept_amt_arr;
		$data_tosave_oth['ee_er'] 		= $ee_er;
		$data_tosave_oth['ot_det'] 		= $ot_det;

		$info[$eid]['base_id'] = $this->savePayrollCutoffSummaryDraft($data_tosave,$data_tosave_oth);
		// echo "<pre>"; print_r($arr_income_config); die;
		return array($info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config);
	}

	function hasAttendanceConfirmed($teachingtype='',$filter=array()){
		$hasData = false;
		$tbl = '';
		if($teachingtype == 'teaching') $tbl = 'attendance_confirmed';
		elseif($teachingtype == 'nonteaching') $tbl = 'attendance_confirmed_nt';
		if($tbl){
			$this->db->select('id');
			$res = $this->db->get_where($tbl,$filter);
			if($res->num_rows() > 0) $hasData = true;
		}
		return $hasData;
	}
	
	function getPayrollSummary($status='',$cutoffstart='',$cutoffend='',$schedule='',$quarter='',$employeeid='',$checkCount=false,$status2='',$bank=''){
		$wC = '';
		if($employeeid)					$wC .= " AND employeeid='$employeeid'";
		if($bank)						$wC .= " AND bank='$bank'";
		if($status && $status2) 		$wC .= " AND (status='$status' OR status='$status2')";
		elseif($status && !$status2)	$wC .= " AND status='$status'";
		$utwc = '';
        // $utdept = $this->session->userdata("department");
        // $utoffice = $this->session->userdata("office");
        // if($this->session->userdata("usertype") == "ADMIN"){
		// 	if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (deptid, '$utdept')";
		// 	if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (office, '$utoffice')";
		// 	if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (deptid, '$utdept') OR FIND_IN_SET (office, '$utoffice'))";
		// 	if(!$utdept && !$utoffice) $utwc =  " AND employeeid = 'nosresult'";
		// 	$usercampus = $this->extras->getCampusUser();
		// 	$utwc .= " AND FIND_IN_SET (campusid,'$usercampus') ";
        // }
        if($utwc) $wC .= " AND employeeid IN (SELECT employeeid FROM employee WHERE 1 $utwc)";
		if($checkCount){
			$cutoff_exist_q = $this->db->query("SELECT count(id) AS existcount from payroll_computed_table WHERE cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND schedule='$schedule' AND quarter='$quarter' $wC");
			if($cutoff_exist_q->num_rows() > 0) return $cutoff_exist_q->row(0)->existcount;
			else 								return 0;
		}else{
			$payroll_q = $this->db->query("SELECT * FROM payroll_computed_table WHERE cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND schedule='$schedule' AND quarter='$quarter' $wC");
			return $payroll_q;
		}
	}

	function getPayrollSummarySub($status='',$cutoffstart='',$cutoffend='',$schedule='',$quarter='',$employeeid='',$checkCount=false,$status2='',$bank=''){
		$wC = '';
		if($employeeid)					$wC .= " AND employeeid='$employeeid'";
		if($bank)						$wC .= " AND bank='$bank'";
		if($status && $status2) 		$wC .= " AND (status='$status' OR status='$status2')";
		elseif($status && !$status2)	$wC .= " AND status='$status'";
		$utwc = '';
        // $utdept = $this->session->userdata("department");
        // $utoffice = $this->session->userdata("office");
        // if($this->session->userdata("usertype") == "ADMIN"){
		// 	if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (deptid, '$utdept')";
		// 	if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (office, '$utoffice')";
		// 	if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (deptid, '$utdept') OR FIND_IN_SET (office, '$utoffice'))";
		// 	if(!$utdept && !$utoffice) $utwc =  " AND employeeid = 'nosresult'";
		// 	$usercampus = $this->extras->getCampusUser();
		// 	$utwc .= " AND FIND_IN_SET (subcampusid,'$usercampus') ";
        // }
        if($utwc) $wC .= " AND employeeid IN (SELECT employeeid FROM employee WHERE 1 $utwc)";
		if($checkCount){
			$cutoff_exist_q = $this->db->query("SELECT count(id) AS existcount from sub_payroll_computed_table WHERE cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND schedule='$schedule' AND quarter='$quarter' $wC");
			if($cutoff_exist_q->num_rows() > 0) return $cutoff_exist_q->row(0)->existcount;
			else 								return 0;
		}else{
			$payroll_q = $this->db->query("SELECT * FROM sub_payroll_computed_table WHERE cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND schedule='$schedule' AND quarter='$quarter' $wC");
			return $payroll_q;
		}
	}

	function getPrevCutoffSalary($cutoff_month='',$quarter=1,$employeeid=''){
		$prevSalary = $prevGrosspay = 0;
		if($cutoff_month){
			if($quarter > 1){
				$res = $this->db->query("SELECT salary,gross FROM payroll_computed_table WHERE employeeid='$employeeid' AND (DATE_FORMAT(cutoffstart,'%Y-%m')='$cutoff_month' OR DATE_FORMAT(cutoffend,'%Y-%m')='$cutoff_month') AND quarter=1 LIMIT 1");
				if($res->num_rows() > 0){
					$prevSalary = $res->row(0)->salary;
					$prevGrosspay = $res->row(0)->gross;
				}
			}
		}else{

		}

		return array($prevSalary,$prevGrosspay);
	}

	function getPrevCutoffTeachingPay($cutoff_month='',$quarter=1,$employeeid=''){
		$prev_teaching_pay = 0;
		if($cutoff_month){
			if($quarter > 1){
				$res = $this->db->query("SELECT teaching_pay FROM payroll_computed_table WHERE employeeid='$employeeid' AND DATE_FORMAT(cutoffstart,'%Y-%m')='$cutoff_month' AND quarter=1 LIMIT 1");
				if($res->num_rows() > 0){
					$prev_teaching_pay = $res->row(0)->teaching_pay;
				}
			}
		}else{

		}

		return $prev_teaching_pay;
	}

	///< PENDING STATUS
	function savePayrollCutoffSummaryDraft($data=array(),$data_oth=array()){
		$this->load->model('utils');
		// $data['addedby']   = $this->session->userdata('username');
		$base_id = $this->utils->insertSingleTblData('payroll_computed_table',$data);
		if($base_id){

			if(sizeof($data_oth['ee_er']) > 0){
				foreach ($data_oth['ee_er'] as $code => $amt) {
					$amt['EE'] = round($amt['EE'],2);
					$amt['EC'] = round($amt['EC'],2);
					$amt['ER'] = round($amt['ER'],2);
					$this->utils->insertSingleTblData('payroll_computed_ee_er',array('base_id'=>$base_id,'code_deduction'=>$code,'EE'=>$amt['EE'],'EC'=>$amt['EC'],'ER'=>$amt['ER'],'provident_er'=>$amt['provident_er']));
				}
			}
			

			if(sizeof($data_oth['perdept_amt_arr']) > 0){ ///< perdept amount details saving
				foreach ($data_oth['perdept_amt_arr'] as $aimsdept => $leclab_arr) {
					foreach ($leclab_arr as $type => $amt) {
						$this->utils->insertSingleTblData('payroll_computed_perdept_detail',array('base_id'=>$base_id,'type'=>$type,'aimsdept'=>$aimsdept,'work_amount'=>$amt['work_amount'],'late_amount'=>$amt['late_amount'],'deduc_amount'=>$amt['deduc_amount']));
					}
				}
			}

			if(sizeof($data_oth['ot_det']) > 0){
				foreach ($data_oth['ot_det'] as $att_baseid => $amt) {
					$amt = round($amt,2);
					$this->utils->insertSingleTblData('payroll_computed_overtime',array('base_id'=>$base_id,'att_baseid'=>$att_baseid,'amount'=>$amt));
				}
			}

		} //< end main if

		return $base_id;
	}

	///< SAVED STATUS
	function savePayrollCutoffSummary($empid = "",$cutoffstart="", $cutoffend="", $schedule = "",$quarter = "",$status="SAVED",$bank=''){
		$success = false;
		$other_update = "";
		if($status == 'PROCESSED') $other_update = ", date_processed = CURDATE()";
		$update_res = $this->db->query("UPDATE payroll_computed_table SET  bank='$bank', status='$status' $other_update
										WHERE employeeid='$empid' AND schedule='$schedule' AND cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND quarter='$quarter'");
		if($update_res) $success = true;
		return $success;
	}

	///< SAVED STATUS FOR SUBSITE
	function savePayrollCutoffSummarySub($empid = "",$cutoffstart="", $cutoffend="", $schedule = "",$quarter = "",$status="SAVED",$bank=''){
		$success = false;
		$other_update = "";
		if($status == 'PROCESSED') $other_update = ", date_processed = CURDATE()";
		$update_res = $this->db->query("UPDATE sub_payroll_computed_table SET  bank='$bank', status='$status' $other_update
										WHERE employeeid='$empid' AND schedule='$schedule' AND cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND quarter='$quarter'");
		if($update_res) $success = true;
		return $success;
	}

	function saveEmpLoanPayment($pct_id, $employeeid, $cutoffstart, $cutoffend, $schedule, $quarter, $loans_list){
		$this->load->model('loan');
		$arr_loan = array();
		if($loans_list){
			foreach (explode("/", $loans_list) as $loans) {
				list($id, $amount) = explode("=", $loans);

				$arr_loan[$id] = $amount;
			}
		}

		if(count($arr_loan) > 0){
			foreach ($arr_loan as $code_loan => $loan_amount) {
				$q_emp_loan = $this->loan->getEmployeeLoanPayment($employeeid, $code_loan, $cutoffstart, $cutoffend, $schedule, $quarter);
				
				foreach ($q_emp_loan as $row) {
					$base_id = $row->id;

					$this->loan->processEmployeePayment($base_id, $loan_amount, $pct_id, $employeeid, $code_loan);
				}
				
			}
		}
	}

	///< PROCESSED STATUS
	function finalizePayrollCutoffSummary($empid = "",$cutoffstart="", $cutoffend="", $schedule = "",$quarter = ""){
		$user = $this->session->userdata('username');
		$todate = date('Y-m-d');
		$update_res = $this->db->query("UPDATE payroll_computed_table SET status='PROCESSED', finalized_by = '$user', finalized_date = '$todate'
										WHERE employeeid='$empid' AND schedule='$schedule' AND cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND quarter='$quarter'");
		$success = false;

		if($update_res){
			$sel_res = $this->db->query("SELECT * FROM payroll_computed_table WHERE employeeid='$empid' AND schedule='$schedule' AND cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND quarter='$quarter'");

			if($sel_res->num_rows() > 0){

				$pct_id 		= $sel_res->row()->id;
				$loans 			= $sel_res->row()->loan;
				$income 		= $sel_res->row()->income;
				$income_adj 		= $sel_res->row()->income_adj;
				$deductfixed 	= $sel_res->row()->fixeddeduc;
				$deductothers 	= $sel_res->row()->otherdeduc;
				$fixeddeduc_arr = array_sum($this->constructArrayListFromComputedTable($sel_res->row()->fixeddeduc));
				$grosssalary 	=((int) $sel_res->row()->salary + (int) $sel_res->row()->income) - ((int) $sel_res->row()->otherdeduc+(int) $sel_res->row()->loan + $fixeddeduc_arr);
				$netsalary 		=((int) $sel_res->row()->salary + (int) $sel_res->row()->income) - (((int) $sel_res->row()->absents + (int) $sel_res->row()->tardy));

				$this->saveEmpLoanPayment($pct_id, $empid, $cutoffstart, $cutoffend, $schedule, $quarter, $loans);

				$query = $this->db->query("INSERT INTO payroll_computed_table_history 
				                                    (employeeid,cutoffstart,cutoffend,schedule,quarter,salary,income,overtime,withholdingtax,fixeddeduc,otherdeduc,loan,tardy,absents,addedby) 
				                            (SELECT employeeid,cutoffstart,cutoffend,schedule,quarter,salary,income,overtime,withholdingtax,fixeddeduc,otherdeduc,loan,tardy,absents,'$user'
				                            FROM payroll_computed_table WHERE employeeid='$empid' AND schedule='$schedule' AND cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND quarter='$quarter')
				                            ");


				$uptloan      =   explode("/",$loans);
				$uptincome    =   explode("/",$income);
				$uptincome_adj    =   explode("/",$income_adj);
				$uptcontri    =   explode("/",$deductfixed);
				$uptothded    =   explode("/",$deductothers);

				$this->finalizeLoan($empid,$schedule,$quarter,$cutoffstart,$cutoffend,$loans,$uptloan,$user);
				$this->finalizeIncome($empid,$schedule,$quarter,$cutoffstart,$cutoffend,$income,$uptincome,$user);
				$this->finalizeIncomeAdj($empid,$schedule,$quarter,$cutoffstart,$cutoffend,$income_adj,$uptincome_adj,$user);
				$this->finalizeFixedDeduction($empid,$schedule,$quarter,$cutoffstart,$cutoffend,$deductfixed,$uptcontri,$user);
				$this->finalizeOtherDeduction($empid,$schedule,$quarter,$cutoffstart,$cutoffend,$deductothers,$uptothded,$user);

				if($query) $success = true;

			}
		}

		return $success;
	}

	function finalizeLoan($eid='',$schedule = "",$quarter = "",$sdate = "",$edate = "",$loans='',$uptloan=array(),$user=''){
        if(count($uptloan) > 0 && !empty($loans)){
            for($x = 0; $x<count($uptloan); $x++){
                $code = explode("=",$uptloan[$x]);
                $qloan = $this->db->query("SELECT nocutoff,amount,famount FROM employee_loan WHERE employeeid='$eid' AND code_loan='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
                if($qloan->num_rows() > 0){
                    $amount = $qloan->row(0)->amount; 
                    $famount = $qloan->row(0)->famount; 
                    $nocutoff = $qloan->row(0)->nocutoff;
                    $this->load->model("loan");
                	$skip_loan = $this->loan->checkIfSkipInLoanPayment($eid, $code[0]);
                	$mode = "CUTOFF";
                	if($skip_loan){
                		$mode = "HOLD";
                	}else{
                    	$nocutoff = $qloan->row(0)->nocutoff-1; 
                	}

                    if($nocutoff >= 0){
                        $qloan = $this->db->query("UPDATE employee_loan SET nocutoff='$nocutoff' WHERE employeeid='$eid' AND code_loan='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
                        $ploan = $this->db->query("INSERT INTO payroll_process_loan 
                                                            (employeeid,code_loan,cutoffstart,cutoffend,amount,schedule,cutoff_period,user) 
                                                    VALUES  ('$eid','".$code[0]."','$sdate','$edate','".$code[1]."','$schedule','$quarter','$user')
                                                    ");
                    
						$hloan = $this->db->query("SELECT * FROM employee_loan_history WHERE employeeid = '".$eid."' AND code_loan = '".$code[0]."' AND schedule='$schedule' ORDER BY cutoffstart DESC LIMIT 1");
						if($hloan->num_rows() > 0){
							if($nocutoff != 0){ 
								$balance = $hloan->row(0)->remainingBalance - $amount;
								$this->db->query("INSERT INTO employee_loan_history (employeeid,code_loan,cutoffstart,cutoffend,startBalance,amount,remainingBalance,schedule,cutoff_period,mode,user)
								VALUES('".$eid."','".$code[0]."','$sdate','$edate',".$hloan->row(0)->remainingBalance.",".$amount.",".$balance.",'".$schedule."','".$quarter."','CUTOFF','".$user."')");
							}
							else {
								$balance = $hloan->row(0)->remainingBalance - $famount;
								$this->db->query("INSERT INTO employee_loan_history (employeeid,code_loan,cutoffstart,cutoffend,startBalance,amount,remainingBalance,schedule,cutoff_period,mode,user)
								VALUES('".$eid."','".$code[0]."','$sdate','$edate',".$hloan->row(0)->remainingBalance.",".$famount.",".$balance.",'".$schedule."','".$quarter."','CUTOFF','".$user."')");
							}
						}
					}						
                }
            }
        }
	}

	function finalizeIncome($eid='',$schedule = "",$quarter = "",$sdate = "",$edate = "",$income='',$uptincome=array(),$user=''){
		if(count($uptincome) > 0 && !empty($income)){
		    for($x = 0; $x<count($uptincome); $x++){
		        $code = explode("=",$uptincome[$x]);
		        $qincome = $this->db->query("SELECT nocutoff FROM employee_income WHERE employeeid='$eid' AND code_income='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
		        if($qincome->num_rows() > 0){
		            $nocutoff = $qincome->row(0)->nocutoff-1; 
		            if($nocutoff > 0){
		                $qincome = $this->db->query("UPDATE employee_income SET nocutoff='$nocutoff' WHERE employeeid='$eid' AND code_income='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
		                $pincome = $this->db->query("INSERT INTO payroll_process_income 
		                                                    (employeeid,code_income,cutoffstart,cutoffend,amount,schedule,cutoff_period,remainingCutoff,user) 
		                                            VALUES  ('$eid','".$code[0]."','$sdate','$edate','".$code[1]."','$schedule','$quarter','$nocutoff','$user')
		                                            ");
		            } 
		        }
				$remainingCutoff = $this->db->query("SELECT * FROM employee_income WHERE employeeid = '$eid' AND code_income = '{$code[0]}' ");
				if($remainingCutoff->num_rows() > 0){
					$remainingCutoff = $remainingCutoff->row()->nocutoff;
					// if($remainingCutoff == 0) $this->db->query("DELETE FROM employee_income WHERE employeeid = '$eid' AND code_income = '{$code[0]}' ");
				}
		    }
		}

		
	}

	function finalizeIncomeAdj($eid='',$schedule = "",$quarter = "",$sdate = "",$edate = "",$income='',$uptincome=array(),$user=''){
		if(count($uptincome) > 0 && !empty($income)){
		    for($x = 0; $x<count($uptincome); $x++){
		        $code = explode("=",$uptincome[$x]);
		        $qincome = $this->db->query("SELECT nocutoff FROM employee_income_adj WHERE employeeid='$eid' AND code_income='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
		        if($qincome->num_rows() > 0){
		            $nocutoff = $qincome->row(0)->nocutoff-1; 
		            if($nocutoff >= 0){
		                $qincome = $this->db->query("UPDATE employee_income_adj SET nocutoff='$nocutoff' WHERE employeeid='$eid' AND code_income='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
		                $pincome = $this->db->query("INSERT INTO payroll_process_income_adj 
		                                                    (employeeid,code_income,cutoffstart,cutoffend,amount,schedule,cutoff_period,user) 
		                                            VALUES  ('$eid','".$code[0]."','$sdate','$edate','".$code[1]."','$schedule','$quarter','$user')
		                                            ");
		            } 
		        }
		    	$remainingCutoff = $this->db->query("SELECT * FROM employee_income_adj WHERE employeeid = '$eid' AND code_income = '{$code[0]}' ")->row()->nocutoff;
				if($remainingCutoff == 0) $this->db->query("DELETE FROM employee_income_adj WHERE employeeid = '$eid' AND code_income = '{$code[0]}' ");
		    }
		}
	}

	function finalizeFixedDeduction($eid='',$schedule = "",$quarter = "",$sdate = "",$edate = "",$deductfixed='',$uptcontri=array(),$user=''){
		if(count($uptcontri) > 0 && !empty($deductfixed)){
		    for($x = 0; $x<count($uptcontri); $x++){
		        $code = explode("=",$uptcontri[$x]);
		        list($tcontri,$er,$ec)   =  $this->payroll->payroll_collection_contribution($code[1]);
		            $pcontri = $this->db->query("INSERT INTO payroll_process_contribution 
		                                                    (employeeid,code_deduct,cutoffstart,cutoffend,amount,schedule,cutoff_period,user) 
		                                            VALUES  ('$eid','".strtoupper($code[0])."','$sdate','$edate','".$code[1]."','$schedule','$quarter','$user')
		                                        "); 
		                       $this->db->query("INSERT INTO payroll_process_contribution_collection 
		                                                    (employeeid,code_deduct,cutoffstart,cutoffend,amount,schedule,cutoff_period,user,ec,amounter,amounttotal) 
		                                            VALUES  ('$eid','".strtoupper($code[0])."','$sdate','$edate','".$code[1]."','$schedule','$quarter','$user','$ec','$er','$tcontri')
		                                        "); 
		                                        
		    }
		}
	}

	function finalizeOtherDeduction($eid='',$schedule = "",$quarter = "",$sdate = "",$edate = "",$deductothers='',$uptothded=array(),$user=''){
		if(count($uptothded) > 0 && !empty($deductothers)){
            for($x = 0; $x<count($uptothded); $x++){
                $code = explode("=",$uptothded[$x]);
                $qincome = $this->db->query("SELECT nocutoff FROM employee_deduction WHERE employeeid='$eid' AND code_deduction='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");
                if($qincome->num_rows() > 0){
                $nocutoff = $qincome->row(0)->nocutoff-1; 
                    if($nocutoff >= 0){
                        $qincome = $this->db->query("UPDATE employee_deduction SET nocutoff='$nocutoff' WHERE employeeid='$eid' AND code_deduction='".$code[0]."' AND schedule='$schedule' AND FIND_IN_SET(cutoff_period,'$quarter,3')");                                        
                        $pcontri = $this->db->query("INSERT INTO payroll_process_otherdeduct 
                                                                (employeeid,code_deduct,cutoffstart,cutoffend,amount,schedule,cutoff_period,remainingCutoff,user) 
                                                        VALUES  ('$eid','".strtoupper($code[0])."','$sdate','$edate','".$code[1]."','$schedule','$quarter','$nocutoff','$user')
                                                    "); 
                    }             
                }                                                                                           
            	$remainingCutoff = $this->db->query("SELECT * FROM employee_deduction WHERE employeeid = '$eid' AND code_deduction = '{$code[0]}' ")->row()->nocutoff;
				if($remainingCutoff == 0) $this->db->query("DELETE FROM employee_deduction WHERE employeeid = '$eid' AND code_deduction = '{$code[0]}' ");
            }
        }
	}



	function getProcessedPayrollSummary($emplist=array(), $sdate='',$edate='',$schedule='',$quarter='',$status='PROCESSED',$bank=''){
		//< initialize needed info ---------------------------------------------------
		$arr_info    = $arr_income_config = $arr_incomeoth_config = $arr_deduc_config = $arr_fixeddeduc_config = $arr_loan_config = array();

		///< ------------------------------ income config ------------------------------------------------------------
		$income_config_q = $this->payroll->displayIncome();
		$arr_income_config = $this->constructArrayListFromStdClass($income_config_q,'id','description');

		$arr_income_adj_config = $arr_income_config;
		$arr_income_adj_config['SALARY'] = array('description'=>'SALARY','hasData'=>0);

		///< ------------------------------ incomeoth config ---------------------------------------------------------------
		$incomeoth_config_q = $this->payroll->displayIncomeOth();
		$arr_incomeoth_config = $this->constructArrayListFromStdClass($incomeoth_config_q,'id','description');

		///< ------------------------------ fixed deduction config ----------------------------------------------------
		$fixeddeduc_config_q = $this->db->query("SELECT code_deduction,description FROM deductions");
		$arr_fixeddeduc_config = $this->constructArrayListFromStdClass($fixeddeduc_config_q,'code_deduction','description');


		///< ------------------------------ deduction config ----------------------------------------------------------
		$deduction_config_q = $this->payroll->displayDeduction();
		$arr_deduc_config = $this->constructArrayListFromStdClass($deduction_config_q,'id','description');


		///< ------------------------------ loan config ---------------------------------------------------------------
		$loan_config_q = $this->payroll->displayLoan();
		$arr_loan_config = $this->constructArrayListFromStdClass($loan_config_q,'id','description');


		foreach ($emplist as $row) {
			$post_to_athena = 1;
			$empid = $row->employeeid;
			
			///< check for computation
			$res = $this->getPayrollSummary($status,$sdate,$edate,$schedule,$quarter,$empid,false,'',$bank);
			if($res->num_rows() > 0){
			// echo "<pre>"; print_r($this->db->last_query());
			// 	die;
				$regpay =  $row->regpay;
				$dependents = $row->dependents;

				$arr_info[$empid]['income'] = $arr_info[$empid]['income_adj'] = $arr_info[$empid]['deduction'] = $arr_info[$empid]['fixeddeduc'] = $arr_info[$empid]['loan'] = array();

				$arr_info[$empid]['fullname'] 	= isset($row->fullname) ? $row->fullname : '';
				$arr_info[$empid]['deptid'] 	= isset($row->deptid) ? $row->deptid : '';
				$arr_info[$empid]['office'] 	= isset($row->office) ? $row->office : '';
				$res 							= $res->row(0); 

				$arr_info[$empid]['base_id'] 	= $res->id; 

				$arr_info[$empid]['salary'] 	= $res->salary;
				$arr_info[$empid]['overtime'] 	= $res->overtime;
				$arr_info[$empid]['tardy'] 		= $res->tardy;
				$arr_info[$empid]['absents'] 	= $res->absents;
				$arr_info[$empid]['whtax'] 		= $res->withholdingtax;
				$arr_info[$empid]['editedby'] 	= $res->editedby;
				$arr_info[$empid]['netbasicpay'] = $res->netbasicpay;
				$arr_info[$empid]['grosspay'] 	= $res->gross;
				$arr_info[$empid]['overload'] 	= isset($res->overload) ? $res->overload : 0;
				$arr_info[$empid]['netpay'] 	= $res->net;
				$arr_info[$empid]['isHold'] 	= $res->isHold;
				$arr_info[$empid]['teaching_pay'] 	= $res->teaching_pay;
				$arr_info[$empid]['finalized_by'] 	= $res->finalized_by;
				$arr_info[$empid]['finalized_date'] 	= $res->finalized_date;
				$arr_info[$empid]['posted_date'] 	= $res->posted_date;
				$arr_info[$empid]['posted_by'] 	= $res->posted_by;
				$arr_info[$empid]['acknowledged_date'] 	= $res->acknowledged_date;
				$arr_info[$empid]['acknowledged_by'] 	= $res->acknowledged_by;
				$arr_info[$empid]['athena_status'] 	= $res->athena_status;

				//< income
				$income_arr 				= $this->constructArrayListFromComputedTable($res->income);
				$arr_info[$empid]['income'] = $income_arr;
				foreach ($income_arr as $k => $v) {
					if(!$this->getGLAccount("payroll_income_config", $k)) $post_to_athena = 0;
					$arr_income_config[$k]['hasData'] = 1;
				}

				$income_adj_arr 				= $this->constructArrayListFromComputedTable($res->income_adj);
				$arr_info[$empid]['income_adj'] = $income_adj_arr;
				foreach ($income_adj_arr as $k => $v) {
					if(!$this->getGLAccount("payroll_income_config", $k)) $post_to_athena = 0;
					$arr_income_adj_config[$k]['hasData'] = 1;
				}

				///< fixed deduc
		        $fixeddeduc_arr = $this->constructArrayListFromComputedTable($res->fixeddeduc);
		        $arr_info[$empid]['fixeddeduc'] = $fixeddeduc_arr;
		        foreach ($fixeddeduc_arr as $k => $v) {
					if(!$this->getGLAccountReg("deductions", $k)) $post_to_athena = 0;
					$arr_fixeddeduc_config[$k]['hasData'] = 1;
				}

		        ///< deduc
		        $deduc_arr = $this->constructArrayListFromComputedTable($res->otherdeduc);
		        $arr_info[$empid]['deduction'] = $deduc_arr;
		        foreach ($deduc_arr as $k => $v) {
					if(!$this->getGLAccount("payroll_deduction_config", $k)) $post_to_athena = 0;
					$arr_deduc_config[$k]['hasData'] = 1;
				}

		        ///< loan
		        $loan_arr = $this->constructArrayListFromComputedTable($res->loan);
		        $arr_info[$empid]['loan'] = $loan_arr;
		        foreach ($loan_arr as $k => $v) {
					if(!$this->getGLAccount("payroll_loan_config", $k)) $post_to_athena = 0;
					$arr_loan_config[$k]['hasData'] = 1;
				}
			}

		} //end loop emplist
		
		$data['emplist'] = $arr_info;
		$data['income_config'] = $arr_income_config;
		$data['income_adj_config'] = $arr_income_adj_config;
		$data['incomeoth_config'] = $arr_incomeoth_config;
		$data['fixeddeduc_config'] = $arr_fixeddeduc_config;
		$data['deduction_config'] = $arr_deduc_config;
		$data['loan_config'] = $arr_loan_config;
		$data['sdate'] = $sdate;
		$data['edate'] = $edate;

		return $data;
	}


	function getAtmPayrolllist($emp_bank='', $cutoffstart, $status = 'PROCESSED', $sortby = '', $campus = '', $company = '', $deptid = '', $office = '', $teachingtype = '', $employeeid=''){
		$where_clause = $order_by = $account_no = $bank_id = '';
		if($employeeid && $employeeid[0] && is_array($employeeid)){
			$emplist = "'" . implode( "','", $employeeid ) . "'";
			$where_clause .= " AND a.`employeeid` IN ($emplist) ";
		}else{
			if($employeeid){
				if(!in_array("", $employeeid)){
					if($employeeid) $where_clause .= " AND a.`employeeid` = '$employeeid' ";
				}
			}
		}
		if($emp_bank) $where_clause .= " AND c.`bank`='$emp_bank' ";
		if($teachingtype){ 
            if($teachingtype == "trelated") $where_clause .= " AND a.teachingtype='teaching' AND a.trelated = 1";
            else $where_clause .= " AND a.teachingtype='$teachingtype'";
        }
		if($deptid) $where_clause .= " AND a.`deptid`='$deptid' ";
		if($office) $where_clause .= " AND a.`office`='$office' ";
		if($emp_bank) $where_clause .= " AND c.`bank`='$emp_bank' ";
		if($campus && $campus != 'all') $where_clause .= " AND a.`campusid`='$campus' ";
		if($company && $company != 'all') $where_clause .= ' AND a.`company_campus`="'.$company.'" ';
		
		if($sortby == 'alphabetical') $order_by = " ORDER BY a.lname";
		if($sortby == 'department') $order_by = " ORDER BY b.description";
		$res = $this->db->query("SELECT a.employeeid, lname, mname, fname, c.`bank`, c.`net`, a.emp_accno/*, b.description*/,a.company_campus
							FROM employee a
							-- INNER JOIN code_office b ON b.`code`=a.`office`
							INNER JOIN payroll_computed_table c ON c.`employeeid`=a.`employeeid`
							WHERE c.`status` = '$status' AND cutoffstart='$cutoffstart' $where_clause $order_by");
		$data = array();
		if($res->num_rows() > 0){
			foreach ($res->result() as $key => $row) {
				$emp_bank = $this->extensions->getEmpBank($row->employeeid);
				$emp_bank = explode("/", $emp_bank);
				if($emp_bank){
					foreach($emp_bank as $bank){
						$fbank = explode("=", $bank);
						if($row->bank == $fbank[0]) $account_no = isset($fbank[1]) ? $fbank[1] : '';
						if($fbank[0] && isset($fbank[0])) $bank_id = $fbank[0];
					}
				}

				$comp_code = $account_number = $bank_name = $branch = "";
				$bank_det = $this->db->query("SELECT * FROM code_bank_account WHERE code = '$bank_id'");
				if($bank_det->num_rows() > 0){
					$comp_code = $bank_det->row()->comp_code; 
					$account_number = $bank_det->row()->account_number; 
					$bank_name = $bank_det->row()->bank_name; 
					$branch = $bank_det->row()->branch; 
				}

				$fullname = $row->lname . ' ' . $row->fname . ' ' . substr($row->mname, 0,1) . '.';
				$data['list'][$row->employeeid] = array(
					'fullname'=>utf8_encode($fullname),
					'account_num'=>$account_no,
					'net_salary'=>$row->net,
					// 'description'=>$row->description,
					'company_campus'=>$row->company_campus,
						"fname" => $row->fname,
						"mname" => $row->mname,
						"lname" => $row->lname,
						"bank_id" => $bank_id,
						"comp_code" => $comp_code,
						"account_number" => $account_number,
						"bank_name" => $bank_name,
						"branch" => $branch
				);
			}
		}



		return $data;

	}


	///< Reglamentory Payment
	function getReglamentoryPaymentComputed($id='',$base_id='',$code_deduction=''){
		$wC = '';
		if($id)				$wC .= " AND id='$id'";
		if($base_id)		$wC .= " AND base_id='$base_id'";
		if($code_deduction)	$wC .= " AND code_deduction='$code_deduction'";
		$res = $this->db->query("SELECT * FROM payroll_computed_ee_er WHERE EE <> 0 $wC LIMIT 1");
		return $res;
	}

	function checkDeductionIfWithtax($key){
		$deduc_query = $this->db->query("SELECT taxable FROM payroll_deduction_config WHERE id = '$key'")->row()->taxable;
		return $deduc_query;
	}

	function checkIfPayrollSaved($payroll_start, $payroll_end, $employeeid){
		$query = $this->db->query("SELECT * FROM payroll_computed_table WHERE employeeid = '$employeeid' AND cutoffstart = '$payroll_start' AND cutoffend = '$payroll_end' ");
		if($query->num_rows() > 0){
			return $query->row()->status;
		}else{
			return FALSE;
		}
	}

	function getTotalNotIncludedInGrosspay($arr_income){
		$income = $this->extensions->getNotIncludedInGrosspayIncome();
		$total = 0;
		foreach($arr_income as $inc_key => $value){
			if(array_key_exists($inc_key, $income)) $total += $value;
		}

		return $total;
	}

	function validateDTRCutoff($sdate, $edate){
		$q_cutoff = $this->db->query("SELECT * FROM payroll_cutoff_config WHERE startdate = '$sdate' AND enddate = '$edate' ");
		return ($q_cutoff->row()->nodtr) ? true : false;
	}
	
	function getAbsentPerdept($empid,$cutoffstart='',$cutoffend=''){
		$query_perdeptAbsent = $this->db->query("SELECT * FROM attendance_confirmed a 
											INNER JOIN workhours_perdept b ON b.`base_id` = a.`id` 
											WHERE a.payroll_cutoffstart='$cutoffstart' 
											AND a.payroll_cutoffend='$cutoffend' 
											AND a.employeeid='$empid' ")->result_array();
		return $query_perdeptAbsent;
	}

	function getAbsentNonteaching($empid,$cutoffstart='',$cutoffend=''){
		$query_perdeptAbsent = $this->db->query("SELECT * FROM attendance_confirmed_nt 
											WHERE payroll_cutoffstart='$cutoffstart' 
											AND payroll_cutoffend='$cutoffend' 
											AND employeeid='$empid' ")->result_array();
		return $query_perdeptAbsent;
	}	

	function updateComputedEE_ORNum($id='',$base_id='',$code_deduction='',$or_number='',$datepaid='',$cutoff='',$schedule=''){
		$wC = "";
		$wC_arr = array();
		if($id) 			array_push($wC_arr, "id='$id'");
		if($base_id) 		array_push($wC_arr, "base_id='$base_id'");
		if($code_deduction)	array_push($wC_arr, "code_deduction='$code_deduction'");
		if(sizeof($wC_arr) > 0){
			$wC = " WHERE " . implode(' AND ', $wC_arr);
		}

		$update = "";
		if(!$datepaid)	$update .= " ,datepaid=NULL";
		else 			$update .= " ,datepaid='$datepaid'";
		
		if(!$cutoff)	$update .= " ,payroll_cutoff=NULL, schedule=NULL";
		else			$update .= " ,payroll_cutoff='$cutoff', schedule = '$schedule'";
		$username = $this->session->userdata("username");
		$date = date('Y-m-d h:i:s');
		
		$res = $this->db->query("UPDATE payroll_computed_ee_er SET or_number='$or_number', modified_by = '$username', modified_date = '$date' $update $wC");
		return $res;
	}

	function getEmployeeSalaryRate($regpay, $daily, $employeeid, $sdate){
		$p_history = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid = '$employeeid' AND date_effective <= '$sdate' ORDER BY date_effective DESC LIMIT 1");
		if($p_history->num_rows() > 0) return array($p_history->row()->semimonthly, $p_history->row()->daily);
		else return array($regpay, $daily);
	}

	function has_birthday_leave($sdate, $edate, $eid){
		return $this->db->query("SELECT * FROM leave_request WHERE fromdate BETWEEN '$sdate' AND '$edate' AND employeeid = '$eid'")->num_rows();
	}

	function minimum_wage($cutoffstart){
		$year = date("Y", strtotime($cutoffstart));
		$q_wage = $this->db->query("SELECT amount FROM payroll_wage_config WHERE year <= '$year'");
		if($q_wage->num_rows() > 0) return $q_wage->row()->amount;
		else{
			$q_wage2 = $this->db->query("SELECT amount FROM payroll_wage_config ORDER BY year LIMIT 1");
			if($q_wage2->num_rows() > 0){
				return $q_wage->row()->amount;
			}else{
				return false;
			}
		}
	}

	function undo_payroll_cutoff($empid = "",$cutoffstart="", $cutoffend="", $schedule = "",$quarter = "",$status="PENDING",$bank='', $remarks=""){
		$res = false;
		$q_update = $this->db->query("UPDATE payroll_computed_table SET  bank='$bank',status='$status'
										WHERE employeeid='$empid' AND schedule='$schedule' AND cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' AND quarter='$quarter'");
		if($q_update){ 
			$res = true;
			
			$undo_data = array(
				"employeeid" => $empid,
				"cutoffstart" => $cutoffstart,
				"cutoffend" => $cutoffend,
				"schedule" => $schedule,
				"quarter" => $quarter,
				"status" => "PENDING",
				"remarks" => $remarks,
				"isread" => 0,
				"modified_by" => $this->session->userdata("username")
			);

			$this->db->insert("payroll_undo", $undo_data);
			return $res;
		}else{
			return false;
		}
	}

	function getPayrollList($sdate, $edate, $schedule, $quarter, $status, $bank){
		return $this->db->query("SELECT `employeeid`,`schedule`,`cutoffstart`,`cutoffend`,`quarter`,`status`,`bank`,`salary`,`netbasicpay`,`gross`,`net`,`overtime`,`substitute`,`income`,`income_adj`,`withholdingtax`,`fixeddeduc`,`otherdeduc`,`loan`,`tardy`,`absents`,`timestamp`,`emp_accno`  
		FROM payroll_computed_table WHERE cutoffstart = '$sdate' AND cutoffend = '$edate' AND quarter = '$quarter' AND schedule = '$schedule' AND status = '$status' AND bank = '$bank' ");
	}

	function postPayrollToAthena($p_data){
		$this->load->model("api");
		$p_data["acknowledged_by"] = $this->session->userdata("username");
		$p_data["acknowledged_date"] = $this->extensions->getServerTime();
		$cutoff = $p_data["cutoffstart"]." ".$p_data["cutoffend"];
		$this->savePayrollBreakdown($p_data["employeeid"], $p_data["otherdeduc"], $p_data["id"], $cutoff, $this->session->userdata("username"), "payroll_deduction_config","other_deduc", $p_data["ppr_code"]);
		$this->savePayrollBreakdown($p_data["employeeid"], $p_data["loan"], $p_data["id"], $cutoff, $this->session->userdata("username"), "payroll_loan_config", "loan", $p_data["ppr_code"]);
		$this->savePayrollBreakdown($p_data["employeeid"], $p_data["fixeddeduc"], $p_data["id"], $cutoff, $this->session->userdata("username"), "deductions", "fixed_deduc", $p_data["ppr_code"]);
		$this->savePayrollBreakdown($p_data["employeeid"], $p_data["income"], $p_data["id"], $cutoff, $this->session->userdata("username"), "payroll_income_config", "income", $p_data["ppr_code"]);
		return $this->api->postPayrollDetails($p_data);
	}

	public function savePayrollBreakdown($employeeid, $payroll_str, $pct_id, $cutoff, $user, $table, $type, $ppr_code){
		$this->load->model("payrollprocess");
		$this->load->model("api");
		$payroll_d = $this->payrollprocess->constructArrayListFromComputedTable($payroll_str);
		foreach($payroll_d as $id => $amount){
			$gl_query = "";
			if($table == "deductions") $gl_query = $this->db->query("SELECT * FROM $table WHERE code_deduction = '$id' AND (gl_debit != '' || gl_credit) ");
			else $gl_query = $this->db->query("SELECT * FROM $table WHERE id = '$id' AND (gl_debit != '' || gl_credit) ");
			
			if($gl_query->num_rows() > 0){
				$payroll_breakdown = array(
					"income_id" => $id,
					"particulars" => $gl_query->row()->description,
					"gl_debit" => $gl_query->row()->gl_debit,
					"gl_credit" => $gl_query->row()->gl_credit,
					"pct_id" => $pct_id,
					"amount" => $amount,
					"cutoff" => $cutoff,
					"employeeid" => $employeeid,
					"emp_name" => $this->extensions->getEmployeeName($employeeid),
					"deptid" => $this->extensions->getEmployeeDepartment($employeeid),
					"office" => $this->extensions->getEmployeeOfficeDesc($employeeid),
					"type" => $type,
					"ppr_code" => $ppr_code,
					"processed_by" => $this->extensions->getAdminName($user)
				);
				
				$this->api->postPayrollBreakdown($payroll_breakdown);
			}
		}
	}

	function getGLAccount($table, $id){
		$query = $this->db->query("SELECT gl_debit, gl_credit FROM $table WHERE id='$id'");
		if($query->num_rows() > 0) return ($query->row()->gl_debit) ? $query->row()->gl_debit : $query->row()->gl_credit;
		else return false;
	}

	function getGLAccountReg($table, $id){
		$query = $this->db->query("SELECT gl_debit, gl_credit FROM $table WHERE code_deduction='$id'");
		if($query->num_rows() > 0) return ($query->row()->gl_debit) ? $query->row()->gl_debit : $query->row()->gl_credit;
		else return false;
	}

	function isPayrollExistsInAthena($sdate,$edate,$schedule,$quarter,$employeeid){
		$athena_db = Globals::athenaDatabase();
		return $this->db->query("SELECT * FROM $athena_db.HRIS_PAYROLL_DETAILS WHERE cutoffstart = '$sdate' AND cutoffend = '$edate' AND quarter = '$quarter' AND schedule = '$schedule' AND employeeid = '$employeeid'")->num_rows();
	}

	function updatePayrollPostStatus($sdate,$edate,$schedule,$quarter,$status,$bank, $employeeid){
		$posted_by = $this->session->userdata("username");
		$posted_date = $this->extensions->getServerTime();
		$this->db->query("UPDATE payroll_computed_table SET posted_by = '$posted_by', posted_date = '$posted_date' WHERE cutoffstart = '$sdate' AND cutoffend = '$edate' AND quarter = '$quarter' AND schedule = '$schedule' AND status = '$status' AND bank = '$bank' AND employeeid = '$employeeid'");
	}

	/**
	 * UPDATE THE STATUS OF PROCESSED PAYROLL THAT HAS BEEN SUCCESSFULLY SAVE IN ATHENA
	 * @param array $ids - lists of processed payroll id
	 */
	function updatePayrollPostStatusNew($ids = array()){
		if(count($ids)==0 || !is_array($ids))
			return;

		$posted_by = $this->session->userdata("username");
		$posted_date = $this->extensions->getServerTime();
		$ids = implode(",", $ids);
		$this->db->query("UPDATE payroll_computed_table SET posted_by = '$posted_by', posted_date = '$posted_date' WHERE FIND_IN_SET(id,'$ids')");
	}

	/**
	 * GET PAYROLL LIST
	 * @param string $sdate - start of cutoff
	 * @param string $edate - end of cutoff
	 * @param string $schedule - semimonthly or monthly
	 * @param int $quarter - 1st or 2nd
	 * @param string $status - status of payroll
	 * @param string $bank -bank where payroll is saved
	 */
	function getPayrollListNew($sdate, $edate, $schedule, $quarter, $status, $bank, $empid=""){
		$where_clause = "";
		if($bank) $where_clause = " AND bank = '$bank' ";
		if($empid) $where_clause = " AND pct.employeeid = '$empid' ";
		$acknowledgeby = $this->session->userdata("username");
		return $this->db->query("SELECT pct.`id`, pct.`employeeid`,pct.`schedule`,pct.`cutoffstart`,pct.`cutoffend`,pct.`quarter`,pct.`status`,pct.`bank`,pct.`salary`
		,pct.`netbasicpay`,pct.`gross`,pct.`net`,pct.`overtime`,pct.`substitute`,pct.`income`,pct.`income_adj`,pct.`withholdingtax`,pct.`fixeddeduc`,pct.`otherdeduc`,pct.`loan`,pct.`tardy`,pct.`absents`,pct.`timestamp`,pct.`emp_accno`,pct.`finalized_by`,pct.`finalized_date`,'' AS gross_gl_type, '' AS net_gl_type, '$acknowledgeby' AS acknowledged_by, CURRENT_TIMESTAMP AS acknowledged_date
		FROM payroll_computed_table pct
		LEFT JOIN payroll_computed_ee_er pce ON pct.id=pce.base_id
		WHERE cutoffstart = '$sdate' AND cutoffend = '$edate' AND quarter = '$quarter' AND pct.schedule = '$schedule' AND status = '$status' $where_clause
		GROUP BY pct.`employeeid` ORDER BY pct.id DESC");
	}

	/**
	 * SEND PROCESSED PAYROLL TO ATHENA
	 * @param array $data - payroll list from getPayrollListNew function
	 */
	function postAPIPayrollToAthena($data) {
		$token = Globals::athenaAccessToken();

		$curl = curl_init();
		$posted_data = array(
			"client_secret"=> "4cecbe4f3d0b5ce3955004a34cba2df8",
			'data' => $data
		);
		curl_setopt_array($curl, array(
		CURLOPT_URL => Globals::athenaAPIUrl().'api/Api.php',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS =>json_encode($posted_data),
		CURLOPT_HTTPHEADER => array(
			'Api-Function: post_processed_payroll',
			'Content-Type: application/json',
			'Bearer: '.$token,
		),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return $response;
	}

	function processPayrollSub($emplist=array(), $sdate='', $edate='', $schedule='', $quarter='', $recompute=false, $payroll_cutoff_id=0){

		$recomputed_emp_payroll = 0;
		$workdays = 0;
		$workhours_lec = $workhours_lab = $workhours_admin = "";

		//< initialize needed info ---------------------------------------------------
		$info    = $arr_income_config = $arr_income_adj_config = $arr_incomeoth_config = $arr_deduc_config = $arr_fixeddeduc_config = $arr_loan_config = array();

		///< ------------------------------ income config ------------------------------------------------------------
		$income_config_q = $this->payroll->displayIncome();
		$arr_income_config = $this->constructArrayListFromStdClass($income_config_q,'id','description');

		///< ------------------------------ incomeoth config ---------------------------------------------------------------
		$incomeoth_config_q = $this->payroll->displayIncomeOth();
		$arr_incomeoth_config = $this->constructArrayListFromStdClass($incomeoth_config_q,'id','description');

		///< ------------------------------ fixed deduction config ----------------------------------------------------
		$fixeddeduc_config_q = $this->db->query("SELECT code_deduction,description FROM deductions");
		$arr_fixeddeduc_config = $this->constructArrayListFromStdClass($fixeddeduc_config_q,'code_deduction','description');


		///< ------------------------------ deduction config ----------------------------------------------------------
		$deduction_config_q = $this->payroll->displayDeduction();
		$arr_deduc_config = $this->constructArrayListFromStdClass($deduction_config_q,'id','description');
		$arr_deduc_config_arithmetic = $this->constructArrayListFromStdClass($deduction_config_q,'id','arithmetic');


		///< ------------------------------ loan config ---------------------------------------------------------------
		$loan_config_q = $this->payroll->displayLoan();
		$arr_loan_config = $this->constructArrayListFromStdClass($loan_config_q,'id','description');

		if($recompute === true){
			foreach($emplist as $row){
				$eid = $row->employeeid;
				$this->db->query("DELETE FROM sub_payroll_computed_table WHERE cutoffstart='$sdate' AND cutoffend='$edate' AND schedule='$schedule' AND quarter='$quarter' AND employeeid='$eid' AND status='PENDING'");
			}
		}
		// die;

		foreach ($emplist as $row) {
			$perdept_amt_arr = array();
			$eid = $row->employeeid;

			$check_saved_q = $this->getPayrollSummarySub('SAVED',$sdate,$edate,$schedule,$quarter,$eid,TRUE,'PROCESSED');
			// echo $this->db->last_query();die;
		
			if(!$check_saved_q){

			$info[$eid]['income'] = $info[$eid]['income_adj'] = array();

			$info[$eid]['fullname'] 	=  isset($row->fullname) ? $row->fullname : '';
			$info[$eid]['deptid'] = isset($row->deptid) ? $row->deptid : '';
			$info[$eid]['office'] = isset($row->office) ? $row->office : '';

			[$info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config]  = $this->computeSubNewPayrollIncome($row,$schedule,$quarter,$sdate,$edate,$info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_deduc_config_arithmetic,$arr_loan_config,$payroll_cutoff_id);

			}

			$recomputed_emp_payroll += 1;
            $emplist_total_payroll = sizeof($emplist);

            // $this->session->set_userdata('emplist_total_payroll', $emplist_total_payroll);
            // $this->session->set_userdata('recomputed_emp_payroll', $recomputed_emp_payroll);

		} //end loop emplist

		// $this->session->unset_userdata('emplist_total_payroll');
        // $this->session->unset_userdata('recomputed_emp_payroll');

		$data['emplist'] = $info;
		$data['income_config'] = $arr_income_config;
		$data['income_adj_config'] = $arr_income_adj_config;
		$data['incomeoth_config'] = $arr_incomeoth_config;
		$data['fixeddeduc_config'] = $arr_fixeddeduc_config;
		$data['deduction_config'] = $arr_deduc_config;
		$data['loan_config'] = $arr_loan_config;
		
		return $data;

	}

	function computeSubNewPayrollIncome($row, $schedule, $quarter, $sdate, $edate, $info, $arr_income_config, $arr_income_adj_config, $arr_fixeddeduc_config,$arr_deduc_config,$arr_deduc_config_arithmetic,$arr_loan_config, $payroll_cutoff_id){
		$this->load->model('payrollcomputation','comp');
		$this->load->model('income');

		$eid = $row->employeeid;
		$regpay = $row->regpay;
		$tnt = $row->teachingtype;
		$dependents = $row->dependents;

		$str_income = $str_income_adj = "";
		$totalincome = $totalincome_adj = 0;
		$perdept_salary = array();

		///<  compute and save other income
		$this->comp->computeEmployeeOtherIncome($eid, $sdate, $edate, $tnt, $schedule, $quarter, $perdept_salary, $regpay);
		
		///< income
		list($arr_income_config,$info[$eid]['income'],$totalincome,$str_income) = $this->comp->computeEmployeeIncome($eid,$schedule,$quarter,$sdate,$edate,$arr_income_config,$payroll_cutoff_id);
		$getTotalNotIncludedInGrosspay = $this->getTotalNotIncludedInGrosspay($info[$eid]['income']);
		///< income adjustment
		list($arr_income_adj_config,$info[$eid]['income_adj'],$totalincome,$str_income_adj) = $this->comp->computeEmployeeIncomeAdj($eid,$schedule,$quarter,$sdate,$edate,$arr_income_adj_config,$totalincome,$payroll_cutoff_id);

		///< fixed deduc
		list($arr_fixeddeduc_config,$info[$eid]['fixeddeduc'],$totalfix,$str_fixeddeduc,$ee_er) = $this->comp->computeSubEmployeeFixedDeduc($eid,$schedule,$quarter,$sdate,$edate,$arr_fixeddeduc_config, $totalincome);

		///< loan
		list($arr_loan_config,$info[$eid]['loan'],$totalloan,$str_loan) = $this->comp->computeEmployeeLoan($eid,$schedule,$quarter,$sdate,$edate,$arr_loan_config);

		list($arr_deduc_config,$info[$eid]['deduction'],$total_deducSub,$total_deducAdd,$str_deduc) = $this->comp->computeEmployeeDeduction($eid,$schedule,$quarter,$sdate,$edate,$arr_deduc_config,$arr_deduc_config_arithmetic);

		///< TAX COMPUTATION
		$wh_tax = $this->comp->getExistingWithholdingTax($eid, $edate);
		if($wh_tax!="") $info[$eid]['whtax'] = $wh_tax;
		else $info[$eid]['whtax']  = $this->comp->computeWithholdingTax($schedule,$dependents,($regpay * 2),$info[$eid]['income'],$info[$eid]['income_adj'],$info[$eid]['deduction'],$info[$eid]['fixeddeduc']);


		//<!--NET PAY-->
		$info[$eid]['netpay'] = ($totalincome - $totalloan - $totalfix - $total_deducSub - $info[$eid]['whtax']);

		///< save to computed table
		$data_tosave = $data_tosave_oth = array();
		$data_tosave['cutoffstart'] 	= $sdate;
		$data_tosave['cutoffend'] 		= $edate;
		$data_tosave['employeeid'] 		= $eid;
		$data_tosave['schedule'] 		= $schedule;
		$data_tosave['quarter'] 		= $quarter;
		$data_tosave['income'] 			= $str_income;
		$data_tosave['income_adj'] 		= $str_income_adj;
		
		$data_tosave['fixeddeduc'] 		= $str_fixeddeduc;
		$data_tosave['otherdeduc'] 		= $str_deduc;
		$data_tosave['loan'] 			= $str_loan;
		$data_tosave['withholdingtax'] 	= $info[$eid]['whtax'];
		
		$data_tosave_oth['ee_er'] 		= $ee_er;
		$info[$eid]['base_id'] = $this->saveIncomeCutoffSummaryDraft($data_tosave,$data_tosave_oth);
		return [$info,$arr_income_config,$arr_income_adj_config,$arr_fixeddeduc_config,$arr_deduc_config,$arr_loan_config];
	}

	///< PENDING STATUS
	function saveIncomeCutoffSummaryDraft($data=array(),$data_oth=array()){
		$this->load->model('utils');
		// $data['addedby']   = $this->session->userdata('username');
		$base_id = $this->utils->insertSingleTblData('sub_payroll_computed_table',$data);

		if($base_id){
			if(sizeof($data_oth['ee_er']) > 0){
				foreach ($data_oth['ee_er'] as $code => $amt) {
					$amt['EE'] = round($amt['EE'],2);
					$amt['EC'] = round($amt['EC'],2);
					$amt['ER'] = round($amt['ER'],2);
					$this->utils->insertSingleTblData('sub_payroll_computed_ee_er',array('base_id'=>$base_id,'code_deduction'=>$code,'EE'=>$amt['EE'],'EC'=>$amt['EC'],'ER'=>$amt['ER'],'provident_er'=>$amt['provident_er']));
				}
			}
		}

		return $base_id;
	}

} //endoffile