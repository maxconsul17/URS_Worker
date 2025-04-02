<?php

// Load Composer's autoloader for using external libraries
require APPPATH . '../vendor/autoload.php'; 

use yidas\queue\worker\Controller as WorkerController;

defined('BASEPATH') OR exit('No direct script access allowed');

class Worker extends WorkerController
{
    public $debug = true;
    public $workerWaitSeconds = 5;
    public $workerMaxNum = 4;
    public $workerStartNum = 1;
    public $logPath = APPPATH . 'cache/my-worker.log';
    public $workerHeathCheck = true;

    // Initializer
    protected function init() {
        $this->load->library("ReportManager", null, "report_manager");
        $this->load->library("RecomputeManager", null, "recompute_manager");
        $this->load->library("PayrollManager", null, "payroll_manager");
        $this->load->library("AttendanceConfirmManager", null, "attendance_confirm_manager");
        $this->load->library("AttendanceManager", null, "attendance_manager");
    }
    
    // Worker
    protected function handleWork($worker_id) {
        $getCalculateJob = $this->attendance_manager->getCalculateJob();
        if($getCalculateJob){
            $this->attendance_manager->processCalculation();
            return false;
        }

        $hasUpdateJob = $this->attendance_manager->getHasUpdateJob();
        if($hasUpdateJob){
            $this->attendance_manager->processCalculationUpdate();
            return false;
        }

        $getReportJob = $this->report_manager->getReportJob();
        if($getReportJob){
            $this->report_manager->processReport($getReportJob, $worker_id);
            return false;
        }
        $getRecomputeJob = $this->recompute_manager->getRecomputeJob();
        if($getRecomputeJob){
            $this->recompute_manager->processRecompute($getRecomputeJob, $worker_id);
            return false;
        }
        $getPayrollJob = $this->payroll_manager->getPayrollJob();
        if($getPayrollJob){
            $this->payroll_manager->processPayroll($getPayrollJob, $worker_id);
            return false;
        }

        $getAttConfirmJob = $this->attendance_confirm_manager->getAttConfirmJob();
        if($getAttConfirmJob){
            $this->attendance_confirm_manager->attConfirm($getAttConfirmJob, $worker_id);
            return false;
        }

        // $this->attendance_manager->processCalculation();
        
        return false;
    }

    // Listener
    protected function handleListen() {

        $getCalculateJob = $this->attendance_manager->getCalculateJob();
        if($getCalculateJob) return true;

        $hasUpdateJob = $this->attendance_manager->getHasUpdateJob();
        if($hasUpdateJob) return false;

        $getReportJob = $this->report_manager->getReportJob();
        if($getReportJob) return true;

        $getRecomputeJob = $this->recompute_manager->getRecomputeJob();
        if($getRecomputeJob) return true;
        
        $getPayrollJob = $this->payroll_manager->getPayrollJob();
        if($getPayrollJob) return true;

        $getAttConfirmJob = $this->attendance_confirm_manager->getAttConfirmJob();
        if($getAttConfirmJob) return true;

        return false;

        // return $this->attendance_manager->getEmployeeToCalculateJob(); // return true or false
    }
}