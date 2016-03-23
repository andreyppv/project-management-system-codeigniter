<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all clients related functions
 */
class DeveloperInvoiceExpanded extends MY_Controller
{

    /**
     * constructor method
     */
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'developerinvoiceexpanded.html';

        //css settings
        $this->data['vars']['css_menu_dashboard'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_dashboard'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-home"></i>';
        
    
    }

    /**
     * This is our re-routing function and is the inital function called
     * 
     */
    function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        $this->invoice_id = $this->uri->segment(3);
        $this->getInvoice($this->invoice_id);
        $this->__flmView('admin/main');

    }

    function getTeamMember($db, $id){
        $prepared = $db->prepare("SELECT * FROM team_profile WHERE team_profile_id = ?");
        $prepared->execute(array($id));
        return $prepared->fetch(PDO::FETCH_ASSOC);
    }

    function getTask_($db, $allTasks, $taskID){
        $prepared = $db->prepare("SELECT `freshbookstaskid` FROM tasks WHERE tasks_id = ?");
        $prepared->execute(array($taskID));
        $count = $prepared->rowCount();
        if($count > 0){
            $row = $prepared->fetch(PDO::FETCH_ASSOC);
            $freshbooksTaskId = $row['freshbookstaskid'];
            foreach ($allTasks as $task) {
                if($task['task_id'] == $freshbooksTaskId){
                    return $task;
                }else{
                    continue;
                }
            }
        }
        return array();
    }

    function getTimeEntries($db, $teamID){
        return $db->query("SELECT * FROM timer WHERE timer_status != 'billed'")->fetchAll();
    }

    function getProjectName($db, $projectid){
        $prepared = $db->prepare("SELECT `projects_title` FROM projects WHERE projects_id = ?");
        $prepared->execute(array($projectid));
        $count = $prepared->rowCount();
        if($count > 0){
            $row = $prepared->fetch(PDO::FETCH_ASSOC);
            return $row['projects_title'];
        }else{
            return "";
        }
    }

    function getTaskDetails_($db, $taskid){
        $prepared = $db->prepare("SELECT `tasks_text` FROM tasks WHERE tasks_id = ?");
        $prepared->execute(array($taskid));
        $count = $prepared->rowCount();
        if($count > 0){
            $row = $prepared->fetch(PDO::FETCH_ASSOC);
            return $row['tasks_text'];
        }else{
            return "";
        }
    }

    function getTaskName($db, $taskid){
        /*UNUSED*/
    }

    function getTaskType($db, $taskid){
        $prepared = $db->prepare("SELECT `freshbookstaskid` FROM tasks WHERE tasks_id = ?");
        $prepared->execute(array($taskid));
        $count = $prepared->rowCount();
        if($count > 0){
            $row = $prepared->fetch(PDO::FETCH_ASSOC);
            $freshbooksTaskId = $row['freshbookstaskid'];

        }else{
            return "";
        }
    }

    function getInvoice($id){
        //profiling
        chdir("__freshbooksapi");
        require_once("__freshbooksinit.php");
        chdir("..");

        $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
        $freshbooksTasks = getTasks()[1]['tasks']['task'];
        $prepared = $db->prepare("SELECT * FROM developer_invoices WHERE id = ?");
        $prepared->execute(array($id));
        $row = $prepared->fetch(PDO::FETCH_ASSOC);

        $this->data['vars']['invoiceid'] = $row['id'];
        $this->data['vars']['invoiceteammember'] = $this->getTeamMember($db, $row['teammember_id'])['team_profile_full_name'];
        $this->data['vars']['invoiceamount'] = round($row['total'],2);
        $this->data['vars']['invoicetime'] = round((($row['totalsecondsworked']/60)/60),2);
        if(empty($this->data['vars']['invoicetime'])){ $this->data['vars']['invoicetime'] = "Flat Rate"; }
        $this->data['vars']['invoicedate'] = $row['date'];
        $this->data['vars']['invoicestatus'] = $row['status'];
        $this->data['vars']['invoicedetails'] = "";

        if($this->isJson($row['invoiceDetails'])){
            $decoded = json_decode($row['invoiceDetails'], true);

            foreach ($decoded as $details) {
                $new_seconds = $details['Seconds']; //600 seconds
                $new_minutes = floor($new_seconds / 60); //10 minutes
                $new_hours = $new_minutes / 60; //0.16667 hours
                
                $new_seconds -= ($new_minutes * 60);
                $new_minutes -= ($new_hours * 60);

                $totalTime = "Total Time: ";
                
                if($new_hours > 0){
                    $totalTime .= round($new_hours, 2) . " hours ";
                }
                
                if($new_minutes > 0){
                    $totalTime .= round($new_minutes, 2) . " minutes ";
                }
                
                if($new_seconds > 0){
                    $totalTime .= $new_seconds . " seconds ";
                }

                if(!empty($details)){
                    $this->data['vars']['invoicedetails'] .=
                    htmlentities($this->getTask_($db, $freshbooksTasks, $details['ID'])['name']) . " | " . 
                    htmlentities($this->getTeamMember($db, $row['teammember_id'])['team_profile_full_name']) . ": " . 
                    htmlentities($this->getProjectName($db, $details['Project'])) . " " . 
                    htmlentities($this->getTaskDetails_($db, $details['ID'])) . " (http://pms.isodeveloper.com/admin/tasksexpanded/" . strval(intval($details['Project'])) . "/" . strval(intval($details['ID'])).") | " . 
                    $totalTime . "\n";
                }
            }
        }else{
            $this->data['vars']['invoicedetails'] = htmlentities($row['invoiceDetails']);
        }

        //$this->data['reg_blocks'][] = 'invoice';
        //$this->data['blocks']['invoice'] = $invoice;
    }

    function isJson($string) {
        return ((is_string($string) &&
                (is_object(json_decode($string)) ||
                is_array(json_decode($string))))) ? true : false;
    }

    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']); //complete the view
        $this->__commonAll_View($view);
    }
}