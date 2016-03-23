<?php

// -- SITE-WIDE VARIABLES--------------------------------------------------------------------------------------------------
/**
 * - some site-wide variables need in the [CLIENT] side templates
 *
 */
$conf['site_url'] = rtrim(base_url(), "/");
$conf['site_url_themes'] = $conf['site_url'] . '/application/themes';
$conf['site_url_themes_common'] = $conf['site_url'] . '/application/themes/'.$data['settings_general']['theme'].'/common';
$conf['site_url_themes_client'] = $conf['site_url'] . '/application/themes/'.$data['settings_general']['theme'].'/client';
$conf['site_url_themes_admin'] = $conf['site_url'] . '/application/themes/'.$data['settings_general']['theme'].'/admin';

//include main view and runtime functions class
include_once (PATHS_APPLICATION_FOLDER . 'views/common/runtime.functions.php');
include_once (PATHS_APPLICATION_FOLDER . 'views/common/common.php');


/* End of file main.php */
/* Location: ./application/views/client/main.php */