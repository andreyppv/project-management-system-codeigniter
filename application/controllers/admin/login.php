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
    public $next = true; //flow control
    public $data = array(); //mega array passed to TBS

    /**
     * Initiates any of the following:
     * 
     */
    public function __construct()
    {

        parent::__construct();

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //set default template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'login.html';

    }

    /**
     * This is our re-routing function and is the inital function called
     */
    public function index()
    {
//if (isset($_COOKIE['ci_session'])){ header("Location: http://pms.isodeveloper.com/admin/home"); exit; }
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            default:
            case 'signin':
                $this->__indexLogin();
                break;

            case 'reminder':
                $this->__indexResetPassword();
                break;

            case 'logout':
                $this->__flmLogOut();
                break;

            case 'passwordreset':
                $this->__flmPasswordReset();
                break;
        }
    }

    /**
     * Initiates any of the login process 
     */
    protected function __indexLogin()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //set widget visibilty
        $this->data['visible']['wi_login_form'] = 1;

        //load login method
        if ($this->auth->loggedin()) {
            //start for processing
            $this->__flmAutoProcess();
        }
        elseif ($this->input->post('submit')) {

            //start for processing
            $this->__flmLoginProcess();
        } else {

            //delete any sessions
            $this->session->sess_destroy();

            //send show login form
            $this->__flmView('admin/main');
        }

    }

    /**
     * Logout the user
     */
    protected function __flmLogOut()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
        //Auth Lib logout
        $this->auth->logout();

        $_COOKIE = array();
        //delete all session data
        $this->session->sess_destroy();

        //Auth Lib logout
        $this->auth->logout();

        //redirect to login page
        redirect('admin/login');

    }

    /**
     * Initiates reset password process
     */
    protected function __indexResetPassword()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //set widget visibilty
        $this->data['visible']['wi_login_form'] = 1;

        //load restet first step
        if ($this->input->post('submit')) {
            $this->__flmPasswordReminder();
        } else {
            $this->__flmView('admin/main');
        }
    }

    /**
     * processes auto login form
     */
    protected function __flmAutoProcess()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //__________validate form__________
        $id = $this->auth->userid();

        if ($id) {
            //get results from checkLogins
            $results = $this->teamprofile_model->checkLogins($id);

            //login was successfuk
            if ($results) {
                //takes steps for successful login
                $this->__flmLoginSuccessful($results);
            }
        }

        //MODEL DEGUG::
        $this->data['debug'][] = $this->teamprofile_model->debug_data;

        //VIEW::
        $this->__flmView('admin/main');

    } //END

    /**
     * processes submitted login form
     */
    protected function __flmLoginProcess()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //__________validate form__________
        if ($this->__flmFormValidation('login')) {

            //get results from checkLogins
            $results = $this->teamprofile_model->checkLogins();

            //login was successfuk
            if ($results) {

                //takes steps for successful login
                $this->__flmLoginSuccessful($results);

            } else {

                //show login form with error
                $this->notices('error', $this->data['lang']['lang_incorrect_login_details']);
            }

        }

        //MODEL DEGUG::
        $this->data['debug'][] = $this->teamprofile_model->debug_data;

        //VIEW::
        $this->__flmView('admin/main');

    } //END

    /**
     * user has managed to login set sessions and redirect
     */
    protected function __flmLoginSuccessful($results = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //set sessions
        $this->__flmLoginSession($results);

        //redirect to home page
        redirect('admin/home');

    }

    /**
     * checks users email is valid and send a passowrd reset link via email
     */
    protected function __flmPasswordReminder()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //----prevent direct access----------------------------------------------------------
        if (!isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('/reminder', '', $this_url);
            redirect($redirect);
        }

        //get the 'POST' data
        $team_profile_email = $this->input->post('my_emailaddress');

        //check if email address exists
        if ($this->teamprofile_model->checkRecordExists('team_profile_email', $team_profile_email)) {

            //update database with a new reset code
            $random_code = random_string('unique');

            if ($this->teamprofile_model->resetPasswordSetup($team_profile_email, $random_code)) {

                /*----------------------------------SEND EMAIL------------------------------------------*/
                //get members details
                $member = $this->teamprofile_model->getDetailsByEmail($team_profile_email);
                $this->data['debug'][] = $this->teamprofile_model->debug_data;

                //email vars
                $password_reset_link = site_url() . 'admin/login/passwordreset/' . $random_code;
                $team_profile_full_name = $member['team_profile_full_name'];

                //email vars
                $email_vars = array(
                    'team_profile_full_name' => $team_profile_full_name,
                    'password_reset_link' => $password_reset_link,
                    'team_profile_email' => $team_profile_email);

                //send email
                $this->__emailer($email = 'password_reset_start', $email_vars);
                /*---------------------------------------------------------------------------------------*/

                //show success
                $this->notices('success', $this->data['lang']['lang_we_have_sent_you_an_email_with_instructions']);

            } else {

                //show error
                $this->notices('error', $this->data['lang']['lang_an_error_has_occurred']);

            }

        } else {

            //show login form with error
            $this->notices('error', $this->data['lang']['lang_no_user_with_that_email_address_was_found']);

        }

        //MODEL DEGUG::
        $this->data['debug'][] = $this->teamprofile_model->debug_data;

        //VIEW::
        $this->__flmView('admin/main');
    }

    /**
     * actual resetting of password, after clicking link in email
     */
    protected function __flmPasswordReset()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //set widget visibilty
        $this->data['visible']['wi_password_reset_form'] = 1;

        //load restet first step
        if ($this->input->post('submit')) {

            //turn post data into vars (tbs)
            $this->data['vars']['resetcode'] = $this->input->post('resetcode');

            //validate the form
            if ($this->__flmFormValidation('restpassword')) {

                //check if code has not expired
                if ($this->teamprofile_model->resetPassword()) { //DEBUG::

                    $this->data['visible']['wi_password_reset_form'] = 0;
                    $this->data['visible']['wi_login_form'] = 1;

                    //show success
                    $this->notices('success', $this->data['lang']['lang_your_password_has_been_update']);

                } else {

                    //code is invalid - show error
                    $this->notices('error', $this->data['lang']['lang_invalid_reset_code_or_code_has_expired']);

                }

            } else {

                //get error message and display it
                $this->notices('error', $this->form_processor->error_message);

            }

        } else {

            //check if resetcode is valid
            if (!$this->teamprofile_model->resetPasswordCheckCode($this->uri->segment(4))) {

                //show error
                $this->notices('error', $this->data['lang']['lang_invalid_reset_code_or_code_has_expired']);

                //hide update form
                $this->data['visible']['wi_password_reset_form'] = 0;

                //show login form
                $this->data['visible']['wi_login_form'] = 1;

            } else {

                //set resetcode in password update form and show the form
                $this->data['vars']['resetcode'] = $this->uri->segment(4);

            }
        }

        //MODEL DEGUG::
        $this->data['debug'][] = $this->teamprofile_model->debug_data;

        //VIEW::
        $this->__flmView('admin/main');
    }

    /**
     * sets session data
     */
    protected function __flmLoginSession($results = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        if (is_array($results)) {

            /** remember me unil I log out setting
             *  Library Auth
             */
            $remember = $this->input->post('rememberme') == 'on';

            $this->auth->login($results['team_profile_id'], $remember);

            //set the sessions data
            $session_data = array(
                'team_profile_id' => $results['team_profile_id'],
                'team_profile_uniqueid' => $results['team_profile_uniqueid'],
                'team_profile_full_name' => $results['team_profile_full_name'],
                'team_profile_email' => $results['team_profile_email'],
                'team_profile_groups_id' => $results['team_profile_groups_id'],
                'team_profile_avatar_filename' => $results['team_profile_avatar_filename'],
                'groups_name' => $results['groups_name']); //set the session - update coookie
            $this->session->set_userdata($session_data);
        }
    }

    /**
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    protected function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //__________validate login form__________
        if ($form == 'login') {
            if (!$this->input->post('email') || !$this->input->post('password')) {

                //show error
                $this->notices('error', $this->data['lang']['lang_fill_in_all_required_fields']);

                return false;
            } else {
                return true;
            }
        }

        //__________validate login form__________
        if ($form == 'restpassword') {

            //check passwords match
            $fields = array('new_password' => $this->data['lang']['lang_new_password'], 'confirm_password' => $this->data['lang']['lang_confirm_password']);

            if ($this->form_processor->validateFields($fields, 'matched')) {

                //if matched, check password is strong
                $fields = array('new_password' => $this->data['lang']['lang_new_password']);
                if ($this->form_processor->validateFields($fields, 'strength')) {

                    return true;

                } else {

                    return false;
                }

            } else {

                return false;
            }
        }
    }

    /**
     * send out an email
     *
     * @param string $email email address
     */
    protected function __emailer($email = '', $vars = array())
    {

        //specific passed variables
        foreach ($vars as $key => $value) {
            $this->data['email_vars'][$key] = $value;
        }

        //new client welcom email-------------------------------
        if ($email == 'password_reset_start') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('password_reset_admin');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);

            //send email
            email_default_settings(); //defaults (from emailer helper)
            $this->email->to($this->data['email_vars']['team_profile_email']);
            $this->email->subject($template['subject']);
            $this->email->message($email_message);
            $this->email->send();

        }
    }

    /**
     * loads the view
     *
     * @param string $view the view to load
     */
    protected function __flmView($view = '')
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
/* Location: ./application/controllers/admin/login.php */
