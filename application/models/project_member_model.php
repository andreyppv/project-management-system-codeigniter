<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Project_member_model extends BF_Model
{
    protected $table        = 'project_members';
    protected $key          = 'project_members_index';
    protected $date_format  = 'datetime';
    protected $set_created  = FALSE;
    protected $set_modified = FALSE;

    //--------------------------------------------------------------------
    public function __construct()
    {
        parent::__construct();
    }
    
    public function join_project()
    {
        return $this->join('projects t2', 't2.projects_id=t1.project_members_project_id', 'left');
    }
    
    public function join_client()
    {
        return $this->join('clients t3', 't3.clients_id=t2.projects_clients_id', 'left'); 
    }
    
    public function select_with_info($member_id)
    {
        $sub_sql_count_tasks = "
            (SELECT COUNT(tasks_id) 
            FROM tasks 
            WHERE tasks_project_id = t2.projects_id
                AND tasks_assigned_to_id = $member_id
                AND tasks_status NOT IN('completed')
            ) AS pending_tasks";
        $sub_sql_timer = "
            (SELECT timer_seconds
            FROM timer
            WHERE timer_project_id = t2.projects_id AND timer_team_member_id = $member_id
            LIMIT 1) AS timer";
        return $this->select("t1.*, t2.*, t3.clients_company_name, $sub_sql_timer, $sub_sql_count_tasks", false);
    }
}//end Settings_model
