<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Welcome extends CI_Controller
{

    /**
     * redirect and any requests on site's base url to client section
     *
     */
    public function index()
    {
        redirect('/client');
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
