<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Myprojects related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Myprojects extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'myprojects.new.html';

        //css settings
        $this->data['vars']['css_menu_heading_myprojects'] = 'heading-menu-active'; //menu
        $this->data['vars']['css_menu_myprojects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_my_projects'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';
        
        $this->data['vars']['menu'] = 'myprojects';

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

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'list':
                $this->__listProjects();
                break;

            default:
                $this->__listProjects();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * list a members own projects
     */
    function __listProjects()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/myprojects/list/in-progress/0
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
        $this->data['reg_blocks'][] = 'myprojects';
        $this->data['reg_blocks'][] = 'myprojects2';
        $this->data['blocks']['myprojects'] = $this->project_members_model->membersProjects($offset, 'search', $members_id, $status);
        $this->data['blocks']['myprojects2'] = $this->project_members_model->membersProjects($offset, 'search', $members_id, $status);
        $this->data['debug'][] = $this->project_members_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->project_members_model->membersProjects($offset, 'count', $members_id, $status);
        $this->data['debug'][] = $this->project_members_model->debug_data;

        //pagination
        $config = pagination_default_config(); //
        $config['base_url'] = site_url("/admin/myprojects/list/$status");
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

/* End of file myprojects.php */
/* Location: ./application/controllers/admin/myprojects.php */
