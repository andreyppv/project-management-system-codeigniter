<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Updating_model extends Super_Model
{

    var $debug_methods_trail; //method profiling
    var $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------
    /**
     * no action
     *
     * @access	private
     * @param	none
     * @return	none
     */
    function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        // Call the Model constructor
        parent::__construct();
    }

    // -- updateDatabase ----------------------------------------------------------------------------------------------
    /**
     * - runs individula queries passed to it from MY_Controller for any mysql.sql updates in /updates folder
     *
     * @param	string $query the mysql query to execute
     * @return	bool
     */

    function updateDatabase($query = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //check query
        if ($query == '') {
            return;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query($query);

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //did it go ok
        if ($transaction_result === true) {
            return true;

        } else {
            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: UPDATING MYSQL FAILES -  $db_error]");
            return false;
        }

    }

}
