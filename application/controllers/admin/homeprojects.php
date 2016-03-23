<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Homeprojects extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'home.projects.html';
    }

    public function index()
    {

        //profiling::
        $this->data['controller_profiling'][] = __function__;
        
        //login check
        $this->__commonAdmin_LoggedInCheck();


        if($this->data['vars']['my_group'] == 1)
        {   //all projects
            $projects = $this->projects_model->allProjects('projects_title', 'ASC', '', 'open');

            $this->data['visible']['wi_total_billiable'] = 1;
        }
        else
        {
            $projects = $this->project_members_model->membersProjects(0, 'search', $this->data['vars']['my_id'], 'open');
        }

        foreach ($projects as $key => $project)
        {
            //client
            if(!isset($project['projects_id']))  $project['projects_id'] = $project['project_members_project_id'];
            $projects[$key] = $this->projects_model->projectClient($project['projects_id']);

            //members            
            $members = $this->project_members_model->listProjectmembers($project['projects_id']);
            $class_user_id = '';
            foreach ($members as $mbr)
            {
                $class_user_id .='user'.$mbr['project_members_team_id'].' ';
            }
            $projects[$key]['filter_class'] = strtolower(str_replace(' ', '-', $project['projects_title'])).' '.$project['status'].' '.$class_user_id;

            $projects[$key]['tasks_count'] = $this->projects_model->projectTasksCount($project['projects_id']);
        }

        //print_r($projects); exit;
        $this->data['reg_blocks'][] = 'projects';
        $this->data['blocks']['projects'] = $projects;

        //load view
        $this->__flmView('admin/main');
    }


	protected function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

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

        $this->load->view($view, array('data' => $this->data));
    }
}

/* End of file homeprojects.php */
/* Location: ./application/controllers/admin/homeprojects.php */
