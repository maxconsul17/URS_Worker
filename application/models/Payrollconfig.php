<?php 
/**
 * @author Angelica Arangco
 * @copyright 2017
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Payrollconfig extends CI_Model {

	function getIncomeConfig($incomeType='',$id='',$coldata=array()){
		$wC = '';
		$return = array();
		if($incomeType) $wC .= " AND incomeType='$incomeType'";
		if($id) 		$wC .= " AND id='$id'";

		$res = $this->db->query("SELECT * FROM payroll_income_config WHERE incomeType IS NOT NULL $wC");
		if($res->num_rows() > 0){
			foreach ($res->result() as $key => $row) {
				$result[$row->id] = array();
				foreach ($coldata as $colname) {
					$result[$row->id][$colname] = $row->$colname;
				}
			}
		}

		return $result;
	}

	function getLoanConfig($id=''){
		$where_clause = ($id) ? "WHERE id='$id'" : "";

		$q_loan_config = $this->db->query("SELECT * FROM payroll_loan_config $where_clause ORDER BY id")->result();

		return $q_loan_config;
	}

	function getDeductionConfig($id=''){
		$where_clause = ($id) ? "WHERE id='$id'" : "";

		$q_deduction_config = $this->db->query("SELECT * FROM payroll_deduction_config $where_clause ORDER BY id")->result();

		return $q_deduction_config;
	}

	function getAllIncomeConfig($id=''){
		$where_clause = ($id) ? "WHERE id='$id'" : "";

		$q_income_config = $this->db->query("SELECT * FROM payroll_income_config $where_clause ORDER BY id")->result();

		return $q_income_config;
	}
}//end of file