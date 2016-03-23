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
class Test extends MY_Controller
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

    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    function index()
    {
//$this->share();
return;
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //$this->load->model('timedoctor_model');
        //$d = $this->timedoctor_model->getCompanies();
        //$d = $this->timedoctor_model->getProjects();
$d = $this->timedoctor_model->getTasks(0, true);

        //$d = $this->timedoctor_model->getWorklogs(18, '','', 7127369);
//task info https://webapi.timedoctor.com/v1.1/companies/306864/users/411182/tasks/7301595?access_token=NTRhN2ZiMTIyMjBlNmFhYTkzNWEzNGFhNmM4ODIxNTRmNzg1NjI2YzhkMWQ3Nzc5NDY1ZmY0NGFkOWZiNTc1Mg
        //$d = $this->timedoctor_model->editTask(0, 7300313, false, '7300313 First of all, we need the "not exist time doctor" switched to "Create TimeDoctor Task" with Green BG.');
        //$d = $this->timedoctor_model->createProject(1, array('project_name'=>'Greenthal'));
        //$d = $this->timedoctor_model->editProject(1, 225634, false, 'RX Supplements');

            /*$task = array(
                'task_name'  => 'Test 21.10',
                'project_id' => 204984,
                'user_id'    => 411182
            );
            $d = $this->timedoctor_model->createTask($this->data['vars']['my_id'], $task);*/
        print_r($d);
        $debug = $this->timedoctor_model->debug_data;
        print_r($debug);

    }

    function share()
    {
        for($i=1; $i<900; $i++)
        {
            $share = '';
            while ($share == '')
            {
                $share = random_string('alnum', 12);
                $row = $this->db->query("select 1 from files where files_share_link='".$share."'")->row();
                if($row)
                {
                    echo 1;
                    $share = '';
                }
            }            
            $this->db->where('files_id', $i);
            $this->db->update('files', array(
                    'files_share_link' => $share
                ));
        }
        echo 3;
    }

}

/* End of file users.php */
/* Location: ./application/controllers/admin/users.php */
