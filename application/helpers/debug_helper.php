<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
// -- flmModelDebugData ----------------------------------------------------------------------------------------------
/**
 * takes debug data (normally from a model and formats it for browser display
 * returns "pretty" formatted debug data
 * 
 * @param	array $debug_array
 * @return	string (debug data)
 */
if (!function_exists('debug_models')) {
    function debug_models($debug_array = '')
    {

        //get $CI instance
        $CI = &get_instance();
        
        //declare variables
        $file = '';
        $line = '';
        $function = '';
        $notes = '';
        $last_query = '';
        $last_error = '';
        $sql_results = '';

        //save all debug data as variables
        if (is_array($debug_array)) {
            foreach ($debug_array as $key => $value) {
                $$key = $value;
            }
        }

        //time stamp
        $debug_models_date = @date('Y-m-d H:i');

        //create results html
        ob_start();
        echo '<pre>';
        if (is_array($results)) {
            print_r($results);
        } else {
            echo $results;
        }
        echo '</pre>';
        $sql_results = ob_get_contents();
        ob_end_clean();

        //create debug html output
        $debug = "<br/>SERVER TIME: $debug_models_date
                  <br/>FILE: $file
                  <br/>LINE: $line
                  <br/>FUNCTION: $function
                  <br/>NOTES: $notes
                  <br/>MYSQL-LAST QUERY: $last_query
                  <br/>MYSQL-LAST ERROR: $last_error
                  <br/>EXECUTION TIME: $execution_time
                  <br/><STRONG>SQL RESULTS/ROWS:</STRONG>
                  <br/>$sql_results
                  <br/>";

        //create final formmated html debug dtata
        ob_start();
        echo '<pre style="background-color: #F5F5F5; 
                          border: 1px solid #CCCCCC; 
                          border-radius: 4px 4px 4px 4px; 
                          color: #333333; display: block; 
                          font-size: 13px; 
                          line-height: 1.42857;
                          margin:0 0 10px;padding: 9.5px;
                          word-break: break-all;
                          word-wrap: break-word; margin-bottom:5px;">' . $debug . '</pre>';

        //save buffer to variable
        $debug_data = ob_get_contents();
        ob_end_clean();

        //return html formatted debug data
        return $debug_data;

    }
}

/* End of file debug_helper.php */
/* Location: ./application/helpers/debug_helper.php */
