<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Tickets_replies_model extends Super_Model
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

    // -- addReply ----------------------------------------------------------------------------------------------
    /**
     * add ticket to database
     *
     * 
     * @param	null
     * @return	mixed (insert id / false)
     */

    function addReply()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }

        //do we have an attachment
        $tickets_replies_has_attachment = ($this->input->post('tickets_file_name') != '') ? 'yes' : 'no';

        //is this client or customer
        $status = ($tickets_replies_by_user_type == 'team') ? 'answered' : 'client-replied';

        //CLIENT-PANEL: suppliment input
        if (is_numeric($this->client_id) || $this->uri->segment(1) == 'client') {
            $tickets_replies_by_user_type = "'client'";
            $tickets_replies_by_user_id = $this->data['vars']['my_id'];
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO tickets_replies (
                                          tickets_replies_ticket_id,
                                          tickets_replies_date,
                                          tickets_replies_message,
                                          tickets_replies_by_user_id,
                                          tickets_replies_by_user_type,
                                          tickets_replies_file_name,
                                          tickets_replies_file_folder,
                                          tickets_replies_file_size,
                                          tickets_replies_file_extension,
                                          tickets_replies_has_attachment
                                          )VALUES(
                                          $tickets_replies_ticket_id,
                                          NOW(),
                                          $tickets_replies_message,
                                          $tickets_replies_by_user_id,
                                          $tickets_replies_by_user_type,
                                          $tickets_file_name,
                                          $tickets_file_folder,
                                          $tickets_file_size,
                                          $tickets_file_extension,
                                          '$tickets_replies_has_attachment')");

        //other results
        $results = $this->db->insert_id(); //last item insert id

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return insert id or false
        if ($results > 0) {
            return $results;
        } else {
            return false;
        }

    }

    // -- deleteReply ----------------------------------------------------------------------------------------------
    /**
     * delete a single ticket reply 
     * @return	bool
     */

    function deleteReply($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [ticket reply id: $id]", '');
            return false;
        }

        //escape input
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM tickets_replies
                                          WHERE tickets_replies_id = $id");

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

    // -- getReplies ----------------------------------------------------------------------------------------------
    /**
     * get all ticket replies for a give ticket
     *
     * 
     * @param	string [id: id of parent ticket]
     * @return	array
     */

    function getReplies($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT tickets_replies.*, client_users.*, team_profile.*
                                          FROM tickets_replies
                                            LEFT OUTER JOIN client_users
                                              ON client_users.client_users_id = tickets_replies.tickets_replies_by_user_id
                                              AND tickets_replies.tickets_replies_by_user_type = 'client'
                                            LEFT OUTER JOIN team_profile
                                              ON team_profile.team_profile_id = tickets_replies.tickets_replies_by_user_id
                                              AND tickets_replies.tickets_replies_by_user_type = 'team'
                                          WHERE tickets_replies_ticket_id = $id
                                          ORDER BY tickets_replies.tickets_replies_id ASC");

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

}

/* End of file tickets_replies_model.php */
/* Location: ./application/models/tickets_replies_model.php */
