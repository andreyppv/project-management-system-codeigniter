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

        $client_data = $this->clients_model->clientDetailsByClientUser($this->data['vars']['my_id']);
        
        $this->data['vars']['credit_amount_remaining'] = $client_data['credit_amount_remaining'];
		
		//get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'projects';
        $this->data['blocks']['projects'] = $this->projects_model->searchProjects(0, 'search', $client_data['clients_id'], null);
        

        //re-route to correct method
        switch ($action) {
            default:
                $this->__loadHome();
        }

        //package integration
        $packages = array(
            array("price" => 250, "bonus" => 0),
            array("price" => 500, "bonus" => 50),
            array("price" => 1000, "bonus" => 150),
            array("price" => 2500, "bonus" => 350),
            array("price" => 5000, "bonus" => 800)
        );

        if (empty($_POST['customamount']) && isset($_POST['package'])){
            $package = $packages[intval($_POST['package']) - 1];

            $items = array();

            foreach ($package as $key => $value) {
                if ($value != 0){
                    if ($key == "bonus"){
                        $items = array_merge($items, array( array("Title" => "$" . strval($value) . " of Project Time Added To Account", "Description" => "Free Bonus Time included with purchase of Package", "Price" => doubleval(0.00), "Quantity" => 1) )); //free
                    }else{
                        //add item for price
                        $items = array_merge($items, array( array("Title" => "$" . strval($value) . " of Project Time Added To Account", "Description" => "$" . strval($value) . " of Project Time Added To Account", "Price" => doubleval($value), "Quantity" => 1) )); //priced
                    }
                }
            }

            if ($this->data['vars']['my_user_type'] == "client"){
                $clientID = $this->data['vars']['my_id'];
                $invoiceUnqiueID = $this->createInvoice($clientID, $items, $package['price']); //created invoice returns unqiue id
                header("Location: https://pms.isodeveloper.com/client/pay/" . $invoiceUnqiueID);
                exit;
            }
        }elseif(isset($_POST['customamount'])){
            $amount = intval($_POST['customamount']);
            $items = array();

            //add item for price
            $items = array_merge($items, array( array("Title" => "$" . strval($amount) . " of Project Time Added To Account", "Description" => "$" . strval($amount) . " of Project Time Added To Account", "Price" => doubleval($amount), "Quantity" => 1) )); //priced

            if ($this->data['vars']['my_user_type'] == "client"){
                $clientID = $this->data['vars']['my_id'];
                $invoiceUnqiueID = $this->createInvoice($clientID, $items, $amount); //created invoice returns unqiue id
                header("Location: https://pms.isodeveloper.com/client/pay/" . $invoiceUnqiueID);
                exit;
            }
        }

        //load view
        $this->__flmView('client/main');

    }


        /**
     * list a members own projects
     */
    function __listProjects()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /client/projects/list/in-progress/0
        * (2)->controller
        * (3)->router
        * (4)->status (open/closed)
        * (5)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //uri segments
        $status = $this->uri->segment(4);
        $offset = (is_numeric($this->uri->segment(5))) ? $this->uri->segment(5) : 0;

        //additional data
        $members_id = $this->data['vars']['my_id'];

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'projects';
        $this->data['blocks']['projects'] = $this->projects_model->searchProjects($offset, 'search', $this->client_id, $status);
        $this->data['debug'][] = $this->projects_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->projects_model->searchProjects($offset, 'count', $this->client_id, $status);
        $this->data['debug'][] = $this->projects_model->debug_data;

        //pagination
        $config = pagination_default_config(); //
        $config['base_url'] = site_url("/client/projects/list/$status");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 5; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //visibility
        if ($rows_count > 0) {
            //show side menu
            $this->data['visible']['wi_projects_table'] = 1;
        } else {
            //show mothing found
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

        //append to main title
        switch ($status) {

            case 'in-progress':
                $this->data['vars']['main_title'] .= ' - ' . $this->data['lang']['lang_in_progress'];
                break;

            case 'closed':
                $this->data['vars']['main_title'] .= ' - ' . $this->data['lang']['lang_closed'];
                break;

            case 'behind-schedule':
                $this->data['vars']['main_title'] .= ' - ' . $this->data['lang']['lang_behind_schedule'];
                break;
        }

    }
    //END OF LIST FUINCTION//

    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function generateRandomIntString($length = 10) {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function createInvoice($clientID, $items, $price){
        $invoiceProjectID = 0; //Default
        $invoiceCustomID = $this->generateRandomString(7) . "-" . $this->generateRandomIntString(2);
        $invoiceUnquieID = $this->generateRandomString(6);
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
     * show the home page 
     *
     */
    function __loadHome()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load members projects
        $this->__clientsProjects();

        //due invoices
        $this->__dueInvoices();

        //display timeline
        $this->__getEventsTimeline();

    }

    /**
     * eload 2 of my projects
     */
    function __clientsProjects()
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
    function __dueInvoices()
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
    function __getEventsTimeline()
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
    function __prepEvents($thedata = '')
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

/* End of file home.php */
/* Location: ./application/controllers/client/home.php */
