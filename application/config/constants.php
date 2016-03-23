<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');



/*
|--------------------------------------------------------------------------
| NEXTLOOP - FILE PATHS
|--------------------------------------------------------------------------
|
| Paths to key folders
| They must all have a trailling / in order to keep coding style uniform
|
*/
define('PATHS_APPLICATION_FOLDER', FCPATH.'application/');
define('PATHS_CACHE_FOLDER', FCPATH.'application/cache/');
define('PATHS_CONFIG_FOLDER', FCPATH.'config/');
define('PATHS_LANGUAGE_FOLDER', FCPATH.'application/language/');
define('PATHS_FONTS', FCPATH.'fonts/');
define('PATHS_LOGS_FOLDER', FCPATH.'application/logs/');
define('PATHS_CAPTCHA_FOLDER', FCPATH.'files/captcha/');
define('FILES_BASE_FOLDER', FCPATH.'files/');
define('FILES_FILETYPE_ICONS_FOLDER', FCPATH.'files/filetype_icons/');
define('FILES_TEMP_FOLDER', FCPATH.'files/temp/');
define('FILES_AVATARS_FOLDER', FCPATH.'files/avatars/');
define('FILES_PROJECT_FOLDER', FCPATH.'files/projects/');
define('FILES_TICKETS_FOLDER', FCPATH.'files/tickets/');
define('FILES_DATABASE_BACKUP_FOLDER', FCPATH.'files/backups/');
define('UPDATES_FOLDER', FCPATH.'updates/');
define('DATABASE_CONFIG_FILE', FCPATH.'application/config/database.php');


define('TASK_ASSIGNED', 0);
define('TASK_PROGRESS', 1);
define('TASK_PENDING_CLIENT_APPROVAL', 2);
define('TASK_PENDING_ADMIN_APPROVAL', 3);
define('TASK_DONE', 4);
define('TASK_NOT_ASSIGNED', 5);
/* End of file constants.php */
/* Location: ./application/config/constants.php */