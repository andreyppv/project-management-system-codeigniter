<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Team_profile_model extends BF_Model
{
    protected $table        = 'team_profile';
    protected $key          = 'team_profile_id';
    protected $date_format  = 'datetime';
    protected $set_created  = FALSE;
    protected $set_modified = FALSE;

    //--------------------------------------------------------------------
    public function __construct()
    {
        parent::__construct();
    }
    
    public function find_primary_id()
    {
        $result = $this->where('is_primary', 1)
            ->limit(1)
            ->find_all();
        
        if($result) return $result[0]->team_profile_id;
        
        return 0;
    }
}//end Settings_model
