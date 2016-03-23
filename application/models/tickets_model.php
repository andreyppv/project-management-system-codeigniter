<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Tickets_model extends Super_Model
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

    // -- searchTickets ----------------------------------------------------------------------------------------------
    /**
     * search tickets table
     * @return	array
     */

    function searchTickets($offset = 0, $type = 'search', $own=0)
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
        if (is_numeric($this->input->get('tickets_id'))) {
            $tickets_id = $this->db->escape($this->input->get('tickets_id'));
            $conditional_sql .= " AND tickets_id = $tickets_id";
        }
        if (is_numeric($this->input->get('tickets_department_id'))) {
            $tickets_department_id = $this->db->escape($this->input->get('tickets_department_id'));
            $conditional_sql .= " AND tickets_department_id = $tickets_department_id";
        }
        if (is_numeric($this->input->get('tickets_by_user_id'))) {
            $tickets_by_user_id = $this->db->escape($this->input->get('tickets_by_user_id'));
            $conditional_sql .= " AND tickets_by_user_id = $tickets_by_user_id";
            $conditional_sql .= " AND tickets_by_user_type = 'client'";
        }
        if (is_numeric($this->input->get('tickets_by_team_member_id'))) {
            $tickets_by_team_member_id = $this->db->escape($this->input->get('tickets_by_team_member_id'));
            $conditional_sql .= " AND tickets_by_user_id = $tickets_by_team_member_id";
            $conditional_sql .= " AND tickets_by_user_type = 'team'";
        }
		if ($own>0) {
            $conditional_sql .= " AND tickets_assigned_to_id = $own";
        }

        //conditional sql ticket status - is it in posted search or maybe uri
        $status = ($this->input->get('tickets_status') != '') ? $this->input->get('tickets_status') : $this->uri->segment(4);
        switch ($status) {

            case 'open':
                $conditional_sql .= " AND tickets_status NOT IN('closed')";
                break;

            case 'new':
                $conditional_sql .= " AND tickets_status = 'new'";
                break;

            case 'closed':
                $conditional_sql .= " AND tickets_status = 'closed'";
                break;

            case 'answered':
                $conditional_sql .= " AND tickets_status = 'answered'";
                break;

            case 'client-replied':
                $conditional_sql .= " AND tickets_status = 'client-replied'";
                break;
        }

        //create the order by sql additional condition
        //these sorting keys are passed in the url and must be same as the ones used in the controller.
        $sort_order = ($this->uri->segment(6) == 'asc') ? 'asc' : 'desc';
        $sort_columns = array(
            'sortby_ticketid' => 'tickets.tickets_id',
            'sortby_datecreated' => 'tickets.tickets_date',
            'sortby_dateactive' => 'tickets.tickets_last_active_date',
            'sortby_status' => 'tickets_status');
        $sort_by = (array_key_exists(''.$this->uri->segment(7), $sort_columns)) ? $sort_columns[$this->uri->segment(7)] : 'tickets.tickets_last_active_date';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //CLIENT-PANEL: limit to this clients data
        if (is_numeric($this->client_id) || $this->uri->segment(1) == 'client') {
            $client_id = $this->client_id;
            $conditional_sql .= " AND tickets.tickets_client_id = '$client_id'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT tickets.*, tickets_departments.*, client_users.*, team_profile.*
                                          FROM tickets
                                            LEFT OUTER JOIN tickets_departments
                                              ON tickets_departments.department_id = tickets.tickets_department_id
                                            LEFT OUTER JOIN client_users
                                              ON client_users.client_users_id = tickets.tickets_by_user_id
                                              AND tickets.tickets_by_user_type = 'client'
                                            LEFT OUTER JOIN team_profile
                                              ON team_profile.team_profile_id = tickets.tickets_by_user_id
                                              AND tickets.tickets_by_user_type = 'team'
                                          WHERE 1 = 1
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

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }

    // -- allTicketCounts ----------------------------------------------------------------------------------------------
    /**
     * count all tickets based of their various statuses
     *
     * 
     * @param numeric   [client_id: optional; if provided, count will be limited to that clients]
     * @return	array
     */

    function allTicketCounts($client_id = '',$team_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //is this for a client
        if (is_numeric($client_id)) {
            $conditional_sql .= " AND tickets_client_id = '$client_id'";
        }
		if (is_numeric($team_id)) {
            $conditional_sql .= " AND tickets_assigned_to_id = '$team_id'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT (SELECT COUNT(tickets_id)
                                            FROM tickets WHERE tickets_status = 'new'
                                            $conditional_sql) AS new,
                                           (SELECT COUNT(tickets_id)
                                            FROM tickets WHERE tickets_status = 'closed'
                                            $conditional_sql) AS closed,
                                           (SELECT COUNT(tickets_id)
                                            FROM tickets WHERE tickets_status = 'client-replied'
                                            $conditional_sql) AS client_replied,
                                           (SELECT COUNT(tickets_id)
                                            FROM tickets WHERE tickets_status = 'answered'
                                            $conditional_sql) AS answered,
                                           (SELECT COUNT(tickets_id)
                                            FROM tickets WHERE tickets_status NOT IN ('closed')
                                            $conditional_sql) AS all_open,
                                           (SELECT COUNT(tickets_id)
                                            FROM tickets WHERE 1 = 1
                                            $conditional_sql) AS all_tickets
                                          FROM tickets
                                          WHERE 1 = 1
                                          LIMIT 1");
        $results = $query->row_array();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return results
        return $results;

    }

    // -- migrateTickets ----------------------------------------------------------------------------------------------
    /**
     * migrate tickets from department to another
     * @return	array
     */

    function migrateTickets($old_department_id = '', $new_department_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($new_department_id) || ! is_numeric($old_department_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [old_department_id or new_department_id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE tickets
                                          SET tickets_department_id = $new_department_id
                                          WHERE tickets_department_id = $old_department_id");

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

    // -- deleteTickets ----------------------------------------------------------------------------------------------
    /**
     * delete all tickets in a department and ticket replies
     * @return	array
     */

    function deleteTickets($department_id)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($department_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [department_id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM tickets
                                          WHERE tickets_department_id = $department_id");

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

    // -- deleteTicket ----------------------------------------------------------------------------------------------
    /**
     * delete a single ticket and all its related [ticket replies]
     * @return	array
     */

    function deleteTicket($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [ticket id: $id]", '');
            return false;
        }

        //escape input
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE tickets, tickets_replies
                                          FROM tickets
                                          LEFT OUTER JOIN tickets_replies
                                          ON tickets_replies.tickets_replies_ticket_id = tickets.tickets_id
                                          WHERE tickets.tickets_id = $id");

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

    // -- getTicket ----------------------------------------------------------------------------------------------
    /**
     * get a single tickets details
     *
     * 
     * @param numeric $item ID]
     * @return	array
     */

    function getTicket($id = '')
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

        //CLIENT-PANEL: limit to this clients data
        if (is_numeric($this->client_id) || $this->uri->segment(1) == 'client') {
            $client_id = $this->client_id;
            $conditional_sql .= " AND tickets.tickets_client_id = '$client_id'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT tickets.*, tickets_departments.*, client_users.*, team_profile.*, clients.*,
                                                    (SELECT team_profile_full_name 
                                                            FROM team_profile
                                                            WHERE team_profile.team_profile_id = tickets.tickets_assigned_to_id)
                                                            AS assigned_to_name
                                          FROM tickets
                                            LEFT OUTER JOIN tickets_departments
                                              ON tickets_departments.department_id = tickets.tickets_department_id
                                            LEFT OUTER JOIN client_users
                                              ON client_users.client_users_id = tickets.tickets_by_user_id
                                              AND tickets.tickets_by_user_type = 'client'
                                            LEFT OUTER JOIN team_profile
                                              ON team_profile.team_profile_id = tickets.tickets_by_user_id
                                              AND tickets.tickets_by_user_type = 'team'
                                            LEFT OUTER JOIN clients
                                              ON clients.clients_id = tickets.tickets_client_id
                                          WHERE tickets_id = $id
                                          $conditional_sql");

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

    // -- addTicket ----------------------------------------------------------------------------------------------
    /**
     * add ticket to database
     *
     * 
     * @param	null
     * @return	mixed (insert id / false)
     */

    function addTicket()
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
        $ticket_has_attachment = ($this->input->post('tickets_file_name') != '') ? 'yes' : 'no';

        //do we have an assigned to ID. If not, set to ID of user posting this ticket
        if (!isset($tickets_assigned_to_id)) {
            $tickets_assigned_to_id = 1;
        }

        //CLIENT-PANEL: suppliment input
        if (is_numeric($this->client_id) || $this->uri->segment(1) == 'client') {
            $tickets_client_id = $this->client_id;
            $tickets_by_user_type = "'client'";
            $tickets_status = "'new'";
            $tickets_by_user_id = $this->data['vars']['my_id'];
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO tickets (
                                          tickets_department_id,
                                          tickets_assigned_to_id,
                                          tickets_date,
                                          tickets_title,
                                          tickets_message,
                                          tickets_client_id,
                                          tickets_by_user_id,
                                          tickets_by_user_type,
                                          tickets_last_active_date,
                                          tickets_status,
                                          tickets_file_name,
                                          tickets_file_folder,
                                          tickets_file_size,
                                          tickets_file_extension,
                                          tickets_has_attachment
                                          )VALUES(
                                          $tickets_department_id,
                                          $tickets_assigned_to_id,
                                          NOW(),
                                          $tickets_title,
                                          $tickets_message,
                                          $tickets_client_id,
                                          $tickets_by_user_id,
                                          $tickets_by_user_type,
                                          NOW(),
                                          $tickets_status,
                                          $tickets_file_name,
                                          $tickets_file_folder,
                                          $tickets_file_size,
                                          $tickets_file_extension,
                                          '$ticket_has_attachment')");

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

    // -- updateTicket ----------------------------------------------------------------------------------------------
    /**
     * update a tickets [assigned to], [status], [department]
     *
     * 
     * @param	void
     * @return	mixed book
     */

    function updateTicket()
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

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE tickets
                                          SET 
                                          tickets_assigned_to_id = $tickets_assigned_to_id,
                                          tickets_department_id = $tickets_department_id,
                                          tickets_status = $tickets_status
                                          WHERE tickets_id = $tickets_id");

        $results = $this->db->affected_rows();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

    // -- updateTicket ----------------------------------------------------------------------------------------------
    /**
     * update a ticket status
     *
     * 
     * @param numeric $ticket_id]
     * @param	string $status: 'new', 'anwered', 'client-replied', 'closed']
     * @return	bool
     */

    function updateStatus($ticket_id = '', $status = 'answered')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($ticket_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$ticket_id]", '');
            return false;
        }

        //validate status
        if (! in_array($status, array(
            'new',
            'answered',
            'client-replied',
            'closed'))) {

            $this->__debugging(__line__, __function__, 0, "Invalid Ticket Status [status=$status]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE tickets
                                          SET 
                                          tickets_status = '$status'
                                          WHERE tickets_id = $ticket_id");

        $results = $this->db->affected_rows();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

    // -- deleteClientsTickets ----------------------------------------------------------------------------------------------
    /**
     * bulk delete ticets, based on a client's ID
     * typically used when deleting a client
     *
     * 
     * @param	numeri   [id; client_id]
     * @return	bool
     */

    function deleteClientsTickets($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //flow control
        $next = true;

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id: $id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM tickets
                                          WHERE tickets_client_id = $id");
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
     * @param numeric $resource_id]
     * @param   numeric [client_id]
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
                                          FROM tickets 
                                          WHERE tickets_id = $resource_id
                                          AND tickets_client_id = $client_id");

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

/* End of file tickets_model.php */
/* Location: ./application/models/tickets_model.php */
