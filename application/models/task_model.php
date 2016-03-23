<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Task_model extends BF_Model
{
    protected $table        = 'tasks';
    protected $key          = 'tasks_id';
    protected $date_format  = 'datetime';
    protected $set_created  = TRUE;
    protected $set_modified = TRUE;
	protected $created_field = 'created';
	protected $modified_field= 'updated';
    //--------------------------------------------------------------------
    public function __construct()
    {
        parent::__construct();
        
        //$this->set_alias();
    }
    
    public function join_assigned_to()
    {
        return $this->join('team_profile t2', 't1.tasks_assigned_to_id = t2.team_profile_id', 'left');
    }
    
    public function join_assigned_by()
    {
        return $this->join('team_profile t3', 't1.tasks_created_by_id = t3.team_profile_id', 'left');
    }      
    
    public function join_billing_category()
    {
        return $this->join('billing_categories t4', 't4.bcat_id=billingcategory', 'left');
    }      
}//end Settings_model
