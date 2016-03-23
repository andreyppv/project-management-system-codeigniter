<?php
function getUserID($access_token){
	$companyID = 306864;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://webapi.timedoctor.com/v1.1/companies?access_token=".$access_token."&_format=json");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = json_decode(curl_exec($ch), true);
	$accounts = $response['accounts'];
	foreach ($accounts as $account) {
		if($account['company_id'] == $companyID){
			return $account['user_id'];
		}else{ continue; }
	}
	return "";
}

function syncProjects($access_token){
	$companyID = 306864;
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");	
	$projects = $db->query("SELECT * FROM projects")->fetchAll();
	foreach($projects as $project){
		if($project['timedoctorid'] != 0){ continue; }

		$userid = getUserID($access_token);
		$saveArray = array(
			"company_id" => $companyID,
			"user_id" => $userid,
			"project[project_name]" => $project['projects_title']
			);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://webapi.timedoctor.com/v1.1/companies/".$companyID."/users/".$userid."/projects?access_token=".$access_token);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $saveArray);
		$response = json_decode(curl_exec($ch), true);
		$projectid = $response['project_id'];

		$prepared = $db->prepare("UPDATE projects SET timedoctorid = ? WHERE projects_id = ?");
		$prepared->execute(array($projectid, $project['projects_id']));
	}
}

function syncTasks($access_token){
	$companyID = 306864;
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");	
	$projects = $db->query("SELECT * FROM projects")->fetchAll();
	foreach ($projects as $project) {
		$timeDoctorProjectId = $project['timedoctorid'];

		$tasks = $db->prepare("SELECT * FROM tasks WHERE tasks_project_id = ? AND used = 0");
		$tasks->execute(array($project['projects_id']));
		$tasks = $tasks->fetchAll();
		foreach($tasks as $task){
			$taskid = $task['timedoctortaskid'];
			//echo $taskid;
			$userid = getUserID($access_token);
			$saveArray = array(
				"company_id" => $companyID,
				"user_id" => $userid,
				"task" => array(
					"task_name" => $task['tasks_text'],
					"project_id" => $timeDoctorProjectId,
					"user_id" => getTimeDoctorUserId($task['tasks_assigned_to_id'])[1],
					"active" => true
					)
			);

			$data = json_encode($saveArray);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
			    'Content-Type: application/json',                                                                                
			    'Content-Length: ' . strlen($data))                                                                       

			);
			//echo "https://webapi.timedoctor.com/v1.1/companies/".$companyID."/users/".$userid."/tasks/".$taskid."?_format=json&access_token=".$access_token;
			curl_setopt($ch, CURLOPT_URL, "https://webapi.timedoctor.com/v1.1/companies/".$companyID."/users/".$userid."/tasks/".$taskid."?_format=json&access_token=".$access_token);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			//echo $data;
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			$response = json_decode(curl_exec($ch), true);
			print_r($response);
			
			//$taskid = $response['task_id'];
			$prepared = $db->prepare("UPDATE tasks SET used = 1 WHERE tasks_id = ?");
			$prepared->execute(array($task['tasks_id']));
			//$prepared = $db->prepare("UPDATE tasks SET timedoctortaskid = ? WHERE tasks_id = ?");
			//$prepared->execute(array($taskid, $task['tasks_id']));
		}
	}
}

function addAProject($projectname, $assignUserId){
	$companyID = 306864;
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");	
	$timeDoctorUser = getTimeDoctorUserId($assignUserId);
	$timeDoctorAccessToken = $timeDoctorUser[0];
	$timeDoctorUserId = $timeDoctorUser[1];

	if($timeDoctorUserId != 0){
		$saveArray = array(
			"company_id" => $companyID,
			"user_id" => $timeDoctorUserId,
			"project[project_name]" => $projectname
			);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://webapi.timedoctor.com/v1.1/companies/".$companyID."/users/".$timeDoctorUserId."/projects?access_token=".$timeDoctorAccessToken);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $saveArray);
		$response = json_decode(curl_exec($ch), true);
		$projectid = $response['project_id'];
		return $projectid;
	}
}

function addATask($projectid, $taskinfo, $assignUserId){
	$companyID = 306864;
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");	
	$timeDoctorProjectId = getTimeDoctorProjectId($projectid);
	$timeDoctorUser = getTimeDoctorUserId($assignUserId);
	$timeDoctorAccessToken = $timeDoctorUser[0];
	$timeDoctorUserId = $timeDoctorUser[1];

	if($timeDoctorUserId != 0 && $timeDoctorProjectId != 0){
		$taskinfo = str_replace('"', "", $taskinfo);
		$saveArray = array(
			"company_id" => $companyID,
			"user_id" => $timeDoctorUserId,
			"task[task_name]" => $taskinfo,
			"task[project_id]" => $timeDoctorProjectId,
			"task[user_id]" => $timeDoctorUserId
		);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://webapi.timedoctor.com/v1.1/companies/".$companyID."/users/".$timeDoctorUserId."/tasks?access_token=".$timeDoctorAccessToken);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $saveArray);
		$response = json_decode(curl_exec($ch), true);
		
		$taskid = $response['task_id'];

		return $taskid;
	}
}

