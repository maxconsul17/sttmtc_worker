<?php 
/**
 * @author Angelica Arangco
 * @copyright 2017
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ob_application extends CI_Model {

	function checkLeaveBalance($employeeid='',$ltype='',$datefrom='',$dateto=''){
		$balance = $credit = $availed = 0;
		$haveCredits = true;
		$bal_q = $this->db->query("SELECT balance,credit,avail FROM employee_leave_credit WHERE employeeid='$employeeid' AND leavetype='$ltype' AND (('$datefrom' BETWEEN dfrom AND dto) OR ('$dateto' BETWEEN dfrom AND dto))");

		if($bal_q->num_rows() > 0){
			$balance = $bal_q->row(0)->balance;
			$credit = $bal_q->row(0)->credit;
			$availed = $bal_q->row(0)->avail;
		}else $haveCredits = false;
		return array($haveCredits,$balance,$credit,$availed);
	}

	function checkExistingLeaveApp($employeeid='',$status='',$datefrom='',$dateto='',$leave_id=''){
		$wc = "";
		if($leave_id) $wc = " AND a.id != '$leave_id'";
		$leave_q = $this->db->query("SELECT * FROM ob_app_emplist a INNER JOIN ob_app b ON a.`base_id`=b.`id`
						 WHERE a.employeeid='$employeeid' AND b.status='$status' 
						 AND ('$datefrom' BETWEEN datefrom AND dateto OR '$dateto' BETWEEN datefrom AND dateto) AND b.id NOT IN (SELECT base_id FROM ob_timerecord WHERE base_id AND STATUS = 'APPROVED' AND base_id = b.id) $wc");
		return $leave_q->num_rows();
	}

    /**
	 * Gets request details based on leave app id.
	 *
	 * @param string $leaveid (Default: "")
	 *
	 * @return stdClass Object
	 */
    function getAppSequencePerLeave($leaveid='', $dis_list=false){
    	if(!$dis_list){
	    	$res = $this->db->query("SELECT a.*,b.status as bypassed_status,b.`timestamp` as bypassed_date,b.`applied_by` FROM ob_app_emplist a INNER JOIN ob_app b ON a.`base_id`=b.`id` WHERE a.id='$leaveid'");
	    	return $res;
	    }else{
	    	$res = $this->db->query("SELECT b.*, c.* FROM ob_app_emplist a INNER JOIN ob_app b ON a.`base_id`=b.`id` INNER JOIN ob_wfh_disapproved c ON a.`base_id`=c.`base_id` WHERE a.id='$leaveid'");
	    	return $res;
	    }
    }

    function getAppSequence($type=""){
    	$res = $this->db->query("SELECT * FROM code_request_form WHERE code_request='$type'");
    	return $res;
    }


     /**
	 * Inserts new leave application in base table and gets last inserted id.
	 *
	 * @return int
	 */
    function insertBaseLeaveApp($app_info, $file_info){
    	
    	$id = "";
    	$res = $this->db->insert("ob_app", $app_info);
		if($res){ 
			$id = $this->db->insert_id();
			if($file_info["content"]){
				$file_info["base_id"] = $id;
				$dbname = $this->db->database_files;
				$this->db->insert($dbname.".ob_app_files", $file_info);
			}
		}
    	return $id;
    }

	function updateBaseLeaveAppFile($base_id, $file_info){
		if($file_info["content"]){
			$dbname = $this->db->database_files;
			$result = $this->db->insert($dbname.".ob_app_files", $file_info);
			if($result){
				$this->db->query("DELETE FROM $dbname.ob_app_files WHERE base_id = '$base_id' LIMIT 1");
			}
			return true;
		}
    	return false;
	}


    /**
	 * Inserts OB app in secondary table for list of employees.
	 *
	 * @return Array
	 */
    function insertLeaveAppEmpList($base_id, $user, $teachingType, $dstatus, $ddate, $isAdmin = false, $col_stat = '', $save_type="", $hrstatus="", $hrdate=""){
    	$empcount = $isread = 0;
    	$arr_data_failed = array();
		$isread = 1;

		# for ica-hyperion 21194
		# by justin (with e)
		$status = '';
		if($isAdmin && $col_stat){
			$status = ", 'APPROVED'";
		}
		# end for ica-hyperion 21194
		$ob_stat = "save";
		if($save_type == "draft") $ob_stat = "draft";
		$res = $this->db->query("
			INSERT INTO ob_app_emplist (save_stat, base_id, employeeid, teachingType, dstatus, ddate, hrstatus, hrdate, isread $col_stat) VALUES ('$ob_stat', '$base_id', '$user', '$teachingType','$dstatus', '$ddate','$hrstatus', '$hrdate', '$isread' $status)
		");

		if($res) $empcount++;
		else array_push($arr_data_failed, $employeeid);

		return array($empcount,$arr_data_failed);
    }

    function modifyLeaveDetails($base_id='',$datefrom='', $dateto='', $tfrom='', $tto='', $paid='',$nodays='',$isHalfDay='',$sched_affected='',$reason='',$destination='', $final_file='', $size='', $file_type='', $obtypes='', $save_type=''){
    	$res = $this->db->query("UPDATE ob_app SET 
													datefrom='$datefrom',
													dateto='$dateto',
													timefrom='$tfrom',
													timeto='$tto',
													paid='$paid',
    												nodays='$nodays',
    												isHalfDay='$isHalfDay',
    												sched_affected='$sched_affected',
    												reason='$reason',
    												destination='$destination',
    												obtypes='$obtypes'
    											WHERE id='$base_id'
    												");
		
		$file_info = array(
			"base_id" => $base_id,
			"content" => $final_file,
			"mime" => $file_type,
			"size" => $size
		);

		$this->ob_application->updateBaseLeaveAppFile($base_id, $file_info);

    	if($save_type && $obtypes=="2"){
    		$ob_stat = "save";
    		if($save_type == "draft") $ob_stat = "draft";
    		$this->db->query("UPDATE ob_app SET save_stat = '$ob_stat' WHERE id = '$base_id'");
    	}
    	if($res) return 1;
    	else return 0;
    }

	


    function insertOTAppEmpListHead($base_id, $arr_emplist, $dhead, $chead, $dseq, $cseq, $user){
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
	    				INSERT INTO ot_app_emplist (base_id, employeeid, dstatus, ddate, isread) VALUES ('$base_id', '$employeeid', '$dstatus', '$ddate' , '$isread')
	    			");
	    			if($res) $empcount++;
	    		}
    			
	    		if(in_array($chead, $arr_emplist)){
	    			if($cseq ==  1){
	    				$cstatus = "APPROVED";
		        		$cdate 	 = date_format( new DateTime('today') ,"Y-m-d");
	    			}
	    			$res = $this->db->query("
	    				INSERT INTO ot_app_emplist (base_id, employeeid, cstatus, cdate, isread) VALUES ('$base_id', '$employeeid', '$cstatus', '$cdate' , '$isread')
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
	    				INSERT INTO ot_app_emplist (base_id, employeeid, dstatus, ddate, cstatus, cdate, isread) VALUES ('$base_id', '$employeeid', '$dstatus', '$ddate' , '$cstatus', '$cdate' , '$isread')
	    			");
	    			if($res) $empcount++;
	    		}
			}
			$isread = 0;
		}
		return $empcount;
    }

    /**
	 * Gets list of employee OB applications.
	 *
	 * @return stdClass Object
	 */
    function getEmpOBHistory($employeeid="", $status="", $leaveid="", $isread='',$target='',$wfh=false){
    	$wC = "";
    	if($status && !$wfh)				 $wC .= " AND b.`status`='$status'";
    	if($leaveid)			 $wC .= " AND a.id='$leaveid'";
    	if($target)		 		 $wC .= " AND b.type='$target'";
    	if(!$wfh){
			$res = $this->db->query("SELECT a.employeeid, a.id AS app_id, b.*, REPLACE(CONCAT(c.LName, ', ', c.FName, ' ', c.MName), 'Ã‘', 'Ñ') AS fullname, b.status, a.base_id, 
				(SELECT COUNT(*) FROM ob_app_emplist sub_a WHERE sub_a.base_id = a.base_id AND sub_a.status = 'APPROVED' ) AS hasApproved
			FROM ob_app_emplist a
			INNER JOIN ob_app b ON a.`base_id` = b.`id`
			INNER JOIN employee c ON a.employeeid = c.employeeid
			WHERE a.employeeid = '$employeeid' 
			$wC 
			GROUP BY b.id")->result();
	        return $res;
	    }else{
	    	$res = $this->db->query("SELECT a.status, a.employeeid, a.id AS app_id, b.* ,REPLACE(CONCAT(c.LName,', ',c.FName,' ',c.MName), 'Ã‘', 'Ñ') AS fullname, 'DISAPPROVED' AS status, '1' AS dis_list, e.isread, a.base_id
        							FROM ob_app_emplist a
									INNER JOIN ob_app b ON a.`base_id`=b.`id`
									INNER JOIN ob_timerecord d ON d.`base_id`=b.`id`
									INNER JOIN employee c ON a.employeeid=c.employeeid
									INNER JOIN ob_wfh_disapproved e ON e.base_id=b.id
									WHERE a.employeeid='$employeeid'
									$wC GROUP BY b.id")->result();

	    	return $res;
	    }
	}

	/**
	 * Gets Leave details.
	 *
	 * @param string $app_id (Default: "")
	 */
	function getLeaveDetails($app_id=''){
		$data = array();
		$res = $this->db->query("SELECT a.id AS app_id, a.employeeid, REPLACE(CONCAT(c.LName,', ',c.FName,' ',c.MName), 'Ã‘', 'Ñ') AS fullname,e.description AS epos, b.status AS status ,d.description AS eoffc, f.description AS edept, c.teachingtype, a.*,b.*
				FROM ob_app_emplist a
				INNER JOIN ob_app b ON a.`base_id`=b.`id`
				INNER JOIN employee c ON a.`employeeid`=c.`employeeid`
				LEFT JOIN code_position e ON c.positionid = e.positionid
				LEFT JOIN code_office d ON c.office = d.code 
				LEFT JOIN code_department f ON c.deptid = f.code 
				WHERE b.id='$app_id'");
		// echo $this->db->last_query(); die;
		// echo "App ID received in the model: " . $app_id;
		if($res->num_rows() > 0){
			foreach ($res->result() as $obj) 
			{
				$data['base_id'] 		= $obj->base_id;
				$data['app_id'] 		= $obj->app_id;
				$data['employeeid'] 	= $obj->employeeid;
				$data['eoffc'] 	= $obj->eoffc;
				$data['othertype'] 		= $obj->type;
				$data['paid'] 			= $obj->paid;
				$data['date_applied'] 	= $obj->date_applied;
				$data['dfrom'] 			= $obj->datefrom;
				$data['dto'] 			= $obj->dateto;
				$data['timefrom'] 		= $obj->timefrom;
				$data['timeto'] 		= $obj->timeto;
				$data['nodays'] 		= $obj->nodays;
				$data['isHalfDay'] 		= $obj->isHalfDay;
				$data['sched_affected'] = $obj->sched_affected;
				$data['reason'] 		= $obj->reason;
				$data['destination'] 	= $obj->destination;
				$data['status'] 		= $obj->status;
				// $data['status'] 		= $obj->adminstatus;
				$data['fullname'] 		= $obj->fullname;
				$data['pos'] 			= $obj->epos;
				$data['rem'] 		= $obj->remarks;
				$data['edept']  		= $obj->edept;
				$data['ob_type']		= $obj->ob_type;
				$data['obtypes']		= $obj->obtypes;
				$data['save_stat']		= $obj->save_stat;
  			}
		}
		
		// echo '<pre>';print_r($data); die;
		return $data;
	}

	function getTimeRecord($base_id=''){
		$timerecord = $this->db->query("SELECT * FROM leave_app_ti_to WHERE aid='$base_id'");
		if($timerecord->num_rows() > 0) return $timerecord->result();
		else return '';
	}

	
	/**
	 * Gets list of leave applications for approver to manage.
	 * @param string $where_clause : containing the where clause to use in query
	 *
	 * @return query result
	 */
	function getLeaveAppListToManage($where_clause){
		return $this->db->query("SELECT a.*, CONCAT(lname, ' ', fname) as fullname, c.deptid, c.office, b.employeeid, b.status FROM ob_app a 
									INNER JOIN ob_app_emplist b ON a.id = b.`base_id` 
									INNER JOIN employee c ON c.employeeid = b.`employeeid` 
									WHERE a.id != '' $where_clause GROUP BY a.id");
	}

	# for ica-hyperion 21194
	# by justin (with e)
	# > isasave dito kapag direct Approved ni admin..
	function directApprovedByAdmin($base_id){
		$status = 'APPROVED';
		$leave_q = $this->db->query("SELECT a.employeeid,b.type,b.obtypes,b.datefrom,b.dateto,b.timefrom,b.timeto,b.nodays,b.paid, b.isHalfDay, b.sched_affected,b.id FROM ob_app_emplist a INNER JOIN ob_app b ON a.`base_id`=b.`id` WHERE b.id='$base_id' GROUP BY b.id");
		$ishalfday = 0;
		$sched_affected = array();
		$sched_affected_string = '';
		$datefrom = $dateto = $employeeid = $ltype = $paid = $nodays = $timefrom = $timeto = $obtypes = $id = '';


		if($leave_q->num_rows() > 0){
			$l_q = $leave_q->row(0);
		    $employeeid 			= $l_q->employeeid;
		    $ishalfday 				= $l_q->isHalfDay;
		    $sched_affected_string 	= $l_q->sched_affected;
		    $datefrom 				= $l_q->datefrom;
		    $dateto 				= $l_q->dateto;
		    $timefrom 				= $l_q->timefrom;
		    $timeto 				= $l_q->timeto;
		    $ltype 					= $l_q->type;
		    $nodays 				= $l_q->nodays;
		    $paid 					= $l_q->paid;
		    $obtypes 					= $l_q->obtypes;
		    $id 					= $l_q->id;
		}
		if($ishalfday && $sched_affected_string && $datefrom) $sched_affected = explode(',', $sched_affected_string);

		/*update bypassed application*/
		$username = $this->session->userdata("username");

		///< check for existing applications
		$exist_app = $this->checkExistingLeaveApp($employeeid,'APPROVED',$datefrom,$dateto,$base_id);
		if($exist_app) {return array('err_code'=>0,'msg'=>'Employee already have approved applications for this date.');}

		/*update ob_app_emplist table*/
		$this->db->where("base_id", $base_id);
    	$this->db->delete("ob_app_emplist");
		$approver_info = array(
			"base_id" => $base_id,
			"employeeid" => $employeeid,
			"approver_id" => $this->session->userdata("username"),
			"status" => "BYPASSED"
		);
    	$result = $this->db->insert("ob_app_emplist", $approver_info);
    	if($result){
			/*update ob_app status*/
			$this->db->where("id", $base_id);
			$this->db->set("status", "APPROVED");
			$this->db->update("ob_app");
		}
		

		$q_trecord = $this->db->query("SELECT * FROM ob_timerecord WHERE base_id = '$base_id' AND status = 'APPROVED'");
		if($q_trecord->num_rows() > 0){
			foreach($q_trecord->result() as $t_row){
				$date = $t_row->t_date;
				$r_timein = date("H:i:s", strtotime($t_row->timein));
				$r_timeout = date("H:i:s", strtotime($t_row->timeout));
				$t_timein = $date." ".$r_timein;
				$t_timeout = $date." ".$r_timeout;
				$this->db->query("INSERT INTO timesheet (userid, timein, timeout, otype) VALUES ('$employeeid', '$t_timein', '$t_timeout', '2')");
			}
		}
		
		$insert_q = $this->db->query("
						INSERT INTO ob_request (aid,employeeid,type,fromdate,todate,timefrom,timeto,ob_type,obtypes,paid,dateapplied,no_days,isHalfDay,sched_affected,remarks,status,dateapproved,approvedby)
						 (SELECT b.id , a.employeeid, b.type, b.datefrom, b.dateto, b.timefrom,b.timeto, b.ob_type, b.obtypes, b.paid, b.date_applied, b.nodays, b.isHalfDay, b.sched_affected, b.reason, '$status', CURRENT_DATE, '$username'
							FROM ob_app_emplist a
							INNER JOIN ob_app b ON a.`base_id`=b.`id`
							 WHERE b.id='$base_id' GROUP BY b.id);

					");

		// CONDITION FOR OB HALFDAY
		if($nodays == 0.5) $nodays = 1;
		$ndays = intval($nodays);

		if($insert_q){
			$datenow = date("Y-m-d");
			///< insert to timesheet
			if($ltype != "CORRECTION"){
				if($paid != 'NO' && $timefrom != "00:00:00" && $timeto != "00:00:00"){
					for ($i=1; $i <= $ndays ; $i++) { 
						if($i == 1){
							$tnt = $this->extensions->getEmployeeTeachingType($employeeid);
							if($tnt == "nonteaching"){
								$df = date('Y-m-d', strtotime($datefrom)). " ". date('H:i:s',strtotime($timefrom));
								$dt = date('Y-m-d', strtotime($datefrom)). " ". date('H:i:s',strtotime($timeto));
								// old
								// $this->db->query("INSERT INTO timesheet (`userid`,`timein`,`timeout`,otype,addedby,dateadded, ob_id) VALUES ('".$employeeid."','".date('Y-m-d H:i:s', strtotime($df))."','".date('Y-m-d H:i:s', strtotime($dt))."', '$obtypes', '$username', '$datenow', '$base_id')"); 
								$this->db->query("INSERT INTO timesheet (`userid`,`timein`,`timeout`,otype,addedby,dateadded) VALUES ('".$employeeid."','".date('Y-m-d H:i:s', strtotime($df))."','".date('Y-m-d H:i:s', strtotime($dt))."', '$obtypes', '$username', '$datenow')");
							}else{
								// $timeto = "15:50:00";
								$sched = $this->attcompute->displaySched($employeeid, $datefrom);
								if($sched->num_rows() > 0){
									foreach($sched->result() as $rsched){
										$starttime = $rsched->starttime;
										$endtime = $rsched->endtime;
										if(strtotime($timeto) >= strtotime($endtime)){
											$df = date('Y-m-d', strtotime($datefrom)). " ". date('H:i:s',strtotime($starttime));
											$dt = date('Y-m-d', strtotime($datefrom)). " ". date('H:i:s',strtotime($endtime));
											$this->db->query("INSERT INTO timesheet (`userid`,`timein`,`timeout`,otype,addedby,dateadded) VALUES ('".$employeeid."','".date('Y-m-d H:i:s', strtotime($df))."','".date('Y-m-d H:i:s', strtotime($dt))."', '$obtypes', '$username', '$datenow')");
										}
									}
								}
							}
						}else{
							$df = date('Y-m-d', strtotime($df . ' +1 day')). " ". date('H:i:s',strtotime($timefrom));
							$dt = date('Y-m-d', strtotime($dt . ' +1 day')). " ". date('H:i:s',strtotime($timeto));
							$this->db->query("INSERT INTO timesheet (`userid`,`timein`,`timeout`,otype,addedby,dateadded) VALUES ('".$employeeid."','".date('Y-m-d H:i:s', strtotime($df))."','".date('Y-m-d H:i:s', strtotime($dt))."', '$obtypes', '$username', '$datenow')");
							
						}
					
					}
				}
			}

			$this->db->query("UPDATE ob_app_emplist SET isread='0' WHERE id='$base_id'");
		if($insert_q) return array('err_code'=>1,'msg'=>"Successfully saved OB application.", "base_id" => $base_id);
		}else {return array('err_code'=>0,'msg'=>"Failed to save.");}

				
	}

	# > for modified tapos direct approved..
	function changeSeqForDirectApproved($id, $base_id){
		$dseq = $cseq = $hrseq = $cpseq = $fdseq = $boseq = $pseq = $upseq = $fdhead = $bohead = $phead = $uphead = "";

		# tatangalin lahat ng seq..
		$this->db->query("UPDATE ob_app SET dseq='{$dseq}', cseq='{$cseq}', hrseq='{$hrseq}', cpseq='{$cpseq}', fdseq='{$fdseq}', boseq='{$boseq}', pseq='{$pseq}', upseq='{$upseq}' WHERE id='{$base_id}'");

		# update to approved status.
		$this->db->query("UPDATE ob_app_emplist SET status='APPROVED' WHERE id='{$id}'");

	}
	# end for ica-hyperion 21194

	/**
	 * Saves leave status change made by approver.
	 *
	 * @return stdClass Object
	 */
	function saveLeaveStatusChange($user='', $app_id='', $status='',$remarks='', $timerecord='', $t_record=array()){
		$res = $prev_wC ='';
		$return = array('err_code'=>0,'msg'=>"Application status change. Error in config.");

		if($status == 'APPROVED'){

			if($t_record){
				foreach($t_record as $t_arr){
					if($t_arr){
						foreach($t_arr as $t_row){
							$t_date = $t_row["date"];
							$timein = isset($t_row["tfrom"]) ? $t_row["tfrom"] : "";
							$timeout = isset($t_row["tto"]) ? $t_row["tto"] : "";
							$activity = isset($t_row["activity"]) ? $t_row["activity"] : "";

							if(!$t_row["tid"]){
								if($timein && $timeout) $this->ob_application->saveWFHTimeRecord($app_id, $timein, $timeout, $t_date, $activity, false, "insert");
							}else{
								if($timein && $timeout) $this->ob_application->saveWFHTimeRecord($app_id, $timein, $timeout, $t_date, $activity, $t_row["tid"], "update");
							}
						}
					}
				}
			}

			$leave_q = $this->db->query("SELECT a.employeeid,b.type,b.obtypes,b.datefrom,b.dateto,b.timefrom,b.timeto,b.nodays,b.paid, b.isHalfDay, b.sched_affected, b.ob_type FROM ob_app_emplist a INNER JOIN ob_app b ON a.`base_id`=b.`id` WHERE b.id='$app_id'");
			$ishalfday = 0;
			$sched_affected = array();
			$sched_affected_string = '';
			$datefrom = $dateto = $employeeid = $ltype = $paid = $nodays = $timefrom = $timeto = $obtypes = '';


			if($leave_q->num_rows() > 0){
				$l_q = $leave_q->row(0);
			    $employeeid 			= $l_q->employeeid;
			    $ishalfday 				= $l_q->isHalfDay;
			    $sched_affected_string 	= $l_q->sched_affected;
			    $datefrom 				= $l_q->datefrom;
			    $dateto 				= $l_q->dateto;
			    $timefrom 				= $l_q->timefrom;
			    $timeto 				= $l_q->timeto;
			    $ltype 					= $l_q->type;
			    $nodays 				= $l_q->nodays;
			    $paid 					= $l_q->paid;
			    $ob_type 				= $l_q->ob_type;
			    $obtypes 				= $l_q->obtypes;
			}
			if($ishalfday && $sched_affected_string && $datefrom) $sched_affected = explode(',', $sched_affected_string);

			if($timerecord && $ob_type != "late" && $ob_type != "undertime" && $ob_type!="wfh"){
				$this->load->model("user");
				$this->load->model("facial");
				$name = $this->user->get_employee_fullname($employeeid);

				foreach (explode("|", $timerecord) as $time) {
					list($tid,$timein,$timeout) = explode("~u~", $time);
					if($timein != "(--:-- --)" && $timeout != "(--:-- --)"){
						$timein = str_replace(": ", " ", $timein);
						$timeout = str_replace(": ", " ", $timeout);
						$timein = date("Y-m-d H:i:s", strtotime($datefrom." ".$timein));
						$timeout = date("Y-m-d H:i:s", strtotime($datefrom." ".$timeout));

						$isNew = explode("add-", $tid);

						if(count($isNew) > 0 && strpos($tid, 'add-') !== false){
							# add new
							$this->db->query("INSERT INTO timesheet (userid, timein, timeout, otype) VALUES ('$employeeid', '$timein', '$timeout', '$ltype')");
						}else{
							# update
							list($tr, $timeid) = explode("-", $tid);
							$result = $this->db->query("SELECT * FROM timesheet WHERE timeid = '$timeid' ");
							if($result->num_rows()){
								$timesheet = $result->row();
								$this->facial->update_facial_log([$timesheet->timein, $timesheet->timeout], [$employeeid, $name, $timein, $timeout]);
								$this->db->query("UPDATE timesheet SET timein='$timein', timeout='$timeout', otype='$ltype' WHERE timeid='$timeid'");
							}
							else {
								$this->db->query("INSERT INTO timesheet (userid, timein, timeout, otype) VALUES ('$employeeid', '$timein', '$timeout', '$ltype')");
							}
						}
					}else{
						list($tr, $timeid) = explode("-", $tid);
						$this->db->query("DELETE FROM timesheet WHERE timeid='$tid'");
					}
				}

			}

			/*get leave_app id*/
			$q_ob = $this->db->query("SELECT base_id, employeeid FROM ob_app_emplist WHERE base_id='$app_id'");
			if($q_ob->num_rows() > 0){
				$ob_id = $q_ob->row()->base_id;
				$employeeid = $q_ob->row()->employeeid;
				$q_trecord = $this->db->query("SELECT * FROM ob_timerecord WHERE base_id = '$ob_id' AND status = 'APPROVED'");
				if($q_trecord->num_rows() > 0){
					foreach($q_trecord->result() as $t_row){
						$date = $t_row->t_date;
						$r_timein = date("H:i:s", strtotime($t_row->timein));
						$r_timeout = date("H:i:s", strtotime($t_row->timeout));
						$t_timein = $date." ".$r_timein;
						$t_timeout = $date." ".$r_timeout;
						$this->db->query("INSERT INTO timesheet (userid, timein, timeout, otype) VALUES ('$employeeid', '$t_timein', '$t_timeout', '2')");
					}
				}
			}

			$insert_q = $this->db->query("
			INSERT INTO ob_request (aid,employeeid,type,ob_type,obtypes,fromdate,todate,timefrom,timeto,paid,dateapplied,no_days,isHalfDay,sched_affected,remarks,status,dateapproved)
			 (SELECT b.id , a.employeeid, b.type, b.ob_type,b.obtypes, b.datefrom ,b.dateto, b.timefrom,b.timeto, b.paid, b.date_applied, b.nodays, b.isHalfDay, b.sched_affected, b.reason, '$status', CURRENT_DATE
				FROM ob_app_emplist a
				INNER JOIN ob_app b ON a.`base_id`=b.`id`
				 WHERE b.id='$app_id' GROUP BY b.id);

				");

			$ndays = intval($nodays);
			if($insert_q){
				if($ltype != "CORRECTION"){
					///< insert to timesheet
					if( $timefrom != "00:00:00" && $timeto != "00:00:00"){
						if($paid != 'NO'){
							$tnt = $this->extensions->getEmployeeTeachingType($employeeid);
							if($tnt == "nonteaching"){
								$df = $dt = "";
								$df = date('Y-m-d', strtotime($datefrom)). " ". date('H:i:s',strtotime($timefrom));
								$dt = date('Y-m-d', strtotime($datefrom)). " ". date('H:i:s',strtotime($timeto));
								$this->db->query("INSERT INTO timesheet (`userid`,`timein`,`timeout`) VALUES ('".$employeeid."','".date('Y-m-d H:i:s', strtotime($df))."','".date('Y-m-d H:i:s', strtotime($dt))."')");
							}else{
								$sched = $this->attcompute->displaySched($employeeid, $datefrom);
								if($sched->num_rows() > 0){
									foreach($sched->result() as $rsched){
										$starttime = $rsched->starttime;
										$endtime = $rsched->endtime;

										$df = $dt = "";
										$df = date('Y-m-d', strtotime($datefrom)). " ". date('H:i:s',strtotime($starttime));
										$dt = date('Y-m-d', strtotime($datefrom)). " ". date('H:i:s',strtotime($endtime));
										$this->db->query("INSERT INTO timesheet (`userid`,`timein`,`timeout`) VALUES ('".$employeeid."','".date('Y-m-d H:i:s', strtotime($df))."','".date('Y-m-d H:i:s', strtotime($dt))."')");
									}
								}
							}
						}
					}
				}

				$this->db->query("UPDATE ob_app SET status = '$status', isread = '0' WHERE id='$app_id'");
				
			if($insert_q){
					$subcampusid = $this->db->query("SELECT subcampusid FROM employee WHERE employeeid = '$employeeid'")->row()->subcampusid ?? null;
				
					if($subcampusid){
						$this->load->model("api");
						$queries = $this->prepareOfficeBusinessQueries($app_id, [$timerecord, $t_record]);
						$other_sites = explode(",", $subcampusid);
						
						$this->api->sendOnlineApplicationToOtherSite($other_sites, $queries);
					}

					return 1;
				}
			}else {
				$this->db->query("UPDATE ob_app SET status = '$status', isread = '0' WHERE id='$app_id'");
				return 2;
			}

		}else{
			// just return success if status is cancelled or disapproved
			return 1;
		}

		return $return;
	}

	function prepareOfficeBusinessQueries($ob_id, $data){
		$ob_app = $this->db->query("SELECT * FROM ob_app WHERE id = '$ob_id' LIMIT 1")->row_array();
		$emplists = $this->db->query("SELECT * FROM ob_app_emplist WHERE base_id = '$ob_id'")->result_array();
		$ob_request = $this->db->query("SELECT * FROM ob_request WHERE aid = '$ob_id' LIMIT 1")->row_array();
		$employee_id = $ob_request['employeeid'];

		$emplists = array_reduce($emplists, function($carry, $item){
			$carry[] = ["ob_app_emplist", $item];
			return $carry;
		}, []);

		$queries = [
			"parent_query" => ["ob_app", $ob_app],
			"sub_queries" => array_merge($emplists, [['ob_request', $ob_request]]),
			"actions" => "execute_office_business_queries",
			"employee_id" => $employee_id,
			"sub_data" => $data
		];

		return $queries;
	}

	/**
	 * Checks if the given OB request dates have already been processed in payroll.
	 *
	 * @param string $datefrom The start date of the OB request (e.g., "2025-01-25").
	 * @param string $dateto   The end date of the OB request (e.g., "2025-02-05").
	 * @return mixed Returns the number of payroll records found if the dates are processed;
	 *               returns an empty array if not.
	 */
	function PayrollDatesAlreadyProcessed($employeeid, $datefrom, $dateto){
		$query =  $this->db->query("SELECT * FROM payroll_computed_table 
									WHERE employeeid = '$employeeid' 
									AND `status` = 'PROCESSED' 
									AND ('$datefrom' BETWEEN cutoffstart AND cutoffend)  
									AND ('$dateto' BETWEEN cutoffstart AND cutoffend)");
		return ($query->num_rows() > 0) ? $query->num_rows() : [];
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
	 * getting sequence for approved ob application.
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
	 * Deletes an Leave app when it's still not approved/disapproved by approving head.
	 *
	 * @param int $id overtime app id
	 *
	 * @return string
	 */
	function deleteLeaveApp($id){
        $return = "";
        $query = $this->db->query("SELECT status FROM ob_app_emplist WHERE base_id='$id' AND status = 'APPROVED' ");
        if($query->num_rows() > 0){
            $return = "Failed to delete!. The request is already ".$query->row()->status;
        }else{
			$ob_data = $this->getLeaveDetails($id);
            $query = $this->db->query("DELETE FROM ob_app WHERE id='$id'");
			if($query) {
            	$return = "Successfully Deleted!.";
            	$employeeid = $ob_data['employeeid'];
				$dfrom = $ob_data['dfrom'];
				$dto = $ob_data['dto'];
				$this->employeeAttendance->updateDTR($employeeid, $dfrom, $dto);
            }
            else{
            	$return = "Failed to delete.";
            }
        }
        return $return;
    }


	function getEmpSchedMinMaxTimePerday($employeeid=''){
		$res = $this->db->query(" SELECT MIN(starttime) as start,MAX(endtime) as end,idx,dayofweek 
		                             FROM employee_schedule_history 
		                             WHERE employeeid='$employeeid'
		                             GROUP BY idx");
		return $res;
	}

	function getEmpCurrentSchedMinMaxTimePerday($employeeid=''){
		$res = $this->db->query(" SELECT MIN(starttime) as start,MAX(endtime) as end,idx,dayofweek 
		                             FROM employee_schedule
		                             WHERE employeeid='$employeeid'
		                             GROUP BY idx");
		return $res;
	}

	function getObAppHistory($dfrom, $dto, $tnt = "",$deptid = "",$campusid = ""){
		$where_clause = "";
		if($tnt) $where_clause = " AND b.teachingtype = '$tnt' ";
		if($deptid) $where_clause = " AND b.deptid = '$deptid' ";
		if($campusid) $where_clause = " AND b.campusid = '$campusid' ";
		$res = $this->db->query("SELECT a.*, CONCAT(b.lname, ',', b.fname, ',', b.mname) AS fullname, deptid, d.description, b.teachingtype
								 FROM ob_request a 
								 INNER JOIN employee b ON b.`employeeid` = a.`employeeid` 
								 INNER JOIN `code_department` c ON c.`code` = b.`deptid`
								 INNER JOIN `code_position` d ON d.`positionid` = b.`positionid`
								 WHERE fromdate BETWEEN '$dfrom' AND '$dto' OR todate BETWEEN '$dfrom' AND '$dto' $where_clause;");
		if($res->num_rows() > 0) return $res->result();
		else return FALSE;
	}

	function checkIfHasExistingOBRequest($aid, $employeeid){
		$query = $this->db->query("SELECT * FROM ob_request WHERE aid = '$aid' AND employeeid = '$employeeid' ");
		if($query->num_rows() > 0) return true;
		else return false;
	}

    public function getObTodayEmployees($datenow){
    	$q_ob = $this->db->query("SELECT DISTINCT employeeid FROM ob_request WHERE '$datenow' BETWEEN fromdate AND todate ");
    	if($q_ob->num_rows() > 0) return $q_ob->result_array();
    	else return false;
    }

    public function getOBAttachments($id){
    	$dbname = $this->db->database_files;
    	return $this->db->query("SELECT content, mime FROM $dbname.ob_app_files WHERE base_id = '$id'");
    }

    public function saveOBSched($base_id, $starttime, $endtime, $dateactive){
    	$is_exists = $this->db->query("SELECT * FROM ob_schedref WHERE base_id = '$base_id' ");
    	if($is_exists->num_rows() == 0) $this->db->query("INSERT INTO ob_schedref (base_id, starttime, endtime, dateactive) VALUES ('$base_id', '$starttime', '$endtime', '$dateactive') ");
    	else $this->db->query("UPDATE ob_schedref SET starttime = '$starttime', endtime = '$endtime', dateactive = '$dateactive' WHERE base_id = '$base_id'");
    }

    public function checkDateApplied($date, $employee){
    	return $this->db->query("SELECT * FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id WHERE a.datefrom = '$date' AND b.employeeid = '$employee' AND a.type LIKE '%CORRECTION%'")->num_rows();
    }

    public function getEmployeeSchedule($employeeid, $idx, $datefrom){
    	return $this->db->query("SELECT * FROM employee_schedule_history WHERE idx IN ($idx) AND employeeid = '$employeeid' AND DATE(dateactive) <= DATE(DATE_SUB('$datefrom',INTERVAL 1 DAY)) GROUP BY starttime");
    }

    public function checkEmployeeSchedule($employeeid, $idx, $datefrom, $starttime, $endtime){
    	return $this->db->query("SELECT * FROM employee_schedule_history WHERE idx IN ($idx) AND employeeid = '$employeeid' AND DATE(dateactive) <= DATE(DATE_SUB('$datefrom',INTERVAL 1 DAY)) AND starttime = '$starttime' AND endtime = '$endtime'");
    }

    public function getEmployeeTimeRecord($employeeid, $date, $is_add){
    	$wc = "";
    	if($is_add) $wc = " AND (otype = '2' OR otype = '')";
    	return $this->db->query("SELECT * FROM timesheet WHERE userid = '$employeeid' AND DATE(timein) = '$date' $wc ");
    }

    public function saveWFHTimeRecord($base_id, $timein, $timeout, $t_date, $activity, $tid="", $func=""){
    	if($func == "insert") $this->db->query("INSERT INTO ob_timerecord (base_id, t_date, timein, timeout, activity) VALUES ('$base_id', '$t_date', '$timein', '$timeout', ".$this->db->escape($activity).")");
    	else $this->db->query("UPDATE ob_timerecord SET t_date = '$t_date', timein = '$timein', timeout = '$timeout', activity = ".$this->db->escape($activity)." WHERE id = '$tid'");
    }

    public function deleteWFHTimeRecord($base_id, $t_date, $tid){
    	$this->db->query("DELETE FROM ob_timerecord WHERE t_date = '$t_date' AND id = '$tid' AND base_id = '$base_id'");
    }

    public function WFHTimeRecord($date, $id, $dis_list=false){
    	$wc = "";
    	if($dis_list) $wc = " AND status = 'DISAPPROVED'";
    	else $wc = " AND status = 'APPROVED'";
    	return $this->db->query("SELECT * FROM ob_timerecord WHERE base_id = '$id' AND t_date = '$date' $wc ");
    }

    public function isWFHTimeRecordDisApproved($date, $id){
    	return $this->db->query("SELECT * FROM ob_timerecord WHERE base_id = '$id' AND t_date = '$date' AND status = 'DISAPPROVED' ")->num_rows();
    }

    public function WFHTimeRecordDisapproved($date, $employeeid, $cutofffrom, $cutoffto){
    	return $this->db->query("SELECT * FROM ob_timerecord a INNER JOIN ob_app_emplist b ON a.base_id = b.base_id WHERE  t_date = '$date' AND a.status = 'DISAPPROVED' AND t_date BETWEEN '$cutofffrom' AND '$cutoffto' AND employeeid = '$employeeid' ");
    }

    public function hasFiledOB($employeeid, $datefrom, $dateto, $ishalfday="", $timefrom="", $timeto=""){
    	$where_clause = "";
    	$sched_affected = $timefrom."|".$timeto;
    	if($datefrom == $dateto){
	    	if($ishalfday == "true"){
	    		$dateto = $datefrom; 
	    		$where_clause = " AND( isHalfDay = '1' AND sched_affected = '$sched_affected') ";
	    	}
	    	return $this->db->query("SELECT * FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id WHERE employeeid = '$employeeid' AND obtypes!='2' AND ('$datefrom' BETWEEN datefrom AND dateto OR '$dateto' BETWEEN datefrom AND dateto) AND (a.status != 'DISAPPROVED' AND a.status != 'CANCELLED') $where_clause ")->num_rows();
	    }else{
	    	if($ishalfday == "true"){
	    		$dateto = $datefrom; 
	    		$where_clause = " AND isHalfDay = '1' AND sched_affected = '$sched_affected' OR(  employeeid = '$employeeid' AND datefrom = '$datefrom' AND dateto = '$dateto') ";
	    	}
	    	return $this->db->query("SELECT * FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id WHERE employeeid = '$employeeid' AND obtypes!='2' AND ('$datefrom' BETWEEN datefrom AND dateto OR '$dateto' BETWEEN datefrom AND dateto) $where_clause ")->num_rows();
	    }
    }

    public function hasFiledOBWFH($employeeid, $datefrom, $dateto){
    	return $this->db->query("SELECT * FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id INNER JOIN ob_timerecord c ON a.id = c.base_id  WHERE employeeid = '$employeeid' AND t_date BETWEEN '$datefrom' AND '$dateto' AND c.status != 'DISAPPROVED' AND c.status != 'CANCELLED' AND b.status != 'DISAPPROVED' AND b.status != 'CANCELLED' ")->num_rows();
    }

    public function isOBApproved($base_id){
    	return $this->db->query("SELECT * FROM ob_app_emplist WHERE base_id = '$base_id' AND status = 'APPROVED'")->num_rows();
    }

    public function disApprovedWFHTime($tid, $timein, $timeout, $remarks, $base_id, $colhead, $colstatus, $coldate){
    	$datenow = date("Y-m-d");
    	$username = $this->session->userdata("username");
    	$this->db->query("UPDATE ob_timerecord SET status = 'DISAPPROVED', remarks = ".$this->db->escape($remarks)." WHERE id = '$tid' ");
    	if($this->db->query("SELECT * FROM ob_wfh_disapproved WHERE base_id = '$base_id' ")->num_rows() == 0){
    		$this->db->query("INSERT INTO ob_wfh_disapproved (base_id, last_update, $colstatus,$coldate) VALUES ('$base_id', '$username','DISAPPROVED','$datenow')");
    	}else{
    		$this->db->query("UPDATE ob_wfh_disapproved SET $colstatus = 'DISAPPROVED', $coldate='$datenow' WHERE base_id = '$base_id'");
    	}
    }

    public function cancelOBApp($id){
        $query = $this->db->query("UPDATE ob_app SET status = 'CANCELLED' WHERE id='$id'");
        $query = $this->db->query("UPDATE ob_request  SET status = 'CANCELLED' WHERE aid='$id'");
		$query = $this->db->query("UPDATE ob_app_emplist SET status = 'CANCELLED' WHERE base_id='$id'");
        return true;
    }

	public function executeCancelToOtherSite($app_id, $employeeid){
		$this->load->model("api");

		$subcampusid = $this->db->query("SELECT subcampusid FROM employee WHERE employeeid = '$employeeid'")->row()->subcampusid ?? null;
				
		if($subcampusid){
			$this->load->model("api");
			$ob_app = $this->db->query("SELECT 
											applied_by, 
											TYPE, 
											ob_type, 
											paid, 
											datefrom, 
											dateto, 
											timefrom, 
											timeto, 
											nodays, 
											isHalfDay, 
											sched_affected, 
											destination, 
											reason, 
											STATUS, 
											date_applied, 
											isread, 
											obtypes, 
											save_stat 
										FROM ob_app
										WHERE id = '$app_id'")->row_array();
			$other_sites = explode(",", $subcampusid);
			
			$this->api->cancelOnlineApplicationToOtherSite($other_sites, ["ob_application", 'cancelOBApp', 'ob_app', $ob_app]);
		}
	}

    public function deleteOBInsertedLogs($employeeid, $timein, $timeout){
    	if($timein && $employeeid && $timeout){
    		/*insert to history*/
    		$this->saveTimesheetHistory($employeeid, $timein, $timeout);

    		return $this->db->query("DELETE FROM timesheet WHERE userid = '$employeeid' AND timein = '$timein' AND timeout = '$timeout'");
    	}
    }

    public function correctionTimeHistory($id){
    	return $this->db->query("SELECT * FROM leave_app_ti_to WHERE aid = '$id' ");
    }

    public function updateTimesheetLogs($timeid, $act_in, $act_out, $req_in, $req_out, $employeeid){
    	/*insert to history*/
    	$this->saveTimesheetHistory($employeeid, $req_in, $req_out);

    	$this->db->query("UPDATE timesheet SET timein = '$act_in', timeout = '$act_out' WHERE timein = '$req_in' AND timeout = '$req_out' AND userid = '$employeeid'");
    }

    public function deleteTimesheetLogs($timeid, $act_in, $act_out, $employeeid){
    	/*insert to history*/
    	$this->saveTimesheetHistory($employeeid, $act_in, $act_out);

    	/*delete timesheet logs*/
    	$this->db->query("DELETE FROM timesheet WHERE userid = '$employeeid' AND timein = '$act_in' AND timeout = '$act_out'");

    }

    public function saveTimesheetHistory($employeeid, $timein, $timeout){
    	/*insert to history*/
    	$this->db->query("
    		INSERT INTO timesheet_history (timeid, userid, timein, timeout, localtimein, localtimeout, mac_add_in, mac_add_out, addedby, dateadded, type, otype, username, h_type) 
    		SELECT timeid, userid, timein, timeout, localtimein, localtimeout, mac_add_in, mac_add_out, addedby, dateadded, type, otype, username, 'DELETED' FROM timesheet WHERE userid = '$employeeid' AND timein = '$timein' AND timeout = '$timeout' ");
    }

    public function applicationSaveType($base_id){
    	$q_ob = $this->db->query("SELECT * FROM ob_app_emplist b WHERE base_id = '$base_id' ");
    	if($q_ob->num_rows() > 0) return $q_ob->row()->save_stat;
    	else return false;
    }

    public function getWFHTimeRecord($id){
    	return $this->db->query("SELECT * FROM ob_timerecord WHERE base_id = '$id' AND status = 'APPROVED' ");
    }

    public function isApplicationDraft($id){
    	return $this->db->query("SELECT * FROM ob_app WHERE id = '$id' AND save_stat = 'draft'")->num_rows();
    }

    public function updateOBTime($base_id, $timefrom, $timeto){
    	$this->db->query("UPDATE ob_app SET timefrom = '$timefrom', timeto = '$timeto' WHERE id = '$base_id'");
    }

    public function OBPendingCount(){
    	return $this->db->query("SELECT * FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id WHERE status = 'PENDING' AND type = 'DIRECT'");
    }

    public function correctionPendingCount(){
    	return $this->db->query("SELECT * FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id WHERE status = 'PENDING' AND type = 'CORRECTION'");
    }

    public function employeePendingCorrection($employeeid, $datefrom, $dateto){
    	return $this->db->query("SELECT * FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id WHERE status = 'PENDING' AND type = 'CORRECTION' AND employeeid = '$employeeid' AND (datefrom BETWEEN '$datefrom' AND '$dateto' || dateto BETWEEN '$datefrom' AND '$dateto') ");
    }

	function seminarOBinsertUpdate($base_id='',$details){
		if($base_id){
			$this->db->where("base_id", $base_id);
			$this->db->set($details);
			$result = $this->db->update("ob_seminar_details");
		}else{
			$result = $this->db->insert("ob_seminar_details", $details);
		}
		return $result;
	}

} //endoffile