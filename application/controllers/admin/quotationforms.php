<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Quotationforms related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Quotationforms extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'quotationforms.html';

        //css settings
        $this->data['vars']['css_menu_topnav_quotation_forms'] = 'nav_alternative_controls_active'; //menu
        $this->data['vars']['css_menu_quotationforms'] = 'open'; //menu

        //js settings
        $this->data['visible']['formbuilder_js'] = 1;

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_quotation_forms'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-file-text"></i>';

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
            case 'new':
                $this->data['vars']['main_title'] = $this->data['lang']['lang_quotation_forms'];
                $this->__newQuotationForm();
                break;

            case 'add':
                $this->__addQuotationForm();
                break;

            case 'list':
                $this->__listQuotationForms();
                break;

            case 'view':
                $this->__viewQuotationForm();
                break;

            case 'edit':
                $this->__editQuotationForm();
                break;

            case 'search':
                $this->__cachedFormSearch();
                break;

            default:
                $this->__listQuotationForms;
                break;

        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * load quotation form builder to start form building process
     */
    function __newQuotationForm()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //visibility
        $this->data['visible']['wi_quotation_form'] = 1;
        $this->data['visible']['js_formbuilder'] = 1;

        /*---------------------------------------------------------------------
        * create blank quotation form data (to avoid error)
        * this is used in the html formbuilder's javascript when loading page
        * bootstrapData:[]
        *--------------------------------------------------------------------*/
        $this->data['vars']['quotationforms_code'] = json_encode(array());

    }

    /**
     * save created form in the the database
     */
    function __addQuotationForm()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //visibility
        $this->data['visible']['wi_quotation_form'] = 1;
        $this->data['visible']['js_formbuilder'] = 1;

        //validate form
        if (!$this->__flmFormValidation('add_form')) {
            //show error
            $this->notices('error', $this->form_processor->error_message, 'html');
            //halt
            $next = false;
        }

        //validate hidden fields
        if ($next) {
            if (!$this->__flmFormValidation('add_form_hidden')) {
                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Creating new quotation form failed: Missing hidden fields]");
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'html');
                //halt
                $next = false;
            }
        }

        //save new quotation form
        if ($next) {
            $result = $this->quotationforms_model->addQuotationForm();
            $this->data['debug'][] = $this->quotationforms_model->debug_data;
            if ($result) {

                //show success
                $this->notifications('wi_notification', $this->data['lang']['lang_request_has_been_completed']);

                //flow
                $next = true;

                //hide form
                $this->data['visible']['wi_quotation_form'] = 0;

            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'html');
                //halt
                $next = false;
            }

        }

        //an error occurred
        if (!$next) {

            /*-----------------------------------------------------------------------------------------
            * display posted formbuilder data
            * turn the posted form data into a php array
            * pull out the 'fields' array
            * save to [vars[ for dynamic inclusion in html
            * NB: this is necessay only becase formbuilder.js adds the posted data in an array
            *     and so cannot be used directly as it was posted
            *-----------------------------------------------------------------------------------------*/
            $posted_form_data = json_decode($this->input->post('quotationforms_code'), true);
            $this->data['vars']['quotationforms_code'] = json_encode($posted_form_data['fields']);

        }
    }

    /**
     * example of a paginated method with no cached search
     */
    function __listQuotationForms()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/quotationforms/list/54/desc/sortby_id/0
        * (2)->controller
        * (3)->router
        * (4)->search id
        * (5)->sort_order
        * (6)->sort_by_column
        * (7)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //visibility
        $this->data['visible']['wi_quotations_search'] = 1;

        //uri segments
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_id' : $this->uri->segment(6);
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'quotationforms';
        $this->data['blocks']['quotationforms'] = $this->quotationforms_model->searchQuotationForms($offset, 'search');
        $this->data['debug'][] = $this->quotationforms_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->quotationforms_model->searchQuotationForms($offset, 'count');
        $this->data['debug'][] = $this->quotationforms_model->debug_data;
        $this->data['vars']['count_quotation_forms'] = $rows_count;

        //pagination
        $config = pagination_default_config(); //load all other settings from helper
        $config['base_url'] = site_url("admin/quotationforms/list/$search_id/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 7; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        //the array names mustbe same as used in the model
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc';
        //flip the sort_by
        $link_sort_by_column = array(
            'sortby_id',
            'sortby_title',
            'sortby_date',
            'sortby_status');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/quotationforms/list/$search_id/$link_sort_by/$column/$offset");
        }

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blocks']['quotationforms'])) {
            $this->data['visible']['wi_quotations_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     * load quotation form
     *
     */
    function __viewQuotationForm()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //title
        $this->data['vars']['main_title'] = 'Quotation Form Details';

        //flow control
        $next = true;

        //get params
        $form_id = $this->uri->segment(4);

        //validate input
        if ($next) {
            if (!is_numeric($form_id)) {
                //show error
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
                //halt
                $next = false;
            }
        }

        //load item
        if ($next) {
            $result = $this->quotationforms_model->getQuotationForm($form_id);
            $this->data['debug'][] = $this->quotationforms_model->debug_data;
            if (!$result) {
                //show error
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
                //halt
                $next = false;
            }
        }

        /*-----------------------------------------------------------------------------------------
        * display posted formbuilder data
        * turn the posted form data into a php array
        * pull out the 'fields' array
        * save to [vars[ for dynamic inclusion in html
        * NB: this is necessay only becase formbuilder.js adds the posted data in an array
        *     and so cannot be used directly as it was posted
        *-----------------------------------------------------------------------------------------*/
        if ($next) {
            //form data
            $form_data = json_decode($result['quotationforms_code'], true);
            $this->data['vars']['quotationforms_code'] = json_encode($form_data['fields']);

            //avoid js errors if something has gone wrong with retrieved data
            if ($this->data['vars']['quotationforms_code'] == '') {
                $this->data['vars']['quotationforms_code'] = '[]';
            }

            //form data
            $this->data['vars']['quotationform_title'] = $result['quotationforms_title'];
            $this->data['vars']['quotationform_id'] = $result['quotationforms_id'];
            $this->data['vars']['quotationforms_status'] = $result['quotationforms_status'];
            $this->data['vars']['quotationforms_code_array'] = $result['quotationforms_code']; //the original array

            //set form visible
            $this->data['visible']['wi_quotation_form_edit'] = 1;
            $this->data['visible']['js_formbuilder'] = 1;
        }

    }

    /**
     * edit a quotation form
     *
     */
    function __editQuotationForm()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //no direct access
        if (!isset($_POST['form_submitted_check'])) {
            redirect('/admin/quotationforms/view/' . $this->uri->segment(4));
        }

        //visibility
        $this->data['visible']['js_formbuilder'] = 1;

        //validate form
        if (!$this->__flmFormValidation('edit_form')) {
            //show error
            $this->notices('error', $this->form_processor->error_message, 'html');
            //halt
            $next = false;
        }

        //validate hidden fields
        if ($next) {
            if (!$this->__flmFormValidation('edit_form_hidden')) {
                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Creating new quotation form failed: Missing hidden fields]");
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'html');
                //halt
                $next = false;
            }
        }

        //save new quotation form
        if ($next) {
            $result = $this->quotationforms_model->editQuotationForm();
            $this->data['debug'][] = $this->quotationforms_model->debug_data;
            if (!$result) {

                //show error
                $this->notices('error', $this->data['lang']['lang_request_has_been_completed'], 'noty');
                $this->notifications('wi_notification', $this->data['lang']['lang_request_could_not_be_completed']);

                //hide form
                $this->data['visible']['wi_quotation_form_edit'] = 0;

                //halt
                $next = false;
            }

        }

        //an error occurred
        if (!$next) {

            /*-----------------------------------------------------------------------------------------
            * display posted formbuilder data
            * turn the posted form data into a php array
            * pull out the 'fields' array
            * save to [vars[ for dynamic inclusion in html
            * NB: this is necessay only becase formbuilder.js adds the posted data in an array
            *     and so cannot be used directly as it was posted
            *-----------------------------------------------------------------------------------------*/
            $posted_form_data = json_decode($this->input->post('quotationforms_code'), true);
            $this->data['vars']['quotationforms_code'] = json_encode($posted_form_data['fields']);

            //avoid js errors if something has gone wrong with retrieved data
            if ($this->data['vars']['quotationforms_code'] == '') {
                $this->data['vars']['quotationforms_code'] = '[]';
            }

            //set form visible
            $this->data['visible']['wi_quotation_form_edit'] = 1;

        }

        //all went ok - reload form
        if ($next) {
            //show success
            $this->notices('success', $this->data['lang']['lang_the_form_has_been_saved_to_the_database'], 'noty');

            //flow
            $next = true;

            //re-load form
            $this->__viewQuotationForm();
        }

    }

    /**
     * takes all posted (search form) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     *
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
        redirect("admin/quotationforms/list/$search_id");

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
        if ($form == 'add_form' || $form == 'edit_form') {

            //check required fields
            $fields = array('quotationforms_title' => $this->data['lang']['lang_title']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //everything ok
            return true;
        }

        //-----------validate hidden fields--------------------------------
        if ($form == 'add_form_hidden') {

            //check required fields
            $fields = array('quotationforms_code' => 'Formbuilder Content');
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //everything ok
            return true;
        }

        //-----------validate hidden fields--------------------------------
        if ($form == 'edit_form_hidden') {

            //check required fields
            $fields = array(
                'quotationforms_code' => 'Formbuilder Content',
                'quotationforms_id' => 'Formbuilder ID',
                'quotationforms_status' => 'Formbuilder Status');
            if (!$this->form_processor->validateFields($fields, 'required')) {
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
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     *
     */
    function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //[all_team_members]
        $data = $this->teamprofile_model->allTeamMembers('team_profile_full_name', 'ASC');
        $this->data['debug'][] = $this->teamprofile_model->debug_data;
        $this->data['lists']['all_team_members'] = create_pulldown_list($data, 'team_members', 'id');

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

/* End of file quotationforms.php */
/* Location: ./application/controllers/admin/quotationforms.php */
