<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// -- template_function -------------------------------------------------------------------------------------------------
/**
 * sets all of the users sessions data to vars for easy use
 * @param   string $user_type	
 */
if (! function_exists('logged_in_check')) {
    function logged_in_check($user_type = 'team_member')
    {

        //get $CI instance
        $CI = &get_instance();

        //invalid detals provided
        if ($user_type == '') {
            redirect('/admin/login');
        }

        //team members:: check logged in and take action
        if ($user_type == 'team_member') {
            if ($CI->session->userdata('team_profile_id') == '') {
                //for regular pages, redirect
                redirect('/admin/login');
            }
        }

        //client user:: check logged in and take action
        if ($user_type == 'client_user') {
            if ($CI->session->userdata('client_users_id') == '' || ! is_numeric($CI->session->userdata('client_users_clients_id'))) {
                //if modal page, just echo
                $modals = array('edit-modal');
                if (in_array($CI->uri->segment(3), $modals)) {
                    die($this->data['lang']['lang_session_timed_out']);
                } else {
                    //for regular pages, redirect
                    redirect('/client/login');
                }
            }
        }
    }

}

// -- set_my_permissions -------------------------------------------------------------------------------------------------
/**
 * sets all permissions that the loggged in team member has (based on their group)
 * permissions are saved in an array
 * @param array $groups_table_array
 */
if (! function_exists('set_my_permissions')) {
    function set_my_permissions($groups_table_array = '')
    {

        //get $CI instance
        $CI = &get_instance();

        //exclude these fields
        $exclude = array(
            'groups_id',
            'groups_name',
            'groups_allow_delete',
            'groups_allow_edit',
            'groups_allow_migrate',
            'groups_allow_change_permissions',
            'groups_allow_zero_members');

        //set permissions array for users group
        foreach ($groups_table_array as $key => $value) {

            //exclude none permission related fields
            if (! in_array($key, $exclude)) {

                //sanity check- make sure value is numeric
                $value = (is_numeric($value)) ? $value : 0;

                //save permission in table
                $CI->data['my_permissions'][$key] = $value;
            }

        }

    }
}

// -- session_notices_show -------------------------------------------------------------------------------------------------
/**
 * displays any session notices and clear session. 
 * normally used to save  message that will be displayed on the next page (i.e. message after a page redirect)
 */
if (! function_exists('session_notices_show')) {
    function session_notices_show()
    {

        //get $CI instance
        $CI = &get_instance();

        //get stored session data
        $stored_notice = @unserialize($CI->session->userdata('session_notice_array'));

        if (is_array($stored_notice)) {

            //get the variables for the notice
            $notice_type = $stored_notice['notice_type'];
            $notice_message = $stored_notice['notice_message'];
            $notice_style = ($stored_notice['notice_style'] == 'html') ? 'html' : 'noty';

            //validate message and notice type
            if ($notice_message == '' || ! in_array($notice_type, array('error', 'success'))) {
                return;
            }

            //all is ok, display message
            $CI->notices($notice_type, $notice_message, $notice_style);

            //delete notice session
            $CI->session->unset_userdata('session_notice_array');
        }

    }
}

// -- session_notices_set -------------------------------------------------------------------------------------------------
/**
 * sets a error/success notice in a session.
 * normally used to save  message that will be displayed on the next page (i.e. message after a page redirect)
 * 
 * 
 * @param   void	
 * @return void 
 */
if (! function_exists('session_notices_set')) {
    function session_notices_set($type = '', $message = '', $style = 'html')
    {

        //get $CI instance
        $CI = &get_instance();

        //validate input
        if ($message == '' || ! in_array($type, array('success', 'error'))) {
            return;
        }

        //set notice data array & serialize it
        $session_notice_array = serialize(array(
            'notice_type' => $type,
            'notice_message' => $message,
            'notice_style' => $style));

        //save in current session
        $CI->session->unset_userdata('session_notice_array'); //just in case
        $CI->session->set_userdata('session_notice_array', $session_notice_array);
    }
}

/* End of file sessions_helper.php */
/* Location: ./application/helpers/sessions_helper.php */
