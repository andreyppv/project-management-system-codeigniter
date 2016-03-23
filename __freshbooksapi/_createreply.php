<?php 
ini_set('display_errors', 1);
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
foreach ($_POST as $key => $value) {
	$$key = $value;
}
if(isset($_GET['client'])){
	$type = "client";
}else{
	$type = "team";
}
$prepared = $db->prepare("INSERT INTO messages_replies VALUES (NULL, ?, ?, ?, ?, ?, ?)");
$prepared->execute(array($messages_replies_project_id, $messages_replies_message_id, date("Y-m-d h:i:s"), $messages_replies_text, $type, $messages_replies_by_id));
print_r($prepared->errorInfo());
?>