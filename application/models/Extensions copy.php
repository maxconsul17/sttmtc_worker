<?php 
/**
 * @author Max Consul
 * @copyright 2018
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Extensions extends CI_Model {

	/**
	* Query for other db data
	*
	* @return query result
	*/

	public function getLastTimesheetId(){
		$query = $this->db->query("SELECT * FROM timesheet ORDER BY timestamp DESC LIMIT 1");
		if($query->num_rows() > 0 ) return $query->row()->timeid;
		else return FALSE;
	}

	public function getLeaveRequestCode(){
		$query = $this->db->query("SELECT * FROM code_request_form")->result_array();
		$description = array();
		$data = array();
		foreach($query as $row){
			$description = explode(" ", $row['description']);
			$data[$row['code_request']] = $description[0];
		}
		return $data;
	}

	public function getCampusId(){
		$query = $this->db->query("SELECT code FROM code_campus");
		$code_campus = array();
		if($query->num_rows() > 0){
			foreach($query->result_array() as $value){
				$code_campus[$value['code']] = $value['code'];
			}
			return $code_campus;
		}
		else return false;
	}

	public function getCampusLists(){
		$data = array();
		$query = $this->db->query("SELECT * FROM code_campus");
		if($query->num_rows() > 0){
			foreach($query->result_array() as $value){
				$data[$value['code']] = $value['description'];
			}

			return $data;
		}
	}

	public function getBuildingLists(){
		$data = array();
		$query = $this->db->query("SELECT building FROM employee_schedule_history  WHERE building != '' GROUP BY building");
		if($query->num_rows() > 0){
			foreach($query->result_array() as $value){
				$data[$value['building']] = $value['building'];
			}
			return $data;
		}
	}

	// public function getTerminalLists($campus = "", $where){
	// 	$data = array();
	// 	$query = $this->db->query("SELECT terminal_name, id FROM terminal");
	// 	if($query->num_rows() > 0){
	// 		foreach($query->result_array() as $value){
	// 			$data[$value['id']] = $value['terminal_name'];
	// 		}
	// 		return $data;
	// 	}
	// }

	public function getFloorLists(){
		$data = array();
		$query = $this->db->query("SELECT floor FROM employee_schedule_history WHERE floor != '' GROUP BY floor ");
		if($query->num_rows() > 0){
			foreach($query->result_array() as $value){
				$data[$value['floor']] = $value['floor'];
			}

			return $data;
		}
	}

	public function isConsecutiveAbsent($sdate, $edate, $empid){
		$count = 0;
		$old_date = '';
		$date_diff = '';
		$query = $this->db->query("SELECT sched_date FROM `employee_attendance_detailed` WHERE sched_date BETWEEN '$sdate' AND '$edate' AND employeeid = '$empid' AND  absents != '' AND absents != 0 ")->result_array();
		if(count($query) >= 10) return true;
		else return false;
	}

	public function getEmployeeDeptHead($empid){
		$query = $this->db->query("SELECT head FROM employee a INNER JOIN code_office b ON b.`code` = a.`deptid` WHERE employeeid = '$empid' ");
		if($query->num_rows() > 0){
			if($query->row()->head != $empid){
				return $this->getEmployeeName($query->row()->head);
			}else{
				$getDivisionHead = $this->db->query("SELECT divisionhead FROM employee a INNER JOIN code_office b ON b.`code` = a.`deptid` WHERE employeeid = '$empid' ");
				if($getDivisionHead->num_rows() > 0){
					return $this->getEmployeeName($getDivisionHead->row()->divisionhead);
				}
			}
		}

	}

	public function getEmployeeName($empid){
		$query = $this->db->query("SELECT CONCAT(lname, ', ', fname , ' ', mname) AS fullname FROM employee WHERE employeeid = '$empid' ");
		if($query->num_rows() > 0) return $query->row()->fullname;
		else return false;
	}

	public function getLeaveDescription($code){
		$query = $this->db->query("SELECT description FROM code_request_form WHERE code_request = '$code' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return false;
	}

	public function employee_name($empid, $column){
		$query = $this->db->query("SELECT $column FROM employee WHERE employeeid = '$empid' ");
		if($query->num_rows() > 0) return $query->row()->$column;
		else return false;
	}

	public function getEmployeePositionId($empid){
		$query = $this->db->query("SELECT positionid FROM employee WHERE employeeid = '$empid' ");
		if($query->num_rows() > 0) return $query->row()->positionid;
		else return;
	}

	public function getEmplistByOfficeHead($office, $teachingtype){
		$query = $this->db->query("SELECT CONCAT(lname, ' ,', fname, ' .', mname) AS fullname, employeeid FROM employee WHERE office = '$office' AND teachingtype = '$teachingtype' ");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getEmplistByCampusPrincipal($campusid, $teachingtype){
		$query = $this->db->query("SELECT CONCAT(lname, ' ,', fname, ' .', mname) AS fullname, employeeid FROM employee WHERE campusid = '$campusid' AND teachingtype = '$teachingtype' ");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function deleteZeroCutoff($empid, $no_cutoff, $code_income, $be_tag){
		$be_tag = strtolower($be_tag);
		if($no_cutoff == "0"){
			$query = $this->db->query("DELETE FROM employee_$be_tag WHERE employeeid = '$empid' AND code_$be_tag = '$code_income' ");
			if($query) return true;
			else return false;
		}
	}

	public function getZeroCutoff($empid, $no_cutoff, $code_income, $be_tag){
		$be_tag = strtolower($be_tag);
		if($no_cutoff == "0"){
			$query = $this->db->query("SELECT * FROM employee_$be_tag WHERE employeeid = '$empid' AND code_$be_tag = '$code_income' ");
			if($query) return true;
			else return false;
		}
	}

	public function checkIfOfficeHead($empid){
		$query = $this->db->query("SELECT * FROM code_office WHERE head = '$empid' OR divisionhead = '$empid' ");
		if($query->num_rows() > 0) return true;
		else return false;
	}

	public function getRemainingCutoff($dfrom, $dto){
		$query = $this->db->query("SELECT * FROM cutoff WHERE CutoffFrom > '$dfrom' AND CutoffTo >  '$dto' ");
		return $query->num_rows();
	}

	public function getRemainingCutoffForPayroll($employeeid, $dfrom, $dto){
		$query = $this->db->query("SELECT * FROM processed_employee WHERE cutoffstart = '$dfrom' AND cutoffend = '$dto' AND employeeid = '$employeeid' LIMIT 1 ");
		return $query->row()->remaining_cutoff;
	}	

	public function getEmpBank($employeeid){
		$query = $this->db->query("SELECT emp_bank FROM employee WHERE employeeid = '$employeeid' ");
		if($query->num_rows() > 0) return $query->row()->emp_bank;
		else return false;
	}

	public function getEmpBankAccountNo($employeeid){
		$query = $this->db->query("SELECT emp_accno FROM employee WHERE employeeid = '$employeeid' ");
		if($query->num_rows() > 0) return $query->row()->emp_accno;
		else return false;
	}

    public function getBankList(){
     	return $this->db->query("SELECT * FROM code_bank_account")->result_array();
    }

    public function getBankCount(){
     	return $this->db->query("SELECT * FROM code_bank_account")->num_rows();
    }

    public function getBankName($bankCode){
     	$query = $this->db->query("SELECT * FROM code_bank_account WHERE code = '$bankCode'");
     	if($query->num_rows() > 0) return $query->row()->bank_name;
     	else return "";
    }

	public function checkIfPayedPhilhealth($eid, $cutoffstart){
		$philhealth = '';
		$date=date_create($cutoffstart);
        date_sub($date,date_interval_create_from_date_string("5 days"));
        $date = date_format($date,"Y-m-d");
        $checkLastCutoff = $this->db->query("SELECT fixeddeduc FROM payroll_computed_table WHERE employeeid = '$eid' AND '$date' BETWEEN cutoffstart AND cutoffend AND status = 'PROCESSED' ");
        if($checkLastCutoff->num_rows() > 0){
            $emp_fixeddeduc = explode("/", $checkLastCutoff->row()->fixeddeduc);
            foreach($emp_fixeddeduc as $key => $value){
                $emp_deduc = explode("=", $value);
                if(in_array("PHILHEALTH", $emp_deduc)){
                    $philhealth = true;
                }
            }
        }
        if($philhealth) return '';
        else return "PHILHEALTH";
	}

	function GetYearDiffBasedOnToday($date){
		if($date != "0000-00-00"){
			$today = new DateTime("NOW");
			$dateformat = new DateTime($date);
			$diff = $dateformat->diff($today);
			return $diff->y;
		}else{
			return "0";
		}
	}
	
	public function getOfficeDescription($code){
		$query = $this->db->query("SELECT * FROM code_office WHERE code = '$code' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return "No Department";
	}

	public function getOfficeDesc($code){
		$query = $this->db->query("SELECT * FROM code_office WHERE code = '$code' ");
		if($code){
			if($query->num_rows() > 0)return $query->row()->description;
			else return "[DELETED OFFICE]";
		}
		else{
			return "[NO OFFICE]";
		}
	}

	public function getPositionDescription($code){
		$query = $this->db->query("SELECT * FROM code_position WHERE positionid = '$code' ");
		if($query->num_rows() > 0) return GLOBALS::_e($query->row()->description);
		else return "No Position";
	}

	public function getDeparmentDescriptionReport($code=''){
		
		if(!$code || $code == 'null'){
			return "No Department";
		}else{
			/**
			 * aragon
			 * ganto kasi fomrat nya ngayon ACAD,ACAD,ADMIN,ADMIN,ADMIN
			 * kaya i-find_in_set nalang and i-distinct para iwas duplicate ng department
			 */
			// $query = $this->db->query("SELECT * FROM code_department WHERE code = '$code' ");
			$query = $this->db->query("SELECT GROUP_CONCAT(DISTINCT description) as description FROM code_department WHERE FIND_IN_SET(code,'$code')");

			if($query->num_rows() > 0) return $query->row()->description? GLOBALS::_e($query->row()->description) : "No Department";
			else return "No Department";
		}
	}

	public function getOfficeDescriptionReport($code=''){
		if(!$code || $code == 'null'){
			return "No Office";
		}else{
			/**
			 * aragon
			 * ganto kasi fomrat nya ngayon ANT,ADMIN,NT
			 * kaya i-find_in_set nalang and i-distinct para iwas duplicate ng office
			 */
			// $query = $this->db->query("SELECT * FROM code_office WHERE code = '$code' ");
			$query = $this->db->query("SELECT GROUP_CONCAT(DISTINCT description) as description FROM code_office WHERE FIND_IN_SET(code,'$code')");
			if($query->num_rows() > 0) return $query->row()->description? GLOBALS::_e($query->row()->description) : "No Office";
			else return "No Office";
		}
	}

	public function getCutoffdate($date){
		$startdate = $enddate = '';
		$query = $this->db->query("SELECT * FROM payroll_cutoff_config WHERE '$date' BETWEEN DATE_FORMAT(startdate, '%Y-%m') AND DATE_FORMAT(enddate, '%Y-%m') ");
		if($query->num_rows() > 0){
			$data = $query->result_array();
			return array($data[0]['startdate'], $data[1]['enddate']);
		}
		else return false;
	}

	public function getHRHead(){
		$query = $this->db->query("SELECT CONCAT(lname, ' ,', fname , ' ,', mname) AS fullname FROM employee WHERE positionid = '99' ");
		if($query->num_rows() > 0) return $query->row()->fullname;
		else return false;
	}

	public function getEmployeeOtherIncome($employeeid, $deminimiss_list){
		$where_clause = '';
		foreach($deminimiss_list as $key => $value){
			if(!$where_clause) $where_clause .= " AND (other_income = '$key' ";
			if($where_clause) $where_clause .= " OR other_income = '$key' ";
		}
		if($where_clause) $where_clause .= " ) ";
		$query = $this->db->query("SELECT SUM(monthly) as total FROM other_income WHERE employeeid = '$employeeid' $where_clause ");
		return $query->row()->total;

	}

	public function getDisciplinaryActionSetup(){
		$query = $this->db->query("SELECT * FROM code_disciplinary_action_sanction ");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getDisciplinarySanctions($code){
		$query = $this->db->query("SELECT * FROM code_disciplinary_action_offense_type WHERE code = '$code' ");
		if($query->num_rows() >0) return $query->row()->sanctions;
		else return " = 0";
	}

	public function getIncomeSetup(){
		$query = $this->db->query("SELECT * FROM payroll_income_config");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getIncomeDesc($code){
		$query = $this->db->query("SELECT * FROM payroll_income_config WHERE id = '$code' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return false;
	}

	public function getDeductionSetup(){
		$query = $this->db->query("SELECT * FROM payroll_deduction_config");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getDeductionDesc($code){
		$query = $this->db->query("SELECT * FROM payroll_deduction_config WHERE id = '$code' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return false;
	}

	public function getLoanSetup(){
		$query = $this->db->query("SELECT * FROM payroll_loan_config");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getLoanDesc($code){
		$query = $this->db->query("SELECT * FROM payroll_loan_config WHERE id = '$code' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return false;
	}

	public function getFixedDeductionSetup(){
		$array = array(
			"SSS" => "SSS",
			"PHILHEALTH" => "PHILHEALTH",
			"PAGIBIG" => "PAGIBIG FUND",
			"PERAA" => "PERAA",
		);
		return $array;
	}

	public function getFixedDeductionDesc($code){
		$query = $this->db->query("SELECT * FROM deductions WHERE code_deduction = '$code' ");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getSpecialVoucherData($type){
		$where_clause = " WHERE type = '$type' ";
		if($type == "income") $data['income'] = $this->getIncomeSetup();
		else if($type == "deduction") $data['deduction'] = $this->getDeductionSetup();
		else if($type == "loan") $data['loan'] = $this->getLoanSetup();
		else if($type == "regdeduction") $data['regdeduction'] = $this->getFixedDeductionSetup();
		else if($type != "witholdingtax"){ 
			$data['income'] = $this->getIncomeSetup();
			$data['deduction'] = $this->getDeductionSetup();
			$data['loan'] = $this->getLoanSetup();
			$data['regdeduction'] = $this->getFixedDeductionSetup();
			$where_clause = "";
		}
		$query = $this->db->query("SELECT * FROM special_voucher $where_clause");
		if($query->num_rows() > 0){
			$data['records'] = $query->result_array();
			return $data;
		}
		else return false;
	}

	public function insertSpecialVoucher($data){
		$query = $this->db->insert("special_voucher", $data);
		if($query) return true;
		else return false;
	}

	public function editSpecialVoucherData($employeeid = "", $category = "", $account = ""){
		if($category != "witholdingtax"){
			$query = $this->db->query("SELECT * FROM special_voucher a INNER JOIN employee b ON b.employeeid = a.employeeid WHERE a.employeeid = '$employeeid' AND a.type = '$category' AND a.account = '$account' ");
		}else{
			$query = $this->db->query("SELECT * FROM special_voucher a INNER JOIN employee b ON b.employeeid = a.employeeid WHERE a.employeeid = '$employeeid' AND a.type = '$category' ");
		}
		if($query->num_rows() > 0) return $query->result_array();
		return false;
	}

	public function updateVoucherData($data){
		$this->db->where('employeeid', $data['employeeid']);
		$this->db->where('type', $data['type']);
		$this->db->where('account', $data['account']);
		$this->db->set($data);
		$query = $this->db->update('special_voucher');
		if($query) return true;
		else return false;
	}

	public function deleteVoucherData($data){
		if($data['type'] != "witholdingtax"){
			$this->db->where('employeeid', $data['employeeid']);
			$this->db->where('type', $data['type']);
			$this->db->where('account', $data['account']);
			$query = $this->db->delete('special_voucher');
		}else{
			$this->db->where('employeeid', $data['employeeid']);
			$this->db->where('type', $data['type']);
			$query = $this->db->delete('special_voucher');
		}
		if($query) return true;
		else return false;
	}

	public function getActiveEmployees(){
		$query = $this->db->query("SELECT * FROM employee WHERE (dateresigned = '1970-01-01' OR dateresigned='0000-00-00' OR dateresigned IS NULL) AND isactive = 1 ");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getUsageLoginData(){
    	$q_dept = $this->db->query("SELECT COUNT(DISTINCT username) AS LOG, DATE_FORMAT(DATE(`timestamp`), '%M') AS DATE FROM login_attempts_hris WHERE STATUS = 'success' GROUP BY MONTH(DATE(`timestamp`)) LIMIT 12")->result_array();
		return $q_dept;
    }

	public function getTimeInAccuracy($empid, $timein){
        $return = array("","");
        $islate = false;
        $last_id = "";
        $sched = $this->attcompute->displaySched($empid,date("Y-m-d"));
        foreach($sched->result() as $rsched){
        	if($empid != $last_id){
	            $stime = $rsched->tardy_start;
	            if(strtotime($stime) < strtotime($timein)) $islate = true;
	            else $islate = false;
	        }

	        $last_id = $empid;
        }
        return $islate;
    }

	public function getTaxableIncome(){
		$query = $this->db->query("SELECT * FROM payroll_income_config WHERE taxable = 'withtax' ");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getAllCutoffPerYear($year){
		$query =$this->db->query("SELECT * FROM payroll_cutoff_config WHERE DATE_FORMAT(startdate, '%Y') = '$year' AND  DATE_FORMAT(enddate, '%Y') = '$year' ORDER BY startdate ASC");
		if($query->num_rows() > 0) return $query->result_array();
		else return false;
	}

	public function getPayrollComputedData($startdate, $enddate, $campus='', $sortby='', $company=''){
		$where = "WHERE STATUS ='PROCESSED' AND cutoffstart = '$startdate' AND cutoffend = '$enddate'";
		$sortby = "";
		if($campus && $campus != 'all') $where .= " AND b.campusid =  '$campus'";
		if($company && $company != 'all') $where .= " AND b.company_campus =  '$company'";
		if($sortby == "department"){
			$orderby = 'b.office, b.lname';
		}else{
			$orderby = 'b.lname';
		}

		$utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
        }
        $where .= $utwc;

		$query =$this->db->query("SELECT CONCAT(lname, ', ', fname, ', ', mname) AS fullname ,a.* FROM payroll_computed_table a INNER JOIN employee b ON b.`employeeid` = a.`employeeid` $where ORDER BY $orderby");
		if($query->num_rows() > 0) return $query->result_array();
		else return array();
	}

	public function checkIfSystemIsRecomputing($tnt){
		$query = $this->db->query("SELECT * FROM recomputing_percentage WHERE teachingtype = '$tnt' ");
		if($query->num_rows() > 0){
			$emp_count = $query->row()->emp_count;
			$emp_total = $query->row()->emp_total;
			$success = $query->row()->success;
			$failed = $query->row()->failed;
			if(!$emp_count && !$emp_total && !$success && !$failed) return true;
			else return false;
		}
	}

	public function getSpecialVoucherDataForAlphalist(){
		$utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (deptid, '$utdept') OR FIND_IN_SET (office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND employeeid = 'nosresult'";
		$usercampus = $this->extras->getCampusUser();
		$utwc .= " AND FIND_IN_SET (campusid,'$usercampus') ";
        }
        if($utwc) $utwc = " AND employeeid IN (SELECT employeeid FROM employee WHERE 1 $utwc)";
		$query_special_voucher = $this->db->query("SELECT * FROM special_voucher WHERE 1 $utwc");
		if($query_special_voucher->num_rows() > 0) return $query_special_voucher->result_array();
		else return array();
	}

	public function getEmployeeSalary($startdate = '', $enddate = '', $employeeid = ''){
		$query_empsalary = $this->db->query("SELECT * FROM payroll_computed_table WHERE employeeid = '$employeeid' AND cutoffstart = '$startdate' AND cutoffend = '$enddate' ");
		if($query_empsalary->num_rows() > 0) return $query_empsalary->row()->salary;
		else return false;
	}

	public function getEmployeeLatestSalary($empid){
		$query_salary = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid = '$empid' ORDER BY date_effective DESC LIMIT 1 ");
		if($query_salary->num_rows() > 0) return array($query_salary->row()->monthly,$query_salary->row()->daily,$query_salary->row()->hourly,$query_salary->row()->date_effective);
		else{
			$query_salary = $this->db->query("SELECT * FROM payroll_employee_salary WHERE employeeid = '$empid' ORDER BY date_effective DESC LIMIT 1 ");
			if($query_salary->num_rows() > 0) return array($query_salary->row()->monthly,$query_salary->row()->daily,$query_salary->row()->hourly,$query_salary->row()->date_effective);
		}
	}

	public function getDepartmentDescription($deptid){
		$query_dept = $this->db->query("SELECT * FROM code_department WHERE code = '$deptid' ");
		if($query_dept->num_rows() > 0) return GLOBALS::_e($query_dept->row()->description);
		else return "No Department";
	}

	public function getServerTime(){
		$query_time = $this->db->query("SELECT CURRENT_TIMESTAMP ")->row()->CURRENT_TIMESTAMP;
		return $query_time;
	}

	public function getNotIncludedInGrosspayIncome(){
		$data = array();
		$query_income = $this->db->query("SELECT * FROM payroll_income_config WHERE grosspayNotIncluded = '0' ");
		if($query_income->num_rows() > 0){
			foreach ($query_income->result_array() as $key => $value) {
				$data[$value['id']] = $value['id'];
			}
		}
		return $data;
	}

	public function getCampusDescription($campusid, $allcampus=false, $specific=false){
		$return = $allcampus === true ? "All Campus" : $specific ? " " : "No Campus";
		$query = $this->db->query("SELECT * FROM code_campus WHERE code = ".$this->db->escape($campusid)." ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return $return;
	}

	public function getTerminalName($terminalid){
		$query = $this->db->query("SELECT * FROM terminal WHERE username = '$terminalid' ");
		if($query->num_rows() > 0){
			$campus = $query->row()->campus;
			$q_campus = $this->db->query("SELECT * FROM code_campus WHERE code = '$campus'");
			if($q_campus->num_rows() > 0) return $q_campus->row()->description;
			else return false;
		}
		else{
			return false;
		}
	}

	public function getCompanyDescription($id){
		$query = $this->db->query("SELECT * FROM campus_company WHERE campus_code = ".$this->db->escape($id)." ");
		if($query->num_rows() > 0) return $query->row()->company_description;
		else return "All Company";
	}

	public function getCompanyDescriptionReports($id){
		$query = $this->db->query("SELECT * FROM campus_company WHERE campus_code = ".$this->db->escape($id)." ");
		if($query->num_rows() > 0) return $query->row()->company_description;
		else return " ";
	}

	public function getAimsDesc($code) {
		$q_sebdesc = $this->db->query("SELECT * FROM aims_department WHERE education_level = '$code' GROUP BY education_level ORDER BY description ASC ");
		return ($q_sebdesc && $q_sebdesc->num_rows() > 0) ? Globals::_e($q_sebdesc->row()->description) : "";
	}
	

	function getFacultyLoadsClassfication() {
		return $this->db->query("SELECT id, description FROM faculty_load_classification")->result();
	}
	public function getMultipleCompany($id){
		$query = $this->db->query("SELECT company_description FROM campus_company WHERE campus_code = ".$this->db->escape($id)."")->result_array();
		if (count($query) > 0) {
			foreach ($query as $key => $val) {
				$data[$key] = $val;
			}
			return $data;
		}
		else{
			return 'No Company';
		}
	}

	public function getCompanyDescriptionAll($id){
		// $query = $this->db->query("SELECT * FROM campus_company WHERE campus_code = ".$this->db->escape($id)." ");
		// if($query->num_rows() > 0) return $query->row()->company_description;
		// else return "All Company";
		if($id){
			return $id;
		}
		else{
			return 'All Company';
		}
	}

	public function getAttendanceAdjustmentRecords($fv, $datesetfrom, $datesetto){
		$data = array();
		$where_clause = '';
		$cutoff_id = $this->getDTRCutoffId($datesetfrom, $datesetto);
		if($fv) $where_clause .= " AND b.employeeid = '$fv' ";
		$utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
        }
        $where_clause .= $utwc;
		$query_ob = $this->db->query("SELECT a.*, CONCAT(lname, ', ', fname, ', ', mname) AS fullname FROM ob_adjustment a INNER JOIN employee b ON a.`employeeid` = b.`employeeid` WHERE payroll_cutoff_id = '$cutoff_id' $where_clause ");
		if($query_ob->num_rows() > 0) $data['ob_adjustment'] = $query_ob->result_array();

		$query_leave = $this->db->query("SELECT a.*, CONCAT(lname, ', ', fname, ', ', mname) AS fullname FROM leave_adjustment a INNER JOIN employee b ON a.`employeeid` = b.`employeeid` WHERE payroll_cutoff_id = '$cutoff_id' $where_clause ");
		if($query_leave->num_rows() > 0) $data['leave_adjustment'] = $query_leave->result_array();

		$query_correction = $this->db->query("SELECT a.*, CONCAT(lname, ', ', fname, ', ', mname) AS fullname FROM correction_adjustment a INNER JOIN employee b ON a.`employeeid` = b.`employeeid` WHERE payroll_cutoff_id = '$cutoff_id' $where_clause ");
		if($query_correction->num_rows() > 0) $data['correction_adjustment'] = $query_correction->result_array();

		return $data;
	}

	public function getPayrollCutoffConfig($dfrom, $dto){
		$cutoff = explode("-", $dtr_cutoff);
		$query_date = $this->db->query("SELECT * FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.`id` = b.`baseid` WHERE CutoffFrom = '$dfrom' AND CutoffTo = '$dto' ");
		if($query_date->num_rows() > 0) return date("F d, Y", strtotime($query_date->row()->startdate))." - ".date("F d, Y", strtotime($query_date->row()->enddate));
		else return date('Y-m-d');
	}

	public function getPayrollCutoffConfigArr($dfrom, $dto){
		$cutoff = explode("-", $dtr_cutoff);
		$query_date = $this->db->query("SELECT * FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.`id` = b.`baseid` WHERE CutoffFrom = '$dfrom' AND CutoffTo = '$dto' ");
		if($query_date->num_rows() > 0) return array($query_date->row()->startdate, $query_date->row()->enddate);
		else return array(null,null);
	}

	public function getDTRCutoffConfig($dfrom, $dto){
		// $cutoff = explode("-", $dtr_cutoff);
		$query_date = $this->db->query("SELECT * FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.`id` = b.`baseid` WHERE startdate = '$dfrom' AND enddate = '$dto' ");
		if($query_date->num_rows() > 0){
			$cutoffdate = (date('F Y',strtotime($query_date->row()->CutoffFrom)) == date('F Y',strtotime($query_date->row()->CutoffTo))) ? date('F d',strtotime($query_date->row()->CutoffFrom)).' -  '.date('d, Y',strtotime($query_date->row()->CutoffTo)) : date('F d',strtotime($query_date->row()->CutoffFrom)).' -  '.date('F d, Y',strtotime($query_date->row()->CutoffTo));
			return $cutoffdate;
		}
		else{ 
			return "";
		}
	}

	public function getDTRCutoffConfigPayslip($dfrom, $dto){
		$cutoff = explode("-", $dtr_cutoff);
		$query_date = $this->db->query("SELECT * FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.`id` = b.`baseid` WHERE startdate = '$dfrom' AND enddate = '$dto' ");
		if($query_date->num_rows() > 0){
			$cutoffdate = (date('F Y',strtotime($query_date->row()->CutoffFrom)) == date('F Y',strtotime($query_date->row()->CutoffTo))) ? date('F d',strtotime($query_date->row()->CutoffFrom)).' -  '.date('d, Y',strtotime($query_date->row()->CutoffTo)) : date('F d',strtotime($query_date->row()->CutoffFrom)).' -  '.date('F d, Y',strtotime($query_date->row()->CutoffTo));
			return array($query_date->row()->CutoffFrom, $query_date->row()->CutoffTo);
		}
		else{ 
			return "";
		}
	}

	public function getDTRCutoffConfigArr($dfrom, $dto){
		$query_date = $this->db->query("SELECT * FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.`id` = b.`baseid` WHERE startdate = '$dfrom' AND enddate = '$dto' ");
		if($query_date->num_rows() > 0){
			$cutoffdate = (date('F Y',strtotime($query_date->row()->CutoffFrom)) == date('F Y',strtotime($query_date->row()->CutoffTo))) ? date('F d',strtotime($query_date->row()->CutoffFrom)).' -  '.date('d, Y',strtotime($query_date->row()->CutoffTo)) : date('F d',strtotime($query_date->row()->CutoffFrom)).' -  '.date('F d, Y',strtotime($query_date->row()->CutoffTo));
			return array($query_date->row()->CutoffFrom, $query_date->row()->CutoffTo);
		}
		else{ 
			return "";
		}
	}

	public function getDeminimissIncomeKeys(){
		$data = array();
		$query_income = $this->db->query("SELECT * FROM payroll_income_config WHERE incomeType = 'deminimiss' ");
		if($query_income->num_rows() > 0){
			foreach ($query_income->result_array() as $key => $value) {
				$data[$value['id']] = $value['id'];
			}
		}
		return $data;
	}

	public function getNonDeminimissIncomeKeys(){
		$data = array();
		$query_income = $this->db->query("SELECT * FROM payroll_income_config WHERE incomeType != 'deminimiss' ");
		if($query_income->num_rows() > 0){
			foreach ($query_income->result_array() as $key => $value) {
				$data[$value['id']] = $value['id'];
			}
		}
		return $data;
	}

	public function getAllIncomeKeysAndDescription(){
		$data = array();
		$query_income = $this->db->query("SELECT * FROM payroll_income_config");
		if($query_income->num_rows() > 0){
			foreach ($query_income->result_array() as $key => $value) {
				$data[$value['id']] = $value['description'];
			}
		}
		return $data;
	}

	public function getDeductioConfignKeys(){
		$data = array();
		$query_deduction = $this->db->query("SELECT * FROM payroll_deduction_config");
		if($query_deduction->num_rows() > 0){
			foreach ($query_deduction->result_array() as $key => $value) {
				$data[$value['id']] = $value['id'];
			}
		}
		return $data;
	}
	public function monthSelection(){
		return array(
			"01" => "January",
			"02" => "February",
			"03" => "March",
			"04" => "April",
			"05" => "May",
			"06" => "June",
			"07" => "July",
			"08" => "August",
			"09" => "September",
			"10" => "October",
			"11" => "November",
			"12" => "December"
		);
	}

	public function getTotalLeaveAndHoliday($employeeid, $sdate, $edate, $tnt=""){
		$query_att;
		if($tnt == "teaching") $query_att = $this->db->query("SELECT SUM(eleave + vleave + sleave + oleave + tholiday) AS total FROM attendance_confirmed WHERE employeeid = '$employeeid' AND payroll_cutoffstart = '$sdate' AND payroll_cutoffend = '$edate' ");
		else $query_att = $this->db->query("SELECT SUM(eleave + vleave + sleave + oleave + isholiday) AS total FROM attendance_confirmed_nt WHERE employeeid = '$employeeid' AND payroll_cutoffstart = '$sdate' AND payroll_cutoffend = '$edate' ");

		if($query_att->num_rows() > 0) return $query_att->row()->total;
		else return false;
	}

	public function getDTRCutoffId($datefrom,$dateto){
		$q_dtrcutoff = $this->db->query("SELECT * FROM cutoff WHERE CutoffFrom = '$datefrom' AND CutoffTo = '$dateto' ");
		if($q_dtrcutoff->num_rows() > 0){
			$dtr_id	= $q_dtrcutoff->row()->ID;
			$q_payrollcutoff = $this->db->query("SELECT * FROM payroll_cutoff_config WHERE baseid = '$dtr_id' ");
			if($q_payrollcutoff->num_rows() > 0) return $q_payrollcutoff->row()->id;
		}
		else return false;
	}

	public function getEmployeeTeachingType($employeeid){
		$q_type = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
		if($q_type->num_rows() > 0) return Globals::_e($q_type->row()->teachingtype);
		else return false;
	}
	
	public function getAllEmployeeId() {
		$q_type = $this->db->query("SELECT employeeid FROM employee");
		if($q_type->num_rows() > 0) return $q_type->result_array();
		else return false;
	}


	public function getEmployeeList(){
		$q_employeelist = $this->db->query("SELECT CONCAT(lname, ' ,', fname, ' .', mname) AS fullname, employeeid, lname, fname, mname, deptid, office, bdate, gender, campusid, mobile, cp_name, cp_mobile, teachingtype, emp_sss, emp_tin, emp_philhealth, emp_peraa, emp_pagibig, addr, mobile, landline, email, emptype, cp_address, cp_relation, positionid FROM employee WHERE employeeid = '2018-05-002' ");
		if($q_employeelist->num_rows() > 0) return $q_employeelist->result_array();
		else return false;
	}

	public function getStudentList(){
		$q_employeelist = $this->db->query("SELECT * FROM student LIMIT 100 ");
		if($q_employeelist->num_rows() > 0) return $q_employeelist->result_array();
		else return false;
	}

	public function updateEmployeeCardnumber($employeeid, $rfid){
		if($this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' AND employeecode != '$rfid' ")->num_rows()){
			$this->db->query("UPDATE employee SET employeecode = '$rfid' WHERE employeeid = '$employeeid' ");
			return true;
		}else{
			return false;
		}
	}

	public function verifyAccessToken($token){
		return $this->db->query("SELECT * FROM token_allowed WHERE access_token = '$token' ")->num_rows();
	}

	public function checkIfDeptIsBED($code){
		$q_bed = $this->db->query("SELECT * FROM code_office WHERE code = '$code' ");
		if($q_bed->num_rows() > 0) return $q_bed->row()->isBED;
		else return false;
	}

	public function checkIfCutoffNoDTR($cutoffstart, $cutoffto){
        $cutoffid = $this->db->query("SELECT ID FROM cutoff WHERE CutoffFrom = '$cutoffstart' AND CutoffTo = '$cutoffto' ")->row()->ID;
        $q_nodtr = $this->db->query("SELECT nodtr FROM payroll_cutoff_config WHERE baseid = '$cutoffid' ");
        if($q_nodtr->num_rows() > 0) return $q_nodtr->row()->nodtr;
        else return false;
	}

	public function checkIfCollegeTeaching($employeeid){
		$collegeDepartment = $this->loadCollegeDepartment();
		$collegeDepartment = "'".implode("','", $collegeDepartment). "'";
		$q_employee = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' AND deptid IN ($collegeDepartment)");
		if($q_employee->num_rows() > 0) return true;
		else return false;
	}

	public function loadCollegeDepartment(){
		$data = array();
		$q_dept = $this->db->query("SELECT * FROM code_department #WHERE iscollege = '1' ");
		if($q_dept->num_rows() > 0){
			foreach($q_dept->result_array() as $row){
				$data[$row['code']] = $row['code'];
			}
		}

		return $data;
	}

    public function empTeachingType($eid)
    {
        $return = "";
        $query = $this->db->query("SELECT teachingtype FROM employee WHERE employeeid='$eid'");
        if ($query->num_rows() > 0) {
            $return = $query->row()->teachingtype;
        }
        return $return;
    }	

    public function checkIfSecondApprover($idkey, $table){
    	$tbl = "";
    	if($table == "leave") $tbl = "leave_app_emplist";
    	elseif($table == "overtime") $tbl = "ot_app_emplist";
    	elseif($table == "ob") $tbl = "ob_app_emplist";
    	elseif($table == "changesched") $tbl = "change_sched_app_emplist";
    	elseif($table == "servicecredit") $tbl = "sc_app_emplist";
    	elseif($table == "useservicecredit") $tbl = "sc_app_use_emplist";
    	elseif($table == "seminar") $tbl = "seminar_app_emplist";
    	elseif($table == "substitute") $tbl = "substitute_app_emplist";
		$issecond = false;
		$q_leave = $this->db->query("SELECT * FROM $tbl WHERE id = '$idkey' ");
		if($q_leave->num_rows() > 0){
			foreach($q_leave->result_array() as $row){
				foreach($row as $value){
					if($value == "APPROVED") $issecond = true;
				}	
			}
		}

		return $issecond;
	}

	public function getBEDDepartments(){
        $data = array();
        $records = $this->db->query("SELECT * FROM code_office WHERE isBED != '1' ")->result_array();
        foreach($records as $row) $data[] = $row["code"];

        return $data;
    }

    public function getEmployeeEmail($employeeid){
		$q_email = "";
		if(!$this->extras->findIfAdmin($employeeid)) $q_email = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' OR email = '$employeeid' ");
		else $q_email = $this->db->query("SELECT * FROM user_info WHERE username = '$employeeid' ");
		if($q_email->num_rows() > 0) return $q_email->row()->email;
		if($q_email->num_rows() > 0) return $q_email->row()->email;
		else return false;
	}

	public function getEmployeePersonalEmail($employeeid) {
		$res = $this->db->query("SELECT personal_email FROM employee WHERE employeeid='$employeeid'");
		if($res && $res->num_rows()>0) return $res->row()->personal_email;
		return '';
	}

	public function generateRandomPassword($length = 10){
	    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}

	public function generateRandomPasswordNumber($length = 6){
	    $characters = '0123456789';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}

	public function forgotPassStatusKey($userid, $key, $action){
		if ($action == "insert") {
			return $this->db->query("INSERT INTO forgot_password_history (`userid`,`key`) VALUES ('$userid', '$key') ");
		}else{
			return $this->db->query("UPDATE forgot_password_history SET `status` = 'READ' WHERE `key` = '$key'");
		}
	}

    public function getEmployeeGender($employeeid){
    	$gender_arr = array(""=>"Not set yet","2"=>"MALE", "1"=>"FEMALE");
    	$gender = $this->db->query("SELECT gender FROM employee WHERE employeeid = '$employeeid' ")->row()->gender;
    	return isset($gender_arr[$gender]) ? $gender_arr[$gender] : "";
    }

    public function checkIfDeptHead($userid){
    	return $this->db->query("SELECT * FROM code_department WHERE head = '$userid' OR divisionhead = '$userid' ")->num_rows();
    }

    public function checkIfCampusPrincipal($userid){
    	// return $this->db->query("SELECT * FROM code WHERE campus_principal = '$userid' ")->num_rows();
    }

    public function getAllDepartmentUnder($userid){
    	$data = array();
    	$q_dept = $this->db->query("SELECT * FROM code_department WHERE head = '$userid' OR divisionhead = '$userid' ");
    	if($q_dept->num_rows() > 0){
    		foreach($q_dept->result_array() as $row){
    			$data[] = $row["code"];
    		}
    	}
    	return $data;
    }

    public function getAllOfficeUnder($userid){
    	$data = array();
    	$q_office = $this->db->query("SELECT * FROM code_office WHERE head = '$userid' OR divisionhead = '$userid' ");
    	if($q_office->num_rows() > 0){
    		foreach($q_office->result_array() as $row){
    			$data[] = $row["code"];
    		}
    	}
   		$q_office2 = $this->db->query("SELECT * FROM campus_office WHERE (/*hrhead='$userid' OR */dhead='$userid' OR divisionhead='$userid'/* OR phead='$userid'*/)");
   		if($q_office2->num_rows() > 0){
    		foreach($q_office2->result_array() as $row){
    			if(!in_array($row["base_code"], $data)) $data[] = $row["base_code"];
    		}
    	}
    	return $data;
    }

    public function getAllCampusUnder($userid){
    	$data = array();
    	$q_campus = $this->db->query("SELECT * FROM code WHERE campus_principal = '$userid' ");
    	if($q_campus->num_rows() > 0){
    		foreach($q_campus->result_array() as $row){
    			$data[] = $row["code"];
    		}
    	}
    	return $data;
    }

    public function getEmplistForDepartmentAttendance($where_clause, $tnt){
    	if($tnt && $tnt != 'undefined'){
			if($tnt != "trelated") $where_clause .= " AND teachingtype = '$tnt' ";
      		else $where_clause .= " AND teachingtype='teaching' AND trelated = '1'";
		}

    	return $this->db->query("SELECT CONCAT(lname, ' ,', fname , ' ,', mname) AS fullname, employeeid, office, deptid FROM employee WHERE employeeid != '' $where_clause ")->result_array();
    }

    public function getEmployeeFname($employeeid){
    	return $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ")->row()->fname;
    }

    public function getEmployeeMname($employeeid){
    	return $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ")->row()->mname;
    }

    public function getEmployeeLname($employeeid){
    	return $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ")->row()->lname;
    }

    public function getEmployeeDepartment($employeeid){
    	$q_dept = $this->db->query("SELECT description FROM employee a INNER JOIN code_department b ON a.`deptid` = b.`code` WHERE employeeid = '$employeeid' ");
    	if($q_dept->num_rows() > 0) return $q_dept->row()->description;
    	else return "Not assigned";
    }

    public function getEmployeeOfficeDesc($employeeid){
    	$q_office = $this->db->query("SELECT description FROM employee a INNER JOIN code_office b ON a.`office` = b.`code` WHERE employeeid = '$employeeid' ");
    	if($q_office->num_rows() > 0) return $q_office->row()->description;
    	else return "Not assigned";
    }

    public function getEmployeePositionDesc($empid){
		$query = $this->db->query("SELECT description FROM employee a INNER JOIN code_position b ON a.`positionid` = b.`positionid` WHERE employeeid = '$empid' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return;
	}

    public function getBirthdayCelebrantsToday(){
    	$datenow = date("m-d", strtotime($this->db->query("SELECT CURRENT_TIMESTAMP ")->row()->CURRENT_TIMESTAMP));
      	$q_bday = $this->db->query("SELECT * FROM employee WHERE DATE_FORMAT(bdate, '%m-%d') = '$datenow' LIMIT 5 ");
      	if($q_bday->num_rows() > 0) return $q_bday->result_array();
      	else return false;
    }

	public function sendEmailToNextApprover($approver_id){
		$email = $this->extensions->getEmployeeEmail($approver_id);

		$fullname = $this->extensions->getEmployeeName($approver_id);

		if($email && $fullname){
			$data["approver_name"] = $fullname;

			$this->load->model("email");
			$this->email->sendEmailForOnlineApplication($email, $data);
		}
		return true;
	}

    public function getAppSequenceForEmail($type=""){
    	$res = $this->db->query("SELECT dhseq,  chseq,  hhseq,  cpseq,  dpseq,  fdseq,  boseq,  pseq,  upseq FROM code_request_form WHERE code_request='$type'")->result_array();
    	return $res;
    }

    public function getCurrentCutoff($date_now){
    	$q_cutoff = $this->db->query("SELECT * FROM cutoff WHERE '$date_now' BETWEEN ConfirmFrom AND ConfirmTo ");
    	if($q_cutoff->num_rows() > 0){
    		if($q_cutoff->row()->ConfirmFrom && $q_cutoff->row()->ConfirmTo) return array($q_cutoff->row()->CutoffFrom, $q_cutoff->row()->CutoffTo);
    		else return false;
    	}else{
    		return false;
    	}
    }

    public function getSubjectDescription($id){
    	$q_sebdesc = $this->db->query("SELECT * FROM code_subj_competent_to_teach WHERE id = '$id' ");
    	if($q_sebdesc->num_rows() > 0) return Globals::_e($q_sebdesc->row()->description);
    	return "--";
    }

    public function getCourseDescription($id){
    	$q_coursedesc = $this->db->query("SELECT * FROM tblCourseCategory WHERE CODE = '$id' ");
    	if($q_coursedesc->num_rows() > 0) return $q_coursedesc->row()->DESCRIPTION;
    	else return "--";
    }

     public function getCourseDescriptionByCode($code){
    	$q_coursedesc = $this->db->query("SELECT * FROM tblCourseCategory WHERE CODE = '$code' ");
    	if($q_coursedesc->num_rows() > 0) return $q_coursedesc->row()->DESCRIPTION;
    	else return "";
    }

    public function getApplicantStatusDesc($id){
    	$q_statusdesc = $this->db->query("SELECT * FROM code_applicant_status WHERE id = '$id' ");
    	if($q_statusdesc->num_rows() > 0){
    		if($q_statusdesc->row()->isrequirements == 1){
    			return 'Initial Requirements';
    		}else if($q_statusdesc->row()->isprerequirements == 1){
    			return 'Pre Requirements';
    		}else{
    			return $q_statusdesc->row()->description;
    		}
    	}
    	else return false;
    }

	public function getHolidayHalfdayTime($date, $isFirstSched = ""){
		$where_clause = "";
		if($isFirstSched) $where_clause = " AND sched_count = '$isFirstSched'" ;
		$q_holiday = $this->db->query("SELECT * FROM code_holiday_calendar WHERE '$date' BETWEEN date_from AND date_to $where_clause ");
		if($q_holiday->num_rows() > 0) return array($q_holiday->row()->fromtime,$q_holiday->row()->totime);
		else return false;
	}

	public function getDTRCutoffByPayrollCutoffID($pcutoff_id){
		$q_cutoff = $this->db->query("SELECT * FROM cutoff WHERE id = '$pcutoff_id' ");
		if($q_cutoff->num_rows() > 0){
			return array($q_cutoff->row()->CutoffFrom, $q_cutoff->row()->CutoffTo);
		}else{
			return array("", "");
		}
	}

	public function getApplicantCampus($applicantId){
		$campusid = $this->db->query("SELECT campusid FROM applicant WHERE applicantId = '$applicantId' ")->row()->campusid;
		return $this->extensions->getCampusDescription($campusid);
	}

	public function getApplicantPosition($applicantId){
    	$positionid = $positiondesc = "";
    	$q_position = $this->db->query("SELECT * FROM applicant WHERE applicantId = '$applicantId' ");
    	if($q_position->num_rows() > 0) $positionid = $q_position->row()->positionApplied;
    
    	$q_posdesc = $this->db->query("SELECT * FROM code_position WHERE positionid = '$positionid' ");
    	if($q_posdesc->num_rows() > 0) $positiondesc = $q_posdesc->row()->description;

    	return $positiondesc;
    }  

	public function getEmployeeDeptid($employeeid){
		$q_dept = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
		if($q_dept->num_rows() > 0) return $q_dept->row()->deptid;
		else return false;
	}

	public function getEmployeeOffice($employeeid){
		$q_dept = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
		if($q_dept->num_rows() > 0) return $q_dept->row()->office;
		else return false;
	}

	public function getHolidayTypeRate($holiday_type, $teachingtype){
		$q_holiday = $this->db->query("SELECT * FROM code_holiday_type WHERE holiday_type = '$holiday_type'");
		if($q_holiday->num_rows() > 0){
			if($teachingtype == "teaching") return $q_holiday->row()->t_rate;
			else return $q_holiday->row()->nt_rate;
		}
	}

	public function getSuspensionTypeRate($holiday_type, $teachingtype){
		$q_holiday = $this->db->query("SELECT * FROM code_holiday_type WHERE holiday_type = '$holiday_type'");
		if($q_holiday->num_rows() > 0){
			if($teachingtype == "teaching") return $q_holiday->row()->nat_rate;
			else return $q_holiday->row()->nant_rate;
		}
	}

	public function employeeBirthdate($employeeid){
		$q_bday = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
		if($q_bday->num_rows() > 0) return $q_bday->row()->bdate;
		else return false;
	}

	public function employeeDateEmployed($employeeid){
		$q_employed = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
		if($q_employed->num_rows() > 0) return $q_employed->row()->dateemployed;
		else return false;
	}

	public function generateEmployeeEmail($fname, $mname, $lname){

		/*replace enye with n for gsuite validation*/
		$fname = str_replace("Ñ", "N", $fname);
		$lname = str_replace("Ñ", "N", $lname);
		$mname = str_replace("Ñ", "N", $mname);
		$fname = str_replace("ñ", "n", $fname);
		$lname = str_replace("ñ", "n", $lname);
		$mname = str_replace("ñ", "n", $mname);

		$fname = str_replace(" ", "", $fname);
		$lname = str_replace(" ", "", $lname);
		$mname = str_replace(" ", "", $mname);
		$email = isset($fname[0]) ? strtolower($fname[0]) : '';
		$email .= isset($mname[0]) ? strtolower($mname[0]) : '';
		$email .= isset($lname) ? strtolower($lname)."@fatima.edu.ph" : '';
		/*if($_SERVER["HTTP_HOST"] != "192.168.2.32"){
			$client = Api_helper::getClientToken();
	        $service = new Google_Service_Directory($client);
	            
	        // Build the User Object
	        $user = new Google_Service_Directory_User();
	      	try {
	        	$email = isset($lname) ? strtolower($lname) : '';
				$email .= isset($fname) ? strtolower($fname) : '';
				$email .= isset($mname[0]) ? strtolower($mname[0])."@fatima.edu.ph" : '';
			} catch (\Google_Service_Exception $e) {
				
			}
		}*/
		return $email;
	}

	public function getDocumentSetup(){
		$q_document = $this->db->query("SELECT * FROM code_documents");
		return $q_document->result_array();
	}

	public function getDocumentDescription($code){
		$q_doc = $this->db->query("SELECT * FROM code_documents WHERE code = '$code' ");
		if($q_doc->num_rows() > 0) return $q_doc->row()->description;
		else return false;
	}

	public function getDPA($userid){
		$q_dpa = $this->db->query("SELECT * FROM employee where employeeid = '$userid'");
		if($q_dpa->num_rows() > 0) return $q_dpa->row()->dpa;
		else return false;
	}

	public function acceptDPA($userid){
		return $this->db->query("UPDATE employee set dpa = '1' where employeeid = '$userid'");
	}

	public function loadTeachingEmployee(){
		return $this->db->query("SELECT CONCAT(lname, ' ,', fname, ' .', mname) AS fullname, employeeid FROM employee WHERE teachingtype = 'teaching'")->result_array();
	}

	public function loadTeachingEmployeeSelect2($where = '', $lc = ''){
		$utwc = '';
		$utdept = $this->session->userdata("department");
		$utoffice = $this->session->userdata("office");
		if($this->session->userdata("usertype") == "ADMIN"){
			if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
			if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
			if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
		}
		
		return $this->db->query("SELECT CONCAT(a.employeeid, ' - ', a.lname, ', ', a.fname, ' ', a.mname) AS fullname, a.employeeid FROM employee a $where AND a.isactive='1' $utwc order by lname, fname, mname $lc")->result_array();
	}

	public function loadTeachingEmployeeSelect2AccountType($where = '', $lc = '', $accounttype = '', $usertype= ''){
		$wc = $utwc = '';
		$utdept = $this->session->userdata("department");
		$utoffice = $this->session->userdata("office");
		if($accounttype && $accounttype != 'undefined') $wc .= " AND b.type = '$accounttype' ";
		if($usertype && $usertype != 'undefined') $wc .= " AND c.code = '$usertype' ";
		if($this->session->userdata("usertype") == "ADMIN"){
			if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
			if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
			if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
		}
		$wc .= $utwc;
		return $this->db->query("SELECT CONCAT(a.employeeid, ' - ', a.lname, ', ', a.fname, ' ', a.mname) AS fullname, a.employeeid FROM employee a INNER JOIN user_info b ON a.employeeid = b.username INNER JOIN user_type c ON b.user_type = c.code $where $wc order by lname, fname, mname $lc")->result_array();
	}

	public function getCompany($empid){
		return $this->db->query("SELECT * FROM employee WHERE employeeid = '$empid'")->row()->company_campus;
	}

	public function checkIfDepartmentPrincipal($employeeid){
		$positionid = 0;
		// $query = $this->db->query("SELECT * FROM code_department2 WHERE dept_principal = '$employeeid' ");
		
		// return ($query->num_rows() > 0) ? true : false;
	}

	public function getPayrollTypeDesc($code){
        $q_type = $this->db->query("SELECT * FROM rank_code_type");
        if($q_type->num_rows() > 0) return $q_type->row()->description;
        else return false;
    }

    public function checkUserForgotPass($name){
		$q_email = $this->db->query("SELECT b.id, a.email,b.email AS emailAdmin, a.personal_email, b.username, b.type FROM user_info b LEFT JOIN employee a ON b.username = a.employeeid WHERE b.email = '$name' OR a.email = '$name' OR a.personal_email = '$name' OR b.username = '$name' LIMIT 1");
		$data = array();
		if($q_email->num_rows() > 0){
			$data["userid"] = $q_email->row()->id;
			if ($q_email->row()->type == "ADMIN" || $q_email->row()->type == "SUPER ADMIN") {

				$data["email"] = $q_email->row()->emailAdmin;
				return $data;
			}else{
				$data["email"] = $q_email->row()->email;
				return $data;
			}	
		} 
		else{
			return false;
		} 
	}

	public function checkUserForgotKey($key){
		$q_email = $this->db->query("SELECT * FROM forgot_password_history WHERE `key` = '$key'");
		if($q_email->row()->status == "SENT"){
			return $q_email->row()->userid;
		} 
		else{
			return false;
		} 
	}

	public function getMonthDescription($code){
		$array = array(
			"January"  => "01",
			"February" => "02",
			"March"	   => "03",
			"April"    => "04",
			"May"      => "05",
			"June"     => "06",
			"July"     => "07",
			"August"   => "08",
			"September"=> "09",
			"October"  => "10",
			"November" => "11",
			"December" => "12"
		);
		$key = array_search($code, $array);
		return $key;
	}

	public function getRelationDesc($relationid){
		$q = $this->db->query("SELECT * FROM code_relationship WHERE relationshipid='$relationid'");
		if($q->num_rows() > 0) return Globals::_e($q->row()->description);
		else return " ";
	}

	public function getEmployeeDeparment($employeeid){
    	$q_dept = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
    	if($q_dept->num_rows() > 0) return $q_dept->row()->deptid;
    	else return false;
    }

    public function getPayrollBank($employeeid="",$dfrom="",$dto=""){
    	$query = $this->db->query("SELECT * FROM payroll_computed_table a LEFT JOIN code_bank_account b ON a.bank = b.code WHERE employeeid = '$employeeid' AND cutoffstart = '$dfrom' AND cutoffend = '$dto'");
    	if($query->num_rows() > 0) return $query->row()->bank_name;
    	else return false;
    }

    public function greetingsMessage(){
    	return $this->db->query("SELECT * FROM announcement WHERE type = 'birthday' AND employeeid = ''");
    }

    public function agreementMessage($campusid, $company_campus, $officeid, $deptid){
    	$username = $this->session->userdata('username');
    	$today = date('Y-m-d');
    	return $this->db->query("SELECT a.* FROM announcement a INNER JOIN announcement_dept b ON a.id = b.base_id WHERE a.announcement = 'agreement' AND (a.campus = '$campusid' OR a.campus = 'All') AND (a.company_campus = ".$this->db->escape($company_campus)." OR a.company_campus = 'all') AND (a.officeid = '$officeid' OR a.officeid = 'alloffice') AND (b.deptid = '$deptid' OR b.deptid = 'alldept') AND a.posted_until >= '$today' AND a.popup = 'YES' AND a.id NOT IN (SELECT announcement_id FROM agreement_logs WHERE username = '$username') ORDER BY a.posted_until ASC");
    }

    public function getEmployee201Files($table, $base_id){
		$filename = $content = $mime = $dbname = '';
		$dbname = $this->db->database_files; 
        // if($_SERVER["HTTP_HOST"] == "192.168.2.97") $dbname = "HRIS_STTHRESE_files";
        // else if($_SERVER["HTTP_HOST"] == "hris.fatima.edu.ph" && strpos($_SERVER["REQUEST_URI"], 'training') !== false) $dbname = "TRNGHrisFiles";
        // else if($_SERVER["HTTP_HOST"] == "hris.fatima.edu.ph" && strpos($_SERVER["REQUEST_URI"], 'hris') !== false) $dbname = "HRIS_STTHRESE_files"; 
		$query = $this->db->query("SELECT * FROM $dbname.employee201_files WHERE table_name = '$table' AND base_id = '$base_id'");
		if($query->num_rows() > 0){
			$filename = $query->row()->filename;
			$content = $query->row()->content;
			$mime = $query->row()->mime;
		}
		else{
			$dbname = 'no data gather';
		}
		return array($filename, $content, $mime);
	}

	public function getTableFiles($table, $base_id){
		$filename = $content = $mime = $dbname = '';
        $dbname = $this->db->database_files;  
		$query = $this->db->query("SELECT * FROM $dbname.table_files WHERE table_name = '$table' AND base_id = '$base_id' ORDER BY ID DESC LIMIT 1");
		if($query->num_rows() > 0){
			$filename = $query->row()->filename;
			$content = $query->row()->content;
			$mime = $query->row()->mime;
		}
		return array($filename, $content, $mime);
	}

	public function getHRIS_STTHRESE_files($table, $base_id){
		$filename = $content = $mime = $dbname = '';
		$dbname = $this->db->database_files;
        
		$query = $this->db->query("SELECT * FROM $dbname.$table WHERE base_id = '$base_id'");
		if($query->num_rows() > 0){
			$filename = $query->row()->filename;
			$content = $query->row()->content;
			$mime = $query->row()->mime;
		}
		return array($filename, $content, $mime);
	}

	public function deleteTableFiles($table, $base_id){
		$dbname = $this->db->database_files;
          
		return $this->db->query("DELETE FROM $dbname.table_files WHERE table_name = '$table' AND base_id = '$base_id'");
		
	}


	public function campusOfficeHead($code, $campus, $head=""){
		$wc = "WHERE 1=1";
		if($code) $wc .= " AND base_code = '$code'";
		if($campus) $wc .= " AND campus = '$campus'";
		$q_campus = $this->db->query("SELECT $head FROM campus_office $wc");
		if(!$q_campus){
			return false;
		}
		if($q_campus->num_rows() > 0) return $q_campus->row()->$head;
		else return false;
	}

	public function getOfficeByManagementID($id){
		$q_office = $this->db->query("SELECT * FROM code_office WHERE managementid = '$id'");
		if($q_office->num_rows() > 0) return $q_office->row()->code;
		else return false;
	}

	public function isNursingDepartment($eid){
		return $this->db->query("SELECT * FROM employee WHERE (deptid = '14' OR office = '122') AND employeeid = '$eid'")->num_rows();
	}

	public function isNursingExcluded($eid){
		return $this->db->query("SELECT * FROM employee WHERE (deptid = '14' OR office = '122') AND nursing_excluded = '1' AND employeeid = '$eid'")->num_rows();
	}

	public function isMedicineDepartment($eid){
		return $this->db->query("SELECT * FROM employee WHERE (deptid = '13' OR office = '99') AND employeeid = '$eid'")->num_rows();
	}

	public function getScheduleDescription($schedid){
		$query = $this->db->query("SELECT description FROM code_schedule WHERE schedid = '$schedid' ");
		if($query->num_rows() > 0) return $query->row()->description;
		else return false;
	}

	public function isHoliday($employeeid, $date, $deptid){
		$holiday = $this->attcompute->isHolidayNew($employeeid,$date,$deptid ); 
		if($holiday){
			$holidayInfo = $this->attcompute->holidayInfo($date);
			return $holidayInfo["description"];
		}else{
			return false;
		}
	}

	public function getAdminName($userid){
		$query = $this->db->query("SELECT CONCAT(lastname, ', ', firstname , ' ', middlename) AS fullname FROM user_info WHERE username = '$userid' ");
		if($query->num_rows() > 0){
			return $query->row()->fullname;
		}else{ 
			$query = $this->db->query("SELECT CONCAT(lastname, ', ', firstname , ' ', middlename) AS fullname FROM user_info WHERE id = '$userid' ");
			if($query->num_rows() > 0){
				return $query->row()->fullname;
			}else{
				return false;
			}
		}
	}

	public function getEemployeeCurrentData($employeeid, $column, $dateformat=''){
    	$query = $this->db->query("SELECT $column FROM employee WHERE employeeid = '$employeeid' ");
    	if($query->num_rows() > 0){
    		if($dateformat){
    			if($query->row()->$column != '' && $query->row()->$column != '0000-00-00' && $query->row()->$column != '1970-01-01') return date($dateformat, strtotime($query->row()->$column));
    			else return false;
    		}else{
    			return $query->row()->$column;
    		}
    	}
    	else return false;
    }

    public function getApproverList($codes, $campusid){
    	if(is_array($codes)) $codes = implode(',', $codes);
    	$query = $this->db->query("SELECT divisionhead,hrhead,phead, base_code, dhead FROM campus_office WHERE FIND_IN_SET (base_code, '$codes') AND campus = '$campusid' ");
    	if($query->num_rows() > 0){
    		return $query->result_array();
    	}else{
    		return false;
    	}
    }

    public function getOBtypeDesc($code){
    	$query = $this->db->query("SELECT * FROM ob_type_list WHERE status = '1' AND id = '$code'");
    	if($query->num_rows() > 0) return $query->row()->type;
    	else return false;
    }

    public function isOBWFH($id){
    	return $this->db->query("SELECT * FROM ob_type_list WHERE id = '$id' AND iswfh = '1' ")->num_rows();
    }

    public function isHolidaySuspension($holiday_id){
    	return $this->db->query("SELECT * FROM code_holiday_type WHERE holiday_type = '$holiday_id' AND is_suspension = '1'")->num_rows();
    }

    public function hasGsuiteAccount($employeeid, $email){
    	return $this->db->query("SELECT * FROM gsuite_accounts WHERE employeeid = '$employeeid' AND email = '$email' ")->num_rows();
    }

    public function is_employee_exists($eid){
    	return $this->db->query("SELECT * FROM employee WHERE employeeid = '$eid'")->num_rows();
    }

    public function getCampusDescriptionByAimsdept($aimsdept="", $employeeid=""){
		$q_sched = $this->db->query("SELECT campus FROM employee_schedule_history WHERE aimsdept = '$aimsdept' AND employeeid = '$employeeid'");
		if($q_sched->num_rows() > 0){
			return $this->getCampusDescription($q_sched->row()->campus);
		}else{
			return false;
		}
	}

	public function getLatestDateActive($employeeid, $date){
    	$query = $this->db->query("SELECT dateactive FROM employee_schedule_history WHERE employeeid = '$employeeid' AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) ORDER BY dateactive DESC LIMIT 1");
    	if($query->num_rows() > 0) return $query->row()->dateactive;
    	else return false;
    }

    public function is_teaching_related($employeeid){
    	return $this->db->query("SELECT * FROM employee WHERE teachingtype = 'nonteaching' AND trelated = '1' AND employeeid = '$employeeid'")->num_rows();
    }

    public function is_employee_resigned($date, $employeeid){
    	return $this->db->query("SELECT * FROM employee WHERE dateresigned2 <= '$date' AND dateresigned2 != '0000-00-00' AND employeeid = '$employeeid'")->num_rows();
    }

    public function getAccountName($userid){
		$query = $this->db->query("SELECT CONCAT(lastname, ', ', firstname , ' ', middlename) AS fullname FROM user_info WHERE username = '$userid' ");
		if($query->num_rows() > 0) return $query->row()->fullname;
		else return false;
	}
	public function getAccountName2($userid){
		$query = $this->db->query("SELECT CONCAT(lname, ', ', fname , ' ', mname) AS fullname FROM employee WHERE employeeid = '$userid' ");
		if($query->num_rows() > 0) return $query->row()->fullname;
		else return false;
	}

	public function getClearanceID($separation){
		$query = $this->db->query("SELECT clearance_id FROM separation_data WHERE id = '$separation'");
		if($query->num_rows() > 0) return $query->row()->clearance_id;
		else return false;
	}

	public function getClearanceForms($clearance_id){
		$ef = $af = 0;
		$query = $this->db->query("SELECT * FROM clearance_type WHERE id = '$clearance_id'");
		if($query->num_rows() > 0){
			$ef = $query->row()->exit;
			$af = $query->row()->accountability;
		}
		return array($ef, $af);
	}

	public function getDepartmentDescriptionByManagement($deptid){
		$query_dept = $this->db->query("SELECT a.description FROM code_department a INNER JOIN code_office b ON a.code = b.managementid WHERE b.managementid = '$deptid' ");
		if($query_dept->num_rows() > 0) return GLOBALS::_e($query_dept->row()->description);
		else return "No Department";
	}

	

	public function getUserType(){
		$query_status = $this->db->query("SELECT * FROM user_type ORDER BY code ASC");
		$data = array();
		if($query_status){
			foreach ($query_status->result_array() as $key => $value) {
				$data[$value['code']."|".$value['description']] = $value;
			}
			return $data;
		}else{
			return false;
		}
	}

	public function getAbsencesRemarks($employeeid = "",$date_absent = ""){
		if($employeeid != ''){
			$query_status = $this->db->query(
				"SELECT emp.employeeid,lb.type,le.status,cd.description 
				 FROM employee as emp 
				 	LEFT JOIN leave_app_base as lb ON lb.applied_by=emp.employeeid 
					LEFT JOIN leave_app_emplist as le ON le.base_id=emp.employeeid 
					LEFT JOIN code_request_form cd ON cd.code_request = lb.type
				 WHERE emp.employeeid = '$employeeid'
				");
			return $query_status->row(0);
		}else{
			return false;
		}
	}

	public function checkAbsencesAppliedLeaveApplication($employeeid="",$dateAbsent=""){
		
		if($employeeid !="" && $dateAbsent !=""){
			$query = $this->db->query(
				"SELECT ead.employeeid,
						COALESCE((SELECT DISTINCT description FROM code_request_form AS crf WHERE lab.type=crf.code_request), '') AS type,lab.paid,lae.status 
				 FROM employee_attendance_detailed AS ead 
					  LEFT JOIN leave_app_base AS lab ON lab.applied_by=ead.employeeid 
					LEFT JOIN leave_app_emplist AS lae ON lae.id=lab.id 
				 WHERE DATE(ead.sched_date) BETWEEN DATE(lab.datefrom) AND DATE(lab.dateto) AND ead.employeeid='$employeeid' AND ead.sched_date='$dateAbsent'");
	
			if($query->num_rows() > 0){
				return $query->row(0);
				
			}else{
				return false;
			}
		}else{
			return false;
		}
		
	}
	
	public function separationDescription($id){
		$q_separation = $this->db->query("SELECT * FROM separation_data WHERE id = '$id'");
		if($q_separation->num_rows() > 0) return $q_separation->row()->description;
		else return false;
	}

	public function employeeSeparation($employeeid){
		return $this->db->query("SELECT separation FROM employee WHERE employeeid = '$employeeid'")->row()->separation;
	}

	public function getCutoff($date_now){
    	$q_cutoff = $this->db->query("SELECT * FROM cutoff WHERE '$date_now' BETWEEN `CutoffFrom` AND `CutoffTo` ");
    	if($q_cutoff->num_rows() > 0){
    		if($q_cutoff->row()->ConfirmFrom && $q_cutoff->row()->ConfirmTo) return array($q_cutoff->row()->CutoffFrom, $q_cutoff->row()->CutoffTo);
    		else return false;
    	}else{
    		return false;
    	}
    }

    public function getLoanType($id){
    	$q_loan = $this->db->query("SELECT * FROM payroll_loan_config WHERE id = '$id' ");
    	if($q_loan->num_rows() > 0) return $q_loan->row()->loan_type;
    	else return false;
    }

	function menuTitle($id){
		$q = $this->db->query("SELECT title FROM menus WHERE menu_id = '$id'");
		if($q->num_rows() > 0) return $q->row()->title;
		else return "No title.";
	}

	public function getATMAccountNumber($empid){
		$q_emp = $this->db->query("SELECT emp_bank FROM employee WHERE employeeid = '$empid' ");
		if($q_emp->num_rows() > 0){
			return $q_emp->row()->emp_bank;
		}else{
			return "000-000-000-000";
		}
	}

	public function daysWeekList(){
		return $this->db->query("SELECT * FROM code_daysofweek")->result_array();
	}

	public function getEvaluationYearPerEmployee($employeeid, $selected_year = ""){
		$return = "<option value=''>Select Year</option>";
		$query = $this->db->query("SELECT * FROM evaluation_score WHERE employeeid = '$employeeid' GROUP BY year ORDER BY year DESC ");
		// echo "<pre>"; print_r($this->db->query()); die;
		foreach($query->result() as $row){
          $year = $row->year;
          if($year){
            if($selected_year == $year)   $return .= "<option value='$year' selected>$year</option>";
            else                $return .= "<option value='$year'>$year</option>";   
          } 
        }
        return $return;
	}

	public function getEvaluationSemester($employeeid, $selected_year = "", $selected_sem = ""){
		$wc = "";
		if($selected_year) $wc .= " AND year = '$selected_year'";
		// if($selected_sem) $wc .= " AND semester = '$selected_sem'";
        $return = "<option value=''>Select Semester</option>";
		$query = $this->db->query("SELECT * FROM evaluation_score WHERE employeeid = '$employeeid' $wc");
		foreach($query->result() as $row){
          $semester = $row->semester;
          if($semester){
            if($selected_sem == $semester)   $return .= "<option value='$semester' selected>$semester</option>";
            else                $return .= "<option value='$semester'>$semester</option>";   
          } 
        }
        return $return;
    }

    public function getEvaluationScore($employeeid, $sem, $year){
    	$score = "";
    	$query = $this->db->query("SELECT * FROM evaluation_score WHERE employeeid = '$employeeid' AND year = '$year' AND semester = '$sem'");
    	if($query->num_rows() > 0) $score = $query->row()->score;
    	return $score;
    }

	public function applicationListDropdown(){
		$option = "<option value=''>Select an option</option>";
		$q_app = $this->db->query("SELECT a.`description`, b.`code_request`  FROM online_application_code a INNER JOIN code_request_form b ON a.`id` = b.`base_id` WHERE ismain = '1'");
		if($q_app->num_rows() > 0){
			foreach($q_app->result() as $row){
				$option .= "<option value='".$row->code_request."'>".$row->description."</option>";
			}
		}
		return $option;
	}

	public function getApplicationList(){
		return $this->db->query("SELECT a.`description`, b.`code_request` 
								FROM online_application_code a 
								INNER JOIN code_request_form b ON a.`id` = b.`base_id` 
								WHERE ismain = '1'")->result();
	}
	

	function getAssignedApprovers(){
		$getOffice = $this->extras->getDepartmentDescription();

		$approvers = array();
		foreach ($getOffice as $keyoffice => $valueoffice) {
			$dhead = $this->extensions->campusOfficeHead($keyoffice, "", "dhead");
			$divisionhead = $this->extensions->campusOfficeHead($keyoffice, "", "divisionhead");
			$hrhead = $this->extensions->campusOfficeHead($keyoffice, "", "hrhead");
			$phead = $this->extensions->campusOfficeHead($keyoffice,"", "phead");

			if($phead) $approvers[$phead] = $this->extensions->getEmployeeName($phead); 
			if($dhead) $approvers[$dhead] = $this->extensions->getEmployeeName($dhead); 
			if($divisionhead) $approvers[$divisionhead] = $this->extensions->getEmployeeName($divisionhead); 
			if($hrhead) $approvers[$hrhead] = $this->extensions->getEmployeeName($hrhead); 
		}
		
		return $approvers;
	}

	public function glAccountDescription($gl_account_id){
		$athena_db = Globals::athenaDatabase();
		$q_gl = $this->db->query("SELECT * FROM $athena_db.GL_ACCOUNT WHERE AID = '$gl_account_id'");
		if($q_gl->num_rows() > 0) return $q_gl->row()->DESCRIPTION;
		else return false;
	}

	public function loadFacialPerson($where = '', $lc = ''){
		return $this->db->query("SELECT personId, name FROM facial_person a INNER JOIN employee b ON a.employeeid = b.employeeid $where GROUP BY FaceId1 ORDER BY name $lc")->result_array();
	}

	public function getTypeOfOB($employeeid, $date) {
		$result = $this->db->query("SELECT `type` FROM ob_type_list WHERE id = (SELECT obtypes FROM ob_app WHERE applied_by = '$employeeid' AND datefrom = '$date' AND `status` = 'APPROVED' LIMIT 1)");
		return $result->num_rows() > 0 ? $result->row()->type : '';
	}

} //endoffile