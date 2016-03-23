<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Task_activity_model extends BF_Model
{
    protected $table        = 'task_activities';
    protected $key          = 'id';
    protected $date_format  = 'datetime';
    protected $set_created  = TRUE;

    //--------------------------------------------------------------------
    public function __construct()
    {
        parent::__construct();
        
    }
    
    public function join_project()
    {
        return $this->join('projects', 'project_id=projects_id', 'left');
    }
    
    public function join_task()
    {
        return $this->join('tasks', 'task_id=tasks_id', 'left');
    }
    
    public function join_team_members()
    {
        return $this->join('team_profile', 'tasks_assigned_to_id=team_profile_id', 'left');
    }
}//end Settings_model
