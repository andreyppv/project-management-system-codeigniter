<?php
ini_set('display_errors', 1);
require_once("__freshbooksinit.php");

$freshbooksTasks = getTasks()[1]['tasks']['task'];

$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
$teamMembers = $db->query("SELECT * FROM team_profile")->fetchAll();

function getTask_($db, $allTasks, $taskID){
	$prepared = $db->prepare("SELECT `freshbookstaskid` FROM tasks WHERE tasks_id = ?");
	$prepared->execute(array($taskID));
	$count = $prepared->rowCount();
	if($count > 0){
		$row = $prepared->fetch(PDO::FETCH_ASSOC);
		$freshbooksTaskId = $row['freshbookstaskid'];
		foreach ($allTasks as $task) {
			if($task['task_id'] == $freshbooksTaskId){
				return $task;
			}else{
				continue;
			}
		}
	}
	return array();
}

function getTimeEntries($db, $teamID){
	$prepared = $db->prepare("SELECT * FROM timer WHERE timer_status != 'billed' AND timer_team_member_id = ?");
	$prepared->execute(array($teamID));
	return $prepared->fetchAll();
}

function getProjectName($db, $projectid){
	$prepared = $db->prepare("SELECT `projects_title` FROM projects WHERE projects_id = ?");
	$prepared->execute(array($projectid));
	$count = $prepared->rowCount();
	if($count > 0){
		$row = $prepared->fetch(PDO::FETCH_ASSOC);
		return $row['projects_title'];
	}else{
		return "";
	}
}

function getTaskDetails_($db, $taskid){
	$prepared = $db->prepare("SELECT `tasks_text` FROM tasks WHERE tasks_id = ?");
	$prepared->execute(array($taskid));
	$count = $prepared->rowCount();
	if($count > 0){
		$row = $prepared->fetch(PDO::FETCH_ASSOC);
		return $row['tasks_text'];
	}else{
		return "";
	}
}

function getTaskName($db, $taskid){
	/*UNUSED*/
}

function getTaskType($db, $taskid){
	$prepared = $db->prepare("SELECT `freshbookstaskid` FROM tasks WHERE tasks_id = ?");
	$prepared->execute(array($taskid));
	$count = $prepared->rowCount();
	if($count > 0){
		$row = $prepared->fetch(PDO::FETCH_ASSOC);
		$freshbooksTaskId = $row['freshbookstaskid'];

	}else{
		return "";
	}
}

foreach ($teamMembers as $teamMember) {
	/*Get all unbilled time entries*/
	$teamID = $teamMember['team_profile_id'];
	$staffID = $teamMember['freshbooksstaffid'];
	$hourlyRate = $teamMember['hourlyrate'];
	$name = $teamMember['team_profile_full_name'];

	$clientInvoiceTotal = 0;
	$developerInvoiceTotal = 0;
	$secondsTotal = 0;

	$invoiceEntries = array();
	//E.g array(taskid, taskseconds, freshbookstaskid, projectid, rate)
	foreach (getTimeEntries($db, $teamID) as $timeEntry) {
		$timerID = $timeEntry['timer_id'];
		$freshbooksID = $timeEntry['timer_timeentryid'];

		$seconds = $timeEntry['timer_seconds']; //600 seconds
		$minutes = floor($seconds / 60); //10 minutes
		$hours = $minutes / 60; //0.16667 hours

		$new_seconds = $timeEntry['timer_seconds']; //600 seconds
		$new_minutes = floor($seconds / 60); //10 minutes
		$new_hours = $minutes / 60; //0.16667 hours

		$new_seconds -= ($new_minutes * 60);
		$new_minutes -= ($new_hours * 60);

		$totalTime = "Total Time: ";
		
		if($new_hours > 0){
			$totalTime .= round($new_hours, 2) . " hours ";
		}
		
		if($new_minutes > 0){
			$totalTime .= round($new_minutes, 2) . " minutes ";
		}
		
		if($new_seconds > 0){
			$totalTime .= $new_seconds . " seconds ";
		}


		$date = $timeEntry['timer_start_datetime'];
		$taskID = $timeEntry['timer_taskid'];
		$projectID = $timeEntry['timer_project_id'];
		$freshbooksTask = getTask_($db, $freshbooksTasks, $taskID);

		if(!empty($freshbooksTask)){
			$freshbooksTaskRate = @$freshbooksTask['rate'];
			$freshbooksTaskName = @$freshbooksTask['name'];
			$freshbooksTaskDescription = @$freshbooksTask['description'];

			//$template = htmlentities($freshbooksTaskName) . " | ".htmlentities($name).": ".htmlentities(getProjectName($db, $projectID))." ".htmlentities(getTaskDetails_($db, $taskID))." (http://pms.isodeveloper.com/admin/tasksexpanded/".strval(intval($projectID))."/".strval(intval($taskID)).") | " . $totalTime;
			$secondsTotal += $seconds;
			$developerInvoiceTotal += ($hourlyRate * $new_hours);
			$clientInvoiceTotal += ($freshbooksTaskRate * $new_hours);
			$invoiceEntries = array_merge($invoiceEntries, array( array("ID"=> $taskID, "Seconds" => $seconds, "Staff" => $staffID, "Project" => $projectID, "Rate" => $hourlyRate) ));
		}

		$prepared = $db->prepare("UPDATE timer SET timer_status = 'billed' WHERE timer_id = ?");
		$prepared->execute(array($timerID));
	}

	$invoiceDetails = json_encode($invoiceEntries);
	if(!empty($invoiceEntries)){
		$prepared = $db->prepare("INSERT INTO developer_invoices VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)");
		$prepared->execute(array($teamID, $secondsTotal, $hourlyRate, ($hourlyRate * ( ( $secondsTotal / 60 ) / 60 ) ), $invoiceDetails, "Pending Approval", date("Y-m-d")));
	}
}

echo "All time cleared, and updated.";
?>