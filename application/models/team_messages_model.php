<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Team_messages_model extends Super_Model
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
		$this->email->set_mailtype("html");	
    }

    // -- addMessage ----------------------------------------------------------------------------------------------
    /**
     * add new message to database
     *
     * 
     * @param	void
     * @return	mixed [record insert id / bool(false)]
     */

    public function addMessage($data = array())
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape all post item
        $fields = array(
            'messages_project_id' => 'numeric',
            'messages_by_id'      => 'numeric',
            'messages_text'       => 'string',
            'taskid'              => 'numeric'
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
        $this->db->insert('team_messages', array(
                'messages_project_id' => $messages_project_id,
                'messages_text'       => $messages_text,
                'messages_by_id'      => $messages_by_id,
                'taskid'              => $taskid
            ));

        $results = $this->db->insert_id(); //(last insert item)
        
		$myname   = $this->data['vars']['my_name'];
		$myavatar = $this->data['vars']['my_avatar'];

        if(!$myname) $myname   = $this->input->post('myname');
        if(!$myavatar) $myname = $this->input->post('myavatar');

        if(!$myname) $myname     = '-';
        if(!$myavatar) $myavatar = '-';
		
        $this->db->select('projects_title');
		$this->db->from('projects');
		$this->db->where('projects_id', $messages_project_id);
		$project = $this->db->get()->row();
        
        //mod by Tomasz
        $text_template = "%s added new message in %s's <a href='%s'>team chat</a>";
        $text = sprintf($text_template, 
            $myname, 
            $project->projects_title,
            site_url("admin/teammessages/$messages_project_id/view")
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
        //return new  client_id or false
        if ($results > 0) {
            return $results;
        } else {
            return false;
        }
    }

    // -- listMessages ----------------------------------------------------------------------------------------------
    /**
     * list team messages, paginated
     * @return	array
     */

    public function listMessages($offset = 0, $type = 'search', $project_id = '')
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
        $limit = (is_numeric($this->data['settings_general']['messages_limit'])) ? $this->data['settings_general']['messages_limit'] : 25;

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT team_messages.*, team_profile.*
                                             FROM team_messages
                                             LEFT OUTER JOIN team_profile
                                             ON team_profile.team_profile_id = team_messages.messages_by_id
                                             WHERE messages_project_id = $project_id
                                             ORDER BY team_messages.messages_id DESC
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
        return $results;

    }

    // -- getMessage ----------------------------------------------------------------------------------------------
    /**
     * return a single message record based on its ID
     *
     * 
     * @param numeric $item ID]
     * @return	array
     */

    public function getMessage($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [message id=$id]", '');
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
                                          FROM team_messages
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
     * 
     * @param	void
     * @return	numeric [affected rows]
     */

    public function editMessage()
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
        $query = $this->db->query("UPDATE team_messages
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

    // -- editReply ----------------------------------------------------------------------------------------------
    /**
     * edit a team message reply
     *
     * 
     * @param	void
     * @return	numeric [affected rows]
     */

    public function editReply()
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
        $query = $this->db->query("UPDATE team_messages_replies
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

    public function deleteMessage($id = '', $delete_by = '')
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
        $query = $this->db->query("DELETE FROM team_messages
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
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting team messages, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM team_messages
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

/* End of file team_messages_model.php */
/* Location: ./application/models/team_messages_model.php */