<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Mytasks related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Tasksfilter extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'tasksfilter.html';

        //css settings
        $this->data['vars']['css_menu_heading_mytasks'] = 'heading-menu-active'; //menu
        $this->data['vars']['css_menu_mytasks'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_my_tasks'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-list-ul"></i>';

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

        //create pulldown lists
        $this->__pulldownLists();

        $this->__listTasks();

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * example of a paginated method
     */
    public function __listTasks()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/tasksfileter/filter/54/desc/sortby_taskid/0
        * (2)->controller
        * (3)->project_id
        * (4)->filter
        * (5)->sort_by
        * (6)->sort_by_column
        * (7)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //refresh task status (all tasks)
        $this->refresh->taskStatus('all');

        //uri segments
        $project_id = (is_numeric($this->uri->segment(3))) ? $this->uri->segment(3) : 0;
        $filter = $this->uri->segment(4);
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_taskid' : $this->uri->segment(6);
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        $search_array = array(
            'tasks_project_id' => $project_id
        );
        switch ($filter) {
            case 'needing_rewiew':
                $search_array['tasks_status'] = 'completed';
                $search_array['memo'] = 'not null';
                break;
        }
        $_GET = $search_array;

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'tasks';
        $this->data['blocks']['tasks'] = $this->tasks_model->searchTasks($offset, 'search', 'all');
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->tasks_model->searchTasks($offset, 'count', 'all');
        $this->data['vars']['my_tasks_count'] = $rows_count;
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //pagination
        $config = pagination_default_config(); //load all other settings from helper
        $config['base_url'] = site_url("admin/tasksfilter/$project_id/$filter/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 7; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        //the array names mustbe same as used in clients_model.php->searchClients()
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_taskid',
            'sortby_taskstatus',
            'sortby_taskduedate',
            'sortby_projectid');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/tasksfilter/$project_id/$filter/$link_sort_by/$column/$offset");
        }

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blocks']['tasks'])) {
            $this->data['visible']['wi_my_tasks_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

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

        //[memebers projects]
        $data = $this->project_members_model->membersProjects(0, 'list', $this->data['vars']['my_id'], 'open');
        $this->data['debug'][] = $this->project_members_model->debug_data;
        $this->data['lists']['all_projects'] = create_pulldown_list($data, 'projects', 'id');

    }

    /**
     * log any error message into the log file
     *
     */
    protected function __errorLogging($theline = '', $thefunction = '', $thefile = '', $themessage = 'system error')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        $message_log = "[FILE: $thefile]  [LINE: $theline]  [FUNCTION: $thefunction]  [MESSAGE: $themessage]";
        log_message('error', $message_log);

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

/* End of file mytasks.php */
/* Location: ./application/controllers/admin/mytasks.php */
