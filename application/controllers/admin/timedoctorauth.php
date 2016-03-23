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
class Timedoctorauth extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'timedoctorauth.modal.html';

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

        $code = $this->input->get('code');
        if($code)
        {
            $result = $this->timedoctor_model->setTimeDoctorToken($this->data['vars']['my_id'], $code);
            $this->data['debug'][] = $this->timedoctor_model->debug_data;
            if(!$result)
            {
                print_r($this->timedoctor_model->debug_data);
                $this->notifications('wi_notification', 'Error');
            }
            else
            {
                $this->notifications('wi_notification', 'Success!');
            }
        }
        else
        {
            $this->notifications('wi_notification', 'Error');
        }
        $this->data['visible']['wi_notification'] = 1;
        //load view
        $this->__flmView('admin/main');
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

        //post data
        $this->data['post'] = $_POST;

        //get data
        $this->data['get'] = $_GET;

        //url segmets array
        $this->data['url_segments'] = $this->uri->segment_array();

        $this->load->view($view, array('data' => $this->data));
    }
}

/* End of file users.php */
/* Location: ./application/controllers/admin/users.php */
