<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Attendance extends CI_Model {
    private $base_sqlquery;
    private $from_date;
    private $to_date;

    private $indSummQuery;
    private $attSummQuery;

    /**
    * Attendance class constructor
    */
    public function __construct(){
        parent::__construct();
    }

    public function initialize($fdate = '', $tdate = ''){
        $this->from_date = $fdate;
        $this->to_date = $tdate;
        //$this->base_sqlquery = "select";
    }

    /**
    * queries database for attendance details
    * of employee(s) for summary reports
    * @param (date) start date to cover
    * @param (date) end date to cover
    * @param (string) employeeid (if any)
    * @param (string) deptid (if any)
    */
    public function giveIndividualSummary($from_date1 = '', $to_date1 = '', $empid = '', $edata = ''){
        $from_date = date("Y-m-d",strtotime($from_date1));
        $to_date = date("Y-m-d",strtotime($to_date1));
        if($edata == "OLD") $tbl = "timesheet_bak"; 
        else                $tbl = "timesheet";
        $this->indSummQuery = "call proc_individual_attendance_summary('".$empid."','".$from_date."','".$to_date."','".$tbl."')";
        mysqli_next_result($this->db->conn_id);
        return $this->db->query($this->indSummQuery)->result_array();
    }
    public function att_summary($from_date1 = '', $to_date1 = '', $empid = ''){
        $from_date = date("Y-m-d",strtotime($from_date1));
        $to_date = date("Y-m-d",strtotime($to_date1));
        $query = $this->db->query("SELECT SUM(tlate) AS late, SUM(tminlate) AS tminlate, SUM(tthlate) AS tthlate, SUM(tovertime) AS tovertime, SUM(tminovertime) AS tminovertime, SUM(tearlydismissal) AS  tearlydismissal, SUM(tearlymindismissal) AS  tearlymindismissal, 
                                    SUM(tabsent) AS tabsent, SUM(tleave) AS tleave, SUM(thalfday) AS thalfday, SUM(tfailuretolog) AS tfailuretolog, SUM(tnoholiday) AS tnoholiday
                                    FROM employee_att_summary WHERE employeeid='$empid' AND dfrom BETWEEN '$from_date1' AND '$to_date1';");

        return $query;        
    }
    /*
    public function otSummary($from_date1 = '', $to_date1 = '', $empid = ''){
        $from_date = date("Y-m-d",strtotime($from_date1));
        $to_date = date("Y-m-d",strtotime($to_date1));

        $this->indSummQuery = "call proc_individual_attendance_summary_ot('".$empid."','".$from_date."','".$to_date."')";
        mysqli_next_result($this->db->conn_id);
        return $this->db->query($this->indSummQuery)->result_array();
    }
    */
    public function giveAttendanceSummary($from_date = '', $to_date = '', $empid = '', $deptid = '', $tnt = '', $estatus = '',$campus=''){

        $condition = ($empid != "") 
            ? " and (TRIM(a.employeeid)='".$empid."' ) " : "";

        $condition .= ($deptid != "") ? " and a.deptid='$deptid' " : "";
        
        if($tnt)     $condition .= " AND a.teachingtype='$tnt'";
        if($estatus) $condition .= " AND a.employmentstat='$estatus'";
        if($campus)  $condition .= " AND a.campusid ='$campus'";
        $this->attSummQuery = "
            SELECT employeeid as qEmpId,deptid as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, b.description AS qDepartment,teachingtype
                FROM employee a
                INNER JOIN code_office b ON a.deptid = b.code 
                WHERE (a.dateresigned < a.dateposition OR a.dateresigned = '0000-00-00' OR a.dateresigned IS NULL) $condition GROUP BY a.employeeid ORDER BY deptid, qFullname";                
        
        return $this->db->query($this->attSummQuery)->result_array();
    }

    public function constructEmpListHaveSched($emplist, $date){
        $new_emplist = array();

        foreach ($emplist as $key => $info) {
            $employeeid = $info["qEmpId"];

            $q_sched = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid='$employeeid' AND DATE_FORMAT(dateactive, '%Y-%m-%d') <= '$date';")->result();            
            if(count($q_sched) > 0) $new_emplist[] = $info;
        }

        return $new_emplist;
    }
    
    public function emp_not_yet_confirmed($dfrom="", $dto="", $tnt="",$employeeid="",$payroll_start="",$payroll_end="",$deptid="",$campus="", $company_campus="", $office="", $isactive=""){
        $ifPayrollProcess = '';
        $wC = '';
        if($company_campus && $company_campus!="all") $wC .= " AND a.company_campus='$company_campus'";
        if($office && $office!="all") $wC .= " AND a.office='$office'";
        if($isactive != "all"){
          if($isactive=="1"){
            $wC .= " AND isactive ='1'";
          }
          if($isactive=="0"){
            $wC .= " AND isactive ='0'";
          }
        }
        
        if($tnt){
            if($tnt != "trelated") $wC .= " AND a.teachingtype = '$tnt' AND a.trelated != '1' ";
            else $wC .= " AND a.teachingtype='teaching' AND a.trelated = '1'";
        }

        if($employeeid) $wC .= " AND a.employeeid='$employeeid'";
        if($deptid) $wC .= " AND a.deptid='$deptid'";
        if($campus && $campus!="All") $wC .= " AND a.campusid='$campus'";
        if($payroll_start && $payroll_end) $ifPayrollProcess = " AND a.employeeid NOT IN (SELECT employeeid FROM payroll_computed_table c WHERE a.employeeid = c.employeeid AND c.cutoffstart = '$payroll_start' AND cutoffend = '$payroll_end' AND status = 'PROCESSED' ) ";
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND a.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          // if($usercampus) $utwc .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
        }

        // Add this condition for restricting not main site
        $main_campus = $this->db->campus_code;
        $wC .= " AND a.campusid = '$main_campus'";

        $wC .= $utwc;
        $this->attSummQuery = "
            SELECT a.employeeid as qEmpId,office as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, b.description AS qDepartment, a.dateresigned2
                FROM employee a
                LEFT JOIN code_office b ON a.office = b.code 
                LEFT JOIN code_department c ON a.deptid = c.code 
                LEFT JOIN  payroll_employee_salary d ON a.employeeid = d.employeeid 
                WHERE (a.dateresigned < a.dateposition OR a.dateresigned = '0000-00-00' OR a.dateresigned IS NULL) 
                AND a.employeeid NOT IN (SELECT employeeid FROM attendance_confirmed c WHERE a.employeeid = c.employeeid AND c.cutoffstart = '$dfrom' AND cutoffend = '$dto' ) $ifPayrollProcess
                $wC
                GROUP BY a.employeeid ORDER BY b.description, qFullname";                
        return $this->db->query($this->attSummQuery)->result_array();
    }

    public function emp_not_yet_confirmed_list($dfrom="", $dto="", $tnt="",$employeeid="",$payroll_start="",$payroll_end="",$deptid="",$campus="",$schedule="", $company_campus="", $office="", $isactive=""){
        $ifPayrollProcess = '';
        $wC = '';
        if($company_campus && $company_campus!="all") $wC .= " AND a.company_campus='$company_campus'";
        if($office && $office!="all") $wC .= " AND a.office='$office'";
        if($isactive != "all"){
          if($isactive=="1"){
            $wC .= " AND isactive ='1'";
          }
          if($isactive=="0"){
            $wC .= " AND isactive ='0'";
          }
        }
        
        if($tnt){
            if($tnt != "trelated") $wC .= " AND a.teachingtype = '$tnt' AND a.trelated != '1' ";
            else $wC .= " AND a.teachingtype='teaching' AND a.trelated = '1'";
        }

        if($employeeid) $wC .= " AND a.employeeid='$employeeid'";
        if($schedule) $wC .= " AND d.schedule='$schedule'";
        if($deptid) $wC .= " AND a.deptid='$deptid'";
        if($campus && $campus!="All") $wC .= " AND a.campusid='$campus'";
        if($payroll_start && $payroll_end) $ifPayrollProcess = " AND a.employeeid NOT IN (SELECT employeeid FROM payroll_computed_table c WHERE a.employeeid = c.employeeid AND c.cutoffstart = '$payroll_start' AND cutoffend = '$payroll_end' AND status = 'PROCESSED' ) ";
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND a.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          // if($usercampus) $utwc .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
        }

        // Add this condition for restricting not main site
        $main_campus = $this->db->campus_code;
        $wC .= " AND a.campusid = '$main_campus'";

        $wC .= $utwc;
        $this->attSummQuery = "
            SELECT a.employeeid as qEmpId,office as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, b.description AS qDepartment, a.dateresigned2
                FROM employee a
                LEFT JOIN code_office b ON a.office = b.code 
                LEFT JOIN code_department c ON a.deptid = c.code 
                LEFT JOIN  payroll_employee_salary d ON a.employeeid = d.employeeid 
                WHERE (a.dateresigned < a.dateposition OR a.dateresigned = '0000-00-00' OR a.dateresigned IS NULL) 
                AND a.employeeid NOT IN (SELECT employeeid FROM attendance_confirmed c WHERE a.employeeid = c.employeeid AND c.cutoffstart = '$dfrom' AND cutoffend = '$dto' ) $ifPayrollProcess
                $wC
                GROUP BY a.employeeid ORDER BY b.description, qFullname";                
        return $this->db->query($this->attSummQuery)->result_array();
    }

    public function emp_not_yet_confirmed_nt($dfrom="", $dto="", $tnt="",$employeeid="",$payroll_start="",$payroll_end="",$deptid="",$campus="", $company_campus="", $office="", $isactive="" ){

        $ifPayrollProcess = '';
        $wC = '';
        if($company_campus && $company_campus!="all") $wC .= " AND a.company_campus='$company_campus'";
        if($office && $office!="all") $wC .= " AND a.office='$office'";
        if($isactive != "all"){
          if($isactive=="1"){
            $wC .= " AND isactive ='1'";
          }
          if($isactive=="0"){
            $wC .= " AND isactive ='0'";
          }
        }

        if($tnt){
            $wC .= " AND a.teachingtype = '$tnt' ";
        }
        if($tnt){
            $wC .= " AND a.teachingtype = '$tnt' ";
        }
      
        if($employeeid) $wC .= " AND a.employeeid='$employeeid'";
        // if($schedule) $wC .= " AND d.schedule='$schedule'";
        if($deptid) $wC .= " AND a.deptid='$deptid'";
        if($campus && $campus!="All") $wC .= " AND a.campusid='$campus'";
        if($payroll_start && $payroll_end) $ifPayrollProcess = " AND a.employeeid NOT IN (SELECT employeeid FROM payroll_computed_table c WHERE a.employeeid = c.employeeid AND c.cutoffstart = '$payroll_start' AND cutoffend = '$payroll_end' AND status = 'PROCESSED' ) ";
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND a.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
        }

        // Add this condition for restricting not main site
        $main_campus = $this->db->campus_code;
        $wC .= " AND a.campusid = '$main_campus'";

        $wC .= $utwc;
        $this->attSummQuery = " SELECT  d.schedule, a.employeeid as qEmpId,office as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, b.description AS  qDepartment, a.dateresigned2 FROM employee a
                      LEFT JOIN code_office b ON a.office = b.code 
                      LEFT JOIN code_department c ON a.deptid = c.code 
                      LEFT JOIN  payroll_employee_salary d ON a.employeeid = d.employeeid 
                      WHERE (a.dateresigned < a.dateposition OR a.dateresigned = '0000-00-00' OR a.dateresigned IS NULL) 
                      AND a.employeeid NOT IN (SELECT employeeid FROM attendance_confirmed_nt c 
                      WHERE a.employeeid = c.employeeid AND c.cutoffstart = '$dfrom' AND cutoffend = '$dto' ) $ifPayrollProcess
                     $wC
                    GROUP BY a.employeeid ORDER BY b.description, qFullname";                
        return $this->db->query($this->attSummQuery)->result_array();
    }

    public function emp_not_yet_confirmed_nt_list($dfrom="", $dto="", $tnt="",$employeeid="",$payroll_start="",$payroll_end="",$deptid="",$campus="",$schedule="", $company_campus="", $office="", $isactive="" ){

        $ifPayrollProcess = '';
        $wC = '';
        if($company_campus && $company_campus!="all") $wC .= " AND a.company_campus='$company_campus'";
        if($office && $office!="all") $wC .= " AND a.office='$office'";
        if($isactive != "all"){
          if($isactive=="1"){
            $wC .= " AND isactive ='1'";
          }
          if($isactive=="0"){
            $wC .= " AND isactive ='0'";
          }
        }

        if($tnt){
            $wC .= " AND a.teachingtype = '$tnt' ";
        }
        if($tnt){
            $wC .= " AND a.teachingtype = '$tnt' ";
        }
      
        if($employeeid) $wC .= " AND a.employeeid='$employeeid'";
        if($schedule) $wC .= " AND d.schedule='$schedule'";
        if($deptid) $wC .= " AND a.deptid='$deptid'";
        if($campus && $campus!="All") $wC .= " AND a.campusid='$campus'";
        if($payroll_start && $payroll_end) $ifPayrollProcess = " AND a.employeeid NOT IN (SELECT employeeid FROM payroll_computed_table c WHERE a.employeeid = c.employeeid AND c.cutoffstart = '$payroll_start' AND cutoffend = '$payroll_end' AND status = 'PROCESSED' ) ";
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND a.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
        }

        // Add this condition for restricting not main site
        $main_campus = $this->db->campus_code;
        $wC .= " AND a.campusid = '$main_campus'";

        $wC .= $utwc;
        $this->attSummQuery = " SELECT  d.schedule, a.employeeid as qEmpId,office as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, b.description AS  qDepartment, a.dateresigned2 FROM employee a
                      LEFT JOIN code_office b ON a.office = b.code 
                      LEFT JOIN code_department c ON a.deptid = c.code 
                      LEFT JOIN  payroll_employee_salary d ON a.employeeid = d.employeeid 
                      WHERE (a.dateresigned < a.dateposition OR a.dateresigned = '0000-00-00' OR a.dateresigned IS NULL) 
                      AND a.employeeid NOT IN (SELECT employeeid FROM attendance_confirmed_nt c 
                      WHERE a.employeeid = c.employeeid AND c.cutoffstart = '$dfrom' AND cutoffend = '$dto' ) $ifPayrollProcess
                     $wC
                    GROUP BY a.employeeid ORDER BY b.description, qFullname";                
        return $this->db->query($this->attSummQuery)->result_array();
    }
    public function emp_confirmed($dfrom="", $dto="", $tnt="", $eid="", $campus="", $deptid="", $sDept = "", $company_campus="", $office="", $isactive=""){
        $wC = "";
        $oB = "qFullname";
        $usercompany =  $this->extras->getCompanyUser();
        $usercampus =  $this->extras->getCampusUser();

        if($usercampus) $wC .= " AND FIND_IN_SET (a.`campusid`,'$usercampus')";
        if($usercompany) $wC .= " AND a.company_campus = '$usercompany'";

        if($eid && $eid!="null") $wC .= " AND FIND_IN_SET (a.`employeeid`,'$eid')";      
        if($campus && $campus!="All") $wC .= " AND a.campusid='$campus'";
        if($company_campus!='undefined' && $company_campus!="all" && $company_campus) $wC .= " AND a.company_campus='$company_campus'";
        if($office && $office!="all") $wC .= " AND a.office='$office'";
        if($isactive != "all"){
          if($isactive=="1"){
            $wC .= " AND isactive ='1'";
          }
          if($isactive=="0"){
            $wC .= " AND isactive ='0'";
          }
        }

        if($tnt){
            if($tnt != "trelated") $wC .= " AND a.teachingtype = '$tnt' AND a.trelated != '1' ";
            else $wC .= " AND a.teachingtype='teaching' AND a.trelated = '1'";
        }

        if($deptid && $deptid != "all") $wC .= " AND a.deptid='$deptid'";
        // if($dept_select && $dept_select != "all") $wC .= " AND a.deptid='$dept_select'";   
        if($sDept) $oB = " b.description,qFullname";    
        // echo "<pre>";print_r($wC);die;
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND a.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
        }
        $wC .= $utwc;
        $this->attSummQuery = "
            SELECT a.employeeid as qEmpId, office as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, a.campusid as qCampusId, b.description AS qDepartment,DATE(c.timestamp) as dateconfirmed,c.status, c.*,d.fixedday, c.isFinal, c.hold_status, a.dateresigned2
                FROM employee a
                LEFT JOIN code_office b ON a.office = b.code 
                INNER JOIN attendance_confirmed c ON a.employeeid = c.employeeid
                LEFT JOIN payroll_employee_salary d ON d.employeeid=a.employeeid
                WHERE (a.dateresigned < a.dateposition OR a.dateresigned = '0000-00-00' OR a.dateresigned IS NULL) 
                    AND c.cutoffstart = '$dfrom' AND cutoffend = '$dto' $wC GROUP BY a.employeeid ORDER BY $oB ";                
        return GLOBALS::resultarray_XHEP($this->db->query($this->attSummQuery)->result_array());
    }


    public function emp_confirmed_list($dfrom="", $dto="", $tnt="", $eid="", $campus="",$schedule="", $deptid="", $sDept = "", $company_campus="", $office="", $isactive=""){
        $wC = "";
        $oB = "qFullname";
        $usercompany =  $this->extras->getCompanyUser();
        $usercampus =  $this->extras->getCampusUser();

        if($usercampus) $wC .= " AND FIND_IN_SET (a.`campusid`,'$usercampus')";
        if($usercompany) $wC .= " AND a.company_campus = '$usercompany'";

        if($eid && $eid!="null") $wC .= " AND FIND_IN_SET (a.`employeeid`,'$eid')";      
        if($campus && $campus!="All") $wC .= " AND a.campusid='$campus'";
        if($company_campus!='undefined' && $company_campus!="all" && $company_campus) $wC .= " AND a.company_campus='$company_campus'";
        if($office && $office!="all") $wC .= " AND a.office='$office'";
        if($isactive != "all"){
          if($isactive=="1"){
            $wC .= " AND isactive ='1'";
          }
          if($isactive=="0"){
            $wC .= " AND isactive ='0'";
          }
        }

        if($tnt){
            if($tnt != "trelated") $wC .= " AND a.teachingtype = '$tnt' AND a.trelated != '1' ";
            else $wC .= " AND a.teachingtype='teaching' AND a.trelated = '1'";
        }

        if($deptid && $deptid != "all") $wC .= " AND a.deptid='$deptid'";
        if($schedule) $wC .= " AND d.schedule='$schedule'";
        // if($dept_select && $dept_select != "all") $wC .= " AND a.deptid='$dept_select'";   
        if($sDept) $oB = " b.description,qFullname";    
        // echo "<pre>";print_r($wC);die;
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND a.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
        }
        $wC .= $utwc;
        $this->attSummQuery = "
            SELECT a.employeeid as qEmpId, office as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, a.campusid as qCampusId, b.description AS qDepartment,DATE(c.timestamp) as dateconfirmed,c.status, c.*,d.fixedday, c.isFinal, c.hold_status, a.dateresigned2
                FROM employee a
                LEFT JOIN code_office b ON a.office = b.code 
                INNER JOIN attendance_confirmed c ON a.employeeid = c.employeeid
                LEFT JOIN payroll_employee_salary d ON d.employeeid=a.employeeid
                WHERE (a.dateresigned < a.dateposition OR a.dateresigned = '0000-00-00' OR a.dateresigned IS NULL) 
                    AND c.cutoffstart = '$dfrom' AND cutoffend = '$dto' $wC GROUP BY a.employeeid ORDER BY $oB ";                
        return GLOBALS::resultarray_XHEP($this->db->query($this->attSummQuery)->result_array());
    }


     public function emp_confirmedsorting($dfrom="", $dto="", $tnt="", $eid="",$category="",$campus=""){
        $wC = "";
        $sort = "";
        if ($category=="campus") {
            if($campus) $wC .= " AND a.campusid ='{$campus}'";$sort = "ORDER BY a.campusid,qFullname";
        }   
        else
        {
            $sort .= "ORDER BY qFullname";
            //$sort .= "ORDER BY qFullname,depid";
        }
        if($eid) $wC .= " AND a.employeeid='$eid'";   
        $this->attSummQuery = "
            SELECT a.campusid,a.employeeid as qEmpId,office as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, b.description AS qDepartment,DATE(c.timestamp) as dateconfirmed, c.*,d.fixedday
                FROM employee a
                INNER JOIN code_office b ON a.office = b.code 
                INNER JOIN attendance_confirmed c ON a.employeeid = c.employeeid
                LEFT JOIN payroll_employee_salary d ON d.employeeid=a.employeeid
                WHERE (a.dateresigned = '1970-01-01' OR a.dateresigned IS NULL OR a.dateresigned = '0000-00-00') AND c.cutoffstart = '$dfrom' AND cutoffend = '$dto' AND a.teachingtype='$tnt' $wC GROUP BY a.employeeid {$sort}";                
        return $this->db->query($this->attSummQuery)->result_array();
    }   
    
    public function emp_confirmed_nt($dfrom="", $dto="", $tnt="", $eid="", $campus="", $deptid="", $sDept="", $company_campus="", $office="", $isactive="", $xtra_wc=""){
        $wC = "";
        $oB = "qFullname";
        if($eid && $eid!="null") $wC .= " AND FIND_IN_SET (a.`employeeid`,'$eid')";    
        if($campus && $campus!="All") $wC .= " AND a.campusid='$campus'";
        if($company_campus && $company_campus!="all") $wC .= " AND a.company_campus='$company_campus'";
        if($office && $office!="all") $wC .= " AND a.office='$office'";
        if($isactive != "all"){
          if($isactive=="1"){
            $wC .= " AND isactive ='1'";
          }
          if($isactive=="0"){
            $wC .= " AND isactive ='0'";
          }
        }

        if($tnt){
            $wC .= " AND a.teachingtype = '$tnt' ";
        }

        if($deptid && $deptid!="all") $wC .= " AND a.deptid='$deptid'"; 
        if($sDept) $oB = " b.description,qFullname";    
        
        $utwc = '';
        $utdept = $this->session->userdata("department");
        // if($schedule) $wC .= " AND d.schedule='$schedule'";
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND a.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
        }
        $wC .= $utwc;
        if($xtra_wc==1) $xtra_wc = "";
        $this->attSummQuery = "
            SELECT a.employeeid as qEmpId,office as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, b.description AS qDepartment, a.campusid as qCampusId,DATE(c.timestamp) as dateconfirmed, c.*,d.fixedday, c.status, c.isFinal, c.hold_status, a.dateresigned2
                FROM employee a
                LEFT JOIN code_office b ON a.office = b.code 
                INNER JOIN attendance_confirmed_nt c ON a.employeeid = c.employeeid
                LEFT JOIN payroll_employee_salary d ON d.employeeid=a.employeeid
                WHERE (a.dateresigned < a.dateposition OR a.dateresigned = '0000-00-00' OR a.dateresigned IS NULL) 
                    AND c.cutoffstart = '$dfrom' AND cutoffend = '$dto'  $wC $xtra_wc GROUP BY a.employeeid ORDER BY $oB";     
        $result = $this->db->query($this->attSummQuery)->result_array();

        return GLOBALS::resultarray_XHEP($result);
    }    



    public function emp_confirmed_nt_list($dfrom="", $dto="", $tnt="", $eid="", $campus="", $schedule="", $deptid="", $sDept="", $company_campus="", $office="", $isactive="", $xtra_wc=""){
        $wC = "";
        $oB = "qFullname";
        if($eid && $eid!="null") $wC .= " AND FIND_IN_SET (a.`employeeid`,'$eid')";    
        if($campus && $campus!="All") $wC .= " AND a.campusid='$campus'";
        if($company_campus && $company_campus!="all") $wC .= " AND a.company_campus='$company_campus'";
        if($office && $office!="all") $wC .= " AND a.office='$office'";
        if($isactive != "all"){
          if($isactive=="1"){
            $wC .= " AND isactive ='1'";
          }
          if($isactive=="0"){
            $wC .= " AND isactive ='0'";
          }
        }

        if($tnt){
            $wC .= " AND a.teachingtype = '$tnt' ";
        }

        if($deptid && $deptid!="all") $wC .= " AND a.deptid='$deptid'"; 
        if($sDept) $oB = " b.description,qFullname";    
        
        $utwc = '';
        $utdept = $this->session->userdata("department");
        if($schedule) $wC .= " AND d.schedule='$schedule'";
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND a.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
        }
        $wC .= $utwc;
        if($xtra_wc==1) $xtra_wc = "";
        $this->attSummQuery = "
            SELECT a.employeeid as qEmpId,office as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, b.description AS qDepartment, a.campusid as qCampusId,DATE(c.timestamp) as dateconfirmed, c.*,d.fixedday, c.status, c.isFinal, c.hold_status, a.dateresigned2
                FROM employee a
                LEFT JOIN code_office b ON a.office = b.code 
                INNER JOIN attendance_confirmed_nt c ON a.employeeid = c.employeeid
                LEFT JOIN payroll_employee_salary d ON d.employeeid=a.employeeid
                WHERE (a.dateresigned < a.dateposition OR a.dateresigned = '0000-00-00' OR a.dateresigned IS NULL) 
                    AND c.cutoffstart = '$dfrom' AND cutoffend = '$dto'  $wC $xtra_wc GROUP BY a.employeeid ORDER BY $oB";     
        $result = $this->db->query($this->attSummQuery)->result_array();

        return GLOBALS::resultarray_XHEP($result);
    }   
    //newly added 03/20/2018
     public function emp_confirmedperdept($dfrom="", $dto="", $tnt="", $eid="", $office="", $campus="",$deptid="",$isactive="",$company_campus=""){
        $wC = "";
        if($isactive != "all"){
          if($isactive=="1"){
            $wC .= " AND isactive ='1'";
          }
          if($isactive=="0"){
            $wC .= " AND isactive ='0'";
          }
        }
        if($company_campus && $company_campus!="all") $wC .= " AND a.company_campus='$company_campus'";
        if($eid) $wC .= " AND a.employeeid='$eid'"; 
        if($office) $wC .= " AND a.office='$office'";
        if($deptid) $wC .= " AND a.deptid='$deptid'";
        if($campus) $wC .= " AND a.campusid='$campus'"; 
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND a.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
        }
        $wC .= $utwc;  
        $this->attSummQuery = "
            SELECT a.employeeid as qEmpId,office as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, b.description AS qDepartment,DATE(c.timestamp) as dateconfirmed, c.*,d.fixedday, a.campusid
                FROM employee a
                INNER JOIN code_office b ON a.office = b.code 
                INNER JOIN attendance_confirmed c ON a.employeeid = c.employeeid
                LEFT JOIN payroll_employee_salary d ON d.employeeid=a.employeeid
                WHERE (a.dateresigned = '1970-01-01' OR a.dateresigned = '0000-00-00' OR a.dateresigned IS NULL) AND c.cutoffstart = '$dfrom' AND cutoffend = '$dto' AND a.teachingtype='$tnt' $wC GROUP BY a.employeeid ORDER BY qDeptId, campusid, qFullname";                
        return $this->db->query($this->attSummQuery)->result_array();
    }
    public function emp_confirmed_ntperdept($dfrom="", $dto="", $tnt="", $eid="", $office="", $campus="",$deptid="",$isactive="",$company_campus=""){
        $wC = "";
        if($isactive != "all"){
          if($isactive=="1"){
            $wC .= " AND isactive ='1'";
          }
          if($isactive=="0"){
            $wC .= " AND isactive ='0'";
          }
        }
        if($company_campus && $company_campus!="all") $wC .= " AND a.company_campus='$company_campus'";
        if($eid) $wC .= " AND a.employeeid='$eid'"; 
        if($office) $wC .= " AND a.office='$office'";
        if($deptid) $wC .= " AND a.deptid='$deptid'";
        if($campus) $wC .= " AND a.campusid='$campus'"; 
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND a.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
        }
        $wC .= $utwc;
        $this->attSummQuery = "
            SELECT a.employeeid as qEmpId,deptid as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, b.description AS qDepartment,DATE(c.timestamp) as dateconfirmed, c.*,d.fixedday,a.campusid
                FROM employee a
                INNER JOIN code_office b ON a.office = b.code 
                INNER JOIN attendance_confirmed_nt c ON a.employeeid = c.employeeid
                LEFT JOIN payroll_employee_salary d ON d.employeeid=a.employeeid
                WHERE (a.dateresigned = '1970-01-01' OR a.dateresigned IS NULL) AND c.cutoffstart = '$dfrom' AND cutoffend = '$dto' AND a.teachingtype='$tnt' $wC GROUP BY a.employeeid ORDER BY campusid, qDeptId, qFullname";                
        return $this->db->query($this->attSummQuery)->result_array();
    }

    
    public function giveBaseQuery(){
        return $this->indSummQuery;
    }

    public function giveAttSummQuery(){
        return $this->attSummQuery;
    }
    
    public function checkLeaveBalance($eid = ""){
        $return = "";
        $query = $this->db->query("SELECT leavetype FROM employee WHERE employeeid='$eid'");
        $ltype = $query->row(0)->leavetype;
        if($ltype){
            $query = $this->db->query("SELECT code_request FROM code_request_form WHERE leavetype='$ltype' AND CURRENT_DATE BETWEEN startdate AND enddate");
            if($query->num_rows() > 0){
                $crequest = $query->row(0)->code_request;
                $query = $this->db->query("SELECT (A.credits - IFNULL(B.creditleave,0)) as leavebalance
                                            FROM
                                            (SELECT credits FROM code_request_form WHERE CURRENT_DATE BETWEEN startdate AND enddate AND leavetype='$ltype') as A, 
                                            (SELECT SUM(no_days) as creditleave FROM leave_request WHERE employeeid='$eid' AND leavetype='$crequest') as B")->result();
                foreach($query as $row){
                    $return = $row->leavebalance;
                }
            }
        }
        return $return;
    }

    # ica-hyperion 22012
    # by justin (with e)
    public function saveAttendanceSummaryPerDay($data, $empid, $teachingtype, $remove_absent=false){
        if(isset($data["workhours_perday"])){
            $workhours_perday = $data["workhours_perday"];
            unset($data["workhours_perday"]);
        }
        $perday_lec = $perday_lab = $perday_admin = $perday_rle = "";
        foreach ($data as $date => $info) {
            $is_allow_add = false;
            $this->db->query("DELETE FROM employee_attendance_detailed WHERE employeeid='$empid' AND sched_date='$date'");
            
            foreach ($info as $key => $value){
                if($value){
                    $is_allow_add = true;
                    break;
                }
            }

            if($is_allow_add || isset($workhours_perday[$date])){
                $overtime = $late = $undertime = $absents = "";

                if($teachingtype == "teaching"){
                    $overtime = isset($info["overtime"]) ? $this->attcompute->sec_to_hm($info["overtime"]) : "";
                    $late = isset($info["late"]) ? $info["late"] : "";
                    $undertime = isset($info["undertime"]) ? $info["undertime"] : "";
                    $ot_amount = isset($info["ot_amount"]) ? $info["ot_amount"] : "";
                    $ot_type = isset($info["ot_type"]) ? $info["ot_type"] : "";
                    $substitute = ($info["substitute"] != "0:00") ? $info["substitute"] : "";
                    if(isset($info["absent"])) $absents = ($info["absent"]) ? $this->convertTimeToNumber(date("H:i", strtotime(($info["absent"])))) : $info["absent"];
                    else $absents = "";

                    /*perday workhours*/
                    if(isset($workhours_perday[$date])){
                        foreach($workhours_perday[$date] as $aimsdept => $workhours_tmp){
                            foreach($workhours_tmp as $type_key => $perdays){
                                /*for suspension less hours*/
                                $perdays["work_hours"] -= $perdays["suspension_less"];
                                if($type_key == "LEC"){

                                    $perday_lec .= "work_hours"."=".$perdays["work_hours"]."/late_hours=".$perdays["late_hours"]."/deduc_hours=".$perdays["deduc_hours"]."/aimsdept=".$perdays["aimsdept"]."/suspension=".$perdays["is_suspension"]."&";
                                }
                                else if($type_key == "LAB"){

                                    $perday_lab .= "work_hours"."=".$perdays["work_hours"]."/late_hours=".$perdays["late_hours"]."/deduc_hours=".$perdays["deduc_hours"]."/aimsdept=".$perdays["aimsdept"]."/suspension=".$perdays["is_suspension"]."&";
                                }
                                else if($type_key == "ADMIN"){

                                    $perday_admin .= "work_hours"."=".$perdays["work_hours"]."/late_hours=".$perdays["late_hours"]."/deduc_hours=".$perdays["deduc_hours"]."/aimsdept=".$perdays["aimsdept"]."/suspension=".$perdays["is_suspension"]."&";
                                }
                                else if($type_key == "RLE"){

                                    $perday_rle .= "work_hours"."=".$perdays["work_hours"]."/late_hours=".$perdays["late_hours"]."/deduc_hours=".$perdays["deduc_hours"]."/aimsdept=".$perdays["aimsdept"]."/suspension=".$perdays["is_suspension"]."&";
                                }
                            }
                        }
                    }
                    /*end*/
                }else{
                    $overtime = ($info["overtime"]) ? $this->attcompute->sec_to_hm($info["overtime"]) : $info["overtime"];
                    $late = ($info["late"]) ? $this->attcompute->sec_to_hm($info["late"]) : $info["late"];
                    $undertime = ($info["undertime"]) ? $this->attcompute->sec_to_hm($info["undertime"]) : $info["undertime"];
                    $absents = ($info["absent"]) ? $this->attcompute->sec_to_hm($info["absent"]) : $info["absent"];
                    $ot_amount = isset($info["ot_amount"]) ? $info["ot_amount"] : 0;
                    $ot_type = isset($info["ot_type"]) ? $info["ot_type"] : "";
                }

                if($remove_absent){
                    $absents = "";
                }

                $save_data = array(
                    "employeeid" => $empid,
                    "sched_date" => $date,
                    "substitute"   => isset($substitute) ? $substitute : "",
                    "overtime"   => $overtime,
                    "late"       => $late,
                    "undertime"  => $undertime,
                    "absents"    => $absents,
                    "ot_amount"    => $ot_amount,
                    "ot_type"    => $ot_type,
                    "lec"    => $perday_lec,
                    "lab"    => $perday_lab,
                    "admin"    => $perday_admin,
                    "rle"    => $perday_rle
                );
                
                $this->db->insert("employee_attendance_detailed", $save_data);
            }
            $perday_lec = $perday_lab = $perday_admin = $perday_rle = "";
        }
    }

    public function convertTimeToNumber($value, $revert=false){
        switch ($revert) {
            case true:
                $exp_value = explode(".", $value);

                if(count($exp_value) > 0){
                    $hrs = $exp_value[0];
                    $min = (isset($exp_value[1])) ? (60 * ("0.".$exp_value[1])) : "00";

                    return $hrs .":". ((int) $min);
                }else return "Failed to convert time";
                break;
            
            default:
                $exp_time = explode(":", $value);

                if(count($exp_time) > 0){
                    list($hrs, $min) = $exp_time;
                    $hrs = (int) $hrs;
                    $min = ((int) $min) / 60;

                    return ($hrs + $min);
                }else return "Failed to convert time";
                break;
        }
    }
    # end ica-hyperion 22012

    /**
     * @revised Angelica
     * Computation for employee attendance summary per cutoff. This will insert computed summary to corresponding table (attendance_confirmed).
     *
     * @param String $from_date
     * @param String $to_date
     * @param String $empid
     *
     * @return string
     */
    public function saveEmployeeAttendanceSummaryTeaching($from_date='',$to_date='',$payroll_start='',$payroll_end='',$payroll_quarter='',$empid='',$isBED=false,$hold_status='',$username=""){
        $dtrend_tmp = $to_date;
        $payrollend_tmp = $payroll_end;
        $tdaily_present = "";
        list($tlec,$tlab,$tadmin,$tabsent,$tdaily_absent,$tel,$tvl,$tsl,$tol,$tdlec,$tdlab,$tdadmin,$holiday,$hasSched,$hasLog,$twork_lec,$twork_lab,$twork_admin,$workhours_arr,$date_list,$daily_present,$substitute,$tholiday,$tsuspension,$trle,$tdrle,$twork_admin,$twork_rle) = $this->computeEmployeeAttendanceSummaryTeaching($from_date,$dtrend_tmp,$empid,false,$isBED);
        $tot_sub = $substitute["tot_sub"];
        if($daily_present) $tdaily_present = implode(",", $daily_present);
        $isnodtr = $this->extensions->checkIfCutoffNoDTR($from_date,$to_date);
        // if($isnodtr){
        //     $date_list = $this->removeLateUtAbsent($date_list, $isnodtr);
        //     $tlec = $tlab = $tadmin = $tabsent = $tdlec = $tdlab = $tdadmin = $trle = $tdrle = $absent_day = $tdaily_absent = '';
        // }

        $this->attendance->saveAttendanceSummaryPerDay($date_list, $empid, "teaching");

        $query = $this->db->query("SELECT * FROM attendance_confirmed WHERE cutoffstart='$from_date' AND cutoffend='$to_date' AND employeeid='$empid'");
        if($query->num_rows() == 0){
            $base_id = '';
            $res = $this->db->query("INSERT INTO attendance_confirmed (employeeid,cutoffstart,cutoffend,overload,substitute,latelec,latelab,lateadmin,absent,day_absent,day_present,eleave,vleave,sleave,oleave,deduclec,deduclab,deducadmin,date_processed,
                                                                        payroll_cutoffstart,payroll_cutoffend,quarter,hold_status,hold_status_change,f_dtrend,f_payrollend,tholiday,tsuspension) 
                                VALUES ('$empid','$from_date','$to_date','','$tot_sub','$tlec','$tlab','$tadmin','$tabsent','$tdaily_absent','$tdaily_present','$tel','$tvl','$tsl','$tol','$tdlec','$tdlab','$tdadmin','". date("Y-m-d H:i:s") ."',
                                            '$payroll_start','$payroll_end','$payroll_quarter','$hold_status','$hold_status','$dtrend_tmp','$payrollend_tmp','$tholiday','$tsuspension')");
            if($res) $base_id = $this->db->insert_id();
            if($base_id){

                /* insert substitute list */
                foreach($substitute["list"] as $substitutes){
                    if($substitutes){
                        foreach($substitutes as $subid => $subdata){
                            $subdata["base_id"] = $base_id;
                            $this->db->insert("attendance_confirmed_substitute_hours", $subdata);
                        }
                    }
                }

                ///< perdepartment work hours
                foreach ($workhours_arr as $aimsdept => $leclab_arr) {
                    foreach ($leclab_arr as $type => $sec) {
                        $work_hours = $this->attcompute->sec_to_hm($sec['work_hours']);
                        $late_hours = $this->attcompute->sec_to_hm($sec['late_hours']);
                        $deduc_hours = $this->attcompute->sec_to_hm($sec['deduc_hours']);
                        $leave_project = $this->attcompute->sec_to_hm($sec['leave_project']);
                        $this->db->query("INSERT INTO workhours_perdept (base_id, work_days, work_hours, late_hours, deduc_hours, type, aimsdept,leave_project) VALUES ('$base_id',0,'$work_hours','$late_hours','$deduc_hours','$type','$aimsdept','$leave_project')");
                    }
                }
            } // end if base_id
        }else{
            return false;
        }

        if($res)    return true;
        else        return false;

    } ///< end of public function


    /**
     * @revised Angelica
     * This will insert computed attendance summary to corresponding table (attendance_confirmed_nt).
     *
     * @param String $from_date
     * @param String $to_date
     * @param String $empid
     *
     * @return boolean
     */
    public function saveEmployeeAttendanceSummaryNonTeaching($from_date='',$to_date='',$payroll_start='',$payroll_end='',$payroll_quarter='',$empid='',$hold_status='',$username=""){
        $this->load->model('utils');
        $startdate = $enddate = $quarter = $isnodtr = "";
        $payrollcutoff = $this->extras->getPayrollCutoff($from_date, $to_date);
        foreach($payrollcutoff as $cutoff_info){
            $startdate = $cutoff_info['startdate'];
            $enddate = $cutoff_info['enddate'];
            $quarter = $cutoff_info['quarter'];
            $isnodtr = $cutoff_info['nodtr'];
        }
        list($tabsent,$tlec,$tlab,$tadmin,$tdlec,$tdlab,$tdadmin,$tutlec,$totr,$totrest,$tothol,$tel,$tvl,$tsl,$tsl,$tol,$tholiday,$holiday,$hasSched,$hasLog,$workdays,$ot_list,$date_list,$tsc,$workhours_arr,$twork_lec,$twork_lab,$twork_admin,$day_absent,$is_flexi) = $this->computeEmployeeAttendanceSummaryNonTeaching($from_date,$to_date,$empid);

         // REMOVE LATE AND ABSENT IF PASOK SA CONDITION NG FLEXI
         $remove_absent = false;
         if($is_flexi){
            if($workdays >= 12){
                $remove_absent = true;
                $tabsent = $day_absent = 0;
            }
        }

        // if($isnodtr){
        //     $date_list = $this->removeLateUtAbsent($date_list, $isnodtr);
        //     $tabsent = $tlec = $tutlec = '';
        // }

        # ica-hyperion 22012
        $this->attendance->saveAttendanceSummaryPerDay($date_list, $empid, "nonteaching", $remove_absent);

        // Save to database
        $query = $this->utils->getSingleTblData('attendance_confirmed_nt',array('id'),array('cutoffstart'=>$from_date,'cutoffend'=>$to_date,'employeeid'=>$empid));
        if($query->num_rows() == 0){
            $res = $this->db->query("INSERT INTO attendance_confirmed_nt (employeeid, cutoffstart, cutoffend, workdays, otreg, otrest, othol, lateut, ut, absent, day_absent, eleave, vleave, sleave, oleave, status, isholiday, forcutoff, payroll_cutoffstart, payroll_cutoffend, quarter, date_processed, scleave, usertype) 
                                     VALUES ('$empid', '$from_date', '$to_date', '$workdays', '$totr', '$totrest', '$tothol', '$tlec', '$tutlec', '$tabsent', '$day_absent', '$tel', '$tvl', '$tsl', '$tol', 'SUBMITTED', '$tholiday', '1', '$startdate', '$enddate', '$quarter', '". date("Y-m-d h:i:s") ."', '$tsc', '$username')");

            $base_id = '';
            if($res) $base_id = $this->db->insert_id();

            if($base_id){
                ///< perdepartment work hours
                foreach ($workhours_arr as $aimsdept => $leclab_arr) {
                    foreach ($leclab_arr as $type => $sec) {
                        if($sec['late_hours'] > 28800) $sec['late_hours'] = 28800;
                        if($sec['deduc_hours'] > 28800) $sec['deduc_hours'] = 28800;
                    
                        $late_hours = $this->attcompute->sec_to_hm($sec['late_hours']);
                        $deduc_hours = $this->attcompute->sec_to_hm($sec['deduc_hours']);
                        $this->db->query("INSERT INTO workhours_perdept_nt (base_id, work_days, late_hours, deduc_hours, type, aimsdept) VALUES ('$base_id',0,'$late_hours','$deduc_hours','$type','$aimsdept')");
                        $inserted_id[$this->db->insert_id()] = $this->db->insert_id();
                    }
                }
                ///<update workhours ( will refer to payroll cutoff)
                list($tabsent,$tlec,$tlab,$tadmin,$tdlec,$tdlab,$tdadmin,$tutlec,$totr,$totrest,$tothol,$tel,$tvl,$tsl,$tsl,$tol,$tholiday,$holiday,$hasSched,$hasLog,$workdays,$ot_list,$date_list,$tsc,$workhours_arr,$twork_lec,$twork_lab,$twork_admin) = $this->computeEmployeeAttendanceSummaryNonTeaching($from_date,$to_date,$empid);
                // if($isnodtr){
                //     $date_list = $this->removeLateUtAbsent($date_list, $isnodtr);
                //     $tlec = $tlab = $tadmin = $tabsent = $tdlec = $tdlab = $tdadmin = $absent_day = $tdaily_absent = '';
                // }
                $this->db->query("UPDATE attendance_confirmed_nt SET workhours_lec='$twork_lec', workhours_lab='$twork_lab', workhours_admin='$twork_admin' WHERE id='$base_id'");

                ///< perdepartment work hours
                foreach ($workhours_arr as $aimsdept => $leclab_arr) {
                    foreach ($leclab_arr as $type => $sec) {
                        $work_hours = $this->attcompute->sec_to_hm($sec['work_hours']);
                        $late_hours = $this->attcompute->sec_to_hm($sec['late_hours']);
                        $deduc_hours = $this->attcompute->sec_to_hm($sec['deduc_hours']);
                        $update_query = $this->db->query("UPDATE workhours_perdept_nt SET work_hours='$work_hours' WHERE base_id='$base_id' AND `type`='$type' AND aimsdept='$aimsdept' ");
                        $inserted_row = $this->checkWorkhoursExisting($base_id, $type, $aimsdept);
                        if(!in_array($inserted_row, $inserted_id)) $this->db->query("INSERT INTO workhours_perdept_nt (base_id, work_hours, work_days,type, aimsdept) VALUES ('$base_id', '$work_hours' ,0,'$type','$aimsdept')");
                    }
                }

                foreach ($ot_list as $ot_data_tmp){
                    $ot_data = $ot_data_tmp;
                    $ot_data["base_id"] = $base_id;

                    $this->db->insert('attendance_confirmed_nt_ot_hours', $ot_data);
                }
            }

        }else{
            return false;
        }

        if($res)    return true;
        else        return false;

    } ///< end of public function      

    /**
     * @revised Angelica
     * Computation for employee attendance summary with given date range. (Teaching)
     *
     * @param String $from_date
     * @param String $to_date
     * @param String $empid
     *
     * @return array
     */
    function computeEmployeeAttendanceSummaryTeaching($from_date='',$to_date='',$empid='',$toCheckPrevAtt=false,$isBED=false){
        $this->load->model("substitute");
        $not_included_ol = array("ABSENT", "EL", "VL", "SL", "CORRECTION", "AL");
        $date_list = array();
        $edata          = 'NEW';
        $deptid = $this->employee->getindividualdept($empid);
        $tdaily_absent = '';
        $tlec = $tlab = $tadmin = $trle = $tabsent = $tabsentperday = $tel = $tvl = $tsl = $tol = $tdlec = $tdlab = $tdadmin = $tdrle = 0; 
        $tempabsent = $lateutlec = $lateutlab = $lateutrle = $twork_lec = $twork_lab = $twork_admin = $twork_rle = 0;
        $workhours_arr = array();
        $workhours_perday = array();
        $aimsdept = '';
        $hasLog = $isSuspension = $isCreditedHoliday = false;
        $firstDate = true;
        $last_day = '';
        $absent_day = '';
        $date_list_absent = $tot_sub = 0;
        $tholiday = $tsuspension = 0;
        $qdate = $this->attcompute->displayDateRange($from_date, $to_date);
        $fromtime = $totime = "";
        ///< based from source -> individual attendance_report
        $daily_absent = $daily_present = array();
        $used_time = array();
        foreach ($qdate as $rdate) {
            $is_half_holiday = true;
            $has_after_suspension = false;
            $has_last_log = false;
            // Holiday
            $isSuspension = $hasSched = false;
            $holiday = $this->attcompute->isHolidayNew($empid,$rdate->dte,$deptid ); 


            $dispLogDate = date("d-M (l)",strtotime($rdate->dte));
            $sched = $this->attcompute->displaySched($empid,$rdate->dte);
            $countrow = $sched->num_rows();
                
            $isValidSchedule = true;

            if($countrow > 0){
                if($sched->row(0)->starttime == '00:00:00' && $sched->row(0)->endtime == '00:00:00') $isValidSchedule = false;
            }

            if($holiday){
                $holidayInfo = $this->attcompute->holidayInfo($rdate->dte);
                if($holidayInfo['holiday_type']==5){
                    $isSuspension = true;
                    $tsuspension++;
                }else{
                    $tholiday++;
                }
            }else{
                if($countrow > 0){
                    $is_holiday_halfday = $this->attcompute->isHolidayNew($empid, $rdate->dte,$deptid, "", "on");
                    list($fromtime, $totime) = $this->extensions->getHolidayHalfdayTime($rdate->dte);
                    if($is_holiday_halfday && ($fromtime && $totime) ){
                        $holidayInfo = $this->attcompute->holidayInfo($rdate->dte);
                        $is_half_holiday = true;
                        if($holidayInfo["holiday_type"] == 5) $tsuspension++;
                        else $tholiday++;
                    }
                }
            }

            if(!$toCheckPrevAtt){
                ///< for validation of absent for 1st day in range. this will check for previous day attendance
                if($firstDate && $holiday){
                    $hasLog = $this->attendance->checkPreviousSchedAttendanceTeaching($rdate->dte,$empid);
                    $firstDate = false;
                }
            }

            // substitute
            list($substitute["list"][$rdate->dte], $substitute_tot) = $this->substitute->substituteTotalHours($rdate->dte, $empid, $holiday, isset($holidayInfo['holiday_type'])? $holidayInfo['holiday_type'] : "");
            $date_list[$rdate->dte]["substitute"] = $substitute_tot;

            $bed_isfirsthalf_absent = $bed_issechalf_absent = $bed_iswholeday_absent = true;
            $bed_setup = $this->getBEDAttendanceSetup();
            $perday_info = array();
            if($countrow > 0 && $isValidSchedule){
                $hasSched = true;

                ///< for validation of holiday (will only be credited if not absent during last schedule)
                $hasLogprev = $hasLog;
                $hasLog = false;

                if($hasLogprev || $isSuspension)    $isCreditedHoliday = true;
                else                                $isCreditedHoliday = false;

                $tempsched = "";
                $seq = 0;
                $isFirstSched = true;
                $bed_rowcount_half = 0;
                foreach($sched->result() as $rsched){
                    $persched_info = array();

                    if($tempsched == $dispLogDate)  $dispLogDate = "";
                    $seq += 1;
                    $stime = $rsched->starttime;
                    $etime = $rsched->endtime; 
                    $type  = $rsched->leclab;
                    $tardy_start = $rsched->tardy_start;
                    $absent_start = $rsched->absent_start;
                    $earlydismissal = $rsched->early_dismissal;
                    $aimsdept = $rsched->aimsdept;
                    $flexible = $rsched->flexible;
                    $is_flexi = ($flexible == "YES") ? true : false;

                    // logtime
                    list($login,$logout,$q,$haslog_forremarks,$used_time) = $this->attcompute->displayLogTime($empid,$rdate->dte,$stime,$etime,$edata,$seq,$absent_start,$earlydismissal,$used_time);
                    if($haslog_forremarks) $hasLog = true;
                    
                    // Leave
                    list($el,$vl,$sl,$ol,$oltype,$ob)     = $this->attcompute->displayLeave($empid,$rdate->dte,'',$stime,$etime,$seq);
                    if($ol == "DIRECT"){
                        $is_wfh = $this->attcompute->isWfhOB($empid,$rdate->dte);
                        if($is_wfh->num_rows() == 1){
                            $ob_id = $is_wfh->row()->aid;
                            $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$rdate->dte);
                            if($hastime->num_rows() == 0) $ol = $oltype = $ob = 0;
                        }
                    }

                    // Absent
                    $absent = $this->attcompute->displayAbsent($stime,$etime,$login,$logout,$empid,$rdate->dte,$earlydismissal, $absent_start);

                    if ($vl >= 1 || $el >= 1 || $sl >= 1 || ($holiday && $isCreditedHoliday))   $absent = "";

                    if ($vl > 0 || $el > 0 || $sl > 0 || ($ol == "DIRECT" && ($login && $logout)) /* || $ob > 0*/){
                        $absent = "";
                    }
                    
                    if($login && $logout && $vl == 0 && $el == 0 && $sl == 0 && (!$holiday && !$isCreditedHoliday)) $daily_present[$rdate->dte] = $rdate->dte;

                    // Late / Undertime
                    list($lateutlec,$lateutlab,$lateutadmin,$tschedlec,$tschedlab,$tschedadmin,$lateutrle,$tschedrle) = $this->attcompute->displayLateUT($stime,$etime,$tardy_start,$login,$logout,$type,$absent);
                    if($el || $vl || $sl || ($holiday && $isCreditedHoliday)){
                         $lateutlec = $lateutlab = $lateutadmin = $tschedlec = $tschedlab = $tschedadmin = $lateutrle = $tschedrle = "";
                    }

                    if($isBED){
                        $isAbsent = $this->attcompute->exp_time($absent) > 0 ? 1 : 0;
                        list($rowcount_half,$isfirsthalf_absent,$issechalf_absent,$iswholeday_absent) = 
                        $this->getBEDPerdayAbsent($bed_setup,array('sched_start'=>$stime,'sched_end'=>$etime,'isAbsent'=>$isAbsent));
                        
                        $bed_rowcount_half += $rowcount_half;

                        $bed_isfirsthalf_absent  =  $bed_isfirsthalf_absent ? (!$isfirsthalf_absent ? false : true) : false ;
                        $bed_issechalf_absent    =  $bed_issechalf_absent ? (!$issechalf_absent ? false : true) : false ;
                        $bed_iswholeday_absent    =  $bed_iswholeday_absent ? (!$iswholeday_absent ? false : true) : false ;
                    }

                    if($absent && !$type) $absent = '';

                    $tempsched = $dispLogDate;
                    /*
                     * ----------------Total---------------------------------------------
                     */ 
                    $absent = $is_flexi ? "" : $absent;
                    // Absent
                    if($absent){
                        if(!$isBED) $tabsent += $this->attcompute->exp_time($absent) > 0 ? 1 : 0;
                        // if($rdate->dte != $absent_day) $tdaily_absent .= substr($rdate->dte, 5)." 1/";
                        // $absent_day = $rdate->dte;

                    }
                    
                    // Leave
                    if($dispLogDate){
                        $tel      += $el;
                        $tvl      += $vl;
                        $tsl      += $sl;
                        $tol      += (!in_array($ol, $not_included_ol) && $ol >= 1) ? (($date_tmp != $rdate->dte) ? 1 : -0.5) : 0;
                    }
                    
                    // Late / UT
                    if($tlec){
                        $secs  = strtotime($lateutlec)-strtotime("00:00:00");
                        if($secs>0) $tlec = date("H:i",strtotime($tlec)+$secs);
                    }else
                        $tlec    = $lateutlec;

                    $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutlec) - strtotime("00:00:00")) : $lateutlec;
                        
                    if($tlab){
                        $secs  = strtotime($lateutlab)-strtotime("00:00:00");
                        if($secs>0) $tlab = date("H:i",strtotime($tlab)+$secs);
                    }else
                        $tlab    = $lateutlab;

                    $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutlab) - strtotime("00:00:00")) : $lateutlab;

                    if($tadmin){
                        $secs  = strtotime($lateutadmin)-strtotime("00:00:00");
                        if($secs>0) $tadmin = date("H:i",strtotime($tadmin)+$secs);
                    }else
                        $tadmin    = $lateutadmin;

                    $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutadmin) - strtotime("00:00:00")) : $lateutadmin;

                    if($trle){
                        $secs  = strtotime($lateutrle)-strtotime("00:00:00");
                        if($secs>0) $trle = date("H:i",strtotime($trle)+$secs);
                    }else
                        $trle    = $lateutrle;

                    $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutrle) - strtotime("00:00:00")) : $lateutrle;

                    // Deductions
                    if($tschedlec)      $tdlec += $this->attcompute->exp_time($tschedlec);
                    $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->attcompute->exp_time($tschedlec)) : $this->attcompute->exp_time($tschedlec);

                    if($tschedlab)      $tdlab += $this->attcompute->exp_time($tschedlab);
                    $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->attcompute->exp_time($tschedlab)) : $this->attcompute->exp_time($tschedlab);

                    if($tschedadmin)    $tdadmin += $this->attcompute->exp_time($tschedadmin);
                    $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->attcompute->exp_time($tschedadmin)) : $this->attcompute->exp_time($tschedadmin);

                    if($tschedrle)    $tdrle += $this->attcompute->exp_time($tschedrle);
                    $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->attcompute->exp_time($tschedrle)) : $this->attcompute->exp_time($tschedrle);

                    $persched_info['sched_type'] = $type;
                    $persched_info['lateut_lec'] = $lateutlec;
                    $persched_info['lateut_lab'] = $lateutlab;
                    $persched_info['lateut_admin'] = $lateutadmin;
                    $persched_info['lateut_rle'] = $lateutrle;
                    $persched_info['deduc_lec'] = $tschedlec;
                    $persched_info['deduc_lab'] = $tschedlab;
                    $persched_info['deduc_admin'] = $tschedadmin;
                    $persched_info['deduc_rle'] = $tschedrle;
                    array_push($perday_info, $persched_info);
                    
                    if(!$tschedadmin && !$absent) $hasLog = true;

                    if($login && $logout && $isFirstSched) $has_last_log = true;
                    $isAffectedAfter = $this->attcompute->affectedBySuspensionAfter(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));

                    // if(!$holiday && !$isCreditedHoliday){
                        list($work_lec,$work_lab,$work_admin,$workhours_arr,$work_rle) = $this->getWorkhoursPerdeptArr($stime,$etime,$type,$aimsdept,$workhours_arr,$lateutlec,$tschedlec,$lateutlab,$tschedlab,$lateutadmin,$tschedadmin,$empid,$rdate->dte,$deptid,$sl,$vl,$login,$logout, $workhours_perday,$rdate->dte,$lateutrle,$tschedrle,$has_last_log,$has_after_suspension,$isFirstSched);
                        $twork_lec += $work_lec;
                        $twork_lab += $work_lab;
                        $twork_admin += $work_admin;
                        $twork_rle += $work_rle;
                    // }
                    $workhours_perday = $this->getWorkhoursPerdayArr($stime,$etime,$type,$aimsdept,$rdate->dte,$workhours_perday,$lateutlec,$tschedlec,$lateutlab,$tschedlab,$lateutadmin,$tschedadmin,$holiday,$empid,$rdate->dte,$deptid,$sl,$vl,$login,$logout,$lateutrle,$tschedrle,$has_last_log,$has_after_suspension, $isFirstSched);
                    
                    if($isAffectedAfter) $has_after_suspension = true;
                    $isFirstSched = false;
                }   // end foreach sched
               
                if($isBED){
                    if($bed_rowcount_half == $countrow) {
                        $bed_issechalf_absent = $bed_iswholeday_absent = false;
                    }elseif($bed_rowcount_half == 0){
                        $bed_isfirsthalf_absent = $bed_iswholeday_absent = false;
                    }

                    if((!$login || !$logout || $login == "0000-00-00 00:00:00" || $logout == "0000-00-00 00:00:00") && ($bed_issechalf_absent || $bed_isfirsthalf_absent)){
                        $bed_issechalf_absent = true;
                    }

                    $bed_absent = 0;
                    if($bed_iswholeday_absent){
                        $bed_absent = 1;
                        $tdadmin += 28800; ///< 8hrs for 1day absent -- BED is fixed to admin TYPE
                        $day_absent = substr($rdate->dte, 5);
                        $tdaily_absent .= $day_absent." 1/";
                        $date_list_absent += 28800;
                    }else{
                        if($bed_issechalf_absent || $bed_isfirsthalf_absent){
                            $bed_absent = 0.5;
                            $tdadmin += 14400; ///< 4hrs for half day absent -- BED is fixed to admin TYPE
                            $day_absent = substr($rdate->dte, 5);
                            $tdaily_absent .= $day_absent." 0.5/";
                            $date_list_absent += 14400;
                        }

                        ///< construct lateut
                        ///< if half/wholeday present , add deduc to late per specific sched

                        $lateut_perday = $this->constructLateUTBedSummary($perday_info,$bed_isfirsthalf_absent,$bed_issechalf_absent,$bed_rowcount_half);
                        $date_list_tlec = ($lateut_perday['tlec']) ? $this->attcompute->sec_to_hm($lateut_perday['tlec']) : 0;
                        $date_list_tlab = ($lateut_perday['tlab']) ? $this->attcompute->sec_to_hm($lateut_perday['tlab']) : 0;
                        $date_list_tadmin = ($lateut_perday['tadmin']) ? $this->attcompute->sec_to_hm($lateut_perday['tadmin']) : 0;
                        $date_list_trle = ($lateut_perday['trle']) ? $this->attcompute->sec_to_hm($lateut_perday['trle']) : 0;

                        if($tlec){
                            if($lateut_perday['tlec']) $tlec = $this->attcompute->sec_to_hm($this->attcompute->exp_time($tlec) + $lateut_perday['tlec']);
                        }else $tlec = $lateut_perday['tlec'] ? $this->attcompute->sec_to_hm($lateut_perday['tlec']) : '';
                        if($date_list_tlec) $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($tlec) - strtotime("00:00:00")) : $date_list_tlec;

                        if($tlab){
                            if($lateut_perday['tlab']) $tlab = $this->attcompute->sec_to_hm($this->attcompute->exp_time($tlab) + $lateut_perday['tlab']);
                        }else $tlab = $lateut_perday['tlab'] ? $this->attcompute->sec_to_hm($lateut_perday['tlab']) : '';
                        if($date_list_tlab) $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($tlab) - strtotime("00:00:00")) : $date_list_tlab;

                        if($tadmin){
                            if($lateut_perday['tadmin']) $tadmin = $this->attcompute->sec_to_hm($this->attcompute->exp_time($tadmin) + $lateut_perday['tadmin']);
                        }else $tadmin = $lateut_perday['tadmin'] ? $this->attcompute->sec_to_hm($lateut_perday['tadmin']) : '';
                        if($date_list_tadmin) $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($tadmin) - strtotime("00:00:00")) : $date_list_tadmin;

                        if($trle){
                            if($lateut_perday['trle']) $trle = $this->attcompute->sec_to_hm($this->attcompute->exp_time($trle) + $lateut_perday['trle']);
                        }else $trle = $lateut_perday['trle'] ? $this->attcompute->sec_to_hm($lateut_perday['trle']) : '';
                        if($date_list_trle) $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($trle) - strtotime("00:00:00")) : $date_list_trle;

                    }

                    $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $date_list_absent) : $date_list_absent;
                    $tabsent     += $bed_absent;
                }
                if($this->hasLogtime($empid, $rdate->dte) == 0) $tdaily_absent .= substr($rdate->dte, 5)." 1/";

            } // end if valid sched
            $tot_sub += $this->attcompute->exp_time($substitute_tot);
            $date_list_absent = $substitute_tot = 0;
            $hasLog = "";
        } // end loop dates
        $tot_sub = $this->attcompute->sec_to_hm($tot_sub);
        $twork_lec = $twork_lec ? $this->attcompute->sec_to_hm($twork_lec) : "";
        $twork_lab = $twork_lab ? $this->attcompute->sec_to_hm($twork_lab) : "";
        $twork_admin = $twork_admin ? $this->attcompute->sec_to_hm($twork_admin) : "";
        $twork_rle = $twork_rle ? $this->attcompute->sec_to_hm($twork_rle) : "";

        $tdlec = ($tdlec ? $this->attcompute->sec_to_hm($tdlec) : "");
        $tdlab = ($tdlab ? $this->attcompute->sec_to_hm($tdlab) : "");
        $tdadmin = ($tdadmin ? $this->attcompute->sec_to_hm($tdadmin) : "");
        $tdrle = ($tdrle ? $this->attcompute->sec_to_hm($tdrle) : "");
        $date_list["workhours_perday"] = $workhours_perday;
        $substitute["tot_sub"] = $tot_sub;
        return array($tlec,$tlab,$tadmin,$tabsent,$tdaily_absent,$tel,$tvl,$tsl,$tol,$tdlec,$tdlab,$tdadmin,$holiday,$hasSched,$hasLog,$twork_lec,$twork_lab,$twork_admin,$workhours_arr,$date_list,$daily_present,$substitute,$tholiday,$tsuspension,$trle,$tdrle,$twork_admin,$twork_rle);
    } ///< end of function computeEmployeeAttendanceSummaryTeaching

    function constructLateUTBedSummary($perday_info=array(),$bed_isfirsthalf_absent=false,$bed_issechalf_absent=false,$bed_rowcount_half=0){
        $lateut_perday = array('tlec'=>0,'tlab'=>0,'tadmin'=>0,'trle'=>0);

        foreach ($perday_info as $key => $persched_info) {
            $lec = $lab = $admin = 0;

            $lec = $this->attcompute->exp_time($persched_info['deduc_lec']);
            $lab = $this->attcompute->exp_time($persched_info['deduc_lab']);
            $admin = $this->attcompute->exp_time($persched_info['deduc_admin']);
            $rle = $this->attcompute->exp_time($persched_info['deduc_rle']);

            $late_lec = $lec + $this->attcompute->exp_time($persched_info['lateut_lec']);
            $late_lab = $lab + $this->attcompute->exp_time($persched_info['lateut_lab']);
            $late_admin = $admin + $this->attcompute->exp_time($persched_info['lateut_admin']);
            $late_rle = $rle + $this->attcompute->exp_time($persched_info['lateut_rle']);

            if($key < $bed_rowcount_half){
                if(!$bed_isfirsthalf_absent){
                    if($persched_info['sched_type'] == 'LEC'){ 
                        $lateut_perday['tlec'] +=  $late_lec;
                    }elseif($persched_info['sched_type'] == 'LAB'){ 
                        $lateut_perday['tlab'] += $late_lab;
                    }elseif($persched_info['sched_type'] == 'ADMIN'){ 
                        $lateut_perday['tadmin'] += $late_admin;
                    }else{                                        
                        $lateut_perday['trle'] += $late_rle;
                    }
                }
            }else{
                if(!$bed_issechalf_absent){
                    if($persched_info['sched_type'] == 'LEC'){ 
                        $lateut_perday['tlec'] +=  $late_lec;
                    }elseif($persched_info['sched_type'] == 'LAB'){ 
                        $lateut_perday['tlab'] += $late_lab;
                    }elseif($persched_info['sched_type'] == 'ADMIN'){ 
                        $lateut_perday['tadmin'] += $late_admin;
                    }else{                                        
                        $lateut_perday['trle'] += $late_rle;
                    }
                }
            }
        }
        return $lateut_perday;
    }

    function getWorkhoursPerdeptArr($stime='',$etime='',$type='',$aimsdept='',$workhours_arr=array(),$lateutlec='',$tschedlec='',$lateutlab='',$tschedlab='',$lateutadmin='',$tschedadmin, $empid='',$date='',$deptid='',$sl='',$vl='',$login='',$logout='',$workhours_perday=array(),$cur_date='',$lateutrle='',$tschedrle='',$has_last_log=false,$has_after_suspension=false, $isFirstSched = false){
        $twork_lec = $twork_lab = $twork_admin = $twork_rle = 0;
        $tsched   = round(abs(strtotime($stime) - strtotime($etime)) / 60,2);
        $tsched   = date('H:i', mktime(0,$tsched));
        $tsched   = $this->attcompute->exp_time($tsched);
        if($type == 'LEC')       $twork_lec =  $tsched;
        elseif($type == 'LAB')   $twork_lab = $tsched;
        elseif($type == "ADMIN") $twork_admin = $tsched;
        else                     $twork_rle = $tsched;

        $is_holiday_halfday = $this->attcompute->isHolidayNew($empid, $date,$deptid, "", "on");
        list($fromtime, $totime) = $this->extensions->getHolidayHalfdayTime($date);
        $holidayInfo = $this->attcompute->holidayInfo($date);
        if($is_holiday_halfday && ($fromtime && $totime) ){
            $is_half_holiday = true;
            if($holidayInfo["holiday_type"] == 5){
                $isAffected = $this->attcompute->affectedBySuspension(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
                
                if($isAffected){
                    $tschedlec = $tschedlab = $tschedadmin = $tschedrle = "";
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
                    }else{
                        $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
                    }

                    $display_hol_remarks = true;
                }else{
                    $is_half_holiday = false;
                    if($holidayInfo["holiday_type"] == 5) $rate = 100;
                    else $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");

                    if(!$login && !$logout) $rate = 0;
                }

                $tsched = $tsched * $rate / 100;
            }else{
                $is_suspension = false;
                $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
                $tsched = $tsched * $rate / 100;
            }
        }else{
            // $tschedlec = $tschedlab = $tschedadmin = $tschedrle = "";
            $rate = 100;
            $is_half_holiday = true;
            if(isset($holidayInfo["holiday_type"]) && $holidayInfo["holiday_type"] == 5){
                $is_half_holiday = true;
                $rate = 50;
            }else{
                if(isset($holidayInfo["holiday_type"])) $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
                $is_half_holiday = false;
            }

            $tsched = $tsched * $rate / 100;
        }

        /*special condition of TMS*/
        $leave_project = 0;
        if($sl){
            $leave_project = $this->SLComputation(isset($workhours_perday[$cur_date]) ? $workhours_perday[$cur_date] : array(), $tsched);
        }else if($vl){
            if($this->extensions->isNursingDepartment($empid) > 0 && !$this->extensions->isNursingExcluded($empid)) $leave_project = $this->SLComputation(isset($workhours_perday[$cur_date]) ? $workhours_perday[$cur_date] : array(), $tsched);
            else $leave_project = $tsched;
        }

        ///< perdepartment work hours
        if($type == 'LEC'){
            if(!isset($workhours_arr[$aimsdept]['LEC']['work_hours'])) $workhours_arr[$aimsdept]['LEC']['work_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['LEC']['late_hours'])) $workhours_arr[$aimsdept]['LEC']['late_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['LEC']['deduc_hours'])) $workhours_arr[$aimsdept]['LEC']['deduc_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['LEC']['leave_project'])) $workhours_arr[$aimsdept]['LEC']['leave_project'] = 0;
            $workhours_arr[$aimsdept]['LEC']['work_hours'] += $tsched;
            $workhours_arr[$aimsdept]['LEC']['late_hours'] += $this->attcompute->exp_time($lateutlec);
            $workhours_arr[$aimsdept]['LEC']['deduc_hours'] += $this->attcompute->exp_time($tschedlec);
            $workhours_arr[$aimsdept]['LEC']['leave_project'] += $leave_project;
        }elseif($type == 'LAB'){
            if(!isset($workhours_arr[$aimsdept]['LAB']['work_hours'])) $workhours_arr[$aimsdept]['LAB']['work_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['LAB']['late_hours'])) $workhours_arr[$aimsdept]['LAB']['late_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['LAB']['deduc_hours'])) $workhours_arr[$aimsdept]['LAB']['deduc_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['LAB']['leave_project'])) $workhours_arr[$aimsdept]['LAB']['leave_project'] = 0;
            $workhours_arr[$aimsdept]['LAB']['work_hours'] += $tsched;
            $workhours_arr[$aimsdept]['LAB']['late_hours'] += $this->attcompute->exp_time($lateutlab);
            $workhours_arr[$aimsdept]['LAB']['deduc_hours'] += $this->attcompute->exp_time($tschedlab);
            $workhours_arr[$aimsdept]['LAB']['leave_project'] += $leave_project;
        }elseif($type == 'RLE'){
            if(!isset($workhours_arr[$aimsdept]['RLE']['work_hours'])) $workhours_arr[$aimsdept]['RLE']['work_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['RLE']['late_hours'])) $workhours_arr[$aimsdept]['RLE']['late_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['RLE']['deduc_hours'])) $workhours_arr[$aimsdept]['RLE']['deduc_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['RLE']['leave_project'])) $workhours_arr[$aimsdept]['RLE']['leave_project'] = 0;
            $workhours_arr[$aimsdept]['RLE']['work_hours'] += $tsched;
            $workhours_arr[$aimsdept]['RLE']['late_hours'] += $this->attcompute->exp_time($lateutrle);
            $workhours_arr[$aimsdept]['RLE']['deduc_hours'] += $this->attcompute->exp_time($tschedrle);
            $workhours_arr[$aimsdept]['RLE']['leave_project'] += $leave_project;
        }
        else{
            if(!isset($workhours_arr[$aimsdept]['ADMIN']['work_hours'])) $workhours_arr[$aimsdept]['ADMIN']['work_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['ADMIN']['late_hours'])) $workhours_arr[$aimsdept]['ADMIN']['late_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['ADMIN']['deduc_hours'])) $workhours_arr[$aimsdept]['ADMIN']['deduc_hours'] = 0;
            if(!isset($workhours_arr[$aimsdept]['ADMIN']['leave_project'])) $workhours_arr[$aimsdept]['ADMIN']['leave_project'] = 0;
            $workhours_arr[$aimsdept]['ADMIN']['work_hours'] += $tsched;
            $workhours_arr[$aimsdept]['ADMIN']['late_hours'] += $this->attcompute->exp_time($lateutadmin);
            $workhours_arr[$aimsdept]['ADMIN']['deduc_hours'] += $this->attcompute->exp_time($tschedadmin);
            $workhours_arr[$aimsdept]['ADMIN']['leave_project'] += $leave_project;
        }

        return array($twork_lec,$twork_lab,$twork_admin,$workhours_arr,$twork_rle);
    }

    function getWorkhoursPerdayArr($stime='',$etime='',$type='',$aimsdept='',$cur_date='',$workhours_perday=array(),$lateutlec='',$tschedlec='',$lateutlab='',$tschedlab='',$lateutadmin='',$tschedadmin,$holiday='',$empid='',$date='',$deptid='',$sl='',$vl='',$login='',$logout='',$lateutrle='',$tschedrle='',$has_last_log=false,$has_after_suspension=false, $isFirstSched = false){
        $suspension_less = 0;
        $twork_lec = $twork_lab = $twork_admin = $twork_rle = 0;
        $tsched   = round(abs(strtotime($stime) - strtotime($etime)) / 60,2);
        $tsched   = date('H:i', mktime(0,$tsched));
        $tsched   = $this->attcompute->exp_time($tsched);
        $is_suspension = true;
        $is_holiday_halfday = $this->attcompute->isHolidayNew($empid, $date,$deptid, "", "on");
        list($fromtime, $totime) = $this->extensions->getHolidayHalfdayTime($date);
        $holidayInfo = $this->attcompute->holidayInfo($date);
        if($is_holiday_halfday && ($fromtime && $totime) ){
            $is_half_holiday = true;
            if($holidayInfo["holiday_type"] == 5){
                $isAffected = $this->attcompute->affectedBySuspension(date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), date("H:i", strtotime($stime)), date("H:i", strtotime($etime)));
                
                if($isAffected){
                    $tschedlec = $tschedlab = $tschedadmin = $tschedrle = "";
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

                    $display_hol_remarks = true;
                }else{
                    $is_half_holiday = false;
                    if($holidayInfo["holiday_type"] == 5) $rate = 100;
                    else $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");

                    if(!$login && !$logout) $rate = 0;
                    else $suspension_less = $tsched;
                }

                $tsched = $tsched * $rate / 100;
            }else{
                $is_suspension = false;
                $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
                $tsched = $tsched * $rate / 100;
            }
        }else{
            // $tschedlec = $tschedlab = $tschedadmin = $tschedrle = "";
            $rate = 100;
            $is_half_holiday = true;
            if(isset($holidayInfo["holiday_type"]) && $holidayInfo["holiday_type"] == 5){
                $is_half_holiday = true;
                $rate = 50;
            }else{
                if(isset($holidayInfo["holiday_type"])) $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], "teaching");
                $is_suspension = false;
                $is_half_holiday = false;
            }

            $tsched = $tsched * $rate / 100;
        }


        /*special condition of TMS*/
       /* $leave_project = 0;
        if($sl){
            if(isset($workhours_perday[$cur_date])) $leave_project = $this->SLComputation($workhours_perday[$cur_date], $tsched);
        }else if($vl){
            if($this->extensions->isNursingDepartment($empid) > 0) $leave_project = $this->SLComputation($workhours_perday[$cur_date], $tsched);
            else $leave_project = 0;
        }*/

        ///< perdepartment work hours
        if($type == 'LEC'){
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LEC']['work_hours'])) $workhours_perday[$cur_date][$aimsdept]['LEC']['work_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LEC']['late_hours'])) $workhours_perday[$cur_date][$aimsdept]['LEC']['late_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LEC']['deduc_hours'])) $workhours_perday[$cur_date][$aimsdept]['LEC']['deduc_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LEC']['is_suspension'])) $workhours_perday[$cur_date][$aimsdept]['LEC']['is_suspension'] = $is_suspension;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LEC']['suspension_less'])) $workhours_perday[$cur_date][$aimsdept]['LEC']['suspension_less'] = $suspension_less;
            // if(!isset($workhours_perday[$cur_date][$aimsdept]['LEC']['leave_project'])) $workhours_perday[$cur_date][$aimsdept]['LEC']['leave_project'] = 0;
            $workhours_perday[$cur_date][$aimsdept]['LEC']['work_hours'] += $tsched;
            $workhours_perday[$cur_date][$aimsdept]['LEC']['late_hours'] += ($holiday) ? 0 : $this->attcompute->exp_time($lateutlec);
            $workhours_perday[$cur_date][$aimsdept]['LEC']['deduc_hours'] += ($holiday) ? 0 : $this->attcompute->exp_time($tschedlec);
            $workhours_perday[$cur_date][$aimsdept]['LEC']['aimsdept'] = $aimsdept;
            // $workhours_perday[$cur_date][$aimsdept]['LEC']['leave_project'] += $leave_project;
        }elseif($type == 'LAB'){
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LAB']['work_hours'])) $workhours_perday[$cur_date][$aimsdept]['LAB']['work_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LAB']['late_hours'])) $workhours_perday[$cur_date][$aimsdept]['LAB']['late_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LAB']['deduc_hours'])) $workhours_perday[$cur_date][$aimsdept]['LAB']['deduc_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LAB']['is_suspension'])) $workhours_perday[$cur_date][$aimsdept]['LAB']['is_suspension'] = $is_suspension;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['LAB']['suspension_less'])) $workhours_perday[$cur_date][$aimsdept]['LAB']['suspension_less'] = $suspension_less;
            // if(!isset($workhours_perday[$cur_date][$aimsdept]['LAB']['leave_project'])) $workhours_perday[$cur_date][$aimsdept]['LAB']['leave_project'] = 0;
            $workhours_perday[$cur_date][$aimsdept]['LAB']['work_hours'] += $tsched;
            $workhours_perday[$cur_date][$aimsdept]['LAB']['late_hours'] += ($holiday) ? 0 : $this->attcompute->exp_time($lateutlab);
            $workhours_perday[$cur_date][$aimsdept]['LAB']['deduc_hours'] += ($holiday) ? 0 : $this->attcompute->exp_time($tschedlab);
            $workhours_perday[$cur_date][$aimsdept]['LAB']['aimsdept'] = $aimsdept;
            // $workhours_perday[$cur_date][$aimsdept]['LAB']['leave_project'] += $leave_project;
        }elseif($type == 'RLE'){
            if(!isset($workhours_perday[$cur_date][$aimsdept]['RLE']['work_hours'])) $workhours_perday[$cur_date][$aimsdept]['RLE']['work_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['RLE']['late_hours'])) $workhours_perday[$cur_date][$aimsdept]['RLE']['late_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['RLE']['deduc_hours'])) $workhours_perday[$cur_date][$aimsdept]['RLE']['deduc_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['RLE']['is_suspension'])) $workhours_perday[$cur_date][$aimsdept]['RLE']['is_suspension'] = $is_suspension;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['RLE']['suspension_less'])) $workhours_perday[$cur_date][$aimsdept]['RLE']['suspension_less'] = $suspension_less;
            // if(!isset($workhours_perday[$cur_date][$aimsdept]['RLE']['leave_project'])) $workhours_perday[$cur_date][$aimsdept]['RLE']['leave_project'] = 0;
            $workhours_perday[$cur_date][$aimsdept]['RLE']['work_hours'] += $tsched;
            $workhours_perday[$cur_date][$aimsdept]['RLE']['late_hours'] += ($holiday) ? 0 : $this->attcompute->exp_time($lateutrle);
            $workhours_perday[$cur_date][$aimsdept]['RLE']['deduc_hours'] += ($holiday) ? 0 : $this->attcompute->exp_time($tschedrle);
            $workhours_perday[$cur_date][$aimsdept]['RLE']['aimsdept'] = $aimsdept;
            // $workhours_perday[$cur_date][$aimsdept]['ADMIN']['leave_project'] += $leave_project;
        }else{
            if(!isset($workhours_perday[$cur_date][$aimsdept]['ADMIN']['work_hours'])) $workhours_perday[$cur_date][$aimsdept]['ADMIN']['work_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['ADMIN']['late_hours'])) $workhours_perday[$cur_date][$aimsdept]['ADMIN']['late_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['ADMIN']['deduc_hours'])) $workhours_perday[$cur_date][$aimsdept]['ADMIN']['deduc_hours'] = 0;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['ADMIN']['is_suspension'])) $workhours_perday[$cur_date][$aimsdept]['ADMIN']['is_suspension'] = $is_suspension;
            if(!isset($workhours_perday[$cur_date][$aimsdept]['ADMIN']['suspension_less'])) $workhours_perday[$cur_date][$aimsdept]['ADMIN']['suspension_less'] = $suspension_less;
            // if(!isset($workhours_perday[$cur_date][$aimsdept]['ADMIN']['leave_project'])) $workhours_perday[$cur_date][$aimsdept]['ADMIN']['leave_project'] = 0;
            $workhours_perday[$cur_date][$aimsdept]['ADMIN']['work_hours'] += $tsched;
            $workhours_perday[$cur_date][$aimsdept]['ADMIN']['late_hours'] += ($holiday) ? 0 : $this->attcompute->exp_time($lateutadmin);
            $workhours_perday[$cur_date][$aimsdept]['ADMIN']['deduc_hours'] += ($holiday) ? 0 : $this->attcompute->exp_time($tschedadmin);
            $workhours_perday[$cur_date][$aimsdept]['ADMIN']['aimsdept'] = $aimsdept;
            // $workhours_perday[$cur_date][$aimsdept]['ADMIN']['leave_project'] += $leave_project;
        }

        return $workhours_perday;
    }

    function getBEDAttendanceSetup(){
        $setup = array();
        $setup['firsthalf_start']    = '05:00';
        $setup['halfday_cutoff']     = '12:00';
        $setup['sechalf_end']        = '21:00';
        return $setup;
    }


    function getBEDPerdayAbsent($setup=array(),$persched_info=array()){
        $rowcount_half = 0;
        $isfirsthalf_absent = $issechalf_absent = $iswholeday_absent = true;

        if( $this->attcompute->exp_time($persched_info['sched_start']) >= $this->attcompute->exp_time($setup['firsthalf_start']) && $this->attcompute->exp_time($persched_info['sched_end']) <= $this->attcompute->exp_time($setup['halfday_cutoff']) ){
            $rowcount_half++;

            if($persched_info['isAbsent'] == 0) $isfirsthalf_absent = $iswholeday_absent = false;

        }elseif( $this->attcompute->exp_time($persched_info['sched_start']) > $this->attcompute->exp_time($setup['halfday_cutoff']) && $this->attcompute->exp_time($persched_info['sched_end']) <= $this->attcompute->exp_time($setup['sechalf_end']) ){
            if($persched_info['isAbsent'] == 0) $issechalf_absent = $iswholeday_absent = false;

        }elseif( $this->attcompute->exp_time($persched_info['sched_start']) >= $this->attcompute->exp_time($setup['firsthalf_start']) && $this->attcompute->exp_time($persched_info['sched_end']) <= $this->attcompute->exp_time($setup['sechalf_end']) ){
            if($persched_info['isAbsent'] == 0) $iswholeday_absent = $isfirsthalf_absent = $issechalf_absent = false;
        }
        return array($rowcount_half,$isfirsthalf_absent,$issechalf_absent,$iswholeday_absent);
    }

    /**
     * @revised Angelica
     * Computation for employee attendance summary with given date range. (Non-teaching)
     *
     * @param String $from_date
     * @param String $to_date
     * @param String $empid
     *
     * @return array
     */
    public function computeEmployeeAttendanceSummaryNonTeaching($from_date='',$to_date='',$empid='',$toCheckPrevAtt=false){
        $edata = 'NEW';
        $date_list = array();
        $deptid = $this->employee->getindividualdept($empid);
        $not_included_ol = array("ABSENT", "EL", "VL", "SL", "CORRECTION");
        $date_tmp = "";

        $fixedday = $this->attcompute->isFixedDay($empid);

        $x = $totr = $totrest = $tothol = $tlec = $tutlec = $absent = $tabsent = $tabsentperday = $tel = $tvl = $tsl = $tol = $tdlec = $tdlab = $tholiday = $pending = $tempOverload = $overload = $tOverload = $lastDayOfWeek = $service_credit = $cs_app = $tsc = 0; 

        $tlec = $tlab = $tadmin = $tabsent = $tabsentperday = $tel = $tvl = $tsl = $tol = $tdlec = $tdlab = $tdadmin = 0; 
        $tempabsent = $lateutlec= $lateutlab = $twork_lec = $twork_lab = $twork_admin = 0;
        $workhours_arr = array();
        $workhours_perday = array();
        $workhours_perdept = array();

        $workdays = 0;
        $seq_new = 0;
        $tlec = 0 ;
        $tempabsent = "";
        $hasLog = $isSuspension = false;

        $ot_list = array();
        $ot_save_list = array();
        
        $used_time = array();
        $qdate = $this->attcompute->displayDateRange($from_date, $to_date);

        $isCreditedHoliday = false;
        $firstDate = true;
        $is_flexi = false;
        $day_absent = 0;
        ///< based from source -> individual attendance_report
        foreach ($qdate as $rdate) {
            $has_last_log = true;
            $holiday_type = '';

            // Holiday
            $isSuspension = false;
            $holiday = $this->attcompute->isHolidayNew($empid,$rdate->dte,$deptid ); 

            $holidayInfo = $this->attcompute->holidayInfo($rdate->dte);
            if($holiday)
            {
                $holiday_type = $holidayInfo["type"];
                if($holidayInfo["code"]=="SUS") $isSuspension = true;
                //if($holidayInfo["withPay"]=='NO') $holiday = '';
                // if($holidayInfo["holiday_rate"] <= 0) $holiday = ''; 
            }

            $is_holiday_valid = $this->attendance->getTotalHoliday($rdate->dte, $rdate->dte, $empid);
            if(!$is_holiday_valid){
                $holidayInfo = array();
                $holiday = "";
            }

            $dispLogDate = date("d-M (l)",strtotime($rdate->dte));
            $sched = $this->attcompute->displaySched($empid,$rdate->dte);
            $countrow = $sched->num_rows();
                
            $isValidSchedule = true;

            if($countrow > 0){
                if($sched->row(0)->starttime == '00:00:00' && $sched->row(0)->endtime == '00:00:00') $isValidSchedule = false;
            }


            $hasSched = false;

             if(!$toCheckPrevAtt){
                ///< for validation of absent for 1st day in range. this will check for previous day attendance
                if($firstDate && $holiday){
                    $hasLog = $this->attendance->checkPreviousSchedAttendanceNonTeaching($rdate->dte,$empid);
                    $firstDate = false;
                }
            }

            if($countrow > 0 && $isValidSchedule){
                $hasSched = $firstsched = true;

                ///< for validation of holiday (will only be credited if not absent during last schedule)
                $hasLogprev = $hasLog;
                $hasLog = false;

                
                if($hasLogprev || $isSuspension)    $isCreditedHoliday = true;
                else                                $isCreditedHoliday = false;

                $tempsched = "";
                $seq=0;

                $isFirstSched = true;
                $ot_list = array();
                $q_sched = $sched;
            // echo "<pre>"; print_r($sched->result()); die;
                foreach($sched->result() as $rsched){

                    if($tempsched == $dispLogDate)  $dispLogDate = "";
                    $seq += 1;
                    $stime  = $rsched->starttime;
                    $etime  = $rsched->endtime; 
                    $tstart = $rsched->tardy_start; 
                    $absent_start = $rsched->absent_start;
                    $earlyd = $rsched->early_dismissal;
                    $type = $rsched->type;
                    $aimsdept = $rsched->aimsdept;
                    $flexible = $rsched->flexible;
                    $is_flexi = ($flexible == "YES") ? true : false;
                    
                    // logtime
                    list($login,$logout,$q)           = $this->attcompute->displayLogTime($empid,$rdate->dte,$stime,$etime,$edata,$seq,$absent_start,$earlyd,$used_time);
                    
                        // Overtime
                    list($otreg,$otrest,$othol) = $this->attcompute->displayOt($empid,$rdate->dte,true);

                    if($isFirstSched){
                        $ot_list_tmp = $this->attcompute->getOvertime($empid,$rdate->dte,true,$holiday_type);
                        $ot_list = $this->attcompute->constructOTlist($ot_list,$ot_list_tmp);

                        $ot_save_list = $this->attcompute->insertOTListToArray($ot_save_list, $ot_list);
                    }

                    // Leave
                    list($el,$vl,$sl,$ol,$oltype,$ob)  = $this->attcompute->displayLeave($empid,$rdate->dte,'',$stime,$etime,$seq);
                    if($ol == "DIRECT"){
                        $is_wfh = $this->attcompute->isWfhOB($empid,$rdate->dte);
                        if($is_wfh->num_rows() == 1){
                            $ob_id = $is_wfh->row()->aid;
                            $hastime = $this->attcompute->hasWFHTimeRecord($ob_id,$rdate->dte);
                            if($hastime->num_rows() == 0) $ol = $oltype = $ob = "";
                        }
                    }

                    //Service Credit 
                    $service_credit = $this->attcompute->displayServiceCredit($empid,$stime,$etime,$rdate->dte);

                    // Change Schedule
                    $cs_app = $this->attcompute->displayChangeSchedApp($empid,$rdate->dte);
                    
                    
                    // Leave Pending
                    $pending = $this->attcompute->displayPendingApp($empid,$rdate->dte);

                        // Absent
                    $absent = $this->attcompute->displayAbsent($stime,$etime,$login,$logout,$empid,$rdate->dte,$earlyd);
                    if($oltype == "ABSENT") $absent = $absent;
                    else if($holiday && $isCreditedHoliday) $absent = "";

                    if ($vl > 0 || $el > 0 || $sl > 0 || ($ol && $ol != "CORRECTION") || $service_credit > 0){
                        $absent = "";
                    }
                    
                    // Late / Undertime
                    $lateutlec = $this->attcompute->displayLateUTNT($stime,$etime,$login,$logout,$absent,'',$tstart);
                    $utlec  = $this->attcompute->computeUndertimeNT($stime,$etime,$login,$logout,$absent,'',$tstart);
                    if($el || $vl || $sl || $service_credit || ($holiday && $isCreditedHoliday)) $lateutlec = $utlec = "";
                    
                                        
                    if($isFirstSched){
                        if(!$login || $absent) $login = $this->attcompute->getLogin($empid, $edata, $rdate->dte);
                        if(!$logout || $absent) $logout = $this->attcompute->getLogout($empid, $edata, $rdate->dte);

                        if($login && $logout){
                            $to_time = strtotime($login);
                            $from_time = strtotime($logout);
                            $tot_min = round(abs($to_time - $from_time) / 60,2);
                            if($tot_min > 5){
                                $lateutlec = $this->attcompute->displayLateUTNT($stime, $etime, $login, $logout, "", "", $tstart);
                                $utlec = $this->attcompute->computeUndertimeNT($stime,$etime,$login,$logout,"","","");
                            }else{
                                $absent = "4:00";
                                $lateutlec = $utlec = "";
                            }

                            // if($absent) $lateutlec = $absent;
                            if($utlec || $lateutlec) $log_remarks = $absent = "";
                            $hasLog = TRUE;
                        }else{
                                foreach($sched->result() as $rsched){
                                if(isset($sched_new[1]->starttime)) $stime  = $rsched->starttime;
                                if(isset($sched_new[1]->endtime)) $etime  = $rsched->endtime; 
                                if(isset($sched_new[1]->tardy_start)) $tstart = $rsched->tardy_start; 
                                if(isset($sched_new[1]->absent_start)) $absent_start = $rsched->absent_start;
                                if(isset($sched_new[1]->early_dismissal)) $earlyd = $rsched->early_dismissal;
                                $seq_new += 1;
                                list($login_new,$logout_new,$q_new,$haslog_forremarks_new)           = $this->attcompute->displayLogTime($empid,$rdate->dte,$stime,$etime,$edata,$seq_new,$absent_start,$earlyd);
                                if($login_new || $logout_new){
                                    // $lateutlec = $absent;
                                    // $lateutlab = $absent;
                                }
                                }
                                // $absent = "";
                        }
                    }else{
                        
                        if(!$login || $absent) $login = $this->attcompute->getLogin($empid, $edata, $rdate->dte);
                        if(!$logout || $absent) $logout = $this->attcompute->getLogout($empid, $edata, $rdate->dte);

                        if($el == FALSE && $vl == FALSE && $sl == FALSE  && $service_credit == FALSE){
                            if($login){
                                // $utlec = $absent;
                                // $utlab = $absent;
                                // $absent = "";
                            }
                            if($login && $logout){
                                $to_time = strtotime($login);
                                $from_time = strtotime($logout);
                                $tot_min = round(abs($to_time - $from_time) / 60,2);
                                if($tot_min > 5){
                                    $lateutlec = $this->attcompute->displayLateUTNT($stime, $etime, $login, $logout, "", "", $tstart);
                                    $utlec = $this->attcompute->computeUndertimeNT($stime,$etime,$login,$logout,"","","");
                                }else{
                                    $absent = "4:00";
                                    $lateutlec = $utlec = "";
                                }

                                // if($absent) $utlec = $absent;
                                if($utlec || $lateutlec) $log_remarks = $absent = "";
                            }
                        }
                    }

                    if($isFirstSched){
                        if($lateutlec){
                            $is_holiday_halfday = $this->attcompute->isHolidayNew($empid, $rdate->dte,$deptid, "on");
                            if($is_holiday_halfday){
                                list($fromtime, $totime) = $this->extensions->getHolidayHalfdayTime($rdate->dte, "first");
                            } 
                            if($is_holiday_halfday && ($fromtime && $totime) ){
                                $is_half_holiday = true;
                                $half_holiday = $this->attcompute->holidayHalfdayComputation(date("H:i", strtotime($login)), date("H:i", strtotime($logout)), date("H:i", strtotime($fromtime)), date("H:i", strtotime($totime)), $isFirstSched);
                                if($half_holiday > 0){
                                    $lateutlec = $this->attcompute->sec_to_hm(abs($half_holiday));
                                }else{
                                    $lateutlec = "";
                                }
                            }
                        }
                    }else{
                        $is_holiday_halfday = $this->attcompute->isHolidayNew($empid, $rdate->dte,$deptid, "on");
                        if($is_holiday_halfday){
                            list($fromtime, $totime) = $this->extensions->getHolidayHalfdayTime($rdate->dte, "second");
                        } 
                        if($is_holiday_halfday && ($fromtime && $totime) ){
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
                    if($el || $vl || $sl  || $service_credit || ($holiday && $isCreditedHoliday)) $lateutlec = $utlec = "";

                    $is_trelated = $this->employee->isTeachingRelated($empid);
                    // Late / UT
                    if($is_trelated){
                        list($lateutlec,$lateutlab,$lateutadmin,$tschedlec,$tschedlab,$tschedadmin) = $this->attcompute->displayLateUT($stime,$etime,$tstart,$login,$logout,$type,$absent);
                        if($el || $vl || $sl  || ($holiday && $isCreditedHoliday)){
                                $lateutlec = $lateutlab = $lateutadmin = $tschedlec = $tschedlab = $tschedadmin = "";
                        }
                        if($tlec){
                            $secs  = strtotime($lateutlec)-strtotime("00:00:00");
                            if($secs>0) $tlec = date("H:i",strtotime($tlec)+$secs);
                        }else
                            $tlec    = $lateutlec;

                        $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutlec) - strtotime("00:00:00")) : $lateutlec;
                            
                        if($tlab){
                            $secs  = strtotime($lateutlab)-strtotime("00:00:00");
                            if($secs>0) $tlab = date("H:i",strtotime($tlab)+$secs);
                        }else
                            $tlab    = $lateutlab;

                        $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutlab) - strtotime("00:00:00")) : $lateutlab;

                        if($tadmin){
                            $secs  = strtotime($lateutadmin)-strtotime("00:00:00");
                            if($secs>0) $tadmin = date("H:i",strtotime($tadmin)+$secs);
                        }else
                            $tadmin    = $lateutadmin;

                        $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? date("H:i",strtotime($date_list[$rdate->dte]["late"]) + strtotime($lateutadmin) - strtotime("00:00:00")) : $lateutadmin;

                        // Deductions
                        if($tschedlec)      $tdlec += $this->attcompute->exp_time($tschedlec);
                        $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->attcompute->exp_time($tschedlec)) : $this->attcompute->exp_time($tschedlec);

                        if($tschedlab)      $tdlab += $this->attcompute->exp_time($tschedlab);
                        $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->attcompute->exp_time($tschedlab)) : $this->attcompute->exp_time($tschedlab);

                        if($tschedadmin)    $tdadmin += $this->attcompute->exp_time($tschedadmin);
                        $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? ($date_list[$rdate->dte]["absent"] + $this->attcompute->exp_time($tschedadmin)) : $this->attcompute->exp_time($tschedadmin);

                        if(!$holiday && !$isCreditedHoliday){
                        list($work_lec,$work_lab,$work_admin,$workhours_arr) = $this->getWorkhoursPerdeptArr($stime,$etime,$type,$aimsdept,$workhours_arr,$lateutlec,$tschedlec,$lateutlab,$tschedlab,$lateutadmin,$tschedadmin,$empid,$rdate->dte,$deptid,$sl,$login,$logout,$workhours_perday,$rdate->dte,"","",$has_last_log, false, $isFirstSched);
                            $twork_lec += $work_lec;
                            $twork_lab += $work_lab;
                            $twork_admin += $work_admin;
                        }
                        $workhours_perday = $this->getWorkhoursPerdayArr($stime,$etime,$type,$aimsdept,$rdate->dte,$workhours_perday,$lateutlec,$tschedlec,$lateutlab,$tschedlab,$lateutadmin,$tschedadmin,$holiday,$empid,$rdate->dte,$deptid,$sl,$vl,$login,$logout,"","",$has_last_log, false, $isFirstSched);
                    }
                    $absent = $this->attcompute->exp_time($absent);
                    if($absent >= 14400 && $countrow==2) $absent = 14400;
                    elseif($absent >= 14400 && $countrow==1) $absent = 28800;
                    $absent   = ($absent ? $this->attcompute->sec_to_hm($absent) : "");

                    $tempsched = $dispLogDate;
                    
                    /*
                     * ----------------Total---------------------------------------------
                     */ 
                    $hasOL = $ol ? ($ol != 'CORRECTION' ? true : false) : false; 
                    $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"])) ? $date_list[$rdate->dte]["absent"] : "";
                    // Absent
                    // $absent = $is_flexi ? "" : $absent;
                    if($absent){
                        if(!$fixedday && $hasOL)   {}
                        else{
                            $tabsent += $this->attcompute->exp_time($absent);
                            $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"]) && $date_list[$rdate->dte]["absent"]) ? $date_list[$rdate->dte]["absent"] + $this->attcompute->exp_time($absent) : $this->attcompute->exp_time($absent);
                        }
                    }else{
                        $hasLog = true;
                    }

                    $hasLog = $hasLog ? $hasLog : ($hasOL ? true : false); 
                    
                    // Late / UT
                    $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"])) ? $date_list[$rdate->dte]["late"] : "";
                    if($lateutlec){
                        $tlec += $this->attcompute->exp_time($lateutlec);
                        $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"]) && $date_list[$rdate->dte]["late"]) ? $date_list[$rdate->dte]["late"] + $this->attcompute->exp_time($lateutlec) : $this->attcompute->exp_time($lateutlec);
                    }

                    $date_list[$rdate->dte]["undertime"] = (isset($date_list[$rdate->dte]["undertime"])) ? $date_list[$rdate->dte]["undertime"] : "";
                    if($utlec){
                        $tutlec += $this->attcompute->exp_time($utlec);
                        $date_list[$rdate->dte]["undertime"] = (isset($date_list[$rdate->dte]["undertime"]) && $date_list[$rdate->dte]["undertime"]) ? $date_list[$rdate->dte]["undertime"] + $this->attcompute->exp_time($utlec) : $this->attcompute->exp_time($utlec);
                    }
                    
                    // Leave
                    if($dispLogDate)
                    {
                        $tel      += $el;
                        $tvl      += ($vl + $el);
                        $tsl      += $sl;
                        $tol      += (!in_array($ol, $not_included_ol) && $ol >= 1) ? (($date_tmp != $rdate->dte) ? 1 : -0.5) : 0;
                        $date_tmp  = $rdate->dte;
                    }

                    if($fixedday){
                        if($hasSched) $workdays+=0.5;
                    }else{
                        if($hasSched && ($absent=='' || $hasOL || $holiday)) $workdays+=0.5;
                    }
                    
                    $firstsched = false;
                    $isFirstSched = false;
                    if($absent) $day_absent += 0.5;
                    $has_last_log = (!$login && !$logout) ? false : true;
                }   // end foreach
                
                /* Overtime */
                $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"])) ? $date_list[$rdate->dte]["overtime"] : "";

                if($otreg){

                    $totr += $this->attcompute->exp_time($otreg);
                    $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"]) && $date_list[$rdate->dte]["overtime"]) ? $date_list[$rdate->dte]["overtime"] + $this->attcompute->exp_time($otreg) : $this->attcompute->exp_time($otreg);

                    list($ot_amount, $ot_mode) = $this->attcompute->getOvertimeAmountDetailed($empid, $ot_list, $date_list[$rdate->dte]["overtime"]);
                    $date_list[$rdate->dte]["ot_type"] = $ot_mode;
                    $date_list[$rdate->dte]["ot_amount"] = $ot_amount;
                }

                if($otrest){
                    $totrest += $this->attcompute->exp_time($otrest);
                    $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"]) && $date_list[$rdate->dte]["overtime"]) ? $date_list[$rdate->dte]["overtime"] + $this->attcompute->exp_time($otrest) : $this->attcompute->exp_time($otrest);

                    list($ot_amount, $ot_mode) = $this->attcompute->getOvertimeAmountDetailed($empid, $ot_list, $date_list[$rdate->dte]["overtime"]);
                    $date_list[$rdate->dte]["ot_type"] = $ot_mode;
                    $date_list[$rdate->dte]["ot_amount"] = $ot_amount;
                }

                if($othol){
                    $tothol += $this->attcompute->exp_time($othol);
                    $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"]) && $date_list[$rdate->dte]["overtime"]) ? $date_list[$rdate->dte]["overtime"] + $this->attcompute->exp_time($othol) : $this->attcompute->exp_time($othol);

                    list($ot_amount, $ot_mode) = $this->attcompute->getOvertimeAmountDetailed($empid, $ot_list, $date_list[$rdate->dte]["overtime"]);
                    $date_list[$rdate->dte]["ot_type"] = $ot_mode;
                    $date_list[$rdate->dte]["ot_amount"] = $ot_amount;
                }

               
            }else{  ////< to compute for overtime if employee have no schedule for this day ----------------------------------------------------------------------
                $date_list[$rdate->dte]["absent"] = (isset($date_list[$rdate->dte]["absent"])) ? $date_list[$rdate->dte]["absent"] : "";
                $date_list[$rdate->dte]["late"] = (isset($date_list[$rdate->dte]["late"])) ? $date_list[$rdate->dte]["late"] : "";
                $date_list[$rdate->dte]["undertime"] = (isset($date_list[$rdate->dte]["undertime"])) ? $date_list[$rdate->dte]["undertime"] : "";
                $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"])) ? $date_list[$rdate->dte]["overtime"] : "";
                list($otreg,$otrest,$othol) = $this->attcompute->displayOt($empid,$rdate->dte,false);
                /* Overtime */
                // total regular
                if($otreg){
                    $totr += $this->attcompute->exp_time($otreg);
                    $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"]) && $date_list[$rdate->dte]["overtime"]) ? $date_list[$rdate->dte]["overtime"] + $this->attcompute->exp_time($otreg) : $this->attcompute->exp_time($otreg);
                    $ot_save_list[count($ot_save_list) - 1]["ot_hours"] = $this->attcompute->sec_to_hm($date_list[$rdate->dte]["overtime"]);

                    list($ot_amount, $ot_mode) = $this->attcompute->getOvertimeAmountDetailed($empid, $ot_list, $date_list[$rdate->dte]["overtime"]);
                    $date_list[$rdate->dte]["ot_type"] = $ot_mode;
                    $date_list[$rdate->dte]["ot_amount"] = $ot_amount;
                }
                // total saturday
                if($otrest){
                    $totrest += $this->attcompute->exp_time($otrest);
                    $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"]) && $date_list[$rdate->dte]["overtime"]) ? $date_list[$rdate->dte]["overtime"] + $this->attcompute->exp_time($otrest) : $this->attcompute->exp_time($otrest);

                    $ot_list_tmp = $this->attcompute->getOvertime($empid,$rdate->dte,false,$holiday_type);
                    $ot_list = $this->attcompute->constructOTlist($ot_list,$ot_list_tmp);
                    $ot_save_list = $this->attcompute->insertOTListToArray($ot_save_list, $ot_list);

                    list($ot_amount, $ot_mode) = $this->attcompute->getOvertimeAmountDetailed($empid, $ot_list, $date_list[$rdate->dte]["overtime"]);
                    $date_list[$rdate->dte]["ot_type"] = $ot_mode;
                    $date_list[$rdate->dte]["ot_amount"] = $ot_amount;
                }
                
                // total holiday
                if($othol){
                    $tothol += $this->attcompute->exp_time($othol);
                    $date_list[$rdate->dte]["overtime"] = (isset($date_list[$rdate->dte]["overtime"]) && $date_list[$rdate->dte]["overtime"]) ? $date_list[$rdate->dte]["overtime"] + $this->attcompute->exp_time($othol) : $this->attcompute->exp_time($othol);

                    $ot_list_tmp = $this->attcompute->getOvertime($empid,$rdate->dte,false,$holiday_type);
                    $ot_list = $this->attcompute->constructOTlist($ot_list,$ot_list_tmp);
                    $ot_save_list = $this->attcompute->insertOTListToArray($ot_save_list, $ot_list);

                    list($ot_amount, $ot_mode) = $this->attcompute->getOvertimeAmountDetailed($empid, $ot_list, $date_list[$rdate->dte]["overtime"]);
                    $date_list[$rdate->dte]["ot_type"] = $ot_mode;
                    $date_list[$rdate->dte]["ot_amount"] = $ot_amount;
                }
                
                $ot_list_tmp = $this->attcompute->getOvertime($empid,$rdate->dte,false,$holiday_type);
                $ot_list = $this->attcompute->constructOTlist($ot_list,$ot_list_tmp);

            } // end if  
            if($holiday && $isCreditedHoliday) $tholiday++;

            $firstDate = true;
            $ot_list = array();
        }

        $tabsent = ($tabsent ? $this->attcompute->sec_to_hm($tabsent) : "");

        $tlec   = ($tlec ? $this->attcompute->sec_to_hm($tlec) : "");       
        $tutlec   = ($tutlec ? $this->attcompute->sec_to_hm($tutlec) : "");       
        $totr   = ($totr ? $this->attcompute->sec_to_hm($totr) : "");
        $totrest = ($totrest ? $this->attcompute->sec_to_hm($totrest) : ""); 
        $tothol = ($tothol ? $this->attcompute->sec_to_hm($tothol) : "");
        $date_list["workhours_perday"] = $workhours_perday;

        return array($tabsent,$tlec,$tlab,$tadmin,$tdlec,$tdlab,$tdadmin,$tutlec,$totr,$totrest,$tothol,$tel,$tvl,$tsl,$tsl,$tol,$tholiday,$holiday,$hasSched,$hasLog,$workdays, $ot_save_list, $date_list, $tsc, $workhours_arr,$twork_lec,$twork_lab,$twork_admin, $day_absent, $is_flexi); ///< $hasSched is applicable only for checking of attendance good for 1 day

    } ///< end of functio computeEmployeeAttendanceSummaryNonTeaching


    /**
     * @Angelica
     * Computation for employee attendance before the given date. This is applicable for validation of credited holidays. (Teaching)
     *
     * @param String $date
     * @param String $empid
     *
     * @return string
     */
    public function checkPreviousSchedAttendanceTeaching($date='',$empid=''){
        
        /*$continueloop = true;
        $loopcount = 0;
        while($continueloop){
            if($loopcount==15) return '';

            $date = new DateTime($date);
            $date->modify('-1 day');
            $date = $date->format('Y-m-d');
            list($tlec,$tlab,$tadmin,$tabsent,$tdaily_absent,$tel,$tvl,$tsl,$tol,$tdlec,$tdlab,$tdadmin,$holiday,$hasSched,$hasLog,$twork_lec,$twork_lab,$twork_admin,$workhours_arr,$date_list) = $this->computeEmployeeAttendanceSummaryTeaching($date,$date,$empid,true);

            if(!$hasSched && !$holiday){
                $continueloop = false;
                $date_list = true;
            }else if($hasSched && !$holiday){
                $continueloop = false;
            }else if(!$hasSched && $holiday){
                $continueloop = false;
                $date_list = true;
            }

            $loopcount++;
        }
        // if($tabsent) return $tabsent;
        // else          return $tdadmin;

        return $date_list;*/
        return true;

    }   


    /**
     * @Angelica
     * Computation for employee attendance before the given date. This is applicable for validation of credited holidays. (Non-teaching)
     *
     * @param String $date
     * @param String $empid
     *
     * @return string
     */
    public function checkPreviousSchedAttendanceNonTeaching($date='',$empid=''){
        
        /*$continueloop = true;
        $loopcount = 0;
        while($continueloop){
            if($loopcount==15) return '';
            
            $date = new DateTime($date);
            $date->modify('-1 day');
            $date = $date->format('Y-m-d');
            list($tabsent,$tlec,$tutlec,$totr,$totrest,$tothol,$tel,$tvl,$tsl,$tsl,$tol,$tholiday,$holiday,$hasSched,$hasLog) = $this->computeEmployeeAttendanceSummaryNonTeaching($date,$date,$empid,true);

            if(!$hasSched && !$holiday){
                $continueloop = false;
                $hasLog = true;
            }else if($hasSched && !$holiday){
                $continueloop = false;
            }else if(!$hasSched && $holiday){
                $continueloop = false;
                $hasLog = true;
            }
            $loopcount++;
        }

        return $hasLog;*/
        return true;
    }       


    
    public function dateRange($first, $last, $step = '+1 day', $format = 'Y-m-d' ) {
        $dates = array();
        $current = strtotime($first);
        $last = strtotime($last);

        while( $current <= $last ) {    
            $dates[] = date($format, $current);
            $current = strtotime($step, $current);
        }
        return $dates;
    }

    public function isValidEmployeesHoliday($empid, $date){
        $is_valid_holiday = false;

        return $is_valid_holiday;
    }

    public function getAttendanceCutOffReportDataForTeaching($cutoff, $tnt, $employeeid, $category, $campusid, $is_emp_side=false, $deptid="",$office="",$status="",$company_campus=""){
        $this->load->model("attcompute");
        $data = array();
        list($from_date, $to_date) = explode(",", $cutoff);
        $q_emplist = $this->emp_confirmed($from_date, $to_date, $tnt, $employeeid, $campusid, $deptid, "", "", $company_campus, $office, $status);
        $q_emplist = ($is_emp_side) ? $this->emp_confirmedperdept($from_date, $to_date, $tnt, $employeeid, $deptid, $campusid, $office, $status, $company_campus) : $q_emplist;
        foreach ($q_emplist as $row) {
            if($category == "campus")  $sort_key = $row["qCampusId"];
            else if($category == "department")  $sort_key = $row["qDeptId"];
            else $sort_key = "name";

            $deptid = $this->employee->getindividualdept($row["qEmpId"]);
            $sort_key = ($is_emp_side) ? $deptid : $sort_key;

            $totUndertime = $this->attcompute->exp_time($row['utadmin']) + $this->attcompute->exp_time($row['utlec']) + $this->attcompute->exp_time($row['utlab']);
            $totLate = $this->attcompute->exp_time($row['latelec']) + $this->attcompute->exp_time($row['latelab']) + $this->attcompute->exp_time($row['lateadmin']);
            $totDeduction = $this->attcompute->exp_time($row['deducperday']);
            $totDeduction = $this->attcompute->sec_to_hm($totDeduction);

            $data[$sort_key][$row["qEmpId"]] = array(
                "name" => $row["qFullname"],
                "ot-regular" => $row["otreg"], 
                "ot-rest-day" => $row["otrest"], 
                "ot-holiday" => $row["othol"], 
                "late" => ($totLate) ? $this->attcompute->sec_to_hm($totLate) : "",
                "undertime" => ($totUndertime) ? $this->attcompute->sec_to_hm($totUndertime) : "",
                "deduclec" => $row["deduclec"],
                "deduclab" => $row["deduclab"],
                "deducadmin" => $row["deducadmin"],
                "vl" => $row["vleave"],
                "sl" => $row["sleave"],
                "scl" => $row["scleave"],
                "ol" => ($row["oleave"] + $row["eleave"]),
                "l_nopay" => $row["l_nopay"],
                "absent" => ($totDeduction) ? ($this->convertTimeToNumber($totDeduction) / 8) : "",
                "no-days" => (!$row["fixedday"]) ? ($this->convertTimeToNumber($row["workhours_admin"]) / 8) : "",
                "holiday" => $this->getTotalHoliday($from_date, $to_date, $row["qEmpId"]),
                "campusid" => $row["qCampusId"],
                "deptid" => $row["qDeptId"],
                "status" => $row["status"],
            );
        }

        return $data;
    }

    public function getAttendanceCutOffReportDataForNonTeaching($cutoff, $tnt, $employeeid, $category, $campusid, $is_emp_side=false, $deptid=""){
        $this->load->model("attcompute");
        $data = array();
        list($from_date, $to_date) = explode(",", $cutoff);
        $q_emplist = $this->attendance->emp_confirmed_nt($from_date, $to_date, $tnt, $employeeid, $campus, $deptid, "", "", $company_campus, $office, $status);
        $q_emplist = ($is_emp_side) ? $this->emp_confirmed_ntperdept($from_date, $to_date, $tnt, $employeeid, $deptid, $campusid) : $q_emplist;
        foreach ($q_emplist as $row) {
            if($category == "campus")  $sort_key = $row["qCampusId"];
            else if($category == "department")  $sort_key = $row["qDeptId"];
            else $sort_key = "name";

            $deptid = $this->employee->getindividualdept($row["qEmpId"]);
            $sort_key = ($is_emp_side) ? $deptid : $sort_key;

            $data[$sort_key][$row["qEmpId"]] = array(
                "name" => $row["qFullname"],
                "ot-regular" => $row["otreg"], 
                "ot-rest-day" => $row["otrest"], 
                "ot-holiday" => $row["othol"],
                "late" => $row["lateut"],
                "undertime" => $row["ut"],
                "lateut" => $row["lateut"],
                "vl" => $row["vleave"],
                "sl" => $row["sleave"],
                "scl" => $row["scleave"],
                "l_nopay" => $row["l_nopay"],
                "ol" => ($row["oleave"] + $row["eleave"]),
                "absent" => ($row["absent"]) ? ($this->convertTimeToNumber($row["absent"]) / 8) : "",
                "no-days" => (!$row["fixedday"]) ? $row['workdays'] : "",
                "holiday" => $this->getTotalHoliday($from_date, $to_date, $row["qEmpId"]),
                "campusid" => $row["qCampusId"],
                "deptid" => $row["qDeptId"],
                "status" => $row["status"]
            );
        }
        ksort($data);
        $acad_data = $data['ACAD'];
        unset($data['ACAD']);
        $data['ACAD'] = $acad_data;
        return $data;
    }

    public function getTotalHoliday($date_from, $date_to, $employeeid){
        $count_holiday = 0;

        $status = "";
        $q_emp_data = $this->db->query("SELECT CONCAT(office, '~', employmentstat) AS status_included FROM employee WHERE employeeid='$employeeid';")->result();
        foreach ($q_emp_data as $row) $status = $row->status_included;

        $q_count_holiday = $this->db->query("SELECT COUNT(*) AS count_holiday
                                             FROM  code_holiday_calendar a
                                             INNER JOIN holiday_inclusions b ON b.holi_cal_id = a.holiday_id
                                             WHERE a.date_from BETWEEN '$date_from' AND '$date_to' AND a.date_to BETWEEN '$date_from' AND '$date_to' AND status_included LIKE '%$status%'")->result();
        foreach ($q_count_holiday as $row) $count_holiday = $row->count_holiday;
        
        return $count_holiday;
    }

    public function unconfirmedTeachingEmployeeAttendance($dfrom, $dto, $empid){
        $res = $this->db->query("DELETE FROM attendance_confirmed WHERE cutoffstart = '$dfrom' AND cutoffend = '$dto' AND employeeid = '$empid' ");
        return $res;
    }

    public function unconfirmedNonTeachingEmployeeAttendance($dfrom, $dto, $empid){
        $res = $this->db->query("DELETE FROM attendance_confirmed_nt WHERE cutoffstart = '$dfrom' AND cutoffend = '$dto' AND employeeid = '$empid' ");
        return $res;
    }

    public function checkWorkhoursExisting($base_id, $type, $aimsdept){
        $query = $this->db->query("SELECT * FROM workhours_perdept WHERE base_id = '$base_id' AND type = '$type' AND aimsdept = '$aimsdept' ");
        if($query->num_rows() > 0) return $query->row()->id;
        else return FALSE;
    }

    public function empCanConfirmAttendance($payroll_start='',$dateresigned=''){
        $canConfirm = true;
        $payroll_start = new DateTime($payroll_start);

        if($dateresigned != '0000-00-00' && $dateresigned != '1970-01-01' && $dateresigned != NULL){
            $dateresigned = new DateTime($dateresigned);

            if($dateresigned < $payroll_start) $canConfirm = false;
        }
        return $canConfirm;
    }

    public function removeLateUtAbsent($date_list,$isnodtr){
        $data = array();
        // if($isnodtr){
        //     foreach($date_list as $key => $value){
        //         $date_list[$key]['late'] = '';
        //         $date_list[$key]['absent'] = '';
        //         $date_list[$key]['undertime'] = '';
        //     }
        // }
        return $date_list;
    }

    public function checkIfHasPendingAttendance($sdate,$edate,$teachingtype,$payroll_start,$payroll_end){
        if($teachingtype == "teaching") $tbl = ' attendance_confirmed';
        else $tbl = ' attendance_confirmed_nt';
        return $this->db->query("SELECT * FROM $tbl WHERE cutoffstart = '$sdate' AND cutoffend = '$edate' AND payroll_cutoffstart = '$payroll_start' AND payroll_cutoffend = '$payroll_end' AND status = 'PENDING' ")->num_rows();   
    }

    public function getTotalAbsentPerday($schedule, $empid, $date){
        $stime = $etime = $type = $seq = $tardy_start = $absent_start = $earlydismissal = "";
        $login = $logout = $q = $haslog_forremarks = "";
        $sched_min = 0;
        $tot_sec = 0;
        $used_time = array();
        foreach($schedule as $rsched){
            // Convert times to timestamps
            $stime = strtotime($rsched->starttime);
            $etime = strtotime($rsched->endtime);

            // Calculate the difference in seconds
            $tot_sec += abs($etime - $stime); // Use abs() to ensure a positive difference

            // $type  = $rsched->leclab;
            // $seq += 1;
            // $tardy_start = $rsched->tardy_start;
            // $absent_start = $rsched->absent_start;
            // $earlydismissal = $rsched->early_dismissal;

            // // logtime
            // list($login,$logout,$q,$haslog_forremarks) = $this->attcompute->displayLogTime($empid,$date,$stime,$etime,"NEW",$seq,$absent_start,$earlydismissal,$used_time);
            // if($login=='0000-00-00 00:00:00') $login = '';
            // if($logout=='0000-00-00 00:00:00') $logout = '';

            // // Absent
            // $absent = $this->attcompute->displayAbsent($stime,$etime,$login,$logout,$empid,$date,$earlydismissal, $absent_start);
            
            // // Late / Undertime
            // list($lateutlec,$lateutlab,$lateutadmin,$tschedlec,$tschedlab,$tschedadmin) = $this->attcompute->displayLateUT($stime,$etime,$tardy_start,$login,$logout,$type,$absent);
            // $perday_absent += $this->attcompute->exp_time($tschedadmin);
            // $sched_min += round(abs($stime - $etime) ,2);
        }
        // $tot_sec = $sched_min * 60 * 60;
        return $tot_sec;
    }

    public function cutoffPresentDays($employeeid, $startdate, $enddate){
        $q_att = $this->db->query("SELECT day_present FROM attendance_confirmed WHERE employeeid = '$employeeid' AND payroll_cutoffstart = '$startdate' AND payroll_cutoffend = '$enddate'");
        if($q_att->num_rows() > 0){
            $present_days = $q_att->row()->day_present;
            $present_arr = explode(",", $present_days);
            return count($present_arr);
        }else{
            return false;
        }
    }

    public function employeeSubstituteDetails($id){
        return $this->db->query("SELECT * FROM substitute_request WHERE id = '$id' ");
    }

    public function employeeSubstituteDetailsPerDay($date, $employeeid){
        return $this->db->query("SELECT * FROM substitute_request WHERE employeeid = '$employeeid' AND '$date' BETWEEN dfrom AND dto ");
    }

    public function employeeMakeupClass($id){
        return $this->db->query("SELECT * FROM substitute_makeup WHERE base_id = '$id'");
    }

    public function batchScheduleDateActive($code){
        $q_sched = $this->db->query("SELECT * FROM code_type WHERE code = '$code'");
        if($q_sched->num_rows() > 0) return date("Y-m-d", strtotime($q_sched->row()->date_active));
        else return false;
    }

    public function getquartercutoff($dto, $dfrom){
        $q = $this->db->query("SELECT a.quarter FROM payroll_cutoff_config a INNER JOIN cutoff b ON a.baseid = b.ID WHERE b.CutoffFrom = '$dfrom' AND b.CutoffTo = '$dto'");
        if($q->num_rows() > 0) return $q->row()->quarter;
        else return FALSE;
    }

    public function allowedToConfirm($dfrom, $dto){
        return $this->db->query("SELECT * FROM cutoff WHERE CutoffFrom = '$dfrom' AND CutoffTo = '$dto' AND DATE_FORMAT(NOW(), '%Y-%c-%d') BETWEEN ConfirmFrom AND ConfirmTo AND DATE_FORMAT(NOW(), '%H:%i:%s') BETWEEN TimeFrom AND TimeTo;")->num_rows();
    }
    
    public function totalWorkhoursPerday($employeeid, $date, $teachingtype = "nonteaching"){
        $t_min = $sched_min = 0;
        $tap_count = 0;
        $timein = $timeout = $endtime = "";
        $sched_start = "";
        $q_timesheet = $this->db->query("SELECT * FROM timesheet WHERE userid = '$employeeid' AND DATE(timein) = '$date' GROUP BY timein, timeout ");
        if($q_timesheet->num_rows() > 0){
           
            foreach($q_timesheet->result() as $row){
                $timein = ($timein) ? (strtotime($timein) < strtotime($row->timein) ? $timein : $row->timein) : ($row->timein);
                $timeout = ($timeout) ? (strtotime($timeout) > strtotime($row->timeout) ? $timeout : $row->timeout) : ($row->timeout);
                
                $t_min = round(abs(strtotime($timeout) - strtotime($timein)) / 60);
                $tap_count++;
            }
            
        }

        if($teachingtype == "nonteaching"){
            $from_time = $t_vacant = $seq = 0;
            $sched = $this->attcompute->displaySched($employeeid,$date);
            $used_time = array();
            $sched_count = $sched->num_rows();
            if($sched->num_rows() > 0){
                foreach($sched->result() as $sched_row){
                    $starttime = $sched_row->starttime;
                    $endtime = $sched_row->endtime; 
                    $absent_start = $sched_row->absent_start;
                    $earlydismissal = $sched_row->early_dismissal;
                    
                    #nilagay ko to kasi kapag kapag sakto ang schedule sa timein kinukulang ng 1minute
                    list($login,$logout,$q,$haslog_forremarks,$used_time) = $this->attcompute->displayLogTime($employeeid,$date,$starttime,$endtime,"NEW",$seq,$absent_start,$earlydismissal,$used_time);
                    if($login){
                        if(date("H:i", strtotime($starttime)) == date("H:i", strtotime($login))){
                            // $t_min += 1;
                        }
                    }

                    if($from_time){
                        $seq += 1;
                        // logtime
                        list($login,$logout,$q,$haslog_forremarks,$used_time) = $this->attcompute->displayLogTime($employeeid,$date,$starttime,$endtime,"NEW",$seq,$absent_start,$earlydismissal,$used_time);

                        $stime = strtotime($from_time);
                        $etime = strtotime($sched_row->starttime);
                        if($haslog_forremarks) $t_vacant += round(abs($etime - $stime) / 60);
                    }else{
                        $sched_start = $sched_row->starttime;
                        $endtime = $sched_row->endtime; 
                    }

                    $from_time = $sched_row->endtime;

                    $sched_min += round(abs(strtotime($sched_row->endtime) - strtotime($sched_row->starttime)) / 60);
                }
            }

            /*remove excess hours*/
            if($sched->num_rows() > 0){
                $timeout = date("H:i:s", strtotime($timeout));
                if($timeout > $endtime){
                    $excess = round(abs(strtotime($timeout) - strtotime($endtime)) / 60);
                    $t_min -= $excess;
                }

                $timein = date("H:i:s", strtotime($timein));
                
                if($sched_start > $timein){
                    $excess = round(abs(strtotime($sched_start) - strtotime($timein)) / 60);
                    $t_min -= $excess;
                }
            }
            if($tap_count>1) $t_vacant = $sched_min - $t_min;
            else $t_vacant = 0;
            
            # OLD CONDITION : $t_vacant > 0
            if($t_vacant && $sched_count > 0){
                $t_min = $t_min - abs($t_vacant);
            }
            
        }
        if($t_min < 0) $t_min = 0;
        if($t_min > 60 && $teachingtype == "nonteaching" && $tap_count == 1 && $t_min > 480) $t_min -= 61;
        // var_dump("<pre>",$date,$t_min);
        return $this->time->minutesToHours(round($t_min));
    }

    public function employeeAbsentTardy($employeeid, $sdate, $edate){
        $lateut = $ut = $absent = "0.00";
        $q_att = $this->db->query("SELECT * FROM attendance_confirmed_nt WHERE employeeid = '$employeeid' AND payroll_cutoffstart = '$sdate' AND payroll_cutoffend= '$edate'");
        if($q_att->num_rows() > 0){
            if($q_att->row()->absent) $absent = $q_att->row()->absent;
            if($q_att->row()->lateut) $lateut = $q_att->row()->lateut;
            if($q_att->row()->ut) $ut = $q_att->row()->ut;
        }

        $lateut = ($this->attcompute->exp_time($lateut) + $this->attcompute->exp_time($ut));
        $lateut = $this->attcompute->sec_to_hm($lateut);

        return array($lateut, $absent);
    }

    public function isCutoffExists($cutofffrom, $cutoffto, $payrolldfrom, $payrolldto, $dkey){
        return $this->db->query("SELECT * FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.ID = b.baseid WHERE ((CutoffFrom = '$cutofffrom' OR CutoffTo = '$cutoffto' OR startdate = '$payrolldfrom' OR enddate = '$payrolldto') OR ('$cutofffrom' BETWEEN CutoffFrom AND CutoffTo OR '$cutoffto' BETWEEN CutoffFrom AND CutoffTo )) AND a.ID != '$dkey' ")->num_rows();
    }

    public function isCutoffExistsWithScheduleType($cutofffrom, $cutoffto, $payrolldfrom, $payrolldto, $dkey, $schedule_type){
        return $this->db->query("SELECT * FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.ID = b.baseid 
                                 WHERE ((CutoffFrom = '$cutofffrom' OR CutoffTo = '$cutoffto' OR startdate = '$payrolldfrom' OR enddate = '$payrolldto') OR ('$cutofffrom' BETWEEN CutoffFrom AND CutoffTo OR '$cutoffto' BETWEEN CutoffFrom AND CutoffTo )) AND a.ID != '$dkey' AND b.schedule = '$schedule_type'")->num_rows();
    }

    public function getEmployeeDetailedAttendance($cutoffstart,$cutoffend,$userid){
        return $this->db->query("SELECT * FROM employee_attendance_detailed WHERE sched_date BETWEEN '$cutoffstart' AND '$cutoffend' AND employeeid = '$userid'");
    }

    public function SLComputation($workhours_arr, $tsched){
         /*for sl computation*/
        $t_lec = $t_lab = 0;
        foreach($workhours_arr as $aimsdept_c => $row){
            foreach($row as $type_c => $workhours_c){
                if($type_c=="LEC") $t_lec += $workhours_c["work_hours"];
                elseif($type_c=="LAB") $t_lab += $workhours_c["work_hours"];
            }
        }

        if($t_lec < 17400){
            if(($t_lec + $tsched) > 17400){
                $tsched = ($t_lec + $tsched) - 17400;
                // $tsched -= $excess;
            }
        }else{
            $tsched = 0;
        }

        if($t_lec < 17400){
            if(($t_lab + $tsched) > 17400){
                $tsched = ($t_lab + $tsched) - 17400;
                // $tsched -= $excess;
            }
        }else{
            $tsched = 0;
        }

        return $tsched;
    }

    function hasLogtime($empid, $date){
        return $this->db->query("SELECT * FROM timesheet WHERE DATE(timein) = '$date' AND userid = '$empid'")->num_rows();
    }

    public function isAttendanceConfirmed($employeeid, $date){
        $tnt = $this->extensions->getEmployeeTeachingType($employeeid);
        $table = "attendance_confirmed";
        if($tnt == "nonteaching") $table = "attendance_confirmed_nt";
        return $this->db->query("SELECT * FROM $table a
                                INNER JOIN payroll_computed_table b ON a.employeeid = b.employeeid
                                WHERE a.employeeid = '$employeeid' AND '$date' BETWEEN a.cutoffstart AND a.cutoffend AND b.status = 'PROCESSED'");
    }

    public function timesheetExists($timeid){
        return $this->db->query("SELECT * FROM timesheet WHERE timeid = '$timeid' ");
    }

    public function getTapCampus($userid, $date){
        $query = $this->db->query("SELECT username FROM timesheet WHERE userid = '$userid' AND DATE(timein) = '$date'");
        if($query->num_rows() > 0) return $query->row()->username;
        else return false;
    }

    function saveConfirmationProgress($data){
        $teachingtype = $data['teachingtype'];
        $cutoff = $data['cutoff'];
        $checker = $this->db->query("SELECT * FROM confirm_attendance_progress WHERE teachingtype = '$teachingtype' AND cutoff = '$cutoff'");
        if($checker->num_rows() > 0){
            $this->db->query("DELETE FROM confirm_attendance_progress WHERE teachingtype = '$teachingtype' AND cutoff = '$cutoff'");
            $this->db->query("DELETE FROM confirming_attendance_result WHERE teachingtype = '$teachingtype' AND cutoff = '$cutoff'");
            $this->db->insert("confirm_attendance_progress", $data);
            return 'ongoing';
        }else{
            $this->db->insert("confirm_attendance_progress", $data);
            return 'success';
        }
    }

    function processingConfirmation($tnt, $dto, $dfrom){
        $this->load->model('utils');
        $cutoff = $dfrom.'~|~'.$dto;
        $success_count = $failed_count = $failed = $success = 0;
        $res = $deptid = $dateresigned2 = $hold_status = '';
        $usertype   = $this->session->userdata("usertype");
        $query = $this->db->query("SELECT current_count, total_count, employeelist FROM confirm_attendance_progress WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
        if($query->num_rows() > 0){
            $current_count =  $query->row(0)->current_count;
            $total_count =  $query->row(0)->total_count;
            $employeelist =  $query->row(0)->employeelist;
            $emplist = explode(',', $employeelist);
            if(count($emplist) > 0){
                $counter = 0;
                $newEmplist = '';
                foreach ($emplist as $uid) {
                    if($counter <= rand(1,5)){
                        list($dtr_start,$dtr_end,$payroll_start,$payroll_end,$payroll_quarter) = $this->payrolloptions->getDtrPayrollCutoffPair($dfrom,$dto);
                        $canConfirm = false;
                        $emp_data = $this->utils->getEmployeeInfo('teachingtype,deptid,dateresigned2',array('employeeid'=>$uid));
                        if($emp_data){
                          $deptid       = $emp_data[0]->deptid;
                          $dateresigned2 = $emp_data[0]->dateresigned2;
                          $canConfirm   = $this->attendance->empCanConfirmAttendance($payroll_start,$dateresigned2);
                        }

                        if($canConfirm){
                            if($tnt == 'teaching' || $tnt == 'trelated'){
                                $isBED = false;
                                $bed_depts = $this->extensions->getBEDDepartments();
                                if(in_array($deptid, $bed_depts)) $isBED = true;
                                $res = $this->attendance->saveEmployeeAttendanceSummaryTeaching($dfrom,$dto,$payroll_start,$payroll_end,$payroll_quarter,$uid, $isBED, $hold_status, $usertype);
                            }elseif($tnt == 'nonteaching'){
                                $res = $this->attendance->saveEmployeeAttendanceSummaryNonTeaching($dfrom,$dto,$payroll_start,$payroll_end,$payroll_quarter,$uid, $hold_status, $usertype);
                            }
                        }

                        if($canConfirm) $success_count++;
                        else $failed_count++;

                        $current_count++;
                        if($current_count >= $total_count){
                            $this->db->query("DELETE FROM confirm_attendance_progress WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
                            $this->db->query("DELETE FROM confirming_attendance_result WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
                        }
                        else{
                            $this->db->query("UPDATE confirm_attendance_progress SET current_count = '$current_count' WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
                        }
                        $counter++;
                    }else{
                        if($newEmplist) $newEmplist .= ','.$uid;
                        else $newEmplist .= $uid;
                    }
                }
            }

            if($newEmplist != ''){
                $this->db->query("UPDATE confirm_attendance_progress set employeelist = '$newEmplist' WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
            }

            $result_query = $this->db->query("SELECT * FROM confirming_attendance_result WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
            if($result_query->num_rows() > 0){
                $success =  $result_query->row(0)->success + $success_count;
                $failed =  $result_query->row(0)->failed + $failed_count;
                $this->db->query("UPDATE confirming_attendance_result SET success = '$success', failed = '$failed' WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
            }else{
                $success = $success_count;
                $failed = $failed_count;
                $this->db->query("INSERT INTO confirming_attendance_result(teachingtype, cutoff, success, failed) VALUES ('$tnt', '$cutoff', '$success_count', '$failed_count')");
            }

            $query = $this->db->query("SELECT CONCAT('[',current_count,'/',total_count,']') as progress, current_count, total_count FROM confirm_attendance_progress WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
            if($query->num_rows() > 0){
                $current_count =  $query->row(0)->current_count;
                $total_count =  $query->row(0)->total_count;
                if($current_count >= $total_count){
                    $this->db->query("DELETE FROM confirm_attendance_progress WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
                    return 0;
                }else{
                    return 'Processing Employees '.$query->row(0)->progress.'</br>'.'Success: '.$success.'</br>'.'Failed: '.$failed;
                }
            }
            else{
                $this->db->query("DELETE FROM confirming_attendance_result WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
                return 0;
            }

        }else{
            return 0;
        }
    }

    function finalizingAttendance($tnt, $pdfrom, $pdto, $cdfrom, $cdto, $quarter, $emp_details){
        $this->load->model('utils');
        $cutoff = $pdfrom.'~|~'.$pdto;
        $success_count = $failed_count = $failed = $success = 0;
        $res = $deptid = $dateresigned2 = $hold_status = '';
        $usertype   = $this->session->userdata("usertype");
        $query = $this->db->query("SELECT current_count, total_count, employeelist FROM confirm_attendance_progress WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
        if($query->num_rows() > 0){
            $current_count =  $query->row(0)->current_count;
            $total_count =  $query->row(0)->total_count;
            $employeelist =  $query->row(0)->employeelist;
            $emplist = explode(',', $employeelist);
            if(count($emplist) > 0){
                $counter = 0;
                $newEmplist = '';
                foreach ($emplist as $uid) {
                    if($counter <= rand(1,5)){
                        
                        if($tnt == "teaching" || $tnt == "trelated"){
                            $cquery = $this->db->query("SELECT * FROM attendance_confirmed WHERE payroll_cutoffstart='$pdfrom' AND payroll_cutoffend='$pdto' AND quarter='$quarter'");
                            if($cquery->num_rows() > 0){
                                if(!$emp_details[$uid]['isFinalPay']) $this->payroll->removeFinalBenefits($uid);
                                $ins    = $this->db->query("UPDATE attendance_confirmed SET forcutoff=1, payroll_cutoffstart='$pdfrom', payroll_cutoffend='$pdto', quarter='$quarter', status = 'PROCESSED', isFinal='{$emp_details[$uid]['isFinalPay']}', hold_status='{$emp_details[$uid]['isOnhold']}' WHERE cutoffstart='$cdfrom' AND cutoffend='$cdto' AND `status` != 'PENDING' AND employeeid = '$uid' ");
                                $this->db->query("DELETE FROM processed_employee WHERE cutoffstart = '$pdfrom' AND cutoffend = '$pdto' AND employeeid = '$uid' ");
                                $this->db->query("INSERT INTO processed_employee (employeeid,cutoffstart,cutoffend,status,remaining_cutoff) VALUES ('$uid', '$pdfrom', '$pdto', 'PROCESSED', '{$emp_details[$uid]['project_cutoff']}') ");

                                /*check if status is onhold*/
                                if($emp_details[$uid]['isOnhold']){
                                    $this->db->query("UPDATE attendance_confirmed SET forcutoff=1, payroll_cutoffstart='$pdfrom', payroll_cutoffend='$pdto', quarter='$quarter', hold_status='{$emp_details[$uid]['isOnhold']}', status = 'SUBMITTED' WHERE cutoffstart='$cdfrom' AND cutoffend='$cdto' AND `status` != 'PENDING' AND employeeid = '$uid' ");
                                    $this->db->query("DELETE FROM processed_employee WHERE cutoffstart = '$pdfrom' AND cutoffend = '$pdto' AND employeeid = '$uid' ");
                                    $this->db->query("DELETE FROM payroll_computed_table WHERE cutoffstart = '$pdfrom' AND cutoffend = '$pdto' AND employeeid = '$uid' AND status != 'PROCESSED' ");
                                }

                                $success = true;
                                /*end*/

                            }else{
                                $success = false;
                            }
                        }else{
                            $cquery = $this->db->query("SELECT * FROM attendance_confirmed_nt WHERE employeeid = '$uid' AND payroll_cutoffstart='$pdfrom' AND payroll_cutoffend='$pdto' AND quarter='$quarter'");
                            if($cquery->num_rows() > 0){
                                if(!$emp_details[$uid]['isFinalPay']) $this->payroll->removeFinalBenefits($uid);
                                $ins    = $this->db->query("UPDATE attendance_confirmed_nt SET forcutoff=1, payroll_cutoffstart='$pdfrom', payroll_cutoffend='$pdto', status = 'PROCESSED', quarter='$quarter', isFinal='{$emp_details[$uid]['isFinalPay']}', hold_status='{$emp_details[$uid]['isOnhold']}' WHERE cutoffstart='$cdfrom' AND cutoffend='$cdto' AND `status` != 'PENDING' AND employeeid = '$uid' "); 
                                $this->db->query("DELETE FROM processed_employee WHERE cutoffstart = '$cdfrom' AND cutoffend = '$cdto' AND employeeid = '$uid' ");
                                $this->db->query("INSERT INTO processed_employee (employeeid,cutoffstart,cutoffend,status,remaining_cutoff) VALUES ('$uid', '$pdfrom', '$pdto', 'PROCESSED', '{$emp_details[$uid]['project_cutoff']}') ");

                                /*check if status is onhold*/
                                if($emp_details[$uid]['isOnhold']){
                                    $this->db->query("UPDATE attendance_confirmed_nt SET forcutoff=1, payroll_cutoffstart='$pdfrom', payroll_cutoffend='$pdto', quarter='$quarter', hold_status='{$emp_details[$uid]['isOnhold']}', status = 'SUBMITTED' WHERE cutoffstart='$cdfrom' AND cutoffend='$cdto' AND `status` != 'PENDING' AND employeeid = '$uid' ");
                                    $this->db->query("DELETE FROM processed_employee WHERE cutoffstart = '$pdfrom' AND cutoffend = '$pdto' AND employeeid = '$uid' ");
                                    $this->db->query("DELETE FROM payroll_computed_table WHERE cutoffstart = '$pdfrom' AND cutoffend = '$pdto' AND employeeid = '$uid' AND status != 'PROCESSED' ");
                                }

                                $success = true;
                                /*end*/

                            }else{
                                $success = false;
                            }
                        }

                        if($success) $success_count++;
                        else $failed_count++;

                        $current_count++;
                        if($current_count >= $total_count){
                            $this->db->query("DELETE FROM confirm_attendance_progress WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
                            $this->db->query("DELETE FROM confirming_attendance_result WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
                        }
                        else{
                            $this->db->query("UPDATE confirm_attendance_progress SET current_count = '$current_count' WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
                        }
                        $counter++;
                    }else{
                        if($newEmplist) $newEmplist .= ','.$uid;
                        else $newEmplist .= $uid;
                    }
                }
            }

            if($newEmplist != ''){
                $this->db->query("UPDATE confirm_attendance_progress set employeelist = '$newEmplist' WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
            }

            $result_query = $this->db->query("SELECT * FROM confirming_attendance_result WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
            if($result_query->num_rows() > 0){
                $success =  $result_query->row(0)->success + $success_count;
                $failed =  $result_query->row(0)->failed + $failed_count;
                $this->db->query("UPDATE confirming_attendance_result SET success = '$success', failed = '$failed' WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
            }else{
                $success = $success_count;
                $failed = $failed_count;
                $this->db->query("INSERT INTO confirming_attendance_result(teachingtype, cutoff, success, failed) VALUES ('$tnt', '$cutoff', '$success_count', '$failed_count')");
            }

            $query = $this->db->query("SELECT CONCAT('[',current_count,'/',total_count,']') as progress, current_count, total_count FROM confirm_attendance_progress WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
            if($query->num_rows() > 0){
                $current_count =  $query->row(0)->current_count;
                $total_count =  $query->row(0)->total_count;
                if($current_count >= $total_count){
                    $this->db->query("DELETE FROM confirm_attendance_progress WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
                    return 0;
                }else{
                    return 'Processing Employees '.$query->row(0)->progress.'</br>'.'Success: '.$success.'</br>'.'Failed: '.$failed;
                }
            }
            else{
                $this->db->query("DELETE FROM confirming_attendance_result WHERE teachingtype = '$tnt' AND cutoff = '$cutoff'");
                return 0;
            }

        }else{
            return 0;
        }
    }

    public function employeeDtrReport($dfrom, $dto, $employeeid, $department='', $office='', $campus='', $status='', $teachingtype='', $company='', $terminal='', $building='', $floor='', $roomno=''){
        $wc = "";
        $dataArr = array();
        if($employeeid) $wc = " AND user_id = '$employeeid'";
        if($company && $company!="all") $wc .= " AND b.company_campus='$company'";
        if($office && $office!="all") $wc .= " AND b.office='$office'";
        if($status != "all"){
          if($status=="1"){
            $wc .= " AND b.isactive ='1'";
          }
          if($status=="0"){
            $wc .= " AND b.isactive ='0'";
          }
        }
        if($department) $wc .= " AND b.deptid='$department'";
        if($campus && $campus!="All") $wc .= " AND b.campusid='$campus'";
        if($teachingtype){
            if($teachingtype != "trelated") $wc .= " AND b.teachingtype = '$teachingtype' AND b.trelated != '1' ";
            else $wc .= " AND b.teachingtype='teaching' AND b.trelated = '1'";
        }

        $firstquery = $this->db->query("SELECT CONCAT(b.lname, ' ', b.fname) AS fullname,a.user_id,DATE(a.datecreated) AS datecreated FROM login_attempts_terminal a INNER JOIN employee b ON a.user_id = b.employeeid WHERE DATE(a.datecreated) BETWEEN '$dfrom' AND '$dto' GROUP BY a.user_id ORDER BY fullname, DATE(datecreated) DESC;");

        if($firstquery->num_rows() > 0){
            foreach($firstquery->result() as $row){
                $userid = $row->user_id;
                $datecreated = $row->datecreated;
                
                $secondquery = $this->db->query("SELECT
                                                  a.id,
                                                  CONCAT(b.lname, ' ', b.fname) AS fullname,
                                                  b.employeeid,
                                                  a.campus,
                                                  o.description AS office,
                                                  d.description AS department,
                                                  DATE(a.datecreated) AS datecreated,
                                                  MIN(TIME(a.datecreated)) AS stamp_in,
                                                  MAX(TIME(a.datecreated)) AS stamp_out
                                                FROM
                                                  login_attempts_terminal a
                                                  INNER JOIN employee b
                                                    ON a.user_id = b.employeeid
                                                  INNER JOIN code_office o
                                                    ON o.code=b.office
                                                  INNER JOIN code_department d
                                                    ON d.code=b.deptid
                                                WHERE user_id = '$userid'
                                                  AND DATE(datecreated) = '$datecreated'
                                                  AND b.isactive = '1'
                                                  AND b.campusid = 'TMS'
                                                HAVING COUNT(*) > 0 ORDER BY fullname,datecreated DESC;");
                if($secondquery->num_rows() > 0){
                    array_push($dataArr ,$secondquery->row(0));
                }
            }
        }
        // echo "<pre>";print_r($dataArr);die;
        return $dataArr;


        
        // return $this->db->query("SELECT id, fullname,b.employeeid, campus, a.stamp_in, a.stamp_out, DATE(datecreated) AS datecreated FROM login_attempts_terminal a INNER JOIN employee b ON a.user_id = b.employeeid WHERE DATE(datecreated) BETWEEN '$dfrom' AND '$dto' $wc ORDER BY fullname,datecreated DESC");
        // return $this->db->query("SELECT a.id, fullname, b.employeeid, a.campus,GROUP_CONCAT(DISTINCT o.description) AS office, GROUP_CONCAT(DISTINCT d.description) AS department, a.stamp_in, a.stamp_out, DATE(datecreated) AS datecreated FROM login_attempts_terminal a INNER JOIN employee b ON a.user_id = b.employeeid INNER JOIN code_office o ON FIND_IN_SET(o.code,b.office) INNER JOIN code_department d ON FIND_IN_SET(d.code,b.deptid) WHERE DATE(datecreated) BETWEEN '$dfrom' AND '$dto' $wc GROUP BY a.id, b.employeeid ORDER BY fullname,datecreated DESC");        
    }

    public function employeeDtrReportLogs($dfrom, $dto, $employeeid){
        $wc = " WHERE a.userid = '$employeeid'"; // IF TERMINAL a.user_id
        // TERMINAL BASE
        // return $this->db->query("SELECT id, CONCAT(lname,', ',fname,' ',mname) AS fullname, campus, stamp_in, stamp_out,datecreated FROM login_attempts_terminal a INNER JOIN employee b ON a.user_id = b.employeeid ".$wc." AND DATE(datecreated) BETWEEN '$dfrom' AND '$dto' ORDER BY fullname,datecreated DESC");
        // TIMESHEET BASE
        // var_dump("SELECT a.timeid,CONCAT(b.lname) AS fullname,b.campusid,a.timein AS stamp_in,a.timeout AS stamp_out,TIMESTAMP AS datecreated FROM timesheet a INNER JOIN employee b ON a.userid = b.employeeid ".$wc." AND DATE(datecreated) BETWEEN '$dfrom' AND '$dto' ORDER BY fullname,datecreated DESC");die;
        return $this->db->query("SELECT a.timeid,CONCAT(b.lname) AS fullname,b.campusid,a.timein AS stamp_in,a.timeout AS stamp_out,TIMESTAMP AS datecreated FROM timesheet a INNER JOIN employee b ON a.userid = b.employeeid ".$wc." AND DATE(a.timein) BETWEEN '$dfrom' AND '$dto' ORDER BY fullname,a.timestamp DESC");
    }

    public function getEmployeeID($employeeid = null,$campusid = null, $office = null, $teachingtype = null, $isactive = null, $deptid = null,$subType = null) { 
        $baseQuery = "SELECT employeeid, CONCAT(e.`fname`,' ',e.`mname`,' ',e.`lname`) AS fullname FROM employee e";
        
        $conditions = [];

        if ($campusid && $campusid != 'All') $conditions[] = "e.campusid = '$campusid'";
        if ($office) $conditions[] = "e.office = '$office'";
        if ($teachingtype) $conditions[] = "e.teachingtype = '$teachingtype'";
        if ($isactive) $conditions[] = "e.isactive = '$isactive'";
        if ($deptid) $conditions[] = "e.deptid = '$deptid'";
        if ($employeeid) $conditions[] = "e.employeeid = '$employeeid'";
    
        //Dugtong yung mga condition sa pinaka query
        $query = $baseQuery;
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        return $this->db->query($query);
    }

    public function employeeDtrReportByTimeSheet($employeeid = null, $dateFrom = null, $dateTo = null, $campusid = null, $office = null, $teachingtype = null, $isactive = null, $deptid = null ,$subType = null) { 
        $baseQuery = "SELECT DISTINCT ts.userid,CONCAT(e.`fname`,' ',e.`mname`,' ',e.`lname`) AS fullname FROM timesheet ts 
                      LEFT JOIN employee e ON e.employeeid = ts.userid";
        
        $conditions = [];
        
        if ($employeeid && $employeeid != 'all') $conditions[] = "ts.userid = '$employeeid'";
        if ($campusid && $campusid != 'All') $conditions[] = "e.campusid = '$campusid'";
        if ($office) $conditions[] = "e.office = '$office'";
        if ($teachingtype) $conditions[] = "e.teachingtype = '$teachingtype'";
        if ($subType) $conditions[] = "e.sub_type = '$subType'";
        if ($isactive) $conditions[] = "e.isactive = '$isactive'";
        if ($deptid) $conditions[] = "e.deptid = '$deptid'";
        
        //Ibat ibang scenario sa pag filter ng date
        if ($dateFrom && $dateTo) {
            $conditions[] = "ts.timein BETWEEN '$dateFrom' AND '$dateTo 23:59:59'";
        } elseif ($dateFrom) {
            $conditions[] = "ts.timein >= '$dateFrom'";
        } elseif ($dateTo) {
            $conditions[] = "ts.timein <= '$dateTo 23:59:59'";
        }
    
        //Dugtong yung mga condition sa pinaka query
        $query = $baseQuery;
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        $query .= " ORDER BY ts.timestamp";

        return $this->db->query($query);
    }

    public function employeeAttendanceLogs($month, $year, $employeeid){
        // BASE ON 'login_attempts_terminal'
        $wc = " WHERE user_id = '$employeeid'";
        $wc .= ($month == "" ? "" : " AND MONTH(datecreated) = '$month'");
        $wc .= ($year == "" ? "" : " AND YEAR(datecreated) = '$year'");
        return $this->db->query("SELECT id, CONCAT(lname,', ',fname,' ',mname) AS fullname, campus, stamp_in, stamp_out,datecreated FROM login_attempts_terminal a INNER JOIN employee b ON a.user_id = b.employeeid ".$wc." ORDER BY datecreated DESC");
        
        // BASE ON 'timesheet'
        // $wc = " WHERE userid = '$employeeid'";
        // $wc .= ($month == "" ? "" : " AND MONTH(timestamp) = '$month'");
        // $wc .= ($year == "" ? "" : " AND YEAR(timestamp) = '$year'");
        // return $this->db->query("SELECT a.timeid,CONCAT(b.lname) AS fullname,b.campusid,a.timein AS stamp_in,a.timeout AS stamp_out,TIMESTAMP AS datecreated FROM timesheet a INNER JOIN employee b ON a.userid = b.employeeid ".$wc." ORDER BY a.timestamp DESC");
    }

    public function isFlexiNoHours($empid){
        return $this->db->query("SELECT * FROM code_schedule a INNER JOIN employee b ON a.`schedid` = b.empshift WHERE flexible = 'YES' AND hours = 0 AND employeeid = '$empid'")->num_rows();
    }

    # VISITOR
        function addVisitor($data=array()){
            if(!$data) return 0;
            $rfid_number_id = $data['rfid'];
            $getQuery = $this->db->query("SELECT rfid_number FROM visitor_rfid WHERE id='{$rfid_number_id}'");
            $rfid_num = $getQuery->row(0)->rfid_number;
            $visitorName = $data['name'];
            $updatedBy = $this->session->userdata("username");
            $dateUpdated = date('Y-m-d');
            $assign = $data['name'] == '' ? '0' : '1';


            
            $updateQuery = $this->db->query("UPDATE visitor_rfid SET assigned='{$assign}',name='{$visitorName}', updated_by='{$updatedBy}',date_updated='{$dateUpdated}' WHERE id='{$rfid_number_id}'");
            
            // $fields = array(
            //     'name' => $data['name'],
            //     'rfid' => $rfid_num,
            //     'visitor_pass_number' => $data['rfid']
            // );
            // $this->db->set($fields)
            // ->insert('visitor');
            return $updateQuery->affected_rows();
        }  
        function addVisitorPass($data=array()){
            if(!$data) return 0;
            $addedBy = $this->session->userdata("username");
            $datecreated = date('Y-m-d');

            $fields = array(
                'description' => $data['description'],
                'rfid_number' => $data['rfid'],
                'added_by' => $addedBy,
                'date_created' => $datecreated
            );
            $this->db->set($fields)
            ->insert('visitor_rfid');
            return $this->db->affected_rows();
        }   
        function updateVisitorPass($data=array()){
            if(!$data) return 0;
            $addedBy = $this->session->userdata("username");
            $datecreated = date('Y-m-d');
            $visitor_id = $data['id'];

            $fields = array(
                'name' => $data['name'],
                'description' => $data['description'],
                'rfid_number' => $data['rfid'],
                'updated_by' => $addedBy,
                'date_updated' => $datecreated
            );
            // echo "<pre>";print_r($data);die;
            $this->db->where('id', $visitor_id);
            $this->db->update('visitor_rfid', $fields);
            
            return $this->db->affected_rows();
        }  
        function getLastDesc(){
        /* this function returns the incremented description of the visitor's pass*/
            $query = $this->db->query("SELECT description FROM visitor_rfid ORDER BY id DESC LIMIT 1");
            $result = "Visitor Pass #1";
            if($query->num_rows() > 0){
                $lastDesc = $query->row(0)->description;
                $descNum = explode(' ', $lastDesc);
                $getDescNum =  intval(substr($descNum[2], 1)) + 1;
                $result = "Visitor Pass #".$getDescNum;

            }

            return $result;

        }     

        function getPassDetails($id){
            return $query = $this->db->query("SELECT * FROM visitor_rfid WHERE id='{$id}'")->row_array();

        }


    // 21-11-2024 added
    
    function getSubjTimeConfig($minutes, $year){
        $tardy = $absent = $early = 0;
        $query = $this->db->query("SELECT * FROM earlydismissal WHERE $minutes BETWEEN rangefrom AND  rangeto AND `year` = '$year' ORDER BY sequence LIMIT 1");
        if($query->num_rows() > 0){
            $tardy = $this->time->hoursToMinutes($query->row()->tardy);
            $absent = $this->time->hoursToMinutes($query->row()->absent);
            $early = $this->time->hoursToMinutes($query->row()->early);
        }
        return array($tardy, $absent, $early);
    }
    #END OF VISITOR

    /**
     * Validates whether the employee's AM schedule is marked as absent for a given date.
     *
     * @param int $employeeid The ID of the employee.
     * @param string $date The date to check (format: YYYY-MM-DD).
     * @return string Returns 'absent' if the employee is marked absent, 'not_absent' otherwise, or an empty string if no record is found.
     */
    public function validateAmScheduleAbsence($employeeid, $date)
    {
        $query = $this->db->query("
            SELECT absent 
            FROM employee_attendance_nonteaching 
            WHERE employeeid = '{$employeeid}' 
            AND DATE = '{$date}' 
            LIMIT 1
        ");

        if ($query->num_rows() > 0) {
            return $query->row()->absent ? 'absent' : 'not_absent';
        }

        return '';
    }


}
// EOF...