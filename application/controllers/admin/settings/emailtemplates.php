<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * class for perfoming all Emailtemplates Settings related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Emailtemplates extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.emailtemplates.html';
        $this->data['vars']['css_menu_heading_settings'] = 'heading-menu-active'; //menu
        $this->data['vars']['css_menu_settings'] = 'open'; //menu
        
        //css settings
        $this->data['vars']['css_menu_topnav_settings'] = 'nav_alternative_controls_active'; //menu

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
    public function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //uri - action segment
        $action = $this->uri->segment(5);

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_settings_email_templates'];

        //re-route to correct method
        switch ($action) {

            case 'view':
                $this->__viewSettings();
                break;

            case 'edit':
                $this->__editSettings();
                break;

            case 'restore-default':
                $this->__restoreDefaultSettings();
                break;

            default:
                $this->__viewSettings();
        }

        //css - active tab
        $this->data['vars']['css_active_tab_emailtemplates'] = 'tab-active';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * load general settings
     *
     * 
     * 
     * 
     */
    function __viewSettings()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get uri segements
        $template_id = $this->uri->segment(4);

        //get type or set to 'admin'
        $template_type = ($this->uri->segment(6) == '') ? 'admin' : $this->uri->segment(6);

        //set some global data
        $this->data['vars']['template_id'] = $template_type; //set to data
        $this->data['vars']['template_type'] = $template_type; //set to data

        // pull all templates
        if ($next) {
            //email templates block
            $this->data['reg_blocks'][] = 'emailtemplates';
            $this->data['blocks']['emailtemplates'] = $this->settings_emailtemplates_model->allTemplates($template_type);
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            if (!$this->data['blocks']['emailtemplates']) {
                //no templates found
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_templates_found']);

                //halt
                $next = false;
            } else {
                //show edit area
                $this->data['visible']['wi_emailtemplates_settings'] = 1;
            }

        }

        //show welcome
        if ($next) {

            if ($template_id == 0) {

                //show welcome
                $this->notifications('wi_inner_tabs_notification', $this->data['lang']['lang_select_item_to_edit']);

                //halt
                $next = false;
            }

        }

        //load particular template
        if ($next) {
            if ($template_id > 0) {

                //email template field
                $this->data['reg_fields'][] = 'emailtemplate';
                $this->data['fields']['emailtemplate'] = $this->settings_emailtemplates_model->getMessage($template_id);
                $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

                if (!$this->data['fields']['emailtemplate']) {
                    //error loading template
                    $this->notifications('wi_inner_tabs_notification', $this->data['lang']['lang_requested_item_not_loaded']);

                    //halt
                    $next = false;

                } else {

                    //show edit area
                    $this->data['visible']['wi_emailtemplates_settings'] = 1;
                    $this->data['visible']['wi_edit_table'] = 1;
                }
            }

        }

    }

    /**
     * edit settings
     *
     * 
     * 
     * 
     */
    function __editSettings()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('edit', 'view', $this_url);
            redirect($redirect);
        }

        //validate form & display any errors
        if ($next) {
            if (!$this->__flmFormValidation('edit_settings')) {

                //show error
                $this->notices('error', $this->form_processor->error_message);
                $next = false;
            }
        }

        //edit settings
        if ($next) {
            $result = $this->settings_emailtemplates_model->editSettings();
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;
            if ($result) {
                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);
            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);
            }
        }

        //show task page
        $this->__viewSettings();
    }

    /**
     * restore settings_general data to system default values.
     *
     * 
     * 
     * 
     */
    function __restoreDefaultSettings()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get uri segements
        $template_id = $this->uri->segment(4);

        //edit settings
        if ($next) {
            $result = $this->settings_emailtemplates_model->restoreDefaultSettings($template_id);
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;
            if ($result) {
                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);
            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);
            }
        }

        //show task page
        $this->__viewSettings();
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
        if ($form == 'edit_settings') {

            //check required fields
            $fields = array('message' => $this->data['lang']['lang_message'], 'subject' => $this->data['lang']['lang_subject']);
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
