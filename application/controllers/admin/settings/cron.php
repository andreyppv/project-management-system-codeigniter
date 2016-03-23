<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * class for perfoming all Cron Settings related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Cron extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.cron.html';

        //css settings
        $this->data['vars']['css_menu_topnav_settings'] = 'nav_alternative_controls_active'; //menu
        $this->data['vars']['css_menu_heading_settings'] = 'heading-menu-active'; //menu
        $this->data['vars']['css_menu_settings'] = 'open'; //menu
        
        //PERMISSIONS CHECK - GENERAL
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            redirect('/admin/error/permission-denied');
        }
    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
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

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_settings_cron_jobs'];

        //re-route to correct method
        switch ($action) {
            case 'view':
                $this->__viewSettings();
                break;
        }

        //css - active tab
        $this->data['vars']['css_active_tab_cron'] = 'tab-active';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * load general settings
     *
     * 
     * 
     * 
     */
    function __viewSettings()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //nothing much here
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

/* End of file xyz.php */
/* Location: ./application/controllers/admin/xyz.php */
