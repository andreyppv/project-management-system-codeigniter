<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Milestones related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Milestones extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.milestones.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_milestones'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';

    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    function index()
    {

        /* --------------URI SEGMENTS---------------
        * [segment example]
        * /admin/milestones/2/view/*.*
        * (2)->controller
        * (3)->project_id
        * (4)->router
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //get project id
        $this->project_id = $this->uri->segment(3);

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;

        //PERMISSIONS CHECK - ACCESS
        //do this check before __commonAll_ProjectBasics()
        if ($this->data['vars']['my_group'] != 1) {
            if (! in_array($this->project_id, $this->data['my_projects_array'])) {
                redirect('/admin/error/permission-denied');
            }
        }

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['view_item_my_project_milestones'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //show wi_project_milestones widget
        $this->data['visible']['wi_project_milestones'] = 1;

        //get the action from url
        $action = $this->uri->segment(4);

        //route the request
        switch ($action) {

            case 'view':
                $this->__milestonesView();
                break;

            case 'add':
                $this->__milestonesAdd();
                break;

            case 'edit':
                $this->__milestonesEdit();
                break;

            default:
                $this->__milestonesView();
                break;
        }

        //css - active tab
        $this->data['vars']['css_active_tab_milestones'] = 'side-menu-main-active';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * main-handler function
     * manage all project milestones
     *
     */
    function __milestonesView()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        /** refresh the milestones tables **/
        $this->refresh->milestones($this->project_id);
        $this->data['debug'][] = $this->refresh->debug_data; //library debug

        //display mile stone
        if ($this->milestones_model->countMilestones($this->project_id, 'all') <= 0) {

            //set notice visible
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_milestones_for_this_project']);

            //show some information/tips
            $this->data['vars']['milestone_info_no_milestones_found'] = 1;

            //set counters
            $this->data['rows1'] = array(
                'in_progress' => 0,
                'completed' => 0,
                'behind_schedule' => 0);

            //stop
            $next = false;

        }
        $this->data['debug'][] = $this->milestones_model->debug_data;

        //get some milestone stats
        if ($next) {

            //in progress
            $this->data['rows1']['in_progress'] = $this->milestones_model->countMilestones($this->project_id, 'in progress');
            $this->data['debug'][] = $this->milestones_model->debug_data;

            //behind schedule
            $this->data['rows1']['behind_schedule'] = $this->milestones_model->countMilestones($this->project_id, 'behind schedule');
            $this->data['debug'][] = $this->milestones_model->debug_data;

            //completed
            $this->data['rows1']['completed'] = $this->milestones_model->countMilestones($this->project_id, 'completed');
            $this->data['debug'][] = $this->milestones_model->debug_data;

        }

        //show list/search results
        if ($next) {

            //offset - used by sql to detrmine next starting point
            $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

            //get results and save for tbs block merging
            $this->data['reg_blocks'][] = 'milestones';
            $this->data['blocks']['milestones'] = $this->milestones_model->listMilestones($offset, 'search', $this->project_id);
            $this->data['debug'][] = $this->milestones_model->debug_data;

            //count results rows - used by pagination class
            $rows_count = $this->milestones_model->listMilestones($offset, 'count', $this->project_id);
            $this->data['debug'][] = $this->milestones_model->debug_data;

            //sorting pagination data that is added to pagination links
            $sort_by = ($this->uri->segment(8) == 'desc') ? 'desc' : 'asc';
            $sort_by_column = ($this->uri->segment(7) == '') ? 'sortby_id' : $this->uri->segment(7);

            //http://mydomain.com/admin/project/2/milestones/view/all/sortby_pending/asc
            //pagination
            $config = pagination_default_config(); //load all other settings from helper
            $config['base_url'] = site_url("admin/project/" . $this->project_id . "/milestones/view/" . $this->uri->segment(6) . "/$sort_by/$sort_by_column");
            $config['total_rows'] = $rows_count;
            $config['per_page'] = $this->data['settings_general']['results_limit'];
            $config['uri_segment'] = 9; //the offset var
            $this->pagination->initialize($config);
            $this->data['vars']['pagination'] = $this->pagination->create_links();

            //sorting links for menus on the top of the table
            //the array names mustbe same as used in clients_model.php->searchClients()
            $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by

            $link_sort_by_column = array(
                'sortby_id',
                'sortby_status',
                'sortby_title',
                'sortby_start_date',
                'sortby_end_date');

            foreach ($link_sort_by_column as $column) {
                $this->data['vars'][$column] = site_url("admin/project/" . $this->project_id . "/milestones/view/" . $this->uri->segment(6) . "/$link_sort_by/$column/$offset");
            }

            //visibility - show table or show nothing found
            if ($rows_count > 0) {

                //show table
                $this->data['visible']['wi_milestones_table'] = 1;

            } else {

                //show nothing found
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_results_found']);
            }

            /** SEND DATA FOR ADDITIONAL PREPARATION **/
            $this->data['blocks']['milestones'] = $this->__prepMilestonesView($this->data['blocks']['milestones']);

        }

    }

    /**
     * additional data preparations for __milestonesView() data
     *
     */
    function __prepMilestonesView($thedata = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (count($thedata) == 0 || ! is_array($thedata)) {
            return $thedata;
        }

        /* -----------------------PREPARE MILESTONES DATA ----------------------------------------/
        *  Loop through all data array and for each milestone:
        *  -----------------------------------------------------------
        *  (1) add visibility for the [control] buttons
        *  -----------------------------------------------------------
        *  (1) above is base on what rights I have on the milestone, i.e:
        *           - am I the project leader
        *           - am I a system administrator
        *
        * [NOTES]
        * Usage is during conditional loading at TBS->MERGEBLOCK level and should be as follows:
        * <!--[onshow;block=div;when [milestones.wi_milestones_control_buttons;block=tr] == 1;comm]-->
        * --AS OPPOSED TO--
        * <!--[onshow;block=div;when [milestones.wi_milestones_control_buttons] == 1;comm]-->
        *
        *------------------------------------------------------------------------------------*/
        for ($i = 0; $i < count($thedata); $i++) {

            //-----(1) VISIBILITY OF CONTROL BUTTONS--------------------------------\\

            //default visibility
            $visibility_control = 0;

            //grant visibility if I am an admin or I am the project leader
            if ($this->data['vars']['my_group'] == 1 || $this->data['vars']['my_id'] == $this->data['vars']['project_leaders_id']) {
                $visibility_control = 1;
            }

            //add control visibility into $thedata array
            $thedata[$i]['wi_milestones_control_buttons'] = $visibility_control;

        }

        //---return the processed array--------
        return $thedata;

    }

    /**
     * add new milestone
     *
     */
    function __milestonesAdd()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['add_item_my_project_milestones'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (! isset($_POST['submit'])) {
            redirect('/admin/milestones/' . $this->project_id . '/view');
        }

        //validate form & display any errors
        if (! $this->__flmFormValidation('add_milestone')) {

            //show error
            $this->notices('error', $this->form_processor->error_message);
            $next = false;

        }

        //validate hidden fields
        if ($next) {
            if (! is_numeric($_POST['milestones_project_id']) || ! is_numeric($_POST['milestones_client_id']) || ! is_numeric($_POST['milestones_created_by']) || $_POST['milestones_events_id'] == '') {

                //log this error
                $this->__errorLogging(__line__, __function__, __file__, 'Adding new milestone failed: [Some or All] Required hidden form fileds missing or invalid');

                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                $next = false;
            }
        }

        //check end date is not behind start date
        if ($next) {

            if (strtotime($this->input->post('milestones_end_date')) < strtotime($this->input->post('milestones_start_date'))) {

                //show error
                $this->notices('error', $this->data['lang']['lang_the_end_date_before_start']);

                $next = false;
            }
        }

        //add new milstone to database
        if ($next) {
            if ($result = $this->milestones_model->addMilestone()) {
                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

                //events tracker
                $this->__eventsTracker('new_milestone', array('target_id' => $result));

            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);
                //halt
                $next = false;
            }
        }
        $this->data['debug'][] = $this->milestones_model->debug_data;

        //refresh project progress
        if ($next) {
            $this->refresh->updateProjectPercentage($this->project_id);
            $this->data['debug'][] = $this->refresh->debug_data;
        }

        //show milestone page
        $this->__milestonesView();
    }

    /**
     * edit existing milestone
     *
     */
    function __milestonesEdit()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //initial state

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['edit_item_my_project_milestones'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (! isset($_POST['submit'])) {
            redirect('/admin/project/' . $this->project_id . '/view');
        }

        //validate form & display any errors
        if (! $this->__flmFormValidation('edit_milestone')) {

            //show error
            $this->notices('error', $this->form_processor->error_message);

            $next = false;
        }

        //validate hidden fields
        if ($next) {
            if ($_POST['milestones_events_id'] == '' || ! is_numeric($_POST['milestones_id'])) {

                //log this error
                $this->__errorLogging(__line__, __function__, __file__, 'Editing milestone failed: Required hidden form fileds missing or invalid'); //show error
                $this->notices('Eror', $this->data['lang']['lang_request_could_not_be_completed']);

                $next = false;
            }
        }

        //edit milstone
        if ($next) {
            if ($this->milestones_model->editMilestone()) {

                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

            } else {

                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);
            }
        }
        $this->data['debug'][] = $this->milestones_model->debug_data;

        //show milestone page
        $this->__milestonesView();
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
        if ($type == 'new_milestone') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->project_id;
            $events['project_events_type'] = 'milestone';
            $events['project_events_details'] = $this->input->post('milestones_title');
            $events['project_events_action'] = 'lang_tl_added_milestone';
            $events['project_events_target_id'] = ($events_data['target_id'] == '') ? 0 : $events_data['target_id'];
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'team';

            //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;
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
        if ($form == 'add_milestone' || $form == 'edit_milestone') {

            //check required fields
            $fields = array(
                'milestones_title' => $this->data['lang']['lang_title'],
                'milestones_start_date' => $this->data['lang']['lang_start_date'],
                'milestones_end_date' => $this->data['lang']['lang_end_date']);
            if (! $this->form_processor->validateFields($fields, 'required')) {
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
        log_message('error', $message_log);
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

/* End of file milestones.php */
/* Location: ./application/controllers/admin/milestones.php */
