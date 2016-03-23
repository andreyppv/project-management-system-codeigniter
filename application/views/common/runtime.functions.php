<?php

// -- runtime_readable_filesize -------------------------------------------------------------------------------------------------
/**
 * converts numeric bytes into human readable format
 *
 * [EXAMPLE]
 * 120MB
 *
 * 
 * @param	data array
 * @return	[Currval: return data to TBS] 
 */
function runtime_readable_filesize($FieldName, &$CurrVal, &$CurrPrm)
{

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    if (is_numeric($CurrVal)) {
        $bytes = floatval($CurrVal);
        $arBytes = array(
            0 => array("UNIT" => "TB", "VALUE" => pow(1024, 4)),
            1 => array("UNIT" => "GB", "VALUE" => pow(1024, 3)),
            2 => array("UNIT" => "MB", "VALUE" => pow(1024, 2)),
            3 => array("UNIT" => "KB", "VALUE" => 1024),
            4 => array("UNIT" => "B", "VALUE" => 1),
            );

        foreach ($arBytes as $arItem) {
            if ($bytes >= $arItem["VALUE"]) {
                $result = $bytes / $arItem["VALUE"];
                $result = strval(round($result, 2)) . " " . $arItem["UNIT"];
                break;
            }
        }

        $CurrVal = $result;
    }
    //return
    return $CurrVal;

}

// -- runtime_timestamp_to_datetime -------------------------------------------------------------------------------------------------
/**
 * Formats a timestamp to a date time format
 *
 * [EXAMPLE]
 * 12-30-2014 [12:35:59]
 *
 * 
 * @param	data array
 * @return	[Currval: return data to TBS] 
 */
function runtime_timestamp_to_datetime($FieldName, &$CurrVal, &$CurrPrm)
{

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    //validate date format
    $dateformat = ($dateformat == '') ? 'm-d-Y' : $dateformat; //default

    //validate timestamp
    if (is_numeric($CurrVal)) {
        $CurrVal = date('m-d-Y [H:m:s]', $CurrVal);
    }

    //return
    return $CurrVal;

}

// -- runtime_check_avatar -------------------------------------------------------------------------------------------------
/**
 * checks if users avatar exists. If not returns the default avatar [/file/avatar/default]
 *
 * 
 * @param	data array
 * @return	[Currval: return data to TBS] 
 */
function runtime_check_avatar($FieldName, &$CurrVal, &$CurrPrm)
{

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    if (is_file(FILES_AVATARS_FOLDER . $CurrVal)) {
        return $CurrVal;
    } else {
        $CurrVal = 'default.png';
        return $CurrVal;
    }
}

// -- runtime_permission_levels_select -------------------------------------------------------------------------------------------------
/**
 * create the select list options (0,1,2,3,4) used by groups.modal.html for setting permission levels
 * for selected categories, the permssion level may only start at [1[ and not [0[. This is to allow atleast a minimum
 * permission levelf or that category. An example is [view_item_project_details] we cant have a setting of [0]
 *
 * 
 * @param	data array
 * @return	echos output to screen
 */
function runtime_permission_levels_select($FieldName, &$CurrVal, &$CurrPrm)
{

    //must view as bare minimum (minimum level 1)
    $minimum_level_one = array('my_project_details');

    //view only (maximum level 1)
    $maximum_level_one = array('clients', 'my_project_others_tasks');

    //minimum level 1 items
    if (in_array(strtolower($CurrVal), $minimum_level_one)) {
        $CurrVal = '{value: 1,text: "1"}, 
	                {value: 2,text: "2"},
			        {value: 3,text: "3"},
			        {value: 4,text: "4"}';

        return $CurrVal;
    }

    //maximum level 1 items
    if (in_array(strtolower($CurrVal), $maximum_level_one)) {
        $CurrVal = '{value: 0,text: "0"},
			        {value: 1,text: "1"}';

        return $CurrVal;
    }

    //default items
    $CurrVal = '{value: 0,text: "0"},
			        {value: 1,text: "1"}, 
	                {value: 2,text: "2"},
			        {value: 3,text: "3"},
			        {value: 4,text: "4"}';

    return $CurrVal;
}

// -- runtime_filetype_icon -------------------------------------------------------------------------------------------------
/**
 * sets the icon image for a given filetype
 *
 * 
 * @param	data array
 * @return	echos output to screen
 */
function runtime_filetype_icon($FieldName, &$CurrVal, &$CurrPrm)
{

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    if (is_file(FILES_FILETYPE_ICONS_FOLDER . '/' . $CurrVal . '.png')) {
        return $CurrVal;
    } else {
        $CurrVal = 'default';
        return $CurrVal;
    }
}

//_____________________________________________________________FORMAT DATE - RUNTIME_________________________________________________________________
function runtime_tickets_check_attachement($FieldName, &$CurrVal, &$CurrPrm)
{
    /**
     * returns download link for the attachement
     */

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    global $conf, $dbase;

    if ($CurrVal == '') {
        return $CurrVal;
    } else {
        $CurrVal = '<a href="' . $conf['site_url'] . '/files/tickets/' . $CurrVal . '" target="_blank"><i class="icon-paper-clip"></i>' . $CurrVal . '</a>';
        return $CurrVal;
    }

}

//___________________________________________________________FORMAT DATE - RUNTIME____________________________________________________________________
function runtime_date($FieldName, &$CurrVal, &$CurrPrm)
{
    /**
     * Formats a date during TBS rendering
     *
     * Usage: 
     * -------------------------------------------------------------
     * [var.this_date;onformat=runtime_functions_date]
     *
     * Example output:
     * -------------------------------------------------------------
     * 12-30-2014
     *
     */

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    //codeigniter - get language array
    $lang = $ci->data['lang'];

    /*-------------------------------------------------------------------------*/

    //validate date format
    $dateformat = ($dateformat == '') ? 'm-d-Y' : $dateformat; //default

    //return formatted date
    $CurrVal = date($dateformat, strtotime($CurrVal));

    if ($CurrVal == '01-01-1970' || $CurrVal == '') {
        $CurrVal = '---';
    }

    return $CurrVal;
}

