<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all milestones related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Quotations_model extends Super_Model
{

    var $debug_methods_trail;
    var $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------
    function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        // Call the Model constructor
        parent::__construct();
    }

    // -- allQuotationsCounts ----------------------------------------------------------------------------------------------
    /**
     * count various quotations
     *
     * @param numeric $client_id optional; if provided, count will be limited to that clients 
     * @return	array
     */
    function allQuotationsCounts($client_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //is this for a client
        if (is_numeric($client_id)) {
            $conditional_sql .= " AND quotations_client_id = '$client_id'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT (SELECT COUNT(quotations_id)
                                                  FROM quotations
                                                  WHERE quotations_status = 'new') AS new,
                                          (SELECT COUNT(quotations_id)
                                                  FROM quotations
                                                  WHERE quotations_status = 'completed') AS completed,
                                          (SELECT COUNT(quotations_id)
                                                  FROM quotations
                                                  WHERE quotations_status = 'pending') AS pending,
                                          (SELECT COUNT(quotations_id)
                                                  FROM quotations
                                                  WHERE quotations_status NOT IN ('completed')) AS all_open,                                                  
                                          (SELECT COUNT(quotations_id)
                                                  FROM quotations) AS all_quotations
                                          FROM quotations
                                          WHERE 1 = 1
                                          $conditional_sql
                                          LIMIT 1");

        //other results
        $results = $query->row_array(); //single row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    // -- searchQuotations ----------------------------------------------------------------------------------------------
    /**
     * search quotations
     *
     * @param string $offset pagination
     * @param string $type: search/count]
     * @return array
     */

    function searchQuotations($offset = 0, $type = 'search')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //---is there any search data-----------------
        if ($this->input->get('quotations_date')) {
            $quotations_date = $this->db->escape($this->input->get('quotations_date'));
            $conditional_sql .= " AND quotations.quotations_date = $quotations_date";
        }
        if ($this->input->get('quotations_status')) {
            $quotations_status = $this->db->escape($this->input->get('quotations_status'));
            $conditional_sql .= " AND quotations.quotations_status = $quotations_status";
        }
        if (is_numeric($this->input->get('quotations_client_id'))) {
            $quotations_client_id = $this->db->escape($this->input->get('quotations_client_id'));
            $conditional_sql .= " AND quotations.quotations_client_id = $quotations_client_id";
        }
        if (is_numeric($this->input->get('quotations_id'))) {
            $quotations_id = $this->db->escape($this->input->get('quotations_id'));
            $conditional_sql .= " AND quotations.quotations_id = $quotations_id";
        }
        if ($this->input->get('quotations_form_title')) {
            $quotations_form_title = str_replace("'", "", $this->db->escape($this->input->get('quotations_form_title')));
            $conditional_sql .= " AND quotations.quotations_form_title LIKE '%$quotations_form_title%'";
        }

        //create the order by sql additional condition
        //these sorting keys are passed in the url and must be same as the ones used in the controller.
        $sort_order = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_columns = array(
            'sortby_id' => 'quotations.quotations_id',
            'sortby_date' => 'quotations.quotations_date',
            'sortby_form_title' => 'quotations.quotations_form_title',
            'sortby_status' => 'quotations.quotations_status');
        $sort_by = (array_key_exists(''.$this->uri->segment(5), $sort_columns)) ? $sort_columns[$this->uri->segment(5)] : 'quotations.quotations_id';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search' || $type == 'results') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //CLIENT-PANEL: limit to this clients data
        if (is_numeric($this->client_id) || $this->uri->segment(1) == 'client') {
            $client_id = $this->client_id;
            $conditional_sql .= " AND quotations_client_id = '$client_id'";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                             FROM quotations
                                             WHERE 1 = 1
                                             $conditional_sql
                                             $sorting_sql
                                             $limiting");
        //results (search or rows)
        //rows are used by pagination class & results are used by tbs block merge
        if ($type == 'search' || $type == 'results') {
            $results = $query->result_array();
        } else {
            $results = $query->num_rows();
        }

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }

    // -- getQuotation ----------------------------------------------------------------------------------------------
    /**
     * retrieve a single quotation
     *
     * @param numeric $id: quotation id]
     * @return	array
     */

    function getQuotation($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT quotations.*, team_profile.*
                                          FROM quotations
                                          LEFT OUTER JOIN team_profile
                                          ON quotations.quotations_reviewed_by_id = team_profile.team_profile_id
                                          WHERE quotations_id = $id");

        $results = $query->row_array();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    // -- deleteClientsQuotations ----------------------------------------------------------------------------------------------
    /**
     * delete all quotations for a client, based on client id
     * 
     * @param numeric $id: client id]
     * @return	bool
     */

    function deleteClientsQuotations($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //flow control
        $next = true;

        //validate id
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM quotations
                                          WHERE quotations_client_id = $id");
        }
        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

    // -- deleteQuotation ----------------------------------------------------------------------------------------------
    /**
     * delete a single quotation, based on quotation id
     * @param numeric $id: quotation id 
     * @return bool
     */

    function deleteQuotation($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //flow control
        $next = true;

        //validate id
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM quotations
                                          WHERE quotations_id = $id");
        }
        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

    // -- updateQuotation ----------------------------------------------------------------------------------------------
    /**
     * updates a quotation with a price and admin notes. If price is greater then zero, status will become 'completed'
     * 
     * @param numeric $quotation_id
     * @return	bool
     */

    function updateQuotation($quotation_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($quotation_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [quotation id=$quotation_id]", '');
            return false;
        }

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }

        //new status - if amount is more than zero then its completed
        if ($this->input->post('quotations_amount') > 0) {
            $quotations_status = 'completed';
        } else {
            $quotations_status = 'pending';

        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE quotations
                                          SET 
                                          quotations_amount = $quotations_amount,
                                          quotations_admin_notes = $quotations_admin_notes,
                                          quotations_status = '$quotations_status',
                                          quotations_reviewed_date = NOW(),
                                          quotations_reviewed_by_id = $quotations_reviewed_by_id
                                          WHERE quotations_id = $quotations_id");

        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

    // -- saveQuotation ----------------------------------------------------------------------------------------------
    /**
     * save a quotation form
     * 
     * @param string $existing_client yes/no
     * @return array
     */

    function saveQuotation($existing_client = 'yes')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }

        //serialize and base64 encode the post data, to make it safe for saving
        $quotations_post_data = base64_encode(serialize($_POST));

        //CLIENT-PANEL: limit to this clients data
        if (is_numeric($this->client_id) || $this->uri->segment(1) == 'client') {
            $quotations_client_id = $this->client_id;
            $quotations_by_client = 'yes';
        } else {
            $quotations_by_client = 'no';
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO quotations (
                                          quotations_date,
                                          quotations_form_title,
                                          quotations_post_data,
                                          quotations_form_data,
                                          quotations_by_client,
                                          quotations_client_id,
                                          quotations_company_name,
                                          quotations_name,
                                          quotations_email,
                                          quotations_telephone                                         
                                          )VALUES(
                                          NOW(),
                                          $quotations_form_title,
                                          '$quotations_post_data',
                                          $quotations_form_data,
                                          '$quotations_by_client',
                                          '$quotations_client_id',
                                          $quotations_company_name,
                                          $quotations_name,
                                          $quotations_email,
                                          $quotations_telephone)");

        $results = $this->db->insert_id(); //last item insert id

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return for INSERT--- ([$results = $this->db->insert_id()]
        if ($results > 0) {
            return $results;
        } else {
            return false;
        }
    }

    // -- validateClientOwner ----------------------------------------------------------------------------------------------
    /**
     * confirm if a given client owns this requested item
     * 
     * @param numeric $resource_id]
     * @param numeric $client_id
     * @return bool
     */

    function validateClientOwner($resource_id = '', $client_id)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($resource_id) || ! is_numeric($client_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Input Data", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM quotations 
                                          WHERE quotations_id = $resource_id
                                          AND quotations_client_id = $client_id");

        $results = $query->num_rows(); //count rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if ($results > 0) {
            return true;
        } else {
            return false;
        }
    }

}

/* End of file quotations_model.php */
/* Location: ./application/models/quotations_model.php */
