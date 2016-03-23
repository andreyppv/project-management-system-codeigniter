<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Profile related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Profile extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . '/profile.html';

        //css settings
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

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

        //client optional fileds
        $this->__optionalFormFieldsDisplay();

        //uri - action segment
        $action = $this->uri->segment(3);

        //default page titles
        $this->data['vars']['main_title'] = '';
        $this->data['vars']['main_title_icon'] = '';

        $this->data['vars']['sub_title'] = $this->data['lang']['lang_company_profile'];
        $this->data['vars']['sub_title_icon'] = '<i class="icon-briefcase"></i>';

        //re-route to correct method
        switch ($action) {

            case 'edit-profile':
                $this->__editProfile();
                break;

            default:
                $this->__clientProfile();
        }

        //load view
        $this->__flmView('client/main');

    }

    /**
     * show the clients profile
     *
     */
    function __clientProfile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //get clients profile
        $this->data['reg_fields'][] = 'client';
        $this->data['fields']['client'] = $this->clients_model->clientDetails($this->client_id);
        $this->data['debug'][] = $this->clients_model->debug_data;

        //show error if data loading error
        if (!$this->data['fields']['client']) {
            //show error
            redirect('/client/error');
        }

    }

    /**
     * hello world
     *
     */
    function __editProfile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //flow control
        $next = true;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('edit-profile', 'view', $this_url);
            redirect($redirect);
        }

        //form validation
        if (!$this->__flmFormValidation('edit_profile')) {
            //show error
            $this->notices('error', $this->form_processor->error_message, 'html');
            //halt
            $next = false;
        }

        //form validation
        if (!$this->__flmFormValidation('edit_profile_hidden')) {
            //show error
            $this->notices('error', $this->form_processor->error_message, 'html');
            //log this error
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Edit client profile failed: missing hidden fields]"); //halt
            $next = false;
        }

        //validate posted client id and current client id
        if ($next) {
            if ($this->input->post('clients_id') != $this->client_id) {
                redirect('/client/error');
            }
        }

        //validate optional fields
        if ($next) {
            $error = '';
            $mysql_client_optional_fields = array();
            for ($i = 1; $i <= 3; $i++) {

                //the field names; values; required state
                $field_name = "clients_optionalfield$i";
                $wi_field_name = "wi_clients_optionalfield$i";
                $field_required = "wi_clients_optionalfield$i" . "_required";
                //process each required field
                if (isset($this->data['visible'][$field_required]) && $this->data['visible'][$field_required] == 1) {
                    //is there post data
                    if ($this->input->post($field_name) == '') {
                        //halt
                        $next = false;
                    }
                }

                //add field to mysql array (for use in model) if its enabled
                if (isset($this->data['visible'][$wi_field_name]) && $this->data['visible'][$wi_field_name] == 1) {
                    $mysql_client_optional_fields[] = $field_name;
                }

            }

            //show error
            if (!$next) {
                $this->notices('error', $this->data['lang']['lang_fill_in_all_required_fields'], 'html');
            }
        }

        //update mysql
        if ($next) {
            $result = $this->clients_model->editProfile($this->client_id, $mysql_client_optional_fields);
            $this->data['debug'][] = $this->clients_model->debug_data;

            //did this update of
            if ($result) {
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');
            } else {
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'html');
            }
        }

        //show profile
        $this->__clientProfile();
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

        if ($form == 'edit_profile') {

            //check required fields
            $fields = array(
                'clients_company_name' => $this->data['lang']['lang_company_name'],
                'clients_address' => $this->data['lang']['lang_address'],
                'clients_city' => $this->data['lang']['lang_city'],
                'clients_state' => $this->data['lang']['lang_state'],
                'clients_zipcode' => $this->data['lang']['lang_zip_code'],
                'clients_country' => $this->data['lang']['lang_country'],
                'clients_website' => $this->data['lang']['lang_website']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }
            //everything ok
            return true;
        }

        if ($form == 'edit_profile_hidden') {

            //check required fields
            $fields = array('clients_id' => $this->data['lang']['lang_client_id']);
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
     * loads [client table] optional fields and makes them TBS visible in whatever form is using the,
     * uses the [clients_optionalfield_visibility] helper to set visibility in ($this-data['visible']) array
     * also sets the [labels] to use in the form as ($this->data['row']['clients_optionalfield1'])
     */
    function __optionalFormFieldsDisplay()
    {

        //check optional form fields & and set visibility of form field widget
        $optional_fields = $this->clientsoptionalfields_model->optionalFields('enabled');
        $this->data['debug'][] = $this->clientsoptionalfields_model->debug_data;
        clients_optionalfield_visibility($optional_fields);
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

/* End of file profile.php */
/* Location: ./application/controllers/client/profile.php */
