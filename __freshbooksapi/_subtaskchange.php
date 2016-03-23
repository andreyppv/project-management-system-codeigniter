<?php
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");

if(isset($_GET['status'], $_GET['subtaskid'])){
	$status = intval($_GET['status']);
	if($status >= 2){ $status = 0; }
	$subtaskid = intval($_GET['subtaskid']);
	$prepared = $db->prepare("UPDATE subtasks SET task_status = ? WHERE id = ?");
	$prepared->execute(array($status, $subtaskid));
}
?>