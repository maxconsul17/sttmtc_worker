<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AttendanceManager
{   
    private $CI, $worker_model, $time, $recompute, $payrollprocess, $extras, $hr_reports, $payroll, $utils, $extensions, $payrollreport, $payrolloptions, $employee, $employeeAttendance, $attcompute;


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
        $this->CI->load->model("Payrolloptions", "payrolloptions");
        $this->CI->load->model("Employee", "employee");
        $this->CI->load->model("EmployeeAttendance", "employeeAttendance");
        $this->CI->load->model("Attcompute", "attcompute");
        $this->CI->load->database();

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
        $this->payrolloptions = $this->CI->payrolloptions;
        $this->employee = $this->CI->employee;
        $this->employeeAttendance = $this->CI->employeeAttendance;
        $this->attcompute = $this->CI->attcompute;
    }

    public function processAttendance($attendanceJob, $worker_id){
        $this->attendance_process($attendanceJob, $worker_id);
    }

    public function getCalculateJob()
    {
        return $this->worker_model->getCalculateJob();
    }

    public function getAttendanceJob()
    {
        return $this->worker_model->getAttendanceJob();
    }

    public function attendance_process($job_det, $worker_id){

        if ($job_det->code == 'attconf' && $job_det->worker_id == $worker_id) $this->processConfirmAttendance($job_det, $worker_id);

    }

    public function processConfirmAttendance($job_det, $worker_id){
        $this->worker_model->updateAttendanceStatus($job_det->id, "ongoing");
        
        $data = json_decode($job_det->formdata,true);

        $employeeids = explode(',',$data["empid"]);

        foreach ($employeeids as $employeeid) {
            $this->confirmAttendance($data);
        }
        $this->worker_model->updateAttendanceStatus($job_det->id, "done");
    }

    public function processCalculation(){
        $emp_list = $this->worker_model->fetch_emp_calculate();  // Fetch list of employees with attendance tasks
        
        // Loop through each employee and reprocess their attendance if applicable
        if ($emp_list && $emp_list->num_rows() > 0) {
            foreach ($emp_list->result_array() as $row) {
                try{
                    $this->worker_model->update_calculate_status($row, "ongoing");
                    $this->worker_model->update_calculate_status($row, "test");
                    $employeeid = $row["employeeid"];
                    $dfrom = $row["dfrom"];
                    $dto = $row["dto"];
                    $this->calculate_attendance($employeeid, $dfrom, $dto, $row); // Calculate attendance for each employee
                }catch (Exception $e) {
                    // SOME ERROR HANDLER HERE
                }
            }
        }
    }

    // Calculate attendance for a specific employee and date
    public function calculate_attendance($employeeid, $dfrom, $dto, $row){
        // Prepare data for the API request to calculate attendance
        $curl_uri = $this->CI->db->base_url_config."/index.php/";
        $this->worker_model->update_calculate_status($row, "sended");
        $form_data = array(
            "client_secret" => "Y2M1N2E4OGUzZmJhOWUyYmIwY2RjM2UzYmI4ZGFiZjk=",
            "username" => "hyperion",
            "password" => "@stmtccHyperion2024",
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

        // Insert attendance calculation history to the database
        $this->CI->db->insert("att_calc_history", $calc_history);
    }

    public function confirmAttendance($data){
		$usertype   = 'ADMIN';
		// $toks 		= $this->input->post("toks");
        // $data 		= $this->input->post();
        // foreach($data as $key => $val){
        // 	$data[$key] = $this->gibberish->decrypt($val, $toks);
        // }
        $data['teaching_type'] = $data['tnt'];
        $data['emp_list'] = $data['empid'];
        // print_r($data);die;
        extract($data);
        list($dfrom, $dto) = explode(",", $cutoff);
        list($dtr_start,$dtr_end,$payroll_start,$payroll_end,$payroll_quarter) = $this->payrolloptions->getDtrPayrollCutoffPair($dfrom,$dto);
        $isnodtr = $this->extensions->checkIfCutoffNoDTR($dfrom,$dto);
        $workhours_arr = array();
        foreach (explode(",", $emp_list) as $employeeid) {
        	$teaching_related = $this->employee->isTeachingRelated($employeeid);
        	if($teaching_type == "teaching" || $teaching_related){
        		$attendance = $this->employeeAttendance->getAttendanceTeaching($employeeid, $dfrom, $dto);
        		$off_lec_total = $off_lab_total = $off_admin_total = $off_overload_total = $twr_lec_total = $twr_lab_total = $twr_admin_total = $twr_overload_total = $teaching_overload_total = $ot_regular_total = $ot_restday_total = $ot_holiday_total = $lateut_lec_total = $lateut_lab_total = $lateut_admin_total = $lateut_overload_total = $absent_lec_total = $absent_lab_total = $absent_admin_total = $service_credit_total = $cto_total = $holiday_lec_total = $holiday_lab_total = $holiday_admin_total = $holiday_overload_total = $holiday_total = $date_list_absent = $total_absent = $vacation_total = $emergency_total = $other_total = $sick_total =  0;
        		$daily_present = $daily_absent = "";
    			foreach ($attendance as $att_date) {
		            $counter = 0;
		            $rowspan = 0;
		            $is_absent = 0;
		            $date = $daily_lec = $daily_lab = $daily_admin = $daily_overload = $daily_overtime_mode =  "";
		            $daily_lec_absent = $daily_lab_absent = $daily_admin_absent = $daily_overload_absent = $daily_lec_late = $daily_lab_late = $daily_admin_late = $daily_overload_late = $daily_lec_undertime = $daily_lab_undertime = $daily_admin_undertime = $daily_overload_undertime = $daily_overtime = $daily_undertime = $daily_late = $daily_absents = $daily_overtime_amount =  0;
		            $ot_list = array();
					
		            foreach ($att_date as $key => $value) {
		            	$rowspan = $value->rowspan;
		            	$leave_project = 0;
		            	if($value->classification == "overload"){
		            		if($value->off_lec) $teaching_overload_total += $this->attcompute->exp_time($value->off_lec);
							else if($value->off_lab) $teaching_overload_total += $this->attcompute->exp_time($value->off_lab);
							else if($value->off_admin || $value->off_overload) $teaching_overload_total += $this->attcompute->exp_time($value->off_admin);
							else if($value->off_overload) $teaching_overload_total += $this->attcompute->exp_time($value->off_overload);
		            	}else{
		            		$off_lec_total += $this->attcompute->exp_time($value->off_lec);
			                $off_lab_total += $this->attcompute->exp_time($value->off_lab);
			                $off_admin_total += $this->attcompute->exp_time($value->off_admin);
			                $off_overload_total += $this->attcompute->exp_time($value->off_overload);

			                $lateut_lec_total += $this->attcompute->exp_time($value->lateut_lec);
			                $lateut_lab_total += $this->attcompute->exp_time($value->lateut_lab);
			                $lateut_admin_total += $this->attcompute->exp_time($value->lateut_admin);
			                $lateut_overload_total += $this->attcompute->exp_time($value->lateut_overload);

			                $absent_lec_total += $this->attcompute->exp_time($value->absent_lec);
			                $absent_lab_total += $this->attcompute->exp_time($value->absent_lab);
			                $absent_admin_total += $this->attcompute->exp_time($value->absent_admin);
		            	}
			                

		                $twr_lec_total += $this->attcompute->exp_time($value->twr_lec);
		                $twr_lab_total += $this->attcompute->exp_time($value->twr_lab);
		                $twr_admin_total += $this->attcompute->exp_time($value->twr_admin);
		                $twr_overload_total += $this->attcompute->exp_time($value->twr_overload);

		                // if($value->teaching_overload != "" && $value->teaching_overload != "--") $teaching_overload_total += $this->attcompute->exp_time($value->teaching_overload);

		                if($counter == 0){
		                    if($value->vacation != ""){
		                    	$vacation_total += $value->vacation;
		                    	$leave_project = $value->vacation;
		                    }

		                    if($value->emergency != ""){
		                    	$emergency_total += $value->emergency;
		                    	$leave_project = $value->emergency;
		                    }
		                    if($value->sick != ""){
		                    	$sick_total += $value->sick;
		                    	$leave_project = $value->sick;
		                    }
		                    if($value->other != ""){
		                    	$other_total += $value->other;
		                    	$leave_project = $value->other;
		                    }
		                }



		                $ot_regular_total += $this->attcompute->exp_time($value->ot_regular);
	                    $ot_restday_total += $this->attcompute->exp_time($value->ot_restday);
	                    $ot_holiday_total += $this->attcompute->exp_time($value->ot_holiday);

	                    $daily_overtime = $this->attcompute->exp_time($value->ot_holiday) + $this->attcompute->exp_time($value->ot_restday) + $this->attcompute->exp_time($value->ot_regular);

	                    $ot_list_tmp = $this->attcompute->getOvertime($employeeid,$value->date,true,$value->holiday_type);
                        $ot_list = $this->attcompute->constructOTlist($ot_list,$ot_list_tmp);

	                    if($value->ot_regular){
	                    	list($overtime_amount, $daily_overtime_mode) = $this->attcompute->getOvertimeAmountDetailed($employeeid, $ot_list, $this->attcompute->exp_time($value->ot_regular));
	                    	$daily_overtime_amount += $overtime_amount;
	                    }else if($value->ot_restday){
	                    	list($overtime_amount, $daily_overtime_mode) = $this->attcompute->getOvertimeAmountDetailed($employeeid, $ot_list, $this->attcompute->exp_time($value->ot_restday));
	                    	$daily_overtime_amount += $overtime_amount;
	                    }else if($value->ot_holiday){
	                    	list($overtime_amount, $daily_overtime_mode) = $this->attcompute->getOvertimeAmountDetailed($employeeid, $ot_list, $this->attcompute->exp_time($value->ot_holiday));
	                    	$daily_overtime_amount += $overtime_amount;
	                    }

		                

		                if($value->service_credit != "" && $value->service_credit != "--") $service_credit_total += $value->service_credit;
		                if($value->cto != "" && $value->cto != "--") $cto_total +=  $this->attcompute->exp_time($value->cto);

		                $holiday_lec_total += $this->attcompute->exp_time($value->holiday_lec);
		                $holiday_lab_total += $this->attcompute->exp_time($value->holiday_lab);
		                $holiday_admin_total += $this->attcompute->exp_time($value->holiday_admin);
		                $holiday_overload_total += $this->attcompute->exp_time($value->holiday_overload);

		                if($value->holiday && $counter == 0) $holiday_total++;

		                if($value->absent_lec && $this->time->hoursToMinutes($value->absent_lec) > 0) $is_absent++;
		                else if($value->absent_lab && $this->time->hoursToMinutes($value->absent_lab) > 0) $is_absent++;
		                else if($value->absent_admin && $this->time->hoursToMinutes($value->absent_admin) > 0) $is_absent++;
		                $date = $value->date;

		                if($value->off_time_in != "--" && $value->off_time_out != "--" && $value->rowspan != 0){
		                	if($value->off_lec){
		                		if($value->lateut_lec && !$isnodtr){
		                			if($value->lateut_remarks == "late") $daily_late += $this->attcompute->exp_time($value->lateut_lec);
		                			else if($value->lateut_remarks == "undertime") $daily_undertime += $this->attcompute->exp_time($value->lateut_lec);
		                		}
		                		if(!$isnodtr) $daily_absents += $this->attcompute->exp_time($value->absent_lec);
		                		$daily_lec .= "work_hours"."=".$this->attcompute->exp_time($value->off_lec)."/late_hours=".(!$isnodtr && $value->lateut_lec ? $value->lateut_lec : 0)."/deduc_hours=".(!$isnodtr && $value->absent_lec ? $value->absent_lec : 0)."/aimsdept=".$value->aims_dept."/suspension=/classification_id=".$value->classification_id;

		                		if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['work_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['work_hours'] = 0;
						        if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['late_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['late_hours'] = 0;
						        if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['deduc_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['deduc_hours'] = 0;
						        if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['leave_project'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['leave_project'] = 0;

						        $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['work_hours'] += ($value->classification_id == 1 ? $this->attcompute->exp_time($value->twr_lec) : $this->attcompute->exp_time($value->off_lec));
					            $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['late_hours'] += $this->attcompute->exp_time($value->lateut_lec);
					            $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['deduc_hours'] += $this->attcompute->exp_time($value->absent_lec);
					            $workhours_arr[$value->aims_dept][$value->classification_id]['LEC']['leave_project'] += $leave_project;
		                	}else if($value->off_lab){
		                		if($value->lateut_lab && !$isnodtr){
		                			if($value->lateut_remarks == "late") $daily_late += $this->attcompute->exp_time($value->lateut_lab);
		                			else if($value->lateut_remarks == "undertime") $daily_undertime += $this->attcompute->exp_time($value->lateut_lab);
		                		}
		                		if(!$isnodtr) $daily_absents += $this->attcompute->exp_time($value->absent_lab);
		                		$daily_lab .= "work_hours"."=".$this->attcompute->exp_time($value->off_lab)."/late_hours=".(!$isnodtr && $value->lateut_lab ? $value->lateut_lab : '')."/deduc_hours=".(!$isnodtr && $value->absent_lab  ? $value->absent_lab : 0)."/aimsdept=".$value->aims_dept."/suspension=/classification_id=".$value->classification_id;

		                		if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['work_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['work_hours'] = 0;
						        if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['late_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['late_hours'] = 0;
						        if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['deduc_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['deduc_hours'] = 0;
						        if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['leave_project'])) $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['leave_project'] = 0;

						        $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['work_hours'] += ($value->classification_id == 1 ? $this->attcompute->exp_time($value->twr_lab) : $this->attcompute->exp_time($value->off_lab));
					            $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['late_hours'] += $this->attcompute->exp_time($value->lateut_lab);
					            $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['deduc_hours'] += $this->attcompute->exp_time($value->absent_lab);
					            $workhours_arr[$value->aims_dept][$value->classification_id]['LAB']['leave_project'] += $leave_project;
		                	}else if($value->off_admin){
		                		if($value->lateut_admin && !$isnodtr){
		                			if($value->lateut_remarks == "late") $daily_late += $this->attcompute->exp_time($value->lateut_admin);
		                			else if($value->lateut_remarks == "undertime") $daily_undertime += $this->attcompute->exp_time($value->lateut_admin);
		                		}
		                		if(!$isnodtr) $daily_absents += $this->attcompute->exp_time($value->absent_admin);
		                		$daily_admin .= "work_hours"."=".$this->attcompute->exp_time($value->off_admin)."/late_hours=".(!$isnodtr && $value->lateut_admin ? $value->lateut_admin : 0)."/deduc_hours=".(!$isnodtr && $value->absent_admin ? $value->absent_admin : 0)."/aimsdept=".$value->aims_dept."/suspension=/classification_id=".$value->classification_id;

		                		if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['work_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['work_hours'] = 0;
						        if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['late_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['late_hours'] = 0;
						        if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['deduc_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['deduc_hours'] = 0;
						        if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['leave_project'])) $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['leave_project'] = 0;

						        $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['work_hours'] += ($value->classification_id == 1 ? $this->attcompute->exp_time($value->twr_admin) : $this->attcompute->exp_time($value->off_admin));
					            $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['late_hours'] += $this->attcompute->exp_time($value->lateut_admin);
					            $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['deduc_hours'] += $this->attcompute->exp_time($value->absent_admin);
					            $workhours_arr[$value->aims_dept][$value->classification_id]['ADMIN']['leave_project'] += $leave_project;
		                	}else if($value->off_overload){
		                		if($value->lateut_overload && !$isnodtr){
		                			if($value->lateut_remarks == "late") $daily_late += $this->attcompute->exp_time($value->lateut_overload);
		                			else if($value->lateut_remarks == "undertime") $daily_undertime += $this->attcompute->exp_time($value->lateut_overload);
		                		}
		                		if(!$isnodtr) $daily_overload .= "work_hours"."=".$this->attcompute->exp_time($value->off_overload)."/late_hours=".(!$isnodtr && $value->lateut_overload  ? $value->lateut_overload : 0)."/deduc_hours=/aimsdept=".$value->aims_dept."/suspension=/classification_id=".$value->classification_id;

		                		if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['work_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['work_hours'] = 0;
						        if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['late_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['late_hours'] = 0;
						        if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['deduc_hours'])) $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['deduc_hours'] = 0;
						        if(!isset($workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['leave_project'])) $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['leave_project'] = 0;

						        $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['work_hours'] +=  ($value->classification_id == 1 ? $this->attcompute->exp_time($value->twr_overload) : $this->attcompute->exp_time($value->off_overload));
					            $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['late_hours'] += $this->attcompute->exp_time($value->lateut_overload);
					            $workhours_arr[$value->aims_dept][$value->classification_id]['RLE']['leave_project'] += $leave_project;
		                	}
		                }
		                $counter++;
		            }
		            

		            if($is_absent > 0 && $is_absent == count($att_date) && ($rowspan != 0 && $rowspan != NULL && $rowspan != "")){
		            	$total_absent++;
		            	$day_absent = substr($date, 5);
                        $daily_absent .= $day_absent." 1/";
		            }else if($is_absent > 0 && $is_absent < count($att_date)){
						$total_absent += .5;
						$day_absent = substr($date, 5);
						$daily_absent .= $day_absent." .5/";
					}else{
		            	if($daily_present == "") $daily_present = $date;
		            	else $daily_present .= ",".$date;
		            }
                    $this->CI->db->query("DELETE FROM employee_attendance_detailed WHERE employeeid='$employeeid' AND sched_date='$date'");

		            $save_data = array(
	                    "employeeid" => $employeeid,
	                    "sched_date" => $date,
	                    "overtime"   =>  ($daily_overtime ? $this->attcompute->sec_to_hm($daily_overtime) : ''),
	                    "late"       =>  ($daily_late ? $this->attcompute->sec_to_hm($daily_late) : ''),
	                    "undertime"  =>  ($daily_undertime ? $this->attcompute->sec_to_hm($daily_undertime) : ''),
	                    "absents"    => ($daily_absents ? $this->attcompute->sec_to_hm($daily_absents) : ''),
	                    "ot_amount"    => $daily_overtime_amount,
	                    "ot_type"    => $daily_overtime_mode,
	                    "lec"    => $daily_lec,
	                    "lab"    => $daily_lab,
	                    "admin"    => $daily_admin,
	                    "rle"    => $daily_overload
	                );
	                
	                $this->CI->db->insert("employee_attendance_detailed", $save_data);
		        } // foreach ($attendance as $att_date)
		        if($isnodtr){
		        	$lateut_lec_total = $lateut_lab_total = $lateut_admin_total = $lateut_overload_total = $absent_lec_total = $absent_lab_total = $absent_admin_total = $daily_absent = "";
		        }
		        $tabsent = '{';
		        if($absent_lec_total) $tabsent .= '"LEC":'.$this->time->hoursToMinutes($this->attcompute->sec_to_hm($absent_lec_total));
		        if($absent_lab_total) $tabsent .= ($tabsent != '{' ? ',' : '').'"LAB":'.$this->time->hoursToMinutes($this->attcompute->sec_to_hm($absent_lab_total));
		        if($absent_admin_total) $tabsent .= ($tabsent != '{' ? ',' : '').'"ADMIN":'.$this->time->hoursToMinutes($this->attcompute->sec_to_hm($absent_admin_total));
		        $tabsent .= '}';


		        $query = $this->CI->db->query("SELECT * FROM attendance_confirmed WHERE cutoffstart='$dfrom' AND cutoffend='$dto' AND employeeid='$employeeid'");
		        if($query->num_rows() == 0){
            		$base_id = '';
            		$lateut_lec_total = $lateut_lec_total ? $this->attcompute->sec_to_hm($lateut_lec_total) : 0;
            		$lateut_lab_total = $lateut_lab_total ? $this->attcompute->sec_to_hm($lateut_lab_total) : 0;
            		$lateut_admin_total = $lateut_admin_total ? $this->attcompute->sec_to_hm($lateut_admin_total) : 0;
            		$lateut_overload_total = $lateut_overload_total ? $this->attcompute->sec_to_hm($lateut_overload_total) : 0;

            		$absent_lec_total = $absent_lec_total ? $this->attcompute->sec_to_hm($absent_lec_total) : 0;
            		$absent_lab_total = $absent_lab_total ? $this->attcompute->sec_to_hm($absent_lab_total) : 0;
            		$absent_admin_total = $absent_admin_total ? $this->attcompute->sec_to_hm($absent_admin_total) : 0;

            		$off_lec_total = $off_lec_total ? $this->attcompute->sec_to_hm($off_lec_total) : 0;
            		$off_lab_total = $off_lab_total ? $this->attcompute->sec_to_hm($off_lab_total) : 0;
            		$off_admin_total = $off_admin_total ? $this->attcompute->sec_to_hm($off_admin_total) : 0;
            		$off_overload_total = $off_overload_total ? $this->attcompute->sec_to_hm($off_overload_total) : 0;
            		$teaching_overload_total = $teaching_overload_total ? $this->attcompute->sec_to_hm($teaching_overload_total) : 0;

					// echo "<pre>";print_r($absent_admin_total);echo "<pre>";die;
            		$res = $this->CI->db->query("INSERT INTO attendance_confirmed SET 
            			employeeid = '$employeeid',
            			cutoffstart = '$dfrom',
            			cutoffend = '$dto',
            			overload = '',
            			substitute = '',
            			workhours_lec = '$off_lec_total',
            			workhours_lab = '$off_lab_total',
            			workhours_admin = '$off_admin_total',
            			workhours_rle = '$off_overload_total',
            			latelec = '$lateut_lec_total',
            			latelab = '$lateut_lab_total',
            			lateadmin = '$lateut_admin_total',
            			laterle = '$lateut_overload_total',
            			absent = '$total_absent',
            			tabsent = '$tabsent',
            			day_absent = '$daily_absent',
            			day_present = '$daily_present',
            			vleave = '$vacation_total',
            			eleave = '$emergency_total',
            			sleave = '$sick_total',
            			oleave = '$other_total',
            			deduclec = '$absent_lec_total',
            			deduclab = '$absent_lab_total',
            			deducadmin = '$absent_admin_total',
            			date_processed = '". date("Y-m-d H:i:s") ."',
            			payroll_cutoffstart = '$payroll_start',
            			payroll_cutoffend = '$payroll_end',
            			quarter = '$payroll_quarter',
            			hold_status = '',
            			hold_status_change = '',
            			f_dtrend = '$dto',
            			f_payrollend = '$payroll_end',
            			tholiday = '$holiday_total',
            			tsuspension = '',
            			t_overload = '$teaching_overload_total'");

					echo "<pre>";print_r($this->CI->db->last_query());echo "<pre>";
            		if($res) $base_id = $this->CI->db->insert_id();

	                foreach ($workhours_arr as $aimsdept => $classification_arr) {
	                    foreach ($classification_arr as $classification => $leclab_arr) {
	                    	foreach ($leclab_arr as $type => $sec) {
		                    	$work_hours = $this->attcompute->sec_to_hm($sec['work_hours']);
		                        $late_hours = $this->attcompute->sec_to_hm($sec['late_hours']);
		                        $deduc_hours = $this->attcompute->sec_to_hm($sec['deduc_hours']);
		                        $leave_project = $this->attcompute->sec_to_hm($sec['leave_project']);
		                        $this->CI->db->query("INSERT INTO workhours_perdept (base_id, work_hours, work_days , late_hours, deduc_hours, type, aimsdept,leave_project, classification) VALUES ('$base_id','$work_hours',0,'$late_hours','$deduc_hours','$type','$aimsdept','$leave_project','$classification')");
								// echo "<pre>";print_r($this->CI->db->last_query());echo "<pre>";
		                    }
	                    }
	                }
            	}
        	}else{
        		if(!isset($workhours_arr['']['ADMIN']['work_hours'])) $workhours_arr['']['ADMIN']['work_hours'] = 0;
		        if(!isset($workhours_arr['']['ADMIN']['late_hours'])) $workhours_arr['']['ADMIN']['late_hours'] = 0;
		        if(!isset($workhours_arr['']['ADMIN']['deduc_hours'])) $workhours_arr['']['ADMIN']['deduc_hours'] = 0;
		        if(!isset($workhours_arr['']['ADMIN']['leave_project'])) $workhours_arr['']['ADMIN']['leave_project'] = 0;
        		$startdate = $enddate = $quarter = $isnodtr = "";
		        $payrollcutoff = $this->extras->getPayrollCutoff($dfrom, $dto);
		        foreach($payrollcutoff as $cutoff_info){
		            $startdate = $cutoff_info['startdate'];
		            $enddate = $cutoff_info['enddate'];
		            $quarter = $cutoff_info['quarter'];
		            $isnodtr = $cutoff_info['nodtr'];
		        }
        		$attendance = $this->employeeAttendance->getAttendanceNonteaching($employeeid, $dfrom, $dto);
        		$not_included_ol = array("ABSENT", "EL", "VL", "SL", "CORRECTION");
				$off_time_in = $off_time_out = $off_time_total = $actlog_time_in = $actlog_time_out = $terminal = $twr_total = $ot_regular_total = $ot_restday_total = $ot_holiday_total = $late_total = $undertime_total = $vl_deduc_late_total = $vl_deduc_undertime_total = $absent_data_total = $service_credit_total = $cto_credit_total = $vacation_total = $sick_total = $other_total = $emergency_total = $holiday_total = $total_holiday = $suspension_total = $total_suspension = $total_absent = $workdays = $total_late_deduc = $late_deduc =  0;
				$daily_overtime_amount = 0;
				$daily_absent = "";

				foreach ($attendance as $att_date) {
					$counter = $daily_overtime = $daily_undertime = $daily_late = $daily_absents = $daily_overtime_amount =  0;
					$rowspan = 0;
					$is_absent = 0;
					$date = $daily_overtime_mode = "";
					$ot_list = array();
					foreach ($att_date as $key => $value) {
						$date = $value->date;
						$leave_project = 0;
						$rowspan = $value->rowspan;
						if($counter == 0){
							$twr_total += $this->attcompute->exp_time($value->twr);
							$ot_regular_total += $this->attcompute->exp_time($value->ot_regular);
							$ot_restday_total += $this->attcompute->exp_time($value->ot_restday);
							$ot_holiday_total += $this->attcompute->exp_time($value->ot_holiday);
							if($value->other != "" && $value->other != "--" && (!in_array($value->other, $not_included_ol) && $value->other && $value->other!="DIRECT")){
								if($other_total == 0.5){
									$other_total += 0.5;
									$leave_project = 0.5;
								}
								else{
									$other_total += 1;
									$leave_project = 1;
								}
							}

							$daily_overtime += $this->attcompute->exp_time($value->ot_holiday) + $this->attcompute->exp_time($value->ot_restday) + $this->attcompute->exp_time($value->ot_regular);
							
							if (!empty($value->holiday)) { 
								if ($value->holiday == 3 || $value->holiday == '3') { 
									$suspension_total++;
									$total_suspension += $this->attcompute->exp_time($value->twr);
								} else {
									$holiday_total++;
									$total_holiday += $this->attcompute->exp_time($value->twr);
								}
							}
							
				
							$off_time_total += $this->attcompute->exp_time($value->off_time_total);
							if($value->vl != "" && $value->vl != "--"){
								$vacation_total += $value->vl;
								$leave_project = $value->vl;
							}
							if($value->sl != "" && $value->sl != "--"){
								$sick_total += $value->sl;
								$leave_project = $value->sl;
							}

							if($value->el != "" && $value->el != "--"){
								$emergency_total += $value->el;
								$leave_project = $value->el;
							}
						}

						
						$ot_list_tmp = $this->attcompute->getOvertime($employeeid,$value->date,true,$value->holiday_type);
                    	$ot_list = $this->attcompute->constructOTlist($ot_list,$ot_list_tmp);
                    	
                    	$daily_undertime += $this->attcompute->exp_time($value->undertime);
                    	$daily_late += $this->attcompute->exp_time($value->late);
                    	$daily_absents += $this->attcompute->exp_time($value->absent);
						if($value->ot_regular){
	                    	list($overtime_amount, $daily_overtime_mode) = $this->attcompute->getOvertimeAmountDetailed($employeeid, $ot_list, $this->attcompute->exp_time($value->ot_regular));
	                    	$daily_overtime_amount += $overtime_amount;
	                    }else if($value->ot_restday){
	                    	list($overtime_amount, $daily_overtime_mode) = $this->attcompute->getOvertimeAmountDetailed($employeeid, $ot_list, $this->attcompute->exp_time($value->ot_restday));
	                    	$daily_overtime_amount += $overtime_amount;
	                    }else if($value->ot_holiday){
	                    	list($overtime_amount, $daily_overtime_mode) = $this->attcompute->getOvertimeAmountDetailed($employeeid, $ot_list, $this->attcompute->exp_time($value->ot_holiday));
	                    	$daily_overtime_amount += $overtime_amount;
	                    }
	                    if($value->absent) $is_absent++;

						if($value->late_deduc){
							$late_deduc += $this->attcompute->exp_time($value->late_deduc);
							$total_late_deduc = $this->attcompute->sec_to_hm($late_deduc);
						}

				        $workhours_arr['']['ADMIN']['work_hours'] += $this->attcompute->exp_time($value->off_time_total);
			            $workhours_arr['']['ADMIN']['late_hours'] += $this->attcompute->exp_time($value->late) + $this->attcompute->exp_time($value->undertime);
			            $workhours_arr['']['ADMIN']['deduc_hours'] += $this->attcompute->exp_time($value->absent);
			            $workhours_arr['']['ADMIN']['leave_project'] += $leave_project;

						$late_total += $this->attcompute->exp_time($value->late);
						$undertime_total += $this->attcompute->exp_time($value->undertime);
						$vl_deduc_late_total += $this->attcompute->exp_time($value->vl_deduc_late);
						$vl_deduc_undertime_total += $this->attcompute->exp_time($value->vl_deduc_undertime);
						$absent_data_total += $this->attcompute->exp_time($value->absent);

						if($value->service_credit != "" && $value->service_credit != "--") $service_credit_total += $value->service_credit;
						if($value->cto != "" && $value->cto != "--") $cto_credit_total +=  $this->attcompute->exp_time($value->cto);
						$counter++;
					}

					if($is_absent > 0 && $is_absent == count($att_date) && ($rowspan != 0 && $rowspan != NULL && $rowspan != "")){
		            	$total_absent++;
		            	$day_absent = substr($date, 5);
		            	// echo "<pre>"; print_r($date); 
                        $daily_absent .= $day_absent." 1/";
		            }else if($is_absent > 0 && $is_absent < count($att_date)){
						$total_absent += .5;
						$day_absent = substr($date, 5);
						$daily_absent .= $day_absent." .5/";
					}

		            if($rowspan != 0 && $rowspan != NULL && $rowspan != ""){
		            	$workdays++;
		            }

					$this->CI->db->query("DELETE FROM employee_attendance_detailed WHERE employeeid='$employeeid' AND sched_date='$date'");

		            $save_data = array(
	                    "employeeid" => $employeeid,
	                    "sched_date" => $date,
	                    "overtime"   =>  ($daily_overtime ? $this->attcompute->sec_to_hm($daily_overtime) : ''),
	                    "late"       =>  ($daily_late ? $this->attcompute->sec_to_hm($daily_late) : ''),
	                    "undertime"  =>  ($daily_undertime ? $this->attcompute->sec_to_hm($daily_undertime) : ''),
	                    "absents"    => ($daily_absents ? $this->attcompute->sec_to_hm($daily_absents) : ''),
	                    "ot_amount"    => $daily_overtime_amount,
	                    "ot_type"    => $daily_overtime_mode
	                );
	                
	                $this->CI->db->insert("employee_attendance_detailed", $save_data);
				}

				if($isnodtr){
		        	$late_total = $undertime_total = $absent_data_total = "";
		        }
				
				$query = $this->CI->db->query("SELECT * FROM attendance_confirmed_nt WHERE cutoffstart='$dfrom' AND cutoffend='$dto' AND employeeid='$employeeid'");
				if($query->num_rows() == 0){
					$base_id = "";
					$ot_regular_total = $ot_regular_total ? $this->attcompute->sec_to_hm($ot_regular_total) : '';
            		$ot_restday_total = $ot_restday_total ? $this->attcompute->sec_to_hm($ot_restday_total) : '';
            		$ot_holiday_total = $ot_holiday_total ? $this->attcompute->sec_to_hm($ot_holiday_total) : '';

            		$late_total = $late_total ? $this->attcompute->sec_to_hm($late_total) : '';
            		$total_suspension = $total_suspension ? $this->attcompute->secondsToDecimalHours($total_suspension) : '';
            		$undertime_total = $undertime_total ? $this->attcompute->sec_to_hm($undertime_total) : '';
            		$absent_data_total = $absent_data_total ? $this->attcompute->sec_to_hm($absent_data_total) : '';

					$res = $this->CI->db->query("INSERT INTO attendance_confirmed_nt SET 
            			employeeid = '$employeeid',
            			cutoffstart = '$dfrom',
            			cutoffend = '$dto',
            			workdays = '$workdays',
            			otreg = '$ot_regular_total',
            			otrest = '$ot_restday_total',
            			othol = '$ot_holiday_total',
            			lateut = '$late_total',
            			late_deduc = '$total_late_deduc',
            			ut = '$undertime_total',
            			absent = '$absent_data_total',
            			day_absent = '$total_absent',
            			eleave = '$emergency_total',
            			vleave = '$vacation_total',
            			sleave = '$sick_total',
            			oleave = '$other_total',
            			status = 'SUBMITTED',
            			isholiday = '$holiday_total',
            			tsuspension = '$total_suspension',
            			issuspended = '$suspension_total',
            			forcutoff = '1',
            			payroll_cutoffstart = '$startdate',
            			payroll_cutoffend = '$enddate',
            			quarter = '$quarter',
            			date_processed = '". date("Y-m-d h:i:s") ."',
            			usertype = '$usertype',
            			scleave = '$service_credit_total',
            			cto = '$cto_credit_total'");
					// echo "<pre>";print_r($this->CI->db->last_query());
            		if($res){
            			$base_id = $this->CI->db->insert_id();
            			foreach ($workhours_arr as $aimsdept => $leclab_arr) {
		                    foreach ($leclab_arr as $type => $sec) {
		                    	$work_hours = $this->attcompute->sec_to_hm($sec['work_hours']);
		                        $late_hours = $this->attcompute->sec_to_hm($sec['late_hours']);
		                        $deduc_hours = $this->attcompute->sec_to_hm($sec['deduc_hours']);
		                        $this->CI->db->query("INSERT INTO workhours_perdept_nt (base_id, work_hours, work_days, late_hours, deduc_hours, type, aimsdept) VALUES ('$base_id', '$work_hours', 0,'$late_hours','$deduc_hours','$type','$aimsdept')");
		                    }
		                }

		                foreach ($ot_list as $ot_data_tmp){
		                    $ot_data = $ot_data_tmp;
		                    $ot_data["base_id"] = $base_id;

		                    $this->CI->db->insert('attendance_confirmed_nt_ot_hours', $ot_data);
		                }
            		}
				}
        	}
        } // foreach (explode(",", $emp_list) as $employeeid)
        return true;
	}

    public function getEmployeeToCalculateJob()
    {
        return $this->worker_model->fetch_emp_calculate();
    }
}