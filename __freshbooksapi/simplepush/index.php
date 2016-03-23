<?php
require_once("simplepush.php");
//require_once('../functions.php');
/*if(isset($_GET['message'])){
	$message = htmlentities(urldecode($_GET['message']));
	$deviceTokens = $db->query("SELECT * FROM devicetokens")->fetchAll();
	foreach($deviceTokens as $deviceToken){
		sendPushNotifcation($deviceToken['token'], $message);
	}
}*/
sendPushNotifcation("4468b164009b750b46ea849ab1af1f88d1b61029ce1505b16ac143116bdedb35", "Hello, World.");
?>