<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Invoiceitems related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Invoiceitems extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'invoice.items.html';
        $this->data['vars']['css_submenu_invoices'] = 'style="display:block; visibility:visible;"';

        //css settings
        $this->data['vars']['css_menu_invoices'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_invoice_items'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-list-alt"></i>';

    }

    /**
     * This is our re-routing function and is the inital function called
     */
    function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'view':
                $this->__viewItems();
                break;

            case 'add':
                $this->__addItems();
                break;

            case 'edit':
                $this->__editItems();
                break;

            case 'search-items':
                $this->__cachedFormSearch();
                break;

            default:
                $this->__someDefault();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * list/search for invoice items
     */
    function __viewItems()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/invoiceitems/view/54/desc/sortby_id/0
        * (2)->controller
        * (3)->router
        * (4)->search id
        * (5)->sort_by
        * (6)->sort_by_column
        * (7)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //uri segments
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_id' : $this->uri->segment(6);
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'items';
        $this->data['blocks']['items'] = $this->invoice_items_model->listItems($offset, 'search');
        $this->data['debug'][] = $this->invoice_items_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->invoice_items_model->listItems($offset, 'count');
        $this->data['vars']['invoice_items_count'] = $rows_count;
        $this->data['debug'][] = $this->invoice_items_model->debug_data;

        //pagination
        $config = pagination_default_config();
        $config['base_url'] = site_url("admin/invoiceitems/view/$search_id/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 7; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        //the array names mustbe same as used in invoice_items_model
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_id',
            'sortby_date',
            'sortby_amount');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/invoiceitems/view/$search_id/$link_sort_by/$column/$offset");
        }

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blocks']['items'])) {
            $this->data['visible']['wi_invoice_items_table'] = 1;
        } else {
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     * add a new invoice item
     */
    function __addItems()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            redirect('/admin/invoiceitems/view');
        }

        //validate form & display any errors
        $validation = $this->__flmFormValidation('add_item');
        if (!$validation) {

            //show error
            $this->notices('error', $this->form_processor->error_message);

        } else {

            //save information to database
            if (!$result = $this->invoice_items_model->addItem()) {

                //halt
                $next = false;
            }
            $this->data['debug'][] = $this->invoice_items_model->debug_data;

            //all is ok
            if ($next) {
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);
            } else {
                $this->notices('error', $this->data['lang']['lang_an_error_has_occurred']);
            }

        }

        //load list
        $this->__viewItems();
    }

    /**
     * edit a new invoice item
     */
    function __editItems()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            redirect('/admin/invoiceitems/view');
        }

        //validate form & display any errors
        $validation = $this->__flmFormValidation('edit_item');
        if (!$validation) {

            //show error
            $this->notices('error', $this->form_processor->error_message);

        } else {

            //save information to database
            if (!$result = $this->invoice_items_model->editItem()) {

                //halt
                $next = false;
            }
            $this->data['debug'][] = $this->invoice_items_model->debug_data;

            //all is ok
            if ($next) {
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);
            } else {
                $this->notices('error', $this->data['lang']['lang_an_error_has_occurred']);
            }

        }

        //load list
        $this->__viewItems();
    }

    /**
     * takes all posted (search form) data and saves it to an array
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
        redirect("admin/invoiceitems/view/$search_id");

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
        if ($form == 'add_item' || $form == 'edit_item') {

            //check required fields
            $fields = array('invoice_items_title' => $this->data['lang']['lang_title'], 'invoice_items_amount' => $this->data['lang']['lang_amount']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check numeric fields
            $fields = array('invoice_items_amount' => $this->data['lang']['lang_amount']);
            if (!$this->form_processor->validateFields($fields, 'numeric')) {
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
     * log any error message into the log file
     */
    function __errorLogging($theline = '', $thefunction = '', $thefile = '', $themessage = 'system error')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        $message_log = "[FILE: $thefile]  [LINE: $theline]  [FUNCTION: $thefunction]  [MESSAGE: $themessage]";
        log_message('error', $message_log);

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

/* End of file invoiceitems.php */
/* Location: ./application/controllers/admin/invoiceitems.php */