//___________________________________________________________FORMAT DATE-TIME - RUNTIME____________________________________________________________________
function runtime_datetime($FieldName, &$CurrVal, &$CurrPrm)
{
    /**
     * Formats a date during TBS rendering
     *
     * Usage: 
     * -------------------------------------------------------------
     * [var.this_date;onformat=runtime_functions_datetime]
     *
     * Example output:
     * -------------------------------------------------------------
     * 07-10-2015 23:11:59
     *
     */

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['datetime_format'];

    //codeigniter - get language array
    $lang = $ci->data['lang'];

    /*-------------------------------------------------------------------------*/

    //validate date format
    $dateformat = ($dateformat == '') ? 'm-d-Y H:i:s' : $dateformat; //default

    //return formatted date
    if($CurrVal == '0000-00-00 00:00:00'  || $CurrVal == '') {
        $CurrVal = '---';
    } else {
        $CurrVal = date($dateformat, strtotime($CurrVal));
    }
    return $CurrVal;
}

//___________________________________________________________FORMAT DATE - RUNTIME____________________________________________________________________
function runtime_project_deadline($FieldName, &$CurrVal, &$CurrPrm)
{
    /**
     * Formats the project dealine date during TBS rendering and formats it with green or red label
     * Usage: 
     * -------------------------------------------------------------
     * [var.this_date;onformat=runtime_project_deadline]
     * Example output:
     * -------------------------------------------------------------
     * <span class="label label-danger label-projects"><i class="icon-warning-sign"></i> 11-30-2014</span>
     */

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    //validate date format
    $dateformat = ($dateformat == '') ? 'm-d-Y' : $dateformat; //default

    //check if time is valid
    if ($CurrVal == '01-01-1970' || $CurrVal == '') {
        $CurrVal = '<span class="label label-danger label-projects"><i class="icon-warning-sign"></i> ---</span>';
        return $CurrVal;
    }

    //format the deadline date
    $deadline_date = date($dateformat, strtotime($CurrVal));

    //are we running late or on time?
    $deadline = strtotime($CurrVal);
    $current_time = time();
    if ($deadline > $current_time) {
        $CurrVal = '<span class="label label-success label-projects">
                    <i class="icon-ok-sign">
                    </i> ' . $ci->data['lang']['lang_due'] . ': ' . $deadline_date . '</span>';
    } else {
        $CurrVal = '<span class="label label-danger label-projects">
                   <i class="icon-warning-sign">
                   </i> ' . $ci->data['lang']['lang_due'] . ':' . $deadline_date . '</span>';
    }

    //return formatted deadline date
    return $CurrVal;
}

//_____________________________________________________________MONTH NAMES - RUNTIME_________________________________________________________________
function runtime_months($FieldName, &$CurrVal, &$CurrPrm)
{
    /**
     * Formats a numeric month to lang month

     * Usage: 
     * -------------------------------------------------------------
     * [var.this_date;onformat=runtime_functions_formatnumber]

     * Example output:
     * -------------------------------------------------------------
     * Jan

     */

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    global $lang;

    $months = array(
        '01' => $ci->data['lang']['jan'],
        '02' => $ci->data['lang']['feb'],
        '03' => $ci->data['lang']['mar'],
        '04' => $ci->data['lang']['apr'],
        '05' => $ci->data['lang']['may'],
        '06' => $ci->data['lang']['jun'],
        '07' => $ci->data['lang']['jul'],
        '08' => $ci->data['lang']['aug'],
        '09' => $ci->data['lang']['sep'],
        '10' => $ci->data['lang']['oct'],
        '11' => $ci->data['lang']['nov'],
        '12' => $ci->data['lang']['dec']);

    if ($months[$CurrVal] != '') {
        $CurrVal = $months[$CurrVal];
    }

    return $CurrVal;
}

//_____________________________________________________________FORMAT DATE - RUNTIME_________________________________________________________________
function runtime_number($FieldName, &$CurrVal, &$CurrPrm)
{
    /**
     * Formats a number during TBS rendering without decimals

     * Usage: 
     * -------------------------------------------------------------
     * [var.this_date;onformat=runtime_functions_formatnumber]

     * Example output:
     * -------------------------------------------------------------
     * 23

     */
    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/
    if (is_numeric($CurrVal)) {
        $CurrVal = number_format($CurrVal);
    } else {
        $CurrVal = 0;
    }

    return $CurrVal;
}

//_____________________________________________________________FORMAT DATE - RUNTIME_________________________________________________________________
function runtime_project_percentage_complete($FieldName, &$CurrVal, &$CurrPrm)
{
    /**
     * returns the numeric value of a given projects completeness.

     * Usage: 
     * -------------------------------------------------------------
     * [var.this_date;onformat=runtime_functions_formatnumber_decimal]

     * Example output:
     * -------------------------------------------------------------
     * 65
     *
     */

    /** GET CODEIGNITER INSTANCE **/
    $CI = &get_instance();

    //load database models
    $CI->load->model('super_model');
    $CI->load->model('milestones_model');

    //check that the project id is valid
    if (! is_numeric($CurrVal)) {
        $CurrVal = 0;
    }

    //calculate the possible [total milestone] percentages (i.e. 5 milestones = [5* 100% = 500%])
    $total_possible_percentage = ($CI->milestones_model->countMilestones($CurrVal, 'all')) * 100;
    $CI->data['debug'][] = $CI->refresh->debug_data; //library debug

    //sum up all the current milestone percentage from all the milestone for this project
    $milestones = $CI->milestones_model->listMilestones(0, 'results', $CurrVal);
    for ($i = 0; $i < count($milestones); $i++) {
        $current_percentages_total += $milestones[$i]['percentage'];
    }

    //work out the PROJECT progress based on [$current_percentages_total/$total_possible_percentage*100]
    $project_percentage = round(($current_percentages_total / $total_possible_percentage) * 100);
    $project_percentage = (is_numeric($project_percentage)) ? $project_percentage : 0;

    /*
    //debug
    echo '<pre style="background-color:#ffffff">';
    print_r($milestones);
    echo '</pre>';
    */

    //return percentage
    $CurrVal = $project_percentage;
    return $CurrVal;

}

//_____________________________________________________________FORMAT DATE - RUNTIME_________________________________________________________________
function runtime_number_decimal($FieldName, &$CurrVal, &$CurrPrm)
{
    /**
     * Formats a number during TBS rendering with decimals

     * Usage: 
     * -------------------------------------------------------------
     * [var.this_date;onformat=runtime_functions_formatnumber_decimal]

     * Example output:
     * -------------------------------------------------------------
     * 1,000.00

     */

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    if (is_numeric($CurrVal)) {
        $CurrVal = number_format($CurrVal, 2, '.', ',');
    } else {
        $CurrVal = '0.00';
    }

    return $CurrVal;
}


