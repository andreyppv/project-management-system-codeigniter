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
class Project_members_model extends Super_Model
{

    public $debug_methods_trail;

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

    // -- listProjectmembers ----------------------------------------------------------------------------------------------
    /**
     * a list of all project members for a specified project
     *    
     * @param numeric $project_id
     * @return  array
     */

    public function listProjectmembers($project_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [project id=project_id]", '');
            return false;
        }

        //escape params items
        $project_id = intval($project_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT project_members.*, team_profile.*
                                          FROM project_members
                                          RIGHT JOIN team_profile
                                          ON team_profile.team_profile_id = project_members.project_members_team_id
                                          WHERE project_members_project_id = $project_id
                                          ORDER BY project_members.project_members_project_lead DESC");

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

    // -- countMyProjects ----------------------------------------------------------------------------------------------
    /**
     * count a members projects
     *
     * @param numeric $id project members id
     * @param string $status 'active', 'closed', 'in-progress', 'behind-schedule', 'completed' [optional]
     * @return array
     */

    public function allMembersProjects($id = '', $status = 'active')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //conditiona sql for project status
        switch ($status) {

            case 'active':
                $conditional_sql .= " AND projects.projects_status NOT IN('closed')";
                break;

            case 'closed':
                $conditional_sql .= " AND projects.projects_status = 'closed'";
                break;

            case 'in-progress':
                $conditional_sql .= " AND projects.projects_status = 'in progress'";
                break;

            case 'behind-schedule':
                $conditional_sql .= " AND projects.projects_status = 'behind schedule'";
                break;

            case 'completed':
                $conditional_sql .= " AND projects.projects_status = 'completed'";
                break;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT project_members.*, projects.*
                                          FROM project_members
                                          LEFT JOIN projects
                                          ON projects.projects_id = project_members.project_members_project_id
                                          WHERE project_members.project_members_team_id = $id
                                          $conditional_sql");

        $results = $query->result_array(); //count rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    // -- membersProjects ----------------------------------------------------------------------------------------------
    /**
     * fetch all projects for a members, based on their ID
     *     
     * @param string $offset pagination offset
     * @param string $type search/count/list
     * @param numeric $members_id members id
     * @param string $status project status )in-progress/behind-schedule/open/closed) [open: shows all not closed]
     * @return array
     */

    public function membersProjects($offset = 0, $type = 'search', $members_id = '', $status = 'in-progress')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //validation
        if (! is_numeric($members_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [members id=$members_id]", '');
            return false;
        }

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //are we searching records (i.e. paginated results)
        //row count is used by pagination class
        if ($type == 'search') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //escape data
        $members_id = $this->db->escape($members_id);
//var_dump($this->input);
    if ($this->input->get('clients_company_name')) {
            $client_id = $this->db->escape($this->input->get('clients_company_name'));
            $conditional_sql .= " AND projects.projects_clients_id = $client_id";
        }
        if ($this->input->get('projects_title')) {
            $projects_title = str_replace("'", "", $this->db->escape($this->input->get('projects_title')));
            $conditional_sql .= " AND projects.projects_title LIKE '%$projects_title%'";
        }
        if (is_numeric($this->input->get('projects_id'))) {
            $projects_id = $this->db->escape($this->input->get('projects_id'));
            $conditional_sql .= " AND projects.projects_id = $projects_id";
        }
        if ($this->input->get('projects_status') && $this->input->get('projects_status') != 'all') {
            $projects_status = $this->db->escape($this->input->get('projects_status'));
            $conditional_sql .= " AND projects.projects_status = $projects_status";
        }


        //which projects
        if ($status == 'open') {
            $conditional_sql .= " AND projects.projects_status NOT IN('closed')";
        } else {
            $status = str_replace('-', ' ', $this->db->escape($status)); //remove - added in url
            $conditional_sql .= " AND projects.projects_status = $status";
        }
//var_dump($conditional_sql);exit;
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT project_members.*,projects.*,
                                               (SELECT timer_seconds 
                                                       FROM timer 
                                                       WHERE timer_project_id = projects.projects_id AND timer_team_member_id = $members_id LIMIT 1) AS timer,
                                               (SELECT clients_company_name 
                                                       FROM clients 
                                                       WHERE clients_id = projects.projects_clients_id LIMIT 1) 
                                                       AS clients_company_name,
                                               (SELECT COUNT(tasks_id) 
                                                       FROM tasks 
                                                       WHERE tasks_project_id = projects.projects_id AND tasks_assigned_to_id = $members_id AND tasks_status NOT IN('completed')) 
                                                       AS pending_tasks
                                             FROM project_members
                                             RIGHT JOIN projects
                                             ON projects.projects_id = project_members.project_members_project_id
                                             WHERE project_members.project_members_team_id = $members_id
                                             $conditional_sql
                                             ORDER BY projects.projects_title
                                             $limiting");
        //results (search or rows)
        //rows are used by pagination class & results are used by tbs block merge
        if ($type == 'search' || $type == 'list') {
            $results = $query->result_array();
        } else {
            $results = $query->num_rows();
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

    // -- countMyProjects ----------------------------------------------------------------------------------------------
    /**
     * count a members projects
     *
     * @param numeri $id
     * @return array
     */

    public function countMyProjects($id = 0)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate
        if ($id == 0 || $id == '') {
            return;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT project_members.*, projects.*
                                          FROM project_members 
                                          RIGHT JOIN projects
                                          on project_members.project_members_project_id = projects.projects_id
                                          WHERE project_members_team_id = $id
                                          AND projects.projects_status NOT IN('closed')");

        $results = $query->num_rows(); //count rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    // -- isMemberAssigned ----------------------------------------------------------------------------------------------
    /**
     * checks if a member is curently assigned to a specified project
     *
     * @param numeric $project_id
     * @param numeric $member_id
     * @return bool
     */

    public function isMemberAssigned($project_id = '', $member_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate
        if (! is_numeric($project_id) || ! is_numeric($member_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT 1
                                          FROM project_members 
                                          WHERE project_members_team_id = $member_id
                                          AND project_members_project_id = $project_id
                                          LIMIT 1");

        $results = $query->num_rows(); //count rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if ($results > 0) {
            return true;
        } else {
            return false;
        }
    }

    // -- isProjectLeader ----------------------------------------------------------------------------------------------
    /**
     * checks if a member is curently assigned as project leader to a specified project
     *
     * @param numeric $project_id
     * @param numeric $member_id
     * @return bool
     */

    public function isProjectLeader($project_id = '', $member_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate
        if (! is_numeric($project_id) || ! is_numeric($member_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM project_members 
                                          WHERE project_members_team_id = $member_id
                                          AND project_members_project_id = $project_id
                                          AND project_members_project_lead = 'yes'");

        $results = $query->num_rows(); //count rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return (bool)$results;
    }

    // -- addMember ----------------------------------------------------------------------------------------------
    /**
     * add a team member to a project
     * 
     * @param numeric $project_id
     * @param numeric $members_id
     * @return array
     */

    public function addMember($project_id = '', $members_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape params items

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT MAX(project_members_index) id FROM project_members");
        $row = $query->row();
        $project_members_index = isset($row->id) ? $row->id+1 : 1;

        $this->db->insert('project_members', array(
            'project_members_index'  => $project_members_index,
            'project_members_team_id'  => $members_id,
            'project_members_project_id'  => $project_id
        ));
        $results = $this->db->affected_rows();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return (bool)$results;
    }


    public function getPrimaryMembers()
    {
        $query = $this->db->query("SELECT team_profile_id FROM team_profile WHERE is_primary=1");

        return $query->result_array();
    }

    // -- deleteProjectMember ----------------------------------------------------------------------------------------------
    /**
     * remove a member from a project
     * 
     * @param numeric $project_id
     * @param numeric $member_id
     * @return bool
     */

    public function deleteProjectMember($project_id = '', $member_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($project_id) || ! is_numeric($member_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data", '');
            return false;
        }

        //escape params items
        $project_id = $this->db->escape($project_id);
        $member_id = $this->db->escape($member_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM project_members
                                          WHERE project_members_project_id = $project_id
                                          AND project_members_team_id = $member_id");

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

    // -- updateProjectLead ----------------------------------------------------------------------------------------------
    /**
     * update the lead member of a project
     *
     * @param numeric $project_id
     * @param numeric $lead_members_id
     * @return  bool
     */

    public function updateProjectLead($project_id = '', $lead_members_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no value client id, return false
        if (! is_numeric($project_id) || ! is_numeric($lead_members_id)) {
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: invalid data (project_id or lead_members_id)]");
            return false;
        }

        //escape data
        $project_id = $this->db->escape($project_id);
        $lead_members_id = $this->db->escape($lead_members_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        //set all other members to 'no' and set this member to 'yes'
        $query = $this->db->query("UPDATE project_members
                                         SET
                                         project_members_project_lead = CASE WHEN project_members_team_id = $lead_members_id THEN 'yes'
                                         ELSE 'no'
                                         END
                                         WHERE project_members_project_id = $project_id");

        $results = $this->db->affected_rows();

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

    // -- bulkDelete ----------------------------------------------------------------------------------------------
    /**
     * bulk delete based on list of project ID's
     * typically used when deleting project/s 
     * 
     * @param string $projects_list a mysql array/list formatted projects list [e.g. 1,2,3,4]
     * @return bool
     */

    public function bulkDelete($projects_list = '')
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
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting project members, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM project_members
                                          WHERE project_members_project_id IN($projects_list)");
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

    // -- getProjectLead ----------------------------------------------------------------------------------------------
    /**
     * get the ID of a projects team leader
     * 
     * @param numeric $project_id
     * @return mixed  [project id]
     */

    public function getProjectLead($project_id = '')
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

        //escape params items
        $project_id = $this->db->escape($project_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT project_members.*, team_profile.*
                                          FROM project_members
                                          LEFT JOIN team_profile
                                          ON project_members.project_members_team_id = team_profile.team_profile_id
                                          WHERE project_members.project_members_project_id = $project_id
                                          AND project_members.project_members_project_lead = 'yes'
                                          LIMIT 1");

        $results = $query->row_array();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

}

/* End of file project_members_model.php */
/* Location: ./application/models/project_members_model.php */
