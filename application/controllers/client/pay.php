<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Pay related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Pay extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . '/pay.html';

        //css settings
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    public function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonClient_LoggedInCheck();

        //create pulldown lists
        $this->__pulldownLists();

        //uri - action segment
        $action = $this->uri->segment(4);

        //default page titles
        $this->data['vars']['main_title'] = $this->data['lang']['lang_invoices'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-list-alt"></i>';

        $this->data['vars']['sub_title'] = $this->data['lang']['lang_payments'];
        $this->data['vars']['sub_title_icon'] = '<i class="icon-credit-card"></i>';

        //re-route to correct method
        switch ($action) {

            default:
            case 'new':
                $this->__newPayment();
                break;

            case 'confirm':
                $this->__confirmPayment();
                break;

            case 'thankyou':
                $this->__thankyou();
                break;
        }

        //load view
        $this->__flmView('client/main');

    }

    /**
     * start a new payment
     *
     */
    protected function __newPayment()
    {
        $clientid = $this->data['vars']['my_id'];

        if($this->input->post('cardPayment'))
        {
            //payment submitted check everything
            //package integration
            $packages = array(
                array("price" => 250, "bonus" => 0),
                array("price" => 500, "bonus" => 50),
                array("price" => 1000, "bonus" => 150),
                array("price" => 2500, "bonus" => 350),
                array("price" => 5000, "bonus" => 800)
            );

            
            $amount = $this->input->post('invoiceAmount');
            $tax = 0.0; //7% sales tax

            $b = false;
            foreach($packages as $p)
            {
                if ($p['price'] == round($amount * (1 - $tax), 0)){
                    $package = $p;
                    $b = true;
                }else{
                    continue;
                }
            }

            if ($b == false){
                $package = array('price' => round($amount, 0), 'bonus' => 0);
            }

            $invoiceID = $this->input->post('invoiceID');
            $cardFirstName = $this->input->post('cardFirstName');
            $cardLastName = $this->input->post('cardLastName');
            $cardNumber = preg_replace('/[\D]/', '', $this->input->post('cardNumber'));
            $cardNumberMask = substr($cardNumber, 0, 4).'***'.substr($cardNumber, -4);
            $cardExp = preg_replace('/[\D]/', '', $this->input->post('cardExp'));
            $cardCCV = preg_replace('/[\D]/', '', $this->input->post('cardCCV'));
            $billingAddress = $this->input->post('billingAddress');
            $billingCity = $this->input->post('billingCity');
            $billingState = $this->input->post('billingState');
            $billingZip = $this->input->post('billingZip');
            $billingCountry = $this->input->post('billingCountry');


            //### CREDTCARD LIBRARY ###
            $this->load->library('Creditcard');
            $this->config->load('creditcard');

            $this->creditcard->setUsername($this->config->item('cc_gateway_id'));
            $this->creditcard->setPassword($this->config->item('cc_password'));
            $this->creditcard->setApiVersion($this->config->item('cc_api_version'));
            $this->creditcard->setApiId($this->config->item('cc_key_id'));
            $this->creditcard->setApiKey($this->config->item('cc_hmac_key'));
            $this->creditcard->setTestMode($this->config->item('cc_test_mode'));

            // Charge
            $this->creditcard->setTransactionType(Creditcard::TRAN_PURCHASE); //Creditcard::TRAN_PURCHASE
            $this->creditcard->setCreditCardType($this->__detectCardType($cardNumber))
                    ->setCreditCardNumber($cardNumber)
                    ->setCreditCardName($cardFirstName . ' ' . $cardLastName)
                    ->setCreditCardExpiration($cardExp)
                    ->setAmount($amount)
                    ->setReferenceNumber($invoiceID);
            if($billingZip) {
                $this->creditcard->setCreditCardZipCode($billingZip);
            }

            if($cardCCV) {
                $this->creditcard->setCreditCardVerification($cardCCV);
            }

            if($billingAddress) {
                $this->creditcard->setCreditCardAddress($billingAddress);
            }

            $this->creditcard->process();
/*
var_dump($this->creditcard->getHeaders() );
echo $this->__detectCardType($cardNumber).'<br>';
echo $cardNumber.'<br>';
echo $cardFirstName . ' ' . $cardLastName.'<br>';
echo $cardExp.'<br>';
echo $amount.'<br>';
echo $invoiceID.'<br>';
*/
            // Check
            if($this->creditcard->isError()) {
                echo '<div class="alert alert-danger">Your card could not be processed! '.$this->creditcard->getErrorMessage().'</div>';
            }
            else
            {
                $invoiceRow = $this->invoices_model->getInvoiceByID($invoiceID, 'custom_id');

                $this->payments_model->addPayment(array(
                        'payments_invoice_id'     => $invoiceRow['invoices_id'],
                        'payments_project_id'     => 0,
                        'payments_client_id'      => $clientid,
                        'payments_amount'         => $amount,
                        'payments_currency_code'  => 'USD',
                        'payments_transaction_id' => $invoiceID,
                        'payments_method'         => 'Credit Card',
                        'payments_notes'          => "Invoice: $invoiceID paid $amount with $cardNumberMask"
                    ));

                $this->invoices_model->updateInvoiceStatus($invoiceRow['invoices_id'], 'paid');

                $total = intval($package['price'] + $package['bonus']);

                $client = $this->clients_model->clientDetails($clientid);

                $total += $client['credit_amount_remaining'];

                $this->clients_model->updateCreditAmountRemaining($client['clients_id'], $total);

                //Add thank you email template

                $this->__thankyou();
            }
            
            $done = true;
        }

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        if ($done){
            $next = false;
        }else{
            $next = true;
        }
        //invoice unique id
        $invoice_unique_id = $this->uri->segment(3);


        //check client ownership
        if ($next) {
            /** CLIENT CHECK PERMISSION **/
            $row = $this->invoices_model->getInvoiceByID($invoice_unique_id, 'unique_id');
            $invoice_id = $row['invoices_id'];

            if (intval($row['invoices_clients_id']) != intval($clientid)) {
                redirect('/client/error/permission-denied-or-not-found');
            }
        }

        /*--------------------------------------------------------------------
        * REFRESH THIS INVOICE
        *-------------------------------------------------------------------*/
        if ($next) {
            $this->refresh->refreshSingleInvoice($invoice_id);
            $this->data['debug'][] = $this->refresh->debug_data;
        }

        //get invoice details
        if ($next) {
            $this->data['reg_fields'][] = 'invoice';
            $this->data['fields']['invoice'] = $this->invoices_model->getInvoice($invoice_id);
            $this->data['debug'][] = $this->invoices_model->debug_data;
            if (!$this->data['fields']['invoice']) {
                //error loading invoice
                $this->notifications('wi_notification', $this->data['lang']['lang_request_could_not_be_completed']);
                //halt
                $next = false;
            }
        }

        //get invoice payments
        if ($next) {
            //sum payments
            $this->data['vars']['invoice_payments_sum'] = $this->payments_model->sumInvoicePayments($invoice_id);
            $this->data['debug'][] = $this->payments_model->debug_data;

            //amount due
            $this->data['vars']['invoice_balance_due'] = $this->data['fields']['invoice']['invoices_amount'] - $this->data['vars']['invoice_payments_sum'];

        }

        //does this invoice have any amount due
        if ($next) {

            if ($this->data['vars']['invoice_balance_due'] > 0) {
                $this->data['visible']['wi_payment_selector'] = 1;
                $this->data['visible']['wi_payment_summary'] = 1;
            } else {
                $this->notifications('wi_notification', $this->data['lang']['lang_there_is_no_amount_owing_on_invoice']);
            }
        }

        //allow part payment?
        if ($next) {
            if ($this->data['settings_invoices']['settings_invoices_allow_partial_payment'] == 'no') {
                $this->data['vars']['part_payment'] = 'readonly="readonly"';
            }
        }
    }

    protected function __detectCardType($num)
    {
        $re = array(
            "visa"       => "/^4[0-9]{12}(?:[0-9]{3})?$/",
            "mastercard" => "/^5[1-5][0-9]{14}$/",
            "amex"       => "/^3[47][0-9]{13}$/",
            "discover"   => "/^6(?:011|5[0-9]{2})[0-9]{12}$/",
        );

        if (preg_match($re['visa'],$num))
        {
            return 'visa';
        }
        else if (preg_match($re['mastercard'],$num))
        {
            return 'mastercard';
        }
        else if (preg_match($re['amex'],$num))
        {
            return 'amex';
        }
        else if (preg_match($re['discover'],$num))
        {
            return 'discover';
        }
        else
        {
            return false;
        }
    }

    /**
     * this is where we setup the payment gateway form
     *
     */
    protected function __confirmPayment()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //reload invoice details
        $this->__newPayment();

        //some vars
        $this->data['vars']['payment_total'] = $this->input->post('payment_amount');
        $this->data['vars']['payment_method'] = strtolower($this->input->post('payment_method'));

        //validate form
        /*if ($next) {
            if (!$this->__flmFormValidation('new_payment')) {
                //show error
                $this->notices('error', $this->form_processor->error_message, 'html');
                //halt
                $next = false;
            }
        }*/




        //check payment is not more than due
        if ($next) {
            if ($_POST['payment_amount'] > $this->data['vars']['invoice_balance_due']) {
                //show error
                $this->notices('error', $this->data['lang']['lang_amount_is_more'], 'html');
                //halt
                $next = false;
            }
        }

        //show input form
        if (!$next) {
            $this->data['visible']['wi_payment_selector'] = 1;
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

        //[all_payment_methods]
        $data = $this->settings_payment_methods_model->paymentMethods('enabled');
        $this->data['debug'][] = $this->settings_payment_methods_model->debug_data;
        $this->data['lists']['all_payment_methods'] = create_pulldown_list($data, 'payment_methods', 'id');

    }

    /**
     * start a new payment
     *
     */
    protected function __thankyou()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //todo - maybe check referrer to ensure users dont just load this page?

        //show notification
        $this->notifications('wi_notification', $this->data['lang']['lang_thank_you_for_your_payment']);

    }

    /**
     * validates forms for various methods in this class
     * @param   string $form identify the form to validate
     */
    protected function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //form validation
        if ($form == 'new_payment') {

            //numeric
            $fields = array('payment_amount' => $this->data['lang']['lang_amount']);
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

/* End of file pay.php */
/* Location: ./application/controllers/client/pay.php */
?>