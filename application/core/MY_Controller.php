<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * NEXTLOOP
 *
 * This is the from loading controller. All other controlller extend this one
 * A huge amount of 'common' work and heavy lifting is done in here
 *          
 *
 */

class MY_Controller extends CI_Controller
{
   //__________STANDARD VARS__________
    public $next = true; //flow control
    public $data = array(); //mega array passed to TBS
    public $results_limit;
    public $client_details = array();
    public $project_details = array();
    public $clients_user_details = array();
    public $jsondata = array(); //mega array passed to json
    public $project_id; //current project
    public $client; //array with logged in clients profile data
    public $client_id; //logged in client
    public $user_id; //logged in client user
    public $member_id; //logged in team members id's
    public $project_leaders_id;
    public $project_leaders_details;

    //__________MAILING LISTS__________
    public $mailinglist_admins; //array of admins email addresses (used to send system emails etc0

    // -- __construct- -------------------------------------------------------------------------------------------------------
    /**
     * do some pre run tasks
     *
     * @usedby  All
     * 
     * @param	void
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        /*
        |----------------------------------------------------------------------------
        | LOAD PROFILER (IF IN DEBUG MODE) - TO SEE MEMORY & EXECUTION TIME USAGE ETC
        |----------------------------------------------------------------------------
        |
        */
        if ($this->config->item('debug_mode') == 1) {
            $this->output->enable_profiler(true);
        }

        /*
        |----------------------------------------------------------------------------
        | LOAD MODELS
        |----------------------------------------------------------------------------
        |
        */
        $this->load->model('version_model');
        $this->load->model('bugs_model');
        $this->load->model('clients_model');
        $this->load->model('clients_model');
        $this->load->model('clientsoptionalfields_model');
		$this->load->model('feed_model');
        $this->load->model('file_messages_model');
        $this->load->model('file_messages_replies_model');
        $this->load->model('files_model');
        $this->load->model('invoice_items_model');
        $this->load->model('invoice_products_model');
        $this->load->model('invoices_model');
        $this->load->model('message_replies_model');
        $this->load->model('messages_model');
        $this->load->model('milestones_model');
        $this->load->model('payments_model');
        $this->load->model('permissions_model');
        $this->load->model('project_events_model');
        $this->load->model('project_members_model');
        $this->load->model('projects_model');
        $this->load->model('projectsoptionalfields_model');
        $this->load->model('quotationforms_model');
        $this->load->model('quotations_model');
        $this->load->model('settings_company_model');
        $this->load->model('settings_emailtemplates_model');
        $this->load->model('settings_general_model');
        $this->load->model('settings_invoices_model');
        $this->load->model('settings_paypal_model');
        $this->load->model('tasks_model');
        $this->load->model('team_message_replies_model');
        $this->load->model('team_messages_model');
        $this->load->model('teamprofile_model');
        $this->load->model('tickets_departments_model');
        $this->load->model('tickets_mailer_model');
        $this->load->model('tickets_model');
        $this->load->model('tickets_replies_model');
        $this->load->model('timer_model');
        $this->load->model('users_model');
        $this->load->model('users_model');
        $this->load->model('settings_payment_methods_model');
        $this->load->model('system_events_model');
        $this->load->model('updating_model');
        $this->load->model('settings_cash_model');
        $this->load->model('settings_bank_model');
        $this->load->model('mynotes_model');
        $this->load->model('email_queue_model');
        $this->load->model('sms_queue_model');
        $this->load->model('reports_model');
        $this->load->model('timedoctor_model');
        $this->load->model('tasks_viewers_model');
        $this->load->model('billing_categories_model');

        /*
        |----------------------------------------------------------------------------
        | SETS ALL COMMON DYNAMIC (DATABASE) DATA
        |----------------------------------------------------------------------------
        |
        | sets database stored data that is used commonly in the system
        |
        */
        $this->__preRun_Dynamic_Data();

        /*
        |----------------------------------------------------------------------------
        | SET LANGUAGE
        |----------------------------------------------------------------------------
        |
        | - verify and set language file (set to data array)
        | - create language pulldown lists
        | - create simple array of all available language (found in the language folder)
        |
        */
        $this->__preRun_Language();

        /*
        |----------------------------------------------------------------------------
        | SETS ALL COMMON STATIC DATA
        |----------------------------------------------------------------------------
        |
        | sets static data that is used commonly in the system
        |
        */
        $this->__preRun_Static_Data();

        /*
        |----------------------------------------------------------------------------
        | SET COMMON ARRAYS
        |----------------------------------------------------------------------------
        |
        | set various/common arrays that are used by various controllers
        |
        */
        $this->__preRun_Arrays();

        /*
        |----------------------------------------------------------------------------
        | REFRESH VARIOUS DATABASE RECORDS
        |----------------------------------------------------------------------------
        |
        | - refresh essential database tables on every page loaded
        | - these are tables that must be kept extra fresh on each page load
        |
        */
        $this->__preRun_RefreshDatabase();

        /*
        |----------------------------------------------------------------------------
        | SET SITE THEME
        |----------------------------------------------------------------------------
        |
        | - verify and set site theme (set to data array)
        | - create themes pulldown lists
        | - create simple array of all available themes (found in the language folder)
        |
        */
        $this->__preRun_Theme();

        /*
        |----------------------------------------------------------------------------
        | LOAD / INITIATE ANY CUSTOM LIBRARIES
        |----------------------------------------------------------------------------
        |
        | - load any libraries that are commonly used but need to be loaded in
        |   some special way
        |
        */
        $this->__preRun_Libraries();

        /*
        |----------------------------------------------------------------------------
        | BEFORE ANYTHING ELSE - CHECK SYSTEM INTEGRITY (SANITY CHECK)
        |----------------------------------------------------------------------------
        |
        | this checks that all parts of the system
        | are setup as expected
        | DEVELOPER MODE - Ignore this when in developer mode
        |
        */
        if ($this->config->item('dev_mode') != 1) {
            $this->__preRun_System_Sanity_Checks();
        }

