<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Login related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Login extends MY_Controller
{

    //__________STANDARD VARS__________
    var $next = true; //flow control
    var $data = array(); //mega array passed to TBS

    /**
     * Initiates any of the following:
     *          - sets the default template for this controller
     *
     * 
     */
    function __construct()
    {

        parent::__construct();

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //set default template file
        $this->data['template_file'] = PATHS_CLIENT_THEME . 'login.html';

    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    function index()
    {
//if (isset($_COOKIE['ci_session'])){ header("Location: http://pms.isodeveloper.com/client/home"); exit; }
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {

            case 'signin':
                $this->__signIn();
                break;

            case 'logout':
                $this->__logOut();
                break;

            case 'reminder':
                $this->__loginReminder();
                break;

            case 'passwordreset':
                $this->__resetPassword();
                break;

            default:
                $this->__loginForm();
                break;

        }

        //load view
        $this->__flmView('client/main');

    }

    /**
     * show login form
     *
     */
    function __loginForm()
    {


        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show form
        $this->data['visible']['wi_login_form'] = 1;

    }

    /**
     * validate signin & set sessions
     *
     */
    function __signIn()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            redirect('/client/login');
        }

        //flow control
        $next = true;

        //validate form
        if ($next) {
            if (!$this->__flmFormValidation('signin')) {
                //show error
                $this->notices('error', $this->form_processor->error_message, 'noty');
                //halt
                $next = false;
            }
        }

        //validate login details & get
        if ($next) {
            $result = $this->users_model->checkLogins();
            $this->data['debug'][] = $this->users_model->debug_data;

            //did login pass
            if ($result) {
                //set the sessions data
                $session_data = array('client_users_id' => $result['client_users_id'], 'client_users_clients_id' => $result['client_users_clients_id']);
                $this->session->set_userdata($session_data);

                //redirect to home
                redirect('/client/home');

            } else {
                //login failed
                $next = false;
            }

        }

        //results
        if (!$next) {
            //show error
            $this->notices('error', $this->data['lang']['lang_incorrect_login_details'], 'noty');

            //show form
            $this->__loginForm();
        }

    }

    /**
     * email login reminder
     *
     */
    function __loginReminder()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //check if email exists
        if ($next) {
            $result = $this->users_model->checkRecordExists($this->input->post('my_emailaddress'));
            $this->data['debug'][] = $this->files_model->debug_data;

            if ($result) {

                /*-----SEND EMAIL---------------------------------------------------------------------*/
                $random_code = random_string('unique');
                $password_reset_link = site_url() . 'client/login/passwordreset/' . $random_code;

                //add code to dbase
                $this->users_model->resetPasswordSetup($this->input->post('my_emailaddress'), $random_code);
                $this->data['debug'][] = $this->users_model->debug_data;

                //email vars
                $email_vars = array(
                    'client_users_email' => $result['client_users_email'],
                    'password_reset_link' => $password_reset_link,
                    'client_users_full_name' => $result['client_users_full_name']);

                //send email
                $this->__emailer($email = 'login_reminder', $email_vars);
                /*---------------------------------------------------------------------------------------*/

                //show success
                $this->notices('success', $this->data['lang']['lang_we_have_sent_you_an_email_with_instructions'], 'noty'); //noty or html

            } else {

                //show error
                $this->notices('error', $this->data['lang']['lang_no_user_with_that_email_address_was_found'], 'noty'); //noty or html

            }
        }

        //show form
        $this->__loginForm();
    }

    /**
     * email login reminder
     *
     */
    function __resetPassword()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //show form
        $this->data['visible']['wi_login_form'] = 1;

        //verify reset code
        if ($next) {
            $user = $this->users_model->resetPasswordCheckCode($this->uri->segment(4));
            $this->data['debug'][] = $this->users_model->debug_data;
            if (!$user) {
                //reset code error
                $this->notices('error', $this->data['lang']['lang_invalid_reset_code_or_code_has_expired'], 'noty');

                //halt
                $next = false;
            }
        }

        //reset the password
        if ($next) {

            //new random password
            $new_password = random_string('alnum', 8);

            //update database
            $result = $this->users_model->resetPassword($this->uri->segment(4), $new_password);
            $this->data['debug'][] = $this->users_model->debug_data;
            if (!$result) {
                //reset code error
                $this->notices('error', $this->data['lang']['lang_error_occurred_info'], 'noty');

                //halt
                $next = false;
            }
        }

        //send email with new password
        if ($next) {
            /*-----SEND EMAIL---------------------------------------------------------------------*/
            //message
            $message = $this->data['lang']['lang_your_password_has_been_reset'] . " <b>$new_password</b>";
            //email vars
            $email_vars = array(
                'client_users_email' => $user['client_users_email'],
                'email_title' => $this->data['lang']['lang_password'],
                'addressed_to' => $user['client_users_full_name'],
                'email_message' => $message);
            //send email
            $this->__emailer($email = 'password_reset', $email_vars);
            /*---------------------------------------------------------------------------------------*/

            //show success
            $this->notices('success', $this->data['lang']['lang_your_password_has_been_updated'], 'noty');

            //notification
            $this->notifications('wi_notification', $this->data['lang']['lang_we_have_sent_you_an_email_with_instructions']);

            //hide form
            $this->data['visible']['wi_login_form'] = 0;
        }

    }

    /**
     * Logout the user
     *
     * @param	string
     */
    function __logOut()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //delete all session data
        $this->session->sess_destroy();

        //redirect to login page
        redirect('client/login');

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
        if ($form == 'signin') {

            //check required fields
            $fields = array('email' => $this->data['lang']['lang_email'], 'password' => $this->data['lang']['lang_password']);
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
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];
        $this->data['email_vars']['client_dashboard_url'] = $this->data['vars']['site_url_client'];

        //specific passed variables
        foreach ($vars as $key => $value) {
            $this->data['email_vars'][$key] = $value;
        }

        //new client welcom email-------------------------------
        if ($email == 'login_reminder') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('password_reset_client');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);

            //send email
            email_default_settings(); //defaults (from emailer helper)
            $this->email->to($this->data['email_vars']['client_users_email']);
            $this->email->subject($template['subject']);
            $this->email->message($email_message);
            $this->email->send();

        }

        //password has been reset----------------------------------------
        if ($email == 'password_reset') {
            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('general_notification_client');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;
            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);

            //send email
            email_default_settings(); //defaults (from emailer helper)
            $this->email->to($this->data['email_vars']['client_users_email']);
            $this->email->subject($this->data['lang']['lang_your_password_has_been_updated']);
            $this->email->message($email_message);

            /*Alteration*/ $this->___newMailPHP($this->data['email_vars']['client_users_email'], $this->data['lang']['lang_your_password_has_been_updated'], $email_message);
            //$this->email->send();

        }
    }

    function ___newMailPHP($to, $subject, $body){
        $headers = "From: system@isodevelopers.com\r\n";
        $headers .= "Reply-To: system@isodevelopers.com\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        $message = $body;
        mail($to, $subject, $message, $headers);
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

/* End of file login.php */
/* Location: ./application/controllers/client/login.php */