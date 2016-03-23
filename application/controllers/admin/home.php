<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Home related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Home extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'home.html';

        //css settings
        $this->data['vars']['css_menu_dashboard'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_dashboard'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-home"></i>';
		
	
    }

    public function index()
    {

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        $userid = $this->data['vars']['my_id'];
        //$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 
        //$result = $db->prepare("SELECT `id` FROM oauth WHERE user_id = ?");
        //$result->execute(array($userid));
        //if($result->rowCount() == 0){ header("Location: http://pms.isodeveloper.com/admin/oauth"); exit; }
        
        
        //login check
        $this->__commonAdmin_LoggedInCheck();

        //uri - action segment
        $action = $this->uri->segment(3);

        //refresh task status (all tasks)
        $this->refresh->taskStatus('all');
		
		//create pulldown lists
        $this->__pulldownLists();

        //search project name
        $this->data['vars']['search_project_name'] = $this->input->post('search_project_name');

        //re-route to correct method
        switch ($action) {

            case 'edit-task':
                $this->__editTask();
                break;
				
			case 'search-projects':
                $this->__formSearchProjects();
                break;

            default:
                $this->__home();
        }

        //load view
        $this->__flmView('admin/main');
		
		
		

    }

    /**
     * loads the main dashboard
     */
    protected function __home()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //payments data
        $this->data['reg_fields'][] = 'payments_sum';
        $this->data['fields']['payments_sum'] = $this->payments_model->periodicPaymentsSum('all', '');
        $this->data['debug'][] = $this->payments_model->debug_data;
		
		//retrieve any search cache query string
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;

        //offset - used by sql to detrmine next starting point
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);

        //load members projects
        //$this->__myProjects();

        //due invoices
        $this->__dueInvoices();

        //load members tasks
        $this->__myTasks();

        //my projects timeline
        $this->__getEventsTimeline();
		
		//RSS feed
        $this->__getFeed();
		
    }

	 /**
     * get rss feed
     */
    protected function __getFeed()
    {

        //get results and save for tbs block merging
		$this->data['reg_blocks'][] = 'feed';
		$this->data['blocks']['feed'] = $this->feed_model->listFeed('search');
    }
	
	
	
    /**
     * sum up my invoices
     */
    protected function __dueInvoices()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //sum up all 'due' invoices
        $due_invoices = $this->invoices_model->dueInvoices('all', '', 'due');
        $this->data['debug'][] = $this->invoices_model->debug_data;
        $this->data['vars']['due_invoices'] = '';
        for ($i = 0; $i < count($due_invoices); $i++) {
            $this->data['vars']['due_invoices'] += $due_invoices[$i]['amount_due'];
        }

        //sum up all 'overdue' invoices
        $overdue_invoices = $this->invoices_model->dueInvoices('all', '', 'overdue');
        $this->data['debug'][] = $this->invoices_model->debug_data;
        $this->data['vars']['overdue_invoices'] = '';
        for ($i = 0; $i < count($overdue_invoices); $i++) {
            $this->data['vars']['overdue_invoices'] += $overdue_invoices[$i]['amount_due'];
        }

    }

    /**
     * load some of my projects
     */
    protected function __myProjects()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		
		
        $projects = $this->project_members_model->membersProjects(0, 'search', $this->data['vars']['my_id'], 'open');
		
		$this->data['blk1'] = $projects;
		
        $this->data['debug'][] = $this->project_members_model->debug_data;

      /*
        //first project
              $this->data['reg_fields'][] = 'project_one';
              $this->data['fields']['project_one'] = (isset($projects[0])) ? $projects[0] : array();
      
              //second project
              $this->data['reg_fields'][] = 'project_two';
              $this->data['fields']['project_two'] = (isset($projects[1])) ? $projects[1] : array();
      
              //visibility of first project
              if (is_array($this->data['fields']['project_one']) && !empty($this->data['fields']['project_one'])) {
                  $this->data['visible']['wi_project_one'] = 1;
              } else {
                  $this->data['visible']['wi_project_none'] = 1;
              }
      
              //visibility of second project
              if (is_array($this->data['fields']['project_two']) && !empty($this->data['fields']['project_two'])) {
                  $this->data['visible']['wi_project_two'] = 1;
              }*/
      

    }

    /**
     * load some of my tasks
     */
    protected function __myTasks()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //results limit
        $limit = 50;

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'mytasks';
        $this->data['blocks']['mytasks'] = $this->tasks_model->myPendingTasks($limit);
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //visibility - show table or show nothing found
        if (count($this->data['blocks']['mytasks']) > 0)
        {
            $this->data['visible']['wi_tasks_seemore_link'] = 1;
            $this->data['visible']['wi_tasks_chart'] = 1;
        }
        else
        {
            $this->data['visible']['wi_tasks_none'] = 1;
        }

    }

    /**
     * edit a task
     */
    protected function __editTask()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/home');
        }

        /** PERMISSION CHECK
         *  only the following users can carry out this action
         *  (1) owner of the task
         *  (2) global admin
         *  (3) project leader
         */
        if ($next) {

            //get task super users
            $superusers = $this->tasks_model->superUsers($this->input->post('tasks_id'));
            $this->data['task_super_users'] = $superusers; //just for debugging
            $this->data['debug'][] = $this->tasks_model->debug_data;

            //check if team member has permission to edit
            if (in_array($this->data['vars']['my_id'], $superusers) || $this->data['vars']['my_group'] == 1) {

                //permission granted
                $next = true;

            } else {

                //permission denied
                $this->notices('error', $this->data['lang']['lang_permission_denied'], 'noty');

                //halt
                $next = false;
            }

        }

        //validate form & display any errors
        if ($next) {
            if (!$this->__flmFormValidation('edit_task')) {

                //show error
                $this->notices('error', $this->form_processor->error_message);
                $next = false;
            }
        }

        //validate hidden fields
        if ($next) {
            if (!is_numeric($_POST['tasks_id']) || $_POST['tasks_events_id'] == '') {

                //log this error
                $this->__errorLogging(__line__, __function__, __file__, 'Editing task failed: [Some or All] Required hidden form fileds missing or invalid');

                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty');

                $next = false;
            }
        }

        //check end date is not behind start date
        if ($next) {

            if (strtotime($this->input->post('tasks_end_date')) < strtotime($this->input->post('tasks_start_date'))) {

                //show error
                $this->notices('error', $this->data['lang']['lang_end_date_cannot_be_before_start_date'], 'noty');

                $next = false;
            }
        }

        //edit task
        if ($next) {
            if ($this->tasks_model->editTask()) {
                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');
            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty');
            }
        }
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //refresh project progress
        if ($next) {
            if (isset($_POST['task_project_id']) && is_numeric($_POST['task_project_id'])) {
                $this->refresh->updateProjectPercentage($this->input->post('task_project_id'));
                $this->data['debug'][] = $this->refresh->debug_data;
            }
        }

        //show task page
        $this->__home();
    }

    /**
     * get ann events from my projects
     */
    protected function __getEventsTimeline()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //try to create 'comma separated' list of my projects
        if ($next) {

            //check if I have projects
            if ($this->data['vars']['my_projects_list']) {

                //get project events (timeline)
                $this->data['reg_blocks'][] = 'timeline';
                $this->data['blocks']['timeline'] = $this->project_events_model->getEvents($this->data['vars']['my_projects_list'], 'project-list');
                $this->data['debug'][] = $this->project_events_model->debug_data;

                //further process events data
                $this->data['blocks']['timeline'] = $this->__prepEvents($this->data['blocks']['timeline']);

                //show timeline
                $this->data['visible']['show_timeline'] = 1;

            } else {

                //show no events found
                $this->data['visible']['show_no_timeline'] = 1;
            }

        }

    }

	protected function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //[all clients]
        $data = $this->clients_model->allClients('clients_company_name', 'ASC');
        $this->data['debug'][] = $this->clients_model->debug_data;
        $this->data['lists']['all_clients'] = create_pulldown_list($data, 'clients', 'id');
		
		
        //[all team members]
        $data = $this->teamprofile_model->allTeamMembers('team_profile_full_name', 'ASC');
        $this->data['debug'][] = $this->teamprofile_model->debug_data;
        $this->data['lists']['all_members'] = create_pulldown_list($data, 'team_members', 'class_name');

        //[all user emails]
        $data = $this->projects_model->allProjects('projects_title', 'ASC');
        $this->data['debug'][] = $this->projects_model->debug_data;
        $this->data['lists']['all_projects'] = create_pulldown_list($data, 'projects', 'name');

    }

    /**
     * additional data preparations project events (timeline) data
     */
    protected function __prepEvents($thedata = '')
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
     * validates forms for various methods in this class
     * 
     * @param string $form identify the form to validate
     */
    protected function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //form validation
        if ($form == 'edit_task') {

            //check required fields
            $fields = array(
                'tasks_text' => $this->data['lang']['lang_title'],
                'tasks_start_date' => $this->data['lang']['lang_start_date'],
                'tasks_end_date' => $this->data['lang']['lang_end_date'],
                'tasks_milestones_id' => $this->data['lang']['lang_milestone'],
                'tasks_assigned_to_id' => $this->data['lang']['lang_assigned_to']);
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

        //post data
        $this->data['post'] = $_POST;

        //get data
        $this->data['get'] = $_GET;

        //url segmets array
        $this->data['url_segments'] = $this->uri->segment_array();

        $this->load->view($view, array('data' => $this->data));
    }

	protected function __formSearchProjects()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //create array containg all post data in format:: array('name'=>$this->input->post('name));
        $search_array = array();
        foreach ($_POST as $key => $value) {
            $search_array[$key] = $this->input->post($key);
        }

        //save serch query in database & get id of database record
        $search_id = $this->input->save_query($search_array);

        //change url to "list" and redirect with cached search id.
        redirect("admin/home/$search_id");
    }

}

/* End of file home.php */
/* Location: ./application/controllers/admin/home.php */
