<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * NEXTLOOP
 *
 * Extended this to process the messages coming from controllers better:
 *          - replace html tags with appropriate \n
 *          - pretty formtting of log file
 *          
 *
 */

class MY_Log extends CI_Log
{

    function MY_Log()
    {

        parent::__construct();

    }

    // --------------------------------------------------------------------

    /**
     * Write Log File
     *
     * Generally this function will be called using the global log_message() function
     *
     * @param	string	the error level
     * @param	string	the error message
     * @param	bool	whether the error is a native PHP error
     * @return	bool
     */
    public function write_log($level = 'error', $msg, $php_error = false)
    {

        /*
        |-----------------------------------------------------------------------------------------------------
        | NEXTLOOP - FORMAT MESSAGE COMING FROM CONTROLLERS WHICH HAVE HTML
        |-----------------------------------------------------------------------------------------------------
        | - removes html tags from log messages coming in from controllers
        | - adds new lines and some extra formatting
        |
        |
        */
        $new_line_html = array(
            '<br>',
            '<p>',
            '<br />',
            '<li>');
        //replace new lines
        $msg = str_ireplace($new_line_html, "\n", $msg);
        //strip all other remaing html
        $msg = strip_tags($msg);
        $msg = str_replace('[FUNCTION', "\n[FUNCTION", $msg);
        $msg = str_replace('[LINE', "\n[LINE", $msg);
        $msg = str_replace('[MESSAGE', "\n[MESSAGE", $msg);
        $msg = str_replace('-->', "\n-->", $msg);
        $msg = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $msg); //removes blank lines
        $msg = $msg . "\n-----------------------------------------------------------------------------------";
        $msg = $msg . "-------------------------------------------\n";

        if ($this->_enabled === false) {
            return false;
        }

        $level = strtoupper($level);

        if (!isset($this->_levels[$level]) or ($this->_levels[$level] > $this->_threshold)) {
            return false;
        }

        $filepath = $this->_log_path . 'log-' . date('Y-m-d') . '.php';
        $message = '';

        if (!file_exists($filepath)) {
            $message .= "<" . "?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?" . ">\n\n";
        }

        if (!$fp = @fopen($filepath, FOPEN_WRITE_CREATE)) {
            return false;
        }

        $message .= $level . ' ' . (($level == 'INFO') ? ' -' : '-') . ' ' . date($this->_date_fmt) . "  \n" . $msg . "\n";

        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);

        @chmod($filepath, FILE_WRITE_MODE);
        return true;
    }

}

/* End of file My_log.php */
/* Location: ./application/libraries/My_log.php */
