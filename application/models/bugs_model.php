<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all bugs related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Bugs_model extends Super_Model
{

    var $debug_methods_trail;
    var $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------
    /**
     * no action
     *
     * 
     */
    function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        // Call the Model constructor
        parent::__construct();
    }

    // -- searchBugs ----------------------------------------------------------------------------------------------
    /**
     * search bugs
     *
     * 
     * @param numeric   $offset  pagination
     * @param	string    $type 'search', 'count'
     * @return	array
     */
	
	function addBugsDev($results)
    {	//var_dump($results);
		foreach($results as $key => $value)
		{
			$i = 0;
			$query = 'SELECT * FROM team_profile WHERE team_profile_id = "'.$results[$key]['bugs_reported_by_id'].'" ';
			$result = mysql_query($query);
			while($row = mysql_fetch_array($result))
			{
				$results[$key][name]=$row['team_profile_full_name'];
				$results[$key][clients_company_name]=$row['team_profile_full_name'];
				$results[$key][client_users_avatar_filename]=$row['team_profile_avatar_filename'];
				//echo $results[$key][clients_company_name];
				//echo $results[$key][name];
				$i++;
			}
			
			
		}
		return $results;
	}
	
	function addBugDev($results)
    {	
		
			
			$query = 'SELECT * FROM team_profile WHERE team_profile_id = "'.$results['bugs_reported_by_id'].'" ';
			$result = mysql_query($query);
			$row = mysql_fetch_array($result);
				$results[name]=$row['team_profile_full_name'];
				$results[clients_company_name]=$row['team_profile_full_name'];
				$results[client_users_full_name]=$row['team_profile_full_name'];
				$results[client_users_avatar_filename]=$row['team_profile_avatar_filename'];
				$results[clients_id]=0;
				//echo $results[$key][clients_company_name];
				//echo $results[$key][name];

			
			//var_dump($results[clients_company_name]);
		
		return $results;
	}
	
	
    function searchBugs($offset = 0, $type = 'search', $list = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';
        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //---is there any search data-----------------
        if (in_array($this->input->get('bugs_status'), array(
            'new-bug',
            'in-progress',
            'resolved',
            'not-a-bug'))) {
            $bugs_status = $this->db->escape($this->input->get('bugs_status'));
            $conditional_sql .= " AND bugs.bugs_status = $bugs_status";
        }
        if (is_numeric($this->input->get('bugs_project_id'))) {
            $bugs_project_id = $this->db->escape($this->input->get('bugs_project_id'));
            $conditional_sql .= " AND bugs.bugs_project_id = $bugs_project_id";
        }
        if (is_numeric($this->input->get('bugs_client_id'))) {
            $bugs_client_id = $this->db->escape($this->input->get('bugs_client_id'));
            $conditional_sql .= " AND bugs.bugs_client_id = $bugs_client_id";
        }
		if ($list != 'all') {
            $conditional_sql .= " AND bugs.bugs_status != 'resolved' AND bugs.bugs_status != 'not-a-bug'";
        }
        //create the order by sql additional condition
        //these sorting keys are passed in the url and must be same as the ones used in the controller.
        $sort_order = ($this->uri->segment(5) == 'asc') ? 'asc' : 'desc';
        $sort_columns = array(
            'sortby_client' => 'bugs.bugs_client_id',
            'sortby_project' => 'bugs.bugs_project_id',
            'sortby_date' => 'bugs.bugs_date',
            'sortby_status' => 'bugs.bugs_status');
        $sort_by = (array_key_exists(''.$this->uri->segment(6), $sort_columns)) ? $sort_columns[$this->uri->segment(6)] : 'bugs.bugs_id';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search' || $type == 'results') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //CLIENT-PANEL: limit to this clients data
        if (is_numeric($this->client_id) || $this->uri->segment(1) == 'client') {
            $client_id = $this->client_id;
            $conditional_sql .= " AND bugs.bugs_client_id = '$client_id'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        
        $q = ("SELECT bugs.*, projects.*, clients.*, client_users.client_users_full_name as name, team_profile.*, team_profile.team_profile_full_name as clients_company_name
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
                                             $limiting");
		$query = $this->db->query($q);
											 
        //results (search or rows)
        //rows are used by pagination class & results are used by tbs block merge
        if ($type == 'search' || $type == 'results') {
            $results = $query->result_array();
            $results=$this->addBugsDev($results);
            
        } else {
            $results = $query->num_rows();
            
        }
        
        $query = $this->db->query("SELECT bugs.*, projects.*, clients.*, client_users.client_users_full_name as name, team_profile.*
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
                                             AND bugs.bugs_client_id > 0
                                             $conditional_sql
                                             $sorting_sql
                                             $limiting");
											 
        //results (search or rows)
        //rows are used by pagination class & results are used by tbs block merge
        if ($type == 'search' || $type == 'results') {
            $results2 = $query->result_array();
            $results=array_merge($results,$results2);
        } else {
            $results2 = $query->num_rows();
            $results=$results+$results2;
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
    
    function getBugsByProject($id)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        //$conditional_sql = '';
        //$limiting = '';
        //system page limit or set default 25
        //$limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        
        //create the order by sql additional condition
        //these sorting keys are passed in the url and must be same as the ones used in the controller.
        $sort_order = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_columns = array(
            'sortby_client' => 'bugs.bugs_client_id',
            'sortby_project' => 'bugs.bugs_project_id',
            'sortby_date' => 'bugs.bugs_date',
            'sortby_status' => 'bugs.bugs_status');
        $sort_by = (array_key_exists(''.$this->uri->segment(6), $sort_columns)) ? $sort_columns[$this->uri->segment(6)] : 'bugs.bugs_id';
        $sorting_sql = "ORDER BY $sort_by $sort_order";


        //CLIENT-PANEL: limit to this clients data
        if (is_numeric($this->client_id) && $this->uri->segment(1) == 'client') {
            $client_id = $this->client_id;
            $conditional_sql .= " AND bugs.bugs_client_id = '$client_id'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        
        $q = ("SELECT bugs.*, projects.*, clients.*, client_users.client_users_full_name as name, team_profile.*, team_profile.team_profile_full_name as clients_company_name
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
                                             AND bugs.bugs_project_id = $id
                                             AND bugs.bugs_status != 'resolved' AND bugs.bugs_status != 'not-a-bug'
                                             $conditional_sql
                                             $sorting_sql
                                             $limiting");
                                             
		$query = $this->db->query($q);
											 
        
            $results = $query->result_array();
            $results=$this->addBugsDev($results);
        
        
        $query = $this->db->query("SELECT bugs.*, projects.*, clients.*, client_users.client_users_full_name as name, team_profile.*
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
                                             AND bugs.bugs_client_id > 0
                                             AND bugs.bugs_project_id = $id
                                             AND bugs.bugs_status != 'resolved' AND bugs.bugs_status != 'not-a-bug'
                                             $conditional_sql
                                             $sorting_sql
                                             $limiting");
											 
        //results (search or rows)
        //rows are used by pagination class & results are used by tbs block merge
       
            $results2 = $query->result_array();
            $results=array_merge($results,$results2);
        
        
        

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------
		
        //return results
        
        return $results;

    }

    // -- allBugsCounts ----------------------------------------------------------------------------------------------
    /**
     * count various bugs based on status
     *
     * 
     * @param numeric   $client_id optional; if provided, count will be limited to that clients
     * @return	array
     */

    function allBugsCounts($client_id = '')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //is this for a client
        if (is_numeric($client_id)) {
            $conditional_sql .= " AND bugs_client_id = '$client_id'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT (SELECT COUNT(bugs_id)
                                                  FROM bugs
                                                  WHERE bugs_status = 'new-bug'
                                                  $conditional_sql) AS new,
                                          (SELECT COUNT(bugs_id)
                                                  FROM bugs
                                                  WHERE bugs_status = 'resolved'
                                                  $conditional_sql) AS resolved,
                                          (SELECT COUNT(bugs_id)
                                                  FROM bugs
                                                  WHERE bugs_status = 'in-progress'
                                                  $conditional_sql) AS in_progress,
                                          (SELECT COUNT(bugs_id)
                                                  FROM bugs
                                                  WHERE bugs_status = 'not-a-bug'
                                                  $conditional_sql) AS not_a_bug,
                                          (SELECT COUNT(bugs_id)
                                                  FROM bugs
                                                  WHERE bugs_status NOT IN('resolved', 'not-a-bug')
                                                  $conditional_sql) all_open,
                                          (SELECT COUNT(bugs_id)
                                                  FROM bugs
                                                  WHERE 1 = 1
                                                  $conditional_sql) AS all_bugs
                                          FROM bugs 
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

    // -- getBug ----------------------------------------------------------------------------------------------
    /**
     * load a bug based on bug id
     *
     * 
     * @param numeric $bug_id bug id
     * @return	array
     */

    function getBug($bug_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($bug_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [bug id=$bug_id]", '');
            return false;
        }

        //escape params items
        $bug_id = $this->db->escape($bug_id);

        //CLIENT-PANEL: limit to this clients data
        if (is_numeric($this->client_id) || $this->uri->segment(1) == 'client') {
            $client_id = $this->client_id;
            $conditional_sql .= " AND bugs.bugs_client_id = '$client_id'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT bugs.*, projects.*, clients.*, client_users.*, team_profile.*
                                          FROM bugs
                                          LEFT OUTER JOIN projects
                                          ON projects.projects_id = bugs.bugs_project_id
                                          LEFT OUTER JOIN clients
                                          ON clients.clients_id = bugs.bugs_client_id
                                          LEFT OUTER JOIN client_users
                                          ON client_users.client_users_id = bugs.bugs_reported_by_id    
                                          LEFT OUTER JOIN team_profile
                                          ON bugs.bugs_resolved_by_id = team_profile.team_profile_id
                                          WHERE bugs.bugs_id = $bug_id
                                          $conditional_sql");

        $results = $query->row_array();
		if($results['bugs_client_id']==0)
		$results=$this->addBugDev($results);
		//var_dump($results['clients_company_name']);
        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    // -- updateBug ----------------------------------------------------------------------------------------------
    /**
     * update a bugs status and comment
     *
     * 
     * @return	bool
     */

    function updateBug()
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

        //get my id
        $my_id = $this->data['vars']['my_id'];
		
		$this->db->select('bugs_status,bugs_client_id,bugs_member_id');
		$this->db->from('bugs');
		$this->db->where('bugs_id', str_replace("'", "", $bugs_id));
		$status = $this->db->get()->row();
		
		if($status->bugs_client_id!=0)
		{
			$this->db->select('client_users_email');
			$this->db->from('client_users');
			$this->db->where('client_users_clients_id', $status->bugs_client_id);
			$email = $this->db->get()->row();
			$email=$email->client_users_email;
		}
		else
		{
			$this->db->select('team_profile_email');
			$this->db->from('team_profile');
			$this->db->where('team_profile_id', $status->bugs_member_id);
			$email = $this->db->get()->row();
			$email=$email->team_profile_email;
		}
		//die(var_dump($status));
		
        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE bugs
                                          SET 
                                          bugs_status = $bugs_status,
                                          
                                          bugs_resolved_by_id = '$my_id'
                                          WHERE bugs_id = $bugs_id");

        $results = $this->db->affected_rows(); //affected rows
		
		$now=date("Y-m-d H:i:s", NOW());
		$myname=$this->data['vars']['my_name'];
		$myavatar=$this->data['vars']['my_avatar'];
        $this->db->select('bugs_title, bugs_project_id');
		$this->db->from('bugs');
		$this->db->where('bugs_id', str_replace("'", "", $bugs_id));
		$name = $this->db->get()->row();
        
        //mod by Tomasz
        /*$text = addslashes($this->data['vars']['my_name'].' changed status of a bug <a href="/admin/bugs/view/'.str_replace("'", "", $bugs_id).'">"'.$name->bugs_title.'"</a> from "'.str_replace("-", " ", $status->bugs_status).'" to "'.str_replace("'", "", str_replace("-", " ", $bugs_status)).'"');*/
        $bugs_id = str_replace("'", "", $bugs_id);
        $text_template = "%s changed status of a bug <a href='%s'>%s</a> from %s to %s";
        $text = mysql_real_escape_string(sprintf($text_template, 
            $this->data['vars']['my_name'], 
            site_url("admin/bugs/view/$bugs_id"),
            $name->bugs_title,
            "'" . str_replace("-", " ", $status->bugs_status) . "'",
            str_replace("-", " ", $bugs_status)));
        //end by Tomasz
		
        $headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		$headers .= 'From: PMSSystem <isodevelopers@isodevelopers.com>'.'\r\n';
		mail($email, 'PMS - Bug Report Notice', $text,$headers);
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
                                          $name->bugs_project_id
                                          )");
		$bugs_status=str_replace("'", "", str_replace("-", " ", $bugs_status));
		$text='<span style="color: #999999">Changed status to '.$bugs_status.'.</span>';
		$query = $this->db->query("INSERT INTO bugs_comments (
                                          messages_project_id,
                                          messages_text,
                                          messages_by,
                                          messages_by_id,
                                          messages_date,
                                          isclient                                         
                                          )VALUES(
                                          $bugs_id,
                                          '$text',
                                          'team',
                                          '$my_id',
                                          '$now',
                                          0)");
		
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

    // -- addBug ----------------------------------------------------------------------------------------------
    /**
     * add new bug
     *
     * 
     * @return	bool
     */

    function addBug()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }

        //CLIENT-PANEL: limit to this clients data
        if (is_numeric($this->client_id) || $this->uri->segment(1) == 'client') {
            $client_id = $this->client_id;
            $my_id = $this->data['vars']['my_id'];
			
        }
		 //ADMIN-PANEL: 
        if (is_numeric($this->member_id) || $this->uri->segment(1) == 'admin') {
            $member_id = $this->member_id;
            $my_id = $member_id;
			
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO bugs (
                                          bugs_project_id,
                                          bugs_client_id,
                                          bugs_member_id,
                                          bugs_title,
                                          bugs_description,
                                          bugs_comment,
                                          bugs_reported_by_id,
                                          bugs_date
                                          )VALUES(
                                          $bugs_project_id,
                                          '$client_id',
                                          '$member_id',
                                          $bugs_title,
                                          $bugs_description,
                                          '',
                                          '$my_id',
                                          NOW())");

        $results = $this->db->insert_id(); //last item insert id
		
		$now=date("Y-m-d H:i:s", NOW());
		$myname=$this->data['vars']['my_name'];
		$myavatar=$this->data['vars']['my_avatar'];
        $this->db->select('bugs_title');
		$this->db->from('bugs');
		$this->db->where('bugs_id', str_replace("'", "", $messages_project_id));
		$name = $this->db->get()->row();
        
        //mod by Tomasz
        /*$text=addslashes($this->data['vars']['my_name'].' added new bug <a href="/admin/bugs/view/'.str_replace("'", "", $this->db->insert_id()).'">"'.str_replace("'", "", $bugs_title).'""</a>');*/
        $bugs_title = str_replace("'", "", $bugs_title);
        $text_template = "%s added new bug <a href='%s'>%s</a>";
        $text = mysql_real_escape_string(sprintf($text_template, 
            $this->data['vars']['my_name'], 
            site_url("admin/bugs/view/".$results),
            $bugs_title));
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
                                          $bugs_project_id)");
		
		
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

    // -- deleteBug ----------------------------------------------------------------------------------------------
    /**
     * delete a single bug
     *
     * 
     * @param numeric $bug_id bugs id
     * @return	bool
     */

    function deleteBug($bug_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($bug_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [bug id=$bug_id]", '');
            return false;
        }

        //escape params items
        $bug_id = $this->db->escape($bug_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM bugs
                                          WHERE bugs_id = $bug_id");

        $results = $this->db->affected_rows();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);

        //---return
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
     * 
     * @param	string $projects_list a mysql array/list formatted projects list [e.g. 1,2,3,4]
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
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting file messages, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM bugs
                                          WHERE bugs_project_id IN($projects_list)");
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

    // -- validateClientOwner ----------------------------------------------------------------------------------------------
    /**
     * confirm if a given client owns this requested item
     *
     * 
     * @param numeric $resource_id
     * @param   numeric $client_id
     * @return	bool
     */

    function validateClientOwner($resource_id = '', $client_id)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($resource_id) || ! is_numeric($client_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Input Data", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM bugs 
                                          WHERE bugs_id = $resource_id
                                          AND bugs_client_id = $client_id");

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
}

/* End of file bugs_model.php */
/* Location: ./application/models/bugs_model.php */
