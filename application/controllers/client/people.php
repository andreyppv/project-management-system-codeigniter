<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all People related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class People extends MY_Controller
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
        $this->data['template_file'] = PATHS_COMMON_THEME . 'people.modal.html';

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

        //uri - action segment
        $action = $this->uri->segment(3);

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_people'];

        //re-route to correct method
        switch ($action) {
            case 'team':
                $this->__teamProfile();
                break;

            case 'client':
                $this->__clientProfile();
                break;

            default:
                $this->__someDefault();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * load team members profile
     *
     */
    function __teamProfile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get users id
        $id = $this->uri->segment(4);

        //load users details
        $result = $this->teamprofile_model->teamMemberDetails($id);
        $this->data['debug'][] = $this->teamprofile_model->debug_data;

        //check data
        if ($result) {

            //set vars array
            $user_details['user_id'] = $result['team_profile_id'];
            $user_details['full_name'] = $result['team_profile_full_name'];
            $user_details['job_title'] = $result['team_profile_job_position_title'];
            $user_details['email_address'] = $result['team_profile_email'];
            $user_details['telephone'] = $result['team_profile_telephone'];
            $user_details['profile_avatar_filename'] = $result['team_profile_avatar_filename'];

            //add array to merge data
            $this->data['reg_fields'][] = 'people';
            $this->data['fields']['people'] = $user_details;

        } else {

            //show error
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     * load client users profile
     *
     */
    function __clientProfile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get users id
        $id = $this->uri->segment(4);

        //load users details
        $result = $this->users_model->userDetails($id);
        $this->data['debug'][] = $this->users_model->debug_data;

        //check data
        if ($result) {

            //set vars array
            $user_details['user_id'] = $result['client_users_id'];
            $user_details['full_name'] = $result['client_users_full_name'];
            $user_details['job_title'] = $result['client_users_job_position_title'];
            $user_details['email_address'] = $result['client_users_email'];
            $user_details['telephone'] = $result['client_users_telephone'];
            $user_details['profile_avatar_filename'] = $result['client_users_avatar_filename'];

            //add array to merge data
            $this->data['reg_fields'][] = 'people';
            $this->data['fields']['people'] = $user_details;

        } else {

            //show error
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
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
        $this->data['controller_profiling'][] = __function__; //template::
        //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file people.php */
/* Location: ./application/controllers/client/people.php */
