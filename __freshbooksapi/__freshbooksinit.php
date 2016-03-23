<?php
ini_set('display_errors', 0);
require_once("../lib/FreshBooksRequest.php");
require_once("_oauthtests.php");
$domain = 'isodevelopers'; // https://your-subdomain.freshbooks.com/
$token = '84c2ca6356fc7f358cc8d23089fe4197'; // your api token found in your account
FreshBooksRequest::init($domain, $token);

function createClient($clientArray){
	return makeFreshbooksRequest("client.create", $clientArray);
}

function createProject($projectArray){
	return makeFreshbooksRequest("project.create", $projectArray);
}

function getProject($projectArray){
	return makeFreshbooksRequest("project.get", $projectArray);
}

function createStaff($staffArray){
	return makeFreshbooksRequest("staff.create", $staffArray);
}

function addStaffToProject($staffArray){
	return makeFreshbooksRequest("staff.update", $staffArray);
}

function addTaskToProject($projectArray){
	return makeFreshbooksRequest("project.update", $projectArray);
}

function currentProjectsForStaff($staffID, $newProjectID){
	$projects = makeFreshbooksRequest("staff.get", array("staff_id"=>$staffID))[1]['staff'];
	//print_r($projects['projects']);
	//return array_merge($projects['projects']['project'], array(array("project_id" => $newProjectID)));
	$returnArray = array();
	if(is_array($projects['projects']['project'])){
		foreach ($projects['projects']['project'] as $value) {
			if(is_array($value)){
				$returnArray = array_merge($returnArray, array(array("project_id" => $value['project_id'])));
			}else{
				//$returnArray = array("project" => $value);
				$returnArray = array_merge($returnArray, array( array("project_id" => $value) ));
			}
		}
	}else{
		if(!empty($projects['projects']['project'])){
			$project_one = $projects['projects']['project'];
			$returnArray = array_merge($returnArray, $project_one);
			$returnArray = array_merge($returnArray, $newProjectID);
		}/*else{
			$returnArray = array_merge($returnArray, array($newProjectID));
		}*/
	}

	$returnArray = array_merge($returnArray, array( array("project_id" => $newProjectID) ));

	return $returnArray;
	//return makeRawFreshbooksRequest('staff.get', '', array("staff_id"=>$staffID), '');
	//return array_merge($projects['projects'], array( "project" => array("project_id" => $newProjectID) ));
}

function currentTaskForProject($projectID, $newTaskID){
	$tasks = makeFreshbooksRequest("project.get", array("project_id"=>$projectID))[1];
	if(!empty($tasks['tasks'])){
		//return array_merge($projects['projects']['project'], array(array("project_id" => $newProjectID)));
		$returnArray = array();
		foreach ($tasks as $value) {
			$returnArray = array_merge($returnArray, $value);
		}
		$returnArray = array_merge($returnArray, array( array("task_id" => $newTaskID) ));
		return $returnArray;
	}else{
		return array("task_id" => $newTaskID);
	}
}

function getRawProjectsList($staffID){
	$result = makeRawFreshbooksRequest_('staff.get', '', array("staff_id"=>$staffID), '');
	$arr = explode('<projects>', $result);
	$arr = $arr[1];
	$arr = explode('</projects>', $arr)[0];
	return $arr;
}

function getTasks(){
	return makeFreshbooksRequest("task.list", array("per_page"=>40));
}

function getTaskDetails(){
	/*Build this function*/
}

function createTimeEntry($timeEntryArray){
	return makeFreshbooksRequest("time_entry.create", $timeEntryArray);
}

function updateTimeEntry($timeEntryArray){
	$request = makeFreshbooksRequest("time_entry.update", $timeEntryArray);
	return $request;
}

function editClient($email, $clientArray){
	/*Unused*/
}

function getClientID($email){
	return makeFreshbooksRequest("client.list", array("email"=>$email));
}

