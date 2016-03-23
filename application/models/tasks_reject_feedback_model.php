<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Tasks_reject_feedback_model extends Super_Model
{

    var $debug_methods_trail;

    // -- __construct ----------------------------------------------------------------------------------------------

    function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        // Call the Model constructor
        parent::__construct();
    }

    // -- addTaskRejectFeeback----------------------------------------------------------------------------------------------
    /**
     * add new task rejection feedback to database
     *
     * 
     * @param   void
     * @return  mixed [record insert id / bool(false)]
     */

    function addTaskRejectFeeback($projects_id, $tasks_id, $team_profile_id, $feedback)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		
		// Check if it already exists
		$this->db->where('projects_id', $projects_id);
		$this->db->where('tasks_id', $tasks_id);
		$this->db->where('team_profile_id', $team_profile_id);
		$this->db->select('tasks_reject_feedback_id');
		$results = $this->db->get('tasks_reject_feedback')->row();
 
		$data = array(
			'projects_id' => $projects_id,
			'tasks_id' => $tasks_id,
			'team_profile_id' => $team_profile_id,
			'feedback' => $feedback,
		);
			
 		// Already exists, update
 		if ($results)
		{
			$results = $results->tasks_reject_feedback_id;
			
			$this->db->where('tasks_reject_feedback_id', $results);
			$this->db->update('tasks_reject_feedback', $data);
		}
 		// Doesn't exist, create one
		else
		{
			$this->db->insert('tasks_reject_feedback', $data);
			$results = $this->db->insert_id(); //(last insert item)
		}

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        //return new tasks_reject_feedback_id or false
        if ($results > 0) {
            return $results;
        } else {
            return false;
        }
    }

}

/* End of file tasks_reject_feedback_model.php */
/* Location: ./application/models/tasks_reject_feedback_model.php */