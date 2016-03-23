<?php

	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
	$id=$_GET['id'];
	$bob=$db->query("SELECT * FROM `files` WHERE `files_id`={$id}");
	$row=$bob->fetch();
	if ($row['files_active']==1)
	$bob=$db->query("UPDATE `files` SET `files_active`=0 WHERE `files_id`={$id}");
	else
	$bob=$db->query("UPDATE `files` SET `files_active`=1 WHERE `files_id`={$id}");
?>