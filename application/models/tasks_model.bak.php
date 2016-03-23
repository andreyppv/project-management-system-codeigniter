<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Tasks_model extends Super_Model
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

    // -- listTasks ----------------------------------------------------------------------------------------------
    /**
     * search/list tasks, paginated
     *
     * 
     * @param numeric $offset: pagination]
     * @param   string $type: search / count]
     * @param   string $status: all/pending/completed/behind-schedule/all-open]
     * @param   numeric $project_id]
     * @return  mixed      [array(rows) / false]
     */

    function listTasks($offset = 0, $type = 'search', $project_id = '', $status = 'pending')
    {
$status = "all-open";
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

        //create conditional statements, from url segment (5)
        if (is_numeric($this->uri->segment(5)) && $this->uri->segment(5) > 0) {
            $conditional_sql = "AND tasks_milestones_id = " . $this->uri->segment(5);
        }

        //are we showing MY tasks or ALL tasks
        $my_id = $this->data['vars']['my_id']; //logged in user's id
        if ($this->uri->segment(8) == 'my' && is_numeric($my_id)) {
            $conditional_sql .= " AND tasks_assigned_to_id = $my_id";
        }

        //status conditional statement
        if (in_array($status, array(
            'pending',
            'completed',
            'behind-schedule'))) {
            $status = str_replace('-', ' ', $status);
            $conditional_sql .= " AND tasks_status = '$status'";
        }

        //task status 'all-open'
        if ($status == 'all-open') {
            //$conditional_sql .= " AND tasks_status NOT IN('completed')";
        }

        //http://mydomain.com/admin/project/2/milestones/view/all/sortby_pending/asc

        //create the order by sql additional condition
        //these sorting keys are passed in the url and must be same as the ones used in the controller.
        $sort_order = ($this->uri->segment(6) == 'desc') ? 'desc' : 'asc';
        $sort_columns = array(
            'sortby_id' => 'tasks.tasks_id',
            'sortby_status' => 'tasks.tasks_status',
            'sortby_end_date' => 'tasks.tasks_end_date');
        $sort_by = (array_key_exists(''.$this->uri->segment(7), $sort_columns)) ? $sort_columns[$this->uri->segment(7)] : 'tasks.tasks_start_date';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search' || $type == 'results') {
            //$limiting = "LIMIT $limit OFFSET $offset ";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT tasks.*, team_profile.*
                                             FROM tasks
                                             LEFT OUTER JOIN team_profile
                                             ON tasks.tasks_assigned_to_id = team_profile.team_profile_id
                                             WHERE tasks_project_id = $project_id
                                             $conditional_sql
                                             $sorting_sql
                                             $limiting");

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
        $i = 0;
        foreach($results as $result){
            $milestoneID = $result['tasks_milestones_id'];
            $query = $this->db->query("SELECT `milestones_title` FROM milestones WHERE milestones_id = '".$milestoneID."'");
            $milestone = $query->result_array()[0]['milestones_title'];
            $result = array_merge($result, array("tasks_milestone_text" => $milestone));
            $results[$i] = $result;
            $i += 1;
        }

        return $results;

    }

    // -- addTask ----------------------------------------------------------------------------------------------
    /**
     * add new task to database
     *
     * 
     * @param   void
     * @return  mixed [record insert id / bool(false)]
     */

    function addTask()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape all post item
        foreach ($_POST as $key => $value) {
            if($key == "billing_category" || $key == "estimatedtaskhours") { $$key = $value; continue; }
            if($key == "tasks_text"){ $$key = '"'.$value.'"'; continue; }
            $$key = $this->db->escape($this->input->post($key));
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
        //if(empty($estimatedtaskhours)){ $estimatedtaskhours = 0; }
        //if(empty($freshbookstaskid)){ $freshbookstaskid = 0; }
        //_____SQL QUERY_______
        chdir("__freshbooksapi");
        require_once("__freshbooksinit.php");
        chdir("..");
        $timedoctortaskid = 0;//addATask($tasks_project_id, $tasks_text, $tasks_assigned_to_id);
        $query = $this->db->query("INSERT INTO tasks (
                                          tasks_assigned_to_id,
                                          tasks_client_id,
                                          tasks_created_by_id,
                                          tasks_end_date,
                                          tasks_events_id,
                                          tasks_milestones_id,
                                          tasks_project_id,
                                          tasks_start_date,
                                          tasks_text,
                                          billingcategory,
                                          estimatedhours,
                                          hourslogged,
                                          timedoctortaskid                                        
                                          )VALUES(
                                          $tasks_assigned_to_id,
                                          $tasks_client_id,
                                          $tasks_created_by_id,
                                          $tasks_end_date,
                                          $tasks_events_id,
                                          $tasks_milestones_id,
                                          $tasks_project_id,
                                          $tasks_start_date,
                                          $tasks_text,
                                          $billing_category,
                                          $estimatedtaskhours,
                                          0,
                                          '$timedoctortaskid')");

        $results = $this->db->insert_id(); //(last insert item)
        
        $now=date("Y-m-d H:i:s", NOW());
        $myname=$this->data['vars']['my_name'];
        $myavatar=$this->data['vars']['my_avatar'];
        $this->db->select('projects_title');
        $this->db->from('projects');
        $this->db->where('projects_id', str_replace("'", "", $tasks_project_id));
        $name = $this->db->get()->row();
        
        //mod by Tomasz
        /*$text=addslashes($this->data['vars']['my_name'].' added new task to project <a href="/admin/project/'.str_replace("'", "", $tasks_project_id).'/view">"'.$name->projects_title.'</a>" - "<a href="/admin/tasksexpanded/'.str_replace("'", "", $tasks_project_id).'/'.$results.'">'.$tasks_text.'</a>"');*/
        $tasks_project_id = str_replace("'", "", $tasks_project_id);
        $projects_title = str_replace("'", "", $projects_title);
        $text_template = "%s added new task to project <a href='%s'>%s</a> - <a href='%s'>%s</a>";
        $text = mysql_real_escape_string(sprintf($text_template, 
            $this->data['vars']['my_name'], 
            site_url("admin/project/$tasks_project_id/view"),
            $name->projects_title,
            site_url("admin/tasksexpanded/$tasks_project_id/$results"),
            $tasks_text
        ));
        //end by Tomasz
        
        $query = $this->db->query("INSERT INTO feed (
                                          feed_by,
                                          feed_by_avatar,
                                          date,
                                          text,
                                          type,
                                          type_id                                         
                                          )VALUES(
                                          '$myname',
                                          '$myavatar',
                                          '$now',
                                          '$text',
                                          'project',
                                          $tasks_project_id)");
        
        
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

    // -- deleteTask ----------------------------------------------------------------------------------------------
    /**
     * delete a task(s) based on a 'delete_by' id
     *
     * 
     * @param numeric   [id: reference id of item(s)]
     * @param   string    [delete_by: tasks-id, milestone-id, project-id, client-id]
     * @return  bool
     */

    function deleteTask($id = '', $delete_by = '')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [tasks_id=$id]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting task(s) failed (tasks_id: $id is invalid)]");
            return false;
        }

        //check if delete_by is valid
        $valid_delete_by = array(
            'task-id',
            'project-id',
            'milestone-id',
            'client-id');

        if (! in_array($delete_by, $valid_delete_by)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [delete_by=$delete_by]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting task(s) failed (delete_by: $delete_by is invalid)]");
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //conditional sql
        switch ($delete_by) {

            case 'task-id':
                $conditional_sql = "AND tasks_id = $id";
                break;

            case 'milestone-id':
                $conditional_sql = "AND tasks_milestones_id = $id";
                break;

            case 'project-id':
                $conditional_sql = "AND tasks_project_id = $id";
                break;

            case 'client-id':
                $conditional_sql = "AND tasks_client_id = $id";
                break;

            default:
                $conditional_sql = "AND tasks_client_id = '0'"; //safety precaution else we wipe out whole table
                break;

        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM tasks
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

    // -- tablecleanup ----------------------------------------------------------------------------------------------
    /**
     * This function will do some clean up of the table:
     *        - delete tasks with mailestones that do not exist
     *
     * 
     * @param   void
     * @return void
     */

    function tableCleanup()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM  tasks
                                              WHERE tasks_milestones_id 
                                              NOT IN (SELECT milestones_id FROM milestones)");

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

    }

    // -- countTasks ----------------------------------------------------------------------------------------------
    /**
     * counts tasks of various status and grouping
     *
     * 
     * @param numeric   [id] (optional)
     * @param   string    [id_reference: reference for the provided ID, for conditional search] (optional)
     *                               - project
     *                               - milestone
     *                               - assigned_to
     *                               - client
     *                               - created_by
     * @param   string    [status: task status] (optional)
     *                               - all
     *                               - pending
     *                               - behind schedule
     *                               - completed
     * 
     * @return  numeric (rows count)
     */

    function countTasks($id = '', $id_reference = 'project', $status = 'all', $show = 'all')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valid id, return false
        if (! is_numeric($id) && $id != '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //conditional search for the ID param, using the $id_reference
        switch ($id_reference) {

            case 'project':
                $conditional_sql .= " AND tasks_project_id = $id";
                break;

            case 'milestone':
                $conditional_sql .= " AND tasks_milestones_id = $id";
                break;

            case 'assigned_to':
                $conditional_sql .= " AND tasks_assigned_to_id = $id";
                break;

            case 'client':
                $conditional_sql .= " AND tasks_client_id = $id";
                break;

            case 'created_by':
                $conditional_sql .= " AND tasks_created_by_id = $id";
                break;
        }

        //conditional search for the ID param, using the $id_reference
        switch ($status) {

            case 'pending':
                $conditional_sql .= " AND tasks_status = 'pending'";
                break;

            case 'completed':
                $conditional_sql .= " AND tasks_status = 'completed'";
                break;

            case 'behind schedule':
                $conditional_sql .= " AND tasks_status = 'behind schedule'";
                break;
        }

        //are we showing MY tasks or ALL tasks
        $my_id = $this->data['vars']['my_id']; //logged in user's id
        if ($show == 'my' && is_numeric($my_id)) {
            $conditional_sql .= " AND tasks_assigned_to_id = $my_id";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM tasks 
                                          WHERE 1 = 1
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

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if (is_numeric($results)) {
            return $results;
        } else {
            return 0;
        }
    }

    // -- allMyTasksCounts ----------------------------------------------------------------------------------------------
    /**
     * count a members various tasks based on status. If project ID is supplied, count will be limited to that project
     *
     * 
     * @param numeric $$id
     * @return  array
     */

    function allMyTasksCounts($id = 0, $project_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($id)) {
            $id = 0;
        }

        //validate id
        if (is_numeric($project_id)) {
            $conditional_sql .= " AND tasks_project_id = '$project_id'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT (SELECT COUNT(tasks_id)
                                                  FROM tasks
                                                  WHERE tasks_status = 'pending'
                                                  AND tasks_assigned_to_id = '$id'
                                                  $conditional_sql) AS pending,
                                          (SELECT COUNT(tasks_id)
                                                  FROM tasks
                                                  WHERE tasks_status = 'completed'
                                                  AND tasks_assigned_to_id = '$id'
                                                  $conditional_sql) AS completed,
                                          (SELECT COUNT(tasks_id)
                                                  FROM tasks
                                                  WHERE tasks_status = 'behind schedule'
                                                  AND tasks_assigned_to_id = '$id'
                                                  $conditional_sql) AS behind_schedule,
                                          (SELECT COUNT(tasks_id)
                                                  FROM tasks
                                                  WHERE tasks_status NOT IN ('completed')
                                                  AND tasks_assigned_to_id = '$id'
                                                  $conditional_sql) AS all_open,
                                          (SELECT COUNT(tasks_id)
                                                  FROM tasks
                                                  WHERE tasks_assigned_to_id = '$id'
                                                  $conditional_sql) AS all_tasks
                                          FROM tasks
                                          WHERE 1 = 1
                                          LIMIT 1");

        //other results
        $results = $query->row_array(); //single row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    // -- searchTasks ----------------------------------------------------------------------------------------------
    /**
     * search tasks table and return results for all tasks (for all members or 'mytasks'...logged in member)
     * @return  array
     */

    function searchTasks($offset = 0, $type = 'search', $members = 'all')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //conditional sql
        //determine if any search condition where passed in the search form
        //actual post data is already cached into $this->input->get(), so use that instead of $_post
        if ($this->input->get('tasks_text')) {
            $tasks_text = str_replace("'", "", $this->db->escape($this->input->get('tasks_text')));
            $conditional_sql .= " AND tasks.tasks_text LIKE '%$tasks_text%'";
        }
        if ($this->input->get('tasks_status')) {
            $tasks_status = $this->db->escape($this->input->get('tasks_status'));
            $conditional_sql .= " AND tasks.tasks_status = $tasks_status";
        } else {
            $conditional_sql .= " AND tasks.tasks_status NOT IN('completed')";
        }
        if (is_numeric($this->input->get('tasks_id'))) {
            $tasks_id = $this->db->escape($this->input->get('tasks_id'));
            $conditional_sql .= " AND tasks.tasks_id = $tasks_id";
        }
        if (is_numeric($this->input->get('tasks_project_id'))) {
            $tasks_project_id = $this->db->escape($this->input->get('tasks_project_id'));
            $conditional_sql .= " AND tasks.tasks_project_id = $tasks_project_id";
        }
        if (is_numeric($this->input->get('team_profile_id'))) {
            $team_profile_id = $this->db->escape($this->input->get('team_profile_id'));
            $conditional_sql .= " AND tasks.tasks_assigned_to_id = $team_profile_id";
        }

        //create the order by sql additional condition
        //these sorting keys are passed in the url and must be same as the ones used in the controller.
        $sort_order = ($this->uri->segment(5) == 'desc') ? 'desc' : 'desc';
        $sort_columns = array(
            'sortby_taskid' => 'tasks.tasks_id',
            'sortby_taskstatus' => 'tasks.tasks_status',
            'sortby_taskduedate' => 'tasks.tasks_end_date',
            'sortby_projectid' => 'tasks.tasks_project_id');
        $sort_by = (array_key_exists(''.$this->uri->segment(6), $sort_columns)) ? $sort_columns[$this->uri->segment(6)] : 'tasks.tasks_start_date';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //are we searching 'my' tasks
        if ($members == 'mytasks') {
            $conditional_sql .= " AND tasks.tasks_assigned_to_id = " . $this->data['vars']['my_id'];
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT tasks.*, team_profile.*,
                                          projects.*
                                          FROM tasks
                                            LEFT OUTER JOIN projects
                                            ON projects.projects_id = tasks.tasks_project_id
                                            LEFT OUTER JOIN team_profile
                                            ON team_profile.team_profile_id = tasks.tasks_assigned_to_id
                                          WHERE 1=1
                                          $conditional_sql
                                          $sorting_sql
                                          $limiting");
        //results (search or rows)
        //rows are used by pagination class & results are used by tbs block merge
        if ($type == 'search') {
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

    function getTasksByProjectID($id = 0)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        
        
        if (is_numeric($id)) {
            $project_id = $this->db->escape($id);
           
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT tasks.*, team_profile.*,
                                          projects.*
                                          FROM tasks
                                            LEFT OUTER JOIN projects
                                            ON projects.projects_id = tasks.tasks_project_id
                                            LEFT OUTER JOIN team_profile
                                            ON team_profile.team_profile_id = tasks.tasks_assigned_to_id
                                          WHERE tasks.tasks_project_id = $project_id
                                          AND tasks.tasks_status != 'completed'
                                          ");
        
            $results = $query->result_array();
        

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

    // -- myPendingTasks ----------------------------------------------------------------------------------------------
    /**
     * all of a members pending tasks (none paginated). Mainly for home page display
     *
     * @paramm  numeric [limit: number of results to show]
     * 
     * @return  array
     */

    function myPendingTasks($limit = 0)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape params items
        $my_id = $this->db->escape($this->data['vars']['my_id']);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //set specified limits
        if ($limit > 0) {
            $limit_sql = "LIMIT $limit";
        }

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT tasks.*, projects.*
                                          FROM tasks
                                          LEFT OUTER JOIN projects
                                          ON projects.projects_id = tasks.tasks_project_id
                                          WHERE tasks_assigned_to_id = $my_id
                                          AND tasks_status NOT IN('completed')
                                          $limit_sql");

        //other results
        $results = $query->result_array(); //multi row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

// -- editDescription ----------------------------------------------------------------------------------------------
    /**
     * edit a tasks description
     *
     * 
     * @param   void
     * @return  numeric [affected rows]
     */

    function editDescription()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if task id value exists in the post data
        if (! is_numeric($this->input->post('tasks_id'))) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [task id: is not numeric/is unavailable]", '');
            return false;
        }

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE tasks
                                          SET 
                                          tasks_text = $tasks_text
                                          WHERE tasks_id = $tasks_id");

        $results = $this->db->affected_rows(); //affected rows

        //benchmark/debug
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

    function completeTask($task_id)
    {
        //die(del);
        //die(var_dump($task_id));
        $this->db->select('timedoctortaskid,tasks_project_id,tasks_assigned_to_id');
        $this->db->from('tasks');
        $this->db->where('tasks_id',$task_id);      
        $tasksData = $this->db->get()->row();
    
            chdir("__freshbooksapi");
            require_once("__freshbooksinit.php");
            chdir("..");
            editTask($tasksData->tasks_project_id,$tasksData->timedoctortaskid,$tasksData->tasks_assigned_to_id);
            //die();
         $query = $this->db->query("UPDATE tasks
                                          SET 
                                          tasks_status = 'completed'
                                          WHERE tasks_id = $task_id");
                                          
            return true;
        
    }

    // -- editTask ----------------------------------------------------------------------------------------------
    /**
     * edit a tasks details
     *
     * 
     * @param   void
     * @return  numeric [affected rows]
     */

    function editTask()
    {
        
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if task id value exists in the post data
        if (! is_numeric($this->input->post('tasks_id'))) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [task id: is not numeric/is unavailable]", '');
            return false;
        }

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }
        
        $this->db->select('timedoctortaskid,tasks_project_id,tasks_assigned_to_id');
        $this->db->from('tasks');
        $this->db->where('tasks_id',str_replace("'", "", $tasks_id));       
        $tasksData = $this->db->get()->row();
        if ($tasks_status == "'completed'")
        {
            chdir("__freshbooksapi");
            require_once("__freshbooksinit.php");
            chdir("..");
            editTask($tasksData->tasks_project_id,$tasksData->timedoctortaskid,$tasksData->tasks_assigned_to_id);
        }
        
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE tasks
                                          SET 
                                          tasks_assigned_to_id = $tasks_assigned_to_id,
                                          tasks_end_date = $tasks_end_date,
                                          tasks_milestones_id = $tasks_milestones_id,
                                          tasks_start_date = $tasks_start_date,
                                          tasks_status = $tasks_status,
                                          tasks_text = $tasks_text
                                          WHERE tasks_id = $tasks_id");

        $results = $this->db->affected_rows(); //affected rows

        //benchmark/debug
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
    
    // -- editAssigned ----------------------------------------------------------------------------------------------
    /**
     * change user assigned to a task
     *
     * 
     * @param   void
     * @return  numeric [affected rows]
     */

    function editAssigned()
    {
            
        
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if task id value exists in the post data
        if (! is_numeric($this->input->post('tasks_id'))) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [task id: is not numeric/is unavailable]", '');
            return false;
        }

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }
        
        $this->db->select('*');
        $this->db->from('tasks');
        $this->db->where('tasks_id',str_replace("'", "", $tasks_id));       
        $tasksData = $this->db->get()->row();
        
        chdir("__freshbooksapi");
        require_once("__freshbooksinit.php");
        chdir("..");
        editTask($tasksData->tasks_project_id,$tasksData->timedoctortaskid,$tasksData->tasks_assigned_to_id);
        
        chdir("__freshbooksapi");
        require_once("__freshbooksinit.php");
        chdir("..");
        $newTask = '"'.$tasksData->tasks_text.' (reassigned)"';
        $timedoctortaskid = addATask($tasksData->tasks_project_id, $newTask, $tasks_assigned);
        
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE tasks
                                          SET 
                                          tasks_text = $newTask,
                                          tasks_assigned_to_id = $tasks_assigned
                                          WHERE tasks_id = $tasks_id");

        $results = $this->db->affected_rows(); //affected rows

        //benchmark/debug
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

    // -- getTask ----------------------------------------------------------------------------------------------
    /**
     * return a single task based on its ID
     *
     * 
     * @param numeric $item ID]
     * @return  array
     */

    function getTask($task_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($task_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [task id=$task_id]", '');
            return false;
        }

        //escape params items
        $task_id = $this->db->escape($task_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM tasks 
                                          WHERE tasks_id = $task_id");

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
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }
    
    // -- getTaskAssignedUser ----------------------------------------------------------------------------------------------
    /**
     * return a user assigned to a task based on task ID
     *
     * 
     * @param numeric $item ID]
     * @return  array
     */

    function getTaskAssignedUser($task_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($task_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [task id=$task_id]", '');
            return false;
        }

        //escape params items
        $task_id = $this->db->escape($task_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT team_profile.*,tasks.*
                                          FROM tasks
                                          JOIN team_profile
                                          ON tasks.tasks_assigned_to_id = team_profile.team_profile_id 
                                          WHERE tasks.tasks_id = $task_id");

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
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }
    
    function getSubTasks($task_id, $projectid){
      $task_id = $this->db->escape($task_id);
      $project_id = $this->db->escape($projectid);

      //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        
        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM subtasks 
                                          WHERE task_id = $task_id AND 
                                          task_projectid = $project_id ORDER BY id DESC");

        $results = $query->result_array(); //multiple row array

        
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
        return $results;
    }

    function getTimer($task_id, $projectid, $userid){
      //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($task_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [task id=$task_id]", '');
            return false;
        }

        if (! is_numeric($projectid)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [task id=$projectid]", '');
            return false;
        }

        if (! is_numeric($userid)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [task id=$userid]", '');
            return false;
        }

        //escape params items
        $task_id = $this->db->escape($task_id);
        $projectid = $this->db->escape($projectid);
        $userid = $this->db->escape($userid);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM timer 
                                          WHERE timer_taskid = $task_id 
                                          AND timer_team_member_id = $userid 
                                          AND timer_project_id = $projectid ORDER BY timer_id DESC LIMIT 1");

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
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }

    // -- superUsers ----------------------------------------------------------------------------------------------
    /**
     * return a array team members (ID's) who have edit/delete access for this task
     *
     * 
     * @param numeric $item ID]
     * @return  array
     */

    function superUsers($task_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($task_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [task id=$task_id]", '');
            return false;
        }

        //escape params items
        $task_id = $this->db->escape($task_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT tasks.*, projects.*
                                          FROM tasks 
                                          LEFT OUTER JOIN projects
                                          ON projects.projects_id = tasks.tasks_project_id
                                          WHERE tasks_id = $task_id");

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
        //----------sql & benchmarking end----------

        //create array of users id's
        $users = array(
            $results['tasks_assigned_to_id'],
            $results['tasks_created_by_id'],
            $results['projects_team_lead_id']);
        return $users;
    }

    // -- bulkDelete ----------------------------------------------------------------------------------------------
    /**
     * bulk delete based on list of project ID's
     * typically used when deleting project/s 
     *
     * 
     * @param   string [projects_list: a mysql array/list formatted projects list] [e.g. 1,2,3,4]
     * @return  bool
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
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting tasks, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM tasks
                                          WHERE tasks_project_id IN($projects_list)");
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

/* End of file tasks_model.php */
/* Location: ./application/models/tasks_model.php */