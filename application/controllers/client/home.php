<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Home related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Home extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . '/home.html';

        //css settings
        $this->data['vars']['css_menu_dashboard'] = 'open'; //menu

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

        //uri - action segment
        $action = $this->uri->segment(3);

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_dashboard']; //lang


        //re-route to correct method
        switch ($action) {
            default:
                $this->__loadHome();
        }

        //load view
        $this->__flmView('client/main');

    }


    /**
     * show the home page 
     *
     */
    public function __loadHome()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        $client_data = $this->clients_model->clientDetailsByClientUser($this->data['vars']['my_id']);
        
        $this->data['vars']['credit_amount_remaining'] = $client_data['credit_amount_remaining'];
        
        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'projects';
        $this->data['blocks']['projects'] = $this->projects_model->searchProjects(0, 'search', $client_data['clients_id'], null);
        $this->data['blocks']['tasks'] = $this->tasks_model->listTasksByClient($client_data['clients_id']);

        ### calculate client's balance ###
        $bcat = $this->billing_categories_model->listCategories();
        $total = 0;
        foreach($this->data['blocks']['tasks'] as $t)
        {
            if($t['projects_status'] != 'completed' && $t['tasks_status'] == 'completed')
            {
                //$total += $t['hourslogged'] * $bcat[$t['billingcategory']]['bcat_rate'];
                $total += floatval($t['estimatedhours']) * $bcat[$t['billingcategory']]['bcat_rate'];
            }
        }
        $this->data['vars']['client_balance'] = $this->data['vars']['credit_amount_remaining'] - $total;

        //payments list
        $this->data['reg_blocks'][] = 'payments';
        $this->data['blocks']['payments'] = $this->payments_model->searchPayments(0, 'search', '', 'all', 'desc');

        //load members projects
        $this->__clientsProjects();

        //due invoices
        $this->__dueInvoices();

        //display timeline
        $this->__getEventsTimeline();


        //package integration
        $amount = floatval($this->input->post('customamount'));

        if($amount)
        {
            $data = array(
                'invoices_project_id'     => 0,
                'invoices_clients_id'     => $this->data['vars']['my_id'],
                'invoices_date'           => date('Y-m-d'),
                'invoices_due_date'       => date('Y-m-d'),
                'invoices_status'         => 'due',
                'invoices_notes'          => 'Development Hours Package Purchase',
                'invoices_created_by_id'  => $this->data['vars']['my_id'],
                'invoices_events_id'      => 0,

                'invoices_pretax_amount' => $amount,
                'invoices_tax_amount'    => 0, //sales tax $
                'invoices_amount'        => $amount, //amount + tax
                'invoices_tax_rate'      => 0 //sales tax %
            );

            $invoice_id = $this->invoices_model->addInvoice($data);

            $invoice = $this->invoices_model->getInvoiceByID($invoice_id, 'id');
            $invoice_uniqiue_id = $invoice['invoices_unique_id'];

            $data = array(
                'invoice_products_invoice_id'  => $invoice_id,
                'invoice_products_project_id'  => 0,
                'invoice_products_title'       => "$ $amount of Project Time Added To Account",
                'invoice_products_description' => "$ $amount of Project Time Added To Account",
                'invoice_products_quantity'    => 1,
                'invoice_products_rate'        => $amount,
                'invoice_products_total'       => $amount
            );
            $this->invoice_products_model->addItem($data);


            redirect("client/pay/$invoice_uniqiue_id");
        }

    }


    private function __generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function __generateRandomIntString($length = 10)
    {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function __createInvoice($clientID, $items, $price)
    {
        $invoiceProjectID = 0; //Default
        $invoiceCustomID = $this->__generateRandomString(7) . '-' . $this->__generateRandomIntString(2);
        $invoiceUnquieID = $this->__generateRandomString(6);
        $invoiceClientID = $clientID;
        $invoicePreTax = $price;
        $invoiceTaxAmount = ($price * 0.00); //Sales tax
        $invoiceTotal = ($invoicePreTax + $invoiceTaxAmount);
        $invoiceTaxRate = 0.00; //7% Sales tax
        $invoiceDate = date("Y-m-d");
        $invoiceDueDate = date("Y-m-d");
        $invoiceStatus = "due";
        $invoiceNotes = "Development Hours Package Purchase";

        $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 
        $prepared = $db->prepare("INSERT INTO invoices VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NULL, 0)");
        $prepared->execute(array($invoiceCustomID, $invoiceUnquieID, $invoiceProjectID, $invoiceClientID, $invoicePreTax, $invoiceTaxAmount, $invoiceTotal, $invoiceTaxRate, $invoiceDate, $invoiceDueDate, $invoiceStatus, $invoiceNotes));
        

        $prepared = $db->prepare("SELECT * FROM invoices WHERE invoices_custom_id = ?");
        $prepared->execute(array($invoiceCustomID));
        $row = $prepared->fetch(PDO::FETCH_ASSOC);
        $invoiceID = $row['invoices_id'];

        //Invoice created, create products now.
        foreach ($items as $item) {
            $prepared = $db->prepare("INSERT INTO invoice_products VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)");
            $prepared->execute(array($invoiceProjectID, $invoiceID, $item['Title'], $item['Description'], $item['Quantity'], $item['Price'], $item['Price']));
        }

        return $invoiceUnquieID;
    }


    /**
     * eload 2 of my projects
     */
    protected function __clientsProjects()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        $projects = $this->projects_model->allProjects('project_deadline', 'DESC', $this->data['vars']['my_client_id'], 'all');
        $this->data['debug'][] = $this->projects_model->debug_data;

        //first project
        $this->data['reg_fields'][] = 'project_one';
        $this->data['fields']['project_one'] = (isset($projects[0]))? $projects[0] : array();

        //second project
        $this->data['reg_fields'][] = 'project_two';
        $this->data['fields']['project_two'] = (isset($projects[1]))? $projects[1] : array();
        
        //visibility of first project
        if (is_array($this->data['fields']['project_one']) && !empty($this->data['fields']['project_one'])) {
            $this->data['visible']['wi_project_one'] = 1;
        } else {
            $this->data['visible']['wi_project_none'] = 1;
        }

        //visibility of second project
        if (is_array($this->data['fields']['project_two']) && !empty($this->data['fields']['project_two'])) {
            $this->data['visible']['wi_project_two'] = 1;
        }

    }

    /**
     * eload 2 of my projects
     */
    protected function __dueInvoices()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //sum up all 'due' invoices
        $due_invoices = $this->invoices_model->dueInvoices($this->data['vars']['my_client_id'], 'client', 'due');
        $this->data['debug'][] = $this->invoices_model->debug_data;
        $this->data['vars']['due_invoices'] = '';
        for ($i = 0; $i < count($due_invoices); $i++) {
            $this->data['vars']['due_invoices'] += $due_invoices[$i]['amount_due'];
        }

        //sum up all 'overdue' invoices
        $overdue_invoices = $this->invoices_model->dueInvoices($this->data['vars']['my_client_id'], 'client', 'overdue');
        $this->data['debug'][] = $this->invoices_model->debug_data;
        $this->data['vars']['overdue_invoices'] = '';
        for ($i = 0; $i < count($overdue_invoices); $i++) {
            $this->data['vars']['overdue_invoices'] += $overdue_invoices[$i]['amount_due'];
        }

    }

    /**
     * get ann events from clients projects
     */
    protected function __getEventsTimeline()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //try to create 'comma separated' list of clients projects
        if ($next) {

            //check if client has projects
            if ($this->data['vars']['my_clients_project_list']) {

                //get project events (timeline)
                $this->data['reg_blocks'][] = 'timeline';
                $this->data['blocks']['timeline'] = $this->project_events_model->getEvents($this->data['vars']['my_clients_project_list'], 'project-list');
                $this->data['debug'][] = $this->project_events_model->debug_data;

                //further process events data
                $this->data['blocks']['timeline'] = $this->__prepEvents($this->data['blocks']['timeline']);

                //show timeline
                $this->data['visible']['show_timeline'] = 1;

            } else {

                //show no events found
                $this->data['visible']['show_no_timeline'] = 1;

            }

        }

    }

    /**
     * additional data preparations project events (timeline) data
     *
     */
    protected function __prepEvents($thedata = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (count($thedata) == 0 || !is_array($thedata)) {
            return $thedata;
        }

        /* -----------------------PREPARE FILES DATA ----------------------------------------/
        *  Loop through all the files in this array and for each file:
        *  -----------------------------------------------------------
        *  (1) process user names ('event by' data)
        *  (2) add back the language for the action carried out
        *
        *
        *------------------------------------------------------------------------------------*/
        for ($i = 0; $i < count($thedata); $i++) {

            //--team member---------------------
            if ($thedata[$i]['project_events_user_type'] == 'team') {
                $thedata[$i]['user_name'] = $thedata[$i]['team_profile_full_name'];
            }

            //--client user---------------------
            if ($thedata[$i]['project_events_user_type'] == 'client') {
                $thedata[$i]['user_name'] = $thedata[$i]['client_users_full_name'];
            }

            //add back langauge
            $word = $thedata[$i]['project_events_action'];
            $thedata[$i]['project_events_action_lang'] = $this->data['lang'][$word];

            //add #hash to numbers (e.g invoice number) and create a new key called 'project_events_item'
            if (is_numeric($thedata[$i]['project_events_details'])) {
                $thedata[$i]['project_events_item'] = '#' . $thedata[$i]['project_events_details'];
            } else {
                $thedata[$i]['project_events_item'] = $thedata[$i]['project_events_details'];
            }

        }

        //retun the processed data
        return $thedata;
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

/* End of file home.php */
/* Location: ./application/controllers/client/home.php */
