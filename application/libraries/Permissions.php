<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
// -- Permission Calss ----------------------------------------------------------------------------------------------
/**
 * PERMISSIONS VALIDATION CLASS - AKA D.A.V.E  (delete/add/view/edit)
 * @package		CodeIgniter
 * @author		NEXTLOOP.NET
 * @since       2014 July
 * @requires    PHP5.3.x

 * 
 * [WHAT IT DOES]
 * ------------------------------------------------------------------------------------------------------------------
 * This class does all the permission checking for [ACTIONS] against [OBJECTS] by [USERS]
 *                 [ACTIONS] - 4 tier actions (delete/add/view/edit)
 *                 [OBJECTS] - an oject is identified by its database ID (example: file_id, project_id, task_id etc)
 *                           - where an object is actually being created (i.e. add new project) object_id must be set to [0]
 *                 [USERS]   - a users is identified by their current session data (both client users & team members)
 *                 Example:  - Does the client_user have permission to delete a file, identified by its file id
 *
 * [INPUT]
 *-------------------------------------------------------------------------------------------------------------------
 * For any given check, 1 input param are required 
 *        (1) The ID of the obejct being affected (i.e. file_id)
 *        (NB) the action against that object is determined by the name of the method in this class that is called.
 *
 *
 * [OUTPUT]
 *-------------------------------------------------------------------------------------------------------------------
 * The class return TRUE/FALSE (i.e. permission granted or not granted). The controller that made the query can then
 * decide what steps to take based on this response.
 *
 *
 * [PERMISSIONS - TEAM]
 *-------------------------------------------------------------------------------------------------------------------
 * For team members, their permissions/rights are determined mainly by:
 *        (1) their permission levels for that action
 *        (2) for project related actions, if they are assigned to that project
 *        (3) if they are in admin group
 *        (4) if they are project leader for given project
 *        (5) ownership of affected object (i.e. did they upload the file in the first place)
 *
 * [PERMISSIONS - CLIENT USER]
 *-------------------------------------------------------------------------------------------------------------------
 * For client users permissions/rights are determined by mainly by:
 *        (1) ownership of affected object (i.e. did they upload the file in the first place)
 *
 *
 * [DEBUG DATA]
 * ----------------------------------------------------------------------------------------------------------------
 * Debug data is saved directly to $this->data['debug'] array, which is accessible from the controller
 *
 * [REASON] [$this->permissions->reason]
 * ----------------------------------------------------------------------------------------------------------------
 * This object stores the 'reason for a decision made by permissions class. i.e. the reason for denied permission
 * can be used in controller to display error messages $error = $this->permissions->reason
 * language used is taken from the application language file
 * 
 */
class Permissions
{

    var $ci; //codeigniter instance
    var $object_id; //the id of the item (e.g. file_id)
    var $debug_data;
    var $lib_lang;

    //some information about the user accessing this class (i.e. the logged in clien user or team member)
    var $my_user_type;
    var $my_id;
    var $my_group_id;
    var $my_group_name;
    var $reason = '';

    // -- __construct ----------------------------------------------------------------------------------------------
    /**
     *
     * 
     * @param	void
     * @return void
     */
    function __construct()
    {

        //ADD CODEIGNITER CORE INSTANCE TO BE ABLE TO USE CODEINITER RESOURCES
        $this->ci = &get_instance();

        //get config debug mode
        $this->debug_mode = $this->ci->config->item('debug_mode');
    }

    // -- usersEdit ----------------------------------------------------------------------------------------------
    /**
     *
     * 
     * @param numeric   [id: the client users id]
     * @return	null
     */

    function usersEdit($object_id = '')
    {

        //initialize
        $this->__initialize($object_id);

        /** -----------------------------I AM A CLIENT USER-------------------------- */
        if ($this->my_user_type == 'client') {

            //Am I editing my own profile
            if ($this->my_id != $object_id) {
                //output debug
                $this->__debugData(__line__, __function__, 'denied', $this->lib_lang['lang_permission_denied_info']);

                //return to controller
                return false;

            } else {
                //output debug
                $this->__debugData(__line__, __function__, "granted", '');

                //return to controller
                return true;

            }
        }

    }

