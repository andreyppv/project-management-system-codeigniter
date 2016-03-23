<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// -- tickets_move_attachment -------------------------------------------------------------------------------------------------
/**
 * attempt to move the whole attachement folder from 'temp' to 'files/tickets' folder
 * delete temp attachment folder
 *
 * 
 * @param	data array
 * @return	echos output to screen
 */
if (!function_exists('tickets_move_attachment')) {

    function tickets_move_attachment($folder_name = '', $file_name = '')
    {

        //get $CI instance
        $CI = &get_instance();

        //validate input
        if ($folder_name == '' || $file_name == '') {
            return false;
        }

        //set some file paths
        $new_file_path = FILES_TICKETS_FOLDER . $folder_name . '/' . $file_name;
        $old_file_path = FILES_TEMP_FOLDER . $folder_name . '/' . $file_name;

        //set some folder paths
        $new_folder_path = FILES_TICKETS_FOLDER . $folder_name;
        $old_folder_path = FILES_TEMP_FOLDER . $folder_name;

        /*
        *----------------------------------------------------------------------------------------
        * We now move the 'old directory' and its file into the '/files/tickets/*' folder
        * we will use the php function rename() by renaming to rename the old folder
        * by renaming the old folderm we are effectively moving it to its new location
        * for sanity's sake, we will check that the old & new paths are not exactly the same
        * as their base directory (else we risk moving the whole TEMP folder etc)
        * this can happen if for some reason $folder_name is empty
        *----------------------------------------------------------------------------------------
        */
        if ($old_folder_path != FILES_TEMP_FOLDER && $new_folder_path != FILES_TICKETS_FOLDER) {
            @rename($old_folder_path, $new_folder_path);
        }

        //check if the move was a success
        if (!is_file($new_file_path)) {

            //log this error
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Add new ticket failed - error moving attachement file/folder ($old_file_path)");

            //try to delete temp files - avoid deleting actual temp folder
            if ($old_folder_path != FILES_TEMP_FOLDER) {
                @unlink($old_folder_path);
            }

            if ($new_folder_path != FILES_TICKETS_FOLDER) {
                @unlink($new_folder_path);
            }

            return false;
        } else {
            return true;
        }
    }
}

// -- dataprep_ticket- -------------------------------------------------------------------------------------------------------
/**
 * additional data preparations for a loaded ticket or for loaded ticket replies
 *       (1) process user names (message posted by) - as used on ticket details section
 *       (2) process client name - as used on ticket details section
 *
 * 
 * @param	void
 * @return	array [array of prepared data]
 */
