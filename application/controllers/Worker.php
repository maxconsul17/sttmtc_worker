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
    }
    
    // Worker
    protected function handleWork($worker_id) {
        $getCalculateJob = $this->attendance_manager->getCalculateJob();
        $getReportJob = $this->report_manager->getReportJob();
        $getRecomputeJob = $this->recompute_manager->getRecomputeJob();
        $getPayrollJob = $this->payroll_manager->getPayrollJob();
        $getAttendanceJob = $this->attendance_manager->getAttendanceJob();

        if($getCalculateJob){
            $this->attendance_manager->processCalculation($getCalculateJob);
            return false;
        }

        if($getAttendanceJob){
            $this->attendance_manager->processAttendance($getAttendanceJob, $worker_id);
            return false;
        }

        if($getReportJob){
            $this->report_manager->processReport($getReportJob, $worker_id);
            return false;
        }
        if($getRecomputeJob){
            $this->recompute_manager->processRecompute($getRecomputeJob, $worker_id);
            return false;
        }
        if($getPayrollJob){
            $this->payroll_manager->processPayroll($getPayrollJob, $worker_id);
            return false;
        }
        
        return false;
    }

    // Listener
    protected function handleListen() {
        $getReportJob = $this->report_manager->getReportJob();
        $getRecomputeJob = $this->recompute_manager->getRecomputeJob();
        $getPayrollJob = $this->payroll_manager->getPayrollJob();
        $getCalculateJob = $this->attendance_manager->getCalculateJob();
        $getAttendanceJob = $this->attendance_manager->getAttendanceJob();

        if($getCalculateJob) return true;
        if($getAttendanceJob) return true;
        if($getReportJob) return true;
        if($getRecomputeJob) return true;
        if($getPayrollJob) return true;

        return false;

        // return $this->attendance_manager->getEmployeeToCalculateJob(); // return true or false
    }
}