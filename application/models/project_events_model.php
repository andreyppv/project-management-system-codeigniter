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
class Project_events_model extends Super_Model
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

    // -- addEvent ----------------------------------------------------------------------------------------------
    /**
     * record a new project event
     *
     * @param	string $event_data
     * @return	array
     */
    function addEvent($event_data = array())
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //initial data verification
        if (! is_array($event_data)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [event_data=$events_data]", '');
            return false;
        }

        //get vars from array
        foreach ($event_data as $key => $value) {
            $$key = $value;
        }

        //second data verications
        if (! is_numeric($project_events_project_id) || ! is_numeric($project_events_user_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [project_events_user_id ($project_events_user_id)--OR-- project_events_project_id ($project_events_project_id)]", '');
            return false;
        }

        //get vars from array and escape for mysql
        foreach ($event_data as $key => $value) {
            $$key = $this->db->escape($value);
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO project_events (
                                          project_events_date,
                                          project_events_details,
                                          project_events_action,
                                          project_events_user_id,
                                          project_events_user_type,
                                          project_events_project_id,
                                          project_events_type,
                                          project_events_target_id
                                          )VALUES(
                                          NOW(),
                                          $project_events_details,
                                          $project_events_action,
                                          $project_events_user_id,
                                          $project_events_user_type,
                                          $project_events_project_id,
                                          $project_events_type,
                                          $project_events_target_id)");

        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if ($results > 0) {
            return $results;
        } else {
            return false;
        }
    }

    // -- getEvents ----------------------------------------------------------------------------------------------
    /**
     * retrieve all events for a project
     *
     *
     * @param numeric $project_id
     * @param string $id_type 'single-project', 'project-list' [project list is comma seperated]
     * @return array
     */
    function getEvents($project_id = '', $id_type = 'single-project')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['timeline_limit'])) ? $this->data['settings_general']['timeline_limit'] : 100;

        //validate id
        if ($id_type == 'single-project') {

            //validate project id
            if (! is_numeric($project_id)) {
                $this->__debugging(__line__, __function__, 0, "Invalid Data [project id=$project_id]", '');
                return false;
            }

            //escape params items
            $project_id = $this->db->escape($project_id);

            //conditional sql for single project
            $conditional_sql .= " AND project_events_project_id = $project_id";
        }


        //validate id
        if ($id_type == 'project-list') {
            //conditional sql for single project
            $conditional_sql .= " AND project_events_project_id IN($project_id)";       
        }
        
                
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        $query = $this->db->query("SELECT project_events.*, client_users.*, team_profile.*
                                          FROM project_events 
                                          LEFT OUTER JOIN client_users
                                          ON project_events.project_events_user_id = client_users.client_users_id
                                          LEFT OUTER JOIN team_profile
                                          ON project_events.project_events_user_id = team_profile.team_profile_id
                                          WHERE 1 = 1
                                          $conditional_sql
                                          ORDER BY project_events_id DESC
                                          LIMIT $limit");

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

    // -- bulkDelete ----------------------------------------------------------------------------------------------
    /**
     * delete all events based on list of project ID's
     * typically used when deleting project/s 
     *
     * @param string $projects_list a mysql array/list formatted projects list [e.g. 1,2,3,4]
     * @return bool
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
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting project events, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM project_events
                                          WHERE project_events_project_id IN($projects_list)");
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

/* End of file project_events_model.php */
/* Location: ./application/models/project_events_model.php */
