<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
// -- FormProcessor ----------------------------------------------------------------------------------------------
/**
 * FORMBUILDER.JS - FORM RENDERING CLASS
 * @package		CodeIgniter
 * @author		NEXTLOOP.NET
 * @since       2014 October
 * @requires    PHP5.3.X
 *
 * [DEPENDECIES       - Bootstrap 3.x
 *                    - The rendered forms are based on bootstrap 3.x styling
 *
 * [DEPENDECIES]      - formbuilder.js v0.2.1
 *                    - Input data must be valid from structure data as created by [formbuilder.js v0.2.1]
 *
 * [DEPENDENCIES]     - jqueryvalidation.js v1.12.0
 *                    - This is required if form validation is being used.
 *                    - Only the validation data attribute 'data-rule-required="true"' to each input (if enabled)
 * 
 * [DEPENDENCIES]    - select2.js v3.4.8
 *                    - This is required if pretty select/pulldowns are being used
 * 
 * [WHAT IT DOES]
 * ------------------------------------------------------------------------------------------------------------------
 * This library takes json from data/structure, form built using [formbuilder.js] and builds an HTML/bootstrap form
 *
 * Prefilling of form after posting is done using the $_POST array. TBS must have blockmerge of 'post', so that
 * [post.my_name;htmkconv=no] can be used.
 *
 * [USAGE]
 * ----------------------------------------------------------------------------------------------------------------
 * 
 */
class Formbuilder
{

    var $ci;
    var $debug_mode;
    var $debug_data;
    var $formdata_main_array = array();
    var $formdata_array = array();
    public $builtform;

    /*-----------------------------------------------------------------------------
    * [CKEDITOR[
    * This setting determines if we should use ckeditor for the textarea fields
    * Set to 'false' to disable
    *----------------------------------------------------------------------------*/
    var $ckeditor = true;
    var $ckeditor_config_name = 'Plain';
    var $ckeditor_height = '200px';
    var $ckeditor_color = '#ffffff';

    /*-----------------------------------------------------------------------------
    * [SELECT2.JS]
    * This setting determines if we should use select2.js to style the pulldowns
    *----------------------------------------------------------------------------*/
    var $select2js = true;

    var $jqueryvalidationjs = true;
    var $jsvalidation = ' data-rule-required="true"';

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

    // -- buildform ----------------------------------------------------------------------------------------------
    /**
     * Process the data passed and check if it likely to be valid [formbuilder.js] form data/structure
     * 
     * @param	string [theform: formbuilder.js form data string]
     * @return void
     */
    function buildform($theform = '')
    {

        //Debugging
        $this->ci->data['vars']['formbuilder_raw_data'] = $theform;

        //sanity check - is the input data 'possibly' valid [formbuilder.js] data
        if (!preg_match('/{"fields":\\[/', $theform)) {

            //log this error
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Input data is not valid (formbuider.js) data]");

            //return false
            return false;
        }

        //decode the [formbuilder.js] string & save the data as an array
        $this->formdata_main_array = json_decode($theform, true);
        $this->ci->data['vars']['formdata_main_array'] = $this->formdata_main_array; //Debugging

        //get the part of the new array which we need
        $this->formdata_array = $this->formdata_main_array['fields'];
        $this->ci->data['vars']['formdata_array'] = $this->formdata_array; //Debugging

        //sanity check - is the array valid
        if (!is_array($this->formdata_array) || empty($this->formdata_array)) {
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Form data array is empty or invalid]");

