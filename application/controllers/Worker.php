<?php

// Load Composer's autoloader for using external libraries
require APPPATH . '../vendor/autoload.php'; 

use yidas\queue\worker\Controller as WorkerController;

defined('BASEPATH') OR exit('No direct script access allowed');

class Worker extends WorkerController
{
    public $debug = true;
    public $workerWaitSeconds = 5;
    public $workerMaxNum = 4;
    public $workerStartNum = 1;
    public $logPath = APPPATH . 'cache/my-worker.log';
    public $workerHeathCheck = true;

    // Initializer
    protected function init() {
        $this->load->library("ReportManager", null, "report_manager");
        $this->load->library("RecomputeManager", null, "recompute_manager");
        $this->load->library("PayrollManager", null, "payroll_manager");
        $this->load->library("AttendanceManager", null, "attendance_manager");
        $this->load->library("FacialManager", null, "facial_manager");
        $this->load->library("UploadManager", null, "upload_manager");
    }
    
    // Worker
    protected function handleWork($worker_id) {

        $getUploadJob = $this->attendance_manager->getUploadJob();
        $getUploadDataJob = $this->attendance_manager->getUploadDataJob();

        if($getUploadJob){
            $this->upload_manager->processUpload($getUploadJob, $worker_id);
            return false;
        }

        if($getUploadDataJob){
            $this->upload_manager->processUploadData($getUploadDataJob, $worker_id);
            return false;
        }
        

        // $getCalculateJob = $this->attendance_manager->getCalculateJob();
        // $getReportJob = $this->report_manager->getReportJob();
        // $getRecomputeJob = $this->recompute_manager->getRecomputeJob();
        // $getPayrollJob = $this->payroll_manager->getPayrollJob();
        // $getAttendanceJob = $this->attendance_manager->getAttendanceJob();
        // $getFacialJob = $this->facial_manager->getFacialJob();
        // $getFailedFacialJob = $this->facial_manager->getFailedFacialJob();

        // if($getCalculateJob){
        //     $this->attendance_manager->processCalculation($getCalculateJob, $worker_id);
        //     return false;
        // }

        // if($getAttendanceJob){
        //     $this->attendance_manager->processAttendance($getAttendanceJob, $worker_id);
        //     return false;
        // }

        // if($getReportJob){
        //     $this->report_manager->processReport($getReportJob, $worker_id);
        //     return false;
        // }
        // if($getRecomputeJob){
        //     $this->recompute_manager->processRecompute($getRecomputeJob, $worker_id);
        //     return false;
        // }
        // if($getPayrollJob){
        //     $this->payroll_manager->processPayroll($getPayrollJob, $worker_id);
        //     return false;
        // }
        // if($getFacialJob){
        //     $this->facial_manager->processFacial($getFacialJob, $worker_id);
        //     return false;
        // }
        // if($getFailedFacialJob){
        //     $this->facial_manager->processFailedFacial($getFailedFacialJob, $worker_id);
        //     return false;
        // }
        
        return false;
    }

    // Listener
    protected function handleListen() {
        // $getReportJob = $this->report_manager->getReportJob();
        // $getRecomputeJob = $this->recompute_manager->getRecomputeJob();
        // $getPayrollJob = $this->payroll_manager->getPayrollJob();
        // $getCalculateJob = $this->attendance_manager->getCalculateJob();
        // $getAttendanceJob = $this->attendance_manager->getAttendanceJob();
        $getUploadJob = $this->upload_manager->getUploadJob();
        // $getFacialJob = $this->facial_manager->getFacialJob();
        // $getFailedFacialJob = $this->facial_manager->getFailedFacialJob();

        // if($getCalculateJob) return true;
        // if($getAttendanceJob) return true;
        // if($getReportJob) return true;
        // if($getRecomputeJob) return true;
        // if($getPayrollJob) return true;
        if($getUploadJob) return true;
        // if($getFacialJob) return true;
        // if($getFailedFacialJob) return true;

        return false;

        // return $this->attendance_manager->getEmployeeToCalculateJob(); // return true or false
    }
}