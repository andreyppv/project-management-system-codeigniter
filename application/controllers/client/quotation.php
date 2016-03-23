<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Quotation related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Quotation extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . '/quotation.html';

        //css settings
        $this->data['vars']['css_menu_quotations'] = 'open'; //menu

        //load form builder library
        $this->load->library('formbuilder');
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

        //create pulldown lists
        $this->__pulldownLists();

        //uri - action segment
        $action = $this->uri->segment(3);

        //default page titles
        $this->data['vars']['main_title'] = $this->data['lang']['lang_request_a_quotation'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-paste"></i>';

        $this->data['vars']['sub_title'] = '';
        $this->data['vars']['sub_title_icon'] = '';

        //re-route to correct method
        switch ($action) {
            case 'select':
                $this->__selectQuotation();
                break;

            case 'load':
                $this->__loadQuotation();
                break;

            case 'save':
                $this->__saveQuotation();
                break;

            case 'view':
                $this->__viewQuotation();
                break;

            default:
                $this->__selectQuotation();
        }

        //load view
        $this->__flmView('client/main');

    }

    /**
     * Load initial page that gives option to selecta particular quotation form
     *
     */
    function __selectQuotation()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //has a selection been made (post)
        if (is_numeric($this->input->post('quotation_form'))) {
            redirect('/client/quotation/load/' . $this->input->post('quotation_form'));

        } else {

            //do we have any quotation forms to display
            $result = $this->quotationforms_model->countForms('enabled');
            $this->data['debug'][] = $this->quotationforms_model->debug_data;

            if ($result > 0) {
                //visibility
                $this->data['visible']['wi_quotation_selector'] = 1;
            } else {
                //visibility
                $this->notifications('wi_notification', $this->data['lang']['lang_no_quotation_forms_available']);
            }
        }

    }

    /**
     * Take a saved quotation form from the database and process it into am HTML form
     * Disply the processed form, readyf or user inpput
     * NOTE: processing of [formbuilder.js] form data is done by library [Formbuilder.php]
     *
     */
    function __loadQuotation()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get form id
        $form_id = $this->uri->segment(4);
        $this->data['vars']['quotation_form_id'] = $form_id;

        //load 'enabled' [formbuilder.js] form data from database
        if ($next) {
            $formdata = $this->quotationforms_model->getQuotationForm($form_id, 'enabled');
            $this->data['debug'][] = $this->quotationforms_model->debug_data;
            if (!$formdata) {
                //show error
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
                //flow
                $next = false;
            }
        }

        /*------------------------------------------------------------------------------
        * [Formbuilder.php]
        *
        * Build an HTML form using the [formbuilder.js] form data/structure
        *
        *------------------------------------------------------------------------------*/
        if ($next) {

            //build the form
            $this->data['vars']['rendered_form'] = $this->formbuilder->buildform($formdata['quotationforms_code']);

            //check if built ok
            if (!$this->data['vars']['rendered_form']) {
                //show error
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
                //flow
                $next = false;
            }

        }

        //if all is ok, show the form
        if ($next) {
            //form database fields
            $this->data['vars']['quotationforms_title'] = $formdata['quotationforms_title'];

            /* -----------------------------------------------------------------------------------------
            * STORE QUOTATION FORM STRUTURE DATA
            *-------------------------------------------------------------------------------------------
            * when a user fill in a quotation form, we also save the original formbuilder.js sructure
            * this will be used to rebuild/display the save quotation later
            * (1) encode the original form data using base64_encode()
            * (2) add it to form as hidden field
            * (3) save the form structure as a session (quotations_form_data)
            * (4) compare the hidden data and session data, to make sure form is same
            *
            *-------------------------------------------------------------------------------------------*/
            //create unique id to reference the encoded form
            $form_session_code = random_string('alnum', 15);
            $this->data['vars']['form_session_code'] = $form_session_code;

            //encode the form structure
            $enconded_form_data = base64_encode($formdata['quotationforms_code']);

            //save as session
            $this->session->set_flashdata($form_session_code, $enconded_form_data);

            //visibility
            $this->data['visible']['wi_quotation_form'] = 1;

        }
    }

    /**
     * save quotation form to database and email admin
     *
     */
    function __saveQuotation()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        if (!isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('save', 'load', $this_url);
            redirect($redirect);
        }

        //flow control
        $next = true;

        //validate form in general
        if ($next) {
            if (!$this->__flmFormValidation('new_quotation')) {
                //show error
                $this->notices('error', $this->form_processor->error_message, 'html');
                //halt
                $next = false;
            }
        }

        //validation session form data & hidden form data
        if ($next) {
            $form_session_code = $_POST['form_session_code'];
            $quotations_form_data = $this->session->flashdata($form_session_code);
            if ($quotations_form_data == '') {
                //noty error
                $this->notices('error', $this->data['lang']['lang_session_timed_out_start_again'], 'html');
                //halt
                $next = false;

            } else {

                //save form data as if  $_posted
                $_POST['quotations_form_data'] = $quotations_form_data;
            }
        }

        //save in database
        if ($next) {
            $result = $this->quotations_model->saveQuotation();
            $this->data['debug'][] = $this->quotations_model->debug_data;
        }

        //results
        if ($next) {
            //thank you message
            $this->notifications('wi_notification', $this->data['lang']['lang_thank_you_for_your_quotation']);

            /*EMAIL - send admin notifications*/
            $this->__emailer('admin_notification_new_quotation');

        } else {
            //noty error
            $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty');
            //show form
            $this->__loadQuotation();
        }

    }

    /**
     * send out an email
     *
     * @param string $email email address
     */
    function __emailer($email = '', $vars = array())
    {

        //common variables
        $this->data['email_vars']['clients_company_name'] = $this->client['clients_company_name'];
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];
        $this->data['email_vars']['client_dashboard_url'] = $this->data['vars']['site_url_client'];
        $this->data['email_vars']['admin_dashboard_url'] = $this->data['vars']['site_url_admin'];

        //-------------send out email-------------------------------
        if ($email == 'admin_notification_new_quotation') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('general_notification_admin');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //specific data
            $this->data['email_vars']['email_message'] = $this->data['lang']['lang_a_new_quotation_has_been_submitted'];
            $this->data['email_vars']['email_title'] = $this->data['lang']['lang_new_quotation_request'];

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);

            //send email to multiple admins
            foreach ($this->data['vars']['mailinglist_admins'] as $email_address) {
                email_default_settings(); //defaults (from emailer helper)
                $this->email->to($email_address);
                $this->email->subject($this->data['lang']['lang_new_quotation_request']);
                $this->email->message($email_message);
                $this->email->send();
            }

        }

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

        //[all_quotation_forms]
        $data = $this->quotationforms_model->allQuotationForms('enabled');
        $this->data['debug'][] = $this->quotationforms_model->debug_data;
        $this->data['lists']['all_quotation_forms'] = create_pulldown_list($data, 'quotation_forms', 'id');

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
        if ($form == 'new_quotation') {

            //check required fields
            $fields = $_POST['required']; //required fields from formbuilder form
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

/* End of file quotation.php */
/* Location: ./application/controllers/client/quotation.php */
