<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Myprojects related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Sales extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'sales.html';

        //css settings
        $this->data['vars']['css_menu_heading_myprojects'] = 'heading-menu-active'; //menu
        $this->data['vars']['css_menu_myprojects'] = 'open'; //menu

        //default page title
        //$this->data['vars']['main_title'] = $this->data['lang']['lang_my_projects'];
        $this->data['vars']['main_title'] = 'Sales';
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';
		
		$this->load->model('sales_model');
		$this->load->model('users_model');
		$this->load->model('clients_model');
		
		
		$this->data['vars']['menu'] = 'sales';
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
        $this->__commonAdmin_LoggedInCheck();

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'list':
                $this->__listSales();
                break;

            case 'view':
                $this->__view();
        		$this->__getEventsTimeline();
				$this->__leadDetails();
                break;
			case 'lead-details':
				$this->__leadDetailsModal();
				$this->__leadDetails();
				break;
			case 'lead-details-save':
				$this->__leadDetailsSave();
				break;
			case 'event-details':
				$this->__eventDetails();
                break;
			case 'delete':
				$this->__delete();	
				break;
			case 'event-delete-details':
				$this->__eventDetails();
				$this->__eventDelete();
				break;
			case 'event-delete':
				$this->__deleteEvent();
				break;
			case 'add-modal':
				$this->__addLeadModal();
				break;
			case 'add-lead':
				$this->__addLead();
				break;
			case 'add-activity':
                $this->__addActivity();
                break;
			case 'create-client':
				$this->__createClientModal();
				$this->__leadDetails();
            default:
                $this->__listSales();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * list a members own projects
     */
    function __listSales()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/myprojects/list/in-progress/0
        * (2)->controller
        * (3)->router
        * (4)->status (open/closed)
        * (5)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;


		//get results and save  for tbs block merging
        $this->data['blk1'] = $this->sales_model->searchSales($offset, 'search');

        //uri segments
        $status = $this->uri->segment(4);
        $offset = (is_numeric($this->uri->segment(5))) ? $this->uri->segment(5) : 0;

        //additional data
        $members_id = $this->data['vars']['my_id'];

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'myprojects';
        $this->data['reg_blocks'][] = 'myprojects2';
        $this->data['blocks']['myprojects'] = $this->project_members_model->membersProjects($offset, 'search', $members_id, $status);
        $this->data['blocks']['myprojects2'] = $this->project_members_model->membersProjects($offset, 'search', $members_id, $status);
        $this->data['debug'][] = $this->project_members_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->project_members_model->membersProjects($offset, 'count', $members_id, $status);
        $this->data['debug'][] = $this->project_members_model->debug_data;

        //pagination
        $config = pagination_default_config(); //
        $config['base_url'] = site_url("/admin/myprojects/list/$status");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 5; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //visibility
        if ($rows_count > 0) {
            //show side menu
            $this->data['visible']['wi_projects_table'] = 1;
        } else {
            //show mothing found
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

        //append to main title
        switch ($status) {

            case 'in-progress':
                $this->data['vars']['main_title'] .= ' - ' . $this->data['lang']['lang_in_progress'];
                break;

            case 'closed':
                $this->data['vars']['main_title'] .= ' - ' . $this->data['lang']['lang_closed'];
                break;

            case 'behind-schedule':
                $this->data['vars']['main_title'] .= ' - ' . $this->data['lang']['lang_behind_schedule'];
                break;
        }

    }

    function __view($id = ''){
        $this->data['controller_profiling'][] = __function__;
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'sales.view.html';
        if (!is_numeric($id)) {  
                $id = $this->uri->segment(4);
            }

		
        $this->data['vars']['main_title'] = "Sales";
		$this->data['vars']['current_item'] = $this->uri->segment(4);
		$this->data['vars']['datetime'] = gmdate("Y-m-d H:i");

		if($_POST[save_note] == 'true') $this->sales_model->saveLeadNote($_POST[lead_id], $_POST[leads_description]);
		
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

	function __getEventsTimeline()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //try to create 'comma separated' list of my projects
        if ($next) {

                //get project events (timeline)
                $this->data['reg_blocks'][] = 'timeline';
                //$this->data['blocks']['timeline'] = $this->lead_events_model->getEvents($this->data['vars']['my_projects_list'], 'project-list');
                
                $this->data['blocks']['timeline'] = $this->sales_model->getEvents($this->data['vars']['current_item'], 'lead-list');
                $this->data['debug'][] = $this->lead_events_model->debug_data;

                //further process events data
                $this->data['blocks']['timeline'] = $this->__prepEvents($this->data['blocks']['timeline']);

                //show timeline
                $this->data['visible']['show_timeline'] = 1;
        }
    }

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

	public function __EventDetails()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

		$this->data['template_file'] = PATHS_ADMIN_THEME . 'sales.event.details.modal.html';
		$this->data['vars']['current_item'] = $this->uri->segment(4);
		
        //flow control
        $next = true;

        //try to create 'comma separated' list of my projects
        if($next)
        {
			$this->data['details'] = $this->sales_model->getEventDetails($this->data['vars']['current_item']);
                        $this->data['vars']['elementDetails'] = $this->data['details'][0];
//                        echo '<pre>';
//			print_r($this->data['vars']['elementDetails']);
//			echo '</pre>';

        }
    }

	public function __leadDetailsModal()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

		$this->data['template_file'] = PATHS_ADMIN_THEME . 'sales.lead.details.modal.html';
		$this->data['vars']['current_item'] = $this->uri->segment(4);
		
        //flow control
        $next = true;

        //try to create 'comma separated' list of my projects
        if ($next) {
        	//added by Tomasz
			$this->load->model('teamprofile_model');
			
			$data = $this->teamprofile_model->allTeamMembers('team_profile_full_name', 'ASC');
			$this->data['debug'][] = $this->teamprofile_model->debug_data;
			$this->data['lists']['all_team_members'] = create_pulldown_list($data, 'team_members', 'id');
			//end by Tomasz
			
			//$this->data['details'] = $this->sales_model->getEventDetails($this->data['vars']['current_item']);
            //$this->data['vars']['elementDetails'] = $this->data['details'][0];

        }
    }
	function __createClientModal()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

		$this->data['template_file'] = PATHS_ADMIN_THEME . 'sales.create.client.modal.html';
		$this->data['vars']['current_item'] = $this->uri->segment(4);
		
        //flow control
        $next = true;

        //try to create 'comma separated' list of my projects
        if ($next) {
        	
			
			//$this->data['details'] = $this->sales_model->getEventDetails($this->data['vars']['current_item']);
            //$this->data['vars']['elementDetails'] = $this->data['details'][0];

        }
    }
	function __leadDetails()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

		$this->data['vars']['current_item'] = $this->uri->segment(4);
		
        //flow control
        $next = true;

        //try to create 'comma separated' list of my projects
        if ($next) {
			$result = $this->sales_model->getLeadDetails($this->data['vars']['current_item']);
			
			$this->data['vars']['leadDetails'] = $result[0];
			if($result[0]['leads_clients_id'] > 0)
			{
				$this->data['vars']['clientDetails'] = $this->clients_model->clientDetails($result[0]['leads_clients_id']);
				$clients = $rows_count = $this->users_model->clientUsers($result[0]['leads_clients_id'] );
				//$clients = $this->clients_model->clientDetails($result[0]['leads_clients_id']);
				$this->data['blk1'] = $clients;
			}	
            
			
			
        }

    }
	function __addLeadModal()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

		$this->data['template_file'] = PATHS_ADMIN_THEME . 'sales.add.modal.html';

		//added by Tomasz
		$this->load->model('teamprofile_model');
		
		$data = $this->teamprofile_model->allTeamMembers('team_profile_full_name', 'ASC');
        $this->data['debug'][] = $this->teamprofile_model->debug_data;
        $this->data['lists']['all_team_members'] = create_pulldown_list($data, 'team_members', 'id');
		//end by Tomasz
		//$this->data['vars']['current_item'] = $this->uri->segment(4);
		
 
    }
	function __EventDelete()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

		$this->data['template_file'] = PATHS_ADMIN_THEME . 'sales.event.delete.modal.html';
		$this->data['vars']['current_item'] = $this->uri->segment(4);
		
        //flow control
        $next = true;

        //try to create 'comma separated' list of my projects
        if ($next) {
	
			$this->data['details'] = $this->sales_model->getEventDetails($this->data['vars']['current_item']);
            $this->data['vars']['details'][0] = $this->data['details'];

         $edit = $this->input->post('edit_id');
         $leadId = $this->input->post('lead_id');
         if($edit){
            $this->sales_model->deleteEvent($edit);
            redirect('admin/sales/view/'.$leadId);
         }
        }
    }
    
        function __deleteEvent(){
                    //profiling
        $this->data['controller_profiling'][] = __function__;
		
        //flow control
        $next = true;

        //try to create 'comma separated' list of my projects
        if ($next) {
         $delete = $this->input->post('delete_id');
         $leadId = $this->input->post('lead_id');
         if(isset($delete)){
            $this->sales_model->deleteEvent($delete);
            redirect('admin/sales/view/'.$leadId);
         }
        }
        }
    
	public function __addActivity()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
        $edit = $this->input->post('edit_id');
        $segmentId = $this->input->post('lead_id');
        //flow control
        $next = true;

        //prevent direct access
        if (! isset($_POST['submit'])) {
            //redirect to form instead
            redirect('admin/sales');
        }


        $lead_id = $this->input->post('lead_id');
        $leads_events_type = $this->input->post('leads_events_type');
        $leads_events_date = $this->input->post('leads_events_date');
        $lead_events_date_next = $this->input->post('lead_events_date_next');
        $leads_events_description = $this->input->post('leads_events_description');

        //save information to database & get the id of this new event
        if ($next)
        {
            if($edit)
            {
                $lead_event_id = $this->sales_model->editLeadEvent($lead_id, $leads_events_type, $leads_events_date, $lead_events_date_next, $leads_events_description, $edit);
            }
            else
            {
                $lead_event_id = $this->sales_model->addLeadEvent($lead_id, $leads_events_type, $leads_events_date, $lead_events_date_next, $leads_events_description);
            }

            //was the project created ok
            if($lead_event_id)
            {
		      redirect('admin/sales/view/'.$lead_id);
            }
            else
            {
                //show error
                $this->notifications('wi_notification', $this->data['lang']['lang_request_could_not_be_completed']);
            }
        }
    }

	function __leadDetailsSave()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
        $edit = $this->input->post('edit_id');
        $segmentId = $this->input->post('lead_id');
        //flow control
        $next = true;

        //prevent direct access
        if (! isset($_POST['submit'])) {
            //redirect to form instead
            redirect('admin/sales/view/'.$_POST['lead_id']);
        }



        //save information to database & get the id of this new event
        if ($next) {

            $lead_event_id = $this->sales_model->editLead($_POST['lead_id'], $_POST['leads_name'], $_POST['leads_company'], $_POST['leads_telephone'], $_POST['leads_email'], $_POST['leads_www'], $_POST['leads_description_edit'], $_POST['leads_value'], $_POST['leads_hot'], $_POST['leads_lost'], $_POST['sales_person']);
			redirect('admin/sales/view/'.$_POST['lead_id']);
           
            
        }
    }
	function __addLead()
    {
		
		//validation
		$this->load->helper(array('form', 'url'));

		$this->load->library('form_validation');

		$this->form_validation->set_rules('leads_name', 'Name', 'required');
		//$this->form_validation->set_rules('leads_company', 'Company', 'required');
		//$this->form_validation->set_rules('leads_telephone', 'Telephone', 'required');
		$this->form_validation->set_rules('leads_email', 'Email', 'required');
		//$this->form_validation->set_rules('leads_www', 'Website', 'required');
		//$this->form_validation->set_rules('leads_description', 'Description', 'required');
		
		if ($this->form_validation->run() == FALSE)
		{
			redirect('admin/sales');
		}
		else
		{
	        //profiling
	        $this->data['controller_profiling'][] = __function__;
	        $edit = $this->input->post('edit_id');
	        $segmentId = $this->input->post('lead_id');
	        //flow control
	        $next = true;
	
	        //prevent direct access
	        if (! isset($_POST['submit'])) {
	            //redirect to form instead
	            redirect('admin/sales');
	        }
	
	
	
	        //save information to database & get the id of this new event
	        if ($next) {
	          
	            $this->sales_model->addLead($_POST['leads_name'], $_POST['leads_company'], $_POST['leads_telephone'], $_POST['leads_email'], $_POST['leads_www'], $_POST['leads_description'], $_POST['leads_value'], $_POST['leads_hot'], $_POST['leads_lost'], $_POST['sales_person']);
				redirect('admin/sales');
	            
	        }
		}
    }


}

/* End of file myprojects.php */
/* Location: ./application/controllers/admin/myprojects.php */
