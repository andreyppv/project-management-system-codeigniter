<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Messages_model extends Super_Model
{

    public $debug_methods_trail;
    public  $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------
    function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        // Call the Model constructor
        parent::__construct();
		
		$config = Array(
		    'protocol' => 'smtp',
		    'smtp_host' => 'ssl://smtp.googlemail.com',
		    'smtp_port' => 465,
		    'smtp_user' => 'pms@isodeveloper.com',
		    'smtp_pass' => '9iLyS1Q96R',
		    'mailtype'  => 'html', 
		    'charset'   => 'iso-8859-1',
		    'protocol' => 'sendmail'
		);
		$this->load->library('email', $config);
		$this->email->set_mailtype('html');		
	}
	
	// -- addComment ----------------------------------------------------------------------------------------------
    /**
     * add new comment to database
     *
     * @param	void
     * @return	mixed [record insert id / bool(false)]
     */

    public function addComment()
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
        /*$this->db->trans_start();
        $query = $this->db->query("SELECT projects_clients_id, projects_title FROM projects WHERE projects_id = ".$messages_project_id."");
        $result = $query->result_array()[0];
        $title = $result['projects_title'];
        $clientid = $result['projects_clients_id'];
        $query = $this->db->query("SELECT client_users_email FROM client_users WHERE client_users_clients_id = ".$clientid."");
        $result = $query->result_array()[0];
        $email = $result['client_users_email'];
				
        mail($email, 'PMS - Client Message Notice', 'You have received the following message regarding project "'.$title.'" please login at http://pms.isodeveloper.com/client, to reply. Message: ' . $messages_text);
        */
		 //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO bugs_comments (
                                          messages_project_id,
                                          messages_text,
                                          messages_by,
                                          messages_by_id,
                                          messages_date,
                                          isclient                                         
                                          )VALUES(
                                          $messages_project_id,
                                          $messages_text,
                                          $messages_by,
                                          $messages_by_id,
                                          NOW(),
                                          0)");
        $results = $this->db->insert_id(); //(last insert item)
        $now=date("Y-m-d H:i:s", NOW());
		$myname=$this->data['vars']['my_name'];
		$myavatar=$this->data['vars']['my_avatar'];
        $this->db->select('bugs_title');
		$this->db->from('bugs');
		$this->db->where('bugs_id', str_replace("'", "", $messages_project_id));
		$name = $this->db->get()->row();
        
        //mod by Tomasz
        /*$text=addslashes($this->data['vars']['my_name'].' added new reply to bug <a href="'.site_url().'/admin/messages/'.str_replace("'", "", $messages_project_id).'/view">"'.$name->bugs_title.'</a>"');*/
        $messages_project_id = str_replace("'", "", $messages_project_id);
        $myname = str_replace("'", "", $myname);
        $text_template = "%s added new reply to bug <a href='%s'>%s</a>";
        $text = mysql_real_escape_string(sprintf($text_template, 
            $this->data['vars']['my_name'], 
            site_url("admin/messages/$messages_project_id/view"),
            $name->bugs_title                     
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
                                          $messages_project_id
                                          )");
		
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
        if ($results > 0) {
            return $results;
        } else {
            return false;
        }
    }
	
	// -- addMessageClient ----------------------------------------------------------------------------------------------
    /**
     * add new message to database - client-side no email
     *
     * @param	void
     * @return	mixed [record insert id / bool(false)]
     */

    public function addMessageClient($data = array())
    {
        //return results
        return $this->addMessage($data, 1);
    }

    // -- addMessage ----------------------------------------------------------------------------------------------
    /**
     * add new message to database
     *
     * @param	void
     * @return	mixed [record insert id / bool(false)]
     */

    public function addMessage($data = array(), $isclient = 0)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape all post item
        $fields = array(
            'messages_project_id' => 'numeric',
            'messages_by_id'      => 'numeric',
            'messages_by'         => 'string',
            'messages_text'       => 'string'
        );
        foreach($fields as $key=>$type)
        {
            if(isset($data[$key])) {
                $$key = $data[$key];
            } else {
                $$key = $this->input->post($key);
            }
            if($type === 'numeric')
            {
                $$key = intval($$key);
            }
        }
		
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();
	

	    //_____SQL QUERY_______
        $this->db->set('messages_date', 'NOW()', FALSE);
        $this->db->insert('messages', array(
                'messages_project_id' => $messages_project_id,
                'messages_text'       => $messages_text,
                'messages_by'         => $messages_by,
                'messages_by_id'      => $messages_by_id,
                'isclient'            => $isclient
            ));
        $results = $this->db->insert_id(); //(last insert item)
		
        $myname = $this->data['vars']['my_name'];
        $myavatar = $this->data['vars']['my_avatar'];

        if(!$myname) $myname = '-';
        if(!$myavatar) $myavatar = '-';
		
        $this->db->select('projects_title');
		$this->db->from('projects');
		$this->db->where('projects_id', $messages_project_id);
		$project = $this->db->get()->row();
        
        //mod by Tomasz
        $text_template = "%s added new message in %s's <a href='%s'>client chat</a>";
        $text = sprintf($text_template, 
            $myname, 
            $project->projects_title,
            site_url("admin/messages/$messages_project_id/view")         
        );
        //end by Tomasz
        
        $this->db->set('date', 'NOW()', FALSE);
        $this->db->insert('feed', array(
                'feed_by'        => $myname,
                'feed_by_avatar' => $myavatar,
                'text'           => $text,
                'type'           => 'project',
                'type_id'        => $messages_project_id
            ));
		
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
        return (bool)$results;
    }


