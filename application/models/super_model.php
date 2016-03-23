<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// -- Super_model- -------------------------------------------------------------------------------------------------------
/**
 * @About 
 * NEXTLOOP
 * This was created mainly to deal with timezone offsets, so as to synchronise php dates with mysql dates
 * All other models now extend this super model instead of CI_Model
 * This model will execute the first sql before anyother sql
 * Its main method is __setTimeZoneOffset
 */

class Super_model extends CI_Model
{

    public $model_debug_output; //debug data
    public $debug_data; //debug data
    public $debug_methods_trail;
    public $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------

    public function __construct()
    {

        // Call the Model constructor
        parent::__construct();

        //Set timezone offset
        $this->__setTimeZoneOffset();
    }

    // -- __setTimeZoneOffset ----------------------------------------------------------------------------------------------
    /**
     * some descrition here
     * @return	array
     */

    public function __setTimeZoneOffset()
    {

        //get the offset, based on what timezone php is using. (php timezone as set in config)
        $date = new DateTime();
        $offset = $date->format("P"); //e.g +2:00
        $this->db->query("SET time_zone='$offset'");

        //bugging
        $last_query = $this->db->last_query();
        $last_error = $this->db->_error_message();
        $debug = "<pre>" . __function__ . "<br/>$last_query<br/><br/>$last_error</pre>";

        //profiling:: (combined with  debug)
        $this->debug_methods_trail[] = $debug;
    }

    // -- __debugging ----------------------------------------------------------------------------------------------
    /**
     * debug prepares debug data and saves it to debug_data
     *
     * 
     * @param	mixed (number/string)
     * @return void
     */

    public function __debugging($line_number = '', $function = '', $execution_time = '', $notes = '', $sql_results = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //is there aany need for mysql data
        $last_query = ($sql_results === '') ? 'N/A' : $this->db->last_query();
        $last_error = ($sql_results === '') ? 'N/A' : $this->db->_error_message();

        $debug_array = array(
            'last_query' => $last_query,
            '_error_message' => $last_error,
            'results' => $sql_results,
            'file' => __file__,
            'line' => $line_number,
            'function' => $function,
            'execution_time' => $execution_time,
            'notes' => $notes);

        $this->debug_data = debug_models($debug_array);

    }

}

/* End of file super_model.php */
/* Location: ./application/models/super_model.php */
