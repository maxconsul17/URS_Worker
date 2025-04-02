<?php 
/**
 * @author Justin
 * @copyright 2016
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Application specific global variables
class Globals
{

    public static function getSchoolName(){
        return "UNIVERSITY OF RIZAL SYSTEM";
    }

    public static function seturl(){
        #return "http://localhost/codeigniter/rest_server";
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
      return array("teaching" => "Teaching", "nonteaching" => "Non-Teaching", "student" => "Student");
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

    public static function monthDescList(){
        return array("1"=>"January", "01"=>"January",  "2"=>"February", "02"=>"February", "3"=>"March", "03"=>"March",  "4"=>"April", "04"=>"April", "5"=>"May", "05"=>"May", "6"=>"June", "06"=>"June",  "7"=>"July", "07"=>"July", "8"=>"August", "08"=>"August", "9"=>"September", "09"=>"September", "10"=>"October", "11"=>"November", "12"=>"December");
    }

    public static function seminarList(){
        return array("PTS_PDP"=>"TA/URS SPIRITUAL and SPIRITUAL FORMATION PROGRAM", "PTS_PDP1"=>"PROFESSIONAL DEVELOPMENT PROGRAM", "PTS_PDP2"=>"PEP DEVELOPMENT PROGRAM", "PTS_PDP3"=>"PSYCOSOCIAL - CULTURAL");
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
        $formdata = explode("&URS-2021&", $formdata);
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

    public static function _array_XHEP($array){
        $data = array();
        foreach($array as $key => $val){
            $data[$key] = GLOBALS::_e($val);
        }

        return $data;
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

    public static function govtid_fields(){
        return  array('Passport #' => "passport", 
                      'PRC #' => "prc", 
                      'Driver license #' => "driver_license", 
                      'Passport # - Expiration Date' => "passport_expiration", 
                      'PRC # - Expiration Date' => "prc_expiration", 
                      'Driver license # - Expiration Date' => "driver_license_expiration", 
                      'TIN #' => "emp_tin",
                      'PAG-IBIG' => "emp_pagibig", 
                      'Type of License' => "driver_license_type", 
                      'PhilHealth' => "emp_philhealth", 
                      'SSS #' => "emp_sss", 
                      'HMO #' => "emp_hmo", 
                      'PERAA' => "emp_peraa", 
                      'GSIS' => "emp_gsis");
    }

    public static function personalrecord_fields(){
        return  array("Date of Birth" => "bdate", 
                      "Age" =>  "age", 
                      "Place of Birth" =>  "bplace", 
                      "Blood Type" => "blood_type", 
                      "Height (m)" =>  "height", 
                      "Weight (kg)" =>  "weight", 
                      "Sex" =>  "gender", 
                      "Nationality" => "nationalityid", 
                      "Religion" =>  "religionid", 
                      "Civil Status" =>  "civil_status", 
                      "Citizenship" =>  "citizenid", 
                      "Personal Email" =>  "personal_email", 
                      "Mobile Number" => "mobile", 
                      "Telephone Number" => "landline", 
                      "URS Email" =>  "email", 
                      "Father's Name" =>  "father", 
                      "Father's Date of Birth" =>  "fatherbdate", 
                      "Father's Occupation" =>  "fatheroccu", 
                      "Father's Living Status" =>  "fatherstat", 
                      "Mother's Name" => "mother", 
                      "Mother's Date of Birth" =>  "motherbdate", 
                      "Mother's Occupation" =>  "motheroccu", 
                      "Mother's Living Status" =>  "motherstat", 
                      "Spouse's Employed/Not employed" =>  "checkboxspouse", 
                      "Spouse's Last Name" =>  "spouse_lname", 
                      "Spouse's First Name" =>  "spouse_fname", 
                      "Spouse's Middle Name" =>  "spouse_mname", 
                      "Spouse's Extension" =>  "spouse_extension", 
                      "Spouse's Date of Birth" =>  "spouse_bdate", 
                      "Spouse's Business Address:" =>  "spouse_Address", 
                      "Spouse's Contact Number" => "spouse_contact", 
                      "Spouse's Employer/ Business Name:" => "spouse_company", 
                      "Spouse's Job Position" =>  "spouse_job", 
                      "Current Address: Region" =>  "regionaladdr", 
                      "Current Address: Province" =>  "provaddr", 
                      "Current Address: City/Municipality" =>  "cityaddr", 
                      "Current Address: House #" => "addr", 
                      "Current Address: Street" =>  "street", 
                      "Current Address: Barangay" =>  "barangay", 
                      "Current Address: Subdivision/Village" =>  "subvil", 
                      "Current Address: House/Block/Lot No." =>  "house", 
                      "Current Address: Zip code" =>  "zip_code", 
                      "Permanent Address: Region" => "permaRegion", 
                      "Permanent Address: Province" =>  "permaProvince", 
                      "Permanent Address: City/Municipality" =>  "permaMunicipality", 
                      "Permanent Address: House #" =>  "permaAddress", 
                      "Permanent Address: Barangay" =>  "permaBarangay",
                      "Permanent Address: Subdivision/Village" =>  "permasubvil",
                      "Permanent Address: Street" =>  "permastreet",
                      "Permanent Address: House/Block/Lot No." =>  "permahouse", 
                      "Permanent Address: Zip code" =>  "permaZipcode");

        
    }

    public static function dataRequestApprovalList(){
        return array(
                    // 'employee_emergencyContact' => "Emergency Contact Information",
                    'employee_personal_record'=>"Personal Record",
                    'employee_govtid'=>"Government ID's",
                    'employee_education'=>"Educational Background",
                    'employee_eligibilities'=>"Government Examinations Taken/Licenses",
                    'employee_family' => "Immediate Family Members",
                    'employee_work_history_related' => "Employment History",
                    'employee_language'=>"Languages",
                    'employee_skills'=>"Skills",
                    // 'employee_pgd'=>"Researches Undertaken",
                    'employee_awardsrecog'=>"Awards|Citations|Recognitions",
                    // 'employee_scholarship'=>"Group Affiliations",
                    // 'employee_char_refference'=>"Character References",
                    // 'employee_resource'=>"Trainings And Seminars",
                    // 'employee_proorg'=>"Membership in Association/Organization",
                    'employee_membership'=>"Membership in Association/Organization",
                    // 'employee_community'=>"Community Involvement",
                    // 'employee_administrative'=>"Position Held in OLFU"
                );
    }

    public static function dataRequestApprovalHeaderList(){
        return array(
                    'employee_education'=>array("School", "Address", "Educational Level", "Course", "Degree Earned", "Units Earned", "Year Graduated", "Honor"),
                    'employee_eligibilities'=>array("Name of Exam", "License No.", "Date", "Place Taken", "Expiration Date", "Rating"),
                    'employee_language'=>array("Language", "Literacy", "Fluency"),
                    'employee_skills'=>array("Skills", "Years of Use", "Level of Expertise"),
                    'employee_pgd'=>array("Type of Research Work", "Status", "Start / End", "Date Published", "Publication / Journal Name"),
                    'employee_awardsrecog'=>array("Award/Citations", "Granting Agency/Org", "Date", "Place Undertaken "),
                    'employee_scholarship'=>array("Name of Organization", "Office Address", "Position", "Date From", "Date To"),
                    'employee_char_refference'=>array("Name", "Position", "Address", "Contact Number"),
                    'employee_resource'=>array("Date From", "Date To", "Traiddning Name", "Resource Speaker", "Venue", "Type", "Category"),
                    'employee_proorg'=>"Membership in Civic Organization",
                    'employee_community'=>"Community Involvement",
                    'employee_administrative'=>"Position Held in URS");
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
        $curl_uri = "";
        if($_SERVER["HTTP_HOST"] == "192.168.2.32") $curl_uri = "http://192.168.2.97/urs/";
        else if($_SERVER["HTTP_HOST"] == "urshr.pinnacle.com.ph") $curl_uri = "https://ursims.pinnacle.com.ph/aims/";
        return $curl_uri;
    }


    public static function accessToken(){
      $curl_uri = "";
      if($_SERVER["HTTP_HOST"] == "192.168.2.32") $curl_uri = "http://192.168.2.97/urs/";
      $result = "";
      $form_data = array(
          "client_id" => "URSHRIS",
          "client_secret" => "RFu2l1sxzcsDUe36SjCIIDPn7bWwL8UDNp",
          "username" => "ursaims",
          "password" => "!auth"
      );
      ini_set('display_errors',1);
      error_reporting(-1);
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $curl_uri."api/aims_token.php");
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_POST, 1 );
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $form_data);
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
          $CI = & get_instance();
          $response["userid"] = $CI->session->userdata("username");
          $CI->db->insert("aims_token", $response);
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
                
                if(strpos($key, '[]') !== false){
                    $key = str_replace('[]', '', $key);
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
        return array(
            "institutional" => "Institutional Loan",
            "gsis" => "GSIS Loan",
            "pagibig" => "PAG-IBIG Loan",
            "ca" => "Cash Advanced"
        );
    }

    public static function libraryAPIUrl(){
        $curl_uri = "";
        if($_SERVER["HTTP_HOST"] == "192.168.2.32") $curl_uri = "http://192.168.2.92/cgi-bin/koha/library/index.php/";
        return $curl_uri;
    }

    public static function libraryAccessToken(){
        $curl_uri = "";
        if($_SERVER["HTTP_HOST"] == "192.168.2.32") $curl_uri = "http://192.168.2.92/cgi-bin/koha/library/index.php/";

        $result = "";
        $form_data = array(
            "grant_type" => "client_credentials",
            "username" => "pinnacle",
            "password" => "olfuphoenix",
            "client_secret" => "HIKT1G/DfmqHadXjnGVTIQ"
        );
        ini_set('display_errors',1);
        error_reporting(-1);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $curl_uri."integration_/generateAccessToken");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1 );
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($form_data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept"=>"application/json"));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $token_data = json_decode($response);
        return isset($token_data->access_token) ? $token_data->access_token : "";

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

    public static function aims_info_field_desc(){
        return array(
            "lname" => "Last Name", 
            "fname" => "First Name", 
            "mname" => "Middle Name", 
            "suffix" => "Suffix", 
            "bdate" => "Birth Date", 
            "email" => "Email", 
            "campusid" => "Campus", 
            "title" => "Faculty Title", 
            "teachingtype" => "Employee Type", 
            "employment_status" => "Employee Status", 
            "designation" => "Designaction", 
            "date_hired" => "Date Hired", 
            "deptid" => "Department", 
            "position" => "Position", 
            "office" => "Office", 
            "parent_course" => "Parent Course", 
            "date_permanent_status" => "Date Permanent Status",  
            "employment_classification" => "Employment Classification", 
            "mobile_no" => "Mobile Number", 
            "years_of_exp_urs" => "Year of Exp. in URS", 
            "date_employment" => "Employment Date",  
            "status" => "status", 
            "landline" => "Telephone No.", 
            "addr" => "Address", 
            "bplace" => "Birth Place", 
            "gender" => "Gender", 
            "citizenship" => "Citizenship", 
            "religion" => "Religion", 
            "civil_status" => "Civil Status", 
            "husband_wife" => "Spouse Name", 
            "fathers_name" => "Father Name", 
            "fathers_occupation" => "Father Occupation", 
            "mothers_name" => "Mother Name", 
            "mothers_occupation" => "Mother Occupation", 
            "parent_address" => "Parent Address", 
            "emp_sss" => "SSS", 
            "emp_philhealth" => "PHILHEALTH", 
            "emp_pagibig" => "PAG-IBIG" 
        );
    }

    public static function excel_header_bmp($campusid){
      switch ($campusid) {
            case 'ANG':
                $bmp = "images/excel_header_angono.bmp";
                break;
            case 'ANT':
                $bmp = "images/excel_header_antipolo.bmp";
                break;
            case 'BIN':
                $bmp = "images/excel_header_binangonan.bmp";
                break;
            case 'CAR':
                $bmp = "images/excel_header_cardona.bmp";
                break;
            case 'CNT':
                $bmp = "images/excel_header_cainta.bmp";
                break;
            case 'MOR':
                $bmp = "images/excel_header_morong.bmp";
                break;
            case 'MRG':
                $bmp = "images/excel_header_morong.bmp";
                break;
            case 'PIL':
                $bmp = "images/excel_header_pililla.bmp";
                break;
            case 'ROD':
                $bmp = "images/excel_header_rodriguez.bmp";
                break;
            case 'TAY':
                $bmp = "images/excel_header_taytay.bmp";
                break;
            case 'TNY':
                $bmp = "images/excel_header_tanay.bmp";
                break;
            default:
                $bmp = "images/excel_header_all_campus.bmp";
                break;
        }

        return $bmp;
    }
    
    // URSLOLA 3-05-2023
    public static function headerPdf($CAMPUSID="",$REPORT_TITLE="",$DATERANGE="",$SIZE=false)
    {   
        $content ='';
        $campusDisplay = Globals::checkCompanyCampus($CAMPUSID);
        $campusEmail = Globals::checkCampusEmail($CAMPUSID);
        $content .= "        

        <htmlpageheader name='Header'>
            <div>
                <table width='100%'>
                    <tr>
                        <td rowspan='5' width='".($SIZE ? "25%":"35%")."' style='text-align: right;'><img src='images/school_logo.png' style='width: 45px;text-align: center;' /></td>
                        <td colspan='1' style='text-align: center;font-size: 10px;'>Republic of the Philippines</td>
                        <td rowspan='5' style='width='".($SIZE ? "25%":"35%")."''><img src='images/ursiso.jpg' style='width: 120px;text-align: center;' /></td>
                    </tr>
                    <tr>
                        <td id='title-pdf' valign='middle' style='padding: 0;text-align: center;color:black;' width='".($SIZE ? "50%":"30%")."'><span style='font-size: 18px; font-weight: normal;'>".Globals::getSchoolName()."</span></td>
                    </tr>
                    <tr>
                        <td  valign='middle' style='padding: 0;text-align: center;color:black;'><span style='font-size: 10px; font-family: Arial, Helvetica, sans-serif;' width='".($SIZE ? "50%":"30%")."'>Province of Rizal</span></td>
                    </tr>
                    <tr>
                        <td  valign='middle' style='padding: 0;text-align: center;color:black;'><span style='font-size: 10px; font-family: Arial, Helvetica, sans-serif;' width='".($SIZE ? "50%":"30%")."'>www.urs.edu.ph</span></td>
                    </tr>
                    <tr>
                        <td valign='middle' style='padding: 2px;font-size: 10px;text-align: center; margin-left:100px;'></td>
                    </tr>
                    <tr>
                        <td colspan='3' valign='middle' style='padding: 0;font-size: 10px;text-align: center; margin-left:100px;'>Email Address : ursmain@urs.edu.ph / urs.opmorong@gmail.com</td>
                    </tr>
                    <tr>
                        <td colspan='3' valign='middle' style='padding: 0;font-size: 10px;text-align: center; margin-left:100px;'>Main Campus : URS Tanay Tel. (02) 8401-4900; 8401-4910; 8401-4911; 8539-9957 to 58</td>
                    </tr>
                    
                </table>
                <table width='100%' >
                    <tr>
                        <td valign='middle' style='padding: 0;text-align: center; margin-left:100px;border:1px solid black;'></td>
                    </tr>
                </table>
                <table width='100%' >
                    <tr>
                        <td valign='middle' style='padding: 0;text-align: center; margin-left:100px;font-weight:bold;color:#0070C0;'><i>Human Resource Management Unit - ".$campusDisplay." Campus</i></td>
                    </tr>
                    <tr>
                        <td valign='middle' style='padding: 0;text-align: center; margin-left:100px;font-weight:bold;color:#0070C0;'>Tel. No. (02) 8542-1095 loc. 203 Email Address : ".$campusEmail."</td>
                    </tr>
                    <tr>
                        <td valign='middle' style='padding: 0;text-align: center; margin-left:100px;font-weight:bold;'>".$REPORT_TITLE."".(($DATERANGE) ? " : ".$DATERANGE : "")."</td>
                    </tr>
                </table>
            </div>
        </htmlpageheader>

        ";
        return $content;
    }

    public static function footerpdf(){
        $content ='';
        $content.= "
            <htmlpagefooter name='Footer'>
                <table width='100%' class='footer'>
                    <tr>
                        <td valign='middle' style='padding: 0;text-align: center; margin-left:100px;font-weight:bold;font-family: CenturyGothic; font-size:20px; '><i>Nurturing Tomorrow's Noblest</i></td>
                    </tr>
                </table>
                <table width='100%'  border=1 class='footer'>
                    <tr>
                        <td valign='middle' style='padding: 0;text-align: center; font-weight:bold;'></td>
                    </tr>
                </table>
                <table width='100%' class='footer' style='margin-top:5px;'>
                    <tr>
                        <td rowspan='3' width='18%' valign='middle' style='padding: 0;text-align: center; margin-left:100px;font-weight:bold;'>&nbsp;</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>URS Angono</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>Tel. 8539-9930 to 31</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>URS Cainta</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>Tel. 8539-9938 to 39</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>URS Pillila</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>Tel. 8539-9942 to 44</td>
                        <td rowspan='3' width='15%' valign='middle' style='padding: 0;text-align: right; margin-left:100px;font-weight:bold;'>Page : {PAGENO} of {nb}</td>
                    </tr>
                    <tr>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>URS Antipolo</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>Tel. 8539-9932 to 34</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>URS Cardona</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>Tel. 8539-9940 to 41</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>URS Rodriguez</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>Tel. 8539-9945 to 47</td>
                    </tr>
                    <tr>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>URS Binangonan</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>Tel. 8539-9935 to 37</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>URS Morong</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>Tel. 8539-9950 to 56</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>URS Taytay</td>
                        <td valign='middle' style='font-size: 8px;padding: 0;text-align: left; margin-left:100px;font-weight:bold;'>Tel. 8539-9948 to 49</td>
                    </tr>
                </table>
            </htmlpagefooter>
        ";

        return $content;
    }

    
    public static function checkCompanyCampus($selected_campus=""){
       
        $getCamp = $selected_campus;
        $returnCompanyCampus = "";
        switch ($getCamp) {
            case 'ANG':
                $returnCompanyCampus = "URS "."ANGONO";
                break;
            case 'ANT':
                $returnCompanyCampus = "URS "."ANTIPOLO";
                break;
            case 'BIN':
                $returnCompanyCampus = "URS "."BINANGONAN";
                break;
            case 'CAR':
                $returnCompanyCampus = "URS "."CARDONA";
                break;
            case 'CNT':
                $returnCompanyCampus = "URS "."CAINTA";
                break;
            case 'MOR':
                $returnCompanyCampus = "URS "."MORONG";
                break;
            case 'PIL':
                $returnCompanyCampus = "URS "."PILILLA";
                break;
            case 'ROD':
                $returnCompanyCampus = "URS "."RODRIGUEZ";
                break;
            case 'TAY':
                $returnCompanyCampus = "URS "."TAYTAY";
                break;
            case 'TNY':
                $returnCompanyCampus = "URS "."TANAY";
                break;
            case 'AGO':
                $returnCompanyCampus = "URS "."ANGONO";
                break;
            default:
            $returnCompanyCampus = "URS (ALL CAMPUS)";
                break;
        }

        return $returnCompanyCampus;
    }

    public static function checkCampusEmail($selected_campus=""){
        $getCamp = $selected_campus;
        $returnCompanyCampus = "";
        switch ($getCamp) {
            case 'ANG':
                $returnCompanyCampus = "hrmo.angono@urs.edu.ph";
                break;
            case 'ANT':
                $returnCompanyCampus = "hrmo.antipolo@urs.edu.ph";
                break;
            case 'BIN':
                $returnCompanyCampus = "hrmo.binangonan@urs.edu.ph";
                break;
            case 'CAR':
                $returnCompanyCampus = "hrmo.cardona@urs.edu.ph";
                break;
            case 'CNT':
                $returnCompanyCampus = "hrmo.cainta@urs.edu.ph";
                break;
            case 'MOR':
                $returnCompanyCampus = "hrmo.morong@urs.edu.ph";
                break;
            case 'PIL':
                $returnCompanyCampus = "hrmopililla@urs.edu.ph";
                break;
            case 'ROD':
                $returnCompanyCampus = "hrmo.rodriguez@urs.edu.ph";
                break;
            case 'TAY':
                $returnCompanyCampus = "hrmo.taytay@urs.edu.ph";
                break;
            case 'TNY':
                $returnCompanyCampus = "hrmo.tanay@urs.edu.ph";
                break;
            case 'AGO':
                $returnCompanyCampus = "hrmo.angono@urs.edu.ph";
                break;
            default:
            $returnCompanyCampus = "";
                break;
        }

        return $returnCompanyCampus;
    }

    public static function num_format($number,$decimal=2){
        return number_format($number,$decimal,'.',',');
    }

    public static function getDateTimeDiff($dateTimeFrom, $dateTimeTo){
        $dateTimeFrom = strtotime($dateTimeFrom);
        $dateTimeTo = strtotime($dateTimeTo);

        $diff = abs($dateTimeTo - $dateTimeFrom);

        $years = floor($diff / (365*60*60*24));

        $months = floor(($diff - $years * 365*60*60*24)
                                        / (30*60*60*24));
        $days = floor(($diff - $years * 365*60*60*24 -
                    $months*30*60*60*24)/ (60*60*24));
        $hours = floor(($diff - $years * 365*60*60*24
                - $months*30*60*60*24 - $days*60*60*24)
                                            / (60*60));
        $minutes = floor(($diff - $years * 365*60*60*24
                - $months*30*60*60*24 - $days*60*60*24
                                    - $hours*60*60)/ 60);
        $seconds = floor(($diff - $years * 365*60*60*24
                - $months*30*60*60*24 - $days*60*60*24
                        - $hours*60*60 - $minutes*60));
        
        return array($years, $months, $days, $hours, $minutes, $seconds);
    }

    

}