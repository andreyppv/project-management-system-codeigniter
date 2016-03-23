<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Users related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Users extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'users.html';

        //css settings
        $this->data['vars']['css_menu_clients'] = 'open'; //menu
        $this->data['vars']['css_submenu_clients'] = 'style="display:block; visibility:visible;"';

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_client_users'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-user"></i>';
    }

    /**
     * This is our re-routing function and is the inital function called
     *
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
                $this->__listUsers();
                break;

            case 'add':
                $this->__addUsers();
                break;

            case 'search-users':
                $this->__formSearchUsers();
                break;

            case 'edit-modal':
                $this->__editUserModal();
                break;

            default:
                $this->__listUsers();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * list all users by default or results of client search. if no search data is posted, list all clients
     *
     */
    function __listUsers()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show wi_clients_search widget
        $this->data['visible']['wi_users_search'] = 1;

        //retrieve any search cache query string
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;

        //offset - used by sql to detrmine next starting point
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);

        //get results and save for tbs block merging
        $this->data['blk1'] = $this->users_model->searchUsers($offset, 'search');
        $this->data['debug'][] = $this->users_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->users_model->searchUsers($offset, 'count');
        $this->data['debug'][] = $this->users_model->debug_data;

        //sorting pagination data that is added to pagination links
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_userid' : $this->uri->segment(6);

        //pagination
        $config = pagination_default_config(); //load all other settings from helper
        $config['base_url'] = site_url("admin/users/list/$search_id/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 7; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        //the array names mustbe same as used in clients_model.php->searchClients()
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_userid',
            'sortby_companyname',
            'sortby_fullname');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/users/list/$search_id/$link_sort_by/$column/$offset");
        }

        //informational: show sorting criteria in footer of table
        $this->data['vars']['info_sort_by'] = $sort_by;
        $this->data['vars']['info_sort_by_column'] = $sort_by_column;
        $this->data['vars']['showing_x_results'] = $this->data['settings_general']['results_limit'];
        $this->data['vars']['results_count'] = $rows_count;

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blk1'])) {
            $this->data['visible']['wi_users_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     * takes all posted (user search) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     *
     */
    function __formSearchUsers()
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
        redirect("admin/users/list/$search_id");

    }

    /**
     * edit client details via modal popup
     *
     */
    function __editUserModal()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_COMMON_THEME . 'users.modal.html';

        //get client id
        $user_id = $this->uri->segment(4);

        //load from database
        $this->data['reg_fields'][] = 'profile';
        $this->data['fields']['profile'] = $this->users_model->userDetails($user_id);
        $this->data['debug'][] = $this->users_model->debug_data;

        //visibility - show table or show nothing found
        if (!empty($this->data['fields']['profile'])) {
            $this->data['visible']['wi_edit_user_details_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }
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

        //[all_users & all_users_email]
        $data = $this->users_model->allUsers('client_users_full_name', 'ASC');
        $this->data['debug'][] = $this->users_model->debug_data;
        $this->data['lists']['all_users'] = create_pulldown_list($data, 'users', 'name');
        $this->data['lists']['all_users_email'] = create_pulldown_list($data, 'users_email', 'name');

        //[all_clients & all_clients_byid]
        $data = $this->clients_model->allClients('clients_company_name', 'ASC');
        $this->data['debug'][] = $this->clients_model->debug_data;
        $this->data['lists']['all_clients'] = create_pulldown_list($data, 'clients', 'name');
        $this->data['lists']['all_clients_byid'] = create_pulldown_list($data, 'clients', 'id');

    }

    /**
     * add a new client from form post data
     *
     */
    function __addUsers()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //PERMISSIONS CHECK - GENERAL
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //validate form & display any errors
        $validation = $this->__flmFormValidation('add_user');
        if (!$validation) {
            $this->notifications('wi_notification', $this->form_processor->error_message);
        } else {

            //save information to database
            $client_id = $this->input->post('client_users_clients_id');
            if (!$new_users_id = $this->users_model->addUser($client_id)) {
                $next = false;
            }
            $this->data['debug'][] = $this->users_model->debug_data;

            //update primary contact & make this new user the primary contact
            if ($next && $this->input->post('client_users_main_contact') == 'on') {
                if (!$this->users_model->updatePrimaryContact($client_id, $new_users_id)) {
                    $next = false;
                }
            }
            $this->data['debug'][] = $this->users_model->debug_data;

            //all is ok
            if ($next) {
                $this->notifications('wi_notification', $this->data['lang']['lang_request_has_been_completed']);
                $this->data['visible']['wi_users_search'] = 1;
            } else {
                $this->notifications('wi_notification', $this->data['lang']['lang_an_error_has_occurred']);
                $this->data['visible']['wi_users_search'] = 1;
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
        if ($form == 'add_user') {

            //check required fields
            $fields = array(
                'client_users_clients_id' => $this->data['lang']['lang_company_name'],
                'client_users_email' => $this->data['lang']['lang_email'],
                'client_users_full_name' => $this->data['lang']['lang_full_name'],
                'client_users_job_position_title' => $this->data['lang']['lang_job_title'],
                'client_users_telephone' => $this->data['lang']['lang_telephone'],
                'client_users_password' => $this->data['lang']['lang_password']);

            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check password (lenght only - 8 characters min)
            $fields = array('client_users_password' => $this->data['lang']['lang_password']);
            if (!$this->form_processor->validateFields($fields, 'length')) {
                return false;
            }

            //everything ok
            return true;
        }

        //nothing specified
        return false;

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

/* End of file users.php */
/* Location: ./application/controllers/admin/users.php */
