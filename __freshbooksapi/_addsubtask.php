<?php
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
if(isset($_POST['task_id'], $_POST['task_by'], $_POST['task_projectid'], $_POST['task_notes'])){
	$timestamp = date("Y-m-d h:i:s A");

	$task_notes = $_POST['task_notes'];
	$task_id = $_POST['task_id'];
	$task_by = $_POST['task_by'];
	$task_projectid = $_POST['task_projectid'];
	$prepared = $db->prepare("UPDATE tasks SET tasks_description=?,updated=now() WHERE tasks_id=?");
	$bob=$prepared->execute(array($task_notes,$task_id));

}
?>