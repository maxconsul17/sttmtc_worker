<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class RecomputeManager
{   
    private $CI;
    private $worker_model;
    private $time;
    private $recompute;
    private $payrollprocess;
    private $extras;
    private $hr_reports;
    private $payroll;

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

        $this->worker_model = $this->CI->worker_model;
        $this->time = $this->CI->time;
        $this->recompute = $this->CI->recompute;
        $this->payrollprocess = $this->CI->payrollprocess;
        $this->extras = $this->CI->extras;
        $this->hr_reports = $this->CI->hr_reports;
        $this->payroll = $this->CI->payroll;
    }

    // public function processReport(){
    //     $this->init_process_dtr();
    // }

    // Initialize processing of Daily Time Record (DTR) reports
    public function processRecompute($recomputeJob, $worker_id){

        $this->recompute_process($recomputeJob, $worker_id);
    }

    public function getRecomputeJob()
    {
        return $this->worker_model->getRecomputeJob();
    }

    public function recompute_process($recompute, $worker_id){

        $this->recompute->updateRecomputeStatus($recompute->id, "ongoing");
        // echo '<pre>';print_r($recompute);die;
        // echo $recompute->formdata;

        // Convert the string back to an associative array
        $data = [];
        $pairs = explode(', ', $recompute->formdata); 

        foreach ($pairs as $pair) {
            list($key, $value) = explode(' => ', $pair); 
            $data[trim($key)] = trim($value);
        }

        $deptid     = isset($data['deptid']) ? $data['deptid'] : '';
		$office     = isset($data['office']) ? $data['office'] : '';
		$teachingtype     = isset($data['teachingType']) ? $data['teachingType'] : '';
		$employeeid = isset($data['employeeid']) ? $data['employeeid'] : '';
		$empstatus = isset($data['empstatus']) ? $data['empstatus'] : '';
		$schedule   = isset($data['schedule']) ? $data['schedule'] : '';
		$cutoff     = isset($data['payrollcutoff']) ? $data['payrollcutoff'] : '';
		$quarter    = isset($data['quarter']) ? $data['quarter'] : '';
		$campus 	= isset($data['campusid']) ? $data['campusid'] : '';
		// $company_campus = isset($data['company_campus']) ? $data['company_campus'] : $this->input->post('company_campus');
		$sortby     = isset($data['sorting']) ? $data['sorting'] : '';
		$compute_type = isset($data['compute_type']) ? $data['compute_type'] : '';

		$success_count = 0;
		$arr_data_failed = array();

		$dates = explode(' ',$cutoff);
		if(isset($dates[0]) && isset($dates[1])){
			$sdate = $dates[0];
			$edate = $dates[1];
			$payroll_cutoff_id = $this->recompute->getPayrollCutoffBaseId($sdate,$edate);
		}

		if($compute_type === "main"){
			$emplist = $this->recompute->loadAllEmpbyDept($deptid,$employeeid,$schedule, $campus,"", $sdate, $edate, $sortby, $office, $teachingtype,$empstatus);
			$emplist2 = $this->recompute->loadAllEmpbyDeptSample($deptid,$employeeid,$schedule, "", "",  $sdate, $edate, $sortby, $office, $teachingtype,$empstatus);

			if(sizeof($emplist) > 0){

				$data = $this->payrollprocess->processPayrollSummary($emplist,$emplist2,$sdate,$edate,$schedule,$quarter,true,$payroll_cutoff_id);
				// echo "<pre>"; print_r($data); die;
				$departments = $this->extras->showdepartment();
				$data['dept'] 	= isset($departments[$deptid]) ? $departments[$deptid] : "";
				$data['deptid'] = $deptid;
				$data['employeeid'] = $employeeid;
				$data['schedule'] = $schedule;
				$data['cutoff'] = $cutoff;
				$data['campus'] = $campus;
				$data['quarter'] = $quarter;
				$data['status'] = 'PENDING';
				$data['issaved'] = '';
				$data['sortby'] = $sortby;

			}

			// echo "<pre>"; print_r($emplist); die;
			// $this->load->view('payroll/payrolllist',$data);
		}else{
			$emplist = $this->payroll->employeeListForSubSite($deptid,$employeeid,$schedule, $campus,"", $sdate, $edate, $sortby, $office, $teachingtype,$empstatus);
			if(sizeof($emplist) > 0){
				$data = $this->payrollprocess->processPayrollSub($emplist, $sdate, $edate, $schedule, $quarter, true, $payroll_cutoff_id);
				$departments = $this->extras->showdepartment();
				$data['dept'] 	= isset($departments[$deptid]) ? $departments[$deptid] : "";
				$data['deptid'] = $deptid;
				$data['employeeid'] = $employeeid;
				$data['schedule'] = $schedule;
				$data['cutoff'] = $cutoff;
				$data['campus'] = $campus;
				$data['quarter'] = $quarter;
				$data['sortby'] = $sortby;
				$data['recompute_msg'] = 'Recompute Successful.';
				// $this->load->view('payroll/payrolllist_income',$data);
			}
            // else{
			// 	echo 'No employees to recompute.';
			// 	return;
			// }
		}
        // echo 'done';
        $this->recompute->updateRecomputeStatus($recompute->id, "done");
    }
}