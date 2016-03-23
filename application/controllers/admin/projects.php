<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Projects related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Projects extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'projects.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';

        //PERMISSIONS CHECK - GENERAL
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            redirect('/admin/error/permission-denied');
        }

    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    public function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //create pulldown lists
        $this->__pulldownLists();

        //projects optional fileds
        $this->__optionalFormFieldsDisplay();

        //uri - action segment
        $action = $this->uri->segment(3);

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project'];

        //re-route to correct method
        switch ($action) {
            case 'list':
                $this->__listProjects();
                break;

            case 'new-project':
                $this->__newProject();
                break;

            case 'add-project':
                $this->__addProject();
                break;

            case 'search-projects':
                $this->__formSearchProjects();
                break;

            default:
                $this->__listProjects();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * list all projects by default or results of projects search. if no search data is posted, list all projects
     *
     */
    protected function __listProjects()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show wi_clients_search widget
        $this->data['visible']['wi_projects_search'] = 1;

        //retrieve any search cache query string
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;

        //offset - used by sql to detrmine next starting point
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);

        //get results and save for tbs block merging
        $this->data['blk1'] = $this->projects_model->searchProjects($offset, 'search', '', 'all');//OR set to 'pending'
        $this->data['debug'][] = $this->projects_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->projects_model->searchProjects($offset, 'count', '', 'all');//OR set to 'pending'
        $this->data['debug'][] = $this->projects_model->debug_data;

        //sorting pagination data that is added to pagination links
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_projectid' : $this->uri->segment(6);

        //pagination
        $config = pagination_default_config(); //load all other settings from helper
        $config['base_url'] = site_url("admin/projects/list/$search_id/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 7; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        //the array names mustbe same as used in clients_model.php->searchClients()
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_projectid',
            'sortby_companyname',
            'sortby_duedate',
            'sortby_status',
            'sortby_dueinvoices',
            'sortby_allinvoices',
            'sortby_progress');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/projects/list/$search_id/$link_sort_by/$column/$offset");
        }

        //informational: show sorting criteria in footer of table
        $this->data['vars']['info_sort_by'] = $sort_by;
        $this->data['vars']['info_sort_by_column'] = $sort_by_column;
        $this->data['vars']['showing_x_results'] = $this->data['settings_general']['results_limit'];
        $this->data['vars']['results_count'] = $rows_count;

        //visibility - show table or show nothing found
        if ($rows_count > 0 && ! empty($this->data['blk1'])) {
            $this->data['visible']['wi_projects_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     * takes all posted (client search) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     *
     */
    protected function __formSearchProjects()
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
        redirect("admin/projects/list/$search_id");

    }

    /**
     * loads and displays the main home page for a given project
     *
     */
    protected function __viewProjectHome()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get project id
        $project_id = $this->uri->segment(4);

        //load man project details
        if ($this->data['rows1'] = $this->projects_model->projectDetails($project_id)) {

            //show project
            $this->data['visible']['wi_projects_view'] = 1;

        } else {
            //project not found
            $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_found']);
        }

    }

    /**
     * display [add new project] form
     */
    protected function __newProject()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //optional fields visibility
        $this->__optionalFormFieldsDisplay();

        //make form visible
        $this->data['visible']['wi_add_project_form'] = 1;

        //page title
        $this->data['visible']['wi_title_bar'] = 1;
        $this->data['vars']['projects_page_title'] = $this->data['lang']['lang_add_new_project'];

        //create post field for tbs
        $this->data['reg_fields'][] = 'post';

    }

    /**
     * add new project
     *
     */
    protected function __addProject()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //prevent direct access
        if (! isset($_POST['submit'])) {
            //redirect to form instead
            redirect('admin/projects');
        }

        //prefill forms with post data
        $this->data['reg_fields'][] = 'post';
        foreach ($_POST as $key => $value) {
            $this->data['fields']['post'][$key] = $value;
        }

        //form validation
        if (! $this->__flmFormValidation('add_project')) {
            //show error
            $this->notices('error', $this->form_processor->error_message, 'html');
            //halt
            $next = false;
        }

        //validate optional fields
        if ($next) {
            $error = ''; //set
            for ($i = 1; $i <= 5; $i++) {

                //the field names; values; required state
                $field_name = "projects_optionalfield$i";
                $wi_field_name = "wi_projects_optionalfield$i";
                $field_required = "wi_projects_optionalfield$i" . "_required";
                $field_title = $this->data['row'][$field_name];
                //process each required field
                if ($this->data['visible'][$field_required] == 1) {
                    //is there post data
                    if ($this->input->post($field_name) == '') {
                        //error
                        $error .= "$field_title - " . $this->data['lang']['lang_is_required'] . " <br/>";
                        //halt
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


        //create teme doctor project
        if ($next)
        {
            $error = ''; //set

            $projects_title = $this->input->post('projects_title');
            $result = $this->timedoctor_model->createProject(0, array('project_name'=>$projects_title));
            if(isset($result['project_id']))
            {
                $_POST['timedoctorid'] = $result['project_id'];
            }
            else
            {
                $error = print_r($result, 1);
                $next = false;
            }  
            //show error
            if (! $next) {
                $this->notices('error', $error, 'html');
            }
        }

        //save information to database & get the id of this new client
        if ($next) {
            $project_id = $this->projects_model->addProject();
            $this->data['debug'][] = $this->projects_model->debug_data;

            //was the project created ok
            if (is_numeric($project_id)) {

                //add me to project members
                $this->project_members_model->addMember($project_id, $this->data['vars']['my_id']);
                $this->data['debug'][] = $this->project_members_model->debug_data;

                //add primary to project members
                $primaries = $this->project_members_model->getPrimaryMembers();
                foreach($primaries as $primary)
                {
                    if($primary['team_profile_id'] != $this->data['vars']['my_id'])
                    {
                        $this->project_members_model->addMember($project_id, $primary['team_profile_id']);
                        $this->data['debug'][] = $this->project_members_model->debug_data;
                    }
                }

                //make me project lead
                $this->project_members_model->updateProjectLead($project_id, $this->data['vars']['my_id']);
                $this->data['debug'][] = $this->project_members_model->debug_data;

                //events tracker
                $this->__eventsTracker('add-project', array('project_id' => $project_id));

                //-send emails to client & admin----------------

                //get client details
                $client_details = $this->clients_model->clientDetails($this->input->post('projects_clients_id'));
                $this->data['debug'][] = $this->clients_model->debug_data;

                //set all the email vars
                $projects_title = $this->input->post('projects_title');
                $project_deadline = $this->input->post('project_deadline');
                $clients_company_name = $client_details['clients_company_name'];
                $client_users_email = $client_details['client_users_email'];
                $client_users_full_name = $client_details['client_users_full_name'];

                $email_vars = array(
                    'projects_title' => $projects_title,
                    'client_users_full_name' => $client_users_full_name,
                    'client_users_email' => $client_users_email,
                    'project_deadline' => $project_deadline,
                    'projects_id' => $project_id,
                    'clients_company_name' => $clients_company_name);

                //send email client
                $this->__emailer('new_project_client', $email_vars);

                //send email admin
                $this->__emailer('new_project_admin', $email_vars);
                //-send emails to client & admin----------------

                //redirect to new project
                redirect("/admin/project/$project_id/view");
            } else {
                //show error
                $this->notifications('wi_notification', $this->data['lang']['lang_request_could_not_be_completed']);
            }
        }
    }

    /**
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     *
     */
    protected function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //[all clients]
        $data = $this->clients_model->allClients('clients_company_name', 'ASC');
        $this->data['debug'][] = $this->clients_model->debug_data;
        $this->data['lists']['all_clients'] = create_pulldown_list($data, 'clients', 'id');

        //[all user emails]
        $data = $this->projects_model->allProjects('projects_title', 'ASC');
        $this->data['debug'][] = $this->projects_model->debug_data;
        $this->data['lists']['all_projects'] = create_pulldown_list($data, 'projects', 'name');

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
        if ($form == 'add_project') {

            //check required fields
            $fields = array(
                'projects_clients_id' => $this->data['lang']['lang_client'],
                'projects_title' => $this->data['lang']['lang_project_title'],
                'project_deadline' => $this->data['lang']['lang_deadline'],
                //'projects_description' => $this->data['lang']['lang_description']
            );
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
     * loads [client table] optional fields and makes them TBS visible in whatever form is using the,
     * uses the [clients_optionalfield_visibility] helper to set visibility in ($this-data['visible']) array
     * also sets the [labels] to use in the form as ($this->data['row']['clients_optionalfield1'])
     */
    protected function __optionalFormFieldsDisplay()
    {

        //check optional form fields & and set visibility of form field widget
        $optional_fields = $this->projectsoptionalfields_model->optionalFields('enabled');
        $this->data['debug'][] = $this->projectsoptionalfields_model->debug_data;
        projects_optionalfield_visibility($optional_fields);
    }

    /**
     * records new project events (timeline)
     *
     * @param	string   $type identify the loop to run in this function
     * @param   array    $vents_data an optional array that can be used to directly pass data]      
     */
    protected function __eventsTracker($type = '', $events_data = array())
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //--------------record a new event-----------------------
        if ($type == 'add-project') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $events_data['project_id'];
            $events['project_events_type'] = 'project';
            $events['project_events_details'] = $this->input->post('projects_title');
            $events['project_events_action'] = 'lang_tl_created_new_project';
            $events['project_events_target_id'] = $events_data['project_id'];
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'team';

            //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;
        }

    }

    /**
     * send out an email
     *
     * @param string $email email address
     */
    protected function __emailer($email = '', $vars = array())
    {

        //common variables
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];
        $this->data['email_vars']['client_dashboard_url'] = $this->data['vars']['site_url_client'];
        $this->data['email_vars']['admin_dashboard_url'] = $this->data['vars']['site_url_admin'];

        //specific passed variables
        foreach ($vars as $key => $value) {
            $this->data['email_vars'][$key] = $value;
        }

        //new client welcom email-------------------------------
        if ($email == 'new_project_client') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_project_client');
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
        if ($email == 'new_project_admin') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_project_admin');
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
    protected function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']);

        //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file projects.php */
/* Location: ./application/controllers/admin/projects.php */