//_____________________________________________________________FORMAT DATE - RUNTIME_________________________________________________________________
function runtime_number_decimal_simple($FieldName, &$CurrVal, &$CurrPrm)
{
    /**
     * Formats a number during TBS rendering with decimals

     * Usage: 
     * -------------------------------------------------------------
     * [var.this_date;onformat=runtime_functions_formatnumber_decimal]

     * Example output:
     * -------------------------------------------------------------
     * 1000.00

     */

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    if (is_numeric($CurrVal)) {
        $CurrVal = number_format($CurrVal, 2, '.', '');
    } else {
        $CurrVal = '0.00';
    }

    return $CurrVal;
}

//____________________________________________________ TABLE LABEL COLORS (for account status)______________________________________________
function runtime_status_colors($FieldName, &$CurrVal, &$CurrPrm)
{

    global $lang, $conf;
    /**
     * styling for the account status in  search results

     * Usage: 
     * -------------------------------------------------------------
     * <!--[blk1.profile_account_status;block=tr;onformat=runtime_functions_status_colors;htmlconv=no;comm]-->

     * Example output:
     * -------------------------------------------------------------
     * <span id="bns-status-badge" class="label label-warning bns-display-show">
     * Pending
     * </span>

     */

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    switch ($CurrVal) {

        case 'pending':
            $CurrVal = '<span class="label label-warning" id="bns-status-badge">' . $ci->data['lang']['lang_pending'] . '</span>';
            break;

        case 'active':
            $CurrVal = '<span class="label label-success" id="bns-status-badge">' . $ci->data['lang']['lang_active'] . '</span>';
            break;

        case 'enabled':
            $CurrVal = '<span class="label label-success" id="bns-status-badge">' . $ci->data['lang']['lang_enabled'] . '</span>';
            break;

        case 'disabled':
            $CurrVal = '<span class="label label-default-dark" id="bns-status-badge">' . $ci->data['lang']['lang_disabled'] . '</span>';
            break;

        case 'suspended':
            $CurrVal = '<span class="label label-default-dark" id="bns-status-badge">' . $ci->data['lang']['lang_suspended'] . '</span>';
            break;

        case 'paused':
            $CurrVal = '<span class="label label-warning-dark" id="bns-status-badge">' . $ci->data['lang']['lang_paused'] . '</span>';
            break;

        case 'open':
            $CurrVal = '<span class="label label-info" id="bns-status-badge">' . $ci->data['lang']['lang_open'] . '</span>';
            break;

        case 'due':
            $CurrVal = '<span class="label label-warning" id="bns-status-badge">' . $ci->data['lang']['lang_due'] . '</span>';
            break;

        case 'paid':
            $CurrVal = '<span class="label label-info" id="bns-status-badge">' . $ci->data['lang']['lang_paid'] . '</span>';
            break;

        case 'overdue':
            $CurrVal = '<span class="label label-danger" id="bns-status-badge">' . $ci->data['lang']['lang_overdue'] . '</span>';
            break;

        case 'new':
            $CurrVal = '<span class="label label-success" id="bns-status-badge">' . $ci->data['lang']['lang_new'] . '</span>';
            break;

        case 'priced':
            $CurrVal = '<span class="label label-info" id="bns-status-badge">' . $ci->data['lang']['lang_priced'] . '</span>';
            break;

        case 'answered':
            $CurrVal = '<span class="label label-warning" id="bns-status-badge">' . $ci->data['lang']['lang_answered'] . '</span>';
            break;

        case 'client-replied':
            $CurrVal = '<span class="label label-danger" id="bns-status-badge">' . $ci->data['lang']['lang_client_replied'] . '</span>';
            break;

        case 'completed':
            $CurrVal = '<span class="label label-success" id="bns-status-badge">' . $ci->data['lang']['lang_completed'] . '</span>';
            break;

        case 'closed':
            $CurrVal = '<span class="label label-default-dark" id="bns-status-badge">' . $ci->data['lang']['lang_closed'] . '</span>';
            break;

        case 'in progress':
        case 'in-progress':
            $CurrVal = '<span class="label label-info" id="bns-status-badge">' . $ci->data['lang']['lang_in_progress'] . '</span>';
            break;

        case 'behind schedule':
        case 'behind-schedule':
            $CurrVal = '<span class="label label-danger" id="bns-status-badge">' . $ci->data['lang']['lang_behind'] . '</span>';
            break;

        case 'yes':
            $CurrVal = '<span class="label label-info" id="bns-status-badge">' . $ci->data['lang']['lang_yes'] . '</span>';
            break;

        case 'no':
            $CurrVal = '<span class="label label-warning" id="bns-status-badge">' . $ci->data['lang']['lang_no'] . '</span>';
            break;

        case 'resolved':
            $CurrVal = '<span class="label label-success" id="bns-status-badge">' . $ci->data['lang']['lang_resolved'] . '</span>';
            break;

        case 'not-a-bug':
            $CurrVal = '<span class="label label-default" id="bns-status-badge">' . $ci->data['lang']['lang_not_a_bug'] . '</span>';
            break;

        case 'new-bug':
            $CurrVal = '<span class="label label-warning" id="bns-status-badge">' . $ci->data['lang']['lang_new'] . '</span>';
            break;

        default;
            $CurrVal = '<span class="label label-default-dark" id="bns-status-badge">' . $CurrVal . '</span>';
            break;
    }
    return $CurrVal;
}

//_____________________________________________TIMELINE CIRCLE COLORS ______________________________________________
function runtime_timeline_colors($FieldName, &$CurrVal, &$CurrPrm)
{

    global $lang, $conf;
    /**
     * styling for the timeline circles
     */
    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    switch ($CurrVal) {

        case 'deleted':
            $CurrVal = 'bg-danger';
            break;

        case 'milestone':
            $CurrVal = 'bg-purple';
            break;

        case 'file':
            $CurrVal = 'bg-success';
            break;

        case 'invoice':
            $CurrVal = 'bg-danger';
            break;

        case 'payment':
            $CurrVal = 'bg-success-dark';
            break;

        case 'file-message':
            $CurrVal = 'bg-info';
            break;

        case 'project-message':
            $CurrVal = 'bg-info';
            break;

        case 'task':
            $CurrVal = 'bg-warning';
            break;

        case 'project':
            $CurrVal = 'bg-information';
            break;

        case 'bug':
            $CurrVal = 'bg-warning-dark';
            break;

        default:
            $CurrVal = 'bg-info';
            break;

    }

    return $CurrVal;
}

