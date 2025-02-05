<?php

// Load Composer's autoloader for using external libraries
require APPPATH . 'libraries/vendor/autoload.php'; 
require_once APPPATH . 'libraries/mpdf/vendor/autoload.php';

use Clegginabox\PDFMerger\PDFMerger;

set_time_limit(0);
ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);
ini_set("pcre.backtrack_limit", "500000000");

defined('BASEPATH') OR exit('No direct script access allowed');

class Que extends CI_Controller {

    // Main entry point for the controller
    public function index(){
        // $this->db->query("UPDATE report_list SET status = 'qweqwe'");
        // sleep(1); // Delaying execution for 5 seconds (could be for load balancing or delay purpose)
        
        // Enable query caching for this section
        $this->db->cache_on();

        // Check if there are pending, ongoing or rendering reports
        $has_pending = $this->db->query("SELECT id FROM report_list WHERE status = 'pending' ")->num_rows();
        $print_ongoing = $this->db->query("SELECT id FROM report_list WHERE status = 'ongoing' ")->num_rows();
        $print_rendering = $this->db->query("SELECT id FROM report_list WHERE status = 'rendering' ")->num_rows();

        $has_pending_recompute = $this->db->query("SELECT id FROM recompute_list WHERE status = 'pending' ")->num_rows();
        $has_ongoing_recompute = $this->db->query("SELECT id FROM recompute_list WHERE status = 'ongoing' ")->num_rows();
        // Disable query caching after the queries
        $this->db->cache_off();
        
        // If there are no ongoing or rendering reports, initiate the DTR processing and generation
        if ($print_ongoing == 0 && $print_rendering == 0) {
            $this->init_process_dtr();  // Initialize and process DTR tasks
        }

        if($has_pending == 0 && $print_ongoing == 0 && $print_rendering == 0){
            // Run the calculate
            $this->init_calculate();
        }

        if($has_pending_recompute > 0 && $has_ongoing_recompute == 0){
            // Run the calculate
            $this->init_recompute();
        }
        
    }

    // Task to reprocess attendance logs based on a schedule
    public function init_calculate(){
        // Enable query caching for this section
        $this->db->cache_on();
        $emp_list = $this->worker_model->fetch_emp_calculate();  // Fetch list of employees with attendance tasks
        // Disable query caching after the queries
        $this->db->cache_off();
        
        // Loop through each employee and reprocess their attendance if applicable
        if ($emp_list->num_rows() > 0) {
            foreach ($emp_list->result_array() as $row) {
                try{
                    $this->worker_model->update_calculate_status($row, "ongoing");

                    $employeeid = $row["employeeid"];
                    $dfrom = $row["dfrom"];
                    $dto = $row["dto"];
                    $this->calculate_attendance($employeeid, $dfrom, $dto); // Calculate attendance for each employee
                }catch (Exception $e) {
                    // SOME ERROR HANDLER HERE
                }
            }
        }
    }

    // Initialize processing of Daily Time Record (DTR) reports
    public function init_process_dtr(){
        $reports = $this->worker_model->get_report_task(); // Get pending DTR report tasks
        
        if ($reports->num_rows() > 0) {
            foreach($reports->result() as $report){
                $this->process_dtr($report); // Process the DTR report for the first task found
            }
        }
    }
	
	public function init_clean_up(){
		$reports = $this->worker_model->stuck_report_list();
		if ($reports->num_rows() > 0) {
            foreach($reports->result() as $report){
                $this->process_clean_up($report); // Process the DTR report for the first task found
            }
        }
	}

