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
class Tasks extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.tasks.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_tasks'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';
        
        $this->load->model('task_model');
        $this->list_limit = $this->data['settings_general']['results_limit'];
    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    public function index()
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
        /*$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 
        $result = $db->prepare("SELECT `id` FROM oauth WHERE user_id = ?");
        $result->execute(array($userid));
        if($result->rowCount() == 0){ header("Location: http://pms.isodeveloper.com/admin/oauth"); exit; }*/
        

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //get project id
        $this->project_id = $this->uri->segment(3);

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;

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
				
			case 'edit-description':
                $this->__editDescription();
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
    protected function __tasksPreRun()
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
    protected function __tasksView()
    {
        //$this->list_limit = 5;
        
        if(isset($_POST['signoff'], $_POST['taskid']))
        {        
            $signoff = $this->input->post('signoff');    
            $taskid = $this->input->post('taskid');
            
            $memo = $this->input->post('memo');
            if ($memo)
            {        
                $this->task_model->update($taskid, ['memo' => $memo]);
            }

            $row = $this->task_model->find($taskid, 1);
            if ($row['signedoff'] == 1){
                //not approved
                if ($signoff != 1){
                    //approved  
                    $this->task_model->update($taskid, ['signedoff' => 1]);
                }
            }elseif ($row['signedoff'] != 0){
                //approved
                echo "You can not change a tasks approval status that has already been approved, contact David or Jonathan for support.";
            }

            if ($row['signedoff'] == 0){
                if ($signoff == 1){
                    //not approved notify admin          
                    $udata = [
                        'tasks_status' => 'pending',
                        'accepted' => 3, //notify admin
                        'signedoff' => 1
                             
                    ];
                    $this->task_model->update($taskid, $udata);
                    
                    //send email to admins
                }else{
                    //approved no need to notify admin mark as done
                    
                    $udata = [
                        'tasks_status' => 'completed',
                        'accepted' => 4, //done
                        'signedoff' => 2
                             
                    ];
                    $this->task_model->update($taskid, $udata);
                }
            }
        }

        //refresh task status (all tasks)
        //$this->refresh->taskStatus('all');

        //uri segments
        $project_id = $this->uri->segment(3);
        //$milestone_id = ($this->uri->segment(5) > 0) ? $this->uri->segment(5) : 0;
        $status = $this->_get_status_param($this->input->get('status'));
        $scope = $this->input->get('scope') == 'my' ? 'my' : '';
        $offset = (is_numeric($this->uri->segment(5))) ? $this->uri->segment(5) : 0;
        
        //building conditions
        $where = array();
        $where['tasks_project_id'] = $project_id;
        if($status != '')
        {
            $status = str_replace('-', ' ', $status);
            $where['t1.tasks_status'] = $status;
        }
        if($scope =='my' )
        {
            $where['t1.tasks_assigned_to_id'] = $this->data['vars']['my_id'];
        }
        
        // build order by
        $order = $this->_get_order();
        $order_by = $this->_get_orderby();
        if($order == '')
        {
            $order = 'created';
            $order_by = 'desc';
        }
        $this->data['vars']['order'] = $this->_get_order();
        $this->data['vars']['orderby'] = $this->_get_orderby();
        
        //get results and save for tbs block merging
        $this->task_model->set_alias();
                                
        $this->data['reg_blocks'][] = 'tasks';
        $this->data['blocks']['tasks'] = $this->task_model
            ->select('t1.*, t2.*, t3.team_profile_full_name asssigned_by_name, estimatedhours*IFNULL(bcat_rate, 0) estimatedcost, 
                IFNULL(bcat_name, "N/A") bcat_name, IFNULL(bcat_rate, 0) bcat_rate, IFNULL(bcat_label_class, "label-default") bcat_label_class', FALSE)
            ->join_assigned_to()
            ->join_assigned_by()
            ->join_billing_category()
            ->where($where)
            ->order_by($order, $order_by)
            ->limit($this->list_limit, $offset)
            ->find_all(); 
           
        $select_sum = "
            SUM(estimatedhours*IFNULL(bcat_rate, 0)) assigned_total, 
            SUM( if(tasks_status='completed', estimatedhours*IFNULL(bcat_rate, 0), 0) ) completed_total,
            SUM( if(tasks_status='completed', estimatedhours*hourlyrate, 0) ) development_total
        ";
        $this->data['vars']['totals'] = $this->task_model
            ->select($select_sum, FALSE)
            ->join_assigned_to()
            ->join_billing_category()
            ->where($where)
            ->find_one_fill_zero();
         
        //echo $this->task_model->last_sql(); exit;
        
        //count results rows - used by pagination class
        $rows_count = $this->task_model
            //->select('t1.*, t2.*, t3.team_profile_full_name asssigned_by_name')
            ->join_assigned_to()
            ->join_assigned_by()
            ->where($where)
            ->order_by($order, $order_by)
            //->limit($this->list_limit, $offset)
            ->count_all();
        
        // setup pagination
        $this->_setup_pagination($rows_count, $project_id);

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

        //add base url
        $this->data['vars']['base'] = site_url("admin/tasks/$project_id/view");
        
        //flow control
        $next = true;
    }

    /**
     * add new task
     *
     */
    protected function __tasksAdd()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //PERMISSIONS CHECK
        /*if ($this->data['project_permissions']['add_item_my_project_my_tasks'] != 1) {
            //show error
            $this->session->set_flashdata('notice-error', $this->data['lang']['lang_permission_denied']);

            //halt
            $next = false;
        }*/

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
            if ($tasks_id = $this->tasks_model->addTask()) {
                //show success
                $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);
                //events tracker
                $this->__eventsTracker('new-task', array());
                
                // Added by Tomasz
                //$this->__send_mail_to_stuff($tasks_id);
                //end by Tomasz
                $this->__emailer('mailqueue_new_task', array('task_id'=>$tasks_id));

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

	protected function __editDescription()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        $task_notes = $this->input->post('task_notes');
        $task_id = intval($this->input->post('task_id'));
        $this->tasks_model->updateDescription($task_id, $task_notes);
		
        redirect('/admin/tasksexpanded/' . $this->project_id . '/'.$task_id.'');
    }

    /**
     * edit a task
     *
     */
    protected function __tasksEdit()
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
     * @param	string $form identify the form to validate
     */
    private function __flmFormValidation($form = '')
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
    private function __errorLogging($theline = '', $thefunction = '', $thefile = '', $themessage = 'system error')
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
    private function __pulldownLists()
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
    private function __eventsTracker($type = '', $events_data = array())
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
    private function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']); //complete the view
        $this->__commonAll_View($view);
    }
    
    // Added by Tomasz
    /*
    private function __send_mail_to_stuff($tasks_id)
    {
        $this->load->model('team_profile_model');
        $this->load->model('project_model');
        //get member row
        $member_id = $this->input->post('tasks_assigned_to_id');
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
        $email_vars['tasks_end_date'] = $this->input->post('tasks_end_date');
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
    */

    // -- __emailer-------------------------------------------------------------------------------------------------------
    /**
     * send out an email
     *
     * @access  private
     * @param   string
     * @return  void
     */
    private function __emailer($email = '', $vars = array())
    {
        //common variables
        $email_vars = array();
        $email_vars['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];

        //------------------------------------queue email in database-------------------------------
        /** THIS WIL NOT SEND BUT QUEUE THE EMAILS*/
        if ($email === 'mailqueue_new_task')
        {
            $this->load->model('team_profile_model');
            $this->load->model('project_model');

            $sqldata = array();
            //email vars
            //get member row
            $member_id = intval($this->input->post('tasks_assigned_to_id'));
            $member = $this->team_profile_model->find($member_id);
            
            //get project row
            $project_id = intval($this->input->post('tasks_project_id'));
            $project = $this->project_model->find($project_id);
            
            $email_vars['team_profile_full_name'] = $member->team_profile_full_name;
            $email_vars['projects_url']   = site_url('admin/project/'.$project_id.'/view');
            $email_vars['projects_title'] = $project->projects_title;
            $email_vars['tasks_url']  = site_url('admin/tasksexpanded/'.$project_id.'/'.$vars['tasks_id']);
            $email_vars['tasks_text'] = $this->input->post('tasks_text');
            $email_vars['tasks_start_date'] = $this->input->post('tasks_start_date');
            $email_vars['tasks_end_date'] = $this->input->post('tasks_end_date');

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('admin_new_task_created');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //parse email
            $email_message = parse_email_template($template['message'], $email_vars);


            //set sqldata() for database
            $sqldata['email_queue_message'] = parse_email_template($template['message'], $email_vars);
            $sqldata['email_queue_subject'] = $template['subject'];
            $sqldata['email_queue_email'] = $member->team_profile_email;

            $this->email_queue_model->addToQueue($sqldata);
            $this->data['debug'][] = $this->email_queue_model->debug_data;
        }
    }

    private function _get_status_param($status)
    {
        $result = '';
        switch($status)
        {
            case 'completed':
                $result = $status;
                break;
            case 'pending':
                $result = 'pending';
                break;
            case 'schedule':
                $result = 'behind schedule';
                break;
        }
        
        return $result;
    }
    
    private function _get_order()
    {
        $result = '';
        
        $order = $this->input->get('order');
        switch($order)
        {
            case 'title':
                $result = 'trim(tasks_text)';
                break;
            case 'developer':
                $result = 't2.team_profile_full_name';
                break; 
            case 'stage':
                $result = 'tasks_status';
                break;
            case 'created':
                $result = 'created';
                break;
            default:
                //$result = 'projects_date_created';
                break;
        } 
        
        return $result;
    }
    
    private function _get_orderby()
    {
        $result = 'asc';
        
        $orderby = $this->input->get('orderby');
        switch($orderby)
        {
            case 'asc':
            case 'desc':
                $result = $orderby;
                break;
        } 
        
        return $result;
    }
    
    private function _get_suffix()
    {
        $result = array();
        
        //add scope
        $scope = $this->input->get('scope');
        if($scope)
        {
            $result[] = "scope=$scope";
        }
        
        //add status
        $status = $this->input->get('status');
        if($status) $result[] = "status=$status";
        
        //add order fields
        $order = $this->_get_order();
        $order_by = $this->_get_orderby();
        if($order != '') 
        {
            $result[] = "order=".$this->input->get('order');
            $result[] = "orderby=$order_by";
        }
        
        $str = '';
        if(!empty($result))
        {
            $str = "?" . join('&amp;', $result);
        }
        
        return $str;
    }
    
    private function _setup_pagination($rows_count, $project_id)
    {
        $config = pagination_default_config();
        $config['base_url']     = site_url("admin/tasks/$project_id/view");
        $config['total_rows']   = $rows_count;
        $config['per_page']     = $this->list_limit;
        $config['uri_segment']  = 5; //the offset var
        $config['suffix']       = $this->_get_suffix();   
        $config['first_url']    = site_url("admin/tasks/$project_id/view" . $this->_get_suffix());
        $this->pagination->initialize($config);
        
        $this->data['vars']['pagination'] = $this->pagination->create_links();    
    }
    //end by Tomasz

}

/* End of file tasks.php */
/* Location: ./application/controllers/admin/tasks.php */