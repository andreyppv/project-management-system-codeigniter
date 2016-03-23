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
class Tasksexpanded extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . 'task_expanded.html';

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

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        //if ($this->data['vars']['my_user_type'] != 'team'){
            /*Redirect to client task expanded*/
        //    header("Location: http://pms.isodeveloper.com/client/tasksexpanded/".$this->uri->segment(3)."/".$this->uri->segment(4));
        //    exit;
        //}
        
        //$this->__commonAdmin_LoggedInCheck();


        //get project id
        $this->project_id = $this->uri->segment(3);

        //set project_id for global use in template

        $this->data['vars']['project_id'] = $this->project_id;
        $this->data['vars']['task_id'] = $this->uri->segment(4);
        
        /*General Fix*/
        $this->data['vars']['task_id'] = str_replace(')', '', $this->data['vars']['task_id']);
        /*From Freshbooks invoicing ^*/

        $userid = $this->data['vars']['my_id'];
        $taskid = $this->data['vars']['task_id'];
        $projectid = $this->data['vars']['project_id'];

        $task = $this->tasks_model->getTask($taskid);

        $this->data['vars']['taskname'] = $task['tasks_text'];

        $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 
        /*$result = $db->prepare("SELECT `id` FROM oauth WHERE user_id = ?");
        $result->execute(array($userid));
        if($result->rowCount() == 0){ header("Location: http://pms.isodeveloper.com/admin/oauth"); exit; }*/
        
        /*$timer = $this->tasks_model->getTimer($taskid, $projectid, $userid);

        if($timer['timer_status'] != 'completed' && $timer['timer_status'] != 'billed'){
            $seconds = $timer['timer_seconds'];
            //7290 seconds / 60 seconds = 121.5 minutes
            $minutes = floor($seconds / 60); //121 minutes
            $hours = floor($minutes / 60); //2.025 hours
            $minutes -= $hours * 60; //-120 minutes
            $seconds = $seconds - ($minutes * 60) - (($hours * 60) * 60); //30 seconds
            $this->data['vars']['hoursworked'] = $hours;
            $this->data['vars']['minutesworked'] = $minutes;
            $this->data['vars']['secondsworked'] = $seconds;
        }else{
            $this->data['vars']['hoursworked'] = 0;
            $this->data['vars']['minutesworked'] = 0;
            $this->data['vars']['secondsworked'] = 0;
        }*/
		
		
        $this->data['blk1'] = $this->tasks_model->getSubTasks($taskid, $projectid);
		
        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //show wi_project_tasks widget
        $this->data['visible']['wi_project_tasks'] = 1;

        //create pulldown lists
        $this->__pulldownLists();

        //do some important things first
        $this->__tasksPreRun();

        //get the action from url
        $action = $this->uri->segment(4);

        /** TABLE CLEAN UP **/
        $this->tasks_model->tableCleanup();
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //route the rrequest
        switch ($action) {

            case 'view':
                $this->__tasksView();
                break;

            case 'add':
                $this->__tasksAdd();
                break;

            case 'edit':
                $this->__tasksEdit();
                break;

            case 'edit-timer':
                $this->__editTaskTimer();
                break;

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

        /* --------------URI SEGMENTS---------------
        * [example]
        *  /admin/tasks/2/view/0/desc/sortby_end_date/all/0
        * (2)->controller
        * (3)->project_id
        * (4)->router
        * (5)->milestone_id
        * (6)->sort_by
        * (7)->sort_by_column
        * (8)->all or my
        * (9)->status (all/pending/behind-schedule/completed/all-open)
        * (10)->offset
        * -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //refresh task status (all tasks)
        $this->refresh->taskStatus('all');

        //uri segments
        $project_id = $this->uri->segment(3);
        $milestone_id = ($this->uri->segment(5) > 0) ? $this->uri->segment(5) : 0;
        $sort_by = ($this->uri->segment(6) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(7) == '') ? 'sortby_taskid' : $this->uri->segment(7);
        $status = ($this->uri->segment(9) == '') ? 'all-open' : $this->uri->segment(9);
        $offset = (is_numeric($this->uri->segment(10))) ? $this->uri->segment(10) : 0;

        //are we listing ALL tasks or just MY tasks
        $show_which = ($this->uri->segment(8) == 'my') ? $this->uri->segment(8) : 'all';

        //toggle [view my tasks] OR [view all tasks] buttons
        if ($show_which == 'all') {
            $this->data['visible']['button_show_my_tasks'] = 1;
        } else {
            $this->data['visible']['button_show_all_tasks'] = 1;
        }

        //css side menu highlight
        $active_milestone = "css_menu_tasks_side_$milestone_id";
        $this->data['vars'][$active_milestone] = 'side-menu-active';

        //load all milestones and their task count for side menu
        $this->data['reg_blocks'][] = 'tasks_milestones';
        $this->data['blocks']['tasks_milestones'] = $this->milestones_model->listMilestones(0, 'results', $this->project_id);
        $this->data['debug'][] = $this->milestones_model->debug_data;

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'tasks';
        $this->data['blocks']['tasks'] = $this->tasks_model->listTasks($offset, 'search', $this->project_id, $status);
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->tasks_model->listTasks($offset, 'count', $this->project_id, $status);
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //pagination
        $config = pagination_default_config(); //
        $config['base_url'] = site_url("admin/tasks/$project_id/view/$milestone_id/$sort_by/$sort_by_column/$show_which/$status/");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 10; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_id',
            'sortby_status',
            'sortby_end_date');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/tasks/$project_id/view/$milestone_id/$link_sort_by/$column/$show_which/$status/$offset");
        }

        //status buttons links
        $this->data['vars']['completed_tasks_link'] = site_url("admin/tasks/$project_id/view/$milestone_id/$link_sort_by/$column/$show_which/completed/$offset");
        $this->data['vars']['pending_tasks_link'] = site_url("admin/tasks/$project_id/view/$milestone_id/$link_sort_by/$column/$show_which/pending/$offset");
        $this->data['vars']['behind_tasks_link'] = site_url("admin/tasks/$project_id/view/$milestone_id/$link_sort_by/$column/$show_which/behind-schedule/$offset");
        $this->data['vars']['all_tasks_link'] = site_url("admin/tasks/$project_id/view/$milestone_id/$link_sort_by/$column/$show_which/all/$offset");

        //Tasks page title
        if ($this->uri->segment(8) == 'my') {
            $whose = $this->data['lang']['lang_my_tasks'];

        } else {
            $whose = $this->data['lang']['lang_all_tasks'];
        }
        if ($results = $this->milestones_model->milestoneDetails($milestone_id)) {
            $this->data['vars']['tasks_tabs_title'] = $whose . ' - (' . $results['milestones_title'] . ')';
        } else {
            $this->data['vars']['tasks_tabs_title'] = $whose . ' - (' . $this->data['lang']['lang_all_milestones'] . ')';
        }
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //various tasks counts (for side menu etc)
        $this->data['vars']['count_tasks_pending'] = $this->tasks_model->countTasks($this->project_id, 'project', 'pending', $show_which);
        $this->data['debug'][] = $this->tasks_model->debug_data;
        $this->data['vars']['count_tasks_completed'] = $this->tasks_model->countTasks($this->project_id, 'project', 'completed', $show_which);
        $this->data['debug'][] = $this->tasks_model->debug_data;
        $this->data['vars']['count_tasks_behind_schedule'] = $this->tasks_model->countTasks($this->project_id, 'project', 'behind schedule', $show_which);
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //visibility
        if ($rows_count > 0) {
            //show side menu
            $this->data['visible']['tasks_milestone_list'] = 1;
        } else {
            //show mothing found
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_results_found']);

            //show some information/tips
            $this->data['vars']['tasks_info_no_tasks_found'] = 1;
        }

        /** SEND DATA FOR ADDITIONAL PREPARATION **/
        $this->data['blocks']['tasks'] = $this->__prepTasksView($this->data['blocks']['tasks']);

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
     * add new task
     *
     */
    function __tasksAdd()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //PERMISSIONS CHECK
        if ($this->data['project_permissions']['add_item_my_project_my_tasks'] != 1) {
            //show error
            $this->session->set_flashdata('notice-error', $this->data['lang']['lang_permission_denied']);

            //halt
            $next = false;
        }

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/tasks/' . $this->project_id . '/view');
        }

        //validate form & display any errors
        if (!$this->__flmFormValidation('add_task')) {

            //show error
            $this->session->set_flashdata('notice-error', $this->form_processor->error_message);
            //halt
            $next = false;
        }

        //validate hidden fields
        if ($next) {
            if (!is_numeric($_POST['tasks_client_id']) || !is_numeric($_POST['tasks_project_id']) || !is_numeric($_POST['tasks_created_by_id']) || $_POST['tasks_events_id'] == '') {

                //log this error
                $this->__errorLogging(__line__, __function__, __file__, 'Adding new task failed: [Some or All] Required hidden form fields missing or invalid');
                //show error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                //halt
                $next = false;
            }
        }

        //check end date is not behind start date
        if ($next) {

            if (strtotime($this->input->post('tasks_end_date')) < strtotime($this->input->post('tasks_start_date'))) {

                //show error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_the_end_date_before_start']);
                //halt
                $next = false;
            }
        }

        //add new task to database
        if ($next) {
            if ($this->tasks_model->addTask()) {
                //show success
                $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);
                //events tracker
                $this->__eventsTracker('new-task', array());
            } else {
                //show error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                //halt
                $next = false;
            }
        }
        $this->data['debug'][] = $this->tasks_model->debug_data; //show task page

        //refresh project progress
        if ($next) {
            $this->refresh->updateProjectPercentage($this->project_id);
            $this->data['debug'][] = $this->refresh->debug_data;
        }

        //redirect back to my last view
        if ($this->input->post('redirect_url') != '') {
            redirect($this->input->post('redirect_url'));
        }

        $this->__tasksView();
    }

    /**
     * edit a task
     *
     */
    function __tasksEdit()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/tasks/' . $this->project_id . '/view');
        }

        //validate hidden fields
        if ($next) {
            if (!is_numeric($_POST['tasks_id']) || $_POST['tasks_events_id'] == '') {
                //log this error
                $this->__errorLogging(__line__, __function__, __file__, 'Editing task failed: [Some or All] Required hidden form fields missing or invalid');
                //show error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                //halt
                $next = false;
            }
        }

        //get task details
        if ($next) {
            //get task super users
            $task = $this->tasks_model->getTask($this->input->post('tasks_id'));
            $this->data['debug'][] = $this->tasks_model->debug_data; //if no task found
            if (!$task) {
                //show error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                //halt
                $next = false;
            }
        }

        /** ----------------------- PERMISSION CHECK --------------------------------
         *  only the following users can carry out this action
         *  (1) super user (admin | project leader)
         *  (3) member the task is assigned to (who has edit level permissions)
         *--------------------------------------------------------------------------*/
        if ($next) {

            //kill the flow - assume I dont have permission
            $next = false; //i am project leader
            if ($this->data['project_permissions']['super_user'] == 1) {
                $next = true;
            }

            //its assigned to me & I have edit permissions set
            if ($task['tasks_assigned_to_id'] == $this->data['vars']['my_id']) {
                if ($this->data['project_permissions']['edit_item_my_project_my_tasks'] == 1) {
                    $next = true;
                }
            }

            //permission denied
            if (!$next) {
                //error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_permission_denied']);
            }

        }

        //validate form & display any errors
        if ($next) {
            if (!$this->__flmFormValidation('edit_task')) {

                //show error
                $this->session->set_flashdata('notice-error', $this->form_processor->error_message);
                //halt
                $next = false;
            }
        }

        //check end date is not behind start date
        if ($next) {

            if (strtotime($this->input->post('tasks_end_date')) < strtotime($this->input->post('tasks_start_date'))) {

                //show error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_the_end_date_before_start']);
                //halt
                $next = false;
            }
        }

        //edit task
        if ($next) {
            if ($this->tasks_model->editTask()) {

                //show success
                $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);

                //events tracker (completed task)
                if ($this->input->post('tasks_status') == 'completed') {
                    $this->__eventsTracker('completed-task', array());
                }

            } else {

                //log this error
                $this->__errorLogging(__line__, __function__, __file__, 'Editing task failed: Database error'); //show error

                //show error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
        }
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //refresh project progress
        if ($next) {
            $this->refresh->updateProjectPercentage($this->project_id);
            $this->data['debug'][] = $this->refresh->debug_data;
        }

        //redirect back to my last view
        if ($this->input->post('redirect_url') != '') {
            redirect($this->input->post('redirect_url'));
        }

        //show task page
        $this->__tasksView();
    }

    /**
     * validates forms for various methods in this class
     * @param   string $form identify the form to validate
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
     * @param   string   $type identify the loop to run in this function
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
