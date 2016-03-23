<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all payments related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Payments extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'payments.html';

        //css settings
        $this->data['vars']['css_menu_payments'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_payments'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-credit-card"></i>';

    }

    /**
     * This is our re-routing function and is the inital function called
     */
    public function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //create pulldown lists
        $this->__pulldownLists();
        //uri - project segment
        $project_id = (int) $this->uri->segment(3);
        //uri - action segment
        $action = $this->uri->segment(4);

        //re-route to correct method
        switch ($action) {

            default:
            case 'view':
                $this->__viewPayments();
                break;

            case 'search-payments':
                $this->__cachedFormSearch();
                break;

            case 'add-payment':
                $this->__addPayment();
                break;
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * list/search for all payments
     */
    public function __viewPayments()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/payments/21/view/54/desc/sortby_id/0
        * (2)->controller
        * (3)->project id
        * (4)->route
        * (5)->search id
        * (6)->sort_by
        * (7)->sort_by_column
        * (8)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //uri segments
        $project_id = (is_numeric($this->uri->segment(3))) ? $this->uri->segment(3) : 0;
        $search_id = (is_numeric($this->uri->segment(5))) ? $this->uri->segment(5) : 0;
        $sort_by = ($this->uri->segment(6) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(7) == '') ? 'sortby_id' : $this->uri->segment(7);
        $offset = (is_numeric($this->uri->segment(8))) ? $this->uri->segment(8) : 0;

        $this->data['vars']['project_id'] = $project_id;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'all_payments';
        if($project_id) {
            $this->data['blocks']['all_payments'] = $this->payments_model->searchPayments($offset, 'search', $project_id, 'project');
            $rows_count = $this->payments_model->searchPayments($offset, 'count', $project_id, 'project');
        } else {
            $this->data['blocks']['all_payments'] = $this->payments_model->searchPayments($offset, 'search', '', 'all');
            $rows_count = $this->payments_model->searchPayments($offset, 'count', '', 'all');
        }
        //print_r($this->data['vars']);
        $this->data['debug'][] = $this->payments_model->debug_data;

        //count results rows - used by pagination class
        $this->data['vars']['all_payments_count'] = $rows_count;
        $this->data['debug'][] = $this->payments_model->debug_data;

        //pagination
        $config = pagination_default_config();
        $config['base_url'] = site_url("admin/payments/$id/view/$search_id/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 8; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        //the array names mustbe same as used in payments_model
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_id',
            'sortby_date',
            'sortby_amount',
            'sortby_method',
            'sortby_project',
            'sortby_invoice',
            'sortby_client');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/payments/$id/view/$search_id/$link_sort_by/$column/$offset");
        }

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blocks']['all_payments'])) {
            $this->data['visible']['wi_payments_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     * takes all posted (search form) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     */
    public function __cachedFormSearch()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //create array containg all post data in format:: array('name'=>$this->input->post('name));
        $search_array = array();
        foreach ($_POST as $key => $value) {
            $search_array[$key] = $this->input->post($key);
        }

        //save serch query in database & get id of database record
        $search_id = $this->input->save_query($search_array);

        //uri - project segment
        $project_id = (int) $this->uri->segment(3);

        //change url to "list" and redirect with cached search id.
        redirect("admin/payments/$project_id/view/$search_id");
    }


    /**
     * add new payment
     */
    public function __addPayment()
    {
        //uri - project segment
        $project_id = (int) $this->uri->segment(3);
        //search client_id
        $client_id = $this->payments_model->getClientID($project_id);
        $amount_paid = (is_numeric($this->input->post('amount_paid'))) ? $this->input->post('amount_paid') : 0;

        //create array containg all post data in format:: array('name'=>$this->input->post('name));
        $thedata = array();
        $thedata['payments_invoice_id'] = 0;
        $thedata['payments_project_id'] = $project_id;
        $thedata['payments_client_id'] = $client_id;
        $thedata['payments_amount'] = $amount_paid;
        $thedata['payments_currency_code'] = 'USD';
        $thedata['payments_transaction_id'] = random_string('alnum', 15);
        $thedata['payments_date'] = $this->input->post('date');
        $thedata['payments_method'] = 'Credit';
        $thedata['payments_notes'] = $this->input->post('note');


        //save data
        $this->payments_model->addPayment($thedata);
        $this->payments_model->changeClientBalance($client_id, $amount_paid);
        redirect("admin/payments/$project_id/view/0/desc/sortby_id/0");
    }

    /**
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     */
    public function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //[all_projects]
        $data = $this->projects_model->allProjects('projects_title', 'ASC');
        $this->data['debug'][] = $this->projects_model->debug_data;
        $this->data['lists']['all_projects'] = create_pulldown_list($data, 'projects', 'id');

    }

    /**
     * loads the view
     *
     * @param string $view the view to load
     */
    public function __flmView($view = '')
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']);

        //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file allpayments.php */
/* Location: ./application/controllers/admin/payments.php */
