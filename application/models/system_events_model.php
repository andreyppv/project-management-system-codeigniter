<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class System_events_model extends Super_Model
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

}

/* End of file system_events_model.php */
/* Location: ./application/models/system_events_model.php */
