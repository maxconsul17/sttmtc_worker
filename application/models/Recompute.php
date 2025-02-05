<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Recompute extends CI_Model {

	public function getServerTime(){
		$query_time = $this->db->query("SELECT CURRENT_TIMESTAMP ")->row()->CURRENT_TIMESTAMP;
		return $query_time;
	}

    public function get_recompute_task(){
        $this->db->where("status", "pending");
        $this->db->order_by("timestamp", "ASC");
        $this->db->limit(1);
		return $this->db->get("recompute_list");
    }

    public function updateRecomputeStatus($rec_id, $status="done"){
        $this->db->where("id", $rec_id);
        $this->db->set("status", $status);
        if($status == "done"){
            $this->db->set("done_time", $this->getServerTime());
        }
        $this->db->update("recompute_list");
    }

    function getPayrollCutoffBaseId($sdate='',$edate=''){
        $payroll_cutoff_id = '';
        $p_q = $this->db->query("SELECT baseid FROM payroll_cutoff_config WHERE startdate='$sdate' AND enddate='$edate'");
        if($p_q->num_rows() > 0) $payroll_cutoff_id = $p_q->row(0)->baseid;
        return $payroll_cutoff_id;
    }

    function loadAllEmpbyDept($dept = "", $eid = "", $sched = "",$campus="",$company_campus="", $sdate = "", $edate = "", $sortby = "", $office="", $teachingtype="", $empstatus=""){
        $date = date('Y-m-d');
        $whereClause = $orderBy = $wC = "";
        if($sortby == "alphabetical") $orderBy = " ORDER BY fullname";
        if($sortby == "department") $orderBy = " ORDER BY d.description";
        if($dept)   $whereClause .= " AND b.deptid='$dept'";
        if($office)   $whereClause .= " AND b.office='$office'";
        if($teachingtype){ 
            if($teachingtype == "trelated") $whereClause .= " AND b.teachingtype='teaching' AND trelated = 1";
            else $whereClause .= " AND b.teachingtype='$teachingtype'";
        }
        if($empstatus != "all" && $empstatus != ''){
            if($empstatus=="1"){
                $wC .= " AND (('$date' < dateresigned2 OR dateresigned2 = '0000-00-00' OR dateresigned2 = '1970-01-01' OR dateresigned2 IS NULL) AND isactive ='1')";
            }
            if($empstatus=="0"){
                $wC .= " AND (('$date' >= dateresigned2 AND dateresigned2 IS NOT NULL AND dateresigned2 <> '0000-00-00' AND dateresigned2 <> '1970-01-01' ) OR isactive = '0')";
            }
            if(is_null($empstatus)) $wC .= " AND isactive = '1' AND (dateresigned2 = '0000-00-00' OR dateresigned2 = '1970-01-01' OR dateresigned2 IS NULL)";
        }
        if($eid && $eid != "all")    $whereClause .= " AND a.employeeid='$eid'";
        if($campus && $campus != "All")    $whereClause .= " AND b.campusid='$campus'";
        if($company_campus && $company_campus != 'all')    $whereClause .= " AND b.company_campus='$company_campus'";
        if($sdate && $edate) $whereClause .= " AND c.cutoffstart = '$sdate' AND c.cutoffend = '$edate' AND c.`status` = 'PROCESSED' ";
        $utwc = '';
        $utdept = '';
        $utoffice = '';
        // if($this->session->userdata("usertype") == "ADMIN"){
        //   if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
        //   if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
        //   if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
        //   if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
        //   $usercampus =  $this->extras->getCampusUser();
        //   if($usercampus) $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        // }
        $whereClause .= $utwc;
        $query = $this->db->query("SELECT a.*, CONCAT(lname,', ',fname,' ',mname) as fullname,a.$sched as regpay, b.teachingtype, b.employmentstat, b.office
                                     FROM payroll_employee_salary_history a 
                                     INNER JOIN employee b ON b.employeeid = a.employeeid
                                     INNER JOIN processed_employee c ON c.`employeeid` = b.`employeeid`
                                     LEFT JOIN code_office d ON d.`code` = b.`office`
                                     WHERE (b.dateresigned2 = '1970-01-01' OR b.dateresigned2 = '0000-00-00' OR b.dateresigned2 IS NULL OR b.dateresigned2 >= '$date' OR b.dateresigned = '1970-01-01' OR b.dateresigned = '0000-00-00' OR b.dateresigned IS NULL OR b.dateresigned >= '$date') AND a.schedule='$sched' AND a.`date_effective` <= '$sdate' AND a.id = (SELECT id FROM payroll_employee_salary_history WHERE date_effective <= '$sdate'  AND employeeid = b.employeeid ORDER BY date_effective DESC LIMIT 1)  $whereClause GROUP BY employeeid $orderBy ")->result();
        // echo $this->db->last_query();
        // die();
        return $query;
   } 

   function loadAllEmpbyDeptSample($dept = "", $eid = "", $sched = "",$campus="",$company_campus="", $sdate, $edate, $sortby = ""){
        $date = date('Y-m-d');
        $whereClause = $orderBy = "";
        if($sortby == "alphabetical") $orderBy = " ORDER BY fullname";
        if($sortby == "department") $orderBy = " ORDER BY d.description";
        if($dept)   $whereClause .= " AND b.office='$dept'";
        if($eid)    $whereClause .= " AND a.employeeid='$eid'";
        if($campus)    $whereClause .= " AND b.campusid='$campus'";
        if($company_campus)    $whereClause .= " AND b.company_campus='$company_campus'";
        if($sdate && $edate) $whereClause .= " AND c.cutoffstart = '$sdate' AND c.cutoffend = '$edate' AND c.`status` = 'PROCESSED' ";
        $utwc = '';
        // $utdept = $this->session->userdata("department");
        // $utoffice = $this->session->userdata("office");
        // if($this->session->userdata("usertype") == "ADMIN"){
        // if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
        // if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
        // if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
        // if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
        // $usercampus =  $this->extras->getCampusUser();
        // if($usercampus) $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        // }
        $whereClause .= $utwc;
        $query = $this->db->query("SELECT a.*, CONCAT(lname,', ',fname,' ',mname) as fullname,a.$sched as regpay, b.teachingtype, b.employmentstat, b.office
                                    FROM payroll_employee_salary a 
                                    INNER JOIN employee b ON b.employeeid = a.employeeid
                                    INNER JOIN processed_employee c ON c.`employeeid` = b.`employeeid`
                                    INNER JOIN code_office d ON d.`code` = b.`office`
                                    WHERE (b.dateresigned2 = '1970-01-01' OR b.dateresigned2 = '0000-00-00' OR b.dateresigned2 IS NULL OR b.dateresigned2 >= '$date' OR b.dateresigned = '1970-01-01' OR b.dateresigned = '0000-00-00' OR b.dateresigned IS NULL OR b.dateresigned >= '$date') AND a.schedule='$sched' $whereClause GROUP BY employeeid $orderBy ")->result();
        // echo $this->db->last_query();
        // die();
        return $query;
    } 

    function processPayrollSummary($emplist=array(),$emplist2=array(),$sdate='',$edate='',$schedule='',$quarter='',$recompute=false,$payroll_cutoff_id=''){

		$recomputed_emp_payroll = 0;
		$workdays = 0;
		$workhours_lec = $workhours_lab = $workhours_admin = "";

		//< initialize needed info ---------------------------------------------------
		$info    = $arr_income_config = $arr_income_adj_config = $arr_incomeoth_config = $arr_deduc_config = $arr_fixeddeduc_config = $arr_loan_config = array();

		///< ------------------------------ income config ------------------------------------------------------------
		$income_config_q = $this->displayIncome();
		$arr_income_config = $this->constructArrayListFromStdClass($income_config_q,'id','description');

		$arr_income_adj_config = $arr_income_config;
		$arr_income_adj_config['SALARY'] = array('description'=>'SALARY','hasData'=>0);

		///< ------------------------------ incomeoth config ---------------------------------------------------------------
		$incomeoth_config_q = $this->displayIncomeOth();
		$arr_incomeoth_config = $this->constructArrayListFromStdClass($incomeoth_config_q,'id','description');

		///< ------------------------------ fixed deduction config ----------------------------------------------------
		$fixeddeduc_config_q = $this->db->query("SELECT code_deduction,description FROM deductions");
		$arr_fixeddeduc_config = $this->constructArrayListFromStdClass($fixeddeduc_config_q,'code_deduction','description');


		///< ------------------------------ deduction config ----------------------------------------------------------
		$deduction_config_q = $this->displayDeduction();
		$arr_deduc_config = $this->constructArrayListFromStdClass($deduction_config_q,'id','description');
		$arr_deduc_config_arithmetic = $this->constructArrayListFromStdClass($deduction_config_q,'id','arithmetic');


		///< ------------------------------ loan config ---------------------------------------------------------------
		$loan_config_q = $this->displayLoan();
		$arr_loan_config = $this->constructArrayListFromStdClass($loan_config_q,'id','description');

		if($recompute === true){
			foreach($emplist as $row){
				$eid = $row->employeeid;
				$this->db->query("DELETE FROM payroll_computed_table WHERE cutoffstart='$sdate' AND cutoffend='$edate' AND schedule='$schedule' AND quarter='$quarter' AND employeeid='$eid' AND status='PENDING'");
			}
		}

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
			
			$this->saveHolidayPay($project_hol_pay, $eid, $sdate, $edate, $quarter);
			$this->saveSuspensionPay($suspension_pay, $eid, $sdate, $edate, $quarter);
			$info[$eid]['substitute'] = $this->comp->computeSubstitute($eid,$conf_base_id);

			// GAWING 0 YUNG AMOUNT NG TARDY AT ABSENT PARA DI NA BUMAWAS PA KAPAG TEACHING DAHIL NABAWAS NA YUN SA TAAS, APPLICABLE MUNA ITO SA TEACHING LANG.
			$is_trelated = $this->isTeachingRelated($eid);
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
			$is_trelated = $this->isTeachingRelated($eid);
			if(!$is_trelated){
				list($tardy_amount,$absent_amount,$workdays,$x,$x,$conf_base_id, $isFinal) = $this->comp->getTardyAbsentSummaryNT($eid,$tnt,$schedule,$quarter,$sdate,$edate,$hourly,false,$daily);
				$info[$eid]['salary'] 	= $this->comp->computeNTCutoffSalary($workdays,$fixedday,$regpay,$daily,$has_bdayleave,$minimum_wage);
				$info[$eid]['substitute'] = 0;
			}else{
				$perdept_salary = $this->comp->getPerdeptSalaryHistory($eid,$sdate);

				list($tardy_amount,$absent_amount,$workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$perdept_amt_arr,$hold_status,$x,$x,$forFinalPay,$conf_base_id,$excess_min) = $this->comp->getTardyAbsentSummaryTeaching($eid,$tnt,$schedule,$quarter,$sdate,$edate,$hourly,$lechour,$labhour,$rlehour,$perdept_salary);
				list($info[$eid]['salary'], $info[$eid]['teaching_pay']) 	=  $this->comp->computeTeachingCutoffSalary($workhours_lec,$workhours_lab,$workhours_admin,$workhours_rle,$hourly,$lechour,$labhour,$rlehour,$fixedday,$regpay,$perdept_amt_arr,$hold_status);

				list($project_hol_pay, $sub_hol_pay, $suspension_pay) = $this->comp->getProjectHolidayPay($payroll_cutoff_id,$lechour,$labhour,$rlehour,$eid,$sdate);

				$this->saveHolidayPay($project_hol_pay, $eid, $sdate, $edate, $quarter);
				$this->saveSuspensionPay($suspension_pay, $eid, $sdate, $edate, $quarter);
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

		$is_flexi = $this->isFlexiNoHours($eid);
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
			list($_13th_month, $employee_benefits) = $this->compute13thMonthPay_2($eid,date('Y',strtotime($sdate)),$sdate,$edate,$info[$eid]['netbasicpay'],$info[$eid]['income'], true, $regpay);
			if($_13th_month > 0) $this->saveEmployeeOtherIncome($eid,$sdate,$edate,'5',$_13th_month,$schedule,$quarter);
			if($employee_benefits > 0) $this->saveEmployeeOtherIncome($eid,$sdate,$edate,'37',$employee_benefits,$schedule,$quarter);

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

    function validateDTRCutoff($sdate, $edate){
		$q_cutoff = $this->db->query("SELECT * FROM payroll_cutoff_config WHERE startdate = '$sdate' AND enddate = '$edate' ");
		return ($q_cutoff->row()->nodtr) ? true : false;
	}

	///< PENDING STATUS
	function savePayrollCutoffSummaryDraft($data=array(),$data_oth=array()){
		$this->load->model('utils');
		$data['addedby']   = $this->session->userdata('username');
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

	function saveEmployeeOtherIncomeComputed($code_income='',$employeeid='',$dtr_start='',$dtr_end='',$payroll_start='',$payroll_end='',$total_pay='',$total_deduc='',$deduc_hours=''){
		$res = $this->db->query("SELECT id FROM other_income_computed 
									WHERE employeeid='$employeeid' 
									AND code_income='$code_income'
									AND dtr_cutoffstart='$dtr_start' AND dtr_cutoffend='$dtr_end'
									AND payroll_cutoffstart='$payroll_start' AND payroll_cutoffend='$payroll_end'");

		if($res->num_rows() > 0) 	$this->updateEmployeeOtherIncomeComputed($res->row(0)->id,$total_pay,$total_deduc,$deduc_hours);
		else 						$this->insertEmployeeOtherIncomeComputed($code_income,$employeeid,$dtr_start,$dtr_end,$payroll_start,$payroll_end,$total_pay,$total_deduc,$deduc_hours);
	}

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

    public function isFlexiNoHours($empid){
        return $this->db->query("SELECT * FROM code_schedule a INNER JOIN employee b ON a.`schedid` = b.empshift WHERE flexible = 'YES' AND hours = 0 AND employeeid = '$empid'")->num_rows();
    }

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

    function isTeachingRelated($user = ""){
        $return = false;
        $query = $this->db->query("SELECT trelated FROM employee WHERE employeeid='$user'");
        if($query->num_rows() > 0)  $return = ($query->row(0)->trelated == "1" ? true : false);
        return $return;    
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

    function showdepartment($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("code,description");
        $this->db->order_by("description","asc");
        $q = $this->db->get("code_department"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[Globals::_e($row->code)] = Globals::_e($row->description);
        }
        return $returns;
    }

    // income
    function displayIncome($id = ""){
        $whereClause = "";        
        if($id) $whereClause = " WHERE id='$id'";
        $query = $this->db->query("SELECT id,description,taxable,incomeType,grossinc,ismainaccount,mainaccount,deductedby,isIncluded,grosspayNotIncluded,addedby,gl_debit,gl_credit FROM payroll_income_config $whereClause");
        return $query;
    }
    // income
    function displayIncomeOth($id = ""){
        $whereClause = "";        
        if($id) $whereClause = " WHERE id='$id'";
        $query = $this->db->query("SELECT id,description,addedby,taxable,grossinc FROM payroll_income_oth_config $whereClause");
        return $query;
    }
     // deduction
     function displayDeduction($id = ""){
        $whereClause = "";        
        if($id) $whereClause = " WHERE id='$id'";
        $query = $this->db->query("SELECT id,description,arithmetic,addedby,taxable,grossinc,loanaccount,gl_debit,gl_credit FROM  payroll_deduction_config $whereClause");
        return $query;
    }
    // loan
    function displayLoan($id = ""){
        $whereClause = "";        
        if($id) $whereClause = " WHERE id='$id'";
        $query = $this->db->query("SELECT id,description,loan_type,addedby,taxable,grossinc,gl_debit,gl_credit FROM payroll_loan_config $whereClause");
        return $query;
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
    

    function payrollSubTotal($emplist=array()){
		$total = array();
		$old_deptid = "";
		if($emplist){
			foreach($emplist as $row){
				// echo "<pre>"; print_r($row); die;
				$deptid = $row["deptid"];
				if($row["loan"]){
					foreach($row["loan"] as $key => $val){
						if(isset($total[$deptid]["loan"][$key])) $total[$deptid]["loan"][$key] += $val;
						else $total[$deptid]["loan"][$key] = $val;
					}
				}

				if($row["fixeddeduc"]){
					foreach($row["fixeddeduc"] as $key => $val){
						if(isset($total[$deptid]["fixeddeduc"][$key])) $total[$deptid]["fixeddeduc"][$key] += $val;
						else $total[$deptid]["fixeddeduc"][$key] = $val;
					}
				}

				if($row["deduction"]){
					foreach($row["deduction"] as $key => $val){
						if(isset($total[$deptid]["deduction"][$key])) $total[$deptid]["deduction"][$key] += $val;
						else $total[$deptid]["deduction"][$key] = $val;
					}
				}

				if($row["income_adj"]){
					foreach($row["income_adj"] as $key => $val){
						if(isset($total[$deptid]["income_adj"][$key])) $total[$deptid]["income_adj"][$key] += $val;
						else $total[$deptid]["income_adj"][$key] = $val;
					}
				}

				if($row["income"]){
					foreach($row["income"] as $key => $val){
						if(isset($total[$deptid]["income"][$key])) $total[$deptid]["income"][$key] += $val;
						else $total[$deptid]["income"][$key] = $val;
					}
				}

				if(isset($total[$deptid]["salary"])) $total[$deptid]["salary"] += $row["salary"];
				else $total[$deptid]["salary"] = $row["salary"];

				if(isset($total[$deptid]["teaching_pay"])) $total[$deptid]["teaching_pay"] += $row["teaching_pay"];
				else $total[$deptid]["teaching_pay"] = $row["teaching_pay"];

				if(isset($total[$deptid]["tardy"])) $total[$deptid]["tardy"] += $row["tardy"];
				else $total[$deptid]["tardy"] = $row["tardy"];

				if(isset($total[$deptid]["absents"])) $total[$deptid]["absents"] += $row["absents"];
				else $total[$deptid]["absents"] = $row["absents"];

				if(isset($total[$deptid]["overtime"])) $total[$deptid]["overtime"] += $row["overtime"];
				else $total[$deptid]["overtime"] = $row["overtime"];

				if(isset($total[$deptid]["whtax"])) $total[$deptid]["whtax"] += $row["whtax"];
				else $total[$deptid]["whtax"] = $row["whtax"];

				if(isset($total[$deptid]["netbasicpay"])) $total[$deptid]["netbasicpay"] += $row["netbasicpay"];
				else $total[$deptid]["netbasicpay"] = $row["netbasicpay"];

				if(isset($total[$deptid]["grosspay"])) $total[$deptid]["grosspay"] += $row["grosspay"];
				else $total[$deptid]["grosspay"] = $row["grosspay"];

				if(isset($total[$deptid]["netpay"])) $total[$deptid]["netpay"] += $row["netpay"];
				else $total[$deptid]["netpay"] = $row["netpay"];
				if(isset($row["substitute"])){
					if(isset($total[$deptid]["substitute"])) $total[$deptid]["substitute"] += $row["substitute"];
					else $total[$deptid]["substitute"] = $row["substitute"];
				}
			}
		}

		return $total;
	}

    function payrollGrandTotal($emplist=array()){
		$total = array();
		if($emplist){
			foreach($emplist as $row){
				// echo "<pre>"; print_r($row); die;
				if($row["loan"]){
					foreach($row["loan"] as $key => $val){
						if(isset($total["loan"][$key])) $total["loan"][$key] += $val;
						else $total["loan"][$key] = $val;
					}
				}

				if($row["fixeddeduc"]){
					foreach($row["fixeddeduc"] as $key => $val){
						if(isset($total["fixeddeduc"][$key])) $total["fixeddeduc"][$key] += $val;
						else $total["fixeddeduc"][$key] = $val;
					}
				}

				if($row["deduction"]){
					foreach($row["deduction"] as $key => $val){
						if(isset($total["deduction"][$key])) $total["deduction"][$key] += $val;
						else $total["deduction"][$key] = $val;
					}
				}

				if($row["income_adj"]){
					foreach($row["income_adj"] as $key => $val){
						if(isset($total["income_adj"][$key])) $total["income_adj"][$key] += $val;
						else $total["income_adj"][$key] = $val;
					}
				}

				if($row["income"]){
					foreach($row["income"] as $key => $val){
						if(isset($total["income"][$key])) $total["income"][$key] += $val;
						else $total["income"][$key] = $val;
					}
				}

				if(isset($total["salary"])) $total["salary"] += $row["salary"];
				else $total["salary"] = $row["salary"];

				if(isset($total["teaching_pay"])) $total["teaching_pay"] += $row["teaching_pay"];
				else $total["teaching_pay"] = $row["teaching_pay"];

				if(isset($total["tardy"])) $total["tardy"] += $row["tardy"];
				else $total["tardy"] = $row["tardy"];

				if(isset($total["absents"])) $total["absents"] += $row["absents"];
				else $total["absents"] = $row["absents"];

				if(isset($total["overtime"])) $total["overtime"] += $row["overtime"];
				else $total["overtime"] = $row["overtime"];

				if(isset($total["whtax"])) $total["whtax"] += $row["whtax"];
				else $total["whtax"] = $row["whtax"];

				if(isset($total["netbasicpay"])) $total["netbasicpay"] += $row["netbasicpay"];
				else $total["netbasicpay"] = $row["netbasicpay"];

				if(isset($total["grosspay"])) $total["grosspay"] += $row["grosspay"];
				else $total["grosspay"] = $row["grosspay"];

				if(isset($total["netpay"])) $total["netpay"] += $row["netpay"];
				else $total["netpay"] = $row["netpay"];

				if(isset($row["substitute"])){
					if(isset($total["substitute"])) $total["substitute"] += $row["substitute"];
					else $total["substitute"] = $row["substitute"];
				}
			}
		}

		return $total;
	}

    function employeeListForSubSite($dept = "", $eid = "", $sched = "",$campus="",$company_campus="", $sdate = "", $edate = "", $sortby = "", $office="", $teachingtype="", $empstatus=""){
        $date = date('Y-m-d');
        $whereClause = $orderBy = $wC = "";
        if($sortby == "alphabetical") $orderBy = " ORDER BY fullname";
        if($sortby == "department") $orderBy = " ORDER BY d.description";
        if($dept) $whereClause .= " AND b.deptid='$dept'";
        if($office) $whereClause .= " AND b.office='$office'";
        if($teachingtype){ 
            if($teachingtype == "trelated") $whereClause .= " AND b.teachingtype='teaching' AND trelated = 1";
            else $whereClause .= " AND b.teachingtype='$teachingtype'";
        }
        if($empstatus != "all" && $empstatus != ''){
            if($empstatus=="1"){
                $whereClause .= " AND (('$date' < dateresigned2 OR dateresigned2 = '0000-00-00' OR dateresigned2 = '1970-01-01' OR dateresigned2 IS NULL) AND isactive ='1')";
            }
            if($empstatus=="0"){
                $whereClause .= " AND (('$date' >= dateresigned2 AND dateresigned2 IS NOT NULL AND dateresigned2 <> '0000-00-00' AND dateresigned2 <> '1970-01-01' ) OR isactive = '0')";
            }
            if(is_null($empstatus)) $whereClause .= " AND isactive = '1' AND (dateresigned2 = '0000-00-00' OR dateresigned2 = '1970-01-01' OR dateresigned2 IS NULL)";
        }
        
        if($eid) $whereClause .= " AND a.employeeid='$eid'";
        $whereClause .= " AND FIND_IN_SET('$campus', b.subcampusid) ";
        
        $query = $this->db->query("SELECT a.*, CONCAT(lname,', ',fname,' ',mname) as fullname,a.$sched as regpay, b.teachingtype, b.employmentstat, b.office
                                    FROM payroll_employee_salary_history a 
                                    INNER JOIN employee b ON b.employeeid = a.employeeid
                                    LEFT JOIN code_office d ON d.`code` = b.`office`
                                    WHERE (b.dateresigned2 = '1970-01-01' OR b.dateresigned2 = '0000-00-00' OR b.dateresigned2 IS NULL OR b.dateresigned2 >= '$date' OR b.dateresigned = '1970-01-01' OR b.dateresigned = '0000-00-00' OR b.dateresigned IS NULL OR b.dateresigned >= '$date') AND a.schedule='$sched' AND a.`date_effective` <= '$sdate' AND a.id = (SELECT id FROM payroll_employee_salary_history WHERE date_effective <= '$sdate'  AND employeeid = b.employeeid ORDER BY date_effective DESC LIMIT 1)  $whereClause GROUP BY employeeid $orderBy ")->result();
      
        return $query;
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

    function constructArrayListFromStdClass($res='',$key='',$value=''){
	    $arr = array();
	    if($res->num_rows() > 0){
	        foreach ($res->result() as $k => $row) {
	            $arr[$row->$key] = array('description'=>$row->$value,'hasData'=>0);
	        }
	    }
	    return $arr;
	}

    

}