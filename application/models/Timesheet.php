<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Timesheet extends CI_Model {
  function logmenow($uid,$logtype,$secs,$macadd){
      $this->db->query("CALL prc_timesheet_set('$uid','".date("Y-m-d H:i:s")."','{$macadd}','$logtype','{$secs}',@res,@fullname,@message,@timed,@submacadd,@userid)");
      # echo "CALL prc_timesheet_set('$uid','".date("Y-m-d H:i:s")."','".$this->extras->returnmacaddress()."','$logtype','{$secs}',@res,@fullname,@message,@timed)";
      $q = $this->db->query("select @res as RESULT_NUM,@fullname as FULLNAME_SET,@message as MESSAGE_SET,@timed as TIME_LEFT,@submacadd as TIME_LEFT,@userid as USERID");
      return $q->row(0);
  }
  function machinedisplaystatus($macadd=""){
      $return = "";
      $mstat = "";
      $mdescription = "";
      $this->db->select("mac_add,type,description,mac_status");
      $this->db->from("machine_setup");
      $this->db->where("mac_add",$macadd);
        $q = $this->db->get();
        
        if($q->num_rows()>0){
         $mstat = $q->row(0)->type; 
         $mdescription= $q->row(0)->description;
        }
        switch($mstat){
           case 'OUT':
             $return = "This station is for LOGGING OUT only.";
           break;
           case 'IN':
             $return = "This station is for LOGGING IN only.";
           break;
           case 'IN-OUT':
             $return = "";
           break;
           default:
             $return = "This station is not registered.";
           break; 
        }
       return array($mstat,$return,$mdescription); 
  }
  function loglist_user($limit,$date,$macadd){
    # echo "pasok";
      $this->db->select("a.userid,a.logtime,a.log_type,b.lname,b.fname,b.mname");

      $this->db->from("timesheet_trail a");
      $this->db->join("employee b","a.userid=b.employeecode");
      $this->db->order_by("a.logtime","DESC");
      
      
      if($date){
      #  $this->db->where("DATE(a.logtime)",$date);
      }
      # $this->db->limit($limit);
      # $q = $this->db->get();
                            
      $q = $this->db->query("SELECT a.userid,a.logtime,a.log_type,a.lname,a.fname,a.mname,a.mac_add FROM 
                            (SELECT a.userid,a.logtime,a.log_type,b.lname,b.fname,b.mname,a.mac_add FROM timesheet_trail a INNER JOIN employee b ON a.userid=b.employeeid WHERE a.userid<>''".($date?" AND DATE(a.logtime)='{$date}'":"").($macadd?" AND a.mac_add='{$macadd}'":"")." 
                            UNION 
                            SELECT a.userid,a.logtime,a.log_type,b.lname,b.fname,b.mname,a.mac_add FROM timesheet_trail a INNER JOIN student b ON a.userid=b.studentid WHERE a.userid<>''".($date?" AND DATE(a.logtime)='{$date}'":"").($macadd?" AND a.mac_add='{$macadd}'":"").")
                            AS a
                            order by a.logtime DESC LIMIT $limit")->result();
      return $q; 
  }
  function csvatt($data){
        if($data){
            $inserted = 0;
            $ins = $this->db->insert('timesheet', $data);
            if($ins)    $inserted++;
            return $inserted;
        }
    }
	
	//ADDED 07-03-2017
	function csvUploaded($data){
        if($data){
            $query = $this->db->insert('timesheet_uploaded', $data);
        }
    }
	
  function csvsched($data,$display=false){
        if($data){
            $inserted = 0;
            if($this->checkemp($data['employeeid'])){
                if($display){
                    $this->db->query("DELETE FROM employee_schedule WHERE employeeid='{$data['employeeid']}'");
                    $this->db->insert('employee_schedule', $data);  
                }else{
                    $ins = $this->db->insert('employee_schedule_history', $data);
                    if($ins)     $inserted++;
                }
            }
            return $inserted;
        }
    }
  function dow($day = "",$idx = false){
        if($idx)
            $arr = array("Monday"=>"1","Tuesday"=>"2","Wednesday"=>"3","Thursday"=>"4","Friday"=>"5","Saturday"=>"6");
        else
            $arr = array("Monday"=>"M","Tuesday"=>"T","Wednesday"=>"W","Thursday"=>"TH","Friday"=>"F","Saturday"=>"S");
        return $arr[$day];
    }
  function checkemp($eid = ""){
    $query = $this->db->query("SELECT * FROM employee WHERE isactive=1 AND employeeid='$eid'");
    if($query->num_rows() > 0)
        return true;
    else
        return false;
  }
  
  function userlog($data=""){
    $job        = $data['job'];
    $userid     = $data['userid'];
    $machine_id = $data['macid'];
    $ltype      = $data['ltype'];
    $localtime  = $data['localtime'];
    
    // Machine Type
    if(!$ltype){  
        $query = $this->db->query("select type from machine_setup where mac_add='{$machine_id}'");
        if($query->num_rows() > 0) $ltype = $query->row(0)->type;  
    }
    
    // PROCESS
    $this->db->query("CALL prc_timesheet_set('$userid','".date("Y-m-d H:i:s")."','{$machine_id}','{$ltype}',0,@res,@fullname,@message,@timed,@submacadd,@userid)");
    
    // RESULT
    $query = $this->db->query("select IFNULL(@res,'') as RESULT_NUM,IFNULL(@fullname,'') as FULLNAME_SET,IFNULL(@message,'') as MESSAGE_SET,IFNULL(@timed,'') as TIME_LEFT,IFNULL(@submacadd,'') as SUBMACADD,IFNULL(@userid,'') as USERID");
    foreach($query->result() as $row){
        $status        = $row->RESULT_NUM;
        $name          = mb_convert_encoding($row->FULLNAME_SET,"UTF-8");
        $user_message  = $row->MESSAGE_SET;
        $submachine_id = $row->SUBMACADD;
        $userid        = $row->USERID;
    }
    return json_encode(array('type'=>'machine', 'machineid'=>$machine_id, 'submachineid'=>$submachine_id, 'userid'=>$userid, 'name'=>$name, 'message'=>$user_message, 'status'=>$status));;
  }


  function userlog1($data=""){
    $job        = $data['job'];
    $userid     = $data['userid'];
    $machine_id = $data['macid'];
    $ltype      = $data['ltype'];
    $localtime  = $data['localtime'];
    $username   = $this->session->userdata('username');
    $machine_id = '';

    ///< validation if no user is logged in
    if(!$username) return json_encode(array('type'=>'machine', 'machineid'=>$machine_id, 'submachineid'=>'', 'userid'=>$userid, 'name'=>'', 'message'=>'Gate is inactive. Please refresh page.', 'status'=>'5'));

    // Machine Type
    if(!$ltype){  
        $query = $this->db->query("select type from machine_setup where mac_add='{$machine_id}'");
        if($query->num_rows() > 0) $ltype = $query->row(0)->type;  
    }
	
    $isEmployee = $is_teaching = false;
    $tbl_timesheet_trail = 'timesheet_trail';
  	$log_time = $employeeid = $bdate = $bdaymsg = $studentid = '';
  	$employeeid_q = $this->db->query("SELECT employeeid, bdate, teachingtype FROM employee WHERE (employeecode='$userid' OR employeeid='$userid' OR REPLACE(employeeid,'-','')='$userid')");

  	if($employeeid_q->num_rows() >0){
      $isEmployee = true;
      $employeeid = $employeeid_q->row(0)->employeeid;
      $bdate      = $employeeid_q->row(0)->bdate;
      if($bdate) $bdaymsg = (date('m-d',strtotime($bdate)) == date('m-d')) ? ' Happy Birthday!  ' : '';
      if($employeeid_q->row(0)->teachingtype == "teaching") $is_teaching = true;
    }else{
      $tbl_timesheet_trail = 'timesheet_trail_student';

        $stud_q = $this->db->query("SELECT studentid FROM student WHERE (studentcode='$userid' OR studentid='$userid' OR REPLACE(studentid,'-','')='$userid')");

        if($stud_q->num_rows() >0){
          $studentid = $stud_q->row(0)->studentid;
        }
    }

    $temp_id = $isEmployee ? $employeeid : $studentid;

    $login = $this->db->query("SELECT DATE_FORMAT(logtime,'%H:%i:%s') AS login FROM $tbl_timesheet_trail WHERE userid='$temp_id' AND log_type='IN' AND DATE_FORMAT(logtime,'%Y-%m-%d')='".date("Y-m-d")."' ORDER BY logtime DESC LIMIT 1");

    if($login->num_rows() > 0){
      $log_time = $login->row(0)->login;
    } 

    
    # for ica-hyperion 22101
    $gate_type = ($isEmployee) ? (($is_teaching) ? "ET" : "ENT") : "ST";
    $allow_gate_config = array(
      "ST" => "Student",
      "ET" => "Employee (Teaching)",
      "ENT" => "Employee (Non Teaching)"
    );
    
    $allow_arr = array();
    $q_gate_allow = $this->db->query("SELECT gate_tap_allow FROM user_info WHERE username='{$this->session->userdata('username')}'")->result();
    foreach ($q_gate_allow as $row) $allow_arr = explode(",", $row->gate_tap_allow);

    if(!in_array($gate_type, $allow_arr)) return json_encode(array('type'=>'machine', 'machineid'=>$machine_id, 'submachineid'=>'', 'userid'=>$userid, 'name'=>'', 'message'=> $allow_gate_config[$gate_type] .' is not allowed to access this gate', 'status'=>'5'));
    # end for ica-hyperion 22101

    if($isEmployee) return $this->logEmployee($log_time,$machine_id,$ltype,$username,$userid,$bdaymsg);
    else return $this->logStudent($log_time,$machine_id,$ltype,$username,$userid);

  }

  ///<----------------------------------employee---------------------------------------------------------
  function logEmployee($log_time='',$machine_id='',$ltype='',$username='',$userid='',$bdaymsg=''){

    /*delete last day timesheet_trail data*/

    $old_userid = $old_logtype = $old_date = $old_time = '';
    $timein = $timeout = '';
    list($login_date, $login_time) = explode(" ", $log_time);
    $check_last_log = $this->db->query("SELECT DATE_FORMAT(logtime, '%Y-%m-%d') AS lastlog FROM timesheet_trail ORDER BY logtime DESC LIMIT 1")->row()->lastlog;
    if($check_last_log != $login_date){
      $all_lastlog = $this->db->query("SELECT userid,DATE_FORMAT(logtime, '%Y-%m-%d') AS DATE, DATE_FORMAT(logtime, '%H:%i:%s') AS TIME, log_type FROM timesheet_trail WHERE DATE_FORMAT(logtime, '%Y-%m-%d') = '$check_last_log' ORDER BY userid,logtime")->result_array();
      foreach($all_lastlog as $value){
        if($value['userid'] == $old_userid && $old_logtype != $value['log_type']){

           $timein = $old_date." ".$old_time;
           $timeout = $value['DATE']." ".$value['TIME'];

           $this->db->query("DELETE FROM timesheet_trail WHERE logtime = '$timein' AND userid = '{$value['userid']}' ");
           $this->db->query("DELETE FROM timesheet_trail WHERE logtime = '$timeout' AND userid = '{$value['userid']}' ");
        }
      $old_userid = $value['userid'];
      $old_logtype = $value['log_type'];
      $old_date = $value['DATE'];
      $old_time = $value['TIME'];
      }
    }

    /*end*/

    /*if($log_time){
      ///< checking logout 1 min after login
      if( ( strtotime(date("H:i:s",time())) - strtotime($log_time) ) <= 60 ){
        return json_encode(array('type'=>'machine', 'machineid'=>$machine_id, 'submachineid'=>'', 'userid'=>$userid, 'name'=>'', 'message'=>$bdaymsg.'You already tapped your card. Please try after 1 minute.', 'status'=>'5'));
      }
    }*/

    // PROCESS
    $this->db->query("CALL prc_timesheet_set_wd_username('$userid','". $log_time ."','{$machine_id}','{$ltype}',0,'$username',@res,@fullname,@message,@timed,@submacadd,@userid)");

    // RESULT
    $query = $this->db->query("select IFNULL(@res,'') as RESULT_NUM,IFNULL(@fullname,'') as FULLNAME_SET,IFNULL(@message,'') as MESSAGE_SET,IFNULL(@timed,'') as TIME_LEFT,IFNULL(@submacadd,'') as SUBMACADD,IFNULL(@userid,'') as USERID");
    foreach($query->result() as $row){
        $status        = $row->RESULT_NUM;
        $name          = mb_convert_encoding($row->FULLNAME_SET,"UTF-8");
        $user_message  = $row->MESSAGE_SET;
        $submachine_id = $row->SUBMACADD;
        $userid        = $row->USERID;
    }
    return json_encode(array('type'=>'machine', 'machineid'=>$machine_id, 'submachineid'=>$submachine_id, 'userid'=>$userid, 'name'=>$name, 'message'=>$bdaymsg.$user_message, 'status'=>$status));
  }

  ///<----------------------------------student---------------------------------------------------------
  function logStudent($log_time='',$machine_id='',$ltype='',$username='',$userid=''){

    /*if($log_time){
      ///< checking logout 2 mins after login
      if( ( strtotime(date("H:i:s",time())) - strtotime($log_time) ) <= 120 ){
        return json_encode(array('type'=>'machine', 'machineid'=>$machine_id, 'submachineid'=>'', 'userid'=>$userid, 'name'=>'', 'message'=>'You already tapped your card. Please try after 2 minutes.', 'status'=>'5'));
      }
    }*/

    // PROCESS
    $this->db->query("CALL prc_timesheet_set_student('$userid','". $log_time ."','{$machine_id}','{$ltype}',0,'$username',@res,@fullname,@message,@timed,@submacadd,@userid)");

    // RESULT
    $query = $this->db->query("select IFNULL(@res,'') as RESULT_NUM,IFNULL(@fullname,'') as FULLNAME_SET,IFNULL(@message,'') as MESSAGE_SET,IFNULL(@timed,'') as TIME_LEFT,IFNULL(@submacadd,'') as SUBMACADD,IFNULL(@userid,'') as USERID");
    foreach($query->result() as $row){
        $status        = $row->RESULT_NUM;
        $name          = mb_convert_encoding($row->FULLNAME_SET,"UTF-8");
        $user_message  = $row->MESSAGE_SET;
        $submachine_id = $row->SUBMACADD;
        $userid        = $row->USERID;
        $user_logtype = explode(" ",$user_message);
        if(in_array("IN", $user_logtype)){
            $this->db->query("INSERT INTO timesheet_history_student (userid, timein, mac_add, username) VALUES ('$userid', '".date("Y-m-d H:i:s")."','$machine_id', 'EAST')");
        }
    }
    return json_encode(array('type'=>'machine', 'machineid'=>$machine_id, 'submachineid'=>$submachine_id, 'userid'=>$userid, 'name'=>$name, 'message'=>$user_message, 'status'=>$status));
  }



  function loglist_user1($limit,$date,$macadd){
      $this->user->getGateUsername();
      $username = $this->session->userdata('username');
      
                            
      $q = $this->db->query("SELECT a.userid,a.logtime,a.log_type,a.lname,a.fname,a.mname,a.mac_add ,a.username FROM 
                            (SELECT a.userid,a.logtime,a.log_type,b.lname,b.fname,b.mname,a.mac_add ,a.username FROM timesheet_trail a INNER JOIN employee b ON a.userid=b.employeeid WHERE a.userid<>''".($date?" AND DATE(a.logtime)='{$date}'":"").($macadd?" AND a.mac_add='{$macadd}'":"")." AND a.username='$username'  
                            UNION 
                            SELECT a.userid,a.logtime,a.log_type,b.lname,b.fname,b.mname,a.mac_add ,a.username FROM timesheet_trail_student a INNER JOIN student b ON a.userid=b.studentid WHERE a.userid<>''".($date?" AND DATE(a.logtime)='{$date}'":"").($macadd?" AND a.mac_add='{$macadd}'":"")."  AND a.username='$username' )
                            AS a
                            order by a.logtime DESC LIMIT $limit")->result();
      return $q; 
  }

  function employeeTimeinToday($employeeid, $datetoday){
    $q_att = $this->db->query("SELECT TIME(localtimein) as timein FROM timesheet_trail WHERE userid = '$employeeid' AND DATE(localtimein) = '$datetoday' ");
    if($q_att->num_rows() > 0) return $q_att->row()->timein;
    else return false;
  }

  function getTimesheetTrail($dfrom, $dto, $where_clause){
        $data = array();
        $last_emp = $last_terminal = "";
        $timesheet_record = $this->db->query("SELECT DATE(datecreated) AS date, fullname, user_id, username, action FROM login_attempts WHERE DATE(datecreated) BETWEEN '$dfrom' AND '$dto' $where_clause AND status = 'success' ORDER BY user_id, date ");
        if($timesheet_record->num_rows() > 0){  
            foreach($timesheet_record->result_array() as $row){
                if($last_emp != $row["user_id"]){
                    $last_terminal = $row["username"];
                }else{
                    if($last_terminal != $row["username"]){
                        $data[$row["user_id"]][$row["date"]]["fullname"] = $row["fullname"];
                        $data[$row["user_id"]][$row["date"]]["in"] = $last_terminal;
                        $data[$row["user_id"]][$row["date"]]["out"] = $row["username"];
                    }
                }

                $last_emp = $row["user_id"];
                $last_terminal = $row["username"];
            }
        }

        return $data;
    }
  
  function getNooutData($employeeid, $date){
    $q_timesheet = $this->db->query("SELECT TIME(logtime) AS logtime FROM timesheet_noout WHERE userid = '$employeeid' AND DATE(logtime) = '$date' ");
    if($q_timesheet->num_rows() > 0) return $q_timesheet->row()->logtime;
    else return false;
  }

  function getAbsentTimein($employeeid, $date){
    $q_timesheet = $this->db->query("SELECT TIME(timein) AS logtime FROM timesheet WHERE userid = '$employeeid' AND DATE(timein) = '$date' ");
    if($q_timesheet->num_rows() > 0) return $q_timesheet->row()->logtime;
    else return false;
  }

  function getAbsentTimeout($employeeid, $date){
    $q_timesheet = $this->db->query("SELECT TIME(timeout) AS logtime FROM timesheet WHERE userid = '$employeeid' AND DATE(timeout) = '$date' ");
    if($q_timesheet->num_rows() > 0) return $q_timesheet->row()->logtime;
    else return false;
  }

  function presentEmployeeToday(){
    return $this->db->query("SELECT * FROM facial_Log WHERE DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = CURDATE() GROUP BY employeeid");
  }

  function attendanceDataForGraph(){
    $datefrom = date("Y-m-d");
    $date = new DateTime($datefrom);
    $date->modify('-5 months');
    $dateto =  $date->format('Y-m-d'); 
    return $this->db->query("SELECT DATE_FORMAT(DATE(timein), '%Y-%m') AS MONTH, COUNT(*) AS total_rows FROM timesheet WHERE DATE(timein) BETWEEN '$dateto' AND '$datefrom' GROUP BY MONTH ORDER BY MONTH ASC");
  }

  public function saveUploadedLogs($log_data){
    return $this->db->insert("timesheet", $log_data);
  }

  public function reprocessFacialLogByScheduleTeaching($emp_id, $date) {
    // store used pair of logs
    $used_time = array();

    // Get employee schedule
    $emp_sched = $this->attcompute->displaySched($emp_id, $date);

    if ($emp_sched->num_rows() > 0) {
        // Delete all timesheet data from facial
        $checklog_q = $this->db->query("DELETE FROM timesheet WHERE userid = '$emp_id' AND DATE(timein) = '$date' AND otype = 'Facial'");
        $first_sched = true;
        $last_timein = $last_timeout = "";
        $has_last_timein = $has_last_timeout = "";
        $emp_schedule = $emp_sched->result_array();
        
        foreach ($emp_schedule as $key => $rsched) {
            $timein = "";
            $timeout = "";
            $device_key = "";
            
            $sched_start = $date . " " . $rsched["starttime"];
            $sched_end = $date . " " . $rsched["endtime"];
            $early_dismissal = $date . " " . $rsched["early_dismissal"];
            $absent_start = $date . " " . $rsched["absent_start"];
            
            $prior_sched_start = isset($emp_schedule[$key + 1]) 
                ? $date . " " . $emp_schedule[$key + 1]["starttime"] 
                : "";

            $last_sched_end = isset($emp_schedule[$key - 1]) 
                ? $date . " " . $emp_schedule[$key - 1]["endtime"] 
                : "";

            $order_by = $first_sched ? " ORDER BY time ASC" : " ORDER BY time_diff ASC";
            $order_by_timeout = $first_sched ? " ORDER BY time_diff ASC" : " ORDER BY time_diff DESC";
            
            // Consolidated query for time-in and time-out logs
            $queries = [
                'timein' => "
                    SELECT 
                        FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime,
                        ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_start'))) AS time_diff,
                        deviceKey
                    FROM facial_Log
                    WHERE employeeid = '$emp_id'
                      AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date'
                      ". ($first_sched ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$sched_end'" : " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$sched_start'") ."
                      ". ($first_sched ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$absent_start'" : " ") ."
                      " . ($last_timeout ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$last_timeout'" : "") . "
                      " . ($last_sched_end ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$last_sched_end'" : "") . "
                      AND FROM_UNIXTIME(FLOOR(`time` / 1000)) != '{$date} 00:00:00' 
                    $order_by
                    LIMIT 1
                ",
                'timeout' => "
                    SELECT 
                        FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime,
                        ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_end'))) AS time_diff,
                        deviceKey
                    FROM facial_Log
                    WHERE employeeid = '$emp_id'
                      AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date'
                      " . ($first_sched ? " AND LEAST(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_end') <= '$sched_end'" : " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$sched_end'") ."
                      " . ($prior_sched_start ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$prior_sched_start'" : "") . "
                      AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$early_dismissal'
                      AND FROM_UNIXTIME(FLOOR(`time` / 1000)) != '{$date} 00:00:00' 
                    $order_by_timeout
                    LIMIT 1
                "
            ];
            

            // Execute and fetch time-in log
            $records = $this->db->query($queries['timein']);
            // echo $this->db->last_query(); die;
            if ($records->num_rows() > 0) {
                $row = $records->row();
                $timein = $row->logtime;
            } else {
                // Fallback query for time pm if no result found
                if($first_sched === false){
                  $fallback_query = "
                      SELECT 
                          FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime,
                          ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_start'))) AS time_diff,
                          deviceKey
                      FROM facial_Log
                      WHERE employeeid = '$emp_id'
                        AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date'
                        AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$sched_start'
                        " . ($prior_sched_start ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) < '$prior_sched_start'" : "") . "
                      $order_by
                      LIMIT 1
                  ";

                  $records = $this->db->query($fallback_query);
                  if ($records->num_rows() > 0) {
                      $row = $records->row();
                      $timein = $row->logtime;
                      $device_key = $row->deviceKey;
                  }
                }
            }

            // Execute and fetch time-out log
            $records = $this->db->query($queries['timeout']);
            
            // echo $this->db->last_query(); die;
            if ($records->num_rows() > 0) {
                $row = $records->row();
                $timeout = $row->logtime;
                $device_key = $row->deviceKey;
            } else {
                // Fallback query for timeout if no result found
                $fallback_query = "
                    SELECT 
                        FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime,
                        ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_end'))) AS time_diff,
                        deviceKey
                    FROM facial_Log
                    WHERE employeeid = '$emp_id'
                      AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date'
                    ". ($first_sched === false ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$sched_end'" : "  AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$sched_end'")."
                    " . ($prior_sched_start ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) > '$prior_sched_start'" : "") . "
                    ORDER BY time_diff ASC
                    LIMIT 1
                ";
                $records = $this->db->query($fallback_query);
                
                if ($records->num_rows() > 0) {
                    $row = $records->row();
                    $timeout = $row->logtime;
                    $device_key = $row->deviceKey;
                }
                
                // CHECK IF HAS UPCOMING LOGS
                $upcomingSchedule = $emp_schedule[$key + 1] ?? '';
                if($upcomingSchedule && ($timein == "" || $timeout == "")){
                  $login = $this->timesheet->currentLogtimeAMSchedule(
                      $emp_id, 
                      $date, 
                      $upcomingSchedule->stime ?? '', 
                      $upcomingSchedule->absent_start ?? ''
                  );

                  $logout = $this->timesheet->currentLogtimePMSchedule(
                      $emp_id, 
                      $date, 
                      $upcomingSchedule->etime ?? '', 
                      $upcomingSchedule->early_dismissal ?? ''
                  );

                }
                
            }

            // remove timein if same value on timeout
            if($timein === $timeout){
              $timein = "";
            }

            if($has_last_timein != "" && $has_last_timeout == ""){
              if($timein == "" && $timeout != ""){
                $timein = $has_last_timein;
              }
            }

            if($has_last_timein == "" && $has_last_timeout != ""){
                $timein = $has_last_timeout;
            }

            // Globals::pd(array($timein, $timeout));
            // Check for existing timesheet record
            $is_exist = $this->db->query("
                SELECT * 
                FROM timesheet 
                WHERE userid = '$emp_id' 
                  AND (timein = '$timein' OR timeout = '$timeout')
            ")->num_rows();

            // Insert timesheet record if no existing entry
            if ($is_exist == 0 && $timein && $timeout) {
              if($this->time->areTimesValid($timein, $timeout)){
                if($timein != $timeout){
                    $t_data = [
                        "timein" => $timein,
                        "timeout" => $timeout,
                        "userid" => $emp_id,
                        "otype" => "Facial",
                        "type" => "EDIT",
                        "addedby" => $device_key,
                    ];
                    $this->db->insert("timesheet", $t_data);
                  }
              }
            
              $last_timein = $timein;
              $last_timeout = $timeout;
            }

            $has_last_timein = $timein;
            $has_last_timeout = $timeout;
            
            $first_sched = false;
        }
      }

  }

  public function reprocessFacialLogBySchedule($emp_id, $date) {
    // Get employee schedule
    $emp_sched = $this->attcompute->displaySched($emp_id, $date);

    if ($emp_sched->num_rows() > 0) {
        // Delete all timesheet data from facial
        $checklog_q = $this->db->query("DELETE FROM timesheet WHERE userid = '$emp_id' AND DATE(timein) = '$date' AND otype = 'Facial'");
        $first_sched = true;
        $last_timein = $last_timeout = "";
        $has_last_timein = $has_last_timeout = "";
        $emp_schedule = $emp_sched->result_array();
        
        foreach ($emp_schedule as $key => $rsched) {
            $timein = "";
            $timeout = "";
            $device_key = "";
            
            $sched_start = $date . " " . $rsched["starttime"];
            $sched_end = $date . " " . $rsched["endtime"];
            $early_dismissal = $date . " " . $rsched["early_dismissal"];
            $absent_start = $date . " " . $rsched["absent_start"];
            
            $prior_sched_start = isset($emp_schedule[$key + 1]) 
                ? $date . " " . $emp_schedule[$key + 1]["starttime"] 
                : "";

            $last_sched_end = isset($emp_schedule[$key - 1]) 
                ? $date . " " . $emp_schedule[$key - 1]["endtime"] 
                : "";

            $order_by = $first_sched ? " ORDER BY time ASC" : " ORDER BY time_diff ASC";
            $order_by_timeout = $first_sched ? " ORDER BY time_diff ASC" : " ORDER BY time_diff DESC";
            
            // Consolidated query for time-in and time-out logs
            $queries = [
                'timein' => "
                    SELECT 
                        FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime,
                        ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_start'))) AS time_diff,
                        deviceKey
                    FROM facial_Log
                    WHERE employeeid = '$emp_id'
                      AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date'
                      ". ($first_sched ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$sched_end'" : " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) < '$sched_start'") ."
                      ". ($first_sched ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$absent_start'" : " ") ."
                      " . ($last_timeout ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) > '$last_timeout'" : "") . "
                      " . ($last_sched_end ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) > '$last_sched_end'" : "") . "
                      AND FROM_UNIXTIME(FLOOR(`time` / 1000)) != '{$date} 00:00:00' 
                    $order_by
                    LIMIT 1
                ",
                'timeout' => "
                    SELECT 
                        FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime,
                        ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_end'))) AS time_diff,
                        deviceKey
                    FROM facial_Log
                    WHERE employeeid = '$emp_id'
                      AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date'
                      " . ($first_sched ? " AND LEAST(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_end') < '$sched_end'" : " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$sched_end'") ."
                      " . ($prior_sched_start ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$prior_sched_start'" : "") . "
                      AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$early_dismissal'
                      AND FROM_UNIXTIME(FLOOR(`time` / 1000)) != '{$date} 00:00:00' 
                    $order_by_timeout
                    LIMIT 1
                "
            ];
            

            // Execute and fetch time-in log
            $records = $this->db->query($queries['timein']);
            // echo $this->db->last_query(); die;
            if ($records->num_rows() > 0) {
                $row = $records->row();
                $timein = $row->logtime;
            } else {
                // Fallback query for time pm if no result found
                if($first_sched === false){
                  $fallback_query = "
                      SELECT 
                          FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime,
                          ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_start'))) AS time_diff,
                          deviceKey
                      FROM facial_Log
                      WHERE employeeid = '$emp_id'
                        AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date'
                        AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$sched_start'
                        " . ($prior_sched_start ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) < '$prior_sched_start'" : "") . "
                      $order_by
                      LIMIT 1
                  ";

                  $records = $this->db->query($fallback_query);
                  if ($records->num_rows() > 0) {
                      $row = $records->row();
                      $timein = $row->logtime;
                      $device_key = $row->deviceKey;
                  }
                }
            }

            // Execute and fetch time-out log
            $records = $this->db->query($queries['timeout']);
            
            // echo $this->db->last_query(); die;
            if ($records->num_rows() > 0) {
                $row = $records->row();
                $timeout = $row->logtime;
                $device_key = $row->deviceKey;
            } else {
                // Fallback query for timeout if no result found
                $fallback_query = "
                    SELECT 
                        FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime,
                        ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_end'))) AS time_diff,
                        deviceKey
                    FROM facial_Log
                    WHERE employeeid = '$emp_id'
                      AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date'
                    ". ($first_sched === false ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$sched_end'" : "  AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$sched_end'")."
                    " . ($prior_sched_start ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) < '$prior_sched_start'" : "") . "
                    ORDER BY time_diff ASC
                    LIMIT 1
                ";
                $records = $this->db->query($fallback_query);
                
                if ($records->num_rows() > 0) {
                    $row = $records->row();
                    $timeout = $row->logtime;
                    $device_key = $row->deviceKey;
                }
                
                // CHECK IF HAS UPCOMING LOGS
                $upcomingSchedule = $emp_schedule[$key + 1] ?? '';
                if($upcomingSchedule && ($timein == "" || $timeout == "")){
                  $login = $this->timesheet->currentLogtimeAMSchedule(
                      $emp_id, 
                      $date, 
                      $upcomingSchedule->stime ?? '', 
                      $upcomingSchedule->absent_start ?? ''
                  );

                  $logout = $this->timesheet->currentLogtimePMSchedule(
                      $emp_id, 
                      $date, 
                      $upcomingSchedule->etime ?? '', 
                      $upcomingSchedule->early_dismissal ?? ''
                  );

                }
                
            }

            // remove timein if same value on timeout
            if($timein === $timeout){
              $timein = "";
            }

            if($has_last_timein != "" && $has_last_timeout == ""){
              if($timein == "" && $timeout != ""){
                $timein = $has_last_timein;
              }
            }

            if($has_last_timein == "" && $has_last_timeout != ""){
                $timein = $has_last_timeout;
            }

            // Globals::pd(array($timein, $timeout));
            // Check for existing timesheet record
            $is_exist = $this->db->query("
                SELECT * 
                FROM timesheet 
                WHERE userid = '$emp_id' 
                  AND (timein = '$timein' OR timeout = '$timein' OR timein = '$timeout' OR timeout = '$timeout')
            ")->num_rows();

            // Insert timesheet record if no existing entry
            if ($is_exist == 0 && $timein && $timeout) {
              if($this->time->areTimesValid($timein, $timeout)){
                if($timein != $timeout){
                    $t_data = [
                        "timein" => $timein,
                        "timeout" => $timeout,
                        "userid" => $emp_id,
                        "otype" => "Facial",
                        "type" => "EDIT",
                        "addedby" => $device_key,
                    ];
                    $this->db->insert("timesheet", $t_data);
                  }
              }
            
              $last_timein = $timein;
              $last_timeout = $timeout;
            }

            $has_last_timein = $timein;
            $has_last_timeout = $timeout;
            
            $first_sched = false;
        }
      }

  }

  public function currentLogtimeAMSchedule($emp_id, $date, $schedule, $early_dismissal, $used_time=array()){
      $sched_with_date = $date." ".$schedule;
      $early_d_with_date = $date." ".$early_dismissal;
      $q_logs = $this->db->query("SELECT 
                                      FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime
                                      FROM
                                      facial_Log 
                                      WHERE employeeid = '$emp_id' 
                                      AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date' 
                                      AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$sched_with_date'
                                      ORDER BY ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_with_date'))) ASC 
                                      LIMIT 1");
      if($q_logs->num_rows() == 0){
        $q_logs = $this->db->query("SELECT 
                            FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime
                            FROM
                            facial_Log 
                            WHERE employeeid = '$emp_id' 
                            AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date' 
                            AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$sched_with_date'
                            AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$early_d_with_date'
                            ORDER BY ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_with_date'))) ASC 
                            LIMIT 1 ");
        if($q_logs->num_rows() > 0){
          return $q_logs->row()->logtime;
        }
    }else{
        return $q_logs->row()->logtime;
    }
  }

  public function currentLogtimePMSchedule($emp_id, $date, $schedule, $early_dismissal, $used_time=array(), $prior_sched_start="", $prior_absent_start=""){
      $sched_with_date = $date." ".$schedule;
      $early_d_with_date = $date." ".$early_dismissal;

      $where_clause = "";
      if($prior_sched_start){
        $prior_sched_start = $date." ".$prior_sched_start;
        $where_clause = " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) < '$prior_sched_start'";
      }
      
      $q_logs = $this->db->query("SELECT 
                                FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime
                                FROM
                                facial_Log 
                                WHERE employeeid = '$emp_id' 
                                AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date' 
                                AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$sched_with_date'
                                AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$early_d_with_date'
                                AND FROM_UNIXTIME(FLOOR(`time` / 1000)) NOT IN (SELECT timein FROM timesheet WHERE userid = '$emp_id' AND DATE(timein) = '$date' AND otype = 'Facial')
                                $where_clause
                                ORDER BY ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_with_date'))) ASC 
                                LIMIT 1 ");
      if($q_logs->num_rows() == 0){
          
          $q_logs = $this->db->query("SELECT 
                              FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime
                              FROM
                              facial_Log 
                              WHERE employeeid = '$emp_id' 
                              AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date' 
                              AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$sched_with_date'
                              AND FROM_UNIXTIME(FLOOR(`time` / 1000)) NOT IN (SELECT timein FROM timesheet WHERE userid = '$emp_id' AND DATE(timein) = '$date' AND otype = 'Facial')
                              $where_clause
                              ORDER BY ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_with_date'))) ASC 
                              LIMIT 1 ");
          if($q_logs->num_rows() > 0){
            return $q_logs->row()->logtime;
          }
      }else{
          return $q_logs->row()->logtime;
      }

      return false;
  }
  
  public function attendanceReprocessFacialLogBySchedule($emp_id, $date) {
    // Get employee schedule
    $emp_sched = $this->attcompute->displaySched($emp_id, $date);
    $t_data = [];
    if ($emp_sched->num_rows() > 0) {
        // Delete all timesheet data from facial
        $checklog_q = $this->db->query("DELETE FROM timesheet WHERE userid = '$emp_id' AND DATE(timein) = '$date' AND otype = 'Facial'");
        $first_sched = true;
        $last_timein = $last_timeout = "";
        $has_last_timein = $has_last_timeout = "";
        $emp_schedule = $emp_sched->result_array();
        
        foreach ($emp_schedule as $key => $rsched) {
            $timein = "";
            $timeout = "";
            $device_key = "";
            
            $sched_start = $date . " " . $rsched["starttime"];
            $sched_end = $date . " " . $rsched["endtime"];
            $early_dismissal = $date . " " . $rsched["early_dismissal"];
            $absent_start = $date . " " . $rsched["absent_start"];
            
            $prior_sched_start = isset($emp_schedule[$key + 1]) 
                ? $date . " " . $emp_schedule[$key + 1]["starttime"] 
                : "";

            $last_sched_end = isset($emp_schedule[$key - 1]) 
                ? $date . " " . $emp_schedule[$key - 1]["endtime"] 
                : "";

            $order_by = $first_sched ? " ORDER BY time ASC" : " ORDER BY time_diff ASC";
            $order_by_timeout = $first_sched ? " ORDER BY time_diff ASC" : " ORDER BY time_diff DESC";

            // Consolidated query for time-in and time-out logs
            $queries = [
                'timein' => "
                    SELECT 
                        FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime,
                        ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_start'))) AS time_diff,
                        deviceKey
                    FROM facial_Log
                    WHERE employeeid = '$emp_id'
                      AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date'
                      ". ($first_sched ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$sched_end'" : " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) < '$sched_start'") ."
                      ". ($first_sched ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$absent_start'" : " ") ."
                      " . ($last_timeout ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) > '$last_timeout'" : "") . "
                      " . ($last_sched_end ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) > '$last_sched_end'" : "") . "
                      AND FROM_UNIXTIME(FLOOR(`time` / 1000)) != '{$date} 00:00:00' 
                    $order_by
                    LIMIT 1
                ",
                'timeout' => "
                    SELECT 
                        FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime,
                        ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_end'))) AS time_diff,
                        deviceKey
                    FROM facial_Log
                    WHERE employeeid = '$emp_id'
                      AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date'
                      " . ($first_sched ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$sched_end'" : " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$sched_end'") ."
                      " . ($prior_sched_start ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) < '$prior_sched_start'" : "") . "
                      AND FROM_UNIXTIME(FLOOR(`time` / 1000)) > '$absent_start'
                      AND FROM_UNIXTIME(FLOOR(`time` / 1000)) != '{$date} 00:00:00' 
                    $order_by_timeout
                    LIMIT 1
                "
            ];
            // Execute and fetch time-in log
            $records = $this->db->query($queries['timein']);
            // echo $this->db->last_query(); die;
            if ($records->num_rows() > 0) {
                $row = $records->row();
                $timein = $row->logtime;
            } else {
                // Fallback query for time pm if no result found
                if($first_sched === false){
                  $fallback_query = "
                      SELECT 
                          FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime,
                          ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_start'))) AS time_diff,
                          deviceKey
                      FROM facial_Log
                      WHERE employeeid = '$emp_id'
                        AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date'
                        AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$sched_start'
                        " . ($prior_sched_start ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) < '$prior_sched_start'" : "") . "
                      $order_by
                      LIMIT 1
                  ";

                  $records = $this->db->query($fallback_query);
                  if ($records->num_rows() > 0) {
                      $row = $records->row();
                      $timein = $row->logtime;
                      $device_key = $row->deviceKey;
                  }
                }
            }
            // echo "<pre>";print_r($this->db->last_query());
            // Execute and fetch time-out log
            $records = $this->db->query($queries['timeout']);
            // echo $this->db->last_query(); die;
            if ($records->num_rows() > 0) {
                $row = $records->row();
                $timeout = $row->logtime;
                $device_key = $row->deviceKey;
            } else {
                // Fallback query for timeout if no result found
                $fallback_query = "
                    SELECT 
                        FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime,
                        ABS(TIME_TO_SEC(TIMEDIFF(FROM_UNIXTIME(FLOOR(`time` / 1000)), '$sched_end'))) AS time_diff,
                        deviceKey
                    FROM facial_Log
                    WHERE employeeid = '$emp_id'
                      AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date'
                    ". ($first_sched === false ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) <= '$sched_end'" : "  AND FROM_UNIXTIME(FLOOR(`time` / 1000)) >= '$sched_end'")."
                    " . ($prior_sched_start ? " AND FROM_UNIXTIME(FLOOR(`time` / 1000)) < '$prior_sched_start'" : "") . "
                    ORDER BY time_diff ASC
                    LIMIT 1
                ";
                $records = $this->db->query($fallback_query);
                if ($records->num_rows() > 0) {
                    $row = $records->row();
                    $timeout = $row->logtime;
                    $device_key = $row->deviceKey;
                }
            }
            // echo "<pre>";print_r($this->db->last_query());
            // remove timein if same value on timeout
            if($timein === $timeout){
              $timein = "";
            }

            if($has_last_timein != "" && $has_last_timeout == ""){
              if($timein == "" && $timeout != ""){
                $timein = $has_last_timein;
              }
            }

            // Globals::pd(array($timein, $timeout));
            // Check for existing timesheet record
            $is_exist = $this->db->query("
                SELECT * 
                FROM timesheet 
                WHERE userid = '$emp_id' 
                  AND (timein = '$timein' OR timeout = '$timein' OR timein = '$timeout' OR timeout = '$timeout')
            ")->num_rows();

            // Insert timesheet record if no existing entry
            
              $t_data[] = [
                  "timein" => $timein,
                  "timeout" => $timeout,
                  "userid" => $emp_id,
                  "otype" => "Facial",
                  "type" => "EDIT",
                  "addedby" => $device_key,
              ];
                  
            
        }
        return [
          "logs_data" => $t_data        
          ];

      }

  }

  public function noSchedLog($emp_id, $date, $order){
      $q_logs = $this->db->query("SELECT 
                          FROM_UNIXTIME(FLOOR(`time` / 1000)) AS logtime
                          FROM
                          facial_Log 
                          WHERE employeeid = '$emp_id' 
                          AND DATE(FROM_UNIXTIME(FLOOR(`time` / 1000))) = '$date' 
                          $order
                          LIMIT 1 ");
      if($q_logs->num_rows() > 0){
        return $q_logs->row()->logtime;
      }else{
        return false;
      }
  }

  public function getEmployeeAttendance($employeeid, $date_range){
    [$dateFrom, $dateTo] = $date_range;

    $query = $this->db->query("SELECT * FROM timesheet WHERE userid = '$employeeid' AND DATE(timein) BETWEEN '$dateFrom' AND '$dateTo'");
    if($query->num_rows() > 0){
      $result = array_map(function($item){
          unset($item['timeid']);
          return $item;
      },  $query->result_array());
      return $result;
    }else{
      return [];
    }
  }

  public function filterDuplicates($data){
      $filteredData = [];

      foreach ($data as $row) {
          $this->db->where($row); 
          $query = $this->db->get("timesheet");
          
          if ($query->num_rows() == 0) {
              $filteredData[] = $row;
          }
      }

      return $filteredData;
  }
}

/* End of file timesheet.php */
/* Location: ./application/models/timesheet.php */