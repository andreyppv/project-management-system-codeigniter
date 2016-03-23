<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Quotations related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Quotations extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'quotations.html';
        $this->data['vars']['css_submenu_quotations'] = 'style="display:block; visibility:visible;"';

        //css settings
        $this->data['vars']['css_menu_quotations'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_quotations'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-paste"></i>';
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
        $this->__commonAdmin_LoggedInCheck();

        //create pulldown lists
        $this->__pulldownLists();

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'list':
                $this->__listQuotations();
                break;

            case 'search':
                $this->__cachedFormSearch();
                break;

            default:
                $this->__listQuotations();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * list/search of all quotations
     */
    function __listQuotations()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/quotations/list/54/desc/sortby_id/0
        * (2)->controller
        * (3)->router
        * (4)->search id
        * (5)->sort_order
        * (6)->sort_by_column
        * (7)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //uri segments
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_taskid' : $this->uri->segment(6);
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'quotations';
        $this->data['blocks']['quotations'] = $this->quotations_model->searchQuotations($offset, 'search');
        $this->data['debug'][] = $this->quotations_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->quotations_model->searchQuotations($offset, 'count');
        $this->data['vars']['count_all_quotations'] = $rows_count;
        $this->data['debug'][] = $this->quotations_model->debug_data;

        //pagination
        $config = pagination_default_config(); //load all other settings from helper
        $config['base_url'] = site_url("admin/quotations/list/$search_id/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 7; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        //the array names mustbe same as used in the model
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_id',
            'sortby_date',
            'sortby_status',
            'sortby_form_title');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/quotations/list/$search_id/$link_sort_by/$column/$offset");
        }

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blocks']['quotations'])) {
            $this->data['visible']['wi_quotations_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     * takes all posted (search form) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     *
     */
    function __cachedFormSearch()
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
        redirect("admin/quotations/list/$search_id");

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

/* End of file quotations.php */
/* Location: ./application/controllers/admin/quotations.php */
