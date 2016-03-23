<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Project_model extends BF_Model
{
    protected $table        = 'projects';
    protected $key          = 'projects_id';
    protected $date_format  = 'date';
    protected $set_created  = TRUE;
    protected $set_modified = FALSE;
	protected $created_field = 'projects_date_created';

    //--------------------------------------------------------------------
    public function __construct()
    {
        parent::__construct();
        
        //$this->set_alias();
    }
}//end Settings_model
