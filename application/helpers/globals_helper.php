<?php 
/**
 * @author Justin
 * @copyright 2016
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Application specific global variables
class Globals
{
    public static function seturl(){
        #return "http://localhost/codeigniter/rest_server";
    }

    public static function getSchoolName(){
        return "St. Therese - MTC Colleges INC.";
    }
    
    public static function getValue(){
        return 50000;
    }

    public static function pf($string){
    	$return = var_dump("<pre>", $string);
    	return $return;
    }

    public static function getBEDDepartments(){
        return array('ELEM','HS','SHS','BED','ACAD');
    }

    public static function getUserAccess(){
      return array("teaching" => "Teaching", "nonteaching" => "Non Teaching", "student" => "Student");
    }

    public static function getBatchEncodeCategory(){
      return array(""=>"Select Category","salary"=>"Salary","deduction"=>"Deduction","income"=>"Income","loan"=>"Loans", "regdeduc"=>"Reglementary Deduction", "regpayment"=>"Reglementary Payment", "prevdata" => "Previous Employer Data");
    }

    public static function documentStatusList(){
        return array("PENDING"=>"PENDING","ON PROCESS"=>"ON PROCESS","APPROVED"=>"APPROVED","DISAPPROVED"=>"DISAPPROVED");
        //return array("PENDING"=>"PENDING","PROCESS"=>"ON PROCESS","APPROVED"=>"APPROVED","DISAPPROVED"=>"DISAPPROVED");
    }

    public static function idxConfig(){
      return array("M" => 1, "T" => 2, "W" => 3, "TH" => 4, "F" => 5, "S" => 6, "SUN" => 7);
    }

    public static function monthList(){
        return array('01' => "January",'02' => "February",'03' => "March",'04' => "April",'05' => "May",'06' => "June",'07' => "July",'08' => "August",'09' => "September",'10' => "October",'11' => "November",'12' => "December");
    }

    public static function monthListIDX(){
        return array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");
    }

    public static function seminarList(){
        return array("PTS_PDP"=>"TA/TMSN SPIRITUAL and SPIRITUAL FORMATION PROGRAM", "PTS_PDP1"=>"PROFESSIONAL DEVELOPMENT PROGRAM", "PTS_PDP2"=>"PEP DEVELOPMENT PROGRAM", "PTS_PDP3"=>"PSYCOSOCIAL - CULTURAL");
    }
    
    public static function approverSeqList($seq){
        $seq = '';
        switch($seq){
            case 1:$seq='1ST APPROVER';break;
            case 2:$seq='2ND APPROVER';break;
            case 3:$seq='3RD APPROVER';break;
            case 4:$seq='4TH APPROVER';break;
            case 5:$seq='5TH APPROVER';break;
            case 6:$seq='6TH APPROVER';break;
            case 7:$seq='7TH APPROVER';break;
            case 8:$seq='8TH APPROVER';break;
            case 9:$seq='9TH APPROVER';break;
            case 10:$seq='10TH APPROVER';break;
        }
        return $seq;
    }

    public static function convertFormDataToArray($formdata){
        $data_arr = array();
        $formdata = explode("&", $formdata);
        foreach($formdata as $row){
            if($row != ''){
                list($key, $value) = explode("=", $row);
                $key = str_replace(';', '', $key);
                if($key != "undefined") $data_arr[$key] = $value;
            }
        }

        return $data_arr;
    }

    public static function convertFormDataToArrayAnnouncement($formdata){
        $data_arr = array();
        $formdata = explode("&TMS-2021&", $formdata);
        foreach($formdata as $row){
            if($row != ''){
                list($key, $value) = explode("==", $row);
                if($key != "undefined") $data_arr[$key] = $value;
            }
        }
        
        return $data_arr;
    }

    //ADDED & MODIFIED BY RYE 10-15-2020
    public static function decryptFormData($loc){
        $toks = $loc->input->post('toks');
        $data = $loc->input->post();
        if($toks){
            unset($data['toks']);
            foreach($data as $key => $val){
                if($key == 'form_data'){
                    unset($data['form_data']);
                    $tmp = Globals::convertFormDataToArray(urldecode($loc->gibberish->decrypt($val, $toks)));
                    foreach ($tmp as $keyy => $vall) {
                        $data[$keyy] = $vall;
                    }
                }
                else{
                    $data[$key] = urldecode($loc->gibberish->decrypt($val, $toks));
                }
            }
        }   
        if (empty($data['employeeid'])) {
            unset($data['employeeid']);
        }
        return $data;
    }

    public static function _e($string){
        // if(!is_array($string)) return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        return $string;
    }



    public static function result_XHEP($query){
        // $data = array();
        // foreach ($query as $key => $value) {
        //     foreach ($value as $keyy => $vall) {
        //         $data[$key]->$keyy = GLOBALS::_e($vall);
        //     }
        // }
        // return $data;
        return $query;
    }

    public static function resultarray_XHEP($query){
        // $data = array();
        // foreach ($query as $key => $value) {
        //     foreach ($value as $keyy => $vall) {
        //         $data[$key][$keyy] = GLOBALS::_e($vall);
        //     }
        // }
        // return $data;
        return $query;
    }

    public static function XHEP($query){
        foreach ($query->row(0) as $key => $value) {
            $data[$key] = GLOBALS::_e($value);
        }
        return $data;
    }

    // ADDED & MODIFIED BY RYE 10-15-2020
    public static function decryptFormData_get($loc){
        $toks = $loc->input->get('toks');
        $data = $loc->input->get();
        if($toks){
            unset($data['toks']);
            foreach($data as $key => $val){
                if($key == 'form_data'){
                    unset($data['form_data']);
                    $tmp = Globals::convertFormDataToArray($loc->gibberish->decrypt(str_replace(' ', '+',$val), $toks));
                    foreach ($tmp as $keyy => $vall) {
                        $data[$keyy] = $vall;
                    }
                }
                else{
                    $data[$key] = urldecode($loc->gibberish->decrypt($val, $toks));
                }
            }
        }   
        if (empty($data['employeeid'])) {
            unset($data['employeeid']);
        }
        return $data;
    }

    public static function customconvertFormDataToArray($formdata){
        $data_arr = array();
        $formdata = explode("&", $formdata);
        $data_arr["sanctions"] = "";
        foreach($formdata as $row){
            if($row != ''){
                list($key, $value) = explode("=", $row);
                if($key != "code" && $key != "desc" && $key != "action"){$data_arr["sanctions"] .= $key."=".$value."/";}
                else{$data_arr[$key] = $value;}
            }
        }
        $data_arr["sanctions"] = substr($data_arr["sanctions"], 0, -1);
        return $data_arr;
    }

    public static function dataRequestApprovalList(){
        return array(
                    // 'employee_family' => "Family Members",
                    // 'employee_emergencyContact' => "Emergency Contact Information",
                    'employee_education'=>"Educational Background",
                    'employee_eligibilities'=>"Government Examinations Taken/Licenses",
                    'employee_language'=>"Languages",
                    'employee_skills'=>"Skills",
                    'employee_pgd'=>"Researches Undertaken",
                    'employee_awardsrecog'=>"Awards|Citations|Recognitions",
                    'employee_scholarship'=>"Group Affiliations",
                    'employee_char_refference'=>"Character References",
                    'employee_resource'=>"Trainings And Seminars",
                    'employee_work_history_related'=> "Employment History",
                    
                    // 'employee_proorg'=>"Membership in Civic Organization",
                    // 'employee_community'=>"Community Involvement",
                    // 'employee_administrative'=>"Position Held in TMS"
                );
    }

    public static function dataRequestApprovalHeaderList(){
        return array(
                    'employee_education'=>array("School", "Address", "Educational Level", "Course", "Degree Earned", "Units Earned", "Inclusive Years/Completed", "Honor"),
                    'employee_eligibilities'=>array("Name of Exam", "License No.", "Date", "Place Taken", "Expiration Date", "Rating"),
                    'employee_language'=>array("Language", "Literacy", "Fluency"),
                    'employee_skills'=>array("Skills", "Years of Use", "Level of Expertise"),
                    'employee_pgd'=>array("Type of Research Work", "Status", "Start / End", "Date Published", "Publication / Journal Name"),
                    'employee_awardsrecog'=>array("Award/Citations", "Granting Agency/Org", "Date", "Place Undertaken "),
                    'employee_scholarship'=>array("Name of Organization", "Office Address", "Position", "Date From", "Date To"),
                    'employee_char_refference'=>array("Name", "Position", "Address", "Contact Number"),
                    'employee_resource'=>array("Date From", "Date To", "Training Name", "Resource Speaker", "Venue", "Type", "Category"),
                    'employee_proorg'=>"Membership in Civic Organization",
                    'employee_community'=>"Community Involvement",
                    'employee_administrative'=>"Position Held in TMS");
    }

    public static function dataRequestApprovalColumnList(){
        return array(
                    'employee_education'=>array("school", "address", "educ_level", "course", "degree", "units", "date_graduated", "honor_received"),
                    'employee_eligibilities'=>array("description", "license_number", "date_issued", "place_undertaken", "date_expired", "remarks"),
                    'employee_language'=>array("language", "literacy", "fluency"),
                    'employee_skills'=>array("skills", "experience", "level"),
                    'employee_pgd'=>array("publication", "type", "datef", "publisher", "title"),
                    'employee_awardsrecog'=>array("award", "institution", "datef", "place_undertaken"),
                    'employee_scholarship'=>array("scholarship", "gr_agency", "prog_study", "datef", "dateto"),
                    'employee_char_refference'=>array("char_name", "position", "address", "contact_number"),
                    'employee_resource'=>array("datef", "datet", "topic", "organizer", "venue", "location", "typedesc"),
                );
    }

    public static function convertMime($mime) {
        $mime_map = array(
            'video/3gpp2'                                                               => '3g2',
            'video/3gp'                                                                 => '3gp',
            'video/3gpp'                                                                => '3gp',
            'application/x-compressed'                                                  => '7zip',
            'audio/x-acc'                                                               => 'aac',
            'audio/ac3'                                                                 => 'ac3',
            'application/postscript'                                                    => 'ai',
            'audio/x-aiff'                                                              => 'aif',
            'audio/aiff'                                                                => 'aif',
            'audio/x-au'                                                                => 'au',
            'video/x-msvideo'                                                           => 'avi',
            'video/msvideo'                                                             => 'avi',
            'video/avi'                                                                 => 'avi',
            'application/x-troff-msvideo'                                               => 'avi',
            'application/macbinary'                                                     => 'bin',
            'application/mac-binary'                                                    => 'bin',
            'application/x-binary'                                                      => 'bin',
            'application/x-macbinary'                                                   => 'bin',
            'image/bmp'                                                                 => 'bmp',
            'image/x-bmp'                                                               => 'bmp',
            'image/x-bitmap'                                                            => 'bmp',
            'image/x-xbitmap'                                                           => 'bmp',
            'image/x-win-bitmap'                                                        => 'bmp',
            'image/x-windows-bmp'                                                       => 'bmp',
            'image/ms-bmp'                                                              => 'bmp',
            'image/x-ms-bmp'                                                            => 'bmp',
            'application/bmp'                                                           => 'bmp',
            'application/x-bmp'                                                         => 'bmp',
            'application/x-win-bitmap'                                                  => 'bmp',
            'application/cdr'                                                           => 'cdr',
            'application/coreldraw'                                                     => 'cdr',
            'application/x-cdr'                                                         => 'cdr',
            'application/x-coreldraw'                                                   => 'cdr',
            'image/cdr'                                                                 => 'cdr',
            'image/x-cdr'                                                               => 'cdr',
            'zz-application/zz-winassoc-cdr'                                            => 'cdr',
            'application/mac-compactpro'                                                => 'cpt',
            'application/pkix-crl'                                                      => 'crl',
            'application/pkcs-crl'                                                      => 'crl',
            'application/x-x509-ca-cert'                                                => 'crt',
            'application/pkix-cert'                                                     => 'crt',
            'text/css'                                                                  => 'css',
            'text/x-comma-separated-values'                                             => 'csv',
            'text/comma-separated-values'                                               => 'csv',
            'application/vnd.msexcel'                                                   => 'csv',
            'application/x-director'                                                    => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/x-dvi'                                                         => 'dvi',
            'message/rfc822'                                                            => 'eml',
            'application/x-msdownload'                                                  => 'exe',
            'video/x-f4v'                                                               => 'f4v',
            'audio/x-flac'                                                              => 'flac',
            'video/x-flv'                                                               => 'flv',
            'image/gif'                                                                 => 'gif',
            'application/gpg-keys'                                                      => 'gpg',
            'application/x-gtar'                                                        => 'gtar',
            'application/x-gzip'                                                        => 'gzip',
            'application/mac-binhex40'                                                  => 'hqx',
            'application/mac-binhex'                                                    => 'hqx',
            'application/x-binhex40'                                                    => 'hqx',
            'application/x-mac-binhex40'                                                => 'hqx',
            'text/html'                                                                 => 'html',
            'image/x-icon'                                                              => 'ico',
            'image/x-ico'                                                               => 'ico',
            'image/vnd.microsoft.icon'                                                  => 'ico',
            'text/calendar'                                                             => 'ics',
            'application/java-archive'                                                  => 'jar',
            'application/x-java-application'                                            => 'jar',
            'application/x-jar'                                                         => 'jar',
            'image/jp2'                                                                 => 'jp2',
            'video/mj2'                                                                 => 'jp2',
            'image/jpx'                                                                 => 'jp2',
            'image/jpm'                                                                 => 'jp2',
            'image/jpeg'                                                                => 'jpeg',
            'image/pjpeg'                                                               => 'jpeg',
            'application/x-javascript'                                                  => 'js',
            'application/json'                                                          => 'json',
            'text/json'                                                                 => 'json',
            'application/vnd.google-earth.kml+xml'                                      => 'kml',
            'application/vnd.google-earth.kmz'                                          => 'kmz',
            'text/x-log'                                                                => 'log',
            'audio/x-m4a'                                                               => 'm4a',
            'application/vnd.mpegurl'                                                   => 'm4u',
            'audio/midi'                                                                => 'mid',
            'application/vnd.mif'                                                       => 'mif',
            'video/quicktime'                                                           => 'mov',
            'video/x-sgi-movie'                                                         => 'movie',
            'audio/mpeg'                                                                => 'mp3',
            'audio/mpg'                                                                 => 'mp3',
            'audio/mpeg3'                                                               => 'mp3',
            'audio/mp3'                                                                 => 'mp3',
            'video/mp4'                                                                 => 'mp4',
            'video/mpeg'                                                                => 'mpeg',
            'application/oda'                                                           => 'oda',
            'audio/ogg'                                                                 => 'ogg',
            'video/ogg'                                                                 => 'ogg',
            'application/ogg'                                                           => 'ogg',
            'application/x-pkcs10'                                                      => 'p10',
            'application/pkcs10'                                                        => 'p10',
            'application/x-pkcs12'                                                      => 'p12',
            'application/x-pkcs7-signature'                                             => 'p7a',
            'application/pkcs7-mime'                                                    => 'p7c',
            'application/x-pkcs7-mime'                                                  => 'p7c',
            'application/x-pkcs7-certreqresp'                                           => 'p7r',
            'application/pkcs7-signature'                                               => 'p7s',
            'application/pdf'                                                           => 'pdf',
            'application/octet-stream'                                                  => 'pdf',
            'application/x-x509-user-cert'                                              => 'pem',
            'application/x-pem-file'                                                    => 'pem',
            'application/pgp'                                                           => 'pgp',
            'application/x-httpd-php'                                                   => 'php',
            'application/php'                                                           => 'php',
            'application/x-php'                                                         => 'php',
            'text/php'                                                                  => 'php',
            'text/x-php'                                                                => 'php',
            'application/x-httpd-php-source'                                            => 'php',
            'image/png'                                                                 => 'png',
            'image/x-png'                                                               => 'png',
            'application/powerpoint'                                                    => 'ppt',
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.ms-office'                                                 => 'ppt',
            'application/msword'                                                        => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop'                                                   => 'psd',
            'image/vnd.adobe.photoshop'                                                 => 'psd',
            'audio/x-realaudio'                                                         => 'ra',
            'audio/x-pn-realaudio'                                                      => 'ram',
            'application/x-rar'                                                         => 'rar',
            'application/rar'                                                           => 'rar',
            'application/x-rar-compressed'                                              => 'rar',
            'audio/x-pn-realaudio-plugin'                                               => 'rpm',
            'application/x-pkcs7'                                                       => 'rsa',
            'text/rtf'                                                                  => 'rtf',
            'text/richtext'                                                             => 'rtx',
            'video/vnd.rn-realvideo'                                                    => 'rv',
            'application/x-stuffit'                                                     => 'sit',
            'application/smil'                                                          => 'smil',
            'text/srt'                                                                  => 'srt',
            'image/svg+xml'                                                             => 'svg',
            'application/x-shockwave-flash'                                             => 'swf',
            'application/x-tar'                                                         => 'tar',
            'application/x-gzip-compressed'                                             => 'tgz',
            'image/tiff'                                                                => 'tiff',
            'text/plain'                                                                => 'txt',
            'text/x-vcard'                                                              => 'vcf',
            'application/videolan'                                                      => 'vlc',
            'text/vtt'                                                                  => 'vtt',
            'audio/x-wav'                                                               => 'wav',
            'audio/wave'                                                                => 'wav',
            'audio/wav'                                                                 => 'wav',
            'application/wbxml'                                                         => 'wbxml',
            'video/webm'                                                                => 'webm',
            'audio/x-ms-wma'                                                            => 'wma',
            'application/wmlc'                                                          => 'wmlc',
            'video/x-ms-wmv'                                                            => 'wmv',
            'video/x-ms-asf'                                                            => 'wmv',
            'application/xhtml+xml'                                                     => 'xhtml',
            'application/excel'                                                         => 'xl',
            'application/msexcel'                                                       => 'xls',
            'application/x-msexcel'                                                     => 'xls',
            'application/x-ms-excel'                                                    => 'xls',
            'application/x-excel'                                                       => 'xls',
            'application/x-dos_ms_excel'                                                => 'xls',
            'application/xls'                                                           => 'xls',
            'application/x-xls'                                                         => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.ms-excel'                                                  => 'xlsx',
            'application/xml'                                                           => 'xml',
            'text/xml'                                                                  => 'xml',
            'text/xsl'                                                                  => 'xsl',
            'application/xspf+xml'                                                      => 'xspf',
            'application/x-compress'                                                    => 'z',
            'application/x-zip'                                                         => 'zip',
            'application/zip'                                                           => 'zip',
            'application/x-zip-compressed'                                              => 'zip',
            'application/s-compressed'                                                  => 'zip',
            'multipart/x-zip'                                                           => 'zip',
            'text/x-scriptzsh'                                                          => 'zsh',
        );

        return isset($mime_map[$mime]) === true ? $mime_map[$mime] : false;
    }

    public static function applicantForm(){
        return array("applicant_education", "applicant_eligibilities", "applicant_subj_competent_to_teach", "applicant_credentials", "applicant_workshops");
    }

    public static function pd($data, $isdump=false){
		if($isdump){
			echo "<pre>"; var_dump($data);
		}else{
			echo "<pre>"; print_r($data);
		}
	}

    public static function aimsAPIUrl(){
        if(getenv('ENVIRONMENT') == 'Office'){
            return "https://training4.pinnacle.com.ph/sttherese/";
         }
         else if(getenv('ENVIRONMENT') == 'Development'){
            return "https://training4.pinnacle.com.ph/sttherese/";
         }
         else if(getenv('ENVIRONMENT') == 'Staging'){
            return "https://training4.pinnacle.com.ph/sttherese/";
         }
         else if(getenv('ENVIRONMENT') == 'Production'){
             if($_SERVER['SERVER_NAME'] == 'lafiesta-sttheresehris.pinnacle.edu.ph'){
                return "https://lafiesta.stmtcc.edu.ph/lafiesta/";
             }
             else if($_SERVER['SERVER_NAME'] == 'magdalo-sttheresehris.pinnacle.edu.ph'){
                return "https://magdalo.stmtcc.edu.ph/magdalo/";
             }
             else if($_SERVER['SERVER_NAME'] == 'tigbauan-sttheresehris.pinnacle.edu.ph'){
                return "https://tigbauan.stmtcc.edu.ph/tigbauan/";
             }
         }
    }

    public static function accessToken(){
        $curl_uri = Globals::aimsAPIUrl();
        $result = "";
        $form_data = array(
            "client_id" => "HRIS",
            "client_secret" => "biVEdrFmiY3TKgLmkRFbUW6f6jl1Sw3svuy",
            "username" => "hris",
            "password" => "sttherese2024**"
        );
        ini_set('display_errors',1);
        error_reporting(-1);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $curl_uri."api/aims_token.php");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1 );
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($form_data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept"=>"application/json"));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
  
        if($httpCode == 404) {
            return;
        }
        else{
            $response = json_decode($response, true);
            return isset($response["access_token"]) ? $response["access_token"] : ""; 
        }
        
    }

    public static function convertFormDataToArrayMultiple($formdata){
        
        $data_arr = array();
        $formdata = explode("&", $formdata);

                
        foreach($formdata as $row){
            if($row != ''){
                list($key, $value) = explode("=", $row);
                $key = str_replace(';', '', $key);
                // echo "<pre>";print_r($key);
                if(strpos($key, '[]') !== false){
                    $key = str_replace('[]', '', $key);
                    $data_arr[$key][] = $value;
                }elseif(strpos($key, '%5B%5D') !== false){
                    $key = str_replace('%5B%5D', '', $key);
                    $data_arr[$key][] = $value;
                }
                elseif($key != "undefined") {
                    $data_arr[$key] = $value;
                }
            }
        }
        return $data_arr;
    }

    public static function gsuite_info(){
        return array("employeeid", "positionid", "teachingtype", "deptid", "campusid", "mobile", "landline", "addr", "personal_email");
    }

    public static function loan_type_list(){
        /*return array(
            "institutional" => "Institutional Loan",
            "sss" => "SSS Loan",
            "pagibig" => "PAG-IBIG Loan",
            "ca" => "Cash Advanced"
        );*/

        return array(
            "statutory" => "Statutory Loans",
            "institutional" => "Institutional Loan"
        );
    }

    public static function libraryAPIUrl(){
        $curl_uri = "";
        if($_SERVER["HTTP_HOST"] == "192.168.2.32") $curl_uri = "http://192.168.2.92/cgi-bin/koha/library/index.php/";
        return $curl_uri;
    }

    public static function aimsdept_type(){
        return array("undergrad" => "Under Graduate", "grad" => "Graduate", "nstp" => "NSTP");
    }

    public static function aims_info(){
        return array(
            "lname" => "lastname", 
            "fname" => "firstname", 
            "mname" => "middlename", 
            "suffix" => "suffix", 
            "bdate" => "birthdate", 
            "email" => "email", 
            "campusid" => "campus_code", 
            "title" => "faculty_title", 
            "teachingtype" => "employee_type", 
            "employment_status" => "employee_status", 
            "designation" => "designation", 
            "date_hired" => "date_hired", 
            "deptid" => "department", 
            "position" => "position", 
            "office" => "office", 
            "parent_course" => "parent_course", 
            "date_permanent_status" => "date_permanent_status",  
            "employment_classification" => "employment_classification", 
            "mobile" => "mobile_no", 
            "years_of_exp_urs" => "years_of_exp_urs", 
            "date_employment" => "employment_date",  
            "status" => "status", 
            "landline" => "tel_no", 
            "addr" => "address", 
            "bplace" => "birth_place", 
            "gender" => "gender", 
            "citizenship" => "citizenship", 
            "religionid" => "religion", 
            "civil_status" => "civil_status", 
            "husband_wife" => "husband_wife", 
            "husband_wife_occupation" => "husband_wife_occupation",
            "husband_wife_address" => "husband_wife_address",
            "fathers_name" => "fathers_name", 
            "fathers_occupation" => "fathers_occupation", 
            "mothers_name" => "mothers_name", 
            "mothers_occupation" => "mothers_occupation", 
            "parent_address" => "parent_address", 
            "emp_sss" => "parent_sss", 
            "emp_philhealth" => "parent_philhealth", 
            "emp_pagibig" => "parent_pagibig", 
            "emp_tin" => "other_tin" 
        );
    }
    
    // LOLA 11-18-2022
    public static function headerPdf($CAMPUSID="",$REPORT_TITLE="",$DATERANGE="",$SIZE=false)
    {   
        $content ='';
        $campusDisplay = $CAMPUSID;
        $campusEmail = $CAMPUSID;
        $content .= "

        <htmlpageheader name='Header'>
            <div>
                <table width='100%' border=0>
                    <tr>
                        <td rowspan='3' width='".($SIZE ? "25%":"25%")."' style='text-align: right;'></td>
                        <td colspan='1' style='text-align: center;font-size: 10px;'></td>
                        <td rowspan='3' style='width='".($SIZE ? "25%":"25%")."''>&nbsp;</td>
                    </tr>
                    <tr>
                        <td rowpan='2' id='title-pdf' valign='middle' style='padding: 0;text-align: center;color:black;' width='".($SIZE ? "50%":"50%")."'><span style='font-size: 18px; font-weight: normal;'><img  src='images/school_logo_with_desc.png' style='width: ".($SIZE ? "47":"50")."%;text-align: center;' /></span></td>
                    </tr>
                    <tr>
                        <td valign='middle' style='padding: 2px;font-size: 10px;text-align: center; margin-left:100px;'></td>
                    </tr>
                    <tr>
                        <td colspan='3' valign='middle' style='padding: 0;font-size: 13px;text-align: center; margin-left:100px;font-weight:bold;'>".strtoupper($REPORT_TITLE)."</td>
                    </tr>
                    <tr>
                        <td colspan='3' valign='middle' style='padding: 0;font-size: 13px;text-align: center; margin-left:100px;font-weight:bold;'>".strtoupper($DATERANGE)."</td>
                    </tr>
                </table>
            </div>
        </htmlpageheader>
        <br><br>
        ";
        return $content;
    }

    public static function footerpdf($SIZEFOOTER = true, $LARGEMODE = false, $showFooter = false) {
        if (!$showFooter) {
            return '';
        }
        $content ='';
        $content.= "
            <htmlpagefooter name='Footer'>
                <div>
                    <table width='100%' border=0 class='footer-design'>
                        <tr>
                            <td colspan='4' align='middle' style='padding: 0;font-size: 10px;text-align: center; margin-left:100px;font-weight:bold;'>&nbsp;</td>
                        </tr>
                        <tr style='background:white;'>
                            <td rowspan='1' width='".($SIZEFOOTER ? ($LARGEMODE ? "25%" : "20%"):"23%")."' style='text-align: right;'><img src='".base_url()."/images/isoMontessori.png' style='width: ".($SIZEFOOTER ? "15":"20")."%;text-align: center;' /></td>
                            <td colspan='1' valign='top' style='text-align: left;font-size: 10px;'>
                                &nbsp;&nbsp;<b style='font-size: ".($SIZEFOOTER ? "18":"15")."px;'>ISO 9001:2015 CERTIFIED</b><br>&nbsp;&nbsp;<span style='font-size: ".($SIZEFOOTER ? "15":"11")."px;'>BY TUV RHEINLAND PHILIPPINES, INC.</span>
                            </td>
                            <td rowspan='1' valign='top' width='".($SIZEFOOTER ? "20%":"23%")."' style='text-align: right;'><img src='".base_url()."/images/peac_img.png' style='width: ".($SIZEFOOTER ? "15":"20")."%;text-align: center;' /></td>
                            <td colspan='1' valign='top' style='text-align: left;font-size: 10px;'>
                                &nbsp;&nbsp;<b style='font-size: ".($SIZEFOOTER ? "18":"15")."px;'>ACCREDITED SCHOOL</b><br>&nbsp;&nbsp;<span style='font-size: ".($SIZEFOOTER ? "15":"11")."px;'>FOR K-12 BEd & STE CURRICULA</span>
                            </td>
                        </tr>
                        
                        <tr>
                            <td valign='middle' style='padding: 0;font-size: 10px;text-align: center;font-weight:bold;'>&nbsp;</td>
                            <td colspan='2' valign='middle' style='text-shadow: 1px 1px #FF0000;color:white;padding-bottom: 5px;font-size: 12px;text-align: center;font-weight:bold;'>
                                ".($SIZEFOOTER ? " &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp;" : "&nbsp;&nbsp; &nbsp;&nbsp;")."
                                La Puerta del Sol, Hi-Land Subdivision, Maliwalo, Tarlac City 
                                <br> 
                                ".($SIZEFOOTER ? " &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp;" : "&nbsp;&nbsp; &nbsp;&nbsp;")."
                                Tel: (045) 800 6566 Email: tarlac_montessori@yahoo.com.ph</td>
                            <td valign='middle' style='padding: 0;font-size: 10px;text-align: right;font-weight:bold;color:white;text-shadow: 1px 1px #FF0000;'>
                                <div class='footer'>
                                    Page : {PAGENO} of {nb}  &nbsp;&nbsp;&nbsp;&nbsp;
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </htmlpagefooter>
        ";

        return $content;
    }


    //STMTCCHYP-281
    public static function footerWithCnPdf($control_no) {
        $content ='';
        $content.= "
        <htmlpagefooter name='Footer'>
            <div>
                <table width='100%'>
                    <tr>
                        <td style='text-align: right;font-size: 13px;padding:20px;' >
                        <i>
                        ".$control_no."
                        </i>
                        </td>
                    </tr>
                </table>
            </div>
        </htmlpagefooter>
        ";
        return $content;
    }

    public static function displayDateRange($dfrom = "",$dto = "",$hasSun=true){
        $day = array("SU","MO","TU","WE","TH","FR","SA");
        $months = array("1" => "Jan", "2" => "Feb", "3" => "Mar", "4" => "Apr", "5" => "May", "6" => "Jun", "7" => "Jul", "8" => "Aug", "9" => "Sept", "10" => "Oct", "11" => "Nov", "12" => "Dec");
        $date_list = array();
        $period = new DatePeriod(
            new DateTime($dfrom),
            new DateInterval('P1D'),
            new DateTime($dto." +1 day")
        );
        foreach ($period as $key => $value) {
            if($hasSun){
                $date_list[$key] = array();
                $date_list[$key] = (object) $date_list[$key];
                $date_list[$key]->dte = $value->format('Y-m-d');  
                $date_list[$key]->week = $day[$value->format('w')];
                $date_list[$key]->monthday = $months[$value->format('n')]."-".$value->format('d'); 
            }else{
                if($day[$value->format('w')] != "SU"){
                    $date_list[$key] = array();
                    $date_list[$key] = (object) $date_list[$key];
                    $date_list[$key]->dte = $value->format('Y-m-d');  
                    $date_list[$key]->week = $day[$value->format('w')];
                    $date_list[$key]->monthday = $months[$value->format('n')]."-".$value->format('d'); 
                }
            }
        }
        
        return $date_list;
    }

    public static function athenaDatabase(){
        if($_SERVER["HTTP_HOST"] == "192.168.2.32" || $_SERVER["HTTP_HOST"] == "localhost:8080"){
              return "Athena_TMS";
        }
        else if($_SERVER["HTTP_HOST"] == "" && strpos($_SERVER["REQUEST_URI"], 'hristrng') !== false){
              return "AthenaTrng_TMS";
        }
        else if($_SERVER["HTTP_HOST"] == "" && strpos($_SERVER["REQUEST_URI"], 'hris') !== false){
              return "Athena_TMS"; 
        }
   }
   
    public static function semesterConfig($val = ""){
        $semester_arr = array(1 => "First Semester", 2 => "Second Semester");
        if($val) return $semester_arr[$val];
        else return $semester_arr;
    }

    public static function getSchoolYears() {
        $currentYear = date("Y");
        $schoolYears = [];
    
        // Include the previous year as well, so start from $currentYear - 1
        for ($i = -1; $i < 4; $i++) { // Change the loop range to include the last year
            $year = $currentYear + $i;
            $nextYear = $year + 1;
            $val = sprintf('%02d%02d', $year % 100, $nextYear % 100);
            $schoolYears[$val] = $val;
        }
    
        return $schoolYears;
    }

    public static function campusDatabase($campus_id=""){
        $db_list = array(
            "LF" => "HRIS_STTHRESE_LA_FIESTA",
            "MD" => "HRIS_STTHRESE_MAGDALO",
            "TG" => "HRIS_STTHRESE_TIGBAUAN"
        );

        if($campus_id){
            return $db_list[$campus_id];
        }else{
            return $db_list;
        }
    }

    public static function sites($site_id=""){
        $site_list = array(
            "LF" => "La Fiesta",
            "MD" => "Magdalo",
            "TG" => "Tigbauan"
        );

        if($site_id){
            return $site_list[$site_id];
        }else{
            return $site_list;
        }
    }

    // Temporary method for getting endpoint
    // To be moved in env file
    public static function campusEndpoints($campus_id=""){
        $db_list = array(
            "LF" => "https://lafiesta-sttheresehris.pinnacle.edu.ph/index.php/",
            "MD" => "https://magdalo-sttheresehris.pinnacle.edu.ph/index.php/",
            "TG" => "https://tigbauan-sttheresehris.pinnacle.edu.ph/index.php/"
        );

        if($campus_id){
            return $db_list[$campus_id];
        }else{
            return false;
        }
    }

    public static function currentSY(){
        $currentYear = date("Y");
        $year = $currentYear;
        $nextYear = $year + 1;
        $val = sprintf('%02d%02d', $year % 100, $nextYear % 100);
        return $val;
    }

    public static function hrisAccessToken($curl_uri){
        $result = "";
        $form_data = array(
            "client_secret" => "N2ZhOWUyYmIwY2RjM2UzYmI4ZGFiZjkY2M1N2E4OGUzZmJh=",
            "username" => "hyperion",
            "password" => "@stmtccHyperion2024"
        );
        ini_set('display_errors',1);
        error_reporting(-1);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $curl_uri."Api_/hyperion_token");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1 );
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($form_data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept"=>"application/json"));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
  
        if($httpCode == 404) {
            return;
        }
        else{
            $response = json_decode($response, true);
            return isset($response["access_token"]) ? $response["access_token"] : ""; 
        }
    }

    public static function is_super_admin($user){
        $list = array(
            "maricris.dumaran",
            "clodin.fuerte",
            "hazel.cruz"
        );

        return in_array($user, $list);
    }

}