    // Calculate attendance for a specific employee and date
    public function calculate_attendance($employeeid, $dfrom, $dto){
        // Prepare data for the API request to calculate attendance
        $curl_uri = getenv('CONFIG_BASE_URL')."/index.php/";
        $form_data = array(
            "client_secret" => "Y2M1N2E4OGUzZmJhOWUyYmIwY2RjM2UzYmI4ZGFiZjk=",
            "username" => "hyperion",
            "password" => "@uaHyperion2024",
            "employeeid" => $employeeid,
            "dfrom" => $dfrom,
            "dto" => $dto
        );
        
        // Set up cURL request to external API
        ini_set('display_errors', 1);
        error_reporting(-1);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $curl_uri . "Api_/calculate_attendance");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1 );
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($form_data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept" => "application/json"));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // Log the calculated attendance result to the database
        $calc_history = array(
            "status" => $response,
            "employeeid" => $employeeid,
            "dfrom" => $dfrom,
            "dto" => $dto
        );

        $filter = array(
            "employeeid" => $employeeid,
            "dfrom" => $dfrom,
            "dto" => $dto
        );
        $this->db->where($filter)
         ->set('status', "done")
         ->update('employee_to_calculate');

        // Insert attendance calculation history to the database
        $this->db->insert("att_calc_history", $calc_history);
    }

    // Process the DTR report for a given report task
    public function process_dtr($det){
        // Check if there are any ongoing or rendering reports
        $has_pending_reports = $this->db->query("SELECT id FROM report_list WHERE status IN ('rendering', 'ongoing')")->num_rows();
        if ($has_pending_reports > 0) {
            return; // Exit if there are pending reports
        }

        // Mark the report as ongoing
        $this->worker_model->updateReportStatus($det->id, "", "ongoing");

        // Prepare date range
        $data["actual_dates"] = [$det->dfrom, $det->dto];
        $dates = $this->time->generateMonthDates($det->dfrom);
        // $det->dfrom = $dates["first_date"];
        // $det->dto = $dates["last_date"];

        // Fetch employees for the report
        $employeelist = $this->worker_model->getEmployeeList($det->where_clause);
        if (empty($employeelist) || count($employeelist) == 0) {
            $this->worker_model->updateReportStatus($det->id, "", "No employee to generate");
            return;
        }

        // Prepare header description
        $header_desc = "From " . date("F d, Y", strtotime($det->dfrom)) . " to " . date("F d, Y", strtotime($det->dto));
        $data['month_of'] = (date("F Y", strtotime($det->dfrom)) === date("F Y", strtotime($det->dto))) 
            ? date("F Y", strtotime($det->dfrom)) 
            : date("F Y", strtotime($det->dfrom)) . ' - ' . date("F Y", strtotime($det->dto));

        $is_cancelled = false;

        foreach ($employeelist as $employee) {
			try {
				// Check if the report was cancelled
				if ($this->worker_model->report_cancelled($det->id) > 0) {
					$is_cancelled = true;
					break;
				}

				// Prepare data for the report
				$isteaching = $this->worker_model->getempteachingtype($employee->employeeid);
				$data['report_id'] =  $det->id;
				$data['campus'] = $employee->campusid;
				$data['employeeid'] = $employee->employeeid;
				$data['attendance'] = $this->worker_model->getEmployeeDTR($employee->employeeid, $det->dfrom, $det->dto, $isteaching);
                $data['dtrcutoff'] = date('F d, Y', strtotime($det->dfrom)) . ' - ' . date('F d, Y', strtotime($det->dto));
                // echo "<pre>";print_r($data['attendance']);die;
                // echo "<pre>";print_r($data);die;

				// Load the appropriate report view
				$report = $this->load->view(
					$isteaching ? 'dtr/teachingDailyTimeReport' : 'dtr/nonteachingDailyTimeReport',
					$data,
					TRUE
				);

				// Prepare report breakdown data
				$report_data = [
					"base_id" => $det->id,
					"employeeid" => $employee->employeeid,
					"campus" => $det->campus,
					"header_desc" => $header_desc
				];
				$data["report_list"] = [["report" => $report]];
				$data["path"] = "files/reports/pdf/{$employee->employeeid}_{$det->id}.pdf";

                // ADDITIONAL TRY CATCH FOR ERROR HANDLING
                try {
                    // Save the report breakdown and generate the PDF
                    $this->worker_model->save_report_breakdown($report_data);
                    $this->load->view('dtr/daily_time_report_pdf', $data);
                }catch (Exception $e) {
                    $this->worker_model->updateReportStatus($det->id, "", "error encountered");
                    continue;
                }

			}catch (Exception $e) {
                $this->worker_model->updateReportStatus($det->id, "", "error encountered");
				continue;
			}
        }

        // Update the report status based on cancellation
        $this->worker_model->updateReportStatus($det->id, "", $is_cancelled ? "cancelled" : "done");
    }

	public function process_clean_up($det){
		$this->worker_model->reset_report_process($det->id);
	}

    public function init_recompute(){
        $recomputelist = $this->recompute->get_recompute_task(); // Get pending DTR report tasks
        
        if ($recomputelist->num_rows() > 0) {
            foreach($recomputelist->result() as $recompute){
                $this->recompute_process($recompute); // Process the DTR report for the first task found
            }
        }
    }

    public function recompute_process($recompute){

        $this->load->model('recompute');
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

        $this->load->model('payrollprocess');

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

			}else{
				echo 'No employees to recompute.';
				return;
			}
			$data['recompute_msg'] = 'Recompute Successful.';

			$data["subtotal"] = $this->hr_reports->payrollSubTotal($data["emplist"]);
			$data["total"] = $this->hr_reports->payrollGrandTotal($data["emplist"]);

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
