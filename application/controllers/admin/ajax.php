<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all ajax related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Ajax extends MY_Controller
{

    // --  constructor- -------------------------------------------------------------------------------------------------------
    public function __construct()
    {

        parent::__construct();

        //reduce error reporting to only critical
        @error_reporting(E_ERROR);

        //turn off codeigniter profiler (which may be on in MY_Controller) during debug mode
        $this->output->enable_profiler(false);

    }

    // -- index -------------------------------------------------------------------------------------------------------
    /**
     * This is our re-routing function and is the inital function called
     */
    public function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //---check if logged in, using this local function and not one in MY_Controller--------
        if ($this->uri->segment(2) == 'team') {
            $this->__flmUserLoggedInCheck('team');
        }

        //---check if logged in, using this local function and not one in MY_Controller--------
        if ($this->uri->segment(2) == 'client') {
            $this->__flmUserLoggedInCheck('client');
        }

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'editable-client-profile':
                $this->__editableClientProfile();
                break;

            case 'editable-user-profile':
                $this->__editableUserProfile();
                break;

            case 'editable-team-profile':
                $this->__editableTeamProfile();
                break;

            case 'validation-is-team-email-in-use':
                $this->__validationIsEmailInUse('team_member');
                break;

            case 'editable-group-permissions':
                $this->__changePermissionsGroup();
                break;

            case 'delete-group':
                $this->__deleteGroup();
                break;

            case 'delete-milestone':
                $this->__deleteMilestone();
                break;
			case 'complete-task':
                $this->__completeTask();
                break;
			case 'count-notifications':
                $this->__countNotifications();
                break;
            case 'delete-task':
                $this->__deleteTask();
                break;
				
			case 'delete-lead':
                $this->__deleteLead();
                break;	

            case 'delete-invoice':
                $this->__deleteInvoice();
                break;
				
			case 'delete-message':
                $this->__deleteMessage();
                break;

            case 'delete-invoice-item':
                $this->__deleteInvoiceItem();
                break;

            case 'delete-project-message':
                $this->__deleteProjectMessage();
                break;

            case 'delete-project-message-reply':
                $this->__deleteProjectMessageReply();
                break;

            case 'delete-project-team-message':
                $this->__deleteProjectTeamMessage();
                break;

            case 'delete-project-team-message-reply':
                $this->__deleteProjectTeamMessageReply();
                break;

            case 'delete-project-file-message':
                $this->__deleteProjectFileMessage();
                break;

            case 'delete-project-file-message-reply':
                $this->__deleteProjectFileMessageReply();
                break;

            case 'delete-project-my-notes':
                $this->__deleteProjectMyNotes();
                break;
				
			case 'log-action':
                $this->__logAction();
                break;

            case 'toggle-timer':
                $this->__toggleTimer();
                break;

            case 'refresh-timer':
                $this->__refreshTimer();
                break;

            case 'reset-timer':
                $this->__resetTimer();
                break;

            case 'validation-is-group-available':
                $this->__validationIsGroupAvailable();
                break;

            case 'project-milestones-list':
                $this->__projectMilestonesList();
                break;

            case 'clients-projects-list':
                $this->__clientsProjectsList();
                break;

            case 'get-invoice-item-details':
                $this->__getInvoiceItemDetails();
                break;

            case 'backup-database-now':
                $this->__backupDatabaseNow();
                break;

            case 'delete-backup-file':
                $this->__deleteBackupFile();
                break;

            case 'delete-ticket':
                $this->__deleteTicket();
                break;

            case 'delete-project-member':
                $this->__deleteProjectMember();
                break;

            case 'delete-ticket-reply':
                $this->__deleteTicketReply();
                break;

            case 'delete-client':
                $this->__deleteClient();
                break;

            case 'delete-project':
                $this->__deleteProject();
                break;

            case 'delete-bug':
                $this->__deleteBug();
                break;

            case 'delete-quotation':
                $this->__deleteQuotation();
                break;

            case 'delete-team-member':
                $this->__deleteTeamMember();
                break;

            case 'delete-invoice-payment':
                $this->__deletePayment();
                break;

            case 'delete-department':
                $this->__deleteTicketsDepartment();
                break;

            case 'toggle-hot':
                $this->__toggleHot();
                break;

            case 'toggle-lost':
                $this->__toggleLost();
                break;

            default:
                $this->__default($action);
                break;
        }

        //log debug data
        $this->__ajaxdebugging();

    }

    // -- __flmUserLoggedInCheck- -------------------------------------------------------------------------------------------------------
    /**
     * checks if user is logged in, else redirects
     */

    function __flmUserLoggedInCheck($user_type = 'team')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //-----set for admin------------------------
        if ($user_type == 'team') {
            //is user logged in..else redirect to login page
            if (!is_numeric($this->session->userdata('team_profile_id'))) {

                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_session_timed_out'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log debug data
                $this->__ajaxdebugging();

                //load the view for json echo
                $this->__flmView('common/json');

                //now die and exit
                die('Session timed out - Please login again');
            }

        }

        //-----set for admin------------------------
        if ($user_type == 'client') {
            //is user logged in..else redirect to login page
            if (!is_numeric($this->session->userdata('client_users_clients_id'))) {

                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_session_timed_out'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log debug data
                $this->__ajaxdebugging();

                //load the view for json echo
                $this->__flmView('common/json');

                //now die and exit
                die('Session timed out - Please login again');
            }
        }

    }

    // -- __default- -------------------------------------------------------------------------------------------------------
    /**
     * if nothing was passed in url
     */

    function __default($action = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        header('HTTP/1.0 400 Bad Request', true, 400);
        $this->jsondata = array(
            'result' => 'error',
            'message' => 'An error has occurred',
            'debug_line' => __line__);

        //log this error
        log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Routing errror. Specified method/action ($action) not found]");

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __editableClientProfile- -------------------------------------------------------------------------------------------------------
    /**
     * edit client profile via inline editable
     */

    function __editableClientProfile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //load models
        $this->load->model('clients_model');
        $this->load->model('clientsoptionalfields_model');

        //collect data sent by editable.js
        $id = $this->input->post('pk');
        $name = $this->input->post('name');
        $value = $this->input->post('value');

        //form validation - create array of required form fields
        //determin any required fields for optional fields and merge
        $required = array(
            'clients_company_name',
            'clients_address',
            'clients_city',
            'clients_state',
            'clients_zipcode');
        $optional_fields = $this->clientsoptionalfields_model->optionalFields('enabled');
        $clients_optionalfield_array = clients_optionalfield_array($optional_fields);
        $required = @array_merge($required, $clients_optionalfield_array);

        //form validate required fields
        if (in_array($name, $required) && $value == '') {
            $next = false;
            echo $this->data['lang']['lang_item_is_required'];
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //update record & return http status
        if ($next) {

            //run update sql (for client primary user update)
            if ($name == 'client_users') {
                $update = $this->users_model->updatePrimaryContact($id, $value);
                $this->data['debug'][] = $this->clients_model->debug_data;
            } else {
                //run any other update to the form
                $update = $this->clients_model->updateClientDetails($id, $name, $value);
                $this->data['debug'][] = $this->clients_model->debug_data;
            }

            //log debug data
            $this->__ajaxdebugging();

            //check if update was successful
            if ($update) {
                header('HTTP/1.0 200 OK', true, 200);
            } else {
                echo 'Error saving data';
                header('HTTP/1.0 400 Bad Request', true, 400);
            }
        }
    }

    // -- __editableTeamProfile- -------------------------------------------------------------------------------------------------------
    /**
     * edit team profile via inline editable
     */

    function __editableTeamProfile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //load models
        $this->load->model('teamprofile_model');

        //collect data sent by editable.js
        $id = $this->input->post('pk');
        $name = $this->input->post('name');
        $value = $this->input->post('value');

        //form validation - create array of required form fields
        //determin any required fields for optional fields and merge
        $required = array(
            'team_profile_full_name',
            'team_profile_job_position_title',
            'team_profile_email',
            'team_profile_password',
            'team_profile_telephone',
            'groups_id');

        //form validate required fields
        if (in_array($name, $required) && $value == '') {
            $next = false;

            //frontend javascript is expecting http 200/400
            echo $this->data['lang']['lang_item_is_required'];
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //input validation - email field
        if ($next && $name == 'team_profile_email' && !is_email_address($value)) {
            $next = false;

            //frontend javascript is expecting http 200/400
            echo $this->data['lang']['lang_invalid_email'];
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //input validation - password field
        if ($next && $name == 'team_profile_password' && !is_strong_password($value)) {
            $next = false;

            //frontend javascript is expecting http 200/400
            echo $this->data['lang']['lang_password_must_be_at_least_eight'];
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //if updating email, check that it is unique
        if ($next && $name == 'team_profile_email') {
            if ($this->teamprofile_model->checkRecordExists('team_profile_email', $value) > 0) {
                $this->data['debug'][] = $this->teamprofile_model->debug_data;
                $next = false;

                //frontend javascript is expecting http 200/400
                echo $this->data['lang']['lang_email_address_alread_in_use'];
                header('HTTP/1.0 400 Bad Request', true, 400);
            }
        }

        /* PERMISSION - ADMIN ONLY FOR CHANGE OF GROUP*/
        if ($next) {
            if ($this->data['vars']['my_group'] != 1 && $name == 'team_profile_groups_id') {
                //show error
                echo $this->data['lang']['lang_permission_denied_info'];
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }
        }

        //update record & return http status
        if ($next) {

            //update record
            $update = $this->teamprofile_model->updateTeamMembersDetails($id, $name, $value);
            $this->data['debug'][] = $this->teamprofile_model->debug_data;

            //count number of admins
            //just in case last update left us with no admins (group_id = 1)
            if ($this->teamprofile_model->groupMembersCount(1) == 0) {
                //revert last user back to admin
                $update = $this->teamprofile_model->updateTeamMembersDetails($id, 'team_profile_groups_id', 1);
                $this->data['debug'][] = $this->teamprofile_model->debug_data;

                //frontend javascript is expecting http 200/400
                header('HTTP/1.0 400 Bad Request', true, 400);
                die('Atleast 1 admin is required');
            }

            //log debug data
            $this->__ajaxdebugging();

            //frontend javascript is expecting http 200/400
            if ($update) {
                header('HTTP/1.0 200 OK', true, 200);
            } else {
                echo 'Error saving data';
                header('HTTP/1.0 400 Bad Request', true, 400);
            }

        }

    }

    // -- __editableTeamProfileAvatar- -------------------------------------------------------------------------------------------------------
    /**
     * accepts avatar files uploaded via modal form.
     * initiates the jquery.fileupload.js (php backend class /libraries/UploadHandler.php)
     */

    function __editableTeamProfileAvatar()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load models
        $this->load->model('teamprofile_model');

        //initiate upload handler
        $this->load->library("UploadHandler");

        //log debug data
        $this->__ajaxdebugging();
    }

    // -- __changePermissionsGroup- -------------------------------------------------------------------------------------------------------
    /**
     * change user permissions for a group
     */

    function __changePermissionsGroup()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load models

        //form input
        $group_id = $this->input->post('pk');
        $category = $this->input->post('name');
        $level = $this->input->post('value');

        if (!$this->groups_model->permissionsChange($group_id, $category, $level)) {
            //output something, just for debugging
            header('HTTP/1.0 400 Bad Request', true, 400);
            die('Error changing permissions for group item');
        }
        $this->data['debug'][] = $this->groups_model->debug_data;

        //get new permissions and set to json aray - echo json for use in javascript
        $result = $this->groups_model->groupPermissions($group_id, $category);
        if (!empty($result)) {
            foreach ($result as $key => $value) {
                if ($value == 'yes') {
                    $result[$key] = '<span class="label label-info bns-display-show" id="bns-status-badge">' . $this->data['lang']['lang_yes'] . '</span>';
                }
                if ($value == 'no') {
                    $result[$key] = '<span class="label label-default bns-display-show" id="bns-status-badge">' . $this->data['lang']['lang_no'] . '</span>';
                }
            }
            //save data
            $this->jsondata = $result;

            //debug
            $this->data['debug'][] = $this->groups_model->debug_data;
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');

    }

    // -- __deleteGroup- -------------------------------------------------------------------------------------------------------
    /**
     * delete a group
     */

    function __deleteGroup()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('teamprofile_model');

        //flow control
        $next = true;

        /* -------------------GENERAL PERMISSIONS-------------------*/
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_permission_denied_info'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);
            //halt
            $next = false;
        }

        //get data
        $group_id = $this->input->post('data_mysql_record_id');

        //check if deleting this group is allowable
        if ($next) {
            if (!$this->groups_model->isActionAllowable($group_id, 'groups_allow_delete')) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_this_action_is_not_allowed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                $this->data['debug'][] = $this->groups_model->debug_data;
                $next = false;
            }
        }

        //check that the group is empty
        if ($next) {
            if ($this->teamprofile_model->groupMembersCount($group_id) !== 0) {
                //output something, just for debugging
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_this_group_is_not_empty'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                $this->data['debug'][] = $this->teamprofile_model->debug_data;
                $next = false;
            }
        }

        //delete the group
        if ($next) {
            if ($this->groups_model->deleteGroup($group_id)) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);
            } else {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                $next = false;
            }
            $this->data['debug'][] = $this->teamprofile_model->debug_data;
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json
        $this->__flmView('common/json');
    }

    // -- __deleteMilestone- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a milestone and also delete:
     *                      (1) Related Tasks
     */

    function __deleteMilestone()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('milestones_model');
        $this->load->model('tasks_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');
        $milestone_title = $this->input->post('data_mysql_record_id3');
        $project_id = $this->input->post('data_mysql_record_id4');

        /*--------------------------TEAM MEMBER PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            $next = $this->__validateTeamPermissions($project_id, 'delete_item_my_project_milestones');
        }

        //delete the milestone
        if ($next) {
            if ($this->milestones_model->deleteMilestone($id, 'milestone-id')) {

                //delete also the tasks
                $this->tasks_model->deletetask($id, 'milestone-id');

                //create json response
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);

                //refresh project progress
                $this->refresh->updateProjectPercentage($project_id);
                $this->data['debug'][] = $this->refresh->debug_data;

                //events tracker
                $this->__eventsTracker('delete_milestone', array(
                    'target_id' => $id,
                    'project_id' => $project_id,
                    'milestone_title' => $milestone_title));

                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                $next = false;

            }
            //debug
            $this->data['debug'][] = $this->milestones_model->debug_data;
            $this->data['debug'][] = $this->tasks_model->debug_data;
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteQuotation- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a quotation 
     */

    function __deleteQuotation()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');

        /*--------------------------TEAM MEMBER PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            $next = $this->__validateTeamPermissions('general', 'delete_item_quotations');
        }

        //delete the task
        if ($next) {
            $result = $this->quotations_model->deleteQuotation($id);
            $this->data['debug'][] = $this->quotations_model->debug_data;
            if ($result) {
                //success
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);
            } else {
                //error
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //halt
                $next = false;
            }
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }
 // -- __deleteLead- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a lead 
     */

    function __deleteLead()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('sales_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');
        //delete the task
        if ($next) {
            if ($this->sales_model->deleteLead($id)) {

                //refresh project progress
                $this->refresh->updateProjectPercentage($project_id);
                $this->data['debug'][] = $this->refresh->debug_data;

                //create json response
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                $next = false;

            }
            //debug
            //$this->data['debug'][] = $this->sales_model->debug_data;
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        /**
         * frontend javascript is expecting json array
         */
        $this->__flmView('common/json');
    }

 // -- __completeTask- -------------------------------------------------------------------------------------------------------
    /**
     * marking task as completed 
     */

    public function __completeTask()
    {
    	
//profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('tasks_model');

        //flow control
        $next = true;

        //get data
        $id = (int)$this->input->post('data_mysql_record_id');
        $timedoctortaskid = (int)$this->input->post('data_mysql_record_id3');
        $project_id = (int)$this->input->post('data_mysql_record_id4');

        //delete the task
        if ($next) {
            if ($this->tasks_model->completeTask($id)) {

                //refresh project progress
                $this->refresh->updateProjectPercentage($project_id);
                $this->data['debug'][] = $this->refresh->debug_data;

                //disabled time doctor task
                if($timedoctortaskid)
                {
                    $this->timedoctor_model->editTask($this->data['vars']['my_id'], $timedoctortaskid, false);
                }

                //create json response
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                $next = false;

            }
            //debug
            $this->data['debug'][] = $this->tasks_model->debug_data;
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        /**
         * frontend javascript is expecting json array
         */
        $this->__flmView('common/json');
    }

    // -- __deleteTask- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a task 
     */

    function __deleteTask()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('tasks_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');
        $project_id = $this->input->post('data_mysql_record_id4');

        //delete the task
        if ($next) {
            if ($this->tasks_model->deleteTask($id, 'task-id')) {

                //refresh project progress
                $this->refresh->updateProjectPercentage($project_id);
                $this->data['debug'][] = $this->refresh->debug_data;

                //create json response
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                $next = false;

            }
            //debug
            $this->data['debug'][] = $this->tasks_model->debug_data;
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        /**
         * frontend javascript is expecting json array
         */
        $this->__flmView('common/json');
    }

    // -- __toggleTimer- -------------------------------------------------------------------------------------------------------
    /**
     * start and stop or reset the timer for a task 
     */

    function __toggleTimer()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('timer_model');

        //flow control
        $next = true;
        chdir("__freshbooksapi");
        require_once("__freshbooksinit.php");
        chdir("..");
        $timer_id = $this->input->post('data_mysql_record_id');
        $project_id = $this->input->post('data_project_id');
        $timer_new_status = $this->input->post('data_timer_new_status');
        $taskid = $this->input->post('data_timer_task');
        $freshbooksstaffid = getFreshbooksStaffIdFromId($this->input->post('data_timer_userid'));
        //get data
        /*Get task id, and task notes*/
        
        $freshbookstaskid = getFreshbooksTaskIdFromId($taskid);
        $freshbooksprojectid = getFreshbooksProjectIdFromId($project_id);

        //validate required POST input
        if ($next) {

            //time status array
            if (!in_array($timer_new_status, $this->data['common_arrays']['timer_status']) || !is_numeric($timer_id)) {
                //log errror

                //show error
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Updating timer failed. Invalid Post data]");

                //halt
                $next = false;
            }

        }

        /*  PERMISSION CHECK - REVIEWED
        *  only the following users can carry out this action
        *  (1) owner of the timer
        *  (2) global admin
        */
        if ($next) {

            //gettimer owner
            $timer_owner = $this->timer_model->timerOwner($timer_id);

            //check if team member has permission to edit
            if ($timer_owner == $this->data['vars']['my_id'] || $this->data['vars']['my_group'] == 1) {

                //permission granted
                $next = true;

            } else {

                //permission denied - show error
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_permission_denied'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Updating timer failed. Permission denied]");

                //halt
                $next = false;
            }

        }

        //--update the project timer-------------------------------
        if ($next) {

            /*
            * initiate the mysql update of the timer for this project
            * or simply start/reset the timer
            */

            if ($this->timer_model->updateTimer($timer_id, $timer_new_status)) {
                $success = true;
            } else {
                $success = false;
            }
            $this->data['debug'][] = $this->timer_model->debug_data;

            /*
            * everything seems to have gone ok
            * prepare json output for feeding back to javascript
            */
            if ($success) {

                //what message to show end user 'noty.js popup'
                switch ($timer_new_status) {

                    case 'stopped':
                        $message = $this->data['lang']['lang_timer_stopped'];
                        break;

                    case 'running':
                        $message = $this->data['lang']['lang_timer_started'];
                        break;

                    case 'reset':
                        $message = $this->data['lang']['lang_timer_reset'];
                        break;

                    default:
                        $message = $this->data['lang']['lang_request_has_been_completed'];
                        break;
                }

                /*
                * what is the time now for this task
                * format the returned seconds into H:M:S (e.g. 02:12:44)
                */
                $current_time = $this->timer_model->timerCurrentTime($timer_id);
                $this->data['debug'][] = $this->timer_model->debug_data;
                $current_time = format_timer_time($current_time);


                switch ($timer_new_status) {
                    case 'stopped':
                        /*Make freshbooks time entry completed*/
                        $time = explode(" : ", $current_time);
                        $hours = $time[0];
                        $minutes = $time[1];
                        $seconds = $time[2];
                        $totalHours = $hours;
                        $totalHours += ($minutes / 60);
                        $totalHours += (($seconds / 60) / 60);

                        /*Log this on task*/
                        updateTaskHoursLogged($hours, $taskid);
                        $timearray = array("time_entry" => array("project_id" => $freshbooksprojectid,
                            "task_id" => $freshbookstaskid,
                            "staff_id" => $freshbooksstaffid,
                            "hours" => $totalHours,
                            "notes" => getTaskNotesFrom($taskid),
                            "date" => date("Y-m-d")));
                        createTimeEntry($timearray);
                        $next = true;
                        
                    break;

                    default:
                        /*Not sure what to do..*/
                        break;
                }

                /*
                * what is the new sum of all timers for this project
                */
                $project_timers_duration = $this->timer_model->projectTime($project_id, 'all');
                $this->data['debug'][] = $this->timer_model->debug_data;
                $project_timers_duration = format_timer_time($project_timers_duration);

                //set to json output
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $message,
                    'current_time' => $current_time,
                    'project_total_time' => $project_timers_duration);
                header('HTTP/1.0 200 OK', true, 200);

            } else {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
            }

        }
        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        /*
        * frontend javascript is expecting json array
        */
        $this->__flmView('common/json');
    }

    // -- __refreshTimer- -------------------------------------------------------------------------------------------------------
    /**
     * this will refresh a members timer, but at same time refresh all timers for that project
     */

    function __refreshTimer()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('timer_model');

        //flow control
        $next = true;

        //get ajax post data
        $timer_id = $this->input->post('data_mysql_record_id');
        $project_id = $this->input->post('data_project_id');

        //validate input data
        if (!is_numeric($timer_id) || !is_numeric($project_id)) {

            //log this messsage
            log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Refreshing timer failed. Invalid Post data]");

            //json output
            $this->jsondata = array(
                'result' => 'failed',
                'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                'debug_line' => __line__);

            //send headers
            header('HTTP/1.0 400 Bad Request', true, 400);

            //halt
            $next = false;

        }

        //--get refreshed data----------------------------------------------------
        if ($next) {

            /* REFRESH TASK TIMERS
            * start by refreshing all the task timers
            */
            $this->timer_model->refeshProjectTimers($project_id);
            $this->data['debug'][] = $this->refresh->debug_data;

            /*
            * what is the time now for this task
            * format the returned seconds into H:M:S (e.g. 02:12:44)
            */
            $current_time = $this->timer_model->timerCurrentTime($timer_id);
            $this->data['debug'][] = $this->timer_model->debug_data;
            $current_time = format_timer_time($current_time);

            /*
            * what is the new sum of all timers for this project
            */
            $project_timers_duration = $this->timer_model->projectTime($project_id, 'all');
            $this->data['debug'][] = $this->timer_model->debug_data;
            $project_timers_duration = format_timer_time($project_timers_duration);

            //set to json output
            $this->jsondata = array(
                'result' => 'success',
                'message' => $this->data['lang']['lang_request_has_been_completed'],
                'current_time' => $current_time,
                'project_total_time' => $project_timers_duration);
            header('HTTP/1.0 200 OK', true, 200);

        } else {
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __resetTimer- -------------------------------------------------------------------------------------------------------
    /**
     * reset timer to 0 secods for a single task. Send response back to javascript
     * [custom.ajax.refresh.js]
     */

    function __resetTimer()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('tasks_model');

        //flow control
        $next = true;

        //get ajax post data
        $task_id = $this->input->post('data_mysql_record_id');
        $project_id = $this->input->post('data_project_id');

        //validate input data
        if (!is_numeric($task_id) || !is_numeric($project_id)) {

            //log this messsage
            log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Refreshing timer failed. Invalid Post data]");

            //json output
            $this->jsondata = array(
                'result' => 'failed',
                'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                'debug_line' => __line__);

            //send headers
            header('HTTP/1.0 400 Bad Request', true, 400);

            //halt
            $next = false;

        }

        //--get refreshed data----------------------------------------------------
        if ($next) {

            /*
            * reset the timer duration to zero
            * stop the timer and get the new duration, which should be zero
            */
            if ($this->tasks_model->updateTimer($task_id, 'reset')) {

                //debug
                $this->data['debug'][] = $this->tasks_model->debug_data;

                /* REFRESH TASK TIMERS
                * now refresh the task timers for this project
                */
                $this->refresh->taskTimers($project_id);
                $this->data['debug'][] = $this->refresh->debug_data;

                //get new time for this task, which should now be 0sec
                $new_duration = $this->tasks_model->sumTaskHours($task_id, 'task-id', 'all');
                $this->data['debug'][] = $this->tasks_model->debug_data;
                $refreshed_duration = format_timer_time($new_duration);

                //get new total time for the project
                $new_project_duration = $this->tasks_model->sumTaskHours($project_id, 'project-id', 'all');
                $this->data['debug'][] = $this->tasks_model->debug_data;
                $refreshed_project_duration = format_timer_time($new_project_duration);

                //send to json output
                $this->jsondata = array(
                    'result' => 'success',
                    'reset_time' => $refreshed_duration,
                    'total_project_time' => $refreshed_project_duration,
                    'message' => $this->data['lang']['lang_request_has_been_completed']);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Resetting timer failed. Possible database error]");
                //json output
                $this->jsondata = array(
                    'result' => 'failed',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
            }

        }

        /* REFRESH TASK TIMERS
        * start by refreshing all the task timers
        */
        $this->refresh->taskTimers('all');
        $this->data['debug'][] = $this->refresh->debug_data;

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    function __validationIsGroupAvailable()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use

        //check if group is available
        $result = $this->groups_model->doesGroupExist($this->input->post('groups_name'));
        $this->data['debug'][] = $this->groups_model->debug_data;

        //log debug data
        $this->__ajaxdebugging();

        //load the view
        if ($result) {
            echo 'false';
        } else {
            echo 'true';
        }
    }

	function __countNotifications()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$myid=$this->data['vars']['my_id'];
		//get results and save  for tbs block merging
		$query = $this->db->query("SELECT files.files_created as new_date, logs.*,projects.projects_title,projects.projects_id,project_members.* FROM project_members
									JOIN projects ON projects.projects_id=project_members.project_members_project_id
									JOIN logs ON logs.logs_user_id = project_members.project_members_team_id
									LEFT JOIN files ON files.files_project_id = projects.projects_id
									WHERE logs.logs_action = 'Files'
									AND logs.logs_user_id = $myid
									AND logs.logs_project_id = projects.projects_id
									AND project_members.project_members_team_id = $myid
									AND files.files_created > logs.logs_time
									GROUP BY projects.projects_title");
		$files = $query->result_array();
		
		$query = $this->db->query("SELECT messages.messages_date as new_date, logs.*,projects.projects_title,projects.projects_id,project_members.* FROM project_members 
									JOIN projects ON projects.projects_id=project_members.project_members_project_id 
									JOIN logs ON logs.logs_user_id = project_members.project_members_team_id 
									LEFT JOIN messages ON messages.messages_project_id = projects.projects_id 
									WHERE logs.logs_action = 'Client Chat'
									AND logs.logs_user_id = $myid
									AND logs.logs_project_id = projects.projects_id 
									AND project_members.project_members_team_id = $myid
									AND messages.messages_date > logs.logs_time 
									GROUP BY projects.projects_title");
		$messages = $query->result_array();
		
		$query = $this->db->query("SELECT team_messages.messages_date as new_date, logs.*,projects.projects_title,projects.projects_id,project_members.* FROM project_members 
									JOIN projects ON projects.projects_id=project_members.project_members_project_id 
									JOIN logs ON logs.logs_user_id = project_members.project_members_team_id 
									LEFT JOIN team_messages ON team_messages.messages_project_id = projects.projects_id 
									WHERE logs.logs_action = 'Team Chat'
									AND logs.logs_user_id = $myid
									AND logs.logs_project_id = projects.projects_id 
									AND project_members.project_members_team_id = $myid
									AND team_messages.messages_date > logs.logs_time 
									GROUP BY projects.projects_title");
		$team_messages = $query->result_array();
		
        $this->data['blk1'] = array_merge($files,$messages,$team_messages);
        
        //count results rows - used by pagination class
        $rows_count = count($this->data['blk1']);
		
        echo $rows_count;
        

        //log debug data
        $this->__ajaxdebugging();

        
    }

	function __logAction()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$project=$this->input->post('projectid');
		$user=$this->input->post('userid');
		$action=$this->input->post('action');
		
		switch ($action) {
			case 'Client Chat':
				$type='messages';
				break;
				
			case 'Team Chat':
				$type='teammessages';
				break;
				
			case 'Files':
				$type='files';
				break;
				
				break;
		}
		
		$data = array(
   		'logs_user_id' => $user ,
   		'logs_project_id' => $project ,
   		'logs_action' => $action,
   		'logs_type' => $type
		);
		$query = $this->db->get_where('logs', $data);
		if ($query->num_rows() > 0)
		{
			$this->db->where($data);
			$this->db->set('logs_time', 'NOW()', FALSE);
			$this->db->update('logs'); 
		}
		else {
			$this->db->insert('logs',$data);
		}
		
        
        

        //log debug data
        $this->__ajaxdebugging();

        //load the view
        if ($result) {
            echo 'false';
        } else {
            echo 'true';
        }
    }

    // -- __projectMilestonesList- -------------------------------------------------------------------------------------------------------
    /**
     * create milestones list based on project id
     */

    function __projectMilestonesList()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('milestones_model');

        //get post daataa
        $project_id = $this->input->post('data_mysql_record_id');

        //flow control
        $next = true;

        //validate post
        if (!is_numeric($project_id)) {

            //log this error
            log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Fetch project milestones error: invalid input]");

            //halt
            $next = false;

        }

        //get all milestones for this project
        if ($next) {

            //get list of milestone for the project
            $result = $this->milestones_model->allMilestones('milestones_title', 'ASC', $project_id);
            $this->data['debug'][] = $this->milestones_model->debug_data;

            if (is_array($result)) {

                //create a list from results
                $list = create_pulldown_list($result, 'milestones', 'id');

                //client id needed in hidden form field
                $client_id = $result[0]['milestones_client_id'];

                //json output
                $this->jsondata = array(
                    'result' => 'success',
                    'list' => $list,
                    'client_id' => $client_id);

                //send headers
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error]");

                //halt
                $next = false;
            }
        }

        //an error occurred, send to javascript
        if (!$next) {

            //json output
            $this->jsondata = array(
                'result' => 'failed',
                'message' => $this->data['lang']['lang_requested_item_not_loaded'],
                'debug_line' => __line__);

            //send headers
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __clientsProjectsList- -------------------------------------------------------------------------------------------------------
    /**
     * create project list based on project id
     */

    function __clientsProjectsList()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('projects_model');

        //get post daataa
        $clients_id = $this->input->post('data_mysql_record_id');

        //flow control
        $next = true;

        //validate post
        if (!is_numeric($clients_id)) {

            //log this error
            log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Fetch projects list error: invalid input]");

            //halt
            $next = false;

        }

        //get all milestones for this project
        if ($next) {

            //get list of milestone for the project
            $result = $this->projects_model->allProjects('projects_title', 'ASC', $clients_id, '');
            $this->data['debug'][] = $this->projects_model->debug_data;

            if (is_array($result)) {

                //create a list from results
                $list = create_pulldown_list($result, 'projects', 'id');

                //json output
                $this->jsondata = array('result' => 'success', 'list' => $list);

                //send headers
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error]");

                //halt
                $next = false;
            }
        }

        //an error occurred, send to javascript
        if (!$next) {

            //json output
            $this->jsondata = array(
                'result' => 'failed',
                'message' => $this->data['lang']['lang_requested_item_not_loaded'],
                'debug_line' => __line__);

            //send headers
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __uploadAllowedFileTypes- -------------------------------------------------------------------------------------------------------
    /**
     * Generate an array of allowed file types from settings.php config
     */

    function __uploadAllowedFileTypes()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if allow all file types
        if ($this->config->item('files_tickets_max_size') === 0) {

            return array();
        }

        //explode array from settings.php config file
        $allowed = explode("|", $this->config->item('files_tickets_max_size'));

        //loop through and create new flat array of file types
        for ($i = 0; $i < count($allowed); $i++) {
            $file_extension = strtolower(trim(str_replace("'", '', $allowed[$i])));

            //if $file_extension is valid alphabetic
            if (ctype_alpha($file_extension) || ctype_alnum($file_extension)) {
                $allowed_array[] = $file_extension;
            }
        }

        return $allowed_array;

    }
	
	// -- __deleteMessage- -------------------------------------------------------------------------------------------------------
    /**
     * deleting (changing text to 'DELETED') a chat message 
     */

    function __deleteMessage()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('messages_model');
        $this->load->model('message_replies_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');
        $who = $this->input->post('data_mysql_record_id2');
		
		$_POST['messages_id'] = $id;
		$_POST['by'] = $who;
        /*--------------------------TEAM MEMBER PERMISSIONS---------------------*/
        /*if ($this->data['vars']['my_user_type'] == 'team') {
            $next = $this->__validateTeamPermissions($project_id, 'delete_item_my_project_messages');
        }*/

        //validate data
        if ($next) {
            if (!is_numeric($id)) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Invalid post data]");
            }
        }

        //delete the message
        if ($next) {

            if ($this->messages_model->editDeleteMessage()) {

                //success
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //error deleting message
                $this->jsondata = array(
                    'result' => 'error',
                    'message' =>  $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [Deleting message failed: Database errror]");

            }
            //debug
            $this->data['debug'][] = $this->messages_model->debug_data;

        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteProjectMessage- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a project message 
     */

    function __deleteProjectMessage()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('messages_model');
        $this->load->model('message_replies_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');
        $project_id = $this->input->post('data_mysql_record_id2');

        /*--------------------------TEAM MEMBER PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            $next = $this->__validateTeamPermissions($project_id, 'delete_item_my_project_messages');
        }

        //validate data
        if ($next) {
            if (!is_numeric($id)) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Invalid post data]");
            }
        }

        //delete the message
        if ($next) {

            if ($this->messages_model->deleteMessage($id, 'message-id')) {

                //delete replies also
                $this->message_replies_model->deleteReply($id, 'message-id');
                $this->data['debug'][] = $this->message_replies_model->debug_data;

                //success
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //error deleting message
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [Deleting message failed: Database errror]");

            }
            //debug
            $this->data['debug'][] = $this->messages_model->debug_data;

        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteTicketReply- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a ticket reply message
     */

    function __deleteTicketReply()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('message_replies_model');

        //flow control
        $next = true;

        /*--------------------------TEAM MEMBER GENERAL PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            $next = $this->__validateTeamPermissions('general', 'delete_item_tickets');
        }

        //get data
        $id = $this->input->post('data_mysql_record_id');

        //validate data
        if ($next) {
            if (!is_numeric($id)) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting ticket reply failed - Invalid post data]");
            }
        }

        //delete ticket reply
        if ($next) {
            //delete the ticket and its replies
            $result = $this->tickets_replies_model->deleteReply($id);
            $this->data['debug'][] = $this->tickets_replies_model->debug_data;

            //check results
            if ($result) {

                //create json response
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                $next = false;

            }
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');

    }

    // -- __deleteBug- -------------------------------------------------------------------------------------------------------
    /**
     * delete a project bug
     */

    function __deleteBug()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');

        /*--------------------------TEAM MEMBER GENERAL PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            $next = $this->__validateTeamPermissions('general', 'edit_item_bugs');
        }

        //delete bug
        if ($next) {

            $result = $this->bugs_model->deleteBug($id);
            $this->data['debug'][] = $this->bugs_model->debug_data;

            if ($result) {
                //json output
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);

                //send headers
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //json output
                $this->jsondata = array(
                    'result' => 'failed',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);

                //send headers
                header('HTTP/1.0 400 Bad Request', true, 400);
            }
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteProjectMessageReply- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a project message reply
     */

    function __deleteProjectMessageReply()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('message_replies_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');
        $project_id = $this->input->post('data_mysql_record_id2');

        /*-------------------------TEAM MEMBER PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            $next = $this->__validateTeamPermissions($project_id, 'delete_item_my_project_messages');
        }

        //validate data
        if ($next) {
            if (!is_numeric($id)) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Invalid post data]");
            }
        }

        //delete the message
        if ($next) {

            if ($this->message_replies_model->deleteReply($id, 'reply-id')) {

                //success
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //error deleting message
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [Deleting message failed: Database errror]");

            }
            //debug
            $this->data['debug'][] = $this->message_replies_model->debug_data;

        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteProjectTeamMessage- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a project team message
     */

    function __deleteProjectTeamMessage()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('team_messages_model');
        $this->load->model('team_message_replies_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');
        $project_id = $this->input->post('data_mysql_record_id2');

        /*--------------------------TEAM MEMBER PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            $next = $this->__validateTeamPermissions($project_id, 'delete_item_my_project_team_messages');
        }

        //validate data
        if ($next) {
            if (!is_numeric($id)) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Invalid post data]");
            }
        }

        //delete the message
        if ($next) {

            if ($this->team_messages_model->deleteMessage($id, 'message-id')) {

                //delete replies also
                $this->team_message_replies_model->deleteReply($id, 'message-id');
                $this->data['debug'][] = $this->team_message_replies_model->debug_data;

                //success
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //error deleting message
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [Deleting message failed: Database errror]");

            }
            //debug
            $this->data['debug'][] = $this->team_messages_model->debug_data;

        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteProjectTeamMessageReply- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a project team message reply
     */

    function __deleteProjectTeamMessageReply()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('team_message_replies_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');
        $project_id = $this->input->post('data_mysql_record_id2');

        /*-------------------------TEAM MEMBER PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            $next = $this->__validateTeamPermissions($project_id, 'delete_item_my_project_team_messages');
        }

        //validate data
        if ($next) {
            if (!is_numeric($id)) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Invalid post data]");
            }
        }

        //delete the message
        if ($next) {

            if ($this->team_message_replies_model->deleteReply($id, 'reply-id')) {

                //success
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //error deleting message
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [Deleting message failed: Database errror]");

            }
            //debug
            $this->data['debug'][] = $this->team_message_replies_model->debug_data;

        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteProjectFileMessage- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a project team message
     */
    function __deleteProjectFileMessage()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('file_messages_model');
        $this->load->model('file_messages_replies_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');

        //validate data
        if ($next) {
            if (!is_numeric($id)) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Invalid post data]");
            }
        }

        //delete the message
        if ($next) {

            if ($this->file_messages_model->deleteMessage($id, 'message-id')) {

                //delete replies also
                $this->file_messages_replies_model->deleteReply($id, 'message-id');
                $this->data['debug'][] = $this->file_messages_replies_model->debug_data;

                //success
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //error deleting message
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [Deleting message failed: Database errror]");

            }
            //debug
            $this->data['debug'][] = $this->file_messages_model->debug_data;

        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteProjectFileMessageReply- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a project file message reply
     */
    function __deleteProjectFileMessageReply()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('file_messages_replies_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');

        //validate data
        if ($next) {
            if (!is_numeric($id)) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Invalid post data]");
            }
        }

        //delete the message
        if ($next) {

            if ($this->file_messages_replies_model->deleteReply($id, 'reply-id')) {

                //success
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //error deleting message
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [Deleting message failed: Database errror]");

            }
            //debug
            $this->data['debug'][] = $this->file_messages_replies_model->debug_data;

        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteProjectMyNotes- -------------------------------------------------------------------------------------------------------
    /**
     * delete a project note
     */
    function __deleteProjectMyNotes()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('team_notes_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');

        //validate input data
        if (!is_numeric($id)) {

            //log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting task(s) failed (tasks_id: $id is invalid)]");

            //send error
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);

            //halt
            $next = false;
        }

        //delete the note
        if ($next) {
            if ($this->team_notes_model->deleteNote($id, 'note-id')) {

                //json reposne
                $this->jsondata = array(
                    'result' => 'sucess',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //log error
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting note failed - Database error]");

                //json respone
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //halt
                $next = false;
            }
            $this->data['debug'][] = $this->team_notes_model->debug_data;
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteInvoice- -------------------------------------------------------------------------------------------------------
    /**
     * delete an invoice
     */
    function __deleteInvoice()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('invoice_items_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');
        $project_id = $this->input->post('data_mysql_record_id4');

        /*--------------------------TEAM MEMBER PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            $next = $this->__validateTeamPermissions($project_id, 'delete_item_my_project_invoices');
        }

        //validate input data
        if (!is_numeric($id) || !is_numeric($project_id)) {

            //log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting invoice failed (invalid post data)]");

            //send error
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);

            //halt
            $next = false;
        }

        //delete the invoice
        if ($next) {
            $result = $this->invoices_model->deleteInvoice($id);
            $this->data['debug'][] = $this->invoices_model->debug_data;
            if ($result) {

                //now delete all invoice products for this invoice
                $this->invoice_products_model->deleteItems($id);
                $this->data['debug'][] = $this->invoice_products_model->debug_data;

                //DELETE PAYMENTS
                //TODO - maybe no need to delete payments?

                //events tracker
                $this->__eventsTracker('delete-invoice', array('project_id' => $project_id, 'details' => $id));

                //json reposne
                $this->jsondata = array(
                    'result' => 'sucess',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //log error
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting invoice failed - Database error]");

                //json respone
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //halt
                $next = false;
            }
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteInvoiceItem- -------------------------------------------------------------------------------------------------------
    /**
     * delete a invoice payment
     */
    function __deletePayment()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('payments_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');

        /* -------------------GENERAL PERMISSIONS-------------------*/
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_permission_denied_info'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);
            //halt
            $next = false;
        }

        //validate input data
        if ($next) {
            if (!is_numeric($id)) {

                //log error to file
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting payment failed (id: $id is invalid)]");

                //send error
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //halt
                $next = false;
            }
        }

        //delete the invoice item
        if ($next) {
            if ($this->payments_model->deletePayment($id)) {

                //json reposne
                $this->jsondata = array(
                    'result' => 'sucess',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //log error
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting payment failed - Database error]");

                //json respone
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //halt
                $next = false;
            }
            $this->data['debug'][] = $this->payments_model->debug_data;
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteInvoiceItem- -------------------------------------------------------------------------------------------------------
    /**
     * delete a invoice item
     */
    function __deleteInvoiceItem()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('invoice_items_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');

        /* -------------------GENERAL PERMISSIONS-------------------*/
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_permission_denied_info'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);
            //halt
            $next = false;
        }

        //validate input data
        if ($next) {
            if (!is_numeric($id)) {

                //log error to file
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting invoice item failed (id: $id is invalid)]");

                //send error
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //halt
                $next = false;
            }
        }

        //delete the invoice item
        if ($next) {
            if ($this->invoice_items_model->deleteItem($id)) {

                //json reposne
                $this->jsondata = array(
                    'result' => 'sucess',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //log error
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting invoice item failed - Database error]");

                //json respone
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //halt
                $next = false;
            }
            $this->data['debug'][] = $this->invoice_items_model->debug_data;
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __getInvoiceItemDetails- -------------------------------------------------------------------------------------------------------
    /**
     * get an invoice items details and return them as an array
     */
    function __getInvoiceItemDetails()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('invoice_items_model');

        //get post daataa
        $id = $this->input->post('data_mysql_record_id');

        //flow control
        $next = true;

        //validate post
        if (!is_numeric($id)) {

            //log this error
            log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Fetch invoice-item details failed: invalid id($id)]");

            //halt
            $next = false;

        }

        //get all invoice-item details
        if ($next) {

            //get list of milestone for the project
            $result = $this->invoice_items_model->getItem($id);
            $this->data['debug'][] = $this->invoice_items_model->debug_data;

            if (is_array($result)) {

                //get all the details
                $invoice_items_title = $result['invoice_items_title'];
                $invoice_items_description = $result['invoice_items_description'];
                $invoice_items_amount = $result['invoice_items_amount'];

                //json output
                $this->jsondata = array(
                    'invoice_items_title' => $invoice_items_title,
                    'invoice_items_description' => $invoice_items_description,
                    'invoice_items_amount' => $invoice_items_amount);

                //send headers
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: get invoice-item failed - Database Error]");

                //halt
                $next = false;
            }
        }

        //an error occurred, send to javascript
        if (!$next) {

            //json output
            $this->jsondata = array(
                'result' => 'failed',
                'message' => $this->data['lang']['lang_requested_item_not_loaded'],
                'debug_line' => __line__);

            //send headers
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __backupDatabaseNow- -------------------------------------------------------------------------------------------------------
    /**
     * backup the database
     */
    function __backupDatabaseNow()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        // Load the DB utility class
        $this->load->dbutil();

        $next = true;

        //backup the database
        if ($next) {

            // Backup entire database and assign it to a variable
            $backup = &$this->dbutil->backup();

            //generate a filename from the current date-time
            $filename = date('m-d-Y_H-m-s');
            $filepath = FILES_DATABASE_BACKUP_FOLDER . $filename . '.gz';

            // Load the file helper and write the file to the server
            write_file($filepath, $backup);

            //check if we mnaged to create the file
            if (!file_exists($filepath)) {
                //log this error
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Backing up database failed. Unable to save file ($filepath)]");

                //halt
                $next = false;
            }

        }

        if ($next) {

            //json output
            $this->jsondata = array(
                'result' => 'success',
                'message' => $this->data['lang']['lang_backup_complete_page_will_now_reload'],
                'debug_line' => __line__);

            //send headers
            header('HTTP/1.0 200 OK', true, 200);

        } else {

            //json output
            $this->jsondata = array(
                'result' => 'failed',
                'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                'debug_line' => __line__);

            //send headers
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteBackupFile- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a database backup file 
     */
    function __deleteBackupFile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //get file name
        $file_name = $this->input->post('data_mysql_record_id');

        //delete the file
        if ($next) {

            //file path
            $filepath = FILES_DATABASE_BACKUP_FOLDER . $file_name;

            //delete file
            @unlink($filepath);

            //does file still exists
            if (is_file($filepath)) {

                //log this error
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting backup file failed]");

                //show error
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

            } else {

                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            }

        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteTicket- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a ticket and also delete:
     *                      (1) Related replies
     */
    function __deleteTicket()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('tickets_model');

        //flow control
        $next = true;

        /*-------------------------TEAM MEMBER GENERAL PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            $next = $this->__validateTeamPermissions('general', 'delete_item_tickets');
        }

        //get data
        $id = $this->input->post('data_mysql_record_id');

        //delete the ticket and its replies
        if ($next) {
            $result = $this->tickets_model->deleteTicket($id);
            $this->data['debug'][] = $this->tickets_model->debug_data;

            //check results
            if ($result) {

                //create json response
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);

            } else {

                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                $next = false;

            }
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteProject- -------------------------------------------------------------------------------------------------------
    /**
     * delete a single project and all of its associated data/files
     */
    function __deleteProject()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        /*-------------------GENERAL PERMISSIONS-------------------*/
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_permission_denied_info'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);
            //halt
            $next = false;
        }

        //get data
        $project_id = $this->input->post('data_mysql_record_id');

        //validate input
        if ($next) {

            if (!is_numeric($project_id)) {

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting project failed. Invalid ID]");

                //halt
                $next = false;
            }

        }

        //-----------(1)-BULK/MASS DELETE VARIOUS ASSETS BELONGING TO THIS PROJECT--------------
        if ($next) {
            //run the bulk delete
            $this->__bulkDeleteRecords($project_id);
        }

        //-----------(2)-DELETE THIS PROJECT--------------
        if ($next) {
            $result = $this->projects_model->deleteProject($project_id);
            $this->data['debug'][] = $this->projects_model->debug_data;

            //check results
            if (!$result) {

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting project failed]");

                //halt
                $next = false;
            }
        }

        //final response and notices
        if ($next) {

            /**first delete the project directory's contents
            * IMPORTANT: make sure we have a valid project_id
            */
            if (is_numeric($project_id)) {
                delete_files(FILES_PROJECT_FOLDER . $project_id . '/', true);
            }

            //now delete the empty project directory
            @rmdir(FILES_PROJECT_FOLDER . $project_id);

            //success message
            $this->jsondata = array(
                'result' => 'success',
                'message' => $this->data['lang']['lang_request_has_been_completed'],
                'debug_line' => __line__);
            header('HTTP/1.0 200 OK', true, 200);

        } else {

            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_an_error_has_occurred'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __deleteClient- -------------------------------------------------------------------------------------------------------
    /**
     * delete a client
     */
    function __deleteClient()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        /*-------------------GENERAL PERMISSIONS-------------------*/
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_permission_denied_info'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);
            //halt
            $next = false;
        }

        //get data
        $client_id = $this->input->post('data_mysql_record_id');

        //validate input
        if ($next) {

            if (!is_numeric($client_id)) {

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting client failed. Invalid ID]");

                //halt
                $next = false;
            }

        }

        //create arrays of clients 'project_ids'
        if ($next) {
            //start by getting clients projects
            $results = $this->projects_model->allProjects('projects_id', 'ASC', $client_id, 'all');

            //create a mysql 'IN' list (1,2,3,4) of clients project ID
            $clients_projects = ''; //set
            for ($i = 0; $i < count($results); $i++) {
                $clients_projects .= $results[$i]['projects_id'] . ',';
            }
            //remove trailing comma
            $clients_projects = rtrim($clients_projects, ',');

        }

        //-----------(1)-BULK/MASS DELETE VARIOUS ASSETS BELONGING TO THIS CLIENT'S PROJECTS--------------
        if ($next) {
            //did we have any projects
            if (count($results) > 0) {
                //run the bulk delete
                $this->__bulkDeleteRecords($clients_projects);
            }
        }

        //-----------(2)-DELETE VARIOUS ASSETS BELONGING TO THIS CLIENT--------------
        if ($next) {

            //--DELETE QUOTATIONS--
            $result = $this->quotations_model->deleteClientsQuotations($client_id);
            $this->data['debug'][] = $this->quotations_model->debug_data;

            //--DELETE TICKETS--
            $result = $this->tickets_model->deleteClientsTickets($client_id);
            $this->data['debug'][] = $this->tickets_model->debug_data;

            //--DELETE TICKETS FILES--
            //TODO

            //--DELETE TICKETS REPLIES--
            //TODO

        }

        //-----------(3)-DELETE THIS CLIENT--------------
        if ($next) {
            $result = $this->clients_model->deleteClient($client_id);
            $this->data['debug'][] = $this->clients_model->debug_data;

            //check results
            if (!$result) {

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting client failed]");

                //halt
                $next = false;
            }
        }

        //-----------(4)-DELETE THIS CLIENTS USERS--------------
        if ($next) {
            $result = $this->users_model->deleteClientUsers($client_id);
            $this->data['debug'][] = $this->users_model->debug_data;
        }

        //final response and notices
        if ($next) {

            //success message
            $this->jsondata = array(
                'result' => 'success',
                'message' => $this->data['lang']['lang_request_has_been_completed'],
                'debug_line' => __line__);
            header('HTTP/1.0 200 OK', true, 200);

        } else {

            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_an_error_has_occurred'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __bulkDeleteRecords-------------------------------------------------------------------------------------------------------
    /**
     * bulk delete common assets of a project(s)
     */
    function __bulkDeleteRecords($project_ids = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //--DELETE BUGS--
        $result = $this->bugs_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->bugs_model->debug_data;

        //--DELETE FILE MESSAGES EVENTS--
        $result = $this->file_messages_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->file_messages_model->debug_data;

        //--DELETE FILES --
        $result = $this->files_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->files_model->debug_data;

        //--PHYSICALLY DELETE FILES--
        $files = explode(",", $project_ids);
        foreach ($files as $key) {
            @unlink(FILES_BASE_FOLDER . $key);
        }

        //--DELETE INVOICE--
        $result = $this->invoices_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->invoices_model->debug_data;

        //--DELETE INVOICE PRODUCTS--
        $result = $this->invoice_products_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->invoice_products_model->debug_data;

        //--DELETE MESSAGE REPLIES--
        $result = $this->message_replies_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->message_replies_model->debug_data;

        //--DELETE MESSAGE--
        $result = $this->messages_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->messages_model->debug_data;

        //--DELETE MILESTONES--
        $result = $this->milestones_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->milestones_model->debug_data;

        //--DELETE PAYMENTS--
        $result = $this->payments_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->payments_model->debug_data;

        //--DELETE PROJECT EVENTS--
        $result = $this->project_events_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->project_events_model->debug_data;

        //--DELETE PROJECT MEMBERS--
        $result = $this->project_members_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->project_members_model->debug_data;

        //--DELETE PROJECTS--
        $result = $this->projects_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->projects_model->debug_data;

        //--DELETE TASKS--
        $result = $this->tasks_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //--DELETE TEAM MESSAGE REPLIES--
        $result = $this->team_message_replies_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->team_message_replies_model->debug_data;

        //--DELETE TEAM MESSAGES--
        $result = $this->team_messages_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->team_messages_model->debug_data;

        //--DELETE TIMERS--
        $result = $this->timer_model->bulkDelete($project_ids);
        $this->data['debug'][] = $this->timer_model->debug_data;

    }

    // -- __deleteProjectMember- -------------------------------------------------------------------------------------------------------
    /**
     * remove a member from a project
     */
    function __deleteProjectMember()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        /*-------------------GENERAL PERMISSIONS-------------------*/
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_permission_denied_info'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);
            //halt
            $next = false;
        }

        //get data
        $members_id = $this->input->post('data_mysql_record_id');
        $project_id = $this->input->post('data_mysql_record_id2');

        //validate
        if ($next) {
            if (!is_numeric($members_id)) {

                //json output
                $this->jsondata = array(
                    'result' => 'failed',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting project member failed. Invalid Post data]");

                //send headers
                header('HTTP/1.0 400 Bad Request', true, 400);

                //halt
                $next = false;
            }
        }

        //is member a project leader
        if ($next) {
            $result = $this->project_members_model->isProjectLeader($project_id, $members_id);
            $this->data['debug'][] = $this->project_members_model->debug_data;

            if ($result) {
                //json output
                $this->jsondata = array(
                    'result' => 'failed',
                    'message' => $this->data['lang']['lang_you_cannot_delete_project_leader'],
                    'debug_line' => __line__);

                //send headers
                header('HTTP/1.0 400 Bad Request', true, 400);

                //halt
                $next = false;
            }
        }

        //delete memeber
        if ($next) {
            $result = $this->project_members_model->deleteProjectMember($project_id, $members_id);
            $this->data['debug'][] = $this->project_members_model->debug_data;

            if ($result) {
                //json output
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);

                //send headers
                header('HTTP/1.0 200 OK', true, 200);
            } else {
                //json output
                $this->jsondata = array(
                    'result' => 'failed',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting project member failed. Mysql error]");

                //send headers
                header('HTTP/1.0 400 Bad Request', true, 400);
            }
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- validateTeamPermissions-------------------------------------------------------------------------------------------------------
    /**
     * checks if a team member has access to carry out an action like deleting a file
     * [EXAMPLE USAGE]
     * $next = validateTeamPermissions($project_id, 'delete_item_my_project_files');
     *
     * @access	private
     * @param	mixed $project_id numeric project id | 'general' for none project items 
     * @param	string $action example: delete_item_my_project_files 
     * @return	bool
     */
    function __validateTeamPermissions($project_id = 0, $action = 'none_specified')
    {

        //error control
        $next = true;

        //profiling
        $this->data['controller_profiling'][] = __function__;

        /*------------------------TEAM MEMBER GENERAL PERMISSION---------------------*/
        if ($project_id == 'general') {
            if ($this->data['vars']['my_user_type'] == 'team') {
                if ($this->data['vars']['my_group'] != 1) {
                    if ($this->data['permission'][$action] != 1) {
                        //create json response
                        $this->jsondata = array(
                            'result' => 'error',
                            'message' => $this->data['lang']['lang_permission_denied_info'],
                            'debug_line' => __line__);
                        header('HTTP/1.0 400 Bad Request', true, 400);
                        //halt
                        $next = false;
                    }
                }

                //return results
                if ($next) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        /*--------------------------TEAM MEMBER PROJECT ACCESS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            if ($this->data['vars']['my_group'] != 1) {
                if (!in_array($project_id, $this->data['my_projects_array'])) {
                    //create json response
                    $this->jsondata = array(
                        'result' => 'error',
                        'message' => $this->data['lang']['lang_permission_denied_info'],
                        'debug_line' => __line__);
                    header('HTTP/1.0 400 Bad Request', true, 400);
                    //halt
                    $next = false;
                }
            }
        }

        /* --------------------------TEAM MEMBER PPROJECT PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            if ($this->data['vars']['my_group'] != 1) {
                //load project basics - this also sets my 'this project' permissions
                $this->__commonAll_ProjectBasics($project_id);
                //
                if ($this->data['project_permissions'][$action] != 1) {
                    //create json response
                    $this->jsondata = array(
                        'result' => 'error',
                        'message' => $this->data['lang']['lang_permission_denied_info'],
                        'debug_line' => __line__);
                    header('HTTP/1.0 400 Bad Request', true, 400);
                    //halt
                    $next = false;
                }
            }
        }

        //return results
        if ($next) {
            return true;
        } else {
            return false;
        }

    }

    // -- __deleteTeamMember- -------------------------------------------------------------------------------------------------------
    /**
     * delete a team user
     */

    function __deleteTeamMember()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        /*-------------------GENERAL PERMISSIONS-------------------*/
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_permission_denied_info'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);
            //halt
            $next = false;
        }

        //get the post data
        $id = $this->input->post('data_mysql_record_id');

        //prevent admin from deleting their own account
        if ($next) {
            if ($this->data['vars']['my_id'] == $id) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                $next = false;
            }
        }

        //get members details
        if ($next) {
            $member = $this->teamprofile_model->teamMemberDetails($id);
            $this->data['debug'][] = $this->teamprofile_model->debug_data;

            //check if we got any details
            if (!$member) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                $next = false;
            }
        }

        //delete the member
        if ($next) {
            $result = $this->teamprofile_model->deleteTeamMember($id);
            $this->data['debug'][] = $this->teamprofile_model->debug_data;

            //check if success
            if ($result) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);
                $next = false;
            } else {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                $next = false;
            }
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');

    }

    // -- __deleteTicketsDepartment- -------------------------------------------------------------------------------------------------------
    /**
     * delete a a support tickets department
     */

    function __deleteTicketsDepartment()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        /* -------------------GENERAL PERMISSIONS-------------------*/
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_permission_denied_info'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);
            //halt
            $next = false;
        }

        //get data
        $department_id = $this->input->post('data_mysql_record_id');

        //check that the group is empty
        if ($next) {
            if (!is_numeric($department_id)) {
                //output something, just for debugging
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }
        }

        //delete department
        if ($next) {
            if (!$this->tickets_departments_model->deleteDepartment($department_id)) {

                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //halt
                $next = false;
            }
            $this->data['debug'][] = $this->tickets_departments_model->debug_data;
        }

        //delete associated tickets
        if ($next) {

            $this->tickets_model->deleteTickets($department_id);
            $this->data['debug'][] = $this->tickets_model->debug_data;

            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_request_has_been_completed'],
                'debug_line' => __line__);
            header('HTTP/1.0 200 OK', true, 200);
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json
        $this->__flmView('common/json');
    }

    // -- __toggleHot- -------------------------------------------------------------------------------------------------------
    /**
     * toggle a client/lead's "hot" column
     */

    function __toggleHot()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;
		
		$id = $this->input->post('id');
		$clients_id = $this->input->post('clients_id');
		$type = $this->input->post('type');
		$newValue = '';
		
		switch ($type)
		{
			case 'lead':
				$this->load->model('sales_model');
				$newValue = $this->sales_model->toggleRow($id, 'leads_hot');
			break;
			case 'client':
				$this->load->model('clients_model');
				$newValue = $this->clients_model->toggleRow($clients_id, 'clients_hot');
			break;
			default:
				$next = false;
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => "Invalid sale type",
                    'debug_line' => __line__);
		}

        if ($next) {
			$this->jsondata = array(
				'result' => 'success',
				'message' => ucfirst($type).' is now marked as'.($newValue? ' ': ' not ').'HOT',
				'newValue' => $newValue,
				'debug_line' => __line__);
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json
        $this->__flmView('common/json');
    }

    // -- __toggleLost- -------------------------------------------------------------------------------------------------------
    /**
     * toggle a client/lead's "lost" column
     */

    function __toggleLost()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;
		
		$id = $this->input->post('id');
		$clients_id = $this->input->post('clients_id');
		$type = $this->input->post('type');
		$newValue = '';
		
		switch ($type)
		{
			case 'lead':
				$this->load->model('sales_model');
				$newValue = $this->sales_model->toggleRow($id, 'leads_lost');
			break;
			case 'client':
				$this->load->model('clients_model');
				$newValue = $this->clients_model->toggleRow($clients_id, 'clients_lost');
			break;
			default:
				$next = false;
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => "Invalid sale type",
                    'debug_line' => __line__);
		}

        if ($next) {
			$this->jsondata = array(
				'result' => 'success',
				'message' => ucfirst($type).' is now marked as'.($newValue? ' ': ' not ').'LOST',
				'newValue' => $newValue,
				'debug_line' => __line__);
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json
        $this->__flmView('common/json');
    }
	

    // -- fmlView-------------------------------------------------------------------------------------------------------
    /**
     * loads json outputting view
     */
    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //sent to TBS engine
        $this->load->view($view, array('data' => $this->jsondata));
    }

    // -- DEBUGGING --------------------------------------------------------------------------------------------------------------
    /**
     * - ajax runs in the background, so we want to do as much logging as possibe for debugging
     * 
     */
    function __ajaxdebugging()
    {

        //url segmets array
        $this->data['url_segments'] = $this->uri->segment_array();

        //format debug data for log file
        ob_start();
        print_r($this->data);
        print_r($this->jsondata);
        $all_data = ob_get_contents();
        ob_end_clean();

        //write to logi file
        if ($this->config->item('debug_mode') == 2 || $this->config->item('debug_mode') == 1) {
            log_message('debug', "AJAX-LOG:: BIG DATA $all_data");
        }
    }

    // -- __eventsTracker- -------------------------------------------------------------------------------------------------------
    /**
     * records new project events (timeline)
     *
     * @access	private
     * @param	string $type identify the loop to run in this function 
     * @param   array $events_data an optional array that can be used to directly pass data       
     * @return void
     */

    function __eventsTracker($type = '', $events_data = array())
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //--------------record a new event-----------------------
        if ($type == 'delete_milestone') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $events_data['project_id'];
            $events['project_events_type'] = 'deleted';
            $events['project_events_details'] = $events_data['milestone_title'];
            $events['project_events_action'] = 'lang_tl_deleted_milestone';
            $events['project_events_target_id'] = ($events_data['target_id'] == '') ? 0 : $events_data['target_id'];
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'team';

            //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;

        }

        //--------------record a new event-----------------------
        if ($type == 'delete-invoice') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $events_data['project_id'];
            $events['project_events_type'] = 'deleted';
            $events['project_events_details'] = $events_data['details'];
            $events['project_events_action'] = 'lang_tl_deleted_invoice';
            $events['project_events_target_id'] = 0;
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'team';

            //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;

        }
    }

}

/* End of file ajax.php */
/* Location: ./application/controllers/admin/ajax.php */
