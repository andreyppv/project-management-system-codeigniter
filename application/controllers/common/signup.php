<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Signup related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Signup extends MY_Controller
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
        $this->data['template_file'] = PATHS_COMMON_THEME . 'client.signup.html';

        //load the models that we will use
        $this->load->model('teamprofile_model');

        //load libraries
        $this->load->helper('captcha');

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

        //client optional fileds
        $this->__optionalFormFieldsDisplay();

        //uri - action segment
        $action = $this->uri->segment(3);

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_signup_client_account'];

        //register field for form population
        $this->data['reg_fields'][] = 'new_client';
        $this->data['fields']['new_client'] = array();

        //re-route to correct method
        switch ($action) {
            case 'signup':
                $this->__signupForm();
                break;

            case 'create-account':
                $this->__createAccount();
                break;

            default:
                $this->__signupForm();
                break;
        }

        //load view
        $this->__flmView('common/main');

    }

    /**
     * client signup form
     *
     */
    function __signupForm()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //generate a new cptch image
        $this->data['vars']['captcha_image'] = new_captcha();

        //check if client registration is allowed
        if ($this->data['settings_general']['client_registration'] == 'no') {

            //show error
            $this->notifications('wi_notification', $this->data['lang']['lang_new_account_registration_is_disabled']);

            //halt
            $next = false;
        } else {
            //show the form
            $this->data['visible']['wi_client_signup_form'] = 1;
        }

    }

    /**
     * add new client account to database
     *
     */
    function __createAccount()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            //redirect to form instead
            redirect('common/signup');
        }

        //prefill forms with post data
        foreach ($_POST as $key => $value) {
            $this->data['fields']['new_client'][$key] = $value;
        }

        //form validation
        if (!$this->__flmFormValidation('client_signup')) {
            //show error
            $this->notices('error', $this->form_processor->error_message, 'html');
            //halt
            $next = false;
        }

        //validate optional fields
        if ($next) {
$error = '';
            for ($i = 1; $i <= 3; $i++) {

                //the field names; values; required state
                $field_name = "clients_optionalfield$i";
                $wi_field_name = "wi_clients_optionalfield$i";
                $field_required = "wi_clients_optionalfield$i" . "_required";
                $field_title = $this->data['row'][$field_name];
                //process each required field
                if ($this->data['visible'][$field_required] == 1) {
                    //is there post data
                    if ($this->input->post($field_name) == '') {
                        //error
                        $error .= "$field_title - is required <br/>"; //halt
                        $next = false;
                    }
                }

                //add field to mysql array (for use in model) if its enabled
                if ($this->data['visible'][$wi_field_name] == 1) {
                    $mysql_client_optional_fields[] = $field_name;
                }

            }

            //show error
            if (!$next) {
                $this->notices('error', $error, 'html');
            }
        }

        //validate captcha
        if ($next) {
            if (!validate_captcha($this->input->post('captcha_text'))) {
                //show error
                $this->notices('error', $this->data['lang']['lang_incorrect_security_text'], 'html');

                //generate a new cptch image
                $this->data['vars']['captcha_image'] = new_captcha();

                //halt
                $next = false;
            }
        }

        //save information to database & get the id of this new client
        if ($next) {
            $client_id = $this->clients_model->addClients();
            $this->data['debug'][] = $this->clients_model->debug_data;
            if (!$client_id) {
                //halt
                $next = false;
            }
        }

        //save user details & get the id of this new user
        if ($next) {

            //create a password (fake post)
            $_POST['client_users_password'] = random_string('alnum', 8);

            //add user to database
            $client_users_id = $this->users_model->addUser($client_id);
            $this->data['debug'][] = $this->users_model->debug_data;
            if (!$client_users_id) {
                //halt
                $next = false;
            }
        }

        //update primary contact & make this new user the primary contact
        if ($next) {
            $result = $this->users_model->updatePrimaryContact($client_id, $client_users_id);
            $this->data['debug'][] = $this->users_model->debug_data;
            if (!$result) {
                //halt
                $next = false;
            }
        }

        //results
        //all is ok
        if ($next) {

            //send email to client
            $this->__emailer('new_client_welcome_client');
            //send email to admin
            $this->__emailer('new_client_admin');

            //show login page
            $this->data['template_file'] = PATHS_CLIENT_THEME . 'login.html';
            $this->data['visible']['wi_login_form'] = 1;

            //show success message
            $this->notices('success', $this->data['lang']['lang_account_created_check_email'], 'html');

            //delete captch session - to help avoid user refreshing post
            $this->session->unset_userdata('captacha_word');

        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_an_error_has_occurred']);
        }

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
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //form validation
        if ($form == 'client_signup') {

            //check required fields
            $fields = array(
                'client_users_full_name' => $this->data['lang']['lang_full_name'],
                'client_users_email' => $this->data['lang']['lang_email_address'],
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

        //nothing specified - return false & error message
        $this->form_processor->error_message = $this->data['lang']['lang_form_validation_error'];
        return false;
    }

    /**
     * send out an email
     *
     * @param string $email email address
     */
    function __emailer($email = '', $vars = array())
    {

        //common variables
        $this->data['email_vars']['clients_company_name'] = $this->input->post('clients_company_name');
        $this->data['email_vars']['client_users_full_name'] = $this->input->post('client_users_full_name');
        $this->data['email_vars']['client_users_email'] = $this->input->post('client_users_email');
        $this->data['email_vars']['client_users_password'] = $this->input->post('client_users_password');
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];
        $this->data['email_vars']['client_dashboard_url'] = $this->data['vars']['site_url_client'];
        $this->data['email_vars']['admin_dashboard_url'] = $this->data['vars']['site_url_admin'];

        //new client welcom email-------------------------------
        if ($email == 'new_client_welcome_client') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_client_welcome_client');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);

            //send email to multiple admins
                email_default_settings(); //defaults (from emailer helper)
                $this->email->to($this->data['email_vars']['client_users_email']);
                $this->email->subject($template['subject']);
                $this->email->message($email_message);
                $this->email->send();
        }

        //new client welcom email-------------------------------
        if ($email == 'new_client_admin') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_client_admin');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);

            //send email to multiple admins
            foreach ($this->data['vars']['mailinglist_admins'] as $email_address) {
                email_default_settings(); //defaults (from emailer helper)
                $this->email->to($email_address);
                $this->email->subject($template['subject']);
                $this->email->message($email_message);
                $this->email->send();
            }
        }

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

/* End of file signup.php */
/* Location: ./application/controllers/common/signup.php */