//_____________________________________________TIMELINE CIRCLE COLORS ______________________________________________
function runtime_timeline_icons($FieldName, &$CurrVal, &$CurrPrm)
{

    global $lang, $conf;
    /**
     * styling for the timeline circles
     */

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    switch ($CurrVal) {

        case 'deleted':
            $CurrVal = 'icon-remove';
            break;

        case 'milestone':
            $CurrVal = 'icon-flag';
            break;

        case 'file':
            $CurrVal = 'icon-upload-alt';
            break;

        case 'invoice':
            $CurrVal = 'icon-list-alt';
            break;

        case 'payment':
            $CurrVal = 'icon-credit-card';
            break;

        case 'file-message':
            $CurrVal = 'icon-comments';
            break;

        case 'project-message':
            $CurrVal = 'icon-comments';
            break;

        case 'task':
            $CurrVal = 'icon-tasks';
            break;

        case 'project':
            $CurrVal = 'icon-folder-open';
            break;

        case 'bug':
            $CurrVal = 'icon-bug';
            break;

        default:
            $CurrVal = 'icon-file';
            break;
    }
}

//____________________________________________________ TABLE LABEL COLORS (for account status)______________________________________________
function runtime_country_code($FieldName, &$CurrVal, &$CurrPrm)
{
    /**
     * takes country code and returns country name
     * e.g ZWE returns Zimbabwe
     */

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/
    $country_list = array(
        'AFG' => 'Afghanistan',
        'ALA' => 'Åland Islands',
        'ALB' => 'Albania',
        'DZA' => 'Algeria',
        'ASM' => 'American Samoa',
        'AND' => 'Andorra',
        'AGO' => 'Angola',
        'AIA' => 'Anguilla',
        'ATA' => 'Antarctica',
        'ATG' => 'Antigua and Barbuda',
        'ARG' => 'Argentina',
        'ARM' => 'Armenia',
        'ABW' => 'Aruba',
        'AUS' => 'Australia',
        'AUT' => 'Austria',
        'AZE' => 'Azerbaijan',
        'BHS' => 'Bahamas',
        'BHR' => 'Bahrain',
        'BGD' => 'Bangladesh',
        'BRB' => 'Barbados',
        'BLR' => 'Belarus',
        'BEL' => 'Belgium',
        'BLZ' => 'Belize',
        'BEN' => 'Benin',
        'BMU' => 'Bermuda',
        'BTN' => 'Bhutan',
        'BOL' => 'Bolivia, Plurinational State of',
        'BES' => 'Bonaire, Sint Eustatius and Saba',
        'BIH' => 'Bosnia and Herzegovina',
        'BWA' => 'Botswana',
        'BVT' => 'Bouvet Island',
        'BRA' => 'Brazil',
        'IOT' => 'British Indian Ocean Territory',
        'BRN' => 'Brunei Darussalam',
        'BGR' => 'Bulgaria',
        'BFA' => 'Burkina Faso',
        'BDI' => 'Burundi',
        'KHM' => 'Cambodia',
        'CMR' => 'Cameroon',
        'CAN' => 'Canada',
        'CPV' => 'Cape Verde',
        'CYM' => 'Cayman Islands',
        'CAF' => 'Central African Republic',
        'TCD' => 'Chad',
        'CHL' => 'Chile',
        'CHN' => 'China',
        'CXR' => 'Christmas Island',
        'CCK' => 'Cocos (Keeling) Islands',
        'COL' => 'Colombia',
        'COM' => 'Comoros',
        'COG' => 'Congo',
        'COD' => 'Congo, the Democratic Republic of the',
        'COK' => 'Cook Islands',
        'CRI' => 'Costa Rica',
        'CIV' => 'Côte dIvoire',
        'HRV' => 'Croatia',
        'CUB' => 'Cuba',
        'CUW' => 'Curaçao',
        'CYP' => 'Cyprus',
        'CZE' => 'Czech Republic',
        'DNK' => 'Denmark',
        'DJI' => 'Djibouti',
        'DMA' => 'Dominica',
        'DOM' => 'Dominican Republic',
        'ECU' => 'Ecuador',
        'EGY' => 'Egypt',
        'SLV' => 'El Salvador',
        'GNQ' => 'Equatorial Guinea',
        'ERI' => 'Eritrea',
        'EST' => 'Estonia',
        'ETH' => 'Ethiopia',
        'FLK' => 'Falkland Islands (Malvinas)',
        'FRO' => 'Faroe Islands',
        'FJI' => 'Fiji',
        'FIN' => 'Finland',
        'FRA' => 'France',
        'GUF' => 'French Guiana',
        'PYF' => 'French Polynesia',
        'ATF' => 'French Southern Territories',
        'GAB' => 'Gabon',
        'GMB' => 'Gambia',
        'GEO' => 'Georgia',
        'DEU' => 'Germany',
        'GHA' => 'Ghana',
        'GIB' => 'Gibraltar',
        'GRC' => 'Greece',
        'GRL' => 'Greenland',
        'GRD' => 'Grenada',
        'GLP' => 'Guadeloupe',
        'GUM' => 'Guam',
        'GTM' => 'Guatemala',
        'GGY' => 'Guernsey',
        'GIN' => 'Guinea',
        'GNB' => 'Guinea-Bissau',
        'GUY' => 'Guyana',
        'HTI' => 'Haiti',
        'HMD' => 'Heard Island and McDonald Islands',
        'VAT' => 'Holy See (Vatican City State)',
        'HND' => 'Honduras',
        'HKG' => 'Hong Kong',
        'HUN' => 'Hungary',
        'ISL' => 'Iceland',
        'IND' => 'India',
        'IDN' => 'Indonesia',
        'IRN' => 'Iran, Islamic Republic of',
        'IRQ' => 'Iraq',
        'IRL' => 'Ireland',
        'IMN' => 'Isle of Man',
        'ISR' => 'Israel',
        'ITA' => 'Italy',
        'JAM' => 'Jamaica',
        'JPN' => 'Japan',
        'JEY' => 'Jersey',
        'JOR' => 'Jordan',
        'KAZ' => 'Kazakhstan',
        'KEN' => 'Kenya',
        'KIR' => 'Kiribati',
        'PRK' => 'Korea North',
        'KOR' => 'Korea South',
        'KWT' => 'Kuwait',
        'KGZ' => 'Kyrgyzstan',
        'LAO' => 'Lao',
        'LVA' => 'Latvia',
        'LBN' => 'Lebanon',
        'LSO' => 'Lesotho',
        'LBR' => 'Liberia',
        'LBY' => 'Libya',
        'LIE' => 'Liechtenstein',
        'LTU' => 'Lithuania',
        'LUX' => 'Luxembourg',
        'MAC' => 'Macao',
        'MKD' => 'Macedonia',
        'MDG' => 'Madagascar',
        'MWI' => 'Malawi',
        'MYS' => 'Malaysia',
        'MDV' => 'Maldives',
        'MLI' => 'Mali',
        'MLT' => 'Malta',
        'MHL' => 'Marshall Islands',
        'MTQ' => 'Martinique',
        'MRT' => 'Mauritania',
        'MUS' => 'Mauritius',
        'MYT' => 'Mayotte',
        'MEX' => 'Mexico',
        'FSM' => 'Micronesia',
        'MDA' => 'Moldova',
        'MCO' => 'Monaco',
        'MNG' => 'Mongolia',
        'MNE' => 'Montenegro',
        'MSR' => 'Montserrat',
        'MAR' => 'Morocco',
        'MOZ' => 'Mozambique',
        'MMR' => 'Myanmar',
        'NAM' => 'Namibia',
        'NRU' => 'Nauru',
        'NPL' => 'Nepal',
        'NLD' => 'Netherlands',
        'NCL' => 'New Caledonia',
        'NZL' => 'New Zealand',
        'NIC' => 'Nicaragua',
        'NER' => 'Niger',
        'NGA' => 'Nigeria',
        'NIU' => 'Niue',
        'NFK' => 'Norfolk Island',
        'MNP' => 'Northern Mariana Islands',
        'NOR' => 'Norway',
        'OMN' => 'Oman',
        'PAK' => 'Pakistan',
        'PLW' => 'Palau',
        'PSE' => 'Palestinian Territory',
        'PAN' => 'Panama',
        'PNG' => 'Papua New Guinea',
        'PRY' => 'Paraguay',
        'PER' => 'Peru',
        'PHL' => 'Philippines',
        'PCN' => 'Pitcairn',
        'POL' => 'Poland',
        'PRT' => 'Portugal',
        'PRI' => 'Puerto Rico',
        'QAT' => 'Qatar',
        'REU' => 'Réunion',
        'ROU' => 'Romania',
        'RUS' => 'Russian Federation',
        'RWA' => 'Rwanda',
        'BLM' => 'Saint Barthélemy',
        'SHN' => 'Saint Helena',
        'KNA' => 'Saint Kitts and Nevis',
        'LCA' => 'Saint Lucia',
        'MAF' => 'Saint Martin',
        'SPM' => 'Saint Pierre and Miquelon',
        'VCT' => 'Saint Vincent and Grenadines',
        'WSM' => 'Samoa',
        'SMR' => 'San Marino',
        'STP' => 'Sao Tome and Principe',
        'SAU' => 'Saudi Arabia',
        'SEN' => 'Senegal',
        'SRB' => 'Serbia',
        'SYC' => 'Seychelles',
        'SLE' => 'Sierra Leone',
        'SGP' => 'Singapore',
        'SXM' => 'Sint Maarten',
        'SVK' => 'Slovakia',
        'SVN' => 'Slovenia',
        'SLB' => 'Solomon Islands',
        'SOM' => 'Somalia',
        'ZAF' => 'South Africa',
        'SGS' => 'South Georgia',
        'SSD' => 'South Sudan',
        'ESP' => 'Spain',
        'LKA' => 'Sri Lanka',
        'SDN' => 'Sudan',
        'SUR' => 'Suriname',
        'SJM' => 'Svalbard and Jan Mayen',
        'SWZ' => 'Swaziland',
        'SWE' => 'Sweden',
        'CHE' => 'Switzerland',
        'SYR' => 'Syrian Arab Republic',
        'TWN' => 'Taiwan',
        'TJK' => 'Tajikistan',
        'TZA' => 'Tanzania',
        'THA' => 'Thailand',
        'TLS' => 'Timor-Leste',
        'TGO' => 'Togo',
        'TKL' => 'Tokelau',
        'TON' => 'Tonga',
        'TTO' => 'Trinidad and Tobago',
        'TUN' => 'Tunisia',
        'TUR' => 'Turkey',
        'TKM' => 'Turkmenistan',
        'TCA' => 'Turks and Caicos Islands',
        'TUV' => 'Tuvalu',
        'UGA' => 'Uganda',
        'UKR' => 'Ukraine',
        'ARE' => 'United Arab Emirates',
        'GBR' => 'United Kingdom',
        'USA' => 'United States',
        'UMI' => 'United States Islands',
        'URY' => 'Uruguay',
        'UZB' => 'Uzbekistan',
        'VUT' => 'Vanuatu',
        'VEN' => 'Venezuela',
        'VNM' => 'Viet Nam',
        'VGB' => 'Virgin Islands, British',
        'VIR' => 'Virgin Islands, U.S.',
        'WLF' => 'Wallis and Futuna',
        'ESH' => 'Western Sahara',
        'YEM' => 'Yemen',
        'ZMB' => 'Zambia',
        'ZWE' => 'Zimbabwe');

    //CHECK IF CODE IS IN ARRAY
    if (array_key_exists($CurrVal, $country_list)) {

        $CurrVal = $country_list[$CurrVal];
        return $CurrVal;

    } else {
        return $CurrVal; //$CurrVal;
    }
}

