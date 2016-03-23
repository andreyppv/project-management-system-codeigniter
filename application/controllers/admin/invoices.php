<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Invoices related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Invoices extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.invoices.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_invoices'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';

    }

    /**
     * This is our re-routing function and is the inital function called
     */
    function index()
    {

        /* --------------URI SEGMENTS----------------
        *
        * /admin/tasks/2/view/*.*
        * (2)->controller
        * (3)->project_id
        * (4)->router
        *
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //get project id
        $this->project_id = $this->uri->segment(3);

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;

        //PERMISSIONS CHECK - ACCESS
        //do this check before __commonAll_ProjectBasics()
        if ($this->data['vars']['my_group'] != 1) {
            if (!in_array($this->project_id, $this->data['my_projects_array'])) {
                redirect('/admin/error/permission-denied');
            }
        }

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['view_item_my_project_invoices'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //get the action from url
        $action = $this->uri->segment(4);

        //route the rrequest
        switch ($action) {

            case 'view':
                $this->__viewInvoices();
                break;

            case 'edit':
                $this->__editInvoice();
                break;

            default:
                $this->__viewInvoices();
                break;
        }

        //css - active tab
        $this->data['vars']['css_active_tab_invoices'] = 'side-menu-main-active';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * list/search for invoice items
     */
    function __viewInvoices()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/invoices/2/view/all/0
        * (2)->controller
        * (3)-> project id
        * (4)->router
        * (5)->type [all/paid/pending/overdue]
        * (6)-> offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //uri segments
        $offset = (is_numeric($this->uri->segment(6))) ? $this->uri->segment(6) : 0;
        $status = $this->uri->segment(5);

        //validate status param
        $valid_status = array(
            'new',
            'due',
            'paid',
            'overdue',
            'all');
        if (in_array($status, $valid_status)) {
            $invoice_status = $status;
        } else {
            //$invoice_status = 'all';
$invoice_status = 'paid';
        }

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'invoices';
        $this->data['blocks']['invoices'] = $this->invoices_model->viewInvoices($offset, 'search', $this->project_id, $invoice_status);
        $this->data['debug'][] = $this->invoices_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->invoices_model->viewInvoices($offset, 'count', $this->project_id, $invoice_status);
        $this->data['vars']['invoice_items_count'] = $rows_count;
        $this->data['debug'][] = $this->invoices_model->debug_data;

        //pagination
        $config = pagination_default_config();
        $config['base_url'] = site_url("admin/invoices/" . $this->project_id . "/view/$invoice_status");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 6; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //various counts for the side menu
        $this->data['vars']['count_invoices_all'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'all');
        $this->data['debug'][] = $this->invoices_model->debug_data;
        $this->data['vars']['count_invoices_open'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'all-unpaid');
        $this->data['debug'][] = $this->invoices_model->debug_data;
        $this->data['vars']['count_invoices_due'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'due');
        $this->data['debug'][] = $this->invoices_model->debug_data;
        $this->data['vars']['count_invoices_overdue'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'overdue');
        $this->data['debug'][] = $this->invoices_model->debug_data;
        $this->data['vars']['count_invoices_paid'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'paid');
        $this->data['debug'][] = $this->invoices_model->debug_data;

        //various sums for the side menu
        $this->data['vars']['sum_invoices_all'] = $this->invoices_model->sumInvoices($this->project_id, 'project', 'all');
        $this->data['debug'][] = $this->invoices_model->debug_data;
        $this->data['vars']['sum_invoices_open'] = $this->invoices_model->sumInvoices($this->project_id, 'project', 'open');
        $this->data['debug'][] = $this->invoices_model->debug_data;
        $this->data['vars']['sum_invoices_due'] = $this->invoices_model->sumInvoices($this->project_id, 'project', 'due');
        $this->data['debug'][] = $this->invoices_model->debug_data;
        $this->data['vars']['sum_invoices_overdue'] = $this->invoices_model->sumInvoices($this->project_id, 'project', 'overdue');
        $this->data['debug'][] = $this->invoices_model->debug_data;
        $this->data['vars']['sum_invoices_paid'] = $this->invoices_model->sumInvoices($this->project_id, 'project', 'paid');
        $this->data['debug'][] = $this->invoices_model->debug_data;
        $this->data['vars']['sum_invoices_part_paid'] = $this->invoices_model->sumInvoices($this->project_id, 'project', 'partpaid');
        $this->data['debug'][] = $this->invoices_model->debug_data;

        //side menu
        $this->data['vars']["css_menu_side_invoices_$invoice_status"] = 'side-menu-active';

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blocks']['invoices'])) {
            $this->data['visible']['wi_invoices_table'] = 1;
        } else {
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     * edit invoice basic details
     *
     */
    function __editInvoice()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            redirect('/admin/error/not-allowed');
        }

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['edit_item_my_project_invoices'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //validate form & display any errors
        if (!$this->__flmFormValidation('edit_invoice')) {

            //show error
            $this->notices('error', $this->form_processor->error_message);

            //halt
            $next = false;

        }

        //validate hidden fields
        if ($next) {

            //array of hidden fields and their check type
            $hidden_fields = array('invoices_id' => 'numeric');

            //loop through and validate each hidden field
            foreach ($hidden_fields as $key => $value) {

                if (($value == 'numeric' && !is_numeric($_POST[$key])) || ($value == 'string' && $_POST[$key] == '')) {

                    //log this error
                    log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Adding new message failed: Required hidden form field ($key) missing or invalid]");

                    //show error
                    $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                    //halt
                    $next = false;
                }
            }
        }

        //validate dates are not mis-matched
        if ($next) {

            if (strtotime($this->input->post('invoices_due_date')) < strtotime($this->input->post('invoices_date'))) {
                //show error
                $this->notices('error', $this->data['lang']['lang_due_date_cannot_be_behind_the_invoice_date']);

                //halt
                $next = false;
            }
        }

        //edit database record
        if ($next) {

            if ($this->invoices_model->editInvoice()) {
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);
            } else {

                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }

        }

        //load invoice view
        if ($next) {
            $this->__viewInvoices();
        }
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
        if ($form == 'edit_invoice') {

            //check required fields
            $fields = array('invoices_date' => $this->data['lang']['lang_invoice_date'], 'invoices_due_date' => $this->data['lang']['lang_due_date']);
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

/* End of file invoices.php */
/* Location: ./application/controllers/admin/invoices.php */