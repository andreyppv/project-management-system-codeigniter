<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// -- regex_is_domain -------------------------------------------------------------------------------------------------
/**
 * returns array with all the other pagination settings that we dont want to cluter the controller with
 */
if (!function_exists('pagination_default_config')) {
    function pagination_default_config()
    {

        //get $CI instance
        $CI = &get_instance();

        //create the pagination config array
        $config = array();
        $config['num_links'] = 4;
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active"><span>';
        $config['cur_tag_close'] = '<span class="sr-only">(current)</span></span></li>';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['first_link'] = '&laquo;';
        $config['prev_link'] = '&lsaquo;';
        $config['last_link'] = '&raquo;';
        $config['next_link'] = '&rsaquo;';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';

        //return the config array
        return $config;
    }
}

/* End of file pagination_helper.php */
/* Location: ./application/helpers/pagination_helper.php */
