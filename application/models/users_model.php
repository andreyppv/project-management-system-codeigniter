<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Users_model extends Super_Model
{

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

    // -- checkLogins ----------------------------------------------------------------------------------------------
    /**
     * checks email and password against database records.
     * returns row of users's profile or returns false;
     * @return	mixed (table row / false)
     */

    function checkLogins()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //mysq protection
        $email = $this->db->escape($this->input->post('email'));
        $password = $this->db->escape(md5($this->input->post('password')));

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //fetch row
        $query = $this->db->query("SELECT * FROM client_users 
                                            WHERE client_users_email = $email
                                            AND client_users_password = $password");
        $results = $query->row_array();

        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');
        //----------sql & benchmarking start----------

        //debug data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);

        //__________RESULT__________
        if ($query->num_rows() > 0) {
            return $results;
        } else {
            return false;
        }

    }

    // -- allClients ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of users in table
     * accepts order_by and asc/desc values
     *
     * @usedby  various [mainly for producing pulldown lists]
     * 
     * @param	string
     * @return	array
     */

    function allUsers($orderby = 'client_users_full_name', $sort = 'ASC', $client_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //check if any specifi ordering was passed
        if (!$this->db->field_exists($orderby, 'client_users')) {
            $orderby = 'client_users_full_name';
        }

        //check if sorting type was passed
        $sort = ($sort == 'asc' || $sort == 'desc') ? $sort : 'ASC';

        //CLIENT-PANEL: check if client id has been provided
        if (is_numeric($client_id)) {
            $conditional_sql .= " AND client_users.client_users_clients_id = '$client_id'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM client_users
                                          WHERE 1=1
                                          $conditional_sql
                                          ORDER BY $orderby $sort");

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

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }

    // -- searchUsers ----------------------------------------------------------------------------------------------
    /**
     * search users database and return results for all matching users as array
     *
     * @usedby  Admin->Clients->[list/search] menu
     * 
     * 
     * @return	array
     */

    function searchUsers($offset = 0, $type = 'search', $client_id = '')
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
        //actual post data is already cached, so use that instead of $_post
        if ($this->input->get('client_users_full_name')) {
            $client_users_full_name = str_replace("'", "", $this->db->escape($this->input->get('client_users_full_name')));
            $conditional_sql .= " AND client_users.client_users_full_name LIKE '%$client_users_full_name%'";
        }
        if ($this->input->get('clients_company_name')) {
            $clients_company_name = str_replace("'", "", $this->db->escape($this->input->get('clients_company_name')));
            $conditional_sql .= " AND clients.clients_company_name LIKE '%$clients_company_name%'";
        }
        if ($this->input->get('client_users_email')) {
            $client_users_email = $this->db->escape($this->input->get('client_users_email'));
            $conditional_sql .= " AND client_users.client_users_email = $client_users_email";
        }
        if (is_numeric($this->input->get('client_users_id'))) {
            $client_users_id = $this->db->escape($this->input->get('client_users_id'));
            $conditional_sql .= " AND client_users.client_users_id = $client_users_id";
        }

        //CLIENT-PANEL: check if client id has been provided
        if (is_numeric($client_id)) {
            $conditional_sql .= " AND client_users.client_users_clients_id = '$client_id'";
        }

        //--------client|admin results sorting---------------------------------------------------------------

        $sort_columns = array(
            'sortby_userid' => 'client_users.client_users_id',
            'sortby_companyname' => 'clients.clients_company_name',
            'sortby_fullname' => 'client_users.client_users_full_name');

        //admin side uri
        $sort_order = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by = (array_key_exists('' . $this->uri->segment(6), $sort_columns)) ? $sort_columns[$this->uri->segment(6)] : 'client_users.client_users_id';

        //client side uri
        if ($this->uri->segment(1) == 'client') {
            $sort_order = ($this->uri->segment(4) == 'desc') ? 'desc' : 'asc';
            $sort_by = (array_key_exists('' . $this->uri->segment(5), $sort_columns)) ? $sort_columns[$this->uri->segment(5)] : 'client_users.client_users_id';
        }

        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //--------client|admin results sorting---------------------------------------------------------------

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
        $query = $this->db->query("SELECT clients.*,
                                          client_users.*
                                          FROM client_users
                                            LEFT OUTER JOIN clients
                                            ON clients.clients_id = client_users.client_users_clients_id
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

    // -- userDetails ----------------------------------------------------------------------------------------------
    /**
     * load all user details based on client_users_id
     *
     * @usedby  Admin->Clients->[list/search] menu
     * 
     * 
     * @return	array
     */

    function userDetails($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            return false;
        }

        //escape data
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT client_users.*, clients.*
                                            FROM client_users
                                            LEFT OUTER JOIN clients
                                            ON clients.clients_id = client_users.client_users_clients_id
                                            WHERE client_users_id = $id");

        $results = $query->row_array();

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }

    // -- clientUsers ----------------------------------------------------------------------------------------------
    /**
     * returns all an array of all the users for a given client_id
     *
     * @usedby  Admin->Clients->[list/search] menu
     * 
     * 
     * @return	array
     */

    function clientUsers($client_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($client_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //escape data
        $client_id = $this->db->escape($client_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * FROM client_users 
                                            WHERE client_users_clients_id = $client_id
                                            ORDER BY client_users_main_contact DESC");

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

    // -- mainUserDetails ----------------------------------------------------------------------------------------------
    /**
     * load all user details of the main/primary user, bases on client_id
     *
     * @usedby  Admin->Clients->[list/search] menu
     * 
     * 
     * @return	array
     */

    function mainUserDetails($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            return false;
        }

        //escape data
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM client_users
                                          WHERE client_users_clients_id = $id
                                          AND client_users_main_contact = 'yes'");

        $results = $query->row_array();

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }

    // -- updateUserDetails ----------------------------------------------------------------------------------------------
    /**
     * update user details, field by field. Input is normaly coming from Modal (editable) as selected by client_users_id.
     * returns false or true
     *
     * @usedby  Many
     * 
     * @param	mixed
     * @return	bool
     */

    function updateUserDetails($client_users_id = '', $field = '', $new_value = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no value client id, return false
        if (!is_numeric($client_users_id) || $field == '') {
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: missing data (client_users_id or field)]");
            return false;
        }

        //check if field exists in database table
        if (!$this->db->field_exists($field, 'client_users')) {
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: field (field) not found]");
            //return
            return false;
        }

        //escape data
        $client_users_id = $this->db->escape($client_users_id);

        //md5 password only
        if ($field == 'client_users_password') {
            $new_value = $this->db->escape(md5($new_value));
        } else {
            $new_value = $this->db->escape($new_value);
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE client_users
                                          SET $field = $new_value
                                          WHERE client_users_id = $client_users_id");

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

    // -- updatePrimaryContact ----------------------------------------------------------------------------------------------
    /**
     * clients details, field by field. Input is normaly coming from Modal (editable) as selected by client_id.
     * returns false or true
     *
     * @usedby  Many
     * 
     * @param	mixed
     * @return	bool
     */

    function updatePrimaryContact($client_id = '', $new_client_users_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no value client id, return false
        if (!is_numeric($client_id) || !is_numeric($new_client_users_id)) {
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: invalid data (client_id or new_client_users_id)]");
            return false;
        }

        //escape data
        $new_client_users_id = $this->db->escape($new_client_users_id);
        $client_id = $this->db->escape($client_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        //set all other clients users primary contact field (client_users_main_contact) to 'no' and the selcted one to 'yes'
        $query = $this->db->query("UPDATE client_users
                                         SET
                                         client_users_main_contact = CASE WHEN client_users_id = $new_client_users_id THEN 'yes'
                                         ELSE 'no'
                                         END
                                         WHERE client_users_clients_id = $client_id");

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
	function updateLead($lead_id = '', $client_id = '')
    {
        	

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $q = "UPDATE leads
                                         SET
                                         leads_clients_id = $client_id
                                         WHERE id = $lead_id";
        $query = $this->db->query($q);

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
    // -- isEmailAlreadyInuse ----------------------------------------------------------------------------------------------
    /**
     * checks if a client-user with a user with same email aready exists.
     * email addresses are used during login and so are expected to be unique for each client user
     * @return	bool
     */

    function isEmailAlreadyInuse($email = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if ($email == '') {
            return false;
        }

        //escape data
        $email = $this->db->escape($email);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                            FROM client_users 
                                            WHERE client_users_email = $email");
        $results = $query->num_rows(); //count rows

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if ($results == 0) {
            return true;
        } else {
            return false;
        }

    }

    // -- isEmailAlreadyInuse ----------------------------------------------------------------------------------------------
    /**
     * checks a client user record exists, based on email address. Used mainly for reminder email
     * @param	string  [email]
     * @return	array   [row of users details]
     */

    function checkRecordExists($email = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if ($email == '') {
            return false;
        }

        //escape data
        $email = $this->db->escape($email);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                            FROM client_users 
                                            WHERE client_users_email = $email");
        $results = $query->row_array();

        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');
        //----------sql & benchmarking start----------

        //debug data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);

        //__________RESULT__________
        if ($query->num_rows() > 0) {
            return $results;
        } else {
            return false;
        }
    }

    // -- isPrimaryContact ----------------------------------------------------------------------------------------------
    /**
     * checks if a client-user (id) is the primary/main contact
     * @return	bool
     */

    function isPrimaryContact($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            return false;
        }

        //escape data
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                            FROM client_users 
                                            WHERE client_users_id = $id
                                            AND client_users_main_contact = 'yes'");
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

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if ($results === 1) {
            return true;
        } else {
            return false;
        }

    }

    // -- addUser ----------------------------------------------------------------------------------------------
    /**
     * add new user to the database
     * expects form post input
     * returns the new users id (client_users_id)
     *
     * @usedby  Admin->Clients->__addClients()
     *          Admin->Users->__addUsers()
     * 
     * @param	string
     * @return	bool
     */

    function addUser($client_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        /*text formatting - ucwords*/
        $format_input = array('client_users_full_name', 'client_users_job_position_title');

        //escape all data and set as variable
        foreach ($_POST as $key => $value) {

            //format any applicable text
            if (in_array($key, $format_input)) {
                $$key = $this->db->escape(ucwords(strtolower($this->input->post($key))));
            } else {
                $$key = $this->db->escape($this->input->post($key));
            }

            //md5 hash password
            if ($key == 'client_users_password') {
                $$key = $this->db->escape(md5($value));
            }
        }

        //unique user code
        $client_users_uniqueid = random_string('alnum', 20);

        //do we have a valid client id
        if (!is_numeric($client_id)) {
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO client_users (
                                               client_users_clients_id,
                                               client_users_uniqueid,
                                               client_users_full_name,
                                               client_users_job_position_title,
                                               client_users_email,
                                               client_users_password,
                                               client_users_telephone
                                               )VALUES(
                                               '$client_id',
                                               '$client_users_uniqueid',
                                               $client_users_full_name,
                                               $client_users_job_position_title,
                                               $client_users_email,
                                               $client_users_password,
                                               $client_users_telephone)");

        $results = $this->db->insert_id(); //client_users_id (last insert item)

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

        //return new client_users_id or false
        if ($results > 0) {
            return $results;
        } else {
            return false;
        }

    }

    // -- deleteUser ----------------------------------------------------------------------------------------------
    /**
     * delete a use based on the [client_users_id]
     * @return	array
     */

    function deleteUser($id = '', $client_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            return false;
        }

        //escape data
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM client_users 
                                          WHERE client_users_id = $id");
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

        //return affected rows
        return $results;

    }

    // -- deleteClientUsers ----------------------------------------------------------------------------------------------
    /**
     * delete all users based on client id (used whend deleting a client)
     * @return	array
     */

    function deleteClientUsers($client_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($client_id)) {
            return false;
        }

        //escape data
        $client_id = $this->db->escape($client_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM client_users 
                                          WHERE client_users_clients_id = $client_id");
        $results = $this->db->affected_rows(); //affected rows

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return affected rows
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }

    }

    // -- registerLastActive ----------------------------------------------------------------------------------------------
    /**
     * logs the datetime when user was last active
     * @return	bool
     */

    function registerLastActive($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            return false;
        }

        //escape data
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE client_users
                                            SET client_users_last_active = NOW() 
                                            WHERE client_users_id = $id");
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
        if ($results == 1) {
            return true;
        } else {
            return false;
        }

    }

    // -- retrieveLastActive ----------------------------------------------------------------------------------------------
    /**
     * retrieves the datetime when team member was last active
     * @return	bool
     */

    function retrieveLastActive($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            return false;
        }

        //escape data
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT client_users_last_active
                                            FROM client_users 
                                            WHERE client_users_id = $id");
        $results = $query->row_array(); //single row array
        $last_active = $results['client_users_last_active'];

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
        return $last_active;

    }

    // -- clientPrimaryUser ----------------------------------------------------------------------------------------------
    /**
     * returns the row for the primary user, for a given client_id
     * @param	numeic [client_id]
     * @return	array
     */

    function clientPrimaryUser($client_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($client_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [client id=$client_id]", '');
            return false;
        }

        //escape params items
        $client_id = $this->db->escape($client_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM client_users 
                                          WHERE client_users_clients_id = $client_id
                                          AND client_users_main_contact = 'yes'");

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

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }

    // -- updateAvatar ----------------------------------------------------------------------------------------------
    /**
     * updates teammembers profile with new "file name" (complete with extension)
     *   when uploading an avatar for a team member
     * @param numeric $id: client user id]
     * @param numeric $filename: avatar full file name]
     * @return	bool
     */

    function updateAvatar($id = '', $filename = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id) || $filename == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id or filename:$filename]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);
        $filename = $this->db->escape($filename);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE client_users
                                          SET client_users_avatar_filename = $filename
                                          WHERE client_users_id = $id");

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

    // -- resetPasswordSetup ----------------------------------------------------------------------------------------------
    /**
     * adds a unique code to user profile which is used to verify reset password link
     * adds a timestamp to allow the link expire
     * 
     * @param	string
     * @return	bool
     */

    function resetPasswordSetup($email = '', $random_code)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //check data is valid
        if ($email == '' || $random_code == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [email=$email] [code=$random_code]", '');
            return false;
        }

        //mysq protection
        $email = $this->db->escape($email);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //update row
        $query = $this->db->query("UPDATE client_users SET 
                                          client_users_reset_code = '$random_code',
                                          client_users_reset_timestamp = NOW()
                                          WHERE client_users_email = $email");
        $results = $this->db->affected_rows();

        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');
        //----------sql & benchmarking end----------

        //debug data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);

        //__________RESULT__________
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }

    }

    // -- resetPasswordCheckCode ----------------------------------------------------------------------------------------------
    /**
     * checks if a valid reset code has been provided
     * returns true on valid
     * @param	string
     * @return	bool
     */

    function resetPasswordCheckCode($reset_code = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //mysql security
        $reset_code = $this->db->escape($reset_code);

        //return if no code provided
        if ($reset_code == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [reset_code=$reset_code]", '');
            return false;
        }

        //----------sql & benchmarking start----------[check reset code]
        $this->benchmark->mark('code_start');

        //check validity of resetcode
        $query = $this->db->query("SELECT * FROM client_users
                                            WHERE client_users_reset_timestamp >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                                            AND client_users_reset_code = $reset_code");
        $rows = $query->num_rows();
        $results = $query->row_array();

        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debug data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking start----------

        //_____RESULT_____
        if ($rows == 1) {
            return $results;
        } else {
            return false;
        }

    }

    // -- resetPassword ----------------------------------------------------------------------------------------------
    /**
     * lets try to reset the users password
     * returns true on valid
     * @param	string
     * @return	bool
     */

    function resetPassword($reset_code = '', $new_password = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //check code & password is not empty
        if ($reset_code == '' || $new_password == '') {
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Expected variable reset_code & new_password missing]");
            return false;
        }

        //mysq protection
        $reset_code = $this->db->escape($reset_code);
        //new password
        $new_password = md5($new_password);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //check validity of resetcode
        $query = $this->db->query("UPDATE client_users SET 
                                              client_users_password = '$new_password',
                                              client_users_reset_code = '',
                                              client_users_reset_timestamp = ''
                                            WHERE client_users_reset_code = $reset_code");
        $results = $this->db->affected_rows();

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debug data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking start-----------

        //return results
        if ($results > 0) {
            return true;
        } else {
            return false;
        }

    }

    // -- allBugsCounts ----------------------------------------------------------------------------------------------
    /**
     * count all users
     * @param numeric   [client_id: optional; if provided, count will be limited to that clients]
     * @return	array
     */

    function allUsersCounts($client_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //is this for a client
        if (is_numeric($client_id)) {
            $conditional_sql .= " AND client_users_clients_id = '$client_id'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM client_users 
                                          WHERE 1 = 1
                                          $conditional_sql");

        //other results
        $results = $query->num_rows(); //single row array

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

/* End of file users_model.php */
/* Location: ./application/models/users_model.php */
