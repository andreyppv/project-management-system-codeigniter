<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all upload files related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Upload extends MY_Controller
{
    public $jsondata = array();


    // --  constructor- -------------------------------------------------------------------------------------------------------
    public function __construct()
    {

        parent::__construct();

        //reduce error reporting to only critical
        error_reporting(E_ERROR);

        //turn off codeigniter profiler (which may be on in MY_Controller) during debug mode
        $this->output->enable_profiler(false);

    }

    // -- index -------------------------------------------------------------------------------------------------------
    /**
     * This is our re-routing function and is the inital function called
     */
    public function index()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            default:
            case 'project':
                $this->__uploadProject();
                break;
        }

        //log debug data
        $this->__ajaxDebugging();

    }


    // -- __uploadProject- -------------------------------------------------------------------------------------------------------
    protected function __uploadProject()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;
		
        $files_path = $_SERVER['DOCUMENT_ROOT'].'/files/projects/';

        $files_project_id = intval($this->input->get('files_project_id'));
        $files_client_id = intval($this->input->get('files_client_id'));
        $files_events_id = $this->input->get('files_events_id');
        $files_uploaded_by = $this->input->get('files_uploaded_by');
        $files_uploaded_by_id = intval($this->input->get('files_uploaded_by_id'));
        $files_size = intval($this->input->get('files_size'));

        $this->jsondata['error'] = '';
        $this->jsondata['link'] = '';

		if(
            !$files_project_id ||
            !isset($_FILES['file']['tmp_name'])
        )
        {
            $this->jsondata['error'] = 'No data';
            $next = false;
        }

        if($next)
        {
            $files_path .= $files_project_id.'/';
            if(!file_exists($files_path))
            {
                mkdir($files_path);
            }

            $folder_name = rand(0,9);

            $files_path .= $folder_name.'/';
            if(!file_exists($files_path))
            {
                mkdir($files_path);
            }

            //config
            $config = array();
            $config['upload_path'] = $files_path;
            $config['max_size'] = $this->config->item('files_max_size'); //in kilobytes
            $config['overwrite'] = false;
            $config['allowed_types'] = $this->config->item('files_allowed_types');
            $this->load->library('upload', $config);

            if ( ! $this->upload->do_upload('file'))
            {
                $this->jsondata['error'] = $this->upload->display_errors();
                $next = false;
            }
            else
            {
/*
Array
(
    [file_name] => Снимок_экрана_от_2015-11-10_23:24:41.png
    [file_type] => image/png
    [file_path] => /var/www/vhosts/isodeveloper.com/pms.isodeveloper.com/files/projects/95/8/
    [full_path] => /var/www/vhosts/isodeveloper.com/pms.isodeveloper.com/files/projects/95/8/Снимок_экрана_от_2015-11-10_23:24:41.png
    [raw_name] => Снимок_экрана_от_2015-11-10_23:24:41
    [orig_name] => Снимок_экрана_от_2015-11-10_23:24:41.png
    [client_name] => Снимок экрана от 2015-11-10 23:24:41.png
    [file_ext] => .png
    [file_size] => 156.29
    [is_image] => 1
    [image_width] => 1317
    [image_height] => 744
    [image_type] => png
    [image_size_str] => width="1317" height="744"
)
*/
                $data = $this->upload->data();

                $file = array(
                    'client_id'      => $files_client_id,
                    'description'    => $data['client_name'],
                    'events_id'      => $files_events_id,
                    'extension'      => str_replace('.', '', $data['file_ext']),
                    'foldername'     => $folder_name,
                    'name'           => $data['orig_name'],
                    'show_name'      => $data['file_name'],
                    'project_id'     => $files_project_id,
                    'uploaded_by'    => $files_uploaded_by,
                    'uploaded_by_id' => $files_uploaded_by_id,
                    'size'           => $data['file_size']*1024,
                    'size_human'     => convert_file_size($data['file_size']*1024)
                );

                $file_id = $this->files_model->addFile($file);
                $this->data['debug'][] = $this->files_model->debug_data;

                //$link = "http://pms.isodeveloper.com/files/projects/$files_project_id/$folder_name/".$data['orig_name'];
                $this->jsondata['link'] = "http://pms.isodeveloper.com/admin/file/$files_project_id/download/$file_id";
            }
        }

        //log debug data
        $this->__ajaxDebugging();

        //load the view for json
        $this->__flmView('common/json');
    }

    // -- fmlView-------------------------------------------------------------------------------------------------------
    /**
     * loads json outputting view
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
     * - ajax runs in the background, so we want to do as much logging as possibe for debugging
     * 
     */
    private function __ajaxDebugging()
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
            log_message('debug', "AJAX-LOG:: BIG DATA $all_data");
        }
    }

}

/* End of file upload.php */
/* Location: ./application/controllers/admin/upload.php */
