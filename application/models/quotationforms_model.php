<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all quotation forms related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Quotationforms_model extends Super_Model
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

    // -- addQuotationForm ----------------------------------------------------------------------------------------------
    /**
     * add a new quotation form
     *
     * @param	void
     * @return	mixed (id/bool)
     */

    function addQuotationForm()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }

        //created by id
        $my_id = $this->data['vars']['my_id'];

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO quotationforms (
                                          quotationforms_title,
                                          quotationforms_code,
                                          quotations_created_by_id,
                                          quotationforms_date_created
                                          )VALUES(
                                          $quotationforms_title,
                                          $quotationforms_code,
                                          $my_id,
                                          NOW())");

        //other results
        $results = $this->db->insert_id(); //last item insert id

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if ($results > 0) {
            return $results;
        } else {
            return false;
        }
    }

    // -- searchQuotationForms ----------------------------------------------------------------------------------------------
    /**
     * search/list quotation form
     *
     * @param numeric $offset: page 'number' 
     * @param string $type search/count 
     * @return array
     */

    function searchQuotationForms($offset = 0, $type = 'search')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //---is there any search data-----------------
        if (is_numeric($this->input->get('quotationforms_id'))) {
            $quotationforms_id = $this->db->escape($this->input->get('quotationforms_id'));
            $conditional_sql .= " AND quotationforms.quotationforms_id = $quotationforms_id";
        }
        if ($this->input->get('quotationforms_title')) {
            $quotationforms_title = str_replace("'", "", $this->db->escape($this->input->get('quotationforms_title')));
            $conditional_sql .= " AND quotationforms.quotationforms_title LIKE '%$quotationforms_title%'";
        }
        if ($this->input->get('quotationforms_status')) {
            $quotationforms_status = $this->db->escape($this->input->get('quotationforms_status'));
            $conditional_sql .= " AND quotationforms.quotationforms_status = $quotationforms_status";
        }
        if ($this->input->get('quotations_created_by_id')) {
            $quotations_created_by_id = $this->db->escape($this->input->get('quotations_created_by_id'));
            $conditional_sql .= " AND quotationforms.quotations_created_by_id = $quotations_created_by_id";
        }

        //create the order by sql additional condition
        //these sorting keys are passed in the url and must be same as the ones used in the controller.
        $sort_order = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_columns = array(
            'sortby_id' => 'quotationforms.quotationforms_id',
            'sortby_title' => 'quotationforms.quotationforms_title',
            'sortby_status' => 'quotationforms.quotationforms_status',
            'sortby_date' => 'quotationforms.quotationforms_date_created');
        $sort_by = (array_key_exists(''.$this->uri->segment(6), $sort_columns)) ? $sort_columns[$this->uri->segment(6)] : 'quotationforms.quotationforms_id';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search' || $type == 'results') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT quotationforms.*, team_profile.*
                                             FROM quotationforms
                                             LEFT OUTER JOIN team_profile
                                             ON quotationforms.quotations_created_by_id = team_profile.team_profile_id
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

    // -- getQuotationForm ----------------------------------------------------------------------------------------------
    /**
     * retrieve a quotation form from the database
     *
     * @param numeric $id: item for id 
     * @param string $status enabled/disabled 
     * @return	array
     */

    function getQuotationForm($id = '', $status = 'enabled')
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
        $status = $this->db->escape($status);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM quotationforms 
                                          WHERE quotationforms_id = $id
                                          AND quotationforms_status = $status");

        $results = $query->row_array();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }

    // -- editQuotationForm ----------------------------------------------------------------------------------------------
    /**
     * edit quotation
     *
     * @return	bool
     */

    function editQuotationForm()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE quotationforms
                                          SET
                                          quotationforms_code = $quotationforms_code,
                                          quotationforms_title = $quotationforms_title,
                                          quotationforms_status = $quotationforms_status
                                          WHERE quotationforms_id = $quotationforms_id");

        $results = $this->db->insert_id(); //last item insert id

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //update
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

    // -- countForms ----------------------------------------------------------------------------------------------
    /**
     * count quotation forms
     *
     * 
     * @param status $status
     * @return numeric
     */

    function countForms($status = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //conditional sql
        if (ctype_alpha($status)) {
            $status = $this->db->escape($status);
            $conditional_sql .= " AND quotationforms_status = $status";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM quotationforms
                                          WHERE 1 = 1
                                          $conditional_sql");

        $results = $query->num_rows(); //count rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    // -- allQuotationForms ----------------------------------------------------------------------------------------------
    /**
     * load all quotation forms
     *
     * @param string $status enabled/disabled 
     * @return array
     */

    function allQuotationForms($status = 'enabled')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape params items
        $status = $this->db->escape($status);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM quotationforms 
                                          WHERE quotationforms_status = $status");

        $results = $query->result_array();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }
}

/* End of file quotationforms_model.php */
/* Location: ./application/models/quotationforms_model.php */