if (!function_exists('dataprep_tickets')) {
    function dataprep_tickets($thedata = '')
    {

        //get $CI instance
        $CI = &get_instance();

        //profiling
        $CI->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (!is_array($thedata)) {
            return $thedata;
        }

        //-----(2) PROCESS (TEAM/CLIENT) USER NAMES--------------------------------\\

        //--team member---------------------
        if ($thedata['tickets_by_user_type'] == 'team') {

            //avatar
            $thedata['avatar_filename'] = $thedata['team_profile_avatar_filename'];

            //is the users data available
            if ($thedata['team_profile_full_name'] != '') {

                //trim max lenght
                $fullname = trim_string_length($thedata['team_profile_full_name'], 20);
                $user_id = $thedata['team_profile_id'];
                //create users name label
                $thedata['submitted_by'] = '<a class="links-blue iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $CI->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("admin/people/team/$user_id") . '"
                                                      href="#">' . $fullname . '</a>';

                $thedata['submitted_by_label'] = '<label class="label label-info">' . $fullname . '</label>';

            } else {

                //this user is unavailable (has been deleted etc)
                $thedata['submitted_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $CI->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $CI->data['lang']['lang_unavailable'] . '</span>';

                $thedata['submitted_by_label'] = '<label class="label label-default">' . $CI->data['lang']['lang_unavailable'] . '</label>';
            }
        }

        //--client user----------------------
        if ($thedata['tickets_by_user_type'] == 'client') {

            //avatar
            $thedata['avatar_filename'] = $thedata['client_users_avatar_filename'];

            //is the users data available
            if ($thedata['client_users_full_name'] != '') {

                //trim max lenght
                $fullname = trim_string_length($thedata['client_users_full_name'], 20);
                $user_id = $thedata['client_users_id'];
                //create html
                $thedata['submitted_by'] = '<a class="links-blue iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $CI->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("admin/people/client/$user_id") . '"
                                                      href="#">' . $fullname . '</a>';

                $thedata['submitted_by_label'] = '<label class="label label-purple">' . $fullname . '</label>';

            } else {

                //this user is unavailable (has been deleted etc)
                $thedata['submitted_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $CI->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $CI->data['lang']['lang_unavailable'] . '</span>';

                $thedata['submitted_by_label'] = '<label class="label label-default">' . $CI->data['lang']['lang_unavailable'] . '</label>';

            }
        }

        //retrun the data
        return $thedata;

    }
}

// -- dataprep_ticket_replies- -------------------------------------------------------------------------------------------------------
/**
 * additional data preparations for loaded ticket replies
 *       (1) process user names (message posted by) - as used on ticket details section
 *       (2) process client name - as used on ticket details section
 *
 * 
 * @param	void
 * @return	array [array of prepared data]
 */
if (!function_exists('dataprep_ticket_replies')) {
    function dataprep_ticket_replies($thedata = '')
    {

        //get $CI instance
        $CI = &get_instance();

        //profiling
        $CI->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (!is_array($thedata)) {
            return $thedata;
        }

        //-----(2) PROCESS (TEAM/CLIENT) USER NAMES--------------------------------\\

        for ($i = 0; $i < count($thedata); $i++) {

            //--team member---------------------
            if ($thedata[$i]['tickets_replies_by_user_type'] == 'team') {

                //avatar
                $thedata[$i]['avatar_filename'] = $thedata[$i]['team_profile_avatar_filename'];

                //is the users data available
                if ($thedata[$i]['team_profile_full_name'] != '') {

                    //trim max lenght
                    $fullname = trim_string_length($thedata[$i]['team_profile_full_name'], 20);
                    $user_id = $thedata[$i]['team_profile_id'];
                    //create users name label
                    $thedata[$i]['submitted_by'] = '<a class="links-blue iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $CI->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("admin/people/team/$user_id") . '"
                                                      href="#">' . $fullname . '</a>';

                    $thedata[$i]['submitted_by_label'] = '<label class="label label-info">' . $fullname . '</label>';

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['submitted_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $CI->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $CI->data['lang']['lang_unavailable'] . '</span>';

                    $thedata[$i]['submitted_by_label'] = '<label class="label label-default">' . $CI->data['lang']['lang_unavailable'] . '</label>';
                }
            }

            //--client user----------------------
            if ($thedata[$i]['tickets_replies_by_user_type'] == 'client') {
            
                //avatar
                $thedata[$i]['avatar_filename'] = $thedata[$i]['client_users_avatar_filename'];
                
                //is the users data available
                if ($thedata[$i]['client_users_full_name'] != '') {

                    //trim max lenght
                    $fullname = trim_string_length($thedata[$i]['client_users_full_name'], 20);
                    $user_id = $thedata[$i]['client_users_id'];
                    //create html
                    $thedata[$i]['submitted_by'] = '<a class="links-blue iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $CI->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("admin/people/client/$user_id") . '"
                                                      href="#">' . $fullname . '</a>';

                    $thedata[$i]['submitted_by_label'] = '<label class="label label-purple">' . $fullname . '</label>';

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['submitted_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $CI->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $CI->data['lang']['lang_unavailable'] . '</span>';

                    $thedata[$i]['submitted_by_label'] = '<label class="label label-default">' . $CI->data['lang']['lang_unavailable'] . '</label>';

                }
            }

        }
        //retrun the data
        return $thedata;

    }
}

// -- dataprep_tickets_list- -------------------------------------------------------------------------------------------------------
/**
 * additional data preparations for tickets list (search lists etc)
 *  (1) process user names (message posted by)
 *
 * 
 * @param	void
 * @return	array (processed data)
 */
if (!function_exists('dataprep_tickets_list')) {
    function dataprep_tickets_list($thedata = '')
    {

        //get $CI instance
        $CI = &get_instance();

        //profiling
        $CI->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (count($thedata) == 0 || !is_array($thedata)) {
            return $thedata;
        }

        /* -----------------------PREPARE FILES DATA ----------------------------------------/
        *  Loop through all the date in this array and for each ticket:
        *------------------------------------------------------------------------------------*/
        for ($i = 0; $i < count($thedata); $i++) {

            //-----(2) PROCESS (TEAM/CLIENT) USER NAMES--------------------------------\\

            //--team member---------------------
            if ($thedata[$i]['tickets_by_user_type'] == 'team') {

                //is the users data available
                if ($thedata[$i]['team_profile_full_name'] != '') {

                    //trim max lenght
                    $fullname = trim_string_length($thedata[$i]['team_profile_full_name'], 20);
                    $user_id = $thedata[$i]['team_profile_id'];
                    //create users name label
                    $thedata[$i]['submitted_by'] = '<label class="label label-info">' . $fullname . '</label>';

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['submitted_by'] = '<label class="label label-default">' . $CI->data['lang']['lang_unavailable'] . '</label>';
                }
            }

            //--client user----------------------
            if ($thedata[$i]['tickets_by_user_type'] == 'client') {

                //is the users data available
                if ($thedata[$i]['client_users_full_name'] != '') {

                    //trim max lenght
                    $fullname = trim_string_length($thedata[$i]['client_users_full_name'], 20);
                    $user_id = $thedata[$i]['client_users_id'];
                    //create html
                    $thedata[$i]['submitted_by'] = '<label class="label label-purple">' . $fullname . '</label>';

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['submitted_by'] = '<label class="label label-default">' . $CI->data['lang']['lang_unavailable'] . '</label>';
                }
            }

        }

        //retrun the data
        return $thedata;

    }
}

/* End of file tickets_helper.php */
/* Location: ./application/helpers/tickets_helper.php */
