<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all invoices related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Invoices_model extends Super_Model
{

    var $debug_methods_trail;
    var $number_of_rows;

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

    // -- addInvoice ----------------------------------------------------------------------------------------------
    /**
     * Add a new invoice
     *
     * @return	new record id
     */
    public function addInvoice($data = array())
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //get all post data and escape it
        if($data)
        {
            foreach ($data as $key => $value)
            {
                $$key = $value;
            }
        }
        else
        {
            foreach ($_POST as $key => $value)
            {
                $$key = $this->input->post($key);
            }
        }

        if(!isset($invoices_status)) $invoices_status = 'new';
        
        if(!isset($invoices_pretax_amount)) $invoices_pretax_amount = 0;
        if(!isset($invoices_tax_amount))    $invoices_tax_amount = 0;
        if(!isset($invoices_amount))        $invoices_amount = 0;
        if(!isset($invoices_tax_rate))      $invoices_tax_rate = 0;

        if(!isset($invoices_custom_id))
            $invoices_custom_id = random_string('alnum', 7).'-'.random_string('alnum', 2);

        //generate a unique invoice id
        if(!isset($invoices_unique_id))
            $invoices_unique_id = '';

        while ($invoices_unique_id == '')
        {
            $invoices_unique_id = random_string('alnum', 6);
            $row = $this->db->query("select 1 from invoices where invoices_unique_id='".$invoices_unique_id."'")->row();
            if($row)
            {
                $invoices_unique_id = '';
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $this->db->insert('invoices', array(
            'invoices_project_id'     => $invoices_project_id,
            'invoices_clients_id'     => $invoices_clients_id,
            'invoices_date'           => $invoices_date,
            'invoices_due_date'       => $invoices_due_date,
            'invoices_status'         => $invoices_status,
            'invoices_notes'          => $invoices_notes,
            'invoices_created_by_id'  => $invoices_created_by_id,
            'invoices_events_id'      => $invoices_events_id,
            'invoices_unique_id'      => $invoices_unique_id,
            'invoices_custom_id'      => $invoices_custom_id,

            'invoices_pretax_amount' => $invoices_pretax_amount,
            'invoices_tax_amount'    => $invoices_tax_amount,
            'invoices_amount'        => $invoices_amount,
            'invoices_tax_rate'      => $invoices_tax_rate
        ));

        $results = $this->db->insert_id();

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }

    // -- searchInvoices ----------------------------------------------------------------------------------------------
    /**
     * list/search invoice items
     *
     * 
     * @param numeric $offset pagination
     * @param string $type: 'search', 'count'
     * @param mixed $id: 'all', 'numeric id', ''
     * @param mixed $list_by: 'all', 'client', 'project'
     * @return array
     */

    public function searchInvoices($offset = 0, $type = 'search', $id = '', $list_by = 'all', $status = 'all')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //---------------SEARCH FORM CONDITONAL STATMENTS------------------
        if (is_numeric($this->input->get('invoices_id'))) {
            $invoices_id = $this->db->escape($this->input->get('invoices_id'));
            $conditional_sql .= " AND invoices.invoices_id = $invoices_id";
        }
        if ($this->input->get('start_date')) {
            $start_date = $this->db->escape($this->input->get('start_date'));
            $conditional_sql .= " AND invoices.invoices_date >= $start_date";
        }
        if ($this->input->get('end_date')) {
            $end_date = $this->db->escape($this->input->get('end_date'));
            $conditional_sql .= " AND invoices.invoices_date <= $end_date";
        }
        if (is_numeric($this->input->get('invoices_project_id'))) {
            $invoices_project_id = $this->db->escape($this->input->get('invoices_project_id'));
            $conditional_sql .= " AND invoices.invoices_project_id = $invoices_project_id";
        }
        if (is_numeric($this->input->get('invoices_clients_id'))) {
            $invoices_clients_id = $this->db->escape($this->input->get('invoices_clients_id'));
            $conditional_sql .= " AND invoices.invoices_clients_id = $invoices_clients_id";
        }
        if ($this->input->get('invoices_status')) {
            $invoices_status = $this->db->escape($this->input->get('invoices_status'));
            $conditional_sql .= " AND invoices.invoices_status = $invoices_status";
        }

        //---------------CLIENT - PROJECT - ALL -- INVOICES------------------
        if ($list_by != 'all' && is_numeric($id)) {
            switch ($list_by) {

                case 'client':
                    $conditional_sql .= " AND invoices.invoices_clients_id = $id";
                    break;

                case 'project':
                    $conditional_sql .= " AND invoices.invoices_project_id = $id";
                    break;
            }
        }

        //---------------INVOICE STATUS CONDITIONAL--------------------------
        if (in_array($status, array(
            'new',
            'paid',
            'due',
            'overdue'))) {
            $conditional_sql .= " AND invoices.invoices_status = '$status'";
        }

        //---------------URL QUERY - ORDER BY STATMENTS-------------------------
        $sort_order = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_columns = array(
            'sortby_id' => 'invoices.invoices_id',
            'sortby_date' => 'invoices.invoices_date',
            'sortby_due_date' => 'invoices.invoices_due_date',
            'sortby_amount' => 'invoices.invoices_amount',
            'sortby_amount_paid' => 'payments_sum',
            'sortby_amount_due' => 'amount_due',
            'sortby_status' => 'invoices.invoices_status');
        $sort_by = (array_key_exists(''.$this->uri->segment(6), $sort_columns)) ? $sort_columns[$this->uri->segment(6)] : 'invoices.invoices_id';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //---------------IF SEARCHING - LIMIT FOR PAGINATION----------------------
        if ($type == 'search' || $type == 'results') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //CLIENT-PANEL: limit to this clients data
        if (is_numeric($this->client_id) || $this->uri->segment(1) == 'client') {
            $client_id = $this->client_id;
            $conditional_sql .= " AND invoices.invoices_clients_id = '$client_id'";//show only clients invoices
            $conditional_sql .= " AND invoices.invoices_status NOT IN('new')";//hide new
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT invoices.*, clients.*, projects.*,payments.*,
                                               IFNULL((SELECT SUM(payments_amount)
                                                       FROM payments
                                                       WHERE payments.payments_invoice_id = invoices.invoices_id),0)
                                                       AS payments_sum,
                                               IFNULL((SELECT invoices.invoices_amount - payments_sum),0) AS amount_due
                                            FROM invoices
                                            LEFT OUTER JOIN clients
                                            ON clients.clients_id = invoices.invoices_clients_id
                                            LEFT OUTER JOIN projects
                                            ON projects.projects_id = invoices.invoices_project_id
                                            LEFT JOIN payments
                                            ON payments.payments_invoice_id = invoices.invoices_id
                                            WHERE 1 = 1
                                            $conditional_sql
                                            GROUP BY invoices.invoices_id
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

    // -- viewInvoices ----------------------------------------------------------------------------------------------
    /**
     * view invoices for a particular project
     *
     * 
     * @param numeric $offset: pagination
     * @param string $type search / count
     * @param numeric $project_id project id
     * @param status $status new/paid/due/overdue
     * @return array
     */

    public function viewInvoices($offset = 0, $type = 'search', $project_id = '', $status = 'all')
    {
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc"); 
$prepared = $db->prepare("SELECT projects_clients_id FROM projects WHERE projects_id = ?");
$prepared->execute(array($project_id));
$row = $prepared->fetch(PDO::FETCH_ASSOC);
$clientid = $row['projects_clients_id'];

$prepared = $db->prepare("SELECT client_users_id FROM client_users WHERE client_users_clients_id = ?");
$prepared->execute(array($clientid));
$rows = $prepared->fetchAll();
$size = sizeof($rows);
$clientid_user = $rows[$size-1]['client_users_id'];

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //if no valie client id, return false
        if (! is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [project id=$project_id]", '');
            return false;
        }

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //conditional sql for invoice status
        if (ctype_alpha($status)) {
            //valid status param values
            $valid_status = array(
                'new',
                'due',
                'paid',
                'overdue',
                'all');

            //conditional sql for status param
            if (in_array($status, $valid_status)) {
                //skip 'all' status
                if ($status != 'all') {
                    $conditional_sql .= " AND invoices_status = '$status'";
                }
            }
        }

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search' || $type == 'results') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT invoices.*,
                                                (SELECT SUM(payments_amount) FROM payments
                                                        WHERE payments.payments_invoice_id = invoices.invoices_id)
                                                        AS payments_sum
                                          FROM invoices
                                          WHERE (invoices.invoices_clients_id = $clientid OR invoices.invoices_clients_id = $clientid_user)
                                          $conditional_sql
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

    // -- countInvoices ----------------------------------------------------------------------------------------------
    /**
     * counts invoices of various status
     *
     * 
     * @param numeric $id optional
     * @param string $id_reference reference for the provided ID | void (optional)
     * @param string $status all | open | due | overdue | void (optional)
     * @return numeric [rows count]
     */

    public function countInvoices($id = '', $id_reference = 'project', $status = 'all')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valid id, return false
        if (! is_numeric($id) && $id != '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //conditional query for the ID param, using the $id_reference
        if (is_numeric($id)) {
            switch ($id_reference) {

                case 'project':
                    $conditional_sql .= " AND invoices_project_id = $id";
                    break;

                case 'client':
                    $conditional_sql .= " AND invoices_clients_id = $id";
                    break;
            }
        }

        //conditional query for $type
        if (ctype_alpha($status)) {
            //valid status param values
            $valid_status = array(
                'new',
                'paid',
                'overdue',
                'all',
                'partpaid');

            //conditional sql for status param
            if (in_array($status, $valid_status)) {
                //skip 'all' status
                if ($status != 'all') {
                    $conditional_sql .= " AND invoices_status = '$status'";
                }
            }
        }

        //all unpaid invoices
        if ($status == 'all-unpaid') {
            $conditional_sql .= " AND invoices_status NOT IN('paid', 'new')";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM invoices 
                                          WHERE invoices_status NOT IN ('new')
                                          $conditional_sql");

        $results = $query->num_rows(); //count rows

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

    // -- sumInvoices ----------------------------------------------------------------------------------------------
    /**
     * sum invoices of various status
     *
     * @param numeric $id | void] (optional)
     * @param string $id_reference reference for the provided ID | void (optional)
     * @param string $status all | open | due | overdue | void (optional)
     * @return numeric [rows count]
     */

    public function sumInvoices($id = '', $id_reference = 'project', $status = 'all')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valid id, return false
        if (! is_numeric($id) && $id != '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //conditional query for the ID param, using the $id_reference
        if (is_numeric($id)) {
            switch ($id_reference) {

                case 'project':
                    $conditional_sql .= " AND invoices_project_id = $id";
                    break;

                case 'client':
                    $conditional_sql .= " AND invoices_clients_id = $id";
                    break;
            }
        }

        //conditional query for $type
        if (ctype_alpha($status)) {
            //valid status param values
            $valid_status = array(
                'open',
                'paid',
                'overdue',
                'all');

            //conditional sql for status param
            if (in_array($status, $valid_status)) {
                //skip 'all' status
                if ($status != 'all') {
                    $conditional_sql .= " AND invoices_status = '$status'";
                }
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT SUM(invoices_amount) as sum
                                          FROM invoices 
                                          WHERE 1 = 1
                                          $conditional_sql");

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

    // -- getInvoice ----------------------------------------------------------------------------------------------
    /**
     * return a single invoice record based on its ID (join the project details for this invoice)
     *
     * @param numeric $id
     * @return array
     */

    public function getInvoice($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [invoice id=$id]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT invoices.*, projects.*, clients.*
                                          FROM invoices
                                          LEFT OUTER JOIN projects
                                          ON projects.projects_id = invoices.invoices_project_id
                                          LEFT OUTER JOIN clients
                                          ON clients.clients_id = invoices.invoices_clients_id
                                          WHERE invoices.invoices_id = $id");

        $results = $query->row_array(); //single row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }

    // -- getInvoiceByID ----------------------------------------------------------------------------------------------
    /**
     * return invoices array
     *    
     * @param numeric $id 
     * @return array
     */
    public function getInvoiceByID($id, $type = 'custom_id')
    {
        $this->debug_methods_trail[] = __function__;

        //declare

        $this->db->select('*')->from('invoices');

        switch ($type) {
            default:
            case 'custom_id':
                $this->db->where('invoices_custom_id', $id);
                break;
            case 'unique_id':
                $this->db->where('invoices_unique_id', $id);
                break;
            case 'id':
                $this->db->where('invoices_id', $id);
                break;
        }
        $query = $this->db->get();

        $results = $query->row_array();

        //debugging data
        $this->__debugging(__line__, __function__, 0, __class__, $results);

        return $results;
    }


    // -- editInvoice ----------------------------------------------------------------------------------------------
    /**
     * edit an invoices basic details
     *
     * @param void
     * @return numeric [affected rows]
     */

    public function editInvoice()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if task id value exists in the post data
        if (! is_numeric($this->input->post('invoices_id'))) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [invoice id: is not numeric/is unavailable]", '');
            return false;
        }

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE invoices
                                          SET 
                                          invoices_date = $invoices_date,
                                          invoices_due_date = $invoices_due_date,
                                          invoices_notes = $invoices_notes,
                                          invoices_tax_rate = $invoices_tax_rate,
                                          invoices_custom_id = $invoices_custom_id
                                          WHERE invoices_id = $invoices_id");

        $results = $this->db->affected_rows(); //affected rows

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

    // -- recalculateInvoiceItems ----------------------------------------------------------------------------------------------
    /**
     * recalculate the totals/maths for each invoice item (quantity * rate) for a particular invoice
     *
     * @param string $id groups name [age: users age]
     * @return array
     */

    public function recalculateInvoiceItems($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE invoice_products
                                          SET invoice_products_total = (invoice_products_rate * invoice_products_quantity)
                                          WHERE 
                                          invoice_products_invoice_id = $id");

        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

    // -- getInvoicePretaxTotal ----------------------------------------------------------------------------------------------
    /**
     * sum up all the invoice products/items totals for an invoice
     *
     * @param numeric $id: invoice id]
     * @return mixed (numeric / false)
     */

    public function getInvoicePretaxTotal($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT COALESCE(SUM(invoice_products_total),0) as sum
                                          FROM invoice_products 
                                          WHERE invoice_products_invoice_id = $id
                                          $conditional_sql");
        $results = $query->row_array();
        $results = $results['sum']; //get sum results

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
            return false;
        }
    }

    // -- updateInvoiceTotal ----------------------------------------------------------------------------------------------
    /**
     * update invoice total with new supplied figure
     *
     * 
     * @param string $id invoice id
     * @param numeric $invoice_pretax_amount: invoice total before tax
     * @param numeric $tax_amount: amount of tax applied to invoice
     * @param numeric $new_total: new invoice total
     * @return array
     */

    public function updateInvoiceTotal($id = '', $invoice_pretax_amount = '', $tax_amount = '', $new_total = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id) || ! is_numeric($invoice_pretax_amount) || ! is_numeric($tax_amount) || ! is_numeric($new_total)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id or new_total=$new_total]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);
        $invoice_pretax_amount = $this->db->escape($invoice_pretax_amount);
        $tax_amount = $this->db->escape($tax_amount);
        $new_total = $this->db->escape($new_total);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE invoices
                                          SET 
                                          invoices_amount = $new_total,
                                          invoices_pretax_amount = $invoice_pretax_amount,
                                          invoices_tax_amount = $tax_amount
                                          WHERE 
                                          invoices_id = $id");

        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

    // -- dueInvoices ----------------------------------------------------------------------------------------------
    /**
     * return an array of due or overdue invoices, with amount due included in results rows
     * to show all system wide due, dueInvoices('all', '', 'due');
     * to show all system wide due, dueInvoices('all', '', 'overdue');
     * to show a clients due, dueInvoices('23', 'client', 'overdue');
     * to show a projects due, dueInvoices('23', 'project', 'overdue');
     * 
     * @param mixed $id client/project id --OR-- 'all'
     * @param string $id_type client/project
     * @param string $status due/overdue
     * @return array
     */

    public function dueInvoices($id = '', $id_type = '', $status = 'due')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //is this for client
        if (is_numeric($id) && $id_type == 'client') {
            $conditional_sql .= " AND invoices.invoices_clients_id = $id";
        }

        //is this for project invoices
        if (is_numeric($id) && $id_type == 'project') {
            $conditional_sql .= " AND invoices.invoices_project_id = $id";
        }

        if ($status == 'overdue') {
            $conditional_sql .= " AND invoices.invoices_due_date < NOW()";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT invoices.*, payments.*,
                                               IFNULL((SELECT SUM(payments_amount) FROM payments
                                                              WHERE payments.payments_invoice_id = invoices.invoices_id),0)
                                                              AS paid,
                                               (SELECT invoices.invoices_amount - paid) AS amount_due
                                          FROM invoices
                                          LEFT JOIN payments
                                          ON payments.payments_invoice_id = invoices.invoices_id
                                          WHERE invoices.invoices_status NOT IN('paid')
                                          $conditional_sql
                                          GROUP BY invoices.invoices_id");

        $results = $query->result_array(); //multi row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    // -- updateInvoiceStatus ----------------------------------------------------------------------------------------------
    /**
     * manually update the status of a single invoice
     *
     * @param numeric $id invoice is
     * @param string $status new invoice status [valid status are: 'new', 'due', 'overdue', 'paid']
     * @return bool
     */

    public function updateInvoiceStatus($id = '', $status = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($id)) {
            return false;
        }

        //vaidate status
        if (! in_array($status, array(
            'new',
            'due',
            'overdue',
            'paid'))) {
            return false;
        }

        //escape

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $this->db->where('invoices_id', $id);
        $this->db->update('invoices', array('invoices_status' => $status));

        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return (bool)$results;
    }

    // -- updateLastEmailed ----------------------------------------------------------------------------------------------
    /**
     * update the last emailed date on the invoice to NOW()
     *
     * @param numeric $id: invoice is
     * @return	bool
     */

    public function updateLastEmailed($id = '')
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

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE invoices
                                          SET invoices_last_emailed = NOW()
                                          WHERE invoices_id = '$id'");

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

    // -- deleteInvoice ----------------------------------------------------------------------------------------------
    /**
     * delete an invoice based on invoice id
     *
     * @param numeric invoice_id
     * @return	bool
     */

    public function deleteInvoice($invoice_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (! is_numeric($invoice_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [invoice id=$invoice_id]", '');
            return false;
        }

        //escape params items
        $invoice_id = $this->db->escape($invoice_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM invoices
                                          WHERE invoices_id = $invoice_id");

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

    // -- bulkDelete ----------------------------------------------------------------------------------------------
    /**
     * bulk delete based on list of project ID's
     * typically used when deleting project/s 
     * 
     * @param string $projects_list a mysql array/list formatted projects list [e.g. 1,2,3,4]
     * @return bool
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
            if (! is_numeric(trim($lists[$i]))) {
                //log error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting invoices, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM invoices
                                          WHERE invoices_project_id IN($projects_list)");
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

    // -- validateClientOwner ----------------------------------------------------------------------------------------------
    /**
     * confirm if a given client owns this requested item
     * 
     * @param numeric $resource_id
     * @param numeric $client_id
     * @return bool
     */

    public function validateClientOwner($resource_id = '', $client_id)
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
                                          FROM invoices 
                                          WHERE invoices_id = $resource_id
                                          AND invoices_clients_id = $client_id");

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

    // -- getInvoiceID ----------------------------------------------------------------------------------------------
    /**
     * returns the invoice ID, based on invoice unique id
     *
     * @param numeric $unique_invoice_id]
     * @return bool
     */

    public function getInvoiceID($unique_invoice_id)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if ($unique_invoice_id == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Input Data", '');
            return false;
        }

        //escape params items
        $unique_invoice_id = $this->db->escape($unique_invoice_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM invoices 
                                          WHERE invoices_unique_id = $unique_invoice_id");

        $results = $query->row_array();
        $results = $results['invoices_id'];

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if (is_numeric($results)) {
            return $results;
        } else {
            return false;
        }
    }
}

/* End of file invoices_model.php */
/* Location: ./application/models/invoices_model.php */