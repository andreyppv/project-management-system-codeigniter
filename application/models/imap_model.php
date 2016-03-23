<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Imap_model extends Super_Model
{
    protected $_imap = null;

    // -- __construct ----------------------------------------------------------------------------------------------

    public function __construct()
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        // Call the Model constructor
        parent::__construct();
    }


    public function listUnseen($num = '1')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //connect
        $this->connect($num);

        //list emails
        $search = imap_search($this->_imap, 'UNSEEN');
        $emails = array();
        if($search)
        {
            foreach ($search as $uid)
            {
                //$overview = imap_fetch_overview($this->_imap, $uid, FT_UID);
                $info = imap_headerinfo($this->_imap, $uid, FT_UID);
//print_r($info);
                //$structure = imap_fetchstructure($this->_imap, $uid, FT_UID);
                $emails[$uid] = array();
                $emails[$uid]['message'] = imap_qprint(imap_fetchbody($this->_imap, $uid, 1));

                $base64 = base64_decode($emails[$uid]['message'], true);
                if($base64 !== false)
                {
                    $emails[$uid]['message'] = $base64;
                }
                //$emails[$uid]['message'] = imap_utf8($emails[$uid]['message']);

                $emails[$uid]['message'] = substr($emails[$uid]['message'], 0, strpos($emails[$uid]['message'], '###do not remove this message###'));
                $emails[$uid]['message'] = strip_tags($emails[$uid]['message']);    
                $emails[$uid]['message'] = htmlspecialchars($emails[$uid]['message'], ENT_IGNORE);    


                $emails[$uid]['email'] = $info->from[0]->mailbox . '@' . $info->from[0]->host;
                $emails[$uid]['subject'] = $info->subject;

                $i = strrpos($emails[$uid]['subject'], '|');
                if($i === false)
                    $emails[$uid]['identifier'] = '';
                else
                    $emails[$uid]['identifier'] = trim(substr($emails[$uid]['subject'], $i+1, strlen($emails[$uid]['subject'])-1));
            }
        }

        return $emails;
    }

    public function connect($num = '1')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;

        if(!$this->_imap)
        {
            $this->_imap = imap_open($this->data['config']['imap'.$num.'_host'],
                                     $this->data['config']['imap'.$num.'_login'],
                                     $this->data['config']['imap'.$num.'_pass']
                            ) or die('Cannot connect to Gmail: ' . imap_last_error());
        }
        return (bool)$this->_imap;
    }

}

/* End of file timedoctor_model.php */
/* Location: ./application/models/timedoctor_model.php */
