<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Bugs related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Reports extends MY_Controller
{

    /**
     * constructor method
     */
    public function __construct()
    {
        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;


        //css settings
        //$this->data['vars']['css_submenu_bugs'] = 'style="display:block; visibility:visible;"';
        //$this->data['vars']['css_menu_bugs'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_report'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-screenshot"></i>';
        
        $this->data['vars']['menu'] = 'reports';
        
        //PERMISSIONS CHECK - ADMIN ONLY
        if ($this->data['vars']['my_group'] != 1) {
            redirect('/admin/error/permission-denied');
        }
    }

    
    public function home(){
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.html';
        $this->__flmView('admin/main');
    }
    
    public function sales(){
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.sales.html';
        $this->__flmView('admin/main');
    }
    public function freelancers(){
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.freelancers.html';
        $this->__flmView('admin/main');
    }
    public function clients(){
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.clients.html';
        $this->__flmView('admin/main');
    }
    public function mytasks(){
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.mytasks.html';
        $this->__flmView('admin/main');
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
        $this->__commonAdmin_LoggedInCheck();

        //PERMISSIONS CHECK - GENERAL
        //do this check after __commonAll_ProjectBasics()
        /*if ($this->data['permission']['view_item_bugs'] != 1) {
            redirect('/admin/error/permission-denied');
        }*/

        //create pulldown lists
        $this->__pulldownLists();

        //uri - action segment
        
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'home':      
                $this->data['reg_blocks'][] = 'totals7';
                $this->data['blocks']['totals7'] = $this->reports_model->getSalesTotal7();
                $this->data['reg_blocks'][] = 'totals30';
                $this->data['blocks']['totals30'] = $this->reports_model->getSalesTotal30();
                
                $this->data['reg_blocks'][] = 'totalBySales';
                $this->data['blocks']['totalBySales'] = $this->reports_model->getSalesTotalBySales();
                
                $this->data['reg_blocks'][] = 'clients';
                $this->data['blocks']['clients'] = $this->reports_model->getClients();
                
                $this->data['reg_blocks'][] = 'freelancers';
                $this->data['blocks']['freelancers'] = $this->reports_model->getFreelancers();
                
                $this->data['reg_blocks'][] = 'myTasks';
                $this->data['blocks']['myTasks'] = $this->reports_model->getMyTasks();
                
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.html';
                
                //added by Tomasz
                $this->_get_sales_info();
                $this->_get_team_info();
                //end by Tomasz
                
                break;

            case 'sales':
                $this->data['reg_blocks'][] = 'openLeads';
                $this->data['blocks']['openLeads'] = $this->reports_model->getSalesOpenLeads();
                
                $this->data['reg_blocks'][] = 'hotLeads';
                $this->data['blocks']['hotLeads'] = $this->reports_model->getSalesHotLeads();
                
                $this->data['reg_blocks'][] = 'lost';
                $this->data['blocks']['lost'] = $this->reports_model->getSalesLost();
                
                $this->data['reg_blocks'][] = 'convertedLeads';
                $this->data['blocks']['convertedLeads'] = $this->reports_model->getCovertedLeads();
                
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.sales.html';
                break;

            case 'freelancers':
                $this->data['reg_blocks'][] = 'freelancers';
                $this->data['blocks']['freelancers'] = $this->reports_model->getFreelancers();
                
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.freelancers.html';
                break;

            case 'clients':
                $this->data['reg_blocks'][] = 'clients';
                $this->data['blocks']['clients'] = $this->reports_model->getClients();
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.clients.html';
                break;
            case 'mytasks':
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.mytasks.html';
                break;
            default:
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.html';
                break;
        }

        //load view
        $this->__flmView('admin/main');

    }

    protected function __salesHotLeads()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/reports/sales/54/desc/sortby_project/0
        * (2)->controller
        * (3)->router
        * (4)->search id
        * (5)->sort_by
        * (6)->sort_by_column
        * (7)->offset
        ** -----------------------------------------*/

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.sales.html';

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show search form
        $this->data['visible']['wi_sales_search'] = 1;

        //uri segments
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_taskid' : $this->uri->segment(6);
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);

		$list = $this->uri->segment(5);

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'report';
        $this->data['blocks']['report'] = $this->reports_model->salesHotLeads($offset, 'search', $list);
        $this->data['debug'][] = $this->reports_model->debug_data;
		
		
        //count results rows - used by pagination class
        $rows_count = $this->reports_model->salesHotLeads($offset, 'count' );
        $this->data['vars']['count_all_mess'] = $rows_count;
        $this->data['debug'][] = $this->reports_model->debug_data;
		//die(var_dump($rows_count));

        //pagination
        $config = pagination_default_config(); //load all other settings from helper
        $config['base_url'] = site_url("admin/reports/sales/$search_id/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 7; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        //the array names mustbe same as used in the model
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_client',
            'sortby_project',
            'sortby_date',
            'sortby_status');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/reports/sales/$search_id/$link_sort_by/$column/$offset");
        }
    }


    /**
     * takes all posted (search form) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     *
     */
    protected function __cachedFormSearch()
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

        //change url to "list" and redirect with cached search id.
        redirect("admin/reports/list/$search_id");

    }


    /**
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     *
     */
    protected function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //[all_projects]
        $data = $this->projects_model->allProjects('projects_title', 'ASC');
        $this->data['debug'][] = $this->projects_model->debug_data;
        $this->data['lists']['all_projects'] = create_pulldown_list($data, 'projects', 'id');

        //[all_clients]
        $data = $this->clients_model->allClients('clients_company_name', 'ASC');
        $this->data['debug'][] = $this->clients_model->debug_data;
        $this->data['lists']['all_clients'] = create_pulldown_list($data, 'clients', 'id');
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

    // Added by Tomasz
    private function _get_sales_info()
    {
        $this->load->model('lead_model');
        $this->load->model('client_model');
        
        $search_type = $this->input->post('sales-search-type');
        $start_date = '';
        $end_date = '';
       
        if($search_type == 'year')
        {
            $start_date = date('Y-m-d', strtotime('-1 year'));
            $end_date = date('Y-m-d');
        }
        else if($search_type == 'month')
        {
            $start_date = date('Y-m-d', strtotime('-1 month'));
            $end_date = date('Y-m-d');
        }
        else if($search_type == 'week')
        {
            $start_date = date('Y-m-d', strtotime('-1 week'));
            $end_date = date('Y-m-d');
        }
        else if($search_type == 'range')
        {
            $start_date = $this->input->post('sales_start_date');
            $end_date = $this->input->post('sales_end_date');
        } 
        
        $where = array();
        $where2 = array();
        if($start_date != '') 
        {
            $where['leads_created >='] = $start_date;
            $where2['clients_date_created >='] = $start_date;
        }
        
        if($end_date != '') 
        {
            $where['leads_created <='] = $end_date; 
            $where2['clients_date_created <='] = $end_date;
        }          
        
        $total_lead_no = $this->lead_model
            ->where($where)
            ->count_all();
        $total_lead_value = $this->lead_model
            ->where($where)
            ->select_sum('leads_value', 'leads_value')
            ->find_all();
        
        $hot_lead_no = $this->lead_model
            ->where($where)
            ->where('leads_hot', '1')
            ->count_all();
        $hot_lead_value = $this->lead_model
            ->where($where)
            ->where('leads_hot', '1')
            ->select_sum('leads_value', 'leads_value') 
            ->find_all();
        
        $lost_lead_no = $this->lead_model
            ->where($where)
            ->where('leads_lost', '1')
            ->count_all();
        $lost_lead_value = $this->lead_model
            ->where($where)
            ->where('leads_lost', '1')
            ->select_sum('leads_value', 'leads_value') 
            ->find_all();
        
        $client_no = $this->client_model
            ->where($where2)
            ->count_all();
        $client_value =  $this->client_model
            ->where($where2)
            ->select_sum('clients_value', 'clients_value') 
            ->find_all(); 
                   
        $this->data['vars']['sales_start_date'] = $start_date;  
        $this->data['vars']['sales_end_date'] = $end_date;
        $this->data['vars']['sales_search_type'] = $search_type;
        
        $this->data['vars']['total_lead_no'] = $total_lead_no;
        $this->data['vars']['total_lead_value'] = is_null($total_lead_value[0]->leads_value) ? 0 : $total_lead_value[0]->leads_value;
        $this->data['vars']['hot_lead_no'] = $hot_lead_no;
        $this->data['vars']['hot_lead_value'] = is_null($hot_lead_value[0]->leads_value) ? 0 : $hot_lead_value[0]->leads_value;
        $this->data['vars']['lost_lead_no'] = $lost_lead_no;
        $this->data['vars']['lost_lead_value'] = is_null($lost_lead_value[0]->leads_value) ? 0 : $lost_lead_value[0]->leads_value;
        $this->data['vars']['client_no'] = $client_no;
        $this->data['vars']['client_value'] = is_null($client_value[0]->clients_value) ? 0 : $client_value[0]->clients_value;
    }
    
    private function _get_team_info()
    {
        $this->load->model('task_model');
        $this->task_model->set_alias();
        
        $search_type = $this->input->post('team-search-type');
        $start_date = '';
        $end_date = '';
       
        if($search_type == 'year')
        {
            $start_date = date('Y-m-d', strtotime('-1 year'));
            $end_date = date('Y-m-d');
        }
        else if($search_type == 'month')
        {
            $start_date = date('Y-m-d', strtotime('-1 month'));
            $end_date = date('Y-m-d');
        }
        else if($search_type == 'week')
        {
            $start_date = date('Y-m-d', strtotime('-1 week'));
            $end_date = date('Y-m-d');
        }
        else if($search_type == 'range')
        {
            $start_date = $this->input->post('team_start_date');
            $end_date = $this->input->post('team_end_date');
        } 
        
        $where = array();
        if($start_date != '') 
        {
            $where['created >='] = $start_date;
        }
        
        if($end_date != '') 
        {
            $where['created <='] = $end_date; 
        }          
        
        //get values here
        // -unassigned tasks
        $unassigned = $this->task_model
            ->select('COUNT(*) cnt, SUM(estimatedhours*bcat_rate) cost')
            ->join_billing_category()
            ->where($where)
            ->where('accepted', TASK_NOT_ASSIGNED)
            ->find_one_fill_zero();
        //print_r($unassigned); exit;
        
        // -unassigned tasks
        $assigned = $this->task_model
            ->select('COUNT(*) cnt, SUM(estimatedhours*bcat_rate) cost')
            ->join_billing_category()
            ->where($where)
            ->where('accepted', TASK_ASSIGNED)
            ->find_one_fill_zero();
        //print_r($unassigned); exit;
        
        // -unassigned tasks
        $select = 'COUNT(*) cnt, 
            SUM(estimatedhours) assigned_hours, SUM(estimatedhours*bcat_rate) assigned_cost, 
            SUM(hourslogged) worked_hours, SUM(hourslogged*bcat_rate) worked_cost';
        $progress = $this->task_model
            ->select($select)
            ->join_billing_category()
            ->where($where)
            ->where('accepted', TASK_PROGRESS)
            ->find_one_fill_zero();
        //print_r($unassigned); exit;
        
        // -pending tasks
        $select = 'COUNT(*) cnt, SUM(hourslogged) hours, SUM(hourslogged*hourlyrate) cost';
        $pending = $this->task_model
            ->select($select)
            ->join_assigned_to()
            //->join_billing_category() 
            ->where($where)
            ->where('accepted', TASK_PENDING_CLIENT_APPROVAL)
            ->find_one_fill_zero();
        //print_r($unassigned); exit;
        
        // -completed tasks
        $select = 'COUNT(*) cnt, SUM(hourslogged) worked_hours, 
            SUM(estimatedhours) assigned_hours, SUM(hourslogged*hourlyrate) worked_cost';
        $completed = $this->task_model
            ->select($select)
            ->join_assigned_to()
            //->join_billing_category() 
            ->where($where)
            ->where('accepted', TASK_PENDING_CLIENT_APPROVAL)
            ->find_one_fill_zero();
        //print_r($completed); exit;
                   
        $this->data['vars']['team_start_date'] = $start_date;  
        $this->data['vars']['team_end_date'] = $end_date;
        $this->data['vars']['team_search_type'] = $search_type;
        
        $this->data['vars']['task_unassigned_no'] = $unassigned->cnt;
        $this->data['vars']['task_unassigned_cost'] = $unassigned->cost;
        $this->data['vars']['task_assigned_no'] = $assigned->cnt;
        $this->data['vars']['task_assigned_cost'] = $assigned->cost;
        $this->data['vars']['task_progress_no'] = $progress->cnt;
        $this->data['vars']['task_progress_assigned_hours'] = $progress->assigned_hours;
        $this->data['vars']['task_progress_assigned_cost'] = $progress->assigned_cost;
        $this->data['vars']['task_progress_worked_hours'] = $progress->worked_hours;
        $this->data['vars']['task_progress_worked_cost'] = $progress->worked_cost;
        $this->data['vars']['task_pending_no'] = $pending->cnt;
        $this->data['vars']['task_pending_hours'] = $pending->hours;                
        $this->data['vars']['task_pending_cost'] = $pending->cost;                
        $this->data['vars']['task_completed_no'] = $completed->cnt;
        $this->data['vars']['task_completed_worked_hours'] = $completed->worked_hours;
        $this->data['vars']['task_completed_assigned_hours'] = $completed->assigned_hours;
        $this->data['vars']['task_completed_cost'] = $completed->worked_cost;
    }
    // End by Tomasz
}

/* End of file bugs.php */
/* Location: ./application/controllers/admin/bugs.php */
