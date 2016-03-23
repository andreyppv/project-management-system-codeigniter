<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Error related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Error extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . 'error.html';

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_error'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-warning-sign"></i>';

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
        $this->__commonClient_LoggedInCheck();

        //show error
        $this->__showErrorPage();

        //load view
        $this->__flmView('client/main');

    }

    /**
     * displays an error message based on uri segment 3 data
     * this is normally due to redirect from controller. Example: redirect('admin/error/not-allowed');
     *
     */
    function __showErrorPage()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //error messages
        switch ($this->uri->segment(3)) {

            case 'not-found':
                $this->data['vars']['notification'] = $this->data['lang']['lang_requested_item_not_found'];
                break;

            case 'not-allowed':
                $this->data['vars']['notification'] = $this->data['lang']['lang_requested_item_not_found'];
                break;

            case 'permission-denied':
                $this->data['vars']['notification'] = $this->data['lang']['lang_permission_denied_info'];
                break;

            case 'permission-denied-or-not-found':
                $this->data['vars']['notification'] = $this->data['lang']['lang_permission_denied_or_not_found'];
                break;

            default:
                $this->data['vars']['notification'] = $this->data['lang']['lang_error_occurred_info'];
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

/* End of file error.php */
/* Location: ./application/controllers/client/error.php */
