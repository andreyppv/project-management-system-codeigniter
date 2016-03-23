<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * class for perfoming all Updates Settings related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Updates extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.updates.html';

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
        $this->data['vars']['main_title'] = $this->data['lang']['lang_settings_updates'];

        //re-route to correct method
        switch ($action) {
            case 'check':
                $this->__checkUpdates();
                break;

            default:
                $this->__checkUpdates();
        }

        //css - active tab
        $this->data['vars']['css_active_tab_updates'] = 'tab-active';

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
    function __checkUpdates()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        $updates_signature = 'FD-3Ygr3U';
        $product_code = $this->data['settings_general']['product_purchase_code'];
        $version = $this->data['version']['number'];
        $installed_date = $this->data['version']['date'];
        $install_type = $this->data['version']['install_type'];
        $installed_url = rtrim(base_url(), "/");
        $ip_address = $_SERVER['REMOTE_ADDR'];

        //connect to server
        $ch = curl_init();
        $postdata = "product_code=$product_code&updates_signature=$updates_signature&version=$version&installed_date=$installed_date&install_type=$install_type&installed_url=$installed_url&ip_address=$ip_address";
        curl_setopt($ch, CURLOPT_URL, 'http://updates.nextloop.net/update.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $result = curl_exec($ch);
        curl_close($ch);

        //get the response from update server
        if ($result != '') {

            //decode the server response
            $update_result = json_decode($result, true);

            $this->data['vars']['latest_version'] = $update_result['latest_version'];
            $this->data['vars']['update_notes'] = $update_result['update_notes'];
            $this->data['vars']['update_download_link'] = $update_result['update_download_link'];

            //shoudl we show download link
            if ($update_result['update_available'] == 1) {
                $this->data['visible']['updates_download_button'] = 1;
            }

        } else {

            //an error occurred communicating with server
            $this->data['vars']['update_response'] = $this->data['lang']['lang_update_server_error'];
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

/* End of file xyz.php */
/* Location: ./application/controllers/admin/xyz.php */
