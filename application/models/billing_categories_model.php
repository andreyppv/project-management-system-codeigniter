<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Billing_categories_model extends Super_Model
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
    }

    public function listCategories()
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //_____SQL QUERY_______
        $query = $this->db->query("
            SELECT
                *
            FROM billing_categories
            ORDER BY bcat_id
        ");
        $tmp = $query->result_array();
        $results = array();
        foreach($tmp as $t)
        {
            $results[$t['bcat_id']] = $t;
        }

        $this->__debugging(__line__, __function__, 0, "SQL", $this->db->last_query());

        return $results;
    }

}

/* End of file tasks_viewers_model.php */
/* Location: ./application/models/tasks_viewers_model.php */