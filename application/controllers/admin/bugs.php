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
class Bugs extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'bugs.html';

        //css settings
        $this->data['vars']['css_submenu_bugs'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_bugs'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_bugs'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-bug"></i>';
        
        $this->data['vars']['menu'] = 'bugs';
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
        if ($this->data['permission']['view_item_bugs'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //create pulldown lists
        $this->__pulldownLists();

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'list':
                $this->__listBugs();
                break;

            case 'list-project':
                $this->__listBugsProject();
                break;

            case 'view':
                $this->__viewBug();
                break;

            case 'update':
                $this->__updateBug();
                break;

            case 'report-bug':
                $this->__reportNewBug();
                break;

            case 'edit':
                $this->__editBug();
                break;

            case 'search-bugs':
                $this->__cachedFormSearch();
                break;

            default:
                $this->__listBugs();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * report a new bug
     *
     */
    protected function __reportNewBug()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //validate form
        if ($next) {
            if (!$this->__flmFormValidation('report_bug')) {
                //show error
                $this->notices('error', $this->form_processor->error_message, 'html');
                //halt
                $next = false;
            }
        }

        //SANITY: validate clients project is correct
        /*if ($next) {
            if (!in_array($this->input->post('bugs_project_id'), $this->data['my_clients_project_array'])) {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'html');
                //halt
                $next = false;
            }
        }*/

        //add new bug
        if ($next) {

            $result = $this->bugs_model->addBug();
            $this->data['debug'][] = $this->bugs_model->debug_data;
            if ($result) {
                //success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty'); //noty or html
            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty'); //noty or html

                //halt
                $next = false;
            }

        }

        //track event
        if ($next) {
            //events tracker
            $this->__eventsTracker('new_bug', array('target_id' => $result));
        }

        //show bugs list
        $this->__listbugs();

    }


    protected function __listBugsProject()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/bugs/list-project/21
        * (2)->controller
        * (3)->router
        * (4)->project id
        * (5)->sort_by
        * (6)->sort_by_column
        * (7)->offset
        ** -----------------------------------------*/
        //profiling
        $this->data['controller_profiling'][] = __function__;

        $project_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;
        $search_array = array(
            'bugs_project_id' => $project_id
        );

        //save serch query in database & get id of database record
        $search_id = $this->input->save_query($search_array);

        //change url to "list" and redirect with cached search id.
        redirect("admin/bugs/list/$search_id");
    }


    /**
     * list all bugs
     *
     */
    protected function __listBugs()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/bugs/list/54/desc/sortby_project/0
        * (2)->controller
        * (3)->router
        * (4)->search id
        * (5)->sort_by
        * (6)->sort_by_column
        * (7)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show search form
        $this->data['visible']['wi_bugs_search'] = 1;

        //uri segments
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_taskid' : $this->uri->segment(6);
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);

		$list=$this->uri->segment(4);
		if($list=="all")
		{
			$this->data['vars']['bugs_list'] = 'Show pending bugs';
			$this->data['vars']['bugs_list_link'] = '';
			
		}
		else {
			$this->data['vars']['bugs_list'] = 'Show all bugs';
			$this->data['vars']['bugs_list_link'] = 'all';
		}
        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'bugs';
        $this->data['blocks']['bugs'] = $this->bugs_model->searchBugs($offset, 'search', $list);
        $this->data['debug'][] = $this->bugs_model->debug_data;
		//echo $this->bugs_model->db->last_query(); exit;
		
        //count results rows - used by pagination class
        $rows_count = $this->bugs_model->searchBugs($offset, 'count', $list);
        
        $this->data['vars']['count_all_bugs'] = $rows_count;
        $this->data['debug'][] = $this->bugs_model->debug_data;
		//die(var_dump($rows_count));

        //pagination
        $config = pagination_default_config(); //load all other settings from helper
        $config['base_url'] = site_url("admin/bugs/list/$search_id/$sort_by/$sort_by_column");
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
            $this->data['vars'][$column] = site_url("admin/bugs/list/$search_id/$link_sort_by/$column/$offset");
        }

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blocks']['bugs'])) {
            $this->data['visible']['wi_bugs_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     * load a bug
     *
     */
    protected function __viewBug()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //bug id
        $bug_id = $this->uri->segment(4);

        //get bug
        if ($next) {
            $this->data['reg_fields'][] = 'bug';
            $this->data['fields']['bug'] = $this->bugs_model->getBug($bug_id);
            $this->data['debug'][] = $this->bugs_model->debug_data;

            //results
            if ($this->data['fields']['bug']) {
                //show bug
                $this->data['visible']['wi_show_bug'] = 1;

                //show comment
                if ($this->data['fields']['bug']['bugs_comment'] != '') {
                    $this->data['visible']['wi_show_bug_comment'] = 1;
                }
				//get results and save for tbs block merging
		        $this->data['reg_blocks'][] = 'messages';
		        $this->data['blocks']['messages'] = $this->messages_model->listComments($offset, 'search', $bug_id);
		        $this->data['debug'][] = $this->messages_model->debug_data;
            } else {
                redirect('/admin/error/not-found');
            }
        }
    }

    /**
     * update a bug
     *
     */
    protected function __updateBug()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //PERMISSIONS CHECK - GENERAL
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['permission']['edit_item_bugs'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //flow control
        $next = true;

        //prevent direct acccess
        if (!isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('update', 'view', $this_url);
            redirect($redirect);
        }

        //update bug
        if ($next) {
            $result = $this->bugs_model->updateBug();
            $this->data['debug'][] = $this->bugs_model->debug_data;

            //results
            if ($result) {
                //success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');

                //events tracker (resolved & not-a-bug, responses)
                if ($this->input->post('bugs_status') == 'resolved' || $this->input->post('bugs_status') == 'not-a-bug') {
                    $this->__eventsTracker('resolved-bug', array());
                }

            } else {
                //failed
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty');
            }
        }

        //view bug
        $this->__viewBug();

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
        redirect("admin/bugs/list/$search_id");

    }

    /**
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    protected function __flmFormValidation($form = '')
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

            //check required fields
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

        if ($form == 'report_bug') {

            //check required fields
            $fields = array('bugs_title' => $this->data['lang']['lang_title'], 'bugs_description' => $this->data['lang']['lang_description']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
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
     * records new project events (timeline)
     *
     * @param	string   $type identify the loop to run in this function
     * @param   array    $vents_data an optional array that can be used to directly pass data]      
     */
    protected function __eventsTracker($type = '', $events_data = array())
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //--------------record a new event-----------------------
        if ($type == 'resolved-bug') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->input->post('bugs_project_id');
            $events['project_events_type'] = 'bug';
            $events['project_events_details'] = $this->input->post('bugs_title');
            $events['project_events_action'] = 'lang_tl_resolved_bug';
            $events['project_events_target_id'] = $this->input->post('bugs_id');
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'team';

            //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;
        }
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
