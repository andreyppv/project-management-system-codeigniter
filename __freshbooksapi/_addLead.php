<?php
	$user = "freelancer_db";
	$pass = 't8Z6MxzvB?1bipjc';
	$host = "localhost";
	$base = "admin_freelancer";
	mysql_connect($host, $user, $pass);
	mysql_select_db($base);
	
	print_r($_POST);
	
	echo $q = 'INSERT INTO leads ';

?>