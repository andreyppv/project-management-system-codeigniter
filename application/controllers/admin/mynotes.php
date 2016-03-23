<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for team members notes for a specific project
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Mynotes extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.mynotes.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_my_project_notes'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-file-text-alt"></i>';

        //show table
        $this->data['visible']['wi_project_mynotes'] = 1;
    }

    /**
     * This is our re-routing function and is the inital function called
     */
    function index()
    {

        /* --------------URI SEGMENTS---------------
        * [segment example]
        * /admin/files/2/view/*.*
        * (2)->controller
        * (3)->project_id
        * (4)->router
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //get project id
        $this->project_id = $this->uri->segment(3);

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //get the action from url
        $action = $this->uri->segment(4);

        //route the request
        switch ($action) {

            case 'view':
                $this->__viewNotes();
                break;

            case 'edit':
                $this->__editNotes();
                break;

            case 'update':
                $this->__updateNotes();
                break;

            default:
                $this->__viewNotes();
                break;
        }

        //css - active tab
        $this->data['vars']['css_active_tab_mynotes'] = 'side-menu-main-active';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * view a members project note
     */
    function __viewNotes()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show text view
        $this->data['visible']['wi_my_note_view'] = 1;

        //check if team member has a note for this project
        $notes = $this->mynotes_model->checkNotes($this->project_id, $this->data['vars']['my_id']);

        //do it have a note for this project, if not create a blank one
        if ($notes === 0) {
            $this->mynotes_model->newNote($this->project_id, $this->data['vars']['my_id']);
            $this->data['debug'][] = $this->mynotes_model->debug_data;
        }

        //reload notes again
        //load team members first
        $this->data['reg_fields'][] = 'mynotes';
        $this->data['fields']['mynotes'] = $this->mynotes_model->getNotes($this->project_id, $this->data['vars']['my_id']);
        $this->data['debug'][] = $this->mynotes_model->debug_data;

    }

    /**
     * edit a member project note
     */
    function __editNotes()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //get the note
        $this->__viewNotes();

        //visibility
        $this->data['visible']['wi_my_note_edit'] = 1;
        $this->data['visible']['wi_my_note_view'] = 0;

    }

    /**
     * update a member's project note
     */
    function __updateNotes()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //prevent direct access
        if (!isset($_POST['posted'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('update', 'view', $this_url);
            redirect($redirect);
        }

        //save sql here
        $result = $this->mynotes_model->updateNote($this->project_id, $this->data['vars']['my_id'], $this->input->post('mynotes_text'));
        $this->data['debug'][] = $this->mynotes_model->debug_data;

        //check
        if ($result) {
            $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty'); //noty or html
        } else {
            $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty'); //noty or html
        }

        //get the note
        $this->__viewNotes();

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

/* End of file members.php */
/* Location: ./application/controllers/admin/members.php */