        /*
        |----------------------------------------------------------------------------
        | EXECUTE ANY MYSQL UPDATE FILES
        |----------------------------------------------------------------------------
        |
        | this checks if any mysql files exist in the /updates folder and executes
        | If error is ecountered it halts and alerts ADMIN ONLY
        | DEVELOPER MODE - Ignore this when in developer mode
        |
        */
        if ($this->config->item('dev_mode') != 1) {
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                $this->__preRun_MysqlUpdates();
            }
        }

        /*
        |----------------------------------------------------------------------------
        | SET TEAM MEMBERS CORE PERMISSION LEVELS BASED ON THEIR GROUP
        |----------------------------------------------------------------------------
        |
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {

            //get my permission levels
            set_my_permissions($this->groups_model->groupDetails($this->session->userdata('team_profile_groups_id')));

            //set my D.A.V.E (delete/add/view/edit) permissions
            $this->__commonAdmin_SetMyPermissions();
        }

        /*
        |----------------------------------------------------------------------------
        | REGISTER TEAM MEMBER LAST ACTIVE IN DATABASE
        |----------------------------------------------------------------------------
        |
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->__commonAdmin_RegisterLastActive();
        }

        /*
        |----------------------------------------------------------------------------
        | REGISTER CLIENT USER LAST ACTIVE IN DATABASE
        |----------------------------------------------------------------------------
        |
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $this->__commonClient_RegisterLastActive();
        }

        /*
        |----------------------------------------------------------------------------
        | DISPLAY ANY SESSION FLASH NOTICES
        |----------------------------------------------------------------------------
        | This can be messages that were set before a page redirected. They are set
        | as follows, on the previous page (before redirect or post etc)
        | $this->session->set_flashdata(notice-success', 'Request has been completed');
        | $this->session->set_flashdata(notice-error', 'Request could not be completed');
        |
        */
        if ($this->session->flashdata('notice-success') != '') {
            $this->notices('success', $this->session->flashdata('notice-success'), 'noty');
        }
        if ($this->session->flashdata('notice-error') != '') {
            $this->notices('error', $this->session->flashdata('notice-error'), 'noty');
        }
		
		//added by Tomasz
		$this->data['vars']['menu'] = 'dashboard';
		//end by Tomasz
    }

    //=================================================================ADMIN METHODS============================================================\\

    // -- __commonAdmin_LoggedInCheck- -------------------------------------------------------------------------------------------------------
    /**
     * checks if user is logged in, else redirects
     *
     * @usedby  Admin
     * 
     * @param	void
     * @return void
     */
    public function __commonAdmin_LoggedInCheck()
    {

        //is user logged in..else redirect to login page
        logged_in_check('team_member');

    }

    // -- __commonAdmin_RegisterLastActive- -------------------------------------------------------------------------------------------------------
    /**
     * records a team members last activity as NOW() whenver this controler is loaded
     *
     * @usedby  Admin
     * @usedby  Team
     * 
     * @param	void
     * @return void
     */
    public function __commonAdmin_RegisterLastActive()
    {

        //update team member as last active now()
        $this->teamprofile_model->registerLastActive($this->session->userdata('team_profile_id'));
        $this->data['debug'][] = $this->teamprofile_model->debug_data;
    }

    // -- __commonAdmin_PermissionsMenus- -------------------------------------------------------------------------------------------------------
    /**
     *  Set my human D.A.V.E (delete/add/view/edit) permissions for CATEGORY
     *
     *  [CATEGORIES] are set in the common array
     *                      - $this->data['common_arrays']['permission_categories']
     *
     *  Now set permission for each category, based on my permission level fro each category
     *                      - $this->data['permission'][delete_item_my_project_files] = 1
     *                      - $this->data['permission'][add_item_my_project_files] = 1
     *                      - $this->data['permission'][view_item_my_project_files] = 1
     *                      - $this->data['permission'][edit_item_my_project_files] = 1
     *
     *  [PERMISSION LEVELS] These are set when I log in and are taken from my [GROUPS] permission
     *                      - They are all set in $this->data['my_permissions']
     *
     * @usedby  Admin
     * 
     * @param	void
     * @return void
     */
    public function __commonAdmin_SetMyPermissions()
    {

        //loop through each category and set my D.A.V.E (delete/add/view/edit) permissions
        foreach ($this->data['common_arrays']['permission_categories'] as $value) {

            //the individual D.A.V.E permissions for each category (e.g.delete_item_clients)
            $delete = 'delete_item_' . $value;
            $add = 'add_item_' . $value;
            $view = 'view_item_' . $value;
            $edit = 'edit_item_' . $value;

            //what is my numeric permission level for this category
            $my_permission = $this->data['my_permissions'][$value];

            //set my D.A.V.E into a new array $this->data['permission']
            $this->data['permission'][$delete] = ($my_permission >= 4) ? 1 : 0;
            $this->data['permission'][$add] = ($my_permission >= 2) ? 1 : 0;
            $this->data['permission'][$view] = ($my_permission >= 1) ? 1 : 0;
            $this->data['permission'][$edit] = ($my_permission >= 3) ? 1 : 0;
        }
    }

    // -- __commonPermissionVisibility- -------------------------------------------------------------------------------------------------------
    /**
     *  Set visibility of common items such as menus etc, based on team members permissions
     *
     *
     * @usedby  Admin
     * 
     * @param	void
     * @return void
     */
    public function __commonPermissionVisibility()
    {

    }

    // -- __commonAll_ProjectBasics- -------------------------------------------------------------------------------------------------------
    /**
     * 1) checks if a project exists. 
     * 2) loads the main project detials into $data['rows4']
     * 3) sets some vars that will be used universally in this object [$this->client_id]
     * 4) If project does not exist, it redirects to error page
     *
     * @usedby  Admin & Client
     * 
     * @param numeric $project_id]
     * @return void
     */
    public function __commonAll_ProjectBasics($project_id)
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //register this project counts array (used later - below)
        $this->data['reg_fields'][] = 'this_project_counts';

        //check if project exists
        if ($next) {
            if (!$this->project_details = $this->projects_model->projectDetails($project_id)) {
                //redirect to error handler
                if (is_numeric($this->session->userdata('team_profile_id'))) {
                    redirect('admin/error/not-found');
                }
                if (is_numeric($this->session->userdata('client_users_id'))) {
                    redirect('client/error/not-found');
                }
            }

        }

        //load of of the projects data
        if ($next) {
        	
			//return link
			$this->data['vars']['return_link'] = $this->data['vars']['site_url_admin'].'/project/'.$project_id.'/view';

            //main project data
            $this->data['rows4'] = $this->project_details;
            $this->data['reg_fields'][] = 'project_details';
            $this->data['fields']['project_details'] = $this->project_details; //for tbs merging
            $this->data['project_details'] = $this->project_details; //for general use

            //set client_id
            $this->client_id = $this->project_details['projects_clients_id'];
            $this->data['vars']['client_id'] = $this->client_id;

            //do not disturb the session client_id for clients
            if (is_numeric($this->session->userdata('client_users_id'))) {
                $this->client_id = $this->session->userdata('client_users_clients_id');
            }

            //get clients primary user
            $this->clients_user_details = $this->users_model->clientPrimaryUser($this->client_id);
            $this->data['debug'][] = $this->users_model->debug_data;

            //get team project leader details
            $this->project_leaders_details = $this->project_members_model->getProjectLead($project_id);
            $this->data['debug'][] = $this->project_members_model->debug_data;

            //set the project leader id into a var
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                $this->data['vars']['project_leaders_id'] = $this->project_leaders_details['project_members_team_id'];
            }

            //get the project percentage complete figure
            $project_percentage = $this->__commonAdmin_ProjectPecentageComplete($this->project_id);
            $this->data['vars']['project_percentage_completed'] = $project_percentage;

            //refresh all timers for this project and make the time up2date
            $this->timer_model->refeshProjectTimers($this->project_id);
            $this->data['debug'][] = $this->timer_model->debug_data;

            //get ALL time spent on project
            $this->data['vars']['project_timer_hours_spent'] = $this->timer_model->projectTime($this->project_id, 'all');
            $this->data['debug'][] = $this->timer_model->debug_data;

            /*MY TIMER SPENT ON PROJECT
            * --only do this if a team members is logged in---
            */
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                $this->data['vars']['my_project_timer_hours_spent'] = $this->timer_model->projectTime($this->project_id, $this->data['vars']['my_id']);
                $this->data['debug'][] = $this->timer_model->debug_data;
            }

            /*MY TIMER STATUS & BUTTON VISIBILITY
            * --only do this if a team members is logged in---
            */
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                $this->data['vars']['my_project_timer_status'] = $this->timer_model->timerStatus($this->project_id, $this->data['vars']['my_id']);
                $this->data['debug'][] = $this->timer_model->debug_data;
                if ($this->data['vars']['my_project_timer_status'] == 'running') {
                    //show start button
                    $this->data['vars']['css_start_timer_btn'] = 'invisible';
                    $this->data['vars']['css_stop_timer_btn'] = 'visible';
                } else {
                    //shw stop button
                    $this->data['vars']['css_stop_timer_btn'] = 'invisible';
                    $this->data['vars']['css_start_timer_btn'] = 'visible';
                }
            }

            /*CREATE A TIMER FOR ME IF I DONT HAVE ONE
            * --only do this if a team members is logged in---
            */
           /* if (is_numeric($this->session->userdata('team_profile_id'))) {
                if ($this->data['vars']['my_project_timer_status'] == 'none') {
                    $this->timer_model->addNewTimer($this->project_id, $this->data['vars']['my_id']);
                    $this->data['debug'][] = $this->timer_model->debug_data;
                }
            }*/

            /* MY TIMER ID FOR THIS PROJECT
            * --only do this if a team members is logged in---
            */
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                $result = $this->timer_model->timerDetails($this->project_id, $this->data['vars']['my_id']);
                $this->data['vars']['my_project_timer_id'] = $result['timer_id'];
                $this->data['debug'][] = $this->timer_model->debug_data;
            }

            /* MY TASKS COUNT FOR THIS PROJECT
            * [my_project_tasks_count.pending]
            * [my_project_tasks_count.completed]
            * [my_project_tasks_count.behing_schedule]
            * [my_project_tasks_count.all_open]
            * [my_project_tasks_count.all_tasks]
            * --only do this if a team members is logged in---
            */
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                $this->data['reg_fields'][] = 'my_project_tasks_count';
                $this->data['fields']['my_project_tasks_count'] = $this->tasks_model->allMyTasksCounts($this->session->userdata('team_profile_id'), $this->project_id);
                $this->data['debug'][] = $this->tasks_model->debug_data;
            }

            /* PROJECT MILESTONE COUNTS
            * [this_project_counts.milestone_all]
            * [this_project_counts.milestone_all_open]
            * [this_project_counts.milestone_inprogress]
            * [this_project_counts.milestone_behind]
            * [this_project_counts.milestone_completed]
            */
            $this->data['fields']['this_project_counts']['milestone_all'] = $this->milestones_model->countMilestones($this->project_id, 'all');
            $this->data['debug'][] = $this->milestones_model->debug_data;
            $this->data['fields']['this_project_counts']['milestone_all_open'] = $this->milestones_model->countMilestones($this->project_id, 'uncompleted');
            $this->data['debug'][] = $this->milestones_model->debug_data;
            $this->data['fields']['this_project_counts']['milestone_completed'] = $this->milestones_model->countMilestones($this->project_id, 'completed');
            $this->data['debug'][] = $this->milestones_model->debug_data;
            $this->data['fields']['this_project_counts']['milestone_behind'] = $this->milestones_model->countMilestones($this->project_id, 'behind schedule');
            $this->data['debug'][] = $this->milestones_model->debug_data;
            $this->data['fields']['this_project_counts']['milestone_inprogress'] = $this->milestones_model->countMilestones($this->project_id, 'in progress');
            $this->data['debug'][] = $this->milestones_model->debug_data;

            /* PROJECT INVOICES COUNTS
            * [this_project_counts.invoices_all]           
            * [this_project_counts.invoices_paid]
            * [this_project_counts.invoices_due]
            * [this_project_counts.invoices_overdue]
            * [this_project_counts.invoices_partpaid]
            * [this_project_counts.invoices_all_unpaid]
            */
            $this->data['fields']['this_project_counts']['invoices_all'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'all');
            $this->data['debug'][] = $this->milestones_model->debug_data;
            $this->data['fields']['this_project_counts']['invoices_paid'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'paid');
            $this->data['debug'][] = $this->milestones_model->debug_data;
            $this->data['fields']['this_project_counts']['invoices_due'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'due');
            $this->data['debug'][] = $this->milestones_model->debug_data;
            $this->data['fields']['this_project_counts']['invoices_overdue'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'overdue');
            $this->data['debug'][] = $this->milestones_model->debug_data;
            $this->data['fields']['this_project_counts']['invoices_partpaid'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'partpaid');
            $this->data['debug'][] = $this->milestones_model->debug_data;
            $this->data['fields']['this_project_counts']['invoices_all_unpaid'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'all-unpaid');
            $this->data['debug'][] = $this->milestones_model->debug_data;

            /* PROJECT MAILING LIST
            * creates a list of all users to receive email notifications
            * [team members] and [client users] for this project
            * only users who have anabled email notifications will receive them
            */
            $this->data['vars']['project_members_team'] = $this->project_members_model->listProjectmembers($this->project_id);
            $this->data['debug'][] = $this->project_members_model->debug_data;
            $this->data['vars']['project_members_client'] = $this->users_model->clientUsers($this->project_details['clients_id']);
            $this->data['debug'][] = $this->users_model->debug_data;
            //add team members to mailing list for this project
            for ($i = 0; $i < count($this->data['vars']['project_members_team']); $i++) {
                if ($this->data['vars']['project_members_team'][$i]['team_profile_notifications_system'] == 'yes') {
                    $name      = $this->data['vars']['project_members_team'][$i]['team_profile_full_name'];
                    $email     = $this->data['vars']['project_members_team'][$i]['team_profile_email'];
                    $telephone = $this->data['vars']['project_members_team'][$i]['team_profile_telephone'];
                    $user_type = 'team';

                    $this->data['vars']['project_mailing_list'][] = array(
                        'name' => $name,
                        'email' => $email,
                        'user_type' => $user_type);
                    $this->data['vars']['project_team_mailing_list'][] = array(
                        'name' => $name,
                        'email' => $email,
                        'telephone' => $telephone,
                        'user_type' => $user_type);
                }
            }
            //add client users to mailing list for this project
            for ($i = 0; $i < count($this->data['vars']['project_members_client']); $i++) {
                if ($this->data['vars']['project_members_client'][$i]['client_notifications_system'] == 'yes') {
                    $name = $this->data['vars']['project_members_client'][$i]['client_users_full_name'];
                    $email = $this->data['vars']['project_members_client'][$i]['client_users_email'];
                    $user_type = 'client';
                    $this->data['vars']['project_mailing_list'][] = array(
                        'name' => $name,
                        'email' => $email,
                        'user_type' => $user_type);
                }
            }
        }

        /** PERMISSIONS - VISIBILITY **/
        if ($next) {
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                $this->__common_ProjectPermissions($this->project_id, $this->session->userdata('team_profile_id'));
            }
        }
    }

    // -- __common_ProjectPermissions- -------------------------------------------------------------------------------------------------------
    /**
     * Create my final permission for [THIS PROJECT[
     * Store the permissions in $this->data['perm'] array.
     * My final permissions will depened of if I am a project leader or not for [THIS PROJECT]
     * We will use $this->data['perm'] for hiding certain menus, buttons, and general permission testing 
     *
     * @usedby  Admin
     * 
     * @param numeric $project_id]
     * @return void
     */
    public function __common_ProjectPermissions($project_id, $members_id)
    {

        $my_group = $this->data['vars']['my_group'];
        $my_projects_array = $this->data['my_projects_array'];
        $my_active_projects_array = $this->data['my_active_projects_array'];
        $my_leaders_projects_array = $this->data['my_leaders_projects_array'];
        $permission_categories = $this->data['common_arrays']['permission_categories'];

        /**-------------------------------------------------------------
        * SPECIAL CATEGORIES
        *--------------------------------------------------------------
        * these categories should not be altered from their database 
        * permission settings
        *
        *--------------------------------------------------------------*/
        $special_categories = array(
            'clients',
            'bugs',
            'tickets',
            'quotations');

        //loop through all categories and set my [project permissions] for this project
        foreach ($permission_categories as $key) {

            $view = "view_item_$key";
            $edit = "edit_item_$key";
            $add = "add_item_$key";
            $delete = "delete_item_$key";

            //exclude special categories
            if (!in_array($key, $special_categories)) {

                /**-------------------------------------------------------------
                * I AM A PROJECT LEADER
                *--------------------------------------------------------------
                * Grant new FULL permissions on projects that I am leader of
                * overide whatever my general permissions for each category
                * otherwise 
                *--------------------------------------------------------------*/
                if (in_array($project_id, $my_leaders_projects_array)) {
                    //overide my permission & give full acess
                    $this->data['project_permissions'][$delete] = 1;
                    $this->data['project_permissions'][$add] = 1;
                    $this->data['project_permissions'][$view] = 1;
                    $this->data['project_permissions'][$edit] = 1;

                } else {
                    /**-------------------------------------------------------------
                    * I AM NOT A PROJECT LEADER
                    *--------------------------------------------------------------
                    *leave permission as set in database for all the categories
                    * 
                    *--------------------------------------------------------------*/
                    $this->data['project_permissions'][$delete] = $this->data['permission'][$delete];
                    $this->data['project_permissions'][$add] = $this->data['permission'][$add];
                    $this->data['project_permissions'][$view] = $this->data['permission'][$view];
                    $this->data['project_permissions'][$edit] = $this->data['permission'][$edit];

                }
            }
        }

        /**-------------------------------------------------------------
        * I AM A SUPER USER -OR- A REGULAR USER
        *--------------------------------------------------------------
        * Grant the [admin] and [project leader] SUPER USER STATUS
        * this will allow easier identification of these two users
        * in TBS etc 
        *--------------------------------------------------------------*/
        if (in_array($project_id, $my_leaders_projects_array) || $my_group == 1) {
            $this->data['project_permissions']['super_user'] = 1;
        } else {
            $this->data['project_permissions']['regular_user'] = 1;
        }

    }

    // -- __commonAdmin_ProjectPecentageComplete- -------------------------------------------------------------------------------------------------------
    /**
     * calculate the percentage progress of a particular project
     * calculation is based on the cumulative percentages for each milestone
     * a milestones progress is measured as a percentage/fraction of the completed tasks for that milesone
     * 
     * [sum of all current milstone percentages]/[total possible milestone percentages] *100
     * (i.e. 5 milestones = [5* 100% = 500%] total possible milestone percentages)
     *
     * @usedby  Admin
     * @usedby  Team
     * 
     * @param	void
     * @return void
     */
    public function __commonAdmin_ProjectPecentageComplete($project_id)
    {

        if (!is_numeric($project_id)) {
            return 0;
        }

        //---------------calculate percentage-------------------
        //calculate the possible [total milestone] percentages (i.e. 5 milestones = [5* 100% = 500%])
        $total_possible_percentage = ($this->milestones_model->countMilestones($project_id, 'all')) * 100; //sum up all the current milestone percentage from all the milestone for this project
        $total_possible_percentage = ($total_possible_percentage <= 0) ? 100 : $total_possible_percentage; //make sure we have something
        $milestones = $this->milestones_model->listMilestones(0, 'results', $project_id);
        $current_percentages_total = 0;
        for ($i = 0; $i < count($milestones); $i++) {
            $current_percentages_total += $milestones[$i]['percentage'];
        }

        //work out the PROJECT progress based on [$current_percentages_total/$total_possible_percentage*100]
        $project_percentage = round(($current_percentages_total / $total_possible_percentage) * 100);
        $project_percentage = (is_numeric($project_percentage)) ? $project_percentage : 0; //return percentage
        return $project_percentage;
    }

    // -- __commonAdmin_Milestones- -------------------------------------------------------------------------------------------------------
    /**
     *
     *
     * @usedby  Admin
     * 
     * @param numeric $project_id]
     * @return void
     */
    public function __commonAdmin_Milestones($project_id)
    {

    }

    // -- __preRun_Arrays- -------------------------------------------------------------------------------------------------------
    /**
     * makes common arrays globally available
     *
     * @usedby  Admin
     * 
     * @param	void
     * @return void
     */
    public function __preRun_Arrays()
    {

        /** used in setting permissions etc.
         *(same as the column names in [groups] table)
         */
        $this->data['common_arrays']['permission_categories'] = array(
            'my_project_files',
            'my_project_details',
            'my_project_milestones',
            'my_project_my_tasks',
            'my_project_others_tasks',
            'my_project_messages',
            'my_project_team_messages',
            'my_project_invoices',
            'bugs',
            'clients',
            'tickets',
            'quotations');
        /** timer updates
         */
        $this->data['common_arrays']['timer_status'] = array(
            'running',
            'stopped',
            'reset');
    }

    //=================================================================CLIENT METHODS============================================================\\

    // -- __commonClient_LoggedInCheck- -------------------------------------------------------------------------------------------------------
    /**
     * checks if client user is logged in, else redirects
     * 
     * @usedby  Client
     * 
     * @param	void
     * @return void
     */
    public function __commonClient_LoggedInCheck()
    {

        //is user logged in..else redirect to login page
        if (!is_numeric($this->session->userdata('client_users_id')) || !is_numeric($this->session->userdata('client_users_clients_id'))) {
            redirect('client/login');
        }
    }

    // -- __commonClient_registerLastActive- -------------------------------------------------------------------------------------------------------
    /**
     * records a clientusers last activity as NOW() whenver this controler is loaded
     * 
     * @usedby  Client
     * 
     * @param	void
     * @return void
     */
    public function __commonClient_registerLastActive()
    {
        $this->users_model->registerLastActive($this->session->userdata('client_users_clients_id'));
        $this->data['debug'][] = $this->users_model->debug_data;
    }

    //=================================================================ADMIN & CLIENT METHODS============================================================\\

    // -- notices- -------------------------------------------------------------------------------------------------------
    /**
     * set the visibility of error messages (notices)
     * 
     * @usedby  ALL
     * 
     * @param	string [type: error/success]   [message: the error message]
     * @return void [sets to big data]
     */
    public function notices($type = '', $message = '', $display = 'noty')
    {

        //valid $type
        $valid_type = array('success', 'error'); //some sanity checks for valid params
        if (!in_array($type, $valid_type)) {
            return;
        }

        if ($message == '') {
            return;
        }

        //show ordinary notices on top of page
        if ($display == 'html') {
            if ($type == 'error') {
                $widget = 'wi_notice_error'; //used in tbs conditional [onshow; when wi_notice_error ==1] statement
            }

            //set the widget var
            if ($type == 'success') {
                $widget = 'wi_notice_success'; //used in tbs conditional [onshow; when wi_notice_success ==1] statement
            }
        }

        //show noty.js popup on bottom of page
        if ($display == 'noty') {
            if ($type == 'error') {
                $widget = 'wi_notice_error_noty'; //noty
            }

            //set the widget var
            if ($type == 'success') {
                $widget = 'wi_notice_success_noty'; //noty
            }
        }

        //save in big data array, for tbs usage
        $this->data['visible'][$widget] = 1;
        $this->data['notices'][$type] = $message;
    }

    // -- notifications- -------------------------------------------------------------------------------------------------------
    /**
     * set the visibility of notification style divs (e.g. [nothing found]
     * 
     * @usedby  ALL
     * 
     * @param	[message: the error message]
     * @param	[type: tabs|general]
     * @return void [sets to big data]
     */
    public function notifications($block = '', $message = '')
    {

        //set message and visibility of notification
        $this->data['visible'][$block] = 1;
        $this->data['vars']['notification'] = $message;
    }

    // -- __commonAll_View- -------------------------------------------------------------------------------------------------------
    /**
     * sets routine view settings and loads the view
     * 
     * @usedby  ALL
     * 
     * @param	string [type: error/success]   [message: the error message]
     * @return void [sets to big data]
     */
    public function __commonAll_View($view = '')
    {
        //refresh dynamic data (again) is there was a post
        if (isset($_POST['submit'])) {
            $this->__preRun_Dynamic_Data();
        }

        //post data
        $this->data['post'] = $_POST; //get data
        $this->data['get'] = $_GET; //url segmets array
        $this->data['url_segments'] = $this->uri->segment_array(); //sent to TBS engine
        
        $this->load->view($view, array('data' => $this->data));
    }

    // -- __preRun_System_Sanity_Checks- -------------------------------------------------------------------------------------------------------
    /**
     * checks the systems integrity, such
     *        - has installation completed
     *        - has the install folder been deleted
     *        - can we connect to the database
     *        - are writeable directories set correctly
     *
     * @usedby  ALL
     * 
     * @param	void
     * @return void [sets to big data]
     */
    public function __preRun_System_Sanity_Checks()
    {

        //flow control
        $next = true;

        //declare
        $message = '';

        /*INSTALLATION FOLDER*/
        if ($next) {
            if (is_dir(FCPATH . 'install')) {
                $message = 'If you have completed installation, you must delete the <strong>INSTALL</strong> folder';
                $sanity_failed = true;
                $next = false;
            }
        }

        /*INSTALLATION COMPLETED -GET VERSION*/
        if ($next) {
            $result = $this->version_model->currentVersion();
            $this->data['debug'][] = $this->version_model->debug_data;
            if ($result) {
                $this->data['version']['number'] = $result['version'];
                $this->data['version']['date'] = $result['date_installed'];
                $this->data['version']['install_type'] = $result['install_type'];

                //results
                $version_results = '<li> Version Number: ' . $result['version'] . ' <span style="color:#00a625;">PASSED</span></li>';
            } else {
                //set the message
                $version_results = '<li> Version Number: Unkown <span style="color:#fb2020;">FAILED</span></li>';
            }

            $message .= "<p><strong>Checking Application Version Number</strong></p> 
                            <ul>" . $version_results . "</ul>";

        }

        /*WRITEABLE DIRECTORIES*/
        if ($next) {

            $writeable_directories = array(
                FILES_BASE_FOLDER,
                PATHS_CACHE_FOLDER,
                PATHS_LOGS_FOLDER,
                UPDATES_FOLDER,
                FILES_AVATARS_FOLDER,
                FILES_TEMP_FOLDER,
                FILES_PROJECT_FOLDER,
                FILES_DATABASE_BACKUP_FOLDER,
                FILES_TICKETS_FOLDER,
                PATHS_CAPTCHA_FOLDER,
                DATABASE_CONFIG_FILE); //loop and check each folder

            $writeable_results = ''; //declare
            foreach ($writeable_directories as $value) {

                if (is_writeable($value)) {
                    $writeable_results .= '<li>' . $value . ' - <span style="color:#00a625;">PASSED</span></li>';
                } else {
                    $writeable_results .= '<li>' . $value . ' - <span style="color:#fb2020;">FAILED</span></li>';
                    $sanity_failed = true;
                }
            }

            //set the message
            $message .= "<p><strong>Checking Directories CHMOD Settings</strong></p> 
                            <ul>" . $writeable_results . "</ul>";
        }

        /*CHECK CURL IS INSTALLED*/
        if ($next) {
            if (!function_exists('curl_version')) {
                $curl_results = '<li>Curl Installed- <span style="color:#fb2020;">FAILED</span></li>';
                $sanity_failed = true;
            } else {
                $curl_results = '<li>Curl Installed- <span  style="color:#00a625;">PASSED</span></li>';
            }

            //message
            $message .= "<p><strong>Checking Curl </strong></p> 
                            <ul>" . $curl_results . "</ul>";
        }

        /*CHECK PHP VERSION*/
        if ($next) {
            if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                $php_results = '<li>PHP Version at least 5.3.0 - <span  style="color:#00a625;">PASSED</span></li>';
            } else {
                $php_results = '<li>PHP Version at least 5.3.0 - <span style="color:#fb2020;">FAILED</span></li>';
                $sanity_failed = true;
            }

            //message
            $message .= "<p><strong>Checking PHP Version </strong></p> 
                            <ul>" . $php_results . "</ul>";
        }

        //sanity check - failed
        if (isset($sanity_failed) && $sanity_failed) {
            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\

            //log error
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: $message]"); //show error and die
            show_error($message, 500); //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\
        }

    }

    // -- __preRun_MysqlUpdates- -------------------------------------------------------------------------------------------------------
    /**
     * checks the /updates folder for any .sql files
     *        - exectutes each file
     *        - if error are encountered it displays error and halts
     *        - if all is ok, it set a session message to say update was successful
     *
     * @usedby  ADMIN
     * @return void [shows errr]
     */
    public function __preRun_MysqlUpdates()
    {

        //only do this if on admin panel
        if (!is_numeric($this->session->userdata('team_profile_id'))) {
            return;
        }

        //get list of all file in /updates folder
        $map = directory_map(UPDATES_FOLDER, 1);

        //loop through all the files and select only the .sql files
        foreach ($map as $key => $value) {

            //reset error
            $errors = false;

            //set the file path
            $file_path = UPDATES_FOLDER . $value;

            //get some information about this file
            $file_path_info = pathinfo(UPDATES_FOLDER . $value);

            //use only .sql extension files
            if (is_array($file_path_info) && $file_path_info['extension'] == 'sql') {

                //set sql file path
                $sql_file_path = $file_path;

                //loop through each line and build a full query
                $file_content = file($file_path);
                $query = '';
                foreach ($file_content as $sql_line) {
                    if (trim($sql_line) != "" && strpos($sql_line, "--") === false) {
                        $query .= $sql_line;
                        if (preg_match("/;\s*$/", $sql_line)) {

                            //execute each query
                            $result = $this->updating_model->updateDatabase($query);
                            $this->data['debug'][] = $this->files_model->debug_data;

                            //error for display
                            $update_error = $this->files_model->debug_data;

                            //did we enounter an error
                            if (!$result) {
                                //error
                                $errors = true;

                                //exit this file
                                break;
                            }

                            //reset query
                            $query = "";
                        }
                    }
                }
            }

            //was there an error? - log, diplay & exit
            if ($errors) {

                //errr
                $message .= "<p><strong>UPDATING MYSQL DATABASE FAILED</strong></p>$update_error";

                //log error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: UPDATING MYSQL FAILED (file: $file_path) (error: $update_error) ]");

                //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\
                show_error($message, 500);

                //stop all other sql updates - halt here
                break;

            } else {
                //delete this file if there was no error
                if (isset($sql_file_path) && is_file($sql_file_path)) {
                    @unlink($sql_file_path);
                }
            }

        }

    }

    // -- __preRun_RefreshDatabase- -------------------------------------------------------------------------------------------------------
    /**
     * various database refreshing that is run on each page load
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void [sets to big data]
     */
    public function __preRun_RefreshDatabase($view = '')
    {

        /* REFRESH INVOICES BASIC STATUS
        * This is a light weight refresh of [invoices status] only and is not resources demanidng
        * A more detailed invoice updating routine is run via cron job
        */
        $this->refresh->basicInvoiceStatus();
        $this->data['debug'][] = $this->refresh->debug_data; //library debug

    }

    // -- __preRun_Static_Data- -------------------------------------------------------------------------------------------------------
    /**
     * system wide information set into data arary
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void [sets to big data]
     */
    public function __preRun_Static_Data()
    {

        /*CONFIG FILE - SET TO DATA ARRAY*/
        $this->data['config'] = $this->config->config;
        /*BASE URL's*/
        $this->data['vars']['site_url'] = site_url(); //main
        $this->data['vars']['site_url_client'] = site_url('/client'); //clients
        $this->data['vars']['site_url_admin'] = site_url('/admin'); //admin
        $this->data['vars']['site_url_api'] = site_url('/api'); //api
        $this->data['vars']['site_url_current_page'] = current_url();
        /*PAYPAL IPN URL*/
        $this->data['vars']['paypal_ipn_url'] = site_url('/api/paypalipn'); //admin

        /*CR0N JOB LINK
        *--------------------------------------------------------------------
        *  url has special key to prevent anyone running this cron urls'
        * key must be changed to make it unique in the settings.php file
        *--------------------------------------------------------------------
        */
        $this->data['vars']['cronjobs_url_general'] = site_url('/admin/cronjobs/general/' . $this->data['config']['security_key']);
        /*ALLOWED FILE TYPES LIST - HUMAN READABLE
        * used mainly to display files types that are allowed in 'info/help' tips for users
        */
        if ($this->config->item('files_allowed_types') === 0) {
            $this->data['vars']['allowed_file_types_human_readable'] = $this->data['lang']['lang_all'];
        } else {
            $this->data['vars']['allowed_file_types_human_readable'] = str_replace('|', ', ', $this->config->item('files_allowed_types'));
        }

    }

    // -- __preRun_Dynamic_Data- -------------------------------------------------------------------------------------------------------
    /**
     * system wide database stored information set into data arary
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void [sets to big data]
     */
    public function __preRun_Dynamic_Data()
    {

        /*CURRENT URL - PAGE URL*/
        $this->data['vars']['current_url'] = current_url();

        /* ADMIN - THIS TEAM MEMBERS GLOBALLY ACCESSIBLE DATA
        * --only do this if a team member user is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {

            //load users profile data
            $this->member_id = $this->session->userdata('team_profile_id');
            $this->team_member = $this->teamprofile_model->teamMemberDetails($this->member_id);
            $this->data['debug'][] = $this->teamprofile_model->debug_data; //refresh my data
            $this->data['vars']['my_user_type'] = 'team';
            $this->data['vars']['my_id'] = $this->member_id;
            $this->data['vars']['my_unique_id'] = $this->team_member['team_profile_uniqueid'];
            $this->data['vars']['my_name'] = $this->team_member['team_profile_full_name'];
            $this->data['vars']['my_email'] = $this->team_member['team_profile_email'];
            $this->data['vars']['my_group'] = $this->team_member['team_profile_groups_id'];
            $this->data['vars']['my_group_name'] = $this->session->userdata('groups_name');
            $this->data['vars']['my_avatar'] = $this->team_member['team_profile_avatar_filename'];
            $this->data['vars']['my_telephone'] = $this->team_member['team_profile_telephone'];
			$this->data['vars']['today_date'] = date("Y-m-d");
        }

        /*EVENTS RANDOM CODE*/
        $this->data['vars']['new_events_id'] = random_string('alnum', 40);
        /*TODAYS FRIENDLY DATE*/
        $this->data['vars']['todays_date'] = date('j F Y'); //8 June 2014

        /*GENERAl SETTINGS*/
        $this->data['settings_general'] = $this->settings_general_model->getSettings();
        $this->data['debug'][] = $this->settings_general_model->debug_data;
        $this->data['reg_fields'][] = 'settings_general';
        $this->data['fields']['settings_general'] = $this->data['settings_general'];
        /*GENERAl COMPANY*/
        $this->data['settings_company'] = $this->settings_company_model->getSettings();
        $this->data['debug'][] = $this->settings_company_model->debug_data;
        $this->data['reg_fields'][] = 'settings_company';
        $this->data['fields']['settings_company'] = $this->data['settings_company'];
        /*INVOICE SETTINGS*/
        $this->data['settings_invoices'] = $this->settings_invoices_model->getSettings();
        $this->data['debug'][] = $this->settings_invoices_model->debug_data;
        $this->data['reg_fields'][] = 'settings_invoices';
        $this->data['fields']['settings_invoices'] = $this->data['settings_invoices']; //set to data->fields array

        /*COMPANY DETAILS*/
        $this->data['settings_company'] = $this->settings_company_model->getSettings();
        $this->data['debug'][] = $this->settings_company_model->debug_data;
        $this->data['reg_fields'][] = 'settings_company';
        $this->data['fields']['settings_company'] = $this->data['settings_company']; //set to data->fields array

        /* MAILING LISTS
        * lists (arrays) of vairous email addresses used to send email
        * typically used to send out notifications and system emails
        */
        /*ADMIN - MAILING LIST OF ADMINS EMAILS
        * this is a list of all the emails for users in admin groupd
        * normally used to send out system notifcations
        */
        $result = $this->teamprofile_model->mailingListAdmin();
        $this->data['debug'][] = $this->teamprofile_model->debug_data;
        for ($i = 0; $i < count($result); $i++) {
            $this->data['vars']['mailinglist_admins'][] = $result[$i]['team_profile_email'];
            $this->mailinglist_admins[] = $result[$i]['team_profile_email'];
        }

        /* ADMIN - MY PROJECTS ARRAY & LIST
        * a comma separated list of all MY projects ID's (logged in team)
        * also a standard array of the project ID's
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $projects = $this->project_members_model->allMembersProjects($this->session->userdata('team_profile_id'));
            $this->data['debug'][] = $this->project_members_model->debug_data; //set arrays
            $this->data['my_leaders_projects_array'] = array();
            $this->data['my_active_projects_array'] = array();
            $this->data['my_projects_array'] = array();

            //declare
            $this->data['vars']['my_leaders_projects_list'] = '';
            $this->data['vars']['my_active_projects_list'] = '';
            $this->data['vars']['my_projects_list'] = '';
            $this->data['vars']['my_completed_projects_list'] = '';

            //loop through and create list of project id's & also normal array
            for ($i = 0; $i < count($projects); $i++) {

                /** all projects that I am leader */
                if ($projects[$i]['project_members_project_lead'] == 'yes') {
                    //comma list
                    $this->data['vars']['my_leaders_projects_list'] .= $projects[$i]['project_members_project_id'] . ','; //normal array
                    $this->data['my_leaders_projects_array'][] = $projects[$i]['project_members_project_id'];
                }

                /** all projects that are active */
                if ($projects[$i]['projects_status'] != 'completed') {
                    //comma list
                    $this->data['vars']['my_active_projects_list'] .= $projects[$i]['project_members_project_id'] . ','; //normal array
                    $this->data['my_active_projects_array'][] = $projects[$i]['project_members_project_id'];
                }

                /** all projects that are behind */
                if ($projects[$i]['projects_status'] == 'behind schedule') {
                    //comma list
                    $this->data['vars']['my_active_projects_list'] .= $projects[$i]['project_members_project_id'] . ','; //normal array
                    $this->data['my_behind_projects_array'][] = $projects[$i]['project_members_project_id'];
                }

                /** all projects that are currently on time */
                if ($projects[$i]['projects_status'] == 'in progress') {
                    //comma list
                    $this->data['vars']['my_active_projects_list'] .= $projects[$i]['project_members_project_id'] . ','; //normal array
                    $this->data['my_inprogress_projects_array'][] = $projects[$i]['project_members_project_id'];
                }

                /** all projects that are completed */
                if ($projects[$i]['projects_status'] == 'completed') {
                    //comma list
                    $this->data['vars']['my_completed_projects_list'] .= $projects[$i]['project_members_project_id'] . ','; //normal array
                    $this->data['my_completed_projects_list'][] = $projects[$i]['project_members_project_id'];
                }

                /** all projects */
                //comma list
                $this->data['vars']['my_projects_list'] .= $projects[$i]['project_members_project_id'] . ','; //normal array
                $this->data['my_projects_array'][] = $projects[$i]['project_members_project_id'];
            }

            //trim trailing comma ,
            $this->data['vars']['my_projects_list'] = rtrim($this->data['vars']['my_projects_list'], ',');
        }

        /* ADMIN - 'MY' PROJECTS COUNT
        * [my_projects_count.in_progress]
        * [my_projects_count.completed]
        * [my_projects_count.behind_schedule]
        * [my_projects_count.all_open]
        * [my_projects_count.all_projects]
        * 
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['reg_fields'][] = 'my_projects_count';
            $this->data['fields']['my_projects_count']['in_progress'] = isset($this->data['my_inprogress_projects_array']) ? count($this->data['my_inprogress_projects_array']) : 0;
            $this->data['fields']['my_projects_count']['completed'] = isset($this->data['my_completed_projects_list']) ? count($this->data['my_completed_projects_list']) : 0;
            $this->data['fields']['my_projects_count']['behind_schedule'] = isset($this->data['my_behind_projects_array']) ? count($this->data['my_behind_projects_array']) : 0;
            $this->data['fields']['my_projects_count']['all_open'] = isset($this->data['my_active_projects_array']) ? count($this->data['my_active_projects_array']) : 0;
            $this->data['fields']['my_projects_count']['all_projects'] = isset($this->data['my_projects_array']) ? count($this->data['my_projects_array']) : 0;

        }

        /* ADMIN - 'ALL' PROJECTS COUNT
        * [projects_count.in_progress]
        * [projects_count.completed]
        * [projects_count.behind_schedule]
        * [projects_count.all_open]
        * [projects_count.all_projects]
        * 
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['reg_fields'][] = 'projects_count';
            $this->data['fields']['projects_count'] = $this->projects_model->allProjectsCounts();
            $this->data['debug'][] = $this->projects_model->debug_data;
        }

        /* ADMIN - TICKETS COUNTS
        * [tickets_count.new]
        * [tickets_count.closed]
        * [tickets_count.client_replied]
        * [tickets_count.answered]
        * [tickets_count.all_open]
        * [tickets_count.all_tickets]
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['reg_fields'][] = 'tickets_count';
			if ($this->data['vars']['my_group']!=1)
			{
            	$this->data['fields']['tickets_count'] = $this->tickets_model->allTicketCounts(null,$this->data['vars']['my_id']);
			}
			else {
				$this->data['fields']['tickets_count'] = $this->tickets_model->allTicketCounts();
			}
            $this->data['debug'][] = $this->tickets_model->debug_data;
        }

        /* ADMIN - BUGS COUNTS
        * [bugs_count.new]
        * [bugs_count.resolved]
        * [bugs_count.in_progress]
        * [bugs_count.not_a_bug]
        * [bugs_count.all_open]
        * [bugs_count.all_bugs]
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['reg_fields'][] = 'bugs_count';
            $this->data['fields']['bugs_count'] = $this->bugs_model->allBugsCounts();
            $this->data['debug'][] = $this->bugs_model->debug_data;
        }

        /* ADMIN -  QUOTATIONS COUNTS
        * [quotations_count.new]
        * [quotations_count.completed]
        * [quotations_count.pending]
        * [quotations_count.all_open]        
        * [quotations_count.all_quotations]
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['reg_fields'][] = 'quotations_count';
            $this->data['fields']['quotations_count'] = $this->quotations_model->allQuotationsCounts();
            $this->data['debug'][] = $this->quotations_model->debug_data;
        }

        /*ADMIN - QUOTATION FORMS COUNT
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['vars']['quotation_forms_count'] = $this->quotationforms_model->countForms();
            $this->data['debug'][] = $this->quotationforms_model->debug_data;
        }

        /*ADMIN - QUOTATION FORMS COUNT
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['vars']['clients_count'] = $this->clients_model->countClients();
            $this->data['debug'][] = $this->clients_model->debug_data;
        }

        /* ADMIN -  MY TASKS COUNT
        * [my_tasks_count.pending]
        * [my_tasks_count.completed]
        * [my_tasks_count.behing_schedule]
        * [my_tasks_count.all_open]
        * [my_tasks_count.all_tasks]
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['reg_fields'][] = 'my_tasks_count';
            $this->data['fields']['my_tasks_count'] = $this->tasks_model->allMyTasksCounts($this->session->userdata('team_profile_id'));
            $this->data['debug'][] = $this->tasks_model->debug_data;
        }

        /* ADMIN - MY PROJECTS COUNT
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['vars']['count_my_projects'] = $this->project_members_model->countMyProjects($this->session->userdata('team_profile_id'));
            $this->data['debug'][] = $this->projects_model->debug_data;
        }

        /* CLIENT - THIS CLIENTS GLOBALLY ACCESSIBLE DATA
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {

            //set acclient id
            $this->client_id = $this->session->userdata('client_users_clients_id'); //load clients profile data
            $this->client = $this->clients_model->clientDetails($this->client_id);
            $this->data['debug'][] = $this->clients_model->debug_data; //load users profile data
            $this->user_id = $this->session->userdata('client_users_id');
            $this->client_user = $this->users_model->userDetails($this->user_id);
            $this->data['debug'][] = $this->clients_model->debug_data;
            //refresh my data
            $this->data['vars']['my_id'] = $this->user_id;
            $this->data['vars']['my_unique_id'] = $this->client_user['client_users_uniqueid'];
            $this->data['vars']['my_name'] = $this->client_user['client_users_full_name'];
            $this->data['vars']['my_user_type'] = 'client';
            $this->data['vars']['my_primary_contact'] = $this->client_user['client_users_main_contact'];
            $this->data['vars']['my_email'] = $this->client_user['client_users_email'];
            $this->data['vars']['my_avatar'] = $this->client_user['client_users_avatar_filename'];
            $this->data['vars']['my_telephone'] = $this->client_user['client_users_telephone'];
            $this->data['vars']['my_client_id'] = $this->client_id;
            $this->data['vars']['my_company_name'] = $this->client['clients_company_name'];
            $this->data['vars']['my_company_address'] = $this->client['clients_address'];
            $this->data['vars']['my_company_city'] = $this->client['clients_city'];
            $this->data['vars']['my_company_state'] = $this->client['clients_state'];
            $this->data['vars']['my_company_zipcode'] = $this->client['clients_zipcode'];
        }

        /* CLIENT - PROJECTS COUNT
        * [client_projects_count.in_progress]
        * [client_projects_count.completed]
        * [client_projects_count.behind_schedule]
        * [client_projects_count.all_open]
        * [client_projects_count.all_projects]
        * 
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $this->data['reg_fields'][] = 'client_projects_count';
            $this->data['fields']['client_projects_count'] = $this->projects_model->allProjectsCounts($this->session->userdata('client_users_clients_id'));
            $this->data['debug'][] = $this->projects_model->debug_data;
        }

        /* CLIENT - TICKETS COUNTS
        * [client_tickets_count.new]
        * [client_tickets_count.closed]
        * [client_tickets_count.client_replied]
        * [client_tickets_count.answered]
        * [client_tickets_count.all_open]
        * [client_tickets_count.all_tickets]
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $this->data['reg_fields'][] = 'client_tickets_count';
            $this->data['fields']['client_tickets_count'] = $this->tickets_model->allTicketCounts($this->session->userdata('client_users_clients_id'));
            $this->data['debug'][] = $this->tickets_model->debug_data;
        }

        /* CLIENT - BUGS COUNTS
        * [client_bugs_count.new]
        * [client_bugs_count.resolved]
        * [client_bugs_count.in_progress]
        * [client_bugs_count.not_a_bug]
        * [client_bugs_count.all_open]
        * [client_bugs_count.all_bugs]
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $this->data['reg_fields'][] = 'client_bugs_count';
            $this->data['fields']['client_bugs_count'] = $this->bugs_model->allBugsCounts($this->session->userdata('client_users_clients_id'));
            $this->data['debug'][] = $this->bugs_model->debug_data;
        }

        /* CLIENT - QUOTATIONS COUNTS
        * [client_quotations_count.all_quotations]
        * [client_quotations_count.new]
        * [client_quotations_count.completed]
        * [client_quotations_count.pending]
        * [client_quotations_count.all_open]
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $this->data['reg_fields'][] = 'client_quotations_count';
            $this->data['fields']['client_quotations_count'] = $this->quotations_model->allQuotationsCounts($this->session->userdata('client_users_clients_id'));
            $this->data['debug'][] = $this->quotations_model->debug_data;
        }

        /* CLIENT - USERS COUNTS
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $this->data['vars']['client_users_count'] = $this->users_model->allUsersCounts($this->session->userdata('client_users_clients_id'));
            $this->data['debug'][] = $this->users_model->debug_data;
        }

        /* CLIENT - COMMA SEPERATED LIST OF CLIENTS PROJECTS
        *  create a list that can be used in sql query e.g. (WHERE project_events_project_id IN (2,4,5,9))
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $projects = $this->projects_model->allProjects('projects_id', 'DESC', $this->session->userdata('client_users_clients_id', 'all'));
            $this->data['debug'][] = $this->projects_model->debug_data;
            //do we have any projects
            if (@count($projects) <= 0 || !is_array($projects)) {
                //show no events message
                $this->data['visible']['no_timeline_events'] = 1; //halt
                $next = false;
            } else {

                //loop through and create list of project id's & also normal array
                $this->data['vars']['my_clients_project_list'] = '';
                for ($i = 0; $i < count($projects); $i++) {
                    //comma list
                    $this->data['vars']['my_clients_project_list'] .= $projects[$i]['projects_id'] . ','; //normal array
                    $this->data['my_clients_project_array'][] = $projects[$i]['projects_id'];
                }

                //trim trailing comma ,
                $this->data['vars']['my_clients_project_list'] = rtrim($this->data['vars']['my_clients_project_list'], ',');
            }
        }

    }

    // -- __preRun_Language- -------------------------------------------------------------------------------------------------------
    /**
     * validate language file set in settings actually exists. If not, halt system with an error message
     * create an array of all languages that are available
     * create pulldown list of languages available
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void [sets to big data] 
     *               - $this->data['languages_available']
     *               - $this->data['lists']['all_languages']
     */
    public function __preRun_Language()
    {

        //currently specified language
        $current_language = $this->data['fields']['settings_general']['language']; //get content of language folder
        $language_folder = directory_map(PATHS_LANGUAGE_FOLDER, false, false); //check if the file 'defauls_lang.php' exists in each folder that is found
        $this->data['lists']['all_languages'] = ''; //declare
        foreach ($language_folder as $key => $value) {
            if (is_array($language_folder[$key])) {
                if (in_array('default_lang.php', $language_folder[$key])) {

                    //it exists, add it to languages array
                    $this->data['languages_available'][] = $key; //add it to language pull down list
                    $this->data['lists']['all_languages'] .= '<option value="' . $key . '">' . ucfirst($key) . '</option>';
                }
            }
        }

        // check if language that is set in settings_general, physically exists
        if (!in_array($current_language, $this->data['languages_available'])) {

            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\
            $message = '<b>File: </b>' . __file__ . '<br/>
                        <b>Function: </b>' . __function__ . '<br/>
                        <b>Line: </b>' . __line__ . '<br/>
                        <b>Notes: </b> Specified language file could not be found (' . $current_language . ')'; //display error
            show_error($message, 500); //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\

        }

        //everything is ok,load language into array
        $this->data['lang'] = $this->lang->load('default', $current_language, true);

        //--------------UTF-8 Encode the kanguage file ------------------
        function utf8encode(&$item, $key)
        {
            $item = htmlspecialchars(utf8_encode($item));
        }
        //only do this if language_mode is set to "2" in settings.php
        if ($this->config->item('language_mode') == 2) {
            array_walk_recursive($this->data['lang'], 'utf8encode');
        }
        //--------------UTF-8 Encode the language file end----------------

    }

    // -- __preRun_Theme- -------------------------------------------------------------------------------------------------------
    /**
     * validate theme that in settings actually exists. If not, halt system with an error message
     * create an array of all themes that are available
     * create pulldown list of themes available
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void [sets to big data] 
     *               - $this->data['themes_available']
     *               - $this->data['lists']['all_themes']
     */
    public function __preRun_Theme()
    {

        //currently specified theme
        $current_theme = $this->data['fields']['settings_general']['theme']; //get content of language folder
        $themes_folder = directory_map(PATHS_APPLICATION_FOLDER . 'themes', false, false);
        /* get each 'folder' name (only folders in first level)
        *  - at this first stage, we only check the admin themes
        *  - assume its a valid theme
        *  - add it to array and pulldown
        */
        $this->data['lists']['all_themes'] = '';
        foreach ($themes_folder as $key => $value) {

            //add folder name to theme array
            $this->data['themes_available'][] = $key; //add folder name to pull down list
            $this->data['lists']['all_themes'] .= '<option value="' . $key . '">' . ucfirst($key) . '</option>';
        }

        // check if theme that is currently set in settings_general, physically exists
        if (!in_array($current_theme, $this->data['themes_available'])) {

            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\
            $message = '<b>File: </b>' . __file__ . '<br/>
                        <b>Function: </b>' . __function__ . '<br/>
                        <b>Line: </b>' . __line__ . '<br/>
                        <b>Notes: </b> Specified theme could not be found (' . $current_theme . ')'; //display error
            show_error($message, 500); //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\

        }

        /* now save the current theme in a constant for global use
        *  also set the client theme to same name
        */
        define('PATHS_ADMIN_THEME', FCPATH . "application/themes/$current_theme/admin/");
        define('PATHS_CLIENT_THEME', FCPATH . "application/themes/$current_theme/client/");
        define('PATHS_COMMON_THEME', FCPATH . "application/themes/$current_theme/common/"); //check if client theme/folder also exists
        if (!is_dir(PATHS_CLIENT_THEME)) {

            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\
            $message = '<b>File: </b>' . __file__ . '<br/>
                        <b>Function: </b>' . __function__ . '<br/>
                        <b>Line: </b>' . __line__ . '<br/>
                        <b>Notes: </b> Specified [client] theme could not be found (' . $current_theme . ')'; //display error
            show_error($message, 500); //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\

        }
    }

    // -- __preRun_Libraries- -------------------------------------------------------------------------------------------------------
    /**
     * any libraries that need to be loaded in any special way
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void
     */
    public function __preRun_Libraries()
    {

        /*FORM PROCESSOR */
        //load form processor with default language
        $this->load->library("Form_processor", $this->data['lang']);
    }

    // -- __preRun_Dir_Cleanup- -------------------------------------------------------------------------------------------------------
    /**
     * clean up the 
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void
     */
    public function __preRun_Dir_Cleanup()
    {

        /*FORM PROCESSOR */
        //load form processor with default language
        $this->load->library("Form_processor", $this->data['lang']);
    }

}

/* End of file My_Controller.php */
/* Location: ./application/core/My_Controller.php */
