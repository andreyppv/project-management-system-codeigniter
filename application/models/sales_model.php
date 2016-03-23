<?php
ini_set('display_errors', 1);
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all bugs related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Sales_model extends Super_Model
{

    // -- __construct ----------------------------------------------------------------------------------------------
    public function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        // Call the Model constructor
        parent::__construct();
    }

    

   

    public function searchSales($offset = 0, $type = 'search')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //conditional sql
        //determine if any search condition where passed in the search form
        //actual post data is already cached into $this->input->get(), so use that instead of $_post
        if ($this->input->get('client_name')) {
            $client_name = str_replace("'", "", $this->db->escape($this->input->get('client_name')));
            $conditional_sql .= " AND clients_company_name LIKE '%$client_name%'";
        }
        if ($this->input->get('client_email')) {
            $client_email = $this->db->escape($this->input->get('client_email'));
            $conditional_sql .= " AND client_users.client_users_email = $client_email";
        }
        if (is_numeric($this->input->get('client_id'))) {
            $client_id = $this->db->escape($this->input->get('client_id'));
            $conditional_sql .= " AND clients_id = $client_id";
        }

        //create the order by sql additional condition
        //these sorting keys are passed in the url and must be same as the ones used in the controller.
        $sort_order = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_columns = array(
            'sortby_clientid' => 'clients.clients_id',
            'sortby_contactname' => 'client_users.client_users_main_contact',
            'sortby_companyname' => 'clients.clients_company_name',
            'sortby_dueinvoices' => 'unpaid_invoices',
            'sortby_allinvoices' => 'all_invoices',
            'sortby_projects' => 'active_projects');
        $sort_by = (array_key_exists('' . $this->uri->segment(6), $sort_columns)) ? $sort_columns[$this->uri->segment(6)] : 'clients.clients_id';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT leads.*, clients.*, client_users.*, t2.team_profile_full_name,
									(SELECT COUNT(projects.projects_id)
									FROM projects
									WHERE projects.projects_clients_id = leads.leads_clients_id)
									AS all_projects
									FROM leads
									LEFT JOIN team_profile t2 ON t2.team_profile_id = sale_person_id
									LEFT OUTER JOIN clients ON clients.clients_id = leads.leads_clients_id
									LEFT OUTER JOIN client_users ON client_users.client_users_clients_id = leads.leads_clients_id
									AND client_users.client_users_main_contact = 'yes'
                                  ");
        //results (search or rows)
        //rows are used by pagination class & results are used by tbs block merge
        if ($type == 'search') {
            $results = $query->result_array();
			
			foreach($results as $key => $value)
			{

				if($results[$key][leads_clients_id] == "0" )
				{
					$results[$key][leads_status] = "Lead";
					$results[$key][all_projects] = "";
				}
				//if lead is related with clients, overwrite name/email/telephone with main contact data
				else 
				{
					$results[$key][leads_status] = "Client";
					$results[$key][leads_name] = $results[$key][client_users_full_name].' ('.$results[$key][client_users_job_position_title].')';
					$results[$key][leads_company] = $results[$key][clients_company_name];
					$results[$key][leads_email] = $results[$key][client_users_email];
					$results[$key][leads_telephone] = $results[$key][client_users_telephone];
					
					
				}
			}
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
	// -- getEvents ----------------------------------------------------------------------------------------------
    /**
     * retrieve all events for a lead
     *
     *
     * @param numeric $lead_id
     * @param string $id_type 'single-project', 'project-list' [project list is comma seperated]
     * @return array
     */
    public function getEvents($lead_id = '', $id_type = 'single-lead')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['timeline_limit'])) ? $this->data['settings_general']['timeline_limit'] : 100;

        //validate id
        if ($id_type == 'single-lead') {

            //validate lead id
            if (! is_numeric($lead_id)) {
                $this->__debugging(__line__, __function__, 0, "Invalid Data [lead id=$lead_id]", '');
                return false;
            }

            //escape params items
            $lead_id = $this->db->escape($lead_id);

            //conditional sql for single lead
            $conditional_sql .= " AND lead_events_lead_id = $lead_id";
        }


        //validate id
        if ($id_type == 'lead-list') {
            //conditional sql for single lead
            $conditional_sql .= " AND lead_events_lead_id IN($lead_id)";       
        }
        
                
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        $query = $this->db->query("SELECT lead_events.*, team_profile.*
                                          FROM lead_events
                                          LEFT OUTER JOIN team_profile
                                          ON lead_events.lead_events_user_id = team_profile.team_profile_id 
                                          WHERE deleted='0' 
                                          $conditional_sql
                                          ORDER BY lead_events_id DESC
                                          LIMIT $limit");

        //other results
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

    public function getLeadDetails($leads_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['timeline_limit'])) ? $this->data['settings_general']['timeline_limit'] : 100;

             
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        $query = $this->db->query("SELECT t1.*, t2.team_profile_full_name
								FROM leads t1
								LEFT JOIN team_profile t2 ON t2.team_profile_id = sale_person_id
								WHERE id = $leads_id ");

        //other results
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

    public function getEventDetails($event_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['timeline_limit'])) ? $this->data['settings_general']['timeline_limit'] : 100;

             
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        $query = $this->db->query("SELECT *
                                          FROM lead_events
                                          WHERE lead_events_id = $event_id");

        //other results
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


	public function deleteEvent($event_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

      
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        $query = $this->db->query("UPDATE lead_events SET deleted = '1' WHERE lead_events_id = $event_id ");

        //other results
			
			
        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }

	public function saveLeadNote($lead_id, $note)
	{
		//profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        $query = $this->db->query("UPDATE leads SET leads_description = '".mysql_real_escape_string($note)."' WHERE id = $lead_id ");

        //other results
			
			
        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
	}


	public function addLead($leads_name, $leads_company, $leads_telephone, $leads_email, $leads_www, $leads_description, $leads_value, $leads_hot, $leads_lost, $sales_person='')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
          //_____SQL QUERY_______
          $query = $this->db->query("INSERT INTO leads (
                                            leads_name,
                                            leads_company,
                                            leads_telephone,
                                            leads_email,
                                            leads_www,
                                            leads_description,
                                            leads_value,
                                            leads_hot,
                                            leads_lost,
                                            leads_source,
                                            sale_person_id
                                            )VALUES(
                                             '".mysql_real_escape_string($leads_name)."',
                                            '".mysql_real_escape_string($leads_company)."',
                                            '".mysql_real_escape_string($leads_telephone)."',
                                            '".mysql_real_escape_string($leads_email)."',
                                            '".mysql_real_escape_string($leads_www)."',
                                            '".mysql_real_escape_string($leads_description)."',
                                            '".mysql_real_escape_string($leads_value)."',
                                            '".mysql_real_escape_string($leads_hot)."',
                                            '".mysql_real_escape_string($leads_lost)."',
                                            '4',
                                            '".mysql_real_escape_string($sales_person)."'
                                            )");
          $results = $this->db->insert_id();
          
         

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


	public function addLeadEvent($id, $type, $date, $date_next, $description)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $this->db->insert('lead_events', array(
            'lead_events_lead_id'   => $id,
            'lead_events_date'      => $date,
            'lead_events_date_next' => $date_next,
            'lead_events_type'      => $type,
            'lead_events_details'   => $description,
            'lead_events_user_id'   => $this->data['vars']['my_id']
        ));
        
        $results = $this->db->insert_id();

        
        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        
        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        
        //----------sql & benchmarking end----------

        
        //---return
        return (bool)$results;
        
    }
    
    public function deleteLead($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (! is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [tasks_id=$id]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting task(s) failed (tasks_id: $id is invalid)]");
            return false;
        }


        //escape params items
        $id = $this->db->escape($id);


        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM leads
                                          WHERE 1 = 1
                                          AND id = $id
                                          $conditional_sql");

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

    public function editLeadEvent($id, $type, $date, $date_next, $description, $edit)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $this->db->where('lead_events_id', $edit);
        $this->db->update('lead_events', array(
                'lead_events_date'      => $date,
                'lead_events_date_next' => $date_next,
                'lead_events_type'      => $type,
                'lead_events_details'   => $description
        ));

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return true;
    }

	public function editLead($lead_id, $leads_name, $leads_company, $leads_telephone, $leads_email, $leads_www, $leads_description, $leads_value='', $leads_hot='', $leads_lost='', $sales_person=0)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
          //_____SQL QUERY_______
          $query = $this->db->query("UPDATE leads  SET 
                                            leads_name='".mysql_real_escape_string($leads_name)."',
                                            leads_company='".mysql_real_escape_string($leads_company)."',
                                            leads_telephone='".mysql_real_escape_string($leads_telephone)."',
                                            leads_email='".mysql_real_escape_string($leads_email)."', 
                                            leads_www='".mysql_real_escape_string($leads_www)."', 
                                            leads_description='".mysql_real_escape_string($leads_description)."',
                                            leads_value='".mysql_real_escape_string($leads_value)."',
                                            leads_hot='".mysql_real_escape_string($leads_hot)."',
                                            leads_lost='".mysql_real_escape_string($leads_lost)."',
											sale_person_id='".mysql_real_escape_string($sales_person)."'
                                            WHERE id='".$lead_id."'");
          $results = $this->db->insert_id();
          
		//----------benchmarking end------------------
          $this->benchmark->mark('code_end');
          $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

          //debugging data
          $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
          //----------sql & benchmarking end----------

          //---return
              return true;
    }
	
	public function toggleRow($id, $row)
	{
		// Retrieve current value
		$this->db->select($row);
		$this->db->where('id', $id);
		$newValue = $this->db->get('leads')->row()->$row? 0: 1;
		
		// Set new value
		$this->db->where('id', $id);
		$this->db->update( 'leads', array($row => $newValue) );
		
		return $newValue;
	}
}
/* End of file sales_model.php */
/* Location: ./application/models/sales_model.php */

