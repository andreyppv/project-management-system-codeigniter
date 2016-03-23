<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Mynotes_model extends Super_Model
{

    var $debug_methods_trail; //method profiling
    var $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------
    /**
     */
    function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        // Call the Model constructor
        parent::__construct();
    }


    // -- getNotes ----------------------------------------------------------------------------------------------
    /**
     * - get a team members notes
     * @param	string [id: team members id]
     * @return	array
     */

    function checkNotes($project_id = '', $id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //validate id
        if (!is_numeric($id) || !is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id] or [project_id=$project_id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM mynotes 
                                          WHERE mynotes_team_id = $id
                                          AND mynotes_project_id = $project_id
                                          LIMIT 1");

        //other results
        $results = $query->num_rows(); //multi row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }
    
    
    // -- getNotes ----------------------------------------------------------------------------------------------
    /**
     * - get a team members notes
     * @param	string [id: team members id]
     * @return	array
     */

    function getNotes($project_id = '', $id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;
        
        //validate id
        if (!is_numeric($id) || !is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id] or [project_id=$project_id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM mynotes 
                                          WHERE mynotes_team_id = $id
                                          AND mynotes_project_id = $project_id
                                          LIMIT 1");

        //other results
        $results = $query->row_array(); //multi row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    // -- newNote ----------------------------------------------------------------------------------------------
    /**
     * - create a new note for a team member
     * @param	string [id: team members id]
     * @return	array
     */

    function newNote($project_id = '', $id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //validate id
        if (!is_numeric($id) || !is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id] or [project_id=$project_id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO mynotes
                                          (mynotes_project_id, mynotes_team_id)
                                          VALUES
                                          ($project_id, $id)");

        //other results
        $results = $this->db->insert_id();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if ($results > 0) {
            return $results;
        } else {
            return false;
        }

    }
    
    
        // -- updateNote ----------------------------------------------------------------------------------------------
    /**
     * - update a project note
     * @param	string [id: team members id]
     * @return	array
     */

    function updateNote($project_id = '', $id = '', $notes='')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //validate id
        if (!is_numeric($id) || !is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id] or [project_id=$project_id]", '');
            return false;
        }
        
        //escape data
        $notes = $this->db->escape($notes);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE mynotes
                                          SET 
                                          mynotes_text = $notes,
                                          mynotes_last_edited = NOW()
                                          WHERE mynotes_project_id = $project_id
                                          AND mynotes_team_id = $id");

        //other results
        $results = $this->db->affected_rows();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if ($results > 0) {
            return true;
        } else {
            return false;
        }

    }
}
