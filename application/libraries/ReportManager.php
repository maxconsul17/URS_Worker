<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ReportManager
{   
    private $CI;
    private $worker_model;
    private $time;
    private $employeeAttendance;

    function __construct() 
    {
        $this->CI = & get_instance();
        $this->CI->load->model("Worker_model", "worker_model");
        $this->CI->load->model("Time", "time");
        $this->CI->load->model("EmployeeAttendance", "employeeAttendance");
        $this->worker_model = $this->CI->worker_model;
        $this->time = $this->CI->time;
        $this->employeeAttendance = $this->CI->employeeAttendance;
    }

    // public function processReport(){
    //     $this->init_process_dtr();
    // }

    // Initialize processing of Daily Time Record (DTR) reports
    public function processReport($reportJob, $worker_id){
        // $this->worker_model->forTrail($worker_id);
        // $reports = $this->worker_model->get_report_task(); // Get pending DTR report tasks
        
        // if ($reports->num_rows() > 0) {
        //     foreach($reports->result() as $report){
        //         $this->process_dtr($report); // Process the DTR report for the first task found
        //     }
        // }

        if ($reportJob->code == 'dtr') $this->process_dtr($reportJob, $worker_id);
        elseif ($reportJob->code == 'summAbsentTardy' && $reportJob->worker_id == $worker_id) $this->process_summAbsentTardy($reportJob, $worker_id);
    }

    public function getReportJob()
    {
        return $this->worker_model->getReportJob();
    }

    // Process the DTR report for a given report task
    public function process_dtr($det, $worker_id){
        $this->worker_model->updateReportStatus($det->id, "", "ongoing");
        if ($det->total_tasks == "0") $this->worker_model->updateReportStatus($det->id, "", "No employee to generate");

        // Prepare date range
        $data["actual_dates"] = [$det->dfrom, $det->dto];
        $dates = $this->time->generateMonthDates($det->dfrom);
        $det->dfrom = $dates["first_date"];
        $det->dto = $dates["last_date"];
        
        // Fetch employees for the report
        $employeelist = $this->worker_model->getEmployeeList($det->where_clause, $worker_id, $det->id);
        // $this->worker_model->forTrail();

        foreach ($employeelist as $employee) {
            try {
                // Check if the report was cancelled
                if ($this->worker_model->report_cancelled($det->id) > 0) {
                    $this->worker_model->updateReportStatus($det->id, "", "cancelled", 0);
                    break;
                }

                // Prepare data for the report
                $form_data = json_decode($det->form_data);
                $isteaching = $this->worker_model->getempteachingtype($employee->employeeid);
                $data['month_of'] = (date("F Y", strtotime($det->dfrom)) === date("F Y", strtotime($det->dto))) 
                ? date("F Y", strtotime($det->dfrom)) 
                : date("F Y", strtotime($det->dfrom)) . ' - ' . date("F Y", strtotime($det->dto));
                $data['report_id'] =  $det->id;
                $data['dfrom'] =  $det->dfrom;
                $data['dto'] =  $det->dto;
                $data['userlog'] =  $det->user;
                $data['verified_name'] =  $form_data->verified_name;
                $data['campus_director_name'] =  $form_data->campus_director_name;
                $data['verified_position'] =  $form_data->verified_position;
                $data['campus_director_position'] =  $form_data->campus_director_position;
                $data['campus'] = $employee->campusid;
                $data['employeeid'] = $employee->employeeid;
                $data['attendance'] = $this->worker_model->getEmployeeDTR($employee->employeeid, $det->dfrom, $det->dto, $isteaching);
                // Load the appropriate report view
                // echo '<pre>';print_r($data);die;
                $report = $this->CI->load->view(
                    $isteaching ? 'dtr/teachingDailyTimeReport' : 'dtr/nonteachingDailyTimeReport',
                    $data,
                    TRUE
                );

                var_dump($employee->employeeid);
                // Update the report breakdown and generate the PDF
                $this->worker_model->updateReportBreakdown("done", $employee->rep_breakdown_id, $det->id);

                $data["report_list"] = [["report" => $report, "campus" => $employee->campusid, "header_desc" => $data['month_of']]];
                $data["path"] = "files/reports/pdf/{$employee->employeeid}_{$det->id}.pdf";

                $this->CI->load->view('dtr/daily_time_report_pdf', $data);

            }catch (Exception $e) {
                $this->worker_model->updateReportStatus($det->id, "", $e);
                continue;
            }
        }
    }

    public function process_summAbsentTardy($det, $worker_id){
        $this->worker_model->updateReportStatus($det->id, "", "ongoing");
        $data = json_decode($det->form_data, true);
        
        $cutOff = explode(',',$data['cutoff']);
		$data['date'] = "From $cutOff[0] to $cutOff[1]";
		$employeeList = $this->employeeAttendance->getEmployeeIDTeachingTypeList($data['employeeid'], $data['campusid'], $data['employment_status']);
		$teachingType = $data['tnt'] ? $data['tnt'] : $employeeList[0]->teaching;
		foreach ($employeeList as $employee) {
			$summary = $this->employeeAttendance->getAbsentTardySummary($cutOff[0], $cutOff[1], $employee->employeeid, $teachingType);
			if ($summary[0]->employeeid) {
				$data['datas'][] = $summary;
			}
		}
        $data["path"] = "files/report_list/pdf/{$det->id}.pdf";

		ini_set('max_execution_time', 0);
		ini_set("memory_limit",-1);
		ini_set("pcre.backtrack_limit", "10000000000");
		$pdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter-L', 'tempDir' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'mpdf', 'margin_top' => '10', 'margin_bottom' => '10', 'margin_right' => '10', 'margin_left' => '10']);
		$info = $this->CI->load->view('forms_pdf/absentTardySummary', $data, true);
		$pdf->WriteHTML($info);
        $info = ob_get_clean();
        $pdf->Output($data["path"], "F");

        $this->worker_model->updateReportStatus($det->id, "", "done");
    }
}