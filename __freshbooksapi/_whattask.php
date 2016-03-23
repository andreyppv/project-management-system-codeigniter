<?php
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
$prepared = $db->prepare("SELECT `taskid` FROM team_messages WHERE messages_id = ?");
$prepared->execute(array($_GET['messageid']));
$row = $prepared->fetch(PDO::FETCH_ASSOC);
if(empty($row)) { echo ""; exit; }
$taskid = $row['taskid'];
$prepared = $db->prepare("SELECT `tasks_text` FROM tasks WHERE tasks_id = ?");
$prepared->execute(array($taskid));
$row = $prepared->fetch(PDO::FETCH_ASSOC);
if(empty($row)) { echo ""; exit; }
$tasktext = $row['tasks_text'];
if(!empty($tasktext)){
	$message = "In regards to task: " . $tasktext;
}
echo $message;
?>