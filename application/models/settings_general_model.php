<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Settings_general_model extends Super_Model
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
     * pull all general settings
     *
     * 
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

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM settings_general 
                                          WHERE settings_id = 'default'");

        $results = $query->row_array();

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

    // -- editSettings ----------------------------------------------------------------------------------------------
    /**
     * edit settings
     *
     * 
     * @param	void
     * @return	numeric [affected rows]
     */

    function editSettings()
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

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE settings_general
                                          SET
                                          currency_code= $currency_code,
                                          currency_symbol = $currency_symbol,
                                          dashboard_title = $dashboard_title,
                                          date_format = $date_format,
                                          language = $language,
                                          messages_limit = $messages_limit,
                                          timeline_limit = $timeline_limit,
                                          results_limit = $results_limit,
                                          theme = $theme,
                                          show_information_tips = $show_information_tips,
                                          notifications_display_duration = $notifications_display_duration,
                                          client_registration = $client_registration,
                                          product_purchase_code = $product_purchase_code
                                          WHERE settings_id = 'default'");

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

    // -- restoreDefaultSettings ----------------------------------------------------------------------------------------------
    /**
     * restores the settings_general row (applicable fields) to the default data
     * default data is stored in fields in the same row, with 'destore_' prefix
     *
     * 
     * @return	bool
     */

    function restoreDefaultSettings($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE settings_general
                                          SET 
                                          currency_code = restore_currency_code,
                                          currency_symbol = restore_currency_symbol,
                                          dashboard_title = restore_dashboard_title,
                                          date_format = restore_date_format,
                                          language = restore_language,
                                          messages_limit = restore_messages_limit,
                                          timeline_limit = restore_timeline_limit,
                                          results_limit = restore_results_limit,
                                          theme = restore_theme,
                                          show_information_tips = restore_show_information_tips,
                                          notifications_display_duration = restore_notifications_display_duration,
                                          client_registration = restore_client_registration
                                          WHERE settings_id = 'default'");

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
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }
}

/* End of file settings_general_model.php */
/* Location: ./application/models/settings_general_model.php */