    // -- usersDelete ----------------------------------------------------------------------------------------------
    /**
     * delete a client user
     * 
     * @param numeric   [object_id: the objects id e.g. file_id]
     * @return	null
     */

    function usersDelete($object_id = '')
    {

        //initial validation steps
        $this->__initialize($object_id);

        /** -----COMON DATA AND SETTINGS------*/

        $result = $this->ci->users_model->userDetails($object_id);
        $client_id = $result['client_users_clients_id'];
        $primary_contact = $result['client_users_main_contact'];

        /** ------COMON PERMISSION CHECKS-----*/
        //is user being deleted the main or only contact
        if ($primary_contact != 'no') {
            //return
            $this->__debugData(__line__, __function__, 'denied', $this->lib_lang['lang_you_cannot_delete_primary_contact']);

            //return
            return false;
        }

        /** ------I AM A CLIENT USER-----------*/
        if ($this->my_user_type == 'client') {

            //is user being deleted in same company as me
            if ($client_id != $this->ci->data['vars']['my_client_id']) {
                //return
                $this->__debugData(__line__, __function__, 'denied', $this->lib_lang['lang_permission_denied']);

                //return
                return false;
            }

            //everything ok
            $this->__debugData(__line__, __function__, "granted", '');

            //return
            return true;
        }
        
                    //return
            return true;

    }

    // -- quotationsView ----------------------------------------------------------------------------------------------
    /**
     * view a quotation
     * 
     * @param numeric   [object_id: the objects id e.g. file_id]
     * @return	null
     */

    function quotationsView($object_id = '')
    {

        //initialize
        $this->__initialize($object_id);

        /** -----------------------------I AM A CLIENT USER-------------------------- */
        if ($this->my_user_type == 'client') {

            //check that client owns this quotation
            $result = $this->ci->quotations_model->validateClientOwner($object_id, $this->ci->data['vars']['my_client_id']);
            //debug
            $this->ci->data['debug'][] .= $this->ci->quotations_model->debug_data;

            //is the client the quotation owner?
            if ($result) {
                //everything ok
                $this->__debugData(__line__, __function__, "granted", '');
                //return
                return true;
            }

            //failed test
            $this->__debugData(__line__, __function__, 'denied', $this->lib_lang['lang_permission_denied']);
            //return
            return false;
        }

    }

    // -- quotationsView ----------------------------------------------------------------------------------------------
    /**
     * view a bug
     * 
     * @param numeric   [object_id: the objects id e.g. file_id]
     * @return	null
     */

    function bugsView($object_id = '')
    {

        //initialize
        $this->__initialize($object_id);

        /** -----------------------------I AM A CLIENT USER-------------------------- */
        if ($this->my_user_type == 'client') {

            //check that client owns this quotation
            $result = $this->ci->bugs_model->validateClientOwner($object_id, $this->ci->data['vars']['my_client_id']);
            //debug
            $this->ci->data['debug'][] .= $this->ci->bugs_model->debug_data;

            //is the client the quotation owner?
            if ($result) {
                //everything ok
                $this->__debugData(__line__, __function__, "granted", '');
                //return
                return true;
            }

            //failed test
            $this->__debugData(__line__, __function__, 'denied', $this->lib_lang['lang_permission_denied']);
            //return
            return false;
        }

    }

    // -- filesView ----------------------------------------------------------------------------------------------
    /**
     * view a file
     * 
     * @param numeric   [object_id: the objects id e.g. file_id]
     * @return	null
     */

    function filesView($object_id = '')
    {

        //initialize
        $this->__initialize($object_id);

        /** -----------------------------I AM A CLIENT USER-------------------------- */
        if ($this->my_user_type == 'client') {

            //check that client owns this quotation
            $result = $this->ci->files_model->validateClientOwner($object_id, $this->ci->data['vars']['my_client_id']);
            //debug
            $this->ci->data['debug'][] .= $this->ci->files_model->debug_data;

            //is the client the quotation owner?
            if ($result) {
                //everything ok
                $this->__debugData(__line__, __function__, "granted", '');
                //return
                return true;
            }

            //failed test
            $this->__debugData(__line__, __function__, 'denied', $this->lib_lang['lang_permission_denied']);
            //return
            return false;
        }

    }

