<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
// -- FormProcessor ----------------------------------------------------------------------------------------------
/**
 * FORM VALIDATION CLASS
 * @package		CodeIgniter or Standalone
 * @author		NEXTLOOP
 * @since       2014 April
 * @requires    PHP5.3.x
 * 
 * [WHAT IT DOES]
 * ------------------------------------------------------------------------------------------------------------------
 * It checks a submitted form and returns TRUE/FALSE against specified check types
 *
 *
 * [CODEIGNITER USAGE]
 * ---------------------------------------------------------------------------
 * //It is better not to load this in autoload, so you can pass language file
 * //load form processor library
 * $this->load->library("FormProcessor",$lang); //$lang is the laguage file (name $lang is not mandatory)
 * 
 * //set the fields
 * $fields = array(
 * 'user_name'=>'User Name',
 * 'tel_number'=>'Telephone Number'); //$key = field name; $value = Friendly name
 * 
 * //valid types: required, email, password, matched              
 * if($this->formprocessor->validateFields($fields, 'required')){
 * echo 'form is ok';
 * }else{
 * echo $this->formprocessor->error_message; //e.g. User Name - Is required
 * }
 * 
 *
 * [ADDITIONAL NOTES]
 * ------------------------------------------------------------------------------------------------------------------
 * For matched fields, your array must have only 2 items e.g
 *
 * $fields = array(
 * 'passoword'=>'Password',
 * 'password_confirm'=>'Password Confirm');
 * 
 *
 * [CHECK TYPES - REQUIRED]
 * ------------------------------------------------------------------------------------------------------------------
 * required fields can be any input type, including check boxes.
 * select boxes, its best to make teh first blank
 * 
 *
 * [VALIDATION TYPE]
 * ------------------------------------------------------------------------------------------------------------------
 * required
 * email
 * matched
 * strength   (atleast 8 alphanumeric (a-z 0-9), with 1 number minimum)
 * length    (atleast 8 characters - only checks lenght not complexity)
 * alpha-numeric
 * alpha (A-Z, a-z)
 * numeric (incuding decimal)
 * 
 * [LANGUAGE ARRAY]
 * ------------------------------------------------------------------------------------------------------------------
 * Language is taken from codeigniter language file, or system default english will be used
 * 
 */

class Form_processor
{

    var $error_message;
    var $lang;
    var $validation_type = array(
        'required',
        'email',
        'strength',
        'length',
        'matched',
        'alpha-numeric',
        'alpha',
        'numeric');

    // -- __construct ----------------------------------------------------------------------------------------------
    /**
     * check if a languge array is being passed and set it
     * initialize the __language() method
     *
     * 
     * @param	array
     * @return void
     */
    function __construct($lang_array = '')
    {

        //ADD CODEIGNITER CORE INSTANCE TO BE ABLE TO USE CODEINITER RESOURCES
        $ci = &get_instance();

        //codeigniter - get language array
        $this->lang = $ci->data['lang'];

        //process language
        $this->__language();
    }

    // -- __language ----------------------------------------------------------------------------------------------
    /**
     * checks if the expected language variable are found in the array
     * if not, it sets the array with english
     *
     * 
     * @param	array
     * @return void
     */
    function __language()
    {

        //do we have a language array
        if (!is_array($this->lang)) {
            $this->lang = array();
        }

        //check each language variable - (if not found, use default english language)
        if (!isset($this->lang['form_field_is_required'])) {
            $this->lang['form_field_is_required'] = 'Is required';
        }

        if (!isset($this->lang['form_fields_do_not_match'])) {
            $this->lang['form_fields_do_not_match'] = 'Do not match';
        }

        if (!isset($this->lang['form_field_is_not_a_valid_email'])) {
            $this->lang['form_field_is_not_a_valid_email'] = 'Is not a valid email';
        }

        if (!isset($this->lang['form_field_is_not_strong_enough'])) {
            $this->lang['form_field_is_not_strong_enough'] = 'Must have minimum 8 characters, with atleast 1 number';
        }

        if (!isset($this->lang['form_field_is_too_short'])) {
            $this->lang['form_field_is_too_short'] = 'Must have minimum 8 characters';
        }

        if (!isset($this->lang['form_field_is_not_alphanumeric'])) {
            $this->lang['form_field_is_not_alphanumeric'] = 'Must be numbers and letter only';
        }

        if (!isset($this->lang['form_field_is_not_valid_url'])) {
            $this->lang['form_field_is_not_valid_url'] = 'Is not a valid url';
        }

        if (!isset($this->lang['form_field_is_not_a_number'])) {
            $this->lang['form_field_is_not_a_number'] = 'Is not a valid number';
        }

        if (!isset($this->lang['form_field_is_not_alphabetic'])) {
            $this->lang['form_field_is_not_alphabetic'] = 'Is not alphabetic (A-Z, a-z only)';
        }

    }

