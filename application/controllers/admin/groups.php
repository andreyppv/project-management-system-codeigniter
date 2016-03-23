<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Groups related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Groups extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'groups.html';

        //css settings
        $this->data['vars']['css_menu_topnav_groups'] = 'nav_alternative_controls_active'; //menu
        $this->data['vars']['css_menu_groups'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_groups'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-sitemap"></i>';

        //PERMISSIONS CHECK - GENERAL
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            redirect('/admin/error/permission-denied');
        }

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

        //create pulldown lists
        $this->__pulldownLists();

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'list':
                $this->__listGroups();
                break;

            case 'list-members-modal':
                $this->__listMembersModal();
                break;

            case 'permissions-modal':
                $this->__editPermissionLevel();
                break;

            case 'edit-settings':
                $this->__editSettings();
                break;

            case 'migrate-members':
                $this->__migrateMembers();
                break;

            case 'add-new-group':
                $this->__addNewgroup();
                break;

            default:
                $this->__listGroups();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * checks if a given action is allowed. Example: can this groupd be deleted?
     * redirects to error page if action is not allowed and halts all other steps in this controller
     *
     * @param $group_id the groups id
     * @param $action corresponding to the 'action fileds' in the 'groups' mysql table (listed below)
     */
    function __checkAllowable($group_id = '', $action = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if allowable
        if (!$this->groups_model->isActionAllowable($group_id, $action)) {
            $this->data['debug'][] = $this->groups_model->debug_data;

            //redirect to error handler
            redirect('admin/error/not-allowed');

            //extra precaution
            die();
        }

    }

    /**
     * lists all the groups
     */
    function __listGroups()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show wi_clients_search widget
        $this->data['visible']['wi_clients_search'] = 1;

        //get results and save for tbs block merging
        $this->data['blk1'] = $this->groups_model->allGroups();
        $this->data['debug'][] = $this->clients_model->debug_data;

        //check if any groups were found
        if (empty($this->data['blk1'])) {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        } else {
            $this->data['visible']['wi_groups_table'] = 1;
        }

    }

    /**
     * edit groups permissions levels
     */
    function __editPermissionLevel()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'groups.modal.html';

        //get client id
        $group_id = $this->uri->segment(4);

        //permission categories - (same as the column names in [groups] table)
        $permission_categories = $this->data['common_arrays']['permission_categories']; //array from MY_controller

        //load from database
        foreach ($permission_categories as $category) {
            $this->data['blk1'][] = $this->groups_model->groupPermissions($group_id, $category);
            $this->data['debug'][] = $this->groups_model->debug_data;
        }

        //visibility - show table or show nothing found
        if (!empty($this->data['blk1'])) {
            $this->data['visible']['wi_edit_group_permissions_table'] = 1;
            $this->data['visible']['wi_info_permissions_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     * list all members in a group
     */
    function __listMembersModal()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'groups.modal.html';

        //get client id
        $group_id = $this->uri->segment(4);

        //get all memebers results
        $this->data['blk2'] = $this->groups_model->allGroupMembers($group_id);
        $this->data['debug'][] = $this->groups_model->debug_data;

        //create editable.js data set of groups
        $result = $this->groups_model->allGroups('groups_name', 'ASC');
        $this->data['debug'][] = $this->groups_model->debug_data;
        $this->data['vars']['editable_groups_list'] = '';
        for ($i = 0; $i < count($result); $i++) {
            $this->data['vars']['editable_groups_list'] .= "{value: '" . $result[$i]['groups_id'] . "', text: '" . $result[$i]['groups_name'] . "'},";
        }
        $this->data['vars']['editable_groups_list'] = rtrim($this->data['vars']['editable_groups_list'], ",");

        //visibility - show table or show nothing found
        if (!empty($this->data['blk2'])) {
            //show members table
            $this->data['visible']['wi_group_members_table'] = 1;
        } else {
            //error notfication
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
            $this->data['visible']['wi_group_members_table'] = 0;
        }

    }

    /**
     * edit a single field record for a particular group.
     *
     * @param	$field the name of database field
     */
    function __editSettings($field = 'groups_name')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //get field name (if any has been provided)
        if ($this->input->post('field') != '') {
            $field = $this->input->post('field');
        }

        //validate form & display any errors
        if (!$this->__flmFormValidation('edit_group')) {

            //success message
            $this->notices('error', $this->form_processor->error_message);

        } else {

            //save new group data to database
            if ($this->groups_model->editGroup($this->input->post('groups_id'), $field, $this->input->post('groups_name'))) {

                //success message
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

            } else {

                //error message
                $this->notifications('wi_notification', $this->data['lang']['lang_request_could_not_be_completed']);
            }
            $this->data['debug'][] = $this->groups_model->debug_data;
        }

        //finished - now list groups
        $this->__listGroups();

    }

    /**
     * migrate group members from one group to another
     *
     */
    function __migrateMembers()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if migration is allowable for this group
        $this->__checkAllowable($this->input->post('old_groups_id'), 'groups_allow_migrate');

        //migrate the members to new group
        if ($this->teamprofile_model->migrateMembers($this->input->post('old_groups_id'), $this->input->post('new_groups_id'))) {

            //show success
            $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

        } else {

            //show error
            $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

        }
        $this->data['debug'][] = $this->teamprofile_model->debug_data;

        //finished - now list groups
        $this->__listGroups();
    }

    /**
     * add a new group
     *
     */
    function __addNewGroup()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            redirect('/admin/groups/list');
        }

        //validate form & display any errors
        if (!$this->__flmFormValidation('add_group')) {
            //show form error
            $this->notices('error', $this->form_processor->error_message);
            $next = false;
        }

        //add new group
        if ($next) {
            if ($this->groups_model->addNewGroup($this->input->post('groups_name'))) {
                //show success
                $this->notices('success', 'New group hase been created - Default permissions set');
            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_an_error_has_occurred']);
            }
        }

        //show list of groups
        $this->__listGroups();

    }

    /**
     * validates forms for various methods in this class
     * @param string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //form validation
        if ($form == 'add_user') {

            //check required fields
            $fields = array('company_name_field' => $this->data['lang']['lang_company_name'], 'email_field' => $this->data['lang']['lang_email']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check email fields
            $fields = array('users_email' => $this->data['lang']['lang_email']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check password (lenght only - 8 characters min)
            $fields = array('password_field' => $this->data['lang']['lang_password']);
            if (!$this->form_processor->validateFields($fields, 'length')) {
                return false;
            }

            //everything ok
            return true;
        }

        //form validation
        if ($form == 'edit_group') {

            //check required fields
            $fields = array('groups_name' => $this->data['lang']['lang_group_name'], 'groups_id' => $this->data['lang']['lang_id']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //everything ok
            return true;
        }

        //---------------validate a form--------------------------------------
        if ($form == 'add_group') {

            //check required fields
            $fields = array('groups_name' => $this->data['lang']['lang_group_name']);
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
     */
    function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //[all_groups_id]
        $data = $this->groups_model->allGroups('groups_name', 'ASC');
        $this->data['debug'][] = $this->groups_model->debug_data;
        $this->data['lists']['all_groups_id'] = create_pulldown_list($data, 'groups', 'id');
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

/* End of file groups.php */
/* Location: ./application/controllers/admin/groups.php */
