<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Team_message_replies_model extends Super_Model
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

    public function addMessage()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape all post item
        foreach ($_POST as $key => $value) {
            if( in_array($key, array('messages_replies_message_id', 'messages_replies_project_id', 'messages_replies_by_id')) )
            {
                $$key = intval($value);
                continue;
            }
            //$$key = $this->db->escape($this->input->post($key));
            $$key = $this->input->post($key);
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();
		
        /*
        * depricated, using control teammessages->__emailer
		$query = $this->db->query("SELECT projects_clients_id, projects_title FROM projects WHERE projects_id = ".$messages_replies_project_id."");
        $result = $query->result_array()[0];
        $title = $result['projects_title'];
        $clientid = $result['projects_clients_id'];
		
		$query = $this->db->query("SELECT team_profile_full_name FROM team_profile WHERE team_profile_id = ".$messages_replies_by_id."");
        $result = $query->result_array()[0];
        $usrname = $result['team_profile_full_name'];		
				
        $this->db->select('project_members.*, team_profile.*');
		$this->db->from('project_members');
		$this->db->join('team_profile', 'team_profile.team_profile_id = project_members.project_members_team_id');		
		$this->db->where('project_members.project_members_project_id',str_replace("'", "", $messages_replies_project_id));
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		$headers .= 'From: PMSSystem <pms@isodeveloper.com>'.'\r\n';
		$projectID=str_replace("'", "", $messages_replies_project_id);
		foreach($this->db->get()->result() as $row)
		{
            // Added by Tomasz
            $email_vars['client_users_full_name'] = $row->team_profile_full_name;
            $email_vars['projects_url']   = site_url("admin/project/".$projectID."/view");
            $email_vars['projects_title'] = $title;
            $email_vars['messages_text']  = $messages_replies_text;
            $email_vars['usrname']        = $usrname;
            $email_vars['reply_url']      = site_url("admin/messages/".$projectID."/view");
            $email_vars['company_email_signature'] = $this->data['settings_company']['company_email_signature'];

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('admin_communication');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //parse email
            $email_message = parse_email_template($template['message'], $email_vars);

            //send email
            email_default_settings(); //defaults (from emailer helper)
            $this->email->to($row->team_profile_email);
            $this->email->subject($title . $template['subject']);
            $this->email->message($email_message);
            $this->email->send();
            //end by Tomasz
		}
        */

        //_____SQL QUERY_______
        $this->db->set('messages_replies_date', 'NOW()', FALSE);
        $this->db->insert('team_messages_replies', array(
                'messages_replies_message_id' => $messages_replies_message_id,
                'messages_replies_project_id' => $messages_replies_project_id,
                'messages_replies_text'       => $messages_replies_text,
                'messages_replies_by_id'      => $messages_replies_by_id
            ));

        $results = $this->db->insert_id(); //(last insert item)
        
        if (!$_POST['myname'])
		{
			$myname=$this->data['vars']['my_name'];
			$myavatar=$this->data['vars']['my_avatar'];
		}
		
        $this->db->select('projects_title');
		$this->db->from('projects');
		$this->db->where('projects_id', $messages_replies_project_id);
		$projects_title = $this->db->get()->row_array()['projects_title'];
        
        //mod by Tomasz
        $myname = str_replace("'", "", $myname);
        $messages_replies_project_id = str_replace("'", "", $messages_replies_project_id);
        $text_template = "%s added new message in %s's <a href='%s'>team chat</a>";
        $text = mysql_real_escape_string(sprintf($text_template, 
            $myname, 
            $projects_title,
            site_url("admin/teammessages/$messages_replies_project_id/view")
        ));
        //end by Tomasz
        
        $this->db->set('date', 'NOW()', FALSE);
        $this->db->insert('feed', array(
                'feed_by'        => $messages_replies_message_id,
                'feed_by_avatar' => $myavatar,
                'text'           => $text,
                'type'           => 'project',
                'type_id'        => $messages_replies_project_id
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

    // -- getReplies ----------------------------------------------------------------------------------------------
    /**
     * get all replies for a given message (by message id)
     *
     * 
     * @param numeric $id: id of main message]
     * @return	array
     */

    public function getReplies($id = '')
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
        $query = $this->db->query("SELECT team_messages_replies.*, team_profile.*
                                             FROM team_messages_replies
                                             LEFT OUTER JOIN team_profile
                                             ON team_profile.team_profile_id = team_messages_replies.messages_replies_by_id
                                             WHERE team_messages_replies.messages_replies_message_id = $id
                                             ORDER BY team_messages_replies.messages_replies_id ASC");

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
     * edit a project team message reply
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
        if (! is_numeric($this->input->post('messages_replies_id')) || $this->input->post('messages_replies_text') == '') {
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

    // -- getReply ----------------------------------------------------------------------------------------------
    /**
     * return a single message reply record based on its ID
     *
     * 
     * @param numeric $item ID]
     * @return	array
     */

    public function getReply($id = '')
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
                                          FROM team_messages_replies
                                          WHERE messages_replies_id = $id");

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

    // -- deleteReply ----------------------------------------------------------------------------------------------
    /**
     * delete a message reply based on a 'delete_by' id
     *
     * 
     * @param numeric   [id: reference id of item(s)]
     * @param   string    [delete_by: reply-id, message-id]
     * @return	bool
     */

    public function deleteReply($id = '', $delete_by = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting message reply(s) failed (id: $id is invalid)]");
            return false;
        }

        //check if delete_by is valid
        $valid_delete_by = array('reply-id', 'message-id');

        if (! in_array($delete_by, $valid_delete_by)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [delete_by=$delete_by]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting message reply(s) failed (delete_by: $delete_by is invalid)]");
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //conditional sql
        switch ($delete_by) {

            case 'reply-id':
                $conditional_sql = "AND messages_replies_id = $id";
                break;

            case 'message-id':
                $conditional_sql = "AND messages_replies_message_id = $id";
                break;

            default:
                $conditional_sql = "AND messages_replies_id = '0'"; //safety precaution else we wipe out whole table
                break;

        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM team_messages_replies
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
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting team message replies, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM team_messages_replies
                                          WHERE messages_replies_project_id IN($projects_list)");
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

/* End of file team_message_replies_model.php */
/* Location: ./application/models/team_message_replies_model.php */