// -- listComments ----------------------------------------------------------------------------------------------
    /**
     * list bug comments, paginated
     * @return	array
     */

    function listComments($offset = 0, $type = 'search', $project_id = '')
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

        

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT bugs_comments.*, client_users.*, team_profile.*
                                             FROM bugs_comments
                                             LEFT OUTER JOIN client_users
                                             ON client_users.client_users_id = bugs_comments.messages_by_id
                                             AND bugs_comments.messages_by = 'client'
                                             LEFT OUTER JOIN team_profile
                                             ON team_profile.team_profile_id = bugs_comments.messages_by_id
                                             AND bugs_comments.messages_by = 'team'
                                             WHERE messages_project_id = $project_id
                                             ORDER BY bugs_comments.messages_id DESC
                                             ");
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
        
        foreach ($results as $key=>$value)
		{
			if ($value['messages_by']=='team')
			{
			$results[$key]['name']=$value['team_profile_full_name'];
			$results[$key]['avatar']=$value['team_profile_avatar_filename'];
			}
			if ($value['messages_by']=='client')
			{
			$results[$key]['name']=$value['client_users_full_name'];
			$results[$key]['avatar']=$value['client_users_avatar_filename'];
			}	
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

    // -- listMessages ----------------------------------------------------------------------------------------------
    /**
     * list project messages, paginated
     * @return	array
     */

    function listMessages($offset = 0, $type = 'search', $project_id = '')
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
        $limit = 15;//(is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search') {
            $limiting = "LIMIT $offset, $limit";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT messages.*, client_users.*, team_profile.*
                                             FROM messages
                                             LEFT OUTER JOIN client_users
                                             ON client_users.client_users_id = messages.messages_by_id
                                             AND messages.messages_by = 'client'
                                             LEFT OUTER JOIN team_profile
                                             ON team_profile.team_profile_id = messages.messages_by_id
                                             AND messages.messages_by = 'team'
                                             WHERE messages_project_id = $project_id
                                             ORDER BY messages.messages_id DESC
                                             $limiting
                                             ");
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

    // -- getMessage ----------------------------------------------------------------------------------------------
    /**
     * return a single message record based on its ID
     *
     * @param numeric $item ID]
     * @return	array
     */

    function getMessage($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [file id=$id]", '');
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
                                          FROM messages
                                          WHERE messages_id = $id");

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

    // -- editMessage ----------------------------------------------------------------------------------------------
    /**
     * edit a project message
     * 
     * @param	void
     * @return	numeric [affected rows]
     */

    function editMessage()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if task id value exists in the post data
        if (! is_numeric($this->input->post('messages_id')) || $this->input->post('messages_text') == '') {
            $this->__debugging(__line__, __function__, 0, "Editing Message Failed: Invalid Data messages_id or messages_text", '');
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
        $query = $this->db->query("UPDATE messages
                                          SET 
                                          messages_text = $messages_text
                                          WHERE messages_id = $messages_id");

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

	function editDeleteMessage()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if task id value exists in the post data
        if (! is_numeric($this->input->post('messages_id'))) {
            $this->__debugging(__line__, __function__, 0, "Editing Message Failed: Invalid Data messages_id or messages_text", '');
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
		
		$deleted_date=date('m-d-Y');
		
        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE messages
                                          SET 
                                          messages_deleted = 1,
                                          messages_deleted_by = $by,
                                          messages_deleted_date = '$deleted_date'
                                          WHERE messages_id = $messages_id");

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

    // -- editReply ----------------------------------------------------------------------------------------------
    /**
     * edit a project message reply
     *
     * 
     * @param	void
     * @return	numeric [affected rows]
     */

    function editReply()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if task id value exists in the post data
        if (! is_numeric($this->input->post('messages_replies_id')) || $this->input->post('messages_replies_text') == '') {
            $this->__debugging(__line__, __function__, 0, "Editing Message Failed: Invalid Data messages_replies_id or messages_replies_text", '');
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
        $query = $this->db->query("UPDATE messages_replies
                                          SET 
                                          messages_replies_text = $messages_replies_text
                                          WHERE messages_replies_id = $messages_replies_id");

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

    // -- deleteMessage ----------------------------------------------------------------------------------------------
    /**
     * delete a message based on a 'delete_by' id
     *
     * 
     * @param numeric   [id: reference id of item(s)]
     * @param   string    [delete_by: message-id, project-id]
     * @return	bool
     */

    function deleteMessage($id = '', $delete_by = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting message(s) failed (id: $id is invalid)]");
            return false;
        }

        //check if delete_by is valid
        $valid_delete_by = array('message-id', 'project-id');

        if (! in_array($delete_by, $valid_delete_by)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [delete_by=$delete_by]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting message(s) failed (delete_by: $delete_by is invalid)]");
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //conditional sql
        switch ($delete_by) {

            case 'message-id':
                $conditional_sql = "AND messages_id = $id";
                break;

            case 'project-id':
                $conditional_sql = "AND messages_project_id = $id";
                break;

            default:
                $conditional_sql = "AND messages_id = '0'"; //safety precaution else we wipe out whole table
                break;

        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM messages
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
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting messages, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM messages
                                          WHERE messages_project_id IN($projects_list)");
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

/* End of file messages_model.php */
/* Location: ./application/models/messages_model.php */
