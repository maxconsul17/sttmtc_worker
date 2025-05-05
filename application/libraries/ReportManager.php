<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ReportManager
{   
    private $CI;
    private $worker_model;
    private $time;

    function __construct() 
    {
        $this->CI = & get_instance();
        $this->CI->load->model("Worker_model", "worker_model");
        $this->CI->load->model("Time", "time");
        $this->worker_model = $this->CI->worker_model;
        $this->time = $this->CI->time;
    }

    // public function processReport(){
    //     $this->init_process_dtr();
    // }

    // Initialize processing of Daily Time Record (DTR) reports
    public function processReport($reportJob, $worker_id){
        // $this->worker_model->forTrail($worker_id);
        // $reports = $this->worker_model->get_report_task(); // Get pending DTR report tasks
        
        // if ($reports->num_rows() > 0) {
        //     foreach($reports->result() as $report){
        //         $this->process_dtr($report); // Process the DTR report for the first task found
        //     }
        // }

        $this->process_dtr($reportJob, $worker_id);
    }

    public function getReportJob()
    {
        return $this->worker_model->getReportJob();
    }

    // Process the DTR report for a given report task
    public function process_dtr($det, $worker_id){
        $this->worker_model->updateReportStatus($det->id, "", "ongoing");
        if ($det->total_tasks == "0") $this->worker_model->updateReportStatus($det->id, "", "No employee to generate");

        // Prepare date range
        $data["actual_dates"] = [$det->dfrom, $det->dto];
        
        // Fetch employees for the report
        $employeelist = $this->worker_model->getEmployeeList($det->where_clause, $worker_id, $det->id);
        // $this->worker_model->forTrail();

        foreach ($employeelist as $employee) {
            try {
                // Check if the report was cancelled
                if ($this->worker_model->report_cancelled($det->id) > 0) {
                    $this->worker_model->updateReportStatus($det->id, "", "cancelled", 0);
                    break;
                }

                // Prepare data for the report
                $isteaching = $this->worker_model->getempteachingtype($employee->employeeid);
                $data['report_id'] =  $det->id;
                $data['campus'] = $employee->campusid;
                $data['employeeid'] = $employee->employeeid;
                $data['attendance'] = $this->worker_model->getEmployeeDTR($employee->employeeid, $det->dfrom, $det->dto, $isteaching);
                $data['dtrcutoff'] = date('F d, Y', strtotime($det->dfrom)) . ' - ' . date('F d, Y', strtotime($det->dto));
                // Load the appropriate report view
                $report = $this->CI->load->view(
                    $isteaching ? 'dtr/teachingDailyTimeReport' : 'dtr/nonteachingDailyTimeReport',
                    $data,
                    TRUE
                );
                $this->worker_model->forTrail($report);
                // Update the report breakdown and generate the PDF
                $this->worker_model->updateReportBreakdown("done", $employee->rep_breakdown_id, $det->id);

                $data["report_list"] = [["report" => $report]];
                $data["path"] = "files/reports/pdf/{$employee->employeeid}_{$det->id}.pdf";

                $this->CI->load->view('dtr/daily_time_report_pdf', $data);

            }catch (Exception $e) {
                $this->worker_model->updateReportStatus($det->id, "", $e);
                continue;
            }
        }
    }
}