<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Myprojects related functions
 *
 * @author   Tomasz Nowak
 * @access   public
 * @since    2015-10-21
 */
class Myprojects extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'myprojects.new.html';

        //css settings
        $this->data['vars']['css_menu_heading_myprojects'] = 'heading-menu-active'; //menu
        $this->data['vars']['css_menu_myprojects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_my_projects'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';
        
        $this->data['vars']['menu'] = 'myprojects';

        //login check
        $this->__commonAdmin_LoggedInCheck();
        
        $this->load->model('project_member_model');
        $this->project_member_model->set_alias();
        
        $this->list_limit = $this->data['settings_general']['results_limit'];
    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    function index($offset = 0)
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;
        
        // build conditions
        $member_id = $this->data['vars']['my_id'];
        $status = $this->_get_status_param($this->input->get('status'));
        
        $where = array();
        $where['t1.project_members_team_id'] = $member_id;
        if($status != '')
        {
            $status = str_replace('-', ' ', $status);
            $where['t2.projects_status'] = $status;
        }
        else
        {
            $where['t2.projects_status NOT IN ("closed")'] = null;
        }
        
        // build order by
        $order = $this->_get_order();
        $order_by = $this->_get_orderby();
        if($order == '')
        {
            $order = 'projects_date_created';
            $order_by = 'desc';
        }
        $this->data['vars']['order'] = $this->input->get('order');
        $this->data['vars']['orderby'] = $this->input->get('orderby');
        
        // get total result
        $this->data['reg_blocks'][] = 'myprojects';
        $this->data['blocks']['myprojects'] = $this->project_member_model
            ->select_with_info($member_id)
            ->join_project()
            ->join_client()
            ->where($where)
            ->order_by($order, $order_by)
            ->limit($this->list_limit, $offset)
            ->find_all();
        //echo $this->project_member_model->last_sql(); exit;
        
        // get total numbers
        $rows_count = $this->project_member_model
            //->select_with_info($member_id)
            ->join_project()
            //->join_client()
            ->where($where)
            ->count_all();
        
        // setup pagination
        $this->_setup_pagination($rows_count);

        //visibility
        if ($rows_count > 0) {
            //show side menu
            $this->data['visible']['wi_projects_table'] = 1;
        } else {
            //show mothing found
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }
        
        //append to main title
        $this->_get_main_title($status);
        
        //add base url
        $this->data['vars']['base'] = site_url('admin/myprojects');
        
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

    //////////////////////////////////////////////////////////////
    // Private Methods
    //////////////////////////////////////////////////////////////
    private function _get_status_param($status)
    {
        $result = '';
        switch($status)
        {
            case 'completed':
                $result = $status;
                break;
            case 'progress':
                $result = 'in-progress';
                break;
            case 'schedule':
                $result = 'behind-schedule';
                break;
        }
        
        return $result;
    }
    
    private function _get_main_title($status) 
    {
        switch ($status) {
            case 'progress':
                $this->data['vars']['main_title'] .= ' - ' . $this->data['lang']['lang_in_progress'];
                break;
            case 'closed':
                $this->data['vars']['main_title'] .= ' - ' . $this->data['lang']['lang_closed'];
                break;     
            case 'completed':
                $this->data['vars']['main_title'] .= ' - ' . $this->data['lang']['lang_completed'];
                break;
            case 'schedule':
                $this->data['vars']['main_title'] .= ' - ' . $this->data['lang']['lang_behind_schedule'];
                break;
        }
    }
    
    private function _get_order()
    {
        $result = '';
        
        $order = $this->input->get('order');
        switch($order)
        {
            case 'project':
                $result = 'projects_title';
                break;
            case 'client':
                $result = 'clients_company_name';
                break; 
            case 'deadline':
                $result = 'project_deadline';
                break;
            case 'progress':
                $result = 'projects_progress_percentage';
                break;
            case 'status':
                $result = 'projects_status';
                break;
            case 'date':
                $result = 'projects_date_created';
                break;
            default:
                //$result = 'projects_date_created';
                break;
        } 
        
        return $result;
    }
    
    private function _get_orderby()
    {
        $result = 'asc';
        
        $orderby = $this->input->get('orderby');
        switch($orderby)
        {
            case 'asc':
            case 'desc':
                $result = $orderby;
                break;
        } 
        
        return $result;
    }
    
    private function _get_suffix()
    {
        $result = array();
        
        //add status
        $status = $this->input->get('status');
        if($status) $result[] = "status=$status";
        
        //add order fields
        $order = $this->_get_order();
        $order_by = $this->_get_orderby();
        if($order != '') 
        {
            $result[] = "order=".$this->input->get('order');
            $result[] = "orderby=$order_by";
        }
        
        $str = '';
        if(!empty($result))
        {
            $str = "?" . join('&amp;', $result);
        }
        
        return $str;
    }
    
    private function _setup_pagination($rows_count)
    {
        $config = pagination_default_config();
        $config['base_url']     = site_url("admin/myprojects/index");
        $config['total_rows']   = $rows_count;
        $config['per_page']     = $this->list_limit;
        $config['uri_segment']  = 4; //the offset var
        $config['suffix']       = $this->_get_suffix();   
        $config['first_url']    = site_url("admin/myprojects".$this->_get_suffix());
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();    
    }
}

/* End of file myprojects.php */
/* Location: ./application/controllers/admin/myprojects.php */
