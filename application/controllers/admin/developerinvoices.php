<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all clients related functions
 */
class DeveloperInvoices extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'developerinvoices.html';

        //css settings
        $this->data['vars']['css_menu_dashboard'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_dashboard'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-home"></i>';
        
        $this->data['vars']['menu'] = 'developerinvoices';
    }

    /**
     * This is our re-routing function and is the inital function called
     * 
     */
    function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        $this->getInvoices();
        $this->__flmView('admin/main');

    }

    function getTeamMember($db, $id){
        $prepared = $db->prepare("SELECT * FROM team_profile WHERE team_profile_id = ?");
        $prepared->execute(array($id));
        return $prepared->fetch(PDO::FETCH_ASSOC);
    }

    function getInvoices(){
        //profiling
        $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
        $rawInvoices = $db->query("SELECT * FROM developer_invoices")->fetchAll();
        $this->data['controller_profiling'][] = __function__;
        //$this->data['vars']['invoices'] = $invoices;

        $invoices = array();

        foreach ($rawInvoices as $invoice) {
            $invoices = array_merge($invoices, array( 
                array( 
                    "id" => $invoice['id'],
                    "teammember" => $this->getTeamMember($db, $invoice['teammember_id'])['team_profile_full_name'], 
                    "total" => round($invoice['total'],2), 
                    "date" => $invoice['date'], 
                    "status" => $invoice['status'] 
                ) 
            ));
        }

        $this->data['reg_blocks'][] = 'invoices';
        $this->data['blocks']['invoices'] = $invoices;
    }

    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']); //complete the view
        $this->__commonAll_View($view);
    }
}