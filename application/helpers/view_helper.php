<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// -----------------------------------------------------------------------------------------------------------------
/**
 * Check Template File - Checks if a given template file exists
 * 
 * @param $template path to template file
 * @return if exists: return given file path; if not: returns 404 error
 */
if (! function_exists('help_template_verify')) {
    function help_verify_template($template = '')
    {
        if (is_file($template)) {
            return $template;
        } else {

            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\
            $message = '<b>File: </b>' . __file__ . '<br/>
                        <b>Function: </b>' . __function__ . '<br/>
                        <b>Line: </b>' . __line__ . '<br/>
                        <b>Notes: </b> Template file could not be found (' . $template . ')';
            //log error
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Template file could not be found ($template)]");
            //disply error
            show_error($message, 500);
            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\

        }
    }
}

if(!function_exists('show_duration')) 
{
    function show_duration($duration)
    {
        //from sec
        $h = floor($duration/3600);
        $m = floor(($duration-$h*3600)/60);
        $s = $duration-$h*3600-$m*60;
        if($h<10) $h='0'.$h;
        if($m<10) $m='0'.$m;
        if($s<10) $s='0'.$s;
        return $h.'h '.$m.'m '.$s.'s';
    }
}

/* End of file view_helper.php */
/* Location: ./application/helpers/view_helper.php */
