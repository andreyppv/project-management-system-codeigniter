<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Settings_payment_methods_model extends Super_Model
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

    // -- paymentMethods ----------------------------------------------------------------------------------------------
    /**
     * get payment methods
     *
     * 
     * @param	string [status: status of payment method], ['enabled', 'disabled']
     * @return	array
     */

    function paymentMethods($status = 'enabled')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        if (in_array($status, array('enabled', 'disabled'))) {
            $conditional_sql .= " AND settings_payment_methods_status = '$status'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * FROM settings_payment_methods
                                            WHERE 1 = 1
                                            $conditional_sql");

        $results = $query->result_array(); //multi row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return for SELECT
        return $results;

    }

    // -- updateStatus ----------------------------------------------------------------------------------------------
    /**
     * update the 'enabled/disabled' status
     *
     * 
     * @param	string $gateway_name]
     * @param	string $new_status: 'enabled'/'disabled']
     * @return	bool
     */

    function updateStatus($gateway_name = '', $new_status = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if ($gateway_name == '' || ! in_array($new_status, array('enabled', 'disabled'))) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data", '');
            return false;
        }

        //escape params items
        $new_status = $this->db->escape($new_status);
        $gateway_name = $this->db->escape($gateway_name);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE settings_payment_methods
                                          SET settings_payment_methods_status = $new_status
                                          WHERE settings_payment_methods_name = $gateway_name");

        $results = $this->db->affected_rows();

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

/* End of file settings_payment_methods_model.php */
/* Location: ./application/models/settings_payment_methods_model.php */
