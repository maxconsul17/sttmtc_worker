<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Time extends CI_Model {
   
    /**
    * @author Robert Ram Bolista
    * @copyright ram_bolista@yahoo.com
    * @date 6-26-2014
    * @time 16:56
    */
   
   // Transform hours like "1:45" into the total number of minutes, "105". 
   function hoursToMinutes($hours) 
   { 
       $minutes = 0; 
       if (strpos($hours, ':') !== false) 
       { 
           // Split hours and minutes. 
           list($hours, $minutes) = explode(':', $hours); 
            return $hours * 60 + $minutes; 
       } 
   }
   
   // Transform minutes like "105" into hours like "1:45". 
   function minutesToHours($minutes) 
   { 
       $hours = (int)($minutes / 60); 
       $minutes -= $hours * 60; 
       return sprintf("%d:%02.0f", $hours, $minutes); 
   }   

  /**
   * Para makuha yung difference between 2 time kahit may conflict sa format ng time
   * lndrsnts
   */
   function calculateTimeDifferenceInHoursMinutes($startTimestamp, $endTimestamp)
   {
       // Format the timestamps to H:i format
       $startFormattedTime = date("H:i", $startTimestamp);
       $endFormattedTime = date("H:i", $endTimestamp);
   
       // Convert formatted time into Unix timestamps for accurate calculation
       $startTime = strtotime($startFormattedTime);
       $endTime = strtotime($endFormattedTime);
   
       // Calculate the difference in seconds
       $timeDifferenceInSeconds = $endTime - $startTime;
   
       // Calculate hours and minutes
       $hours = floor($timeDifferenceInSeconds / 3600); // Convert seconds to hours
       $minutes = floor(($timeDifferenceInSeconds % 3600) / 60); // Convert remaining seconds to minutes
   
       // Return the difference in the format "H:MM"
       return sprintf("%d:%02d", $hours, $minutes);
   }   
   
   /**
   * getTardy()
   *
   * return total number of minutes late
   *
   * @param (time) ($timein) employee login
   * @param (time) ($schedulein) employee in-schedule
   * @param (time) ($tardystart) employee starting time of late 
   * @return (time-minute) ($return)
   */
   function getTardy($timein='',$schedulein='',$tardystart=''){
      $return=0;
      $timein = $this->time->hoursToMinutes(date("H:i",strtotime($timein)));
      $schedulein = $this->time->hoursToMinutes(date("H:i",strtotime($schedulein)));
      $tardystart = $this->time->hoursToMinutes(date("H:i",strtotime($tardystart)));
      
      $totalminute_tardy = $schedulein - $tardystart;
      $late = $timein - $schedulein;
      if($late>=$totalminute_tardy) $return = $late;
      if($return<0)$return=0;
      return $return;
   }
   
   // get HalfdayAbsent : Added By Justin : 03/30/2015
   function getHalfAbsent($timein='',$schedulehalfabsent='',$scheduleabsentwhole=''){
    $return = 0;
    // Convert Hour to minutes
    $timein = $this->time->hoursToMinutes(date("H:i",strtotime($timein)));
    $schedulehalfabsent = $this->time->hoursToMinutes(date("H:i",strtotime($schedulehalfabsent)));
    $scheduleabsentwhole = $this->time->hoursToMinutes(date("H:i",strtotime($scheduleabsentwhole)));
    if($timein >= $schedulehalfabsent && $timein < $scheduleabsentwhole){
        $return = 1;
    }
    return $return; 
   }
   
   /**
   * getUnderTime()
   *
   * return total number of minutes undertimed
   *
   * @param (time) ($timeout) employee logout
   * @param (time) ($scheduleout) employee out-schedule
   * @return (time-minute) ($return)
   */
   function getUnderTime($timeout='',$scheduleout=''){
      $return=0;
      if($this->time->hoursToMinutes(date("H:i",strtotime($timeout)))>0){
      $timeout = $this->time->hoursToMinutes(date("H:i",strtotime($timeout)));
      $scheduleout = $this->time->hoursToMinutes(date("H:i",strtotime($scheduleout)));
      $return = $scheduleout - $timeout;   
      }
      
      if($return<0)$return=0;
      return $return;
   }
   
   /**
   * getOverTime()
   *
   * return total number of minutes undertimed
   *
   * @param (time) ($timeout) employee logout
   * @param (time) ($scheduleout) employee out-schedule
   * @param (time) ($timein) employee login
   * @param (time) ($schedulein) employee in-schedule
   * 
   * @return (time-minute) ($return)
   */
   function getOverTime($timeout='',$scheduleout='',$timein='',$schedulein='',$holiday=FALSE){
      $return=0;
      
      if($scheduleout=="" || $holiday){
         $timein = $this->time->hoursToMinutes(date("H:i",strtotime($timein)));
         $timeout = $this->time->hoursToMinutes(date("H:i",strtotime($timeout)));
         $return = $timeout - $timein;
         
      }else{
         if($this->time->hoursToMinutes(date("H:i",strtotime($timeout)))>0){
         $timeout = $this->time->hoursToMinutes(date("H:i",strtotime($timeout)));
         $scheduleout = $this->time->hoursToMinutes(date("H:i",strtotime($scheduleout)));
         $return = $timeout - $scheduleout;   
         }   
      }
      
      
      if($return<0)$return=0;
      return $return;
   }
   
   /**
   * getLeave()
   *
   * return total number of minutes undertimed
   *
   * @param (date) ($date) date of leave
   * @param (varchar) ($employeeid) employee unique identity
   * 
   * @return (boolean) ($return)
   */
   function getLeave($date='',$employeeid='',&$arraytime){
      $return=0;
      $timein="";
      $timeout="";
      $isleave=false;
      $sql = $this->db->query("SELECT * FROM employee_schedule_adjustment WHERE employeeid='$employeeid' AND cdate='$date'")->result();
      $return = count($sql);
      foreach($sql as $row){
          $timein=$row->starttime;
          $timeout=$row->endtime;   
          $isleave=$row->remarks > 0 ? true : false;
      }
      $arraytime = array($timein,$timeout,$isleave);
      return $return;
   }
   
   /**
   * getHoliday()
   *
   * return total number of minutes undertimed
   *
   * @param (date) ($date) date of leave
   * 
   * @return (boolean) ($return)
   */
   function getHoliday($date=''){
      $return=0;
      $timein="";
      $timeout="";
      $sql = $this->db->query("SELECT * FROM code_holiday_calendar WHERE '$date' BETWEEN date_from AND date_to")->result();
      $return = count($sql);
      #$arraytime = array($timein,$timeout);
      return $return;
   }


   // gets the number of minutes tardy for the second half
   function getSecondHalfTardy($timein='', $tardystart = ''){
      $timein = $this->time->hoursToMinutes(date("H:i",strtotime($timein)));
      $tardystart = $this->time->hoursToMinutes(date("H:i",strtotime($tardystart)));
      return ($timein - $tardystart);
   }

   // determines if the actual log is after the time to be late/absent
  function loggedLate($log = '', $lateRef=''){
    $actuallog = strtotime($log);
    $reference = strtotime($lateRef);
    return ($actuallog >= $reference);
   }

   // determines if sched is half day only
   function isSchedHalfDayOnly($schedin='', $schedout){
    $intvalschedin = strtotime($schedin);
    $intvalschedout = strtotime($schedout);
    $diff = $intvalschedout - $intvalschedin;
    return ($diff == 14400);
   }

   // create a display for the range of date covered of the process
   function createRangeToDisplay($from_date = '', $to_date = ''){
    $daterange = "";
    if ( ($from_date != "") && ($to_date != ""))  {
      if((date("Y-m", strtotime($from_date))) == (date("Y-m", strtotime($to_date)))){
        $daterange = date("F d-", strtotime($from_date)) . date("d Y", strtotime($to_date));

      }else if ( (date("Y-m-d", strtotime($from_date))) != (date("Y-m-d", strtotime($to_date))) ) {
        $daterange = date("F d, Y - ", strtotime($from_date)) . date("F d, Y", strtotime($to_date));
      }else{
        $daterange = date("F d, Y", strtotime($from_date));
      }
    }else if ((!$from_date) && ($to_date != "")) {
      $daterange = date("F d, Y", strtotime($to_date));
    }else if (($from_date != "") && (!$to_date)) {
      $daterange = date("F d, Y", strtotime($from_date));
    }
    return $daterange;
   }// end createRangeToDisplay function


   function giveNumDaysCovered($from_date = '', $to_date = ''){
    $diff = date_diff(date_create($from_date),date_create($to_date));
    return intval($diff->format("%a") + 1);
   }

   function DayFormatted($date)
   {
       $day = date("d",strtotime($date));
       $week = substr(date("l", strtotime($date)), 0, 1);
       $date = $day.'-'.$week;

       return $date;
   }

   function formatTimeOutput($dateData, $is_time=false) {
      if ($dateData === '--') {
          return ''; 
      }
      
      if($is_time === true) $dateData = date("H:i", strtotime($dateData));

      return $dateData ?? ''; 
  }

   function toHoursAndMinutes($arr = ''){
      $timedisp = explode(":", $this->time->minutesToHours(array_sum($arr)));

      $hr = (intval($timedisp[0]) > 0) ? intval($timedisp[0]) . ((intval($timedisp[0]) > 1) ? "hrs ":"hr "):"";

      $min = (intval($timedisp[1]) > 0) ? intval($timedisp[1]) . ((intval($timedisp[1]) > 1) ? "mins":"min"):"";

      return ($hr . $min);
   }
  
  function EditedOT($date=""){
    $ot = "";            
    $query = $this->db->query("SELECT * FROM payroll_emp_otaccepted WHERE otdate='$date' ORDER BY id DESC");
    if($query->num_rows() > 0){
        $ot =  $query->row(0)->overtime > 0 ? $query->row(0)->overtime : "";
    }
    return $ot;
  }
  
  function date_range($first, $last, $step = '+1 day', $output_format = 'Y-m-d' ) {

    $dates = array();
    $current = strtotime($first);
    $last = strtotime($last);

    while( $current <= $last ) {

      $dates[] = date($output_format, $current);
      $current = strtotime($step, $current);
    }

    return $dates;
  }

  function roundOffTime($time){
    $new_time = 0;
    $time= explode(":", $time);
    $hours = $time[0];
    $minutes = $time[1];
    
    if($minutes >= 50){
      $new_time = $hours + 1;
      return $new_time .= ":00";
    }else{
      $new_time = $hours;
      return $new_time .= ":00";
    }

  }

    public function getPresentEmployee($datenow){
        $q_present = $this->db->query("SELECT * FROM (SELECT userid, timein AS localtimein FROM timesheet WHERE DATE(timein) = '$datenow' AND otype != 'ob'
        UNION
        SELECT userid, localtimein FROM timesheet_trail WHERE DATE(localtimein) = '$datenow' AND log_type = 'IN'
        UNION
        SELECT userid, localtimein FROM webcheckin_history WHERE DATE(localtimein) = '$datenow' AND log_type = 'IN') a
        INNER JOIN employee b ON a.userid = b.employeeid");
        if ($q_present->num_rows() == 0) {
           $q_present = $this->db->query("SELECT *, a.userid as user_id FROM webcheckin_history a INNER JOIN employee b ON a.userid = b.employeeid WHERE DATE(localtimein)='$datenow' AND log_type = 'IN' GROUP BY userid ORDER BY a.`localtimein` DESC");
        }
        if($q_present->num_rows() > 0) return $q_present->result_array();
        else return false;

    }

    public function getTotalActiveEmployeeCount(){
        $datenow = date('Y-m-d', strtotime($this->extensions->getServerTime()));
        $q_present = $this->db->query("SELECT count(employeeid) as total FROM employee where '$datenow' < dateresigned2 OR dateresigned2 = '0000-00-00' OR dateresigned2 = '1970-01-01' OR dateresigned2 IS NULL AND isactive = '1'");
        return $q_present->row()->total;
    }
     
    public function getPresentEmployeeList(){
      $datenow = date("Y-m-d", strtotime($this->db->query("SELECT CURRENT_TIMESTAMP ")->row()->CURRENT_TIMESTAMP));
      $q_attendance = $this->db->query("SELECT * FROM TMS_INOUT.tblLoginAttempts a INNER JOIN employee b ON a.`user_id` = b.`employeeid` WHERE DATE(datecreated) = '$datenow' AND status = 'success' GROUP BY user_id LIMIT 9 ");
      if($q_attendance->num_rows() > 0) return $q_attendance->result_array();
      else return false;
    }

    

    public function getTimeoutEmployeeList($user_id){
      $datenow = date("Y-m-d", strtotime($this->db->query("SELECT CURRENT_TIMESTAMP ")->row()->CURRENT_TIMESTAMP));
      $q_attendance = $this->db->query("SELECT * FROM login_attempts WHERE DATE(datecreated) = '$datenow' AND status = 'success' AND action = 'OUT' AND user_id = '$user_id' ");
      if($q_attendance->num_rows() > 0) return $q_attendance->row()->datecreated;
      else return "";
    }

    public function getTimeout($user_id,$datenow,$type){
      $q_present = $this->db->query("SELECT localtimeout FROM (SELECT userid, timeout AS localtimeout FROM timesheet WHERE DATE(timeout) = '$datenow' AND otype != 'ob'
      UNION
      SELECT userid, localtimein AS localtimeout FROM timesheet_trail WHERE DATE(localtimein) = '$datenow' AND log_type = '$type'
      UNION
      SELECT userid, localtimein FROM webcheckin_history WHERE DATE(localtimein) = '$datenow' AND log_type = '$type') a
      INNER JOIN employee b ON a.userid = b.employeeid WHERE userid = '".$user_id."'");
      if($q_present->num_rows() > 0) return $q_present->row()->localtimeout;
      else return "";
    }

    public function getAbsentEmployeeList($datenow){
      $q_attendance = $this->db->query("SELECT * FROM employee WHERE employeeid NOT IN (SELECT userid FROM timesheet_trail WHERE log_type = 'IN' AND DATE(localtimein) = '$datenow')  AND employeeid NOT IN (SELECT userid FROM webcheckin_history WHERE log_type = 'IN' AND DATE(localtimein) = '$datenow')");
      if ($q_attendance->num_rows() == 0) {
         $q_attendance = $this->db->query("SELECT * FROM webcheckin_history a INNER JOIN employee b ON a.userid = b.employeeid WHERE DATE(localtimein)='2020-08-05' AND log_type = 'IN' GROUP BY userid ORDER BY a.`localtimein` DESC LIMIT 9");
      }
      if($q_attendance->num_rows() > 0) return $q_attendance->result_array();
      else return false;
    }

    public function dateDiff($date){
        $date_today = date('Y-m-d', strtotime($this->extensions->getServerTime()));
        $now = date("Y-m-d",strtotime($date_today));
        $diff = strtotime($date) - strtotime($now);
        return abs(round($diff / 86400)); 
    }

    public function getLeaveTodayEmployees($datenow){
      $q_leave = $this->db->query("SELECT DISTINCT * FROM leave_request a INNER JOIN employee b ON a.`employeeid` = b.`employeeid` WHERE '$datenow' BETWEEN fromdate AND todate ");
      if($q_leave->num_rows() > 0) return $q_leave->result_array();
      else return false;
    }

    public function isTimeFromOBWfh($eid, $date){
      return $this->db->query("SELECT * FROM timesheet WHERE userid = '$eid' AND DATE(timein) = '$date' AND otype = 'wfh'");
    }

    public function obWfhDetails($eid, $date){
      $q_d = $this->db->query("SELECT c.* FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id INNER JOIN ob_timerecord c ON a.id = c.base_id WHERE employeeid = '$eid' AND t_date = '$date'");
      if($q_d->num_rows() > 0) return $q_d->row()->activity;
      else return false;
    }

    public function hasTimeIn($user, $dfrom, $dto){
      return $this->db->query("SELECT * FROM timesheet WHERE userid = '$user' AND DATE(timein) = '$dfrom' AND DATE(timeout) = '$dto' ")->num_rows();
    }

    /**
     * getMinuteDiff()
     *
     * return total number of minutes difference
     *
     * @param (time) ($time_1) from time
     * @param (time) ($time_2)to time
     * @return integer - total minute diff
     */
    function getMinuteDiff($time_1='',$time_2=''){
      $to_time = strtotime($time_1);
      $from_time = strtotime($time_2);
      return round(abs($to_time - $from_time) / 60,2);
    }

    function getMinutesBetween($startTime, $endTime) {
      $start = new DateTime($startTime);
      $end = new DateTime($endTime);
      
      $interval = $start->diff($end);
      $minutes = $interval->h * 60 + $interval->i;
  
      return $minutes;
    }

    /**
     * Check if both timeIn and timeOut are not equal to "00:00:00".
     *
     * This function takes two time values in the format "H:i:s" and verifies if neither of them is "00:00:00".
     *
     * @param string $timeIn  The time-in value in the format "H:i:s".
     * @param string $timeOut The time-out value in the format "H:i:s".
     *
     * @return bool Returns true i f neither timeIn nor timeOut is "00:00:00", false otherwise.
     */
    function areTimesValid($timeIn, $timeOut) {
      // Create a DateTime object
      $timeIn = new DateTime($timeIn);
      $timeOut = new DateTime($timeOut);

      // Format to extract the time
      $timeIn = $timeIn->format('H:i:s');
      $timeOut = $timeOut->format('H:i:s');

      // Check if either time is "00:00:00"
      if ($timeIn === "00:00:00" || $timeOut === "00:00:00") {
          return false; 
      }

      // If neither time is "00:00:00", return true
      return true;
    }

    function getSecondsBetween($startTime, $endTime) {
      $start = new DateTime($startTime);
      $end = new DateTime($endTime);
      
      $interval = $start->diff($end);
      
      return $interval->days * 86400 +  // Convert days to seconds
             $interval->h * 3600 +      // Convert hours to seconds
             $interval->i * 60 +        // Convert minutes to seconds
             $interval->s;              // Add remaining seconds
     }

     function sumTimes($times) {
        $totalMinutes = 0;
    
        foreach ($times as $time) {
            list($hours, $minutes) = explode(':', $time);
            $totalMinutes += ($hours * 60) + $minutes;
        }
    
        // Convert back to HH:MM format
        $totalHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;
        
        return sprintf("%02d:%02d", $totalHours, $remainingMinutes);
    }
     function getSecondsBetweenTimes($startTime, $endTime){
      // Parse times with 12-hour format (AM/PM)
      $start = DateTime::createFromFormat('g:i A', $startTime);
      $end = DateTime::createFromFormat('g:i A', $endTime);
  
      // If end time is earlier than start, assume it's on the next day
      if ($end < $start) {
          $end->modify('+1 day');
      }
  
      // Return the exact difference in seconds
      return $end->getTimestamp() - $start->getTimestamp();
  }

  function validateDateBetween($dateArray, $selectedDate){

    // Convert the date strings to DateTime objects
    $startDate = new DateTime($dateArray[0]);
    $endDate = new DateTime($dateArray[1]);
    $checkDate = new DateTime($selectedDate);

    // Check if the selected date is between the start and end dates
    if ($checkDate >= $startDate && $checkDate <= $endDate) {
        return true;
    } else {
      return false;
    }
  }

  function totalLateUndertimeDuration($datas)
    {
        $totalHour = 0;
        $totalMinute = 0;
        foreach($datas as $data)
        {
            if($data->lateut_lec && $data->lateut_lec != '--')
            {
                $totalHour += (int) $this->time->extractTimeComponent($data->lateut_lec,'hour');
                $totalMinute += (int) $this->time->extractTimeComponent($data->lateut_lec,'minute');
            }
            if($data->lateut_lab && $data->lateut_lab != '--')
            {
                $totalHour += (int) $this->time->extractTimeComponent($data->lateut_lab,'hour');
                $totalMinute += (int) $this->time->extractTimeComponent($data->lateut_lab,'minute');
            }
            if($data->lateut_admin && $data->lateut_admin != '--')
            {
                $totalHour += (int) $this->time->extractTimeComponent($data->lateut_admin,'hour');
                $totalMinute += (int) $this->time->extractTimeComponent($data->lateut_admin,'minute');
            }
            if($data->lateut_overload && $data->lateut_overload != '--')
            {
                $totalHour += (int) $this->time->extractTimeComponent($data->lateut_overload,'hour');
                $totalMinute += (int) $this->time->extractTimeComponent($data->lateut_overload,'minute');
            }
        }

        return [$totalHour,$totalMinute];
        
    }

    function extractTimeComponent($time, $component)
    {
        list($hour, $minute) = explode(':', $time);

        switch ($component) {
            case 'hour':
                return $hour;
            case 'minute':
                return $minute;
            default:
                throw new InvalidArgumentException("Invalid time component specified. Use 'hour' or 'minute'.");
        }
    }

}
/* End of file time.php */
/* Location: ./application/models/time.php */