//___________________________________________________________________RETURN LANGAUGE______________________________________________
function runtime_lang($FieldName, &$CurrVal, &$CurrPrm)
{

    /**
     * takes a word/string and returns the corresponding $this->data['lang']['some_string']
     * 
     */

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get language array
    $lang = $ci->data['lang'];

    /*-------------------------------------------------------------------------*/

    //make lowercase
    $CurrVal = strtolower($CurrVal);

    //check blanks
    if ($CurrVal == '') {

        $CurrVal = '---';

        return $CurrVal;

    }

    //find in language array
    if (array_key_exists($CurrVal, $lang)) {
        $CurrVal = $lang[$CurrVal];
        return $CurrVal;
    } else {

        //remove any dashes and return back string
        $CurrVal = str_replace('_', ' ', $CurrVal); //e.g. [save_changes] = [save changes]
        return $CurrVal;
    }

}

//___________________________________________________________________RETURN LANGAUGE______________________________________________
function runtime_group_name($FieldName, &$CurrVal, &$CurrPrm)
{

    global $lang;

    /**
     * formats a group name
     * 
     */

    if ($CurrVal == '') {

        $CurrVal = '---';

        return $CurrVal;

    } else {

        $CurrVal = ucfirst(str_replace('_', ' ', $CurrVal));

        return $CurrVal;

    }
}

