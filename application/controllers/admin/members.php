<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Members related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Members extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.members.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_members'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-group"></i>';
    

    }

    /**
     * This is our re-routing function and is the inital function called
     */
    function index()
    {

        /* --------------URI SEGMENTS---------------
        * [segment example]
        * /admin/files/2/view/*.*
        * (2)->controller
        * (3)->project_id
        * (4)->router
        ** -----------------------------------------*/

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

        //create pulldown lists
        $this->__pulldownLists();

        //get the action from url
        $action = $this->uri->segment(4);

        //route the request
        switch ($action) {

            case 'view':
                $this->__viewMembers();
                break;

            case 'add':
                $this->__addMembers();
                break;

            case 'edit':
                $this->__editMembers();
                break;

            default:
                $this->__viewMembers();
                break;
        }

        //css - active tab
        $this->data['vars']['css_active_tab_members'] = 'side-menu-main-active';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * loads project members, both team members and clients users
     */
    function __viewMembers()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show table
        $this->data['visible']['wi_project_members'] = 1;

        //load team members first
        $this->data['reg_blocks'][] = 'members';
        $this->data['blocks']['members'] = $this->project_members_model->listProjectmembers($this->project_id);
        $this->data['debug'][] = $this->project_members_model->debug_data;
    
    
        $this->data['vars']['return_link'] = $this->data['vars']['site_url_admin'].'/project/'.$this->uri->segment(3).'/view';

        //load client members first
        $this->data['reg_blocks'][] = 'clients_users';
        $this->data['blocks']['clients_users'] = $this->users_model->clientUsers($this->data['project_details']['clients_id']);
        $this->data['debug'][] = $this->users_model->debug_data;

    }

    /**
     * make a member the project leader
     */
    function __editMembers()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //PERMISSIONS CHECK - GENERAL
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //prevent direct access
        if (!isset($_POST['submit'])) {
            $redirect = str_replace('edit', 'view', uri_string());
            redirect($redirect);
        }

        //flow control
        $next = true;

        //validate post data
        if ($next) {
            if (!$this->__flmFormValidation('edit_member_hidden')) {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty');

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Editing project leader failed: Required hidden form field missing or invalid]");

                //halt
                $next = false;
            }
        }

        //make leader
        if ($next) {
            $result = $this->project_members_model->updateProjectLead($this->project_id, $this->input->post('project_members_team_id'));
            $this->data['debug'][] = $this->project_members_model->debug_data;
            if ($result) {
                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');
            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty');
            }
        }

        //load members list
        $this->__viewMembers();

    }

    /**
     * add new member to project
     */
    function __addMembers()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            $redirect = str_replace('add', 'view', uri_string());
            redirect($redirect);
        }

        //PERMISSIONS CHECK - GENERAL
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //flow control
        $next = true;

        //validate post data
        if ($next) {
            if (!$this->__flmFormValidation('add_member')) {
                //show error
                $this->notices('error', $this->form_processor->error_message);
                $next = false;
            }
        }

        //check if member is not already in list
        if ($next) {
            $result = $this->project_members_model->isMemberAssigned($this->project_id, $this->input->post('team_profile_id'));
            $this->data['debug'][] = $this->project_members_model->debug_data;
            if ($result) {
                //show error
                $this->notices('error', $this->data['lang']['lang_this_member_is_already_assigned_to_this_project'], 'noty');
                //halt
                $next = false;
            }
        }

        //add member to project
        if ($next) {
            $result = $this->project_members_model->addMember($this->project_id, $this->input->post('team_profile_id'));
            $this->data['debug'][] = $this->project_members_model->debug_data;
            if ($result) {
                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');
            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty');
            }
        }

        //load members list
        $this->__viewMembers();

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

        //[all_team_members]
        $data = $this->teamprofile_model->allTeamMembers('team_profile_full_name', 'ASC');
        $this->data['debug'][] = $this->teamprofile_model->debug_data;
        $this->data['lists']['all_team_members'] = create_pulldown_list($data, 'team_members', 'id');

    }

    /**
     * validates forms for various methods in this class
     * @param string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //form validation
        if ($form == 'add_member') {

            //check required fields
            $fields = array('team_profile_id' => $this->data['lang']['lang_member']);
            if (!$this->form_processor->validateFields($fields, 'numeric')) {
                return false;
            }

            //everything ok
            return true;
        }

        //form validation
        if ($form == 'edit_member_hidden') {

            //check required fields
            $fields = array('project_members_team_id' => $this->data['lang']['lang_member']);
            if (!$this->form_processor->validateFields($fields, 'numeric')) {
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

/* End of file members.php */
/* Location: ./application/controllers/admin/members.php */
