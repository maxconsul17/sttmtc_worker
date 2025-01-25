<?php 
   $style = '
   <style>
       .form_no{
           font-size:0.9rem;
       }

       .main-wrapper{
           height:100%;
           width:100%;
           padding-right:25%;
           padding-left:25%;
       }
       .table-header,.sub-header,.main-table{
           width:100%;
           height:100%;
           font-size:18px;
       }

       .main-table{
           border-collapse:collapse;
           border: 1px solid black;
       }

       .main-table td{
           border: 1px solid black;
           font-size:18px;
       }

       .title{
           font-size:1.5rem;
       }

       .name{
           font-size:1.5rem;
       }

       .tc{
           text-align:center;
           font-size:27;
       }
       
       .tb{
           font-weight:bold;
       }
       .m10{
           margin-top:10px;
       }
   </style>';

    $fullname = $this->worker_model->getEmployeeName($employeeid);
    $campus = $this->worker_model->getEemployeeCurrentData($employeeid, 'campusid');
    $employmentstat = $this->worker_model->getemployeestatus($this->worker_model->getEemployeeCurrentData($employeeid, 'employmentstat'));
    $department = $this->worker_model->getEmployeeDepartment($employeeid);

    $content = '
    <table style="width:100%;">
        <tr style="margin-right:15px;">';
        $att_mirror='
            <td style="padding-right: 18px;">
                <div class="container main-wrapper">
                    <table class="table-header">
                        <tr><td class="form_no"><i>Civil Service Form No. 48</i></td></tr>
                        <tr><td class="tc tb title">'.strtoupper("DAILY TIME RECORD").'</td></tr>
                        <tr><td class="tc"><h3>-----o0o-----</h3></td></tr>
                        <tr><td class="tc name">'.$fullname.'</td></tr>
                        <tr><td><hr></td></tr>
                        <tr><td class="tc" style="font-size:20px;">NAME</td></tr>
                    </table>
                    <br>
                    <table class="table-header" style="font-size:15px;">
                        <tr>
                            <td width="22%">For the month of</td> 
                            <td width="78%">'.strtoupper($month_of).'<hr style="margin: 0px; padding: 0px;"></td>
                        </tr>
                    </table>

                    <table class="sub-header m10" style="font-size:19px;">
                        <tr>
                            <td style="font-size:15px;">Official hours for arrival</td>
                            <td style="font-size:15px;">Regular <br>Days</td>
                            <td style="width:100px;font-size:15px;"><hr></td>
                        </tr>
                        <tr>
                            <td style="padding-left:15px;font-size:15px;">and departure</td>
                            <td style="font-size:15px;">Saturdays</td>
                            <td style="font-size:15px;"><hr></td>
                        </tr>

                    </table>

                    <table class="main-table m10" style="font-size:19px;">
                        <tr>
                            <td class="tc" rowspan="2">Day</td>
                            <td class="tc" colspan="2">A.M</td>
                            <td class="tc" colspan="2">P.M</td>
                            <td class="tc" colspan="2">Undertime/late</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="tc">Arrival</td>
                            <td class="tc">Departure</td>
                            <td class="tc">Arrival</td>
                            <td class="tc">Departure</td>
                            <td class="tc">Hours</td>
                            <td class="tc">Minutes</td>
                            <td class="tc">Remarks</td>
                        </tr>';
                       
                        foreach ($attendance as $date_arr => $att_date) {
                            $AM_arrival = $AM_departure = $PM_arrival = $PM_departure = $hour = $minute = $remarks = "";
                            
                            if($att_date){
                                $date = $this->time->DayFormatted($date_arr);
                                $AM_arrival = (isset($att_date[0]) ? $this->time->formatTimeOutputNew($att_date[0]->actlog_time_in, true) : '');
                                $AM_departure = (isset($att_date[0]) ?$this->time->formatTimeOutputNew($att_date[0]->actlog_time_out, true) : '');

                                $lastDataIndexPerDate = count($att_date)-1; //Para makuha yung pinaka last out nya 

                                $PM_arrival = (isset($att_date[$lastDataIndexPerDate]) && count($att_date) > 1 ? $this->time->formatTimeOutputNew($att_date[$lastDataIndexPerDate]->actlog_time_in, true):'');
                                $PM_departure = (isset($lastDataIndexPerDate) && count($att_date) > 1 ? $this->time->formatTimeOutputNew($att_date[$lastDataIndexPerDate]->actlog_time_out, true):'');

                                list($hour,$minute) = $this->time->totalLateUndertimeDuration($att_date);

                                $remarks = "";
                                // $remarks = (isset($att_date[$lastDataIndexPerDate])  ?$this->time->formatTimeOutput($att_date[$lastDataIndexPerDate]->remarks) : '');
                                if($this->worker_model->displaySched($employeeid, $date_arr)->num_rows() == 0){
                                    // $remarks = "No Shedule";
                                    $remarks = date("l", strtotime($date_arr));
                                }

                                // if (strpos($remarks, "PENDING") !== false) {
                                //     $remarks = "";
                                // }
                                
                                // if (strpos($remarks, "LEAVE") !== false) {
                                //     $remarks = "LEAVE";
                                // }elseif (strpos($remarks, "CORRECTION") !== false) {
                                //     $remarks = "";
                                // }elseif (strpos($remarks, "COC") !== false) {
                                //     $remarks = "OVERTIME";
                                // }elseif (strpos($remarks, "CTO") !== false) {
                                //     $remarks = "OVERTIME";
                                // }elseif (strpos($remarks, "SERVICE CREDIT") !== false) {
                                //     $remarks = "OVERTIME";
                                // }

                                foreach($att_date as $k => $v){
                                    if($v->holiday){
                                        $remarks = "Holiday";
                                    }else if (strpos($v->remarks, 'UNDERTIME') !== false){
                                        if($v->actlog_time_in == "--" && strtotime($v->actlog_time_out) >= strtotime($v->off_time_out)){
                                            // $remarks = "";
                                        }
                                    }else if(strpos($v->remarks, 'NO TIME IN') !== false){
                                        if($v->off_time_in != "--" && $v->off_time_out != "--"){
                                            if($v->actlog_time_in == "--" && $v->actlog_time_out == "--"){
                                                // $remarks = '<span style="color:red">ABSENT</span>'; 
                                            }else if(($v->actlog_time_in == "--" && $v->actlog_time_out != "--") || ($v->actlog_time_in != "--" && $v->actlog_time_out == "--")){
                                                if($AM_arrival == "" && $PM_departure == ""){
                                                    // $remarks = '<span style="color:red">UNDERTIME</span>'; 
                                                }else{
                                                    // $remarks = "";
                                                }
                                            }
                                        }
                                    }else if(strpos($v->remarks, 'OFFICIAL BUSINESS') !== false){
                                        $AM_arrival = $AM_departure = $PM_arrival = $PM_departure = "";
                                    }
                                }
                                

                                if($this->time->validateDateBetween($actual_dates, $date_arr) === false){
                                    $AM_arrival = $AM_departure = $PM_arrival = $PM_departure = $hour = $minute = $remarks = "";
                                }

                                $date = $this->time->DayFormatted($date_arr);

                            }else{
                                if($this->worker_model->displaySched($employeeid, $date_arr)->num_rows() == 0){
                                    $AM_arrival = $AM_departure = $PM_arrival = $PM_departure = $hour = $minute = "";
                                    $date = $this->time->DayFormatted($date_arr);
                                    $remarks = date("l", strtotime($date_arr));
                                }
                            }

                            $hour = $minute = 0;
                            $att_mirror.='

                            <tr>
                                <td class="tc">'.($this->time->DayFormatted($date_arr)).'</td>
                                <td class="tc">'.$AM_arrival.'</td>
                                <td class="tc">'.$AM_departure.'</td>
                                <td class="tc">'.$PM_arrival.'</td>
                                <td class="tc">'.$PM_departure.'</td>
                                <td class="tc">'.(($hour != 0) ? $hour : '').'</td>
                                <td class="tc">'.(($minute != 0) ? $minute : '').'</td>
                                <td class="tc">'.$remarks.'</td>
                            </tr>';

                        }

                   $att_mirror.=' 
                   <tr>
                                    <td style="font-size:18px;" colspan="5" style="text-align:right;">Total: </td>
                                    <td style="font-size:18px;"></td>
                                    <td style="font-size:18px;"></td>
                                    <td style="font-size:18px;"></td>
                                </tr>
                   </table>
                </div>

                <br>
                    <table class="regards" style="width:100%;margin-left:15px;">
                        <tr>
                            <td style="font-size:18px;">
                                I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
                            </td>
                        </tr>
                    </table>

                    <br>
                    <table class="regards" style="width:100%;margin-left:15px;text-align:center;">
                        <tr>
                            <td style="vertical-align:bottom;font-size:18px;">
                                <b><span>'.$fullname.'</span></b>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                ____________________________________________________________________________________
                            </td>
                        </tr>
                        <tr>
                            <td>
                                
                            </td>
                        </tr>
                        <tr>
                            <td style="font-size:18px;">
                                <i>VERIFIED as to the prescribed office hours :</i>
                            </td>
                        </tr>
                        <tr>
                            <td><span style="color:white;">BLANK SPACE</span></td>
                        </tr>
                    
                        <tr>
                            <td>
                                ____________________________________________________________________________________
                            </td>
                        </tr>
                        <tr>
                            <td style="font-size:18px;">
                                <i>In-charge</i>
                            </td>
                        </tr>
                    </table>
                
            </td>';
            $content = $content.$att_mirror.$att_mirror;

     $content.='
        </tr>
    </table>';
    
    echo $style.$content;
?>