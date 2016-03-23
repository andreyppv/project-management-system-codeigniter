<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Tickets_departments_model extends Super_Model
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

    // -- allDepartments ----------------------------------------------------------------------------------------------
    /**
     * get all ticket departments
     *
     * 
     * @return	array
     */

    function allDepartments()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT tickets_departments.*,
                                          (SELECT COUNT(tickets_id) 
                                                  FROM tickets
                                                  WHERE tickets.tickets_department_id = tickets_departments.department_id)
                                                  AS tickets_count                                                  
                                          FROM tickets_departments
                                          ORDER BY department_name ASC");

        $results = $query->result_array();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }

    // -- isUnique ----------------------------------------------------------------------------------------------
    /**
     * check if department name is unique
     *
     * 
     * @return	array
     */

    function isUnique($name = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate input
        if ($name == '') {
            //log this error
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: invalid input ($name)]");
            //return
            return false;

        }

        //escape
        $name = $this->db->escape($name);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM tickets_departments
                                          WHERE department_name = $name");

        $results = $query->num_rows();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if ($results === 0) {
            return true; //"zero" results found
        } else {
            return false;
        }
    }

    // -- addDepartment ----------------------------------------------------------------------------------------------
    /**
     * add new ticket department
     *
     * 
     * @param	void
     * @return	mixed [record insert id / bool(false)]
     */

    function addDepartment()
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
        $query = $this->db->query("INSERT INTO tickets_departments (
                                          department_name,
                                          department_description                                       
                                          )VALUES(
                                          $department_name,
                                          $department_description)");

        $results = $this->db->insert_id(); //(last insert item)

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        //return new  client_id or false
        if ($results > 0) {
            return $results;
        } else {
            return false;
        }
    }

    // -- editDepartment ----------------------------------------------------------------------------------------------
    /**
     * edit a ticket department
     *
     * 
     * @param	void
     * @return	mixed [record insert id / bool(false)]
     */

    function editDepartment()
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
        $query = $this->db->query("UPDATE tickets_departments
                                          SET
                                          department_name = $department_name,
                                          department_description = $department_description                                      
                                          WHERE
                                          department_id = $department_id");

        $results = $this->db->insert_id(); //(last insert item)

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

    // -- deleteDepartment ----------------------------------------------------------------------------------------------
    /**
     * - delete support tickets department
     * @param numeric $department_id
     * @return	bool
     */

    function deleteDepartment($department_id = '')
    {

        //validate
        if (!is_numeric($department_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [department id=$department_id]", '');
            return false;
        }

        //escape params items
        $department_id = $this->db->escape($department_id);

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM tickets_departments 
                                          WHERE department_id = $department_id");

        //other results
        $results = $this->db->affected_rows();

        //debugging data
        $this->__debugging(__line__, __function__, '', __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

}

/* End of file tickets_departments_model.php */
/* Location: ./application/models/tickets_departments_model.php */
