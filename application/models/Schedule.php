<?php 
/**
 * @author Angelica Arangco
 * @copyright 2017
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Schedule extends CI_Model {


	function getOfficialSchedList($schedid=''){
		$schedlist = array();
		$wC = '';
		if($schedid) $wC .= " WHERE schedid='$schedid'";
		$res = $this->db->query("SELECT * FROM code_schedule $wC");

		foreach ($res->result() as $key => $row) {
			$schedlist[$row->schedid] = $row->description;
		}
		return $schedlist;
	}


	function getOfficialSchedDetail($schedid=''){
		
	}

	/**
	 Employee Edit / Delete Schedules
	**/
	function SCHEDactions($id,$job)
	{
		$msg = "";
		if ($job == "delete") {
			$sched_app = $this->db->query("DELETE FROM change_sched_app WHERE id='$id' ");
			if ($sched_app) {
				$sched_app_detail = $this->db->query("DELETE FROM change_sched_app_detail WHERE base_id='$id'");
				if ($sched_app_detail) {
					$sched_app_emplist = $this->db->query("DELETE FROM change_sched_app_emplist WHERE base_id='$id'");
					if ($sched_app_emplist) {
						$msg = "Successfully Deleted";
					}
					else
					{
						$msg = "Failed to saved!";
					}
				}
			}			
		}
		if($job){
			$result = $this->db->query("UPDATE change_sched_app SET status = 'CANCELLED' WHERE id='$id' ");
			if($result){
				$msg = "Successfully cancelled";
			}else{
				$msg = "Failed to cancel!";
			}
		}
		return $msg;
	}

    /**
	 * Inserts new change sched application in base table and gets last inserted id.
	 *
	 * @return int
	 */
    function insertBaseSchedApp($user, $dhead, $chead, $hrhead, $cphead, $fdhead, $bohead, $phead, $uphead, $dseq, $cseq, $hrseq, $cpseq, $fdseq, $boseq, $pseq, $upseq, $date_effective, $specific, $start, $end,$reason,$code_type, $app_type, $eids_sub, $sub_date, $isread = false, $file_info){
    	$id = "";
    	$res = $this->db->query("INSERT INTO change_sched_app (
    			applied_by,  dhead,		chead,	 hrhead, cphead, 	fdhead, 	bohead, 	phead, 	uphead, 	dseq, 	cseq, 	hrseq, cpseq, 	fdseq, 		boseq, 		pseq, 	upseq, date_effective, date_applied, isTemporary, dfrom, dto,reason,code_type, type, sub_empid, sub_date, isread) VALUES (
    			'$user', 	'$dhead', '$chead', '$hrhead', '$cphead', '$fdhead', '$bohead', '$phead', '$uphead', '$dseq', '$cseq', '$hrseq', '$cpseq', '$fdseq', '$boseq', '$pseq', '$upseq', '$date_effective', CURRENT_DATE, '$specific', '$start', '$end','$reason','$code_type','$app_type','$eids_sub','$sub_date','$isread')
    			");
    	if($res){
    		$dbname = $this->db->database_files;
    		$id = $this->db->insert_id();
    		$file_info["base_id"] = $id;
    		$this->db->insert($dbname.".change_sched_app_files", $file_info);
		}  	
    	return $id;
    }
    function earlydismissal($id,$rangefrom,$rangeto,$tardy,$absent,$early,$job,$year,$sequences)
	{

        $return = array("err_code"=>0,"msg"=>'');
		if ($job =="update") {
			$tardy = $tardy * 60;
			$tardy = $this->attcompute->sec_to_hm($tardy);
			$absent = $absent * 60;
			$absent = $this->attcompute->sec_to_hm($absent);
			$early = $early * 60;
			$early = $this->attcompute->sec_to_hm($early);

			$stime=$etime=$total=$day=$b=$ttardy=$tabsent=$tearly=$r=$tstart=$tend=$tardyset=$comptardy=$absentset=$compabsent=$earlyset=$compearly="";
						//EMPLOYE SCHEDULE
						$query = $this->db->query("SELECT dayofweek,employeeid,starttime,endtime FROM employee_schedule WHERE DATE_FORMAT(dateedit,'%Y')= '{$year}'  AND (leclab='LEC' OR leclab= 'LAB')");
						if ($query->num_rows()>0) {
						  // print_r($query->result());
						 foreach ($query->result()  as $key => $row) {
						            
						           $stime = $row->starttime;
						           $etime = $row->endtime;
						           $day   = $row->dayofweek;
						           $tstart = date('H:i:s',strtotime($row->starttime));
						           $tend  = date('H:i:s',strtotime($row->endtime));
						           $id = $row->employeeid;
						           $total = (abs(strtotime($row->starttime) - strtotime($row->endtime))/ 3600)*60;
						         
						           
						          
						           $tardyset = date('H:i:s',strtotime($tardy));
						           $comptardy = strtotime($tardyset) - strtotime("00:00:00");
						           $ttardy = date("H:i:s A",strtotime($tstart)+$comptardy);

						          
						          
						           $absentset = date('H:i:s',strtotime($absent));
						           $compabsent = strtotime($absentset) - strtotime("00:00:00");
						           $tabsent = date("H:i:s A",strtotime($tstart) + $compabsent);

						          
						          
						           $earlyset = date('H:i:s',strtotime($early));
						           $compearly = strtotime($earlyset) - strtotime("00:00:00");
						           $tearly = date("H:i:s A",strtotime($tend) - $compearly);

						           // echo 'START TIME '.$row->starttime.'<br> END TIME '.$row->endtime. '<br> TARDY '. $ttardy. '<br> ABSENT '.$tabsent .' EARLY '. $tabsent;  

						           if($total >= $rangefrom && $total <= $rangeto)
						            {
						              $query = $this->db->query("UPDATE employee_schedule SET tardy_start='{$ttardy}',absent_start='{$tabsent}',early_dismissal='{$tearly}' WHERE dayofweek='{$day}' AND employeeid='$id' AND starttime='{$tstart}' AND endtime='{$tend}'  ");
						              
						            } 
						             
						         }
						}
						//EMPLOYE SCHEDULE HISTORY
						$query = $this->db->query("SELECT dayofweek,employeeid,starttime,endtime FROM employee_schedule_history WHERE DATE_FORMAT(dateactive,'%Y')= '{$year}'  AND (leclab='LEC' OR leclab= 'LAB')");
						if ($query->num_rows()>0) {
						  // print_r($query->result());
						 foreach ($query->result()  as $key => $row) {
						            
						           $stime = $row->starttime;
						           $etime = $row->endtime;
						           $day   = $row->dayofweek;
						           $tstart = date('H:i:s',strtotime($row->starttime));
						           $tend  = date('H:i:s',strtotime($row->endtime));
						           $id = $row->employeeid;
						           $total = (abs(strtotime($row->starttime) - strtotime($row->endtime))/ 3600)*60;
						         
						           
						          
						           $tardyset = date('H:i:s',strtotime($tardy));
						           $comptardy = strtotime($tardyset) - strtotime("00:00:00");
						           $ttardy = date("H:i:s A",strtotime($tstart)+$comptardy);

						          
						          
						           $absentset = date('H:i:s',strtotime($absent));
						           $compabsent = strtotime($absentset) - strtotime("00:00:00");
						           $tabsent = date("H:i:s A",strtotime($tstart) + $compabsent);

						          
						          
						           $earlyset = date('H:i:s',strtotime($early));
						           $compearly = strtotime($earlyset) - strtotime("00:00:00");
						           $tearly = date("H:i:s A",strtotime($tend) - $compearly);

						           // echo 'START TIME '.$row->starttime.'<br> END TIME '.$row->endtime. '<br> TARDY '. $ttardy. '<br> ABSENT '.$tabsent .' EARLY '. $tabsent;  

						           if($total >= $rangefrom && $total <= $rangeto)
						            {
						              $query = $this->db->query("UPDATE employee_schedule_history SET tardy_start='{$ttardy}',absent_start='{$tabsent}',early_dismissal='{$tearly}' WHERE dayofweek='{$day}' AND employeeid='$id' AND starttime='{$tstart}' AND endtime='{$tend}'  ");
						              
						            } 
						             
						         }
						}
						
						$query = $this->db->query("Update earlydismissal SET rangefrom='{$rangefrom}',rangeto='{$rangeto}',tardy='{$tardy}',absent='{$absent}',early='{$early}',year='{$year}',sequence='{$sequences}' WHERE id='{$id}'");
						if (count(array($query)) >0)
							$return = array("err_code"=>0,"msg"=>'Successfully updated!');
						else
							$return = array("err_code"=>2,"msg"=>'Unable to update data!');
					
		}
		else {
			$query = $this->db->query("DELETE FROM earlydismissal WHERE id='{$id}'");
			if ($query) $return = array("err_code"=>0,"msg"=>'Successfully deleted!');
			else
			$return = array("err_code"=>2,"msg"=>'Unable to delete data!');
		}
		// echo $query;die;
		return $return;
		
	}

    /**
	 * Inserts sched app details as referenced to base table.
	 *
	 * @return query result
	 */
    function insertSchedAppDetail($base_id, $timesched, $tnt,$reason){
    	$res = '';
		$sched_list = explode("|",$timesched);
		foreach($sched_list as $slist){
			$nosched = 0;
			$halfsched = 0;
			list($dow,$idx,$tsched,$tardy,$absent,$halfabsent,$earlyd,$leclab,$toremove,$course,$section,$subject,$aimsdept) = explode("~u~",$slist);
			  $extsched = explode("-",$tsched);
			  $start_time = date("H:i:s",strtotime($extsched[0]));
			  $end_time = date("H:i:s",strtotime($extsched[1]));
			  $tardy = $tardy ? date("H:i:s",strtotime($tardy)) : "";
			  $absent = $absent ? date("H:i:s",strtotime($absent)) : "";
			  $halfabsent = $halfabsent ? date("H:i:s",strtotime($halfabsent)) : "";
			  $earlyd = $earlyd ? date("H:i:s",strtotime($earlyd)) : "";
			  if($toremove=="checked") $start_time = $end_time = "00:00:00";

			  if($tnt == 'nonteaching') $leclab = '';
    			
    		  $res = $this->db->query("INSERT INTO change_sched_app_detail (base_id, starttime, endtime, dayofweek, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab,course,section,subject,aimsdept,reason) 
							VALUES('$base_id','$start_time','$end_time','$dow','$idx','$tardy','$absent','$halfabsent','$earlyd','$leclab','$aimsdept','$section','$subject','$aimsdept','$reason')");
			  
		}
		return $res;
    }

    /**
	 * Inserts app in secondary table for list of employees.
	 *
	 * @return int
	 */
    function insertSchedAppEmpList($base_id, $arr_emplist, $teachingType, $dstatus, $ddate, $user, $reason, $code_type){
    	$empcount = $isread = 0;
    	$arr_data_failed = array();
    	// $isread = 1;
    	foreach ($arr_emplist as $employeeid) {

    		if(isset($employeeid["employeeid"])) $employeeid = $employeeid["employeeid"];

			$res = $this->db->query("
				INSERT INTO change_sched_app_emplist (base_id, employeeid, teachingType, dstatus, ddate, isread, reason, code_type) VALUES ('$base_id', '$employeeid', '$teachingType', '$dstatus', '$ddate', '1', '$reason', '$code_type')
			");
			if($res) $empcount++;
			else array_push($arr_data_failed, $employeeid);

		}
		return array($empcount,$arr_data_failed);
    }

    /*
    * new function for ica-hyperion 21194
    * by justin (with e)
    */	
    # save change sched app
    function insertSchedAppEmpListByAdmin($base_id, $empID, $teachingType, $dstatus, $ddate, $user, $isDirectApproved, $reason, $code_type=""){
    	$isread = 1;
    	
    	# para sa direct approved
    	$status_col = $status_val = '';
    	if($isDirectApproved == 0){
    		$status_col = ', status';
    		$status_val = ", 'APPROVED'";
    	}

    	$res = $this->db->query("INSERT INTO change_sched_app_emplist (base_id, employeeid, teachingType, dstatus, ddate, isread, reason,code_type, isDirectApproved {$status_col}) VALUES ('$base_id', '$empID', '$teachingType', '$dstatus', '$ddate', '$isread', '$reason','$code_type' , '$isDirectApproved' $status_val)");

    	$csid = '';

    	if($res) $csid = $this->db->insert_id();  //< @Angelica Ticket #ICA-HYPERION21362
    	
    	return $csid;
    }

    # get list change sched app
    function getChangeSchedListByAdmin($user,$category='',$dfrom='',$dto='',$isLoad=0,$type="change",$tnt=""){
    	$WC = '';
    	# query
		// var_dump("<pre>",$isLoad);die;
    	$sql = "SELECT csa.id AS base_id,csae.id AS csid, csae.`employeeid` AS empId, CONCAT(e.`lname`, ', ', e.`fname`, ' ', e.`mname`) AS fullname, csad.`timestamp`,csa.`date_effective`, 
    			csa.`isTemporary`, csa.`dfrom`, csa.`dto`, csae.`reason`, csa.`status`, type, (SELECT 1 FROM change_sched_app_emplist_items WHERE csa.id = base_id AND status = 'APPROVED' LIMIT 1) as hasApproved
					FROM change_sched_app csa
					LEFT JOIN change_sched_app_detail csad ON csad.`base_id` = csa.`id`
					LEFT JOIN change_sched_app_emplist csae ON csae.`base_id` = csa.`id`
					LEFT JOIN employee e ON e.`employeeid` = csae.`employeeid`
						WHERE csae.employeeid != '' AND type = '$type'";
		
		if($isLoad > 0){
			if($dfrom && $dto) $sql .=  " AND (csa.dfrom BETWEEN '{$dfrom}' AND '{$dto}') AND (csa.dto BETWEEN '{$dfrom}' AND '{$dto}') OR csa.date_effective BETWEEN '{$dfrom}' AND '{$dto}'"; #kapag may sinelect na date..
			if($category) $sql .= " AND csae.`status`='{$category}'"; # kapag sinelect si category..
		}else{
			$sql .= " AND csae.`status`='PENDING'"; // kapag 0 lahat ng data na idi-displayed nya sa cs_history_admin ay pending. para sa default displayed ito..
		}
		if($tnt){
			if($tnt != "trelated") $sql .= " AND e.teachingtype = '$tnt' ";
			else $sql .= " AND e.teachingtype='teaching' AND e.trelated = '1'";
		}
		$utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (e.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (e.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (e.deptid, '$utdept') OR FIND_IN_SET (e.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND e.employeeid = 'nosresult'";
          $usercampus = $this->extras->getCampusUser();
            $utwc .= " AND FIND_IN_SET (e.campusid,'$usercampus') ";
        }
        $sql .= $utwc;

		$sql .= " GROUP BY base_id ORDER BY base_id;";
		return $query = $this->db->query($sql)->result();
    }
    /*
    * end of new function for ica-hyperion 21194
    */

    function insertSchedAppEmpListHead($base_id, $arr_emplist, $dhead, $chead, $dseq, $cseq, $user){
    	$empcount = $isread = 0;
    	foreach ($arr_emplist as $employeeid) {
    		if($employeeid == $user) $isread = 1;
    		$dstatus = "PENDING";
    		$ddate = "";
    		$cstatus = "PENDING";
    		$cdate = "";

    		if($dhead <> $chead){
	    		if(in_array($dhead, $arr_emplist)){
	    			if($dseq == 1){
	    				$dstatus = "APPROVED";
		        		$ddate 	 = date_format( new DateTime('today') ,"Y-m-d");
	    			}
	    			$res = $this->db->query("
	    				INSERT INTO change_sched_app_emplist (base_id, employeeid, dstatus, ddate, isread) VALUES ('$base_id', '$employeeid', '$dstatus', '$ddate' , '$isread')
	    			");
	    			if($res) $empcount++;
	    		}
    			
	    		if(in_array($chead, $arr_emplist)){
	    			if($cseq ==  1){
	    				$cstatus = "APPROVED";
		        		$cdate 	 = date_format( new DateTime('today') ,"Y-m-d");
	    			}
	    			$res = $this->db->query("
	    				INSERT INTO change_sched_app_emplist (base_id, employeeid, cstatus, cdate, isread) VALUES ('$base_id', '$employeeid', '$cstatus', '$cdate' , '$isread')
	    			");
	    			if($res) $empcount++;
	    		}
			}else{
	    		if(in_array($dhead, $arr_emplist) || in_array($chead, $arr_emplist)){
	    			if($dseq == 1){
	    				$dstatus = "APPROVED";
		        		$ddate 	 = date_format( new DateTime('today') ,"Y-m-d");
	    			}
	    			if($cseq ==  1){
	    				$cstatus = "APPROVED";
		        		$cdate 	 = date_format( new DateTime('today') ,"Y-m-d");
	    			}
	    			$res = $this->db->query("
	    				INSERT INTO change_sched_app_emplist (base_id, employeeid, dstatus, ddate, cstatus, cdate, isread) VALUES ('$base_id', '$employeeid', '$dstatus', '$ddate' , '$cstatus', '$cdate' , '$isread')
	    			");
	    			if($res) $empcount++;
	    		}
			}
			$isread = 0;
		}
		return $empcount;
    }

    /**
	 * Gets request setup based on code_request.
	 *
	 * @param string $type (Default: "")
	 *
	 * @return stdClass Object
	 */
    function getAppSequence($type=""){
    	$res = $this->db->query("SELECT * FROM code_request_form WHERE code_request='$type'");
    	return $res;
    }

     /**
	 * Gets request details based on ot app id.
	 *
	 * @param string $otid (Default: "")
	 *
	 * @return stdClass Object
	 */
    function getAppSequencePerSched($id=''){
    	$res = $this->db->query("SELECT * FROM change_sched_app_emplist a INNER JOIN change_sched_app b ON a.`base_id`=b.`id` WHERE a.id='$id'");
    	return $res;
    }

    /**
	 * Get list of days in a week.
	 *
	 * @return array
	 */
   	function getSchedDays(){
        $res = $this->db->query("SELECT day_index, day_code, day_name FROM code_daysofweek ORDER BY day_index");    
        $schedDays = array();  
        if($res->num_rows() > 0 ){
        	foreach ($res->result() as $key => $row) {
        		$schedDays[$row->day_index] = array('day_code'=>$row->day_code,'day_name'=>$row->day_name);
        	}
        }     
        $sun = $schedDays[0];
        unset($schedDays[0]);
        $schedDays[0] = $sun;         
        return $schedDays;
    }

	/**
	 * Sorts approval heads based on sequence. Stores sorted details in array.
	 *
	 * @param stdClass Object $setup approval sequence details of specific OT
	 *
	 * @return array
	 */
	function sortApprovalSeq($setup){
		$this->load->model('employee');
		$this->load->model('utils');
		$user = $this->session->userdata('username');
		$deptid = $this->employee->getempdatacol('office');
		$campusid = $this->employee->getempdatacol('campusid');

		$chead = $dhead = $cphead = $dphead = '';

		$isClusterHead = $isDeptHead = $isCpHead = $isDpHead = false;
		$isCluster_q = $this->db->query("SELECT code FROM code_office a INNER JOIN campus_office b ON a.code = b.base_code WHERE b.divisionhead='$user'");
		if($isCluster_q->num_rows() > 0) $isClusterHead = true;
		$isHead_q = $this->db->query("SELECT code FROM code_office a INNER JOIN campus_office b ON a.code = b.base_code WHERE b.dhead='$user'");
		if($isHead_q->num_rows() > 0) $isDeptHead = true;
		$isCp_q = $this->db->query("SELECT code FROM code_campus WHERE campus_principal='$user'");
		if($isCp_q->num_rows() > 0) $isCpHead = true;
		$isDp_q = $this->db->query("SELECT code FROM code_department WHERE head='$user'");
		if($isDp_q->num_rows() > 0) $isDpHead = true;

		if($isClusterHead) 	$chead = $user;
		if($isDeptHead) 	$dhead = $user;
		if($isCpHead) 		$cphead = $user;
		if($isDpHead) 		$dphead = $user;

		/*$dhead = $this->overtime->getDeptHead('head',		$deptid);	
		$chead = $this->overtime->getDeptHead('divisionhead',$deptid);*////< user must be divisionhead of his own department to be counted as cluster head
		$hrhead = $this->utils->getDeptHead('hrhead',		'');
		$prhead = $this->utils->getDeptHead('phead',		'');

		if($setup->hhseq==0){
			$setup->hhseq = $setup->pseq;
			$setup->pseq = 0;
			$hrhead = $prhead;
		}


		$arr_aprvl_seq = array();
		$arr_aprvl_seq[ $setup->dhseq ] = array('position'=>'dhead' , 'head_id'=>$dhead);
		$arr_aprvl_seq[ $setup->chseq ] = array('position'=>'chead' , 'head_id'=>$chead);
		$arr_aprvl_seq[ $setup->hhseq ] = array('position'=>'hrhead', 'head_id'=>$hrhead);
		$arr_aprvl_seq[ $setup->cpseq ] = array('position'=>'cphead', 'head_id'=>$cphead);
		$arr_aprvl_seq[ $setup->dpseq ] = array('position'=>'dphead', 'head_id'=>$dphead);
		$arr_aprvl_seq[ $setup->fdseq ] = array('position'=>'fdhead', 'head_id'=>$setup->financedir);
		$arr_aprvl_seq[ $setup->boseq ] = array('position'=>'bohead', 'head_id'=>$setup->budgetoff);
		$arr_aprvl_seq[ $setup->pseq  ] = array('position'=>'phead' , 'head_id'=>$setup->president);
		$arr_aprvl_seq[ $setup->upseq ] = array('position'=>'uphead', 'head_id'=>($setup->univphy . 
											($setup->univphyt <> ""?(",".$setup->univphyt):"")));
		//unset 0 , those not included in sequence
		unset($arr_aprvl_seq['0']);

		//ksort
		ksort($arr_aprvl_seq);
		return $arr_aprvl_seq;
	}

	/**
	 * getting sequence for approved cs application.
	 *
	 * @param object application sequence
	 *
	 * @return array
	 */

	function sortApprovalSeqApprovedApplication($setup){
		$this->load->model('employee');
		$this->load->model('utils');
		$user = $this->session->userdata('username');
		$deptid = $this->employee->getempdatacol('office');
		$campusid = $this->employee->getempdatacol('campusid');

		$chead = $dhead = $cphead = $dphead = '';

		$isClusterHead = $isDeptHead = $isCpHead = $isDpHead = false;
		$isCluster_q = $this->db->query("SELECT code FROM code_office a INNER JOIN campus_office_history b ON a.code = b.base_code WHERE b.divisionhead='$user'");
		if($isCluster_q->num_rows() > 0) $isClusterHead = true;
		$isHead_q = $this->db->query("SELECT code FROM code_office a INNER JOIN campus_office_history b ON a.code = b.base_code WHERE b.dhead='$user'");
		if($isHead_q->num_rows() > 0) $isDeptHead = true;
		$isCp_q = $this->db->query("SELECT code FROM code_campus WHERE campus_principal='$user'");
		if($isCp_q->num_rows() > 0) $isCpHead = true;
		$isDp_q = $this->db->query("SELECT code FROM code_department WHERE head='$user'");
		if($isDp_q->num_rows() > 0) $isDpHead = true;

		if($isClusterHead) 	$chead = $user;
		if($isDeptHead) 	$dhead = $user;
		if($isCpHead) 		$cphead = $user;
		if($isDpHead) 		$dphead = $user;

		/*$dhead = $this->overtime->getDeptHead('head',		$deptid);	
		$chead = $this->overtime->getDeptHead('divisionhead',$deptid);*////< user must be divisionhead of his own department to be counted as cluster head
		$hrhead = $this->utils->getDeptHead('hrhead',		'');
		$prhead = $this->utils->getDeptHead('phead',		'');

		if($setup->hhseq==0){
			$setup->hhseq = $setup->pseq;
			$setup->pseq = 0;
			$hrhead = $prhead;
		}


		$arr_aprvl_seq = array();
		$arr_aprvl_seq[ $setup->dhseq ] = array('position'=>'dhead' , 'head_id'=>$dhead);
		$arr_aprvl_seq[ $setup->chseq ] = array('position'=>'chead' , 'head_id'=>$chead);
		$arr_aprvl_seq[ $setup->hhseq ] = array('position'=>'hrhead', 'head_id'=>$hrhead);
		$arr_aprvl_seq[ $setup->cpseq ] = array('position'=>'cphead', 'head_id'=>$cphead);
		$arr_aprvl_seq[ $setup->dpseq ] = array('position'=>'dphead', 'head_id'=>$dphead);
		$arr_aprvl_seq[ $setup->fdseq ] = array('position'=>'fdhead', 'head_id'=>$setup->financedir);
		$arr_aprvl_seq[ $setup->boseq ] = array('position'=>'bohead', 'head_id'=>$setup->budgetoff);
		$arr_aprvl_seq[ $setup->pseq  ] = array('position'=>'phead' , 'head_id'=>$setup->president);
		$arr_aprvl_seq[ $setup->upseq ] = array('position'=>'uphead', 'head_id'=>($setup->univphy . 
											($setup->univphyt <> ""?(",".$setup->univphyt):"")));
		//unset 0 , those not included in sequence
		unset($arr_aprvl_seq['0']);

		//ksort
		ksort($arr_aprvl_seq);
		return $arr_aprvl_seq;
	}

	/**
	 * Gets list of OT applications for given approver to manage.
	 *
	 * @return stdClass Object
	 */
	function getCSAppListToManage($user="", $colhead="", $colstatus='', $status="", $prevcolstatus='',$datefrom="", $dateto="",$teachingType='',$seq_count='',$deptid='',$office='',$type="change"){
		$colseq =  $colhead ? (substr($colhead,0,-4) . 'seq') : '';

		$wC = "";
    	if($datefrom && $dateto) $wC .= " AND b.`date_applied` BETWEEN '$datefrom' AND '$dateto'";
    	if($status)			 	 $wC .= " AND $colstatus='$status'";
		// if($colseq)			 	 $wC .= " AND $colseq!='0'";
		if($seq_count)			 $wC .= " AND ($colseq='$seq_count' OR $colseq='0')";
    	if($prevcolstatus) 	 	 $wC .= " AND $prevcolstatus='APPROVED'";
    	if($teachingType) 	 	 $wC .= " AND c.teachingType='$teachingType'";
    	if($deptid)		 		 $wC .= " AND c.deptid='$deptid'";
    	if($office)		 		 $wC .= " AND c.office='$office'";
    	if($type)		 		 $wC .= " AND b.type='$type'";

		$res = $this->db->query("SELECT a.id AS csid, REPLACE(CONCAT(c.LName, ', ', c.FName, ' ', c.MName),'Ã‘','Ñ') AS fullname,
  			b.*,b.`reason` AS getReason, a.* FROM change_sched_app_emplist a  INNER JOIN change_sched_app b ON b.id = a.`base_id` INNER JOIN employee c ON a.`employeeid` = c.`employeeid` 
								WHERE ($colhead='$user') AND a.status != 'DISAPPROVED' $wC");
		return $res;
	}

	/**
	 * Gets App details per application id.
	 *
	 * @param string $csid (Default: "")
	 * @param string $colstatus (Default: "")
	 *
	 * @return array
	 */
	function getSchedEmpDetails($csid='', $colstatus=''){
		$data =array();
		$res = $this->db->query("SELECT a.id AS csid, REPLACE(CONCAT(c.LName,', ',c.FName,' ',c.MName), 'Ã‘', 'Ñ') AS fullname,e.description AS epos, d.description AS edept, g.description AS eoffice, c.teachingtype,b.date_effective AS effective_date, b.code_type as codetype, a.reason as reasons, a.*,b.*,f.*
									FROM change_sched_app_emplist a
									INNER JOIN change_sched_app b ON b.id=a.`base_id`
									INNER JOIN employee c ON a.`employeeid`=c.`employeeid`
									LEFT JOIN code_position e ON c.positionid = e.positionid
									LEFT JOIN code_department d ON c.deptid = d.code 
									LEFT JOIN code_office g ON c.office = g.code 
									INNER JOIN change_sched_app_detail f ON b.id=f.`base_id`
									WHERE a.id='$csid'");
		if($res->num_rows() > 0){
			foreach ($res->result() as $obj) {
				$isadmin = $this->extras->findIfAdmin($obj->applied_by);
				$data['isadmin'] 		= $isadmin;
				$data['csid'] 			= $obj->csid;
				$data['base_id'] 		= $obj->base_id;
				$data['employeeid'] 	= $obj->employeeid;
				$data['date_applied'] 	= $obj->date_applied;
				$data['status'] 		= $obj->status;
				if($colstatus && isset($obj->$colstatus)) 	$data['colstat'] = $obj->$colstatus; 
				$data['fullname'] 		= $obj->fullname;
				$data['pos'] 			= $obj->epos;
				$data['edept']  		= $obj->edept;
				$data['eoffice']  		= $obj->eoffice;
				$data['teachingtype']  		= $obj->teachingtype;
				$data['date_effective']	= $obj->effective_date;
				$data['isTemporary']	= $obj->isTemporary;
				$data['dfrom']  		= $obj->dfrom;
				$data['dto']  			= $obj->dto;
				$data['course']  		= $obj->course;
				$data['section']  		= $obj->section;
				$data['subject']  		= $obj->subject;
				$data['aimsdept']  		= $obj->aimsdept;
				$data['code_type']  		= $obj->codetype;
				$data['reason']  		= $obj->reasons;
				$data['type']  		= $obj->type;
				$data['sub_empid']  		= $obj->sub_empid;
				$data['sub_date']  		= $obj->sub_date;
			}
		}
		return $data;
	}

	/**
	 * Get sched app detail from secondary table with given base id.
	 *
	 * @return query result
	 */
	function getSchedDetails($base_id='',$colstatus=''){
		$data='';
		$res = $this->db->query("SELECT *
								FROM change_sched_app_detail a 
								LEFT JOIN code_daysofweek b ON a.dayofweek = b.day_code WHERE base_id='$base_id'
								");
		if($res->num_rows() > 0){
			$data = $res->result();
		}
		return $data;
	}

	function saveChangeSchedule($scid='',$timesched='',$reason='', $date_active='', $start='', $end='')
	{
		$sched_list = explode("|",$timesched);
			foreach($sched_list as $slist){
				$nosched = 0;
				$halfsched = 0;
				list($detail_id,$dow,$idx,$tsched,$tardy,$absent,$halfabsent,$earlyd,$leclab) = explode("~u~",$slist);
				  $extsched = explode("-",$tsched);
				  $start_time = date("H:i:s",strtotime($extsched[0]));
				  $end_time = date("H:i:s",strtotime($extsched[1]));
				  $tardy = $tardy ? date("H:i:s",strtotime($tardy)) : "";
				  $absent = $absent ? date("H:i:s",strtotime($absent)) : "";
				  $halfabsent = $halfabsent ? date("H:i:s",strtotime($halfabsent)) : "";
				  $earlyd = $earlyd ? date("H:i:s",strtotime($earlyd)) : "";
				  // if($isremove=="checked") $start_time = $end_time = "00:00:00";

		  // if($tnt == 'nonteaching') $leclab = '';
			if($date_active){
				$this->db->query("UPDATE change_sched_app SET date_effective = '$date_active', dfrom = '', dto = '', isTemporary = '0' WHERE id = '$scid'");
			}elseif($start && $end){
				$this->db->query("UPDATE change_sched_app SET dfrom = '$start', dto = '$end', date_effective = '', isTemporary = '1' WHERE id = '$scid'");
			}

			$this->db->query("UPDATE change_sched_app SET reason = '$reason' WHERE id = '$scid'");

    		$res = $this->db->query("UPDATE change_sched_app_detail SET 
	    		  						starttime = '$start_time',
	    		  						endtime = '$end_time',
	    		  						dayofweek = '$dow',
	    		  						idx = '$idx',
	    		  						tardy_start = '$tardy',
	    		  						absent_start = '$absent',
	    		  						absent_half_start = '$halfabsent',
	    		  						early_dismissal = '$earlyd',
	    		  						leclab = '$leclab',
	    		  						reason = '$reason'
	    		  					WHERE id='$detail_id'
								");
				  
			}
			return $res;

	}

	/**
	 * Saves new status of application made by approver. Inserts changes to official schedule if approved.
	 *
	 * @return query result
	 */
	function saveSchedStatusChange($user='',$csid='',$employeeid='', $status='',$colstatus='',$coldate='',$colhead='',$isLastApprover='', $timesched='', $base_id='', $date_active='',$reason='',$prev_colhead='', $endorse='', $bypassed=0){
		$res = $prev_wC ='';

		if($colhead) 			$prev_wC = " AND $colhead='$user'";
		if($prev_colhead) 		$prev_wC = " AND $prev_colhead='$user'";
		$test_q = $this->db->query("SELECT a.id, a.base_id FROM change_sched_app_emplist a INNER JOIN change_sched_app b ON b.id=a.base_id WHERE a.id='$csid' $prev_wC");

		if($test_q->num_rows() > 0){
			$base_id = $test_q->row()->base_id;
			if($colstatus == 'hrstatus'){
				$sched_list = explode("|",$timesched);
				foreach($sched_list as $slist){
					$nosched = 0;
					$halfsched = 0;
					list($detail_id,$dow,$idx,$tsched,$tardy,$absent,$halfabsent,$earlyd,$leclab,$isremove) = explode("~u~",$slist);
					  $extsched = explode("-",$tsched);
					  $start_time = date("H:i:s",strtotime($extsched[0]));
					  $end_time = date("H:i:s",strtotime($extsched[1]));
					  $tardy = $tardy ? date("H:i:s",strtotime($tardy)) : "";
					  $absent = $absent ? date("H:i:s",strtotime($absent)) : "";
					  $halfabsent = $halfabsent ? date("H:i:s",strtotime($halfabsent)) : "";
					  $earlyd = $earlyd ? date("H:i:s",strtotime($earlyd)) : "";
					  if($isremove=="checked") $start_time = $end_time = "00:00:00";

					  // if($tnt == 'nonteaching') $leclab = '';
		    			
		    		    $this->db->query("UPDATE change_sched_app_detail SET 
		    		  						starttime = '$start_time',
		    		  						endtime = '$end_time',
		    		  						dayofweek = '$dow',
		    		  						idx = '$idx',
		    		  						tardy_start = '$tardy',
		    		  						absent_start = '$absent',
		    		  						absent_half_start = '$halfabsent',
		    		  						early_dismissal = '$earlyd',
		    		  						leclab = '$leclab',
		    		  						reason = '$reason'
		    		  					WHERE id='$detail_id'
									");
					  
				}
			}
			
			if($date_active) $this->db->query("UPDATE change_sched_app SET date_effective='$date_active' WHERE id='$base_id'");
			if($bypassed){
				$username = $this->session->userdata("username");
				$this->db->query("UPDATE change_sched_app SET bypassed = '1', bypassed_by = '$username',bypassed_date='".date('Y-m-d')."' WHERE id = '$base_id' ");
			}

			if($status == 'DISAPPROVED' || $isLastApprover){
				// additional by justin (with e)  for ica-hyperion 21983
				if($this->session->userdata("usertype") == "ADMIN"){
					$res = $this->db->query("UPDATE change_sched_app_emplist SET `status`='$status', isApprovedByAdmin=1 WHERE id='$csid'");

					$isLastApprover = true;
				}else $res = $this->db->query("UPDATE change_sched_app_emplist SET $colstatus='$status', $coldate=CURRENT_DATE, status='$status' WHERE id='$csid'");
			}else{
				// additional by justin (with e)  for ica-hyperion 21983
				if($this->session->userdata("usertype") == "ADMIN"){
					$res = $this->db->query("UPDATE change_sched_app_emplist SET `status`='$status', isApprovedByAdmin=1 WHERE id='$csid'");

					$isLastApprover = true;
				}else $res = $this->db->query("UPDATE change_sched_app_emplist SET $colstatus='$status', $coldate=CURRENT_DATE WHERE id='$csid'");
			}


			if($status == 'APPROVED' && $isLastApprover){
				///<check if temporary schedule
				$isTemporary = $dfrom = $dto = "";
				$tmp = $this->db->query("SELECT isTemporary, dfrom, dto FROM change_sched_app WHERE id='$csid'");

				if($tmp->num_rows() > 0){
					$isTemporary 	= $tmp->row(0)->isTemporary;
					$dfrom 			= $tmp->row(0)->dfrom;
					$dto 			= $tmp->row(0)->dto;
				}

				if($isTemporary){
					$dow = '';
					$dow_q = $this->db->query("SELECT GROUP_CONCAT(CONCAT_WS(',',DAYOFWEEK)) as dow FROM change_sched_app_detail WHERE base_id='$csid'");
					if($dow_q->num_rows() > 0) $dow = $dow_q->row(0)->dow;
				
					///< insert initial sched with diff date active
					$this->db->query("INSERT INTO employee_schedule_history(employeeid, starttime, endtime, dayofweek, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab,dateactive,aimsdept,section,subject) 
			                             (SELECT DISTINCT  employeeid, starttime, endtime, DAYOFWEEK, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab, CONCAT('$dto',' 23:59:00'),aimsdept,section,subject
											FROM employee_schedule_history a WHERE employeeid='$employeeid'
											AND dateactive = 
											(SELECT z.dateactive FROM employee_schedule_history z 
											WHERE z.`employeeid` = a.`employeeid` GROUP BY dateactive ORDER BY dateactive DESC LIMIT 1))");
	


					///< insert temp sched
					 $res = $this->db->query("INSERT INTO employee_schedule_history(employeeid, starttime, endtime, dayofweek, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab,dateactive,aimsdept,section,subject) 
			                             (SELECT employeeid, starttime, endtime, DAYOFWEEK, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab,CONCAT((DATE(c.dfrom)-INTERVAL 1 DAY),' 23:59:00'),aimsdept,section,subject
											FROM change_sched_app_emplist a 
											INNER JOIN change_sched_app c ON c.`id`=a.`base_id`
											INNER JOIN change_sched_app_detail b ON a.`base_id`=b.`base_id` 
											WHERE a.employeeid='{$employeeid}' AND a.id='$csid')");

					/*$this->db->query("INSERT INTO employee_official_schedule_history (employeeid,datefrom,dateto,start_time,end_time,tardy,absent,halfday_absent,early_dismissal,user,timestamp) VALUES ('$employeeid','$dfrom','$dto','$fromtime','$totime','$tardy','$fabsent','$habsent','$earlyd','$uname','".date('Y-m-d h:i:s')."')");*/


				}else{


					$this->db->query("DELETE FROM employee_schedule WHERE employeeid = '$employeeid'");

		            $this->db->query("INSERT INTO employee_schedule(employeeid, starttime, endtime, dayofweek, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab,dateedit,aimsdept,section,subject) 
			                             (SELECT employeeid, starttime, endtime, DAYOFWEEK, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab,CONCAT((DATE(c.date_effective)-INTERVAL 1 DAY),' 00:00:00)',aimsdept,section,subject
											FROM change_sched_app_emplist a 
											INNER JOIN change_sched_app c ON c.`id`=a.`base_id`
											INNER JOIN change_sched_app_detail b ON a.`base_id`=b.`base_id`  
											WHERE a.employeeid='{$employeeid}' AND a.id='$csid')");

		            $res = $this->db->query("INSERT INTO employee_schedule_history(employeeid, starttime, endtime, dayofweek, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab, dateactive,aimsdept,section,subject) 
			                             (SELECT employeeid, starttime, endtime, DAYOFWEEK, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab,CONCAT((DATE(c.date_effective)-INTERVAL 1 DAY),' 00:00:00'),aimsdept,section,subject
											FROM change_sched_app_emplist a 
											INNER JOIN change_sched_app c ON c.`id`=a.`base_id`
											INNER JOIN change_sched_app_detail b ON a.`base_id`=b.`base_id` 
											WHERE a.employeeid='{$employeeid}' AND a.id='$csid')");
					
				}
				$this->db->query("UPDATE change_sched_app_emplist SET isread=0 WHERE id='$csid'");
			}

		} ///< end if test_q

		return $res;
	}
	/**
	 * Gets list of employee applications.
	 *
	 * @return stdClass Object
	 */
    function getEmpSchedHistory($employeeid="", $datefrom="", $dateto="", $status="", $id="", $isread='', $type="change"){
    	$wC = "";
    	if($datefrom && $dateto) $wC .= " AND b.`date_applied` BETWEEN '$datefrom' AND '$dateto'";
    	if($status)				 $wC .= " AND a.`status`='$status'";
    	if($id)				 	$wC .= " AND a.id='$id'";
    	if($type)				 	$wC .= " AND b.type='$type'";
    	// if($isread <> '')		 $wC .= " AND a.isread='$isread'";
        $res = $this->db->query("SELECT a.id AS csid, a.*,b.* ,REPLACE(CONCAT(c.LName,', ',c.FName,' ',c.MName), 'Ã‘', 'Ñ') AS fullname
        							FROM change_sched_app_emplist a
									INNER JOIN change_sched_app b ON a.`base_id`=b.`id`
									INNER JOIN employee c ON a.employeeid=c.employeeid
									WHERE a.employeeid='$employeeid' 
									$wC");
									// OR b.applied_by='$employeeid' 
        return $res;
	}

	function getCSManagementHistory($category='', $deptid='', $dfrom='', $dto='', $campus='', $tnt=''){
		$wC = '';
		if($category)		$wC .= " AND a.`status`='$category'";
		if($deptid) 		$wC .= " AND c.deptid='$deptid'";
		if($campus) 		$wC .= " AND c.campusid='$campus'";
		if($tnt) 		$wC .= " AND c.teachingtype='$tnt'";
		if($dfrom && $dto)  $wC .= " AND b.`date_applied` BETWEEN '$dfrom' AND '$dto'";

        $res = $this->db->query("SELECT a.id AS csid, a.*,b.* ,REPLACE(CONCAT(c.LName,', ',c.FName,' ',c.MName), 'Ã‘', 'Ñ') AS fullname
        							FROM change_sched_app_emplist a
									INNER JOIN change_sched_app b ON a.`base_id`=b.`id`
									INNER JOIN employee c ON a.employeeid=c.employeeid
									WHERE IFNULL(a.employeeid,'')!='' 
									$wC");
        return $res;
	}

	/**
	 * Saves new change schedule application directly by HR.
	 *
	 * @return string
	 */
	function saveSchedAppHRDirect($user, $arr_emplist, $hrhead, $date_effective, $timesched, $tnt){
		$base_id = "";
		$empcount = 0;
		$start_time = $end_time = $tardy = $absent = $halfabsent = $earlyd = $leclab = $dow = $idx = '';

		$sched_list = explode("|",$timesched);
		foreach($sched_list as $slist){
			$nosched = 0;
			$halfsched = 0;
			list($dow,$idx,$tsched,$tardy,$absent,$halfabsent,$earlyd,$leclab,$isremove) = explode("~u~",$slist);
			  $extsched = explode("-",$tsched);
			  $start_time = date("H:i:s",strtotime($extsched[0]));
			  $end_time = date("H:i:s",strtotime($extsched[1]));
			  $tardy = $tardy ? date("H:i:s",strtotime($tardy)) : "";
			  $absent = $absent ? date("H:i:s",strtotime($absent)) : "";
			  $halfabsent = $halfabsent ? date("H:i:s",strtotime($halfabsent)) : "";
			  $earlyd = $earlyd ? date("H:i:s",strtotime($earlyd)) : "";
			  if($isremove=="checked") $start_time = $end_time = "00:00:00";

			  if($tnt == 'nonteaching') $leclab = '';
			  
		}

		$res = $this->db->query("INSERT INTO change_sched_app (
				applied_by,	 hrhead, hrseq,  date_effective, date_applied) VALUES (
				'$user',  '$hrhead','1', '$date_effective', CURRENT_DATE)
				");
		if($res)  	$base_id = $this->db->insert_id();

		if($base_id) $res = $this->insertSchedAppDetail($base_id, $timesched, $tnt);
		if($res){
			$datebefore = date('Y-m-d',(strtotime ( '-1 day' , strtotime ( $date_effective) ) ));
	    	foreach ($arr_emplist as $employeeid) {
				$res = $this->db->query("
					INSERT INTO change_sched_app_emplist (base_id, employeeid, status, hrstatus, hrdate) VALUES ('$base_id', '$employeeid', 'APPROVED', 'APPROVED', CURRENT_DATE)
				");
				if($res) {
					$this->db->query("DELETE FROM employee_schedule WHERE employeeid = '$employeeid'");

		            $this->db->query("INSERT INTO employee_schedule(employeeid, starttime, endtime, dayofweek, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab,dateedit) VALUES ('$employeeid', '$start_time', '$end_time', '$dow', '$idx', '$tardy', '$absent', '$halfabsent', '$earlyd', '$leclab', '$datebefore')");

		            $res = $this->db->query("INSERT INTO employee_schedule_history(employeeid, starttime, endtime, dayofweek, idx, tardy_start, absent_start, absent_half_start, early_dismissal, leclab,dateactive) VALUES ('$employeeid', '$start_time', '$end_time', '$dow', '$idx', '$tardy', '$absent', '$halfabsent', '$earlyd', '$leclab', '$datebefore')");
				}
				if($res) $empcount++;
			}
		}

		return $empcount;

	}

	function showSelectAimsDept(){
        $return = "<option value=''>Choose Aims department..</option>"; 
        $query = $this->db->query("SELECT * FROM tblCourseCategory GROUP BY GROUP_ID ORDER BY DESCRIPTION ASC")->result();
       foreach($query as $val){
          $return .= "<option value='".$val->GROUP_ID."'>".$val->DESCRIPTION."</option>";    
       }
       	return $return;
    }  

    function showSubject(){
    	// no data, connected to aims..
        $return = "<option value=''>Select an Option</option>"; 
       	return $return;
	}
	
	function showSection(){
    	// no data, connected to aims..
        $return = "<option value=''>Select an Option</option>"; 
       	return $return;
    }

	function getEmployeeScheduleHistory($employeeid='', $date_active=''){
		$where_clause = "";
        $latestda = date('Y-m-d', strtotime($this->extensions->getLatestDateActive($employeeid, $date_active)));
        if($date_active >= $latestda) $where_clause .= " AND DATE(dateactive) = DATE('$latestda')";
		$res = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid='$employeeid' $where_clause ORDER BY dateactive DESC, idx ASC, starttime ASC");
		return $res;
	}

	function getEmployeeScheduleHistoryLatest($employeeid=''){
		$res = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid='$employeeid' ORDER BY dateactive DESC");
		return $res;
	}


	function updateEmployeeScheduleHistory($user='',$sched_id='',$employeeid='',$timesched="",$dateactive_time="00:00:00"){
		$res = "";
		if($timesched){
			list($dayofweek,$starttime,$endtime,$tardy_start,$absent_start,$early_dismissal,$leclab,$flexible,$hours,$breaktime,$dateactive,$aimsval,$subjectval) = explode('~u~', $timesched);
			$datenow = $this->extensions->getServerTime();

			$dateactive = new DateTime($dateactive);
			$dateactive->modify('-1 day');
			$dateactive = $dateactive->format('Y-m-d') . " " . $dateactive_time;
			if($dateactive <= $datenow){
				$this->employeeAttendance->updateDTR($employeeid, $dateactive, $datenow);
			}
			$starttime = date("H:i:s",strtotime($starttime));
			$endtime = date("H:i:s",strtotime($endtime));
			$tardy_start = date("H:i:s",strtotime($tardy_start));
			$absent_start = date("H:i:s",strtotime($absent_start));
			$early_dismissal = date("H:i:s",strtotime($early_dismissal));
			$aims = $aimsval;
			$subject = $subjectval;

			$res = $this->db->query("UPDATE employee_schedule_history 
										SET starttime='$starttime',
											endtime='$endtime',
											tardy_start='$tardy_start',
											absent_start='$absent_start',
											early_dismissal='$early_dismissal',
											leclab='$leclab',
											flexible='$flexible',
											`hours`='$hours',
											breaktime='$breaktime',
											mode='day',
											dateactive='$dateactive',
											changeby='$user',
											aimsdept = '$aims',
											subject = '$subject'
										WHERE editstamp='$sched_id' AND dayofweek='$dayofweek' AND employeeid='$employeeid'
										");
		}
		return $res;
	}

	function deleteEmployeeScheduleHistory($sched_id='',$employeeid=''){
		$q_sched = $this->db->query("SELECT * FROM employee_schedule_history WHERE editstamp='$sched_id' AND employeeid='$employeeid'");
		if($q_sched->num_rows() > 0){
			foreach($q_sched->result() as $row){
				$starttime = $row->starttime;
				$endtime = $row->endtime;
				$idx = $row->idx;
				$dateactive = $row->dateactive;

				$this->db->query("DELETE FROM employee_schedule WHERE employeeid = '$employeeid' AND dateedit = '$dateactive' AND idx = '$idx' AND starttime = '$starttime' AND endtime = '$endtime'");
			}
		}
		$res = $this->db->query("DELETE FROM employee_schedule_history WHERE editstamp='$sched_id' AND employeeid='$employeeid'");
		return $res;
	}

	function isApprovedByAdmin($id){
		$is_admin_approved = 0;
		$status = "";

		$q_admin_approved = $this->db->query("SELECT isApprovedByAdmin, `status` FROM change_sched_app_emplist WHERE id='$id';")->result();
		foreach ($q_admin_approved as $row){
			$is_admin_approved = $row->isApprovedByAdmin;
			$status = $row->status;
		}

		return array($is_admin_approved, $status);
	}

function findFinalizedEmp($emp_list='',$date_apply=''){
		$res = "";
		foreach ($emp_list as $employeeid) {
			$resFnd = 0;
			$empIDFinal = $employeeid;
			if(strlen($empIDFinal)) $empIDFinal = "0".$employeeid;

			$findFinalEmp = $this->db->query("SELECT * FROM attendance_confirmed_nt WHERE employeeid='".$empIDFinal."' ")->result();
			foreach ($findFinalEmp as $ffe) {
				$date_apply = date('Ymd', strtotime($date_apply));
				$cutOffStart = date('Ymd', strtotime($ffe->cutoffstart));
				$cutOffEnd = date('Ymd', strtotime($ffe->cutoffend));
				if($date_apply >= $cutOffStart && $date_apply <= $cutOffEnd) $resFnd = 1; 
			}
			
			if($resFnd == 1){
				$empDetails = $this->db->query("SELECT * FROM user_info WHERE username='".$employeeid."'")->result();
				foreach ($empDetails as $ed) {
					$res = $res ."* ".$employeeid." - ".$ed->lastname.", ".$ed->firstname." ".$ed->middlename."\n";
				}
			}

		}
		
		return $res;
	}

	public function checkIfSchedExisting($employeeid, $date_active){
		return $this->db->query("SELECT * FROM `employee_schedule_history` WHERE employeeid = '$employeeid' AND DATE(dateactive) = '$date_active' ")->num_rows();
	}

	public function deleteExistingSchedule($employeeid, $date_active){
		return $this->db->query("DELETE FROM `employee_schedule_history` WHERE employeeid = '$employeeid' AND DATE(dateactive) = '$date_active' ");
	}

	public function employeeScheduleListByCourse($employeeid){
		$count = 0;
		$sched_list = array();

		$q_rate = $this->db->query("SELECT * FROM `payroll_emp_salary_perdept_history` where employeeid  = '$employeeid' and aimsdept not in (SELECT aimsdept FROM employee_schedule_history WHERE employeeid = '$employeeid'  and  aimsdept is not null) AND timestamp = (SELECT timestamp FROM payroll_emp_salary_perdept_history WHERE employeeid = '$employeeid' ORDER BY timestamp DESC LIMIT 1)");
		// echo $this->db->last_query(); die;
		if($q_rate->num_rows() > 0){
			foreach($q_rate->result() as $rates){
				list($GROUP_ID, $DESCRIPTION) = $this->getEmployeeDepartmentDet($rates->aimsdept);
				if($rates->isall == 0){
					$GROUP_ID = ($rates->aimsdept == "all") ? "all" : $GROUP_ID; /*for all default setup*/
					$sched_list[$employeeid][$GROUP_ID]["aimsdept"] = $GROUP_ID;
					$sched_list[$employeeid][$GROUP_ID]["description"] = $DESCRIPTION;
					$sched_list[$employeeid][$GROUP_ID]["lechour"] = $rates->lechour;
					$sched_list[$employeeid][$GROUP_ID]["labhour"] = $rates->labhour;
					$sched_list[$employeeid][$GROUP_ID]["rlehour"] = $rates->rlehour;
					$sched_list[$employeeid][$GROUP_ID]["campus"] = $rates->campus;
				}

				$GROUP_ID = $DESCRIPTION = "";
			}
		}

		/*$q_substitute = $this->db->query("SELECT * FROM tblCourseCategory a LEFT JOIN payroll_emp_salary_perdept_history b ON a.`CODE` = b.`aimsdept` LEFT JOIN substitute_request c ON a.`CODE` = c.`aimsdept` WHERE b.employeeid = '$employeeid' GROUP BY a.CODE");
		
		if($q_substitute->num_rows() > 0){
			foreach($q_substitute->result() as $substitute){
				$sched_list[$employeeid][$substitute->CODE]["aimsdept"] = $substitute->CODE;
				$sched_list[$employeeid][$substitute->CODE]["description"] = $substitute->DESCRIPTION;
				$sched_list[$employeeid][$substitute->CODE]["lechour"] = $substitute->lechour;
				$sched_list[$employeeid][$substitute->CODE]["labhour"] = $substitute->labhour;
				$sched_list[$employeeid][$substitute->CODE]["rlehour"] = $substitute->rlehour;
			}
		}*/

		$q_sched = $this->db->query("SELECT employeeid, aimsdept, campus FROM employee_schedule_history WHERE employeeid = '$employeeid' AND aimsdept IS NOT NULL AND aimsdept != '' GROUP BY aimsdept  ");
		if($q_sched->num_rows() > 0){
			foreach($q_sched->result() as $scheds){
				list($lechour, $labhour, $rlehour) = $this->getEmployeePerdeptRate($employeeid, $scheds->aimsdept);
				list($GROUP_ID, $DESCRIPTION) = $this->getEmployeeDepartmentDet($scheds->aimsdept);

				$sched_list[$employeeid][$GROUP_ID]["aimsdept"] = $GROUP_ID;
				$sched_list[$employeeid][$GROUP_ID]["description"] = $DESCRIPTION;
				$sched_list[$employeeid][$GROUP_ID]["lechour"] = $lechour;
				$sched_list[$employeeid][$GROUP_ID]["labhour"] = $labhour;
				$sched_list[$employeeid][$GROUP_ID]["rlehour"] = $rlehour;
				$sched_list[$employeeid][$GROUP_ID]["campus"] = $scheds->campus;

				$GROUP_ID = $DESCRIPTION = $lechour = $labhour = $rlehour = "";
			}
		}
		unset($sched_list[$employeeid][""]);
		$sched_list[$employeeid][""]=array("aimsdept"=>"","description"=>"","lechour"=>"","labhour"=>"","rlehour"=>"", "campus"=>"");
		return $sched_list;
	}

	public function employeeScheduleList($employeeid){
		$sched_list = array();

		$q_sched = $this->db->query("SELECT employeeid, aimsdept, campus FROM employee_schedule_history WHERE employeeid = '$employeeid' AND aimsdept IS NOT NULL AND aimsdept != '' GROUP BY aimsdept  ");
		if($q_sched->num_rows() > 0){
			foreach($q_sched->result() as $scheds){
				$sched_list[$scheds->aimsdept]["lechour"] = "";
				$sched_list[$scheds->aimsdept]["labhour"] = "";
				$sched_list[$scheds->aimsdept]["rlehour"] = "";
				$sched_list[$scheds->aimsdept]["campus"] = $scheds->campus;
			}
		}
		return $sched_list;
	}

	public function employeePayrollList($employeeid){
		$count = 0;
		$sched_list = array();

		$q_sched = $this->db->query("SELECT employeeid, aimsdept, campus FROM employee_schedule_history WHERE employeeid = '$employeeid' AND aimsdept IS NOT NULL AND aimsdept != '' GROUP BY aimsdept  ");
		if($q_sched->num_rows() > 0){
			foreach($q_sched->result() as $scheds){
				list($GROUP_ID, $DESCRIPTION) = $this->getEmployeeDepartmentDet($scheds->aimsdept);
				$sched_list[$employeeid][$scheds->aimsdept]["aimsdept"] = $GROUP_ID;
				$sched_list[$employeeid][$scheds->aimsdept]["description"] = $DESCRIPTION;
				$sched_list[$employeeid][$scheds->aimsdept]["lechour"] = "";
				$sched_list[$employeeid][$scheds->aimsdept]["labhour"] = "";
				$sched_list[$employeeid][$scheds->aimsdept]["rlehour"] = "";
				$sched_list[$employeeid][$scheds->aimsdept]["campus"] = $scheds->campus;
			}
		}

		// $q_rate = $this->db->query("SELECT * FROM `payroll_emp_salary_perdept_history` where employeeid  = '$employeeid' AND aimsdept NOT IN (SELECT aimsdept FROM employee_schedule_history WHERE employeeid = '$employeeid'  and  aimsdept is not null) AND timestamp = (SELECT timestamp FROM payroll_emp_salary_perdept_history WHERE employeeid = '$employeeid' ORDER BY timestamp DESC LIMIT 1)");
		// echo $this->db->last_query(); die;
		$q_rate = $this->db->query("  SELECT *
									FROM `payroll_emp_salary_perdept_history` p1
									WHERE p1.employeeid = '$employeeid'");
		if($q_rate->num_rows() > 0){
			foreach($q_rate->result() as $rates){
				list($GROUP_ID, $DESCRIPTION) = $this->getEmployeeDepartmentDet($rates->aimsdept);
				if($rates->isall == 0){
					$GROUP_ID = ($rates->aimsdept == "all") ? "all" : $GROUP_ID; /*for all default setup*/
					$sched_list[$employeeid][$GROUP_ID]["aimsdept"] = $GROUP_ID;
					$sched_list[$employeeid][$GROUP_ID]["description"] = $DESCRIPTION;
					$sched_list[$employeeid][$GROUP_ID]["lechour"] = $rates->lechour;
					$sched_list[$employeeid][$GROUP_ID]["labhour"] = $rates->labhour;
					$sched_list[$employeeid][$GROUP_ID]["rlehour"] = $rates->rlehour;
					$sched_list[$employeeid][$GROUP_ID]["campus"] = $rates->campus;
				}

				$GROUP_ID = $DESCRIPTION = "";
			}
		}

		

		unset($sched_list[$employeeid][""]);
		$sched_list[$employeeid][""]=array("aimsdept"=>"","description"=>"","lechour"=>"","labhour"=>"","rlehour"=>"", "campus"=>"");

		return $sched_list;
	}

	public function getEmployeePerdeptRate($employeeid, $aimsdept){
		$lechour = $labhour = $rlehour = 0;
		$q_rate = $this->db->query("SELECT * FROM payroll_emp_salary_perdept_history WHERE employeeid = '$employeeid' AND aimsdept = '$aimsdept' AND timestamp = (SELECT timestamp FROM payroll_emp_salary_perdept_history WHERE employeeid = '$employeeid' ORDER BY timestamp DESC LIMIT 1) ORDER BY timestamp DESC LIMIT 1 ");
		if($q_rate->num_rows() > 0){
			$lechour = $q_rate->row()->lechour;
			$labhour = $q_rate->row()->labhour;
			$rlehour = $q_rate->row()->rlehour;
		}

		return array($lechour, $labhour, $rlehour);
	}

	public function getEmployeeDepartmentDet($aimsdept){
		$group_id = $description = "";
		$q_dept = $this->db->query("SELECT CODE, DESCRIPTION FROM tblCourseCategory WHERE CODE = BINARY '$aimsdept' ");
		if($q_dept->num_rows() > 0){
			$group_id = $q_dept->row()->CODE;
			$description = $q_dept->row()->DESCRIPTION;
		}

		return array($group_id, $description);
	}

	public function saveScheduleSched($base_id, $starttime, $endtime, $dateactive){
    	$is_exists = $this->db->query("SELECT * FROM cs_schedref WHERE base_id = '$base_id' ");
    	if($is_exists->num_rows() == 0) $this->db->query("INSERT INTO cs_schedref (base_id, starttime, endtime, dateactive) VALUES ('$base_id', '$starttime', '$endtime', '$dateactive') ");
    	else $this->db->query("UPDATE cs_schedref SET starttime = '$starttime', endtime = '$endtime', dateactive = '$dateactive' WHERE base_id = '$base_id'");
    }

    public function insertSetScheduleHistory($employeeid, $date_active, $schedid){
    	$user = $this->session->userdata("username");
    	$this->db->query("INSERT INTO set_employee_schedule_history (employeeid, schedid, editedby, effective_date) VALUES ('$employeeid', '$schedid', '$user', '$date_active')");
    }

    public function laodSetScheduleHistory($where_clause=""){
    	return $this->db->query("SELECT * FROM set_employee_schedule_history a INNER JOIN employee b ON a.employeeid = b.employeeid WHERE a.employeeid != '' $where_clause ");
    }

    public function cancelCSApp($id){
        $query = $this->db->query("UPDATE change_sched_app_emplist  SET status = 'CANCELLED' WHERE id='$id'");
        return true;
    }

    public function deleteApprovedSchedule($employeeid, $date_effective){
    	return $this->db->query("DELETE FROM employee_schedule_history WHERE employeeid = '$employeeid' AND dateactive = '$date_effective'");
    }

    public function removeSchedDetail($csid=""){
    	return $this->db->query("DELETE FROM change_sched_app_detail WHERE base_id = '$csid' ");
    }

    public function updateCSApplication($csid, $reason, $date_effective, $dfrom, $dto, $code_type, $app_type="", $eids_sub="", $sub_date=""){
    	if($code_type) $this->db->query("UPDATE change_sched_app_emplist SET `code_type`='$code_type', reason = '$reason' WHERE id='$csid'");
    	return $this->db->query("UPDATE change_sched_app SET reason = '$reason', dfrom = '$dfrom', dto = '$dto', date_effective = '$date_effective', type='$app_type', sub_empid='$eids_sub', sub_date='$sub_date' WHERE id = '$csid' ");
    }

    public function aimsdept_type($code){
    	$q_aimsdept = $this->db->query("SELECT type FROM tblCourseCategory WHERE CODE = '$code'");
    	if($q_aimsdept->num_rows() > 0) return $q_aimsdept->row()->type;
    	else return false;
    }

    public function aims_department_description($deptid){
    	$q_dept = $this->db->query("SELECT * FROM tblCourseCategory WHERE DEPT_ID = '$deptid' ");
    	if($q_dept->num_rows() > 0) return $q_dept->row()->DESCRIPTION;
    	else return false;
    }

    public function getEmployeeList($where_clause){
    	return $this->db->query("SELECT * FROM employee WHERE employeeid != '' $where_clause");
    }

    public function changeSchedulePendingCount(){
    	return $this->db->query("SELECT * FROM change_sched_app a INNER JOIN change_sched_app_emplist b ON a.id = b.base_id WHERE STATUS = 'PENDING' AND a.type = 'change'");
    }

	public function substitutePendingCount(){
    	return $this->db->query("SELECT * FROM change_sched_app a INNER JOIN change_sched_app_emplist b ON a.id = b.base_id WHERE STATUS = 'PENDING' AND a.type = 'substitute'");
    }

    public function getScheduleListByDayoff($wc=""){
    	return $this->db->query("SELECT * FROM code_schedule $wc");
    }

	public function loadEmployeeSched($campus="",$company="",$teachingtype="",$deptid="",$office="",$isactive="",$gender=""){
        $where_clause = "";
		if($teachingtype && $teachingtype != 'undefined'){
		    if($teachingtype != "trelated") $where_clause .= " AND a.teachingtype = '$teachingtype' ";
		    else $where_clause .= " AND a.teachingtype='teaching' AND a.trelated = '1'";
		}
        // if($deptid != "") $where_clause .= " AND a.`deptid`='$deptid' ";
        // if($office != "") $where_clause .= " AND a.`office`='$office' ";
        if($deptid != "") $where_clause .= " AND FIND_IN_SET ('$deptid', a.deptid)";
        if($office != "") $where_clause .= " AND FIND_IN_SET ('$office', a.office)"; 
		if($isactive != "") $where_clause .= " AND a.`isactive`='$isactive' ";
        if($campus && $campus != 'All') $where_clause .= " AND a.`campusid`='".$campus."' ";
        if($company && $company != 'All') $where_clause .= " AND a.`company_campus`='".$company."' ";
		if($gender != "") $where_clause .= " AND a.`gender`='$gender' ";
		return $this->db->query("SELECT a.`employeeid`,CONCAT(a.`lname`, ', ', a.`fname`, ' ', a.`mname`) AS fullname,a.campusid,a.office,a.deptid,a.positionid FROM employee as a WHERE employeeid != '' $where_clause ORDER BY a.lname ASC");
    }
    
    // public function loadEmployeeLoad($campus="",$company="",$teachingType="",$college="",$department="",$status="",$dfrom="",$dto="",$employeeids=""){
        
    // }

	function EmployeeScheduleHistory($employeeid='', $date_active=''){
		$where_clause = "";
		if($date_active) $where_clause = " AND DATE(dateactive) <= '$date_active' ";
		$res = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid='$employeeid' $where_clause ORDER BY idx ASC");
		return $res;
	}

	function dateActiveScheduleHistory($employeeid='', $date_active=''){
		$where_clause = "";
		if($date_active) $where_clause = " AND DATE(dateactive) <= '$date_active' ";
		$res = $this->db->query("SELECT dateactive FROM employee_schedule_history WHERE employeeid='$employeeid' $where_clause ORDER BY dateactive DESC, idx ASC, starttime ASC LIMIT 1");
		return $res;
	}

	public function getChangeSchedList($campus="",$company="",$teachingtype="",$deptid="",$office="",$isactive="",$gender="",$dfrom ="",$dto="",$employeeid="",$type="change"){
        $fixclause = "";
		$where_clause = "";
		if($teachingtype != "") $where_clause .= " AND e.`teachingtype`='$teachingtype' ";
        if($deptid != "") $where_clause .= " AND e.`deptid`='$deptid' ";
        if($office != "") $where_clause .= " AND e.`office`='$office' ";
		if($isactive != "") $where_clause .= " AND e.`isactive`='$isactive' ";
        if($campus && $campus != 'All') $where_clause .= " AND e.`campusid`='".$campus."' ";
        if($company && $company != 'All') $where_clause .= " AND e.`company_campus`='".$company."' ";
		if($gender != "") $where_clause .= " AND e.`gender`='$gender' ";
		if($employeeid != "") $fixclause .= "FIND_IN_SET(e.`employeeid`,'$employeeid')";
		else $fixclause .= "e.`employeeid`!=''";
		if($dfrom && $dto) $where_clause .=  "AND csa.date_effective BETWEEN '{$dfrom}' AND '{$dto}'";
		

		$sql = "SELECT csa.id AS base_id,csae.id AS csid, csae.`employeeid` AS employeeid, CONCAT(e.`lname`, ', ', e.`fname`, ' ', e.`mname`) AS fullname,e.campusid,e.office,e.deptid,e.positionid,csad.`timestamp`,csa.`date_effective`, 
    			csa.`isTemporary`, csa.`dfrom`, csa.`dto`, csae.`reason`, csae.`status`, type
					FROM change_sched_app csa
					LEFT JOIN change_sched_app_detail csad ON csad.`base_id` = csa.`id`
					LEFT JOIN change_sched_app_emplist csae ON csae.`base_id` = csa.`id`
					LEFT JOIN employee e ON e.`employeeid` = csae.`employeeid`
						WHERE $fixclause AND type = '$type' $where_clause GROUP BY csa.id ORDER BY e.lname ASC";

		
		return $this->db->query($sql);

		// return $this->db->query("SELECT a.`employeeid`,CONCAT(a.`lname`, ', ', a.`fname`, ' ', a.`mname`) AS fullname,a.campusid,a.office,a.deptid,a.positionid FROM employee as a WHERE employeeid != '' $where_clause ORDER BY a.lname ASC");
    }

	function getSchedDetailsArray($base_id='',$dayWeek=""){
		$data='';
		$where_clause = '';
		if($dayWeek != "") $where_clause = 'AND b.day_name ="'.$dayWeek.'"'; 

		$res = $this->db->query("SELECT *
								FROM change_sched_app_detail a 
								LEFT JOIN code_daysofweek b ON a.dayofweek = b.day_code WHERE base_id='$base_id'
								$where_clause");
		if($res->num_rows() > 0){
			$data = $res->result_array();
		}
		if($dayWeek != "") return $res->num_rows();
		else return $data;

	}

	function changeScheduleAllowedDate($datetoday){
		$q_cutoff = $this->db->query("SELECT CutoffTo FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.`ID` = b.`baseid` WHERE '$datetoday' BETWEEN CutoffFrom AND CutoffTo");
		if($q_cutoff->num_rows() > 0) return $q_cutoff->row()->CutoffTo;
		else return $datetoday;
	}

	public function saveEmployeeSchedule($sched_data){
		$sched_data["dateedit"] = $sched_data["dateactive"];
		$sched_data["editedby"] = $sched_data["changeby"];
		unset($sched_data["dateactive"]);
		unset($sched_data["changeby"]);
		return $this->db->insert("employee_schedule", $sched_data);
	}

	public function saveEmployeeScheduleHistory($sched_data){
		return $this->db->insert("employee_schedule_history", $sched_data);
	}

	public function deleteEmployeeSchedule($where_clause){
		return $this->db->query("DELETE FROM employee_schedule $where_clause");
	}

	public function employeeScheduleHistorySummary($employeeid){
		$res = $this->db->query("SELECT * FROM employee_schedule_history WHERE employeeid='$employeeid' GROUP BY dateactive, dayofweek ORDER BY dateactive DESC, idx ASC, starttime ASC");
		return $res;
	}
	
} //endoffile