<?php 
/**
 * @author Justin
 * @copyright 2016
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Employeemod extends CI_Model {
    
    /*
     * Load Query
     */
    function displayleavetype($user = ""){
        $user = $user ? $user : $this->session->userdata("username");
        $query = $this->db->query("SELECT a.*, b.balance, b.credit, b.avail, b.leavetype, c.description AS leavedesc, b.dfrom, b.dto
                                    FROM employee_leave_credit b
                                    INNER JOIN code_request_form a ON a.code_request = b.leavetype
                                    INNER JOIN online_application_code c ON a.base_id = c.id
                                    WHERE b.employeeid='$user' AND is_leave = '1' AND (CURRENT_DATE BETWEEN b.dfrom and b.dto OR (b.dfrom = '0000-00-00' AND b.dto = '0000-00-00'))
                                    GROUP BY b.leavetype");
        return $query;
    }
    function managesicktype($types)
    {
        $sel = "";
        $read="";
        $opt="<option value=''>--Select Sick Type--</option>";
        $query = $this->db->query("SELECT * FROM code_sicknesstype ORDER BY description" );
        foreach ($query->result() as $row) {
            if ($types == $row->code)
                $sel = "selected";
            else $sel = ""; 
                $opt .="<option  $read value='$row->code' $sel>$row->description</option>";
        }
        return $opt;
    }
    function displayleavehistory($stat = ""){
        $user = $this->session->userdata("username");
        if($stat)   $wC = " AND status='$stat'";
        else        $wC = "";
        $query = $this->db->query("SELECT * FROM leave_app
                                    WHERE employeeid='$user' AND other != 'DA' $wC");
        return $query;
    }
    function displaybushistory($stat=""){
        $user = $this->session->userdata("username");
        $wC = "";
        if($stat)   $wC = " AND status='$stat'";
        $query = $this->db->query("SELECT * FROM leave_app WHERE employeeid='$user' AND other='DA' $wC");
        return $query;
    }
    function displayseminarhistory($category="",$dfrom="",$dto="",$stat=""){
        $user = $this->session->userdata("username");
        $wC   = "";
        if($category){   $wC = " AND a.status='$category'";     }
        if($dfrom && $dto) $wC .= " AND DATE(a.timestamp) BETWEEN '$dfrom' AND '$dto'";
        
        $query = $this->db->query("SELECT a.*, b.timestamp as dateattached, b.status as attstat FROM seminar_app a LEFT JOIN seminar_app_attach b ON a.id = b.id WHERE a.employeeid='$user' $wC");
        return $query;
    }
    function displayschedrequesthistory($category=""){
        $wC   = "";
        $user = $this->session->userdata("username");
        // $deptcode = $this->employee->getHeadDeptCode($this->session->userdata('username'));
        if($category)   $wC .= " AND status='$category'";
        // if($category)   $wC .= " AND b.employeeid='$user'";
        
        $query = $this->db->query("SELECT *, a.status, a.id as baseid, a.isread, (SELECT 1 FROM change_sched_app_emplist_items WHERE a.id = base_id AND status = 'APPROVED' LIMIT 1) as hasApproved FROM change_sched_app a INNER JOIN change_sched_app_emplist b ON a.id = b.base_id INNER JOIN employee c ON b.employeeid = c.employeeid WHERE a.id <> '' AND b.employeeid = '$user' $wC GROUP BY b.id");
        return $query;
    }
	function getChangeSchedRequest($status = null, $datefrom = null,$dateto = null){
		$wC = "";
		$user_id = $this->session->userdata("username");

        // if($status)   $wC .= " AND a.status='$status'"; else $wC .= " AND a.status='PENDING'";
        if($status)   $wC .= " AND a.status='$status'";

		if (!empty($datefrom) && !empty($dateto)) $wC .= " AND DATE(a.`timestamp`) BETWEEN '$datefrom' AND '$dateto'";
        $query = $this->db->query("
									SELECT *, b.id, a.id as baseid, a.status, csi.id as csi_id, csi.isread FROM change_sched_app a 
									INNER JOIN change_sched_app_emplist b ON a.id = b.base_id 
									INNER JOIN employee c ON b.employeeid = c.employeeid 
									LEFT JOIN change_sched_app_emplist_items csi ON b.employeeid = csi.employeeid
									WHERE a.id <> '' $wC AND csi.approver_id = '$user_id' GROUP BY b.id
								 ");
        return $query;
    }
    function displayotrequest($dfrom="",$dto=""){
        $user = $this->session->userdata("username");
        $query = $this->db->query("SELECT * FROM overtime_app WHERE employeeid='$user'");
        return $query;
    }
    function loadleavestatus($category="",$dfrom="",$dto="",$dept="",$cnoti=""){
        $user = $this->session->userdata("username");
        $bfp = $this->employee->getBudgetFinPres($user);
        
        if($category)   $wC = " AND a.status='$category'";
        else            $wC = "";

        if($dept == "HR")   $tbl = "leave_app_hrd";
        else                $tbl = "leave_app_dhead";
        
        if(($this->employee->campus_principal($user)) === true) $tbl = "leave_app_principal"; // for principal campus notification
        if($this->employee->getClusterHead($user))      $tbl = "leave_app_chead";
        if($this->employee->getUnivPhysician($user))    $tbl = "leave_app_uphy";
        if($bfp) $tbl = $bfp;

        if($cnoti){
			if($dept == "HR")
			{
                $query = $this->db->query("SELECT a.id,a.aid,a.employeeid,a.head,a.type,a.other,a.othertype,a.paid,a.datefrom,a.dateto,a.timefrom,a.timeto,a.otimefrom,a.otimeto,
                b.htimefrom, b.htimeto ,a.nodays,a.reason,a.status,a.dateapproved,a.timestamp,
                CONCAT(c.lname,', ',c.fname,' ',c.mname) AS fullname , c.deptid
                FROM leave_app_dhead a
                LEFT JOIN leave_app_hrd b ON a.aid = b.aid 
                LEFT JOIN employee c ON a.employeeid = c.employeeid
                WHERE FIND_IN_SET('$user',a.head) AND a.other <> 'DA' AND a.status = 'PENDING'
                UNION
                SELECT d.id,d.aid,d.employeeid,d.head,d.type,d.other,d.othertype,d.paid,d.datefrom,d.dateto,d.timefrom,d.timeto,d.otimefrom,d.otimeto, d.htimefrom, d.htimeto ,d.nodays,d.reason,d.status,d.dateapproved,d.timestamp,CONCAT(c.lname,', ',c.fname,' ',c.mname) AS fullname , c.deptid
                FROM leave_app_hrd d
                LEFT JOIN employee c ON d.employeeid = c.employeeid
                WHERE FIND_IN_SET('$user',d.head) AND d.other <> 'DA' AND d.status = 'PENDING'");
            }
            else
            {
                $query = $this->db->query("SELECT a.*, CONCAT(b.lname,', ',b.fname,' ',b.mname) as fullname, b.deptid FROM $tbl a
                LEFT JOIN employee b ON a.employeeid = b.employeeid 
                WHERE FIND_IN_SET('$user',a.head) AND a.status='PENDING' AND a.other<>'DA'");
            }
        }else{
            //echo "<pre>hellot";die;
			if($category == "CANCELED")
			{
				$query = $this->db->query("SELECT a.*,b.aid,CONCAT(c.lname,', ',c.fname,' ',c.mname) AS fullname , c.deptid
				FROM leave_app a
				LEFT JOIN leave_request b ON a.id = b.aid
				LEFT JOIN employee c ON a.employeeid = c.employeeid
				WHERE a.status = '$category' AND DATE(a.timestamp) BETWEEN '$dfrom' AND '$dto'");
			}
			else if($dept == "HR")
			{
				if($wC != "") {  $wC = " AND a.status='$category'"; $wC2 = " AND d.status='$category'";}
				else            {$wC = ""; $wC2 = "";}
				$query = $this->db->query("SELECT a.id,a.aid,a.employeeid,a.head,a.type,a.other,a.othertype,a.paid,a.datefrom,a.dateto,a.timefrom,a.timeto,a.otimefrom,a.otimeto,
				b.htimefrom, b.htimeto ,a.nodays,a.reason,a.status,a.dateapproved,a.timestamp,
				CONCAT(c.lname,', ',c.fname,' ',c.mname) AS fullname , c.deptid
				FROM leave_app_dhead a
				LEFT JOIN leave_app_hrd b ON a.aid = b.aid 
				LEFT JOIN employee c ON a.employeeid = c.employeeid
				LEFT JOIN leave_app l ON a.aid = l.id
				WHERE FIND_IN_SET('$user',a.head) AND a.other <> 'DA' $wC AND DATE(a.timestamp) BETWEEN '$dfrom' AND '$dto' AND l.status = '$category'
				UNION
				SELECT d.id,d.aid,d.employeeid,d.head,d.type,d.other,d.othertype,d.paid,d.datefrom,d.dateto,d.timefrom,d.timeto,d.otimefrom,d.otimeto, d.htimefrom, d.htimeto ,d.nodays,d.reason,d.status,d.dateapproved,d.timestamp,CONCAT(c.lname,', ',c.fname,' ',c.mname) AS fullname , c.deptid
				FROM leave_app_hrd d
				LEFT JOIN employee c ON d.employeeid = c.employeeid
				LEFT JOIN leave_app l ON d.aid = l.id
				WHERE FIND_IN_SET('$user',d.head) AND d.other <> 'DA' $wC2 AND DATE(d.timestamp) BETWEEN '$dfrom' AND '$dto'
				AND d.aid NOT IN (SELECT aid FROM leave_app_dhead WHERE FIND_IN_SET('$user',head) AND other <> 'DA' AND status='$category' AND DATE(timestamp) BETWEEN '$dfrom' AND '$dto')
				AND l.status = '$category'");
			}
			else
			{
				$query = $this->db->query("SELECT a.*, CONCAT(b.lname,', ',b.fname,' ',b.mname) as fullname, b.deptid FROM $tbl a
                LEFT JOIN employee b ON a.employeeid = b.employeeid 
                WHERE FIND_IN_SET('$user',a.head) AND DATE(a.timestamp) BETWEEN '$dfrom' AND '$dto' AND a.other<>'DA' $wC");
			}
		}
                
        return $query;
    }
    function loadottatus($category="",$dfrom="",$dto="",$dept="",$cnoti=""){
        $user = $this->session->userdata("username");
        
        if($category)   $wC = " AND a.status='$category'";
        else            $wC = "";
        
        if($dept == "HR")   $tbl = "overtime_app_hrd";
        else                $tbl = "overtime_app_dhead";
        
        if($this->employee->getClusterHead($user))      $tbl = "overtime_app_chead";
        if($cnoti){
        $query = $this->db->query("SELECT a.*, CONCAT(b.lname,', ',b.fname,' ',b.mname) as fullname FROM $tbl a
                                    LEFT JOIN employee b ON a.employeeid = b.employeeid 
                                    WHERE FIND_IN_SET('$user',a.head) AND a.status='PENDING'");
        }else{
        $query = $this->db->query("SELECT a.*, CONCAT(b.lname,', ',b.fname,' ',b.mname) as fullname FROM $tbl a
                                    LEFT JOIN employee b ON a.employeeid = b.employeeid 
                                    WHERE FIND_IN_SET('$user',a.head) AND DATE(a.timestamp) BETWEEN '$dfrom' AND '$dto' $wC");
        }         
        return $query;
    }
    # new function for ica-hyperion 21128
    # by : justin (with e)
    function loadseminarstatusnew($category="",$dfrom="",$dto="",$cnoti=""){
        $empId = $this->session->userdata('username');
        $tbl = array(
                        'dh' => "seminar_app_dhead",
                        'hh' => "seminar_app_hrd",
                        'ch' => "seminar_app_chead",
                        'cp' => "seminar_principal",
                        'bo' => "seminar_app_budgetoff",
                        'fd' => "seminar_app_findir",
                        'ph' => "seminar_app_president",
                    );
        $head = array();
        $sqlList = array();

        
        $user = $this->session->userdata("username");
        $ih   = "d.status";
        $dates = "";
        if($dfrom && $dto) $dates .= "AND DATE(a.timestamp) BETWEEN '$dfrom' AND '$dto'";

        if($category){
            if($this->employee->getDHRCHead($user,"head") && $this->employee->getDHRCHead($user,"divisionhead") != $user && $this->employee->OffBusinessBudgetFinPres("budgetoff") != $user && $this->employee->OffBusinessBudgetFinPres("financedir") != $user)   $wC = " AND a.status='$category'";
            else   $wC = " AND a.status='$category'";
        }   else    $wC = "";

        # dhead
        $dquery = $this->db->query("SELECT * FROM code_office WHERE head='$empId' ")->num_rows();
        if($dquery > 0) array_push($head, "dh");
        
        # chead
        $cquery = $this->db->query("SELECT * FROM code_office WHERE divisionhead='$empId' ")->num_rows();
        if($cquery > 0) array_push($head, "ch");

        # principal
        $scampus = $this->db->query("SELECT * FROM code_campus a WHERE a.`code` = (SELECT e.`campusid` FROM employee e WHERE e.`employeeid`='$user')");
        if($scampus->num_rows() > 0) array_push($head, "cp");

        # hr
        $hquery = $this->db->query("SELECT * FROM code_office WHERE head='$empId' AND code='HR'")->num_rows();
        if($hquery > 0) array_push($head, "hh");

        # bo
        $bquery = $this->db->query("SELECT * FROM code_request_form WHERE budgetoff = '$empId'")->num_rows();
        if($bquery > 0) array_push($head, "bo");

        # fd
        $fquery = $this->db->query("SELECT * FROM code_request_form WHERE financedir = '$empId'")->num_rows();
        if($fquery > 0) array_push($head, "fd");

        # ph
        $pquery = $this->db->query("SELECT * FROM code_request_form WHERE president = '$empId'")->num_rows();
        if($pquery > 0) array_push($head, "ph");

        $count = 0;
        #$sql = "";
        //print_r($head);die;
        foreach ($head as $key) {
            # $nquery = $this->db->query("SELECT * FROM {$tbl[$key]} WHERE head='{$user}' AND status='PENDING'");
            if($cnoti){
                $sqlList[$key] = "SELECT a.*, CONCAT(b.lname,', ',b.fname,' ',b.mname) as fullname, c.points, c.timestamp as dateattached, c.status as attstat FROM {$tbl[$key]} a
                                    LEFT JOIN employee b ON a.employeeid = b.employeeid 
                                    LEFT JOIN seminar_app_attach c ON a.aid = c.id
                                    INNER JOIN seminar_app d ON a.aid = d.id 
                                    WHERE FIND_IN_SET('$user',a.head)  $wC";
                
            }else{
                $sqlList[$key] = "SELECT a.*, CONCAT(b.lname,', ',b.fname,' ',b.mname) as fullname, c.points, c.timestamp as dateattached, c.status as attstat FROM {$tbl[$key]} a
                                        LEFT JOIN employee b ON a.employeeid = b.employeeid 
                                        LEFT JOIN seminar_app_attach c ON a.aid = c.id 
                                        INNER JOIN seminar_app d ON a.aid = d.id
                                        WHERE FIND_IN_SET('$user',a.head) $dates $wC";

            }
        }
        
        return $sqlList;
    }
    # end of new function for ica-hyperion 21128
    function loadseminarstatus($category="",$dfrom="",$dto="",$cnoti=""){
        $user = $this->session->userdata("username");
        $ih   = "d.status";
        $dates = "";
        if($dfrom && $dto) $dates .= "AND DATE(a.timestamp) BETWEEN '$dfrom' AND '$dto'";

        if($category){
            if($this->employee->getDHRCHead($user,"head") && $this->employee->getDHRCHead($user,"divisionhead") != $user && $this->employee->OffBusinessBudgetFinPres("budgetoff") != $user && $this->employee->OffBusinessBudgetFinPres("financedir") != $user)   $wC = " AND a.status='$category'";
            else   $wC = " AND a.status='$category'";
        }   else    $wC = "";
        if($this->employee->getDHRCHead($user,"head") && $this->employee->getDHRCHead($user,"divisionhead") != $user && $this->employee->OffBusinessBudgetFinPres("budgetoff") != $user && $this->employee->OffBusinessBudgetFinPres("financedir") != $user)    $ih = "a.status";    
        
        if($this->employee->getDHRCHead($user,"head")){
            if($user == $this->employee->getDHRCHead($user,"head"))         $tbl = "seminar_app_dhead";
        }            
        if($this->employee->getDHRCHead($user,"head",true)){
            if($user == $this->employee->getDHRCHead($user,"head",true))    $tbl = "seminar_app_hrd";
        }       
        if($this->employee->getDHRCHead($user,"divisionhead")){
            if($user == $this->employee->getDHRCHead($user,"divisionhead"))  $tbl = "seminar_app_chead";
        }
        if($this->employee->OffBusinessBudgetFinPres("budgetoff")){
            if($user == $this->employee->OffBusinessBudgetFinPres("budgetoff"))  $tbl = "seminar_app_budgetoff";
        }
        if($this->employee->OffBusinessBudgetFinPres("financedir")){
            if($user == $this->employee->OffBusinessBudgetFinPres("financedir")) $tbl = "seminar_app_findir";
        }
        if($this->employee->OffBusinessBudgetFinPres("president")){
            if($user == $this->employee->OffBusinessBudgetFinPres("president"))  $tbl = "seminar_app_president";
        }
        #if($this->employeemod->manageseminarnotif()->num_rows() && $this->employee->getDHRCHead($user,"head") && $this->employee->getDHRCHead($user,"divisionhead") != $user && $this->employee->OffBusinessBudgetFinPres("budgetoff") != $user && $this->employee->OffBusinessBudgetFinPres("financedir") != $user && !$wC){
        if($cnoti || $this->employee->getDHRCHead($user,"head") && $this->employee->getDHRCHead($user,"divisionhead") != $user && $this->employee->OffBusinessBudgetFinPres("budgetoff") != $user && $this->employee->OffBusinessBudgetFinPres("financedir") != $user && !$wC){
            $query = $this->db->query("SELECT a.*, CONCAT(b.lname,', ',b.fname,' ',b.mname) as fullname, c.points, c.timestamp as dateattached, c.status as attstat, a.status FROM $tbl a
                                    LEFT JOIN employee b ON a.employeeid = b.employeeid 
                                    LEFT JOIN seminar_app_attach c ON a.aid = c.id
                                    INNER JOIN seminar_app d ON a.aid = d.id 
                                    WHERE FIND_IN_SET('$user',a.head)  $wC");
        }else{
            $query = $this->db->query("SELECT a.*, CONCAT(b.lname,', ',b.fname,' ',b.mname) as fullname, c.points, c.timestamp as dateattached, c.status as attstat, a.status FROM $tbl a
                                        LEFT JOIN employee b ON a.employeeid = b.employeeid 
                                        LEFT JOIN seminar_app_attach c ON a.aid = c.id 
                                        INNER JOIN seminar_app d ON a.aid = d.id
                                        WHERE FIND_IN_SET('$user',a.head) $dates $wC");
        }
        // echo "<pre>".$this->db->last_query();
        return $query;
    }
    
    function loadseminarappdata($id="",$manage=false){
        $user = $this->session->userdata("username");
        $tbl  = "seminar_app";
        if($manage){
            if($this->employee->getDHRCHead($user,"head")){
                if($user == $this->employee->getDHRCHead($user,"head"))         $tbl = "seminar_app_dhead";
            }            
            if($this->employee->getDHRCHead($user,"head",true)){
                if($user == $this->employee->getDHRCHead($user,"head",true))    $tbl = "seminar_app_hrd";
            }       
            if($this->employee->getDHRCHead($user,"divisionhead")){
                if($user == $this->employee->getDHRCHead($user,"divisionhead")) $tbl = "seminar_app_chead";
            }
            if($this->employee->OffBusinessBudgetFinPres("budgetoff")){
                if($user == $this->employee->OffBusinessBudgetFinPres("budgetoff"))  $tbl = "seminar_app_budgetoff";
            }
            if($this->employee->OffBusinessBudgetFinPres("financedir")){
                if($user == $this->employee->OffBusinessBudgetFinPres("financedir")) $tbl = "seminar_app_findir";
            }
            if($this->employee->OffBusinessBudgetFinPres("president")){
                if($user == $this->employee->OffBusinessBudgetFinPres("president"))  $tbl = "seminar_app_president";
            }
        }
        # updated by justin (with e) ica-hyperion 21128
        $tbl = array(
                        'dh' => "seminar_app_dhead",
                        'hh' => "seminar_app_hrd",
                        'ch' => "seminar_app_chead",
                        'cp' => "seminar_principal",
                        'bo' => "seminar_app_budgetoff",
                        'fd' => "seminar_app_findir",
                        'ph' => "seminar_app_president",
                    );
        $expID = explode("-", $id);
        $idx = $expID[0];
        $id = $expID[1];
        $query = $this->db->query("SELECT *,a.`employeeid` AS eid ,c.description AS epos, d.description AS edept FROM {$tbl[$idx]} a 
                                     LEFT JOIN employee b ON a.employeeid = b.employeeid
                                     LEFT JOIN code_position c ON b.positionid = c.positionid
                                     LEFT JOIN code_office d ON b.deptid = d.code 
                                     WHERE id='$id'");
        # end of updated ica-hyperion 21128
        return $query;
    }
    
    function loadovertimeappdata($id="",$mng=false){
        $user = $this->session->userdata("username");
        $tbl  = "overtime_app";
        if(!$mng){
            if($this->employee->getDHRCHead($user,"head")){
                if($user == $this->employee->getDHRCHead($user,"head"))         $tbl = "overtime_app_dhead";
            }            
            if($this->employee->getDHRCHead($user,"head",true)){
                if($user == $this->employee->getDHRCHead($user,"head",true))    $tbl = "overtime_app_hrd";
            }       
        }
        $query = $this->db->query("SELECT * FROM $tbl WHERE id='$id'");
        return $query;
    }
    
    function loadoffbusstatus($category="",$dfrom="",$dto="",$dept="",$cnoti=""){
        $user = $this->session->userdata("username");
        $bfp = $this->employee->getBudgetFinPres($user);
        
        if($category)   $wC = " AND a.status='$category'";
        else            $wC = "";

        if($dept == "HR") $tbl = "leave_app_hrd";
        else              $tbl = "leave_app_dhead";
        
        if(($this->employee->campus_principal($user)) === true) $tbl = "leave_app_principal"; // for principal campus notification
        

        if($this->employee->getClusterHead($user))      $tbl = "leave_app_chead";
        if($this->employee->getUnivPhysician($user))    $tbl = "leave_app_uphy";
        if($bfp) $tbl = $bfp;
        if($cnoti){
			if($dept == "HR")
			{
                //echo "<pre>". print_r("asdasda");die;
                // $query = $this->db->query("SELECT a.*,b.*, CONCAT(c.lname,', ',c.fname,' ',c.mname) AS fullname , c.deptid
                // FROM leave_app_dhead a, leave_app_hrd b
                // LEFT JOIN employee c ON b.employeeid = c.employeeid
                // WHERE FIND_IN_SET('$user',a.head) AND b.other = 'DA' AND b.status = 'PENDING'");
                $query = $this->db->query("SELECT a.id,a.aid,a.employeeid,a.head,a.type,a.other,a.othertype,a.paid,a.datefrom,a.dateto,a.timefrom,a.timeto,a.otimefrom,a.otimeto,
                b.htimefrom, b.htimeto ,a.nodays,a.reason,a.status,a.dateapproved,a.timestamp,
                CONCAT(c.lname,', ',c.fname,' ',c.mname) AS fullname , c.deptid
                FROM leave_app_dhead a
                LEFT JOIN leave_app_hrd b ON a.aid = b.aid 
                LEFT JOIN leave_app e ON e.employeeid = a.employeeid
                LEFT JOIN employee c ON e.employeeid = c.employeeid
                WHERE FIND_IN_SET('$user',a.head) AND a.other = 'DA' AND a.status = 'PENDING'
                UNION
                SELECT d.id,d.aid,d.employeeid,d.head,d.type,d.other,d.othertype,d.paid,d.datefrom,d.dateto,d.timefrom,d.timeto,d.otimefrom,d.otimeto, d.htimefrom, d.htimeto ,d.nodays,d.reason,d.status,d.dateapproved,d.timestamp,CONCAT(c.lname,', ',c.fname,' ',c.mname) AS fullname , c.deptid
                FROM leave_app_hrd d
                LEFT JOIN leave_app e ON e.employeeid = d.employeeid
                LEFT JOIN employee c ON e.employeeid = c.employeeid
                WHERE FIND_IN_SET('$user',d.head) AND d.other = 'DA' AND d.status = 'PENDING'");
            }
            else
            {
                $query = $this->db->query("SELECT a.*, CONCAT(b.lname,', ',b.fname,' ',b.mname) as fullname, b.deptid FROM $tbl a
                LEFT JOIN employee b ON a.employeeid = b.employeeid 
                WHERE FIND_IN_SET('$user',a.head) AND a.status='PENDING' AND a.other='DA'");
            }
        }else{
            if($dept == "HR")
            {
                if($wC != "") {  $wC = " AND a.status='$category'"; $wC2 = " AND d.status='$category'";}
                else            {$wC = ""; $wC2 = "";}
                // $query = $this->db->query("SELECT a.*,b.*, CONCAT(c.lname,', ',c.fname,' ',c.mname) AS fullname , c.deptid
                // FROM leave_app_dhead a, leave_app_hrd b
                // LEFT JOIN employee c ON b.employeeid = c.employeeid
                // WHERE FIND_IN_SET('$user',a.head) AND b.other = 'DA' $wC AND DATE(a.timestamp) BETWEEN '$dfrom' AND '$dto' AND '$dto' ");
                $query = $this->db->query("SELECT a.id,a.aid,a.employeeid,a.head,a.type,a.other,a.othertype,a.paid,a.datefrom,a.dateto,a.timefrom,a.timeto,a.otimefrom,a.otimeto,
				b.htimefrom, b.htimeto ,a.nodays,a.reason,a.status,a.dateapproved,a.timestamp,
				CONCAT(c.lname,', ',c.fname,' ',c.mname) AS fullname , c.deptid
				FROM leave_app_dhead a
				LEFT JOIN leave_app_hrd b ON a.aid = b.aid 
				LEFT JOIN leave_app e ON e.employeeid = a.employeeid
				LEFT JOIN employee c ON e.employeeid = c.employeeid
				WHERE FIND_IN_SET('$user',a.head) AND a.other = 'DA' $wC AND DATE(e.timestamp) BETWEEN '$dfrom' AND '$dto'
				UNION
				SELECT d.id,d.aid,d.employeeid,d.head,d.type,d.other,d.othertype,d.paid,d.datefrom,d.dateto,d.timefrom,d.timeto,d.otimefrom,d.otimeto, d.htimefrom, d.htimeto ,d.nodays,d.reason,d.status,d.dateapproved,d.timestamp,
                CONCAT(c.lname,', ',c.fname,' ',c.mname) AS fullname , c.deptid
				FROM leave_app_hrd d
				LEFT JOIN leave_app e ON e.employeeid = d.employeeid
				LEFT JOIN employee c ON e.employeeid = c.employeeid
				WHERE FIND_IN_SET('$user',d.head) AND d.other = 'DA' $wC2 AND DATE(e.timestamp) BETWEEN '$dfrom' AND '$dto' ");
			}
			else
			{
				$query = $this->db->query("SELECT a.*, CONCAT(b.lname,', ',b.fname,' ',b.mname) as fullname, b.deptid FROM $tbl a
                LEFT JOIN employee b ON a.employeeid = b.employeeid 
                WHERE FIND_IN_SET('$user',a.head) AND DATE(a.timestamp) BETWEEN '$dfrom' AND '$dto' AND a.other='DA' $wC");
			}
		}
		
        
        return $query;
    }
    
    function loadseminarapp_attachment($id=""){
        $user  = $this->session->userdata("username");
        $query = $this->db->query("SELECT a.*,b.points,b.timestamp as dateattached,b.status,a.hrdir FROM seminar_app a LEFT JOIN seminar_app_attach b ON a.id = b.id WHERE a.id='$id'");
        return $query;
    }
    
    function attendance_confirmation($cfrom="", $cto=""){
        $return = "";
        $dfrom  = "";
        $dto    = "";
        // $query = $this->db->query("SELECT * FROM cutoff WHERE CURRENT_DATE BETWEEN ConfirmFrom AND ConfirmTo AND CutoffFrom <= CURRENT_DATE ORDER BY CutoffFrom DESC LIMIT 1");
        $query = $this->db->query("SELECT * FROM cutoff WHERE CutoffFrom = '$cfrom' AND CutoffTo = '$cto'  ORDER BY CutoffFrom DESC LIMIT 1");
        if($query->num_rows() > 0){
            $return  = "<p style='font-size:17px;'><b>Reminder : </b>Kindly confirm your attendance From <b style='color:red;font-size:17px;'><u>".date("F d, Y",strtotime($query->row(0)->CutoffFrom))."</u></b style='color:red;'> to <b style='color:red;'><u>".date("F d, Y",strtotime($query->row(0)->CutoffTo))."</u></b>. Failure to confirm your attendance within <b style='color:red;font-size:19px;'><u>".date("F d, Y",strtotime($query->row(0)->ConfirmFrom))."</u></b> to <b style='color:red;font-size:19px;'><u>".date("F d, Y",strtotime($query->row(0)->ConfirmTo))." between ".date("h:i A",strtotime($query->row(0)->TimeFrom))." and ".date("h:i A",strtotime($query->row(0)->TimeTo))." </u></b> will be considered as confirmed.</p>";
            // $return .= "Click Here to view Attendance for this cut off: <a href='#' class='btn blue' id='viewcutoff'>View Cut-off</a>";
            $dfrom   = $query->row(0)->CutoffFrom;
            $dto     = $query->row(0)->CutoffTo;
        }
        return array($return,$dfrom,$dto);
    }
    
    function loadleaveCredsetup($data){
        $cred = 0;
        $query = $this->db->query("SELECT * FROM code_request_form WHERE code_request='{$data['ltype']}'");
        if($query->num_rows() > 0)  $cred = $query->row(0)->credits;
        return $cred;
    }    
    
    function loadleaveCred(){
        $query = $this->db->query("SELECT * FROM code_leave_setup ORDER BY timestamp DESC");
        return $query;
    }

    function loadleaveSchoolYear($id = ""){
        $where ="";
        if ($id != "") $where = "WHERE id = '$id'";
        $query = $this->db->query("SELECT * FROM code_leave_school_coverage $where ORDER BY timestamp DESC");
        return $query;
    }

    function loadAutoRecomputeErrorLogs($id = ""){
        $where ="";
        if ($id != "") $where = "WHERE id = '$id'";
        $query = $this->db->query("SELECT * FROM employee_leave_credit_auto $where ORDER BY timestamp DESC");
        return $query;
    }
    
    function load_leave_setup($id=""){
        $query = $this->db->query("SELECT * FROM code_leave_setup WHERE id='$id'");
        return $query;
    }

    function load_leave_setup_coverage($id=""){
        $where ="";
        if ($id != "") $where = "WHERE id = '$id'";
        $query = $this->db->query("SELECT * FROM code_leave_coverage $where");
        return $query;
    }
    
    function getLeaveHead($id=""){
        $query = $this->db->query("SELECT * FROM leave_app WHERE id='$id'");
        return $query;
    }
    
    function getSeminarHead($id=""){
        $query = $this->db->query("SELECT * FROM seminar_app WHERE id='$id'");
        return $query;
    }
    
    function getOvertimeHead($id=""){
        $query = $this->db->query("SELECT * FROM overtime_app WHERE id='$id'");
        return $query;
    }
    
    function getChangeSchedHead($id=""){
        $query = $this->db->query("SELECT * FROM change_schedule_request WHERE id='$id'");
        return $query;
    }
    
    function loadSchedday(){
        $query = $this->db->query("SELECT B.day_code,B.day_name, A.starttime,A.endtime,A.idx,A.tardy_start,A.absent_start,A.tardy_half_start,A.absent_half_start,A.no_schedule,A.half_schedule,A.early_dismissal from employee_schedule as A INNER join code_daysofweek AS B on A.dayofweek = B.day_code GROUP BY day_code ORDER BY idx")->result();                    
        return $query;
    }
    
    function loadschedreqdata($id=""){
        $query = $this->db->query("SELECT *,c.description AS epos, d.description AS edept FROM change_schedule_request a 
                                    LEFT JOIN employee b ON a.employeeid = b.employeeid 
                                    LEFT JOIN code_position c ON b.positionid = c.positionid
                                    LEFT JOIN code_office d ON b.deptid = d.code
                                    WHERE a.id='$id'");
        return $query;
    }
    function loadschedreqset($id=""){
        $query = $this->db->query("SELECT * FROM change_schedule a INNER JOIN code_daysofweek AS b ON a.dayofweek = b.day_code WHERE id='$id'");
        return $query;
    }
    
    // Notif
    function leavenotif(){
        $user  = $this->session->userdata("username");
        $query = $this->db->query("SELECT * FROM leave_app WHERE isread=0 AND other <> 'DA' AND employeeid='$user'");
        return $query; 
    }
    function seminarnotif(){
        $user  = $this->session->userdata("username");
        $query = $this->db->query("SELECT * FROM seminar_app WHERE isread=0 AND applied_by='$user'");
        return $query; 
    }
    function overtimenotif(){
        $user  = $this->session->userdata("username");
        $query = $this->db->query("SELECT * FROM overtime_app WHERE isread=0 AND employeeid='$user'");
        return $query; 
    }
    function offbusnotif(){
        $user  = $this->session->userdata("username");
        $query = $this->db->query("SELECT * FROM leave_app WHERE isread=0 AND other = 'DA' AND employeeid='$user'");
        return $query; 
    }
    function cschednotif(){
        $user  = $this->session->userdata("username");
        $query = $this->db->query("SELECT * FROM change_schedule_request WHERE isread=0 AND employeeid='$user'");
        return $query; 
    }
    function manageleavenotif(){
        $user = $this->session->userdata("username");
        $bfp = $this->employee->getBudgetFinPres($user);
        $tbl = "leave_app";
        if($this->employee->getDHRCHead($user,"head")){
            if($user == $this->employee->getDHRCHead($user,"head"))         $tbl = "leave_app_dhead";
        }            
        if($this->employee->getDHRCHead($user,"head",true)){
            if($user == $this->employee->getDHRCHead($user,"head",true))    $tbl = "leave_app_hrd";
        }
        if(($this->employee->campus_principal($user)) === true){
            $tbl = "leave_app_principal"; // for principal campus notification
        }       
        if($this->employee->getDHRCHead($user,"divisionhead")){
            if($user == $this->employee->getDHRCHead($user,"divisionhead")) $tbl = "leave_app_chead";
        }
        if($this->employee->getUnivPhysician($user))                        $tbl = "leave_app_uphy";
        if($bfp)                                                            $tbl = $bfp;    
        
		if($tbl != "leave_app_hrd" && $tbl != "leave_app_hrd")
		{
			 $query = $this->db->query("SELECT * FROM $tbl WHERE FIND_IN_SET('{$user}',head) AND other <> 'DA' AND status='PENDING'");
		}
		else
		{
			
			// $query = $this->db->query("SELECT a.*,b.* FROM leave_app_dhead a, leave_app_hrd b WHERE a.other = 'DA' AND b.status = 'PENDING' AND FIND_IN_SET('{$user}',b.head)");
			$query = $this->db->query("SELECT a.id,a.aid,a.employeeid,a.head,a.type,a.other,a.othertype,a.paid,a.datefrom,a.dateto,a.timefrom,a.timeto,a.otimefrom,a.otimeto,
				b.htimefrom, b.htimeto ,a.nodays,a.reason,a.status,a.dateapproved,a.timestamp
				FROM leave_app_dhead a
				LEFT JOIN leave_app_hrd b ON a.aid = b.aid 
				WHERE FIND_IN_SET('$user',a.head) AND a.other <> 'DA' AND a.status = 'PENDING'
				UNION
				SELECT d.id,d.aid,d.employeeid,d.head,d.type,d.other,d.othertype,d.paid,d.datefrom,d.dateto,d.timefrom,d.timeto,d.otimefrom,d.otimeto, d.htimefrom,
                d.htimeto ,d.nodays,d.reason,d.status,d.dateapproved,d.timestamp
				FROM leave_app_hrd d
				WHERE FIND_IN_SET('$user',d.head) AND d.other <> 'DA' AND d.status = 'PENDING'
			");
		}
        return $query;
    }
    # new function for ica-hyperion 21128
    function manageseminarnotifnew(){
        $empId = $this->session->userdata('username');
        $tbl = array(
                        'dh' => "seminar_app_dhead",
                        'hh' => "seminar_app_hrd",
                        'ch' => "seminar_app_chead",
                        'cp' => "seminar_principal",
                        'bo' => "seminar_app_budgetoff",
                        'fd' => "seminar_app_findir",
                        'ph' => "seminar_app_president",
                    );
        $head = array();

        $user = $this->session->userdata("username");
        # dhead
        $dquery = $this->db->query("SELECT * FROM code_office WHERE head='$empId' ")->num_rows();
        if($dquery > 0) array_push($head, "dh");
        
        # chead
        $cquery = $this->db->query("SELECT * FROM code_office WHERE divisionhead='$empId' ")->num_rows();
        if($cquery > 0) array_push($head, "ch");

        # principal
        $scampus = $this->db->query("SELECT * FROM code_campus a WHERE a.`code` = (SELECT e.`campusid` FROM employee e WHERE e.`employeeid`='$user')");
        if($scampus->num_rows() > 0) array_push($head, "cp");

        # hr
        $hquery = $this->db->query("SELECT * FROM code_office WHERE head='$empId' AND code='HR'")->num_rows();
        if($hquery > 0) array_push($head, "hh");

        # bo
        $bquery = $this->db->query("SELECT * FROM code_request_form WHERE budgetoff = '$empId'")->num_rows();
        if($bquery > 0) array_push($head, "bo");

        # fd
        $fquery = $this->db->query("SELECT * FROM code_request_form WHERE financedir = '$empId'")->num_rows();
        if($fquery > 0) array_push($head, "fd");

        # ph
        $pquery = $this->db->query("SELECT * FROM code_request_form WHERE president = '$empId'")->num_rows();
        if($pquery > 0) array_push($head, "ph");

        $count = 0;
        #$sql = "";
        foreach ($head as $key) {
            $nquery = $this->db->query("SELECT * FROM {$tbl[$key]} WHERE head='{$user}' AND status='PENDING'");
            #$sql .= "SELECT * FROM {$tbl[$key]} WHERE head='{$user}' AND status='PENDING'". " ~u~";
            $count += $nquery->num_rows();
        }
        //echo "<pre>" .print_r($sql); die;
        return $count;
    }

    # end of new function for ica-hyperion 21128
    function manageseminarnotif($mng=false){
        $user = $this->session->userdata("username");
        $tbl = "seminar_app";
        if($this->employee->getDHRCHead($user,"head")){
            if($user == $this->employee->getDHRCHead($user,"head"))         $tbl = "seminar_app_dhead";
        }            
        if($this->employee->getDHRCHead($user,"head",true)){
            if($user == $this->employee->getDHRCHead($user,"head",true))    $tbl = "seminar_app_hrd";
        }       
        if($this->employee->getDHRCHead($user,"divisionhead")){
            if($user == $this->employee->getDHRCHead($user,"divisionhead")) $tbl = "seminar_app_chead";
        }
        if($this->employee->OffBusinessBudgetFinPres("budgetoff")){
            if($user == $this->employee->OffBusinessBudgetFinPres("budgetoff"))  $tbl = "seminar_app_budgetoff";
        }
        if($this->employee->OffBusinessBudgetFinPres("financedir")){
            if($user == $this->employee->OffBusinessBudgetFinPres("financedir")) $tbl = "seminar_app_findir";
        }
        if($this->employee->OffBusinessBudgetFinPres("president")){
            if($user == $this->employee->OffBusinessBudgetFinPres("president"))  $tbl = "seminar_app_president";
        }
        
       /* if($mng){
            if($this->employee->getDHRCHead($user,"head") && $this->employee->getDHRCHead($user,"divisionhead") != $user && $this->employee->OffBusinessBudgetFinPres("budgetoff") != $user && $this->employee->OffBusinessBudgetFinPres("financedir")  != $user && $this->employee->OffBusinessBudgetFinPres("president")  != $user)
            $query = $this->db->query("SELECT * FROM $tbl a INNER JOIN seminar_app b ON a.aid = b.id WHERE a.head='{$user}' AND b.status='APPROVED' AND isread=0");
            else
            $query = $this->db->query("SELECT * FROM $tbl a INNER JOIN seminar_app b ON a.aid = b.id WHERE a.head='{$user}' AND a.status='PENDING'");
        }else
            $query = $this->db->query("SELECT * FROM $tbl WHERE head='{$user}' AND status='APPROVED'");*/

        $query = $this->db->query("SELECT * FROM $tbl a INNER JOIN seminar_app b ON a.aid = b.id WHERE a.head='{$user}' AND a.status='PENDING'");
        return $query; 
    }
    function manageovertimenotif(){
        $user = $this->session->userdata("username");
        $tbl = "overtime_app";                
        if($this->employee->getDHRCHead($user,"head")){
            if($user == $this->employee->getDHRCHead($user,"head"))         $tbl = "overtime_app_dhead";
        }            
        if($this->employee->getDHRCHead($user,"head",true)){
            if($user == $this->employee->getDHRCHead($user,"head",true))    $tbl = "overtime_app_hrd";
        }       
        if($this->employee->getDHRCHead($user,"divisionhead")){
            if($user == $this->employee->getDHRCHead($user,"divisionhead")) $tbl = "overtime_app_chead";
        }
        $query = $this->db->query("SELECT * FROM $tbl WHERE head='{$user}' AND status='PENDING'");
        return $query;
    }
    function manageoffbusnotif(){
        $user = $this->session->userdata("username");
        $tbl = "leave_app";
        $tbl2 = "";
        if($this->employee->getDHRCHead($user,"head")){
            if($user == $this->employee->getDHRCHead($user,"head"))         $tbl = "leave_app_dhead";
        }            
        if($this->employee->getDHRCHead($user,"head",true)){
            if($user == $this->employee->getDHRCHead($user,"head"))    $tbl = "leave_app_hrd";
        }       
        if($this->employee->getDHRCHead($user,"divisionhead")){
            if($user == $this->employee->getDHRCHead($user,"divisionhead")) $tbl = "leave_app_chead";
        }
        if(($this->employee->campus_principal($user)) === true){
            $tbl = "leave_app_principal"; // for principal campus notification
        }
        if($this->employee->getUnivPhysician($user))                        $tbl = "leave_app_uphy";
        if($this->employee->OffBusinessBudgetFinPres("budgetoff")){
            if($user == $this->employee->OffBusinessBudgetFinPres("budgetoff"))  $tbl = "leave_app_budgetoff";
        }
        if($this->employee->OffBusinessBudgetFinPres("financedir")){
            if($user == $this->employee->OffBusinessBudgetFinPres("financedir")) $tbl = "leave_app_financedir";
        }
        if($this->employee->OffBusinessBudgetFinPres("president")){
            if($user == $this->employee->OffBusinessBudgetFinPres("president"))  $tbl = "leave_app_president";
        }
        if($tbl != "leave_app_hrd" && $tbl != "leave_app_hrd")
        {
            $query = $this->db->query("SELECT * FROM $tbl WHERE FIND_IN_SET('{$user}',head) AND other = 'DA' AND status='PENDING'");
        }
        else
        {
        
			// $query = $this->db->query("SELECT a.*,b.* FROM leave_app_dhead a, leave_app_hrd b WHERE a.other = 'DA' AND b.status = 'PENDING' AND FIND_IN_SET('{$user}',b.head)");
			$query = $this->db->query("SELECT a.id,a.aid,a.employeeid,a.head,a.type,a.other,a.othertype,a.paid,a.datefrom,a.dateto,a.timefrom,a.timeto,a.otimefrom,a.otimeto,
			b.htimefrom, b.htimeto ,a.nodays,a.reason,a.status,a.dateapproved,a.timestamp
			FROM leave_app_dhead a
			LEFT JOIN leave_app_hrd b ON a.aid = b.aid 
			WHERE FIND_IN_SET('$user',a.head) AND a.other = 'DA' AND a.status = 'PENDING'
			UNION
			SELECT d.id,d.aid,d.employeeid,d.head,d.type,d.other,d.othertype,d.paid,d.datefrom,d.dateto,d.timefrom,d.timeto,d.otimefrom,d.otimeto, d.htimefrom, d.htimeto ,d.nodays,d.reason,d.status,d.dateapproved,d.timestamp
			FROM leave_app_hrd d
			WHERE FIND_IN_SET('$user',d.head) AND d.other = 'DA' AND d.status = 'PENDING'
						");
		}
        return $query;
    }
    function managecschednotif(){
        $user = $this->session->userdata("username");
        $tbl = "change_schedule_request";            
        if($this->employee->getDHRCHead($user,"divisionhead"))  if($user == $this->employee->getDHRCHead($user,"divisionhead")) $tbl = "change_schedule_request_chead";
        if($this->employee->getDHRCHead($user,"head",true))     if($user == $this->employee->getDHRCHead($user,"head",true))    $tbl = "change_schedule_request_hrd";
        
        $query = $this->db->query("SELECT * FROM $tbl WHERE FIND_IN_SET('{$user}',head) AND status='PENDING'");
        return $query;
    }
    function compensationhistory($user=""){
        $query = $this->db->query("SELECT * FROM payroll_employee_salary_history WHERE employeeid='$user'");
        return $query;
    }
    function empothincome($user=""){
        $query = $this->db->query("SELECT * FROM employee_income_oth a INNER JOIN payroll_income_oth_config b ON a.code_income = b.id WHERE employeeid='$user'");
        return $query;
    }

        
    /*
     * Display Options
     */
    function withPay($param=""){
        $arr = array("YES","NO");
        $opt='';
        foreach($arr as $desc){
            if($param == $desc) $sel = " selected";
            else                $sel = "";
            $opt .= "<option value='$desc' $sel>$desc</option>";
        }
        return $opt;
    }
    function othLeave($type="",$isother=true,$noDA='',$leaveonly=''){
        $opt = "<option value=''>Select Leave Type</option>";
        // if($isother) $wC = " WHERE ismain=0";
        // else         $wC = " WHERE ismain=1";

        if($isother) $wC = " WHERE is_leave=0";
        else         $wC = " WHERE is_leave=1";
        
        if($noDA) $wC .= ($wC ? " AND code_request != 'DA'  AND code_request != 'DAHEAD'" : " WHERE code_request != 'DA' AND code_request != 'DAHEAD'");

        $query = $this->db->query("SELECT a.code_request, b.description AS app_desc, a.* FROM code_request_form a INNER JOIN online_application_code b ON a.base_id = b.id $wC");
        foreach($query->result() as $row){
            $code = $row->code_request;
            $pos = strpos($code, 'NON');
            $pos1 = strpos($code, 'HEAD');
            if($pos==false && $pos1==false){
                if($type == $code) $sel = " selected";
                else                            $sel = "";
                $opt .= "<option value='".$code."' $sel>".$row->app_desc."</option>";
            }
        }
        return $opt;
    }

    function displayCutOff($withCaption=true){
        $date_now = date('Y-m-d');
        $sel = $opt = "";
        if($withCaption){
            $opt = "<option value=''>Select Cut-Off</option>";
        }
        $query = $this->db->query("SELECT * FROM cutoff ORDER BY CutoffFrom DESC, CutoffTo DESC");
        foreach($query->result() as $row){
            $nodtr_tag = "";
            if($this->checkIfNoDTR($row->ID)) $nodtr_tag = "(No DTR)";

            $opt .= "<option value='".Globals::_e($row->CutoffFrom).",".Globals::_e($row->CutoffTo)."' $sel>".date("F d, Y",strtotime($row->CutoffFrom))." - ".date("F d, Y",strtotime($row->CutoffTo))." $nodtr_tag </option>";
        }
        return $opt;
    }

    //lndrsnts
    function loadCutOffBySchedule($schedule = '') {
        $whereClause = !empty($schedule) ? "WHERE b.schedule = '$schedule'" : '';
    
        $options = "<option value=''>Select Cut-Off</option>";
        $query = $this->db->query("
            SELECT 
                a.`ID`, 
                a.`CutoffFrom`, 
                a.`CutoffTo`
            FROM 
                cutoff AS a
            LEFT JOIN 
                payroll_cutoff_config b 
            ON 
                a.`ID` = b.`baseid`
            $whereClause
            ORDER BY 
                a.`CutoffFrom` DESC, 
                a.`CutoffTo` DESC
        ");
    
        foreach ($query->result() as $row) {
            $nodtrTag = $this->checkIfNoDTR($row->ID) ? "(No DTR)" : '';
            $cutoffFrom = date("F d, Y", strtotime($row->CutoffFrom));
            $cutoffTo = date("F d, Y", strtotime($row->CutoffTo));
            $encodedValue = Globals::_e($row->CutoffFrom) . "," . Globals::_e($row->CutoffTo);
            
            $options .= "<option value='$encodedValue'>$cutoffFrom - $cutoffTo $nodtrTag</option>";
        }
    
        return $options;
    }    
    
    function displayCutOffcampus($withCaption=true){
        $date_now = date('Y-m-d');
        $sel = $opt = "";
        if($withCaption){
            $opt = "<option value=''>Select Cut-Off</option>";
        }
        $query = $this->db->query("SELECT * FROM code_campus WHERE description  ORDER BY description DESC");
        foreach($query->result() as $row){
            $nodtr_tag = "";
            if($this->checkIfNoDTR($row->ID)) $nodtr_tag = "(No DTR)";

            $opt .= "<option value='".$row->description."' $nodtr_tag </option>";
        }
        return $opt;
    }

    function checkIfNoDTR($id){
        return $this->db->query("SELECT * FROM payroll_cutoff_config WHERE baseid = '$id' AND (nodtr != '' AND nodtr = '1') ")->num_rows();
    }

    function getCutoff($cutoff){
        $arr_cutoff = array();
        $exp_co = explode("~~", $cutoff);

        $cutoffstart = $exp_co[1] .'-'. date("m", strtotime($exp_co[0])) .'-%';

        $query = $this->db->query("SELECT DISTINCT cutoffstart, cutoffend FROM payroll_computed_table WHERE cutoffstart LIKE '$cutoffstart';")->result();
        foreach ($query as $res) {
            $arr_cutoff[] = array(
                'start_date' => $res->cutoffstart, 
                'end_date' => $res->cutoffend 
            );
        }
        return $arr_cutoff;
    }
    
    function displayIncomeReportCutOff($perMonth = false){
        $sel = "";
        $opt = "<option value=''>Select Cut-Off</option>";

        # > month array..
        $month = array();

        $query = $this->db->query("SELECT DISTINCT cutoffstart,cutoffend FROM payroll_computed_table ORDER BY cutoffstart DESC");
        foreach($query->result() as $row){
            $opt .= "<option value='".$row->cutoffstart.",".$row->cutoffend."' $sel>".date("F d, Y",strtotime($row->cutoffstart))." - ".date("F d, Y",strtotime($row->cutoffend))."</option>";

            # > push sa month...
            if(!(array_key_exists(date("F",strtotime($row->cutoffstart)), $month))){
                $month[date("F-Y",strtotime($row->cutoffstart))] = date("F",strtotime($row->cutoffstart)).'-'. date("Y",strtotime($row->cutoffstart));
            }
        }

        # > change the value of opt var.
        if($perMonth){
            $opt = '';
            $opt = "<option value=''>Select Cut-Off</option>";
            $i = 0;
            foreach ($month as $code => $desc) {
                $opt .= "<option value='$code'> $desc </option>";
            }
        }
        return $opt;
    }
	
	function displayCutOffconfig(){
        $sel = "";
        $opt = "<option value=''>Select Cut-Off</option>";
        $query = $this->db->query("SELECT * FROM payroll_cutoff_config ORDER BY Timestamp DESC");
        foreach($query->result() as $row){
            $opt .= "<option value='".$row->startdate.",".$row->enddate."' $sel>".date("F d, Y",strtotime($row->startdate))." - ".date("F d, Y",strtotime($row->enddate))."</option>";
        }
        return $opt;
    }
	
    function getAppSequence($type = "",$deptid = "",$code = "",$eid = ""){
        
        $qdhead = $this->db->query("SELECT head,divisionhead FROM code_office WHERE code='$deptid'");
        $dhead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->head : "");                         // department head
        $chead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->divisionhead : "");                 // Cluster head
        $findCP = $this->db->query("SELECT em.employeeid, cc.* FROM code_campus AS cc, employee AS em WHERE cc.code = em.`campusid` AND employeeid='{$eid}'");
        $cphead  = ($findCP->num_rows() > 0 ? $findCP->row(0)->campus_principal : "");                 // Cluster head
        $qhrd   = $this->db->query("SELECT head FROM code_office WHERE code='HR'");
        $hrd    = ($qhrd->num_rows() > 0 ? $qhrd->row(0)->head : "");                             // HR Director
        $qup    = $this->db->query("SELECT * FROM code_request_form WHERE (univphy<>'' OR univphyt<>'') AND code_request='$code'"); // University Physician
        $up     = ($qup->num_rows() > 0 ? $qup->row(0)->univphy.",".$qup->row(0)->univphyt : "");

        
        $seq = array();
        $lquery = $this->db->query("SELECT * FROM code_request_form WHERE code_request='{$type}'");
        if($lquery->num_rows()){
            $seq = array (
                            $lquery->row()->dhseq => array("type"=>"dh","tbl"=>"leave_app_dhead", "head"=> $dhead),
                            $lquery->row()->hhseq => array("type"=>"hh","tbl"=>"leave_app_hrd", "head"=> $hrd),
                            $lquery->row()->chseq => array("type"=>"ch","tbl"=>"leave_app_chead", "head"=> $chead),
                            $lquery->row()->cpseq => array("type"=>"cp","tbl"=>"leave_app_principal", "head"=> $cphead),
                            $lquery->row()->upseq => array("type"=>"up","tbl"=>"leave_app_uphy", "head"=> $up),
                            $lquery->row()->boseq => array("type"=>"bo","tbl"=>"leave_app_budgetoff", "head"=> $lquery->row()->budgetoff),
                            $lquery->row()->fdseq => array("type"=>"fd","tbl"=>"leave_app_financedir", "head"=> $lquery->row()->financedir),
                            $lquery->row()->pseq  => array("type"=>"pt","tbl"=>"leave_app_president", "head"=> $lquery->row()->president)
                          );
            ksort($seq);
        }
        return $seq;
    }

    function getSeminarSequence($type = "",$deptid = "",$code = "",$campus=""){
        
        $qdhead = $this->db->query("SELECT head,divisionhead FROM code_office WHERE code='$deptid'");
        $dhead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->head : "");                         // department head
        $chead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->divisionhead : "");                 // Cluster head
        $qcampus = $this->db->query("SELECT campus_principal FROM code_campus WHERE code='$campus'");
        $cphead  = ($qcampus->num_rows() > 0 ? $qcampus->row(0)->campus_principal : "");                 // Cluster head
        $qhrd   = $this->db->query("SELECT head FROM code_office WHERE code='HR'");
        $hrd    = ($qhrd->num_rows() > 0 ? $qhrd->row(0)->head : "");                             // HR Director
        $qup    = $this->db->query("SELECT * FROM code_request_form WHERE (univphy<>'' OR univphyt<>'') AND code_request='$code'"); // University Physician
        $up     = ($qup->num_rows() > 0 ? $qup->row(0)->univphy.",".$qup->row(0)->univphyt : "");
        
        $seq = array();
        $lquery = $this->db->query("SELECT * FROM code_request_form WHERE code_request='{$type}'");
        if($lquery->num_rows()){
            $seq = array (
                            $lquery->row()->dhseq => array("type"=>"dh","tbl"=>"seminar_app_dhead", "head"=> $dhead),
                            $lquery->row()->hhseq => array("type"=>"hh","tbl"=>"seminar_app_hrd", "head"=> $hrd),
                            $lquery->row()->chseq => array("type"=>"ch","tbl"=>"seminar_app_chead", "head"=> $chead),
                            $lquery->row()->cpseq => array("type"=>"cp","tbl"=>"seminar_principal", "head"=> $cphead),
                            $lquery->row()->upseq => array("type"=>"up","tbl"=>"seminar_app_uphy", "head"=> $up),
                            $lquery->row()->boseq => array("type"=>"bo","tbl"=>"seminar_app_budgetoff", "head"=> $lquery->row()->budgetoff),
                            $lquery->row()->fdseq => array("type"=>"fd","tbl"=>"seminar_app_findir", "head"=> $lquery->row()->financedir),
                            $lquery->row()->pseq  => array("type"=>"pt","tbl"=>"seminar_app_president", "head"=> $lquery->row()->president)
                          );
            ksort($seq);
        }
        return $seq;
    }
    
    /*
     * Display Options Desc
     */
    function othLeaveDesc($type=""){
        $return = $wC = "";
        if($type)   $wC = " WHERE code_request='$type'";
        $query = $this->db->query("SELECT b.description FROM code_request_form a INNER JOIN online_application_code b ON a.base_id = b.id $wC");
        foreach($query->result() as $row){
            $return = $row->description;
        }
		// if($return == "ABSENT") $return = "ABSENT W/ FILE";
        return $return;
    }

    function othLeaveSubDesc($type=""){
        $return = $wC = "";
        if($type)   $wC = " WHERE code_request='$type'";
        $query = $this->db->query("SELECT description FROM code_request_form $wC");
        foreach($query->result() as $row){
            $return = $row->description;
        }
        return $return;
    }
    
    /*
     * Save Data
     */
    function applyLeave($data){
        $return = ""; 
        $reason = $this->extras->clean($data['reason']);
        $eid    = $this->session->userdata("username");
        $data['datesetfrom']  = isset($data['datesetfrom']) ? $data['datesetfrom'] : "";
        $data['datesetto']    = isset($data['datesetto']) ? $data['datesetto'] : "";
        $tfrom  = isset($data['tfrom']) ? date("H:i:s",strtotime($data['tfrom'])) : "";
        $tto    = isset($data['tto']) ? date("H:i:s",strtotime($data['tto'])) : "";
        $dltype = isset($data['dltype']) ? $data['dltype'] : "";
        $user   = $this->session->userdata("username");
        $qdept  = $this->db->query("SELECT deptid FROM employee WHERE employeeid='$user'");
        $deptid = ($qdept->num_rows() > 0 ? $qdept->row(0)->deptid : "");
        if($deptid){
            $qdhead = $this->db->query("SELECT head,divisionhead FROM code_office WHERE code='$deptid'");
            $dhead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->head : "");                         // department head
            $chead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->divisionhead : "");                 // Cluster head
            $qhrd   = $this->db->query("SELECT head FROM code_office WHERE code='HR'");
            $hrd    = ($qhrd->num_rows() > 0 ? $qhrd->row(0)->head : "");                             // HR Director
            $qup    = $this->db->query("SELECT * FROM code_request_form WHERE (univphy<>'' OR univphyt<>'') AND code_request='{$data['ltype']}'"); // University Physician
            $up     = ($qup->num_rows() > 0 ? $qup->row(0)->univphy.",".$qup->row(0)->univphyt : "");
            
            $continue = true;
            $msg      = "";
            if($data['ltype'] != "other"){
                $query  = $this->db->query("SELECT * FROM employee_leave_credit WHERE employeeid='$eid' AND leavetype='{$data['ltype']}' AND ('{$data['datesetfrom']}' BETWEEN dfrom AND dto OR '{$data['datesetto']}' BETWEEN dfrom AND dto)");
                if($query->num_rows() > 0){  
                    $lbal = $query->row(0)->balance;
                    if($data['ltype'] == "VL"){
                        $qbal = $this->db->query("SELECT * FROM employee_leave_credit WHERE employeeid='$eid' AND leavetype='EL' AND ('{$data['datesetfrom']}' BETWEEN dfrom AND dto OR '{$data['datesetto']}' BETWEEN dfrom AND dto)");
                        if($qbal->num_rows() > 0){
                            if($qbal->row(0)->balance <= 0 && $lbal <= 0){
                                $continue = false;
                                $msg      = "You have no remaining leave balance..";    
                            }
                        }                 
                    }else{
                        if($lbal <= 0){   
                            $continue = false;
                            $msg      = "You have no remaining leave balance..";
                        }
                    }
                }else    
                    $continue = false;
            }
                
            $query = $this->db->query("SELECT * FROM leave_app WHERE employeeid='$eid' AND status='APPROVED' AND ('{$data['datesetfrom']}' BETWEEN datefrom AND dateto) OR ({$data['datesetto']} BETWEEN datefrom AND dateto)");
            if($query->num_rows() > 0){
                $continue = false;
                $msg = "The date you applied is already approved"; 
            }
            $seq = $this->getAppSequence($data['ltype'], $deptid, $data['ltype']);                                                                                                     
            
            // Vacation, Emergency, Sick & Other Leave
            if(in_array($data['ltype'],array("VL","EL","SL","other")) && $continue){
                if($dltype)
                    $ins   = $this->db->query("INSERT INTO leave_app (employeeid,type,other,othertype,paid,datefrom,dateto,timefrom,timeto,nodays,reason,depthead,hrdir,univphy,clusterhead) VALUES ('{$user}','{$data['ltype']}','{$data['othleave']}','$dltype','{$data['withpay']}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$reason}','{$dhead}','{$hrd}','{$up}','{$chead}')");
                else
                    $ins   = $this->db->query("INSERT INTO leave_app (employeeid,type,other,paid,datefrom,dateto,nodays,reason,depthead,hrdir,univphy,clusterhead) VALUES ('{$user}','{$data['ltype']}','{$data['othleave']}','{$data['withpay']}','{$data['datesetfrom']}','{$data['datesetto']}','{$data['ndays']}','{$reason}','{$dhead}','{$hrd}','{$up}','{$chead}')");
                if($ins){
                    if($dltype) $wC = " AND timefrom='{$tfrom}' AND timeto='{$tto}'";
                    else        $wC = " AND datefrom='{$data['datesetfrom']}' AND dateto='{$data['datesetto']}'";
                    $qid   = $this->db->query("SELECT id FROM leave_app WHERE employeeid='$user' AND type='{$data['ltype']}' $wC  AND nodays='{$data['ndays']}' AND depthead='{$dhead}' AND hrdir='{$hrd}' AND reason='{$reason}'");
                    $aid   = ($qid->num_rows() > 0 ? $qid->row(0)->id : 0);
                        
                    if($dltype)
                        $query = $this->db->query("INSERT INTO ".$seq[1]["tbl"]." (aid,employeeid,head,type,other,othertype,paid,datefrom,dateto,timefrom,timeto,nodays,reason,status) VALUES ('$aid','{$user}','{$seq[1]["head"]}','{$data['ltype']}','{$data['othleave']}','$dltype','{$data['withpay']}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','$reason','PENDING')");
                    else
                        $query = $this->db->query("INSERT INTO ".$seq[1]["tbl"]." (aid,employeeid,head,type,other,paid,datefrom,dateto,nodays,reason,status) VALUES ('$aid','{$user}','{$seq[1]["head"]}','{$data['ltype']}','{$data['othleave']}','{$data['withpay']}','{$data['datesetfrom']}','{$data['datesetto']}','{$data['ndays']}','$reason','PENDING')");
                    if($query)  $return = "Application Sent!.";
                    else        $return = "Ooops, Failed!";
                }
            }else{
                if(!$continue && $msg)  $return = $msg;
                else                    $return = "Leave application is failed!. ";
            }
            
        }else   $return = "Failed to apply leave. Please set your department first.";
        return $return;
    }

    ///< @Angelica - converted for optimization -- refer to (leave_application)
   function applyLeaveWithSequence($data){
        $sched_affected = "";
        $ishalfday = isset($data['ishalfday']) ? 1 : 0;
        if($ishalfday) $sched_affected = implode(',', $data['sched_affected']);

        $deptid = "";
        $return = "";
        $reason = $this->extras->clean($data['reason']);
        $eid    = $this->session->userdata("username");
        $data['datesetfrom']  = isset($data['datesetfrom']) ? $data['datesetfrom'] : "";
        $data['datesetto']    = isset($data['datesetto']) ? $data['datesetto'] : "";
        // $tfrom  = isset($data['tfrom']) ? date("H:i:s",strtotime($data['tfrom'])) : "";
        if(isset($data['tfrom']))
        {
            $tfrom  = $data['tfrom'] == "" ? "00:00:00" : date("H:i:s",strtotime($data['tfrom']));
        }
        else
        {
            $tfrom = "";
        }
        // $tto    = isset($data['tto']) ? date("H:i:s",strtotime($data['tto'])) : "";
        if(isset($data['tfrom']))
        {
            $tto    = $data['tto'] == "" ? "00:00:00" : date("H:i:s",strtotime($data['tto']));
        }
        else
        {
            $tto    = "";
        }
        $dltype = isset($data['dltype']) ? $data['dltype'] : "";
        $user   = $this->session->userdata("username");
        $ltype  = isset($data['ltype']) ? $data['ltype'] : "";

        $continue = true;
        $msg = "";
        if($data['ltype'] != "other"){
            $query  = $this->db->query("SELECT * FROM employee_leave_credit WHERE employeeid='$eid' AND leavetype='{$data['ltype']}' AND ('{$data['datesetfrom']}' BETWEEN dfrom AND dto OR '{$data['datesetto']}' BETWEEN dfrom AND dto)");
            if($query->num_rows() > 0){
                $lbal = $query->row(0)->balance;
                if($data['ltype'] == "VL"){
                    $qbal = $this->db->query("SELECT * FROM employee_leave_credit WHERE employeeid='$eid' AND leavetype='EL' AND ('{$data['datesetfrom']}' BETWEEN dfrom AND dto OR '{$data['datesetto']}' BETWEEN dfrom AND dto)");
                    if($qbal->num_rows() > 0){
                        if($qbal->row(0)->balance <= 0 && $lbal <= 0){
                            $continue = false;
                            $msg      = array("err_code"=>2,"msg"=>"You have no remaining leave balance..");    
                        }
                    }                 
                }else{
                    if($lbal <= 0){   
                        $continue = false;
                        $msg  = array("err_code"=>2,"msg"=>"You have no remaining leave balance..");
                    }
                }
            }else    
                $continue = false;
        }

        if($continue){
            $query = $this->db->query("SELECT * FROM leave_app WHERE employeeid='$eid' AND status='APPROVED' AND ('{$data['datesetfrom']}' BETWEEN datefrom AND dateto) OR ({$data['datesetto']} BETWEEN datefrom AND dateto)");
            if($query->num_rows() > 0){
                $continue = false;
                $msg = array("err_code"=>2,"msg"=>"The date you applied is already approved");
            }
        }//else return $msg;

        if($continue){
            //echo "<pre>". print_r("asda");die;
                
            $dhead = $hrd = $chead = $up = $budgetOff = $financeDir = $president = "";

            $qdept  = $this->db->query("SELECT deptid FROM employee WHERE employeeid='$user'");
            $deptid = ($qdept->num_rows() > 0 ? $qdept->row(0)->deptid : "");
            if($deptid){
                $qdhead = $this->db->query("SELECT head,divisionhead FROM code_office WHERE code='$deptid'");
                $dhead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->head : "");                         // department head
                $chead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->divisionhead : "");                 // Cluster head
            }
            ///< head will look up on head code setup
            $forhead = '';
            if($user==$dhead || $user==$chead) $forhead = 'HEAD';

            #get data from code_request_from for list of approval based on leave type setup
            $leave_setup_data = array();
            if(!$dltype && $ltype!= 'other') $leave_setup_data = $this->db->query("SELECT * FROM code_request_form WHERE code_request='$ltype".$forhead."'")->result_array();
            else        $leave_setup_data = $this->db->query("SELECT * FROM code_request_form WHERE code_request='{$data['othleave']}".$forhead."'")->result_array();

            
            ///< no setup validation
            if(count($leave_setup_data) == 0) return array("err_code"=>2,"msg"=>"No setup for this application.");

            foreach ($leave_setup_data as $key => $setup) {
                if($setup['dhseq'] == "0"){ 
                    $dhead = '';
                }

                if($setup['hhseq'] != "0"){
                    $qhrd   = $this->db->query("SELECT head FROM code_office WHERE code='HR'");
                    $hrd    = ($qhrd->num_rows() > 0 ? $qhrd->row(0)->head : "");                             // HR Director
                } 

                if($setup['chseq'] == "0"){ 
                    $chead = "";
                }

                // get campus principal id
                // author : justin (with e)
                $findCP = $this->db->query("SELECT em.employeeid, cc.campus_principal FROM code_campus AS cc, employee AS em WHERE cc.code = em.`campusid` AND employeeid='{$user}'");
                $cphead = $setup['cpseq'] != 0 ? $findCP->row()->campus_principal : "";
                //echo "<pre>". print_r($cphead);die;
                // end of get campus principal id

                if($setup['upseq'] != "0"){ 
                    $qup    = $this->db->query("SELECT * FROM code_request_form WHERE (univphy<>'' OR univphyt<>'') AND code_request='$ltype'"); // University Physician
                    $up     = ($qup->num_rows() > 0 ? $qup->row(0)->univphy.",".$qup->row(0)->univphyt : "");
                }

                if($setup['boseq'] != "0") $budgetOff      = $setup['budgetoff'];
                if($setup['fdseq'] != "0") $financeDir     = $setup['financedir'];
                if($setup['pseq'] != "0")  $president      = $setup['president'];

                // $seq = $this->getAppSequence($data['ltype'], $deptid, $data['ltype']); 
                #condition for sequence
                $tbl = "";
                $tblhead = "";
                if($setup['dhseq'] == "1"){      $tbl = "leave_app_dhead";        $tblhead = $dhead;
                }elseif($setup['hhseq'] == "1"){  $tbl = "leave_app_hrd";         $tblhead = $hrd;
                }elseif($setup['chseq'] == "1"){  $tbl = "leave_app_chead";       $tblhead = $chead;
                }elseif($setup['cpseq'] == "1"){  $tbl = "leave_app_principal";   $tblhead = $cphead; // for principal
                }elseif($setup['upseq'] == "1"){  $tbl = "leave_app_uphy";        $tblhead = $up;
                }elseif($setup['boseq'] == "1"){  $tbl = "leave_app_budgetoff";   $tblhead = $budgetOff;
                }elseif($setup['fdseq'] == "1"){  $tbl = "leave_app_financedir";  $tblhead = $financeDir;
                }elseif($setup['pseq'] == "1"){   $tbl = "leave_app_president";   $tblhead = $president;}

                // new condition added by justin (with e) for #ica-hyperion 21090
                if($dltype){
                    if($dltype == "NO PUNCH IN/OUT") $data['datesetto'] = $data['datesetfrom'];
                }
                
                // Vacation, Emergency, Sick & Other Leave
                if(in_array($data['ltype'],array("VL","EL","SL","other")) && $continue){
                    if($dltype)
                        $ins   = $this->db->query("
                                INSERT INTO leave_app (employeeid,type,other,othertype,paid,datefrom,dateto,timefrom,timeto,nodays,isHalfDay,sched_affected,reason,depthead,hrdir,univphy,clusterhead,campusprincipal,budgetoff,financedir,president,dhseq,hhseq,chseq,cpseq,upseq,boseq,fdseq,pseq) 
                                VALUES ('{$user}','{$data['ltype']}','{$data['othleave']}','$dltype','{$data['withpay']}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','$ishalfday','$sched_affected','{$reason}','{$dhead}','{$hrd}','{$up}','{$chead}','{$cphead}','{$budgetOff}','{$financeDir}','{$president}','{$setup['dhseq']}','{$setup['hhseq']}','{$setup['chseq']}','{$setup['cpseq']}','{$setup['upseq']}','{$setup['boseq']}','{$setup['fdseq']}','{$setup['pseq']}')
                                ");
                    else
                        $ins   = $this->db->query("
                                INSERT INTO leave_app (employeeid,type,other,paid,datefrom,dateto,nodays,isHalfDay,sched_affected,reason,depthead,hrdir,univphy,clusterhead,campusprincipal,budgetoff,financedir,president,dhseq,hhseq,chseq,cpseq,upseq,boseq,fdseq,pseq) 
                                VALUES ('{$user}','{$data['ltype']}','{$data['othleave']}','{$data['withpay']}','{$data['datesetfrom']}','{$data['datesetto']}','{$data['ndays']}','$ishalfday','$sched_affected','{$reason}','{$dhead}','{$hrd}','{$up}','{$chead}','{$cphead}','{$budgetOff}','{$financeDir}','{$president}','{$setup['dhseq']}','{$setup['hhseq']}','{$setup['chseq']}','{$setup['cpseq']}','{$setup['upseq']}','{$setup['boseq']}','{$setup['fdseq']}','{$setup['pseq']}')
                            ");

                    if($ins){
                        if($dltype) $wC = " AND timefrom='{$tfrom}' AND timeto='{$tto}'";
                        else        $wC = " AND datefrom='{$data['datesetfrom']}' AND dateto='{$data['datesetto']}'";
                        // $qid   = $this->db->query("SELECT id FROM leave_app WHERE employeeid='$user' AND type='{$data['ltype']}' $wC  AND nodays='{$data['ndays']}' AND depthead='{$dhead}' AND hrdir='{$hrd}' AND reason='{$reason}'");
                        // $aid   = ($qid->num_rows() > 0 ? $qid->row(0)->id : 0);
                        $aid = 0;
                        $aid = $this->db->insert_id();

                        if($dltype)
                            $query = $this->db->query("INSERT INTO $tbl (aid,employeeid,head,type,other,othertype,paid,datefrom,dateto,timefrom,timeto,nodays,isHalfDay,sched_affected,reason,status) VALUES ('$aid','{$user}','$tblhead','{$data['ltype']}','{$data['othleave']}','$dltype','{$data['withpay']}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','$ishalfday','$sched_affected','$reason','PENDING')");
                        else
                            $query = $this->db->query("INSERT INTO $tbl (aid,employeeid,head,type,other,paid,datefrom,dateto,nodays,isHalfDay,sched_affected,reason,status) 
                                                        VALUES ('$aid','{$user}','$tblhead','{$data['ltype']}','{$data['othleave']}','{$data['withpay']}','{$data['datesetfrom']}','{$data['datesetto']}','{$data['ndays']}','$ishalfday','$sched_affected','$reason','PENDING')");


                        // if($deptid == 'HR'){
                            // $last_id = $this->db->insert_id();
                            // $arr_nexttable = array('id'=>$last_id,'aid'=>$aid,'status'=>'APPROVED','dept'=>$deptid,'eddept'=>$deptid,'eid'=>$eid,'ltype'=>$ltype,'dltype'=>$dltype,'othleave'=>$data['othleave'],'hrhead'=>$tblhead);
                            // $this->leave_approve_head($arr_nexttable);
                        // };

                        // wait for it (justin with e)
                        if($forhead && $tblhead==$eid){
                            $last_id = $this->db->insert_id();
                            $arr_nexttable = array('id'=>$last_id,'aid'=>$aid,'status'=>'APPROVED','dept'=>$deptid,'eddept'=>$deptid,'eid'=>$eid,'ltype'=>$ltype,'dltype'=>$dltype,'othleave'=>$data['othleave'],'hrhead'=>$tblhead);
                            $this->leave_approve_head($arr_nexttable);
                        };



                        if($query)  $return = array("err_code"=>0,"msg"=>"Application Sent!.","base_id"=>$aid);
                        else        $return = array("err_code"=>2,"msg"=>"Ooops, Failed!");
                        
                        return $return;
                    }
                }else{
                    if(!$continue && $msg)  $return = $msg;
                    else                    $return = array("err_code"=>2,"msg"=>"Leave application is failed!. ");
                    

                    return $return;
                }
            }
        }else{
            if($msg) $return = $msg;
            else $return = array("err_code"=>2,"msg"=>"Failed to apply leave. Please set your department first.");
            

            ///echo "<pre>". var_dump($return);die;
            return $return;
        }
        return $return;
   }

    ///< @Angelica - converted for optimization -- refer to (leave_application)
    function leave_approve_head($data){
        $return = $msg = $query = "";
        if($data['id']){
            $id     = $data['id'];
            $ndays  = isset($data['ndays'])?$data['ndays']:"";
            $aid    = $data['aid'];
            $status = $data['status'];
            $dept   = $data['dept'];
            $edept  = $data['eddept'];
            $eid    = $data['eid'];
            $ltype  = $data['ltype'];
            $dltype  = isset($data['dltype'])?$data['dltype']:"";
            $othleave  = isset($data['othleave'])?$data['othleave']:"";
            $cdate  = date("Y-m-d");
            $remarks = isset($data['remarks'])?$data['remarks']:"";

            $user = isset($data['hrhead'])?$data['hrhead']:$this->session->userdata("username");

            $qdept  = $this->db->query("SELECT deptid FROM employee WHERE employeeid='$eid'");
            $deptid = ($qdept->num_rows() > 0 ? $qdept->row(0)->deptid : "");
            if($deptid){
                $qdhead = $this->db->query("SELECT head,divisionhead FROM code_office WHERE code='$deptid'");
                $dhead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->head : "");                         // department head
                $chead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->divisionhead : "");                 // Cluster head
            }
            $forhead = '';
            if($eid==$dhead || $eid==$chead) $forhead = 'HEAD';
            
            $seq = array();
            // gamitin yung eid.
            if(!$dltype && $ltype!= 'other') $seq = $this->getAppSequence($ltype.$forhead,$edept,$ltype.$forhead,$eid);
            else        $seq = $this->getAppSequence($othleave.$forhead,$edept,$othleave.$forhead,$eid);
            
            $seqnum = 1;
            $tbl = "";
            for($seqno = $seqnum; $seqno< count($seq); $seqno++){
                
                if(in_array($user, explode(",",$seq[$seqno]["head"]))){
                    $tbl                = $seq[$seqno]["tbl"];
                    $head_col_status    = ($seq[$seqno]["type"] == "dh" ? "deptheadstatus" : 
                                            ($seq[$seqno]["type"] == "hh" ? "hrdirstatus" : 
                                            ($seq[$seqno]["type"] == "ch" ? "clusterheadstatus" : 
                                            ($seq[$seqno]["type"] == "cp" ? "campusprincipalstatus" : 
                                            ($seq[$seqno]["type"] == "up" ? "univphystatus" :
                                            ($seq[$seqno]["type"] == "bo" ? "budgetoffstatus" :
                                            ($seq[$seqno]["type"] == "fd" ? "financedirstatus" :
                                            ($seq[$seqno]["type"] == "pt" ? "presidentstatus" :
                                             ""))))))));
                    $head_col_date      = ($seq[$seqno]["type"] == "dh" ? "deptheaddate" : 
                                            ($seq[$seqno]["type"] == "hh" ? "hrdirdate" : 
                                            ($seq[$seqno]["type"] == "ch" ? "clusterheaddate" :
                                            ($seq[$seqno]["type"] == "cp" ? "campusprincipaldate" :
                                            ($seq[$seqno]["type"] == "up" ? "univphydate" : 
                                            ($seq[$seqno]["type"] == "bo" ? "budgetoffdate" :
                                            ($seq[$seqno]["type"] == "fd" ? "financedirdate" :
                                            ($seq[$seqno]["type"] == "pt" ? "presidentdate" :
                                            ""))))))));
                    if($seqno <= $seqnum)
                    {
                        $seqnum += 1;
                    }
                    else
                    {
                        $seqnum = $seqno + 1;
                        
                    }
                }
                if($this->db->query("SELECT * from $tbl WHERE employeeid='{$eid}' AND aid='{$aid}' AND id='{$id}' AND status='PENDING' ")->num_rows() > 0)
                {
                    break;
                }
            } 
            //echo "$head_col_status, $head_col_date";die;
            // Vacation, Emergency, Sick & Other Leave
            // echo $seqnum. ' - '. count($seq).'///';
            // echo $tbl.' - ' . $id.'<br>';
            // echo $seq[$seqnum]["tbl"];
            if(in_array($ltype,array("VL","EL","SL","other"))){
                if($seqnum == count($seq)){
                    if($status == "APPROVED"){
                        // echo '  sdito';
                        $this->db->query("INSERT INTO leave_request (aid,employeeid,leavetype,other,othertype,paid,fromdate,todate,timefrom,timeto,no_days,remarks,status,dateapplied,dateapproved)
                                            (SELECT aid,employeeid,type,other,othertype,paid,datefrom,dateto,timefrom,timeto,nodays,reason,'$status',DATE(timestamp),DATE(NOW()) FROM $tbl WHERE id='$id')  
                                         ");


                        //< for halfday
                        $ishalfday = 0;
                        $sched_affected = array();
                        $sched_affected_string = '';
                        $datefrom = '';

                        $sched_affected_q = $this->db->query("SELECT datefrom, isHalfDay, sched_affected FROM $tbl WHERE id='$id'");
                        if($sched_affected_q->num_rows() > 0){
                            $ishalfday = $sched_affected_q->row(0)->isHalfDay;
                            $sched_affected_string = $sched_affected_q->row(0)->sched_affected;
                            $datefrom = $sched_affected_q->row(0)->datefrom;
                        }
                        if($ishalfday && $sched_affected_string && $datefrom) $sched_affected = explode(',', $sched_affected_string);
                        ///< end for halfday



                        if($ltype != "other"){
                            $query  = $this->db->query("SELECT * FROM employee_leave_credit WHERE employeeid='$eid' AND leavetype='$ltype' AND CURRENT_DATE BETWEEN dfrom AND dto");
                            if($query->num_rows() > 0){
                                $bal = $query->row(0)->balance;
                                if($ltype == "VL"){
                                    if($query->row(0)->balance > $ndays  && $query->row(0) >= $ndays){
                                        $this->db->query("UPDATE employee_leave_credit SET avail='".($query->row(0)->avail+$ndays)."', balance='".($query->row(0)->balance-$ndays)."' WHERE employeeid='$eid' AND leavetype='$ltype' AND CURRENT_DATE BETWEEN dfrom AND dto");
                                        ///< insert to timesheet

                                        $this->insertToTimesheetFromLeave($eid,$sched_affected,$datefrom,$ltype);

                                    }else{
                                        $avail = ($query->row(0)->avail+$ndays);
                                        $query  = $this->db->query("SELECT * FROM employee_leave_credit WHERE employeeid='$eid' AND leavetype='EL' AND CURRENT_DATE BETWEEN dfrom AND dto");
                                        if($query->num_rows() > 0){
                                            if($query->row(0)->balance > 0 && $query->row(0) >= $ndays){
                                                $this->db->query("UPDATE employee_leave_credit SET avail='$avail' WHERE employeeid='$eid' AND leavetype='$ltype' AND CURRENT_DATE BETWEEN dfrom AND dto");
                                                $this->db->query("UPDATE employee_leave_credit SET balance='".($query->row(0)->balance-$ndays)."' WHERE employeeid='$eid' AND leavetype='EL' AND CURRENT_DATE BETWEEN dfrom AND dto");
                                                ///< insert to timesheet

                                                $this->insertToTimesheetFromLeave($eid,$sched_affected,$datefrom,$ltype);
                                            }else{
                                                $msg = "This employee have no remaining balance";
                                            }
                                        }
                                    }
                                }else{
                                    if($bal > 0 && $bal >= $ndays){
                                        $this->db->query("UPDATE employee_leave_credit SET avail='".($query->row(0)->avail+$ndays)."', balance='".($query->row(0)->balance-$ndays)."' WHERE employeeid='$eid' AND leavetype='$ltype' AND CURRENT_DATE BETWEEN dfrom AND dto");
                                        ///< insert to timesheet

                                        $this->insertToTimesheetFromLeave($eid,$sched_affected,$datefrom,$ltype);
                                    }else        $msg = "This employee have no remaining balance";
                                }                          
                            }                                              
                        }
                    }
                    if(!$msg){
                        $isread = 0;
                        if($status == 'PENDING') $isread = 1;
                                    $this->db->query("UPDATE $tbl SET status='$status', dateapproved='$cdate', remarks='$remarks' WHERE id='$id'");  
                        $query  =   $this->db->query("UPDATE leave_app SET status='$status', $head_col_status='$status', $head_col_date='$cdate', isread=$isread, remarks='$remarks' WHERE id='$aid'");
                        
                    }
                }else{
                    //echo "<pre>". var_dump($seq);die;
                    if($status == "APPROVED"){
                        // $qhrd   = $this->db->query("SELECT head FROM code_office WHERE code='HR'");
                        // $hrd    = ($qhrd->num_rows() > 0 ? $qhrd->row(0)->head : "");                         // department head
                        // $seqnum+=1;
                        $this->db->query("INSERT INTO ".$seq[$seqnum]["tbl"]." (aid,employeeid,head,type,other,othertype,paid,datefrom,dateto,timefrom,timeto,nodays,reason,status,timestamp)
                                        (SELECT aid,employeeid,'{$seq[$seqnum]["head"]}',type,other,othertype,paid,datefrom,dateto,timefrom,timeto,nodays,reason,status,timestamp FROM $tbl WHERE id='$id')  
                                        ");
                    }
                        $this->db->query("UPDATE $tbl SET status='$status', dateapproved='$cdate', remarks='$remarks' WHERE id='$id'");
                    if($status == "DISAPPROVED"){
                        $query  = $this->db->query("UPDATE leave_app SET status='$status', $head_col_status='$status', $head_col_date='$cdate', isread=0, remarks='$remarks' WHERE id='$aid'");
                    }else
                        $query  = $this->db->query("UPDATE leave_app SET $head_col_status='$status', $head_col_date='$cdate', remarks='$remarks' WHERE id='$aid'");
                }
            }
            if($query)  $return = "Success! Status now is : $status"; 
            else        $return = "Failed!.";
            if($msg)    $return = $msg;
        }
        return $return;
    }


    function insertToTimesheetFromLeave($employeeid='',$sched='',$dfrom='',$ltype=''){
        ///< sample laman ng sched_affected
          /*array(2) {
            [0]=>
            string(17) "08:00:00|12:00:00"
            [1]=>
            string(17) "13:00:00|17:00:00"
          }*/

          $ltypedesc = 'LEAVE';

          if($ltype=='VL') $ltypedesc = 'VACATION';
          if($ltype=='SL') $ltypedesc = 'SICK';
          if($ltype=='EL') $ltypedesc = 'EMERGENCY';

        if(count($sched) > 0){
            foreach ($sched as $row) {
                $time = explode('|', $row);
                if(isset($time[0]) && isset($time[1]) && $dfrom){
                    $timein = $dfrom . ' ' . $time[0];
                    $timeout = $dfrom . ' ' . $time[1];
                    $this->db->query("INSERT INTO timesheet (userid,timein,timeout,otype) VALUES ('$employeeid','$timein','$timeout','$ltypedesc')");
                }
            }
            
        }
    }


    function modifyLeave($data){
        $return = $msg = "";
        $continue = true;
        $eid     = $data['eid'];
        if($data['ltype'] != "other"){
            $query  = $this->db->query("SELECT * FROM employee_leave_credit WHERE employeeid='$eid' AND leavetype='{$data['ltype']}' AND ('{$data['datesetfrom']}' BETWEEN dfrom AND dto OR '{$data['datesetto']}' BETWEEN dfrom AND dto)");
            if($query->num_rows() > 0){  
                $lbal = $query->row(0)->balance;
                if($data['ltype'] == "VL"){
                    $qbal = $this->db->query("SELECT * FROM employee_leave_credit WHERE employeeid='$eid' AND leavetype='EL' AND ('{$data['datesetfrom']}' BETWEEN dfrom AND dto OR '{$data['datesetto']}' BETWEEN dfrom AND dto)");
                    if($qbal->num_rows() > 0){
                        if($qbal->row(0)->balance <= 0 && $lbal <= 0){
                            $continue = false;
                            $msg      = "You have no remaining leave balance..";    
                        }
                    }                 
                }else{
                    if($lbal <= 0){   
                        $continue = false;
                        $msg      = "You have no remaining leave balance..";
                    }
                }
            }else{
                $continue = false;
                $msg = "You have no credit for this type of leave.";
            }
        }
        $query = $this->db->query("SELECT * FROM leave_app WHERE employeeid='$eid' AND status='APPROVED' AND ('{$data['datesetfrom']}' BETWEEN datefrom AND dateto) OR ({$data['datesetto']} BETWEEN datefrom AND dateto)");
        if($query->num_rows() > 0){
            $continue = false;
            $msg = "The date you applied is already approved"; 
        }
        if($continue){
            $query = $this->db->query("SELECT * FROM leave_app WHERE (deptheadstatus='APPROVED' OR deptheadstatus='DISAPPROVED') WHERE id='{$data['id']}'");
            if($query->num_rows() > 0){
                $return = "Failed to update!. The request has already ".$query->row()->deptheadstatus;
            }else{
                $query = $this->db->query("UPDATE leave_app       SET type='{$data['ltype']}', other='{$data['othleave']}', paid='{$data['withpay']}', datefrom='{$data['datesetfrom']}', dateto='{$data['datesetto']}', nodays='{$data['ndays']}', reason='{$data['reason']}' WHERE id='{$data['id']}'");        
                         $this->db->query("UPDATE leave_app_dhead SET type='{$data['ltype']}', other='{$data['othleave']}', paid='{$data['withpay']}', datefrom='{$data['datesetfrom']}', dateto='{$data['datesetto']}', nodays='{$data['ndays']}', reason='{$data['reason']}' WHERE aid='{$data['id']}'");
                         $this->db->query("UPDATE leave_app_chead SET type='{$data['ltype']}', other='{$data['othleave']}', paid='{$data['withpay']}', datefrom='{$data['datesetfrom']}', dateto='{$data['datesetto']}', nodays='{$data['ndays']}', reason='{$data['reason']}' WHERE aid='{$data['id']}'");
                         $this->db->query("UPDATE leave_app_uphy  SET type='{$data['ltype']}', other='{$data['othleave']}', paid='{$data['withpay']}', datefrom='{$data['datesetfrom']}', dateto='{$data['datesetto']}', nodays='{$data['ndays']}', reason='{$data['reason']}' WHERE aid='{$data['id']}'");
                         $this->db->query("UPDATE leave_app_hrd   SET type='{$data['ltype']}', other='{$data['othleave']}', paid='{$data['withpay']}', datefrom='{$data['datesetfrom']}', dateto='{$data['datesetto']}', nodays='{$data['ndays']}', reason='{$data['reason']}' WHERE aid='{$data['id']}'");
                $return = "Successfully Saved!";
            }
        }else{
            $return = $msg;
        }
        return $return;
    }
    function delLeave($data){
        $return = "";
        $query = $this->db->query("SELECT * FROM leave_app WHERE (deptheadstatus='APPROVED' OR deptheadstatus='DISAPPROVED') WHERE id='{$data['id']}'");
        if($query->num_rows() > 0){
            $return = "Failed to delete!. The request has already ".$query->row()->deptheadstatus;
        }else{
            $this->db->query("DELETE FROM leave_app       WHERE id ='{$data['id']}'");
            $this->db->query("DELETE FROM leave_app_dhead WHERE aid='{$data['id']}'");
            $this->db->query("DELETE FROM leave_app_chead WHERE aid='{$data['id']}'");
            $this->db->query("DELETE FROM leave_app_uphy  WHERE aid='{$data['id']}'");
            $this->db->query("DELETE FROM leave_app_hrd   WHERE aid='{$data['id']}'");
            $return = "Succesfully Deleted!.";
        }
        return $return;    
    }
    function leave_modify_query($id=""){
        $query = $this->db->query("SELECT * FROM leave_app WHERE id='$id'");
        return $query;
    }
    function applySeminar($data){
        $return = "";
        if(isset($data['employeeid']))
            $user   = $data['employeeid'];
        else
            $user   = $this->session->userdata("username"); 
        $tfrom  = date("H:i:s",strtotime($data['tfrom']));
        $tto    = date("H:i:s",strtotime($data['tto']));
        $poa    = $this->extras->clean($data['poa']);
        $course = $this->extras->clean($data['course']);
        $venue  = $this->extras->clean($data['venue']);
        $speaker= $this->extras->clean($data['speaker']);
        $misc   = $this->extras->clean($data['miscellaneous']);
        $soc    = $this->extras->clean($data['soc']);
        
        $qdept  = $this->db->query("SELECT deptid FROM employee WHERE employeeid='$user'");
        $deptid = ($qdept->num_rows() > 0 ? $qdept->row(0)->deptid : "");
        
        if($deptid){
            $qdhead = $this->db->query("SELECT head,divisionhead FROM code_office WHERE code='$deptid'");
            $dhead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->head : "");                         // department head
            $chead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->divisionhead : "");                 // Cluster head
            $qhrd   = $this->db->query("SELECT head FROM code_office WHERE code='HR'");
            $hrd    = ($qhrd->num_rows() > 0 ? $qhrd->row(0)->head : "");                             // HR Director
            $qs     = $this->db->query("SELECT * FROM code_request_form WHERE ismain='3'");
            $boff   = ($qs->num_rows() > 0 ? $qs->row(0)->budgetoff : "");                             // Budget Officer
            $fdir   = ($qs->num_rows() > 0 ? $qs->row(0)->financedir : "");                            // Finance Director
            $pres   = ($qs->num_rows() > 0 ? $qs->row(0)->president : "");                             // President
                        
            if($user == $dhead){    // Dean/Head Seminar Application
                if(!empty($chead)){
                    $eid = $data['eid'];
                    $qbid   = $this->db->query("SELECT baseid FROM based_id_offbus");
                    $bid    = $qbid->row(0)->baseid;
                              $this->db->query("UPDATE based_id_offbus SET baseid='".($bid+1)."'");
                    foreach(explode(",",$eid) as $empid){
                        #if($empid != $user) $isread = 0;
                        #else                
                        $isread = 1;                        
                        $ins   = $this->db->query("INSERT INTO seminar_app (employeeid,base_id,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,depthead,deptheadstatus,deptheaddate,hrdir,cluster,budgetoff,financedir,president,isread) VALUES ('{$empid}','{$bid}','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['meal']}','{$data['transpo']}','{$data['hotel']}','{$data['tc']}','{$venue}','{$speaker}','{$misc}','{$soc}','{$dhead}','APPROVED',CURRENT_DATE,'{$hrd}','{$chead}','{$boff}','{$fdir}','{$pres}','{$isread}')");
                        if($ins){
                            $qid   = $this->db->query("SELECT id FROM seminar_app WHERE employeeid='$empid' AND dfrom='{$data['datesetfrom']}' AND dto='{$data['datesetto']}' AND tstart='{$tfrom}' AND tend='{$tto}' AND nodays='{$data['ndays']}' AND purpose='{$poa}' AND course='{$course}' AND venue='{$venue}' AND statement='{$soc}'");
                            $aid   = ($qid->num_rows() > 0 ? $qid->row(0)->id : 0);
                                    
                            $query = $this->db->query("INSERT INTO seminar_app_dhead (aid,base_id,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,status,dateapproved,dateapplied) VALUES ('$aid','{$bid}','{$empid}','{$dhead}','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['meal']}','{$data['transpo']}','{$data['hotel']}','{$data['tc']}','{$venue}','{$speaker}','{$misc}','{$soc}','APPROVED',NOW(),NOW())");
                            $query = $this->db->query("INSERT INTO seminar_app_hrd (aid,base_id,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied) VALUES ('$aid','{$bid}','{$empid}','{$hrd}','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['meal']}','{$data['transpo']}','{$data['hotel']}','{$data['tc']}','{$venue}','{$speaker}','{$misc}','{$soc}',NOW())");                                                                                    
                        }
                    }
                    if($query)  $return = "Application Sent!.";
                    else        $return = "Ooops, Failed!";
                }else   $return = "Ooops, Failed! Your department has no cluster head.";
            }else if($user == $hrd){    // HR Head Seminar Application
                if(!empty($chead)){
                $ins   = $this->db->query("INSERT INTO seminar_app (employeeid,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,depthead,hrdir,cluster,budgetoff,financedir,president) VALUES ('{$user}','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['meal']}','{$data['transpo']}','{$data['hotel']}','{$data['tc']}','{$venue}','{$speaker}','{$misc}','{$soc}','{$dhead}','{$hrd}','{$chead}','{$boff}','{$fdir}','{$pres}')");
                    if($ins){
                        $qid   = $this->db->query("SELECT id FROM seminar_app WHERE employeeid='$user' AND dfrom='{$data['datesetfrom']}' AND dto='{$data['datesetto']}' AND tstart='{$tfrom}' AND tend='{$tto}' AND nodays='{$data['ndays']}' AND purpose='{$poa}' AND course='{$course}' AND venue='{$venue}' AND statement='{$soc}'");
                        $aid   = ($qid->num_rows() > 0 ? $qid->row(0)->id : 0);
                                
                        $query = $this->db->query("INSERT INTO seminar_app_chead (aid,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied) VALUES ('$aid','{$user}','{$dhead}','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['meal']}','{$data['transpo']}','{$data['hotel']}','{$data['tc']}','{$venue}','{$speaker}','{$misc}','{$soc}',NOW())");
                        if($query)  $return = "Application Sent!.";
                        else        $return = "Ooops, Failed!";
                    }
                }else   $return = "Ooops, Failed! Your department has no cluster head.";
            }else if($user == $chead){      // Cluster Head Seminar Application
                $ins   = $this->db->query("INSERT INTO seminar_app (employeeid,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,depthead,hrdir,cluster,budgetoff,financedir,president) VALUES ('{$user}','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['meal']}','{$data['transpo']}','{$data['hotel']}','{$data['tc']}','{$venue}','{$speaker}','{$misc}','{$soc}','{$dhead}','{$hrd}','{$chead}','{$boff}','{$fdir}','{$pres}')");
                if($ins){
                    $qid   = $this->db->query("SELECT id FROM seminar_app WHERE employeeid='$user' AND dfrom='{$data['datesetfrom']}' AND dto='{$data['datesetto']}' AND tstart='{$tfrom}' AND tend='{$tto}' AND nodays='{$data['ndays']}' AND purpose='{$poa}' AND course='{$course}' AND venue='{$venue}' AND statement='{$soc}'");
                    $aid   = ($qid->num_rows() > 0 ? $qid->row(0)->id : 0);
                        
                    $query = $this->db->query("INSERT INTO seminar_app_budgetoff (aid,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied) VALUES ('$aid','{$user}','{$dhead}','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['meal']}','{$data['transpo']}','{$data['hotel']}','{$data['tc']}','{$venue}','{$speaker}','{$misc}','{$soc}',NOW())");
                    if($query)  $return = "Application Sent!.";
                    else        $return = "Ooops, Failed!";
                }   
            }else{
                $ins   = $this->db->query("INSERT INTO seminar_app (employeeid,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,depthead,deptheadstatus,deptheaddate,hrdir,cluster,budgetoff,financedir,president) VALUES ('{$user}','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['meal']}','{$data['transpo']}','{$data['hotel']}','{$data['tc']}','{$venue}','{$speaker}','{$misc}','{$soc}','{$dhead}','APPROVED',CURRENT_DATE,'{$hrd}','{$chead}','{$boff}','{$fdir}','{$pres}')");
                if($ins){
                    $qid   = $this->db->query("SELECT id FROM seminar_app WHERE employeeid='$user' AND dfrom='{$data['datesetfrom']}' AND dto='{$data['datesetto']}' AND tstart='{$tfrom}' AND tend='{$tto}' AND nodays='{$data['ndays']}' AND purpose='{$poa}' AND course='{$course}' AND venue='{$venue}' AND statement='{$soc}'");
                    $aid   = ($qid->num_rows() > 0 ? $qid->row(0)->id : 0);
                        
                    $query = $this->db->query("INSERT INTO seminar_app_dhead (aid,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,status,dateapproved,dateapplied) VALUES ('$aid','{$user}','{$dhead}','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['meal']}','{$data['transpo']}','{$data['hotel']}','{$data['tc']}','{$venue}','{$speaker}','{$misc}','{$soc}','APPROVED',CURRENT_DATE,NOW())");
                    $query = $this->db->query("INSERT INTO seminar_app_hrd (aid,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied) VALUES ('$aid','{$user}','{$hrd}','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['meal']}','{$data['transpo']}','{$data['hotel']}','{$data['tc']}','{$venue}','{$speaker}','{$misc}','{$soc}',NOW())");
                    if($query)  $return = "Application Sent!.";
                    else        $return = "Ooops, Failed!";
                }
            }
        }
        return $return;
    }
    # new seminar sequence for ica-hyperion 21128
    # by justin (with e)
    function applySeminarWithSequenceNew($data){
        if(isset($data['employeeid']))
             $user   = $data['employeeid'];
        else
             $user   = $this->session->userdata("username"); 

        $tfrom  = date("H:i:s",strtotime($data['tfrom']));
        $tto    = date("H:i:s",strtotime($data['tto']));
        $poa    = $this->extras->clean($data['poa']);
        $course = $this->extras->clean($data['course']);
        $venue  = $this->extras->clean($data['venue']);
        $speaker= $this->extras->clean($data['speaker']);
        $misc   = $this->extras->clean($data['miscellaneous']);
        $soc    = $this->extras->clean($data['soc']);

        $eid = $data['eid'];
        foreach (explode(",", $eid) as $empId) {
            $obs_setup = $isHead = "";

            $query = $this->db->query("SELECT * FROM code_office WHERE head = '$empId' OR divisionhead = '$empId'")->result();
            if(count($query) > 0) $isHead = "HEAD";
            else{
                $query1 = $this->db->query("SELECT * FROM employee WHERE employeeid = '$empId' AND teachingtype = 'TEACHING'")->result();
                if(count($query1) == 0) $isHead = "NON";
            }

            $obs_setup = $this->db->query("SELECT * FROM code_request_form WHERE code_request='OBS".$isHead."'")->result();
            foreach ($obs_setup as $os) {
                $dhead = $chead = $hrhead = $cphead = $fdhead = $bohead = $phead = $uphead = $uphead1 = "";
                $tbl = array(
                                'seminar_app_dhead' => $os->dhseq,
                                'seminar_app_chead' => $os->chseq,
                                'seminar_app_hrd' => $os->hhseq,
                                'seminar_principal' => $os->cpseq,
                                'seminar_app_findir' => $os->fdseq,
                                'seminar_app_budgetoff' => $os->boseq,
                                'seminar_app_president' => $os->pseq,
                                'seminar_app_uphy' => $os->upseq
                            ); 
                # dhead
                if(!$os->dhseq || $os->dhseq > 0) 
                    $dhead = $this->db->query("SELECT a.`head`, a.`divisionhead` FROM code_office a, employee b WHERE a.`code` = b.`deptid` AND b.`employeeid` = '$empId'")->row()->head;
                # chead
                if(!$os->chseq || $os->chseq > 0) 
                    $chead = $this->db->query("SELECT a.`head`, a.`divisionhead` FROM code_office a, employee b WHERE a.`code` = b.`deptid` AND b.`employeeid` = '$empId'")->row()->divisionhead;
                # hrhead
                if(!$os->hhseq || $os->hhseq > 0) 
                    $hrhead = $this->db->query("SELECT a.`head`, a.`divisionhead` FROM code_office a WHERE a.`code`='HR'")->row()->head;
                # cp
                if(!$os->cpseq || $os->cpseq > 0) 
                    # check if teching
                    $cphead = "";
                    $fndCPHead = $this->db->query("SELECT a.`campus_principal` FROM code_campus a, employee b WHERE a.`code` = b.`campusid` AND b.`employeeid` = '$empId'");
                    if($fndCPHead->num_rows() > 0) $cphead = $fndCPHead->row()->campus_principal; # new condition for ica-hyperion 21152
                # fd
                if(!$os->fdseq || $os->fdseq > 0)
                    $fdhead = $os->financedir;
                # bo
                if(!$os->boseq || $os->boseq > 0)
                    $bohead = $os->budgetoff;
                # pres
                if(!$os->pseq || $os->pseq > 0)
                    $phead = $os->budgetoff;
                # up
                if(!$os->upseq || $os->upseq > 0){
                    $uphead = $os->univphy;
                    $uphead1 = $os->univphyt;
                }


                
                # save in seminar app
                $qbid   = $this->db->query("SELECT baseid FROM based_id_offbus");
                $bid    = $qbid->row(0)->baseid;
                $this->db->query("UPDATE based_id_offbus SET baseid='".($bid+1)."'");

                $isread = 1;                        
                $ins   = $this->db->query("INSERT INTO seminar_app 
                                            (employeeid, base_id,purpose, course, dfrom, dto, tstart, tend, nodays, paiddays, coursefee, coursefee_approved, meal, meal_approved, transportation, transportation_approved, hotel,hotel_approved, othermiscellaneous, othermiscellaneous_approved, totalcost, totalcost_approved, venue, speaker, miscellaneous, statement, depthead, hrdir, cluster, budgetoff, financedir, president, dhseq, hhseq, chseq, upseq, boseq, fdseq, pseq, cphead, cpseq, isread) 
                                           VALUES 
                                            ('{$empId}', '{$bid}', '{$poa}', '{$course}', '{$data['datesetfrom']}', '{$data['datesetto']}', '{$tfrom}', '{$tto}', '{$data['ndays']}', '{$data['pwd']}', '{$data['cfee']}', '{$data['cfeeApproved']}', '{$data['meal']}', '{$data['mealApproved']}', '{$data['transpo']}', '{$data['transpoApproved']}', '{$data['hotel']}', '{$data['hotelApproved']}', '{$data['othermiscellaneous']}', '{$data['othermiscellaneousApproved']}', '{$data['tc']}', '{$data['tcApproved']}', '{$venue}', '{$speaker}', '{$misc}', '{$soc}', '{$dhead}' , '{$hrhead}' , '{$chead}', '{$bohead}', '{$fdhead}', '{$phead}', '{$os->dhseq}', '{$os->hhseq}', '{$os->chseq}', '{$os->upseq}', '{$os->boseq}', '{$os->fdseq}', '{$os->pseq}', '{$cphead}', '{$os->cpseq}', '{$isread}')");

                # save to approver table
                $tblSel = array_search(1, $tbl);
                $headSel = array(
                                'seminar_app_dhead' => $dhead,
                                'seminar_app_chead' => $chead,
                                'seminar_app_hrd' => $hrhead,
                                'seminar_principal' => $cphead,
                                'seminar_app_findir' => $fdhead,
                                'seminar_app_budgetoff' => $bohead,
                                'seminar_app_president' => $phead,
                                'seminar_app_uphy' => $uphead .",". $uphead1
                               );
                
                $qid   = $this->db->query("SELECT id FROM seminar_app WHERE employeeid='$empId' AND dfrom='{$data['datesetfrom']}' AND dto='{$data['datesetto']}' AND tstart='{$tfrom}' AND tend='{$tto}' AND nodays='{$data['ndays']}' AND purpose='{$poa}' AND course='{$course}' AND venue='{$venue}' AND statement='{$soc}'");
                $aid   = ($qid->num_rows() > 0 ? $qid->row(0)->id : 0);

                $tblhead = $headSel[$tblSel];
                $query = $this->db->query("INSERT INTO $tblSel 
                                            (aid , base_id, employeeid, head, purpose, course, dfrom, dto, tstart, tend, nodays, paiddays, coursefee, coursefee_approved, meal, meal_approved, transportation, transportation_approved , hotel, hotel_approved, othermiscellaneous, othermiscellaneous_approved, totalcost, totalcost_approved, venue, speaker, miscellaneous, statement, dateapplied) 
                                           VALUES ('$aid', '{$bid}', '{$empId}', '$tblhead', '{$poa}', '{$course}', '{$data['datesetfrom']}', '{$data['datesetto']}', '{$tfrom}','{$tto}', '{$data['ndays']}', '{$data['pwd']}', '{$data['cfee']}', '{$data['cfeeApproved']}', '{$data['meal']}', '{$data['mealApproved']}', '{$data['transpo']}', '{$data['transpoApproved']}', '{$data['hotel']}', '{$data['hotelApproved']}', '{$data['othermiscellaneous']}', '{$data['othermiscellaneousApproved']}', '{$data['tc']}', '{$data['tcApproved']}', '{$venue}', '{$speaker}', '{$misc}', '{$soc}', NOW())");
                
                echo "Application Sent!";
                
            }

        }

    }
    # end of seminar sequence
    function applySeminarWithSequence($data){
        $return = array("err_code"=>2,"msg"=>"Ooops, Failed!","base_id"=>"");
        $bid = "";
		

        if(isset($data['employeeid']))
             $user   = $data['employeeid'];
        else
             $user   = $this->session->userdata("username"); 

        $tfrom  = date("H:i:s",strtotime($data['tfrom']));
        $tto    = date("H:i:s",strtotime($data['tto']));
        $poa    = $this->extras->clean($data['poa']);
        $course = $this->extras->clean($data['course']);
        $venue  = $this->extras->clean($data['venue']);
        $speaker= $this->extras->clean($data['speaker']);
        $misc   = $this->extras->clean($data['miscellaneous']);
        $soc    = $this->extras->clean($data['soc']);

        $qdept  = $this->db->query("SELECT deptid FROM employee WHERE employeeid='$user'");
        $deptid = ($qdept->num_rows() > 0 ? $qdept->row(0)->deptid : "");

        $dhead = $hrd = $chead = $up = $budgetOff = $financeDir = $president = $cheadtest = $dheadtest = "";
        $leave_setup_data = $this->db->query("SELECT * FROM code_request_form WHERE code_request='OBS'")->result_array();

        $qdept  = $this->db->query("SELECT deptid FROM employee WHERE employeeid='$user'");
        $deptid = ($qdept->num_rows() > 0 ? $qdept->row(0)->deptid : "");
        if($deptid){
            $qdhead = $this->db->query("SELECT head,divisionhead FROM code_office WHERE code='$deptid'");
            $dheadtest = $dhead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->head : "");                         // department head
            $cheadtest = $chead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->divisionhead : "");                 // Cluster head
        }

        if($deptid){
			
            foreach ($leave_setup_data as $key => $setup) {
                if($setup['dhseq'] == "0"){ 
                    $dhead = '';
                }

                if($setup['hhseq'] != "0"){
                    $qhrd   = $this->db->query("SELECT head FROM code_office WHERE code='HR'");
                    $hrd    = ($qhrd->num_rows() > 0 ? $qhrd->row(0)->head : "");                             // HR Director
                } 

                if($setup['chseq'] == "0"){ 
                    $chead = "";
                }

                if($setup['upseq'] != "0"){ 
                    $qup    = $this->db->query("SELECT * FROM code_request_form WHERE (univphy<>'' OR univphyt<>'') AND code_request='$ltype'"); // University Physician
                    $up     = ($qup->num_rows() > 0 ? $qup->row(0)->univphy.",".$qup->row(0)->univphyt : "");
                }

                if($setup['boseq'] != "0") $budgetOff      = $setup['budgetoff'];
                if($setup['fdseq'] != "0") $financeDir     = $setup['financedir'];
                if($setup['pseq'] != "0")  $president      = $setup['president'];

                // $seq = $this->getAppSequence($data['ltype'], $deptid, $data['ltype']); 
                #condition for sequence
                $tbl = "";
                $tblhead = "";
                if($setup['dhseq'] == "2"){      $tbl = "seminar_app_dhead";       $tblhead = $dhead;
                }elseif($setup['hhseq'] == "2"){  $tbl = "seminar_app_hrd";         $tblhead = $hrd;
                }elseif($setup['chseq'] == "2"){  $tbl = "seminar_app_chead";       $tblhead = $chead;
                }elseif($setup['upseq'] == "2"){  $tbl = "seminar_app_uphy";        $tblhead = $up;
                }elseif($setup['boseq'] == "2"){  $tbl = "seminar_app_budgetoff";   $tblhead = $budgetOff;
                }elseif($setup['fdseq'] == "2"){  $tbl = "seminar_app_findir";      $tblhead = $financeDir;
                }elseif($setup['pseq'] == "2"){   $tbl = "seminar_app_president";   $tblhead = $president;}

                if($user == $dhead){    // Dean/Head Seminar Application
                    // if(!empty($chead)){
                        $eid = $data['eid'];
                        $qbid   = $this->db->query("SELECT baseid FROM based_id_offbus");
                        $bid    = $qbid->row(0)->baseid;
                                  $this->db->query("UPDATE based_id_offbus SET baseid='".($bid+1)."'");
                        foreach(explode(",",$eid) as $empid){
                            ///< check if emp is dhead or chead
                            // if($empid == $dheadtest || $empid == $cheadtest){
                            //     $query = $this->applyHeadSeminar($empid,$dheadtest, $cheadtest, $bid, $data, $poa,$course,$tfrom,$tto,$venue,$speaker,$misc,$soc);
                                
                            // }else{

                                $isread = 1;                        
                                $ins   = $this->db->query("
                                    INSERT INTO seminar_app (employeeid,base_id,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,coursefee_approved,meal,meal_approved,transportation,transportation_approved,hotel,hotel_approved,othermiscellaneous,othermiscellaneous_approved,totalcost,totalcost_approved,venue,speaker,miscellaneous,statement,depthead,deptheadstatus,deptheaddate,hrdir,cluster,budgetoff,financedir,president,dhseq,hhseq,chseq,upseq,boseq,fdseq,pseq,isread) 
                                    VALUES ('{$empid}','{$bid}','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['cfeeApproved']}','{$data['meal']}','{$data['mealApproved']}','{$data['transpo']}','{$data['transpoApproved']}','{$data['hotel']}','{$data['hotelApproved']}','{$data['othermiscellaneous']}','{$data['othermiscellaneousApproved']}','{$data['tc']}','{$data['tcApproved']}','{$venue}','{$speaker}','{$misc}','{$soc}','{$dhead}','APPROVED',CURRENT_DATE,'{$hrd}','{$chead}','{$budgetOff}','{$financeDir}','{$president}','{$setup['dhseq']}','{$setup['hhseq']}','{$setup['chseq']}','{$setup['upseq']}','{$setup['boseq']}','{$setup['fdseq']}','{$setup['pseq']}','{$isread}')");
                                if($ins){
                                    $qid   = $this->db->query("SELECT id FROM seminar_app WHERE employeeid='$empid' AND dfrom='{$data['datesetfrom']}' AND dto='{$data['datesetto']}' AND tstart='{$tfrom}' AND tend='{$tto}' AND nodays='{$data['ndays']}' AND purpose='{$poa}' AND course='{$course}' AND venue='{$venue}' AND statement='{$soc}'");
                                    $aid   = ($qid->num_rows() > 0 ? $qid->row(0)->id : 0);
                                            
                                    $query = $this->db->query("INSERT INTO seminar_app_dhead (aid,base_id,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,coursefee_approved,meal,meal_approved,transportation,transportation_approved,hotel,hotel_approved,othermiscellaneous,othermiscellaneous_approved,totalcost,totalcost_approved,venue,speaker,miscellaneous,statement,status,dateapproved,dateapplied) VALUES ('$aid','{$bid}','{$empid}','{$dhead}','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['cfeeApproved']}','{$data['meal']}','{$data['mealApproved']}','{$data['transpo']}','{$data['transpoApproved']}','{$data['hotel']}','{$data['hotelApproved']}','{$data['othermiscellaneous']}','{$data['othermiscellaneousApproved']}','{$data['tc']}','{$data['tcApproved']}','{$venue}','{$speaker}','{$misc}','{$soc}','APPROVED',NOW(),NOW())");
                                    $query = $this->db->query("INSERT INTO $tbl (aid,base_id,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,coursefee_approved,meal,meal_approved,transportation,transportation_approved,hotel,hotel_approved,othermiscellaneous,othermiscellaneous_approved,totalcost,totalcost_approved,venue,speaker,miscellaneous,statement,dateapplied) VALUES ('$aid','{$bid}','{$empid}','$tblhead','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['cfeeApproved']}','{$data['meal']}','{$data['mealApproved']}','{$data['transpo']}','{$data['transpoApproved']}','{$data['hotel']}','{$data['hotelApproved']}','{$data['othermiscellaneous']}','{$data['othermiscellaneousApproved']}','{$data['tc']}','{$data['tcApproved']}','{$venue}','{$speaker}','{$misc}','{$soc}',NOW())");
                                } 
                            // }
                        }
                        if($query){
                            $return = array("err_code"=>0,"msg"=>"Application Sent!.","base_id"=>$bid);
                        }  
                        else{
                            $return = array("err_code"=>2,"msg"=>"Ooops, Failed!","base_id"=>"");
                        }        
                    // }else   $return = "Ooops, Failed! Your department has no cluster head.";
                }
				else{
					$return = array("err_code"=>2,"msg"=>"Ooops, Failed!","base_id"=>"");
				}
            } //end foreach

        } //end if

        return $return;
    }

    function applyHeadSeminar($empid,$dhead, $chead, $bid, $data, $poa,$course,$tfrom,$tto,$venue,$speaker,$misc,$soc){
        $hrd  = $up = $budgetOff = $financeDir = $president = "";
        $leave_setup_data = $this->db->query("SELECT * FROM code_request_form WHERE code_request='OBSHEAD'")->result_array();

        foreach ($leave_setup_data as $key => $setup) {
            if($setup['dhseq'] == "0"){ 
                $dhead = '';
            }

            if($setup['hhseq'] != "0"){
                $qhrd   = $this->db->query("SELECT head FROM code_office WHERE code='HR'");
                $hrd    = ($qhrd->num_rows() > 0 ? $qhrd->row(0)->head : "");                             // HR Director
            } 

            if($setup['chseq'] == "0"){ 
                $chead = "";
            }

            if($setup['upseq'] != "0"){ 
                $qup    = $this->db->query("SELECT * FROM code_request_form WHERE (univphy<>'' OR univphyt<>'') AND code_request='$ltype'"); // University Physician
                $up     = ($qup->num_rows() > 0 ? $qup->row(0)->univphy.",".$qup->row(0)->univphyt : "");
            }

            if($setup['boseq'] != "0") $budgetOff      = $setup['budgetoff'];
            if($setup['fdseq'] != "0") $financeDir     = $setup['financedir'];
            if($setup['pseq'] != "0")  $president      = $setup['president'];

            // $seq = $this->getAppSequence($data['ltype'], $deptid, $data['ltype']); 
            #condition for sequence
            $tbl = "";
            $tblhead = "";
            if($setup['dhseq'] == "1"){      $tbl = "seminar_app_dhead";       $tblhead = $dhead;
            }elseif($setup['hhseq'] == "1"){  $tbl = "seminar_app_hrd";         $tblhead = $hrd;
            }elseif($setup['chseq'] == "1"){  $tbl = "seminar_app_chead";       $tblhead = $chead;
            }elseif($setup['upseq'] == "1"){  $tbl = "seminar_app_uphy";        $tblhead = $up;
            }elseif($setup['boseq'] == "1"){  $tbl = "seminar_app_budgetoff";   $tblhead = $budgetOff;
            }elseif($setup['fdseq'] == "1"){  $tbl = "seminar_app_findir";      $tblhead = $financeDir;
            }elseif($setup['pseq'] == "1"){   $tbl = "seminar_app_president";   $tblhead = $president;}


            $isread = 1;                        
            $ins   = $this->db->query("
                INSERT INTO seminar_app (employeeid,base_id,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,coursefee_approved,meal,meal_approved,transportation,transportation_approved,hotel,hotel_approved,othermiscellaneous,othermiscellaneous_approved,totalcost,totalcost_approved,venue,speaker,miscellaneous,statement,depthead,deptheadstatus,deptheaddate,hrdir,cluster,budgetoff,financedir,president,dhseq,hhseq,chseq,upseq,boseq,fdseq,pseq,isread) 
                VALUES ('{$empid}','{$bid}','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['cfeeApproved']}','{$data['meal']}','{$data['mealApproved']}','{$data['transpo']}','{$data['transpoApproved']}','{$data['hotel']}','{$data['hotelApproved']}','{$data['othermiscellaneous']}','{$data['othermiscellaneousApproved']}','{$data['tc']}','{$data['tcApproved']}','{$venue}','{$speaker}','{$misc}','{$soc}','{$dhead}','APPROVED',CURRENT_DATE,'{$hrd}','{$chead}','{$budgetOff}','{$financeDir}','{$president}','{$setup['dhseq']}','{$setup['hhseq']}','{$setup['chseq']}','{$setup['upseq']}','{$setup['boseq']}','{$setup['fdseq']}','{$setup['pseq']}','{$isread}')");
            if($ins){
                $qid   = $this->db->query("SELECT id FROM seminar_app WHERE employeeid='$empid' AND dfrom='{$data['datesetfrom']}' AND dto='{$data['datesetto']}' AND tstart='{$tfrom}' AND tend='{$tto}' AND nodays='{$data['ndays']}' AND purpose='{$poa}' AND course='{$course}' AND venue='{$venue}' AND statement='{$soc}'");
                $aid   = ($qid->num_rows() > 0 ? $qid->row(0)->id : 0);
                        
                $query = $this->db->query("INSERT INTO $tbl (aid,base_id,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,coursefee_approved,meal,meal_approved,transportation,transportation_approved,hotel,hotel_approved,othermiscellaneous,othermiscellaneous_approved,totalcost,totalcost_approved,venue,speaker,miscellaneous,statement,dateapplied) VALUES ('$aid','{$bid}','{$empid}','$tblhead','{$poa}','{$course}','{$data['datesetfrom']}','{$data['datesetto']}','{$tfrom}','{$tto}','{$data['ndays']}','{$data['pwd']}','{$data['cfee']}','{$data['cfeeApproved']}','{$data['meal']}','{$data['mealApproved']}','{$data['transpo']}','{$data['transpoApproved']}','{$data['hotel']}','{$data['hotelApproved']}','{$data['othermiscellaneous']}','{$data['othermiscellaneousApproved']}','{$data['tc']}','{$data['tcApproved']}','{$venue}','{$speaker}','{$misc}','{$soc}',NOW())");
            } 

        }

        return $query;

    }
    
    function modifySeminar($data){
        $return = "";
        $tfrom  = date("H:i:s",strtotime($data['tfrom']));
        $tto    = date("H:i:s",strtotime($data['tto']));
        $poa    = $this->extras->clean($data['poa']);
        $course = $this->extras->clean($data['course']);
        $venue  = $this->extras->clean($data['venue']);
        $speaker= $this->extras->clean($data['speaker']);
        $misc   = $this->extras->clean($data['miscellaneous']);
        $soc    = $this->extras->clean($data['soc']);
        $eid    = $data['eid'];
        foreach(explode(",",$eid) as $empid){
            $eq    = $this->db->query("SELECT * FROM seminar_app WHERE employeeid='$empid' AND base_id='{$data['id']}' AND (hrdirstatus='APPROVED' OR hrdirstatus='DISAPPROVED')");
            if($eq->num_rows() > 0){
                $return .= " Employee No.: ".$empid." is unable to update because the status is already ".$eq->row(0)->hrdirstatus." by HR Head.\n";         
            }else{
                     $this->db->query("UPDATE seminar_app SET purpose='{$poa}', course='{$course}', dfrom='{$data['datesetfrom']}', dto='{$data['datesetto']}', tstart='{$tfrom}', tend='{$tto}', nodays='{$data['ndays']}', paiddays='{$data['pwd']}', coursefee='{$data['cfee']}', meal='{$data['meal']}', transportation='{$data['transpo']}', hotel='{$data['hotel']}', totalcost='{$data['tc']}', venue='{$venue}', miscellaneous='{$misc}', statement='$soc' WHERE employeeid='$empid' AND base_id='{$data['id']}'");
            $query = $this->db->query("UPDATE seminar_app_hrd SET purpose='{$poa}', course='{$course}', dfrom='{$data['datesetfrom']}', dto='{$data['datesetto']}', tstart='{$tfrom}', tend='{$tto}', nodays='{$data['ndays']}', paiddays='{$data['pwd']}', coursefee='{$data['cfee']}', meal='{$data['meal']}', transportation='{$data['transpo']}', hotel='{$data['hotel']}', totalcost='{$data['tc']}', venue='{$venue}', miscellaneous='{$misc}', statement='$soc' WHERE employeeid='$empid' AND base_id='{$data['id']}'");
                $return .= " Employee No.: ".$empid." is successfully updated!\n";
            }
        }
        return $return;
    }
    function delSeminar($data){
        $return = "";
        $eq    = $this->db->query("SELECT * FROM seminar_app WHERE base_id='{$data['id']}' AND (hrdirstatus='APPROVED' OR hrdirstatus='DISAPPROVED')");
        if($eq->num_rows() > 0){
            foreach($eq->result() as $row){
                $return .= " Employee No.: ".$row->employeeid." is unable to delete because the status is already ".$row->hrdirstatus." by HR Head.\n";
            }
            $eq    = $this->db->query("SELECT * FROM seminar_app WHERE base_id='{$data['id']}' AND (hrdirstatus IS NULL OR hrdirstatus='PENDING')");
            foreach($eq->result() as $row){
                $this->db->query("DELETE FROM seminar_app WHERE employeeid='".$row->employeeid."' AND base_id='{$data['id']}'");
                $this->db->query("DELETE FROM seminar_app_hrd WHERE employeeid='".$row->employeeid."' AND base_id='{$data['id']}'");
                $return .= " Employee No.: ".$row->employeeid." is successfully deleted!.\n";
            }
        }else{
            $eq    = $this->db->query("SELECT * FROM seminar_app WHERE base_id='{$data['id']}' AND (hrdirstatus IS NULL OR hrdirstatus='PENDING')");
            foreach($eq->result() as $row){
                $this->db->query("DELETE FROM seminar_app WHERE employeeid='".$row->employeeid."' AND base_id='{$data['id']}'");
                $this->db->query("DELETE FROM seminar_app_hrd WHERE employeeid='".$row->employeeid."' AND base_id='{$data['id']}'");
                $return .= " Employee No.: ".$row->employeeid." is successfully deleted!.\n";
            }
        }
        return $return;
    }
        
    function seminar_modify_query($id=""){
        $query = $this->db->query("SELECT * FROM seminar_app WHERE base_id='$id'");
        return $query;
    }
    
    function seminar_approve_head($data){
        $user = $this->session->userdata("username");
        $id     = $data['id'];
        $aid    = $data['aid'];
        $status = $data['status'];
        
        $head       = $this->db->query("SELECT * FROM seminar_app WHERE id='$aid'");
        $depthead   = ($head->num_rows() > 0 ? $head->row(0)->depthead : "");
        $hrdir      = ($head->num_rows() > 0 ? $head->row(0)->hrdir : "");
        $cluster    = ($head->num_rows() > 0 ? $head->row(0)->cluster : "");
        $budgetoff  = ($head->num_rows() > 0 ? $head->row(0)->budgetoff : "");
        $financedir = ($head->num_rows() > 0 ? $head->row(0)->financedir : "");
        $president  = ($head->num_rows() > 0 ? $head->row(0)->president : "");
        
        if($depthead == $user){
            if($status == "APPROVED"){
            $query = $this->db->query(" INSERT INTO seminar_app_hrd 
                                        (aid,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied)
                                (SELECT aid,employeeid,'$hrdir',purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied FROM seminar_app_dhead WHERE id='$id')
                                      ");
            }
            if($status == "DISAPPROVED")
                $query = $this->db->query("UPDATE seminar_app SET deptheadstatus='$status', deptheaddate=CURRENT_DATE, isread=0 WHERE id='$aid'");
            else
                $query = $this->db->query("UPDATE seminar_app SET deptheadstatus='$status', deptheaddate=CURRENT_DATE WHERE id='$aid'");
                         $this->db->query("UPDATE seminar_app_dhead SET status='$status', dateapproved=CURRENT_DATE WHERE id='$id'");
        }else if($hrdir == $user){
            if($status == "APPROVED"){
            $query = $this->db->query(" INSERT INTO seminar_app_chead 
                                        (aid,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied)
                                (SELECT aid,employeeid,'$cluster',purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied FROM seminar_app_hrd WHERE id='$id')
                                      ");
            }
            if($status == "DISAPPROVED")
                $query = $this->db->query("UPDATE seminar_app SET hrdirstatus='$status', hrdirdate=CURRENT_DATE, isread=0 WHERE id='$aid'");
            else
                $query = $this->db->query("UPDATE seminar_app SET hrdirstatus='$status', hrdirdate=CURRENT_DATE WHERE id='$aid'");
                         $this->db->query("UPDATE seminar_app_hrd SET status='$status', dateapproved=CURRENT_DATE WHERE id='$id'");
        }else if($cluster == $user){
            if($status == "APPROVED"){
            $query = $this->db->query(" INSERT INTO seminar_app_budgetoff 
                                        (aid,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied)
                                (SELECT aid,employeeid,'$budgetoff',purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied FROM seminar_app_chead WHERE id='$id')
                                      ");
            }
            if($status == "DISAPPROVED")
                $query = $this->db->query("UPDATE seminar_app SET clusterstatus='$status', clusterdate=CURRENT_DATE, isread=0 WHERE id='$aid'");
            else
                $query = $this->db->query("UPDATE seminar_app SET clusterstatus='$status', clusterdate=CURRENT_DATE WHERE id='$aid'");
                         $this->db->query("UPDATE seminar_app_chead SET status='$status', dateapproved=CURRENT_DATE WHERE id='$id'");
        }else if($budgetoff == $user){
            if($status == "APPROVED"){
            $query = $this->db->query(" INSERT INTO seminar_app_findir 
                                        (aid,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied)
                                (SELECT aid,employeeid,'$financedir',purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied FROM seminar_app_budgetoff WHERE id='$id')
                                      ");
            }
            if($status == "DISAPPROVED")
                $query = $this->db->query("UPDATE seminar_app SET budgetoffstatus='$status', budgetoffdate=CURRENT_DATE, isread=0 WHERE id='$aid'");
            else            
                $query = $this->db->query("UPDATE seminar_app SET budgetoffstatus='$status', budgetoffdate=CURRENT_DATE WHERE id='$aid'");
                         $this->db->query("UPDATE seminar_app_budgetoff SET status='$status', dateapproved=CURRENT_DATE WHERE id='$id'");
        }else if($financedir == $user){
            if($status == "APPROVED"){
            $query = $this->db->query(" INSERT INTO seminar_app_president 
                                        (aid,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied)
                                (SELECT aid,employeeid,'$president',purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,dateapplied FROM seminar_app_findir WHERE id='$id')
                                      ");
            }
            if($status == "DISAPPROVED")
                $query = $this->db->query("UPDATE seminar_app SET financedirstatus='$status', financedirdate=CURRENT_DATE, isread=0 WHERE id='$aid'");
            else    
                $query = $this->db->query("UPDATE seminar_app SET financedirstatus='$status', financedirdate=CURRENT_DATE WHERE id='$aid'");
                         $this->db->query("UPDATE seminar_app_findir SET status='$status', dateapproved=CURRENT_DATE WHERE id='$id'");
        }else if($president == $user){
            if($status == "APPROVED"){
            $query = $this->db->query(" INSERT INTO seminar_request 
                                        (aid,employeeid,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,status)
                                (SELECT aid,employeeid,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,coursefee,meal,transportation,hotel,totalcost,venue,speaker,miscellaneous,statement,'$status' FROM seminar_app_findir WHERE aid='$aid')
                                      ");
                     $this->db->query("INSERT INTO timesheet (userid,timein,timeout,otype) (SELECT employeeid,CONCAT(dfrom,' ',tstart),CONCAT(dto,' ',tend),'SEMINAR' FROM seminar_app_findir WHERE aid='$aid')");
            }
            if($status == "DISAPPROVED" || $status == "APPROVED"){
                $query = $this->db->query("UPDATE seminar_app SET status='$status',presidentstatus='$status', presidentdate=CURRENT_DATE, isread=0 WHERE id='$aid'");
                         $this->db->query("UPDATE seminar_app_president SET status='$status', dateapproved=CURRENT_DATE WHERE id='$id'");
            }
        }
        
        if($query)  $return = "Successfully $status!.";  
        else        $return = "Failed!.";
        
        return $return;
    }

    function seminar_approve_head_withsequence($data){
        $query = $return = "";
        $user = $this->session->userdata("username");
        $id     = $data['id'];
        $aid    = $data['aid'];
        $eid     = $data['eid'];
        $status = $data['status'];
        $campus = '';
        $deptid = $this->employee->getempdatacol('deptid',$eid);

        $qdept  = $this->db->query("SELECT deptid, campusid FROM employee WHERE employeeid='$eid'");
        $deptid = ($qdept->num_rows() > 0 ? $qdept->row(0)->deptid : "");
        $campus = ($qdept->num_rows() > 0 ? $qdept->row(0)->campusid : "");
        if($deptid){
            $qdhead = $this->db->query("SELECT head,divisionhead FROM code_office WHERE code='$deptid'");
            $dhead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->head : "");                         // department head
            $chead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->divisionhead : "");                 // Cluster head
        }
        $forhead = '';

        if($eid==$dhead || $eid==$chead) $forhead = 'HEAD';

        $seq = $this->getSeminarSequence('OBS'.$forhead,$deptid,'OBS'.$forhead,$campus);
        // echo "<pre>";print_r($seq);
        $seqnum = 1;
        $tbl = $head_col_status = $head_col_date = '';
        for($seqno = $seqnum; $seqno< count($seq); $seqno++){
            if(in_array($user, explode(",",$seq[$seqno]["head"]))){
                $tbl                = $seq[$seqno]["tbl"];
                $head_col_status    = ($seq[$seqno]["type"] == "dh" ? "deptheadstatus" : 
                                        ($seq[$seqno]["type"] == "hh" ? "hrdirstatus" : 
                                        ($seq[$seqno]["type"] == "ch" ? "clusterstatus" : 
                                        ($seq[$seqno]["type"] == "cp" ? "cpstatus" : 
                                        ($seq[$seqno]["type"] == "up" ? "univphystatus" :
                                        ($seq[$seqno]["type"] == "bo" ? "budgetoffstatus" :
                                        ($seq[$seqno]["type"] == "fd" ? "financedirstatus" :
                                        ($seq[$seqno]["type"] == "pt" ? "presidentstatus" :
                                         ""))))))));
                $head_col_date      = ($seq[$seqno]["type"] == "dh" ? "deptheaddate" : 
                                        ($seq[$seqno]["type"] == "hh" ? "hrdirdate" : 
                                        ($seq[$seqno]["type"] == "ch" ? "clusterdate" : 
                                        ($seq[$seqno]["type"] == "cp" ? "cpdate" : 
                                        ($seq[$seqno]["type"] == "up" ? "univphydate" : 
                                        ($seq[$seqno]["type"] == "bo" ? "budgetoffdate" :
                                        ($seq[$seqno]["type"] == "fd" ? "financedirdate" :
                                        ($seq[$seqno]["type"] == "pt" ? "presidentdate" :
                                        ""))))))));

                // if($seqno < $seqnum){
					// break;
                // }
				if($seqno <= $seqnum)
				{
					$seqnum += 1;
				}
				else
				{
					$seqnum = $seqno + 1;	
				}
            }
			if($this->db->query("SELECT * from $tbl WHERE employeeid='{$eid}' AND aid='{$aid}' AND id='{$id}' AND status='PENDING' ")->num_rows() > 0)
			{
				break;
			}             
        }  
        // echo $tbl.' - ' . $seq[$seqnum]['tbl'];
        // echo print_r($seq);
        // echo ' seqnum - '.$seqnum. ' - '. count($seq).'///';
        $head_col_status = array(
                                    'seminar_app_dhead' => 'deptheadstatus',
                                    'seminar_app_hrd' => 'hrdirstatus',
                                    'seminar_app_chead' => 'clusterstatus',
                                    'seminar_principal' => 'cpstatus',
                                    'seminar_app_uphy' => 'univphystatus',
                                    'seminar_app_budgetoff' => 'budgetoffstatus',
                                    'seminar_app_findir' => 'financedirstatus',
                                    'seminar_app_president' => 'presidentstatus',
                                );
        $head_col_date = array(
                                    'seminar_app_dhead' => 'deptheaddate',
                                    'seminar_app_hrd' => 'hrdirdate',
                                    'seminar_app_chead' => 'clusterdate',
                                    'seminar_principal' => 'cpdate',
                                    'seminar_app_uphy' => 'univphydate',
                                    'seminar_app_budgetoff' => 'budgetoffdate',
                                    'seminar_app_findir' => 'financedirdate',
                                    'seminar_app_president' => 'presidentdate',
                                );
        $head_col_status = $head_col_status[$tbl];
        $head_col_date = $head_col_date[$tbl];
        if($seqnum == count($seq)){
            if($status == "APPROVED"){
                $query = $this->db->query(" INSERT INTO seminar_request 
                                        (aid,employeeid,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,paiddays_approved,coursefee,coursefee_approved,meal,meal_approved,transportation,transportation_approved,hotel,hotel_approved,othermiscellaneous,othermiscellaneous_approved,totalcost,totalcost_approved,venue,speaker,miscellaneous,statement,status)
                                (SELECT aid,employeeid,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,paiddays_approved,coursefee,coursefee_approved,meal,meal_approved,transportation,transportation_approved,hotel,hotel_approved,othermiscellaneous,othermiscellaneous_approved,totalcost,totalcost_approved,venue,speaker,miscellaneous,statement,'$status' FROM $tbl WHERE aid='$aid')
                                      ");    
				
                $this->db->query("INSERT INTO timesheet (userid,timein,timeout,otype) (SELECT employeeid,CONCAT(dfrom,' ',tstart),CONCAT(dto,' ',tend),'SEMINAR' FROM $tbl WHERE aid='$aid')");
            }
			if($status == "DISAPPROVED" || $status == "APPROVED"){
                $query = $this->db->query("UPDATE seminar_app SET status='$status', $head_col_status='$status', $head_col_date=CURRENT_DATE, isread=0,paiddays_approved = '{$data['pwdApproved']}',coursefee_approved = '{$data['coursefeeApproved']}' , meal_approved = '{$data['mealApproved']}' , transportation_approved= '{$data['transportationApproved']}' , hotel_approved = '{$data['hotelApproved']}' , othermiscellaneous_approved = '{$data['othermiscellaneousApproved']}' , totalcost_approved = '{$data['tcApproved']}' WHERE id='$aid'");
                         $this->db->query("UPDATE $tbl SET status='$status', dateapproved=CURRENT_DATE,paiddays_approved = '{$data['pwdApproved']}',coursefee_approved = '{$data['coursefeeApproved']}' , meal_approved = '{$data['mealApproved']}' , transportation_approved= '{$data['transportationApproved']}' , hotel_approved = '{$data['hotelApproved']}' , othermiscellaneous_approved = '{$data['othermiscellaneousApproved']}'  , totalcost_approved = '{$data['tcApproved']}' WHERE id='$id'");
            }
		}else{
            if($status == "APPROVED"){
				$query = $this->db->query("UPDATE $tbl SET paiddays_approved = '{$data['pwdApproved']}', coursefee_approved = '{$data['coursefeeApproved']}' , meal_approved = '{$data['mealApproved']}' , transportation_approved= '{$data['transportationApproved']}' , hotel_approved = '{$data['hotelApproved']}' , othermiscellaneous_approved = '{$data['othermiscellaneousApproved']}' , totalcost_approved = '{$data['tcApproved']}' WHERE id='$id'");    
				$query = $this->db->query("UPDATE seminar_app SET paiddays_approved = '{$data['pwdApproved']}', coursefee_approved = '{$data['coursefeeApproved']}' , meal_approved = '{$data['mealApproved']}' , transportation_approved= '{$data['transportationApproved']}' , hotel_approved = '{$data['hotelApproved']}' , othermiscellaneous_approved = '{$data['othermiscellaneousApproved']}' , totalcost_approved = '{$data['tcApproved']}' WHERE id='$aid'");    
				
                $query = $this->db->query(" INSERT INTO {$seq[$seqnum]['tbl']} 
                                       (aid,employeeid,head,purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,paiddays_approved,coursefee,coursefee_approved,meal,meal_approved,transportation,transportation_approved,hotel,hotel_approved,othermiscellaneous,othermiscellaneous_approved,totalcost,totalcost_approved,venue,speaker,miscellaneous,statement,dateapplied,status)
                               (SELECT aid,employeeid,'{$seq[$seqnum]["head"]}',purpose,course,dfrom,dto,tstart,tend,nodays,paiddays,paiddays_approved,coursefee,coursefee_approved,meal,meal_approved,transportation,transportation_approved,hotel,hotel_approved,othermiscellaneous,othermiscellaneous_approved,totalcost,totalcost_approved,venue,speaker,miscellaneous,statement,dateapplied,'PENDING' FROM $tbl WHERE id='$id')
                                     ");
				
				
           }
		   if($status == "DISAPPROVED"){
                echo "<pre>". print_r("UPDATE seminar_app SET status='$status', $head_col_status='$status', $head_col_date=CURRENT_DATE, isread=0,coursefee_approved = '{$data['coursefeeApproved']}',coursefee_approved = '{$data['coursefeeApproved']}' , meal_approved = '{$data['mealApproved']}' , transportation_approved= '{$data['transportationApproved']}' , hotel_approved = '{$data['hotelApproved']}' , othermiscellaneous_approved = '{$data['othermiscellaneousApproved']}' , totalcost_approved = '{$data['tcApproved']}' WHERE id='$aid'");die;
				$query = $this->db->query("UPDATE seminar_app SET status='$status', $head_col_status='$status', $head_col_date=CURRENT_DATE, isread=0,coursefee_approved = '{$data['coursefeeApproved']}',coursefee_approved = '{$data['coursefeeApproved']}' , meal_approved = '{$data['mealApproved']}' , transportation_approved= '{$data['transportationApproved']}' , hotel_approved = '{$data['hotelApproved']}' , othermiscellaneous_approved = '{$data['othermiscellaneousApproved']}' , totalcost_approved = '{$data['tcApproved']}' WHERE id='$aid'");                
           }
		   else {    
				$query = $this->db->query("UPDATE seminar_app SET $head_col_status='$status', $head_col_date=CURRENT_DATE, isread=1, coursefee_approved = '{$data['coursefeeApproved']}',coursefee_approved = '{$data['coursefeeApproved']}' , meal_approved = '{$data['mealApproved']}' , transportation_approved= '{$data['transportationApproved']}' , hotel_approved = '{$data['hotelApproved']}' , othermiscellaneous_approved = '{$data['othermiscellaneousApproved']}' , totalcost_approved = '{$data['tcApproved']}' WHERE id='$aid'");
                $this->db->query("UPDATE $tbl SET status='$status', dateapproved=CURRENT_DATE, coursefee_approved = '{$data['coursefeeApproved']}',coursefee_approved = '{$data['coursefeeApproved']}' , meal_approved = '{$data['mealApproved']}' , transportation_approved= '{$data['transportationApproved']}' , hotel_approved = '{$data['hotelApproved']}' , othermiscellaneous_approved = '{$data['othermiscellaneousApproved']}' , totalcost_approved = '{$data['tcApproved']}' WHERE id='$id'");
		   }
	   }
		if($query)  $return = "Success! Status now is : $status";  
        else        $return = "Failed!.";
        return $return;
    }
    
    function applyOT($data){
        $return = ""; 
        $reason = $this->extras->clean($data['reason']);
        $tstart = date("H:i:s",strtotime($data['tfrom']));
        $tend    = date("H:i:s",strtotime($data['tto']));
        if(isset($data['employeeid']))
            $user   = $data['employeeid'];
        else
            $user   = $this->session->userdata("username");
        $qdept  = $this->db->query("SELECT deptid FROM employee WHERE employeeid='$user'");
        $deptid = ($qdept->num_rows() > 0 ? $qdept->row(0)->deptid : "");
        if($deptid){
            $qdhead = $this->db->query("SELECT head,divisionhead FROM code_office WHERE code='$deptid'");
            $dhead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->head : "");                         // department head
            $chead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->divisionhead : "");                 // Cluster head
            $qhrd   = $this->db->query("SELECT head FROM code_office WHERE code='HR'");
            $hrd    = ($qhrd->num_rows() > 0 ? $qhrd->row(0)->head : "");                             // HR Director
                
                if($user == $dhead || $user == $hrd){    // Dean/Head & HR Head Vacation Leave Application
                    if(!empty($chead)){
                        $ins   = $this->db->query("INSERT INTO overtime_app (employeeid,dfrom,dto,tstart,tend,total,reason,depthead,hrdir) VALUES ('{$user}','{$data['datesetfrom']}','{$data['datesetto']}','{$tstart}','{$tend}','{$data['tot']}','{$reason}','{$chead}','{$hrd}')");
                        if($ins){
                            $qid   = $this->db->query("SELECT id FROM overtime_app WHERE employeeid='$user' AND dfrom='{$data['datesetfrom']}' AND dto='{$data['datesetto']}' AND tstart='{$tstart}' AND tend='{$tend}' AND total='{$data['tot']}' AND reason='{$reason}' AND depthead='{$chead}' AND hrdir='{$hrd}'");
                            $aid   = ($qid->num_rows() > 0 ? $qid->row(0)->id : 0);
                            
                            $query = $this->db->query("INSERT INTO overtime_app_chead (aid,employeeid,head,dfrom,dto,tstart,tend,total,reason,dateapplied) VALUES ('$aid','{$user}','{$chead}','{$data['datesetfrom']}','{$data['datesetto']}','{$tstart}','{$tend}','{$data['tot']}','{$reason}',NOW())");
                            if($query)  $return = "Application Sent!.";
                            else        $return = "Ooops, Failed!";
                        }
                    }else   $return = "Ooops, Failed! Your department has no cluster head.";
                }else if($user == $chead){      // Cluster Head Vacation Leave Application
                    $ins   = $this->db->query("INSERT INTO overtime_app (employeeid,dfrom,dto,tstart,tend,total,reason,depthead,hrdir) VALUES ('{$user}','{$data['datesetfrom']}','{$data['datesetto']}','{$tstart}','{$tend}','{$data['tot']}','{$reason}','{$dhead}','{$hrd}')");
                    if($ins){
                        $qid   = $this->db->query("SELECT id FROM overtime_app WHERE employeeid='$user' AND dfrom='{$data['datesetfrom']}' AND dto='{$data['datesetto']}' AND tstart='{$tstart}' AND tend='{$tend}' AND total='{$data['tot']}' AND reason='{$reason}' AND depthead='{$dhead}' AND hrdir='{$hrd}'");
                        $aid   = ($qid->num_rows() > 0 ? $qid->row(0)->id : 0);
                        
                        $query = $this->db->query("INSERT INTO leave_app_hrd (aid,employeeid,head,dfrom,dto,tstart,tend,total,reason,dateapplied) VALUES ('$aid','{$user}','{$dhead}','{$data['datesetfrom']}','{$data['datesetto']}','{$tstart}','{$tend}','{$data['tot']}','{$reason}',NOW())");
                        if($query)  $return = "Application Sent!.";
                        else        $return = "Ooops, Failed!";
                    }   
                }else{      // Employee Leave Application
                        $ins   = $this->db->query("INSERT INTO overtime_app (employeeid,dfrom,dto,tstart,tend,total,reason,depthead,hrdir) VALUES ('{$user}','{$data['datesetfrom']}','{$data['datesetto']}','{$tstart}','{$tend}','{$data['tot']}','{$reason}','{$dhead}','{$hrd}')");
                    if($ins){
                        $qid   = $this->db->query("SELECT id FROM overtime_app WHERE employeeid='$user' AND dfrom='{$data['datesetfrom']}' AND dto='{$data['datesetto']}' AND tstart='{$tstart}' AND tend='{$tend}' AND total='{$data['tot']}' AND reason='{$reason}' AND depthead='{$dhead}' AND hrdir='{$hrd}'");
                        $aid   = ($qid->num_rows() > 0 ? $qid->row(0)->id : 0);
                        
                        $query = $this->db->query("INSERT INTO overtime_app_dhead (aid,employeeid,head,dfrom,dto,tstart,tend,total,reason,dateapplied) VALUES ('$aid','{$user}','{$dhead}','{$data['datesetfrom']}','{$data['datesetto']}','{$tstart}','{$tend}','{$data['tot']}','{$reason}',NOW())");
                        if($query)  $return = "Application Sent!.";
                        else        $return = "Ooops, Failed!";
                    }
                }
        }else   $return = "Failed to apply leave. Please set your department first.";
        return $return;
    }
    
    function ot_approve_head($data){
        $return = "";
        if($data['id']){
            $id     = $data['id'];
            $aid    = $data['aid'];
            $status = $data['status'];
            $dept   = $data['dept'];
            $cdate  = date("Y-m-d");
            
                if($dept == "HR"){
                    if($status == "APPROVED"){
                              $this->db->query("INSERT INTO overtime_request (aid,employeeid,dfrom,dto,tstart,tend,total,reason,status,dateapproved,dateapplied)
                                                (SELECT aid,employeeid,dfrom,dto,tstart,tend,total,reason,'$status','$cdate',dateapplied FROM overtime_app_hrd WHERE id='$id')  
                                              ");
                    }
                              $this->db->query("UPDATE overtime_app SET status='$status',hrdirstatus='$status', hrdirdate='$cdate', isread=0 WHERE id='$aid'");
                    $query  = $this->db->query("UPDATE overtime_app_hrd SET status='$status', dateapproved='$cdate' WHERE id='$id'");
                }else{
                        if($this->employee->getClusterHead($this->session->userdata("username")))
                                $tbl = "overtime_app_chead";
                        else    $tbl = "overtime_app_dhead";
                        if($status == "APPROVED"){
                            $qhrd   = $this->db->query("SELECT head FROM code_office WHERE code='HR'");
                            $hrd    = ($qhrd->num_rows() > 0 ? $qhrd->row(0)->head : "");                         // department head
                                      $this->db->query("INSERT INTO overtime_app_hrd (aid,employeeid,head,dfrom,dto,tstart,tend,total,reason,dateapplied)
                                                        (SELECT aid,employeeid,'$hrd',dfrom,dto,tstart,tend,total,reason,dateapplied FROM $tbl WHERE id='$id')  
                                                      ");
                        }
                        if($status == "DISAPPROVED")
                              $this->db->query("UPDATE overtime_app SET status='$status',deptheadstatus='$status', deptheaddate='$cdate', isread=0 WHERE id='$aid'");
                        else
                              $this->db->query("UPDATE overtime_app SET deptheadstatus='$status', deptheaddate='$cdate' WHERE id='$aid'");
                    $query  = $this->db->query("UPDATE $tbl SET status='$status', dateapproved='$cdate' WHERE id='$id'");
                }
            
            if($query)  $return = "Success! Status now is : $status"; 
            else        $return = "Failed!.";
        }
        return $return;
    }
    function modifyOT($data){
        $return = "";
        $id = $data['id'];
        $query = $this->db->query("SELECT * FROM overtime_app WHERE id='$id' AND (deptheadstatus='APPROVED' OR deptheadstatus='DISAPPROVED')");
        if($query->num_rows() > 0){
            $return = "Failed to update!. The request has already ".$query->row()->deptheadstatus;
        }else{
            $query = $this->db->query("UPDATE overtime_app       SET dfrom='{$data['datesetfrom']}', dto='{$data['datesetto']}', tstart='{$data['tfrom']}', tend='{$data['tto']}', total='{$data['tot']}', reason='{$data['reason']}' WHERE id='$id'");
                     $this->db->query("UPDATE overtime_app_chead SET dfrom='{$data['datesetfrom']}', dto='{$data['datesetto']}', tstart='{$data['tfrom']}', tend='{$data['tto']}', total='{$data['tot']}', reason='{$data['reason']}' WHERE aid='$id'");
                     $this->db->query("UPDATE overtime_app_hrd   SET dfrom='{$data['datesetfrom']}', dto='{$data['datesetto']}', tstart='{$data['tfrom']}', tend='{$data['tto']}', total='{$data['tot']}', reason='{$data['reason']}' WHERE aid='$id'");
            $return = "Successfully Saved!.";
        }
        return $return;
    }
    function delOT($data){
        $return = "";
        $id = $data['id'];
        $query = $this->db->query("SELECT * FROM overtime_app WHERE id='$id' AND (deptheadstatus='APPROVED' OR deptheadstatus='DISAPPROVED')");
        if($query->num_rows() > 0){
            $return = "Failed to delete!. The request has already ".$query->row()->deptheadstatus;
        }else{
            $query = $this->db->query("DELETE FROM overtime_app       WHERE id='$id'");
                     $this->db->query("DELETE FROM overtime_app_chead WHERE aid='$id'");
                     $this->db->query("DELETE FROM overtime_app_hrd   WHERE aid='$id'");
            $return = "Successfully Deleted!.";
        }
        return $return;
    }
    function ot_modify_query($id=""){
        $query = $this->db->query("SELECT * FROM overtime_app WHERE id='$id'");
        return $query;
    }
    function modifyOffBus($data){

        $return = "";
        $id = $data['id'];
        $query = $this->db->query("SELECT * FROM leave_app WHERE (deptheadstatus='APPROVED' OR deptheadstatus='DISAPPROVED') WHERE id='{$id}'");
        if($query->num_rows() > 0){
            $return = "Failed to update!. The request has already ".$query->row()->deptheadstatus;
        }else{
            $data['tfrom'] = date('H:i:s', strtotime($data['tfrom']));
            $data['tto'] = date('H:i:s', strtotime($data['tto']));

            $query = $this->db->query("UPDATE leave_app       SET type='{$data['ltype']}', other='{$data['othleave']}', othertype='{$data['dltype']}', paid='{$data['withpay']}', datefrom='{$data['datesetfrom']}', dateto='{$data['datesetto']}', timefrom='{$data['tfrom']}', timeto='{$data['tto']}', nodays='{$data['ndays']}', reason='{$data['reason']}' WHERE id='{$data['id']}'");        
                     $this->db->query("UPDATE leave_app_dhead SET type='{$data['ltype']}', other='{$data['othleave']}', othertype='{$data['dltype']}', paid='{$data['withpay']}', datefrom='{$data['datesetfrom']}', dateto='{$data['datesetto']}', timefrom='{$data['tfrom']}', timeto='{$data['tto']}', nodays='{$data['ndays']}', reason='{$data['reason']}' WHERE aid='{$data['id']}'");
                     $this->db->query("UPDATE leave_app_chead SET type='{$data['ltype']}', other='{$data['othleave']}', othertype='{$data['dltype']}', paid='{$data['withpay']}', datefrom='{$data['datesetfrom']}', dateto='{$data['datesetto']}', timefrom='{$data['tfrom']}', timeto='{$data['tto']}', nodays='{$data['ndays']}', reason='{$data['reason']}' WHERE aid='{$data['id']}'");
                     $this->db->query("UPDATE leave_app_uphy  SET type='{$data['ltype']}', other='{$data['othleave']}', othertype='{$data['dltype']}', paid='{$data['withpay']}', datefrom='{$data['datesetfrom']}', dateto='{$data['datesetto']}', timefrom='{$data['tfrom']}', timeto='{$data['tto']}', nodays='{$data['ndays']}', reason='{$data['reason']}' WHERE aid='{$data['id']}'");
                     $this->db->query("UPDATE leave_app_hrd   SET type='{$data['ltype']}', other='{$data['othleave']}', othertype='{$data['dltype']}', paid='{$data['withpay']}', datefrom='{$data['datesetfrom']}', dateto='{$data['datesetto']}', timefrom='{$data['tfrom']}', timeto='{$data['tto']}', nodays='{$data['ndays']}', reason='{$data['reason']}' WHERE aid='{$data['id']}'");
                     $this->db->query("UPDATE leave_app_president   SET type='{$data['ltype']}', other='{$data['othleave']}', othertype='{$data['dltype']}', paid='{$data['withpay']}', datefrom='{$data['datesetfrom']}', dateto='{$data['datesetto']}', timefrom='{$data['tfrom']}', timeto='{$data['tto']}', nodays='{$data['ndays']}', reason='{$data['reason']}' WHERE aid='{$data['id']}'");
                     $this->db->query("UPDATE leave_app_principal   SET type='{$data['ltype']}', other='{$data['othleave']}', othertype='{$data['dltype']}', paid='{$data['withpay']}', datefrom='{$data['datesetfrom']}', dateto='{$data['datesetto']}', timefrom='{$data['tfrom']}', timeto='{$data['tto']}', nodays='{$data['ndays']}', reason='{$data['reason']}' WHERE aid='{$data['id']}'");
            $return = "Successfully Saved!";
        }
        return $return;
    }
    function delOffBus($data){
        $return = "";
        $query = $this->db->query("SELECT * FROM leave_app WHERE (deptheadstatus='APPROVED' OR deptheadstatus='DISAPPROVED') WHERE id='{$data['id']}'");
        if($query->num_rows() > 0){
            $return = "Failed to delete!. The request has already ".$query->row()->deptheadstatus;
        }else{
            $this->db->query("DELETE FROM leave_app       WHERE id ='{$data['id']}'");
            $this->db->query("DELETE FROM leave_app_dhead WHERE aid='{$data['id']}'");
            $this->db->query("DELETE FROM leave_app_chead WHERE aid='{$data['id']}'");
            $this->db->query("DELETE FROM leave_app_uphy  WHERE aid='{$data['id']}'");
            $this->db->query("DELETE FROM leave_app_hrd   WHERE aid='{$data['id']}'");
            $this->db->query("DELETE FROM leave_app_principal  WHERE aid='{$data['id']}'");
            $this->db->query("DELETE FROM leave_app_president  WHERE aid='{$data['id']}'");
            $this->db->query("DELETE FROM leave_app_tito  WHERE aid='{$data['id']}'");
            $return = "Succesfully Deleted!.";
        }
        return $return;
    }
    function modifyOffbus_query($id=""){
        $query = $this->db->query("SELECT * FROM leave_app WHERE id='$id'");
        return $query;
    }
    function seminar_attach($data){
        $return = "";
        $query = $this->db->query("SELECT * FROM seminar_app_attach WHERE id='{$data['id']}'");
        if($query->num_rows() > 0){
            if($data['job'] == "view")
                $query = $this->db->query("UPDATE seminar_app_attach SET status='{$data['status']}' WHERE id='{$data['id']}'");
            else
                $query = $this->db->query("UPDATE seminar_app_attach SET points='{$data['points']}' WHERE id='{$data['id']}'");
            if($query)  $return = "Report Updated!.";
            else        $return = "Ooops, Failed!";
        }else{
            if($data['job'] != "view"){
                $query = $this->db->query("INSERT INTO seminar_app_attach (id,points) VALUES ('{$data['id']}','{$data['points']}')");
                if($query)  $return = "Report Successfully Attached!.";
                else        $return = "Ooops, Failed!";
            }else   $return = "Invalid! No Report Attached!.";
        }
        return $return;
    }
    
    ///< binago na, na sa models/leave na function neto
    function setupLeave($data){
        $ins  = 0;
        $msg  = "";
        $user = $this->session->userdata("username");
        $job  = $data['code'] ? true : false;
		
		if($data['tnt'] == 'teaching')
		{
			$query = $this->db->query("SELECT * FROM code_leave_setup WHERE leavetype='{$data['mh_leavetype']}' AND teachingType ='{$data['tnt']}' AND ('{$data['datesetfrom']}' BETWEEN dfrom AND dto OR '{$data['datesetto']}' BETWEEN dfrom AND dto) ");
			if($query->num_rows() == 0)
				 $this->db->query("INSERT INTO code_leave_setup (leavetype,teachingType,credit,dfrom,dto,user) VALUES ('{$data['mh_leavetype']}','{$data['tnt']}','{$data['mh_credits']}','{$data['datesetfrom']}','{$data['datesetto']}','{$user}')");
			else $msg = "Conflict Schedule of Leave.";

			if(!$msg || $job){
				$query = $this->db->query("SELECT employeeid FROM employee WHERE isactive=1 AND teachingtype ='{$data['tnt']}'");
				foreach($query->result() as $row){
					$eid =  $row->employeeid;
								$this->db->query("UPDATE code_leave_setup SET dfrom='{$data['datesetfrom']}', dto='{$data['datesetto']}' WHERE id='{$data['lid']}'");
					$cquery =   $this->db->query("SELECT * FROM employee_leave_credit WHERE employeeid='$eid' AND leavetype='{$data['mh_leavetype']}' AND ('{$data['datesetfrom']}' BETWEEN dfrom AND dto OR '{$data['datesetto']}' BETWEEN dfrom AND dto) ");
					if($cquery->num_rows() == 0){
						$this->db->query("INSERT INTO employee_leave_credit (employeeid,leavetype,balance,credit,dfrom,dto,user) VALUES ('{$eid}','{$data['mh_leavetype']}','{$data['mh_credits']}','{$data['mh_credits']}','{$data['datesetfrom']}','{$data['datesetto']}','{$user}')");
						$ins++;
					}
					$msg = " $ins Employee Leave Successfully Distributed!.";      
				}
			}
		}
		else
		{
			$continue = true;
			foreach($data['empstatus'] as $key => $values)
			{
				$query = $this->db->query("SELECT * FROM code_leave_setup WHERE leavetype='{$data['mh_leavetype']}' AND teachingType='{$data['tnt']}' AND employmentStatus='{$values}' AND ('{$data['datesetfrom']}' BETWEEN dfrom AND dto OR '{$data['datesetto']}' BETWEEN dfrom AND dto) ");
				if($query->num_rows() != 0)
				{
					$msg = "Conflict Schedule of Leave.";
					if($job)$continue = false;
					break;
				}
			}
			
			if($continue)
			{
				if(!$msg || $job)
				{
					$employmentStatus = implode("/",$data['empstatus']);
					if(!$job)
					{
						$this->db->query("INSERT INTO code_leave_setup (leavetype,teachingType,employmentStatus,credit,dfrom,dto,user) VALUES ('{$data['mh_leavetype']}','{$data['tnt']}','{$employmentStatus}','{$data['mh_credits']}','{$data['datesetfrom']}','{$data['datesetto']}','{$user}')");
					}
						
					foreach($data['empstatus'] as $key => $values)
					{	
						$query = $this->db->query("SELECT employeeid FROM employee WHERE isactive=1 AND teachingtype ='{$data['tnt']}' AND employmentstat = '{$values}'");
						foreach($query->result() as $row){
							$eid =  $row->employeeid;
										$this->db->query("UPDATE code_leave_setup SET dfrom='{$data['datesetfrom']}', dto='{$data['datesetto']}' WHERE id='{$data['lid']}'");
							$cquery =   $this->db->query("SELECT * FROM employee_leave_credit WHERE employeeid='$eid' AND leavetype='{$data['mh_leavetype']}' AND ('{$data['datesetfrom']}' BETWEEN dfrom AND dto OR '{$data['datesetto']}' BETWEEN dfrom AND dto) ");
							if($cquery->num_rows() == 0){
								$this->db->query("INSERT INTO employee_leave_credit (employeeid,leavetype,balance,credit,dfrom,dto,user) VALUES ('{$eid}','{$data['mh_leavetype']}','{$data['mh_credits']}','{$data['mh_credits']}','{$data['datesetfrom']}','{$data['datesetto']}','{$user}')");
								$ins++;
							}
							$msg = " $ins Employee Leave Successfully Distributed!.";      
						}
					}
				}
			}
		}
        return $msg;
    }
    
    function markasread($data){
        $tbl    = $data['tbl'];
        $id     = $data['id'];
        $val    = $data['val'];
        $this->db->query("UPDATE $tbl SET isread='$val' WHERE id='$id'");
        echo "<pre>";
        print_r($this->db->last_query());
    }

    function markasread_sc($data){
        $tbl    = $data['tbl'];
        $id     = $data['id'];
        $val    = $data['val'];
        $this->db->query("UPDATE $tbl SET isread='$val' WHERE base_id='$id'");
    }

    function markasreadall($data){
        $tbl    = $data['tbl'];
        $id     = $data['id'];
        $val    = $data['val'];
        $this->db->query("UPDATE $tbl SET isread=$val WHERE base_id=$id");
    }

    function markasread_wfh($data){
        $tbl    = $data['tbl'];
        $id     = $data['id'];
        $val    = $data['val'];
        $this->db->query("UPDATE $tbl SET isread='$val' WHERE base_id='$id'");
    }
    
    function checkPendingApp($user = "",$dfrom = "", $dto = ""){
        $continue = false;
        $query = $this->db->query("SELECT * FROM leave_app WHERE employeeid='$user' AND datefrom BETWEEN '$dfrom' AND '$dto';");
        if($query->num_rows() > 0)      $continue = true;
        if(!$continue){
            $query = $this->db->query("SELECT * FROM overtime_app WHERE employeeid='$user' AND dfrom BETWEEN '$dfrom' AND '$dto';");
            if($query->num_rows() > 0)  $continue = true;    
        }
        if(!$continue){
            $query = $this->db->query("SELECT * FROM seminar_app WHERE employeeid='$user' AND dfrom BETWEEN '$dfrom' AND '$dto';");
            if($query->num_rows() > 0)  $continue = true;    
        }
        return $continue;
    }
    
    function confirmatt($data){
        $empid = $this->session->userdata("username");
        $tnt        = $this->employee->getempdatacol("teachingtype",$empid);
        $from_date  = $data['dfrom'];
        $to_date    = $data['dto'];
        $edata      = "NEW";
        $continue   = $this->checkPendingApp($empid,$from_date,$to_date);
        $qdate = $this->attcompute->displayDateRange($from_date, $to_date);
        
        if($continue) return "You cannot confirm this attendance because you still have pending request.. Please check your requests..";
        
        if($tnt == "teaching"){ // Teaching
        $tlec = $tlab = $tabsent = $tel = $tvl = $tsl = $tol = $tdlec = $tdlab = $tempOverload = $overload = $tOverload = $lastDayOfWeek = $firstDayOfWeek = ""; 
		$tempabsent = "";
		$firstDayOfWeek = $this->attcompute->getFirstDayOfWeek($empid);
		$lastDayOfWeek = $this->attcompute->getLastDayOfWeek($empid);
	
		if(date("l",strtotime($qdate[0]->dte) != $firstDayOfWeek))
		{
			$tempOverload = $this->attcompute->getPastDayOverload($empid,$qdate[0]->dte,$firstDayOfWeek,$edata);
		}
            foreach ($qdate as $rdate) {
                $dispLogDate = date("d-M (l)",strtotime($rdate->dte));
                $sched = $this->attcompute->displaySched($empid,$rdate->dte);
                $countrow = $sched->num_rows();
                
                if($countrow > 0){
                    $tempsched = "";
                    foreach($sched->result() as $rsched){
                        if($tempsched == $dispLogDate)  $dispLogDate = "";
                        $stime = $rsched->starttime;
                        $etime = $rsched->endtime; 
                        $type  = $rsched->leclab;
                        
						//Holiday
						$holiday = $this->attcompute->isHoliday($rdate->dte);
						
                        // logtime
                        list($login,$logout,$q) = $this->attcompute->displayLogTime($empid,$rdate->dte,$stime,$etime,$edata);
                        // Leave
                        list($el,$vl,$sl,$ol,$oltype)     = $this->attcompute->displayLeave($empid,$rdate->dte);
                        // Absent
                         $absent = $this->attcompute->displayAbsent($stime,$etime,$login,$logout,$empid,$rdate->dte);
						if($oltype == "ABSENT") $absent = $absent;
						else if($el || $vl || $sl || $ol || $holiday) $absent = "";
                        
                        // Late / Undertime
                        list($lateutlec,$lateutlab,$tschedlec,$tschedlab) = $this->attcompute->displayLateUT($stime,$etime,$login,$logout,$type,$absent);
						if($el || $vl || $sl || $ol || $oltype || $holiday){
							$lateutlec = $lateutlab = $tschedlec = $tschedlab = "";
						}
						
						if($holiday)
						{
							if($this->attcompute->isHolidayWithpay($rdate->dte) == "YES")
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
						
						// Service Credit
						$service_credit    = $this->attcompute->displayServiceCredit($empid,$rdate->dte);
						
						if($service_credit) $absent = 0;
						
						// Overload
						if(!$absent && !$lateutlec)
						{
							// $overload           = $this->attcompute->displayOverloadTime($stime,$etime,$login,$logout);
							// $tempOverload           += $this->attcompute->displayOverloadTime($login,$logout);
							$tempOverload           += $this->attcompute->displayOverloadTime($stime,$etime,$lateutlab);
							// var_dump(date('H:i',strtotime($overload)));
							
							// die;
						}
						else
						{
							// $overload = 0;
							$tempOverload += 0;
						}
						
						if($tempOverload > $this->attcompute->exp_time("30:00"))
						{
							$overload = $tempOverload - $this->attcompute->exp_time("30:00");
						}
						
						$tempsched = $dispLogDate;
						
                        /*
                         * Total
                         */ 
                        // Absent
                        $tabsent  += $absent;
                        // Late / UT
                        if($lateutlec){
                            $tlec += $this->attcompute->exp_time($lateutlec);
                        }
                        if($lateutlab){
                            $tlab += $this->attcompute->exp_time($lateutlab);
                        }
						
						if($dispLogDate){
							$tel      += $el;
							$tvl      += $vl;
							$tsl      += $sl;
							$tol      += ($ol ? 1 : "") + ($q ? ($q == 1 ? "" : 1) : "") ;
						} // end if
						
                        // Deductions
                        if($tschedlec){
                            $tdlec += $this->attcompute->exp_time($tschedlec);
                        }
                        if($tschedlab){
                            $tdlab += $this->attcompute->exp_time($tschedlab);
                        }
						
						if($lastDayOfWeek == date("l",strtotime($rdate->dte)))
						{
							$tOverload += $overload;
							$overload = $tempOverload = 0;
						}
						
                        
                    }   // end foreach
					
                } // end if
            }
            $tlec = ($tlec ? $this->attcompute->sec_to_hm($tlec) : "");
            $tlab = ($tlab ? $this->attcompute->sec_to_hm($tlab) : "");
            $tdlec = ($tdlec ? $this->attcompute->sec_to_hm($tdlec) : "");
            $tdlab = ($tdlab ? $this->attcompute->sec_to_hm($tdlab) : "");
			$tOverload = ($tOverload ? $this->attcompute->sec_to_hm($tOverload) : "");
            
            // Save to database
            $query = $this->db->query("SELECT * FROM attendance_confirmed WHERE cutoffstart='$from_date' AND cutoffend='$to_date' AND employeeid='$empid'");
            if($query->num_rows() == 0){
                $this->db->query("INSERT INTO attendance_confirmed (employeeid,cutoffstart,cutoffend,overload,latelec,latelab,absent,eleave,vleave,sleave,oleave,deduclec,deduclab) 
                                    VALUES ('$empid','$from_date','$to_date','$tOverload','$tlec','$tlab','$tabsent','$tel','$tvl','$tsl','$tol','$tdlec','$tdlab')");
            }
        }else{ // non teaching
            $totr = $totsat = $totsun = $tothol =$tlec = $tabsent = $tel = $tvl = $tsl = $tol = $tdlec = $tdlab = $tholiday = ""; 
			$tempabsent = "";
            foreach ($qdate as $rdate) {
            $dispLogDate = date("d-M (l)",strtotime($rdate->dte));
            $sched = $this->attcompute->displaySched($empid,$rdate->dte);
            $countrow = $sched->num_rows();
                
            if($countrow > 0){
                $tempsched = "";
				$seq = "";
                foreach($sched->result() as $rsched){
                    if($tempsched == $dispLogDate)  $dispLogDate = "";
					if($seq == ""){$seq = 1; }else{ $seq = 2;}
                    $stime = $rsched->starttime;
                    $etime = $rsched->endtime;
                    $tstart = $rsched->tardy_start; 
                    $earlyd = $rsched->early_dismissal; 
                    
                    // Holiday
                    $holiday = $this->attcompute->isHoliday($rdate->dte); 
                    
                    // logtime
                    list($login,$logout,$q) = $this->attcompute->displayLogTime($empid,$rdate->dte,$stime,$etime,$edata,$seq);
                        
                    // Overtime
                    list($otreg,$otsat,$otsun,$othol) = $this->attcompute->displayOt($empid,$rdate->dte);
                                
                    // Leave
                    list($el,$vl,$sl,$ol,$oltype)             = $this->attcompute->displayLeave($empid,$rdate->dte);
                     
                    // Absent
                    $absent = $this->attcompute->displayAbsent($stime,$etime,$login,$logout,$empid,$rdate->dte,$earlyd);
					if($oltype == "ABSENT") {$absent = $absent;}
					else if($el || $vl || $sl || $ol || $oltype || $holiday){ $absent = "";}
                        
                    // Late / Undertime
                    $lateutlec = $this->attcompute->displayLateUTNT($stime,$etime,$login,$logout,$absent,$tstart);
					if($el || $vl || $sl || $ol || $oltype || $holiday) $lateutlec = "";	
					
					if($holiday)
					{
						if($this->attcompute->isHolidayWithpay($rdate->dte) == "YES")
						{
							if($tempabsent)
							{
								$absent = 0.5;
							}
						}
						else
						{
							if(!$login && !$logout)
							{
								$absent = 0.5;
							}
						}
					}
					else
					{
						$tempabsent = $absent;
					}
					
					// Service Credit
					$service_credit    = $this->attcompute->displayServiceCredit($empid,$rdate->dte);
					
					if($service_credit) $absent = 0;
					
					$tempsched = $dispLogDate;
					
                    /*
                    * Total
                    */ 
                    // Absent
                    $tabsent  += $absent;
                    // Late / UT
                    if($lateutlec){
                        $tlec += $this->attcompute->exp_time($lateutlec);
                    }
					
					// Leave
					if($dispLogDate){
						$tel      += $el;
						$tvl      += $vl;
						$tsl      += $sl;
						$tol      += ($ol ? 1 : "") + ($q ? ($q == 1 ? "" : 1) : "") ;
					}
                }   // end foreach
                
                
                // total holiday
                $tholiday += $holiday;
                
                /* Overtime */
                // total regular
                if($otreg){
                    $totr += $this->attcompute->exp_time($otreg);
                }
                // total saturday
                if($otsat){
                    $totsat += $this->attcompute->exp_time($otsat);
                }
                    // total sunday
                if($otsun){
                    $totsun += $this->attcompute->exp_time($otsun);
                }
                // total holiday
                if($othol){
                    $tothol += $this->attcompute->exp_time($othol);
                }
            } // end if                  
        } // end foreach
        $tlec   = ($tlec ? $this->attcompute->sec_to_hm($tlec) : "");           
        $totr   = ($totr ? $this->attcompute->sec_to_hm($totr) : "");
        $totsat = ($totsat ? $this->attcompute->sec_to_hm($totsat) : ""); 
        $totsun = ($totsun ? $this->attcompute->sec_to_hm($totsun) : ""); 
        $tothol = ($tothol ? $this->attcompute->sec_to_hm($tothol) : "");
            
        // Save to database
        $query = $this->db->query("SELECT * FROM attendance_confirmed_nt WHERE cutoffstart='$from_date' AND cutoffend='$to_date' AND employeeid='$empid'");
        if($query->num_rows() == 0){
            $this->db->query("INSERT INTO attendance_confirmed_nt (employeeid,cutoffstart,cutoffend,otreg,otsat,otsun,othol,lateut,absent,eleave,vleave,sleave,oleave,isholiday) 
                                VALUES ('$empid','$from_date','$to_date','$totr','$totsat','$totsun','$tothol','$tlec','$tabsent','$tel','$tvl','$tsl','$tol','$tholiday')");
        }
        }
        
    }
    function confirmatt_nt($data){
        $user = $this->session->userdata("username");
        $logdate    = date("Y-m-d",strtotime($data['logdate']));
        $schedstart = date("H:i:s",strtotime($data['schedstart']));
        $schedend   = date("H:i:s",strtotime($data['schedend']));
        $timein     = ($data['timein'] != "--") ? date("H:i:s",strtotime($data['timein']))  : "";
        $timeout    = ($data['timeout'] != "--") ? date("H:i:s",strtotime($data['timeout'])) : "";
        $query = $this->db->query("SELECT logdate FROM attendance_confirmed_nt WHERE logdate='$logdate' AND schedstart='$schedstart' AND schedend='$schedend' AND timein='$timein' AND timeout='$timeout'");
        if($query->num_rows() == 0){
            $this->db->query("INSERT INTO attendance_confirmed_nt (employeeid,logdate,schedstart,schedend,timein,timeout,otreg,otsat,otsun,othol,lateut,absent,eleave,vleave,sleave,oleave) 
                                VALUES ('$user','$logdate','$schedstart','$schedend','$timein','$timeout','{$data['otr']}','{$data['otsat']}','{$data['otsun']}','{$data['othol']}','{$data['lateut']}','{$data['absent']}','{$data['eleave']}','{$data['vleave']}','{$data['sleave']}','{$data['othleave']}')");
        }
    }
    
    function checkconfirmed_att($data){
        $user = $this->session->userdata("username");
        if($this->employee->getempdatacol("teachingtype") == "teaching"){
            $query = $this->db->query("SELECT * FROM attendance_confirmed WHERE cutoffstart = '{$data['dfrom']}' AND cutoffend = '{$data['dto']}' AND employeeid='$user'");
        }else{
            $query = $this->db->query("SELECT * FROM attendance_confirmed_nt WHERE cutoffstart = '{$data['dfrom']}' AND cutoffend = '{$data['dto']}' AND employeeid='$user'");
        }
        if($query->num_rows() > 0)  return true;
        else                        return false;
    }
    
    ///< @Angelica -- nilipat ko na to (process_\saveEmployeeAttendanceSummary)
    function hrconfirmatt($data){
        $empid      = $data['empid']; 
        $tnt        = $this->employee->getempdatacol("teachingtype",$empid);
        $from_date  = $data['dfrom'];
        $to_date    = $data['dto'];
        $edata      = "NEW";
        $qdate = $this->attcompute->displayDateRange($from_date, $to_date);
        if($tnt == "teaching"){ // Teaching
            $tlec = $tlab = $tabsent = $tel = $tvl = $tsl = $tol = $tdlec = $tdlab = $el = $vl = $sl = $ol = $tempOverload = $overload = $tOverload = $lastDayOfWeek = $firstDayOfWeek = ""; 
			$tempabsent = "";
			$firstDayOfWeek = $this->attcompute->getFirstDayOfWeek($empid);
			$lastDayOfWeek = $this->attcompute->getLastDayOfWeek($empid);
			
			if(date("l",strtotime($qdate[0]->dte) != $firstDayOfWeek))
			{
				$tempOverload = $this->attcompute->getPastDayOverload($empid,$qdate[0]->dte,$firstDayOfWeek,$edata);
			}
			
            foreach ($qdate as $rdate) {
                $dispLogDate = date("d-M (l)",strtotime($rdate->dte));
                $sched = $this->attcompute->displaySched($empid,$rdate->dte);
                $countrow = $sched->num_rows();
                
                if($countrow > 0){
					$tempsched = "";
                    foreach($sched->result() as $rsched){
                        if($tempsched == $dispLogDate)  $dispLogDate = "";
                        $stime = $rsched->starttime;
                        $etime = $rsched->endtime; 
                        $type  = $rsched->leclab;
						
						//Holiday
						$holiday = $this->attcompute->isHoliday($rdate->dte); 
						
                        // logtime
                        list($login,$logout,$q) = $this->attcompute->displayLogTime($empid,$rdate->dte,$stime,$etime,$edata);
                        // Leave
                        list($el,$vl,$sl,$ol,$oltype)     = $this->attcompute->displayLeave($empid,$rdate->dte);
                        // Absent
                        $absent = $this->attcompute->displayAbsent($stime,$etime,$login,$logout,$empid,$rdate->dte);
                        if($oltype == "ABSENT") $absent = $absent;
						else if($el || $vl || $sl || $ol || $holiday) $absent = "";
                        // Late / Undertime
                        list($lateutlec,$lateutlab,$tschedlec,$tschedlab) = $this->attcompute->displayLateUT($stime,$etime,$login,$logout,$type,$absent);
						if($el || $vl || $sl || $ol || $oltype || $holiday){
							$lateutlec = $lateutlab = $tschedlec = $tschedlab = "";
						}
						
						if($holiday)
						{
							if($this->attcompute->isHolidayWithpay($rdate->dte) == "YES")
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
						
						// Service Credit
						$service_credit    = $this->attcompute->displayServiceCredit($empid,$rdate->dte);
						
						if($service_credit) $absent = 0;
						
						// Overload
						if(!$absent && !$lateutlec)
						{
							// $overload           = $this->attcompute->displayOverloadTime($stime,$etime,$login,$logout);
							// $tempOverload           += $this->attcompute->displayOverloadTime($login,$logout);
							$tempOverload           += $this->attcompute->displayOverloadTime($stime,$etime,$lateutlab);
							// var_dump(date('H:i',strtotime($overload)));
							
							// die;
						}
						else
						{
							// $overload = 0;
							$tempOverload += 0;
						}
						
						if($tempOverload > $this->attcompute->exp_time("30:00"))
						{
							$overload = $tempOverload - $this->attcompute->exp_time("30:00");
						}
                        
						$tempsched = $dispLogDate;
						
                        /*
                         * Total
                         */ 
                        // Absent
                        $tabsent  += $absent;
                        // Late / UT
                        if($lateutlec){
                            $tlec += $this->attcompute->exp_time($lateutlec);
                        }
                        if($lateutlab){
                            $tlab += $this->attcompute->exp_time($lateutlab);
                        }
                        // Deductions
                        if($tschedlec){
                            $tdlec += $this->attcompute->exp_time($tschedlec);
                        }
                        if($tschedlab){
                            $tdlab += $this->attcompute->exp_time($tschedlab);
                        }
						
						if($lastDayOfWeek == date("l",strtotime($rdate->dte)))
						{
							$tOverload += $overload;
							$overload = $tempOverload = 0;
						}
						
						 // Leave
						if($dispLogDate){
							$tel      += $el;
							$tvl      += $vl;
							$tsl      += $sl;
							$tol      += ($ol ? 1 : "") + ($q ? ($q == 1 ? "" : 1) : "") ;
						} // end if
                        
                    }   // end foreach
                   
                } // end if
            }
            $tlec = ($tlec ? $this->attcompute->sec_to_hm($tlec) : "");
            $tlab = ($tlab ? $this->attcompute->sec_to_hm($tlab) : "");
            $tdlec = ($tdlec ? $this->attcompute->sec_to_hm($tdlec) : "");
            $tdlab = ($tdlab ? $this->attcompute->sec_to_hm($tdlab) : "");
            $tOverload = ($tOverload ? $this->attcompute->sec_to_hm($tOverload) : "");
            
            // Save to database
            $query = $this->db->query("SELECT * FROM attendance_confirmed WHERE cutoffstart='$from_date' AND cutoffend='$to_date' AND employeeid='$empid'");
            if($query->num_rows() == 0){
                $this->db->query("INSERT INTO attendance_confirmed (employeeid,cutoffstart,cutoffend,overload,latelec,latelab,absent,eleave,vleave,sleave,oleave,deduclec,deduclab) 
                                    VALUES ('$empid','$from_date','$to_date','$tOverload','$tlec','$tlab','$tabsent','$tel','$tvl','$tsl','$tol','$tdlec','$tdlab')");
            }
                        
        }else{ // Non Teaching
                $totr = $totsat = $totsun = $tothol = $tlec = $tabsent = $tel = $tvl = $tsl = $tol = $tdlec = $tdlab = $el = $vl = $sl = $ol = $tholiday = $tempOverload = $overload = $tOverload = $lastDayOfWeek = ""; 
				$tempabsent = "";
				foreach ($qdate as $rdate) {
                $dispLogDate = date("d-M (l)",strtotime($rdate->dte));
                $sched = $this->attcompute->displaySched($empid,$rdate->dte);
                $countrow = $sched->num_rows();
                
                if($countrow > 0){
                    $tempsched = "";
                    $seq = "";
                    foreach($sched->result() as $rsched){
                        if($tempsched == $dispLogDate)  $dispLogDate = "";
                        if($seq == ""){$seq = 1; }else{ $seq = 2;}
                        $stime = $rsched->starttime;
                        $etime = $rsched->endtime; 
                        $type  = $rsched->leclab;
						$tstart = $rsched->tardy_start; 
                        $earlyd = $rsched->early_dismissal;
                        
                        // Holiday
                        $holiday = $this->attcompute->isHoliday($rdate->dte); 
                        
                        // logtime
                        list($login,$logout,$q) = $this->attcompute->displayLogTime($empid,$rdate->dte,$stime,$etime,$edata,$seq);
                        
                        // Overtime
                        list($otreg,$otsat,$otsun,$othol) = $this->attcompute->displayOt($empid,$rdate->dte);
                                
                        // Leave
                        list($el,$vl,$sl,$ol,$oltype)     = $this->attcompute->displayLeave($empid,$rdate->dte);
                        
                        // Absent
                        $absent = $this->attcompute->displayAbsent($stime,$etime,$login,$logout,$empid,$rdate->dte,$earlyd);
						if($oltype == "ABSENT") $absent = $absent;
						else if($el || $vl || $sl || $ol || $oltype || $holiday) $absent = "";
						 
                        // Late / Undertime
                        $lateutlec = $this->attcompute->displayLateUTNT($stime,$etime,$login,$logout,$absent,$type,$tstart);//<----ADD $type
                        if($el || $vl || $sl || $ol || $oltype || $holiday) $lateutlec = "";
						
						if($holiday)
						{
							if($this->attcompute->isHolidayWithpay($rdate->dte) == "YES")
							{
								if($tempabsent)
								{
									$absent = 0.5;
								}
							}
							else
							{
								if(!$login && !$logout)
								{
									$absent = 0.5;
								}
							}
						}
						else
						{
							$tempabsent = $absent;
						}
						
						// Service Credit
						$service_credit    = $this->attcompute->displayServiceCredit($empid,$rdate->dte);
						
						if($service_credit) $absent = 0;
						
						// Overload
						// if(!$absent && !$lateutlec)
						// {
							// $tempOverload           += $this->attcompute->displayOverloadTime($login,$logout);
						// }
						// else
						// {
							// $tempOverload += 0;
						// }
						// $lastDayOfWeek = $this->attcompute->getLastDayOfWeek($empid);
						
						// if($tempOverload > $this->attcompute->exp_time("30:00"))
						// {
							// $overload = $tempOverload - $this->attcompute->exp_time("30:00");
						// }
						
						$tempsched = $dispLogDate;
						
                        /*
                         * Total
                         */ 
                        // Absent
                        $tabsent  += $absent;
                        // Late / UT
                        if($lateutlec)  $tlec += $this->attcompute->exp_time($lateutlec);
						
						// Leave
						if($dispLogDate){
							$tel      += $el;
							$tvl      += $vl;
							$tsl      += $sl;
							$tol      += ($ol ? 1 : "") + ($q ? ($q == 1 ? "" : 1) : "") ;
						}
                    }   // end foreach
                    
                    
                    // total holiday
                    $tholiday += $holiday;
                    
                    /* Overtime */
                    // total regular
                    if($otreg){
                        $totr += $this->attcompute->exp_time($otreg);
                    }
                    // total saturday
                    if($otsat){
                        $totsat += $this->attcompute->exp_time($otsat);
                    }
                    // total sunday
                    if($otsun){
                        $totsun += $this->attcompute->exp_time($otsun);
                    }
                    // total holiday
                    if($othol){
                        $tothol += $this->attcompute->exp_time($othol);
                    }
					
					// if($lastDayOfWeek == date("l",strtotime($rdate->dte)))
					// {
						// $tOverload += $overload;
						// $overload = $tempOverload = 0;
					// }
                } // end if                  
                } // end foreach
            $tlec   = ($tlec ? $this->attcompute->sec_to_hm($tlec) : "");       
            $totr   = ($totr ? $this->attcompute->sec_to_hm($totr) : "");
            $totsat = ($totsat ? $this->attcompute->sec_to_hm($totsat) : ""); 
            $totsun = ($totsun ? $this->attcompute->sec_to_hm($totsun) : ""); 
            $tothol = ($tothol ? $this->attcompute->sec_to_hm($tothol) : "");
            // $tOverload = ($tOverload ? $this->attcompute->sec_to_hm($tOverload) : "");
            
            // Save to database
            $query = $this->db->query("SELECT * FROM attendance_confirmed_nt WHERE cutoffstart='$from_date' AND cutoffend='$to_date' AND employeeid='$empid'");
            if($query->num_rows() == 0){
                $this->db->query("INSERT INTO attendance_confirmed_nt (employeeid,cutoffstart,cutoffend,otreg,otsat,otsun,othol,lateut,absent,eleave,vleave,sleave,oleave,isholiday) 
                                    VALUES ('$empid','$from_date','$to_date','$totr','$totsat','$totsun','$tothol','$tlec','$tabsent','$tel','$tvl','$tsl','$tol','$tholiday')");
            }
        }
        return " Cut-Off from ".date("F d, Y",strtotime($from_date))." to ".date("F d, Y",strtotime($to_date)).", ".count($tnt)."    "." Employees are successfully confirmed!."; 
    }
	
	//Added 5/11/2017
	function hrunconfirmatt($data){
        $empid      = $data['empid']; 
        $from_date  = $data['dfrom'];
        $to_date    = $data['dto'];
        $tnt    = $data['tnt'];
		$tbl = "";
		if($tnt == "teaching") $tbl = "attendance_confirmed";
		else  $tbl = "attendance_confirmed_nt";
        
		$query = $this->db->query("DELETE FROM $tbl WHERE `employeeid` = '{$empid}' AND `cutoffstart` = '{$from_date}' AND `cutoffend` = '{$to_date}'");
		
        return " Cut-Off from ".date("F d, Y",strtotime($from_date))." to ".date("F d, Y",strtotime($to_date))." for employee no. $empid is successfully unconfirmed!."; 
    }
    
    function payrollconfirm($data){
        $result=mysql_query("SELECT count(employeeid) as count from attendance_confirmed");
        $tnt        = $data['tnt'];
        $from_date  = $data['dfrom'];
        $to_date    = $data['dto'];
        $empid      = $data['eid'];
        $ins = $return = "";
        
        if($tnt == "teaching"){

            $ins = $this->db->query("UPDATE attendance_confirmed SET status='SUBMITTED' WHERE employeeid='$empid' AND cutoffstart='$from_date' AND cutoffend='$to_date'"); 

            $this->db->query("UPDATE cutoff SET TPostedDate=NOW() WHERE CutoffFrom='$from_date' AND CutoffTo='$to_date'");  
        }else{  // Non Teaching

            $ins = $this->db->query("UPDATE attendance_confirmed_nt SET status='SUBMITTED' WHERE employeeid='$empid' AND cutoffstart='$from_date' AND cutoffend='$to_date'"); 

            $this->db->query("UPDATE cutoff SET NTPostedDate=NOW() WHERE CutoffFrom='$from_date' AND CutoffTo='$to_date'");
        }

        if($ins)  $return = array(1,"Cut-Off from ".date("F d, Y",strtotime($from_date))." - ".date("F d, Y",strtotime($to_date)).",".count($result)." "." Employees are successfully finalized!");
        else      $return = array(0,"Finalize Failed!.");

        return json_encode($return); 
    }
    
    function showFinalize($dfrom,$dto,$tnt,$campus=""){
        $where_clause = "";
        // if($campus) $where_clause = " AND campusid = '$campus'";
        if($tnt == "teaching"){
            $query = $this->db->query("SELECT * FROM attendance_confirmed WHERE cutoffstart='$dfrom' AND cutoffend='$dto' AND status='SUBMITTED' $where_clause");
        }
        else{
            $query = $this->db->query("SELECT * FROM attendance_confirmed_nt WHERE cutoffstart='$dfrom' AND cutoffend='$dto' AND status='SUBMITTED' $where_clause");
        }
        if($query->num_rows() == 0)  return false;
        else                         return true;
    }
    
    function addAbsent($data){
        $user       = $this->session->userdata("username");
        $sjob       = $data['sjob'];
        $eid        = $data['eid'];
        $scheddate  = ($data['scheddate'] ? $data['scheddate'] : "");
        $schedstart = ($data['schedstart'] ? $data['schedstart'] : "");
        $schedend   = ($data['schedend'] ? $data['schedend'] : "");
        $absent     = ($data['absent'] ? $data['absent'] : "");
        $idkey      = ($data['idkey'] ? $data['idkey'] : "");
        if($sjob == "addAbsent"){
            $query = $this->db->query("INSERT INTO attendance_absent_checker VALUES ('','$eid','$scheddate','$schedstart','$schedend','$absent','$user',CURRENT_TIMESTAMP)");
        }else{
            $query = $this->db->query("DELETE FROM attendance_absent_checker WHERE id='$idkey'");
        }
        return $return;
    }
    
    function checktagAbsent($eid,$scheddate,$schedstart,$schedend){
        $id = "";
        $query = $this->db->query("SELECT * FROM attendance_absent_checker WHERE employeeid='$eid' AND scheddate='$scheddate' AND schedstart='$schedstart' AND schedend='$schedend'");
        if($query->num_rows() > 0)  $id = $query->row(0)->id;
        return $id;
    }
    
    // Process the request
    function requestsched($data){
        $msg = "";
        if($data["timesched"]){
              $eid = $data["eids"];  
              $dtime = $data["dfrom"]." 00:00:00";
              $head = $this->session->userdata("username");
              $user = $this->session->userdata("userid");
              $sched_list = explode("|",$data["timesched"]);
              
              $qdept  = $this->db->query("SELECT deptid FROM employee WHERE employeeid='$head'");
              $deptid = ($qdept->num_rows() > 0 ? $qdept->row(0)->deptid : "");
                
              if($deptid){
                $qdhead = $this->db->query("SELECT head,divisionhead FROM code_office WHERE code='$deptid'");
                $chead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->divisionhead : "");                 // Cluster head
                $qhrd   = $this->db->query("SELECT head FROM code_office WHERE code='HR'");
                $hrd    = ($qhrd->num_rows() > 0 ? $qhrd->row(0)->head : "");                             // HR Director
                if($chead){
                    
                    foreach(explode(",",$eid) as $empid){
                      /** Insert new data */
                      if($empid == $head)
                        $this->db->query("INSERT INTO change_schedule_request (employeeid,dhead,chead,hrd,isread) VALUES ('$empid','$head','$chead','$hrd',1)");
                      else
                        $this->db->query("INSERT INTO change_schedule_request (employeeid,dhead,chead,hrd) VALUES ('$empid','$head','$chead','$hrd')");
                      $query = $this->db->query("SELECT * FROM change_schedule_request WHERE employeeid='$empid' ORDER BY id DESC LIMIT 1");
                      if($query->num_rows() > 0){
                        $id = $query->row(0)->id;
                        if(!empty($chead))  $this->db->query("INSERT INTO change_schedule_request_chead (id,employeeid,head) VALUES ('$id','{$empid}','{$chead}')");
                          foreach($sched_list as $slist){
                            $nosched = 0;
                            $halfsched = 0;
                            list($dow,$idx,$tsched,$tardy,$absent,$halfabsent,$earlyd,$leclab) = explode("~u~",$slist);
                              $extsched = explode("-",$tsched);
                              $start_time = date("H:i:s",strtotime($extsched[0]));
                              $end_time = date("H:i:s",strtotime($extsched[1]));
                              $tardy = $tardy ? date("H:i:s",strtotime($tardy)) : "";
                              $absent = $absent ? date("H:i:s",strtotime($absent)) : "";
                              $halfabsent = $halfabsent ? date("H:i:s",strtotime($halfabsent)) : "";
                              $earlyd = $earlyd ? date("H:i:s",strtotime($earlyd)) : "";
                              $this->db->query("INSERT INTO change_schedule(id,employeeid, starttime, endtime, dayofweek, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab, editedby, dateedit) 
        			                             VALUES('$id','$empid','$start_time','$end_time','$dow','$idx','$tardy','$absent','$halfabsent','$earlyd','$leclab','$user','$dtime')");
                          }
                      }
                    }
                $msg = "Schedule Successfully Requested!.";
                }else
                $msg = "Ooops, Failed! Your department has no cluster head.";
            }
        }
        return $msg;
    }
    function approvedSched($data){
        $msg    = "";
        $id     = $data['id'];
        $status = $data['status'];
        
        $qdhead = $this->db->query("SELECT head FROM change_schedule_request_chead WHERE id='$id'");
        $chead  = ($qdhead->num_rows() > 0 ? $qdhead->row(0)->head : "");                 // Cluster head                
        $qhrd   = $this->db->query("SELECT head FROM code_office WHERE code='HR'");
        $hrd    = ($qhrd->num_rows() > 0 ? $qhrd->row(0)->head : "");                             // HR Director
        if($chead == $this->session->userdata("username")){
            if($hrd){
                $query = $this->db->query("SELECT * FROM change_schedule_request WHERE id='$id'");
                if($query->num_rows() > 0){
                 if($status != "DISAPPROVED")
                 $this->db->query("INSERT INTO change_schedule_request_hrd (id,employeeid,head) VALUES ('$id','".$query->row(0)->employeeid."','".$query->row(0)->hrd."')");
                 $this->db->query("UPDATE change_schedule_request SET cheadstatus='$status',cheaddate=CURRENT_DATE WHERE id='$id'");
                 $this->db->query("UPDATE change_schedule_request_chead SET status='$status' WHERE id='$id'");
                 $msg = "Success! Status now is : $status";   
                }
            }else$msg = "Failed to approve!. Hr Director does not exist..";    
        }else{
            if($status == "APPROVED"){
                $query = $this->db->query("SELECT employeeid FROM change_schedule_request_hrd WHERE id='$id'");
                if($query->num_rows() > 0){
                    $eid = $query->row(0)->employeeid;
                    $this->db->query("DELETE FROM employee_schedule WHERE employeeid = '$eid'");
                    $this->db->query("INSERT INTO employee_schedule(employeeid, starttime, endtime, dayofweek, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab,editedby,dateedit) 
			                             (SELECT employeeid, starttime, endtime, dayofweek, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab,editedby,CONCAT((DATE(dateedit)-INTERVAL 1 DAY),' 00:00:00') FROM change_schedule WHERE employeeid='{$eid}' AND id='$id')");
                    $this->db->query("INSERT INTO employee_schedule_history(employeeid, starttime, endtime, dayofweek, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab,changeby,dateactive) 
			                             (SELECT employeeid, starttime, endtime, dayofweek, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab,editedby,CONCAT((DATE(dateedit)-INTERVAL 1 DAY),' 00:00:00') FROM change_schedule WHERE employeeid='{$eid}' AND id='$id')");
                }
                $this->db->query("UPDATE change_schedule_request SET isread=0 WHERE id='$id'");
            }
            $this->db->query("UPDATE change_schedule_request SET hrdstatus='$status',status='$status',hrddate=CURRENT_DATE WHERE id='$id'");
            $this->db->query("UPDATE change_schedule_request_hrd SET status='$status' WHERE id='$id'");
            $msg = "Success! Status now is : $status";
        }
        return $msg;
    }
    
    function modify_offbus_time($data){
        $return = "";
        foreach($data as $key=>$val)    $$key = $val;
        
        if($newtfrom != $oldtfrom || $newtto != $oldtto){
            $newtfrom   = date("H:i:s",strtotime($newtfrom));
            $newtto     = date("H:i:s",strtotime($newtto));
            $oldtfrom   = date("H:i:s",strtotime($oldtfrom));
            $oldtto     = date("H:i:s",strtotime($oldtto));
            $this->db->query("INSERT INTO off_business_timehistory (aid,employeeid,timein,timeout,newtimein,newtimeout,user) VALUES ('$aid','$eid','$oldtfrom','$oldtto','$newtfrom','$newtto','".$this->session->userdata("username")."')");
            $this->db->query("UPDATE leave_app SET timefrom='$newtfrom', timeto='$newtto' WHERE id='$aid' AND employeeid='$eid'");
            $this->db->query("UPDATE leave_app_dhead SET timefrom='$newtfrom', timeto='$newtto' WHERE aid='$aid' AND employeeid='$eid'");
            $this->db->query("UPDATE leave_app_chead SET timefrom='$newtfrom', timeto='$newtto' WHERE aid='$aid' AND employeeid='$eid'");
            $this->db->query("UPDATE leave_app_uphy SET timefrom='$newtfrom', timeto='$newtto' WHERE aid='$aid' AND employeeid='$eid'");
            $this->db->query("UPDATE leave_app_hrd SET timefrom='$newtfrom', timeto='$newtto', otimefrom='$oldtfrom', otimeto='$oldtto' WHERE aid='$aid' AND employeeid='$eid'");
            $return = "Successfully Saved!.";
        }else{
            $return = "Nothing changes in time..";
        }
        
        return $return;
    }
    
    function offBuss_daytime($id="",$dfrom="",$dto=""){
        $wC = "";
        if($id)             $wC = " WHERE userid='$id'";
        if($dfrom && $dto)  $wC .= " AND DATE(timein) BETWEEN '$dfrom' AND '$dto'";  
        $query = $this->db->query("SELECT * FROM timesheet $wC");
        return $query;
    }
    
    function changepass($data){
        $return = "";
        $user = $this->session->userdata("username");
        $oldpass = $data['oldpass'];
        $newpass = $data['newpass'];
        $retpass = $data['retpass'];
        $is_activation = isset($data["activation"]) ? true : false;
        if($newpass == $retpass){
            $query = $this->db->query("SELECT * FROM user_info WHERE username='$user' ");
            if($query->num_rows() > 0){
                $is_activated = $query->row()->activated;
                $lastname = $query->row()->lastname;
                $hash_pass = $query->row()->password;
                if(password_verify($oldpass, $hash_pass) || ($hash_pass == "" && $oldpass == $lastname)){
                    $timestamp = $this->extensions->getServerTime();
                    $retpass = password_hash($retpass, PASSWORD_DEFAULT);
                    $this->db->query("UPDATE user_info SET password='$retpass', is_password_updated = TRUE WHERE username='$user'");

                    // // UPDATE ALSO THE OTHER CAMPUS ACCOUNT
                    // $db_campus_list = Globals::campusDatabase();
                    // foreach($db_campus_list as $db_campus){
                    //     $this->db->query("UPDATE $db_campus.user_info SET password='$retpass' WHERE username='$user'");
                    // }

                    // echo "<pre>";print_r($this->db->last_query());die;
                    $this->session->set_userdata("logged_in", TRUE);
                    $return = "Successfully Saved!.";

                    // RUN ONLY THIS IF ACCOUNT IS FROM NOT ACTIVATED
                    if($is_activated == 0){
                        $this->db->query("UPDATE user_info SET activated='1', log_count = '1', log_date = '$timestamp', activation_stamp = '$timestamp' WHERE username='$user'");
                        $return = "activated";

                        // // ACTIVATE ALSO THE OTHER CAMPUS ACCOUNT
                        // foreach($db_campus_list as $db_campus){
                        //     $this->db->query("UPDATE $db_campus.user_info SET activated='1', log_count = '1', log_date = '$timestamp', activation_stamp = '$timestamp' WHERE username='$user'");
                        // }

                        //init_activateAccountAcrossCampus_api
                        $status = 1;
                        if(getenv('ENVIRONMENT') == 'Production' || getenv('ENVIRONMENT') == 'Staging'){
                            $this->activateAccountAcrossCampuses($user,$status,$retpass);
                        }

                    }

                    $loginTrail = array();
                    $name = $query->row()->lastname.", ".$query->row()->firstname." ". $query->row()->middlename;
                    $loginTrail['ip'] = $_SERVER['REMOTE_ADDR'];
                    $loginTrail['status'] = "success";
                    $loginTrail['username'] = $user;
                    $loginTrail['userid'] = $query->row()->id;
                    $loginTrail['mac'] = $this->extras->returnmacaddress($_SERVER['REMOTE_ADDR']);
                    $loginTrail['name'] = ($name)? $name:"Not Registered User";
                    $loginTrail['device'] = $_SERVER['HTTP_USER_AGENT'];
                    // Insert Trail

                    $this->extras->insertLoginTrails($loginTrail);
                    if($is_activation) $this->activate_linked_account($query->row()->linked, $retpass);

                }else{
                    $return = "Incorrect Current Password..";
                }
            }
        }else{
            $return = "Password did not matched!.";
        }
        return $return;
    }
    
    function activate_linked_account($user, $retpass){

        $query = $this->db->query("SELECT * FROM user_info WHERE username='$user' ");
        if($query->num_rows() > 0){
            $timestamp = $this->extensions->getServerTime();
            $retpass = password_hash($retpass, PASSWORD_DEFAULT);
            $this->db->query("UPDATE user_info SET password='$retpass', activated='1', log_count = 1, log_date = '$timestamp', activation_stamp = '$timestamp' WHERE username='$user'");
            $return = "Successfully Saved!.";

            $loginTrail = array();
            $name = $query->row()->lastname.", ".$query->row()->firstname." ". $query->row()->middlename;
            $loginTrail['ip'] = $_SERVER['REMOTE_ADDR'];
            $loginTrail['status'] = "success";
            $loginTrail['username'] = $user;
            $loginTrail['userid'] = $query->row()->id;
            $loginTrail['mac'] = $this->extras->returnmacaddress($_SERVER['REMOTE_ADDR']);
            $loginTrail['name'] = ($name)? $name:"Not Registered User";
            $loginTrail['device'] = $_SERVER['HTTP_USER_AGENT'];
            // Insert Trail

            $this->extras->insertLoginTrails($loginTrail);
        }    
    }
    
	//Added 6-8-2017
    function addppass($data){
        $return = "";
        $user = $this->session->userdata("username");
        $newpass = $data['newpass'];
        $query = $this->db->query("SELECT * FROM user_info WHERE username='$user'");
        if($query->num_rows() > 0)
            $this->db->query("UPDATE user_info SET ppass='$newpass' WHERE username='$user'");
        $return = "Successfully Saved!.";
        return $return;
    }
	
	function changeppass($data){
        $return = "";
        $user = $this->session->userdata("username");
        $oldpass = $data['oldpass'];
		$newpass = $data['newpass'];
        $retpass = $data['retpass'];
		if($newpass == $retpass){
			$query = $this->db->query("SELECT * FROM user_info WHERE username='$user' AND ppass='{$oldpass}'");
			if($query->num_rows() == 1){
				$this->db->query("UPDATE user_info SET ppass='$newpass' WHERE username='$user'");
				$return = "Successfully Saved!.";
			}else
				$return = "Incorrect Current Password..";
		}else   $return = "Password did not matched!.";
        return $return;
    }

    function saveemployeeeducation($employeeid,$user,$datas){
        list($eb_school,$eb_level,$eb_course,$eb_units,$eb_date_graduated,$eb_honors,$eb_datefrom,$eb_dateto) = explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_education (employeeid,school,educational_level,course,units,date_graduated,honors,datefrom,dateto,modified_by)
                                VALUES ('{$employeeid}','{$eb_school}','{$eb_level}','{$eb_course}','{$eb_units}','{$eb_date_graduated}','{$eb_honors}','{$eb_datefrom}','{$eb_dateto}','{$user}')
                            ");
// print_r($this->db->last_query()); die;
        return $query;
    }

    function saveEmployeeEligibilities($employeeid,$datas){
        list($el_description,$el_licence_number,$el_date,$el_expired,$el_remark) = explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_eligibilities (employeeid,date_issued,educ_level,date_expired,remarks,license_number)
                                VALUES ('{$employeeid}','{$el_date}','{$el_description}','{$el_expired}','{$el_remark}','{$el_licence_number}')
                            ");
      
        return $query;
    }

    function saveEmployeeSCTT($employeeid,$datas){
        list($el_subj_id,$el_remarks) = explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_subj_competent_to_teach (employeeid,subj_id,remarks)
                                VALUES ('{$employeeid}','{$el_subj_id}','{$el_remarks}')
                            ");
      
        return $query;
    }

    function saveEmployeeOT($employeeid,$datas){
        list($el_skills,$el_proficiency) = explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_credentials (employeeid,skills,profiency)
                                VALUES ('{$employeeid}','{$el_skills}','{$el_proficiency}')
                            ");
      
        return $query;
    }

    function saveEmployeePts($employeeid,$user,$datas){
        // $title = $datef = $organizer = $venue = "";
        list($title,$datef,$organizer,$venue) = explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_pts (employeeid , title, datef , organizer , `venue` , modified_by)
                                VALUES ('{$employeeid}','{$title}','{$datef}','{$organizer}','{$venue}','{$user}')
                            ");
        return $query;

    }

    function saveEmployeePts_pdp1($employeeid,$user,$datas){
        // $title = $datef = $organizer = $venue = "";
        list($title,$datef,$organizer,$venue) = explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_pts_pdp1 (employeeid , title, datef , organizer , `venue` , modified_by)
                                VALUES ('{$employeeid}','{$title}','{$datef}','{$organizer}','{$venue}','{$user}')
                            ");
        return $query;
    }

    function saveEmployeePgd($employeeid,$user,$datas){
        list($publication,$title,$publisher,$type,$date) = explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_pgd (employeeid,publication,title,publisher,`type`,datef,modified_by)
                                VALUES ('{$employeeid}','{$publication}','{$title}','{$publisher}','{$type}','{$date}','{$user}')
                            ");
        return $query;
    }
    function saveEmployeeResearches($employeeid,$user,$datas){
        list($date,$educ_level,$title) = explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_researches (employeeid,educ_level,date_published,title,modified_by,modified_on)
                                VALUES ('{$employeeid}','{$educ_level}','{$date}','{$title}','{$user}','".date('Y-m-d h:i:s')."')
                            ");
        return $query;
    }

    function saveEmployeeAwardsRecog($employeeid,$user,$datas){
        list($award,$institution,$address,$date) = explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_awardsrecog (employeeid,award,institution,address,datef,modified_by)
                                VALUES ('{$employeeid}','{$award}','{$institution}','{$address}','{$date}','{$user}')
                            ");
        return $query;
    }

    function saveEmployeeScholarship($employeeid,$user,$datas){
        list($type_of_scho,$gr_agency,$prog_study,$ins_scho,$datef,$dateto) = explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_scholarship (employeeid,type_of_scho,gr_agency,prog_study,ins_scho,datef,dateto,modified_by)
                                VALUES ('{$employeeid}','{$type_of_scho}','{$gr_agency}','{$prog_study}','{$ins_scho}','{$datef}','{$dateto}','{$user}')
                            ");
        return $query;
    }

    function saveEmployeeScs($employeeid,$user,$datas){
        list($sm_school,$educ_level,$sm_year_graduated,$sm_honor,$sm_type,$sm_location) = explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_scs (employeeid,educ_level,school,honor,year_graduated,modified_by,modified_on,`type`,location)
                                VALUES ('{$employeeid}','{$educ_level}','{$sm_school}','{$sm_honor}','{$sm_year_graduated}','{$user}','".date('Y-m-d h:i:s')."','{$sm_type}','{$sm_location}')
                            ");
        return $query;
    }

    function saveEmployeeTraining($employeeid,$user,$datas){
        list($sm_school,$educ_level,$sm_year_graduated,$sm_honor,$sm_type,$sm_location)= explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_workshops (employeeid,educ_level,school,honor,year_graduated,modified_by,modified_on,`type`,location)
                                VALUES ('{$employeeid}','{$educ_level}','{$sm_school}','{$sm_honor}','{$sm_year_graduated}','{$user}','".date('Y-m-d h:i:s')."','{$sm_type}','{$sm_location}')
                            ");
        return $query;
    }

    function saveEmployeeResource($employeeid,$user,$datas){
        list($datef,$topic,$organizer,$venue)= explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_resource (employeeid,datef,topic,organizer,venue,modified_by)
                                VALUES ('{$employeeid}','{$datef}','{$topic}','{$organizer}','{$venue}','{$user}')
                            ");
        
        return $query;
    }

    function saveEmployeeCommunity($employeeid,$user,$datas){
                var_dump($query); die;

                $school = $educational_level = $year_grad = $honor = $ctype ="";

        list($school,$educational_level,$year_grad,$honor,$ctype)= explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_community (employeeid,school,educational_level,year_grad,honor,ctype,modified_by)
                                VALUES ('{$employeeid}','{$school}','{$educational_level}','{$year_grad}','{$honor}','{$ctype}','{$user}')
                            ");
        
        return $query;
    }

    function saveEmployeeOrganization($employeeid,$user,$datas){
         list($name_org,$datef,$position)= explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_proorg (employeeid,name_org,datef,position,modified_by)
                                VALUES ('{$employeeid}','{$name_org}','{$datef}','{$position}','{$user}')
                            ");
        return $query;
    }

    function saveEmployeeAdministrative($employeeid,$user,$datas){
         list($positionf,$department,$datef)= explode("~u~",$datas);
        $query="";
        $query   = $this->db->query("
                            INSERT INTO employee_administrative (employeeid,positionf,department,datef,modified_by)
                                VALUES ('{$employeeid}','{$positionf}','{$department}','{$datef}','{$user}')
                            ");
        
        return $query;
    }
	
	//Added 5/8/2017
	function employeestatusupdatenotif(){
        $query = $this->db->query("SELECT * FROM code_status ORDER BY seqno ASC");
		return $query;
    }
	
	function employeestatusupdatenotifcontent($code = "",$duration = 0,$isCount=false){
        $where = $dwhere = "";
        if($code != "") $where = "AND employmentstat = '{$code}'";
        if($duration>0 && $isCount) $duration -= 1; // get daw mga count ng mga 1 month before due date 
        if($duration != 0) $dwhere = " AND `dateposition` <= DATE(NOW() - INTERVAL {$duration} MONTH)";
        $select = $isCount? "COUNT(employeeid) AS count" : "*";
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (deptid, '$utdept') OR FIND_IN_SET (office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND employeeid = 'nosresult'";
            $usercampus = $this->extras->getCampusUser();
            $utwc .= " AND FIND_IN_SET (campusid,'$usercampus') ";
        }
        $dwhere .= $utwc;
        // AND (dateresigned2 = '0000-00-00' OR dateresigned2 = '1970-01-01' OR dateresigned2 IS NULL) REMOVE FOR HR REPORT SAME RESULT
        // $query = $this->db->query("SELECT * FROM employee WHERE 1 $dwhere  AND isactive = '1' {$where} AND employeeid = '2023-001'");//DATE_ADD(NOW(), INTERVAL -{$duration} MONTH)
        $query = $this->db->query("SELECT $select FROM employee WHERE 1 $dwhere  AND isactive = '1' {$where}");
        // echo "<pre>"; print_r($this->db->last_query()); die;
        return $query;  
    }
	
    function employeestatusupdatenotifcount(){
        $count = 0;
        $code_status = $this->employeestatusupdatenotif();
        if($code_status && $code_status->num_rows()){
            foreach( $code_status->result() as $row){
                $code=$row->code;
                $duration=$row->duration;
                if($duration==0 || $duration=='')continue;
                $where = $dwhere = "";
                if($code != "") $where = "AND employmentstat = '{$code}'";
                $duration -= 1;
                if($duration) $dwhere = " AND `dateposition` <= DATE(NOW() - INTERVAL {$duration} MONTH)";
                $utwc = '';
                $utdept = $this->session->userdata("department");
                $utoffice = $this->session->userdata("office");
                if($this->session->userdata("usertype") == "ADMIN"){
                  if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (deptid, '$utdept')";
                  if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (office, '$utoffice')";
                  if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (deptid, '$utdept') OR FIND_IN_SET (office, '$utoffice'))";
                  if(!$utdept && !$utoffice) $utwc =  " AND employeeid = 'nosresult'";
                    $usercampus = $this->extras->getCampusUser();
                    $utwc .= " AND FIND_IN_SET (campusid,'$usercampus') ";
                }
                $dwhere .= $utwc;
                $query = $this->db->query("SELECT COUNT(employeeid) AS count FROM employee WHERE 1 $dwhere  AND isactive = '1' {$where}");
                $count += $query->row()->count;
            }
        }        
        return $count;
    }
	
	function employeedeficiencynotif($division="",$office="",$concerned_dept="",$status="",$notif=false, $department="", $campus="", $company="", $tnt="", $empstatus=""){
		$datetoday = date("Y-m-d");
		$wC ="";
		if($division) $wC .= " AND b.managementid = '$division' ";
		if($office) $wC .= " AND b.office = '$office' ";
        if($department) $wC .= " AND b.deptid = '$department' ";
        if($campus && ($campus != 'All' && $campus != 'all')) $wC .= " AND b.campusid = '$campus' ";
        if($company  && ($company != 'All' && $company != 'all')) $wC .= " AND b.company_campus = '$company' ";
        if($tnt) $wC .= " AND b.teachingtype = '$tnt' ";
		if($concerned_dept) $wC .= " AND a.concerned_dept = '$concerned_dept' ";
		if($status){
			if($status == "Completed") $wC .= " AND a.is_completed = 1 ";
			else if($status == "Incomplete") $wC .= " AND a.is_completed = 0";
			else if($status == "Both") $wC .= "";
		}

        if($empstatus != "all"){
          if($empstatus=="1"){
            $wC .= " AND (('$datetoday' < b.dateresigned2 OR b.dateresigned2 = '0000-00-00' OR b.dateresigned2 = '1970-01-01' OR b.dateresigned2 IS NULL) AND b.isactive ='1')";
          }
          if($empstatus=="0"){
            $wC .= " AND (('$datetoday' >= b.dateresigned2 AND b.dateresigned2 IS NOT NULL AND b.dateresigned2 <> '0000-00-00' AND b.dateresigned2 <> '1970-01-01' ) OR b.isactive = '0')";
          }
          if(is_null($empstatus)){
            $wC .= " AND b.isactive = '1' AND (b.dateresigned2 = '0000-00-00' OR b.dateresigned2 = '1970-01-01' OR b.dateresigned2 IS NULL)";
          }
        }
		
		if(!$wC) $wC = " AND a.submission_date <= '{$datetoday}' AND a.is_completed = 0";
		if($notif) { $wC .=" AND a.submission_date != '0000-00-00'";}
		// if($wC) $wC = "AND " . $wC;

		$utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= "   FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= "   FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = "   (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  "AND  b.employeeid = 'nosresult'";
          $usercampus = $this->extras->getCampusUser();
          $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }
        // if($wC && $utwc) { $wC .=" AND ";}
        $wC .= $utwc;
		
		$query = $this->db->query("SELECT a.*,CONCAT(b.lname,', ',b.fname,' ',b.mname) as fullname,c.description as department,d.description as deficiency_desc
		FROM employee_deficiency a
		INNER JOIN employee b on a.employeeid = b.employeeid
		INNER JOIN code_office c on a.concerned_dept = c.code
		INNER JOIN code_deficiency d on a.def_id = d.id WHERE 1
		$wC"); //AND a.submission_date != '0000-00-00'
        // echo "<pre>"; print_r($this->db->last_query()); die;
		return $query;
		
	}
	
	//Added 6-6-2017 Display cutoff by month and year
	function displayCutOffByMonthAndYear(){
        $opt = array();
        $query = $this->db->query("SELECT * FROM payroll_cutoff_config ORDER BY Timestamp ASC");
        foreach($query->result() as $row){
			if(!in_array(date("F Y",strtotime($row->startdate)),$opt))
			{
				array_push($opt,date("F Y",strtotime($row->startdate)));
			}
        }
        return $opt;
    }
	
	//Added 6-8-17 DETECT IF PPASS EXIST
	function existPayslipPassword($username = ""){
		$return="";
		$query = $this->db->query("SELECT ppass FROM user_info WHERE username = '{$username}'")->result();
		if($query[0]->ppass)	$return = True;
		else $return = False;
		return $return;
	}
	
	//Added 6-8-17 VERIFY PASSWORD FOR PAYSLIP
	function verifyPayslipPassword($username = "", $password = ""){
		$return="";
        $password = md5($password);
		$query = $this->db->query("SELECT ppass FROM user_info WHERE username = '{$username}' and ppass='$password'");
		if($query->num_rows() > 0)	$return = "SUCCESS";
		else $return = "FAILED";
		return $return;
	}

    // new condition added for #ica-21090 by justin (with e)
    function findTimeRecord($data){
        $sql = "SELECT * FROM timesheet WHERE userid='{$data['empid']}' AND (timein LIKE '%{$data['cdate']}%' OR timeout LIKE '%{$data['cdate']}%')";
        $query = $this->db->query($sql);

        if($query->num_rows() == 0){
            $query = $this->db->query("SELECT '' AS timeid, timein, '0000-00-00 00:00:00' AS timeout FROM timesheet_history WHERE userid='{$data['empid']}' AND timein LIKE '%{$data['cdate']}%'");
        }
        return $query->result();
    }

    function saveTimeRecordModel($data){
        extract($data);
        $query = $this->db->query("INSERT INTO leave_app_ti_to (aid, tid, cdate, actual_time, request_time, status) VALUES ('$aid', '$tid', '$cdate', '$actual', '$request', '$status')");
    }

    function findApplyTimeRecord($aid){
        //$sql = "SELECT * FROM leave_app_ti_to WHERE aid='{$aid}'";
        $sql = "SELECT a.*,DATE_FORMAT(b.`timestamp`, '%Y-%m-%d') AS correctiondate FROM leave_app_ti_to a LEFT JOIN ob_app_emplist b ON b.base_id = a.aid WHERE aid='$aid' GROUP BY tid";
        return $this->db->query($sql)->result();
    }
    function findApplyTimeRecordDate($aid){
        //$sql = "SELECT * FROM leave_app_ti_to WHERE aid='{$aid}'";
        $sql = "SELECT a.*,DATE_FORMAT(b.`timestamp`, '%Y-%m-%d') AS correctiondate FROM leave_app_ti_to a LEFT JOIN ob_app_emplist b ON b.base_id = a.aid WHERE aid='{$aid}' GROUP BY aid";
        if($this->db->query($sql)->num_rows() > 0){
            return $this->db->query($sql);
        }else{
            return false;
        }
    }

    function saveFinalTimeRecord($data, $job){
        $sql = "";

        if($job == 1){
            // disapproved
            $sql = "UPDATE leave_app_tito SET status='{$data['status']}', actual_timein='{$data['timein']}', actual_timeout='{$data['timeout']}' WHERE aid='{$data['aid']}' AND tid='{$data['tid']}'";
            $this->db->query($sql);

        }else{
            // change
            $userid  = $this->db->query("SELECT employeeid FROM leave_app WHERE id='{$data['aid']}'")->row()->employeeid;
            $timein  = date('Y-m-d H:i:s', strtotime($data['cdate']." ".$data['timein']));
            $timeout = date('Y-m-d H:i:s', strtotime($data['cdate']." ".$data['timeout']));
            
            $sql = "SELECT * FROM timesheet WHERE timeid='{$data['tid']}'";
            $result = $this->db->query($sql)->result();
            if(count($result) > 0){
                $sql = "UPDATE timesheet SET timein='$timein', timeout='$timeout' WHERE timeid='{$data['tid']}'";
                $this->db->query($sql);
            }else{
                               
                $sql = "INSERT INTO timesheet (`userid`, `timein`, `timeout`) VALUES ('$userid','$timein','$timeout')";
                $this->db->query($sql);
            }
        }
        return $sql;
    }
    // end of new condition added for #ica-21090 by justin (with e)

    # for ica-hyperion 21194 & 21196
    # by justin (with e)
    # > direct approved..
    function saveTimeRecordInTimesheet($data){
        
        # > get request time, then explode to get time in/out
        $time = explode(" - ", $data["request"]);
        $tfrom = date("Y-m-d H:i:s",strtotime($data["cdate"] ." ". $time[0]));
        $tto = date("Y-m-d H:i:s",strtotime($data["cdate"] ." ". $time[1]));

        # > tId error.. 
        # > for ica-hyperion 21406
        $tid = '';
        if(!$tid) $tid = $data["tid"];

        # > get user
        $userid = $this->db->query("SELECT employeeid FROM ob_app_emplist WHERE base_id='{$data["aid"]}'")->row()->employeeid;

        # > add, update and delete
        if($data["status"] == "NEW"){
            $this->db->query("INSERT INTO timesheet (userid, timein, timeout) VALUES ('{$userid}','{$tfrom}','{$tto}')");
        }
        if($data["status"] == "REMOVED"){
            $this->db->query("DELETE FROM timesheet WHERE timeid='{$tid}'");
        }
        if($data["status"] == "UPDATED"){
            $check_if_existing = $this->db->query("SELECT * FROM timesheet WHERE DATE_FORMAT(timein, '%Y-%m-%d') = '{$data['cdate']}' AND userid = '{$userid}' ");
            if($check_if_existing->num_rows == 0) $this->db->query("INSERT INTO timesheet (userid, timein, timeout) VALUES ('{$userid}','{$tfrom}','{$tto}')");
            else $this->db->query("UPDATE timesheet SET timein='{$tfrom}', timeout='{$tto}' WHERE timeid='{$tid}'");
        }
    }

    function applicantlist(){
        $userid = $this->session->userdata("username");
        return $this->db->query("SELECT DISTINCT * FROM applicant a INNER JOIN applicant_info b ON a.applicantId = b.baseId INNER JOIN code_position c ON a.positionApplied = c.positionid INNER JOIN `applicant_application_status` d ON a.`applicantId` = d.`applicantid` WHERE assigned_head = '$userid' AND application_status = 'current' AND (head_stat IS NULL OR head_stat != 'done') AND a.applicantId NOT IN (SELECT applicantid FROM applicant_endorsement) ")->result_array();
    }

    function applicantlisthistory(){
        $fullname = $this->session->userdata("fullname");
        return $this->db->query("SELECT DISTINCT * FROM applicant a INNER JOIN applicant_info b ON a.applicantId = b.baseId INNER JOIN code_position c ON a.positionApplied = c.positionid INNER JOIN `applicant_application_status` d ON a.`applicantId` = d.`applicantid` WHERE application_status = 'history' AND app_stat <> 'NOT RECOMMENDED' AND a.isemployee = 0 ORDER BY d.id")->result_array();
    }
    # end for ica-hyperion 21194 & 21196
    
    function getConfirmButton($cutofffrom, $cutoffto){
        // return $this->db->query("SELECT * FROM cutoff WHERE CutoffFrom = '$cutofffrom' AND CutoffTo = '$cutoffto' AND CURRENT_DATE BETWEEN ConfirmFrom AND ConfirmTo")->num_rows(); 
        return $this->db->query("SELECT * FROM cutoff WHERE CutoffFrom = '$cutofffrom' AND CutoffTo = '$cutoffto' AND ConfirmTo >= CURRENT_DATE AND DATE_FORMAT(NOW(), '%H:%i:%s') BETWEEN TimeFrom AND TimeTo")->num_rows();
    }

    function endorsedApplicant(){
        return $this->db->query("SELECT * FROM applicant WHERE applicantId IN (SELECT applicantid FROM applicant_endorsement) AND (datehired IS NULL OR datehired = '') AND isactive = 1");
    }

     function applicantListNotif($userid){
        return $this->db->query("SELECT * FROM applicant a INNER JOIN applicant_info b ON a.applicantId = b.baseId INNER JOIN code_position c ON a.positionApplied = c.positionid INNER JOIN`applicant_application_status` d ON a.`applicantId` = d.`applicantid` WHERE assigned_head = '$userid' AND application_status = 'current' AND (head_stat IS NULL OR head_stat != 'done') AND a.applicantId NOT IN (SELECT applicantid FROM applicant_endorsement)")->num_rows();
    }

    function activate_account($data){
        $return = "";
        $key = $data["activation_email"];
        $user = $this->db->query("SELECT employeeid FROM employee WHERE email = '$key'")->row()->employeeid;
        
        $newpass = $data['newpass'];
        $retpass = $data['retpass'];
        if($newpass == $retpass){
            $query = $this->db->query("SELECT * FROM user_info WHERE username='$user' ");
            if($query->num_rows() == 1){
                $timestamp = $this->extensions->getServerTime();
                $retpass = password_hash($retpass, PASSWORD_DEFAULT);
                $this->db->query("UPDATE user_info SET password='$retpass', activated='1', log_count = 1, log_date = '$timestamp', activation_stamp = '$timestamp' WHERE username='$user'");
                $this->session->set_userdata("logged_in", TRUE);
                $return = "Successfully Saved!.";

                $loginTrail = array();
                $name = $query->row()->lastname.", ".$query->row()->firstname." ". $query->row()->middlename;
                $loginTrail['ip'] = $_SERVER['REMOTE_ADDR'];
                $loginTrail['status'] = "success";
                $loginTrail['username'] = $user;
                $loginTrail['userid'] = $query->row()->id;
                $loginTrail['mac'] = $this->extras->returnmacaddress($_SERVER['REMOTE_ADDR']);
                $loginTrail['name'] = ($name)? $name:"Not Registered User";
                $loginTrail['device'] = $_SERVER['HTTP_USER_AGENT'];
                // Insert Trail

                $this->extras->insertLoginTrails($loginTrail);

                if ($query->row()->activated == 0) {
                    $this->session->sess_destroy();
                }

                $this->activate_linked_account($query->row()->linked, $retpass);
            }
        }else{
            $return = "Password did not matched!.";
        }
        return $return;
    }

    function loadSemesterSchedule($id = ""){
        $where ="";
        if ($id != "") $where = "WHERE id = '$id'";
        $query = $this->db->query("SELECT * FROM semester_schedule $where ORDER BY timestamp DESC");
        return $query;
    }

    /**
     * Activates an account across multiple campuses via API calls.
     *
     * @param string $user The user ID to be activated.
     * @param string $campus The campus where the account is being activated.
     * @return void
     * lndrsnts
     */
    function activateAccountAcrossCampuses($user, $status,$retpass)
    {
        try {
            $postData = array(
                "user_id" => $user,
                "status" => $status,
                "password" => $retpass
            );
            
            $siteList = Globals::sites();

            foreach ($siteList as $code => $desc) {
                $endpoint = Globals::campusEndpoints($code);
                
                $apiUrl = $endpoint . "api_/activate_account_across_campus";
              
                $token = Globals::hrisAccessToken($endpoint);

                $response = $this->sendApiRequest($apiUrl, $postData, $token);

                if ($response['error']) {
                    error_log("API request failed for campus $code: " . $response['message']);
                }
            }
            // die;
        } catch (Exception $e) {
            error_log("Error in activating account across campuses: " . $e->getMessage());
        }


        
    }

    /**
     * Sends a POST request to the given API URL with the specified data and token.
     *
     * @param string $apiUrl The URL to which the POST request is sent.
     * @param array $data The data to be posted.
     * @param string $token The authorization token for the API request.
     * @return array The response of the API call, containing success or error status.
     */
    public static function sendApiRequest($apiUrl, $data, $token)
    {
        $curl = curl_init();
    
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 10, // Reduce timeout to 10 seconds
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data), // Ensure data is sent as JSON
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json",
                "Authorization: Bearer $token", // Include authorization token
                "Cache-Control: no-cache",
                "Content-Type: application/json" // Add Content-Type for JSON
            ),
        ));
    
        $result = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
    
        if ($error) {
            return ['error' => true, 'message' => $error];
        }
    
        if ($httpCode < 200 || $httpCode >= 300) {
            return ['error' => true, 'message' => "HTTP Error: $httpCode", 'response' => $result];
        }
    
        return ['error' => false, 'message' => $result];
    }
    




}
?>