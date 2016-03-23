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
        $this->data['template_file'] = PATHS_CLIENT_THEME . 'project.members.html';

        //default page titles
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_members'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-group"></i>';

        $this->data['vars']['sub_title'] = '';
        $this->data['vars']['sub_title_icon'] = '';

    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonClient_LoggedInCheck();

        //get project id
        $this->project_id = $this->uri->segment(3);

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //get the action from url
        $action = $this->uri->segment(4);

        /** CLIENT-RESOURCE-OWNERSHIP VALIDATION **/
        if (!in_array($this->project_id, $this->data['my_clients_project_array'])) {
            redirect('/client/error/permission-denied');
        }

        //route the request
        switch ($action) {

            case 'view':
                $this->__viewMembers();
                break;

            default:
                $this->__viewMembers();
                break;
        }

        //load view
        $this->__flmView('client/main');

    }

    /**
     * loads project members, both team members and clients users
     *
     * @param	string
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

        //load client members first
        $this->data['reg_blocks'][] = 'clients_users';
        $this->data['blocks']['clients_users'] = $this->users_model->clientUsers($this->data['project_details']['clients_id']);
        $this->data['debug'][] = $this->users_model->debug_data;

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
/* Location: ./application/controllers/client/members.php */
