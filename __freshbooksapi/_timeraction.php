<?php 
ini_set('display_errors', 1);
require_once("__freshbooksinit.php");
function getRandomCode(){
    $an = "0123456789AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz";
    $su = strlen($an) - 1;
	$rand='';
	for ($i=0;$i<40;$i++)
	{
		$rand .=substr($an, rand(0, $su), 1);
		
	}
    return $rand;
}
function getTimeEntryID($db, $taskid, $projectid, $userid){
	$prepared = $db->prepare("SELECT * FROM timer WHERE timer_project_id = ? AND timer_taskid = ? AND timer_team_member_id = ? ORDER BY timer_id DESC LIMIT 1");
	$prepared->execute(array($projectid, $taskid, $userid));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	return $row['timer_timeentryid'];
}

$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
if(isset($_GET['taskid'], $_GET['projectid'], $_GET['userid'], $_GET['action'], $_GET['totalseconds'])){
	
	$taskid = intval($_GET['taskid']);
	$projectid = intval($_GET['projectid']);
	$userid = intval($_GET['userid']);
	$action = $_GET['action'];

	$seconds = intval($_GET['totalseconds']);
	$hours = floatval( ($seconds / 60) / 60 );

	$freshbooksprojectid = getFreshbooksProjectIdFromId($projectid);
	$freshbooksstaffid = getFreshbooksStaffIdFromId($userid);
	$freshbookstaskid = getFreshbooksTaskIdFromId($taskid); 

	switch ($action) {
		case 'start':
			if(empty($taskid)){ break; }
			if(empty($projectid)){ break; }
			if(empty($userid)){ break; }
			$prepared = $db->prepare("SELECT * FROM timer 
				WHERE timer_project_id = ? 
				AND timer_taskid = ? 
				AND timer_team_member_id = ? ORDER BY timer_id DESC LIMIT 1");
			$prepared->execute(array($projectid, $taskid, $userid));
			$count = $prepared->rowCount();
			$row = $prepared->fetch(PDO::FETCH_ASSOC);

			/*Most recent time entry is completed, create new one or continue old one.*/
			if($count == 0 || $row['timer_status'] == 'completed' || $row['timer_status'] == 'billed'){
				$timeEntryID = createTimeEntry(array(
					"time_entry" => array("project_id" => $freshbooksprojectid,
					"task_id" => $freshbookstaskid,
					"staff_id" => $freshbooksstaffid)
					))[1];

				if(@$timeEntryID['code'] == 30010){
					/*Not on project, add to project.*/
					addStaffToProject(array(
						"staff"=>array(
							"staff_id" => $freshbooksstaffid,
							"projects" => array(
								"project" => currentProjectsForStaff($freshbooksstaffid, $freshbooksprojectid)
							)
						)
					));
					/*Fix why timeentryid is not working, needs to add staff to project if they're not on it, then it will work fine.*/
					$timeEntryID = createTimeEntry(array(
						"time_entry" => array("project_id" => $freshbooksprojectid,
						"task_id" => $freshbookstaskid,
						"staff_id" => $freshbooksstaffid)
					))[1];
					//print_r($timeEntryID);
					$timeEntryID = $timeEntryID['time_entry_id'];
				}elseif(@$timeEntryID['code'] == 40060){ /*Missing task, add it.*/

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
						"staff_id" => $freshbooksstaffid)
					))[1];

					$timeEntryID = $timeEntryID['time_entry_id'];
				}else{
					$timeEntryID = $timeEntryID['time_entry_id'];
				}

				if(empty($taskid)){ break; }
				if(empty($projectid)){ break; }
				if(empty($userid)){ break; }

				if($taskid == 0){ break; }
				if($projectid == 0){ break; }
				if($userid == 0){ break; }

				$prepared = $db->prepare("INSERT INTO timer VALUES (NULL, ?, ?, ?, 0, ?, ?, ?)");
				$prepared->execute(array($projectid, $taskid, date("Y-m-d h:i:s"), $userid, 'started', $timeEntryID));

			}else{
				$timerID = $row['timer_id'];
				$prepared = $db->prepare("UPDATE timer SET timer_status = 'started' 
				WHERE  timer_id = ?");
				$prepared->execute(array($timerID));
				$prepared = $db->prepare("UPDATE tasks SET tasks_status = ? 
				WHERE tasks_id = ? AND tasks_project_id = ?");
				$prepared->execute(array('pending', $taskid, $projectid));
				if($seconds > 0){ 
						$prepared = $db->prepare("UPDATE timer SET timer_seconds = ? 
				WHERE timer_id = ?");
					$prepared->execute(array($seconds, $timerID));
				}
			}
			break;
		
		case 'stop':
			if(empty($taskid)){ break; }
			if(empty($projectid)){ break; }
			if(empty($userid)){ break; }
			$prepared = $db->prepare("SELECT * FROM timer 
				WHERE timer_project_id = ? 
				AND timer_taskid = ? 
				AND timer_team_member_id = ? ORDER BY timer_id DESC LIMIT 1");
			$prepared->execute(array($projectid, $taskid, $userid));
			$count = $prepared->rowCount();
			$row = $prepared->fetch(PDO::FETCH_ASSOC);

			if($count > 0 && !($row['timer_status'] == 'completed' || $row['timer_status'] == 'billed')){

				$prepared = $db->prepare("UPDATE timer SET timer_status = 'stopped' 
					WHERE timer_id = ?");
				$prepared->execute(array($row['timer_id']));
				$prepared = $db->prepare("UPDATE timer SET timer_seconds = ?
					WHERE timer_id = ?");
				$prepared->execute(array($seconds, $row['timer_id']));
				$prepared = $db->prepare("UPDATE tasks SET hourslogged = ? 
					WHERE timer_id = ?");
				$prepared->execute(array($hours, $row['timer_id']));

				$timeEntryID = getTimeEntryID($db, $taskid, $projectid, $userid);
				
				updateTimeEntry(array(
					"time_entry"=>array("time_entry_id" => $timeEntryID,
					"hours" => $hours,
					"notes" => getTaskNotesFrom($taskid, $userid, $projectid))
				));
			}	
			break;

		case 'pause':
			if(empty($taskid)){ break; }
			if(empty($projectid)){ break; }
			if(empty($userid)){ break; }
			$prepared = $db->prepare("SELECT * FROM timer 
				WHERE timer_project_id = ? 
				AND timer_taskid = ? 
				AND timer_team_member_id = ? ORDER BY timer_id DESC LIMIT 1");
			$prepared->execute(array($projectid, $taskid, $userid));
			$count = $prepared->rowCount();
			$row = $prepared->fetch(PDO::FETCH_ASSOC);

			if($count > 0 && !($row['timer_status'] == 'completed' || $row['timer_status'] == 'billed')){
				$prepared = $db->prepare("UPDATE timer SET timer_status = 'paused' 
					WHERE timer_id = ?");
				$prepared->execute(array($row['timer_id']));
				$prepared = $db->prepare("UPDATE timer SET timer_seconds = ?
					WHERE timer_id = ?");
				$prepared->execute(array($seconds, $row['timer_id']));
				$prepared = $db->prepare("UPDATE tasks SET hourslogged = ? 
					WHERE timer_id = ?");
				$prepared->execute(array($hours, $row['timer_id']));

				$timeEntryID = getTimeEntryID($db, $taskid, $projectid, $userid);
				
				updateTimeEntry(array(
					"time_entry"=>array("time_entry_id" => $timeEntryID,
					"hours" => $hours,
					"notes" => getTaskNotesFrom($taskid, $userid, $projectid))
				));
			}
			break;

		case 'completed':
			if(empty($taskid)){ break; }
			if(empty($projectid)){ break; }
			if(empty($userid)){ break; }
			$prepared = $db->prepare("SELECT * FROM timer 
				WHERE timer_project_id = ? 
				AND timer_taskid = ? 
				AND timer_team_member_id = ? ORDER BY timer_id DESC LIMIT 1");
			$prepared->execute(array($projectid, $taskid, $userid));
			$count = $prepared->rowCount();
			$row = $prepared->fetch(PDO::FETCH_ASSOC);

			if($count > 0 && !($row['timer_status'] == 'completed' || $row['timer_status'] == 'billed')){
				
				$prepared = $db->prepare("UPDATE timer SET timer_status = 'completed' 
					WHERE timer_id = ?");
				$prepared->execute(array($row['timer_id']));
				$prepared = $db->prepare("UPDATE timer SET timer_seconds = ?
					WHERE timer_id = ?");
				$prepared->execute(array($seconds, $row['timer_id']));
				$prepared = $db->prepare("UPDATE tasks SET hourslogged = ? 
					WHERE tasks_id = ? AND tasks_project_id = ?");
				$prepared->execute(array($hours, $taskid, $projectid));
				$prepared = $db->prepare("UPDATE tasks SET tasks_status = ? 
					WHERE tasks_id = ? AND tasks_project_id = ?");
				$prepared->execute(array('completed', $taskid, $projectid));
				
				if ($userid!=13)
				{
				$prepared = $db->prepare("SELECT projects_title FROM projects 
				WHERE projects_id = ?");
				$prepared->execute(array($projectid));
				$row = $prepared->fetch(PDO::FETCH_ASSOC);
				$projectName=$row['projects_title'];
				
				$prepared = $db->prepare("SELECT * FROM tasks 
				WHERE tasks_id = ? ");
				$prepared->execute(array($taskid));
				$count = $prepared->rowCount();
				$row = $prepared->fetch(PDO::FETCH_ASSOC);
				$taskName=$row['tasks_text'];
				$taskMilestone=$row['tasks_milestones_id'];
				$taskClient=$row['tasks_client_id'];
				$taskProject=$row['tasks_project_id'];
				
				$prepared = $db->prepare("SELECT team_profile_full_name FROM team_profile 
				WHERE team_profile_id = ?");
				$prepared->execute(array($userid));
				$row = $prepared->fetch(PDO::FETCH_ASSOC);
				$name=$row['team_profile_full_name'];
				
				$text="User ".$name." finished task ".$projectName." - ".$taskName.". Please review it";
				
				$datetime = new DateTime('tomorrow');
				$event_id="'".getRandomCode()."'";
				
				$prepared = $db->prepare("INSERT INTO tasks VALUES (NULL, ?,?,?,13,?,?,?,?,?,?,0,24,0, 0,1,NULL)");
				$prepared->execute(array($taskMilestone,$taskProject,$taskClient,$text,date("Y-m-d"),$datetime->format('Y-m-d'),13,'pending',$event_id));
				}
				$timeEntryID = getTimeEntryID($db, $taskid, $projectid, $userid);
				updateTimeEntry(array(
					"time_entry"=>array("time_entry_id" => $timeEntryID,
					"hours" => $hours,
					"notes" => getTaskNotesFrom($taskid, $userid, $projectid))
				));

			}
			break;

		case 'update':
			if(empty($taskid)){ break; }
			if(empty($projectid)){ break; }
			if(empty($userid)){ break; }
			$prepared = $db->prepare("SELECT * FROM timer 
				WHERE timer_project_id = ? 
				AND timer_taskid = ? 
				AND timer_team_member_id = ? ORDER BY timer_id DESC LIMIT 1");
			$prepared->execute(array($projectid, $taskid, $userid));
			$count = $prepared->rowCount();
			$row = $prepared->fetch(PDO::FETCH_ASSOC);

			if($count > 0 && !($row['timer_status'] == 'completed' || $row['timer_status'] == 'billed')){

				$prepared = $db->prepare("UPDATE timer SET timer_seconds = ?
					WHERE timer_id = ?");
				$prepared->execute(array($seconds, $row['timer_id']));
				$prepared = $db->prepare("UPDATE tasks SET hourslogged = ? 
					WHERE tasks_id = ? AND tasks_project_id = ?");
				$prepared->execute(array($hours, $taskid, $projectid));
				$timeEntryID = getTimeEntryID($db, $taskid, $projectid, $userid);
				
				$request = updateTimeEntry(array(
						"time_entry"=>array("time_entry_id" => $timeEntryID,
						"hours" => $hours,
						"date" => date('Y-m-d'),
						"notes" => getTaskNotesFrom($taskid, $userid, $projectid))
					));
				
				if(@$request[1]['code'] == 30010){
					/*Not on project, add to project.*/
					addStaffToProject(array(
						"staff"=>array(
							"staff_id" => $freshbooksstaffid,
							"projects" => array(
								"project" => currentProjectsForStaff($freshbooksstaffid, $freshbooksprojectid)
							)
						)
					));
					/*Fix why timeentryid is not working, needs to add staff to project if they're not on it, then it will work fine.*/
					$request = updateTimeEntry(array(
						"time_entry"=>array("time_entry_id" => $timeEntryID,
						"hours" => $hours,
						"date" => date("Y-m-d"),
						"notes" => getTaskNotesFrom($taskid, $userid, $projectid))
					));
				}

			}
			break;

		case 'checkforrunning':
			if(empty($taskid)){ break; }
			if(empty($projectid)){ break; }
			if(empty($userid)){ break; }
			$prepared = $db->prepare("SELECT * FROM timer 
				WHERE timer_status = ?
				AND timer_team_member_id = ? ORDER BY timer_id DESC LIMIT 1");
			$prepared->execute(array('started', $userid));

			$count = $prepared->rowCount();
			if($count > 0){
				echo 'running';
			}else{
				echo 'not running';
			}
			break;

		default:
			/*Nothing*/
			break;
	}
}
?>