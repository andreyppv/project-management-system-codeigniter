<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all cronjobs related functions
 * [TYPICAl CRON URL] http://www.yourdomain.com/admin/cronjobs/general/AHusEwFd8HDg630sk
 * [SECURITY KEY] This must be changed in /config/settings.php for security
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Cronjobs extends MY_Controller
{

    /**
     * constructor method
     */
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        set_time_limit(1000);
        ini_set('display_errors', 1);
    }

    /**
     * This is our re-routing function and is the inital function called
     *
     */
    public function index()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check authentication key
        if ($this->uri->segment(4) == $this->config->item('security_key'))
        {
            //uri - action segment
            $action = $this->uri->segment(3);

            //re-route to correct method
            switch ($action)
            {
                case 'general':
                    $this->__generalCron();
                    break;
                case 'update-tokens':
                    $this->__updateUserTokens();
                    break;
                case 'tasks-logged-time':
                    $this->__tasksLoggedTime();
                    break;
                case 'readmail':
                    $this->__readMail();
                    break;
                default:
                    $this->__defaultCron();
            }
        }
        else
        {
            echo 'Permission Denied';
        }
    }

    /**
     * run the general cron
     *
     */
    protected function __generalCron()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //refresh milestone
        $this->refresh->milestones('all');

        //refresh tasks
        $this->refresh->taskStatus('all');

        //refresh tasks
        $this->refresh->projectStatus('all');

        //refresh invoice status
        $this->refresh->basicInvoiceStatus();

        //send emails that are in the queue
        $this->__emailQueue();

        //parse inbox email
        $this->__readMail();
    }

    /**
     * send emails that are in the queue
     *
     */
    protected function __emailQueue()
    {
//return;
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get email queue data
        $queue = $this->email_queue_model->getEmailBatch(30);
        $this->data['debug'][] = $this->email_queue_model->debug_data;

        //loop throuh and send emails
        $delete_list = '';
        $found_email = false; //reset
        if($next && is_array($queue))
        {
            for($i = 0; $i < count($queue); $i++)
            {

                //reset email settings
                $this->email->clear();

                //send email
                email_default_settings(); //defaults (from emailer helper)

                //send
                $this->email->to($queue[$i]['email_queue_email']);
                $this->email->subject($queue[$i]['email_queue_subject']);
                $this->email->message($queue[$i]['email_queue_message']);
                $this->email->send();

                //comma separated list for later deleting from queue
                $delete_list .= ',' . $queue[$i]['email_queue_id'];

                //we sent some emails
                $found_email = true;
            }
        }

        //delete emails that have been sent
        if ($next && $found_email) {
            //prepre list of email id's
            $delete_list = trim($delete_list, ',');
            //delete emails
            $this->email_queue_model->deleteFromQueue($delete_list);
            $this->data['debug'][] = $this->email_queue_model->debug_data;
        }

    }

    /**
     * update time doctor tokens
     *
     */
    protected function __updateUserTokens()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        $this->timedoctor_model->initVars($this->data['config']['timedoctor_admin_profile_id']);

        $users = $this->timedoctor_model->getExpTokens();

        $i = 0;
        foreach ($users as $u)
        {
            $result = $this->timedoctor_model->updateTokens($u['team_profile_id'], $u['refresh_token']);
            if(!$result)
            {
                $this->data['debug'][] = $this->timedoctor_model->debug_data;
            }
            else
            {
                $i++;
                //echo "ok\n";
            }
        }
        echo 'Updated: '.$i;
    }

    /**
     * tasks Logged time
     *
     */
    protected function __tasksLoggedTime()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        $tasks = $this->tasks_model->getListTimeDocTask();

        $start_date =  date('Y-m-d', time()-86400*365);
        $end_date =  date('Y-m-d', time());

        foreach ($tasks as $t)
        {
            $result = $this->timedoctor_model->getWorklogs($t['team_profile_id'], $start_date, $end_date, $t['timedoctortaskid']);
            if(!$result)
            {
                $debug = $this->timedoctor_model->debug_data;
                print_r($debug);
                //exit;
            }
            else
            {
                $hourslogged = round($result['total_time'] / 3600, 2);
                $this->tasks_model->updateHoursLogged($t['tasks_id'], $hourslogged);
                //echo "ok\n";
            }
        }
    }

    /*
        http://pms.isodeveloper.com/admin/cronjobs/readmail/AHusEwFd8HDg630sk
        ###do not remove this message###
    */
    protected function __readMail()
    {
        $this->load->model('imap_model');
        $emails = $this->imap_model->listUnseen('1');
        if($emails)
        {
            foreach ($emails as $key => $em)
            {
                //get user id
                $user_id = 0;
                //$em['email'] = 'david@isodevelopers.com';
                $em['identifier'] = intval($em['identifier']);

                $user = $this->clients_model->clientDetailsByEmail($em['email']);
                if(!empty($user['client_users_id']))
                {
                    $user_id = $user['client_users_id'];
                    $user_name = $user['client_users_full_name'];
                    $by = 'client';
                }

                if(!$user_id)
                {
                    $user = $this->teamprofile_model->getDetailsByEmail($em['email']);
                    if(!empty($user['team_profile_id']))
                    {
                        $user_id = $user['team_profile_id'];
                        $user_name = $user['team_profile_full_name'];
                        $by = 'team';
                    }
                }
                
                if($user_id && $em['identifier'] && $em['message'])
                {
                    //save client communications
                    $data = array(
                        'messages_project_id' => $em['identifier'],
                        'messages_by_id'      => $user_id,
                        'messages_by'         => $by,
                        'messages_text'       => $em['message']
                    );

                    $this->data['vars']['my_name'] = $user_name;

                    if($by === 'team')
                    {
                        $this->messages_model->addMessage($data);
                    }

                    if($by === 'client')
                    {
                        $this->messages_model->addMessageClient($data);
                    }

                    $this->project_id = $em['identifier'];
                    $this->__commonAll_ProjectBasics($this->project_id);

                    $this->data['vars']['my_email'] = $em['email'];
                    $this->__emailer('mailqueue_new_message', array('message'=>$em['message']));
                }
            }
        }
    }

    /**
     * nothing to see here
     *
     */
    protected function __defaultCron()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
    }

    // -- __emailer-------------------------------------------------------------------------------------------------------
    /**
     * send out an email
     *
     * @access  private
     * @param string
     * @return  void
     */
    private function __emailer($email = '', $vars = array())
    {

        //common variables
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];

        //------------------------------------queue email in database-------------------------------
        /** THIS WIL NOT SEND BUT QUEUE THE EMAILS*/
        if ($email === 'mailqueue_new_message') 
        {
            $sqldata = array();

            //email vars
            $this->data['email_vars']['projects_title'] = $this->data['fields']['project_details']['projects_title'];
            $this->data['email_vars']['usrname'] = $this->data['vars']['my_name'];
            $this->data['email_vars']['messages_text'] = $vars['message'];

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('client_communication');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //loop through all project members (mailing list)
            for ($i = 0; $i < count($this->data['vars']['project_mailing_list']); $i++)
            {
                //dynamic email vars based on (client/team) member
                $this->data['email_vars']['client_users_full_name'] = $this->data['vars']['project_mailing_list'][$i]['name'];

                if ($this->data['vars']['project_mailing_list'][$i]['user_type'] == 'team')
                {
                    $this->data['email_vars']['admin_dashboard_url'] = $this->data['vars']['site_url_admin'];
                    $this->data['email_vars']['reply_url'] = site_url('admin/messages/' . $this->project_id . '/view');
                }
                else
                {
                    $this->data['email_vars']['admin_dashboard_url'] = $this->data['vars']['site_url_client'];
                    $this->data['email_vars']['reply_url'] = site_url('client/messages/' . $this->project_id . '/view');
                }

                //set sqldata() for database
                $sqldata['email_queue_message'] = parse_email_template($template['message'], $this->data['email_vars']);

                $sqldata['email_queue_subject'] = $this->data['lang']['lang_project_update'] . ' - ' . $this->data['lang']['lang_new_message'].' | '.$this->project_id;
                $sqldata['email_queue_email']  = $this->data['vars']['project_mailing_list'][$i]['email'];

                //add to email queue database - excluding uploader (no need to send them an email)
                if($sqldata['email_queue_email'] != $this->data['vars']['my_email'])
                {
                    $this->email_queue_model->addToQueue($sqldata);
                    $this->data['debug'][] = $this->email_queue_model->debug_data;
                }
            }
        }
    }
}

/* End of file cron.php */
/* Location: ./application/controllers/admin/cron.php */
