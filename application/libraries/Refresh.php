<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
// -- FormProcessor ----------------------------------------------------------------------------------------------
/**
 * DATABASE REFRESH CLASS
 * @package		CodeIgniter
 * @author		NEXTLOOP
 * @since       2014 July
 * @requires    PHP5.3.x

 * 
 * [WHAT IT DOES]
 * ------------------------------------------------------------------------------------------------------------------
 * This class refreshes various records in the database, to reflect changes in status etc
 * Example is it update the milestones table by determining how many tasks has been completed etc
 *
 *
 * [USAGE - MILESTONES]
 * ----------------------------------------------------------------------------------------------------------------
 * $this->refresh->milestones($this->project_id); //or\\ $this->refresh->milestones('all')
 * $this->data['debug'][] = $this->refresh->debug_data; //library debug
 * 
 */
class Refresh
{

    var $ci;
    var $debug_mode;
    var $debug_data;
    var $todays_date;

    // -- __construct ----------------------------------------------------------------------------------------------
    /**
     *
     * 
     * @param	void
     * @return void
     */
    function __construct()
    {

        //ADD CODEIGNITER CORE INSTANCE TO BE ABLE TO USE CODEINITER RESOURCES
        $this->ci = &get_instance();

        //get config debug mode
        $this->debug_mode = $this->ci->config->item('debug_mode');

        //set todays date (mysql format)
        $this->todays_date = date('Y-m-d');

    }

    // -- milestones ----------------------------------------------------------------------------------------------
    /**
     * Refresh/Update milestones
     * Set to [in progress] or [behind schedule] or [completed]
     *
     * 
     * @param numeric/string [$what: expected to be the ID of a single project -OR- set to 'all' for all milestones]
     * @return	null
     */

