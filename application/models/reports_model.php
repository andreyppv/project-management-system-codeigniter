<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all bugs related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Reports_model extends Super_Model {

    public $debug_methods_trail;
    public $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------
    /**
     * no action
     *
     * 
     */
    public function __construct() {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        // Call the Model constructor
        parent::__construct();
    }
    
    function getMyTasks(){
        $team_profile_id = $this->session->userdata("team_profile_id");
        
        $sql = "SELECT  t.tasks_id, t.tasks_text, t.tasks_status"
                ." FROM tasks AS t "
                ." WHERE t.tasks_assigned_to_id = $team_profile_id";
        
        $query = $this->db->query($sql);
        $results = $query->result_array();
        
        return $results;
    }
    
    function getFreelancers(){
        $sql = "SELECT  tp.team_profile_id, g.groups_name, tp.team_profile_full_name, count(t.tasks_id) as tasks_count, count(distinct t.tasks_project_id) as projects_count"
                ." FROM groups AS g, team_profile AS tp "
                . " LEFT OUTER JOIN tasks AS t ON tp.team_profile_id = t.tasks_assigned_to_id"
                ." WHERE tp.team_profile_groups_id = g.groups_id"
                . " GROUP BY tp.team_profile_id";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        
        return $results;
    }

    function getSalesTotal7() {
        $sql = "SELECT count(*) as total, l.leads_source "
                . " FROM leads AS l "
                . " WHERE DATEDIFF(now(), l.leads_created) <= 7 "
                . " GROUP BY l.leads_source";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        
        $row = array();
        $total = 0;
        foreach ($results as $item) {
            if ($item['leads_source'] == 1) {
                $row["branding_landing"] = $item["total"];
            } else if ($item['leads_source'] == 2) {
                $row["iso_developers_contact_form"] = $item["total"];
            } else if ($item['leads_source'] == 3) {
                $row["iso_developers_mobile_website_test"] = $item["total"];
            } else if ($item['leads_source'] == 4) {
                $row["added_by_admin"] = $item["total"];
            }
            $total += $item["total"];
        }
        $row["total"] = $total;
        
        $results_new[] = $row;
        return $results_new;
    }

    function getSalesTotal30() {
        $sql = "SELECT count(*) as total, l.leads_source "
                . " FROM leads AS l "
                . " WHERE DATEDIFF(now(), l.leads_created) <= 30 "
                . " GROUP BY l.leads_source";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        
        $row = array();
        $total = 0;
        foreach ($results as $item) {
            if ($item['leads_source'] == 1) {
                $row["branding_landing"] = $item["total"];
            } else if ($item['leads_source'] == 2) {
                $row["iso_developers_contact_form"] = $item["total"];
            } else if ($item['leads_source'] == 3) {
                $row["iso_developers_mobile_website_test"] = $item["total"];
            } else if ($item['leads_source'] == 4) {
                $row["added_by_admin"] = $item["total"];
            }
            $total += $item["total"];
        }
        $row["total"] = $total;
        
        $results_new[] = $row;
        return $results_new;
    }

    function getSalesTotalBySales() {
        $sql = "SELECT count(l.id) as total, t.team_profile_full_name"
                . " FROM leads AS l, team_profile AS t "
                . " WHERE l.sale_person_id=t.team_profile_id "
                . " GROUP BY l.sale_person_id ORDER BY total DESC";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        
        return $results;
    }
    
    function getClients(){
        $sql = "SELECT c.clients_id, c.clients_company_name, "
                . " c.clients_value, c.credit_amount_remaining, c.clients_hot, c.clients_date_created"
                . " FROM clients c "
                . " ORDER BY c.clients_date_created DESC, c.clients_id DESC";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        
        return $results;
    }
    
    function getSalesOpenLeads() {
        $this->debug_methods_trail[] = __function__;
        $sql = "SELECT l.id, l.leads_value, l.leads_name, l.leads_clients_id, "
                . " c.clients_company_name, t.team_profile_full_name, "
                . " l.leads_created "
                . " FROM leads AS l "
                . " LEFT OUTER JOIN clients AS c"
                . "   ON l.leads_clients_id = c.clients_id"
                . " LEFT OUTER JOIN team_profile AS t"
                . "   ON l.sale_person_id = t.team_profile_id"
                . " WHERE l.leads_hot = 0 AND l.leads_lost = 0";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        return $results;
    }

    function getSalesHotLeads() {
        $this->debug_methods_trail[] = __function__;
        $sql = "SELECT l.id, l.leads_value, l.leads_name, l.leads_clients_id, "
                . " c.clients_company_name, t.team_profile_full_name, "
                . " l.leads_created "
                . " FROM leads AS l"
                . " LEFT OUTER JOIN clients AS c"
                . "   ON l.leads_clients_id = c.clients_id"
                . " LEFT OUTER JOIN team_profile AS t"
                . "   ON l.sale_person_id = t.team_profile_id"
                . " WHERE l.leads_hot = 1";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        return $results;
    }

    function getSalesLost() {
        $this->debug_methods_trail[] = __function__;
        $sql = "SELECT l.id, l.leads_value, l.leads_name, l.leads_clients_id, "
                . " c.clients_company_name, t.team_profile_full_name, "
                . " l.leads_created "
                . " FROM leads AS l"
                . " LEFT OUTER JOIN clients AS c"
                . "   ON l.leads_clients_id = c.clients_id"
                . " LEFT OUTER JOIN team_profile AS t"
                . "   ON l.sale_person_id = t.team_profile_id"
                . " WHERE l.leads_lost = 1";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        return $results;
    }

    function getCovertedLeads() {
        $this->debug_methods_trail[] = __function__;
        $sql = "SELECT l.id, l.leads_value, l.leads_name, l.leads_clients_id, "
                . " c.clients_company_name, t.team_profile_full_name, "
                . " l.leads_created "
                . " FROM leads AS l"
                . " LEFT OUTER JOIN clients AS c"
                . "   ON l.leads_clients_id = c.clients_id"
                . " LEFT OUTER JOIN team_profile AS t"
                . "   ON l.sale_person_id = t.team_profile_id"
                . " WHERE c.from_lead = 1";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        return $results;
    }

    function salesHotLeads($offset = 0, $type = 'search', $list = '') {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';
        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //---is there any search data-----------------
        if (is_numeric($this->input->get('bugs_client_id'))) {
            //$bugs_client_id = $this->db->escape($this->input->get('bugs_client_id'));
            //$conditional_sql .= " AND bugs.bugs_client_id = $bugs_client_id";
        }

        //create the order by sql additional condition
        //these sorting keys are passed in the url and must be same as the ones used in the controller.
        $sorting_sql = '';
        /* $sort_order = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
          $sort_columns = array(
          'sortby_client' => 'bugs.bugs_client_id',
          'sortby_project' => 'bugs.bugs_project_id',
          'sortby_date' => 'bugs.bugs_date',
          'sortby_status' => 'bugs.bugs_status');
          $sort_by = (array_key_exists($this->uri->segment(6), $sort_columns)) ? $sort_columns[$this->uri->segment(6)] : 'bugs.bugs_id';
          $sorting_sql = "ORDER BY $sort_by $sort_order"; */

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search' || $type == 'results') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______

        $sql = "SELECT bugs.*, projects.*, clients.*, client_users.client_users_full_name as name, team_profile.*, team_profile.team_profile_full_name as clients_company_name
                                             FROM bugs
                                             LEFT OUTER JOIN projects
                                             ON bugs.bugs_project_id = projects.projects_id
                                             LEFT OUTER JOIN clients 
                                             ON bugs.bugs_client_id = clients.clients_id
                                             LEFT OUTER JOIN client_users 
                                             ON bugs.bugs_reported_by_id = client_users.client_users_id
                                             LEFT OUTER JOIN team_profile
                                             ON bugs.bugs_resolved_by_id = team_profile.team_profile_id
                                             WHERE 1 = 1
                                             AND bugs.bugs_member_id > 0
                                             $conditional_sql
                                             $sorting_sql
                                             $limiting";
        $query = $this->db->query($sql);

        //results (search or rows)
        //rows are used by pagination class & results are used by tbs block merge
        if ($type == 'search' || $type == 'results') {
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

}

/* End of file bugs_model.php */
/* Location: ./application/models/reports_model.php */
