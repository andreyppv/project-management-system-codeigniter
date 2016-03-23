<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Project related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Project extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . 'project.details.html';

        //css settings
        $this->data['vars']['css_active_tab_details'] = 'side-menu-main-active';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_details'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';

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
        $action = $this->uri->segment(4);

        //get project id
        $this->project_id = $this->uri->segment(3);

        /** CLIENT-RESOURCE-OWNERSHIP VALIDATION **/
        if (!in_array($this->project_id, $this->data['my_clients_project_array'])) {
            redirect('/client/error/permission-denied');
        }

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project'];

        //route the rrequest
        switch ($action) {

            case 'view':
                $this->__detailsView();
                break;

            case 'edit-project':
                $this->__editProject();
                break;

            case 'edit-timer':
                $this->__editTimer();
                break;

            default:
                $this->__detailsView();
                break;
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * main-handler function
     * display, edit project details
     *
     */
    function __detailsView()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get client details
        $this->data['rows1'] = $this->clients_model->clientDetails($this->client_id);

        //get clients main contact
        $this->data['rows2'] = $this->clients_user_details;

        //get team leaders contacts
        $this->data['rows3'] = $this->project_leaders_details;

        //display optional fields data
        $optional_fields = $this->projectsoptionalfields_model->optionalFields('enabled');
        $this->data['debug'][] = $this->projectsoptionalfields_model->debug_data;
        $this->data['blk2'] = projects_optionalfields($optional_fields, $this->project_details);

        //show we show optional fields
        if (count($this->data['blk2']) >= 1) {
            $this->data['visible']['wi_additional_project_details'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

        //show wi_project_details widget
        $this->data['visible']['wi_project_details'] = 1;

        //get project events (timeline)
        $this->data['reg_blocks'][] = 'timeline';
        $this->data['blocks']['timeline'] = $this->project_events_model->getEvents($this->project_id);
        $this->data['debug'][] = $this->project_events_model->debug_data;

        //further process events data
        $this->data['blocks']['timeline'] = $this->__prepEvents($this->data['blocks']['timeline']);

    }

    /**
     * additional data preparations project events (timeline) data
     *
     */
    function __prepEvents($thedata = '')
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
        *  (1) process user names ('event by' data)
        *  (2) add back the language for the action carried out
        *
        *
        *------------------------------------------------------------------------------------*/
        for ($i = 0; $i < count($thedata); $i++) {

            //--team member---------------------
            if ($thedata[$i]['project_events_user_type'] == 'team') {
                $thedata[$i]['user_name'] = $thedata[$i]['team_profile_full_name'];
            }

            //--client user---------------------
            if ($thedata[$i]['project_events_user_type'] == 'client') {
                $thedata[$i]['user_name'] = $thedata[$i]['client_users_full_name'];
            }

            //add back langauge
            $word = $thedata[$i]['project_events_action'];
            $thedata[$i]['project_events_action_lang'] = $this->data['lang'][$word];

            //add #hash to numbers (e.g invoice number) and create a new key called 'project_events_item'
            if (is_numeric($thedata[$i]['project_events_details'])) {
                $thedata[$i]['project_events_item'] = '#' . $thedata[$i]['project_events_details'];
            } else {
                $thedata[$i]['project_events_item'] = $thedata[$i]['project_events_details'];
            }

        }

        //retun the processed data
        return $thedata;
    }

    /**
     * edit key project details
     *
     */
    function __editProject()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('edit-project', 'view', $this_url);
            redirect($redirect);
        }

        //validate for
        if ($next) {
            if (!$this->__flmFormValidation('edit_project')) {
                //show error
                $this->notices('error', $this->form_processor->error_message, 'noty');
                //halt
                $next = false;
            }
        }

        //validate hidden fields
        if ($next) {
            if (!$this->__flmFormValidation('edit_project_hidden')) {
                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE:edit project failed - invalid post data]");
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty');
                //halt
                $next = false;
            }
        }

        //update database
        if ($next) {
            $result = $this->projects_model->editProject();
            $this->data['debug'][] = $this->projects_model->debug_data;

            if ($result) {
                //success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');

                //events tracker
                $this->__eventsTracker('edit-project', array('target_id' => $result));

            } else {
                //error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty');
            }

        }

        //reload project data (refresh after update)
        $this->__commonAll_ProjectBasics($this->project_id);

        //load project
        $this->__detailsView();

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
        if ($form == 'add_milestone') {

            //check required fields
            $fields = array(
                'milestones_title' => $this->data['lang']['lang_title'],
                'milestones_start_date' => $this->data['lang']['lang_start_date'],
                'milestones_end_date' => $this->data['lang']['lang_end_date']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //everything ok
            return true;
        }

        //form validation
        if ($form == 'edit_project') {

            //check required fields
            $fields = array(
                'projects_title' => $this->data['lang']['lang_title'],
                'project_deadline' => $this->data['lang']['lang_deadline'],
                'projects_description' => $this->data['lang']['lang_description']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //everything ok
            return true;
        }

        //form validation
        if ($form == 'edit_project_hidden') {

            //check required fields
            $fields = array('projects_id' => $this->data['lang']['lang_id']);
            if (!$this->form_processor->validateFields($fields, 'numeric')) {
                return false;
            }

            //everything ok
            return true;
        }

        //form validation
        if ($form == 'edit_timer') {

            //check required fields
            $fields = array('new_time' => $this->data['lang']['lang_time']);
            if (!$this->form_processor->validateFields($fields, 'numeric')) {
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
     * edit project timer
     *
     */
    function __editTimer()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('edit-timer', 'view', $this_url);
            redirect($redirect);
        }

        //flow control
        $next = true;

        //validate time
        if ($next) {
            if (!$this->__flmFormValidation('edit_timer')) {
                //show error
                $this->notices('error', $this->form_processor->error_message, 'noty');
                //halt
                $next = false;
            }
        }

        //update time
        if ($next) {

            //change time(hours) to seconds
            $new_time = $this->input->post('new_time') * 3600;

            //update
            $result = $this->timer_model->updateTimerTime($this->input->post('timer_id'), $new_time);
            $this->data['debug'][] = $this->timer_model->debug_data;

            if ($result) {
                //do xyz
            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty');
            }
        }

        //reload project data (refresh after update)
        $this->__commonAll_ProjectBasics($this->project_id);

        //load project
        $this->__detailsView();

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
        log_message('error', $message_log);
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
        if ($type == 'edit-project') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->project_id;
            $events['project_events_type'] = 'project';
            $events['project_events_details'] = $this->input->post('projects_title');
            $events['project_events_action'] = 'lang_tl_edited_project';
            $events['project_events_target_id'] = ($this->project_id == '') ? 0 : $this->project_id;
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

/* End of file project.php */
/* Location: ./application/controllers/client/project.php */
