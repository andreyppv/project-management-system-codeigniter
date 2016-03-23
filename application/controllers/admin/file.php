<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all file related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class File extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.file.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_file'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';
    }

    /**
     * This is our re-routing function and is the inital function called
     *
     */
    public function index()
    {

        /* --------------URI SEGMENTS---------------
        * [segment example]
        * /admin/file/2/view/8*.*
        * (2)->controller
        * (3)->project_id
        * (4)->router
        * (4)->file id
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //get project id
        $this->project_id = $this->uri->segment(3);

        //set the file id for global use
        $this->data['vars']['file_id'] = $this->uri->segment(5);

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;

        //PERMISSIONS CHECK - ACCESS
        //do this check before __commonAll_ProjectBasics()
        if ($this->data['vars']['my_group'] != 1) {
            if (!in_array($this->project_id, $this->data['my_projects_array'])) {
                redirect('/admin/error/permission-denied');
            }
        }

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['view_item_my_project_files'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //get the action from url
        $action = $this->uri->segment(4);

        //route the request
        switch ($action) {

            case 'view':
                $this->__fileView();
                break;

            case 'download':
                $this->__fileDownload();
                break;

            case 'add-message':
                $this->__addMessage();
                break;

            case 'add-reply':
                $this->__addReply();
                break;

            case 'edit-message':
                $this->__editMessageModal();
                break;

            case 'edit-reply':
                $this->__editRepliesModal();
                break;

            default:
                $this->__fileView();
                break;
        }

        //css - active tab
        $this->data['vars']['css_active_tab_files'] = 'side-menu-main-active';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * load file details
     */
    function __fileView()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //uri segments
        $id = $this->uri->segment(5);

        //validate data
        if ($next) {

            if (!is_numeric($id)) {
                //show error
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_found']);
                //halt
                $next = false;
            }
        }

        //load file details
        if ($next) {

            //register tbs field
            $this->data['reg_fields'][] = 'file';

            //get file details
            if ($this->data['fields']['file'] = $this->files_model->getFile($id)) {

                //prepare the data
                $this->data['fields']['file'] = $this->__prepFileView($this->data['fields']['file']);

                //visibility
                $this->data['visible']['wi_file_details'] = 1;

            } else {

                //show error
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_found']);
                //halt
                $next = false;

            }
            $this->data['debug'][] = $this->files_model->debug_data;
        }

        //should we show a preview
        if ($next) {

            //files with previews
            $preview_extensions = array(
                'png',
                'jpg',
                'gif');

            //show preview section
            if (in_array($this->data['fields']['file']['files_extension'], $preview_extensions)) {
                $this->data['visible']['wi_file_preview_image'] = 1;
            } else {
                $this->data['visible']['wi_file_no_preview'] = 1;
            }

        }

        //load messages
        $this->__messagesView();

    }

    /**
     * additional data preparations for __fileView() data
     *
     */
    function __prepFileView($thedata = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (!is_array($thedata)) {
            return $thedata;
        }

        /* -----------------------PREPARE FILES DATA ----------------------------------------/
        *  Loop through all the files in this array and for each file:
        *
        *  (1) process user names (files uploaded by)
        *  (2) add file url to data array
        *  (3) add file path to data array
        *
        *
        *------------------------------------------------------------------------------------*/

        //----------------(1) PROCESS USER NAMES------------------------------------\\

        //--team member------------------------
        if ($thedata['files_uploaded_by'] == 'team') {

            //is the users data available
            if ($thedata['team_profile_full_name'] != '') {

                //trim max lenght
                $fullname = trim_string_length($thedata['team_profile_full_name'], 15);
                $user_id = $thedata['team_profile_id'];
                //create users name label <label class="label label-info">
                $thedata['uploaded_by'] = '<label class="label label-info iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $this->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("admin/people/team/$user_id") . '"
                                                      href="#">' . $fullname . '</label>';

            } else {

                //this user is unavailable (has been deleted etc)
                $thedata['uploaded_by'] = '<label class="label label-information tooltips" 
                                                       data-original-title="' . $this->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $this->data['lang']['lang_unavailable'] . '</label>';
            }
        }

        //--client user-----------------------------
        if ($thedata['files_uploaded_by'] == 'client') {

            //is the users data available
            if ($thedata['client_users_full_name'] != '') {

                //trim max lenght
                $fullname = trim_string_length($thedata['client_users_full_name'], 15);
                $user_id = $thedata['client_users_id'];
                //create html
                $thedata['uploaded_by'] = '<label class="label label-purple iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $this->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("admin/people/client/$user_id") . '"
                                                      href="#">' . $fullname . '</label>';

            } else {

                //this user is unavailable (has been deleted etc)
                $thedata['uploaded_by'] = '<label class="label label-information tooltips"  
                                                       data-original-title="' . $this->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $this->data['lang']['lang_unavailable'] . '</label>';
            }
        }

        //-------------------(2) ADD FILE URL---------------------------------------\\
        $thedata['file_url'] = site_url('files/projects/' . $thedata['files_project_id'] . '/' . $thedata['files_foldername'] . '/' . $thedata['files_name']);

        //-------------------(3) ADD FILE PATH---------------------------------------\\
        $thedata['file_path'] = FILES_PROJECT_FOLDER . $thedata['files_project_id'] . '/' . $thedata['files_foldername'] . '/' . $thedata['files_name'];

        //return the processed array
        return $thedata;

    }

    /**
     * download file and count
     *
     */
    function __fileDownload()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //download helper
        $this->load->helper('download');

        //flow control
        $next = true;

        //file id
        $file_id = $this->uri->segment(5);

        //validate input data
        if ($next) {
            if (!is_numeric($file_id) || !is_numeric($this->project_id)) {

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: File download failed. Invalid file id or project id]");

                //redirect
                redirect('admin/error/not-found');

                $next = false; //just in case

            }
        }

        //get file detalils from database
        if ($next) {
            if ($result = $this->files_model->getFile($file_id)) {

                $file_name = $result['files_name'];
                $files_foldername = $result['files_foldername'];
                $file_path = FILES_PROJECT_FOLDER . $this->project_id . '/' . $files_foldername . '/' . $file_name;

            } else {

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: File download failed. File not found in database]");
                //redirect
                redirect('admin/error/not-found');

                $next = false; //just in case
            }
            $this->data['debug'][] = $this->files_model->debug_data;
        }

        //serve the file
        if ($next) {

            if (is_file($file_path)) {

                //increase download counter
                $this->files_model->downloadCounter($file_id);

                //force browser to download file
                $file_data = file_get_contents($file_path); // Read the file's contents
                force_download($file_name, $file_data);

            } else {

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: File download failed. File not found on server drive]");
                //redirect
                redirect('admin/error/not-found');
            }
        }
    }

    /**
     * view project file messages
     */
    function __messagesView()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/file/2/view/25/10
        * (2)->controller
        * (3)->project id
        * (4)->route
        * (5)->file id
        * (6)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //explicitly set messaged template file (needed)
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.file.html';

        //uri segments
        $offset = (is_numeric($this->uri->segment(6))) ? $this->uri->segment(6) : 0;

        //correct offset for incoming method calls from __editMessages() and __editReplies()
        if ($this->uri->segment(4) == 'edit-message' || $this->uri->segment(4) == 'edit-reply') {
            $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;
        }

        //set offset for use in template
        $this->data['vars']['offset'] = $offset;

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'messages';
        $this->data['blocks']['messages'] = $this->file_messages_model->listMessages($offset, 'search', $this->uri->segment(5));
        $this->data['debug'][] = $this->file_messages_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->file_messages_model->listMessages($offset, 'count', $this->uri->segment(5));
        $this->data['debug'][] = $this->file_messages_model->debug_data;

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/file/2/view/25/10
        * (2)->controller
        * (3)->project id
        * (4)->route
        * (5)->file id
        * (6)->offset
        ** -----------------------------------------*/
        //pagination
        $config = pagination_default_config(); //
        $config['base_url'] = site_url('admin/file/' . $this->project_id . '/view/' . $this->uri->segment(5));
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['messages_limit'];
        $config['uri_segment'] = 6; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //visibility
        if ($rows_count > 0) {
            //show side menu
            $this->data['visible']['wi_project_file_messages'] = 1;
        }

        //prepare data
        $this->data['blocks']['messages'] = $this->__prepMessagesView($this->data['blocks']['messages']);
    }

    /**
     * additional data preparations for __fileView() data
     *
     */
    function __prepMessagesView($thedata = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (count($thedata) == 0 || !is_array($thedata)) {
            return $thedata;
        }

        /* -----------------------PREPARE FILES DATA ----------------------------------------/
        *  Loop through all the files in this array and for each file:
        *  -----------------------------------------------------------
        *  (1) add visibility for the [control] buttons
        *  (2) process team user names (message posted by)
        *  (3) process client user names (message posted by)
        *  (3) strip unwanted html tags from message_text
        *
        * [NOTES]
        * Usage is during conditional loading at TBS->MERGEBLOCK level and should be as follows:
        * <!--[onshow;block=div;when [files.wi_files_control_buttons;block=tr] == 1;comm]-->
        * --AS OPPOSED TO--
        * <!--[onshow;block=div;when [files.wi_files_control_buttons] == 1;comm]-->
        *
        *-------------------------------------------------------------------------------------*/

        for ($i = 0; $i < count($thedata); $i++) {

            //-----(1) VISIBILITY OF CONTROL BUTTONS--------------------------------\\

            //default visibility
            $visibility_control = 0;

            //grant visibility if (I am an admin or I am the project leader)
            if ($this->data['vars']['my_group'] == 1 || $this->data['vars']['my_id'] == $this->data['vars']['project_leaders_id']) {
                $visibility_control = 1;
            }

            //add my rights into $thedata array
            $thedata[$i]['wi_messages_control_buttons'] = $visibility_control;

            //----------(1) PROCESS TEAM NAMES---------------------------------\\

            if ($thedata[$i]['messages_by'] == 'team') {
                //is the users data available
                if ($thedata[$i]['team_profile_full_name'] != '') {

                    //trim max lenght
                    $fullname = trim_string_length($thedata[$i]['team_profile_full_name'], 20);
                    $user_id = $thedata[$i]['team_profile_id'];
                    //create users name label
                    $thedata[$i]['uploaded_by'] = '<a class="links-blue iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $this->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("admin/people/team/$user_id") . '"
                                                      href="#">' . $fullname . '</a>';

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['uploaded_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $this->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $this->data['lang']['lang_unavailable'] . '</span>';
                }

                $thedata[$i]['avatar_filename'] = $thedata[$i]['team_profile_avatar_filename'];
            }

            //----------(2) PROCESS CLIENT USER NAMES---------------------------------\\

            if ($thedata[$i]['messages_by'] == 'client') {
                //is the users data available
                if ($thedata[$i]['client_users_full_name'] != '') {

                    //trim max lenght
                    $fullname = trim_string_length($thedata[$i]['client_users_full_name'], 20);
                    $user_id = $thedata[$i]['client_users_id'];
                    //create users name label
                    $thedata[$i]['uploaded_by'] = '<a class="links-blue iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $this->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("client/people/client/$user_id") . '"
                                                      href="#">' . $fullname . '</a>';

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['uploaded_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $this->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $this->data['lang']['lang_unavailable'] . '</span>';
                }

                $thedata[$i]['avatar_filename'] = $thedata[$i]['client_users_avatar_filename'];
            }

            //(3) STRIP UNWANTED CKEDITOR HTML TAGS-----------------------------\\

            //remove </p> tags
            $unwanted = array('</p>');
            $thedata[$i]['messages_text'] = str_replace($unwanted, '', $thedata[$i]['messages_text']);
            //replace <p> with </br> tags
            $thedata[$i]['messages_text'] = str_replace('<p>', '</br>', $thedata[$i]['messages_text']);

            //-------(4) INJECT REPLIES ARRAY----------------------------------\\
            $replies = $this->file_messages_replies_model->getReplies($thedata[$i]['messages_id']);
            $this->data['debug'][] = $this->files_model->debug_data;
            if (is_array($replies)) {
                //prepare replies
                $thedata[$i]['replies'] = $this->__prepMessageReplies($replies);
            }
        }

        //retrun the data
        return $thedata;
    }

    /**
     * additional data preparations for [message replies]
     *
     */
    function __prepMessageReplies($thedata = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (count($thedata) == 0 || !is_array($thedata)) {
            return $thedata;
        }

        /* -----------------------PREPARE FILES DATA ----------------------------------------/
        *  Loop through all the files in this array and for each file:
        *  -----------------------------------------------------------
        *  (1) add visibility for the [control] buttons
        *  (2) process team user names (message posted by)
        *  (3) process client user names (message posted by)
        *  (3) strip unwanted html tags from message_text
        *
        * [NOTES]
        * Usage is during conditional loading at TBS->MERGEBLOCK level and should be as follows:
        * <!--[onshow;block=div;when [files.wi_files_control_buttons;block=tr] == 1;comm]-->
        * --AS OPPOSED TO--
        * <!--[onshow;block=div;when [files.wi_files_control_buttons] == 1;comm]-->
        *
        *-------------------------------------------------------------------------------------*/
        for ($i = 0; $i < count($thedata); $i++) {

            //-----(1) VISIBILITY OF CONTROL BUTTONS--------------------------------\\

            //default visibility
            $visibility_control = 0;

            //grant visibility if (I am an admin or I am the project leader)
            if ($this->data['vars']['my_group'] == 1 || $this->data['vars']['my_id'] == $this->data['vars']['project_leaders_id']) {
                $visibility_control = 1;
            }

            //add my rights into $thedata array
            $thedata[$i]['wi_messages_replies_control_buttons'] = $visibility_control;

            //----------(1) PROCESS TEAM NAMES---------------------------------\\

            if ($thedata[$i]['messages_replies_by'] == 'team') {
                //is the users data available
                if ($thedata[$i]['team_profile_full_name'] != '') {

                    //trim max lenght
                    $fullname = trim_string_length($thedata[$i]['team_profile_full_name'], 20);
                    $user_id = $thedata[$i]['team_profile_id'];
                    //create users name label
                    $thedata[$i]['uploaded_by'] = '<a class="links-blue iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $this->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("admin/people/team/$user_id") . '"
                                                      href="#">' . $fullname . '</a>';

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['uploaded_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $this->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $this->data['lang']['lang_unavailable'] . '</span>';
                }

                $thedata[$i]['avatar_filename'] = $thedata[$i]['team_profile_avatar_filename'];
            }

            //----------(2) PROCESS CLIENT USER NAMES---------------------------------\\

            if ($thedata[$i]['messages_replies_by'] == 'client') {
                //is the users data available
                if ($thedata[$i]['client_users_full_name'] != '') {

                    //trim max lenght
                    $fullname = trim_string_length($thedata[$i]['client_users_full_name'], 20);
                    $user_id = $thedata[$i]['client_users_id'];
                    //create users name label
                    $thedata[$i]['uploaded_by'] = '<a class="links-blue iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $this->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("client/people/client/$user_id") . '"
                                                      href="#">' . $fullname . '</a>';

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['uploaded_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $this->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $this->data['lang']['lang_unavailable'] . '</span>';
                }

                $thedata[$i]['avatar_filename'] = $thedata[$i]['client_users_avatar_filename'];
            }

            //(4) STRIP UNWANTED CKEDITOR HTML TAGS-----------------------------\\

            //remove </p> tags
            $unwanted = array('</p>');
            $thedata[$i]['messages_replies_text'] = str_replace($unwanted, '', $thedata[$i]['messages_replies_text']);
            //replace <p> with </br> tags
            $thedata[$i]['messages_replies_text'] = str_replace('<p>', '</br>', $thedata[$i]['messages_replies_text']);

        }

        //retrun the data
        return $thedata;
    }

    /**
     * add team message
     *
     */
    function __addMessage()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['messages_file_id'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('add-message', 'view', $this_url);
            redirect($redirect);
        }

        //flow control
        $next = true; //validate post
        if ($this->input->post('messages_text') == '') {

            //show message
            $this->notices('error', $this->data['lang']['lang_fill_in_all_required_fields']);

            //halt
            $next = false;
        }

        //validate hidden fields
        if ($next) {

            //array of hidden fields and their check type
            $hidden_fields = array(
                'messages_project_id' => 'numeric',
                'messages_by_id' => 'numeric',
                'messages_file_id' => 'numeric');

            //loop through and validate each hidden field
            foreach ($hidden_fields as $key => $value) {

                if (($value == 'numeric' && !is_numeric($_POST[$key])) || ($value == 'string' && $_POST[$key] == '')) {

                    //log this error
                    log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Adding new message failed: Required hidden form field ($key) missing or invalid]");
                    $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                    //halt
                    $next = false;
                }
            }
        }

        //update database
        if ($next) {

            if ($this->file_messages_model->addMessage()) {

                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

                //events tracker
                $this->__eventsTracker('posted-message', array());

            } else {
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
            //debug
            $this->data['debug'][] = $this->file_messages_model->debug_data;
        }

        //show messages
        $this->__fileView();
    }

    /**
     * add team message reply
     *
     */
    function __addReply()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('add-reply', 'view', $this_url);
            redirect($redirect);
        }

        //flow control
        $next = true; //validate post
        if ($this->input->post('messages_replies_text') == '') {

            //show message
            $this->notices('error', $this->data['lang']['lang_fill_in_all_required_fields']);

            //halt
            $next = false;
        }

        //validate hidden fields
        if ($next) {

            //array of hidden fields and their check type
            $hidden_fields = array(
                'messages_replies_message_id' => 'numeric',
                'messages_replies_by_id' => 'numeric',
                'messages_replies_file_id' => 'numeric');

            //loop through and validate each hidden field
            foreach ($hidden_fields as $key => $value) {

                if (($value == 'numeric' && !is_numeric($_POST[$key])) || ($value == 'string' && $_POST[$key] == '')) {

                    //log this error
                    log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Adding new message reply failed: Required hidden form field ($key) missing or invalid]");
                    $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                    //halt
                    $next = false;
                }
            }
        }

        //update database
        if ($next) {

            if ($this->file_messages_replies_model->addMessage()) {

                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

                //events tracker
                $this->__eventsTracker('posted-message', array());

            } else {
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
            //debug
            $this->data['debug'][] = $this->file_messages_replies_model->debug_data;
        }

        //show messages
        $this->__fileView();
    }

    /**
     * edit message via modal window
     *
     */
    function __editMessageModal()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_COMMON_THEME . 'file_messages.modal.html';

        //get message id
        $message_id = $this->uri->segment(6);

        //avoid direct access of this url (apart from in a modal edit window)
        if (!isset($_POST['messages_text']) && $this->uri->segment(8) != 'modal') {
            redirect('/admin/file/' . $this->project_id . '/view/' . $this->uri->segment(5));
        }

        //get the offset of paths we were on
        $this->data['vars']['offset'] = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //flow control
        $next = true;

        //--updating message via form post--------------------
        if (isset($_POST['submit'])) {

            //validate data
            if ($next) {
                if (!$this->__flmFormValidation('edit_message')) {
                    //show error
                    $this->notices('error', $this->form_processor->error_message);
                    $next = false;
                }

            }

            //validate hiddens
            if ($next) {
                //array of hidden fields and their check type
                $hidden_fields = array('messages_id' => 'numeric'); //loop through and validate each hidden field
                foreach ($hidden_fields as $key => $value) {

                    if (($value == 'numeric' && !is_numeric($_POST[$key])) || ($value == 'string' && $_POST[$key] == '')) {

                        //log this error
                        $this->__errorLogging(__line__, __function__, __file__, "Updating project message failed. Required hidden form field ($key) missing or invalid"); //show error
                        $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                        $next = false;
                    }
                }
            }

            //update database
            if ($next) {

                if ($this->file_messages_model->editMessage()) {
                    $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);
                } else {
                    //log this error
                    $this->__errorLogging(__line__, __function__, __file__, "Updating project message failed. Database error"); //show error
                    $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                }

            }

            //show messages
            $this->__fileView();
        }

        //--load orginal message in modal--------------------
        if (!isset($_POST['submit'])) {

            //get message
            $this->data['reg_fields'][] = 'message';
            if ($this->data['fields']['message'] = $this->file_messages_model->getMessage($message_id)) {

                //show message form
                $this->data['visible']['wi_edit_message_table'] = 1;
            } else {

                //show error
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
            }
            $this->data['debug'][] = $this->file_messages_model->debug_data;

        }

    }

    /**
     * edit message replies via modal window
     *
     */
    function __editRepliesModal()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'file_messages.modal.html';

        //get message id
        $message_id = $this->uri->segment(6);

        //avoid direct access of this url (apart from in a modal edit window)
        if (!isset($_POST['messages_replies_text']) && $this->uri->segment(8) != 'modal') {
            redirect('/admin/file/' . $this->project_id . '/view/' . $this->uri->segment(5));
        }

        //get the offset of paths we were on
        $this->data['vars']['offset'] = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //flow control
        $next = true;

        //--updating message via form post--------------------
        if (isset($_POST['submit'])) {

            //validate data
            if ($next) {
                if (!$this->__flmFormValidation('edit_reply')) {
                    //show error
                    $this->notices('error', $this->form_processor->error_message);
                    $next = false;
                }

            }

            //validate hiddens
            if ($next) {
                //array of hidden fields and their check type
                $hidden_fields = array('messages_replies_id' => 'numeric'); //loop through and validate each hidden field
                foreach ($hidden_fields as $key => $value) {

                    if (($value == 'numeric' && !is_numeric($_POST[$key])) || ($value == 'string' && $_POST[$key] == '')) {

                        //log this error
                        $this->__errorLogging(__line__, __function__, __file__, "Updating project message reply, failed. Required hidden form field ($key) missing or invalid"); //show error
                        $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                        $next = false;
                    }
                }
            }

            //update database
            if ($next) {

                if ($this->file_messages_model->editReply()) {
                    $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);
                } else {
                    //log this error
                    $this->__errorLogging(__line__, __function__, __file__, "Updating project message failed. Database error"); //show error
                    $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                }

            }

            //show messages
            $this->__fileView();
        }

        //--load orginal message in modal--------------------
        if (!isset($_POST['submit'])) {

            //get message
            $this->data['reg_fields'][] = 'replies';
            if ($this->data['fields']['replies'] = $this->file_messages_replies_model->getReply($message_id)) {

                //show message form
                $this->data['visible']['wi_edit_replies_table'] = 1;
            } else {

                //show error
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
            }
            $this->data['debug'][] = $this->file_messages_replies_model->debug_data;

        }

    }

    /**
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //form validation
        if ($form == 'edit_message') {

            //check required fields
            $fields = array('messages_text' => $this->data['lang']['lang_message']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //everything ok
            return true;
        }

        //---------------validate form post data--------------------------
        if ($form == 'edit_reply') {

            //check required fields
            $fields = array('messages_replies_text' => $this->data['lang']['lang_message']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //everything ok
            return true;
        }

        //nothing specified - return false & error message
        $this->form_processor->error_message = $this->data['lang']['lang_form_validation_error'];
        return false;
    }

    /**
     * log any error message into the log file
     *
     */
    function __errorLogging($theline = '', $thefunction = '', $thefile = '', $themessage = 'system error')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
        $message_log = "[FILE: $thefile]  [LINE: $theline]  [FUNCTION: $thefunction]  [MESSAGE: $themessage]";
        log_message('debug', $message_log);
    }

    /**
     * records new project events (timeline)
     *
     * @param	string   $type identify the loop to run in this function
     * @param   array    $vents_data an optional array that can be used to directly pass data]      
     */
    function __eventsTracker($type = '', $events_data = array())
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //--------------record a new event-----------------------
        if ($type == 'posted-message') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->project_id;
            $events['project_events_type'] = 'file-message';
            $events['project_events_details'] = '(' . $this->input->post('messages_file_name') . ') ' . regex_remove_lines_spaces(strip_tags($this->input->post('messages_text')));
            $events['project_events_action'] = 'lang_tl_comented_file';
            $events['project_events_target_id'] = 0;
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'team';

            //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;
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

/* End of file file.php */
/* Location: ./application/controllers/admin/file.php */
