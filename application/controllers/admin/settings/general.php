<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * class for perfoming all General Settings related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class General extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.general.html';

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

        //uri - action segment
        $action = $this->uri->segment(4);

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_settings_general'];

        //re-route to correct method
        switch ($action) {
            case 'edit':
                $this->__editSettings();
                break;

            case 'view':
                $this->__viewSettings();
                break;

            case 'restore-default':
                $this->__restoreDefaultSettings();
                break;

            default:
                $this->__viewSettings();
        }

        //css - active tab
        $this->data['vars']['css_active_tab_general'] = 'tab-active';

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

        //load settings
        if ($next) {

            //get the current data
            $this->data['reg_fields'][] = 'general';
            $this->data['fields']['general'] = $this->settings_general_model->getSettings();
            $this->data['debug'][] = $this->settings_general_model->debug_data;

            if ($this->data['fields']['general']) {

                //show form
                $this->data['visible']['wi_general_settings'] = 1;

            } else {
                //show error
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']);
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

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/settings/general/view');
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
            $result = $this->settings_general_model->editSettings();
            $this->data['debug'][] = $this->settings_general_model->debug_data;
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

        //edit settings
        if ($next) {
            $result = $this->settings_general_model->restoreDefaultSettings();
            $this->data['debug'][] = $this->settings_general_model->debug_data;
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
            $fields = array(
                'currency_code' => $this->data['lang']['lang_currency_code'],
                'currency_symbol' => $this->data['lang']['lang_currency_symbol'],
                'dashboard_title' => $this->data['lang']['lang_dashboard_title'],
                'date_format' => $this->data['lang']['lang_date_format'],
                'language' => $this->data['lang']['lang_language'],
                'messages_limit' => $this->data['lang']['lang_messages_limit'],
                'timeline_limit' => $this->data['lang']['lang_timeline_limit'],
                'results_limit' => $this->data['lang']['lang_general_limits'],
                'theme' => 'Theme',
                'show_information_tips' => $this->data['lang']['lang_information_tips'],
                'client_registration' => $this->data['lang']['lang_allow_client_registration'],
                'notifications_display_duration' => $this->data['lang']['lang_notifications_duration']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check numeric fields
            $fields = array(
                'messages_limit' => $this->data['lang']['lang_messages_limit'],
                'timeline_limit' => $this->data['lang']['lang_timeline_limit'],
                'results_limit' => $this->data['lang']['lang_general_limits']);
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

/* End of file xyz.php */
/* Location: ./application/controllers/admin/xyz.php */
