<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Email_queue_model extends Super_Model
{

    public $debug_methods_trail;
    public $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------

    public function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        // Call the Model constructor
        parent::__construct();
    }

    // -- addToQueue ----------------------------------------------------------------------------------------------
    /**
     * add an email to the queue
     * @return	array
     */

    public function addToQueue($sqldata = array())
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //validate
        if (!is_array($sqldata)) {
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Email Queue - invalid data sqldata()]");
            return false;
        }

        //validate
        if ($sqldata['email_queue_email'] == '' || $sqldata['email_queue_subject'] == '' || $sqldata['email_queue_message'] == '') {
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Email Queue - Adding email to database failed, invalid data]");
            return false;
        }

        //get email data
        $email_queue_email = $this->db->escape($sqldata['email_queue_email']);
        $email_queue_subject = $this->db->escape($sqldata['email_queue_subject']);
        $email_queue_message = $this->db->escape($sqldata['email_queue_message']);

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO email_queue (
                                               email_queue_email,
                                               email_queue_subject,
                                               email_queue_message,
                                               email_queue_date
                                               )VALUES(
                                               $email_queue_email,
                                               $email_queue_subject,
                                               $email_queue_message,
                                               NOW())");
        $results = $this->db->insert_id();

        $this->__debugging(__line__, __function__, '', __class__, $results); //----------sql & benchmarking end----------

        //---return
        return $results;
    }

    // -- getEmailBatch ----------------------------------------------------------------------------------------------
    /**
     * - get a list of emails in the queue for sending in batches
     * @param numeric $batch_limit maximum email to get
     * @return	bool
     */

    public function getEmailBatch($batch_limit = 10)
    {

        //validate
        if (!is_numeric($batch_limit)) {
            $batch_limit = 10;
        }

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * FROM email_queue 
                                            ORDER BY email_queue_id ASC
                                            LIMIT $batch_limit");

        //other results
        $results = $query->result_array(); //multi row array

        //debugging data
        $this->__debugging(__line__, __function__, '', __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }

    // -- deleteFromQueue ----------------------------------------------------------------------------------------------
    /**
     * - delete some emails from the queue
     * @param string $emails_list comma separated list (1,2,3) of email id's
     * @return	bool
     */

    public function deleteFromQueue($emails_list = '')
    {

        //validate
        if ($emails_list == '') {
            return false;
        }

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM email_queue 
                                          WHERE email_queue_id IN($emails_list)");

        //other results
        $results = $this->db->affected_rows();

        //debugging data
        $this->__debugging(__line__, __function__, '', __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }
}

/* End of file version_model.php */
/* Location: ./application/models/version_model.php */
