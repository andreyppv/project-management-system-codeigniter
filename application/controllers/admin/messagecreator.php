<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all clients related functions
 */
class MessageCreator extends MY_Controller
{

    /**
     * constructor method
     */
    function __construct()
    {

        parent::__construct();
		$this -> load -> database();
		
        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'messagecreator.html';

        //css settings
        $this->data['vars']['css_menu_dashboard'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_dashboard'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-home"></i>';
        
    
    }

    /**
     * This is our re-routing function and is the inital function called
     * 
     */
    function index()
    {
    	
		

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();
        
		$this->data['vars']['mail']=$query = $this->db->get_where('failed_mails', array('failed_mails_id' => $this->uri->segment(3)))->result()[0];
		
        if($_POST){
        	$data=array();
            foreach ($_POST as $key => $value) {
            	$data[$key]=$value;
            }
			$data[messages_date]=date('Y-m-d H:i:s');
			if (isset($data['messages_by']))
			{
				if ($data['messages_by'] == 'team')
				{
					$data['isclient']=0;
				}
				else
				{
					$data['isclient']=1;
				}
			}
			
			if ($data['type']==1)
			{
				unset($data['type']);
				$done=$this->db->insert('messages',$data);
			}
			elseif ($data['type']==2)
			{
				unset($data['type']);
				$done=$this->db->insert('team_messages',$data);
			}

			
            

            echo "<script>alert('Message added');</script>";
        }

        $this->__flmView('admin/main');

    }

    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']); //complete the view
        $this->__commonAll_View($view);
    }
}