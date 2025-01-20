<?php  
/**
* @author justin (with e)
* @copyright 2018
*/

class Extras_lib
{
	function __construct(){}

	function getDateIncluded($from_date, $to_date){
		$days_arr   = array();
        $d_from     = date_create($from_date);
        $d_to       = date_create($to_date);
        $diff_date  = date_diff($d_from, $d_to);
        $count_days = $diff_date->format("%a");

        for ($i=0; $i <= $count_days ; $i++) { 
            $days = date_create($from_date);
            date_add($days,date_interval_create_from_date_string("$i days"));
            $days_arr[$i] = date_format($days, "Y-m-d"); 
        }

        return $days_arr;
	}

    function getDateDifference($from_date, $to_date, $format = '%R%a days'){
        $datetime1 = date_create($from_date);
        $datetime2 = date_create($to_date);
        $interval = date_diff($datetime1, $datetime2);
        
        return $interval->format($format);
    }
    function convertTimeToNumbers(time_val){
  const [time, modifier] = time_val.split(' ');

  let [hours, minutes] = time_val.split(':');

  if (hours === '12') {
    hours = '00';
  }

  if (modifier === 'PM') {
    hours = parseInt(hours, 10) + 12;
  }

  hours = parseInt(hours);
  minutes = parseInt(minutes) / 60;
  return hours + minutes;
}

    function convertTimeToNumber($time){
        $returnNum = 0;

        list($hours, $minutes) = explode(":", $time);
        $returnNum += $hours;
        $returnNum += ($minutes / 60);
        
        return $returnNum;
    }

}
?>