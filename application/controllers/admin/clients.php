<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all clients related functions
 */
class Clients extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'clients.html';

        //css settings
        $this->data['vars']['css_menu_clients'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_clients'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-user"></i>';
        
        $this->data['vars']['menu'] = 'clients';
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

        //create pulldown lists
        $this->__pulldownLists();

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'list':
                $this->__listClients();
                break;

            case 'search-clients':
                $this->__cachedFormSearch();
                break;

            case 'add':
                $this->__addClients();
                break;

            case 'edit-modal':
                $this->__editClientModal();
                break;

            default:
                $this->__listClients();

        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * list all clients by default or results of client search. if no search data is posted, list all clients
     *
     */
    function __listClients()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/clients/list/54/desc/sortby_dueinvoices/0
        * (2)->controller
        * (3)->router
        * (4)->search id
        * (5)->sort_by
        * (6)->sort_by_column
        * (7)->offset
        ** -----------------------------------------*/

        //PERMISSION CHECK
        if ($this->data['permission']['view_item_clients'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show wi_clients_search widget
        $this->data['visible']['wi_clients_search'] = 1;

        //retrieve any search cache query string
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;

        //offset - used by sql to detrmine next starting point
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);

        //get results 
        $results = $this->clients_model->searchClients($offset, 'search');
		
		//add projects list
		$results = $this->clients_model->addProjectList($results);
		foreach ($results as $key=>$one)
		{
			$results[$key]["class_name"]=strtolower(str_replace(' ', '-', $results[$key]['clients_company_name']));
		}

        //save result for tbs block merging
		$this->data['blk1'] = $results;
		
        $this->data['debug'][] = $this->clients_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->clients_model->searchClients($offset, 'count');
        $this->data['debug'][] = $this->clients_model->debug_data;

        //sorting pagination data that is added to pagination links
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_clientid' : $this->uri->segment(6);

        //pagination
        $config = pagination_default_config(); //load all other settings from helper
        $config['base_url'] = site_url("admin/clients/list/$search_id/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 7; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        //the array names mustbe same as used in clients_model.php->searchClients()
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_clientid',
            'sortby_contactname',
            'sortby_dueinvoices',
            'sortby_projects',
            'sortby_allinvoices',
            'sortby_companyname');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/clients/list/$search_id/$link_sort_by/$column/$offset");
        }

        //informational: show sorting criteria in footer of table
        $this->data['vars']['info_sort_by'] = $sort_by;
        $this->data['vars']['info_sort_by_column'] = $sort_by_column;
        $this->data['vars']['showing_x_results'] = $this->data['settings_general']['results_limit'];
        $this->data['vars']['results_count'] = $rows_count;

        //Optional Form Fields [client table]
        //used by various modal like "add new client"
        $this->__optionalFormFieldsDisplay();

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blk1'])) {
            $this->data['visible']['wi_clients_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     * takes all posted (client search) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     */
    function __cachedFormSearch()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //create array containg all post data in format:: array('name'=>$this->input->post('name));
        $search_array = array();
        foreach ($_POST as $key => $value) {
            $search_array[$key] = $this->input->post($key);
        }

        //save serch query in database & get id of database record
        $search_id = $this->input->save_query($search_array);

        //change url to "list" and redirect with cached search id.
        redirect("admin/clients/list/$search_id");

    }

    /**
     * edit client details via modal popup
     */
    function __editClientModal()
    {

        //PERMISSION CHECK
        if ($this->data['permission']['add_item_clients'] != 1) {
            die($this->data['lang']['lang_permission_denied']);
        }

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'clients.modal.html';

        //get client id
        $client_id = $this->uri->segment(4);

        //load from database
        $this->data['row'] = $this->clients_model->clientDetails($client_id);
        $this->data['debug'][] = $this->clients_model->debug_data;

        //load all this clients users
        $blk2 = $this->users_model->clientUsers($client_id);
        $this->data['debug'][] = $this->users_model->debug_data;

        //create editable.js data set
		$this->data['vars']['editable_users_list'] = '';
        for ($i = 0; $i < count($blk2); $i++) {
            $this->data['vars']['editable_users_list'] .= "{value: '" . $blk2[$i]['client_users_id'] . "', text: '" . $blk2[$i]['client_users_full_name'] . "'},";
        }
        //trim trailing ,
        $this->data['vars']['editable_users_list'] = rtrim($this->data['vars']['editable_users_list'], ",");

        //Optional Form Fields [client table]
        //used by various modal like "add new client"
        $this->__optionalFormFieldsDisplay();

        //visibility - show table or show nothing found
        if ($this->data['row']) {
            $this->data['visible']['wi_edit_client_details_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }
    }

    /**
     * add a new client from form post data
     *
     */
    function __addClients()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //PERMISSION CHECK
        if ($this->data['permission']['add_item_clients'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/clients/list');
        }

        //validate form & display any errors
        $validation = $this->__flmFormValidation('add_client');
        if (!$validation) {

            //show error
            $this->notices('error', $this->form_processor->error_message);

        } else {

            //save information to database & get the id of this new client
            if (!$client_id = $this->clients_model->addClients()) {
                $next = false;
            }
            $this->data['debug'][] = $this->clients_model->debug_data;

            //save user details & get the id of this new user
            if ($next) {
                if (!$client_users_id = $this->users_model->addUser($client_id)) {
                    $next = false;
                }
            }
            $this->data['debug'][] = $this->users_model->debug_data;

			//if client was lead, update lead info 
			if(isset($_POST[lead_id])) $this->users_model->updateLead($_POST[lead_id], $client_id);
			
            //update primary contact & make this new user the primary contact
            if ($next) {
                if (!$client_users_id = $this->users_model->updatePrimaryContact($client_id, $client_users_id)) {
                    $next = false;
                }
            }
            $this->data['debug'][] = $this->users_model->debug_data;

            //all is ok
            if ($next) {

                //send email to client
                $this->__emailer('new_client_welcome_client');
                //send email to admin
                $this->__emailer('new_client_admin');
                //notice
                $this->notifications('wi_notification', $this->data['lang']['lang_request_has_been_completed']);
				redirect('/admin/clients/list');
            } else {
                $this->notifications('wi_notification', $this->data['lang']['lang_an_error_has_occurred']);
            }

        }
    }

    /**
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //form validation
        if ($form == 'add_client') {

            //check required fields
            $fields = array(
                'clients_company_name' => $this->data['lang']['lang_company_name'],
                //'clients_address' => $this->data['lang']['lang_address'],
                //'clients_city' => $this->data['lang']['lang_city'],
                //'clients_state' => $this->data['lang']['lang_state'],
                //'clients_zipcode' => $this->data['lang']['lang_zip_code'],
                //'clients_country' => $this->data['lang']['lang_country'],
                //'clients_website' => $this->data['lang']['lang_website'],
                'client_users_full_name' => $this->data['lang']['lang_full_name'],
                //'client_users_job_position_title' => $this->data['lang']['lang_job_title'],
                //'client_users_telephone' => $this->data['lang']['lang_telephone'],
                'client_users_email' => $this->data['lang']['lang_email'],
                'client_users_password' => $this->data['lang']['lang_password']);

            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check password (lenght only - 8 characters min)
            $fields = array('client_users_password' => 'Password');
            if (!$this->form_processor->validateFields($fields, 'length')) {
                return false;
            }

            //check password match
            $fields = array('client_users_password' => 'Password', 'confirm_password' => 'Confirm Password');
            if (!$this->form_processor->validateFields($fields, 'matched')) {
                return false;
            }

            //everything ok
            return true;

        }
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
     *
     */
    function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //[all_clients]
        $data = $this->clients_model->allClients('clients_company_name', 'ASC');
        $this->data['debug'][] = $this->clients_model->debug_data;
        $this->data['lists']['all_clients'] = create_pulldown_list($data, 'clients', 'name');

        //[all_users_email]
        $data = $this->users_model->allUsers('client_users_full_name', 'ASC');
        $this->data['debug'][] = $this->users_model->debug_data;
        $this->data['lists']['all_users_email'] = create_pulldown_list($data, 'users_email', 'name');

    }

    /**
     * send out an email
     *
     * @param string $email email address
     */
    function __emailer($email = '', $vars = array())
    {

        //common variables
        $this->data['email_vars']['clients_company_name'] = $this->input->post('clients_company_name');
        $this->data['email_vars']['client_users_full_name'] = $this->input->post('client_users_full_name');
        $this->data['email_vars']['client_users_email'] = $this->input->post('client_users_email');
        $this->data['email_vars']['client_users_password'] = $this->input->post('client_users_password');
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];
        $this->data['email_vars']['client_dashboard_url'] = $this->data['vars']['site_url_client'];
        $this->data['email_vars']['admin_dashboard_url'] = $this->data['vars']['site_url_admin'];

        //new client welcom email-------------------------------
        if ($email == 'new_client_welcome_client') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_client_welcome_client');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);

            //send email
            email_default_settings(); //defaults (from emailer helper)
            $this->email->to($this->data['email_vars']['client_users_email']);
            $this->email->subject($template['subject']);
            $this->email->message($email_message);
            $this->email->send();

        }

        //new client welcom email-------------------------------
        if ($email == 'new_client_admin') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_client_admin');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

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
        $this->data['controller_profiling'][] = __function__;

        //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']);

        //complete the view
        $this->__commonAll_View($view);

    }

}

/* End of file clients.php */
/* Location: ./application/controllers/admin/clients.php */
