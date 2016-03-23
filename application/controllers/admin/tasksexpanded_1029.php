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
    public $project_id;
    public $task_id;

    /**
     * constructor method
     */
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'task_expanded.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_tasks'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';
        
        $this->data['vars']['return_link'] = $this->data['vars']['site_url_admin'].'/project/'.$this->uri->segment(3).'/view';

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
        
        $this->__commonAdmin_LoggedInCheck();
        //login check
        if ($this->data['vars']['my_user_type'] != 'team'){
            /*Redirect to client task expanded*/
            header("Location: /client/tasksexpanded/".$this->uri->segment(3)."/".$this->uri->segment(4));
            exit;
        }

        //get project id, task id
        $this->project_id = intval($this->uri->segment(3));
        $this->task_id = intval($this->uri->segment(4));
        //get the action from url
        $action = $this->uri->segment(5);

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;
        $this->data['vars']['task_id'] = $this->task_id;
        
        //assigned User array
        $this->data['vars']['assigned'] = $this->tasks_model->getTaskAssignedUser($this->task_id);


        //save acceptoffer
        if($this->input->post('taskoffer'))
        {
            $this->tasks_model->taskofferTask($this->task_id);
			
			// BAD, hard-coded but this is how the model works :(
			// if taskoffer=2, means it was rejected, insert reason in the tasks_reject_feedback table
        	if ( $this->input->post('taskoffer') == 2 && isset($_POST['feedback']) )
			{
				$this->load->model('tasks_reject_feedback_model');
           		$id = $this->tasks_reject_feedback_model->addTaskRejectFeeback( $this->project_id, $this->task_id, $this->data['vars']['my_id'], $this->input->post('feedback') );
			}
        }

        $task = $this->tasks_model->getTask($this->task_id);
        
        //check if user can edit task description
        if($task['tasks_created_by_id'] == $this->data['vars']['my_id'])
        {
            $this->data['vars']['has_edit_permission'] = 1;
        }
        else
        {
            $this->data['vars']['has_edit_permission'] = 0;
        }
        
        
        $this->data['vars']['accepted'] = $task['accepted'];

        $this->data['vars']['taskname'] = $task['tasks_text'];
        $this->data['vars']['descr'] = $task['tasks_description'];

        $this->data['vars']['timedoctortaskid'] = $task['timedoctortaskid'];

        $this->data['vars']['assigned_time'] = $task['estimatedhours'];
        $this->data['vars']['logged_time'] = $task['hourslogged'];
        $this->data['vars']['time_remaining'] = doubleval(($task['estimatedhours'] - $task['hourslogged']));
        
      



        //check acceptoffer
        if($this->data['vars']['accepted']+1 == 1) {
            //Assigned (1)
            $this->data['visible']['acceptoffer'] = 1;
			$this->data['vars']['taskDetailsStyle'] = 'pointer-events: none; position:relative;';
        }
		else
        {
			$this->data['vars']['taskDetailsStyle'] = '';
        }

        //create pulldown lists
        $this->__pulldownLists();

        //route the rrequest
        switch ($action) {
            default:
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

            case 'time-doctor':
                $this->__viewTimeDoctorModal();
                break;

            case 'time-doctor-add':
                $this->__addTimeDoctorModal();
                break;

            case 'change-status':
                $this->__changeStatus();
                break;

                
        }

        //css - active tab
        $this->data['vars']['css_active_tab_tasks'] = 'side-menu-main-active';

        //load view
        $this->__flmView('admin/main');
    }


    /**
     * main-handler function
     * manage all project tasks
     *
     */
    protected function __tasksView()
    {
        /* --------------URI SEGMENTS---------------
        * [example]
        *  /admin/tasksexpanded/95/950
        * (2)->controller
        * (3)->project_id
        * (4)->task_id
        * (5)->action
        * -----------------------------------------*/
        
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //refresh task status (all tasks)
        $this->refresh->taskStatus('all');
        
        //assigned_by
        $assigner = $this->teamprofile_model->teamMemberDetails($task['tasks_created_by_id']);
        if(!empty($assigner))
        {
            $this->data['vars']['assigned_by'] = $assigner['team_profile_full_name'];
        }

        /** TABLE CLEAN UP **/
        $this->tasks_model->tableCleanup();


        $this->data['blk1'] = $this->tasks_model->getSubTasks($this->task_id, $this->project_id);
        $this->data['reg_blocks'][] = 'files';
        $this->data['blocks']['files'] = $this->files_model->getTaskFiles($this->task_id);        
        
        /** SEND DATA FOR ADDITIONAL PREPARATION **/
        $this->data['blocks']['tasks'] = $this->__prepTasksView($this->data['blocks']['tasks']);
    }

    /**
     * 
     *
     */
    protected function __changeStatus()
    {
        $stage = (int)$this->input->post('stage');
        $logtime = (int)$this->input->post('logtime');


        if ($this->data['vars']['accepted'] != $stage)
        {
            if($stage == 2)
            {
                //TASK model
                $task = $this->tasks_model->getTask($this->task_id);

                $project = $this->projects_model->projectDetails($this->project_id);
                $clients = $this->clients_model->clientList($task['tasks_client_id']);

                $vars = array(
                    'task_title'              => $task['tasks_text'],
                    'client_users_full_name'  => $client['client_users_full_name'],
                    'project_name'            => $project['projects_title'],
                    'project_id'              => $this->project_id,
                    'company_email_signature' => 'ISO Developers'
                );
                foreach($clients as $client)
                {
                    $this->__emailer('new_client_approval_notification', $client['client_users_email'], $vars);
                }

                $rates = array(75, 75, 95, 125, 125, 65, 125);

                $newAmountLogged = doubleval($logtime);
                $totalLogged = $task['hourslogged'] + $newAmountLogged;
                if($totalLogged > $task['estimatedhours']) $totalLogged = $task['estimatedhours']; //added by Tomasz
                $remainingLogged = $task['estimatedhours'] - $totalLogged;

                //3 hours estimated only 2 used, subtract the final hour for profit.
                $rate = $rates[$task['billingcategory'] - 1]; //$1
                $amount = $remainingLogged * $rate;
                
                $balance = $project['credit_amount_remaining'];

                if($amount >= 0)
                {
                    $balance -= $amount;
                }
                else
                {
                    echo 'You can not subtract anymore hours from this task.';
                }

                $this->clients_model->updateCreditAmountRemaining($task['tasks_client_id'], $balance);

                /*Commented by Marco 2015-10-14, as requested by Abi to stop putting automatically the Assigned time in the logged time
                $prepared = $db->prepare("UPDATE tasks SET hourslogged = ? WHERE tasks_id = ?");
                $prepared->execute(array($task['estimatedhours'], $task['tasks_id']));
                */                  
                if($balance <= 0)
                {
                    $this->projects_model->updateStatus2($task['tasks_client_id'], 7);
                }
            }
            
            $this->tasks_model->updateStage($this->task_id, $stage, $totalLogged); 
        }

        redirect('/admin/tasksexpanded/'.$this->project_id.'/'.$this->task_id.'/view');
    }



    /**
     * 
     *
     */
    protected function __addTimeDoctorModal()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        if(!$this->data['vars']['timedoctortaskid'])
        {
            $project = $this->projects_model->projectDetails($this->project_id);
            if(!$project['timedoctorid'])
            {
                $this->data['visible']['wi_notification'] = 1;
                $this->data['vars']['notification'] = 'Project has no Time Doctor ID';
            }
            else
            {
                $task = array(
                    'task_name'  => $this->data['vars']['taskname'],
                    'project_id' => $project['timedoctorid'],
                    'user_id'    => $this->data['vars']['assigned']['team_profile_timedoctorid'],
                );
                $result = $this->timedoctor_model->createTask($this->data['config']['timedoctor_admin_profile_id'], $task);
                $this->data['debug'][] = $this->timedoctor_model->debug_data;

                if(empty($result['task_id']))
                {
                    $this->data['visible']['wi_notification'] = 1;
                    $this->data['vars']['notification'] = 'Error: '.print_r($result, true);
                }
                else
                {
                    $res = $this->tasks_model->setTimeDocTask($this->task_id, $result['task_id']);
                    if(!$res) die($this->tasks_model->debug_data);
                }
                //var_dump($result); exit;
            }
        }
        //view
        $this->__viewTimeDoctorModal();
    }

    /**
     * 
     *
     */
    function __viewTimeDoctorModal()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;
        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'task_expanded.modal.html';

        if(!$this->data['vars']['timedoctortaskid'])
        {
            $this->data['visible']['wi_task_time_doctor_add'] = 1;
            $this->data['vars']['add_time_doctor_link'] =
                site_url("admin/tasksexpanded/".$this->project_id."/".$this->task_id."/time-doctor-add");
        }
        else
        {
            $this->data['visible']['wi_task_time_doctor_view'] = 1;
            //$this->data['visible']['wi_notification'] = 1;
            //$this->data['vars']['notification'] = 11111;
        }

    }

    function getClients($clientid){
        $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 

        $prepared = $db->prepare("SELECT * FROM client_users WHERE client_users_clients_id = ? ORDER BY client_users_id DESC");
        $prepared->execute(array($clientid));
        $rows = $prepared->fetchAll();
        return $rows;
    }

    function getProject($projectid){
        $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 

        $prepared = $db->prepare("SELECT * FROM projects WHERE projects_id = ?");
        $prepared->execute(array($projectid));
        $row = $prepared->fetch(PDO::FETCH_ASSOC);
        return $row;
    }

    function __emailer($template, $toSendEmail = '', $vars = array())
    {

        //specific passed variables
        foreach ($vars as $key => $value) {
            $this->data['email_vars'][$key] = $value;
        }

        //get message template from database
        $template = $this->settings_emailtemplates_model->getEmailTemplate($template);
        $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

        //parse email
        $email_message = parse_email_template($template['message'], $this->data['email_vars']);

        //send email
        email_default_settings(); //defaults (from emailer helper)
        $this->email->to($toSendEmail);
        $this->email->subject($template['subject']);
        $this->email->message($email_message);
        $this->email->send();

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

        //project members
        $data = $this->project_members_model->listProjectmembers($this->project_id);
        $this->data['debug'][] = $this->project_members_model->debug_data;
        $this->data['lists']['all_team_members'] = create_pulldown_list($data, 'team_members', 'id');

        //stage list
        $data = $this->tasks_model->listStages();
        $this->data['debug'][] = $this->tasks_model->debug_data;
        $this->data['lists']['all_stages'] = create_pulldown_list($data, 'tasks_stage', 'id', $this->data['vars']['accepted']);
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