<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class EmployeeAttendance extends CI_Model {

    public function employeeAttendanceTeaching($employeeid, $date){
        $this->load->model("ob_application");
        $this->load->model('Attcompute'); 
        $this->removeExistingAttendance($employeeid, $date);
        $deptid = $this->employee->getindividualdept($employeeid);
        $teachingtype = "teaching";
        $classification_arr = $this->extensions->getFacultyLoadsClassfication();
        $classification_list = array();
        foreach ($classification_arr as $key => $value) {
            $classification_list[$value->id] =  strtolower($value->description);
        }
        $edata = "NEW";
        $x = 0;
        $not_included_ol = array("ABSENT", "EL", "VL", "SL", "CORRECTION", "DA");
        $cto_id_list = $sc_app_id_list = array();
        $firstDayOfWeek = $this->attcompute->getFirstDayOfWeek($employeeid);
        $lastDayOfWeek = $this->attcompute->getLastDayOfWeek($employeeid);
        $lab_holhours = $lec_holhours = $admin_holhours = $rle_holhours = $holiday_type = "";
        $subtotaltpdlec = $totaltpdlec = $subtotaltpdlab = $totaltpdlab = $subtotaltpdadmin = $totaltpdadmin = $subtotaltpdrle = $totaltpdrle = 0;
        $weeklyOverload = $weeklyATH = $weeklyTotalOverload = 0;
        $rendered_lec = $rendered_lab = $rendered_admin = $rendered_rle = $t_rendered_lec = $t_rendered_lab = $t_rendered_admin = $t_rendered_rle = $vacation = $emergency = $sick = $other = 0;
        $lateutlec = $lateutlab = $lateutadmin = $tschedlec = $tschedlab = $tschedadmin = $lateutrle = $tschedrle = $absent =  $tpdlab = $tpdlec = $tpdrle = 0;
        $ot_remarks = $sc_app_remarks = $wfh_app_remarks = $seminar_app_remarks = $tempabsent = "";
        $hasLog = $isSuspension = false;
        // list($ath, $overload_limit) = $this->attcompute->getEmployeeATH($employeeid);
        // $ath = 60 * $ath;
        // $overload_limit = 60*$overload_limit;
        // if(date("l",strtotime($date) != $firstDayOfWeek))
        // {
        //     $tempOverload = $this->attcompute->getPastDayOverload($employeeid,$date, $firstDayOfWeek,"NEW");
        // }

        $used_time = array();
        $isCreditedHoliday = false;
        $firstDate = true;

        if($firstDayOfWeek == date("l",strtotime($date))){
            $weeklyOverload = $weeklyATH = $weeklyTotalOverload = 0;
        }

        $is_holiday_halfday = false;
        $isAffectedAfter = false;
        $is_half_holiday = true;
        $has_after_suspension = false;
        $has_last_log = false;
        $display_hol_remarks = false;
        $isSuspension = false;
        $isRegularHoliday = false;
        $isSpecialNonWorking = false;

        $holidayInfo = array();

        /*get campus where employee tap*/
        $campus_tap = $this->attendance->getTapCampus($employeeid, $date);
        $rate = 0;
        // Holiday
        // $holiday = $this->attcompute->isHolidayNew($employeeid,$date,$deptid); 
        $holiday = $this->attcompute->isHolidayDetailed($employeeid, $date,$deptid, "", "", $teachingtype);
        if(!empty($holiday)){
            $holidayInfo = $this->attcompute->holidayInfo($date);
            if(isset($holidayInfo['holiday_type'])){
                $holiday_type = $holidayInfo['holiday_type'];
                if($holidayInfo['holiday_type']==1) $isRegularHoliday = true;
                if($holidayInfo['holiday_type']==3) $isSuspension = true;
                $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
            }
        }

        $holiday_data = (isset($holidayInfo['type']) && !empty($holidayInfo['type']) ? $holidayInfo['type'] : '');

        $dispLogDate = date("d-M (l)",strtotime($date));

        $sched = $this->attcompute->displaySched($employeeid,$date);

        $countrow = $sched->num_rows();

        $isValidSchedule = true;

        if($countrow > 0){
            if($sched->row(0)->starttime == '00:00:00' && $sched->row(0)->endtime == '00:00:00') $isValidSchedule = false;
        }

        if($x%2 == 0)   $color = " style='background-color: white;'";
        else            $color = " style='background-color: white;'";
        $x++;

        if($firstDate && $holiday){
            $firstDate = false;
        }

        if($countrow > 0 && $isValidSchedule){
            $haswholedayleave = false;
            $hasleavecount = 0;

            ///< for validation of holiday (will only be credited if not absent during last schedule)
            $hasLogprev = $hasLog;
            $hasLog = false;

            if($hasLogprev || $isSuspension)    $isCreditedHoliday = true;
            else                                $isCreditedHoliday = false;
            $schedule_result = $sched->result();

            $tempsched = "";
            $seq = 0;
            $isFirstSched = true;
            $remark_list = array();
            $between_overload = 0;
            $presentLastLog = false;
            
            foreach($schedule_result as $rschedkey => $rsched){
                $off_time_in = $off_time_out = $off_lec = $off_lab = $off_admin = $off_overload = $actlog_time_in = $actlog_time_out = $terminal = $twr_lec = $twr_lab = $twr_admin = $twr_overload = $aims_dept = $campus_name = $subject = $teaching_overload = $ot_regular = $ot_restday = $ot_holiday = $lateut_lec = $lateut_lab = $lateut_admin = $lateut_overload = $absent_lec = $absent_lab = $absent_admin = $service_credit = $cto_credit = $remarks = $holiday_lec = $holiday_lab = $holiday_admin = $holiday_overload = $holiday_name = $lateut_remarks = $pending_ot_remarks= "";
                $overload = 0;
                if($tempsched == $dispLogDate)  $dispLogDate = "";
                $off_time_in = $stime = $rsched->starttime;
                $off_time_out = $etime = $rsched->endtime; 
                $type  = $rsched->leclab;
                $aims_dept = $aimsdept  = $rsched->aimsdept;
                $campus  = $rsched->campus;
                if($campus == "Select an Option") $campus = "";
                $time1 = new DateTime($stime);
                $time2 = new DateTime($etime);
                // $classification = isset($classification_list[$rsched->classification]) ? $classification_list[$rsched->classification] : '';
                // $isOverload = ($classification == 'overload');
                if($type == "LEC"){
                    $to_time = strtotime($stime);
                    $from_time = strtotime($etime);
                    $totaltpdlec += round(abs($to_time - $from_time) / 60,2);
                    $subtotaltpdlec = $this->time->minutesToHours(round(abs($to_time - $from_time) / 60,2));
                    $sched_minutes = round(abs($to_time - $from_time) / 60,2);
                    list($tardy, $absent, $early) = $this->attendance->getSubjTimeConfig($sched_minutes, date('Y', strtotime($date)));
                    // $tardy_start = date("H:i:s", strtotime('+'.$tardy.' minutes', strtotime($stime)));
                    $tardy_start = $rsched->tardy_start;
                    $absent_start = date("H:i:s", strtotime('+'.$absent.' minutes', strtotime($etime)));
                    $earlydismissal = date("H:i:s", strtotime('+'.$early.' minutes', strtotime($stime)));
                    
                    $subtotaltpdlab = "";
                    $subtotaltpdadmin = "";
                    $subtotaltpdrle = "";
                }elseif($type == "LAB"){
                    $to_time = strtotime($stime);
                    $from_time = strtotime($etime);
                    $totaltpdlab += round(abs($to_time - $from_time) / 60,2);
                    $subtotaltpdlab = $this->time->minutesToHours(round(abs($to_time - $from_time) / 60,2));
                    $sched_minutes = round(abs($to_time - $from_time) / 60,2);
                    list($tardy, $absent, $early) = $this->attendance->getSubjTimeConfig($sched_minutes, date('Y', strtotime($date)));
                    // $tardy_start = date("H:i:s", strtotime('+'.$tardy.' minutes', strtotime($stime)));
                    $tardy_start = $rsched->tardy_start;
                    $absent_start = date("H:i:s", strtotime('+'.$absent.' minutes', strtotime($etime)));
                    $earlydismissal = date("H:i:s", strtotime('+'.$early.' minutes', strtotime($stime)));
                    
                    $subtotaltpdlec = "";
                    $subtotaltpdadmin = "";
                    $subtotaltpdrle = "";
                }elseif($type == "ADMIN"){
                    $to_time = strtotime($stime);
                    $from_time = strtotime($etime);
                    $totaltpdadmin += round(abs($to_time - $from_time) / 60,2);
                    $subtotaltpdadmin = $this->time->minutesToHours(round(abs($to_time - $from_time) / 60,2));
                    $tardy_start = $rsched->tardy_start;
                    $absent_start = $rsched->absent_start;
                    $earlydismissal = $rsched->early_dismissal;
                    $subtotaltpdlab = "";
                    $subtotaltpdlec = "";
                    $subtotaltpdrle = "";

                }else{
                    $to_time = strtotime($stime);
                    $from_time = strtotime($etime);
                    $totaltpdrle += round(abs($to_time - $from_time) / 60,2);
                    $subtotaltpdrle = $this->time->minutesToHours(round(abs($to_time - $from_time) / 60,2));
                    $tardy_start = $rsched->tardy_start;
                    $absent_start = $rsched->absent_start;
                    $earlydismissal = $rsched->early_dismissal;
                    $subtotaltpdlab = "";
                    $subtotaltpdlec = "";
                    $subtotaltpdadmin = "";
                }

                $seq += 1;
                

                // logtime
                $used_time = array();
                list($login, $logout, $q, $haslog_forremarks, $used_time, $is_ob, $campus_in, $campus_out) = array_pad($this->attcompute->displayLogTime($employeeid, $date, $stime, $etime, "NEW", $seq, $absent_start, $earlydismissal, $used_time, $campus), 6, '');

                if($q == "VACATION" || $q == "SICK" || $q == "EMERGENCY"){
                    if(date('H:i:s', strtotime($login)) != $stime && date('H:i:s', strtotime($logout)) != $etime) $login = $logout = "";
                }
                if($seq == $countrow){
                    $weeklyOverloadOT = $this->attcompute->displayLogTimeOutsideOT($employeeid,$date);
                    if($weeklyOverloadOT){
                        $overload += $weeklyOverloadOT;
                        $weeklyOverload += $weeklyOverloadOT;
                    }
                }

                if($login=='0000-00-00 00:00:00') $login = '';
                if($logout=='0000-00-00 00:00:00') $logout = '';

                list($otreg,$otrest,$othol) = $this->attcompute->displayOt($employeeid,$date,true);
                if($otreg || $otrest || $othol){
                    $ot_remarks = "OVERTIME APPLICATION";
                }

                $coc = $this->attcompute->displayCOC($employeeid,$date,true);
                if($coc > 0){
                    if($ot_remarks != "APPROVED COC APPLICATION"){
                        $ot_remarks.=($ot_remarks?", APPROVED COC APPLICATION":"APPROVED COC APPLICATION");
                    }
                }

                $ot_app =  $this->attcompute->displayOTApp($employeeid, $date);
                if ($ot_app) {
                    if ($ot_app->status == "APPROVED") {
                        $ot_regular = $ot_app->approved_total;
                    } else {
                        $pending_ot_remarks = "PENDING OVERTIME APPLICATION";
                    }
                }
        

                $sc_application = $this->attcompute->displaySCAttendance($employeeid,$date, $stime, $etime);
                if($sc_application > 0){
                    if($sc_app_remarks != "Approved Conversion Service Credit"){
                        $sc_app_remarks.=($sc_app_remarks?", Approved Conversion Service Credit":"Approved Conversion Service Credit");
                    }
                }


                // Leave
                list($el, $vl, $sl, $ol, $oltype, $ob, $abs_count, $l_nopay, $obtypes, $ob_id, $l_nopay_remarks) = array_pad($this->attcompute->displayLeave($employeeid, $date, '', $stime, $etime, $seq), 11, '');
                list($cto, $ctohalf, $cto_id, $cto_sched) = $this->attcompute->displayCTOUsageAttendance($employeeid,$date, $stime, $etime);
                list($sc_app, $sc_app_half, $sc_app_id) = $this->attcompute->displaySCUsageAttendance($employeeid,$date, $stime, $etime);

                // $pvl = $this->attcompute->displayPVL($employeeid,$date);
                // if($ol == "0.50"){
                //     $login = $stime;
                //     $logout = $etime;
                // }

                if($ol == "DIRECT"){
                    $is_wfh = $this->attcompute->isWfhOB($employeeid,$date);
                    
                    if($is_wfh->num_rows() == 1 && $obtypes==2 ){
                        $ob_id = $is_wfh->row()->aid;
                        $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
                        if($hastime->num_rows() == 0) $ol = $oltype = $ob = "";
                        if($wfh_app_remarks != "Approved Work From Home Application"){
                            $wfh_app_remarks.=($wfh_app_remarks?", Approved Work From Home Application":"Approved Work From Home Application");
                        }
                    }
                }else if($ol == "DA" && $obtypes==3 && $ob_id && $is_ob == $ob_id){
                    $ob_details = $this->ob_application->getLeaveDetails($ob_id);
                    if($ob_details['timefrom'] && $ob_details['timeto']){
                        $login = $ob_details['timefrom'];
                        $logout = $ob_details['timeto'];
                    }
                }else if($ol == "DA" && $obtypes==2 && $ob_id && $is_ob == $ob_id){
                    $ob_details = $this->ob_application->getLeaveDetails($ob_id);
                    
                    if($ob_details['timefrom'] && $ob_details['timeto']){
                        $login = $ob_details['timefrom'];
                        $logout = $ob_details['timeto'];
                    }
                }else if($ol == "DA" && $obtypes==1 && $ob_id){
                    $ob_details = $this->ob_application->getLeaveDetails($ob_id);
                    if($ob_details['sched_affected']){
                        list($login, $logout) = explode('|', $ob_details['sched_affected']);
                    }
                } else if($ol == "CORRECTION" && $ob_id){
                    // $ob_details = $this->ob_application->getLeaveDetails($ob_id);
                    
                    // if(isset($ob_details['app_id'])){
                    //     $timerecord = $this->employeemod->findApplyTimeRecordNew($ob_details['app_id']);
                    //     if(isset($timerecord[($seq-1)]->request_time) && $timerecord[($seq-1)]->request_time){
                    //         $login_logout = explode('-', $timerecord[($seq-1)]->request_time);
                    //         $login = isset($login_logout[0]) ? $login_logout[0] : '';
                    //         $logout = isset($login_logout[1]) ? $login_logout[1] : '';
                    //     }
                    // }
                } 

                // if($cto && $cto_id){
                //     if($ctohalf){
                //         list($login, $logout) = explode("|", $cto_sched);
                //     }else{
                //         $login = $stime;
                //         $logout = $etime;
                //     }
                // }

                $cs_app = $this->attcompute->displayChangeSchedApp($employeeid,$date);
                $pending = $this->attcompute->displayPendingApp($employeeid,$date, "", $ol);
                $pending .= $this->attcompute->displayPendingOBWfh($employeeid,$date);

                // Absent
                $initial_absent = $absent = $this->attcompute->displayAbsent($stime,$etime,$login,$logout,$employeeid,$date,$earlydismissal, $absent_start);

                $vl_approved = $this->attcompute->approvedLeaveApplication($employeeid, $date);
                if ($vl_approved || $oltype == 'OFFICIAL BUSINESS') {
                    if ($vl_approved[0]->nodays == 0.50) {
                        list($start_time, $end_time) = explode('|', $vl_approved[0]->sched_affected);
                        if($start_time == $stime ){
                            $login = $stime;
                            $logout = $etime;
                        }
                    }else {
                        $login = $stime;
                        $logout = $etime;
                    }
                }


                // additional absent condition;
                if(array_key_exists($rschedkey - 1, $schedule_result)){  // First Solution - base to previous schedule absent start - comment ko muna baka kailanganin
                    $previousIndex = $rschedkey - 1;
                    $previousSchedule = $schedule_result[$previousIndex];
                    $previousAbsentStart = $previousSchedule->absent_start;

                    if(strtotime($previousAbsentStart) < strtotime($login)){
                        $schedstart   = strtotime($stime);
                        $schedend   = strtotime($etime);
                        $totalHoursOfWork = round(abs($schedend - $schedstart) / 60,2);
                        $absent = $totalHoursOfWork;
                    }
                }

                // additional absent condition; 
                // if($logout && strtotime($rsched->absent_start) > strtotime(date("H:i", strtotime($logout)))){
                //     $schedstart   = strtotime($stime);
                //     $schedend   = strtotime($etime);
                //     $totalHoursOfWork = round(abs($schedend - $schedstart) / 60,2);
                //     $absent = $totalHoursOfWork;
                // }

                if($oltype == "ABSENT")                 $absent = $absent;
                else if($holiday && $isCreditedHoliday) $absent = "";
                if ($vl >= 1 || $el >= 1 || $sl >= 1 || $ob >= 1 || ($cto && $ctohalf == 0) || ($sc_app && $sc_app_half == 0) || (strpos($oltype, "LEAVE") !== false && ($sl > 1 || $vl > 1 || $el > 1 || $ol > 1))){
                    $absent = "";
                    $haswholedayleave = true;
                }
                if ($vl > 0  || $el > 0 || $sl > 0 || $ob > 0 || $cto || $sc_app || (strpos($oltype, "LEAVE") !== false && ($sl > 0 || $vl > 0 || $el > 0 || $ol > 0)) ){
                    $absent = "";
                    $hasleavecount++;
                }
                

                list($lateutlec,$lateutlab,$lateutadmin,$tschedlec,$tschedlab,$tschedadmin,$lateutrle,$tschedrle) = $this->attcompute->displayLateUT($stime,$etime,$tardy_start,$login,$logout,$type,$absent);

                if($el || $vl || $sl || ($holiday && $isCreditedHoliday) || $cto || $sc_app ){
                    $lateutlec = $lateutlab = $lateutadmin = $tschedlec = $tschedlab = $tschedadmin = $tschedrle = "";
                }

                // if($absent && $presentLastLog){
                //     if($type == "LEC"){
                //         $lateutlec = $absent;
                //         $absent = $tschedlec = '';
                //     }elseif($type == "LAB"){
                //         $lateutlab = $absent;
                //         $absent = $tschedlab = '';
                //     }elseif($type == "ADMIN"){
                //         $lateutadmin = $absent;
                //         $absent = $tschedadmin = '';
                //     }else{
                //         $lateutrle = $absent;
                //         $absent = $tschedrle = '';
                //     }
                // }

                // COMMENT MUNA TO -MAX
                // NIREREMOVE NYA YUNG REMARKS FOR OVERLOAD
                // if($absent && !$type) $absent = '';

                // if($absent && !$type) $absent = '';

                //Total Hours of Work
                $schedstart   = strtotime($stime);
                $schedend   = strtotime($etime);

                if($holiday){
                    if($this->attcompute->isHolidayWithpay($date) == "YES"){
                        if($tempabsent){
                            $absent = $absent;
                        }
                    }else{
                        if(!$login && !$logout){
                            $absent = $absent;
                        }
                    }
                }else{
                    $tempabsent = true;
                }



                // Overload
                // if(!$absent && !$lateutlec){
                //     $tempOverload += $this->attcompute->displayOverloadTime($stime,$etime,$lateutlab);
                // }else{
                //     $tempOverload += 0;
                // }//late-under-undertime remarks
                $ob_data = $this->attcompute->displayLateUTAbs($employeeid, $date);

                // GET CURRENT LOG TIME BY SCHEDULE
                if(!$login ){
                    $login = $this->timesheet->currentLogtimeAMSchedule($employeeid, $date, $stime, $absent_start, $used_time);
                    if(in_array($login, $used_time)){
                        $login = "";
                    }

                    if(isset($last_login) && $last_login == $login){
                        $login = "";
                    }
                }

                // GET CURRENT LOG TIME BY SCHEDULE
                if(!$logout ){
                    $logout = $this->timesheet->currentLogtimePMSchedule($employeeid, $date, $etime, $earlydismissal, $used_time);
                    if(in_array($logout, $used_time)){
                        $logout = "";
                    }

                    if(isset($last_logout) && $last_logout == $logout){
                        $logout = "";
                    }
                }

                $log_remarks = '';

                // $absent = "01:30";

                if ($absent && !$holiday) {
                    if (!$login && !$logout || $absent) {
                        $log_remarks = 'NO TIME IN AND OUT ';
                    } elseif (!$login) {
                        $log_remarks = 'NO TIME IN';
                    } elseif (!$logout) {
                        $log_remarks = 'NO TIME OUT';
                    }

                    // if (!$login || !$logout || $absent) {
                    //     $log_remarks = 'NO TIME IN AND OUT';
                    // }
                }
              
                if(!$login){
                    $login = $this->timesheet->getNooutData($employeeid, $date);
                }
                
                if($login && $logout && $isFirstSched) $has_last_log = true;

                $is_holiday_halfday = $this->attcompute->isHolidayNew($employeeid, $date,$deptid, "", "on");
                list($fromtime, $totime) = $this->extensions->getHolidayHalfdayTime($date);
                if($is_holiday_halfday && ($fromtime && $totime) ){
                    $holidayInfo = $this->attcompute->holidayInfo($date);
                    $is_half_holiday = true;
                    if(isset($holidayInfo["holiday_type"])){
                        $isAffected = $this->attcompute->affectedBySuspension(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
                        if($isAffected){
                            $is_half_holiday = true;
                            if($holidayInfo["holiday_type"] == 5){
                                if($has_after_suspension){
                                    if($login && $logout){
                                        $rate = 100;
                                    }else{
                                        if($has_last_log) $rate = 100;
                                        else $rate = 50;
                                    }
                                }else{
                                    $isAffectedBefore = $this->attcompute->affectedBySuspensionBefore(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
                                    if($isAffectedBefore){
                                        $rate = 50;
                                        if($has_last_log && !$isFirstSched) $rate = 100;
                                    }

                                    $isAffectedAfter = $this->attcompute->affectedBySuspensionAfter(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
                                    if($isAffectedAfter){
                                        $rate = 50;
                                        if($has_last_log) $rate = 100;
                                    }
                                }
                            }

                            if ($holidayInfo["holiday_type"] == 1) {
                                if ($holidayInfo["sched_count"] == "first") {
                                    $login = $rschedkey == 0 ? date("h:i A", strtotime($off_time_in)) : "";
                                    $logout = $rschedkey == 0 ? date("h:i A", strtotime($off_time_out)) : "";
                                } elseif ($holidayInfo["sched_count"] == "second") {
                                    $login = $rschedkey == 0 ? "" : date("h:i A", strtotime($off_time_in));
                                    $logout = $rschedkey == 0 ? "" : date("h:i A", strtotime($off_time_out));
                                }
                            }

                            $display_hol_remarks = true;
                        }else{
                            $is_half_holiday = false;
                            if($holidayInfo["holiday_type"] == 5) $rate = 100;
                            else $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
                            if(!$login && !$logout) $rate = 0;
                        }

                        if($is_half_holiday){
                            $hol_lec = $this->time->hoursToMinutes($subtotaltpdlec);
                            $hol_lab = $this->time->hoursToMinutes($subtotaltpdlab);
                            $hol_admin = $this->time->hoursToMinutes($subtotaltpdadmin);
                            $hol_rle = $this->time->hoursToMinutes($subtotaltpdrle);
                            $lec_holhours = $hol_lec * $rate / 100;
                            $lab_holhours = $hol_lab * $rate / 100;
                            $admin_holhours = $hol_admin * $rate / 100;
                            $rle_holhours = $hol_rle * $rate / 100;

                            // $totlec_holhours += $lec_holhours;
                            // $totlab_holhours += $lab_holhours;
                            // $totadmin_holhours += $admin_holhours;
                            // $totrle_holhours += $rle_holhours;

                            $lec_holhours = $this->time->minutesToHours($lec_holhours);
                            $lab_holhours = $this->time->minutesToHours($lab_holhours);
                            $admin_holhours = $this->time->minutesToHours($admin_holhours);
                            $rle_holhours = $this->time->minutesToHours($rle_holhours);
                        }

                    }else{
                        $half_holiday = $this->attcompute->holidayHalfdayComputation(date("H:i", strtotime($login)), date("H:i", strtotime($logout)), date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)));
                        if($half_holiday > 0){
                            $lateutlec = $lateutlab = $lateutadmin = $lateutrle = $this->attcompute->sec_to_hm(abs($half_holiday));
                        }else{
                            $lateutlec = $lateutlab = $lateutadmin = $lateutrle = "";
                        }
                    }
                }else{
                    $holidayInfo = $this->attcompute->holidayInfo($date);
                    $is_half_holiday = true;
                    if(isset($holidayInfo["holiday_type"])){
                        $is_half_holiday = true;
                        if($holidayInfo["holiday_type"] == 5) $rate = 50;
                        if($isRegularHoliday && $holidayInfo["holiday_type"] == 1) {
                            $login = date("h:i A",strtotime($off_time_in));
                            $logout = date("h:i A",strtotime($off_time_out));
                        }
                        else $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
                        if($is_half_holiday){
                            $hol_lec = $this->time->hoursToMinutes($subtotaltpdlec);
                            $hol_lab = $this->time->hoursToMinutes($subtotaltpdlab);
                            $hol_admin = $this->time->hoursToMinutes($subtotaltpdadmin);
                            $hol_rle = $this->time->hoursToMinutes($subtotaltpdrle);

                            $lec_holhours = $hol_lec * $rate / 100;
                            $lab_holhours = $hol_lab * $rate / 100;
                            $admin_holhours = $hol_admin * $rate / 100;
                            $rle_holhours = $hol_rle * $rate / 100;

                            // $totlec_holhours += $lec_holhours;
                            // $totlab_holhours += $lab_holhours;
                            // $totadmin_holhours += $admin_holhours;
                            // $totrle_holhours += $rle_holhours;

                            $lec_holhours = $this->time->minutesToHours($lec_holhours);
                            $lab_holhours = $this->time->minutesToHours($lab_holhours);
                            $admin_holhours = $this->time->minutesToHours($admin_holhours);
                            $rle_holhours = $this->time->minutesToHours($rle_holhours);
                        }
                    }else{
                        $is_half_holiday = false;
                    }
                }

                if($el || $vl || $sl || $is_half_holiday || ($holiday && $isCreditedHoliday)){
                    if($holiday)
                    {
                        $totaltpdlec = (int)$totaltpdlec - (int)$tpdlec;
                        $totaltpdlab = (int)$totaltpdlab - (int)$tpdlab;
                        $totaltpdrle = (int)$totaltpdrle - (int)$tpdrle;
                        $lateutlec = $lateutlab = $lateutadmin = $tschedlec = $tschedlab = $tschedadmin = $lateutrle = $tschedrle = $absent =  $tpdlab = $tpdlec = $tpdrle = "";
                    }
                }
                

                if($lateutlec == "00:00") $lateutlec = "";
                if($lateutlab == "00:00") $lateutlab = "";
                if($lateutadmin == "00:00") $lateutadmin = "";
                if($lateutrle == "00:00") $lateutrle = "";
                if($subtotaltpdlab == "00:00") $subtotaltpdlab = "";
                if($subtotaltpdlec == "00:00") $subtotaltpdlec = "";
                if($subtotaltpdadmin == "00:00") $subtotaltpdadmin = "";
                if($subtotaltpdrle == "00:00") $subtotaltpdrle = "";

                if(strtotime($login) > strtotime($date." ".$stime)) $start = strtotime($login);
                else $start = strtotime($date." ".$stime);
                if(strtotime($logout) > strtotime($date." ".$etime)) $end = strtotime($date." ".$etime);
                else $end = strtotime($logout);
                if ($oltype == 'OFFICIAL BUSINESS') {
                    if(strtotime($date." ".$login) > strtotime($date." ".$stime)) $start = strtotime($date." ".$login);
                    else $start = strtotime($date." ".$stime);
                    if(strtotime($date." ".$logout) > strtotime($date." ".$etime)) $end = strtotime($date." ".$etime);
                    else $end = strtotime($logout);
                }else if($ol == "CORRECTION"){
                    if(strtotime($date." ".$login) > strtotime($date." ".$stime)) $start = strtotime($date." ".$login);
                    else $start = strtotime($date." ".$stime);
                    if(strtotime($date." ".$logout) > strtotime($date." ".$etime)) $end = strtotime($date." ".$etime);
                    else $end = strtotime($logout);
                }


                $mins = ($end - $start) / 60;

                if(!$end || !$start || $mins < 0) $mins = 0;

                if($rsched->leclab == "LEC"){
                    if($isRegularHoliday){
                        $rendered_lec = "0:00";
                    }else{
                        //$rendered_lec = $this->time->minutesToHours($time_diff);
                        $rendered_lec = $this->time->calculateTimeDifferenceInHoursMinutes($start,$end);
                        $t_rendered_lec += $mins;
                        $weeklyATH += $mins;
                    }
                }
                elseif($rsched->leclab == "LAB"){
                    if($isRegularHoliday){
                        $rendered_lab = "0:00";
                    }else{
                        $rendered_lab = $this->time->minutesToHours($mins);
                        $t_rendered_lab += $mins;
                        $weeklyATH += $mins;
                    }
                }
                elseif($rsched->leclab == "ADMIN"){
                    if($isRegularHoliday){
                        $rendered_admin = "0:00";
                    }else{
                        $rendered_admin = $this->time->minutesToHours($mins);
                        $t_rendered_admin += $mins;
                    }
                }else{
                    if($isRegularHoliday){
                        $rendered_rle = "0:00";
                    }else{
                        $rendered_rle = $this->time->minutesToHours($mins);
                        $t_rendered_rle += $mins;
                    }
                }


              
                $excessTime = 0;
                if($login && $logout && $stime && $etime && ($rsched->leclab == "LEC" || $rsched->leclab == "LAB")){
                    if(isset($schedule_result[$rschedkey + 1])){
                        if(date('H:i',strtotime($etime)) < date('H:i',strtotime($schedule_result[$rschedkey + 1]->starttime)) && $mins > 0){
                            $schedTime = strtotime($schedule_result[$rschedkey + 1]->starttime);
                            $logTime = strtotime($etime);
                            $excessTime += ($schedTime - $logTime) / 60;
                            $between_overload++;
                        }
                    }else if(isset($schedule_result[$rschedkey - 1])){
                        if(date('H:i',strtotime($stime)) > date('H:i',strtotime($schedule_result[$rschedkey - 1]->endtime)) && $mins > 0){
                            $schedTime = strtotime($stime);
                            $logTime = strtotime(substr($login, 11));
                            $excessTime += ($schedTime - $logTime) / 60;
                        }
                    }else{
                        if($between_overload == 0){
                            if(date('H:i',strtotime($stime)) > date('H:i',strtotime($login)) && $mins > 0){
                                $schedTime = strtotime($stime);
                                $logTime = strtotime(substr($login, 11));
                                $excessTime += ($schedTime - $logTime) / 60;
                            }

                            if(date('H:i',strtotime($logout)) > date('H:i',strtotime($etime)) && $mins > 0){
                                $schedTime = strtotime($etime);
                                $logTime = strtotime(substr($logout, 11));
                                $excessTime += ($logTime - $schedTime) / 60;
                            }
                        }else{
                            if(date('H:i',strtotime($logout)) > date('H:i',strtotime($etime)) && $mins > 0){
                                $schedTime = strtotime($etime);
                                $logTime = strtotime(substr($logout, 11));
                                $excessTime += ($logTime - $schedTime) / 60;
                            }
                        }
                    }

                    if($excessTime != 0){
                        $overload += $excessTime;
                        $weeklyOverload += $excessTime;
                        
                    }
                }

                if($login && $logout && ($login != $logout)){
                    $presentLastLog = true;
                }else{
                    $presentLastLog = false;
                }
                
                if(!empty($holidayInfo) && $holidayInfo['type'] == 'OTHERS'){ // if other holiday, it will automatically set the logs as off time in
                    $login = date("h:i A",strtotime($off_time_in));
                    $logout = date("h:i A",strtotime($off_time_out));
                    $log_remarks = str_replace('NO TIME IN AND OUT', '', $log_remarks);
                    $log_remarks .= $holidayInfo['description'];
                    $absent = false;
                    
                    // sum the total render and total of absent
                    $subtotaltpdlec = $this->time->sumTimes([$subtotaltpdlec, $tschedlec]);
                    $subtotaltpdlab = $this->time->sumTimes([$subtotaltpdlab, $tschedlab]);
                    $subtotaltpdadmin = $this->time->sumTimes([$subtotaltpdadmin, $tschedadmin]);
                    $subtotaltpdrle = $this->time->sumTimes([$subtotaltpdrle, $tschedrle]);

                    $subtotaltpdlec = $subtotaltpdlec == "00:00" ? "" : $subtotaltpdlec;
                    $subtotaltpdlab = $subtotaltpdlab == "00:00" ? "" : $subtotaltpdlab;
                    $subtotaltpdadmin = $subtotaltpdadmin == "00:00" ? "" : $subtotaltpdadmin;
                    $subtotaltpdrle = $subtotaltpdrle == "00:00" ? "" : $subtotaltpdrle;

                    // remove the absent total
                    $tschedlec = "";
                    $tschedlab = "";
                    $tschedadmin = "";
                    $tschedrle = "";
                }
              
                $off_lec = $subtotaltpdlec ? $subtotaltpdlec : "";
                $off_lab = $subtotaltpdlab ? $subtotaltpdlab : "";
                $off_admin = $subtotaltpdadmin ? $subtotaltpdadmin : "";
                $off_overload = $subtotaltpdrle ? $subtotaltpdrle : "";

                $actlog_time_in = ($login && !$absent ? date("h:i A",strtotime($login)) : "--");
                $actlog_time_out = ($logout && !$absent ? date("h:i A",strtotime($logout)) : "--");

                $terminal = $this->extensions->getTerminalName($campus_tap);

                $twr_lec = $rendered_lec ? $rendered_lec : "";
                $twr_lab = $rendered_lab  ? $rendered_lab : "";
                $twr_admin = $rendered_admin ? $rendered_admin : "";
                $twr_overload = $rendered_rle ? $rendered_rle : "";

                // $aims_dept = $aimsdept ? $this->extensions->getAimsDesc($aimsdept) : "";
                $aims_dept = $aimsdept;

                $campus_name = $this->extensions->getCampusDescription($rsched->campus);
                $campus_in = $this->extensions->getCampusDescription($campus_in ?? '');
                $campus_out = $this->extensions->getCampusDescription($campus_out ?? '');

                $subject = $rsched->subject;
                $teaching_overload = ($overload ? $this->time->minutesToHours($overload) : "");
                $ot_regular = ($otreg ? $this->attcompute->sec_to_hm($this->attcompute->exp_time($otreg)) : "");
                $ot_restday = ($otrest ? $this->attcompute->sec_to_hm($this->attcompute->exp_time($otrest)) : "");
                $ot_holiday = ($othol ? $this->attcompute->sec_to_hm($this->attcompute->exp_time($othol)) : "");
                

                $lateut_lec = $lateutlec ? date("H:i",strtotime($lateutlec)) : "";
                $lateut_lab = $lateutlab ? date("h:i",strtotime($lateutlab)) : "";
                $lateut_admin = $lateutadmin ? date("h:i",strtotime($lateutadmin)) : "";
                // $lateut_overload = $lateutrle ? $lateutrle : "";
                $lateut_overload = $lateutrle ? $this->time->minutesToHours($lateutrle) : "";

                // ob application
                $ob_app_status = $this->attcompute->displayApprWholeDayOBApp($employeeid, $date);
                if ($ob_app_status) { 
                    $login = $stime;
                    $logout = $etime;
                }


                
                $absent_lec = ($tschedlec != "0:00") ? $tschedlec : "";
                $absent_lab = ($tschedlab != "0:00") ? $tschedlab : "";
                $absent_admin = ($tschedadmin != "0:00") ? $tschedadmin : "";
                $absent_overload = ($tschedrle != "0:00") ? $tschedrle : "";

                if($sc_app_half == 1 || !$sc_app){
                    $service_credit = ($sc_app ? $sc_app : "");
                }

                if($ctohalf == 1 || !$cto){
                    $cto_credit = ($cto ? $cto : "");
                }

                $other = ($ol && !in_array($ol, $not_included_ol) && $oltype)  ? 1 : ""; 

                

                $rwcount = 1;
                if(!$dispLogDate) $rwcount = 1;
                if($haswholedayleave || $pending || $holiday) $rwcount = $countrow;

                if($dispLogDate || (!$haswholedayleave && !$pending && !$holiday)){
                    if($sc_app_half == 0 && $sc_app){
                        $service_credit = ($sc_app ? $sc_app : "");
                    }

                    if($ctohalf == 0 && $cto){
                        $cto_credit = ($cto ? $cto : "");
                    }

                    if ($ol  === "late"){
                        if ($lateutadmin) {
                            $remarks .= "<h5 style='color:green;'>Excused Late</h5>";
                        }
                    }elseif(($lateutlab != "" || $lateutlec != "" || $lateutrle != "" || $lateutadmin != "" && !$ol)) {
                        if(date("H:i:s",strtotime($logout)) < date("H:i:s",strtotime($etime))){
                            $remarks .= "<h5 style='color:red;'>Unexcused Undertime</h5>";
                            $lateut_remarks = "undertime";
                        }else{
                            $remarks .= "<h5 style='color:red;'>Unexcused Late</h5>";
                            $lateut_remarks = "late";
                        }
                    }

                    

                    if ($oltype == 'OFFICIAL BUSINESS') {
                        $obType = $this->extensions->getTypeOfOB($employeeid, $date);
                        $oltype = $obType == 'SEMINAR' ? $obType : $oltype;
                    }


                    $ob_app_status = $this->attcompute->displayApprWholeDayOBApp($employeeid, $date);

                    if ($ob_app_status && $ol != 'CORRECTION') { 
                        if ($ob_app_status->status == "APPROVED") {
                            $actlog_time_in = $off_time_in;
                            $actlog_time_out = $off_time_out;
                        } 
                    }
                   
                    $other_leave = $this->attcompute->otherApprovedLeave($employeeid, $date);
                    if ($other_leave) { 
                        if ($other_leave->subtype == "BL") {
                            $rwcount = 2;
                            $other = 1; 
                        } else if ($other_leave->subtype == "EL") {
                            $rwcount = 2;
                            $el = 1; 
                        }else if($other_leave->subtype == "SL"){
                            $rwcount = 2;
                            $sl = 1; 
                        }else if($other_leave->subtype == "VL"){
                            $rwcount = 2;
                            $vl = 1; 
                            $other = "";
                        }else if($other_leave->subtype == "OS"){
                            $rwcount = 2;
                            $other = 1; 
                        }
                    }

                // Use Service Credit
                $use_sc_remarks = "";
                $use_sc_status = $this->attcompute->getUseServiceCreditStatus($employeeid, $date);
                if ($use_sc_status) {
                    if ($use_sc_status->status == "PENDING") {
                        if($use_sc_status->credit_used == "1"){
                            $use_sc_remarks .= "<br> PENDING USE SERVICE CREDIT APPLICATION <br>";
                        }else{
                            $sched_affected = $use_sc_status->sched_affected;        
                            $sched_time = explode("|", $sched_affected);        
                            if($sched_time[0] == $stime){
                                $use_sc_remarks .= "<br> PENDING USE SERVICE CREDIT APPLICATION <br>";
                            }
                        }
                    } elseif ($use_sc_status->status == "APPROVED") {
                        if($use_sc_status->credit_used == "1"){
                            $actlog_time_in = date("h:i A",strtotime($off_time_in));
                            $actlog_time_out = date("h:i A",strtotime($off_time_out));
                            $use_sc_remarks .= "<br> APPROVED USE SERVICE CREDIT APPLICATION <br>";
                        }else{
                            $sched_affected = $use_sc_status->sched_affected;        
                            $sched_time = explode("|", $sched_affected);        
                            if($sched_time[0] == $stime){
                                $use_sc_remarks .= "<br> APPROVED USE SERVICE CREDIT APPLICATION <br>";
                                $actlog_time_in = date("h:i A",strtotime($off_time_in));
                                $actlog_time_out = date("h:i A",strtotime($off_time_out));
                            }

                        }
                    }
                }
                 
                    $emergency = ($el) ? $el : "";
                    $vacation = ($vl) ? $vl : "";
                    $sick = ($sl) ? $sl : "";
                    
                    $remarks .= ($log_remarks?$log_remarks."<br>":"");
                    $remarks .=  ($ot_remarks?$ot_remarks."<br>":"");
                    $remarks .=  ($sc_app_remarks?$sc_app_remarks."<br>":"");
                    $remarks .=  ($wfh_app_remarks?$wfh_app_remarks."<br>":"");
                    $remarks .=  ($pending_ot_remarks?$pending_ot_remarks."<br>":"");
                    $remarks .=  ($use_sc_remarks?$use_sc_remarks."<br>":"");
                    $remarks .=  $dispLogDate ? ($cs_app?$cs_app.'<br>':'') : '';
                    $remarks .=  ($pending)?"PENDING ".$pending.'<br>'
                            :($ol ? ($oltype ? ($oltype == "ABSENT" ? "ABSENT W/ FILE<br>" 
                            : ($oltype != "Excuse Slip (late)" ? $oltype.'<br>':"")) : ($l_nopay_remarks ? $l_nopay_remarks : $this->employeemod->othLeaveDesc($ol)).'<br>') 
                            : ($q ? ($q == "1" ? "" : "".'<br>') : ""));
                    // $remarks .= (($holiday || $is_half_holiday) && isset($holidayInfo['description']))?$holidayInfo['description']:"";
                    $remarks .= (($isRegularHoliday || $isSuspension ) && isset($holidayInfo['description']))?$holidayInfo['description']:"";
                    $remarks .= $cto?'APPROVED CTO APPLICATION<br>':'';
                    $remarks .= $sc_app?'USE SERVICE CREDIT<br>':'';
                    // $remarks .= ($pvl > 0)?'APPROVED PROPORTIONAL VACATION LEAVE<br>':'';
                    $sc_status = $this->attcompute->getServiceCreditStatus($employeeid, $date);
                    
                    if ($sc_status > 0) {
                        if ($sc_status->status == "PENDING")  $remarks .= ($remarks ? "<br> PENDING SERVICE CREDIT APPLICATION <br>" : "");
                        elseif ($sc_status->status == "APPROVED") $remarks .= ($remarks ? "<br> APPROVED SERVICE CREDIT APPLICATION <br>" : "");
                    }
                    
                   

                    if(isset($schedule_result[$rschedkey + 1]) && $rwcount == $countrow && !$sc_app && !$ol){
                        $stime_ = $schedule_result[$rschedkey + 1]->starttime;
                        $etime_ = $schedule_result[$rschedkey + 1]->endtime; 
                        $type_  = $schedule_result[$rschedkey + 1]->leclab;
                        $aimsdept_  = $rsched->aimsdept;
                        $time1_ = new DateTime($stime_);
                        $time2_ = new DateTime($etime_);
                        $seq_ = $seq++;
                        $tardy_start_ = $schedule_result[$rschedkey + 1]->tardy_start;
                        $absent_start_ = $schedule_result[$rschedkey + 1]->absent_start;
                        $earlydismissal_ = $schedule_result[$rschedkey + 1]->early_dismissal;
                        $campus_ = $schedule_result[$rschedkey + 1]->campus;

                        $used_time_ = array();
                        list($login_,$logout_,$q_,$haslog_forremarks_,$used_time_) = $this->attcompute->displayLogTime($employeeid,$date,$stime_,$etime_,"NEW",$seq_,$absent_start_,$earlydismissal_,$used_time_, $campus_);
                        if($q_ == "VACATION" || $q_ == "SICK" || $q_ == "EMERGENCY"){
                            if(date('H:i:s', strtotime($login_)) != $stime_ && date('H:i:s', strtotime($logout_)) != $etime_) $login_ = $logout_ = "";
                        }
                        if($login_=='0000-00-00 00:00:00') $login_ = '';
                        if($logout_=='0000-00-00 00:00:00') $logout_ = '';
                        
                        list($el_,$vl_,$sl_,$ol_,$oltype_,$ob_,$abs_count_,$l_nopay_,$obtypes_)     = $this->attcompute->displayLeave($employeeid,$date,'',$stime_,$etime_,$seq_);
                        
                        if($ol_ == "DIRECT"){
                            $is_wfh_ = $this->attcompute->isWfhOB($employeeid,$date);
                            if($is_wfh_->num_rows() == 1 && $obtypes==2){
                                $ob_id_ = $is_wfh_->row()->aid;
                                $hastime_ = $this->attcompute->hasWFHTimeRecord($ob_id_,$date);
                                if($hastime_->num_rows() == 0) $ol_ = $oltype_ = $ob_ = "";
                            }
                        }

                        $cs_app_ = $this->attcompute->displayChangeSchedApp($employeeid,$date);
                        

                        $absent_ = $this->attcompute->displayAbsent($stime_,$etime_,$login_,$logout_,$employeeid,$date,$earlydismissal_, $absent_start_);

                        if($oltype_ == "ABSENT")                $absent_ = $absent_;
                        else if($holiday && $isCreditedHoliday) $absent_ = "";
                        if ($vl_ >= 1  || $el_ >= 1 || $sl_ >= 1 || $ob_ >= 1 ){
                            $absent_ = "";
                        }
                        if ($vl_ > 0 || $el_ > 0 || $sl_ > 0 || $ob_ > 0){
                            $absent_ = "";
                        }
                        
                        // Late / Undertime
                        list($lateutlec_,$lateutlab_,$lateutadmin_,$tschedlec_,$tschedlab_,$tschedadmin_,$lateutrle_,$tschedrle_) = $this->attcompute->displayLateUT($stime_,$etime_,$tardy_start_,$login_,$logout_,$type_,$absent_);
                        if($el_ || $vl_  || $sl_ || ($holiday && $isCreditedHoliday)){
                            $lateutlec_ = $lateutlab_ = $lateutadmin_ = $tschedlec_ = $tschedlab_ = $tschedadmin_ = $tschedrle_ = "";
                        }
                        if($absent_ == ""){
                            if ($ol_  === "late"){
                                if ($lateutadmin_) {
                                    $remarks .= "<h5 style='color:green;'>Excused Late</h5>";
                                }
                            }elseif($lateutlab_ != "" || $lateutlec_ != "" || $lateutrle_ != "" || $lateutadmin_ != "") {
                                
                                if(date("H:i:s",strtotime($logout_)) < date("H:i:s",strtotime($etime_))){
                                    $remarks .= "<h5 style='color:red;'>Unexcused Undertime</h5>";
                                    $lateut_remarks = "undertime";
                                }else if(date("H:i:s",strtotime($login_)) > date("H:i:s",strtotime($stime_))){
                                    $remarks .= "<h5 style='color:red;'>Unexcused Late</h5>";
                                    $lateut_remarks = "late";
                                }
                            }
                        }
                    }
                }

                
                $holiday_lec = ($lec_holhours != "0:00") ? $lec_holhours : "";
                $holiday_lab = ($lab_holhours != "0:00") ? $lab_holhours : "";
                $holiday_admin = ($admin_holhours != "0:00") ? $admin_holhours : "";
                $holiday_overload = ($rle_holhours != "0:00") ? $rle_holhours : "";
                $holiday_name = ($holiday && isset($holidayInfo['type'])) ? $holidayInfo['type'] : "";

                if($cto){
                    if(in_array($cto_id, $cto_id_list)){
                        $cto_credit = "";
                    }else{
                        $cto_id_list[] = $cto_id;
                    }
                }

                if($sc_app){
                    if(in_array($sc_app_id, $sc_app_id_list)){
                        $service_credit = "";
                    }else{
                        $sc_app_id_list[] = $sc_app_id;
                    }
                }
                
                
          

                // $classification_id = $rsched->classification;
                // set as empty 
                $classification = $classification_id = "";

                if($ol && $oltype != "" && ($ol && !in_array($ol, $not_included_ol)) ){
                    $actlog_time_in = $actlog_time_out = "--";
                    $twr_lec = $twr_lab = $twr_admin = $twr_overload = "";
                    $absent_lec = $absent_lab = $absent_admin = $absent_overload = "";
                }
       
                $holiday_type = (isset($holidayInfo['holiday_code']) && $holiday) ?$holidayInfo['holiday_code'] : "";
                
                /**
                 * Validate if actual log time is within official work hours.
                 */
                // if ($this->validateLogTimeWithinWorkSchedule($off_time_out, $actlog_time_out)) {
                //     if(!$lateut_lec)
                //     {
                //         $actlog_time_in = $actlog_time_out = '--';
                //     }
                // }
                // COMMENTED BY MAX para sa ticket na to https://jira.pinnacle.edu.ph/browse/STMTCCHYP-1195
                
                // Categories for processing
                $categories = [
                    'lec' => ['absent' => $absent_lec, 'off' => $off_lec, 'lateut' => $lateut_lec],
                    'lab' => ['absent' => $absent_lab, 'off' => $off_lab, 'lateut' => $lateut_lab],
                    'admin' => ['absent' => $absent_admin, 'off' => $off_admin, 'lateut' => $lateut_admin],
                    'overload' => ['absent' => $absent_overload, 'off' => $off_overload, 'lateut' => $lateut_overload],
                ];

                if ($actlog_time_in === '--' && $actlog_time_out === '--') {
                    foreach ($categories as $key => $data) {
                        if (!$data['absent']) {
                            ${"lateut_$key"} = $data['off'];
                        }
                        if ($data['off']) {
                            ${"twr_$key"} = "";
                        }
                    }
                } else {
                    foreach ($categories as $key => $data) {
                        if ($data['off']) {
                            ${"twr_$key"} = $this->calculateTimeDifference($data['off'], $data['lateut']);
                        }
                    }
                }
             
                $this->db->query("INSERT INTO employee_attendance_teaching
                    SET employeeid = '$employeeid',
                        `date` = '$date',
                        off_time_in = '$off_time_in',
                        off_time_out = '$off_time_out',
                        off_lec = '$off_lec',
                        off_lab = '$off_lab',
                        off_admin = '$off_admin',
                        off_overload = '$off_overload',
                        actlog_time_in = '$actlog_time_in',
                        actlog_time_out = '$actlog_time_out',
                        terminal = '$terminal',
                        twr_lec = '$twr_lec',
                        twr_lab = '$twr_lab',
                        twr_admin = '$twr_admin',
                        twr_overload = '$twr_overload',
                        aims_dept = '$aims_dept',
                        campus = '$campus_name',
                        subject = '$subject',
                        teaching_overload = '$teaching_overload',
                        ot_regular = '$ot_regular',
                        ot_restday = '$ot_restday',
                        ot_holiday = '$ot_holiday',
                        lateut_lec = '$lateut_lec',
                        lateut_lab = '$lateut_lab',
                        lateut_admin = '$lateut_admin',
                        lateut_overload = '$lateut_overload',
                        absent_lec = '$absent_lec',
                        absent_lab = '$absent_lab',
                        absent_admin = '$absent_admin',
                        absent_overload = '$absent_overload',
                        service_credit = '$service_credit',
                        cto = '$cto_credit',
                        remarks = ".$this->db->escape($remarks).",
                        holiday_lec = '$holiday_lec',
                        holiday_lab = '$holiday_lab',
                        holiday_admin = '$holiday_admin',
                        holiday_overload = '$holiday_overload',
                        lateut_remarks = '$lateut_remarks',
                        holiday = '$holiday_data',
                        emergency = '$emergency',
                        vacation = '$vacation',
                        sick = '$sick',
                        other = '$other',
                        seq = '$seq',
                        rowspan = '$rwcount',
                        holiday_type = '$holiday_type',
                        rate = '$rate',
                        classification = '$classification',
                        classification_id = '$classification_id',
                        campus_in = '$campus_in',
                        campus_out = '$campus_out',
                        color = ".$this->db->escape($color)."
                        ");
                $isFirstSched = false;  
                $lec_holhours = $lab_holhours = $admin_holhours = $rle_holhours = "";
                $rendered_lec = $rendered_lab = $rendered_admin = $rendered_rle = "";
                if(!$tschedadmin && !$absent) $hasLog = true;
            } // $schedule_result loop
        }else{ // countrow && validsched
            $totalQ = 0;
            $stime = "";
            $etime = ""; 
                
            $log = $this->attcompute->displayLogTimeFlexi($employeeid,$date,$edata);

            // Leave
            list($el,$vl,$sl,$ol,$oltype,$ob,$abs_count,$l_nopay,$obtypes)     = $this->attcompute->displayLeave($employeeid,$date);
            if($ol == "DIRECT"){
                $is_wfh = $this->attcompute->isWfhOB($employeeid,$date);
                if($is_wfh->num_rows() == 1 && $obtypes==2){
                    $ob_id = $is_wfh->row()->aid;
                    $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
                    if($hastime->num_rows() == 0) $ol = $oltype = "";
                }
            }

            // Leave Pending
            $pending = $this->attcompute->displayPendingApp($employeeid,$date);
            // $pending .= $this->attcompute->displayPendingOBWfh($employeeid,$date);
 
            // Overtime
            list($otreg,$otrest,$othol) = $this->attcompute->displayOt($employeeid,$date,true);

            $coc = $this->attcompute->displayCOC($employeeid,$date,true);
            if($coc > 0){
                if($ot_remarks != "APPROVED COC APPLICATION"){
                    $ot_remarks.=($ot_remarks?", APPROVED COC APPLICATION":"APPROVED COC APPLICATION");
                }
            }

            $sc_application = $this->attcompute->displaySCAttendance($employeeid,$date, $stime, $etime);
            if($sc_application > 0){
                if($sc_app_remarks != "Approved Conversion Service Credit"){
                    $sc_app_remarks.=($sc_app_remarks?", Approved Conversion Service Credit":"Approved Conversion Service Credit");
                }
            }

            /* Overtime */
            if($otreg){
                $totr += $this->attcompute->exp_time($otreg);
            }

            if($otrest){
                $totrest += $this->attcompute->exp_time($otrest);
            }

            if($othol){
                $tothol += $this->attcompute->exp_time($othol);
            }
                
            $service_credit    = $this->attcompute->displayServiceCredit($employeeid,$date);
            $service_credit = $service_credit?$service_credit:null;
            
            if($holiday){
                $holidayInfo = $this->attcompute->holidayInfo($date);
            }

            if(count($log) > 0){
                list($el,$vl,$sl,$ol,$oltype,$ob,$abs_count,$l_nopay,$obtypes)     = $this->attcompute->displayLeave($employeeid,$date);
                if($ol == "DIRECT"){
                    $is_wfh = $this->attcompute->isWfhOB($employeeid,$date);
                    if($is_wfh->num_rows() == 1 && $obtypes==2){
                        $ob_id = $is_wfh->row()->aid;
                        $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
                        if($hastime->num_rows() == 0) $ol = $oltype = "";
                    }
                }

                $login = $logout = $q = "";
                $stime = $etime = "--";
                for($i = 0;$i < count($log);$i++){
                    $login = $log[$i][0];
                    $logout = $log[$i][1];
                    $q = $log[$i][2];
                    if($q) $totalQ++;

                    $start = strtotime($login);
                    $end = strtotime($logout);
                    $mins = ($end - $start) / 60;

                    $off_time_in = $off_time_out = "--";
                    $off_lec = $off_lab = $off_admin = $off_overload = $twr_lec = $twr_lab = $twr_admin = $twr_overload = $campus_name = $aims_dept = $subject = $lateut_lec = $lateut_lab = $lateut_admin = $lateut_overload = $absent_lec = $absent_lab = $absent_admin = $cto_credit =  "";
                    $actlog_time_in = ($login? date("h:i A",strtotime($login)) : "--");
                    $actlog_time_out = ($logout? date("h:i A",strtotime($logout)) : "--");
                    $terminal = $this->extensions->getTerminalName($campus_tap);
                    $teaching_overload = (isset($overload)?$this->time->minutesToHours($overload):"");
                    $ot_regular = ($otreg)?$this->attcompute->sec_to_hm($this->attcompute->exp_time($otreg)):"";
                    $ot_restday = ($otrest)?$this->attcompute->sec_to_hm($this->attcompute->exp_time($otrest)):"";
                    $ot_holiday = ($othol)?$this->attcompute->sec_to_hm($this->attcompute->exp_time($othol)):"";
                    $remarks = "";

                    $remarks .= (isset($cs_app)?$cs_app.'<br>':'') ;
                    $remarks .= ($pending)?"PENDING ".$pending.'<br>':($ol ? ($oltype ? ($oltype == "ABSENT" ? "ABSENT W/ FILE<br>" : $oltype.'<br>') : $this->employeemod->othLeaveDesc($ol).'<br>') : ($q ? ($q == "1" ? "" : $q.'<br>') : ""));
                    $remarks .= $ot_remarks."<br>";
                    $remarks .= $sc_app_remarks."<br>";
                    $remarks .= ($holiday && isset($holidayInfo['description']))?$holidayInfo['description'].'':"";                    
                    $holiday_lec = ($lec_holhours != "0:00") ? $lec_holhours : " ";
                    $holiday_lab = ($lab_holhours != "0:00") ? $lab_holhours : " ";
                    $holiday_admin = ($admin_holhours != "0:00") ? $admin_holhours : " ";
                    $holiday_overload = ($rle_holhours != "0:00") ? $rle_holhours : " ";
                    $holiday = ($holiday && isset($holidayInfo['type']))?$holidayInfo['type']:"";
                    if($ol && $this->employeemod->othLeaveDesc($ol, true) != ""){
                        $actlog_time_in = $actlog_time_out = "--";
                        $twr_lec = $twr_lab = $twr_admin = $twr_overload = "";
                    }
              
                  
                    $this->db->query("INSERT INTO employee_attendance_teaching
                    SET employeeid = '$employeeid',
                        `date` = '$date',
                        off_time_in = '$off_time_in',
                        off_time_out = '$off_time_out',
                        off_lec = '$off_lec',
                        off_lab = '$off_lab',
                        off_admin = '$off_admin',
                        off_overload = '$off_overload',
                        actlog_time_in = '$actlog_time_in',
                        actlog_time_out = '$actlog_time_out',
                        terminal = '$terminal',
                        twr_lec = '$twr_lec',
                        twr_lab = '$twr_lab',
                        twr_admin = '$twr_admin',
                        twr_overload = '$twr_overload',
                        aims_dept = '$aims_dept',
                        campus = '$campus_name',
                        subject = '$subject',
                        teaching_overload = '$teaching_overload',
                        ot_regular = '$ot_regular',
                        ot_restday = '$ot_restday',
                        ot_holiday = '$ot_holiday',
                        lateut_lec = '$lateut_lec',
                        lateut_lab = '$lateut_lab',
                        lateut_admin = '$lateut_admin',
                        lateut_overload = '$lateut_overload',
                        absent_lec = '$absent_lec',
                        absent_lab = '$absent_lab',
                        absent_admin = '$absent_admin',
                        service_credit = '$service_credit',
                        cto = '$cto_credit',
                        remarks = ".$this->db->escape($remarks).",
                        holiday_lec = '$holiday_lec',
                        holiday_lab = '$holiday_lab',
                        holiday_admin = '$holiday_admin',
                        holiday_overload = '$holiday_overload',
                        holiday = '$holiday'");

                    $stime = $etime = "";

                } // for($i = 0;$i < count($log);$i++){
            }else{ // if(count($log) > 0)
                if($holiday){
                    $holidayInfo = $this->attcompute->holidayInfo($date);
                }
                
                // Leave
                list($el,$vl,$sl,$ol,$oltype) = $this->attcompute->displayLeave($employeeid,$date);
                if($ol == "DIRECT"){
                    $is_wfh = $this->attcompute->isWfhOB($employeeid,$date);
                    if($is_wfh->num_rows() == 1){
                        $ob_id = $is_wfh->row()->aid;
                        $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
                        if($hastime->num_rows() == 0) $ol = $oltype = "";
                    }
                }else{
                    $el = $vl = $sl = $ol = $oltype = 0;
                }

                $sc_application = $this->attcompute->displaySCAttendance($employeeid,$date, $stime, $etime);
                if($sc_application > 0){
                    if($sc_app_remarks != "Approved Conversion Service Credit"){
                        $sc_app_remarks.=($sc_app_remarks?", Approved Conversion Service Credit":"Approved Conversion Service Credit");
                    }
                }

                $off_time_in = $off_time_out = $off_lec = $off_lab = $off_admin = $off_overload = $actlog_time_in = $actlog_time_out = $terminal = $twr_lec = $twr_lab = $twr_admin = $twr_overload = $aims_dept = $campus_name = $subject = $teaching_overload = $ot_regular = $ot_restday = $ot_holiday = $lateut_lec = $lateut_lab = $lateut_admin = $lateut_overload = $absent_lec = $absent_lab = $absent_admin = $service_credit = $cto_credit = $remarks = $holiday_lec = $holiday_lab = $holiday_admin = $holiday_overload = $holiday_name = "--";
                $terminal = $this->extensions->getTerminalName($campus_tap);
                $service_credit = ($sc_application ? $sc_application : '--');
                $remarks = ($ol ? ($oltype ? ($oltype == "ABSENT" ? "ABSENT W/ FILE" : $oltype) : $this->employeemod->othLeaveDesc($ol)) : "");
                $remarks .= ($holiday && isset($holidayInfo['description']))?$holidayInfo['description']:"";
                $remarks .= $sc_app_remarks."<br>";
                $holiday = ($holiday && isset($holidayInfo['type']))?$holidayInfo['type']:"";
                if($ol && $this->employeemod->othLeaveDesc($ol, true) != ""){
                    $actlog_time_in = $actlog_time_out = "--";
                    $twr_lec = $twr_lab = $twr_admin = $twr_overload = "";
                }

                $sc_status = $this->attcompute->getServiceCreditStatus($employeeid, $date);
                    
                if ($sc_status > 0) {
                    if ($sc_status->status == "PENDING")  $remarks .= ($remarks ? "<br> PENDING SERVICE CREDIT APPLICATION <br>" : "");
                    elseif ($sc_status->status == "APPROVED") $remarks .= ($remarks ? "<br> APPROVED SERVICE CREDIT APPLICATION <br>" : "");
                }

                $this->db->query("INSERT INTO employee_attendance_teaching
                    SET employeeid = '$employeeid',
                        `date` = '$date',
                        off_time_in = '$off_time_in',
                        off_time_out = '$off_time_out',
                        off_lec = '$off_lec',
                        off_lab = '$off_lab',
                        off_admin = '$off_admin',
                        off_overload = '$off_overload',
                        actlog_time_in = '$actlog_time_in',
                        actlog_time_out = '$actlog_time_out',
                        terminal = '$terminal',
                        twr_lec = '$twr_lec',
                        twr_lab = '$twr_lab',
                        twr_admin = '$twr_admin',
                        twr_overload = '$twr_overload',
                        aims_dept = '$aims_dept',
                        campus = '$campus_name',
                        subject = '$subject',
                        teaching_overload = '$teaching_overload',
                        ot_regular = '$ot_regular',
                        ot_restday = '$ot_restday',
                        ot_holiday = '$ot_holiday',
                        lateut_lec = '$lateut_lec',
                        lateut_lab = '$lateut_lab',
                        lateut_admin = '$lateut_admin',
                        lateut_overload = '$lateut_overload',
                        absent_lec = '$absent_lec',
                        absent_lab = '$absent_lab',
                        absent_admin = '$absent_admin',
                        service_credit = '$service_credit',
                        cto = '$cto_credit',
                        remarks = ".$this->db->escape($remarks).",
                        holiday_lec = '$holiday_lec',
                        holiday_lab = '$holiday_lab',
                        holiday_admin = '$holiday_admin',
                        holiday_overload = '$holiday_overload',
                        holiday = '$holiday'");
            } // if(count($log) > 0)
        } // else - countrow && validsched 
    }

    public function employeeAttendanceNonteaching($employeeid, $date){
        $this->load->model("ob_application");
        $this->load->model("payrollcomputation");
        $this->load->model('Attcompute'); 
        $this->removeExistingAttendance($employeeid, $date, false);
        $deptid = $this->employee->getindividualdept($employeeid);
        $fixedday = $this->attcompute->isFixedDay($employeeid);
        $classification_arr = $this->extensions->getFacultyLoadsClassfication();
        $classification_list = array();
        $edata = "NEW";
        $teachingtype = "nonteaching";
        foreach ($classification_arr as $key => $value) {
            $classification_list[$value->id] =  strtolower($value->description);
        }
        $total_perday_absent = $totr = $totrest = $tothol = $tOverload = $totlec_holhours = $totlab_holhours = $totadmin_holhours = $totrle_holhours = 0;
        $x = $totr = $totrest = $tothol = $tlec = $tutlec= $absent = $tabsent = $tabsentperday = $tel = $tvl = $tsl = $tol = $tdlec = $tdlab = $tholiday = $pending = $tempOverload = $overload = $tOverload = $lastDayOfWeek = $cs_app = $date_tmp = $tcto = $tsc_app = 0; 
        $tlec = $workdays = $tworkdays = 0 ;
        $tempabsent = 0;
        $t_service_credit = $service_credit = 0;
        $seq_new = 0;
        $cto_id_list = $sc_app_id_list = array();
        $perday_absent = $total_perday_absent = 0;
        $login_new = $logout_new = $q_new = $haslog_forremarks_new = "";
        $not_included_ol = array("ABSENT", "EL", "VL", "SL", "CORRECTION", "DA");
        $ishalfday = "";
        $hasLog = $isSuspension = false;
        $used_time = array();
        $isCreditedHoliday = false;
        $firstDate = true;
        $ob_data = array();
        $ot_remarks = $sc_app_remarks = $wfh_app_remarks = $pending_ot_remarks =  "";
        $sum_tworkhours = 0;

        $ot_remarks = "";
        $holidayInfo = array();
        // Holiday
        $isSuspension = false;
        $isRegularHoliday = false;
        $otherHoliday = false;
        $last_login = $last_logout = "";
        $last_stime = $last_etime = "";
        $campus_tap = $this->attendance->getTapCampus($employeeid, $date);
        
        // $holiday = $this->attcompute->isHolidayNew($employeeid, $date,$deptid, "", "", $teachingtype); 
        $holiday = $this->attcompute->isHolidayDetailed($employeeid, $date,$deptid, "", "", $teachingtype);
      
        $holiday_type = "";
        $rate = 0;
        if(!empty($holiday)){
            $holidayInfo = $this->attcompute->holidayInfo($date);
            if(isset($holidayInfo['holiday_type'])){
                $holiday_type = $holidayInfo['holiday_type'];
                if($holidayInfo['holiday_type']==1) $isRegularHoliday = true;
                if($holidayInfo['holiday_type']==3) $isSuspension = true;
                if($holidayInfo['type']=='OTHERS') $otherHoliday = true;
                $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "nonteaching");
            }
        }
       
        $is_holiday_valid = $this->attendance->getTotalHoliday($date, $date, $employeeid);
        // if(!$is_holiday_valid){
        //     $holidayInfo = array();
        //     $holiday = "";
        // }
        
        // VALIDATE IF HAS LOG BEFORE THE DAY OF HOLIDAY
        $before_holiday_log = $this->checkEmployeeLog($employeeid, $date);
        if($holiday){
            // var_dump($otherHoliday); die;
            if($otherHoliday === false){
                if($before_holiday_log === 0){
                    $holiday = "";
                    $holiday_type = "";
                    $rate = "";
                    $isRegularHoliday = false;
                    $isSuspension = false;
                    $otherHoliday = false;
                    // $holidayInfo = array();
                }
            }
        }

        $dispLogDate = date("d-M (l)",strtotime($date));
        $sched = $this->attcompute->displaySched($employeeid,$date);

        $countrow = $sched->num_rows();

        $isValidSchedule = true;

        if($countrow > 0){
            if($sched->row(0)->starttime == '00:00:00' && $sched->row(0)->endtime == '00:00:00') $isValidSchedule = false;
        }
        
        if($x%2 == 0)   $color = " style='background-color: white;'";
        else            $color = " style='background-color: #fafafa;'";
        $x++;
        
        if($firstDate && $holiday){
            $hasLog = $this->attendance->checkPreviousSchedAttendanceNonTeaching($date,$employeeid);
            $firstDate = false;
        }

        $tworkhours = $this->attendance->totalWorkhoursPerday($employeeid, $date);
        if($isRegularHoliday) $tworkhours = "0:00";

        if($countrow > 0 && $isValidSchedule){
            $haswholedayleave = false;
            $hasleavecount = 0;

            $hasLogprev = $hasLog;
            $hasLog = false;
            
            if($hasLogprev || $isSuspension)    $isCreditedHoliday = "true";
            else                                $isCreditedHoliday = "false";
            $tempsched = "";
            $seq = 0;
            $service_credit = null;
            $service_credit_used = 0;

            $isFirstSched = true;
            $q_sched = $sched;
            $perday_absent = $this->attendance->getTotalAbsentPerday($sched->result(), $employeeid, $date);
            $total_perday_absent += $perday_absent;
            $presentLastLog = false;
            $sched_min = 0;
            $used_time_no_used = array();
            $prior_sched_start = $prior_sched_end = $prior_absent_start = "";
            $schedule_result_data = $sched->result();
            foreach($schedule_result_data as $sched_row){
                $sched_min += round(abs(strtotime($sched_row->endtime) - strtotime($sched_row->starttime)) / 60,2);
            }
            $sched_min = $this->time->minutesToHours($sched_min);
            $sched_sec = round(abs(strtotime($sched_row->endtime) - strtotime($sched_row->starttime)),2);

            $previous_login = $previous_logout = "";

            foreach($schedule_result_data as $sched_key => $rsched){
                $workdays = 0;
                $ob_type = true;
                if(1){
                    if($tempsched == $dispLogDate){  $dispLogDate = "";}
                    $stime  = $rsched->starttime;
                    $etime  = $rsched->endtime; 
                    $tstart = $rsched->tardy_start; 
                    $absent_start = $rsched->absent_start;
                    $earlyd = $rsched->early_dismissal;
                    $sched_count = $sched_key + 1;
                    $prior_sched_start = isset($schedule_result_data[$sched_count]->starttime) ? $schedule_result_data[$sched_count]->starttime : "";
                    $prior_sched_end = isset($schedule_result_data[$sched_count]->endtime) ? $schedule_result_data[$sched_count]->endtime : "";
                    $prior_absent_start = isset($schedule_result_data[$sched_count]->absent_start) ? $schedule_result_data[$sched_count]->absent_start : "";
                    $prior_absent_ed = isset($schedule_result_data[$sched_count]->early_dismissal) ? $schedule_result_data[$sched_count]->early_dismissal : "";
                    

                    $seq += 1;
                    // logtime
                    $log_time_data = $this->attcompute->displayLogTime($employeeid, $date, $stime, $etime, $edata, $seq, $absent_start, $earlyd, $used_time_no_used);

                    $upcomingLogEntry = $this->attcompute->displayLogTime($employeeid, $date, $prior_sched_start, $prior_sched_end, $edata, $seq+1, $absent_start, $prior_absent_ed, $used_time_no_used);

                    if (count($log_time_data) >= 6) {
                        list($login, $logout, $q, $haslog_forremarks, $used_time_no_used, $ob_id) = $log_time_data;
                    } else {
                        list($login, $logout, $q, $haslog_forremarks, $used_time_no_used, $ob_id) = array_pad($log_time_data, 6, null);
                    }
                    if($q == "VACATION" || $q == "SICK" || $q == "EMERGENCY"){
                        if(date('H:i:s', strtotime($login)) != $stime && date('H:i:s', strtotime($logout)) != $etime) $login = $logout = "";
                    }

                    list($otreg,$otrest,$othol) = $this->attcompute->displayOt($employeeid,$date,true, $holiday);
                    if($otreg || $otrest || $othol){
                        $ot_remarks = "OVERTIME APPLICATION";
                    }
                    
                    $coc = $this->attcompute->displayCOC($employeeid,$date,true);
                    if($coc > 0){
                        if($ot_remarks != "APPROVED COC APPLICATION"){
                            $ot_remarks.=($ot_remarks?", APPROVED COC APPLICATION":"APPROVED COC APPLICATION");
                        }
                    }
                    $sc_application = $this->attcompute->displaySCAttendance($employeeid,$date, $stime, $etime);
                    if($sc_application > 0){
                        if($sc_app_remarks != "Approved Conversion Service Credit"){
                            $sc_app_remarks.=($sc_app_remarks?", Approved Conversion Service Credit":"Approved Conversion Service Credit");
                        }
                    }

                    $leave_data = $this->attcompute->displayLeave($employeeid, $date, '', $stime, $etime, $seq);

                    if (count($leave_data) >= 11) {
                        list($el, $vl, $sl, $ol, $oltype, $ob, $abs_count, $l_nopay, $obtypes, $ob_id, $l_nopay_remarks) = $leave_data;
                    } else {
                        list($el, $vl, $sl, $ol, $oltype, $ob, $abs_count, $l_nopay, $obtypes, $ob_id, $l_nopay_remarks) = array_pad($leave_data, 11, null);
                    }
                    list($cto, $ctohalf, $cto_id, $cto_sched_affected) = $this->attcompute->displayCTOUsageAttendance($employeeid,$date, $stime, $etime);
                    list($sc_app, $sc_app_half, $sc_app_id) = $this->attcompute->displayServiceCredit($employeeid,$date, $stime, $etime);

                    if($ol == "DIRECT"){
                        $is_wfh = $this->attcompute->isWfhOB($employeeid,$date);
                        if($is_wfh->num_rows() == 1 && $obtypes==2){
                            $ob_id = $is_wfh->row()->aid;
                            $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
                            if($hastime->num_rows() == 0) $ol = $oltype = $ob = 0;
                            if($wfh_app_remarks != "Approved Work From Home Application"){
                                $wfh_app_remarks.=($wfh_app_remarks?", Approved Work From Home Application":"Approved Work From Home Application");
                            }
                        }
                    }else if($ol == "DA" && $obtypes==3 && $ob_id && $ob_id == $is_ob){
                        $ob_details = $this->ob_application->getLeaveDetails($ob_id);
                        if($ob_details['timefrom'] && $ob_details['timeto']){
                            $login = $ob_details['timefrom'];
                            $logout = $ob_details['timeto'];
                        }
                    }else if($ol == "DA" && $obtypes==2 && $ob_id && $ob_id == $is_ob){
                        $ob_details = $this->ob_application->getLeaveDetails($ob_id);
                        if($ob_details['timefrom'] && $ob_details['timeto']){
                            $login = $ob_details['timefrom'];
                            $logout = $ob_details['timeto'];
                        }
                    }else if($ol == "DA" && $obtypes==1 && $ob_id){
                        $ob_details = $this->ob_application->getLeaveDetails($ob_id);
                        if($ob_details['sched_affected']){
                            list($login, $logout) = explode('|', $ob_details['sched_affected']);
                        }
                    }

                    if($cto && $cto_id){
                        if($ctohalf){
                            list($login, $logout) = explode("|", $cto_sched_affected);
                            $startTimestamp = strtotime($login);
                            $endTimestamp = strtotime($logout);

                            $minutesDifference = ($endTimestamp - $startTimestamp) / 60;
                            $tworkhours = $this->time->minutesToHours($minutesDifference);
                        }else{
                            $login = $stime;
                            $logout = $etime;
                            $tworkhours = $sched_min;
                        }
                    }

                    $ob_data = $this->attcompute->displayLateUTAbs($employeeid, $date);
                    //Service Credit 
                    $service_credit = $this->attcompute->displayServiceCredit($employeeid,$stime,$etime,$date);
                    // Change Schedule
                    $cs_app = $this->attcompute->displayChangeSchedApp($employeeid,$date);
                    // $cs_app = false;
                    // Leave Pending
                    $pending = $this->attcompute->displayPendingApp($employeeid,$date, "", $ol);
                    // $pending .= $this->attcompute->displayPendingOBWfh($employeeid,$date);

                    // ob application
                    $ob_app_status = $this->attcompute->displayApprWholeDayOBApp($employeeid, $date);
                    if ($ob_app_status) { 
                        $login = $stime;
                        $logout = $etime;
                    }

                                    
                $vl_approved = $this->attcompute->approvedLeaveApplication($employeeid, $date);
                if($vl_approved){
                    if ($vl_approved[0]->nodays == 0.50) {
                        list($start_time, $end_time) = explode('|', $vl_approved[0]->sched_affected);
                        if($start_time == $stime ){
                            $login = $stime;
                            $logout = $etime;
                        }
                    }else{
                        $login = $stime;
                        $logout = $etime;
                    }
                }


                // Use Service Credit
                $use_sc_remarks = "";
                $use_sc_status = $this->attcompute->getUseServiceCreditStatus($employeeid, $date);
                if ($use_sc_status) {
                    if ($use_sc_status->status == "PENDING") {
                        if($use_sc_status->credit_used == "1"){
                            $use_sc_remarks .= "<br> PENDING USE SERVICE CREDIT APPLICATION <br>";
                        }else{
                            $sched_affected = $use_sc_status->sched_affected;        
                            $sched_time = explode("|", $sched_affected);        
                            if($sched_time[0] == $stime){
                                $use_sc_remarks .= "<br> PENDING USE SERVICE CREDIT APPLICATION <br>";
                            }
                        }
                    } elseif ($use_sc_status->status == "APPROVED") {
                        if($use_sc_status->credit_used == "1"){
                            $login = $stime;
                            $logout = $etime;
                            $tworkhours = $sched_min;
                            $use_sc_remarks .= "<br> APPROVED USE SERVICE CREDIT APPLICATION <br>";
                        }else{
                            $sched_affected = $use_sc_status->sched_affected;        
                            $sched_time = explode("|", $sched_affected);        
                            if($sched_time[0] == $stime){
                                $use_sc_remarks .= "<br> APPROVED USE SERVICE CREDIT APPLICATION <br>";
                            }

                        }
                    }
                }

                    
                     // Absent
                    $absent = $this->attcompute->displayAbsent($stime,$etime,$login,$logout,$employeeid,$date,$earlyd);

                    if($oltype == "ABSENT") $absent = $absent;
                    else if($holiday && $isCreditedHoliday) $absent = "";


                    if ($vl >= 1 || $el >= 1 || $sl >= 1 || $ob >= 1 || $ol >= 1 || $service_credit >= 1 || ($cto && $ctohalf == 0) || ($sc_app && $sc_app_half == 0)){
                        $absent = "";
                        $haswholedayleave = true;
                    }
                    if ($vl > 0 || $el > 0 || $sl > 0 || $ob > 0 || $ol > 0 || $service_credit > 0 || $cto || $sc_app){
                        $absent = "";
                        $hasleavecount++;
                    }
                    if ($vl == 0.5 || $el == 0.5 || $sl == 0.5 || $ob == 0.5 || $ol == 0.5 || $service_credit == 0.5 || ($cto && $ctohalf == 1) || ($sc_app && $sc_app_half == 1)) {
                        $ishalfday = true;
                    }
                    if($abs_count >= 1) $haswholedayleave = true;

                    $lateutlec = $this->attcompute->displayLateUTNT($stime,$etime,$login,$logout,$absent,$teachingtype,$tstart);
                    $utlec  = $this->attcompute->computeUndertimeNT($stime,$etime,$login,$logout,$absent,$teachingtype,$tstart);
                    if($el || $vl || $sl || $service_credit || ($holiday && $isCreditedHoliday)) $lateutlec = $utlec = "";
                    
                    if($absent && $presentLastLog){
                        $utlec = $absent;
                        $absent = '';
                    }

                    if($this->attcompute->exp_time($utlec) == $sched_sec){
                        $absent = $utlec;
                        $utlec = $login = $logout = $haslog_forremarks = "";
                    }
                    $log_remarks = '';
                    
                    if($otherHoliday){
                        $login = $stime;
                        $logout = $etime;
                    }


                    if($holiday)
                    {
                        if($this->attcompute->isHolidayWithpay($date) == "YES")
                        {
                            if($tempabsent)
                            {
                                $absent = $absent;
                            }
                        }
                        else
                        {
                            if(!$login && !$logout)
                            {
                                $absent = $absent;
                            }
                        }
                    }
                    else
                    {
                        $tempabsent = $absent;
                    }

                    // GET CURRENT LOG TIME BY SCHEDULE (LOGIN)
                    if (!$login) {
                        $login = $this->timesheet->currentLogtimeAMSchedule($employeeid, $date, $stime, $absent_start, $used_time_no_used);

                        // NEW: Remove logout if it’s within a short interval (e.g., 5 minute) of login
                        $min_diff = 5; // minutes
                        if ($login && $last_login) {
                            $login_time = strtotime($login);
                            $last_login_time = strtotime($last_login);
                            $diff = abs($last_login_time - $login_time) / 60; // difference in minutes
 
                            if ($diff <= $min_diff) {
                                $login = "";
                            }
                        }

                        // If first schedule and still no login, mark as absent
                        if ($sched_key == 0 && $login == "") {
                            $schedstart = strtotime($stime);
                            $schedend   = strtotime($etime);
                            $totalMinutes = round(abs($schedend - $schedstart) / 60, 2);
                            $absent = date('H:i', mktime(0, $totalMinutes));
                        }
                    }

                    // GET CURRENT LOG TIME BY SCHEDULE (LOGOUT)
                    if (!$logout) {
                        $logout = $this->timesheet->currentLogtimePMSchedule(
                            $employeeid, $date, $etime, $earlyd, $used_time_no_used, $prior_sched_start, $prior_absent_start
                        );

                        if($last_logout || $last_login || $previous_logout){
                            if(($last_logout == $logout || $last_login == $logout) || (!$previous_logout && !$login)){
                                $logout = "";
                            }
                        }

                        // Use previous logout if no login found
                        if (!$login) {
                            $login = $previous_logout;
                        }

                        // If logout exists, clear absence
                        if ($logout) {
                            // $absent = ""; REMOVE AS PER MAM MARICRIS
                        }

                        // Prevent identical login/logout if they are both true
                        if ($login && $logout && $login === $logout) {
                            $logout = "";
                        }

                        // NEW: Remove logout if it’s within a short interval (e.g., 1 minute) of login
                        $min_diff = 5; // minutes
                        if ($login && $logout) {
                            $login_time = strtotime($login);
                            $logout_time = strtotime($logout);
                            $diff = abs($logout_time - $login_time) / 60; // difference in minutes

                            if ($diff <= $min_diff) {
                                $logout = "";
                            }
                        }
                    }

                    // REMOVE LOG IF IT USED ON FIRST SCHEDULE BUT  NOT USABLE IN NEXT SCHEDULE
                    if($seq > 1){
                        if (strtotime($stime) > strtotime($logout) && $last_logout == $login) {
                            $login = "";
                        }
                    }

                    $previous_login = $login;
                    $previous_logout = $logout;
                    
                    if($absent && $log_remarks != "is-absent"){
                        if(!$login && !$logout && !$haslog_forremarks){
                            if($last_login && $last_logout){
                                $log_remarks = '<span style="color:red">UNDERTIME</span>';
                            }else{
                                $log_remarks = '<span style="color:red">NO TIME IN AND OUT</span>';
                            }
                        }elseif(!$login){
                            $log_remarks = 'NO TIME IN';
                        }elseif(!$logout){
                            $log_remarks = 'NO TIME OUT';
                        }
                    }  

                    $hasOL = $ol ? ($ol != 'CORRECTION' ? true : false) : false; 
                    if(!$fixedday){
                        if($absent=='' || $hasOL) $workdays=1;
                    }

                    if($isFirstSched){
                        $is_holiday_halfday = $this->attcompute->isHolidayNew($employeeid, $date,$deptid, "", "on");
                        if($is_holiday_halfday){
                            list($fromtime, $totime) = $this->extensions->getHolidayHalfdayTime($date, "first");
                        } 
                        if($is_holiday_halfday && ($fromtime && $totime) ){
                            $holidayInfo = $this->attcompute->holidayInfo($date);
                            $is_half_holiday = true;
                            $half_holiday = $this->attcompute->holidayHalfdayComputation(date("H:i", strtotime($login)), date("H:i", strtotime($logout)), date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), $isFirstSched);
                            if($half_holiday > 0){
                                $lateutlec = $this->attcompute->sec_to_hm(abs($half_holiday)); 
                                $absent = $this->attcompute->sec_to_hm(abs($absent)); 
                            }else{
                                $lateutlec = "";
                                $absent = "";
                                $log_remarks = "";
                            }
                        }
                    }else{
                        $is_holiday_halfday = $this->attcompute->isHolidayNew($employeeid, $date,$deptid, "", "on");
                        if($is_holiday_halfday){
                            list($fromtime, $totime) = $this->extensions->getHolidayHalfdayTime($date, "second");
                        } 
                        if($is_holiday_halfday && ($fromtime && $totime) ){
                            $holidayInfo = $this->attcompute->holidayInfo($date);
                            $is_half_holiday = true;
                            if($utlec){
                                $half_holiday = $this->attcompute->holidayHalfdayComputation($login, $logout, date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), $isFirstSched);
                                if($half_holiday > 0){
                                    $utlec = $this->attcompute->sec_to_hm(abs($half_holiday)); 

                                }else{
                                    $utlec = "";
                                }
                                
                            }
                        }
                    }

                    if($el || $vl || $sl  || $service_credit || ($holiday && $isCreditedHoliday) || $cto || $sc_app) $lateutlec = $utlec = $absent = "";
                    $absent = $this->attcompute->exp_time($absent);
                    // if($absent >= 14400 && $countrow==2) $absent = 14400;
                    // elseif($absent >= 14400 && $countrow==1) $absent = 28800;
                    $absent   = ($absent ? $this->attcompute->sec_to_hm($absent) : "");

                    if($lateutlec){
                        if(in_array("late", $ob_data)) $log_remarks = "EXCUSED LATE";
                        else{
                            if($utlec)
                            {
                                $log_remarks = "<span style='color:red'>LATE</span><br>";
                                $log_remarks .= "<span style='color:red'>UNDERTIME</span>";
                            }else{
                                $log_remarks = "<span style='color:red'>LATE</span>";
                            }
                            $ob_type = false;
                        }
                    }else if($utlec){
                        if(in_array("undertime", $ob_data)) $log_remarks = "EXCUSED UNDERTIME";
                        else{
                            $log_remarks = "<span style='color:red'>UNDERTIME</span>";
                            $ob_type = false;
                        }
                    }else if($absent){
                        if(in_array("absent", $ob_data)) $log_remarks = "EXCUSED ABSENT";
                        else{
                            $ob_type = false; 
                            if(!$login && !$logout){
                                if($last_login && $last_logout){
                                    $log_remarks = '<span style="color:red">UNDERTIME</span>';
                                }else{
                                    $log_remarks = '<span style="color:red">NO TIME IN AND OUT</span>';
                                }
                            }
                        }
                    }
                    
                    // For bypassing remarks, displaying missing logs
                    if ($login xor $logout) {
                        if (!$login) {
                            $log_remarks = '<span style="color:red">NO TIME IN</span>';
                        } elseif (!$logout) {
                            $log_remarks = '<span style="color:red">NO TIME OUT</span>';
                        }
                    }
                    
                    if(!$login){
                        $login = $this->timesheet->getNooutData($employeeid, $date);
                    }

                    if($login && $logout && ($login != $logout)){
                        $presentLastLog = true;
                    }else{
                        $presentLastLog = false;
                    }
                    // comment out
                    // list($vl_lateut) = $this->payrollcomputation->removeLateUTByVL($employeeid, $date, $this->time->hoursToMinutes($lateutlec));
                    // list($vl_utlec) = $this->payrollcomputation->removeLateUTByVL($employeeid, $date, $this->time->hoursToMinutes($utlec));
                    // $vl_lateut = ($lateutlec) ? $this->time->minutesToHours($vl_lateut) : "";
                    // $vl_utlec = ($utlec) ? $this->time->minutesToHours($vl_utlec) : "";
                    // COMMENT TO WAG ICOUNT ANG OB
                    // if other leave is OB
                    // if($ol == "DA"){
                    //     $ol = $ob;
                    // }

                    $off_time_in = ($stime != "00:00:00" ? date('h:i A',strtotime($stime)) : "--");
                    $off_time_out = ($stime != "00:00:00" ? date('h:i A',strtotime($etime)) : "--");
                    $off_time_total = $this->attcompute->sec_to_hm($perday_absent);

                    $actlog_time_in = ($login ? date("h:i A",strtotime($login)) : "--");
                    $actlog_time_out = ($logout  ? date("h:i A",strtotime($logout)) : "--");

                    $terminal = $this->extensions->getTerminalName($campus_tap);

                    $twr = $tworkhours;

                    $ot_regular = ($otreg)?$this->attcompute->sec_to_hm($this->attcompute->exp_time($otreg)):"";
                    $ot_restday = ($otrest)?$this->attcompute->sec_to_hm($this->attcompute->exp_time($otrest)):"";
                    $ot_holiday = ($othol)?$this->attcompute->sec_to_hm($this->attcompute->exp_time($othol)):"";

                    $late = $lateutlec;
                    $undertime = $utlec;

                    if(!empty($holidayInfo) && $holiday){
                        $actlog_time_in = $off_time_in;
                        $actlog_time_out = $off_time_out;
                        $log_remarks = str_replace('<span style="color:red">NO TIME IN AND OUT</span>', '', $log_remarks);
                    }

                    // COMMENT PO MUNA PARANG ITO CAUSE KAYA NAG DODOBLE PO BAWAS NG LATE SA RENDERED HOURS
                    // if($late){
                    //     $twr = $this->attcompute->exp_time($twr) - $this->attcompute->exp_time($late);
                    //     $twr = $this->attcompute->sec_to_hm($twr);
                    // }

                    // if($late){
                    //     $twr = $this->attcompute->exp_time($twr) - $this->attcompute->exp_time($undertime);
                    //     $twr = $this->attcompute->sec_to_hm($twr);
                    // }

                    // $vl_deduc_late = $vl_lateut;
                    // $vl_deduc_undertime = $vl_utlec;
                    $vl_deduc_late = $vl_deduc_undertime = "";

                    $absent_data = (!$fixedday && !$hasOL) ? $absent : ($absent?$absent:'');

                    $service_credit = ($sc_app ? $sc_app : ($sc_application ? $sc_application : ''));

                    $cto_credit = ($cto ? $cto : "");

                    if ($oltype == 'OFFICIAL BUSINESS') {
                        $obType = $this->extensions->getTypeOfOB($employeeid, $date);
                        $oltype = $obType == 'SEMINAR' ? $obType : $oltype;
                    }
                  
                    $other = ($ol && !in_array($ol, $not_included_ol) && $oltype)  ? $ol : ""; 
                    $rwcount = 1;
                    if(!$dispLogDate) $rwcount = 1;
                    if($haswholedayleave || $pending || $holiday) $rwcount = $countrow;


                    // ob application
                    $ob_app_status = $this->attcompute->displayApprWholeDayOBApp($employeeid, $date);
                    if ($ob_app_status) { 
                        if ($ob_app_status->status == "APPROVED") {
                            $actlog_time_in = $off_time_in;
                            $actlog_time_out = $off_time_out;
                        } 
                    }

                    $ot_app =  $this->attcompute->displayOTApp($employeeid, $date);
                    $hasOThours=  $this->attcompute->hasOvertimeHours($employeeid, $date);
                    if ($ot_app) {
                        if ($ot_app->status == "APPROVED") {
                            // $ot_regular = $ot_app->grand_total;
                        } else {
                            if($hasOThours)
                            {
                                $pending_ot_remarks = "PENDING OVERTIME APPLICATION";
                            }
                        }
                    }

                    // leave apllication
                    $leave_count = isset($leave_data[3]) ? $leave_data[3] : 0;
                    $leave_app_status = $this->attcompute->otherApprovedLeave($employeeid, $date);
                    if ($leave_app_status) { 
                        if ($leave_app_status->subtype == "BL") {
                            $rwcount = 2;
                            $other = $leave_count; 
                            $log_remarks = 'BIRTHDAY LEAVE<br>';
                        } else if ($leave_app_status->subtype == "EL") {
                            $rwcount = 2;
                            $el = $leave_count; 
                            $log_remarks = 'EMERGENCY LEAVE<br>';
                        } else if ($leave_app_status->subtype == "VL") {
                            $rwcount = 2;
                            $vl = $leave_count; 
                            $other = "";
                        }else if($leave_app_status->subtype == "SL"){
                            $rwcount = 2;
                            $sl = $leave_count; 
                            $other = "";
                            // $log_remarks = 'SICK LEAVE<br>';
                        }else if($leave_app_status->subtype == "OS"){
                            $rwcount = 2;
                            $other = $leave_count;
                            $log_remarks = 'OFF SET<br>';
                        }
                    }
                    $emergency = ($el) ? $el : "";
                    $vacation = ($vl) ? $vl : "";
                    $sick = ($sl) ? $sl : "";
          
                    // birthday and emergency
                   
                    $remarks = ($log_remarks?$log_remarks."<br>":'');
                    $remarks .= ($ot_remarks) ? $ot_remarks."<br>" : '' ;
                    $remarks .= ($sc_app_remarks) ? $sc_app_remarks."<br>" : '';
                    $remarks .= ($wfh_app_remarks) ? $wfh_app_remarks."<br>" : '';
                    $remarks .= $cs_app ? ($cs_app?$cs_app.'<br>':'') : '';
                    $remarks .= ($pending)?"PENDING ".$pending.'<br>':'';
                    $remarks .= ($use_sc_remarks)? $use_sc_remarks.'<br>':'';
                    $remarks .=  ($pending_ot_remarks?$pending_ot_remarks."<br>":"");
                    $remarks .= ($ol ? ($oltype ? ($oltype == "ABSENT" ? "ABSENT W/ FILE<br>" : ($oltype == "CORRECTED TIME IN/OUT" ? ($actlog_time_in != "--" && $actlog_time_out != '--' ? $oltype : '') : $oltype ).'<br>') : ($l_nopay_remarks ? $l_nopay_remarks : $this->employeemod->othLeaveDesc($ol))) .'<br>' : '') ;
                    $remarks .= $service_credit?'SERVICE CREDIT<br>':'';
                    $remarks .= $cto?'APPROVED USE CTO APPLICATION<br>':'';
                    $remarks .= $sc_app?'USE SERVICE CREDIT<br>':'';
                    $remarks .= $dispLogDate ? (isset($holidayInfo['description']) ? $holidayInfo['description'] : '') : '';
                    
                    $holiday_data = (!empty($holidayInfo['holiday_type']) && !empty($holiday)) ? $holidayInfo['holiday_type'] : '';
                    $holiday_type = (!empty($holidayInfo['type']) && !empty($holiday)) ? $holidayInfo['type'] : '';
                    

                   
               
                    // deduct total workhours rendered if has late
                    
                    if($late || $undertime){
                        $official_time_span = $this->time->getSecondsBetween($off_time_in,$off_time_out);
                        $twr = $official_time_span;
                        $lateut = $this->attcompute->exp_time($late) + $this->attcompute->exp_time($undertime);
                        $twr_sec = abs($twr - $lateut);
                        $twr = $this->attcompute->sec_to_hm($twr_sec);
                    }else{
                        $twr = $off_time_total;
                    }

                    // // echo "<pre>";print_r($twr);die;

                    if(!$late && !$undertime && !$absent_data)
                    {
                        $twr = $this->attcompute->sec_to_hm($this->time->getSecondsBetweenTimes($off_time_in, $off_time_out));
                    }
                    if($late &&  !$undertime && !$absent_data)
                    {
                        $lateut = $this->attcompute->exp_time($late);
                        $twr = $this->attcompute->sec_to_hm($this->time->getSecondsBetweenTimes($off_time_in, $off_time_out) - $lateut);
                    }


                    /**
                     * Etong condition na ito para ma check kung may logs sya sa pm para maging late ang logs nya sa AM
                     * at hindi sya ma tag as absent
                     * STTHERESE
                     * lndrsnts
                     */
                
                    if ((!$actlog_time_in || $actlog_time_in === '--') && (!$actlog_time_out || $actlog_time_out === '--')) {
                        // Retrieve upcoming log entry data with parameters for upcoming data.
                        $logEntryFieldsCount = 6;
                    
                        // Use array unpacking or pad with `null` if less than expected fields.
                        list($upcomingLogin, $upcomingLogout, $upcomingQueue, $hasLogForRemarks, $unusedTime, $observationId) = 
                            count($upcomingLogEntry) >= $logEntryFieldsCount
                                ? $upcomingLogEntry
                                : array_pad($upcomingLogEntry, $logEntryFieldsCount, null);
                    
                        // Handle case where an upcoming login exists.
                        if ($upcomingLogin) {
                            $undertime = $absent_data;
                            $absent_data = '';
                        }else if($last_login && $last_logout){
                            $late = $absent_data;
                            $absent_data = '';
                        }
                    }


                    if ($absent_data) {
                        // Validate if the AM schedule is also marked as absent
                        $isAMSchedAbsent = $this->attendance->validateAmScheduleAbsence($employeeid, $date);
                
                        if ($isAMSchedAbsent == 'absent') {
                            $twr = "0:00";
                        } else if ($isAMSchedAbsent == 'not_absent') {
                            if($actlog_time_in == '--')
                            {
                                $remarks = str_replace('LATE', 'NO TIME IN', $remarks);
                            }
                            $late = "";
                            $twr = "0:00";
                            
                        } else {
                            $twr = "0:00";
                        }
                    } else {
                        
                        // Handle cases when absent_data is not present
                        $isAMSchedAbsent = $this->attendance->validateAmScheduleAbsence($employeeid, $date);
                
                        if ($isAMSchedAbsent == 'not_absent') {
                            //$undertime = $late;
                            // $late = "2:00";
                            

                            $lateut = $this->attcompute->exp_time($late) + $this->attcompute->exp_time($undertime);
                            $twr = $this->attcompute->sec_to_hm(abs($this->attcompute->exp_time($stime) - $this->attcompute->exp_time($etime))-$lateut);
                        }
                    }

                    if($actlog_time_in == '--' || $actlog_time_out == '--')
                    {
                        $twr = "0:00";
                    }

                    
                    // $ot_regular = '03:00';
                    // $ot_restday = '';
                    // $ot_holiday = '';
                    // $late = '3:30';

                    $lateDeduction ='0:00';
                    if($late)
                    {
                        $lateDeduction = $this->calculateLateDeduction($late,[$ot_regular,$ot_restday,$ot_holiday]);
                    }


                    //Para makuha yung late naunang ma add sa db at maisama sa total overtime
                    $logs_late = $this->attendance->loadLates($employeeid, $date, "employee_attendance_nonteaching");
                    $logsLateMinute = $this->attcompute->convertToMinutes($logs_late);
                    $lateMinute = $this->attcompute->convertToMinutes($late);

                    $totalLate = abs($logsLateMinute + $lateMinute);

                    $totalLateFormatted = $this->attcompute->convertToTimeFormat($totalLate);

                    //Total overtime with && without 25% 
                     $totalOTWith25 = $this->attcompute->calculateOTWith25($totalLateFormatted,$ot_regular,$ot_restday,$ot_holiday);
                     $totalOTWithout25 = "";
                    //  $totalOTWithout25 = $this->attcompute->calculateOTWithout25($totalLateFormatted,$ot_regular,$ot_restday,$ot_holiday);
                   
                    $this->db->query("INSERT INTO employee_attendance_nonteaching
                    SET employeeid = '$employeeid',
                        `date` = '$date',
                        off_time_in = '$off_time_in',
                        off_time_out = '$off_time_out',
                        off_time_total = '$off_time_total',
                        actlog_time_in = '$actlog_time_in',
                        actlog_time_out = '$actlog_time_out',
                        terminal = '$terminal',
                        twr = '$twr',
                        ot_regular = '$ot_regular',
                        ot_restday = '$ot_restday',
                        ot_holiday = '$ot_holiday',
                        late = '$late',
                        undertime = '$undertime',
                        vl_deduc_late = '$vl_deduc_late',
                        vl_deduc_undertime = '$vl_deduc_undertime',
                        absent = '$absent_data',
                        service_credit = '$service_credit',
                        cto = '$cto_credit',
                        remarks = ".$this->db->escape($remarks).",
                        vl = '$vacation',
                        sl = '$sick',
                        other = '$other',
                        el = '$emergency',
                        holiday = '$holiday_data',
                        seq = '$seq',
                        rowspan = '$countrow',
                        color = ".$this->db->escape($color).",
                        rowcount = '$rwcount',
                        holiday_type = '$holiday_type',
                        rate = '$rate',
                        late_deduc = '$lateDeduction',
                        total_ot_with_25 = '$totalOTWith25',
                        total_ot_without_25 = '$totalOTWithout25'
                        ");

                    //Update all same total with and without 25% 
                    $this->attendance->updateTotalOT($totalOTWith25,$employeeid,$date,'total_ot_with_25');
                    $this->attendance->updateTotalOT($totalOTWithout25,$employeeid,$date,'total_ot_without_25');


                    // VALIDATE WORKHOURS RENDERED IF HAS LATE OR UT ON 2ND SCHEDULE
                    // $twr_q = $this->db->query("SELECT twr, id FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND date = '$date' AND off_time_in = '$last_stime' AND off_time_out = '$last_etime'");
                    // if($twr_q->num_rows() > 0){
                    //     if($twr_q->row()->twr != $twr){
                    //         $att_id = $twr_q->row()->id;
                    //         $this->db->query("UPDATE employee_attendance_nonteaching SET twr = '$twr' WHERE id = '$att_id'");
                    //         // echo $this->db->last_query(); die;
                    //     }
                    // }


                } // if($rsched->flexible != "YES")
                $last_login = $login;
                $last_logout = $logout;
                $last_stime = $off_time_in;
                $last_etime = $off_time_out;
            } // foreach($sched->result() as $rsched)
        }else{ // if($countrow > 0 && $isValidSchedule)
            $totalQ = 0;
            $stime = "";
            $etime = ""; 
            
            $log = $this->attcompute->displayLogTimeFlexi($employeeid,$date,$edata);
            list($otreg,$otrest,$othol) = $this->attcompute->displayOt($employeeid,$date,false);
            // echo "<pre>";echo "DISPLAY OT: ";print_r($this->db->last_query());
            if($otreg || $otrest || $othol){
                $ot_remarks = "OVERTIME APPLICATION";
            }

           list($el, $vl, $sl, $ol, $oltype, $ob, $abs_count, $l_nopay, $obtypes, $ob_id) = $this->attcompute->displayLeave($employeeid, $date);

            if($ol == "DIRECT"){
                $is_wfh = $this->attcompute->isWfhOB($employeeid,$date);
                if($is_wfh->num_rows() == 1 && $obtypes==2){
                    $ob_id = $is_wfh->row()->aid;
                    $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$date);
                    if($hastime->num_rows() == 0) $ol = $oltype = "";
                }
            }else{
                $el = $vl = $sl = 0;
            }

            $tworkhours = $this->attendance->totalWorkhoursPerday($employeeid, $date);

            //Service Credit 
            $service_credit = $this->attcompute->displayServiceCredit($employeeid,$stime,$etime,$date);
            
            $sc_application = $this->attcompute->displaySCAttendance($employeeid,$date, $stime, $etime);
            if($sc_application > 0){
                if($sc_app_remarks != "Approved Conversion Service Credit"){
                    $sc_app_remarks.=($sc_app_remarks?", Approved Conversion Service Credit":"Approved Conversion Service Credit");
                }
            }

            // SC APPLICATION 
            $sc_status = $this->attcompute->getServiceCreditStatus($employeeid, $date);
            $sc_remarks = ""; 
            if ($sc_status) { 
                if ($sc_status->status == "APPROVED") {
                    $sc_remarks .= ($sc_remarks ? "<br>" : "") . "APPROVED SERVICE CREDIT APPLICATION";
                }
            }
            
            // Leave Pending
            $pending = $this->attcompute->displayPendingApp($employeeid,$date);

            $pending .= $this->attcompute->displayPendingOBWfh($employeeid,$date);
            // Overtime
            list($otreg,$otrest,$othol) = $this->attcompute->displayOt($employeeid,$date,false);

            if($otreg){
                $totr += $this->attcompute->exp_time($otreg);
                $ot_remarks = "OVERTIME APPLICATION";
            }

            if($otrest){
                $totrest += $this->attcompute->exp_time($otrest);
                $ot_remarks = "OVERTIME APPLICATION";
            }

            if($othol){
                $tothol += $this->attcompute->exp_time($othol);
                $ot_remarks = "OVERTIME APPLICATION";
            }

            if($ol == "DA"){
                $ol = $ob;
            }

            if(count($log) > 0){
                $login = $logout = $q = "";
                $stime = $etime = "--";

                for($i = 0;$i < count($log);$i++){
                    $login = $log[$i][0];
                    $logout = $log[$i][1];
                    $q = $log[$i][2];
                    if($q) $totalQ++;

                    $off_time_in = $off_time_out = $off_time_total = $actlog_time_in = $actlog_time_out = $terminal = $twr = $ot_regular = $ot_restday = $ot_holiday = $late = $undertime = $vl_deduc_late = $vl_deduc_undertime = $absent_data = $service_credit = $cto_credit = $remarks = $vacation = $sick = $other = $holiday_data = "--";
                    $off_time_total = $this->attcompute->sec_to_hm($perday_absent);
                    $actlog_time_in = $login?date("h:i A",strtotime($login)):"--";
                    $actlog_time_out = $logout?date("h:i A",strtotime($logout)):"--";
                    
                    $terminal = $this->extensions->getTerminalName($campus_tap);
                    $twr = $tworkhours;
                    $ot_regular = $otreg?$otreg:"--";
                    $ot_restday = $otrest?$otrest:"--";
                    $ot_holiday = $othol?$othol: "--";
                    $service_credit = ($sc_application ? $sc_application : '--');
                    $remarks = "";
                    $remarks .= $ot_remarks;
                    $remarks .= $sc_app_remarks;
                    $remarks .= ($cs_app?$cs_app.'<br>':"");
                    $remarks .= $sc_remarks && $sc_remarks != '--'? $sc_remarks:'';
                    $remarks .= ($pending)?"PENDING ".$pending.'<br>':($ol ? ($oltype ? ($oltype == "ABSENT" ? "ABSENT W/ FILE<br>" : ($oltype == "CORRECTED TIME IN/OUT" ? ($actlog_time_in != "--" && $actlog_time_out != '--' ? $oltype : '') : $oltype )."<br>") : $this->employeemod->othLeaveDesc($ol)."<br>") 
                        : '');
                    $remarks .= $service_credit && $service_credit != '--'?'SERVICE CREDIT<br>':'';
                    $remarks .= (isset($holidayInfo["description"]) ? $holidayInfo["description"] : "");
                    if(strpos($pending, "SERVICE CREDIT") === false && !$service_credit){
                        $remarks .= '<a class="btn btn-success" id="applysc" href="#" data-toggle="modal" data-target="#myModal1" style="display: none;"> dateInitial="$remarks .= $date?>" >Apply as Service Credit
                                    <span class="notifdiv bell" style="position: relative;top:5px;"><i class="glyphicon glyphicon-bell large" style="color: #FF1744;font-size: 20px;"></i></span></a>';
                    }
                    $holiday_data = (isset($holidayInfo['type']) ? $holidayInfo['type'] : '');
                    $holiday_type = (isset($holidayInfo['holiday_type']) && $holiday) ?$holidayInfo['holiday_code'] : "";

                    $this->db->query("INSERT INTO employee_attendance_nonteaching
                    SET employeeid = '$employeeid',
                        `date` = '$date',
                        off_time_in = '$off_time_in',
                        off_time_out = '$off_time_out',
                        off_time_total = '$off_time_total',
                        actlog_time_in = '$actlog_time_in',
                        actlog_time_out = '$actlog_time_out',
                        terminal = '$terminal',
                        twr = '$twr',
                        ot_regular = '$ot_regular',
                        ot_restday = '$ot_restday',
                        ot_holiday = '$ot_holiday',
                        late = '$late',
                        undertime = '$undertime',
                        vl_deduc_late = '$vl_deduc_late',
                        vl_deduc_undertime = '$vl_deduc_undertime',
                        absent = '$absent_data',
                        service_credit = '$service_credit',
                        cto = '$cto_credit',
                        remarks = ".$this->db->escape($remarks).",
                        vl = '$vacation',
                        sl = '$sick',
                        other = '$other',
                        holiday = '$holiday_data'
                        ");
                } // for($i = 0;$i < count($log);$i++){
            }else{ // if(count($log) > 0)
                $sc_application = $this->attcompute->displaySCAttendance($employeeid,$date, $stime, $etime);
                if($sc_application > 0){
                    if($sc_app_remarks != "Approved Conversion Service Credit"){
                        $sc_app_remarks.=($sc_app_remarks?", Approved Conversion Service Credit":"Approved Conversion Service Credit");
                    }
                }

                $log = $this->attcompute->displayLogTimeFlexi($employeeid,$date,$edata);
                $off_time_in = $off_time_out = $off_time_total = $actlog_time_in = $actlog_time_out = $terminal = $twr = $ot_regular = $ot_restday = $ot_holiday = $late = $undertime = $vl_deduc_late = $vl_deduc_undertime = $absent_data = $service_credit = $cto_credit = $remarks = $vacation = $sick = $other = $holiday_data = "--";

                $login = $this->timesheet->noSchedLog($employeeid, $date, "ORDER BY logtime ASC");
                if($login !== false){
                    $actlog_time_in = date("h:i A", strtotime($login));
                }

                $logout = $this->timesheet->noSchedLog($employeeid, $date, "ORDER BY logtime DESC");
                if($logout !== false && $login != $logout){
                    $actlog_time_out = date("h:i A", strtotime($logout));
                }

                $off_time_total = $this->attcompute->sec_to_hm($perday_absent);
                $terminal = $this->extensions->getTerminalName($campus_tap);
                $twr = $tworkhours;
                $ot_regular = $otreg?$otreg:"--";
                $ot_restday = $otrest?$otrest:"--";
                $ot_holiday = $othol?$othol: "--";
                $service_credit = ($sc_application ? $sc_application : '--');
                $remarks = "";
                $remarks .= $ot_remarks;
                $remarks .= $sc_app_remarks ;
                $remarks .= ($pending) ? "PENDING ".$pending."<br>" : "";
                $remarks .= ($ol ? ($oltype ? ($oltype == "ABSENT" ? "ABSENT W/ FILE" : $oltype) : $this->employeemod->othLeaveDesc($ol)) : "");
                $remarks .= $service_credit && $service_credit != '--'?'SERVICE CREDIT<br>':'';
                $remarks .= $sc_remarks && $sc_remarks != '--'? $sc_remarks:'';
                $remarks .= (isset($holidayInfo["description"]) ? $holidayInfo["description"] : "");
                $holiday_data = (isset($holidayInfo['type']) ? $holidayInfo['type'] : '');
                $holiday_type = (isset($holidayInfo['holiday_type']) && $holiday) ?$holidayInfo['holiday_code'] : "";
                
                $this->db->query("INSERT INTO employee_attendance_nonteaching
                SET employeeid = '$employeeid',
                    `date` = '$date',
                    off_time_in = '$off_time_in',
                    off_time_out = '$off_time_out',
                    off_time_total = '$off_time_total',
                    actlog_time_in = '$actlog_time_in',
                    actlog_time_out = '$actlog_time_out',
                    terminal = '$terminal',
                    twr = '$twr',
                    ot_regular = '$ot_regular',
                    ot_restday = '$ot_restday',
                    ot_holiday = '$ot_holiday',
                    late = '$late',
                    undertime = '$undertime',
                    vl_deduc_late = '$vl_deduc_late',
                    vl_deduc_undertime = '$vl_deduc_undertime',
                    absent = '$absent_data',
                    service_credit = '$service_credit',
                    cto = '$cto_credit',
                    remarks = ".$this->db->escape($remarks).",
                    vl = '$vacation',
                    sl = '$sick',
                    other = '$other',
                    holiday = '$holiday_data'
                    ");
            } 
            $ot_remarks = $sc_app_remarks = $wfh_app_remarks = "";
        } 
    }

    public function removeExistingAttendance($employeeid, $date, $teaching = true){
        if($teaching === true){
            $this->db->query("DELETE FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND date = '$date'");
        }else{
            $this->db->query("DELETE FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND date = '$date'");
        }
    }

    /**
     * @author Leandrei Santos
     *
     * Itong function ay para ma check kung kailangan i display pa yung actual log time  
     * display = kung pasok yung actual log time sa official time.
     */
    public function validateLogTimeWithinWorkSchedule($official_out,$actLog_time)
    {
        $official_out_formatted = date('H:i A', strtotime($official_out));
        $actLog_tim_formatted = date('H:i A', strtotime($actLog_time));

        return $official_out_formatted > $actLog_tim_formatted;
    }

    /**
     * @author Leandrei Santos
     * STHERESE PROCESS
     * New Computation of OT
     * 
     * Late deduction = late - OT
     * kapag lumabas sa late deduction is negative automatic 0:00 na ito
     * 
     */
    function calculateLateDeduction($late, array $overtime)
    {
        // Find the first valid overtime value or default to 0
        $validOvertime = 0;
        foreach ($overtime as $ot) {
            if (!empty($ot)) {
                $validOvertime = $ot;
                break;
            }
        }
    
        // Convert time values to seconds
        $lateInSeconds = $this->attcompute->exp_time($late);
        $overtimeInSeconds = $this->attcompute->exp_time($validOvertime);
    
        // Calculate the late deduction
        $lateDeduction = $lateInSeconds - $overtimeInSeconds;
    
        // Ensure the deduction is not negative
        if ($lateDeduction < 0) {
            return '0:00';
        }
    
        // Convert back to hours and minutes
        return $this->attcompute->sec_to_hm($lateDeduction);
    }    
    
    function getEmployeeList($where = "", $orderBy = ""){
        return $this->db->query("SELECT 
        a.employeeid, 
        a.fname, 
        a.lname, 
        SUBSTRING(a.`mname`, 1, 1) as mname, 
        b.`description` as department, 
        TRIM(c.`description`) 
        as position_desc, 
        d.description as employement_desc,
        a.campusid
        FROM employee a 
        LEFT JOIN `code_department` b on a.`deptid` = b.`code` 
        LEFT JOIN `code_position` c on a.`positionid` = c.`positionid` 
        LEFT JOIN `code_status` d on a.`employmentstat` = d.`code`
        WHERE 1 = 1 $where $orderBy")->result();
    }

    function updateDTR($employeeid, $date_from, $date_to, $for_report=true){
        if($for_report === true){
            $date_range = $this->attcompute->displayDateRange($date_from, $date_to);
            foreach ($date_range as $date) {
                $query = $this->db->query("SELECT id FROM employee_attendance_update WHERE employeeid = '$employeeid' AND `date` = '$date->dte'")->num_rows();
                if($query > 0){
                    $this->db->query("UPDATE employee_attendance_update SET hasUpdate = '1' WHERE employeeid = '$employeeid' AND `date` = '$date->dte'");
                }else{
                    $this->db->query("INSERT INTO employee_attendance_update SET hasUpdate = '1', employeeid = '$employeeid', `date` = '$date->dte'");
                }
            }
        }else{
            if($date_from != "0000-00-00" && $date_to != "0000-00-00" && $employeeid)
            {
                // Prepare data to insert if not existing
                $to_calculate = array(
                    "employeeid" => $employeeid,
                    "dfrom" => $date_from,
                    "dto" => $date_to,
                    "endpoint" => base_url()
                );
            
                // Check if the record already exists
                $is_exist = $this->db->select("employeeid")->where($to_calculate)->get("employee_to_calculate")->num_rows();
            
                // If it doesn't exist, insert the new record
                if ($is_exist === 0) {
                    $this->db->insert("employee_to_calculate", $to_calculate);
                }
            }
        }
    }

    function checkIfHasLogDelete($employeeid, $date){
        $this->db->query("DELETE FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date'");
        $this->db->query("DELETE FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND `date` = '$date'");
        $today = date('Y-m-d');
        $query_nonteaching = $this->db->query("SELECT * FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date'")->num_rows();
        $query_teaching = $this->db->query("SELECT * FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND `date` = '$date'")->num_rows();
        $query = $this->db->query("SELECT * FROM employee_attendance_update WHERE employeeid = '$employeeid' AND `date` = '$date' AND hasUpdate = '1'")->num_rows();
        if($query > 0 || $date >= $today){
            if($query_nonteaching > 0){
                $this->db->query("DELETE FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date'");
                $this->db->query("UPDATE employee_attendance_update SET hasUpdate = '0' WHERE employeeid = '$employeeid' AND `date` = '$date'");
                return false;
            }

            if($query_teaching > 0){
                $this->db->query("DELETE FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND `date` = '$date'");
                $this->db->query("UPDATE employee_attendance_update SET hasUpdate = '0' WHERE employeeid = '$employeeid' AND `date` = '$date'");
                return false;
            }
        }else{
            if($query_nonteaching > 0){
                return true;
            }else if($query_teaching > 0){
                return true;
            }else{
                return false;
            }
        }
    }

  
    
    function checkIfHasLogOld($employeeid, $date){
        $today = date('Y-m-d');
        $query_nonteaching = $this->db->query("SELECT * FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date'")->num_rows();
        $query_teaching = $this->db->query("SELECT * FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND `date` = '$date'")->num_rows();
        $query = $this->db->query("SELECT * FROM employee_attendance_update WHERE employeeid = '$employeeid' AND `date` = '$date' AND hasUpdate = '1'")->num_rows();
        if($query > 0 || $date >= $today){
            if($query_nonteaching > 0){
                $this->db->query("DELETE FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date'");
                $this->db->query("UPDATE employee_attendance_update SET hasUpdate = '0' WHERE employeeid = '$employeeid' AND `date` = '$date'");
                return false;
            }

            if($query_teaching > 0){
                $this->db->query("DELETE FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND `date` = '$date'");
                $this->db->query("UPDATE employee_attendance_update SET hasUpdate = '0' WHERE employeeid = '$employeeid' AND `date` = '$date'");
                return false;
            }
        }else{
            if($query_nonteaching > 0){
                return true;
            }else if($query_teaching > 0){
                return true;
            }else{
                return false;
            }
        }
    }

    function checkIfHasLog($employeeid, $date) {
        $today = date('Y-m-d');

        // Queries for attendance and updates
        $query_nonteaching = $this->db->query("SELECT * FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND date = '$date'");
        $query_teaching = $this->db->query("SELECT * FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND date = '$date'");
        $hasUpdate = $this->db->query("SELECT employeeid FROM employee_attendance_update WHERE employeeid = '$employeeid' AND date = '$date' AND hasUpdate = '1'")->num_rows() > 0;

        // Check for update or future date, handle attendance deletion
        if ($hasUpdate || $date >= $today) {
            if ($query_nonteaching->num_rows() > 0) {
                $this->deleteAttendanceAndResetUpdate($employeeid, $date, 'nonteaching');
                return false;
            }
            if ($query_teaching->num_rows() > 0) {
                $this->deleteAttendanceAndResetUpdate($employeeid, $date, 'teaching');
                return false;
            }
        } else {
            // Handle cases with or without actual log time
            if ($query_nonteaching->num_rows() > 0) {
                return $this->processAttendanceRow($query_nonteaching->row(), $employeeid, $date, 'nonteaching');
            }
            if ($query_teaching->num_rows() > 0) {
                return $this->processAttendanceRow($query_teaching->row(), $employeeid, $date, 'teaching');
            }
        }

        return false;
    }

    // Helper function to delete attendance and reset update
    private function deleteAttendanceAndResetUpdate($employeeid, $date, $type) {
        $table = $type === 'nonteaching' ? 'employee_attendance_nonteaching' : 'employee_attendance_teaching';
        $table_history = $type === 'nonteaching' ? 'employee_attendance_nonteaching_history' : 'employee_attendance_teaching_history';
        $table_equivalent = [
            $table => $table_history,
        ];
        
        foreach ($table_equivalent as $from_table => $to_table) {
            // Retrieve columns for both tables
            $from_columns = $this->db->query("SHOW COLUMNS FROM $from_table")->result();
            $to_columns = $this->db->query("SHOW COLUMNS FROM $to_table")->result();
        
            // Filter out 'auto_increment' columns and store column names
            $from_column_list = array_column(
                array_filter($from_columns, function($col) { return $col->Extra !== 'auto_increment'; }),
                'Field'
            );
        
            $to_column_list = array_column(
                array_filter($to_columns, function($col) { return $col->Extra !== 'auto_increment'; }),
                'Field'
            );
        
            // Find common columns to create ordered column list for the query
            $order_column = implode(',', array_intersect($from_column_list, $to_column_list));
        
            // Build the insert query with selected columns
            $query = "INSERT INTO $to_table ($order_column) 
                      SELECT $order_column 
                      FROM $from_table 
                      WHERE employeeid = ? AND date = ?";
        
            // Execute the query using bound parameters for security
            $transfer = $this->db->query($query, [$employeeid, $date]);
        }
        
        
        $this->db->query("DELETE FROM $table WHERE employeeid = '$employeeid' AND date = '$date'");
        $this->db->query("UPDATE employee_attendance_update SET hasUpdate = '0' WHERE employeeid = '$employeeid' AND date = '$date'");
    }

    // Helper function to process attendance row
    private function processAttendanceRow($attendanceRow, $employeeid, $date, $type) {
        if ($attendanceRow->actlog_time_in == "--") {
            $query_timesheet = $this->db->query("SELECT * FROM timesheet WHERE userid = '$employeeid' AND DATE(timein) = '$date'");
            if ($query_timesheet->num_rows() > 0) {
                $this->deleteAttendanceAndResetUpdate($employeeid, $date, $type);
                return false;
            }
            return true;
        }
        return true;
    }

    function getAttendanceTeaching($employeeid, $datesetfrom, $datesetto){
        $attendance = array();
        $date_range = $this->attcompute->displayDateRange($datesetfrom, $datesetto);
        foreach ($date_range as $date) {
            $attendance[$date->dte] = $this->db->query("SELECT * FROM employee_attendance_teaching WHERE employeeid = '$employeeid' AND `date` = '$date->dte' ORDER BY `id`")->result();
        }
        return $attendance;
    }

    function getAttendanceNonteaching($employeeid, $datesetfrom, $datesetto){
        $attendance = array();
        $date_range = $this->attcompute->displayDateRange($datesetfrom, $datesetto);
        foreach ($date_range as $date) {
            $attendance[$date->dte] = $this->db->query("SELECT * FROM employee_attendance_nonteaching WHERE employeeid = '$employeeid' AND `date` = '$date->dte' ORDER BY `id`")->result();
        }

        return $attendance;
    }

    function getTeachingAttendanceSummary($attendance){
        $off_lec_total = $off_lab_total = $off_admin_total = $off_overload_total = $twr_lec_total = $twr_lab_total = $twr_admin_total = $twr_overload_total = $teaching_overload_total = $ot_regular_total = $ot_restday_total = $ot_holiday_total = $lateut_lec_total = $lateut_lab_total = $lateut_admin_total = $lateut_overload_total = $absent_lec_total = $absent_lab_total = $absent_admin_total = $service_credit_total = $cto_total = $holiday_lec_total = $holiday_lab_total = $holiday_admin_total = $holiday_overload_total = $holiday_total = $date_list_absent = $total_absent = $vacation_total = $emergency_total = $other_total = $sick_total =  0;
        foreach ($attendance as $att_date) {
            $counter = 0;
            $rowspan = 0;
            $is_absent = 0;
            $date = "";
            foreach ($att_date as $key => $value) {
                $off_lec_total += $this->attcompute->exp_time($value->off_lec);
                $off_lab_total += $this->attcompute->exp_time($value->off_lab);
                $off_admin_total += $this->attcompute->exp_time($value->off_admin);
                $off_overload_total += $this->attcompute->exp_time($value->off_overload);

                $twr_lec_total += $this->attcompute->exp_time($value->twr_lec);
                $twr_lab_total += $this->attcompute->exp_time($value->twr_lab);
                $twr_admin_total += $this->attcompute->exp_time($value->twr_admin);
                $twr_overload_total += $this->attcompute->exp_time($value->twr_overload);

                if($value->teaching_overload != "" && $value->teaching_overload != "--") $teaching_overload_total += $this->attcompute->exp_time($value->teaching_overload);

                if($counter == 0){
                    $ot_regular_total += $this->attcompute->exp_time($value->ot_regular);
                    $ot_restday_total += $this->attcompute->exp_time($value->ot_restday);
                    $ot_holiday_total += $this->attcompute->exp_time($value->ot_holiday);
                    $vacation_total += $value->vacation;
                    $emergency_total += $value->emergency;
                    $sick_total += $value->sick;
                    $other_total += $value->other;

                }

                $lateut_lec_total += $this->attcompute->exp_time($value->lateut_lec);
                $lateut_lab_total += $this->attcompute->exp_time($value->lateut_lab);
                $lateut_admin_total += $this->attcompute->exp_time($value->lateut_admin);
                $lateut_overload_total += $this->attcompute->exp_time($value->lateut_overload);

                $absent_lec_total += $this->attcompute->exp_time($value->absent_lec);
                $absent_lab_total += $this->attcompute->exp_time($value->absent_lab);
                $absent_admin_total += $this->attcompute->exp_time($value->absent_admin);

                if($value->service_credit != "" && $value->service_credit != "--") $service_credit_total += $value->service_credit;
                if($value->cto != "" && $value->cto != "--") $cto_total +=  $this->attcompute->exp_time($value->cto);

                $holiday_lec_total += $this->attcompute->exp_time($value->holiday_lec);
                $holiday_lab_total += $this->attcompute->exp_time($value->holiday_lab);
                $holiday_admin_total += $this->attcompute->exp_time($value->holiday_admin);
                $holiday_overload_total += $this->attcompute->exp_time($value->holiday_overload);

                if($value->holiday && $counter == 0) $holiday_total++;

                if($value->absent_lec && $value->absent_lab && $value->absent_admin) $is_absent++;
                $date = $value->date;
            }
            if($is_absent > 0 && $is_absent == count($att_date)) $total_absent++;
        }

        return array(
            $this->attcompute->sec_to_hm($off_lec_total), 
            $this->attcompute->sec_to_hm($off_lab_total), 
            $this->attcompute->sec_to_hm($off_admin_total), 
            $this->attcompute->sec_to_hm($off_overload_total), 
            $this->attcompute->sec_to_hm($twr_lec_total), 
            $this->attcompute->sec_to_hm($twr_lab_total), 
            $this->attcompute->sec_to_hm($twr_admin_total), 
            $this->attcompute->sec_to_hm($twr_overload_total), 
            ($teaching_overload_total != 0 ? $this->attcompute->sec_to_hm($teaching_overload_total) : ""), 
            ($ot_regular_total ? $this->attcompute->sec_to_hm($ot_regular_total) : ""), 
            ($ot_restday_total ? $this->attcompute->sec_to_hm($ot_restday_total) : ""), 
            ($ot_holiday_total ? $this->attcompute->sec_to_hm($ot_holiday_total) : ""), 
            ($lateut_lec_total ? $this->attcompute->sec_to_hm($lateut_lec_total) : ""), 
            ($lateut_lab_total ? $this->attcompute->sec_to_hm($lateut_lab_total) : ""), 
            ($lateut_admin_total ? $this->attcompute->sec_to_hm($lateut_admin_total) : ""), 
            ($lateut_overload_total ? $this->attcompute->sec_to_hm($lateut_overload_total) : ""), 
            ($absent_lec_total ? $this->attcompute->sec_to_hm($absent_lec_total) : ""), 
            ($absent_lab_total ? $this->attcompute->sec_to_hm($absent_lab_total) : ""), 
            ($absent_admin_total ? $this->attcompute->sec_to_hm($absent_admin_total) : ""), 
            $service_credit_total, ($cto_total ? $this->attcompute->sec_to_hm($cto_total) : ""), 
            ($holiday_lec_total != 0 ? $this->time->minutesToHours($holiday_lec_total) : ""), 
            ($holiday_lab_total != 0 ? $this->time->minutesToHours($holiday_lab_total) : ""), 
            ($holiday_admin_total != 0 ? $this->time->minutesToHours($holiday_admin_total) : ""), 
            ($holiday_overload_total != 0 ? $this->time->minutesToHours($holiday_overload_total) : ""), 
            $holiday_total, 
            $total_absent,
            $emergency_total,
            $sick_total,
            $vacation_total,
            $other_total);
    }

    function checkAttendanceDate($date){
        return $this->db->query("SELECT * FROM employee_attendance_date WHERE `date` = '$date'");
    }

    function saveAttendanceDate($employeelist, $date){
        $this->db->query("INSERT INTO employee_attendance_date SET employee_list = '$employeelist', `date` = '$date'");
    }

    function updateAttendanceDate($employeelist, $date){
        $this->db->query("UPDATE employee_attendance_date SET employee_list = '$employeelist' WHERE `date` = '$date'");
    }

    // function getAbsentTardySummary($dateFrom, $dateTo, $employeeId) {
    //     return $this->db->query("SELECT eant.`employeeid`, CONCAT(e.`fname`, ' ', e.`lname`) AS fullname, `date`, CONCAT(`late`,`undertime`) AS late, `absent`, CONCAT(vl,sl,el,other) AS `leave` FROM `employee_attendance_nonteaching` AS eant
    //                                 INNER JOIN employee AS e ON e.employeeid=eant.employeeid
    //                             WHERE `date` BETWEEN '$dateFrom' AND '$dateTo' 
    //                                 AND (late NOT IN ('', '--')  OR undertime NOT IN ('', '--')
    //                                 OR absent NOT IN ('', '--'))
    //                                 AND eant.employeeid = '$employeeId'
    //                             UNION
    //                             SELECT eat.`employeeid`, CONCAT(e.`fname`, ' ', e.`lname`) AS fullname, `date`, CONCAT(`lateut_lec`,`lateut_lab`,`lateut_admin`,`lateut_overload`) AS late, CONCAT(`absent_lec`,`absent_lab`,`absent_admin`) AS absent, CONCAT(vacation,emergency,sick,other) AS `leave` FROM `employee_attendance_teaching` AS eat
    //                                 INNER JOIN employee AS e ON e.employeeid=eat.employeeid
    //                             WHERE `date` BETWEEN '$dateFrom' AND '$dateTo' 
    //                                 AND (lateut_lec NOT IN ('', '--')  OR lateut_lab NOT IN ('', '--')  OR lateut_admin NOT IN ('', '--') OR lateut_overload NOT IN ('', '--') 
    //                                 OR absent_lec NOT IN ('', '--') OR absent_lab NOT IN ('', '--') OR absent_admin NOT IN ('', '--')) 
    //                                 AND eat.employeeid = '$employeeId'
    //                             ORDER BY `employeeid`,`date`")->result();
    // }

    function getAbsentTardySummary($dateFrom, $dateTo, $employeeId, $teachingType) {
        if ($teachingType == 'teaching') {
            return $this->db->query("SELECT eat.`employeeid`,
                                        CONCAT(e.`fname`, ' ', e.`lname`) AS fullname,
                                        GROUP_CONCAT(DATE_FORMAT(`date`, '%d' ) ORDER BY DATE) AS `date`,
                                        GROUP_CONCAT(CONCAT(`lateut_lec`,`lateut_lab`,`lateut_admin`,`lateut_overload`) ORDER BY DATE) AS late,
                                        GROUP_CONCAT(CONCAT(`absent_lec`,`absent_lab`,`absent_admin`) ORDER BY DATE) AS absent,
                                        GROUP_CONCAT(CONCAT(vacation, emergency, sick, other) ORDER BY DATE) AS `leave`
                                    FROM `employee_attendance_teaching` AS eat
                                        INNER JOIN employee AS e ON e.employeeid=eat.employeeid
                                    WHERE `date` BETWEEN '$dateFrom' AND '$dateTo' 
                                        AND (lateut_lec NOT IN ('', '--')  OR lateut_lab NOT IN ('', '--')  OR lateut_admin NOT IN ('', '--') OR lateut_overload NOT IN ('', '--') 
                                        OR absent_lec NOT IN ('', '--') OR absent_lab NOT IN ('', '--') OR absent_admin NOT IN ('', '--')) 
                                        AND eat.employeeid = '$employeeId'
                                    ORDER BY `date`")->result();
        } else {
            return $this->db->query("SELECT eant.`employeeid`,
                                        CONCAT(e.`fname`, ' ', e.`lname`) AS fullname,
                                        GROUP_CONCAT(DATE_FORMAT(`date`, '%d' ) ORDER BY DATE) AS `date`,
                                        GROUP_CONCAT(CONCAT(`late`, `undertime`) ORDER BY DATE) AS late,
                                        GROUP_CONCAT(`absent` ORDER BY DATE) AS `absent`,
                                        GROUP_CONCAT(CONCAT(vl, sl, el, other) ORDER BY DATE) AS `leave`
                                    FROM `employee_attendance_nonteaching` AS eant
                                        INNER JOIN employee AS e ON e.employeeid=eant.employeeid
                                    WHERE `date` BETWEEN '$dateFrom' AND '$dateTo' 
                                        AND (late NOT IN ('', '--')  OR undertime NOT IN ('', '--')
                                        OR absent NOT IN ('', '--'))
                                        AND eant.employeeid = '$employeeId'
                                    ORDER BY `date`")->result();
        }
    }

    function getEmployeeIDTeachingTypeList($employeeid='all', $campusid='all', $employmentStatus='') {
        $where = "WHERE 1";
        $where .= $campusid != 'All' && $campusid ? " AND campusid = '$campusid'" : '';
        $where .= $employeeid != 'all' && $employeeid ? " AND employeeid = '$employeeid'" : '';
        $where .= $employmentStatus ? " AND employmentstat = '$employmentStatus'" : '';
        return $this->db->query("SELECT DISTINCT employeeid, teaching FROM employee $where")->result();
    }

    function checkConfirmedAttendance($employeeid, $date_from, $date_to){
        $query = $this->db->query("SELECT * FROM attendance_confirmed WHERE employeeid = '$employeeid' AND cutoffstart = '$date_from' AND cutoffend = '$date_to'")->num_rows();
        $query_nt = $this->db->query("SELECT * FROM attendance_confirmed_nt WHERE employeeid = '$employeeid' AND cutoffstart = '$date_from' AND cutoffend = '$date_to'")->num_rows();
        return $query+$query_nt;
    }

    function getDateTimeDiff($dateTimeFrom, $dateTimeTo){
        // Declare and define two dates
        $dateTimeFrom = strtotime($dateTimeFrom);
        $dateTimeTo = strtotime($dateTimeTo);

        $diff = abs($dateTimeTo - $dateTimeFrom);

        $years = floor($diff / (365*60*60*24));

        $months = floor(($diff - $years * 365*60*60*24)
                                        / (30*60*60*24));
        $days = floor(($diff - $years * 365*60*60*24 -
                    $months*30*60*60*24)/ (60*60*24));
        $hours = floor(($diff - $years * 365*60*60*24
                - $months*30*60*60*24 - $days*60*60*24)
                                            / (60*60));
        $minutes = floor(($diff - $years * 365*60*60*24
                - $months*30*60*60*24 - $days*60*60*24
                                    - $hours*60*60)/ 60);
        $seconds = floor(($diff - $years * 365*60*60*24
                - $months*30*60*60*24 - $days*60*60*24
                        - $hours*60*60 - $minutes*60));
        return array($years, $months, $days, $hours, $minutes, $seconds);
    }

    function calculateTimeDifference($offTime, $lateutTime) {
        if (!$lateutTime) {
            return $offTime; // If no late time, return original time
        }
    
        $diffInSeconds = abs(strtotime($offTime) - strtotime($lateutTime));
        $hours = floor($diffInSeconds / 3600);
        $minutes = floor(($diffInSeconds % 3600) / 60);
    
        return sprintf("%02d:%02d", $hours, $minutes);
    }

    public function employee_to_calculate($details){
        $this->db->insert("employee_to_calculate", $details);
    }

    public function getEmployeeListCounts($where){
        return $this->db->query("SELECT 
            CONCAT(a.lname, ', ', a.fname , ' ', a.mname) AS fullname,
            a.employeeid, 
            a.fname, 
            a.lname, 
            SUBSTRING(a.`mname`, 1, 1) as mname, 
            b.`description` as department, 
            TRIM(c.`description`) 
            as position_desc, 
            d.description as employement_desc,
            a.campusid
            FROM employee a 
            LEFT JOIN `code_department` b on a.`deptid` = b.`code` 
            LEFT JOIN `code_position` c on a.`positionid` = c.`positionid` 
            LEFT JOIN `code_status` d on a.`employmentstat` = d.`code`
            WHERE 1 = 1 $where
            ORDER BY fullname ASC")->num_rows();
    }

    /**
     * Check if the employee has any log entry on the last scheduled day before the given date,
     * looking back up to 5 days.
     *
     * @param string $employee_id The ID of the employee.
     * @param string $date        The date in 'Y-m-d' format.
     *
     * @return int The number of log records found for the employee on the last scheduled day before the given date.
     */
    public function checkEmployeeLog($employee_id, $date) {
        $look_back_limit = 5;
        $days_checked = 0;

        // Start with the day before the given date
        $previous_date = date('Y-m-d', strtotime($date . ' -1 day'));

        while ($days_checked < $look_back_limit) {
            $sched = $this->attcompute->displaySched($employee_id, $previous_date);

            if ($sched && $sched->num_rows() > 0) {
                // Found a scheduled day, check for logs
                return $this->db->query("
                    SELECT id 
                    FROM facial_Log 
                    WHERE employeeid = '$employee_id' 
                    AND DATE(FROM_UNIXTIME(FLOOR(TIME/1000))) = '$previous_date'
                ")->num_rows();
            }

            // Move back one more day
            $previous_date = date('Y-m-d', strtotime($previous_date . ' -1 day'));
            $days_checked++;
        }

        // No scheduled day found within the look-back limit
        return 0;
    }

    
}
/* End of file employee.php */
/* Location: ./application/models/employee.php */