    // -- ticketsView ----------------------------------------------------------------------------------------------
    /**
     * view a a ticket
     * 
     * @param numeric   [object_id: the objects id e.g. file_id]
     * @return	null
     */

    function ticketsView($object_id = '')
    {

        //initialize
        $this->__initialize($object_id);

        /** -----------------------------I AM A CLIENT USER-------------------------- */
        if ($this->my_user_type == 'client') {

            //check that client owns this quotation
            $result = $this->ci->tickets_model->validateClientOwner($object_id, $this->ci->data['vars']['my_client_id']);
            //debug
            $this->ci->data['debug'][] .= $this->ci->tickets_model->debug_data;

            //is the client the ticket owner?
            if ($result) {
                //everything ok
                $this->__debugData(__line__, __function__, "granted", '');
                //return
                return true;
            }

            //failed test
            $this->__debugData(__line__, __function__, 'denied', $this->lib_lang['lang_permission_denied']);
            //return
            return false;
        }

    }

    // -- invoicesView ----------------------------------------------------------------------------------------------
    /**
     * view an invoice
     * 
     * @param numeric   [object_id: the objects id e.g. file_id]
     * @return	null
     */

    function invoicesView($object_id = '')
    {

        //initialize
        $this->__initialize($object_id);

        /** -----------------------------I AM A CLIENT USER-------------------------- */
        if ($this->my_user_type == 'client') {

            //check that client owns this quotation
            $result = $this->ci->invoices_model->validateClientOwner($object_id, $this->ci->data['vars']['my_client_id']);
            //debug
            $this->ci->data['debug'][] .= $this->ci->invoices_model->debug_data;

            //is the client the invoice owner?
            if ($result) {
                //everything ok
                $this->__debugData(__line__, __function__, "granted", '');
                //return
                return true;
            }

            //failed test
            $this->__debugData(__line__, __function__, 'denied', $this->lib_lang['lang_permission_denied']);
            //return
            return false;
        }

    }

    // -- filesDelete ----------------------------------------------------------------------------------------------
    /**
     *
     * 
     * @param numeric   [object_id: the objects id e.g. file_id]
     * @return	null
     */

    function filesDelete($object_id = '')
    {

        //initialize
        $this->__initialize($object_id);

        /** -----------------------------I AM A CLIENT USER-------------------------- */
        if ($this->my_user_type == 'client') {

            //check that client owns this quotation
            $result = $this->ci->files_model->validateClientOwner($object_id, $this->ci->data['vars']['my_client_id']);
            //debug
            $this->ci->data['debug'][] .= $this->ci->files_model->debug_data;

            //is the client the invoice owner?
            if ($result) {
                //everything ok
                $this->__debugData(__line__, __function__, "granted", '');
                //return
                return true;
            }

            //failed test
            $this->__debugData(__line__, __function__, 'denied', $this->lib_lang['lang_permission_denied']);
            //return
            return false;
        }

    }

    // -- TEMPLATE METHOD ----------------------------------------------------------------------------------------------
    /**
     *
     * 
     * @param numeric   [object_id: the objects id e.g. file_id]
     * @return	null
     */

