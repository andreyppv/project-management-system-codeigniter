<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Timer_model extends Super_Model
{

    var $debug_methods_trail;
    var $number_of_rows;

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

    // -- updateTimer ----------------------------------------------------------------------------------------------
    /**
     * start/stops/resets a particular project timer
     * @param numeric $timer_id]
     * @parama  string [new_status: stopped / running /reset ]...for 'reset' actual new status will be 'stopped'
     * @return	array
     */

    function updateTimer($timer_id = '', $new_status = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate $project_id
        if (! is_numeric($timer_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [timer id=$timer_id]", '');
            return false;
        }

        //validate $new_status
        if (! in_array($new_status, $this->data['common_arrays']['timer_status'])) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [timer_status=$new_status]", '');
            //log this error
            log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Updating project timer failed. Invalid data]");
            return false;

        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        /** STOP THE TIMER **/
        if ($new_status == 'stopped') {
            $query = $this->db->query("UPDATE timer SET 
                                              timer_seconds = 
                                              (SELECT TIMESTAMPDIFF(SECOND, timer_start_datetime, NOW())+timer_seconds),
                                              timer_start_datetime = NOW(),
                                              timer_status = 'stopped'
                                              WHERE timer_id = '$timer_id'
                                              AND timer_status = 'running'");

        }

        /** START THE TIMER **/
        if ($new_status == 'running') {
            $query = $this->db->query("UPDATE timer SET 
                                              timer_start_datetime = NOW(),
                                              timer_status = 'running'
                                              WHERE timer_id = '$timer_id'
                                              AND timer_status = 'stopped'");

        }

        /** RESET THE TIMER **/
        if ($new_status == 'reset') {
            $query = $this->db->query("UPDATE timer SET 
                                              timer_start_datetime = NOW(),
                                              timer_seconds = 0,
                                              timer_status = 'stopped'
                                              WHERE timer_id = '$timer_id'");

        }

        //results
        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if (is_numeric($results) || $results === 0) {
            return true;
        } else {
            return false;
        }
    }

    // -- updateTimerTime ----------------------------------------------------------------------------------------------
    /**
     * update the timers seconds
     * @param	string [name: groups name], [age: users age]
     * @return	array
     */

    function updateTimerTime($timer_id = '', $new_time)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($timer_id) || ! is_numeric($new_time)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE timer
                                          SET 
                                          timer_seconds = $new_time,
                                          timer_start_datetime = NOW()
                                          WHERE timer_id = $timer_id");

        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

    // -- projectTime ----------------------------------------------------------------------------------------------
    /**
     * get the sum of all the time for a given project
     * @param numeric $project_id: project id]
     * @param	mixed [users_id: 'all'/user id]     
     * @return	array
     */

    function projectTime($project_id = '', $users_id = 'all')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$project_id]", '');
            return false;
        }

        //is this for just one user
        if (is_numeric($users_id)) {
            $conditional_sql .= " AND timer_team_member_id = '$users_id'";
        }

        //escape params items
        $project_id = $this->db->escape($project_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT SUM(timer_seconds) AS project_time
                                          FROM timer 
                                          WHERE timer_project_id = $project_id
                                          $conditional_sql");

        $results = $query->row_array();
        $results = $results['project_time']; //get sum results

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    // -- timerStatus--------------------------------------------------------------------------------------------
    /**
     * return the current timer status
     * @param numeric $project_id: project id]
     * @param	mixed [users_id: 'all'/user id]     
     * @return	mixed (running/stopped/none)
     */

    function timerStatus($project_id = '', $user_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($project_id) || ! is_numeric($user_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$project_id OR user_id:$user_id]", '');
            return false;
        }

        //escape params items
        $project_id = $this->db->escape($project_id);
        $user_id = $this->db->escape($user_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * FROM timer 
                                          WHERE timer_project_id = $project_id
                                          AND timer_team_member_id = $user_id");

        $results = $query->row_array();

        //valid status
        $valid_status = array('running', 'stopped');

        $results = $results['timer_status'];

        //do we have a valid status
        if (! in_array($results, $valid_status)) {
            $results = 'none';
        }

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        return $results;
    }

    // -- addTimer--------------------------------------------------------------------------------------------
    /**
     * create a new timer for a user
     * @param numeric $project_id: project id]
     * @param numeric $users_id: team members id]     
     * @return	bool
     */

    function addNewTimer($project_id = '', $user_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($project_id) || ! is_numeric($user_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$project_id OR user_id:$user_id]", '');
            return false;
        }

        //escape params items
        $project_id = $this->db->escape($project_id);
        $user_id = $this->db->escape($user_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO timer (
                                               timer_project_id,
                                               timer_team_member_id,
                                               timer_start_datetime
                                               )VALUES(
                                               $project_id,
                                               $user_id,
                                               NOW())
                                               ON DUPLICATE KEY UPDATE
                                               timer_project_id = $project_id");

        $results = $this->db->affected_rows();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        if ($results > 0) {
            return $results;
        } else {
            return false;
        }
    }

    // -- timerDetails--------------------------------------------------------------------------------------------
    /**
     * return the whole for a users time for a particular project
     * @param numeric $project_id: project id]
     * @param	mixed [users_id: 'all'/user id]     
     * @return	mixed (running/stopped/none)
     */

    function timerDetails($project_id = '', $user_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($project_id) || ! is_numeric($user_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$project_id OR user_id:$user_id]", '');
            return false;
        }

        //escape params items
        $project_id = $this->db->escape($project_id);
        $user_id = $this->db->escape($user_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * FROM timer 
                                          WHERE timer_project_id = $project_id
                                          AND timer_team_member_id = $user_id");

        $results = $query->row_array();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        return $results;
    }

    // -- timerOwner--------------------------------------------------------------------------------------------
    /**
     * return the current timer owner
     * @param numeric $timer_id: timer id]
     * @return	numeric
     */

    function timerOwner($timer_id)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($timer_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$timer_id]", '');
            return false;
        }

        //escape params items
        $timer_id = $this->db->escape($timer_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * FROM timer 
                                          WHERE timer_id = $timer_id");

        $results = $query->row_array();
        $results = $results['timer_team_member_id'];

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        return $results;
    }

    // -- timerCurrentTime--------------------------------------------------------------------------------------------
    /**
     * return the current timer time
     * @param numeric $timer_id: timer id]
     * @return	numeric
     */

    function timerCurrentTime($timer_id)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($timer_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$timer_id]", '');
            return false;
        }

        //escape params items
        $timer_id = $this->db->escape($timer_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * FROM timer 
                                          WHERE timer_id = $timer_id");

        $results = $query->row_array();
        $results = (is_numeric($results['timer_seconds'])) ? $results['timer_seconds'] : 0;

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        return $results;
    }

    // -- refeshProjectTimers ----------------------------------------------------------------------------------------------
    /**
     * refreshes all the timers for a given project
     * @param numeric $project_id]
     * @return	array
     */

    function refeshProjectTimers($project_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate $project_id
        if (! is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$project_id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        /** STOP THE TIMER **/

        $query = $this->db->query("UPDATE timer SET 
                                              timer_seconds = 
                                              (SELECT TIMESTAMPDIFF(SECOND, timer_start_datetime, NOW())+timer_seconds),
                                              timer_start_datetime = NOW()
                                              WHERE timer_project_id = '$project_id'
                                              AND timer_status = 'running'");

        //results
        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if (is_numeric($results) || $results === 0) {
            return true;
        } else {
            return false;
        }
    }

    // -- viewTimers ----------------------------------------------------------------------------------------------
    /**
     * view timers (all or team members)
     * @param numeric $offset: pagination]
     * @param	mixed [type: 'all' / members id]
     * @param	string [status: 'running'/'stopped]
     * @return	array
     */

    function viewTimers($offset = 0, $type = 'search', $view = 'all', $status = 'running')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = 'AND timer.timer_start_datetime >= date_sub(curdate(),INTERVAL WEEKDAY(curdate()) -0 DAY)';
        $limiting = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;
		
		//are we searching all, or just a members
        if (is_numeric($view)) {
            $user_sql = " AND timer.timer_team_member_id = $view";
        }
		
        //---is there any search data-----------------

        if ($this->input->get('date_from')) {
            $date_from = $this->db->escape($this->input->get('date_from'));
            $conditional_sql = " AND timer.timer_start_datetime >= $date_from";
        }
        if ($this->input->get('date_to')) {
        	
            $date_to = $this->db->escape($this->input->get('date_to'));
            $conditional_sql .= " AND timer.timer_start_datetime <= $date_to";
			//die(var_dump($conditional_sql));
        }
        if (is_numeric($this->input->get('team_profile_id'))) {
            $member = $this->db->escape($this->input->get('team_profile_id'));
            $user_sql = " AND timer.timer_team_member_id = $member";
        }		

		//die(var_dump($conditional_sql));
        //are we searching records or just counting rows
        //row count is used by pagination class
       

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT timer.*, team_profile.*, projects.*, tasks.*, assigned.team_profile_full_name AS assigned_by
                                             FROM timer
                                             LEFT OUTER JOIN team_profile
                                             ON timer.timer_team_member_id = team_profile.team_profile_id
                                             LEFT OUTER JOIN projects
                                             ON timer.timer_project_id = projects.projects_id
                                             LEFT OUTER JOIN tasks
                                             ON timer.timer_taskid = tasks.tasks_id
                                             LEFT OUTER JOIN team_profile AS assigned
                                             ON assigned.team_profile_id = tasks.tasks_created_by_id
                                             WHERE 1 = 1
                                             $conditional_sql
                                             $user_sql
                                             ORDER BY timer.timer_id DESC");
        //results (search or rows)
        //rows are used by pagination class & results are used by tbs block merge
        if ($type == 'search' || $type == 'results') {
            $results = $query->result_array();
        } else {
            $results = $query->num_rows();
        }
        $time=0;
		foreach ($results as $res)
		{
			$time=$time+$res['timer_seconds'];
		}
		$H = floor($time / 3600);
		$i = ($time / 60) % 60;
		$s = $time % 60;
		$results[0]['total']=sprintf("%02d:%02d:%02d", $H, $i, $s);
        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------
        //return results
        return $results;

    }

    // -- bulkDelete ----------------------------------------------------------------------------------------------
    /**
     * bulk delete based on list of project ID's
     * typically used when deleting project/s 
     * @param	string [projects_list: a mysql array/list formatted projects list] [e.g. 1,2,3,4]
     * @return	bool
     */

    function bulkDelete($projects_list = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //flow control
        $next = true;

        //sanity check - ensure we have a valid projects_list, with only numeric id's
        $lists = explode(',', $projects_list);
        for ($i = 0; $i < count($lists); $i++) {
            if (! is_numeric(trim($lists[$i]))) {
                //log error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting timers, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM timer
                                          WHERE timer_project_id IN($projects_list)");
        }
        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

}

/* End of file timer_model.php */
/* Location: ./application/models/timer_model.php */
