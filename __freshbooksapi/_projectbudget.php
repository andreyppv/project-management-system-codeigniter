<?php

require_once("__freshbooksinit.php");
function calcearnings($projectid){
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
	$budget = 0;
	$projectedExpenses = 0;
	$actualExpenses = 0;

	$prepared = $db->prepare("SELECT budget FROM projects WHERE projects_id = ?");
	$prepared->execute(array($projectid));
	$row = $prepared->fetch(PDO::FETCH_ASSOC);
	$budget = $row['budget'];

	$prepared = $db->prepare("SELECT * FROM tasks WHERE tasks_project_id = ?");
	$prepared->execute(array($projectid));
	$tasks = $prepared->fetchAll();
	foreach ($tasks as $task) {
		$personDoing = $task['tasks_assigned_to_id'];
		$esthours = $task['estimatedhours'];
		$loggedhours = $task['hourslogged'];

		$prepared = $db->prepare("SELECT * FROM team_profile WHERE team_profile_id = ?");
		$prepared->execute(array($personDoing));
		$row = $prepared->fetch(PDO::FETCH_ASSOC);

		$staffid = $row['freshbooksstaffid'];
		$rate = getRateFromStaffId($staffid);
		$projectedExpenses += ( $rate * $esthours );
		$actualExpenses += ( $rate * $loggedhours );
	}
	return "
	<h2>Project budget: $".$budget."</h2>
	<h2>Project expense: $".$projectedExpenses."</h2>
	<h2>Actual Project expense: $".$actualExpenses."</h2>";
}	

if(isset($_GET['projectid'])){
	echo calcearnings($_GET['projectid']);
}
?>