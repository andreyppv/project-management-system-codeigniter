<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Paypal ipn related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Paypalipn extends MY_Controller
{

    /**
     * constructor method
     */
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //reduce error reporting to only critical
        @error_reporting(E_ERROR);

        //turn off codeigniter profiler (which may be on in MY_Controller) during debug mode
        $this->output->enable_profiler(false);
    }

    /**
     * process paypal IPN calls
     *
     */
    function index()
    {

        //flow control
        $next = true;

        //Set to false when live & true when testing
        define("USE_SANDBOX", false);

        /** ---------------------------------------------------------------------------
         *  STEP 1
         *  Read initial Paypal post data. This is initiation call made by Paypal
         * ----------------------------------------------------------------------------*/
        if ($next) {
            $raw_post_data = file_get_contents('php://input');
            $raw_post_array = explode('&', $raw_post_data);

            //save each post item to an array
            $paypal = array();
            foreach ($raw_post_array as $keyval) {
                $keyval = explode('=', $keyval);
                if (count($keyval) == 2) $paypal[$keyval[0]] = urldecode($keyval[1]);
            }

            //log that ipn has been initiated
            if ($this->config->item('debug_mode') == 1) {
                $log_message = string_print_r($paypal); //turn arry into pretty string
                log_message('error', 'PAYPAL IPN LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: PAYPAL IPN STARTED - Paypal has just called <pre>$log_message</pre>]");
            }
        }

        /** ---------------------------------------------------------------------------
         *  STEP 2
         *  Read the post from PayPal system and add 'cmd=_notify-validate'
         * ----------------------------------------------------------------------------*/
        if ($next) {
            $our_reponse = 'cmd=_notify-validate';
            if (function_exists('get_magic_quotes_gpc')) {
                $get_magic_quotes_exists = true;
            }
            foreach ($paypal as $key => $value) {
                if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                    $value = urlencode(stripslashes($value));
                } else {
                    $value = urlencode($value);
                }
                $our_reponse .= "&$key=$value";
            }
        }

        /** ------------------------------------------------------------------------------
         *  STEP 3
         *  Post Paypals origianl data (received in step 1: $our_response) back to Paypal
         * -------------------------------------------------------------------------------*/
        if ($next) {

            //live or sandbox
            if (USE_SANDBOX == true) {
                $paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
            } else {
                $paypal_url = "https://www.paypal.com/cgi-bin/webscr";
            }

            $ch = curl_init($paypal_url);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $our_reponse);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
            $paypal_response = curl_exec($ch);

            //check if ok
            if (curl_errno($ch) != 0) {

                //full header response
                $header_response = curl_getinfo($ch, CURLINFO_HEADER_OUT);

                //log this error & header repsonse
                if ($this->config->item('debug_mode') == 1) {
                    log_message('error', 'PAYPAL IPN LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: PAYPAL IPN FAILED - $header_response]");
                }

                //halt
                $next = false;

            }

            //close curl
            curl_close($ch);
        }

        /** ---------------------------------------------------------------------------
         *  STEP 4
         *  Inspect Paypals reponse to our post back. Response is alway one word
         *  VERIFIED or INVALID
         * ----------------------------------------------------------------------------*/
        // Split response headers and payload, a better way for strcmp
        if ($next) {
            $tokens = explode("\r\n\r\n", trim($paypal_response));
            $paypal_response = trim(end($tokens));

            //transaction verified OK
            if (strcmp($paypal_response, "VERIFIED") == 0) {

                //get the payments details we need and save to array
                $payment['payments_transaction_id'] = $_POST['txn_id'];
                $payment['payments_invoice_id'] = $_POST['item_number'];
                $payment['payments_invoice_unique_id'] = $_POST['item_name'];
                $payment['payments_amount'] = $_POST['payment_gross'];
                $payment['payments_currency_code'] = $_POST['mc_currency'];
                $payment['payments_transaction_status'] = strtolower($_POST['payment_status']);
                $payment['payments_notes'] = '';
                $payment['payments_by_user_id'] = $_POST['custom'];

                //log this for debugging
                if ($this->config->item('debug_mode') == 1) {
                    $log_message = string_print_r($_POST); //turn arry into pretty string
                    log_message('error', 'PAYPAL IPN LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: PAYPAL IPN PASSED - <pre>$log_message</pre>]");
                }

                //now check what paypal had to say about the transaction itself
                if (in_array($payment['payments_transaction_status'], array(
                    'completed',
                    'in-progress',
                    'pending'))) {
                    //flow control for (step 5)
                    $update_database = true;
                }
            }

            //actaul IPN failed. NB 'failed transactions' should be checked above, not here
            if (strcmp($paypal_response, "INVALID") == 0) {
                //log this for debugging
                if ($this->config->item('debug_mode') == 1) {
                    $log_message = string_print_r($_POST); //turn arry into pretty string
                    log_message('error', 'PAYPAL IPN LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: PAYPAL IPN FAILED - <pre>$log_message</pre>]");
                }

                //halt
                $next = false;
            }
        }

        /** ---------------------------------------------------------------------------
         *  STEP 5
         *  Update our database
         * ----------------------------------------------------------------------------*/
        if ($next && $update_database) {

            //get invoice id
            $invoice_id = $this->invoices_model->getInvoiceID($payment['payments_invoice_unique_id']);
            $this->data['debug'][] = $this->invoices_model->debug_data;
            log_message('debug', 'PAYPAL IPN LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: PAYPAL IPN - \$invoice ID (" . string_print_r($invoice) . ")]");

            //get actual invoice
            $invoice = $this->invoices_model->getInvoice($invoice_id);
            $this->data['debug'][] = $this->invoices_model->debug_data;
            log_message('debug', 'PAYPAL IPN LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: PAYPAL IPN - \$invoice Data (" . string_print_r($invoice) . ")]");

            if ($invoice) {

                //append additional info payment data array
                $payment['payments_invoice_id'] = $invoice['invoices_id'];
                $payment['payments_invoices_custom_id'] = $invoice['invoices_custom_id'];
                $payment['payments_project_id'] = $invoice['invoices_project_id'];
                $payment['payments_client_id'] = $invoice['invoices_clients_id'];
                $payment['payments_method'] = 'paypal';
                log_message('debug', 'PAYPAL IPN LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: PAYPAL IPN - \$ayment Data Array: (" . string_print_r($payment) . ")]");

                //check if payment has not already been recorded
                $paid = $this->payments_model->getByTransactionID($payment['payments_transaction_id']);
                $this->data['debug'][] = $this->payments_model->debug_data;
                log_message('debug', 'PAYPAL IPN LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: PAYPAL IPN - \$paid Data Array: (" . string_print_r($paid) . ")]");

                //insert record
                if (!$paid) {
                    $this->payments_model->addPayment($payment);
                    $this->data['debug'][] = $this->payments_model->debug_data;

                    //flow - send email
                    $next_email = true;
                }

                //update record
                if ($paid) {
                    $this->payments_model->updatePaymentStatus($payment['payments_transaction_id'], $payment['payments_transaction_status']);
                    $this->data['debug'][] = $this->payments_model->debug_data;
                }

            } else {
                //log error
                log_message('error', 'PAYPAL IPN LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: PAYPAL IPN FAILED - Received invoice id is not in the database ($invoice_unique_id) Transaction ID: ($transaction_id)]");

                //halt
                $next = false;
            }

        }

        /** ---------------------------------------------------------------------------
         *  STEP 6
         *  Send out emails & track event & update invoice with new payment (refresh)
         * ----------------------------------------------------------------------------*/
        if ($next && $next_email) {

            //---email admins--------------------------
            $email_vars['clients_company_name'] = $invoice['clients_company_name'];
            $email_vars['invoice_id'] = $payment['payments_invoice_id'];
            $email_vars['transaction_id'] = $payment['payments_transaction_id'];
            $email_vars['amount'] = $payment['payments_amount'];
            $email_vars['currency'] = $payment['payments_currency_code'];
            $this->data['vars']['emailvars'] = $email_vars; //debug
            $this->__emailer('new_payment', $email_vars);

            //---track event---------------------------
            $event_vars['invoices_project_id'] = $invoice['invoices_project_id'];
            if ($payment['payments_invoices_custom_id'] != '') {
                $event_vars['invoices_id'] = $payment['payments_invoices_custom_id'];
            } else {
                $event_vars['invoices_id'] = $payment['payments_invoice_id'];
            }
            $event_vars['payments_by_user_id'] = $payment['payments_by_user_id'];
            $this->data['vars']['eventvars'] = $email_vars; //debug
            $this->__eventsTracker('invoice-payment', $event_vars);

            //---refresh invoice-----------------------
            $this->refresh->refreshSingleInvoice($payment['payments_invoice_id']);

            //log this
            log_message('debug', 'PAYPAL IPN LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: PAYPAL IPN - sending email & recording this event in timeline]");
        }

        //debugging
        $this->__ajaxdebugging();
    }

    /**
     * send out an email
     *
     * @param string $email email address
     */
    function __emailer($email = '', $vars = array())
    {

        //specific passed variables
        foreach ($vars as $key => $value) {
            $this->data['email_vars'][$key] = $value;
        }

        //-------------send out email-------------------------------
        if ($email == 'new_payment') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_payment_admin');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);
            //send email to multiple admins
            foreach ($this->data['vars']['mailinglist_admins'] as $email_address) {
                email_default_settings(); //defaults (from emailer helper)
                $this->email->to($email_address);
                $this->email->subject($this->data['lang']['lang_new_payment']);
                $this->email->message($email_message);
                $this->email->send();
            }
        }

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

        //--------------get any passed data-----------------------
        foreach ($event_data as $key => $value) {
            $$key = $value;
        }

        //flow control
        $next = true;

        //--------------record a new event-----------------------
        if ($type == 'invoice-payment') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $invoices_project_id;
            $events['project_events_type'] = 'payment';
            $events['project_events_details'] = $invoices_id;
            $events['project_events_action'] = 'lang_tl_paid_invoice';
            $events['project_events_target_id'] = $invoices_id;
            $events['project_events_user_id'] = $payments_by_user_id;
            $events['project_events_user_type'] = 'client';

            //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;
        }
    }

    /**
     * ipn runs in the background, so we want to do as much logging as possibe for debuggin
     */
    function __ajaxdebugging()
    {

        //url segmets array
        $this->data['url_segments'] = $this->uri->segment_array();

        //format debug data for log file
        ob_start();
        print_r($this->data);
        $all_data = ob_get_contents();
        ob_end_clean();

        //write to logi file
        if ($this->config->item('debug_mode') == 2 || $this->config->item('debug_mode') == 1) {
            log_message('error', "IPN-DEBUGGING-LOG:: BIG DATA $all_data");
        }
    }

}

/* End of file paypalipn.php */
/* Location: ./application/controllers/api/paypalipn.php */