//___________________________________________________________________RETURN LANGAUGE______________________________________________
function runtime_lang_ucwords($FieldName, &$CurrVal, &$CurrPrm)
{

    /**
     * formats language words using ucwords
     * 
     */

    $CurrVal = ucwords(strtolower($CurrVal));
    return $CurrVal;

}

//___________________________________________________________________RETURN LANGAUGE______________________________________________
function runtime_check_blank_ucwords($FieldName, &$CurrVal, &$CurrPrm)
{

    global $lang;

    /**
     * checks if string is null. also formats with ucwords()
     * 
     */

    if ($CurrVal == '' || $CurrVal == '0000-00-00 00:00:00' || $CurrVal == 'NULL') {

        $CurrVal = '---';

        return $CurrVal;

    } else {

        $CurrVal = ucwords(strtolower($CurrVal));
        return $CurrVal;
    }
}

//___________________________________________________________________RETURN LANGAUGE______________________________________________
function runtime_check_blank_ucfirst($FieldName, &$CurrVal, &$CurrPrm)
{

    global $lang;

    /**
     * checks if string is null. also formats with ucfirst()
     * 
     */

    if ($CurrVal == '' || $CurrVal == '0000-00-00 00:00:00' || $CurrVal == 'NULL') {

        $CurrVal = '---';

        return $CurrVal;

    } else {

        $CurrVal = ucfirst(strtolower($CurrVal));
        return $CurrVal;
    }
}

//___________________________________________________________________RETURN LANGAUGE______________________________________________
function runtime_check_blank($FieldName, &$CurrVal, &$CurrPrm)
{

    global $lang;

    /**
     * checks if a merge data is [blank, NULL, 0000 datetime]
     * returns a --- to avoid table results with just gaps in them
     * 
     */

    if ($CurrVal == '' || $CurrVal == '0000-00-00 00:00:00' || $CurrVal == 'NULL') {

        $CurrVal = '---';

        return $CurrVal;

    }
}

//___________________________________________________________________RETURN LANGAUGE______________________________________________
function runtime_check_url($FieldName, &$CurrVal, &$CurrPrm)
{

    global $lang;

    /**
     * checks if the href value is a valid url
     * return javascript.void if not
     * adds http:// if needed.
     * 
     */

    if ($CurrVal == '') {

        $CurrVal = 'javascript:void(0)';

        return $CurrVal;

    } else {

        if (! preg_match('%^http://%', $CurrVal)) {
            $CurrVal = 'http://' . $CurrVal;
        }

        return $CurrVal;

    }
}

//__________________________________________RUNTIME - PRRODUCE DAYS, HOURS, MINUTES LEFT FROM FUTURE DATE___________________________________________
function tbs_runtime_timeleft($FieldName, &$CurrVal, &$CurrPrm)
{

    /**
     * (1) Calculates the amount of time left between (NOW) and a given date(unix time stamp).
     * (2) Formats the result like: 23 Days, 14 Hours, 2 Minutes
     * (Notes) Language can be set in lang file: Days, Hours, Minutes
     * similar to tbs_runtime_timeleft function (above) but calutates in the revese
     */
    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    global $lang, $conf;
    $debugcheck = $CurrVal;

    //___Get Language_______________
    $lang_years = ($ci->data['lang']['years'] == '') ? 'Years' : $ci->data['lang']['years'];
    $lang_days = ($ci->data['lang']['days'] == '') ? 'Days' : $ci->data['lang']['days'];
    $lang_hours = ($ci->data['lang']['hours'] == '') ? 'Hours' : $ci->data['lang']['hours'];
    $lang_minutes = ($ci->data['lang']['minutes'] == '') ? 'Minutes' : $ci->data['lang']['minutes'];
    $lang_year = ($ci->data['lang']['year'] == '') ? 'Year' : $ci->data['lang']['year'];
    $lang_day = ($ci->data['lang']['day'] == '') ? 'Day' : $ci->data['lang']['day'];
    $lang_hour = ($ci->data['lang']['hour'] == '') ? 'Hour' : $ci->data['lang']['hour'];
    $lang_minute = ($ci->data['lang']['minute'] == '') ? 'Minute' : $ci->data['lang']['minute'];

    //__Calculate Seconds Left__________
    $seconds = $CurrVal - time();

    $the_years = floor($seconds / 31536000);
    if ($the_years == 1) {
        $years = "$the_years $lang_year, ";
    }
    if ($the_years > 1) {
        $years = "$the_years $lang_years, ";
    }
    if ($the_years <= 0) {
        $years = '';
    }
    $seconds %= 31536000;

    $the_days = floor($seconds / 86400);
    if ($the_days == 1) {
        $days = "$the_days $lang_day, ";
    }
    if ($the_days > 1) {
        $days = "$the_days $lang_days, ";
    }
    if ($the_days <= 0) {
        $days = '';
    }
    $seconds %= 86400;

    $the_hours = floor($seconds / 3600);
    if ($the_hours == 1) {
        $hours = "$the_hours $lang_hour, ";
    }
    if ($the_hours > 1) {
        $hours = "$the_hours $lang_hours, ";
    }
    if ($the_hours <= 0) {
        $hours = '';
    }
    $seconds %= 3600;

    $the_minutes = floor($seconds / 60);
    if ($the_minutes == 1) {
        $minutes = "$the_minutes $lang_minute, ";
    }
    if ($the_minutes > 1) {
        $minutes = "$the_minutes $lang_minutes, ";
    }
    if ($the_minutes <= 0) {
        $minutes = '';
    }
    $seconds %= 60;

    $CurrVal = trim("$years $days $hours $minutes");

}

