<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all clients related functions
 */
class TimeTrack extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'timetrack.html';

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
        $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");

        if(isset($_POST['project'], $_POST['tasks_assigned_to_id'], $_POST['description'], $_POST['freshbookstaskid'], $_POST['time'], $_POST['tasks_start_date'], $_POST['tasks_end_date'],$_POST['tasks_events_id'])){
            foreach ($_POST as $key => $value) {
                $$key = $value;
            }

            chdir("__freshbooksapi");
            require_once("__freshbooksinit.php");
            chdir("..");

            $prepared = $db->prepare("SELECT * FROM projects WHERE projects_id = ?");
            $prepared->execute(array($project));
            $row = $prepared->fetch(PDO::FETCH_ASSOC);
            $tasks_client_id = $row['projects_clients_id'];
            $hours = $time;//floatval(floatval($time / 60) / 60);
            $prepared = $db->prepare("INSERT INTO tasks (
                                          tasks_assigned_to_id,
                                          tasks_client_id,
                                          tasks_created_by_id,
                                          tasks_end_date,
                                          tasks_events_id,
                                          tasks_milestones_id,
                                          tasks_project_id,
                                          tasks_start_date,
                                          tasks_text,
                                          freshbookstaskid,
                                          estimatedhours,
                                          hourslogged,
                                          timedoctortaskid,
                                          tasks_status,
                                          tasks_events_id                                          
                                          )VALUES(
                                          ?,
                                          ?,
                                          ?,
                                          ?,
                                          ?,
                                          ?,
                                          ?,
                                          ?,
                                          ?,
                                          ?,
                                          ?,
                                          ?,
                                          ?,
                                          ?)");
            $prepared->execute(array($tasks_assigned_to_id, $tasks_client_id, 13, $tasks_end_date, '', 0, $project, $tasks_start_date, $description, $freshbookstaskid, $hours, $hours, 0, 'completed',$tasks_events_id));
            $results = $db->lastInsertId(); //(last insert item)
            $taskid = $results;

            $freshbooksprojectid = getFreshbooksProjectIdFromId($project);
            $freshbooksstaffid = getFreshbooksStaffIdFromId($tasks_assigned_to_id);
            $freshbookstaskid = getFreshbooksTaskIdFromId($taskid);

           
			$now=$tasks_start_date." 12:00:00";
			$time=$time*3600;
            $prepared = $db->prepare("INSERT INTO timer VALUES (NULL, ?, ?, ?, ?, ?, ?, 0, 0, NULL, 0)");
            $prepared->execute(array($project, $taskid, $now, $time, $tasks_assigned_to_id, 'completed'));

            echo "<script>alert('Time entry created, and saved.');</script>";
        }

        $this->__flmView('admin/main');

    }

    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']); //complete the view
        $this->__commonAll_View($view);
    }
}