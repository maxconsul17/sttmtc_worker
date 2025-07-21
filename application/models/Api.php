<?php 

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Model {

	public function base64UrlEncode($data) {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}
	
	public function generateAccessToken($header, $payload, $key) {
		// Encode the header and payload to JSON and then to Base64Url
		$headerEncoded = $this->base64UrlEncode(json_encode($header));
		$payloadEncoded = $this->base64UrlEncode(json_encode($payload));
		
		// Create the signature by hashing the encoded header and payload with the secret key
		$signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $key, true);
		$signatureEncoded = $this->base64UrlEncode($signature);
		
		// Construct the JWT
		$access_token = "$headerEncoded.$payloadEncoded.$signatureEncoded";
		
		// Ensure the JWT is exactly 200 characters long
		// If JWT is shorter than 200 characters, pad it with '='
		// If JWT is longer, truncate it (which is generally not recommended for real JWTs)
		if (strlen($access_token) > 165) {
			$access_token = substr($access_token, 0, 165);
		} else {
			$access_token = str_pad($access_token, 165, '=');
		}
	
		return $access_token;
	}

	public function generateSsoKey($length = 32) {
        // Generate a random key of the specified length in bytes
        $randomBytes = random_bytes($length);
        
        // Convert the binary data to a hexadecimal representation
        $key = bin2hex($randomBytes);
        
        return $key;
    }

	public function addNewEmployee($emp_data, $user_data){
		 $this->db->insert("employee", $emp_data);
		 $this->db->insert("user_info", $user_data);
	}

	public function updateAccessToken($userid, $access_token){
		return $this->db->query("UPDATE aims_token SET userid = '$userid' WHERE access_token = '$access_token' ");
	}

	public function employeeMasterList($where_clause){
		return $this->db->query("SELECT CONCAT(fname, ' ', mname, ' ', lname) AS fullname, employeeid FROM employee a INNER JOIN user_info b ON a.employeeid = b.username WHERE employeeid != '' $where_clause");
	}

	public function accountInAims($username){
		$empinfo["employee_id"] = $username;
		$curl_uri = Globals::aimsAPIUrl();
		$api_url = $curl_uri."api/request.php";
		$token = Globals::accessToken();
		$curl = curl_init();
		curl_setopt_array($curl, array(
		CURLOPT_URL => $api_url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => json_encode($empinfo),
		CURLOPT_HTTPHEADER => array(
			"Accept: application/json",
			"Authorization: Bearer $token",
			"API_Function: core_faculty_check_employeeid",
			"cache-control: no-cache"
			),
		));

		$result = curl_exec($curl);
		$response = json_decode($result);
		$err = curl_error($curl);
		curl_close($curl);
		if(isset($response->data) && $response->data) return true;
		else return false;
	}

	public function isEmployeeActive($employeeid=""){
		return $this->db->query("SELECT * FROM employee WHERE (dateresigned='1970-01-01' OR dateresigned='0000-00-00' OR dateresigned IS NULL) AND isactive = '1' AND employeeid = '$employeeid' ");
	}

	public function isAccountActive($employeeid=""){
		return $this->db->query("SELECT * FROM user_info WHERE username = '$employeeid' AND STATUS = 'ACTIVE' ");
	}

	public function saveHyperionToken($data){
		$this->db->insert("hyperion_token", $data);  /*save granted token*/
	}

	public function verifyHyperionAccessToken($token){
		return $this->db->query("SELECT * FROM hyperion_token WHERE access_token = '$token'")->num_rows();
	}

	function getIdx($day){
        if($day == "M") return 1;
        elseif($day == "T") return 2;
        elseif($day == "W") return 3;
        elseif($day == "TH") return 4;
        elseif($day == "F") return 5;
        elseif($day == "S") return 6;
        elseif($day == "SUN") return 0;
    }
   
    /**
     * gracePeriodRange()
     *
     * return query result
     *
     * @param (integer) ($minutes) total minutes of schedule
     * @param (integer) - ($year) year of config
     * @return (resource) - query result
     */
    public function gracePeriodRange($minutes, $year){
    	return $this->db->query("SELECT * FROM earlydismissal WHERE '$minutes' BETWEEN rangefrom AND rangeto AND year <= '$year'");
    }

	public function saveSSOKey($data){
		$this->db->insert("sso_key", $data); 
	}

	public function verifySSOKey($key){
		$expires = strtotime($this->extensions->getServerTime());
		return $this->db->query("SELECT * FROM sso_key WHERE log_key = '$key' AND expires_in >= '$expires'");
	}
	
	public function failedAimsApi(){
		return $this->db->query("SELECT * FROM aims_api_logs WHERE status != 'success' AND is_processed = 0 ");
	}

	public function updateAimsApiStatus($id){
		return $this->db->query("UPDATE aims_api_logs SET is_processed = 1 WHERE id = '$id'");
	}

	public function save_sched_logs($log_arr){
    	return $this->db->insert("aims_schedule_logs", $log_arr);
    }

	public function save_faculty_logs($log_arr){
    	return $this->db->insert("aims_faculty_logs", $log_arr);
    }
	
	public function saveSchoolOf($data) {
		$q_exists = $this->db->query("SELECT * FROM tblCourseCategory WHERE CODE = '{$data['code']}' AND CAMPUS = '{$data['campus']}'");
		
		if ($q_exists->num_rows() > 0) {
			$this->db->where("CODE", $data["code"]);
			$this->db->where("CAMPUS", $data["campus"]);
			$this->db->update("tblCourseCategory", $data);
			
			return ($this->db->affected_rows() > 0);
		} else {
			$this->db->insert("tblCourseCategory", $data);
			
			return ($this->db->affected_rows() > 0);
		}
	}
	

	public function saveHrisApiLogs($data){
		$this->db->insert("hris_api_logs", $data);
	}

	public function update_calculate_status($filter, $status="done"){
		$this->db->where($filter)
         ->set('status', $status)
         ->update('employee_to_calculate');
		//  $det = [
		// 	"details" => $this->db->last_query()
		// ];
		// $this->db->insert("for_trail", $det);
	}

	public function updateCalculateTagging($employeeid, $dfrom, $dto){
		$this->db->set('hasUpdate', '0')
         ->where('date >=', $dfrom)
         ->where('date <=', $dto)
         ->where('employeeid', $employeeid)
         ->update('employee_attendance_update');

	}

	public function sendLogsToOtherSite($logs_d, $que_log = false, $processed_by=""){
		// If que_log is false, meaning that it will be process now
		if($que_log === false){
			$logs_d = (array) $logs_d;
			try{
				$logs_d["client_secret"] = getenv("API_CLIENT_SECRET");
				$logs_d["username"] = getenv("API_USERNAME");
				$logs_d["password"] = getenv("API_PASSWORD");
				$trail = array();
				$campus_id = $this->db->campus_code;
				$site_list = Globals::sites();
				unset($site_list[$campus_id]);
				foreach($site_list as $code => $desc){
					$endpoint = Globals::campusEndpoints($code);
					$api_url = $endpoint."Api_/saveLogsFromOtherSite";
					$token = Globals::hrisAccessToken($endpoint);
					$curl = curl_init();
					curl_setopt_array($curl, array(
					CURLOPT_URL => $api_url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_SSL_VERIFYHOST => false,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "POST",
					CURLOPT_POSTFIELDS => json_encode($logs_d),
					CURLOPT_HTTPHEADER => array(
						"Accept: application/json",
						"Authorization: Bearer $token",
						),
					));

					$result = curl_exec($curl);
					$response = json_decode($result);
					$err = curl_error($curl);

					if($err === ""){
						$trail = array(
							"details" => json_encode($logs_d),
							"status" => "success"
						);
					}else{
						$trail = array(
							"details" => json_encode($logs_d),
							"status" => "has curl error"
						);

						// ADD THIS CODE TO QUE FAILED ATTEMPT TO SEND LOGS TO OTHER SITE
						$logs_d = (array) $logs_d;
						$logs_d["client_secret"] = getenv("API_CLIENT_SECRET");
						$logs_d["username"] = getenv("API_USERNAME");
						$logs_d["password"] = getenv("API_PASSWORD");
						$que_d = array(
							"body" => json_encode($logs_d),
							"status" => "pending",
							"try" => 3,
							"processed_by" => $this->session->userdata("username"),
						);

						$this->db->insert("facial_log_que", $que_d);
					}
					
					$this->db->insert("transfer_logs_trail", $trail);
					curl_close($curl);
					
				}
			}catch (Exception $e) {
				$trail = array(
					"details" => $post_data,
					"status" => "has code error"
				);

				$this->db->insert("transfer_logs_trail", $trail);
			}
		}else{
			$logs_d = (array) $logs_d;

			// if true, meaning it will be process later by worker

			$logs_d["client_secret"] = getenv("API_CLIENT_SECRET");
			$logs_d["username"] = getenv("API_USERNAME");
			$logs_d["password"] = getenv("API_PASSWORD");
			$trail = array(
				"body" => json_encode($logs_d),
				"status" => "pending",
				"try" => 3,
				"processed_by" => $processed_by,
			);

			$this->db->insert("facial_log_que", $trail);

			Globals::pd($this->db->last_query());
		}
	}

	public function cancelOnlineApplicationToOtherSite($other_sites, $temp_payload){
		try{
			foreach($other_sites as $code){
				$endpoint = Globals::campusEndpoints($code);
				$api_url = $endpoint."Api_/cancel_online_application_to_other_sites";
				$token = Globals::hrisAccessToken($endpoint);

				$payload = [
					"client_secret" => "Y2M1N2E4OGUzZmJhOWUyYmIwY2RjM2UzYmI4ZGFiZjk=",
					"username" => "hyperion",
					"password" => "@stmtccHyperion2024",
					"data" => $temp_payload,
				];

				$curl = curl_init();
				curl_setopt_array($curl, array(
				CURLOPT_URL => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode($payload),
				CURLOPT_HTTPHEADER => array(
					"Accept: application/json",
					"Authorization: Bearer $token",
					),
				));

				$result = curl_exec($curl);
				$response = json_decode($result);
				$err = curl_error($curl);

				curl_close($curl);
			}
		}catch (Exception $e) {
			var_dump($e);
		}
	}

	public function sendOnlineApplicationToOtherSite($other_sites, $payload){
		try{
			foreach($other_sites as $code){
				$endpoint = Globals::campusEndpoints($code);
				$api_url = $endpoint."Api_/save_online_application_to_other_sites";
				$token = Globals::hrisAccessToken($endpoint);

				$payload = array_merge($payload, [
					"client_secret" => "Y2M1N2E4OGUzZmJhOWUyYmIwY2RjM2UzYmI4ZGFiZjk=",
					"username" => "hyperion",
					"password" => "@stmtccHyperion2024",
				]);

				$curl = curl_init();
				curl_setopt_array($curl, array(
				CURLOPT_URL => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode($payload),
				CURLOPT_HTTPHEADER => array(
					"Accept: application/json",
					"Authorization: Bearer $token",
					),
				));

				$result = curl_exec($curl);
				$response = json_decode($result);
				$err = curl_error($curl);

				curl_close($curl);
			}
		}catch (Exception $e) {
			var_dump($e);
		}
	}


	public function syncLogsFromMain($payload){
		$this->load->model("facial");
		$this->load->model("timesheet");
		$campus_code = $this->db->campus_code;
		$sites = explode(",", $payload['campusid']);
		$sites = array_filter($sites, function($site) use($campus_code) {
			return $site != $campus_code;
		});

		$has_error = false;

		unset($payload['campusid']);

		try{
			foreach($sites as $site){
				$endpoint = Globals::campusEndpoints($site);
				$api_url = $endpoint."Api_/sync_employee_attendance";
				$token = Globals::hrisAccessToken($endpoint);

				$payload = array_merge($payload, [
					"client_secret" => "Y2M1N2E4OGUzZmJhOWUyYmIwY2RjM2UzYmI4ZGFiZjk=",
					"username" => "hyperion",
					"password" => "@stmtccHyperion2024",
					'campusid' => $site,
				]);

				$curl = curl_init();
				curl_setopt_array($curl, array(
				CURLOPT_URL => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode($payload),
				CURLOPT_HTTPHEADER => array(
					"Accept: application/json",
					"Authorization: Bearer $token",
					),
				));

				$result = curl_exec($curl);
				$response = json_decode($result, true);
				$err = curl_error($curl);

				if ($err === "") {
					$facialLogs = $this->facial->filterDuplicates($response['facialLog']);
					$timesheetLogs = $this->timesheet->filterDuplicates($response['timesheet']);

					$this->db->insert_batch("facial_Log", $facialLogs);
					$this->db->insert_batch("timesheet", $timesheetLogs);
				} else {
					$has_error = true;
				}

				curl_close($curl);
			}
		}catch (Exception $e) {
			var_dump($e);

			echo json_encode(array(
				"err" => 1,
				"message" => "Failed to sync logs. Please check the data or try again."
			));
			return;
		}

		if($has_error) {
			echo json_encode(array(
				"err" => 1,
				"message" => "Failed to sync logs. Please check the data or try again."
			));
			return;
		}

		echo json_encode(array(
			"err" => 0,
			"message" => "Logs synced successfully."
		));
		return;
	}

	public function getOtherSiteIncomeContri($schedule,$cutoff_period,$empid,$sdate,$edate,$included){
		$campus_code = $this->db->campus_code;
		$qsites = $this->db->query("SELECT CONCAT(campusid, IF(subcampusid IS NOT NULL AND subcampusid != '' AND subcampusid != 'null', CONCAT(',', subcampusid), '')) AS allcampus FROM employee WHERE employeeid = '$empid'")->row(0)->allcampus;

		$sites = explode(",", $qsites);
        $other_sites = array_filter($sites, function($key) use($campus_code) {
            return $key != $campus_code;
        }, ARRAY_FILTER_USE_BOTH);

		$gross = 0;
		
		try{
			foreach($other_sites as $code){
				$endpoint = Globals::campusEndpoints($code);
				$api_url = $endpoint."Api_/get_other_site_income_contri";
				// $api_url = "http://192.168.2.236:9043/index.php/"."Api_/get_other_site_salary";

				$payload = [
					"client_secret" => "Y2M1N2E4OGUzZmJhOWUyYmIwY2RjM2UzYmI4ZGFiZjk=",
					"username" => "hyperion",
					"password" => "@stmtccHyperion2024",
					"schedule" => $schedule,
					"cutoff_period" => $cutoff_period,
					"empid" => $empid,
					"sdate" => $sdate,
					"edate" => $edate,
					"included" => $included,
				];

				$curl = curl_init();
				curl_setopt_array($curl, array(
				CURLOPT_URL => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode($payload),
				CURLOPT_HTTPHEADER => array(
					"Accept: application/json",
					),
				));

				$result = curl_exec($curl);
				$response = json_decode($result);
				$err = curl_error($curl);

				curl_close($curl);

				if ($response){
					if ($response->gross){
						$gross += $response->gross;
					}
				}
			}
			return $gross;
		}catch (Exception $e) {
			return $gross;
		}
	}

	public function getOtherSiteSalary($eid,$sdate){
		$campus_code = $this->db->campus_code;
		$qsites = $this->db->query("SELECT CONCAT(campusid, IF(subcampusid IS NOT NULL AND subcampusid != '' AND subcampusid != 'null', CONCAT(',', subcampusid), '')) AS allcampus FROM employee WHERE employeeid = '$eid'")->row(0)->allcampus;

		$sites = explode(",", $qsites);
        $other_sites = array_filter($sites, function($key) use($campus_code) {
            return $key != $campus_code;
        }, ARRAY_FILTER_USE_BOTH);

		$lecrate = $monthly = 0;
		
		try{
			foreach($other_sites as $code){
				$endpoint = Globals::campusEndpoints($code);
				$api_url = $endpoint."Api_/get_other_site_salary";
				// $api_url = "http://192.168.2.236:9043/index.php/"."Api_/get_other_site_salary";

				$payload = [
					"client_secret" => "Y2M1N2E4OGUzZmJhOWUyYmIwY2RjM2UzYmI4ZGFiZjk=",
					"username" => "hyperion",
					"password" => "@stmtccHyperion2024",
					"eid" => $eid,
					"sdate" => $sdate,
				];

				$curl = curl_init();
				curl_setopt_array($curl, array(
				CURLOPT_URL => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode($payload),
				CURLOPT_HTTPHEADER => array(
					"Accept: application/json",
					),
				));

				$result = curl_exec($curl);
				$response = json_decode($result);
				$err = curl_error($curl);

				curl_close($curl);

				if ($response){
					if ($response->salary){
						if ($response->schedHrs){
							$lecrate += ($response->salary->lechour * $response->schedHrs) * 4;
						}
						if ($response->hasAdminSched){
							$monthly += $response->salary->monthly;
						}
					}
				}
			}
			return array($lecrate,$monthly);
		}catch (Exception $e) {
			return array($lecrate,$monthly);
		}
	}

	public function calculateEmployeeAttendanceToOtherSite($other_sites, $employee_id, $dfrom, $dto){
		try{
			$temp_payload = [$employee_id, $dfrom, $dto];

			foreach($other_sites as $code){
				$endpoint = Globals::campusEndpoints($code);
				$api_url = $endpoint."Api_/calculate_employee_attendance";
				$token = Globals::hrisAccessToken($endpoint);

				$payload = [
					"client_secret" => "Y2M1N2E4OGUzZmJhOWUyYmIwY2RjM2UzYmI4ZGFiZjk=",
					"username" => "hyperion",
					"password" => "@stmtccHyperion2024",
					"data" => $temp_payload,
				];

				$curl = curl_init();
				curl_setopt_array($curl, array(
				CURLOPT_URL => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode($payload),
				CURLOPT_HTTPHEADER => array(
					"Accept: application/json",
					"Authorization: Bearer $token",
					),
				));

				$result = curl_exec($curl);
				$response = json_decode($result);
				$err = curl_error($curl);

				curl_close($curl);
			}
		}catch (Exception $e) {
			var_dump($e);
		}
	}

}