<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Tasks_viewers_model extends Super_Model
{

    public $debug_methods_trail;
    public $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------

    public function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        // Call the Model constructor
        parent::__construct();
    }

    public function getViewers($task_id)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        $task_id = intval($task_id);

        //_____SQL QUERY_______
        $query = $this->db->query("
            SELECT
                v.tasks_viewers_id,
                p.*
            FROM tasks_viewers v, team_profile p
            WHERE v.task_id = $task_id
                  AND v.team_profile_id = p.team_profile_id
            ORDER BY v.created
        ");
        $results = $query->result_array();

        $this->__debugging(__line__, __function__, 0, "SQL", $this->db->last_query());

        return $results;
    }

    public function listAddViewers($task_id)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        $task_id = intval($task_id);

        //_____SQL QUERY_______
        $query = $this->db->query("
            SELECT
                p.*
            FROM tasks t,
                 project_members pm,
                 team_profile p
            WHERE t.tasks_id = $task_id
                  AND t.tasks_project_id = pm.project_members_project_id
                  AND p.team_profile_id = pm.project_members_team_id

                  AND p.team_profile_id != t.tasks_assigned_to_id

                  AND NOT EXISTS (
                        select 1 from tasks_viewers v
                        where v.task_id=t.tasks_id and v.team_profile_id = p.team_profile_id
                    )
            ORDER BY p.team_profile_full_name
        ");
        $results = $query->result_array();

        $this->__debugging(__line__, __function__, 0, "SQL", $this->db->last_query());

        return $results;
    }


    public function addViewer($task_id, $team_profile_id)
    {
        $task_id = intval($task_id);
        $team_profile_id = intval($team_profile_id);

        $next = true;

        if($next)
        {
            $query = $this->db->query("
                SELECT 1 FROM tasks_viewers WHERE task_id = $task_id AND team_profile_id = $team_profile_id
            ");
            if($query->row()) $next = false;
            else              $next = true;
        }

        if($next)
        {
            $query = $this->db->query("
                SELECT 1 FROM tasks WHERE tasks_id = $task_id AND tasks_assigned_to_id = $team_profile_id
            ");
            if($query->row()) $next = false;
            else              $next = true;
        }

        $results = false;

        if($next)
        {
            $this->db->insert('tasks_viewers', array(
                'task_id'         => (int)$task_id,
                'team_profile_id' => $team_profile_id,
                'created'         => 'NOW()'
            ));

            $results = $this->db->insert_id(); //(last insert item)
        }

        return $results;
    }


    public function deleteViewer($tasks_viewers_id)
    {
        $tasks_viewers_id = intval($tasks_viewers_id);

        $this->db->where('tasks_viewers_id', $tasks_viewers_id);
        $this->db->delete('tasks_viewers');

        $results = $this->db->affected_rows();

        return (bool)$results;
    }
}

/* End of file tasks_viewers_model.php */
/* Location: ./application/models/tasks_viewers_model.php */