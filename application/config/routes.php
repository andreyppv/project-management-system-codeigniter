<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


//admin
$route['home/(:any)'] = "admin/$1";
$route['admin'] = "admin/home";
$route['admin/settings/([a-z]+)'] = "admin/settings/$1";
$route['admin/settings/([a-z]+)/(.*)'] = "admin/settings/$1";
$route['admin/([a-z]+)'] = "admin/$1";
$route['admin/([a-z]+)/(.*)'] = "admin/$1";
$route['admin/(:any)'] = "admin/error";

//client
$route['client'] = "client/home";
$route['client/([a-z]+)'] = "client/$1";
$route['client/([a-z]+)/(.*)'] = "client/$1";
$route['client/(:any)'] = "client/error";

//common
$route['common/([a-z]+)'] = "common/$1";
$route['common/([a-z]+)/(.*)'] = "common/$1";
$route['common/(:any)'] = "common/error";

//API
$route['api'] = "api/home";
$route['api/([a-z]+)'] = "api/$1";
$route['api/([a-z]+)/(.*)'] = "api/$1";


//default
$route['default_controller'] = "common/welcome";
$route['404_override'] = 'common/welcome';
/* End of file routes.php */
/* Location: ./application/config/routes.php */
