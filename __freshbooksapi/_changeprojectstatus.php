<?php
ini_set('display_errors', 1);
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
if(isset($_GET['projectid'], $_GET['newstatus'])){
	$projectid = $_GET['projectid'];
	$newstatus = intval($_GET['newstatus']);
	$prepared = $db->prepare("UPDATE projects SET status = ? WHERE projects_id = ?");
	$prepared->execute(array($newstatus, $projectid));
}
?>