<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FacialManager
{   
    private $CI;
    private $worker_model;
    private $time;

    function __construct() 
    {
        $this->CI = & get_instance();
        $this->CI->load->model("Worker_model", "worker_model");
        $this->CI->load->model("Time", "time");
        $this->CI->load->database();
        $this->worker_model = $this->CI->worker_model;
        $this->time = $this->CI->time;
    }

    // public function processReport(){
    //     $this->init_process_dtr();
    // }

    // Initialize processing of Daily Time Record (DTR) reports
    public function processFacial($facialJob, $worker_id){

        if ($facialJob->worker_id == $worker_id) $this->process_facial($facialJob, $worker_id);
    }

    public function processFailedFacial($facialJob, $worker_id){

        if ($facialJob->worker_id == $worker_id){
            $this->worker_model->retryFacial($facialJob->id);
        }
    }

    public function getFacialJob()
    {
        return $this->worker_model->getFacialJob();
    }
    public function getFailedFacialJob()
    {
        return $this->worker_model->getFailedFacialJob();
    }

    // Process the DTR report for a given report task
    public function process_facial($det, $worker_id){
        $this->worker_model->updateFacialStatus($det->id,"ongoing");
        try{
            $trail = array();
            $campus_id = $this->CI->db->campus_code;
            $site_list = Globals::sites();
            unset($site_list[$campus_id]);
            foreach($site_list as $code => $desc){
                $endpoint = Globals::campusEndpoints($code);
                $api_url = $endpoint."Api_/saveLogsFromOtherSite";
                $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => $api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $det->body,
                CURLOPT_HTTPHEADER => array(
                    "Accept: application/json",
                    ),
                ));

                $result = curl_exec($curl);
                $response = json_decode($result);
                $err = curl_error($curl);

                if($err === ""){
                    $trail = array(
                        "details" => $det->body,
                        "status" => "success"
                    );
                }else{
                    $trail = array(
                        "details" => $det->body,
                        "status" => "has curl error"
                    );
                }
                
                $this->CI->db->insert("transfer_logs_trail", $trail);
                curl_close($curl);
                
            }
        }catch (Exception $e) {
            $trail = array(
                "details" => $det->body,
                "status" => "has code error"
            );

            $this->CI->db->insert("transfer_logs_trail", $trail);
        }
        $this->worker_model->updateFacialStatus($det->id,"done");
    }
}