<?php

class Share extends MY_Controller
{

    // --  constructor- -------------------------------------------------------------------------------------------------------
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //reduce error reporting to only critical
        error_reporting(E_ERROR);

        //turn off codeigniter profiler (which may be on in MY_Controller) during debug mode
        $this->output->enable_profiler(false);

    }

    // -- index -------------------------------------------------------------------------------------------------------
    /**
     * This is our re-routing function and is the inital function called
     *
     * @access	public
     * @param	void
     * @return void
     */
    public function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
        
        //download helper
        $this->load->helper('download');

        //uri - action segment
        $share_link = $this->uri->segment(3);
        
        $next = true;

        //get file detalils from database
        $result = $this->files_model->getFileShareLink($share_link);
        if(!$result)
        {
            //log this error
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: File download failed. File not found in database]");
            //redirect
            redirect('common/error/not-found');

            $next = false; //just in case
        }

        if($next)
        {
            $file_id = $result['files_id'];
            $file_name = $result['files_name'];
            $file_foldername = $result['files_foldername'];
            $project_id = $result['files_project_id'];
            $file_path = FILES_PROJECT_FOLDER . $project_id . '/' . $file_foldername . '/' . $file_name;

            if (is_file($file_path)) {

                //increase download counter
                $this->files_model->downloadCounter($file_id);

                //force browser to download file
                $file_data = file_get_contents($file_path); // Read the file's contents
                force_download($file_name, $file_data);

            } else {

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: File download failed. File not found on server drive]");
                //redirect
                redirect('common/error/not-found');
            }

        }
    }
}

/* End of file ajax.php */
/* Location: ./application/controllers/common/ajax.php */
