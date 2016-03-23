<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all team messages related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Teammessages extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.messages.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_team_messages'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';
    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    public function index()
    {
        /* --------------URI SEGMENTS---------------
        * [segment example]
        * /admin/messages/2/view/*.*
        * (2)->controller
        * (3)->project_id
        * (4)->router
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();
		
		$data = array(
   		'logs_user_id'    => $this->data['vars']['my_id'] ,
   		'logs_project_id' => $this->uri->segment(3) ,
   		'logs_action'     => 'Team Chat',
   		'logs_type'       => 'teammessages'
		);
		$query = $this->db->get_where('logs', $data);
		if ($query->num_rows() > 0)
		{
			$this->db->where($data);
			$this->db->set('logs_time', 'NOW()', FALSE);
			$this->db->update('logs'); 
		}
		else {
			$this->db->insert('logs',$data);
		}

        //get project id
        $this->project_id = $this->uri->segment(3);

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
        if ($this->data['project_permissions']['view_item_my_project_team_messages'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //get the action from url
        $action = $this->uri->segment(4);
        //route the rrequest
        switch ($action) {

            case 'view':
                $this->__messagesView();
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
                $this->__messagesView();
                break;
        }

        //css - active tab
        $this->data['vars']['css_active_tab_teammessages'] = 'side-menu-main-active';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * view project team messages
     */
    protected function __messagesView()
    {
        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/messages/2/view/1
        * (2)->controller
        * (3)->project id
        * (4)->route
        * (5)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //explicitly set messaged template file (needed)
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.teammessages.html';

        //uri segments
        $offset = (is_numeric($this->uri->segment(5))) ? $this->uri->segment(5) : 0;

        //correct offset for incoming method calls from __editMessages() and __editReplies()
        if ($this->uri->segment(4) == 'edit-message' || $this->uri->segment(4) == 'edit-reply') {
            $offset = (is_numeric($this->uri->segment(6))) ? $this->uri->segment(6) : 0;
        }

        //set offset for use in template
        $this->data['vars']['offset'] = $offset;

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'messages';
        $this->data['blocks']['messages'] = $this->team_messages_model->listMessages($offset, 'search', $this->project_id);
        $this->data['debug'][] = $this->team_messages_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->team_messages_model->listMessages($offset, 'count', $this->project_id);
        $this->data['debug'][] = $this->team_messages_model->debug_data;

        //pagination
        $config = pagination_default_config(); //
        $config['base_url'] = site_url("admin/teammessages/" . $this->project_id . "/view");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['messages_limit'];
        $config['uri_segment'] = 5; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //visibility
        if ($rows_count > 0) {
            //show side menu
            $this->data['visible']['wi_project_team_messages'] = 1;
        } else {
            //show mothing found
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_results_found']);
        }

        //prepare data
        $this->data['blocks']['messages'] = $this->__prepMessagesView($this->data['blocks']['messages']);
    }

    /**
     * additional data preparations for __messagesView() data
     *
     */
    protected function __prepMessagesView($thedata = array())
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
        *  (2) process user names (message posted by)
        *  (3) strip unwanted html tags from message_text
        *  (4) process avatar images
        *  -----------------------------------------------------------
        *  (1) above is base on what rights I have on the message, i.e:
        *           - am I the message poster
        *           - am I the project leader
        *           - am I a system administrator
        *
        * [NOTES]
        * Usage is during conditional loading at TBS->MERGEBLOCK level and should be as follows:
        * <!--[onshow;block=div;when [files.wi_files_control_buttons;block=tr] == 1;comm]-->
        * --AS OPPOSED TO--
        * <!--[onshow;block=div;when [files.wi_files_control_buttons] == 1;comm]-->
        *
        *------------------------------------------------------------------------------------*/
        for ($i = 0; $i < count($thedata); $i++) {

            //-----(1) VISIBILITY OF CONTROL BUTTONS--------------------------------\\

            //default visibility
            $visibility_control = 0;

            //grant rights if I am the one who posted the message
            if ($this->data['vars']['my_id'] == $thedata[$i]['messages_by_id']) {
                $visibility_control = 1;
            }

            //grant visibility if I am an admin or I am the project leader
            if ( $this->data['vars']['my_group'] == 1 || $this->data['vars']['my_id'] == $this->data['vars']['project_leaders_id']) {
                $visibility_control = 1;
            }

            //add my rights into $thedata array
            $thedata[$i]['wi_messages_control_buttons'] = $visibility_control;

            //----------(2) PROCESS USER NAMES---------------------------------\\

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

            //(2) STRIP UNWANTED CKEDITOR HTML TAGS-----------------------------\\

            //remove </p> tags
            $unwanted = array('</p>');
            $thedata[$i]['messages_text'] = str_replace($unwanted, '', $thedata[$i]['messages_text']);
            //replace <p> with </br> tags
            $thedata[$i]['messages_text'] = str_replace('<p>', '</br>', $thedata[$i]['messages_text']);

            //-------(4) PROCESS AVATAR IMAGES----------------------------------\\

            $thedata[$i]['avatar_filename'] = $thedata[$i]['team_profile_avatar_filename'];

            //-------(4) INJECT REPLIES ARRAY----------------------------------\\
            $replies = $this->team_message_replies_model->getReplies($thedata[$i]['messages_id']);
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
    protected function __prepMessageReplies($thedata = '')
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
        *  (2) process user names (message posted by)
        *  (3) strip unwanted html tags from message_text
        *  (4) process avatar images
        *  -----------------------------------------------------------
        *  (1) above is base on what rights I have on the message, i.e:
        *           - am I the message poster
        *           - am I the project leader
        *           - am I a system administrator
        *
        * [NOTES]
        * Usage is during conditional loading at TBS->MERGEBLOCK level and should be as follows:
        * <!--[onshow;block=div;when [files.wi_files_control_buttons;block=tr] == 1;comm]-->
        * --AS OPPOSED TO--
        * <!--[onshow;block=div;when [files.wi_files_control_buttons] == 1;comm]-->
        *
        *------------------------------------------------------------------------------------*/
        for ($i = 0; $i < count($thedata); $i++) {

            //-----(1) VISIBILITY OF CONTROL BUTTONS--------------------------------\\

            //default visibility
            $visibility_control = 0;

            //grant rights if I am the one who posted the message
            if ($this->data['vars']['my_id'] == $thedata[$i]['messages_replies_by_id']) {
                $visibility_control = 1;
            }

            //grant visibility if I am an admin or I am the project leader
            if ($this->data['vars']['my_group'] == 1 || $this->data['vars']['my_id'] == $this->data['vars']['project_leaders_id']) {
                $visibility_control = 1;
            }

            //add my rights into $thedata array
            $thedata[$i]['wi_messages_replies_control_buttons'] = $visibility_control;

            //-----(2) PROCESS (TEAM/CLIENT) USER NAMES--------------------------------\\

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

            //-------(3) PROCESS AVATAR IMAGES----------------------------------\\

            $thedata[$i]['avatar_filename'] = $thedata[$i]['team_profile_avatar_filename'];

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
    protected function __addMessage()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['messages_text'])) {
            redirect('/admin/teammessages/' . $this->project_id . '/view');
        }

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        /*if ($this->data['project_permissions']['add_item_my_project_team_messages'] != 1) {
            redirect('/admin/error/permission-denied');
        }*/
        $messages_text = $this->input->post('messages_text');

        //flow control
        $next = true; //validate post
        if (!$messages_text) {

            //show message
            $this->notices('error', $this->data['lang']['lang_fill_in_all_required_fields']);

            //halt
            $next = false;
        }

        //validate hidden fields
        if ($next) {

            //array of hidden fields and their check type
            $hidden_fields = array('messages_project_id' => 'numeric', 'messages_by_id' => 'numeric');

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
            $message_id = $this->team_messages_model->addMessage();
            if ($message_id) {

                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

                //email notification
                $this->__emailer('mailqueue_new_message', array('message' => $messages_text));

                //sms notification
                if($this->input->post('send-sms') == 1) {
                    $this->__smser('smsqueue_new_message', array('message' => $messages_text, 'message_id' => $message_id));
                }

            } else {
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
            //debug
            $this->data['debug'][] = $this->team_messages_model->debug_data;
        }
        
        unset($_POST);
        
        //show messages
        if ($next) {
            redirect('/admin/teammessages/' . $this->project_id . '/view');
        } else {
            $this->__messagesView();
        }
    }

    /**
     * add team message reply
     *
     */
    protected function __addReply()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['messages_replies_text'])) {
            redirect('/admin/teammessages/' . $this->project_id . '/view');
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
            $hidden_fields = array('messages_replies_message_id' => 'numeric', 'messages_replies_by_id' => 'numeric');

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
            $message_id = $this->team_message_replies_model->addMessage();
            if ($message_id) {

                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

                //email notification
                $this->__emailer('mailqueue_new_message', array('message' => $this->input->post('messages_replies_text')));

                //sms notification
                if($this->input->post('send-sms') == 1) {
                    $this->__smser('smsqueue_new_message', array('message' => $this->input->post('messages_text'), 'message_id' => $message_id));
                }

            } else {
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
            //debug
            $this->data['debug'][] = $this->team_message_replies_model->debug_data;
        }

        unset($_POST);
        
        //show messages
        if ($next) {
            redirect('/admin/teammessages/' . $this->project_id . '/view');
        } else {
            $this->__messagesView();
        }
    }

    /**
     * edit message via modal window
     *
     */
    protected function __editMessageModal()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'team_messages.modal.html';

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['edit_item_my_project_team_messages'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //get message id
        $message_id = $this->uri->segment(5);

        //avoid direct access of this url (apart from in a modal edit window)
        if (!isset($_POST['messages_text']) && $this->uri->segment(7) != 'modal') {
            redirect('/admin/teammessages/' . $this->project_id . '/view/');
        }

        //get tye offset of paths we were on
        $this->data['vars']['offset'] = (is_numeric($this->uri->segment(6))) ? $this->uri->segment(6) : 0;

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

                if ($this->team_messages_model->editMessage()) {
                    $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);
                } else {
                    //log this error
                    $this->__errorLogging(__line__, __function__, __file__, "Updating project message failed. Database error"); //show error
                    $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                }
                $this->data['debug'][] = $this->team_messages_model->debug_data;

            }

            //show messages
            $this->__messagesView();
        }

        //--load orginal message in modal--------------------
        if (!isset($_POST['submit'])) {

            //get message
            $this->data['reg_fields'][] = 'message';
            if ($this->data['fields']['message'] = $this->team_messages_model->getMessage($message_id)) {

                //show message form
                $this->data['visible']['wi_edit_message_table'] = 1;
            } else {

                //show error
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
            }
            $this->data['debug'][] = $this->team_messages_model->debug_data;

        }

    }

    /**
     * edit message replies via modal window
     *
     */
    protected function __editRepliesModal()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'team_messages.modal.html';

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['edit_item_my_project_team_messages'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //get message id
        $message_id = $this->uri->segment(5);

        //avoid direct access of this url (apart from in a modal edit window)
        if (!isset($_POST['messages_replies_text']) && $this->uri->segment(7) != 'modal') {
            redirect('/admin/teammessages/' . $this->project_id . '/view/');
        }

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

                if ($this->team_messages_model->editReply()) {
                    $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);
                } else {
                    //log this error
                    $this->__errorLogging(__line__, __function__, __file__, "Updating project message failed. Database error"); //show error
                    $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                }
                $this->data['debug'][] = $this->team_messages_model->debug_data;

            }

            //show messages
            $this->__messagesView();
        }

        //--load orginal message in modal--------------------
        if (!isset($_POST['submit'])) {

            //get message
            $this->data['reg_fields'][] = 'replies';
            if ($this->data['fields']['replies'] = $this->team_message_replies_model->getReply($message_id)) {

                //show message form
                $this->data['visible']['wi_edit_replies_table'] = 1;
            } else {

                //show error
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
            }
            $this->data['debug'][] = $this->team_message_replies_model->debug_data;

        }

    }

    /**
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    protected function __flmFormValidation($form = '')
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
    protected function __errorLogging($theline = '', $thefunction = '', $thefile = '', $themessage = 'system error')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
        $message_log = "[FILE: $thefile]  [LINE: $theline]  [FUNCTION: $thefunction]  [MESSAGE: $themessage]";
        log_message('error', $message_log);
    }

    // -- __emailer-------------------------------------------------------------------------------------------------------
    /**
     * send out an email
     *
     * @access	private
     * @param	string
     * @return	void
     */
    protected function __emailer($email = '', $vars = array())
    {
        //common variables
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];

        //------------------------------------queue email in database-------------------------------
        /** THIS WIL NOT SEND BUT QUEUE THE EMAILS*/
        if ($email === 'mailqueue_new_message')
        {
            $sqldata = array();
            //email vars
            $this->data['email_vars']['email_title'] = $this->data['lang']['lang_new_team_message'];
            $this->data['email_vars']['reply_url'] = site_url('admin/teammessages/' . $this->project_id . '/view');
            $this->data['email_vars']['usrname'] = $this->data['vars']['my_name'];
            $this->data['email_vars']['messages_text'] = $vars['message'];
            $this->data['email_vars']['projects_url'] = site_url('admin/project/' . $this->project_id . '/view');
            $email_vars['company_email_signature'] = $this->data['settings_company']['company_email_signature'];

            //get message template from database
            //$template = $this->settings_emailtemplates_model->getEmailTemplate('general_notification_admin');
            $template = $this->settings_emailtemplates_model->getEmailTemplate('admin_communication');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //general temaplate - email body
            /*
            $this->data['email_vars']['email_message'] = '<div style=" border:#CCCCCC solid 1px; padding:8px;">
                                                          <strong>' . $this->data['lang']['lang_project'] . ':</strong>
                                                          ' . $this->data['fields']['project_details']['projects_title'] . '<br />------------------------<br />
                                                          <br /><strong>' . $this->input->post('posted_by') . '</strong><br />
                                                          ' . $vars['message'] . '</div>';
            */

            //loop through all project members (mailing list)
            for ($i = 0; $i < count($this->data['vars']['project_team_mailing_list']); $i++) {

                //dynamic email vars based on (client/team) member
                $this->data['email_vars']['client_users_full_name'] = $this->data['vars']['project_team_mailing_list'][$i]['name'];

                if ($this->data['vars']['project_team_mailing_list'][$i]['user_type'] == 'team') {
                    $this->data['email_vars']['admin_dashboard_url'] = $this->data['vars']['site_url_admin'];
                } else {
                    $this->data['email_vars']['admin_dashboard_url'] = $this->data['vars']['site_url_client'];
                }

                //set sqldata() for database
                $sqldata['email_queue_message'] = parse_email_template($template['message'], $this->data['email_vars']);
                //$sqldata['email_queue_subject'] = $this->data['lang']['lang_project_update'] . ' - ' . $this->data['lang']['lang_new_team_message'];
                $sqldata['email_queue_subject'] = $this->data['fields']['project_details']['projects_title'] . $template['subject'];
                $sqldata['email_queue_email'] = $this->data['vars']['project_team_mailing_list'][$i]['email'];

                //add to email queue database - excluding uploader (no need to send them an email)
                //if ($sqldata['email_queue_email'] != $this->data['vars']['my_email']) {
                    $this->email_queue_model->addToQueue($sqldata);
                    $this->data['debug'][] = $this->email_queue_model->debug_data;
                //}

            }
        }
    }

    // -- __smser-------------------------------------------------------------------------------------------------------
    /**
     * send out an email
     *
     * @access  private
     * @param   string
     * @return  void
     */
    protected function __smser($type = '', $vars = array())
    {
        //------------------------------------queue email in database-------------------------------
        /** THIS WIL NOT SEND BUT QUEUE THE SMS*/
        if ($type == 'smsqueue_new_message') {

            //loop through all project members (sms list)
            foreach($this->data['vars']['project_team_mailing_list'] as $v) {
                //set sqldata() for database
                $sqldata = array();
                $sqldata['sms_queue_object_type'] = 'message';
                $sqldata['sms_queue_object_id'] = isset($vars['message_id']) ? $vars['message_id'] : 0;
                $sqldata['sms_queue_message'] =
                    $this->data['lang']['lang_new_team_message'].'. '.
                    $this->data['lang']['lang_project'].': '.
                    $this->data['fields']['project_details']['projects_title'];

                $sqldata['sms_queue_telephone'] = preg_replace('/\D/', '', $v['telephone']);

                //add to email queue database - excluding uploader (no need to send them an email)
                if ( strlen($sqldata['sms_queue_telephone'])==10 && $sqldata['sms_queue_telephone'] != $this->data['vars']['my_telephone']) {
                    $this->sms_queue_model->addToQueue($sqldata);
                    $this->data['debug'][] = $this->sms_queue_model->debug_data;
                }
            }
        }
    }

    /**
     * loads the view
     *
     * @param string $view the view to load
     */
    protected function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']);
        
        //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file teammessages.php */
/* Location: ./application/controllers/admin/teammessages.php */