    // -- validateFields ----------------------------------------------------------------------------------------------
    /**
     * checks all required fields in submitted array have a value
     * on errors, it sets the message and exits
     *
     * 
     * @param	array
     * @return void
     */
    function validateFields($fields, $type)
    {

        //reset items
        $error_count = 0;
        $this->error_message = '';

        //------------CHECK INPUT IS VALID ARRAY------------------
        if (!is_array($fields) || (!in_array($type, $this->validation_type))) {

            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\
            $message = '<b>File: </b>' . __file__ . '<br/>
                        <b>Function: </b>' . __function__ . '<br/>
                        <b>Line :</b>' . __line__ . '<br/>
                        <b>Notes :</b> Specified type (' . $type . ') is invalid';
            show_error($message, 500);
            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\

        }

        //------------CHECK REQUIRED & EMAIL FIELDS------------------
        if ($type == 'required' || $type == 'email' || $type == 'strength' || $type == 'alpha' || $type == 'alpha-numeric' || $type == 'numeric') {
            foreach ($fields as $key => $value) {
                $key = trim($key);

                //type 'reqiuired'
                if ($type == 'required') {
                    if ($_POST[$key] == '' || !isset($_POST[$key])) {
                        $this->error_message .= "<br/>$value - " . $this->lang['form_field_is_required'];
                        $error_count++;
                    }
                }

                //type 'email'
                if ($type == 'email') {
                    if (!eregi("^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-z]{2,3})$", $_POST[$key])) {
                        $this->error_message .= "<br/>$value - " . $this->lang['form_field_is_not_a_valid_email'];
                        $error_count++;
                    }
                }

                //type 'alpha-numeric'
                if ($type == 'alpha-numeric') {
                    if (!ctype_alnum($_POST[$key])) {
                        $this->error_message .= "<br/>$value - " . $this->lang['form_field_is_not_a_valid_email'];
                        $error_count++;
                    }
                }

                //type 'numeric'
                if ($type == 'numeric') {
                    if (!is_numeric($_POST[$key])) {
                        $this->error_message .= "<br/>$value - " . $this->lang['form_field_is_not_a_number'];
                        $error_count++;
                    }
                }

                //type 'alpha'
                if ($type == 'alpha') {
                    if (!ctype_alpha($_POST[$key])) {
                        $this->error_message .= "<br/>$value - " . $this->lang['form_field_is_not_alphabetic'];
                        $error_count++;
                    }
                }

                //type 'strength'
                if ($type == 'strength') {
                    if (!preg_match('/[A-Za-z]/', $_POST[$key]) || !preg_match('/[0-9]/', $_POST[$key]) || strlen($_POST[$key]) < 8) {
                        $this->error_message .= "<br/>$value - " . $this->lang['form_field_is_not_strong_enough'];
                        $error_count++;
                    }
                }

                //type 'length'
                if ($type == 'length') {
                    if (strlen($_POST[$key]) < 8) {
                        $this->error_message .= "<br/>$value - " . $this->lang['form_field_is_too_short'];
                        $error_count++;
                    }
                }

            }
        }

        //------------CHECK 2 MATCHED FIELDS------------------
        if ($type == 'matched') {

            //get first array key & value
            $field1_name = end($fields);
            $key1 = trim(key($fields));

            //get last array key & value
            $field2_name = reset($fields);
            $key2 = trim(key($fields));

            //check if their values match
            if (($_POST[$key1] != $_POST[$key2]) || (!isset($_POST[$key1])) || (!isset($_POST[$key2]))) {
                $this->error_message .= "<br/>$field1_name & $field2_name - " . $this->lang['form_fields_do_not_match'];
                $error_count++;
            }

        }

        //------------RETURN STATE------------------
        if ($error_count > 0) {
            //trim leading <br/> from error message
            $this->error_message = preg_replace("%^<br/>%", "", $this->error_message);
            return false;
        } else {
            return true;
        }

    }
}

/* End of file Form_processor.php */
/* Location: ./application/libraries/Form_processor.php */
