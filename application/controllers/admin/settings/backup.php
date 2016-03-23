<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * class for perfoming all Backup Settings related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Backup extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.backup.html';

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
        $action = $this->uri->segment(4);

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_settings_backup'];

        //re-route to correct method
        switch ($action) {

            case 'view':
                $this->__viewBackups();
                break;

            case 'download':
                $this->__downloadbackup();
                break;

            default:
                $this->__viewBackups();
        }

        //css - active tab
        $this->data['vars']['css_active_tab_backup'] = 'tab-active';

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
    function __viewBackups()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get all the files in the backup folder
        if ($next) {

            //get list of all files in database directory
            $this->data['reg_blocks'][] = 'backup_dir';
            $this->data['blocks']['backup_dir'] = get_dir_file_info(FILES_DATABASE_BACKUP_FOLDER, $top_level_only = true);

            //ignore index.html file
            unset($this->data['blocks']['backup_dir']['index.html']);
            
            /*---------------------sort files array by ['date'] value--------------------------------------
            * this little setup will take the array returned by codeigniter get_dir_file_info() function
            * and sorts the multidimensional arr according to the ['date'] value of each sub array.
            * essentially sorting the files array by 'date modified' of each file
            */
            function sortByTimestamp($a, $b)
            {
                return $b['date'] - $a['date']; //DESC (newest first)
                //return $a['date'] - $b['date']; //ASC (oldest first)
            }
            usort($this->data['blocks']['backup_dir'], 'sortByTimestamp');
            //-------------sort files array by ['date'] value--------------------------------

            //check if we have any file
            if (count($this->data['blocks']['backup_dir']) == 0 || !is_array($this->data['blocks']['backup_dir'])) {

                //now file found
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_backup_files_were_found']);

                //halt
                $next = false;

            } else {

                //show list
                $this->data['visible']['wi_backups_table'] = 1;

            }
        }

        //if we have more than 10 files, create
        if ($next) {

            if (count($this->data['blocks']['backup_dir']) > 10) {

                //lop and delete (assumes files are sorted in order of date ^^above)
                for ($i = 10; $i <= $this->data['blocks']['backup_dir'] - 1; $i++) {

                    //unlink the file

                    //pop it off the array

                }
            }

        }
    }

    /**
     * download backup file
     *
     * 
     * 
     * 
     */
    function __downloadbackup()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //download helper

        //flow control
        $next = true;

        //download helper
        $this->load->helper('download');

        $file_name = $this->uri->segment(5);

        //attempt to download
        if (is_file(FILES_DATABASE_BACKUP_FOLDER . $file_name)) {

            //force browser to download file
            $file_data = file_get_contents(FILES_DATABASE_BACKUP_FOLDER . $file_name);
            force_download($file_name, $file_data);

        } else {

            //redirect
            redirect('admin/error/not-found');

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
