<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Sms_queue_model extends Super_Model
{

    public $debug_methods_trail;

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
     * add the sms to the queue
     * @return	array
     */

    public function addToQueue($sqldata = array())
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //validate
        if (!is_array($sqldata)) {
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: SMS Queue - invalid data sqldata()]");
            return false;
        }

        //validate
        if (!$sqldata['sms_queue_telephone'] || !$sqldata['sms_queue_message']) {
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: SMS Queue - Adding sms to database failed, invalid data]");
            return false;
        }
        $sqldata['sms_queue_object_type'] = $this->db->escape($sqldata['sms_queue_object_type']) ;
        $sqldata['sms_queue_object_id'] = $this->db->escape($sqldata['sms_queue_object_id']) ;
        $sqldata['sms_queue_telephone'] = $this->db->escape($sqldata['sms_queue_telephone']) ;
        $sqldata['sms_queue_message'] = $this->db->escape($sqldata['sms_queue_message']) ;

        //_____SQL QUERY_______
        $sql = "
            INSERT INTO sms_queue (
                sms_queue_object_type,
                sms_queue_object_id,
                sms_queue_telephone,
                sms_queue_message,
                sms_queue_date_create
            ) VALUES (
                ".$sqldata['sms_queue_object_type'].",
                ".$sqldata['sms_queue_object_id'].",
                ".$sqldata['sms_queue_telephone'].",
                ".$sqldata['sms_queue_message'].",
                NOW()
            )
        ";

        $query = $this->db->query($sql);
        $results = $this->db->insert_id();

        $this->__debugging(__line__, __function__, '', __class__, $results); //----------sql & benchmarking end----------

        //---return
        return $results;
    }

    // -- getSmsBatch ----------------------------------------------------------------------------------------------
    /**
     * - get a list of sms in the queue for sending in batches
     * @param numeric $batch_limit maximum sms to get
     * @return	bool
     */

    public function getSmsBatch($batch_limit = 10)
    {
        //validate
        if (!is_numeric($batch_limit)) {
            $batch_limit = 10;
        }

        //_____SQL QUERY_______
        $query = $this->db->query("
            SELECT * FROM sms_queue 
            WHERE status = 0
            ORDER BY sms_queue_id ASC
            LIMIT $batch_limit
        ");

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
     * - delete some sms from the queue
     * @param string $sms_queue_id_list array of sms_queue_id's
     * @return	bool
     */

    public function deleteFromQueue($sms_queue_id_list = array())
    {

        //validate
        if (!is_array($emails_list)) {
            return false;
        }

        //_____SQL QUERY_______
        $query = $this->db->query(
            "UPDATE FROM sms_queue SET status = 1 WHERE sms_queue_id IN (?)",
            array($sms_queue_id_list)
        );

        //other results
        $results = $this->db->affected_rows();

        //debugging data
        $this->__debugging(__line__, __function__, '', __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }
}

/* End of file sms_queue_model.php */
/* Location: ./application/models/sms_queue_model.php */
