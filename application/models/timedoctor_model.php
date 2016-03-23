<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Timedoctor_model extends Super_Model
{

    public $debug_methods_trail;

    protected $access_token = '';
    protected $user_id = 0;

    protected $_curl = null;

    // -- __construct ----------------------------------------------------------------------------------------------

    public function __construct()
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        // Call the Model constructor
        parent::__construct();
    }

    // -- setTimeDoctorKey--------------------------------------------------------------------------------------------
    /**
     * SAVE USERS TOKEN
     * @param	numeric [users_id]     
     * @param   string [code oauth/v2]
     * @return	boolen
     */

    public function setTimeDoctorToken($team_profile_id, $code)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        $data = $this->getContent($this->data['config']['timedoctor_url_access_token'].$code);

        //declare
        $conditional_sql = '';

        //validate
        if(!$data) {
            $this->__debugging(__line__, __function__, 0, "setTimeDoctorToken: Error getContent", '');
            return false;
        }        
        if(!isset($data['access_token'])) {
            $this->__debugging(__line__, __function__, 0, "setTimeDoctorToken: Invalid Data [access_token]", '');
            return false;
        }
        if(!isset($data['expires_in']) || !is_numeric($data['expires_in']) || $data['expires_in'] < 86400 ) {
            $this->__debugging(__line__, __function__, 0, "setTimeDoctorToken: Invalid Data [expires_in]", '');
            return false;
        }
        if(!isset($data['token_type'])) {
            $this->__debugging(__line__, __function__, 0, "setTimeDoctorToken: Invalid Data [token_type]", '');
            return false;
        }
        if(!isset($data['refresh_token'])) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [refresh_token]", '');
            return false;
        }


        //escape params items
        $team_profile_id = intval($team_profile_id);
        
        $this->access_token = $data['access_token'];

        $access_token = $this->db->escape($data['access_token']);
        $token_type = $this->db->escape($data['token_type']);
        $refresh_token = $this->db->escape($data['refresh_token']);
        $expires_in = date('Y-m-d', $data['expires_in'] + time());

        //----------get time doctor user id----------
        $data = $this->getCompanies($team_profile_id);
        $this->user_id = 0;
        //print_r($data); exit;
        if(isset($data['accounts']))
        {

            foreach($data['accounts'] as $a)
            {
                if($a['company_id'] == $this->data['config']['timedoctor_company_id'])
                {
                    $this->user_id = $a['user_id'];
                    break;
                }
            }
        }
        if(!$this->user_id)
        {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [unknow user]", '');
            return false;
        }


        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $conditional_sql = "UPDATE team_profile SET team_profile_timedoctorid=".$this->user_id." WHERE team_profile_id = $team_profile_id";
        $this->db->query($conditional_sql);

        $conditional_sql = "
            REPLACE INTO timedoctor_tokens
            (team_profile_id, access_token, token_type, refresh_token, expires_in, timedoctor_user_id) VALUES
            ($team_profile_id, $access_token, $token_type, $refresh_token, '$expires_in', ".$this->user_id.")
        ";
        $this->db->query($conditional_sql);

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        //$this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        return true;
    }

    // -- getCompanies--------------------------------------------------------------------------------------------
    /**
     * return array
     *   {
     *    "user": {
     *      "full_name": "User  Name",
     *      "email": "user@com.ru",
     *      "url": "https://webapi.timedoctor.com/v1.1/companies"
     *    },
     *    "accounts": [
     *      {
     *        "user_id": 9999,
     *        "company_id": 1111,
     *        "type": "user",
     *        "company_name": "ISO Developers - ISO Doc, Inc.",
     *        "url": "https://webapi.timedoctor.com/v1.1/companies/1111/users/9999"
     *      }
     *    ]
     *  }
     * @return  boolen
     */
    public function getCompanies($team_profile_id = 0)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        $this->initVars($team_profile_id);

        $url = '/companies?access_token={access_token}&_format=json';
        $data = $this->getContent($url);
        
        //debugging data
        //$this->__debugging(__line__, __function__, $execution_time, __class__, '');

        return $data;
    }

    // -- getProjects--------------------------------------------------------------------------------------------
    /**
     * return array
     * @return  boolen
     */
    public function getProjects($team_profile_id = 0)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        $this->initVars($team_profile_id);

        $url = '/companies/{company_id}/users/{user_id}/projects?access_token={access_token}&_format=json';
        $data = $this->getContent($url);
        
        //debugging data
        //$this->__debugging(__line__, __function__, $execution_time, __class__, '');

        return $data;
    }

    // -- getTasks--------------------------------------------------------------------------------------------
    /**
     * return array
     * @return  boolen
     */
    public function getTasks($team_profile_id = 0, $active = null)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        $this->initVars($team_profile_id);

        // status = all/active
        $url = '/companies/{company_id}/users/{user_id}/tasks?access_token={access_token}&_format=json&status=all';
        $data = $this->getContent($url);
        
        if($active !== null && isset($data['tasks']))
        {
            $active = (bool)$active;
            foreach($data['tasks'] as $k=>$v)
            {
                if($v['active'] != $active)
                {
                    unset($data['tasks'][$k]);
                }
            }
        }

        //debugging data
        //$this->__debugging(__line__, __function__, $execution_time, __class__, '');

        return $data;
    }


    // -- getExpTokens--------------------------------------------------------------------------------------------
    /**
     * return array
     * @return  boolen
     */
    public function getExpTokens()
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;


        $sql = "SELECT * FROM timedoctor_tokens WHERE expires_in < DATE_ADD(NOW(), INTERVAL 1 DAY)";
        $query = $this->db->query($sql);

        return $query->result_array();
    }

    // -- updateTokens--------------------------------------------------------------------------------------------
    /**
     * return array
     * @return  boolen
     */
    public function updateTokens($team_profile_id, $refresh_token)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        $url = 'https://webapi.timedoctor.com/oauth/v2/token?client_id={client_id}&client_secret={secret_key}&grant_type=refresh_token&refresh_token='.$refresh_token;
        $data = $this->getContent($url);

        if(isset($data['access_token']))
        {
            $data['expires_in'] = date('Y-m-d', $data['expires_in'] + time());

            $data = array(
                    'access_token'  => $data['access_token'],
                    'expires_in'    => $data['expires_in'],
                    'token_type'    => $data['token_type'],
                    'refresh_token' => $data['refresh_token']
            );
            $this->db->where('team_profile_id', $team_profile_id);
            $this->db->update('timedoctor_tokens', $data);
            return true;
        }
        else
        {
            $this->__debugging(__line__, __function__, 0, "Invalid Data ".print_r($data, true), '');
            print_r($data);
            return false;
        }
    }


    // -- createTask--------------------------------------------------------------------------------------------
    /** param task array (
    *       task_name string,
    *       user_id  - is developer time doctor user id
    * )
     * @return time doctor task id
     */
    public function createTask($team_profile_id = 0, $task = array())
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //validate
        if(!isset($task['task_name'])) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [task_name]", '');
            return false;
        }
        if(!isset($task['user_id'])) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [user_id]", '');
            return false;
        }
        if(!isset($task['project_id'])) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [project_id]", '');
            return false;
        }


        $this->initVars($team_profile_id);

        $url = '/companies/{company_id}/users/{user_id}/tasks?access_token={access_token}';

        $task = array('_format'=>'json', 'task'=>$task);
        $data = $this->getContent($url, 'POST', $task);
        
        //debugging data
        //$this->__debugging(__line__, __function__, $execution_time, __class__, '');

        return $data;
    }


    // -- createProject--------------------------------------------------------------------------------------------
    /** param project array (
    *       project_name string
    * )
     * @return time doctor project id
     */
    public function createProject($team_profile_id = 0, $project = array())
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //validate
        if(!isset($project['project_name'])) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [project_name]", '');
            return false;
        }

        if($team_profile_id <= 0)
            $team_profile_id = $this->data['config']['timedoctor_admin_profile_id'];

        $this->initVars($team_profile_id);

        $url = '/companies/{company_id}/users/{user_id}/projects?access_token={access_token}';

        $project = array('_format'=>'json', 'project'=>$project);
        $data = $this->getContent($url, 'POST', $project);
        
        $this->access_token = '';

        //debugging data
        //$this->__debugging(__line__, __function__, $execution_time, __class__, '');

        return $data;
    }

    // -- editTask--------------------------------------------------------------------------------------------
    /** 
     * $active = 'True' or 'False'
     * @return
     */
    public function editTask($team_profile_id = 0, $task_id = 0, $active = true, $task_name = '', $project_id = 0, $user_id = 0)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        $this->initVars($team_profile_id);

        $url = '/companies/{company_id}/users/{user_id}/tasks/'.$task_id.'?access_token={access_token}';

        $task = array();
        $task['active']                        = (bool)$active;
        if($task_name)     $task['task_name']  = $task_name;
        if($project_id)    $task['project_id'] = $project_id;
        if($user_id)       $task['user_id']    = $user_id;

        $task = array('_format'=>'json', 'task'=>$task);
        $data = $this->getContent($url, 'PUT', $task);
        
        //debugging data
        //$this->__debugging(__line__, __function__, $execution_time, __class__, '');

        return $data;
    }


    // -- editTask--------------------------------------------------------------------------------------------
    /** 
     * $archived = True or False
     * @return
     */
    public function editProject($team_profile_id = 0, $project_id = 0, $archived = true, $project_name = '')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        $this->initVars($team_profile_id);

        $url = '/companies/{company_id}/users/{user_id}/projects/'.$project_id.'?access_token={access_token}';

        $project = array();
        $project['archived'] = (bool)$archived;
        if($project_name)    $project['project_name']  = $project_name;

        $project = array('_format'=>'json', 'project'=>$project);
        $data = $this->getContent($url, 'PUT', $project);
        
        //debugging data
        //$this->__debugging(__line__, __function__, $execution_time, __class__, '');

        return $data;
    }


    // -- getWorklogs--------------------------------------------------------------------------------------------
    /**
     * start_date  [string] 'yyyy-mm-dd' Start date you want to fetch data for
     * end_date    [string] 'yyyy-mm-dd'  End date you want to fetch data for
     * user_ids    [string] separated by comma List of user ids you want to fetch data for. If left blank, data will be returned for all users.
     * project_ids [string] separated by comma List of project ids you want to fetch data for. If left blank, data will be returned for all projects.
     * return array
     * @return  boolen
     */
    public function getWorklogs($team_profile_id = 0, $start_date = '', $end_date = '', $task_id = 0)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        $this->initVars($team_profile_id);

        if(!$start_date) $start_date = date('Y-m-d', time()-86400);
        if(!$end_date)   $end_date = date('Y-m-d', time());

        
        $result = array(
            'total_time' => 0 //hours
        );

        $url  = '/companies/{company_id}/worklogs?access_token={access_token}&_format=json';
        $url .= '&start_date='.$start_date;
        $url .= '&end_date='.$end_date;
        $url .= '&consolidated=true';

        $data = $this->getContent($url);
        if(isset($data['worklogs']['items']))
        {
            foreach ($data['worklogs']['items'] as $k => $v)
            {
                if($v['task_id'] == $task_id || $task_id == 0)
                {
                    $result['total_time'] += $v['length'];
                }
            }
        }
        else
        {        
            //debugging data
            //print_r($data);
            $this->__debugging(__line__, __function__, 0, "Error [worklogs]", print_r($data, 1));
            return false;
        }

        return $result;
    }

    // -- initVars--------------------------------------------------------------------------------------------
    /**
     * return array
     * @param   string [url]     
     * @param   array [data]
     * @return  array
     */
    public function initVars($team_profile_id = 0)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        if(!$team_profile_id || !is_numeric($team_profile_id))
        {
            //$team_profile_id = $this->data['vars']['my_id'];
            $team_profile_id = $this->data['vars']['my_id'];
        }

        $conditional_sql = "SELECT * FROM timedoctor_tokens WHERE team_profile_id = $team_profile_id";
        $query = $this->db->query($conditional_sql);
        $row = $query->row_array();
        if($row)
        {
            $this->access_token = $row['access_token'];
            $this->user_id = $row['timedoctor_user_id'];
        }
        else {
            $this->__debugging(__line__, __function__, 0, "Invalid team_profile_id or timedoctor_user_id", $team_profile_id);
            return false;
        }
        //debugging data
        //$this->__debugging(__line__, __function__, $execution_time, __class__, $conditional_sql);

        return $data;
    }


    // -- getContent--------------------------------------------------------------------------------------------
    /**
     * return array
     * @param   string [url]     
     * @param   array [data]
     * @return  array
     */
    public function getContent($url, $method = 'GET', $post_data = array())
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        if(!$this->access_token)
        {
            $this->initVars();
        }

        if(strpos($url, 'http') === false)
        {
            $url = $this->data['config']['timedoctor_url_base'].$url;
        }

        $replace = array(
            '{access_token}'  => $this->access_token,
            '{user_id}'       => $this->user_id,
            '{company_id}'    => $this->data['config']['timedoctor_company_id'],
            '{client_id}'     => $this->data['config']['timedoctor_client_id'],
            '{secret_key}'    => $this->data['config']['timedoctor_secret_key']
        );

        $url = str_replace(array_keys($replace), array_values($replace), $url);
//echo $url; exit;
        //-------connect ---------
        if(!$this->_curl)
        {
            $this->_curl = curl_init();
            curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($this->_curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        }
        //post data
        if($method === 'POST' || $method === 'PUT')
        {
            $post_data = json_encode($post_data);
            //echo $post_data; exit;
            curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $post_data);
        }
        else
        {
            curl_setopt($this->_curl, CURLOPT_POST, FALSE);
        }
        curl_setopt($this->_curl, CURLOPT_URL, $url);
        $data = curl_exec($this->_curl);

        //$data = file_get_contents($url);
        $data = json_decode($data, true);

        if($data['code'] != 200)
        {
            $this->__debugging(__line__, __function__, 0, $data['error'].':'.$data['description'], '');
        }

        return $data;
    }

}

/* End of file timedoctor_model.php */
/* Location: ./application/models/timedoctor_model.php */
