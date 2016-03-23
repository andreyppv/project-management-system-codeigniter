<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all project optional fields related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Projectsoptionalfields_model extends Super_Model
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

    // -- optionalFields ----------------------------------------------------------------------------------------------
    /**
     * returns an array of optional fields and their status (enabled/disabled/all)
     *
     * @param string $status 'enabled', 'disabled', 'all'
     * @return array
     */

    function optionalFields($status = 'all')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //check if tvalid status has been passed
        if (in_array($status, array('enabled', 'disabled'))) {
            $conditional_sql .= " AND projects_optionalfield_status = '$status'";
        }

        //escape data
        $status = $this->db->escape($status);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                      FROM projects_optionalfields 
                                      WHERE 1 = 1
                                      $conditional_sql");

        $results = $query->result_array();

        //----------sql & benchmarking end----------
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
     * update setting for optional form fields
     *
     * @return	bool
     */

    function editSettings($field_name = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate field name param
        if (! in_array($field_name, array(
            'projects_optionalfield1',
            'projects_optionalfield2',
            'projects_optionalfield3',
            'projects_optionalfield4',
            'projects_optionalfield5'))) {
            return false;
        }

        /*
        *--------------------------------------------------------------
        * VALIDATION OF POST DATA
        *
        * check if the values set in post are valid
        * if not, fall back to defaults
        * its cheating a bit, but as we are looping the updates...
        * ...for now its the easy way out
        *--------------------------------------------------------------
        */

        foreach ($_POST as $key => $value) {

            if ($key == 'projects_optionalfield_status' && (! in_array($value, array('enabled', 'disabled')))) {
                $_POST[$key] = 'disabled'; //just set to disabled
            }
            if ($key == 'projects_optionalfield_require' && (! in_array($value, array('yes', 'no')))) {
                $_POST[$key] = 'no'; //just set to disabled
            }
            if ($key == 'projects_optionalfield_title' && ($value == '')) {
                $_POST[$key] = 'Field'; //some place holder text
            }
            $$key = $this->db->escape($this->input->post($key));
        }

        //conditional sql
        $conditional_sql = " AND projects_optionalfield_name = '$field_name'";

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start'); //_____SQL QUERY_______
        $query = $this->db->query("UPDATE projects_optionalfields
                                          SET
                                          projects_optionalfield_title = $projects_optionalfield_title,
                                          projects_optionalfield_status = $projects_optionalfield_status,
                                          projects_optionalfield_require = $projects_optionalfield_require
                                          WHERE 1 = 1
                                          $conditional_sql");

        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);

        //---return
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }
}

/* End of file projectsoptionalfields_model.php */
/* Location: ./application/models/projectsoptionalfields_model.php */