function updateTaskHoursLogged($hours, $taskId){
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
	$prepared = $db->prepare("UPDATE tasks SET hourslogged = ? WHERE tasks_id = ?");
	$prepared->execute(array($hours, $taskId));
	return true;
}

function getFreshbooksStaffIdFromId($id){
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
	$prepared = $db->prepare("SELECT * FROM team_profile WHERE team_profile_id = ?");
	$prepared->execute(array($id));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	return $row['freshbooksstaffid'];
}

function _old_addTaskToProject($projectID, $taskID){

}

function getFreshbooksTaskIdFromId($id){
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
	$prepared = $db->prepare("SELECT * FROM tasks WHERE tasks_id = ?");
	$prepared->execute(array($id));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	return $row['freshbookstaskid'];
}

function getFreshbooksTimeEntryFrom($id){
	return 0;
}

function getTaskNotesFrom($id, $userid, $projectid){
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
	$prepared = $db->prepare("SELECT * FROM tasks WHERE tasks_id = ?");
	$prepared->execute(array($id));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	$text=$row['tasks_text'];
	$text = strlen($text) > 50 ? substr($text,0,150)."..." : $text;
	return getEmployeeNameFromId($userid) . ": " . $text . " - ".getTaskLink($id, $projectid)."";
}

function getEmployeeNameFromId($id){
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
	$prepared = $db->prepare("SELECT * FROM team_profile WHERE team_profile_id = ?");
	$prepared->execute(array($id));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	return $row['team_profile_full_name'];
}

function getTaskLink($id, $projectid){
	return "http://pms.isodeveloper.com/admin/tasksexpanded/".$projectid."/".$id;
}

function getFreshbooksProjectIdFromId($id){
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
	$prepared = $db->prepare("SELECT * FROM projects WHERE projects_id = ?");
	$prepared->execute(array($id));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	return $row['freshbooksprojectid'];
}

function getSkillsFromId($id){
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
	$prepared = $db->prepare("SELECT * FROM team_profile WHERE team_profile_id = ?");
	$prepared->execute(array($id));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	return $row['skills'];
}

function makeFreshbooksRequest($apiMethod, $params){
	$fb = new FreshBooksRequest($apiMethod);
	$fb->post($params);
	$fb->request();
	if($fb->success()){ return array(true, $fb->getResponse()); }else{ return array(false, $fb->getResponse()); }
}


function makeRawFreshbooksRequest_($apiMethod, $insideArray, $params, $additionalRawXML){
	$domain = 'isodevelopers'; // https://your-subdomain.freshbooks.com/
$token = '84c2ca6356fc7f358cc8d23089fe4197'; // your api token found in your account
	$data = '<!--?xml version="1.0" encoding="utf-8"?-->  
	<request method="'.$apiMethod.'">';
	
	if(!empty($insideArray)){
    	$data .= '<'.$insideArray.'>';
	}

	foreach ($params as $key => $value) {
		if(!is_array($value)){
			$data .= '<'.$key.'>'.$value.'</'.$key.'>';
		}else{
			$data .= '<'.$key.'>';
			foreach ($value as $key2 => $value2) {
				$data .= '<'.$key2.'>'.$value2.'</'.$key2.'>';
			}
			$data .= '</'.$key.'>';
		}
	}

	$data .= $additionalRawXML;

	if(!empty($insideArray)){
		$data .= '</'.$insideArray.'>';
	}

	$data .= '</request>';

	echo $data;

	$url = "https://".$domain.".freshbooks.com/api/2.1/xml-in";
    $ch = curl_init();    // initialize curl handle
    curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
    curl_setopt($ch, CURLOPT_TIMEOUT, 40); // times out after 40s
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // add POST fields
    curl_setopt($ch, CURLOPT_USERPWD, $token. ':X');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    return $result = curl_exec($ch);
    //$json = json_decode( json_encode(simplexml_load_string($result)),true);
    //return $json;
}

