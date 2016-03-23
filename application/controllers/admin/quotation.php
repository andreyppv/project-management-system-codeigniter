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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'quotation.html';
        $this->data['vars']['css_submenu_quotations'] = 'style="display:block; visibility:visible;"';

        //css settings
        $this->data['vars']['css_menu_quotations'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_quotation'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-paste"></i>';

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
        $this->__commonAdmin_LoggedInCheck();

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'view':
                $this->__viewQuotation();
                break;

            case 'update':
                $this->__updateQuotation();
                break;

            default:
                $this->__viewQuotation();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * Load a quoation from the database
     *
     */
    function __viewQuotation()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //quotation id
        $quotation_id = $this->uri->segment(4);

        //validate id
        if (!is_numeric($quotation_id)) {
            $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
            //halt
            $next = false;
        }

        //get quotation
        if ($next) {
            $this->data['reg_fields'][] = 'quotation';
            $this->data['fields']['quotation'] = $this->quotations_model->getQuotation($quotation_id);
            $this->data['debug'][] = $this->quotations_model->debug_data;
            if (!$this->data['fields']['quotation']) {
                //success
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
            } else {
                //get the required data
                $theform = $this->data['fields']['quotation']['quotations_form_data'];
                $postdata = $this->data['fields']['quotation']['quotations_post_data'];
            }
        }

        //rebuild the form
        if ($next) {
            $this->data['reg_blocks'][] = 'quotationform';
            $this->data['blocks']['quotationform'] = $this->formbuilder->reBuildForm($theform, $postdata);
            $this->data['visible']['wi_quotation'] = 1;
        }

    }

    /**
     * price a quotation and emal client (optional)
     *
     */
    function __updateQuotation()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('update', 'view', $this_url);
            redirect($redirect);
        }

        //validate input
        if ($next) {
            if (!$this->__flmFormValidation('update_quotation')) {
                //show error
                $this->notices('error', $this->form_processor->error_message, 'html');
                //halt
                $next = false;
            }
        }

        //update database
        if ($next) {
            $result = $this->quotations_model->updateQuotation($this->input->post('quotations_id'));
            $this->data['debug'][] = $this->quotations_model->debug_data;
            if ($result) {
                //success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');
            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty');
                //halt
                $next = false;
            }
        }

        //send email
        if ($next) {
            if ($this->input->post('send_email') == 'yes') {
                $this->__emailer('quotation_updated', array(
                    'quotation_notes' => $this->input->post('quotations_admin_notes'),
                    'client_users_full_name' => $this->input->post('client_users_full_name'),
                    'quotation_amount' => $this->input->post('quotations_amount')));
            }
        }

        //load quotation
        $this->__viewQuotation();
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
        $this->data['email_vars']['currency_symbol'] = $this->data['settings_general']['currency_symbol'];

        //specific passed variables
        foreach ($vars as $key => $value) {
            $this->data['email_vars'][$key] = $value;
        }

        //-------------send out email-------------------------------
        if ($email == 'quotation_updated') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_quotation_client');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data; //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']); //send email
            email_default_settings(); //defaults (from emailer helper)
            $this->email->to($this->input->post('clients_email'));
            $this->email->subject($template['subject']);
            $this->email->message($email_message);
            $this->email->send();
        }

    }

    /**
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //form validation
        if ($form == 'update_quotation') {

            //check amount is numeric
            $fields = array('quotations_amount' => $this->data['lang']['lang_amount']);
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
        $this->data['controller_profiling'][] = __function__; //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']); //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file quotation.php */
/* Location: ./application/controllers/admin/quotation.php */
