<?php 
/**
 * @author Glen Mark Liporada
 * @copyright 2018
 *
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Loan extends CI_Model {

	function saveEmployeeLoan($employeeid,$data=array()){
	    $user = $this->session->userdata("username");

	    if ($data['loan'] <> "All") {
	    	$query = $this->db->query("SELECT * FROM employee_loan WHERE employeeid='{$employeeid}' AND code_loan='{$data['loan']}'");
	    	
	    	$base_id = $base_id_history = '';

	    	if($query->num_rows() == 0) $res = $this->insertEmpLoan($data,$employeeid); 
	    	else                        $res = $this->updateEmpLoan($data,$employeeid);

	    	if ($res) {
	    		$this->insertEmpLoanHistoy($data,$employeeid);
	    	}
	    	return $res;
	    }
	    
	}

	function insertEmpLoan($data,$employeeid)
	{	
		$data["remarks"] = isset($data["remarks"]) ? $data["remarks"] : "SYSTEM";
		$username = $this->session->userdata("username");
		$res = $this->db->query("INSERT INTO employee_loan
								 (employeeid,code_loan,datefrom,amount,startingamount,currentamount,nocutoff,loan_base,schedule, cutoff_period, modified_by, remarks) 
								 VALUES
								 ('{$employeeid}','{$data['loan']}','{$data['deductiondate']}','{$data['amount']}','{$data['startingbalance']}','{$data['currentbalance']}','{$data['nocutoff']}','{$data['baseon']}','{$data['schedule']}', '{$data['cutoff_period']}', '$username', '{$data['remarks']}')");

		$base_id = $this->db->insert_id();
		$q_insert_loan_payment = $this->db->query("INSERT INTO employee_loan_payment
												   (base_id, credit, debit, balance, payment_seq, no_payment)
												   VALUES
												   ('$base_id', '{$data['currentbalance']}', 0, {$data['currentbalance']}, 0, '{$data['nocutoff']}');
												  ");

		return $res;
	}

	function updateEmpLoan($data,$employeeid)
	{
		$username = $this->session->userdata("username");
		$res = $this->db->query("UPDATE employee_loan 
								 SET datefrom='{$data['deductiondate']}', amount='{$data['amount']}', startingamount='{$data['startingbalance']}', currentamount='{$data['currentbalance']}', nocutoff='{$data['nocutoff']}', loan_base='{$data['baseon']}', schedule='{$data['schedule']}', cutoff_period='{$data['cutoff_period']}', modified_by = '$username' 
								 WHERE employeeid='{$employeeid}' AND  code_loan='{$data['loan']}'");

		$base_id = $this->getEmployeeLoanBaseId($employeeid, $data['loan']);

		$q_update_loan_payment = $this->db->query("UPDATE employee_loan_payment
												   SET credit='{$data['currentbalance']}', balance='{$data['currentbalance']}', no_payment='{$data['nocutoff']}'
												   WHERE base_id='$base_id' AND `status`='PAYMENT' AND payment_seq='0';");

		return $res;
	}

	function updateEmpLoanForLoanHasPayment($data,$employeeid){
		$res = $this->db->query("UPDATE employee_loan 
								 SET datefrom='{$data['deductiondate']}', amount='{$data['amount']}', startingamount='{$data['startingbalance']}', currentamount='{$data['currentbalance']}', nocutoff='{$data['nocutoff']}', loan_base='{$data['baseon']}', schedule='{$data['schedule']}', cutoff_period='{$data['cutoff_period']}' 
								 WHERE employeeid='{$employeeid}' AND  code_loan='{$data['loan']}'");

		$base_id = $this->getEmployeeLoanBaseId($employeeid, $data['loan']);
		
		$q_last_payment = $this->db->query("SELECT * FROM employee_loan_payment WHERE (`status`='PAYMENT' OR `status`='EDITED') AND base_id='$base_id' ORDER BY payment_seq DESC, id DESC LIMIT 1;")->result();

		foreach ($q_last_payment as $row) {
			$credit = $balance = $data['startingbalance'];
			$debit  = 0;
			$payment_seq = $row->payment_seq;
			$no_payment = $data['nocutoff'];
			$status = "EDITED";
			
			$q_save_loan_payment = $this->db->query("INSERT INTO employee_loan_payment (base_id, credit, debit, balance, payment_seq, no_payment, status) VALUES ('$base_id', '$credit', '$debit', '$balance', '$payment_seq', '$no_payment', '$status');");
		}

		return $res;
	}

	function getEmployeeLoanBaseId($employeeid, $code_loan){
		$base_id = false;

		$q_base_id = $this->db->query("SELECT id FROM employee_loan WHERE employeeid='{$employeeid}' AND  code_loan='$code_loan';")->result();

		foreach ($q_base_id as $row) $base_id = $row->id;

		return $base_id;
	}

	function isLoanAbleToEdit($employeeid, $code_loan){
		$can_edit = true;
		$base_id = $this->getEmployeeLoanBaseId($employeeid, $code_loan);

		$q_employee_loan_payment = $this->db->query("SELECT * FROM employee_loan_payment WHERE base_id='$base_id' AND `status`='PAYMENT';")->result();

		if(count($q_employee_loan_payment) > 1) $can_edit = false;

		return $can_edit;
	}

	function getLoanPayment($base_id, $status = ''){
		$where_clause = ($status != "PAYMENT_HOLD") ? "AND a.status='$status'" : "";

		$q_employee_loan_payment = $this->db->query("SELECT b.cutoffstart, b.cutoffend, a.*
													 FROM employee_loan_payment a 
													 INNER JOIN payroll_computed_table b ON b.id = a.pct_id
													 WHERE a.base_id='$base_id' $where_clause;")->result();
		
		return $q_employee_loan_payment;
	}

	function insertEmpLoanHistoy($data,$employeeid)
	{
		$res = $this->db->query("INSERT INTO employee_loan_history(employeeid,code_loan,cutoffstart,amount,startBalance,currentBalance,remainingBalance,mode,schedule) VALUES('{$employeeid}','{$data['loan']}','{$data['deductiondate']}','{$data['amount']}','{$data['startingbalance']}','{$data['startingbalance']}','{$data['currentbalance']}','UPDATE','{$data['schedule']}')");

		if($res) return true;
        else return false;
	}

	function getLoanPaymentHistory($employeeid, $id='', $base_id='', $order_by_clause = 'DESC'){

		$q_loan_payment_history = $this->db->query("SELECT b.code_loan, c.description AS loan_desc, b.startingamount, d.cutoffstart, d.cutoffend, a.*
													FROM employee_loan_payment a
													LEFT JOIN employee_loan b ON b.id = a.base_id
													LEFT JOIN payroll_loan_config c ON c.id = b.code_loan
													LEFT JOIN payroll_computed_table d ON d.id = a.pct_id
													WHERE b.employeeid='$employeeid' AND a.no_payment != b.nocutoff AND a.pct_id != 0
													ORDER BY a.timestamp $order_by_clause;
												   ")->result();

		return $q_loan_payment_history;

	}

	function getLoanPaymentHistoryByHistoryTable($employeeid){
		$q_loan_payment_history = $this->db->query("SELECT b.code_loan, c.description AS loan_desc, b.startBalance, d.cutoffstart, d.cutoffend, a.*
													FROM employee_loan_payment a
													INNER JOIN employee_loan el ON a.base_id = el.id 
													INNER JOIN employee_loan_history b ON b.code_loan = el.code_loan
													INNER JOIN payroll_loan_config c ON c.id = b.code_loan
													INNER JOIN payroll_computed_table d ON d.id = a.pct_id
													WHERE b.employeeid='$employeeid' AND MODE = 'CUTOFF' AND b.cutoffstart != '' AND a.pct_id != 0
													ORDER BY a.timestamp DESC;
												   ")->result();

		return $q_loan_payment_history;
	}

	function getEmployeeLoan($employeeid, $id=''){
		$where_clause = ($id) ? "AND a.id='$id'": "";

		$q_emp_loan = $this->db->query("SELECT b.description AS loan_desc, SUM(c.debit) AS total_amount, COUNT(c.payment_seq) - 1 AS lastest_payment_seq, a.*  
										FROM employee_loan a
										LEFT JOIN payroll_loan_config b ON b.id = a.code_loan
										LEFT JOIN employee_loan_payment c ON c.base_id = a.id AND c.status = 'PAYMENT'
										WHERE a.employeeid='$employeeid' $where_clause
										GROUP BY a.id
										ORDER BY a.employeeid;")->result();
		return $q_emp_loan;
	}

	function deleteEmpLoan($id, $remarks){
		$data = array();
		$q_emp_loan = $this->db->query("SELECT * FROM employee_loan WHERE id='$id';")->result();
		foreach ($q_emp_loan as $row) {
			$data["base_id"] = $id;
			$data["employeeid"] = $row->employeeid;
			$data["code_loan"] = $row->code_loan;
			$data["cutoffstart"] = $row->datefrom;
			$data["startBalance"] = $row->startingamount;
			$data["currentBalance"] = $row->currentamount;
			$data["amount"] = $row->amount;
			$data["remainingBalance"] = $row->famount;
			$data["schedule"] = $row->schedule;
			$data["cutoff_period"] = $row->cutoff_period;
			$data["mode"] = "DELETE";
			$data["user"] = $this->session->userdata('username');
		}

		$this->db->insert('employee_loan_history', $data);
		$this->db->query("DELETE FROM employee_loan WHERE id='$id';");

		$this->db->query("UPDATE employee_loan_payment SET status='DELETED', remarks='$remarks' WHERE base_id='$id';");

		return "Successfully Deleted..";
	}

	function getEmployeeLoanPayment($employeeid, $code_loan, $cutoffstart, $cutoffend, $schedule, $quarter=''){

		$q_loan_payment = $this->db->query("SELECT * FROM employee_loan WHERE employeeid='$employeeid' AND code_loan='$code_loan' AND datefrom <='$cutoffend' AND `schedule`='$schedule' AND (cutoff_period='$quarter' OR cutoff_period='3') AND currentamount > 0;")->result();

		return $q_loan_payment;
	}

	function processEmployeePayment($base_id, $amount, $pct_id, $eid, $code_loan){
		$skip_loan = $this->loan->checkIfSkipInLoanPayment($eid, $code_loan);
		$q_loan_seq = $this->db->query("SELECT * FROM employee_loan_payment WHERE base_id='$base_id' AND (status='PAYMENT' OR status='EDITED') ORDER BY id DESC LIMIT 1")->result();

		
		foreach ($q_loan_seq as $row) {
			$credit = $row->balance;
			$debit = $amount;
			$balance = $credit - $amount;
			$payment_seq =  ($skip_loan) ? $row->payment_seq : $row->payment_seq + 1;
			$no_payment =  ($skip_loan) ? $row->no_payment : $row->no_payment - 1;
			$status = ($balance <= 0) ? "PAID" : "PAYMENT";
			if($skip_loan) $status = "HOLD";

			$loan_payment_data = array(
				"pct_id"		=> $pct_id,
				"base_id" 		=> $base_id,
				"credit" 		=> $credit,
				"debit" 		=> $debit,
				"balance" 		=> $balance,
				"payment_seq" 	=> $payment_seq,
				"no_payment" 	=> $no_payment,
				"status" 		=> $status
			);
			
			$q_save_payment = $this->db->insert('employee_loan_payment', $loan_payment_data);
			
			$q_update_remain_bal = $this->db->query("UPDATE employee_loan SET currentamount='$balance' WHERE id='$base_id;'");
		}
	}

	function getLastLoanPaymentEditHistoryCutoff($base_id){
		$cutoff = 0;
		
		$q_last_payment = $this->db->query("SELECT * FROM employee_loan_payment WHERE (`status`='PAYMENT' OR `status`='EDITED') AND base_id='$base_id';")->result();
		foreach ($q_last_payment as $row){
			if($row->status == "EDITED") $cutoff = 0;
			else 						 $cutoff += ($row->payment_seq == 0) ? 0 : 1;
		}

		return $cutoff;
	}

	function getLoanPaymentRemarks($id){
		$remarks = "";

		$q_loan_payment = $this->db->query("SELECT * FROM employee_loan_payment WHERE id='$id';")->result();
		foreach ($q_loan_payment as $row) $remarks = $row->remarks;

		return $remarks;
	}

	function deleteLoanBatch($employeeid,$info){
        $query = "";
        $query = "DELETE FROM employee_loan WHERE employeeid='{$employeeid}' AND code_loan='{$info['loan']}';";
        $q_saveLoan = $this->db->query($query);
        return $q_saveLoan;
    }

    function checkIfSkipInLoanPayment($employeeid, $code_loan){
    	$q_loan = $this->db->query("SELECT * FROM skip_loan_history WHERE employeeid = '$employeeid' AND code_loan = '$code_loan' AND status = 'YES' ");
    	if($q_loan->num_rows() > 0) return true;
    	else return false;
    }

    function checkIfLoanIsExisting($employeeid, $code_loan){
    	$q_loan = $this->db->query("SELECT * FROM skip_loan_history WHERE employeeid = '$employeeid' AND code_loan = '$code_loan' ");
    	if($q_loan->num_rows() > 0) return true;
    	else return false;
    }

    function skipEmployeeLoanPayment($employeeid, $code_loan, $status, $is_exisiting){
    	$q_loan = 0;
    	$user = $this->session->userdata('username');
    	if($is_exisiting) $q_loan = $this->db->query("UPDATE skip_loan_history SET status = '$status', timestamp = CURRENT_TIMESTAMP WHERE employeeid = '$employeeid' AND code_loan = '$code_loan' ");
    	else $q_loan = $this->db->query("INSERT INTO skip_loan_history (employeeid, code_loan, status, editedby) VALUES ('$employeeid', '$code_loan', '$status', '$user') ");

    	return $q_loan;
    }

    public function checkIfHasExistingLoan($empid, $code){
    	return $this->db->query("SELECT * FROM employee_loan WHERE employeeid = '$empid' AND code_loan = '$code' ")->num_rows();
    }

	function updateData($empData,$codeDeduction){
		$this->db->where('code_loan', $codeDeduction);
		$this->db->where('employeeid', $empData['employeeid']);
      	$this->db->update('employee_loan', $empData);
      	$base_id = $this->db->query("SELECT * FROM employee_loan WHERE employeeid = '{$empData['employeeid']}' AND code_loan = '{$empData['code_loan']}' ")->row()->id;
      	return $this->db->query("UPDATE employee_loan_payment
												   SET credit='{$empData['currentamount']}', balance='{$empData['currentamount']}', no_payment='{$empData['nocutoff']}'
												   WHERE base_id='$base_id' AND `status`='PAYMENT' AND payment_seq='0';");
    }

    function insertData($empData){
  	    $this->db->insert('employee_loan', $empData);
  	    $base_id = $this->db->insert_id();
		$this->db->query("INSERT INTO employee_loan_payment
												   (base_id, credit, debit, balance, payment_seq, no_payment)
												   VALUES
												   ('$base_id', '{$empData['currentamount']}', 0, {$empData['currentamount']}, 0, '{$empData['nocutoff']}');
												  ");
		return $this->db->query("INSERT INTO employee_loan_history(employeeid,code_loan,amount,startBalance,currentBalance,remainingBalance,mode,schedule) VALUES('{$empData['employeeid']}','{$empData['code_loan']}','{$empData['amount']}','{$empData['startingamount']}','{$empData['startingamount']}','{$empData['currentamount']}','UPDATE','{$empData['schedule']}')");
    }

}