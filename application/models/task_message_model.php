<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Task_message_model extends BF_Model
{
    protected $table        = 'task_messages';
    //protected $key          = 'id';
    protected $date_format  = 'datetime';
    protected $set_created  = TRUE;
    protected $set_modified = FALSE;

    //--------------------------------------------------------------------
    public function __construct()
    {
        parent::__construct();
    }
    
    public function join_member()
    {
        return $this->join('team_profile t2', 't1.created_by = t2.team_profile_id', 'left');
    }
}//end Settings_model