function makeRawFreshbooksRequest($apiMethod, $insideArray, $params, $additionalRawXML){
	$domain = 'isodevelopers'; // https://your-subdomain.freshbooks.com/
$token = '84c2ca6356fc7f358cc8d23089fe4197'; // your api token found in your account
	$data = '<!--?xml version="1.0" encoding="utf-8"?-->  
	<request method="'.$apiMethod.'">';
	
	if(!empty($insideArray)){
    	$data .= '<'.$insideArray.'>';
	}

	foreach ($params as $key => $value) {
		if(!is_array($value)){
			$data .= '<'.$key.'>'.$value.'</'.$key.'>';
		}else{
			$data .= '<'.$key.'>';
			foreach ($value as $key2 => $value2) {
				$data .= '<'.$key2.'>'.$value2.'</'.$key2.'>';
			}
			$data .= '</'.$key.'>';
		}
	}

	$data .= $additionalRawXML;

	if(!empty($insideArray)){
		$data .= '</'.$insideArray.'>';
	}

	$data .= '</request>';

	echo $data;

	$url = "https://".$domain.".freshbooks.com/api/2.1/xml-in";
    $ch = curl_init();    // initialize curl handle
    curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
    curl_setopt($ch, CURLOPT_TIMEOUT, 40); // times out after 40s
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // add POST fields
    curl_setopt($ch, CURLOPT_USERPWD, $token. ':X');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    $json = json_decode( json_encode(simplexml_load_string($result)),true);
    return $json;
}

function getRateFromStaffId($staffid){
	$staff = makeFreshbooksRequest("staff.get", array("staff_id"=>$staffid));
	if($staff[0]){
		$staff = $staff[1];
		return $staff['staff']['rate'];
	}else{
		return 0;
	}
}

function getTimeEntiresForDateRange($staffid, $startDate, $endDate){
	@$timeEntries = makeFreshbooksRequest("time_entry.list", array(
		"date_from" => $startDate,
		"date_to" => $endDate
		))[1]['time_entries']['time_entry'];
	if(empty($timeEntries)){ return array(); }
	$time = array();
	foreach($timeEntries as $timeEntry){
		if (isset($timeEntry['staff_id'])) {
		if($timeEntry['staff_id'] == $staffid){
			$time = array_merge($time, array($timeEntry));
		}
		}
	}
	return $time;
}

function getInvoicesForClientId($startDate,$endDate,$staffid){
	return makeFreshbooksRequest("invoice.list", array(
		"date_from" => $startDate,
		"date_to" => $endDate,
		"client_id" => $staffid,
		"status" => 'paid',
		"per_page" => 9999,
		));
}
function getInvoicesForClientIdByStatus($staffid,$status){
	return makeFreshbooksRequest("invoice.list", array(
		"client_id" => $staffid,
		"status" => $status,
		"per_page" => 9999,
		));
}

function getYesterdaysHours($staffid){
	$hours = 0;
	$timeEntries = getTimeEntiresForDateRange($staffid, date('Y-m-d',strtotime("-1 days")), date('Y-m-d',strtotime("-1 days")));
	foreach ($timeEntries as $timeEntry) {
		$hours += doubleval($timeEntry['hours']);
	}
	return strval($hours);
}

function getTodaysHours($staffid){
	$hours = 0;
	$timeEntries = getTimeEntiresForDateRange($staffid, date('Y-m-d'), date('Y-m-d'));
	foreach ($timeEntries as $timeEntry) {
		$hours += doubleval($timeEntry['hours']);
	}
	return strval($hours);
}

function getWeekHours($staffid){
	$hours = 0;
	$timeEntries = getTimeEntiresForDateRange($staffid, date('Y-m-d',strtotime("-7 days")), date('Y-m-d'));
	foreach ($timeEntries as $timeEntry) {
		$hours += doubleval($timeEntry['hours']);
	}
	return strval($hours);
}

function getTotalPaid($staffid){
	return "0";
}
?>