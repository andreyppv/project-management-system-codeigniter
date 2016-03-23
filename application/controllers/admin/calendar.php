<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Calendar related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Calendar extends MY_Controller
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
        $this->data['vars']['css_menu_heading_calendar'] = 'heading-menu-active'; //menu
        $this->data['vars']['css_menu_calendar'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_calendar'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-calendar"></i>';
        
        $this->data['vars']['menu'] = 'calendar';
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
//        $this->__pulldownLists();

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'details':
                $this->__details();
                break;

            default:
                $this->__list();
        }

        //load view
        $this->__flmView('admin/main');

    }


    /**
     * example of a paginated method
     */
    function __list()
    {
        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'calendar.html';
		
        //profiling
        $this->data['controller_profiling'][] = __function__;
    }


    function __details()
    {
        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'calendar.details.html';
		
        //profiling
        $this->data['controller_profiling'][] = __function__;
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

/* End of file mytasks.php */
/* Location: ./application/controllers/admin/mytasks.php */
