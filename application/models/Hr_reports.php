<?php 
/**
 * @author Max Consul
 * @copyright 2019
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Hr_reports extends CI_Model {

    public function getEmployeeDetails($employeeid, $column){
    	$query = $this->db->query("SELECT * FROM employee_attendance_detailed WHERE employeeid = '$employeeid'");
    	if($query->num_rows() > 0) return $query->row()->$column;
    	else return false;    	
    }

    public function getLeaveReportSummary($types,$datefrom,$dateto){
    	$query = $this->db->query("SELECT a.`leavetype`, COUNT(*) AS TOTAL, a.`other`, b.`description` FROM leave_request a INNER JOIN code_request_form b ON a.`leavetype` = b.`code_request` OR a.`other` = b.`code_request` AND a.`fromdate` BETWEEN '$datefrom' AND '$dateto' OR a.`todate` BETWEEN '$datefrom' AND '$dateto' WHERE b.`code_request` IN ('$types') AND a.`status` = 'APPROVED' GROUP BY a.`leavetype`,other");
    	return $query->result();		
    }

	public function getAttConfirmed_summary($teachingtype='',$cutoffstart='',$cutoffend='',$payroll_start='',$employeeid='',$campus='',$is_trelated=false,$office="",$deptid="",$status="",$company_campus=""){
		$data = array();

		if($teachingtype == 'teaching'){
			$data = $this->getAttConfirmed_summary_T($teachingtype,$cutoffstart,$cutoffend,$payroll_start,$employeeid,$campus,$office,$deptid,$status,$company_campus);
		}elseif($teachingtype == 'nonteaching'){
			$data = $this->getAttConfirmed_summary_NT($teachingtype,$cutoffstart,$cutoffend,$payroll_start,$employeeid,$campus,$is_trelated);
		}else{
			$data = $this->getAttConfirmed_summary_T($teachingtype,$cutoffstart,$cutoffend,$payroll_start,$employeeid,$campus,$office,$deptid,$status,$company_campus);
		}
		return $data;
	}

	//LNDRSNTS
	public function getCutoffByPayrollCutoff(int $id): array
	{
		$cutoffFrom = '';
		$cutoffTo = '';
	
		$query = $this->db->query("
			SELECT c.CutoffFrom, c.CutoffTo 
			FROM cutoff c
			LEFT JOIN payroll_cutoff_config pc ON pc.baseid = c.id
			WHERE pc.baseid = ?
		", [$id]);
	
		if ($query->num_rows() > 0) {
			$result = $query->row(); 
			$cutoffFrom = $result->CutoffFrom ?? '';
			$cutoffTo = $result->CutoffTo ?? '';
		}
	
		return [
			'cutoffFrom' => $cutoffFrom,
			'cutoffTo' => $cutoffTo,
		];
	}
	

	public function getAttConfirmed_summary_T($teachingtype='',$cutoffstart='',$cutoffend='',$payroll_start='',$employeeid='',$campus='',$office="",$deptid="",$isactive="",$company_campus=""){
		$data = array();
		$where_clause = "";
		if($employeeid) $where_clause = " AND a.employeeid = '$employeeid' ";
		if($company_campus && $company_campus!="all") $where_clause .= " AND a.company_campus='$company_campus'";
        if($office && $office!="all") $where_clause .= " AND a.office='$office'";
        if($isactive != "all"){
          if($isactive=="1"){
            $where_clause .= " AND isactive ='1'";
          }
          if($isactive=="0"){
            $where_clause .= " AND isactive ='0'";
          }
        }
        if($deptid) $where_clause .= " AND a.deptid='$deptid'";
		$usercampus = $this->extras->getCampusUser();
	      if($campus && $campus != "All"){
	        $where_clause .= " AND a.campusid = '$campus'";
	      }else{
	          if($usercampus) $where_clause .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
	      }
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

		if($teachingtype){
			if($teachingtype != "trelated") $utwc .= " AND a.teachingtype = '$teachingtype' ";
			else $utwc .= " AND a.teachingtype='teaching' AND a.trelated = '1'";

		}

        $where_clause .= $utwc;
		$att_q = $this->db->query("
									SELECT c.id AS base_id, a.employeeid as qEmpId,deptid as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, b.description AS qDepartment,DATE(c.`timestamp`) as dateconfirmed, substitute, c.*
									                FROM employee a
									                LEFT JOIN code_department b ON a.deptid = b.code 
									                INNER JOIN attendance_confirmed c ON a.employeeid = c.employeeid
									                WHERE (a.dateresigned2 < a.dateemployed 
										            		OR a.dateresigned2 = '0000-00-00' 
										            		OR a.dateresigned2 = '1970-01-01' 
										            		OR a.dateresigned2 IS NULL 
										            		OR a.dateresigned2 > '$payroll_start')  
									                	AND c.cutoffstart = '$cutoffstart' AND cutoffend = '$cutoffend'  $where_clause GROUP BY a.employeeid ORDER BY office, qFullname

						");

		if($att_q->num_rows() > 0){
			foreach ($att_q->result() as $key => $row) {

				$data[$row->qDeptId][$row->qEmpId] = array(
					'fullname'=>$row->qFullname,
					'eleave'=>$row->eleave,
					'vleave'=>$row->vleave,
					'sleave'=>$row->sleave,
					'oleave'=>$row->oleave,
					'absent'=>$row->absent,
					'day_absent'=>$row->day_absent,
					'hold_status_change'=>$row->hold_status_change,
					'dateconfirmed'=>$row->dateconfirmed,
					'date_processed'=>$row->date_processed,
					'substitute'=>$row->substitute,

					'total_wlec' => $row->workhours_lec,
					'total_wlab' => $row->workhours_lab,
					'total_wadmin' => $row->workhours_admin,
					'total_woverload' => $row->workhours_rle,

					'total_late_lec' => $row->latelec,
					'total_late_lab' => $row->latelab,
					'total_late_admin' => $row->lateadmin,
					'total_late_overload' => $row->laterle,
					
					'total_absent_lec' => $row->deduclec,
					'total_absent_lab' => $row->deduclab,
					'total_absent_admin' => $row->deducadmin,
					'total_absent_overload' => $row->deducrle,
			
					);


				$perdept_arr = array();
				$perdept_q = $this->db->query("SELECT work_hours, late_hours, deduc_hours, `type`, aimsdept FROM workhours_perdept WHERE base_id='{$row->base_id}'");

				foreach ($perdept_q->result() as $key_dept => $row_dept) {
					$perdept_arr[$row_dept->aimsdept][$row_dept->type] = array('work_hours'=>$row_dept->work_hours,'late_hours'=>$row_dept->late_hours,'deduc_hours'=>$row_dept->deduc_hours, 'aimsdept' => $row_dept->aimsdept);
				}

				$data[$row->qDeptId][$row->qEmpId]["perdept_arr"] = $this->detailedHolidayPay($perdept_arr, $cutoffstart, $cutoffend, $row->qEmpId);
				$data[$row->qDeptId][$row->qEmpId]["perdept_arr"] = $this->substituteDetailed($data[$row->qDeptId][$row->qEmpId]["perdept_arr"], $row->base_id);
			}
		}
		// echo "<pre>"; print_r($data); die;
		return $data;
	}

	public function getAttConfirmed_summary_NT($teachingtype='',$cutoffstart='',$cutoffend='',$payroll_start='',$employeeid='',$campus='',$is_trelated=false){
		$data = array();
		$where_clause = "";
		if($employeeid) $where_clause .= " AND a.employeeid = '$employeeid' ";
		if($is_trelated) $where_clause .= " AND a.trelated = '1' ";
		$usercampus = $this->extras->getCampusUser();
	      if($campus && $campus != "All"){
	        $where_clause .= " AND a.campusid = '$campus'";
	      }else{
	          if($usercampus) $where_clause .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
	      }
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
        $where_clause .= $utwc;
		$att_q = $this->db->query("
									SELECT c.id AS base_id, a.employeeid as qEmpId,office as qDeptId, CONCAT(lname,', ',fname,' ',mname) AS qFullname, b.description AS qDepartment,DATE(c.`timestamp`) as dateconfirmed, c.*
									                FROM employee a
									                INNER JOIN code_office b ON a.office = b.code 
									                INNER JOIN attendance_confirmed_nt c ON a.employeeid = c.employeeid
									                WHERE (a.dateresigned2 < a.dateemployed 
										            		OR a.dateresigned2 = '0000-00-00' 
										            		OR a.dateresigned2 = '1970-01-01' 
										            		OR a.dateresigned2 IS NULL 
										            		OR a.dateresigned2 > '$payroll_start')  
									                	AND c.cutoffstart = '$cutoffstart' AND cutoffend = '$cutoffend' AND a.teachingtype='$teachingtype' $where_clause GROUP BY a.employeeid ORDER BY office, qFullname

						");

		if($att_q->num_rows() > 0){
			foreach ($att_q->result() as $key => $row) {

				$data[$row->qDeptId][$row->qEmpId] = array('fullname'=>$row->qFullname,'oleave'=>$row->oleave,'vleave'=>$row->vleave,'sleave'=>$row->sleave,'otrest'=>$row->otrest,'absent'=>$row->absent,'otreg'=>$row->otreg,'otsat'=>$row->otsat,'otsun'=>$row->otsun,'othol'=>$row->othol,'lateut'=>$row->lateut,'isholiday'=>$row->isholiday,'workdays'=>$row->workdays,'day_absent'=>$row->day_absent,'hold_status_change'=>$row->hold_status);

				if($att_q->num_rows() > 0){
				foreach ($att_q->result() as $key => $row) {

					$data[$row->qDeptId][$row->qEmpId] = array('fullname'=>$row->qFullname,'eleave'=>$row->eleave,'vleave'=>$row->vleave,'sleave'=>$row->sleave,'absent'=>$row->absent,'day_absent'=>$row->day_absent,'hold_status_change'=>$row->hold_status,'substitute'=>$row->substitute);

					$perdept_arr = array();
					$perdept_q = $this->db->query("SELECT work_hours, late_hours, deduc_hours, `type`, aimsdept FROM workhours_perdept_nt WHERE base_id='{$row->base_id}'");

					foreach ($perdept_q->result() as $key_dept => $row_dept) {
						$perdept_arr[$row_dept->aimsdept][$row_dept->type] = array('work_hours'=>$row_dept->work_hours,'late_hours'=>$row_dept->late_hours,'deduc_hours'=>$row_dept->deduc_hours, 'aimsdept' => $row_dept->aimsdept);
					}

					$data[$row->qDeptId][$row->qEmpId]["perdept_arr"] = $this->detailedHolidayPay($perdept_arr, $cutoffstart, $cutoffend, $row->qEmpId);
					$data[$row->qDeptId][$row->qEmpId]["perdept_arr"] = $this->substituteDetailed($data[$row->qDeptId][$row->qEmpId]["perdept_arr"], $row->base_id);
				}
			}

			}
		}
		return $data;
	}
	function substituteDetailed($perdept_arr, $id){
		$q_att = $this->db->query("SELECT * FROM  attendance_confirmed_substitute_hours WHERE base_id = '$id'");
		foreach ($q_att->result() as $key_dept => $row_dept) {
			if($row_dept->type == "LEC") $perdept_arr[$row_dept->aimsdept]["LEC"]['substitute'] = array('work_hours'=>$row_dept->hours, 'late_hours'=>'0:00', 'deduc_hours'=>'0:00');
			if($row_dept->type == "LAB") $perdept_arr[$row_dept->aimsdept]["LAB"]['substitute'] = array('work_hours'=>$row_dept->hours, 'late_hours'=>'0:00', 'deduc_hours'=>'0:00');
			if($row_dept->type == "ADMIN") $perdept_arr[$row_dept->aimsdept]["ADMIN"]['substitute'] = array('work_hours'=>$row_dept->hours, 'late_hours'=>'0:00', 'deduc_hours'=>'0:00');
			if($row_dept->type == "RLE") $perdept_arr[$row_dept->aimsdept]["RLE"]['substitute'] = array('work_hours'=>$row_dept->hours, 'late_hours'=>'0:00', 'deduc_hours'=>'0:00');
		}

		return $perdept_arr;
	}

	function detailedHolidayPay($perdept_arr, $cutoffstart, $cutoffend, $employeeid){
		$lec_tothours = $lab_tothours = $admin_tothours = $rle_tothours = "";
		$detailed_att = $this->db->query("SELECT * FROM `employee_attendance_detailed` WHERE sched_date BETWEEN '$cutoffstart' AND '$cutoffend' AND employeeid = '$employeeid' ");
		if($detailed_att->num_rows() > 0){
			foreach($detailed_att->result_array() as $det_atts){
				$teachingtype = $this->extensions->getEmployeeTeachingType($employeeid);
				$deptid = $this->extensions->getEmployeeOffice($employeeid);
				$holiday = $this->attcompute->isHolidayNewAttendanceReport($employeeid,$det_atts["sched_date"],$deptid); 
				if($holiday){
					$holidayInfo = $this->attcompute->holidayInfo($det_atts["sched_date"]);
					if($holidayInfo) $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], $teachingtype);
					$lec_hours = $this->constructArrayListFromAttendanceDetailed($det_atts["lec"]);
					$lab_hours = $this->constructArrayListFromAttendanceDetailed($det_atts["lab"]);
					$admin_hours = $this->constructArrayListFromAttendanceDetailed($det_atts["admin"]);
					$rle_hours = $this->constructArrayListFromAttendanceDetailed($det_atts["rle"]);
					if($holidayInfo["holiday_type"] != 5){
						if($lec_hours){
							foreach($lec_hours as $count_lec => $lec){
								// $lec["work_hours"] = $lec["work_hours"] / 60;
								// $lec["work_hours"] = $lec["work_hours"] * $rate / 100;
								$lec["aimsdept"] = isset($lec["aimsdept"]) ? $lec["aimsdept"] : "";
								if(isset($perdept_arr[$lec["aimsdept"]]["LEC"]['holiday']) && 
								is_array($perdept_arr[$lec["aimsdept"]]["LEC"]['holiday'])){
									$holiday = $perdept_arr[$lec["aimsdept"]]["LEC"]['holiday'];
									
									$lec["work_hours"] += isset($holiday["work_hours"]) ? (float)$holiday["work_hours"] : 0;
									$lec["late_hours"] += isset($holiday["late_hours"]) ? (float)$holiday["late_hours"] : 0;
									$lec["deduc_hours"] += isset($holiday["deduc_hours"]) ? (float)$holiday["deduc_hours"] : 0;
								}

								$lec_hours[$count_lec]["work_hours"] = $lec["work_hours"];
								$lec_hours[$count_lec]["late_hours"] = $lec["late_hours"];
								$lec_hours[$count_lec]["deduc_hours"] = isset($lec["deduc_hours"]) ? $lec["deduc_hours"] : "";
							}

						}
						if($lab_hours){
							foreach($lab_hours as $count_lab => $lab){
								// $lab["work_hours"] = $lab["work_hours"] / 60;
								// $lab["work_hours"] = $lab["work_hours"] * $rate / 100;
								$lab["aimsdept"] = isset($lab["aimsdept"]) ? $lab["aimsdept"] : "";
								if(isset($perdept_arr[$lab["aimsdept"]]["LAB"]['holiday'])){
									$lab["work_hours"] += $perdept_arr[$lab["aimsdept"]]["LAB"]['holiday']["work_hours"];
									$lab["late_hours"] += $perdept_arr[$lab["aimsdept"]]["LAB"]['holiday']["late_hours"];
									$lab["deduc_hours"] += $perdept_arr[$lab["aimsdept"]]["LAB"]['holiday']["deduc_hours"];
								}

								$lab_hours[$count_lab]["work_hours"] = $lab["work_hours"];
								$lab_hours[$count_lab]["late_hours"] = $lab["late_hours"];
								$lab_hours[$count_lab]["deduc_hours"] = $lab["deduc_hours"];
							}
						}
						if($rle_hours){
							foreach($rle_hours as $count_rle => &$rle){
								$rle["aimsdept"] = isset($rle["aimsdept"]) ? $rle["aimsdept"] : "";
								
								if(isset($perdept_arr[$rle["aimsdept"]]["RLE"]['holiday']) && 
								   is_array($perdept_arr[$rle["aimsdept"]]["RLE"]['holiday'])){
									$holiday = $perdept_arr[$rle["aimsdept"]]["RLE"]['holiday'];
									
									$rle["work_hours"] += isset($holiday["work_hours"]) ? (float)$holiday["work_hours"] : 0;
									$rle["late_hours"] += isset($holiday["late_hours"]) ? (float)$holiday["late_hours"] : 0;
									$rle["deduc_hours"] = isset($rle["deduc_hours"]) ? (float)$rle["deduc_hours"] : 0;
									$rle["deduc_hours"] += isset($holiday["deduc_hours"]) ? (float)$holiday["deduc_hours"] : 0;
								}
						 
								$rle_hours[$count_rle]["work_hours"] = $rle["work_hours"];
								$rle_hours[$count_rle]["late_hours"] = $rle["late_hours"];
								$rle_hours[$count_rle]["deduc_hours"] = $rle["deduc_hours"];
							}
						}
						if($lec_hours){ 
							foreach($lec_hours as $lecs){
								$lecs["aimsdept"] = isset($lecs["aimsdept"]) ? $lecs["aimsdept"] : "";
								$perdept_arr[$lecs["aimsdept"]]["LEC"]['holiday'] = array('work_hours'=>$lecs["work_hours"],'late_hours'=>$lecs["late_hours"],'deduc_hours'=>$lecs["deduc_hours"],'aimsdept'=>$lecs["aimsdept"]);
							}
						}
						if($lab_hours){ 
							foreach($lab_hours as $labs){
								$labs["aimsdept"] = isset($labs["aimsdept"]) ? $labs["aimsdept"] : "";
								$perdept_arr[$labs["aimsdept"]]["LAB"]['holiday'] = array('work_hours'=>$labs["work_hours"],'late_hours'=>$labs["late_hours"],'deduc_hours'=>$labs["deduc_hours"],'aimsdept'=>$labs["aimsdept"]);
							}
						}
						if($admin_hours){ 
							foreach($admin_hours as $admins){
								$admins["aimsdept"] = isset($admins["aimsdept"]) ? $admins["aimsdept"] : "";
								$perdept_arr[$admins["aimsdept"]]["ADMIN"]['holiday'] = array('work_hours'=>$admins["work_hours"],'late_hours'=>$admins["late_hours"],'deduc_hours'=>$admins["deduc_hours"],'aimsdept'=>$admins["aimsdept"]);
							}
						}
						if($rle_hours){ 
							foreach($rle_hours as $rles){
								$rles["aimsdept"] = isset($rles["aimsdept"]) ? $rles["aimsdept"] : "";
								$perdept_arr[$rles["aimsdept"]]["RLE"]['holiday'] = array('work_hours'=>$rles["work_hours"],'late_hours'=>$rles["late_hours"],'deduc_hours'=>$rles["deduc_hours"],'aimsdept'=>$rles["aimsdept"]);
							}
						}
					}else{
						$holidayInfo = $this->attcompute->holidayInfo($det_atts["sched_date"]);
						if($holidayInfo) $rate = $this->extensions->getHolidayTypeRate($holidayInfo["holiday_type"], $teachingtype);
						$lec_hours = $this->constructArrayListFromAttendanceDetailed($det_atts["lec"]);
						$lab_hours = $this->constructArrayListFromAttendanceDetailed($det_atts["lab"]);
						$admin_hours = $this->constructArrayListFromAttendanceDetailed($det_atts["admin"]);
						$rle_hours = $this->constructArrayListFromAttendanceDetailed($det_atts["rle"]);
						if($lec_hours){
							$tag = "holiday";
							foreach($lec_hours as $count_lec => $lec){
								if($lec["suspension"]) $tag = "suspension";
								/*$lec["work_hours"] = $lec["work_hours"] / 60;
								$lec["work_hours"] = $lec["work_hours"] * $rate / 100;*/

								if(isset($perdept_arr[$lec["aimsdept"]]["LEC"][$tag])){
									$lec["work_hours"] += $perdept_arr[$lec["aimsdept"]]["LEC"][$tag]["work_hours"];
									$lec["late_hours"] += $perdept_arr[$lec["aimsdept"]]["LEC"][$tag]["late_hours"];
									$lec["deduc_hours"] += $perdept_arr[$lec["aimsdept"]]["LEC"][$tag]["deduc_hours"];
								}

								$lec_hours[$count_lec]["work_hours"] = $lec["work_hours"];
								$lec_hours[$count_lec]["late_hours"] = $lec["late_hours"];
								$lec_hours[$count_lec]["deduc_hours"] = $lec["deduc_hours"];
							}

						}
						if($lab_hours){
							$tag = "holiday";
							foreach($lab_hours as $count_lab => $lab){
								if($lab["suspension"]) $tag = "suspension";
								/*$lab["work_hours"] = $lab["work_hours"] / 60;
								$lab["work_hours"] = $lab["work_hours"] * $rate / 100;*/

								if(isset($perdept_arr[$lab["aimsdept"]]["LAB"][$tag])){
									$lab["work_hours"] += $perdept_arr[$lab["aimsdept"]]["LAB"][$tag]["work_hours"];
									$lab["late_hours"] += $perdept_arr[$lab["aimsdept"]]["LAB"][$tag]["late_hours"];
									$lab["deduc_hours"] += $perdept_arr[$lab["aimsdept"]]["LAB"][$tag]["deduc_hours"];
								}

								$lab_hours[$count_lab]["work_hours"] = $lab["work_hours"];
								$lab_hours[$count_lab]["late_hours"] = $lab["late_hours"];
								$lab_hours[$count_lab]["deduc_hours"] = $lab["deduc_hours"];
							}
						}
						if($rle_hours){
							$tag = "holiday";
							foreach($rle_hours as $count_rle => $rle){
								if($rle["suspension"]) $tag = "suspension";
								/*$rle["work_hours"] = $rle["work_hours"] / 60;
								$rle["work_hours"] = $rle["work_hours"] * $rate / 100;*/

								if(isset($perdept_arr[$rle["aimsdept"]]["RLE"][$tag])){
									$rle["work_hours"] += $perdept_arr[$rle["aimsdept"]]["RLE"][$tag]["work_hours"];
									$rle["late_hours"] += $perdept_arr[$rle["aimsdept"]]["RLE"][$tag]["late_hours"];
									$rle["deduc_hours"] += $perdept_arr[$rle["aimsdept"]]["RLE"][$tag]["deduc_hours"];
								}

								$rle_hours[$count_rle]["work_hours"] = $rle["work_hours"];
								$rle_hours[$count_rle]["late_hours"] = $rle["late_hours"];
								$rle_hours[$count_rle]["deduc_hours"] = $rle["deduc_hours"];
							}
						}
						if($lec_hours){ 
							$tag = "holiday";
							foreach($lec_hours as $lecs){
								if($lecs["suspension"]) $tag = "suspension";
								$perdept_arr[$lecs["aimsdept"]]["LEC"][$tag] = array('work_hours'=>$lecs["work_hours"],'late_hours'=>$lecs["late_hours"],'deduc_hours'=>$lecs["deduc_hours"]);
							}
						}
						if($lab_hours){ 
							$tag = "holiday";
							foreach($lab_hours as $labs){
								if($labs["suspension"]) $tag = "suspension";
								$perdept_arr[$labs["aimsdept"]]["LAB"][$tag] = array('work_hours'=>$labs["work_hours"],'late_hours'=>$labs["late_hours"],'deduc_hours'=>$labs["deduc_hours"]);
							}
						}
						if($admin_hours){ 
							$tag = "holiday";
							foreach($admin_hours as $admins){
								$perdept_arr[$admins["aimsdept"]]["ADMIN"][$tag] = array('work_hours'=>$admins["work_hours"],'late_hours'=>$admins["late_hours"],'deduc_hours'=>$admins["deduc_hours"]);
							}
						}
						if($rle_hours){ 
							$tag = "holiday";
							foreach($rle_hours as $rles){
								if($rles["suspension"]) $tag = "suspension";
								$perdept_arr[$rles["aimsdept"]]["RLE"][$tag] = array('work_hours'=>$rles["work_hours"],'late_hours'=>$rles["late_hours"],'deduc_hours'=>$rles["deduc_hours"]);
							}
						}
					}
				}

				$deptid = $teachingtype = "";
			}
		}
		// echo "<pre>"; print_r($perdept_arr); die;
		return $perdept_arr;
	}

	function constructArrayListFromAttendanceDetailed($str=''){
	    $arr = array();
	    if($str){
	        $str_base = explode('&', $str);
	        if(count($str_base)){
	        	foreach($str_base as $count => $str_arr){
	        		$str_arr = explode("/", $str_arr);
		            foreach ($str_arr as $i_temp) {
		                $str_arr_temp = explode('=', $i_temp);
		                if(isset($str_arr_temp[0]) && isset($str_arr_temp[1])){
		                    $arr[$count][$str_arr_temp[0]] = $str_arr_temp[1];
		                }
	        		}
	            }
	        }
	    }
	    return $arr;
	}

	function payrollGrandTotal($emplist=array()){
		$total = array();
		if($emplist){
			foreach($emplist as $row){
				// echo "<pre>"; print_r($row); die;
				if($row["loan"]){
					foreach($row["loan"] as $key => $val){
						if(isset($total["loan"][$key])) $total["loan"][$key] += $val;
						else $total["loan"][$key] = $val;
					}
				}

				if($row["fixeddeduc"]){
					foreach($row["fixeddeduc"] as $key => $val){
						if(isset($total["fixeddeduc"][$key])) $total["fixeddeduc"][$key] += $val;
						else $total["fixeddeduc"][$key] = $val;
					}
				}

				if($row["deduction"]){
					foreach($row["deduction"] as $key => $val){
						if(isset($total["deduction"][$key])) $total["deduction"][$key] += $val;
						else $total["deduction"][$key] = $val;
					}
				}

				if($row["income_adj"]){
					foreach($row["income_adj"] as $key => $val){
						if(isset($total["income_adj"][$key])) $total["income_adj"][$key] += $val;
						else $total["income_adj"][$key] = $val;
					}
				}

				if($row["income"]){
					foreach($row["income"] as $key => $val){
						if(isset($total["income"][$key])) $total["income"][$key] += $val;
						else $total["income"][$key] = $val;
					}
				}

				if(isset($total["salary"])) $total["salary"] += $row["salary"];
				else $total["salary"] = $row["salary"];

				if(isset($total["teaching_pay"])) $total["teaching_pay"] += $row["teaching_pay"];
				else $total["teaching_pay"] = $row["teaching_pay"];

				if(isset($total["tardy"])) $total["tardy"] += $row["tardy"];
				else $total["tardy"] = $row["tardy"];

				if(isset($total["absents"])) $total["absents"] += $row["absents"];
				else $total["absents"] = $row["absents"];

				if(isset($total["overtime"])) $total["overtime"] += $row["overtime"];
				else $total["overtime"] = $row["overtime"];

				if(isset($total["whtax"])) $total["whtax"] += $row["whtax"];
				else $total["whtax"] = $row["whtax"];

				if(isset($total["netbasicpay"])) $total["netbasicpay"] += $row["netbasicpay"];
				else $total["netbasicpay"] = $row["netbasicpay"];

				if(isset($total["grosspay"])) $total["grosspay"] += $row["grosspay"];
				else $total["grosspay"] = $row["grosspay"];

				if(isset($total["netpay"])) $total["netpay"] += $row["netpay"];
				else $total["netpay"] = $row["netpay"];

				if(isset($row["substitute"])){
					if(isset($total["substitute"])) $total["substitute"] += $row["substitute"];
					else $total["substitute"] = $row["substitute"];
				}
			}
		}

		return $total;
	}

	function payrollSubTotal($emplist=array()){
		$total = array();
		$old_deptid = "";
		if($emplist){
			foreach($emplist as $row){
				// echo "<pre>"; print_r($row); die;
				$deptid = $row["deptid"];
				if($row["loan"]){
					foreach($row["loan"] as $key => $val){
						if(isset($total[$deptid]["loan"][$key])) $total[$deptid]["loan"][$key] += $val;
						else $total[$deptid]["loan"][$key] = $val;
					}
				}

				if($row["fixeddeduc"]){
					foreach($row["fixeddeduc"] as $key => $val){
						if(isset($total[$deptid]["fixeddeduc"][$key])) $total[$deptid]["fixeddeduc"][$key] += $val;
						else $total[$deptid]["fixeddeduc"][$key] = $val;
					}
				}

				if($row["deduction"]){
					foreach($row["deduction"] as $key => $val){
						if(isset($total[$deptid]["deduction"][$key])) $total[$deptid]["deduction"][$key] += $val;
						else $total[$deptid]["deduction"][$key] = $val;
					}
				}

				if($row["income_adj"]){
					foreach($row["income_adj"] as $key => $val){
						if(isset($total[$deptid]["income_adj"][$key])) $total[$deptid]["income_adj"][$key] += $val;
						else $total[$deptid]["income_adj"][$key] = $val;
					}
				}

				if($row["income"]){
					foreach($row["income"] as $key => $val){
						if(isset($total[$deptid]["income"][$key])) $total[$deptid]["income"][$key] += $val;
						else $total[$deptid]["income"][$key] = $val;
					}
				}

				if(isset($total[$deptid]["salary"])) $total[$deptid]["salary"] += $row["salary"];
				else $total[$deptid]["salary"] = $row["salary"];

				if(isset($total[$deptid]["teaching_pay"])) $total[$deptid]["teaching_pay"] += $row["teaching_pay"];
				else $total[$deptid]["teaching_pay"] = $row["teaching_pay"];

				if(isset($total[$deptid]["tardy"])) $total[$deptid]["tardy"] += $row["tardy"];
				else $total[$deptid]["tardy"] = $row["tardy"];

				if(isset($total[$deptid]["absents"])) $total[$deptid]["absents"] += $row["absents"];
				else $total[$deptid]["absents"] = $row["absents"];

				if(isset($total[$deptid]["overtime"])) $total[$deptid]["overtime"] += $row["overtime"];
				else $total[$deptid]["overtime"] = $row["overtime"];

				if(isset($total[$deptid]["whtax"])) $total[$deptid]["whtax"] += $row["whtax"];
				else $total[$deptid]["whtax"] = $row["whtax"];

				if(isset($total[$deptid]["netbasicpay"])) $total[$deptid]["netbasicpay"] += $row["netbasicpay"];
				else $total[$deptid]["netbasicpay"] = $row["netbasicpay"];

				if(isset($total[$deptid]["grosspay"])) $total[$deptid]["grosspay"] += $row["grosspay"];
				else $total[$deptid]["grosspay"] = $row["grosspay"];

				if(isset($total[$deptid]["netpay"])) $total[$deptid]["netpay"] += $row["netpay"];
				else $total[$deptid]["netpay"] = $row["netpay"];
				if(isset($row["substitute"])){
					if(isset($total[$deptid]["substitute"])) $total[$deptid]["substitute"] += $row["substitute"];
					else $total[$deptid]["substitute"] = $row["substitute"];
				}
			}
		}

		return $total;
	}

	public function fetch_dtr($id){
        return $this->db->query("SELECT a.report_title, b.* FROM report_list a INNER JOIN report_breakdown b ON a.id = b.base_id WHERE status IN ('rendering', 'ongoing', 'done') AND b.base_id = '$id' GROUP BY b.employeeid ")->result_array();
    }

}