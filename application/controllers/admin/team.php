<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Team related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Team extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'team.html';

        //css settings
        $this->data['vars']['css_menu_team'] = 'open'; //menu
        $this->data['vars']['css_submenu_team'] = 'style="display:block; visibility:visible;"';

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_team_members'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-group"></i>';
        
        $this->data['vars']['menu'] = 'team';
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

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {

            default:
            case 'list':
                $this->__listTeamMembers();
                break;

            case 'add':
                $this->__addTeamMembers();
                break;

            case 'search-team-members':
                $this->__formSearchTeamMembers();
                break;

            case 'edit-modal':
                $this->__editTeamMemberModal();
                break;
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * list all Team Members by default or results of client search. if no search data is posted, list all clients
     *
     */
    protected function __listTeamMembers()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show wi_clients_search widget
        $this->data['visible']['wi_team_search'] = 1;

        //retrieve any search cache query string
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;

        //offset - used by sql to detrmine next starting point
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);

        //get results and save for tbs block merging
        $this->data['blk1'] = $this->teamprofile_model->searchTeamMembers($offset, 'search');
        $this->data['debug'][] = $this->teamprofile_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->teamprofile_model->searchTeamMembers($offset, 'count');
        $this->data['debug'][] = $this->teamprofile_model->debug_data;

//calculate hours

