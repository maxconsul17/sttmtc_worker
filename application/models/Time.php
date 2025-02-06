<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Time extends CI_Model {

    public function formatTimeOutputNew($dateData, $is_time=false) {
        if ($dateData === '--') {
            return ''; 
        }

        if($is_time === true) $dateData = date("h:i", strtotime($dateData));

        return $dateData ?? ''; 
    }

    public function formatTimeOutput($dateData, $is_time=false) {
        if ($dateData === '--') {
            return ''; 
        }
        
        if($is_time === true) $dateData = date("H:i", strtotime($dateData));

        return $dateData ?? ''; 
    }

    public function DayFormatted($date){
        $day = date("d",strtotime($date));
        $week = substr(date("l", strtotime($date)), 0, 1);
        $date = $day.'-'.$week;

            return $date;
    }

    public function tableVeticalSpacer($count){
        while ($count>0){
            echo "<tr><td></td></tr>";
            $count --;
        }
    }

    // public function to generate all dates for the month of a given date and return only the first and last dates
    public function generateMonthDates($input_date){
        // Create a DateTime object from the input date
        $date = new DateTime($input_date);

        // Get the first and last day of the month directly
        $first_date = $date->modify('first day of this month')->format('Y-m-d');
        $last_date = $date->modify('last day of this month')->format('Y-m-d');

        // Return the first and last dates as an array
        return [
            'first_date' => $first_date,
            'last_date' => $last_date
        ];
    }

    public function validateDateBetween($dateArray, $selectedDate){
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

    public function exp_time($time) { //explode time and convert into seconds
        $time = explode(':', $time);
        $h = $m = 0;
        if(isset($time[0]) && is_numeric($time[0])) { $h = $time[0];} else{ $h = 0;}
        if(isset($time[1]) && is_numeric($time[1])) { $m = $time[1]; }else {$m = 0;}
        $time = $h * 3600 + $m * 60;
        return $time;
    }
    public function sec_to_hm($time) { //convert seconds to hh:mm
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

    public function totalLateUndertimeDuration($datas)
    {
        $totalHour = 0;
        $totalMinute = 0;
        foreach($datas as $data)
        {
            if($data->lateut_lec && $data->lateut_lec != '--')
            {
                $totalHour += (int) $this->extractTimeComponent($data->lateut_lec,'hour');
                $totalMinute += (int) $this->extractTimeComponent($data->lateut_lec,'minute');
            }
            if($data->lateut_lab && $data->lateut_lab != '--')
            {
                $totalHour += (int) $this->extractTimeComponent($data->lateut_lab,'hour');
                $totalMinute += (int) $this->extractTimeComponent($data->lateut_lab,'minute');
            }
            if($data->lateut_admin && $data->lateut_admin != '--')
            {
                $totalHour += (int) $this->extractTimeComponent($data->lateut_admin,'hour');
                $totalMinute += (int) $this->extractTimeComponent($data->lateut_admin,'minute');
            }
            if($data->lateut_overload && $data->lateut_overload != '--')
            {
                $totalHour += (int) $this->extractTimeComponent($data->lateut_overload,'hour');
                $totalMinute += (int) $this->extractTimeComponent($data->lateut_overload,'minute');
            }
        }

        return [$totalHour,$totalMinute];
        
    }

    function extractTimeComponent($time, $component){
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