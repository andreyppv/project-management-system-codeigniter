<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all payments related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Payments_model extends Super_Model
{

    public $debug_methods_trail;
    public $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------
    public function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        // Call the Model constructor
        parent::__construct();
    }

    // -- getClientID ----------------------------------------------------------------------------------------------
    /**
     * return Client ID by Project ID
     *    
     * @param numeric $id 
     * @return array
     */
    public function getClientID($project_id = 0)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [project_id=$project_id]", '');
            return false;
        }

        //escape params items
        $project_id = $this->db->escape($project_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT projects_clients_id FROM projects WHERE projects_id = $project_id");

        $results = $query->result_array(); //single row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        if(empty($results[0]['projects_clients_id'])) {
            $results[0]['projects_clients_id'] = 0;
        }
        //return results
        return (int)$results[0]['projects_clients_id'];
    }

    // -- getClientID ----------------------------------------------------------------------------------------------
    /**
     * return Client ID by Project ID
     *    
     * @param numeric $client_id 
     * @param numeric $amount_paid 
     * @param boolen  $is_credit 
     * @return array
     */
    public function changeClientBalance($client_id, $amount_paid, $is_credit = TRUE)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($client_id) || !is_numeric($amount_paid)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [client_id={$client_id}, amount_paid=$amount_paid]", '');
            return false;
        }

        //escape params items
        $project_id = $this->db->escape($project_id);
        if($is_credit == FALSE) $amount_paid = -1 * $amount_paid;

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE clients SET credit_amount_remaining=credit_amount_remaining+{$amount_paid} WHERE clients_id = {$client_id}");

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------
    }


    // -- getInvoicePayments ----------------------------------------------------------------------------------------------
    /**
     * return all payments attached to a particulat invoice. Based on invoice ID
     *    
     * @param numeric $id 
     * @return array
     */

    public function getInvoicePayments($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [invoice id=$id]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM payments
                                          WHERE payments_invoice_id = $id");

        $results = $query->result_array(); //single row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }

    // -- sumInvoicePayments ----------------------------------------------------------------------------------------------
    /**
     * sum payments for a particular invoices
     *   
     * @param numeric $id (optional)
     * @return numeric  [sum of payments]
     */

    public function sumInvoicePayments($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valid id, return false
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT SUM(payments_amount) AS sum
                                          FROM payments 
                                          WHERE payments_invoice_id = $id");

        $results = $query->row_array(); //single row array
        $results = $results['sum'];

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if (is_numeric($results)) {
            return $results;
        } else {
            return 0;
        }
    }

    // -- periodicPaymentsCount ----------------------------------------------------------------------------------------------
    /**
     * returns array of payments (COUNT) made 'today', 'this_week', 'this_month', 'last_month', 'this_year', 'last_year'
     * results can be for 'all_payments' or a numeric 'id for a given client or project
     *
     * @param numeric $id this can be client id pr project id
     * @param string $id_type his can be 'client' / 'project'
     * @return array
     */

    public function periodicPaymentsCount($id = '', $id_type = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //are we searching for all payments or for client/project
        if (is_numeric($id) && ($id_type == 'client' || $id_type == 'project')) {

        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT (SELECT COUNT(payments_id)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m%d') = DATE_FORMAT(NOW(),'%Y%m%d')) AS today,
                                          (SELECT COUNT(payments_id)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')) AS this_month,
                                          (SELECT COUNT(payments_id)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m') = DATE_FORMAT(NOW() - INTERVAL 1 MONTH,'%Y%m')) AS last_month,
                                          (SELECT COUNT(payments_id)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y') = DATE_FORMAT(NOW(),'%Y')) AS this_year,
                                          (SELECT COUNT(payments_id)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y') = DATE_FORMAT(NOW() - INTERVAL 1 YEAR,'%Y')) AS last_year
                                          FROM payments
                                          LIMIT 1");
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

    // -- periodicPaymentsSum ----------------------------------------------------------------------------------------------
    /**
     * returns array of payments (SUM) made 'today', 'this_week', 'this_month', 'last_month', 'this_year', 'last_year'
     * results can be for 'all_payments' or a numeric 'id for a given client or project
     *
     * @param numeric $id this can be client id pr project id
     * @param string $id_type this can be 'client' / 'project'
     * @return rray
     */

    public function periodicPaymentsSum($id = '', $id_type = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //are we searching for all payments or for client/project
        if (is_numeric($id) && ($id_type == 'client' || $id_type == 'project')) {

        }

        //conditional sql
        if ($id_type == 'client') {
            $conditional_sql = " AND payments_client_id = '$id'";
        }
        if ($id_type == 'project') {
            $conditional_sql = " AND payments_project_id = '$id'";
        }
        
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT (SELECT SUM(payments_amount)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m%d') = DATE_FORMAT(NOW(),'%Y%m%d')
                                            $conditional_sql) AS today,
                                          (SELECT SUM(payments_amount)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m%d') = DATE_FORMAT(NOW() - INTERVAL 1 DAY,'%Y%m%d')
                                            $conditional_sql) AS yesterday,
                                          (SELECT SUM(payments_amount)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')
                                            $conditional_sql) AS this_month,
                                          (SELECT SUM(payments_amount)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m') = DATE_FORMAT(NOW() - INTERVAL 1 MONTH,'%Y%m')
                                            $conditional_sql) AS last_month,
                                          (SELECT SUM(payments_amount)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y') = DATE_FORMAT(NOW(),'%Y')
                                            $conditional_sql) AS this_year,
                                          (SELECT SUM(payments_amount)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y') = DATE_FORMAT(NOW() - INTERVAL 1 YEAR,'%Y')
                                            $conditional_sql) AS last_year
                                          FROM payments
                                          LIMIT 1");
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

    // -- addPayment ----------------------------------------------------------------------------------------------
    /**
     * add a new payment
     *
     *
     * @param array $thedata normally the $_post array
     * @return array
     */

    public function addPayment($thedata = array())
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //turn array into vars
        foreach ($thedata as $key => $value) {
            $$key = $this->db->escape($value);
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO payments (
                                          payments_invoice_id,
                                          payments_project_id,
                                          payments_client_id,
                                          payments_amount,
                                          payments_currency_code,
                                          payments_transaction_id,
                                          payments_date,
                                          payments_method,
                                          payments_notes
                                          )VALUES(
                                          $payments_invoice_id,
                                          $payments_project_id,
                                          $payments_client_id,
                                          $payments_amount,
                                          $payments_currency_code,
                                          $payments_transaction_id,
                                          NOW(),
                                          $payments_method,
                                          $payments_notes)");

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

    // -- searchPayments ----------------------------------------------------------------------------------------------
    /**
     * list/search payments made
     *
     *
     * @param numeric $offset
     * @param   string $type 'search', 'count'
     * @param   mixed $id'all', 'numeric id', ''
     * @param   mixed $list_by 'all', 'client', 'project'
     * @return  mixed table array | bool (false)]
     */

    public function searchPayments($offset = 0, $type = 'search', $id = '', $list_by = 'all', $sort_order = '')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //---------------SEARCH FORM CONDITONAL STATMENTS------------------
        if (is_numeric($this->input->get('payments_id'))) {
            $payments_id = $this->db->escape($this->input->get('payments_id'));
            $conditional_sql .= " AND payments.payments_id = $payments_id";
        }
        if ($this->input->get('payments_transaction_id')) {
            $payments_transaction_id = $this->db->escape($this->input->get('payments_transaction_id'));
            $conditional_sql .= " AND payments.payments_transaction_id = $payments_transaction_id";
        }
        if ($this->input->get('payments_method')) {
            $payments_method = $this->db->escape($this->input->get('payments_method'));
            $conditional_sql .= " AND payments.payments_method = $payments_method";
        }
        if ($this->input->get('payment_date')) {
            $payment_date = $this->db->escape($this->input->get('payment_date'));
            $conditional_sql .= " AND payments.payments_date = $payment_date";
        }
        if ($this->input->get('start_date')) {
            $start_date = $this->db->escape($this->input->get('start_date'));
            $conditional_sql .= " AND payments.payments_date >= $start_date";
        }
        if ($this->input->get('end_date')) {
            $end_date = $this->db->escape($this->input->get('end_date'));
            $conditional_sql .= " AND payments.payments_date <= $end_date";
        }
        if (is_numeric($this->input->get('payments_project_id'))) {
            $payments_project_id = $this->db->escape($this->input->get('payments_project_id'));
            $conditional_sql .= " AND payments.payments_project_id = $payments_project_id";
        }
        if (is_numeric($this->input->get('payments_client_id'))) {
			$conditional_sql .= " AND payments.payments_client_id = " . $this->input->get('payments_client_id');
        }
        if (is_numeric($this->input->get('payments_invoice_id'))) {
            $payments_invoice_id = $this->db->escape($this->input->get('payments_invoice_id'));
            $conditional_sql .= " AND payments.payments_invoice_id = $payments_invoice_id";
        }

        //---------------CLIENT - PROJECT - ALL -- INVOICES------------------
        if ($list_by != 'all' && is_numeric($id)) {
            switch ($list_by) {

                case 'client':
					//mod by Tomasz
                    //$conditional_sql .= " AND payments.payments_client_id = " . $this->session->userdata('client_users_id');
					$conditional_sql .= " AND payments.payments_client_id = $id";
					//end by Tomasz
                    break;

                case 'project':
                    $conditional_sql .= " AND payments.payments_project_id = $id";
                    break;
            }
        }

        //---------------URL QUERY - ORDER BY STATMENTS-------------------------
        if(!$sort_order)
            $sort_order = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_columns = array(
            'sortby_id' => 'payments.payments_id',
            'sortby_date' => 'payments.payments_date',
            'sortby_amount' => 'payments.payments_amount',
            'sortby_method' => 'payments.payments_method',
            'sortby_project' => 'payments.payments_project_id',
            'sortby_invoice' => 'payments.payments_invoice_id',
            'sortby_client' => 'payments.invoices_status');
        $sort_by = (array_key_exists('' . $this->uri->segment(6), $sort_columns)) ? $sort_columns[$this->uri->segment(6)] : 'payments.payments_id';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //---------------IF SEARCHING - LIMIT FOR PAGINATION----------------------
        if ($type == 'search' || $type == 'results') {
            $limiting = "LIMIT $limit OFFSET $offset ";
        }

        //clients dashboard limitation
        if (is_numeric($this->session->userdata('client_users_clients_id'))) {
            $conditional_sql .= " AND payments.payments_client_id = " . $this->session->userdata('client_users_clients_id');
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT payments.*, clients.*, projects.*
                                            FROM payments
                                            LEFT OUTER JOIN clients
                                            ON clients.clients_id = payments.payments_client_id
                                            LEFT OUTER JOIN projects
                                            ON projects.projects_id = payments.payments_project_id
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
        //print_r($results);

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }

    // -- getByTransactionID ----------------------------------------------------------------------------------------------
    /**
     * retrieve a payment based on its payment transaction ID
     *
     *
     * @param   string $transaction_id transaction id 
     * @return  array
     */

    public function getByTransactionID($transaction_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if ($transaction_id == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data ($transaction_id)", '');
            return false;
        }

        //escape params items
        $transaction_id = $this->db->escape($transaction_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //clients dashboard limitation
        if (is_numeric($this->session->userdata('client_users_clients_id'))) {
            $conditional_sql .= " AND payments.payments_client_id = " . $this->session->userdata('client_users_id');
        }

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM payments 
                                          WHERE payments_transaction_id = $transaction_id");

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

    // -- updatePaymentStatus ----------------------------------------------------------------------------------------------
    /**
     * update payment status (normally used by IPN updates)
     *
     * @param string $status status
     * @param string $transaction_id  transaction id
     * @return bool
     */

    public function updatePaymentStatus($transaction_id = '', $status = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if ($status == '' || $transaction_id == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data ($status) ($transaction_id)", '');
            return false;
        }

        //escape params items
        $status = $this->db->escape($status);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE payments
                                          SET 
                                          payments_transaction_status = $status,
                                          WHERE payments_transaction_id = $transaction_id");

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


    // -- deletePayment ----------------------------------------------------------------------------------------------
    /**
     * delete invoice payment
     * @param numeric $id reference id of item(s)
     * @return  bool
     */

    public function deletePayment($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting invoice item failed (id: $id is invalid)]");
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM payments
                                          WHERE payments_id = $id");

        $results = $this->db->affected_rows(); //affected rows

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if ($results > 0 || $transaction_result === true) {
            return true;
        } else {
            return false;
        }
    }

    // -- bulkDelete ----------------------------------------------------------------------------------------------
    /**
     * bulk delete based on list of project ID's
     * typically used when deleting project/s 
     *
     *
     * @param   string $projects_list a mysql array/list formatted projects list [e.g. 1,2,3,4]
     * @return  bool
     */

    public function bulkDelete($projects_list = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //flow control
        $next = true;

        //sanity check - ensure we have a valid projects_list, with only numeric id's
        $lists = explode(',', $projects_list);
        for ($i = 0; $i < count($lists); $i++) {
            if (!is_numeric(trim($lists[$i]))) {
                //log error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting payments, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM payments
                                          WHERE payments_project_id IN($projects_list)");
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

}

/* End of file payments_model.php */
/* Location: ./application/models/payments_model.php */
