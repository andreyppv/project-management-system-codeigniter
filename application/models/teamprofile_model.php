<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class TeamProfile_model extends Super_Model
{

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

    // -- checkLogins ----------------------------------------------------------------------------------------------
    /**
     * checks email and password against database records.
     * returns row of team profile or returns false;
     * @return	mixed (table row / false)
     */

    public function checkLogins($team_profile_id = 0)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //mysq protection
        //$email = $this->db->escape($this->input->post('email'));
        $email = $this->input->post('email');
        //$password = $this->db->escape(md5($this->input->post('password')));
        $password = md5($this->input->post('password'));
        $team_profile_id = intval($team_profile_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //fetch row
        /*
        $query = $this->db->query("SELECT team_profile.*, groups.*
                                            FROM team_profile
                                            LEFT OUTER JOIN groups
                                            ON groups.groups_id = team_profile.team_profile_groups_id
                                            WHERE team_profile_email = $email
                                            AND team_profile_password = $password");
        */
        $this->db->select('*');
        $this->db->from('team_profile');
        $this->db->join('groups', 'groups.groups_id = team_profile.team_profile_groups_id', 'left outer');
        if($team_profile_id)
        {
            $this->db->where('team_profile_id', $team_profile_id);
        }
        else
        {
            $this->db->where('team_profile_email', $email);
            $this->db->where('team_profile_password', $password);
        }
        $query = $this->db->get();

        $results = $query->row_array();

        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');
        //----------sql & benchmarking start----------

        //debug data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);

        //__________RESULT__________
        if ($query->num_rows() > 0) {
            return $results;
        } else {
            return false;
        }

    }

    // -- checkRecordExists ----------------------------------------------------------------------------------------------
    /**
     * checks if a record exists, by checking against a single field and value
     * e.g. check is record exists, where team_profile_email = 'email@domain.com'
     *
     * 
     * @param	string
     * @return	bool
     */

    public function checkRecordExists($field = '', $value = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //check data is valid
        if (!regex_is_az123dashes($field) || $value == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [field=$field] [value=$value]", '');
            return false;
        }

        //mysq protection
        $value = $this->db->escape($value);
        $field = str_replace("'", "", $this->db->escape($field)); //remove '' added by db->escape()

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //fetch row
        $query = $this->db->query("SELECT * FROM team_profile 
                                            WHERE $field = $value
                                            LIMIT 1");
        $results = $query->num_rows();

        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');
        //----------sql & benchmarking start----------

        //debug data
        $this->__debugging(__line__, __function__, 0, 'teamprofile_model', $results);

        //__________RESULT__________
        return $results;

    }

    // -- resetPasswordSetup ----------------------------------------------------------------------------------------------
    /**
     * adds a unique code to user profile which is used to verify reset password link
     * adds a timestamp to allow the link expire
     * 
     * @param	string
     * @return	bool
     */

    public function resetPasswordSetup($email = '', $random_code)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //check data is valid
        if ($email == '' || $random_code == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [email=$email] [code=$random_code]", '');
            return false;
        }

        //mysq protection
        $email = $this->db->escape($email);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //update row
        $query = $this->db->query("UPDATE team_profile SET 
                                          team_profile_reset_code = '$random_code',
                                          team_profile_reset_timestamp = NOW()
                                          WHERE team_profile_email = $email");
        $results = $this->db->affected_rows();

        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');
        //----------sql & benchmarking end----------

        //debug data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);

        //__________RESULT__________
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }

    }

    // -- resetPasswordCheckCode ----------------------------------------------------------------------------------------------
    /**
     * checks if a valid reset code has been provided
     * returns true on valid
     *
     * 
     * @param	string
     * @return	bool
     */

    public function resetPasswordCheckCode($reset_code = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //mysql security
        $reset_code = $this->db->escape($reset_code);

        //return if no code provided
        if ($reset_code == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [reset_code=$reset_code]", '');
            return false;
        }

        //----------sql & benchmarking start----------[check reset code]
        $this->benchmark->mark('code_start');

        //check validity of resetcode
        $query = $this->db->query("SELECT * FROM team_profile
                                            WHERE team_profile_reset_timestamp >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                                            AND team_profile_reset_code = $reset_code");
        $results = $query->num_rows();

        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debug data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking start----------

        //_____RESULT_____
        if ($results > 0) {
            return true;
        } else {
            return false;
        }

    }

    // -- resetPassword ----------------------------------------------------------------------------------------------
    /**
     * lets try to reset the users password
     * returns true on valid
     *
     * 
     * @param	string
     * @return	bool
     */

    public function resetPassword()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //mysq protection
        $reset_code = $this->db->escape($this->input->post('resetcode'));
        $new_password = md5($this->input->post('new_password')); //md5 is good enough to "esacpe" this input

        //check code & password is not empty
        if ($reset_code == '' || $new_password == '') {

            //ERROR-LOG::
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Expected variable reset_code & new_password missing]");
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //check validity of resetcode
        $query = $this->db->query("UPDATE team_profile SET 
                                              team_profile_password = '$new_password',
                                              team_profile_reset_code = '',
                                              team_profile_reset_timestamp = ''
                                            WHERE team_profile_reset_code = $reset_code");
        $results = $this->db->affected_rows();

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debug data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking start-----------

        //return results
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }

    }

    // -- registerLastActive ----------------------------------------------------------------------------------------------
    /**
     * logs the datetime when team member was last active
     * @return	bool
     */

    public function registerLastActive($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //escape data
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE team_profile
                                            SET team_profile_last_active = NOW() 
                                            WHERE team_profile_id = $id");

        $results = $this->db->affected_rows(); //affected rows

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

    // -- retrieveLastActive ----------------------------------------------------------------------------------------------
    /**
     * retrieves the datetime when team member was last active
     * @return	bool
     */

    public function retrieveLastActive($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$group_id]", '');
            return false;
        }

        //escape data
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT team_profile_last_active
                                            FROM team_profile 
                                            WHERE team_profile_id = $id");
        $results = $query->row_array(); //single row array
        $last_active = $results['team_profile_last_active'];

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $last_active;

    }

    // -- searchTeamMembers ----------------------------------------------------------------------------------------------
    /**
     * search team profile database and return results for all matching team members as array
     * @return	array
     */

    public function searchTeamMembers($offset = 0, $type = 'search', $group_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //conditional sql
        //determine if any search condition where passed in the search form
        //actual post data is already cached, so use that instead of $_post
        if ($this->input->get('team_profile_full_name')) {
            $team_profile_full_name = str_replace("'", "", $this->db->escape($this->input->get('team_profile_full_name')));
            $conditional_sql .= " AND team_profile.team_profile_full_name LIKE '%$team_profile_full_name%'";
        }
        if ($this->input->get('groups_name')) {
            $groups_name = str_replace("'", "", $this->db->escape($this->input->get('groups_name')));
            $conditional_sql .= " AND groups.groups_name LIKE '%$groups_name%'";
        }
        if ($this->input->get('team_profile_email')) {
            $team_profile_email = $this->db->escape($this->input->get('team_profile_email'));
            $conditional_sql .= " AND team_profile.team_profile_email = $team_profile_email";
        }

        //has a group_id been provided
        if (is_numeric($group_id)) {
            $conditional_sql .= " AND groups.groups_id = '$group_id'";
        }

        //create the order by sql additional condition
        //these sorting variables are passed in the url and must be same as used in controller.
        $sort_order = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_columns = array(
            'sortby_profileid' => 'team_profile.team_profile_id',
            'sortby_group' => 'groups.groups_name',
            'sortby_fullname' => 'team_profile.team_profile_full_name');
        $sort_by = (array_key_exists('' . $this->uri->segment(6), $sort_columns)) ? $sort_columns[$this->uri->segment(6)] : 'team_profile.team_profile_id';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT team_profile.*,
                                          groups.*
                                          FROM team_profile
                                            LEFT OUTER JOIN groups
                                            ON groups.groups_id = team_profile.team_profile_groups_id
                                          WHERE 1 = 1
                                          $conditional_sql
                                          $sorting_sql
                                          $limiting");
        //results (search or rows)
        //rows are used by pagination class & results are used by tbs block merge
        if ($type == 'search') {
            $results = $query->result_array();
        } else {
            $results = $query->num_rows();
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

    // -- allTeamMembers ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of team members in table
     * accepts order_by and asc/desc values
     *
     * @usedby  various [mainly for producing pulldown lists]
     * 
     * @param	string
     * @return	array
     */

    public function allTeamMembers($orderby = 'team_profile_full_name', $sort = 'ASC', $group_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //check if any specifi ordering was passed
        if (!$this->db->field_exists($orderby, 'team_profile')) {
            $orderby = 'team_profile_full_name';
        }

        //check if sorting type was passed
        $sort = ($sort == 'asc' || $sort == 'desc') ? $sort : 'ASC';

        //CLIENT-PANEL: check if client id has been provided
        if (is_numeric($group_id)) {
            $conditional_sql .= " AND team_profile.team_profile_groups_id = '$group_id'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM team_profile
                                          WHERE 1=1
                                          $conditional_sql
                                          ORDER BY $orderby $sort");

        $results = $query->result_array(); //multi row array

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }

    // -- groupMembersCount ----------------------------------------------------------------------------------------------
    /**
     * counts the number of team members in a group
     *
     * 
     * @param	string
     * @return	numeric [number of rows]
     */

    public function groupMembersCount($group_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //check valid group id
        if (!is_numeric($group_id)) {
            return 0;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM team_profile
                                          WHERE team_profile_groups_id = '$group_id'");

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
        return $results;

    }

    // -- teamMemberDetails ----------------------------------------------------------------------------------------------
    /**
     * load all team members details based on team_profile_id
     * @return	array
     */

    public function teamMemberDetails($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            return false;
        }

        //escape data
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT team_profile.*, groups.*
                                            FROM team_profile
                                            LEFT OUTER JOIN groups
                                            ON team_profile.team_profile_groups_id = groups.groups_id
                                            WHERE team_profile.team_profile_id = $id");

        $results = $query->row_array();

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }

    // -- getDetailsByEmail ----------------------------------------------------------------------------------------------
    /**
     * load all team members details based on their emaila address.
     * @return	array
     */

    public function getDetailsByEmail($email = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if ($email == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [email=$email]", '');
            return false;
        }

        //escape data
        $email = $this->db->escape($email);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT team_profile.*, groups.*
                                            FROM team_profile
                                            LEFT OUTER JOIN groups
                                            ON team_profile.team_profile_groups_id = groups.groups_id
                                            WHERE team_profile.team_profile_email = $email");

        $results = $query->row_array();

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }

    // -- updateTeamMembersDetails ----------------------------------------------------------------------------------------------
    /**
     * update team members details, field by field. Input is normaly coming from Modal/Ajax (editable)
     * as selected by team_profile_id.
     * returns false or true
     *
     * @usedby  Many
     * 
     * @param	mixed
     * @return	bool
     */

    public function updateTeamMembersDetails($team_profile_id = '', $field = '', $new_value = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no value client id, return false
        if (!is_numeric($team_profile_id) || $field == '') {
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: missing data (team_profile_id or field)]");
            return false;
        }

        //check if field exists in database table
        if (!$this->db->field_exists($field, 'team_profile')) {
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: field (field) not found]");
            //return
            return false;
        }

        //md5 password
        if ($field == 'team_profile_password') {
            $new_value = $this->db->escape(md5($new_value));
        } else {
            $new_value = $this->db->escape($new_value);
        }

        //escape data
        $team_profile_id = $this->db->escape($team_profile_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE team_profile
                                          SET $field = $new_value
                                          WHERE team_profile_id = $team_profile_id");

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

    // -- addTeamMembers ----------------------------------------------------------------------------------------------
    /**
     * Add a new team member to the database.
     * Inout from POST
     * @return	new record id
     */

    public function addTeamMembers()
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //get all post data and escape it

        foreach ($_POST as $key => $value) {
            $$key = $value;//$this->db->escape($this->input->post($key));
        }
        
        $team_profile_full_name = str_replace("Â", " ", $team_profile_full_name);
        $team_profile_full_name = str_replace("  ", " ", $team_profile_full_name);

        //md5 password
        $password = $this->input->post('team_profile_password');
        $team_profile_password = md5($password);

        //unique user code
        $team_profile_uniqueid = random_string('alnum', 20);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        $rand = mt_rand(999,99999) . mt_rand(999, 99999);
        $name_ = explode(' ', $team_profile_full_name);

        $first_name = $name_[0];
        $last_name = $name_[1];

        if(empty($last_name)){
            $first_name = $team_profile_full_name;
            $last_name = '.';
        }

        /*$createStaffInstance = createStaff(array(

          "staff" => array(
            "first_name" => $first_name,
            "last_name" => $last_name,
            "email" => $team_profile_email,
            "username" => str_replace(" ", "", $team_profile_full_name) . $rand,
            "password" => $password,
            "email_credentials" => 1,
            "country" => "",
            "state" => "",
            "city" => "",
            "street1" => "",
            "street2" => "",
            "code" => "",
            "home_phone" => $team_profile_telephone,
            "business_phone" => $team_profile_telephone,
            "mobile_phone" => $team_profile_telephone,
            "fax" => "",
            "rate" => $team_profile_hourlyrate,
            "projects" => array()
          )

          ));*/
        //$staffid = $createStaffInstance[1]['staff_id'];
        //_____SQL QUERY_______
        $this->db->insert('team_profile',array(
            'team_profile_groups_id'          => $team_profile_groups_id,
            'team_profile_full_name'          => $team_profile_full_name,
            'team_profile_job_position_title' => $team_profile_job_position_title,
            'team_profile_email'              => $team_profile_email,
            'team_profile_password'           => $team_profile_password,
            'team_profile_telephone'          => $team_profile_telephone,
            'team_profile_uniqueid'           => $team_profile_uniqueid,
            'skills'                          => $skills,
            'hourlyrate'                      => $team_profile_hourlyrate
        ));

        $results = $this->db->insert_id(); //new team members id's (last insert item)

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

    // -- isEmailAlreadyInuse ----------------------------------------------------------------------------------------------
    /**
     * checks if a team members with same email aready exists.
     * email addresses are used during login and so are expected to be unique for each team member
     * @return	bool
     */

    public function isEmailAlreadyInuse($email = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if ($email == '') {
            return false;
        }

        //escape data
        $email = $this->db->escape($email);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                            FROM team_profile 
                                            WHERE team_profile_email = $email");
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
        if ($results == 0) {
            return true;
        } else {
            return false;
        }

    }

    // -- migrateMembers ----------------------------------------------------------------------------------------------
    /**
     * migrate group members from one group to another
     * @return	array
     */

    public function migrateMembers($old_group_id = '', $new_group_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($old_group_id) || !is_numeric($new_group_id)) {
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE team_profile
                                          SET team_profile_groups_id = $new_group_id
                                          WHERE team_profile_groups_id = $old_group_id");

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

    // -- updateAvatar ----------------------------------------------------------------------------------------------
    /**
     * updates teammembers profile with new "file name" (complete with extension)
     *   when uploading an avatar for a team member
     *
     * 
     * @param numeric $id: client user id]
     * @param numeric $extension: avatar fle extenion (e.g. 'png']
     * @return	bool
     */

    public function updateAvatar($id = '', $filename = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id) || $filename == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$group_id or extension:$filename]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);
        $filename = $this->db->escape($filename);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE team_profile
                                          SET team_profile_avatar_filename = $filename
                                          WHERE team_profile_id = $id");

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
        if ($results > 0 || $transaction_result === true) {
            return true;
        } else {
            return false;
        }
    }

    // -- mailingListAdmin ----------------------------------------------------------------------------------------------
    /**
     * return an array of system notification enabled admins, useful for sending out system notifications
     *
     * 
     * @param   void
     * @return	array
     */

    public function mailingListAdmin()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM team_profile 
                                          WHERE team_profile_groups_id = 1
                                          AND team_profile_notifications_system = 'yes'");

        $results = $query->result_array();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //eturn
        return $results;

    }

    // -- deleteTeamMember ----------------------------------------------------------------------------------------------
    /**
     * delete a team member
     *
     * 
     * @param	string [name: groups name], [age: users age]
     * @return	array
     */

    public function deleteTeamMember($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM team_profile
                                          WHERE team_profile_id = $id");

        $results = $this->db->affected_rows(); //affected rows

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

    // -- notificationsEmail ----------------------------------------------------------------------------------------------
    /**
     * get team members email address for sending notifications
     * returns false is the team members has disabled system notifications
     * @return	string email address
     */

    public function notificationsEmail($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            return false;
        }

        //escape data
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * FROM team_profile
                                            WHERE team_profile_id = $id
                                            AND team_profile_notifications_system = 'yes'");

        $results = $query->row_array();
        $results = $results['team_profile_email'];

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if ($results != '') {
            return $results;
        } else {
            return false;
        }
    }



    // -- calcHoursPaid ----------------------------------------------------------------------------------------------
    /**
     * calculate hours and paid
     * @return  string email address
     */

    public function calcHoursPaid($team_profile_id, $hourlyrate = 0)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape data
        $team_profile_id = intval($team_profile_id);
        $hourlyrate = floatval($hourlyrate);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $data = array(
            'uncompleted_hrs' => 0,
            'completed_hrs'   => 0,
            'total_unpaid'    => 0,
        );

        //Uncompleted assigned hrs
        $this->db->select('SUM(estimatedhours) uncompleted_hrs')->
                   from('tasks')->
                   where('tasks_assigned_to_id', $team_profile_id)->
                   where("tasks_status != 'completed'");
        $row = $this->db->get()->row_array();
        if($row) $data['uncompleted_hrs'] = $row['uncompleted_hrs'];

        //Completed hours
        $this->db->select('SUM(estimatedhours) completed_hrs')->
                   from('tasks')->
                   where('tasks_assigned_to_id', $team_profile_id)->
                   where("tasks_status = 'completed'");
        $row = $this->db->get()->row_array();
        if($row) $data['completed_hrs'] = $row['completed_hrs'];

        //Total unpaid
        $data['total_unpaid'] = round($data['completed_hrs'] * $hourlyrate, 2);

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $data;
    }
}

/* End of file teamprofile_model.php */
/* Location: ./application/models/teamprofile_model.php */
