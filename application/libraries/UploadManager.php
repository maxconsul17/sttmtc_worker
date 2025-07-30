
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UploadManager
{   
    private $CI;
    private $worker_model;

    function __construct() 
    {
        $this->CI = & get_instance();
        
        $this->CI->load->model("Worker_model", "worker_model");

        $this->worker_model = $this->CI->worker_model;
    }

    public function getUploadJob()
    {   
        $this->worker_model->forTrail("here");
        return $this->worker_model->getUploadJob();
    }

    public function getUploadDataJob()
    {
        return $this->worker_model->getUploadDataJob();
    }


}