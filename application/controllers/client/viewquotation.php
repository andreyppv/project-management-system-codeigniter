<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Viewquotation related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Viewquotation extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . '/view.quotation.html';

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

        //quotation id
        $quotation_id = $this->uri->segment(4);

        /** CLIENT CHECK PERMISSION **/
        if (!$this->permissions->quotationsView($quotation_id)) {
            redirect('/client/error/permission-denied-or-not-found');
        }

        //default page titles
        $this->data['vars']['main_title'] = $this->data['lang']['lang_quotation'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-paste"></i>';

        $this->data['vars']['sub_title'] = '';
        $this->data['vars']['sub_title_icon'] = '';

        //re-route to correct method
        switch ($action) {

            case 'view':
                $this->__viewQuotation();
                break;

            default:
                $this->__viewQuotation();
        }

        //load view
        $this->__flmView('client/main');

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