            //return
            return false;
        }

        //everything seems ok, continue
        return ($this->__buildTheForm());

    }

    function __buildTheForm()
    {

        //flow control
        $next = true;

        //loop through the form and build fields
        $formfields = '';
        for ($i = 0; $i < count($this->formdata_array); $i++) {

            //BUILD - [Radio Field]
            if ($this->formdata_array[$i]['field_type'] == 'radio') {

                //process & build the field
                $formfields .= $this->__buildRadioField($this->formdata_array[$i]);

            }

            //BUILD - [checkbox field]
            if ($this->formdata_array[$i]['field_type'] == 'checkboxes') {

                //process & build the field
                $formfields .= $this->__buildCheckboxField($this->formdata_array[$i]);

            }

            //BUILD - [select field]
            if ($this->formdata_array[$i]['field_type'] == 'dropdown') {

                //process & build the field
                $formfields .= $this->__buildSelectField($this->formdata_array[$i]);

            }

            //BUILD - [text field]
            if ($this->formdata_array[$i]['field_type'] == 'text') {

                //process & build the field
                $formfields .= $this->__buildTextField($this->formdata_array[$i]);

            }

            //BUILD - [text field]
            if ($this->formdata_array[$i]['field_type'] == 'paragraph') {

                //process & build the field
                $formfields .= $this->__buildTextareaField($this->formdata_array[$i]);

            }

        }

        //are we use jqueryvalidation.js
        if ($this->jqueryvalidationjs) {
            $formfields = $formfields . '
                             <script>
                                   $(document).ready(function(){
                                      $(".formbuilder").validate({
                                            submitHandler: function(form) {
                                            form.submit();
                                            }
                                          });
                                    });
                              </script>';
        }

        //return the form fields
        return $formfields;
    }

    // -- __buildRadioField ----------------------------------------------------------------------------------------------
    /**
     * Build an HTML [RADIO] form field
     * 
     * @param	void
     * @return void
     */
    function __buildRadioField($fielddata = '')
    {

        //reset some things - just in case
        $element = '';
        $element_hidden_field = '';
        $element_asterix = '';
        $element_hidden_question = '';
        
        //basics of this input field
        $label = $fielddata['label'];
        $field_name = $fielddata['cid'];
        $field_options = $fielddata['field_options']['options'];
        $jqueryvalidationjs_tag = ($fielddata['required'] == 1) ? $this->jsvalidation : '';

        //get all of its options & build
        $options = '';
        for ($e = 0; $e < count($field_options); $e++) {
            $value = $field_options[$e]['label'];
            $checked = ($field_options[$e]['checked'] == 1) ? ' checked="checked"' : '';
            $options .= '<div class="radio">
                                     <label>
                                       <input type="radio" value="' . $value . '" name="' . $field_name . '"' . $checked . $jqueryvalidationjs_tag . '>' . $value . '</label>
                                  </div>';

            //reset jqueryvalidation tag for rest of options
            $jqueryvalidationjs_tag = '';
        }

        /*
        * is this field required - if yes, create ahidden field for later validation
        * add the 'question' as the hidden fields [value]
        */
        if (isset($field_required) && $field_required == 1) {
            $element_hidden_field = '<input type="hidden" name="required[' . $field_name . ']" value="' . $label . '">';
            $element_asterix = '<strong>*</strong>'; //put an asterix to show its required
        }

        //build element
        $element = '<div class="form-group formbuilder-form-rendered">
                              <label class="control-label">' . $label . $element_asterix . '</label>
                                <div>
                                  ' . $options . '
                                </div>
                           </div>' . $element_hidden_field . $element_hidden_question; //retun element

        //return the finished form html form field
        return $element;
    }

    // -- __buildCheckboxField ----------------------------------------------------------------------------------------------
    /**
     * Build an HTML [CHECKBOX] form field
     * 
     * @param	void
     * @return void
     */
    function __buildCheckboxField($fielddata = '')
    {

        //reset some things - just in case
        $element = '';
        $element_hidden_field = '';
        $element_hidden_question = '';
        $element_asterix = '';

        //basics of this input field
        $label = $fielddata['label'];
        $field_name = $fielddata['cid'];
        $field_options = $fielddata['field_options']['options'];
        $field_required = $fielddata['required'];
        //create the tag for jqueryvalidation.js
        $jqueryvalidationjs_tag = ($fielddata['required'] == 1) ? $this->jsvalidation : '';

        //get all of its options & build
        $options = '';
        for ($e = 0; $e < count($field_options); $e++) {
            //the value
            $value = $field_options[$e]['label'];
            //is it pre-checked
            $checked = ($field_options[$e]['checked'] == 1) ? ' checked="checked"' : '';
            //create the html
            $options .= '<label class="checkbox">
                          <input type="checkbox" value="' . $value . '" name="' . $field_name . '"' . $checked . $jqueryvalidationjs_tag . '>' . $value . '
                          </label>';

            //reset jqueryvalidation tag for rest of option
            $jqueryvalidationjs_tag = '';
        }

        /*
        * is this field required - if yes, create ahidden field for later validation
        * add the 'question' as the hidden fields [value]
        */
        if ($field_required == 1) {
            $element_hidden_field = '<input type="hidden" name="required[' . $field_name . ']" value="' . $label . '">';
            $element_asterix = '<strong>*</strong>'; //put an asterix to show its required
        }

        //build element
        $element = '<div class="form-group formbuilder-form-rendered">
                              <label class="control-label">' . $label . $element_asterix . '</label>
                                <div>
                                  ' . $options . '
                                </div>
                           </div>' . $element_hidden_field . $element_hidden_question; //retun element

        //return the finished form html form field
        return $element;
    }

    // -- __buildSelectField ----------------------------------------------------------------------------------------------
    /**
     * Build an HTML [SELECT] form field
     * 
     * @param	void
     * @return void
     */
    function __buildSelectField($fielddata = '')
    {

        //reset some things - just in case
        $element = '';
        $element_hidden_field = '';
        $element_asterix = '';
        $element_hidden_question = '';
        $blank_option = '';
        
        //basics of this input field
        $label = $fielddata['label'];
        $field_name = $fielddata['cid'];
        $field_options = $fielddata['field_options']['options'];
        $field_required = $fielddata['required'];
        //create the tag for jqueryvalidation.js
        $jqueryvalidationjs_tag = ($fielddata['required'] == 1) ? $this->jsvalidation : '';

        //should we adda blank option
        if ($fielddata['field_options']['include_blank_option'] == 1) {
            $blank_option = '<option></option>';
        }

        //get all of its options & build
        $options = '';
        for ($e = 0; $e < count($field_options); $e++) {
            //the value
            $value = $field_options[$e]['label'];
            //is it pre-checked
            $checked = ($field_options[$e]['checked'] == 1) ? ' selected="selected"' : '';
            //create the html
            $options .= '<option value="' . $value . '"' . $checked . '>' . $value . '</option>';
        }

        /*
        * is this field required - if yes, create ahidden field for later validation
        * add the 'question' as the hidden fields [value]
        */
        if ($field_required == 1) {
            $element_hidden_field = '<input type="hidden" name="required[' . $field_name . ']" value="' . $label . '">';
            $element_asterix = '<strong>*</strong>'; //put an asterix to show its required
        }

        //build element
        $element = '<div class="form-group formbuilder-form-rendered">
                              <label class="control-label">' . $label . $element_asterix . '</label>
                                <div><select name="' . $field_name . '" id="' . $field_name . '"' . $jqueryvalidationjs_tag . '>
                                  ' . $blank_option . $options . '
                                </select></div>
                           </div>' . $element_hidden_field . $element_hidden_question; //retun element

        //add select2.js code if it is enabled
        if ($this->select2js) {
            $element .= '<script>
                            $(document).ready(function(){
                              $("#' . $field_name . '").select2({
                                allowClear: true
                                });
                             });
                        </script>';
        }

        //return the finished form html form field
        return $element;
    }

    // -- __buildTextField ----------------------------------------------------------------------------------------------
    /**
     * Build an HTML [TEXT] form field
     * 
     * @param	void
     * @return void
     */
    function __buildTextField($fielddata = '')
    {

        //reset some things - just in case
        $element = '';
        $element_hidden_field = '';
        $element_asterix = '';
        $element_hidden_question = '';
        
        //basics of this input field
        $label = $fielddata['label'];
        $field_name = $fielddata['cid'];
        $field_required = $fielddata['required'];
        //create the tag for jqueryvalidation.js
        $jqueryvalidationjs_tag = ($fielddata['required'] == 1) ? $this->jsvalidation : '';

        /*
        * is this field required - if yes, create ahidden field for later validation
        * add the 'question' as the hidden fields [value]
        */
        if ($field_required == 1) {
            $element_hidden_field = '<input type="hidden" name="required[' . $field_name . ']" value="' . $label . '">';
            $element_asterix = '<strong>*</strong>'; //put an asterix to show its required
        }

        //build element
        $element = '<div class="form-group formbuilder-form-rendered">
                              <label class="control-label">' . $label . $element_asterix . '</label>
                                <div>
                                <input type="text" class="form-control" name="' . $field_name . '"' . $jqueryvalidationjs_tag . 'value="[onshow;post.' . $field_name . ';htmlconv=no]">
                                </div>
                           </div>' . $element_hidden_field . $element_hidden_question; //retun element

        //return the finished form html form field
        return $element;
    }

    // -- __buildTextField ----------------------------------------------------------------------------------------------
    /**
     * Build an HTML [TEXT] form field
     * 
     * @param	void
     * @return void
     */
    function __buildTextareaField($fielddata = '')
    {

        //reset some things - just in case
        $element = '';
        $element_hidden_field = '';
        $element_asterix = '';
        $element_hidden_question = '';
        
        //basics of this input field
        $label = $fielddata['label'];
        $field_name = $fielddata['cid'];
        $field_required = $fielddata['required'];
        //create the tag for jqueryvalidation.js
        $jqueryvalidationjs_tag = ($fielddata['required'] == 1) ? $this->jsvalidation : '';

        /*
        * is this field required - if yes, create ahidden field for later validation
        * add the 'question' as the hidden fields [value]
        */
        if ($field_required == 1) {
            $element_hidden_field = '<input type="hidden" name="required[' . $field_name . ']" value="' . $label . '">';
            $element_asterix = '<strong>*</strong>'; //put an asterix to show its required
        }

        //build element
        $element = '<div class="form-group formbuilder-form-rendered">
                              <label class="control-label">' . $label . $element_asterix . '</label>
                                <div>
                                  <textarea class="form-control" name="' . $field_name . '" id="' . $field_name . '" rows="5"' . $jqueryvalidationjs_tag . '>
                                  [onshow;post.' . $field_name . ';htmlconv=no]
                                  </textarea>
                                   <script>
                                       CKEDITOR.replace( \'' . $field_name . '\', {
                                                toolbar: \'Plain\',
                                                uiColor: \'#ffffff\',
	                                            height: \'120px\'
                                               });
                                   </script>
                                </div>
                           </div>' . $element_hidden_field . $element_hidden_question; //retun element

        //return the finished form html form field
        return $element;
    }

    // -- reBuildForm ----------------------------------------------------------------------------------------------
    /**
     * The reverse processs of taking a saved form and building it back for display.
     * it takes 2 inputs (1) the origianl $_post array of what the user filled in the form
     *                     (2) the original json_encoded, formbuilder.js form structure of the filled in form
     * Data is oiginally stored in the database as base64_encoded and additionally $_post data also serialied
     * 
     * @param	string [theform: formbuilder.js form structure data]
     * @param	string [postdata: the original $_post form data]
     * @return void
     */
    function reBuildForm($theform = '', $postdata = '')
    {

        //validate params
        if ($theform == '' || $postdata == '') {
            //log this error
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Input data is not valid]");
            return false;
        }

        //turn post data back into an array
        $this->ci->data['vars']['raw_post_data'] = $postdata;
        $this->ci->data['vars']['post_data_array'] = unserialize(base64_decode($postdata));

        //decode the [formbuilder.js] string & save the data as an array
        $this->ci->data['vars']['raw_form_data'] = base64_decode($theform);
        $this->ci->data['vars']['form_data_array'] = json_decode($this->ci->data['vars']['raw_form_data'], true);

        //get the part of the new array which we need
        $this->ci->data['vars']['form_array'] = $this->ci->data['vars']['form_data_array']['fields'];

        //sanity check - is the array valid
        if (!is_array($this->ci->data['vars']['form_array']) || !is_array($this->ci->data['vars']['form_data_array'])) {
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Form arrays are invalid]");
            //return
            return false;
        }

        //create new array, combining form data & post data
        $this->ci->data['vars']['rebuilt_quotation_form'] = array();
        for ($i = 0; $i < count($this->ci->data['vars']['form_array']); $i++) {
            //key from form structure
            $key = $this->ci->data['vars']['form_array'][$i]['cid'];
            //label from form structure
            $question = $this->ci->data['vars']['form_array'][$i]['label'];
            //match key to post data, to get value
            $answer = $this->ci->data['vars']['post_data_array'][$key];
            //create new array 'form structure label'=>'post data'
            $temp = array('question' => $question, 'answer' => $answer);
            //build up array
            $this->ci->data['vars']['rebuilt_quotation_form'][] = $temp;
        }

        //return new rebuilt form array
        return $this->ci->data['vars']['rebuilt_quotation_form'];

    }
}

/* End of file xyz.php */
/* Location: ./application/libraries/xyz.php */
