<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
date_default_timezone_set('Asia/Manila');
class Extras extends CI_Model {
    public function getclientipaddress(){
        $ipaddress = '';
        if(getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');#$_SERVER[''];
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');#$_SERVER[''];
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');#$_SERVER[''];
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');#$_SERVER[''];
        else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');#$_SERVER[''];
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');#$_SERVER[''];
        else $ipaddress = 'UNKNOWN';
        $remoteIp = $ipaddress;
        return $remoteIp;
    }
    function returnmacaddress($remoteIp="") {
        if($remoteIp==""){
           $remoteIp = $this->getclientipaddress();
        }
        // This code is under the GNU Public Licence
        // Written by michael_stankiewicz {don't spam} at yahoo {no spam} dot com
        // Tested only on linux, please report bugs
        
        // WARNinG: the commands 'which' and 'arp' should be executable
        // by the apache user; on most linux boxes the default configuration
        // should work fine
        
        // get the arp executable path
        
        $location = `which arp`;
        $location = rtrim($location);
        // Execute the arp command and store the output in $arpTable
        $arpTable = `$location -n`;
        # echo $arpTable;
        // Split the output so every line is an entry of the $arpSplitted array
        $arpSplitted = explode("\n", $arpTable);
        //echo $arpSplitted[6];
        // get the remote ip address (the ip address of the client, the browser)
        # $remoteIp = str_replace(".", "\\.", $remoteIp);
        //echo $remoteIp;
        // Cicle the array to find the match with the remote ip address
        foreach ($arpSplitted as $value) {
        // Split every arp line, this is done in case the format of the arp
        // command output is a bit different than expected
        $valueSplitted = explode(" ",$value);
        # echo $valueSplitted[0];
        $ipFound = false;
        foreach ($valueSplitted as $spLine) {
            # echo "/$remoteIp/ : $spLine";
            if (preg_match("/$remoteIp/",$spLine)) {
             $ipFound = true;
            }
            // The ip address has been found, now rescan all the string
            // to get the mac address
            if ($ipFound) {
            // Rescan all the string, in case the mac address, in the string
            // returned by arp, comes before the ip address
            // (you know, Murphy's laws)
            reset($valueSplitted);
            foreach ($valueSplitted as $spLine) {
                if (preg_match("/[0-9a-f][0-9a-f][:-]".
                    "[0-9a-f][0-9a-f][:-]".
                    "[0-9a-f][0-9a-f][:-]".
                    "[0-9a-f][0-9a-f][:-]".
                    "[0-9a-f][0-9a-f][:-]".
                    "[0-9a-f][0-9a-f]/i",$spLine)) {
                    return $spLine;
                    }
                    }
            }
            $ipFound = false;
            }
        }
        return false;
    }

    function getBankDesc($empid = ''){
        $query = $this->db->query("SELECT emp_bank from employee where employeeid = '$empid'")->result();
        return $query;
    }

    function showMonth($months=""){
        $result = '';
        $month = array('' => "Month",'01' => "January",'02' => "February",'03' => "March",'04' => "April",'05' => "May",'06' => "June",'07' => "July",'08' => "August",'09' => "September",'10' => "October",'11' => "November",'12' => "December" );
        foreach ($month as $key => $value) {
            if ($months == $key) {
                $sel = "selected";
            }
            else
            {
                $sel = "";
            }
            $result .= "<option value='$key' $sel>".$value."</option>";
        }

        return $result;
    }  
    
    function school_name(){
        return "St. Therese - MTC Colleges";

    }

    function school_desc(){
        return " ";
    }

    
    function enum_select( $table , $field ){
        $query = "SHOW COLUMNS FROM `$table` LIKE '$field'";
        $res = $this->db->query($query);
        #extract the values
        #the values are enclosed in single quotes
        #and separated by commas
        $regex = "/'(.*?)'/";
        preg_match_all( $regex , $res->row(0)->Type, $enum_array );
        $enum_fields = $enum_array[1];
        $return = array();
        foreach($enum_fields as $enumvalue){
          $return[$enumvalue] = $enumvalue;  
        }
        return $return;
    }
    
    /** This function will connect to AIMS */
    function showSchoolYear(){
        $returns = array(""=>"");
        $this->db->select("SY");
        $this->db->order_by("DDC.SYFORMAT(DDC.tblFacultyLoad.SY)","DESC");
        $q = $this->db->get("DDC.tblFacultyLoad"); 
        
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->SY] = $row->SY;
        }
        return $returns;
    }
    # by Naces 12-18-17
    function showStudentYL($selected,$dept){


         $caption = "-All Year Level-";

         
    if($dept == ''){
            $return = "<option value=''>{$caption}</option>";
            $sql = $this->db->query("SELECT DISTINCT YearLevel FROM ICADasma.tblStudClassList ");
                foreach ($sql->result() as $value) {
                    $return .= "<option value='$value->YearLevel'>$value->YearLevel</option>";
                }

    }else{
        $return = "<option value=''>{$caption}</option>";
         if($selected == "" || $selected == null){
                $query = $this->db->query("SELECT a.`Years` FROM ICADasma.tblCourses a WHERE a.`HSOrCollege` = '$dept' ");
                $years = $query->row(0)->Years;
                for($i=1; $i <= $years; $i++){
                    $return .= "<option value='$i'>$i</option>";    
                }
            }else{

                $query = $this->db->query("SELECT a.`Years` FROM ICADasma.tblCourses a WHERE a.`HSOrCollege` = '$dept' ");
                $years = $query->row(0)->Years;
                for($i=1; $i <= $years; $i++){
                    ($selected == $i) ? $selecthis = "selected": $selecthis = "";
                    $return .= "<option value='$i' $selecthis>$i</option>";    
                }

            }
        
    }   
    
     return $return;

    }
    function showStudentSection($selected='',$dept='',$sy='',$sem='',$yl=''){

        $caption = "All Section";
        
        
        
        if($dept == ''){
            $return = "<option value=''>{$caption}</option>";
                    $sql = $this->db->query("SELECT DISTINCT SectCode FROM ICADasma.tblStudClassList");
                    foreach ($sql->result() as $value) {
                        $return .= "<option value='$value->SectCode'>$value->SectCode</option>";
                    }

        }else{
            
            if($selected == "" || $selected == null){
                    
            $return = "<option value=''>{$caption}</option>";
                    $sql = $this->db->query("SELECT a.section FROM student AS a 
                                    WHERE a.SY = '$sy' 
                                      AND a.Sem = '$sem' 
                                      AND a.yearlevel = '$yl' 
                                      AND a.depttype = '$dept' 
                                    GROUP BY a.section ");
                    
                    foreach ($sql->result() as $value) {
                        $return .= "<option value='$value->section'>".$value->section."</option>";
                    }
                }else{
                    $return = "<option value=''>{$caption}</option>";
                    $sql = $this->db->query("SELECT a.section FROM student AS a 
                                WHERE a.SY = '$sy' 
                                  AND a.Sem = '$sem' 
                                  AND a.yearlevel = '$yl' 
                                  AND a.depttype = '$dept' 
                                GROUP BY a.section ");
                    foreach ($sql->result() as $value) {
                        ($selected == $value->section) ? $selecthis = "selected": $selecthis = "";
                        $return .= "<option value='$value->section' $selecthis>".$value->section."</option>";
                    }

                }

        }
        return $return;

    }
   
    
    function showStudentDepartmentType($depts,$selected) {

    $caption = "-All Department-";

    if($depts == ''){
    $return = "<option value=''>{$caption}</option>";

    if($selected == "" || $selected == null){

                $sql = $this->db->query("SELECT * FROM _depttypes ORDER BY description");
                foreach ($sql->result() as $key) {
                $return .= "<option value='$key->code'>$key->description</option>";
            }

    }else{
            
            $sql = $this->db->query("SELECT * FROM _depttypes ORDER BY description");
            foreach ($sql->result() as $key) {
                ($selected == $key->code) ? $selecthis = "selected": $selecthis = "";
                $return .= "<option value='$key->code' $selecthis>$key->description</option>";
            }
    }

    
    return $return;
    }else{
        $deptArray = explode(",", $depts);
        $count = count($deptArray);

        $descArray = "";
        for($i=0; $i < $count; $i++){
        $sql = $this->db->query("SELECT description FROM _depttypes WHERE code = '$deptArray[$i]' ORDER BY description");
        if ($sql->num_rows() > 0) {
            $descArray .= $sql->row(0)->description."<br>";
        }
        }
        

        return $descArray;
    }
}
function showStudentSY() {
    $caption = "-All School Year-";

    $return = "<option value=''>{$caption}</option>";
    $sql = $this->db->query("(SELECT DISTINCT SY FROM ICADasma.tblStatusHistory WHERE SY<>'' ORDER BY SY DESC)
                                    UNION ALL
                                   (SELECT DISTINCT SY FROM ICADasma.tblConfig WHERE SY<>'' AND SY NOT IN (SELECT DISTINCT SY FROM ICADasma.tblStatusHistory WHERE SY<>'' ORDER BY SY DESC) ORDER BY SY DESC)
                                    UNION ALL
                                   (SELECT DISTINCT SY FROM ICADasma.tblSchedule WHERE SY<>''
                                                                        AND SY NOT IN (SELECT DISTINCT SY FROM ICADasma.tblStatusHistory WHERE SY<>'')
                                                                        AND SY NOT IN (SELECT DISTINCT SY FROM ICADasma.tblConfig WHERE SY<>'' AND SY NOT IN (SELECT DISTINCT SY FROM ICADasma.tblStatusHistory WHERE SY<>'')))
                                    ORDER BY SY DESC;");
    foreach ($sql->result() as $value) {
        $return .= "<option value='$value->SY'>$value->SY</option>";
    }
    return $return;
}

    function saveStudentSchedule($data){
        $sy = $data['sy'];
        $dept = $data['dept'];
        $yl = $data['yl'];
        $sect = $data['sect'];
        $aDate = $data['aDate'];

        $deptArray = implode(',', $dept);

        $timeStart = $data['timeStart'];
        $timeStart = date("Y-m-d H:i:s",strtotime($timeStart));

        $timeEnd = $data['timeEnd'];
        $timeEnd = date("Y-m-d H:i:s",strtotime($timeEnd));


        $tardyStart = $data['tardyStart'];
        $tardyStart = date("Y-m-d H:i:s",strtotime($tardyStart));

        $halfdayStart = $data['halfdayStart'];
        $halfdayStart = date("Y-m-d H:i:s",strtotime($halfdayStart));

        $absentStart = $data['absentStart'];
        $absentStart = date("Y-m-d H:i:s",strtotime($absentStart));

        $sql = $this->db->query("INSERT INTO student_schedule_batch (sy,department,yl,section,timeStart,timeEnd,tardyStart,halfdayStart,absentStart,applicableDate) VALUES('$sy','$deptArray','$yl','$sect','$timeStart','$timeEnd','$tardyStart','$halfdayStart','$absentStart','$aDate')");
        ($sql === true) ? $check = 'Successfully Saved' : $check = "Somethings Wrong...";
        echo $check;
        return;
    }

    #End by naces 12-18-17


     function showSemester(){
        $return = array(""=>"","A"=>"First Semester","B"=>"Second Semester","C"=>"Summer");
        return $return;
    }
    function showYearLevel($section=""){
        $return = array(""=>"All year level"); 
        #$q1 = "select distinct trim(yearlevel) as yearlevel from student where ifnull(trim(yearlevel),'')<>'' ".($section?" and section='{$section}'":"")."  ORDER BY trim(yearlevel)";
        $q1 = "select distinct trim(YearLevel) as yearlevel from StJude.tblPersonalData where ifnull(trim(YearLevel),'')<>'' ".($section?" and SectCode='{$section}'":"")."  ORDER BY trim(YearLevel)";
        $q = $this->db->query($q1)->result();
        $return = array(""=>"All year level");
        foreach($q as $oo){
          $return[$oo->yearlevel] = $oo->yearlevel;    
        }
        return $return;
    }
    function showSection($yearlevel="", $dept=""){
        $return = array(""=>"All section");
        #$que = "select distinct trim(section) as section from student where ifnull(trim(section),'')<>''".($yearlevel?" and (yearlevel='{$yearlevel}'":"")." ".($dept?" and coursecode='{$dept}' )":"")." ORDER BY trim(section)";
        $que = "select distinct trim(SectCode) as section from StJude.tblPersonalData where ifnull(trim(SectCode),'')<>''".($yearlevel?" and (YearLevel='{$yearlevel}'":"")." ".($dept?" and CourseCode='{$dept}' )":"")." ORDER BY trim(SectCode)";
        $q = $this->db->query($que)->result();
        foreach($q as $oo){
          $return[$oo->section] = $oo->section;
        }
        return $return;
    }
    
    function showLecLab(){
        $return = array("LEC"=>"LEC","LAB"=>"LAB");
        return $return;
    }
    function showMachineType(){
        $return = array("IN-OUT"=>"IN-OUT","IN"=>"IN","OUT"=>"OUT");
        return $return;
    }
    function showAllStatus(){
        $return = array("ACTIVE"=>"ACTIVE","INACTIVE"=>"INACTIVE");
        return $return;
    }
    function showLeaveStatus(){
        $return = array("PENDING"=>"PENDING","APPROVED"=>"APPROVED","DISAPPROVED"=>"DISAPPROVED");
        return $return;
    }
    function showCategory(){
        $return = array(""=>"- All Category - ", "PENDING"=>"PENDING", "APPROVED"=>"APPROVED", "DISAPPROVED"=>"DISAPPROVED");
        return $return;
    }
    function showCategoryopt($id = ""){
        $opt = "";
        $return = array(""=>"- All Category - ", "PENDING"=>"PENDING", "APPROVED"=>"APPROVED", "DISAPPROVED"=>"DISAPPROVED");
        foreach($return as $key=>$val){
            if($key == $id) $sel = " selected";
            else            $sel = "";
            $opt .= "<option value='$key' $sel>$val</option>";
        }
        return $opt;
    }
    function showLeavelist(){
        $return = array("VL"=>"Vacation Leave","SL"=>"Sick Leave","EL"=>"Emergency Leave","other"=>"Others");
        return $return;
    }
    function showcstat(){
        $return = array(""=>"- All Status -", "PENDING"=>"PENDING", "DONE"=>"DONE");
        return $return;
    }

    /**
     * DISPLAY DEGREE STATUS FOR EMPLOYEE 201 EMPLOYEE DETAILS
     * @return string - options of degree status
     */
    function showDegreeStatus(){
        $degrees = array('UNDERGRADUATE', 'GRADUATE', 'COMPLETED','UNIT EARNER');
        $option = '';
        foreach($degrees as $d){
            $selected = ($d == $degrees) ?'selected':'';
            $option .= "
                <option value='$d' $selected>$d</option>
            ";
        }
        return $option;
    }

    function showLoanConfig($loan_type="")
    {
        $return = "";
        $where_clause = "";
        if($loan_type) $where_clause = " WHERE loan_type = '$loan_type'";
        $query = $this->db->query("SELECT id,description FROM payroll_loan_config $where_clause ORDER by ID");
        foreach ($query->result() as $key) {
            $return .= "<option value='$key->id'>".$key->description."</option>";
        }
        return $return;
    }

     function showDeductionsConfig()
    {
        $return = "";
        $query = $this->db->query("SELECT id,description FROM payroll_deduction_config ORDER by ID");
        foreach ($query->result() as $key) {
            $return .= "<option value='$key->id'>".$key->description."</option>";
        }
        return $return;
    }

    function showDeductionLoanConfig()
    {
        $return = "";
        $query = $this->db->query(" SELECT id,description FROM payroll_loan_config UNION ALL SELECT id,description FROM payroll_deduction_config ORDER BY ID");
        foreach ($query->result() as $key) {
            $return .= "<option value='$key->description'>".$key->description."</option>";
        }
        return $return;
    }
    function showLeaveType($ltype = "",$eid = ""){
        $wC     = "";
        $return = "<option value=''>- Leave Type -</option>";
        $qemp   = $this->db->query("SELECT leavetype FROM employee WHERE employeeid='$eid'");
        $eltype = $qemp->row(0)->leavetype;
        if($eltype ) $wC     = " AND leavetype='$eltype'";  
        $query  = $this->db->query("SELECT code_request,description,leavetype FROM code_request_form WHERE leavetype <> '' $wC")->result();  
        foreach($query as $val){
            $code = $val->code_request;
            $desc = $val->description;
            $type = $val->leavetype;
            if($ltype == $code)
                $return .= "<option value='$code' selected>$desc (".$type.")</option>";
            else
                $return .= "<option value='$code'>$desc (".$type.")</option>";
        }
        return $return;
    }

    function getemployeemlevel($emptype=""){
        $returns = "";
        $q = $this->db->query("select description from code_managementlevel WHERE managementid='$emptype'")->result();
        foreach($q as $row){
            $returns = $row->description;
        }
        
        return $returns;
    }
    /*

    */
    /*  
    * title   : new function added
    * author  : justin (with e)
    *
    */
    function saveDependent($data){
        $msg = '';
        if($data['job'] == "saveNewDependent")
            $query = $this->db->query("INSERT INTO code_tax_status VALUES('{$data['dep_code']}','{$data['stat_name']}','{$data['tax_exc']}')");
        else
            $query = $this->db->query("UPDATE code_tax_status SET status_desc='{$data['stat_name']}', status_exemption='{$data['tax_exc']}' WHERE status_code='{$data['dep_code']}'");

        // return after saving
        $msg = "Successfully Saved!.";
        return $msg;
    }

    function deleteDependent($dep_code){
        $msg ='';
        $query = $this->db->query("DELETE FROM code_tax_status WHERE status_code='{$dep_code}'");
        $msg = "Successfully Deleted!.";
        return $msg;
    }
     /**get all campuses**/
    function getCampusesMutiple($campusid = "") {
        $campuses = explode(',', $campusid);
        $where = '';
        $usercampus =  $this->getCampusUser();
        if($usercampus) $where = " where FIND_IN_SET (code,'$usercampus') ";
        $return = "<option value=''>Select site </option>";
        $query = $this->db->query("SELECT code, description FROM code_campus $where ")->result();
        foreach ($query as $key) {
            if(in_array($key->code, $campuses ) ) $return .= "<option value='$key->code' selected>$key->description</option>";
            else $return .= "<option value='$key->code'>$key->description</option>";
        }

        return $return;
    }

    function getEmployeeMutiple($employeeid = "") {
        $employees = explode(',', $employeeid);
        $where = '';
        $return = "<option value=''>Select employee</option>";
        $query = $this->db->query("SELECT * FROM employee WHERE (dateresigned = '1970-01-01' OR dateresigned='0000-00-00' OR dateresigned IS NULL) AND employeeid <> '' ORDER BY lname")->result();
        foreach ($query as $key) {
            $fullname = $key->lname.", ".$key->fname." ".$key->mname;
            if(in_array($key->employeeid, $employees, true)) $return .= "<option value='$key->employeeid' selected>$fullname</option>";
            else $return .= "<option value='$key->employeeid'>$fullname</option>";
        }

        return $return;
    }

    function getAllEmployeeListOption($status=""){
        $this->db->select("CONCAT(lname, ', ', fname, ' ', mname) AS fullname, employeeid", FALSE);
        if($status != "") $this->db->where('isactive', $status);
        $query = $this->db->get('employee');
        return $query->result_array();
    }


    function getCampusUser() {
        $usercampus = "";
        if($GLOBALS['_SERVER']['DB_NAME'] == 'HRIS_STTHRESE_LA_FIESTA'){
        	$usercampus = 'https://lafiesta-sttheresehris.pinnacle.edu.ph/';
        }else if ($GLOBALS['_SERVER']['DB_NAME'] == 'HRIS_STTHRESE_MAGDALO') {
        	$usercampus = 'https://magdalo-sttheresehris.pinnacle.edu.ph/';
        } else if ($GLOBALS['_SERVER']['DB_NAME'] == 'HRIS_STTHRESE_TIGBAUAN') {
        	$usercampus = 'https://tigbauan-sttheresehris.pinnacle.edu.ph/';
        }
        return $usercampus;
    }

    function getCompanyUser() {
        $username = $this->session->userdata("username");
        $return = "";
        $query = $this->db->query("SELECT company FROM user_info where username='$username' ")->result();
        foreach (GLOBALS::result_XHEP($query) as $key) {
            $return = $key->company;
        }
        return $return;
    }

    function getOfficeTypeDescription($code) {
        $return = "Not Set";
        $query = $this->db->query("SELECT description FROM office_type where code='$code' ")->result();
        foreach (GLOBALS::result_XHEP($query) as $key) {
            $return = $key->description;
        }
        return $return;
    }

    function getOfficeType($type = "") {
        $where = '';
        $return = "<option value=''>Select Type</option>";

        $query = $this->db->query("SELECT code, description FROM office_type $where")->result();
        foreach ($query as $key) {
            if($type == $key->code) $return .= '<option value='.Globals::_e($key->code).' selected>'.Globals::_e($key->description).'</option>';
            else $return .= '<option value='.Globals::_e($key->code).'>'.Globals::_e($key->description).'</option>';
        }
        return $return;
    }

    function getEmploymentStatMultiple($empStat = "") {
        $query = $this->db->query("SELECT * FROM code_status")->result();
        foreach ($query as $key) {
            if(strpos($empStat, $key->code) !== false) $return .= '<option value='.Globals::_e($key->code).' selected>'.Globals::_e($key->description).'</option>';
            else $return .= '<option value='.Globals::_e($key->code).'>'.Globals::_e($key->description).'</option>';
        }
        return $return;
    }

    function getCampuses($campusid = "") {
        $return = "";
        $campusid = $this->db->campus_code;
        $query = $this->db->query("SELECT code, description FROM code_campus WHERE code = '$campusid'")->result();
        foreach ($query as $key) {
            if($campusid == $key->code) $return .= '<option value='.Globals::_e($key->code).' selected>'.Globals::_e($key->description).'</option>';
            else $return .= '<option value='.Globals::_e($key->code).'>'.Globals::_e($key->description).'</option>';
        }
        
        return $return;
    }

    function getEmpDetails($empid){
        return $this->db->query("SELECT firstname, lastname FROM user_info WHERE `username` = '$empid'")->row();
    }

    function getSubCampuses($campusid = "") {
        $campusid = $this->db->campus_code;
        $query = $this->db->query("SELECT code, description FROM code_campus WHERE code != '$campusid'")->result();
        foreach ($query as $key) {
            if($campusid == $key->code) $return .= '<option value='.Globals::_e($key->code).' selected>'.Globals::_e($key->description).'</option>';
            else $return .= '<option value='.Globals::_e($key->code).'>'.Globals::_e($key->description).'</option>';
        }
        
        return $return;
    }
    
    function getMainSite() {
        $result = $this->db->query("SELECT code, description FROM code_campus LIMIT 1")->row();
        if ($result) {
            return $result; 
        }
        return ''; 
    }

    function mainSiteDropdown() {
        $return = "";
        $mainCamp = $this->getMainSite();
        $return = "<option value='".$mainCamp->code."'>".$mainCamp->description."</option>";

        return $return;
    }

    function subSiteDropdown($selectOpt = TRUE, $sitecodes='', $campus_code = null) {
        $return = "";
        $selectedSites = "";
        if ($sitecodes) $selectedSites = explode(',', $sitecodes);
        $mainSite = $campus_code ?? $this->getMainSite()->code;
        $allSites = Globals::sites('');

        if ($selectOpt) $return .= "<option value=''>Select Site</option>";
        foreach ($allSites as $site => $desc){
            if ($mainSite != $site) {
                $isSelected = in_array($site, $selectedSites) ? 'selected' : '';
                $return .= "<option value='".$site."' $isSelected>".$desc."</option>";
            }
        }
        return $return;
    }

    function renderCampusOptions($defaultValCode=''){
        $result ="";
        $query = $this->db->query("SELECT * FROM code_campus")->result();
        foreach($query as $campus){
            if($defaultValCode == $campus->code) $result .= '<option value='.Globals::_e($campus->code).' selected>'.Globals::_e($campus->description).'</option>';
            else $result .= '<option value='.Globals::_e($campus->code).'>'.Globals::_e($campus->description).'</option>';
        }
        return $result;
    }

    function getCampusesWithAll($campusid = "",$all=true) {
        $where = '';
        $usercampus =  $this->getCampusUser();
        $return = "<option value=''>Select Site</option>";
        if($all) $return = "<option value='all'>All Site</option>";

        if($usercampus) $where = " where FIND_IN_SET (code,'$usercampus') ";
        $query = $this->db->query("SELECT code, description FROM code_campus $where")->result();
        foreach ($query as $key) {
            if($campusid == $key->code) $return .= '<option value='.Globals::_e($key->code).' selected>'.Globals::_e($key->description).'</option>';
            else $return .= '<option value='.Globals::_e($key->code).'>'.Globals::_e($key->description).'</option>';
        }
        return $return;
    }

    function getTerminalDevices($terminalid = "",$all=true,$campus="") {
        $where = '';
        $where .= ($campus) ? "WHERE campus='$campus'" : "";
        $usercampus =  $this->getCampusUser();
        $return = "<option value=''>Select Terminal</option>";
        if($all) $return = "<option value='All'>All Terminal</option>";

        $query = $this->db->query("SELECT id, name FROM terminal_devices $where")->result();
        foreach ($query as $key) {
            if($terminalid == $key->id) $return .= '<option value='.Globals::_e($key->id).' selected>'.Globals::_e($key->name).'</option>';
            else $return .= '<option value='.Globals::_e($key->id).'>'.Globals::_e($key->name).'</option>';
        }
        return $return;
    }

    function getFloor($floorid = "",$all=true) {
        $where = $isAll ='';
        $return = "<option value=''>Select Floor</option>";
        if ($floorid == "All") $isAll = "selected";
        if($all) $return = "<option value='All'".$isAll.">All Floor</option>";

        $query = $this->db->query("SELECT code, description FROM floor $where")->result();
        if(strpos($floorid, ',') !== false){
            $floorArray = explode(",", $floorid);
            foreach ($query as $key) {
                if(in_array($key->description, $floorArray)) $return .= '<option value='.Globals::_e($key->description).' selected>'.Globals::_e($key->description).'</option>';
                else $return .= '<option value='.Globals::_e($key->description).'>'.Globals::_e($key->description).'</option>';
            }
        }else{
            foreach ($query as $key) {
                if($floorid == $key->description) $return .= '<option value='.Globals::_e($key->description).' selected>'.Globals::_e($key->description).'</option>';
                else $return .= '<option value='.Globals::_e($key->description).'>'.Globals::_e($key->description).'</option>';
            }
        }
        return $return;
    }

    function getBuilding($buildingid = "",$all=true) {
        $where = $isAll = '';
        $return = "<option value=''>Select Building</option>";
        if ($buildingid == "All") $isAll = "selected";
        if($all) $return = "<option value='All' ".$isAll.">All Building</option>";

        $query = $this->db->query("SELECT code, description FROM building $where")->result();
        if(strpos($buildingid, ',') !== false){
            $buildingArray = explode(",", $buildingid);
            foreach ($query as $key) {
                if(in_array($key->description, $buildingArray)) $return .= '<option value='.Globals::_e($key->description).' selected>'.Globals::_e($key->description).'</option>';
                else $return .= '<option value='.Globals::_e($key->description).'>'.Globals::_e($key->description).'</option>';
            }
        }else{
            foreach ($query as $key) {
                if($buildingid == $key->description) $return .= '<option value='.Globals::_e($key->description).' selected>'.Globals::_e($key->description).'</option>';
                else $return .= '<option value='.Globals::_e($key->description).'>'.Globals::_e($key->description).'</option>';
            }
        }
        return $return;
    }

    function getCampusCompanyID($campCom = "",$all=true) {
        $where = $isAll = '';
        $return = "<option value=''>Select Company</option>";
        if ($campCom == "All") $isAll = "selected";
        if($all) $return = "<option value='All' ".$isAll.">All Company</option>";

        $query = $this->db->query("SELECT id, company_description FROM campus_company $where")->result();
        if(strpos($campCom, ',') !== false){
            $buildingArray = explode(",", $campCom);
            foreach ($query as $key) {
                if(in_array($key->id, $buildingArray)) $return .= '<option value='.Globals::_e($key->id).' selected>'.Globals::_e($key->company_description).'</option>';
                else $return .= '<option value='.Globals::_e($key->id).'>'.Globals::_e($key->company_description).'</option>';
            }
        }else{
            foreach ($query as $key) {
                if($campCom == $key->id) $return .= '<option value='.Globals::_e($key->id).' selected>'.Globals::_e($key->company_description).'</option>';
                else $return .= '<option value='.Globals::_e($key->id).'>'.Globals::_e($key->company_description).'</option>';
            }
        }
        return $return;
    }

    function getCampusCompany($company_campus='', $campusid='', $where_clause=''){
        $where_clause = "WHERE 1";
        $usercompany =  $this->getCompanyUser();
        $usercampus =  $this->getCampusUser();

        if($campusid && !$usercompany) $where_clause .= " AND campus_code = '$campusid' ";
        if($usercompany) $where_clause .= " AND FIND_IN_SET (`id`,'$usercompany')";
        if(!$usercompany && $usercampus != '') $where_clause .= " AND FIND_IN_SET (`campus_code`,'$usercampus')";

        $return = "<option value='all'> All Company </option>";
        $query = $this->db->query("SELECT company_description FROM campus_company $where_clause")->result();

        foreach ($query as $key) {
            if($company_campus == Globals::_e($key->company_description)) $return .= '<option value="'.Globals::_e($key->company_description).'" selected>'.Globals::_e($key->company_description).'</option>';
            else $return .= '<option value="'.Globals::_e($key->company_description).'">'.Globals::_e($key->company_description).'</option>';
        }

        return $return;
    }

    function selectCampusCompany($company_campus='', $campusid='', $where_clause=''){
        $where_clause = "WHERE 1";
        $usercompany =  $this->getCompanyUser();

        if($campusid && !$usercompany) $where_clause .= " AND campus_code = '$campusid' ";
        if($usercompany) $where_clause .= " AND FIND_IN_SET (`id`,'$usercompany')";

        $return = "<option value=''>All Company</option>";
        $query = $this->db->query("SELECT company_description FROM campus_company $where_clause")->result();
        foreach ($query as $key) {
            if($company_campus == $key->company_description) $return .= '<option value="'.Globals::_e($key->company_description).'" selected>'.Globals::_e($key->company_description).'</option>';
            else $return .= '<option value="'.Globals::_e($key->company_description).'">'.Globals::_e($key->company_description).'</option>';
        }

        return $return;
    }

    function getCampusCompany201($company_campus='', $campusid='', $where_clause=''){
        $where_clause = "";
        if($campusid) $where_clause .= "WHERE campus_code = '$campusid' ";

        $return = "<option value=''>  All Company  </option>";
        $query = $this->db->query("SELECT company_description FROM campus_company $where_clause")->result();
        foreach ($query as $key) {
            if($company_campus == $key->company_description) $return .= '<option value="'.Globals::_e($key->company_description).'" selected>'.Globals::_e($key->company_description).'</option>';
            else $return .= '<option value="'.Globals::_e($key->company_description).'">'.Globals::_e($key->company_description).'</option>';
        }

        return $return;
    }

    function getCampusCompanyMulti($company_campus='', $campusid='', $compID=''){
        $where_clause = "WHERE 1";
        if($campusid) $where_clause .= " AND campus_code IN($campusid)";
        if($compID) $where_clause .= " AND id = '$company_campus'";

        $return = "<option value=''>  All Company  </option>";
        $query = $this->db->query("SELECT company_description, id FROM campus_company $where_clause")->result();
        foreach ($query as $key) {
            if($company_campus == $key->id) $return .= '<option value="'.Globals::_e($key->id).'" selected>'.Globals::_e($key->company_description).'</option>';
            else $return .= '<option value="'.Globals::_e($key->id).'">'.Globals::_e($key->company_description).'</option>';
        }

        return $return;
    }

    function getOfficeDescription()
    {
        $return = array();
        $query = $this->db->query("SELECT code,description FROM code_department ORDER BY code");
        foreach ($query->result() as $row) {
            $return[$row->code] = $row->description;
        }
        return $return;
    }

    function getDepartmentDescription()
    {
        $return = array();
        $query = $this->db->query("SELECT code,description FROM code_office ORDER BY code");
        foreach ($query->result() as $row) {
            $return[$row->code] = $row->description;
        }
        return $return;
    }

    public function getofficeCoverage($code, $employment_stat){
        return $this->db->query("SELECT dfrom,dto FROM `code_leave_school_coverage` WHERE DEPARTMENT = '$code' AND LOCATE('$employment_stat', employment_stat) AND CURDATE() BETWEEN dfrom AND dto")->result_array();
    }

    public function getCoverageDate($code, $employment_stat){
        return $this->db->query("SELECT dfrom,dto FROM `code_leave_coverage` WHERE SUBSTR(dfrom, 1, 4) = YEAR(CURDATE()) AND LOCATE('REG', employment_stat) AND leave_type = '$code'")->result_array();
    }

    public function getCoverageDateData(){
        return $this->db->query("SELECT * FROM `code_leave_coverage`")->result_array();
    }

     function getDeptpartment($deptid = "") {
        $return = "<option value=''>  All Department  </option>";
        $query = $this->db->query("SELECT code, description FROM code_office ")->result();
        foreach ($query as $key) {
            if($deptid == $key->code) $return .= "<option value='$key->code' selected>$key->description</option>";
            else $return .= "<option value='$key->code'>$key->description</option>";
        }

        return $return;
    }

    function getDeptpartmentCodeDepartment($deptid = "", $isall = false, $ismultiple=false) {
        if($isall) $return = "<option value='all'  ".($deptid == 'all' ? 'selected' : '').">  All Department  </option>";
        else  $return = "<option value=''>  All Department  </option>";

        if($ismultiple && $deptid != 'all') $deptid = explode(',', $deptid);
        $query = $this->db->query("SELECT id, code, description FROM code_department")->result();
        foreach ($query as $key) {
            if($ismultiple && $deptid != 'all'){
                if(in_array($key->code, $deptid)) $return .= '<option value= '.$key->code.' selected>'.GLOBALS::_e($key->description).'</option>';
                else $return .= '<option value= '.$key->code.' >'.GLOBALS::_e($key->description).'</option>';
            }else{
                if($deptid == $key->code) $return .= '<option value= '.$key->code.' selected>'.GLOBALS::_e($key->description).'</option>';
                else $return .= '<option value= '.$key->code.' >'.GLOBALS::_e($key->description).'</option>';
            }
        }

        return $return;
    }

    function getDeptpartmentCodeDepartmentbyCode($deptid = "") {
        $return = "<option value=''>  All Department  </option>";
        $query = $this->db->query("SELECT id, code, description FROM code_department")->result();
        foreach ($query as $key) {
            if($deptid == $key->code) $return .= '<option value= '.$key->code.' selected>'.GLOBALS::_e($key->description).'</option>';
            else $return .= '<option value= '.$key->code.' >'.GLOBALS::_e($key->description).'</option>';
        }

        return $return;
    }

    function departmentidtocode($id = ""){
        $query = $this->db->query("SELECT code FROM code_department WHERE id = '$id'");
        return $query->row()->code;

    }

    function getempFilter($deptid = '', $office = "") {
        $query = $this->db->query("SELECT b.employeeid from code_office a INNER JOIn employee b on a.code = b.`office` where b.office = '$office' ")->result();
        return $return;
    }
    function getOffice($officeid = "", $isall=false, $ismultiple=false) {
        if($isall) $return = "<option value='all' ".($officeid == 'all' ? 'selected' : '')."> All Office/Unit </option>";
        else $return = "<option value=''> All Office/Unit </option>";
        if($ismultiple && $officeid != 'all') $officeid = explode(',', $officeid);
        $query = $this->db->query("SELECT code, description FROM code_office ")->result();
        foreach ($query as $key) {
            if($ismultiple && $officeid != 'all'){
                if(in_array($key->code, $officeid)) $return .= "<option value='".Globals::_e($key->code)."' selected>".Globals::_e($key->description)."</option>";
                else $return .= "<option value='".Globals::_e($key->code)."'>".Globals::_e($key->description)."</option>";
            }else{
                if($officeid == $key->code && $officeid != '') $return .= "<option value='".Globals::_e($key->code)."' selected>".Globals::_e($key->description)."</option>";
                else $return .= "<option value='".Globals::_e($key->code)."'>".Globals::_e($key->description)."</option>";
            }
            
        }

        return $return;
    }
    
    function getCategoryTrail($categoryid = "") {
        $return = "<option value=''> - All Category - </option>";
        $query = $this->db->query("SELECT DISTINCT Description FROM reports_item ")->result();
        foreach ($query as $key) {
            if($categoryid == $key->Description) $return .= "<option value='$key->Description'>$key->Description</option>";
            else $return .= "<option value='$key->Description'>$key->Description</option>";
        }

        return $return;
    }

    /*
    * end of new function added
    */
    function showManagement(){
        $return = array(""=>"Choose a Division ...");
        $q = $this->db->query("select managementid,description from code_managementlevel order by description")->result();
        foreach($q as $oo){
          $return[$oo->managementid] = $oo->description;    
        }
        return $return;
    }

    function getEmploymentCodeStatus($where_clause=""){
        $q = $this->db->query("SELECT * FROM code_status $where_clause");
        if($q->num_rows() > 0) return $q->result();
        else return "";
    }
	
	//Added 5-26-17
	function getManagementLevelDescription($mLevel=""){
        $return = "";
		$wC = "";
        if($mLevel) $wC .= " WHERE managementid='$mLevel'";
        $result = $this->db->query("SELECT managementid, description FROM code_managementlevel $wC")->result();
		foreach($result as $row)
		{
			$return = $row->description;
		}
        return $return;
    }
	
    function showPostion($positionid="", $caption=true){
        $return = array();
        $wC = "";
        if($positionid) $wC = " WHERE positionid='$positionid'";
        else{
            if($caption) $return = array(""=>"Choose a position ...");
        } 
        $q = $this->db->query("SELECT positionid,description FROM code_position $wC order by description")->result();
        foreach($q as $oo){
          $return[Globals::_e($oo->positionid)] = Globals::_e($oo->description);    
        }
        return $return;
    }
    function showPosDesc($pos){
        $return = "";
        $q = $this->db->query("SELECT description FROM code_position WHERE positionid='{$pos}'")->result();
        foreach($q as $val){
            $return = Globals::_e($val->description);
        }
        return $return;
    }
    function showCitizenship(){
        $return = array(""=>"Choose a citizenship ...");
        $q = $this->db->query("SELECT citizenid,description FROM code_citizenship ORDER BY description;")->result();
        foreach($q as $oo){
          $return[Globals::_e($oo->citizenid)] = Globals::_e($oo->description);    
        }
        return $return;
    }
    function showReligion(){
        $return = array(""=>"Choose a religion ...");
        $q = $this->db->query("SELECT religionid,description FROM code_religion ORDER BY description")->result();
        foreach($q as $oo){
          $return[Globals::_e($oo->religionid)] = Globals::_e($oo->description);    
        }
        return $return;
    }
    function showNationality(){
        $return = array(""=>"Choose a nationality ...");
        $q = $this->db->query("SELECT nationalityid,description FROM code_nationality ORDER BY description")->result();
        foreach($q as $oo){
          $return[Globals::_e($oo->nationalityid)] = Globals::_e($oo->description);    
        }
        return $return;
    }
    
    function regionlist($regid=""){
        $return = array(""=>"Choose a region ...");
        $q = $this->db->query("SELECT regDesc,regCode FROM refregion ORDER BY regCode")->result();
        foreach($q as $oo){
          $return[Globals::_e($oo->regCode)] = Globals::_e($oo->regDesc);    
        }
        return $return;
    }
    
    function regiondesc($regid=""){
        $return = "";
        $query = $this->db->query("SELECT * FROM refregion where regCode='$regid'");
        foreach($query->result() as $row){
            $return = $row->regDesc;
        }
        return $return;
    } 
    
    /*function regionlist($regid=""){
        $q = $this->db->query("SELECT region_name,region_code FROM regions ORDER BY region_code")->result();
        echo '<select class="chosen col-md-10" name="region">';
        
        echo "<option value=''>Choose a region ...</option>";
        foreach($q as $oo){
          $return[$oo->region_code] = $oo->region_name;    
          echo "<option value='{$oo->region_code'}">'{$oo->region_name}'</option>";
        }
        echo "</select>";
    }*/
    /*function provincelist($provid="",$regCode=""){
        $return = array(""=>"Choose a province ...");
        $q = $this->db->query("SELECT cpName,cpID FROM city_provinces where RegionCode = '$regCode' ORDER BY cpName")->result();
        foreach($q as $oo){
          $return[$oo->cpID] = $oo->cpName;    
        }
        return $return;
    }*/
    function provincelist($data){
        $provid=$data['provid'];
        $regCode=$data['regCode'];
        $q = $this->db->query("SELECT provDesc,provCode FROM refprovince where regCode = '$regCode' ORDER BY provDesc")->result();
            
        echo "<option value=''>Choose a province ...</option>";
        foreach($q as $oo){
            $val = $oo->provCode;
            $disp = $oo->provDesc;
          echo "<option value='$val' ". ($provid == $val? "selected":"")  .">$disp</option>";
        }
    }

    function provincedesc($prov=""){
        $return = "";
        $query = $this->db->query("SELECT * FROM refprovince where provCode='$prov'");
        foreach($query->result() as $row){
            $return = $row->provDesc;
        }
        return $return;
    } 
    
    function municipalitylist($data){
        $munid=trim($data['munid']);
        $ProvID=$data['ProvID'];
        // $q = $this->db->query("SELECT REPLACE(REPLACE(DistMunName,'Ã‘','&#241;'),'ñ','&#241;') as DistMunName,dmunID FROM district_municipalities Where ProvID = '$ProvID'  ORDER BY DistMunName")->result();
        $q = $this->db->query("SELECT citymunDesc,citymunCode FROM refcitymun Where provCode = '$ProvID'  ORDER BY citymunDesc")->result();
        echo "<option value=''>Choose a municipality ... </option>";
        foreach($q as $oo){
            $val = $oo->citymunCode;
            $val = trim($val);
            $disp = html_entity_decode(htmlentities($oo->citymunDesc));
          echo "<option value='$val' ". ($munid == $val? "selected":"") .">$disp</option>";
        }
    }

    function barangaylist($data){
        $brgyid=trim($data['brgyid']);
        $munid=$data['munid'];
        // $q = $this->db->query("SELECT REPLACE(REPLACE(DistMunName,'Ã‘','&#241;'),'ñ','&#241;') as DistMunName,dmunID FROM district_municipalities Where ProvID = '$ProvID'  ORDER BY DistMunName")->result();
        $query = $this->db->query("SELECT brgyDesc,brgyCode FROM refbrgy Where citymunCode = $munid ORDER BY brgyDesc");
        echo "<option value=''>Choose a barangay ...</option>";
        if($query && $query->num_rows()){
            $q=$query->result();
            foreach($q as $oo){
                $val = $oo->brgyCode;
                $val = trim($val);
                $disp = strtoupper(html_entity_decode(htmlentities($oo->brgyDesc)));
              echo "<option value='$val' ". ($brgyid == $val? "selected":"")  .">$disp</option>";
            }
        }
    }
    
    function barangaydesc($barangay=""){
        $return = "";
        $query = $this->db->query("SELECT * FROM refbrgy where brgyCode='$barangay'");
        foreach($query->result() as $row){
            $return = $row->brgyDesc;
        }
        return $return;
    } 
    
    function municipalitydesc($municipality=""){
        $return = "";
        $query = $this->db->query("SELECT * FROM refcitymun where citymunCode='$municipality'");
        foreach($query->result() as $row){
            $return = $row->citymunDesc;
        }
        return $return;
    } 

    function changeaddr($data){
        $employeeid = $data['employeeid'];
        if($data['changeaddr'] == 'regionaladdr'){
            $update = $this->db->query("UPDATE employee set provaddr = '', cityaddr = '', barangay='', zip_code = '' where employeeid ='$employeeid'");
        }else if($data['changeaddr'] == 'provaddr'){
            $update = $this->db->query("UPDATE employee set cityaddr = '', barangay='', zip_code = '' where employeeid ='$employeeid'");
        }else if($data['changeaddr'] == 'cityaddr'){
            $update = $this->db->query("UPDATE employee set barangay='', zip_code = '' where employeeid ='$employeeid'");
        }else if($data['changeaddr'] == 'permaRegion'){
            $update = $this->db->query("UPDATE employee set permaProvince = '', permaMunicipality = '', permaBarangay='', permaZipcode = '' where employeeid ='$employeeid'");
        }
        else if($data['changeaddr'] == 'permaProvince'){
            $update = $this->db->query("UPDATE employee set permaMunicipality = '', permaBarangay='', permaZipcode = '' where employeeid ='$employeeid'");
        }
        else if($data['changeaddr'] == 'permaMunicipality'){
            $update = $this->db->query("UPDATE employee set permaBarangay='', permaZipcode = '' where employeeid ='$employeeid'");
        }
    } 
	
    function showrequestform(){
        $returns = array(""=>"NA");
        $this->db->select("code_request,description");
        $this->db->order_by("is_leave","asc");
        $this->db->order_by("description","asc");
        $q = $this->db->get("code_request_form"); 
        
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->code_request] = $row->description;
        }
        return $returns;
    }
    
    function showStatus(){
        $query = $this->db->query("SELECT * FROM code_status");
        return $query;
    }
    
    function showClearanceStatus(){
        $query = $this->db->query("SELECT * FROM clearance_type ORDER BY description ASC");
        return $query;
    }
    
    function showdepartment($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("code,description");
        $this->db->order_by("description","asc");
        $q = $this->db->get("code_department"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[Globals::_e($row->code)] = Globals::_e($row->description);
        }
        return $returns;
    }

    function showcampuscompany($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("id,company_description");
        $this->db->order_by("company_description","asc");
        $q = $this->db->get("campus_company"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[Globals::_e($row->id)] = Globals::_e($row->company_description);
        }
        return $returns;
    }

    public function employmentSalary($employeeid){
        $q_salary = $this->db->query("SELECT * FROM employee_employment_status_history WHERE employeeid = '$employeeid' ORDER BY TIMESTAMP DESC LIMIT 1 ");
        if($q_salary->num_rows() > 0) return Globals::_e($q_salary->row()->salary);
        else return false;
    }

    public function getPayrollCutoffSelect($id){
        $result = $this->db->query("SELECT id,startdate,enddate FROM payroll_cutoff_config")->result();
        $return = "<option value=''></option>";
        foreach ($result as $value) {
            if($id === $value->id){
                $return .= "<option selected value='$value->id'>$value->startdate ~ $value->enddate</option>";
            }
            else{
                $return .= "<option value='$value->id'>$value->startdate ~ $value->enddate</option>";
            }
        }
        return $return;
    }

    public function getPayrollCutoffDescription($id){
        $result = $this->db->query("SELECT id,startdate,enddate FROM payroll_cutoff_config WHERE id='$id'")->result();
        $return = "";
        foreach ($result as $value) {
            $return = $value->startdate." ~ ".$value->enddate;
        }
        return $return;
    }

    function showoffice($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("code,description");
        $this->db->order_by("description","asc");
        $q = $this->db->get("code_office"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[Globals::_e($row->code)] = Globals::_e($row->description);
        }
        return $returns;
    }

    function showOB($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("id,type");
        $this->db->order_by("id","asc");
        $q = $this->db->get("ob_type_list"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[Globals::_e($row->id)] = Globals::_e($row->type);
        }
        return $returns;
    }

    function showcampus($caption = "")
    {
        $return = array();

        $return[""] = $caption; // add by justin (with e) for ica-hyperion 21671
        $query = $this->db->query("SELECT code,description FROM code_campus ORDER by code")->result();
        foreach ($query as $key => $row) {
             $return[$row->code] = $row->description;
        }
       return $return;

    }
    
    function showstatusdescription()
    {
        $return = array();
        $query = $this->db->query("SELECT code,description FROM code_status ORDER BY code ASC")->result();
        foreach ($query as $key => $row) {
            $return[$row->code] = $row->description;
        }
        return $return;

    }

    // function showreportseduclevel($select,$code)
    // {
    //     $return = array();
    //     $query = $this->db->query("SELECT level,ID FROM reports_item Where reportcode='ECT' ORDER BY level")->result();
    //     foreach ($query as $key => $row) {
    //         $return[$row->level] = $row->level;
    //     }
    //     return $return;

    // }

    function showreportseligibilities($select,$code)
    {
        $return = array();
        $query = $this->db->query("SELECT level,ID FROM reports_item Where reportcode='$code' ORDER BY level")->result();
        foreach ($query as $key => $row) {
            $return[$row->level] = $row->level;
        }
        return $return;

    }

    function showreportseduclevelseminar($code)
    {
        $return = array();
        $query = $this->db->query("SELECT level,ID FROM reports_item Where reportcode='$code' ORDER BY level")->result();
        foreach ($query as $key => $row) {
            $return[$row->level] = $row->level;
        }
        return $return;

    }
    function showRemarks()
    {
        $query = $this->db->query("SELECT * FROM code_remarks")->result_array();
        return $query;
    }
    function showreportseduclevel($caption='',$code){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("level,ID");
        $this->db->order_by("level");
        $this->db->where("reportcode",$code);
        $q = $this->db->get("reports_item"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[Globals::_e($row->ID)] = strtoupper(Globals::_e($row->level));
        }
        // echo "<pre>"; print_r($this->db->last_query()); die;
        return $returns;
    }

    function showReportsItemDesc($report_code='',$id=''){
        $q_report = $this->db->query("SELECT Description, level FROM reports_item WHERE reportcode = '$report_code' AND ID = '$id' ");
        if($q_report->num_rows() > 0) return $q_report->row()->level;
        else return false;
    }

    function checkIfUsernameAndUserIdEqual($username='', $id=''){
        $q_report = $this->db->query("SELECT username FROM user_info WHERE id = '$id' ");
        $uname = $q_report->row()->username;
        if ($uname != $username) {
            return $q_report->row()->username;
        }else{
           return false;
       }
    }

    function showSchools(){
        $query = $this->db->query("SELECT * FROM code_school")->result_array();
        return $query;
    }

    function showSchoolsDesc($id){
        $query = $this->db->query("SELECT * FROM code_school WHERE schoolid = '$id'");
        if($query->num_rows() > 0) return $query->row()->description;
        else return '';
    }

    function showreportsvenue($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("venue,ID");
        $this->db->order_by("venue");
        $q = $this->db->get("reports_item"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->venue] = $row->venue;
        }
        return $returns;
    }

    function showReasonForLeaving(){
        $returns = array();
        $this->db->select("*");
        $this->db->order_by("id");
        $this->db->where("reportcode","EH");
        $q = $this->db->get("reports_item"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[Globals::_e($row->level)] = Globals::_e($row->level);
        }
        return $returns;
    }

    function showCodeLang($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("id,language");
        $this->db->order_by("language_code");
        $this->db->where("status","1");
        $q = $this->db->get("code_language"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[Globals::_e($row->language)] = Globals::_e($row->language);
        }
        return $returns;
    }

    function showLiteracy($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("id,literacy");
        $this->db->order_by("literacy");
        $this->db->where("status","1");
        $q = $this->db->get("code_language"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[Globals::_e($row->literacy)] = Globals::_e($row->literacy);
        }
        return $returns;
    }

    function showReportsItemLiteracy(){
        $returns = array();
        $this->db->select("*");
        $this->db->order_by("id");
        $this->db->where("reportcode","SCTTLITERACY");
        $q = $this->db->get("reports_item"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[Globals::_e($row->level)] = Globals::_e($row->level);
        }
        return $returns;
    }

    function showFluency($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("id,fluency");
        $this->db->order_by("fluency");
        $this->db->where("status","1");
        $q = $this->db->get("code_language"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[Globals::_e($row->fluency)] = Globals::_e($row->fluency);
        }
        return $returns;
    }

    function emplang($id){
        $query = $this->db->query("SELECT language FROM employee_language WHERE id = '{$id}'")->result_array();
        // $d = explode(',', $query['0']['language']);
        return $query['0']['language'];
    }

    function empflu($id){
        $query = $this->db->query("SELECT fluency FROM employee_language WHERE id = '{$id}'")->result_array();
        // $d = explode(',', $query['0']['language']);
        return $query['0']['fluency'];
    }

    function emplits($id){
        $query = $this->db->query("SELECT literacy FROM employee_language WHERE id = '{$id}'")->result_array();
        $d = explode(',', $query['0']['literacy']);
        return $d;
    }

    function applicantlit($id){
        $query = $this->db->query("SELECT literacy FROM applicant_language WHERE id = '{$id}'")->result_array();
        $d = explode(',', $query['0']['literacy']);
        return $d;
    }

    function applicantflu($id){
        $query = $this->db->query("SELECT fluency FROM applicant_language WHERE id = '{$id}'")->result_array();
        return $query['0']['fluency'];
    }

    function applicantlang($id){
        $query = $this->db->query("SELECT language FROM applicant_language WHERE id = '{$id}'")->result_array();
        return $query['0']['language'];
    }
    // function showCodeSctt($caption=''){
    //     $returns = array();
    //     if (isset($caption)) {
    //         $returns = array(""=>$caption);
    //     }
    //     $this->db->select("id,subj_code,description");
    //     $this->db->order_by("subj_code");
    //     $this->db->where("status","1");
    //     $q = $this->db->get("code_subj_competent_to_teach"); 
    //     for($t=0;$t<$q->num_rows();$t++){
    //       $row = $q->row($t);
    //       $returns[$row->id] = $row->subj_code;
    //     }
    //     return $returns;
    // }
    
    function showdepartmentholiday($hol=""){
        $returns = array();
        $param = "";
        if($hol)    $param = " AND b.holi_cal_id='$hol'";
        $q = $this->db->query("SELECT a.code,a.description,b.* FROM code_office a LEFT JOIN holiday_inclusions b ON a.code = b.dept_included $param ORDER BY a.description"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->code] = $row->description."|".$row->permanent."|".$row->prob."|".$row->contractual;
        }
        return $returns;
    }

    function listDepartmentsAffectedByHoliday($holiday_id){
        $listAffecteds = array();
        $sql = "SELECT dept_included from holiday_inclusions WHERE holi_cal_id = '".$holiday_id."' ";
        $result = $this->db->query($sql)->result_array();
        foreach ($result as $key => $value) {
            $listAffecteds[Globals::_e($key)] = Globals::_e($value["dept_included"]);
        }
        return $listAffecteds;
    }

    function showcutofdatebyid($cid,$employeeid=""){
        $returns = array();
        $this->db->select("cdate");
        $this->db->where("id",$cid);
        $this->db->order_by("cdate","asc");
        $q = $this->db->get("cutoff_details"); 
        
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->cdate] = date("d M D",strtotime($row->cdate));
        }
        
        if($employeeid && $cid){
            $qe = $this->db->query("select DISTINCT cdate from employee_schedule_adjustment where cutoffid='{$cid}' and employeeid='{$employeeid}'");
            for($u=0;$u<$qe->num_rows();$u++){
              $row = $qe->row($u);  
              $returns[$row->cdate] = date("d M D",strtotime($row->cdate));  
            }
        }
        array_multisort($returns);
        return $returns;
    }
    function showincomebase(){
        $returns = array();
        $this->db->select("income_base,description");
        $this->db->order_by("description","asc");
        $q = $this->db->get("code_income_base"); 
        
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->income_base] = $row->description;
        }
        return $returns;
        
    }
    function getincomebase($incomebase){
        $returns = "";
        $this->db->select("income_base,description");
        $this->db->where("income_base",$incomebase);
        $this->db->order_by("description","asc");
        $q = $this->db->get("code_income_base"); 
        
        if($q->num_rows()>0){
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns = $row->description;
        }
        }
        return $returns;
    }
    function showtaxstatus($inp=''){
        $returns = array();
        $this->db->select("status_code,status_desc,status_exemption");
        $this->db->order_by("status_desc","asc");
        $q = $this->db->get("code_tax_status"); 
        
        if($inp==''){
            for($t=0;$t<$q->num_rows();$t++){
              $row = $q->row($t);
              $returns[$row->status_code] = $row->status_desc;
            }
            return $returns;
        }

        return $q;
    }
    function gettaxstatus($taxstatus=""){
        $returns = "";
        $this->db->select("status_code,status_desc");
        $this->db->where("status_code",$taxstatus);
        $this->db->order_by("status_desc","asc");
        $q = $this->db->get("code_tax_status"); 
        if($q->num_rows()>0){
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns .= $row->status_desc;
        }
        }
        return $returns;
    }
    
    function gettaxstatuscode($taxstatus=""){
        $returns = "";
        $q = $this->db->query("SELECT status_code,status_desc FROM code_tax_status WHERE status_code='$taxstatus'")->result();
        foreach($q as $row){
            $returns = GLOBALS::_e($row->status_desc);
        }
        
        return $returns;
    }
    function getemployeetype($emptype=""){
        $returns = "";
        $q = $this->db->query("SELECT code,description FROM code_type WHERE code='$emptype'")->result();
        foreach(GLOBALS::result_XHEP($q) as $row){
            $returns = $row->description;
        }
        
        return $returns;
    }
    function getrelation($emptype=""){
        $returns = "";
        $q = $this->db->query("SELECT relationshipid,description FROM code_relationship WHERE relationshipid='$emptype'")->result();
        foreach($q as $row){
            $returns = Globals::_e($row->description);
        }
        
        return $returns;
    }

    function getemployeestatus($empstatus=""){
        $returns = "";
        $q = $this->db->query("SELECT code,description FROM code_status WHERE code='$empstatus'")->result();
        foreach($q as $row){
            $returns = $row->description;
        }
        return $returns;
    }
    
    function getemployeecol($employeeid="",$col=""){
        $return = "";
        $query = $this->db->query("SELECT $col FROM employee WHERE employeeid='$employeeid'")->result();
        foreach($query as $row){
            $return = $row->$col;
        }
        return $return;
    }
    
    function getemployeedepartment($deptid=''){
        $mydept = "";
        $row = $this->db->query("SELECT description FROM code_department WHERE code = '{$deptid}' ")->result();
        foreach ($row as $key => $val) {
            $mydept = GLOBALS::_e($val->description);
        }
        if($deptid == "ALL")    $mydept = " ALL Department";
        return $mydept;
        // return "SELECT description FROM code_department WHERE CODE =" . $this->db->escape($deptid) . " ";
        // return $this->db->escape($deptid);
    }

    function getemployeedepartmentbyCode($deptid=''){
        $mydept = "";
        $row = $this->db->query("SELECT description FROM code_department WHERE code = '{$deptid}' ")->result();
        foreach ($row as $key => $val) {
            $mydept = GLOBALS::_e($val->description);
        }
        if($deptid == "ALL")    $mydept = " ALL Department";
        else if($deptid == '') $mydept = "All Department/College";
        return $mydept;
        // return "SELECT description FROM code_department WHERE CODE =" . $this->db->escape($deptid) . " ";
        // return $this->db->escape($deptid);
    }

    function getemployeeofficeMultiple($office=''){
        $myoffice = "";
        $row = $this->db->query("SELECT code,description FROM code_office WHERE FIND_IN_SET(`code`, '$office') ")->result();
        foreach ($row as $key => $val) {
            if($myoffice != '') $myoffice .= ', '.$val->description;
            else $myoffice = $val->description;
        }
        return Globals::_e($myoffice);
    }

     function showPosDescMultiple($pos){
        $return = "";
        $q = $this->db->query("SELECT description FROM code_position WHERE FIND_IN_SET(`positionid`, '$pos')")->result();
        foreach($q as $val){
            if($return != '') $return .= ', '.$val->description;
            else $return = $val->description;
        }
        return $return;
    }

    function getemployeeoffice($office=''){
        $myoffice = "";
        $row = $this->db->query("SELECT code,description FROM code_office WHERE code ='{$office}' ")->result();
        foreach ($row as $key => $val) {
            $myoffice = $val->description;
        }
        if($office == "ALL")    $mydept = " ALL Office/Unit";
        return Globals::_e($myoffice);
    }
    
    function listEmpDept($deptid=''){
        $mydept = "<option value=''>- All Department -</option>";
        $row = $this->db->query("SELECT code,description FROM code_office")->result();
        foreach ($row as $val) {
            $mydept .= "<option value='{$val->code}'>{$val->description}</option>";
        }
        return $mydept;
    }

    function showshiftschedule(){
        $return = array(""=>"Choose Schedule");
        
        $this->db->select("schedid,description,schedcode,tardy_start");
        $this->db->order_by("description","");
        $q = $this->db->get("code_schedule"); 
        # echo $this->db->last_query();
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $return[Globals::_e($row->schedid)] = Globals::_e($row->description);
        }
        return $return;
        // echo "<pre>"; print_r($return); die;
        
    }
    function showholiday(){
        $return = array(""=>"regular day");
        
        $this->db->select("code,description,holiday_type");
        $this->db->order_by("description","asc");
        $q = $this->db->get("code_holidays"); 
        # echo $this->db->last_query();
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $return[$row->code] = $row->description . " (".$row->holiday_type.")";
        }
        return $return;
        
    }
    function showincome(){
        $returns = array(""=>"");
        $this->db->select("code_income,description");
        $this->db->order_by("description","asc");
        $q = $this->db->get("incomes"); 
        if($q->num_rows()>0){
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->code_income] = $row->description;
        }
        }
        return $returns;
        
    }
    
    function showdeductions(){
        $returns = array(""=>"");
        $this->db->select("code_deduction,description");
        $this->db->order_by("description","asc");
        $q = $this->db->get("deductions"); 
        
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->code_deduction] = $row->description;
        }
        return $returns;
        
    }
    
    function reformstring($str="",$num=2,$fill="0",$isback=0){
        $tmp = $str;
        while(strlen($tmp)<$num){
           if($isback) $tmp .= $fill;
           else $tmp = $fill.$tmp;  
        }
        return $tmp;
    }
    function showhours($hr=""){
        $return = "<option value=''></option>";
        $u = 1;
        while($u<13){
          $return .= "<option".($hr==$u ? " selected" : "")." value='$u'>".$this->reformstring($u)."</option>";    
          $u++;  
        }
        return $return;
    }
    function showminutes($min="",$detailed = false){
        $return = "<option value=''></option>";
        $u = array(00,15,30,45);
        if($detailed){
            $u = array();
            for($t=0;$t<60;$t++){
               array_push($u,$this->reformstring($t)); 
            }
        }
        
        foreach($u as $mins){
          $return .= "<option".(($min==$mins && $min!="") ? " selected" : "")." value='$mins'>".$this->reformstring($mins)."</option>";      
        }
        return $return;
    }
    function showstat($stats=""){
        $return = "<option value=''></option>";
        $u = array("AM","PM");
        foreach($u as $stat){
          $return .= "<option".($stat==$stats ? " selected" : "")." value='$stat'>$stat</option>";      
        }
        return $return;
    }
    function sreformstring($str="",$ende=0){
        $return = $str;
        $equi = array("~"=>":curl:","#"=>":num:","@"=>":at:","$"=>":dollar:","%"=>":percent:","^"=>":roof:","&"=>":amp:","*"=>":ast:","("=>":opar:",")"=>":cpar:","_"=>":uscore:","+"=>":plus:","-"=>":minus:","/"=>":fslash:","="=>":equal:");
        foreach($equi as $key=>$value){
          $s = $ende==1 ? $key : $value;
          $t = $ende==1 ? $value : $key;
          $return = str_replace($s,$t,$return);  
        }
        return $return;
    }
    function showgender($all=''){
        # return array("MALE"=>"MALE","FEMALE"=>"FEMALE");
        if($all) $return = array(""=>"All Gender");
        else $return = array(""=>"Choose a Gender ...");
        $q = $this->db->query("SELECT genderid,description FROM code_gender order by description")->result();
        foreach($q as $oo){
          $return[Globals::_e($oo->genderid)] = Globals::_e($oo->description);    
        }
        return $return;
    }
    function show_blood(){
        $return = array(""=>"Choose a Blood Type ...");
        $q = $this->db->query("SELECT bloodid, description FROM code_blood order by description")->result();
        foreach($q as $oo){
          $return[Globals::_e($oo->bloodid)] = Globals::_e($oo->description);    
        }
        return $return;
    }
    function show_languages(){
        $return = array(""=>"Choose a Languages ...");
        $q = $this->db->query("SELECT id, language FROM employee_language order by language")->result();
        foreach($q as $oo){
          $return[$oo->id] = $oo->language;    
        }
        return $return;
    }
    
    function showemployeetype(){
        //return array("TEACHING LOAD"=>"TEACHING LOAD","NON-TEACHING LOAD"=>"NON-TEACHING LOAD");
        $return = array(""=>"Choose a type ...");
        $q = $this->db->query("SELECT code,description,schedid FROM code_type  WHERE schedid <> '' order by description ")->result();
        foreach($q as $oo){
          $return[Globals::_e($oo->code)] = Globals::_e($oo->description);    
        }
        return $return;
        // echo "<pre>"; print_r($this->db->last_query()); die;
    }

    function getCodeType(){
        //return array("TEACHING LOAD"=>"TEACHING LOAD","NON-TEACHING LOAD"=>"NON-TEACHING LOAD");
        $return = array(""=>"Choose Schedule");
        $q = $this->db->query("SELECT code,description,schedid FROM code_type  WHERE schedid <> '' order by description ")->result();
        foreach($q as $oo){
          $return[Globals::_e($oo->schedid)] = Globals::_e($oo->description);    
        }
        return $return;
        // echo "<pre>"; print_r($this->db->last_query()); die;
    }
    
    //AIMS INTEGRATION
     function showAimsDepartment(){
         $return = array(""=>"Choose a Department ...");
         $q = $this->db->query("SELECT CODE AS codes,description FROM `_depttypes` order by codes")->result();
         foreach($q as $oo){
           $return[Globals::_e($oo->codes)] = Globals::_e($oo->description);    
         }
         return $return;   
        // $return = "<option value=''>- Select Department-</option>";
        // $query = $this->db->query("SELECT CODE AS codes,description FROM ICADasma.`_depttypes`");
        // foreach($query->result() as $row){
        //     if($row->codes)    $sel = " selected";
        //     else                            $sel = " ";
        //     $return .= "<option value='".$row->codes."' $sel>".$row->description."</option>";
        // }
        // return $return;
    }
    
    /* ADDED BY JUSTIN - 02/09/2015 */
    function showEmployee($emp=""){
        $return = "<option value=''>- Select Employee -</option>";
        $query = $this->db->query("SELECT *,CONCAT(lname,', ',fname,' ',mname) as fullname FROM employee");
        foreach($query->result() as $row){
            if($emp && $row->employeeid == $emp)    $sel = " selected";
            else                            $sel = " ";
            $return .= "<option value='".$row->employeeid."' $sel>".$row->fullname."</option>";
        }
        return $return;
    }
            
    function showDay(){
        $strday = array(""=>"-Select Day-", "M"=>"Monday", "T"=>"Tuesday", "W"=>"Wednesday", "TH"=>"Thursday", "F"=>"Friday", "S"=>"Saturday", "SUN"=>"Sunday");
        $return = "";
        foreach($strday as $key=>$val){
            $return .= "<option value=$key>$val</option>";
        }
        return $return;
    }
    
    function showShift($code){
        $return = "";
        $sql = $this->db->query("SELECT description FROM code_type where code='$code'");
        foreach($sql->result() as $row){
            $return = $row->description;
        }
        return $return;
    }
    function showOfficialSchedHistory($id){
        $sql = $this->db->query("SELECT * FROM employee_official_schedule_history WHERE employeeid='$id' ORDER BY timestamp DESC");
        return $sql->result();
    }
    /* END */
    
    function idxval($dayofweek=''){
        $return = "";
        switch($dayofweek){
            case "M" : $return = "1";break;
            case "T" : $return = "2";break;
            case "W" : $return = "3";break;
            case "TH" : $return = "4";break;
            case "F" : $return = "5";break;
            case "S" : $return = "6";break;
            case "SUN" : $return = "0";break;
            default:
                $return = "";break;
        }
        return $return;
    }
    /* END */
    
    function showemployeestatus($content=""){
        //return array("FULL-TIME"=>"FULL-TIME","PART-TIME"=>"PART-TIME");
		if($content) $return = array(""=>$content);
        else $return = array(""=>"Choose a status ...");
        $q = $this->db->query("SELECT code,description FROM code_status order by description")->result();
        foreach($q as $oo){
          $return[Globals::_e($oo->code)] = Globals::_e($oo->description);    
        }
        return $return;
    }

    function show_separation_type($content=""){
        //return array("FULL-TIME"=>"FULL-TIME","PART-TIME"=>"PART-TIME");
        if($content) $return = array(""=>$content);
        else $return = array(""=>"Select Separation Type");
        $q = $this->db->query("SELECT id,description FROM separation_data order by description")->result();
        foreach($q as $oo){
          $return[Globals::_e($oo->id)] = Globals::_e($oo->description);    
        }
        return $return;
    }
    function showcivilstatus(){
        return array("SINGLE"=>"SINGLE","MARRIED"=>"MARRIED","WIDOW"=>"WIDOW/WIDOWER");
    }

    function listCivilStatus(){
        $arrStatus = array("" => "Choose a status ...");
        $res = $this->db->query("SELECT code, description FROM code_civil_status ORDER BY code")->result();
        foreach ($res as $key) {
            $arrStatus[$key->code] =  Globals::_e($key->description);
        }
        return $arrStatus;
    }

    function listTitle(){
        $arrTitle = array("" => "Choose a title");
        $res = $this->db->query("SELECT titleid, description FROM code_title ORDER BY description")->result();
        foreach ($res as $key) {
            $arrTitle[$key->titleid] =  Globals::_e($key->description);
        }
        return $arrTitle;
    }

    function listRankType(){
        $arrRankType = array("" => "Choose Rank Type ...");
        $q = $this->db->query("SELECT id, description FROM rank_code_type ORDER BY id")->result();
        foreach($q as $er){
            $arrRankType[$er->id] = Globals::_e($er->description);
        }
        
        return $arrRankType;
    }

    function listRank(){
        $arrRank = array("" => "Choose Rank ...");
        $q = $this->db->query("SELECT id, description FROM rank_code ORDER BY id")->result();
        foreach($q as $er){
            $arrRank[$er->id] = Globals::_e($er->description);
        }
        
        return $arrRank;
    }

    function listRankSet(){
        $arrRankSet = array("" => "Choose Rank ...");
        $q = $this->db->query("SELECT id, description FROM rank_code ORDER BY id")->result();
        foreach($q as $er){
            $arrRankSet[$er->id] = Globals::_e($er->description);
        }
        
        return $arrRankSet;
    }

    function listRelation(){
        $arrRelation = array("" => "Choose a relation ...");
        $q = $this->db->query("SELECT relationshipid,description FROM code_relationship ORDER BY relationshipid")->result();
        foreach($q as $er){
            $arrRelation[Globals::_e($er->relationshipid)] = Globals::_e($er->description);
        }
        
        return $arrRelation;
    }
    
    function civilstatusdesc($stat=""){
        $return = "";
        $query = $this->db->query("SELECT * FROM code_civil_status where code='$stat'");
        foreach($query->result() as $row){
            $return = $row->description;
        }
        return $return;
    }
    
    function citizenshipdesc($stat=""){
        $return = "";
        $query = $this->db->query("SELECT * FROM code_citizenship where citizenid='$stat'");
        foreach($query->result() as $row){
            $return = $row->description;
        }
        return $return;
    }  
    
    function religiondesc($stat=""){
        $return = "";
        $query = $this->db->query("SELECT * FROM code_religion where religionid='$stat'");
        foreach($query->result() as $row){
            $return = $row->description;
        }
        return $return;
    }
    
    function nationalitydesc($stat=""){
        $return = "";
        $query = $this->db->query("SELECT * FROM code_nationality where nationalityid='$stat'");
        foreach($query->result() as $row){
            $return = $row->description;
        }
        return $return;
    }
    
    function genderdesc($stat=""){
        $return = "No Gender";
        $query = $this->db->query("SELECT * FROM code_gender where genderid='$stat'");
        foreach($query->result() as $row){
            $return = $row->description;
        }
        return $return;
    }    

    function listSchoolYears(){
        $sqlsy = "SELECT DISTINCT A.SY FROM ICADasma.tblStatusHistory AS A WHERE A.SY <> '' AND A.SY IS NOT NULL ORDER BY A.SY DESC";
        return $this->db->query($sqlsy)->result_array();
    }

    function listHolidayFreqs(){
        $arrFreq = array(0 => "Select frequency");
        $res = $this->db->query("SELECT freq_id, freq_description FROM code_holiday_freq")->result();
        foreach ($res as $key) {
            $arrFreq[$key->freq_id] = $key->freq_description;
        }
        return $arrFreq;
    }

    function showadjustment_code($issched=false){
        $return = array();
        $q = $this->db->query("SELECT `code`,`description`,rate,is_sched,salary_type FROM code_adjustment".($issched ? " where is_sched='1'" : ""));
        for($i=0;$i<$q->num_rows();$i++){
           $mrow = $q->row($i); 
           $return[$mrow->code] = $mrow->description;  
        }
        return $return; 
    }
    function counthours($ft,$et){
        $q = $this->db->query("SELECT TIMEDIFF('$et','$ft') as totdif;");
        $row = $q->row(0);
        list($h,$m,$s) = explode(":",$row->totdif);
        $timetot = substr($h,0,1)=="0" ? substr($h,1,1) : $h;
        $mins = substr($m,0,1)=="0" ? substr($m,1,1) : $m;
        $timetot += $mins/60;
        return $timetot;
    }
    function displaytablefields(&$sheet,$r,$c,$coltitle,$fields){
        foreach($fields as $colinfo){ 
         list($caption,$span,$width,$extra) = $colinfo;	
         if($span > 1) $sheet->setMerge($r, $c, $r, (($c-1) + $span));	
         $sheet->write($r,$c,$caption,$coltitle);
         if($extra){
           $sheet->writeNote($r,$c,$extra);	 
         }
         $sheet->setColumn($c,$c,$width);	
         $c += $span;
        }
    } 
    function changeenye($enye = ""){
    	$return = $enye;
    	$return = str_replace("Ãƒâ€˜","Ã‘",$return);
        $return = str_replace("Ã‘","Ñ",$return); 
        $return = str_replace("Ã±","ñ",$return);
    	return $return;
    }
    function htmlchangeenye($enye = ""){
	       $return = $enye;
	       $return = str_replace("Ñ","&Ntilde;",$return);
           $return = str_replace("Ãƒâ€˜","Ã‘",$return);
           $return = str_replace("Ã‘","&Ntilde;",$return);
           $return = str_replace("??","&Eacute;",$return);
	       return $return;
   }
    function getdays(){
        $return = array();
    	for($t=1;$t<=31;$t++){
    	   $return[$this->reformstring($t)] = $this->reformstring($t);
    	}
    	return $return;
    }
    function getmonths(){
        $return = array();
    	for($t=1;$t<13;$t++){
    	   $return[$this->reformstring($t)] = date("M",strtotime("2001-".$this->reformstring($t)."-01"));
    	}
    	return $return;
    }
    function getyears($f="",$limit=10,$i = false){
        $return = array();
        $f = $f ? $f : date("Y");
        for($y=$f;($i ? ($y<=($f+$limit)) : ($y>=($f-$limit)));($i ? $y++ : $y--)){
          $return[$y] = date("Y",strtotime("$y-01-01"));  
        }
    	return $return;
    }
    function getperiodbycutoff($cutoffid=""){
        $sql = $this->db->query("select cutoff_period from cutoff_summary where id='{$cutoffid}'");
        if($sql->num_rows()>0){
          $return = $sql->row($o)->cutoff_period;      
        }
    	return $return;
    }
    function excel_column($col_number,$row_number) {
        if( ($col_number < 0) || ($col_number > 701)) die('Column must be between 0(A) and 701(ZZ)');
        if($col_number < 26) {
        return(chr(ord('A') + $col_number) . ($row_number+1));
        } else {
        $remainder = floor($col_number / 26) - 1;
        return(chr(ord('A') + $remainder) . excel_column($col_number % 26) . ($row_number+1));
        }
    }
    function showcutoffprocessed(){
        $return = array(""=>"DISPLAY ALL");
        $q = $this->db->query("SELECT a.`id`,CONCAT(b.description,' - ',DATE_FORMAT(datefrom,'%M %d, %Y'),' - ',DATE_FORMAT(dateto,'%M %d, %Y')) as `description` FROM cutoff_summary a inner join code_income_base b on b.income_base=a.cutoff_type where a.is_process='1' ORDER BY a.id DESC");
        for($i=0;$i<$q->num_rows();$i++){
           $mrow = $q->row($i); 
           $return[$mrow->id] = $mrow->description;  
        }
        return $return; 
    }
    function showusertype($utset = "", $type = ""){
        $return = "<option value=''>Select an User Type</option>";
        $wc = "";
        if ($type != "") $wc = "WHERE `type` = '$type'";
        $utype = $this->db->query("SELECT * FROM user_type $wc")->result_array();
        
        foreach($utype as $ut){
            $selected = ($utset == $ut['code'])? "selected":"";
            $return .= "<option value='".$ut['code']."' ".$selected.">".$ut['description']."</option>";
        }
        // echo"<pre>";print_r($return);die;
        return $return;
    }
    function showrequesttype($rtypeid){
        $return = "";
        $utype = $this->db->query("select id,request_code,description from code_request_type")->result();
        foreach($utype as $ut){
            $return .= "<option".($rtypeid==$ut->request_code?" selected":"")." value='{$ut->request_code}'>{$ut->description}</option>";
        }
        return $return;
    }
    function showrelation($rtypeid){
        $return = "<option value=''></option>";
        $utype = $this->db->query("select relationshipid,description from code_relationship")->result();
        foreach($utype as $ut){
            $return .= "<option".($rtypeid==$ut->relationshipid?" selected":"")." value='{$ut->relationshipid}'>{$ut->description}</option>";
        }
        return $return;
    }

    function setHolidayAffectedDepartments($hol_id,$depts,$permanent,$prob,$contractual){
        $temp1 = $temp2 = $temp3 = "";
        $queDel = "DELETE FROM holiday_inclusions WHERE holi_cal_id = {$hol_id}";
        $this->db->query($queDel);
        if (!empty($depts)) {
            foreach ($depts as $key => $deptvalue) {
                foreach($permanent as $pkey=>$pval){
                    $pvale = explode("~",$pval);
                    if($pvale[0] == $deptvalue){  
                        $temp1 = $pval;break;
                    }else
                        $temp1 = "";
                }
                foreach($prob as $pkey=>$pval){
                    $prval = explode("~",$pval);
                    if($prval[0] == $deptvalue){ 
                        $temp2 = $pval;break;
                    }else
                        $temp2 = "";
                }
                foreach($contractual as $pkey=>$pval){
                    $cont = explode("~",$pval);
                    if($cont[0] == $deptvalue){  
                        $temp3 = $pval;break;
                    }else
                        $temp3 = "";
                }
                $queAdd = "INSERT INTO holiday_inclusions VALUES({$hol_id},'{$deptvalue}','$temp1','$temp2','$temp3')";
                $this->db->query($queAdd);
            }// end foreach
        }// end if
    }// end function

    function checkIfInList($needle, $listVar){
        $inList = false;
        foreach ($listVar as $key => $listItem) {
            if ($needle == $listItem) {
                $inList = true;
                break;
            }
        }
        return $inList;
    }
    
    function setPass(){
        return "327ycaza";
    }
    
    function messages($cat="",$param = "",$dfrom = "",$dto = ""){
    $query = "";        
    $whereClause = "";
   # if($this->session->userdata("userid") != 2){
    if($param) $whereClause .= " AND a.id='$param'";
    if($cat)   $whereClause .= " AND a.status='$cat'";
    if(!empty($dfrom) && !empty($dto)) $whereClause .= " AND DATE(a.timestamp) BETWEEN '$dfrom' AND '$dto'";
    
    $query = $this->db->query("SELECT a.id, a.receiver, a.date, a.description, a.sender, a.status, a.timestamp 
                                FROM messages a 
                                LEFT JOIN user_info b ON a.receiver = b.id 
                                WHERE (FIND_IN_SET('".$this->session->userdata("userid")."',receiver) OR receiver='0') $whereClause ORDER BY timestamp DESC")
                                ->result();
  #  }
    return $query;
    }
    
    function viewCutOff($dfrom='',$dto='',$sched=''){
        $wC = "";
        if(!empty($dfrom) && !empty($dto))  $wC = " WHERE CutoffFrom = '$dfrom' AND CutoffTo = '$dto'";
        if(!empty($sched))
        {
            $extend = $wC != ""?"AND":"WHERE";
            $wC = " $extend b.schedule = '{$sched}'";
        }
        $query = $this->db->query("SELECT a.`ID`,a.`CutoffFrom`,a.`CutoffTo`,a.`TPostedDate`,a.`NTPostedDate`,b.`schedule`,b.`quarter`,b.`startdate`,b.`enddate`,a.`ConfirmFrom`,a.`ConfirmTo` FROM cutoff AS a LEFT JOIN payroll_cutoff_config b ON(a.`ID` = b.`baseid`) $wC ORDER BY a.`CutOffFrom` DESC")->result();
        return $query;
    }
    
    function viewCutOffConfirmed($dfrom='',$dto='',$dept=''){
        $wC = "";
        if(!empty($dfrom) && !empty($dto))  $wC = " WHERE CutOffFrom = '$dfrom' AND CutOffTo = '$dto' AND dateresigned = '1970-01-01'";
        if(!empty($dept))                   $wC .= " AND deptid='$dept'";
        $query = $this->db->query("SELECT * FROM cutoff_confirmed INNER JOIN employee USING (employeeid) $wC");
        return $query;
    }
    
    function viewCutOffNoConfirmed($dfrom='',$dto='',$dept=''){
        $wC = "";$param = "";
        if(!empty($dfrom) && !empty($dto))  $wC = " WHERE CutOffFrom = '$dfrom' AND CutOffTo = '$dto'";
        if(!empty($dept))                   $param .= " AND deptid='$dept' AND dateresigned = '1970-01-01'";
        $query = $this->db->query("SELECT employeeid FROM employee WHERE employeeid NOT IN (SELECT employeeid FROM cutoff_confirmed $wC) $param");
        return $query;
    }
    
    function viewcutoffdate($cutoff=""){
        $attr = "";
        $cutoffbox = "<option value=''>- Cut-Off Date -</option>";
        $cutoffq = $this->db->query("SELECT CutoffFrom, CutoffTo FROM cutoff ORDER BY CutoffFrom DESC");
        foreach($cutoffq->result() as $qrow){
            if($cutoff == $qrow->CutoffFrom."|".$qrow->CutoffTo) $attr = " selected";
            $cutoffbox .= "<option value='".$qrow->CutoffFrom."|".$qrow->CutoffTo."' ".$attr.">".date('F d, Y',strtotime($qrow->CutoffFrom))." to ".date('F d, Y',strtotime($qrow->CutoffTo))." </option>";
            $attr="";
        }
        return $cutoffbox; 
    }
    
    function editCutoff($key = ''){
        $query = $this->db->query("SELECT a.`ID`,a.`CutoffFrom`,a.`CutoffTo`,a.`TPostedDate`,a.`NTPostedDate`,b.`schedule`,b.`quarter`,b.`startdate`,b.`enddate`,a.`ConfirmFrom`,a.`ConfirmTo`,a.`TimeFrom`,a.`TimeTo`,b.nodtr FROM cutoff AS a LEFT JOIN payroll_cutoff_config b ON(a.`ID` = b.`baseid`)WHERE a.`ID`='$key' ORDER BY a.`Timestamp` ");
        return $query->result();
    }
    
    function removeID($ltype = ''){
        // $q = $this->db->query("SELECT GROUP_CONCAT(DISTINCT employeeid SEPARATOR ',') AS employeeids FROM employee WHERE employeeid<>'';");
        //         if($q){
        //             $res = $q->row();
        //             $employeeids = $res->employeeids;
        //             $employeeids = explode(',',$employeeids);
        //             $this->load->model('aims');
        //             return $this->aims->removeEmployeeRFIDNumberToAims(array('employeeids'=>$employeeids));
        //         }      
        //         return '';  
        $msg = "";
        if($ltype == "E" || $ltype != "S"){
            $query = $this->db->query("UPDATE employee SET employeecode = ''");
            if($query){
                // REMOVE RFID IN AIMS
                $q = $this->db->query("SELECT employeeid FROM employee");
                if($q){
                    $employeeids = $q->result_array();
                    $this->load->model('aims');
                    $this->aims->removeEmployeeRFIDNumberToAims(array('employeeids'=>$employeeids));
                }
            }
        }else{
            $query = $this->db->query("UPDATE StJude.tblPersonalData SET StudCardNo = ''");
            $query = $this->db->query("UPDATE student SET studentcode = ''");
        }
        if($query)  $msg = "All RFID Number has been deleted successfully";
        return $msg;
    }
    
    function hrDocx(){
        $id = "";
        $cquery = $this->db->query("SELECT id FROM elfinder_file WHERE NAME='HR FORMS'")->result();
        
        foreach($cquery as $row){
            $id = $row->id;
        }
        $query = $this->db->query("SELECT title FROM elfinder_file WHERE parent_id='$id'")->result();
        return $query;
    }
    
    function opengate(){
        $opengate = `nohup php /var/www/incl/id_server.php &`;
    }
    
    function leavedatevalidity($data){
        $toks = $data['toks'];
        $startdate = $this->gibberish->decrypt($data['dfrom'], $toks);
        $enddate   = $this->gibberish->decrypt($data['dto'], $toks);
        $ltype     = $this->gibberish->decrypt($data['ltype'], $toks);      
        $query = $this->db->query("SELECT * FROM code_request_form WHERE leavetype='$ltype' AND (('$startdate' BETWEEN startdate AND enddate) OR ('$enddate' BETWEEN startdate AND enddate))");
        return $query->num_rows();
    }

    function checkOtherLeaveTypeCodeAndDesc($code, $desc, $tblid=""){
        $return = "";
        if($tblid == ""){
            $query = $this->db->query("SELECT * FROM code_request_form WHERE code_request = '$code'")->num_rows();
            if($query > 0) $return = "code";
            $query = $this->db->query("SELECT * FROM code_request_form WHERE description = '$desc'")->num_rows();
            if($query > 0) $return =  "desc";
        }else{
            $query = $this->db->query("SELECT * FROM code_request_form WHERE description = '$desc'")->num_rows();
            if($query > 1) $return = "updatedesc";
        }
        return $return;
    }
    
    function leavetype($ltype = ""){
        $return = "";
        $arr = array("OLD"=>"OLD","NEW"=>"NEW","MID"=>"NEW-MID","NEW9"=>"NEW9","NEW8"=>"NEW8","NEW7"=>"NEW7","NEW6"=>"NEW6");
        foreach($arr as $key=>$val){
            if($ltype == $key)
                $return .= "<option value='$key' selected>$val</option>";
            else
                $return .= "<option value='$key'>$val</option>";
        }
        return $return;
    }
    
    function ftwodigits(){
        $return = "<option value=''>All ID</option>";
        $query = $this->db->query("SELECT A.employeeid FROM (SELECT SUBSTR(employeeid,1,2) AS employeeid FROM employee) AS A WHERE A.employeeid REGEXP '^[0-9]+$' GROUP BY A.employeeid;");
        foreach($query->result() as $row){
            $return .= "<option value='".$row->employeeid."'>".$row->employeeid."</option>";
        }   
        return $return; 
    }
    function save_terminal(){
         $query   = $this->db->query("
                            INSERT INTO code_terminal (id,terminal_name,campus,building,`floor`,`password`,rt_password)
                                VALUES ('{$id}','{$terminal_name}','{$campus}','{$building}','{$floor}','{$password}','{$rt_password}')
                            ");
        return $query;
    }
    
    function   saveltype($data){
        $dept  = $data['deptid'];
        $eid   = $data['employeeid'];
        $ltype = $data['ltype'];
        $twod  = $data['eidtwo'];
        $wC    = "";
        
        if($dept)   $wC = " AND deptid='$dept'";
        if($eid)    $wC = " AND employeeid='$eid'";
        if($twod)   $wC = " AND SUBSTR(employeeid,1,2)='$twod'";
        $query = $this->db->query("UPDATE employee SET leavetype='$ltype' WHERE dateresigned='1970-01-01' $wC");
        if($query)  return "Successfully Saved!.";
        else        return "Failed to Saved!. Please check your connection..";
    }
    
    function updateltype($data){
        $series = $data['eid'];
        $type   = $data['type'];
        $query = $this->db->query("INSERT INTO leavetype_trail (seriesno,type,user) VALUES ('$series','$type','".$this->session->userdata("username")."')");
        $query = $this->db->query("UPDATE employee SET leavetype='$type' WHERE SUBSTR(employeeid,1,2) = '$series'");
        if($query)  return "Update Successfully!.";
        else        return "Failed to Update!. Please Check your connection..";
    }
    
    function showLeaveTrail(){
        $return = "";
        $query = $this->db->query("SELECT * FROM leavetype_trail");
        foreach($query->result() as $row){
            $return .=  "<tr>
                            <td>".$row->seriesno."</td>
                            <td>".$row->type."</td>
                            <td>".$row->user."</td>
                            <td>".date('F d, Y h:i:s',strtotime($row->timestamp))."</td>
                        </tr>";
        }
        return $return;
    }
    
    function OtTime($eid = "", $dfrom = "",$dto = ""){
        $query = $this->db->query("SELECT SUM(overtime) as ttime FROM payroll_emp_otaccepted WHERE employeeid='$eid' AND otdate BETWEEN '$dfrom' AND '$dto'");
        return $query->row(0)->ttime;
    }
    
    function showtimedtr($eid='', $date=''){
        $timein = $timeout = "";
        $query = $this->db->query("SELECT TIME_FORMAT(timein,'%h:%i %p') AS tin, TIME_FORMAT(timeout,'%h:%i %p') AS tout FROM timesheet WHERE DATE(timein)='$date' AND userid='$eid' LIMIT 1");
        if($query->num_rows() > 0){
            $timein  = $query->row(0)->tin;
            $timeout = $query->row(0)->tout;
        }else{
            $query = $this->db->query("SELECT TIME_FORMAT(starttime,'%h:%i %p') AS tin, TIME_FORMAT(endtime,'%h:%i %p') AS tout FROM employee_schedule_adjustment WHERE DATE(cdate)='$date' AND employeeid='$eid' ORDER BY id DESC LIMIT 1");
            $timein  = $query->row(0)->tin;
            $timeout = $query->row(0)->tout;
        }
        
        if(empty($timein)){
            $query = $this->db->query("SELECT TIME_FORMAT(logtime,'%h:%i %p') AS tin FROM timesheet_trail WHERE DATE(logtime)='$date' AND userid='$eid' AND log_type='IN' LIMIT 1");
            if($query->num_rows() > 0)  $timein  = $query->row(0)->tin;
        }
        
        if(empty($timeout)){
            $query = $this->db->query("SELECT TIME_FORMAT(logtime,'%h:%i %p') AS tin FROM timesheet_trail WHERE DATE(logtime)='$date' AND userid='$eid' AND log_type='OUT' ORDER BY logtime DESC LIMIT 1");
            if($query->num_rows() > 0)  $timeout  = $query->row(0)->tin;
        }
        
        return array($timein,$timeout);
    }
    
    function showAccessmsg($user = ""){
        $return = false;
        $query = $this->db->query("SELECT * FROM user_info WHERE id='$user' AND msgaccess=1");
        if($query->num_rows() > 0)  $return = true;
        return $return;
    }
    
    function getTimeIn(){
        $return = array("","");
        $islate = false;
        $empid = $this->session->userdata("username");
        $sched = $this->attcompute->displaySched($empid,date("Y-m-d"));
        foreach($sched->result() as $rsched){
            $stime  = $rsched->starttime;
            $etime  = $rsched->endtime; 
            list($login,$logout,$q)           = $this->attcompute->displayLogTimeCurrent($empid,date("Y-m-d"),$stime,$etime,"NEW");
            $lateutlec = $this->attcompute->displayLateUTNT($stime,$etime,$login,$logout,"");
            $return = array($login,$islate);
        }
        return $return;
    }

    function getTimeInEmployeeRemarks(){
        $return = array("","");
        $islate = false;
        $empid = $this->session->userdata("username");
        $sched = $this->attcompute->displaySched($empid,date("Y-m-d"))->result();
        if (empty($sched)) {
            $remarks = "noSched";
            $time = "Please add a schedule.";
        }else{
            $tardy =  date("H:i:s",strtotime($sched[0]->tardy_start));
            $absent =  date("H:i:s",strtotime($sched[0]->absent_start));
            list($login,$logout,$q)            = $this->attcompute->displayLogTime($empid,date("Y-m-d"),$sched[0]->starttime,$sched[0]->endtime,"NEW");
            if ($login != "") {
               $login =  date("H:i:s",strtotime($login));
            }

            if($login == "" && date("H:i s",strtotime("now")) > $tardy) {
                $remarks = "absent";
                $time = date("h:i A",strtotime($absent));
            }elseif($login == "") {
                $remarks = "notlog";
                $time = date("h:i A",strtotime($sched[0]->starttime));
            }elseif($login >= $tardy) {
                $remarks = "LateIn";
                $time = date("h:i A",strtotime($login));
            }elseif($login <= $tardy) {
                $remarks = "On Time";
                $time = date("h:i A",strtotime($login));
            }
        }
            
            
            $return = array($remarks,$time);
        return $return;
    }
            
    function showHol($date){
        $return = "";
        $query = $this->db->query("SELECT date_from,date_to,hdescription,c.description FROM code_holiday_calendar a
                                    INNER JOIN code_holidays b ON a.holiday_id = b.holiday_id
                                    INNER JOIN code_holiday_type c ON b.holiday_type = c.holiday_type
                                    WHERE SUBSTR(date_from,1) = '$date' ORDER BY date_from")->result();
        return $query;
    }        
    
    function holDate($dfrom="",$dto=""){
        $query = "";
        $query = $this->db->query("SELECT DATE('{$dfrom}') + INTERVAL A + B + C DAY dte FROM
                                    (SELECT 0 A UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 ) d,
                                    (SELECT 0 B UNION SELECT 10 UNION SELECT 20 UNION SELECT 30 UNION SELECT 40 UNION SELECT 60 UNION SELECT 70 UNION SELECT 80 UNION SELECT 90) m , 
                                    (SELECT 0 C UNION SELECT 100 UNION SELECT 200 UNION SELECT 300 UNION SELECT 400 UNION SELECT 600 UNION SELECT 700 UNION SELECT 800 UNION SELECT 900) Y
                                    WHERE DATE('{$dfrom}') + INTERVAL A + B + C DAY  <=  DATE('{$dto}') ORDER BY A + B + C;")->result();
        return $query;
    }
    
    function imgExists(){
        $query = $this->db->query("SELECT * FROM elfinder_file WHERE title='".$this->session->userdata("username")."'");
        return $query->num_rows();
    }

    function userPhoto(){
        return $this->db->query("SELECT * FROM employee_photo WHERE employeeid='".$this->session->userdata("username")."'"); 
    }
    
    function infoEditRestriction(){
        $query = $this->db->query("SELECT * FROM ");
    }
    
    /*
     * Others
     */
    function clean($string) {
       return preg_replace('/[^A-Za-z0-9\- ]/', '', $string); // Removes special chars.
    }
    function computeAge($age=""){
      //date in mm/dd/yyyy format; or it can be in other formats as well
      $birthDate = date("m/d/Y",strtotime($age));
      //explode the date to get month, day and year
      $birthDate = explode("/", $birthDate);
      //get age from date or birthdate
      $age = (date("md", date("U", mktime(0, 0, 0, $birthDate[0], $birthDate[1], $birthDate[2]))) > date("md") ? ((date("Y") - $birthDate[2]) - 1) : (date("Y") - $birthDate[2]));
      return $age;
    }
	
	//Addedd 5-19-17
	function getOfficeDesc($deptid=""){
        $multiple = false;
        $deptid = addslashes($deptid);

        $where = strpos($deptid, ',') !== false
            ? "WHERE code IN ('" . implode("', '", explode(',', $deptid)) . "')"
            : "WHERE code = '$deptid'";

        $return = "";
        $query = $this->db->query("SELECT * FROM code_office $where")->result();

        foreach ($query as $index => $row) {
            $return .= ($index > 0 ? ' | ' : '') . $row->description;
        }

        if ($deptid === "alloffice") {
            $return = "All Office/Unit";
        }

        return $return;
	}

    function getDeptDesc($deptid=""){
        $return ="";
        $query = $this->db->query("SELECT * FROM code_department WHERE code = '{$deptid}'")->result();
        foreach($query as $row)
        {
            $return = Globals::_e($row->description);
        }
        return $return;
        
    }

    public function getCampus($campus=""){
        $result = $this->db->query("SELECT code,description FROM code_campus GROUP BY description ASC")->result();
        $return = "<option value='All'>All Site</option>";
        foreach ($result as $value) {
            if ($value->code == $campus) {
                $return .= "<option value='$value->code' selected >$value->description</option>";
            }else $return .= "<option value='$value->code'>$value->description</option>";
        }
        return $return;
    }

    public function getLeaveType($leaveId=""){
        $result = $this->db->query("SELECT * FROM code_request_form WHERE is_leave=1")->result();
        $return = "<option value=''>Leave Type</option>";
        foreach ($result as $value) {
            if ($value->code_request == $leaveId) {
                $return .= "<option value='$value->code_request' selected >$value->description</option>";
            }else $return .= "<option value='$value->code_request'>$value->description</option>";
        }
        return $return;
    }
	
	function getEmpSchedule($empid=""){
		$query = $this->db->query("SELECT * FROM employee_schedule WHERE employeeid = '{$empid}' ORDER BY idx ASC")->result();
		return $query;
		
	}

    function validateBatchDtr($data){
        foreach($data as $row){
            if(!$row) return false;
        }
    }

    ##Modified by Glen Mark
	//Batchapproval for Manage DTR
    function batchApprovalDTR($data="")
    // function batchapprovalDTR($data="")
    {

        $dow = array("SUN","M","T","W","TH","F","SAT");
        $user = $this->session->userdata('username');

        $result = "";
        $msg = "";
        $count = $countInsert = "";
        $queryInsert ="";
        $datas = explode("|", $data);
        $prev_eid = $baseid = $tID = '';
            # code...
            foreach ($datas as $value) {
                list($eid,$timein,$timeout,$date,$timestamp, $remarks) = explode("~u~", $value);
                $timestamp = $timestamp?$timestamp:"";
                if($timein != '') $finaltimein = date("Y-m-d H:i:s",strtotime("$date $timein"));
                else $finaltimein = '0000-00-00 00:00:00';
                
                if($timeout != '') $finaltimeout = date("Y-m-d H:i:s",strtotime("$date $timeout"));
                else $finaltimeout = '0000-00-00 00:00:00';

                $actual_timein = $actual_timeout = '';

                //< insert to adjustment
                
                $idx = date("w",strtotime($date));
                $status = '';

                if($eid != $prev_eid){

                    $select = $this->db->query("SELECT * FROM employee_schedule_adjustment WHERE cdate ='$date' AND dayofweek='{$dow[$idx]}' AND idx='{$idx}' AND employeeid='{$eid}'");
                    if ($select) $status = 'UPDATED';
                    $insert_adj = $this->db->query("INSERT INTO employee_schedule_adjustment (employeeid, cdate, dayofweek, idx, remarks, editedby,status) VALUES ('$eid','$date','{$dow[$idx]}','{$idx}','{$remarks}','{$user}','UPDATED') ");

                    if($insert_adj){ 
                        $baseid = $this->db->insert_id();
                        $this->db->query("DELETE FROM manage_inout_draft WHERE date = '$date' AND employeeid = '$eid'");
                    }
                }



                if ($timeout != "" && $timein !=""){

                    $query = $this->db->query("SELECT * FROM timesheet WHERE date(timein) ='$date' AND date(timeout) ='$date' AND userid='$eid' AND timeid='$timestamp'");

                    if ($query->num_rows() > 0){

                        $actual_timein = date('h:i a',strtotime($query->row(0)->timein));
                        $actual_timeout = date('h:i a',strtotime($query->row(0)->timeout));
                        $tID = $query->row(0)->timeid;

                       $queryInsert = $this->db->query("UPDATE timesheet SET timein = '$finaltimein', timeout ='$finaltimeout' WHERE userid='$eid' AND date(timein) = '$date' AND date(timeout) = '$date' AND timeid='$timestamp'");
                       $this->db->query("DELETE FROM manage_inout_draft WHERE date = '$date' AND employeeid = '$eid'");

                       if ($queryInsert){
                            $countInsert ++;
                        }
                    
                    }else{

                        $queryInsert = $this->db->query("INSERT INTO timesheet(userid,timein,timeout)VALUES('$eid','$finaltimein','$finaltimeout')");
                        if ($queryInsert){
                            // $insert = $this->db->query("INSERT INTO employee_schedule_adjustment(employeeid,cdate)VALUES('$eid','$date')");
                            $this->db->query("DELETE FROM manage_inout_draft WHERE date = '$date' AND employeeid = '$eid'");
                            $countInsert++;
                        }
                    }
                }
                else if ($timein != "" && $timeout == "") 
                {
                   $query = $this->db->query("SELECT * FROM timesheet_trail WHERE userid='$eid' AND date(logtime) ='$date'");
                    if ($query->num_rows() > 0) 
                    {
                      $actual_timein = date('h:i a',strtotime($query->row(0)->logtime));

                      $queryInsert = $this->db->query("UPDATE timesheet_trail SET logtime = '$finaltimein' WHERE userid='$eid' AND date(logtime) = '$date'");
                      $this->db->query("DELETE FROM manage_inout_draft WHERE date = '$date' AND employeeid = '$eid'");

                        if ($queryInsert){
                            $countInsert ++;
                        }
                    }else{

                        $queryInsert = $this->db->query("INSERT INTO timesheet_trail(userid,logtime) VALUES('$eid','$finaltimein')");
                        if ($queryInsert){
                            $countInsert ++;
                        }
                    }
                   
                }


                $actual_time = ($actual_timein || $actual_timeout) ? $actual_timein . " - " . $actual_timeout : '';

                $final_time = (($timein != '') ? date('Y-m-d h:i A',strtotime($finaltimein)) : '0000-00-00 00:00:00') . ' - ' . (($timeout != '') ? date('Y-m-d h:i A',strtotime($finaltimeout)) : '0000-00-00 00:00:00' );

                $findExistBIdAndTId = $this->db->query("SELECT * FROM employee_schedule_adjustment_ext WHERE baseID={$baseid} AND tID='{$tID}'")->result();
                if(count($findExistBIdAndTId) && $prev_eid != $eid){
                    foreach ($findExistBIdAndTId as $febat) {
                        $id = $febat->id;
                        $this->db->query("UPDATE employee_schedule_adjustment_ext SET actual_time='{$actual_time}', final_time='{$final_time}' WHERE id={$id}");
                        echo "<pre>"; print_r($this->db->last_query());
                    }
                }else{
                    $this->db->query("INSERT INTO employee_schedule_adjustment_ext (baseID, tID, actual_time,final_time) VALUES ('{$baseid}','{$tID}','{$actual_time}','{$final_time}')");
                }
                if ($timein != "" && $timeout == "") $this->employeeAttendance->updateDTR($eid, $date, $date);
                
                $this->db->query("DELETE FROM manage_inout_draft WHERE date = '$date' AND employeeid = '$eid'");

                $prev_eid = $eid;
                $tID = '';
            }
            

        if ($queryInsert) {
            return "Successfully Saved!";
        }
        else
        {
            return "Failed to saved data!";

        }
        
    }
    // function batchApprovalDTR($data="")
    // // function batchapprovalDTR($data="")
    // {

    //     $dow = array("SUN","M","T","W","TH","F","SAT");
    //     $user = $this->session->userdata('username');

    //     $result = "";
    //     $msg = "";
    //     $count = $countInsert = "";
    //     $queryInsert ="";
    //     $datas = explode("|", $data);
    //     $prev_eid = $baseid = $tID = '';
    //         # code...
    //         foreach ($datas as $value) {
    //             list($eid,$timein,$timeout,$date,$timestamp) = explode("~u~", $value);
    //             $timestamp = $timestamp?$timestamp:"";
    //             if($timein != '') $finaltimein = date("Y-m-d H:i:s",strtotime("$date $timein"));
    //             else $finaltimein = '0000-00-00 00:00:00';
                
    //             if($timeout != '') $finaltimeout = date("Y-m-d H:i:s",strtotime("$date $timeout"));
    //             else $finaltimeout = '0000-00-00 00:00:00';

    //             $actual_timein = $actual_timeout = '';

    //             //< insert to adjustment
                
    //             $idx = date("w",strtotime($date));
    //             $status = '';

    //             if($eid != $prev_eid){

    //                 $select = $this->db->query("SELECT * FROM employee_schedule_adjustment WHERE cdate ='$date' AND dayofweek='{$dow[$idx]}' AND idx='{$idx}' AND employeeid='{$eid}'");
    //                 if ($select) $status = 'UPDATED';
    //                 $insert_adj = $this->db->query("INSERT INTO employee_schedule_adjustment (employeeid, cdate, dayofweek, idx, remarks, editedby,status) VALUES ('$eid','$date','{$dow[$idx]}','{$idx}','','{$user}','UPDATED') ");

    //                 if($insert_adj) $baseid = $this->db->insert_id();

    //             }



    //             if ($timeout != "" && $timein !=""){

    //                 $query = $this->db->query("SELECT * FROM timesheet WHERE date(timein) ='$date' AND date(timeout) ='$date' AND userid='$eid' AND timeid='$timestamp'");

    //                 if ($query->num_rows() > 0){

    //                     $actual_timein = date('h:i a',strtotime($query->row(0)->timein));
    //                     $actual_timeout = date('h:i a',strtotime($query->row(0)->timeout));
    //                     $tID = $query->row(0)->timeid;

    //                    $queryInsert = $this->db->query("UPDATE timesheet SET timein = '$finaltimein', timeout ='$finaltimeout' WHERE userid='$eid' AND date(timein) = '$date' AND date(timeout) = '$date' AND timeid='$timestamp'");
    //                    if ($queryInsert){
    //                         $countInsert ++;
    //                     }
                    
    //                 }else{

    //                     $queryInsert = $this->db->query("INSERT INTO timesheet(userid,timein,timeout)VALUES('$eid','$finaltimein','$finaltimeout')");
    //                     if ($queryInsert){
    //                         // $insert = $this->db->query("INSERT INTO employee_schedule_adjustment(employeeid,cdate)VALUES('$eid','$date')");
    //                         $countInsert++;
    //                     }
    //                 }
    //             }
    //             else if ($timein != "" && $timeout == "") 
    //             {
    //                $query = $this->db->query("SELECT * FROM timesheet_trail WHERE userid='$eid' AND date(logtime) ='$date'");
    //                 if ($query->num_rows() > 0) 
    //                 {
    //                   $actual_timein = date('h:i a',strtotime($query->row(0)->logtime));

    //                   $queryInsert = $this->db->query("UPDATE timesheet_trail SET logtime = '$finaltimein' WHERE userid='$eid' AND date(logtime) = '$date'");
    //                     if ($queryInsert){
    //                         $countInsert ++;
    //                     }
    //                 }else{

    //                     $queryInsert = $this->db->query("INSERT INTO timesheet_trail(userid,logtime) VALUES('$eid','$finaltimein')");
    //                     if ($queryInsert){
    //                         $countInsert ++;
    //                     }
    //                 }
                   
    //             }


    //             $actual_time = ($actual_timein || $actual_timeout) ? $actual_timein . " - " . $actual_timeout : '';

    //             $final_time = (($timein != '') ? date('Y-m-d h:i A',strtotime($finaltimein)) : '0000-00-00 00:00:00') . ' - ' . (($timeout != '') ? date('Y-m-d h:i A',strtotime($finaltimeout)) : '0000-00-00 00:00:00' );

    //             $findExistBIdAndTId = $this->db->query("SELECT * FROM employee_schedule_adjustment_ext WHERE baseID={$baseid} AND tID='{$tID}'")->result();
    //             if(count($findExistBIdAndTId)){
    //                 foreach ($findExistBIdAndTId as $febat) {
    //                     $id = $febat->id;
    //                     $this->db->query("UPDATE employee_schedule_adjustment_ext SET actual_time='{$actual_time}', final_time='{$final_time}' WHERE id={$id}");
    //                 }
    //             }else{
    //                 $this->db->query("INSERT INTO employee_schedule_adjustment_ext (baseID, tID, actual_time,final_time) VALUES ('{$baseid}','{$tID}','{$actual_time}','{$final_time}')");
    //             }

    //             $prev_eid = $eid;
    //             $tID = '';
    //         }
            

    //     if ($queryInsert) {
    //         return "Successfully Saved!";
    //     }
    //     else
    //     {
    //         return "Failed to saved data!";

    //     }
        
    // }

    // for holiday 
    // author : justin (with e)
    function getCodeStatus(){
        $ret = $this->db->query("SELECT * FROM code_status ORDER BY seqno");
        return $ret;
    }
    function saveHolidayInclusion($hol_id,$dept,$stat){
        // save holiday inclusion
        $this->db->query("INSERT INTO holiday_inclusions (holi_cal_id, dept_included, status_included) VALUES ({$hol_id},'{$dept}','{$stat}')");
    }
    function findStatusIncluded($hol_id=0,$dept){
        if($hol_id == "") $hol_id = 0;
        $query = $this->db->query("SELECT * FROM holiday_inclusions WHERE holi_cal_id={$hol_id} AND dept_included='{$dept}'");

        return $query;
    }
    function findAllStatusIncluded($hol_id){
        $query = $this->db->query("SELECT * FROM holiday_inclusions WHERE holi_cal_id={$hol_id}");

        return $query;
    }
	// end for holiday 
	
    // for manage dtr
    // justin (with e)
    function findTimeRecordModel($eid, $cdate){
        $query = $this->db->query("SELECT * FROM timesheet WHERE (userid='{$eid}' AND timein LIKE '%{$cdate}%') OR (userid='{$eid}' AND timeout LIKE '%{$cdate}%')");

        if($query->num_rows() == 0){
            $query = $this->db->query("SELECT DISTINCT localtimein as timein, '' as timeout, '' as type, '' as timeid, userid FROM timesheet_trail WHERE userid='{$eid}' AND localtimein LIKE '%{$cdate}%'");
        }

        return $query->result();

    }

    function saveManageDTRModel($data, $idx, $editBy){
        $toks = $data['toks'];
        foreach($data as $key => $val){
            if($key != "Password" && $key != "toks") $data[$key] = $this->gibberish->decrypt($val, $toks);
        }
        $dow = array("SUN","M","T","W","TH","F","SAT");
        $select = $this->db->query("SELECT * FROM employee_schedule_adjustment WHERE cdate ='{$data['cdate']}' AND dayofweek='{$dow[$idx]}' AND idx='{$idx}' AND employeeid='{$data['eid']}'");
        if ($select) {
           $query = $this->db->query("INSERT INTO employee_schedule_adjustment (employeeid, cdate, dayofweek, idx, remarks, editedby,status,timestamp) VALUES ('{$data['eid']}','{$data['cdate']}','{$dow[$idx]}','{$idx}','{$data['remarks']}','{$editBy}','UPDATED','".date('Y-m-d H:i:s')."')");
        }
        else
        {
            $query = $this->db->query("INSERT INTO employee_schedule_adjustment (employeeid, cdate, dayofweek, idx, remarks, editedby,timestamp) VALUES ('{$data['eid']}','{$data['cdate']}','{$dow[$idx]}','{$idx}','{$data['remarks']}','{$editBy}','".date('Y-m-d H:i:s')."')");
        }
        // echo $this->db->last_query();
        return $query = $this->db->query("SELECT id FROM employee_schedule_adjustment WHERE id=(SELECT MAX(id) FROM employee_schedule_adjustment)")->row()->id;
    }

    function saveManageDTRAndTimesheet($eid,$bID, $tID,$fTime,$timein,$timeout){
        
        // save to employee_schedule_adjustment_ext
        $findExistBIdAndTId = $this->db->query("SELECT * FROM employee_schedule_adjustment_ext WHERE baseID={$bID} AND tID='{$tID}'")->result();
        if(count($findExistBIdAndTId)){
            foreach ($findExistBIdAndTId as $febat) {
                $id = $febat->id;
                $this->db->query("UPDATE employee_schedule_adjustment_ext SET final_time='{$fTime}' WHERE id={$id}");
            }
        }else{
            $this->db->query("INSERT INTO employee_schedule_adjustment_ext (baseID, tID, final_time) VALUES ({$bID},'{$tID}','{$fTime}')");
        }
        // end of saving to employee_schedule_adjustment_ext

        // save to timesheet
        $this->db->query("INSERT INTO timesheet (userid, timein, timeout, timestamp) VALUES ('{$eid}','{$timein}','{$timeout}','".date("Y-m-d H:i:s")."')");
        // end saving to timesheet
        // var_dump("<pre>",$this->db->last_query());die;
    }
    function findRemarks($id=0){
        $result = $this->db->query("SELECT description FROM code_request_type WHERE request_code=".$id);
        if($result->num_rows() > 0) return $result->row()->description;
        else return false;
    }

    function findRemarksByRequestCode($id=0){
        //aragon
        // check if id is numberic kasi yung remark na others, mismong reason ang nakastore so mag eerror sya sa sql
        // pag hindi numeric means return lang natin yung reason
        if(!is_numeric($id)) return $id;

        $result = $this->db->query("SELECT description FROM code_request_type WHERE request_code=".$id);
        if($result->num_rows() > 0) return $result->row()->description;
        else return false;
    }
    function findRemarksByRequestCodeFromReportItem($id=0){
        if(!is_numeric($id)) return $id;

        $result = $this->db->query("SELECT description FROM code_remarks WHERE remarksid=".$id);
        if($result->num_rows() > 0) return $result->row()->description;
        else return false;
    }

    
    // end for manage dtr
     function leaveSetup($job, $data){
        if($job == 0){
            $sql = "DELETE FROM code_request_form WHERE id=".$data['id'];
            $this->db->query($sql);
            return $sql;
        }else{
            $sql = "INSERT INTO code_request_form
                              (code_request,description,details,dhseq,hhseq,chseq,cpseq,upseq,boseq,fdseq,pseq,budgetoff,univphy,univphyt,financedir,president,is_leave, ismain) 
                              VALUES 
                              ('{$data['code']}','{$data['description']}','{$data['details']}','{$data['dhseq']}','{$data['hhseq']}','{$data['chseq']}','{$data['cpseq']}','{$data['upseq']}','{$data['boseq']}','{$data['fdseq']}','{$data['pseq']}','{$data['bo']}','{$data['up']}','{$data['upt']}','{$data['fd']}','{$data['pres']}',{$data['mngt']},{$data['mngt']})";
            $this->db->query($sql);
            return $sql;
        }
    }

    function getMainLeave($code = "") {
        $wc = '';
        $return = "";
        if ($code) $wc = "AND code_request = '$code'";
        $query = $this->db->query("SELECT * FROM code_request_form WHERE ismain='1' AND is_leave='1' $wc")->result();
        foreach ($query as $key) {
            if($code == $key->code_request) $return .= '<option value='.Globals::_e($key->code_request).' selected>'.Globals::_e($key->description).'</option>';
            else $return .= '<option value='.Globals::_e($key->code_request).'>'.Globals::_e($key->description).'</option>';
        }
        
        return $return;
    }

    function getMainLeaveInfo($code = "") {
        return $this->db->query("SELECT * FROM code_request_form WHERE ismain='1' AND is_leave='1' AND code_request = '$code'");
    }

    function checkScheduleAvail($employeeid = "",$idx = ""){
        $result = array();
        $findExistSchedule = $this->db->query("SELECT * FROM employee_schedule WHERE employeeid='{$employeeid}' AND idx='{$idx}'");
        if($findExistSchedule->num_rows() > 0)$result = array('result' => $findExistSchedule->num_rows(),'query' => "success",'err_code' => 0,"idx" => $idx); 
        else $result = array('result' => false,'query' => "Failed",'err_code' => '1',"idx" => $idx);
        return $result;
    }

    # for ica-hyperion 21152
    # by : justin (with e) 
    function getAllEmployee($empID){
        $data = array();
        $getCode = $this->db->query("SELECT DISTINCT code FROM code_office WHERE head='$empID' OR divisionhead='$empID'")->row()->code;
        $getEmployee = $this->db->query("SELECT employeeid AS empID, CONCAT(lname, ', ', fname, ' ', mname) AS fullname FROM employee WHERE deptid='$getCode'")->result();
        foreach ($getEmployee as $key) {
            $data[$key->empID] = $key->empID ." - ". $key->fullname;
        }

        # if head is not included on the list
        if(!(array_key_exists($empID, $data))){
            $getInfo = $this->db->query("SELECT employeeid AS empID, CONCAT(lname, ', ', fname, ' ', mname) AS fullname FROM employee WHERE employeeid='$empID'");
            $key = $getInfo->row()->empID;
            $val = $getInfo->row()->fullname;
            $data[$key] = $key ." - ".$val;
        }
        
        return $data;
    }
    # end for ica-hyperion 21152

    # for ica-hyperion 21194
    # justin (with e)
    function findIfAdmin($empid){
        #return  "SELECT * FROM user_info WHERE username='$empid' AND `type` LIKE '%admin%';"; die;
        $query = $this->db->query("SELECT * FROM user_info WHERE username='$empid' AND `type` LIKE '%admin%';")->result();
        if(count($query) > 0)
            return true;
        else 
            return false;
    }
    # justin (with e)
    function getAdminInfo($username){
        $query = $this->db->query("SELECT CONCAT(lastname, ', ', firstname, ' ', middlename) AS fullname FROM user_info WHERE username='$username'; ");

        if($query->num_rows() > 0) return $query->row()->fullname;
        else return false;
    }

    #get all clustertype
    function loadclustertype()
    {
        $return = '';
        $query = $this->db->query("SELECT code,description FROM code_type")->result();
            foreach ($query as $key) {
                $return .= "<option value='".Globals::_e($key->code)."'>".$key->description."</option>";
            }
        return $return;
    }
    #Added by Glen Mark
    //get campus description 
    function getCampusDescription()
    {
        $return = array();
        $query = $this->db->query("SELECT code,description FROM code_campus ORDER BY code");
        foreach ($query->result() as $row) {
            $return[$row->code] = $row->description;
        }
        return $return;
    }

    function getTableIDDesciption($table, $code, $column ="description")
    {
        
        if($code == "All") $return = "All";
        else $return = "None";
        if (strpos($code, ',') !== false) {
            // echo "<pre>";print_r(explode(",", $code));
            $return = "";
            foreach (explode(",", $code) as $key => $value) {
                $query = $this->db->query("SELECT id, $column FROM $table WHERE id = '$value'");
                foreach ($query->result() as $row) {
                    $return .= $row->$column."/";
                }
            }
            $return = substr($return, 0, -1);
        }else{
           $query = $this->db->query("SELECT id, $column FROM $table WHERE id = '$code'");
            foreach ($query->result() as $row) {
                    // echo "<pre>";print_r($row->$column);
                $return = $row->$column;
            } 
        }
        

        return $return;
    }

    function getTableCodeDesciption($table, $code)
    {
        
        if($code == "All") $return = "All";
        else $return = "None";
        if (strpos($code, ',') !== false) {
            // echo "<pre>";print_r(explode(",", $code));
            $return = "";
            foreach (explode(",", $code) as $key => $value) {
                $query = $this->db->query("SELECT code, description FROM $table WHERE code = '$value'");
                foreach ($query->result() as $row) {
                    $return .= $row->description."/";
                }
            }
            $return = substr($return, 0, -1);
        }else{
           $query = $this->db->query("SELECT code, description FROM $table WHERE code = '$code'");
            foreach ($query->result() as $row) {
                    // echo "<pre>";print_r($row->description);
                $return = $row->description;
            } 
        }
        

        return $return;
    }

    function getCompanyDescription($id)
    {
        if($code == "All") $return = "All";
        else $return = "None";
        $query = $this->db->query("SELECT company_description FROM campus_company WHERE id = '$id'");
        foreach ($query->result() as $row) {
            $return = $row->company_description;
        }
        return $return;
    }
    #Added by Glen Mark
     //get department description 
    function getDeptDescription()
    {
        $return = array();
        $query = $this->db->query("SELECT code,description FROM code_office ORDER BY code");
        foreach ($query->result() as $row) {
            $return[$row->code] = $row->description;
        }
        return $return;
    }
     #Added by Glen Mark
     //get EmployeeType description 
    function getEmpTypeDescription()
    {
        $return = array();
        $query = $this->db->query("SELECT code,description FROM code_campus ORDER BY code");
        foreach ($query->result() as $row) {
            $return[$row->code] = $row->description;
        }
        return $return;
    }

    function getPayrollCutoff($cutoffstart, $cutoffto){
        $cutoffid = $this->db->query("SELECT ID FROM cutoff WHERE CutoffFrom = '$cutoffstart' AND CutoffTo = '$cutoffto' ")->row()->ID;
        $query = $this->db->query("SELECT * FROM payroll_cutoff_config WHERE baseid = '$cutoffid' ")->result_array();
        return $query;
    }

    function getEmployeeTeachingType($employeeid){
        $type = "teaching";
        $query = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ");
        if($query->num_rows() > 0) return $query = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid' ")->row()->teachingtype;
        return $type;
    }

    function getUserEmail($username){
        $query = $this->db->query("SELECT email, type FROM user_info WHERE username = '$username' ");
        if($query->num_rows() > 0 && $query->row()->email != "") {
            return $query->row()->email;
        }elseif($query->row()->type == "EMPLOYEE"){
            $query = $this->db->query("SELECT email FROM employee WHERE employeeid = '$username' ");
            if($query->num_rows() > 0) {
                return $query->row()->email;
            }else{
                return '';
            }
        }
    }

    function checkLinkingValidator($empId, $usernameLinker){
       $sql = $this->db->query("SELECT * FROM user_info WHERE linked = '{$empId}' limit 1");
       if($sql->num_rows()==0) $sql = $this->db->query("SELECT * FROM user_info WHERE linked = '{$usernameLinker}' limit 1");
       if($sql->num_rows()==0) return 'true';
       else return 'false';
    }

    function verifySecCode($code,$id){
        $query = $this->db->query("SELECT * FROM security_trail WHERE id = '$id' AND `key` = '$code'");
        if($query->num_rows() > 0) return true;
        else return '';
    }

    function getAllEmployeeList(){
        $query = $this->db->query("SELECT * FROM employee");
        return $query->result_array();
    }

    function getAllEmployeeUserListForSelect(){
        $query = $this->db->query("SELECT username, CONCAT(lastname, ', ', firstname) AS fullname FROM user_info WHERE TYPE='EMPLOYEE' AND username IS NOT NUll AND username != ''");
        return $query->result_array();
    }
    function getAllAdminUserListForSelect(){
        $query = $this->db->query("SELECT username, CONCAT(lastname, ', ', firstname) AS fullname FROM user_info WHERE TYPE='ADMIN' AND username IS NOT NUll AND username != ''");
        return $query->result_array();
    }

    function getAMSLink($menu_id='150'){
        $link = "";

        $q_link = $this->db->query("SELECT link FROM menus WHERE menu_id='$menu_id'")->result();
        foreach ($q_link as $row) $link = $row->link;

        return $link;
    }

    function getEmploymentStatus($empid){
        $query = $this->db->query("SELECT employmentstat FROM employee WHERE employeeid = '$empid' ");
        if($query->num_rows() > 0) return $query->row()->employmentstat;
        else return "REG";
    }

    function isConsecutive($date){
        $date = explode("/", $date);
        $each_date = array();
        foreach ($date as $key => $value) {
            $each_date[] = substr($value, 3);
        }
        $last_data = 0;
        $sequence = 0;
        $isconsec = 0;

        $day = $count = 0;
        foreach($each_date as $key => $row){
            if($row) list($day, $count) = explode(" ", $row);
            if(!$last_data){
                $last_data = $count;
                $sequence +=1;
            }else{
                if($last_data + 1 == $count){
                   $sequence += 1;
                   if($last_data >= 3) $isconsec += 1; 
                   $last_data = $count;
                }
                else{
                   $sequence = 0;
                   $sequence += 1;
                   $last_data = $count;
                }
            }
        }   
        if($isconsec > 0) return true;  
    }

    function getFacial($facial = "") {
        $return = "<option value=''>Select Device</option>";
        $query = $this->db->query("SELECT * FROM facial_devices")->result();
        foreach ($query as $key) {
            if($facial == $key->serial_number) $return .= "<option value='$key->serial_number' selected>$key->name</option>";
            else $return .= "<option value='$key->serial_number'>$key->name</option>";
        }
        return $return;
    }

    /**
     * GET GL ACCOUNT IN OPTION FORMAT
     * @param string $gl_acc - id of gl account
     * @return string string formatted option for gl account
     */
    function getGLAccountsFromAthena($gl_acc='') {
        if($this->session->flashdata('gl_account')) return $this->displayListOption($this->session->flashdata('gl_account'),'Select GL Account', $gl_acc);

        $this->load->model('api');
        $response = $this->api->getGLAccountFromAthena();
        
        $this->session->set_flashdata('gl_account',$response);
        return $this->displayListOption($response,'Select GL Account', $gl_acc);
    }

    /**
     * DISPLAY THE ARRAY GL ACCOUNTS INTO OPTION
     * @param array $data - array of gl accounts
     * @param string $default - default value for option
     * @param string $gl - gl account id
     * @return string of option
     */
    private function displayListOption($data, $default='', $gl) {
        $return = $default ? "<option value=''>$default</option>" : '';
        if(!is_array($data)) return $return;

        foreach($data as $key => $value) {
            $selected = $value->AID==$gl ? 'selected': '';
            $return .= "<option value='$value->AID' $selected>$value->DESCRIPTION</option>";
        }
        return $return;
    }

    function constructArrayListFromComputedTable($str=''){
        $arr = array();
        if($str){
            $str_arr = explode('/', $str);
            if(count($str_arr)){
                foreach ($str_arr as $i_temp) {
                    $str_arr_temp = explode('=', $i_temp);
                    if(isset($str_arr_temp[0]) && isset($str_arr_temp[1])){
                        $arr[$str_arr_temp[0]] = $str_arr_temp[1];
                    }
                }
            }
        }
        return $arr;
    }

    function checkifCodeExist($code, $table, $holiday_type=''){
        $where = '';
        $num = ($holiday_type) ? 1 : 0; 
        if($table == 'code_holidays') $where = 'code';
        else $where = 'holiday_code';
        $query = $this->db->query("SELECT * FROM $table WHERE $where = '$code'");
        if($query->num_rows() > $num) return 0;
        else return 1;
    }

    function getUserLockedStat($username){
        $query = $this->db->query("SELECT locked FROM user_info WHERE username='$username'");
        if ($query->num_rows() == 0) {
            $empID = "";
           $q_user = $this->db->query("SELECT employeeid FROM employee WHERE email = '$username' OR personal_email = '$username' ");
            if($q_user->num_rows() > 0) $empID = $q_user->row()->employeeid;
            $query = $this->db->query("SELECT locked FROM user_info WHERE username='$empID'");
        }
        if ($query->num_rows() == 0) return "";
        else return $query->row()->locked;     
    }

    function getLinkedAccount($type,$username){
        $account = false;
        if ($type == "ADMIN") {
            $query = $this->db->query("SELECT linked AS linkAccount FROM user_info WHERE username='$username'");
            if($query->num_rows() > 0) $account = $query->row()->linkAccount;   
        }elseif($type == "EMPLOYEE"){
            $query = $this->db->query("SELECT username AS linkAccount FROM user_info WHERE linked='$username'");
            if($query->num_rows() > 0) $account = $query->row()->linkAccount;   
        }

        return $account; 
    }

    function getPendingClearance($username){
        $query = $this->db->query("SELECT type FROM user_info where username = '$username'");
        if($query->num_rows() > 0){
            $type = $query->row()->type;
            if($type == "EMPLOYEE"){
                $return = $this->db->query("SELECT * FROM employee_deficiency WHERE employeeid = '$username' ORDER BY submission_date DESC")->result_array();
                return $return;
            }else{
                return "ADMIN";
            }
        }else{
            return false;
        }
    }

    function accountStatusChecker($username){
        $account = false;
        $query = $this->db->query("SELECT status FROM user_info WHERE username ='$username'");
        if($query->num_rows() > 0) {
            $account = $query->row()->status;
        }   
        return $account; 
    }

    function checkLinkedAccount($type,$username){
        $account = false;
        if ($type == "ADMIN") {
            $query = $this->db->query("SELECT linked AS linkAccount FROM user_info WHERE username='$username'");
            if($query->num_rows() > 0) $account = true;   
        }elseif($type == "EMPLOYEE"){
            $query = $this->db->query("SELECT username AS linkAccount FROM user_info WHERE linked='$username'");
            if($query->num_rows() > 0) $account = true;   
        }
        return $account; 
    }

    function updateStatusUser($username,$status){
        return $this->db->query("UPDATE user_info SET status='{$status}' WHERE username = '{$username}'");
    }

    function emailChekerSyncLinker($username,$email){
        $account = false;
        $query = $this->db->query("SELECT username FROM user_info WHERE username !='$username' AND email = '$email'");
        if($query->num_rows() > 0) {
            $account = $query->row()->username;
            $this->db->query("UPDATE user_info SET linked ='$username' WHERE username='$account'");
        }   
        return $account; 
    }

    function getUserId($username){
        $query = $this->db->query("SELECT id FROM user_info WHERE username='$username'; ");
        if($query->num_rows() > 0) {
            return $query->row()->id;
        }else{
            return "";
        }
        
    }

    public function getBankInfo($code){
        $query =  $this->db->query("SELECT * FROM code_bank_account WHERE code = '$code'");
        return $query->num_rows() > 0 ? $query->row() : "";
    }

    function getUserUsernameByID($id){
        $query = $this->db->query("SELECT username FROM user_info WHERE id='$id'; ");
        return $query->row()->username;
    }

    function getUserType($uid){
        $query = $this->db->query("SELECT user_type FROM user_info WHERE id='$uid' ");
        return $query->row()->user_type;
    }

    function updateLockedHistory($stat,$key){
        return $this->db->query("UPDATE lock_account_history SET status='$stat' WHERE key='$key'");
    }

    function updateLinkAccount($username,$linked){
        return $this->db->query("UPDATE user_info SET linked='$linked' WHERE username='$username'");
    }

    function updateUserLockedStat($username,$stat){
        return $this->db->query("UPDATE user_info SET locked='$stat' WHERE username='$username'");
    }

    function unlickAccount($id){
        $query = $this->db->query("SELECT linked,username FROM user_info WHERE id = '$id' ");
        $linked = $query->row()->linked;
        $username = $query->row()->username;
        $linkTrail = array('link_to' => $username, 'link_from' => $linked, 'set_by' => $this->session->userdata('username'), 'status' => 'REMOVED LINK');
        $this->db->insert("linking_trail", $linkTrail);
        $this->db->query("UPDATE user_info SET linked ='', email = '' WHERE username ='$linked'");
        return $this->db->query("UPDATE user_info SET linked='' WHERE id='$id'");
    }

    function insertRequestTrails($data){
        return $this->db->insert("lock_account_history", $data);
    }

    function insertRequestTrailsSecurity($data){
        $this->db->insert("security_trail", $data);
        // return $this->db->insert_id();
        $query = $this->db->get_where('security_trail', $data);
        return $query->row(0)->id;
    }

    function insertRequestTrailsLinking($data){
        return $this->db->insert("linking_trail", $data);  
    }

    function insertLoginTrails($data){
        return $this->db->insert("login_attempts_hris", $data);
    }

    function getUnlockStatus($key=''){
        return $this->db->query("SELECT `status` FROM lock_account_history where `key` ='$key'")->row()->status;
    }

    function getUnlockUser($key=''){
        return $this->db->query("SELECT userid FROM lock_account_history where `key` ='$key'")->row()->userid;
    }

    function getUnlockTimeRequest($key=''){
        return $this->db->query("SELECT `timestamp` FROM lock_account_history where `key` ='$key'")->row()->timestamp;
    }

    function getAccountStatus($userid=''){
        return $this->db->query("SELECT locked FROM user_info where username = '$userid'")->row()->locked;
    }

    function getHeadOffice($empid=''){
        return $this->db->query("SELECT office from employee where employeeid = '$empid'")->row()->office;
    }

    function getUnderDept($employeeid=''){
        return $this->db->query("SELECT base_code FROM campus_office WHERE (dhead='$employeeid' OR divisionhead='$employeeid' OR hrhead='$employeeid' OR phead='$employeeid')")->result_array();
    }

    function getUnderDeptEmployee($code='', $category='', $office='', $username=''){
        // if($emplist != 'all'){
        //     $wc = '';
        //     if($category != 'all') $wc .= " AND is_completed = '$category' ";
        //     return $this->db->query("SELECT b.id AS empdef_id, c.description AS defdesc,d.description AS deptdesc, b.* FROM employee a LEFT JOIN employee_deficiency b ON a.employeeid = b.employeeid INNER JOIN code_deficiency c on b.def_id=c.id LEFT JOIN code_office d ON d.code=b.concerned_dept WHERE FIND_IN_SET (b.concerned_dept, '1,125,73,73,1,121,123,120,120,125,HR,49,49,49,49,49,49,127,128,129,130,87,6,PMO,96,43,4,444') AND b.lookfor = '$username' $wc ORDER BY employeeid ")->result();

        // }else{
        // return $this->db->query("SELECT employeeid, lname, fname, mname, office FROM employee WHERE FIND_IN_SET (office,'$code')")->result_array();
        // }
         $wc = '';
            if($office) $wc .= " AND a.office = '$office' ";
            if($category != 'all') $wc .= " AND b.is_completed = '$category' ";
            return $this->db->query("SELECT b.id AS empdef_id, c.description AS defdesc,d.description AS deptdesc, a.office as empoffice, b.* FROM employee a LEFT JOIN employee_deficiency b ON a.employeeid = b.employeeid INNER JOIN code_deficiency c on b.def_id=c.id LEFT JOIN code_office d ON d.code=b.concerned_dept WHERE FIND_IN_SET (b.concerned_dept, '$code') AND b.lookfor = '$username' $wc ORDER BY employeeid ")->result();
    }

    function checkClearance($head='', $employeeid=''){
        return $this->db->query("SELECT * FROM employee_deficiency WHERE lookfor = '$head' AND employeeid = '$employeeid'");
    }

    function readPastRequest($username){
        return $this->db->query("UPDATE lock_account_history SET status='READ' WHERE username='$username' AND `status`='SENT'");
    }


    public function getReason($id=''){
        $query = $this->db->query("SELECT resigned_reason from employee_employment_status_history where id ='$id'");
        if($query->num_rows() > 0) return $query->row()->resigned_reason;
        else return "No reason indicated.";
    }

      function countDepartment(){
        return $this->db->query("SELECT code from code_department")->num_rows();
    }

    function getNextSeqNo(){
        $q = $this->db->query("SELECT seqno FROM code_status ORDER BY seqno DESC LIMIT 1");
        if($q->num_rows() > 0) return $q->row()->seqno + 1;
        else return 1;
    }

    function showleavet(){
        $query = $this->db->query("SELECT code_request,description FROM code_request_form WHERE is_leave= 1")->result();
        foreach ($query as $key => $row) {
             $return[$row->code_request] = $row->description;
        }
       return $return;
    }

    function showleavetv2($leave_id = ""){
       $query  = $this->db->query("SELECT a.* FROM code_request_form b INNER JOIN online_application_code a ON a.id = b.base_id WHERE ismain = '1' AND is_leave = 1 GROUP BY a.id")->result();  
       foreach ($query as $key => $row) {
            $return[$row->id] = $row->description;
       }
       return $return;
    }

    function showHolPast($month = "", $year = ""){
        $return = "";
        $query = $this->db->query("SELECT date_from,date_to,hdescription,c.description FROM code_holiday_calendar a
                                    INNER JOIN code_holidays b ON a.holiday_id = b.holiday_id
                                    INNER JOIN code_holiday_type c ON b.holiday_type = c.holiday_type
                                    WHERE SUBSTR(date_from,1,7) = '$year-$month' AND DATE(NOW()) > a.`date_to` ORDER BY date_from")->result();
        return $query;
    }

    function getLatestCutoff(){
        $query = $this->db->query("SELECT * FROM cutoff ORDER BY ID DESC LIMIT 1")->row();
        return $query->CutoffFrom." ".$query->CutoffTo;
    }

    function getLatestCutOffBasedStartDate(){
        $query = $this->db->query("SELECT * FROM payroll_computed_table ORDER BY cutoffstart DESC LIMIT 1")->row();
        return $query->cutoffstart." ".$query->cutoffend;
    }

    function leaveCreditSetupAdd($data){
        $this->db->insert("code_request_leave_setup", $data);
    }

    function leaveCreditSetupDelete($code = ""){
        $this->db->query("DELETE FROM code_request_leave_setup WHERE code ='$code'");
    }

    function getEmployeeDataRecountLeave($id = ""){
        $where = "";
        if ($id) $where = "WHERE a.employeeid = '$id'";
        $query = $this->db->query("SELECT a.employeeid,a.dateemployed,a.teachingtype,a.employmentstat,a.office,b.type,c.description FROM employee a LEFT JOIN code_office b ON a.office = b.`code` LEFT JOIN code_gender c ON a.gender = c.genderid $where")->result();
        return $query;
    }

    public function recountAvailedLeave($empid, $leavetype, $dfrom, $dto){
        $q = $this->db->query("SELECT SUM(b.nodays) AS total FROM leave_app_emplist a LEFT JOIN leave_app_base b ON b.id = a.base_id AND a.sequence = (SELECT MAX(sequence) FROM leave_app_emplist WHERE base_id=b.id) WHERE a.employeeid = '$empid' AND b.type = '$leavetype' AND b.datefrom BETWEEN '$dfrom' AND '$dto' AND b.dateto BETWEEN '$dfrom' AND '$dto' AND (a.`status` = 'APPROVED' OR a.`status` = 'BYPASSED') AND paid = 'YES'");
        if($q->row(0)->total){
            return $q->row(0)->total;
        }
        else{
            return 0;
        }
    }

    function getEmployeeDataRecountLeavePast($id = "", $teachingtype = "", $status = "",$campus = "",$department = "",$office = ""){
        $today = date("Y-m-d");
        $wh = "";
        // var_dump($teachingtype);
        if ($id == "" || $id == 'all'){ 
            if ($teachingtype != ""){
                if($teachingtype != "trelated") $wh .= " AND a.teachingtype = '$teachingtype' ";
                else $wh .= " AND a.teachingtype='teaching' AND a.trelated = '1'";
            }
            if ($campus != "") $wh .= ($campus == "All" ? "" : " AND a.campusid = '$campus'");
            if ($department != "") $wh .= " AND a.deptid = '$department'";
            if ($office != "") $wh .= " AND a.office = '$office'";
            if ($status == "active"){
                $wh .= " AND isactive=1"; 
            }elseif($status == "inactive"){
                $wh .= " AND isactive=0"; 
            }
            $wh .= " AND employeeid != ''";
        }else{
            $wh .= " AND employeeid = '$id'";
        }
        
        $query = $this->db->query("SELECT a.employeeid,a.dateemployed,a.teachingtype,a.employmentstat,a.office,b.type,c.description,a.bdate FROM employee a LEFT JOIN code_office b ON a.office = b.`code` LEFT JOIN code_gender c ON a.gender = c.genderid where 1 = 1 $wh")->result();
        return $query;
    }

    function getLeaveCredit($employeeid, $leaveType){
        return $this->db->query("SELECT * FROM employee_leave_credit WHERE employeeid = '$employeeid' AND leavetype = '$leaveType'")->result();
    }

    function getLeaveCreditSetup($type){
        return $this->db->query("SELECT DISTINCT(`code`),emp_type FROM code_request_leave_setup WHERE teaching_type = '$type'")->result();
    }

    function saveLeaveToHistory($data){
        $this->db->insert("employee_leave_credit_history", $data);
    }

    function deleteLeaveData($id = ""){
        $this->db->query("DELETE FROM employee_leave_credit WHERE id ='$id'");
    }

    function deleteLeaveDataOldHistory($empid = "", $dfrom = "", $dto = ""){
        $this->db->query("DELETE FROM employee_leave_credit_history WHERE employeeid ='$empid' AND dfrom = '$dfrom' AND dto = '$dto' ");
    }

    function leaveCreditSetupData($code = "", $yearService = "", $teachingtype = "", $employmentstat = "", $cal_type = ""){
        $value = "";
        $type = "";
        $calWhere = "";
        if ($cal_type != "" AND $yearService >= 11) $calWhere = "AND cal_type = '$cal_type'";
        $leaveType = $teachingtype."Type";
        $data = $this->db->query("SELECT credits, cal_type FROM code_request_leave_setup WHERE $yearService BETWEEN `from` AND `to` AND `code` = '$code' AND emp_type = '$employmentstat' AND teaching_type = '$teachingtype' $calWhere")->result_array();
        if (empty($data)) {
        //     $leaveGreater = $this->db->query("SELECT credits, cal_type FROM code_request_leave_setup WHERE `code` = '$code' AND emp_type = '$employmentstat' AND teaching_type = '$teachingtype' order by `to` DESC limit 1")->result_array();
        //     if (empty($leaveGreater)) {
                $value = "NoSetup";
        //     }else{
        //         foreach ($leaveGreater as $row) {
        //             $value = $row['credits'];
        //             $type = $row['cal_type'];
        //         }
        //     }
        }else{
            foreach ($data as $row){
                $value = $row['credits'];
                $type = $row['cal_type'];
            } 
        }
        return $value."/".$type;
    }

    function getEmployementType($employementCode = "") {
        $query = $this->db->query("SELECT code, description FROM code_status ")->result();
        $return = "";
        foreach ($query as $key) {
            if($employementCode == $key->code) $return .= "<option value='$key->code' selected>$key->description</option>";
            else $return .= "<option value='$key->code'>$key->description</option>";
        }

        return $return;
    }

    function insertLeaveData($data){
        $this->db->insert("employee_leave_credit", $data);
    }

    function insertLeaveDataErrorLog($data){
        $this->db->insert("employee_leave_credit_auto", $data);
    }

    function getOBdesc($id){
        $query = $this->db->query("SELECT type FROM ob_type_list WHERE id = '$id'");
        return $query->row(0)->type;
    }

    function getCampdesc($id){
        $query = $this->db->query("SELECT description FROM code_campus WHERE code = '$id'");
        return $query->row(0)->description;
    }



    function getBankdescription($id){
        $query = $this->db->query("SELECT bank_name FROM code_bank_account WHERE code = '$id'");
        return $query->row(0)->bank_name;
    }

    function getGenderApplicable($code){
        $query = $this->db->query("SELECT genderApplicable FROM code_request_form WHERE code_request = '$code'");
        if($query->num_rows() > 0){
            return $query->row(0)->genderApplicable;
        }else return false;
        
    }

    function setupNewCoverageDate(){
        $yearNow = date("Y");
        $dateFrom = date('Y',strtotime(date("Y-m-d", time()) . " + 365 day"));
        $setup = $this->getLeaveCreditSetup('nonteaching');
        foreach ($setup as $key => $value) {
            $query = $this->leaveCreditSetupDataEmpStat($value->code, "nonteaching");
            $insertdata = array();
            $insertdata['leave_type'] = $value->code;
            $insertdata['employment_stat'] = $query;
            $insertdata["dfrom"] = date("Y")."-01-01";
            $insertdata["dto"] = date('Y',strtotime(date("Y-m-d", time()) . " + 365 day"))."-12-31";
            $insertdata["user"] = "Auto Compute";
            $this->db->insert("code_leave_coverage", $insertdata);
        }
    }

    function leaveCreditSetupDataEmpStat($code = "", $teachingtype = ""){
        $emptype = "";
        $data = $this->db->query("SELECT DISTINCT(emp_type) FROM code_request_leave_setup WHERE `code` = '$code' AND teaching_type = '$teachingtype'")->result_array();
        foreach ($data as $row){
            $emptype .= $row['emp_type']."/";
        } 
        return $emptype;
    }

    function showdepartmentUnder(){
        $option = "<option value=''>All Department</option>";
        $user = $this->session->userdata("username");
        $q_offc = $this->db->query("SELECT c.id, c.description FROM campus_office a INNER JOIN code_office b ON b.code = a.base_code INNER JOIN code_department c ON b.managementid = c.id WHERE (a.dhead='$user' OR a.divisionhead='$user') GROUP BY c.id");
        if($q_offc->num_rows() > 0){
            foreach($q_offc->result() as $row){
                $option .= "<option value='$row->base_code' selected>$row->description</option>";
            }
        }

        return $option;
    }

    function getGsuiteID($employeeid){
        $q_emp = $this->db->query("SELECT * FROM gsuite_accounts WHERE employeeid = '$employeeid'");
        if($q_emp->num_rows() > 0){
            return $q_emp->row()->g_id;
        }else{
            $q_emp2 = $this->db->query("SELECT * FROM employee WHERE employeeid = '$employeeid'");
            if($q_emp2->num_rows() > 0){
                return $q_emp2->row()->email;
            }else{
                return false;
            }
        }
    }

    function save_success_log($username){
        $logdate = $this->extensions->getServerTime();
        $this->db->query("UPDATE user_info  SET log_count = log_count + 1, log_date = '$logdate' WHERE username = '$username' ");
    }

    function school_of_list(){
        $query = $this->db->query("SELECT CODE,DESCRIPTION FROM tblCourseCategory ORDER BY DESCRIPTION");
        if($query->num_rows() > 0) return $query->result_array();
        else return false;
    }

    function getDevices($serial_number = "") {
        $return = "<option value=''>  All Devices  </option>";
        $query = $this->db->query("SELECT deviceKey, deviceName FROM facial_heartbeat")->result();
        foreach ($query as $key) {
            $key->deviceKey = Globals::_e($key->deviceKey);
            $key->deviceName = Globals::_e($key->deviceName);
            if($serial_number == $key->deviceKey) $return .= "<option value='$key->deviceKey' selected>$key->deviceName</option>";
            else $return .= "<option value='$key->deviceKey'>$key->deviceName</option>";
        }

        return $return;
    }

    function checkIfAlreadyAgreed($announcement_id, $username){
        $query = $this->db->query("SELECT * FROM agreement_logs WHERE username = '$username' AND announcement_id = '$announcement_id'");
        return $query->num_rows();
    }

    function clearanceDescription($clearance_id){
        $query = $this->db->query("SELECT description FROM clearance_type WHERE id = '$clearance_id'");
        if($query->num_rows() > 0) return $query->row()->description;
        else return "";
    }

    function showWFHActivities($base_id){
        $return = "";
        $query = $this->db->query("SELECT * FROM ob_timerecord WHERE base_id = '$base_id' GROUP BY t_date");
        if($query->num_rows() > 0){
            foreach ($query->result() as $key => $value) {
                if($value->activity != ""){
                    if($return == "") $return .= date('F d', strtotime($value->t_date)).": ".$value->activity;
                    else $return .= "<br>".date('F d', strtotime($value->t_date)).": ".$value->activity;
                }
            }
        }
        return $return;
    }

    function getWFHData($base_id, $date, $column, $null=false){
        $return = "";
        $gby = $wc = "";
        if($null) $wc = " AND $column != ''";
        if($column == "activity") $gby = "GROUP BY t_date";
        $query = $this->db->query("SELECT * FROM ob_timerecord WHERE base_id = '$base_id' AND t_date = '$date' $wc $gby");
        // echo "<pre>"; print_r($this->db->last_query()); die;
        if($query->num_rows() > 0){
            foreach ($query->result() as $key => $value) {
                if($return == "") $return .= $value->$column;
                else $return .= "<br>".$value->$column;
            }
        }
        return $return;
    }

    function getTerminal($username = "", $isall=false) {
        if($isall) $return = "<option value='all' ".($username == 'all' ? 'selected' : '')."> All Terminal </option>";
        else $return = "<option value=''> All Terminal </option>";
        $query = $this->db->query("SELECT username, terminal_name FROM terminal ")->result();
        foreach ($query as $key) {
            if($username == $key->username && $username != '') $return .= "<option value='".Globals::_e($key->username)."' selected>".Globals::_e($key->terminal_name)."</option>";
            else $return .= "<option value='".Globals::_e($key->username)."'>".Globals::_e($key->terminal_name)."</option>";
        }

        echo $return;
    }

    function leaveExtender($empstat, $dfrom, $dto, $dept, $oldDfrom, $oldDto){
        $record = $this->db->query("SELECT * FROM employee a INNER JOIN code_office b ON a.office = b.code INNER JOIN office_type c ON c.code = b.type WHERE b.type = '$dept' AND a.teachingtype = 'teaching' AND employmentstat = '$empstat'")->result();
        foreach ($record as $key => $value) {
            $updateArray = array();
            $updateArray['dfrom'] = $dfrom;
            $updateArray['dto'] = $dto;
            $this->db->where('employeeid', $value->employeeid);
            $this->db->where('employmentstat', $empstat);
            $this->db->where('dfrom', $oldDfrom);
            $this->db->where('dto', $oldDto);
            $this->db->update('employee_leave_credit', $updateArray);
        }
    }

    function getURLLink($menu_id){
        $query = $this->db->query("SELECT * FROM menus_url_link WHERE menu_id = '$menu_id'");
        if($query->num_rows() > 0) return $query->row()->url_link;
        else return "";
    }

    function clear_url_link($menu_id){
        $this->db->query("DELETE FROM menus_url_link WHERE menu_id = '$menu_id'");
    }

    function save_url_link($menu_id, $url){
        $this->clear_url_link($menu_id);
        $user = $this->session->userdata("username");
        $this->db->query("INSERT INTO menus_url_link(menu_id, url_link) VALUES ('$menu_id', '$url')");
        $this->db->query("INSERT INTO menus_url_logs(menu_id, url_link, user) VALUES ('$menu_id', '$url', '$user')");
        return "1";
    }

    function getEmployeePositionDesc($employeeid = "")
    {
        $returns = "";

        // $q = $this->db->query("SELECT b.description FROM employee a LEFT JOIN  code_position b ON b.positionid = a.positionid where a.employeeid = '$employeeid'")->result();
        // foreach ($q as $row) {
        //     $returns = $row->description;
        // }
        $query = $this->db->query("SELECT positionid FROM employee WHERE employeeid = '$employeeid'");
        
        if($query->num_rows() > 0){
            $returns = $this->extras->showPosDescMultiple($query->row()->positionid);
        }

        return $returns;
    }

    function getEmployeePositionDescPipe($positionid = "")
    {        
        $query = $this->db->query("SELECT GROUP_CONCAT(DISTINCT description SEPARATOR ' | ') AS description FROM code_position WHERE FIND_IN_SET(`positionid`,'$positionid')");

        if($query->num_rows() > 0) return Globals::_e($query->row()->description);       
        return '' ;
    }

    function getEmployeeDepartmentDesc($employeeid = "")
    {
        $returns = "";

        $q = $this->db->query("SELECT b.description FROM employee a LEFT JOIN  code_department b ON b.code = a.deptid where a.employeeid = '$employeeid'")->result();
        foreach ($q as $row) {
            $returns = $row->description;
        }

        return $returns;
    }

    function getEmployeeOfficeDesc($employeeid = "")
    {
        $returns = "";

        $q = $this->db->query("SELECT b.description FROM employee a LEFT JOIN  code_office b ON b.code = a.office WHERE a.employeeid = '$employeeid'")->result();
        foreach ($q as $row) {
            $returns = $row->description;
        }

        return $returns;
    }

    function getBatchAccess()
    {
        $return = "";
        $username = $this->session->userdata("username");
        $query = $this->db->query("SELECT batch_access FROM user_info where username='$username' and batch_access != 'null' ");
        if($query->num_rows() > 0){
            foreach ($query->result() as $key) {
                $return = $key->batch_access;
            }
        }
        return $return;
    }

    function getEmployeeOfficeDescByCode($code = "")
    {
        $returns = "";
        $q = $this->db->query("SELECT * FROM code_office WHERE FIND_IN_SET (code,'$code')")->result();
        foreach ($q as $row) {
            $returns = $row->description;
            if($returns != "") $returns .= ", ".$row->description;
            else $returns .= $row->description;
        }
        // echo "<pre>"; print_r($returns); die;

        return $returns;
    }

    function getRfidList($rfid=""){
        $result = "<option value=''>Select Visitor Pass</option>";
        $q = $this->db->query("SELECT * FROM visitor_rfid WHERE assigned = '0'")->result();
        foreach ($q as $row) {
            if ($rfid == $row) {
                $result .= "<option value='$row->id' rfid_number='$row->rfid_number' description='$row->description' selected>".$row->description."</option>";
            }
            else {
                $result .= "<option value='$row->id' rfid_number='$row->rfid_number' description='$row->description' >".$row->description."</option>";
            }
        }

        return $result;
    }

    function getEmployeeListForAthena() {
        $query = $this->db->query("SELECT employeeid, fname, mname, lname, personal_email, deptid, office, sub_office, isactive FROM employee");
        return $query->result_array();
    }

    function getSpecialAccess($userid, $menuid){
        $read = $write = "NO";
        $query = $this->db->query("SELECT * FROM user_access_special WHERE userid = '$userid' AND menuid = '$menuid'");
        if($query->num_rows() > 0){
            $read = $query->row()->read;
            $write = $query->row()->write;
        }
        return array($read, $write);
    }

    public function getSemesterCoverage($semester){
        $where_clause = " AND YEAR(CURDATE()) = YEAR(dfrom)";
        if($semester == 2) $where_clause = " AND YEAR(CURDATE()) = YEAR(dto)";
        return $this->db->query("SELECT * FROM semester_schedule WHERE semester = '$semester' $where_clause ")->result_array();
    }

    public function getLeaveTypeSemester($code){
        $query = $this->db->query("SELECT semester FROM code_request_form WHERE code_request = '$code'");
        if($query->num_rows() > 0){
            return $query->row()->semester;
        }else{
            return false;
        }
    }


    function getEmployeeList() {
        return $this->db->query("SELECT CONCAT(a.lname, ', ', a.fname, ' ', a.mname) AS fullname, a.employeeid FROM employee a INNER JOIN user_info b ON a.employeeid = b.username")->result_array();
    }

    function getMainCampusSite() {
        return $this->db->query("SELECT code, description FROM code_campus LIMIT 1")->row();
    }

    public function remarksDescription($id){
        $q = $this->db->query("SELECT description FROM code_remarks WHERE remarksid = '$id'");
        if($q->num_rows() > 0){
            return $q->row()->description;
        }else{
            return false;
        }
    }

    public function hasApproved($baseid, $basetbl, $tbl){
        return $this->db->query("SELECT TRUE FROM $tbl b INNER JOIN $basetbl a ON b.`base_id` = a.`id` WHERE b.base_id = '$baseid' AND b.status = 'APPROVED' LIMIT 1");
    }
    
    public function hasHoliday($dfrom, $dto){
        return $this->db->query("SELECT TRUE FROM code_holiday_calendar WHERE (DATE(date_from) BETWEEN '$dfrom' AND '$dto') OR (DATE(date_to) BETWEEN '$dfrom' AND '$dto') AND holiday_id != '5' LIMIT 1");
    }

    public function isIncluded($dfrom, $dto, $empid){
        $isIncluded = $this->db->query("SELECT TRUE FROM code_holiday_calendar a INNER JOIN code_holidays b ON a.holiday_id = b.holiday_id WHERE ((DATE(a.date_from) BETWEEN '$dfrom' AND '$dto') OR (DATE(a.date_to) BETWEEN '$dfrom' AND '$dto')) AND FIND_IN_SET('$empid', b.allowed)");
        if ($isIncluded->result()){
            return 'YES';
        }else{
            return 'NO';
        }
        
    }
    
    public function holidayCount($dfrom, $dto, $empid){
        $q = $this->db->query("SELECT isholiday FROM attendance_confirmed_nt WHERE employeeid = '$empid' AND cutoffstart = '$dfrom' AND cutoffend = '$dto'");
        if($q->num_rows() > 0){
            return $q->row()->isholiday;
        }else{
            return '0';
        }
    }
    public function getSuspensionRenderedHours($dfrom, $dto, $empid){
        $q = $this->db->query("SELECT isholiday FROM attendance_confirmed_nt WHERE employeeid = '$empid' AND cutoffstart = '$dfrom' AND cutoffend = '$dto'");
        if($q->num_rows() > 0){
            return $q->row()->isholiday;
        }else{
            return '0';
        }
    }

    public function getDaily($empid){
        $q = $this->db->query("SELECT daily FROM payroll_employee_salary WHERE employeeid = '$empid' LIMIT 1");
        if($q->num_rows() > 0){
            return $q->row()->daily;
        }else{
            return '0';
        }
    }
    public function getHourly($empid) {
        $q = $this->db->query("SELECT hourly FROM payroll_employee_salary WHERE employeeid = '$empid'");
        if ($q->num_rows() > 0) {
            $result = $q->row();
            return isset($result->hourly) ? (float) $result->hourly : 0; 
        } else {
            return 0;
        }
    }
    
    public function getAllOffice() {
        $query = $this->db->query("SELECT code, description FROM code_office");
        if ($query) {
            return $query->result();
        } else {
            return [];
        }
    }

    public function renderedWorkhoursSuspension($dfrom, $dto, $employeeid) {
        $query = $this->db->query("SELECT tsuspension FROM attendance_confirmed_nt WHERE (DATE(cutoffstart) BETWEEN '$dfrom' AND '$dto') AND employeeid='$employeeid'");
    
        if ($query->num_rows() > 0) {
            $result = $query->row();
            return isset($result->tsuspension) ? (float) $result->tsuspension : 0;
        } else {
            return 0;
        }
    }
    

    public function hasSuspension($dfrom, $dto){
        $query  = $this->db->query("SELECT 1 FROM code_holiday_calendar WHERE (DATE(date_from) BETWEEN '$dfrom' AND '$dto') OR (DATE(date_to) BETWEEN '$dfrom' AND '$dto') AND holiday_id = '5'");
        if($query->num_rows() > 0){
            return $query->row();
        }else{
            return '0';
        }
    }

}
 
/* End of file extras.php */
/* Location: ./application/models/extras.php */