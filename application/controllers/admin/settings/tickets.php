<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * class for perfoming all Tickets Settings settings related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Tickets extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.tickets.html';

        //css settings
        $this->data['vars']['css_menu_topnav_settings'] = 'nav_alternative_controls_active'; //menu
        $this->data['vars']['css_menu_heading_settings'] = 'heading-menu-active'; //menu
        $this->data['vars']['css_menu_settings'] = 'open'; //menu
        
        //PERMISSIONS CHECK - GENERAL
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            redirect('/admin/error/permission-denied');
        }

    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     * 
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
        $action = $this->uri->segment(4);

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_settings_tickets'];

        //re-route to correct method
        switch ($action) {
            case 'view-departments':
                $this->__viewDepartments();
                break;

            case 'add-department':
                $this->__addDepartment();
                break;

            case 'edit-department':
                $this->__editDepartment();
                break;

            case 'migrate-tickets':
                $this->__migrateTickets();
                break;

            case 'delete-tickets':
                $this->__deleteTickets();
                break;

            case 'view-mailer':
                $this->__viewMailer();
                break;

            case 'edit-mailer':
                $this->__editMailer();
                break;

            default:
                $this->__viewHome();
        }

        //css - active tab
        $this->data['vars']['css_active_tab_tickets'] = 'tab-active';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * ticket settings home page
     *
     * 
     * 
     * 
     */
    function __viewHome()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //css side menu highlight
        $this->data['vars']['css_menu_settings_side_home'] = 'side-menu-active';

        //show welcome message
        $this->notifications('wi_inner_tabs_notification', $this->data['lang']['lang_select_item_from_menu']);

    }

    /**
     * ticket settings home page
     *
     * 
     * 
     * 
     */
    function __viewDepartments()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //css side menu highlight
        $this->data['vars']['css_menu_settings_side_departments'] = 'side-menu-active';

        //visibility
        $this->data['visible']['wi_ticket_departments_add'] = 1; //add button

        //get all departments
        if ($next) {
            //email templates block
            $this->data['reg_blocks'][] = 'tickets';
            $this->data['blocks']['tickets'] = $this->tickets_departments_model->allDepartments();
            $this->data['debug'][] = $this->tickets_departments_model->debug_data;

            if ($this->data['blocks']['tickets']) {

                //show departments table
                $this->data['visible']['wi_ticket_departments_table'] = 1;

            } else {

                //no templates found
                $this->notifications('wi_inner_tabs_notification', $this->data['lang']['lang_no_results_found']);

                //halt
                $next = false;
            }

        }

    }

    /**
     * create a new department
     *
     * 
     * 
     * 
     */
    function __addDepartment()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        /** PERMISSIONS CHECK */

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/settings/tickets/view-departments');
        }

        //flow control
        $next = true;

        //validate post data
        if ($next) {
            if (!$this->__flmFormValidation('add_department')) {
                //show error
                $this->notices('error', $this->form_processor->error_message);
                $next = false;
            }
        }

        //check for duplicate department
        if ($next) {

            //database
            $result = $this->tickets_departments_model->isUnique($this->input->post('department_name'));

            //debug
            $this->data['debug'][] = $this->tickets_departments_model->debug_data;

            if (!$result) {

                //notice
                $this->notices('error', $this->data['lang']['lang_department_with_name_already_exists']);

                //halt
                $next = false;
            }

        }

        //update database
        if ($next) {

            //database
            $result = $this->tickets_departments_model->addDepartment();

            //debug
            $this->data['debug'][] = $this->tickets_departments_model->debug_data;

            if ($result) {

                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

            } else {
                //notice
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
        }

        //show notes
        $this->__viewDepartments();
    }

    /**
     * migrate tickets from one department to another
     *
     * 
     * 
     * 
     */
    function __migrateTickets()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        /** PERMISSIONS CHECK */

        //flow control
        $next = true;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            redirect('/admin/settings/tickets');
        }

        //get post data
        $old_departemnts_id = $this->input->post('old_departemnts_id');
        $new_departments_id = $this->input->post('new_departments_id');

        //validate form
        if ($next) {
            if (!$this->__flmFormValidation('migrate_tickets')) {
                //show error
                $this->notices('error', $this->form_processor->error_message);
                $next = false;
            }
        }

        //validate hidden form fields
        if ($next) {
            if (!$this->__flmFormValidation('migrate_tickets_hidden')) {
                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Migrating tickets failed: Required hidden form field ($key) missing or invalid]");
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);
                $next = false;
            }
        }

        //migrate the members to new group
        if ($next) {
            $result = $this->tickets_model->migrateTickets($old_departemnts_id, $new_departments_id);
            $this->data['debug'][] = $this->tickets_model->debug_data;
            if ($result) {
                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

            }
        }

        //finished - now list groups
        $this->__viewDepartments();
    }

    /**
     * delete all the tickets in this department
     *
     * 
     * 
     * 
     */
    function __deleteTickets()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        /** PERMISSIONS CHECK */

        //flow control
        $next = true;

        //get post data
        $department_id = $this->uri->segment(5);

        //validate form
        if ($next) {
            if (!is_numeric($department_id)) {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);
                $next = false;
            }
        }

        //migrate the members to new group
        if ($next) {
            $result = $this->tickets_model->deleteTickets($department_id);
            $this->data['debug'][] = $this->tickets_model->debug_data;
            if ($result) {
                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

            }
        }

        //finished - now list groups
        $this->__viewDepartments();
    }

    /**
     * add a department
     *
     * 
     * 
     * 
     */
    function __editDepartment()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        /** PERMISSIONS CHECK */

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/settings/tickets/view-departments');
        }

        //flow control
        $next = true;

        //validate post data
        if ($next) {
            if (!$this->__flmFormValidation('edit_department')) {
                //show error
                $this->notices('error', $this->form_processor->error_message);
                $next = false;
            }
        }

        //check for duplicate department
        if ($next) {

            //database
            $result = $this->tickets_departments_model->isUnique($this->input->post('department_name'));

            //debug
            $this->data['debug'][] = $this->tickets_departments_model->debug_data;

            if (!$result) {

                //notice
                $this->notices('error', $this->data['lang']['lang_department_with_name_already_exists']);

                //halt
                $next = false;
            }

        }

        //update database
        if ($next) {

            //database
            $result = $this->tickets_departments_model->editDepartment();

            //debug
            $this->data['debug'][] = $this->tickets_departments_model->debug_data;

            if ($result) {

                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

            } else {
                //notice
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
        }

        //show notes
        $this->__viewDepartments();
    }

    /**
     * delete all the tickets in this department
     *
     * 
     * 
     * 
     */
    function __deleteDepartment()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get post data
        $department_id = $this->uri->segment(5);

        //validate form
        if ($next) {
            if (!is_numeric($department_id)) {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);
                $next = false;
            }
        }

        //migrate the members to new group
        if ($next) {
            $result = $this->tickets_model->deleteTickets($department_id);
            $this->data['debug'][] = $this->tickets_model->debug_data;
            if ($result) {
                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

            }
        }

        //finished - now list groups
        $this->__viewDepartments();
    }

    /**
     * view mailer settings
     *
     * 
     * 
     * 
     */
    function __viewMailer()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //css side menu highlight
        $this->data['vars']['css_menu_settings_side_mailer'] = 'side-menu-active';

        //get all departments
        if ($next) {
            //email templates block
            $this->data['reg_fields'][] = 'mailer';
            $this->data['fields']['mailer'] = $this->tickets_mailer_model->getSettings();
            $this->data['debug'][] = $this->tickets_mailer_model->debug_data;

            if ($this->data['fields']['mailer']) {

                //show mailer table
                $this->data['visible']['wi_ticket_mailer_settings'] = 1;

            } else {

                //no results found
                $this->notifications('wi_inner_tabs_notification', $this->data['lang']['lang_requested_item_not_loaded']);

                //log this error

                //halt
                $next = false;
            }

        }

    }

    /**
     * edit ticket mailer settings
     *
     * 
     * 
     * 
     */
    function __editMailer()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        /** PERMISSIONS CHECK */

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/settings/tickets/view-mailer');
        }

        //flow control
        $next = true;

        //update database
        if ($next) {

            //database
            $result = $this->tickets_mailer_model->editSettings();

            //debug
            $this->data['debug'][] = $this->tickets_mailer_model->debug_data;

            if ($result) {

                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');

            } else {
                //notice
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
        }

        //show notes
        $this->__viewMailer();
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
        if ($form == 'add_department' || $form == 'edit_department') {

            //check required fields
            $fields = array('department_name' => $this->data['lang']['lang_name'], 'department_description' => $this->data['lang']['lang_description']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }
            //everything ok
            return true;
        }

        //form validation
        if ($form == 'migrate_tickets') {

            //check required fields
            $fields = array('new_departments_id' => $this->data['lang']['lang_new_department']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }
            //everything ok
            return true;
        }

        //form validation
        if ($form == 'migrate_tickets_hidden') {

            //check required fields
            $fields = array('old_departemnts_id' => $this->data['lang']['lang_old_department']);
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
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     *
     * 
     * 
     * 
     */
    function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //[all_groups]
        $data = $this->tickets_departments_model->allDepartments();
        $this->data['debug'][] = $this->tickets_departments_model->debug_data;
        $this->data['lists']['all_departments_id'] = create_pulldown_list($data, 'tickets_departments', 'id');
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

/* End of file xyz.php */
/* Location: ./application/controllers/admin/xyz.php */
