<?php 
/**
 * @author Justin
 * @copyright 2016
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Reports extends CI_Model {
    
    /*
     * Load Options
     */
    function employeetype($tnt = ""){
        $return  = "";
        $type = array("teaching"=>"teaching","nonteaching"=>"Non-Teaching");
        foreach($type as $key=>$val){
			if($tnt == $key) $selected = "selected";
			else $selected = "";
            $return .= "<option value='$key' $selected>".ucwords($val)."</option>";    
        }
        return $return;
    }
    
    /*
     * Load Query Data
     */ 
    function loadempdata($col='', $division='', $deptid='', $employeeid='',$campus='',$company_campus='',$tnt='',$status='', $empsortby=""){
        $datenow = date("Y-m-d");
        $return = "";
		$empstathistory = array("office2","deptid2","employmentstat2","positionid2","dateposition2", "salary2");
        $empstat = array("salary");
		$children = array("childname","childbday","childage");
		$taxDependents = array (   "tdname","tdrelation","tdaddress","tdcontact","tdbdate","tdlegitimate");
        // $immigrationDetails =  array ( "passport2","visa","icardnum","crnno");

        // echo "<pre>"; print_r($col); die;
        $excol  = explode(',',$col);
        foreach($excol as $str){
			if(!in_array($str,$empstathistory) && !in_array($str,$children) && !in_array($str,$taxDependents) && !in_array($str,$empstat))
			{
            $str2 = '';
            if($return) $return .= ",";
            if($str == "empshift")          $str2 = " b.schedcode as empshift ";
            if($str == "fullname")          $str2 = " REPLACE(CONCAT(a.lname,', ',a.fname,' ',SUBSTR(a.mname, 1, 1),'. '), 'Ã‘', 'Ñ') as fullname";
            if($str == "emptype")           $str2 = " c.description as emptype ";
            if($str == "employmentstat")    $str2 = " d.description as employmentstat ";
            if($str == "deptid")            $str2 = " e.description as deptid ";
            if($str == "civil_status")      $str2 = " f.description as civil_status ";
            if($str == "isactive")          $str2 = " IF(isactive = 1,'Active','In Active') as isactive";
            if($str == "positionid")        $str2 = " g.description as positionid";
            if($str == "managementid")      $str2 = " h.description as managementid";
            if($str == "region")            $str2 = " j.region_name as region";
            if($str == "province")          $str2 = " k.cpname as province";
            if($str == "municipality")      $str2 = " l.DistMunName as municipality";
            if($str == "permaRegion")       $str2 = " aj.region_name as permaRegion";
            if($str == "permaProvince")     $str2 = " ak.cpname as permaProvince";
            if($str == "permaMunicipality") $str2 = " al.DistMunName as permaMunicipality";
            if($str == "gender")            $str2 = " m.description as gender";
            if($str == "religionid")        $str2 = " n.description as religionid";
            if($str == "citizenid")         $str2 = " o.description as citizenid";
            if($str == "nationalityid")     $str2 = " p.description as nationalityid";
            if($str == "rank")              $str2 = " q.description as rank";
            if($str == "aimsdept")          $str2 = " r.DESCRIPTION as aimsdept";
            if($str == "campusid")          $str2 = " s.description as campusid";
            if($str == "a.date_active")     $str2 = " DATE(a.date_active) as 'a.date_active'";
            if($str == "blood_type")        $str2 = " t.description as blood_type";
            if($str == "office")            $str2 = " v.description AS office";
            if($str == "passport2")         $str2 = "passport";
            // if($str == "salary")            $str2 = "w.monthly";

            $return .= $str2; 
            // echo "<pre>"; print_r($return); die;
            if($str2 == ''){
                if($str == "driver_license_type")   $return .= 'UPPER('.$str.') as '.$str;
                else    $return .= 'a.'.$str;
            }
            $str2 = '';

			}
        }
        // echo "<pre>"; print_r($return); die;
		
		$wC = $orderby = '';
        if($division)       $wC .= " AND a.managementid='$division'";
        if($deptid)         $wC .= " AND a.deptid='$deptid'";
        if($employeeid)     $wC .= " AND a.employeeid='$employeeid'";
		if($campus)        $wC .= " AND a.campusid='$campus'";
        if($company_campus)        $wC .= " AND a.company_campus='$company_campus'";
        if($tnt == 'trelated'){
            $wC .= " AND a.trelated='1' AND a.teachingtype = 'teaching'";
        }else{
            if($tnt) $wC .= " AND a.teachingtype='$tnt'";
        } 
        if($status != "all" && $status != ''){
          if($status=="1"){
            $wC .= " AND (('$datenow' < a.dateresigned2 OR a.dateresigned2 = '0000-00-00' OR a.dateresigned2 = '1970-01-01' OR a.dateresigned2 IS NULL) AND a.isactive ='1')";
          }
          if($status=="2"){
            $wC .= " AND (('$datenow' >= a.dateresigned2 AND a.dateresigned2 IS NOT NULL AND a.dateresigned2 <> '0000-00-00' AND a.dateresigned2 <> '1970-01-01' ) OR a.isactive = '0')";
          }
          if(is_null($status)) $wC .= " AND a.isactive = '1' AND (a.dateresigned2 = '0000-00-00' OR a.dateresigned2 = '1970-01-01' OR a.dateresigned2 IS NULL)";
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
        $wC .= $utwc;
        if($empsortby == "alpha") $orderby = " ORDER BY a.lname"; 
        if($empsortby == "employeeid") $orderby = " ORDER BY a.employeeid";   
        if(!$return == "")  $return .= ", ";
        $query = $this->db->query("SELECT $return a.employeeid, a.lname FROM employee a 
                                    LEFT JOIN code_schedule b ON a.empshift = b.schedid 
                                    LEFT JOIN code_type c ON a.emptype = c.code
                                    LEFT JOIN code_status d ON a.employmentstat = d.code
                                    LEFT JOIN code_office e ON a.office = e.code
                                    LEFT JOIN code_civil_status f ON a.civil_status = f.code
                                    LEFT JOIN code_position g ON a.positionid = g.positionid
                                    LEFT JOIN code_managementlevel h ON a.managementid = h.managementid
                                    LEFT JOIN regions j ON a.regionaladdr = j.region_code
                                    LEFT JOIN city_provinces k ON a.provaddr = k.cpID
                                    LEFT JOIN district_municipalities l ON a.cityaddr = l.dmunID
                                    LEFT JOIN regions aj ON a.permaRegion = aj.region_code
                                    LEFT JOIN city_provinces ak ON a.permaProvince = ak.cpID
                                    LEFT JOIN district_municipalities al ON a.permaMunicipality = al.dmunID
                                    LEFT JOIN code_gender m ON a.gender = m.genderid
                                    LEFT JOIN code_religion n ON a.religionid = n.religionid
                                    LEFT JOIN code_citizenship o ON a.citizenid = o.citizenid
                                    LEFT JOIN code_nationality p ON a.nationalityid = p.nationalityid
                                    LEFT JOIN rank_code_type q ON a.rank = q.id
                                    LEFT JOIN tblCourseCategory r ON a.aimsdept = r.code
                                    LEFT JOIN code_campus s ON a.campusid = s.code
                                    LEFT JOIN code_blood t ON a.blood_type = t.bloodid
                                    LEFT JOIN code_department v ON a.deptid = v.code
                                    -- LEFT JOIN payroll_employee_salary w ON a.employeeid = w.employeeid
                                    WHERE 1 $wC $orderby")->result();
        // echo "<pre>"; print_r($this->db->last_query()); die;

        return $query;
    }

    function loadempbirthdayreportage($where_clause){
        $return = "";
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (deptid, '$utdept') OR FIND_IN_SET (office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND employeeid = 'nosresult'";
        }
        $where_clause .= $utwc;
        $query = $this->db->query("SELECT employeeid, fname, lname, deptid, bdate, age, CONCAT(lname, ', ', fname, ' ', mname) AS fullname, office FROM employee WHERE 1 $where_clause ORDER BY bdate DESC")->result();

        return $query;
    }

    function loademplinkingreport($where_clause){
        $return = "";

        $query = $this->db->query("SELECT b.*, a.deptid, a.office FROM linking_trail b LEFT JOIN employee a ON b.link_to = a.employeeid OR b.link_from = a.employeeid WHERE 1 $where_clause ORDER BY b.`timestamp` DESC")->result();

        return $query;
    }

    function loadempbirthdayreportall($where_clause){
        $return = "";
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
        $where_clause .= $utwc;
        $query = $this->db->query("SELECT employeeid, fname, lname, deptid, bdate, age, CONCAT(lname, ', ', fname, ' ', mname) AS fullname, office FROM employee WHERE 1 $where_clause ORDER BY DATE_FORMAT(bdate, '%m %d')")->result();
        return $query;
    }
    
    function loadempbirthdayreportmonth($month, $where_clause){
        $return = ""; 
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
        $where_clause .= $utwc;
        $query = $this->db->query("SELECT employeeid, fname, lname, deptid, bdate, age, CONCAT(lname, ', ', fname, ' ', mname) AS fullname, office FROM employee WHERE DATE_FORMAT(bdate, '%m') = $month $where_clause ORDER BY DATE_FORMAT(bdate, '%d')")->result();
        return $query;
    }
    public function allEmpByInfoTrail($where_clause){
        $return = "";
        $query = $this->db->query("SELECT a.*, CONCAT(a.lname,', ',a.fname,' ',a.mname) AS fullname, b.* FROM employee a INNER JOIN empinfo_edit_trail b ON a.`employeeid` = b.`employeeid` INNER JOIN employee_education c ON a.employeeid = c.employeeid inner join reports_item d on d.level = c.educ_level $where_clause ORDER BY fullname ASC ")->result();
        return $query;
    }
    function loadempLeavereportmonth($month){
        $return = ""; 
        $query = $this->db->query("SELECT 
          CONCAT(a.lname, ',', a.fname, ',', a.mname) AS fullname,
          c.description as description,
          a.deptid as deptid,
          b.leavetype as leavetype,
          d.nodays as nodays,
          CONCAT(e.fromdate, ',', e.todate) AS dateofexclusive,
          d.reason as reason,
          b.balance as balance
        FROM
          employee a 
          INNER JOIN employee_leave_credit b 
            ON a.employeeid = b.employeeid 
          INNER JOIN code_position c 
            ON a.positionid = c.positionid 
          INNER JOIN leave_app_base d 
            ON a.`employeeid` = d.`applied_by` 
          INNER JOIN leave_request e 
            ON a.`employeeid` = e.`employeeid` WHERE isactive = 1 AND DATE_FORMAT(fromdate, '%m') = $month  ORDER BY DATE_FORMAT(fromdate, '%d')")->result();
                return $query;
    }
    function loadempLeavereportallMonth($where_clause){
        $return = "";
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (c.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (c.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (c.deptid, '$utdept') OR FIND_IN_SET (c.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND c.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (c.campusid,'$usercampus') ";
        }
        $where_clause .= $utwc;
        $query = $this->db->query("SELECT c.employeeid, CONCAT(c.lname, ',', c.fname, ',', c.mname) AS fullname, e.description as description, f.description as deptid, a.type as ltype, a.nodays as nodays, CONCAT(d.dfrom, ' - ', d.dto) AS dateofexclusive, a.reason as reason,  d.avail as avail, a.datefrom, a.dateto, a.nodays FROM leave_app_base a INNER JOIN leave_app_emplist b ON b.`base_id` = a.`id` INNER JOIN employee c ON c.`employeeid` = b.`employeeid` INNER JOIN employee_leave_credit d ON b.`employeeid` = d.`employeeid` INNER JOIN code_position e ON c.positionid = e.positionid INNER JOIN code_office f ON c.office = f.code INNER JOIN leave_app_emplist g ON g.`base_id` = a.`id` WHERE a.id != '' AND g.status = 'APPROVED' $where_clause GROUP BY a.id ORDER BY c.lname")->result();

        // $query = $this->db->query("SELECT 
        //   CONCAT(a.lname, ',', a.fname, ',', a.mname) AS fullname,
        //   c.description as description,
        //   f.description as deptid,
        //   b.leavetype as leavetype,
        //   d.nodays as nodays,
        //   CONCAT(b.dfrom, ' - ', b.dto) AS dateofexclusive,
        //   d.reason as reason,
        //   b.avail as avail
        // FROM
        //   employee a 
        //   INNER JOIN code_office f 
        //   on a.deptid = f.code
        //   LEFT JOIN employee_leave_credit b 
        //     ON a.employeeid = b.employeeid 
        //   LEFT JOIN code_position c 
        //     ON a.positionid = c.positionid 
        //   INNER JOIN leave_app_base d 
        //     ON b.`leavetype` = d.`type` 
        //   INNER JOIN leave_request e 
        //     ON a.`employeeid` = e.`employeeid` WHERE 1 $where_clause ORDER BY a.lname ")->result();
            // ON a.`employeeid` = e.`employeeid` $where_clause ORDER BY DATE_FORMAT(b.dfrom, '%m %d') ")->result();
        return $query;
    }

    function getTaxableAmount($empid, $cutoff, $isMRRreport = ""){
        $amount = $gross = $tardy = $absent = 0;
        $list_income = '';

        if(!$isMRRreport){
            $ecutoff = explode(",",$cutoff);
            $cStart = $ecutoff[0];
            $cEnd = $ecutoff[1];
            $q_tax = $this->db->query("SELECT a.gross,a.income, a.tardy, a.absents 
                                    FROM payroll_computed_table a
                                    WHERE a.employeeid = '$empid' AND a.cutoffstart='$cStart' AND a.cutoffend = '$cEnd' AND a.bank <> '' AND a.emp_accno <> '';");
            if($q_tax->num_rows() > 0) $q_tax = $q_tax->result();
            else $q_tax = array();
            if(count($q_tax) > 0){
                foreach ($q_tax as $res) {
                    $gross = $res->gross;
                    $tardy = $res->tardy;
                    $absent = $res->absents;
                    $list_income = $res->income;
                }

                $amount = $gross;
                foreach (explode("/", $list_income) as $exp_list_income) {
                    list($id, $value) = explode("=", $exp_list_income);

                    $isNoTax = $this->getSetupForPayrollIncome("taxable", $id);

                    if($isNoTax == 'notax') $amount -= $value;
                }
            }else{
                $amount = 0;
            }
        }else{
            $q_tax = $this->db->query("SELECT a.gross,a.income, a.tardy, a.absents 
            FROM payroll_computed_table a
            WHERE a.employeeid = '$empid' AND DATE_FORMAT(a.`cutoffstart`, '%M~~%Y') = '$cutoff' AND DATE_FORMAT(a.`cutoffstart`, '%M~~%Y') = '$cutoff' AND a.bank <> '' ")->result();
        

            if(count($q_tax) > 0){
                foreach ($q_tax as $res) {
                    $gross += $res->gross;
                    $tardy += $res->tardy;
                    $absent += $res->absents;
                    $list_income .= "/".$res->income;
                }
                $list_income = substr($list_income, 1);
                $amount = $gross;
                foreach (explode("/", $list_income) as $exp_list_income) {
                    list($id, $value) = explode("=", $exp_list_income);

                    $isNoTax = $this->getSetupForPayrollIncome("taxable", $id);

                    if($isNoTax == 'notax') $amount -= $value;
                }
            }else{
                $amount = 0;
            }
            return $amount;
        }
    }

    function alphalistEmp($year)
    {
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
        $return = $this->db->query("SELECT 
                                        CONCAT(a.lname,' ,',a.fname) AS fullname,
                                        a.emp_sss,
                                        a.emp_tin,
                                        a.emp_philhealth,
                                        a.emp_pagibig,
                                        a.bdate,
                                        b.employeeid
                                    FROM employee a
                                    INNER JOIN
                                    (
                                        SELECT DISTINCT(employeeid)
                                        FROM payroll_computed_table
                                        WHERE YEAR(cutoffstart) = '{$year}' AND YEAR(cutoffend) = '{$year}'
                                    ) b ON a.employeeid = b.employeeid WHERE 1 $utwc
                                    ORDER BY a.lname")->result();
        return $return;
    }

    function alphalistData($empid,$year)
    {
        $return = $this->db->query("SELECT *
                                    FROM payroll_computed_table
                                    WHERE YEAR(cutoffstart) = '{$year}' AND YEAR(cutoffend) = '{$year}' AND employeeid = '{$empid}'
                                    ORDER BY cutoffstart")->result();
        return $return;
    }
    function loademployeeDeduction($datefrom="",$dto="",$employeeid="",$code= "")
    {
        $wC = "";
        if ($datefrom) {$wC .= "AND cutoffstart ='$datefrom'";}
        if ($dto) {$wC .= "AND cutoffend ='$dto'";}
        if ($employeeid) { $wC .= " AND a.employeeid='$employeeid'";}
        if ($code) { $wC .= "AND a.code_loan='$code'";}
       
        $query = $this->db->query("SELECT *, SUM(a.amount) AS amounts  FROM employee_loan_history a  LEFT JOIN employee b ON (a.`employeeid` = b.employeeid) WHERE a.mode='CUTOFF' AND  (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00') AND a.employeeid  !='' $wC  ORDER BY a.employeeid, a.code_loan")->result();

        return $query;
    }

    function loademployeeDeductionForVerify($datefrom="",$dto="",$employeeid="",$code= "")
    {
        $wC = "";
        if ($datefrom && $dto) {$wC .= "AND cutoffstart BETWEEN '$datefrom' AND '$dto'";}
        /*if ($dto) {$wC .= "AND cutoffend ='$dto'";}*/
        if ($employeeid) { $wC .= " AND a.employeeid='$employeeid'";}
        if ($code) { $wC .= "AND a.code_loan='$code'";}
       
        $query = $this->db->query("SELECT * FROM employee_loan_history a  LEFT JOIN employee b ON (a.`employeeid` = b.employeeid) WHERE (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00') AND a.employeeid  !='' $wC  ORDER BY a.employeeid, a.code_loan")->result();

        return $query;
    }

    function loademployeeDeductionDetailed($stats,$startdate="",$enddate="",$employeeid="",$code)
    {
        $wC = "";
        if ($startdate) {$wC .= "AND cutoffstart ='$startdate'";}
        if ($enddate) {$wC .= "AND p.cutoffend ='$enddate'";}
        if ($employeeid) { $wC .= " AND a.employeeid='$employeeid'";}
        if ($code) { $wC .= "AND a.code_loan='$code'";}
       
        $query = $this->db->query("SELECT * FROM employee_loan_history a  LEFT JOIN employee b ON (a.`employeeid` = b.employeeid) LEFT JOIN payroll_computed_table p ON (b.`employeeid` = p.employeeid) WHERE  p.status = '$stats' AND (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00') AND a.employeeid  !='' $wC  AND a.mode !='DELETED' ORDER BY a.employeeid,a.code_loan,a.timestamp")->result();
        
        return $query;
    }
    function loadEmployeeLedgerData($eid="",$empstat="",$dept="",$year="")
    {
        $whereClause="";
        if($eid)  $whereClause .="AND b.employeeid='$eid'";
        if($empstat) $whereClause .="AND b.employmentstat='$empstat'";
        if($dept) $whereClause .="AND b.deptid='$dept'";
        // if($year) $whereClause .="AND YEAR(a.cutoffstart)='$year' AND YEAR(a.cutoffend)='$year'";

        $query = $this->db->query("SELECT a.*, CONCAT(lname, ', ', fname, ' ', mname) AS fullname,b.emp_accno,b.emp_sss,b.`emp_tin`,c.`description` AS empPosition,d.`description` AS department,e.`description` AS employmentstatus
        FROM payroll_computed_table a LEFT JOIN employee b ON a.employeeid = b.employeeid LEFT JOIN code_position c ON(c.`positionid` = b.`positionid`) LEFT JOIN code_office d ON(d.code = b.`deptid`) LEFT JOIN code_status e ON(e.code = b.employmentstat) WHERE YEAR(a.cutoffstart) = '$year'  $whereClause ORDER BY a.cutoffstart ASC LIMIT 5")->result();
        return $query;
    }


    
	//Added 5-19-17
	// function loadempdataschedule($division="",$department="",$tnt="",$dfrom="",$division="",$department=""){
	// 	$wC ="";
		
	// 	if($division) $wC.="AND a.managementid = '{$division}'";
 //        if($department) $wC.="AND a.deptid = '{$department}'";
 //        if($tnt) $wC.="AND a.teachingtype = '{$tnt}'";
 //        // if($dfrom) $wC.="AND a.dateemployed <= '{$dfrom}'";

 //        $query = $this->db->query("SELECT a.*, CONCAT(a.lname,', ',a.fname,' ',a.mname) as fullname  FROM employee a 
	// 								INNER JOIN employee_schedule b on a.employeeid = b.employeeid
 //                                    WHERE (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00')
	// 								$wC GROUP BY employeeid ORDER BY deptid ASC")->result();
 //        return $query;
 //    }
    //12072020
    function loadempdataschedule($where_clause){
        $wC ="";
        
        // if($division) $wC.="AND a.managementid = '{$division}'";
        // if($department) $wC.="AND a.deptid = '{$department}'";
        // if($tnt) $wC.="AND a.teachingtype = '{$tnt}'";
        // if($dfrom) $wC.="AND a.dateemployed <= '{$dfrom}'";
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

        $query = $this->db->query("SELECT a.*, CONCAT(a.lname,', ',a.fname,' ',a.mname) as fullname  FROM employee a 
                                    INNER JOIN employee_schedule b on a.employeeid = b.employeeid WHERE 1 
                                    $where_clause GROUP BY employeeid ORDER BY deptid ASC")->result();
        return $query;
    }

    function getPayrollIncomeConfig($table="", $id="", $is_adjustment=false){
        $wC = "";
        if($id != "selectAll") $wC .= "id = '$id' ";
        if($is_adjustment) $wC .= "description LIKE '% adj%' OR description LIKE '%adjustment%' OR description LIKE '%adj %'";

        $wC = ($wC) ? "WHERE ". $wC : "";
        $data = $this->db->query("SELECT * FROM $table $wC")->result_array();
        return $data;
    }

    function getPayrollIncomeConfigDeminimis($table="", $id=""){
        $wC = "";
        ($id == 'selectAll') ? $wC .= ""  : $wC .= " AND id = '$id'";
        $data = $this->db->query("SELECT * FROM $table WHERE incomeType = 'deminimiss' $wC")->result_array();
        return $data;
    }

    function getPayrollIncomeConfigNoDeminimis($table="", $id=""){
        $wC = "";
        ($id == 'selectAll') ? $wC .= ""  : $wC .= " AND id = '$id'";
        $data = $this->db->query("SELECT * FROM $table WHERE incomeType != 'deminimiss' $wC")->result_array();
        return $data;
    }

    function getEmployeeListPerCodeIncome($code_income="",$cutoffstart="", $cutoffend="", $tnt="",$eid=""){
        $wC = "";
        if($code_income) $wC .= " AND p.code_income='$code_income'";
        if($tnt) $wC .= " AND a.teachingtype='$tnt'";
        $data = $this->db->query("SELECT DISTINCT p.employeeid,REPLACE(CONCAT(a.lname,', ',a.fname,' ',a.mname), 'Ã‘', 'Ñ') AS fullname, p.cutoffstart, p.cutoffend,p.code_income, p.amount
                                    FROM payroll_process_income p
                                    INNER JOIN employee a ON a.`employeeid`=p.`employeeid`
                                    WHERE p.`cutoffstart`='$cutoffstart' AND p.`cutoffend`='$cutoffend' $wC")->result_array();
        return $data;
    }

    function getEmployeeListByIncome($code_income="",$cutoffstart="", $cutoffend="", $tnt="",$eid="",$sort=""){
        $wC = "";
        ini_set('display_errors',1);
        error_reporting(-1);
        // if($code_income) $wC .= " AND p.code_income='$code_income'";
        if($tnt) $wC .= " AND a.teachingtype='$tnt'";
        $data = $this->db->query("SELECT DISTINCT p.employeeid,REPLACE(CONCAT(a.lname, ', ', a.fname, ' ', a.mname),'Ã‘','Ñ') AS fullname,p.cutoffstart,p.cutoffend,p.income,a.emp_accno FROM payroll_computed_table p 
                                    INNER JOIN employee a ON a.`employeeid`=p.`employeeid`
                                    WHERE p.`status` = '$sort' AND p.`cutoffstart`='$cutoffstart' AND p.`cutoffend`='$cutoffend' AND p.income <> '' AND a.`emp_accno` <> '' $wC")->result_array();
        // echo "SELECT DISTINCT p.employeeid,REPLACE(CONCAT(a.lname, ', ', a.fname, ' ', a.mname),'Ã‘','Ñ') AS fullname,p.cutoffstart,p.cutoffend,p.income FROM payroll_computed_table p 
        //                             INNER JOIN employee a ON a.`employeeid`=p.`employeeid`
        //                             WHERE p.`cutoffstart`='$cutoffstart' AND p.`cutoffend`='$cutoffend' AND p.income <> ''  AND p.`empAccNo` <> ''  $wC";
        return $data;
    }

    
    /*
     * Description
     */
    function showdesc($data){
		$return = array ( 
							"employeeid"=>"EMPLOYEE ID",
                            "fullname"=>"FULL NAME",
                            "employeecode"=>"RFID",      
							"lname"=>"LAST NAME",
							"fname"=>"FIRST NAME",
                            "mname"=>"MIDDLE NAME",
                            "nname"=>"NICK NAME",
                            "dateemployed"=>"DATE HIRED",
                            "year_service"=>"YEAR'S IN SERVICE",
                            "rank"=>"RANK",
                            "designation"=>"DESIGNATION",
                            "title"=>"TITLE",
                            "aimsdept"=>"AIMS DEPARTMENT",

                            "emp_tin"=>"TIN #",
                            "emp_sss"=>"SSS #",
                            "emp_philhealth"=>"PHILHEALTH #",
                            "emp_pagibig"=>"PAG-IBIG #",
                            "emp_peraa"=>"PERAA #",
                            "emp_medicare"=>"HMO",
                            "prc"=>"PRC #",
                            "driver_license"=>"DRIVER LICENSE #",
                            "passport"=>"PASSPORT #",
                            "emp_accno"=>"ACCOUNT #",
                            "emp_hmo"=>"HMO #",
                            "passport_expiration"=>"DATE OF EXPIRATION",
                            "prc_expiration"=>"DATE OF EXPIRATION",
                            "driver_license_expiration"=>"DATE OF EXPIRATION",
                            "driver_license_type"=>"TYPE OF LICENSE",
                            "driver_license_expiration"=>"DATE OF EXPIRATION",
                            "driver_license_expiration"=>"DATE OF EXPIRATION",

                            "teachingtype"=>"TYPE",
                            "isactive"=>"STATUS",
                            "emptype"=>"BATCH SCHEDULING",
                            "empshift"=>"SCHEDULE LIST",
                            "a.date_active"=>"EFFECTIVITY DATE",
                            "dateresigned"=>"DATE RESIGNED",
                            "resigned_reason"=>"REASON FOR LEAVING",
                            "campusid"=>"CAMPUS",
                            "company_campus"=>"COMPANY",
							
                            "managementid"=>"MANAGEMENT",
                            "deptid"=>"OFFICE",
                            "office"=>"DEPARTMENT/COLLEGE",
                            "employmentstat"=>"EMPLOYEE STATUS",
                            "positionid"=>"POSITION HELD",
                            "dateposition"=>"DATE OF EMPLOYMENT",
                            "salary"=>"SALARY",
                            "sep_type"=>"TYPE(TL/VSL)",
							
							"managementid2"=>"MANAGEMENT",
                            "office2"=>"DEPARTMENT/COLLEGE",
                            "deptid2"=>"OFFICE",
                            "employmentstat2"=>"EMPLOYEE STATUS",
                            "positionid2"=>"POSITION HELD",
                            "dateposition2"=>"DATE OF EMPLOYMENT",
                            "salary2"=>"SALARY",
							
							"bdate"=>"DATE OF BIRTH",
                            "bplace"=>"PLACE OF BIRTH",
                            "age"=>"AGE",
							"civil_status"=>"CIVIL STATUS",
							"citizenid"=>"CITIZENSHIP",
							"gender"=>"GENDER",
							"mobile"=>"MOBILE NUMBER",
							"religionid"=>"RELIGION",
                            "nationalityid"=>"NATIONALITY",
                            "region"=>"REGION",
                            "province"=>"PROVINCE",
                            "municipality"=>"MUNICIPALITY",
                            "addr"=>"HOUSE #",
                            "blood_type"=>"BLOOD TYPE",
                            "height"=>"HEIGHT",
                            "weight"=>"WEIGHT",
                            "personal_email"=>"PERSONAL EMAIL",
                            "email"=>"WORK EMAIL",
                            "landline"=>"LANDLINE",
                            "barangay"=>"BARANGAY",
                            "zip_code"=>"ZIP CODE",

                            "permaRegion"=>"REGION",
                            "permaProvince"=>"PROVINCE",
                            "permaMunicipality"=>"MUNICIPALITY",
                            "permaAddress"=>"HOUSE #",
                            "permaBarangay"=>"BARANGAY",
                            "permaZipcode"=>"ZIP CODE",
							
                            "father"=>"FATHER'S NAME",
                            "fatheroccu"=>"OCCUPATION",
                            "fatherbdate"=>"DATE OF BIRTH",
							
							"mother"=>"NAME",
                            "motheroccu"=>"OCCUPATION",
                            "motherbdate"=>"DATE OF BIRTH",
							
							"spouse_lname"=>"LAST NAME",
                            "spouse_mname"=>"MIDDLE NAME",
                            "spouse_fname"=>"FIRST NAME",
                            "spouse_bdate"=>"DATE OF BIRTH",
                            "spouse_Address"=>"OFFICE ADDRESS",
                            "spouse_contact"=>"CONTACT NUMBER",
                            "spouse_company"=>"NAME OF COMPANY",
                            "spouse_job"=>"JOB POSITION",
							
                            "childname"=>"CHILD`S NAME",
                            "childbday"=>"BIRTHDAY",
                            "childage"=>"AGE",
							
							"passport2"=>"PASSPORT #",
                            "visa"=>"VISA #",
                            "icardnum"=>"ICARD #",
                            "crnno"=>"CRN #",
							
                            "tdname"=>"NAME",
                            "tdrelation"=>"RELATION",
							"tdaddress"=>"ADDRESS",
                            "tdcontact"=>"CONTACT #",
                            "tdbdate"=>"BIRTHDAY",
                            "tdlegitimate"=>"LEGITIMATE",
		);
        // $return = array (   "employeeid"=>"Employee ID",
                            // "employeecode"=>"Employee Code",
                            // "emptype"=>"Leave Type",
                            // "empshift"=>"Shift Schedule",
                            // "employmentstat"=>"Employee Status",
                            // "deptid"=>"Department",
                            // "lname"=>"Last Name",
                            // "fname"=>"First Name",
                            // "mname"=>"Middle Name",
                            // "gender"=>"Gender",
                            // "mobile"=>"Mobile",
                            // "email"=>"Email",
                            // "provaddr"=>"Provincial Address",
                            // "occupation"=>"Occupation",
                            // "age"=>"Age",
                            // "bdate"=>"Birthdate",
                            // "bplace"=>"Birthplace",
                            // "dateemployed"=>"Date Employed",
                            // "civil_status"=>"Civil Status",
                            // "emp_accno"=>"Account No.",
                            // "dateposition"=>"Date Position",
                            // "assignment"=>"Assignment",
                            // "remarks"=>"Remarks",
                            // "managementid"=>"Age",
                            // "dateresigned"=>"Date Resigned",
                            // "resigned_reason"=>"Reason",
                            // "prc"=>"Prc",
                            // "passport"=>"Passport #",
                            // "visa"=>"Visa #",
                            // "crnno"=>"CRN #",
                            // "teaching"=>"Cluster Head",
                            // "teachingtype"=>"Type",
                            // "isactive"=>"Account",
                            // "leavetype"=>"Leave Type",
                            // "mother"=>"Mother",
                            // "motheroccu"=>"Mother Occupation",
                            // "father"=>"Father",
                            // "fatheroccu"=>"Father Occupation",
                            // "spouse_name"=>"Spouse",
                            // "cityaddr"=>"City Address",
                            // "positionid"=>"Position"
                        // );
        return $return[$data];
    }
    
    
    /*
     * Count Data
     */
    function countLicensedEmployee($dept='',$campus='')
    {
        $return = "";
         $whereC = '';
        if ($campus) {$whereC = "AND campusid='$campus'";}  
        $query = $this->db->query("SELECT COUNT(employeeid) as licensed FROM employee WHERE prc != '' AND deptid='$dept' $whereC");
        if ($query->num_rows() > 0) {
               $return = $query->row(0)->licensed;
           }   
        return $return;
    }
    // function countHeadByEducBackground($dept='',$campus='',$type='')
    // {
    //     $return='';
    //      $whereC = '';
    //     if ($campus) {$whereC = "AND campusid='$campus'";}  
    //     if ($type == "1") {
    //         $query = $this->db->query("SELECT   count(educ_level) as count , a.`employeeid`
    //         FROM employee a
    //         LEFT JOIN employee_education b ON b.`employeeid` = a.`employeeid` AND b.`educ_level` = (SELECT MAX(educ_level) FROM employee_education WHERE employeeid = a.`employeeid` )
    //         WHERE b.`educ_level` != '' AND a.`deptid`='$dept' AND b.`educ_level`='1' $whereC");
    //             }
    //             else if ($type == "2") {
    //                 $query = $this->db->query("SELECT   count(educ_level)  as count, a.`employeeid`
    //         FROM employee a
    //         LEFT JOIN employee_education b ON b.`employeeid` = a.`employeeid` AND b.`educ_level` = (SELECT MAX(educ_level) FROM employee_education WHERE employeeid = a.`employeeid` )
    //         WHERE b.`educ_level` != '' AND a.`deptid`='$dept' AND b.`educ_level`='2' $whereC");
    //             }
    //             else{
    //                 $query = $this->db->query("SELECT   count(educ_level)  as count, a.`employeeid`
    //         FROM employee a
    //         LEFT JOIN employee_education b ON b.`employeeid` = a.`employeeid` AND b.`educ_level` = (SELECT MAX(educ_level) FROM employee_education WHERE employeeid = a.`employeeid` )
    //         WHERE b.`educ_level` != '' AND a.`deptid`='$dept' AND b.`educ_level`='234' $whereC");
    //     }
        
    //     if ($query->num_rows() > 0) 
    //     {
    //         $return = $query->row(0)->count;
    //     }        
    //     return $return; 
    // }

    function countHeadByEducBackground($dept='',$campus='',$type='')
    {
        $return='';
         $whereC = '';
        if ($campus) {$whereC = " AND a.campusid='$campus'";}  

        // $query = $this->db->query("SELECT count(b.educ_level) as count FROM employee a INNER JOIN employee_education b ON a.employeeid = b.employeeid WHERE a.office = '$deptid' AND b.educ_level = '$type' $whereClause");
        $query = $this->db->query("SELECT count(educ_level) as count , a.`employeeid`
            FROM employee a
            LEFT JOIN employee_education b ON b.`employeeid` = a.`employeeid` AND b.`educ_level` = (SELECT MAX(educ_level) FROM employee_education WHERE employeeid = a.`employeeid` )
            WHERE b.`educ_level` != '' AND a.`office`='$dept' AND b.`educ_level`='$type' $whereC");
                
        
        if ($query->num_rows() > 0) 
        {
            $return = $query->row(0)->count;
        }        
        return $return; 
    }
    function countDeptHeadsNew($dept="", $campusid=""){
        $return = "";
        $whereC = '';
        if ($campusid) {
            $whereC = " AND campus = '$campusid'";
        }
        
        $query = $this->db->query("SELECT COUNT(DISTINCT dhead) as thead FROM campus_office WHERE base_code='$dept' AND dhead <> 0 $whereC");
        if($query->num_rows() > 0)  $return = $query->row(0)->thead;
        return ($return ? $return : "");

    }
    function countDeptHeads($dept=""){
        $return = "";
        $whereC = '';
              
        
        $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM code_office WHERE code='$dept' AND head <> '' ");
        if($query->num_rows() > 0)  $return = $query->row(0)->thead;
        return ($return ? $return : "");

    }
    function countHeads($dept=""){
        $return = "";
        $query = $this->db->query("SELECT DISTINCT COUNT(*) AS thead FROM code_managementlevel WHERE managementid='$dept'");
        if($query->num_rows() > 0)  $return = $query->row(0)->thead;
        return ($return ? $return : "");
    }

    function countDeptESTAT($dept="",$type="",$campusid='',$isactive='', $tnt='', $company=''){
        $return = "";
        $whereC = '';
        if ($campusid) {$whereC .= " AND campusid='$campusid'";}   
        if ($isactive) {
            if($isactive == 2) $whereC .= " AND isactive = '0'";
            else if($isactive == 1) $whereC .= " AND isactive = '1'";
        }   
        if ($tnt) {$whereC .= " AND teachingtype='$tnt'";}    
        if ($company) {$whereC .= " AND company_campus='$company'";}  
        $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE office='$dept' AND employmentstat = '$type' $whereC");
        // if($query->num_rows() > 0)  $return = $query->row(0)->thead;
        // return ($return ? $return : "");
        return $query->row(0)->thead;


    }
    function employeeoffices(){
         $q = $this->db->query("SELECT COUNT(DISTINCT office) AS hh FROM employee");
         if ($q->num_rows() > 0) {
             return $q->row(0)->hh;
         }
         else{
            return 'WALA';
         }
    }

    function countDeptESTATNew($dept="",$type="",$campus='',$active='', $tnt='', $company='',$description=''){
        $return = "";
        $datenow = date("Y-m-d");
        $whereC = ''; 
        if($active != "all"){
          if($active=="1"){
            $whereC .= " AND (('$datenow' < dateresigned2 OR dateresigned2 = '0000-00-00' OR dateresigned2 = '1970-01-01' OR dateresigned2 IS NULL) AND isactive ='1')";
          }
          if($active=="2"){
            $whereC .= " AND (('$datenow' >= dateresigned2 AND dateresigned2 IS NOT NULL AND dateresigned2 <> '0000-00-00' AND dateresigned2 <> '1970-01-01' ) OR isactive = '0')";
          }
          if(is_null($active)){ $whereC .= " AND isactive = '1' AND (dateresigned2 = '0000-00-00' OR dateresigned2 = '1970-01-01' OR dateresigned2 IS NULL)";}
        }   
        if ($tnt) {
            if($tnt != "trelated") $whereC .= " AND a.teachingtype='$tnt'";
            else $whereC .= " AND a.trelated='1' AND a.teachingtype = 'teaching'";
        }       
        if ($company) {$whereC .= " AND a.company_campus='$company'";}  
        $usercampus = $this->extras->getCampusUser();
        if($campus && $campus!="All"){
          $whereC .= " AND campusid = '$campus'";
        }else{
            if($usercampus){
              $usercampus .= ",All";
              $whereC .= " AND FIND_IN_SET (campusid,'$usercampus') ";
            }
        }
        // $query3 = $this->db->query("SELECT code FROM code_campus")->result_array();
        // foreach ($query3 as $key => $value) {
        //     $query2 = $this->db->query("SELECT dhead FROM campus_office WHERE base_code = '$deptid' AND campus = '$value'");
        //     $headid = $query2->row(0)->dhead;
        //     $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE office='$dept' AND (employmentstat = '$type' OR employmentstat = '$description') AND employeeid != '$headid' $whereC");
        //     // if($query->num_rows() > 0)  $return = $query->row(0)->thead;
        //     // return ($return ? $return : "");
        //     return $query->row(0)->thead;
        // }
        $query = $this->db->query("SELECT COUNT(a.employeeid) AS thead FROM employee a WHERE a.office='$dept' AND (a.employmentstat = '$type' OR a.employmentstat = '$description') $whereC");
        return $query->row(0)->thead;
    }
    function countnoffice($dept="",$type="",$campusid='',$isactive='', $tnt='', $company='',$description=''){
        $return = "";
        $whereC = '';
        if ($campusid) {$whereC .= " AND a.campusid='$campusid'";}   
        if ($isactive) {
            if($isactive == 2) $whereC .= " AND a.isactive = '0'";
            else if($isactive == 1) $whereC .= " AND a.isactive = '1'";
        }   
        if ($tnt) {$whereC .= " AND a.teachingtype='$tnt'";}    
        if ($company) {$whereC .= " AND a.company_campus='$company'";}  
        // $query3 = $this->db->query("SELECT code FROM code_campus")->result_array();
        // foreach ($query3 as $key => $value) {
        //     $query2 = $this->db->query("SELECT dhead FROM campus_office WHERE base_code = '$deptid' AND campus = '$value'");
        //     $headid = $query2->row(0)->dhead;
        //     $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE office='$dept' AND (employmentstat = '$type' OR employmentstat = '$description') AND employeeid != '$headid' $whereC");
        //     // if($query->num_rows() > 0)  $return = $query->row(0)->thead;
        //     // return ($return ? $return : "");
        //     return $query->row(0)->thead;
        // }
        $query = $this->db->query("SELECT COUNT(DISTINCT a.employeeid) AS thead FROM employee a WHERE a.office='' AND (a.employmentstat = '$type' OR a.employmentstat = '$description') $whereC");
        return $query->row(0)->thead;
    }
    function countnoofficeandnoemploymentstat($dept="",$type="",$campus='',$active='', $tnt='', $company='',$description=''){
        $return = "";
        $whereC = '';
        $datenow = date("Y-m-d");
        if($active != "all"){
          if($active=="1"){
            $whereC .= " AND (('$datenow' < dateresigned2 OR dateresigned2 = '0000-00-00' OR dateresigned2 = '1970-01-01' OR dateresigned2 IS NULL) AND isactive ='1')";
          }
          if($active=="2"){
            $whereC .= " AND (('$datenow' >= dateresigned2 AND dateresigned2 IS NOT NULL AND dateresigned2 <> '0000-00-00' AND dateresigned2 <> '1970-01-01' ) OR isactive = '0')";
          }
          if(is_null($active)){ $whereC .= " AND isactive = '1' AND (dateresigned2 = '0000-00-00' OR dateresigned2 = '1970-01-01' OR dateresigned2 IS NULL)";}
        }  
        if ($tnt) {$whereC .= " AND a.teachingtype='$tnt'";}    
        if ($company) {$whereC .= " AND a.company_campus='$company'";}

        // $query3 = $this->db->query("SELECT code FROM code_campus")->result_array();
        // foreach ($query3 as $key => $value) {
        //     $query2 = $this->db->query("SELECT dhead FROM campus_office WHERE base_code = '$deptid' AND campus = '$value'");
        //     $headid = $query2->row(0)->dhead;
        //     $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE office='$dept' AND (employmentstat = '$type' OR employmentstat = '$description') AND employeeid != '$headid' $whereC");
        //     // if($query->num_rows() > 0)  $return = $query->row(0)->thead;
        //     // return ($return ? $return : "");
        //     return $query->row(0)->thead;
        // }
        $usercampus = $this->extras->getCampusUser();
        if($campus && $campus!="All"){
          $whereC .= " AND campusid = '$campus'";
        }else{
            if($usercampus){
              $usercampus .= ",All";
              $whereC .= " AND FIND_IN_SET (campusid,'$usercampus') ";
            }
        }
        $q = $this->db->query("SELECT code, description FROM code_status");
        $empStatusAll = '';
        $empCount = $q->num_rows();
        $c = 0;
        foreach ($q->result() as $key => $value) {
            $c = $c + 1;
            if ($c != $empCount) {
                $empStatusAll .= " employmentstat != '$value->code' AND employmentstat != '$value->description' AND";
            }
            else{
                $empStatusAll .= " employmentstat != '$value->code' AND employmentstat != '$value->description'";
            }
            
        }
        $query = $this->db->query("SELECT COUNT(a.employeeid) AS thead FROM employee a WHERE a.office='$dept' AND ($empStatusAll) $whereC");
        return $query->row(0)->thead;
        // return $empStatusAll;
    }

    function countDeptESTATxls($dept="",$type="",$campusid='',$isactive='', $tnt='', $company=''){
        $return = "";
        $whereC = '';
        if ($campusid) {$whereC .= " AND campusid='$campusid'";}   
        if ($isactive!="") {$whereC .= " AND isactive='$isactive'";}   
        if ($tnt) {$whereC .= " AND teachingtype='$tnt'";}    
        if ($company) {$whereC .= " AND company_campus='$company'";}  
        $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE office='$dept' AND employmentstat = '$type' $whereC");
        // if($query->num_rows() > 0)  $return = $query->row(0)->thead;
        // return ($return ? $return : "");
        // return $query;
        if ($query->num_rows() > 0) {
            return $query->row(1)->thead;
        } else {
            return 0;
        }


    }

    function countDeptDivision($managementid="",$date="",$type="",$campusid=''){
        $return = ""; $whereC = '';
       $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE managementid='$managementid' AND employmentstat = '$type' AND (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00' OR DATE_FORMAT(dateresigned,'%Y-%m') >= '$date') $whereC");
       if($query->num_rows() > 0)  $return = $query->row(0)->thead;
       return ($return ? $return : "");
        // if($query->num_rows() > 0)  $return = $query->row(0)->thead;
        // return ($return ? $return : "");
    }
    // function countDeptESTAT($dept="",$date="",$type="",$campusid=''){
    //     $return = "";
    //     $whereC = '';
    //     if ($campusid) {$whereC = "AND campusid='$campusid'";}   
    //     if($type == "REGULAR")
    //         $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE deptid='$dept' AND employmentstat = 'REG' AND (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00' OR DATE_FORMAT(dateresigned,'%Y-%m') >= '$date') $whereC");
    //     else if($type == "PROBITIONARY")
    //         $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE deptid='$dept' AND employmentstat = 'PROB' AND (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00' OR DATE_FORMAT(dateresigned,'%Y-%m') >= '$date') $whereC");
    //      else if($type == "FULLTIME")
    //         $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE deptid='$dept' AND employmentstat = 'FULL' AND (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00' OR DATE_FORMAT(dateresigned,'%Y-%m') >= '$date') $whereC");
    //      else if($type == "CASUAL")
    //         $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE deptid='$dept' AND employmentstat = 'CAS' AND (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00' OR DATE_FORMAT(dateresigned,'%Y-%m') >= '$date') $whereC");

    //     else
    //         $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE deptid='$dept' AND employmentstat = 'CON' AND (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00' OR DATE_FORMAT(dateresigned,'%Y-%m') >= '$date') $whereC");
    //     if($query->num_rows() > 0)  $return = $query->row(0)->thead;
    //     return ($return ? $return : "");


    // }

    // function countDeptDivision($managementid="",$date="",$type="",$campusid=''){
    //     $return = ""; $whereC = '';
        
    //    if($type == "permanent")
    //        $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE managementid='$managementid' AND employmentstat = 'PER' AND (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00' OR DATE_FORMAT(dateresigned,'%Y-%m') >= '$date') $whereC");
    //    else if($type == "probitionary")
    //        $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE managementid='$managementid' AND employmentstat = 'PROB' AND (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00' OR DATE_FORMAT(dateresigned,'%Y-%m') >= '$date') $whereC");
    //    else if($type == "full")
    //        $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE managementid='$managementid' AND employmentstat = 'FULL' AND (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00' OR DATE_FORMAT(dateresigned,'%Y-%m') >= '$date') $whereC");
    //    else if($type == "casual")
    //        $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE managementid='$managementid' AND employmentstat = 'CAS' AND (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00' OR DATE_FORMAT(dateresigned,'%Y-%m') >= '$date') $whereC");
    //    else
    //        $query = $this->db->query("SELECT DISTINCT COUNT(*) as thead FROM employee WHERE managementid='$managementid' AND employmentstat = 'CON' AND (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00' OR DATE_FORMAT(dateresigned,'%Y-%m') >= '$date') $whereC");
    //    if($query->num_rows() > 0)  $return = $query->row(0)->thead;
    //    return ($return ? $return : "");
    //     if($query->num_rows() > 0)  $return = $query->row(0)->thead;
    //     return ($return ? $return : "");
    // }

    function countPRC($dept="",$date="",$type="")
    {
        $return = "";
        if($type == "prc")
            $query = $this->db->query("SELECT DISTINCT COUNT(*) as prc FROM employee WHERE deptid='$dept' AND prc != '' AND (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00' OR DATE_FORMAT(dateresigned,'%Y-%m') >= '$date')");
        if ($query->num_rows() > 0 ) {
            $return = $query->row(0)->prc;
        }
        return ($return?$return:"");
    }
    function showEmpDetailCols($desc=''){
		
        $return = "";
		if($desc == "General Information")
		{
			// $arrcol = array ("employeeid","fname","lname","mname","dateemployed","year_service","rank","aimsdept");
            $arrcol = array ("employeeid","fullname","fname","lname","mname","dateemployed","year_service","rank", "employeecode");
        }
		else if($desc == "Government ID'S")
		{
			$arrcol = array (   "passport","passport_expiration","prc","prc_expiration","driver_license","driver_license_expiration","driver_license_type","emp_tin","emp_philhealth","emp_pagibig","emp_sss","emp_hmo" );
		}
		else if($desc == "Employee Information")
		{
			$arrcol = array (   "teachingtype","isactive","emptype","empshift","a.date_active","campusid");
			// $arrcol = array (   "teachingtype","isactive","emptype","empshift","a.date_active","campusid","company_campus");
		}
		else if($desc == "Employment Status")
		{
			$arrcol = array (   "office","deptid","employmentstat","positionid","dateposition", "salary", "sep_type" );
		}
		else if($desc == "Employment Status History")
		{
			$arrcol = array (   "office2","deptid2","employmentstat2","positionid2","dateposition2", "salary2" );
		}
		else if($desc == "Personal Information")
		{
			$arrcol = array (   "bdate","bplace","age","civil_status","citizenid","gender","mobile","religionid","nationalityid","blood_type","height","weight","personal_email","landline","email");
		}
		else if($desc == "Father")
		{
			$arrcol = array (   "father","fatheroccu", "fatherbdate");
		}
		else if($desc == "Mother")
		{
			$arrcol = array (   "mother","motheroccu","motherbdate");
		}
		else if($desc == "Spouse Details")
		{
			$arrcol = array ( "spouse_lname","spouse_fname","spouse_mname","spouse_bdate","spouse_Address","spouse_contact","spouse_company","spouse_job");
		}
		else if($desc == "Number of Children")
		{
			$arrcol = array (   "childname","childbday","childage");
		}
        else if($desc == "Current Address")
        {
            $arrcol = array ("region","province","municipality","addr","barangay","zip_code");
        }
        else if($desc == "Permanent Address")
        {
            $arrcol = array ("permaRegion","permaProvince","permaMunicipality","permaAddress","permaBarangay","permaZipcode");
        }
		else if($desc == "Immigration Details")
		{
			$arrcol = array (   "passport2","visa","icardnum","crnno");
		}
		else if($desc == "Tax Dependents")
		{
			$arrcol = array (   "tdname","tdrelation","tdaddress","tdcontact","tdbdate","tdlegitimate");
		}
        // $arrcol = array (   "employeeid","fname","lname","mname","employeecode","passport","visa","crnno","prc",
                            // "teaching","teachingtype","leavetype","emptype","empshift","positionid","dateposition",
                            // "assignment","remarks","managementid","deptid","employmentstat","dateemployed","dateresigned","resigned_reason","bdate","bplace","age","civil_status",
                            // "spouse_name","occupation","gender","mobile","cityaddr","provaddr",
                            // "emp_accno",
                            // "isactive",
                            // "mother",
                            // "motheroccu",
                            // "father",
                            // "fatheroccu"
                        // );
        
        #$query = $this->db->query("SHOW COLUMNS FROM employee WHERE !FIND_IN_SET(FIELD,'title,citytelno,maxregular,maxparttime,numberofdependents,income_base,emp_sss,emp_tin,emp_philhealth,emp_pagibig,emp_peraa,emp_medicare,tax_status,positionid,managementid,citizenid,religionid,nationalityid,permanentaddr,cp_name,cp_relation,cp_address,cp_mobile,cp_telno,isFlexi,hospitalized,hospitalizedtxt,operation,operationtxt,operationdate,medhistory,medhistorytxt,medconditions,createdby,createdon,icardnum');")->result();
        $return .=  '<div class="col-md-6" style="margin-bottom:5%"><span><strong>'.$desc.'</strong></span><br />';
		foreach($arrcol as $row){
            #$col = $row->Field;
            $col = $row;
            $return .=  '
                                <div class="col-md">
                                    <input type="checkbox" name="edata" id="edata" value="'.$col.'" > '.$this->showdesc($col).'
                                </div>
                        ';
        }
		$return .=  '</div>';
        return $return;
    }
	
	function rdc($division="",$department="",$cutoff="",$deduction="",$isRDCForm="", $campus="", $company='', $employeeid='', $month='', $year='', $tnt=''){
		    $where_clause = "";
            $wc= "";
        if($division) $where_clause .= " AND b.managementid = '$division' ";
        if($department) $where_clause .= " AND b.deptid = '$department' ";
        if($tnt){
            if($tnt != "trelated") $where_clause .= " AND b.teachingtype='$tnt'";
            else $where_clause .= " AND b.teachingtype = 'teaching' AND trelated = '1'";
        }
        if($campus && $campus != 'all' && $campus != 'All'){
            $wc .= " AND b.campusid = '$campus'";
            $where_clause .= " AND b.campusid = '$campus'";
        }
        if($company && $company != 'all' && $company != 'All'){
            $wc .= " AND b.company_campus = '$company'";
            $where_clause .= ' AND b.company_campus = "'.$company.'"';
        }
        if($employeeid) $where_clause .=  " AND b.employeeid IN ($employeeid)";
   
        if($isRDCForm){
            $exp_co = explode("~~", $cutoff);
            $cutoffstart = $exp_co[1] .'-'. date("m", strtotime($exp_co[0])) .'-%';
            $where_clause = "a.`cutoffstart` LIKE '$cutoffstart' AND a.fixeddeduc <> ''";
        }
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }
        $where_clause .= $utwc;
        $wC .= $utwc;

        $month_year = $month."-".$year;

        if($isRDCForm){ $query = $this->db->query("SELECT a.fixeddeduc,a.employeeid,a.cutoffstart,a.cutoffend,CONCAT(b.lname, ', ', b.fname, ' ', b.mname) AS fullname, b.campusid, c.company_description,
                                  b.emp_sss,b.emp_pagibig,b.emp_philhealth,b.emp_tin,a.withholdingtax
                                FROM payroll_computed_table a 
                                  INNER JOIN employee b ON a.employeeid = b.employeeid
                                  INNER JOIN campus_company c ON c.company_description = b.company_campus
                                WHERE 1 
                                $wC GROUP BY employeeid ORDER BY fullname,employeeid");

        }else{
            $query = $this->db->query("SELECT a.fixeddeduc,a.employeeid,a.cutoffstart,a.cutoffend,CONCAT(b.lname, ', ', b.fname, ' ', b.mname) AS fullname,
                                  b.emp_sss,b.emp_pagibig,b.emp_philhealth,b.emp_tin,SUM(a.withholdingtax) as withholdingtax, a.withholdingtax as taxwithheld, b.campusid, b.deptid, c.company_description
                                FROM payroll_computed_table a 
                                  INNER JOIN employee b ON a.employeeid = b.employeeid
                                  INNER JOIN campus_company c ON c.company_description = b.company_campus
                                WHERE DATE_FORMAT(a.`cutoffstart`, '%m-%Y') = '$month_year' AND DATE_FORMAT(a.`cutoffend`, '%m-%Y') = '$month_year' $where_clause
                                 GROUP BY employeeid ORDER BY b.campusid, c.company_description,fullname,employeeid;");
        }
        // $query = $this->db->query("SELECT a.*,CONCAT(b.lname,', ',b.fname,' ',b.mname) AS fullname,b.emp_sss,b.`emp_pagibig`,b.`emp_philhealth`
        // FROM payroll_process_contribution_collection a
        // INNER JOIN employee b ON a.`employeeid` = b.`employeeid`
        // $wC  ORDER BY employeeid")->result();
        // echo "<pre>"; print_r($this->db->last_query()); die; 
        return $query;
    }
	
	function empstathistoryquery($empid,$col)
	{
		$empstathistory = array("office2","deptid2","employmentstat2","positionid2","dateposition2", "salary2");
        $excol  = explode(',',$col);
		$a = "";
        foreach($excol as $str){
			if($a) $a .= ",";
			if($str == "managementid2")          $str = " IFNULL( b.description,'') as managementid2 ";
			if($str == "deptid2")           $str = " IFNULL( f.description,'') as deptid2 ";
			if($str == "employmentstat2")    $str = " IFNULL( d.description,'') as employmentstat2 ";
			if($str == "positionid2")            $str = " IFNULL( e.description,'') as positionid2 ";
			if($str == "dateposition2")	$str = "IFNULL( a.dateposition,'') as dateposition2";
            if($str == "office2") $str = "IFNULL( c.description,'') as office2";
            if($str == "salary2") $str = "IFNULL( a.salary,'') as salary2";
			$a .= $str; 
		}
			$return=$this->db->query("SELECT {$a}
                FROM employee_employment_status_history a
                LEFT JOIN code_managementlevel b ON a.`managementid`=b.`managementid`
                LEFT JOIN code_office c ON a.`deptid`=c.`code`
                LEFT JOIN code_status d ON a.`employeestat`=d.`code`
                LEFT JOIN code_position e ON a.`positionid`=e.`positionid`
                LEFT JOIN code_department f ON a.office = f.code
				WHERE employeeid = '{$empid}'
                ORDER BY `timestamp` DESC ")->result();

		return $return;
	}
	
	function childrenquery($empid,$col)
	{
		$children = array("childname","childbday","childage");
		$excol  = explode(',',$col);
		$b = "";
        foreach($excol as $str){
			if($b) $b .= ",";
			if($str == "childname")          $str = " IFNULL(name,'') as childname ";
			if($str == "childbday")           $str = " IFNULL(birthdate,'') as childbday ";
			if($str == "childage")      $str = " IFNULL(age,'') as childage ";
			$b .= $str; 
		}
			$return=$this->db->query("select {$b} from employee_children WHERE employeeid = '{$empid}'")->result();
							
		return($return);
	}
	
	function taxDependentsquery($empid,$col)
	{
		$taxDependents = array (   "tdname","tdrelation","tdaddress","tdcontact","tdbdate","tdlegitimate");
		$excol  = explode(',',$col);
		$c = "";
        foreach($excol as $str){
			if($c) $c .= ",";
			if($str == "tdname")          $str = " IFNULL(legitimate_name,'') as tdname ";
			if($str == "tdrelation")           $str = " IFNULL(legitimate_relation,'') as tdrelation ";
			if($str == "tdaddress")           $str = " IFNULL(legitimate_address,'') as tdaddress ";
			if($str == "tdcontact")           $str = " IFNULL(legitimate_contactno,'') as tdcontact ";
			if($str == "tdbdate")           $str = " IFNULL(legitimate_bdate,'') as tdbdate ";
			if($str == "tdlegitimate")           $str = " IFNULL(legit,'') as tdlegitimate ";
			$c .= $str;  
		}
			$return=$this->db->query("select {$c} from employee_legitimate_relations WHERE employeeid = '{$empid}'")->result();
							
		return($return);
	}
    
    # for ica-hyperion 21578
    # by justin (with e)
    function getDeptHead($col="", $code=""){
        $head = "";
        $res = $this->db->query("SELECT $col FROM code_office WHERE code='$code'");
        if($res->num_rows() > 0) $head = $res->row(0)->$col;
        return $head;
    }

    function getVPFinanceHEAD($empid)
    {
         $VPFullname="";
        $query = $this->db->query("SELECT  CONCAT(fname,' ',mname,' ',lname) AS fullname  FROM employee  WHERE employeeid='$empid' LIMIT 1")->result();
        foreach ($query as $data) {
            $VPFullname = $data->fullname;
        }
        return $VPFullname;
    }

    function SSSContributionPerMY($pfrom,$pto,$pyearfrom,$pyearto,$empid,$campusid ="", $company="",$tnt="")
    {
        $wC = "";
        // if ($empid) {
        //     $wC .= " AND a.employeeid='$empid'";
        // }
        if ($campusid && $campusid != 'all' && $campusid != 'All') {
            $wC .= " AND b.campusid='$campusid'";
        }
        if ($company && $company != 'all' && $company != 'All') {
            $wC .= " AND b.company_campus=" . $this->db->escape($company) . "";
        }
        if($tnt){
            if($tnt != "trelated") $wC .= " AND b.teachingtype='$tnt'";
            else $wC .= " AND b.teachingtype = 'teaching' AND trelated = '1'";
        }
        if($empid) $wC .=  " AND b.employeeid IN ($empid)";

        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }
        $wC .= $utwc;
        $query =  $this->db->query("SELECT a.id,CONCAT(b.fname,' ',b.mname,' ',b.lname) AS fullname,b.`emp_sss` AS sssnumber ,b.employeeid,a.fixeddeduc,a.cutoffstart,a.cutoffend,c.or_number,c.datepaid, b.campusid, b.company_campus FROM payroll_computed_table a INNER JOIN employee b ON(a.employeeid = b.employeeid) INNER JOIN payroll_computed_ee_er c  ON (a.`id` = c.`base_id`) WHERE fixeddeduc <> '' AND MONTH(cutoffstart) BETWEEN '$pfrom' AND '$pto' AND MONTH(cutoffend) BETWEEN '$pfrom' AND '$pto' AND YEAR(cutoffstart) BETWEEN '$pyearfrom' AND '$pyearto' AND YEAR(cutoffend) BETWEEN '$pyearfrom' AND '$pyearto' AND a.status='PROCESSED' AND a.bank <> '' AND c.`code_deduction` = 'SSS' $wC ")->result();
        // echo "<pre>"; print_r($this->db->last_query()); die;
        return $query;
    }

    function getSSSContribution($id,$sss,$type)
    {
        $ee = $er = $ec = 0;
        if ($type == "ec") {
            $query = $this->db->query("SELECT b.`EE`,b.`EC` ,b.`ER` FROM payroll_computed_table a INNER JOIN payroll_computed_ee_er b ON (a.`id` = b.`base_id`) WHERE b.`base_id`='$id' AND b.`code_deduction` ='SSS' LIMIT 1 ")->result();

            if($query){
              foreach($query as $row){
                $ee = $row->EE;
                $er = $row->ER;
                $ec = $row->EC;
              }
            }
        }
        else if ($type == "totalsss") {
            $query = $this->db->query("SELECT b.`EE`,b.`EC` ,b.`ER` FROM payroll_computed_table a INNER JOIN payroll_computed_ee_er b ON (a.`id` = b.`base_id`) WHERE b.`base_id`='$id' AND b.`code_deduction` ='SSS'  LIMIT 1 ")->result();

            if($query){
              foreach($query as $row){
                  $ee = $row->EE;
                  $er = $row->ER;
                  $ec = $row->EC;
                }
            }
        }    
       
       return array($ee, $er, $ec);
    }

    function philhealthContributionPerMY($pfrom,$pto,$pyearfrom,$pyearto,$empid,$campusid = "", $company= '',$tnt='')
    {
        $wC = "";
        // if ($empid) {
        //     $wC .= " AND a.employeeid='$empid'";
        // }
        if ($campusid && $campusid != 'all' && $campusid != 'All') {
            $wC .= " AND b.campusid='$campusid'";
        }
        if ($company && $company != 'all' && $company != 'All') {
            $wC .= " AND b.company_campus=" . $this->db->escape($company) . "";
        }
        if($tnt){
            if($tnt != "trelated") $wC .= " AND b.teachingtype='$tnt'";
            else $wC .= " AND b.teachingtype = 'teaching' AND trelated = '1'";
        }
        if($empid) $wC .=  " AND b.employeeid IN ($empid)";
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }
        $wC .= $utwc;
     $query =  $this->db->query("SELECT a.id,CONCAT(b.fname,' ',b.mname,' ',b.lname) AS fullname,b.`emp_philhealth` AS philhealthnumber ,b.employeeid,a.fixeddeduc,a.cutoffstart,a.cutoffend,c.or_number,c.datepaid, b.campusid, b.company_campus FROM payroll_computed_table a INNER JOIN employee b ON(a.employeeid = b.employeeid) INNER JOIN payroll_computed_ee_er c  ON (a.`id` = c.`base_id`) WHERE fixeddeduc <> '' AND MONTH(cutoffstart) BETWEEN '$pfrom' AND '$pto' AND MONTH(cutoffend) BETWEEN '$pfrom' AND '$pto' AND YEAR(cutoffstart) BETWEEN '$pyearfrom' AND '$pyearto' AND YEAR(cutoffend) BETWEEN '$pyearfrom' AND '$pyearto' AND a.status='PROCESSED' AND a.bank <> '' AND c.`code_deduction` = 'PHILHEALTH' $wC ")->result();

        return $query;
    }

    function getphilhealthContribution($id,$philhealth,$type)
    {
        $return = "";
        if ($type == "er") {
            $query = $this->db->query("SELECT b.`EE`,b.`EC` ,b.`ER` FROM payroll_computed_table a INNER JOIN payroll_computed_ee_er b ON (a.`id` = b.`base_id`) WHERE b.`base_id`='$id' AND b.`code_deduction` ='PHILHEALTH' LIMIT 1 ")->result();
            foreach ($query as $res) {
                $return = $res->ER?$res->ER:"0.00";
            }
        }
        else if ($type == "totalphilhealth") {
            $query = $this->db->query("SELECT b.`EE`,b.`EC` ,b.`ER` FROM payroll_computed_table a INNER JOIN payroll_computed_ee_er b ON (a.`id` = b.`base_id`) WHERE b.`base_id`='$id' AND b.`code_deduction` ='PHILHEALTH' LIMIT 1 ")->result();
            foreach ($query as $res) {
                $return = $res->EE ? $res->EE :"0.00";
            }
        }    

        return $return?$return:"0.00";
    }
    function getpagibigContribution($id,$philhealth,$type){
        $return = "";
        if ($type == "er") {
            $query = $this->db->query("SELECT b.`EE`,b.`EC` ,b.`ER` FROM payroll_computed_table a INNER JOIN payroll_computed_ee_er b ON (a.`id` = b.`base_id`) WHERE b.`base_id`='$id' AND b.`code_deduction` ='PAGIBIG' LIMIT 1 ")->result();
            foreach ($query as $res) {
                $return = $res->ER?$res->ER:"0.00";
            }
        }
        else if ($type == "totalpagibig") {
            $query = $this->db->query("SELECT b.`EE`,b.`EC` ,b.`ER` FROM payroll_computed_table a INNER JOIN payroll_computed_ee_er b ON (a.`id` = b.`base_id`) WHERE b.`base_id`='$id' AND b.`code_deduction` ='PAGIBIG' LIMIT 1 ")->result();
            foreach ($query as $res) {
                $return = $res->EE ? $res->EE :"0.00";
            }
        }    

        return $return?$return:"0.00";

    }
    function hdmfContributionPerMY($pfrom,$pto,$pyearfrom,$pyearto,$empid,$campusid = "",$company = "",$tnt="")
    {
        $wC = "";
        if ($campusid && $campusid != 'all' && $campusid != 'All') {
            $wC .= " AND b.campusid='$campusid'";
        }
        if ($company && $company != 'all' && $company != 'All') {
            $wC .= " AND b.company_campus=" . $this->db->escape($company) . "";
        }
        if($tnt){
            if($tnt != "trelated") $wC .= " AND b.teachingtype='$tnt'";
            else $wC .= " AND b.teachingtype = 'teaching' AND trelated = '1'";
        }
        if($empid) $wC .=  " AND b.employeeid IN ($empid)";
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }
        $wC .= $utwc;
        $query =  $this->db->query("SELECT a.id,CONCAT(b.fname,' ',b.mname,' ',b.lname) AS fullname,b.`emp_pagibig` AS pagibignumber ,b.employeeid,a.fixeddeduc,a.cutoffstart,a.cutoffend,c.or_number,c.datepaid, b.campusid, b.company_campus FROM payroll_computed_table a INNER JOIN employee b ON(a.employeeid = b.employeeid) INNER JOIN payroll_computed_ee_er c  ON (a.`id` = c.`base_id`) WHERE fixeddeduc <> '' AND MONTH(cutoffstart) BETWEEN '$pfrom' AND '$pto' AND MONTH(cutoffend) BETWEEN '$pfrom' AND '$pto' AND YEAR(cutoffstart) BETWEEN '$pyearfrom' AND '$pyearto' AND YEAR(cutoffend) BETWEEN '$pyearfrom' AND '$pyearto' AND a.status='PROCESSED' AND a.bank <> '' AND c.`code_deduction` = 'PAGIBIG' $wC ")->result();

        return $query;
        
    }
    # end for ica-hyperion 21578

    # for ica-hyperion 21655
    # by justin (with e)
    function getRDCEmpList($division = '', $department = '', $cutoff, $status ='', $campus='', $company='', $employeeid='', $tnt=''){
        $whereClause  = ($department) ? "AND b.deptid='$department' " : "";
        $whereClause .= ($division) ? "AND b.managementid='$division'" : "";
        if($tnt){
            if($tnt != "trelated") $whereClause .= " AND b.teachingtype='$tnt'";
            else $whereClause .= " AND b.teachingtype = 'teaching' AND trelated = '1'";
        }
        $orderby = "";
        if($employeeid) $whereClause .=  " AND b.employeeid IN ($employeeid)";
        if($campus && $campus != 'all' && $campus != 'All') $whereClause .= " AND b.campusid = '$campus'";
        if($company && $company != 'all' && $company != 'All') $whereClause .= " AND b.company_campus = " . $this->db->escape($company) . " ";
        $orderby = 'b.office, b.lname,campusid,b.company_campus,deptid';
        

        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }
        $whereClause .= $utwc;
        $cutoff_to_find = "'" . implode ( "', '", $cutoff ) . "'";
        $q_emplist = $this->db->query("SELECT CONCAT(b.lname, ', ', b.fname, ' ', b.mname) AS fullname, lname, fname, mname, c.code AS dept_code,c.description AS dept_desc, d.code AS campus_code, d.description AS campus_desc, b.emp_sss, b.emp_pagibig, b.emp_philhealth, b.emp_tin, b.emp_peraa, a.*, b.company_campus
                                        FROM payroll_computed_table a
                                        INNER JOIN employee b ON b.employeeid = a.employeeid
                                        LEFT JOIN code_office c ON c.code = b.deptid
                                        LEFT JOIN code_campus d ON d.code = b.campusid
                                        INNER JOIN campus_company e ON e.company_description = b.company_campus
                                        WHERE a.status = '$status' AND DATE_FORMAT(a.cutoffstart, '%Y-%m') IN ($cutoff_to_find) $whereClause
                                        ORDER BY $orderby")->result();
        
        return $q_emplist;
    }

    function findRDCEEER($pct_id, $code_deduction){

        $q_ee_er = $this->db->query("SELECT * FROM payroll_computed_ee_er WHERE base_id='$pct_id' AND code_deduction='$code_deduction';")->result();
        return $q_ee_er;
    }
    # end for ica-hyperion 21655

    function getIncomeDeminimiss(){
        $this->db->from("payroll_income_config");
        $this->db->where('incomeType', 'deminimiss');
        $query = $this->db->get();
        return $query->result();
    }
    function getIncome(){
        $this->db->from("payroll_income_config");
        $query = $this->db->get();
        return $query->result();
    }
    function getDeductionConfig(){
        $this->db->from("payroll_deduction_config");
        $query = $this->db->get();
        return $query->result();
    }
 
    function getDeductionConfigArr(){
        $deduction = array();

        $q_deduction = $this->db->query("SELECT * FROM payroll_deduction_config ")->result();
        foreach ($q_deduction as $row) $deduction[] = $row->id;

        return $deduction;
    }
 
    function getOtherIncome(){
        $this->db->from("payroll_income_config");
        $this->db->where('incomeType', 'other');
        $this->db->or_where('incomeType', '');
        $query = $this->db->get();
        return $query->result();
    }

    function save_payrollregister_filter($data, $type){
        $code = "";
        if(isset($data['selectalltdeminimis'])){
            unset($data['selectalltdeminimis']);
        }
        foreach($data as $key => $row){
            $code .= $key.",";
        }
        $this->db->query("INSERT INTO payroll_register_history (code, filter_type) VALUES ('$code','$type') ");
    }

    function getFilterHistory($filter_type){
        $query = $this->db->query("SELECT code FROM payroll_register_history WHERE filter_type = '$filter_type' ORDER BY timestamp DESC LIMIT 1");
        if($query->num_rows() > 0) return $this->db->query("SELECT code FROM payroll_register_history WHERE filter_type = '$filter_type' ORDER BY timestamp DESC LIMIT 1")->row()->code;
        else return false;
    }

    function getEmployeeList($employeeid="", $campusid="", $deptid="", $teachingtype="", $is_show_resign=false){
        $where_clause = "";

        if($employeeid)     $where_clause .= "a.employeeid='$employeeid' ";
        if($campusid)       $where_clause .= (($where_clause) ? "AND " : ""). "a.campusid='$campusid' ";
        if($deptid)         $where_clause .= (($where_clause) ? "AND " : ""). "a.deptid='$deptid' ";
        if($teachingtype)   $where_clause .= (($where_clause) ? "AND " : ""). "a.teachingtype='$teachingtype' ";
        if(!$is_show_resign) $where_clause .= (($where_clause) ? "AND " : ""). "(a.dateresigned = '1970-01-01' OR a.dateresigned IS NULL OR a.dateresigned = '0000-00-00') ";

        $where_clause = (($where_clause) ? "WHERE " : "") ."". $where_clause;
        return $this->db->query("SELECT a.*, CONCAT(a.lname, ', ', a.fname, ' ', a.mname) AS fullname, b.description AS dept_desc, c.description AS campus_desc
                                 FROM employee a
                                 LEFT JOIN code_office b ON b.code = a.deptid
                                 LEFT JOIN code_campus c ON c.code = a.campusid
                                 $where_clause
                                 ORDER BY fullname
                                 ");
    }

    function getDateIncluded($dfrom, $dto){
        $date_list = array();
        $base_date = $dfrom;

        $no_days = $this->dateDifference($dfrom, $dto);
        $date_list[] = $base_date;
        if($no_days > 0){
            for($i = 1; $i <= $no_days; $i++ ){
                $date_list[] = date('Y-m-d', strtotime($base_date. " + $i days"));
            }
        }

        return $date_list;
    }

    function dateDifference($from_date , $to_date , $differenceFormat = '%a' ){
        $start_date = date_create($from_date);
        $end_date   = date_create($to_date);
        
        $interval   = date_diff($start_date, $end_date);
        
        return $interval->format($differenceFormat);
    }

    function getMonthList($date_list){
        $list = array();

        foreach ($date_list as $date) $list[date("m", strtotime($date))] = date("F Y", strtotime($date));

        return $list;
    }

    function convertTimeToNumber($value, $revert=false){
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

    function getAbsentOBCorrectionReport($dfrom, $dto, $type){
        $type_arr = array("ABSENT", "DIRECT", "CORRECTION");
        $data = array();

        if(!$type){
            foreach ($type_arr as $type) {
                $where_clause = ($type == "ABSENT") ? "a.leavetype = '$type'" : "a.othertype = '$type'";
                $table = ($type == "ABSENT") ? "leave_request" : "ob_request";

                $data[$type] = $this->db->query("SELECT a.*, CONCAT(b.lname, ', ', b.fname, ' ', b.mname) AS fullname, c.description AS position_desc, b.deptid
                                                 FROM $table a
                                                 LEFT JOIN employee b ON b.employeeid = a.employeeid
                                                 LEFT JOIN code_position c ON c.positionid = b.positionid
                                                 WHERE $where_clause AND a.dateapplied BETWEEN '$dfrom' AND '$dto';")->result_array();
            }
        }else{
            $where_clause = ($type == "ABSENT") ? "a.leavetype = '$type'" : "a.othertype = '$type'";
            $table = ($type == "ABSENT") ? "leave_request" : "ob_request";

            $data[$type] = $this->db->query("SELECT a.*, CONCAT(b.lname, ', ', b.fname, ' ', b.mname) AS fullname, c.description AS position_desc, b.deptid
                                             FROM $table a
                                             LEFT JOIN employee b ON b.employeeid = a.employeeid
                                             LEFT JOIN code_position c ON c.positionid = b.positionid
                                             WHERE $where_clause AND a.dateapplied BETWEEN '$dfrom' AND '$dto';")->result_array();
        }

        return $data;
    }

    function showDetailedAttendance($fv, $datesetfrom, $datesetto, $category){
        $where_clause = '';
        $selected_category = "a.*";
        if($category) $selected_category = " $category "." AS hours";
        if($category == "overtime") $selected_category .= " ,ot_amount, ot_type";
        if($fv) $where_clause = " AND b.employeeid = '$fv' ";
        if($category == "lateut") $selected_category = " late, undertime ";

        if($category != "lateut") $where_clause .= " AND $category != ''";
        if($category == "att_adj" || $category == "att_terminal") return FALSE;
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }

        $batchaccess = $this->extras->getBatchAccess();
        if ($batchaccess && !in_array("all", explode(",", $batchaccess))) {
            $where_clause .= " AND a.employeeid IN (SELECT d.employeeid FROM payroll_batch_emp d WHERE FIND_IN_SET (base_id,'$batchaccess') ) ";
        }
        
        $where_clause .= $utwc;

        $data = $this->db->query("SELECT $selected_category, b.`employeeid`, a.`sched_date`, CONCAT(lname, ',', fname, ',', mname) AS fullname, deptid, campusid, c.`description` FROM employee_attendance_detailed a INNER JOIN employee b  ON b.`employeeid` = a.employeeid LEFT JOIN code_office c ON c.`code` = b.`office` WHERE sched_date BETWEEN '$datesetfrom' AND '$datesetto'  $where_clause AND (late != '' OR undertime != '' OR absents != '' OR overtime != '') ORDER BY c.`description` ");
        // echo "<pre>"; print_r($this->db->last_query()); die;
        if($data->num_rows() > 0) return $data->result_array();
        else return FALSE;
    }

    function getEmployeeListFromPayrollComputedTable($employeeid="", $teaching_type="", $cutoff_start="", $cutoff_end="", $status="", $is_have_income = false, $is_have_other_deduc=false, $is_have_loan = false, $categ = '', $campus='', $company=''){
        if($cutoff_start && $cutoff_end && $categ == "LOAN"){
          $cutoff_start = date("F~~Y", strtotime($cutoff_start));
          $cutoff_end = date("F~~Y", strtotime($cutoff_end));
        }
        $where_clause = "";
        if($employeeid) $where_clause .=  " a.employeeid IN ($employeeid)";
        if($teaching_type) $where_clause .= (($where_clause) ? "AND " : "") ."b.teachingtype='$employeeid' ";
        if($cutoff_start && $cutoff_end && $categ == "DEDUCTION") $where_clause .= (($where_clause) ? "AND " : "") ."a.cutoffstart='$cutoff_start' AND a.cutoffend='$cutoff_end' ";
        if($cutoff_start && $cutoff_end && $categ == "LOAN") $where_clause .= (($where_clause) ? "AND " : "") ."DATE_FORMAT(a.cutoffstart, '%M~~%Y')='$cutoff_start' AND DATE_FORMAT(a.cutoffend, '%M~~%Y')='$cutoff_end' ";
        if($is_have_income) $where_clause .= (($where_clause) ? "AND " : "") ."a.income <> '' ";
        if($is_have_other_deduc) $where_clause .= (($where_clause) ? "AND " : "") ."a.otherdeduc <> '' ";
        if($is_have_loan) $where_clause .= (($where_clause) ? "AND " : "") ."a.loan <> '' ";
        if($campus && $campus != "All") $where_clause .= (($where_clause) ? "AND " : "") ."b.campusid = '$campus'";
        if($company && $company =! 'all') $where_clause .= (($where_clause) ? "AND " : "") ."b.company_campus = '$company'";
        $where_clause = ($where_clause) ? "WHERE $where_clause" : "";

        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }
        $where_clause .= $utwc;
        return $this->db->query("SELECT CONCAT(b.lname, ', ', b.fname, ' ', b.mname) AS fullname, b.deptid, c.description AS office_desc, e.description AS dept_desc, b.campusid, c.description AS campus_desc, b.teachingtype, a.*,d.company_description
                                 FROM payroll_computed_table a
                                 INNER JOIN employee b ON b.employeeid = a.employeeid
                                 LEFT JOIN code_office c ON c.code = b.office
                                 LEFT JOIN code_department e ON e.code = b.deptid
                                 INNER JOIN campus_company d ON d.company_description = b.company_campus
                                 $where_clause
                                 ORDER BY b.campusid,d.company_description,fullname;")->result();
    }


    function getOtherIncomeEmployeeList($employeeid=""){
        $where_clause = ($employeeid) ? "AND FIND_IN_SET(a.employeeid, '$employeeid')" : "";

        /*return $this->db->query("SELECT CONCAT(b.lname, ', ', b.fname, ' ', b.mname) AS fullname, b.deptid, c.description AS dept_desc, b.teachingtype, d.description AS income_desc, a.*  
                                 FROM other_income a
                                 INNER JOIN employee b ON b.employeeid = a.employeeid
                                 LEFT JOIN code_office c ON c.code = b.deptid
                                 LEFT JOIN payroll_income_config d ON d.id = a.other_income
                                 $where_clause
                                 ORDER BY fullname, income_desc;")->result();*/
        return $this->db->query("SELECT a.employeeid AS empID, CONCAT(a.lname, ', ', a.fname, ' ', a.mname) AS fullname, a.deptid, c.description AS dept_desc, a.teachingtype, d.description AS income_desc, b.*
                                 FROM employee a
                                 LEFT JOIN other_income b ON b.employeeid = a.employeeid
                                 LEFT JOIN code_office c ON c.code = a.deptid
                                 LEFT JOIN payroll_income_config d ON d.id = b.other_income
                                 WHERE (dateresigned = '1970-01-01' OR dateresigned IS NULL OR dateresigned = '0000-00-00') $where_clause
                                 ORDER BY fullname, income_desc;")->result();
    }

    function getDeminimissIncome(){
        $income = array();

        $q_income = $this->db->query("SELECT * FROM payroll_income_config WHERE incomeType='deminimiss'")->result();
        foreach ($q_income as $row) $income[] = $row->id;

        return $income;
    }

    function getAllCampus(){
        $campus_list = array();

        $q_campus = $this->db->query("SELECT code, description FROM code_campus;")->result();
        foreach ($q_campus as $row) $campus_list[$row->code] = $row->description;

        return $campus_list;
    }

    function getAllDepartment(){
        $department_list = array();

        $q_campus = $this->db->query("SELECT code, description FROM code_department ")->result();
        foreach ($q_campus as $row) $department_list[$row->code] = $row->description;

        return $department_list;
    }

    function getAllOffice(){
        $department_list = array();

        $q_campus = $this->db->query("SELECT code, description FROM code_department ")->result();
        foreach ($q_campus as $row) $department_list[$row->code] = $row->description;

        return $department_list;
    }

    function getPayrollConfig($table_key){
        $table_arr = array(
            "income" => "payroll_income_config",
            "deduction" => "payroll_deduction_config",
            "loan" => "payroll_loan_config"
        );
        $table = $table_arr[$table_key];

        $config = array();
        $q_payroll_config = $this->db->query("SELECT id, description FROM $table")->result();

        foreach ($q_payroll_config as $row) $config[$row->id] = $row->description;

        return $config;
    }

    function getPayrollConfigID($table_key){
        $table_arr = array(
            "income" => "payroll_income_config",
            "deduction" => "payroll_deduction_config",
            "loan" => "payroll_loan_config"
        );
        $table = $table_arr[$table_key];

        $config = array();
        $q_payroll_config = $this->db->query("SELECT id, description FROM $table")->result();

        foreach ($q_payroll_config as $row) $config[] = $row->id;

        return $config;
    }

    function getLoanBalance($employeeid, $code, $date, $forReport = ''){
        $balance = 0;
        $where_clause = '';
        if($forReport) $where_clause = " AND DATE_FORMAT(b.timestamp, '%M~~%Y')='$date' ";
        else $where_clause = "  AND DATE(b.timestamp) <= '$date' ";

        $q_balance = $this->db->query("SELECT b.balance
                                       FROM employee_loan a 
                                       LEFT JOIN employee_loan_payment b ON b.base_id = a.id
                                       WHERE a.employeeid='$employeeid' AND a.code_loan='$code' $where_clause 
                                       ORDER BY b.timestamp DESC 
                                       LIMIT 1;")->result();
        foreach ($q_balance as $row) $balance = $row->balance;
        return $balance;
    }

    function getSetupForPayrollIncome($col, $id){
        $result = '';
        $q_income = $this->db->query("SELECT $col AS selCol FROM payroll_income_config WHERE id='$id';")->result();
        foreach ($q_income as $res) $result = $res->selCol;

        return $result;
    }

    function getPayrollComputedData($cutoffstart, $cutoffend, $status='', $teachingtype='', $campus='', $sortby='', $company='', $employeeid=''){
        $where_clause = "";
        if($status) $where_clause .= "AND a.status='$status' ";
        if($employeeid) $where_clause .=  " AND b.employeeid IN ($employeeid)";
        if($teachingtype){
            if($teachingtype != "trelated") $where_clause .= " AND b.teachingtype='$teachingtype'";
            else $where_clause .= " AND b.teachingtype = 'teaching' AND trelated = '1'";
        }
        if($campus && $campus != 'all' && $campus != 'All') $where_clause .= " AND b.campusid =  '$campus'";
        if($company && $company != 'all' && $company != 'All') $where_clause .= ' AND b.company_campus =  "'.$company.'"';
        if($sortby == "department"){
            $orderby = 'b.office, b.lname';
        }else{
            $orderby = 'b.lname,';
        }

        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (b.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (b.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (b.deptid, '$utdept') OR FIND_IN_SET (b.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (b.campusid,'$usercampus') ";
        }
        $where_clause .= $utwc;

        return $this->db->query("SELECT REPLACE(CONCAT(b.LName,', ',b.FName,' ',b.MName), 'Ã‘', 'Ñ') AS fullname, b.teachingtype, b.deptid, b.office, c.description AS office_desc, e.description AS dept_desc, b.campusid, a.*, d.company_description
                                 FROM payroll_computed_table a
                                 LEFT JOIN employee b ON b.employeeid = a.employeeid
                                 LEFT JOIN code_office c ON c.code = b.office
                                 LEFT JOIN code_department e ON e.code = b.deptid
                                 JOIN campus_company d ON d.company_description = b.company_campus
                                 WHERE cutoffstart='$cutoffstart' AND cutoffend='$cutoffend' $where_clause
                                 ORDER BY d.campus_code, d.company_description, fullname;")->result();
    }

    function getPayrollIncomeAdjustment($selected_income=''){
        $data = array();
        $where_clause = ($selected_income) ? "AND FIND_IN_SET(id, '$selected_income')" : "";

        $q_income = $this->db->query("SELECT * FROM payroll_income_config WHERE (description LIKE '% adj%' OR description LIKE '%adjustment%' OR description LIKE '%adj %') $where_clause ORDER BY description;")->result();
        foreach ($q_income as $row) {
            $data[$row->id] = array(
                "description" => $row->description,
                "taxable"     => $row->taxable
            );
        }
        return $data;
    }

    //Added may 29 2019 ken // modified 12/15/2020 add where_clause
    public function allEmpByDept($where_clause){
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
        $query = $this->db->query("SELECT a.*, CONCAT(a.lname,', ',a.fname,' ',a.mname) AS fullname  FROM employee a WHERE 1 $where_clause GROUP BY employeeid ORDER BY deptid,a.`lname` ASC")->result();
        return $query;
    }

    public function allEmpByGender($where_clause){
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
        //modified 1/11/21 remove //a.* change to //a.gender,a.employeeid
        $query = $this->db->query("SELECT a.gender,a.employeeid, CONCAT(a.lname,', ',a.fname,' ',a.mname) AS fullname  FROM employee a WHERE 1 $where_clause GROUP BY employeeid ORDER BY gender,a.`lname` ASC")->result();
        return $query;
    }

    public function agreement_report($where_clause){
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND b.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus) $utwc .= " AND FIND_IN_SET (a.campusid,'$usercampus') ";
        }
        $where_clause .= $utwc;
        $query = $this->db->query("SELECT a.employeeid, CONCAT(a.lname,', ',a.fname,' ',a.mname) AS fullname, b.timestamp, c.title  FROM employee a INNER JOIN agreement_logs b on a.employeeid = b.username INNER JOIN announcement c on b.announcement_id = c.id WHERE c.announcement = 'agreement' $where_clause GROUP BY employeeid ORDER BY gender, a.`lname` ASC")->result();
        return $query;
    }
    public function allEmpByPerfectAttd($datefrom, $dateto, $where_clause, $scheddatef, $dateappliedf){

//         $query = $this->db->query("SELECT 
//   a.*, CONCAT(a.lname,', ',a.fname,' ',a.mname) AS fullname 
// FROM
//   employee a
// WHERE (
//     dateresigned = '1970-01-01' 
//     OR dateresigned IS NULL 
//     OR dateresigned = '0000-00-00'
//   ) 
//   AND isactive = '1' AND employeeid NOT IN (SELECT employeeid FROM employee_attendance_detailed WHERE absents != '' AND absents != '0' AND sched_date BETWEEN '$datefrom' AND '$dateto') AND isactive = '1' AND employeeid NOT IN (SELECT employeeid FROM leave_request WHERE leavetype != '' AND leavetype != '0' AND dateapplied BETWEEN '$datefrom' AND '$dateto')")->result();
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
        $query = $this->db->query("
            SELECT
              a.*,CONCAT (a.lname, ', ', a.fname, ' ', a.mname) AS fullname
            FROM
              employee a
            WHERE (
                dateresigned = '1970-01-01'
                OR dateresigned IS NULL
                OR dateresigned = '0000-00-00'
              )
              $where_clause
              AND employeeid IN
              (SELECT
                employeeid
              FROM
                employee_attendance_detailed
              WHERE absents IN ('', '0')
                $scheddatef)
              OR employeeid IN
              (SELECT
                employeeid
              FROM
                leave_request
              WHERE leavetype IN ('', '0') 
               $dateappliedf) ORDER BY a.lname ASC
            ")->result();
        return $query;
    }

    public function allEmpByUsedLeave($leavetype=""){
        $where_clause = "";
        if($leavetype) $where_clause = " WHERE a.leavetype = '$leavetype'";

        $query = $this->db->query("SELECT  CONCAT(b.lname,', ',b.fname,' ',b.mname) AS fullname, a.leavetype, b.employeeid  FROM employee_leave_credit a INNER JOIN employee b ON a.employeeid = b.employeeid $where_clause GROUP BY b.employeeid ORDER BY leavetype,b.lname ASC")->result();
        return $query;
    }
    public function allEmpByUsedLeaveyear($leavetype=""){
        $where_clause = "";
        if($leavetype) $where_clause = " WHERE b.leavetype = '$leavetype'";

        $query = $this->db->query("SELECT CONCAT(a.lname, ',', a.fname, ',', a.mname) AS fullname,b.leavetype,b.employeeid, c.description, a.deptid,  d.nodays, CONCAT(e.fromdate, ',', e.todate) AS dateofexclusive, d.reason, b.balance
        FROM employee a 
        INNER JOIN employee_leave_credit b ON a.employeeid = b.employeeid 
        INNER JOIN code_position cON a.positionid = c.positionid
        INNER JOIN leave_app_base d ON a.`employeeid` = d.`applied_by`
        INNER JOIN leave_request e ON a.`employeeid` = e.`employeeid`
         $where_clause GROUP BY b.employeeid ORDER BY leavetype, a.lname ASC")->result();
        return $query;
    }
    // public function allEmpByLate($late="",$undertime=""){
    //     $where_clause = "";
    //     if($late) $where_clause = " WHERE a.late = ''";
    //     if($undertime) $where_clause = " WHERE a.undertime = ''";

    //     $query = $this->db->query("SELECT  CONCAT(b.lname,', ',b.fname,' ',b.mname) AS fullname, b.employeeid  FROM employee_attendance_detailed a INNER JOIN employee b ON a.employeeid = b.employeeid $where_clause GROUP BY b.employeeid ORDER BY b.lname ASC")->result();
    //     return $query;
    // }
    public function allEmpByLate($dfrom, $dto){
        $cutoff = explode("-", $dtr_cutoff);
        $query_date = $this->db->query("SELECT * FROM cutoff a INNER JOIN payroll_cutoff_config b ON a.`id` = b.`baseid` WHERE CutoffFrom = '$dfrom' AND CutoffTo = '$dto' ");
        if($query_date->num_rows() > 0) return date("F d, Y", strtotime($query_date->row()->startdate))." - ".date("F d, Y", strtotime($query_date->row()->enddate));
        else return date('Y-m-d');
    }
    public function allEmpByTardiness($late=""){
        $where_clause = "";
        if($late) $where_clause = " WHERE a.late = '$late'";
        $query = $this->db->query("SELECT  CONCAT(b.lname,', ',b.fname,' ',b.mname) AS fullname, b.employeeid  FROM employee_attendance_detailed a INNER JOIN employee b ON a.employeeid = b.employeeid $where_clause GROUP BY b.employeeid ORDER BY b.lname ASC")->result();
        return $query;
    }

    public function allEmpByCS($where_clause=""){
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
        $query = $this->db->query("SELECT a.*, CONCAT(a.lname,', ',a.fname,' ',a.mname) AS fullname  FROM employee a WHERE 1 $where_clause GROUP BY employeeid ORDER BY civil_status,a.`lname` ASC")->result();
        return $query;
    }

    public function emplistbyaccounttype($whereClause=""){
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (a.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (a.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (a.deptid, '$utdept') OR FIND_IN_SET (a.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND employeeid = 'nosresult'";
          $usercampus = $this->extras->getCampusUser();
          $utwc .= " AND FIND_IN_SET (campusid,'$usercampus') ";
        }
        $whereClause .= $utwc;
        return $this->db->query("SELECT CONCAT(a.lname, ', ', a.fname, ' ', a.mname) AS fullname, a.employeeid, c.description as usertype FROM employee a INNER JOIN user_info b ON a.employeeid = b.username LEFT JOIN user_type c ON b.user_type = c.code $whereClause order by a.lname, a.fname, a.mname")->result();
    }

    public function getCivilStatus($code){
        $query = $this->db->query("SELECT description FROM code_civil_status WHERE CODE = '$code'")->result();
        return $query;
    }

    public function allEmpByOffice($where_clause){
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
        $query = $this->db->query("SELECT a.*, CONCAT(a.lname,', ',a.fname,' ',a.mname) AS fullname  FROM employee a WHERE 1 $where_clause GROUP BY employeeid ORDER BY office,a.`lname` ASC")->result();
        return $query;
    }

    public function allEmpByEmpStat($where_clause=""){
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
        $query = $this->db->query("SELECT a.*, CONCAT(a.lname,', ',a.fname,' ',a.mname) AS fullname, b.description as description FROM employee a inner join code_status b on a.employmentstat = b.code WHERE 1 $where_clause GROUP BY employeeid ORDER BY employmentstat,a.`lname` ASC")->result();
        return $query;
    }


    public function allEmpByPosition($where_clause){
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
        $query = $this->db->query("SELECT a.*, CONCAT(a.lname,', ',a.fname,' ',a.mname) AS fullname  FROM employee a WHERE 1 $where_clause GROUP BY employeeid ORDER BY positionid,a.`lname` ASC ")->result();
        return $query;
    }

    public function allEmpAttendancePresent($date,$sort){
        $query = $this->db->query("SELECT a.`lname`,a.`fname`,a.`mname`,a.`deptid`,a.`office`,a.`employeeid` AS oldemployee,b.`userid`,b.`timein`,CONCAT(a.lname, ', ', a.fname, ' ', a.mname) AS fullname FROM employee a INNER JOIN timesheet b ON a.`employeeid` = b.`userid` AND b.`timein` LIKE '$date%' GROUP BY a.`employeeid` ORDER BY a.$sort,a.`lname` ASC ")->result();
        return $query;
    }

    public function allEmpAttendanceAbsent($date,$sort){
        $query = $this->db->query("SELECT a.`lname`,a.`fname`,a.`mname`,a.`deptid`,a.`office`,a.`employeeid` AS oldemployee,b.`userid`,b.`timein`,CONCAT(a.lname, ', ', a.fname, ' ', a.mname) AS fullname FROM employee a INNER JOIN timesheet b ON a.`employeeid` = b.`userid` AND b.`timein` NOT LIKE '$date%' GROUP BY a.`employeeid` ORDER BY a.$sort,a.`lname` ASC ")->result();
        return $query;
    }

    public function allEmpAttendance($sort){
        $query = $this->db->query("SELECT a.`employeeid` AS oldemployee, a.`deptid`,a.`office`, CONCAT(a.lname,', ',a.fname,' ',a.mname) AS fullname  FROM employee a GROUP BY employeeid ORDER BY $sort, a.lname ASC")->result();
        return $query;
    }

    public function getLeaveDate($employeeid){
        $query = $this->db->query("SELECT b.`dateto`, b.`type` FROM leave_app_emplist a INNER JOIN leave_app_base b ON a.`base_id` = b.`id` AND a.`employeeid` = '$employeeid'")->result_array();
        return $query;
    }

    public function getOBDate($employeeid){
        $query = $this->db->query("SELECT b.`dateto`, b.`type` FROM ob_app_emplist a INNER JOIN ob_app b ON a.`base_id` = b.`id` AND a.`employeeid` = '$employeeid'")->result_array();
        return $query;
    }

    public function LeaveOBChecker($employeeid,$date){
        if(sizeof($this->getOBDate($employeeid)) > 0){
            $query = $this->getOBDate($employeeid);
            $date_now = $date;
                if ($date_now > date($query[0]['dateto'])) {
                   return  $return = null;
                }else{
                    return $return = $this->employeemod->othLeaveDesc($query[0]['type']);
                }
        }else if(sizeof($this->getLeaveDate($employeeid)) > 0){
            $query = $this->getLeaveDate($employeeid);
            $date_now = $date;
                if ($date_now > date($query[0]['dateto'])){
                    return $return = null;
                }else{
                    return $return = $this->employeemod->othLeaveDesc($query[0]['type']);
                }
        }else return false;
    }

    public function allEmpBySalary(){
        $query = $this->db->query("SELECT a.`lname`, a.`fname`, a.`mname`, a.`employeeid` AS oldemployee, b.`employeeid`, b.`monthly`, CONCAT(a.lname,', ',a.fname,' ',a.mname) AS fullname FROM employee a LEFT JOIN payroll_employee_salary b ON a.`employeeid` = b.`employeeid` GROUP BY a.`employeeid` ORDER BY b.`monthly` IS NULL, a.`lname` ASC ")->result();
        return $query;
    }

    public function allEmpYearService($where_clause){
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
        $query = $this->db->query("SELECT a.`lname`, a.`fname`, a.`mname`, a.`employeeid` AS oldemployee, b.`employeeid`, b.`dateposition`,a.`year_service` AS year_service, CONCAT(a.lname,', ',a.fname,' ',a.mname) AS fullname, a.dateemployed FROM employee a LEFT JOIN employee_employment_status_history b ON a.`employeeid` = b.`employeeid` WHERE 1 $where_clause GROUP BY a.`employeeid` ORDER BY a.`lname` ASC")->result();
        return $query;
    }

    public function getOBlist($wc){
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (c.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (c.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (c.deptid, '$utdept') OR FIND_IN_SET (c.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND c.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus && $usercampus != 'null') $utwc .= " AND FIND_IN_SET (c.campusid,'$usercampus') ";
        }
        $wc .= $utwc;
        $query = $this->db->query("SELECT b.employeeid, CONCAT(c.lname, ', ', c.fname, ' ', c.mname) AS fullname, a.datefrom, a.dateto, a.timefrom, a.timeto, a.reason, c.office, c.deptid, t_date, timein, timeout, activity, obtypes, a.date_applied, b.base_id FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id INNER JOIN employee c ON b.employeeid = c.employeeid LEFT JOIN ob_timerecord d ON  d.base_id = b.base_id  WHERE 1 AND a.type = 'direct' $wc GROUP BY a.id ORDER BY c.lname ASC,  a.datefrom");

        // echo "<pre>"; print_r($this->db->last_query()); die;

        if($query->num_rows() > 0) return $query->result();
        else return false;
    }

    public function getdisabledaccount($wc){
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
        $wc .= $utwc;
        $query = $this->db->query("SELECT employeeid, CONCAT(lname, ', ', fname, ' ', mname) AS fullname, office, deptid, isactive, dateresigned2, resigned_reason,email FROM  employee WHERE (dateresigned2 !='1970-01-01' AND dateresigned2 !='0000-00-00') $wc ORDER BY lname ASC")->result();
        return $query;
    }

    public function office_dept_approver($wc){
        $query = $this->db->query("SELECT b.campus, a.type, a.managementid, a.description, b.dhead, b.divisionhead, b.phead, b.hrhead FROM code_office a INNER JOIN campus_office b ON a.code = b.base_code WHERE 1 $wc ORDER BY b.campus")->result();
        return $query;
    }

    public function modified_dra($wc){
        $query = $this->db->query("SELECT CONCAT(a.lname, ', ', a.fname, ' ', a.mname) AS fullname, b.* FROM employee a INNER JOIN data_request_history b ON a.employeeid = b.employeeid WHERE 1 $wc AND b.status='active' ORDER BY b.table")->result();
        return $query;
    }

    public function unmodified_201($wc, $dfrom, $dto){
      $query = $this->db->query("SELECT CONCAT(lname, ', ', fname, ' ', mname) AS fullname, employeeid FROM employee WHERE employeeid NOT IN (SELECT employeeid FROM data_request_history WHERE DATE_FORMAT(`timestamp`,'%Y-%m-%d') BETWEEN '$dfrom' AND '$dto') AND employeeid NOT IN (SELECT employeeid FROM empinfo_edit_trail WHERE DATE_FORMAT(`timestamp`,'%Y-%m-%d') BETWEEN '$dfrom' AND '$dto') $wc")->result();
       return $query;
    }

    public function getResignedEmployee($dfrom, $dto){
      return $this->db->query("SELECT employeeid, CONCAT(lname, ', ', fname, ' ', mname) AS fullname, dateresigned2, email, b.description AS department, c.description AS office, d.description AS campus, e.company_description AS campus_company, a.teachingtype, isactive FROM employee a
                              LEFT JOIN code_department b ON a.deptid = b.code
                              LEFT JOIN code_office c ON a.deptid = c.code
                              LEFT JOIN code_campus d ON a.campusid = d.code
                              LEFT JOIN campus_company e ON d.code = e.campus_code
                              WHERE dateresigned2 BETWEEN '$dfrom' AND '$dto' ORDER BY dateresigned2")->result();
    }

    public function summarize($text, $length, $splitOnWholeWords = true, $append = '...')
    {
        if (strlen($text) <= $length) return $text;
        $split = 0;
        if ($splitOnWholeWords)
        {
            if (ctype_space($text)) {
                $i = 0; $lplus1 = $length + 1;
                while (($i = strpos($text, ' ', $i + 1)) < $lplus1)
                {
                    if ($i === false) break;
                    $split = $i;
                }
                return wordwrap($text, $split, "<br>\n");
            }else{
                return wordwrap($text, $length, "<br>\n");
            }
        }
        else{
            return wordwrap($text, $length, "<br>\n");
        }

        // return substr($text, 0, $split).$append;
    }

    public function summ($text, $length, $append = '...', $splitOnWholeWords = true){
        if (strlen($text) <= $length) return $text;
        $split = 0;
        if ($splitOnWholeWords)
        {
            substr($text, 0, $length).$append;
        }
        else
            $split = $length;

        return substr($text, 0, $split).$append;
    }

    public function getEmployeelistwithTardiness($where_clause='', $dfrom='', $dto=''){
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
        $where_clause .= $utwc;
          return $this->db->query("SELECT employeeid, CONCAT(lname, ', ', fname, ' ', mname) AS fullname, deptid, campusid, office  FROM employee $where_clause AND employeeid IN (SELECT userid FROM timesheet_trail WHERE DATE(localtimein) BETWEEN '$dfrom' AND '$dto' UNION SELECT userid FROM webcheckin_history  WHERE DATE(localtimein) BETWEEN '$dfrom' AND '$dto' UNION SELECT userid FROM timesheet WHERE DATE(timein) BETWEEN '$dfrom' AND '$dto')")->result_array();
    }
    public function getEmployeeBanklist($wC){
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
        return $this->db->query("SELECT a.employeeid, CONCAT(a.lname, ', ', a.fname, ' ', a.mname) AS fullname, a.emp_bank, a.teachingtype, a.office, a.deptid FROM employee a WHERE 1 $wC ORDER BY a.lname")->result();
    }

    public function getEmployeelistwithAbsences($where_clause='', $dfrom='', $dto=''){
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
        $where_clause .= $utwc;
          return $this->db->query("SELECT employeeid, CONCAT(lname, ', ', fname, ' ', mname) AS fullname, deptid, campusid, office  FROM employee $where_clause AND employeeid IN (SELECT employeeid FROM employee_schedule_history WHERE DATE(dateactive) <= DATE(DATE_SUB('$dfrom',INTERVAL 1 DAY)))")->result_array();
    }

    public function getEmployeelistwithOvertimeOld($where_clause='', $dfrom='', $dto=''){
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
        $where_clause .= $utwc;
          return $this->db->query("SELECT employeeid, CONCAT(lname, ', ', fname, ' ', mname) AS fullname, deptid, campusid, office  FROM employee $where_clause AND employeeid IN (SELECT employeeid FROM overtime_request WHERE ((dfrom BETWEEN '$dfrom' AND '$dto') AND (dto BETWEEN '$dfrom' AND '$dto')))")->result_array();
    }

    public function getEmployeelistwithOvertime($where_clause='', $dfrom='', $dto=''){
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (c.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (c.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (c.deptid, '$utdept') OR FIND_IN_SET (c.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND c.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus && $usercampus != 'null') $utwc .= " AND FIND_IN_SET (c.campusid,'$usercampus') ";
        }
        $where_clause .= $utwc;
        $query = $this->db->query("SELECT b.employeeid, CONCAT(c.lname, ', ', c.fname, ' ', c.mname) AS fullname, c.deptid, c.campusid, c.office, a.dfrom, a.dto, a.tstart, a.tend, a.breaktime, a.total, a.approved_total, a.reason, b.status FROM ot_app a INNER JOIN ot_app_emplist b ON a.id = b.base_id INNER JOIN employee c ON b.employeeid = c.employeeid $where_clause AND ((a.dfrom BETWEEN '$dfrom' AND '$dto') AND (a.dto BETWEEN '$dfrom' AND '$dto')) GROUP BY a.id ORDER BY c.lname ASC,  a.dfrom");
        // echo "<pre>"; print_r($this->db->last_query()); die;
        if($query->num_rows() > 0) return $query->result_array();
        else return array();
    }

    public function getEmployeelistwithCorrection($where_clause='', $dfrom='', $dto=''){
        $utwc = '';
        $utdept = $this->session->userdata("department");
        $utoffice = $this->session->userdata("office");
        if($this->session->userdata("usertype") == "ADMIN"){
          if($utdept && $utdept != 'all') $utwc .= " AND  FIND_IN_SET (c.deptid, '$utdept')";
          if($utoffice && $utoffice != 'all') $utwc .= " AND  FIND_IN_SET (c.office, '$utoffice')";
          if(($utdept && $utdept != 'all') && ($utoffice && $utoffice != 'all')) $utwc = " AND  (FIND_IN_SET (c.deptid, '$utdept') OR FIND_IN_SET (c.office, '$utoffice'))";
          if(!$utdept && !$utoffice) $utwc =  " AND c.employeeid = 'nosresult'";
          $usercampus =  $this->extras->getCampusUser();
          if($usercampus && $usercampus != 'null') $utwc .= " AND FIND_IN_SET (c.campusid,'$usercampus') ";
        }
        $where_clause .= $utwc;
        $query = $this->db->query("SELECT b.employeeid, CONCAT(c.lname, ', ', c.fname, ' ', c.mname) AS fullname, c.deptid, c.campusid, c.office, b.base_id, b.status, a.applied_by FROM ob_app a INNER JOIN ob_app_emplist b ON a.id = b.base_id INNER JOIN employee c ON b.employeeid = c.employeeid $where_clause AND a.type = 'CORRECTION' AND ((a.datefrom BETWEEN '$dfrom' AND '$dto') AND (a.dateto BETWEEN '$dfrom' AND '$dto')) GROUP BY a.id ORDER BY c.lname ASC,  a.datefrom");

        if($query->num_rows() > 0) return $query->result_array();
        else return array();
    }

    public function getEmployeelistwithChangeSched($where_clause='', $dfrom='', $dto=''){
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
        $where_clause .= $utwc;
          return $this->db->query("SELECT b.employeeid, CONCAT(lname, ', ', fname, ' ', mname) AS fullname, deptid, campusid, office, b.reason, dfrom, dto, date_applied, a.status FROM change_sched_app a INNER JOIN change_sched_app_emplist b ON a.id = b.base_id INNER JOIN employee c ON b.employeeid = c.employeeid $where_clause AND ((dfrom BETWEEN '$dfrom' AND '$dto') AND (dto BETWEEN '$dfrom' AND '$dto'))")->result_array();
    }

    public function getEmployeelistwithCorrectionOld($where_clause='', $dfrom='', $dto=''){
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
        $where_clause .= $utwc;
          return $this->db->query("SELECT employeeid, CONCAT(lname, ', ', fname, ' ', mname) AS fullname, deptid, campusid, office  FROM employee $where_clause AND employeeid IN (SELECT employeeid FROM ob_request WHERE othertype = 'CORRECTION' AND ((fromdate BETWEEN '$dfrom' AND '$dto') AND (todate BETWEEN '$dfrom' AND '$dto')))")->result_array();
    }

    public function getEmployeebyuserid($empid=""){
        return $this->db->query("SELECT *, a.employeeid, CONCAT(a.lname, ', ', a.fname, ' ', a.mname) AS fullname  FROM employee a WHERE a.employeeid = '$empid' ORDER BY a.lname ASC");
    }

    public function holrenderedhour($where = "", $holstart = "", $holend = ""){
        $sql = "SELECT b.userid, a.date_from, a.date_to, a.halfday, c.office, c.deptid, c.campusid, c.teachingtype 
        FROM code_holiday_calendar a 
        INNER JOIN timesheet b ON b.timein BETWEEN a.date_from AND a.date_from + INTERVAL 1 DAY
        INNER JOIN employee c ON b.userid = c.employeeid $where GROUP BY b.userid ORDER BY c.lname ASC
        ";
        $q = $this->db->query($sql)->result_array();
        $holidayU = array();
        $hrperday = array();
        $timest = array();
        $emp = array();
        $timetotal = 0;
        $dateee = date("Y-m-d");
        foreach ($q as $key) {
                $empid = $key['userid'];
                // array_push($emp, $key['userid']);
                $code_calendar = $this->db->query("SELECT date_from,halfday FROM code_holiday_calendar WHERE date_from != '1970-01-01' AND date_from BETWEEN '$holstart' AND '$holend' ORDER BY date_from ASC")->result_array();
                foreach ($code_calendar as $calendar) {
                    $petsa = $calendar['date_from'];

                    $isholidayNew = $this->attcompute->isHolidayNew($empid,$petsa,$key['office'],$key['campusid'],$calendar['halfday'],$key['teachingtype']);
                    if ($isholidayNew) {
                        $gettimesheet = $this->db->query("SELECT * FROM timesheet a WHERE a.timein BETWEEN '$petsa' AND '$petsa' + INTERVAL 1 DAY AND a.userid = '$empid'")->result_array();
                    
                        
                        foreach ($gettimesheet as $timesheet) {
                            $date1 = new DateTime($timesheet['timeout']);
                            $date2 = new DateTime($timesheet['timein']);
                            $interval = date_diff($date2, $date1);
                            $int = $interval->format('%h:%i:%s');
                            
                            $holidaydate = date("Y-m-d", strtotime($timesheet['timein']));
                            if(!in_array($holidaydate, $holidayU)){

                                $timetotal = 0;
                                array_push($holidayU, $holidaydate);
                            }
                            $timetotal = $timetotal + (strtotime($int) - strtotime($dateee));
                            $hours = floor($timetotal / 3600);
                            $mins = floor($timetotal / 60 % 60);
                            $secs = floor($timetotal % 60);
                            $sample = $hours.":".$mins.":".$secs;

                            $timestore = date("H:i", strtotime($sample));

                            $emp[$empid][$petsa] = $timestore;
                            // array_push($emp, $empid[array($petsa=>$timestore)]);
                        }
                    }
                }
                $holidayU = array();
                // $emp = array();
        }
        return $emp;
    }

    public function getholidaybydate($date=''){
        return $this->db->query("SELECT holiday_id FROM code_holiday_calendar a 
            WHERE a.date_from = '$date'");
    }

    public function getholidayinfo($id=''){
        return $this->db->query("SELECT * FROM code_holidays a
            INNER JOIN code_holiday_type b ON a.holiday_type = b.holiday_type 
            WHERE a.holiday_id = '$id'");
    }

    public function login_history($where_clause){
      $query = $this->db->query("SELECT a.timestamp, a.ip, a.username, REPLACE(CONCAT(b.lastname,', ',b.firstname,' ',SUBSTR(b.middlename, 1, 1),'. '), 'Ã‘', 'Ñ') as fullname, b.timestamp AS datecreated, b.activation_stamp AS activation_date, b.status FROM login_attempts_hris a INNER JOIN user_info b ON a.username = b.username LEFT JOIN employee c ON c.employeeid = a.username WHERE a.timestamp >= b.activation_stamp AND a.status = 'success' $where_clause ORDER BY fullname");
      if($query->num_rows() > 0) return $query->result();
      else return false;
    }

    public function neverlog_reports($where_clause){
      $query = $this->db->query("SELECT username, REPLACE(CONCAT(lastname,', ',firstname,' ',SUBSTR(middlename, 1, 1),'. '), 'Ã‘', 'Ñ') as fullname, timestamp AS datecreated, activation_stamp AS activation_date, status, b.email,a.email as personalEmail, employeeid, c.description as department, d.description as office, e.description as campus
        FROM user_info b 
        LEFT JOIN employee a ON b.username = a.employeeid 
        LEFT JOIN code_department c ON c.code = a.deptid 
        LEFT JOIN code_office d ON d.code = a.office 
        LEFT JOIN code_campus e ON e.code = a.campusid 
        WHERE log_count = 0 AND activated = 0 $where_clause GROUP BY username ORDER BY fullname");
      if($query->num_rows() > 0) return $query->result();
      else return false;
    }

    public function lastlog_reports($where_clause){
      $query = $this->db->query("SELECT REPLACE(CONCAT(b.lastname,', ',b.firstname,' ',SUBSTR(b.middlename, 1, 1),'. '), 'Ã‘', 'Ñ') as fullname, b.timestamp AS datecreated, b.activation_stamp AS activation_date, b.log_date AS last_log, b.log_count,  b.status, b.username 
        FROM user_info b 
        LEFT JOIN employee a ON b.username = a.employeeid 
        WHERE activated = '1' AND log_count > 0 AND (log_date != '' OR log_date IS NOT NULL) $where_clause GROUP BY username ORDER BY fullname");
      // echo $this->db->last_query(); die;
      if($query){
        if($query->num_rows() > 0) return $query->result();
        else return false;
      }else{
        return false;
      }
      
    }

    public function trail201_report($where_clause = "",$whereemployeeid = ""){
        // EMPLOYEE ID BASED OPTIMIZE
        if($where_clause != ""){
            $query = $this->db->query("SELECT REPLACE(CONCAT(b.lname,', ',b.fname,' ',SUBSTR(b.mname, 1, 1),'. ', '|', b.employeeid), 'Ã‘', 'Ñ') AS fullnameid FROM employee AS b 
            LEFT JOIN empinfo_edit_trail AS t ON t.employeeid=b.`employeeid` 
            LEFT JOIN user_info AS u ON u.username=b.employeeid $where_clause GROUP BY b.employeeid ORDER BY b.lname ASC");
        }else{
            $query = $this->db->query("SELECT REPLACE(CONCAT(b.lname,', ',b.fname,' ',SUBSTR(b.mname, 1, 1),'. ', '|', b.employeeid), 'Ã‘', 'Ñ') AS fullnameid, u.type ,t.* FROM employee AS b 
            LEFT JOIN empinfo_edit_trail AS t ON t.employeeid=b.`employeeid` 
            LEFT JOIN user_info AS u ON u.username=b.employeeid $whereemployeeid GROUP BY b.employeeid ORDER BY b.lname,t.timestamp ASC");
        }
        if($query->num_rows() > 0) return $query->result_array();
        else return false;
    }

    public function headAccess_report($where_clause = ""){
        // EMPLOYEE ID BASED OPTIMIZE
        $query = $this->db->query(
        "SELECT REPLACE(CONCAT(b.lname,', ',b.fname,' ',SUBSTR(b.mname, 1, 1),'. ', '|', b.employeeid), 'Ã‘', 'Ñ') AS fullnameid, u.type,b.campusid, b.deptid,b.aimsdept,b.company_campus,b.office,u.user_type FROM employee AS b 
        LEFT JOIN user_info AS u ON u.username=b.employeeid $where_clause GROUP BY b.employeeid ORDER BY b.lname ASC");
    
        // var_dump("<pre>",$this->db->last_query());die;
        if($query->num_rows() > 0) return $query->result_array();
        else return false;
    }

    public function get_log_count($username){
      return $this->db->query("SELECT * FROM login_attempts_hris WHERE username = '$username' AND status = 'success'");
    }

    public function payroll_computed_data($where_clause){
      return $this->db->query("SELECT a.*, REPLACE(CONCAT(b.lname,', ',b.fname,' ',SUBSTR(b.mname, 1, 1),'. '), 'Ã‘', 'Ñ') as fullname FROM payroll_computed_table a INNER JOIN employee b ON a.employeeid = b.employeeid WHERE a.employeeid != '' $where_clause ORDER BY fullname");
    }

    public function employee_loan_data($where_clause){
      return $this->db->query("SELECT a.*, REPLACE(CONCAT(b.lname,', ',b.fname,' ',SUBSTR(b.mname, 1, 1),'. '), 'Ã‘', 'Ñ') as fullname FROM employee_loan a INNER JOIN employee b ON a.employeeid = b.employeeid WHERE a.employeeid != '' $where_clause ORDER BY fullname");
    }

    public function emp_deduction_list($where_clause=""){
      return $this->db->query("SELECT loan, otherdeduc FROM payroll_computed_table WHERE employeeid != '' $where_clause");
    }

    public function document_request_list($where_clause=""){
      return $this->db->query("SELECT * FROM document_app a INNER JOIN employee b ON a.employeeid = b.employeeid WHERE a.employeeid != '' $where_clause ")->result();
    }

    function getAtmPayrolllist($emp_bank='', $cutoffstart, $status = 'PROCESSED', $sortby = '', $campus = '', $company = '', $deptid = '', $office = '', $teachingtype = '', $employeeid=''){
        $where_clause = $order_by = $account_no = '';
        if($employeeid && $employeeid[0] && is_array($employeeid)){
          $emplist = "'" . implode( "','", $employeeid ) . "'";
          $where_clause .= " AND a.`employeeid` IN ($emplist) ";
        }else{
          if($employeeid){
            if(!in_array("", $employeeid)){
              if($employeeid) $where_clause .= " AND a.`employeeid` = '$employeeid' ";
            }
          }
        }
        if($emp_bank) $where_clause .= " AND c.`bank`='$emp_bank' ";
        if($teachingtype){
            if($teachingtype != "trelated") $where_clause .= " AND a.teachingtype='$teachingtype'";
            else $wC .= " AND a.teachingtype = 'teaching' AND trelated = '1'";
        }
        if($deptid) $where_clause .= " AND a.`deptid`='$deptid' ";
        if($office) $where_clause .= " AND a.`office`='$office' ";
        if($emp_bank) $where_clause .= " AND c.`bank`='$emp_bank' ";
        if($campus && $campus != 'all') $where_clause .= " AND a.`campusid`='$campus' ";
        if($company && $company != 'all') $where_clause .= " AND a.`company_campus`='$company' ";
        
        if($sortby == 'alphabetical') $order_by = " ORDER BY a.lname";
        if($sortby == 'department') $order_by = " ORDER BY b.description";
        $res = $this->db->query("SELECT a.employeeid, lname, mname, fname, c.`bank`, c.`net`, a.emp_accno, b.description,a.company_campus
                  FROM employee a
                  INNER JOIN code_office b ON b.`code`=a.`office`
                  INNER JOIN payroll_computed_table c ON c.`employeeid`=a.`employeeid`
                  WHERE c.`status` = '$status' AND cutoffstart='$cutoffstart' $where_clause $order_by");
        $data = array();
        if($res->num_rows() > 0){
          foreach ($res->result() as $key => $row) {
            $emp_bank = $this->extensions->getEmpBank($row->employeeid);
            $emp_bank = explode("/", $emp_bank);
            if($emp_bank){
              foreach($emp_bank as $bank){
                $fbank = explode("=", $bank);
                if($row->bank == $fbank[0]) $account_no = isset($fbank[1]) ? $fbank[1] : '';
              }
            }

            $fullname = $row->lname . ' ' . $row->fname . ' ' . substr($row->mname, 0,1) . '.';
            $data['list'][$row->employeeid] = array('fullname'=>utf8_encode($fullname),'account_num'=>$account_no,'net_salary'=>$row->net,'description'=>$row->description,'company_campus'=>$row->company_campus, "fname" => $row->fname, "mname" => $row->mname, "lname" => $row->lname);
          }
        }

        $data['branch'] = 'METROBANK';
        $data['bank_name'] = 'MBOS';


        return $data;

    }


    function getTerminalDowntimeData()
    {
        // $this->db->select('email');
        $this->db->limit(300);
        $this->db->order_by("timestamp", "DESC");
        return $this->db->get('terminal_devices_log')->result();
    }

    function getSyncDataLogs()
    {
        return $this->db->query("SELECT employeeid, COUNT(*) AS syncCount, `status`,DATE_FORMAT(`timestamp`, '%r') AS 'time', DATE(`timestamp`) AS 'date' FROM aims_schedule_logs GROUP BY `timestamp`, `status`, `employeeid`")->result();
    }

    function getSummaryData($month ="", $from ="", $to ="", $format = "xls",$campus = "", $deptid = "", $office = "", $teachingtype = "", $status = "", $employeeid = "", $sortBy = ""){


        $return = array('data' => "",'from' => "", 'to' => "");
        $dayFrom = "";
        $dayTo = "";

        $where = "";
        $orderBy = "";

        if($campus != "" && $campus != "All"){
            $where .= " AND a.campusid = '$campus'";
        }

        if ($deptid != "" && $deptid != "All") {
            $where .= " AND a.deptid = '$deptid'";
        }

        if ($office != "" && $office != "All") {
            $where .= " AND a.office = '$office'";
        }

        if ($teachingtype != "" && $teachingtype != "All") {
            if($teachingtype != "trelated") $where .= " AND a.teachingtype='$teachingtype'";
            else $where .= " AND trelated='1' AND a.teachingtype = 'teaching'";
        }

        if ($status != "" && $status != "All") {
            $where .= " AND a.isactive = '$status'";
        }

        if ($employeeid != "" && $employeeid != "All") {
            $where .= " AND a.employeeid = '$employeeid'";
        }

        $orderBy = "";

        switch(strtolower($sortBy)){
            case "sortalphabetical":
                $orderBy .= "ORDER BY a.fname ASC";
                break;
            case "sortdept":
                $orderBy .= "ORDER BY b.code ASC";
                break;
            case "sortoffice":
                $orderBy .= "ORDER BY a.office ASC";
                break;
        }

        $dayFrom = $from;
        $dayTo = $to;
        
        $return['from'] = $dayFrom;
        $return['to'] = $dayTo;

        $getArrayDate = $this->extras->getDatesBetween($dayFrom, $dayTo);
        
        $getEmployeeInfo = $this->extras->getEmployeeList($where, $orderBy);
        $record = array();
        foreach ($getEmployeeInfo as $ky => $emp) {
            // echo "<pre>"; print_r($record[$emp->employeeid]['absent_date'] );die;
            $record[$emp->employeeid]['employeeid'] = $emp->employeeid;
            $record[$emp->employeeid]['fname'] = $emp->fname;
            $record[$emp->employeeid]['lname'] = $emp->lname;
            $record[$emp->employeeid]['mname'] = $emp->mname;

            // Absent
            $abcenses = 0;
            $Absentreason = array();
            $AbsentDate = array();
            $AbsentPay = array();
            
            // Tardy
            $tardy = 0;
            $TardyDate = array();
            $TardyPay = array();
            $totalMins = 0;


            foreach ($getArrayDate as $key => $date) {
               $sched = $this->attcompute->displaySched($emp->employeeid,$date);
               if($sched->num_rows() > 0){
                  $isAbsent = $this->extras->isAbsent($date, $emp->employeeid);
                  if($isAbsent){
                      $abcenses++;
                      $absentData = $this->extras->getAbsentReason($date, $emp->employeeid);
                      if($absentData['reason'] != "none"){
                          $Absentreason[] = substr($date, 8, 10) . "-" . $absentData['reason'];
                      }
                      $AbsentPay[] = $absentData['with_pay'];
                      $AbsentDate[substr($date, 5, 2)][] = substr($date, 8, 10);
                  }

                  $isTardy = $this->extras->isTardy($date, $emp->employeeid);
                  if($isTardy['islate'] == true){
                      $tardy++;
                      $tardyData = $this->extras->getAbsentReason($date, $emp->employeeid);
                      $TardyPay[] = $tardyData['with_pay'];
                      $TardyDate[substr($date, 5, 2)][] = substr($date, 8, 10);
                      $totalMins += $isTardy['minutes_late'];
                  }
               }
                  
            }

            $record[$emp->employeeid]['absent_no'] = $abcenses;
            foreach ($AbsentDate as $mtAbsent => $dayAbsent) {
                if ($format == "PDF") {
                    $record[$emp->employeeid]['absent_date'] .= date("F", strtotime("2023-$mtAbsent-01")) . "<br>" . implode("<br>", $dayAbsent) . "<br>" . date("Y") . "    ";
                    $record[$emp->employeeid]['absent_date2'] .= date("F", strtotime("2023-$mtAbsent-01")) . " " . implode("<br>", $dayAbsent) . " " . date("Y") . "    ";
                }else{
                    $record[$emp->employeeid]['absent_date'] .= date("F", strtotime("2023-$mtAbsent-01")) . " " . implode(",", $dayAbsent) . ",  " . date("Y") . "    ";
                }
            }
            
            if($format == "PDF"){
                $record[$emp->employeeid]['absent_pay'] = implode("<br>", $AbsentPay);
            }else{
                $record[$emp->employeeid]['absent_pay'] = implode(", ", $AbsentPay);
            }
            
            $reasonTxt = implode("/", $Absentreason);
            $record[$emp->employeeid]['absent_reason'] = ($reasonTxt == "/")? "": $reasonTxt;
            $record[$emp->employeeid]['tardy_no'] = $tardy;
            foreach ($TardyDate as $mtTardy => $dayTardy) {
                $record[$emp->employeeid]['tardy_date'] .= $mtTardy . "/" . implode(",", $dayTardy) . "/" . date("Y") . "    ";
            }
            
            $record[$emp->employeeid]['tardy_pay'] = implode("/", $TardyPay);
            $record[$emp->employeeid]['tardy_mins'] = $totalMins;
            $record[$emp->employeeid]['tardy_hours'] = round($totalMins / 60, 2);
            if($emp->teachingtype == "nonteaching"){
                $record[$emp->employeeid]['tardy_days'] = round(($totalMins / 60) / 8, 2);
            }else{
                $record[$emp->employeeid]['tardy_days'] = round(($totalMins / 60) / 7, 2);
            }
            // echo "<pre>"; print_r($record[$emp->employeeid]['absent_date']);die;
        }
        $return['data'] = $record;
        return $return;
    }

    function getDTRReportDates($dtr_report_id){
      $from = $to = "";
      $query = $this->db->query("SELECT * FROM employee_dtr_report WHERE id = '$dtr_report_id'");
      if($query->num_rows() > 0){
        $from = $query->row()->date_from;
        $to = $query->row()->date_to;
      }
      return array($from, $to);
    }

    

    /**
     *  DATA FOR FILTER
     *  @param String $dtr_report_id - 
     *  @param String $employeelist - 
     */
    function saveIndividualData($dtr_report_id, $employeelist){
        $return = array('data' => "", 'from' => "", 'to' => "");
        list($from, $to) = $this->getDTRReportDates($dtr_report_id);
        $return['from'] = $from;
        $return['to'] = $to;

        $undertime = '';
        $getArrayDate = $this->extras->getDatesBetween($from, $to);
        $where = "";
        if($employeelist) $where .= " AND  FIND_IN_SET (a.employeeid, '$employeelist')";

        $getEmployeeInfo = $this->extras->getEmployeeList($where);
        
        foreach ($getEmployeeInfo as $ky => $emp) {

            $record = array();
            $employeeid = $emp->employeeid;
            $this->db->query("INSERT INTO employee_dtr_report_employee(base_id, employee_id)VALUES('$dtr_report_id', '$employeeid')");
            $baseid = $this->db->insert_id();
            $deptid = $this->extensions->getEemployeeCurrentData($employeeid, 'office');
            $teachingtype = $this->extensions->getEemployeeCurrentData($employeeid, 'teachingtype');
            

            // For Summary
            $leave_hours_total = 0;
            $late_total = 0;
            $absent_total = 0;
            $render_total = 0;
            $overtime_total = 0;
            $under_time_total = 0;

            foreach ($getArrayDate as $key => $date) {

                $dateText = date("F j, Y", strtotime($date));
                $day = date("l", strtotime($date));

                $timeInandOut = $this->extras->getTimeInandOut($emp->employeeid, $date, false);
               
                $time_in = $timeInandOut['time_in'];
                $time_out = $timeInandOut['time_out'];
                $leave_type = "-";
                $leave_hours = "0";
                $late = "0";
                $absent = "0";
                $render = "0";
                $overtime = "0";
                $under_time = "0";
                $day_type = "";
                $official_in = $official_out = "--:--";
                $breaktime = array();
                $breaktimeCounter = $undertime = 0;
                $isAbsent = $this->extras->isAbsent($date, $emp->employeeid);
                $getScheduleEmp = $this->extras->getEmpScheduleHistorySlim($emp->employeeid, $date);
                foreach ($getScheduleEmp as $k => $v) {
                  if($official_in == "--:--"){
                    $official_in = date('h:i A',strtotime($v['starttime']));
                  }else{
                    $breaktime[$breaktimeCounter]['end'] = $v['starttime'];
                    $breaktimeCounter++;
                  }
                  $official_out = date('h:i A',strtotime($v['endtime']));
                  if(isset($getScheduleEmp[$k+1])){
                    $breaktime[$breaktimeCounter]['start'] = $v['endtime'];
                  }
                }
                $totalHoursSched = $this->extras->getTotalHours($getScheduleEmp);

                // DayType
                $dayType = $this->extras->getDayTypeEmployee($date, $emp->employeeid, $getScheduleEmp);
                $day_type = $dayType;

                $holiday = $this->attcompute->isHolidayNew($emp->employeeid, $date,$deptid, "", "", $teachingtype); 

                if($holiday){
                  $holidayInfo = $this->attcompute->holidayInfo($date);
                }

                $is_holiday_valid = $this->attendance->getTotalHoliday($date, $date, $emp->employeeid);
                if(!$is_holiday_valid){
                    $holidayInfo = array();
                    $holiday = "";
                }

                // Create DayType Array
                $record[$emp->employeeid]['excess'] = array(1 => 0,2  => 0,3  => 0,4  => 0,5  => 0,6  => 0);
                // ABSENT
                if ($isAbsent && !isset($holidayInfo['description'])) {
                    $absentData = $this->extras->getAbsentReason($date, $emp->employeeid);
                    if ($absentData['reason'] != "none") {
                        $leave_type = $absentData['reason'];
                        if ($absentData['half_day'] == 1) {
                            $leave_hours = ($totalHoursSched / 2);
                        } else {
                            $leave_hours = $totalHoursSched;
                        }

                        // Add Total Leave Hours
                        $leave_type_total += $this->extras->convertHoursToMins($leave_hours);

                        $leave_hours = $this->extras->covertMinsToHoursFormat($leave_hours);
                    }else{
                        if($absentData['half_day'] == 1){
                            $absent = ($totalHoursSched / 2);
                        }else{
                            $absent = $totalHoursSched;
                        }
                    }

                    // Add Total Absent
                    $absent_total += $this->extras->convertHoursToMins($absent);

                    $absent = ($absent == 0) ? "-":$this->extras->covertMinsToHoursFormat($absent);

                }elseif($timeInandOut['time_in'] != "--:--" && $timeInandOut['time_out'] != "--:--" && $timeInandOut['time_in'] != $timeInandOut['time_out']){
                    $renderHours = $this->extras->calculateTotalHoursRendered($timeInandOut['time_in'], $timeInandOut['time_out'], $getScheduleEmp);
                    $render = $this->extras->covertMinsToHoursFormat($renderHours);

                    // echo "<pre>"; print_r($getScheduleEmp);
                    // echo "<pre>"; print_r($timeInandOut); die;
                    $totalMinsRendered = $this->extras->convertHoursToMins($renderHours);
                    // Add Total Rendered
                    $render_total += $totalMinsRendered;
                }

                if(!$absent && $timeInandOut['time_in'] == $timeInandOut['time_out'] && !isset($holidayInfo['description'])){
                  $absent = $totalHoursSched;
                  $absent_total += $this->extras->convertHoursToMins($absent);

                    $absent = ($absent == 0) ? "-":$this->extras->covertMinsToHoursFormat($absent);
                }

                // LATE
                $isTardy = $this->extras->isTardy($date, $emp->employeeid);
                if ($isTardy['islate'] == true && $timeInandOut['time_in'] != "--:--" && $timeInandOut['time_out'] != "--:--" && $timeInandOut['time_in'] != $timeInandOut['time_out'] && !isset($holidayInfo['description'])) {
                    $late = $this->time->minutesToHours($isTardy['minutes_late']);

                    // Add Total Late
                    $late_total += $isTardy['minutes_late'];
                }

                // UNDERTIME
                
                if($timeInandOut['time_out'] != "--:--" && $timeInandOut['time_in'] != $timeInandOut['time_out'] && count($getScheduleEmp) > 0 && !isset($holidayInfo['description'])){
                    $lastSched = end($getScheduleEmp);
                    // $timeInandOut['time_out'] = "12:30 PM";
                    foreach ($breaktime as $key => $value) {
                        if(date('H:i:s', strtotime($timeInandOut['time_out'])) >= $value['start'] && date('H:i:s', strtotime($timeInandOut['time_out'])) <= $value['end']){
                          $undertime =  $undertime + $this->extras->getUnderTime($value['end'], $lastSched['endtime']);
                        }else if($value['start'] >= date('H:i:s', strtotime($timeInandOut['time_out']))){
                          $undertime =  $undertime + $this->extras->getUnderTime($timeInandOut['time_out'], $value['start']);
                          $undertime =  $undertime + $this->extras->getUnderTime($value['end'], $lastSched['endtime']);
                          // $absent = ($totalHoursSched / 2);
                          // $absent_total += $this->extras->convertHoursToMins($absent);
                          // $absent = ($absent == 0) ? "-":$this->extras->covertMinsToHoursFormat($absent);
                        }else{
                          $undertime = $undertime + $this->extras->getUnderTime($timeInandOut['time_out'], $lastSched['endtime']);
                        }
                    }

                    // $undertime = $this->extras->getUnderTime($timeInandOut['time_out'], $lastSched['endtime']);
                    // echo "<pre>"; print_r($breaktime);
                    // echo "<pre>"; print_r($timeInandOut);
                    // echo "<pre>"; print_r($getScheduleEmp); die;
                    
                    $under_time = ($undertime <= 0)? "-":$this->extras->convertMinsToHours($undertime);
        
                    // Add Total Undertime
                    if($undertime > 0) $under_time_total += $undertime;
                    
                }

                // Overtime
                $overTime = $this->extras->getOvertime($date, $emp->employeeid, $getScheduleEmp);
                if($overTime){
                    $overtime = $overTime;
                    $overTimeMins = $this->extras->convertTimeToMins($overTime);

                    $record[$emp->employeeid]['excess'][$dayType] += $overTimeMins;

                    // Add Total Overtime
                    $overtime_total += $overTimeMins;
                }


                // OFFICIAL TIME
                $lastSched = end($getScheduleEmp);
                $startSched = current($getScheduleEmp);

                // echo "<pre>"; print_r($startSched);
                // echo "<pre>"; print_r($lastSched);die; 
                
                if($dayType == 1 && $isTardy['islate']) $day_type = "-";
                if($dayType == 1 && $isAbsent) $day_type = "-";
                if($dayType == 1 && $undertime) $day_type = "-";
                if($dayType == 1 && $overTime) $day_type = 1;
                
                $leave_hours = ($leave_hours == "0") ? "-":$leave_hours;
                $late = ($late == "0")? "-":$late;
                $absent = ($absent == "0")? "-":$absent;
                $render = ($render == "0")? "-":$render;
                $overtime = ($overtime == "0")? "-":$overtime;

                $this->db->query("INSERT INTO employee_dtr_report_detailed(base_id, `date`, day, official_in, official_out, timein, timeout,total_rendered, late, absent, undertime, overtime, day_type, leave_type, leave_hours)VALUES('$baseid', '$dateText', '$day', '$official_in', '$official_out', '$time_in', '$time_out', '$render', '$late', '$absent', '$under_time', '$overtime', '$day_type', '$leave_type', '$leave_hours')");
            }

            $leave_hours_total = $this->extras->convertMinsToHours($leave_hours_total);
            $late_total = $this->extras->convertMinsToHours($late_total);
            $absent_total = $this->extras->convertMinsToHours($absent_total);
            $render_total = $this->extras->convertMinsToHours($render_total);
            $overtime_total = $this->extras->convertMinsToHours($overtime_total);
            $under_time_total = $this->extras->convertMinsToHours($under_time_total);

            $this->db->query("INSERT INTO employee_dtr_report_computation_summary(base_id, total_work_rendered, absent, late, undertime, overtime, total_paid_leaves)VALUES('$baseid', '$render_total', '$absent_total', '$late_total', '$under_time_total', '$overtime_total', '$leave_hours_total')");

            foreach ($record[$emp->employeeid]['excess'] as $key => $data) {
                $record[$emp->employeeid]['excess'][$key] = ($data != 0) ? $this->extras->convertMinsToHours($data): "";
                $excess = $record[$emp->employeeid]['excess'][$key];
                $this->db->query("INSERT INTO employee_dtr_report_work_rendered(base_id, type, date_type, excess)VALUES('$baseid', '$key', '$key', '$excess')");
            }

    }
        return "Done";
    }

    /**
     *  DATA FOR FILTER
     *  @param String $from - start of data
     *  @param String $campus - campust name
     *  @param String $deptid - department
     *  @param String $office - office
     *  @param String $teaching - type of teaching
     *  @param String $status - active or not
     *  @param String $employeeid - 
     */
    function getIndividualData($from = "", $to = "", $campus = "", $deptid = "", $office = "", $teaching = "", $status = "", $employeeid = "", $employeelist="")
    {
        $return = array('data' => "", 'from' => "", 'to' => "");

        $return['from'] = $from;
        $return['to'] = $to;

        $undertime = '';

        $getArrayDate = $this->extras->getDatesBetween($from, $to);
        $where = "";
        if ($campus && $campus != "All" && $campus != "") $where .= " AND a.campusid = '$campus'";
        if ($deptid && $deptid != "All" && $deptid != "") $where .= " AND a.deptid = '$deptid'";
        if ($office && $office != "All" && $office != "") $where .= " AND a.office = '$office'";
        if ($teaching && strtolower($teaching) != "all" && $teaching != "") $where .= " AND a.teachingtype = '$teaching'";
        if ($status && $status != "All" && $status != "") $where .= " AND a.isactive = '$status'";
        if($employeelist) $where .= " AND  FIND_IN_SET (a.employeeid, '$employeelist')";
        else if ($employeeid && $employeeid != "All"&& $employeeid != "all" && $employeeid != "") $where .= " AND a.employeeid = '$employeeid'";

        $getEmployeeInfo = $this->extras->getEmployeeList($where);
        // echo "<pre>"; print_r($getEmployeeInfo); die;
        
        $record = array();
        foreach ($getEmployeeInfo as $ky => $emp) {
            $record[$emp->employeeid]['employeeid'] = $emp->employeeid;
            $record[$emp->employeeid]['fname'] = $emp->fname;
            $record[$emp->employeeid]['lname'] = $emp->lname;
            $record[$emp->employeeid]['mname'] = $emp->mname;
            $record[$emp->employeeid]['department'] = $emp->department;
            $record[$emp->employeeid]['position_desc'] = $emp->position_desc;
            $record[$emp->employeeid]['employement_desc'] = $emp->employement_desc;

            // For Summary
            $record[$emp->employeeid]['leave_hours_total'] = 0;
            $record[$emp->employeeid]['late_total'] = 0;
            $record[$emp->employeeid]['absent_total'] = 0;
            $record[$emp->employeeid]['render_total'] = 0;
            $record[$emp->employeeid]['overtime_total'] = 0;
            $record[$emp->employeeid]['under_time_total'] = 0;

            foreach ($getArrayDate as $key => $date) {
                
                $record[$emp->employeeid]['logs'][$date]['date'] = date("F j, Y", strtotime($date));
                $record[$emp->employeeid]['logs'][$date]['day'] = date("l", strtotime($date));

                $timeInandOut = $this->extras->getTimeInandOut($emp->employeeid, $date, false);
                
                $record[$emp->employeeid]['logs'][$date]['time_in'] = $timeInandOut['time_in'];
                $record[$emp->employeeid]['logs'][$date]['time_out'] = $timeInandOut['time_out'];
                $record[$emp->employeeid]['logs'][$date]['leave_type'] = "-";
                $record[$emp->employeeid]['logs'][$date]['leave_hours'] = "0";
                $record[$emp->employeeid]['logs'][$date]['late'] = "0";
                $record[$emp->employeeid]['logs'][$date]['absent'] = "0";
                $record[$emp->employeeid]['logs'][$date]['render'] = "0";
                $record[$emp->employeeid]['logs'][$date]['overtime'] = "0";
                $record[$emp->employeeid]['logs'][$date]['under_time'] = "0";
                $record[$emp->employeeid]['logs'][$date]['day_type'] = "";

                $isAbsent = $this->extras->isAbsent($date, $emp->employeeid);
                $getScheduleEmp = $this->extras->getEmpScheduleHistorySlim($emp->employeeid, $date);
                $totalHoursSched = $this->extras->getTotalHours($getScheduleEmp);

                // DayType
                $dayType = $this->extras->getDayTypeEmployee($date, $emp->employeeid, $getScheduleEmp);
                // var_dump($this->db->last_query());die;
                $record[$emp->employeeid]['logs'][$date]['day_type'] = $dayType;

                // Create DayType Array
                $record[$emp->employeeid]['excess'] = array(1 => 0,2  => 0,3  => 0,4  => 0,5  => 0,6  => 0);
                // echo "<pre>";var_dump($emp->employeeid);die;
                // ABSENT
                if ($isAbsent) {
                    $absentData = $this->extras->getAbsentReason($date, $emp->employeeid);
                    if ($absentData['reason'] != "none") {
                        $record[$emp->employeeid]['logs'][$date]['leave_type'] = $absentData['reason'];
                        if ($absentData['half_day'] == 1) {
                            $record[$emp->employeeid]['logs'][$date]['leave_hours'] = ($totalHoursSched / 2);
                        } else {
                            $record[$emp->employeeid]['logs'][$date]['leave_hours'] = $totalHoursSched;
                        }

                        // Add Total Leave Hours
                        $record[$emp->employeeid]['leave_type_total'] += $this->extras->convertHoursToMins($record[$emp->employeeid]['logs'][$date]['leave_hours']);

                        $record[$emp->employeeid]['logs'][$date]['leave_hours'] = $this->extras->covertMinsToHoursFormat($record[$emp->employeeid]['logs'][$date]['leave_hours']);
                    }else{
                        if($absentData['half_day'] == 1){
                            $record[$emp->employeeid]['logs'][$date]['absent'] = ($totalHoursSched / 2);
                        }else{
                            $record[$emp->employeeid]['logs'][$date]['absent'] = $totalHoursSched;
                        }
                    }

                    // Add Total Absent
                    $record[$emp->employeeid]['absent_total'] += $this->extras->convertHoursToMins($record[$emp->employeeid]['logs'][$date]['absent']);

                    $record[$emp->employeeid]['logs'][$date]['absent'] = ($record[$emp->employeeid]['logs'][$date]['absent'] == 0) ? "-":$this->extras->covertMinsToHoursFormat($record[$emp->employeeid]['logs'][$date]['absent']);

                }elseif($timeInandOut['time_in'] != "--:--" && $timeInandOut['time_out'] != "--:--"){
                    $renderHours = $this->extras->calculateTotalHoursRendered($timeInandOut['time_in'], $timeInandOut['time_out'], $getScheduleEmp);
                    $record[$emp->employeeid]['logs'][$date]['render'] = $this->extras->covertMinsToHoursFormat($renderHours);
                    $totalMinsRendered = $this->extras->convertHoursToMins($renderHours);
                    // echo "<pre>";print_r($totalMinsRendered);die;
                    // Add Total Rendered
                    $record[$emp->employeeid]['render_total'] += $totalMinsRendered;
                }

                // LATE
                $isTardy = $this->extras->isTardy($date, $emp->employeeid);
                if ($isTardy['islate'] == true) {
                    $record[$emp->employeeid]['logs'][$date]['late'] = $this->time->minutesToHours($isTardy['minutes_late']);

                    // Add Total Late
                    $record[$emp->employeeid]['late_total'] += $isTardy['minutes_late'];
                }

                // UNDERTIME
                
                if($timeInandOut['time_out'] != "--:--" && $timeInandOut['time_in'] != $timeInandOut['time_out'] && count($getScheduleEmp) > 0){
                    $lastSched = end($getScheduleEmp);
                    $undertime = $this->extras->getUnderTime($timeInandOut['time_out'], $lastSched['endtime']);
                    
                    $record[$emp->employeeid]['logs'][$date]['under_time'] = ($undertime == 0)? "-":$this->extras->convertMinsToHours($undertime);
        
                    // Add Total Undertime
                    $record[$emp->employeeid]['under_time_total'] += $undertime;
                }

                // Overtime
                $overTime = $this->extras->getOvertime($date, $emp->employeeid, $getScheduleEmp);
                if($overTime){
                    $record[$emp->employeeid]['logs'][$date]['overtime'] = $overTime;
                    $overTimeMins = $this->extras->convertTimeToMins($overTime);

                    $record[$emp->employeeid]['excess'][$dayType] += $overTimeMins;

                    // Add Total Overtime
                    $record[$emp->employeeid]['overtime_total'] += $overTimeMins;
                }
                
                // DAYTYPE NEW CONDITION 5-2-2023
                // CHECK IF HAS LATE AND DAYTYPE IS REGULAR PUT -
                if($dayType == 1 && $isTardy['islate']) $record[$emp->employeeid]['logs'][$date]['day_type'] = "-";
                // CHECK IF HAS ABSENT AND DAYTYPE IS REGULAR PUT -
                if($dayType == 1 && $isAbsent) $record[$emp->employeeid]['logs'][$date]['day_type'] = "-";
                // CHECK IF HAS UNDERTIME AND DAYTYPE IS REGULAR PUT -
                if($dayType == 1 && $undertime) $record[$emp->employeeid]['logs'][$date]['day_type'] = "-";
                // CHECK IF HAS OT AND DAYTYPE IS REGULAR PUT 1 ELSE -
                if($dayType == 1 && $overTime) $record[$emp->employeeid]['logs'][$date]['day_type'] = 1;
                
                $record[$emp->employeeid]['logs'][$date]['leave_hours'] = ($record[$emp->employeeid]['logs'][$date]['leave_hours'] == "0") ? "-":$record[$emp->employeeid]['logs'][$date]['leave_hours'];
                $record[$emp->employeeid]['logs'][$date]['late'] = ($record[$emp->employeeid]['logs'][$date]['late'] == "0")? "-":$record[$emp->employeeid]['logs'][$date]['late'];
                $record[$emp->employeeid]['logs'][$date]['absent'] = ($record[$emp->employeeid]['logs'][$date]['absent'] == "0")? "-":$record[$emp->employeeid]['logs'][$date]['absent'];
                $record[$emp->employeeid]['logs'][$date]['render'] = ($record[$emp->employeeid]['logs'][$date]['render'] == "0")? "-":$record[$emp->employeeid]['logs'][$date]['render'];
                $record[$emp->employeeid]['logs'][$date]['overtime'] = ($record[$emp->employeeid]['logs'][$date]['overtime'] == "0")? "-":$record[$emp->employeeid]['logs'][$date]['overtime'];
            }

            $record[$emp->employeeid]['leave_hours_total'] = $this->extras->covertMinsToHoursFormat($record[$emp->employeeid]['leave_hours_total']);
            $record[$emp->employeeid]['late_total'] = $this->extras->covertMinsToHoursFormat($record[$emp->employeeid]['late_total']);
            $record[$emp->employeeid]['absent_total'] = $this->extras->convertMinsToHours($record[$emp->employeeid]['absent_total']);
            $record[$emp->employeeid]['render_total'] = $this->extras->convertMinsToHours($record[$emp->employeeid]['render_total']);
            $record[$emp->employeeid]['overtime_total'] = $this->extras->covertMinsToHoursFormat($record[$emp->employeeid]['overtime_total']);
            $record[$emp->employeeid]['under_time_total'] = $this->extras->convertMinsToHours($record[$emp->employeeid]['under_time_total']);


            
            foreach ($record[$emp->employeeid]['excess'] as $key => $data) {
                $record[$emp->employeeid]['excess'][$key] = ($data != 0) ? $this->extras->convertMinsToHours($data): "";
            }
        }

        // echo "<pre>";
        // print_r($record);
        // die;
        $return['data'] = $record;

        return $return;
    }
    
    public function aimsAPILogs($where_clause){
        return $this->db->query("SELECT 
                            a.employeeid,
                            fname,
                            lname,
                            mname,
                            c.`description` as title,
                            d.`description` as campus,
                            e.`description` as department,
                            f.`description` AS office,
                            b.teachingtype,
                            g.`description` as empstat,
                            h.`description` as position,
                            i.`designation` as designation,
                            b.dateemployed,
                            b.createdon,
                            a.status,
                            createdby
                        FROM
                            aims_logs a 
                            INNER JOIN employee b 
                            ON a.employeeid = b.employeeid 
                            left join code_title c on b.`title` = c.`titleid`
                            left join code_campus d on b.`campusid` = d.`code`
                            LEFT JOIN code_department e ON e.`code` = b.`deptid`
                            LEFT JOIN code_office f ON f.`code` = b.`office`
                            left join code_status g ON g.`code` = b.`employmentstat`
                            left join code_position h ON h.`positionid` = b.`positionid`
                            left join code_designation i ON i.`code` = b.`designation` $where_clause");
    }

    public function aimsModifiedTrail($desc){
		
        $return = "";
        if($desc == "Trail Logs 1")
        {
            $arrcol = array ("fname","lname","mname","title","campusid","deptid", "office", "teachingtype", "employmentstat", "designation", "positionid", "dateposition", "mobile", "landline", "permaAddress");
        }
        else if($desc == "Trail Logs 2")
        {
            $arrcol = array (   "bdate","bplace","email","gender","citizenid","religionid","civil_status","spouse_fname","spouse_job","father","fatheroccu","mother","motheroccu","emp_tin","emp_philhealth","emp_pagibig","emp_sss" );
        }
        
        $return .=  '<div class="col-md-4" style="margin-bottom:5%"><span><strong></strong></span><br />';
        foreach($arrcol as $row){
            $col = $row;
            $return .=  '
                        <div class="col-md">
                            <input type="checkbox" name="tdata[]" value="'.$col.'" > '.$this->showdesc($col).'
                        </div>
                ';
        }
        $return .=  '</div>';
        return $return;
    }
    
    public function aimsColumnDescription(){
        $arrcol = array (
            "fname" => "First Name",
            "lname" => "Last Name",
            "mname" => "Middle Name",
            "title" => "Title",
            "campusid" => "Campus",
            "deptid" => "Department",
            "office" => "Office",
            "teachingtype" => "Teaching Type",
            "employmentstat" => "Employment Status",
            "designation" => "Designation",
            "positionid" => "Position",
            "dateposition" => "Date Position",
            "mobile" => "Mobile Number",
            "landline" => "Landline",
            "permaAddress" => "Permanent Address",
            "bdate" => "Birth Date",
            "bplace" => "Birth Place",
            "email" => "Email",
            "gender" => "Gender",
            "citizenid" => "Citizenship",
            "religionid" => "Religion",
            "civil_status" => "Civil Status",
            "spouse_fname" => "Spouse Name",
            "spouse_job" => "Spouse Job",
            "father" => "Father Name",
            "fatheroccu" => "Father Occupation",
            "mother" => "Mother Name",
            "motheroccu" => "Mother Occupation",
            "emp_tin" => "Tin No.",
            "emp_philhealth" => "Philhealth No.",
            "emp_pagibig" => "Pagibig No.",
            "emp_sss" => "SSS No."
        );

        return $arrcol;
    }

    public function aimsUpdateInfoLogs($where_clause){
        return $this->db->query("SELECT a.*, CONCAT(b.lname, ' ', b.fname, ', ', mname) AS fullname FROM aims_info_trail_logs a INNER JOIN employee b ON a.`employeeid` = b.`employeeid` $where_clause");
    }

    public function summarizedEmployeeReport($data) {
        $results = array();


        // CAMPUS
        $campushQuery = $this->db->select("code, description")->from("code_campus");
        if(strtolower($data['campus']) != "all"){
            $this->db->where('code', $data['campus']);
        }
        $campushQuery = $campushQuery->get()->result();

        $query = $this->getEmployeeCountData($data['campus'], "", "", $data['asof']);

        $empData = [];
        foreach($query as $idx => $emp){
            $empData[$emp->campusid] = $emp;
        }

        foreach($query as $idx => $data){
            $results[$data->campusid."|".$data->campusDesc][$data->teachingtype][$data->employmentstat][] = $data;
        }


        return $results;
    }

    /**
     * - PARAMETERS
     * $campusid = nakabased sa campusid sa employee table
     * $teachingtype = nakabased sa teachingtype sa employee table
     * $emplomentstat = nakabased sa emplomentstat sa employee table
     * $asOf = nakabased sa dateemployed sa employee table
     */
    public function getEmployeeCountData($campusid = "", $teachingtype = "", $emplomentstat = "", $asOf = ""){

        // $employmentstatArray = ["PRMNT", "PRTTIM", "TMP", "CSL", "CNTRCT","EL","COS","JOSPR"]; 

        $employee = $this->db->select('emp.campusid, emp.employeeid, emp.teachingtype, emp.dateemployed, emp.employmentstat, code_campus.description as campusDesc')
        ->from("employee as emp")
        ->join('code_campus', 'code_campus.code = emp.campusid', 'inner')
        ->join('code_status', 'code_status.code = emp.employmentstat', 'inner')
        // ->where_in("emp.employmentstat", $employmentstatArray)
        ->where("employeeid !=", "");

        if(strtolower($campusid) != "all"){
            $this->db->where('campusid', $campusid);
        }

        if($teachingtype != ""){
            $this->db->where('teachingtype', $teachingtype);
        }

        if($emplomentstat != ""){
            $this->db->where('employmentstat', $emplomentstat);
        }

        if($asOf != ""){
            $this->db->where('dateemployed <=', $asOf);

        }
        return $employee->get()->result();
    }


    
    /**
     * - PARAMETERS
     * $campusid = nakabased sa campusid sa employee table
     * $teachingtype = nakabased sa teachingtype sa employee table
     * $emplomentstat = nakabased sa emplomentstat sa employee table
     * $asOf = nakabased sa dateemployed sa employee table
     */
    public function getEmployeeCount($campusid = "", $teachingtype = "", $emplomentstat = "", $asOf = ""){
        $employeeCount = $this->db
        ->select('COUNT(employeeid) as employee_count')
        ->from('employee')
        ->join('code_campus', 'code_campus.code = employee.campusid', 'inner')
        ->join('code_status', 'code_status.code = employee.employmentstat', 'inner');

        if(isset($campusid)){
            $this->db->where('employee.campusid', $campusid);
        }

        if (isset($teachingtype)) {
            $this->db->where('employee.teachingtype', $teachingtype);
        }
        
        if (isset($emplomentstat)) {
            $this->db->where('employee.employmentstat', $emplomentstat);
        }
        
        if (isset($asOf)) {
            $this->db->where('employee.dateemployed <=', $asOf);
        }

        $employeeCount = $this->db->get()->row()->employee_count;
  
        return $employeeCount;
      }
  
    public function getLeaveApp($employeeid, $date, $leavetype) {
		return $this->db->query("SELECT SUM(no_days) AS no_days FROM leave_request WHERE employeeid = '$employeeid' AND leavetype = '$leavetype' AND `status` = 'APPROVED' AND YEAR(dateapproved) = YEAR('$date') AND MONTH(dateapproved) = MONTH('$date')")->row()->no_days;
    }

    public function getOtherLeaveApp($employeeid, $date) {
        return $this->db->query("SELECT leavetype, fromdate, todate FROM leave_request WHERE employeeid = '$employeeid' AND leavetype != 'VL' AND leavetype != 'SL' AND `status` = 'APPROVED' AND YEAR(dateapproved) = YEAR('$date') AND MONTH(dateapproved) = MONTH('$date')")->result();
    }
    
    public function getLeaveDates($employeeid, $date, $leavetype) {
        return $this->db->query("SELECT DISTINCT fromdate, todate FROM leave_request WHERE employeeid = '$employeeid' AND leavetype = '$leavetype' AND `status` = 'APPROVED' AND YEAR(dateapproved) = YEAR('$date') AND MONTH(dateapproved) = MONTH('$date')")->result();
    }
    
    public function getLearningDevelopmentInterventions($where='', $order='') {
        return $this->db->query("SELECT o.description AS office, e.employeeid, CONCAT(e.lname,', ', e.fname, ' ', e.mname) AS fullname, topic, venue, datef, datet, location, hours FROM employee_resource er INNER JOIN employee e ON e.employeeid = er.employeeid LEFT JOIN code_office o ON o.code = e.office $where ORDER BY $order")->result();
    }

    /**
     *  DATA FOR FILTER
     *  @param String $dtr_report_id - 
     *  @param String $employeelist - 
     */
   
    
}