//__________________________________________RUNTIME - PRRODUCE DAYS, HOURS, MINUTES LEFT FROM FUTURE DATE___________________________________________
function tbs_runtime_daysleft($FieldName, &$CurrVal, &$CurrPrm)
{

    /**
     * (1) Calculates days left between (NOW) and a given date(mysql date forma yyyy-mm-dd).
     */

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    if ($CurrVal == '') {
        return '---';
    }

    $time_left = strtotime($CurrVal) - time();

    $CurrVal = floor($time_left / 86400);

    return $CurrVal;
}

//________________________________________________________________FORMT SECONDS INTO HOURS______________________________________________________
/**
 * Takes a mysql datetime input and returns it as  1hr 30min ago (how long ago)
 * if date is more than 24hrs in the past, it will just return the date/time...
 * formatted to the current system date-time setting
 * [Example] 1hr 30min
 * 
 */
function runtime_time_ago($FieldName, &$CurrVal, &$CurrPrm)
{

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    //codeigniter - get language array
    $lang = $ci->data['lang'];
    /*-------------------------------------------------------------------------*/

    //validate date format
    $dateformat = ($dateformat == '') ? 'm-d-Y' : $dateformat; //default

    //current time
    $current_time = time();
    $supliedtime = strtotime($CurrVal);

    //validate supplied date
    if (! is_numeric($supliedtime)) {
        $CurrVal = '---';
        return $CurrVal;

    }

    //the time difference
    $time = $current_time - $supliedtime;

    //if the date is more than 24hrs ago, simply return it
    if ($time > 86400) {

        $CurrVal = date($dateformat, strtotime($CurrVal));

        return $CurrVal;

    }

    //declare
    $hour = '';
    $seconds = '';
    $second = '';
    $minutes = '';
    $hours = '';
    $minute = '';
    

    //get the hours
    $h = floor($time / 3600);
    if ($h == 1) {
        $hours = $h . $ci->data['lang']['lang_time_hr'] . ' ';
        $hour = true;
    }
    if ($h > 1) {
        $hours = $h . $ci->data['lang']['lang_time_hrs'] . ' ';
        $hour = true;
    }

    //get the minutes
    $m = floor(($time - ($h * 3600)) / 60);
    if ($m == 1) {
        $minutes = $m . $ci->data['lang']['lang_time_min'] . ' ';
        $minute = true;
    }
    if ($m > 1) {
        $minutes = $m . $ci->data['lang']['lang_time_mins'] . ' ';
        $minute = true;
    }

    //get the seconds
    $s = $time - ($h * 3600 + $m * 60);
    if ($s == 1) {
        $seconds = $s . $ci->data['lang']['lang_time_sec'] . ' ';
        $second = true;
    }
    if ($s > 1) {
        $seconds = $s . $ci->data['lang']['lang_time_secs'] . ' ';
        $second = true;
    }

    if ($s == 0) {
        $seconds = $s . $ci->data['lang']['lang_time_secs'] . ' ';
        $second = true;
    }

    //create final output [2hrs 10mins] or [0mins]
    if (! $hour && ! $minute) {
        //show seconds only
        $CurrVal = $seconds . $ci->data['lang']['lang_time_ago']; //e.g. 30secs ago
    } else {
        //show hours and minutes only
        $CurrVal = $hours . $minutes . $ci->data['lang']['lang_time_ago']; //e.g 1hr 12mins ago
    }

    return $CurrVal;
}

//__________________________________________RUNTIME - PRRODUCE DAYS, HOURS, MINUTES LEFT FROM FUTURE DATE___________________________________________
function runtime_time_elapsed($FieldName, &$CurrVal, &$CurrPrm)
{

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    //codeigniter - get language array
    $lang = $ci->data['lang'];
    /*-------------------------------------------------------------------------*/

    //___Get Language_______________
    $lang_years = $ci->data['lang']['lang_time_years'];
    $lang_days = $ci->data['lang']['lang_time_days'];
    $lang_hours = $ci->data['lang']['lang_time_hours'];
    $lang_minutes = $ci->data['lang']['lang_time_minutes'];
    $lang_year = $ci->data['lang']['lang_time_year'];
    $lang_day = $ci->data['lang']['lang_time_day'];
    $lang_hour = $ci->data['lang']['lang_time_hour'];
    $lang_minute = $ci->data['lang']['lang_time_minute'];

    //__Calculate Seconds Left__________
    $seconds = time() - $CurrVal;

    $the_years = floor($seconds / 31536000);
    if ($the_years == 1) {
        $years = "$the_years $lang_year, ";
    }
    if ($the_years > 1) {
        $years = "$the_years $lang_years, ";
    }
    if ($the_years <= 0) {
        $years = '';
    }
    $seconds %= 31536000;

    $the_days = floor($seconds / 86400);
    if ($the_days == 1) {
        $days = "$the_days $lang_day, ";
    }
    if ($the_days > 1) {
        $days = "$the_days $lang_days, ";
    }
    if ($the_days <= 0) {
        $days = '';
    }
    $seconds %= 86400;

    $the_hours = floor($seconds / 3600);
    if ($the_hours == 1) {
        $hours = "$the_hours $lang_hour, ";
    }
    if ($the_hours > 1) {
        $hours = "$the_hours $lang_hours, ";
    }
    if ($the_hours <= 0) {
        $hours = '';
    }
    $seconds %= 3600;

    $the_minutes = floor($seconds / 60);
    if ($the_minutes == 1) {
        $minutes = "$the_minutes $lang_minute, ";
    }
    if ($the_minutes > 1) {
        $minutes = "$the_minutes $lang_minutes, ";
    }
    if ($the_minutes <= 0) {
        $minutes = '';
    }
    $seconds %= 60;

    $CurrVal = trim("$years $days $hours $minutes");

}

//__________________________________________RUNTIME - PRRODUCE DAYS, HOURS, MINUTES LEFT FROM FUTURE DATE___________________________________________
function runtime_permission_setting($FieldName, &$CurrVal, &$CurrPrm)
{
    /**
     * check if a permission item is set to yes/no
     * item is translated
     * a nice formating label is added
     */

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    if ($CurrVal == 'yes') {
        $CurrVal = '<span class="label label-info " id="bns-status-badge">' . $ci->data['lang']['lang_yes'] . '</span>';
    }

    if ($CurrVal == 'no') {
        $CurrVal = '<span class="label label-default " id="bns-status-badge">' . $ci->data['lang']['lang_no'] . '</span>';
    }

    if ($CurrVal == '') {
        $CurrVal = '<span class="label label-warning " id="bns-status-badge">---</span>';
    }

    return $CurrVal;

}

