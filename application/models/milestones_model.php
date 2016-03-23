<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all milestones related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Milestones_model extends Super_Model
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

    // -- allMilestone ----------------------------------------------------------------------------------------------
    /**
     * retrieve all mile stone for a given project
     *
     * 
     * @param	string $orderby: table sorting] (optional)
     * @param   string $sort: asc/desc] (optional)
     * @param   numeric $project_id] (optional)
     * @return	array
     */

    function allMilestones($orderby = 'milestones_title', $sort = 'ASC', $project_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //check if any specifi ordering was passed
        if (! $this->db->field_exists($orderby, 'milestones')) {
            $orderby = 'milestones_title';
        }

        //check if sorting type was passed
        $sort = ($sort == 'asc' || $sort == 'desc') ? $sort : 'ASC';

        //if project_id has been specified, show only for this project
        if (is_numeric($project_id)) {
            $conditional_sql = "AND milestones_project_id = $project_id";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM milestones 
                                          WHERE 1 = 1
                                          $conditional_sql
                                          ORDER BY $orderby $sort");

        $results = $query->result_array(); //multi row array

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }

    // -- listMilestones ----------------------------------------------------------------------------------------------
    /**
     * search/list milestone, paginated
     * @return	array
     */

    function listMilestones($offset = 0, $type = 'search', $project_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //if no valie client id, return false
        if (! is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [project id=$project_id]", '');
            return false;
        }

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //create conditional statements, from url segment (6)
        switch ($this->uri->segment(6)) {

            case 'all':
                $conditional_sql = '';
                break;

            case 'in_progress':
                $conditional_sql = "AND milestones_end_date > NOW() AND milestones_status = 'pending'";
                break;

            case 'behind_schedule':
                $conditional_sql = "AND milestones_end_date < NOW() AND milestones_status = 'pending'";
                break;

            case 'completed':
                $conditional_sql = "AND milestones_status = 'completed'";
                break;

        }

        //http://mydomain.com/admin/project/2/milestones/view/all/sortby_pending/asc

        //create the order by sql additional condition
        //these sorting keys are passed in the url and must be same as the ones used in the controller.
        $sort_order = ($this->uri->segment(8) == 'desc') ? 'desc' : 'desc';
        $sort_columns = array(
            'sortby_id' => 'milestones.milestones_id',
            'sortby_status' => 'milestones.milestones_status',
            'sortby_title' => 'milestones.milestones_title',
            'sortby_start_date' => 'milestones.milestones_start_date',
            'sortby_end_date' => 'milestones.milestones_end_date');
        $sort_by = (array_key_exists(''.$this->uri->segment(7), $sort_columns)) ? $sort_columns[$this->uri->segment(7)] : 'milestones.milestones_id';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //CLIENT-PANEL: limit to this clients data
        if (is_numeric($this->client_id) || $this->uri->segment(1) == 'client') {
            $client_id = $this->client_id;
            $conditional_sql .= " AND milestones.milestones_client_id = '$client_id'";
        }

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *, ROUND(IFNULL((tmp.completed_task_count/tmp.task_count*100),0)) as percentage
                                       FROM (SELECT milestones.*, milestones.milestones_id AS id,
                                                    (SELECT COUNT(tasks_id) FROM tasks 
                                                             WHERE tasks_milestones_id = id) AS task_count,
                                                    (SELECT COUNT(tasks_id) FROM tasks
                                                             WHERE tasks_milestones_id = id
                                                             AND tasks_status = 'completed') AS completed_task_count
                                             FROM milestones
                                             WHERE milestones_project_id = $project_id
                                             $conditional_sql
                                             $sorting_sql
                                             $limiting) AS tmp");
        //results (search or rows)
        //rows are used by pagination class & results are used by tbs block merge
        if ($type == 'search' || $type == 'results') {
            $results = $query->result_array();
        } else {
            $results = $query->num_rows();
        }

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }

	// -- addTasktList ----------------------------------------------------------------------------------------------
    /**
     * get result from function listMilestones and add to every milestone array of tasks
     *
     * @param $results - result from function searchClients
     * @return	array
     */

    function addTaskList($results, $id)
    {
    		
		$color[0] = 'text-primary';
		$color[1] = 'text-success';
		$color[2] = 'text-warning';
		$color[3] = 'text-danger';    	
		foreach($results as $key => $value)
		{
			$color_index = 0;
			$i = 0;
			$query = 'SELECT * FROM projects WHERE projects_clients_id = "'.$results[$key][clients_id].'" ';
			$result = $this->db->query("SELECT tasks.*, team_profile.*,
                                          projects.*
                                          FROM tasks
                                            LEFT OUTER JOIN projects
                                            ON projects.projects_id = tasks.tasks_project_id
                                            LEFT OUTER JOIN team_profile
                                            ON team_profile.team_profile_id = tasks.tasks_assigned_to_id
                                          WHERE tasks.tasks_milestones_id = ".$results[$key][milestones_id]."
                                          AND tasks.tasks_project_id = $id
                                          AND tasks.tasks_status != 'completed'");
			$result=$result->result();
			foreach ($result as $r)
			{
				
				$color_index++;
				if($color_index == 4) $color_index = 0;
				$results[$key][tasks][$i][project_id] = $r->tasks_project_id;
				$results[$key][tasks][$i][tasks_id] = $r->tasks_id;
				$results[$key][tasks][$i][tasks_text] = $r->tasks_text;
				
				$results[$key][tasks][$i][classname] = $color[$color_index];
				
				$i++;
				
			}
			$results[$key][milestones_title] = str_replace('â€“','-',$results[$key][milestones_title]);
		}
		//die(var_dump($results));
		return $results;
	}

    // -- addMilestone ----------------------------------------------------------------------------------------------
    /**
     * add new milstone to database
     *
     * 
     * @param	void
     * @return	mixed [record insert id / bool(false)]
     */

    function addMilestone()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO milestones (
                                          milestones_project_id,
                                          milestones_title,
                                          milestones_start_date,
                                          milestones_end_date,
                                          milestones_created_by,
                                          milestones_events_id,
                                          milestones_client_id
                                          )VALUES(
                                          $milestones_project_id,
                                          $milestones_title,
                                          $milestones_start_date,
                                          $milestones_end_date,
                                          $milestones_created_by,
                                          $milestones_events_id,
                                          $milestones_client_id)");

        $results = $this->db->insert_id(); //(last insert item)

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        //return new  client_id or false
        if ($results > 0) {
            return $results;
        } else {
            return false;
        }
    }

    // -- countMilestones ----------------------------------------------------------------------------------------------
    /**
     * counts milestones "for given project" (of various statu e.g in_progress, completed, behind_schedule
     *
     * 
     * @param	string [count_type: the type of milestone ststus], [project_id]
     * @return	array
     */

    function countMilestones($project_id = '', $count_type = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [project id=$project_id]", '');
            return false;
        }

        //count types array
        $count_types = array(
            'in progress',
            'completed',
            'behind schedule',
            'uncompleted',
            'all');

        //checkf if type is valid
        if (! in_array($count_type, $count_types)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [count_type =$count_type]", '');
            return false;
        }

        //create conditional statements
        switch ($count_type) {

            case 'all':
                $conditional_sql = '';
                break;

            case 'completed':
                $conditional_sql = "AND milestones_status = 'completed'";
                break;

            case 'in progress':
                $conditional_sql = "AND milestones_status = 'in progress'";
                break;

            case 'behind schedule':
                $conditional_sql = "AND milestones_status = 'behind schedule'";
                break;

            case 'uncompleted':
                $conditional_sql = "AND milestones_status NOT IN ('completed')";
                break;
        }

        //escape params items
        $project_id = $this->db->escape($project_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM milestones 
                                          WHERE milestones_project_id = $project_id
                                          $conditional_sql");

        $results = $query->num_rows(); //count rows

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }

    // -- editMilestone ----------------------------------------------------------------------------------------------
    /**
     * edit a milestones details
     *
     * 
     * @param	void
     * @return	numeric [affected rows]
     */

    function editMilestone()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if milestone id value exists in the post data
        if (! is_numeric($this->input->post('milestones_id'))) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [milestone id: is not numeric/is unavailable]", '');
            return false;
        }

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE milestones
                                          SET 
                                          milestones_title = $milestones_title,
                                          milestones_start_date = $milestones_start_date,
                                          milestones_end_date = $milestones_end_date
                                          WHERE milestones_id = $milestones_id");

        $results = $this->db->affected_rows(); //affected rows

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if (is_numeric($results) || $transaction_result === true) {
            return true;
        } else {
            return false;
        }
    }

    // -- deleteMilestone ----------------------------------------------------------------------------------------------
    /**
     * delete a single milestone based on its ID
     *
     * 
     * @param numeric $id]
     * @param   string $delete_by: milestone-id, project-id, client-id]
     * @return	bool
     */

    function deleteMilestone($id = '', $delete_by = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [milestones_id=$id]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting milestone failed (milestones_id: $id is invalid)]");
            return false;
        }

        //check if delete_by is valid
        $valid_delete_by = array(
            'milestone-id',
            'project-id',
            'client-id');

        if (! in_array($delete_by, $valid_delete_by)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [delete_by=$delete_by]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting milestones failed (delete_by: $delete_by is invalid)]");
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //conditional sql
        switch ($delete_by) {

            case 'milestone-id':
                $conditional_sql = "AND milestones_id = $id";
                break;

            case 'project-id':
                $conditional_sql = "AND milestones_project_id = $id";
                break;

            case 'client-id':
                $conditional_sql = "AND milestones_client_id = $id";
                break;

            default:
                $conditional_sql = "AND milestones_client_id = '0'"; //safety precaution else we wipe out whole table
                break;

        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM milestones
                                          WHERE 1 = 1
                                          $conditional_sql");

        $results = $this->db->affected_rows(); //affected rows

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if ($results > 0 || $transaction_result === true) {
            return true;
        } else {
            return false;
        }
    }

    // -- milestoneDetails ----------------------------------------------------------------------------------------------
    /**
     * full details of a single milestone
     *
     * 
     * @param numeric
     * @return	array
     */

    function milestoneDetails($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [milestone id=$group_id]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM milestones 
                                          WHERE milestones_id = $id");

        $results = $query->row_array(); //single row array

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);

        //return results
        return $results;
    }

    // -- bulkDelete ----------------------------------------------------------------------------------------------
    /**
     * bulk delete based on list of project ID's
     * typically used when deleting project/s 
     *
     * 
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
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting milestones, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM milestones
                                          WHERE milestones_project_id IN($projects_list)");
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

/* End of file milestones_model.php */
/* Location: ./application/models/milestones_model.php */
