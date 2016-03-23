<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * NEXTLOOP
 *
 * extends the INPUT library to allow for posted saving search form data as a query string
 *          
 *
 */

class MY_Input extends CI_Input
{

    // -- save_query -------------------------------------------------------------------------------------------------
    /**
     * recieves a search query (data that was submitted in a serch form)
     * converts this into a $_GET type query string
     * saves the query string in the database
     * returns the unique id for the new record
     *
     * 
     * @param	array
     * @return	numeric
     */
    function save_query($search_array = '')
    {

        $CI = &get_instance();

        if (isset($_POST) && is_array($search_array)) {

            //turn array into GET type query string
            $array = http_build_query($search_array);

            //add to database
            $CI->db->query("INSERT INTO search_cache 
                                       (query_string, date_added)
                                        VALUES
                                       ('$array', NOW())");

            //return id of inserted item
            return $CI->db->insert_id();

        } else {

            return 0;
        }
    }

    // -- load_query -------------------------------------------------------------------------------------------------
    /**
     * searches the database for a saved search query by id
     * saves the results in the $_GET superglobal
     * this action mimics the original search query as if its being passed around via the
     * traditional long query url's
     *
     * 
     * @param	array
     * @return	numeric
     */
    function load_query($search_id = '')
    {

        $CI = &get_instance();
        if (is_numeric($search_id)) {
            $rows = $CI->db->get_where('search_cache', array('id' => $search_id))->result();
            if (isset($rows[0])) {
                parse_str($rows[0]->query_string, $_GET);
            }
        }

    }

}

/* End of file My_Input.php */
/* Location: ./application/core/My_Input.php */
