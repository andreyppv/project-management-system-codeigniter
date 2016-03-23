<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Tickets_mailer_model extends Super_Model
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

    // -- getSettings ----------------------------------------------------------------------------------------------
    /**
     * get default mailer settings
     *
     * 
     * @param	string [name: groups name], [age: users age]
     * @return	array
     */

    function getSettings($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM tickets_mailer 
                                          WHERE tickets_mailer_id = 'default'");

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

    // -- editSettings ----------------------------------------------------------------------------------------------
    /**
     * edit default mailer settings
     *
     * 
     * @param	string [name: groups name], [age: users age]
     * @return	array
     */

    function editSettings($id = '')
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
        $query = $this->db->query("UPDATE tickets_mailer
                                          SET 
                                          tickets_mailer_enabled = $tickets_mailer_enabled,
                                          tickets_mailer_delete_read = $tickets_mailer_delete_read,
                                          tickets_mailer_imap_pop = $tickets_mailer_imap_pop,
                                          tickets_mailer_ssl = $tickets_mailer_ssl,
                                          tickets_mailer_email_address = $tickets_mailer_email_address,
                                          tickets_mailer_server = $tickets_mailer_server,
                                          tickets_mailer_server_port = $tickets_mailer_server_port,
                                          tickets_mailer_username = $tickets_mailer_username,
                                          tickets_mailer_password = $tickets_mailer_password,
                                          tickets_mailer_flags = $tickets_mailer_flags,
                                          tickets_mailer_imap_settings = $tickets_mailer_imap_settings
                                          WHERE tickets_mailer_id = 'default'");

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

/* End of file tickets_mailer_model.php */
/* Location: ./application/models/tickets_mailer_model.php */