    function TEMPLATE($object_id = '')
    {

        //initialize
        $this->__initialize($object_id);

        /** ---------------------------COMON DATA AND SETTINGS-----------------------*/
        //common data
        $var = 'foo';
        $bar = 'foobar';

        /** ---------------------------COMON PERMISSION CHECKS-----------------------*/
        //common check for admin & client request
        if ($bar == 'foo') {
            //return
            $this->__debugData(__line__, __function__, 'denied', $this->lib_lang['lang_permission_denied']);

            //return
            return false;
        }

        /** -----------------------------I AM A CLIENT USER-------------------------- */
        if ($this->my_user_type == 'client') {

            //do the permission check
            if ($foo == $bar) {
                //return - denied
                $this->__debugData(__line__, __function__, 'denied', '');
            }

            //everything ok
            $this->__debugData(__line__, __function__, "granted", '');

            //return
            return true;
        }

        /** -----------------------------I AM A TEAM MEMBER--------------------------- */
        if ($this->my_user_type == 'team') {

            //do the permission check
            if ($foo == $bar) {
                //return - granted
                $this->__debugData(__line__, __function__, 'denied', '');
            }

            //everything ok
            $this->__debugData(__line__, __function__, "granted", '');

            //return
            return true;
        }
    }

    // -- __initialize ----------------------------------------------------------------------------------------------
    /**
     * This sets the language (which will now be available at this point)
     * It also does intial validation of input
     * 
     * @param numeric   [id: the database id of the affected object]
     * @return	null
     */

    function __initialize($object_id = '')
    {

        //import language file and set to local object
        $this->lib_lang = $this->ci->data['lang'];

        //reset object id
        $this->object_id = '';

        //-----am I logged in-------------------------------------------------------------
        if (!is_numeric($this->ci->data['vars']['my_id']) || !in_array($this->ci->data['vars']['my_user_type'], array('team', 'client'))) {

            //debug
            $this->ci->data['debug'][] .= $this->__debugData(__line__, __function__, 'denied', $this->lib_lang['lang_permission_denied_info']);

            //return
            return false;

        } else {

            //set some data about me for easy access
            $this->my_user_type = $this->ci->data['vars']['my_user_type'];
            $this->my_id = $this->ci->data['vars']['my_id'];

            if ($this->my_user_type == 'team') {
                $this->my_group_name = $this->ci->data['vars']['my_group_name'];
                $this->my_group_id = $this->ci->data['vars']['my_group'];
            } else {
                $this->ci->data['vars']['my_group_name'] = '';
                $this->ci->data['vars']['my_group'] = '';
                $this->ci->data['vars']['my_group_id'] = '';
                $this->my_group_name = '';
                $this->my_group_id = '';
            }

        }

        //-----validate object param-----------------------------------------------------------------------
        if (!is_numeric($object_id)) {
            //log error
            $this->ci->data['debug'][] .= $this->__debugData(__line__, __function__, 'denied', "Invalid Input Data");
            //return
            return false;
        } else {

            //set the object id for easy access
            $this->object_id = $object_id;
        }

    }

    // -- __debugData ----------------------------------------------------------------------------------------------
    /**
     * create nicely formatted debug data
     *
     * 
     * @return	bool      [this function is the one that returns back to controller]
     */

    function __debugData($line_number = '', $function = '', $outcome = 'denied', $reason = 'permission denied')
    {

        //reset response
        $this->response_message = '';

        //create nice array
        $debug_array = array(
            'LIBRARY' => '[PERMISSIONS CLASS - RESULTS]',
            'FILE' => __file__,
            'LINE' => $line_number,
            'FUNCTION' => $function,
            'OBJECT ID' => $this->object_id,
            'MY ID' => $this->ci->data['vars']['my_id'],
            'MY USER TYPE' => $this->ci->data['vars']['my_user_type'],
            'MY GROUP NAME' => $this->ci->data['vars']['my_group_name'],
            'MY GROUP ID' => $this->ci->data['vars']['my_group_id'],
            'OUTCOME' => $outcome,
            'REASON' => $reason);

        //format with <pre> and return the data
        ob_start();
        echo '<pre>';
        print_r($debug_array);
        echo '</pre>';
        $debug = ob_get_contents();
        ob_end_clean();

        //debug output
        $this->ci->data['debug'][] .= $debug;

        //also save as local object (good for accessing from ajax calls)
        $this->debug_data = $debug;

        //save error message (notes)
        $this->reason = $reason;

    }

}

/* End of file Permissions.php */
/* Location: ./application/libraries/Permissions.php */