//Uncompleted assigned hrs
//Completed hours
        foreach ($this->data['blk1'] as $key => $value)
        {
            $hours_paid = $this->teamprofile_model->calcHoursPaid($value['team_profile_id'], $value['hourlyrate']);
            $this->data['debug'][] = $this->teamprofile_model->debug_data;
            $this->data['blk1'][$key] = array_merge($this->data['blk1'][$key], $hours_paid);
        }
        


        //sorting pagination data that is added to pagination links
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_team_profile_id' : $this->uri->segment(6);

        //pagination
        $config = pagination_default_config(); //load all other settings from helper
        $config['base_url'] = site_url("admin/team/list/$search_id/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 7; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        //the array names mustbe same as used in clients_model.php->searchClients()
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_profileid',
            'sortby_group',
            'sortby_fullname');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/team/list/$search_id/$link_sort_by/$column/$offset");
        }

        //informational: show sorting criteria in footer of table
        $this->data['vars']['info_sort_by'] = $sort_by;
        $this->data['vars']['info_sort_by_column'] = $sort_by_column;
        $this->data['vars']['showing_x_results'] = $this->data['settings_general']['results_limit'];
        $this->data['vars']['results_count'] = $rows_count;

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blk1'])) {
            $this->data['visible']['wi_team_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     * takes all posted (team search) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     *
     */
    protected function __formSearchTeamMembers()
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
        redirect("admin/team/list/$search_id");

    }

    /**
     * edit client details via modal popup
     *
     */
    protected function __editTeamMemberModal()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //get team members id
        $team_profile_id = $this->uri->segment(4);

        //PERMISSIONS CHECK - GENERAL
        //Administrator & profile owner only
        if ($this->data['vars']['my_group'] == 1 || $this->data['vars']['my_id'] == $team_profile_id) {

            //flow
            $next = true;

        } else {

            //permission denied notice
            $this->notifications('wi_notification', $this->data['lang']['lang_permission_denied']);

            //halt
            $next = false;
        }

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'team.modal.html';

        if ($next) {

            //load from database
            $this->data['row'] = $this->teamprofile_model->teamMemberDetails($team_profile_id);
            $this->data['debug'][] = $this->teamprofile_model->debug_data;

            //load all groups
            $blk2 = $this->groups_model->allGroups('groups_name', 'ASC');
            $this->data['debug'][] = $this->groups_model->debug_data;

            //create editable.js data set of groups
            $this->data['vars']['editable_groups_list'] = ''; //set
            for ($i = 0; $i < count($blk2); $i++) {
                $this->data['vars']['editable_groups_list'] .= "{value: '" . $blk2[$i]['groups_id'] . "', text: '" . $blk2[$i]['groups_name'] . "'},";
            }
            $this->data['vars']['editable_groups_list'] = rtrim($this->data['vars']['editable_groups_list'], ",");

            //visibility - upload avatar button (only show if editig my own profile)
            $this->data['visible']['wi_upload_avatar'] = ($this->data['vars']['my_id'] == $team_profile_id) ? 1 : 0;

            //visibility - show table or show nothing found
            if (!empty($this->data['row'])) {
                //$this->data['row'][$key] = $value;
                $this->data['visible']['wi_edit_team_member_details_table'] = 1;
            } else {
                $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
            }
        }
    }

    /**
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     *
     */
    private function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //[all_team_members & all_team_members_email]
        $data = $this->teamprofile_model->allTeamMembers('team_profile_full_name', 'ASC');
        $this->data['debug'][] = $this->teamprofile_model->debug_data;
        $this->data['lists']['all_team_members'] = create_pulldown_list($data, 'team_members', 'name');
        $this->data['lists']['all_team_members_email'] = create_pulldown_list($data, 'team_members_email', 'name');

        //[all_groups]
        $data = $this->groups_model->allGroups('groups_name', 'ASC');
        $this->data['debug'][] = $this->groups_model->debug_data;
        $this->data['lists']['all_groups'] = create_pulldown_list($data, 'groups', 'name');
        $this->data['lists']['all_groups_id'] = create_pulldown_list($data, 'groups', 'id');

    }

    /**
     * add a new team member form post data
     *
     */
    protected function __addTeamMembers()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //PERMISSIONS CHECK - GENERAL
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            die('permissions error');
            redirect('/admin/error/permission-denied');
        }

        //flow control
        $next = true;

        //validate form & display any errors
        $validation = $this->__flmFormValidation('add_team_member');
        if (!$validation) {

            //show error
            die('form validation');exit;
            $this->notices('error', $this->form_processor->error_message);

        } else {

            //save information to database
            if (!$new_team_members_id = $this->teamprofile_model->addTeamMembers()) {
                die('error here: '.print_r($this->teamprofile_model->debug_data, 1));
                $next = false;
            }
            //$this->data['debug'][] = $this->teamprofile_model->debug_data;

            //all is ok
            if ($next) {
                $this->notifications('wi_notification', $this->data['lang']['lang_request_has_been_completed']);
                $this->data['visible']['wi_users_search'] = 1;

                /*EMAIL - send user an email*/
                $this->__emailer('new_team_member');

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
    private function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //form validation
        if ($form == 'add_team_member') {

            //check required fields
            $fields = array(
                'team_profile_full_name' => $this->data['lang']['lang_full_name'],
                'team_profile_groups_id' => $this->data['lang']['lang_group'],
                'team_profile_job_position_title' => $this->data['lang']['lang_job_title'],
                'team_profile_email' => $this->data['lang']['lang_email'],
                'team_profile_telephone' => $this->data['lang']['lang_telephone'],
                'team_profile_password' => $this->data['lang']['lang_password']);

            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check password (lenght only - 8 characters min)
            $fields = array('team_profile_password' => $this->data['lang']['lang_password']);
            if (!$this->form_processor->validateFields($fields, 'length')) {
                return false;
            }

            //everything ok
            return true;
        }

        //nothing specified - return false & error message
        $this->form_processor->error_message = $this->data['lang']['lang_form_validation_error'];
        return false;

    }

    // -- __emailer-------------------------------------------------------------------------------------------------------
    /**
     * send out an email
     *
     * @access	private
     * @param	string
     * @return	void
     */
    private function __emailer($email = '', $vars = array())
    {

        //common variables
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];
        $this->data['email_vars']['admin_dashboard_url'] = $this->data['vars']['site_url_admin'];


        //------------------------------------send out email-------------------------------
        if ($email == 'new_team_member') {
        
        $this->data['email_vars']['team_profile_full_name'] = $this->input->post('team_profile_full_name');
        $this->data['email_vars']['team_profile_email'] = $this->input->post('team_profile_email');
        $this->data['email_vars']['team_profile_password'] = $this->input->post('team_profile_password');

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_team_member');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);

            //send email
            email_default_settings(); //defaults (from emailer helper)
            $this->email->to($this->input->post('team_profile_email'));
            $this->email->subject($template['subject']);
            $this->email->message($email_message);
            $this->email->send();
        }
    }
    /**
     * loads the view
     *
     * @param string $view the view to load
     */
    private function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']);

        //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file team.php */
/* Location: ./application/controllers/admin/team.php */
