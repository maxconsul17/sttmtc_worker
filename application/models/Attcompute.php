<?php 
/**
 * @author Justin
 * @copyright 2015
 */

use GuzzleHttp\Psr7\Query;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Attcompute extends CI_Model {
    
    
    /*
     * Date
     */
    function displayDateRange($dfrom = "",$dto = ""){
        /*
        $query = $this->db->query("SELECT DATE('$dfrom') + INTERVAL A + B + C DAY dte FROM
                                    (SELECT 0 A UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 ) d,
                                    (SELECT 0 B UNION SELECT 10 UNION SELECT 20 UNION SELECT 30 UNION SELECT 40 UNION SELECT 60 UNION SELECT 70 UNION SELECT 80 UNION SELECT 90) m , 
                                    (SELECT 0 C UNION SELECT 100 UNION SELECT 200 UNION SELECT 300 UNION SELECT 400 UNION SELECT 600 UNION SELECT 700 UNION SELECT 800 UNION SELECT 900) Y
                                    WHERE DATE('$dfrom') + INTERVAL A + B + C DAY  <=  DATE('$dto') ORDER BY A + B + C;")->result();
        */
      /*  $query = $this->db->query("SELECT * FROM 
                                    (SELECT ADDDATE('1970-01-01',t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) dte FROM
                                    (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t0,
                                    (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,
                                    (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,
                                    (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,
                                    (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t4) v
                                    WHERE dte BETWEEN '$dfrom' AND '$dto'")->result();*/

        $date_list = array();
        $period = new DatePeriod(
            new DateTime($dfrom),
            new DateInterval('P1D'),
            new DateTime($dto." +1 day")
        );
        foreach ($period as $key => $value) {
            $date_list[$key] = array();
            $date_list[$key] = (object) $date_list[$key];
            $date_list[$key]->dte = $value->format('Y-m-d')    ;   
        }
        
        return $date_list;
    }
    
    /*
     * Schedule
     */
    function displaySchedOLD($eid="",$date = ""){
        $return = "";
        $query = $this->db->query("SELECT * FROM employee_schedule WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateedit) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) /*AND starttime <> '00:00:00'*/ ORDER BY dateedit DESC,starttime DESC LIMIT 1;");
        if($query->num_rows() > 0){
            #$da = date("Y-m-d",strtotime($query->row(0)->dateactive));
            $da = $query->row(0)->dateedit;
            #$query = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) AND /*DATE(dateactive)='$da'*/ dateactive='$da' GROUP BY starttime,endtime ORDER BY editstamp;");
            $query = $this->db->query("SELECT * FROM employee_schedule WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateedit) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) AND DATE_FORMAT(dateedit,'%Y-%m-%d %H') = DATE_FORMAT('$da','%Y-%m-%d %H') GROUP BY starttime,endtime ORDER BY starttime;"); 
        }
        else
        {
            $query = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) /*AND starttime <> '00:00:00'*/ ORDER BY dateactive DESC,starttime DESC LIMIT 1;");
            if($query->num_rows() > 0){
                #$da = date("Y-m-d",strtotime($query->row(0)->dateactive));
                $da = $query->row(0)->dateactive;
                #$query = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) AND /*DATE(dateactive)='$da'*/ dateactive='$da' GROUP BY starttime,endtime ORDER BY editstamp;");
                $query = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) AND DATE_FORMAT(dateactive,'%Y-%m-%d %H') = DATE_FORMAT('$da','%Y-%m-%d %H') GROUP BY starttime,endtime ORDER BY starttime;"); 
            }
        }
        return $query; 
    }

    /*
     * Schedule
     */
    function displaySched($eid="",$date = ""){
        $return = "";
        $wc = "";
        $latestda = date('Y-m-d', strtotime($this->extensions->getLatestDateActive($eid, $date)));
        if($date >= $latestda) $wc .= " AND DATE(dateactive) = DATE('$latestda')";
        $query = $this->db->query("SELECT dateactive FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) $wc ORDER BY dateactive DESC,starttime DESC LIMIT 1;");
        if($query->num_rows() > 0){
            $da = $query->row(0)->dateactive;
            $query = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) AND DATE_FORMAT(dateactive,'%Y-%m-%d %H') = DATE_FORMAT('$da','%Y-%m-%d %H') GROUP BY starttime,endtime ORDER BY starttime;"); 
        }
        return $query; 
    }

    function getAvailableScheduleDayOfWeek($eid=""){
        $date = date("Y-m-d");
        $wc = "";
        $latestda = date('Y-m-d', strtotime($this->extensions->getLatestDateActive($eid, $date)));

        if($date >= $latestda) $wc .= " AND DATE(dateactive) = DATE('$latestda')";

        $query = $this->db->query("SELECT dateactive FROM employee_schedule_history WHERE employeeid = '$eid' AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) $wc ORDER BY dateactive DESC,starttime DESC LIMIT 1;");

        if($query->num_rows() > 0){
            $da = $query->row(0)->dateactive;
            $query = $this->db->query("SELECT dayofweek FROM employee_schedule_history WHERE employeeid = '$eid' AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) AND DATE_FORMAT(dateactive,'%Y-%m-%d %H') = DATE_FORMAT('$da','%Y-%m-%d %H') GROUP BY dayofweek ORDER BY starttime;"); 
        }

        return $query; 
    }

    function scheduleChecker($eid="",$date = ""){
        $return = "";
        $query = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$eid' AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) ORDER BY dateactive DESC,starttime DESC LIMIT 1");
        
        if($query->num_rows() > 0){
            $return = "true";
        }else{
          $return = "false";
        }
        return $return; 
    }

    /*
     * Schedule Substitute
     */
    function displaySchedSubstitute($eid="",$date = "",$wc=""){
        $return = "";
        $query = $this->db->query("SELECT dateactive FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) $wc ORDER BY dateactive DESC,starttime DESC LIMIT 1;");
        if($query->num_rows() > 0){
            $da = $query->row(0)->dateactive;
            $query = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$eid' AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) AND DATE_FORMAT(dateactive,'%Y-%m-%d %H') = DATE_FORMAT('$da','%Y-%m-%d %H') $wc GROUP BY starttime,endtime ORDER BY starttime;"); 
        }
        return $query; 
    }

    function displayLogTime($eid="",$date="",$tstart="",$tend="",$tbl="NEW",$seq=1,$absent_start='',$earlyd='',$used_time=array(), $campus=''){
        // echo "<pre>"; print_r($date);die;
        $haslog = true;
        $timein = $timeout = $otype = $is_ob ="";

        if($tbl == "NEW")   $tbl = "timesheet";
        else                $tbl = "timesheet_bak";

        /*$wCAbsentEarlyD = '';
        if($absent_start) $wCAbsentEarlyD .= " AND ( TIME(timeout) > '$absent_start' )";
        if($earlyd)       $wCAbsentEarlyD .= " AND ( TIME(timein) < '$earlyd'  )";*/
        // echo "<pre>"; print_r(array($tstart, $tend));
        $add_wc = "";
        if($used_time){
            if(!isset($used_time[0])) $used_time[0] = "0000-00-00 00:00:00";
            if(!isset($used_time[1])) $used_time[1] = "0000-00-00 00:00:00";
            // COMMENT PO MUNA ITO DAHIL I ALLOW ANG WHOLE DAY NA 2 LOGS LANG
            // $add_wc = " AND timein != '{$used_time[0]}' AND timeout != '{$used_time[1]}' ";
        }
        //QUERY CHANGED TO DESC FROM ASC :KEN

        //QUERY CHANGED TO ASC FROM DESC :MAX 2022
        $query = $this->db->query("
                SELECT timein,timeout,otype, addedby, ob_id FROM $tbl 
                WHERE userid='$eid' 
                AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                AND ( TIME(timein)<='$absent_start' )
                -- AND ( TIME(timein)>='$tstart' )
                AND ( TIME(timeout) >= '$earlyd' ) 
                AND timein != timeout
                $add_wc
                AND (UNIX_TIMESTAMP(timeout) - UNIX_TIMESTAMP(timein) ) > '60' 
                ORDER BY timein DESC LIMIT 1");

 
             
        
        if($query->num_rows() > 0){
            $otype   = $query->row($seq)->otype;
            $addedby   = $query->row($seq)->addedby;
            $seq = $seq - 1;
            $timein  = $query->row($seq)->timein;
            $timeout = $query->row($seq)->timeout;
            $is_ob = $query->row($seq)->ob_id;
            
            // if(in_array($timein, $used_time) && in_array($timeout, $used_time)){
                $query = $this->db->query("
                SELECT timein,timeout,otype, addedby, ob_id FROM $tbl 
                WHERE userid='$eid' 
                AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                AND ( TIME(timein)<='$tend' )
                -- AND ( TIME(timein)>='$tstart' )
                AND ( TIME(timeout) > '$tstart' ) 
                AND timein != timeout
        
                ORDER BY timein ASC LIMIT 1");
                if($query->num_rows() > 0){
                    $timein  = $query->row($seq)->timein;
                    $timeout = $query->row($seq)->timeout;
                    $otype   = $query->row($seq)->otype;
                    $addedby   = $query->row($seq)->addedby;
                    $is_ob   = $query->row($seq)->ob_id;
                }
            // } 

            if($otype == "Facial" && $campus){
                if($addedby != "FacialResync"){
                    $facial_campus_id = $this->db->query("SELECT campusid FROM facial_heartbeat WHERE deviceKey = '$addedby'");
                    if($facial_campus_id->num_rows() > 0){
                        if($facial_campus_id->row()->campusid != $campus){
                            $timein = $timeout = $otype = "";
                        }

                        if($timein == "" && $timeout == ""){
                            $query = $this->db->query("
                            SELECT timein,timeout,otype, addedby FROM $tbl 
                            WHERE userid='$eid' 
                            AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                            AND ( TIME(timein)<='$tend' )
                            -- AND ( TIME(timein)>='$tstart' )
                            AND ( TIME(timeout) > '$tstart' ) 
                            AND timein != timeout
                    
                            ORDER BY timein DESC");
                            if($query->num_rows() > 0){
                                foreach ($query->result() as $key => $value) {
                                    $time_in  = date('Y-m-d', strtotime($value->timein));
                                    $time_out =   date('Y-m-d', strtotime($value->timeout));
                                    $added_by   = $value->addedby;
                                    $facial_logs = $this->db->query("SELECT * FROM facial_Log a INNER JOIN facial_heartbeat b ON a.deviceKey = b.deviceKEy WHERE a.deviceKey = '$added_by' AND ( DATE(`date`)='$time_in' AND DATE(`date`)='$time_out' ) AND b.campusid = '$campus' AND employeeid = '$eid' ");
                                    if($facial_logs->num_rows()){
                                        $timein = $value->timein;
                                        $timeout = $value->timeout;
                                        $otype = "Facial";
                                    }

                                }
                            }
                        }
                    }
                }
            }
            
        }else{

            $wCAbsentEarlyD = '';
            if($absent_start) $wCAbsentEarlyD .= " AND ( TIME(timeout) > '$absent_start' OR DATE_FORMAT(timeout,'%H:%i:%s') = '00:00:00' )";
            if($earlyd)       $wCAbsentEarlyD .= " AND ( TIME(timein) < '$earlyd' OR DATE_FORMAT(timein,'%H:%i:%s') = '00:00:00' )";

            $query = $this->db->query("
                    SELECT timein,timeout,otype, addedby FROM $tbl 
                    WHERE userid='$eid' 
                    AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                    AND ( TIME(timein)<='$tend' OR  DATE_FORMAT(timein,'%H:%i:%s') = '00:00:00' )
                    AND ( TIME(timeout) > '$tstart' OR DATE_FORMAT(timeout,'%H:%i:%s') = '00:00:00' ) 
                    AND timein != timeout
                    $wCAbsentEarlyD 
                    ORDER BY timein ASC LIMIT 1");
               
    
                    // echo "<pre>"; print_r($this->db->last_query());
                    // echo "<pre>"; print_r($query->num_rows());

            if($query->num_rows() > 0){
                $seq = $seq - 1;

                $timein  = $query->row($seq)->timein;
                $timeout = $query->row($seq)->timeout;
                $otype   = $query->row($seq)->otype;  
                $addedby   = $query->row($seq)->addedby;  
                if($otype == "Facial" && $campus){
                    if($addedby != "FacialResync"){
                        $facial_campus_id = $this->db->query("SELECT campusid FROM facial_heartbeat WHERE deviceKey = '$addedby'");
                        if($facial_campus_id->num_rows() > 0){
                            if($facial_campus_id->row()->campusid != $campus){
                                $timein = $timeout = $otype = "";
                            }

                            if($timein == "" && $timeout == ""){
                                $query = $this->db->query("
                                SELECT timein,timeout,otype, addedby FROM $tbl 
                                WHERE userid='$eid' 
                                AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                                AND ( TIME(timein)<='$tend' )
                                -- AND ( TIME(timein)>='$tstart' )
                                AND ( TIME(timeout) > '$tstart' ) 
                                AND timein != timeout
                        
                                ORDER BY timein DESC");
                                if($query->num_rows() > 0){
                                    foreach ($query->result() as $key => $value) {
                                        $time_in  = date('Y-m-d', strtotime($value->timein));
                                        $time_out =   date('Y-m-d', strtotime($value->timeout));
                                        $added_by   = $value->addedby;
                                        $facial_logs = $this->db->query("SELECT * FROM facial_Log a INNER JOIN facial_heartbeat b ON a.deviceKey = b.deviceKEy WHERE a.deviceKey = '$added_by' AND ( DATE(`date`)='$time_in' AND DATE(`date`)='$time_out' ) AND b.campusid = '$campus' AND employeeid = '$eid' ");
                                        if($facial_logs->num_rows()){
                                            $timein = $value->timein;
                                            $timeout = $value->timeout;
                                            $otype = "Facial";
                                        }

                                    }
                                }
                            }
                        }
                    }
                } 
            }else{

                $query = $this->db->query("SELECT logtime FROM timesheet_trail WHERE userid='$eid' AND DATE(logtime)='$date' AND log_type = 'IN' ORDER BY logtime DESC LIMIT $seq");
             
                if($query->num_rows() > 0){
                    $seq = $seq - 1;
                    $timein  = $query->row($seq)->logtime;
                    $timeout = $otype = "";
                    // $return = array($timein,"","",$haslog);
                }else{
                    $haslog = false;
                    $checklog_q = $this->db->query("SELECT timein,timeout, addedby, otype FROM $tbl WHERE userid='$eid' AND DATE(timein)='$date' ORDER BY timein DESC LIMIT $seq"); // lol timeid to timein DESC
                  
                    if($checklog_q->num_rows() > 0) $haslog = true;
                    if($haslog){
                        $seq = $seq - 1;                           // lola put this
                        $timein = $timeout = "";
                        $timein  = $checklog_q->row($seq)->timein; // lola get value timein
                        $timeout  = $checklog_q->row($seq)->timeout; // URS-564
                        $otype   = $checklog_q->row($seq)->otype;  
                        $addedby   = $checklog_q->row($seq)->addedby;  
                        if($otype == "Facial" && $campus){
                            if($addedby != "FacialResync"){
                                $facial_campus_id = $this->db->query("SELECT campusid FROM facial_heartbeat WHERE deviceKey = '$addedby'");
                                if($facial_campus_id->num_rows() > 0){
                                    if($facial_campus_id->row()->campusid != $campus){
                                        $timein = $timeout = $otype = "";
                                    }

                                    if($timein == "" && $timeout == ""){
                                        $query = $this->db->query("
                                        SELECT timein,timeout,otype, addedby FROM $tbl 
                                        WHERE userid='$eid' 
                                        AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                                        AND ( TIME(timein)<='$tend' )
                                        -- AND ( TIME(timein)>='$tstart' )
                                        AND ( TIME(timeout) > '$tstart' ) 
                                        AND timein != timeout
                                
                                        ORDER BY timein DESC");
                                        if($query->num_rows() > 0){
                                            foreach ($query->result() as $key => $value) {
                                                $time_in  = date('Y-m-d', strtotime($value->timein));
                                                $time_out =   date('Y-m-d', strtotime($value->timeout));
                                                $added_by   = $value->addedby;
                                                $facial_logs = $this->db->query("SELECT * FROM facial_Log a INNER JOIN facial_heartbeat b ON a.deviceKey = b.deviceKEy WHERE a.deviceKey = '$added_by' AND ( DATE(`date`)='$time_in' AND DATE(`date`)='$time_out' ) AND b.campusid = '$campus' AND employeeid = '$eid' ");
                                                if($facial_logs->num_rows()){
                                                    $timein = $value->timein;
                                                    $timeout = $value->timeout;
                                                    $otype = "Facial";
                                                }

                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $otype = true;
                    }else{
                        
                        // DISPLAY THE TIME-IN OF EMPLOYEE IMMEDIATELY.
                        $query = $this->db->select('stamp_in, stamp_out, DATE(FROM_UNIXTIME(FLOOR(`time_in`/1000))) AS datecreated')
                                ->from('login_attempts_terminal')
                                ->where('user_id', $eid)
                                ->where("DATE(FROM_UNIXTIME(FLOOR(`time_in`/1000)))", $date)
                                ->get();

                        $rows = $query->result_array();
                        $haslog = false;
                        if($query->num_rows() > 0) $haslog = true;
                        if($haslog){
                            $timein = $rows[0]['stamp_in'];
                        }

                    }
                }
            }   
            // var_dump($this->db->last_query());die;
        }

        if($timein=='0000-00-00 00:00:00') $timein = "";
        if($timeout=='0000-00-00 00:00:00') $timeout = "";
        $used_time = array($timein, $timeout);
        $campus_in = $campus_out = null;
        return array($timein,$timeout,$otype,$haslog,$used_time, $is_ob, $campus_in, $campus_out);
    }

    /*LOGIN DATA FOR OB WFH*/
    function displayLogTimeOB($eid="",$date="",$tstart="",$tend="",$tbl="",$seq=1,$absent_start='',$earlyd='',$used_time=array()){
        $haslog = true;
        $timein = $timeout = $otype = "";

        $add_wc = "";
        if($used_time){
            if(!isset($used_time[0])) $used_time[0] = "0000-00-00 00:00:00";
            if(!isset($used_time[1])) $used_time[1] = "0000-00-00 00:00:00";
            $add_wc = " AND timein != '{$used_time[0]}' AND timeout != '{$used_time[1]}' ";
        }
        //QUERY CHANGED TO DESC FROM ASC :KEN
        $query = $this->db->query("
                SELECT timein,timeout FROM ob_timerecord a
                INNER JOIN ob_app_emplist b ON a.id = b.base_id
                WHERE employeeid='$eid' 
                AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                AND ( TIME(timein)<='$absent_start' )
                -- AND ( TIME(timein)>='$tstart' )
                AND ( TIME(timeout) >= '$earlyd' ) 
                AND timein != timeout
                $add_wc
                AND (UNIX_TIMESTAMP(timeout) - UNIX_TIMESTAMP(timein) ) > '60' 
                ORDER BY timein DESC LIMIT 1");
        if($query->num_rows() > 0){
            $seq = $seq - 1;
            $timein  = $query->row($seq)->timein;
            $timeout = $query->row($seq)->timeout;
            if(in_array($timein, $used_time) && in_array($timeout, $used_time)){
                $query = $this->db->query("
                SELECT timein,timeout FROM ob_timerecord a
                INNER JOIN ob_app_emplist b ON a.id = b.base_id
                WHERE employeeid='$eid' 
                AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                AND ( TIME(timein)<='$tend' )
                -- AND ( TIME(timein)>='$tstart' )
                AND ( TIME(timeout) > '$tstart' ) 
                AND timein != timeout
        
                ORDER BY timein DESC LIMIT 1");
                if($query->num_rows() > 0){
                    $timein  = $query->row($seq)->timein;
                    $timeout = $query->row($seq)->timeout;
                }
            }                        

        }else{

            $wCAbsentEarlyD = '';
            if($absent_start) $wCAbsentEarlyD .= " AND ( TIME(timeout) > '$absent_start' OR DATE_FORMAT(timeout,'%H:%i:%s') = '00:00:00' )";
            if($earlyd)       $wCAbsentEarlyD .= " AND ( TIME(timein) < '$earlyd' OR DATE_FORMAT(timein,'%H:%i:%s') = '00:00:00' )";

            $query = $this->db->query("
                    SELECT timein,timeout FROM ob_timerecord a
                    INNER JOIN ob_app_emplist b ON a.id = b.base_id
                    WHERE employeeid='$eid' 
                    AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                    AND ( TIME(timein)<='$tend' OR  DATE_FORMAT(timein,'%H:%i:%s') = '00:00:00' )
                    AND ( TIME(timeout) > '$tstart' OR DATE_FORMAT(timeout,'%H:%i:%s') = '00:00:00' ) 
                    AND timein != timeout
                    $wCAbsentEarlyD 
                    ORDER BY timein ASC LIMIT 1");
            

            if($query->num_rows() > 0){
                $seq = $seq - 1;

                $timein  = $query->row($seq)->timein;
                $timeout = $query->row($seq)->timeout;
                                        
            }   
        }

        if($timein=='0000-00-00 00:00:00') $timein = "";
        if($timeout=='0000-00-00 00:00:00') $timeout = "";
        $used_time = array($timein, $timeout);
        return array($timein,$timeout,$otype,$haslog,$used_time);
    }

    function isFixedDay($empid=''){
        $fixedday = TRUE;
        $fixedday_q = $this->db->query("SELECT fixedday FROM payroll_employee_salary WHERE employeeid='$empid'");
        if($fixedday_q->num_rows() > 0) $fixedday = $fixedday_q->row(0)->fixedday;
        return $fixedday;
    }
   
    /*
     * Late & Undertime
     */ 
    ///< LATE condition (if timein > tardy start, then late = timein - tardy_start)
    // Teaching
    function displayLateUT($stime="",$etime="",$tardy_start='',$login="",$logout="",$type="",$absent=""){
        if(!$tardy_start) $tardy_start = $stime;
        $lec = $lab = $tschedlec = $tschedlab = $admin = $tschedadmin = $rle = $tschedrle = 0;
        $schedstart   = strtotime($stime);
        $schedend   = strtotime($etime);
        $schedtardy   = strtotime($tardy_start);
        
        if($login && $logout && !$absent){
            if($login)  $login = date("H:i:s",strtotime($login));
            if($logout) $logout = date("H:i:s",strtotime($logout));
            
            // Late
            $logtime    = strtotime($login);
            $logouttime    = strtotime($logout);
            
            $late = '';
            // if($logtime >= $schedtardy) $late        = round(($logtime - $schedstart) / 60,2);
            if($logtime >= $schedtardy) $late        = round(($logtime - $schedtardy) / 60,2);


            if($late > 0){
                if($type == 'LEC')       $lec =  $late;
                elseif($type == 'LAB')   $lab = $late;
                elseif($type == 'ADMIN') $admin = $late;
                else                     $rle = $late;
            }
            
            // Undertime
            $ut=0;
            if($logouttime < $schedend) {
                $ut = round(($schedend - $logouttime) / 60,2);
            }
            if($ut > 0){
                if($type == 'LEC')       $lec +=  $ut;
                elseif($type == 'LAB')   $lab += $ut;
                elseif($type == 'ADMIN') $admin += $ut;
                else                    $rle += $ut;
            }


            
        }

        if($type == 'LEC' && $lec)       $lec =  date('H:i', mktime(0,$lec));
        elseif($type == 'LAB' && $lab)   $lab =  date('H:i', mktime(0,$lab));
        elseif($type == 'ADMIN' && $rle)                   $admin =  date('H:i', mktime(0,$rle));
        // else $admin =  date('H:i', mktime(0,$rle));
        elseif($rle) $admin =  date('H:i', mktime(0,$rle));
        
        if($absent){
            // total sched
            $tsched   = round(abs($schedstart - $schedend) / 60,2);
            $tsched   = date('H:i', mktime(0,$tsched));
            if($type == 'LEC')       $tschedlec =  $tsched;
            elseif($type == 'LAB')   $tschedlab = $tsched;
            elseif($type == 'ADMIN') $tschedadmin = $tsched;
            else                     $tschedrle = $tsched;
        }
         
        return array($lec,$lab,$admin,$tschedlec,$tschedlab,$tschedadmin,$rle,$tschedrle);
    }
    // Non Teaching
    function displayLateUTNT($stime="",$etime="",$login="",$logout="",$absent="",$ttype="",$tardy=""){
        $lateut = "";
        if($login && $logout && !$absent){
            
            if($login)  $login = date("H:i",strtotime($login));
            if($logout) $logout = date("H:i",strtotime($logout));
            
            // Late
            $schedstart  = strtotime($stime);
            $logtime     = strtotime($login);
            $schedtardy   = strtotime($tardy) - 60; //< get actual tardy start
            if($logtime > $schedtardy){
                $lateut        = round(($logtime - strtotime($stime)) / 60,2);
                $lateut = date('H:i', mktime(0,$lateut));
            }
            
        }
        if($lateut == "00:00") $lateut = "";
        return $lateut;
    }

    function computeUndertime($stime="",$etime="",$tardy_start='',$login="",$logout="",$type="",$absent=""){
        $lec = $lab = $admin = 0 ;
        $schedend   = strtotime($etime);
        
        if($login && $logout && !$absent){
            if($logout) $logout = date("H:i:s",strtotime($logout));
            
            $logouttime    = strtotime($logout);
            
            // Undertime
            $ut='';
            if($logouttime < $schedend) $ut = round(($schedend - $logouttime) / 60,2);
            if($ut > 0){
                if($type == 'LEC')       $lec +=  $ut;
                elseif($type == 'LAB')   $lab += $ut;
                else                    $admin += $ut;
            }
            
        }

        if($lec > 0 && $lec < 1) $lec = 1;
        if($lab > 0 && $lab < 1) $lab = 1;
        if($admin > 0 && $admin < 1) $admin = 1;

        if($type == 'LEC' && $lec)       $lec =  date('H:i', mktime(0,$lec));
        elseif($type == 'LAB' && $lab)   $lab =  date('H:i', mktime(0,$lab));
        elseif($admin)                   $admin =  date('H:i', mktime(0,$admin));
        
        return array($lec,$lab,$admin);
    }

    function computeUndertimeNT($stime="",$etime="",$login="",$logout="",$absent="",$ttype="",$tardy=""){
        $schedstart = strtotime($stime);
        $schedend = strtotime($etime);
        $workhours = round(abs($schedstart - $schedend) / 60,2);
        
        $lateut = "";
        if($login && $logout && !$absent){
            // if($login < $stime) $login = $stime;
            
            if($login)  $login = date("H:i",strtotime($login));
            if($logout) $logout = date("H:i",strtotime($logout));
            
            // Undertime
            $schedend    = strtotime($etime);
            $logtime     = strtotime($logout);
            $ut          = round(abs($logtime - $schedend) / 60,2);

            // ADD THIS CONDITION PARA DI NASASAMA YUNG BREAKTIME SA UNDERTIME
            if($ut > $workhours){
                $ut = $workhours;
            }

            if(abs($logout) > 0){
                if( $logout < $etime )   $lateut = date('H:i', mktime(0,$ut));
            }
        }
        if($lateut == "00:00") $lateut = "";
        return $lateut;
    }
    
    /*
     * Absent
     */


     function displayAbsentOld($stime = "", $etime = "", $login = "", $logout = "", $empid = "", $dset = "", $earlyd = "", $absent_start = "") {
        $absent = "";
        $isteaching = $this->employee->getempteachingtype($empid);
        if ($login)  $login = date("H:i:s", strtotime($login));
        if ($logout) $logout = date("H:i:s", strtotime($logout));
        
        $schedstart = strtotime($stime);
        $schedend = strtotime($etime);
        $logtime = strtotime($login);
        $logouttime = strtotime($logout);
    
        $totalMinutes = ($schedend - $logouttime) / 60; 
        $hours = floor($totalMinutes / 60); 
        $minutes = $totalMinutes % 60;
        
        if ($logout && $logouttime < $schedend) {
            $absent = sprintf('%02d:%02d', $hours, $minutes); 
        } else if (!$login && !$logout) {
            $absent = date('H:i', mktime(0, ($schedend - $schedstart) / 60)); 
        }
    
        if ($empid) {
            $query = $this->db->query("SELECT * FROM attendance_absent_checker WHERE employeeid='$empid' AND scheddate = '$dset' AND schedstart = '$stime' AND schedend = '$etime'");
            if ($query->num_rows() > 0) $absent++;
        }
        if ($logout <= $earlyd && !$absent) $absent = sprintf('%02d:%02d', $hours, $minutes);
        if (!$absent && $earlyd && $logout < $earlyd) $absent = sprintf('%02d:%02d', $hours, $minutes);
        if (!$absent && $absent_start && $login >= $absent_start) $absent = sprintf('%02d:%02d', $hours, $minutes);
    
        return $absent;
    }
    
    function displayAbsent($stime="",$etime="",$login="",$logout="",$empid="",$dset="",$earlyd="",$absent_start=""){
        $absent = "";
        $isteaching = $this->employee->getempteachingtype($empid);
        if($login)  $login = date("H:i:s",strtotime($login));
        if($logout) $logout = date("H:i:s",strtotime($logout));
        $schedstart   = strtotime($stime);
        $schedend   = strtotime($etime);
        $logtime    = strtotime($login);
        $logouttime    = strtotime($logout);
        
        $schedHour = round((abs($logouttime - $logtime) /60)/60,2);
        $interval   = round(abs($schedend - $schedstart) / 60,2);
        // $interval   = round(abs($schedend - $etime) / 60,2);
        
        $totalHoursOfWork = round(abs($schedend - $schedstart) / 60,2);
        
        if($schedHour <= 2)
        {
            if( $stime && ($interval <= 30 || !$login) && $stime <> '00:00:00'  ) $absent = date('H:i', mktime(0,$totalHoursOfWork));
        }
        else if($schedHour > 2)
        {
            if( $stime && ($interval <= 60 || !$login) && $stime <> '00:00:00' && $schedstart< $logtime ) $absent = date('H:i', mktime(0,$totalHoursOfWork));
        }

        
        if($empid){
            $query = $this->db->query("SELECT * FROM attendance_absent_checker WHERE employeeid='$empid' AND scheddate = '$dset' AND schedstart = '$stime' AND schedend = '$etime'");
            if($query->num_rows() > 0)  $absent++;
        }
        if($logout <= $earlyd && !$absent) $absent = date('H:i', mktime(0,$totalHoursOfWork));  // log-out <= start of schedule will be marked as absent.
        if(!$absent && $earlyd)if($logout < $earlyd) $absent = date('H:i', mktime(0,$totalHoursOfWork));  // log-out <= early dismissal will be marked as absent. 
        if(!$absent && $absent_start)if($login >= $absent_start) $absent = date('H:i', mktime(0,$totalHoursOfWork));  // log-in >= absent start will be marked as absent. 
        // if(!$isteaching)    $absent = ($absent/2) ? ($absent/2) : "";

        if(!$absent && !$login && !$logout){
             $absent = date('H:i', mktime(0,$totalHoursOfWork));
        }


        return $absent;
    }
    
    /*
     * Leave
     */
    //backup
    /*function displayLeave($eid="",$date="",$absent=""){
        $sl = $el = $vl = $ol = $oltype = "";
        $query = $this->db->query("SELECT * FROM leave_request WHERE '$date' BETWEEN fromdate AND todate AND employeeid='$eid'");
        if($query->num_rows() > 0){  
            if($query->row(0)->leavetype == "VL" && $query->row(0)->paid == "YES"){       $vl++; $ol = $query->row(0)->leavetype; $oltype = "VACATION";}
            else if($query->row(0)->leavetype == "EL" && $query->row(0)->paid == "YES"){  $el++; $ol = $query->row(0)->leavetype; $oltype = "EMERGENCY";}
            else if($query->row(0)->leavetype == "SL" && $query->row(0)->paid == "YES"){  $sl++; $ol = $query->row(0)->leavetype; $oltype = "SICK";}
            else if($query->row(0)->leavetype == "other" && $query->row(0)->paid == "YES"){  $ol = $query->row(0)->other; $oltype = "OFFICIAL BUSINESS";}
            else                                         {$ol = $query->row(0)->other; $oltype = $query->row(0)->othertype;}
        }
        return array($el,$vl,$sl,$ol,$oltype);
    }*/
    function displayLeave($eid="",$date="",$absent="",$stime='',$etime='',$sched_count=''){
       $sl = $el = $vl = $ol = $ob = $abs_count = $tfrom = $tto = $daterange = $split=$l_nopay=0;
       $oltype = "";
        $time_aff = $stime.'|'.$etime;
        $query = $this->db->query("SELECT * FROM leave_request lr WHERE '$date' BETWEEN fromdate AND todate AND employeeid='$eid' AND EXISTS (SELECT 1 FROM leave_app_base WHERE `status` = 'APPROVED' AND id = lr.aid)");
        // echo $this->db->last_query().'<br>';
        if($query && $query->num_rows() > 0){  
            $res = $query->row(0);
            $arr_sched_aff = array();
            $no_days = $res->no_days;
            $base_id = $res->aid;

            // COMMENT PO MUNA
            // $time_aff = $this->displayLeaveSched($base_id, $date, $sched_count);

            if($no_days == 0.50 && $res->sched_affected){
                $arr_sched_aff = explode(',', $res->sched_affected);
            } 


            if($res->leavetype == "AL" && $query->row(0)->paid == "YES")
            {     
                if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                    if(in_array($time_aff, $arr_sched_aff)){
                        $vl = $no_days; 
                        $ol = $res->leavetype; 
                        $oltype = $this->employeemod->othLeaveDesc($ol);
                    }
                }else{
                    $vl = $no_days >= 1 ? 1.00 : $no_days;  
                    $ol = $res->leavetype; 
                    $oltype = $this->employeemod->othLeaveDesc($ol);
                }  
            }
            else if(strpos($res->leavetype, 'PL-') !== false && $query->row(0)->paid == "YES")
            {     
                if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                    if(in_array($time_aff, $arr_sched_aff)){
                        $vl = $no_days; 
                        $ol = $res->leavetype; 
                        $oltype = $this->employeemod->othLeaveDesc($ol);
                    }
                }else{
                    $vl = $no_days >= 1 ? 1.00 : $no_days;  
                    $ol = $res->leavetype; 
                    $oltype = $this->employeemod->othLeaveDesc($ol);
                }  
            }
            else if($res->leavetype == "EL" && $res->paid == "YES"){  
                if($no_days == 0.50){
                    if(in_array($time_aff, $arr_sched_aff)){
                        $vl = $no_days; 
                        $ol = $res->leavetype; 
                        $oltype = $this->employeemod->othLeaveDesc($ol);
                    }
                }else{
                    $vl = 1.00; 
                    $ol = $res->leavetype; 
                    $oltype = $this->employeemod->othLeaveDesc($ol);
                }  
            }
            else if($res->paid == "YES" && ($res->leavetype != "AL" && $res->leavetype != "SL")){  
                if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                    if(in_array($time_aff, $arr_sched_aff)){
                        
                        $ol = $res->leavetype; 
                        $oltype = $this->employeemod->othLeaveDesc($ol);
                        $ol = $no_days; 
                    }
                }else{
                      
                    $ol = $res->leavetype; 
                    $oltype = $this->employeemod->othLeaveDesc($ol);
                    $ol = $no_days >= 1 ? 1.00 : $no_days;
                }  
            }
            else if($res->leavetype == "SL" && $res->paid == "YES"){  
                if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                    if(in_array($time_aff, $arr_sched_aff)){
                        $sl = $no_days; 
                        $ol = $res->leavetype; 
                        $oltype = $this->employeemod->othLeaveDesc($ol);
                    }
                }else{
                    $sl = $no_days >= 1 ? 1.00 : $no_days;  
                    $ol = $res->leavetype; 
                    $oltype = $this->employeemod->othLeaveDesc($ol);
                }  
            }
            else if($res->leavetype == "ABSENT"){  
                if($no_days == 0.50 && sizeof($arr_sched_aff) > 0){
                    if(in_array($time_aff, $arr_sched_aff)){
                        $abs_count = $no_days; 
                        $ol = $res->leavetype; 
                        $oltype = $this->employeemod->othLeaveDesc($ol);
                    }
                }else{
                    $abs_count = $no_days >= 1 ? 1.00 : $no_days;  
                    $ol = $res->leavetype; 
                    $oltype = $this->employeemod->othLeaveDesc($ol);
                }  
            }
            else if($res->leavetype == "other"/* && $res->paid == "YES"*/){ 
                // $othertype = $res->othertype;
                // if($othertype=='NO PUNCH IN/OUT')   $oltype = 'CORRECTED TIME IN/OUT';
                // elseif($othertype=='ABSENT')        $oltype = 'ABSENT W/ FILE';
                // else                                $oltype = "OFFICIAL BUSINESS";
                $ol = $res->other; 
            }else if($res->leavetype && $res->paid == "NO"){ 
                $l_nopay = $no_days;
            }
            else{
                $ol = $res->leavetype;  
                $oltype = $this->employeemod->othLeaveDesc($ol);
            }
        }

        $query1 = $this->db->query("SELECT * FROM ob_request WHERE '$date' BETWEEN fromdate AND todate AND employeeid='$eid' AND (ob_type = 'ob' OR ob_type = '') AND obtypes != '2' AND status = 'APPROVED' ");
        $obtypes = "";
        if($query1 && $query1->num_rows() > 0){  
            $res = $query1->row(0);
            $arr_sched_aff = array();
            $no_days = $res->no_days;
            $isHalfDay = $res->isHalfDay;
            if(!$res->sched_affected) $res->sched_affected = $res->timefrom."|".$res->timeto;
            // echo "<pre>"; print_r($res->sched_affected);
            $arr_sched_aff = explode(',', $res->sched_affected);
            $obtypes = $res->obtypes;

            if($isHalfDay  && sizeof($arr_sched_aff) > 0){
                if(in_array($time_aff, $arr_sched_aff)){
                    // $othertype = $res->othertype;
                    $othertype = $res->type;
                    if($othertype=='DA' && $res->paid == "YES"){
                        if($isHalfDay) $ob = 0.50;
                        else $ob = $no_days;
                    }
                    if($othertype=='CORRECTION')        $oltype .= 'CORRECTED TIME IN/OUT';
                    elseif($othertype=='ABSENT')        $oltype .= 'ABSENT W/ FILE';
                    else                                $oltype .= "OFFICIAL BUSINESS";
                    $ol = $othertype;
                }
            }else{
                // $othertype = $res->othertype;
                $othertype = $res->type;
                if($othertype=='DA' && $res->paid == "YES"){
                    if($isHalfDay) $ob = 0.50;
                    else $ob = 1.00; 
                }
                if($othertype=='CORRECTION')        $oltype .= 'CORRECTED TIME IN/OUT';
                elseif($othertype=='ABSENT')        $oltype .= 'ABSENT W/ FILE';
                else                                $oltype .= "OFFICIAL BUSINESS";
                $ol = $othertype;
            }

        }


        $query2 = $this->db->query("SELECT * FROM ob_request WHERE '$date' BETWEEN fromdate AND todate AND employeeid='$eid' AND ob_type != 'ob' AND type != 'CORRECTION'  AND status = 'APPROVED' ");

        if($query2 && $query2->num_rows() > 0){  

            $res = $query2->row(0);
            $ob = "";
            $othertype = $res->ob_type;
            if($othertype=='late')        $oltype = 'Excuse Slip (late)';
            elseif($othertype=='absent')  $oltype = 'Excuse Slip (absent)';
            // $ol = $othertype;
            
        }

        $query3 = $this->db->query("SELECT * FROM ob_request WHERE '$date' BETWEEN fromdate AND todate AND employeeid='$eid' AND (ob_type = 'ob' OR ob_type = '') AND obtypes = '2'  AND status = 'APPROVED'");

        if($query3 && $query3->num_rows() > 0){  

            $res = $query3->row(0);
            if($res->othertype == "DA"){
                $is_wfh = $this->attcompute->isWfhOB($eid,$date);
                if($is_wfh->num_rows() == 1){
                    $ob_id = $is_wfh->row()->aid;
                    $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
                    if($hastime->num_rows() == 0){
                        // $ol = $oltype = $ob = "";
                    }
                    else{
                        $fitSched = false;
                        foreach ($hastime->result() as $htkey => $htval) {
                            $ht_timein =  date("H:i:s", strtotime($htval->timein));
                            $ht_timeout =  date("H:i:s", strtotime($htval->timeout));
                            if($ht_timein <= $stime || $ht_timeout >= $etime){
                                $fitSched = true;
                            }
                        }
                        if($fitSched){
                            $othertype = $res->othertype;
                            $ob = 1.00;
                            if($oltype) $oltype .= "<br><br>OFFICIAL BUSINESS";
                            else $oltype .= "OFFICIAL BUSINESS";
                            if(!$ol) $ol = $othertype;
                        }
                    }
                }
                 
            }

        }

        return [
            isset($el) ? $el : 0,
            isset($vl) ? $vl : 0,
            isset($sl) ? $sl : 0,
            isset($ol) ? $ol : '',
            isset($oltype) ? $oltype : '',
            isset($ob) ? $ob : '',
            isset($abs_count) ? $abs_count : 0,
            isset($l_nopay) ? $l_nopay : 0,
            isset($obtypes) ? $obtypes : 0,
            isset($ob_id) ? $ob_id : null
        ];
    }

    function displayLeaveSched($base_id='', $date='',$sched_count=''){
      $sched = array();
      $leave_d = $this->db->query("SELECT a.base_id, a.employeeid FROM leave_app_emplist a INNER JOIN leave_app_base b ON a.base_id = b.id WHERE a.id = '$base_id'");
      if($leave_d->num_rows() > 0){
        $leave_id = $leave_d->row()->base_id;
        $employeeid = $leave_d->row()->employeeid;
        $leave_sched = $this->db->query("SELECT * FROM leave_schedref WHERE base_id = '$leave_id' ");
        if($leave_sched->num_rows() > 0){
          $dateactive = $leave_sched->row()->dateactive;
          $schedule = $this->db->query("SELECT * FROM employee_schedule_history WHERE dateactive = '$dateactive' AND idx  = DATE_FORMAT('$date','%w') AND employeeid = '$employeeid' ");
          if($schedule->num_rows() > 0){
            $seq_count = 1;
            foreach($schedule->result() as $res){
              $sched[$seq_count] = $res->starttime."|".$res->endtime;
              $seq_count++;
            }
          }
        }
      }

      return isset($sched[$sched_count]) ? $sched[$sched_count] : "|";
    }

    function vlWholeDayApproved($employeeid, $date) {
        $q = $this->db->query(" SELECT a.status AS `status`, a.datefrom, a.dateto
                                FROM leave_app_base a
                                INNER JOIN leave_app_emplist b 
                                ON a.id = b.base_id
                                WHERE '$date' 
                                BETWEEN a.datefrom 
                                AND a.dateto 
                                AND b.employeeid='$employeeid' 
                                AND a.status = 'APPROVED'");
        
        if (!$q) {
            log_message('error', 'No data:' . $this->db->last_query());
            return false;
        }
        
        return $q->num_rows() > 0;
    }

    function otherApprovedLeave($employeeid, $date) {
        $q = $this->db->query("SELECT a.type as `type`, a.datefrom, a.dateto
                               FROM leave_app_base a
                               LEFT JOIN leave_app_emplist b
                               ON a.id = b.base_id
                               WHERE b.employeeid = '{$employeeid}'
                               AND a.type IN ('BL', 'EL', 'VL', 'SL', 'OS')
                               AND '$date' 
                               BETWEEN a.datefrom 
                               AND a.dateto 
                               AND a.nodays != '0.5' 
                               AND a.`status` = 'APPROVED'");
        if (!$q) {
            log_message('error', 'No data: ' . $this->db->last_query());
            return false;
        }
        
        return $q->row();
    }
    

    function displayOBSched($base_id='', $date='',$sched_count=''){
      $sched = array();
      $leave_d = $this->db->query("SELECT a.base_id FROM ob_app_emplist a INNER JOIN ob_app_base b ON a.base_id = b.id WHERE a.id = '$base_id'");
      if($leave_d->num_rows() > 0){
        $leave_id = $leave_d->row()->base_id;
        $employeeid = $leave_d->row()->employeeid;
        $leave_sched = $this->db->query("SELECT * FROM ob_schedref WHERE base_id = '$leave_id' ");
        if($leave_sched->num_rows() > 0){
          $dateactive = $leave_sched->row()->dateactive;
          $schedule = $this->db->query("SELECT * FROM employee_schedule_history WHERE dateactive = '$dateactive' AND idx  = DATE_FORMAT('$date','%w') AND employeeid = '$employeeid' ");
          if($schedule->num_rows() > 0){
            $seq_count = 1;
            foreach($schedule->result() as $res){
              $sched[$seq_count] = $res->starttime."|".$res->endtime;
              $seq_count++;
            }
          }
        }
      }

      return isset($sched[$sched_count]) ? $sched[$sched_count] : "|";
    }

    function displayChangeSchedApp($employeeid='',$date=''){
        $return = '';
        $query = $this->db->query("SELECT a.id,a.status FROM change_sched_app_emplist_items a INNER JOIN change_sched_app b ON a.base_id = b.id WHERE '$date' BETWEEN b.dfrom AND b.dto AND a.employeeid='$employeeid' AND a.status = 'APPROVED'"); 
        if($query->num_rows() > 0){
            $return = "APPROVED CHANGE SCHEDULE APPLICATION";
        }
        return $return;
    }

    //ADDED 07-06-17 SERVICE CREDIT
    function displayServiceCredit($eid="",$stime='',$etime='',$date="")
    {

        $service_credit = '';
        $time_aff = $stime.'|'.$etime;
        
        $query = $this->db->query("SELECT a.*,b.* FROM sc_app_use a LEFT JOIN sc_app_use_emplist b ON a.id = b.base_id WHERE b.employeeid='$eid' AND a.date = '$date' AND b.status = 'APPROVED'");
        
        if($query->num_rows() > 0){
            foreach($query->result() as $row)
            {
                $arr_sched_aff = array();
                $service_credit = $row->needed_service_credit;

                if($service_credit == 0.5 && $row->sched_affected){
                    $arr_sched_aff = explode(',', $row->sched_affected);
                }

                if($service_credit == 0.5 && sizeof($arr_sched_aff) > 0){
                    if(!in_array($time_aff, $arr_sched_aff)){
                        $service_credit = '';
                    }
                }

            }
        }
        
        return $service_credit;
    }
    

    //Service Credit 
    function displayServiceCreditRemarks($eid,$stime,$etime,$date)
    {
        $return = '';
        $query = $this->db->query("SELECT DISTINCT a.otype FROM timesheet a  INNER JOIN sc_app_use_emplist b ON(b.`employeeid` = a.`userid`) WHERE b.`status` = 'APPROVED' AND DATE(timein) = '$date' AND DATE(timeout) = '$date'  AND  b.employeeid='$eid' ORDER BY timein ASC");
        if ($query->num_rows() > 0) {
            $return = $query->row(0)->otype;
        }
        // echo $this->db->last_query();
        return $return;
    }

    
    /*
     * Leave
     */
    function displayPendingApp($eid="",$date="",$absent=""){
        $return="";
        $query1 = $this->db->query("SELECT a.id,b.type FROM leave_app_emplist a INNER JOIN leave_app_base b ON a.base_id = b.id WHERE '$date' BETWEEN b.datefrom AND b.dateto AND a.employeeid='$eid' AND a.status = 'PENDING'");
        if($query1->num_rows() > 0){  
            $desc_q = $this->db->query("SELECT description FROM code_request_form WHERE code_request='{$query1->row(0)->type}'");
            if($desc_q->num_rows() > 0) $return.=($return?", ".$desc_q->row(0)->description." APPLICATION":$desc_q->row(0)->description." APPLICATION");
            else $return.=($return?", LEAVE APPLICATION":"LEAVE APPLICATION");
        }
        $query1 = $this->db->query("SELECT a.id,b.type FROM ob_app_emplist a INNER JOIN ob_app b ON a.base_id = b.id WHERE '$date' BETWEEN b.datefrom AND b.dateto AND a.employeeid='$eid' AND a.status = 'PENDING' AND obtypes != '2'");
        if($query1->num_rows() > 0){  
            $obtype = $query1->row(0)->type;
            $obtypedesc = ($obtype == 'CORRECTION' ? "CORRECTION FOR TIME IN/OUT APPLICATION":($obtype == 'ABSENT' ? "ABSENT APPLICATION":"OFFICIAL BUSINESS APPLICATION"));

            $return.=($return?", ".$obtypedesc:$obtypedesc);
        }
        $query2 = $this->db->query("SELECT id FROM seminar_app WHERE '$date' BETWEEN datesetfrom AND datesetto AND applied_by='$eid' AND status = 'PENDING'");
        if($query2->num_rows() > 0){  
            $return.=($return?", SEMINAR APPLICATION":"SEMINAR APPLICATION");
        }
        $query3 = $this->db->query("SELECT a.id FROM ot_app_emplist a INNER JOIN ot_app b ON a.base_id = b.id WHERE '$date' BETWEEN b.dfrom AND b.dto AND a.employeeid='$eid' AND a.status = 'PENDING'");
        if($query3->num_rows() > 0){  
            $return.=($return?", OVERTIME APPLICATION":"OVERTIME APPLICATION");
        }
        $query4 = $this->db->query("SELECT a.id FROM change_sched_app_emplist_items a INNER JOIN change_sched_app b ON a.base_id = b.id WHERE '$date' BETWEEN b.dfrom AND b.dto AND a.employeeid='$eid' AND a.status = 'PENDING'"); 
        if($query4->num_rows() > 0){  
            $return.=($return?", CHANGE SCHEDULE APPLICATION":"CHANGE SCHEDULE APPLICATION");
        }

        $query4 = $this->db->query("SELECT a.id FROM sc_app_emplist a INNER JOIN sc_app b ON a.base_id = b.id WHERE `date`='$date' AND a.employeeid='$eid' AND a.status = 'PENDING'"); 
        if($query4->num_rows() > 0){  
            $return.=($return?", SERVICE CREDIT APPLICATION":"SERVICE CREDIT APPLICATION");
        }
        
        return $return;
    }

    function displayPendingOBWfh($eid, $date){
        $obtypedesc = "";
        $q_ob = $this->db->query("SELECT a.id,b.type FROM ob_app_emplist a INNER JOIN ob_app b ON a.base_id = b.id INNER JOIN ob_timerecord c ON b.id = c.base_id WHERE '$date' BETWEEN b.datefrom AND b.dateto AND a.employeeid='$eid' AND t_date = '$date' AND a.status = 'PENDING' AND obtypes = '2' AND c.status = 'APPROVED'");
        if($q_ob->num_rows() > 0){  
            $obtype = $q_ob->row(0)->type;
            $obtypedesc = "OFFICIAL BUSINESS APPLICATION";

        }
        
        return $obtypedesc;
    }

    function displayApprWholeDayOBApp($eid, $date){
        // echo"<pre>;"; print_r($date);
        $query = $this->db->query("SELECT b.status AS `status`, b.datefrom, b.dateto
                                   FROM ob_app_emplist a
                                   INNER JOIN ob_app b 
                                   ON a.base_id = b.id 
                                   WHERE '$date' 
                                   BETWEEN b.datefrom 
                                   AND b.dateto 
                                   AND a.employeeid='$eid' 
                                   AND b.status = 'APPROVED'
                                   AND b.type = 'DA'
                                   ");
         if (!$query) {
            log_message('error', 'No data: ' . $this->db->last_query());
            return false;
        }
        
        return $query->row();
    }
    /*
     * Overtime
     */

    function displayOtforReport($eid="",$date=""){
        $otTotal = $tstart = $tend = $status = "";
        $query = $this->db->query("SELECT  a.status, b.tstart, b.tend, b.total, b.approved_total
          FROM ot_app_emplist a
          INNER JOIN ot_app b ON a.`base_id`=b.`id`
          INNER JOIN employee c ON a.employeeid=c.employeeid
          WHERE ( '$date' BETWEEN b.`dfrom` AND b.`dto`) AND a.status <> 'PENDING' AND a.employeeid='$eid'"); 
       if($query->num_rows() > 0){
            foreach($query->result() as $value){
                $otTotal = ($value->approved_total) ? $value->approved_total : $value->total;
                $tstart = $value->tstart;
                $tend = $value->tend;
                $status = $value->status;
            }
        }
        
        return array($otTotal, $tstart, $tend, $status);
    }

    function displayOt($eid="",$date="",$hasSched=true){
        $otreg = $otrest = $othol =  0;
        $otstat = '';
        // $query = $this->db->query("SELECT a.*,b.* FROM ot_app a LEFT JOIN ot_app_emplist b ON a.id = b.base_id WHERE b.employeeid='$eid' AND '$date' BETWEEN a.dfrom AND a.dto AND b.status = 'APPROVED'");

        // $query = $this->db->query("
        //                             SELECT tstart,tend,total,status
        //                             FROM overtime_request
        //                             WHERE employeeid='$eid' AND ('$date' BETWEEN dfrom AND dto) AND `status` = 'APPROVED' 
        //                         ");
        $query = $this->db->query("
                                    SELECT
                                    tstart,
                                    tend,
                                    SEC_TO_TIME(SUM(TIME_TO_SEC(grand_total))) AS total,
                                    STATUS
                                    FROM
                                    group_overtime AS go
                                    inner join overtime_request AS oreq
                                        ON oreq.`aid` = go.`base_id`
                                    WHERE oreq.employeeid = '$eid'
                                    AND (
                                        '$date' BETWEEN oreq.dfrom
                                        AND oreq.dto
                                    )
                                    AND oreq.`status` = 'APPROVED'
                                ");


       if($query->num_rows() > 0){
            foreach($query->result() as $value){
                if      ($hasSched)  $otreg += $this->attcompute->exp_time($value->total);
                else                 $otrest += $this->attcompute->exp_time($value->total);
                if($hasSched) $otstat = isset($value->status) ? $value->status : ""; // LOLA
                if($this->isHoliday($date)){
                    $deptid = $this->employee->getindividualdept($eid);
                    $teachingtype = $this->employee->getempteachingtype($eid);
                    $holiday = $this->attcompute->isHolidayNew($eid, $date,$deptid, "", "", $teachingtype); 
                    if($holiday){
                        $otreg = $otrest = 0;
                        $othol += $this->attcompute->exp_time($value->total);
                    }
                }
            }
        }
        
        $otreg = ($otreg) ? $this->attcompute->sec_to_hm($otreg) : 0;
        $otrest = ($otrest) ? $this->attcompute->sec_to_hm($otrest) : 0;
        $othol = ($othol) ? $this->attcompute->sec_to_hm($othol) : 0;
        $otstat = ($otstat) ? $otstat : "";
        return array($otreg,$otrest,$othol,$otstat);
    }

    function displayOTApp($eid="", $date=""){
        $query = $this->db->query(" SELECT a.office_hour, a.approved_total, b.status, b.dfrom, b.dto
                                    FROM group_overtime a
                                    LEFT JOIN ot_app b
                                    ON a.base_id = b.id
                                    LEFT JOIN `ot_app_emplists` c
                                    ON b.id = c.base_id
                                    WHERE '$date'
                                    BETWEEN b.dfrom 
                                    AND b.dto 
                                    AND c.employeeid = '$eid';");
        return $query->row();      
    }

    function displayOtCollege($eid="",$date="",$holiday='',$holiday_type=''){
        $otreg = $otsat = $otsun = $othol = 0;
        $wdname = date("l",strtotime($date));
        // $query = $this->db->query("SELECT a.*,b.* FROM ot_app a LEFT JOIN ot_app_emplist b ON a.id = b.base_id WHERE b.employeeid='$eid' AND '$date' BETWEEN a.dfrom AND a.dto AND status = 'APPROVED'");
        $query = $this->db->query("SELECT * FROM overtime_request WHERE employeeid='$eid' AND '$date' BETWEEN dfrom AND dto AND STATUS = 'APPROVED'");

        if($query->num_rows() > 0){
            foreach ($query->result() as $key => $row) {

                if($holiday && in_array($holiday_type, array('1','2','4'))){
                    $othol += $this->exp_time($row->total);
                }else{
                    if      ($wdname == "Saturday") $otsat += $this->exp_time($row->total);
                    else if ($wdname == "Sunday")   $otsun += $this->exp_time($row->total);
                    else                            $otreg += $this->exp_time($row->total);
                }

            }
        }

        $otreg = $otreg != 0 ? $this->sec_to_hm($otreg) : 0; 
        $otsat = $otsat != 0 ? $this->sec_to_hm($otsat) : 0; 
        $otsun = $otsun != 0 ? $this->sec_to_hm($otsun) : 0; 
        $othol = $othol != 0 ? $this->sec_to_hm($othol) : 0; 
        
        return array($otreg,$otsat,$otsun,$othol);
    }

    ///< @Angelica -- based on new ot setup
    function getOvertime($employeeid='',$date='',$hasSched=true,$code_holtype=''){
        // need time ng ot
        // check if weekend

        //TODO : NIGHT_DIFF

        $ot_list = array();
        $excess_limit = 8*60*60;

        $dayofweek = date('N',strtotime($date));
        $isWeekend = in_array($dayofweek, array('6','7')) ? true : false;

        $ot_type = '';
        if($hasSched) $ot_type = 'WITH_SCHED';
        if($hasSched && $isWeekend) $ot_type = 'WITH_SCHED_WEEKEND';
        if(!$hasSched) $ot_type = 'NO_SCHED';

        $holiday_type = 'NONE';
        if($code_holtype){
           /* if($code_holtype == 1)  $holiday_type = 'REGULAR';
            elseif($code_holtype != 1) $holiday_type = 'SPECIAL';*/
            $holiday_type = $code_holtype;
        }

        if (strpos($holiday_type, 'SPECIAL ') !== false) {
            $holiday_type = "SPECIAL";
        }


        $ot_q = $this->db->query("
                                    SELECT tstart,tend,total
                                    FROM overtime_request
                                    WHERE employeeid='$employeeid' AND ('$date' BETWEEN dfrom AND dto) AND `status` = 'APPROVED' 
                                ");

        foreach ($ot_q->result() as $key => $row) {
            $isExcess = false;
            $excess = 0;
            $ottime = $this->exp_time($row->total);

            if($ottime > $excess_limit){
                $excess = $ottime - $excess_limit;
                $ottime = $excess_limit;
            }

            if($excess > 0) $isExcess = true;

            /*for multiple apply of ot*/
            if(isset($ot_list[$ot_type][$holiday_type])){
                $ot_list[$ot_type][$holiday_type][0] += $ottime;
                if($isExcess) $ot_list[$ot_type][$holiday_type][1] += $excess;
            }else{
                $ot_list[$ot_type][$holiday_type][0] = $ottime;
                if($isExcess) $ot_list[$ot_type][$holiday_type][1] = $excess;
            }
        }
        // echo '<pre>'.$date;
        // print_r($ot_list);
        // echo '</pre>';

        return $ot_list;
    }

    function constructOTlist($ot_list,$ot_list_tmp){
        foreach ($ot_list_tmp as $ot_type => $det) {
            foreach ($det as $ot_hol_type => $ex_det) {
                foreach ($ex_det as $isExcess => $ot_hours) {
                    if(!isset($ot_list[$ot_type][$ot_hol_type][$isExcess])) $ot_list[$ot_type][$ot_hol_type][$isExcess] = 0;
                    $ot_list[$ot_type][$ot_hol_type][$isExcess] += $ot_hours;
                }
            }
        }
        return $ot_list;
    }
    
    /*
     * Holiday
     */
    function isHoliday($date=""){
        $sql = $this->db->query("SELECT date_from,date_to FROM code_holiday_calendar WHERE '$date' BETWEEN date_from AND date_to");
        if($sql->num_rows() > 0)  return true;
        else                      return false;
    }
    
    //Added 5-31-17 Holiday With Pay
    function isHolidayWithpay($date=""){
        $return="";
        $sql = $this->db->query("SELECT a.withPay
        FROM code_holiday_type a
        LEFT JOIN code_holidays b ON a.`holiday_type` = b.holiday_type
        LEFT JOIN code_holiday_calendar c ON b.`holiday_id` = c.holiday_id
        WHERE '$date' BETWEEN c.date_from AND c.date_to");
        foreach($sql->result() as $row)
        {
            $return = $row->withPay;
        }
        return $return;
    }
    
    function holidayInfo($date=""){
        $return=array();
        $sql = $this->db->query("SELECT a.withPay, a.holiday_type, a.description, b.hdescription, b.code, a.holiday_rate,c.sched_count
        FROM code_holiday_type a
        LEFT JOIN code_holidays b ON a.`holiday_type` = b.holiday_type
        LEFT JOIN code_holiday_calendar c ON b.`holiday_id` = c.holiday_id
        WHERE '$date' BETWEEN c.date_from AND c.date_to");
        foreach($sql->result() as $row)
        {
            $return["holiday_type"] = $row->holiday_type;
            $return["withPay"] = $row->withPay;
            $return["type"] = $row->description;
            $return["description"] = $row->hdescription;
            $return["code"] = $row->code;
            $return["holiday_rate"] = $row->holiday_rate;
            $return["sched_count"] = $row->sched_count;
        }
        return $return;
    }

    function checkHolidayToday($date) {
        $query = $this->db->query("SELECT a.hdescription
                                   FROM code_holiday_calendar a 
                                   INNER JOIN code_holidays b 
                                   ON a.holiday_id = b.holiday_id 
                                   WHERE '$date' BETWEEN a.date_from AND a.date_to");
    
        if ($query === false) {
            return null;  
        }

        return $query->num_rows() > 0;
    }

    function isHolidayNew($empid,$date,$deptid,$campus="",$halfday="",$teachingtype=""){
        $where_clause = "";
        if($teachingtype && $teachingtype!="all") $where_clause = " AND teaching_type = '$teachingtype'";
        $sql = $this->db->query("SELECT a.holiday_id,a.date_from,a.date_to FROM code_holiday_calendar a INNER JOIN code_holidays b ON a.holiday_id = b.holiday_id WHERE '$date' BETWEEN a.date_from AND a.date_to AND (a.halfday = '$halfday' OR a.halfday IS NULL) ");
        if($sql->num_rows() > 0){

            
            $paymentType = "";
            $holiday_id = $sql->row(0)->holiday_id;
            $query = $this->db->query("SELECT * from employee where employeeid = '{$empid}'");
            // echo "<pre>";print_r($empid);die;
            $employmentstat = $query->row(0)->employmentstat;
            $statusemp = $this->db->query("SELECT * from code_status where description = '{$employmentstat}'");
            if ($statusemp->num_rows() > 0) {
                $employmentstat = $statusemp->row(0)->code;
            }
            $campusid = $query->row(0)->campusid;
            $teachingtype = $query->row(0)->teachingtype;
            $holiday = $this->db->query("SELECT * FROM code_holidays WHERE holiday_id = '$holiday_id'")->result();

            $Ptype = $this->db->query("SELECT fixedday FROM payroll_employee_salary WHERE employeeid = '{$empid}'");

            // $isAllowed = "SELECT 1 
            //             FROM code_holiday_calendar a
            //             LEFT JOIN code_holidays b ON a.holiday_id = b.holiday_id
            //             LEFT JOIN holiday_inclusions c ON b.holiday_id = c.holi_cal_id
            //             WHERE FIND_IN_SET('$empid', b.allowed) 
            //             AND '$date' BETWEEN a.date_from AND a.date_to";
            if ($Ptype->num_rows() > 0) {
                $paymentType = $Ptype->row(0)->fixedday;
            }

            if(isset($holiday[0]->campus)){

                if ($holiday[0]->campus == "All" OR $holiday[0]->campus == "" OR $holiday[0]->campus == $campusid) {

                    if ($holiday[0]->teaching_type == "all" OR $holiday[0]->teaching_type == $teachingtype) {

                        if ($holiday[0]->payment_type == "all" OR $holiday[0]->payment_type == $paymentType) {

                            $que = $this->db->query("SELECT status_included from holiday_inclusions where holi_cal_id = '{$holiday_id}' AND dept_included = '{$deptid}' AND status_included IS NOT NULL");
    
                            if($que->num_rows() > 0)
                            {
                                $return = false;
                                foreach(explode(", ",$que->row(0)->status_included) as $k => $v)
                                {
                                    $include = explode("~",$v);
                                    if(isset($include[1]) && $include[1] == $employmentstat)
                                    {
                                        $return = $include[1];
                                        break;
                                    }
                                    // else{
                                    //     $isEmployeeAllowed = $this->db->query($isAllowed);
                                    //     return $isEmployeeAllowed->num_rows() > 0;
                                    // }
                                }
                                return $return;
                            }
                        }
                    }
                    // else{
                    //     $isEmployeeAllowed = $this->db->query($isAllowed);
                    //     return $isEmployeeAllowed->num_rows() > 0;
                    // }
                }
            }

            else { return false; }
        }
        else{   return false;}
    }

    function isHolidayDetailed($empid,$date,$deptid,$campus="",$halfday="",$teachingtype=""){
        $isAllowed = "SELECT 1 
        FROM code_holiday_calendar a
        LEFT JOIN code_holidays b ON a.holiday_id = b.holiday_id
        LEFT JOIN holiday_inclusions c ON b.holiday_id = c.holi_cal_id
        WHERE FIND_IN_SET('$empid', b.allowed) AND NOT FIND_IN_SET('$empid', b.prohibited)
        AND '$date' BETWEEN a.date_from AND a.date_to";
        
        $isProhibited = "SELECT 1 
        FROM code_holiday_calendar a
        LEFT JOIN code_holidays b ON a.holiday_id = b.holiday_id
        LEFT JOIN holiday_inclusions c ON b.holiday_id = c.holi_cal_id
        WHERE FIND_IN_SET('$empid', b.prohibited)
        AND '$date' BETWEEN a.date_from AND a.date_to";

        $isEmployeeAllowed = $this->db->query($isAllowed)->num_rows();
        $isEmployeeProhibited = $this->db->query($isProhibited)->num_rows();
        if($isEmployeeAllowed) return $this->db->query($isAllowed)->num_rows();
        elseif($isEmployeeProhibited) return false;
        else return $this->isHolidayNew($empid, $date,$deptid, "", "", $teachingtype);
        // return $isEmployeeAllowed->num_rows() > 0;
    }


    function displaySCUsageAttendance($eid, $date, $stime, $etime){
        $sc_use = $isHalfDay = $sc_app_id = $sched_affected = "";
        $official_time = $stime.'|'.$etime;
        $query = $this->db->query("SELECT * FROM sc_app_use a LEFT JOIN sc_app_use_emplist b  ON a.id = b.base_id WHERE b.employeeid = '$eid' AND date = '$date' AND b.upstatus = 'APPROVED'");
        if($query->num_rows() > 0){
            $isHalfDay = $query->row()->ishalfday;
            $sc_app_id = $query->row()->id;
            $sched_affected = $query->row()->sched_affected;
            if($isHalfDay == 1){
                if($official_time != $sched_affected){
                    $sc_use = $isHalfDay = $sc_app_id = $sched_affected = "";
                }else{
                    $sc_use = 0.5;
                }
            }else{
                $sc_use = 1;
            }
        }
        // if($sc_use){
        //     echo $isHalfDay.'~~'.$sc_use.'~~'.$date;
        // }
        
        return array($sc_use, $isHalfDay, $sc_app_id);
    }


    function displaySCAttendance($eid, $date, $stime, $etime) {
        $sc = 0;
        $official_time = $stime . '|' . $etime;
        $sql = "SELECT * FROM sc_app a LEFT JOIN sc_app_emplist b ON a.id = b.base_id WHERE b.employeeid = ? AND date = ? AND b.upstatus = ?";
        
        $query = $this->db->query($sql, [$eid, $date, 'APPROVED']);
        
        if (!$query) {
            echo 'Error executing query: ' . $this->db->error()['message'];
            echo 'Last SQL query: ' . $this->db->last_query();
            return $sc;
        }
        
        if ($query->num_rows() > 0) {
            $sc = 1;
        }
        
        return $sc;
    }


    //----- recently added  21-11-2024 ----- // 
    function displayCTOUsageAttendance($eid, $date, $stime, $etime){
        $total = $isHalfDay = $cto_id = $sched_affected = "";
        $official_time = $stime.'|'.$etime;
        
        $query = $this->db->query("SELECT * FROM employee_cto_usage WHERE employeeid = '$eid' AND date_applied = '$date' AND app_status = 'APPROVED'");
        
        if ($query !== false && $query->num_rows() > 0) {
            $total = $query->row()->total;
            $isHalfDay = $query->row()->ishalfday;
            $cto_id = $query->row()->id;
            $sched_affected = $query->row()->sched_affected;
    
            if ($isHalfDay == 1) {
                if ($official_time != $sched_affected) {
                    $total = $isHalfDay = $cto_id = $sched_affected = "";
                }
            }
        } else {
            log_message('error', 'CTO Usage query failed or no records found for Employee ID: ' . $eid . ' on ' . $date);
        }
    
        return array($total, $isHalfDay, $cto_id, $sched_affected);
    }

    function displayCOC($eid="", $date="", $hasSched=true) {
        $query = $this->db->query("
            SELECT b.id
            FROM overtime_request a
            INNER JOIN ot_app b ON a.aid = b.id
            WHERE a.employeeid = ? AND (? BETWEEN a.dfrom AND a.dto) AND a.status = 'APPROVED' AND b.ot_type = 'CTO'
        ", [$eid, $date]);
    
        if ($query === false) {
            log_message('error', 'Failed to execute displayCOC query. ' . $this->db->last_query());
            return 0; 
        }
    
        return $query->num_rows();
    }

    function getServiceCreditStatus($employeeid, $date) {
        $query = $this->db->query("SELECT b.upstatus as app_status FROM sc_app a LEFT JOIN sc_app_emplist b ON a.id = b.base_id WHERE b.employeeid = '$employeeid' AND DATE(date_applied) = '$date' AND b.upstatus IN ('PENDING', 'APPROVED')");

        if ($query === false) {
            log_message('error', 'Failed to execute Service Credit query. ' . $this->db->last_query());
            return 0; 
        }
    
        return $query->num_rows();
       
    }

    function displayLogTimeOutsideOT($eid="",$date=""){
        $haslog = true;
        $timein = $timeout = $otype = "";
        $overload = 0;
        $time_logs = array();

        $tbl = "timesheet";

        $query = $this->db->query("
                SELECT timein,timeout,otype FROM $tbl 
                WHERE userid='$eid' 
                AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                ORDER BY timein ASC");
        if($query->num_rows() > 0){
            foreach ($query->result() as $key => $value) {
                $time_logs[$key]['timein'] = date("H:i:s", strtotime($value->timein));
                $time_logs[$key]['timeout'] = date("H:i:s", strtotime($value->timeout));
            }
        }
        

        $overload_logs = $time_logs;
        $sched = $this->attcompute->displaySched($eid,$date);
        foreach($sched->result() as $rsched){
            $stime = $rsched->starttime;
            $etime = $rsched->endtime; 
            foreach ($time_logs as $key => $value) {
                $timein = $value['timein'];
                $timeout = $value['timeout'];
                if($timein <= $etime && $timeout >= $stime){
                    unset($overload_logs[$key]);
                }
            }
            
        }
       
        foreach ($overload_logs as $key => $value) {
            $start = strtotime($date." ".$value['timein']);
            $end = strtotime($date." ".$value['timeout']);
            $overload += ($end - $start) / 60;
        }
        return $overload;
    }
    
    // -----
    
    function isHolidayNewAttendanceReport($empid,$date,$deptid,$campus="",$halfday="",$teachingtype=""){
        $where_clause = "";
        if($teachingtype && $teachingtype!="all") $where_clause = " AND teaching_type = '$teachingtype'";
        $sql = $this->db->query("SELECT holiday_id,date_from,date_to FROM code_holiday_calendar WHERE '$date' BETWEEN date_from AND date_to ");
        if($sql->num_rows() > 0){
            $paymentType = "";
            $holiday_id = $sql->row(0)->holiday_id;
            $query = $this->db->query("SELECT * from employee where employeeid = '{$empid}'");
            $employmentstat = $query->row(0)->employmentstat;
            $campusid = $query->row(0)->campusid;
            $teachingtype = $query->row(0)->teachingtype;
            $holiday = $this->db->query("SELECT * FROM code_holidays WHERE holiday_id = '$holiday_id'")->result();
            $Ptype = $this->db->query("SELECT fixedday FROM payroll_employee_salary WHERE employeeid = '{$empid}'");
            if ($Ptype->num_rows() > 0) {
                $paymentType = $Ptype->row(0)->fixedday;
            }
            if(isset($holiday[0]->campus)){
                if ($holiday[0]->campus == "All" OR $holiday[0]->campus == $campusid) {
                    if ($holiday[0]->teaching_type == "all" OR $holiday[0]->teaching_type == $teachingtype) {
                        if ($holiday[0]->payment_type == "all" OR $holiday[0]->payment_type == $paymentType) {
                            $que = $this->db->query("SELECT status_included from holiday_inclusions where holi_cal_id = '{$holiday_id}' AND dept_included = '{$deptid}' AND status_included IS NOT NULL");
                            if($que->num_rows() > 0)
                            {
                                $return = false;
                                foreach(explode(", ",$que->row(0)->status_included) as $k => $v)
                                {
                                    $include = explode("~", $v);
                                    if (count($include) > 1 && $include[1] == $employmentstat) {
                                        $return = $include[1];
                                        break;
                                    }
                                }
                                return $return;
                            }
                        }
                    }
                }
            }

            else { return false; }
        }
        else{   return false;}
    }
    
    /*
     * Attendance Confirmed & Vice Versa
     */
    function att_confirmed($empid="",$date=""){
        $sql = $this->db->query("SELECT * FROM attendance_confirmed WHERE logdate = '$date' AND employeeid='$empid'");
        return $sql;
    }
    
    function att_nt_confirmed($empid="",$date=""){
        $sql = $this->db->query("SELECT * FROM attendance_confirmed_nt WHERE logdate = '$date' AND employeeid='$empid'");
        return $sql;
    }
    
    /*
     *  Total Time 
     */ 
    function exp_time($time) { //explode time and convert into seconds
        $time = explode(':', $time);
        $h = $m = 0;
        if(isset($time[0]) && is_numeric($time[0])) { $h = $time[0];} else{ $h = 0;}
        if(isset($time[1]) && is_numeric($time[1])) { $m = $time[1]; }else {$m = 0;}
        $time = ($h * 3600) + ($m * 60);


        return $time;
    }
    

    function sec_to_hm($time) { //convert seconds to hh:mm
        $time = (int) $time;
        if(is_numeric($time)){
            $hour = floor($time / 3600);
            $minute = strval(floor(($time % 3600) / 60));
            if ($minute == 0) {
                $minute = "00";
            } else {
                $minute = $minute;
            }

            if ($hour == 0) {
                $hour = "00";
            } else {
                $hour = $hour;
            }
            $time = $hour . ":" . str_pad($minute,2,'0',STR_PAD_LEFT);
            return $time;
        }
    }

    function secondsToDecimalHours($seconds) {
        if (!is_numeric($seconds)) {
            return 0;
        }
        $hours = $seconds / 3600;
        return round($hours, 2);
    }

    //Added 6-7-2017 DISPLAY OVERLOAD
    // function displayOverloadTime($stime,$etime,$login,$logout) {
        // $st = $this->exp_time(date("H:i",strtotime($stime)));
        // $et = $this->exp_time(date("H:i",strtotime($etime)));
        // $li = $this->exp_time(date("H:i",strtotime($login)));
        // $lo = $this->exp_time(date("H:i",strtotime($logout)));
        
        // $return =  ($lo - $li) - ($et - $st);
        // $return = $this->sec_to_hm($return);
        // return $return;
    // }
    
    function displayOverloadTime($stime,$etime,$lateutlab) {
        $st = $this->exp_time(date('H:i',strtotime($stime)));
        $et = $this->exp_time(date('H:i',strtotime($etime)));
        $lab = 0;
        
        if($lateutlab)
        {
            $lab = $this->exp_time(date('H:i',strtotime($lateutlab)));
        }
        
        $return =   ($et - $st) - $lab;
        // $return = $this->sec_to_hm($return);
        return $return;
    }
    
        
    //Added 6-7-2017
    function getLastDayOfWeek($eid=""){
        $return = "";
        $query = $this->db->query("SELECT DISTINCT(dayofweek) FROM employee_schedule_history WHERE employeeid = '$eid' ORDER BY idx DESC LIMIT 1")->result();
       if($query)
       {
       switch($query[0]->dayofweek)
       {
           case "M": $return = "Monday"; break;
           case "T": $return = "Thusday"; break;
           case "W": $return = "Wednesday"; break;
           case "TH": $return = "Thursday"; break;
           case "F": $return = "Friday"; break;
           case "S": $return = "Saturday"; break;
           case "SUN": $return = "Sunday"; break;
       }
       }
        
        
        return $return; 
    }
    
    function getFirstDayOfWeek($eid=""){
        $return = "";
        $query = $this->db->query("SELECT DISTINCT(dayofweek) FROM employee_schedule_history WHERE employeeid = '$eid' ORDER BY idx ASC LIMIT 1")->result();
       
       if($query)
       {
       switch($query[0]->dayofweek)
       {
           case "M": $return = "Monday"; break;
           case "T": $return = "Thusday"; break;
           case "W": $return = "Wednesday"; break;
           case "TH": $return = "Thursday"; break;
           case "F": $return = "Friday"; break;
           case "S": $return = "Saturday"; break;
           case "SUN": $return = "Sunday"; break;
       }
       }
        
        
        return $return; 
    }
    
    
    function getPastDayOverload($eid,$date,$firstDay,$edata){
        
        $return = "";
        $d = date("Y-m-d",strtotime($date) - (60*60));
        
        while ($d != $date){
            $sched = $this->displaySched($eid,$d);
            foreach($sched->result() as $rsched){
                $stime = $rsched->starttime;
                $etime = $rsched->endtime; 
                $type  = $rsched->leclab;
                
                // Holiday
                $holiday = $this->attcompute->isHoliday($d); 
                
                // logtime
                list($login,$logout,$q) = $this->attcompute->displayLogTime($eid,$d,$stime,$etime,$edata);
                
                // Leave
                list($el,$vl,$sl,$ol,$oltype)     = $this->attcompute->displayLeave($eid,$d);
                
                // Absent
                $absent = $this->attcompute->displayAbsent($stime,$etime,$login,$logout,$eid,$d);
                if($oltype == "ABSENT") $absent = $absent;
                else if($el || $vl || $sl || $ol || $holiday) $absent = "";
                
                // Late / Undertime
                list($lateutlec,$lateutlab,$tschedlec,$tschedlab) = $this->attcompute->displayLateUT($stime,$etime,$login,$logout,$type,$absent);
                if($el || $vl || $sl || $ol || $oltype || $holiday){
                    $lateutlec = $lateutlab = $tschedlec = $tschedlab = "";
                }
                
                if($holiday)
                {
                    $tempabsent = isset($tempabsent)?$tempabsent:"";
                    if($this->attcompute->isHolidayWithpay($d) == "YES")
                    {
                        if($tempabsent)
                        {
                            $absent = 1;
                        }
                    }
                    else
                    {
                        if(!$login && !$logout)
                        {
                            $absent = 1;
                        }
                    }
                }
                else
                {
                    $tempabsent = $absent;
                }
                
                if(!$absent && !$lateutlec)
                {
                    $return           += $this->attcompute->displayOverloadTime($stime,$etime,$lateutlab);
                }
                else
                {
                    $return += 0;
                }
                
            }
            $d = date('Y-m-d',strtotime($d . "+1 days"));
        }
        
        return $return; 
    }
    

    //ADDED 07-15-17 WITH LOG
    function withLog($eid="",$date = ""){
        $return = "";
        $query = $this->db->query("SELECT * FROM timesheet WHERE userid = '$eid' AND DATE(timein)  = DATE('$date') AND DATE(timein) = DATE('$date') ORDER BY timein");
        
        return $query; 
    }
    
    //ADDED 07-21-17 DISPLAY LOG TIME OF FLEXI SCHED
    function displayLogTimeFlexi($eid="",$date="",$tbl=""){
        $return = array();
        if($tbl == "NEW")   $tbl = "timesheet";
        else                $tbl = "timesheet_bak";
        // $query = $this->db->query("SELECT timein,timeout,otype FROM $tbl WHERE userid='$eid' AND DATE(timein)='$date' ORDER BY timein ASC");
        $query = $this->db->query("SELECT MIN(timein) AS timein,MAX(timeout) AS timeout,otype FROM $tbl WHERE userid='$eid' AND DATE(timein)='$date' ORDER BY timein ASC");

        if($query && $query->num_rows() > 0){
            foreach($query->result() as $row)
            {
                $timein = $row->timein;
                $timeout = $row->timeout;
                if($timein!=null || $timeout!=null){
                    if($timein=='0000-00-00 00:00:00') $timein = "";
                    if($timeout=='0000-00-00 00:00:00') $timeout = "";
                    array_push($return,array($timein,$timeout,$row->otype));
                }
            }
        }else{
            $query = $this->db->query("SELECT logtime FROM timesheet_trail WHERE userid='$eid' AND (DATE(logtime)='$date' OR DATE(localtimein)='$date') AND log_type = 'IN' ORDER BY logtime DESC");
            if($query && $query->num_rows() > 0){
                foreach($query->result() as $row)
                {
                    $logtime = ($row->logtime == "0000-00-00 00:00:00" ? $row->localtimein : $row->logtime);
                    if($logtime=='0000-00-00 00:00:00') $logtime = "";
                    array_push($return,array($logtime,"",""));
                }
            }   
            
        }
        
        return $return;
    }

    function getLogsPerDay($eid="",$date="",$tbl="", $is_add_time_trail = true){
        
        $return = array();
        if($tbl == "NEW")   $tbl = "timesheet";
        else                $tbl = "timesheet_bak";
        $query = $this->db->query("SELECT DISTINCT timein,timeout,otype FROM $tbl WHERE userid='$eid' AND DATE(timein)='$date' ORDER BY timein ASC");
        // $query = $this->db->query("SELECT MIN(timein) AS timein,MAX(timeout) AS timeout,otype FROM $tbl WHERE userid='$eid' AND DATE(timein)='$date' ORDER BY timein ASC");

        if($query && $query->num_rows() > 0){
            foreach($query->result() as $row)
            {
                if($row->timein!=null || $row->timeout!=null){
                    array_push($return,array($row->timein,$row->timeout,$row->otype));
                }
            }
        }
        
        $query = $this->db->query("SELECT DISTINCT logtime FROM timesheet_trail WHERE userid='$eid' AND (DATE(logtime)='$date' OR DATE(localtimein)='$date') AND log_type = 'IN' ORDER BY logtime DESC");
        if($query && $query->num_rows() > 0){
            foreach($query->result() as $row)
            {   
                if($is_add_time_trail) array_push($return,array(($row->logtime == "0000-00-00 00:00:00" ? $row->localtimein : $row->logtime),"",""));
            }
        }   
        
        return $return;
    }
    
    //ADDED 07-21-17 DISPLAY ABSENT OF FLEXI SCHED
    function displayAbsentFlexi($log="",$hours="",$mode="",$empid="",$dset="",$type='',$breaktime=0,$count_leave=0){
        $absent = "";
        $time = sprintf('%02d:%02d', (int) $hours, fmod($hours, 1) * 60);
        $h = date("H:i",strtotime($time));

        $hSTR = $this->exp_time($time);
        $breaktime = $breaktime * 60 * 60;

        if($mode == "day")
        {
            $totalHour= 0;

            if($count_leave == 0.50){
                $totalHour = ($hSTR-$breaktime)/2;
            }else{
                $totalHour = ($hSTR-$breaktime);
            }
            
            if(count($log) <= 0) $absent = $totalHour;
            else{

                if(isset($log[0][0])){
                    if($log[0][0] == null || $log[0][0] == '0000-00-00 00:00:00') $absent = $totalHour;
                }
                if(isset($log[0][1])){
                    if($log[0][1] == null || $log[0][1] == '0000-00-00 00:00:00') $absent = $totalHour;
                }

            }

            if( $absent > 0 ){
                $absent = $this->sec_to_hm($absent);
            }
        
            if($empid){
                $query = $this->db->query("SELECT * FROM attendance_absent_checker WHERE employeeid='$empid' AND scheddate = '$dset'");
                if($query->num_rows() > 0)  $absent = $h;
            }

        }
        return $absent;
    }
    
    //ADDED 07-21-17 DISPLAY LATE OF FLEXI SCHED
    // Teaching
    function displayLateUTFlexi($log="",$hours="",$mode="",$type="",$absent="",$breaktime=0,$count_leave=0){
        $lec = $lab  = $admin = $tschedlec = $tschedlab = $tschedadmin = "";
        $time = sprintf('%02d:%02d', (int) $hours, fmod($hours, 1) * 60);
        $h = date("H:i:s",strtotime($time));
        // $hSTR  = strtotime($h);
        $hSTR = $this->exp_time($time);
        $breaktime = $breaktime * 60 * 60;
        if($mode == "day")
        {
            if(count($log) > 0 && !$absent)
            {

                $login = $logout = $totalHour= 0;

                if($count_leave == 0.50){
                    $totalHour = ($hSTR-$breaktime)/2;
                }

                for($i = 0;$i < count($log);$i++)
                {
                    // if($log[$i][0]) $login = strtotime($log[$i][0]);
                    // if($log[$i][1]) $logout = strtotime($log[$i][1]);
              
                    if($log[$i][0]) $login = new DateTime($log[$i][0]);
                    if($log[$i][1]) $logout = new DateTime($log[$i][1]);

                    $duration = $login->diff($logout); //$duration is a DateInterval object
              
                    $duration = $this->exp_time($duration->format("%H:%I"));

                    $totalHour += $duration;
                }

         
                $diff = $hSTR - $totalHour;     
                

                if($diff >  (($hSTR-$breaktime)/2) && $diff <= ((($hSTR-$breaktime)/2)+$breaktime)){
                    $diff = ($hSTR-$breaktime)/2;
                }elseif($diff > ((($hSTR-$breaktime)/2)+$breaktime) || $totalHour > ((($hSTR-$breaktime)/2)+$breaktime) ){
                    $diff = $diff - $breaktime;
                }

                if( $diff > 0 ){

                    if($type == "LEC"){ 
                        $lec = $this->sec_to_hm($diff);
                    }elseif($type=="LAB"){
                        $lab = $this->sec_to_hm($diff);
                    }else{
                        $admin = $this->sec_to_hm($diff);
                    }
                }
            }elseif(count($log) == 0 && !$absent){
                $totalHour = 0;
                if($count_leave == 0.50){
                    $totalHour = (($hSTR-$breaktime)/2);
                }elseif($count_leave >= 1){
                    $totalHour = $hSTR;
                }

                $diff = $hSTR - $totalHour;     
    
                if($diff >  (($hSTR-$breaktime)/2) && $diff <= ((($hSTR-$breaktime)/2)+$breaktime)){
                    $diff = ($hSTR-$breaktime)/2;
                }elseif($diff > ((($hSTR-$breaktime)/2)+$breaktime) || $totalHour > ((($hSTR-$breaktime)/2)+$breaktime) ){
                    $diff = $diff - $breaktime;
                }

                if( $diff > 0 ){
                    if($type == "LEC"){ 
                        $lec = $this->sec_to_hm($diff);
                    }elseif($type=="LAB"){
                        $lab = $this->sec_to_hm($diff);
                    }else{
                        $admin = $this->sec_to_hm($diff);
                    }
                }
            }
            
            if($absent)
            {
                if($type == "LEC"){
                    $tschedlec  = $this->sec_to_hm($hSTR-$breaktime);
                }elseif($type=="LAB"){
                    $tschedlab  = $this->sec_to_hm($hSTR-$breaktime);
                }else{
                    $tschedadmin  = $this->sec_to_hm($hSTR-$breaktime);
                }
            }
        }
        return array($lec,$lab,$admin,$tschedlec,$tschedlab,$tschedadmin);
    }  
    // Non-Teaching
    function displayLateUTNTFlexi($log="",$hours="",$mode="",$absent="",$breaktime=0,$count_leave=0){
        $lec = $lab = $tschedlec = $tschedlab = "";
        $lateut = "";
        $time = sprintf('%02d:%02d', (int) $hours, fmod($hours, 1) * 60);
        $h = date("H:i:s",strtotime($time));
        $hSTR  = $this->exp_time($h);
        $breaktime = $breaktime * 60 * 60;

        if($mode == "day")
        {
            if(count($log) > 0 && !$absent)
            {
                $login = $logout = $totalHour= 0;

                if($count_leave == 0.50){
                    $totalHour = ($hSTR-$breaktime)/2;
                }
                
                for($i = 0;$i < count($log);$i++)
                {
                    if(isset($log[$i][0]) && isset($log[$i][1])){
                        if($log[$i][0] != '0000-00-00 00:00:00' && $log[$i][1] != '0000-00-00 00:00:00' && $log[$i][0] != '' && $log[$i][1] != ''){
                            if($log[$i][0]) $login = $this->exp_time(date("H:i:s",strtotime($log[$i][0])));
                            if($log[$i][1]) $logout = $this->exp_time(date("H:i:s",strtotime($log[$i][1])));
                        }
                    }

                    $totalHour += $logout - $login;
                }
                
                $diff = $hSTR - $totalHour;
                
                // $lateut = date('H:i', $diff);
                if($diff > 0){
                    $lateut = $this->sec_to_hm($diff);
                }
                

            }elseif(count($log) == 0 && !$absent){
                $totalHour = 0;
                if($count_leave == 0.50){
                    $totalHour = (($hSTR-$breaktime)/2);
                }elseif($count_leave >= 1){
                    $totalHour = $hSTR;
                }

                $diff = $hSTR - $totalHour;     
    
                if($diff >  (($hSTR-$breaktime)/2) && $diff <= ((($hSTR-$breaktime)/2)+$breaktime)){
                    $diff = ($hSTR-$breaktime)/2;
                }elseif($diff > ((($hSTR-$breaktime)/2)+$breaktime) || $totalHour > ((($hSTR-$breaktime)/2)+$breaktime) ){
                    $diff = $diff - $breaktime;
                }

                if( $diff > 0 ){
                    $lateut = $this->sec_to_hm($diff);
                }
            }
        }
        if($lateut == "00:00") $lateut = "";
        return $lateut;
    }
    
    //ADDED 07-21-17 DISPLAY LATE OF FLEXI SCHED
    function displayOverloadTimeFlexi($log="",$hours="",$mode="",$lateutlab="") {
        $return = "";
        if($mode == "day")
        {
            if(count($log) > 0)
            {
                $st = $et = $lab = 0;
                for($i = 0;$i < count($log);$i++)
                {
                    if($log[$i][0]) $st += $this->exp_time(date('H:i',strtotime($log[$i][0])));
                    if($log[$i][1]) $et += $this->exp_time(date('H:i',strtotime($log[$i][1])));
                }
                
                if($lateutlab)
                {
                    $lab = $this->exp_time(date('H:i',strtotime($lateutlab)));
                }
                
                $return =   ($et - $st) - $lab;
            }
        }
        return $return;
    }

    function getLogout($empid, $edata, $date){
        $logout = "";

        $tbl = "timesheet_bak";
        if($edata == "NEW") $tbl = "timesheet";

        $q_findLogTime = $this->db->query("SELECT MAX(timeout) as timeout FROM $tbl WHERE userid='$empid' AND (DATE_FORMAT(timein, '%Y-%m-%d') BETWEEN '$date' AND '$date' OR DATE_FORMAT(timeout, '%Y-%m-%d') BETWEEN '$date' AND '$date') ORDER BY timein DESC;");
        if($q_findLogTime){
            foreach ($q_findLogTime->result() as $res) $logout = $res->timeout; 
        }

        return $logout;
    }

    function getLogin($empid, $edata, $date){
        $login = "";

        $tbl = "timesheet_bak";
        if($edata == "NEW") $tbl = "timesheet";

        $q_findLogTime = $this->db->query("SELECT MIN(timein) as timein FROM $tbl WHERE userid='$empid' AND (DATE_FORMAT(timein, '%Y-%m-%d') BETWEEN '$date' AND '$date' OR DATE_FORMAT(timeout, '%Y-%m-%d') BETWEEN '$date' AND '$date') ORDER BY timein DESC;");
        if($q_findLogTime){
            foreach ($q_findLogTime->result() as $res){
                if($res->timein != "0000-00-00 00:00:00" && $res->timein) $login = $res->timein;
            } 

        }

        if(!$login){
            $q_findLogTime = $this->db->query("SELECT * FROM timesheet_trail WHERE userid='$empid' AND (DATE_FORMAT(logtime, '%Y-%m-%d')='$date' OR DATE_FORMAT(localtimein, '%Y-%m-%d')='$date') AND log_type='IN' ORDER BY logtime DESC;");
            if($q_findLogTime){
                foreach ($q_findLogTime->result() as $res){
                    if(($res->logtime != "0000-00-00 00:00:00" && $res->logtime) || ($res->localtimein !="0000-00-00 00:00:00" && $res->localtimein)) $login = ($res->logtime == "0000-00-00 00:00:00" ? $res->localtimein : $res->logtime);
                }                
            }
        }

        return $login;
    }

    function getOvertimeAmountPayed($empid, $data){
        $this->load->model('income');
        $this->load->model('overtime');
        $key = $ot_type = $ot_hours = '';
        $ot_amount = $ot_type = '';

        foreach($data as $key => $value){
            foreach($value as $ot_data){

                $emp_status = $this->extras->getEmploymentStatus($empid);
                $ot_workhours = $this->attcompute->sec_to_hm($ot_data[0]);
                $ot_workhours = $this->time->hoursToMinutes($ot_workhours);
                $getOvertimeSetup = $this->overtime->getOvertimeSetup($emp_status, $key);
                $percentage = number_format($getOvertimeSetup['percent'], 2) / 100;
                /*get employee hourly rate*/
                $emp_minutely = $this->income->getEmployeeSalaryRate($empid, "minutely");
                $ot_amount = $emp_minutely * $ot_workhours;

                $ot_amount = $ot_amount * $percentage;


                if($key == "WITH_SCHED" || $key == "WITH_SCHED_WEEKEND") $ot_type = "Regular Day";
                else if($key == "NO_SCHED") $ot_type = "Rest Day";
                /*end*/                
            }
        }

        return array($ot_amount, $ot_type);
    }

    function getOvertimeAmountDetailed($empid, $ot_details, $emp_ot=''){
        #echo "<pre>"; print_r($ot_details);
        $this->load->model('utils');
        $this->load->model('payrollcomputation');
        $this->load->model('time');
        $this->load->model('income');
        $ot_amount = 0;
        $ot_type = "";

        $rate_per_hour = ($this->income->getEmployeeSalaryRate1($empid, "daily") / 8);
        $rate_per_minute = $rate_per_hour / 60;
        $employeement_status = $this->extras->getEmploymentStatus($empid);
        $setup = $this->payrollcomputation->getOvertimeSetup($employeement_status);

        $percent = 100;
        foreach ($ot_details as $ot_type => $holiday_type_list) {
            foreach ($holiday_type_list as $holiday_type => $ot_info) {
                $ot_min = ($emp_ot) ? $emp_ot : $ot_info[0];
                $ot_min = $this->sec_to_hm($ot_min);
                $ot_min = $this->time->hoursToMinutes($ot_min);
                $sel_setup = (isset($ot_info[1])) ? 1 : 0;

                if(isset($setup[$employeement_status][$ot_type][$holiday_type][$sel_setup])) $percent = $setup[$employeement_status][$ot_type][$holiday_type][$sel_setup];
                $percent = $percent / 100;
                
                $minutely = $rate_per_minute * $percent;
                $ot_amount = $minutely * $ot_min;

                switch ($ot_type) {
                    case 'WITH_SCHED': case 'WITH_SCHED_WEEKEND':
                        $ot_type = "Regular Day";
                        break;
                    
                    case 'NO_SCHED':
                        $ot_type = "Rest Day";
                        break;
                }
            }
        }

        return array($ot_amount, $ot_type);
    }

    function insertOTListToArray($ot_save_list, $ot_list){
        if(count($ot_list)){
            foreach ($ot_list as $ot_type => $ot_data) {
                foreach ($ot_data as $holiday_type => $holiday_data) {
                    foreach ($holiday_data as $is_excess => $ot_time) {
                        $ot_save_list[] = array(
                            'ot_hours'=> $this->sec_to_hm($ot_time),
                            'ot_type' => $ot_type,
                            'holiday_type' => $holiday_type,
                            'is_excess' => $is_excess
                        );
                    }
                }
            }
        }
        
        return $ot_save_list;
    }

    function gettotalhours($empid='',$dfrom= "",$dto=""){
        $return = array();
        $query = $this->db->query("SELECT DATE_FORMAT(DATE_ADD(timein, INTERVAL(2-DAYOFWEEK(timein)) DAY),'%Y-%m-%d')AS datestart,
        DATE_FORMAT(DATE_ADD(timeout, INTERVAL(6-DAYOFWEEK(timeout)) DAY),'%Y-%m-%d')dateend,
        WEEK(timein)AS numweek,
        #SUM(TIMESTAMPDIFF(HOUR, timein, timeout)) AS totalhours
        SEC_TO_TIME(SUM(TIME_TO_SEC(timeout) - TIME_TO_SEC(timein))) AS totalhours
        FROM timesheet
        WHERE userid='$empid' 
        AND DATE_FORMAT(timein,'%Y-%m-%d') >= '$dfrom'
        AND DATE_FORMAT(timein,'%Y-%m-%d') <= '$dto'
        GROUP BY numweek")->result();
        return $query;
    }

    function displayLateUTAbs($empid, $date){
        $ob_data = array();
        $q_ob = $this->db->query("SELECT * FROM ob_request WHERE employeeid = '$empid' AND ob_type != 'ob' AND paid = 'NO' AND fromdate AND todate BETWEEN '$date' AND '$date' ");
        if($q_ob->num_rows() > 0){
            foreach($q_ob->result_array() as $value){
                $ob_data[$value['ob_type']] = $value['ob_type'];
            }
        }
        return $ob_data;
    }

    function holidayHalfdayComputation($login, $logout, $fromtime, $totime , $firstsched=""){
        /*if(!$firstsched){*/
            if(($this->exp_time($fromtime) <= $this->exp_time($logout) ) || ($this->exp_time($logout) <= $this->exp_time($totime)) ){
                if($logout) return $this->exp_time($fromtime) - $this->exp_time(date("H:i", strtotime($logout)));
                else return false;
            }
        /*}else{
            if(($this->exp_time($fromtime) <= $this->exp_time($login) ) || ($this->exp_time($login) <= $this->exp_time($totime)) ){
                if($login) return $this->exp_time(date("H:i", strtotime($login))) - $this->exp_time($totime);
                else return false;
            }
        }*/
    }

    function displayLogTimeCurrent($eid="",$date="",$tstart="",$tend="",$tbl="",$seq=1,$absent_start='',$earlyd=''){
        $haslog = true;
        $timein = $timeout = $otype = "";

        if($tbl == "NEW")   $tbl = "timesheet";
        else                $tbl = "timesheet_bak";
        $q_timesheet = $this->db->query("SELECT * FROM timesheet WHERE employeeid = '$eid' AND DATE(timein)='$date' ");
        if($q_timesheet && $q_timesheet->num_rows() == 0){
            $q_attempts = $this->db->query("SELECT stamp, DATE(datecreated) AS datecreated FROM TMS_INOUT.tblLoginAttempts WHERE DATE(datecreated) = '$date' AND user_id = '$eid' ");

            if($q_attempts && $q_attempts->num_rows() > 0){
                $timein  = $q_attempts->row($seq)->datecreated." ".$q_attempts->row($seq)->stamp;
            }else{
                if($timein=='0000-00-00 00:00:00') $timein = "";
                if($timeout=='0000-00-00 00:00:00') $timeout = "";
            }

            return array($timein,$timeout,$otype,$haslog);
        }

        $query = $this->db->query("
                SELECT timein,timeout,otype FROM $tbl 
                WHERE userid='$eid' 
                AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                AND ( TIME(timein)<='$tend' )
                AND ( TIME(timeout) > '$tstart' ) 
                AND timein != timeout
        
                ORDER BY timein ASC LIMIT 1");
        if($query && $query->num_rows() > 0){
            $seq = $seq - 1;
            $timein  = $query->row($seq)->timein;
            $timeout = $query->row($seq)->timeout;
            $otype   = $query->row($seq)->otype;
                                    

        }else{

            $wCAbsentEarlyD = '';
            if($absent_start) $wCAbsentEarlyD .= " AND ( TIME(timeout) > '$absent_start' OR DATE_FORMAT(timeout,'%H:%i:%s') = '00:00:00' )";
            if($earlyd)       $wCAbsentEarlyD .= " AND ( TIME(timein) < '$earlyd' OR DATE_FORMAT(timein,'%H:%i:%s') = '00:00:00' )";

            $query = $this->db->query("
                    SELECT timein,timeout,otype FROM $tbl 
                    WHERE userid='$eid' 
                    AND ( DATE(timein)='$date' OR DATE(timeout)='$date' ) 
                    AND ( TIME(timein)<='$tend' OR  DATE_FORMAT(timein,'%H:%i:%s') = '00:00:00' )
                    AND ( TIME(timeout) > '$tstart' OR DATE_FORMAT(timeout,'%H:%i:%s') = '00:00:00' ) 
                    AND timein != timeout
                    $wCAbsentEarlyD 
                    ORDER BY timein ASC LIMIT 1");
            

            if($query && $query->num_rows() > 0){
                $seq = $seq - 1;

                $timein  = $query->row($seq)->timein;
                $timeout = $query->row($seq)->timeout;
                $otype   = $query->row($seq)->otype;
                                        
            }else{

                $query = $this->db->query("SELECT logtime FROM timesheet_trail WHERE userid='$eid' AND (DATE(logtime)='$date' OR DATE(localtimein)='$date') AND log_type = 'IN' ORDER BY logtime DESC LIMIT $seq");
                if($query && $query->num_rows() > 0){
                    $seq = $seq - 1;
                    $timein  = ($query->row($seq)->logtime == "0000-00-00 00:00:00" ? $query->row($seq)->localtimein : $query->row($seq)->logtime);
                    $timeout = $otype = "";
                    // $return = array($timein,"","",$haslog);
                }else{
                    $haslog = false;
                    $checklog_q = $this->db->query("SELECT timeid FROM $tbl WHERE userid='$eid' AND DATE(timein)='$date'");
                    if($checklog_q && $checklog_q->num_rows() > 0) $haslog = true;

                    $timein = $timeout = "";
                    $otype = true;

                }
            }   

        }

        if($timein=='0000-00-00 00:00:00') $timein = "";
        if($timeout=='0000-00-00 00:00:00') $timeout = "";
        
        return array($timein,$timeout,$otype,$haslog);
    }

    public function payslipOvertimeDetailed($empid, $d_cutoffrom, $d_cutoffto){
        $this->load->model('utils');
        $this->load->model('payrollcomputation');
        $this->load->model('time');
        $this->load->model('income');
        $ot_amount = 0;
        $ot_data = array();

        $rate_per_hour = ($this->income->getEmployeeSalaryRate1($empid, "hourly"));
        $rate_per_minute = $rate_per_hour / 60;
        $rate_per_minute = number_format($rate_per_minute, 2, '.', '');
        $employeement_status = $this->extras->getEmploymentStatus($empid);
        $setup = $this->payrollcomputation->getOvertimeSetup($employeement_status);
        $q_att = $this->db->query("SELECT * FROM `attendance_confirmed_nt` WHERE employeeid = '$empid' AND payroll_cutoffstart = '$d_cutoffrom' AND payroll_cutoffend = '$d_cutoffto' ");
        if($q_att->num_rows() > 0){
            $att_id = $q_att->row()->id;
            $q_ot = $this->db->query("SELECT * FROM attendance_confirmed_nt_ot_hours where base_id = '$att_id' ");
            if($q_ot->num_rows() > 0){
                foreach($q_ot->result_array() as $row){
                    $sel_setup = ($row["holiday_type"]) ? 0 : 1;
                    $is_excess = ($row["is_excess"]) ? 1 : 0;
                    $ot_min = $this->time->hoursToMinutes($row["ot_hours"]);
                    $ot_hour = $ot_min / 60;
                    
                    $percent = 100;
                    if(isset($setup[$employeement_status][$row["ot_type"]][$row["holiday_type"]])) $percent = $setup[$employeement_status][$row["ot_type"]][$row["holiday_type"]][$is_excess];
                    $percent = $percent / 100;
                    
                    $hourly_rate = $rate_per_hour * $percent;
                    $ot_amount = $hourly_rate * $ot_hour;
                    // $ot_amount = floatval($ot_amount);
                    if(!isset($ot_data[$row["ot_type"]][$row["holiday_type"]])) $ot_data[$row["ot_type"]][$row["holiday_type"]]["ot_hours"] = $this->exp_time($row["ot_hours"]);
                    else $ot_data[$row["ot_type"]][$row["holiday_type"]]["ot_hours"] += $this->exp_time($row["ot_hours"]);

                    if(!isset($ot_data[$row["ot_type"]][$row["holiday_type"]])) $ot_data[$row["ot_type"]][$row["holiday_type"]]["ot_amount"] = $ot_amount;
                    else $ot_data[$row["ot_type"]][$row["holiday_type"]]["ot_amount"] += $ot_amount;

                }
            }
        }
        // Globals::pd($ot_data); die;
        return $ot_data;
    }

    public function displaySubstitute($employeeid, $date){
        $lec = $lab = $admin = $rle = 0;
        $q_sub = $this->db->query("SELECT * FROM substitute_request WHERE employeeid = '$employeeid' AND '$date' BETWEEN dfrom AND dto ");
        if($q_sub->num_rows() > 0){
            foreach($q_sub->result_array() as $row){
                if($row["type"] == "LEC") $lec += $this->time->hoursToMinutes($row["total"]);
                elseif($row["type"] == "LAB") $lab += $this->time->hoursToMinutes($row["total"]);
                elseif($row["type"] == "ADMIN") $admin += $this->time->hoursToMinutes($row["total"]);
                else $rle += $this->time->hoursToMinutes($row["total"]);
            }
        }
        
        return array($lec, $lab, $admin, $rle);
    }

    public function affectedBySuspension($hol_start="", $hol_end="", $sched_start="", $sched_end=""){
        $hol_start = strtotime($hol_start);
        $hol_end = strtotime($hol_end);
        $sched_start = strtotime($sched_start);
        $sched_end = strtotime($sched_end);
        if(($hol_start <= $sched_start && $hol_end >= $sched_start) || ($hol_start <= $sched_end && $hol_end >= $sched_end)) return true;
        else return false;
    }

    public function affectedBySuspensionBefore($hol_start="", $hol_end="", $sched_start="", $sched_end=""){
        $hol_start = strtotime($hol_start);
        $hol_end = strtotime($hol_end);
        $sched_start = strtotime($sched_start);
        $sched_end = strtotime($sched_end);
        if($hol_start <= $sched_start && $hol_end >= $sched_start) return true;
        else return false;
    }

    public function affectedBySuspensionAfter($hol_start="", $hol_end="", $sched_start="", $sched_end=""){
        $hol_start = strtotime($hol_start);
        $hol_end = strtotime($hol_end);
        $sched_start = strtotime($sched_start);
        $sched_end = strtotime($sched_end);
        if($sched_start < $hol_start && $hol_end > $sched_end) return true;
        else return false;
    }

    function displayPendingOBApp($eid="", $date="", $ob_type=""){
        $return = $obtypedesc = "";
        $query1 = $this->db->query("SELECT a.id,b.type,b.ob_type FROM ob_app_emplist a INNER JOIN ob_app b ON a.base_id = b.id WHERE '$date' BETWEEN b.datefrom AND b.dateto AND a.employeeid='$eid' AND a.status = 'PENDING' AND ob_type = '$ob_type'");
        if($query1->num_rows() > 0){  
            $obtype2 = $query1->row(0)->ob_type;

            if($obtype2=='late')        $obtypedesc = ' <br>PENDING EXCUSE SLIP (late) APPLICATION';
            elseif($obtype2=='undertime')  $obtypedesc = ' <br>PENDING EXCUSE SLIP (undertime) APPLICATION';
            elseif($obtype2=='absent')  $obtypedesc = ' <br>PENDING EXCUSE SLIP (absent) APPLICATION';

            $return.=($return?", ".$obtypedesc:$obtypedesc);
        }

        return $return;
    }

    function employeeScheduleDateActive($eid, $date, $starttime="", $endtime=""){
      $wc = "";
      if($starttime && $endtime) $wc = " AND starttime = '$starttime' AND endtime = '$endtime' ";
      $query = $this->db->query("SELECT dateactive FROM employee_schedule_history WHERE employeeid = '$eid' $wc AND idx  = DATE_FORMAT('$date','%w') AND DATE(dateactive) <= DATE(DATE_SUB('$date',INTERVAL 1 DAY)) ORDER BY dateactive DESC,starttime DESC LIMIT 1;");
      if($query->num_rows() > 0) return $query->row()->dateactive;
      else return false;
    }


    function isWfhOB($eid, $date){
        return $this->db->query("SELECT * FROM ob_request WHERE '$date' BETWEEN fromdate AND todate AND employeeid='$eid' AND (ob_type = 'ob' OR ob_type = '') AND obtypes = '2'");
    }

    function hasWFHTimeRecord($id, $date){
        return $this->db->query("SELECT * FROM ob_timerecord WHERE base_id = '$id' AND t_date = '$date' AND status = 'APPROVED'");
    }

    function getPerdeptLate($id){
        $tot_late = 0;
        $q_workhours = $this->db->query("SELECT * FROM workhours_perdept WHERE base_id = '$id'");
        if($q_workhours->num_rows() > 0){
            foreach($q_workhours->result() as $row){
                $late_hours = $this->exp_time($row->late_hours);
                $tot_late += $late_hours;
            }
        }

        return $tot_late;
    }

    function getPerdeptAbsent($id){
        $tot_deduc = 0;
        $q_workhours = $this->db->query("SELECT * FROM workhours_perdept WHERE base_id = '$id'");
        if($q_workhours->num_rows() > 0){
            foreach($q_workhours->result() as $row){
                $deduc_hours = $this->exp_time($row->deduc_hours);
                $tot_deduc += $deduc_hours;
            }
        }

        return $tot_deduc;
    }

    public function getTotalHoursSched($datefrom, $dateto, $employeeid){
        $t_hours = 0;
        $qdate = $this->attcompute->displayDateRange($datefrom, $dateto);
        foreach($qdate as $rdate){
            $sched = $this->displaySched($employeeid, $rdate->dte);
            if($sched->num_rows() > 0){
                foreach($sched->result() as $sched_list){
                    $time1 = strtotime($sched_list->starttime);
                    $time2 = strtotime($sched_list->endtime);
                    $difference = round(abs($time2 - $time1) ,2);
                    $t_hours += $difference;
                }
            }
        }

        return $this->sec_to_hm($t_hours);
    }

    public function getTotalHoursOBTimeRecord($leave_id, $obtypes, $datefrom, $dateto, $tfrom, $tto, $employeeid, $dis_list=false, $ob_type="", $iscorrection=false){
        $wc = "";
        if($dis_list) $wc = " AND status = 'DISAPPROVED'";
        else $wc = " AND status = 'APPROVED'";

        $qdate = $this->attcompute->displayDateRange($datefrom, $dateto);
        $sched_min = 0;
        $t_vacant = 0;
        $from_time = "";
        $t_min = $sched_min = 0;
        $timein = $timeout = "";
        foreach($qdate as $rdate){
            /*get breaktime hours*/
            list($breakfrom, $breakto) = $this->get_emp_breaktime($employeeid, $rdate->dte);

            $tap_count = 0;
            if($iscorrection === false){
                $q_timesheet = $this->db->query("SELECT * FROM ob_timerecord WHERE base_id = '$leave_id' AND t_date = '$rdate->dte' $wc ");
                if($q_timesheet->num_rows() > 0){
                    foreach($q_timesheet->result() as $row){
                        $timein = $row->timein;
                        $timeout = $row->timeout;
                        $t_min += round(abs(strtotime($timeout) - strtotime($timein)) / 60,2);

                        $tap_count++;
                    }
                }else{
                    if($ob_type != "WFH"){
                        $timein = $tfrom;
                        $timeout = $tto;
                        $t_min += round(abs(strtotime($timeout) - strtotime($timein)) / 60,2);
                        $tap_count++;
                    }
                }
            }else{
                $q_correction = $this->db->query("SELECT * FROM leave_app_ti_to WHERE aid = '$leave_id' AND cdate = '$rdate->dte' ");

                if($q_correction->num_rows() > 0){
                    foreach($q_correction->result() as $keys => $row){
                        // LOLA LATEST UPDATE RETURN TITO
                        // if($keys == 0){
                        //     $timein = $timeout = "";
                        //     if($row->request_time){
                        //         list($timein, $timeout) = explode(" - ", $row->request_time);
                        //         $timein = date("H:i:s", strtotime($timein));
                        //         $timeout = date("H:i:s", strtotime($timeout));
                        //         $tap_count++;
                        //     }
                        //     $t_min += round(abs(strtotime($timeout) - strtotime($timein)) / 60,2);
                        // }
                            $timein = $timeout = "";
                            if($row->request_time){
                                list($timein, $timeout) = explode(" - ", $row->request_time);
                                $timein = date("H:i:s", strtotime($timein));
                                $timeout = date("H:i:s", strtotime($timeout));
                                $tap_count++;
                            }
                            $t_min += round(abs(strtotime($timeout) - strtotime($timein)) / 60,2);
                    }
                }
            }
            
            $from_time = $t_vacant = $seq = 0;
            $sched = $this->attcompute->displaySched($employeeid,$rdate->dte);
            $used_time = array();
            $sched_count = $sched->num_rows();
            if($sched->num_rows() > 0){
                foreach($sched->result() as $sched_row){
                    if($from_time){

                        $seq += 1;
                        $starttime = $sched_row->starttime;
                        $endtime = $sched_row->endtime; 
                        $type  = $sched_row->leclab;
                        $tardy_start = $sched_row->tardy_start;
                        $absent_start = $sched_row->absent_start;
                        $earlydismissal = $sched_row->early_dismissal;
                        $aimsdept = $sched_row->aimsdept;

                        // logtime
                        list($login,$logout,$q,$haslog_forremarks,$used_time) = $this->attcompute->displayLogTimeOB($employeeid,$rdate->dte,$starttime,$endtime,"NEW",$seq,$absent_start,$earlydismissal,$used_time);

                        $stime = strtotime($from_time);
                        $etime = strtotime($sched_row->starttime);
                        if($haslog_forremarks) $t_vacant += round(abs($etime - $stime) / 60,2);
                    }

                    $from_time = $sched_row->endtime;

                    $sched_min += round(abs(strtotime($sched_row->endtime) - strtotime($sched_row->starttime)) / 60,2);
                }
            }

            /*if($tap_count>=1) $t_vacant = abs($t_min - $sched_min);
            else $t_vacant = 0;*/

            $teachingtype = $this->extensions->getEmployeeTeachingType($employeeid);
            if($teachingtype == "nonteaching"){
                if($tap_count > 1) $t_vacant = 0;
                if($tap_count == 1 && $this->is_between_time($breakfrom, $timein, $timeout) ){
                    $t_min -= $t_vacant;
                }
            }
        }

        return $this->time->minutesToHours($t_min);

    }

    public function is_between_time($current_time, $from_time, $to_time){
        $date1 = DateTime::createFromFormat('H:i:s', $current_time);
        $date2 = DateTime::createFromFormat('H:i:s', $from_time);
        $date3 = DateTime::createFromFormat('H:i:s', $to_time);
        if ($date1 > $date2 && $date1 < $date3) return true;
        else return false;
    }

    public function get_emp_breaktime($employeeid, $date){
        $from_time = $to_time = "";
        $seq = 0;
        $sched = $this->attcompute->displaySched($employeeid,$date);
        $used_time = array();
        $sched_count = $sched->num_rows();
        if($sched->num_rows() > 0){
            foreach($sched->result() as $sched_row){
                if($from_time){

                    $seq += 1;
                    $to_time = $sched_row->starttime; 
                   
                }

                $from_time = $sched_row->starttime;

            }
        }

        if($seq == 1){
            $from_time = "12:00:00";
            $to_time = "13:00:00";
        }

        return array($from_time, $to_time);

    }

    public function hasLeave($date, $employeeid, $base_id=""){
        $wc = "";
        if($base_id) $wc = " AND base_id != '$base_id'";
        $hasleave = $this->db->query("SELECT * FROM leave_app_base a INNER JOIN leave_app_emplist b ON a.id = b.base_id WHERE employeeid = '$employeeid' AND isHalfDay = '0' AND '$date' BETWEEN datefrom AND dateto AND (a.status != 'DISAPPROVED' AND a.status != 'CANCELLED') ")->num_rows();
        $hasob = $this->db->query("SELECT * FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id WHERE employeeid = '$employeeid' AND '$date' BETWEEN datefrom AND dateto AND a.status != 'DISAPPROVED' AND obtypes != '2' $wc ")->num_rows();

        return $hasob + $hasleave;
    }

    # CHECK LOGS IN AND OUT BASE SCHEDULE return bool
    public function CheckLogsBaseSchedule($employeeid="",$current_date=""){

        # SET RETURN BOOL
        $checkLogsFlags = true;
        # GET CURRENT CUTOFF
        list($cutoffstart,$cutoffend) = $this->extensions->getCutoff($current_date);
        # GET ALL DAYS FROM FIRST DAY CUTOFF TO CURRENT DATE
        $getDays = $this->displayDateRange($cutoffstart, $current_date);
        // var_dump($getDays);die;
        # LOOP DAYS AND CHECK EACH IN AND OUT OF THE EMPLOYEE'S SCHEDULE
        foreach ($getDays as $keyday => $days){
            $sched = $this->attcompute->displaySched($employeeid,$days->dte);
            foreach($sched->result() as $rsched){
                $stime = $rsched->starttime;
				$etime = $rsched->endtime;
				$tardy_start = $rsched->tardy_start;
				$absent_start = $rsched->absent_start;
				$earlydismissal = $rsched->early_dismissal;
                list($login,$logout) = $this->attcompute->displayLogTime($employeeid,$days->dte,$stime,$etime,"NEW",1,$absent_start,$earlydismissal);
                if($login=='0000-00-00 00:00:00' || $login=='') $checkLogsFlags = false;
                if($logout=='0000-00-00 00:00:00' || $logout=='') $checkLogsFlags = false;
            }
        }
        
        return $checkLogsFlags;
    }

    # CHECK LOGS IN AND OUT BASE EMPLOYEE ATTENDANCE DETAILED ABSENTS return bool version 2.0
    public function CheckLogsBaseSchedulev2($employeeid="",$current_date=""){

        $start_date = date("Y-m-d",strtotime($current_date));
        $end_date = '';
        $lacking_in_out = $days_half = $days_absent = 0;
        # SET RETURN BOOL
        $checkLogsFlags = true;
        // $test = array();
        # CHECK ABSENTS
        $query = $this->db->query("SELECT CutoffFrom, CutoffTo FROM cutoff a INNER JOIN payroll_cutoff_config b ON b.baseid = a.ID WHERE '$start_date'  BETWEEN b.`startdate` AND b.`enddate`")->result_array();
        if($query){
            foreach ($query as $key => $value) {
                if($start_date > $value['CutoffTo']){
                    $end_date = $value['CutoffFrom'];
                    $start_date = $value['CutoffTo'];
                }else{
                    $end_date = $value['CutoffFrom'];
                }
            }        
            $query = $this->db->query("SELECT * FROM `employee_attendance_detailed` WHERE employeeid  = '$employeeid' AND sched_date BETWEEN '$end_date' AND '$start_date'")->result_array();
            foreach($query as $key => $row){
                if($row['absents'] == "4:00") $days_half++;
                if($row['absents'] == "8:00") $days_absent++;
                // $test[] = ($row['absents'] == "4.00");
            }
        }
        $data['absent'] = $days_absent;
        $data['half_day'] = $days_half;
        
        if($data['half_day'] > 0){ $checkLogsFlags = false;} 
        if($data['absent'] > 0){ $checkLogsFlags = false;} 

        return $checkLogsFlags;
        // return array('half' => $test,'absent' =>$days_absent,'status' => $checkLogsFlags);
    }

    /**
     * Retrieves and calculates the total rendered hours for a specific employee on a given date.
     *
     * @param int $empID The ID of the employee.
     * @param string $date The date to filter (format: YYYY-MM-DD).
     * @param string $table The table name to query.
     * @return string The total rendered hours in "HH:MM" format.
     */
    public function getAllRenderedHours($empID,$date,$table)
    {
        $query = "SELECT twr FROM $table WHERE employeeid = '{$empID}' AND date = '{$date}'";
        $result = $this->db->query($query);

        $totalMinutes = 0;
        if ($result && $result->num_rows() > 0) {

            foreach($result->result() as $data){
                $minute = $this->exp_time($data->twr);
                $totalMinutes += $minute;
            }
        }
        return $this->sec_to_hm($totalMinutes);

    }


    function loadConfOvertime($employeeid = '', $date = "", $base_id="")
    {

        $result = [];
        $ot_type = '';

        $excess_limit = 8*60*60;
        $dayofweek = date('N',strtotime($date));
        $isWeekend = in_array($dayofweek, array('6','7')) ? true : false;

        $getSched = $this->displaySched($employeeid,$date)->result();
        $hasSched = count($getSched) > 0 ? true : false;

        if($hasSched) $ot_type = 'WITH_SCHED';
        if($hasSched && $isWeekend) $ot_type = 'WITH_SCHED_WEEKEND';
        if(!$hasSched) $ot_type = 'NO_SCHED';

        $query = $this->db->query("SELECT approved_total
                    FROM
                    group_overtime AS go
                    LEFT JOIN overtime_request AS oreq
                        ON oreq.`aid` = go.`base_id`
                    WHERE oreq.employeeid = '$employeeid'
                    AND (
                            go.`date` = '$date'
                    )
                    AND oreq.`status` = 'APPROVED'");

        $query_result =  $query->result();


        if(count($query_result) > 0)
        {
            // $this->load->model('attendance');

            $otTotalWith25 = $this->attendance->loadTotalOT($employeeid,$date,$date,"total_ot_with_25");
            $otTotalWithout25 = $this->attendance->loadTotalOT($employeeid,$date,$date,"total_ot_without_25");

            $holidayType = $this->loadHolidayType($employeeid,$date);
            $excessTime = $this->excessLimit($query_result[0]->approved_total?$query_result[0]->approved_total:"00:00");

            $result['base_id'] = $base_id;
            $result['ot_hours'] = $query_result[0]->approved_total?$query_result[0]->approved_total:"";
            $result['ot_type'] = $ot_type;
            $result['holiday_type'] =  $holidayType ;
            $result['is_excess'] = $excessTime;
            $result['total_ot_with_25']=$otTotalWith25;
            $result['total_ot_without_25']=$otTotalWithout25;
        }
        return $result;

    }

    function loadHolidayType($employeeid="",$date="")
    {
        $result = "NONE";
        $query = $this->db->query("SELECT cht.`description`
                                    FROM code_holiday_calendar AS chc
                                    LEFT JOIN code_holidays AS ch ON chc.holiday_id = ch.holiday_id
                                    LEFT JOIN code_holiday_type AS cht ON cht.holiday_type = ch.holiday_type
                                    WHERE 
                                        !FIND_IN_SET('$employeeid', ch.prohibited) 
                                        AND '$date' BETWEEN chc.date_from AND chc.date_to;");

        $query_result = $query->result();

        if($query_result)
        {
            if(strpos($query_result[0]->description, 'SPECIAL')!== false) $result = 'SPECIAL';
            else if(strpos($query_result[0]->description, 'REGULAR')!== false) $result = 'REGULAR';
        }

        return $result;
    }

    function excessLimit($ot)
    {
        $excess_limit = 8*60*60;
        $excess = 0;
        $ottime = $this->exp_time($ot?$ot:"00:00");

        if($ottime > $excess_limit){
            $excess = $ottime - $excess_limit;
            $ottime = $excess_limit;
        }

        return $excess;
    }



}