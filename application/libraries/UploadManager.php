
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UploadManager
{   
    private $CI;
    private $worker_model;
    private $time;

    function __construct() 
    {
        $this->CI = & get_instance();
        
        $this->CI->load->model("Worker_model", "worker_model");
        $this->CI->load->model("Time", "time");
        $this->CI->load->model("employeeAttendance", "employeeAttendance");
        $this->CI->load->model("Employee", "employee");
        $this->CI->load->model("Facial", "facial");

        $this->worker_model = $this->CI->worker_model;
        $this->time = $this->CI->time;
        $this->employeeAttendance = $this->CI->employeeAttendance;
        $this->employee = $this->CI->employee;
        $this->facial = $this->CI->facial;
    }

    public function getUploadJob()
    {
        return $this->worker_model->getUploadJob();
    }

    public function getUploadDataJob()
    {
        return $this->worker_model->getUploadDataJob();
    }


}