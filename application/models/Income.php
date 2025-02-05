<?php 
/**
 * @author Angelica Arangco
 * @copyright 2018
 *
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Income extends CI_Model {

	///<  @Angelica added functions copy by justin (with e) for ica-hyperion 21622
	function getIncomeSetupList($filter=array()){
		$res = $this->db->get_where('payroll_income_config',$filter);

		
		return $res;
	}

	function deleteIncome($filter=array()){
		$res = $this->db->delete('payroll_income_config',$filter);
		return $res;
	}

	function getEmployeeOtherIncomeConfig($employeeid='',$sdate='',$edate='',$codeIncome=''){
		$wC = "";
		if($codeIncome) $wC .= " AND a.other_income = '{$codeIncome}'";
		if($employeeid) $wC .= " AND a.employeeid = '{$employeeid}'";
		$res = $this->db->query("SELECT a.*
									FROM other_income a
                            		WHERE ( (dateEffective <= '$edate') OR (dateEnd >= '$edate') )
                            		{$wC}
                            		order by a.employeeid");
		return $res;
	}

	function getEmployeeOtherIncome($sdate='',$edate='',$campus='',$codeIncome=''){
		$wC = "";
		if($campus) $wC .= "AND c.campusid = '{$campus}'";
		if($codeIncome) $wC .= " AND b.other_income = '{$codeIncome}'";

		$res = $this->db->query("SELECT a.`amount`, b.*
									FROM employee_income a
									INNER JOIN other_income b ON b.`employeeid`=a.`employeeid` AND b.`other_income`=a.`code_income`
									INNER JOIN employee c ON c.employeeid = a.employeeid
									WHERE a.datefrom='$sdate'
									$wC
                            		order by a.employeeid");
		return $res;
	}

	function saveEmployeeOtherIncome($employeeid='',$sdate='',$edate='',$codeIncome='',$total_pay=0,$schedule='',$quarter=''){
		$code = '';
		$projected_income_code = 0;
		if($codeIncome == 5){
			$code = 18;
			$projected_income_code = 39;
		}
		else if($codeIncome == 37){
			$code = 27; 
			$projected_income_code = 40;
		}

		$exisiting_income = 0;
		$total = 0;
		$projected_income = $this->db->query("SELECT code_income, amount FROM projected_income WHERE code_income='$projected_income_code' AND employeeid='$employeeid'");

		if($projected_income->num_rows() > 0) $res = $this->db->query("SELECT code_income, amount FROM employee_income WHERE code_income='$projected_income_code' AND employeeid='$employeeid'");
		else $res = $this->db->query("SELECT code_income, amount FROM employee_income WHERE code_income='$codeIncome' AND employeeid='$employeeid'");

		if($res->num_rows() > 0) $exisiting_income = $res->row()->amount;
		$total = round($total_pay, 2) - round($exisiting_income, 2);
		if($res->num_rows() > 0){
			if($codeIncome == 5 || $codeIncome == 37){
				if($total){
					$this->insertProjectedEmployeeOtherIncome($employeeid,$sdate,$edate,$projected_income_code,$total_pay,$schedule,$quarter);

					if($total > 0) $this->updateEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,$total,$schedule,$quarter);
					else $this->updateEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,0,$schedule,$quarter);

					$emp_deduction = $this->db->query("SELECT code_deduction, amount FROM employee_deduction WHERE code_deduction='$code' AND employeeid='$employeeid'");
					if($emp_deduction->num_rows() > 0){
						if($emp_deduction->row()->amount > 0){
						 $this->db->query("DELETE FROM employee_deduction WHERE employeeid = '$employeeid' AND code_deduction = '$code' ");
					   	 $this->insertEmployeeDeduction($employeeid, $sdate, $edate, $code, abs($total), $schedule, $quarter);
						}
					}else{
						$this->insertEmployeeDeduction($employeeid, $sdate, $edate, $code, abs($total), $schedule, $quarter);
					}
				}

			}else{
				if($total_pay > $exisiting_income){
					$this->updateEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,$total_pay,$schedule,$quarter);
				}else if($total_pay == $exisiting_income){

				}
				else{
					$this->updateEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,$total_pay,$schedule,$quarter);
					$this->insertEmployeeDeduction($employeeid, $sdate, $edate, $code, $total, $schedule, $quarter);
				}
			}
		}

		else{				
			$this->insertEmployeeOtherIncome($employeeid,$sdate,$edate,$codeIncome,$total_pay,$schedule,$quarter);
		}
	}

	function updateEmployeeOtherIncome($employeeid='',$sdate='',$edate='',$codeIncome='',$total_pay=0,$schedule='',$quarter=''){
		$res = $this->db->query("UPDATE employee_income SET datefrom='$sdate', amount='$total_pay', nocutoff=1, schedule='$schedule', cutoff_period='$quarter' 
		                                                    WHERE employeeid='$employeeid' AND code_income='$codeIncome'");
		return $res;
	}

	function insertEmployeeOtherIncome($employeeid='',$sdate='',$edate='',$codeIncome='',$total_pay=0,$schedule='',$quarter=''){
		$res = $this->db->query("INSERT INTO employee_income (employeeid,code_income,datefrom,amount,nocutoff,schedule,cutoff_period) VALUES ('$employeeid','$codeIncome','$sdate','$total_pay',1,'$schedule','$quarter')");
		return $res;
	}

	function insertProjectedEmployeeOtherIncome($employeeid='',$sdate='',$edate='',$codeIncome='',$total_pay=0,$schedule='',$quarter=''){
		$res = $this->db->query("INSERT INTO projected_income (employeeid,code_income,datefrom,amount,nocutoff,schedule,cutoff_period) VALUES ('$employeeid','$codeIncome','$sdate','$total_pay',1,'$schedule','$quarter')");
		return $res;
	}


	function saveEmployeeOtherIncomeComputed($code_income='',$employeeid='',$dtr_start='',$dtr_end='',$payroll_start='',$payroll_end='',$total_pay='',$total_deduc='',$deduc_hours=''){
		$res = $this->db->query("SELECT id FROM other_income_computed 
									WHERE employeeid='$employeeid' 
									AND code_income='$code_income'
									AND dtr_cutoffstart='$dtr_start' AND dtr_cutoffend='$dtr_end'
									AND payroll_cutoffstart='$payroll_start' AND payroll_cutoffend='$payroll_end'");

		if($res->num_rows() > 0) 	$this->updateEmployeeOtherIncomeComputed($res->row(0)->id,$total_pay,$total_deduc,$deduc_hours);
		else 						$this->insertEmployeeOtherIncomeComputed($code_income,$employeeid,$dtr_start,$dtr_end,$payroll_start,$payroll_end,$total_pay,$total_deduc,$deduc_hours);
	}

	function updateEmployeeOtherIncomeComputed($id='',$total_pay='',$total_deduc='',$deduc_hours=''){
		$res = $this->db->query("UPDATE other_income_computed SET amount_total='$total_pay', amount_deduc='$total_deduc', hours_deduc='$deduc_hours' WHERE id='$id'");
		return $res;
	}

	function insertEmployeeOtherIncomeComputed($code_income='',$employeeid='',$dtr_start='',$dtr_end='',$payroll_start='',$payroll_end='',$total_pay='',$total_deduc='',$deduc_hours=''){
		$user = $this->session->userdata('username');
		$res = $this->db->query("INSERT INTO other_income_computed (employeeid,code_income,dtr_cutoffstart,dtr_cutoffend,payroll_cutoffstart,payroll_cutoffend,amount_total,amount_deduc,hours_deduc,addedby) 
									VALUES ('$employeeid','$code_income','$dtr_start','$dtr_end','$payroll_start','$payroll_end','$total_pay','$total_deduc','$deduc_hours','$user')");
		return $res;
	}

	function getEmployeeOtherIncomeComputed($sdate='',$edate='',$campus='',$codeIncome=''){
		$wC = "";
		if($campus) $wC .= "AND c.campusid = '{$campus}'";
		if($codeIncome) $wC .= " AND a.code_income = '{$codeIncome}'";

		$res = $this->db->query("SELECT a.*, b.monthly
									FROM other_income_computed a
									INNER JOIN other_income b ON b.employeeid=a.employeeid AND b.other_income=a.code_income
									INNER JOIN employee c ON c.employeeid = a.employeeid
									WHERE a.dtr_cutoffstart='$sdate' AND a.dtr_cutoffend='$edate'
									$wC
                            		order by a.employeeid");
		return $res;
	}

	function isIncludedLongevity($employeeid=''){
		$isIncluded = 0;
		$res = $this->db->query("SELECT isIncluded FROM longevity_income_included WHERE employeeid='$employeeid'");
		if($res->num_rows() > 0) $isIncluded = $res->row(0)->isIncluded;
		return $isIncluded;
	}

	function getLongevityEmpIncluded(){
		$res = $this->db->query("SELECT a.employeeid, REPLACE(CONCAT(a.LName,', ',a.FName,' ',a.MName), 'Ã‘', 'Ñ') as fullname, b.isIncluded
								 FROM employee a
								 LEFT JOIN longevity_income_included b ON b.employeeid=a.employeeid
								 WHERE (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00')
								 ORDER BY a.LName");
		return $res;
	}

	function saveLongevityEmpIncluded($employeeid=''){
		$res = $this->db->query("SELECT id FROM longevity_income_included 
									WHERE employeeid='$employeeid' ");

		if($res->num_rows() > 0) 	$return = $this->updateLongevityEmpIncluded($res->row(0)->id);
		else 						$return = $this->insertLongevityEmpIncluded($employeeid);

		return $return;
	}

	function updateLongevityEmpIncluded($id=''){
		$res = $this->db->query("UPDATE longevity_income_included SET isIncluded='1' WHERE id='$id'");
		return $res;
	}

	function insertLongevityEmpIncluded($employeeid=''){
		$user = $this->session->userdata('username');
		$res = $this->db->query("INSERT INTO longevity_income_included (employeeid,isIncluded,editedby) 
									VALUES ('$employeeid','1','$user')");
		return $res;
	}

	function saveLongevityComputed($dtr_cutoff_id='',$employeeid='',$credited_years=0,$prev_basicpay=0,$present_basicpay=0,$amount=0){
		$user = $this->session->userdata('username');

		$res = $this->db->query("SELECT id FROM longevity_income_computed
									WHERE employeeid='$employeeid' AND dtr_cutoff_id='$dtr_cutoff_id'");

		if($res->num_rows() > 0) 	$return = $this->updateLongevityComputed($res->row(0)->id,$credited_years,$prev_basicpay,$present_basicpay,$amount,$user);
		else 						$return = $this->insertLongevityComputed($dtr_cutoff_id,$employeeid,$credited_years,$prev_basicpay,$present_basicpay,$amount,$user);
		return $return;
	}

	function updateLongevityComputed($id='',$credited_years=0,$prev_basicpay=0,$present_basicpay=0,$amount=0,$editedby=''){
		$res = $this->db->query("UPDATE longevity_income_computed 
									SET credited_years='$credited_years',
									prev_basicpay='$prev_basicpay',
									present_basicpay='$present_basicpay',
									amount='$amount',
									editedby='$editedby' WHERE id='$id'");
		return $res;
	}

	function insertLongevityComputed($dtr_cutoff_id='',$employeeid='',$credited_years=0,$prev_basicpay=0,$present_basicpay=0,$amount=0,$editedby=''){
		$res = $this->db->query("INSERT INTO longevity_income_computed (employeeid,dtr_cutoff_id,credited_years,prev_basicpay,present_basicpay,amount,editedby) 
									VALUES ('$employeeid','$dtr_cutoff_id','$credited_years','$prev_basicpay','$present_basicpay','$amount','$editedby')");
		return $res;
	}

	function getLongevityEmpComputed($employeeid='',$dtr_cutoff_id='',$campusid=''){
		$wC = '';
		if($employeeid) 	$wC .= " AND a.employeeid='$employeeid'";
		if($campusid) 		$wC .= " AND b.campusid='$campusid'";
		if($dtr_cutoff_id) 	$wC .= " AND a.dtr_cutoff_id='$dtr_cutoff_id'";

		$res = $this->db->query("SELECT a.*, b.campusid, b.deptid, b.dateemployed, REPLACE(CONCAT(b.LName,', ',b.FName,' ',b.MName), 'Ã‘', 'Ñ') as fullname, c.isIncluded
								 FROM longevity_income_computed a
								 LEFT JOIN employee b  ON b.employeeid=a.employeeid
								 LEFT JOIN longevity_income_included c ON c.employeeid = a.employeeid
								 WHERE (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00')
								 $wC
								 ORDER BY b.LName");
		return $res;
	}

	///< ADJUSTMENTS

	function getExistingIncomeAdj($filter=array()){
		$res = $this->db->get_where('employee_income_adj',$filter);
		return $res;
	}

	function updateExistingIncomeAdj($filter=array(),$data=array()){
		$res = $this->db->update('employee_income_adj',$data,$filter);
		return $res;
	}

	function addNewIncomeAdj($data=array()){
		$res = $this->db->insert('employee_income_adj',$data);
		return $res;
	}

	function getLeaveAdjAmount($payroll_cutoff_id='',$empid='',$type='LEAVE'){
		$total_amt = 0;

		$tbl = 'leave_adjustment';
		if($type ==  'OB') $tbl = 'ob_adjustment';
		if($type ==  'CORRECTION') $tbl = 'correction_adjustment';

		$adj_q = $this->db->query("SELECT SUM(amount) as total_amt FROM $tbl WHERE employeeid='$empid' AND payroll_cutoff_id='$payroll_cutoff_id';");
		
		if($adj_q->num_rows() > 0){
			$total_amt = $adj_q->row(0)->total_amt;
		}
		return $total_amt;
	}

	function getLeaveAdjDays($payroll_cutoff_id='',$empid='',$type='LEAVE'){
		$total_days = 0;

		$tbl = 'leave_adjustment';
		if($type ==  'OB') $tbl = 'ob_adjustment';
		if($type ==  'CORRECTION') $tbl = 'correction_adjustment';

		$adj_q = $this->db->query("SELECT SUM(total_days) as total_days FROM $tbl WHERE employeeid='$empid' AND payroll_cutoff_id='$payroll_cutoff_id';");
		if($adj_q->num_rows() > 0){
			$total_days = $adj_q->row(0)->total_days;
		}
		return $total_days;
	}

	function computeOtherIncomeAdj($employeeid='',$total_days=0,$p_start='',$p_end='',$ishourly=false,$total_hours='0:00'){
		$this->load->model('payrollprocess');
		$arr_adj_to_add = array();
		$income_adj_str = '';
		$total_min = 0;

		if($ishourly){
			$total_min = $this->time->hoursToMinutes($total_hours);
		}

		if($total_days > 0 || $total_min > 0){

			$income_config_q = $this->getIncomeSetupList(array('ismainaccount'=>'0','mainaccount'=>'1'));

			$arr_income_config = $this->payrollprocess->constructArrayListFromStdClass($income_config_q,'id','deductedby');

			$oth_income_exist_q = $this->getEmployeeOtherIncomeConfig($employeeid,$p_start,$p_end);

			if($oth_income_exist_q->num_rows() > 0){
				foreach ($oth_income_exist_q->result() as $key => $row) {
					$codeIncome = $row->other_income;
					$daily = $row->daily;
					$minutely = $row->hourly / 60;

					if(isset($arr_income_config[$codeIncome])){
						if(in_array($arr_income_config[$codeIncome]['description'], array('ABSENT','BOTH'))){

							if(!$ishourly) $amount = ( $total_days * $daily);
							else 		   $amount = ( $total_min  * $minutely );

							$arr_adj_to_add[$codeIncome] = $amount;

							if($income_adj_str) $income_adj_str .= '/';
							$income_adj_str .= $codeIncome . '=' . $amount;
						}

					}
				}
			}
		}

		return array($arr_adj_to_add,$income_adj_str);
	}

	function saveIncomeAdj_FromApplication($employeeid='',$payroll_cutoff_id='',$arr_adj_to_add=array()){
		list($x,$x,$p_start,$p_end,$p_quarter,$p_sched) = $this->payrolloptions->getDtrPayrollCutoffPair('','','','','',$payroll_cutoff_id);

		foreach ($arr_adj_to_add as $code_income => $amount) {
			$res = '';
			$data = array();
			$filter = array('employeeid'=>$employeeid,'code_income'=>$code_income);

			$exist_adj = $this->getExistingIncomeAdj($filter);

			$data['datefrom'] 	= $p_start;
			$data['schedule'] 	= $p_sched;
			$data['nocutoff'] 	= 1;
			$data['amount'] 	= $amount;

			if($exist_adj->num_rows() > 0){
				$ex_datefrom 	= $exist_adj->row(0)->datefrom;
				$ex_dateto 		= $exist_adj->row(0)->dateto;
				$ex_deduct 		= $exist_adj->row(0)->deduct;
				$ex_amount 		= $exist_adj->row(0)->amount;

				if($ex_amount > 0){
					$data['datefrom'] 	= $ex_datefrom;
					if($ex_deduct == 1) $data['amount'] = $ex_amount - $amount;
					else 				$data['amount'] = $ex_amount + $amount;
				}else{
					$data['dateto'] = $p_end;
				} 

				$res = $this->updateExistingIncomeAdj($filter,$data);

			}else{
				$data['employeeid'] 	= $employeeid;
				$data['code_income'] 	= $code_income;
				$data['dateto'] 		= $p_end;
				$data['deduct'] 		= 0;
				$data['cutoff_period'] 	= $p_quarter;

				$res = $this->addNewIncomeAdj($data);
			}
		}
	}

		///< 13th Month pay computation -- Ticket #MCU-Hyperion21650 (old ticket MCU-Hyperion21483)
	function compute13thMonthPay_2($employeeid='',$year='',$current_cutoffstart='',$current_cutoffend='',$current_netbasicpay=array(),$current_income_arr=array(), $forPayroll = "", $last_pay = ""){
		$this->load->model('utils');
		$this->load->model('payrollprocess');

		$remaining_cutoff = $this->extensions->getRemainingCutoffForPayroll($employeeid, $current_cutoffstart, $current_cutoffend);

		$deminimiss_list = array();
		$salary_list = $filter = array();

		$total_deduction = 0;
		$latest_processed_month = $amount = $employee_benefits = 0;
		// $isComplete = true;
		$teachingtype = $this->employee->getempdatacol('teachingtype',$employeeid);
		$deptid = $this->employee->getempdatacol('deptid',$employeeid);
		/*get deminimiss income*/
		$included_income = $this->getIncomeIncluded();
		foreach($included_income as $row) $deminimiss_list[$row->id] = $row->id;
		/*end*/
		$latest_processed_month = intval(date('m',strtotime($current_cutoffstart)));

		if($latest_processed_month){

			$config_arr = $this->getIncomeConfigIncludedIn13thMonth();

			$filter['employeeid'] = $employeeid;
			$filter['status'] = "PROCESSED";
			$filter["DATE_FORMAT(cutoffstart,'%Y')"] = $year;

			$yearly_q = $this->utils->getSingleTblData('payroll_computed_table',array('id','cutoffstart','cutoffend','salary','netbasicpay','income','tardy','absents'),$filter);

			foreach ($yearly_q->result() as $key => $row) {
				$month = date('m',strtotime($row->cutoffstart));
				$month = intval($month);

				if(!isset($salary_list[$month]['salary'])) $salary_list[$month]['salary'] = 0;
				$salary_list[$month]['salary'] += $row->salary;

				///< income list
				$income_list = $this->payrollprocess->constructArrayListFromComputedTable($row->income);

				foreach ($income_list as $i_code => $i_amount) {
					if(in_array($i_code, $config_arr)){
						$salary_list[$month]['salary'] += $i_amount;
					}
				}

				/*for employee benefits*/
				foreach ($income_list as $i_code => $i_amount) {
					if(in_array($i_code, $deminimiss_list)){
						$employee_benefits += $i_amount;
					}
				}
				/*end*/

				///< deduc tardy and absents
				$total_deduction += ($row->tardy + $row->absents);
			}

			///< add current cutoff netbasic and income
			if(!isset($salary_list[$latest_processed_month]['salary'])) $salary_list[$latest_processed_month]['salary'] = 0;
			
			if($forPayroll){
				$salary_list[$latest_processed_month]['salary'] += $current_netbasicpay;
				foreach ($current_income_arr as $i_code => $i_amount) {
					if(in_array($i_code, $deminimiss_list)){
						$employee_benefits += $i_amount;
					}
				}
			}

			foreach ($current_income_arr as $i_code => $i_amount) {
				if(in_array($i_code, $config_arr)){
					$salary_list[$month]['salary'] += $i_amount;
				}
			}

			$total_monthly_salary = 0;
			foreach ($salary_list as $month => $det) {
				$total_monthly_salary += $det['salary'];
			}

			if($remaining_cutoff >0){
				$project_salary = $last_pay * $remaining_cutoff;
				$total_monthly_salary += $project_salary;
			}

			$total_monthly_salary -= $total_deduction;

			$amount = $total_monthly_salary / 12;
		}

		/*project employee_benefits*/
		$project_employee_benefits = $this->extensions->getEmployeeOtherIncome($employeeid, $deminimiss_list);
		$project_employee_benefits *= $remaining_cutoff;
		$employee_benefits += $project_employee_benefits;
		/*end*/
		$employee_benefits /= 12;
		return array($amount,$employee_benefits);

	}

	function getIncomeConfigIncludedIn13thMonth(){
		$config_arr = array();
		$includedlist = '61,63,66';
		$res = $this->db->query("SELECT * FROM payroll_income_config WHERE FIND_IN_SET(mainaccount,'$includedlist')");
		foreach ($res->result() as $key => $row) {
			array_push($config_arr, $row->id);
		}
		return $config_arr;
	}

	function getIncomeIncluded(){
        $this->db->from("payroll_income_config");
        $this->db->where('isIncluded', '1');
        $query = $this->db->get();
        return $query->result();
    }

    function getEmployeeSalaryRate($employeeid, $column){
    	$query = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid = '$employeeid' ORDER BY TIMESTAMP DESC LIMIT 1 ");
    	if($query->num_rows() > 0) return $query->row()->$column;
    	else return false;
    }

    function getEmployeeSalaryRate1($employeeid, $column){
    	$query = $this->db->query("SELECT * FROM payroll_employee_salary WHERE employeeid = '$employeeid' ORDER BY TIMESTAMP DESC LIMIT 1 ");
    	if($query->num_rows() > 0) return $query->row()->$column;
    	else return false;    	
    }

    function insertEmployeeDeduction($employeeid, $sdate, $edate, $code_deduction, $ap_13month, $schedule, $quarter){
    	$query = $this->db->query("SELECT * FROM employee_deduction WHERE employeeid = '$employeeid' AND datefrom = '$sdate' AND dateto = '$edate' AND code_deduction = '$code_deduction' ");
    	if($query->num_rows == 0){
	    	$data = array(
	    		"employeeid" => $employeeid,
	    		"datefrom" => $sdate,
	    		"dateto" => $edate,
	    		"code_deduction" => $code_deduction,
	    		"amount" => $ap_13month,
	    		"schedule" => $schedule,
	    		"nocutoff" => "1",
	    		"cutoff_period" => $quarter
	    	);

	    	$this->db->insert("employee_deduction", $data);
	    }else{
	    	$this->db->query("DELETE FROM employee_deduction WHERE employeeid = '$employeeid' AND datefrom = '$sdate' AND dateto = '$edate' AND code_deduction = '$code_deduction' ");
	    	$data = array(
	    		"employeeid" => $employeeid,
	    		"datefrom" => $sdate,
	    		"dateto" => $edate,
	    		"code_deduction" => $code_deduction,
	    		"amount" => $ap_13month,
	    		"schedule" => $schedule,
	    		"nocutoff" => "1",
	    		"cutoff_period" => $quarter
	    	);

	    	$this->db->insert("employee_deduction", $data);
	    }

    /*	$query = $this->db->query("SELECT * FROM payroll_computed_table WHERE employeeid = '$employeeid' AND cutoffstart = '$sdate' AND cutoffend = '$edate' ");
    	if($query->num_rows() > 0) $emp_deduction = $query->row()->otherdeduc;
    	if(!$emp_deduction){
    		$query = $this->db->query("UPDATE payroll_computed_table SET otherdeduc = '$otherdeduc' WHERE employeeid = '$employeeid' AND cutoffstart = '$sdate' AND cutoffend = '$edate' ");

    	}else{
    		$all_deduction = explode("/", $emp_deduction);
    		foreach($all_deduction as $key => $value){
    			$deduc_categ = explode("=", $value);
    			if($deduc_categ[0] != "13"){
    				$query2 = $this->db->query("UPDATE payroll_computed_table SET otherdeduc = '$otherdeduc' WHERE employeeid = '$employeeid' AND cutoffstart = '$sdate' AND cutoffend = '$edate' ");
    			}
    		}
    	}
    	echo "<pre>".$this->db->last_query(); */
    }

    public function saveHolidayPay($project_hol_pay, $eid, $sdate, $edate, $cutoff_period){
		$this->db->query("DELETE FROM employee_income WHERE employeeid = '$eid' AND code_income = '7' ");
    	$emp_income = $this->db->query("SELECT * FROM employee_income WHERE employeeid = '$eid' AND datefrom = '$sdate' AND dateto = '$edate' AND code_income = '7' ");
    	if($emp_income->num_rows() == 0) $this->db->query("INSERT INTO employee_income (employeeid, code_income, datefrom, dateto, amount, nocutoff, schedule, cutoff_period, visibility) VALUES ('$eid', '7', '$sdate', '$edate', '$project_hol_pay', '1', 'semimonthly', '$cutoff_period', 'SHOW')");
    	else $this->db->query("UPDATE employee_income SET amount = '$project_hol_pay' WHERE employeeid = '$eid' AND datefrom = '$sdate' AND dateto = '$edate' AND code_income = '7' ");
    }

    public function saveSuspensionPay($suspension_pay, $eid, $sdate, $edate, $cutoff_period){
		$this->db->query("DELETE FROM employee_income WHERE employeeid = '$eid' AND code_income = '74' ");
    	$emp_income = $this->db->query("SELECT * FROM employee_income WHERE employeeid = '$eid' AND datefrom = '$sdate' AND dateto = '$edate' AND code_income = '74' ");
    	if($emp_income->num_rows() == 0) $this->db->query("INSERT INTO employee_income (employeeid, code_income, datefrom, dateto, amount, nocutoff, schedule, cutoff_period, visibility) VALUES ('$eid', '74', '$sdate', '$edate', '$suspension_pay', '1', 'semimonthly', '$cutoff_period', 'SHOW')");
    	else $this->db->query("UPDATE employee_income SET amount = '$suspension_pay' WHERE employeeid = '$eid' AND datefrom = '$sdate' AND dateto = '$edate' AND code_income = '74' ");
    }

    function updateData($empData,$codeIncome) {
		$this->db->where('code_income', $codeIncome);
		$this->db->where('employeeid', $empData['employeeid']);
      	return $this->db->update('employee_income', $empData);
    }

    function saveIncomeHistory($empData, $ci, $userid){
		$this->db->where('code_income', $ci);
		$this->db->set('userid', $userid);
      	return $this->db->insert('employee_income_history', $empData);
    }

    function insertData($empData) {
  	    return $this->db->insert('employee_income', $empData);
    }

    public function checkIfHasExistingIncome($empid, $code){
    	return $this->db->query("SELECT * FROM employee_income WHERE employeeid = '$empid' AND code_income = '$code' ")->num_rows();
	}
	
	///< COLA
	public function getCOLASetupList($filter=array()){
		$res = $this->db->get_where('payroll_income_cola_config',$filter);
		return $res;
	}

	public function insertCOLASetup($data=array()){
		$res = $this->db->insert('payroll_income_cola_config',$data);
		return $res;
	}

	public function updateCOLASetup($filter=array(),$data=array()){
		$res = $this->db->update('payroll_income_cola_config',$data,$filter);
		return $res;
	}	

	public function getCOLAEffectiveAmount($sdate=''){
		$multiplier = 0;
		$res = $this->db->query("SELECT multiplier FROM payroll_income_cola_config WHERE date_effective <= '$sdate' ORDER BY date_effective DESC LIMIT 1");
		if($res->num_rows(0) > 0) $multiplier = $res->row(0)->multiplier;
		return $multiplier;
	}

	function insertBatchSalary($data){
    	$q_salary = $this->db->query("SELECT * FROM payroll_employee_salary WHERE employeeid = '{$data['employeeid']}'");
    	if($q_salary->num_rows() > 0){
    		$this->db->where("employeeid", $data["employeeid"]);
    		$this->db->set($data);
    		$this->db->update("payroll_employee_salary");
    		return $this->db->insert("payroll_employee_salary_history", $data);
    	}else{ 
    		$this->db->insert("payroll_employee_salary", $data);
    		return $this->db->insert("payroll_employee_salary_history", $data);
    	}
    }

    function updatePerdeptSalary($perdept_d, $salary_id){
    	$q_perdept = $this->db->query("SELECT * FROM payroll_emp_salary_perdept WHERE base_id = '$salary_id' AND aimsdept = '{$perdept_d["aimsdept"]}' ");
    	if($q_perdept->num_rows() > 0){
    		$this->db->set($perdept_d);
    		$this->db->where("base_id", $salary_id);
    		$this->db->where("aimsdept", $perdept_d["aimsdept"]);
    		$this->db->update("payroll_emp_salary_perdept");
    		return $this->db->insert("payroll_emp_salary_perdept_history", $perdept_d);
    	}else{
    		$this->db->insert("payroll_emp_salary_perdept", $perdept_d);
    		return $this->db->insert("payroll_emp_salary_perdept_history", $perdept_d);
    	}
    }

    function savePerdeptSalary($perdept_d){
    	$this->db->insert("payroll_emp_salary_perdept", $perdept_d);
    	return $this->db->insert("payroll_emp_salary_perdept_history", $perdept_d);
    }

    function savePerdeptHistory($salary_id){

    	/*insert salary history*/

    	$this->db->query("INSERT INTO payroll_employee_salary_history (employeeid,workdays,fixedday,workhours,workhoursexemp,monthly,semimonthly,biweekly,weekly,daily,hourly,minutely,SCHEDULE,dependents,addedby,lechour,labhour,honorarium,date_effective, emp_bank, TYPE, rank, whtax, timestamp) SELECT employeeid,workdays,fixedday,workhours,workhoursexemp,monthly,semimonthly,biweekly,weekly,daily,hourly,minutely,SCHEDULE,dependents,addedby,lechour,labhour,honorarium,date_effective, emp_bank, TYPE, rank, whtax, current_timestamp + interval '10' second FROM payroll_employee_salary WHERE id = '$salary_id'");

    	/*get insert id */
    	$base_id = $this->db->insert_id();

    	/*insert history perdept*/
    	$this->db->query("INSERT INTO payroll_emp_salary_perdept_history (base_id, employeeid, aimsdept, lechour, labhour, rlehour) SELECT '$base_id', employeeid, aimsdept, lechour, labhour, rlehour FROM payroll_emp_salary_perdept WHERE base_id = '$salary_id'");
    }

    function saveNewSalary($salary_id, $date_effective,$empid){

    	/*insert salary history*/

    	$this->db->query("INSERT INTO payroll_employee_salary (employeeid,workdays,fixedday,workhours,workhoursexemp,monthly,semimonthly,biweekly,weekly,daily,hourly,minutely,SCHEDULE,dependents,addedby,lechour,labhour,honorarium,date_effective, emp_bank, TYPE, rank, whtax) SELECT employeeid,workdays,fixedday,workhours,workhoursexemp,monthly,semimonthly,biweekly,weekly,daily,hourly,minutely,SCHEDULE,dependents,addedby,lechour,labhour,honorarium,'$date_effective', emp_bank, TYPE, rank, whtax FROM payroll_employee_salary WHERE id = '$salary_id'");

    	/*get insert id */
    	$base_id = $this->db->insert_id();

    	if($this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid = '$empid'")->num_rows() == 0){

	    	$this->db->query("INSERT INTO payroll_employee_salary_history (employeeid,workdays,fixedday,workhours,workhoursexemp,monthly,semimonthly,biweekly,weekly,daily,hourly,minutely,SCHEDULE,dependents,addedby,lechour,labhour,honorarium,date_effective, emp_bank, TYPE, rank, whtax) SELECT employeeid,workdays,fixedday,workhours,workhoursexemp,monthly,semimonthly,biweekly,weekly,daily,hourly,minutely,SCHEDULE,dependents,addedby,lechour,labhour,honorarium,date_effective, emp_bank, TYPE, rank, whtax FROM payroll_employee_salary WHERE id = '$salary_id'");
	    	$history_id = $this->db->insert_id();

	    	/*insert history perdept*/
	    	$this->db->query("INSERT INTO payroll_emp_salary_perdept_history (base_id, employeeid, aimsdept, lechour, labhour, RLE) SELECT '$history_id', employeeid, aimsdept, lechour, labhour, RLE FROM payroll_emp_salary_perdept WHERE base_id = '$salary_id'");
	    }

    	$this->db->query("DELETE FROM payroll_employee_salary WHERE id = '$salary_id'");

    	return $base_id;
    }

    public function getIncomePaymentHistory($employeeid){
    	return $this->db->query("SELECT * FROM payroll_process_income WHERE employeeid = '$employeeid' ORDER BY code_income;");
    }

}

?>