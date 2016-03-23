<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all files related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Files_model extends Super_Model
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

    // -- searchFiles ----------------------------------------------------------------------------------------------
    /**
     * search files for a particular project or for searched files (can be any project etc)
     * @return  array
     */

    public function searchFiles($offset = 0, $type = 'search', $project_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //if project_id has been specified, show only for this project
        if (is_numeric($project_id)) {
            $conditional_sql .= " AND files.files_project_id = $project_id";
        }

        //create the order by sql additional condition
        //these sorting keys are passed in the url and must be same as the ones used in the controller.
        $sort_order = ($this->uri->segment(6) == 'asc') ? 'asc' : 'desc'; //reverse order of normal desc as default
        $sort_columns = array(
            'sortby_fileid' => 'files.files_id',
            'sortby_filename' => 'files.files_name',
            'sortby_projectid' => 'files.files_project_id',
            'sortby_downloads' => 'files.download_count',
            'sortby_filetype' => 'files.files_extension',
            'sortby_uploadedby' => 'files.files_uploaded_by_id',
            'sortby_date' => 'files.files_date_uploaded',
            'sortby_size' => 'files.files_size');
        $sort_by = (array_key_exists(''.$this->uri->segment(7), $sort_columns)) ? $sort_columns[$this->uri->segment(7)] : 'files.files_id';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT files.*, client_users.*, team_profile.*,
                                          (SELECT COUNT(filedownloads_id) FROM filedownloads
                                                  WHERE filedownloads.filedownloads_file_id = files.files_id) AS downloads_count,
                                          (SELECT COUNT(file_comments_id) FROM file_comments
                                                  WHERE file_comments.file_comments_file_id = files.files_id) AS comments_count
                                          FROM files
                                          LEFT OUTER JOIN client_users
                                               ON client_users.client_users_id = files.files_uploaded_by_id
                                               AND files.files_uploaded_by = 'client'
                                          LEFT OUTER JOIN team_profile
                                               ON team_profile.team_profile_id = files.files_uploaded_by_id
                                               AND files.files_uploaded_by = 'team'
                                          WHERE 1 = 1
                                          AND files_active=1
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

    public function getTaskFiles($taskid)
    {
         $query = $this->db->query("SELECT files.*,team_profile.team_profile_full_name FROM files JOIN team_profile ON files.files_uploaded_by_id = team_profile.team_profile_id WHERE files_task_id=$taskid");
         $results = $query->result_array();
          $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }
         return $results;
    }
    
    public function getProjectFiles($id)
    {
         $query = $this->db->query("SELECT files.*, client_users.*, team_profile.*,
                                          (SELECT COUNT(filedownloads_id) FROM filedownloads
                                                  WHERE filedownloads.filedownloads_file_id = files.files_id) AS downloads_count,
                                          (SELECT COUNT(file_comments_id) FROM file_comments
                                                  WHERE file_comments.file_comments_file_id = files.files_id) AS comments_count
                                          FROM files
                                          LEFT OUTER JOIN client_users
                                               ON client_users.client_users_id = files.files_uploaded_by_id
                                               AND files.files_uploaded_by = 'client'
                                          LEFT OUTER JOIN team_profile
                                               ON team_profile.team_profile_id = files.files_uploaded_by_id
                                               AND files.files_uploaded_by = 'team'
                                         WHERE
                                          files_project_id=$id 
                                        ORDER BY files.files_id DESC");
        
        
        
        
//       $query = $this->db->query("SELECT files.*,team_profile.team_profile_full_name 
//                                      FROM files 
//                                      JOIN team_profile 
//                                          ON files.files_uploaded_by_id = team_profile.team_profile_id 
//                                      WHERE 
//                                      files_project_id=$id 
//                                      ORDER BY files.files_id DESC");
         $results = $query->result_array();
          $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }
         return $results;
    }

    // -- addFile ----------------------------------------------------------------------------------------------
    /**
     * add project file to database from post data
     *   
     * @return  numeric [insert id]
     */

    public function addFile($file = array())
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');


        //----------share link------------------------
        $share = '';
        while ($share == '')
        {
            $share = random_string('alnum', 12);
            $row = $this->db->query("select 1 from files where files_share_link='".$share."'")->row();
            if($row)
            {
                $share = '';
            }
        }   

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $this->db->set('files_date_uploaded', 'NOW()', FALSE);
        $this->db->set('files_time_uploaded', 'NOW()', FALSE);
        $this->db->insert('files', array(
                'files_client_id'      => $file['client_id'],
                'files_description'    => $file['description'],
                'files_events_id'      => $file['events_id'],
                'files_extension'      => $file['extension'],
                'files_foldername'     => $file['foldername'],
                'files_name'           => $file['name'],
                'files_show_name'      => $file['show_name'],
                'files_project_id'     => $file['project_id'],
                'files_uploaded_by'    => $file['uploaded_by'],
                'files_uploaded_by_id' => $file['uploaded_by_id'],
                'files_size'           => $file['size'],
                'files_size_human'     => $file['size_human'],
                'files_share_link'     => $share
            ));

        $results = $this->db->insert_id(); //last item insert id

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

    // -- superUsers ----------------------------------------------------------------------------------------------
    /**
     * return a array of all users who have edit/delete access for this file
     *
     * @param numeric $file_id
     * @return  array
     */

    public function superUsers($file_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric(files_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [file id=$file_id]", '');
            return false;
        }

        //escape params items
        $file_id = $this->db->escape($file_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT files.*, projects.*
                                          FROM files 
                                          LEFT OUTER JOIN projects
                                          ON projects.projects_id = files.files_project_id
                                          WHERE files_id = $file_id");

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

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //create array of users id's
        $users = array($results['files_uploaded_by_id'], $results['projects_team_lead_id']);
        return $users;
    }

    // -- editFile ----------------------------------------------------------------------------------------------
    /**
     * edit a files details
     *
     * @param   void
     * @return  numeric [affected rows]
     */

    public function editFile()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if task id value exists in the post data
        if (! is_numeric($this->input->post('files_id'))) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [file id: is not numeric/is unavailable]", '');
            return false;
        }

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE files
                                          SET 
                                          files_description = $files_description
                                          WHERE files_id = $files_id");

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

    // -- editName ----------------------------------------------------------------------------------------------
    /**
     * edit a files name
     *
     * @param   void
     * @return  numeric [affected rows]
     */

    public function editName()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if task id value exists in the post data
        if (! is_numeric($this->input->post('files_id'))) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [file id: is not numeric/is unavailable]", '');
            return false;
        }

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }
        
        
        
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE files
                                          SET 
                                          files_show_name = $files_name
                                          WHERE files_id = $files_id");

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

    // -- deleteFile ----------------------------------------------------------------------------------------------
    /**
     * delete a file(s) based on a 'delete_by' id
     *
     * @param numeric   $id reference id of item(s) 
     * @param   string    $delete_by file-id, milestone-id, project-id, client-id 
     * @return  bool
     */

    public function deleteFile($id = '', $delete_by = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [file_id=$id]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting file(s) failed (file_id: $id is invalid)]");
            return false;
        }

        //check if delete_by is valid
        $valid_delete_by = array(
            'file-id',
            'project-id',
            'client-id');

        if (! in_array($delete_by, $valid_delete_by)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [delete_by=$delete_by]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting file(s) failed (delete_by: $delete_by is invalid)]");
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //conditional sql
        switch ($delete_by) {

            case 'file-id':
                $conditional_sql = "AND files_id = $id";
                break;

            case 'project-id':
                $conditional_sql = "AND files_project_id = $id";
                break;

            case 'client-id':
                $conditional_sql = "AND files_client_id = $id";
                break;

            default:
                $conditional_sql = "AND files_id = '0'"; //safety precaution else we wipe out whole table
                break;

        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM files
                                          WHERE 1 = 1
                                          $conditional_sql");

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

    // -- getFile ----------------------------------------------------------------------------------------------
    /**
     * return a single files record based on its ID
     *
     * @param numeric $id
     * @return  array
     */

    public function getFile($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [file id=$id]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT files.*, client_users.*, team_profile.*,
                                             (SELECT COUNT(filedownloads_file_id)
                                                     FROM filedownloads
                                                     WHERE filedownloads.filedownloads_file_id = files.files_id)
                                                     AS downloads
                                          FROM files
                                          LEFT OUTER JOIN client_users
                                               ON client_users.client_users_id = files.files_uploaded_by_id
                                               AND files.files_uploaded_by = 'client'
                                          LEFT OUTER JOIN team_profile
                                               ON team_profile.team_profile_id = files.files_uploaded_by_id
                                               AND files.files_uploaded_by = 'team'
                                          WHERE files.files_id = $id");

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

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }


    // -- getFileShareLink ----------------------------------------------------------------------------------------------
    /**
     * return a single files record based on its share link
     *
     * @param numeric $id
     * @return  array
     */

    public function getFileShareLink($share_link = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!$share_link) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [file id=$id]", '');
            return false;
        }

        //escape params items
        $share_link = $this->db->escape($share_link);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * FROM files WHERE files_share_link = $share_link");

        $results = $query->row_array(); //single row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }


    // -- downloadCounter ----------------------------------------------------------------------------------------------
    /**
     * increase the files download count by 1
     *
     * @param numeric $id
     * @return  numeric [insert id]
     */
    public function downloadCounter($id)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [file id=$id]", '');
            return false;
        }

        //escape params items
        $my_id = $this->data['vars']['my_id'];
        $my_user_type = $this->data['vars']['my_user_type'];
        $project_id = $this->data['vars']['project_id'];
        $client_id = $this->data['vars']['client_id'];

        //validate data
        if (! is_numeric($id) || ! is_numeric($my_id) || ! is_numeric($project_id) || ! is_numeric($client_id) || $my_user_type == '') {
            //log this
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Download counter failed. Invalid input data]");
            //return
            return;
        }

        //escape data
        $id = $this->db->escape($id);
        $my_id = $this->db->escape($my_id);
        $my_user_type = $this->db->escape($my_user_type);
        $project_id = $this->db->escape($project_id);
        $client_id = $this->db->escape($client_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO filedownloads (
                                          filedownloads_project_id,
                                          filedownloads_client_id,
                                          filedownloads_file_id,
                                          filedownloads_date,
                                          filedownloads_user_type
                                          )VALUES(
                                          $project_id,
                                          $client_id,
                                          $id,
                                          NOW(),
                                          $my_user_type)");

        $results = $this->db->insert_id(); //last item insert id

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

    // -- validateClientOwner ----------------------------------------------------------------------------------------------
    /**
     * confirm if a given client owns this requested item
     *
     * @param numeric $resource_id
     * @param   numeric $client_id
     * @return  bool
     */

    public function validateClientOwner($resource_id = '', $client_id)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($resource_id) || ! is_numeric($client_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Input Data", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM files 
                                          WHERE files_id = $resource_id
                                          AND files_client_id = $client_id");

        $results = $query->num_rows(); //count rows

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

    // -- bulkDelete ----------------------------------------------------------------------------------------------
    /**
     * bulk delete based on list of project ID's
     * typically used when deleting project/s 
     *
     * @param   string $projects_list a mysql array/list formatted projects list] [e.g. 1,2,3,4]
     * @return  bool
     */

    public function bulkDelete($projects_list = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //flow control
        $next = true;

        //sanity check - ensure we have a valid projects_list, with only numeric id's
        $lists = explode(',', $projects_list);
        for ($i = 0; $i < count($lists); $i++) {
            if (! is_numeric(trim($lists[$i]))) {
                //log error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting files, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM files
                                          WHERE files_project_id IN($projects_list)");
        }
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
}

/* End of file files_model.php */
/* Location: ./application/models/files_model.php */
