<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Bug_model extends BF_Model
{
    protected $table        = 'bugs';
    protected $key          = 'bugs_id';
    protected $date_format  = 'datetime';
    protected $set_created  = TRUE;
    protected $set_modified = FALSE;
    protected $created_field='bugs_date';

    //--------------------------------------------------------------------
    public function __construct()
    {
        parent::__construct();
        
        //$this->set_alias();
    }
}//end Settings_model
