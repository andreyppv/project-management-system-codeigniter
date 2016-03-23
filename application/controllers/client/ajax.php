<?php

class Ajax extends MY_Controller
{

    // --  constructor- -------------------------------------------------------------------------------------------------------
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //reduce error reporting to only critical
        @error_reporting(E_ERROR);

        //turn off codeigniter profiler (which may be on in MY_Controller) during debug mode
        $this->output->enable_profiler(false);

        //check if logged in
        $this->__LoggedInCheck();

    }

    // -- index -------------------------------------------------------------------------------------------------------
    /**
     * This is our re-routing function and is the inital function called
     *
     * @access	public
     * @param	void
     * @return void
     */
    function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {

            case 'validation-is-user-email-in-use':
                $this->__validationIsEmailInUse('client_user');
                break;

        }

        //log debug data
        $this->__ajaxdebugging();

    }

    // -- __LoggedInCheck- -------------------------------------------------------------------------------------------------------
    /**
     * checks if user is logged in
     *  
     * @access	private
     * @param	void
     * @return void
     */

    function __LoggedInCheck()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //is user logged in..else redirect to login page
        if (!is_numeric($this->session->userdata('client_users_clients_id'))) {

            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_session_timed_out'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);

            //log debug data
            $this->__ajaxdebugging();

            //load the view for json echo
            $this->__flmView('common/json');

            //now die and exit
            die($this->data['lang']['lang_session_timed_out']);
        }

    }

    // -- __default- -------------------------------------------------------------------------------------------------------
    /**
     * if nothing was passed in url
     *  
     * @access	private
     * @param	void
     * @return void
     */

    function __default($action = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        header('HTTP/1.0 400 Bad Request', true, 400);
        $this->jsondata = array(
            'result' => 'error',
            'message' => 'An error has occurred',
            'debug_line' => __line__);

        //log this error
        log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Routing errror. Specified method/action ($action) not found]");

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- fmlView-------------------------------------------------------------------------------------------------------
    /**
     * loads json outputting view
     *
     * @access	private
     * @param	string
     * @return void
     */
    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //sent to TBS engine
        $this->load->view($view, array('data' => $this->jsondata));
    }

    // -- DEBUGGING --------------------------------------------------------------------------------------------------------------
    /**
     * - saves ajax debug output to logfile, seeing as we cant echo or display it
     * 
     */
    function __ajaxdebugging()
    {

        //url segmets array
        $this->data['url_segments'] = $this->uri->segment_array();

        //format debug data for log file
        ob_start();
        print_r($this->data);
        print_r($this->jsondata);
        $all_data = ob_get_contents();
        ob_end_clean();

        //write to logi file
        if ($this->config->item('debug_mode') == 2 || $this->config->item('debug_mode') == 1) {
            log_message('error', "AJAX-LOG:: BIG DATA $all_data");
        }
    }

}

/* End of file ajax.php */
/* Location: ./application/controllers/client/ajax.php */