function editTask($projectid, $taskid, $assignUserId){
	$companyID = 306864;
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");	
	$timeDoctorProjectId = getTimeDoctorProjectId($projectid);
	$timeDoctorUser = getTimeDoctorUserId($assignUserId);
	$timeDoctorAccessToken = $timeDoctorUser[0];
	$timeDoctorUserId = $timeDoctorUser[1];
	$timeDoctorTaskId = $taskid;
	if($timeDoctorUserId != 0 && $timeDoctorProjectId != 0){
		//var_dump($timeDoctorTaskId);
		$saveArray = array(
				"company_id" => $companyID,
				"user_id" => $timeDoctorUserId,
				"task" => array(
					"active" => false
					)
			);
			//var_dump($saveArray);
		$data = json_encode($saveArray);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
			    'Content-Type: application/json',                                                                                
			    'Content-Length: ' . strlen($data))                                                                       

			);
			curl_setopt($ch, CURLOPT_URL, "https://webapi.timedoctor.com/v1.1/companies/".$companyID."/users/".$timeDoctorUserId."/tasks/".$timeDoctorTaskId."?_format=json&access_token=".$timeDoctorAccessToken);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			//echo $data;
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			$response = json_decode(curl_exec($ch), true);
		//var_dump($response);
		//$taskid = $response['task_id'];
		return $response;
	}
}
function getTimeDoctorProjectId($projectid){
	$projectid = str_replace('\'', "", $projectid);
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");	
	$prepared = $db->prepare("SELECT * FROM projects WHERE projects_id = ?");
	$prepared->execute(array($projectid));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	return $row['timedoctorid'];
}

function getTimeDoctorUserId($userid){
	$userid = str_replace('\'', "", $userid);
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");	
	$prepared = $db->prepare("SELECT access_token FROM oauth WHERE user_id = ?");
	$prepared->execute(array($userid));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	
	if($prepared->rowCount() == 0){ return 0; }

	return array($row['access_token'], getUserID($row['access_token']));
}

function syncTimesheets(){
	$companyID = 306864;
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");	
	$row = $db->query("SELECT access_token FROM oauth WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
	$access_token = $row['access_token'];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://webapi.timedoctor.com/v1.1/companies/".$companyID."/worklogs?access_token=".$access_token."&_format=json&start_date=".date('Y-m-d')."&end_date=".date('Y-m-d'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = json_decode(curl_exec($ch), true);
	$worklogs = $response['worklogs']['items'];
	foreach ($worklogs as $worklog) {
		print_r($worklog);
		exit;
		$totalseconds = $worklog['length'];
		$currentTask = getCurrentTask($worklog['task_id']);
		$taskID = $currentTask['tasks_id'];
		$userID = $currentTask['tasks_assigned_to_id'];
		$projectID = getCurrentProject($worklog['project_id']);
		$action = 'start';

		/*$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://pms.isodeveloper.com/__freshbooksapi/_timeraction.php?taskid=".$taskID."&projectid=".$projectID."&userid=".$userID."&action=".$action."&totalseconds=".$totalseconds);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		echo $response;

		$_action = 'update';
		$_ch = curl_init();
		curl_setopt($_ch, CURLOPT_URL, "http://pms.isodeveloper.com/__freshbooksapi/_timeraction.php?taskid=".$taskID."&projectid=".$projectID."&userid=".$userID."&action=".$_action."&totalseconds=".$totalseconds);
		curl_setopt($_ch, CURLOPT_RETURNTRANSFER, 1);
		$_response = curl_exec($_ch);
		echo $_response;*/

		$prepared = $db->prepare("SELECT * FROM timer 
			WHERE timer_fromTimeDoctor = 1 
			AND timer_taskid = ? 
			AND timer_team_member_id = ?
			AND timer_status != 'billed'
			LIMIT 1 ORDER BY timer_id DESC");
		$prepared->execute(array($taskID, $userID));
		if($prepared->rowCount() == 0){
			$timerRow = $prepared->fetch(PDO::FETCH_ASSOC);
			print_r($timerRow);
		}else{
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

			$prepared = $db->prepare("INSERT INTO timer VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)");
			$prepared->execute(array($projectID, $taskID, date('Y-m-d h:i:s A'), $totalseconds, $userID, 'offsite', 0, 1));

			$timer_row = $db->query("SELECT `timer_id` FROM timer ORDER BY `timer_id` DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
			$timer_id = $timer_row['timer_id'];
		}
	}
}

function getCurrentTask($timerTaskId){
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");	
	$prepared = $db->prepare("SELECT * FROM tasks WHERE timedoctortaskid = ?");
	$prepared->execute(array($timerTaskId));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	if(!empty($row)){
		return $row;
	}else{
		return array();
	}
}

function getCurrentProject($projectId){
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");	
	$prepared = $db->prepare("SELECT `projects_id` FROM projects WHERE timedoctorid = ?");
	$prepared->execute(array($projectId));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	if(!empty($row)){
		return $row['projects_id'];
	}else{
		return '';
	}
}

function getCurrentUser($userId){
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");	
	$prepared = $db->prepare("SELECT `tasks_id` FROM team_profile WHERE timedoctortaskid = ?");
	$prepared->execute(array($timerTaskId));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	if(!empty($row)){
		return $row['tasks_id'];
	}else{
		return "";
	}
}

?>