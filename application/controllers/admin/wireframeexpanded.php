<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Tasks related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class wireframeexpanded extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.wireframeexpanded.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_tasks'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';

    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    function index()
    {

        /* --------------URI SEGMENTS----------------
        *
        * /admin/tasks/2/view/*.*
        * (2)->controller
        * (3)->project_id
        * (4)->router
        *
        ** -----------------------------------------*/
        $userid = $this->data['vars']['my_id'];
        $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 
        //$result = $db->prepare("SELECT `id` FROM oauth WHERE user_id = ?");
        //$result->execute(array($userid));
        //if($result->rowCount() == 0){ header("Location: http://pms.isodeveloper.com/admin/oauth"); exit; }*/

        if (isset($_GET['ideas'])){
            $this->data['vars']['wireframe_type'] = "Ideas";
            $type = "ideas";
        }elseif (isset($_GET['db'])) {
            $this->data['vars']['wireframe_type'] = "Database Management";
            $type = "db";
        }elseif (isset($_GET['notes'])) {
            $this->data['vars']['wireframe_type'] = "Client";
            $type = "client";
        }elseif (isset($_GET['development'])) {
            $this->data['vars']['wireframe_type'] = "Development";
            $type = "development";
        }elseif (isset($_GET['issues'])) {
            $this->data['vars']['wireframe_type'] = "Issues";
            $type = "issues";
        }elseif (isset($_GET['design'])) {
            $this->data['vars']['wireframe_type'] = "Design";
            $type = "design";
        }else{
            $this->data['vars']['wireframe_type'] = "Ideas";
            $type = "ideas";
        }

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //get project id
        //$this->project_id = $this->uri->segment(3);
        $this->wireframe_id = $this->uri->segment(3);
        $this->data['vars']['wireframe_id'] = $this->wireframe_id;

        $prepared = $db->prepare("SELECT `page_link` FROM wireframe_page WHERE id = ?");
        $prepared->execute(array($this->wireframe_id));
        $row = $prepared->fetch(PDO::FETCH_ASSOC);
        $this->data['vars']['url'] = $row['page_link'];

        if (isset($_POST['notes'])){
            $prepared = $db->prepare("SELECT id FROM wireframe_notes WHERE wireframeid = ? AND type = ?");
            $prepared->execute(array($this->wireframe_id, $type));
            if ($prepared->rowCount() > 0){
                $row = $prepared->fetch(PDO::FETCH_ASSOC);
                $prepared = $db->prepare("UPDATE wireframe_notes SET notes = ? WHERE id = ?");
                $prepared->execute(array($_POST['notes'], $row['id']));
            }else{
                $prepared = $db->prepare("INSERT INTO wireframe_notes VALUES (NULL, ?, ?, ?)");
                $prepared->execute(array($this->wireframe_id, $type, $_POST['notes']));
            }
        }

        $prepared = $db->prepare("SELECT `notes` FROM wireframe_notes WHERE wireframeid = ? AND type = ?");
        $prepared->execute(array($this->wireframe_id, $type));
        if ($prepared->rowCount() > 0){
            $row = $prepared->fetch(PDO::FETCH_ASSOC);
            $this->data['vars']['notes'] = $row['notes'];
        }else{
            $this->data['vars']['notes'] = "";
        }

        

        //set project_id for global use in template
        //$this->data['vars']['project_id'] = $this->project_id;

        //check if project exists & set some basic data
        //$this->__commonAll_ProjectBasics($this->project_id);

        //show wi_project_tasks widget
        $this->data['visible']['wi_project_tasks'] = 1;

        //create pulldown lists
        $this->__pulldownLists();

        //do some important things first
        $this->__tasksPreRun();

        //get the action from url
        //$action = $this->uri->segment(4);

        /** TABLE CLEAN UP **/
        //$this->tasks_model->tableCleanup();
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //route the rrequest
        switch ($action) {
            default:
                $this->__tasksView();
                break;
        }

        //css - active tab
        if ($this->uri->segment(8) == 'my') {
            $this->data['vars']['css_active_tab_mytasks'] = 'side-menu-main-active';
        } else {
            $this->data['vars']['css_active_tab_tasks'] = 'side-menu-main-active';
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * some various important settings befoe doing anything else
     *
     */
    function __tasksPreRun()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

    }

    /**
     * main-handler function
     * manage all project tasks
     *
     */
    function __tasksView()
    {
        $project_id = $this->uri->segment(3);
        $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;
    }

    /**
     * additional data preparations for __tasksView() data
     *
     */
    function __prepTasksView($thedata = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (count($thedata) == 0 || !is_array($thedata)) {
            return $thedata;
        }

        /* -----------------------PREPARE TASKS DATA ----------------------------------------/
        *  Loop through all the tasks in this array and for each task:
        *  -----------------------------------------------------------
        *  (1) add visibility for the [control] buttons
        *  (2) process user names (task assigned to)
        *  -----------------------------------------------------------
        *
        * [NOTES]
        * Usage is during conditional loading at TBS->MERGEBLOCK level and should be as follows:
        * <!--[onshow;block=div;when [tasks.wi_tasks_timer_buttons;block=tr] == 1;comm]-->
        * --AS OPPOSED TO--
        * <!--[onshow;block=div;when [tasks.wi_tasks_timer_buttons] == 1;comm]-->
        *
        *------------------------------------------------------------------------------------*/

        for ($i = 0; $i < count($thedata); $i++) {

            //--------------------(1) VISIBILITY OF EDIT AND DELETE BUTTON------------------------------\\

            //first set visibility to 0
            $visible_delete_button = 0;
            $visible_edit_button = 0;

            //task is assigned to me and I have edit rights
            if ($thedata[$i]['tasks_assigned_to_id'] == $this->data['vars']['my_id']) {
                //align with my general permissions
                $visible_delete_button = $this->data['project_permissions']['delete_item_my_project_my_tasks'];
                $visible_edit_button = $this->data['project_permissions']['edit_item_my_project_my_tasks'];
            }

            //i am project leader or admininstrator
            if ($this->data['vars']['my_id'] == $this->data['vars']['project_leaders_id'] || $this->data['my_group'] == 1) {
                $visible_delete_button = 1;
                $visible_edit_button = 1;
            }

            //inject control visibility into $thedata array
            $thedata[$i]['visible_delete_button'] = $visible_delete_button;
            $thedata[$i]['visible_edit_button'] = $visible_edit_button;

            //-----(2) PROCESS (ASSIGNED TO) USER NAMES------------------------------\\

            if ($thedata[$i]['team_profile_full_name'] != '') {

                //trim max lenght
                $fullname = trim_string_length($thedata[$i]['team_profile_full_name'], 10);
                $user_id = $thedata[$i]['team_profile_id']; //create users name label
                $thedata[$i]['assigned_to'] = '<a class="links-blue iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $this->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("admin/people/team/$user_id") . '"
                                                      href="#">' . $fullname . '</a>';
            } else {

                //this user is unavailable (has been deleted etc)
                $thedata[$i]['assigned_to'] = '<a class="tooltips" 
                                                       data-original-title="' . $this->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $this->data['lang']['lang_unavailable'] . '</a>';
            }
        }

        //return the processed array
        return $thedata;
    }

    /**
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //form validation
        if ($form == 'add_task' || $form == 'edit_task') {

            //check required fields
            $fields = array(
                'tasks_text' => $this->data['lang']['lang_title'],
                'tasks_start_date' => $this->data['lang']['lang_start_date'],
                'tasks_end_date' => $this->data['lang']['lang_end_date'],
                'tasks_milestones_id' => $this->data['lang']['lang_milestone'],
                'tasks_assigned_to_id' => $this->data['lang']['lang_assigned_to']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //everything ok
            return true;
        }

        //nothing specified - return false & error message
        $this->form_processor->error_message = $this->data['lang']['lang_form_validation_error'];
        return false;
    }

    /**
     * log any error message into the log file
     *
     */
    function __errorLogging($theline = '', $thefunction = '', $thefile = '', $themessage = 'system error')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //write to log file
        $message_log = "[FILE: $thefile]  [LINE: $theline]  [FUNCTION: $thefunction]  [MESSAGE: $themessage]";
        log_message('error', $message_log);
    }

    /**
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     *
     */
    function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //[all_milestones]
        $data = $this->milestones_model->allMilestones('milestones_title', 'ASC', $this->project_id);
        $this->data['debug'][] = $this->milestones_model->debug_data;
        $this->data['lists']['all_milestones'] = create_pulldown_list($data, 'milestones', 'id'); //[all_team_members]
        $data = $this->teamprofile_model->allTeamMembers('team_profile_full_name', 'ASC');
        $this->data['debug'][] = $this->teamprofile_model->debug_data;
        $this->data['lists']['all_team_members'] = create_pulldown_list($data, 'team_members', 'id');
    }

    /**
     * records new project events (timeline)
     *
     * @param	string   $type identify the loop to run in this function
     * @param   array    $vents_data an optional array that can be used to directly pass data]      
     */
    function __eventsTracker($type = '', $events_data = array())
    {
        //profiling
        $this->data['controller_profiling'][] = __function__; //flow control
        $next = true; //--------------record a new event-----------------------
        if ($type == 'new-task') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->project_id;
            $events['project_events_type'] = 'task';
            $events['project_events_details'] = $this->input->post('tasks_text');
            $events['project_events_action'] = 'lang_tl_add_new_task';
            $events['project_events_target_id'] = 0;
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'team'; //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;
        }

        //--------------record a new event-----------------------
        if ($type == 'completed-task') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->project_id;
            $events['project_events_type'] = 'task';
            $events['project_events_details'] = $this->input->post('tasks_text');
            $events['project_events_action'] = 'lang_tl_completed_task';
            $events['project_events_target_id'] = 0;
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'team';
            //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;
        }

    }

    /**
     * loads the view
     *
     * @param string $view the view to load
     */
    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']); //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file tasks.php */
/* Location: ./application/controllers/admin/tasks.php */