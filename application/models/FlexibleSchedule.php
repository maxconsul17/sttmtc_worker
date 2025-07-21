<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class FlexibleSchedule extends CI_Model
{

    public function __construct()
    {
        $this->load->model("attcompute");
        $this->load->model("timesheet");
        $this->load->model("employeemod");
        $this->load->model("attendance");
    }

    /**
     * Processes the attendance for a given employee on a specific date based on the provided schedule.
     *
     * This method loops through each schedule row (if multiple schedules exist) and calculates various attendance-related
     * values such as actual log times, total work hours, undertime, official time, and remarks. It then inserts the computed
     * attendance data into the 'employee_attendance_nonteaching' table.
     *
     * @param int $employeeid The unique identifier of the employee.
     * @param string $date The date for which attendance is being processed (format: 'YYYY-MM-DD').
     * @param object $sched The schedule object containing schedule rows and related information.
     * @return void
     */
    public function processAttendance($employeeid, $date, $sched)
    {
        global $holidayInfo, $campus_tap;

        // Loop through each schedule row if multiple schedules exist
        if ($sched->num_rows() > 0) {
            foreach ($sched->result() as $row_index => $sched_row) {
                $off_time_in = $off_time_out = $actlog_time_in = $actlog_time_out = $terminal = $total_work_hours = $ot_regular = $ot_restday = 
                $ot_holiday = $late = $undertime = $remarks = $holiday_data = "--";

                $terminal = $this->extensions->getTerminalName($campus_tap);
                $holiday_data = (isset($holidayInfo['type']) ? $holidayInfo['type'] : '');
                $is_flexible = $sched_row->flexible == 'YES';
                $breaktime = $sched_row->breaktime;
                $off_time_in = $sched_row->starttime == '00:00:00' || !$sched_row->starttime ? '--' : $sched_row->starttime;
                $off_time_out = $sched_row->endtime == '00:00:00' || !$sched_row->endtime ? '--' : $sched_row->endtime;

                $rows = $sched->num_rows();
                $required_hours = $sched_row->hours;

                // display logs
                [$actlog_time_in, $actlog_time_out] = $this->displayActualLogs($employeeid, $date, $row_index);

                // paki sundan nalang po yung ganitong format, for online application
                [$data, $correction_remarks] = $this->attcompute->displayCorrection($employeeid, $date);

                $required_hours_value = $required_hours + $breaktime;
                // kapag walang required hours, hindi required pumasok  si employee, and mag babase siya sa official time para makuha yung rendered hours
                if($required_hours == 0 && $off_time_in != "--" && $off_time_out != '--'){
                    $total_work_hours = $this->getRenderedHours($off_time_in, $off_time_out, $required_hours, $breaktime);
                }else{
                    $total_work_hours = $this->getRenderedHours($actlog_time_in, $actlog_time_out, $required_hours, $breaktime);
                }

                // $required_value * $rows to get the total of required hours
                $undertime = $this->getUndertime($total_work_hours, $required_hours);
                $absent = $this->getAbsent($undertime, $required_hours);
                if($absent != '--') $undertime = "--";
                $off_time_total = $this->displayOffialTime($rows > 0 ? ($required_hours_value - $breaktime) * $rows : $required_hours_value);
                
                $remarks = $this->displayRemarks([
                    "Flexible Schedule",
                    !($undertime == "00:00" || $undertime == "--") ? "<br> Undertime" : "",
                    !($absent == "00:00" || $absent == "--") ? "<br> Absent" : "",
                    $correction_remarks
                ]);

                $this->db->query("INSERT INTO employee_attendance_nonteaching
                    SET employeeid = '$employeeid',
                        `date` = '$date',
                        off_time_in = '$off_time_in',
                        off_time_out = '$off_time_out',
                        off_time_total = '$off_time_total',
                        actlog_time_in = '$actlog_time_in',
                        actlog_time_out = '$actlog_time_out',
                        terminal = '$terminal',
                        twr = '$total_work_hours',
                        ot_regular = '$ot_regular',
                        ot_restday = '$ot_restday',
                        ot_holiday = '$ot_holiday',
                        late = '$late',
                        undertime = '$undertime',
                        absent = '$absent',
                        remarks = " . $this->db->escape($remarks) . ",
                        holiday = '$holiday_data',
                        is_flexible = '$is_flexible'
                    ");
            }
        }
    }

    /**
     * Displays remarks based on the provided array of other remarks.
     *
     * @param array $other_remarks An optional array of remarks to be displayed. Defaults to an empty array.
     * @return void
     */
    private function displayRemarks($other_remarks = []){
        $remarks = "";

        foreach($other_remarks as $remark) $remarks .= $remark;
        
        return $remarks;
    }
    

    /**
     * Displays the actual logs for a specific employee on a given date.
     *
     * @param int    $employeeid The unique identifier of the employee.
     * @param string $date       The date for which to display logs (format: 'YYYY-MM-DD').
     * @param int    $index      Optional. The index for log retrieval or display. Default is 0.
     *
     * @return mixed Returns the actual logs data for the specified employee and date.
     */
    private function displayActualLogs($employeeid, $date, $index = 0){
        $actlog_time_in = $actlog_time_out = "--";

        $log = $this->attcompute->displayLogTimeFlexiAll($employeeid,$date, "NEW");
        
        // if yung timesheet may dalawang row or more
        if(count($log) > 1 && count($log) >= $index){
            $login = $log[$index][0];
            $logout = $log[$index][1];  
            
            $actlog_time_in = date("h:i A", strtotime($login));
            $actlog_time_out = date("h:i A", strtotime($logout));
        }else{ // if walang logs kasi walang schedule
            $login = $this->timesheet->noSchedLog($employeeid, $date, "ORDER BY logtime ASC");

            if ($login !== false) {
                $actlog_time_in = date("h:i A", strtotime($login));
            }

            $logout = $this->timesheet->noSchedLog($employeeid, $date, "ORDER BY logtime DESC");
            
            if ($logout !== false && $login != $logout) {
                $actlog_time_out = date("h:i A", strtotime($logout));
            }
        }
        
        return [$actlog_time_in, $actlog_time_out];
    }

    public function getTotalRenderedHours($employeeid, $date, $sched){
        $total_work_hours = "00:00";

        foreach ($sched->result() as $row_index => $sched_row) {
                $actlog_time_in = $actlog_time_out = "";
                $required_hours = $sched_row->hours;

                // display logs
                [$actlog_time_in, $actlog_time_out] = $this->displayActualLogs($employeeid, $date, $row_index);
                $required_hours_value = $required_hours;
                $required_hours_sec = $required_hours_value * 60 * 60;
                $required_hours_str = $this->attcompute->sec_to_hm($required_hours_sec);

                $work_hours = $this->getRenderedHours($actlog_time_in, $actlog_time_out, $required_hours_str);
                $total_work_hours = $this->addTime($total_work_hours, $work_hours == "--" ? "00:00" : $work_hours);  
        }

        return $total_work_hours;
    }

    function addTime($currentTime, $toAdd) {
        // Convert both times to DateTime objects for manipulation
        $baseTime = DateTime::createFromFormat('H:i', $currentTime);
        $toAddParts = explode(':', $toAdd);

        // Add hours and minutes separately
        $baseTime->modify("+{$toAddParts[0]} hours");
        $baseTime->modify("+{$toAddParts[1]} minutes");

        // Return formatted time
        return $baseTime->format('H:i');
    }

    /**
     * Generates a condition based on the rendered hours for a given schedule.
     *
     * @param float $total_work_hours The total number of work hours rendered.
     * @param array $sched The schedule details to be evaluated.
     * @return mixed Returns the condition result based on the rendered hours and schedule.
     */

    private function renderedHoursCondition($total_work_hours, $sched)
    {
        // Convert "HH:MM" to total minutes
        list($hours, $minutes) = explode(':', $total_work_hours);
        $total_minutes = ((int)$hours * 60) + (int)$minutes;

        // If there is more than one schedule, divide total work time by 2
        if ($sched->num_rows() > 1) {
            $total_minutes = $total_minutes / 2;
        }

        // Convert total minutes back to "HH:MM"
        $new_hours = floor($total_minutes / 60);
        $new_minutes = $total_minutes % 60;
        $renderedHours = sprintf('%02d:%02d', $new_hours, $new_minutes);


        return $renderedHours == '00:00' ? '--' : $renderedHours;
    }

    /**
     * Calculates the rendered (worked) hours between login and logout times.
     * Optionally applies a callback for further processing and caps the result to required hours.
     *
     * @param string $login The login datetime string (format: 'Y-m-d H:i:s').
     * @param string $logout The logout datetime string (format: 'Y-m-d H:i:s').
     * @param string $required_hours The required hours in "HH:MM" format. Default is '00:00'.
     * @param callable|null $callback Optional. A callback for additional processing. Default is null.
     * @return string The rendered hours in "HH:MM" format, or "--" if invalid.
     */
    public function getRenderedHours($login, $logout, $required_hours = 0, $breaktime = 0, $callback = null)
    {
        $required_hours_sec = $required_hours * 60 * 60;
        $required_hours = $this->attcompute->sec_to_hm($required_hours_sec);

        $break_hours_sec = $breaktime * 60 * 60;
        $breaktime = $this->attcompute->sec_to_hm($break_hours_sec);

        $total_work_hours = "--";

        if ($login && $logout && $login != $logout) {
            $diffInSeconds = abs(strtotime($logout) - strtotime($login));
            $hours = floor($diffInSeconds / 3600);
            $minutes = floor(($diffInSeconds % 3600) / 60);
            $total_work_hours = sprintf("%02d:%02d", $hours, $minutes);

            // If the difference is exactly 24 hours, check if login/logout are valid
            if ($total_work_hours === '24:00') {
                $login_date = date('Y-m-d', strtotime($login));
                $logout_date = date('Y-m-d', strtotime($logout));
                if ($login_date !== $logout_date || $login === '0000-00-00 00:00:00' || $logout === '0000-00-00 00:00:00') {
                    $total_work_hours = '--';
                }
            }

            if($callback != null){
                [$parent, $function, $param] = $callback;
                $total_work_hours = $parent->$function($total_work_hours, $param);
            }

            // Limit rendered hours to required hours if required_hours is not '00:00'
            if ($required_hours !== '00:00') {
                // Convert required hours to seconds
                list($req_h, $req_m) = explode(':', $required_hours);
                $requiredSeconds = ((int)$req_h * 3600) + ((int)$req_m * 60);

                // Convert total work hours to seconds
                list($work_h, $work_m) = explode(':', $total_work_hours);
                $workSeconds = ((int)$work_h * 3600) + ((int)$work_m * 60);

                // If total work exceeds required, cap it
                if ($workSeconds > $requiredSeconds) {
                    $hours = floor($requiredSeconds / 3600);
                    $minutes = floor(($requiredSeconds % 3600) / 60);
                    $total_work_hours = sprintf("%02d:%02d", $hours, $minutes);
                }
            }

            if($breaktime != '00:00' && $breaktime != '--'){
                // Convert breaktime to seconds
                list($break_h, $break_m) = explode(':', $breaktime);
                $breakSeconds = ((int)$break_h * 3600) + ((int)$break_m * 60);

                // Subtract break time from total work hours
                list($work_h, $work_m) = explode(':', $total_work_hours);
                $workSeconds = ((int)$work_h * 3600) + ((int)$work_m * 60);

                // Calculate new total work hours after subtracting break time
                $newWorkSeconds = max(0, $workSeconds - $breakSeconds);
                $hours = floor($newWorkSeconds / 3600);
                $minutes = floor(($newWorkSeconds % 3600) / 60);
                $total_work_hours = sprintf("%02d:%02d", $hours, $minutes);
            }
        }

        return $total_work_hours;
    }

    /**
     * Calculates the undertime hours for a specific employee on a given date.
     * This process will get the total of the work hours and then compare it to required hours
     * 
     * @param int $employeeid The ID of the employee.
     * @param string $date The date for which to calculate undertime (format: 'YYYY-MM-DD').
     * @param float|int $required_hours Optional. The number of required working hours for the day. Default is 0.
     * @param callable|null $callback Optional. A callback function to process the result.
     * @return float The calculated undertime hours.
     */

    public function getUndertime($total_work_hours, $required_hours = 0, $callback = null) 
    {
        // Compute undertime
        $undertime_str = $this->calculateUndertime($total_work_hours, $required_hours);
        
         // additional condition
        if($callback != null){
            [$parent, $function, $condition] = $callback;
            $undertime_str = $parent->$function($undertime_str, $condition);
        }

        return $undertime_str == '00:00' ? '--' : $undertime_str;
    }

    // Helper function to calculate undertime
    public function calculateUndertime($rendered, $required)
    {
        if($rendered == '--') $rendered = "00:00";

        // Convert float required hours to HH:mm format
        $requiredHours = floor($required);
        $requiredMinutes = ($required - $requiredHours) * 60;
        
        // Convert rendered hours from string format
        [$renderedHours, $renderedMinutes] = explode(':', $rendered);
        
        // Calculate undertime in minutes
        $totalRequiredMinutes = ($requiredHours * 60) + $requiredMinutes;
        $totalRenderedMinutes = ($renderedHours * 60) + $renderedMinutes;

        $undertimeMinutes = max(0, $totalRequiredMinutes - $totalRenderedMinutes);
        
        // Format as HH:mm
        return sprintf("%02d:%02d", floor($undertimeMinutes / 60), $undertimeMinutes % 60);
    }


    /**
     * @param mixed $undertime The undertime value to be returned if the condition is true.
     * @param bool $condition Optional. If true, returns the undertime value; otherwise, returns "--". Default is false.
     * @return mixed Returns the undertime value if the condition is true, otherwise returns "--".
     */
    private function undertimeConditions($undertime, $condition = false){
        if($condition) return $undertime;

        return "--";
    }

    /**
     * Determines if the undertime value qualifies as an absence based on required hours.
     *
     * Converts the required hours (as a float) to minutes and compares it with the undertime
     * (in "HH:mm" string format) also converted to minutes. If the undertime is equal to or
     * exceeds the required hours, the undertime value is returned as the absent value.
     * Otherwise, "00:00" is returned, indicating no absence.
     *
     * @param string $undertime       The undertime in "HH:mm" format.
     * @param float  $required_hours  The required hours as a float (e.g., 8.5 for 8 hours 30 minutes).
     * @return string                 The absent value in "HH:mm" format or "00:00" if not absent.
     */

    public function getAbsent($undertime, $required_hours = 0)
    {
        if($undertime == '--' || !$undertime) $undertime = "00:00";
        // Convert float required hours to HH:mm format
        $requiredHours = floor($required_hours);
        $requiredMinutes = ($required_hours - $requiredHours) * 60;

        // Convert undertime hours from string format
        [$undertimeHours, $undertimeMinutes] = explode(':', $undertime);

        // Calculate total required and undertime in minutes
        $totalRequiredMinutes = ($requiredHours * 60) + $requiredMinutes;
        $totalUndertimeMinutes = ($undertimeHours * 60) + $undertimeMinutes;

        // If undertime is equal to required hours, return undertime as absent value
        if ($totalUndertimeMinutes >= $totalRequiredMinutes && $totalRequiredMinutes > 0) {
            return $undertime;
        }

        return "--"; // If not absent, return "00:00"
    }

    /**
     * Converts the required hours value to an official time string in "HH:MM" format.
     *
     * @param float|int $required_hours_value The number of required hours to be converted.
     * @return string Returns the official time in "HH:MM" format, or "--" if the value is zero.
     */
    public function displayOffialTime($required_hours_value){
        $official_time_total_value = ($required_hours_value) * 60 * 60; // convert to seconds
        $off_time_total = $this->attcompute->sec_to_hm($official_time_total_value);
        if ($off_time_total == "00:00") $off_time_total = "--";
        return $off_time_total;
    }
}
