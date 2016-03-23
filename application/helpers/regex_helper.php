<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// -- regex_is_domain -------------------------------------------------------------------------------------------------
/**
 * Regex: checks if input is a valid domain name
 * @param array $data
 */
if (!function_exists('regex_is_domain')) {
    function regex_is_domain($data = '')
    {

        //get $CI instance
        $CI = &get_instance();

        if (preg_match('/^(http|https|ftp)://([A-Z0-9][A-Z0-9_-]*(?:.[A-Z0-9][A-Z0-9_-]*)+):?(d+)?/?/i', $data)) {
            return true;
        } else {
            return false;
        }
    }
}

// -- regex_is_az123dashes -------------------------------------------------------------------------------------------------
/**
 * Regex: checks if input is standard (letters, numbers, dashes, underscrore)
 *
 * @param array $data
 */
if (!function_exists('regex_is_az123dashes')) {
    function regex_is_az123dashes($data = '')
    {

        //get $CI instance
        $CI = &get_instance();

        if (preg_match('/^[a-zA-Z0-9_-]+$/', $data)) {
            return true;
        } else {
            return false;
        }
    }
}

// -- regex_remove_lines_spaces -------------------------------------------------------------------------------------------------
/**
 * Regex: removes new lines and extra white spaces/tabs etc from a string.
 *        good to use if you have for example strip_tags html formated text and you want it to be readable single line
 *
 * @param array $data
 */
if (!function_exists('regex_remove_lines_spaces')) {
    function regex_remove_lines_spaces($data = '')
    {

        //get $CI instance
        $CI = &get_instance();
        $data = preg_replace('%\n%', " ", $data);
        $data = preg_replace('/\s*$^\s*/m', " ", $data);
        $data = preg_replace('/[ \t]+/', ' ', $data);

        //return
        return $data;

    }
}

/* End of file regex_helper.php */
/* Location: ./application/helpers/regex_helper.php */
