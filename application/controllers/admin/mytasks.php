<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Mytasks related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Mytasks extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'mytasks.html';

        //css settings
        $this->data['vars']['css_menu_heading_mytasks'] = 'heading-menu-active'; //menu
        $this->data['vars']['css_menu_mytasks'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_my_tasks'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-list-ul"></i>';
        
        $this->data['vars']['menu'] = 'mytasks';

    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    public function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //create pulldown lists
        $this->__pulldownLists();

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'list-tasks':
                $this->__listTasks();
                break;
            case 'hold-tasks':
                $this->__listTasks(true);
                break;
            case 'search-tasks':
                $this->__cachedFormSearch();
                break;

            case 'add-task':
                $this->__addTask();
                break;

            case 'edit-task':
                $this->__editTask();
                break;
			
			case 'edit-assigned':
                $this->__editAssigned();
                break;

            case 'edit-assigned-time':
                $this->__editAssignedTime();
                break;
				
			case 'edit-description':
                $this->__editDescription();
                break;	

            default:
                $this->__listTasks();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * example of a paginated method
     */
    function __listTasks($hold=false)
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/mytasks/list/54/desc/sortby_taskid/0
        * (2)->controller
        * (3)->router
        * (4)->search id
        * (5)->sort_by
        * (6)->sort_by_column
        * (7)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //refresh task status (all tasks)
        $this->refresh->taskStatus('all');

        //uri segments
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_taskid' : $this->uri->segment(6);
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);

        if($hold == false)
        {
            //get results and save for tbs block merging
            $this->data['reg_blocks'][] = 'tasks';
            $this->data['blocks']['tasks'] = $this->tasks_model->searchTasks($offset, 'search', 'mytasks');
            $this->data['debug'][] = $this->tasks_model->debug_data;

            //count results rows - used by pagination class
            $rows_count = $this->tasks_model->searchTasks($offset, 'count', 'mytasks');
            $this->data['vars']['my_tasks_count'] = $rows_count;
            $this->data['debug'][] = $this->tasks_model->debug_data;

            //pagination
            $config = pagination_default_config(); //load all other settings from helper
            $config['base_url'] = site_url("admin/mytasks/list-tasks/$search_id/$sort_by/$sort_by_column");
            $config['total_rows'] = $rows_count;
            $config['per_page'] = $this->data['settings_general']['results_limit'];
            $config['uri_segment'] = 7; //the offset var
            $this->pagination->initialize($config);
            $this->data['vars']['pagination'] = $this->pagination->create_links();
            
            $this->data['vars']['tab'] = 'actived';
        }
        else
        {
            //get results and save for tbs block merging
            $this->data['reg_blocks'][] = 'tasks';
            $this->data['blocks']['tasks'] = $this->tasks_model->searchTasks($offset, 'search', 'mytasks', 'hold');
            $this->data['debug'][] = $this->tasks_model->debug_data;

            //count results rows - used by pagination class
            $rows_count = $this->tasks_model->searchTasks($offset, 'count', 'mytasks', 'hold');
            $this->data['vars']['my_tasks_count'] = $rows_count;
            $this->data['debug'][] = $this->tasks_model->debug_data;

            //pagination
            $config = pagination_default_config(); //load all other settings from helper
            $config['base_url'] = site_url("admin/mytasks/hold/$search_id/$sort_by/$sort_by_column");
            $config['total_rows'] = $rows_count;
            $config['per_page'] = $this->data['settings_general']['results_limit'];
            $config['uri_segment'] = 7; //the offset var
            $this->pagination->initialize($config);
            $this->data['vars']['pagination'] = $this->pagination->create_links(); 
            
            $this->data['vars']['tab'] = 'hold';   
        }

        //sorting links for menus on the top of the table
        //the array names mustbe same as used in clients_model.php->searchClients()
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_taskid',
            'sortby_taskstatus',
            'sortby_taskduedate',
            'sortby_projectid');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/mytasks/list-tasks/$search_id/$link_sort_by/$column/$offset");
        }

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blocks']['tasks'])) {
            $this->data['visible']['wi_my_tasks_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     * takes all posted (search form) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     *
     */
    function __cachedFormSearch()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //create array containg all post data in format:: array('name'=>$this->input->post('name));
        $search_array = array();
        foreach ($_POST as $key => $value) {
            $search_array[$key] = $this->input->post($key);
        }

        //save serch query in database & get id of database record
        $search_id = $this->input->save_query($search_array);

        //change url to "list" and redirect with cached search id.
        redirect("admin/mytasks/list-tasks/$search_id");

    }

    /**
     * add new task
     *
     */
    function __addTask()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/mytasks/');
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
            if (!is_numeric($_POST['tasks_client_id']) || !is_numeric($_POST['tasks_created_by_id']) || $_POST['tasks_events_id'] == '') {

                //log this error
                $this->__errorLogging(__line__, __function__, __file__, 'Adding new task failed: [Some or All] Required hidden form fileds missing or invalid');

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
        $this->__listTasks();
    }

	/**
     * edit a task description
     *
     */
    function __editDescription()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/mytasks');
        }

        /** PERMISSION CHECK
         *  only the following users can carry out this action
         *  (1) owner of the task
         *  (2) global admin
         *  (3) project leader
         */
        if ($next) {

            //get task super users
            $superusers = $this->tasks_model->superUsers($this->input->post('tasks_id'));
            $this->data['task_super_users'] = $superusers; //just for debugging
            $this->data['debug'][] = $this->tasks_model->debug_data;

            //check if team member has permission to edit
            if (in_array($this->data['vars']['my_id'], $superusers) || $this->data['vars']['my_group'] == 1) {

                //permission granted
                $next = true;

            } else {

                //permission denied
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_permission_denied']);

                //halt
                $next = false;
            }

        }

        //validate form & display any errors
        if ($next) {
            if (!$this->__flmFormValidation('edit-description')) {

                //show error
                $this->session->set_flashdata('notice-error', $this->form_processor->error_message);

                //halt
                $next = false;
            }
        }

       


        //edit task
        if ($next) {
            if ($this->tasks_model->editDescription()) {
                //show success
                $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);
                

            } else {
                //show error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                //halt
                $next - false;
            }
        }
        $this->data['debug'][] = $this->tasks_model->debug_data;

 

        //redirect back to my last view
        if ($this->input->post('redirect_url') != '') {
            redirect($this->input->post('redirect_url'));
        }

        //show task page
        $this->__listTasks();
    }

    /**
     * edit a task
     *
     */
    function __editTask()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/mytasks');
        }

        /** PERMISSION CHECK
         *  only the following users can carry out this action
         *  (1) owner of the task
         *  (2) global admin
         *  (3) project leader
         */
        if ($next) {

            //get task super users
            $superusers = $this->tasks_model->superUsers($this->input->post('tasks_id'));
            $this->data['task_super_users'] = $superusers; //just for debugging
            $this->data['debug'][] = $this->tasks_model->debug_data;

            //check if team member has permission to edit
            if (in_array($this->data['vars']['my_id'], $superusers) || $this->data['vars']['my_group'] == 1) {

                //permission granted
                $next = true;

            } else {

                //permission denied
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_permission_denied']);

                //halt
                $next = false;
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

        //validate hidden fields
        if ($next) {
            if (!is_numeric($_POST['tasks_id']) || $_POST['tasks_events_id'] == '') {

                //log this error
                $this->__errorLogging(__line__, __function__, __file__, 'Editing task failed: [Some or All] Required hidden form fileds missing or invalid');

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
                //show error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                //halt
                $next - false;
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
        $this->__listTasks();
    }

	/**
     * change user assigned to task
     *
     */
    protected function __editAssigned()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/mytasks');
        }

        /** PERMISSION CHECK
         *  only the following users can carry out this action
         *  (1) owner of the task
         *  (2) global admin
         *  (3) project leader
         */
        if ($next) {

            //get task super users
            $superusers = $this->tasks_model->superUsers($this->input->post('tasks_id'));
            $this->data['task_super_users'] = $superusers; //just for debugging
            $this->data['debug'][] = $this->tasks_model->debug_data;

            //check if team member has permission to edit
            if (in_array($this->data['vars']['my_id'], $superusers) || $this->data['vars']['my_group'] == 1) {

                //permission granted
                $next = true;

            } else {

                //permission denied
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_permission_denied']);

                //halt
                $next = false;
            }

        }

        //validate form & display any errors
        /*if ($next) {
            if (!$this->__flmFormValidation('edit_task')) {

                //show error
                $this->session->set_flashdata('notice-error', $this->form_processor->error_message);

                //halt
                $next = false;
            }
        }*/

        //validate hidden fields
        if ($next) {
            if (!is_numeric($_POST['tasks_id'])) {

                //log this error
                $this->__errorLogging(__line__, __function__, __file__, 'Editing task failed: [Some or All] Required hidden form fileds missing or invalid');

                //show error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                //halt
                $next = false;
            }
        }

        

        //edit task
        if ($next) {
        	
            if ($this->tasks_model->editAssigned()) {
                //show success
                $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);
                //events tracker (completed task)
                if ($this->input->post('tasks_status') == 'completed') {
                    $this->__eventsTracker('completed-task', array());
                }

                $this->__send_mail_to_stuff();

            } else {
                //show error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                //halt
                $next - false;
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
        $this->__listTasks();
    }


    /**
     * change user assigned time to task
     *
     */
    protected function __editAssignedTime()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        $tasks_id = $this->input->post('tasks_id');
        $assigned_time = $this->input->post('assigned_time');

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit']) || !$tasks_id || !$assigned_time) {
            redirect('/admin/mytasks');
        }

        /** PERMISSION CHECK
         *  only the following users can carry out this action
         *  (1) owner of the task
         *  (2) global admin
         *  (3) project leader
         */
        if ($next) {

            //get task super users
            $superusers = $this->tasks_model->superUsers($this->input->post('tasks_id'));
            $this->data['task_super_users'] = $superusers; //just for debugging
            $this->data['debug'][] = $this->tasks_model->debug_data;

            //check if team member has permission to edit
            if (in_array($this->data['vars']['my_id'], $superusers) || $this->data['vars']['my_group'] == 1) {

                //permission granted
                $next = true;

            } else {

                //permission denied
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_permission_denied']);

                //halt
                $next = false;
            }

        }

        //edit task
        if ($next) {
            
            if ($this->tasks_model->editAssignedTime($tasks_id, $assigned_time)) {
                //show success
                $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);

                $this->__send_mail_to_stuff();
            } else {
                //show error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                //halt
                $next - false;
            }
        }
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //redirect back to my last view
        if ($this->input->post('redirect_url') != '') {
            redirect($this->input->post('redirect_url'));
        }

        //show task page
        $this->__listTasks();
    }


    private function __send_mail_to_stuff()
    {
        $this->load->model('team_profile_model');
        $this->load->model('project_model');

        $tasks_id = $this->input->post('tasks_id');

        //get member row
        $member_id = $this->input->post('tasks_assigned');
        $member = $this->team_profile_model->find($member_id);
        
        //get project row
        $project_id = $this->input->post('tasks_project_id');
        $project = $this->project_model->find($project_id);
        
        $email_vars['team_profile_full_name'] = $member->team_profile_full_name;
        $email_vars['projects_url']   = site_url("admin/project/".$project_id."/view");
        $email_vars['projects_title'] = $project->projects_title;
        $email_vars['tasks_url']  = site_url("admin/tasksexpanded/$project_id/$tasks_id");
        $email_vars['tasks_text'] = $this->input->post('tasks_text');
        $email_vars['tasks_start_date'] = $this->input->post('tasks_start_date');
        $email_vars['tasks_startend_date'] = $this->input->post('tasks_end_date');
        $email_vars['company_email_signature'] = $this->data['settings_company']['company_email_signature'];

        //get message template from database
        $template = $this->settings_emailtemplates_model->getEmailTemplate('admin_new_task_created');
        $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

        //parse email
        $email_message = parse_email_template($template['message'], $email_vars);
        
        //send email
        email_default_settings(); //defaults (from emailer helper)
        $this->email->to($member->team_profile_email);
        $this->email->subject($title . $template['subject']);
        $this->email->message($email_message);
        $this->email->send();
    }


    /**
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //form validation
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
		elseif($form == 'edit-description')
		{
			//check required fields
            $fields = array(
                'tasks_text' => $this->data['lang']['lang_title']);
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
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     *
     */
    function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //[memebers projects]
        $data = $this->project_members_model->membersProjects(0, 'list', $this->data['vars']['my_id'], 'open');
        $this->data['debug'][] = $this->project_members_model->debug_data;
        $this->data['lists']['all_projects'] = create_pulldown_list($data, 'projects', 'id');

    }

    /**
     * log any error message into the log file
     *
     */
    function __errorLogging($theline = '', $thefunction = '', $thefile = '', $themessage = 'system error')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        $message_log = "[FILE: $thefile]  [LINE: $theline]  [FUNCTION: $thefunction]  [MESSAGE: $themessage]";
        log_message('error', $message_log);

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
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //--------------record a new event-----------------------
        if ($type == 'new-task') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->input->post('tasks_project_id');
            $events['project_events_type'] = 'task';
            $events['project_events_details'] = $this->input->post('tasks_text');
            $events['project_events_action'] = 'lang_tl_add_new_task';
            $events['project_events_target_id'] = 0;
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'team';

            //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;
        }

        //--------------record a new event-----------------------
        if ($type == 'completed-task') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->input->post('tasks_project_id');
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
        $this->data['controller_profiling'][] = __function__;

        //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']);

        //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file mytasks.php */
/* Location: ./application/controllers/admin/mytasks.php */
