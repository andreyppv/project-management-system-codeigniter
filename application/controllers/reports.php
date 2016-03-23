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
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.html';
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
                
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'report.freelancers.html';
                break;

            case 'clients':
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

}

/* End of file bugs.php */
/* Location: ./application/controllers/admin/bugs.php */