    function milestones($what = '')
    {

        //set condirional sql
        $conditional_sql = '';

        //exit if $what is not numeric or its not 'all'
        if (!is_numeric($what) && $what != 'all') {
            return;
        }

        //if we are refreshing milestones for a single project, create conditional sql
        if (is_numeric($what)) {
            $conditional_sql = "AND milestones.milestones_project_id = $what";
        }

        //load milestones----------------------------------------------------------------------
        /** Get all milestones that are:
         *     (1) not marked as complete 
         *     (2) have incomplete tasks alocated to them
         */
        //----------sql & benchmarking start----------
        $this->ci->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->ci->db->query("SELECT *, ROUND(IFNULL((tmp.completed_task_count/tmp.task_count*100),0)) as percentage
                                       FROM (SELECT milestones.*, milestones.milestones_id AS id,
                                                    (SELECT COUNT(tasks_id) FROM tasks 
                                                             WHERE tasks_milestones_id = id) AS task_count,
                                                    (SELECT COUNT(tasks_id) FROM tasks
                                                             WHERE tasks_milestones_id = id
                                                             AND tasks_status = 'completed') AS completed_task_count
                                             FROM milestones
                                             WHERE 1 = 1
                                             $conditional_sql) AS tmp");

        $results = $query->result_array();
        $count = $query->num_rows();

        //benchmark/debug
        $this->ci->benchmark->mark('code_end');
        $execution_time = $this->ci->benchmark->elapsed_time('code_start', 'code_end');

        //add to debugging data
        $this->debug_data .= $this->__debugSQL(__line__, __function__, $execution_time, 'Lib: Refresh.php', $results);
        //----------sql & benchmarking end----------

        //update/refresh milestones--------------------------------------------------------------------------------
        /** loop through the array results from above:
         *   (1) Update milestones with tasks 100% completed to 'completed' status 
         *   (2) Update milestones with tasks less than 100% completed to 'in progress' status
         *   (3) Update milestones with tasks less than 100% and with end date that has passed to 'behind schedule'
         *   (4) Update events tables to record any change in status for milestone ::TODO (events sql)
         */

        //lets benchmarck this whole loop for performance
        $this->ci->benchmark->mark('loop_start');

        ///use a different var name to avoid confusion inside loop
        $milestone = $results;

        //start update loop
        for ($i = 0; $i < $count; $i++) {
            if(empty($milestones_end_date) || empty($milestone[$i]['milestones_start_date'])){ continue; }
            //retrieve some needed data
            $percentage = $milestone[$i]['percentage'];
            $milestones_end_date = @strtotime($milestone[$i]['milestones_end_date']);
            $milestones_id = $milestone[$i]['milestones_id'];
            $milestones_events_id = $milestone[$i]['milestones_events_id'];
            $now = @strtotime($this->todays_date);

            /** ----update milestone with 100% tasks completed to 'completed' status---- **/
            if ($percentage == 100) {

                //update milestones
                $this->ci->benchmark->mark('code_start'); //benchmark
                $query = $this->ci->db->query("UPDATE milestones 
                                                      SET milestones_status = 'completed' 
                                                      WHERE milestones_id = $milestones_id");
                $results = $this->ci->db->affected_rows();
                $this->ci->benchmark->mark('code_end'); //benchmark
                $execution_time = $this->ci->benchmark->elapsed_time('code_start', 'code_end'); //benchmark
                $this->debug_data .= $this->__debugSQL(__line__, __function__, $execution_time, 'Refresh.php [update milestones - completed]', $results); //debug

            }

            /** ----update all behind schedule milestone---- */
            if ($percentage < 100 && ($now > $milestones_end_date)) {
                //update milestones
                $this->ci->benchmark->mark('code_start'); //benchmark
                $query = $this->ci->db->query("UPDATE milestones 
                                                      SET milestones_status = 'behind schedule' 
                                                      WHERE milestones_id = '$milestones_id'");
                $results = $this->ci->db->affected_rows();
                $this->ci->benchmark->mark('code_end'); //benchmark
                $execution_time = $this->ci->benchmark->elapsed_time('code_start', 'code_end'); //benchmark
                $this->debug_data .= $this->__debugSQL(__line__, __function__, $execution_time, 'Refresh.php [update milestones - behind schedule]', $results); //debug

            }

            /** ----update all behind schedule milestone---- */
            if ($percentage < 100 && ($now < $milestones_end_date)) {
                //update milestones
                $this->ci->benchmark->mark('code_start'); //benchmark
                $query = $this->ci->db->query("UPDATE milestones 
                                                      SET milestones_status = 'in progress' 
                                                      WHERE milestones_id = '$milestones_id'");
                $results = $this->ci->db->affected_rows();
                $this->ci->benchmark->mark('code_end'); //benchmark
                $execution_time = $this->ci->benchmark->elapsed_time('code_start', 'code_end'); //benchmark
                $this->debug_data .= $this->__debugSQL(__line__, __function__, $execution_time, 'Refresh.php [update milestones - in progress]', $results); //debug

            }

        }

        //end of loop benchmark
        $this->ci->benchmark->mark('loop_end'); //benchmark
        $execution_time = $this->ci->benchmark->elapsed_time('loop_start', 'loop_end'); //benchmark

        //log the performance of this loop
        $this->debug_data .= $this->__debugGeneral(__line__, __function__, $execution_time, "Refresh Milestones Loop [Execution Time: $execution_time]"); //debug

    }

    // -- taskStatus ----------------------------------------------------------------------------------------------
    /**
     * Refresh/Update tasks status
     * Set to [pending] or [behind schedule]
     * Exclude tasks whose status is [completed] from this refresh
     *
     * 
     * @param numeric/string [$what: expected to be the ID of a single project -OR- set to 'all' for all milestones]
     * @return	null
     */

    function taskStatus($what = '')
    {

        //set condirional sql
        $conditional_sql = '';

        //exit if $what is not numeric or its not 'all'
        if (!is_numeric($what) && $what != 'all') {
            return;
        }

        //if we are refreshing tasks for a single project, create conditional sql
        if (is_numeric($what)) {
            $conditional_sql = "AND tasks_project_id = $what";
        }

        //update [behind schedule] tasks-------------------------------------------------------
        /** update all tasks to 'behind schedule' status, that:
         *      - have an end date that is before today
         *      - are not currently marked as 'completed'
         */
        //----------sql & benchmarking start----------
        $this->ci->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->ci->db->query("UPDATE tasks
                                        SET tasks_status = 'behind schedule'
                                        WHERE tasks_status NOT IN ('completed')
                                        AND tasks_end_date < NOW()
                                        $conditional_sql");

        $results = $this->ci->db->affected_rows();

        //----------benchmarking end------------------
        $this->ci->benchmark->mark('code_end');
        $execution_time = $this->ci->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->debug_data .= $this->__debugSQL(__line__, __function__, $execution_time, 'Refresh.php [update tasks:- behind schedule]', $results); //debug

        //update [pending] tasks-------------------------------------------------------
        /** update all tasks to 'pending' status, that:
         *      - have an end date that is beyond today
         *      - are not currently marked as 'completed'
         */

        //----------sql & benchmarking start----------
        $this->ci->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->ci->db->query("UPDATE tasks
                                        SET tasks_status = 'pending'
                                        WHERE tasks_status NOT IN ('completed')
                                        AND tasks_end_date > NOW()
                                        $conditional_sql");

        $results = $this->ci->db->affected_rows();

        //----------benchmarking end------------------
        $this->ci->benchmark->mark('code_end');
        $execution_time = $this->ci->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->debug_data .= $this->__debugSQL(__line__, __function__, $execution_time, 'Refresh.php [update tasks to:- pending]', $results); //debug

    }

    // -- updateProjectPercentage ----------------------------------------------------------------------------------------------
    /**
     * Refresh and update a given projects percentant complete record
     *
     * 
     * @param numeric $project_id project id
     * @return	null
     */

    function updateProjectPercentage($project_id = '')
    {

        //validate data
        if (!is_numeric($project_id)) {
            return 0;
        }

        //load database models
        $this->ci->load->model('milestones_model');
        $this->ci->load->model('projects_model');
        $this->ci->load->model('tasks_model');

        //---------------calculate percentage-------------------
        //calculate the possible [total milestone] percentages (i.e. 5 milestones = [5* 100% = 500%])
        $total_possible_percentage = ($this->ci->milestones_model->countMilestones($project_id, 'all')) * 100; //sum up all the current milestone percentage from all the milestone for this project
        $this->ci->data['debug'][] = $this->ci->milestones_model->debug_data;

        $total_possible_percentage = ($total_possible_percentage <= 0) ? 100 : $total_possible_percentage; //make sure we have something
        $milestones = $this->ci->milestones_model->listMilestones(0, 'results', $project_id);
        $this->ci->data['debug'][] = $this->ci->milestones_model->debug_data;

        $current_percentages_total = 0;
        for ($i = 0; $i < count($milestones); $i++) {
            $current_percentages_total += $milestones[$i]['percentage'];
        }

        //work out the PROJECT progress based on [$current_percentages_total/$total_possible_percentage*100]
        $project_percentage = round(($current_percentages_total / $total_possible_percentage) * 100);
        $project_percentage = (is_numeric($project_percentage)) ? $project_percentage : 0; //return percentage

        //update project progress percentage
        $total_possible_percentage = $this->ci->projects_model->updateProgress($project_id, $project_percentage);
        $this->debug_data .= $this->ci->projects_model->debug_data;

        //if project is now 100% update status to 'completed'
        if ($project_percentage == 100) {
        
            //count tasks - to make sure this is not just a brand new project
            $tasks_count = $this->ci->tasks_model->countTasks($project_id, 'project', ' all', 'all');
            $this->debug_data .= $this->ci->tasks_model->debug_data;

            //update status
            if ($tasks_count > 0) {
                $this->ci->projects_model->updateStatus($project_id, 'completed');
                $this->debug_data .= $this->ci->projects_model->debug_data;
            }
        }

    }

    // -- projectStatus ----------------------------------------------------------------------------------------------
    /**
     * Refresh/Update project status
     * Set to [in progress] or [behind schedule]
     * even if a project has 100% of completed task, the 'completed' status must be manually confirmed (by submit button)
     * a project marked as completed and has new tasks making it less than 100% will be marked as 'in progress'
     *
     * 
     * @param numeric/string [$what: expected to be the ID of a single project -OR- set to 'all' for all milestones]
     * @return	null
     */

    function projectStatus($what = '')
    {

        //TODO - this is incomplete (as of 2 Nov 2014)

        //update [behing schedule] projects-------------------------------------------------------
        /**
         *      - update any project with less than 100%
         *      - which is not already set as 'in progress'
         *      - must not ne behind schedule
         */

        //----------sql & benchmarking start----------
        $this->ci->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->ci->db->query("UPDATE projects SET
                                              projects_status = 'behind schedule'
                                              WHERE project_deadline < NOW()
                                              AND projects_status NOT IN('completed')");

        $results = $this->ci->db->affected_rows();

        //----------benchmarking end------------------
        $this->ci->benchmark->mark('code_end');
        $execution_time = $this->ci->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->debug_data .= $this->__debugSQL(__line__, __function__, $execution_time, 'Refresh.php [fix task timers that are running yet task is completed]', $results); //debug

    }

    // -- basicInvoiceStatus ----------------------------------------------------------------------------------------------
    /**
     * Refresh/Update all invoice statuses. 
     * This is a very basic refresh of invoices which is not very demanding on server resources.
     * It only updates the status based on due dates etc. No payment calculations/sanitization etc are done.
     * A more detailed invoice refreshing routine for all invoices is run as a cron jon. //TODO
     *
     * 
     * @return	null
     */

    function basicInvoiceStatus()
    {

        //update 'overdue' invoices-------------------------------------------------------
        /**
         *      - update any invoice as 'overdue' which ...
         *          (1) has due date before today and is not marked as paid'
         */

        //----------sql & benchmarking start----------
        $this->ci->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->ci->db->query("UPDATE invoices 
                                              SET invoices_status = 'overdue'
                                              WHERE invoices_due_date < NOW()
                                              AND invoices_status NOT IN('paid', 'new')");

        $results = $this->ci->db->affected_rows();

        //----------benchmarking end------------------
        $this->ci->benchmark->mark('code_end');
        $execution_time = $this->ci->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->debug_data .= $this->__debugSQL(__line__, __function__, $execution_time, 'Refresh.php [updating overdue invoices. Affected records:]', $results); //debug

        //fix mistakenly 'overdue' invoices-------------------------------------------------------
        /**
         *      - update invoices as 'due' which ...
         *          (1) have a due date after today and are mistakenly marked as 'overdue'
         */

        //----------sql & benchmarking start----------
        $this->ci->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->ci->db->query("UPDATE invoices 
                                              SET invoices_status = 'due'
                                              WHERE invoices_due_date > NOW()
                                              AND invoices_status = 'overdue'");

        $results = $this->ci->db->affected_rows();

        //----------benchmarking end------------------
        $this->ci->benchmark->mark('code_end');
        $execution_time = $this->ci->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->debug_data .= $this->__debugSQL(__line__, __function__, $execution_time, 'Refresh.php [updating overdue invoices. Affected records:]', $results); //debug

    }

    // -- refreshSingleInvoice ----------------------------------------------------------------------------------------------
    /**
     * Complete invoice [refreshing/recalculating/updating/sanitizing] of a single invoice. 
     * This is normally run when:
     *                        - an invoice is loaded
     *                        - invoice payment is made
     *                        - invoice payment is deleted
     *
     *   (1) Update invoice 'items' (line totals) to ensure the add up correctly
     *   (2) Sum up invoice payments and compare with invoice total. Mark as 'paid / due' accordingly
     *   (3) General due/overdue status update (when invoice is not already fully paid)
     *
     * 
     * @param   numeric   [id: invoice id]
     * @return	null
     */

    function refreshSingleInvoice($id = 0)
    {

        //do we have a valid id
        if ($id < 0) {
            return;
        }

        /* GET INVOICE DETAILS FIRST */
        $invoice = $this->ci->invoices_model->getInvoice($id);
        $this->debug_data .= $this->ci->invoices_model->debug_data;

        /* RATIONALIZE LINE TOTALS*/
        $this->ci->invoices_model->recalculateInvoiceItems($id);
        $this->debug_data .= $this->ci->invoices_model->debug_data;

        /* HAS THIS BEEN FULLY PAID */
        $payments_sum = $this->ci->payments_model->sumInvoicePayments($id);
        $this->debug_data .= $this->ci->payments_model->debug_data;
        $balance_due = $invoice['invoices_amount'] - $payments_sum;

        //update - paid
        if ($invoice['invoices_amount'] != 0 && $balance_due <= 0) {
            $this->ci->invoices_model->updateInvoiceStatus($id, 'paid');
            $this->debug_data .= $this->ci->payments_model->debug_data;
        }

        //update - due & overdue (exclude 'new' unpublished invoices)
        if ($balance_due > 0 && $invoice['invoices_status'] != 'new') {

            //calculate due dats
            $invoice_due = strtotime($invoice['invoices_due_date']);

            //now
            $time_now = time();

            //update as due
            if ($time_now < $invoice_due) {
                $this->ci->invoices_model->updateInvoiceStatus($id, 'due');
                $this->debug_data .= $this->ci->payments_model->debug_data;
            }

            //update as overdue
            if ($time_now > $invoice_due) {
                $this->ci->invoices_model->updateInvoiceStatus($id, 'overdue');
                $this->debug_data .= $this->ci->payments_model->debug_data;
            }

            //run general invoice refresh to get the correct status
            $this->basicInvoiceStatus();
        }

    }

    // -- __debugSQL ----------------------------------------------------------------------------------------------
    /**
     * process MySQL debug data and store return it formatted nicely
     *
     * 
     * @param	mixed (number/string)
     * @return void
     */

    function __debugSQL($line_number = '', $function = '', $execution_time = '', $notes = '', $sql_results = '')
    {

        //is there aany need for mysql data
        $last_query = ($sql_results === '') ? 'N/A' : $this->ci->db->last_query();
        $last_error = ($sql_results === '') ? 'N/A' : $this->ci->db->_error_message();

        //create nice array
        $debug_array = array(
            'file' => __file__,
            'line' => $line_number,
            'function' => $function,
            'execution_time' => $execution_time,
            'notes' => $notes,
            'last_query' => $last_query,
            '_error_message' => $last_error,
            'results' => $sql_results);

        //format with <pre> and return the data
        ob_start();
        echo '<pre>';
        print_r($debug_array);
        echo '</pre>';
        $debug = ob_get_contents();
        ob_end_clean();

        return $debug;

    }

    // -- __debugGenaral ----------------------------------------------------------------------------------------------
    /**
     * process general debug data and store return it formatted nicely
     *
     * 
     * @param	mixed (number/string)
     * @return void
     */

    function __debugGeneral($line_number = '', $function = '', $execution_time = '', $notes = '')
    {

        //create nice array
        $debug_array = array(
            'file' => __file__,
            'line' => $line_number,
            'function' => $function,
            'execution_time' => $execution_time,
            'notes' => $notes);

        //format with <pre> and return the data
        ob_start();
        echo '<pre>';
        print_r($debug_array);
        echo '</pre>';
        $debug = ob_get_contents();
        ob_end_clean();

        return $debug;

    }

}

/* End of file xyz.php */
/* Location: ./application/libraries/xyz.php */
