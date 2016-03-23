<?php 
$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
foreach ($_POST as $key => $value) {
	$$key = $value;
}
if(isset($_GET['client'])){
	$type = "client";
}else{
	$type = "team";
}
$prepared = $db->prepare("INSERT INTO messages VALUES (NULL, ?, ?, ?, ?, ?)");
$prepared->execute(array($messages_project_id, date("Y-m-d h:i:s"), $messages_text, $type, $messages_by_id));
print_r($prepared->errorInfo());
?>