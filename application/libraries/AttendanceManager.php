<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AttendanceManager
{   
    private $CI;
    private $worker_model;

    function __construct() 
    {
        $this->CI = & get_instance();
        $this->CI->load->model("Worker_model", "worker_model");
        $this->worker_model = $this->CI->worker_model;
    }

    public function processCalculation(){
        $emp_list = $this->worker_model->fetch_emp_calculate();  // Fetch list of employees with attendance tasks
        
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

        // Insert attendance calculation history to the database
        $this->CI->db->insert("att_calc_history", $calc_history);
    }

    public function getEmployeeToCalculateJob()
    {
        return $this->worker_model->fetch_emp_calculate();
    }
}