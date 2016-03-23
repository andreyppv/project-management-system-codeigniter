<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Lead_model extends BF_Model
{
    protected $table        = 'leads';
    protected $key          = 'id';
    protected $date_format  = 'datetime';
    protected $set_created  = TRUE;
    protected $set_modified = FALSE;
    protected $created_field = 'leads_created';

    //--------------------------------------------------------------------
    public function __construct()
    {
        parent::__construct();
    }
}//end Settings_model
