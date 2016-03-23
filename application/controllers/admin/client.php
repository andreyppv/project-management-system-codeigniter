<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Client related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Client extends MY_Controller
{

    /**
     * constructor method
     */
    public function __construct()
    {

        parent::__construct();

        //profiling::

        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'client.html';

        //css settings
        $this->data['vars']['css_submenu_clients'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_clients'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_client_profile'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-user"></i>';

        //PERMISSIONS CHECK - GENERAL
        if ($this->data['permission']['view_item_clients'] != 1) {
            redirect('/admin/error/permission-denied');
        }

    }

    /**
     * This is our re-routing function and is the inital function called
     *
     */
    function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //uri - action segment
        $action = $this->uri->segment(4);

        //uri - client id
        $this->client_id = $this->uri->segment(3);
        $this->data['vars']['client_id'] = $this->client_id;

        //start by loading clients details
        $this->__clientDetails();

        //client optional fileds
        $this->__optionalFormFieldsDisplay();

        //create pulldown lists
        $this->__pulldownLists();

        //re-route to correct method
        switch ($action) {

            case 'projects':
                $this->data['vars']['css_tabsmenu_client_projects'] = 'active'; //menu
                $this->__clientProjects();
                break;

            case 'invoices':
                $this->data['vars']['css_tabsmenu_client_invoices'] = 'active'; //menu
                $this->__clientInvoices();
                break;

            case 'payments':
                $this->data['vars']['css_tabsmenu_client_payments'] = 'active'; //menu
                $this->__clientPayments();
                break;

            case 'users':
                $this->data['vars']['css_tabsmenu_client_users'] = 'active'; //menu
                $this->__clientusers();
                break;

            case 'profile':
                $this->data['vars']['css_tabsmenu_client_profile'] = 'active'; //menu
                $this->__clientProfile();
                break;

            case 'edit-profile':
                $this->data['vars']['css_tabsmenu_client_profile'] = 'active'; //menu
                $this->__editProfile();
                break;

            case 'add-invoice':
                $this->data['vars']['css_tabsmenu_client_invoices'] = 'active'; //menu
                $this->__addInvoice();
                break;

            case 'add-user':
                $this->data['vars']['css_tabsmenu_client_users'] = 'active'; //menu
                $this->__addClientUser();
                break;

            default:
                $this->data['vars']['css_tabsmenu_client_projects'] = 'active'; //menu
                $this->__clientProjects();
                break;
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * load clients front page details
     *
     * @return	bool
     */
    function __clientDetails()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get client details
        if ($next) {
            $this->data['reg_fields'][] = 'client';
            $this->data['fields']['client'] = $this->clients_model->clientDetails($this->client_id);
            $this->data['debug'][] = $this->files_model->debug_data;

            //is this a valid client
            if (! $this->data['fields']['client']) {

                //client not found - redirect
                redirect('/admin/error/not-found');

            } else {

                //page heading
                $this->data['vars']['page_heading'] = $this->data['fields']['client']['clients_company_name'];

                //visibility
                $this->data['visible']['wi_show_client'] = 1;

            }
        }

        //get primary contact
        if ($next) {
            $this->data['reg_fields'][] = 'primary_contact';
            $this->data['fields']['primary_contact'] = $this->users_model->clientPrimaryUser($this->client_id);
            $this->data['debug'][] = $this->users_model->debug_data;
        }

        //various stats for the side menu
        if ($next) {
            //completed projects
            $this->data['vars']['client_count_completed_projects'] = $this->projects_model->countProjects($this->client_id, 'client', 'completed');
            $this->data['debug'][] = $this->projects_model->debug_data;

            //open projects
            $this->data['vars']['client_count_open_projects'] = $this->projects_model->countProjects($this->client_id, 'client', 'all open');
            $this->data['debug'][] = $this->projects_model->debug_data;

            //unpaid invoices
            $this->data['vars']['client_count_unpaid_invoices'] = $this->invoices_model->countInvoices($this->client_id, 'client', 'all-unpaid');
            $this->data['debug'][] = $this->invoices_model->debug_data;

            //paid invoices
            $this->data['vars']['client_count_paid_invoices'] = $this->invoices_model->countInvoices($this->client_id, 'client', 'paid');
            $this->data['debug'][] = $this->invoices_model->debug_data;

            //payments this month & this year
            $result = $this->payments_model->periodicPaymentsSum($this->client_id, 'client');
            $this->data['debug'][] = $this->payments_model->debug_data;
            $this->data['vars']['client_payments_this_month'] = (isset($result['this_month']))? $result['this_month'] : 0;
            $this->data['vars']['client_payments_this_year'] = (isset($result['this_year']))? $result['this_year'] : 0;
			
			chdir("__freshbooksapi");
        require_once("__freshbooksinit.php");
        chdir("..");
		
		$startDate = strtotime('-1 year');
		$startDate = date('Y-m-d', $startDate);		
		$endDate = date('Y-m-d');
		
		$invoices = $this->getInvoices($startDate,$endDate,$this->data['fields']['client']['freshbooksclientid']);
		$this->data['vars']['paid_year'] = $invoices;
		
		$startDate = strtotime('-1 month');
		$startDate = date('Y-m-d', $startDate);		
		$endDate = date('Y-m-d');
		
		$invoices = $this->getInvoices($startDate,$endDate,$this->data['fields']['client']['freshbooksclientid']);
		$this->data['vars']['paid_month'] = $invoices;
		
		$this->data['vars']['paid'] = $this->countInvoices($this->data['fields']['client']['freshbooksclientid'], 'paid');
		$this->data['vars']['unpaid'] = $this->countInvoices($this->data['fields']['client']['freshbooksclientid'], 'inpaid');
		
        }

    }

	function getInvoices($startDate,$endDate,$staffid)
	{
		chdir("__freshbooksapi");
        require_once("__freshbooksinit.php");
        chdir("..");
		
		$invoices = getInvoicesForClientId($startDate,$endDate,$staffid);
		$i = 0;
		foreach ($invoices[1][invoices][invoice] as $key => $value) {
	
		$bob = $invoices[1][invoices][invoice][$key][lines][line];
		
			foreach ($bob as $key2 => $value) {
				$amount = $amount + $bob[$key2][amount];
			}
	
		
		$i++;
		}
		return $amount;
	}

	function countInvoices($staffid,$status)
	{
		chdir("__freshbooksapi");
        require_once("__freshbooksinit.php");
        chdir("..");
		
		$invoices = getInvoicesForClientIdByStatus($staffid,$status);
		$i = 0;
		foreach ($invoices[1][invoices][invoice] as $key => $value) {
			
		$i++;
		}
		return $i;
	}

    /**
     * load & edit a clients profile.
     * profile information is already provided by __clientDetails()
     *
     */
    function __clientProfile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //visibility
        $this->data['visible']['wi_client_profile'] = 1;

        //if form post, use post data instead to populate the form
        if ($action = $this->uri->segment(4) == 'edit-profile') {
            //skip these
            $skip = array('clients_id', 'clients_date_created');

            //add rest to the tbs 'client' field
            foreach ($_POST as $key => $value) {
                if (! in_array($key, $skip)) {
                    $this->data['fields']['client'][$key] = $value;
                }

            }
        }

    }

    /**
     * load all of a clients payments
     */
    function __clientPayments()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * admin/client/1/payments/asc/sortby_id/2
        * (2)->controller
        * (3)->client id
        * (4)->router
        * (5)->sort_order
        * (6)->sort_by_column
        * (7)->offset       
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //PERMISSIONS CHECK
        //uri segments
        $client_id = $this->uri->segment(3);
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_due_date' : $this->uri->segment(6);
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0; //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'client_payments';
        $this->data['blocks']['client_payments'] = $this->payments_model->searchPayments($offset, 'search', $client_id, 'client');
        $this->data['debug'][] = $this->payments_model->debug_data; //count results rows - used by pagination class
        $rows_count = $this->payments_model->searchPayments($offset, 'count', $client_id, 'client');
        $this->data['debug'][] = $this->payments_model->debug_data; //pagination
        $config = pagination_default_config();
        $config['base_url'] = site_url("admin/client/$client_id/payments/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 7; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links(); //sorting links for menus on the top of the table
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_id',
            'sortby_date',
            'sortby_method',
            'sortby_amount',
            'sortby_project',
            'sortby_invoice',
            'sortby_client');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/client/$client_id/payments/$link_sort_by/$column/$offset");
        }

        //visibility
        if ($rows_count > 0) {
            //show side menu
            $this->data['visible']['wi_client_payments'] = 1;
        } else {
            //show mothing found
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_payments_found']);
        }

    }

    /**
     * load all of a clients users
     */
    function __clientUsers()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * admin/client/1/users/asc/sortby_id/2
        * (2)->controller
        * (3)->client id
        * (4)->router
        * (5)->sort_order
        * (6)->sort_by_column
        * (7)->offset       
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //PERMISSIONS CHECK
        //uri segments
        $client_id = $this->uri->segment(3);
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_due_date' : $this->uri->segment(6);
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0; //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'client_users';
        $this->data['blocks']['client_users'] = $this->users_model->searchUsers($offset, 'search', $client_id);
        $this->data['debug'][] = $this->users_model->debug_data; //count results rows - used by pagination class
        $rows_count = $this->users_model->searchUsers($offset, 'count', $client_id);
        $this->data['debug'][] = $this->users_model->debug_data; //pagination
        $config = pagination_default_config();
        $config['base_url'] = site_url("admin/client/$client_id/users/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 7; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links(); //sorting links for menus on the top of the table
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array('sortby_fullname');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/client/$client_id/users/$link_sort_by/$column/$offset");
        }

        //visibility
        if ($rows_count > 0) {
            //show side menu
            $this->data['visible']['wi_client_users'] = 1;
        } else {
            //show mothing found
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_users_found']);
        }

    }

    /**
     * load of this clients projects
     */
    function __clientProjects()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * admin/client/1/projects/asc/sortby_id/in-progress/2
        * (2)->controller
        * (3)->client id
        * (4)->router
        * (5)->sort_order
        * (6)->sort_by_column
        * (7)->status ['in-progress, 'completed', 'behind-schedule', 'all']
        * (8)->offset       
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //uri segments
        $client_id = $this->uri->segment(3);
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_duedate' : $this->uri->segment(6);
        $status = ($this->uri->segment(7) == '') ? 'all' : $this->uri->segment(7);
        $offset = (is_numeric($this->uri->segment(8))) ? $this->uri->segment(8) : 0;

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'client_projects';
        $this->data['blocks']['client_projects'] = $this->projects_model->searchProjects($offset, 'search', $client_id, $status);
        $this->data['debug'][] = $this->projects_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->projects_model->searchProjects($offset, 'count', $client_id, $status);
        $this->data['debug'][] = $this->projects_model->debug_data;

        //pagination
        $config = pagination_default_config();
        $config['base_url'] = site_url("admin/client/$client_id/projects/$sort_by/$sort_by_column/$status");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 6; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc';

        //flip the sort_by
        $link_sort_by_column = array(
            'sortby_projectid',
            'sortby_duedate',
            'sortby_status',
            'sortby_companyname',
            'sortby_dueinvoices',
            'sortby_allinvoices',
            'sortby_progress');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/client/$client_id/projects/$link_sort_by/$column/$status/$offset");
        }

        //visibility
        if ($rows_count > 0) {
            //show side menu
            $this->data['visible']['wi_client_projects'] = 1;
        } else {
            //show mothing found
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_projects_have_been_found']);
        }
    }

    /**
     * load of this clients invoices
     */
    function __clientInvoices()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * admin/client/1/invoices/asc/sortby_id/due/2
        * (2)->controller
        * (3)->client id
        * (4)->router
        * (5)->sort_order
        * (6)->sort_by_column
        * (7)->status ['new', 'due', 'paid', 'part-paid', 'overdue', 'all']
        * (8)->offset       
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //uri segments
        $client_id = $this->uri->segment(3);
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_due_date' : $this->uri->segment(6);
        $status = ($this->uri->segment(7) == '') ? 'all' : $this->uri->segment(7);
        $offset = (is_numeric($this->uri->segment(8))) ? $this->uri->segment(8) : 0;

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'client_invoices';
        $this->data['blocks']['client_invoices'] = $this->invoices_model->searchInvoices($offset, 'search', $client_id, 'client', $status);
        $this->data['debug'][] = $this->invoices_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->invoices_model->searchInvoices($offset, 'count', $client_id, 'client', $status);
        $this->data['debug'][] = $this->invoices_model->debug_data;

        //pagination
        $config = pagination_default_config();
        $config['base_url'] = site_url("admin/client/$client_id/invoices/$sort_by/$sort_by_column/$status");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 8; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc';

        //flip the sort_by
        $link_sort_by_column = array(
            'sortby_id',
            'sortby_date',
            'sortby_due_date',
            'sortby_amount',
            'sortby_amount_paid',
            'sortby_amount_due',
            'sortby_status');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/client/$client_id/invoices/$link_sort_by/$column/$status/$offset");
        }

        //visibility
        if ($rows_count > 0) {
            //show side menu
            $this->data['visible']['wi_client_invoices'] = 1;
            $this->data['visible']['wi_client_invoices_menu'] = 1;
        } else {
            //show mothing found
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_invoices_have_been_found']);
            $this->data['visible']['wi_client_invoices_menu'] = 1;
        }

    }

    /**
     * add a new invoice
     *
     */
    function __addInvoice()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //flow control
        $next = true; //prevent direct access
        if (! isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('add-invoice', 'invoices', $this_url);
            redirect($redirect);
        }

        //validate form & display any errors
        if (! $this->__flmFormValidation('add_invoice')) {

            //show error
            $this->notices('error', $this->form_processor->error_message);

            //error
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']);

            //halt
            $next = false;
        }

        //validate hidde fields
        if (! $this->__flmFormValidation('add_invoice_hidden')) {

            //log this error
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Adding new message failed: Required hidden form field ($key) missing or invalid]");
            //error
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']);

            //halt
            $next = false;
        }

        //validate dates are not mis-matched
        if ($next) {

            if (strtotime($this->input->post('invoices_due_date')) < strtotime($this->input->post('invoices_date'))) {
                //show error
                $this->notices('error', $this->data['lang']['lang_due_date_cannot_be_behind_the_invoice_date']);

                //error
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
        }

        //add to database
        if ($next) {

            if ($this->data['vars']['new_invoice_id'] = $this->invoices_model->addInvoice()) {

                //show invoice created box
                $this->data['visible']['wi_tabs_invoice_created'] = 1; //set project id for url
                $this->data['vars']['invoice_project_id'] = $this->input->post('invoices_project_id');
            } else {

                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                //error
                $this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']);
                //halt
                $next = false;
            }

        }

    }

    /**
     * add a new client user to an existing client account
     *
     */
    function __addClientUser()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //validate form & display any errors
        $validation = $this->__flmFormValidation('add_user');
        if (! $validation) {

            //show error
            $this->notifications('wi_notification', $this->form_processor->error_message);

            //halt
            $next = false;
        }

        //add to database
        if ($next) {

            //some info
            $client_id = $this->input->post('client_users_clients_id'); //add to database
            $new_users_id = $this->users_model->addUser($client_id);
            $this->data['debug'][] = $this->users_model->debug_data; //was adding successful
            if (! $new_users_id) {
                //halt
                $next = false;
            }
        }

        //update primary contact if selected
        if ($next) {
            if ($this->input->post('client_users_main_contact') == 'on') {
                $this->users_model->updatePrimaryContact($client_id, $new_users_id);
                $this->data['debug'][] = $this->users_model->debug_data;
            }
        }

        //all is ok
        if ($next) {
            //success
            $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');

            /*EMAIL - send user an email*/
            $this->__emailer('new_user');

            /*EMAIL - send admin notifications*/
            $this->__emailer('admin_notification_new_user');

        } else {
            $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'html');
        }

        //load user page
        $this->__clientUsers();
    }

    /**
     * edit client profile
     */
    function __editProfile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //flow control
        $next = true;

        //prevent direct access
        if (! isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('edit-profile', 'profile', $this_url);
            redirect($redirect);
        }

        //form validation
        if (! $this->__flmFormValidation('edit_profile')) {
            //show error
            $this->notices('error', $this->form_processor->error_message, 'html');
            //halt
            $next = false;
        }

        //form validation
        if (! $this->__flmFormValidation('edit_profile_hidden')) {
            //show error
            $this->notices('error', $this->form_processor->error_message, 'html');
            //log this error
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Edit client profile failed: missing hidden fields]"); //halt
            $next = false;
        }

        //validate optional fields
        if ($next) {
            $error = ''; //set
            for ($i = 1; $i <= 3; $i++) {

                //the field names; values; required state
                $field_name = "clients_optionalfield$i";
                $wi_field_name = "wi_clients_optionalfield$i";
                $field_required = "wi_clients_optionalfield$i" . "_required";
                $field_title = $this->data['row'][$field_name];
                //process each required field
                if ($this->data['visible'][$field_required] == 1) {
                    //is there post data
                    if ($this->input->post($field_name) == '') {
                        //error
                        $error .= "$field_title - is required <br/>"; //halt
                        $next = false;
                    }
                }

                //add field to mysql array (for use in model) if its enabled
                if ($this->data['visible'][$wi_field_name] == 1) {
                    $mysql_client_optional_fields[] = $field_name;
                }

            }

            //show error
            if (! $next) {
                $this->notices('error', $error, 'html');
            }
        }

        //update mysql
        if ($next) {
            $result = $this->clients_model->editProfile($this->input->post('clients_id'), $mysql_client_optional_fields);
            $this->data['debug'][] = $this->clients_model->debug_data;

            //did this update of
            if ($result) {
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');
            } else {
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'html');
            }
        }

        //show profile
        $this->__clientProfile();
    }

    /**
     * loads [client table] optional fields and makes them TBS visible in whatever form is using the,
     * uses the [clients_optionalfield_visibility] helper to set visibility in ($this-data['visible']) array
     * also sets the [labels] to use in the form as ($this->data['row']['clients_optionalfield1'])
     */
    function __optionalFormFieldsDisplay()
    {

        //check optional form fields & and set visibility of form field widget
        $optional_fields = $this->clientsoptionalfields_model->optionalFields('enabled');
        $this->data['debug'][] = $this->clientsoptionalfields_model->debug_data;
        clients_optionalfield_visibility($optional_fields);
    }

    /**
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     */
    function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //[all_clients_projects]
        $data = $this->projects_model->allProjects('projects_title', 'ASC', $this->client_id, 'all');
        $this->data['debug'][] = $this->projects_model->debug_data;
        $this->data['lists']['all_clients_projects'] = create_pulldown_list($data, 'projects', 'id');
    }

    /**
     * validates forms for various methods in this class
     * @param string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //form validation
        if ($form == 'add_invoice') {

            //check required fields
            $fields = array(
                'invoices_date' => $this->data['lang']['lang_date_billed'],
                'invoices_due_date' => $this->data['lang']['lang_date_due'],
                'invoices_project_id' => $this->data['lang']['lang_date_project']);
            if (! $this->form_processor->validateFields($fields, 'required')) {
                return false;
            }
            //everything ok
            return true;
        }

        if ($form == 'add_invoice_hidden') {

            //check required fields
            $fields = array('invoices_created_by_id' => 'Created By', 'invoices_clients_id' => 'Client ID');
            if (! $this->form_processor->validateFields($fields, 'numeric')) {
                return false;
            }
            //everything ok
            return true;
        }

        //form validation
        if ($form == 'add_user') {

            //check required fields
            $fields = array(
                'client_users_clients_id' => $this->data['lang']['lang_company_name'],
                'client_users_email' => $this->data['lang']['lang_email'],
                'client_users_full_name' => $this->data['lang']['lang_full_name'],
                //'client_users_job_position_title' => $this->data['lang']['lang_job_title'],
                //'client_users_telephone' => $this->data['lang']['lang_telephone'],
                'client_users_password' => $this->data['lang']['lang_password']);

            if (! $this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check password (lenght only - 8 characters min)
            $fields = array('client_users_password' => $this->data['lang']['lang_password']);
            if (! $this->form_processor->validateFields($fields, 'length')) {
                return false;
            }

            //everything ok
            return true;
        }

        if ($form == 'edit_profile') {

            //check required fields
            $fields = array(
                'clients_company_name' => $this->data['lang']['lang_company_name'],
                'clients_address' => $this->data['lang']['lang_address'],
                'clients_city' => $this->data['lang']['lang_city'],
                'clients_state' => $this->data['lang']['lang_state'],
                'clients_zipcode' => $this->data['lang']['lang_zip_code'],
                'clients_country' => $this->data['lang']['lang_country'],
                'clients_website' => $this->data['lang']['lang_website']);
            if (! $this->form_processor->validateFields($fields, 'required')) {
                return false;
            }
            //everything ok
            return true;
        }

        if ($form == 'edit_profile_hidden') {

            //check required fields
            $fields = array('clients_id' => $this->data['lang']['lang_client_id']);
            if (! $this->form_processor->validateFields($fields, 'required')) {
                return false;
            }
            //everything ok
            return true;
        }

        //nothing specified - return false & error message
        $this->form_processor->error_message = $this->data['lang']['lang_form_validation_error'];
        return false;
    }

    /**
     * send out an email
     *
     * @param string $email email address
     */
    function __emailer($email = '', $vars = array())
    {

        //common variables
        $this->data['email_vars']['clients_company_name'] = $this->data['fields']['client']['clients_company_name'];
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];
        $this->data['email_vars']['client_dashboard_url'] = $this->data['vars']['site_url_client'];
        $this->data['email_vars']['admin_dashboard_url'] = $this->data['vars']['site_url_admin'];

        //new client welcom email-------------------------------
        if ($email == 'new_user') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_user_client');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //specific data
            $this->data['email_vars']['client_users_full_name'] = $this->input->post('client_users_full_name');
            $this->data['email_vars']['client_users_email'] = $this->input->post('client_users_email');
            $this->data['email_vars']['client_users_password'] = $this->input->post('client_users_password');

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);

            //send email
            email_default_settings(); //defaults (from emailer helper)
            $this->email->to($this->data['email_vars']['client_users_email']);
            $this->email->subject($template['subject']);
            $this->email->message($email_message);
            $this->email->send();

        }

        //admin notification - new client user-------------------------------
        if ($email == 'admin_notification_new_user') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_user_admin');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //specific data
            $this->data['email_vars']['client_users_full_name'] = $this->input->post('client_users_full_name');
            $this->data['email_vars']['client_users_email'] = $this->input->post('client_users_email');
            $this->data['email_vars']['clients_company_name'] = $this->client['clients_company_name'];

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);
            //send email to multiple admins
            foreach ($this->data['vars']['mailinglist_admins'] as $email_address) {
                email_default_settings(); //defaults (from emailer helper)
                $this->email->to($email_address);
                $this->email->subject($template['subject']);
                $this->email->message($email_message);
                $this->email->send();
            }

        }

    }

    /**
     * loads the view
     *
     * @param string $view the view to load
     */
    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']); //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file client.php */
/* Location: ./application/controllers/admin/client.php */
