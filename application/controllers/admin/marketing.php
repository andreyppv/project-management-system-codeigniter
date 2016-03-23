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
class Marketing extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'marketing.html';

        //css settings
        $this->data['vars']['css_menu_heading_myprojects'] = 'heading-menu-active'; //menu
        $this->data['vars']['css_menu_myprojects'] = 'open'; //menu

        //default page title
        //$this->data['vars']['main_title'] = $this->data['lang']['lang_my_projects'];
        $this->data['vars']['main_title'] = 'Sales';
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';
		
        $this->data['vars']['menu'] = 'marketing';
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
                $this->__listSales();
                break;

            default:
                $this->__listSales();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * list a members own projects
     */
    function __listSales()
    {

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
