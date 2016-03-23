<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * class for perfoming all Formfields Settings related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Formfields extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.formfields.html';
        $this->data['vars']['css_menu_heading_settings'] = 'heading-menu-active'; //menu
        $this->data['vars']['css_menu_settings'] = 'open'; //menu
        
        //css settings
        $this->data['vars']['css_menu_topnav_settings'] = 'nav_alternative_controls_active'; //menu

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
        $action = $this->uri->segment(4);

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_settings_additional_form_fields'];

        //re-route to correct method
        switch ($action) {
            case 'edit':
                $this->__editSettings();
                break;

            case 'view':
                $this->__viewSettings();
                break;

            default:
                $this->__viewSettings();
        }

        //css - active tab
        $this->data['vars']['css_active_tab_formfields'] = 'tab-active';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * load current settings for clients_optionalfields & projects_optionalfields
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

        //------get all data for clients-------
        $result = $this->clientsoptionalfields_model->optionalFields('all');
        $this->data['debug'][] = $this->clientsoptionalfields_model->debug_data;

        //create tbs merge fields for each row (3 rows)
        for ($i = 1; $i < 4; $i++) {
            $e = $i - 1; //go back one number as array start from '0'
            $this->data['reg_fields'][] = "cf$i";
            $this->data['fields']["cf$i"] = $result[$e];
        }

        //------get all data for projects------
        $result = $this->projectsoptionalfields_model->optionalFields('all');
        $this->data['debug'][] = $this->projectsoptionalfields_model->debug_data;

        //create tbs merge fields for each row (5 rows)
        for ($i = 1; $i < 6; $i++) {
            $e = $i - 1; //go back one number as array start from '0'
            $this->data['reg_fields'][] = "pf$i";
            $this->data['fields']["pf$i"] = $result[$e];
        }

    }

    /**
     * edit clients_optionalfields & projects_optionalfields
     *
     * 
     * 
     * 
     */
    function __editSettings()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        if (!isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('edit', 'view', $this_url);
            redirect($redirect);
        }

        /*
        *---------------------------------------------------------------------
        * UPDATE ALL THE ROWS, 1 AT A TIME
        * we are updating 3 rows and so we have to do one at a time.
        * We set the actual post data as if its one row being posted/updates
        * Then loop through all 3 rows
        * In the end, the model sql will see this as one form being posted 
        * at a time. (perhaps a 'multiple update' sql query in future??)
        *--------------------------------------------------------------------
        */
        for ($i = 1; $i < 4; $i++) {

            //artificially set post data from actual
            $_POST['clients_optionalfield_title'] = $_POST["clients_optionalfield_title$i"];
            $_POST['clients_optionalfield_require'] = $_POST["clients_optionalfield_require$i"];
            $_POST['clients_optionalfield_status'] = $_POST["clients_optionalfield_status$i"];

            $result = $this->clientsoptionalfields_model->editSettings("clients_optionalfield$i");
            $this->data['debug'][] = $this->clientsoptionalfields_model->debug_data;
        }

        for ($i = 1; $i < 6; $i++) {

            //artificially set post data from actual
            $_POST['projects_optionalfield_title'] = $_POST["projects_optionalfield_title$i"];
            $_POST['projects_optionalfield_require'] = $_POST["projects_optionalfield_require$i"];
            $_POST['projects_optionalfield_status'] = $_POST["projects_optionalfield_status$i"];

            $result = $this->projectsoptionalfields_model->editSettings("projects_optionalfield$i");
            $this->data['debug'][] = $this->projectsoptionalfields_model->debug_data;
        }

        $this->__viewSettings();
    }

    /**
     * loads the view
     *
     * @param string $view the view to load
     */
    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']); //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file xyz.php */
/* Location: ./application/controllers/admin/xyz.php */
