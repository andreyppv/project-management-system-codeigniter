<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// -- clients_optionalfield_visibility -------------------------------------------------------------------------------------------------
/**
 * sets the visibility of form fields (TBS Widget) for each enabled optional fields
 * sets the name/title of each field
 * It is expeted that the widgets will be named as (wi_clients_optionalfield1) (wi_clients_optionalfield2) etc
 * 
 * @param	array $optional_fields of all rows from clients_optionalfields table (as passed on by controller)
 */
if (! function_exists('clients_optionalfield_visibility')) {
    function clients_optionalfield_visibility($optional_fields = '')
    {

        //get $CI instance
        $CI = &get_instance();

        //loop through all fields and set visibility and field names
        if (is_array($optional_fields)) {
            for ($i = 0; $i < count($optional_fields); $i++) {

                //get name, title, required state - of each field
                $optional_field = $optional_fields[$i]['clients_optionalfield_name'];
                $required = $optional_fields[$i]['clients_optionalfield_require'];
                $title = $optional_fields[$i]['clients_optionalfield_title'];

                //create new widget style name
                $widget_name = 'wi_' . $optional_field;
                $widget_name_required = 'wi_' . $optional_field . '_required';

                //make form input widget visible (e.g. in form)
                $CI->data['visible'][$widget_name] = 1;

                //make additional form heading visible
                $CI->data['visible']['wi_clients_optionalfields_heading'] = 1;

                //set name to use (e.g. label)
                $CI->data['row'][$widget_name] = $title;

                //set any javascript related setting (e.g. jquery.validation.js settings)
                if ($required == 'yes') {
                    $CI->data['visible'][$widget_name_required] = 1;
                    $CI->data['js_validation'][$widget_name] = $optional_field . ': "required",';
                    $CI->data['js_validation_message'][$widget_name] = $optional_field . ': "' . $CI->data['lang']['lang_field_is_required'] . '",';
                } else {
                    $CI->data['visible'][$widget_name_required] = 0;
                }
            }
        }
    }
}

// -- projects_optionalfield_visibility -------------------------------------------------------------------------------------------------
/**
 * sets the visibility of form fields (TBS Widget) for each enabled optional fields
 * sets the name/title of each field
 * It is expeted that the widgets will be named as (wi_projects_optionalfield1) (wi_projects_optionalfield2) etc
 * 
 * @param array $optional_fields of all rows from projects_optionalfields table (as passed on by controller)
 */
if (! function_exists('projects_optionalfield_visibility')) {
    function projects_optionalfield_visibility($optional_fields = '')
    {

        //get $CI instance
        $CI = &get_instance();

        //loop through all fields and set visibility and field names
        if (is_array($optional_fields)) {
            for ($i = 0; $i < count($optional_fields); $i++) {

                //get name, title, required state - of each field
                $optional_field = $optional_fields[$i]['projects_optionalfield_name'];
                $required = $optional_fields[$i]['projects_optionalfield_require'];
                $title = $optional_fields[$i]['projects_optionalfield_title'];

                //create new widget style name
                $widget_name = 'wi_' . $optional_field;

                //make form input widget visible (e.g. in form)
                $CI->data['visible'][$widget_name] = 1;

                //make additional form heading visible
                $CI->data['visible']['wi_projects_optionalfields_heading'] = 1;

                //set name to use (e.g. label)
                $CI->data['row'][$widget_name] = $title;

                //set any javascript related setting (e.g. jquery.validation.js settings)
                if ($required == 'yes') {
                    $CI->data['js_validation'][$widget_name] = $optional_field . ': "required",';
                    $CI->data['js_validation_message'][$widget_name] = $optional_field . ': "' . $CI->data['lang']['lang_field_is_required'] . '",';
                }
            }
        }
    }
}

// -- clients_optionalfield_array -------------------------------------------------------------------------------------------------
/**
 * returns an array of all required fields for optional fields for 
 * this array is useful for form validation etc
 *
 * @param array $optional_fields of all rows from clients_optionalfields table (as passed on by controller)
 */
if (! function_exists('clients_optionalfield_array')) {
    function clients_optionalfield_array($optional_fields = '')
    {

        //get $CI instance
        $CI = &get_instance();

        //loop thorugh all the rows and create array of required ones
        if (is_array($optional_fields)) {
            $status = array();
            for ($i = 0; $i < count($optional_fields); $i++) {
                if ($optional_fields[$i]['clients_optionalfield_require'] == 'yes') {
                    $status[] = $optional_fields[$i]['clients_optionalfield_name'];
                }
            }
        }
        //return array of field status
        return $status;
    }
}

// -- projects_optionalfield_array -------------------------------------------------------------------------------------------------
/**
 * returns an array of all required fields for optional fields for 
 * this array is useful for form validation etc 
 * 
 * @param array $optional_fields of all rows from projects_optionalfields table (as passed on by controller)
 */
if (! function_exists('projects_optionalfield_array')) {
    function projects_optionalfield_array($optional_fields = '')
    {

        //get $CI instance
        $CI = &get_instance();

        //loop thorugh all the rows and create array of required ones
        if (is_array($optional_fields)) {
            $status = array();
            for ($i = 0; $i < count($optional_fields); $i++) {
                if ($optional_fields[$i]['projects_optionalfield_require'] == 'yes') {
                    $status[] = $optional_fields[$i]['projects_optionalfield_name'];
                }
            }
        }
        //return array of field status
        return $status;
    }
}

// -- projects_optionalfields -------------------------------------------------------------------------------------------------
/**
 * returns an array of all required fields for optional fields for 
 * this array is useful for form validation etc
 * 
 * @param array $optional_fields of all rows from projects_optionalfields table (as passed on by controller)
 */
if (! function_exists('projects_optionalfields')) {
    function projects_optionalfields($optional_fields = array(), $project_data = array())
    {

        //get $CI instance
        $CI = &get_instance();

        //loop thorugh all the rows and create array of required ones
        if (is_array($optional_fields) && is_array($project_data)) {
            $result = array();

            for ($i = 0; $i < count($optional_fields); $i++) {

                //get the field title
                $filed_title = $optional_fields[$i]['projects_optionalfield_title'];
                //get the field name
                $field_name = $optional_fields[$i]['projects_optionalfield_name'];
                //get data from row with same field name
                $row_data = $project_data[$field_name];

                //merge into big array
                $result[] = array('field_title' => $filed_title, 'field_data' => $row_data);
            }
        }
        //return array of field status
        return $result;
    }
}

/* End of file optionalfields_helper.php */
/* Location: ./application/helpers/optionalfields_helper.php */
