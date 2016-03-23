<?php
	ini_set('display_errors', 1);
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
	
	$bob=$db->query("SELECT * FROM `files`");
	
	while ($row=$bob->fetch())
	{
		$id = $row['files_id'];
	 echo $id.'<br>';	
	 $db->query("UPDATE `files` SET `files_show_name`=`files_name` WHERE `files_id`={$id}");
	}
	//$bob=$db->query("UPDATE `files` SET `files_active`=0 WHERE `files_id`={$id}");
	
?>