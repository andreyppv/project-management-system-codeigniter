<?php

class Ajax extends MY_Controller
{

    // --  constructor- -------------------------------------------------------------------------------------------------------
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //reduce error reporting to only critical
        @error_reporting(E_ERROR);

        //turn off codeigniter profiler (which may be on in MY_Controller) during debug mode
        $this->output->enable_profiler(false);

    }

    // -- index -------------------------------------------------------------------------------------------------------
    /**
     * This is our re-routing function and is the inital function called
     *
     * @access	public
     * @param	void
     * @return void
     */
    function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {

            case 'validation-is-user-email-in-use':
                $this->__validationIsEmailInUse('client_user');
                break;

            case 'validation-is-team-email-in-use':
                $this->__validationIsEmailInUse('team_member');
                break;

            case 'editable-user-profile':
                $this->__editableUserProfile();
                break;

            case 'delete-client-user':
                $this->__deleteClientUser();
                break;

            case 'upload-ticket-file':
                $this->__uploadTicketsFile();
                break;

            case 'upload-project-file':
                $this->__uploadProjectFile();
                break;

            case 'delete-project-file':
                $this->__deleteProjectFile();
                break;

            case 'delete-task-viewer':
                $this->__deleteTaskViewer();
                break;

            case 'upload-avatar':
                $this->__uploadAvatar();
                break;
        }

        //log debug data
        $this->__ajaxdebugging();

    }

    // -- __isEmailAlreadyInUse- -------------------------------------------------------------------------------------------------------
    /**
     * used to validate if email is aready in use, when a new user is being added. (both team members and client users)
     * normally used during jquery.validation.js email field validation
     *
     * @access	private
     * @javascript jquery.validation.js
     * @param	string: $type team/user
     * @return	bool
     */

    function __validationIsEmailInUse($type = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //get email from jquery.validater.js ajax call & load model
        //check if email is already in use
        if ($type == 'client_user') {
            $this->load->model('users_model');
            $email = $this->input->post('client_users_email');
            $result = $this->users_model->isEmailAlreadyInuse($email);
            $this->data['debug'][] = $this->users_model->debug_data;
        }
        if ($type == 'team_member') {
            $this->load->model('teamprofile_model');
            $email = $this->input->post('team_profile_email');
            $result = $this->teamprofile_model->isEmailAlreadyInuse($email);
            $this->data['debug'][] = $this->teamprofile_model->debug_data;
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view
        if (!$result) {
            echo 'false';
        } else {
            echo 'true';
        }

    }

    // -- __editableUserProfile- -------------------------------------------------------------------------------------------------------
    /**
     * edit user profile via inline editable
     */

    function __editableUserProfile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //load models
        $this->load->model('users_model');

        //collect data sent by editable.js
        $id = $this->input->post('pk'); //client_users_id
        $name = $this->input->post('name');
        $value = $this->input->post('value');

        /*CHECK PERMISSIONS **/
        if ($this->data['vars']['my_user_type'] == 'client') {
            if (!$this->permissions->usersEdit($id)) {

                echo $this->data['lang']['lang_permission_denied'];
                header('HTTP/1.0 400 Bad Request', true, 400);

                //halt
                $next = false;

                //log this
                log_message('debug', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: " . $this->permissions->debug_data . "]");
                //exit
                return;
            }
        }

        /*CHECK PERMISSIONS **/
        if ($this->data['vars']['my_user_type'] == 'team') {
            if (!$this->data['permissions']['edit_item_clients'] != 1) {

                echo $this->data['lang']['lang_permission_denied'];
                header('HTTP/1.0 400 Bad Request', true, 400);

                //halt
                $next = false;

                //log this
                log_message('debug', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: " . $this->permissions->debug_data . "]");
                //exit
                return;
            }

        }

        //form validation - create array of required form fields
        //determin any required fields for optional fields and merge
        $required = array(
            'client_users_full_name',
            'client_users_job_position_title',
            'client_users_email',
            'client_users_password',
            'client_users_telephone',
            'client_users_main_contact');

        //form validate required fields
        if (in_array($name, $required) && $value == '') {
            $next = false;
            echo $this->data['lang']['lang_item_is_required'];
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //input validation - email field
        if ($next && $name == 'client_users_email' && !is_email_address($value)) {
            $next = false;
            echo $this->data['lang']['lang_invalid_email'];
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //input validation - password field
        if ($next && $name == 'client_users_password' && !is_strong_password($value)) {
            $next = false;
            echo $this->data['lang']['lang_password_must_be_at_least_eight'];
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //if updating email, check that it is unique
        if ($next && $name == 'client_users_email') {
            if ($this->users_model->checkRecordExists('client_users_email', $value) > 0) {
                $this->data['debug'][] = $this->users_model->debug_data;
                $next = false;
                echo $this->data['lang']['lang_password_must_be_at_least_eight'];
                header('HTTP/1.0 400 Bad Request', true, 400);
            }
        }

        //update record & return http status
        if ($next) {

            //run update sql (for client primary user update)
            if ($name == 'client_users_main_contact') {
                //get the client_id for the user
                $result = $this->users_model->userDetails($id);
                $client_id = $result['client_users_clients_id'];
                //now update
                $update = $this->users_model->updatePrimaryContact($client_id, $id);
                $this->data['debug'][] = $this->users_model->debug_data;
            } else {
                //run any other update to the form
                $update = $this->users_model->updateUserDetails($id, $name, $value);
                $this->data['debug'][] = $this->users_model->debug_data;
            }

            //log debug data
            $this->__ajaxdebugging();

            //check if update was successful
            if ($update) {
                header('HTTP/1.0 200 OK', true, 200);
            } else {
                echo 'Error saving data';
                header('HTTP/1.0 400 Bad Request', true, 400);
            }
        }
    }

    // -- __deleteClientUser- -------------------------------------------------------------------------------------------------------
    /**
     * delete a client user
     */
    function __deleteClientUser()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //load models
        $this->load->model('users_model');

        //get the post data
        $id = $this->input->post('data_mysql_record_id');

        //is this being done via client side
        $client_id = $this->session->userdata('client_users_clients_id');

        /*CHECK PERMISSIONS **/
        if (!$this->permissions->usersDelete($id)) {

            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->permissions->reason,
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);

            //halt
            $next = false;
        }

        //is this being done via admin side - Administrator only
        if ($next && is_numeric($this->session->userdata('team_profile_id'))) {
            if ($this->data['vars']['my_group'] != 1) {
                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_permission_denied_info'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }
        }
        
        //is this being done via admin side
        if ($next) {
            if (is_numeric($this->session->userdata('team_profile_id'))) {

                //get client id from form post
                $client_id = $this->input->post('data_mysql_record_id2');

            }

            //sanity check
            if (!is_numeric($id) || !is_numeric($client_id)) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                $next = false;
            }

        }

        //check if this is primary contact
        if ($next) {
            if ($this->users_model->isPrimaryContact($id)) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_you_cannot_delete_primary_contact'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                $this->data['debug'][] = $this->users_model->debug_data;
                $next = false;
            }
        }

        //delete user
        if ($next) {
            if ($result = $this->users_model->deleteUser($id, $client_id)) {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);
            } else {
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                $next = false;
            }
            $this->data['debug'][] = $this->users_model->debug_data;
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');

    }

    // -- __loggedInCheck- -------------------------------------------------------------------------------------------------------
    /**
     * checks if user is logged in
     */

    function __loggedInCheck()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //-----set for admin------------------------
        if (!is_numeric($this->data['vars']['my_id'])) {
            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_session_timed_out'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);

            //log debug data
            $this->__ajaxdebugging();

            //load the view for json echo
            $this->__flmView('common/json');

            //now die and exit
            die('Session timed out - Please login again');
        }

    }

    // -- __uploadTicketsFile- -------------------------------------------------------------------------------------------------------
    /**
     * handles ticket file uploads/attachments)
     *  - [frontend] : SimpleAjaxUploader.js | custom.uploadfile.js
     *  - [backend] : /libraries/Fileupload.php
     *  - files are uploaded into temp folder
     *  - uploaded files are renamed with unique id
     *  - return name of new file and the unique folder name
     *  - http headers:200 response for no errors, with jason data. [success = 1]
     *  - http headers:200 response for errors, with jason data. [success = 0]
     */

    function __uploadTicketsFile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //---START UPLOAD PROECSS--------------------------------------------------------------

        //some settings
        $this->data['field_name'] = 'uploadedfile';

        //set the input field name (same as in custom.upload*.js)
        $this->data['allowed_extensions'] = $this->__uploadAllowedFileTypes();

        /*
        * destination folder
        * each file is saved in its own unique folder. This allows for multiple files with same name
        * [example] /home/files/temp/jdhy38risuee8w88/picture.jpg
        *           
        */
        $this->data['file_foldername'] = random_string('alnum', 20);
        $this->data['file_folder_path'] = FILES_TEMP_FOLDER . $this->data['file_foldername'];

        //start the upload
        $this->load->library('fileupload');
        $this->fileupload->allowedExtensions = $this->data['allowed_extensions'];
        $result = $this->fileupload->handleUpload($this->data['file_folder_path']);

        //some data about new file
        $filedata['upload_errors'] = $this->fileupload->getErrorMsg();
        $filedata['file_size'] = $this->fileupload->getFileSize();
        $filedata['file_name'] = $this->fileupload->getFileName();
        $filedata['file_extension'] = $this->fileupload->getExtension();
        $filedata['file_path'] = $this->fileupload->getSavedFile();
        $filedata['file_folder_path'] = $this->data['file_folder_path'];
        $filedata['file_foldername'] = $this->data['file_foldername'];

        //---END UPLOAD PROECSS------------------------------------------------------------------

        //Upload passed - continue
        if ($result) {

            //check that new file exists
            if (is_file($filedata['file_path'])) {

                //json
                $jsondata = array(
                    'success' => 1,
                    'message' => $this->data['lang']['lang_file_has_been_uploaded'],
                    'debug_line' => __line__);

            } else {

                //json
                $jsondata = array(
                    'success' => 0,
                    'message' => $this->data['lang']['lang_upload_system_error'],
                    'debug_line' => __line__);
                //log this error
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Error saving ticket image]");
            }

            //merge all data for json array
            $jsondata = array_merge($jsondata, $filedata);
            header('HTTP/1.0 200 OK', true, 200);

        }

        //Upload failed - delete folder
        if (!$result) {

            //unlink any folder that may have been created
            @unlink($filedata['file_path']);
            @rmdir($this->data['file_folder_path']);

            //what error message to show
            $message = ($filedata['upload_errors'] != '') ? $filedata['upload_errors'] : $this->data['lang']['lang_file_could_not_uploaded'];

            //create json array, with merge of file data
            $jsondata = array(
                'success' => 0,
                'message' => $message,
                'debug_line' => __line__);

            //merge with data from upload class
            $jsondata = array_merge($jsondata, $filedata);
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //set the json data
        $this->jsondata = $jsondata;

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __uploadAvatar- -------------------------------------------------------------------------------------------------------
    /**
     * handles avatar file uploads (both team member & client users)
     *  - [frontend] : SimpleAjaxUploader.js | custom.upload.avatars.js
     *  - [backend] : Fileupload.php
     *  - files are uploaded into temp folder
     *  - uploaded files are renamed to the users unique id
     *  - existing files in avatar folder with same name are deleted
     *  - uploaded file is 'copied' into avatar folder
     *  - profile database record is updated with new avatar file extension
     *  - http headers:200 response for no errors, with jason data. [success = 1]
     *  - http headers:200 response for errors, with jason data. [success = 0]
     *
     */

    function __uploadAvatar()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('users_model');
        $this->load->model('teamprofile_model');

        //is this team member or client user avatar upload
        $user_type = $this->uri->segment(4);

        //---START UPLOAD PROECSS--------------------------------------------------------------
        //some settings
        $this->data['field_name'] = 'myavatar'; //set the input field name
        $this->data['new_file_name'] = random_string('alnum', 40) . '.tmp';
        $this->data['allowed_extensions'] = array(
            'jpg',
            'jpeg',
            'png',
            'gif');

        //start the upload
        $this->load->library('fileupload');
        $this->fileupload->allowedExtensions = $this->data['allowed_extensions'];
        $this->fileupload->newFileName = $this->data['new_file_name'];
        $result = $this->fileupload->handleUpload(FILES_TEMP_FOLDER);

        //some data about new file
        $filedata['upload_errors'] = $this->fileupload->getErrorMsg();
        $filedata['file_size'] = $this->fileupload->getFileSize();
        $filedata['file_name'] = $this->fileupload->getFileName();
        $filedata['file_extension'] = $this->fileupload->getExtension();
        $filedata['file_path'] = $this->fileupload->getSavedFile();
        //---END UPLOAD PROECSS------------------------------------------------------------------

        //Upload passed - continue
        if ($result) {

            // store temp file as variable and delete the actual file
            $tempfile = read_file($filedata['file_path']);
            @unlink($filedata['file_path']);

            //deleting any existing avatar files for this ser
            $avatar_files = FILES_AVATARS_FOLDER . $this->data['vars']['my_unique_id'] . '.*';
            @array_map("unlink", glob($avatar_files));

            //save file to final destination,
            $new_file_name = $this->data['vars']['my_unique_id'] . '.' . $filedata['file_extension'];
            $new_file_path = FILES_AVATARS_FOLDER . $new_file_name;
            write_file($new_file_path, $tempfile);

            //check that new file exists & update database with new file extension
            if (file_exists($new_file_path)) {

                //json
                $jsondata = array('success' => 1, 'message' => $this->data['lang']['lang_file_has_been_uploaded']);

                //update database
                if ($user_type == 'team') {
                    $this->teamprofile_model->updateAvatar($this->data['vars']['my_id'], $new_file_name);
                    $this->data['debug'][] = $this->teamprofile_model->debug_data;
                }

                //update database
                if ($user_type == 'client') {
                    $update = $this->users_model->updateAvatar($this->data['vars']['my_id'], $new_file_name);
                    $this->data['debug'][] = $this->users_model->debug_data;
                    if (!$update) {
                        log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Error saving avatar image ($this->users_model->debug_data)]");
                    }
                }

            } else {

                //json
                $jsondata = array('success' => 0, 'message' => $this->data['lang']['lang_upload_system_error']);
                //log this error
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Error saving avatar image]");
            }

            //merge all data for json array
            $jsondata = array_merge($jsondata, $filedata);
            header('HTTP/1.0 200 OK', true, 200);

        }

        //Upload passed - continue
        if (!$result) {

            //what error message to show
            $message = ($filedata['upload_errors'] != '') ? $filedata['upload_errors'] : $this->data['lang']['lang_file_could_not_uploaded'];

            //create json array, with merge of file data
            $jsondata = array('success' => 0, 'message' => $message);
            //merge with data from upload class
            $jsondata = array_merge($jsondata, $filedata);
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //set the json data
        $this->jsondata = $jsondata;

        //debug
        $this->data['debug'][] = $this->users_model->debug_data;
        $this->data['debug'][] = $this->teamprofile_model->debug_data;

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __uploadAllowedFileTypes- -------------------------------------------------------------------------------------------------------
    /**
     * Generate an array of allowed file types from settings.php config
     */

    function __uploadAllowedFileTypes()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if allow all file types
        if ($this->config->item('files_tickets_max_size') === 0) {

            return array();
        }

        //explode array from settings.php config file
        $allowed = explode("|", $this->config->item('files_tickets_max_size'));

        //loop through and create new flat array of file types
        for ($i = 0; $i < count($allowed); $i++) {
            $file_extension = strtolower(trim(str_replace("'", '', $allowed[$i])));

            //if $file_extension is valid alphabetic
            if (ctype_alpha($file_extension) || ctype_alnum($file_extension)) {
                $allowed_array[] = $file_extension;
            }
        }

        return $allowed_array;

    }

    // -- __uploadProjectFile- -------------------------------------------------------------------------------------------------------
    /**
     * handles avatar file uploads (both team member & client users)
     *  - [frontend] : SimpleAjaxUploader.js | custom.upload.projectfiles.js
     *  - [backend] : /libraries/Fileupload.php
     *  - files are uploaded into temp folder
     *  - uploaded files are renamed to the users unique id
     *  - uploaded file is 'copied' into avatar folder
     *  - profile database record is updated with new avatar file extension
     *  - http headers:200 response for no errors, with jason data. [success = 1]
     *  - http headers:200 response for errors, with jason data. [success = 0]
     */

    public function __uploadProjectFile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('files_model');

        //is this team member or client user file upload
        $project_id = $this->uri->segment(4);
        $user_type = $this->uri->segment(5);

        /* CLIENT-RESOURCE-OWNERSHIP VALIDATION **/
        if ($this->data['vars']['my_user_type'] == 'client') {
            if (!in_array($project_id, $this->data['my_clients_project_array'])) {
                //exit
                return;
            }
        }

        //validate project id
        if (!is_numeric($project_id)) {

            $jsondata = array('success' => 0, 'message' => $this->data['lang']['lang_upload_system_error']);
            //log this error
            log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Project id is invalid]");

            return;
        }

        //---START UPLOAD PROECSS--------------------------------------------------------------
        //some settings
        $this->data['field_name'] = 'projectfile'; //set the input field name (same as in custom.upload*.js)
        $this->data['allowed_extensions'] = $this->__uploadProjectAllowedFileTypes();

        /*
        * destination folder
        * each file is saved in its own unique folder. This allows for multiple files with same name
        * [example] /home/files/project/project_id/jdhy38risuee8w88/picture.jpg
        *           /home/files/project/23/jdhy38risuee8w88/picture.jpg
        */
        //$this->data['file_foldername'] = random_string('alnum', 20);
        $this->data['file_foldername'] = rand(0,9);
        $this->data['file_folder_path'] = FILES_PROJECT_FOLDER . $project_id . '/' . $this->data['file_foldername'];

        //start the upload
        $this->load->library('fileupload');
        $this->fileupload->allowedExtensions = $this->data['allowed_extensions'];
        //$this->fileupload->newFileName = 'newFile.jpg'; //(optional)
        $result = $this->fileupload->handleUpload($this->data['file_folder_path']);

        //some data about new file
        $filedata['upload_errors'] = $this->fileupload->getErrorMsg();
        $filedata['file_size'] = $this->fileupload->getFileSize();
        $filedata['file_name'] = $this->fileupload->getFileName();
        $filedata['file_extension'] = $this->fileupload->getExtension();
        $filedata['file_path'] = $this->fileupload->getSavedFile();
        $filedata['file_folder_path'] = $this->data['file_folder_path'];
        $filedata['file_foldername'] = $this->data['file_foldername'];
        //---END UPLOAD PROECSS------------------------------------------------------------------

        //Upload passed - continue
        if ($result) {

            //check that new file exists
            if (is_file($filedata['file_path'])) {

                //json
                $jsondata = array('success' => 1, 'message' => $this->data['lang']['lang_file_has_been_uploaded']);

            } else {

                //json
                $jsondata = array('success' => 0, 'message' => $this->data['lang']['lang_upload_system_error']);
                //log this error
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Error saving avatar image]");
            }

            //merge all data for json array
            $jsondata = array_merge($jsondata, $filedata);
            header('HTTP/1.0 200 OK', true, 200);
            //header(�HTTP/1.0 400 Bad Request�, true, 400);

        }

        //Upload passed - continue
        if (!$result) {

            //unlink any folder that may have been created
            @unlink($filedata['file_path']);
            @rmdir($this->data['file_folder_path']);

            //what error message to show
            $message = ($filedata['upload_errors'] != '') ? $filedata['upload_errors'] : $this->data['lang']['lang_file_could_not_uploaded'];

            //create json array, with merge of file data
            $jsondata = array('success' => 0, 'message' => $message);
            //merge with data from upload class
            $jsondata = array_merge($jsondata, $filedata);
            header('HTTP/1.0 400 Bad Request', true, 400);
        }

        //set the json data
        $this->jsondata = $jsondata;

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __uploadProjectAllowedFileTypes- -------------------------------------------------------------------------------------------------------
    /**
     * Generate an array of allowed file types from settings.php config
     */

    protected function __uploadProjectAllowedFileTypes()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if allow all file types
        if ($this->config->item('files_allowed_types') === 0) {

            return array();
        }

        //explode array from settings.php config file
        $allowed = explode("|", $this->config->item('files_allowed_types'));

        //loop through and create new flat array of file types
        for ($i = 0; $i < count($allowed); $i++) {
            $file_extension = strtolower(trim(str_replace("'", '', $allowed[$i])));

            //if $file_extension is valid alphabetic
            if (ctype_alpha($file_extension) || ctype_alnum($file_extension)) {
                $allowed_array[] = $file_extension;
            }
        }

        return $allowed_array;

    }

    // -- __deleteProjectFile- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a project file 
     */

    function __deleteProjectFile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //load the models that we will use
        $this->load->model('files_model');

        //flow control
        $next = true;

        //get data
        $id = $this->input->post('data_mysql_record_id');
        $folder_name = $this->input->post('data_mysql_record_id2');
        $project_id = $this->input->post('data_mysql_record_id3');
        $file_name = $this->input->post('data_mysql_record_id4');

        /*-----------------CLIENT-RESOURCE-OWNERSHIP VALIDATION ------------------------**/
        if ($this->data['vars']['my_user_type'] == 'client') {
            if (!$this->permissions->filesDelete($id)) {
                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_permission_denied_info'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }
        }

        /*-------------------------TEAM MEMBER PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            $next = $this->__validateTeamPermissions($project_id, 'delete_item_my_project_files');
        }

        //validate input
        if ($next) {

            if (!is_numeric($id) || !is_numeric($project_id) || $folder_name == '') {

                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting file failed. Invalid post data]");

                $next = false;
            }

        }

        //delete the file in database
        if ($next) {
            if (!$this->files_model->deleteFile($id, 'file-id')) {

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting file failed - Database error]");

                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                $next = false;

            }
            //debug
            $this->data['debug'][] = $this->files_model->debug_data;
        }

        //delete the file folder
        if ($next) {

            //file & folder
            $file = FILES_PROJECT_FOLDER . $project_id . '/' . $folder_name . '/' . $file_name;
            $folder = FILES_PROJECT_FOLDER . $project_id . '/' . $folder_name;

            //remove file & directory
            @unlink($file);
            @rmdir($folder);

            //events tracker
            $this->__eventsTracker('delete_file', array(
                'target_id' => $id,
                'project_id' => $project_id,
                'details' => $file_name));

            //create json response
            $this->jsondata = array(
                'result' => 'success',
                'message' => $this->data['lang']['lang_request_has_been_completed'],
                'file' => $file);
            header('HTTP/1.0 200 OK', true, 200);

        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }


    // -- __deleteProjectFile- -------------------------------------------------------------------------------------------------------
    /**
     * deleting a project file 
     */

    function __deleteTaskViewer()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get data
        $tasks_viewers_id = $this->input->post('data_mysql_record_id');



        //delete the file in database
        if ($next) {
            if (!$this->tasks_viewers_model->deleteViewer($tasks_viewers_id)) {

                //log this messsage
                log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Deleting file failed - Database error]");

                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_an_error_has_occurred'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                $next = false;
            }
            //debug
            $this->data['debug'][] = $this->tasks_viewers_model->debug_data;
        }

        if ($next) {
            //create json response
            $this->jsondata = array(
                'result' => 'success',
                'message' => $this->data['lang']['lang_request_has_been_completed'],
                'file' => $file);
            header('HTTP/1.0 200 OK', true, 200);
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __eventsTracker- -------------------------------------------------------------------------------------------------------
    /**
     * records new project events (timeline)
     *
     * @param	string $type identify the loop to run in this function
     * @param   array $events_data an optional array that can be used to directly pass data
     */

    function __eventsTracker($type = '', $events_data = array())
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //--------------record a new event-----------------------
        if ($type == 'delete_file') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $events_data['project_id'];
            $events['project_events_type'] = 'deleted';
            $events['project_events_details'] = $events_data['details'];
            $events['project_events_action'] = 'lang_tl_deleted_file';
            $events['project_events_target_id'] = ($events_data['target_id'] == '') ? 0 : $events_data['target_id'];
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = $this->data['vars']['my_user_type'];

            //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;

        }

    }

    // -- validateTeamPermissions-------------------------------------------------------------------------------------------------------
    /**
     * checks if a team member has access to carry out an action like deleting a file
     * [EXAMPLE USAGE]
     * $next = validateTeamPermissions($project_id, 'delete_item_my_project_files');
     *
     * @access	private
     * @param numeric $project_id
     * @param	string $action example: delete_item_my_project_files
     * @return	bool
     */
    function __validateTeamPermissions($project_id = 0, $action = 'none_specified')
    {

        //error control
        $next = true;

        //profiling
        $this->data['controller_profiling'][] = __function__;
        /* --------------------------TEAM MEMBER PROJECT ACCESS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            if ($this->data['vars']['my_group'] != 1) {
                if (!in_array($project_id, $this->data['my_projects_array'])) {
                    //create json response
                    $this->jsondata = array(
                        'result' => 'error',
                        'message' => $this->data['lang']['lang_permission_denied_info'],
                        'debug_line' => __line__);
                    header('HTTP/1.0 400 Bad Request', true, 400);
                    //halt
                    $next = false;
                }
            }
        }

        /* --------------------------TEAM MEMBER PPROJECT PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            if ($this->data['vars']['my_group'] != 1) {
                //load project basics - this also sets my 'this project' permissions
                $this->__commonAll_ProjectBasics($project_id);
                //
                if ($this->data['project_permissions'][$action] != 1) {
                    //create json response
                    $this->jsondata = array(
                        'result' => 'error',
                        'message' => $this->data['lang']['lang_permission_denied_info'],
                        'debug_line' => __line__);
                    header('HTTP/1.0 400 Bad Request', true, 400);
                    //halt
                    $next = false;
                }
            }
        }

        //return results
        if ($next) {
            return true;
        } else {
            return false;
        }

    }

    // -- fmlView-------------------------------------------------------------------------------------------------------
    /**
     * loads json outputting view
     */
    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //sent to TBS engine
        $this->load->view($view, array('data' => $this->jsondata));
    }

    // -- DEBUGGING --------------------------------------------------------------------------------------------------------------
    /**
     * - saves ajax debug output to logfile, seeing as we cant echo or display it
     * 
     */
    function __ajaxdebugging()
    {

        //url segmets array
        $this->data['url_segments'] = $this->uri->segment_array();

        //format debug data for log file
        ob_start();
        print_r($this->data);
        print_r($this->jsondata);
        $all_data = ob_get_contents();
        ob_end_clean();

        //write to logi file
        if ($this->config->item('debug_mode') == 2 || $this->config->item('debug_mode') == 1) {
            log_message('debug', "AJAX-LOG:: BIG DATA $all_data");
        }
    }

}

/* End of file ajax.php */
/* Location: ./application/controllers/common/ajax.php */
