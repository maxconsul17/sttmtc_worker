<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PayrollManager
{   
    private $CI, $worker_model, $time, $recompute, $payrollprocess, $extras, $hr_reports, $payroll, $utils, $extensions, $payrollreport;

    function __construct() 
    {
        $this->CI = & get_instance();
        $this->CI->load->model("Worker_model", "worker_model");
        $this->CI->load->model("Time", "time");
        $this->CI->load->model("Recompute", "recompute");
        $this->CI->load->model("Payrollprocess", "payrollprocess");
        $this->CI->load->model("Extras", "extras");
        $this->CI->load->model("Hr_reports", "hr_reports");
        $this->CI->load->model("Payroll", "payroll");
        $this->CI->load->model("Utils", "utils");
        $this->CI->load->model("Extensions", "extensions");
        $this->CI->load->model("Payrollreport", "payrollreport");

        $this->worker_model = $this->CI->worker_model;
        $this->time = $this->CI->time;
        $this->recompute = $this->CI->recompute;
        $this->payrollprocess = $this->CI->payrollprocess;
        $this->extras = $this->CI->extras;
        $this->hr_reports = $this->CI->hr_reports;
        $this->payroll = $this->CI->payroll;
        $this->utils = $this->CI->utils;
        $this->extensions = $this->CI->extensions;
        $this->payrollreport = $this->CI->payrollreport;
    }

    // public function processReport(){
    //     $this->init_process_dtr();
    // }

    // Initialize processing of Daily Time Record (DTR) reports
    public function processPayroll($payrollJob, $worker_id){

        $this->payroll_process($payrollJob, $worker_id);
    }

    public function getPayrollJob()
    {
        return $this->worker_model->getPayrollJob();
    }

    public function payroll_process($job_det, $worker_id){

        $this->worker_model->updatePayrollStatus($job_det->id, "ongoing");

        if ($job_det->code == 'payrollreg' && $job_det->worker_id == $worker_id) $this->payrollRegistrarReport($job_det, $worker_id);
        if ($job_det->code == 'payslip' && $job_det->worker_id == $worker_id) $this->paySlip($job_det, $worker_id);
        if ($job_det->code == 'atmlist' && $job_det->worker_id == $worker_id) $this->atmPayrollList($job_det, $worker_id);


        
    }

    public function atmPayrollList($job_det, $worker_id){
        $data = array();
        $formdata = json_decode($job_det->formdata,true);
        // print_r($formdata);die;

        $deptid     		=  ($formdata['deptid']) ?? '';
		$employeeid 		=  ($formdata['employeeid']) ?? ''; 
		$schedule   		=  ($formdata['schedule']) ??'';
		$cutoff     		=  ($formdata['payrollcutoff']) ?? '';
		$quarter    		=  ($formdata['quarter']) ?? '';
		$campus    			=  ($formdata['campus']) ?? '';
		$company_campus    	=  ($formdata['company_campus']) ?? '';
		$sortby 			=  ($formdata['sortby']) ?? '';
		$office 			=  ($formdata['office']) ?? '';
		$teachingtype 		=  ($formdata['tnt']) ?? '';
        $employmentstat     =  isset($formdata['employmentstat']) ? $formdata['employmentstat'] : '';

		$reportname 		=  ($formdata['reportname']) ?? '';
		$reportformat 		=  ($formdata['reportformat']) ?? '';

		$dateprocessed 		=  ($formdata['dateprocessed']) ?? '';
		

		$dates = explode(' ',$cutoff);
		if(isset($dates[0]) && isset($dates[1])){
			$sdate = $dates[0];
			$edate = $dates[1];
		}else{
            $this->worker_model->updatePayrollStatus($job_det->id, "Invalid Cufoff");
			return;
		}
        
        $emp_bank = $formdata['emp_bank'] ?? '';
        $status =  $formdata['emp_status'] ?? '';

        if(!$status) $status = $formdata['payroll_status'] ?? '';

        $data = $this->payrollprocess->getAtmPayrolllist($emp_bank, $sdate, $status, $sortby,$campus, $company_campus,$deptid,$office,$teachingtype,$employeeid, $employmentstat);
        $data['sdate'] = $sdate;
        $data['edate'] = $edate;
        $data['sortby'] = $sortby;
        $data["emp_bank"] = $emp_bank;
        $data["dateprocessed"] = $dateprocessed;
        $data['campus_desc'] = (isset($campus) ? ($campus == "All" || $campus == '' ? "All Campus" : $this->extensions->getCampusDescription($campus)) : '');
        $data['company_desc'] = (isset($company_campus) ? $this->extensions->getCompanyDescriptionReports($company_campus) : '');
        $data['emp_type']  = ($teachingtype) ? ucfirst($teachingtype) ." Employees " : "";      
        
        $data['campusid'] = $formdata['campusid'] ?? '';
        $data["mtitle"] = "ATM PAYROLL LIST";                                                     				   // ****
        // $data["departmentid"] = $this->utils->getDepartmentDesc($this->input->post("department") );                // **** LOLA 11-22-2022
        $data['cutoff_start'] = $sdate;
        $data['cutoff_end'] = $edate;
        // $data['company_campus'] =$this->input->post('company_campus');
        $data['first_person_name'] = ($formdata['first_person_name']) ?? '';
        $data['first_person_position'] = ($formdata['first_person_position']) ?? '';
        $data['second_person_name'] = ($formdata['second_person_name']) ?? '';
        $data['second_person_position'] = ($formdata['second_person_position']) ?? '';
        $data["path"] = "files/payroll/{$job_det->id}.pdf";

        // echo "<pre>";print_r($data);die;
        $this->CI->load->view('forms_pdf/atm_payroll_list',$data);
        $this->worker_model->updatePayrollStatus($job_det->id, "done");
    }

    public function paySlip($job_det, $worker_id){

        try{
        $data = json_decode($job_det->formdata,true);
        // print_r($data);die;

		$data["campus"] = $data["campusid"];
        $employmentstat = isset($data['employmentstat']) ? $data['employmentstat'] : null;

		$data["sort"] = 0;
		$data['payroll_config'] = $this->extensions->getAllIncomeKeysAndDescription();

		if ($data['eid']) {
			if($data['eid'][0] == ""){
				$data["eid"] = "";
			}else{
				if(!is_array($data["eid"])) $data["eid"] = array(0=>$data["eid"]);
				else $data["eid"] = "'" . implode( "','", $data["eid"]) . "'";
			}
		}
		
		$emplist = $this->payroll->loadAllEmpbyDeptForPayslip($data["dept"],$data["eid"],$data["schedule"],$data["sort"],$data["dfrom"],true,'', $data["campus"], '', $data['bank'], $employmentstat);

		$emp_data = $this->payrollreport->getPayslipSummary($emplist,$data["dfrom"],$data["dto"],$data["schedule"],$data["quarter"],$data["bank"]);

		if(isset($emp_data['emplist'])){
			foreach($emp_data['emplist'] as $empid => &$employee){
				$holiday_pay = $this->payroll->getEmpHolidayPay($empid, $data["dfrom"], $data["dto"]);
				$suspension_pay = $this->payroll->getEmpSuspensionPay($empid, $data["dfrom"], $data["dto"]);
				
				$employee['holiday_pay'] = $holiday_pay;
				$employee['suspension_pay'] = $suspension_pay;
			}
			
			unset($emp_data['emplist']['holiday_pay'], $emp_data['emplist']['suspension_pay']);
		}
		
		$emp_data["emp_bank"] = $this->extensions->getBankName($data["bank"]);
		$emp_data["dfrom"] = $data["dfrom"];
		$emp_data["dto"] = $data["dto"];
		$emp_data["company"] = $data["campusid"];

        // print_r($emp_data);die;

        $emp_data["path"] = "files/payroll/{$job_det->id}.pdf";

        $this->CI->db->query("INSERT INTO payroll_list_trail (id, details) VALUES ('$job_det->id', 'success')");
        
        $this->CI->load->view('forms_pdf/payslip_detailed', $emp_data);
        }catch (Exception $e) {
            $this->CI->db->query("INSERT INTO payroll_list_trail (id, details) VALUES ('$job_det->id', '".$e->getMessage()."')");
        }
        $this->worker_model->updatePayrollStatus($job_det->id, "done");
    }

    public function payrollRegistrarReport($job_det, $worker_id){
        // echo '<pre>';print_r($job_det);die;
        $post_data = json_decode($job_det->formdata,true);
        // echo '<pre>';print_r($post_data);die;
		$deptid = $post_data["department"];
		$employeeid = isset($post_data["employeeid"]) ? $post_data["employeeid"] : "";
		$schedule = $post_data["schedule"];
		$campus = $post_data["campusid"];
		$sortby = isset($post_data["sortby"]) ? $post_data["sortby"] : "";
		$company = $post_data["company_campus"];
		$teachingtype = $post_data["tnt"];
		$office = $post_data["office"];
		$employmentstat = isset($post_data["employmentstat"]) ? $post_data["employmentstat"] : "";
		$bank = $post_data["emp_bank"];
		$quarter = $post_data["quarter"];

        $payrollcutoff = str_replace("+", " ", $post_data['payrollcutoff']);
		$dates = explode(' ', $payrollcutoff);
		if (isset($dates[0]) && isset($dates[1])) {
			$sdate = $dates[0];
			$edate = $dates[1];
		}

		$emplist = $this->payroll->loadAllEmpbyDeptForProcessed($deptid, $employeeid, $schedule, $campus, $sortby, $company, $office, $teachingtype, false, $employmentstat);
	
		if (sizeof($emplist) > 0) {
			$data = $this->payrollprocess->getProcessedPayrollSummary($emplist, $sdate, $edate, $schedule, $quarter, 'PROCESSED', $bank);
            
            $holiday_total = 0; 
            $suspension_total = 0; 
            foreach ($data['emplist'] as $empid => &$employee) {
                $holiday_pay = $this->payroll->getEmpHolidayPay($empid, $sdate, $edate);
                $suspension_pay = $this->payroll->getEmpSuspensionPay($empid, $sdate, $edate);
                $employee['holiday_pay'] = $holiday_pay; 
                $employee['suspension_pay'] = $suspension_pay; 
                $holiday_total += isset($holiday_pay) ? $holiday_pay : 0;
                $suspension_total += isset($suspension_pay) ? $suspension_pay : 0;
            }

			$departments = $this->extras->showdepartment();
			$data['dept'] = $departments[$deptid];
			$data['deptid'] = $deptid;
			$data['employeeid'] = $employeeid;
			$data['schedule'] = $schedule;
			$data['payrollcutoff'] = $payrollcutoff;
			$data['quarter'] = $quarter;
			$data['campusid'] = $campus;
			$data['status'] = 'PROCESSED';
			$data['sortby'] = $sortby;
			$data["subtotal"] = $this->hr_reports->payrollSubTotal($data["emplist"]);
			$data["total"] = $this->hr_reports->payrollGrandTotal($data["emplist"]);
            $data["holiday_total"] = $holiday_total;
            $data["suspension_total"] = $suspension_total;
	
			$data['hasEditPayrollComputedEditAccess'] = $this->utils->hasEditPayrollComputedEditAccess();
			$data["office"] = $office;
			$data["teachingtype"] = $teachingtype;
			$data['bank'] = $bank;
			// echo "<pre>";print_r($data);die;
			if ($post_data['reportformat'] == "PDF") {
                $data["path"] = "files/payroll/{$job_det->id}.pdf";
                $this->CI->load->view('forms_pdf/processed_payroll_register', $data);
			} else {
                $data["path"] = "files/payroll/{$job_det->id}.xls";
                $this->CI->load->view('reports_excel/processed_payroll_register', $data);
			}
            $this->worker_model->updatePayrollStatus($job_det->id, "done");
		} else {
            $this->worker_model->updatePayrollStatus($job_det->id, "No employees to display.");
		}
    }
}