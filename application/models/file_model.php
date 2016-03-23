<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class File_model extends BF_Model
{
    protected $table        = 'files';
    protected $key          = 'files_id';
    protected $date_format  = 'datetime';
    protected $set_created  = TRUE;
    protected $set_modified = FALSE;
    protected $created_field= 'files_created';
    //--------------------------------------------------------------------
    public function __construct()
    {
        parent::__construct();
        
        //$this->set_alias();
    }
}//end Settings_model
