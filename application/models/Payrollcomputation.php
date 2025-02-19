<?php 
/**
 * @author Angelica Arangco
 * @copyright 2018
 *
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Payrollcomputation extends CI_Model {

	function computeEmployeeIncome($empid='',$schedule='',$quarter='',$sdate='',$edate='',$arr_income_config='',$payroll_cutoff_id=''){
		$arr_info = array();
		$str_income = '';
		$totalincome = 0;

		$res = $this->payrolloptions->incometitle($empid,'amount',$schedule,$quarter,'',$sdate,$edate);

		foreach ($res->result() as $key => $row) {
			$amount = $row->title;

			$arr_info[$row->code_income] = $amount;
			$totalincome += $amount;
			$arr_income_config[$row->code_income]['hasData'] = 1;
			if($str_income) $str_income .= '/';
			$str_income .= $row->code_income . '=' . $amount;
		}

	/*	$this->load->model('income');
		$leave_adj_code = '31';
		$leave_adj_amt = $this->income->getLeaveAdjAmount($payroll_cutoff_id,$empid);

		if($leave_adj_amt){
			$arr_info[$leave_adj_code] = $leave_adj_amt;
			$totalincome += $leave_adj_amt;
			$arr_income_config[$leave_adj_code]['hasData'] = 1;
			if($str_income) $str_income .= '/';
			$str_income .= $leave_adj_code . '=' . $leave_adj_amt;
		}

		$ob_adj_code = '32';
		$ob_adj_amt = $this->income->getLeaveAdjAmount($payroll_cutoff_id,$empid,'OB');
		$ob_adj_amt += $this->income->getLeaveAdjAmount($payroll_cutoff_id,$empid,'CORRECTION');

		if($ob_adj_amt){
			$arr_info[$ob_adj_code] = $ob_adj_amt;
			$totalincome += $ob_adj_amt;
			$arr_income_config[$ob_adj_code]['hasData'] = 1;
			if($str_income) $str_income .= '/';
			$str_income .= $ob_adj_code . '=' . $ob_adj_amt;
		}*/

		return array($arr_income_config,$arr_info,$totalincome,$str_income);
	}

	function computeEmployeeIncomeAdj($empid='',$schedule='',$quarter='',$sdate='',$edate='',$arr_income_adj_config='',$totalincome=0,$payroll_cutoff_id=0){
		$arr_info = array();
		$str_income_adj = '';

		$res = $this->payrolloptions->getEmpIncomeAdj($empid,'amount',$schedule,$quarter,'',$sdate,$edate);

		foreach ($res->result() as $key => $row) {
			$amount = $row->title;
			if($row->deduct==1) $amount = $amount * -1;

			$arr_info[$row->code_income] = $amount;
			$totalincome += $amount;
			$arr_income_adj_config[$row->code_income]['hasData'] = 1;
			if($str_income_adj) $str_income_adj .= '/';
			$str_income_adj .= $row->code_income . '=' . $amount;
		}

		$res = $this->payrolloptions->getEmpIncomeAdjSalary($empid,'amount',$schedule,$quarter,'SALARY',$sdate,$edate);

		foreach ($res->result() as $key => $row) {
			$amount = $row->title;
			if($row->deduct==1) $amount = $amount * -1;

			$arr_info[$row->code_income] = $amount;
			$totalincome += $amount;
			$arr_income_adj_config[$row->code_income]['hasData'] = 1;
			if($str_income_adj) $str_income_adj .= '/';
			$str_income_adj .= $row->code_income . '=' . $amount;
		}
		
		$this->load->model('income');
		$leave_adj_code = '31';
		$leave_adj_amt = $this->income->getLeaveAdjAmount($payroll_cutoff_id,$empid);

		if($leave_adj_amt){
			$arr_info[$leave_adj_code] = $leave_adj_amt;
			$totalincome += $leave_adj_amt;
			$arr_income_adj_config[$leave_adj_code]['hasData'] = 1;
			if($str_income_adj) $str_income_adj .= '/';
			$str_income_adj .= $leave_adj_code . '=' . $leave_adj_amt;
		}

		$ob_adj_code = '1';
		$ob_adj_amt = $this->income->getLeaveAdjAmount($payroll_cutoff_id,$empid,'OB');
		$ob_adj_amt += $this->income->getLeaveAdjAmount($payroll_cutoff_id,$empid,'CORRECTION');

		if($ob_adj_amt){
			$arr_info[$ob_adj_code] = $ob_adj_amt;
			$totalincome += $ob_adj_amt;
			$arr_income_adj_config[$ob_adj_code]['hasData'] = 1;
			if($str_income_adj) $str_income_adj .= '/';
			$str_income_adj .= $ob_adj_code . '=' . $ob_adj_amt;
		}

		return array($arr_income_adj_config,$arr_info,$totalincome,$str_income_adj);
	}


	function computeEmployeeOtherIncome($employeeid='',$sdate='',$edate='',$tnt='teaching',$schedule='',$quarter='',$perdept_salary=array(),$regpay=0){
		$this->load->model('income');
		$this->load->model('payrollprocess');
		$total_holiday_and_leave = $this->extensions->getTotalLeaveAndHoliday($employeeid, $sdate, $edate, $tnt);
		$income_config_q = $this->payroll->displayIncome();
		$arr_income_config = $this->payrollprocess->constructArrayListFromStdClass($income_config_q,'id','deductedby');
		$workingdays = '';
		$computeOtherIncome = 1;
		foreach ($arr_income_config as $codeIncome => $det) {
				$deductedby = $det['description'];

				$oth_q = $this->income->getEmployeeOtherIncomeConfig($employeeid,$sdate,$edate,$codeIncome);

				if($oth_q->num_rows() > 0){
					$row = $oth_q->row(0);

				    ///< compute for deduction and total pay
				    $total_deduc = $total_pay = $deduc_hours = 0;
				    $oth_monthly = $row->monthly;
				    $oth_daily = $row->daily;
				    $oth_hourly = $row->hourly;


					if($deductedby != '' || $deductedby != NULL){

						$deduc_min = 0;

						if($tnt == 'teaching'){
							list($tardy_amount,$absent_amount,$x,$x,$x,$x,$tardy_min,$absent_min) = $this->getTardyAbsentSummaryTeaching($employeeid,$tnt,'','',$sdate,$edate,$oth_hourly,$oth_hourly,$oth_hourly,$oth_hourly, $perdept_salary, $regpay);
							$workingdays = 261;
						}else{
							list($tardy_amount,$absent_amount,$x,$tardy_min,$absent_min) = $this->getTardyAbsentSummaryNT($employeeid,$tnt,'','',$sdate,$edate,$oth_hourly,false,$oth_daily);
							$workingdays = 313;
						}

						///< deduct base on setup
						if($deductedby == 'BOTH'){
							$deduc_min = $tardy_min + $absent_min;
							$total_deduc = $tardy_amount + $absent_amount;

						}elseif($deductedby == 'TARDY'){
							$deduc_min = $tardy_min;
							$total_deduc = $tardy_amount;

						}elseif($deductedby == 'ABSENT'){
							$deduc_min = $absent_min;
							// $total_deduc = $absent_amount;
							if($deduc_min > 0) $total_deduc = $oth_monthly * 2 * 12 / $workingdays;
						}

						$deduc_hours = $this->time->minutesToHours($deduc_min);
					}

				    if($deductedby == 'ABSENT'){
				    	$no_days = $deduc_hours / 8;
				    	$total_pay = $oth_monthly - ($no_days * $total_deduc);
				    }
				    else $total_pay = $oth_monthly - $total_deduc;

				    if($total_pay < 0) $total_pay = 0;

				    if($codeIncome == 29) $total_pay -= $total_holiday_and_leave * $oth_daily;

				    ///< insert to employee_income
			    	$this->income->saveEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,$total_pay,$schedule,$quarter);

			    	//< get corresponding dtr cutoff for given payroll cutoff
			    	list($dtr_start,$dtr_end) = $this->payrolloptions->getDtrPayrollCutoffPair('','',$sdate,$edate);

			    	///< save other income computation results for viewing
			    	$this->income->saveEmployeeOtherIncomeComputed($codeIncome,$employeeid,$dtr_start,$dtr_end,$sdate,$edate,$total_pay,$total_deduc,$deduc_hours);
				    
				} ///< end if

		} //<end loop income config

	}

	function computeLongevity($employeeid='',$sdate='',$edate='',$tnt='teaching',$schedule='',$quarter=''){
		$codeIncome = '14';
		$year = date("Y",strtotime($sdate));
		$regyear = $this->employee->EmpRegularDate($employeeid);
		$noCreditYears = $year - date("Y",strtotime($regyear));

		if($noCreditYears >= 5){

			$this->load->model('income');

			$a = $noCreditYears - 4;
			$prev_basicpay= $this->employee->GetBasicPreviousPay($employeeid);
			$present_basicpay= $this->employee->GetBasicCurrentPay($employeeid);

			$pcpay= round(((($prev_basicpay + $present_basicpay)/ 2)/12),2); 
			$totallongevity = round(((($pcpay * 3)*$a)/26),2);   ///< TOTAL LONGEVITY

			///< save to longevity computed for viewing
			$dtr_cutoff_id = $this->payrolloptions->getDtrPayrollCutoffID('','',$sdate,$edate);
			$this->income->saveLongevityComputed($dtr_cutoff_id,$employeeid,$noCreditYears,$prev_basicpay,$present_basicpay,$totallongevity);

			///< save to employee_income if is included
			$isIncluded = $this->income->isIncludedLongevity($employeeid);

			if($isIncluded){
					///< compute for deduction and total pay
					$total_deduc = $total_pay =  0;

					$deductedby = "";
					$income_config_q = $this->payroll->displayIncome($codeIncome);
					if($income_config_q->num_rows() > 0) $deductedby = $income_config_q->row(0)->deductedby;

					$workingdays = 313;
					if($tnt == 'teaching') $workingdays = 261;
					$hourly = (($totallongevity * 12) / $workingdays) / 8;

					if($tnt == 'teaching'){
						list($tardy_amount,$absent_amount,$x,$x,$x,$x,$tardy_min,$absent_min) = $this->getTardyAbsentSummaryTeaching($employeeid,$tnt,'','',$sdate,$edate,$hourly,$hourly,$hourly,$hourly);
					}else{
						list($tardy_amount,$absent_amount,$x,$tardy_min,$absent_min) = $this->getTardyAbsentSummaryNT($employeeid,$tnt,'','',$sdate,$edate,$hourly);
					}

					///< deduct base on setup
					if($deductedby == 'BOTH'){
						$total_deduc = $tardy_amount + $absent_amount;

					}elseif($deductedby == 'TARDY'){
						$total_deduc = $tardy_amount;

					}elseif($deductedby == 'ABSENT'){
						$total_deduc = $absent_amount;
					}

					$total_pay = ($totallongevity/2) - $total_deduc; ///< NET LONGEVITY PER CUTOFF
					if($total_pay < 0) $total_pay = 0;

			    	$this->income->saveEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,$total_pay,$schedule,$quarter);

			} //end if isIncluded


		}

	}


	function computeEmployeeFixedDeduc($empid='',$schedule='',$quarter='',$sdate='',$edate='',$arr_fixeddeduc_config='',$arr_info_emp='',$prevSalary=0,$prevGrosspay=0, $getTotalNotIncludedInGrosspay = 0, $basic_salary=0,$teaching_pay=0, $prev_teaching_pay=0, $regpay = 0){
		$tnt = $this->extensions->getEmployeeTeachingType($empid);
		$arr_info = $ee_er = array();
		$str_fixeddeduc = '';
		$totalfix = 0;
		
		// $total_gross = $arr_info_emp['grosspay'] + $prevGrosspay;
		// Change To Basic Pay Salary
		$total_gross = $arr_info_emp['salary'] + $arr_info_emp['salary'];
		$prevGrosspay = $arr_info_emp['salary'];
		$arr_info_emp['grosspay'] = $arr_info_emp['salary'];

		$employee_salary = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid = '$empid' ");
		if($employee_salary->num_rows() > 0){
			$employee_salary = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid = '$empid' ORDER BY date_effective DESC LIMIT 1")->row()->monthly;
		}else{
			$employee_salary = $this->db->query("SELECT * FROM payroll_employee_salary WHERE employeeid = '$empid' ORDER BY date_effective DESC LIMIT 1")->row()->monthly;
		}

		
		// $reference_amount = $arr_info_emp['salary'] + $prevSalary;
		$reference_amount = $schedule == 'semimonthly' ? $regpay * 2 : $regpay;
		$is_trelated = $this->employee->isTeachingRelated($empid);
		if($tnt == "teaching" && !$is_trelated){
			$reference_amount = $teaching_pay + $prev_teaching_pay;
		}
		$res = $this->payrolloptions->getEmpFixedDeduc($empid,'amount','HIDDEN',$schedule,$quarter,'',$sdate,$edate);
		// echo $this->db->last_query(); die;
		foreach ($res->result() as $key => $row) {
			$cutoff_period = $row->cutoff_period;
			$er = $ec = $provident_er = 0;
			$amount_fx = $row->title;
			$code_deduction = $row->code_deduction;
		/*	if($amount_fx == NULL){
				if($row->code_deduction == 'PHILHEALTH'){
					$amount_fx = $this->computePHILHEALTHContri($arr_info_emp['salary'] * 2);
				}
				else if ($row->code_deduction == 'SSS') {
					$amount_fx = $this->computeSSSContri($arr_info_emp['grosspay']);
				}
				else if ($row->code_deduction == 'PERAA') {
					$amount_fx = ($arr_info_emp['salary'] * 2) * 0.0325;
				}
				else if ($row->code_deduction == 'PAGIBIG') {
					$amount_fx = $this->computePagibigContri($arr_info_emp['salary'] * 2);
				}
			}*/
			if($row->code_deduction == 'PHILHEALTH'){
				list($amount_fx,$er) = $this->computePHILHEALTHContri($amount_fx,$reference_amount,$cutoff_period,$sdate,$prevGrosspay,$quarter,$empid);
				if(!$this->payrolloptions->checkIdnumber($empid, $code_deduction)) $amount_fx = $er = 0;

			}
			else if ($row->code_deduction == 'SSS') {
				/*if teaching is non teaching, use salary, if teaching use gross*/
				if($tnt == "nonteaching"){
					// $arr_info_emp["grosspay"] = $basic_salary;
					// $prevGrosspay = $prevSalary;
				}
				list($amount_fx,$ec,$er,$provident_er) = $this->computeSSSContri($amount_fx,$reference_amount,$prevGrosspay,$empid,$sdate,$edate,$quarter,$cutoff_period,$getTotalNotIncludedInGrosspay);
				if(!$this->payrolloptions->checkIdnumber($empid, $code_deduction)) $amount_fx = $er = $ec = 0;
			}
			else if ($row->code_deduction == 'PAGIBIG') {
				list($amount_fx,$er) = $this->computePagibigContri($amount_fx,$empid,$reference_amount, $cutoff_period, $quarter, $sdate);
				if(!$this->payrolloptions->checkIdnumber($empid, $code_deduction)) $amount_fx = $er = 0;
			}
			else if ($row->code_deduction == 'PERAA') {
				if($row->amount == "") $amount_fx = $reference_amount * 0.0325;
				$er = $amount_fx;
			}

			$ee_er[$row->code_deduction]['EE'] = $amount_fx;
			$ee_er[$row->code_deduction]['ER'] = $er;
			$ee_er[$row->code_deduction]['EC'] = $ec;
			$ee_er[$row->code_deduction]['provident_er'] = $provident_er;


			$arr_info[$row->code_deduction] = $amount_fx;
			$totalfix += $amount_fx;

			$arr_fixeddeduc_config[$row->code_deduction]['hasData'] = 1;
			if($str_fixeddeduc) $str_fixeddeduc .= '/';
			$str_fixeddeduc .= $row->code_deduction . '=' . $amount_fx;
		}

		return array($arr_fixeddeduc_config,$arr_info,$totalfix,$str_fixeddeduc,$ee_er);
	}

	

	function computeEmployeeDeduction($empid='',$schedule='',$quarter='',$sdate='',$edate='',$arr_deduc_config='',$arr_deduc_config_arithmetic=''){
		$arr_info = array();
		$str_deduc = '';
		$total_deducSub = $total_deducAdd = 0;

		$res = $this->payrolloptions->deducttitle($empid,'amount','SHOW',$schedule,$quarter,'',$sdate,$edate);

		foreach ($res->result() as $key => $row) {
			$arr_info[$row->code_deduction] = $row->title;
			if ($arr_deduc_config_arithmetic[$row->code_deduction]['description'] == "sub") {
				 $total_deducSub += $row->title;
			}else{
				 $total_deducAdd += $row->title;	
			}
			// $total_deduc += $row->title;
			$arr_deduc_config[$row->code_deduction]['hasData'] = 1;
			if($str_deduc) $str_deduc .= '/';
			$str_deduc .= $row->code_deduction . '=' . $row->title;
		}

		return array($arr_deduc_config,$arr_info,$total_deducSub,$total_deducAdd,$str_deduc);
	}

	function computeEmployeeLoan($empid='',$schedule='',$quarter='',$sdate='',$edate='',$arr_loan_config=''){
		$arr_info = array();
		$str_loan = '';
		$totalloan = 0;

		$res = $this->payrolloptions->loantitle($empid,'amount',$schedule,$quarter,'',$sdate,$edate);

		foreach ($res->result() as $key => $row) {
			$this->load->model("loan");
			$skip_loan = $this->loan->checkIfSkipInLoanPayment($empid, $row->code_loan);
			// if(!$skip_loan){
			if($skip_loan) $row->title = 0;
			$arr_info[$row->code_loan] = $row->title;
			$totalloan += $row->title;
			$arr_loan_config[$row->code_loan]['hasData'] = 1;
			if($str_loan) $str_loan .= '/';
			$str_loan .= $row->code_loan . '=' . $row->title;
			// }
		}

		return array($arr_loan_config,$arr_info,$totalloan,$str_loan);
	}


	/*function computePHILHEALTHContri($monthlySalary=0){
		$contri = 0;
		if($monthlySalary <= 10000) $contri = 275;
		elseif($monthlySalary > 10000 && $monthlySalary <= 40000) $contri = $monthlySalary * 0.0275;
		elseif($monthlySalary > 40000) $contri = 1100;

		return $contri / 2; ///< for employee and employer
	}

	function computeSSSContri($gross=0){
		$return = '0.00';
		$query = $this->db->query("SELECT emp_ee FROM sss_deduction WHERE '$gross' BETWEEN compensationfrom AND compensationto");
		if ($query->num_rows() > 0) {
			$return = $query->row()->emp_ee;
		} 
		return $return;
	}

	function computePagibigContri($gross=0){
		$return = '0.00';
		$query = $this->db->query("SELECT emp_ee FROM hdmf_deduction WHERE '$gross' BETWEEN compensationfrom AND compensationto");
		if ($query->num_rows() > 0) {
			$return = $query->row()->emp_ee;
		} 
		return $return;
	}*/

	function philhealthContribution($monthlySalary="",$payroll_start=""){
		$year = date("Y", strtotime($payroll_start));
		$isrange = $this->db->query("SELECT * FROM `philhealth_empshare` WHERE $monthlySalary BETWEEN min_salary AND max_salary AND min_salary != '' AND max_salary != '' AND year <= '$year' ORDER BY year DESC LIMIT 1");
		if($isrange->num_rows() == 0) $isrange = $this->db->query("SELECT * FROM `philhealth_empshare` WHERE $monthlySalary BETWEEN min_salary AND max_salary AND min_salary != '' AND max_salary != '' ORDER BY year DESC LIMIT 1");

		if($isrange->num_rows() > 0){
			if($isrange->row()->percentage){
				$isrange->row()->percentage = str_replace(".", "", $isrange->row()->percentage);
				$isrange->row()->percentage = "0.0".$isrange->row()->percentage;
				$ee = $monthlySalary * $isrange->row()->percentage;
				return $ee;
			}else{
				$ee = $isrange->row()->def_amount;
				return $ee;
			}
		}
		$isminimum = $this->db->query("SELECT * FROM `philhealth_empshare` WHERE min_salary > $monthlySalary AND min_salary != '' AND def_amount != '' AND year = '$year'");
		if($isminimum->num_rows() == 0) $isminimum = $this->db->query("SELECT * FROM `philhealth_empshare` WHERE min_salary > $monthlySalary AND min_salary != '' AND def_amount != '' ORDER BY year DESC LIMIT 1");

		if($isminimum->num_rows() > 0){
			$ee = $isminimum->row()->def_amount;
			return $ee;
		}
		$ismaximum = $this->db->query("SELECT * FROM `philhealth_empshare` WHERE max_salary > $monthlySalary AND max_salary != '' AND def_amount != '' AND year = '$year'");
		if($ismaximum->num_rows() == 0) $ismaximum = $this->db->query("SELECT * FROM `philhealth_empshare` WHERE max_salary > $monthlySalary AND max_salary != '' AND def_amount != '' ORDER BY year DESC LIMIT 1");

		if($ismaximum->num_rows() > 0){
			$ee = $ismaximum->row()->def_amount;
			return $ee;
		}
	}

	function computePHILHEALTHContri($encoded_ee=NULL,$gross=0,$cutoff_period="",$sdate="",$prevGrosspay="",$quarter="",$empid=""){
		$ee = $er = $true_ee = 0;
		$monthly_gross = $prevGrosspay + $gross;
		if($encoded_ee == NULL){
			$ee = $this->philhealthContribution($gross, $sdate);
			$ee = $ee / 2; ///< for employee and employer
			
			if($cutoff_period == 3){
				// DIVIDE 2 FOR SEMIMONTHLY CONDITION
				$ee /= 2;
				$er /= 2;
			}

			
						
			$ee = isset($ee) ? floatval($ee) : 0;
			$er = isset($er) ? floatval($er) : 0;

			$true_ee = ($ee*100)/100;
			
			$excess = $ee - $true_ee;
			
			$er = $ee + $excess;
			
		}else{
			$true_ee = $er = $encoded_ee;
		}
		return array($true_ee,$er); 
	}

	///< Ticket #ICA-HYPERION21515
	function computeSSSContri($encoded_ee=NULL,$gross=0,$prevGrosspay=0,$empid='',$sdate='',$edate='',$quarter='',$cutoff_period='',$getTotalNotIncludedInGrosspay=0){
		$ee = $ec = $er = $provident_er = 0;
		$total_gross = 0;
		$year = date("Y", strtotime($sdate));
		if($cutoff_period == 3){
			list($ee,$ec,$er,$provident_er) = $this->getSSSContriFromSetup($encoded_ee,$gross, date('Y',strtotime($sdate)));
			
			// DIVIDE 2 FOR SEMIMONTHLY CONDITION
			$ee /= 2;
			$ec /= 2;
			$er /= 2;

		}else{
			$total_gross = $gross;
			list($ee,$ec,$er,$provident_er) = $this->getSSSContriFromSetup($encoded_ee,$total_gross, date('Y',strtotime($sdate)));
		}

		return array($ee,$ec,$er,$provident_er);
	}

	///< Ticket #ICA-HYPERION21515
	function getSSSContriFromSetup($encoded_ee=NULL,$gross=0,$year=""){
		$ee = $ec = $er = $provident_er = 0;
		
		if($encoded_ee == NULL){
			$query = $this->db->query("SELECT emp_ee,emp_con,emp_er,total_ee,provident_er FROM sss_deduction WHERE '$gross' BETWEEN compensationfrom AND compensationto AND year = '$year' ORDER BY year DESC LIMIT 1");
			if ($query->num_rows() > 0) {
				$ee = ($query->row()->total_ee) ? $query->row()->total_ee : $query->row()->emp_ee;
				$ec = $query->row()->emp_con;
				$er = $query->row()->emp_er;
				$provident_er = $query->row()->provident_er;
			}else{
				$query_latest = $this->db->query("SELECT emp_ee,emp_con,emp_er, total_ee, provident_er FROM sss_deduction WHERE '$gross' BETWEEN compensationfrom AND compensationto AND compensationto ORDER BY YEAR DESC LIMIT 1");
				if ($query_latest->num_rows() > 0) {
					$ee = $query_latest->row()->total_ee;
					$ec = $query_latest->row()->emp_con;
					$er = $query_latest->row()->emp_er;
					$provident_er = $query_latest->row()->provident_er;
				}
			}  
		}else{
			$ee = $encoded_ee;
			$query = $this->db->query("SELECT emp_ee,emp_con,emp_er,provident_er FROM sss_deduction WHERE emp_ee <= $encoded_ee ORDER BY emp_ee DESC LIMIT 1");
			if ($query->num_rows() > 0) {
				$ec = $query->row()->emp_con;
				$er = $query->row()->emp_er;
				$provident_er = $query->row()->provident_er;
			} 
		}
		return array($ee,$ec,$er,$provident_er);
	}

	///< Ticket #ICA-HYPERION21515
	function getPrevSSSContri($cutoff_month='',$quarter=1,$employeeid=''){
		$prev_ee = $prev_ec = $prev_er = 0;
		if($cutoff_month){
			if($quarter > 1){
				$res = $this->db->query("SELECT b.code_deduction,b.EE,b.EC,b.ER FROM payroll_computed_table a
											INNER JOIN payroll_computed_ee_er b ON b.base_id = a.id
											WHERE a.employeeid='$employeeid' AND DATE_FORMAT(a.cutoffstart,'%Y-%m')='$cutoff_month' AND a.quarter=1 AND b.code_deduction = 'SSS'
											LIMIT 1");

				if($res->num_rows() > 0){
					$prev_ee = $res->row(0)->EE;
					$prev_ec = $res->row(0)->EC;
					$prev_er = $res->row(0)->ER;
				}
			}
		}else{

		}

		return array($prev_ee,$prev_ec,$prev_er);
	}

	function getPrevPhilhealthContri($cutoff_month='',$quarter=1,$employeeid=''){
		$prev_ee = $prev_ec = $prev_er = 0;
		if($cutoff_month){
			if($quarter > 1){
				$res = $this->db->query("SELECT b.code_deduction,b.EE,b.EC,b.ER FROM payroll_computed_table a
											INNER JOIN payroll_computed_ee_er b ON b.base_id = a.id
											WHERE a.employeeid='$employeeid' AND DATE_FORMAT(a.cutoffstart,'%Y-%m')='$cutoff_month' AND a.quarter=1 AND b.code_deduction = 'PHILHEALTH'
											LIMIT 1");

				if($res->num_rows() > 0){
					$prev_ee = $res->row(0)->EE;
					$prev_ec = $res->row(0)->EC;
					$prev_er = $res->row(0)->ER;
				}
			}
		}else{

		}

		return array($prev_ee,$prev_ec,$prev_er);
	}

	function computePagibigContri($encoded_ee=NULL,$employeeid='',$gross=0, $cutoff_period=0, $quarter=0, $cutoffstart=""){
		$ee = $er = 0;
		$year = date("Y", strtotime($cutoffstart));
		if($encoded_ee == NULL){
			$query = $this->db->query("SELECT emp_ee,emp_er FROM hdmf_deduction WHERE '$gross' BETWEEN compensationfrom AND compensationto AND year <= '$year' ORDER BY year DESC LIMIT 1");
			if ($query->num_rows() > 0) {
				$ee = $query->row()->emp_ee;
				$er = $query->row()->emp_er;
			} 
		}else{
			$ee = $encoded_ee;
			$query = $this->db->query("SELECT emp_ee,emp_er FROM hdmf_deduction WHERE emp_ee <= $encoded_ee AND year <= '$year' ORDER BY emp_ee DESC LIMIT 1");
			if ($query->num_rows() > 0) {
				$er = $query->row()->emp_er;
			} 
		}

		if($cutoff_period == 3){
			$ee /= 2;
			$er /= 2;
		}

		return array($ee,$er);
	}

	function computeTeachingCutoffSalary($workhours_lec='',$workhours_lab='',$workhours_admin='',$workhours_rle='',$hourly=0,$lechour=0,$labhour=0,$rlehour=0,$fixedday=0,$regpay=0,$perdept_amt_arr=array(),$hold_status='',$excess_min=0,$hasleave=false,$minimum_wage=0){
		$salary = 0;
		$perdept_amount = 0;

		if(sizeof($perdept_amt_arr) > 0){
			foreach ($perdept_amt_arr as $aimsdept => $leclab_arr) {
				foreach ($leclab_arr as $type => $amt) {
					/*if ($type != 'ADMIN')*/ $perdept_amount += $amt['work_amount'];
				}
			}
		}

		if($hold_status == 'ALL') 			$regpay = $perdept_amount = 0;
		elseif($hold_status == 'LECLAB') 	$perdept_amount = 0;
		
		if($fixedday){
			$hourly = number_format($hourly, 2, '.', '');
			$minutely = $hourly / 60;
			$excess_amt = 0;
			$minutely = number_format($minutely, 2, '.', '');
			if($excess_min > 0) $excess_amt = $minutely * $excess_min;
			$salary += $perdept_amount + $excess_amt;
		}else{
			$salary = $perdept_amount;
			/*remove regpay if not monthly rate*/
			$regpay = 0;
		}

		/*add minimum wage for bday leave*/
		if($hasleave && $salary > 0){
			$salary += $minimum_wage;
		}

		return array($regpay, $salary);
	}

	function computeNTCutoffSalary($workdays=0,$fixedday=0,$regpay=0,$daily=0,$hasleave=false,$minimum_wage=0){
		$salary = 0;
		if($fixedday){
			$salary = $regpay;
		}else{
			$salary = $workdays * $daily;
		}
		if($hasleave && $salary > 0){
			// $salary -= $daily;
			// $salary += $minimum_wage;
		}

		return $salary;
	}

	function computeOvertime($empid='',$tnt='teaching',$schedule='',$quarter='',$sdate='',$edate='',$hourly=0){
		$overtimepay = 0;
		$otreg = $otrest = $othol = 0;
		if($hourly){
			$minutely = $hourly / 60;

			if($tnt == 'teaching'){

			}else{
				$detail_q = $this->db->query("SELECT otreg,otrest,othol FROM attendance_confirmed_nt WHERE employeeid='$empid' AND payroll_cutoffstart='$sdate' AND payroll_cutoffend='$edate'");
		    	if($detail_q->num_rows() > 0){
		    		$otreg 		= $detail_q->row(0)->otreg;
		    		$otrest 	= $detail_q->row(0)->otrest;
		    		$othol 		= $detail_q->row(0)->othol;
		    		
			        $otreg = $this->attcompute->exp_time($otreg);
			        $otrest = $this->attcompute->exp_time($otrest);
			        $othol = $this->attcompute->exp_time($othol);
		    	}
			}

			$otreg      	= $this->time->hoursToMinutes($this->attcompute->sec_to_hm($otreg));
			$otrest      	= $this->time->hoursToMinutes($this->attcompute->sec_to_hm($otrest));
			$othol      	= $this->time->hoursToMinutes($this->attcompute->sec_to_hm($othol));

			$otregpay = $otreg * ($minutely * 1.25);
			$otrestpay = $otrest * ($minutely * 0.25);
			$otholpay = $othol * ($minutely * 2.00);

			$overtimepay = $otregpay + $otrestpay + $otholpay;
		}

		return $overtimepay;
	}

	function computeOvertime2($empid='',$tnt='teaching',$hourly=0,$base_id='',$employmentstat=''){
		$this->load->model('utils');
		$overtimepay = 0;
		$ot_det = array();

		/*$hourly = number_format($hourly, 2, '.', '');
		$minutely_orig = $hourly / 60;

		$minutely_orig = number_format($minutely_orig, 2, '.', '');*/

		$setup = $this->getOvertimeSetup($employmentstat);
		// echo '<pre>';
		// print_r($setup);
		// echo '</pre>';

		$tbl = 'attendance_confirmed_ot_hours';
		if($tnt=='nonteaching') $tbl = 'attendance_confirmed_nt_ot_hours';

		if($base_id){
			$ot_q = $this->utils->getSingleTblData($tbl,array('*'),array('base_id'=>$base_id));
			
			foreach ($ot_q->result() as $key => $row) {
				$att_baseid = $row->id;

				$ot_hours = $row->ot_hours;
				$ot_type = $row->ot_type;
				$holiday_type = $row->holiday_type;
				$is_excess = $row->is_excess;

				$ot_with_25 = $this->time->hoursToMinutes($row->total_ot_with_25);
				$ot_without_25 = $this->time->hoursToMinutes($row->total_ot_without_25);

				// $ot_min = $this->time->hoursToMinutes($ot_hours);
				// $ot_hour = $ot_min / 60;

				$ot_min = abs($ot_with_25+$ot_without_25);
				$ot_hour = $ot_min / 60;

				$percent = 100; ///< default

				if(isset($setup[$employmentstat][$ot_type][$holiday_type][$is_excess])){ ///< get percent if has existing setup
					$percent = $setup[$employmentstat][$ot_type][$holiday_type][$is_excess];
				}

				$percent = $percent / 100;

				$hourly_rate = $hourly * $percent;
				$minutely_rate = $hourly_rate / 60; 


				$initial_pay = ($ot_min * $minutely_rate);

				if($ot_with_25 != 0)
				{
					$percent_with = 125 / 100; //default
					$initial_pay = ($ot_min * $minutely_rate) * $percent_with;
				}

				$ot_det[$att_baseid] = $initial_pay; ///< insert later for overtime amount details

				$overtimepay += $initial_pay;

			}
		}

		return array($overtimepay,$ot_det);
	}

	function computeSubstitute($empid,$id=''){
		$this->load->model('utils');
		$substitutepay = 0;
		if($id){
			$q_substitute = $this->utils->getSingleTblData("attendance_confirmed_substitute_hours",array('*'),array('base_id'=>$id));
			
			foreach ($q_substitute->result() as $row) {
				list($lec, $lab) = $this->aimsdeptSalaryRate($empid, $row->type);
				$lec /= 60;
				$substitute_hours = $row->hours;
				$substitute_minute = $this->time->hoursToMinutes($substitute_hours);
				if($row["holiday"]){
					if($row["holiday"] == "2"){
						$substitute_minute /= 2;
					}elseif($row["holiday"] == "9"){
						$substitute_minute = 0;
					}
				}
				$substitutepay += ($substitute_minute * $lec);

			}
		}
		return $substitutepay;
	}

	public function aimsdeptSalaryRate($employeeid, $aimdept){
		$lec = $lab = 0;
		$q_rate = $this->db->query("SELECT * FROM `payroll_emp_salary_perdept_history` WHERE employeeid = '$employeeid' AND aimsdept = '$aimdept' LIMIT 1");
		if($q_rate->num_rows() > 0){ 
			$lec = $q_rate->row()->lechour;
			$lab = $q_rate->row()->labhour;
			$rle = $q_rate->row()->rlehour;
		}

		return array($lec, $lab);
	}

	function getOvertimeSetup($employmentstat=''){
		$filter = $setup = array();

		if($employmentstat) $filter['code_status'] = $employmentstat;
		$ot_q = $this->utils->getSingleTblData('code_overtime',array('*'),$filter);

		foreach ($ot_q->result() as $key => $row) {
			$setup[$row->code_status][$row->ot_types] = array(
																'NONE' 		=> array('0'=>$row->percent,'1'=>$row->excess_percent),
																'REGULAR' 	=> array('0'=>$row->regular_percent,'1'=>$row->regular_percent_excess),
																'SPECIAL' 	=> array('0'=>$row->other_percent,'1'=>$row->other_percent_excess)
															);
		}
		return $setup;
	}


	/**
	 * Compute withholding tax. Taxable income = Salary + taxable income - included deductions - fixed deduc.
	 * Refer to ticket# ICA-Hyperion21063
	 *
	 * @return Float
	 */
	function computeWithholdingTax($schedule='',$dependents='',$regpay='',$arr_income,$arr_income_adj,$arr_deduc,$arr_fixeddeduc,$overtime=0,$tardy=0,$absents=0){
		$whtax = $total_income = $total_deduc = $total_fixeddeduc =  $total_taxable = 0;
		$this->load->model('payrollprocess');

		///< get total taxable income first

		$income_config_q = $this->payroll->displayIncome();
		$arr_income_config = $this->payrollprocess->constructArrayListFromStdClass($income_config_q,'id','taxable');
		$deduction_config_q = $this->payroll->displayDeduction();
		$arr_deduc_config = $this->payrollprocess->constructArrayListFromStdClass($deduction_config_q,'id','arithmetic');

		if(sizeof($arr_income) > 0){
			foreach ($arr_income as $key => $value) {
				if($arr_income_config[$key]['description'] == 'withtax') $total_income += $value;
			}
		}
		if(sizeof($arr_income_adj) > 0){
			foreach ($arr_income_adj as $key => $value) {
				if(isset($arr_income_config[$key]['description'])){
					if($arr_income_config[$key]['description'] == 'withtax') $total_income += $value;

				}else{
					if($key == 'SALARY') $total_income += $value;
				}
			}
		}
		if(sizeof($arr_deduc) > 0){
			foreach ($arr_deduc as $key => $value) {
				$isDeductionWithtax = $this->payrollprocess->checkDeductionIfWithtax($key);
				if($isDeductionWithtax == "withtax"){
					if($arr_deduc_config[$key]['description'] == 'sub') $total_deduc -= $value;
					else $total_deduc += $value;
				}
			}
		}
		if(sizeof($arr_fixeddeduc) > 0){
			foreach ($arr_fixeddeduc as $key => $value) {
				if($key != 'PERAA') $total_fixeddeduc += $value; ///< fixed deductions are subtracted automatically
			}
		}
		
		if($schedule == 'monthly'){
			$total_taxable = $regpay + $total_income - ($total_fixeddeduc * 2);

			$whtax = $this->calculateWithholdingTax($total_taxable,$schedule,$regpay,$dependents);
		}else{
			$total_taxable = $regpay + $total_income - $total_fixeddeduc;
			$whtax = $this->calculateWithholdingTax($total_taxable,$schedule,$regpay,$dependents);
		}

		return $whtax;
	}

	private function calculateWithholdingTax($total_taxable=0,$schedule='',$regpay=0,$dependents=''){
		$whtax = 0;
		$wc = "";
		if($dependents) $wc = " AND status_='$dependents'";
		$tax_config_q = $this->db->query("SELECT * FROM code_tax WHERE tax_type='$schedule' AND tax_range <= '$total_taxable' $wc ORDER BY tax_range DESC LIMIT 1");

		if($tax_config_q->num_rows() > 0){
			$tax_config = $tax_config_q->row(0);

			if(is_numeric($regpay) && is_numeric($tax_config->tax_range) && is_numeric($regpay) && is_numeric($tax_config->percent) && is_numeric($tax_config->basic_tax)){

				$whtax = (( $total_taxable - $tax_config->tax_range ) * ($tax_config->percent/100) ) + $tax_config->basic_tax;
			}
		}

		return $whtax;
	}


	function getTardyAbsentSummaryTeaching($empid = "",$ttype="",$schedule = "",$quarter = "",$sdate = "",$edate = "",$hourly=0,$lechour=0,$labhour=0,$rlehour=0,$perdept_salary=array(),$force_useHourly=false,$semimonthly=0){
		$this->load->model("utils");
		$separated_department = $this->extensions->getBEDDepartments();

		$empDepartment = $this->utils->getEmployeeDepartment($empid); 
		$tardy_amount = $absent_amount = $tardy_lec = $tardy_lab = $tardy_admin = $tardy_rle = $absent_lec = $absent_lab = $absent_admin = $absent_rle = 0;
		$workhours_lec = $workhours_lab = $workhours_admin = $workhours_rle = $hold_status = 0;
		$isFinal = 0;
		$tot_min = 0;
		$total_tardy_min = $total_absent_min = 0;

		$min_lec = $lechour / 60;
		$min_lab = $labhour / 60;
		$min_admin = $hourly / 60;
		$min_rle = $rlehour / 60;

		$perdept_amt_arr = array();
			    
		$base_id = '';
		$total_work_hours = 0;
    	$detail_q = $this->db->query("SELECT id ,latelec, latelab, lateadmin, laterle, deduclec, deduclab, deducadmin, deducrle, workhours_lec, workhours_lab, workhours_admin, workhours_rle , hold_status_change, isFinal
    									FROM attendance_confirmed 
    									WHERE employeeid='$empid' AND payroll_cutoffstart='$sdate' AND payroll_cutoffend='$edate' 
    											AND `status`='PROCESSED' AND forcutoff=1
    									ORDER BY cutoffstart DESC");
    	
    	if($detail_q->num_rows() > 0){
    		$tlec = $tlab = $tadmin = $trle = $tdlec = $tdlab = $tdadmin = $tdrle = 0;

    		$hold_status = $detail_q->row(0)->hold_status_change;
    		$isFinal = $detail_q->row(0)->isFinal;
    			
    		if($hold_status != 'ALL'){

	    		///< workhours will refer to latest  cutoff
	    		$workhours_lec 	= $detail_q->row(0)->workhours_lec;
	    		$workhours_lab 	= $detail_q->row(0)->workhours_lab;
	    		$workhours_admin 	= $detail_q->row(0)->workhours_admin;
	    		$workhours_rle 	= $detail_q->row(0)->workhours_rle;

	    		foreach ($detail_q->result() as $key => $row) {
	    			///< for cases of more than 1 dtr cutoff per 1 payroll cutoff
	    			///< sum up tardy and absent
	    			$base_id 	= $row->id;
	    			$perdept_q = $this->db->query("SELECT work_hours, late_hours, deduc_hours, `type`, aimsdept, leave_project FROM workhours_perdept WHERE base_id='$base_id' ORDER BY type ASC");

	    			$perdept_list = $perdept_q->result();

	    			/*get included only in computation -- this is for nursing department*/
	    			if($this->extensions->isNursingDepartment($empid) > 0 && !$this->extensions->isNursingExcluded($empid)){
	    				$nursing_included = $this->nursingIncludedPerdept($perdept_q->result());
	    				$perdept_list = $nursing_included;
	    			}
	    				// echo "	<pre>"; print_r(	$perdept_list);
	    			foreach ($perdept_list as $key_dept => $row_dept) {
	    				$leave_project = $row_dept->leave_project;
	    				$aimsdept = $row_dept->aimsdept;
	    				$type = $row_dept->type;
	    				$type_rate = '';
	    				if($type == "LEC") $type_rate = "lechour";
	    				if($type == "LAB") $type_rate = "labhour";
	    				if($type == "RLE") $type_rate = "rlehour";

	    				if( ($type == 'LEC' && $type == 'LAB' && $type == 'RLE') || $hold_status != 'LECLAB' ){
			    				if($type == 'ADMIN'){
			    					$rate_min = $min_admin;
			    				}else{
			    					$rate_min = isset($perdept_salary[$aimsdept][$type_rate]) ? ($perdept_salary[$aimsdept][$type_rate] / 60): 0;
			    				}

			    				if($force_useHourly) $rate_min = $min_admin;

			    				$work_min = $this->time->hoursToMinutes($row_dept->work_hours);
			    				$late_min = $this->time->hoursToMinutes($row_dept->late_hours);
			    				$deduc_min = $this->time->hoursToMinutes($row_dept->deduc_hours);


			    				if($leave_project){
			    					$workhours_tmp = $this->attcompute->exp_time($row_dept->work_hours);
			    					$leave_project_tmp = $this->attcompute->exp_time($leave_project);
			    					$workhours_f = $workhours_tmp - $leave_project_tmp;
			    					$row_dept->work_hours = $this->attcompute->sec_to_hm($workhours_f);
			    				}

			    				// $work_amt = ($type == 'ADMIN') ? 0 : ($this->time->hoursToMinutes($row_dept->work_hours) * $rate_min); //< no perdept work amount if type=ADMIN
								$actual_work_min = $work_min - $late_min - $deduc_min;
								$work_amt = ($actual_work_min * $rate_min);
			    				$late_amt = $late_min * $rate_min;
			    				$deduc_amt = $deduc_min * $rate_min;
			    				/*remove late and absent of nursing because it is already taken ughhh*/
			    				/*if($this->extensions->isNursingDepartment($empid) > 0){
			    					$deduc_amt = $late_amt = 0;
			    				}*/

			    				/*for ticket TMSHYP-3976*/
			    				$total_work_hours += $work_min - $late_min - $deduc_min;

			    				if(!isset($perdept_amt_arr[$aimsdept][$type]['work_amount'])) $perdept_amt_arr[$aimsdept][$type]['work_amount'] = 0;
			    				if(!isset($perdept_amt_arr[$aimsdept][$type]['late_amount'])) $perdept_amt_arr[$aimsdept][$type]['late_amount'] = 0;
			    				if(!isset($perdept_amt_arr[$aimsdept][$type]['deduc_amount'])) $perdept_amt_arr[$aimsdept][$type]['deduc_amount'] = 0;
			    				$perdept_amt_arr[$aimsdept][$type]['work_amount'] += $work_amt;
			    				$perdept_amt_arr[$aimsdept][$type]['late_amount'] += $late_amt;
			    				$perdept_amt_arr[$aimsdept][$type]['deduc_amount'] += $deduc_amt;

			    				$tardy_amount += $late_amt;
			    				/*if($type != "ADMIN") */$absent_amount += $deduc_amt;

			    				$total_tardy_min += $late_min;
			    				$total_absent_min += $deduc_min;
			    				// echo "<pre>"; print_r($deduc_min."-".$rate_min."-".$aimsdept."-".$type);
			    				$tot_min += (($work_min - $deduc_min - $late_min) > 0) ? ($work_min - $deduc_min - $late_min) : 0;
	    				}
	    			}

	    			if(in_array($empDepartment, $separated_department)){
    					$daily_rate = $this->getEmployeeDailySalary($empid);
						$days_absent = $this->getEmployeeDayAbsent($empid, $sdate, $edate);
						$perday_deduction = $daily_rate * $days_absent; #per day deduction
						$absent_amount = $perday_deduction;

    				}

	    		}
    		} // end if hold_status

    	}

	    /*for medicine department*/
	    $excess = 0;
    	if($this->extensions->isMedicineDepartment($empid) > 0){
	    	if($tot_min < 960){
	    		$less_workhours = 16 - ($tot_min / 60);
	    		$less_rate = $semimonthly / 16;
	    		$less_amount = $less_workhours * $less_rate;
	    		// var_dump($less_amount); die;
	    		if($less_amount) $absent_amount += $less_amount;
	    	}else{
	    		$excess = $tot_min - 960;
	    	}
	    }
		
		return array($tardy_amount,$absent_amount,$workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$perdept_amt_arr,$hold_status,$total_tardy_min,$total_absent_min,$isFinal,$base_id,$excess);
	}

	function nursingIncludedPerdept($perdept){
		/*validate to make rle first and lab 2nd*/
		$validated_perdept = array("RLE" => array(), "LAB" => array(), "LEC" => array());
		
		foreach($perdept as $perdepts){
			$perdepts = (array) $perdepts;
			if($perdepts["type"] == "RLE") $validated_perdept["RLE"][] = $perdepts;
			elseif($perdepts["type"] == "LAB") $validated_perdept["LAB"][] = $perdepts;
			else $validated_perdept["LEC"][] = $perdepts;
		}

		$filtered_perdepts = array();
		foreach($validated_perdept as $sorted_perdepts){
			foreach($sorted_perdepts as $perdeptss){
				$workhours = $this->attcompute->exp_time($perdeptss["work_hours"]);
				$deduchours = $this->attcompute->exp_time($perdeptss["deduc_hours"]);
				$latehours = $this->attcompute->exp_time($perdeptss["late_hours"]);
				$workhours = $workhours - $deduchours - $latehours;
				$perdeptss["work_hours"] = $this->attcompute->sec_to_hm($workhours);
				$filtered_perdepts[] = (object) $perdeptss;
			}
		}

		$new_perdept = array();
		$to_deduc = 2880;
		if($filtered_perdepts){
			foreach($filtered_perdepts as $key => $row){
				$dept_min = $this->time->hoursToMinutes($row->work_hours);
				if($to_deduc >= $dept_min){
					$to_deduc -= $dept_min;
				}else{
					$countable = $dept_min - $to_deduc;
					$to_deduc -= $to_deduc;
					$new_perdept[$key] = $row;
					$new_perdept[$key]->work_hours = $this->time->minutesToHours($countable);
				}
			}
		}

		return (object) $new_perdept;
	}

	function getTardyAbsentSummaryNT($empid = "",$ttype="",$schedule = "",$quarter = "",$sdate = "",$edate = "",$hourly=0,$useDTRCutoff=false,$daily=0){
		$tardy_amount = $absent_amount = $tardy = $ut = $absent = $isFinal = $day_absent = 0;
		$workdays = 0;
		$base_id = '';

		$minutely = $daily / 8 / 60;

		$wC = '';
		if($useDTRCutoff){
			$wC .= " AND cutoffstart='$sdate' AND cutoffend='$edate'";
		}else{
			$wC .= " AND payroll_cutoffstart='$sdate' AND payroll_cutoffend='$edate'";
		}
	  
    	$detail_q = $this->db->query("SELECT id, lateut, ut, absent, day_absent, workdays, isFinal FROM attendance_confirmed_nt WHERE employeeid='$empid' $wC");
    	if($detail_q->num_rows() > 0){
    		$base_id 	= $detail_q->row(0)->id;

    		$tlec 		= $detail_q->row(0)->lateut;
    		$utlec 		= $detail_q->row(0)->ut;
    		$tabsent 	= $detail_q->row(0)->absent;

    		$workdays 	= $detail_q->row(0)->workdays;
    		$isFinal 	= $detail_q->row(0)->isFinal;

	        $tardy = $this->attcompute->exp_time($tlec);
	        $ut = $this->attcompute->exp_time($utlec);
	        $absent = $this->attcompute->exp_time($tabsent);
	        $day_absent 	= $detail_q->row(0)->day_absent;
    	}


	    $tardy      	= $this->time->hoursToMinutes($this->attcompute->sec_to_hm($tardy)) + $this->time->hoursToMinutes($this->attcompute->sec_to_hm($ut));
	    $absent      	= $this->time->hoursToMinutes($this->attcompute->sec_to_hm($absent));
	    $absent_hour = $absent / 60;

	    $tardy_amount     = number_format($tardy * $minutely,2,'.', '');
	    $absent_amount     = number_format($day_absent * $daily,2,'.', '');

		$total_schedule_days = $workdays;
		return array($tardy_amount,$absent_amount,$total_schedule_days,$tardy,$absent,$base_id, $isFinal);
	}

	# added by justin (with e) for ica-hyperion 21555
	function getYearToDateSummaries_whTax($employeeid, $sel_year, $date_to){
		$amount = 0;

		$q_yearly_withholdingtax = $this->db->query("SELECT withholdingtax
													 FROM payroll_computed_table 
													 WHERE employeeid = '$employeeid' AND cutoffstart LIKE '$sel_year%' AND cutoffend <= '$date_to' AND `status`='PROCESSED';")->result();

		foreach ($q_yearly_withholdingtax as $res) $amount += round($res->withholdingtax, 2);

		return $amount;
	}

	function getExistingWithholdingTax($employeeid, $date){
			$query_whtax = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE date_effective <= '$date' AND employeeid = '$employeeid' ORDER BY date_effective DESC LIMIT 1 ");
			if($query_whtax->num_rows() > 0) return $query_whtax->row()->whtax;
			else return false;
	}

	function getPerdeptSalary($employeeid=''){
		$perdept_salary = array();
		$res = $this->db->query("SELECT * FROM payroll_emp_salary_perdept WHERE employeeid='$employeeid'");
		foreach ($res->result() as $key => $row) {
			$perdept_salary[$row->aimsdept] = array('lechour'=>$row->lechour,'labhour'=>$row->labhour,'rlehour'=>$row->rlehour);
		}
		return $perdept_salary;
	}

	function getPerdeptSalaryHistory($employeeid='',$payroll_cutoff_from=''){
		$this->load->model("schedule");
		$perdept_salary = array();
		$base_id = '';
		$base_res = $this->db->query("SELECT id FROM payroll_employee_salary_history WHERE employeeid='$employeeid' AND date_effective <= '$payroll_cutoff_from' ORDER BY date_effective DESC LIMIT 1");
		if($base_res->num_rows() > 0) $base_id = $base_res->row(0)->id;

		if($base_id){
			$res = $this->db->query("SELECT * FROM payroll_emp_salary_perdept_history WHERE base_id='$base_id'");
			foreach ($res->result() as $key => $row) {
				if($row->aimsdept == "all"){
					$load_arr = $this->schedule->employeeScheduleList($employeeid);
					foreach($load_arr as $sched_aimsdept => $sched_r){
						$perdept_salary[$sched_aimsdept] = array('lechour'=>$row->lechour,'labhour'=>$row->labhour,'rlehour'=>$row->rlehour);
					}
				}else{
					$perdept_salary[$row->aimsdept] = array('lechour'=>$row->lechour,'labhour'=>$row->labhour,'rlehour'=>$row->rlehour);
				}
			}
		}
		return $perdept_salary;
	}

	function getEmployeeDailySalary($empid){
		$employeeDailyRate = $this->db->query("SELECT * FROM payroll_employee_salary WHERE employeeid = '$empid' ORDER BY date_effective DESC LIMIT 1")->row()->daily;
		return $employeeDailyRate;
	}

	function getEmployeeDayAbsent($empid, $sdate, $edate){
		$employeeDailyRate = $this->db->query("SELECT * FROM attendance_confirmed WHERE employeeid = '$empid' AND payroll_cutoffstart = '$sdate' AND payroll_cutoffend = '$edate' ")->row()->absent;
		return $employeeDailyRate;
	}

	function getProjectHolidayPay($payroll_cutoff_id,$lechour,$labhour,$rlehour,$eid,$payroll_cutoff_from){
		$this->load->model("payrollprocess");
		$this->load->model("hr_reports");
		$rate = "";
		$lec = $lab = $admin = "";
		$lec_amount = $lab_amount = 0;
		$tot_lab_amount = $tot_lec_amount = $tot_admin_amount = $tot_rle_amount = 0;
		$sus_lab_amount = $sus_lec_amount = $sus_admin_amount = $sus_rle_amount = 0;
		$sub_lab_amount = $sub_lec_amount = $sub_admin_amount = $sub_rle_amount = 0;
		$teachingtype = $this->extensions->getEmployeeTeachingType($eid);
		$deptid = $this->extensions->getEmployeeOffice($eid);
		list($from_date, $to_date) = $this->extensions->getDTRCutoffByPayrollCutoffID($payroll_cutoff_id);
		if($from_date && $to_date){
			$qdate = $this->attcompute->displayDateRange($from_date, $to_date);
			foreach($qdate as $rdate){
				$date = $rdate->dte;
				$holiday = $this->attcompute->isHolidayNew($eid,$date,$deptid); 
	            if($holiday){
	                $holidayInfo = $this->attcompute->holidayInfo($date);
	                if($holidayInfo) $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], $teachingtype);
	            
					$q_detailed = $this->db->query("SELECT * FROM employee_attendance_detailed WHERE sched_date = '$date' AND employeeid = '$eid' ");
					if($q_detailed->num_rows() > 0){
						$lec = $q_detailed->row()->lec;
						$lab = $q_detailed->row()->lab;
						$admin = $q_detailed->row()->admin;
						$rle = $q_detailed->row()->rle;
						$lec_hours = $this->hr_reports->constructArrayListFromAttendanceDetailed($lec);
						$lab_hours = $this->hr_reports->constructArrayListFromAttendanceDetailed($lab);
						$admin_hours = $this->hr_reports->constructArrayListFromAttendanceDetailed($admin);
						$rle_hours = $this->hr_reports->constructArrayListFromAttendanceDetailed($rle);
						if($lec_hours){
							foreach($lec_hours as $count_lec => $lec_data){
								$lec_data["aimsdept"] = isset($lec_data["aimsdept"]) ? $lec_data["aimsdept"] : 0;
								$lec_data["deduc_hours"] = isset($lec_data["deduc_hours"]) && is_numeric($lec_data["deduc_hours"]) ? $lec_data["deduc_hours"] : 0;
								list($lechour, $labhour, $rlehour) = $this->getPerdeptSalaryByID($eid, $payroll_cutoff_from, $lec_data["aimsdept"]);

								$lec_tothours = $lec_data["work_hours"] - ($lec_data["deduc_hours"] - $lec_data["late_hours"]);
								$lec_tothours = $lec_tothours / 60;
								$lec_amount = $lec_tothours * ($lechour / 60);
								if($holidayInfo["holiday_type"]==5) $lec_amount /= 2;
								$sub_lec_amount += $lec_tothours * ($lechour / 60);
								if($lec_data["suspension"] == 1) $sus_lec_amount += $lec_amount * $rate / 100;
								else $tot_lec_amount += $lec_amount * $rate / 100;
							}
						}
						if($lab_hours){
							foreach($lab_hours as $count_lab => $lab_data){
								list($lechour, $labhour, $rlehour) = $this->getPerdeptSalaryByID($eid, $payroll_cutoff_from, $lab_data["aimsdept"]);
								$lab_tothours = $lab_data["work_hours"] - ($lab_data["deduc_hours"] - $lab_data["late_hours"]);
								$lab_tothours = $lab_tothours / 60;
								$lab_amount = $lab_tothours * ($labhour / 60);
								if($holidayInfo["holiday_type"]==5) $lab_amount /= 2;
								$sub_lab_amount += $lab_tothours * ($labhour / 60);
								if($lab_data["suspension"] == 1) $sus_lab_amount += $lab_amount * $rate / 100;
								else $tot_lab_amount += $lab_amount * $rate / 100;
							}
						}
						if($admin_hours){
							foreach($admin_hours as $count_admin => $admin_data){
								list($lechour, $adminhour, $rlehour) = $this->getPerdeptSalaryByID($eid, $payroll_cutoff_from, $admin_data["aimsdept"]);
								$admin_tothours = $admin_data["work_hours"] - ($admin_data["deduc_hours"] - $admin_data["late_hours"]);
								$admin_tothours = $admin_tothours / 60;
								$admin_amount = $admin_tothours * ($adminhour / 60);
								if($holidayInfo["holiday_type"]==5) $admin_amount /= 2;
								$sub_admin_amount += $admin_tothours * ($adminhour / 60);
								if($admin_data["suspension"] == 1) $sus_admin_amount += $admin_amount * $rate / 100;
								else $tot_admin_amount += $admin_amount * $rate / 100;
							}
						}
						if($rle_hours){
							foreach($rle_hours as $count_rle => $rle_data){
								list($lechour, $labhour, $rlehour) = $this->getPerdeptSalaryByID($eid, $payroll_cutoff_from, $rle_data["aimsdept"]);
								
								$work_hours = is_numeric($rle_data["work_hours"]) ? $rle_data["work_hours"] : 0;
								$deduc_hours = is_numeric($rle_data["deduc_hours"]) ? $rle_data["deduc_hours"] : 0;
								$late_hours = is_numeric($rle_data["late_hours"]) ? $rle_data["late_hours"] : 0;

								$rle_tothours = $work_hours - ($deduc_hours - $late_hours);
								$rle_tothours = $rle_tothours / 60;
								$rle_amount = $rle_tothours * ($rlehour / 60);
								if($holidayInfo["holiday_type"]==5) $rle_amount /= 2;
								$sub_rle_amount += $rle_tothours * ($rlehour / 60);
								if($rle_data["suspension"] == 1) $sus_rle_amount += $rle_amount * $rate / 100;
								else $tot_rle_amount += $rle_amount * $rate / 100;
							}
						}
					}
	            }
			}
		}

		return array($tot_lab_amount + $tot_lec_amount, $sub_lab_amount + $sub_lec_amount, $sus_lab_amount + $sus_lec_amount);
	}

	function getPerdeptSalaryByID($employeeid, $payroll_cutoff_from, $aimsdept){
		$perdept_salary = array();
		$salaryid = '';
		$base_res = $this->db->query("SELECT id FROM payroll_employee_salary_history WHERE employeeid='$employeeid' AND date_effective <= '$payroll_cutoff_from' ORDER BY date_effective DESC LIMIT 1");
		if($base_res->num_rows() > 0) $salaryid = $base_res->row(0)->id;

		$q_salary = $this->db->query("SELECT * FROM `payroll_emp_salary_perdept_history` WHERE base_id = '$salaryid' AND employeeid = '$employeeid' AND (aimsdept = '$aimsdept' OR aimsdept = 'all') ");
		if($q_salary->num_rows() > 0) return array($q_salary->row()->lechour, $q_salary->row()->labhour, $q_salary->row()->rlehour);
		else return array(0, 0, 0);
	}

	function computeCOLAIncome($employeeid='',$sdate='',$edate='',$schedule='',$quarter='',$workdays=0,$absentdays=0){
		$this->load->model('income');
		$codeIncome = '11';
		$cola_amount = 0;
		$present_days = $this->attendance->cutoffPresentDays($employeeid, $sdate, $edate);
		$multiplier = $this->income->getCOLAEffectiveAmount($sdate);
		$cola_amount = $multiplier * $present_days;

		if($cola_amount > 0){
			$this->income->saveEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,$cola_amount,$schedule,$quarter);
		}
	}

	function computeSubEmployeeFixedDeduc($empid='', $schedule='', $quarter='', $sdate='', $edate='', $arr_fixeddeduc_config, $netpay=0){
		$arr_info = $ee_er = array();
		$str_fixeddeduc = '';
		$totalfix = 0;

		$res = $this->payrolloptions->getEmpFixedDeduc($empid,'amount','HIDDEN',$schedule,$quarter,'',$sdate,$edate);
		foreach ($res->result() as $key => $row) {
			$cutoff_period = $row->cutoff_period;
			$er = $ec = $provident_er = 0;
			$amount_fx = $row->title;
			$code_deduction = $row->code_deduction;
		
			if($row->code_deduction == 'PHILHEALTH'){
				list($amount_fx,$er) = $this->computePHILHEALTHContri($amount_fx,$netpay,$cutoff_period,$sdate,0,$quarter,$empid);
				if(!$this->payrolloptions->checkIdnumber($empid, $code_deduction)) $amount_fx = $er = 0;

			}
			else if ($row->code_deduction == 'SSS') {
				list($amount_fx,$ec,$er,$provident_er) = $this->computeSSSContri($amount_fx,$netpay,0,$empid,$sdate,$edate,$quarter,$cutoff_period,0);
			}
			else if ($row->code_deduction == 'PAGIBIG') {
				list($amount_fx,$er) = $this->computePagibigContri($amount_fx,$empid,$netpay, $cutoff_period, $quarter, $sdate);
			}
			else if ($row->code_deduction == 'PERAA') {
				$er = $amount_fx;
			}

			if(!$this->payrolloptions->checkIdnumber($empid, $code_deduction)) $amount_fx = $er = $ec = 0;

			$ee_er[$row->code_deduction]['EE'] = $amount_fx;
			$ee_er[$row->code_deduction]['ER'] = $er;
			$ee_er[$row->code_deduction]['EC'] = $ec;
			$ee_er[$row->code_deduction]['provident_er'] = $provident_er;


			$arr_info[$row->code_deduction] = $amount_fx;
			$totalfix += $amount_fx;

			$arr_fixeddeduc_config[$row->code_deduction]['hasData'] = 1;
			if($str_fixeddeduc) $str_fixeddeduc .= '/';
			$str_fixeddeduc .= $row->code_deduction . '=' . $amount_fx;
		}

		return [$arr_fixeddeduc_config,$arr_info,$totalfix,$str_fixeddeduc,$ee_er];
	}

	function computeSubWithholdingTax($schedule='',$dependents='',$regpay='',$arr_income,$arr_income_adj,$arr_deduc,$arr_fixeddeduc){
		$whtax = $total_income = $total_deduc = $total_fixeddeduc =  $total_taxable = 0;
		$this->load->model('payrollprocess');

		///< get total taxable income first

		$income_config_q = $this->payroll->displayIncome();
		$arr_income_config = $this->payrollprocess->constructArrayListFromStdClass($income_config_q,'id','taxable');
		$deduction_config_q = $this->payroll->displayDeduction();
		$arr_deduc_config = $this->payrollprocess->constructArrayListFromStdClass($deduction_config_q,'id','arithmetic');

		if(sizeof($arr_income) > 0){
			foreach ($arr_income as $key => $value) {
				if($arr_income_config[$key]['description'] == 'withtax') $total_income += $value;
			}
		}
		if(sizeof($arr_income_adj) > 0){
			foreach ($arr_income_adj as $key => $value) {
				if(isset($arr_income_config[$key]['description'])){
					if($arr_income_config[$key]['description'] == 'withtax') $total_income += $value;

				}else{
					if($key == 'SALARY') $total_income += $value;
				}
			}
		}
		if(sizeof($arr_deduc) > 0){
			foreach ($arr_deduc as $key => $value) {
				$isDeductionWithtax = $this->payrollprocess->checkDeductionIfWithtax($key);
				if($isDeductionWithtax == "withtax"){
					if($arr_deduc_config[$key]['description'] == 'sub') $total_deduc -= $value;
					else $total_deduc += $value;
				}
			}
		}
		if(sizeof($arr_fixeddeduc) > 0){
			foreach ($arr_fixeddeduc as $key => $value) {
				if($key != 'PERAA') $total_fixeddeduc += $value; ///< fixed deductions are subtracted automatically
			}
		}

		
		$total_taxable = $regpay + $total_income - ($total_fixeddeduc * 2);

		$wc = "";
		if($dependents) $wc = " AND status_='$dependents'";

		$tax_config_q = $this->db->query("SELECT * FROM code_tax WHERE tax_type='$schedule' AND tax_range <= '$total_taxable' $wc ORDER BY tax_range DESC LIMIT 1");

		if($tax_config_q->num_rows() > 0){
			$tax_config = $tax_config_q->row(0);

			if(is_numeric($regpay) && is_numeric($tax_config->tax_range) && is_numeric($regpay) && is_numeric($tax_config->percent) && is_numeric($tax_config->basic_tax)){

				$whtax = (( $total_taxable - $tax_config->tax_range ) * ($tax_config->percent/100) ) + $tax_config->basic_tax;
			}
		}
		// divide tax to 2 for semimonthly
		$whtax /= 2;
		return $whtax;
	}

}//endoffile
