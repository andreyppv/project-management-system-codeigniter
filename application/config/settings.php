<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|----------------------------------------------------------------------------------------------------
| SECURITY KEY
|----------------------------------------------------------------------------------------------------
|
| This key is used to enhance the security of your site, such as preventing anyone from runing 
| the cronjobs url.
|
| [WARNING]: Change it to something unique (BUT IT MUST BE ALPHA-NUMERIC ONLY)
| 
| [EXAMPLE]: 'Jfhe0j792j66291aph'
|
*/

$config['security_key'] = 'AHusEwFd8HDg630sk';

/*
|----------------------------------------------------------------------------------------------------
| UPLOAD SETTING - AVATARS
|----------------------------------------------------------------------------------------------------
|
| various file upload settings for avatars
| [files_avatar_max_size]      - set to: 0 for no limit OR set to a number (in kilobytes) e.g. 200
| [files_avatar_max_width]     - set to: 0 for no limit OR set to a number (in pixels) e.g. 100
| [files_avatar_max_height]    - set to: 0 for no limit OR set to a number (in pixels) e.g. 100
|
*/
$config['files_avatar_max_size']	= 500;
$config['files_avatar_max_width']	= 150;
$config['files_avatar_max_height']	= 150;


/*
|----------------------------------------------------------------------------------------------------
| UPLOAD SETTING - PROJECT FILES & TICKET ATTACHMENTS
|----------------------------------------------------------------------------------------------------
|
| various file upload settings for avatars
| [files_max_size]       - set to: 0 for no limit OR set to a number (in kilobytes) e.g. 200
| [files_allowed_types]
|                 - $config['files_allowed_types'] = 'msi|exe|bat|jpg|jpeg'; //file extension
|                 - $config['files_allowed_types'] = 0; //allow ALL file types (NOT RECOMENDED)
*/
$config['files_max_size']	= 20000; //in kilobytes
$config['files_allowed_types']	= 'jpg|jpeg|gif|png|tif|tiff|bmp|psd|zip|txt|html|htm|css|pdf|doc|docx|mp4|mp5|avi|mp3|divx|xls|ppt|mov|wav|flv|wma|txt|m4a|dwg|pub|swf|indd|iso|fla|gz|rtf|vob|3gp|ttf|tgz|log|mid|m4v|ogg|rar|wmv';



/*
|----------------------------------------------------------------------------------------------------
| TIMEDOCTOR SETTING
|----------------------------------------------------------------------------------------------------
*/
$config['timedoctor_client_id']         = '151_a4y6hen992go4sko8wo8scgsgoowwwwo8sc8gwk8g8ocsgkc4';
//$config['timedoctor_client_id']         = '153_1kxtugzgzrtw808k4kwo4scws40sc44wscckgc8g480g08sck4';
$config['timedoctor_company_id']        = '306864';
$config['timedoctor_secret_key']        = 'vs5blnfhqtws4ok8kg8wwwwksggscwssc44cg4ow48owkcg8k';
//$config['timedoctor_secret_key']        = '2gyrw9qim6yoo4sskw4ksk4kk4wsos8cw0oow80cok8gk408cc';
$config['timedoctor_admin_profile_id']  = '1'; //David
$config['timedoctor_url_redirect']      = 'http://pms.isodeveloper.com/admin/timedoctorauth';
$config['timedoctor_url_base']          = 'https://webapi.timedoctor.com/v1.1';
$config['timedoctor_url_auth_code']     = urlencode('https://webapi.timedoctor.com/oauth/v2/auth?client_id='.$config['timedoctor_client_id'].'&response_type=code&redirect_uri='.$config['timedoctor_url_redirect']);
$config['timedoctor_url_access_token']  = 'https://webapi.timedoctor.com/oauth/v2/token?client_id='.$config['timedoctor_client_id'].'&client_secret='.$config['timedoctor_secret_key'].'&grant_type=authorization_code&redirect_uri='.$config['timedoctor_url_redirect'].'&code=';
$config['timedoctor_url_refresh_token'] = 'https://webapi.timedoctor.com/oauth/v2/token?client_id='.$config['timedoctor_client_id'].'&client_secret='.$config['timedoctor_secret_key'].'&grant_type=refresh_token&refresh_token=';


/*
|----------------------------------------------------------------------------------------------------
| IMAP SETTING
|----------------------------------------------------------------------------------------------------
*/
$config['imap1_host']  = '{imap.gmail.com:993/imap/ssl}INBOX';
$config['imap1_login'] = 'pms@isodeveloper.com';
$config['imap1_pass']  = '9iLyS1Q96R';



/* End of file xyz.php */
/* Location: ./application/controllers/admin/xyz.php */