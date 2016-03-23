<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Milestones related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Tasks extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . 'project.tasks.html';

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
    public function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonClient_LoggedInCheck();

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

        //show wi_project_milestones widget
        $this->data['visible']['wi_project_milestones'] = 1;

        //get the action from url
        $action = $this->uri->segment(4);

        //route the request
        switch ($action) {

            case 'view':
                $this->__tasksView();
                break;
            case 'add':
                $this->_tasksAdd();
                break;
            default:
                $this->__tasksView();
                break;
        }

        //css - active tab
        $this->data['vars']['css_active_tab_tasks'] = 'side-menu-main-active';

        //load view
        $this->__flmView('client/main');

    }

    /**
     * main-handler function
     * manage all project milestones
     *
     */
    private function __tasksView()
    {
        if(isset($_POST['signoff'], $_POST['taskid']))
        {
            $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 
            $signoff = intval($_POST['signoff']);
            $taskid = intval($_POST['taskid']);

			$memo = @$_POST['memo'];
			if (!empty($memo)){
				$prepared = $db->prepare("UPDATE tasks SET memo = ? WHERE tasks_id = ?");
				$prepared->execute(array($memo, $taskid));
			}

            $prepared = $db->prepare("SELECT * FROM tasks WHERE tasks_id = ?");
            $prepared->execute(array($taskid));
            $row = $prepared->fetch(PDO::FETCH_ASSOC);


            if ($row['signedoff'] == 1){
                //not approved
                if ($signoff != 1){
                    //approved
                    $prepared = $db->prepare("UPDATE tasks SET signedoff = 1 WHERE tasks_id = ?");
                    $prepared->execute(array($taskid));
                }
            }elseif ($row['signedoff'] != 0){
                //approved
                echo "You can not change a tasks approval status that has already been approved, contact David or Jonathan for support.";
            }

            if ($row['signedoff'] == 0){
                if ($signoff == 1){
                    //not approved notify admin
                    $prepared = $db->prepare("UPDATE tasks SET tasks_status = 'pending' WHERE tasks_id = ?");
                    $prepared->execute(array($taskid));

                    $prepared = $db->prepare("UPDATE tasks SET accepted = ? WHERE tasks_id = ?");
                    $prepared->execute(array(3, $taskid)); //notify admin

                    $prepared = $db->prepare("UPDATE tasks SET signedoff = ? WHERE tasks_id = ?");
                    $prepared->execute(array(1, $taskid));

                    //send email to admins
                }else{
                    //approved no need to notify admin mark as done
					$prepared = $db->prepare("UPDATE tasks SET tasks_status = 'completed' WHERE tasks_id = ?");
                    $prepared->execute(array($taskid));

                    $prepared = $db->prepare("UPDATE tasks SET accepted = ? WHERE tasks_id = ?");
                    $prepared->execute(array(4, $taskid)); //done

                    $prepared = $db->prepare("UPDATE tasks SET signedoff = ? WHERE tasks_id = ?");
                    $prepared->execute(array(2, $taskid));

					//added by Tomasz
					$this->load->model('client_model');
					$this->load->model('task_model');
					$this->load->model('project_model');
					$this->load->model('task_activity_model');
					$this->load->helper('application');
					
					//calculate cost of task
					$task = $this->task_model->find($taskid);
					$cost = $task->hourslogged * get_cost_per_hours($task->billingcategory);

					//reduce amount from client's balance
					$client_id = $this->session->userdata('client_users_clients_id');
					$client = $this->client_model->find($client_id);

					//update first
					$balance = $client->credit_amount_remaining - $cost;
					$udata = array('credit_amount_remaining' => $balance);
					$this->client_model->update($client_id, $udata);
					
					//log activity
					$this->task_activity_model->insert([
						'client_id' => $client_id,
						'project_id'=> $task->tasks_project_id,
						'task_id'	=> $task->tasks_id,
					]);

					
					//if balance is under 125, then update project status to on hold
					if($balance < 125)
					{
						$field = "projects_id = $task->tasks_project_id AND projects_status = 'in progress'";
						$this->project_model->db
							->where($field, NULL)
							->update('projects', array('projects_status' => 'on hold - pending funds'));
					}
					//echo $this->project_model->db->last_query(); exit;
					//end by Tomasz
                }
            }
        }

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
            $this->data['reg_blocks'][] = 'tasks';
            $this->data['blocks']['tasks'] = $this->tasks_model->listTasks($offset, 'search', $this->project_id);
            $this->data['debug'][] = $this->tasks_model->debug_data;
            

            //count results rows - used by pagination class
            $rows_count = $this->tasks_model->listTasks($offset, 'count', $this->project_id);
            $this->data['debug'][] = $this->tasks_model->debug_data;

            //sorting pagination data that is added to pagination links
            $sort_by = ($this->uri->segment(8) == 'desc') ? 'desc' : 'asc';
            $sort_by_column = ($this->uri->segment(7) == '') ? 'sortby_id' : $this->uri->segment(7);

            //http://mydomain.com/client/project/2/milestones/view/all/sortby_pending/asc
            //pagination
            $config = pagination_default_config(); //load all other settings from helper
            $config['base_url'] = site_url("client/project/" . $this->project_id . "/tasks/view/" . $this->uri->segment(6) . "/$sort_by/$sort_by_column");
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
                $this->data['vars'][$column] = site_url("client/project/" . $this->project_id . "/tasks/view/" . $this->uri->segment(6) . "/$link_sort_by/$column/$offset");
            }

            //visibility - show table or show nothing found
            if ($rows_count > 0) {

                //show table
                $this->data['visible']['wi_milestones_table'] = 1;

            } else {

                //show nothing found
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_results_found']);
            }
            
            //added by Tomasz
            $this->load->model('task_model');
            
            $this->task_model->set_alias();            
            $this->data['vars']['totals'] = $this->task_model
                ->select("SUM( if(tasks_status='completed', estimatedhours*IFNULL(bcat_rate, 0), 0) ) completed_total", FALSE)
                ->join_billing_category()
                ->where('tasks_project_id', $this->project_id)
                ->find_one_fill_zero();
            //end by Tomasz
        }
        
    }
    
    private function _tasksAdd()
    {
        $this->load->model('task_model');
        $this->load->model('team_profile_model');
        
        $primary_id = $this->team_profile_model->find_primary_id();
        
        $data = [
            'tasks_milestones_id' => $this->input->post('tasks_milestones_id'),
            'tasks_project_id' => $this->input->post('tasks_project_id'),
            'tasks_client_id' => $this->input->post('tasks_client_id'),
            'tasks_assigned_to_id' => $primary_id,
            'tasks_text' => $this->input->post('tasks_text'), 
            'tasks_start_date' => $this->input->post('tasks_start_date'),
            'tasks_end_date' => '',
            'tasks_created_by_id' => $this->session->userdata('client_users_id'),
            'tasks_status' => 'unassigned',
            'tasks_events_id' => $this->input->post('tasks_events_id'),
            'estimatedhours' => $this->input->post('estimatedtaskhours'),
            'hourslogged' => 0,
            'timedoctortaskid' => 0
        ];
        
        $insert_id = $this->task_model->insert($data);
        
        $project_id = $this->input->post('tasks_project_id');
        redirect("client/tasks/$project_id/view");
    }

    /**
     * loads the view
     *
     * @param string $view the view to load
     */
    private function __flmView($view = '')
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
/* Location: ./application/controllers/client/milestones.php */