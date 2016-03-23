<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all timers related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Timers extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'timers.html';
		
        //css settings
        $this->data['vars']['css_menu_heading_mytimes'] = 'heading-menu-active';
        if (is_numeric($this->uri->segment(4))) {
            $this->data['vars']['css_menu_heading_mytimes'] = 'heading-menu-active'; //menu
            $this->data['vars']['css_menu_mytimers'] = 'open'; //menu
            //set page title
            $this->data['vars']['main_title'] = 'My Project Timers';
            //for search
            $this->data['vars']['timers_view_type'] = $this->uri->segment(4);

        } else {
            $this->data['vars']['css_menu_topnav_timers'] = 'nav_alternative_controls_active'; //menu
            //set page title
            $this->data['vars']['main_title'] = 'All Project Timers';
            //for search
            $this->data['vars']['timers_view_type'] = 'all';
            //visble members list
            $this->data['visible']['all_members'] = 1;
        }

        //default page title
        $this->data['vars']['main_title_icon'] = '<i class="icon-time"></i>';
        
        //load models
        $this->load->model('team_profile_model');
        $this->load->model('task_activity_model');
        $this->load->helper('application');
        
        //$this->data['vars']['my_group'] = 5;
        $this->team_profile_id = $this->session->userdata('team_profile_id');
        $profile = $this->team_profile_model->find($this->team_profile_id);
                 
        $this->is_admin = $profile->team_profile_groups_id == 1 ? true : false;
        $this->list_limit = $this->data['settings_general']['results_limit'];
        
        $this->data['vars']['menu'] = 'timers';
    }
    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    function index($offset = 0)
    {   
        $offset = $this->uri->segment(4) ? $this->uri->segment(4) : 0;
        
        //get all team members
        $this->data['vars']['team_members'] = create_dropdown($this->team_profile_model
            ->order_by('team_profile_full_name')
            ->format_dropdown('team_profile_id', 'team_profile_full_name'));
        
        //get search conditions
        if($this->input->post('btn_search') && $offset != 0)
        {
            redirect($this->uri->uri_string());
        }
        
        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');
        $team_profile_id = $this->input->post('team_profile_id');
        
        $this->data['vars']['date_from'] = $date_from;
        $this->data['vars']['date_to'] = $date_to;
        $this->data['vars']['team_profile_id'] = $team_profile_id;
        
        $where = array();
        if($date_from) $where['created_on >='] = $date_from;
        if($date_to) $where['created_on <='] = $date_to;
        if($this->is_admin)
        {
            if($team_profile_id) $where['tasks_assigned_to_id'] = $team_profile_id;
        }
        else
        {
            $where['tasks_assigned_to_id'] = $this->team_profile_id;
        }
        
        //get task activities
        $total_rows = $this->task_activity_model
            ->join_project()
            ->join_task()
            ->join_team_members()
            ->where($where)
            ->count_all();
        
        $result = $this->task_activity_model
            ->join_project()
            ->join_task()
            ->join_team_members()
            ->where($where)
            ->limit($this->list_limit, $offset)
            ->order_by('created_on', 'desc')
            ->find_all(1);
            
        $this->data['reg_blocks'][] = 'result';
        $this->data['blocks']['result'] = $result;
        //echo $this->task_activity_model->last_sql();
        
        if ($total_rows > 0) {
            //show side menu
            $this->data['visible']['wi_timers_table'] = 1;
        } else {
            //show mothing found
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        } 
        
        //setup pagination
        $this->_setup_pagination('admin/timers/index', $total_rows);
         
        //load view
        $this->__flmView('admin/main');

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

    private function _setup_pagination($base_uri, $total_rows, $uri_segment=4)
    {
        $this->load->library('pagination');
        
        $this->pager = array();
        $this->pager['full_tag_open']   = '<div class="dataTables_wrapper no-footer"><div class="dataTables_paginate paging_simple_numbers"><ul>';
        $this->pager['full_tag_close']  = '</ul></div></div>';
        $this->pager['next_link']       = 'Next';
        $this->pager['prev_link']       = 'Prev';
        $this->pager['next_tag_open']   = '<li class="paginate_button">';
        $this->pager['next_tag_close']  = '</li>';
        $this->pager['prev_tag_open']   = '<li class="paginate_button">';
        $this->pager['prev_tag_close']  = '</li>';
        $this->pager['first_tag_open']  = '<li class="paginate_button">';
        $this->pager['first_tag_close'] = '</li>';
        $this->pager['last_tag_open']   = '<li class="paginate_button">';
        $this->pager['last_tag_close']  = '</li>';
        $this->pager['cur_tag_open']    = '<li class="paginate_button current">';
        $this->pager['cur_tag_close']   = '</li>';
        $this->pager['num_tag_open']    = '<li class="paginate_button">';
        $this->pager['num_tag_close']   = '</li>';
        
        $this->pager['base_url']    = site_url($base_uri);
        $this->pager['total_rows']  = $total_rows;
        $this->pager['per_page']    = $this->list_limit;
        $this->pager['uri_segment'] = $uri_segment;
        
        $this->pagination->initialize($this->pager);
        //echo $this->pagination->create_links(); exit;
        $this->data['vars']['pagination'] = $this->pagination->create_links();
    }
}

/* End of file timers.php */
/* Location: ./application/controllers/admin/timers.php */
