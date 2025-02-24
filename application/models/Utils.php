<?php 
/**
 * @author Angelica Arangco
 * @copyright 2017
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Utils extends CI_Model {

	/**
	 * Get data from a single table from database.
	 *
	 * @param string $tbl (table name)
	 * @param array $fields (list of fields to select)
	 * @param array $filter (conditions)
	 * @param string $order_by (data sorting)
	 *
	 * @return array
	 */
	public $used_id = array();
	function getSingleTblData($tbl='',$fields=array(),$filter=array(),$order_by='',$limit=0){
        $this->db->select($fields);
        if($order_by) $this->db->order_by($order_by);
        if($limit) 		$this->db->limit($limit);
        if(sizeof($filter) > 0) $data_q = $this->db->get_where($tbl,$filter); 
        else 					$data_q = $this->db->get($tbl); 
        return $data_q;
        
    }

    /**
	 * Insert data to a single table.
	 *
	 * @param string $tbl (table name)
	 * @param array $insert_data (list of data to insert)
	 *
	 * @return boolean/id
	 */
	function insertSingleTblData($tbl='',$insert_data=array()){
		$res = false;
        if(sizeof($insert_data) > 0) 	$res = $this->db->insert($tbl,$insert_data); 
        if($res) $res = $this->db->insert_id();
        return $res;
    }

    /**
	 * Update data to from single table.
	 *
	 * @param string $tbl (table name)
	 * @param array $update_data (list of data to update)
	 * @param array $filter (conditions)
	 *
	 * @return boolean/id
	 */
	function updateSingleTblData($tbl='',$update_data=array(),$filter=array()){
		$res = false;
        if(sizeof($filter) > 0) 	$this->db->where($filter);
        $res = $this->db->update($tbl,$update_data);
        return $res;
    }

	function getEmployerInfo($code_list=array()){
		$details = array();
		$wC = '';
		
		if(sizeof($code_list) > 0) {
			$codestr = implode(',', $code_list);
			$wC .= " WHERE FIND_IN_SET(code,'$codestr')";
		}

		$res = $this->db->query("SELECT * FROM _employer_info $wC");
		foreach ($res->result() as $key => $row) {
			$details[$row->code] = $row->description;
		}

		return $details;
	}
	
	/**
	 * Generate an array of string dates between 2 dates
	 *
	 * @param string $start Start date
	 * @param string $end End date
	 * @param string $format Output format (Default: Y-m-d)
	 *
	 * @return array
	 */

	function getDatesFromRange($start, $end, $format = 'Y-m-d') {
	    $array = array();
	    $interval = new DateInterval('P1D');
	    $realEnd = new DateTime($end);
	    $realEnd->add($interval);
	    $period = new DatePeriod(new DateTime($start), $interval, $realEnd);
	    foreach($period as $date) { 
			$array[] = $date->format($format); 
	    }
	    return $array;
	}
	
	
	/**
	 * Generate an array of distinct day of weeks between 2 dates
	 *
	 * @param string $start Start date
	 * @param string $end End date
	 *
	 * @return array
	 */

	function getDayofweekFromDates($start, $end){
		$arr_dates = $this->getDatesFromRange($start,$end);
		$arr_dow = array();

		foreach ($arr_dates as $date) {
			$dayofwk = date('w', strtotime($date));
			if(!in_array($dayofwk, $arr_dow)) array_push($arr_dow, $dayofwk);
		}
		return $arr_dow;		
	}
	
	function getUserAccess($accesstype='',$userid='',$menuid=''){
        $return='';
        $res = $this->db->query("SELECT * FROM user_access WHERE userid = $userid AND menu_id = $menuid");
        if($res->num_rows() > 0){
            if($accesstype == 'read') $return = $res->row(0)->read;
            elseif($accesstype == 'write') $return = $res->row(0)->write;
        }
		else
		{
			$return = "YES";
		}
        return $return;
    }

    function getUserAccessPayroll($userid='',$employeeid=''){
        $return='';
        $emppostype = '';
        $res = $this->db->query("SELECT b.`type` 
									FROM employee a 
									INNER JOIN code_position b ON b.`positionid`=a.`positionid` 
									WHERE employeeid='$employeeid'");

        if($res->num_rows() > 0) $emppostype = $res->row(0)->type;

        if($emppostype){
        	$res = $this->db->query("SELECT * FROM user_access_payroll WHERE userid = '$userid' #AND position_type = '$emppostype'");
        	if($res->num_rows() > 0) $return = true;
        }
        return $return;
    }

    function hasEditPayrollComputedEditAccess($user=''){
    	// if(!$user) $user = $this->session->userdata('username');
    	$access_list = array('lara','pinnacle','jvlalonzo');
    	if(in_array($user, $access_list)) return true;
    	else return false;
    }
	
   

	 /**
	 * Gets fullname of employee.
	 *
	 * @param string $employeeid (Default: "")
	 *
	 * @return string
	 */
    function getFullName($employeeid=''){
    	$fullname = '';
    	$res = $this->db->query("SELECT REPLACE(CONCAT(c.LName,', ',c.FName,' ',c.MName), 'Ã‘', 'Ñ') AS fullname FROM employee c WHERE employeeid='$employeeid'");
    	if($res->num_rows() > 0 ) $fullname = $res->row(0)->fullname;
    	return $fullname;
    }

    function getLFName($employeeid=''){
    	$fullname = '';
    	$res = $this->db->query("SELECT REPLACE(CONCAT(c.LName,', ',c.FName), 'Ã‘', 'Ñ') AS fullname FROM employee c WHERE employeeid='$employeeid'");
    	if($res->num_rows() > 0 ) $fullname = $res->row(0)->fullname;
    	return $fullname;
    }

	/**
	 * Generate list of employees (employeeid and fullname) under a given department id/managementid. Returns all if no id given.
	 *
	 * @param string $deptid (Default: "")
	 *
	 * @return array
	 */
	function getEmplist($deptid = "", $division='', $caption='', $tnt='',$employmentstat='',$nameformat='',$includeResigned=false,$dateGen='', $isShowDeptAndTnt = false,$employeeid='', $isactive="", $campus='', $sDept='', $sAlp='', $company='', $office='', $soffc="", $multiple=false){

		$wC = '';
		$oD = 'lname';
		// $employeeid = "97350-0294";
		$cond = $emplist = $emplistWithDeptAndTnt =  array();
		if(!$includeResigned) 	array_push($cond,"(dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00')");
		if($dateGen) 			array_push($cond,"(dateresigned > '$dateGen' OR dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00')");
		if($deptid && $deptid != "undefined") 			array_push($cond,"deptid = '$deptid'");
		if($office) 			array_push($cond,"office = '$office'");
		if($campus && $campus != 'All') 			array_push($cond,"campusid = '$campus'");
		if($division) 			array_push($cond,"managementid = '$division'");

		if($tnt){	
			if($tnt != "trelated"){
				array_push($cond,"teachingtype = '$tnt'");
			}else{
      			array_push($cond,"teachingtype = 'teaching'");
      			array_push($cond,"trelated = '1'");
			}
		}
		
		if($employmentstat) 	array_push($cond,"employmentstat = '$employmentstat'");
		if($employeeid){
			if($multiple){
				$emp_arr = explode(",", $employeeid);
				if(!in_array("all", $emp_arr)) array_push($cond, "FIND_IN_SET (employeeid, '$employeeid')");
			}
			else{
				array_push($cond,"employeeid = '$employeeid'");
			}
		}
		if($company && $company != "undefined") 		array_push($cond,"company_campus = ".$this->db->escape($company)."");
		if($isactive){
			if ($isactive == '1') {
				array_push($cond,"isactive = '1'");
			}
			if($isactive == '2'){
				array_push($cond,"isactive = '0'");
			}
		}
		
		if(sizeof($cond) > 0) {
			$wC = implode(' AND ', $cond);
			$wC = 'WHERE ' . $wC;
		}

		if (isset($caption)) {
            $emplist = array(""=>$caption);
        }
        if ($soffc != "") {
        	$oD = "office,".$oD;
        }
        if ($sDept != "") {
        	$oD = "deptid,".$oD;
        }
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
        $wC .= $utwc;
		$query = $this->db->query("SELECT employeeid,lname,fname,mname,deptid,teachingtype FROM employee $wC ORDER BY $oD")->result();
		// echo "<pre>"; print_r($this->db->last_query()); die;
		foreach($query as $val){
			if($nameformat=='NAMEONLY'){
				$emplist[$val->employeeid] = $val->lname.", ".$val->fname." ".$val->mname ;
				$emplistWithDeptAndTnt[$val->employeeid]["name"] = $val->lname.", ".$val->fname." ".$val->mname ;
			}else{
				$emplist[$val->employeeid] = $val->employeeid." - ".$val->lname.", ".$val->fname." ".$val->mname ;
				$emplistWithDeptAndTnt[$val->employeeid]["name"] = $val->lname.", ".$val->fname." ".$val->mname ;
			}

			$emplistWithDeptAndTnt[$val->employeeid]["deptid"] = $val->deptid;
			$emplistWithDeptAndTnt[$val->employeeid]["tnt"]    = $val->teachingtype;
		}
		unset($emplist['']);

		if($isShowDeptAndTnt) $emplist = $emplistWithDeptAndTnt;
		// echo "<pre>"; print_r($emplist);
		return $emplist;
	}
				
	/**
	 * Generates list of employeeid's under a given department. Returns all if no deptid given.
	 *
	 * @param string $deptid (Default: "")
	 *
	 * @return array
	 */
	function getEmpIDs($deptid="",$division='',$tnt='',$employeeid='',$orderby='', $campus = ''){
		$wC = $orderby = "";
    	$arr_empids = array();
		if($deptid)  		$wC .= " AND deptid = '$deptid'";
		if($division)       $wC .= " AND managementid='$division'";
		if($tnt)       		$wC .= " AND teachingtype='$tnt'";
		if($employeeid)  	$wC .= " AND employeeid='$employeeid'";
		if($campus)			$WC .= " AND campusid='$campus'";
    	$res = $this->db->query("SELECT employeeid FROM employee WHERE (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00') $wC $orderby");
    	if($res->num_rows() > 0){
    		foreach ($res->result() as $obj) {
    			array_push($arr_empids, $obj->employeeid);
    		}
    	}
    	return $arr_empids;
    }

    function getEmployeeIDList($deptid="",$division='',$tnt='',$employeeid='',$orderby='', $campus = '', $department = '', $dept_select="",$office="",$isactive="",$company_campus=""){
    	$wC = "";
    	$arr_empids = array();
    	if($company_campus && $company_campus!="all") $wC .= " AND a.company_campus='$company_campus'";
        if($deptid && $deptid!="all") $wC .= " AND a.deptid='$deptid'";
        if($isactive != "all"){
          if($isactive=="1"){
            $wC .= " AND isactive ='1'";
          }
          if($isactive=="0"){
            $wC .= " AND isactive ='0'";
          }
        }
		if($office)  		$wC .= " AND a.office = '$office'";
		if($division)       $wC .= " AND a.managementid='$division'";
		if($tnt){
			if($tnt != 'trelated') $wC .= " AND a.teachingtype='$tnt'";
			else $wC .= " AND a.teachingtype='teaching' AND a.trelated = '1'"; // nonteaching to teaching coz its trelated
		}
		// if($tnt)       		$wC .= " AND a.teachingtype='$tnt'";
		if($employeeid && $employeeid != 'all')  	$wC .= " AND a.employeeid='$employeeid'";
		if($campus && ($campus != 'all' && $campus != 'All'))			$wC .= " AND a.campusid='$campus'";
		if($department)		$wC .= " AND b.description='$department'";
		if($dept_select)	$wC .= " AND a.deptid='$dept_select'";
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
        $wC .= $utwc;
    	$res = $this->db->query("SELECT c.description AS campus, b.description AS department, a.employeeid, REPLACE(CONCAT(a.LName,', ',a.FName,' ',a.MName), 'Ã‘', 'Ñ') AS fullname
								 FROM employee a
								 LEFT JOIN code_office b ON b.code = a.office
								 LEFT JOIN code_campus c ON c.code = a.campusid
								 WHERE (a.dateresigned = '1970-01-01' OR a.dateresigned IS NULL OR a.dateresigned = '0000-00-00') $wC $orderby");
    	// echo $this->db->last_query(); die;
    	if($res->num_rows() > 0){
    		foreach ($res->result() as $obj) {
    			$arr_empids[$obj->employeeid] = array(
    				"fullname" => $obj->fullname,
    				"department" => $obj->department,
    				"campus" => $obj->campus
    			);
    		}
    	}
    	return $arr_empids;
    }
    /**
	 * Generates list of all departments.
	 *
	 * @param string $caption (Default: "")
	 *
	 * @return array
	 */
    function getOffice($caption=''){
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

    function getDepartments($caption=''){
        $returns = array();
    
		if (isset($caption)) {
			$returns = array(""=>$caption);
		}
		$this->db->select("code,description");
		$this->db->order_by("description","asc");
		$q = $this->db->get("code_department"); 
		for($t=0;$t<$q->num_rows();$t++){
			$row = $q->row($t);
			$returns[GLOBALS::_e($row->code)] = GLOBALS::_e($row->description);
		}
		return $returns;
		
    }
	function getDepartmentDesc($select=''){
        $returns = "All College";
        if($select){
			$this->db->select("description");
        	$this->db->where("code=", $select);
			$q = $this->db->get("code_department"); 
			return $q->row(0)->description;
		}
		return $returns;
    }

    function getschedcluster($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("schedcode,description");
        $this->db->order_by("description","asc");
        $q = $this->db->get("code_schedule"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->schedcode] = $row->description;
        }
        return $returns;
    }

   	function getEmpDept($user='')
   	{
   		$office = $campus = "";
   		
   		$query = $this->db->query("SELECT office,campusid FROM employee WHERE employeeid='$user'");
   		if ($query->num_rows() > 0)
   		{
   		 $office = $query->row(0)->office; 
   		 $campus = $query->row(0)->campusid;
   		}
   		return array($office,$campus);
   	}
	/**
	 * Gets department data based on given column name and code.
	 *
	 * @param string $col column name ie. head/divisionhead (Default: "")
	 * @param string $code dept code (Default: "")
	 *
	 * @return string
	 */
    function getDeptHead($col="", $code="", $campus=""){
    	$head = "";
    	$where_clause = "";
    	$user = $this->session->userdata('username');
    	if($code) $where_clause .= " AND code='$code'";
    	else $where_clause .= " AND $col = '$user'";

    	if($campus) $where_clause .= " AND b.campus = '$campus'";
    	$res = $this->db->query("SELECT b.$col FROM code_office a LEFT JOIN campus_office b ON a.code = b.base_code WHERE 1 $where_clause");
    	if($res && $res->num_rows() > 0) $head = $res->row(0)->$col;
    	return $head;
    }
    function getUserType($user=""){
    	$type = "";
    	$isType = false;
    	$res = $this->db->query("SELECT type FROM user_info WHERE username='$user'");
    	if($res->num_rows() > 0){ 
    		$type = $res->row(0)->type;
    	}
    	switch ($type) {
    		case 'ADMIN':case 'SUPER ADMIN': 
    			$isType = true;
    			break;
    		
    		default:
    			$isType = false;
    			break;
    	}
    	return $isType;
    }
    function checkIfHead($employeeid=''){
    	$isHead = false;
    	$res = $this->db->query("SELECT * FROM campus_office WHERE (dhead='$employeeid' OR divisionhead='$employeeid' OR phead = '$employeeid' OR hrhead = '$employeeid')");
    	if($res->num_rows() == 0){
    		$res = $this->db->query("SELECT * FROM code_campus WHERE campus_principal='$employeeid'");
    	}
    	if($res->num_rows() > 0) $isHead = true;
    	return $isHead;
    }

    /**
	 * Gets number of applications not read by the user.
	 *
	 * @return int
	 */
	function getNotif($tbl_emplist=''){
		$employeeid = $this->session->userdata('username');
		$res = $this->db->query("SELECT COUNT(id) as count FROM $tbl_emplist WHERE employeeid='$employeeid' AND status IN ('APPROVED', 'DISAPPROVED', 'CANCELLED') AND isread='0'");
		if($res->num_rows() > 0 ) return $res->row(0)->count;
		else 	return 0;
	}

	function getNotifOB($type=''){
		$wC = '';
		if($type) $wC .= " AND b.type='$type'";

		$ob = $wfh = 0;
		$employeeid = $this->session->userdata('username');
		$res = $this->db->query("SELECT COUNT(DISTINCT a.id) as count FROM ob_app_emplist a INNER JOIN ob_app b ON b.id=a.base_id WHERE employeeid='$employeeid' AND isread='0' $wC");
		if($res->num_rows() > 0) $ob = $res->row(0)->count;

		/*for ob wfh disapproved*/

		$res2 = $this->db->query("SELECT COUNT(DISTINCT a.id) as count FROM ob_app_emplist a INNER JOIN ob_app b ON b.id=a.base_id INNER JOIN ob_timerecord c ON b.id = c.base_id INNER JOIN ob_wfh_disapproved d ON d.base_id = c.base_id WHERE employeeid='$employeeid' AND d.isread = '0' $wC");
		if($res2->num_rows() > 0) $wfh = $res2->row(0)->count;
		

		// echo $this->db->last_query();
		return $ob+$wfh;
	}

	/**
	 * Gets number of PENDING applications by approver.
	 *
	 * @return int
	 */
	function getNotifManage($tbl_base='' ,$tbl_emplist='',$code='OT',$srcmodel='overtime',$isRetArr=false,$type='',$app_type=''){
		
		$user 	 = $this->session->userdata('username');
		$colhead = $prevcolstatus = ""	;

		$arr_aprvl_seq 	= array();
		$this->load->model($srcmodel);
		$setup 			= $this->$srcmodel->getAppSequence($code);

		if($setup->num_rows() > 0){
			$arr_aprvl_seq = $this->$srcmodel->sortApprovalSeq($setup->row(0));
		}

		$prevkey 	 = '';
		$arr_apprv = array();
		foreach ($arr_aprvl_seq as $key => $obj) {
			if($obj['head_id'] == $user){
				$colhead = $obj['position'];
				if($key > 1) 	$prevkey  = $key - 1;
				// break;

				if($prevkey && isset($arr_aprvl_seq[$prevkey]['position'])){
					$prevcolstatus = substr($arr_aprvl_seq[$prevkey]['position'],0,-4) . 'status';
				}
				$colstatus =  $colhead ? (substr($colhead,0,-4) . 'status') : '';

				array_push($arr_apprv, array('seq_count'=>$key,'colhead'=>$colhead,'colstatus'=>$colstatus,'prevcolstatus'=>$prevcolstatus));


			}
		}
		$count = 0;
		$res_arr = array();
		$codeType = $code;
		foreach ($arr_apprv as $key => $arr) {
			$prev_seq_count = ($key > 1) ? $key /*- 1*/ : 0; 
			$colseq =  $arr['colhead'] ? (substr($arr['colhead'],0,-4) . 'seq') : '';
			$prevcolseq =  $arr['prevcolstatus'] ? (substr($arr['prevcolstatus'],0,-6) . 'seq') : '';

			$wC = "";

			if(substr($code, -3) == 'NON' || strpos($codeType, 'NON') !== false) 	$teachingtype = 'nonteaching';
			else 		   					$teachingtype = 'teaching';
			

			if($arr['prevcolstatus']) 	 	 $wC .= " AND ".$arr['prevcolstatus']."='APPROVED'";
			if($colseq)			 	 		 $wC .= " AND $colseq!='0'";
			if($prev_seq_count)	 	 		 $wC .= " AND $prevcolseq='$prev_seq_count'";
			if($arr['seq_count'])	 	 	 $wC .= " AND $colseq='".$arr['seq_count']."'";

			$wC .= " AND a.teachingtype='$teachingtype'";
			if($tbl_emplist == "ot_app_emplist" && $code == "OTHEADNON"){
				$used_otid = "'".implode("','", $this->used_id). "'";
				// $wC .= " AND a.id NOT IN ($used_otid)";
			}
			if($code){
				$code = str_replace('NON','',$code);
				$code = str_replace('HEAD','',$code);
			}
			if($tbl_base=='leave_app_base')  $wC .= " AND (b.type='$code' OR b.other='$code')";
			// if($tbl_base=='change_sched_app')  $wC .= " AND b.type='$app_type'";

			if($type && $app_type == '') $wC .= " AND b.type='$type'";
			
			if($tbl_base == "sc_app")		 $wC .= " GROUP BY a.approval_id";
			if($tbl_base == "ob_app" && $type == "DIRECT")	 	 $wC .= " AND a.save_stat = 'save'";
			if($tbl_base){
				$res = $this->db->query("SELECT a.id
									FROM $tbl_emplist a
									INNER JOIN $tbl_base b ON a.`base_id`=b.`id`
									WHERE ".$arr['colhead']."='$user' AND ".$arr['colstatus']."='PENDING' AND a.status = 'PENDING' $wC");

			}else{
				$res = $this->db->query("SELECT a.id
									FROM $tbl_emplist a
									WHERE ".$arr['colhead']."='$user' AND ".$arr['colstatus']."='PENDING' AND status = 'PENDING' $wC");
			}

			if($res->num_rows() > 0 ){
				foreach ($res->result() as $key => $row) {
					if(!in_array($row->id, $res_arr)) array_push($res_arr, $row->id);
				}
				// $count += $res->row(0)->count;
			}
			if($code == 'SC' && $tbl_emplist == 'sc_app_emplist'){
				
				//echo "<pre>". $code; print_r($res_arr); echo "</pre>";
			}
		}


		if($tbl_emplist == "ot_app_emplist") $this->used_id = array_merge($res_arr, $this->used_id);
		$count = sizeof($res_arr);
		
		if($isRetArr) return $res_arr;
		else return $count;

	}

	function getChangeSchedAppNotifCount(){
		$user_id = $this->session->userdata("username");
		$sql = "SELECT 1 FROM change_sched_app a INNER JOIN change_sched_app_emplist b ON a.id = b.base_id WHERE a.id <> '' AND EXISTS (SELECT 1 FROM change_sched_app_emplist_items WHERE b.employeeid = employeeid AND approver_id = '$user_id' AND isread = 0) GROUP BY a.id";
		$query = $this->db->query($sql);
		if($query){
			return $query->num_rows();
		}

		return 0;
	}

	function getChangeSchedReqNotifCount(){
		$user_id = $this->session->userdata("username");
		$sql = "SELECT 1 FROM change_sched_app a 
				INNER JOIN change_sched_app_emplist b ON a.id = b.base_id
				WHERE b.employeeid = '$user_id' AND (a.isread = 0 OR a.isread IS null) GROUP BY a.id";
		$query = $this->db->query($sql);
		
		if($query){
			return $query->num_rows();
		}

		return 0;
	}

	function getNotifManageLEAVE_old(){
		$count = 0;
		$ret_arr = array();
		$form = $this->db->query("SELECT code_request FROM code_request_form");
		if($form->num_rows() > 0){
			foreach ($form->result() as $key => $row) {
				$count1 = $this->getNotifManage('leave_app_base','leave_app_emplist',$row->code_request,'leave_application',true);
				$count2 = $this->getNotifManage('leave_app_base','leave_app_emplist',$row->code_request.'NON','leave_application',true);
				$count3 = $this->getNotifManage('leave_app_base','leave_app_emplist',$row->code_request.'HEAD','leave_application',true);
				$count4 = $this->getNotifManage('leave_app_base','leave_app_emplist',$row->code_request.'HEADNON','leave_application',true);

				$ret_arr = array_unique (array_merge ($ret_arr, $count1));
				$ret_arr = array_unique (array_merge ($ret_arr, $count2));
				$ret_arr = array_unique (array_merge ($ret_arr, $count3));
				$ret_arr = array_unique (array_merge ($ret_arr, $count4));
				// echo $this->db->last_query();echo '<br>';
			}
		}
		$count = sizeof($ret_arr);

		return $count;
	}

	function getNotifManageSeminar(){
		$seminar_list = array();

		$status 	= "PENDING";
		$datefrom 	= $dateto = "";

		$user 		= $this->session->userdata('username');

		$seminar_list = array();
		$form = $this->db->query("SELECT code_request FROM code_request_form");
		if($form->num_rows() > 0){
			foreach ($form->result() as $key => $row) {
				///< --------------------------(condition added for Campus Principal which is ommitted for non teaching)-------------------------------------------
				$seminar_list_teaching = $this->getSeminarAppListToManageProcess($row->code_request,$status,$datefrom,$dateto,$user,'teaching');
				if(sizeof($seminar_list_teaching) > 0) 	$seminar_list =  $seminar_list + $seminar_list_teaching;

				$seminar_list_non = $this->getSeminarAppListToManageProcess($row->code_request.'NON',$status,$datefrom,$dateto,$user,'nonteaching');
				if(sizeof($seminar_list_non) > 0) 		$seminar_list =  $seminar_list + $seminar_list_non;

				$seminar_list_teaching = $this->getSeminarAppListToManageProcess($row->code_request.'HEAD',$status,$datefrom,$dateto,$user,'teaching');
				if(sizeof($seminar_list_teaching) > 0) 	$seminar_list =  $seminar_list + $seminar_list_teaching;

				$seminar_list_non = $this->getSeminarAppListToManageProcess($row->code_request.'HEADNON',$status,$datefrom,$dateto,$user,'nonteaching');
				if(sizeof($seminar_list_non) > 0) 		$seminar_list =  $seminar_list + $seminar_list_non;
				

			}
		}
		
		return count($seminar_list);
	}

	function getSeminarAppListToManageProcess($code_request="VL",$status='',$datefrom='',$dateto='',$user='',$teachingType=''){
		$this->load->model("seminar");
		$colhead = $isLastApprover = "";
		$prevcolstatus = ""	;
		$seminar_list = array();
		$arr_aprvl_seq 	= array();
		$setup 			= $this->seminar->getAppSequence($code_request);
		if($setup->num_rows() > 0){
			$arr_aprvl_seq = $this->seminar->sortApprovalSeq($setup->row(0));
		}

		$aprvl_count = sizeof($arr_aprvl_seq);
		$prevkey 	 = '';
		$arr_apprv = array();
		foreach ($arr_aprvl_seq as $key => $obj) {
			if($obj['head_id'] == $user){
				$colhead = $obj['position'];
				if($aprvl_count == $key) 	 $isLastApprover = true;
				if($key > 1) 				 $prevkey 		 = $key - 1;
				// break;

				if($prevkey && isset($arr_aprvl_seq[$prevkey]['position'])){
					$prevcolstatus = substr($arr_aprvl_seq[$prevkey]['position'],0,-4) . 'status';
				}
				$colstatus =  $colhead ? (substr($colhead,0,-4) . 'status') : '';

				array_push($arr_apprv, array('seq_count'=>$key,'colhead'=>$colhead,'colstatus'=>$colstatus,'prevcolstatus'=>$prevcolstatus,'isLastApprover'=>$isLastApprover,'code_request'=>$code_request));

				$isLastApprover = '';
			}
		}

		foreach ($arr_apprv as $key => $arr) {
			$temp_res = $this->seminar->getSeminarAppListToManage($user, $arr['colhead'], $arr['colstatus'], $status, $arr['prevcolstatus'], $datefrom, $dateto,$teachingType,$code_request,$arr['seq_count']);

			if($temp_res->num_rows() > 0){
				foreach ($temp_res->result() as $key => $row) {
					$seminar_list[$row->aid] = array('data_list'=>$row,'colhead'=>$arr['colhead'],'colstatus'=>$arr['colstatus'],'prevcolstatus'=>$arr['prevcolstatus'],'isLastApprover'=>$arr['isLastApprover'],'code_request'=>$arr['code_request']);
				}
			}
		}

		return $seminar_list;
	}
	function getNotifManageOvertime(){
		$monotifcount       = $this->getNotifManage('ot_app','ot_app_emplist','OT');
        $monotifcount       += $this->getNotifManage('ot_app','ot_app_emplist','OTNON');
        $monotifcount       += $this->getNotifManage('ot_app','ot_app_emplist','OTHEAD');
        $monotifcount       += $this->getNotifManage('ot_app','ot_app_emplist','OTHEADNON');
        echo $monotifcount;
	}

	function getLeaveAppListToManageProcess($code_request="VL",$status='',$datefrom='',$dateto='',$user='',$teachingType=''){
		$this->load->model("leave_application");
		$colhead = $isLastApprover = "";
		$prevcolstatus = ""	;
		$leave_list = array();
		$arr_aprvl_seq 	= array();
		$setup 			= $this->leave_application->getAppSequence($code_request);
		if($setup->num_rows() > 0){
			$arr_aprvl_seq = $this->leave_application->sortApprovalSeq($setup->row(0));
		}

		$aprvl_count = sizeof($arr_aprvl_seq);
		$prevkey 	 = '';
		$arr_apprv = array();
		foreach ($arr_aprvl_seq as $key => $obj) {
			if($obj['head_id'] == $user){
				$colhead = $obj['position'];
				if($aprvl_count == $key) 	 $isLastApprover = true;
				if($key > 1) 				 $prevkey 		 = $key - 1;
				// break;

				if($prevkey && isset($arr_aprvl_seq[$prevkey]['position'])){
					$prevcolstatus = substr($arr_aprvl_seq[$prevkey]['position'],0,-4) . 'status';
				}
				$colstatus =  $colhead ? (substr($colhead,0,-4) . 'status') : '';

				array_push($arr_apprv, array('seq_count'=>$key,'colhead'=>$colhead,'colstatus'=>$colstatus,'prevcolstatus'=>$prevcolstatus,'isLastApprover'=>$isLastApprover,'code_request'=>$code_request));

				$isLastApprover = '';
			}
		}

		foreach ($arr_apprv as $key => $arr) {
			$temp_res = $this->leave_application->getLeaveAppListToManage($user, $arr['colhead'], $arr['colstatus'], $status, $arr['prevcolstatus'], $datefrom, $dateto,$teachingType,$code_request,$arr['seq_count']);

			if($temp_res->num_rows() > 0){
				foreach ($temp_res->result() as $key => $row) {
					$leave_list[$row->aid] = array('data_list'=>$row,'colhead'=>$arr['colhead'],'colstatus'=>$arr['colstatus'],'prevcolstatus'=>$arr['prevcolstatus'],'isLastApprover'=>$arr['isLastApprover'],'code_request'=>$arr['code_request']);
				}
			}
		}

		return $leave_list;
	}
	
	/**
	 * Generates list of all management levels.
	 *
	 * @param string $caption (Default: "")
	 *
	 * @return array
	 */
    function getManagementLevels($caption=''){
    	$return = array();
        if (isset($caption)) {
            $return = array(""=>$caption);
        }
        $q = $this->db->query("select managementid,description from code_managementlevel order by description")->result();
        foreach($q as $oo){
          $return[Globals::_e($oo->managementid)] = Globals::_e($oo->description);    
        }
        return $return;
    }

    function getAuditTrailHistory($dfrom='', $dto=''){
    	$wC = '';
    	if($dfrom && $dto) $wC .= " WHERE dtime >= '$dfrom' AND dtime <= DATE(DATE_ADD('$dto',INTERVAL 1 DAY))";
    	else 			   $wC .= " WHERE dtime>CURRENT_DATE";
    	$res = $this->db->query("SELECT a.id,a.userid ,b.username,CONCAT(b.lastname,', ',b.firstname,' ',b.`middlename`) AS fullname ,menuid,title, que, dtime
								    FROM tbltrail a
								    LEFT JOIN user_info b ON a.userid = b.id
								    LEFT JOIN menus c ON c.`menu_id`=a.`menuid`
								    $wC
								    ORDER BY a.id desc");
    	if($res->num_rows() > 0) return $res->result();
    	else return '';
    }

    function getCampusList($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("code,description");
        $this->db->order_by("description","asc");
        $q = $this->db->get("code_campus"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->code] = $row->description;
        }
        return $returns;
    }

    function getEmptypeList($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("code,description");
        $this->db->order_by("description","asc");
        $q = $this->db->get("code_type"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->code] = $row->description;
        }
        return $returns;
    }

    function getBankList($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("code,bank_name");
        $q = $this->db->get("code_bank_account"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->code] = $row->bank_name;
        }
        return $returns;
    }

    function getBloodTypes(){
        $return = array(""=>"- Select Blood Type -","O+" => "O+", "0-" => "0-", "A+" => "A+", "A-" => "A-", "B+" => "B+", "B-" => "B-", "AB+" => "AB+", "AB-" => "AB-");
		return $return;
    }
	
	/**
	 * Generate an html option select with given data.
	 *
	 * @param array $data list for options
	 * @param string $caption (Default: "")
	 *
	 * @return string
	 */
	function constructOptionSelect($data, $caption=""){
		$select = "";
		if($caption) $select .= "<option value=''>$caption</option>"; 
		foreach ($data as $key => $value) {
			$select .= "<option value='$key'>$value</option>";
		}
		return $select;
	}

	function getRequestApprover(){
		$returns = array();
		$this->db->select("code,positionName");
		$q = $this->db->get("code_request_form_approver"); 
		for($t=0;$t<$q->num_rows();$t++){
		  $row = $q->row($t);
		  $returns[$row->code] = $row->positionName;
		}
		return $returns;
	}

	# for ica-hyperion 21129 
    # justin (with e)
    function findIfHead($empid){
    	$query = $this->db->query("SELECT * FROM code_office WHERE head = '$empid' OR divisionhead = '$empid'")->result();
    	if(count($query) > 0)
    		return true;
    	else 
    		return false;
    }
	# end for ica-hyperion 21129 

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

	function findEmpListPerType($teaching_type = ""){
		$result = array();
		$result["msg"] = "";
		$where_clause = "";
		if($teaching_type) $where_clause = " AND e.teachingtype = '$teaching_type' ";
		# query para sa employee na ganitong type
		$query = $this->db->query("SELECT e.`employeeid` AS empID, CONCAT(e.`lname`, ', ', e.`fname`, ' ', e.`mname`) AS fullname, e.`dateresigned`, e.`dateemployed`, e.`teachingtype`
										FROM employee e 
											WHERE (e.`dateresigned` < e.`dateemployed` OR e.`dateresigned`='0000-00-00'  OR e.`dateresigned` = '1970-01-01' OR e.`dateresigned` IS NULL)
											  $where_clause
											ORDER BY fullname;")->result();
		# ibato lahat dito ng result 
		if(count($query) > 0){
			foreach ($query as $res) {
				# insert sa array result
				$result[$res->empID] = $res->fullname;
			}
		}else{
			$result["msg"] = "No Data Available";
		}

		return $result;
	}

	function getEmpListToCbo(){
		$result = array();
		$result["msg"] = "";
		
		# query para sa employee na ganitong type
		$query = $this->db->query("SELECT e.`employeeid` AS empID, CONCAT(e.`lname`, ', ', e.`fname`, ' ', e.`mname`) AS fullname, e.`dateresigned`, e.`dateemployed`, e.`teachingtype`
										FROM employee e 
											WHERE (e.`dateresigned` < e.`dateemployed` OR e.`dateresigned`='0000-00-00' OR e.`dateresigned` = '1970-01-01' OR e.`dateresigned` IS NULL)
											 AND isactive = '1' ORDER BY fullname;")->result();
		# ibato lahat dito ng result 
		if(count($query) > 0){
			foreach ($query as $res) {
				# insert sa array result
				$result[$res->empID] = $res->fullname;
			}
		}else{
			$result["msg"] = "No Data Available";
		}

		return $result;
	}
	# end for ica-hyperion 21194

	function basedon($id='', $is_allow_null = true)
	{
		$basedon = array(1=>"Term",2=>"Monthly"); 
		$return = "";
		if($is_allow_null) $return .= "<option value=''>Select Loan Base</option>";
		
		foreach ($basedon as $code => $description) {
			if ($id <> "" && $id == $code) {
				$return .= "<option value='$code' selected>".$description."</option>";
			}
			else
			{
				$return .= "<option value='$code' >".$description."</option>";
			}
		
		}
		return $return;
	}

	function getSCManageNotif($type = "SC", $datefrom = "", $dateto = "", $status = "PENDING"){
		$username = $this->session->userdata("username");
		return $this->db->query("SELECT * FROM sc_app a INNER JOIN sc_app_emplists b ON a.id = b.base_id WHERE FIND_IN_SET('$username', approver_id) > 0 AND a.status = 'PENDING' AND ongoing_approver = 'me'")->num_rows();
		// $this->load->model('service_credit');
		// $count_notif = 0;
		
		// if($type == "SC") $count_notif = $this->service_credit->getSCNotification($datefrom, $dateto, $status);
		// else 			  $count_notif = $this->service_credit->getSCUseNotification($datefrom, $dateto, $status);

		// return $count_notif;
	}

	function getUseSCManageNotif(){
		$username = $this->session->userdata("username");
		return $this->db->query("SELECT * FROM sc_app_use a INNER JOIN sc_app_use_emplists b ON a.id = b.base_id WHERE FIND_IN_SET('$username', approver_id) > 0 AND a.status = 'PENDING' AND ongoing_approver = 'me'")->num_rows();
	}

	function getCodeStatus(){
		$q_code_status = $this->db->query("SELECT code, description FROM code_status")->result();
		return $q_code_status;
	}

	function getOvertimeTypes(){
		$ot_types = array("WITH_SCHED" => "With Sched", "WITH_SCHED_WEEKEND" => "Week End w/ Sched", "NO_SCHED" => "No Sched", "NIGHT_DIFF" => "Night Diff");

		return $ot_types;
	}	

	function getEmployeeGender($empid){
		$result = '';
		$query = $this->db->query("SELECT gender FROM employee WHERE employeeid = '$empid' ");
		if($query->num_rows() > 0 ) return $result = $this->db->query("SELECT gender FROM employee WHERE employeeid = '$empid' ")->row()->gender;
		else return FALSE;
	}	

	function mergeTeachingAndNonTeachingList($teaching_list, $non_teaching_list){
		$emplist = array();

		foreach ($teaching_list as $emp_id => $info) $emplist[$emp_id] = $info;
		foreach ($non_teaching_list as $emp_id => $info) $emplist[$emp_id] = $info;

		return $emplist;
	}

    function loadApplicantStatus(){
    	$query = $this->db->query("SELECT * FROM code_applicant_status");
    	if($query->num_rows() > 0){
    		$appstat = $query->result_array();
    		foreach ($appstat as $key => $value) {
	            foreach ($value as $keyy => $vall) {
	                $data[$key][$keyy] = GLOBALS::_e($vall);
	            }
	        }
        return $data;
    	}
    	else{ return false;}
    }

    function loadApplicantDocument(){
    	$query = $this->db->query("SELECT * FROM code_applicant_document");
    	if($query->num_rows() > 0) return $query->result_array();
    	else return false;
    }

    function getAIMSDepartment($caption=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $this->db->select("GROUP_ID,DESCRIPTION");
        $this->db->where("GROUP_ID !=", "0");
        $this->db->order_by("DESCRIPTION","asc");
        $q = $this->db->get("tblCourseCategory"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[Globals::_e($row->GROUP_ID)] = Globals::_e($row->DESCRIPTION);
        }
        return $returns;
    }

    function getAIMSDepartmentCode($caption='', $campusid=''){
        $returns = array();
        if (isset($caption)) {
            $returns = array(""=>$caption);
        }
        $returns["all"] = "All AIMS Department";
        $this->db->select("CODE,DESCRIPTION");
        $this->db->where("CODE !=", "");
        // if($campusid) $this->db->where("CAMPUS", $campusid);
        $this->db->order_by("DESCRIPTION","asc");
        $q = $this->db->get("tblCourseCategory"); 
        for($t=0;$t<$q->num_rows();$t++){
          $row = $q->row($t);
          $returns[$row->CODE] = $row->DESCRIPTION;
        }
        return $returns;
    }

	public function getRateHour($employeeId, $aimsDept, $taggedHour) //LNDRSNTS
	{
		$validHours = ['lec' => 'lechour', 'lab' => 'labhour', 'rle' => 'rlehour'];
		$result = '';
	
		if (!isset($validHours[$taggedHour])) {
			return (float) $result; // Return empty if an invalid hour type is provided
		}
	
		$query = $this->db->query("
			SELECT ph.*, tc.* 
			FROM payroll_emp_salary_perdept_history ph
			LEFT JOIN tblCourseCategory tc ON tc.id = ph.aimsdept
			WHERE ph.employeeid = ? AND tc.DESCRIPTION = ?
			ORDER BY ph.TIMESTAMP DESC 
			LIMIT 1
		", [$employeeId, $aimsDept]);
	
		if ($query->num_rows() > 0) {
			$queryResult = $query->row();
			$result = $queryResult->{$validHours[$taggedHour]} ?? '';
		}
	
		return (float) $result;
	}
	

    function getSubject(){
        $returns = array();
        $returns = $this->db->query("SELECT SUBJECT FROM employee_schedule_history WHERE SUBJECT != '' GROUP BY SUBJECT ")->result_array();
        return $returns;
    }

    function hasSchedHistory($employeeid){
    	return $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$employeeid'")->num_rows();
    }

    function getEmployeeInfo($select='',$filter=array()){
    	$res = '';
    	if($select) $this->db->select($select);
    	if(sizeof($filter) > 0) $res = $this->db->get_where('employee',$filter);
    	else 					$res = $this->db->get('employee');
    	if($res->num_rows() > 0) return $res->result();
    	else return false;
    }

    function getEmployeeDepartment($empid){
		$empDepartment = $this->db->query("SELECT deptid FROM employee a INNER JOIN code_department b ON b.`code` =  a.`deptid` WHERE employeeid = '$empid' ");
		if($empDepartment->num_rows() > 0) return $empDepartment->row()->deptid; //1-19-2023 LOLA put return
		else return false;
	}

	function getEmployeeOffice($empid){
		$empDepartment = $this->db->query("SELECT office FROM employee WHERE employeeid = '$empid' ");
		if($empDepartment->num_rows() > 0) return $empDepartment->row()->office; //1-19-2023 LOLA put return
		else return false;
	}

	function getEmployeeDateActive($empid){
		$empDateActive = $this->db->query("SELECT date_active FROM employee WHERE employeeid = '$empid' ");
		if($empDateActive->num_rows() > 0) return $empDateActive->row()->date_active; //1-19-2023 LOLA put return
		else return false;
	}

	function getScheduleEffectiveDate($empid){
		$empDateActive = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$empid' ORDER BY dateactive DESC");
		if($empDateActive->num_rows() > 0){
			$dateactive = date("Y-m-d", strtotime($empDateActive->row()->dateactive));
			return $dateactive;
		}
		else{ 
			return false;
		}
	}

	function getScheduleSyncDate($empid){
		$empDateActive = $this->db->query("SELECT * FROM employee_schedule WHERE employeeid = '$empid' ORDER BY sync_date DESC LIMIT 1 ");
		if($empDateActive->num_rows() > 0){
			$sync_date = $empDateActive->row()->sync_date;
			return $sync_date;
		}
		else{ 
			return false;
		}
	}

    function getAIMS_DTR_DeptPair(){
    	$ret = array();
		$this->db->select('code,aimsdept');
		$q = $this->db->get("code_department"); 
		foreach ($q->result() as $key => $row) {
			$ret[$row->aimsdept] = $row->code; 		
    	}    	
    	return $ret;
    }

    function checkIfHolidayHalfday($holiday_id){
    	return $this->db->query("SELECT * FROM code_holiday_calendar a INNER JOIN code_holidays b ON a.holiday_id = b.holiday_id WHERE id = '$holiday_id' ")->result_array();
    }

	function getLeaveCategories(){
		$q_code_status = $this->db->query("SELECT * FROM reports_item WHERE reportcode = 'LD'")->result();
		return $q_code_status;
	}

	function getEmployeeCode($empid){
		$result = '';
		$query = $this->db->query("SELECT employeecode FROM employee WHERE employeeid = '$empid' ");
		if($query->num_rows() > 0 ) return $result = $this->db->query("SELECT employeecode FROM employee WHERE employeeid = '$empid' ")->row()->employeecode;
		else return FALSE;
	}

	function rfidChecker($rfid){
		$query = $this->db->query("SELECT employeecode FROM employee WHERE employeecode = '$rfid' ");
		if($query->num_rows() > 0 ) return True;
		else return false;
	}

	function getEmplistModified($deptid = "", $division='', $caption='', $tnt='',$employmentstat='',$nameformat='',$includeResigned=false,$dateGen='', $isShowDeptAndTnt = false,$employeeid='', $isactive = '', $campus='', $office=''){

		$wC = '';
		$cond = $emplist = $emplistWithDeptAndTnt =  array();
		if(!$includeResigned) 	array_push($cond,"(dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00')");
		if($dateGen) 			array_push($cond,"(dateresigned > '$dateGen' OR dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00')");
		// if($isactive != "") 			array_push($cond,"isactive = '$isactive'");
		if($isactive){
			if ($isactive == '1') {
				array_push($cond,"isactive = '1'");
			}
			if($isactive == '2'){
				array_push($cond,"isactive = '0'");
			}
		}
		if($campus) 			array_push($cond,"campusid = '$campus'");
		if($deptid) 			array_push($cond,"deptid = '$deptid'");
		if($office) 			array_push($cond,"office = '$office'");
		if($division) 			array_push($cond,"managementid = '$division'");
		if($tnt) 				array_push($cond,"teachingtype = '$tnt'");
		if($employmentstat) 	array_push($cond,"employmentstat = '$employmentstat'");
		if($employeeid) 		array_push($cond,"employeeid = '$employeeid'");
		
		if(sizeof($cond) > 0) {
			$wC = implode(' AND ', $cond);
			$wC = 'WHERE ' . $wC;
		}

		if (isset($caption)) {
            $emplist = array(""=>$caption);
        }

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
        $wC .= $utwc;

		$query = $this->db->query("SELECT employeeid,lname,fname,mname,deptid,teachingtype FROM employee $wC ORDER BY lname")->result();
		foreach($query as $val){
			if($nameformat=='NAMEONLY'){
				$emplist[$val->employeeid] = $val->lname.", ".$val->fname." ".$val->mname ;
				$emplistWithDeptAndTnt[$val->employeeid]["name"] = $val->lname.", ".$val->fname." ".$val->mname ;
			}else{
				$emplist[$val->employeeid] = $val->employeeid." - ".$val->lname.", ".$val->fname." ".$val->mname ;
				$emplistWithDeptAndTnt[$val->employeeid]["name"] = $val->lname.", ".$val->fname." ".$val->mname ;
			}

			$emplistWithDeptAndTnt[$val->employeeid]["deptid"] = $val->deptid;
			$emplistWithDeptAndTnt[$val->employeeid]["tnt"]    = $val->teachingtype;
		}
		unset($emplist['']);

		if($isShowDeptAndTnt) $emplist = $emplistWithDeptAndTnt;

		return $emplist;
	}

	function isLastApprover($employeeid){
		return $this->db->query("SELECT * FROM campus_office WHERE (hrhead = '$employeeid' OR phead = '$employeeid')")->num_rows();
	}

	function employeeScheduleList($employeeid, $date){
		return $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid = '$employeeid' AND DATE(dateactive) <= DATE('$date') ");
	}

	function getEmployeePhoto($empid){
		$empPhoto = $this->db->query("SELECT * FROM employee_photo where employeeid = '$empid'");
		return $empPhoto;
	}

	function getEmployeePhotoElfinder($empid){
		$empPhoto = $this->db->query("SELECT * FROM elfinder_file a WHERE a.name LIKE '$empid%'")->result();
		return $empPhoto;
	}

	function sortApprovalSeqPerLeave($setup){
		$arr_aprvl_seq = array();
		$arr_aprvl_seq[ $setup->dseq ] = array('position'=>'dhead' , 'head_id'=>$setup->dhead ,  'status'=>$setup->dstatus , 'date'=>$setup->ddate);
		$arr_aprvl_seq[ $setup->cseq ] = array('position'=>'chead' , 'head_id'=>$setup->chead,   'status'=>$setup->cstatus , 'date'=>$setup->cdate);
		$arr_aprvl_seq[ $setup->hrseq ] = array('position'=>'hrhead', 'head_id'=>$setup->hrhead, 'status'=>$setup->hrstatus , 'date'=>$setup->hrdate);
		$arr_aprvl_seq[ $setup->cpseq ] = array('position'=>'cphead', 'head_id'=>$setup->cphead, 'status'=>$setup->cpstatus , 'date'=>$setup->cpdate);
		$arr_aprvl_seq[ $setup->fdseq ] = array('position'=>'fdhead', 'head_id'=>$setup->fdhead, 'status'=>$setup->fdstatus , 'date'=>$setup->fddate);
		$arr_aprvl_seq[ $setup->boseq ] = array('position'=>'bohead', 'head_id'=>$setup->bohead, 'status'=>$setup->bostatus , 'date'=>$setup->bodate);
		$arr_aprvl_seq[ $setup->pseq  ] = array('position'=>'phead' , 'head_id'=>$setup->phead,  'status'=>$setup->pstatus , 'date'=>$setup->pdate);
		$arr_aprvl_seq[ $setup->upseq ] = array('position'=>'uphead', 'head_id'=>$setup->uphead, 'status'=>$setup->upstatus , 'date'=>$setup->update);
		//unset 0
		unset($arr_aprvl_seq['0']);
		//ksort
		ksort($arr_aprvl_seq);
		return $arr_aprvl_seq;
	}

	function getNotifLeaveRequest(){
		$username = $this->session->userdata("username");
		return $this->db->query("SELECT * FROM leave_app_base a INNER JOIN leave_app_emplist b ON a.id = b.base_id WHERE employeeid = '$username' AND a.isread = '0' GROUP BY a.id")->num_rows();
	}

	function getNotifManageLEAVE(){
		$username = $this->session->userdata("username");
		return $this->db->query("SELECT * FROM leave_app_emplist WHERE FIND_IN_SET('$username', approver_id) > 0 AND status = 'PENDING' AND ongoing_approver = 'me'")->num_rows();
	}

	function getNotifOBRequest(){
		$username = $this->session->userdata("username");
		return $this->db->query("SELECT * FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id WHERE employeeid = '$username' AND a.isread = '0' GROUP BY a.id")->num_rows();
	}

	function getNotifManageOB(){
		$username = $this->session->userdata("username");
		return $this->db->query("SELECT * FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id WHERE FIND_IN_SET('$username', approver_id) > 0 AND a.status = 'PENDING' AND ongoing_approver = 'me' AND ob_type = 'ob'")->num_rows();
	}

	function getNotifCorrectionRequest(){
		$username = $this->session->userdata("username");
		return $this->db->query("SELECT * FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id WHERE employeeid = '$username' AND a.isread = '0' AND type = 'CORRECTION' GROUP BY a.id")->num_rows();
	}

	function getNotifManageCorrection(){
		$username = $this->session->userdata("username");
		return $this->db->query("SELECT * FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id WHERE FIND_IN_SET('$username', approver_id) > 0 AND a.status = 'PENDING' AND ongoing_approver = 'me' AND type = 'CORRECTION'")->num_rows();
	}

	function getNotifManageOT(){
		$username = $this->session->userdata("username");
		return $this->db->query("SELECT * FROM ot_app a INNER JOIN ot_app_emplists b ON a.id = b.base_id WHERE FIND_IN_SET('$username', approver_id) > 0 AND a.status = 'PENDING' AND ongoing_approver = 'me'")->num_rows();
	}

	// Helper function to generate a 128-bit UUID
	public function generateUUID() {
		return $this->db->query("SELECT UUID() AS emp_uuid")->row()->emp_uuid;
	}
	

} //endoffile