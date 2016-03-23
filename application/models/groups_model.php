<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all bugs related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Groups_model extends Super_Model
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

    // -- allGroups ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of groups in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function allGroups()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT groups.*,
                                          (SELECT COUNT(team_profile_id)
                                                  FROM team_profile
                                                  WHERE team_profile_groups_id = groups.groups_id
                                                  )AS groups_members_count
                                          FROM groups
                                          ORDER BY groups_name ASC");

        $results = $query->result_array(); //multi row array

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
        return $results;

    }

    // -- allGroupMembers ----------------------------------------------------------------------------------------------
    /**
     * lists all group members based on group_id
     * 
     * @param	$group_id
     * @return	array
     */

    function allGroupMembers($group_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($group_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [group_id=$group_id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT groups.*, team_profile.*
                                            FROM groups
                                            RIGHT JOIN team_profile
                                            ON team_profile.team_profile_groups_id = groups.groups_id
                                            WHERE groups_id = $group_id");

        $results = $query->result_array(); //multi row array

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
        return $results;

    }

    // -- groupPermissions ----------------------------------------------------------------------------------------------
    /**
     * load groups permission levele
     * @param  $group_id
     * @param  $category
     * @return	array
     */

    function groupPermissions($group_id = '', $category)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($group_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [group_id=$group_id]", '');
            return false;
        }

        //check if category/colum exists in both groups and permissions tables
        if (! $this->db->field_exists($category, 'groups')) {
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT groups.$category AS groups_category,
                                                              groups.*,
                                                              permissions.*,
                                                              '$category' as 'component'
                                            FROM groups
                                            LEFT OUTER JOIN permissions
                                            ON permissions.level = groups.$category
                                            WHERE groups_id = $group_id");
        $results = $query->row_array(); //single row array

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
        return $results;

    }

    // -- groupDetails ----------------------------------------------------------------------------------------------
    /**
     * load full table for a particular group
     * 
     * @return	array
     */

    function groupDetails($group_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($group_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [group_id=$group_id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT * FROm groups
                                            WHERE groups_id = $group_id");
        $results = $query->row_array(); //single row array

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
        return $results;

    }

    // -- permissionsChange ----------------------------------------------------------------------------------------------
    /**
     * change the permission level for a single category in a group.
     * e.g. set permission level for [agents > projects] to 4
     *
     * @usedby  Admin->Clients->[list/search] menu
     * 
     * @param	string: [group_id], [category: e.g. projects/clients/etc], [level: new permission levels]
     * @return	affected rows / bool
     */

    function permissionsChange($group_id = '', $category = '', $level = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate input
        if (! is_numeric($group_id) || ! is_numeric($level) || ! $this->db->field_exists($category, 'groups')) {
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: missing/invalid data (group_id:$group_id or level:$level or category:$category)]");
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
        $query = $this->db->query("UPDATE groups
                                            SET $category = '$level'
                                            WHERE groups_id = '$group_id'");

        //rows affected
        $results = $this->db->affected_rows();

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

    // -- isActionAllowable ----------------------------------------------------------------------------------------------
    /**
     * checks if a particular action is allowed against this table
     * good for testing prior to carrying out the database operation/query
     * example: checking is a certain record is allowed to be deleted or edited
     *
     * @param  $id
     * @param  $check
     * @return	bool
     */

    function isActionAllowable($id = '', $check = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //array of checkable actions that we can run/pass in this function as $check
        $checkable = array(
            'groups_allow_delete',
            'groups_allow_edit',
            'groups_allow_migrate',
            'groups_allow_change_permissions');

        //if no valie client id, return false
        if (! is_numeric($id) || ! in_array($check, $checkable)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [group_id=$group_id] OR [check=$check]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                            FROM groups 
                                            WHERE $check = 1
                                            AND groups_id = $id");
        $results = $query->num_rows(); //count rows

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
        if ($results == 1) {
            return true;
        } else {
            return false;
        }

    }

    // -- editGroup ----------------------------------------------------------------------------------------------
    /**
     * edit a single field record for a group
     * based on input of [file name] and new [field value]
     * 
     * @param  $group_id
     * @param  $field
     * @param  $new_value
     * @return	array
     */

    function editGroup($group_id = '', $field = '', $new_value = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate input
        if (! is_numeric($group_id) || $field == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [group_id=$group_id] OR [field=$field]", '');
            return false;
        }

        //check if field exists in database table
        if (! $this->db->field_exists($field, 'groups')) {
            $this->__debugging(__line__, __function__, 0, ": field ($field) not found", '');
            return false;
        }

        //escape data
        $new_value = $this->db->escape($new_value);
        $group_id = $this->db->escape($group_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
        $query = $this->db->query("UPDATE groups
                                          SET $field = $new_value
                                          WHERE groups_id = $group_id");

        $results = $this->db->affected_rows();

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
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }

    }

    // -- deleteGroup ----------------------------------------------------------------------------------------------
    /**
     * delete a group
     * 
     * @param $new_value
     * @return	array
     */

    function deleteGroup($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting group failed (group_id: $id is invalid)]");
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM groups
                                          WHERE groups_id = $id");

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
        if ($results === 1) {
            return true;
        } else {
            return false;
        }

    }

    // -- doesGroupExist ----------------------------------------------------------------------------------------------
    /**
     * checks if a group exists in the database
     * 
     * @param	string $name groups name  [age: users age]
     * @return	bool
     */

    function doesGroupExist($name = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if ($name == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [group name=$name]", '');
            return false;
        }

        //escape params items
        $name = $this->db->escape($name);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM groups 
                                          WHERE groups_name = $name");
        $results = $query->num_rows(); //count rows

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
        if ($results > 0) {
            return true;
        } else {
            return false;
        }
    }

    // -- addNewGroup ----------------------------------------------------------------------------------------------
    /**
     * add new group to the database
     * 
     * @param	string $name groups name
     * @return	bool
     */

    function addNewGroup($name = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if ($name == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [group name=$group_id]", '');
            return false;
        }

        //escape params items
        $name = $this->db->escape($name);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO groups (
                                          groups_name
                                          )VALUES(
                                          $name)");
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
        if ($results === 1) {
            return true;
        } else {
            return false;
        }
    }

}

/* End of file groups_model.php */
/* Location: ./application/models/groups_model.php */
