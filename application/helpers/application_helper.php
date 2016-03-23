<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if(!function_exists('get_cost_per_hours')) 
{
	function get_cost_per_hours($billingcategory)
	{
		$costperhour = 0;
		switch($billingcategory) {
		  case 1:
			  $costperhour = 75;
			  break;
		  case 2:
			  $costperhour = 75;
			  break; 
		  case 3:
			  $costperhour = 95;
			  break;
		  case 4:
			  $costperhour = 125;
			  break;
		  case 5:
			  $costperhour = 125;
			  break;  
		  case 6:
			  $costperhour = 65;
			  break;
		  case 7:
			  $costperhour = 125;
			  break;
		  default:
			   $costperhour = 125;
		}
		
		return $costperhour;
	}
}

if(!function_exists('create_dropdown')) 
{
    function create_dropdown($array)
    {
        if(!is_array($array)) return '';
        
        $result = '';
        foreach($array as $k => $v)
        {
            $result .= "<option value='$k'>$v</option>";        
        }
        
        return $result;
    }
}

if(!function_exists('mkpath')) 
{
    function mkpath($path)
    {
        if(@mkdir($path) or file_exists($path)) return true;
        return (mkpath(dirname($path)) and mkdir($path));
    }
}

if(!function_exists('icon_path')) 
{
    function icon_path($ext)
    {
        if (is_file(FILES_FILETYPE_ICONS_FOLDER . '/' . $ext . '.png')) 
        {
            return FILES_FILETYPE_ICONS_FOLDER . '/' . $ext . '.png';
        } 
        
        return FILES_FILETYPE_ICONS_FOLDER . '/default.png';
    }
}
