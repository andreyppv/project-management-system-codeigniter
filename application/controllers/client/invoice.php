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
        $this->data['template_file'] = PATHS_CLIENT_THEME . '/view.invoice.html';

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

        //uri - action segment
        $action = $this->uri->segment(4);

        //default page titles
        $this->data['vars']['main_title'] = $this->data['lang']['lang_invoices'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-list-alt"></i>';

        //re-route to correct method
        switch ($action) {

            case 'view':
                $this->__viewInvoice();
                break;

            case 'pdf':
                $this->__pdfInvoice();
                break;

            default:
                $this->__viewInvoice();
        }

        //load view
        if ($action != 'pdf') {
            $this->__flmView('client/main');
        }

    }

    /**
     * hello world
     *
     */
    function __viewInvoice($id = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get invoice id
        if (is_numeric($id)) {
            $invoice_id = $id;
        } else {
            $invoice_id = $this->uri->segment(5);
        }

        /** CLIENT CHECK PERMISSION **/
        $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 
        $prepared = $db->prepare("SELECT * FROM invoices WHERE invoices_id = ?");
        $prepared->execute(array($invoice_id));
        $row = $prepared->fetch(PDO::FETCH_ASSOC);
        if (intval($row['invoices_id']) != intval($clientid)) {
            redirect('/client/error/permission-denied-or-not-found');
        }

        //check invoice id again
        if ($next) {
            if (!is_numeric($invoice_id)) {
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_requested_item_not_loaded']);
                $next = false;
            }
        }

        /*--------------------------------------------------------------------
        * REFRESH THIS INVOICE
        *-------------------------------------------------------------------*/
        if ($next) {
            $this->refresh->refreshSingleInvoice($invoice_id);
            $this->data['debug'][] = $this->refresh->debug_data;
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

            //show also project title
            $this->data['vars']['invoice_page_title'] = $this->data['fields']['invoice']['projects_title'];

        }

    }

    /**
     * generate a pdf invoice
     * uses the mpdf class
     * @attribution
     * http://www.mpdf1.com/mpdf/index.php?page=Download
     * http://davidsimpson.me/2013/05/19/using-mpdf-with-codeigniter/     *
     * @param	string   [output: display on screen or save as file
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

        /** CLIENT CHECK PERMISSION **/
        if (!$this->permissions->invoicesView($invoice_id)) {
            show_error($this->data['lang']['lang_permission_denied_info'], 500);
            die();
        }

        //set a new invoice file name
        $filename = "invoice_$invoice_id.pdf";

        if ($next) {

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
            //$this->dompdf->stream($filename, array("Attachment" => 0)); // VIEW IN OTHER TAB
            /*-------------------------------------- GENERATE PDF END -------------------------------*/

            //email pdf
            if ($action == 'email') {

                //create a unique director
                $this->data['vars']['temp_invoice_directory'] = FILES_TEMP_FOLDER . random_string('alpha', 12);
                @mkdir($this->data['vars']['temp_invoice_directory']);
                $this->data['vars']['temp_invoice_path'] = $this->data['vars']['temp_invoice_directory'] . '/' . $filename;
                write_file($this->data['vars']['temp_invoice_path'], $pdf);

                //load view
                $this->data['template_file'] = PATHS_CLIENT_THEME . '/view.invoice.html';
                $this->__viewInvoice();
                $this->__flmView('client/main');
                //email me (the logged in client user)
                //$this->_emailer('email_invoice_attached');

            } else {

                //force download
                force_download($filename, $pdf);

                //if we want user to view in browser (comment out the force_download)
                //$this->dompdf->stream($filename, array("Attachment" => 0));
            }

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
        if ($email == 'email_invoice_attached') {

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

    }

    /**
     * recalculate an
     *
     * @param numeric $$id: invoice id]
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
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //form validation
        if ($form == 'add_user') {

            //check required fields
            $fields = array('company_name_field' => $this->data['lang']['lang_company_name'], 'email_field' => $this->data['lang']['lang_email']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check email fields
            $fields = array('users_email' => $this->data['lang']['lang_email']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check password (lenght only - 8 characters min)
            $fields = array('password_field' => $this->data['lang']['lang_password']);
            if (!$this->form_processor->validateFields($fields, 'length')) {
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

/* End of file invoice.php */
/* Location: ./application/controllers/client/invoice.php */
