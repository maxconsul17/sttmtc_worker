
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

    public function processUpload($uploadJob, $worker_id){
       $this->initUploadLogs($uploadJob, $worker_id);
    }

    public function processUploadData($uploadJob, $worker_id){
        if ($uploadJob->worker_id == $worker_id) $this->uploadLogs($uploadJob, $worker_id);
    }

    public function uploadLogs($uploadJob, $worker_id){
        if($this->worker_model->uploadedDataStatus($uploadJob->id) === 0){
            return true;
        }

        $success = 0;
        $failed = 0;
        $total = 0;
        $phoenix_failed_row = 0;
        $aims_failed_row = 0;
        $failed_content = "<p style='color:red;font-weight:bold;'>Upload failed. Please retry.</p>";
        $exceeding_dates = [];
        $processed_by = $uploadJob->user;
        

        if ($uploadJob->emp_data && isset($uploadJob->emp_data)) {
            $emp_d_list = json_decode($uploadJob->emp_data);
            $this->worker_model->updateUploadDataStatus($uploadJob->id, 'ongoing');
            foreach($emp_d_list as $row){
                // ERROR CODE
                // 1 = Empty date
                // 2 = Empty employeeid
                // 3 = Empty timein
                // 4 = Empty timeout
                // 5 = Employee not exist
                // 6 = Log date already exist in timesheet
                // 7 = Empty timein (PM)
                // 8 = Empty timeout (PM)
                // 9 = Empty row (skip)
                // 10 = Future dates must not be in application
                $err_code = 0;

                // TO CHECK IF SA CURRENT UPLOAD GALING YUNG DATA
                $prev_date = [];

                $err_code = 0;
                $log_date = $row[0];
                $emp_id = $row[1];
                $name = $row[2];
                $timein = $row[3];
                $timeout = $row[4];
                $pmtimein = $row[5];
                $pmtimeout = $row[6];

                $timeinVal = $timein;
                $timeoutVal = $timeout;
                $pmtimeinVal = $pmtimein;
                $pmtimeoutVal = $pmtimeout;

                $today = date('Y-m-d');


                  // TAG WHO IS CURRENTLY PROCESSING
                $this->worker_model->updateUploadCurrentProcessing($uploadJob->base_id, $emp_id."-".$log_date);

                if (!empty($log_date)) {
                    $date_format = DateTime::createFromFormat('m/d/Y', $log_date);

                    if ($log_date === false) {
                        $err_code = 1;
                    } else {
                        $convlog_date = date('Y-m-d', strtotime($log_date));
                        if ($convlog_date > $today) {
                            $err_code = 10;
                            $exceeding_dates[] = $row + 1; 
                        }
                    }
                } else {
                    $err_code = 1;
                }

                if($emp_id == ""){
                    $err_code = 2;
                }

                if($timein == ""){
                    // $err_code = 3;
                }

                if($timeout == ""){
                    // $err_code = 4;
                }

                $is_exists = $this->employee->isEmployeeIDExist($emp_id);
                if($is_exists == 0){
                    $err_code = 5;
                }

                if (!isset($prev_date[$emp_id])) {
                    $prev_date[$emp_id] = [];
                }

                // $is_log_exist = $this->employee->isEmployeeLogExist($emp_id,$convlog_date);
                // if($is_log_exist > 0 && !in_array($convlog_date, $prev_date[$emp_id])){
                //  $err_code = 6;
                // }

                if($pmtimein == ""){
                    // $err_code = 7;
                }

                if($pmtimeout == ""){
                    // $err_code = 8;
                }

                // it should be last err_code
                if ($log_date == "" && $emp_id == "" && $name == "" && $timein == "" && $timeout == "" && $pmtimein == "" && $pmtimeout == ""){
                    $err_code = 9;
                }

                if($err_code == 0){
                    //delete same logs
                    $epoch_am_timein = strtotime(date("Y-m-d H:i:s", strtotime($log_date." ".$timein)))*1000;
                    $epoch_am_timeout = strtotime(date("Y-m-d H:i:s", strtotime($log_date." ".$timeout)))*1000;
                    $epoch_pm_timein = strtotime(date("Y-m-d H:i:s", strtotime($log_date." ".$pmtimein)))*1000;
                    $epoch_pm_timeout = strtotime(date("Y-m-d H:i:s", strtotime($log_date." ".$pmtimeout)))*1000;

                    $this->CI->db->query("DELETE FROM facial_Log WHERE deviceKey='UPLOAD' AND employeeid='$emp_id' AND (time = '{$epoch_am_timein}' OR time = '{$epoch_am_timeout}' OR time = '{$epoch_pm_timein}' OR time = '{$epoch_pm_timeout}')");
                    //end

                    $log_data = array();
                    $facial_data = array();
                    $timein = date("Y-m-d H:i:s", strtotime($log_date." ".$timein));
                    $timeout = date("Y-m-d H:i:s", strtotime($log_date." ".$timeout));
                    $epochtimein = strtotime($timein)*1000;
                    $epochtimeout = strtotime($timeout)*1000;

                    // echo "<pre>";print_r($epochtimein);die;
                    
                    $log_data = array(
                        "userid" => $emp_id,
                        "timein" => $timein,
                        "timeout" => $timeout,
                        "otype" => "UPLOAD",
                        "dateadded" => date("Y-m-d"),
                        "addedby" => $uploadJob->user,
                        "username" => $uploadJob->user
                    );
                    
                    $log_date = date("Y-m-d", strtotime($log_date));

                    $facial_data = array(
                        "deviceKey" => "UPLOAD",
                        "employeeid" => $emp_id,
                        "time" => $epochtimein,
                        "type" => "UPLOAD",
                        "name" => $name
                    );

                    if ($timeinVal != "") $this->facial->saveUploadedLogs($facial_data, "",$processed_by);

                    $facial_data = array(
                        "deviceKey" => "UPLOAD",
                        "employeeid" => $emp_id,
                        "time" => $epochtimeout,
                        "type" => "UPLOAD",
                        "name" => $name
                    );
                        
                    if ($timeoutVal != "") $this->facial->saveUploadedLogs($facial_data,"", $processed_by);
                    

                    //For PM schedule
                    $log_data = array();
                    $facial_data = array();
                    $pmtimein = date("Y-m-d H:i:s", strtotime($log_date." ".$pmtimein));
                    $pmtimeout = date("Y-m-d H:i:s", strtotime($log_date." ".$pmtimeout));
                    $epochpmtimein = strtotime($pmtimein) * 1000;
                    $epochpmtimeout = strtotime($pmtimeout) * 1000;
                    
                    $log_data = array(
                        "userid" => $emp_id,
                        "timein" => $pmtimein,
                        "timeout" => $pmtimeout,
                        "otype" => "UPLOAD",
                        "dateadded" => date("Y-m-d"),
                        "addedby" => $uploadJob->user,
                        "username" => $uploadJob->user
                    );
                    
                    $log_date = date("Y-m-d", strtotime($log_date));
                    $this->employeeAttendance->updateDTR($emp_id, $log_date, $log_date);


                    $facial_data = array(
                        "deviceKey" => "UPLOAD",
                        "employeeid" => $emp_id,
                        "time" => $epochpmtimein,
                        "type" => "UPLOAD",
                        "name" => $name
                    );
                    if ($pmtimeinVal != "") $this->facial->saveUploadedLogs($facial_data,"", $processed_by);
                    $facial_data = array(
                        "deviceKey" => "UPLOAD",
                        "employeeid" => $emp_id,
                        "time" => $epochpmtimeout,
                        "type" => "UPLOAD",
                        "name" => $name
                    );
                    if ($pmtimeoutVal != "") $this->facial->saveUploadedLogs($facial_data,"", $processed_by);
                    //For PM schedule END

                    $prev_date[$emp_id][] = $convlog_date;
                    $success++;
                }elseif ($err_code == 9){
                    //skip blank row
                } else {
                    $failed_row = 1;
                    $log_date = date("Y-m-d", strtotime($log_date));
                    $this->worker_model->saveFailedUploadedLogs($uploadJob->base_id, $log_date, $err_code, $emp_id);
                    $failed++;
                }
                
                $total++;
                $this->worker_model->updateUploadDataStatus($uploadJob->id, 'done');

                if (($this->CI->db->query("SELECT 1 FROM upload_list_data WHERE base_id = '$uploadJob->base_id' AND status != 'done'")->num_rows()) == 0){
                    $this->worker_model->updateUploadStatus($uploadJob->base_id, 'done');
                }

                $this->worker_model->updateUploadDataErrCode($uploadJob->id, $err_code);

                $this->CI->db->query("UPDATE upload_list_data SET completed = completed + 1 WHERE base_id = '$uploadJob->base_id' AND completed != total ");
            }

        } else {
            $err_code = 403;
            $this->worker_model->updateUploadDataStatus($uploadJob->id, 'done');
            if (($this->CI->db->query("SELECT 1 FROM upload_list_data WHERE base_id = '$uploadJob->base_id' AND status != 'done'")->num_rows()) == 0){
                $this->worker_model->updateUploadStatus($uploadJob->base_id, 'done');
            }
            $this->worker_model->updateUploadDataErrCode($uploadJob->id, $err_code);
        }
        if (!empty($exceeding_dates)) {
            $failed_content .= "<p >Future dates are not allowed for employee records. Please enter a valid date for the ff:</p>";
            $failed_content .= "<ul style='color:red;'>";
            foreach ($exceeding_dates as $row_num) {
                $failed_content .= "<br>Row #" . $row_num . "";
            }
            $failed_content .= "</ul>";
        }
    
        if($failed == 0 && empty($exceeding_dates)){
            $failed_content = "";
        }


    
        // $response = array(
        //  "icon" => "info",
        //  "title" => "Batch Upload Result",
        //  "msg" => "<p>Uploaded <b> " . $total . "</b> employee logs. Successfully applied <b> ". $success . "</b> logs. </p>". $failed_content
        // );
    
        // echo json_encode($response);
    }

    public function initUploadLogs($uploadJob, $worker_id){
        $this->worker_model->updateUploadStatus($uploadJob->id, 'ongoing');
        $success = 0;
        $failed = 0;
        $total = 0;
        $phoenix_failed_row = 0;
        $aims_failed_row = 0;
        $failed_content = "<p style='color:red;font-weight:bold;'>Upload failed. Please retry.</p>";
        $exceeding_dates = [];
        // Check if the file was uploaded without errors
        if ($uploadJob->filedata && isset($uploadJob->filedata)) {
            $fileData = $uploadJob->filedata;
    
            // Open the file for reading
            if (($handle = fopen('php://memory', 'r+')) !== false) {
                fwrite($handle, $fileData);
                rewind($handle);
                $csvData = []; // Initialize an array to hold the CSV data
    
                // Loop through each line in the CSV file
                $is_header = 0; //header si 0 and 1
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    if ($is_header > 1) {
                        if (!empty(array_filter($data))) {
                            $csvData[] = $data;
                        }
                    }
                    $is_header++;
                }

                if ($csvData){
                    $buffer = [];
                    $employeeIds = [];
                    $batchSize = 5000;

                    foreach ($csvData as $row) {
                        $buffer[] = $row;
                        $employeeIds[] = $row[1]; // Assuming index 1 is employeeid

                        $worker_ids = [3, 4, 5, 6];

                        // Get current usage counts for each worker_id under the same base_id
                        $this->CI->db->select('worker_id, COUNT(*) as count');
                        $this->CI->db->where('base_id', $uploadJob->id);
                        $this->CI->db->where_in('worker_id', $worker_ids);
                        $this->CI->db->group_by('worker_id');
                        $query = $this->CI->db->get('upload_list_data');

                        $usage_counts = array_fill_keys($worker_ids, 0);
                        foreach ($query->result_array() as $row) {
                            $usage_counts[(int)$row['worker_id']] = (int)$row['count'];
                        }

                        // Find the worker_id with the lowest usage count (round-robin fallback to 3)
                        asort($usage_counts); // Sort by count ascending
                        reset($usage_counts); // move pointer to the first element
                        $assign_worker = key($usage_counts); // get the first key

                        // When buffer reaches 100, insert it
                        if (count($buffer) === $batchSize) {
                            if ($assign_worker !== null) {
                                $insert_data = array(
                                    'base_id'       => $uploadJob->id,
                                    'employeeid'    => implode(',', $employeeIds),
                                    'employeename'  => null,
                                    'emp_data'      => json_encode($buffer),
                                    'total'         => count($buffer),
                                    'user'          => $uploadJob->user,
                                    'worker_id'     => $assign_worker
                                );

                                $this->CI->db->insert('upload_list_data', $insert_data);
                            } else {
                            }


                            // Reset for next batch
                            $buffer = [];
                            $employeeIds = [];
                        }
                    }

                    // Insert remaining rows if less than 100 (final batch)
                    if (count($buffer) > 0) {
                        $insert_data = array(
                            'base_id'       => $uploadJob->id,
                            'employeeid'    => implode(',', $employeeIds),
                            'employeename'  => null,
                            'emp_data'      => json_encode($buffer),
                            'total'         => count($buffer),
                            'user'          => $uploadJob->user,
                            'worker_id'     => $assign_worker
                        );

                        $this->CI->db->insert('upload_list_data', $insert_data);
                    }
                }else{
                    $this->worker_model->updateUploadStatus($uploadJob->id, 'done');
                }
    
            } else {
                $this->worker_model->updateUploadStatus($uploadJob->id, 'failed');
            }
        } else {
            $this->worker_model->updateUploadStatus($uploadJob->id, 'failed');
        }


    }

}