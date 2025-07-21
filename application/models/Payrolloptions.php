<?php 
/**
 * @author Justin
 * @copyright 2015
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Payrolloptions extends CI_Model {
    
    /*
     *  DISPLAY WORK DAYS/HOURS
     */        
    function viewWorkingdays($param = ""){
        $return = "";
        $dars = array("1"=>"1 Day","2"=>"2 Days","3"=>"3 Days","4"=>"4 Days","5"=>"5 Days","6"=>"6 Days","7"=>"7 Days");
        foreach($dars as $val=>$desc){
            if($param == $val)
                $return .= "<option value='$val' selected>$desc</option>";
            else
                $return .= "<option value='$val'>$desc</option>"; 
        }
        return $return;
    }             
    function viewWorkHours($exemp = "",$param = ""){
        $return = "";
        if($exemp)  $return = "<option value=''>No Exemption</option>"; 
        for($x = 1; $x <= 24; $x++){
            if($param == $x)
                $return .= "<option value='$x' selected>$x ".(($x == 1) ? 'Hour' : 'Hours')."</option>";
            else
                $return .= "<option value='$x'>$x ".(($x == 1) ? 'Hour' : 'Hours')."</option>";
        }
        return $return;
    }
    function taxdependents($code = ""){
        if($code === "") $code = "S";
        $return = "<option value=''> Choose One.. </option>";
        $query = $this->db->query("SELECT * FROM code_tax_status")->result();
        foreach($query as $row){
            $row->status_code = Globals::_e($row->status_code);
            $row->status_desc = Globals::_e($row->status_desc);
            if($code == $row->status_code)
                $return .= "<option value='{$row->status_code}' selected>{$row->status_desc}</option>";
            else
                $return .= "<option value='{$row->status_code}'>{$row->status_desc}</option>";
        }
        return $return;
    }
    function incomebase($income = ""){
        $return = "";
        $query = $this->db->query("SELECT * FROM code_income_base")->result();
        foreach($query as $row){
            $return .= "<option value='{$row->income_base}'>{$row->description}</option>";
        }
        return $return;
    }

    /** @author Leandrei Santos
     * Save CutOff
     * HRIS_STTHRESE_LA_FIESTA
     * HRIS_STTHRESE_MAGDALO
     * HRIS_STTHRESE_TIGBAUAN
     * **/
    function saveCutoffForMultipleCampuses(
        $dfrom, $dto, $postedby, $dkey, $payrollsched, $payrollquarter,
        $payrolldfrom, $payrolldto, $confrmdfrom, $confrmdto, $user, $nodtr, $tfrom, $tto
    ) {
        $databases = [
            'HRIS_STTHRESE_LA_FIESTA',
            'HRIS_STTHRESE_MAGDALO',
            'HRIS_STTHRESE_TIGBAUAN'
        ];
    
        $return = ["err_code" => 0, "msg" => ''];
    
        if ($dkey) {
            foreach ($databases as $database) {
                $this->db->query("
                    UPDATE `{$database}`.cutoff 
                    SET 
                        CutoffTo = ?, 
                        CutoffFrom = ?, 
                        ConfirmFrom = ?, 
                        ConfirmTo = ?, 
                        TimeFrom = ?, 
                        TimeTo = ? 
                    WHERE ID = ?
                ", [$dto, $dfrom, $confrmdfrom, $confrmdto, $tfrom, $tto, $dkey]);
    
                $this->db->query("
                    UPDATE `{$database}`.payroll_cutoff_config 
                    SET
                        schedule = ?,
                        quarter = ?,
                        startdate = ?,
                        enddate = ?,
                        nodtr = ?
                    WHERE baseid = ?
                ", [$payrollsched, $payrollquarter, $payrolldfrom, $payrolldto, $nodtr, $dkey]);
            }
            return ["err_code" => 0, "msg" => 'Successfully updated!'];
        }
    
        $isCutoffExist = 0;
        foreach ($databases as $database) {
            $query = $this->db->query("
                SELECT * 
                FROM `{$database}`.payroll_cutoff_config 
                WHERE 
                    (? BETWEEN startdate AND enddate OR ? BETWEEN startdate AND enddate) 
                    AND schedule = ?
            ", [$payrolldfrom, $payrolldto, $payrollsched]);
    
            $isCutoffExist += $query->num_rows();
        }
    
        if ($isCutoffExist > 0) {
            return ["err_code" => 2, "msg" => ucfirst($payrollsched) . " Cut-off Already Exists!"];
        }

        foreach ($databases as $database) {
            $this->db->query("
                INSERT INTO `{$database}`.cutoff 
                    (CutoffFrom, CutoffTo, ConfirmFrom, ConfirmTo, TimeFrom, TimeTo, PostedBy) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [$dfrom, $dto, $confrmdfrom, $confrmdto, $tfrom, $tto, $postedby]);
    
            if (!$this->db->affected_rows()) {
                return ["err_code" => 2, "msg" => 'Unable to save cutoff!'];
            }
        }
    
        $cutoffID = $this->db->insert_id();

        $databases = [
            'HRIS_STTHRESE_LA_FIESTA',
            'HRIS_STTHRESE_MAGDALO',
            'HRIS_STTHRESE_TIGBAUAN'
        ];
    
        foreach ($databases as $database) {
            $this->db->query("
                INSERT INTO `{$database}`.payroll_cutoff_config 
                    (baseid, schedule, quarter, startdate, enddate, confrmdate, confrmend, addedby, nodtr) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [$cutoffID, $payrollsched, $payrollquarter, $payrolldfrom, $payrolldto, $confrmdfrom, $confrmdto, $user, $nodtr]);
                
            if (!$this->db->affected_rows()) {
                return ["err_code" => 2, "msg" => 'Unable to save payroll cutoff configuration!'];
            }
        }
    
        return ["err_code" => 0, "msg" => 'Successfully saved!'];
    }    

    //CUT OFF SET-UP (Glen Mark)
    function cutoffsetup($dfrom,$dto,$postedby,$dkey,$payrollsched,$payrollquarter,$payrolldfrom,$payrolldto,$confrmdfrom,$confrmdto,$user,$nodtr,$tfrom,$tto)
    {
        $return = array("err_code"=>0,"msg"=>'');
        if ($dkey){
                $this->db->query("Update cutoff SET CutoffTo='{$dto}',CutoffFrom='{$dfrom}', ConfirmFrom='{$confrmdfrom}', ConfirmTo='{$confrmdto}', TimeFrom='{$tfrom}', TimeTo='{$tto}'  WHERE ID='{$dkey}'");
                // echo $this->db->last_query(); die;
                $this->db->query("Update payroll_cutoff_config SET schedule='{$payrollsched}',quarter='{$payrollquarter}',startdate='{$payrolldfrom}',enddate='{$payrolldto}',nodtr='{$nodtr}' WHERE baseid='{$dkey}'");
                // echo $this->db->last_query(); die;
                $return = array("err_code"=>0,"msg"=>'Successfully updated!');
        }
        else
        {
             $query  =   $this->db->query("SELECT * FROM payroll_cutoff_config where ('$payrolldfrom' BETWEEN startdate AND enddate OR '$payrolldto' BETWEEN startdate and enddate) AND schedule='$payrollsched'");
            if($query->num_rows() == 0){
               
                $insert1 = $this->db->query("INSERT INTO cutoff(CutoffFrom,CutoffTo,ConfirmFrom, ConfirmTo, TimeFrom, TimeTo, PostedBy) VALUES('{$dfrom}','{$dto}','{$confrmdfrom}','{$confrmdto}','{$tfrom}','{$tto}','{$postedby}')");
                $cutoffID = $this->db->insert_id();
             
                if ($insert1) {
                   $insert2 =$this->db->query("INSERT INTO payroll_cutoff_config (baseid,schedule,quarter,startdate,enddate,confrmdate,confrmend,addedby,nodtr) VALUES ('$cutoffID','$payrollsched','$payrollquarter','$payrolldfrom','$payrolldto','{$confrmdfrom}','{$confrmdto}','$user','$nodtr')");
                   if ($insert2) {
                     $return = array("err_code"=>0,"msg"=>'Successfully Saved!');    
                   }
                   else
                   {
                    $return = array("err_code"=>2,"msg"=>'Unabled to save!');
                   }
                    
                }
                else
                {
                    $return = array("err_code"=>2,"msg"=>'Unabled to save!');
                }
                
               
            }
            else
                $return = array("err_code"=>2,"msg"=>ucwords($payrollsched)." Cut-off Already Exists!.");  
        }

         return $return;
    }


    /*
     *  DISPLAY WORK TYPE AND QUARTERS Options
     */ 
    function payschedulenoweekly($scheduleval = "",$showAll=true){
        $return = "<option value=''>Select Schedule</option>";
        if($showAll) $schedule = array("semimonthly"=>"Semi-Monthly","monthly"=>"Monthly");
        else $schedule = array("semimonthly"=>"Semi-Monthly");
        foreach($schedule as $schedule=>$desc){
            $schedule = Globals::_e($schedule);
            $desc = Globals::_e($desc);
            if($scheduleval == $schedule)
                $return .= "<option value='$schedule' selected>$desc</option>";
            else
                $return .= "<option value='$schedule'>$desc</option>";
        }
        return $return;
    }            
    function payschedule($scheduleval = "",$showAll=true){
        $return = "<option value=''>Select Schedule</option>";
        if($showAll) $schedule = array("semimonthly"=>"Semi-Monthly","monthly"=>"Monthly");
        else $schedule = array("semimonthly"=>"Semi-Monthly");
        foreach($schedule as $schedule=>$desc){
            $schedule = Globals::_e($schedule);
            $desc = Globals::_e($desc);
            if($scheduleval == $schedule)
                $return .= "<option value='$schedule' selected>$desc</option>";
            else
                $return .= "<option value='$schedule'>$desc</option>";
        }
        return $return;
    }
    function payscheduledesc($key = ""){
        $return = "";
        $schedule = array("weekly"=>"Weekly","semimonthly"=>"Semi-Monthly","monthly"=>"Monthly");
        foreach($schedule as $val=>$desc){
            if($key == $val)    $return = $desc;
        } 
        return Globals::_e($return);                  
    }
    function cutoffconfigquarter($quarterval = "",$visible = FALSE, $schedule = "", $isDisplaySelectQuarter = false){
        $return = "";
        if($schedule == 'weekly'){
            $quarter = array("0"=>"- Select -","1"=>"1st Cut-Off","2"=>"2nd Cut-Off");
        }else if($schedule == 'semimonthly'){
        if($visible)
            $quarter = array("0"=>"- Select -","1"=>"1st Cut-Off","2"=>"2nd Cut-Off");
        else
            $quarter = array("0"=>"- Select -","1"=>"1st Cut-Off","2"=>"2nd Cut-Off");
        }else if($schedule == 'monthly'){
            $quarter = array("0"=>"- Select -","3"=>"All Cut-Off");
        }else{
            $quarter = array("0"=>"- Select -","1"=>"1st Cut-Off","2"=>"2nd Cut-Off");
        }

        if($isDisplaySelectQuarter) $return .= "<option value='0' selected>Select a Quarter</option>";

        foreach($quarter as $quarter=>$desc){
            if($quarterval == $quarter)
                $return .= "<option value='$quarter' selected>$desc</option>";
            else
                $return .= "<option value='$quarter'>$desc</option>";
        }
        return $return;
    }
    function quarter($quarterval = "",$visible = FALSE, $schedule = "", $isDisplaySelectQuarter = false){
        $return = "";
        if($schedule == 'weekly'){
            $quarter = array("0"=>"- Select -","1"=>"1st Cut-Off","2"=>"2nd Cut-Off");
        }else if($schedule == 'semimonthly'){
        if($visible)
            $quarter = array("0"=>"- Select -","1"=>"1st Cut-Off","2"=>"2nd Cut-Off","3"=>"All Cut-Off");
        else
            $quarter = array("0"=>"- Select -","1"=>"1st Cut-Off","2"=>"2nd Cut-Off","3"=>"All Cut-Off");
        }else if($schedule == 'monthly'){
            $quarter = array("0"=>"- Select -","3"=>"All Cut-Off");
        }else{
            $quarter = array("0"=>"- Select -","1"=>"1st Cut-Off","2"=>"2nd Cut-Off");
        }

        if($isDisplaySelectQuarter) $return .= "<option value='0' selected>Select a Quarter</option>";

        foreach($quarter as $quarter=>$desc){
            if($quarterval == $quarter)
                $return .= "<option value='$quarter' selected>$desc</option>";
            else
                $return .= "<option value='$quarter'>$desc</option>";
        }
        return $return;
    }
    function quarterdesc($key = "",$visible = FALSE,$schedule = ""){
        $return = "";
        if($schedule == 'weekly'){
            $quarter = array("1"=>"1st Cut-Off","2"=>"2nd Cut-Off","3"=>"Third Cut-Off","4"=>"Fourth Cut-Off");
        }else if($schedule == 'semimonthly'){
        if($visible)
            $quarter = array("1"=>"1st Cut-Off","2"=>"2nd Cut-Off");
        else
            $quarter = array("1"=>"1st Cut-Off","2"=>"2nd Cut-Off","3"=>"All Cut-Off");
        }else if($schedule == 'monthly'){
            $quarter = array("1"=>"Whole Cut-Off");
        }else    
            $quarter = array(""=>"No Data..");
            
        foreach($quarter as $val=>$desc){
            if($key == $val)  $return = $desc;
        } 
        return Globals::_e($return);                  
    }    
    
    /*
     * Display Payroll Options with Query
     */
     
     // deduction
    function deduction($deduction = ""){
        $return = "<option value=''>- Select Deduction -</option>";
        $query = $this->db->query("SELECT * FROM payroll_deduction_config");
        foreach($query->result() as $row){
            if($deduction == $row->id)
                $return .= "<option value='".$row->id."' selected>".$row->description."</option>";
            else
                $return .= "<option value='".$row->id."'>".$row->description."</option>";
        }
        return $return;
    }
    // income
    function income($income = "",$addSalary=FALSE){
        $return = "<option value=''>- Select Income -</option>";

        if($addSalary){
            if($income == "SALARY")
                $return .= "<option value='SALARY' selected>SALARY</option>";
            else
                $return .= "<option value='SALARY'>SALARY</option>";
        }
        
        $query = $this->db->query("SELECT * FROM payroll_income_config");
        foreach($query->result() as $row){
            if($income == $row->id)
                $return .= "<option value='".Globals::_e($row->id)."' selected>".Globals::_e($row->description)."</option>";
            else
                $return .= "<option value='".Globals::_e($row->id)."'>".Globals::_e($row->description)."</option>";
        }
        return $return;
    }
    // loan
    function loan($loan = ""){
        $return = "<option value=''>- Select Loan -</option>";
        $query = $this->db->query("SELECT * FROM payroll_loan_config");
        foreach($query->result() as $row){
            if($loan == $row->id)
                $return .= "<option value='".$row->id."' selected>".$row->description."</option>";
            else
                $return .= "<option value='".$row->id."'>".$row->description."</option>";
        }
        return $return;
    }
    // other income
    function incomeoth($income = ""){
        $return = "<option value=''>- Select Income -</option>";
        $query = $this->db->query("SELECT * FROM payroll_income_oth_config");
        foreach($query->result() as $row){
            if($income == $row->id)
                $return .= "<option value='".$row->id."' selected>".$row->description."</option>";
            else
                $return .= "<option value='".$row->id."'>".$row->description."</option>";
        }
        return $return;
    }
    
    // deduction desc
    function deductiondesc($deduction = ""){
        $query = $this->db->query("SELECT * FROM payroll_deduction_config WHERE id='$deduction'");
        if($query->num_rows() > 0 ) return Globals::_e($query->row(0)->description);
        else return false;
    }
    // income desc
    function incomedesc($income = ""){
        $desc = "";
        $query = $this->db->query("SELECT * FROM payroll_income_config WHERE id='$income'");
        if ($query->num_rows() > 0) {
            $desc = $query->row(0)->description; 
        }
        return Globals::_e($desc);
    }
    // loan desc
    function loandesc($loan = ""){
        $return = "";
        $query = $this->db->query("SELECT * FROM payroll_loan_config WHERE id='$loan'");
        if ($query->num_rows() > 0) {
            $return = $query->row(0)->description;
        }
        return $return;
    }
    // Other income desc
    function incomedescoth($income = ""){
        $query = $this->db->query("SELECT * FROM payroll_income_oth_config WHERE id='$income'");
        return $query->row(0)->description;
    }
    function taxable($taxable = ""){
        $return = "";
        $tax = array(""=>"- Select -","withtax"=>"Taxable","notax"=>"Non-Taxable");
        foreach($tax as $key=>$desc){
            if($taxable == $key)
                $return .= "<option value='$key' selected>$desc</option>";
            else
                $return .= "<option value='$key'>$desc</option>";
        }
        return $return;
    }
    // finalized cut-off (used in payroll->process dtr)
    function displaycutofffinalized($type = "teaching", $data = array()){
        $return = "<option value=''>Select DTR Cut-Off Dates</option>";
        if($type == "teaching" || $type == "trelated")
            //$query = $this->db->query("SELECT cutoffstart,cutoffend FROM attendance_confirmed WHERE (`status`='SUBMITTED' OR `status`='PROCESSED' ) GROUP BY cutoffstart DESC,cutoffend DESC")->result();
            $query = $this->db->query("SELECT a.`ID`,a.`CutoffFrom`,a.`CutoffTo`,a.`TPostedDate`,a.`NTPostedDate`,b.`schedule`,b.`quarter`,b.`startdate`,b.`enddate`,a.`ConfirmFrom`,a.`ConfirmTo` FROM cutoff AS a LEFT JOIN payroll_cutoff_config b ON(a.`ID` = b.`baseid`)  ORDER BY a.`CutOffFrom` DESC")->result();
        else
            //$query = $this->db->query("SELECT cutoffstart,cutoffend FROM attendance_confirmed_nt WHERE (`status`='SUBMITTED' OR `status`='PROCESSED' ) GROUP BY cutoffstart DESC,cutoffend DESC")->result();
            $query = $this->db->query("SELECT a.`ID`,a.`CutoffFrom`,a.`CutoffTo`,a.`TPostedDate`,a.`NTPostedDate`,b.`schedule`,b.`quarter`,b.`startdate`,b.`enddate`,a.`ConfirmFrom`,a.`ConfirmTo` FROM cutoff AS a LEFT JOIN payroll_cutoff_config b ON(a.`ID` = b.`baseid`)  ORDER BY a.`CutOffFrom` DESC")->result();

        $firstLoad = isset($data["firstLoad"]) ? $data["firstLoad"] : false;
        foreach($query as $data){
            $selected = "";
            if($firstLoad){
                $datenow = date("Y-m-d", strtotime($this->extensions->getServerTime()));
                if (($datenow >= $data->CutoffFrom) && ($datenow <= $data->CutoffTo)){
                    $selected = " selected";
                }
            }
            $return .= "<option value='{$data->CutoffFrom},{$data->CutoffTo}' {$selected}>".date("F d, Y",strtotime($data->CutoffFrom))." - ".date("F d, Y",strtotime($data->CutoffTo))."</option>";
        }
        return $return;
    }

    function displaycutofffinalizedSaving($type = "teaching", $datas = array()){
        //from dtr to payroll cutoff
        $payrollcutoff = $this->extensions->getPayrollCutoffConfigArr($datas['payroll_cutoffstart'], $datas['payroll_cutoffend']);
        $return = "<option value=''>Select Payroll Cut-Off Dates</option>";
        if($type == "teaching" || $type == "trelated")
            $query = $this->db->query("SELECT 
                                        CutoffFrom AS cutoffstart,
                                        CutoffTo AS cutoffend,
                                        startdate AS payroll_cutoffstart,
                                        enddate AS payroll_cutoffend 
                                        FROM
                                        cutoff a 
                                        INNER JOIN payroll_cutoff_config b 
                                            ON a.id = b.baseid 
                                        WHERE startdate IN 
                                        (SELECT 
                                            payroll_cutoffstart 
                                        FROM
                                            attendance_confirmed
                                        WHERE STATUS = 'SUBMITTED' 
                                            OR STATUS = 'PROCESSED')")->result();
        else
            $query = $this->db->query("SELECT 
                                        CutoffFrom AS cutoffstart,
                                        CutoffTo AS cutoffend,
                                        startdate AS payroll_cutoffstart,
                                        enddate AS payroll_cutoffend 
                                        FROM
                                        cutoff a 
                                        INNER JOIN payroll_cutoff_config b 
                                            ON a.id = b.baseid 
                                        WHERE startdate IN 
                                        (SELECT 
                                            payroll_cutoffstart 
                                        FROM
                                            attendance_confirmed_nt 
                                        WHERE STATUS = 'SUBMITTED' 
                                            OR STATUS = 'PROCESSED')")->result();
        foreach($query as $data){
            $selected = "";
            if (($payrollcutoff[0] == $data->payroll_cutoffstart) && ($payrollcutoff[1] == $data->payroll_cutoffend)){
                $selected = " selected";
            }//add this condition for selected 
            $return .= "<option value='{$data->payroll_cutoffstart} {$data->payroll_cutoffend}' {$selected} >".date("F d, Y",strtotime($data->payroll_cutoffstart))." - ".date("F d, Y",strtotime($data->payroll_cutoffend))."</option>";
        }
        return $return;
    }

    // Payroll cut-off (used in payroll->payroll)
    function displaypayrollcutoff($sched = "", $data = array()){
        $firstLoad = isset($data["firstLoad"]) ? $data["firstLoad"] : false;
        $date_list = '';
        $cutoffList_arr = $cutoffList_arr2 = array();
        $getCutoffList = $this->db->query("SELECT DISTINCT payroll_cutoffstart FROM attendance_confirmed WHERE status = 'PROCESSED' ");
        if($getCutoffList->num_rows() > 0) $cutoffList_arr = $getCutoffList->result_array();
        $getCutoffList2 = $this->db->query("SELECT DISTINCT payroll_cutoffstart FROM attendance_confirmed_nt WHERE status = 'PROCESSED' ");
        if($getCutoffList2->num_rows() > 0) $cutoffList_arr2 = $getCutoffList2->result_array();
        $getCutoffList = array_merge($cutoffList_arr, $cutoffList_arr2);
        foreach($getCutoffList as $key => $value){
            if($date_list) $date_list .= ",".$value['payroll_cutoffstart'];
            else $date_list .= $value['payroll_cutoffstart']; 
        }
        $whereClause = "";
        $return = "<option value=''>Select Payroll Cut-Off Date</option>";
        if($sched)  $whereClause = " AND schedule='$sched'";
        $query = $this->db->query("SELECT CONCAT(startdate,' ',enddate,' ',quarter) as cutoffdate, CONCAT(DATE_FORMAT(startdate,'%M %e, %Y'),' - ',DATE_FORMAT(enddate,'%M %e, %Y')) as cutoffdatedisplay, startdate, enddate FROM payroll_cutoff_config a WHERE FIND_IN_SET(startdate,'$date_list') $whereClause  ORDER BY startdate DESC")->result();
        foreach($query as $data){
            $selected = "";
            if($firstLoad){
                $datenow = date("Y-m-d", strtotime($this->extensions->getServerTime()));
                if (($datenow >= $data->startdate) && ($datenow <= $data->enddate)){
                    $selected = " selected";
                }
            }
            $return .= "<option value='".$data->cutoffdate."' {$selected} >".$data->cutoffdatedisplay."</option>";
        }
        return $return;
    }

    //for other income cutoffs
    public function payrollcutoffPerSched($sched = "", $data = array()){
        $firstLoad = isset($data["firstLoad"]) ? $data["firstLoad"] : false;
        $return = "<option value=''>Select Payroll Cut-Off Date</option>";
        if($sched)  $whereClause = " AND schedule='$sched'";
        $query = $this->db->query("SELECT CONCAT(startdate,' ',enddate,' ',quarter) as cutoffdate, CONCAT(DATE_FORMAT(startdate,'%M %e, %Y'),' - ',DATE_FORMAT(enddate,'%M %e, %Y')) as cutoffdatedisplay, startdate, enddate FROM payroll_cutoff_config a WHERE 1 $whereClause GROUP BY startdate, enddate ORDER BY startdate DESC")->result();
        foreach($query as $data){
            $selected = "";
            if($firstLoad){
                $datenow = date("Y-m-d", strtotime($this->extensions->getServerTime()));
                if (($datenow >= $data->startdate) && ($datenow <= $data->enddate)){
                    $selected = " selected";
                }
            }
            $return .= "<option value='".$data->cutoffdate."' {$selected} >".$data->cutoffdatedisplay."</option>";
        }
        return $return;
    }

    // Payroll cut-off (used in payroll reports on hold employees)
    function displayOnholdCutoff($sched = ""){
        $date_list = '';
        $cutoffList_arr = $cutoffList_arr2 = array();
        $getCutoffList = $this->db->query("SELECT DISTINCT payroll_cutoffstart FROM attendance_confirmed");
        if($getCutoffList->num_rows() > 0) $cutoffList_arr = $getCutoffList->result_array();
        $getCutoffList2 = $this->db->query("SELECT DISTINCT payroll_cutoffstart FROM attendance_confirmed_nt");
        if($getCutoffList2->num_rows() > 0) $cutoffList_arr2 = $getCutoffList2->result_array();
        $getCutoffList = array_merge($cutoffList_arr, $cutoffList_arr2);
        foreach($getCutoffList as $key => $value){
            if($date_list) $date_list .= ",".$value['payroll_cutoffstart'];
            else $date_list .= $value['payroll_cutoffstart']; 
        }
        $whereClause = "";
        $return = "<option value=''>Select Payroll Cut-Off Date</option>";
        if($sched)  $whereClause = " AND schedule='$sched'";
        $query = $this->db->query("SELECT CONCAT(startdate,' ',enddate,' ',quarter) as cutoffdate, CONCAT(DATE_FORMAT(startdate,'%M %e, %Y'),' - ',DATE_FORMAT(enddate,'%M %e, %Y')) as cutoffdatedisplay FROM payroll_cutoff_config a WHERE FIND_IN_SET(startdate,'$date_list') $whereClause  ORDER BY startdate DESC")->result();
        foreach($query as $data){
            $return .= "<option value='".$data->cutoffdate."'>".$data->cutoffdatedisplay."</option>";
        }
        return $return;
    }

     function displaypayrollcutoffmonthrangepayslip($sched = "", $monthfrom="", $monthto="", $year="", $quarter=""){
        $date_list = '';
        $cutoffList_arr = $cutoffList_arr2 = array();
        $month_list = "'".implode("','", $monthfrom). "'";
        $getCutoffList = $this->db->query("SELECT DISTINCT payroll_cutoffstart FROM attendance_confirmed WHERE status = 'PROCESSED' ");
        if($getCutoffList->num_rows() > 0) $cutoffList_arr = $getCutoffList->result_array();
        $getCutoffList2 = $this->db->query("SELECT DISTINCT payroll_cutoffstart FROM attendance_confirmed_nt WHERE status = 'PROCESSED' ");
        if($getCutoffList2->num_rows() > 0) $cutoffList_arr2 = $getCutoffList2->result_array();
        $getCutoffList = array_merge($cutoffList_arr, $cutoffList_arr2);
        foreach($getCutoffList as $key => $value){
            if($date_list) $date_list .= ",".$value['payroll_cutoffstart'];
            else $date_list .= $value['payroll_cutoffstart']; 
        }
        $whereClause = "";
        if($sched)  $whereClause = " AND schedule='$sched'";
        // $date_from = $year."-".$monthfrom."-01";
        // $date_to = $year."-".$monthto."-31";
        if($month_list)  $whereClause .= " AND (DATE_FORMAT(startdate, '%m') IN ($month_list) AND DATE_FORMAT(enddate, '%m') IN ($month_list))";
        if($year)  $whereClause .= " AND (DATE_FORMAT(startdate, '%Y') = '$year' AND DATE_FORMAT(enddate, '%Y') = '$year')";
        if($quarter && $quarter != 3) $whereClause .= "AND quarter = '$quarter'";
        return $this->db->query("SELECT CONCAT(startdate,' ',enddate,' ',quarter) as cutoffdate, CONCAT(DATE_FORMAT(startdate,'%M %e, %Y'),' - ',DATE_FORMAT(enddate,'%M %e, %Y')) as cutoffdatedisplay, a.* FROM payroll_cutoff_config a WHERE FIND_IN_SET(startdate,'$date_list') $whereClause  ORDER BY startdate DESC")->result();
        
    }

    function getDtrPayrollCutoffPair($dtr_start='',$dtr_end='',$payroll_start='',$payroll_end='',$dtr_id='',$p_id=''){
        $wC = $payroll_quarter = $payroll_sched = '';

        if($dtr_start && $dtr_end) $wC .= " WHERE a.CutoffFrom='$dtr_start' AND a.CutoffTo='$dtr_end'";
        elseif($payroll_start && $payroll_end) $wC .= " WHERE b.startdate='$payroll_start' AND b.enddate='$payroll_end'";
        elseif($dtr_id) $wC .= " WHERE a.ID='$dtr_id'";
        elseif($p_id) $wC   .= " WHERE b.id='$p_id'";

        $p_cutoff = $this->db->query("SELECT a.CutoffFrom, a.CutoffTo, b.startdate, b.enddate, b.quarter, b.schedule
                                        FROM cutoff a
                                        LEFT JOIN payroll_cutoff_config b ON b.`baseid`=a.`ID` 
                                        $wC");

        if($p_cutoff->num_rows() > 0){
          $dtr_start = $p_cutoff->row(0)->CutoffFrom;
          $dtr_end = $p_cutoff->row(0)->CutoffTo;
          $payroll_start = $p_cutoff->row(0)->startdate;
          $payroll_end = $p_cutoff->row(0)->enddate;
          $payroll_quarter = $p_cutoff->row(0)->quarter;
          $payroll_sched = $p_cutoff->row(0)->schedule;
        }

        return array($dtr_start,$dtr_end,$payroll_start,$payroll_end,$payroll_quarter,$payroll_sched);
    }

    function getDtrPayrollCutoffID($dtr_start='',$dtr_end='',$payroll_start='',$payroll_end=''){
        $wC = $cutoff_id = '';
        if($dtr_start && $dtr_end) $wC .= " WHERE a.CutoffFrom='$dtr_start' AND a.CutoffTo='$dtr_end'";
        if($payroll_start && $payroll_end) $wC .= " WHERE b.startdate='$payroll_start' AND b.enddate='$payroll_end'";

        $p_cutoff = $this->db->query("SELECT a.ID
                                        FROM cutoff a
                                        LEFT JOIN payroll_cutoff_config b ON b.`baseid`=a.`ID` 
                                        $wC");

        if($p_cutoff->num_rows() > 0){
          $cutoff_id = $p_cutoff->row(0)->ID;
        }

        return $cutoff_id;
    }

    function getPayrollCutoffId($sdate='',$edate=''){
        $payroll_cutoff_id = '';
        $p_q = $this->db->query("SELECT id FROM payroll_cutoff_config WHERE startdate='$sdate' AND enddate='$edate'");
        if($p_q->num_rows() > 0) $payroll_cutoff_id = $p_q->row(0)->id;
        return $payroll_cutoff_id;
    }

    function getPayrollCutoffBaseId($sdate='',$edate=''){
        $payroll_cutoff_id = '';
        $p_q = $this->db->query("SELECT baseid FROM payroll_cutoff_config WHERE startdate='$sdate' AND enddate='$edate'");
        if($p_q->num_rows() > 0) $payroll_cutoff_id = $p_q->row(0)->baseid;
        return $payroll_cutoff_id;
    }
      
    function displaypayrollcutoffdata($sched = "",$data = ""){
        $whereClause = "";
        $firstLoad = isset($data["firstLoad"]) ? $data["firstLoad"] : false;
        $eid = isset($data['eid']) ? $data['eid'] : "";
        $return = "<option value=''>- Select Payroll Cut-Off Date -</option>";
        $payroll_cutoff_id = isset($data['payroll_cutoff']) ? $data['payroll_cutoff'] : "";
        if($sched)  $whereClause = " AND schedule='$sched'";
        if($eid) $whereClause.= " AND employeeid='$eid'";

        $query = $this->db->query("SELECT CONCAT(cutoffstart,' ',cutoffend,' ',quarter) as cutoffdate, CONCAT(DATE_FORMAT(cutoffstart,'%M %e, %Y'),' - ',DATE_FORMAT(cutoffend,'%M %e, %Y')) as cutoffdatedisplay, cutoffstart, cutoffend 
                                    FROM payroll_computed_table a 
                                    WHERE a.status = 'PROCESSED'
                                    $whereClause GROUP BY cutoffstart DESC,cutoffend DESC")->result();
        foreach($query as $data){
            $selected = "";
            if($firstLoad){
                $datenow = date("Y-m-d", strtotime($this->extensions->getServerTime()));
                if (($datenow >= $data->cutoffstart) && ($datenow <= $data->cutoffend)){
                    $selected = " selected";
                }
            }
            $return .= "<option value='".$data->cutoffdate."' ".($payroll_cutoff_id == $data->cutoffdate ? 'selected' : '')." {$selected}>".$data->cutoffdatedisplay."</option>";
        }
        return $return;
    }
    function displaypayrollcutoffdatasub($sched = "",$data = ""){
        $whereClause = "";
        $firstLoad = isset($data["firstLoad"]) ? $data["firstLoad"] : false;
        $eid = isset($data['eid']) ? $data['eid'] : "";
        $return = "<option value=''>- Select Payroll Cut-Off Date -</option>";
        $payroll_cutoff_id = isset($data['payroll_cutoff']) ? $data['payroll_cutoff'] : "";
        if($sched)  $whereClause = " AND schedule='$sched'";
        if($eid) $whereClause.= " AND employeeid='$eid'";

        $query = $this->db->query("SELECT CONCAT(cutoffstart,' ',cutoffend,' ',quarter) as cutoffdate, CONCAT(DATE_FORMAT(cutoffstart,'%M %e, %Y'),' - ',DATE_FORMAT(cutoffend,'%M %e, %Y')) as cutoffdatedisplay, cutoffstart, cutoffend 
                                    FROM sub_payroll_computed_table a 
                                    WHERE a.status = 'PROCESSED'
                                    $whereClause GROUP BY cutoffstart DESC,cutoffend DESC")->result();
        foreach($query as $data){
            $selected = "";
            if($firstLoad){
                $datenow = date("Y-m-d", strtotime($this->extensions->getServerTime()));
                if (($datenow >= $data->cutoffstart) && ($datenow <= $data->cutoffend)){
                    $selected = " selected";
                }
            }
            $return .= "<option value='".$data->cutoffdate."' ".($payroll_cutoff_id == $data->cutoffdate ? 'selected' : '')." {$selected}>".$data->cutoffdatedisplay."</option>";
        }
        return $return;
    }

    // Available Quarters Set from config
    function quarterpayroll($data,$visible = FALSE,$sched = ""){
        $return = "<option value=''>Select Quarter</option>";
        $whereClause = "";
        $cutoffdate = explode(' ',$data['cutoffdate']);
        $sdate = $cutoffdate[0];
        $edate = $cutoffdate[1];
        function quarterdesc($sched = "",$quarter = ""){
            switch($sched){
                case "weekly"   :
                        switch($quarter){
                            case    "1" :  return "1st Cut-Off";break;
                            case    "2" :  return "2nd Cut-Off";break;
                            case    "3" :  return "3rd Cut-Off";break;
                            case    "4" :  return "4th Cut-Off";break;
                        }break;
                case "semimonthly"  :   
                        switch($quarter){
                            case    "1" :  return "1st Cut-Off";break;
                            case    "2" :  return "2nd Cut-Off";break;
                            case    "3" :  return "All Cut-Off";break;
                            case    "0" :  return "All Cut-Off";break;
                        }break;
                default :
                        switch($quarter){
                            case    "3" :  return "Whole Cut-Off";break;
                        }break;
          }
        }
        if(isset($sdate)) $whereClause = " AND startdate='{$sdate}' AND enddate='{$edate}'";
        $query = $this->db->query("SELECT quarter FROM payroll_cutoff_config where schedule='$sched' $whereClause GROUP BY quarter ORDER BY QUARTER ASC")->result();
        // echo $this->db->last_query();die;
        foreach($query as $row){
                $return .= "<option value='".$row->quarter."' ".((count($query) >= 1) ? ' selected' : '')." >".quarterdesc($sched,$row->quarter)."</option>";
        }
        return $return;
    }
    function viewtaxpercutoff($cutoff = ""){
        $return = "<option value=''>- Select Cut-Off -</option>";
        $query = $this->db->query("SELECT DISTINCT cutoffstart, cutoffend FROM payroll_computed_table")->result();
        foreach($query as $row){
            $return .= "<option value='".$row->cutoffstart."*".$row->cutoffend."'>".date('F d, Y',strtotime($row->cutoffstart))." - ".date('F d, Y',strtotime($row->cutoffend))."</option>";
        }
        return $return;
    }

    function getBankListSelect($bank=''){
        $return = "<option value=''>Select Bank</option>";
        $res = $this->payroll->displayBankList($bank);
        if($res->num_rows() > 0){
            foreach($res->result() as $key => $row){
                $return .= "<option value='".Globals::_e($row->code)."'>".Globals::_e($row->bank_name)."</option>";
            }
        }
        return $return;
    }
   
   /*
    * DISPLAY TABLE HEADERS/BODY FOR PAYROLL 
    */     
   function incometitle($eid = "",$title = "",$schedule = "",$quarter = "", $colname = "",$sdate = "",$edate = ""){
        $whereClause = "";
        if($eid){$whereClause   = " AND employeeid='$eid'";}        
        if($schedule){
            $whereClause .= " AND schedule='$schedule'";
            if($schedule == "semimonthly"){
                if($quarter)  $whereClause .= " AND FIND_IN_SET(cutoff_period,'$quarter,3')";
            }else             $whereClause .= " AND cutoff_period='$quarter'";  
        }
        if($colname)    $whereClause .= " AND code_income='$colname'";
        if($sdate && $edate)    $whereClause  .= " AND ((datefrom BETWEEN '$sdate' AND '$edate') OR (datefrom <= '$sdate')) AND datefrom <> '0000-00-00'  ";
        $query = $this->db->query("SELECT IFNULL($title,0) as title, code_income FROM employee_income INNER JOIN payroll_income_config b ON (b.id = code_income)  WHERE code_income <> '' $whereClause AND nocutoff > 0 GROUP BY code_income");
        return $query;
   }

   function getEmpIncomeAdj($eid = "",$title = "",$schedule = "",$quarter = "", $colname = "",$sdate = "",$edate = ""){
        $whereClause = "";
        if($eid){$whereClause   = " AND employeeid='$eid'";}        
        if($schedule){
            $whereClause .= " AND schedule='$schedule'";
            if($schedule == "semimonthly"){
                if($quarter)  $whereClause .= " AND FIND_IN_SET(cutoff_period,'$quarter,3')";
            }else             $whereClause .= " AND cutoff_period='$quarter'";  
        }
        if($colname)    $whereClause .= " AND code_income='$colname'";
        if($sdate && $edate)    $whereClause  .= " AND ((datefrom BETWEEN '$sdate' AND '$edate') OR (datefrom <= '$sdate')) AND datefrom <> '0000-00-00'  ";
        $query = $this->db->query("SELECT IFNULL($title,0) as title, code_income, a.deduct, a.taxable FROM employee_income_adj a INNER JOIN payroll_income_config b ON (b.id = a.code_income)  WHERE a.code_income <> '' $whereClause AND nocutoff > 0 GROUP BY code_income");
        return $query;
   }

   function getEmpIncomeAdjSalary($eid = "",$title = "",$schedule = "",$quarter = "", $colname = "",$sdate = "",$edate = ""){
        $whereClause = "";
        if($eid){$whereClause   = " AND employeeid='$eid'";}        
        if($schedule){
            $whereClause .= " AND schedule='$schedule'";
            if($schedule == "semimonthly"){
                if($quarter)  $whereClause .= " AND FIND_IN_SET(cutoff_period,'$quarter,3')";
            }else             $whereClause .= " AND cutoff_period='$quarter'";  
        }
        if($colname)    $whereClause .= " AND code_income='$colname'";
        if($sdate && $edate)    $whereClause  .= " AND ((datefrom BETWEEN '$sdate' AND '$edate') OR (datefrom <= '$sdate')) AND datefrom <> '0000-00-00'  ";
        $query = $this->db->query("SELECT IFNULL($title,0) as title, code_income, deduct, taxable FROM employee_income_adj WHERE code_income <> '' $whereClause AND nocutoff > 0 GROUP BY code_income");
        return $query;
   }

   function deducttitle($eid = "",$title = "",$visible = "",$schedule = "",$quarter = "",$colname = ""){
        $whereClause = " AND visibility='$visible'";
        if($eid)     $whereClause = " AND employeeid='$eid' AND visibility='$visible'";
        if($schedule){
            $whereClause .= " AND schedule='$schedule'";
            if($schedule == "semimonthly"){
                if($quarter)  $whereClause .= " AND FIND_IN_SET(cutoff_period,'$quarter,3')";
            }else             $whereClause .= " AND cutoff_period='$quarter'";  
        }
        if($visible == "SHOW")  $whereClause .= " AND nocutoff > 0";
        if($colname)    $whereClause .= " AND code_deduction='$colname'";
        $query = $this->db->query("SELECT IFNULL($title,0) as title, code_deduction FROM employee_deduction  INNER JOIN payroll_deduction_config b ON(b.id = code_deduction) WHERE code_deduction <> '' $whereClause GROUP BY code_deduction");
        return $query;
   }

   function checkIdnumber($empid='', $code=''){
        $idnum = "emp_".$code;
        $query = $this->db->query("SELECT $idnum from employee where employeeid = '$empid' and $idnum != '' ")->num_rows();
        return $query;
    }

   function getEmpFixedDeduc($eid = "",$title = "",$visible = "",$schedule = "",$quarter = "",$colname = "", $cutoffstart = "", $cutoffend = ""){
        $philhealth = $this->extensions->checkIfPayedPhilhealth($eid, $cutoffstart);

        $whereClause = " AND visibility='$visible'";
        if($eid)     $whereClause = " AND employeeid='$eid' AND visibility='$visible'";
        if($schedule){
            $whereClause .= " AND schedule='$schedule'";
            if($schedule == "semimonthly"){
                if($quarter)  $whereClause .= " AND FIND_IN_SET(cutoff_period,'$quarter,3')";
            }else             $whereClause .= " AND cutoff_period='$quarter'";  
        }
        // if($visible == "SHOW")  $whereClause .= " AND nocutoff > 0";
        if($colname)    $whereClause .= " AND a.code_deduction='$colname'";

        if($philhealth) $whereClause .= " OR a.code_deduction <> '' ". $whereClause ." AND a.code_deduction = 'PHILHEALTH' ";
        $query = $this->db->query("SELECT a.$title as title, a.code_deduction, a.cutoff_period,a.amount FROM employee_deduction a INNER JOIN deductions b ON(b.code_deduction = a.code_deduction) 
                WHERE a.code_deduction <> '' $whereClause GROUP BY a.code_deduction");

        return $query;
   }


   function loantitle($eid = "",$title = "",$schedule = "",$quarter = "",$colname = "",$sdate = "",$edate = ""){
        $whereClause = "";
        if($eid)     $whereClause = " AND employeeid='$eid'";
        if($schedule){
            $whereClause .= " AND schedule='$schedule'";
            if($schedule == "semimonthly"){
                if($quarter)  $whereClause .= " AND FIND_IN_SET(cutoff_period,'$quarter,3')";
            }else             $whereClause .= " AND cutoff_period='$quarter'";  
        }
        if($colname)    $whereClause .= " AND code_loan='$colname'";
        if($sdate && $edate)    $whereClause  .= " AND ((datefrom BETWEEN '$sdate' AND '$edate') OR (datefrom <= '$sdate')) AND datefrom <> '0000-00-00'  ";
        $query = $this->db->query("SELECT 
                                        CASE 
                                            WHEN '$title' = 'amount' THEN
                                                CASE 
                                                    WHEN bypass = 1 THEN IFNULL(bypass_amount, 0) 
                                                    ELSE 
                                                        CASE 
                                                            WHEN amount > currentamount THEN IFNULL(currentamount, 0)
                                                            ELSE IFNULL(amount, 0)
                                                        END
                                                END
                                            ELSE IFNULL($title, 0)
                                        END AS title, 
                                        code_loan 
                                    FROM employee_loan 
                                    INNER JOIN payroll_loan_config b ON b.id = code_loan 
                                    WHERE code_loan <> '' $whereClause AND nocutoff > 0 
                                    GROUP BY code_loan");

        return $query;
   }
   
   /*
    * DISPLAY PROCESSED TABLE HEADERS/BODY FOR PAYROLL 
    */     
    
   // Income
   function incometitlep($empid = "",$schedule = "",$quarter = "",$sdate = "",$edate = ""){
        $whereClause = "";
        $income = "";
        $title = array();
        $amt   = array();
        if($empid)      $whereClause = " AND employeeid='$empid'";
        if($schedule)   $whereClause .= " AND schedule='$schedule'";
        if($quarter)    $whereClause .= " AND quarter='$quarter'";
        if($sdate)      $whereClause .= " AND cutoffstart='$sdate'";
        if($edate)      $whereClause .= " AND cutoffend='$edate'";
        $query = $this->db->query("SELECT income FROM payroll_computed_table WHERE timestamp <> '' $whereClause");
        $income = $query->row(0)->income;
        if($income){
            $iex = explode('/',$income);
            for($x = 0; $x < count($iex); $x++){
                $singleex = explode('=',$iex[$x]); 
                $title[] = $singleex[0];
                $amt[]   = $singleex[1];
            }
            if($empid) return $amt;
             else return $title;
        }else return "";
   }
   // Fixed Deductions
   function deducttitlep($empid = "",$schedule = "",$quarter = "",$sdate = "",$edate = ""){
        $whereClause = "";
        $fixeddeduc = "";
        $title = array();
        $amt   = array();
        if($empid)      $whereClause = " AND employeeid='$empid'";
        if($schedule)   $whereClause .= " AND schedule='$schedule'";
        if($quarter)    $whereClause .= " AND quarter='$quarter'";
        if($sdate)      $whereClause .= " AND cutoffstart='$sdate'";
        if($edate)      $whereClause .= " AND cutoffend='$edate'";
        $query = $this->db->query("SELECT fixeddeduc FROM payroll_computed_table WHERE timestamp <> '' $whereClause");
        $fixeddeduc = $query->row(0)->fixeddeduc;
        if($fixeddeduc){
            $iex = explode('/',$fixeddeduc);
            for($x = 0; $x < count($iex); $x++){
                $singleex = explode('=',$iex[$x]); 
                $title[] = $singleex[0];
                $amt[]   = $singleex[1];
            }
        if($empid) return $amt; else return $title;
        }else   return "";
   }
   // Loans
   function loantitlep($empid = "",$schedule = "",$quarter = "",$sdate = "",$edate = ""){
        $whereClause = "";
         $loan = "";
        $title = array();
        $amt   = array();
        if($empid)      $whereClause = " AND employeeid='$empid'";
        if($schedule)   $whereClause .= " AND schedule='$schedule'";
        if($quarter)    $whereClause .= " AND quarter='$quarter'";
        if($sdate)      $whereClause .= " AND cutoffstart='$sdate'";
        if($edate)      $whereClause .= " AND cutoffend='$edate'";
        $query = $this->db->query("SELECT loan FROM payroll_computed_table WHERE timestamp <> '' $whereClause");
        $loan = $query->row(0)->loan;
        if($loan){
            $iex = explode('/',$loan);
            for($x = 0; $x < count($iex); $x++){
                $singleex = explode('=',$iex[$x]); 
                $title[] = $singleex[0];
                $amt[]   = $singleex[1];
            }
        if($empid) return $amt; else return $title;
        }else   return "";
        
   }
   // Other Deductions 
   function deducttitleothp($empid = "",$schedule = "",$quarter = "",$sdate = "",$edate = ""){
        $whereClause = "";
        $otherdeduc = "";
        $title = array();
        $amt   = array();
        if($empid)      $whereClause = " AND employeeid='$empid'";
        if($schedule)   $whereClause .= " AND schedule='$schedule'";
        if($quarter)    $whereClause .= " AND quarter='$quarter'";
        if($sdate)      $whereClause .= " AND cutoffstart='$sdate'";
        if($edate)      $whereClause .= " AND cutoffend='$edate'";
        $query = $this->db->query("SELECT otherdeduc FROM payroll_computed_table WHERE timestamp <> '' $whereClause");
        $otherdeduc = $query->row(0)->otherdeduc;
        if($otherdeduc){
            $iex = explode('/',$otherdeduc);
            for($x = 0; $x < count($iex); $x++){
                $singleex = explode('=',$iex[$x]); 
                $title[] = $singleex[0];
                $amt[]   = $singleex[1];
            }
            if($empid) return $amt; else return $title;
        }else   return  "";
   }
   // Tardy / Absent Deduct Display
    function dtrdeductdisplay($empid = "",$schedule = "",$quarter = "",$sdate = "",$edate = "",$title = "",$dtrcutoff = FALSE){
        $wC = "";
        if($empid)  $wC = " AND employeeid='$empid'";
        $query = $this->db->query("SELECT $title as title,
                                    (SELECT startdate FROM payroll_employee_deductions WHERE deductcutoffstart='$sdate' AND deductcutoffend='$edate' LIMIT 1) as dtrcutoff
                                     FROM payroll_computed_table WHERE schedule='$schedule' AND quarter='$quarter' AND cutoffstart='$sdate' AND cutoffend='$edate' $wC");
        if($dtrcutoff)  return $query->row(0)->dtrcutoff;
        else            return $query->row(0)->title;
    }
   
  /*
   * Modify Payroll
   */ 
   
   function modpayroll($empid = "",$schedule = "",$quarter = "",$sdate = "",$edate = "",$title = "",$col = ""){
       $whereClause = "";
       $query = $this->db->query("SELECT $title as title FROM payroll_computed_table WHERE employeeid='$empid' AND schedule='$schedule' AND quarter='$quarter' AND cutoffstart='$sdate' AND cutoffend='$edate'");
       $coltitle = $query->row(0)->title;
       if(!empty($coltitle)){
           $data = explode('/',$coltitle);
           for($x = 0; $x < count($data); $x++){
            $coldata = explode('=',$data[$x]);
            if($col == $coldata[0])    return $coldata[1];
           }
       }
       return 0;
   }
  
  /*
   * Count All Employees in Department 
   */
   function demptotal($empid,$dept,$sdate,$edate,$sched,$quarter,$campus){
    $whereClause = "";
    $arr = array();
        if($empid)  $whereClause .= " AND a.employeeid='$empid'";
        if($dept)   $whereClause .= " AND b.deptid='$dept'";
        if($sdate)  $whereClause .= " AND c.cutoffstart='$sdate'";
        if($edate)  $whereClause .= " AND c.cutoffend='$edate'";
        if($campus) $whereClause .= " AND b.campusid='$campus'";
        $query = $this->db->query("SELECT a.*, CONCAT(lname,', ',fname,' ',mname) as fullname,c.salary as regpay, d.editedby
                                     FROM payroll_employee_salary a 
                                     INNER JOIN employee b ON b.employeeid = a.employeeid
                                     INNER JOIN payroll_computed_table c ON c.employeeid = a.employeeid
                                     LEFT JOIN payroll_computed_table_adjustment d ON a.employeeid = d.employeeid AND c.cutoffstart = d.cutoffstart AND c.cutoffend = d.cutoffend
                                     WHERE (b.dateresigned = '1970-01-01' OR b.dateresigned IS NULL) AND a.schedule='$sched' $whereClause GROUP BY employeeid");
        foreach($query->result() as $row){
            $arr[] = $row->employeeid;    
        }
         //$this->db->last_query();die;
        return array($query->num_rows(),$arr);
   }
   
   /*
    * OTHER OPTIONS
    */
  function monthname($select="",$rfile = "",$return="", $default=true){

    if($rfile == "sssform")
        $month = array("03"=>"March","06"=>"June","09"=>"September","12"=>"December");
    else
        $month = array("00"=>"All Month","01"=>"January","02"=>"February","03"=>"March","04"=>"April","05"=>"May","06"=>"June","07"=>"July","08"=>"August","09"=>"September","10"=>"October","11"=>"November","12"=>"December");
    if($default) $return = "<option value='' disabled> Select Month </option>";
    foreach($month as $month=>$desc){
        $return .= "<option ".($select == $month ? "selected" : "")." value='$month'>$desc</option>";
    }
    return $return;
  }  
  function monthdesc($val = ""){
  $month = array("01"=>"January","02"=>"February","03"=>"March","04"=>"April","05"=>"May","06"=>"June","07"=>"July","08"=>"August","09"=>"September","10"=>"October","11"=>"November","12"=>"December");
  return $month[$val];
  }  
  function arithmetic($val){
    $arr = array("add"=>"Addition","sub"=>"Subtract");
    foreach($arr as $key=>$data){
        if($key == $val)    $return .= "<option value='$key' selected>$data</option>";
        else                $return .= "<option value='$key'>$data</option>";
    }
    return $return;
  }
  function yesno($val){
    $arr = array(""=>"- Select -","1"=>"Yes","2"=>"No");
    foreach($arr as $key=>$data){
        if($key == $val)    $return .= "<option value='$key' selected>$data</option>";
        else                $return .= "<option value='$key'>$data</option>";
    }
    return $return;
  }
  
  function periodyear($year = "",$caption='',$selected=''){
        $return = '';
        if($caption)  $return = "<option value=''>".$caption."</option>";
        for($x = date("Y"); $x >= 1970; $x--){
            $sel_str = $selected == $x ? 'selected' : '';
            $return .= "<option value='$x' $sel_str>$x</option>";
        }
        return $return;
    }
  
  /*
   * OPTIONS DESCRIPTION
   */
   function arithmeticdesc($val){
    $return = "";
    $arr = array("add"=>"Addition","sub"=>"Subtract");
    foreach($arr as $key=>$data){
        if($key == $val)    $return = $data;
    }
    return $return;
  }
   
   function getPayrollCutoffDetails($id='',$baseid='',$startdate='',$enddate='',$dateKey=false){
        $data = array();
        $wC = '';
        if($id) $wC .= " AND id='$id'";
        if($baseid) $wC .= " AND baseid='$baseid'";
        if($startdate && $enddate) $wC .= " AND startdate='$startdate' AND enddate='$enddate'";

        $res = $this->db->query("SELECT * FROM payroll_cutoff_config WHERE id <> '' $wC");
        foreach ($res->result() as $key => $row) {
            $cutoffkey = $dateKey ? $row->startdate.'|'.$row->enddate : $row->id;
            $data[$cutoffkey] = array(
                                    'id' => $row->id,
                                    'baseid' => $row->baseid,
                                    'schedule' => $row->schedule,
                                    'quarter' => $row->quarter,
                                    'startdate' => $row->startdate,
                                    'enddate' => $row->enddate,
                                    'addedby' => $row->addedby,
                                    'lastupdate' => $row->lastupdate,
                                    'timestamp' => $row->timestamp
                                    );
        }
        return $data;
    }

    function athenaGLAccount($gl_account = ""){
        $return = "";
        $athena_db = Globals::athenaDatabase();
        $q_gl = $this->db->query("SELECT AID, DESCRIPTION FROM $athena_db.GL_ACCOUNT");
        foreach($q_gl->result() as $key => $data){
            $is_selected = ($gl_account == $data->AID) ? " selected" : "";
            $return .= "<option value='".$data->AID."' $is_selected > ".$data->DESCRIPTION." </option>";
        }
        return $return;
    }

    /**
     * Retrieves the schedule from the cutoff table by joining with payroll_cutoff_config.
     *
     * This method performs an inner join between the 'cutoff' table (aliased as 'a')
     * and the 'payroll_cutoff_config' table (aliased as 'b') on the matching 'id' and 'baseid'.
     * It then filters the records where 'CutoffFrom' matches $sdate and 'CutoffTo' matches $edate.
     *
     * @param string $sdate The starting date for the cutoff period.
     * @param string $edate The ending date for the cutoff period.
     *
     * @return mixed Returns the schedule if found; otherwise, returns false.
     */
    function dtrCutoffSchedule($sdate, $edate)
    {
        // Select the schedule field from the cutoff table.
        $this->db->select('b.schedule');
        
        // Define the main table with an alias.
        $this->db->from('cutoff a');
        
        // Join with the payroll_cutoff_config table using the matching id and baseid fields.
        $this->db->join('payroll_cutoff_config b', 'a.id = b.baseid');
        
        // Add the where conditions for the date range.
        $this->db->where('CutoffFrom', $sdate);
        $this->db->where('CutoffTo', $edate);
        
        // Execute the query.
        $q = $this->db->get();
        
        // If a matching record exists, return the schedule; otherwise, return false.
        if ($q->num_rows() > 0) {
            return $q->row()->schedule;
        } else {
            return false;
        }
    }

    function quarterPayroll2($quarterval = "",$visible = FALSE, $schedule = "", $isDisplaySelectQuarter = false){
        $return = "";
       
        $quarter = array("0"=>"- Select -","1"=>"1st Cut-Off","2"=>"2nd Cut-Off","3"=>"All Cut-Off");

        if($isDisplaySelectQuarter) $return .= "<option value='0' selected>Select a Quarter</option>";

        foreach($quarter as $quarter=>$desc){
            if($quarterval == $quarter)
                $return .= "<option value='$quarter' selected>$desc</option>";
            else
                $return .= "<option value='$quarter'>$desc</option>";
        }
        return $return;
    }
    function quarterNew($quarterval = "",$visible = FALSE, $schedule = "", $isDisplaySelectQuarter = false){
        $return = "";
        if($schedule == 'weekly'){
            $quarter = array("0"=>"Select Quarter","1"=>"1st Cut-Off","2"=>"2nd Cut-Off");
        }else if($schedule == 'semimonthly'){
        if($visible)
            $quarter = array("0"=>"Select Quarter","1"=>"1st Cut-Off","2"=>"2nd Cut-Off","3"=>"All Cut-Off");
        else
            $quarter = array("0"=>"Select Quarter","1"=>"1st Cut-Off","2"=>"2nd Cut-Off","3"=>"All Cut-Off");
        }else if($schedule == 'monthly'){
            $quarter = array("0"=>"Select Quarter","3"=>"All Cut-Off");
        }else{
            $quarter = array("0"=>"Select Quarter","1"=>"1st Cut-Off","2"=>"2nd Cut-Off");
        }

        if($isDisplaySelectQuarter) $return .= "<option value='0' selected>Select a Quarter</option>";

        foreach($quarter as $quarter=>$desc){
            if($quarterval == $quarter)
                $return .= "<option value='$quarter' selected>$desc</option>";
            else
                $return .= "<option value='$quarter'>$desc</option>";
        }
        return $return;
    }
}