<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// -- js_allowedFileTypes- -------------------------------------------------------------------------------------------------------
/**
 * Generate the javascript friendly array of allowed files types from settings.php config array
 * This will be used in [simpleajaxuploader.js]
 * [EXAMPLE]
 * allowedExtensions: ['jpg', 'jpeg', 'png', 'gif']
 */
if (!function_exists('js_allowedFileTypes')) {

    function js_allowedFileTypes()
    {

        //get $CI instance
        $CI = &get_instance();

        //profiling
        $CI->data['controller_profiling'][] = __function__;

        //check if allow all file types
        if ($CI->config->item('js_allowed_types') === 0) {
            $CI->data['vars']['js_allowed_types'] = '[]';

            //empty array, meaning allow ALL files
            return;
        }

        /*
        * explode array from settings.php config file
        * $config['files_allowed_types'] = 'jpg|flv|gif';
        * create a js compatible file
        */
        $allowed = explode("|", $CI->config->item('files_allowed_types'));

        //loop through and create new string/js array
        $CI->data['vars']['js_allowed_types'] = '';
        for ($i = 0; $i < count($allowed); $i++) {
            $file_extension = strtolower(trim(str_replace("'", '', $allowed[$i])));

            //if $file_extension is valid alphabetic
            if (ctype_alpha($file_extension) || ctype_alnum($file_extension)) {
                $CI->data['vars']['js_allowed_types'] .= "'$file_extension',";
            }
        }

        $CI->data['vars']['js_allowed_types'] = rtrim($CI->data['vars']['js_allowed_types'], ",");

        //final formating
        $CI->data['vars']['js_allowed_types'] = '[' . $CI->data['vars']['js_allowed_types'] . ']';
    }

}

// -- js_fileSizeLimit- -------------------------------------------------------------------------------------------------------
/**
 * Generate the javascript filesize limit (numeric kilobytes)
 * This will be used in [simpleajaxuploader.js]
 * [EXAMPLE]
 * maxSize: 1024
 *
 * 
 * @param	void
 * @return void (sets data to data['vars']['js-file_size_limit'])
 */
if (!function_exists('js_fileSizeLimit')) {

    function js_fileSizeLimit()
    {

        //get $CI instance
        $CI = &get_instance();

        //profiling
        $CI->data['controller_profiling'][] = __function__;

        //explode array from settings.php config file
        $CI->data['vars']['js_file_size_limit'] = floor($CI->config->item('files_max_size'));
    }

}

/* End of file javascript_helper.php */
/* Location: ./application/helpers/javascript_helper.php */
