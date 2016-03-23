<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// -- selectlist_clients ----------------------------------------------------------------------------------------------
/**
 * creates a pulldown list of all clients
 *
 * @param array $data: full table rows from sql query]
 * @param string $type: clients|users|users_email|team_members|team_members_email|groups|milestones|projects]
 * @param string $key: name / id] (specifies what will be put in the value="" part of the select field
 * @return array
 */
function create_pulldown_list($data, $type = 'users', $key = 'name', $selected = '')
{

    //get $CI instance
    $ci = &get_instance();

    //if data is invalid, return this option
    if (! is_array($data)) {
        return '<option value="NULL">' . $ci->data['lang']['lang_no_data_available'] . '</option>';
    }

    //determine which list we are creating
    //determine date to place in the value="" attribute of the list
    switch ($type) {

        case 'clients':
            $name_key = 'clients_company_name';
            $id_key = 'clients_id';
            break;

        case 'projects':
            $name_key = 'projects_title';
            $id_key = 'projects_id';
            break;

        case 'users':
            $name_key = 'client_users_full_name';
            $id_key = 'client_users_id';
            break;

        case 'users_email':
            $name_key = 'client_users_email';
            $id_key = 'client_users_email';
            break;

        case 'team_members':
            $name_key = 'team_profile_full_name';
            $id_key = 'team_profile_id';
            break;
			
		case 'class_team_members':
            $name_key = 'team_profile_full_name';
            $id_key = 'team_profile_id';
            break;

        case 'team_members_email':
            $name_key = 'team_profile_email';
            $id_key = 'team_profile_email';
            break;

        case 'groups':
            $name_key = 'groups_name';
            $id_key = 'groups_id';
            break;

        case 'milestones':
            $name_key = 'milestones_title';
            $id_key = 'milestones_id';
            break;

        case 'milestones_simple':
            $name_key = 'milestones_title';
            $id_key = 'milestones_id';
            break;

        case 'invoice_items':
            $name_key = 'invoice_items_title';
            $id_key = 'invoice_items_id';
            break;

        case 'tickets_departments':
            $name_key = 'department_name';
            $id_key = 'department_id';
            break;

        case 'quotation_forms':
            $name_key = 'quotationforms_title';
            $id_key = 'quotationforms_id';
            break;

        case 'payment_methods':
            $name_key = 'settings_payment_methods_name';
            $id_key = 'settings_payment_methods_name';
            break;

        case 'tasks_stage':
            $name_key = 'tasks_stage_name';
            $id_key = 'tasks_stage_id';
            break;

        default:
            return false;
    }

    //create pull down list
    $pulldown_list = '';
    for ($i = 0; $i < count($data); $i++) {

        //truncate long names
        if (strlen($data[$i][$name_key]) > 50) {
            $var = substr($data[$i][$name_key], 0, 50) . '...';
        } else {
            $var = $data[$i][$name_key];
        }

        //----add start/end dates to milestones list----------
        if ($type == 'milestones') {
            $date_format = $ci->data['settings_general']['date_format'];
            $start_date = date($date_format, strtotime($data[$i]['milestones_start_date']));
            $end_date = date($date_format, strtotime($data[$i]['milestones_end_date']));
            $start = $ci->data['lang']['lang_start'];
            $end = $ci->data['lang']['lang_end'];
            $var .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$start: $start_date]&nbsp;&nbsp;&nbsp;[$end: $end_date]";
        }

        //create pull down
        if ($key == 'name') {
            $value = $data[$i][$name_key];
        } 
		if ($key == 'id') {
            $value = $data[$i][$id_key];
        }
		else {
            $value = '.user' . $data[$i][$id_key];
        }

        $pulldown_list .= '<option value="' . $value . '"';
        if($value == $selected) $pulldown_list .= ' selected="selected"';
        $pulldown_list .= '>' . $var . '</option>';
    }

    //return pulldown/select list
    return $pulldown_list;

}

/* End of file selectlist_helper.php */
/* Location: ./application/helpers/selectlist_helper.php */
