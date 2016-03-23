<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Invoice related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Invoice extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.invoice.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_invoice'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';

        //view this in wide standalone page
        if ($this->uri->segment(6) == 'standard') {
            $this->data['template_file'] = PATHS_ADMIN_THEME . 'view.invoice.html';

            //show 'invoices' menu instead of projects
            $this->data['vars']['css_submenu_invoices'] = 'style="display:block; visibility:visible;"';
            $this->data['vars']['css_menu_invoices'] = 'open'; //menu

            //hide
            $this->data['vars']['css_menu_projects'] = '';
            $this->data['vars']['css_submenu_projects'] = '';
        }

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

        //view this in wide standalone page
        if ($this->uri->segment(6) == 'standard') {
            $this->data['vars']['main_title'] = $this->data['fields']['project_details']['projects_title'];
            $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';
        }

        //create pulldown lists
        $this->__pulldownLists();

        //get the action from url
        $action = $this->uri->segment(4);

        //route the rrequest
        switch ($action) {

            case 'view':
                $this->__viewInvoice();
                break;

            case 'edit':
                $this->__editInvoice();
                break;

            case 'add-invoice':
                $this->__addInvoice();
                break;

            case 'add-invoice-item':
                $this->__addInvoiceItem();
                break;

            case 'add-invoice-payment':
                $this->__addInvoicePayment();
                break;

            case 'delete-invoice-item':
                $this->__deleteInvoiceItem();
                break;

            case 'publish-invoice':
                $this->__publishInvoice();
                break;

            case 'pdf':
                $this->__pdfInvoice();
                break;

            default:
                $this->__viewInvoice();
                break;
        }

        //css - active tab
        $this->data['vars']['css_active_tab_invoices'] = 'side-menu-main-active';

        //load view
        if ($action != 'pdf') {
            $this->__flmView('admin/main');
        }
    }

    /**
     * view invoices
     */
    function __viewInvoice($id = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get invoice id
        if ($next) {
            if (is_numeric($id)) {
                $invoice_id = $id;
            } else {
                $invoice_id = $this->uri->segment(5);
            }
        }

        /*--------------------------------------------------------------------
        * REFRESH THIS INVOICE
        *-------------------------------------------------------------------*/
        if ($next) {
            $this->refresh->refreshSingleInvoice($invoice_id);
            $this->data['debug'][] = $this->refresh->debug_data;
        }

        //check invoice id again
        if ($next) {
            if (!is_numeric($invoice_id)) {
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_requested_item_not_loaded']);
                $next = false;
            }
        }

        /*
        * First recalcluate invoice
        * to ensure it is mathematically correct
        * incase it was edited outside the system
        * or a previous edit did not complete
        */
        if ($next) {
            if (!$this->__recalculateInvoice($invoice_id)) {
                //invoice could not be loaded
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_requested_item_not_loaded']);

                //halt
                $next = false;
            }
        }

        //get invoice details
        if ($next) {

            //register field
            $this->data['reg_fields'][] = 'invoice';

            if ($this->data['fields']['invoice'] = $this->invoices_model->getInvoice($invoice_id)) {

                //show invoice section
                $this->data['visible']['wi_show_invoice'] = 1;

            } else {
                //invoice could not be loaded
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_requested_item_not_loaded']);

                //halt
                $next = false;
            }
        }
        $this->data['debug'][] = $this->invoices_model->debug_data;

        //get invoice products/items
        if ($next) {

            $this->data['reg_blocks'][] = 'products';
            $this->data['blocks']['products'] = $this->invoice_products_model->getInvoiceProducts($invoice_id);
            $this->data['debug'][] = $this->invoice_products_model->debug_data;

            //do we have products/items on this invoice
            if (count($this->data['blocks']['products']) > 0) {
                //did we charge tax
                if ($this->data['fields']['invoice']['invoices_tax_rate'] > 0) {
                    $this->data['visible']['wi_invocie_tax'] = 1;
                }
            } else {
                //nothing found
                $this->data['visible']['wi_no_invoice_items_found'] = 1;
            }

        }

        //get invoice payments
        if ($next) {

            $this->data['reg_blocks'][] = 'payments';
            $this->data['blocks']['payments'] = $this->payments_model->getInvoicePayments($invoice_id);
            $this->data['debug'][] = $this->payments_model->debug_data;

            if (!$this->data['blocks']['payments']) {
                //no payments found
                $this->data['visible']['wi_sidemenu_no_payments'] = 1;

                $this->data['vars']['invoice_payments_sum'] = 0;
            } else {

                //show payments
                $this->data['visible']['wi_sidemenu_payments_table'] = 1;

                //sum payments
                $this->data['vars']['invoice_payments_sum'] = $this->payments_model->sumInvoicePayments($invoice_id);
                $this->data['debug'][] = $this->payments_model->debug_data;
            }

            //balance due
            $this->data['vars']['invoice_balance_due'] = $this->data['fields']['invoice']['invoices_amount'] - $this->data['vars']['invoice_payments_sum'];

        }

        //Invoice ID for display purposes
        if ($next) {
            if ($this->data['fields']['invoice']['invoices_custom_id'] != '') {
                //show custom invoice id
                $this->data['vars']['invoice_id_display'] = $this->data['lang']['lang_invoice'] . ": " . $this->data['fields']['invoice']['invoices_custom_id'];
            } else {
                //show standard auto-incrementa invoice id
                $this->data['vars']['invoice_id_display'] = $this->data['lang']['lang_invoice'] . ": #" . $this->data['fields']['invoice']['invoices_id'];
            }
        }

        //load invoice
        if ($next) {
            //show invoice section
            $this->data['vars']['wi_show_invoice'] = 1;
        }

    }

    /**
     * recalculate an
     *
     * @param numeric $id: invoice id
     */
    function __recalculateInvoice($id = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //-----validate input------------------------------------------------
        if (!is_numeric($id)) {
            return;
        }

        //----pull out invoice details from database--------------------------
        $result = $this->invoices_model->getInvoice($id);
        $this->data['debug'][] = $this->files_model->debug_data;

        if (!$result) {
            return false;
        } else {
            //get some needed details
            $tax_rate = $result['invoices_tax_rate'];
        }

        //----recalculate invoice items totals (quantity * rate)--------------
        $result = $this->invoices_model->recalculateInvoiceItems($id);
        $this->data['debug'][] = $this->invoices_model->debug_data;
        if (!$result) {
            //return false
            return false;
        }

        //----get new invoice total before tax---------------------------------
        $invoice_pretax_amount = $this->invoices_model->getInvoicePretaxTotal($id);
        $this->data['debug'][] = $this->invoices_model->debug_data;
        if (!is_numeric($invoice_pretax_amount)) {
            //return false
            return false;
        }

        //-----calculate new invoice totals-----------------------------------
        if ($tax_rate > 0) {
            $tax_amount = ($invoice_pretax_amount * $tax_rate) / 100;
            $new_total = $invoice_pretax_amount + $tax_amount;
        } else {
            $tax_amount = 0;
            $new_total = $invoice_pretax_amount;
        }

        //-----update invoice with new totals-----------------------------------
        if (!is_numeric($new_total)) {
            //return false
            return false;

        } else {
            //update the invoice total
            $result = $this->invoices_model->updateInvoiceTotal($id, $invoice_pretax_amount, $tax_amount, $new_total);
            $this->data['debug'][] = $this->invoices_model->debug_data;
            if (!$result) {
                //return false
                return false;

            }
        }

        //all was ok, return true
        return true;

    }

    /**
     * add a new invoice
     */
    function __addInvoice()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['add_item_my_project_invoices'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //flow control
        $next = true;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            redirect('/admin/error/not-allowed');
        }

        //validate form & display any errors
        if (!$this->__flmFormValidation('add_invoice')) {

            //show error
            $this->notices('error', $this->form_processor->error_message);

            //error
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']);

            //halt
            $next = false;

        }

        //validate hidden fields
        if ($next) {

            //array of hidden fields and their check type
            $hidden_fields = array(
                'invoices_created_by_id' => 'numeric',
                'invoices_clients_id' => 'numeric',
                'invoices_project_id' => 'numeric');

            //loop through and validate each hidden field
            foreach ($hidden_fields as $key => $value) {

                if (($value == 'numeric' && !is_numeric($_POST[$key])) || ($value == 'string' && $_POST[$key] == '')) {

                    //log this error
                    log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Adding new message failed: Required hidden form field ($key) missing or invalid]");

                    //show error
                    $this->notices('error', $this->data['lang']['lang_error_occurred_info']);

                    //error
                    $this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']);

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

                //error
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
        }

        //add to database
        if ($next) {

            if ($new_invoice_id = $this->invoices_model->addInvoice()) {
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

                //events tracker
                $this->__eventsTracker('new-invoice', array('target_id' => $new_invoice_id));

            } else {

                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                //error
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }

        }

        //load invoice view
        if ($next) {
            $this->__viewInvoice($new_invoice_id);
        }
    }

    /**
     * add an item to an invoice
     */
    function __addInvoiceItem()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['edit_item_my_project_invoices'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //flow control
        $next = true;

        //invoice id
        $invoice_id = $this->input->post('invoice_products_invoice_id');

        //prevent direct access
        if (!isset($_POST['submit'])) {

            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('add-invoice-item', 'view', $this_url);
            redirect($redirect);
        }

        //validate hidden fields
        if ($next) {

            //array of hidden fields and their check type
            $hidden_fields = array('invoice_products_invoice_id' => 'numeric', 'invoice_products_project_id' => 'numeric');

            //loop through and validate each hidden field
            foreach ($hidden_fields as $key => $value) {

                if (($value == 'numeric' && !is_numeric($_POST[$key])) || ($value == 'string' && $_POST[$key] == '')) {

                    //log this error
                    log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Adding new invoice item failed: Required hidden form field ($key) missing or invalid]");

                    //show error
                    $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                    //error
                    $this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']);

                    //halt
                    $next = false;
                }
            }
        }

        //add to invoice database
        if ($next) {

            if ($result = $this->invoice_products_model->addItem($invoice_id)) {
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);
            } else {

                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                //error
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
        }

        //add to invoice items database (if new item)
        if ($next) {
            if ($this->input->post('invoice_items_id') == 0 && $this->input->post('save_new_item') == 'on') {
                //set/imitate "post" vars
                $_POST['invoice_items_title'] = $this->input->post('invoice_products_title');
                $_POST['invoice_items_description'] = $this->input->post('invoice_products_description');
                $_POST['invoice_items_amount'] = $this->input->post('invoice_products_rate');

                //save item
                $this->invoice_items_model->addItem();
                $this->data['debug'][] = $this->files_model->debug_data;
            }
        }

        //load invoice view
        if ($next) {
            $this->__viewInvoice($invoice_id);
        }
    }

    /**
     * delete an item from an invoice
     */
    function __deleteInvoiceItem()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['edit_item_my_project_invoices'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //flow control
        $next = true;

        //invoice id
        $invoice_id = $this->uri->segment(5);
        $item_id = $this->uri->segment(6);

        //check invoice id and item id
        if ($next) {
            if (!is_numeric($invoice_id) || !is_numeric($item_id)) {
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_requested_item_not_loaded']);
                $next = false;
            }
        }

        //delete invoice item from database
        if ($next) {
            $result = $this->invoice_products_model->deleteItem($item_id);
            $this->data['debug'][] = $this->invoice_products_model->debug_data;
            if ($result) {
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);
            } else {

                //error
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']); //log this error

                //halt
                $next = false;
            }

        }

        //load invoice view
        if ($next) {
            $this->__viewInvoice($invoice_id);
            return true;
        } else {
            return false;
        }
    }

    /**
     * publish a new invoice and email pdf invoice
     *
     * @param string $type are we resending or publishing for the first time
     */
    function __publishInvoice($type = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['edit_item_my_project_invoices'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //flow control
        $next = true;

        //get invoice id
        $invoice_id = $this->uri->segment(5);

        //get publish type
        $type = $this->uri->segment(6);

        //are we publishing first time or resending
        if ($next) {
            if (!in_array($type, array(
                'first',
                'resend',
                'reminder'))) {
                //set flash notice
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
        }

        //get the invoice and save as PDF in temp folder
        if ($next) {

            $invoice = $this->invoices_model->getInvoice($invoice_id);
            $this->data['debug'][] = $this->invoices_model->debug_data;

            if ($invoice) {

                //save invoice as pdf to temp folder
                $pdfinvoice = $this->__savePDFInvoice($invoice_id, $invoice['invoices_unique_id']);

                //-----check if file now exist------
                if (is_file($pdfinvoice)) {

                    //update invoice as published
                    if ($type == 'first') {
                        $this->invoices_model->updateInvoiceStatus($invoice_id, 'due');
                        $this->data['debug'][] = $this->invoices_model->debug_data;
                    }

                } else {

                    //set flash notice
                    $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);

                    //halt
                    $next = false;

                }

            } else {

                //set flash notice
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;

            }

        }

        //------send out an email-----------
        if ($next) {

            //-send emails to client & admin----------------

            //retrieve some details from database
            $primary_user = $this->users_model->clientPrimaryUser($invoice['clients_id']);
            $this->data['debug'][] = $this->users_model->debug_data;

            //set to vars
            $client_users_full_name = $primary_user['client_users_full_name'];
            $client_users_email = $primary_user['client_users_email'];
            $clients_company_name = $invoice['clients_company_name'];
            $invoices_id = $invoice['invoices_id'];
            $invoice_standard_terms = $this->data['settings_invoices']['settings_invoices_notes'];

            //which email template to use
            switch ($type) {

                case 'first':
                    $email_template = 'new_invoice_client';
                    $email_subject = $this->data['lang']['lang_invoice_has_been_created'];
                    break;

                case 'resend':
                    $email_template = 'new_invoice_client';
                    $email_subject = $this->data['lang']['lang_your_invoice'];
                    break;

                case 'reminder':
                    $email_template = 'invoice_reminder_client';
                    $email_subject = $this->data['lang']['lang_invoice_reminder'];
                    break;

            }

            //add vars to array
            $email_vars = array(
                'client_users_full_name' => $client_users_full_name,
                'client_users_email' => $client_users_email,
                'clients_company_name' => $clients_company_name,
                'invoices_id' => $invoice_id,
                'invoice_standard_terms' => $invoice_standard_terms,
                'email_subject' => $email_subject,
                'email_template' => $email_template,
                'pdfinvoice' => $pdfinvoice);

            //send email
            $this->__emailer('client_invoice', $email_vars);

            //-send emails to client & admin----------------

            //update last emailed
            $this->invoices_model->updateLastEmailed($invoice_id);

            //set flash notice
            $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);
        }

        //delete any files
        @unlink($pdfinvoice);

        //redirect back to view the invoice
        redirect('/admin/invoice/' . $this->project_id . '/view/' . $invoice_id);

    }

    /**
     * Save an invoice as a pdf in the /temp folder
     * @param numeric $invoice_id
     * @param	string $invoice_unique_id
     * @return	string $file path to new file 
     */
    function __savePDFInvoice($invoice_id = '', $invoice_unique_id)
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_COMMON_THEME . 'project.invoice.pdf.html';

        //invoice is invalid
        if (!is_numeric($invoice_id) || $invoice_unique_id == '') {
            return false;
        }

        //verify invoce exists
        $invoice = $this->invoices_model->getInvoice($invoice_id);
        $this->data['debug'][] = $this->invoices_model->debug_data;
        if (!$invoice) {
            return false;
        }

        //--GENERATE INVOICE AND CAPTURE OUTPUT--------------------------
        //reduce error reporting to only critical
        @error_reporting(E_ERROR);

        //turn off codeigniter profiler (which may be on in MY_Controller) during debug mode
        $this->output->enable_profiler(false);

        $this->config->set_item('debug_mode', '0');

        //generate the invoice view as normal, but buffer it to variable ($html)
        ob_start();

        $this->__viewInvoice($invoice_id);
        $this->__flmView('admin/main');
        $html = ob_get_contents();
        ob_end_clean();

        /*------------------------------- GENERATE PDF------- (mpdf class)------------------------/
        * Take generated html and passes it to mpdf class pdf output is saved in variable $pdf
        *
        *----------------------------------------------------------------------------------------*/

        $filename = "invoice_$invoice_unique_id.pdf";
        $this->load->library('dompdf_lib');
        $dompdf = new DOMPDF();

        // Convert to PDF
        $this->dompdf->set_paper("A4", "portrait");
        $this->dompdf->load_html($html);
        $this->dompdf->render();
        $pdf = $this->dompdf->output();
        /*-------------------------------------- GENERATE PDF END -------------------------------*/

        //new file path
        $new_file = FILES_TEMP_FOLDER . $filename;

        //delete existing pdf file if any
        @unlink($new_file);

        //save the file
        write_file($new_file, $pdf);

        //check if file was created ok
        if (is_file($new_file)) {
            return $new_file;
        } else {
            return false;
        }

    }

    /**
     * generate a pdf invoice
     * uses the mpdf class
     * @attribution
     * http://www.mpdf1.com/mpdf/index.php?page=Download
     * http://davidsimpson.me/2013/05/19/using-mpdf-with-codeigniter/     *
     * @param	string $output display on screen or save as file
     */
    function __pdfInvoice($output = 'view')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_COMMON_THEME . 'project.invoice.pdf.html';

        //flow control
        $next = true;

        //load helper
        $this->load->helper('download');

        //invoice id
        $invoice_id = $this->uri->segment(5);

        //invoice id
        $action = $this->uri->segment(6);

        //check if invoice exists
        if ($next) {
            $invoice = $this->invoices_model->getInvoice($invoice_id);
            $this->data['debug'][] = $this->invoices_model->debug_data;

            //set invoice name
            if ($invoice) {
                $filename = 'invoice_' . $invoice['invoices_unique_id'] . '.pdf';
            } else {

                //set flash notice
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);

                //redirect back to view the invoice
                redirect('/admin/invoice/' . $this->project_id . '/view/' . $invoice_id);
            }
        }

        //start to generate the invoice1
        if ($next) {

            //reduce error reporting to only critical
            @error_reporting(E_ERROR);

            //turn off codeigniter profiler (which may be on in MY_Controller) during debug mode
            $this->output->enable_profiler(false);

            //generate the invoice view as normal, but buffer it to variable ($html)
            ob_start();

            $this->__viewInvoice();

            $this->__flmView('client/main');

            $html = ob_get_contents();

            ob_end_clean();

            /*------------------------------- GENERATE PDF------- (mpdf class)------------------------/
            * Take generated html and passes it to mpdf class pdf output is saved in variable $pdf
            *
            *----------------------------------------------------------------------------------------*/
            $this->load->library('dompdf_lib');
            $dompdf = new DOMPDF();

            // Convert to PDF
            //$this->dompdf->set_paper(DEFAULT_PDF_PAPER_SIZE, 'portrait');
            $this->dompdf->set_paper("A4", "portrait");
            $this->dompdf->load_html($html);
            $this->dompdf->render();
            $pdf = $this->dompdf->output();
            /*-------------------------------------- GENERATE PDF END -------------------------------*/

            //force download
            force_download($filename, $pdf);

            //if we want user to view in browser (comment out the force_download)
            //$this->dompdf->stream($filename, array("Attachment" => 0));

        }
    }

    /**
     * edit invoice basic details
     */
    function __editInvoice()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['edit_item_my_project_invoices'] != 1) {
            redirect('/admin/error/permission-denied');
        }

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
        if (!$this->__flmFormValidation('edit_invoice')) {

            //show error
            $this->notices('error', $this->form_processor->error_message);

            //halt
            $next = false;

        }

        //validate form & display any errors
        if (!$this->__flmFormValidation('edit_invoice_hidden')) {

            //show error
            $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

            //log error
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Adding new message failed: Required hidden form field ($key) missing or invalid]");
            //halt
            $next = false;

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
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');
            } else {

                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty');

                //halt
                $next = false;
            }

        }

        //load invoice view
        $this->__viewInvoice();

    }

    /**
     * manually add a payment to an invoice
     */
    function __addInvoicePayment()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['edit_item_my_project_invoices'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //flow control
        $next = true;

        //prevent direct access
        if (!isset($_POST['submit'])) {

            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('add-invoice-payment', 'view', $this_url);
            redirect($redirect);
        }

        //validate form
        if ($next) {
            if (!$this->__flmFormValidation('add_invoice_payment')) {
                //show error
                $this->notices('error', $this->form_processor->error_message, 'html');
                //halt
                $next = false;
            }
        }

        //validate hidden fields
        if ($next) {
            if (!$this->__flmFormValidation('add_invoice_payment_hidden')) {

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Adding new invoice payment failed: Required hidden form fields missing or invalid]");

                //error
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
        }

        //add to database
        if ($next) {

            $result = $this->payments_model->addPayment($this->input->post(null, true));
            $this->data['debug'][] = $this->payments_model->debug_data;

            //did transaction pass
            if ($result) {
                //success message
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');

                //events tracker
                $this->__eventsTracker('invoice-payment', array());

            } else {
                //error message
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'html');
                //halt
                $next = false;
            }
        }

        /*--------------------------------------------------------------------
        * REFRESH THIS INVOICE
        *-------------------------------------------------------------------*/
        $this->refresh->refreshSingleInvoice($this->input->post('payments_invoice_id'));
        $this->data['debug'][] = $this->refresh->debug_data; //library debug

        //load invoice view
        $this->__viewInvoice();

    }

    /**
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {
        //profiling
        $this->data['controller_profiling'][] = __function__; //form validation
        if ($form == 'add_invoice') {

            //check required fields
            $fields = array('invoices_date' => $this->data['lang']['lang_date_billed'], 'invoices_due_date' => $this->data['lang']['lang_due_date']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }
            //everything ok
            return true;
        }

        //form validation
        if ($form == 'add_invoice_item') {

            //check required fields
            $fields = array(
                'invoice_products_title' => $this->data['lang']['lang_title'],
                'invoice_products_rate' => $this->data['lang']['lang_rate'],
                'invoice_products_quantity' => $this->data['lang']['lang_quantity']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check numeric fields
            $fields = array('invoice_products_rate' => $this->data['lang']['lang_rate'], 'invoice_products_quantity' => $this->data['lang']['lang_quantity']);
            if (!$this->form_processor->validateFields($fields, 'numeric')) {
                return false;
            }

            //everything ok
            return true;
        }

        //form validation
        if ($form == 'edit_invoice') {

            //check required fields
            $fields = array('invoices_date' => $this->data['lang']['lang_date_billed'], 'invoices_due_date' => $this->data['lang']['lang_due_date']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check numeric fields
            $fields = array('invoices_tax_rate' => $this->data['lang']['lang_tax']);
            if (!$this->form_processor->validateFields($fields, 'numeric')) {
                return false;
            }

            //everything ok
            return true;
        }

        //form validation
        if ($form == 'edit_invoice_hidden') {

            //check required fields
            $fields = array('invoices_id' => $this->data['lang']['lang_id']);
            if (!$this->form_processor->validateFields($fields, 'numeric')) {
                return false;
            }

            //everything ok
            return true;
        }

        //form validation
        if ($form == 'add_invoice_payment') {

            //check required fields
            $fields = array(
                'payments_method' => $this->data['lang']['lang_payment_method'],
                'payments_date' => $this->data['lang']['lang_date'],
                'payments_currency_code' => $this->data['lang']['lang_currency'],
                'payments_transaction_id' => $this->data['lang']['lang_transaction_id']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check numeric fields
            $fields = array('payments_amount' => $this->data['lang']['lang_amount']);
            if (!$this->form_processor->validateFields($fields, 'numeric')) {
                return false;
            }

            //everything ok
            return true;
        }

        //form validation
        if ($form == 'add_invoice_payment_hidden') {

            //check required fields (numeric)
            $fields = array(
                'payments_invoice_id' => $this->data['lang']['lang_payment_method'],
                'payments_client_id' => $this->data['lang']['lang_client_id'],
                'payments_project_id' => $this->data['lang']['lang_project_id']);
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
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     */
    function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //[all_invoice_items]
        $data = $this->invoice_items_model->allItems('invoice_items_title', 'ASC');
        $this->data['debug'][] = $this->invoice_items_model->debug_data;
        $this->data['lists']['all_invoice_items'] = create_pulldown_list($data, 'invoice_items', 'id');

        //[payment_methods]
        $data = $this->settings_payment_methods_model->paymentMethods('enabled');
        $this->data['debug'][] = $this->settings_payment_methods_model->debug_data;
        $this->data['lists']['payment_methods'] = create_pulldown_list($data, 'payment_methods', 'id');

    }

    /**
     * records new project events (timeline)
     *
     * @param	string   $type identify the loop to run in this function
     * @param   array    $vents_data an optional array that can be used to directly pass data]      
     */
    function __eventsTracker($type = '', $events_data = array())
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //--------------record a new event-----------------------
        if ($type == 'new-invoice') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->input->post('invoices_project_id');
            $events['project_events_type'] = 'invoice';
            $events['project_events_details'] = $events_data['target_id'];
            $events['project_events_action'] = 'lang_tl_created_invoice';
            $events['project_events_target_id'] = $events_data['target_id'];
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'team';

            //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;
        }

        //--------------record a new event-----------------------
        if ($type == 'invoice-payment') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->input->post('payments_project_id');
            $events['project_events_type'] = 'payment';
            if ($this->input->post('invoices_custom_id') == '') {
                $events['project_events_details'] = $this->input->post('payments_invoice_id');
            } else {
                $events['project_events_details'] = $this->input->post('invoices_custom_id');
            }
            $events['project_events_action'] = 'lang_tl_added_invoice_payment';
            $events['project_events_target_id'] = $this->input->post('payments_invoice_id');
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'team';

            //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;
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
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];
        $this->data['email_vars']['client_dashboard_url'] = $this->data['vars']['site_url_client'];

        //specific passed variables
        foreach ($vars as $key => $value) {
            $this->data['email_vars'][$key] = $value;
        }

        //-------------send out email-------------------------------
        if ($email == 'client_invoice') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate($this->data['email_vars']['email_template']);
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);
            //send email
            email_default_settings(); //defaults (from emailer helper)
            //$this->email->to($this->data['email_vars']['client_users_email']);
            $this->email->to($this->data['email_vars']['client_users_email']);
            $this->email->subject($this->data['email_vars']['email_subject']);
            $this->email->message($email_message);
            $this->email->attach($this->data['email_vars']['pdfinvoice']);
            $this->email->send();

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

/* End of file invoice.php */
/* Location: ./application/controllers/admin/invoice.php */
