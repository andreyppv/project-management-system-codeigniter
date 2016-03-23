<?php
if(isset($_GET['teammember'], $_GET['amount'], $_GET['details'])){
	$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
	$prepared = $db->prepare("INSERT INTO developer_invoices VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)");
	$prepared->execute(array($_GET['teammember'], '0', '0', $_GET['amount'], $_GET['details'], 'Pending Approval', date('Y-m-d')));
}
?>