//________________________________________________________________FORMT SECONDS INTO HOURS______________________________________________________
/**
 * take input if seconds and returns it formatted in Hours: Minutes: Seconds
 * [Example] 2 Hrs: 4 Mins : 10Sec
 * 
 */
function runtime_hours_spent($FieldName, &$CurrVal, &$CurrPrm)
{

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    //set time to '0' if none is specified
    if (! is_numeric($CurrVal)) {
        $CurrVal = 0;
    }

    //time from tbs merge
    $time = $CurrVal;

    //get the hours
    $h = floor($time / 3600);
    if ($h > 1) {
        $lang_hours = $ci->data['lang']['lang_time_hrs'];
    } else {
        $lang_hours = $ci->data['lang']['lang_time_hr'];
    }

    //get the minutes
    $m = floor(($time - ($h * 3600)) / 60);
    if ($m > 1) {
        $lang_mins = $ci->data['lang']['lang_time_mins'];
    } else {
        $lang_mins = $ci->data['lang']['lang_time_min'];
    }

    //get the seconds
    $s = $time - ($h * 3600 + $m * 60);
    if ($s > 1) {
        $lang_secs = $ci->data['lang']['lang_time_secs'];
    } else {
        $lang_secs = $ci->data['lang']['lang_time_sec'];
    }

    //return the time formated nicely
    $CurrVal = $h . 'hrs: ' . $m . 'mins: ' . $s . 'sec';

}

//________________________________________________________________FORMT SECONDS INTO HOURS______________________________________________________
/**
 * Takes seconds as input and formats to H:M:S 
 * [Example] 02:12:05
 * 
 */
function runtime_timer($FieldName, &$CurrVal, &$CurrPrm)
{

    //set time to '0' if none is specified
    if (! is_numeric($CurrVal)) {
        $CurrVal = 0;
    }

    //time from tbs merge
    $time = $CurrVal;

    //get the hours
    $h = floor($time / 3600);
    if ($h < 10) {
        $hrs = "0$h"; //to give 00:00:00 type formart
    } else {
        $hrs = $h;
    }

    //get the minutes
    $m = floor(($time - ($h * 3600)) / 60);
    if ($m < 10) {
        $mins = "0$m"; //to give 00:00:00 type formart
    } else {
        $mins = $m;
    }

    //get the seconds
    $s = $time - ($h * 3600 + $m * 60);
    if ($s < 10) {
        $sec = "0$s"; //to give 00:00:00 type formart
    } else {
        $sec = $s;
    }

    //return the time formated nicely
    $CurrVal = "$hrs : $mins : $sec";

}

//____________________________________________________LABEL FOR PROJECT MEMBER______________________________________________
function runtime_project_members_label($FieldName, &$CurrVal, &$CurrPrm)
{

    /*--------------------GET CODEIGNITER INSTANCE -----------------------------*/

    $ci = &get_instance();

    //codeigniter - get system date settings

    $dateformat = $ci->data['settings_general']['date_format'];

    /*-------------------------------------------------------------------------*/

    global $lang, $conf;

    switch ($CurrVal) {

        case 'yes':
            $CurrVal = '<span class="label label-success">' . $ci->data['lang']['lang_project_leader'] . '</span>';
            break;

        case 'no':
            $CurrVal = '<span class="label label-info">' . $ci->data['lang']['lang_staff_member'] . '</span>';
            break;
    }
    return $CurrVal;
}

//Added by Tomasz
function runtime_main_menu_class($FieldName, &$CurrVal, &$CurrPrm)
{
    if($CurrVal == $CurrPrm['item']) return $CurrVal = 'active';
    return $CurrVal = '';
}   

function runtime_sort_class($FieldName, &$CurrVal, &$CurrPrm)
{     
    $ci = &get_instance();
    
    $order = $ci->input->get('order');
    $order_by = $ci->input->get('orderby');
    
    $class = '';
    if($order == $CurrPrm['order']) $class = 'sorted';         
    else $class = 'sortable';
    
    if($order_by == '') $class .= ' desc';
    else $class .= ' ' . $order_by;
    
    return $CurrVal = $class;
}    

function runtime_sort_link($FieldName, &$CurrVal, &$CurrPrm)
{     
    $ci = &get_instance();
    
    $order = $ci->input->get('order');
    $order_by = $ci->input->get('orderby');
    $status = $ci->input->get('status');
    $scope = $ci->input->get('scope');
    
    $stack = array();
    if($scope) $stack[] = "scope=" . $scope;
    if($status) $stack[] = "status=" .$status;
    
    $stack[] = "order=" . $CurrPrm['order'];  
    if($order == $CurrPrm['order']) $stack[] = 'orderby=' . ($order_by == 'asc' ? 'desc' : 'asc');         
    else $stack[] = 'orderby=asc';
    
    $query = '';
    if(!empty($stack))
    {
        $query .= '?' . join('&', $stack);
    }
    
    return $CurrVal = $query;
}   

function runtime_task_status_text($FieldName, &$CurrVal, &$CurrPrm)
{
    $text = '';
    $class = '';
    switch($CurrVal)
    {
        case TASK_ASSIGNED:
            $text = 'Assigned';
            $class = 'label-default';
            break;
        case TASK_PROGRESS:
            $text = 'In Progress';
            $class = 'label-primary';
            break;
        case TASK_PENDING_CLIENT_APPROVAL:
            $text = 'Pending Client Approval';
            $class = 'label-danger';
            break;
        case TASK_PENDING_ADMIN_APPROVAL:
            $text = 'Pending Admin Approval';
            $class = 'label-warning';
            break;
        case TASK_DONE:
            $text = 'Completed';
            $class = 'label-success';
            break;
        case TASK_NOT_ASSIGNED:
            $text = 'Not Assigned';
            $class = '';
            break;
    }
    
    $result = '';
    if($CurrPrm['withlabel'] == 1)
    {
        $result = "<span class='label $class'>$text</span>";
    }
    else $result = $text;
    
    return $CurrVal = $result;    
}
//end by Tomasz
/* End of file runtime.functions.php */
/* Location: ./application/views/common/runtime.functions.php */
