<?php
ini_set('display_errors', 1);
require_once("_oauthtests.php");
require_once("__freshbooksinit.php");
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");	

echo '<pre>';



foreach (getFreshbooksTasks($db) as $task) {
	
	addUpdateTimeEntry($db, $task);
}

function getFreshbooksTasks($db){
	$date = "2015-06-08";
	$companyID = 306864;
	$tasks = $db->query("SELECT * FROM freshbooksTasks WHERE counted = 0");

	return $tasks;
}

function addUpdateTimeEntry($db, $task){
	$date = $task['date'];

	$taskid = $task['tasks_id'];
	$userid = $task['user_id'];
	$projectid = $task['project_id'];
	$action = 'start';

	$hours = floatval( ($task['time_total'] / 60) / 60 );

	$freshbooksprojectid = getFreshbooksProjectIdFromId($projectid);
	$freshbooksstaffid = getFreshbooksStaffIdFromId($userid);
	$freshbookstaskid = $task['freshbookstaskid']; 

	$prepared = $db->prepare("SELECT * FROM timer 
				WHERE timer_project_id = ? 
				AND timer_taskid = ? 
				AND timer_team_member_id = ? 
				AND timer_fromTimeDoctor = 1
				ORDER BY timer_id DESC LIMIT 1");
	$prepared->execute(array($projectid, $taskid, $userid));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	var_dump($row);

	if($task['timeentryid']==0){
		$timeEntryID = createTimeEntry(array(
			"time_entry" => array("project_id" => $freshbooksprojectid,
			"task_id" => $freshbookstaskid,
			"date" => $date,
			"hours" => $hours,
			"notes" => $task['notes'],
			"staff_id" => $freshbooksstaffid)
		))[1];
		
		if(@$timeEntryID['code'] == 40060){ /*Missing task, add it.*/

                addTaskToProject(array(
                    "project"=>array(
                        "project_id" => $freshbooksprojectid,
                        "tasks" => array(
                            "task" => currentTaskForProject($freshbooksprojectid, $freshbookstaskid)
                        )
                    )
                ));

                $timeEntryID = createTimeEntry(array(
                    "time_entry" => array("project_id" => $freshbooksprojectid,
                    "task_id" => $freshbookstaskid,
                    "staff_id" => $freshbooksstaffid,
                    "notes" => $task['notes'],
                    "hours" => $hours,
                    "date"=> $date)
                    ))[1];

            }
		
		$update=$db->query("UPDATE freshbooksTasks SET counted = 1, timeentryid = ".$timeEntryID['time_entry_id']." WHERE id = ".$task['id']." ");
var_dump($timeEntryID);
var_dump($freshbooksprojectid);
var_dump($freshbookstaskid);
var_dump($freshbooksstaffid);
var_dump($task['notes']);
var_dump($hours);
var_dump($date);

	}else{
		
		print_r(updateTimeEntry(array(
			"time_entry"=>array("time_entry_id" => $task['timeentryid'],
			"hours" => $hours,
			"date" => $date,
			"notes" => $task['notes']
		))));
		$update=$db->query("UPDATE freshbooksTasks SET counted = 1 WHERE id = ".$task['id']." ");


	}
}

?>