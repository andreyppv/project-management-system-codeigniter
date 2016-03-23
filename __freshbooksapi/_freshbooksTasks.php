<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once("__freshbooksinit.php");
$freshbooksTasks = getTasks()[1]['tasks']['task'];

$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
$timers = $db->query("SELECT timer.*, tasks.freshbookstaskid, team_profile.freshbooksstaffid FROM timer JOIN tasks on timer.timer_taskid = tasks.tasks_id JOIN team_profile ON timer.timer_team_member_id = team_profile.team_profile_id WHERE counted = 0 ORDER BY timer_taskid DESC");
$update=$db->query("UPDATE timer SET counted = 1 WHERE counted = 0");
echo 'Timers found: '.$timers->rowCount().'<br>';
foreach ($timers as $timer)
{
	
	$timer['timer_start_datetime']=date("Y-m-d",strtotime($timer['timer_start_datetime']));
	$times[$timer['timer_taskid']]['time_total']=$times[$timer['timer_taskid']]['time_total']+$timer['timer_seconds'];
	$times[$timer['timer_taskid']]['date']=$timer['timer_start_datetime'];
	$times[$timer['timer_taskid']]['project_id']=$timer['timer_project_id'];
	$times[$timer['timer_taskid']]['task_id']=$timer['timer_taskid'];
	$times[$timer['timer_taskid']]['user_id']=$timer['timer_team_member_id'];
	$times[$timer['timer_taskid']]['freshbooksstaffid']=$timer['freshbooksstaffid'];
	$times[$timer['timer_taskid']]['freshbookstaskid']=$timer['freshbookstaskid'];
	$times[$timer['timer_taskid']]['notes']=getTaskNotesFrom($timer['timer_taskid'],$timer['timer_team_member_id'],$timer['timer_project_id']);
}

//echo '<pre>';
//print_r($times);
//echo '</pre>';

foreach ($times as $time)
{
	$query=$db->query("SELECT * FROM freshbooksTasks WHERE task_id =".$time['task_id']." AND date = '".$time['date']."'");
	
	if ($query->rowCount()==0 and $time['time_total'] != 0)
	{
		$time['notes'] = str_replace("'", "\'", $time['notes']);
		$q = "INSERT INTO freshbooksTasks (notes,date, project_id, task_id, freshbookstaskid, user_id, freshbooksstaffid, time_total) 
		VALUES ('".$time['notes']."','".$time['date']."',".$time['project_id'].",".$time['task_id'].",".$time['freshbookstaskid'].",".$time['user_id'].",".$time['freshbooksstaffid'].",".$time['time_total'].")";
		$insert=$db->query($q);
		
		$added++;
	}
	else
	{
		foreach ($query as $result) $current_id = $result[id];
		$update=$db->query("UPDATE freshbooksTasks SET counted = 0, time_total = ".$time['time_total']." WHERE id = ".$current_id." ");
		$updated++;
	}
}

echo "Added: ".$added."<br>Updated: ".$updated;
?>